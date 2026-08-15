<?php

use App\Http\Controllers\Api\Mcp\McpController;
use App\Http\Controllers\Api\Mcp\OAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Endpoint MCP remoto (Streamable HTTP, JSON-RPC 2.0)
|--------------------------------------------------------------------------
| Montado en la raíz (/mcp) con el grupo de middleware "api" (sin sesión ni
| CSRF). Autenticación por CUALQUIERA de:
|   - Token Bearer de Sanctum  (Agent API — Claude Code/Desktop, API, apps host).
|   - OAuth 2.1 vía Passport    (conectores de UI web: Claude.ai, ChatGPT).
| El guard "sanctum,api" prueba Sanctum primero y cae a Passport.
*/
Route::match(['GET', 'POST', 'DELETE'], '/mcp', [McpController::class, 'handle'])
    ->middleware(['auth:sanctum,api', 'throttle:120,1']);

/*
|--------------------------------------------------------------------------
| OAuth 2.1 — descubrimiento y registro dinámico (para conectores web)
|--------------------------------------------------------------------------
| Passport ya expone /oauth/authorize y /oauth/token. Estos endpoints añaden
| lo que el flujo MCP exige además: metadatos de descubrimiento (RFC 8414 y
| 9728) y registro dinámico de clientes (RFC 7591). Todos públicos (sin auth).
*/
Route::get('/.well-known/oauth-authorization-server', [OAuthController::class, 'authorizationServer']);
Route::get('/.well-known/oauth-protected-resource', [OAuthController::class, 'protectedResource']);
Route::get('/.well-known/oauth-protected-resource/mcp', [OAuthController::class, 'protectedResource']);
Route::post('/oauth/register', [OAuthController::class, 'register'])->middleware('throttle:20,1');
