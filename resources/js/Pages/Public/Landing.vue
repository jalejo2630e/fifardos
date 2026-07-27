<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { useScrollReveal } from '@/composables/useScrollReveal';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const TARGET = '2026-09-15T00:00:00';

const timeLeft = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
let timer = null;

function calc() {
    const diff = new Date(TARGET) - new Date();
    if (diff <= 0) return;
    timeLeft.value = {
        days: String(Math.floor(diff / 86400000)).padStart(2, '0'),
        hours: String(Math.floor((diff / 3600000) % 24)).padStart(2, '0'),
        minutes: String(Math.floor((diff / 60000) % 60)).padStart(2, '0'),
        seconds: String(Math.floor((diff / 1000) % 60)).padStart(2, '0'),
    };
}

onMounted(() => {
    calc();
    timer = setInterval(calc, 1000);
});

onUnmounted(() => {
    clearInterval(timer);
});

const { observe: observeHero } = useScrollReveal();
const { observe: observeSteps } = useScrollReveal();
const { observe: observePrizes } = useScrollReveal();
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
            <div class="flex items-center gap-3">
                <Link v-if="canLogin" :href="route('login')"
                      class="text-sm text-elite-primary/60 hover:text-elite-primary transition-colors px-3 py-2">
                    INICIAR SESIÓN
                </Link>
                <Link v-if="canRegister" :href="route('register')"
                      class="text-sm font-bold font-elite-condensed uppercase tracking-wider
                             px-5 py-2 rounded-lg bg-elite-secondary text-black
                             hover:brightness-110 transition-all duration-200">
                    REGISTRARSE
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

                <!-- Countdown -->
                <div class="glass-panel inline-flex gap-3 sm:gap-5 p-4 sm:p-6 mb-10 glow-secondary">
                    <div v-for="unit in ['DAYS','HOURS','MINUTES','SECONDS']" :key="unit"
                         class="flex flex-col items-center min-w-[60px] sm:min-w-[80px]">
                        <span class="font-elite-mono font-bold text-3xl sm:text-4xl lg:text-5xl text-white tabular-nums leading-none">
                            {{ timeLeft[unit.toLowerCase()] }}
                        </span>
                        <span class="text-[10px] sm:text-xs font-elite-condensed uppercase tracking-[0.15em] text-elite-secondary mt-2">
                            {{ unit }}
                        </span>
                    </div>
                </div>

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

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                <div v-for="(prize, i) in [
                    { place: '2º', amount: '$500', color: 'border-white/20', text: 'text-white/60', bg: '' },
                    { place: '1º', amount: '$1,000', color: 'border-elite-secondary/50', text: 'text-elite-secondary', bg: 'glow-secondary' },
                    { place: '3º', amount: '$250', color: 'border-white/20', text: 'text-white/60', bg: '' },
                ]" :key="i"
                     class="glass-panel p-6 sm:p-8 relative overflow-hidden transition-all duration-300"
                     :class="[prize.bg, { 'scale-105 sm:scale-110': i === 1 }]">
                    <div class="font-elite-condensed font-black text-4xl sm:text-5xl text-white mb-2">
                        {{ prize.place }}
                    </div>
                    <div class="font-elite-mono font-bold text-2xl sm:text-3xl mb-3" :class="prize.text">
                        {{ prize.amount }}
                    </div>
                    <div class="text-xs text-elite-primary/40 uppercase tracking-wider font-elite-condensed">
                        {{ i === 0 ? 'Subcampeón' : i === 1 ? 'Campeón' : 'Tercer Lugar' }}
                    </div>
                </div>
            </div>
        </section>

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
