#!/usr/bin/env node
/**
 * Servidor MCP de FIFARDOS.
 *
 * Expone las herramientas de la Agent API de FIFARDOS a través del
 * Model Context Protocol (MCP), de modo que asistentes como Claude Desktop,
 * Cursor, GitHub Copilot o ChatGPT puedan consultar torneos, tablas de
 * posiciones, goleadores y — sobre todo — CREAR un torneo a pedido del usuario.
 *
 * Config vía variables de entorno:
 *   FIFARDOS_BASE_URL  (ej. https://tu-dominio.com  o  http://127.0.0.1:8000)
 *   FIFARDOS_TOKEN     (token Bearer de Sanctum con habilidad "agent:access")
 */

import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
    ListToolsRequestSchema,
    CallToolRequestSchema,
} from "@modelcontextprotocol/sdk/types.js";

const BASE_URL = (process.env.FIFARDOS_BASE_URL || "http://127.0.0.1:8000").replace(/\/+$/, "");
const TOKEN = process.env.FIFARDOS_TOKEN || "";

if (!TOKEN) {
    console.error(
        "[fifardos-mcp] Falta FIFARDOS_TOKEN. Genera un token en la app (API tokens) y expórtalo como variable de entorno."
    );
}

/** Llama a la Agent API de FIFARDOS y devuelve el JSON (o lanza error legible). */
async function api(method, path, body) {
    const res = await fetch(`${BASE_URL}/api/agent${path}`, {
        method,
        headers: {
            Authorization: `Bearer ${TOKEN}`,
            Accept: "application/json",
            ...(body ? { "Content-Type": "application/json" } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    const text = await res.text();
    let json;
    try {
        json = text ? JSON.parse(text) : {};
    } catch {
        json = { raw: text };
    }

    if (!res.ok) {
        const msg = json?.message || json?.error || res.statusText;
        throw new Error(`FIFARDOS API ${res.status}: ${msg}`);
    }
    return json;
}

const TOOLS = [
    {
        name: "list_tournaments",
        description:
            "Lista todos los torneos: nombre, estado, número de jugadores, progreso de partidos y quién va líder. Úsalo para '¿qué torneos hay?', '¿cuál está en curso?', '¿quién va ganando?'.",
        inputSchema: { type: "object", properties: {}, additionalProperties: false },
        run: () => api("GET", "/tournaments"),
    },
    {
        name: "create_tournament",
        description:
            "Crea un torneo nuevo con su lista de jugadores. Genera automáticamente el fixture de fase de grupos (todos contra todos) repartido entre las consolas. Úsalo cuando el usuario pida 'ármame/créame un torneo'.",
        inputSchema: {
            type: "object",
            properties: {
                name: { type: "string", description: "Nombre del torneo" },
                players: {
                    type: "array",
                    items: { type: "string" },
                    minItems: 2,
                    maxItems: 32,
                    description: "Nombres de los jugadores (mínimo 2, sin repetidos)",
                },
                consoles_count: {
                    type: "integer",
                    minimum: 1,
                    maximum: 20,
                    description: "Consolas/TVs disponibles (opcional, por defecto 1)",
                },
            },
            required: ["name", "players"],
            additionalProperties: false,
        },
        run: (args) =>
            api("POST", "/tournaments", {
                name: args.name,
                players: args.players,
                consoles_count: args.consoles_count ?? 1,
            }),
    },
    {
        name: "get_standings",
        description:
            "Tabla de posiciones de un torneo: posición, puntos, PJ/PG/PE/PP, goles y si ya hay campeón. Úsalo para '¿cómo va la tabla?' o '¿ya hay campeón?'.",
        inputSchema: {
            type: "object",
            properties: { tournament_id: { type: "integer", description: "ID del torneo" } },
            required: ["tournament_id"],
            additionalProperties: false,
        },
        run: (args) => api("GET", `/tournaments/${args.tournament_id}/standings`),
    },
    {
        name: "get_top_scorer",
        description:
            "Máximo goleador de un torneo (goles acumulados y goles por partido). Úsalo para '¿quién lleva más goles?'.",
        inputSchema: {
            type: "object",
            properties: { tournament_id: { type: "integer", description: "ID del torneo" } },
            required: ["tournament_id"],
            additionalProperties: false,
        },
        run: (args) => api("GET", `/tournaments/${args.tournament_id}/top-scorer`),
    },
    {
        name: "get_matches",
        description:
            "Partidos de un torneo (ronda, TV, jugadores, marcador, ganador). Filtrable por estado. Úsalo para '¿qué partidos faltan?' o '¿cómo van los partidos?'.",
        inputSchema: {
            type: "object",
            properties: {
                tournament_id: { type: "integer", description: "ID del torneo" },
                status: {
                    type: "string",
                    enum: ["pending", "finished"],
                    description: "Filtrar por estado (opcional)",
                },
            },
            required: ["tournament_id"],
            additionalProperties: false,
        },
        run: (args) => {
            const q = args.status ? `?status=${encodeURIComponent(args.status)}` : "";
            return api("GET", `/tournaments/${args.tournament_id}/matches${q}`);
        },
    },
    {
        name: "get_player",
        description:
            "Datos y estadísticas de un jugador (partidos, victorias, goles, puntos, torneo). Úsalo para 'dame los datos de X jugador'.",
        inputSchema: {
            type: "object",
            properties: { player_id: { type: "integer", description: "ID del jugador" } },
            required: ["player_id"],
            additionalProperties: false,
        },
        run: (args) => api("GET", `/players/${args.player_id}`),
    },
    {
        name: "search",
        description:
            "Búsqueda semántica sobre resúmenes narrativos del desempeño de jugadores en un período. Úsalo SOLO para preguntas abiertas tipo '¿cómo le fue a X este mes?'. Para datos exactos usa las otras herramientas.",
        inputSchema: {
            type: "object",
            properties: { query: { type: "string", description: "Pregunta en lenguaje natural" } },
            required: ["query"],
            additionalProperties: false,
        },
        run: (args) => api("POST", "/search", { query: args.query }),
    },
];

const server = new Server(
    { name: "fifardos", version: "1.0.0" },
    { capabilities: { tools: {} } }
);

server.setRequestHandler(ListToolsRequestSchema, async () => ({
    tools: TOOLS.map(({ name, description, inputSchema }) => ({ name, description, inputSchema })),
}));

server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const tool = TOOLS.find((t) => t.name === request.params.name);
    if (!tool) {
        return {
            isError: true,
            content: [{ type: "text", text: `Herramienta desconocida: ${request.params.name}` }],
        };
    }

    try {
        const result = await tool.run(request.params.arguments || {});
        return { content: [{ type: "text", text: JSON.stringify(result, null, 2) }] };
    } catch (err) {
        return {
            isError: true,
            content: [{ type: "text", text: `Error: ${err.message}` }],
        };
    }
});

const transport = new StdioServerTransport();
await server.connect(transport);
console.error(`[fifardos-mcp] servidor MCP conectado. BASE_URL=${BASE_URL}`);
