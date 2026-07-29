<?php

use App\Http\Controllers\Api\Agent\AgentApiController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// --- Autenticación móvil (Sanctum tokens) ---
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// --- API de agentes / móvil (Bearer token) ---
Route::prefix('agent')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('tournaments', [AgentApiController::class, 'tournaments']);
    Route::post('tournaments', [AgentApiController::class, 'createTournament']);
    Route::get('tournaments/{id}/standings', [AgentApiController::class, 'standings']);
    Route::get('tournaments/{id}/top-scorer', [AgentApiController::class, 'topScorer']);
    Route::get('tournaments/{id}/matches', [AgentApiController::class, 'matches']);
    Route::post('tournaments/{id}/matches/{matchId}/score', [AgentApiController::class, 'recordScore']);
    Route::get('players/{id}', [AgentApiController::class, 'player']);
    Route::get('schema', [AgentApiController::class, 'schema']);
    Route::post('search', [AgentApiController::class, 'search']);
});
