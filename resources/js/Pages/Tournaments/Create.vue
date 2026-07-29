<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n({
    useScope: 'local',
    messages: {
        es: {
            headTitle: 'Nuevo Torneo',
            headerNew: 'Nuevo',
            headerTournament: 'Torneo',
            headerSubtitle: 'Configura tu torneo FIFA Champions',
            stepName: 'Nombre',
            stepTvs: 'TVs',
            stepPlayers: 'Jugadores',
            tournamentNameLabel: 'Nombre del Torneo',
            tournamentNamePlaceholder: 'Ej: FIFA WORLD CUP 2026',
            tournamentNameHint: 'Elige un nombre épico para tu torneo',
            formatLabel: 'Formato del torneo',
            formatHint: '¿Cómo se define el campeón?',
            formatGroupsKnockout: 'Grupos + eliminatorias',
            formatGroupsKnockoutDesc: 'Todos contra todos y luego llaves hasta la final.',
            formatLeague: 'Liga · todos contra todos',
            formatLeagueDesc: 'Una sola tabla; campeón = primero al final.',
            homeAndAway: 'Ida y vuelta',
            homeAndAwayDesc: 'Cada cruce se juega dos veces (local y visitante).',
            consolesLabel: 'Televisores / Consolas',
            consolesHint: '¿Cuántas pantallas disponibles?',
            minutesLabel: 'Minutos por partido',
            minutesHint: 'Duración de cada partido',
            minuteUnit: '{m} min',
            playersLabel: 'Jugadores',
            playersHint: 'Añade a los participantes del torneo',
            playerNamePlaceholder: 'Nombre del jugador',
            addPlayer: '+ Añadir',
            noPlayers: 'Sin jugadores',
            playersCount: '{n} jugador | {n} jugadores',
            emptyPlayers: 'No hay jugadores aún. ¡Añade al menos 2!',
            estimatedDuration: 'Duración estimada',
            estimateLeague: '{total} partidos de liga{extra}',
            estimateLeagueHomeAway: ' (ida y vuelta)',
            estimateKnockout: '{total} partidos ({group} de grupos{extra} + {knockout} de eliminatorias)',
            estimateKnockoutHomeAway: ' ida y vuelta',
            estimateParallel: ' · {tv} {tvLabel} en paralelo · {m} min por partido.',
            estimateTvSingular: 'TV/cancha',
            estimateTvPlural: 'TVs/canchas',
            estimateDisclaimer: 'Estimado aproximado, sin contar descansos entre partidos.',
            emailReminder: 'Recordatorio por email',
            optional: '· opcional',
            emailReminderDesc: 'Te enviaremos un correo en esta fecha recordándote que tienes un torneo pendiente.',
            confirmationEmail: 'Enviarme un email de confirmación ahora',
            previous: 'Anterior',
            next: 'Siguiente',
            creating: 'Creando...',
            startTournament: 'Iniciar Torneo',
            durationDash: '—',
            durationMin: '{mm} min',
            durationHour: '{h} h',
            durationHourMin: '{h} h {mm} min',
        },
        en: {
            headTitle: 'New Tournament',
            headerNew: 'New',
            headerTournament: 'Tournament',
            headerSubtitle: 'Set up your FIFA Champions tournament',
            stepName: 'Name',
            stepTvs: 'TVs',
            stepPlayers: 'Players',
            tournamentNameLabel: 'Tournament Name',
            tournamentNamePlaceholder: 'e.g. FIFA WORLD CUP 2026',
            tournamentNameHint: 'Pick an epic name for your tournament',
            formatLabel: 'Tournament format',
            formatHint: 'How is the champion decided?',
            formatGroupsKnockout: 'Groups + knockout',
            formatGroupsKnockoutDesc: 'Round-robin and then brackets up to the final.',
            formatLeague: 'League · round-robin',
            formatLeagueDesc: 'A single table; champion = first at the end.',
            homeAndAway: 'Home and away',
            homeAndAwayDesc: 'Each matchup is played twice (home and away).',
            consolesLabel: 'TVs / Consoles',
            consolesHint: 'How many screens are available?',
            minutesLabel: 'Minutes per match',
            minutesHint: 'Duration of each match',
            minuteUnit: '{m} min',
            playersLabel: 'Players',
            playersHint: 'Add the tournament participants',
            playerNamePlaceholder: 'Player name',
            addPlayer: '+ Add',
            noPlayers: 'No players',
            playersCount: '{n} player | {n} players',
            emptyPlayers: 'No players yet. Add at least 2!',
            estimatedDuration: 'Estimated duration',
            estimateLeague: '{total} league matches{extra}',
            estimateLeagueHomeAway: ' (home and away)',
            estimateKnockout: '{total} matches ({group} group{extra} + {knockout} knockout)',
            estimateKnockoutHomeAway: ' home and away',
            estimateParallel: ' · {tv} {tvLabel} in parallel · {m} min per match.',
            estimateTvSingular: 'TV/pitch',
            estimateTvPlural: 'TVs/pitches',
            estimateDisclaimer: 'Rough estimate, not counting breaks between matches.',
            emailReminder: 'Email reminder',
            optional: '· optional',
            emailReminderDesc: 'We will email you on this date to remind you that you have a pending tournament.',
            confirmationEmail: 'Send me a confirmation email now',
            previous: 'Previous',
            next: 'Next',
            creating: 'Creating...',
            startTournament: 'Start Tournament',
            durationDash: '—',
            durationMin: '{mm} min',
            durationHour: '{h} h',
            durationHourMin: '{h} h {mm} min',
        },
    },
});

const form = useForm({
    name: '',
    consoles_count: 1,
    minutes_per_match: 6,
    format: 'groups_knockout',
    home_and_away: false,
    players: [],
    reminder_at: '',
    notify_email: false,
});

const newPlayer = ref('');
const step = ref(1);

// Estimador de duración: round-robin (liga o fase de grupos, ida/vuelta si aplica)
// + eliminatorias (solo en groups_knockout), en paralelo por TV.
const estimate = computed(() => {
    const p = form.players.length;
    const tv = Math.max(1, form.consoles_count);
    const m = Math.max(1, form.minutes_per_match);
    if (p < 2) return null;
    let group = (p * (p - 1)) / 2;
    if (form.home_and_away) group *= 2;
    let knockout = 0;
    if (form.format === 'groups_knockout') {
        let top = p <= 4 ? 4 : (p <= 8 ? 8 : 16);
        top = Math.min(top, p);
        top = Math.pow(2, Math.floor(Math.log2(top)));
        if (top < 2) top = 2;
        knockout = top >= 4 ? top : 1;
    }
    const total = group + knockout;
    const slots = Math.ceil(total / tv);
    return { group, knockout, total, minutes: slots * m, tv, m };
});

function fmtDuration(min) {
    if (!min || min <= 0) return t('durationDash');
    const h = Math.floor(min / 60);
    const mm = min % 60;
    if (h === 0) return t('durationMin', { mm });
    return mm === 0 ? t('durationHour', { h }) : t('durationHourMin', { h, mm });
}

const minReminder = computed(() => {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 16);
});

function addPlayer() {
    const name = newPlayer.value.trim();
    if (!name) return;
    if (form.players.includes(name)) return;
    form.players.push(name);
    newPlayer.value = '';
}

function removePlayer(index) {
    form.players.splice(index, 1);
}

function nextStep() {
    if (step.value === 1 && !form.name.trim()) return;
    if (step.value === 2) { step.value = 3; return; }
    step.value++;
}

function prevStep() {
    step.value--;
}

function submit() {
    if (form.players.length < 2) return;
    // El input datetime-local entrega hora LOCAL sin zona; la convertimos a ISO/UTC
    // para que el servidor (TZ UTC) la guarde y compare en el instante correcto.
    form.transform((data) => ({
        ...data,
        reminder_at: data.reminder_at ? new Date(data.reminder_at).toISOString() : null,
    })).post(route('tournaments.store'));
}

const playerCountText = computed(() => {
    const n = form.players.length;
    if (n === 0) return t('noPlayers');
    return t('playersCount', n, { named: { n } });
});

const canNext = computed(() => {
    if (step.value === 1) return form.name.trim().length > 0;
    if (step.value === 2) return form.consoles_count >= 1;
    return form.players.length >= 2;
});
</script>

<template>
    <Head :title="t('headTitle')" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="ucl-title-lg">
                    {{ t('headerNew') }} <span class="text-elite-secondary">{{ t('headerTournament') }}</span>
                </h1>
                <p class="ucl-meta mt-1">{{ t('headerSubtitle') }}</p>
            </div>
        </template>

        <div class="py-6 sm:py-8 lg:py-10">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <!-- Steps indicator -->
                <div class="flex items-center gap-3 sm:gap-4 mb-8 sm:mb-10">
                    <template v-for="s in 3" :key="s">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div :class="[
                                'ucl-step shrink-0',
                                step === s ? 'ucl-step-active' :
                                (step > s || (s === 3 && form.players.length >= 2)) ? 'ucl-step-done' : 'ucl-step-pending'
                            ]">
                                <span v-if="step > s || (s === 3 && form.players.length >= 2)">✓</span>
                                <span v-else>{{ s }}</span>
                            </div>
                            <span class="hidden sm:block font-condensed text-xs tracking-[0.1em] uppercase"
                                  :class="step === s ? 'text-elite-secondary' : 'text-white/20'">
                                {{ s === 1 ? t('stepName') : s === 2 ? t('stepTvs') : t('stepPlayers') }}
                            </span>
                        </div>
                        <div v-if="s < 3" class="flex-1 h-px bg-white/10 last:hidden" />
                    </template>
                </div>

                <form @submit.prevent="submit">
                    <!-- Step 1: Name -->
                    <div v-show="step === 1" class="ucl-card p-6 sm:p-8 animate-fade-up">
                        <div class="stars-overlay" />
                        <div class="relative">
                            <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40 mb-3">
                                {{ t('tournamentNameLabel') }}
                            </label>
                            <input v-model="form.name" type="text"
                                   :placeholder="t('tournamentNamePlaceholder')"
                                   class="ucl-input-lg text-base sm:text-xl tracking-wider text-center h-14"
                                   maxlength="40" />
                            <p class="text-xs text-white/20 text-center mt-3">{{ t('tournamentNameHint') }}</p>
                            <p v-if="form.errors.name" class="text-sm text-red-400 mt-2 text-center">{{ form.errors.name }}</p>
                        </div>
                    </div>

                    <!-- Step 2: Consoles -->
                    <div v-show="step === 2" class="ucl-card p-6 sm:p-8 animate-fade-up">
                        <div class="stars-overlay" />
                        <div class="relative space-y-6">
                            <!-- Formato del torneo -->
                            <div>
                                <div class="text-center mb-3">
                                    <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40">
                                        {{ t('formatLabel') }}
                                    </label>
                                    <p class="text-xs text-white/20 mt-1">{{ t('formatHint') }}</p>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <button type="button" @click="form.format = 'groups_knockout'"
                                            class="p-3 rounded-xl border text-left transition-all"
                                            :class="form.format === 'groups_knockout' ? 'bg-elite-secondary/15 border-elite-secondary/40' : 'bg-white/5 border-white/5 hover:bg-white/10'">
                                        <span class="block font-condensed font-bold text-sm"
                                              :class="form.format === 'groups_knockout' ? 'text-elite-secondary' : 'text-white/70'">
                                            {{ t('formatGroupsKnockout') }}
                                        </span>
                                        <span class="block text-[11px] text-white/30 mt-0.5">{{ t('formatGroupsKnockoutDesc') }}</span>
                                    </button>
                                    <button type="button" @click="form.format = 'league'"
                                            class="p-3 rounded-xl border text-left transition-all"
                                            :class="form.format === 'league' ? 'bg-elite-secondary/15 border-elite-secondary/40' : 'bg-white/5 border-white/5 hover:bg-white/10'">
                                        <span class="block font-condensed font-bold text-sm"
                                              :class="form.format === 'league' ? 'text-elite-secondary' : 'text-white/70'">
                                            {{ t('formatLeague') }}
                                        </span>
                                        <span class="block text-[11px] text-white/30 mt-0.5">{{ t('formatLeagueDesc') }}</span>
                                    </button>
                                </div>
                                <!-- Ida y vuelta -->
                                <label class="mt-2 flex items-center gap-3 cursor-pointer select-none rounded-xl border border-white/5 bg-white/5 p-3">
                                    <input v-model="form.home_and_away" type="checkbox"
                                           class="w-4 h-4 rounded accent-elite-secondary bg-white/10" />
                                    <span>
                                        <span class="block text-sm text-white/80 font-medium">{{ t('homeAndAway') }}</span>
                                        <span class="block text-[11px] text-white/30">{{ t('homeAndAwayDesc') }}</span>
                                    </span>
                                </label>
                            </div>

                            <div class="text-center pt-5 border-t border-white/5">
                                <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40 mb-3">
                                    {{ t('consolesLabel') }}
                                </label>
                                <p class="text-xs text-white/20">{{ t('consolesHint') }}</p>
                            </div>

                            <div class="flex items-center justify-center gap-5">
                                <button type="button" @click="form.consoles_count = Math.max(1, form.consoles_count - 1)"
                                        class="min-h-touch min-w-touch flex items-center justify-center rounded-xl bg-white/10 text-white font-bold text-xl hover:bg-white/20 transition-all">
                                    −
                                </button>
                                <div class="w-20 h-16 rounded-xl bg-elite-secondary/10 border border-elite-secondary/20
                                            flex items-center justify-center font-condensed font-bold text-3xl text-elite-secondary">
                                    {{ form.consoles_count }}
                                </div>
                                <button type="button" @click="form.consoles_count = Math.min(20, form.consoles_count + 1)"
                                        class="min-h-touch min-w-touch flex items-center justify-center rounded-xl bg-white/10 text-white font-bold text-xl hover:bg-white/20 transition-all">
                                    +
                                </button>
                            </div>

                            <!-- TV indicators -->
                            <div class="flex justify-center gap-2 flex-wrap">
                                <div v-for="i in Math.min(form.consoles_count, 12)" :key="i"
                                     class="w-8 h-8 rounded-lg bg-gradient-to-br from-elite-secondary/20 to-orange-700/10
                                            border border-elite-secondary/20 flex items-center justify-center
                                            font-condensed font-bold text-xs text-elite-secondary">
                                    {{ i }}
                                </div>
                                <div v-if="form.consoles_count > 12"
                                     class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-xs text-white/30">
                                    +{{ form.consoles_count - 12 }}
                                </div>
                            </div>

                            <!-- Minutos por partido -->
                            <div class="pt-5 border-t border-white/5">
                                <div class="text-center mb-3">
                                    <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40">
                                        {{ t('minutesLabel') }}
                                    </label>
                                    <p class="text-xs text-white/20 mt-1">{{ t('minutesHint') }}</p>
                                </div>
                                <div class="flex justify-center flex-wrap gap-2">
                                    <button v-for="m in [4, 5, 6, 8, 10, 12]" :key="m" type="button"
                                            @click="form.minutes_per_match = m"
                                            class="px-4 py-2 rounded-xl font-condensed font-bold text-sm border transition-all"
                                            :class="form.minutes_per_match === m
                                                ? 'bg-elite-secondary/15 border-elite-secondary/40 text-elite-secondary'
                                                : 'bg-white/5 border-white/5 text-white/50 hover:text-white hover:bg-white/10'">
                                        {{ t('minuteUnit', { m }) }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Players -->
                    <div v-show="step === 3" class="ucl-card p-6 sm:p-8 animate-fade-up">
                        <div class="stars-overlay" />
                        <div class="relative space-y-5">
                            <div class="text-center">
                                <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40 mb-3">
                                    {{ t('playersLabel') }}
                                </label>
                                <p class="text-xs text-white/20">{{ t('playersHint') }}</p>
                            </div>

                            <!-- Add input -->
                            <div class="flex gap-3">
                                <input v-model="newPlayer" @keydown.enter.prevent="addPlayer" type="text"
                                       :placeholder="t('playerNamePlaceholder')"
                                       class="ucl-input flex-1" />
                                <button @click="addPlayer" type="button"
                                        class="ucl-btn-primary min-h-touch px-5 text-xs">
                                    {{ t('addPlayer') }}
                                </button>
                            </div>

                            <!-- Player count -->
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-px bg-white/5" />
                                <span class="text-xs font-condensed tracking-wider"
                                      :class="form.players.length >= 2 ? 'text-ucl-gold' : 'text-white/20'">
                                    {{ playerCountText }}
                                </span>
                                <div class="flex-1 h-px bg-white/5" />
                            </div>

                            <!-- Player tags -->
                            <div v-if="form.players.length > 0" class="flex flex-wrap gap-2">
                                <div v-for="(player, idx) in form.players" :key="idx"
                                     class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 border border-white/5
                                            group/tag transition-all hover:border-white/10">
                                    <span class="w-6 h-6 rounded-lg bg-elite-secondary/10 flex items-center justify-center
                                                 text-xs font-bold text-elite-secondary shrink-0">
                                        {{ idx + 1 }}
                                    </span>
                                    <span class="text-sm font-medium text-white/80 truncate max-w-[120px]">{{ player }}</span>
                                    <button @click="removePlayer(idx)" type="button"
                                            class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center
                                                   text-[10px] text-white/30 hover:bg-red-500/30 hover:text-red-400 transition-all shrink-0">
                                        ✕
                                    </button>
                                </div>
                            </div>
                            <p v-else class="text-center text-xs text-white/15 py-4">
                                {{ t('emptyPlayers') }}
                            </p>

                            <p v-if="form.errors.players" class="text-sm text-red-400 text-center">{{ form.errors.players }}</p>

                            <!-- Simulador de duración -->
                            <div v-if="estimate" class="rounded-2xl border border-elite-secondary/25 bg-elite-secondary/[0.06] p-4 sm:p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5">
                                        <svg class="w-5 h-5 text-elite-secondary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <circle cx="12" cy="12" r="9" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" />
                                        </svg>
                                        <span class="font-condensed text-sm tracking-[0.1em] uppercase text-white/50">{{ t('estimatedDuration') }}</span>
                                    </div>
                                    <span class="font-condensed font-bold text-2xl sm:text-3xl text-elite-secondary leading-none">≈ {{ fmtDuration(estimate.minutes) }}</span>
                                </div>
                                <p class="text-xs text-white/30 mt-3 leading-relaxed">
                                    <template v-if="form.format === 'league'">
                                        {{ t('estimateLeague', { total: estimate.total, extra: form.home_and_away ? t('estimateLeagueHomeAway') : '' }) }}
                                    </template>
                                    <template v-else>
                                        {{ t('estimateKnockout', { total: estimate.total, group: estimate.group, extra: form.home_and_away ? t('estimateKnockoutHomeAway') : '', knockout: estimate.knockout }) }}
                                    </template>
                                    {{ t('estimateParallel', { tv: estimate.tv, tvLabel: estimate.tv === 1 ? t('estimateTvSingular') : t('estimateTvPlural'), m: estimate.m }) }}
                                </p>
                                <p class="text-[11px] text-white/20 mt-1">{{ t('estimateDisclaimer') }}</p>
                            </div>

                            <!-- Recordatorio por email (opcional) -->
                            <div class="pt-5 mt-1 border-t border-white/5 space-y-3">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-elite-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <label class="font-condensed text-sm tracking-[0.1em] uppercase text-white/40">
                                        {{ t('emailReminder') }}
                                        <span class="text-white/20 normal-case tracking-normal">{{ t('optional') }}</span>
                                    </label>
                                </div>
                                <input v-model="form.reminder_at" type="datetime-local" :min="minReminder"
                                       class="ucl-input w-full" />
                                <p class="text-xs text-white/25 leading-relaxed">
                                    {{ t('emailReminderDesc') }}
                                </p>
                                <label class="flex items-center gap-3 cursor-pointer select-none">
                                    <input v-model="form.notify_email" type="checkbox"
                                           class="w-4 h-4 rounded accent-elite-secondary bg-white/10" />
                                    <span class="text-sm text-white/60">{{ t('confirmationEmail') }}</span>
                                </label>
                                <p v-if="form.errors.reminder_at" class="text-sm text-red-400">{{ form.errors.reminder_at }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="flex items-center justify-between mt-6 sm:mt-8">
                        <button v-if="step > 1" @click="prevStep" type="button"
                                class="ucl-btn-ghost text-xs sm:text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            {{ t('previous') }}
                        </button>
                        <div v-else />

                        <button v-if="step < 3" @click="nextStep" type="button" :disabled="!canNext"
                                class="ucl-btn-primary text-xs sm:text-sm">
                            {{ t('next') }}
                            <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <button v-else type="submit" :disabled="form.processing || form.players.length < 2"
                                class="ucl-btn-gold text-xs sm:text-sm">
                            <svg v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            {{ form.processing ? t('creating') : t('startTournament') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
