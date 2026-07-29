<?php

namespace App\Http\Middleware;

use App\Support\HumanChallenge;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica el desafío anti-bots (HumanChallenge) en login/registro.
 * En fallo lanza un error de validación sobre el campo "captcha" (Inertia lo
 * muestra sin recargar; el desafío visible en pantalla se mantiene para reintentar).
 */
class EnsureHuman
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! HumanChallenge::passes($request)) {
            throw ValidationException::withMessages([
                'captcha' => __('Verificación incorrecta. Resolvé la operación e intentá de nuevo.'),
            ]);
        }

        return $next($request);
    }
}
