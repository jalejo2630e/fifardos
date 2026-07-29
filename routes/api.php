<?php

use App\Http\Controllers\Api\Agent\AgentApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('agent')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('tournaments', [AgentApiController::class, 'tournaments']);
    Route::post('tournaments', [AgentApiController::class, 'createTournament']);
    Route::get('tournaments/{id}/standings', [AgentApiController::class, 'standings']);
    Route::get('tournaments/{id}/top-scorer', [AgentApiController::class, 'topScorer']);
    Route::get('tournaments/{id}/matches', [AgentApiController::class, 'matches']);
    Route::get('players/{id}', [AgentApiController::class, 'player']);
    Route::get('schema', [AgentApiController::class, 'schema']);
    Route::post('search', [AgentApiController::class, 'search']);
});
