<?php

use App\Http\Controllers\Api\BioTimeBridgeController;
use App\Http\Controllers\Api\BioTimeSyncController;
use Illuminate\Support\Facades\Route;

Route::middleware(['biotime.sync', 'throttle:biotime-sync'])->group(function (): void {
    Route::get('/biotime/health', [BioTimeSyncController::class, 'health']);
    Route::post('/biotime/heartbeat', [BioTimeSyncController::class, 'heartbeat']);
    Route::post('/biotime/sync', [BioTimeSyncController::class, 'store']);

    Route::get('/biotime/config', [BioTimeBridgeController::class, 'config']);
    Route::get('/biotime/commands', [BioTimeBridgeController::class, 'commands']);
    Route::post('/biotime/commands/{id}/ack', [BioTimeBridgeController::class, 'ack'])
        ->whereNumber('id');
    Route::get('/biotime/roster', [BioTimeBridgeController::class, 'roster']);
});
