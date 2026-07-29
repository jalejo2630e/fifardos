<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\Player;
use App\Models\GameMatch;
use App\Models\GoalScorer;
use App\Mail\TournamentCreatedMail;
use App\Services\StandingsService;
use App\Services\TournamentFactoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class TournamentController extends Controller
{
    const COLORS = ['#F97316', '#FFD700', '#FF6B9D', '#10B981', '#8B5CF6', '#00D4FF', '#EF4444', '#84CC16'];

    const PHASE_ORDER = ['round_of_16', 'quarterfinals', 'semifinals', 'final', 'third_place'];

    const PHASE_LABELS = [
        'round_of_16' => 'Octavos de final',
        'quarterfinals' => 'Cuartos de final',
        'semifinals' => 'Semifinales',
        'final' => 'Final',
        'third_place' => 'Tercer puesto',
    ];

    /** Aborta con 403 si el torneo no pertenece al usuario autenticado. */
    private function ensureOwner(Tournament $tournament): void
    {
        abort_unless((int) $tournament->user_id === (int) auth()->id(), 403);
    }

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
                $arr['estimated_minutes'] = Tournament::estimateMinutes(
                    $tournament->players_count,
                    $tournament->consoles_count,
                    $tournament->minutes_per_match ?? 6,
                );
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
            'minutes_per_match' => 'nullable|integer|min:1|max:60',
            'players' => 'required|array|min:2',
            'players.*' => 'required|string|max:255|distinct',
            'reminder_at' => 'nullable|date|after:now',
            'reminder_email' => 'nullable|email',
            'notify_email' => 'boolean',
        ]);

        $reminderEmail = $validated['reminder_email'] ?? auth()->user()->email;

        $tournament = app(TournamentFactoryService::class)->make(
            auth()->id(),
            $validated['name'],
            $validated['consoles_count'],
            $validated['players'],
        );

        $tournament->update(['minutes_per_match' => $validated['minutes_per_match'] ?? 6]);

        if (!empty($validated['reminder_at'])) {
            $tournament->update([
                'reminder_at' => $validated['reminder_at'],
                'reminder_email' => $reminderEmail,
            ]);
        }

        // Email de confirmación inmediato (opcional)
        if (!empty($validated['notify_email']) && $reminderEmail) {
            try {
                Mail::to($reminderEmail)->send(new TournamentCreatedMail($tournament));
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar el email de torneo creado', ['error' => $e->getMessage()]);
            }
        }

        $extra = !empty($validated['reminder_at']) ? ' Te recordaremos por email.' : '';

        return redirect()->route('tournaments.show', $tournament)
            ->with('success', "Torneo «{$tournament->name}» creado con " . count($validated['players']) . " jugadores.{$extra}");
    }

    public function show(Tournament $tournament)
    {
        $this->ensureOwner($tournament);

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
        $groupAllPlayed = $tournament->matches->where('phase', 'group')->every(fn($m) => $m->status === 'finished');

        // Auto-generate knockout when groups finish
        if ($groupAllPlayed && !$tournament->matches->whereIn('phase', self::PHASE_ORDER)->count()) {
            $this->autoGenerateKnockout($tournament);
            $tournament->refresh();
            $tournament->load(['players', 'matches' => function ($q) {
                $q->with(['player1', 'player2'])->orderBy('round')->orderBy('id');
            }]);
            $standings = app(StandingsService::class)->calculate($tournament);
        }

        // Goalscorers stats
        $finishedMatchIds = $tournament->matches->where('status', 'finished')->pluck('id');
        $goalScorersData = [];
        if ($finishedMatchIds->isNotEmpty()) {
            $gsRaw = \DB::table('goal_scorers')
                ->whereIn('match_id', $finishedMatchIds)
                ->selectRaw('player_id, SUM(goals) as total_goals, COUNT(DISTINCT match_id) as matches_played')
                ->groupBy('player_id')
                ->orderBy('total_goals', 'desc')
                ->get();

            $playerIds = $gsRaw->pluck('player_id');
            $players = \App\Models\Player::whereIn('id', $playerIds)->pluck('name', 'id');

            foreach ($gsRaw as $gs) {
                $goalScorersData[] = [
                    'player_id' => $gs->player_id,
                    'player_name' => $players[$gs->player_id] ?? '—',
                    'goals' => (int) $gs->total_goals,
                    'matches' => (int) $gs->matches_played,
                    'average' => $gs->matches_played > 0 ? round($gs->total_goals / $gs->matches_played, 2) : 0,
                ];
            }
        }

        // Group knockout matches by phase
        $phases = [];
        foreach (self::PHASE_ORDER as $phase) {
            $matches = $tournament->matches->where('phase', $phase)->sortBy('bracket_position')->values();
            if ($matches->count()) {
                $phases[] = [
                    'key' => $phase,
                    'label' => self::PHASE_LABELS[$phase] ?? $phase,
                    'matches' => $matches,
                    'allPlayed' => $matches->every(fn($m) => $m->status === 'finished'),
                ];
            }
        }

        return Inertia::render('Tournaments/Show', [
            'tournament' => $tournament,
            'standings' => $standings,
            'allPlayed' => $allPlayed,
            'rounds' => $groupRounds,
            'groupAllPlayed' => $groupAllPlayed,
            'phases' => $phases,
            'goalScorers' => $goalScorersData,
            'estimatedMinutes' => Tournament::estimateMinutes(
                $tournament->players->count(),
                $tournament->consoles_count,
                $tournament->minutes_per_match ?? 6,
            ),
        ]);
    }

    public function generateKnockout(Request $request, Tournament $tournament)
    {
        $this->ensureOwner($tournament);

        $top = (int) $request->input('top', 8);
        if (!in_array($top, [2, 4, 8, 16])) {
            return redirect()->back()->with('error', 'El número de jugadores debe ser 2, 4, 8 o 16.');
        }

        $hasExisting = $tournament->matches()->whereIn('phase', self::PHASE_ORDER)->exists();
        if ($hasExisting) {
            return redirect()->back()->with('error', 'Ya hay eliminatorias generadas. Editá los resultados directamente.');
        }

        $this->buildKnockoutBracket($tournament, $top);

        return redirect()->back()->with('success', 'Eliminatorias generadas.');
    }

    public function updateScore(Request $request, Tournament $tournament, GameMatch $match)
    {
        $this->ensureOwner($tournament);
        abort_unless((int) $match->tournament_id === (int) $tournament->id, 404);

        $validated = $request->validate([
            'score1' => 'required|integer|min:0',
            'score2' => 'required|integer|min:0',
            'played_at' => 'nullable|date',
            'penalties1' => 'nullable|integer|min:0',
            'penalties2' => 'nullable|integer|min:0',
            'stats' => 'nullable|array',
            'stats.possession_a' => 'nullable|integer|min:0|max:100',
            'stats.possession_b' => 'nullable|integer|min:0|max:100',
            'stats.shots_a' => 'nullable|integer|min:0',
            'stats.shots_b' => 'nullable|integer|min:0',
            'stats.shots_on_target_a' => 'nullable|integer|min:0',
            'stats.shots_on_target_b' => 'nullable|integer|min:0',
            'stats.cards_a' => 'nullable|integer|min:0',
            'stats.cards_b' => 'nullable|integer|min:0',
            'goal_scorers' => 'nullable|array',
            'goal_scorers.*.player_id' => [
                'required', 'integer',
                \Illuminate\Validation\Rule::exists('players', 'id')->where('tournament_id', $tournament->id),
            ],
            'goal_scorers.*.goals' => 'required|integer|min:1',
            'goal_scorers.*.minutes' => 'nullable|array',
            'goal_scorers.*.minutes.*' => 'integer|min:1|max:120',
        ]);

        $data = [
            'score1' => $validated['score1'],
            'score2' => $validated['score2'],
            'status' => 'finished',
            'played_at' => $validated['played_at'] ?? now(),
        ];

        if (isset($validated['penalties1']) && isset($validated['penalties2'])) {
            $data['penalties1'] = $validated['penalties1'];
            $data['penalties2'] = $validated['penalties2'];
        }

        if (isset($validated['stats'])) {
            $data['stats'] = $validated['stats'];
        }

        $match->update($data);

        // Save goal scorers
        if (isset($validated['goal_scorers'])) {
            $match->goalScorers()->delete();
            foreach ($validated['goal_scorers'] as $gs) {
                $match->goalScorers()->create([
                    'player_id' => $gs['player_id'],
                    'goals' => $gs['goals'],
                    'minutes' => $gs['minutes'] ?? null,
                ]);
            }
        }

        // Recalculate by phase
        if ($match->phase === 'group') {
            $this->updateTournamentProgress($tournament);
        } elseif (in_array($match->phase, ['round_of_16', 'quarterfinals', 'semifinals'])) {
            $this->autoAdvancePhase($tournament, $match->phase);
            $this->updateTournamentProgress($tournament);
        } else {
            $this->updateTournamentProgress($tournament);
        }

        return redirect()->route('tournaments.show', $tournament)
            ->with('success', 'Marcador guardado.');
    }

    public function editScore(Request $request, Tournament $tournament, GameMatch $match)
    {
        $this->ensureOwner($tournament);
        abort_unless((int) $match->tournament_id === (int) $tournament->id, 404);

        $match->update([
            'score1' => null,
            'score2' => null,
            'status' => 'pending',
            'penalties1' => null,
            'penalties2' => null,
        ]);

        $match->goalScorers()->delete();

        // Clear subsequent phase matches that depended on this result
        if (in_array($match->phase, ['round_of_16', 'quarterfinals', 'semifinals'])) {
            $this->clearSubsequentPhases($tournament, $match->phase);
        }

        return redirect()->back()->with('success', 'Marcador reabierto para editar.');
    }

    public function destroy(Tournament $tournament)
    {
        $this->ensureOwner($tournament);

        $name = $tournament->name;
        $tournament->delete();
        return redirect()->route('dashboard')
            ->with('success', "Torneo «{$name}» eliminado.");
    }

    public function replacePlayer(Request $request, Tournament $tournament, Player $player)
    {
        $this->ensureOwner($tournament);
        abort_unless((int) $player->tournament_id === (int) $tournament->id, 404);

        $validated = $request->validate([
            'new_name' => 'required|string|max:255',
        ]);

        $newPlayer = Player::create([
            'tournament_id' => $tournament->id,
            'name' => $validated['new_name'],
        ]);

        // Update all pending matches referencing the old player
        GameMatch::where('tournament_id', $tournament->id)
            ->where('status', 'pending')
            ->where('player1_id', $player->id)
            ->update(['player1_id' => $newPlayer->id]);

        GameMatch::where('tournament_id', $tournament->id)
            ->where('status', 'pending')
            ->where('player2_id', $player->id)
            ->update(['player2_id' => $newPlayer->id]);

        // Transfer goal scorer records to the new player
        GoalScorer::where('player_id', $player->id)->update(['player_id' => $newPlayer->id]);

        // Delete the old player
        $player->delete();

        return redirect()->route('tournaments.show', $tournament)
            ->with('success', "Jugador reemplazado por {$newPlayer->name}.");
    }

    private function autoGenerateKnockout(Tournament $tournament)
    {
        $total = $tournament->players->count();
        $top = match (true) {
            $total <= 4 => 4,
            $total <= 8 => 8,
            default => 16,
        };
        $top = min($top, $total);
        // Round down to nearest power of 2
        $top = (int) pow(2, floor(log($top, 2)));
        if ($top < 2) $top = 2;

        $this->buildKnockoutBracket($tournament, $top);
    }

    private function buildKnockoutBracket(Tournament $tournament, int $top)
    {
        $standings = app(StandingsService::class)->calculate($tournament);
        $qualified = array_slice($standings, 0, $top);

        if (count($qualified) < 2) return;

        $positions = $this->seedBracket($qualified);
        $maxRound = $tournament->matches()->where('phase', 'group')->max('round') ?? 0;

        foreach ($positions as $pos => $players) {
            $phase = $this->bracketPosToPhase($pos);
            $roundOffset = match ($phase) {
                'round_of_16' => 1,
                'quarterfinals' => 2,
                'semifinals' => 3,
                'final' => 4,
                'third_place' => 4,
                default => 1,
            };

            GameMatch::create([
                'tournament_id' => $tournament->id,
                'round' => $maxRound + $roundOffset,
                'player1_id' => $players[0],
                'player2_id' => $players[1],
                'phase' => $phase,
                'bracket_position' => $pos,
                'status' => 'pending',
                'tv_number' => 1,
            ]);
        }

        Log::info('Eliminatorias generadas', [
            'tournament_id' => $tournament->id,
            'top' => $top,
        ]);
    }

    private function bracketPosToPhase(string $pos): string
    {
        if (str_starts_with($pos, 'r16')) return 'round_of_16';
        if (str_starts_with($pos, 'qf')) return 'quarterfinals';
        if (str_starts_with($pos, 'sf')) return 'semifinals';
        if ($pos === 'final') return 'final';
        if ($pos === 'third_place') return 'third_place';
        return 'quarterfinals';
    }

    private function autoAdvancePhase(Tournament $tournament, string $completedPhase)
    {
        $phaseMatches = $tournament->matches()->where('phase', $completedPhase)->get();
        $allFinished = $phaseMatches->every(fn($m) => $m->status === 'finished');

        if (!$allFinished) return;

        $nextPhase = match ($completedPhase) {
            'round_of_16' => 'quarterfinals',
            'quarterfinals' => 'semifinals',
            'semifinals' => 'final',
            default => null,
        };

        if (!$nextPhase) return;

        $existing = $tournament->matches()->where('phase', $nextPhase)->exists();
        if ($existing) {
            // Update existing next-phase matches with correct winners
            $this->updateNextPhaseWinners($tournament, $completedPhase, $nextPhase);
            return;
        }

        // Build winners mapping from bracket positions
        $winners = [];
        foreach ($phaseMatches as $m) {
            $winners[$m->bracket_position] = $this->getWinner($m);
        }

        $maxRound = $tournament->matches()->where('phase', 'group')->max('round') ?? 0;
        $roundOffset = match ($nextPhase) {
            'quarterfinals' => 2,
            'semifinals' => 3,
            'final' => 4,
            default => 4,
        };

        // Create next phase matches
        $nextPositions = $this->getNextPositions($completedPhase, $winners);
        foreach ($nextPositions as $pos => $playerIds) {
            GameMatch::create([
                'tournament_id' => $tournament->id,
                'round' => $maxRound + $roundOffset,
                'player1_id' => $playerIds[0],
                'player2_id' => $playerIds[1],
                'phase' => $nextPhase,
                'bracket_position' => $pos,
                'status' => 'pending',
                'tv_number' => 1,
            ]);
        }

        // If completing semifinals, also generate third place match
        if ($completedPhase === 'semifinals') {
            $this->generateThirdPlace($tournament, $phaseMatches, $maxRound + $roundOffset);
        }

        Log::info('Avance automático de fase', [
            'tournament_id' => $tournament->id,
            'from' => $completedPhase,
            'to' => $nextPhase,
        ]);
    }

    private function updateNextPhaseWinners(Tournament $tournament, string $completedPhase, string $nextPhase)
    {
        $phaseMatches = $tournament->matches()->where('phase', $completedPhase)->get();
        $winners = [];
        foreach ($phaseMatches as $m) {
            $winners[$m->bracket_position] = $this->getWinner($m);
        }

        $nextMatches = $tournament->matches()->where('phase', $nextPhase)->get();
        foreach ($nextMatches as $nm) {
            $parent1 = $this->getParentPosition($nm->bracket_position, 1);
            $parent2 = $this->getParentPosition($nm->bracket_position, 2);
            $p1 = isset($winners[$parent1]) ? $winners[$parent1] : $nm->player1_id;
            $p2 = isset($winners[$parent2]) ? $winners[$parent2] : $nm->player2_id;
            if ($nm->status === 'pending') {
                $nm->update(['player1_id' => $p1, 'player2_id' => $p2]);
            }
        }
    }

    private function getParentPosition(string $pos, int $index): string
    {
        // sf_1 gets winners from qf_1 and qf_2
        // sf_2 gets winners from qf_3 and qf_4
        // final gets winners from sf_1 and sf_2
        if (str_starts_with($pos, 'sf')) {
            $num = (int) substr($pos, 3);
            $qfNum = ($num - 1) * 2 + $index;
            return "qf_{$qfNum}";
        }
        if ($pos === 'final') {
            return $index === 1 ? 'sf_1' : 'sf_2';
        }
        return $pos;
    }

    private function getNextPositions(string $completedPhase, array $winners): array
    {
        $positions = [];
        if ($completedPhase === 'round_of_16') {
            // r16_1+r16_2 -> qf_1, r16_3+r16_4 -> qf_2, etc.
            $qfPairs = [[1, 2], [3, 4], [5, 6], [7, 8]];
            foreach ($qfPairs as $i => $pair) {
                $p1 = $winners["r16_{$pair[0]}"] ?? null;
                $p2 = $winners["r16_{$pair[1]}"] ?? null;
                $positions["qf_" . ($i + 1)] = [$p1, $p2];
            }
        } elseif ($completedPhase === 'quarterfinals') {
            $positions['sf_1'] = [$winners['qf_1'] ?? null, $winners['qf_2'] ?? null];
            $positions['sf_2'] = [$winners['qf_3'] ?? null, $winners['qf_4'] ?? null];
        } elseif ($completedPhase === 'semifinals') {
            $positions['final'] = [$winners['sf_1'] ?? null, $winners['sf_2'] ?? null];
        }
        return $positions;
    }

    private function generateThirdPlace(Tournament $tournament, $semifinals, int $round)
    {
        $losers = [];
        foreach ($semifinals as $m) {
            $losers[] = $this->getLoser($m);
        }

        GameMatch::create([
            'tournament_id' => $tournament->id,
            'round' => $round,
            'player1_id' => $losers[0] ?? null,
            'player2_id' => $losers[1] ?? null,
            'phase' => 'third_place',
            'bracket_position' => 'third_place',
            'status' => 'pending',
            'tv_number' => 1,
        ]);
    }

    private function clearSubsequentPhases(Tournament $tournament, string $phase)
    {
        $phases = self::PHASE_ORDER;
        $idx = array_search($phase, $phases);
        if ($idx === false) return;

        $subsequent = array_slice($phases, $idx + 1);
        if (empty($subsequent)) return;

        $tournament->matches()->whereIn('phase', $subsequent)->delete();
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
            $positions['r16_1'] = [$ids[0], $ids[15]];
            $positions['r16_2'] = [$ids[7], $ids[8]];
            $positions['r16_3'] = [$ids[3], $ids[12]];
            $positions['r16_4'] = [$ids[4], $ids[11]];
            $positions['r16_5'] = [$ids[1], $ids[14]];
            $positions['r16_6'] = [$ids[6], $ids[9]];
            $positions['r16_7'] = [$ids[2], $ids[13]];
            $positions['r16_8'] = [$ids[5], $ids[10]];
            $positions['qf_1'] = [null, null];
            $positions['qf_2'] = [null, null];
            $positions['qf_3'] = [null, null];
            $positions['qf_4'] = [null, null];
            $positions['sf_1'] = [null, null];
            $positions['sf_2'] = [null, null];
            $positions['final'] = [null, null];
        }

        return $positions;
    }

    private function updateTournamentProgress(Tournament $tournament)
    {
        $allPlayed = $tournament->matches()->count() > 0
            && $tournament->matches()->where('status', '!=', 'finished')->count() === 0;

        if ($allPlayed && $tournament->status !== 'completed') {
            $tournament->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);
        } elseif (!$allPlayed && $tournament->status !== 'in_progress') {
            $tournament->update(['status' => 'in_progress']);
        }
    }

    private function getWinner(GameMatch $match): ?int
    {
        if ($match->status !== 'finished') return null;
        if ($match->score1 === null || $match->score2 === null) return null;

        if ($match->score1 > $match->score2) return $match->player1_id;
        if ($match->score2 > $match->score1) return $match->player2_id;

        // Scores tied — check penalties
        if ($match->penalties1 !== null && $match->penalties2 !== null) {
            if ($match->penalties1 > $match->penalties2) return $match->player1_id;
            if ($match->penalties2 > $match->penalties1) return $match->player2_id;
        }

        return $match->player1_id;
    }

    private function getLoser(GameMatch $match): ?int
    {
        if ($match->status !== 'finished') return null;
        $winner = $this->getWinner($match);
        if ($winner === null) return null;
        return $winner === $match->player1_id ? $match->player2_id : $match->player1_id;
    }

}
