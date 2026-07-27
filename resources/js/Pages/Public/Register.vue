<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useScrollReveal } from '@/composables/useScrollReveal';
import { useMouseSpotlight } from '@/composables/useMouseSpotlight';

const props = defineProps({
    tournaments: Array,
});

const { observe: observeHero } = useScrollReveal();
const { x: sx, y: sy, onMove, onEnter, onLeave } = useMouseSpotlight();

const teams = [
    'Real Madrid', 'FC Barcelona', 'Manchester City', 'Manchester United',
    'Liverpool', 'Arsenal', 'Chelsea', 'Tottenham', 'Paris Saint-Germain',
    'Bayern Munich', 'Borussia Dortmund', 'Juventus', 'AC Milan', 'Inter Milan',
    'Napoli', 'Atlético de Madrid', 'Ajax', 'Sporting CP', 'Benfica', 'Porto',
];

const form = useForm({
    tournament_id: '',
    name: '',
    psn_id: '',
    email: '',
    preferred_team: '',
});

function submit() {
    form.post(route('players.public.store'));
}
</script>

<template>
    <div class="min-h-screen bg-elite-bg text-elite-primary font-elite-sans scanline grid-bg">
        <Head title="FIFARDOS — Registro" />

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
            <Link href="/" class="text-sm text-elite-primary/60 hover:text-elite-primary transition-colors px-3 py-2">
                INICIO
            </Link>
        </nav>

        <!-- HERO -->
        <section ref="observeHero" class="relative z-10 text-center px-4 pt-16 sm:pt-24 pb-10">
            <div class="inline-block glass-panel px-4 py-1.5 mb-4 text-xs font-elite-condensed uppercase tracking-[0.15em] text-elite-secondary">
                Inscripción
            </div>
            <h1 class="font-elite-condensed font-black text-4xl sm:text-6xl text-white leading-none mb-3">
                REGÍSTRATE EN<br class="sm:hidden"/>
                <span class="text-elite-secondary"> LA LIGA</span>
            </h1>
            <p class="text-sm text-elite-primary/50 max-w-md mx-auto">
                Completa tus datos para unirte a la competencia
            </p>
        </section>

        <!-- SUCCESS STATE -->
        <section v-if="form.wasSuccessful" class="relative z-10 px-4 pb-24">
            <div class="max-w-md mx-auto glass-panel p-10 text-center glow-secondary">
                <div class="w-16 h-16 rounded-full bg-elite-secondary/20 border border-elite-secondary/40
                            flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-elite-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="font-elite-mono font-bold text-xl text-white tracking-wider mb-3">
                    TRANSMISSION RECEIVED
                </p>
                <p class="text-sm text-elite-primary/50 leading-relaxed">
                    Tu solicitud ha sido registrada. Te contactaremos con los detalles del torneo.
                </p>
                <Link href="/" class="inline-block mt-8 text-sm text-elite-secondary hover:text-white transition-colors font-elite-condensed uppercase tracking-wider">
                    Volver al inicio
                </Link>
            </div>
        </section>

        <!-- FORM -->
        <section v-else ref="observeHero" class="relative z-10 px-4 pb-24">
            <div class="max-w-md mx-auto">
                <div class="glass-panel p-6 sm:p-8 relative overflow-hidden"
                     @mouseenter="onEnter"
                     @mousemove="onMove"
                     @mouseleave="onLeave"
                     :style="{
                         background: `radial-gradient(600px circle at ${sx}% ${sy}%, rgba(255,95,0,0.06), transparent 40%)`,
                     }">

                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Tournament selector -->
                        <div>
                            <label class="block text-xs font-elite-condensed uppercase tracking-[0.12em] text-elite-primary/60 mb-1.5">
                                ¿A qué torneo te unes?
                            </label>
                            <div class="relative">
                                <select v-model="form.tournament_id"
                                        class="w-full bg-elite-surface-low border border-elite-outline/40 rounded-lg
                                               px-4 py-3 text-sm text-white
                                               focus:outline-none focus:border-elite-secondary/60 focus:ring-1 focus:ring-elite-secondary/30
                                               transition-all duration-200 appearance-none
                                               bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2212%22%20height%3D%2212%22%20fill%3D%22none%22%20stroke%3D%22%23ffb599%22%20stroke-width%3D%222%22%3E%3Cpath%20d%3D%22M3%205l3%203%203-3%22%2F%3E%3C%2Fsvg%3E')] bg-[length:12px] bg-[right_16px_center] bg-no-repeat">
                                    <option value="" disabled selected class="bg-elite-surface-low">
                                        Selecciona un torneo
                                    </option>
                                    <option v-for="t in tournaments" :key="t.id" :value="t.id"
                                            :disabled="t.full"
                                            class="bg-elite-surface-low">
                                        {{ t.name }}
                                        <span v-if="t.full"> (Completo)</span>
                                        <span v-else-if="t.max_players"> ({{ t.players_count }}/{{ t.max_players }})</span>
                                    </option>
                                </select>
                            </div>
                            <p v-if="!tournaments.length" class="mt-1.5 text-xs text-elite-primary/40">
                                No hay torneos disponibles por el momento.
                            </p>
                            <p v-if="form.errors.tournament_id" class="mt-1.5 text-xs text-red-400">
                                {{ form.errors.tournament_id }}
                            </p>
                        </div>

                        <!-- Name -->
                        <div>
                            <label class="block text-xs font-elite-condensed uppercase tracking-[0.12em] text-elite-primary/60 mb-1.5">
                                Nombre completo
                            </label>
                            <input type="text" v-model="form.name"
                                   class="w-full bg-elite-surface-low border border-elite-outline/40 rounded-lg
                                          px-4 py-3 text-sm text-white placeholder-elite-primary/20
                                          focus:outline-none focus:border-elite-secondary/60 focus:ring-1 focus:ring-elite-secondary/30
                                          transition-all duration-200"
                                   placeholder="Tu nombre" />
                            <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-400">{{ form.errors.name }}</p>
                        </div>

                        <!-- PSN ID -->
                        <div>
                            <label class="block text-xs font-elite-condensed uppercase tracking-[0.12em] text-elite-primary/60 mb-1.5">
                                PSN ID
                            </label>
                            <input type="text" v-model="form.psn_id"
                                   class="w-full bg-elite-surface-low border border-elite-outline/40 rounded-lg
                                          px-4 py-3 text-sm text-white placeholder-elite-primary/20
                                          focus:outline-none focus:border-elite-secondary/60 focus:ring-1 focus:ring-elite-secondary/30
                                          transition-all duration-200"
                                   placeholder="Tu ID de PlayStation Network" />
                            <p v-if="form.errors.psn_id" class="mt-1.5 text-xs text-red-400">{{ form.errors.psn_id }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-elite-condensed uppercase tracking-[0.12em] text-elite-primary/60 mb-1.5">
                                Correo electrónico
                            </label>
                            <input type="email" v-model="form.email"
                                   class="w-full bg-elite-surface-low border border-elite-outline/40 rounded-lg
                                          px-4 py-3 text-sm text-white placeholder-elite-primary/20
                                          focus:outline-none focus:border-elite-secondary/60 focus:ring-1 focus:ring-elite-secondary/30
                                          transition-all duration-200"
                                   placeholder="tu@email.com" />
                            <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-400">{{ form.errors.email }}</p>
                        </div>

                        <!-- Preferred Team -->
                        <div>
                            <label class="block text-xs font-elite-condensed uppercase tracking-[0.12em] text-elite-primary/60 mb-1.5">
                                Equipo favorito
                            </label>
                            <select v-model="form.preferred_team"
                                    class="w-full bg-elite-surface-low border border-elite-outline/40 rounded-lg
                                           px-4 py-3 text-sm text-white
                                           focus:outline-none focus:border-elite-secondary/60 focus:ring-1 focus:ring-elite-secondary/30
                                           transition-all duration-200 appearance-none
                                           bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2212%22%20height%3D%2212%22%20fill%3D%22none%22%20stroke%3D%22%23ffb599%22%20stroke-width%3D%222%22%3E%3Cpath%20d%3D%22M3%205l3%203%203-3%22%2F%3E%3C%2Fsvg%3E')] bg-[length:12px] bg-[right_16px_center] bg-no-repeat">
                                <option value="" disabled selected class="bg-elite-surface-low">Selecciona un equipo</option>
                                <option v-for="team in teams" :key="team" :value="team" class="bg-elite-surface-low">
                                    {{ team }}
                                </option>
                            </select>
                            <p v-if="form.errors.preferred_team" class="mt-1.5 text-xs text-red-400">{{ form.errors.preferred_team }}</p>
                        </div>

                        <!-- Submit -->
                        <button type="submit" :disabled="form.processing || !tournaments.length"
                                class="w-full py-4 rounded-xl bg-elite-secondary text-black font-bold font-elite-condensed
                                       text-base uppercase tracking-widest
                                       shadow-lg shadow-elite-secondary/30
                                       hover:brightness-110 hover:shadow-xl hover:shadow-elite-secondary/40
                                       active:brightness-90 disabled:opacity-50 disabled:cursor-not-allowed
                                       transition-all duration-200 flex items-center justify-center gap-2">
                            <svg v-if="form.processing" class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            {{ form.processing ? 'ENVIANDO...' : 'REGISTRARSE' }}
                        </button>
                    </form>
                </div>

                <p class="text-center text-xs text-elite-primary/30 mt-6">
                    Al registrarte aceptas las
                    <Link href="/rules" class="text-elite-secondary/70 hover:text-elite-secondary transition-colors">reglas de la competencia</Link>.
                </p>
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
