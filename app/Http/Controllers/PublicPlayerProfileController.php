<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Tournament;
use App\Services\StandingsService;
use Inertia\Inertia;

class PublicPlayerProfileController extends Controller
{
    /**
     * Perfil público de un jugador dentro de un torneo.
     *
     * Sólo expone datos seguros (nombre de pila, username, equipo y estadísticas
     * de ESE torneo). Nunca email, apellido ni ningún dato de contacto.
     */
    public function show(Tournament $tournament, Player $player)
    {
        // scopeBindings garantiza que $player pertenece a $tournament.
        $tournament->load(['players', 'matches' => function ($q) {
            $q->with(['player1:id,name,username', 'player2:id,name,username'])
                ->orderBy('round')->orderBy('id');
        }]);

        $standings = app(StandingsService::class)->calculate($tournament);

        $position = null;
        $stats = null;
        foreach ($standings as $i => $row) {
            if ($row['competitor_id'] === $player->id) {
                $position = $i + 1;
                $stats = collect($row)->except(['player_id', 'player_name'])->toArray();
                break;
            }
        }

        $matches = $tournament->matches
            ->filter(fn ($m) => $m->status === 'finished'
                && in_array($player->id, [$m->player1_id, $m->player2_id], true))
            ->map(function ($m) use ($player) {
                $isP1 = $m->player1_id === $player->id;
                $opponent = $isP1 ? $m->player2 : $m->player1;
                $mine = $isP1 ? $m->score1 : $m->score2;
                $theirs = $isP1 ? $m->score2 : $m->score1;

                return [
                    'round' => $m->round,
                    'opponent_name' => $opponent?->name,
                    'opponent_username' => $opponent?->username,
                    'gf' => $mine,
                    'gc' => $theirs,
                    'result' => $mine > $theirs ? 'W' : ($mine < $theirs ? 'L' : 'D'),
                ];
            })
            ->values();

        return Inertia::render('Public/PlayerProfile', [
            'tournament' => [
                'name' => $tournament->name,
                'slug' => $tournament->slug,
                'status' => $tournament->status,
            ],
            'player' => [
                'name' => $player->name,
                'username' => $player->username,
                'preferred_team' => $player->preferred_team,
            ],
            'stats' => $stats,
            'position' => $position,
            'totalPlayers' => count($standings),
            'matches' => $matches,
            'seo' => $this->seoFor($tournament, $player, $stats, $position),
        ]);
    }

    private function seoFor(Tournament $tournament, Player $player, ?array $stats, ?int $position): array
    {
        $url = route('players.public.profile', [$tournament, $player]);
        $wins = $stats['pg'] ?? 0;
        $goals = $stats['gf'] ?? 0;
        $posText = $position ? "puesto {$position}" : 'participante';

        return [
            'title' => "{$player->name} (@{$player->username}) en {$tournament->name} | FIFARDOS",
            'description' => "Estadísticas de {$player->name} en el torneo {$tournament->name}: {$posText}, "
                . "{$wins} victorias, {$goals} goles a favor y el historial de sus partidos.",
            'type' => 'profile',
            'canonical' => $url,
            'jsonld' => [
                [
                    '@type' => 'ProfilePage',
                    'url' => $url,
                    'mainEntity' => [
                        '@type' => 'Person',
                        'name' => $player->name,
                        'alternateName' => $player->username,
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => $tournament->name, 'item' => route('tournaments.public.bracket', $tournament)],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $player->name, 'item' => $url],
                    ],
                ],
            ],
        ];
    }
}
