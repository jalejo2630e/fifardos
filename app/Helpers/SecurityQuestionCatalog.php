<?php

namespace App\Helpers;

class SecurityQuestionCatalog
{
    const QUESTIONS = [
        '¿Cuál era el nombre de tu primera mascota?',
        '¿En qué ciudad naciste?',
        '¿Cuál es el segundo nombre de tu madre?',
        '¿Cuál fue tu primer equipo de fútbol favorito?',
        '¿Cuál era el nombre de tu colegio de primaria?',
        '¿Cuál es tu película favorita?',
        '¿Cómo se llamaba tu mejor amigo de la infancia?',
        '¿Cuál es tu comida favorita?',
        '¿Cuál es tu color favorito?',
        '¿Cuál es el nombre de tu personaje favorito?',
    ];

    const MAX_ATTEMPTS = 5;
    const DECAY_MINUTES = 15;

    public static function all(): array
    {
        return self::QUESTIONS;
    }

    public static function isValid(string $question): bool
    {
        return in_array($question, self::QUESTIONS, true);
    }

    public static function normalizeAnswer(string $answer): string
    {
        $value = mb_strtolower(trim($answer), 'UTF-8');

        $unwanted = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'Ü' => 'u', 'Ñ' => 'n',
        ];

        return strtr($value, $unwanted);
    }

    public static function hashAnswer(string $answer): string
    {
        return bcrypt(self::normalizeAnswer($answer));
    }

    public static function checkAnswer(string $answer, string $hash): bool
    {
        return password_verify(self::normalizeAnswer($answer), $hash);
    }
}
