<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tournament: Object,
    player: Object,
    stats: Object,
    position: Number,
    totalPlayers: Number,
    matches: Array,
});

const bracketUrl = computed(() => route('tournaments.public.bracket', props.tournament.slug));

const statusLabel = computed(() => ({
    in_progress: 'En vivo',
    finished: 'Finalizado',
    completed: 'Finalizado',
}[props.tournament.status] || 'Próximamente'));

const statCards = computed(() => {
    const s = props.stats || {};
    return [
        { label: 'Puntos', value: s.pts ?? 0, accent: true },
        { label: 'Jugados', value: s.pj ?? 0 },
        { label: 'Ganados', value: s.pg ?? 0 },
        { label: 'Empatados', value: s.pe ?? 0 },
        { label: 'Perdidos', value: s.pp ?? 0 },
        { label: 'Goles a favor', value: s.gf ?? 0 },
        { label: 'Goles en contra', value: s.gc ?? 0 },
        { label: 'Diferencia', value: (s.dg ?? 0) > 0 ? `+${s.dg}` : (s.dg ?? 0) },
    ];
});

const resultStyle = (r) => ({
    W: 'bg-green-500/15 text-green-400 border-green-500/30',
    D: 'bg-white/10 text-white/60 border-white/20',
    L: 'bg-red-500/15 text-red-400 border-red-500/30',
}[r] || 'bg-white/10 text-white/60 border-white/20');
</script>

<template>
    <div class="min-h-screen bg-[#0a0a0d] text-white/90">
        <Head :title="`${player.name} — ${tournament.name}`" />

        <!-- NAV -->
        <nav class="flex items-center justify-between px-4 sm:px-8 py-4 border-b border-white/10">
            <Link href="/" class="flex items-center gap-2 font-bold tracking-widest text-white/80">
                <span class="w-8 h-8 rounded-lg bg-[#ff5f00] text-black flex items-center justify-center text-sm font-black">F</span>
                FIFARDOS
            </Link>
            <a :href="bracketUrl" class="text-sm px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white/70 hover:bg-white/10 transition-colors">
                ← Volver al torneo
            </a>
        </nav>

        <main class="max-w-4xl mx-auto px-4 sm:px-8 py-10">
            <!-- Breadcrumb visible -->
            <nav class="text-xs text-white/40 mb-6" aria-label="Breadcrumb">
                <Link href="/" class="hover:text-white/70">Inicio</Link>
                <span class="mx-1.5">/</span>
                <a :href="bracketUrl" class="hover:text-white/70">{{ tournament.name }}</a>
                <span class="mx-1.5">/</span>
                <span class="text-white/70">{{ player.name }}</span>
            </nav>

            <!-- HERO -->
            <header class="flex flex-col sm:flex-row sm:items-center gap-5 mb-10">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#ff5f00] to-orange-700 flex items-center justify-center text-3xl font-black text-black shrink-0">
                    {{ (player.name || '?').charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0">
                    <h1 class="text-3xl sm:text-4xl font-black leading-tight">{{ player.name }}</h1>
                    <p class="text-white/50 mt-1">
                        <span v-if="player.username">@{{ player.username }}</span>
                        <span v-if="player.preferred_team"> · {{ player.preferred_team }}</span>
                    </p>
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <span class="text-[11px] uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#ff5f00]/15 text-[#ff5f00] border border-[#ff5f00]/30">
                            {{ statusLabel }}
                        </span>
                        <a :href="bracketUrl" class="text-[11px] uppercase tracking-wider px-2.5 py-1 rounded-full bg-white/5 text-white/60 border border-white/10 hover:bg-white/10 transition-colors">
                            {{ tournament.name }}
                        </a>
                        <span v-if="position" class="text-[11px] uppercase tracking-wider px-2.5 py-1 rounded-full bg-white/5 text-white/60 border border-white/10">
                            Puesto {{ position }} de {{ totalPlayers }}
                        </span>
                    </div>
                </div>
            </header>

            <!-- STATS -->
            <section aria-label="Estadísticas" class="mb-10">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-white/50 mb-3">Estadísticas en el torneo</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div v-for="c in statCards" :key="c.label"
                         class="rounded-xl border p-4 text-center"
                         :class="c.accent ? 'bg-[#ff5f00]/10 border-[#ff5f00]/30' : 'bg-white/[0.03] border-white/10'">
                        <div class="text-2xl font-black" :class="c.accent ? 'text-[#ff5f00]' : 'text-white'">{{ c.value }}</div>
                        <div class="text-[11px] uppercase tracking-wider text-white/40 mt-1">{{ c.label }}</div>
                    </div>
                </div>
            </section>

            <!-- MATCHES -->
            <section v-if="matches.length" aria-label="Historial de partidos">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-white/50 mb-3">Partidos jugados</h2>
                <div class="rounded-xl border border-white/10 overflow-hidden divide-y divide-white/5">
                    <div v-for="(m, i) in matches" :key="i"
                         class="flex items-center gap-3 px-4 py-3 bg-white/[0.02]">
                        <span class="w-7 h-7 shrink-0 rounded-md border flex items-center justify-center text-xs font-bold"
                              :class="resultStyle(m.result)">
                            {{ m.result }}
                        </span>
                        <div class="flex-1 min-w-0 text-sm">
                            <span class="text-white/50">vs</span>
                            <component :is="m.opponent_username ? 'a' : 'span'"
                                       :href="m.opponent_username ? route('players.public.profile', [tournament.slug, m.opponent_username]) : undefined"
                                       class="font-medium text-white/90"
                                       :class="m.opponent_username ? 'hover:text-[#ff5f00] transition-colors' : ''">
                                {{ m.opponent_name || '—' }}
                            </component>
                        </div>
                        <span class="font-black tabular-nums text-lg">{{ m.gf }} - {{ m.gc }}</span>
                    </div>
                </div>
            </section>
            <p v-else class="text-sm text-white/40">Aún no hay partidos jugados por este jugador.</p>

            <!-- CTA -->
            <div class="mt-12 rounded-2xl border border-white/10 bg-white/[0.02] p-6 text-center">
                <p class="text-white/60 text-sm">¿Quieres organizar tu propio torneo?</p>
                <Link href="/" class="inline-block mt-3 px-5 py-2.5 rounded-lg bg-[#ff5f00] text-black font-semibold text-sm hover:brightness-110 transition-all">
                    Crear un torneo gratis en FIFARDOS
                </Link>
            </div>
        </main>
    </div>
</template>
