<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\Player;
use App\Models\GameMatch;
use App\Services\StandingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TournamentController extends Controller
{
    const COLORS = ['#F97316', '#FFD700', '#FF6B9D', '#10B981', '#8B5CF6', '#00D4FF', '#EF4444', '#84CC16'];

    public function index()
    {
        $tournaments = Tournament::where('user_id', auth()->id())
            ->withCount(['players', 'matches', 'matches as matches_played' => fn($q) => $q->where('status', 'finished')])
            ->with(['players', 'matches' => fn($q) => $q->where('status', 'finished')])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($tournament) {
                $arr = $tournament->toArray();
                unset($arr['players'], $arr['matches']);
                $arr['leader'] = null;
                if ($tournament->matches_played > 0) {
                    $standings = app(StandingsService::class)->calculate($tournament);
                    $arr['leader'] = [
                        'name' => $standings[0]['player_name'],
                        'pts' => $standings[0]['pts'],
                    ];
                }
                return $arr;
            });

        return Inertia::render('Tournaments/Index', [
            'tournaments' => $tournaments,
        ]);
    }

    public function create()
    {
        return Inertia::render('Tournaments/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'consoles_count' => 'required|integer|min:1|max:20',
            'players' => 'required|array|min:2',
            'players.*' => 'required|string|max:255|distinct',
        ]);

        $usedColors = Tournament::where('user_id', auth()->id())->pluck('color')->toArray();
        $available = array_values(array_diff(self::COLORS, $usedColors));
        $color = empty($available) ? self::COLORS[array_rand(self::COLORS)] : $available[array_rand($available)];

        $tournament = Tournament::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'consoles_count' => $validated['consoles_count'],
            'status' => 'in_progress',
            'color' => $color,
        ]);

        foreach ($validated['players'] as $playerName) {
            Player::create([
                'tournament_id' => $tournament->id,
                'name' => $playerName,
            ]);
        }

        $this->generateMatches($tournament);

        Log::info('Torneo creado', [
            'user_id' => auth()->id(),
            'tournament_id' => $tournament->id,
            'name' => $tournament->name,
            'players' => count($validated['players']),
        ]);

        return redirect()->route('tournaments.show', $tournament);
    }

    public function show(Tournament $tournament)
    {
        $tournament->load(['players', 'matches' => function ($q) {
            $q->with(['player1', 'player2'])->orderBy('round')->orderBy('id');
        }]);

        $standings = app(StandingsService::class)->calculate($tournament);
        $allPlayed = $tournament->matches->every(fn($m) => $m->status === 'finished');

        if ($allPlayed && $tournament->status !== 'completed') {
            $tournament->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);
            $tournament = $tournament->fresh();
            $tournament->load(['players', 'matches' => function ($q) {
                $q->with(['player1', 'player2'])->orderBy('round')->orderBy('id');
            }]);
            $standings = app(StandingsService::class)->calculate($tournament);
        }

        $groupRounds = $tournament->matches->where('phase', 'group')->groupBy('round')->values();
        $knockoutMatches = $tournament->matches->where('phase', 'knockout')->sortBy('bracket_position')->values();
        $groupAllPlayed = $tournament->matches->where('phase', 'group')->every(fn($m) => $m->status === 'finished');

        return Inertia::render('Tournaments/Show', [
            'tournament' => $tournament,
            'standings' => $standings,
            'allPlayed' => $allPlayed,
            'rounds' => $groupRounds,
            'groupAllPlayed' => $groupAllPlayed,
            'knockoutMatches' => $knockoutMatches,
        ]);
    }

    public function generateKnockout(Request $request, Tournament $tournament)
    {
        $top = (int) $request->input('top', 8);
        if (!in_array($top, [2, 4, 8, 16])) {
            return redirect()->back()->with('error', 'El número de jugadores debe ser 2, 4, 8 o 16.');
        }

        $standings = app(StandingsService::class)->calculate($tournament);
        $qualified = array_slice($standings, 0, $top);

        if (count($qualified) < 2) {
            return redirect()->back()->with('error', 'Se necesitan al menos 2 jugadores para eliminatorias.');
        }

        $hasExisting = $tournament->matches()->where('phase', 'knockout')->exists();
        if ($hasExisting) {
            return redirect()->back()->with('error', 'Ya hay eliminatorias generadas. Editá los resultados directamente.');
        }

        $positions = $this->seedBracket($qualified);
        $tvCount = $tournament->consoles_count;
        $maxRound = $tournament->matches()->where('phase', 'group')->max('round') ?? 0;

        foreach ($positions as $pos => $players) {
            $round = $maxRound + match (true) {
                str_starts_with($pos, 'qf') => 1,
                str_starts_with($pos, 'sf') => 2,
                $pos === 'final' => 3,
                default => 1,
            };
            GameMatch::create([
                'tournament_id' => $tournament->id,
                'round' => $round,
                'player1_id' => $players[0],
                'player2_id' => $players[1],
                'phase' => 'knockout',
                'bracket_position' => $pos,
                'status' => 'pending',
                'tv_number' => 1,
            ]);
        }

        Log::info('Eliminatorias generadas', [
            'tournament_id' => $tournament->id,
            'top' => $top,
        ]);

        return redirect()->back();
    }

    public function updateScore(Request $request, Tournament $tournament, GameMatch $match)
    {
        $validated = $request->validate([
            'score1' => 'required|integer|min:0',
            'score2' => 'required|integer|min:0',
        ]);

        $match->update([
            'score1' => $validated['score1'],
            'score2' => $validated['score2'],
            'status' => 'finished',
            'played_at' => now(),
        ]);

        Log::info('Resultado guardado', [
            'user_id' => auth()->id(),
            'tournament_id' => $tournament->id,
            'match_id' => $match->id,
            'score' => "{$validated['score1']}-{$validated['score2']}",
        ]);

        return redirect()->back();
    }

    public function editScore(Request $request, Tournament $tournament, GameMatch $match)
    {
        $match->update([
            'score1' => null,
            'score2' => null,
            'status' => 'pending',
        ]);

        Log::info('Resultado editado (reset)', [
            'user_id' => auth()->id(),
            'tournament_id' => $tournament->id,
            'match_id' => $match->id,
        ]);

        return redirect()->back();
    }

    public function destroy(Tournament $tournament)
    {
        Log::info('Torneo eliminado', [
            'user_id' => auth()->id(),
            'tournament_id' => $tournament->id,
            'name' => $tournament->name,
        ]);

        $tournament->delete();
        return redirect()->route('dashboard');
    }

    private function seedBracket(array $qualified): array
{
    $total = count($qualified);
    $positions = [];
    $ids = array_map(fn($p) => $p['player_id'], $qualified);

    if ($total === 2) {
        $positions['final'] = [$ids[0], $ids[1]];
    } elseif ($total === 4) {
        $positions['sf_1'] = [$ids[0], $ids[3]];
        $positions['sf_2'] = [$ids[1], $ids[2]];
        $positions['final'] = [null, null];
    } elseif ($total === 8) {
        $positions['qf_1'] = [$ids[0], $ids[7]];
        $positions['qf_2'] = [$ids[3], $ids[4]];
        $positions['qf_3'] = [$ids[1], $ids[6]];
        $positions['qf_4'] = [$ids[2], $ids[5]];
        $positions['sf_1'] = [null, null];
        $positions['sf_2'] = [null, null];
        $positions['final'] = [null, null];
    } elseif ($total === 16) {
        $positions['qf_1'] = [$ids[0], $ids[15]];
        $positions['qf_2'] = [$ids[7], $ids[8]];
        $positions['qf_3'] = [$ids[3], $ids[12]];
        $positions['qf_4'] = [$ids[4], $ids[11]];
        $positions['qf_5'] = [$ids[1], $ids[14]];
        $positions['qf_6'] = [$ids[6], $ids[9]];
        $positions['qf_7'] = [$ids[2], $ids[13]];
        $positions['qf_8'] = [$ids[5], $ids[10]];
        $positions['sf_1'] = [null, null];
        $positions['sf_2'] = [null, null];
        $positions['sf_3'] = [null, null];
        $positions['sf_4'] = [null, null];
        $positions['final'] = [null, null];
    }

    return $positions;
}

    private function generateMatches(Tournament $tournament)
    {
        $players = $tournament->players()->pluck('id', 'name')->toArray();
        $playerNames = array_keys($players);
        $playerIds = array_values($players);

        if (count($playerNames) % 2 !== 0) {
            $playerNames[] = 'BYE';
            $playerIds[] = null;
        }

        $numPlayers = count($playerNames);
        $rounds = $numPlayers - 1;
        $half = $numPlayers / 2;
        $tvCount = $tournament->consoles_count;

        for ($round = 0; $round < $rounds; $round++) {
            $roundMatches = [];
            for ($i = 0; $i < $half; $i++) {
                $p1Name = $playerNames[$i];
                $p2Name = $playerNames[$numPlayers - 1 - $i];
                $p1Id = $playerIds[$i];
                $p2Id = $playerIds[$numPlayers - 1 - $i];

                if ($p1Name !== 'BYE' && $p2Name !== 'BYE') {
                    $roundMatches[] = [
                        'p1' => $p1Id,
                        'p2' => $p2Id,
                    ];
                }
            }

            $tvIndex = 0;
            foreach ($roundMatches as $matchData) {
                GameMatch::create([
                    'tournament_id' => $tournament->id,
                    'round' => $round + 1,
                    'player1_id' => $matchData['p1'],
                    'player2_id' => $matchData['p2'],
                    'tv_number' => ($tvIndex % $tvCount) + 1,
                ]);
                $tvIndex++;
            }

            $lastName = array_pop($playerNames);
            $lastId = array_pop($playerIds);
            array_splice($playerNames, 1, 0, [$lastName]);
            array_splice($playerIds, 1, 0, [$lastId]);
        }
    }

}
