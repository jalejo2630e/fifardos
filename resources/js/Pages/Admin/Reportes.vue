<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    metrics: Object,
    signups: Array,
    recentTournaments: Array,
    recentUsers: Array,
    topScorers: Array,
});

const maxSignup = computed(() => Math.max(1, ...props.signups.map((s) => s.count)));

const cards = computed(() => [
    { label: 'Usuarios', value: props.metrics.users, sub: `+${props.metrics.usersLast7} en 7 días`, icon: 'users' },
    { label: 'Torneos', value: props.metrics.tournaments, sub: `${props.metrics.inProgress} en curso · ${props.metrics.completed} finalizados`, icon: 'trophy' },
    { label: 'Jugadores', value: props.metrics.players, sub: `${props.metrics.avgPlayers} por torneo`, icon: 'user' },
    { label: 'Partidos', value: props.metrics.matches, sub: `${props.metrics.matchesPlayed} jugados · ${props.metrics.matchesPending} pendientes`, icon: 'ball' },
    { label: 'Goles', value: props.metrics.goals, sub: `${props.metrics.avgGoalsPerMatch} por partido`, icon: 'net' },
    { label: 'Admins', value: props.metrics.admins, sub: `de ${props.metrics.users} usuarios`, icon: 'shield' },
]);

function fmtDay(d) {
    const parts = (d || '').split('-');
    return parts.length === 3 ? `${parts[2]}/${parts[1]}` : d;
}
</script>

<template>
    <Head title="Reportes" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="ucl-title-lg">Reportes <span class="text-elite-secondary">de administración</span></h1>
                <p class="ucl-meta mt-1">Métricas globales de la plataforma</p>
            </div>
        </template>

        <div class="py-6 sm:py-8 lg:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Metric cards -->
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="c in cards" :key="c.label" class="ucl-card p-5 sm:p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="font-condensed font-bold text-3xl sm:text-4xl text-white leading-none">{{ c.value }}</div>
                                <div class="mt-2 text-xs uppercase tracking-wider text-white/40">{{ c.label }}</div>
                                <div class="mt-1 text-xs text-white/30">{{ c.sub }}</div>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-elite-secondary/10 border border-elite-secondary/20 flex items-center justify-center text-elite-secondary shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path v-if="c.icon==='users'" stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 0 0-3-3.87M9 20H4v-1a4 4 0 0 1 3-3.87m6-5.13a3 3 0 1 0-4 0M17 8a3 3 0 1 0-2 0"/>
                                    <path v-else-if="c.icon==='trophy'" stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4zM17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/>
                                    <path v-else-if="c.icon==='user'" stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z"/>
                                    <path v-else-if="c.icon==='ball'" stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 7l3 2-1 4h-4l-1-4z"/>
                                    <path v-else-if="c.icon==='net'" stroke-linecap="round" stroke-linejoin="round" d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6l7-3z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Signups chart -->
                <div class="ucl-card p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="font-condensed font-bold text-lg tracking-wider text-white">Registros · últimos 14 días</h2>
                        <span class="text-xs text-white/40">+{{ metrics.usersLast30 }} en 30 días</span>
                    </div>
                    <div class="flex items-end gap-1.5 h-32">
                        <div v-for="s in signups" :key="s.day" class="flex-1 flex flex-col items-center gap-1.5 group">
                            <div class="w-full rounded-t bg-gradient-to-t from-elite-secondary/40 to-elite-secondary transition-all"
                                 :style="{ height: Math.max(4, (s.count / maxSignup) * 104) + 'px' }"
                                 :title="s.count + ' registros'"></div>
                            <span class="text-[9px] text-white/25 rotate-0">{{ fmtDay(s.day) }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent tournaments -->
                    <div class="ucl-card p-5 sm:p-6">
                        <h2 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">Torneos recientes</h2>
                        <div class="space-y-2">
                            <div v-for="t in recentTournaments" :key="t.id" class="flex items-center gap-3 py-2 border-b border-white/5 last:border-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="t.status==='completed' ? 'bg-emerald-400' : 'bg-elite-secondary'"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-white/90 truncate">{{ t.name }}</div>
                                    <div class="text-xs text-white/40 truncate">{{ t.owner }} · {{ t.players }} jugadores</div>
                                </div>
                                <span class="text-xs text-white/30 shrink-0">{{ t.created_at }}</span>
                            </div>
                            <p v-if="!recentTournaments.length" class="text-sm text-white/30 py-4 text-center">Sin torneos aún.</p>
                        </div>
                    </div>

                    <!-- Recent users -->
                    <div class="ucl-card p-5 sm:p-6">
                        <h2 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">Usuarios recientes</h2>
                        <div class="space-y-2">
                            <div v-for="u in recentUsers" :key="u.id" class="flex items-center gap-3 py-2 border-b border-white/5 last:border-0">
                                <span class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-xs font-bold text-white/70 shrink-0">
                                    {{ u.name.charAt(0).toUpperCase() }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-white/90 truncate">
                                        {{ u.name }}
                                        <span v-if="u.is_admin" class="ml-1 text-[10px] uppercase tracking-wide text-elite-secondary">admin</span>
                                    </div>
                                    <div class="text-xs text-white/40 truncate">{{ u.email }}</div>
                                </div>
                                <span class="text-xs text-white/30 shrink-0">{{ u.created_at }}</span>
                            </div>
                            <p v-if="!recentUsers.length" class="text-sm text-white/30 py-4 text-center">Sin usuarios.</p>
                        </div>
                    </div>
                </div>

                <!-- Top scorers -->
                <div class="ucl-card p-5 sm:p-6">
                    <h2 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">Goleadores (global)</h2>
                    <div v-if="topScorers.length" class="space-y-2.5">
                        <div v-for="(s, i) in topScorers" :key="i" class="flex items-center gap-3">
                            <span class="font-condensed font-bold text-lg w-6 text-elite-secondary">{{ i + 1 }}</span>
                            <span class="text-sm text-white/80 flex-1 truncate">{{ s.name }}</span>
                            <span class="text-sm font-bold text-white">{{ s.goals }} <span class="text-white/30 font-normal">goles</span></span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-white/30 py-4 text-center">Aún no hay goleadores registrados.</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
