<?php

namespace App\Services;

use App\Models\Tournament;

class StandingsService
{
    /**
     * Calcula la tabla de posiciones de un torneo.
     *
     * Competidores: en deportes de equipo se ordenan por equipo; en deportes
     * individuales por jugador. El puntaje respeta las reglas del deporte
     * (win/draw/loss configurable) y el marcador se interpreta según el
     * deporte (goles, puntos o sets).
     */
    public function calculate(Tournament $tournament): array
    {
        $sport = $tournament->sport ?? 'fifa';
        $isTeam = SportsCatalog::isTeam($sport);
        $winPts = SportsCatalog::winPoints($sport);
        $drawPts = SportsCatalog::drawPoints($sport);

        $competitors = $isTeam ? $tournament->teams : $tournament->players;
        $standings = [];
        foreach ($competitors as $c) {
            $standings[$c->id] = [
                'competitor_id' => $c->id,
                'competitor_name' => $c->name,
                'type' => $isTeam ? 'team' : 'player',
                'pts' => 0, 'pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0,
                'gf' => 0, 'gc' => 0, 'dg' => 0,
            ];
        }

        $tournament->loadMissing('matches');
        foreach ($tournament->matches as $match) {
            if ($match->status !== 'finished') continue;

            $c1 = $isTeam ? $match->team1_id : $match->player1_id;
            $c2 = $isTeam ? $match->team2_id : $match->player2_id;
            if (!isset($standings[$c1], $standings[$c2])) continue;

            $s1 = &$standings[$c1];
            $s2 = &$standings[$c2];

            $s1['pj']++; $s2['pj']++;
            $s1['gf'] += (int) $match->score1; $s2['gf'] += (int) $match->score2;
            $s1['gc'] += (int) $match->score2; $s2['gc'] += (int) $match->score1;

            $outcome = SportsCatalog::matchOutcome(
                $sport,
                $match->score1,
                $match->score2,
                $match->penalties1,
                $match->penalties2,
            );

            if ($outcome === 1) {
                $s1['pg']++; $s1['pts'] += $winPts;
                $s2['pp']++;
            } elseif ($outcome === 2) {
                $s2['pg']++; $s2['pts'] += $winPts;
                $s1['pp']++;
            } else {
                $s1['pe']++; $s1['pts'] += $drawPts;
                $s2['pe']++; $s2['pts'] += $drawPts;
            }

            $s1['dg'] = $s1['gf'] - $s1['gc'];
            $s2['dg'] = $s2['gf'] - $s2['gc'];
            unset($s1, $s2);
        }

        usort($standings, function ($a, $b) {
            if ($b['pts'] !== $a['pts']) return $b['pts'] - $a['pts'];
            if ($b['dg'] !== $a['dg']) return $b['dg'] - $a['dg'];
            return $b['gf'] - $a['gf'];
        });

        // Aliases para compatibilidad con consumidores existentes.
        return array_map(function ($row) use ($isTeam) {
            if ($isTeam) {
                $row['team_id'] = $row['competitor_id'];
                $row['team_name'] = $row['competitor_name'];
            } else {
                $row['player_id'] = $row['competitor_id'];
                $row['player_name'] = $row['competitor_name'];
            }
            return $row;
        }, array_values($standings));
    }
}
