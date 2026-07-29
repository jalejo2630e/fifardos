<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    match: Object,
    goalScorers: Array,
});

const isDraw = props.match.score1 === props.match.score2;
const hasPenalties = props.match.penalties1 !== null && props.match.penalties2 !== null;
const winner1 = !isDraw ? props.match.score1 > props.match.score2 : (hasPenalties ? props.match.penalties1 > props.match.penalties2 : false);
</script>

<template>
    <Head :title="`${match.player1?.name || '?'} vs ${match.player2?.name || '?'}`" />
    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto py-8 px-4 space-y-6">
            <!-- Back -->
            <Link :href="route('tournaments.show', match.tournament_id)" class="text-xs text-white/30 hover:text-white/60 font-condensed tracking-wider uppercase">
                ← Volver al torneo
            </Link>

            <!-- Match header -->
            <div class="bg-[#242b3d] border border-[#343d54] rounded-2xl p-6">
                <div class="text-center mb-4">
                    <span class="text-[10px] text-[#7a8299] font-condensed tracking-widest uppercase">{{ match.tournament?.name || 'Torneo' }}</span>
                    <span class="mx-2 text-[#7a8299]/30">·</span>
                    <span class="text-[10px] text-[#7a8299] font-condensed tracking-wider uppercase">{{ match.phase === 'group' ? 'Fase de grupos' : match.phase?.replace('_', ' ') }}</span>
                </div>

                <!-- Scoreboard -->
                <div class="flex items-center justify-center gap-6 py-4">
                    <div class="text-right flex-1">
                        <div class="text-lg font-bold text-[#f4f2ef]" :class="!isDraw && winner1 ? 'text-emerald-400' : ''">{{ match.player1?.name || '—' }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-5xl font-bold font-condensed" :class="!isDraw ? (winner1 ? 'text-emerald-400' : 'text-white/30') : 'text-white/60'">{{ match.score1 }}</span>
                        <span class="text-2xl text-white/10 font-condensed">:</span>
                        <span class="text-5xl font-bold font-condensed" :class="!isDraw ? (!winner1 ? 'text-emerald-400' : 'text-white/30') : 'text-white/60'">{{ match.score2 }}</span>
                    </div>
                    <div class="text-left flex-1">
                        <div class="text-lg font-bold text-[#f4f2ef]" :class="!isDraw && !winner1 ? 'text-emerald-400' : ''">{{ match.player2?.name || '—' }}</div>
                    </div>
                </div>

                <!-- Penalties if draw -->
                <div v-if="isDraw && hasPenalties" class="text-center mt-2">
                    <span class="text-xs text-[#7a8299] font-condensed tracking-wider">Penales: {{ match.penalties1 }} — {{ match.penalties2 }}</span>
                </div>

                <div class="text-center mt-4">
                    <span v-if="match.status === 'finished'" class="text-[10px] text-emerald-400/60 font-condensed tracking-widest uppercase">Finalizado</span>
                    <span v-else class="text-[10px] text-amber-400/60 font-condensed tracking-widest uppercase">Pendiente</span>
                    <span v-if="match.played_at" class="ml-2 text-[10px] text-[#7a8299] font-condensed">· {{ new Date(match.played_at).toLocaleDateString() }}</span>
                </div>
            </div>

            <!-- Stats -->
            <div v-if="match.stats" class="bg-[#242b3d] border border-[#343d54] rounded-2xl overflow-hidden">
                <div class="px-5 py-3 border-b border-[#343d54]">
                    <h2 class="text-sm font-bold text-[#f4f2ef] font-condensed tracking-wider uppercase">Estadísticas</h2>
                </div>
                <div class="p-5 space-y-3">
                    <div v-if="match.stats.possession_a !== null" class="flex items-center gap-3">
                        <span class="text-xs text-[#f4f2ef] w-8 text-right font-semibold">{{ match.stats.possession_a }}%</span>
                        <div class="flex-1 h-2 bg-[#343d54] rounded-full overflow-hidden">
                            <div class="h-full bg-[#3d9bff] rounded-full" :style="{ width: match.stats.possession_a + '%' }" />
                        </div>
                        <span class="text-[10px] text-[#7a8299] w-12 font-condensed">Posesión</span>
                        <div class="flex-1 h-2 bg-[#343d54] rounded-full overflow-hidden">
                            <div class="h-full bg-[#10b981] rounded-full" :style="{ width: match.stats.possession_b + '%' }" />
                        </div>
                        <span class="text-xs text-[#f4f2ef] w-8 font-semibold">{{ match.stats.possession_b }}%</span>
                    </div>
                    <div v-if="match.stats.shots_a !== null" class="flex justify-between text-xs text-[#f4f2ef]">
                        <span>{{ match.stats.shots_a }}</span>
                        <span class="text-[10px] text-[#7a8299] font-condensed">Tiros</span>
                        <span>{{ match.stats.shots_b }}</span>
                    </div>
                    <div v-if="match.stats.shots_on_target_a !== null" class="flex justify-between text-xs text-[#f4f2ef]">
                        <span>{{ match.stats.shots_on_target_a }}</span>
                        <span class="text-[10px] text-[#7a8299] font-condensed">A puerta</span>
                        <span>{{ match.stats.shots_on_target_b }}</span>
                    </div>
                    <div v-if="match.stats.cards_a !== null" class="flex justify-between text-xs text-[#f4f2ef]">
                        <span>{{ match.stats.cards_a }}</span>
                        <span class="text-[10px] text-[#7a8299] font-condensed">Tarjetas</span>
                        <span>{{ match.stats.cards_b }}</span>
                    </div>
                </div>
            </div>

            <!-- Goal scorers -->
            <div v-if="goalScorers && goalScorers.length" class="bg-[#242b3d] border border-[#343d54] rounded-2xl overflow-hidden">
                <div class="px-5 py-3 border-b border-[#343d54]">
                    <h2 class="text-sm font-bold text-[#f4f2ef] font-condensed tracking-wider uppercase">Goleadores</h2>
                </div>
                <div class="p-5 space-y-2">
                    <div v-for="gs in goalScorers" :key="gs.player_name" class="flex items-center justify-between py-1 border-b border-[#343d54]/30 last:border-0">
                        <span class="text-sm text-[#f4f2ef] font-medium">{{ gs.player_name }}</span>
                        <span class="text-sm font-bold text-[#ffb35e] font-condensed">{{ gs.goals }} gol{{ gs.goals > 1 ? 'es' : '' }}</span>
                    </div>
                    <div v-if="goalScorers.some(gs => gs.minutes?.length)" class="mt-2 pt-2 border-t border-[#343d54]/50">
                        <span class="text-[10px] text-[#7a8299] font-condensed">Minutos: </span>
                        <span v-for="(gs, i) in goalScorers.filter(gs => gs.minutes?.length)" :key="i" class="text-[10px] text-[#f4f2ef]">
                            {{ gs.player_name }} ({{ gs.minutes.join(', ') }}){{ i < goalScorers.length - 1 ? ', ' : '' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
