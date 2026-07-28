<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\Player;
use App\Models\GameMatch;
use App\Services\StandingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AgentApiController extends Controller
{
    /**
     * GET /api/agent/tournaments
     * Lista de torneos activos con estado, cantidad de jugadores, partidos jugados/totales.
     */
    public function tournaments()
    {
        $tournaments = Tournament::withCount([
            'players',
            'matches',
            'matches as matches_played' => fn($q) => $q->where('status', 'finished'),
        ])->orderBy('created_at', 'desc')->get()->map(function ($t) {
            $leader = null;
            if ($t->matches_played > 0) {
                $standings = app(StandingsService::class)->calculate($t);
                $leader = [
                    'player_id' => $standings[0]['player_id'],
                    'player_name' => $standings[0]['player_name'],
                    'pts' => $standings[0]['pts'],
                ];
            }

            return [
                'id' => $t->id,
                'name' => $t->name,
                'status' => $t->status,
                'color' => $t->color,
                'consoles_count' => $t->consoles_count,
                'max_players' => $t->max_players,
                'players_count' => $t->players_count,
                'total_matches' => $t->matches_count,
                'played_matches' => $t->matches_played,
                'progress_percent' => $t->matches_count > 0
                    ? round($t->matches_played / $t->matches_count * 100)
                    : 0,
                'leader' => $leader,
                'created_at' => $t->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $tournaments,
            'count' => $tournaments->count(),
        ]);
    }

    /**
     * GET /api/agent/tournaments/{id}/standings
     * Tabla de posiciones completa del torneo.
     */
    public function standings($id)
    {
        $tournament = Tournament::with(['players', 'matches' => fn($q) => $q->with(['player1', 'player2'])])
            ->findOrFail($id);

        $rows = app(StandingsService::class)->calculate($tournament);
        $allPlayed = $tournament->matches->every(fn($m) => $m->status === 'finished');

        $standings = array_map(function ($row, $index) use ($allPlayed) {
            return [
                'position' => $index + 1,
                'player_name' => $row['player_name'],
                'points' => $row['pts'],
                'played' => $row['pj'],
                'won' => $row['pg'],
                'drawn' => $row['pe'],
                'lost' => $row['pp'],
                'goals_for' => $row['gf'],
                'goals_against' => $row['gc'],
                'goal_difference' => $row['dg'],
                'is_champion' => $index === 0 && $allPlayed,
            ];
        }, $rows, array_keys($rows));

        return response()->json([
            'tournament' => $tournament->name,
            'status' => $tournament->status,
            'all_matches_played' => $allPlayed,
            'standings' => $standings,
        ]);
    }

    /**
     * GET /api/agent/tournaments/{id}/top-scorer
     * Goleador actual del torneo (jugador con más goles acumulados).
     *
     * NOTA: Los goles se calculan sumando score1 (cuando el jugador es player1)
     *       y score2 (cuando es player2). No hay tabla individual de goleadores aún.
     */
    public function topScorer($id)
    {
        $tournament = Tournament::with(['players', 'matches' => fn($q) => $q->where('status', 'finished')])
            ->findOrFail($id);

        $goalCounts = [];
        foreach ($tournament->players as $player) {
            $goalCounts[$player->id] = [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'total_goals' => 0,
                'matches_played' => 0,
            ];
        }

        foreach ($tournament->matches as $match) {
            if (isset($goalCounts[$match->player1_id])) {
                $goalCounts[$match->player1_id]['total_goals'] += $match->score1 ?? 0;
                $goalCounts[$match->player1_id]['matches_played']++;
            }
            if (isset($goalCounts[$match->player2_id])) {
                $goalCounts[$match->player2_id]['total_goals'] += $match->score2 ?? 0;
                $goalCounts[$match->player2_id]['matches_played']++;
            }
        }

        usort($goalCounts, fn($a, $b) => $b['total_goals'] - $a['total_goals']);

        $top = $goalCounts[0] ?? null;

        if ($top && $top['matches_played'] > 0) {
            $top['goals_per_match'] = round($top['total_goals'] / $top['matches_played'], 2);
        } else {
            $top['goals_per_match'] = 0;
        }

        return response()->json([
            'success' => true,
            'tournament_id' => (int) $id,
            'tournament_name' => $tournament->name,
            'data' => $top,
        ]);
    }

    /**
     * GET /api/agent/tournaments/{id}/matches?status=pending|finished
     * Partidos del torneo, filtrable por estado (status query param opcional).
     */
    public function matches(Request $request, $id)
    {
        $tournament = Tournament::findOrFail($id);

        $query = GameMatch::with(['player1', 'player2'])
            ->where('tournament_id', $id)
            ->orderBy('round')
            ->orderBy('id');

        if ($request->filled('status')) {
            $status = $request->status;
            if (in_array($status, ['pending', 'finished'])) {
                $query->where('status', $status);
            }
        }

        $matches = $query->get()->map(function ($m) {
            return [
                'id' => $m->id,
                'round' => $m->round,
                'tv_number' => $m->tv_number,
                'status' => $m->status,
                'player1' => $m->player1 ? [
                    'id' => $m->player1->id,
                    'name' => $m->player1->name,
                ] : null,
                'player2' => $m->player2 ? [
                    'id' => $m->player2->id,
                    'name' => $m->player2->name,
                ] : null,
                'score1' => $m->score1,
                'score2' => $m->score2,
                'played_at' => $m->played_at,
                'winner_id' => $m->status === 'finished'
                    ? ($m->score1 > $m->score2 ? $m->player1_id : ($m->score2 > $m->score1 ? $m->player2_id : null))
                    : null,
                'is_draw' => $m->status === 'finished' && $m->score1 === $m->score2,
            ];
        });

        return response()->json([
            'success' => true,
            'tournament_id' => (int) $id,
            'tournament_name' => $tournament->name,
            'filter_status' => $request->status ?? 'all',
            'count' => $matches->count(),
            'data' => $matches,
        ]);
    }

    /**
     * GET /api/agent/players/{id}
     * Datos y estadísticas de un jugador específico.
     */
    public function player($id)
    {
        $player = Player::with('tournament')->findOrFail($id);

        $matchesAsP1 = GameMatch::where('player1_id', $id)->where('status', 'finished')->get();
        $matchesAsP2 = GameMatch::where('player2_id', $id)->where('status', 'finished')->get();

        $played = 0;
        $wins = 0;
        $draws = 0;
        $losses = 0;
        $goalsFor = 0;
        $goalsAgainst = 0;
        $pts = 0;

        foreach ($matchesAsP1 as $m) {
            $played++;
            $goalsFor += $m->score1;
            $goalsAgainst += $m->score2;
            if ($m->score1 > $m->score2) { $wins++; $pts += 3; }
            elseif ($m->score1 === $m->score2) { $draws++; $pts += 1; }
            else { $losses++; }
        }

        foreach ($matchesAsP2 as $m) {
            $played++;
            $goalsFor += $m->score2;
            $goalsAgainst += $m->score1;
            if ($m->score2 > $m->score1) { $wins++; $pts += 3; }
            elseif ($m->score2 === $m->score1) { $draws++; $pts += 1; }
            else { $losses++; }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $player->id,
                'name' => $player->name,
                'apellido' => $player->apellido,
                'username' => $player->username,
                'psn_id' => $player->psn_id,
                'email' => $player->email,
                'preferred_team' => $player->preferred_team,
                'tournament' => $player->tournament ? [
                    'id' => $player->tournament->id,
                    'name' => $player->tournament->name,
                    'status' => $player->tournament->status,
                ] : null,
                'stats' => [
                    'matches_played' => $played,
                    'wins' => $wins,
                    'draws' => $draws,
                    'losses' => $losses,
                    'goals_for' => $goalsFor,
                    'goals_against' => $goalsAgainst,
                    'goal_difference' => $goalsFor - $goalsAgainst,
                    'points' => $pts,
                ],
            ],
        ]);
    }

    /**
     * GET /api/agent/schema
     * Descripción estructurada de todos los endpoints disponibles.
     * Cada entrada incluye method, path, description (para que un LLM decida
     * cuándo llamarlo), parámetros y un example_response con los campos reales.
     */
    public function schema()
    {
        return response()->json([
            'api_name' => 'FIFARDOS Agent API',
            'version' => '1.0',
            'base_url' => url('/api/agent'),
            'authentication' => 'Bearer token via Laravel Sanctum. Incluir header: Authorization: Bearer {token}',
            'endpoints' => self::getSchemaEndpoints(),
        ]);
    }

    public static function getSchemaEndpoints(): array
    {
        return [
                [
                    'method' => 'GET',
                    'path' => '/api/agent/schema',
                    'description' => 'Devuelve este documento completo. Es el punto de entrada para que un LLM entienda qué herramientas tiene disponibles, sus parámetros y el formato exacto de respuesta de cada una.',
                    'parameters' => [],
                    'example_response' => [
                        'api_name' => 'FIFARDOS Agent API',
                        'version' => '1.0',
                        'base_url' => 'https://tudominio.com/api/agent',
                        'authentication' => 'Bearer token via Laravel Sanctum...',
                        'endpoints' => [[
                            'method' => 'GET',
                            'path' => '/api/agent/schema',
                            'description' => 'string',
                            'parameters' => [],
                            'example_response' => 'object',
                        ]],
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/agent/tournaments',
                    'description' => 'Lista todos los torneos creados. Incluye nombre, estado (setup/in_progress/finished), número de jugadores, progreso de partidos (played_matches/total_matches + porcentaje), y quién va líder con sus puntos si ya hay partidos jugados. Úsalo para responder preguntas como "¿cuántos torneos hay?", "¿cuál está en curso?", "¿quién va ganando?" o "¿qué torneos están disponibles?".',
                    'parameters' => [],
                    'example_response' => [
                        'success' => true,
                        'data' => [[
                            'id' => 'int',
                            'name' => 'string',
                            'status' => 'string (setup|in_progress|finished)',
                            'color' => 'string (hex)',
                            'consoles_count' => 'int',
                            'max_players' => 'int|null',
                            'players_count' => 'int',
                            'total_matches' => 'int',
                            'played_matches' => 'int',
                            'progress_percent' => 'int',
                            'leader' => [
                                'player_id' => 'int',
                                'player_name' => 'string',
                                'pts' => 'int',
                            ],
                            'created_at' => 'string (datetime)',
                        ]],
                        'count' => 'int',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/agent/tournaments/{id}/standings',
                    'description' => 'Devuelve la tabla de posiciones del torneo. Cada fila tiene posición, nombre del jugador, puntos, partidos jugados/ganados/empatados/perdidos, goles a favor/en contra/diferencia, y is_champion (true solo si el torneo terminó y es el primero). Úsalo tanto para "¿quién va ganando?" como para "¿ya hay campeón?" o "¿cómo va la tabla?"',
                    'parameters' => [
                        ['name' => 'id', 'type' => 'integer', 'description' => 'ID del torneo (obligatorio, va en la URL)', 'required' => true, 'in' => 'path'],
                    ],
                    'example_response' => [
                        'tournament' => 'string',
                        'status' => 'string',
                        'all_matches_played' => 'boolean',
                        'standings' => [[
                            'position' => 'int',
                            'player_name' => 'string',
                            'points' => 'int',
                            'played' => 'int',
                            'won' => 'int',
                            'drawn' => 'int',
                            'lost' => 'int',
                            'goals_for' => 'int',
                            'goals_against' => 'int',
                            'goal_difference' => 'int',
                            'is_champion' => 'boolean',
                        ]],
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/agent/tournaments/{id}/top-scorer',
                    'description' => 'Devuelve el jugador con más goles acumulados en el torneo. Calcula total_goals sumando los goles de cada partido (score1 si es player1, score2 si es player2). Incluye goals_per_match. NOTA: No hay tabla de goleadores individual — los goles son a nivel equipo en cada partido. Úsalo para "¿quién lleva más goles?", "¿cuál es el máximo goleador?"',
                    'parameters' => [
                        ['name' => 'id', 'type' => 'integer', 'description' => 'ID del torneo (obligatorio, va en la URL)', 'required' => true, 'in' => 'path'],
                    ],
                    'example_response' => [
                        'success' => true,
                        'tournament_id' => 'int',
                        'tournament_name' => 'string',
                        'data' => [
                            'player_id' => 'int',
                            'player_name' => 'string',
                            'total_goals' => 'int',
                            'matches_played' => 'int',
                            'goals_per_match' => 'float',
                        ],
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/agent/tournaments/{id}/matches',
                    'description' => 'Lista todos los partidos de un torneo ordenados por ronda. Cada partido incluye ronda, TV, estado (pending/finished), nombres de los jugadores, marcador (score1/score2), winner_id (int o null si es empate) y is_draw. Se puede filtrar por ?status=pending (solo pendientes) o ?status=finished (solo jugados). Úsalo para "¿qué partidos faltan?", "¿cómo van los partidos?", "¿quién ganó tal partido?"',
                    'parameters' => [
                        ['name' => 'id', 'type' => 'integer', 'description' => 'ID del torneo (obligatorio, va en la URL)', 'required' => true, 'in' => 'path'],
                        ['name' => 'status', 'type' => 'string', 'description' => 'Filtrar por estado: "pending" (pendientes) o "finished" (jugados). Opcional: si no se envía, devuelve todos.', 'required' => false, 'in' => 'query'],
                    ],
                    'example_response' => [
                        'success' => true,
                        'tournament_id' => 'int',
                        'tournament_name' => 'string',
                        'filter_status' => 'string',
                        'count' => 'int',
                        'data' => [[
                            'id' => 'int',
                            'round' => 'int',
                            'tv_number' => 'int',
                            'status' => 'string (pending|finished)',
                            'player1' => ['id' => 'int', 'name' => 'string'],
                            'player2' => ['id' => 'int', 'name' => 'string'],
                            'score1' => 'int|null',
                            'score2' => 'int|null',
                            'played_at' => 'string (datetime) | null',
                            'winner_id' => 'int|null',
                            'is_draw' => 'boolean',
                        ]],
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/agent/players/{id}',
                    'description' => 'Devuelve la información completa de un jugador: nombre, apellido, username, PSN ID, email, equipo favorito, torneo en el que participa, y estadísticas detalladas (partidos jugados, ganados, empatados, perdidos, goles a favor/en contra, diferencia, puntos). Úsalo para "dame los datos de X jugador", "¿cómo le está yendo a Y?" o para obtener estadísticas individuales.',
                    'parameters' => [
                        ['name' => 'id', 'type' => 'integer', 'description' => 'ID del jugador (obligatorio, va en la URL)', 'required' => true, 'in' => 'path'],
                    ],
                    'example_response' => [
                        'success' => true,
                        'data' => [
                            'id' => 'int',
                            'name' => 'string',
                            'apellido' => 'string|null',
                            'username' => 'string|null',
                            'psn_id' => 'string|null',
                            'email' => 'string|null',
                            'preferred_team' => 'string|null',
                            'tournament' => [
                                'id' => 'int',
                                'name' => 'string',
                                'status' => 'string',
                            ],
                            'stats' => [
                                'matches_played' => 'int',
                                'wins' => 'int',
                                'draws' => 'int',
                                'losses' => 'int',
                                'goals_for' => 'int',
                                'goals_against' => 'int',
                                'goal_difference' => 'int',
                                'points' => 'int',
                            ],
                        ],
                        ],
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/agent/search',
                        'description' => 'Búsqueda semántica sobre resúmenes narrativos de jugadores. Úsalo SOLO para preguntas narrativas o abiertas que requieran resumir el desempeño de un jugador en un período (ej. "¿cómo le fue a X este mes?", "¿cómo ha sido su temporada?"). Para datos exactos (puntos, goles, posición actual, resultados de partidos específicos) usa los endpoints estructurados en su lugar, no este.',
                        'parameters' => [
                            ['name' => 'query', 'type' => 'string', 'description' => 'Texto de la pregunta o búsqueda en lenguaje natural (obligatorio, min 1 carácter)', 'required' => true, 'in' => 'body'],
                        ],
                        'example_response' => [
                            'success' => true,
                            'query' => 'string',
                            'results' => [[
                                'player_name' => 'string',
                                'period' => 'string (ej. "julio 2026")',
                                'summary' => 'string',
                                'similarity_score' => 'float',
                            ]],
                        ],
                    ],
            ];
        }

    /**
     * POST /api/agent/search
     * Búsqueda semántica sobre resúmenes narrativos de jugadores.
     */
    public function search(Request $request)
    {
        $data = $request->validate(['query' => 'required|string|min:1']);
        $queryText = $data['query'];

        $response = Http::timeout(15)
            ->withHeader('x-goog-api-key', config('services.gemini.api_key'))
            ->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent', [
                'model' => 'models/gemini-embedding-001',
                'content' => ['parts' => [['text' => $queryText]]],
                'output_dimensionality' => 768,
            ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'error' => 'Error al generar embedding: ' . $response->body(),
            ], 500);
        }

        $queryEmbedding = $response->json('embedding.values');
        if (!is_array($queryEmbedding)) {
            return response()->json([
                'success' => false,
                'error' => 'Gemini no devolvió un array de embedding',
            ], 500);
        }

        $rows = DB::table('match_summaries')
            ->join('players', 'match_summaries.player_id', '=', 'players.id')
            ->whereNotNull('match_summaries.embedding')
            ->select(
                'match_summaries.player_id',
                'match_summaries.period_start',
                'match_summaries.period_end',
                'match_summaries.summary_text',
                'match_summaries.embedding',
                'players.name as player_name'
            )
            ->get();

        $scored = [];
        foreach ($rows as $row) {
            $stored = json_decode($row->embedding, true);
            if (!is_array($stored)) continue;

            $score = $this->cosineSimilarity($queryEmbedding, $stored);
            $scored[] = [
                'player_name' => $row->player_name,
                'period' => Carbon::parse($row->period_start)->locale('es')->isoFormat('MMMM YYYY'),
                'summary' => $row->summary_text,
                'similarity_score' => round($score, 2),
            ];
        }

        usort($scored, fn($a, $b) => $b['similarity_score'] <=> $a['similarity_score']);
        $results = array_slice($scored, 0, 3);

        return response()->json([
            'success' => true,
            'query' => $queryText,
            'results' => $results,
        ]);
    }

    /**
     * Calcula la similitud coseno entre dos vectores.
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($a); $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dot / ($normA * $normB);
    }
}
