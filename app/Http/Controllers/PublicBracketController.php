<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\StandingsService;
use Inertia\Inertia;

class PublicBracketController extends Controller
{
    public function show(Tournament $tournament)
    {
        $tournament->load(['players', 'matches' => function ($q) {
            $q->with(['player1', 'player2'])->orderBy('round')->orderBy('id');
        }]);

        $standings = app(StandingsService::class)->calculate($tournament);
        $rounds = $tournament->matches->groupBy('round')->values();

        return Inertia::render('Public/Bracket', [
            'tournament' => $tournament,
            'standings' => $standings,
            'rounds' => $rounds,
        ]);
    }

}
