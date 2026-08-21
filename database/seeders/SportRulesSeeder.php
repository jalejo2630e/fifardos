<?php

namespace Database\Seeders;

use App\Models\SportRuleDefinition;
use Illuminate\Database\Seeder;

/**
 * Reglas parametrizables por deporte (definiciones). Cada fila describe un campo
 * del formulario dinámico de reglas del torneo: clave, etiqueta (es/en), tipo
 * (boolean | number | select), valor por defecto, opciones y límites.
 *
 * Siguiendo el spec (reglas_deportes_spec.md), agregar un deporte nuevo o una
 * regla solo requiere nuevas filas aquí; el frontend y la validación se adaptan solos.
 */
class SportRulesSeeder extends Seeder
{
    public function run(): void
    {
        SportRuleDefinition::query()->delete();

        foreach ($this->data() as $sport => $rules) {
            foreach ($rules as $i => $rule) {
                SportRuleDefinition::create(array_merge([
                    'sport' => $sport,
                    'sort_order' => $i,
                ], $rule));
            }
        }
    }

    /** Construye una regla de tipo boolean. */
    private static function b(string $key, string $label, string $labelEn, bool $default, string $group = 'general', ?string $note = null, ?string $noteEn = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'label_en' => $labelEn,
            'group' => $group,
            'note' => $note,
            'note_en' => $noteEn,
            'type' => 'boolean',
            'default' => $default ? '1' : '0',
        ];
    }

    /** Construye una regla de tipo number. */
    private static function n(string $key, string $label, string $labelEn, int $default, int $min, int $max, string $group = 'general', ?string $note = null, ?string $noteEn = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'label_en' => $labelEn,
            'group' => $group,
            'min' => $min,
            'max' => $max,
            'note' => $note,
            'note_en' => $noteEn,
            'type' => 'number',
            'default' => (string) $default,
        ];
    }

    /** Construye una regla de tipo select. */
    private static function s(string $key, string $label, string $labelEn, string $default, array $options, string $group = 'general', ?string $note = null, ?string $noteEn = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'label_en' => $labelEn,
            'group' => $group,
            'options' => $options,
            'note' => $note,
            'note_en' => $noteEn,
            'type' => 'select',
            'default' => $default,
        ];
    }

    public static function data(): array
    {
        $g = 'general';
        $d = 'desempate';
        $m = 'marcador';

        return [
            'fifa' => [
                self::b('alargue_en_eliminatorias', 'Alargue en eliminatorias', 'Extra time in knockouts', true, $g, 'Tiempo extra si hay empate', 'Extra time if tied'),
                self::b('penales_desempate', 'Penales en eliminatorias', 'Penalties in knockouts', true, $d, 'Definición por penales', 'Penalty shootout'),
                self::s('dificultad', 'Dificultad', 'Difficulty', 'amateur', ['amateur', 'semi_pro', 'pro', 'world_class', 'legendary'], $g, 'Nivel de la CPU', 'CPU level'),
            ],
            'soccer' => [
                self::b('definicion_penales', 'Definición por penales', 'Penalty shootout', true, $d),
                self::b('fuera_de_juego', 'Fuera de juego', 'Offside', true, $g),
                self::s('cambios_permitidos', 'Cambios permitidos', 'Allowed substitutions', '5', ['ilimitado', '3', '5', '7'], $g),
                self::b('cambios_pueden_reingresar', 'Los cambios pueden reingresar', 'Subs can re-enter', true, $g),
                self::b('tarjeta_roja_repone_jugador', 'Roja repone jugador', 'Red card allows replacement', false, $g),
            ],
            'football7' => [
                self::b('definicion_penales', 'Definición por penales', 'Penalty shootout', true, $d),
                self::b('fuera_de_juego', 'Fuera de juego', 'Offside', false, $g),
                self::s('cambios_permitidos', 'Cambios permitidos', 'Allowed substitutions', 'ilimitado', ['ilimitado', '3', '5', '7'], $g),
                self::b('cambios_pueden_reingresar', 'Los cambios pueden reingresar', 'Subs can re-enter', true, $g),
                self::b('arquero_de_linea_permitido', 'Arquero de línea permitido', 'Flying goalkeeper allowed', false, $g),
            ],
            'football8' => [
                self::b('definicion_penales', 'Definición por penales', 'Penalty shootout', true, $d),
                self::b('fuera_de_juego', 'Fuera de juego', 'Offside', false, $g),
                self::s('cambios_permitidos', 'Cambios permitidos', 'Allowed substitutions', 'ilimitado', ['ilimitado', '3', '5', '7'], $g),
                self::b('cambios_pueden_reingresar', 'Los cambios pueden reingresar', 'Subs can re-enter', true, $g),
                self::b('arquero_de_linea_permitido', 'Arquero de línea permitido', 'Flying goalkeeper allowed', false, $g),
            ],
            'football6' => [
                self::b('definicion_penales', 'Definición por penales', 'Penalty shootout', true, $d),
                self::b('fuera_de_juego', 'Fuera de juego', 'Offside', false, $g),
                self::s('cambios_permitidos', 'Cambios permitidos', 'Allowed substitutions', 'ilimitado', ['ilimitado', '3', '5', '7'], $g),
                self::b('cambios_pueden_reingresar', 'Los cambios pueden reingresar', 'Subs can re-enter', true, $g),
                self::b('arquero_de_linea_permitido', 'Arquero de línea permitido', 'Flying goalkeeper allowed', false, $g),
            ],
            'futsal' => [
                self::b('definicion_penales', 'Definición por penales', 'Penalty shootout', true, $d),
                self::b('fuera_de_juego', 'Fuera de juego', 'Offside', false, $g),
                self::s('cambios_permitidos', 'Cambios permitidos', 'Allowed substitutions', 'ilimitado', ['ilimitado', '3', '5', '7'], $g),
                self::b('cambios_pueden_reingresar', 'Los cambios pueden reingresar', 'Subs can re-enter', true, $g),
                self::b('arquero_de_linea_permitido', 'Arquero de línea permitido', 'Flying goalkeeper allowed', false, $g),
            ],
            'basketball' => [
                self::n('faltas_para_bonus', 'Faltas para bonus', 'Fouls to bonus', 5, 1, 10, $g),
                self::n('faltas_para_expulsion', 'Faltas para expulsión', 'Fouls to disqualification', 5, 1, 10, $g),
            ],
            'basketball3' => [
                self::n('puntos_para_ganar', 'Puntos para ganar', 'Points to win', 21, 11, 30, $m),
                self::n('valor_tiro_dentro_arco', 'Valor tiro dentro del arco', 'Inside arc shot value', 1, 1, 2, $m),
                self::n('valor_tiro_fuera_arco', 'Valor tiro fuera del arco', 'Outside arc shot value', 2, 2, 3, $m),
            ],
            'volleyball' => [
                self::s('sets_para_ganar_partido', 'Sets para ganar el partido', 'Sets to win the match', '3', ['2', '3'], 'sets'),
                self::n('puntos_por_set', 'Puntos por set', 'Points per set', 25, 15, 25, 'sets'),
                self::n('puntos_set_desempate', 'Puntos del set decisivo', 'Deciding set points', 15, 10, 25, $d),
                self::n('diferencia_minima', 'Diferencia mínima', 'Minimum lead', 2, 2, 5, 'sets'),
                self::b('libero_habilitado', 'Líbero habilitado', 'Libero allowed', true, $g),
                self::b('rotacion_obligatoria', 'Rotación obligatoria', 'Mandatory rotation', true, $g),
            ],
            'tennis' => [
                self::s('sets_para_ganar', 'Sets para ganar', 'Sets to win', '2', ['2', '3'], 'sets'),
                self::n('juegos_por_set', 'Juegos por set', 'Games per set', 6, 4, 6, 'sets'),
                self::n('diferencia_minima_juegos', 'Diferencia mínima de juegos', 'Minimum games lead', 2, 2, 2, 'sets'),
                self::n('tie_break_a', 'Tie-break a', 'Tie-break up to', 7, 6, 10, $d),
                self::b('no_ad_scoring', 'Sistema No-Ad', 'No-Ad scoring', false, $m),
                self::b('set_decisivo_es_match_tiebreak', 'Set decisivo es match tie-break', 'Deciding set is match tie-break', false, $d),
            ],
            'padel' => [
                self::s('sets_para_ganar', 'Sets para ganar', 'Sets to win', '2', ['2', '3'], 'sets'),
                self::n('juegos_por_set', 'Juegos por set', 'Games per set', 6, 4, 6, 'sets'),
                self::n('diferencia_minima_juegos', 'Diferencia mínima de juegos', 'Minimum games lead', 2, 2, 2, 'sets'),
                self::n('tie_break_a', 'Tie-break a', 'Tie-break up to', 7, 6, 10, $d),
                self::b('no_ad_scoring', 'Sistema No-Ad', 'No-Ad scoring', false, $m),
                self::b('golden_point', 'Golden point', 'Golden point', false, $d, 'Un solo punto decisivo por juego', 'Single deciding point per game'),
            ],
            'table_tennis' => [
                self::s('sets_para_ganar', 'Sets para ganar', 'Sets to win', '3', ['2', '3', '4'], 'sets'),
                self::n('puntos_por_set', 'Puntos por set', 'Points per set', 11, 11, 11, 'sets'),
                self::n('diferencia_minima', 'Diferencia mínima', 'Minimum lead', 2, 2, 2, 'sets'),
                self::n('cambio_saque_cada', 'Cambio de saque cada', 'Service change every', 2, 1, 5, $g),
            ],
            'pickleball' => [
                self::s('puntos_para_ganar', 'Puntos para ganar', 'Points to win', '11', ['11', '15', '21'], $m),
                self::n('diferencia_minima', 'Diferencia mínima', 'Minimum lead', 2, 2, 2, $m),
                self::s('sistema_de_punto', 'Sistema de punto', 'Scoring system', 'solo_saque_anota', ['solo_saque_anota', 'rally_point'], $m, 'El punto solo se suma al servir', 'Point only scored by serving team'),
                self::b('regla_doble_pique', 'Regla del doble pique', 'Double bounce rule', true, $g),
            ],
            'handball' => [
                self::b('definicion_penales_7m', 'Definición por penales 7m', '7m penalty shootout', true, $d),
                self::n('exclusion_temporal_min', 'Exclusión temporal (min)', 'Temporary exclusion (min)', 2, 1, 2, $g),
            ],
            'rugby' => [
                self::n('valor_try', 'Valor del try', 'Try value', 5, 5, 5, $m),
                self::n('valor_conversion', 'Valor de la conversión', 'Conversion value', 2, 2, 2, $m),
                self::n('valor_penal', 'Valor del penal', 'Penalty value', 3, 3, 3, $m),
                self::n('valor_drop', 'Valor del drop', 'Drop goal value', 3, 3, 3, $m),
                self::b('bono_por_tries', 'Bono por 4 tries', 'Try bonus (4 tries)', false, $m),
                self::b('bono_perdedor_cerca', 'Bono al perdedor por 7 o menos', 'Losing bonus (7 or less)', false, $m),
            ],
            'nba2k' => [
                self::n('cantidad_cuartos', 'Cantidad de cuartos', 'Number of quarters', 4, 2, 4, $g),
                self::s('reloj_posesion_seg', 'Reloj de posesión (seg)', 'Shot clock (sec)', '24', ['14', '24', 'sin_reloj'], $g),
                self::n('faltas_para_bonus', 'Faltas para bonus', 'Fouls to bonus', 5, 1, 10, $g),
                self::n('faltas_para_expulsion', 'Faltas para expulsión', 'Fouls to disqualification', 6, 1, 10, $g),
            ],
            'fighting' => [
                self::n('cantidad_rounds', 'Cantidad de rounds', 'Number of rounds', 3, 1, 5, $g),
                self::b('knockout_termina_round', 'Knockout termina el round', 'Knockout ends the round', true, $m, 'Si hay KO, termina el round inmediatamente', 'If KO, round ends immediately'),
                self::s('sistema_puntuacion', 'Sistema de puntuación', 'Scoring system', 'judge', ['judge', 'knockout_only', 'mixed'], $g, 'Judge = puntos por rondas, Mixed = ambas formas', 'Judge = points per round, Mixed = both methods'),
            ],
            'tennis_v' => [
                self::s('sets_para_ganar', 'Sets para ganar', 'Sets to win', '2', ['2', '3'], 'sets'),
                self::n('juegos_por_set', 'Juegos por set', 'Games per set', 6, 4, 6, 'sets'),
                self::n('diferencia_minima_juegos', 'Diferencia mínima de juegos', 'Minimum games lead', 2, 2, 2, 'sets'),
                self::n('tie_break_a', 'Tie-break a', 'Tie-break up to', 7, 6, 10, $d),
                self::b('no_ad_scoring', 'Sistema No-Ad', 'No-Ad scoring', false, $m),
                self::b('set_decisivo_es_match_tiebreak', 'Set decisivo es match tie-break', 'Deciding set is match tie-break', false, $d),
            ],
            'volleyball_v' => [
                self::s('sets_para_ganar_partido', 'Sets para ganar el partido', 'Sets to win the match', '3', ['2', '3'], 'sets'),
                self::n('puntos_por_set', 'Puntos por set', 'Points per set', 25, 15, 25, 'sets'),
                self::n('puntos_set_desempate', 'Puntos del set decisivo', 'Deciding set points', 15, 10, 25, $d),
                self::n('diferencia_minima', 'Diferencia mínima', 'Minimum lead', 2, 2, 5, 'sets'),
                self::b('libero_habilitado', 'Líbero habilitado', 'Libero allowed', true, $g),
                self::b('rotacion_obligatoria', 'Rotación obligatoria', 'Mandatory rotation', true, $g),
            ],
        ];
    }
}
