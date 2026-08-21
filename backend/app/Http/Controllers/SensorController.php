<?php

namespace App\Http\Controllers;

use App\Models\SensorReading;
use App\Models\Alert;
use App\Models\DigesterStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SensorController extends Controller
{
    /**
     * POST /api/readings
     * Receives sensor data from ESP32 and stores it.
     */
    public function storeReading(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'temperature' => 'required|numeric',
            'ph'          => 'required|numeric',
            'gas_level'   => 'required|numeric',
            'pressure'    => 'required|numeric',
            'flow_rate'   => 'required|numeric',
            'status'      => 'required|integer|in:0,1,2',
            'valve_open'  => 'boolean',
        ]);

        $reading = SensorReading::create([
            'temperature' => $validated['temperature'],
            'ph'          => $validated['ph'],
            'gas_level'   => $validated['gas_level'],
            'pressure'    => $validated['pressure'],
            'flow_rate'   => $validated['flow_rate'],
            'status'      => $validated['status'],
            'valve_open'  => $validated['valve_open'] ?? false,
            'recorded_at' => now(),
        ]);

        // Update digester status (upsert on fixed status_id = 1)
        DigesterStatus::updateOrCreate(
            ['status_id' => 1],
            [
                'reading_id'     => $reading->reading_id,
                'valve_status'   => $reading->valve_open ? 'open' : 'closed',
                'overall_status' => $reading->getStatusLabelAttribute(),
            ]
        );

        return response()->json([
            'success'  => true,
            'reading_id' => $reading->reading_id,
        ], 201);
    }

    /**
     * POST /api/alerts
     * Receives alert data from ESP32 (triggered by threshold breach).
     */
    public function storeAlert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'temperature' => 'required|numeric',
            'ph'          => 'required|numeric',
            'gas_level'   => 'required|numeric',
            'pressure'    => 'required|numeric',
            'flow_rate'   => 'required|numeric',
            'alert_type'  => 'required|string|max:100',
            'severity'    => 'required|string|in:warning,critical',
            'message'     => 'required|string|max:500',
        ]);

        // First store the reading
        $reading = SensorReading::create([
            'temperature' => $validated['temperature'],
            'ph'          => $validated['ph'],
            'gas_level'   => $validated['gas_level'],
            'pressure'    => $validated['pressure'],
            'flow_rate'   => $validated['flow_rate'],
            'status'      => $validated['severity'] === 'critical' ? 2 : 1,
            'valve_open'  => $validated['pressure'] > 200.0,
            'recorded_at' => now(),
        ]);

        // Then store the alert
        $alert = Alert::create([
            'reading_id'  => $reading->reading_id,
            'alert_type'  => $validated['alert_type'],
            'severity'    => $validated['severity'],
            'message'     => $validated['message'],
        ]);

        // Update digester status
        DigesterStatus::updateOrCreate(
            ['status_id' => 1],
            [
                'reading_id'     => $reading->reading_id,
                'valve_status'   => $reading->valve_open ? 'open' : 'closed',
                'overall_status' => $reading->getStatusLabelAttribute(),
            ]
        );

        return response()->json([
            'success'   => true,
            'alert_id'  => $alert->alert_id,
            'reading_id' => $reading->reading_id,
        ], 201);
    }

    /**
     * GET /api/readings
     * Returns paginated sensor readings for dashboard.
     */
    public function getReadings(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);

        $readings = SensorReading::orderByDesc('recorded_at')
            ->skip($offset)
            ->take($limit)
            ->get();

        return response()->json($readings);
    }

    /**
     * GET /api/readings/latest
     * Returns the most recent sensor reading.
     */
    public function getLatest(): JsonResponse
    {
        $reading = SensorReading::orderByDesc('recorded_at')->first();

        if (!$reading) {
            return response()->json(['message' => 'No readings yet'], 404);
        }

        return response()->json($reading);
    }

    /**
     * GET /api/readings/history
     * Returns historical data for charts (hourly averages).
     */
    public function getHistory(Request $request): JsonResponse
    {
        $hours = $request->get('hours', 24);

        $readings = SensorReading::selectRaw("
            date_trunc('hour', recorded_at) as hour,
            avg(temperature) as temperature,
            avg(ph) as ph,
            avg(gas_level) as gas_level,
            avg(pressure) as pressure,
            avg(flow_rate) as flow_rate
        ")
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return response()->json($readings);
    }

    /**
     * GET /api/alerts
     * Returns alert history for the dashboard.
     */
    public function getAlerts(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $acknowledged = $request->get('acknowledged');

        $query = Alert::with('sensorReading')
            ->orderByDesc('created_at');

        if ($acknowledged !== null) {
            $query->where('acknowledged', filter_var($acknowledged, FILTER_VALIDATE_BOOLEAN));
        }

        $alerts = $query->take($limit)->get();

        return response()->json($alerts);
    }

    /**
     * PATCH /api/alerts/{id}/acknowledge
     * Marks an alert as acknowledged.
     */
    public function acknowledgeAlert(int $id): JsonResponse
    {
        $alert = Alert::find($id);

        if (!$alert) {
            return response()->json(['message' => 'Alert not found'], 404);
        }

        $alert->update(['acknowledged' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/status
     * Returns current digester status (for dashboard header).
     */
    public function getStatus(): JsonResponse
    {
        $status = DigesterStatus::first();
        $latest = SensorReading::orderByDesc('recorded_at')->first();

        return response()->json([
            'status' => $status,
            'latest_reading' => $latest,
        ]);
    }

    /**
     * DELETE /api/readings/prune
     * Remove readings older than N days (cleanup endpoint).
     * BUG FIX: Must delete alerts and digester_status first to avoid FK constraint violations.
     */
    public function prune(Request $request): JsonResponse
    {
        $days = $request->get('days', 90);

        $cutoff = now()->subDays($days);

        // Step 1: Delete alerts linked to old readings
        $deletedAlerts = Alert::whereIn('reading_id', function ($query) use ($cutoff) {
            $query->select('reading_id')
                ->from('sensor_readings')
                ->where('recorded_at', '<', $cutoff);
        })->delete();

        // Step 2: Delete digester_status entries linked to old readings
        $deletedStatus = DigesterStatus::whereIn('reading_id', function ($query) use ($cutoff) {
            $query->select('reading_id')
                ->from('sensor_readings')
                ->where('recorded_at', '<', $cutoff);
        })->delete();

        // Step 3: Now safe to delete the readings themselves
        $deletedReadings = SensorReading::where('recorded_at', '<', $cutoff)->delete();

        return response()->json([
            'deleted_readings' => $deletedReadings,
            'deleted_alerts' => $deletedAlerts,
            'deleted_status' => $deletedStatus,
        ]);
    }
}
