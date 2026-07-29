<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import TournamentCard from '@/Components/TournamentCard.vue';
import TournamentCardSkeleton from '@/Components/TournamentCardSkeleton.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n({
    useScope: 'local',
    messages: {
        es: {
            headTitle: 'Mis Torneos',
            titlePrefix: 'Mis',
            titleHighlight: 'Torneos',
            subtitle: 'Gestiona tus torneos FIFA Champions',
            newTournament: 'Nuevo Torneo',
            tabTodos: 'Todos',
            tabInProgress: 'En curso',
            tabCompleted: 'Finalizados',
            tabSetup: 'Config',
            createTournament: '+ Crear Torneo',
            viewAll: 'Ver todos',
            emptyTodosTitle: 'El campo está vacío',
            emptyTodosDesc: 'Crea tu primer torneo y que empiece el partido.',
            emptyInProgressTitle: 'Sin actividad',
            emptyInProgressDesc: 'No hay torneos en curso. Activa uno desde la configuración o crea uno nuevo.',
            emptyCompletedTitle: 'Nadie ha ganado aún',
            emptyCompletedDesc: 'Cuando un torneo finalice, el campeón aparecerá aquí con todos los honores.',
            emptySetupTitle: 'Nada en preparación',
            emptySetupDesc: 'Los torneos en fase de configuración vivirán aquí. Prepara las consolas y los jugadores.',
            step1Title: 'Crea un torneo',
            step1Desc: 'Elegí un nombre, las consolas disponibles y agregá los jugadores. El fixture se genera automáticamente.',
            step2Title: 'Ingresá resultados',
            step2Desc: 'Partido a partido, cargá los goles de cada encuentro. La tabla se actualiza sola.',
            step3Title: 'Coroná al campeón',
            step3Desc: 'Cuando se juegue el último partido, el campeón aparecerá con todos los honores. ¡Compartí el bracket público!',
        },
        en: {
            headTitle: 'My Tournaments',
            titlePrefix: 'My',
            titleHighlight: 'Tournaments',
            subtitle: 'Manage your FIFA Champions tournaments',
            newTournament: 'New Tournament',
            tabTodos: 'All',
            tabInProgress: 'In progress',
            tabCompleted: 'Finished',
            tabSetup: 'Setup',
            createTournament: '+ Create Tournament',
            viewAll: 'View all',
            emptyTodosTitle: 'The pitch is empty',
            emptyTodosDesc: 'Create your first tournament and let the match begin.',
            emptyInProgressTitle: 'No activity',
            emptyInProgressDesc: 'There are no tournaments in progress. Activate one from the setup or create a new one.',
            emptyCompletedTitle: 'No one has won yet',
            emptyCompletedDesc: 'When a tournament finishes, the champion will appear here with full honors.',
            emptySetupTitle: 'Nothing in preparation',
            emptySetupDesc: 'Tournaments in the setup phase will live here. Get the consoles and players ready.',
            step1Title: 'Create a tournament',
            step1Desc: 'Pick a name, the available consoles and add the players. The fixture is generated automatically.',
            step2Title: 'Enter results',
            step2Desc: 'Match by match, enter the goals of each game. The table updates on its own.',
            step3Title: 'Crown the champion',
            step3Desc: 'When the last match is played, the champion will appear with full honors. Share the public bracket!',
        },
    },
});

const props = defineProps({
    tournaments: Array,
});

const activeFilter = ref('todos');

const tabs = computed(() => [
    { key: 'todos', label: t('tabTodos') },
    { key: 'in_progress', label: t('tabInProgress') },
    { key: 'completed', label: t('tabCompleted') },
    { key: 'setup', label: t('tabSetup') },
]);

const filteredTournaments = computed(() => {
    if (activeFilter.value === 'todos') return props.tournaments;
    return props.tournaments.filter(row => row.status === activeFilter.value);
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

const emptyMessages = computed(() => ({
    todos: { icon: 'ball', title: t('emptyTodosTitle'), desc: t('emptyTodosDesc') },
    in_progress: { icon: 'bolt', title: t('emptyInProgressTitle'), desc: t('emptyInProgressDesc') },
    completed: { icon: 'cup', title: t('emptyCompletedTitle'), desc: t('emptyCompletedDesc') },
    setup: { icon: 'gear', title: t('emptySetupTitle'), desc: t('emptySetupDesc') },
}));
</script>

<template>
    <Head :title="t('headTitle')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="ucl-title-lg text-white">
                        {{ t('titlePrefix') }} <span class="text-elite-secondary">{{ t('titleHighlight') }}</span>
                    </h1>
                    <p class="ucl-meta mt-1">{{ t('subtitle') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('tournaments.create')"
                          class="hidden sm:inline-flex ucl-btn-primary min-h-touch px-6">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ t('newTournament') }}
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
                        <span v-else class="ml-1.5 text-[10px] opacity-50">({{ tournaments.filter(row => row.status === tab.key).length }})</span>
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
                            {{ t('createTournament') }}
                        </Link>
                        <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 max-w-2xl mx-auto">
                            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 text-left">
                                <div class="w-8 h-8 rounded-full bg-elite-secondary/15 flex items-center justify-center mb-3
                                            text-elite-secondary font-bold text-sm">1</div>
                                <h4 class="text-white/70 text-sm font-semibold mb-1">{{ t('step1Title') }}</h4>
                                <p class="text-white/30 text-xs leading-relaxed">{{ t('step1Desc') }}</p>
                            </div>
                            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 text-left">
                                <div class="w-8 h-8 rounded-full bg-elite-secondary/15 flex items-center justify-center mb-3
                                            text-elite-secondary font-bold text-sm">2</div>
                                <h4 class="text-white/70 text-sm font-semibold mb-1">{{ t('step2Title') }}</h4>
                                <p class="text-white/30 text-xs leading-relaxed">{{ t('step2Desc') }}</p>
                            </div>
                            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 text-left">
                                <div class="w-8 h-8 rounded-full bg-elite-secondary/15 flex items-center justify-center mb-3
                                            text-elite-secondary font-bold text-sm">3</div>
                                <h4 class="text-white/70 text-sm font-semibold mb-1">{{ t('step3Title') }}</h4>
                                <p class="text-white/30 text-xs leading-relaxed">{{ t('step3Desc') }}</p>
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
                                {{ t('viewAll') }}
                            </button>
                            <Link :href="route('tournaments.create')"
                                  class="ucl-btn-primary px-6 py-3 text-sm">
                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ t('createTournament') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Grid de torneos -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 lg:gap-6">
                    <div v-for="(row, i) in filteredTournaments" :key="row.id"
                         class="animate-fade-up"
                         :style="{ animationDelay: (i * 80) + 'ms' }">
                        <TournamentCard :tournament="row" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
