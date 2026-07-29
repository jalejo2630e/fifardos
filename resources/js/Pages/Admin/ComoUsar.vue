<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n({
    useScope: 'local',
    messages: {
        es: {
            headTitle: 'Admin · Cómo usar FIFARDOS',
            breadcrumbDocs: 'Documentación',
            backToPanel: '← Volver al panel',
            badgeDocs: 'Documentación Admin',
            heroTitle: 'Cómo usar FIFARDOS',
            heroSubtitle: 'Guía completa del panel de administración: torneos, jugadores, partidos, y la API REST para integración con agentes externos.',
            quickNav: 'Navegación rápida',
            navDashboard: 'Dashboard',
            navTorneos: 'Torneos',
            navJugadores: 'Jugadores y Partidos',
            navApi: 'API para Agentes',
            navAuth: 'Autenticación',
            dashboardTitle: 'Dashboard',
            whereLabel: 'Dónde:',
            dashboardWhere: 'Menú principal → Dashboard',
            dashboardDesc: 'Panel con resumen de torneos activos, jugadores registrados, partidos jugados y pendientes. Desde aquí puedes acceder rápidamente a cualquier torneo o crear uno nuevo.',
            torneosTitle: 'Torneos',
            torneosWhere: 'Menú → Torneos',
            torneosStep1Prefix: 'Haz clic en',
            torneosStep1Btn: '"+ Crear Torneo"',
            torneosStep1Suffix: 'desde el Dashboard o la navegación.',
            torneosStep2: 'Asigna un nombre, color y número de consolas (TVs) disponibles.',
            torneosStep3Prefix: 'Dentro del torneo, ve a la pestaña',
            torneosStep3Btn: '"Jugadores"',
            torneosStep3Suffix: 'para agregar participantes.',
            torneosStep4Prefix: 'Usa',
            torneosStep4Btn: '"Generar Partidos"',
            torneosStep4Suffix: 'para crear automáticamente el calendario Round Robin (todos contra todos).',
            torneosStep5Prefix: 'En la pestaña',
            torneosStep5Btn: '"Partidos"',
            torneosStep5Suffix: ', marca los resultados de cada encuentro. Los standings se actualizan automáticamente.',
            jugadoresTitle: 'Jugadores y Partidos',
            jugadoresWhere: 'Dentro de cada torneo',
            jugadoresStandingsPrefix: 'Los',
            jugadoresStandingsWord: 'standings',
            jugadoresStandingsSuffix: 'se calculan automáticamente con el sistema de puntuación estándar:',
            jugadoresVictoriaLabel: 'Victoria:',
            jugadoresVictoriaValue: '3 puntos',
            jugadoresEmpateLabel: 'Empate:',
            jugadoresEmpateValue: '1 punto',
            jugadoresDerrotaLabel: 'Derrota:',
            jugadoresDerrotaValue: '0 puntos',
            jugadoresTableDesc: 'La tabla incluye: posición, nombre, puntos, partidos jugados (PJ), ganados (PG), empatados (PE), perdidos (PP), goles a favor (GF), goles en contra (GC), y diferencia de gol (DG).',
            noteLabel: 'Nota:',
            jugadoresNote: 'Los goles se registran por partido (score1 / score2), no hay tracking de goleador individual por jugador. El endpoint "top-scorer" calcula los goles sumando los resultados de cada partido en los que participa el jugador.',
            apiTitle: 'API para Agentes (n8n)',
            apiIntroPrefix: 'FIFARDOS expone una API REST en',
            apiIntroSuffix: 'diseñada para que un agente de IA (n8n, LLM, etc.) consulte torneos, standings, goleador, partidos y jugadores en tiempo real, sin necesidad de acceso manual al panel.',
            importantLabel: 'Importante:',
            apiImportantPrefix: 'Consulta',
            apiImportantSuffix: 'para ver la documentación completa de cada endpoint, sus parámetros y ejemplos de respuesta. Este endpoint está pensado para que un LLM lo lea como contexto inicial.',
            apiExampleLabel: 'Ejemplo de petición:',
            authTitle: 'Autenticación',
            authIntroPrefix: 'Todos los endpoints de la API requieren un',
            authIntroToken: 'Personal Access Token',
            authIntroSuffix: 'de Laravel Sanctum. Para generar un token exclusivo para el agente:',
            authHeaderPrefix: 'Incluye el token en el header',
            authHeaderSuffix: 'de cada petición:',
            apisTitle: 'APIs disponibles',
            apisSubtitle: 'Todas requieren API key del agente (Bearer token).',
            colMethod: 'Método',
            colRoute: 'Ruta',
            colDesc: 'Descripción',
            quickLinks: 'Accesos rápidos',
            goDashboard: 'Ir al Dashboard',
            viewTorneos: 'Ver Torneos',
            viewApiSchema: 'Ver API Schema',
            footer: 'FIFARDOS — Documentación del panel de administración',
            copy: 'Copiar',
            copied: 'Copiado!',
            apiTournaments: 'Lista de torneos activos (incluye líder actual)',
            apiStandings: 'Tabla de posiciones completa',
            apiTopScorer: 'Goleador del torneo',
            apiMatches: 'Partidos (filtrable ?status=pending|finished)',
            apiPlayer: 'Datos y estadísticas de un jugador',
            apiSchema: 'Documentación completa para agentes IA',
        },
        en: {
            headTitle: 'Admin · How to use FIFARDOS',
            breadcrumbDocs: 'Documentation',
            backToPanel: '← Back to panel',
            badgeDocs: 'Admin Documentation',
            heroTitle: 'How to use FIFARDOS',
            heroSubtitle: 'Complete admin panel guide: tournaments, players, matches, and the REST API for integration with external agents.',
            quickNav: 'Quick navigation',
            navDashboard: 'Dashboard',
            navTorneos: 'Tournaments',
            navJugadores: 'Players and Matches',
            navApi: 'API for Agents',
            navAuth: 'Authentication',
            dashboardTitle: 'Dashboard',
            whereLabel: 'Where:',
            dashboardWhere: 'Main menu → Dashboard',
            dashboardDesc: 'Panel with a summary of active tournaments, registered players, matches played and pending. From here you can quickly access any tournament or create a new one.',
            torneosTitle: 'Tournaments',
            torneosWhere: 'Menu → Tournaments',
            torneosStep1Prefix: 'Click on',
            torneosStep1Btn: '"+ Create Tournament"',
            torneosStep1Suffix: 'from the Dashboard or the navigation.',
            torneosStep2: 'Assign a name, color and number of available consoles (TVs).',
            torneosStep3Prefix: 'Inside the tournament, go to the',
            torneosStep3Btn: '"Players"',
            torneosStep3Suffix: 'tab to add participants.',
            torneosStep4Prefix: 'Use',
            torneosStep4Btn: '"Generate Matches"',
            torneosStep4Suffix: 'to automatically create the Round Robin schedule (everyone against everyone).',
            torneosStep5Prefix: 'In the',
            torneosStep5Btn: '"Matches"',
            torneosStep5Suffix: 'tab, record the results of each match. The standings update automatically.',
            jugadoresTitle: 'Players and Matches',
            jugadoresWhere: 'Inside each tournament',
            jugadoresStandingsPrefix: 'The',
            jugadoresStandingsWord: 'standings',
            jugadoresStandingsSuffix: 'are calculated automatically with the standard scoring system:',
            jugadoresVictoriaLabel: 'Win:',
            jugadoresVictoriaValue: '3 points',
            jugadoresEmpateLabel: 'Draw:',
            jugadoresEmpateValue: '1 point',
            jugadoresDerrotaLabel: 'Loss:',
            jugadoresDerrotaValue: '0 points',
            jugadoresTableDesc: 'The table includes: position, name, points, matches played (MP), won (W), drawn (D), lost (L), goals for (GF), goals against (GA), and goal difference (GD).',
            noteLabel: 'Note:',
            jugadoresNote: 'Goals are recorded per match (score1 / score2); there is no individual top-scorer tracking per player. The "top-scorer" endpoint calculates goals by summing the results of each match the player takes part in.',
            apiTitle: 'API for Agents (n8n)',
            apiIntroPrefix: 'FIFARDOS exposes a REST API at',
            apiIntroSuffix: 'designed for an AI agent (n8n, LLM, etc.) to query tournaments, standings, top scorer, matches and players in real time, without needing manual access to the panel.',
            importantLabel: 'Important:',
            apiImportantPrefix: 'Check',
            apiImportantSuffix: 'to see the full documentation of each endpoint, its parameters and response examples. This endpoint is intended for an LLM to read as initial context.',
            apiExampleLabel: 'Example request:',
            authTitle: 'Authentication',
            authIntroPrefix: 'All API endpoints require a',
            authIntroToken: 'Personal Access Token',
            authIntroSuffix: 'from Laravel Sanctum. To generate a token exclusively for the agent:',
            authHeaderPrefix: 'Include the token in the',
            authHeaderSuffix: 'header of each request:',
            apisTitle: 'Available APIs',
            apisSubtitle: 'All require the agent API key (Bearer token).',
            colMethod: 'Method',
            colRoute: 'Route',
            colDesc: 'Description',
            quickLinks: 'Quick links',
            goDashboard: 'Go to Dashboard',
            viewTorneos: 'View Tournaments',
            viewApiSchema: 'View API Schema',
            footer: 'FIFARDOS — Admin panel documentation',
            copy: 'Copy',
            copied: 'Copied!',
            apiTournaments: 'List of active tournaments (includes current leader)',
            apiStandings: 'Complete standings table',
            apiTopScorer: 'Tournament top scorer',
            apiMatches: 'Matches (filterable ?status=pending|finished)',
            apiPlayer: 'Player data and statistics',
            apiSchema: 'Full documentation for AI agents',
        },
    },
});

const activeSection = ref('');

function scrollTo(id) {
    activeSection.value = id;
    const el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function copy(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = t('copied');
        setTimeout(() => { btn.textContent = t('copy'); }, 2000);
    });
}

const curlExample = `curl -X GET https://tudominio.com/api/agent/tournaments/1/standings \\
  -H "Authorization: Bearer TU_TOKEN_AQUI"`;

const tokenCommand = `php artisan tinker
$user = User::where('email', 'admin@ejemplo.com')->first();
$token = $user->createToken('agent-n8n')->plainTextToken;
echo $token;`;

const apiRows = [
    { method: 'GET', route: '/api/agent/tournaments', descKey: 'apiTournaments' },
    { method: 'GET', route: '/api/agent/tournaments/{id}/standings', descKey: 'apiStandings' },
    { method: 'GET', route: '/api/agent/tournaments/{id}/top-scorer', descKey: 'apiTopScorer' },
    { method: 'GET', route: '/api/agent/tournaments/{id}/matches?status=', descKey: 'apiMatches' },
    { method: 'GET', route: '/api/agent/players/{id}', descKey: 'apiPlayer' },
    { method: 'GET', route: '/api/agent/schema', descKey: 'apiSchema' },
];

const navItems = [
    { id: 'sec-dashboard', labelKey: 'navDashboard' },
    { id: 'sec-torneos', labelKey: 'navTorneos' },
    { id: 'sec-jugadores', labelKey: 'navJugadores' },
    { id: 'sec-api', labelKey: 'navApi' },
    { id: 'sec-auth', labelKey: 'navAuth' },
];
</script>

<template>
    <Head :title="t('headTitle')" />

    <div class="min-h-screen bg-gray-50 text-gray-900 font-sans">

        <!-- Top bar -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-14 sm:h-16 items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Link :href="route('tournaments.index')" class="flex items-center gap-2.5 shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-elite-secondary to-orange-700
                                        flex items-center justify-center text-black shadow-sm">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="6" width="20" height="12" rx="3"/>
                                    <circle cx="8" cy="12" r="1.5"/>
                                    <circle cx="16" cy="12" r="1.5"/>
                                </svg>
                            </div>
                            <span class="font-bold text-base tracking-wider text-gray-800 hidden sm:block">
                                FIFARDOS
                            </span>
                        </Link>
                        <span class="hidden sm:flex items-center gap-1.5 text-xs text-gray-400">
                            <span>/</span>
                            <span>{{ t('breadcrumbDocs') }}</span>
                        </span>
                    </div>
                    <Link :href="route('tournaments.index')"
                          class="text-sm text-gray-400 hover:text-gray-700 transition-colors px-3 py-2">
                        {{ t('backToPanel') }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <div class="bg-white border-b border-gray-200">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-medium mb-4">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    {{ t('badgeDocs') }}
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight">
                    {{ t('heroTitle') }}
                </h1>
                <p class="mt-3 text-gray-500 text-base sm:text-lg max-w-2xl leading-relaxed">
                    {{ t('heroSubtitle') }}
                </p>
            </div>
        </div>

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8 sm:py-10">

            <!-- ===================== NAVEGACIÓN RÁPIDA ===================== -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5 mb-8">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ t('quickNav') }}</p>
                <div class="flex flex-wrap gap-2">
                    <button v-for="item in navItems" :key="item.id"
                            @click="scrollTo(item.id)"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                            :class="activeSection === item.id
                                ? 'bg-gray-900 text-white shadow-sm'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 hover:text-gray-800'">
                        {{ t(item.labelKey) }}
                    </button>
                </div>
            </div>

            <!-- ===================== DASHBOARD ===================== -->
            <section id="sec-dashboard" class="scroll-mt-20 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-sky-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">{{ t('dashboardTitle') }}</h2>
                        </div>
                        <div class="space-y-3 text-sm text-gray-600 leading-relaxed">
                            <p><strong class="text-gray-800">{{ t('whereLabel') }}</strong> {{ t('dashboardWhere') }}</p>
                            <p>{{ t('dashboardDesc') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== TORNEOS ===================== -->
            <section id="sec-torneos" class="scroll-mt-20 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">{{ t('torneosTitle') }}</h2>
                        </div>
                        <div class="space-y-3 text-sm text-gray-600 leading-relaxed">
                            <p><strong class="text-gray-800">{{ t('whereLabel') }}</strong> {{ t('torneosWhere') }}</p>
                            <ul class="list-disc list-inside space-y-1 pl-2">
                                <li>{{ t('torneosStep1Prefix') }} <strong>{{ t('torneosStep1Btn') }}</strong> {{ t('torneosStep1Suffix') }}</li>
                                <li>{{ t('torneosStep2') }}</li>
                                <li>{{ t('torneosStep3Prefix') }} <strong>{{ t('torneosStep3Btn') }}</strong> {{ t('torneosStep3Suffix') }}</li>
                                <li>{{ t('torneosStep4Prefix') }} <strong>{{ t('torneosStep4Btn') }}</strong> {{ t('torneosStep4Suffix') }}</li>
                                <li>{{ t('torneosStep5Prefix') }} <strong>{{ t('torneosStep5Btn') }}</strong>{{ t('torneosStep5Suffix') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== JUGADORES Y PARTIDOS ===================== -->
            <section id="sec-jugadores" class="scroll-mt-20 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">{{ t('jugadoresTitle') }}</h2>
                        </div>
                        <div class="space-y-3 text-sm text-gray-600 leading-relaxed">
                            <p><strong class="text-gray-800">{{ t('whereLabel') }}</strong> {{ t('jugadoresWhere') }}</p>
                            <p>{{ t('jugadoresStandingsPrefix') }} <strong>{{ t('jugadoresStandingsWord') }}</strong> {{ t('jugadoresStandingsSuffix') }}</p>
                            <ul class="list-disc list-inside space-y-1 pl-2">
                                <li><strong>{{ t('jugadoresVictoriaLabel') }}</strong> {{ t('jugadoresVictoriaValue') }}</li>
                                <li><strong>{{ t('jugadoresEmpateLabel') }}</strong> {{ t('jugadoresEmpateValue') }}</li>
                                <li><strong>{{ t('jugadoresDerrotaLabel') }}</strong> {{ t('jugadoresDerrotaValue') }}</li>
                            </ul>
                            <p>{{ t('jugadoresTableDesc') }}</p>
                            <div class="flex items-start gap-2.5 p-3 rounded-lg bg-amber-50 border border-amber-200 mt-2">
                                <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <p class="text-sm text-amber-800"><strong>{{ t('noteLabel') }}</strong> {{ t('jugadoresNote') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== API PARA AGENTES (n8n) ===================== -->
            <section id="sec-api" class="scroll-mt-20 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">{{ t('apiTitle') }}</h2>
                        </div>
                        <div class="space-y-3 text-sm text-gray-600 leading-relaxed">
                            <p>{{ t('apiIntroPrefix') }} <code class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded text-gray-700">/api/agent/*</code> {{ t('apiIntroSuffix') }}</p>
                            <div class="flex items-start gap-2.5 p-3 rounded-lg bg-blue-50 border border-blue-200 mt-1">
                                <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-blue-800">
                                    <strong>{{ t('importantLabel') }}</strong> {{ t('apiImportantPrefix') }} <code class="font-mono text-xs bg-blue-100 px-1.5 py-0.5 rounded">GET /api/agent/schema</code> {{ t('apiImportantSuffix') }}
                                </p>
                            </div>
                            <p>{{ t('apiExampleLabel') }}</p>
                            <div class="relative">
                                <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-sm font-mono overflow-x-auto leading-relaxed">{{ curlExample }}</pre>
                                <button @click="copy(curlExample, $el)"
                                        class="copy-btn absolute top-2 right-2">{{ t('copy') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== AUTENTICACIÓN ===================== -->
            <section id="sec-auth" class="scroll-mt-20 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m0 0v2m0-2h2m-2 0H10m9-11a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">{{ t('authTitle') }}</h2>
                        </div>
                        <div class="space-y-3 text-sm text-gray-600 leading-relaxed">
                            <p>{{ t('authIntroPrefix') }} <strong>{{ t('authIntroToken') }}</strong> {{ t('authIntroSuffix') }}</p>
                            <div class="relative">
                                <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-sm font-mono overflow-x-auto leading-relaxed">{{ tokenCommand }}</pre>
                                <button @click="copy(tokenCommand, $el)"
                                        class="copy-btn absolute top-2 right-2">{{ t('copy') }}</button>
                            </div>
                            <p>{{ t('authHeaderPrefix') }} <code class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded text-gray-700">Authorization</code> {{ t('authHeaderSuffix') }}</p>
                            <div class="relative">
                                <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-sm font-mono overflow-x-auto leading-relaxed">{{ curlExample }}</pre>
                                <button @click="copy(curlExample, $el)"
                                        class="copy-btn absolute top-2 right-2">{{ t('copy') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== TABLA DE APIs ===================== -->
            <section class="mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">{{ t('apisTitle') }}</h2>
                        </div>
                        <p class="text-sm text-gray-500 mb-4">{{ t('apisSubtitle') }}</p>
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider w-20">{{ t('colMethod') }}</th>
                                        <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">{{ t('colRoute') }}</th>
                                        <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">{{ t('colDesc') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="row in apiRows" :key="row.route"
                                        class="hover:bg-gray-50/70 transition-colors">
                                        <td class="px-4 py-3">
                                            <span class="inline-block font-mono text-xs font-bold px-2 py-0.5 rounded
                                                        bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ row.method }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs text-gray-700 break-all">{{ row.route }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ t(row.descKey) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== ACCESOS RÁPIDOS ===================== -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-6">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ t('quickLinks') }}</p>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('tournaments.index')"
                          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        {{ t('goDashboard') }}
                    </Link>
                    <Link :href="route('tournaments.index')"
                          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                        {{ t('viewTorneos') }}
                    </Link>
                    <a href="/api/agent/schema" target="_blank"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition-colors border border-gray-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        {{ t('viewApiSchema') }}
                    </a>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <footer class="border-t border-gray-200 mt-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-6 text-center text-xs text-gray-400">
                {{ t('footer') }}
            </div>
        </footer>
    </div>
</template>

<style scoped>
.copy-btn {
    @apply px-2.5 py-1 text-[11px] font-medium rounded-md bg-gray-800 text-gray-300
           hover:bg-gray-700 hover:text-white transition-colors cursor-pointer;
    font-family: inherit;
}
.copy-btn:active {
    @apply bg-gray-600;
}
pre {
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.1) transparent;
}
</style>

<style scoped>
/* Retheme esports (landing) para páginas de administración */
:deep(.ucl-card) { background: #0e0e11; border: 1px solid rgba(255,255,255,.1); border-radius: 0; box-shadow: none; }
:deep(.ucl-card)::before { display: none; }
:deep(.ucl-title-lg), :deep(.font-condensed) { font-family: 'Anton', 'Bebas Neue', sans-serif; letter-spacing: 0; }
h1, h2 { font-family: 'Anton', 'Bebas Neue', sans-serif; text-transform: uppercase; letter-spacing: -.5px; }
</style>
