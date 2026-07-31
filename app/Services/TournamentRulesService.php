<?php

namespace App\Services;

use App\Models\SportRuleDefinition;
use App\Models\Tournament;
use Illuminate\Support\Collection;

/**
 * Reglas parametrizables por deporte. Expone las definiciones (para el formulario
 * dinámico) y valida/normaliza los valores elegidos por el usuario.
 */
class TournamentRulesService
{
    /** Definiciones agrupadas por deporte, listas para el frontend. */
    public function definitionsBySport(): array
    {
        return SportRuleDefinition::orderBy('sort_order')
            ->get()
            ->groupBy('sport')
            ->map(fn (Collection $group) => $group->values()->all())
            ->all();
    }

    /** Definiciones para un deporte concreto. */
    public function definitionsFor(string $sport): array
    {
        return SportRuleDefinition::where('sport', $sport)
            ->orderBy('sort_order')
            ->get()
            ->all();
    }

    /**
     * Valida los valores de reglas enviados por el usuario contra las definiciones
     * del deporte. Devuelve el array de errores (vacío si todo está OK).
     */
    public function validate(array $values, string $sport): array
    {
        $definitions = SportRuleDefinition::where('sport', $sport)->get()->keyBy('key');
        if ($definitions->isEmpty()) {
            return [];
        }

        $errors = [];
        foreach ($values as $key => $value) {
            $def = $definitions->get($key);
            if ($def === null) {
                $errors[$key] = "Regla desconocida: {$key}";
                continue;
            }

            $value = (string) $value;
            switch ($def->type) {
                case 'boolean':
                    if (!in_array($value, ['0', '1'], true)) {
                        $errors[$key] = 'Debe ser un valor booleano.';
                    }
                    break;
                case 'number':
                    if (!ctype_digit($value)) {
                        $errors[$key] = 'Debe ser un número entero.';
                    } elseif ($def->min !== null && (int) $value < $def->min) {
                        $errors[$key] = "El mínimo es {$def->min}.";
                    } elseif ($def->max !== null && (int) $value > $def->max) {
                        $errors[$key] = "El máximo es {$def->max}.";
                    }
                    break;
                case 'select':
                    $options = $def->options ?? [];
                    if (!in_array($value, $options, true)) {
                        $errors[$key] = 'Valor no válido.';
                    }
                    break;
            }
        }

        return $errors;
    }

    /** Persiste las reglas de un torneo (reemplaza las existentes). */
    public function saveForTournament(Tournament $tournament, array $values): void
    {
        $values = $this->normalize($values);
        $tournament->rules()->delete();

        foreach ($values as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $tournament->rules()->create([
                'rule_key' => $key,
                'value' => $value,
            ]);
        }
    }

    /** Normaliza: conserva solo strings (booleanos como '0'/'1'). */
    private function normalize(array $values): array
    {
        $normalized = [];
        foreach ($values as $key => $value) {
            if (is_bool($value)) {
                $normalized[$key] = $value ? '1' : '0';
            } elseif ($value !== null) {
                $normalized[$key] = (string) $value;
            }
        }
        return $normalized;
    }
}
