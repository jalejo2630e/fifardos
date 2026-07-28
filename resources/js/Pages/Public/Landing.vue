<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useScrollReveal } from '@/composables/useScrollReveal';
import ChatBot from '@/Components/ChatBot.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    tournament: Object,
    prizes: Array,
    standings: Array,
});

const { observe: observeHero } = useScrollReveal();
const { observe: observeSteps } = useScrollReveal();
const { observe: observePrizes } = useScrollReveal();
const { observe: observePodium } = useScrollReveal();
</script>

<template>
    <div class="min-h-screen bg-elite-bg text-elite-primary font-elite-sans scanline grid-bg">
        <Head title="FIFARDOS — FIFA Elite League" />

        <!-- NAV -->
        <nav ref="observeHero" class="relative z-20 flex items-center justify-between px-4 sm:px-8 py-4 border-b border-elite-outline/30">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-elite-secondary to-orange-700
                            flex items-center justify-center text-black font-bold text-sm shadow-lg shadow-elite-secondary/20">
                    FE
                </div>
                <span class="font-elite-condensed font-bold text-lg tracking-widest text-white/90 hidden sm:block">
                    FIFARDOS ELITE
                </span>
            </div>
            <div class="flex items-center gap-1 sm:gap-3">
                <Link href="/"
                      class="text-sm text-elite-primary/60 hover:text-elite-primary transition-colors px-2 sm:px-3 py-2">
                    INICIO
                </Link>
                <Link :href="route('players.public.create')"
                      class="text-sm text-elite-primary/60 hover:text-elite-primary transition-colors px-2 sm:px-3 py-2">
                    REGISTRO
                </Link>
                <Link href="/rules"
                      class="text-sm text-elite-primary/60 hover:text-elite-primary transition-colors px-2 sm:px-3 py-2">
                    EQUIPOS
                </Link>
                <Link v-if="canLogin" :href="route('login')"
                      class="text-sm text-elite-primary/60 hover:text-elite-primary transition-colors px-2 sm:px-3 py-2">
                    INICIAR SESIÓN
                </Link>

            </div>
        </nav>

        <!-- HERO -->
        <section ref="observeHero" class="relative z-10 flex flex-col items-center justify-center text-center px-4 pt-20 sm:pt-28 pb-16 sm:pb-24">
            <div class="max-w-4xl mx-auto">
                <div class="inline-block glass-panel px-4 py-1.5 mb-6 text-xs font-elite-condensed uppercase tracking-[0.15em] text-elite-secondary">
                    Temporada 2026
                </div>

                <h1 class="font-elite-condensed font-black text-5xl sm:text-7xl lg:text-8xl leading-none text-white mb-5">
                    FIFA<br class="sm:hidden"/>
                    <span class="text-elite-secondary">ELITE</span> LEAGUE
                </h1>

                <p class="text-sm sm:text-base text-elite-primary/60 max-w-lg mx-auto mb-10 leading-relaxed">
                    La competencia FIFA definitiva. Demuestra tu talento, domina el torneo
                    y reclama la gloria.
                </p>

                <Link :href="route('register')"
                      class="inline-flex items-center gap-2 px-8 sm:px-12 py-4 sm:py-5 rounded-xl
                             bg-elite-secondary text-black font-bold font-elite-condensed
                             text-base sm:text-lg uppercase tracking-widest
                             shadow-lg shadow-elite-secondary/30
                             hover:brightness-110 hover:shadow-xl hover:shadow-elite-secondary/40
                             active:brightness-90 transition-all duration-200">
                    REGISTER FOR FREE
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </Link>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section ref="observeSteps" class="relative z-10 px-4 py-16 sm:py-24 max-w-6xl mx-auto">
            <div class="text-center mb-12 sm:mb-16">
                <div class="inline-block glass-panel px-4 py-1.5 mb-4 text-xs font-elite-condensed uppercase tracking-[0.15em] text-elite-secondary">
                    Cómo funciona
                </div>
                <h2 class="font-elite-condensed font-black text-3xl sm:text-5xl text-white">
                    HOW IT <span class="text-elite-secondary">WORKS</span>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                <div v-for="(step, i) in [
                    { num: '01', title: 'REGÍSTRATE', desc: 'Crea tu cuenta gratis y únete a la liga. Sin barreras, solo tú y tu talento.' },
                    { num: '02', title: 'COMPITE', desc: 'Participa en torneos online, enfréntate a los mejores y escala posiciones.' },
                    { num: '03', title: 'RECLAMA', desc: 'Los mejores jugadores se llevan premios, gloria y un lugar en la historia.' },
                ]" :key="i"
                     class="glass-panel p-6 sm:p-8 text-center hover:border-elite-secondary/40 transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-full bg-elite-secondary/10 border border-elite-secondary/30
                                flex items-center justify-center mx-auto mb-5
                                text-elite-secondary font-elite-condensed font-bold text-lg
                                group-hover:bg-elite-secondary/20 group-hover:scale-110 transition-all duration-300">
                        {{ step.num }}
                    </div>
                    <h3 class="font-elite-condensed font-bold text-xl text-white mb-3 tracking-wider">
                        {{ step.title }}
                    </h3>
                    <p class="text-sm text-elite-primary/50 leading-relaxed">
                        {{ step.desc }}
                    </p>
                </div>
            </div>
        </section>

        <!-- PRIZE POOL -->
        <section ref="observePrizes" class="relative z-10 px-4 py-16 sm:py-24 max-w-5xl mx-auto text-center">
            <div class="inline-block glass-panel px-4 py-1.5 mb-4 text-xs font-elite-condensed uppercase tracking-[0.15em] text-elite-secondary">
                Premios
            </div>
            <h2 class="font-elite-condensed font-black text-3xl sm:text-5xl text-white mb-12 sm:mb-16">
                PRIZE <span class="text-elite-secondary">POOL</span>
            </h2>

            <div v-if="prizes && prizes.length" class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                <div v-for="(prize, i) in prizes" :key="prize.id"
                     class="glass-panel p-6 sm:p-8 relative overflow-hidden transition-all duration-300"
                     :class="[prize.is_featured ? 'glow-secondary' : '', { 'scale-105 sm:scale-110': prize.is_featured }]">
                    <div class="font-elite-condensed font-black text-4xl sm:text-5xl text-white mb-2">
                        {{ prize.position }}º
                    </div>
                    <div class="font-elite-mono font-bold text-2xl sm:text-3xl mb-3"
                         :class="prize.is_featured ? 'text-elite-secondary' : 'text-white/60'">
                        {{ prize.amount }}
                    </div>
                    <div class="text-xs text-elite-primary/40 uppercase tracking-wider font-elite-condensed">
                        {{ prize.label }}
                    </div>
                    <div v-if="prize.perks && prize.perks.length" class="mt-3 space-y-1">
                        <div v-for="(perk, pi) in prize.perks" :key="pi"
                             class="text-[10px] text-elite-primary/30 uppercase tracking-wider">
                            • {{ perk }}
                        </div>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-elite-primary/40">Próximamente</p>
        </section>

        <!-- PODIUM (visible when tournament is completed) -->
        <section ref="observePodium" v-if="tournament && tournament.status === 'completed'" class="relative z-10 px-4 py-16 sm:py-24 max-w-5xl mx-auto text-center">
            <div class="inline-block glass-panel px-4 py-1.5 mb-4 text-xs font-elite-condensed uppercase tracking-[0.15em] text-elite-secondary">
                Torneo Finalizado
            </div>
            <h2 class="font-elite-condensed font-black text-3xl sm:text-5xl text-white mb-12 sm:mb-16">
                FINAL <span class="text-elite-secondary">STANDINGS</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                <div v-for="(player, i) in standings.slice(0, 3)" :key="player.player_id"
                     class="glass-panel p-6 sm:p-8 relative overflow-hidden transition-all duration-300"
                     :class="[i === 0 ? 'glow-secondary scale-105 sm:scale-110' : '', { 'border-elite-secondary/30': i === 0 }]">
                    <div class="text-4xl sm:text-5xl mb-2">
                        {{ i === 0 ? '🏆' : i === 1 ? '🥈' : '🥉' }}
                    </div>
                    <div class="font-elite-condensed font-black text-2xl sm:text-3xl text-white mb-1">
                        {{ player.player_name }}
                    </div>
                    <div class="font-elite-mono font-bold text-3xl sm:text-4xl mb-4"
                         :class="i === 0 ? 'text-elite-secondary' : 'text-white/60'">
                        {{ player.pts }} <span class="text-xs text-elite-primary/40">pts</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-xs text-elite-primary/50">
                        <div><span class="text-white/80">{{ player.pg }}</span> G</div>
                        <div><span class="text-white/80">{{ player.pe }}</span> E</div>
                        <div><span class="text-white/80">{{ player.pp }}</span> P</div>
                        <div><span class="text-white/80">{{ player.gf }}</span> GF</div>
                        <div><span class="text-white/80">{{ player.gc }}</span> GC</div>
                        <div><span class="text-white/80">{{ player.dg }}</span> DG</div>
                    </div>
                </div>
            </div>

            <div v-if="standings.length > 3" class="mt-10 max-w-2xl mx-auto">
                <div class="glass-panel overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-elite-outline/20 text-xs text-elite-primary/40 uppercase tracking-wider">
                                <th class="py-3 px-4 text-left">#</th>
                                <th class="py-3 px-4 text-left">Jugador</th>
                                <th class="py-3 px-4 text-center">Pts</th>
                                <th class="py-3 px-4 text-center">PJ</th>
                                <th class="py-3 px-4 text-center">DG</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(player, i) in standings" :key="player.player_id"
                                class="border-b border-elite-outline/10 text-elite-primary/70"
                                :class="{ 'bg-elite-secondary/5': i < 3 }">
                                <td class="py-2.5 px-4 text-left font-elite-mono">{{ i + 1 }}</td>
                                <td class="py-2.5 px-4 text-left font-medium text-white/90">{{ player.player_name }}</td>
                                <td class="py-2.5 px-4 text-center font-elite-mono">{{ player.pts }}</td>
                                <td class="py-2.5 px-4 text-center font-elite-mono">{{ player.pj }}</td>
                                <td class="py-2.5 px-4 text-center font-elite-mono">{{ player.dg }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <ChatBot />

        <!-- FOOTER -->
        <footer class="relative z-10 border-t border-elite-outline/20 px-4 py-8 text-center text-xs text-elite-primary/30">
            <p class="font-elite-condensed tracking-wider">
                &copy; 2026 FIFARDOS ELITE LEAGUE. Todos los derechos reservados.
            </p>
        </footer>
    </div>
</template>

<style scoped>
.revealed {
    animation: fadeSlideUp 0.6s ease forwards;
}

@keyframes fadeSlideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
