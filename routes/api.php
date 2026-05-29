<?php

use App\Http\Controllers\Api\BioTimeSyncController;
use Illuminate\Support\Facades\Route;

Route::get('/biotime/health', [BioTimeSyncController::class, 'health']);

Route::post('/biotime/sync', [BioTimeSyncController::class, 'store'])
    ->middleware(['biotime.sync', 'throttle:biotime-sync']);
