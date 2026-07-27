<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    tournament: Object,
    standings: Array,
    rounds: Array,
});

const goldenBoot = computed(() => {
    const sorted = [...props.standings].sort((a, b) => b.gf - a.gf);
    return sorted.length > 0 ? sorted[0] : null;
});

const roundLabels = ['Jornada'];

let intervalId = null;

onMounted(() => {
    intervalId = setInterval(() => {
        router.reload({ only: ['rounds', 'standings'], preserveScroll: true });
    }, 18000);
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});
</script>

<template>
    <div class="min-h-screen bg-elite-bg text-elite-primary font-elite-sans scanline grid-bg">
        <Head :title="`${tournament.name} — Bracket`" />

        <!-- NAV -->
        <nav class="relative z-20 flex items-center justify-between px-4 sm:px-8 py-4 border-b border-elite-outline/30">
            <Link href="/" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-elite-secondary to-orange-700
                            flex items-center justify-center text-black font-bold text-sm shadow-lg shadow-elite-secondary/20">
                    FE
                </div>
                <span class="font-elite-condensed font-bold text-lg tracking-widest text-white/90 hidden sm:block">
                    FIFARDOS ELITE
                </span>
            </Link>
            <div class="flex items-center gap-3">
                <Link href="/rules" class="text-sm text-elite-primary/60 hover:text-elite-primary transition-colors px-3 py-2">
                    REGLAS
                </Link>
                <Link href="/inscribirse"
                      class="text-sm font-bold font-elite-condensed uppercase tracking-wider
                             px-5 py-2 rounded-lg bg-elite-secondary text-black
                             hover:brightness-110 transition-all duration-200">
                    REGISTRARSE
                </Link>
            </div>
        </nav>

        <!-- HERO -->
        <section class="relative z-10 text-center px-4 pt-12 sm:pt-16 pb-8">
            <div class="inline-block glass-panel px-4 py-1.5 mb-4 text-xs font-elite-condensed uppercase tracking-[0.15em] text-elite-secondary">
                {{ tournament.status === 'in_progress' ? 'En Vivo' : tournament.status === 'finished' ? 'Finalizado' : 'Próximamente' }}
            </div>
            <h1 class="font-elite-condensed font-black text-3xl sm:text-5xl lg:text-6xl text-white leading-none mb-3">
                {{ tournament.name }}
            </h1>
            <p class="text-sm text-elite-primary/60 max-w-lg mx-auto">
                {{ tournament.players?.length || 0 }} jugadores &middot; {{ tournament.consoles_count }} consolas &middot; {{ rounds.reduce((a, r) => a + r.length, 0) }} partidos
            </p>
        </section>

        <!-- MAIN CONTENT -->
        <section class="relative z-10 px-4 pb-16 max-w-7xl mx-auto">
            <div class="flex flex-col lg:flex-row gap-6">

                <!-- BRACKET -->
                <div class="flex-1 min-w-0">
                    <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-thin">
                        <div v-for="(round, rIdx) in rounds" :key="rIdx"
                             class="flex-shrink-0 w-[280px] sm:w-[320px] snap-start space-y-3">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="w-8 h-8 rounded-lg bg-elite-secondary/10 border border-elite-secondary/30
                                             flex items-center justify-center text-sm font-bold text-elite-secondary shrink-0">
                                    {{ rIdx + 1 }}
                                </span>
                                <h3 class="font-elite-condensed font-bold text-base uppercase tracking-[0.06em] text-white/70">
                                    {{ roundLabels[0] }} {{ rIdx + 1 }}
                                </h3>
                                <span class="text-[10px] font-elite-condensed text-elite-primary/30 tracking-wider ml-auto">
                                    {{ round.length }}
                                </span>
                            </div>

                            <div v-for="match in round" :key="match.id"
                                 class="glass-panel p-4 space-y-3 transition-all duration-200"
                                 :class="{
                                     'ring-1 ring-elite-secondary/40 shadow-lg shadow-elite-secondary/5': match.status === 'live',
                                 }">
                                <!-- TV + status -->
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-elite-condensed text-elite-primary/40 uppercase tracking-wider flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        TV {{ match.tv_number }}
                                    </span>
                                    <span v-if="match.status === 'live'"
                                          class="flex items-center gap-1.5 text-[10px] font-elite-condensed font-bold uppercase tracking-wider text-red-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse" />
                                        EN VIVO
                                    </span>
                                    <span v-else-if="match.status === 'finished'"
                                          class="text-[10px] font-elite-condensed text-elite-secondary/60 uppercase tracking-wider">
                                        FINAL
                                    </span>
                                    <span v-else
                                          class="text-[10px] font-elite-condensed text-elite-primary/20 uppercase tracking-wider">
                                        PENDIENTE
                                    </span>
                                </div>

                                <!-- Players & Score -->
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex-1 text-right">
                                        <div class="font-elite-condensed font-bold text-sm truncate"
                                             :class="match.status === 'finished' ? (match.score1 > match.score2 ? 'text-white' : 'text-elite-primary/40') : 'text-white/80'">
                                            {{ match.player1?.name || '—' }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <template v-if="match.status === 'finished'">
                                            <span class="min-w-[2rem] text-center text-xl font-elite-condensed font-bold"
                                                  :class="match.score1 > match.score2 ? 'text-elite-secondary' : 'text-elite-primary/30'">
                                                {{ match.score1 }}
                                            </span>
                                            <span class="text-elite-primary/20 font-bold text-sm">:</span>
                                            <span class="min-w-[2rem] text-center text-xl font-elite-condensed font-bold"
                                                  :class="match.score2 > match.score1 ? 'text-elite-secondary' : 'text-elite-primary/30'">
                                                {{ match.score2 }}
                                            </span>
                                        </template>
                                        <template v-else>
                                            <span class="text-elite-primary/20 font-elite-condensed text-xs tracking-widest px-2">VS</span>
                                        </template>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <div class="font-elite-condensed font-bold text-sm truncate"
                                             :class="match.status === 'finished' ? (match.score2 > match.score1 ? 'text-white' : 'text-elite-primary/40') : 'text-white/80'">
                                            {{ match.player2?.name || '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SIDEBAR -->
                <div class="lg:w-80 shrink-0 space-y-6">
                    <!-- Golden Boot -->
                    <div v-if="goldenBoot" class="glass-panel p-5 text-center">
                        <div class="text-3xl mb-2">👑</div>
                        <div class="text-[10px] font-elite-condensed uppercase tracking-[0.15em] text-elite-secondary mb-1">
                            Golden Boot
                        </div>
                        <div class="font-elite-condensed font-black text-xl text-white mb-1">
                            {{ goldenBoot.player_name }}
                        </div>
                        <div class="flex items-center justify-center gap-2">
                            <span class="text-2xl font-elite-condensed font-bold text-elite-secondary">{{ goldenBoot.gf }}</span>
                            <span class="text-xs text-elite-primary/40 font-elite-condensed">GOLES</span>
                        </div>
                    </div>

                    <!-- Standings mini -->
                    <div class="glass-panel p-5">
                        <h3 class="font-elite-condensed font-bold text-sm uppercase tracking-[0.1em] text-white/60 mb-4">
                            CLASIFICACIÓN
                        </h3>
                        <div class="space-y-2">
                            <div v-for="(s, idx) in standings.slice(0, 8)" :key="s.player_id"
                                 class="flex items-center gap-3 text-xs">
                                <span class="w-5 text-center font-elite-condensed font-bold"
                                      :class="idx === 0 ? 'text-elite-secondary' : 'text-elite-primary/30'">
                                    {{ idx + 1 }}
                                </span>
                                <span class="flex-1 truncate font-medium"
                                      :class="idx === 0 ? 'text-white' : 'text-elite-primary/60'">
                                    {{ s.player_name }}
                                </span>
                                <span class="font-elite-condensed font-bold"
                                      :class="idx === 0 ? 'text-elite-secondary' : 'text-elite-primary/40'">
                                    {{ s.pts }}
                                </span>
                                <span class="text-[10px] text-elite-primary/30 w-6 text-right">{{ s.gf }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Polling indicator -->
                    <div class="text-center text-[10px] text-elite-primary/20 font-elite-condensed tracking-wider">
                        Actualizado automáticamente
                    </div>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="relative z-10 border-t border-elite-outline/20 px-4 py-8 text-center text-xs text-elite-primary/30">
            <p class="font-elite-condensed tracking-wider">
                &copy; 2026 FIFARDOS ELITE LEAGUE.
            </p>
        </footer>
    </div>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
    height: 4px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.08);
    border-radius: 2px;
}
</style>
