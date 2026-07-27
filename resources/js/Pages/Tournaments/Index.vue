<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import TournamentCard from '@/Components/TournamentCard.vue';

defineProps({
    tournaments: Array,
});
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
                <Link :href="route('tournaments.create')"
                      class="hidden sm:inline-flex ucl-btn-primary min-h-touch px-6">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Torneo
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-8 lg:py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Empty state -->
                <div v-if="tournaments.length === 0"
                     class="ucl-card p-10 sm:p-16 text-center animate-scale-in">
                    <div class="stars-overlay" />
                    <div class="relative">
                        <div class="text-6xl sm:text-7xl mb-6">🏟️</div>
                        <h2 class="ucl-title-md text-2xl sm:text-3xl text-white/80 mb-3">
                            Sin torneos aún
                        </h2>
                        <p class="text-white/30 max-w-md mx-auto mb-8 text-sm sm:text-base leading-relaxed">
                            Crea tu primer torneo, añade jugadores, configura las consolas
                            y descubre al campeón.
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

                <!-- Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 lg:gap-6">
                    <div v-for="(t, i) in tournaments" :key="t.id"
                         :class="'animate-fade-up'"
                         :style="{ animationDelay: (i * 80) + 'ms' }">
                        <TournamentCard :tournament="t" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
