<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NvrController;
use App\Http\Controllers\Api\CameraController;
use App\Http\Controllers\Api\LayoutController;
use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\AuthController;

// Auth (публичные)
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Защищённые роуты
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // NVR
    Route::apiResource('nvrs', NvrController::class);

    // Камеры
    Route::apiResource('cameras', CameraController::class);

    // Раскладки
    Route::get('/layouts', [LayoutController::class, 'index']);
    Route::get('/layouts/default', [LayoutController::class, 'default']);
    Route::get('/layouts/{id}', [LayoutController::class, 'show']);

    // Архив
    Route::get('/archive/cameras', [ArchiveController::class, 'cameras']);
    Route::post('/archive/playback', [ArchiveController::class, 'startPlayback']);

    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'vms-api',
            'timestamp' => now()
        ]);
    });
});