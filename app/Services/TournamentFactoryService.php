<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;

/**
 * Crea torneos (torneo + competidores + fixture de fase de grupos).
 * Fuente única de verdad usada tanto por el flujo web (TournamentController)
 * como por la API de agentes / MCP (AgentApiController).
 *
 * Soporta deportes individuales (jugadores como competidores) y deportes de
 * equipo (equipos con jugadores como competidores).
 */
class TournamentFactoryService
{
    const COLORS = ['#F97316', '#FFD700', '#FF6B9D', '#10B981', '#8B5CF6', '#00D4FF', '#EF4444', '#84CC16'];

    /**
     * Crea un torneo de deporte individual.
     *
     * @param  string[]  $playerNames
     */
    public function make(
        int $userId,
        string $name,
        int $venuesCount,
        array $playerNames,
        string $format = 'groups_knockout',
        bool $homeAndAway = false,
        string $sport = 'fifa',
    ): Tournament {
        $tournament = $this->createTournament(
            $userId, $name, $venuesCount, $sport, $format, $homeAndAway,
        );

        foreach ($playerNames as $playerName) {
            Player::create([
                'tournament_id' => $tournament->id,
                'name' => $playerName,
            ]);
        }

        $this->generateMatches($tournament);

        return $tournament;
    }

    /**
     * Crea un torneo de deporte de equipo.
     *
     * @param  array<int, array{name: string, players?: string[]}>  $teams  nombre + integrantes opcionales
     */
    public function makeTeamTournament(
        int $userId,
        string $name,
        int $venuesCount,
        string $sport,
        array $teams,
        string $format = 'groups_knockout',
        bool $homeAndAway = false,
    ): Tournament {
        $tournament = $this->createTournament(
            $userId, $name, $venuesCount, $sport, $format, $homeAndAway,
        );

        foreach ($teams as $teamData) {
            $team = Team::create([
                'tournament_id' => $tournament->id,
                'name' => $teamData['name'],
                'color' => $this->pickColor($userId),
            ]);
            foreach ($teamData['players'] ?? [] as $memberName) {
                Player::create([
                    'tournament_id' => $tournament->id,
                    'team_id' => $team->id,
                    'name' => $memberName,
                ]);
            }
        }

        $this->generateMatches($tournament);

        return $tournament;
    }

    private function createTournament(
        int $userId,
        string $name,
        int $venuesCount,
        string $sport,
        string $format,
        bool $homeAndAway,
    ): Tournament {
        if (!in_array($sport, SportsCatalog::keys(), true)) {
            $sport = 'fifa';
        }

        return Tournament::create([
            'user_id' => $userId,
            'name' => $name,
            'sport' => $sport,
            'consoles_count' => max(1, $venuesCount),
            'status' => 'in_progress',
            'format' => in_array($format, ['groups_knockout', 'league'], true) ? $format : 'groups_knockout',
            'home_and_away' => $homeAndAway,
            'color' => $this->pickColor($userId),
        ]);
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
     * repartiendo los partidos entre los espacios disponibles (canchas/consolas). Si el
     * torneo es de ida y vuelta (home_and_away), agrega una segunda rueda con los cruces
     * invertidos. Funciona sobre competidores (equipos o jugadores según el deporte).
     */
    public function generateMatches(Tournament $tournament): void
    {
        $isTeam = $tournament->isTeamSport();
        $competitors = $isTeam
            ? $tournament->teams()->orderBy('id')->get()
            : $tournament->players()->orderBy('id')->get();

        $names = $competitors->pluck('name')->map(fn ($n) => (string) $n)->values()->toArray();
        $ids = $competitors->pluck('id')->map(fn ($i) => (int) $i)->values()->toArray();

        if (count($names) < 2) {
            return;
        }

        if (count($names) % 2 !== 0) {
            $names[] = 'BYE';
            $ids[] = null;
        }

        $numPlayers = count($names);
        $rounds = $numPlayers - 1;
        $half = $numPlayers / 2;
        $tvCount = max(1, $tournament->consoles_count);

        // 1) Construye el calendario de la primera rueda (método del círculo).
        $schedule = [];
        for ($round = 0; $round < $rounds; $round++) {
            $roundMatches = [];
            for ($i = 0; $i < $half; $i++) {
                $p1Name = $names[$i];
                $p2Name = $names[$numPlayers - 1 - $i];
                $p1Id = $ids[$i];
                $p2Id = $ids[$numPlayers - 1 - $i];

                if ($p1Name !== 'BYE' && $p2Name !== 'BYE') {
                    $roundMatches[] = ['p1' => $p1Id, 'p2' => $p2Id];
                }
            }
            $schedule[] = $roundMatches;

            $lastName = array_pop($names);
            $lastId = array_pop($ids);
            array_splice($names, 1, 0, [$lastName]);
            array_splice($ids, 1, 0, [$lastId]);
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

                    $match = [
                        'tournament_id' => $tournament->id,
                        'round' => $roundNumber,
                        'phase' => 'group',
                        'status' => 'pending',
                        'tv_number' => ($tvIndex % $tvCount) + 1,
                    ];

                    if ($isTeam) {
                        $match['team1_id'] = $p1;
                        $match['team2_id'] = $p2;
                    } else {
                        $match['player1_id'] = $p1;
                        $match['player2_id'] = $p2;
                    }

                    GameMatch::create($match);
                    $tvIndex++;
                }
            }
        }
    }
}
