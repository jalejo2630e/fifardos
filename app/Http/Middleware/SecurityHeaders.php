<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Sólo cabeceras (no toca respuestas de streaming/descargas binarias problemáticas)
        $headers = $response->headers;

        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), browsing-topics=()');

        // CSP conservador: bloquea clickjacking (frame-ancestors), inyección de <base>
        // y de <object>/<embed>, sin restringir script/style/img (para no romper Vite,
        // Ziggy @routes inline, JSON-LD, fuentes de Google ni avatares externos).
        $headers->set('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none'; base-uri 'self'");

        // HSTS sólo sobre HTTPS (evita problemas en http/local).
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Respuestas autenticadas: nunca cachear (evita filtrar datos de un usuario a
        // otro vía caché de navegador/proxy/service worker). Los assets estáticos
        // (/build, íconos) se sirven fuera de este middleware, así que siguen cacheables.
        if ($request->user()) {
            $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
