<?php

namespace App\Services;

use App\Models\Tournament;

class StandingsService
{
    public function calculate(Tournament $tournament): array
    {
        $standings = [];
        foreach ($tournament->players as $player) {
            $standings[$player->id] = [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'username' => $player->username,
                'pts' => 0, 'pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0,
                'gf' => 0, 'gc' => 0, 'dg' => 0,
            ];
        }

        foreach ($tournament->matches as $match) {
            if ($match->status !== 'finished') continue;
            if (!isset($standings[$match->player1_id], $standings[$match->player2_id])) continue;

            $s1 = &$standings[$match->player1_id];
            $s2 = &$standings[$match->player2_id];

            $s1['pj']++; $s2['pj']++;
            $s1['gf'] += $match->score1; $s2['gf'] += $match->score2;
            $s1['gc'] += $match->score2; $s2['gc'] += $match->score1;

            if ($match->score1 > $match->score2) {
                $s1['pg']++; $s1['pts'] += 3;
                $s2['pp']++;
            } elseif ($match->score1 < $match->score2) {
                $s2['pg']++; $s2['pts'] += 3;
                $s1['pp']++;
            } else {
                $s1['pe']++; $s1['pts'] += 1;
                $s2['pe']++; $s2['pts'] += 1;
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

        return $standings;
    }
}
