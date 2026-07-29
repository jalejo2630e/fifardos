<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Tournament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tournaments = Tournament::where('user_id', $user->id)->get();
        $tournamentIds = $tournaments->pluck('id');

        // Goals per tournament
        $goalsByTournament = GameMatch::whereIn('tournament_id', $tournamentIds)
            ->where('status', 'finished')
            ->selectRaw('tournament_id, SUM(COALESCE(score1, 0) + COALESCE(score2, 0)) as total_goals, COUNT(*) as matches')
            ->groupBy('tournament_id')
            ->get()
            ->keyBy('tournament_id');

        $chartData = $tournaments->map(fn($t) => [
            'name' => $t->name,
            'goals' => (int) ($goalsByTournament[$t->id]->total_goals ?? 0),
            'matches' => (int) ($goalsByTournament[$t->id]->matches ?? 0),
        ]);

        // Total stats
        $totalMatches = GameMatch::whereIn('tournament_id', $tournamentIds)->count();
        $finishedMatches = GameMatch::whereIn('tournament_id', $tournamentIds)->where('status', 'finished')->count();
        $totalGoals = GameMatch::whereIn('tournament_id', $tournamentIds)
            ->where('status', 'finished')
            ->selectRaw('SUM(COALESCE(score1, 0) + COALESCE(score2, 0)) as total')
            ->value('total') ?? 0;

        $closeMatches = GameMatch::whereIn('tournament_id', $tournamentIds)
            ->where('status', 'finished')
            ->whereRaw('ABS(COALESCE(score1, 0) - COALESCE(score2, 0)) <= 1')
            ->count();
        $pressureIntensity = $finishedMatches > 0 ? round(($closeMatches / $finishedMatches) * 100) : 65;

        return Inertia::render('Analytics', [
            'stats' => [
                'totalMatches' => $totalMatches,
                'finishedMatches' => $finishedMatches,
                'totalGoals' => (int) $totalGoals,
                'pressureIntensity' => min(99, max(30, $pressureIntensity)),
                'avgGoalsPerMatch' => $finishedMatches > 0 ? round($totalGoals / $finishedMatches, 1) : 0,
                'tournamentsCount' => $tournaments->count(),
            ],
            'chartData' => $chartData,
            'metric' => request('metric'),
        ]);
    }
}
