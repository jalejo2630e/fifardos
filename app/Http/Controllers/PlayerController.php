<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\GoalScorer;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PlayerController extends Controller
{
    public function show(Player $player)
    {
        $player->load('tournament');
        abort_unless($player->tournament && (int) $player->tournament->user_id === (int) auth()->id(), 403);

        // Goals across all finished matches
        $gsData = GoalScorer::where('player_id', $player->id)
            ->join('matches', 'goal_scorers.match_id', '=', 'matches.id')
            ->where('matches.status', 'finished')
            ->selectRaw('SUM(goal_scorers.goals) as total_goals, COUNT(DISTINCT goal_scorers.match_id) as matches_played')
            ->first();

        // Goals from direct score data (for tournaments without goal_scorers)
        $directGoals = DB::table('matches')
            ->where('status', 'finished')
            ->where(function ($q) use ($player) {
                $q->where('player1_id', $player->id)->orWhere('player2_id', $player->id);
            })
            ->selectRaw('SUM(CASE WHEN player1_id = ? THEN score1 ELSE score2 END) as total_goals, COUNT(*) as matches_played', [$player->id])
            ->first();

        $totalGoals = ($gsData->total_goals ?? 0) + ($directGoals->total_goals ?? 0);
        $totalMatches = ($gsData->matches_played ?? 0) + ($directGoals->matches_played ?? 0);

        // Tournament history
        $tournaments = Tournament::where('id', $player->tournament_id)
            ->withCount(['matches', 'matches as wins' => function ($q) use ($player) {
                $q->where('status', 'finished')
                  ->where(function ($q) use ($player) {
                      $q->where(function ($q) use ($player) {
                          $q->where('player1_id', $player->id)->whereColumn('score1', '>', 'score2');
                      })->orWhere(function ($q) use ($player) {
                          $q->where('player2_id', $player->id)->whereColumn('score2', '>', 'score1');
                      });
                  });
            }])
            ->first();

        return Inertia::render('Players/Show', [
            'player' => $player,
            'stats' => [
                'total_goals' => (int) $totalGoals,
                'total_matches' => (int) $totalMatches,
                'average' => $totalMatches > 0 ? round($totalGoals / $totalMatches, 2) : 0,
            ],
            'tournaments' => $tournaments,
            'mvpCount' => 0,
        ]);
    }
}
