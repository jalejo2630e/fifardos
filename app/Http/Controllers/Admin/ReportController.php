<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalAdmins = User::where('is_admin', true)->count();
        $totalTournaments = Tournament::count();
        $inProgress = Tournament::where('status', 'in_progress')->count();
        $completed = Tournament::where('status', 'completed')->count();
        $totalPlayers = Player::count();
        $totalMatches = GameMatch::count();
        $matchesPlayed = GameMatch::where('status', 'finished')->count();
        $matchesPending = max(0, $totalMatches - $matchesPlayed);

        $totalGoals = (int) GameMatch::where('status', 'finished')
            ->selectRaw('SUM(COALESCE(score1,0) + COALESCE(score2,0)) as g')
            ->value('g');

        $usersLast7 = User::where('created_at', '>=', now()->subDays(7))->count();
        $usersLast30 = User::where('created_at', '>=', now()->subDays(30))->count();
        $tournamentsLast7 = Tournament::where('created_at', '>=', now()->subDays(7))->count();

        // Serie de registros por día (últimos 14 días) — calculado en PHP (DB-agnóstico)
        $since = now()->subDays(13)->startOfDay();
        $rawUsers = User::where('created_at', '>=', $since)->get(['created_at']);
        $signupsByDay = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $signupsByDay[$day] = 0;
        }
        foreach ($rawUsers as $u) {
            $day = Carbon::parse($u->created_at)->format('Y-m-d');
            if (isset($signupsByDay[$day])) $signupsByDay[$day]++;
        }
        $signups = array_map(fn($day, $count) => ['day' => $day, 'count' => $count], array_keys($signupsByDay), array_values($signupsByDay));

        // Torneos recientes con dueño
        $recentTournaments = Tournament::with('user:id,name')
            ->withCount('players')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'owner' => $t->user?->name ?? '—',
                'status' => $t->status,
                'players' => $t->players_count,
                'created_at' => optional($t->created_at)->toDateString(),
            ]);

        // Usuarios recientes
        $recentUsers = User::orderByDesc('created_at')
            ->limit(6)
            ->get(['id', 'name', 'email', 'is_admin', 'created_at'])
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_admin' => (bool) $u->is_admin,
                'created_at' => optional($u->created_at)->toDateString(),
            ]);

        // Goleadores globales (tabla goal_scorers)
        $topScorers = DB::table('goal_scorers')
            ->join('players', 'goal_scorers.player_id', '=', 'players.id')
            ->selectRaw('players.name as name, SUM(goal_scorers.goals) as goals')
            ->groupBy('players.id', 'players.name')
            ->orderByDesc('goals')
            ->limit(5)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'goals' => (int) $r->goals]);

        return Inertia::render('Admin/Reportes', [
            'metrics' => [
                'users' => $totalUsers,
                'admins' => $totalAdmins,
                'tournaments' => $totalTournaments,
                'inProgress' => $inProgress,
                'completed' => $completed,
                'players' => $totalPlayers,
                'matches' => $totalMatches,
                'matchesPlayed' => $matchesPlayed,
                'matchesPending' => $matchesPending,
                'goals' => $totalGoals,
                'usersLast7' => $usersLast7,
                'usersLast30' => $usersLast30,
                'tournamentsLast7' => $tournamentsLast7,
                'avgPlayers' => $totalTournaments > 0 ? round($totalPlayers / $totalTournaments, 1) : 0,
                'avgGoalsPerMatch' => $matchesPlayed > 0 ? round($totalGoals / $matchesPlayed, 2) : 0,
            ],
            'signups' => $signups,
            'recentTournaments' => $recentTournaments,
            'recentUsers' => $recentUsers,
            'topScorers' => $topScorers,
        ]);
    }
}
