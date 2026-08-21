<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\Player;
use App\Models\GameMatch;
use App\Models\GoalScorer;
use App\Mail\TournamentCreatedMail;
use App\Services\SportsCatalog;
use App\Services\StandingsService;
use App\Services\TournamentFactoryService;
use App\Services\TournamentRulesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
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
            ->withCount(['players', 'teams', 'matches', 'matches as matches_played' => fn($q) => $q->where('status', 'finished')])
            ->with(['players', 'teams', 'matches' => fn($q) => $q->where('status', 'finished')])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($tournament) {
                $arr = $tournament->toArray();
                unset($arr['players'], $arr['matches'], $arr['teams']);

                $competitorCount = $tournament->isTeamSport()
                    ? $tournament->teams_count
                    : $tournament->players_count;

                $arr['estimated_minutes'] = Tournament::estimateMinutes(
                    $competitorCount,
                    $tournament->consoles_count,
                    $tournament->minutes_per_match ?? SportsCatalog::minutes($tournament->sport),
                    $tournament->format ?? 'groups_knockout',
                    (bool) $tournament->home_and_away,
                );
                $sportKey = $tournament->sport ?? 'fifa';
                $arr['sport_name'] = SportsCatalog::name($sportKey);
                $arr['sport_icon'] = SportsCatalog::icon($sportKey);
                $arr['is_team'] = SportsCatalog::isTeam($sportKey);
                $arr['mode'] = $tournament->mode ?? 'virtual';
                $arr['competitor_label'] = $arr['is_team'] ? 'equipos' : 'jugadores';
                $arr['leader'] = null;
                if ($tournament->matches_played > 0) {
                    $standings = app(StandingsService::class)->calculate($tournament);
                    $arr['leader'] = [
                        'name' => $standings[0]['competitor_name'],
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
        return Inertia::render('Tournaments/Create', [
            'sports' => SportsCatalog::all(),
            'rules' => app(TournamentRulesService::class)->definitionsBySport(),
        ]);
    }

    public function store(Request $request)
    {
        $sport = $request->input('sport', 'fifa');
        if (!in_array($sport, SportsCatalog::keys(), true)) {
            $sport = 'fifa';
        }
        $isTeam = SportsCatalog::isTeam($sport);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sport' => 'nullable|string',
            'mode' => 'nullable|in:virtual,physical',
            'consoles_count' => 'required|integer|min:1|max:20',
            'minutes_per_match' => 'nullable|integer|min:1|max:180',
            'format' => 'nullable|in:groups_knockout,league',
            'home_and_away' => 'boolean',
            'rules' => 'nullable|array',
            'players' => $isTeam ? 'nullable|array' : 'required|array|min:2',
            'players.*' => 'required|string|max:255|distinct',
            'teams' => $isTeam ? 'required|array|min:2' : 'nullable|array',
            'teams.*.name' => 'required|string|max:255',
            'teams.*.players' => 'nullable|array',
            'teams.*.players.*' => 'required|string|max:255|distinct',
            'reminder_at' => 'nullable|date|after:now',
            'reminder_email' => 'nullable|email',
            'notify_email' => 'boolean',
        ]);

        // Reglas parametrizables: validar contra las definiciones del deporte
        $rulesErrors = app(TournamentRulesService::class)->validate(
            $validated['rules'] ?? [],
            $sport,
        );
        if (!empty($rulesErrors)) {
            return redirect()->back()
                ->withErrors(['rules' => $rulesErrors])
                ->withInput();
        }

        if ($isTeam) {
            $names = array_map(fn ($t) => $t['name'], $validated['teams']);
            if (count($names) !== count(array_unique($names))) {
                return redirect()->back()->withErrors(['teams' => 'Los nombres de equipos no pueden repetirse.'])->withInput();
            }
        }

        $reminderEmail = $validated['reminder_email'] ?? auth()->user()->email;

        if ($isTeam) {
            $tournament = app(TournamentFactoryService::class)->makeTeamTournament(
                auth()->id(),
                $validated['name'],
                $validated['consoles_count'],
                $sport,
                $validated['teams'],
                $validated['format'] ?? 'groups_knockout',
                (bool) ($validated['home_and_away'] ?? false),
            );
            $competitorLabel = count($validated['teams']) . ' equipos';
        } else {
            $tournament = app(TournamentFactoryService::class)->make(
                auth()->id(),
                $validated['name'],
                $validated['consoles_count'],
                $validated['players'],
                $validated['format'] ?? 'groups_knockout',
                (bool) ($validated['home_and_away'] ?? false),
                $sport,
            );
            $competitorLabel = count($validated['players']) . ' jugadores';
        }

        $tournament->update([
            'minutes_per_match' => $validated['minutes_per_match'] ?? SportsCatalog::minutes($sport),
            'mode' => $validated['mode'] ?? 'virtual',
        ]);

        // Reglas del torneo según el deporte
        if (!empty($validated['rules'])) {
            app(TournamentRulesService::class)->saveForTournament($tournament, $validated['rules']);
        }

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
            ->with('success', "Torneo «{$tournament->name}» creado con {$competitorLabel}.{$extra}");
    }

    public function show(Tournament $tournament)
    {
        $this->ensureOwner($tournament);

        $tournament->load([
            'players',
            'teams.players',
            'rules',
            'matches' => function ($q) {
                $q->with(['player1', 'player2', 'team1', 'team2'])->orderBy('round')->orderBy('id');
            },
        ]);

        $standings = app(StandingsService::class)->calculate($tournament);
        $allPlayed = $tournament->matches->every(fn($m) => $m->status === 'finished');

        if ($allPlayed && $tournament->status !== 'completed') {
            $tournament->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);
            $tournament = $tournament->fresh();
            $tournament->load([
                'players',
                'teams.players',
                'matches' => function ($q) {
                    $q->with(['player1', 'player2', 'team1', 'team2'])->orderBy('round')->orderBy('id');
                },
            ]);
            $standings = app(StandingsService::class)->calculate($tournament);
        }

        $groupRounds = $tournament->matches->where('phase', 'group')->groupBy('round')->values();
        $groupAllPlayed = $tournament->matches->where('phase', 'group')->every(fn($m) => $m->status === 'finished');

        // Auto-generate knockout when groups finish (solo en formato con eliminatorias)
        if ($tournament->format === 'groups_knockout'
            && $groupAllPlayed
            && !$tournament->matches->whereIn('phase', self::PHASE_ORDER)->count()) {
            $this->autoGenerateKnockout($tournament);
            $tournament->refresh();
            $tournament->load([
                'players',
                'teams.players',
                'matches' => function ($q) {
                    $q->with(['player1', 'player2', 'team1', 'team2'])->orderBy('round')->orderBy('id');
                },
            ]);
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

        $sportKey = $tournament->sport ?? 'fifa';

        // Reglas del torneo ya elegidas, combinadas con sus definiciones para mostrar
        $tournamentRules = $tournament->rulesMap();
        $rulesList = [];
        foreach (app(TournamentRulesService::class)->definitionsFor($sportKey) as $def) {
            if (!array_key_exists($def->key, $tournamentRules)) {
                continue;
            }
            if ($def->key === 'tiempo_partido_min') {
                continue;
            }
            $rulesList[] = [
                'key' => $def->key,
                'label' => $def->label,
                'label_en' => $def->label_en,
                'type' => $def->type,
                'value' => $tournamentRules[$def->key],
                'options' => $def->options,
                'group' => $def->group,
            ];
        }

        return Inertia::render('Tournaments/Show', [
            'tournament' => $tournament,
            'sport' => SportsCatalog::get($sportKey),
            'standings' => $standings,
            'allPlayed' => $allPlayed,
            'rounds' => $groupRounds,
            'groupAllPlayed' => $groupAllPlayed,
            'phases' => $phases,
            'goalScorers' => $goalScorersData,
            'tournamentRules' => $tournamentRules,
            'rulesList' => $rulesList,
            'estimatedMinutes' => Tournament::estimateMinutes(
                $tournament->isTeamSport() ? $tournament->teams->count() : $tournament->players->count(),
                $tournament->consoles_count,
                $tournament->minutes_per_match ?? SportsCatalog::minutes($sportKey),
                $tournament->format ?? 'groups_knockout',
                (bool) $tournament->home_and_away,
            ),
        ]);
    }

    public function generateKnockout(Request $request, Tournament $tournament)
    {
        $this->ensureOwner($tournament);

        $top = (int) $request->input('top', 8);
        if (!in_array($top, [2, 4, 8, 16])) {
            return redirect()->back()->with('error', 'El número de competidores debe ser 2, 4, 8 o 16.');
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

        $sport = $tournament->sport ?? 'fifa';
        $isSets = SportsCatalog::isSets($sport);
        $usesPenalties = SportsCatalog::usesPenalties($sport);

        $rules = [
            'played_at' => 'nullable|date',
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
                Rule::exists('players', 'id')->where('tournament_id', $tournament->id),
            ],
            'goal_scorers.*.goals' => 'required|integer|min:1',
            'goal_scorers.*.minutes' => 'nullable|array',
            'goal_scorers.*.minutes.*' => 'integer|min:1|max:180',
        ];

        if ($isSets) {
            $rules['sets'] = 'required|array|min:1';
            $rules['sets.*.a'] = 'required|integer|min:0';
            $rules['sets.*.b'] = 'required|integer|min:0';
        } else {
            $rules['score1'] = 'required|integer|min:0';
            $rules['score2'] = 'required|integer|min:0';
            if ($usesPenalties) {
                $rules['penalties1'] = 'nullable|integer|min:0';
                $rules['penalties2'] = 'nullable|integer|min:0';
            }
        }

        $validated = $request->validate($rules);

        $data = [
            'status' => 'finished',
            'played_at' => $validated['played_at'] ?? now(),
        ];

        if ($isSets) {
            $sets = array_values($validated['sets']);
            foreach ($sets as $set) {
                if ((int) $set['a'] === (int) $set['b']) {
                    return redirect()->back()->with('error', 'Un set no puede terminar empatado.');
                }
            }
            if (count($sets) > SportsCatalog::maxSets($sport)) {
                return redirect()->back()->with('error', 'Demasiados sets. Máximo ' . SportsCatalog::maxSets($sport) . '.');
            }
            $s1 = count(array_filter($sets, fn ($s) => (int) $s['a'] > (int) $s['b']));
            $s2 = count($sets) - $s1;
            if ($s1 === $s2) {
                return redirect()->back()->with('error', 'Debe haber un ganador en sets.');
            }
            $data['sets'] = $sets;
            $data['score1'] = $s1;
            $data['score2'] = $s2;
        } else {
            $data['score1'] = $validated['score1'];
            $data['score2'] = $validated['score2'];
            if ($usesPenalties && isset($validated['penalties1'], $validated['penalties2'])) {
                $data['penalties1'] = $validated['penalties1'];
                $data['penalties2'] = $validated['penalties2'];
            }
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
            'sets' => null,
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
        return redirect()->route('tournaments.index')
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
            'team_id' => $player->team_id,
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

    /** Columnas de competidor (equipo o jugador) según el deporte del torneo. */
    private function competitorColumns(Tournament $tournament, ?int $c1, ?int $c2): array
    {
        if ($tournament->isTeamSport()) {
            return ['team1_id' => $c1, 'team2_id' => $c2];
        }
        return ['player1_id' => $c1, 'player2_id' => $c2];
    }

    private function autoGenerateKnockout(Tournament $tournament)
    {
        $total = $tournament->isTeamSport() ? $tournament->teams->count() : $tournament->players->count();
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

        foreach ($positions as $pos => $competitors) {
            $phase = $this->bracketPosToPhase($pos);
            $roundOffset = match ($phase) {
                'round_of_16' => 1,
                'quarterfinals' => 2,
                'semifinals' => 3,
                'final' => 4,
                'third_place' => 4,
                default => 1,
            };

            GameMatch::create(array_merge([
                'tournament_id' => $tournament->id,
                'round' => $maxRound + $roundOffset,
                'phase' => $phase,
                'bracket_position' => $pos,
                'status' => 'pending',
                'tv_number' => 1,
            ], $this->competitorColumns($tournament, $competitors[0], $competitors[1])));
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
        foreach ($nextPositions as $pos => $competitorIds) {
            GameMatch::create(array_merge([
                'tournament_id' => $tournament->id,
                'round' => $maxRound + $roundOffset,
                'phase' => $nextPhase,
                'bracket_position' => $pos,
                'status' => 'pending',
                'tv_number' => 1,
            ], $this->competitorColumns($tournament, $competitorIds[0], $competitorIds[1])));
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
            $p1 = isset($winners[$parent1]) ? $winners[$parent1] : ($nm->team1_id ?? $nm->player1_id);
            $p2 = isset($winners[$parent2]) ? $winners[$parent2] : ($nm->team2_id ?? $nm->player2_id);
            if ($nm->status === 'pending') {
                $nm->update($this->competitorColumns($tournament, $p1, $p2));
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

        GameMatch::create(array_merge([
            'tournament_id' => $tournament->id,
            'round' => $round,
            'phase' => 'third_place',
            'bracket_position' => 'third_place',
            'status' => 'pending',
            'tv_number' => 1,
        ], $this->competitorColumns($tournament, $losers[0] ?? null, $losers[1] ?? null)));
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
        $ids = array_map(fn($p) => $p['competitor_id'], $qualified);

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
        $outcome = $match->outcome();
        if ($outcome === 1) return $match->competitor1Id();
        if ($outcome === 2) return $match->competitor2Id();

        // Empate sin desempate definido: gana el local (compat con comportamiento previo)
        return $match->competitor1Id();
    }

    private function getLoser(GameMatch $match): ?int
    {
        if ($match->status !== 'finished') return null;
        $winner = $this->getWinner($match);
        if ($winner === null) return null;
        return $winner === $match->competitor1Id() ? $match->competitor2Id() : $match->competitor1Id();
    }
}
