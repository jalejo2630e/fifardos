<?php

use App\Http\Controllers\FamiliaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TournamentController;
use App\Models\Tournament;
use App\Services\StandingsService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $tournament = Tournament::with('prizes')->latest()->first();
    $standings = [];
    if ($tournament && $tournament->status === 'completed') {
        $tournament->load('matches');
        $standings = app(StandingsService::class)->calculate($tournament);
    }

    $totalPlayers = \App\Models\Player::count();
    $totalMatches = \App\Models\GameMatch::count();
    $totalUsers = \App\Models\User::count();
    $totalVenues = \App\Models\Tournament::sum('consoles_count') ?: 16;

    return Inertia::render('Public/Landing', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'tournament' => $tournament,
        'prizes' => $tournament?->prizes->sortBy('position')->values() ?? [],
        'standings' => $standings,
        'stats' => [
            'teams' => $totalPlayers ?: 48,
            'matches' => $totalMatches ?: 104,
            'venues' => max($totalVenues, 16),
            'fans' => $totalUsers > 1000 ? number_format($totalUsers / 1000, 1) . 'K' : ($totalUsers ?: '1.2M'),
        ],
        'seo' => [
            'title' => 'FIFARDOS — Organiza torneos de fútbol con tus amigos',
            'description' => 'Crea torneos de fútbol en segundos —de videojuego (EA Sports FC/FIFA, eFootball) o de fútbol real (fulbito, F5, F7, F11)—: grupos, resultados, tabla en vivo, eliminatorias automáticas y goleador. Gratis. Con integración MCP para conectar Claude, ChatGPT o Gemini y crear torneos desde el chat.',
            'type' => 'website',
            'jsonld' => [
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => '¿Qué es FIFARDOS?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'FIFARDOS es una plataforma web gratuita para organizar y gestionar torneos de fútbol entre amigos, ya sea de videojuego (EA Sports FC/FIFA, eFootball) o de fútbol real (fulbito, F5, F7, F11). Permite crear torneos con fase de grupos y eliminatorias, cargar los resultados de cada partido, ver la tabla de posiciones en tiempo real y seguir estadísticas como el goleador del torneo.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => '¿Cómo organizo un torneo de fútbol?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Crea una cuenta gratis, pulsa "Nuevo torneo", agrega el nombre del torneo, la cantidad de consolas o canchas y los jugadores. FIFARDOS genera automáticamente el fixture de la fase de grupos (todos contra todos) y, al terminar los grupos, arma las eliminatorias con los mejores clasificados.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => '¿FIFARDOS es gratis?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Sí. Crear y gestionar torneos de fútbol en FIFARDOS es completamente gratis. No necesitas instalar nada: funciona desde el navegador en computador y móvil.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => '¿Puedo pedirle a un asistente de IA que me arme el torneo?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Sí. FIFARDOS ofrece un servidor MCP y una API para agentes, de modo que asistentes como Claude, ChatGPT o GitHub Copilot pueden consultar torneos, tablas de posiciones y goleadores, e incluso crear un torneo nuevo por ti.',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);
});

Route::get('/inscribirse', [App\Http\Controllers\PublicPlayerController::class, 'create'])->name('players.public.create');
Route::post('/players/register', [App\Http\Controllers\PublicPlayerController::class, 'store'])->name('players.public.store');

Route::get('/rules', function () {
    $tournament = App\Models\Tournament::with('prizes')->latest()->first();
    return Inertia::render('Public/Rules', [
        'prizes' => $tournament?->prizes->sortBy('position')->values() ?? [],
        'seo' => [
            'title' => 'Reglas y premios de los torneos | FIFARDOS',
            'description' => 'Consulta el reglamento de los torneos de FIFARDOS: formato de grupos y eliminatorias, '
                . 'sistema de puntos, desempates, premios y todo lo que necesitas saber para competir.',
            'type' => 'article',
        ],
    ]);
})->name('rules');

// Redirige 301 las URLs antiguas por id (ya indexadas) a la URL canónica con slug.
Route::get('/torneos/{tournamentId}/bracket', function (string $tournamentId) {
    $tournament = Tournament::findOrFail((int) $tournamentId);
    return redirect()->route('tournaments.public.bracket', $tournament, 301);
})->whereNumber('tournamentId')->name('tournaments.public.bracket.legacy');

Route::get('/torneos/{tournament:slug}/bracket', [App\Http\Controllers\PublicBracketController::class, 'show'])->name('tournaments.public.bracket');

Route::get('/torneos/{tournament:slug}/jugador/{player:username}', [App\Http\Controllers\PublicPlayerProfileController::class, 'show'])
    ->scopeBindings()
    ->name('players.public.profile');

// --- Módulo Familia: minijuegos en tiempo real (público, sin cuenta) ---
Route::prefix('familia')->group(function () {
    Route::get('/', [FamiliaController::class, 'index'])->name('familia.index');
    Route::post('/', [FamiliaController::class, 'create'])->name('familia.create');
    Route::get('/{code}', [FamiliaController::class, 'room'])->whereAlphaNumeric('code')->name('familia.room');
    Route::post('/{code}/join', [FamiliaController::class, 'join'])->whereAlphaNumeric('code')->name('familia.join');
    Route::post('/{code}/game', [FamiliaController::class, 'setGame'])->whereAlphaNumeric('code');
    Route::post('/{code}/playlist', [FamiliaController::class, 'setPlaylist'])->whereAlphaNumeric('code');
    Route::post('/{code}/hello', [FamiliaController::class, 'hello'])->whereAlphaNumeric('code');
    Route::get('/{code}/me', [FamiliaController::class, 'me'])->whereAlphaNumeric('code');
    Route::get('/{code}/word', [FamiliaController::class, 'word'])->whereAlphaNumeric('code');
    Route::post('/{code}/start', [FamiliaController::class, 'start'])->whereAlphaNumeric('code');
    Route::post('/{code}/stroke', [FamiliaController::class, 'stroke'])->whereAlphaNumeric('code');
    Route::post('/{code}/clear', [FamiliaController::class, 'clearCanvas'])->whereAlphaNumeric('code');
    Route::post('/{code}/guess', [FamiliaController::class, 'guess'])->whereAlphaNumeric('code');
    Route::post('/{code}/answer', [FamiliaController::class, 'answer'])->whereAlphaNumeric('code');
    Route::post('/{code}/submit', [FamiliaController::class, 'submit'])->whereAlphaNumeric('code');
    Route::post('/{code}/stop', [FamiliaController::class, 'stop'])->whereAlphaNumeric('code');
    Route::post('/{code}/vote', [FamiliaController::class, 'vote'])->whereAlphaNumeric('code');
    Route::post('/{code}/letter', [FamiliaController::class, 'letter'])->whereAlphaNumeric('code');
    Route::post('/{code}/solve', [FamiliaController::class, 'solve'])->whereAlphaNumeric('code');
    Route::post('/{code}/timeout', [FamiliaController::class, 'timeout'])->whereAlphaNumeric('code');
    Route::post('/{code}/leave', [FamiliaController::class, 'leave'])->whereAlphaNumeric('code');
});

Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily'],
        ['loc' => url('/rules'), 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['loc' => url('/inscribirse'), 'priority' => '0.7', 'changefreq' => 'weekly'],
    ];

    foreach (Tournament::with(['players:id,tournament_id,username'])->orderBy('updated_at', 'desc')->get() as $t) {
        $urls[] = [
            'loc' => route('tournaments.public.bracket', $t),
            'lastmod' => optional($t->updated_at)->toAtomString(),
            'priority' => '0.8',
            'changefreq' => 'daily',
        ];

        foreach ($t->players as $player) {
            if (blank($player->username)) {
                continue;
            }
            $urls[] = [
                'loc' => route('players.public.profile', [$t, $player]),
                'lastmod' => optional($t->updated_at)->toAtomString(),
                'priority' => '0.5',
                'changefreq' => 'weekly',
            ];
        }
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= "  <url>\n    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
        if (!empty($u['lastmod'])) $xml .= "    <lastmod>{$u['lastmod']}</lastmod>\n";
        $xml .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
        $xml .= "    <priority>{$u['priority']}</priority>\n  </url>\n";
    }
    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::middleware(['auth', 'verified'])->group(function () {
    // El dashboard fue retirado: al entrar se va directo a Torneos.
    // Se mantiene el nombre 'dashboard' para que los redirects de auth sigan funcionando.
    Route::redirect('/dashboard', '/tournaments')->name('dashboard');

    Route::get('/analitica', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics');

    Route::get('/jugadores/{player}', [App\Http\Controllers\PlayerController::class, 'show'])->name('players.show');

    Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
    Route::get('/tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
    Route::post('/tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
    Route::get('/tournaments/{tournament}/partidos/{match}', [App\Http\Controllers\MatchController::class, 'show'])->name('matches.show');
    Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy'])->name('tournaments.destroy');

    Route::post('/tournaments/{tournament}/matches/{match}/score', [TournamentController::class, 'updateScore'])->name('matches.score.update');
    Route::post('/tournaments/{tournament}/matches/{match}/edit-score', [TournamentController::class, 'editScore'])->name('matches.score.edit');
    Route::post('/tournaments/{tournament}/generate-knockout', [TournamentController::class, 'generateKnockout'])->name('tournaments.generate-knockout');
    Route::post('/tournaments/{tournament}/players/{player}/replace', [TournamentController::class, 'replacePlayer'])->name('tournaments.players.replace');

    Route::get('/tournaments/{tournament}/prizes', [App\Http\Controllers\PrizeController::class, 'index'])->name('prizes.index');
    Route::post('/tournaments/{tournament}/prizes', [App\Http\Controllers\PrizeController::class, 'store'])->name('prizes.store');
    Route::put('/tournaments/{tournament}/prizes/{prize}', [App\Http\Controllers\PrizeController::class, 'update'])->name('prizes.update');
    Route::delete('/tournaments/{tournament}/prizes/{prize}', [App\Http\Controllers\PrizeController::class, 'destroy'])->name('prizes.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/profile/seguridad', [App\Http\Controllers\SecurityQuestionController::class, 'profileForm'])->name('security-questions.profile.form');
    Route::put('/profile/seguridad', [App\Http\Controllers\SecurityQuestionController::class, 'profileUpdate'])->name('security-questions.profile.update');

    Route::get('/seguridad/configurar', [App\Http\Controllers\SecurityQuestionController::class, 'setupForm'])->name('security-questions.setup.form');
    Route::post('/seguridad/configurar', [App\Http\Controllers\SecurityQuestionController::class, 'setupStore'])->name('security-questions.setup.store');

    // --- Administración (requiere is_admin, vía middleware 'admin') ---
    Route::middleware('admin')->group(function () {
        Route::get('/admin/como-usar', fn () => \Inertia\Inertia::render('Admin/ComoUsar'))->name('admin.como-usar');

        Route::get('/admin/reportes', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('admin.reportes');

        Route::get('/admin/usuarios', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.usuarios');
        Route::post('/admin/usuarios/{user}/toggle-admin', [App\Http\Controllers\Admin\UserController::class, 'toggleAdmin'])->name('admin.usuarios.toggle-admin');

        Route::get('/admin/chat-config', [App\Http\Controllers\Admin\ChatConfigController::class, 'edit'])->name('admin.chat-config.edit');
        Route::put('/admin/chat-config', [App\Http\Controllers\Admin\ChatConfigController::class, 'update'])->name('admin.chat-config.update');
    });

    // API docs + creación de token — disponible para CUALQUIER usuario (para conectar por MCP)
    Route::get('/dashboard/api-docs', fn () => app(App\Http\Controllers\ApiDocsController::class)->index())->name('dashboard.api-docs');

    Route::prefix('api-tokens')->group(function () {
        Route::get('/', [App\Http\Controllers\ApiTokenController::class, 'index'])->name('api-tokens.index');
        Route::post('/', [App\Http\Controllers\ApiTokenController::class, 'store'])->name('api-tokens.store');
        Route::delete('/{tokenId}', [App\Http\Controllers\ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
    });
});

Route::post('/chat', App\Http\Controllers\GeminiChatController::class)
    ->middleware('throttle:20,1')
    ->name('chat');

require __DIR__ . '/auth.php';
