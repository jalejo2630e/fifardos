# FIFARDOS · Servidor MCP

Servidor [MCP (Model Context Protocol)](https://modelcontextprotocol.io) que conecta FIFARDOS con asistentes de IA como **Claude**, **ChatGPT**, **GitHub Copilot** y **Cursor**. Con él puedes pedirle a tu asistente cosas como:

> «Ármame un torneo de FIFA llamado *Copa Sábado* con Diego, Julián, Javier y Sebas en 2 consolas»
> «¿Cómo va la tabla del torneo 3?» · «¿Quién lleva más goles?»

## Herramientas expuestas

| Herramienta | Qué hace |
|---|---|
| `list_tournaments` | Lista los torneos, su estado y quién va líder |
| `create_tournament` | **Crea un torneo** con jugadores y genera el fixture |
| `get_standings` | Tabla de posiciones de un torneo |
| `get_top_scorer` | Máximo goleador de un torneo |
| `get_matches` | Partidos (filtrable por `pending` / `finished`) |
| `get_player` | Datos y estadísticas de un jugador |
| `search` | Búsqueda semántica de resúmenes de jugadores |

## 1. Instalación

```bash
cd mcp
npm install
```

Requiere Node.js ≥ 18.

## 2. Genera un token

1. Inicia sesión en FIFARDOS.
2. Ve a **API tokens** (`/api-tokens`) y crea uno nuevo.
3. Copia el token (empieza por algo como `1|xxxx...`).

## 3. Configura tu asistente

El servidor se lanza con `node /ruta/al/proyecto/mcp/index.js` y lee dos variables de entorno:

- `FIFARDOS_BASE_URL` — la URL de tu FIFARDOS (ej. `https://tudominio.com` o `http://127.0.0.1:8000`)
- `FIFARDOS_TOKEN` — el token del paso anterior

### Claude Desktop

Edita `claude_desktop_config.json`
(macOS: `~/Library/Application Support/Claude/claude_desktop_config.json`):

```json
{
  "mcpServers": {
    "fifardos": {
      "command": "node",
      "args": ["/ruta/absoluta/al/proyecto/mcp/index.js"],
      "env": {
        "FIFARDOS_BASE_URL": "http://127.0.0.1:8000",
        "FIFARDOS_TOKEN": "1|tu_token_aqui"
      }
    }
  }
}
```

Reinicia Claude Desktop. Verás las herramientas de FIFARDOS disponibles.

### Cursor

En `~/.cursor/mcp.json` (global) o `.cursor/mcp.json` (por proyecto):

```json
{
  "mcpServers": {
    "fifardos": {
      "command": "node",
      "args": ["/ruta/absoluta/al/proyecto/mcp/index.js"],
      "env": {
        "FIFARDOS_BASE_URL": "http://127.0.0.1:8000",
        "FIFARDOS_TOKEN": "1|tu_token_aqui"
      }
    }
  }
}
```

### VS Code + GitHub Copilot (modo agente)

En `.vscode/mcp.json` del proyecto (o vía *MCP: Add Server*):

```json
{
  "servers": {
    "fifardos": {
      "type": "stdio",
      "command": "node",
      "args": ["/ruta/absoluta/al/proyecto/mcp/index.js"],
      "env": {
        "FIFARDOS_BASE_URL": "http://127.0.0.1:8000",
        "FIFARDOS_TOKEN": "1|tu_token_aqui"
      }
    }
  }
}
```

Luego abre Copilot Chat en modo **Agent** y las herramientas `fifardos` aparecerán.

### ChatGPT

ChatGPT admite conectores MCP (Developer Mode / *custom connectors*) sobre transporte HTTP/SSE. Este servidor usa transporte **stdio**; para exponerlo por HTTP a ChatGPT, envuélvelo con un puente como [`mcp-proxy`](https://github.com/sparfenyuk/mcp-proxy) o [`supergateway`](https://github.com/supercorp-ai/supergateway):

```bash
npx -y supergateway --stdio "node /ruta/absoluta/al/proyecto/mcp/index.js" --port 8787
```

y registra `http://localhost:8787/sse` como conector MCP en ChatGPT.

## 4. Probar rápido (opcional)

```bash
FIFARDOS_BASE_URL=http://127.0.0.1:8000 FIFARDOS_TOKEN=1|tu_token \
  node index.js
```

El servidor queda escuchando por stdio; tu cliente MCP se encarga del resto.

## Notas

- La API respeta la autenticación de Sanctum: cada token pertenece a un usuario, así que los torneos que cree el asistente quedan bajo ese usuario.
- Todas las herramientas son de solo lectura excepto `create_tournament`.
- El esquema completo de la API está en `GET /api/agent/schema`.
