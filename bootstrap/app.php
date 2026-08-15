<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            // Endpoint MCP remoto en la raíz (/mcp), con middleware "api"
            // (sin sesión ni CSRF) — no bajo el prefijo /api.
            Route::middleware('api')->group(base_path('routes/mcp.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'human' => \App\Http\Middleware\EnsureHuman::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // El endpoint MCP siempre responde JSON (nunca redirige a login),
        // aunque el cliente no envíe el header Accept: application/json.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('mcp')) {
                // WWW-Authenticate con resource_metadata (RFC 9728): así los
                // conectores de UI web descubren el servidor OAuth y arrancan el
                // flujo de autorización (Authorization Code + PKCE) por su cuenta.
                $metadata = rtrim(url('/'), '/').'/.well-known/oauth-protected-resource';

                return response()->json([
                    'jsonrpc' => '2.0',
                    'id' => null,
                    'error' => ['code' => -32001, 'message' => 'Unauthorized: se requiere token (Bearer de Sanctum u OAuth).'],
                ], 401)->header('WWW-Authenticate', 'Bearer resource_metadata="'.$metadata.'"');
            }
        });
    })->create();
