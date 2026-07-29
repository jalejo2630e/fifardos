<?php

namespace App\Http\Controllers;

use App\Models\ChatConfig;
use App\Models\Tournament;
use App\Models\Player;
use App\Services\StandingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiChatController extends Controller
{
    private array $tools;

    public function __construct()
    {
        $this->tools = [
            [
                'name' => 'getTournaments',
                'description' => 'Obtiene todos los torneos con su estado, cantidad de jugadores y fechas.',
                'parameters' => ['type' => 'object', 'properties' => (object)[], 'required' => []],
            ],
            [
                'name' => 'getStandings',
                'description' => 'Obtiene la tabla de posiciones de un torneo específico.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'tournament_id' => ['type' => 'integer', 'description' => 'ID del torneo'],
                    ],
                    'required' => ['tournament_id'],
                ],
            ],
            [
                'name' => 'getPrizes',
                'description' => 'Obtiene los premios de un torneo específico.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'tournament_id' => ['type' => 'integer', 'description' => 'ID del torneo'],
                    ],
                    'required' => ['tournament_id'],
                ],
            ],
            [
                'name' => 'getTopScorers',
                'description' => 'Obtiene los goleadores (máximos anotadores) de un torneo.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'tournament_id' => ['type' => 'integer', 'description' => 'ID del torneo (opcional, usa el más reciente si no se especifica)'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'searchPlayers',
                'description' => 'Busca jugadores por nombre en todos los torneos.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'Nombre del jugador a buscar'],
                    ],
                    'required' => ['name'],
                ],
            ],
            [
                'name' => 'getRecentMatches',
                'description' => 'Obtiene los últimos partidos registrados de un torneo.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'tournament_id' => ['type' => 'integer', 'description' => 'ID del torneo'],
                        'limit' => ['type' => 'integer', 'description' => 'Cantidad de partidos a devolver (default 10)'],
                    ],
                    'required' => ['tournament_id'],
                ],
            ],
        ];
    }

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array|max:20',
            'history.*.role' => 'required|in:user,model',
            'history.*.parts' => 'required|array|max:4',
            'history.*.parts.*.text' => 'required|string|max:2000',
        ]);

        $message = $validated['message'];
        // Saneamos el historial: sólo role + parts[].text (descarta cualquier
        // functionCall/functionResponse inyectado por el cliente → anti prompt-injection).
        $history = array_map(fn($h) => [
            'role' => $h['role'],
            'parts' => array_map(fn($p) => ['text' => (string) $p['text']], $h['parts']),
        ], $validated['history'] ?? []);

        // Try Gemini first (quick timeout)
        $result = $this->tryGemini($message, $history);
        if ($result !== null) {
            $history[] = ['role' => 'user', 'parts' => [['text' => $message]]];
            $history[] = ['role' => 'model', 'parts' => [['text' => $result]]];
            return response()->json(['reply' => $result, 'history' => $history]);
        }

        // Fallback: answer from local DB
        $reply = $this->localAnswer($message, $history);
        $history[] = ['role' => 'user', 'parts' => [['text' => $message]]];
        $history[] = ['role' => 'model', 'parts' => [['text' => $reply]]];
        return response()->json(['reply' => $reply, 'history' => $history]);
    }

    private function tryGemini(string $message, array $history = []): ?string
    {
        try {
            $config = ChatConfig::first();
            if (!$config || !$config->is_active) return null;

            $prompt = $config->system_prompt ?? 'Eres un asistente de la FIFARDOS ELITE LEAGUE. Respondes en español de forma breve y amigable.';
            if ($config->forbidden_topics) {
                $prompt .= "\n\nTEMAS PROHIBIDOS: No debes hablar sobre: {$config->forbidden_topics}.";
            }

            $contents = $history;
            $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

            $payload = [
                'system_instruction' => ['parts' => [['text' => $prompt]]],
                'contents' => $contents,
                'tools' => [['functionDeclarations' => $this->tools]],
                'generationConfig' => [
                    'maxOutputTokens' => $config->max_tokens ?? 800,
                    'temperature' => $config->temperature ?? 0.7,
                ],
            ];

            $response = Http::timeout(3)
                ->withHeaders(['x-goog-api-key' => config('services.gemini.api_key')])
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
                    $payload
                );

            $body = $response->json();

            if (isset($body['error'])) {
                Log::warning('Gemini API error, falling back to local', ['code' => $body['error']['code'] ?? 0]);
                return null;
            }

            $part = $body['candidates'][0]['content']['parts'][0] ?? null;
            if (!$part) return null;

            // Handle function call
            if (isset($part['functionCall'])) {
                $fn = $part['functionCall'];
                $fnResult = $this->executeFunction($fn['name'], $fn['args'] ?? []);

                // Send function result back to Gemini for final answer
                $payload['contents'][] = [
                    'role' => 'model',
                    'parts' => [['functionCall' => ['name' => $fn['name'], 'args' => $fn['args'] ?? []]]],
                ];
                $payload['contents'][] = [
                    'role' => 'user',
                    'parts' => [['functionResponse' => ['name' => $fn['name'], 'response' => ['result' => $fnResult]]]],
                ];

                $response2 = Http::timeout(15)
                    ->withHeaders(['x-goog-api-key' => config('services.gemini.api_key')])
                    ->post(
                        'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
                        $payload
                    );

                $body2 = $response2->json();
                if (isset($body2['error'])) return null;

                return $body2['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            return $part['text'] ?? null;
        } catch (\Exception $e) {
            Log::warning('Gemini exception, falling back to local', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function localAnswer(string $message, array $history = []): string
    {
        $msg = mb_strtolower(trim($message));

        // Keywords to action mapping
        if (str_contains($msg, 'torneo') && (str_contains($msg, 'hay') || str_contains($msg, 'cuantos') || str_contains($msg, 'lista') || str_contains($msg, 'todo'))) {
            return $this->replyTournaments();
        }

        if (str_contains($msg, 'goleador') || str_contains($msg, 'gol') || str_contains($msg, 'maximo') || str_contains($msg, 'anotador')) {
            return $this->replyTopScorers();
        }

        if (str_contains($msg, 'tabla') || str_contains($msg, 'posicion') || str_contains($msg, 'standings') || str_contains($msg, 'clasifica')) {
            return $this->replyStandings();
        }

        if (str_contains($msg, 'premio') || str_contains($msg, 'prize')) {
            return $this->replyPrizes();
        }

        if (str_contains($msg, 'jugadore') || str_contains($msg, 'participante') || (str_contains($msg, 'cuantos') && str_contains($msg, 'jugador'))) {
            return $this->replyPlayers();
        }

        if (str_contains($msg, 'registr') || str_contains($msg, 'inscrib') || str_contains($msg, 'como entro') || str_contains($msg, 'como puedo')) {
            return "Para registrarte en la FIFARDOS ELITE LEAGUE, ingresá a la página de Registro (botón REGISTRO en el menú) y completá tus datos. También podés inscribirte directamente en /inscribirse. El torneo es por invitación y cualquier jugador puede participar.";
        }

        if (str_contains($msg, 'regla') || str_contains($msg, 'formato') || str_contains($msg, 'como funciona')) {
            return "El torneo funciona en dos fases:\n1. Fase de grupos (round-robin): todos contra todos\n2. Eliminatorias directas (knockout): los mejores avanzan\n\nLos partidos tienen resultado numérico (goles) y se registran en el sistema por el administrador.";
        }

        if (str_contains($msg, 'partido') || str_contains($msg, 'resultado') || str_contains($msg, 'match')) {
            return $this->replyRecentMatches();
        }

        // Check if asking about a specific tournament by name
        $tournaments = Tournament::all();
        foreach ($tournaments as $t) {
            if (str_contains($msg, mb_strtolower($t->name))) {
                return $this->replyTournamentDetail($t);
            }
        }

        return "No entendí bien tu consulta. Podés preguntarme sobre:\n- Torneos disponibles\n- Tabla de posiciones\n- Goleadores\n- Premios\n- Cómo registrarte\n- Reglas del torneo\n- Resultados de partidos";
    }

    private function replyTournaments(): string
    {
        $tournaments = Tournament::withCount('players')->latest()->get();
        if ($tournaments->isEmpty()) return "No hay torneos registrados aún.";

        $lines = ["Hay {$tournaments->count()} torneo(s):"];
        foreach ($tournaments as $t) {
            $status = match ($t->status) { 'setup' => 'En configuración', 'in_progress' => 'En curso', 'completed' => 'Finalizado', default => $t->status };
            $lines[] = "• {$t->name} — {$status} — {$t->players_count} jugadores";
        }
        return implode("\n", $lines);
    }

    private function replyTopScorers(): string
    {
        $tournament = Tournament::with('matches')->latest()->first();
        if (!$tournament) return "No hay torneos registrados.";

        $standings = app(StandingsService::class)->calculate($tournament);
        $maxGf = max(array_column($standings, 'gf'));

        if ($maxGf <= 0) return "Todavía no hay goles registrados en {$tournament->name}.";

        $top = array_values(array_filter($standings, fn($s) => $s['gf'] === $maxGf));

        if (count($top) === 1) {
            return "El máximo goleador de {$tournament->name} es {$top[0]['player_name']} con {$top[0]['gf']} goles.";
        }

        $names = array_map(fn($s) => "{$s['player_name']} ({$s['gf']} goles)", $top);
        return "Los máximos goleadores de {$tournament->name} son: " . implode(', ', $names) . ".";
    }

    private function replyStandings(): string
    {
        $tournament = Tournament::with('matches')->latest()->first();
        if (!$tournament) return "No hay torneos registrados.";

        $standings = app(StandingsService::class)->calculate($tournament);
        if (empty($standings)) return "{$tournament->name} no tiene partidos jugados todavía.";

        $lines = ["Tabla de posiciones de {$tournament->name}:"];
        foreach ($standings as $i => $s) {
            $lines[] = ($i + 1) . ". {$s['player_name']} — {$s['pts']} pts (G:{$s['pg']} E:{$s['pe']} P:{$s['pp']} | GF:{$s['gf']} GC:{$s['gc']} DG:{$s['dg']})";
        }
        return implode("\n", $lines);
    }

    private function replyPrizes(): string
    {
        $tournament = Tournament::with('prizes')->latest()->first();
        if (!$tournament) return "No hay torneos registrados.";
        if ($tournament->prizes->isEmpty()) return "{$tournament->name} no tiene premios configurados aún.";

        $lines = ["Premios de {$tournament->name}:"];
        foreach ($tournament->prizes->sortBy('position') as $p) {
            $perks = $p->perks ? ' (' . implode(', ', $p->perks) . ')' : '';
            $lines[] = "• {$p->position}º {$p->label}: {$p->amount}{$perks}";
        }
        return implode("\n", $lines);
    }

    private function replyPlayers(): string
    {
        $tournament = Tournament::withCount('players')->latest()->first();
        if (!$tournament) return "No hay torneos registrados.";

        $players = $tournament->players()->pluck('name');
        if ($players->isEmpty()) return "{$tournament->name} no tiene jugadores registrados.";

        return "{$tournament->name} tiene {$tournament->players_count} jugadores:\n" . $players->map(fn($n, $i) => ($i + 1) . ". {$n}")->join("\n");
    }

    private function replyRecentMatches(): string
    {
        $tournament = Tournament::with('matches.player1', 'matches.player2')->latest()->first();
        if (!$tournament) return "No hay torneos registrados.";

        $matches = $tournament->matches()->where('status', 'finished')->latest('played_at')->limit(5)->get();
        if ($matches->isEmpty()) return "{$tournament->name} no tiene partidos jugados aún.";

        $lines = ["Últimos partidos de {$tournament->name}:"];
        foreach ($matches as $m) {
            $lines[] = "• {$m->player1?->name} {$m->score1} - {$m->score2} {$m->player2?->name}" . ($m->phase === 'knockout' ? ' (eliminatoria)' : '');
        }
        return implode("\n", $lines);
    }

    private function replyTournamentDetail(Tournament $tournament): string
    {
        $status = match ($tournament->status) { 'setup' => 'en configuración', 'in_progress' => 'en curso', 'completed' => 'finalizado', default => $tournament->status };
        $players = $tournament->players()->count();
        $matches = $tournament->matches()->count();
        $finished = $tournament->matches()->where('status', 'finished')->count();

        return "{$tournament->name} — {$status}\nJugadores: {$players}\nPartidos: {$finished} jugados de {$matches} totales" . ($tournament->finished_at ? "\nFinalizó el {$tournament->finished_at->format('d/m/Y')}" : '');
    }

    private function executeFunction(string $name, array $args): array
    {
        return match ($name) {
            'getTournaments' => $this->fnGetTournaments(),
            'getStandings' => $this->fnGetStandings($args['tournament_id'] ?? null),
            'getPrizes' => $this->fnGetPrizes($args['tournament_id'] ?? null),
            'getTopScorers' => $this->fnGetTopScorers($args['tournament_id'] ?? null),
            'searchPlayers' => $this->fnSearchPlayers($args['name'] ?? ''),
            'getRecentMatches' => $this->fnGetRecentMatches($args['tournament_id'] ?? null, $args['limit'] ?? 10),
            default => ['error' => "Función '$name' no encontrada"],
        };
    }

    private function fnGetTournaments(): array
    {
        return Tournament::withCount('players')
            ->latest()
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'status' => $t->status,
                'players_count' => $t->players_count,
                'created_at' => $t->created_at->format('d/m/Y'),
                'finished_at' => $t->finished_at?->format('d/m/Y H:i'),
            ])
            ->toArray();
    }

    private function fnGetStandings(?int $tournamentId): array
    {
        $id = $tournamentId ?? Tournament::latest()->first()?->id;
        $tournament = Tournament::with('matches')->find($id);
        if (!$tournament) return ['error' => 'Torneo no encontrado'];

        $standings = app(StandingsService::class)->calculate($tournament);
        return array_map(fn($s) => [
            'position' => $s['player_id'],
            'player' => $s['player_name'],
            'pts' => $s['pts'],
            'pj' => $s['pj'],
            'pg' => $s['pg'],
            'pe' => $s['pe'],
            'pp' => $s['pp'],
            'gf' => $s['gf'],
            'gc' => $s['gc'],
            'dg' => $s['dg'],
        ], $standings);
    }

    private function fnGetPrizes(?int $tournamentId): array
    {
        $id = $tournamentId ?? Tournament::latest()->first()?->id;
        $tournament = Tournament::with('prizes')->find($id);
        if (!$tournament) return ['error' => 'Torneo no encontrado'];

        return $tournament->prizes->sortBy('position')->map(fn($p) => [
            'position' => $p->position,
            'label' => $p->label,
            'amount' => $p->amount,
            'perks' => $p->perks ?? [],
            'is_featured' => $p->is_featured,
        ])->toArray();
    }

    private function fnGetTopScorers(?int $tournamentId): array
    {
        $id = $tournamentId ?? Tournament::latest()->first()?->id;
        $tournament = Tournament::with('matches')->find($id);
        if (!$tournament) return ['error' => 'Torneo no encontrado'];

        $standings = app(StandingsService::class)->calculate($tournament);
        $maxGf = max(array_column($standings, 'gf'));

        if ($maxGf <= 0) return ['message' => 'No hay goles registrados'];

        return array_values(array_map(
            fn($s) => ['player' => $s['player_name'], 'goals' => $s['gf']],
            array_filter($standings, fn($s) => $s['gf'] === $maxGf)
        ));
    }

    private function fnSearchPlayers(string $name): array
    {
        return Player::where('name', 'like', "%{$name}%")
            ->with('tournament')
            ->limit(20)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'tournament' => $p->tournament?->name,
                'tournament_id' => $p->tournament_id,
            ])
            ->toArray();
    }

    private function fnGetRecentMatches(?int $tournamentId, int $limit): array
    {
        $id = $tournamentId ?? Tournament::latest()->first()?->id;
        $tournament = Tournament::find($id);
        if (!$tournament) return ['error' => 'Torneo no encontrado'];

        return $tournament->matches()
            ->with(['player1', 'player2'])
            ->where('status', 'finished')
            ->latest('played_at')
            ->limit($limit)
            ->get()
            ->map(fn($m) => [
                'round' => $m->round,
                'player1' => $m->player1?->name,
                'player2' => $m->player2?->name,
                'score' => "{$m->score1} - {$m->score2}",
                'phase' => $m->phase ?? 'group',
                'played_at' => $m->played_at?->format('d/m/Y H:i'),
            ])
            ->toArray();
    }
}
