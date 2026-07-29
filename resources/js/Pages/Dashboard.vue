<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import MvpCard from '@/Components/Dashboard/MvpCard.vue';
import LiveMatchSimulation from '@/Components/Dashboard/LiveMatchSimulation.vue';
import TournamentEndingCard from '@/Components/Dashboard/TournamentEndingCard.vue';
import MetricCard from '@/Components/Dashboard/MetricCard.vue';
import ProbabilityBar from '@/Components/Dashboard/ProbabilityBar.vue';

const props = defineProps({
    activeTournaments: { type: Array, default: () => [] },
    mvp: { type: Object, default: null },
    currentMatch: { type: Object, default: null },
    standings: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ totalMatches: 0, totalGoals: 0, pressureIntensity: 65, advanceProbability: 0.5 }) },
});

const activeTab = ref('lobby');
const sidebarOpen = ref(false);
const probExpanded = ref(false);
const victoryProbExpanded = ref(false);
const animated = ref(false);

let refreshInterval = null;

onMounted(() => {
    setTimeout(() => { animated.value = true; }, 100);
    refreshInterval = setInterval(() => {
        router.reload({ only: ['activeTournaments', 'mvp', 'currentMatch', 'standings', 'stats'] });
    }, 10000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

const sortedTournaments = computed(() => {
    return [...props.activeTournaments].sort((a, b) => a.remaining - b.remaining);
});

const mvpScore = computed(() => {
    if (!props.mvp) return 0;
    return Math.min(99, 70 + props.mvp.goals * 3);
});
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <div class="dashboard" :class="{ 'sidebar-open': sidebarOpen }">
            <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle sidebar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12h18M3 6h18M3 18h18"/>
                </svg>
            </button>

            <!-- LEFT COLUMN -->
            <aside class="dash-left">
                <Link :href="route('analytics')" class="left-header dash-clickable">
                    <div class="left-logo">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                            <rect width="28" height="28" rx="6" fill="var(--accent-orange, #ff8a3d)"/>
                            <text x="14" y="19" text-anchor="middle" fill="#000" font-size="14" font-weight="800" font-family="'Bebas Neue',sans-serif">FE</text>
                        </svg>
                    </div>
                    <div class="left-title">Panel de analítica</div>
                </Link>

                <Link :href="route('analytics', { metric: 'presion' })" class="dash-clickable inline-block">
                    <MetricCard label="Intensidad de presión" :value="stats.pressureIntensity" color="var(--accent-orange)" />
                </Link>

                <div class="metric-grid">
                    <Link :href="route('analytics', { metric: 'goles' })" class="dash-clickable inline-block">
                        <MetricCard label="Goles" :value="stats.totalGoals" color="var(--accent-blue)" />
                    </Link>
                    <Link :href="route('analytics', { metric: 'partidos' })" class="dash-clickable inline-block">
                        <MetricCard label="Partidos" :value="stats.totalMatches" color="var(--accent-gold)" />
                    </Link>
                </div>

                <Link v-if="mvp" :href="route('players.show', mvp.id)" class="dash-clickable inline-block">
                    <MvpCard :name="mvp.name" :goals="mvp.goals" :matches="mvp.matches" :initials="mvp.initials" :score="mvpScore" />
                </Link>
                <div v-else class="empty-card">
                    <p class="empty-text">Juega partidos para ver el MVP</p>
                </div>

                <div class="prob-wrapper" :class="{ expanded: probExpanded }">
                    <div class="dash-clickable" @click="probExpanded = !probExpanded">
                        <ProbabilityBar label="Probabilidad de avance" :probability="stats.advanceProbability" home="Tú" away="Rival" />
                    </div>
                    <div v-if="probExpanded" class="prob-breakdown">
                        <div class="prob-bd-row">
                            <span class="prob-bd-label">Head-to-head histórico</span>
                            <span class="prob-bd-value">60%</span>
                        </div>
                        <div class="prob-bd-row">
                            <span class="prob-bd-label">Forma reciente</span>
                            <span class="prob-bd-value">25%</span>
                        </div>
                        <div class="prob-bd-row">
                            <span class="prob-bd-label">Factor localía</span>
                            <span class="prob-bd-value">15%</span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- CENTER COLUMN -->
            <main class="dash-center">
                <div class="center-header">
                    <h1 class="page-title">Actividad del torneo</h1>
                </div>

                <div class="tabs-row">
                    <button class="tab-btn" :class="{ active: activeTab === 'lobby' }" @click="activeTab = 'lobby'">Lobby</button>
                    <button class="tab-btn" :class="{ active: activeTab === 'replay' }" @click="activeTab = 'replay'">Repetición</button>
                    <button class="tab-btn" :class="{ active: activeTab === 'ia' }" @click="activeTab = 'ia'">IA</button>
                </div>

                <!-- Lobby tab -->
                <div v-if="activeTab === 'lobby'" class="tab-content">
                    <div class="section-label">Partido destacado</div>
                    <Link v-if="currentMatch" :href="route('matches.show', [currentMatch.tournament_id, currentMatch.id])" class="dash-clickable inline-block">
                        <LiveMatchSimulation
                            :home="currentMatch.home"
                            :away="currentMatch.away"
                            :homeScore="currentMatch.home_score"
                            :awayScore="currentMatch.away_score"
                            :minute="currentMatch.minute"
                            :tournament="currentMatch.tournament" />
                    </Link>
                    <div v-else class="empty-match">
                        <span class="empty-match-text">No hay partidos aún. Crea un torneo para empezar.</span>
                    </div>

                    <div class="section-label">Simulador de clasificación IA</div>
                    <div class="sim-grid">
                        <Link v-for="t in activeTournaments.slice(0, 3)" :key="t.id"
                              :href="route('tournaments.show', t.id) + '#standings'"
                              class="sim-item dash-clickable">
                            <div class="sim-team">
                                <span class="sim-name">{{ t.name }}</span>
                                <span class="sim-pct">{{ Math.round((t.played / Math.max(t.total, 1)) * 100) }}%</span>
                            </div>
                            <div class="sim-bar-track">
                                <div class="sim-bar-seg sim-win" :style="{ width: animated ? (60 + (t.id * 5) % 20) + '%' : '0%' }" />
                                <div class="sim-bar-seg sim-draw" :style="{ width: animated ? (25 - (t.id * 3) % 15) + '%' : '0%' }" />
                                <div class="sim-bar-seg sim-loss" :style="{ width: animated ? (15 - (t.id * 2) % 10) + '%' : '0%' }" />
                            </div>
                            <div class="sim-labels">
                                <span>{{ animated ? (60 + (t.id * 5) % 20) : 0 }}%</span>
                                <span>{{ animated ? (25 - (t.id * 3) % 15) : 0 }}%</span>
                                <span>{{ animated ? (15 - (t.id * 2) % 10) : 0 }}%</span>
                            </div>
                        </Link>
                        <div v-if="!activeTournaments.length" class="sim-empty">
                            <span>No hay torneos activos para simular.</span>
                        </div>
                    </div>
                </div>

                <!-- Replay tab -->
                <div v-if="activeTab === 'replay'" class="tab-content">
                    <div class="section-label">Repetición de partidos</div>
                    <div class="empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/><path d="M10 8l6 4-6 4V8z"/>
                        </svg>
                        <p class="empty-state-text">Selecciona un partido finalizado para ver su repetición.</p>
                    </div>
                </div>

                <!-- IA tab -->
                <div v-if="activeTab === 'ia'" class="tab-content">
                    <div class="section-label">Análisis predictivo IA</div>
                    <div class="empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5">
                            <path d="M12 2a4 4 0 014 4c0 2-2 3-2 5v2h-4v-2c0-2-2-3-2-5a4 4 0 014-4zM10 17h4v3h-4z"/>
                        </svg>
                        <p class="empty-state-text">Los análisis predictivos estarán disponibles próximamente.</p>
                    </div>
                </div>
            </main>

            <!-- RIGHT COLUMN -->
            <aside class="dash-right">
                <div class="right-card">
                    <div class="right-card-header">
                        <span class="right-card-title">Torneos por finalizar</span>
                    </div>
                    <div class="right-card-body">
                        <TournamentEndingCard v-for="t in sortedTournaments" :key="t.id" :tournament="t" />
                        <div v-if="!sortedTournaments.length" class="empty-list">
                            <span class="empty-list-text">No hay torneos activos.</span>
                            <Link :href="route('tournaments.create')" class="empty-list-link">Crear torneo</Link>
                        </div>
                    </div>
                </div>

                <div class="right-card">
                    <div class="right-card-header">
                        <span class="right-card-title">Probabilidad de victoria</span>
                    </div>
                    <div class="right-card-body victory-prob-wrapper" :class="{ expanded: victoryProbExpanded }">
                        <div class="dash-clickable" @click="victoryProbExpanded = !victoryProbExpanded">
                            <ProbabilityBar :probability="stats.advanceProbability" :compact="true" home="Victorias" away="Derrotas" />
                        </div>
                        <div v-if="victoryProbExpanded" class="prob-breakdown compact">
                            <div class="prob-bd-row"><span class="prob-bd-label">Victorias</span><span class="prob-bd-value">{{ Math.round(stats.advanceProbability * 100) }}%</span></div>
                            <div class="prob-bd-row"><span class="prob-bd-label">Derrotas</span><span class="prob-bd-value">{{ Math.round((1 - stats.advanceProbability) * 100) }}%</span></div>
                        </div>
                    </div>
                </div>

                <div class="right-card">
                    <div class="right-card-header">
                        <span class="right-card-title">Tabla del grupo</span>
                    </div>
                    <div class="right-card-body standings-body">
                        <Link v-for="(s, i) in standings.slice(0, 6)" :key="s.player_id"
                              :href="route('players.show', s.player_id)"
                              class="standing-row dash-clickable">
                            <span class="standing-pos" :class="{ 'pos-first': i === 0 }">{{ i + 1 }}</span>
                            <span class="standing-name">{{ s.player_name }}</span>
                            <span class="standing-pts">{{ s.pts }}</span>
                        </Link>
                        <div v-if="!standings.length" class="empty-list">
                            <span class="empty-list-text">Sin datos de tabla.</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>

<style>
:root {
    --bg-main: #1b2130;
    --card-bg: #242b3d;
    --card-border: #343d54;
    --accent-orange: #ff8a3d;
    --accent-orange-dark: #33261a;
    --accent-blue: #3d9bff;
    --accent-blue-dark: #1e2a3d;
    --text-primary: #f4f2ef;
    --text-secondary: #9aa3bb;
    --text-muted: #7a8299;
}
</style>

<style scoped>
.dashboard {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr) 240px;
    gap: 14px;
    padding: 16px;
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
}

.sidebar-toggle {
    display: none;
    position: fixed;
    top: 12px;
    left: 12px;
    z-index: 100;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--card-bg, #242b3d);
    border: 1px solid var(--card-border, #343d54);
    color: var(--text-primary, #f4f2ef);
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

/* === GLOBAL DASHBOARD CARD MICROINTERACTIONS === */
.dash-clickable {
    cursor: pointer;
    transition: transform 150ms ease-out, border-color 150ms ease-out, box-shadow 150ms ease-out;
    display: block;
    text-decoration: none;
}
.dash-clickable:hover {
    transform: translateY(-3px);
    border-color: var(--accent-orange, #ff8a3d) !important;
    box-shadow: 0 6px 20px rgba(255, 138, 61, 0.15);
}
.dash-clickable:active {
    transform: scale(0.98);
    transition: none;
}

/* === LEFT COLUMN === */
.dash-left {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.left-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: var(--card-bg, #242b3d);
    border: 1px solid var(--card-border, #343d54);
    border-radius: 10px;
    text-decoration: none;
}
.left-logo {
    flex-shrink: 0;
    line-height: 0;
}
.left-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-primary, #f4f2ef);
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
    letter-spacing: 0.05em;
}
.metric-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.empty-card {
    background: var(--card-bg, #242b3d);
    border: 1px dashed var(--card-border, #343d54);
    border-radius: 10px;
    padding: 20px 12px;
    text-align: center;
}
.empty-text {
    font-size: 11px;
    color: var(--text-muted, #7a8299);
}

/* Probability breakdown */
.prob-wrapper {
    border-radius: 10px;
    overflow: hidden;
}
.prob-breakdown {
    background: var(--card-bg, #242b3d);
    border: 1px solid var(--card-border, #343d54);
    border-top: 0;
    border-radius: 0 0 10px 10px;
    padding: 8px 12px;
    animation: slideDown 200ms ease-out;
}
.prob-breakdown.compact {
    border: 0;
    padding: 6px 0 0;
}
.prob-bd-row {
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    padding: 3px 0;
}
.prob-bd-label {
    color: var(--text-muted, #7a8299);
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
    letter-spacing: 0.05em;
}
.prob-bd-value {
    font-weight: 700;
    color: var(--text-primary, #f4f2ef);
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* === CENTER COLUMN === */
.dash-center {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 0;
}
.center-header {
    margin-bottom: 2px;
}
.page-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary, #f4f2ef);
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
    letter-spacing: 0.03em;
}
.tabs-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.tab-btn {
    background: var(--card-bg, #242b3d);
    border: 1px solid var(--card-border, #343d54);
    color: var(--text-secondary, #9aa3bb);
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 150ms ease, background 200ms ease, color 200ms ease, border-color 200ms ease;
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
    letter-spacing: 0.05em;
}
.tab-btn:hover {
    transform: translateY(-2px);
    background: #2c3448;
}
.tab-btn:active {
    transform: scale(0.97);
    transition: none;
}
.tab-btn.active {
    background: var(--accent-orange-dark, #33261a);
    border-color: #5a3820;
    color: var(--accent-orange, #ff8a3d);
}
.tab-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.section-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-muted, #7a8299);
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
    margin-top: 4px;
}
.empty-match {
    background: var(--card-bg, #242b3d);
    border: 1px dashed var(--card-border, #343d54);
    border-radius: 12px;
    padding: 30px 16px;
    text-align: center;
}
.empty-match-text {
    font-size: 12px;
    color: var(--text-muted, #7a8299);
}
.sim-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.sim-item {
    background: var(--card-bg, #242b3d);
    border: 1px solid var(--card-border, #343d54);
    border-radius: 10px;
    padding: 10px 12px;
    text-decoration: none;
}
.sim-team {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}
.sim-name {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-primary, #f4f2ef);
}
.sim-pct {
    font-size: 11px;
    color: var(--text-muted, #7a8299);
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
}
.sim-bar-track {
    height: 6px;
    display: flex;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 3px;
}
.sim-bar-seg {
    height: 100%;
    transition: width 0.8s ease-out;
}
.sim-win { background: var(--accent-blue, #3d9bff); }
.sim-draw { background: var(--accent-gold, #ffb35e); }
.sim-loss { background: var(--accent-orange, #ff8a3d); }
.sim-labels {
    display: flex;
    justify-content: space-between;
    font-size: 9px;
    color: var(--text-muted, #7a8299);
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
    transition: opacity 300ms ease;
}
.sim-empty {
    font-size: 11px;
    color: var(--text-muted, #7a8299);
    text-align: center;
    padding: 12px;
}
.empty-state {
    background: var(--card-bg, #242b3d);
    border: 1px dashed var(--card-border, #343d54);
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}
.empty-state-text {
    font-size: 12px;
    color: var(--text-muted, #7a8299);
}

/* === RIGHT COLUMN === */
.dash-right {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.right-card {
    background: var(--card-bg, #242b3d);
    border: 1px solid var(--card-border, #343d54);
    border-radius: 10px;
    overflow: hidden;
}
.right-card-header {
    padding: 10px 12px;
    border-bottom: 1px solid var(--card-border, #343d54);
}
.right-card-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-secondary, #9aa3bb);
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
}
.right-card-body {
    padding: 10px 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.victory-prob-wrapper.expanded {
    padding-bottom: 0;
}
.standings-body {
    gap: 4px;
}
.standing-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 6px;
    border-radius: 6px;
    font-size: 12px;
    transition: background 150ms;
    text-decoration: none;
}
.standing-row:hover {
    background: rgba(255,255,255,0.03);
}
.standing-pos {
    width: 18px;
    font-weight: 700;
    color: var(--text-muted, #7a8299);
    font-size: 11px;
    text-align: center;
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
}
.standing-pos.pos-first {
    color: var(--accent-gold, #ffb35e);
}
.standing-name {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--text-primary, #f4f2ef);
}
.standing-pts {
    font-weight: 700;
    color: var(--text-secondary, #9aa3bb);
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
    min-width: 22px;
    text-align: right;
}
.empty-list {
    text-align: center;
    padding: 8px 0;
}
.empty-list-text {
    font-size: 11px;
    color: var(--text-muted, #7a8299);
}
.empty-list-link {
    font-size: 11px;
    color: var(--accent-orange, #ff8a3d);
    text-decoration: none;
    display: block;
    margin-top: 4px;
}
.empty-list-link:hover {
    text-decoration: underline;
}

/* === Responsive === */
@media (max-width: 900px) {
    .dashboard {
        grid-template-columns: 1fr;
        gap: 14px;
        padding: 12px;
        padding-top: 56px;
    }
    .sidebar-toggle {
        display: flex;
    }
    .dash-left {
        display: none;
    }
    .dashboard.sidebar-open .dash-left {
        display: flex;
        position: fixed;
        inset: 0;
        z-index: 99;
        background: var(--bg-main, #1b2130);
        padding: 60px 16px 16px;
        overflow-y: auto;
    }
    .dashboard.sidebar-open .sidebar-toggle {
        position: fixed;
        z-index: 100;
    }
}
</style>
