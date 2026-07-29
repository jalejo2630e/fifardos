<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
    chartData: Array,
    metric: String,
});

const metricTitle = computed(() => {
    if (!props.metric) return 'Panel de analítica';
    const labels = { presion: 'Intensidad de presión', goles: 'Evolución de goles', partidos: 'Evolución de partidos' };
    return labels[props.metric] || 'Panel de analítica';
});
</script>

<template>
    <Head :title="metricTitle" />
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-8 px-4 space-y-6">
            <Link :href="route('dashboard')" class="text-xs text-white/30 hover:text-white/60 font-condensed tracking-wider uppercase">
                ← Volver al dashboard
            </Link>

            <h1 class="text-2xl font-bold text-[#f4f2ef] font-condensed tracking-wide">{{ metricTitle }}</h1>

            <!-- Metric grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <Link :href="route('analytics', { metric: 'presion' })" class="bg-[#242b3d] border border-[#343d54] rounded-xl p-4 text-center hover-card block">
                    <div class="text-2xl font-bold text-[#ff8a3d] font-condensed">{{ stats.pressureIntensity }}%</div>
                    <div class="text-[10px] text-[#7a8299] font-condensed tracking-wider uppercase mt-1">Presión</div>
                </Link>
                <Link :href="route('analytics', { metric: 'goles' })" class="bg-[#242b3d] border border-[#343d54] rounded-xl p-4 text-center hover-card block">
                    <div class="text-2xl font-bold text-[#3d9bff] font-condensed">{{ stats.totalGoals }}</div>
                    <div class="text-[10px] text-[#7a8299] font-condensed tracking-wider uppercase mt-1">Goles</div>
                </Link>
                <Link :href="route('analytics', { metric: 'partidos' })" class="bg-[#242b3d] border border-[#343d54] rounded-xl p-4 text-center hover-card block">
                    <div class="text-2xl font-bold text-[#ffb35e] font-condensed">{{ stats.finishedMatches }}</div>
                    <div class="text-[10px] text-[#7a8299] font-condensed tracking-wider uppercase mt-1">Partidos</div>
                </Link>
                <div class="bg-[#242b3d] border border-[#343d54] rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-[#10b981] font-condensed">{{ stats.avgGoalsPerMatch }}</div>
                    <div class="text-[10px] text-[#7a8299] font-condensed tracking-wider uppercase mt-1">Prom. goles</div>
                </div>
            </div>

            <!-- Goals per tournament chart -->
            <div class="bg-[#242b3d] border border-[#343d54] rounded-2xl overflow-hidden">
                <div class="px-5 py-3 border-b border-[#343d54]">
                    <h2 class="text-sm font-bold text-[#f4f2ef] font-condensed tracking-wider uppercase">Goles por torneo</h2>
                </div>
                <div class="p-5 space-y-3">
                    <div v-for="item in chartData" :key="item.name" class="space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-[#f4f2ef] font-medium">{{ item.name }}</span>
                            <span class="text-[#ffb35e] font-bold font-condensed">{{ item.goals }} goles</span>
                        </div>
                        <div class="h-3 bg-[#343d54] rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-[#ff8a3d] to-[#ffb35e] rounded-full transition-all duration-800 ease-out"
                                 :style="{ width: Math.max(4, (item.goals / Math.max(...chartData.map(d => d.goals), 1)) * 100) + '%' }" />
                        </div>
                        <div class="text-[9px] text-[#7a8299] font-condensed">{{ item.matches }} partidos</div>
                    </div>
                    <div v-if="!chartData.length" class="text-xs text-[#7a8299] text-center py-4">Sin datos de torneos.</div>
                </div>
            </div>

            <!-- Evolution chart placeholder -->
            <div v-if="metric" class="bg-[#242b3d] border border-[#343d54] rounded-2xl overflow-hidden">
                <div class="px-5 py-3 border-b border-[#343d54]">
                    <h2 class="text-sm font-bold text-[#f4f2ef] font-condensed tracking-wider uppercase">Evolución temporal</h2>
                </div>
                <div class="p-5 text-center">
                    <p class="text-xs text-[#7a8299]">Gráfico de evolución disponible próximamente.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.hover-card {
    transition: transform 150ms ease-out, border-color 150ms ease-out, box-shadow 150ms ease-out;
}
.hover-card:hover {
    transform: translateY(-3px);
    border-color: var(--accent-orange, #ff8a3d);
    box-shadow: 0 6px 20px rgba(255, 138, 61, 0.15);
}
.hover-card:active {
    transform: scale(0.98);
}
</style>
