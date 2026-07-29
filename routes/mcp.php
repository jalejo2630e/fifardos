<?php

use App\Http\Controllers\Api\Mcp\McpController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Endpoint MCP remoto (Streamable HTTP, JSON-RPC 2.0)
|--------------------------------------------------------------------------
| Montado en la raíz (/mcp) con el grupo de middleware "api" (sin sesión ni
| CSRF), autenticado por token Bearer de Sanctum. Un asistente se conecta con
| solo la URL + header Authorization, sin instalar nada localmente.
*/
Route::match(['GET', 'POST', 'DELETE'], '/mcp', [McpController::class, 'handle'])
    ->middleware(['auth:sanctum', 'throttle:120,1']);
