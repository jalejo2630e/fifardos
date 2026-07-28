<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import { triggerConfetti } from '@/composables/useConfetti';

const props = defineProps({
    tournament: Object,
    standings: Array,
    allPlayed: Boolean,
    rounds: Array,
    groupAllPlayed: Boolean,
    knockoutMatches: Array,
});

const activeTab = ref('matches');
const localRounds = ref([]);

function initRounds() {
    localRounds.value = JSON.parse(JSON.stringify(props.rounds));
}
initRounds();
watch(() => props.rounds, initRounds, { deep: true });

function saveScore(match) {
    const s1 = parseInt(match.score1, 10);
    const s2 = parseInt(match.score2, 10);
    if (isNaN(s1) || isNaN(s2) || s1 < 0 || s2 < 0) return;
    router.post(route('matches.score.update', [props.tournament.id, match.id]), { score1: s1, score2: s2 });
}

function editMatch(match) {
    router.post(route('matches.score.edit', [props.tournament.id, match.id]));
}

const color = computed(() => props.tournament.color || '#F97316');
const totalMatches = computed(() => props.rounds.reduce((a, r) => a + r.length, 0));
const playedMatches = computed(() => props.rounds.reduce((a, r) => a + r.filter(m => m.status === 'finished').length, 0));
const progress = computed(() => totalMatches.value ? Math.round(playedMatches.value / totalMatches.value * 100) : 0);
const champion = computed(() => props.allPlayed && props.standings.length > 0 ? props.standings[0] : null);

function hexToRgb(hex) {
    const v = parseInt(hex.slice(1), 16);
    return `${(v >> 16) & 255}, ${(v >> 8) & 255}, ${v & 255}`;
}

const confettiFired = ref(false);
watch(() => props.allPlayed, (val) => {
    if (val && !confettiFired.value) {
        confettiFired.value = true;
        triggerConfetti();
    }
});

const copiedStandings = ref(false);
const knockoutTop = ref(4);

function generateKnockout() {
    router.post(route('tournaments.generate-knockout', props.tournament.id), { top: knockoutTop.value });
}

function getRoundName(m) {
    const pos = m.bracket_position || '';
    if (pos.startsWith('qf')) return 'Cuartos de final';
    if (pos.startsWith('sf')) return 'Semifinales';
    if (pos === 'final') return 'Final';
    return 'Eliminatorias';
}

function getPosition(m) {
    return m.bracket_position || '';
}

function hasRound(prefix) {
    return props.knockoutMatches?.some(m => (m.bracket_position || '').startsWith(prefix));
}
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
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver
                    </Link>
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

                <!-- Progress -->
                <div v-if="!allPlayed" class="animate-fade-up">
                    <ProgressBar
                        :value="playedMatches"
                        :max="totalMatches"
                        :detail="`${playedMatches}/${totalMatches} · ${progress}%`" />
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
                                 :class="{ 'played': match.status === 'finished' }"
                                 :style="match.status === 'finished' ? { borderColor: `${color}33`, background: `linear-gradient(135deg, ${color}08, rgba(14,22,48,0.95))` } : {}">
                                <div class="stars-overlay" />
                                <div class="relative space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-condensed font-bold uppercase tracking-wider"
                                              :style="{
                                                  background: `${color}18`,
                                                  border: `1px solid ${color}33`,
                                                  color: color
                                              }">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            TV {{ match.tv_number }}
                                        </span>
                                        <span v-if="match.status === 'finished'" class="text-[10px] font-condensed text-ucl-gold uppercase tracking-wider">✓ FINAL</span>
                                        <span v-else class="text-[10px] font-condensed text-white/15 uppercase tracking-wider">PENDIENTE</span>
                                    </div>

                                    <div class="flex items-center justify-between gap-3 py-1">
                                        <div class="flex-1 text-right">
                                             <div class="ucl-player"
                                                  :class="match.status === 'finished' ? (match.score1 > match.score2 ? 'winner' : 'loser') : ''">
                                                 {{ match.player1?.name }}
                                             </div>
                                        </div>

                                        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                                            <template v-if="match.status !== 'finished'">
                                                <input type="number" min="0" class="score-input text-lg" v-model.number="match.score1" />
                                                <span class="ucl-vs">VS</span>
                                                <input type="number" min="0" class="score-input text-lg" v-model.number="match.score2" />
                                            </template>
                                            <template v-else>
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
                                        </div>

                                        <div class="flex-1 text-left">
                                            <div class="ucl-player"
                                                 :class="match.status === 'finished' ? (match.score2 > match.score1 ? 'winner' : 'loser') : ''">
                                                {{ match.player2?.name }}
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="match.status === 'finished'" class="pt-2 border-t border-white/5">
                                        <button @click="editMatch(match)"
                                                class="w-full min-h-touch rounded-xl bg-white/5 text-white/40 hover:text-white hover:bg-white/10 font-condensed text-xs uppercase tracking-wider transition-all duration-200">
                                            EDITAR RESULTADO
                                        </button>
                                    </div>
                                    <div v-else-if="match.score1 >= 0 && match.score2 >= 0" class="pt-2 border-t border-white/5">
                                        <button @click="saveScore(match)"
                                                class="w-full min-h-touch rounded-xl font-condensed text-xs uppercase tracking-wider transition-all duration-200 text-black"
                                                :style="{
                                                    background: `linear-gradient(135deg, ${color}, ${color}cc)`,
                                                    boxShadow: `0 4px 16px ${color}33`
                                                }">
                                            <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            GUARDAR RESULTADO
                                        </button>
                                    </div>
                                    <div v-else class="pt-2 border-t border-white/5 text-center text-[10px] text-white/15 font-condensed tracking-wider">
                                        INTRODUCE LOS GOLES
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
                <!-- ====== KNOCKOUT BRACKET ====== -->
                <div v-if="activeTab === 'knockout'" class="animate-fade-up space-y-6">
                    <!-- Generate knockout button -->
                    <div v-if="groupAllPlayed && !knockoutMatches.length"
                         class="ucl-card p-8 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-elite-secondary/10 flex items-center justify-center">
                            <svg class="w-8 h-8 text-elite-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">¡Fase de grupos completa!</h3>
                        <p class="text-sm text-white/40 mb-6 max-w-md mx-auto">Generá las eliminatorias finales con los mejores jugadores de la tabla.</p>
                        <form @submit.prevent="generateKnockout">
                            <div class="flex items-center justify-center gap-3 mb-4">
                                <label class="text-sm text-white/60">Pasan los mejores</label>
                                <select v-model="knockoutTop"
                                        class="bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm font-medium focus:outline-none focus:border-elite-secondary/50">
                                    <option value="2">2</option>
                                    <option value="4" selected>4</option>
                                    <option value="8">8</option>
                                </select>
                            </div>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold text-white
                                           bg-gradient-to-r from-elite-secondary to-purple-600 hover:brightness-110 transition-all duration-200">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Generar eliminatorias
                            </button>
                        </form>
                    </div>

                    <!-- Waiting for group stage -->
                    <div v-else-if="!groupAllPlayed && !knockoutMatches.length"
                         class="ucl-card p-8 text-center">
                        <p class="text-white/30 text-sm">Completá todos los partidos de la fase de grupos para desbloquear las eliminatorias.</p>
                    </div>

                    <!-- Bracket -->
                    <div v-else-if="knockoutMatches.length" class="space-y-4">
                        <h3 class="text-sm font-semibold text-white/80">Fase eliminatoria</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div v-for="roundName in ['Cuartos de final', 'Semifinales', 'Final']" :key="roundName"
                                 class="space-y-2">
                                <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wider text-center">{{ roundName }}</h4>
                                <div v-for="m in knockoutMatches.filter(mm => getRoundName(mm) === roundName)" :key="m.id"
                                     class="ucl-match !p-3"
                                     :class="{ 'played': m.status === 'finished' }"
                                     :style="m.status === 'finished' ? { borderColor: `${color}33`, background: `linear-gradient(135deg, ${color}08, rgba(14,22,48,0.95))` } : {}">
                                    <div class="stars-overlay" />
                                    <div class="relative space-y-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex-1 text-right text-xs truncate"
                                                 :class="m.status === 'finished' && m.score1 > m.score2 ? 'text-green-400 font-semibold' : 'text-white/60'">
                                                {{ m.player1?.name || '—' }}
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <template v-if="m.status !== 'finished'">
                                                    <input type="number" min="0" class="w-10 h-8 text-center rounded-md bg-white/5 border border-white/10 text-white text-sm" v-model.number="m.score1" />
                                                    <span class="text-white/20 text-xs">:</span>
                                                    <input type="number" min="0" class="w-10 h-8 text-center rounded-md bg-white/5 border border-white/10 text-white text-sm" v-model.number="m.score2" />
                                                </template>
                                                <template v-else>
                                                    <span class="w-8 text-center font-bold text-base" :class="m.score1 > m.score2 ? 'text-ucl-gold' : 'text-white/30'">{{ m.score1 }}</span>
                                                    <span class="text-white/20 text-xs">:</span>
                                                    <span class="w-8 text-center font-bold text-base" :class="m.score2 > m.score1 ? 'text-ucl-gold' : 'text-white/30'">{{ m.score2 }}</span>
                                                </template>
                                            </div>
                                            <div class="flex-1 text-left text-xs truncate"
                                                 :class="m.status === 'finished' && m.score2 > m.score1 ? 'text-green-400 font-semibold' : 'text-white/60'">
                                                {{ m.player2?.name || '—' }}
                                            </div>
                                        </div>
                                        <div v-if="m.status === 'finished'" class="flex justify-center">
                                            <button @click="editMatch(m)" class="text-[10px] text-white/30 hover:text-white transition-colors">EDITAR</button>
                                        </div>
                                        <div v-else-if="m.score1 >= 0 && m.score2 >= 0" class="flex justify-center">
                                            <button @click="saveScore(m)"
                                                    class="text-xs px-3 py-1 rounded-md font-semibold text-black transition-all"
                                                    :style="{ background: `linear-gradient(135deg, ${color}, ${color}cc)` }">
                                                GUARDAR
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bracket Flowchart -->
                    <div v-if="knockoutMatches.length"
                         class="ucl-card overflow-hidden">
                        <div class="px-5 py-3 border-b border-white/5">
                            <h3 class="text-sm font-semibold text-white/80">Recorrido al título</h3>
                        </div>
                        <div class="p-5 overflow-x-auto">
                            <div class="flex items-start justify-center gap-2 min-w-[500px]">
                                <!-- QF column -->
                                <div v-if="hasRound('qf')" class="flex flex-col gap-4 flex-1">
                                    <div v-for="m in knockoutMatches.filter(mm => getPosition(mm).startsWith('qf'))" :key="m.id"
                                         class="rounded-lg border text-xs p-2 text-center transition-all"
                                         :class="m.status === 'finished'
                                             ? (m.score1 !== m.score2 ? 'border-green-500/30 bg-green-500/5' : 'border-white/10 bg-white/[0.02]')
                                             : 'border-white/10 bg-white/[0.02] opacity-60'">
                                        <div class="font-medium truncate" :class="m.status === 'finished' && m.score1 > m.score2 ? 'text-green-400' : 'text-white/60'">{{ m.player1?.name || '?' }}</div>
                                        <div class="text-white/20 text-[10px]">VS</div>
                                        <div class="font-medium truncate" :class="m.status === 'finished' && m.score2 > m.score1 ? 'text-green-400' : 'text-white/60'">{{ m.player2?.name || '?' }}</div>
                                    </div>
                                </div>

                                <!-- Arrow QF→SF -->
                                <div v-if="hasRound('qf')" class="flex flex-col items-center justify-center gap-4 pt-8">
                                    <svg class="w-6 h-6 text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>

                                <!-- SF column -->
                                <div v-if="hasRound('sf')" class="flex flex-col gap-8 flex-1">
                                    <div v-for="m in knockoutMatches.filter(mm => getPosition(mm).startsWith('sf'))" :key="m.id"
                                         class="rounded-lg border text-xs p-2 text-center transition-all"
                                         :class="m.status === 'finished'
                                             ? (m.score1 !== m.score2 ? 'border-purple-500/30 bg-purple-500/5' : 'border-white/10 bg-white/[0.02]')
                                             : 'border-white/10 bg-white/[0.02] opacity-60'">
                                        <div class="font-medium truncate" :class="m.status === 'finished' && m.score1 > m.score2 ? 'text-purple-400' : 'text-white/60'">{{ m.player1?.name || '?' }}</div>
                                        <div class="text-white/20 text-[10px]">VS</div>
                                        <div class="font-medium truncate" :class="m.status === 'finished' && m.score2 > m.score1 ? 'text-purple-400' : 'text-white/60'">{{ m.player2?.name || '?' }}</div>
                                    </div>
                                </div>

                                <!-- Arrow SF→Final -->
                                <div v-if="hasRound('sf')" class="flex flex-col items-center justify-center gap-4 pt-16">
                                    <svg class="w-6 h-6 text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>

                                <!-- Final column -->
                                <div class="flex flex-col justify-center flex-1">
                                    <div v-for="m in knockoutMatches.filter(mm => getPosition(mm) === 'final')" :key="m.id"
                                         class="rounded-lg border text-xs p-3 text-center transition-all"
                                         :class="m.status === 'finished'
                                             ? 'border-ucl-gold/30 bg-amber-500/5'
                                             : 'border-white/10 bg-white/[0.02] opacity-60'">
                                        <div v-if="m.status === 'finished'" class="text-lg mb-1">🏆</div>
                                        <div class="font-semibold truncate" :class="m.status === 'finished' && m.score1 > m.score2 ? 'text-ucl-gold' : 'text-white/60'">{{ m.player1?.name || '?' }}</div>
                                        <div class="text-white/20 text-[10px]">VS</div>
                                        <div class="font-semibold truncate" :class="m.status === 'finished' && m.score2 > m.score1 ? 'text-ucl-gold' : 'text-white/60'">{{ m.player2?.name || '?' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </AuthenticatedLayout>
</template>
