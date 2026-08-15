<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // OAuth 2.1 (Passport) para el endpoint MCP remoto. Los conectores de UI
        // web (Claude.ai, ChatGPT) usan Authorization Code + PKCE con clientes
        // registrados dinámicamente. Un solo scope 'mcp' basta para el acceso.
        Passport::tokensCan(['mcp' => 'Consultar y gestionar torneos de FIFARDOS']);
        Passport::defaultScopes(['mcp']);
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        // Pantalla de consentimiento que ve el usuario al conectar un cliente web.
        Passport::authorizationView('oauth.authorize');
    }
}
