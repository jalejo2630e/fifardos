<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import TournamentCard from '@/Components/TournamentCard.vue';
import TournamentCardSkeleton from '@/Components/TournamentCardSkeleton.vue';

const props = defineProps({
    tournaments: Array,
});

const activeFilter = ref('todos');

const tabs = [
    { key: 'todos', label: 'Todos' },
    { key: 'in_progress', label: 'En curso' },
    { key: 'completed', label: 'Finalizados' },
    { key: 'setup', label: 'Config' },
];

const filteredTournaments = computed(() => {
    if (activeFilter.value === 'todos') return props.tournaments;
    return props.tournaments.filter(t => t.status === activeFilter.value);
});

const hasTournaments = computed(() => props.tournaments.length > 0);
const hasFiltered = computed(() => filteredTournaments.value.length > 0);

const isLoading = ref(false);
let stopStart, stopFinish;

onMounted(() => {
    stopStart = router.on('start', () => { isLoading.value = true; });
    stopFinish = router.on('finish', () => { isLoading.value = false; });
});

onUnmounted(() => {
    stopStart?.();
    stopFinish?.();
});

const emptyMessages = {
    todos: { icon: 'ball', title: 'El campo está vacío', desc: 'Crea tu primer torneo y que empiece el partido.' },
    in_progress: { icon: 'bolt', title: 'Sin actividad', desc: 'No hay torneos en curso. Activa uno desde la configuración o crea uno nuevo.' },
    completed: { icon: 'cup', title: 'Nadie ha ganado aún', desc: 'Cuando un torneo finalice, el campeón aparecerá aquí con todos los honores.' },
    setup: { icon: 'gear', title: 'Nada en preparación', desc: 'Los torneos en fase de configuración vivirán aquí. Prepara las consolas y los jugadores.' },
};
</script>

<template>
    <Head title="Mis Torneos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="ucl-title-lg text-white">
                        Mis <span class="text-elite-secondary">Torneos</span>
                    </h1>
                    <p class="ucl-meta mt-1">Gestiona tus torneos FIFA Champions</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('tournaments.create')"
                          class="hidden sm:inline-flex ucl-btn-primary min-h-touch px-6">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo Torneo
                    </Link>
                    <Link :href="route('tournaments.create')"
                          class="sm:hidden ucl-btn-primary min-h-touch min-w-touch px-3">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8 lg:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Tabs de filtro -->
                <div v-if="hasTournaments"
                     class="ucl-card p-1 flex gap-1 animate-fade-up overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeFilter = tab.key"
                        class="flex-1 min-h-touch whitespace-nowrap px-4 rounded-xl font-condensed text-xs sm:text-sm
                               uppercase tracking-[0.08em] transition-all duration-200"
                        :class="activeFilter === tab.key
                            ? 'bg-elite-secondary/10 text-elite-secondary'
                            : 'text-white/30 hover:text-white/60'"
                    >
                        {{ tab.label }}
                        <span v-if="tab.key === 'todos'" class="ml-1.5 text-[10px] opacity-50">({{ tournaments.length }})</span>
                        <span v-else class="ml-1.5 text-[10px] opacity-50">({{ tournaments.filter(t => t.status === tab.key).length }})</span>
                    </button>
                </div>

                <!-- Skeleton loader -->
                <div v-if="isLoading"
                     class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 lg:gap-6">
                    <TournamentCardSkeleton v-for="n in 3" :key="n" :index="n - 1" />
                </div>

                <!-- Empty state (sin torneos en absoluto) -->
                <div v-else-if="!hasTournaments"
                     class="ucl-card p-10 sm:p-16 text-center animate-scale-in">
                    <div class="stars-overlay" />
                    <div class="relative">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 mx-auto mb-6 rounded-full bg-elite-secondary/5 border border-elite-secondary/15
                                    flex items-center justify-center animate-pulse">
                            <svg class="w-10 h-10 sm:w-12 sm:h-12 text-elite-secondary/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" />
                            </svg>
                        </div>
                        <h2 class="ucl-title-md text-2xl sm:text-3xl text-white/80 mb-3">
                            {{ emptyMessages.todos.title }}
                        </h2>
                        <p class="text-white/30 max-w-md mx-auto mb-8 text-sm sm:text-base leading-relaxed">
                            {{ emptyMessages.todos.desc }}
                        </p>
                        <Link :href="route('tournaments.create')"
                              class="ucl-btn-primary px-8 py-4 text-sm sm:text-base">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            + Crear Torneo
                        </Link>
                        <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 max-w-2xl mx-auto">
                            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 text-left">
                                <div class="w-8 h-8 rounded-full bg-elite-secondary/15 flex items-center justify-center mb-3
                                            text-elite-secondary font-bold text-sm">1</div>
                                <h4 class="text-white/70 text-sm font-semibold mb-1">Crea un torneo</h4>
                                <p class="text-white/30 text-xs leading-relaxed">Elegí un nombre, las consolas disponibles y agregá los jugadores. El fixture se genera automáticamente.</p>
                            </div>
                            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 text-left">
                                <div class="w-8 h-8 rounded-full bg-elite-secondary/15 flex items-center justify-center mb-3
                                            text-elite-secondary font-bold text-sm">2</div>
                                <h4 class="text-white/70 text-sm font-semibold mb-1">Ingresá resultados</h4>
                                <p class="text-white/30 text-xs leading-relaxed">Partido a partido, cargá los goles de cada encuentro. La tabla se actualiza sola.</p>
                            </div>
                            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 text-left">
                                <div class="w-8 h-8 rounded-full bg-elite-secondary/15 flex items-center justify-center mb-3
                                            text-elite-secondary font-bold text-sm">3</div>
                                <h4 class="text-white/70 text-sm font-semibold mb-1">Coroná al campeón</h4>
                                <p class="text-white/30 text-xs leading-relaxed">Cuando se juegue el último partido, el campeón aparecerá con todos los honores. ¡Compartí el bracket público!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state (filtro sin resultados) -->
                <div v-else-if="!hasFiltered"
                     class="ucl-card p-10 sm:p-16 text-center animate-scale-in">
                    <div class="stars-overlay" />
                    <div class="relative">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 mx-auto mb-6 rounded-full bg-elite-secondary/5 border border-elite-secondary/15
                                    flex items-center justify-center animate-pulse">
                            <svg v-if="emptyMessages[activeFilter].icon === 'bolt'" class="w-10 h-10 sm:w-12 sm:h-12 text-elite-secondary/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <svg v-else-if="emptyMessages[activeFilter].icon === 'cup'" class="w-10 h-10 sm:w-12 sm:h-12 text-elite-secondary/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12v4c0 4-3 7-6 7s-6-3-6-7V3z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 3H3v2c0 2.5 1.5 4.5 3 5.5M18 3h3v2c0 2.5-1.5 4.5-3 5.5" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 17v4" />
                            </svg>
                            <svg v-else-if="emptyMessages[activeFilter].icon === 'gear'" class="w-10 h-10 sm:w-12 sm:h-12 text-elite-secondary/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" />
                            </svg>
                            <svg v-else class="w-10 h-10 sm:w-12 sm:h-12 text-elite-secondary/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" />
                            </svg>
                        </div>
                        <h2 class="ucl-title-md text-2xl sm:text-3xl text-white/80 mb-3">
                            {{ emptyMessages[activeFilter].title }}
                        </h2>
                        <p class="text-white/30 max-w-md mx-auto mb-8 text-sm sm:text-base leading-relaxed">
                            {{ emptyMessages[activeFilter].desc }}
                        </p>
                        <div class="flex items-center justify-center gap-3">
                            <button
                                v-if="activeFilter !== 'todos'"
                                @click="activeFilter = 'todos'"
                                class="ucl-btn-ghost px-6 py-3 text-sm"
                            >
                                Ver todos
                            </button>
                            <Link :href="route('tournaments.create')"
                                  class="ucl-btn-primary px-6 py-3 text-sm">
                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                + Crear Torneo
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Grid de torneos -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 lg:gap-6">
                    <div v-for="(t, i) in filteredTournaments" :key="t.id"
                         class="animate-fade-up"
                         :style="{ animationDelay: (i * 80) + 'ms' }">
                        <TournamentCard :tournament="t" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
