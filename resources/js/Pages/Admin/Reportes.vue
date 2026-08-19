<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    Chart as ChartJS, Title, Tooltip, Legend,
    BarElement, CategoryScale, LinearScale,
    ArcElement, PointElement, LineElement, Filler,
} from 'chart.js';
import { Bar, Doughnut, Line } from 'vue-chartjs';

ChartJS.register(
    Title, Tooltip, Legend,
    BarElement, CategoryScale, LinearScale,
    ArcElement, PointElement, LineElement, Filler,
);

const { t } = useI18n({
    useScope: 'local',
    messages: {
        es: {
            title: 'Reportes', h1Lead: 'Reportes', h1Accent: 'de administración',
            subtitle: 'Métricas globales de la plataforma',
            cUsers: 'Usuarios', cTournaments: 'Torneos', cPlayers: 'Jugadores',
            cMatches: 'Partidos', cGoals: 'Marcadores', cAdmins: 'Admins',
            subUsers: '+{n} en 7 días',
            subTournaments: '{a} en curso · {b} finalizados',
            subPlayers: '{n} por torneo',
            subMatches: '{a} jugados · {b} pendientes',
            subGoals: '{n} por partido',
            subAdmins: 'de {n} usuarios',
            signupsTitle: 'Registros · últimos 14 días',
            signups30: '+{n} en 30 días',
            signupsBarTitle: '{n} registros',
            recentTournaments: 'Torneos recientes',
            tOwnerPlayers: '{owner} · {n} jugadores',
            noTournaments: 'Sin torneos aún.',
            recentUsers: 'Usuarios recientes', adminBadge: 'admin',
            noUsers: 'Sin usuarios.',
            topScorers: 'Goleadores (fútbol)', goals: 'goles',
            noScorers: 'Aún no hay goleadores registrados.',
            // --- Minijuegos ---
            mgSection: 'Minijuegos', mgSectionSub: 'Actividad de las salas en tiempo real',
            mgLobbies: 'Lobbies creados', mgGames: 'Partidas jugadas',
            mgConversion: 'Conversión a partida', mgConversionSub: '{played} de {created} salas jugaron',
            mgPlaysTitle: 'Jugadas por minijuego', mgPlaysLabel: 'Partidas',
            mgAvgTitle: 'Participantes promedio', mgAvgLabel: 'Prom. jugadores',
            mgTriviaTitle: 'Dificultad de la Trivia',
            mgTrendTitle: 'Partidas por día · últimos 14 días', mgTrendLabel: 'Partidas',
            mgMostPlayed: 'Más jugado', mgLeastPlayed: 'Menos jugado',
            mgNoData: 'Aún no hay actividad de minijuegos registrada.',
            gPictionary: 'Pictionary', gTrivia: 'Trivia', gTuttifrutti: 'Tutti Frutti',
            gHangman: 'Ahorcado', gMemoria: 'Memoria',
            dFacil: 'Fácil', dNormal: 'Normal', dDificil: 'Difícil',
        },
        en: {
            title: 'Reports', h1Lead: 'Admin', h1Accent: 'reports',
            subtitle: 'Global platform metrics',
            cUsers: 'Users', cTournaments: 'Tournaments', cPlayers: 'Players',
            cMatches: 'Matches', cGoals: 'Scores', cAdmins: 'Admins',
            subUsers: '+{n} in 7 days',
            subTournaments: '{a} in progress · {b} finished',
            subPlayers: '{n} per tournament',
            subMatches: '{a} played · {b} pending',
            subGoals: '{n} per match',
            subAdmins: 'of {n} users',
            signupsTitle: 'Signups · last 14 days',
            signups30: '+{n} in 30 days',
            signupsBarTitle: '{n} signups',
            recentTournaments: 'Recent tournaments',
            tOwnerPlayers: '{owner} · {n} players',
            noTournaments: 'No tournaments yet.',
            recentUsers: 'Recent users', adminBadge: 'admin',
            noUsers: 'No users.',
            topScorers: 'Top scorers (football)', goals: 'goals',
            noScorers: 'No scorers recorded yet.',
            // --- Minigames ---
            mgSection: 'Minigames', mgSectionSub: 'Real-time room activity',
            mgLobbies: 'Lobbies created', mgGames: 'Games played',
            mgConversion: 'Lobby-to-game rate', mgConversionSub: '{played} of {created} rooms played',
            mgPlaysTitle: 'Plays per minigame', mgPlaysLabel: 'Games',
            mgAvgTitle: 'Average participants', mgAvgLabel: 'Avg players',
            mgTriviaTitle: 'Trivia difficulty',
            mgTrendTitle: 'Games per day · last 14 days', mgTrendLabel: 'Games',
            mgMostPlayed: 'Most played', mgLeastPlayed: 'Least played',
            mgNoData: 'No minigame activity recorded yet.',
            gPictionary: 'Pictionary', gTrivia: 'Trivia', gTuttifrutti: 'Tutti Frutti',
            gHangman: 'Hangman', gMemoria: 'Memory',
            dFacil: 'Easy', dNormal: 'Normal', dDificil: 'Hard',
        },
    },
});

const props = defineProps({
    metrics: Object,
    signups: Array,
    recentTournaments: Array,
    recentUsers: Array,
    topScorers: Array,
    minigames: Object,
});

const maxSignup = computed(() => Math.max(1, ...props.signups.map((s) => s.count)));

const cards = computed(() => [
    { label: t('cUsers'), value: props.metrics.users, sub: t('subUsers', { n: props.metrics.usersLast7 }), icon: 'users' },
    { label: t('cTournaments'), value: props.metrics.tournaments, sub: t('subTournaments', { a: props.metrics.inProgress, b: props.metrics.completed }), icon: 'trophy' },
    { label: t('cPlayers'), value: props.metrics.players, sub: t('subPlayers', { n: props.metrics.avgPlayers }), icon: 'user' },
    { label: t('cMatches'), value: props.metrics.matches, sub: t('subMatches', { a: props.metrics.matchesPlayed, b: props.metrics.matchesPending }), icon: 'ball' },
    { label: t('cGoals'), value: props.metrics.goals, sub: t('subGoals', { n: props.metrics.avgGoalsPerMatch }), icon: 'net' },
    { label: t('cAdmins'), value: props.metrics.admins, sub: t('subAdmins', { n: props.metrics.users }), icon: 'shield' },
]);

function fmtDay(d) {
    const parts = (d || '').split('-');
    return parts.length === 3 ? `${parts[2]}/${parts[1]}` : d;
}

// ---------------------------------------------------------------- Minijuegos
const GAME_COLORS = {
    pictionary: '#ff5f00',
    trivia: '#00D4FF',
    tuttifrutti: '#ffb599',
    hangman: '#a78bfa',
    memoria: '#34d399',
};
const gameName = (g) => t('g' + g.charAt(0).toUpperCase() + g.slice(1));

const mg = computed(() => props.minigames ?? {
    plays: [], totalGames: 0, totalLobbies: 0, roomsWithGame: 0, conversion: 0,
    triviaDifficulty: { facil: 0, normal: 0, dificil: 0 }, byDay: [],
});

const hasMinigameData = computed(() => mg.value.totalGames > 0 || mg.value.totalLobbies > 0);
const playedGames = computed(() => mg.value.plays.filter((p) => p.plays > 0));
const mostPlayed = computed(() => playedGames.value[0] ?? null);
const leastPlayed = computed(() => playedGames.value[playedGames.value.length - 1] ?? null);

// Ejes/estética compartida (tema oscuro esports)
const gridColor = 'rgba(255,255,255,0.06)';
const tickColor = 'rgba(255,255,255,0.45)';
const baseTooltip = {
    backgroundColor: '#0e0e11',
    borderColor: 'rgba(255,255,255,0.15)',
    borderWidth: 1,
    titleColor: '#fff',
    bodyColor: 'rgba(255,255,255,0.75)',
    padding: 10,
};

const playsChartData = computed(() => ({
    labels: mg.value.plays.map((p) => gameName(p.game)),
    datasets: [{
        label: t('mgPlaysLabel'),
        data: mg.value.plays.map((p) => p.plays),
        backgroundColor: mg.value.plays.map((p) => GAME_COLORS[p.game] || '#ff5f00'),
        borderRadius: 4,
        borderSkipped: false,
    }],
}));

const avgChartData = computed(() => ({
    labels: mg.value.plays.map((p) => gameName(p.game)),
    datasets: [{
        label: t('mgAvgLabel'),
        data: mg.value.plays.map((p) => p.avgPlayers),
        backgroundColor: mg.value.plays.map((p) => (GAME_COLORS[p.game] || '#ff5f00') + 'cc'),
        borderRadius: 4,
        borderSkipped: false,
    }],
}));

const horizontalBarOptions = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: baseTooltip },
    scales: {
        x: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, precision: 0 } },
        y: { grid: { display: false }, ticks: { color: '#fff', font: { size: 12 } } },
    },
};

const triviaChartData = computed(() => ({
    labels: [t('dFacil'), t('dNormal'), t('dDificil')],
    datasets: [{
        data: [mg.value.triviaDifficulty.facil, mg.value.triviaDifficulty.normal, mg.value.triviaDifficulty.dificil],
        backgroundColor: ['#34d399', '#fbbf24', '#f87171'],
        borderColor: '#0e0e11',
        borderWidth: 2,
    }],
}));
const triviaTotal = computed(() => {
    const d = mg.value.triviaDifficulty;
    return d.facil + d.normal + d.dificil;
});

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '62%',
    plugins: {
        legend: { position: 'bottom', labels: { color: tickColor, boxWidth: 12, padding: 14 } },
        tooltip: baseTooltip,
    },
};

const trendChartData = computed(() => ({
    labels: mg.value.byDay.map((d) => fmtDay(d.day)),
    datasets: [{
        label: t('mgTrendLabel'),
        data: mg.value.byDay.map((d) => d.count),
        borderColor: '#ff5f00',
        backgroundColor: 'rgba(255,95,0,0.15)',
        fill: true,
        tension: 0.35,
        pointRadius: 3,
        pointBackgroundColor: '#ff5f00',
    }],
}));

const trendOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: baseTooltip },
    scales: {
        x: { grid: { display: false }, ticks: { color: tickColor, font: { size: 10 } } },
        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, precision: 0 } },
    },
};
</script>

<template>
    <Head :title="t('title')" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="ucl-title-lg">{{ t('h1Lead') }} <span class="text-elite-secondary">{{ t('h1Accent') }}</span></h1>
                <p class="ucl-meta mt-1">{{ t('subtitle') }}</p>
            </div>
        </template>

        <div class="py-6 sm:py-8 lg:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Metric cards -->
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="c in cards" :key="c.label" class="ucl-card p-5 sm:p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="font-condensed font-bold text-3xl sm:text-4xl text-white leading-none">{{ c.value }}</div>
                                <div class="mt-2 text-xs uppercase tracking-wider text-white/40">{{ c.label }}</div>
                                <div class="mt-1 text-xs text-white/30">{{ c.sub }}</div>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-elite-secondary/10 border border-elite-secondary/20 flex items-center justify-center text-elite-secondary shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path v-if="c.icon==='users'" stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 0 0-3-3.87M9 20H4v-1a4 4 0 0 1 3-3.87m6-5.13a3 3 0 1 0-4 0M17 8a3 3 0 1 0-2 0"/>
                                    <path v-else-if="c.icon==='trophy'" stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4zM17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/>
                                    <path v-else-if="c.icon==='user'" stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z"/>
                                    <path v-else-if="c.icon==='ball'" stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 7l3 2-1 4h-4l-1-4z"/>
                                    <path v-else-if="c.icon==='net'" stroke-linecap="round" stroke-linejoin="round" d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6l7-3z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Signups chart -->
                <div class="ucl-card p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="font-condensed font-bold text-lg tracking-wider text-white">{{ t('signupsTitle') }}</h2>
                        <span class="text-xs text-white/40">{{ t('signups30', { n: metrics.usersLast30 }) }}</span>
                    </div>
                    <div class="flex items-end gap-1.5 h-32">
                        <div v-for="s in signups" :key="s.day" class="flex-1 flex flex-col items-center gap-1.5 group">
                            <div class="w-full rounded-t bg-gradient-to-t from-elite-secondary/40 to-elite-secondary transition-all"
                                 :style="{ height: Math.max(4, (s.count / maxSignup) * 104) + 'px' }"
                                 :title="t('signupsBarTitle', { n: s.count })"></div>
                            <span class="text-[9px] text-white/25 rotate-0">{{ fmtDay(s.day) }}</span>
                        </div>
                    </div>
                </div>

                <!-- ============================ MINIJUEGOS ============================ -->
                <div class="pt-2">
                    <div class="flex items-baseline gap-3 mb-4">
                        <h2 class="font-condensed font-bold text-2xl tracking-wider text-white uppercase">{{ t('mgSection') }}</h2>
                        <span class="text-xs text-white/40">{{ t('mgSectionSub') }}</span>
                    </div>

                    <p v-if="!hasMinigameData" class="ucl-card p-8 text-center text-sm text-white/40">{{ t('mgNoData') }}</p>

                    <div v-else class="space-y-6">
                        <!-- KPIs minijuegos -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="ucl-card p-5">
                                <div class="font-condensed font-bold text-4xl text-white leading-none">{{ mg.totalLobbies }}</div>
                                <div class="mt-2 text-xs uppercase tracking-wider text-white/40">{{ t('mgLobbies') }}</div>
                            </div>
                            <div class="ucl-card p-5">
                                <div class="font-condensed font-bold text-4xl text-white leading-none">{{ mg.totalGames }}</div>
                                <div class="mt-2 text-xs uppercase tracking-wider text-white/40">{{ t('mgGames') }}</div>
                            </div>
                            <div class="ucl-card p-5">
                                <div class="font-condensed font-bold text-4xl text-elite-secondary leading-none">{{ mg.conversion }}%</div>
                                <div class="mt-2 text-xs uppercase tracking-wider text-white/40">{{ t('mgConversion') }}</div>
                                <div class="mt-1 text-xs text-white/30">{{ t('mgConversionSub', { played: mg.roomsWithGame, created: mg.totalLobbies }) }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Jugadas por minijuego (ranking) -->
                            <div class="ucl-card p-5 sm:p-6">
                                <h3 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">{{ t('mgPlaysTitle') }}</h3>
                                <div class="h-56"><Bar :data="playsChartData" :options="horizontalBarOptions" /></div>
                                <div v-if="mostPlayed" class="mt-4 flex flex-wrap gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-400/10 text-emerald-300 border border-emerald-400/20">
                                        🏆 {{ t('mgMostPlayed') }}: <strong class="text-white">{{ gameName(mostPlayed.game) }}</strong> ({{ mostPlayed.plays }})
                                    </span>
                                    <span v-if="leastPlayed && leastPlayed.game !== mostPlayed.game" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/5 text-white/50 border border-white/10">
                                        😴 {{ t('mgLeastPlayed') }}: <strong class="text-white/80">{{ gameName(leastPlayed.game) }}</strong> ({{ leastPlayed.plays }})
                                    </span>
                                </div>
                            </div>

                            <!-- Participantes promedio -->
                            <div class="ucl-card p-5 sm:p-6">
                                <h3 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">{{ t('mgAvgTitle') }}</h3>
                                <div class="h-56"><Bar :data="avgChartData" :options="horizontalBarOptions" /></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Tendencia por día -->
                            <div class="ucl-card p-5 sm:p-6 lg:col-span-2">
                                <h3 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">{{ t('mgTrendTitle') }}</h3>
                                <div class="h-56"><Line :data="trendChartData" :options="trendOptions" /></div>
                            </div>

                            <!-- Dificultad de la Trivia -->
                            <div class="ucl-card p-5 sm:p-6">
                                <h3 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">{{ t('mgTriviaTitle') }}</h3>
                                <div v-if="triviaTotal > 0" class="h-56"><Doughnut :data="triviaChartData" :options="doughnutOptions" /></div>
                                <p v-else class="text-sm text-white/30 py-10 text-center">—</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent tournaments -->
                    <div class="ucl-card p-5 sm:p-6">
                        <h2 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">{{ t('recentTournaments') }}</h2>
                        <div class="space-y-2">
                            <div v-for="row in recentTournaments" :key="row.id" class="flex items-center gap-3 py-2 border-b border-white/5 last:border-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="row.status==='completed' ? 'bg-emerald-400' : 'bg-elite-secondary'"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-white/90 truncate">{{ row.name }}</div>
                                    <div class="text-xs text-white/40 truncate">{{ t('tOwnerPlayers', { owner: row.owner, n: row.players }) }}</div>
                                </div>
                                <span class="text-xs text-white/30 shrink-0">{{ row.created_at }}</span>
                            </div>
                            <p v-if="!recentTournaments.length" class="text-sm text-white/30 py-4 text-center">{{ t('noTournaments') }}</p>
                        </div>
                    </div>

                    <!-- Recent users -->
                    <div class="ucl-card p-5 sm:p-6">
                        <h2 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">{{ t('recentUsers') }}</h2>
                        <div class="space-y-2">
                            <div v-for="u in recentUsers" :key="u.id" class="flex items-center gap-3 py-2 border-b border-white/5 last:border-0">
                                <span class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-xs font-bold text-white/70 shrink-0">
                                    {{ u.name.charAt(0).toUpperCase() }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-white/90 truncate">
                                        {{ u.name }}
                                        <span v-if="u.is_admin" class="ml-1 text-[10px] uppercase tracking-wide text-elite-secondary">{{ t('adminBadge') }}</span>
                                    </div>
                                    <div class="text-xs text-white/40 truncate">{{ u.email }}</div>
                                </div>
                                <span class="text-xs text-white/30 shrink-0">{{ u.created_at }}</span>
                            </div>
                            <p v-if="!recentUsers.length" class="text-sm text-white/30 py-4 text-center">{{ t('noUsers') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Top scorers -->
                <div class="ucl-card p-5 sm:p-6">
                    <h2 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">{{ t('topScorers') }}</h2>
                    <div v-if="topScorers.length" class="space-y-2.5">
                        <div v-for="(s, i) in topScorers" :key="i" class="flex items-center gap-3">
                            <span class="font-condensed font-bold text-lg w-6 text-elite-secondary">{{ i + 1 }}</span>
                            <span class="text-sm text-white/80 flex-1 truncate">{{ s.name }}</span>
                            <span class="text-sm font-bold text-white">{{ s.goals }} <span class="text-white/30 font-normal">{{ t('goals') }}</span></span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-white/30 py-4 text-center">{{ t('noScorers') }}</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Retematiza a la estética esports (landing) sólo en esta página */
:deep(.ucl-card) {
    background: #0e0e11;
    border: 1px solid rgba(255, 255, 255, .1);
    border-radius: 0;
    box-shadow: none;
}
:deep(.ucl-card)::before { display: none; }
:deep(.ucl-title-lg) {
    font-family: 'Anton', Impact, sans-serif;
    text-transform: uppercase;
    letter-spacing: -.5px;
}
:deep(.font-condensed) { font-family: 'Anton', Impact, sans-serif; letter-spacing: 0; }
</style>
