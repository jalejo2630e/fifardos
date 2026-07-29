<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Tournament;

/**
 * Crea torneos (torneo + jugadores + fixture de fase de grupos).
 * Fuente única de verdad usada tanto por el flujo web (TournamentController)
 * como por la API de agentes / MCP (AgentApiController).
 */
class TournamentFactoryService
{
    const COLORS = ['#F97316', '#FFD700', '#FF6B9D', '#10B981', '#8B5CF6', '#00D4FF', '#EF4444', '#84CC16'];

    /**
     * @param  string[]  $playerNames
     */
    public function make(
        int $userId,
        string $name,
        int $consolesCount,
        array $playerNames,
        string $format = 'groups_knockout',
        bool $homeAndAway = false,
    ): Tournament {
        $tournament = Tournament::create([
            'user_id' => $userId,
            'name' => $name,
            'consoles_count' => $consolesCount,
            'status' => 'in_progress',
            'format' => in_array($format, ['groups_knockout', 'league'], true) ? $format : 'groups_knockout',
            'home_and_away' => $homeAndAway,
            'color' => $this->pickColor($userId),
        ]);

        foreach ($playerNames as $playerName) {
            Player::create([
                'tournament_id' => $tournament->id,
                'name' => $playerName,
            ]);
        }

        $this->generateMatches($tournament);

        return $tournament;
    }

    public function pickColor(int $userId): string
    {
        $usedColors = Tournament::where('user_id', $userId)->pluck('color')->toArray();
        $available = array_values(array_diff(self::COLORS, $usedColors));

        return empty($available)
            ? self::COLORS[array_rand(self::COLORS)]
            : $available[array_rand($available)];
    }

    /**
     * Genera el fixture de todos contra todos (round-robin) de la fase de grupos / liga,
     * repartiendo los partidos entre las consolas disponibles. Si el torneo es de ida y
     * vuelta (home_and_away), agrega una segunda rueda con los cruces invertidos.
     */
    public function generateMatches(Tournament $tournament): void
    {
        $players = $tournament->players()->pluck('id', 'name')->toArray();
        $playerNames = array_keys($players);
        $playerIds = array_values($players);

        if (count($playerNames) % 2 !== 0) {
            $playerNames[] = 'BYE';
            $playerIds[] = null;
        }

        $numPlayers = count($playerNames);
        $rounds = $numPlayers - 1;
        $half = $numPlayers / 2;
        $tvCount = max(1, $tournament->consoles_count);

        // 1) Construye el calendario de la primera rueda (método del círculo).
        $schedule = []; // [ [ ['p1'=>id,'p2'=>id], ... ], ... ]
        for ($round = 0; $round < $rounds; $round++) {
            $roundMatches = [];
            for ($i = 0; $i < $half; $i++) {
                $p1Name = $playerNames[$i];
                $p2Name = $playerNames[$numPlayers - 1 - $i];
                $p1Id = $playerIds[$i];
                $p2Id = $playerIds[$numPlayers - 1 - $i];

                if ($p1Name !== 'BYE' && $p2Name !== 'BYE') {
                    $roundMatches[] = ['p1' => $p1Id, 'p2' => $p2Id];
                }
            }
            $schedule[] = $roundMatches;

            $lastName = array_pop($playerNames);
            $lastId = array_pop($playerIds);
            array_splice($playerNames, 1, 0, [$lastName]);
            array_splice($playerIds, 1, 0, [$lastId]);
        }

        // 2) Persiste la(s) rueda(s). En ida y vuelta se duplica invirtiendo local/visitante.
        $legs = $tournament->home_and_away ? 2 : 1;
        $roundNumber = 0;
        for ($leg = 0; $leg < $legs; $leg++) {
            foreach ($schedule as $roundMatches) {
                $roundNumber++;
                $tvIndex = 0;
                foreach ($roundMatches as $matchData) {
                    [$p1, $p2] = $leg === 0
                        ? [$matchData['p1'], $matchData['p2']]
                        : [$matchData['p2'], $matchData['p1']];

                    GameMatch::create([
                        'tournament_id' => $tournament->id,
                        'round' => $roundNumber,
                        'player1_id' => $p1,
                        'player2_id' => $p2,
                        'phase' => 'group',
                        'status' => 'pending',
                        'tv_number' => ($tvIndex % $tvCount) + 1,
                    ]);
                    $tvIndex++;
                }
            }
        }
    }
}
