<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    player: Object,
    stats: Object,
    tournaments: Object,
    mvpCount: Number,
});

const initials = props.player?.name
    ? props.player.name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
    : '??';
</script>

<template>
    <Head :title="player.name" />
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-8 px-4 space-y-6">
            <!-- Back -->
            <Link :href="route('tournaments.index')" class="text-xs text-white/30 hover:text-white/60 font-condensed tracking-wider uppercase">
                ← Volver a torneos
            </Link>

            <!-- Player header -->
            <div class="bg-[#242b3d] border border-[#343d54] rounded-2xl p-6 flex items-center gap-5">
                <div class="w-16 h-16 rounded-full bg-[#ff8a3d] flex items-center justify-center text-black text-xl font-bold font-condensed flex-shrink-0">
                    {{ initials }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-[#f4f2ef] font-condensed tracking-wide">{{ player.name }}</h1>
                    <p class="text-xs text-[#7a8299] mt-0.5 font-condensed tracking-wider">
                        {{ player.tournament?.name || 'Sin torneo' }}
                    </p>
                </div>
            </div>

            <!-- Stats grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-[#242b3d] border border-[#343d54] rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-[#ff8a3d] font-condensed">{{ stats.total_goals }}</div>
                    <div class="text-[10px] text-[#7a8299] font-condensed tracking-wider uppercase mt-1">Goles</div>
                </div>
                <div class="bg-[#242b3d] border border-[#343d54] rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-[#3d9bff] font-condensed">{{ stats.total_matches }}</div>
                    <div class="text-[10px] text-[#7a8299] font-condensed tracking-wider uppercase mt-1">Partidos</div>
                </div>
                <div class="bg-[#242b3d] border border-[#343d54] rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-[#ffb35e] font-condensed">{{ stats.average }}</div>
                    <div class="text-[10px] text-[#7a8299] font-condensed tracking-wider uppercase mt-1">Promedio</div>
                </div>
                <div class="bg-[#242b3d] border border-[#343d54] rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-[#10b981] font-condensed">{{ mvpCount }}</div>
                    <div class="text-[10px] text-[#7a8299] font-condensed tracking-wider uppercase mt-1">MVP</div>
                </div>
            </div>

            <!-- Tournament history -->
            <div class="bg-[#242b3d] border border-[#343d54] rounded-2xl overflow-hidden">
                <div class="px-5 py-3 border-b border-[#343d54]">
                    <h2 class="text-sm font-bold text-[#f4f2ef] font-condensed tracking-wider uppercase">Historial de torneos</h2>
                </div>
                <div class="p-5">
                    <div v-if="tournaments" class="flex items-center justify-between py-2 border-b border-[#343d54]/50 last:border-0">
                        <div>
                            <Link :href="route('tournaments.show', tournaments.id)" class="text-sm font-semibold text-[#f4f2ef] hover:text-[#ff8a3d] transition-colors">
                                {{ tournaments.name }}
                            </Link>
                            <div class="text-[10px] text-[#7a8299] font-condensed tracking-wider mt-0.5">
                                {{ tournaments.matches_count || 0 }} partidos
                                · {{ tournaments.wins || 0 }} victorias
                                · {{ (tournaments.matches_count || 0) - (tournaments.wins || 0) }} derrotas
                            </div>
                        </div>
                        <span class="text-xs font-condensed tracking-wider px-3 py-1 rounded-full"
                              :class="tournaments.status === 'completed' ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-300 border border-amber-500/20'">
                            {{ tournaments.status === 'completed' ? 'Finalizado' : 'En curso' }}
                        </span>
                    </div>
                    <div v-else class="text-xs text-[#7a8299] text-center py-4">Sin historial de torneos.</div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
