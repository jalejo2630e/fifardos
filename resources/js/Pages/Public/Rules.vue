<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useScrollReveal } from '@/composables/useScrollReveal';
import { useMouseSpotlight } from '@/composables/useMouseSpotlight';

defineProps({
    prizes: Array,
});

const { observe: observeHero } = useScrollReveal();
const { observe: observePrizes } = useScrollReveal();

const { x: sx, y: sy, onMove, onEnter, onLeave } = useMouseSpotlight();
</script>

<template>
    <div class="min-h-screen bg-elite-bg text-elite-primary font-elite-sans scanline grid-bg">
        <Head title="Reglas y premios" />

        <!-- NAV -->
        <nav class="relative z-20 flex items-center justify-between px-4 sm:px-8 py-4 border-b border-elite-outline/30">
            <Link href="/" class="flex items-center gap-3">
                <img src="/brand/icon.png" alt="FIFARDOS"
                     class="w-9 h-9 rounded-xl shadow-lg shadow-elite-secondary/20" />
                <span class="font-elite-condensed font-bold text-lg tracking-widest text-white/90 hidden sm:block">
                    FIFA<span class="text-elite-secondary">RDOS</span>
                </span>
            </Link>
            <div class="flex items-center gap-3">
                <Link href="/" class="text-sm text-elite-primary/60 hover:text-elite-primary transition-colors px-3 py-2">
                    INICIO
                </Link>
                <Link :href="route('register')"
                      class="text-sm font-bold font-elite-condensed uppercase tracking-wider
                             px-5 py-2 rounded-lg bg-elite-secondary text-black
                             hover:brightness-110 transition-all duration-200">
                    REGISTRARSE
                </Link>
            </div>
        </nav>

        <!-- HERO -->
        <section ref="observeHero" class="relative z-10 text-center px-4 pt-16 sm:pt-24 pb-12">
            <div class="inline-block glass-panel px-4 py-1.5 mb-4 text-xs font-elite-condensed uppercase tracking-[0.15em] text-elite-secondary">
                Guía de torneos
            </div>
            <h1 class="font-elite-condensed font-black text-4xl sm:text-6xl lg:text-7xl text-white leading-none mb-4">
                REGLAS Y<br class="sm:hidden"/>
                <span class="text-elite-secondary"> PREMIOS</span>
            </h1>
            <p class="text-sm sm:text-base text-elite-primary/60 max-w-lg mx-auto">
                FIFARDOS organiza torneos de <strong class="text-elite-primary/80">cualquier deporte</strong>:
                videojuego de consola o deporte real (fútbol, básquet, vóley, tenis y más).
                Tú defines las reglas; nosotros armamos el fixture, la tabla y las eliminatorias.
            </p>
        </section>

        <!-- RULES -->
        <section ref="observeHero" class="relative z-10 px-4 pb-16 max-w-4xl mx-auto">
            <div class="glass-panel p-6 sm:p-8 md:p-10 space-y-8">
                <div v-for="(rule, i) in [
                    {
                        title: 'PARA CUALQUIER DEPORTE',
                        items: [
                            'FIFARDOS sirve para deportes individuales (tenis, padel, ping pong) y de equipo (fútbol, básquet, vóley, handball, rugby y más), en cancha física o modo virtual.',
                            'Funciona para ligas entre amigos, torneos de oficina, del barrio, de la escuela o eventos.',
                            'El organizador define el formato, la duración y las reglas de cada partido.',
                            'FIFARDOS se encarga del fixture, la tabla de posiciones, los líderes de cada deporte y las eliminatorias.',
                        ],
                    },
                    {
                        title: 'FORMATO DEL TORNEO',
                        items: [
                            'Fase de grupos Round Robin: todos contra todos dentro del grupo.',
                            'Los mejores de cada grupo avanzan a la fase eliminatoria.',
                            'Eliminatorias a partido único, con desempate (tiempo extra y/o penales) si hace falta.',
                            'La cantidad de consolas o canchas define cuántos partidos se juegan en paralelo.',
                        ],
                    },
                    {
                        title: 'REGLAS DE PARTIDO (EJEMPLO)',
                        items: [
                            'Estas son reglas de ejemplo; el organizador las adapta a su torneo y deporte.',
                            'Duración: depende del deporte y del modo (virtual o campo físico).',
                            'Empate en eliminatorias: se resuelve según las reglas configuradas del torneo (desempate, extra time, etc.).',
                            'Amonestaciones (opcional): dos amarillas = suspensión de 1 partido.',
                        ],
                    },
                    {
                        title: 'CÓDIGO DE CONDUCTA',
                        items: [
                            'Respeto total hacia los rivales y los organizadores.',
                            'Prohibido interrumpir el juego de mala fe.',
                            'Cualquier forma de trampa resulta en descalificación inmediata.',
                            'Los organizadores tienen la decisión final en cualquier controversia.',
                        ],
                    },
                ]" :key="i"
                     class="last:mb-0">
                    <h3 class="font-elite-condensed font-bold text-lg sm:text-xl text-white mb-4 tracking-wider flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-elite-secondary/10 border border-elite-secondary/30
                                    flex items-center justify-center text-sm font-bold text-elite-secondary shrink-0">
                            {{ String(i + 1).padStart(2, '0') }}
                        </span>
                        {{ rule.title }}
                    </h3>
                    <ul class="space-y-2.5">
                        <li v-for="(item, j) in rule.items" :key="j"
                            class="flex items-start gap-3 text-sm text-elite-primary/60 leading-relaxed">
                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-elite-secondary/50 shrink-0" />
                            {{ item }}
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- PRIZES -->
        <section ref="observePrizes" class="relative z-10 px-4 pb-16 sm:pb-24 max-w-5xl mx-auto">
            <div class="text-center mb-10 sm:mb-14">
                <div class="inline-block glass-panel px-4 py-1.5 mb-4 text-xs font-elite-condensed uppercase tracking-[0.15em] text-elite-secondary">
                    Prize Pool
                </div>
                <h2 class="font-elite-condensed font-black text-3xl sm:text-5xl text-white">
                    PREMIOS <span class="text-elite-secondary">TOTALES</span>
                </h2>
            </div>

            <!-- Empty state -->
            <div v-if="prizes.length === 0"
                 class="glass-panel p-10 text-center">
                <p class="text-elite-primary/50 font-elite-condensed text-lg tracking-wider">
                    PRIZES COMING SOON
                </p>
                <p class="text-xs text-elite-primary/30 mt-2">
                    Los premios de esta temporada se anunciarán próximamente.
                </p>
            </div>

            <!-- Prize cards -->
            <div v-else
                 class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 justify-items-center"
                 :class="{ 'lg:grid-cols-2': prizes.length === 2 }">

                <div v-for="(prize, i) in prizes" :key="prize.id"
                     @mouseenter="onEnter"
                     @mousemove="onMove"
                     @mouseleave="onLeave"
                     class="glass-panel p-6 sm:p-8 text-center relative overflow-hidden
                            transition-all duration-300 hover:-translate-y-1 w-full"
                     :class="{
                         'lg:col-span-2 lg:max-w-md': prize.is_featured,
                         'glow-secondary': prize.is_featured,
                     }"
                     :style="prize.is_featured ? {
                         background: `radial-gradient(600px circle at ${sx}% ${sy}%, rgba(255,95,0,0.08), transparent 40%)`,
                     } : {}">

                    <div v-if="prize.is_featured"
                         class="absolute -top-4 -right-4 w-16 h-16 bg-elite-secondary/10 rounded-full blur-xl" />

                    <!-- Icon -->
                    <div v-if="prize.icon"
                         class="w-12 h-12 rounded-full bg-elite-secondary/10 border border-elite-secondary/30
                                flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-elite-secondary text-2xl">{{ prize.icon }}</span>
                    </div>

                    <!-- Position -->
                    <div class="font-elite-condensed font-black text-4xl sm:text-5xl text-white mb-1"
                         :class="{ 'text-elite-secondary': prize.is_featured }">
                        {{ prize.position }}º
                    </div>

                    <!-- Label -->
                    <div class="font-elite-condensed font-bold text-sm uppercase tracking-[0.15em]
                                text-white/50 mb-4">
                        {{ prize.label }}
                    </div>

                    <!-- Amount -->
                    <div class="font-elite-mono font-bold text-2xl sm:text-3xl mb-5"
                         :class="prize.is_featured ? 'text-white' : 'text-elite-primary/80'">
                        {{ prize.amount }}
                    </div>

                    <!-- Perks -->
                    <ul v-if="prize.perks?.length" class="space-y-2 text-left max-w-[220px] mx-auto">
                        <li v-for="(perk, j) in prize.perks" :key="j"
                            class="flex items-center gap-2 text-xs sm:text-sm text-elite-primary/50">
                            <svg class="w-3.5 h-3.5 text-elite-secondary/70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ perk }}
                        </li>
                    </ul>
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
