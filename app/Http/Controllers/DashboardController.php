<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Tournament;
use App\Services\StandingsService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Active tournaments with progress
        $activeTournaments = Tournament::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->withCount([
                'matches',
                'matches as matches_played' => fn($q) => $q->where('status', 'finished'),
            ])
            ->with(['players'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                $total = max($t->matches_count, 1);
                $played = $t->matches_played;
                $remaining = $total - $played;
                $pct = round(($played / $total) * 100);

                if ($remaining <= 1) $urgency = 'final';
                elseif ($remaining <= 3) $urgency = 'soon';
                elseif ($remaining <= 8) $urgency = 'normal';
                else $urgency = 'relaxed';

                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'color' => $t->color,
                    'played' => $played,
                    'total' => $total,
                    'pct' => $pct,
                    'remaining' => $remaining,
                    'urgency' => $urgency,
                    'players_count' => $t->players->count(),
                ];
            });

        // MVP: player with most goals across finished matches
        $mvp = null;
        $topScorers = Player::selectRaw('players.id, players.name, sum(COALESCE(m.score1, 0)) as total_goals, count(m.id) as matches_count')
            ->join('matches as m', function ($j) {
                $j->on('m.player1_id', '=', 'players.id')
                  ->orOn('m.player2_id', '=', 'players.id');
            })
            ->where('m.status', 'finished')
            ->where('players.tournament_id', function ($q) use ($user) {
                $q->select('id')->from('tournaments')->where('user_id', $user->id)->limit(1);
            })
            ->groupBy('players.id', 'players.name')
            ->orderBy('total_goals', 'desc')
            ->first();

        if ($topScorers) {
            $mvp = [
                'id' => $topScorers->id,
                'name' => $topScorers->name,
                'goals' => (int) $topScorers->total_goals,
                'matches' => (int) $topScorers->matches_count,
                'initials' => collect(explode(' ', $topScorers->name))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->join(''),
            ];
        }

        // Current/last match for live simulation
        $currentMatch = null;
        $lastMatch = GameMatch::with(['player1', 'player2', 'team1', 'team2', 'tournament'])
            ->where('status', 'finished')
            ->whereHas('tournament', fn($q) => $q->where('user_id', $user->id))
            ->latest('played_at')
            ->first();

        if ($lastMatch) {
            $currentMatch = [
                'id' => $lastMatch->id,
                'tournament_id' => $lastMatch->tournament_id,
                'home' => $lastMatch->competitor1Name() ?? '—',
                'away' => $lastMatch->competitor2Name() ?? '—',
                'home_score' => $lastMatch->score1 ?? 0,
                'away_score' => $lastMatch->score2 ?? 0,
                'minute' => rand(70, 90),
                'tournament' => $lastMatch->tournament?->name ?? '',
            ];
        }

        // Standings for first active tournament
        $primaryTournament = Tournament::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->with('matches')
            ->first();

        $standings = [];
        if ($primaryTournament) {
            $standings = app(StandingsService::class)->calculate($primaryTournament);
        }

        // Stats
        $totalMatches = GameMatch::whereHas('tournament', fn($q) => $q->where('user_id', $user->id))->count();
        $totalGoals = GameMatch::whereHas('tournament', fn($q) => $q->where('user_id', $user->id))
            ->where('status', 'finished')
            ->selectRaw('sum(COALESCE(score1, 0) + COALESCE(score2, 0)) as total')
            ->value('total') ?? 0;

        // Pressure intensity (fake metric based on how many close matches exist)
        $closeMatches = GameMatch::whereHas('tournament', fn($q) => $q->where('user_id', $user->id))
            ->where('status', 'finished')
            ->whereRaw('ABS(COALESCE(score1, 0) - COALESCE(score2, 0)) <= 1')
            ->count();
        $finishedMatches = GameMatch::whereHas('tournament', fn($q) => $q->where('user_id', $user->id))
            ->where('status', 'finished')
            ->count();
        $pressureIntensity = $finishedMatches > 0 ? round(($closeMatches / $finishedMatches) * 100) : 65;
        $pressureIntensity = min(99, max(30, $pressureIntensity));

        // Goleadores por torneo (basado en marcadores de partidos finalizados)
        $goleadores = [];
        $userTournaments = Tournament::where('user_id', $user->id)
            ->with(['players', 'matches' => fn($q) => $q->where('status', 'finished')])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($userTournaments as $t) {
            if ($t->matches->isEmpty()) continue;

            $goals = [];
            foreach ($t->players as $pl) {
                $goals[$pl->id] = ['id' => $pl->id, 'name' => $pl->name, 'goals' => 0, 'matches' => 0];
            }
            foreach ($t->matches as $m) {
                if (isset($goals[$m->player1_id])) {
                    $goals[$m->player1_id]['goals'] += $m->score1 ?? 0;
                    $goals[$m->player1_id]['matches']++;
                }
                if (isset($goals[$m->player2_id])) {
                    $goals[$m->player2_id]['goals'] += $m->score2 ?? 0;
                    $goals[$m->player2_id]['matches']++;
                }
            }

            $top = collect($goals)
                ->filter(fn($g) => $g['goals'] > 0)
                ->sortByDesc('goals')
                ->take(5)
                ->values()
                ->all();

            if (count($top)) {
                $goleadores[] = [
                    'id' => $t->id,
                    'name' => $t->name,
                    'color' => $t->color,
                    'status' => $t->status,
                    'scorers' => $top,
                ];
            }
        }

        return Inertia::render('Dashboard', [
            'activeTournaments' => $activeTournaments,
            'goleadores' => $goleadores,
            'mvp' => $mvp,
            'currentMatch' => $currentMatch,
            'standings' => $standings,
            'stats' => [
                'totalMatches' => $totalMatches,
                'totalGoals' => $totalGoals,
                'pressureIntensity' => $pressureIntensity,
                'advanceProbability' => round(($pressureIntensity + rand(-10, 10)) / 100, 2),
            ],
            'needsSecurityQuestions' => $user->securityQuestions()->count() < 3,
        ]);
    }
}
