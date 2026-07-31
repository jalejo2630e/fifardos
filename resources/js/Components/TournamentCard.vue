<script setup>
import { Link } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import { computed } from 'vue';
import { useCountUp } from '@/composables/useCountUp';

const props = defineProps({
    tournament: Object,
});

const playedCount = computed(() => props.tournament.matches_played || 0);
const totalMatches = computed(() => props.tournament.matches_count || 0);
const leaderPts = computed(() => props.tournament.leader?.pts || 0);
const color = computed(() => props.tournament.color || '#F97316');

const isTeam = computed(() => props.tournament.is_team);
const competitorCount = computed(() => isTeam.value
    ? (props.tournament.teams_count || 0)
    : (props.tournament.players_count || 0));
const competitorLabel = computed(() => props.tournament.competitor_label || (isTeam.value ? 'equipos' : 'jugadores'));
const venueLabel = computed(() => props.tournament.mode === 'physical' ? 'canchas' : 'consolas');
const sportName = computed(() => props.tournament.sport_name || '—');
const sportIcon = computed(() => props.tournament.sport_icon || '🏆');

const estimatedMinutes = computed(() => props.tournament.estimated_minutes || 0);
const durationText = computed(() => {
    const min = estimatedMinutes.value;
    if (!min) return null;
    const h = Math.floor(min / 60);
    const mm = min % 60;
    if (h === 0) return `${mm} min`;
    return mm === 0 ? `${h} h` : `${h} h ${mm} min`;
});

const animatedPlayed = useCountUp(playedCount);
const animatedTotal = useCountUp(totalMatches);
const animatedLeaderPts = useCountUp(leaderPts);
</script>

<template>
    <Link :href="route('tournaments.show', tournament.id)" class="block group">
        <div
            class="ucl-card p-5 sm:p-6 h-full flex flex-col transition-all duration-300 ease-out"
            :class="[
                'hover:shadow-[0_0_20px_rgba(249,115,22,0.3)]'
            ]"
            :style="{
                '--t-color': color,
                '--t-color-rgb': parseInt(color.slice(1,3), 16) + ',' + parseInt(color.slice(3,5), 16) + ',' + parseInt(color.slice(5,7), 16)
            }">
            <div class="stars-overlay" />

            <div class="relative flex items-start justify-between mb-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0
                                group-hover:scale-110 transition-transform duration-300"
                         :style="{
                             background: `linear-gradient(135deg, ${color}22, ${color}08)`,
                             border: `1px solid ${color}33`
                         }"
                         :title="sportName">
                        {{ sportIcon }}
                    </div>
                    <div class="min-w-0">
                        <div class="ucl-title-md text-base sm:text-lg leading-tight transition-colors duration-200 truncate"
                             :style="{ color: 'var(--t-color)' }">
                            {{ tournament.name }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-condensed font-semibold uppercase tracking-wide"
                                  :style="{ background: `${color}15`, border: `1px solid ${color}33`, color }">
                                {{ sportIcon }} {{ sportName }}
                            </span>
                            <span class="ucl-meta">{{ competitorCount }} {{ competitorLabel }}</span>
                        </div>
                    </div>
                </div>
                <StatusBadge :status="tournament.status" />
            </div>

            <div v-if="tournament.status === 'in_progress' && totalMatches > 0" class="relative mt-auto mb-4">
                <ProgressBar
                    :value="playedCount"
                    :max="totalMatches"
                    :detail="animatedPlayed + '/' + animatedTotal"
                />
                <div v-if="tournament.leader"
                     class="mt-2 text-xs text-white/40 flex items-center gap-1.5">
                    <span>🔥</span>
                    <span class="font-condensed font-bold text-elite-secondary">{{ tournament.leader.name }}</span>
                    <span>{{ animatedLeaderPts }} pts</span>
                </div>
            </div>

            <div class="relative mt-auto flex items-center justify-between pt-4 border-t border-white/5">
                <div class="flex items-center gap-4 text-xs text-white/30">
                    <span class="flex items-center gap-1.5">
                        <svg v-if="venueLabel === 'canchas'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-5.5-7-11a7 7 0 1114 0c0 5.5-7 11-7 11z" />
                            <circle cx="12" cy="10" r="2.5" />
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ tournament.consoles_count }} {{ venueLabel }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ totalMatches }} partidos
                    </span>
                    <span v-if="durationText" class="flex items-center gap-1.5" title="Duración estimada">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="9" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" />
                        </svg>
                        ~{{ durationText }}
                    </span>
                </div>
                <svg class="w-4 h-4 text-white/20 transition-all duration-200"
                     :style="{ color: color + '66' }"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </div>

            <!-- Hover glow overlay -->
            <div class="absolute inset-0 rounded-2xl pointer-events-none transition-opacity duration-300 opacity-0 group-hover:opacity-100"
                 :style="{ boxShadow: `inset 0 0 0 1px ${color}44, 0 0 24px ${color}18` }" />
        </div>
    </Link>
</template>
