<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';

const props = defineProps({
    tournament: Object,
    standings: Array,
    allPlayed: Boolean,
    rounds: Array,
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
