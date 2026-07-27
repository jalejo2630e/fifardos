<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    tournaments: Array,
});

function statusIcon(status) {
    if (status === 'completed') return '🏆';
    if (status === 'in_progress') return '⚔️';
    return '⚙️';
}

function statusLabel(status) {
    if (status === 'completed') return 'Finalizado';
    if (status === 'in_progress') return 'En Curso';
    return 'Configuración';
}

function statusColor(status) {
    if (status === 'completed') return 'from-gaming-gold/20 to-amber-900/20 border-gaming-gold/30 text-gaming-gold';
    if (status === 'in_progress') return 'from-gaming-cyan/20 to-blue-900/20 border-gaming-cyan/30 text-gaming-cyan';
    return 'from-white/10 to-white/5 border-white/20 text-white/60';
}
</script>

<template>
    <Head title="Mis Torneos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black font-display tracking-tight">
                        <span class="text-gaming-cyan neon-text">Mis</span>
                        <span class="text-white"> Torneos</span>
                    </h1>
                    <p class="text-white/40 text-sm mt-1">Gestiona tus torneos de FIFA</p>
                </div>
                <Link :href="route('tournaments.create')"
                      class="btn-primary flex items-center gap-2 text-base px-6 py-3.5">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Torneo
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Empty State -->
                <div v-if="tournaments.length === 0"
                     class="glass-card p-16 text-center animate-fade-up">
                    <div class="text-6xl mb-6">🏟️</div>
                    <h2 class="text-2xl font-bold font-display text-white/80 mb-3">No hay torneos aún</h2>
                    <p class="text-white/40 mb-8 max-w-md mx-auto">
                        Crea tu primer torneo FIFA, agrega jugadores, elige cuántas consolas tenéis
                        y empezad a jugar.
                    </p>
                    <Link :href="route('tournaments.create')"
                          class="btn-primary inline-flex items-center gap-2 text-base px-8 py-4">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Crear mi Primer Torneo
                    </Link>
                </div>

                <!-- Tournament Grid -->
                <div v-else class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <div v-for="t in tournaments" :key="t.id"
                         class="glass-card-hover p-6 animate-fade-up group">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-4">
                            <span class="text-2xl">{{ statusIcon(t.status) }}</span>
                            <span class="text-xs font-bold uppercase tracking-wider px-3 py-1.5 rounded-full border"
                                  :class="statusColor(t.status)">
                                {{ statusLabel(t.status) }}
                            </span>
                        </div>

                        <!-- Tournament Name -->
                        <h3 class="text-xl font-bold font-display text-white mb-3 group-hover:text-gaming-cyan transition-colors">
                            {{ t.name }}
                        </h3>

                        <!-- Stats -->
                        <div class="flex items-center gap-6 text-sm text-white/50 mb-6">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ t.players_count }} jugadores</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>{{ t.consoles_count }} TV(s)</span>
                            </div>
                        </div>

                        <!-- Progress Bar for in_progress -->
                        <div v-if="t.status === 'in_progress' && t.matches_count > 0" class="mb-5">
                            <div class="flex items-center justify-between text-xs text-white/40 mb-2">
                                <span>Progreso del torneo</span>
                                <span>{{ t.matches_played || 0 }}/{{ t.matches_count }} partidos</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-gaming-cyan to-gaming-purple transition-all duration-500"
                                     :style="{ width: ((t.matches_played || 0) / t.matches_count * 100) + '%' }">
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 pt-2">
                            <Link :href="route('tournaments.show', t.id)"
                                  class="flex-1 btn-primary text-center text-sm py-2.5">
                                {{ t.status === 'setup' ? 'Configurar' : 'Ver Torneo' }}
                            </Link>
                            <Link :href="route('tournaments.destroy', t.id)"
                                  method="delete" as="button"
                                  class="btn-ghost text-sm py-2.5 px-4"
                                  onclick="return confirm('¿Eliminar torneo?')">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
