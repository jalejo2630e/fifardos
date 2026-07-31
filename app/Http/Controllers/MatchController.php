<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\GoalScorer;
use Inertia\Inertia;

class MatchController extends Controller
{
    public function show(Tournament $tournament, GameMatch $match)
    {
        abort_unless((int) $tournament->user_id === (int) auth()->id(), 403);
        abort_unless((int) $match->tournament_id === (int) $tournament->id, 404);

        $match->load(['player1', 'player2', 'team1', 'team2', 'tournament', 'goalScorers.player']);

        $sportKey = $match->tournament?->sport ?? 'fifa';
        $sport = \App\Services\SportsCatalog::get($sportKey);

        $goalScorers = $match->goalScorers->map(fn($gs) => [
            'player_name' => $gs->player?->name ?? '—',
            'goals' => $gs->goals,
            'minutes' => $gs->minutes,
        ]);

        return Inertia::render('Matches/Show', [
            'match' => $match,
            'goalScorers' => $goalScorers,
            'sport' => [
                'key' => $sportKey,
                'name' => $sport['name'] ?? $sportKey,
                'scoring' => \App\Services\SportsCatalog::scoring($sportKey),
                'uses_penalties' => \App\Services\SportsCatalog::usesPenalties($sportKey),
            ],
        ]);
    }
}
