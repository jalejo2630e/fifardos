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
    todos: { emoji: '🏟️', title: 'Sin torneos aún', desc: 'Crea tu primer torneo, añade jugadores, configura las consolas y descubre al campeón.' },
    in_progress: { emoji: '⚡', title: 'No hay torneos en curso', desc: 'Los torneos que estén activos aparecerán aquí. ¡Crea uno nuevo para empezar!' },
    completed: { emoji: '🏆', title: 'No hay torneos finalizados', desc: 'Cuando un torneo termine, aparecerá aquí con su campeón.' },
    setup: { emoji: '⚙️', title: 'No hay torneos en configuración', desc: 'Los torneos que estén en fase de configuración se mostrarán aquí.' },
};
</script>

<template>
    <Head title="Mis Torneos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="ucl-title-lg text-white">
                        Mis <span class="text-ucl-cyan">Torneos</span>
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
                            ? 'bg-ucl-cyan/10 text-ucl-cyan'
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
                        <div class="text-6xl sm:text-7xl mb-6">🏟️</div>
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
                            Crear Torneo
                        </Link>
                    </div>
                </div>

                <!-- Empty state (filtro sin resultados) -->
                <div v-else-if="!hasFiltered"
                     class="ucl-card p-10 sm:p-16 text-center animate-scale-in">
                    <div class="stars-overlay" />
                    <div class="relative">
                        <div class="text-6xl sm:text-7xl mb-6">{{ emptyMessages[activeFilter].emoji }}</div>
                        <h2 class="ucl-title-md text-2xl sm:text-3xl text-white/80 mb-3">
                            {{ emptyMessages[activeFilter].title }}
                        </h2>
                        <p class="text-white/30 max-w-md mx-auto mb-8 text-sm sm:text-base leading-relaxed">
                            {{ emptyMessages[activeFilter].desc }}
                        </p>
                        <button
                            v-if="activeFilter !== 'todos'"
                            @click="activeFilter = 'todos'"
                            class="ucl-btn-ghost px-6 py-3 text-sm"
                        >
                            Ver todos los torneos
                        </button>
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
