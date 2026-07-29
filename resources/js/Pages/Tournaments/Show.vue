<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed, nextTick, reactive } from 'vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import { triggerConfetti } from '@/composables/useConfetti';

const props = defineProps({
    tournament: Object,
    standings: Array,
    allPlayed: Boolean,
    rounds: Array,
    groupAllPlayed: Boolean,
    phases: Array,
    goalScorers: Array,
});

const activeTab = ref('matches');
const localRounds = ref([]);
const editingMatchId = ref(null);
const formData = reactive({
    score1: 0,
    score2: 0,
    played_at: '',
    showStats: false,
    possession_a: null,
    possession_b: null,
    shots_a: null,
    shots_b: null,
    shots_on_target_a: null,
    shots_on_target_b: null,
    cards_a: null,
    cards_b: null,
    penalties1: null,
    penalties2: null,
    goalScorers: [],
});

const editingMatchRef = ref(null);
const localPhases = ref([]);

function initPhases() {
    localPhases.value = props.phases ? JSON.parse(JSON.stringify(props.phases)) : [];
}
initPhases();
watch(() => props.phases, () => { initPhases(); }, { deep: true });

function initRounds() {
    localRounds.value = JSON.parse(JSON.stringify(props.rounds));
}
initRounds();
watch(() => props.rounds, initRounds, { deep: true });

function openResultForm(match) {
    editingMatchId.value = match.id;
    editingMatchRef.value = match;
    formData.score1 = match.score1 ?? 0;
    formData.score2 = match.score2 ?? 0;
    formData.played_at = new Date().toISOString().slice(0, 16);
    formData.showStats = false;
    formData.possession_a = null;
    formData.possession_b = null;
    formData.shots_a = null;
    formData.shots_b = null;
    formData.shots_on_target_a = null;
    formData.shots_on_target_b = null;
    formData.cards_a = null;
    formData.cards_b = null;
    formData.penalties1 = null;
    formData.penalties2 = null;
    formData.goalScorers = [];
}

function cancelResultForm() {
    editingMatchId.value = null;
}

function saveScore(match) {
    const s1 = parseInt(match.score1, 10);
    const s2 = parseInt(match.score2, 10);
    if (isNaN(s1) || isNaN(s2) || s1 < 0 || s2 < 0) return;
    router.post(route('matches.score.update', [props.tournament.id, match.id]), { score1: s1, score2: s2 });
}

function submitScore(match) {
    const s1 = parseInt(formData.score1, 10);
    const s2 = parseInt(formData.score2, 10);
    if (isNaN(s1) || isNaN(s2) || s1 < 0 || s2 < 0) return;

    const payload = { score1: s1, score2: s2 };

    if (formData.played_at) {
        payload.played_at = formData.played_at;
    }

    // Penalties (only if tied in knockout)
    if (formData.penalties1 !== null && formData.penalties2 !== null) {
        payload.penalties1 = formData.penalties1;
        payload.penalties2 = formData.penalties2;
    }

    if (formData.showStats) {
        const stats = {};
        if (formData.possession_a !== null && formData.possession_b !== null) {
            stats.possession_a = formData.possession_a;
            stats.possession_b = formData.possession_b;
        }
        if (formData.shots_a !== null) stats.shots_a = formData.shots_a;
        if (formData.shots_b !== null) stats.shots_b = formData.shots_b;
        if (formData.shots_on_target_a !== null) stats.shots_on_target_a = formData.shots_on_target_a;
        if (formData.shots_on_target_b !== null) stats.shots_on_target_b = formData.shots_on_target_b;
        if (formData.cards_a !== null) stats.cards_a = formData.cards_a;
        if (formData.cards_b !== null) stats.cards_b = formData.cards_b;
        if (Object.keys(stats).length > 0) {
            payload.stats = stats;
        }
    }

    // Goal scorers
    const validScorers = formData.goalScorers.filter(gs => gs.player_id && gs.goals > 0);
    if (validScorers.length > 0) {
        payload.goal_scorers = validScorers.map(gs => ({
            player_id: gs.player_id,
            goals: gs.goals,
            minutes: gs.minutes && gs.minutes.length > 0 ? gs.minutes : null,
        }));
    }

    router.post(route('matches.score.update', [props.tournament.id, match.id]), payload);
}

function editMatch(match) {
    router.post(route('matches.score.edit', [props.tournament.id, match.id]));
}

const color = computed(() => props.tournament.color || '#F97316');
const totalMatches = computed(() => props.rounds.reduce((a, r) => a + r.length, 0));
const playedMatches = computed(() => props.rounds.reduce((a, r) => a + r.filter(m => m.status === 'finished').length, 0));
const progress = computed(() => totalMatches.value ? Math.round(playedMatches.value / totalMatches.value * 100) : 0);
const champion = computed(() => props.allPlayed && props.standings.length > 0 ? props.standings[0] : null);
const bracketPlayed = computed(() => props.phases ? props.phases.reduce((a, p) => a + p.matches.filter(m => m.status === 'finished').length, 0) : 0);
const bracketTotal = computed(() => props.phases ? props.phases.reduce((a, p) => a + p.matches.length, 0) : 0);
const allMatchesTotal = computed(() => totalMatches.value + bracketTotal.value);
const allMatchesPlayed = computed(() => playedMatches.value + bracketPlayed.value);
const statusText = computed(() => {
    if (props.allPlayed) return 'FINALIZADO';
    return 'EN CURSO';
});
const statusClass = computed(() => {
    if (props.allPlayed) return 'status-completed';
    return 'status-in-progress';
});

function hexToRgb(hex) {
    const v = parseInt(hex.slice(1), 16);
    return `${(v >> 16) & 255}, ${(v >> 8) & 255}, ${v & 255}`;
}

const replaceModalOpen = ref(false);
const replacePlayerId = ref(null);
const replaceNewName = ref('');

function openReplaceModal() {
    replacePlayerId.value = null;
    replaceNewName.value = '';
    replaceModalOpen.value = true;
}

function submitReplace() {
    if (!replacePlayerId.value || !replaceNewName.value.trim()) return;
    router.post(route('tournaments.players.replace', [props.tournament.id, replacePlayerId.value]), {
        new_name: replaceNewName.value.trim(),
    });
    replaceModalOpen.value = false;
}

const confettiFired = ref(false);
watch(() => props.allPlayed, (val) => {
    if (val && !confettiFired.value) {
        confettiFired.value = true;
        triggerConfetti();
    }
});

const copiedStandings = ref(false);

function copyStandings() {
    const header = 'Pos\tJugador\tPTS\tPJ\tPG\tPE\tPP\tGF\tGC\tDG';
    const rows = props.standings.map((s, i) =>
        `${i + 1}\t${s.player_name}\t${s.pts}\t${s.pj}\t${s.pg}\t${s.pe}\t${s.pp}\t${s.gf}\t${s.gc}\t${s.dg > 0 ? '+' : ''}${s.dg}`
    );
    navigator.clipboard.writeText([header, ...rows].join('\n')).then(() => {
        copiedStandings.value = true;
        setTimeout(() => { copiedStandings.value = false; }, 2000);
    });
}

// Bracket helpers
const bracketPhases = computed(() => {
    if (!localPhases.value.length) return [];
    return localPhases.value.filter(p => p.key !== 'third_place');
});

const thirdPlacePhase = computed(() => {
    if (!localPhases.value.length) return null;
    return localPhases.value.find(p => p.key === 'third_place') || null;
});

const hasGroups = computed(() => props.rounds && props.rounds.length > 0);

const finalPhase = computed(() => {
    if (!bracketPhases.value.length) return null;
    return bracketPhases.value[bracketPhases.value.length - 1];
});

function isWinner(match, playerNum) {
    if (match.status !== 'finished') return false;
    if (match.score1 === null || match.score2 === null) return false;
    if (match.score1 > match.score2) return playerNum === 1;
    if (match.score2 > match.score1) return playerNum === 2;
    // Tied — check penalties
    if (match.penalties1 !== null && match.penalties2 !== null) {
        if (match.penalties1 > match.penalties2) return playerNum === 1;
        if (match.penalties2 > match.penalties1) return playerNum === 2;
    }
    return false;
}

function getPlayerName(match, playerNum) {
    const key = playerNum === 1 ? 'player1' : 'player2';
    if (!match[key]) return '—';
    if (!match[key].name) return '—';
    if (match.status === 'pending' && !match.player1_id) return '—';
    return match[key].name;
}

function getMatchGap(phaseIndex, totalPhases) {
    // As we move right, gaps between matches increase to allow for connector lines
    return Math.pow(2, phaseIndex);
}
</script>

<template>
    <Head :title="tournament.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4"
                  :style="{ '--t-color': color, '--t-rgb': hexToRgb(color) }">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="ucl-title-lg truncate" :style="{ color: 'var(--t-color)' }">{{ tournament.name }}</h1>
                        <StatusBadge :status="tournament.status" />
                    </div>
                    <div class="flex items-center gap-3 mt-1.5 text-xs sm:text-sm text-white/30">
                        <span>{{ tournament.players.length }} JUGADORES</span>
                        <span class="w-1 h-1 rounded-full bg-white/10" />
                        <span>{{ tournament.consoles_count }} TV</span>
                        <span class="w-1 h-1 rounded-full bg-white/10" />
                        <span>{{ totalMatches }} PARTIDOS</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a :href="route('tournaments.public.bracket', tournament.id)" target="_blank"
                       class="ucl-btn-ghost text-xs min-h-touch px-4">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                    <Link :href="route('dashboard')" class="ucl-btn-ghost text-xs min-h-touch px-4">
                        <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver
                    </Link>
                    <button @click="openReplaceModal" class="ucl-btn-ghost text-xs min-h-touch px-4" title="Reemplazar jugador">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-4 4m0 0l-4-4m4 4V3" />
                        </svg>
                    </button>
                    <Link :href="route('tournaments.destroy', tournament.id)" method="delete" as="button"
                          class="ucl-btn-danger text-xs min-h-touch px-4"
                          onclick="return confirm('¿Eliminar torneo? Se borrarán todos los datos.')">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8 lg:py-10"
             :style="{ '--t-color': color, '--t-rgb': hexToRgb(color) }">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">

                <!-- Status bar -->
                <div class="ucl-card animate-fade-up">
                    <div class="flex items-center justify-between gap-4 p-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <span class="status-pill" :class="statusClass">{{ statusText }}</span>
                            <span class="text-sm text-white/40 font-condensed tracking-wide">
                                {{ allMatchesPlayed }} de {{ allMatchesTotal }} partidos jugados
                            </span>
                        </div>
                        <div v-if="!allPlayed" class="flex-1 max-w-xs">
                            <ProgressBar
                                :value="playedMatches"
                                :max="totalMatches"
                                :detail="`${progress}%`" />
                        </div>
                    </div>
                </div>

                <!-- Champion Banner -->
                <div v-if="champion" class="ucl-champion animate-scale-in">
                    <div class="relative z-10">
                        <div class="text-5xl sm:text-7xl mb-3 sm:mb-4">🏆</div>
                        <h2 class="font-condensed font-bold text-xl sm:text-2xl tracking-[0.08em] uppercase mb-2"
                            :style="{ color: color }">
                            Champion
                        </h2>
                        <div class="ucl-title-lg text-3xl sm:text-5xl lg:text-6xl text-white animate-gold-pulse"
                             :style="{ textShadow: `0 0 20px ${color}44, 0 0 40px ${color}22` }">
                            {{ champion.player_name }}
                        </div>
                        <div class="flex items-center justify-center gap-4 sm:gap-6 mt-4 text-xs sm:text-sm text-white/40 font-medium">
                            <span class="font-bold" :style="{ color: color }">{{ champion.pts }} PTS</span>
                            <span class="w-1 h-1 rounded-full bg-white/10" />
                            <span>{{ champion.pg }}G {{ champion.pe }}E {{ champion.pp }}P</span>
                            <span class="w-1 h-1 rounded-full bg-white/10" />
                            <span>DG {{ champion.dg > 0 ? '+' : '' }}{{ champion.dg }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="ucl-card p-1 flex animate-fade-up">
                    <button @click="activeTab = 'matches'"
                            class="flex-1 min-h-touch flex items-center justify-center gap-2 rounded-xl font-condensed text-xs sm:text-sm uppercase tracking-[0.08em] transition-all duration-200"
                            :class="activeTab === 'matches' ? 'text-white' : 'text-white/30 hover:text-white/60'"
                            :style="activeTab === 'matches' ? { background: color + '18', color: color } : {}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Partidos
                    </button>
                    <button @click="activeTab = 'standings'"
                            class="flex-1 min-h-touch flex items-center justify-center gap-2 rounded-xl font-condensed text-xs sm:text-sm uppercase tracking-[0.08em] transition-all duration-200"
                            :class="activeTab === 'standings' ? 'text-white' : 'text-white/30 hover:text-white/60'"
                            :style="activeTab === 'standings' ? { background: color + '18', color: color } : {}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Clasificación
                    </button>
                    <button @click="activeTab = 'knockout'"
                            class="flex-1 min-h-touch flex items-center justify-center gap-2 rounded-xl font-condensed text-xs sm:text-sm uppercase tracking-[0.08em] transition-all duration-200"
                            :class="activeTab === 'knockout' ? 'text-white' : 'text-white/30 hover:text-white/60'"
                            :style="activeTab === 'knockout' ? { background: color + '18', color: color } : {}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        Eliminatorias
                    </button>
                    <button @click="activeTab = 'stats'"
                            class="flex-1 min-h-touch flex items-center justify-center gap-2 rounded-xl font-condensed text-xs sm:text-sm uppercase tracking-[0.08em] transition-all duration-200"
                            :class="activeTab === 'stats' ? 'text-white' : 'text-white/30 hover:text-white/60'"
                            :style="activeTab === 'stats' ? { background: color + '18', color: color } : {}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Estadísticas
                    </button>
                </div>

                <!-- ====== MATCHES ====== -->
                <div v-if="activeTab === 'matches'" class="space-y-8 sm:space-y-10">
                    <div v-for="(round, rIdx) in localRounds" :key="rIdx" class="animate-fade-up">
                        <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-5">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center font-condensed font-bold shrink-0"
                                 :style="{
                                     background: `${color}22`,
                                     border: `1px solid ${color}44`,
                                     color: color
                                 }">
                                {{ rIdx + 1 }}
                            </div>
                            <h3 class="font-condensed font-bold text-base sm:text-lg uppercase tracking-[0.06em] text-white/80">
                                Jornada {{ rIdx + 1 }}
                            </h3>
                            <div class="flex-1 h-px bg-gradient-to-r from-white/5 to-transparent" />
                            <span class="text-[10px] sm:text-xs font-condensed text-white/20 tracking-wider">{{ round.length }} PARTIDOS</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
                            <div v-for="match in round" :key="match.id"
                                 class="ucl-match"
                                 :class="{
                                     'played': match.status === 'finished',
                                     'editing-form': editingMatchId === match.id
                                 }"
                                 :style="match.status === 'finished' ? { borderColor: `${color}33`, background: `linear-gradient(135deg, ${color}08, rgba(14,22,48,0.95))` } : {}">
                                <div class="stars-overlay" />
                                <div class="relative space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-condensed font-bold uppercase tracking-wider"
                                              :style="{ background: `${color}18`, border: `1px solid ${color}33`, color: color }">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            TV {{ match.tv_number }}
                                        </span>
                                        <span v-if="match.status === 'finished'" class="text-[10px] font-condensed text-ucl-gold uppercase tracking-wider">✓ FINAL</span>
                                        <span v-else class="text-[10px] font-condensed text-white/15 uppercase tracking-wider">PENDIENTE</span>
                                    </div>

                                    <!-- Players row -->
                                    <div class="flex items-center justify-between gap-3 py-1">
                                        <div class="flex-1 text-right">
                                             <div class="ucl-player"
                                                  :class="match.status === 'finished' ? (match.score1 > match.score2 ? 'winner' : 'loser') : ''">
                                                  {{ match.player1?.name || '—' }}
                                             </div>
                                        </div>

                                        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                                            <template v-if="match.status === 'finished'">
                                                <span class="min-w-[2.5rem] sm:min-w-[3rem] text-center text-2xl sm:text-3xl font-condensed font-bold"
                                                      :class="match.score1 > match.score2 ? 'text-ucl-gold' : match.score1 < match.score2 ? 'text-white/30' : 'text-white/60'">
                                                    {{ match.score1 }}
                                                </span>
                                                <span class="text-white/10 font-condensed font-bold text-sm tracking-widest">:</span>
                                                <span class="min-w-[2.5rem] sm:min-w-[3rem] text-center text-2xl sm:text-3xl font-condensed font-bold"
                                                      :class="match.score2 > match.score1 ? 'text-ucl-gold' : match.score2 < match.score1 ? 'text-white/30' : 'text-white/60'">
                                                    {{ match.score2 }}
                                                </span>
                                            </template>
                                            <template v-else>
                                                <span class="text-white/40 font-condensed font-bold text-lg">vs</span>
                                            </template>
                                        </div>

                                        <div class="flex-1 text-left">
                                            <div class="ucl-player"
                                                 :class="match.status === 'finished' ? (match.score2 > match.score1 ? 'winner' : 'loser') : ''">
                                                {{ match.player2?.name || '—' }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Finished: edit button -->
                                    <div v-if="match.status === 'finished'" class="pt-2 border-t border-white/5">
                                        <button @click="editMatch(match)"
                                                class="w-full min-h-touch rounded-xl bg-white/5 text-white/40 hover:text-white hover:bg-white/10 font-condensed text-xs uppercase tracking-wider transition-all duration-200">
                                            EDITAR RESULTADO
                                        </button>
                                    </div>

                                    <!-- Pending: inline form -->
                                    <div v-else>
                                        <!-- Collapsed: Cargar resultado button -->
                                        <div v-if="editingMatchId !== match.id" class="pt-2 border-t border-white/5">
                                            <button @click="openResultForm(match)"
                                                    class="w-full min-h-touch rounded-xl font-condensed text-xs uppercase tracking-wider transition-all duration-200 text-black"
                                                    :style="{
                                                        background: `linear-gradient(135deg, ${color}, ${color}cc)`,
                                                        boxShadow: `0 4px 16px ${color}33`
                                                    }">
                                                <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                                CARGAR RESULTADO
                                            </button>
                                        </div>

                                        <!-- Expanded: form -->
                                        <div v-else class="pt-3 border-t border-white/5 space-y-3">
                                            <!-- Score inputs -->
                                            <div class="flex items-center justify-center gap-3">
                                                <div class="flex flex-col items-center">
                                                    <label class="text-[9px] text-white/30 font-condensed tracking-wider mb-1">{{ match.player1?.name?.split(' ')[0] || 'A' }}</label>
                                                    <input type="number" min="0" class="score-input text-lg w-16 text-center" v-model.number="formData.score1" />
                                                </div>
                                                <span class="text-white/20 font-condensed font-bold text-sm">—</span>
                                                <div class="flex flex-col items-center">
                                                    <label class="text-[9px] text-white/30 font-condensed tracking-wider mb-1">{{ match.player2?.name?.split(' ')[0] || 'B' }}</label>
                                                    <input type="number" min="0" class="score-input text-lg w-16 text-center" v-model.number="formData.score2" />
                                                </div>
                                            </div>

                                            <!-- Date/time -->
                                            <div>
                                                <label class="text-[9px] text-white/30 font-condensed tracking-wider block mb-1">Fecha y hora del partido</label>
                                                <input type="datetime-local" v-model="formData.played_at"
                                                       class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-xs font-condensed focus:outline-none focus:border-white/20" />
                                            </div>

                                            <!-- Penalties (knockout only, shown when tied) -->
                                            <div v-if="match.phase !== 'group' && match.phase !== 'third_place'"
                                                 class="pt-1">
                                                <details class="group">
                                                    <summary class="text-[10px] text-white/30 hover:text-white/50 font-condensed tracking-wider cursor-pointer select-none">
                                                        + Penales (desempate)
                                                    </summary>
                                                    <div class="mt-2 flex items-center justify-center gap-3">
                                                        <div class="flex flex-col items-center">
                                                            <label class="text-[9px] text-white/30 font-condensed tracking-wider mb-1">{{ match.player1?.name?.split(' ')[0] || 'A' }}</label>
                                                            <input type="number" min="0" v-model.number="formData.penalties1"
                                                                   class="w-14 text-center bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-white text-sm" />
                                                        </div>
                                                        <span class="text-white/20 font-condensed font-bold text-xs">—</span>
                                                        <div class="flex flex-col items-center">
                                                            <label class="text-[9px] text-white/30 font-condensed tracking-wider mb-1">{{ match.player2?.name?.split(' ')[0] || 'B' }}</label>
                                                            <input type="number" min="0" v-model.number="formData.penalties2"
                                                                   class="w-14 text-center bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-white text-sm" />
                                                        </div>
                                                    </div>
                                                </details>
                                            </div>

                                            <!-- Optional stats (collapsible) -->
                                            <details class="group" @toggle="formData.showStats = $event.target.open">
                                                <summary class="text-[10px] text-white/30 hover:text-white/50 font-condensed tracking-wider cursor-pointer select-none">
                                                    + Agregar estadísticas
                                                </summary>
                                                <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                                                    <div class="col-span-2 flex items-center justify-between border-b border-white/5 pb-1 mb-1">
                                                        <span class="text-[9px] text-white/20 font-condensed tracking-wider">Posesión</span>
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">{{ match.player1?.name?.split(' ')[0] || 'A' }} %</label>
                                                        <input type="number" min="0" max="100" v-model.number="formData.possession_a"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">{{ match.player2?.name?.split(' ')[0] || 'B' }} %</label>
                                                        <input type="number" min="0" max="100" v-model.number="formData.possession_b"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>

                                                    <div class="col-span-2 flex items-center justify-between border-b border-white/5 pb-1 mb-1 mt-1">
                                                        <span class="text-[9px] text-white/20 font-condensed tracking-wider">Tiros</span>
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">Totales A</label>
                                                        <input type="number" min="0" v-model.number="formData.shots_a"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">Totales B</label>
                                                        <input type="number" min="0" v-model.number="formData.shots_b"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">A puerta A</label>
                                                        <input type="number" min="0" v-model.number="formData.shots_on_target_a"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">A puerta B</label>
                                                        <input type="number" min="0" v-model.number="formData.shots_on_target_b"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>

                                                    <div class="col-span-2 flex items-center justify-between border-b border-white/5 pb-1 mb-1 mt-1">
                                                        <span class="text-[9px] text-white/20 font-condensed tracking-wider">Tarjetas</span>
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">A</label>
                                                        <input type="number" min="0" v-model.number="formData.cards_a"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">B</label>
                                                        <input type="number" min="0" v-model.number="formData.cards_b"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>

                                                    <!-- Goal scorers -->
                                                    <div class="col-span-2 flex items-center justify-between border-b border-white/5 pb-1 mb-1 mt-1">
                                                        <span class="text-[9px] text-white/20 font-condensed tracking-wider">Goleadores del partido</span>
                                                        <button @click="formData.goalScorers.push({ player_id: null, goals: 1, minutes: [] })"
                                                                class="text-[9px] text-white/30 hover:text-white font-condensed tracking-wider">
                                                            + Añadir goleador
                                                        </button>
                                                    </div>
                                                    <div v-for="(gs, gsIdx) in formData.goalScorers" :key="gsIdx"
                                                         class="col-span-2 grid grid-cols-12 gap-2 items-end border border-white/5 rounded-lg p-2">
                                                        <div class="col-span-5">
                                                            <label class="text-[8px] text-white/30 font-condensed tracking-wider block">Jugador</label>
                                                            <select v-model="gs.player_id"
                                                                    class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs">
                                                                <option value="" disabled>Seleccionar</option>
                                                                <option v-for="p in props.tournament.players" :key="p.id" :value="p.id">
                                                                    {{ p.name }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                        <div class="col-span-2">
                                                            <label class="text-[8px] text-white/30 font-condensed tracking-wider block">Goles</label>
                                                            <input type="number" min="1" v-model.number="gs.goals"
                                                                   class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                        </div>
                                                        <div class="col-span-4">
                                                            <label class="text-[8px] text-white/30 font-condensed tracking-wider block">Minutos</label>
                                                            <input type="text" placeholder="12, 45, 67" :value="(gs.minutes || []).join(', ')"
                                                                   @input="e => { gs.minutes = e.target.value.split(',').map(m => parseInt(m.trim())).filter(m => !isNaN(m)) }"
                                                                   class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                        </div>
                                                        <div class="col-span-1 flex items-end pb-1">
                                                            <button @click="formData.goalScorers.splice(gsIdx, 1)"
                                                                    class="text-white/20 hover:text-red-400 text-sm">✕</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </details>

                                            <!-- Action buttons -->
                                            <div class="flex gap-2 pt-1">
                                                <button @click="submitScore(match)"
                                                        class="flex-1 min-h-touch rounded-xl font-condensed text-xs uppercase tracking-wider transition-all duration-200 text-black"
                                                        :style="{
                                                            background: `linear-gradient(135deg, ${color}, ${color}cc)`,
                                                            boxShadow: `0 4px 16px ${color}33`
                                                        }">
                                                    <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    GUARDAR RESULTADO
                                                </button>
                                                <button @click="cancelResultForm"
                                                        class="px-4 min-h-touch rounded-xl bg-white/5 text-white/30 hover:text-white/60 font-condensed text-xs uppercase tracking-wider transition-all duration-200">
                                                    CANCELAR
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ====== STANDINGS ====== -->
                <div v-if="activeTab === 'standings'" class="ucl-card overflow-hidden animate-fade-up">
                    <div class="px-5 sm:px-6 py-3 border-b border-white/5 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-white/80">Tabla de posiciones</h3>
                        <button @click="copyStandings"
                                class="text-xs px-3 py-1.5 rounded-md font-medium transition-all duration-200"
                                :class="copiedStandings
                                    ? 'bg-emerald-500/20 text-emerald-300'
                                    : 'bg-white/10 text-white/40 hover:bg-white/20 hover:text-white/60'">
                            {{ copiedStandings ? 'Copiado!' : 'Copiar tabla' }}
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="ucl-table">
                            <thead>
                                <tr class="bg-white/[0.02]">
                                    <th class="w-10 sm:w-12 text-center">#</th>
                                    <th>Jugador</th>
                                    <th class="text-center">PTS</th>
                                    <th class="text-center hidden sm:table-cell">PJ</th>
                                    <th class="text-center hidden sm:table-cell">PG</th>
                                    <th class="text-center hidden sm:table-cell">PE</th>
                                    <th class="text-center hidden sm:table-cell">PP</th>
                                    <th class="text-center">GF</th>
                                    <th class="text-center">GC</th>
                                    <th class="text-center">DG</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(s, idx) in standings" :key="s.player_id"
                                     :style="idx === 0 && allPlayed ? { background: `linear-gradient(90deg, ${color}12, transparent)` } : idx === 0 ? { background: `${color}08` } : {}">
                                    <td class="text-center font-condensed font-bold text-lg"
                                        :style="idx === 0 ? { color: allPlayed ? '#FFD700' : color } : {}">
                                        <span v-if="idx === 0 && allPlayed">👑</span>
                                        <span v-else>{{ idx + 1 }}</span>
                                    </td>
                                    <td class="font-semibold"
                                        :style="idx === 0 && allPlayed ? { color: '#FFD700' } : idx === 0 ? { color: color } : {}">
                                        {{ s.player_name }}
                                    </td>
                                    <td class="text-center font-condensed font-bold text-lg sm:text-xl"
                                        :style="idx === 0 && allPlayed ? { color: '#FFD700' } : idx === 0 ? { color: color } : {}">
                                        {{ s.pts }}
                                    </td>
                                    <td class="text-center text-white/40 hidden sm:table-cell">{{ s.pj }}</td>
                                    <td class="text-center hidden sm:table-cell" :class="s.pg > 0 ? 'text-green-400' : 'text-white/30'">{{ s.pg }}</td>
                                    <td class="text-center text-white/40 hidden sm:table-cell">{{ s.pe }}</td>
                                    <td class="text-center hidden sm:table-cell" :class="s.pp > 0 ? 'text-red-400' : 'text-white/30'">{{ s.pp }}</td>
                                    <td class="text-center text-white/70 font-semibold">{{ s.gf }}</td>
                                    <td class="text-center text-white/70 font-semibold">{{ s.gc }}</td>
                                    <td class="text-center font-bold font-condensed"
                                        :class="s.dg > 0 ? 'text-green-400' : s.dg < 0 ? 'text-red-400' : 'text-white/30'">
                                        {{ s.dg > 0 ? '+' : '' }}{{ s.dg }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 sm:px-6 py-3 border-t border-white/5 flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-white/20 font-condensed tracking-wider">
                        <span>PTS = Puntos</span>
                        <span>PJ = Jugados</span>
                        <span>PG = Ganados</span>
                        <span>PE = Empatados</span>
                        <span>PP = Perdidos</span>
                        <span>GF = Goles Favor</span>
                        <span>GC = Goles Contra</span>
                        <span>DG = Diferencia</span>
                    </div>
                </div>

                <!-- ====== KNOCKOUT BRACKET ====== -->
                <div v-if="activeTab === 'knockout'" class="animate-fade-up space-y-6">

                    <!-- No groups yet -->
                    <div v-if="!hasGroups && !localPhases.length"
                         class="ucl-card p-8 text-center">
                        <p class="text-white/30 text-sm">No hay partidos de grupo para generar eliminatorias.</p>
                    </div>

                    <!-- Waiting for group stage -->
                    <div v-else-if="!groupAllPlayed && !localPhases.length"
                         class="ucl-card p-8 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-white/5 flex items-center justify-center">
                            <svg class="w-8 h-8 text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">Fase de grupos en curso</h3>
                        <p class="text-sm text-white/40 max-w-md mx-auto">Completá todos los partidos de la fase de grupos para desbloquear las eliminatorias.</p>
                    </div>

                    <!-- Bracket -->
                    <div v-else-if="bracketPhases.length" class="space-y-6">

                        <!-- Bracket header -->
                        <div class="ucl-card px-5 py-3 border-l-4"
                             :style="{ borderLeftColor: color }">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-semibold text-white">Fase eliminatoria</h3>
                                    <p class="text-xs text-white/30 mt-0.5">
                                        {{ bracketPhases.length }} fase{{ bracketPhases.length > 1 ? 's' : '' }}
                                        · {{ bracketPhases.reduce((a, p) => a + p.matches.length, 0) }} partidos
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-white/40">
                                    <span v-for="p in bracketPhases" :key="p.key"
                                          class="px-2 py-1 rounded-md"
                                          :class="p.allPlayed ? 'bg-emerald-500/10 text-emerald-300' : 'bg-white/5'">
                                        {{ p.label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Bracket layout - horizontal scroll on mobile -->
                        <div class="ucl-card overflow-hidden">
                            <div class="p-4 sm:p-6 overflow-x-auto">
                                <div class="flex items-stretch gap-0 min-w-[600px] sm:min-w-[700px] lg:min-w-0">

                                    <!-- Phase columns -->
                                    <template v-for="(phase, pIdx) in bracketPhases" :key="phase.key">
                                        <div class="flex flex-col shrink-0" style="width: 240px;">
                                            <!-- Phase header -->
                                            <div class="text-center mb-4">
                                                <span class="text-sm font-condensed font-bold uppercase tracking-[0.1em]"
                                                      :class="phase.allPlayed ? 'text-emerald-400' : ''"
                                                      :style="{ color: phase.allPlayed ? undefined : color }">
                                                    {{ phase.label }}
                                                </span>
                                            </div>

                                            <!-- Match cards -->
                                            <div class="flex flex-col flex-1 justify-around gap-4">
                                                <div v-for="(match, mIdx) in phase.matches" :key="match.id"
                                                     class="bracket-match"
                                                     :class="{
                                                         'finished': match.status === 'finished',
                                                         'pending': match.status === 'pending' && match.player1_id && match.player2_id,
                                                         'empty': match.status === 'pending' && (!match.player1_id || !match.player2_id)
                                                     }"
                                                     :style="match.status === 'finished' ? {
                                                         '--match-accent': color,
                                                         borderColor: `${color}55`,
                                                     } : {}">

                                                    <!-- Player 1 -->
                                                    <div class="bracket-player">
                                                        <div class="flex-1 truncate text-xs sm:text-sm font-medium"
                                                             :class="isWinner(match, 1) ? 'text-white font-bold' : match.status === 'finished' ? 'text-white/35' : 'text-white/60'">
                                                            {{ getPlayerName(match, 1) }}
                                                        </div>
                                                         <div class="min-w-[3rem] text-center">
                                                             <template v-if="match.status === 'finished'">
                                                                 <span class="text-sm font-bold"
                                                                       :class="isWinner(match, 1) ? 'text-ucl-gold text-base' : 'text-white/30'">
                                                                     {{ match.score1 }}
                                                                 </span>
                                                             </template>
                                                             <template v-else-if="match.player1_id">
                                                                 <input type="number" min="0" placeholder="-"
                                                                        class="score-input"
                                                                        v-model.number="match.score1" />
                                                             </template>
                                                             <span v-else class="text-white/15 text-xs">—</span>
                                                         </div>
                                                         <span v-if="isWinner(match, 1)" class="winner-indicator">✓</span>
                                                     </div>

                                                     <!-- Separator -->
                                                     <div class="bracket-vs">
                                                         <span class="text-[10px] font-condensed text-white/[0.07] font-bold tracking-[0.15em]">VS</span>
                                                         <span v-if="match.status === 'finished' && match.score1 === match.score2"
                                                               class="text-[9px] text-amber-400/50 font-condensed">EMP</span>
                                                         <span v-if="match.status === 'pending' && match.player1_id && match.player2_id"
                                                               class="text-[9px] text-white/15 font-condensed">PENDIENTE</span>
                                                     </div>

                                                     <!-- Player 2 -->
                                                     <div class="bracket-player">
                                                         <div class="flex-1 truncate text-xs sm:text-sm font-medium"
                                                              :class="isWinner(match, 2) ? 'text-white font-bold' : match.status === 'finished' ? 'text-white/35' : 'text-white/60'">
                                                             {{ getPlayerName(match, 2) }}
                                                         </div>
                                                         <div class="min-w-[3rem] text-center">
                                                             <template v-if="match.status === 'finished'">
                                                                 <span class="text-sm font-bold"
                                                                       :class="isWinner(match, 2) ? 'text-ucl-gold text-base' : 'text-white/30'">
                                                                     {{ match.score2 }}
                                                                 </span>
                                                             </template>
                                                             <template v-else-if="match.player2_id">
                                                                 <input type="number" min="0" placeholder="-"
                                                                        class="score-input"
                                                                        v-model.number="match.score2" />
                                                            </template>
                                                            <span v-else class="text-white/15 text-xs">—</span>
                                                        </div>
                                                        <span v-if="isWinner(match, 2)" class="winner-indicator">✓</span>
                                                    </div>

                                                    <!-- Action button -->
                                                    <div v-if="match.status === 'finished'"
                                                         class="bracket-action">
                                                        <button @click="editMatch(match)"
                                                                class="bracket-btn-edit">
                                                            EDITAR
                                                        </button>
                                                    </div>
                                                    <div v-else-if="match.player1_id && match.player2_id && match.score1 >= 0 && match.score2 >= 0"
                                                         class="bracket-action">
                                                        <button @click="saveScore(match)"
                                                                class="bracket-btn-save">
                                                            GUARDAR
                                                        </button>
                                                    </div>
                                                    <div v-else-if="!match.player1_id || !match.player2_id"
                                                         class="bracket-action">
                                                        <span class="text-[9px] text-white/10 font-condensed italic tracking-wider">Esperando rival...</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Connector arrow between phases -->
                                        <div v-if="pIdx < bracketPhases.length - 1"
                                             class="flex flex-col items-center justify-center shrink-0 px-2">
                                            <div class="flex flex-col items-center gap-1">
                                                <svg class="w-6 h-6 text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                </svg>
                                                <span class="text-[9px] text-white/[0.07] font-condensed uppercase tracking-[0.15em]">avanza</span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Third place match -->
                                    <div v-if="thirdPlacePhase"
                                         class="flex flex-col shrink-0 ml-6" style="width: 240px;">
                                        <div class="text-center mb-4">
                                            <span class="text-sm font-condensed font-bold uppercase tracking-[0.1em]"
                                                  :style="{ color: color }">
                                                {{ thirdPlacePhase.label }}
                                            </span>
                                        </div>
                                        <div class="flex flex-col flex-1 justify-end pb-8">
                                            <div v-for="match in thirdPlacePhase.matches" :key="match.id"
                                                 class="bracket-match"
                                                 :class="{
                                                     'finished': match.status === 'finished',
                                                     'pending': match.status === 'pending' && match.player1_id && match.player2_id,
                                                     'empty': match.status === 'pending' && (!match.player1_id || !match.player2_id)
                                                 }"
                                                 :style="match.status === 'finished' ? { borderColor: `${color}55` } : {}">
                                                <div class="bracket-player">
                                                    <div class="flex-1 truncate text-xs font-medium"
                                                         :class="isWinner(match, 1) ? 'text-white font-bold' : match.status === 'finished' ? 'text-white/35' : 'text-white/60'">
                                                        {{ getPlayerName(match, 1) }}
                                                    </div>
                                                    <div class="min-w-[3rem] text-center">
                                                        <template v-if="match.status === 'finished'">
                                                            <span class="text-sm font-bold" :class="isWinner(match, 1) ? 'text-ucl-gold text-base' : 'text-white/30'">{{ match.score1 }}</span>
                                                        </template>
                                                        <template v-else-if="match.player1_id">
                                                            <input type="number" min="0" placeholder="-"
                                                                   class="score-input"
                                                                   v-model.number="match.score1" />
                                                        </template>
                                                        <span v-else class="text-white/15 text-xs">—</span>
                                                    </div>
                                                    <span v-if="isWinner(match, 1)" class="winner-indicator">✓</span>
                                                </div>
                                                <div class="bracket-vs">
                                                    <span class="text-[10px] font-condensed text-white/[0.07] font-bold tracking-[0.15em]">VS</span>
                                                    <span v-if="match.status === 'finished' && match.score1 === match.score2" class="text-[9px] text-amber-400/50 font-condensed">EMP</span>
                                                    <span v-if="match.status === 'pending' && match.player1_id && match.player2_id" class="text-[9px] text-white/15 font-condensed">PENDIENTE</span>
                                                </div>
                                                <div class="bracket-player">
                                                    <div class="flex-1 truncate text-xs font-medium"
                                                         :class="isWinner(match, 2) ? 'text-white font-bold' : match.status === 'finished' ? 'text-white/35' : 'text-white/60'">
                                                        {{ getPlayerName(match, 2) }}
                                                    </div>
                                                    <div class="min-w-[3rem] text-center">
                                                        <template v-if="match.status === 'finished'">
                                                            <span class="text-sm font-bold" :class="isWinner(match, 2) ? 'text-ucl-gold text-base' : 'text-white/30'">{{ match.score2 }}</span>
                                                        </template>
                                                        <template v-else-if="match.player2_id">
                                                            <input type="number" min="0" placeholder="-"
                                                                   class="score-input"
                                                                   v-model.number="match.score2" />
                                                        </template>
                                                        <span v-else class="text-white/15 text-xs">—</span>
                                                    </div>
                                                    <span v-if="isWinner(match, 2)" class="winner-indicator">✓</span>
                                                </div>
                                                <div v-if="match.status === 'finished'" class="bracket-action">
                                                    <button @click="editMatch(match)" class="bracket-btn-edit">EDITAR</button>
                                                </div>
                                                <div v-else-if="match.player1_id && match.player2_id && match.score1 >= 0 && match.score2 >= 0" class="bracket-action">
                                                    <button @click="saveScore(match)" class="bracket-btn-save">GUARDAR</button>
                                                </div>
                                                <div v-else-if="!match.player1_id || !match.player2_id" class="bracket-action">
                                                    <span class="text-[9px] text-white/10 font-condensed italic tracking-wider">Esperando rival...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Final highlight -->
                        <div v-if="finalPhase && finalPhase.matches.length && finalPhase.matches[0].player1_id && finalPhase.matches[0].player2_id"
                             class="ucl-card overflow-hidden text-center py-6"
                             :style="{ borderColor: `${color}33`, background: `linear-gradient(135deg, ${color}08, transparent)` }">
                            <div class="text-3xl mb-2">🏆</div>
                            <h4 class="font-condensed font-bold text-sm uppercase tracking-[0.1em] text-white/50 mb-3">Gran Final</h4>
                            <div v-for="m in finalPhase.matches" :key="m.id"
                                 class="inline-flex items-center gap-4 sm:gap-6 text-lg sm:text-xl font-condensed font-bold">
                                <span :class="isWinner(m, 1) ? 'text-white' : m.status === 'finished' ? 'text-white/30' : 'text-white/60'">
                                    {{ getPlayerName(m, 1) }}
                                </span>
                                <span class="text-white/20 text-sm">VS</span>
                                <span :class="isWinner(m, 2) ? 'text-white' : m.status === 'finished' ? 'text-white/30' : 'text-white/60'">
                                    {{ getPlayerName(m, 2) }}
                                </span>
                            </div>
                        </div>

                    </div>

                    <!-- No bracket phases -->
                    <div v-else class="ucl-card p-8 text-center">
                        <p class="text-white/30 text-sm">No hay eliminatorias generadas aún.</p>
                    </div>
                </div>

                <!-- ====== ESTADÍSTICAS ====== -->
                <div v-if="activeTab === 'stats'" class="animate-fade-up">
                    <div v-if="goalScorers && goalScorers.length > 0" class="ucl-card overflow-hidden">
                        <div class="px-5 sm:px-6 py-3 border-b border-white/5">
                            <h3 class="text-sm font-semibold text-white/80">Tabla de goleadores</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="ucl-table">
                                <thead>
                                    <tr class="bg-white/[0.02]">
                                        <th class="w-10 text-center">#</th>
                                        <th>Jugador</th>
                                        <th class="text-center">Goles</th>
                                        <th class="text-center hidden sm:table-cell">Partidos</th>
                                        <th class="text-center">Promedio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(gs, idx) in goalScorers" :key="gs.player_id"
                                        :style="idx === 0 ? { background: `${color}08` } : {}">
                                        <td class="text-center font-condensed font-bold text-lg"
                                            :style="idx === 0 ? { color: color } : {}">
                                            {{ idx + 1 }}
                                        </td>
                                        <td class="font-semibold" :style="idx === 0 ? { color: color } : {}">
                                            {{ gs.player_name }}
                                        </td>
                                        <td class="text-center font-condensed font-bold text-lg sm:text-xl"
                                            :style="idx === 0 ? { color: color } : {}">
                                            {{ gs.goals }}
                                        </td>
                                        <td class="text-center text-white/40 hidden sm:table-cell">{{ gs.matches }}</td>
                                        <td class="text-center font-semibold font-condensed" :style="idx === 0 ? { color: color } : {}">
                                            {{ gs.average }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-else class="ucl-card p-8 text-center">
                        <svg class="w-10 h-10 mx-auto mb-3 text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-sm font-bold text-white/50 mb-1 font-condensed tracking-wider uppercase">Sin datos de goleadores</h3>
                        <p class="text-xs text-white/20 font-condensed">No se han cargado goleadores individuales en este torneo. Los datos aparecerán cuando registres goleadores al cargar resultados.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Replace player modal -->
        <Teleport to="body">
            <div v-if="replaceModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
                 @click.self="replaceModalOpen = false">
                <div class="bg-[#1b2130] border border-[#343d54] rounded-2xl p-6 w-full max-w-sm mx-4 shadow-2xl">
                    <h3 class="text-base font-bold text-[#f4f2ef] mb-4 font-condensed tracking-wider uppercase">Reemplazar jugador</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-[10px] text-[#7a8299] font-condensed tracking-wider block mb-1">Jugador a reemplazar</label>
                            <select v-model="replacePlayerId"
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-[#ff8a3d]">
                                <option value="" disabled>Seleccionar jugador</option>
                                <option v-for="p in tournament.players" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] text-[#7a8299] font-condensed tracking-wider block mb-1">Nuevo jugador</label>
                            <input type="text" v-model="replaceNewName" placeholder="Nombre del reemplazo"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-[#ff8a3d]" />
                        </div>
                    </div>
                    <div class="flex gap-2 mt-5 justify-end">
                        <button @click="replaceModalOpen = false"
                                class="px-4 py-2 text-xs text-white/50 hover:text-white/80 font-condensed tracking-wider uppercase">Cancelar</button>
                        <button @click="submitReplace"
                                :disabled="!replacePlayerId || !replaceNewName.trim()"
                                class="px-4 py-2 text-xs font-bold font-condensed tracking-wider uppercase rounded-lg"
                                :class="replacePlayerId && replaceNewName.trim() ? 'bg-[#ff8a3d] text-black hover:bg-[#ffa05e]' : 'bg-white/5 text-white/20 cursor-not-allowed'">
                            Reemplazar
                        </button>
                    </div>
                    <p class="mt-3 text-[10px] text-[#7a8299] font-condensed leading-relaxed">
                        Los partidos pendientes del jugador saliente se asignarán al nuevo. Los historiales (goles, partidos finalizados) se mantienen.
                    </p>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.status-pill {
    font-size: 10px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.status-in-progress {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
}
.status-completed {
    background: rgba(52, 211, 153, 0.15);
    color: #34d399;
    border: 1px solid rgba(52, 211, 153, 0.3);
}
.ucl-match.editing-form {
    border-color: rgba(255,255,255,0.15);
}
</style>
