<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;

/*
|--------------------------------------------------------------------------
| API Routes — SULO Local Backend
|--------------------------------------------------------------------------
|
| These routes handle:
| - ESP32 data ingestion (POST /api/readings, /api/alerts)
| - Dashboard data serving (GET /api/readings, /api/alerts, /api/status)
|
*/

// ── ESP32 → Server (data ingestion) ──
Route::post('/readings', [SensorController::class, 'storeReading']);
Route::post('/alerts', [SensorController::class, 'storeAlert']);

// ── Dashboard → Server (data serving) ──
Route::get('/readings', [SensorController::class, 'getReadings']);
Route::get('/readings/latest', [SensorController::class, 'getLatest']);
Route::get('/readings/history', [SensorController::class, 'getHistory']);
Route::get('/alerts', [SensorController::class, 'getAlerts']);
Route::patch('/alerts/{id}/acknowledge', [SensorController::class, 'acknowledgeAlert']);
Route::get('/status', [SensorController::class, 'getStatus']);

// ── Maintenance ──
Route::delete('/readings/prune', [SensorController::class, 'prune']);
