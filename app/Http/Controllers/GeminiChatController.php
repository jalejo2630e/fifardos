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
                        'tournament_id' => ['type' => 'integer', 'description' => 'ID del torneo'],
                    ],
                    'required' => ['tournament_id'],
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
        $request->validate(['message' => 'required|string|max:1000']);

        $config = ChatConfig::first();
        if ($config && !$config->is_active) {
            return response()->json(['reply' => 'El asistente está desactivado actualmente.']);
        }

        $prompt = $config->system_prompt ?? 'Eres un asistente de la FIFARDOS ELITE LEAGUE. Respondes en español de forma breve y amigable.';

        $history = $request->input('history', []);
        $contents = array_merge($history, [
            ['role' => 'user', 'parts' => [['text' => $request->input('message')]]],
        ]);

        $result = $this->callGemini($prompt, $contents);

        if (isset($result['error'])) {
            return response()->json(['reply' => $result['error']]);
        }

        return response()->json(['reply' => $result['text'], 'history' => $result['history'] ?? []]);
    }

    private function callGemini(string $systemPrompt, array $contents, int $depth = 0): array
    {
        if ($depth > 5) {
            return ['text' => 'La consulta requirió demasiadas operaciones. Intenta simplificarla.'];
        }

        $payload = [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => $contents,
            'tools' => [['functionDeclarations' => $this->tools]],
            'generationConfig' => [
                'maxOutputTokens' => 800,
                'temperature' => 0.7,
            ],
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . config('services.gemini.api_key'),
                $payload
            );

        $body = $response->json();

        if (isset($body['error'])) {
            Log::error('Gemini API error', ['error' => $body['error']]);
            return ['error' => 'Lo siento, ocurrió un error al procesar tu mensaje.'];
        }

        $candidate = $body['candidates'][0] ?? null;
        if (!$candidate) {
            return ['error' => 'Lo siento, no pude procesar tu mensaje.'];
        }

        $part = $candidate['content']['parts'][0] ?? null;
        if (!$part) {
            return ['error' => 'Lo siento, no pude procesar tu mensaje.'];
        }

        // If model wants to call a function
        if (isset($part['functionCall'])) {
            $fn = $part['functionCall'];
            $fnName = $fn['name'];
            $fnArgs = $fn['args'] ?? [];

            $fnResult = $this->executeFunction($fnName, $fnArgs);

            $contents[] = ['role' => 'model', 'parts' => [['functionCall' => ['name' => $fnName, 'args' => $fnArgs]]]];
            $contents[] = ['role' => 'user', 'parts' => [['functionResponse' => ['name' => $fnName, 'response' => ['result' => $fnResult]]]]];

            return $this->callGemini($systemPrompt, $contents, $depth + 1);
        }

        // Model responded with text
        $text = $part['text'] ?? '';
        return ['text' => $text, 'history' => $contents];
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
        $tournament = Tournament::with('matches')->find($tournamentId);
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
        $tournament = Tournament::with('prizes')->find($tournamentId);
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
        $tournament = Tournament::with('matches')->find($tournamentId);
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
        $tournament = Tournament::find($tournamentId);
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
