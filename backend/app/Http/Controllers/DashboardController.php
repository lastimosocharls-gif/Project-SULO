<?php

namespace App\Http\Controllers;

use App\Models\SensorReading;
use App\Models\Alert;
use App\Models\DigesterStatus;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $latest = SensorReading::orderByDesc('recorded_at')->first();
        $status = DigesterStatus::first();
        $recentAlerts = Alert::with('sensorReading')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('dashboard', compact('latest', 'status', 'recentAlerts'));
    }
}
