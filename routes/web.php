<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TournamentController;
use App\Models\Tournament;
use App\Services\StandingsService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $tournament = Tournament::with('prizes')->latest()->first();
    $standings = [];
    if ($tournament && $tournament->status === 'completed') {
        $tournament->load('matches');
        $standings = app(StandingsService::class)->calculate($tournament);
    }

    return Inertia::render('Public/Landing', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'tournament' => $tournament,
        'prizes' => $tournament?->prizes->sortBy('position')->values() ?? [],
        'standings' => $standings,
    ]);
});

Route::get('/inscribirse', [App\Http\Controllers\PublicPlayerController::class, 'create'])->name('players.public.create');
Route::post('/players/register', [App\Http\Controllers\PublicPlayerController::class, 'store'])->name('players.public.store');

Route::get('/rules', function () {
    $tournament = App\Models\Tournament::with('prizes')->latest()->first();
    return Inertia::render('Public/Rules', [
        'prizes' => $tournament?->prizes->sortBy('position')->values() ?? [],
    ]);
});

Route::get('/torneos/{tournament}/bracket', [App\Http\Controllers\PublicBracketController::class, 'show'])->name('tournaments.public.bracket');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [TournamentController::class, 'index'])->name('dashboard');

    Route::get('/tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
    Route::post('/tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
    Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy'])->name('tournaments.destroy');

    Route::post('/tournaments/{tournament}/matches/{match}/score', [TournamentController::class, 'updateScore'])->name('matches.score.update');
    Route::post('/tournaments/{tournament}/matches/{match}/edit-score', [TournamentController::class, 'editScore'])->name('matches.score.edit');
    Route::post('/tournaments/{tournament}/generate-knockout', [TournamentController::class, 'generateKnockout'])->name('tournaments.generate-knockout');

    Route::get('/tournaments/{tournament}/prizes', [App\Http\Controllers\PrizeController::class, 'index'])->name('prizes.index');
    Route::post('/tournaments/{tournament}/prizes', [App\Http\Controllers\PrizeController::class, 'store'])->name('prizes.store');
    Route::put('/tournaments/{tournament}/prizes/{prize}', [App\Http\Controllers\PrizeController::class, 'update'])->name('prizes.update');
    Route::delete('/tournaments/{tournament}/prizes/{prize}', [App\Http\Controllers\PrizeController::class, 'destroy'])->name('prizes.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/admin/como-usar', function () {
        if (!auth()->user()->is_admin) abort(403);
        return \Inertia\Inertia::render('Admin/ComoUsar');
    })->name('admin.como-usar');

    Route::get('/dashboard/api-docs', function () {
        if (!auth()->user()->is_admin) abort(403);
        return app(App\Http\Controllers\ApiDocsController::class)->index();
    })->name('dashboard.api-docs');

    Route::prefix('api-tokens')->group(function () {
        Route::get('/', [App\Http\Controllers\ApiTokenController::class, 'index'])->name('api-tokens.index');
        Route::post('/', [App\Http\Controllers\ApiTokenController::class, 'store'])->name('api-tokens.store');
        Route::delete('/{tokenId}', [App\Http\Controllers\ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
    });
});

Route::post('/chat', App\Http\Controllers\GeminiChatController::class)->name('chat');

require __DIR__ . '/auth.php';
