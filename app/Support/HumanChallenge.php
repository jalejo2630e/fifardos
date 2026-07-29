<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Anti-bots sin servicios externos para login/registro:
 *  1) Desafío aritmético simple cuya respuesta vive en la sesión (no falsificable).
 *  2) Honeypot: un campo oculto ("website") que un humano nunca completa.
 *  3) Time-trap: se rechaza el envío si llega demasiado rápido (bots automáticos).
 */
class HumanChallenge
{
    const ANSWER_KEY = 'human_challenge_answer';
    const TIME_KEY = 'human_challenge_ts';
    const MIN_SECONDS = 2;

    /**
     * Genera un desafío nuevo, guarda la respuesta y el timestamp en sesión,
     * y devuelve los operandos para mostrarlos en el formulario.
     */
    public static function make(): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        session([
            self::ANSWER_KEY => $a + $b,
            self::TIME_KEY => now()->timestamp,
        ]);

        return ['a' => $a, 'b' => $b];
    }

    /** ¿La solicitud parece humana? */
    public static function passes(Request $request): bool
    {
        // Honeypot: si viene con contenido, es un bot.
        if (filled($request->input('website'))) {
            return false;
        }

        // Time-trap: envíos instantáneos = bot.
        $ts = session(self::TIME_KEY);
        if ($ts && (now()->timestamp - (int) $ts) < self::MIN_SECONDS) {
            return false;
        }

        // Desafío aritmético.
        $expected = session(self::ANSWER_KEY);
        if ($expected === null) {
            return false;
        }

        return (int) $request->input('captcha') === (int) $expected;
    }

    /** Limpia el desafío tras un envío exitoso. */
    public static function forget(): void
    {
        session()->forget([self::ANSWER_KEY, self::TIME_KEY]);
    }
}
