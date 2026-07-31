<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\StandingsService;
use Inertia\Inertia;

class PublicBracketController extends Controller
{
    public function show(Tournament $tournament)
    {
        $tournament->load(['players', 'teams.players', 'matches' => function ($q) {
            $q->with(['player1', 'player2', 'team1', 'team2'])->orderBy('round')->orderBy('id');
        }]);

        // Página pública: nunca exponer datos personales de los jugadores en los
        // props JSON (email, apellido, psn_id). Sólo nombre de pila, username y equipo.
        $safe = ['email', 'apellido', 'psn_id', 'password'];
        $tournament->players->each->makeHidden($safe);
        $tournament->teams->each->players->each->makeHidden($safe);
        $tournament->matches->each(function ($m) use ($safe) {
            $m->player1?->makeHidden($safe);
            $m->player2?->makeHidden($safe);
        });

        $standings = app(StandingsService::class)->calculate($tournament);
        $rounds = $tournament->matches->groupBy('round')->values();

        return Inertia::render('Public/Bracket', [
            'tournament' => $tournament,
            'sport' => \App\Services\SportsCatalog::get($tournament->sport ?? 'fifa'),
            'standings' => $standings,
            'rounds' => $rounds,
            'seo' => $this->seoFor($tournament),
        ]);
    }

    /**
     * Metadatos SEO + JSON-LD específicos del torneo (title/description propios,
     * SportsEvent y BreadcrumbList) para que cada bracket sea una página indexable
     * con contenido único, no la portada por defecto.
     */
    private function seoFor(Tournament $tournament): array
    {
        $url = route('tournaments.public.bracket', $tournament);
        $players = $tournament->players->count();
        $statusLabel = match ($tournament->status) {
            'in_progress' => 'en vivo',
            'finished', 'completed' => 'resultados y campeón',
            default => 'próximamente',
        };
        $sportKey = $tournament->sport ?? 'fifa';
        $sportName = \App\Services\SportsCatalog::name($sportKey);
        $isPhysical = $tournament->isPhysical();
        $attendanceMode = $isPhysical
            ? 'https://schema.org/OfflineEventAttendanceMode'
            : 'https://schema.org/OnlineEventAttendanceMode';
        $location = $isPhysical
            ? ['@type' => 'Place', 'name' => ucfirst($tournament->venueLabel())]
            : ['@type' => 'VirtualLocation', 'url' => $url];

        return [
            'title' => "{$tournament->name} — Bracket, tabla y {$statusLabel} | FIFARDOS",
            'description' => "Torneo de {$sportName} «{$tournament->name}» en FIFARDOS: tabla de posiciones "
                . 'en vivo, resultados de cada partido, eliminatorias y líder del torneo.',
            'type' => 'article',
            'canonical' => $url,
            'jsonld' => [
                array_filter([
                    '@type' => 'SportsEvent',
                    'name' => $tournament->name,
                    'url' => $url,
                    'sport' => $sportName,
                    'eventAttendanceMode' => $attendanceMode,
                    'eventStatus' => 'https://schema.org/EventScheduled',
                    'startDate' => optional($tournament->created_at)->toIso8601String(),
                    'endDate' => optional($tournament->finished_at)->toIso8601String(),
                    'organizer' => ['@id' => url('/#organization')],
                    'location' => $location,
                ]),
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => $tournament->name, 'item' => $url],
                    ],
                ],
            ],
        ];
    }

}
