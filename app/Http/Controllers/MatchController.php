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
        $match->load(['player1', 'player2', 'tournament', 'goalScorers.player']);

        $goalScorers = $match->goalScorers->map(fn($gs) => [
            'player_name' => $gs->player?->name ?? '—',
            'goals' => $gs->goals,
            'minutes' => $gs->minutes,
        ]);

        return Inertia::render('Matches/Show', [
            'match' => $match,
            'goalScorers' => $goalScorers,
        ]);
    }
}
