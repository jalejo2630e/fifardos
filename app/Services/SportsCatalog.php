<?php

namespace App\Services;

/**
 * Catálogo central de deportes. Lee la configuración de config/sports.php
 * y expone helpers para toda la lógica del sistema (puntaje, sets, equipos).
 */
class SportsCatalog
{
    public static function all(): array
    {
        $sports = config('sports', []);
        foreach ($sports as $key => &$sport) {
            $sport['key'] = $key;
        }
        return $sports;
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function get(string $key): array
    {
        $sport = config('sports.' . $key, null);
        if ($sport === null) {
            $sport = config('sports.soccer');
        }
        return $sport;
    }

    public static function name(string $key): string
    {
        return self::get($key)['name'];
    }

    public static function icon(string $key): string
    {
        return self::get($key)['icon'] ?? '🏆';
    }

    /** 'individual' | 'team' */
    public static function type(string $key): string
    {
        return self::get($key)['type'] ?? 'individual';
    }

    public static function isTeam(string $key): bool
    {
        return self::type($key) === 'team';
    }

    /** 'goals' | 'points' | 'sets' */
    public static function scoring(string $key): string
    {
        return self::get($key)['scoring'] ?? 'goals';
    }

    public static function isSets(string $key): bool
    {
        return self::scoring($key) === 'sets';
    }

    public static function allowsDraw(string $key): bool
    {
        return (bool) (self::get($key)['allows_draw'] ?? true);
    }

    public static function usesPenalties(string $key): bool
    {
        return (bool) (self::get($key)['uses_penalties'] ?? false);
    }

    public static function maxSets(string $key): int
    {
        return (int) (self::get($key)['max_sets'] ?? 3);
    }

    public static function playersPerSide(string $key): int
    {
        return (int) (self::get($key)['players_per_side'] ?? 1);
    }

    public static function pointsRule(string $key): array
    {
        return self::get($key)['points'] ?? ['win' => 3, 'draw' => 1, 'loss' => 0];
    }

    public static function winPoints(string $key): int
    {
        return (int) self::pointsRule($key)['win'];
    }

    public static function drawPoints(string $key): int
    {
        return (int) self::pointsRule($key)['draw'];
    }

    public static function minutes(string $key): int
    {
        return (int) (self::get($key)['minutes'] ?? 6);
    }

    /**
     * Determina el ganador de un partido según el deporte, considerando
     * sets, puntos y penales. Devuelve 1 (local), 2 (visitante) o 0 (empate).
     */
    public static function matchOutcome(string $sportKey, ?int $s1, ?int $s2, ?int $p1 = null, ?int $p2 = null): int
    {
        if ($s1 === null || $s2 === null) {
            return 0;
        }
        if ($s1 > $s2) {
            return 1;
        }
        if ($s2 > $s1) {
            return 2;
        }
        // Empate en el marcador principal: penales si aplican
        if ($p1 !== null && $p2 !== null && $p1 !== $p2) {
            return $p1 > $p2 ? 1 : 2;
        }
        return 0;
    }
}
