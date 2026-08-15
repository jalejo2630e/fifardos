<?php

namespace App\Http\Controllers\Api\Mcp;

use App\Http\Controllers\Api\Agent\AgentApiController;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * Servidor MCP remoto de FIFARDOS (transporte Streamable HTTP, JSON-RPC 2.0).
 *
 * Expone las mismas herramientas que el puente local (mcp/index.js) pero
 * hosteadas dentro de la app, de modo que un asistente (Claude, Cursor, etc.)
 * se conecta con SOLO una URL + token Bearer, sin instalar Node ni clonar el repo:
 *
 *   {
 *     "mcpServers": {
 *       "fifardos": {
 *         "type": "http",
 *         "url": "https://fifardos.com/mcp",
 *         "headers": { "Authorization": "Bearer 1|tu_token" }
 *       }
 *     }
 *   }
 *
 * La autenticación es por token Sanctum (mismo token de la Agent API); no se usa
 * OAuth porque las credenciales viajan en el header Authorization en cada request.
 */
class McpController extends Controller
{
    /** Versión del protocolo MCP por defecto si el cliente no indica una. */
    private const PROTOCOL_VERSION = '2025-06-18';

    public function handle(Request $request)
    {
        // El transporte Streamable HTTP usa GET para abrir un stream SSE (no lo
        // ofrecemos: somos sin estado) y DELETE para cerrar sesión (no-op).
        if ($request->isMethod('GET')) {
            return response('Method Not Allowed', 405)->header('Allow', 'POST');
        }
        if ($request->isMethod('DELETE')) {
            return response()->noContent();
        }

        $payload = $request->json()->all();
        if (!is_array($payload) || empty($payload)) {
            return response()->json($this->error(null, -32700, 'Parse error'), 400);
        }

        // JSON-RPC admite lotes (array de mensajes) o un mensaje único.
        $isBatch = array_is_list($payload);
        $messages = $isBatch ? $payload : [$payload];

        $responses = [];
        foreach ($messages as $msg) {
            if (!is_array($msg)) {
                $responses[] = $this->error(null, -32600, 'Invalid Request');
                continue;
            }
            $res = $this->dispatch($request, $msg);
            if ($res !== null) {
                $responses[] = $res;
            }
        }

        // Sólo había notificaciones (sin id): responder 202 sin cuerpo.
        if (empty($responses)) {
            return response()->noContent(202);
        }

        return response()->json($isBatch ? $responses : $responses[0]);
    }

    /** Procesa un mensaje JSON-RPC. Devuelve null si es una notificación. */
    private function dispatch(Request $request, array $msg): ?array
    {
        $id = $msg['id'] ?? null;
        $method = $msg['method'] ?? null;
        $params = is_array($msg['params'] ?? null) ? $msg['params'] : [];
        $isNotification = !array_key_exists('id', $msg);

        switch ($method) {
            case 'initialize':
                return $this->result($id, [
                    'protocolVersion' => $params['protocolVersion'] ?? self::PROTOCOL_VERSION,
                    'capabilities' => ['tools' => ['listChanged' => false]],
                    'serverInfo' => ['name' => 'fifardos', 'version' => '1.0.0'],
                    'instructions' => 'Herramientas de FIFARDOS: listar y crear torneos de fútbol, ver la tabla de posiciones, el goleador, los partidos, los datos de un jugador y buscar resúmenes. Cada operación queda acotada al usuario dueño del token.',
                ]);

            case 'notifications/initialized':
            case 'notifications/cancelled':
                return null;

            case 'ping':
                return $this->result($id, (object) []);

            case 'tools/list':
                return $this->result($id, ['tools' => $this->tools()]);

            case 'tools/call':
                return $this->callTool($request, $id, $params);

            case 'resources/list':
                return $this->result($id, ['resources' => []]);

            case 'prompts/list':
                return $this->result($id, ['prompts' => []]);

            default:
                if ($isNotification) {
                    return null;
                }
                return $this->error($id, -32601, "Method not found: {$method}");
        }
    }

    private function callTool(Request $request, $id, array $params): array
    {
        $name = $params['name'] ?? '';
        $args = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];
        $user = $request->user();

        try {
            $data = $this->runTool($user, $name, $args);
        } catch (ValidationException $e) {
            return $this->toolError($id, 'Datos inválidos: ' . implode(' ', Arr::flatten($e->errors())));
        } catch (ModelNotFoundException $e) {
            return $this->toolError($id, 'No encontrado o no te pertenece.');
        } catch (\Throwable $e) {
            return $this->toolError($id, 'Error: ' . $e->getMessage());
        }

        if ($data === null) {
            return $this->error($id, -32602, "Herramienta desconocida: {$name}");
        }

        return $this->result($id, [
            'content' => [[
                'type' => 'text',
                'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]],
            'isError' => false,
        ]);
    }

    /**
     * Ejecuta la herramienta reutilizando el AgentApiController.
     * Devuelve el array de datos, o null si la herramienta no existe.
     */
    private function runTool($user, string $name, array $args): ?array
    {
        $agent = app(AgentApiController::class);

        // Construye una sub-request autenticada con los argumentos como input.
        $req = function (array $input) use ($user): Request {
            $r = Request::create('/', 'POST', $input);
            $r->setUserResolver(fn () => $user);
            return $r;
        };

        return match ($name) {
            'list_tournaments' => $this->data($agent->tournaments($req([]))),

            'create_tournament' => $this->data($agent->createTournament($req([
                'name' => $args['name'] ?? null,
                'players' => $args['players'] ?? null,
                'consoles_count' => $args['consoles_count'] ?? 1,
            ]))),

            'get_standings' => $this->data($agent->standings($req([]), (int) ($args['tournament_id'] ?? 0))),

            'get_top_scorer' => $this->data($agent->topScorer($req([]), (int) ($args['tournament_id'] ?? 0))),

            'get_matches' => $this->data($agent->matches(
                $req(isset($args['status']) ? ['status' => $args['status']] : []),
                (int) ($args['tournament_id'] ?? 0)
            )),

            'record_score' => $this->data($agent->recordScore(
                $req(array_filter([
                    'score1' => $args['score1'] ?? null,
                    'score2' => $args['score2'] ?? null,
                    'penalties1' => $args['penalties1'] ?? null,
                    'penalties2' => $args['penalties2'] ?? null,
                ], fn ($v) => $v !== null)),
                (int) ($args['tournament_id'] ?? 0),
                (int) ($args['match_id'] ?? 0)
            )),

            'get_player' => $this->data($agent->player($req([]), (int) ($args['player_id'] ?? 0))),

            'search' => $this->data($agent->search($req(['query' => $args['query'] ?? '']))),

            default => null,
        };
    }

    /** Extrae el array de datos de una JsonResponse. */
    private function data($response): array
    {
        if ($response instanceof JsonResponse) {
            return (array) $response->getData(true);
        }
        return (array) $response;
    }

    /** Catálogo de herramientas (idéntico al puente local mcp/index.js). */
    private function tools(): array
    {
        return [
            [
                'name' => 'list_tournaments',
                'description' => "Lista todos los torneos de fútbol: nombre, estado, número de jugadores, progreso de partidos y quién va líder. Úsalo para '¿qué torneos hay?', '¿cuál está en curso?', '¿quién va ganando?'.",
                'inputSchema' => ['type' => 'object', 'properties' => (object) [], 'additionalProperties' => false],
            ],
            [
                'name' => 'create_tournament',
                'description' => "Crea un torneo de fútbol nuevo con su lista de jugadores. Genera automáticamente el fixture de fase de grupos (todos contra todos) repartido entre las consolas. Úsalo cuando el usuario pida 'ármame/créame un torneo'.",
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'Nombre del torneo'],
                        'players' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'minItems' => 2,
                            'maxItems' => 32,
                            'description' => 'Nombres de los jugadores (mínimo 2, sin repetidos)',
                        ],
                        'consoles_count' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 20,
                            'description' => 'Consolas/TVs disponibles (opcional, por defecto 1)',
                        ],
                    ],
                    'required' => ['name', 'players'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'get_standings',
                'description' => "Tabla de posiciones de un torneo: posición, puntos, PJ/PG/PE/PP, goles y si ya hay campeón. Úsalo para '¿cómo va la tabla?' o '¿ya hay campeón?'.",
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => ['tournament_id' => ['type' => 'integer', 'description' => 'ID del torneo']],
                    'required' => ['tournament_id'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'get_top_scorer',
                'description' => "Máximo goleador de un torneo (goles acumulados y goles por partido). Úsalo para '¿quién lleva más goles?'.",
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => ['tournament_id' => ['type' => 'integer', 'description' => 'ID del torneo']],
                    'required' => ['tournament_id'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'get_matches',
                'description' => "Partidos de un torneo (ronda, TV, jugadores, marcador, ganador). Filtrable por estado. Úsalo para '¿qué partidos faltan?' o '¿cómo van los partidos?'.",
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'tournament_id' => ['type' => 'integer', 'description' => 'ID del torneo'],
                        'status' => ['type' => 'string', 'enum' => ['pending', 'finished'], 'description' => 'Filtrar por estado (opcional)'],
                    ],
                    'required' => ['tournament_id'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'record_score',
                'description' => "Registra/actualiza el marcador de un partido y lo marca como jugado. En deportes de goles (FIFA, etc.) 'score1' es del competidor1 y 'score2' del competidor2 TAL COMO los devuelve get_matches. Úsalo para 'ponle a X 1 y a B 2' o 'actualiza el marcador de X vs B'. FLUJO: primero resuelve el torneo con list_tournaments y el 'match_id' (y qué lado es cada competidor) con get_matches; luego llama aquí. Recalcula la tabla y cierra el torneo si ya se jugaron todos los partidos.",
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'tournament_id' => ['type' => 'integer', 'description' => 'ID del torneo'],
                        'match_id' => ['type' => 'integer', 'description' => 'ID del partido (campo "id" de get_matches)'],
                        'score1' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Goles del competidor1'],
                        'score2' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Goles del competidor2'],
                        'penalties1' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Penales del competidor1 (opcional; solo si el deporte usa penales y hubo empate)'],
                        'penalties2' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Penales del competidor2 (opcional)'],
                    ],
                    'required' => ['tournament_id', 'match_id', 'score1', 'score2'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'get_player',
                'description' => "Datos y estadísticas de un jugador (partidos, victorias, goles, puntos, torneo). Úsalo para 'dame los datos de X jugador'.",
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => ['player_id' => ['type' => 'integer', 'description' => 'ID del jugador']],
                    'required' => ['player_id'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'search',
                'description' => "Búsqueda semántica sobre resúmenes narrativos del desempeño de jugadores en un período. Úsalo SOLO para preguntas abiertas tipo '¿cómo le fue a X este mes?'. Para datos exactos usa las otras herramientas.",
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => ['query' => ['type' => 'string', 'description' => 'Pregunta en lenguaje natural']],
                    'required' => ['query'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    private function result($id, $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function error($id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }

    /** Error de ejecución de herramienta: va dentro de result con isError=true (MCP). */
    private function toolError($id, string $text): array
    {
        return $this->result($id, [
            'content' => [['type' => 'text', 'text' => $text]],
            'isError' => true,
        ]);
    }
}
