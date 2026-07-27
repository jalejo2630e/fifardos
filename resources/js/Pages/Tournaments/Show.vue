<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    tournament: Object,
    standings: Array,
    allPlayed: Boolean,
    rounds: Array,
});

const activeTab = ref('matches');
const editingScore = ref(null);

// Local reactive copy for score editing
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

function startEdit(match) {
    editingScore.value = match.id;
}

function confirmEdit(match) {
    router.post(route('matches.score.edit', [props.tournament.id, match.id]));
    editingScore.value = null;
}

// Computed stats
const totalMatches = computed(() => {
    let count = 0;
    props.rounds.forEach(r => count += r.length);
    return count;
});

const playedMatches = computed(() => {
    let count = 0;
    props.rounds.forEach(r => r.forEach(m => { if (m.played) count++; }));
    return count;
});

const progressPercent = computed(() => {
    if (totalMatches.value === 0) return 0;
    return Math.round((playedMatches.value / totalMatches.value) * 100);
});

const champion = computed(() => {
    if (props.allPlayed && props.standings.length > 0) return props.standings[0];
    return null;
});
</script>

<template>
    <Head :title="tournament.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-black font-display tracking-tight">
                            <span class="text-gaming-cyan neon-text">{{ tournament.name }}</span>
                        </h1>
                        <span v-if="tournament.status === 'completed'"
                              class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-gaming-gold/20 text-gaming-gold border border-gaming-gold/30">
                            🏆 Finalizado
                        </span>
                        <span v-else
                              class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-gaming-cyan/20 text-gaming-cyan border border-gaming-cyan/30">
                            ⚔️ En Curso
                        </span>
                    </div>
                    <div class="flex items-center gap-4 mt-1 text-sm text-white/40">
                        <span>{{ tournament.players.length }} jugadores</span>
                        <span class="w-1 h-1 rounded-full bg-white/20"></span>
                        <span>{{ tournament.consoles_count }} TV(s)</span>
                        <span class="w-1 h-1 rounded-full bg-white/20"></span>
                        <span>{{ totalMatches }} partidos</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('dashboard')"
                          class="btn-ghost text-sm py-2.5 px-4 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver
                    </Link>
                    <Link :href="route('tournaments.destroy', tournament.id)"
                          method="delete" as="button"
                          class="btn-danger text-sm py-2.5 px-4"
                          onclick="return confirm('¿Eliminar este torneo? Se borrarán todos los datos.')">
                        Eliminar
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">

                <!-- Progress Bar -->
                <div v-if="!allPlayed" class="glass-card p-6 animate-fade-up">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="text-white/60">Progreso del torneo</span>
                            <span class="font-bold text-gaming-cyan">{{ playedMatches }}/{{ totalMatches }}</span>
                            <span class="text-white/40">partidos jugados</span>
                        </div>
                        <span class="text-sm font-bold font-display text-gaming-cyan">{{ progressPercent }}%</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-white/10 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-gaming-cyan via-gaming-purple to-gaming-gold transition-all duration-700 ease-out"
                             :style="{ width: progressPercent + '%' }">
                        </div>
                    </div>
                </div>

                <!-- Champion Banner -->
                <div v-if="champion" class="champion-banner animate-fade-up">
                    <div class="relative z-10">
                        <div class="text-6xl mb-4">🏆</div>
                        <h2 class="text-3xl font-black font-display gold-text text-gaming-gold mb-2">
                            ¡Tenemos un Campeón!
                        </h2>
                        <div class="text-5xl font-black font-display text-white gold-text mt-3 mb-4">
                            {{ champion.player_name }}
                        </div>
                        <div class="flex items-center justify-center gap-6 text-sm text-white/60">
                            <span>{{ champion.pts }} PTS</span>
                            <span class="w-1 h-1 rounded-full bg-white/20"></span>
                            <span>{{ champion.pg }}G {{ champion.pe }}E {{ champion.pp }}P</span>
                            <span class="w-1 h-1 rounded-full bg-white/20"></span>
                            <span>DG: {{ champion.dg > 0 ? '+' : '' }}{{ champion.dg }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="glass-card p-1.5 flex gap-1 animate-fade-up">
                    <button @click="activeTab = 'matches'"
                            class="tab-gaming flex-1 text-center"
                            :class="activeTab === 'matches' ? 'active' : 'text-white/40 hover:text-white/70'">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Partidos
                        </div>
                    </button>
                    <button @click="activeTab = 'standings'"
                            class="tab-gaming flex-1 text-center"
                            :class="activeTab === 'standings' ? 'active' : 'text-white/40 hover:text-white/70'">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Clasificación
                        </div>
                    </button>
                </div>

                <!-- ============ MATCHES VIEW ============ -->
                <div v-if="activeTab === 'matches'" class="space-y-10">
                    <div v-for="(round, rIdx) in localRounds" :key="rIdx" class="animate-fade-up">
                        <!-- Round header -->
                        <div class="flex items-center gap-4 mb-5">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-gaming-cyan/30 to-gaming-purple/30 border border-gaming-cyan/20 flex items-center justify-center text-sm font-black text-gaming-cyan">
                                    {{ rIdx + 1 }}
                                </span>
                                <h3 class="text-lg font-bold font-display text-white">Jornada {{ rIdx + 1 }}</h3>
                            </div>
                            <div class="flex-1 h-px bg-gradient-to-r from-white/10 to-transparent"></div>
                            <span class="text-xs text-white/30">{{ round.length }} partido(s)</span>
                        </div>

                        <!-- Match grid -->
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <div v-for="match in round" :key="match.id"
                                 class="match-card"
                                 :class="{
                                     'played': match.played,
                                     'winner-highlight': allPlayed && ((match.score1 > match.score2 && match.player1.id === champion.player_id) || (match.score2 > match.score1 && match.player2.id === champion.player_id))
                                 }">
                                <!-- TV Badge -->
                                <div class="flex items-center justify-between mb-3">
                                    <span class="badge-tv">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        TV {{ match.tv_number }}
                                    </span>
                                    <span v-if="match.played && !allPlayed"
                                          class="text-xs font-bold text-gaming-green">✓ Finalizado</span>
                                    <span v-else-if="allPlayed"
                                          class="text-xs font-bold text-gaming-gold">🏁</span>
                                    <span v-else class="text-xs text-white/20">Pendiente</span>
                                </div>

                                <!-- Match content -->
                                <div class="flex items-center justify-between gap-3 py-2">
                                    <!-- Player 1 -->
                                    <div class="flex-1 text-right">
                                        <div class="text-base font-bold font-display"
                                             :class="match.played
                                                ? (match.score1 > match.score2 ? 'text-gaming-green' : match.score1 < match.score2 ? 'text-white/50' : 'text-white')
                                                : 'text-white'">
                                            {{ match.player1.name }}
                                        </div>
                                    </div>

                                    <!-- Score -->
                                    <div class="flex items-center gap-3 shrink-0">
                                        <template v-if="!match.played">
                                            <input type="number" min="0"
                                                   class="score-input"
                                                   v-model.number="match.score1"
                                                   :disabled="editingScore !== null && editingScore !== match.id" />
                                            <span class="text-lg font-black text-white/30">:</span>
                                            <input type="number" min="0"
                                                   class="score-input"
                                                   v-model.number="match.score2"
                                                   :disabled="editingScore !== null && editingScore !== match.id" />
                                        </template>
                                        <template v-else>
                                            <span class="min-w-[3.5rem] text-center text-3xl font-black font-display"
                                                  :class="match.score1 > match.score2 ? 'text-gaming-green' : match.score1 < match.score2 ? 'text-gaming-red' : 'text-white'">
                                                {{ match.score1 }}
                                            </span>
                                            <span class="text-lg font-black text-white/20">:</span>
                                            <span class="min-w-[3.5rem] text-center text-3xl font-black font-display"
                                                  :class="match.score2 > match.score1 ? 'text-gaming-green' : match.score2 < match.score1 ? 'text-gaming-red' : 'text-white'">
                                                {{ match.score2 }}
                                            </span>
                                        </template>
                                    </div>

                                    <!-- Player 2 -->
                                    <div class="flex-1 text-left">
                                        <div class="text-base font-bold font-display"
                                             :class="match.played
                                                ? (match.score2 > match.score1 ? 'text-gaming-green' : match.score2 < match.score1 ? 'text-white/50' : 'text-white')
                                                : 'text-white'">
                                            {{ match.player2.name }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="mt-4 pt-3 border-t border-white/5">
                                    <button v-if="!match.played && editingScore !== match.id && match.score1 >= 0 && match.score2 >= 0"
                                            @click="saveScore(match)"
                                            class="btn-success w-full text-sm py-2.5 flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Guardar Resultado
                                    </button>
                                    <div v-else-if="!match.played && editingScore !== match.id"
                                         class="text-center text-xs text-white/20 py-1">
                                        Introduce los goles de ambos jugadores
                                    </div>
                                    <button v-if="match.played"
                                            @click="confirmEdit(match)"
                                            class="btn-warning w-full text-sm py-2.5 flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Editar Resultado
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============ STANDINGS VIEW ============ -->
                <div v-if="activeTab === 'standings'"
                     class="glass-card overflow-hidden animate-fade-up">
                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="standings-table w-full">
                            <thead>
                                <tr class="border-b border-white/10 bg-white/5">
                                    <th class="text-left">#</th>
                                    <th class="text-left">Jugador</th>
                                    <th class="text-center">PTS</th>
                                    <th class="text-center">PJ</th>
                                    <th class="text-center">PG</th>
                                    <th class="text-center">PE</th>
                                    <th class="text-center">PP</th>
                                    <th class="text-center">GF</th>
                                    <th class="text-center">GC</th>
                                    <th class="text-center">DG</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(s, idx) in standings" :key="s.player_id"
                                    class="border-b border-white/5 transition-colors hover:bg-white/5"
                                    :class="{
                                        'bg-gradient-to-r from-gaming-gold/10 to-transparent': idx === 0 && allPlayed,
                                        'bg-gaming-cyan/5': idx === 0 && !allPlayed
                                    }">
                                    <td class="text-center font-bold text-lg"
                                        :class="idx === 0 && allPlayed ? 'text-gaming-gold gold-text' : idx === 0 ? 'text-gaming-cyan' : 'text-white/40'">
                                        <span v-if="idx === 0 && allPlayed">👑</span>
                                        <span v-else>{{ idx + 1 }}</span>
                                    </td>
                                    <td class="font-bold font-display"
                                        :class="idx === 0 && allPlayed ? 'text-gaming-gold text-lg' : idx === 0 ? 'text-gaming-cyan' : 'text-white'">
                                        {{ s.player_name }}
                                    </td>
                                    <td class="text-center font-black text-xl font-display"
                                        :class="idx === 0 && allPlayed ? 'text-gaming-gold gold-text' : idx === 0 ? 'text-gaming-cyan' : 'text-white'">
                                        {{ s.pts }}
                                    </td>
                                    <td class="text-center text-white/60">{{ s.pj }}</td>
                                    <td class="text-center"
                                        :class="s.pg > 0 ? 'text-gaming-green' : 'text-white/40'">{{ s.pg }}</td>
                                    <td class="text-center text-white/60">{{ s.pe }}</td>
                                    <td class="text-center"
                                        :class="s.pp > 0 ? 'text-gaming-red' : 'text-white/40'">{{ s.pp }}</td>
                                    <td class="text-center text-white/80 font-semibold">{{ s.gf }}</td>
                                    <td class="text-center text-white/80 font-semibold">{{ s.gc }}</td>
                                    <td class="text-center font-bold"
                                        :class="s.dg > 0 ? 'text-gaming-green' : s.dg < 0 ? 'text-gaming-red' : 'text-white/40'">
                                        {{ s.dg > 0 ? '+' : '' }}{{ s.dg }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Legend -->
                    <div class="p-4 border-t border-white/5 flex flex-wrap gap-4 text-xs text-white/30">
                        <span>PTS = Puntos</span>
                        <span>PJ = Partidos Jugados</span>
                        <span>PG = Partidos Ganados</span>
                        <span>PE = Partidos Empatados</span>
                        <span>PP = Partidos Perdidos</span>
                        <span>GF = Goles a Favor</span>
                        <span>GC = Goles en Contra</span>
                        <span>DG = Diferencia de Goles</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
