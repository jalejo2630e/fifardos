# FIFARDOS · API para la app móvil

Autenticación por **Bearer token** (Laravel Sanctum). Base: `https://fifardos.com`.
Enviá siempre `Accept: application/json`. Para rutas protegidas: `Authorization: Bearer {token}`.

## Autenticación

### POST /api/auth/login
Body: `{ "email", "password", "device_name?" }`
→ `200 { "token": "1|xxxx", "user": { id, name, email, is_admin, avatar_url } }`
→ `422` credenciales inválidas · rate limit 10/min (+ bloqueo por email+IP tras 5 fallos).

### POST /api/auth/register
Body: `{ "name", "email", "password", "password_confirmation", "device_name?" }`
→ `201 { "token", "user" }` · rate limit 5/min.

### GET /api/auth/me  *(Bearer)*
→ `200 { "user": {...} }`

### POST /api/auth/logout  *(Bearer)*
Revoca el token actual. → `200 { "success": true }`

## Torneos *(Bearer — escopados al usuario del token)*

| Método | Ruta | Descripción |
|---|---|---|
| GET  | `/api/agent/tournaments` | Lista de torneos del usuario (estado, progreso, líder) |
| POST | `/api/agent/tournaments` | Crea torneo `{ name, players[], consoles_count? }` |
| GET  | `/api/agent/tournaments/{id}/standings` | Tabla de posiciones |
| GET  | `/api/agent/tournaments/{id}/top-scorer` | Goleador |
| GET  | `/api/agent/tournaments/{id}/matches?status=pending\|finished` | Partidos |
| POST | `/api/agent/tournaments/{id}/matches/{matchId}/score` | **Registra un marcador** |
| GET  | `/api/agent/players/{id}` | Datos y stats de un jugador |
| GET  | `/api/agent/schema` | Autodescripción de la API |

### POST /api/agent/tournaments/{id}/matches/{matchId}/score
Body: `{ "score1", "score2", "penalties1?", "penalties2?", "played_at?", "goal_scorers?": [{ "player_id", "goals" }] }`
→ `200 { success, match: { id, score1, score2, status }, tournament_status }`
Nota: la generación/avance de eliminatorias se reconcilia al abrir el torneo en la web.

## Flujo offline / sincronización (app)
1. Al abrir, la app guarda en almacenamiento interno (Preferences) el token y la caché de torneos.
2. Cargás resultados offline; cada marcador se encola localmente.
3. Se sincroniza (POST score) al guardar cada partido cuando hay conexión, y al finalizar el torneo.

## Errores
Formato Laravel: `422` validación `{ message, errors }`; `401` sin token; `403` recurso ajeno; `404` no encontrado.
