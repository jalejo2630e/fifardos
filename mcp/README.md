# FIFARDOS · Servidor MCP

Conecta FIFARDOS con asistentes de IA como **Claude**, **ChatGPT**, **GitHub Copilot** y **Cursor** vía [MCP (Model Context Protocol)](https://modelcontextprotocol.io). Con él puedes pedirle a tu asistente cosas como:

> «Ármame un torneo de fútbol llamado *Copa Sábado* con Diego, Julián, Javier y Sebas en 2 consolas»
> «¿Cómo va la tabla del torneo 3?» · «¿Quién lleva más goles?»
> «En la Copa Sábado, ¿qué partidos faltan?» · «Actualiza el marcador de Diego vs Javier: 1 a 2»

## Herramientas expuestas

| Herramienta | Qué hace |
|---|---|
| `list_tournaments` | Lista los torneos, su estado y quién va líder |
| `create_tournament` | **Crea un torneo** con jugadores y genera el fixture |
| `get_standings` | Tabla de posiciones de un torneo |
| `get_top_scorer` | Máximo goleador de un torneo |
| `get_matches` | Partidos (filtrable por `pending` / `finished`) |
| `record_score` | **Registra/actualiza el marcador** de un partido y lo marca como jugado |
| `get_player` | Datos y estadísticas de un jugador |
| `search` | Búsqueda semántica de resúmenes de jugadores |

## Genera un token

1. Inicia sesión en FIFARDOS y ve a **API tokens** (`/api-tokens`).
2. Crea uno nuevo y copia el token (empieza por algo como `1|xxxx...`).

Cada token pertenece a un usuario: los torneos que cree el asistente quedan bajo ese usuario.

---

## Opción A — Servidor MCP remoto (recomendada, sin instalar nada)

FIFARDOS hostea el servidor MCP en `https://fifardos.com/mcp` con transporte **Streamable HTTP**. No necesitas clonar el repo ni Node: solo la URL y tu token en el header `Authorization`.

### Claude / Cursor / VS Code (Copilot) — clientes con soporte HTTP

```json
{
  "mcpServers": {
    "fifardos": {
      "type": "http",
      "url": "https://fifardos.com/mcp",
      "headers": {
        "Authorization": "Bearer 1|tu_token_aqui"
      }
    }
  }
}
```

### ChatGPT / otros conectores remotos

Agrega un conector MCP remoto (Streamable HTTP) apuntando a:

```
URL:     https://fifardos.com/mcp
Header:  Authorization: Bearer 1|tu_token_aqui
```

### Probar el endpoint remoto por curl

```bash
curl -s https://fifardos.com/mcp \
  -H "Authorization: Bearer 1|tu_token_aqui" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

Debe devolver la lista de herramientas.

### Autenticación: token Bearer **o** OAuth 2.1

El endpoint `/mcp` acepta dos métodos en paralelo:

1. **Token Bearer de Sanctum** (lo de arriba) — ideal para Claude Code/Desktop, la API de OpenAI y apps host (Cherry Studio, etc., por donde entra DeepSeek). Pega el token en el header `Authorization` y listo.
2. **OAuth 2.1 (Authorization Code + PKCE)** — para los **conectores de UI web** de Claude.ai y ChatGPT, que no aceptan un header manual. Solo pega la URL `https://fifardos.com/mcp` como conector: el cliente descubre el servidor OAuth, se registra solito y te manda a iniciar sesión en FIFARDOS y aprobar el acceso. No hay que copiar ningún token.

El descubrimiento OAuth vive en:

```
GET /.well-known/oauth-protected-resource      (RFC 9728)
GET /.well-known/oauth-authorization-server     (RFC 8414)
POST /oauth/register                            (registro dinámico, RFC 7591)
GET /oauth/authorize · POST /oauth/token         (Passport)
```

---

## Opción B — Puente local por stdio (avanzada)

Útil si prefieres que el servidor corra en tu máquina (p. ej. contra una instancia local de FIFARDOS).

```bash
cd mcp
npm install    # Node.js >= 18
```

El servidor se lanza con `node /ruta/absoluta/al/repo/mcp/index.js` y lee dos variables de entorno:

- `FIFARDOS_BASE_URL` — la URL de tu FIFARDOS (ej. `http://127.0.0.1:8000` o `https://fifardos.com`)
- `FIFARDOS_TOKEN` — el token del paso anterior

Config para Claude Desktop / Cursor (`claude_desktop_config.json` o `~/.cursor/mcp.json`):

```json
{
  "mcpServers": {
    "fifardos": {
      "command": "node",
      "args": ["/ruta/absoluta/al/repo/mcp/index.js"],
      "env": {
        "FIFARDOS_BASE_URL": "https://fifardos.com",
        "FIFARDOS_TOKEN": "1|tu_token_aqui"
      }
    }
  }
}
```

Para VS Code + Copilot usa el mismo bloque bajo `"servers"` con `"type": "stdio"`.

Probar rápido:

```bash
FIFARDOS_BASE_URL=https://fifardos.com FIFARDOS_TOKEN=1|tu_token node index.js
```

---

## Notas

- La API respeta la autenticación de Sanctum: cada operación queda acotada al usuario dueño del token.
- Todas las herramientas son de solo lectura excepto `create_tournament` y `record_score` (escritura).
- El esquema completo de la API REST está en `GET /api/agent/schema`.
- El endpoint remoto y el puente local exponen exactamente las mismas herramientas.
