<?php

use App\Http\Controllers\Api\BioTimeBridgeController;
use App\Http\Controllers\Api\BioTimeSyncController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MembresiaController;
use App\Http\Controllers\Api\V1\PagoController;
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

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:cliente-app-auth')->group(function (): void {
        Route::post('/auth/activar', [AuthController::class, 'activar']);
        Route::post('/auth/login', [AuthController::class, 'login']);
    });

    Route::middleware(['auth:sanctum', 'cliente.app', 'cliente.sucursal', 'throttle:cliente-app-api'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/cambiar-password', [AuthController::class, 'cambiarPassword']);
        Route::get('/me', [MeController::class, 'show']);
        Route::get('/membresias', [MembresiaController::class, 'index']);
        Route::get('/pagos/pendientes', [PagoController::class, 'pendientes']);
        Route::get('/pagos', [PagoController::class, 'index']);
    });
});
