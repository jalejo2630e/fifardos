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
            headerSubtitle: 'Elegí el deporte y armá el torneo que quieras',
            stepSport: 'Deporte',
            stepName: 'Nombre',
            stepSetup: 'Formato',
            stepCompetitors: 'Competidores',
            sportLabel: '¿Qué deporte?',
            sportHint: 'Torneos individuales o por equipos',
            individualTag: 'Individual',
            teamTag: 'Equipos',
            tournamentNameLabel: 'Nombre del Torneo',
            tournamentNamePlaceholder: 'Ej: Copa del Barrio 2026',
            tournamentNameHint: 'Elige un nombre épico para tu torneo',
            formatLabel: 'Formato del torneo',
            formatHint: '¿Cómo se define el campeón?',
            formatGroupsKnockout: 'Grupos + eliminatorias',
            formatGroupsKnockoutDesc: 'Todos contra todos y luego llaves hasta la final.',
            formatLeague: 'Liga · todos contra todos',
            formatLeagueDesc: 'Una sola tabla; campeón = primero al final.',
            homeAndAway: 'Ida y vuelta',
            homeAndAwayDesc: 'Cada cruce se juega dos veces (local y visitante).',
            venuesLabel: 'Espacios disponibles',
            venuesHint: 'Canchas, consolas o mesas disponibles en paralelo',

            playersLabel: 'Jugadores',
            playersHint: 'Añade a los participantes del torneo',
            playerNamePlaceholder: 'Nombre del jugador',
            addPlayer: '+ Añadir',
            noPlayers: 'Sin jugadores',
            playersCount: '{n} jugador | {n} jugadores',
            emptyPlayers: 'No hay jugadores aún. ¡Añade al menos 2!',
            teamsLabel: 'Equipos',
            teamsHint: 'Añade los equipos y sus integrantes (opcional)',
            teamNamePlaceholder: 'Nombre del equipo',
            addTeam: '+ Añadir equipo',
            memberPlaceholder: 'Jugador del equipo',
            noTeams: 'Sin equipos',
            emptyTeams: 'No hay equipos aún. ¡Añade al menos 2!',
            membersCount: '{n} integrantes',
            estimatedMatches: 'Partidos estimados',
            estimateLeague: '{total} partidos de liga{extra}',
            estimateLeagueHomeAway: ' (ida y vuelta)',
            estimateKnockout: '{total} partidos ({group} de grupos{extra} + {knockout} de eliminatorias)',
            estimateKnockoutHomeAway: ' ida y vuelta',
            estimateParallel: ' · {tv} {tvLabel} en paralelo.',
            estimateTvSingular: 'espacio',
            estimateTvPlural: 'espacios',
            estimateDisclaimer: 'Estimado aproximado, sin contar descansos entre partidos.',
            emailReminder: 'Recordatorio por email',
            optional: '· opcional',
            emailReminderDesc: 'Te enviaremos un correo en esta fecha recordándote que tienes un torneo pendiente.',
            confirmationEmail: 'Enviarme un email de confirmación ahora',
            previous: 'Anterior',
            next: 'Siguiente',
            creating: 'Creando...',
            startTournament: 'Iniciar Torneo',
            pickSportFirst: 'Elegí un deporte para continuar',
            teamPlayersHint: 'Los integrantes se registran pero la tabla y los partidos son por equipo.',
            sectionVirtual: 'Videojuegos / Consola',
            sectionPhysical: 'Campo Fisico',
            rulesLabel: 'Reglas del torneo',
            rulesHint: 'Cada deporte tiene sus propias reglas. Ajustalas a tu torneo.',
            rulesGroupGeneral: 'Reglas generales',
            rulesGroupTiempo: 'Tiempo',
            rulesGroupSets: 'Sets',
            rulesGroupMarcador: 'Marcador',
            rulesGroupDesempate: 'Desempate',
            venuesLabelVirtual: 'Consolas disponibles',
            venuesLabelPhysical: 'Canchas disponibles',
            venuesHintVirtual: 'Consolas disponibles en paralelo',
            venuesHintPhysical: 'Canchas disponibles en paralelo',
            estimateTvSingularVirtual: 'consola',
            estimateTvPluralVirtual: 'consolas',
            estimateTvSingularPhysical: 'cancha',
            estimateTvPluralPhysical: 'canchas',
            ruleOpt: {
                ilimitado: 'Ilimitado',
                sin_reloj: 'Sin reloj',
                solo_saque_anota: 'Solo el saque anota',
                rally_point: 'Rally point',
                amateur: 'Amateur',
                semi_pro: 'Semiprofesional',
                pro: 'Profesional',
                world_class: 'Clase mundial',
                legendary: 'Leyenda',
                judge: 'Por jueces',
                knockout_only: 'Solo knockout',
                mixed: 'Mixto (jueces + KO)',
            },
        },
        en: {
            headTitle: 'New Tournament',
            headerNew: 'New',
            headerTournament: 'Tournament',
            headerSubtitle: 'Pick a sport and build any tournament you want',
            stepSport: 'Sport',
            stepName: 'Name',
            stepSetup: 'Format',
            stepCompetitors: 'Competitors',
            sportLabel: 'Which sport?',
            sportHint: 'Individual or team tournaments',
            individualTag: 'Individual',
            teamTag: 'Teams',
            tournamentNameLabel: 'Tournament Name',
            tournamentNamePlaceholder: 'e.g. Neighborhood Cup 2026',
            tournamentNameHint: 'Pick an epic name for your tournament',
            formatLabel: 'Tournament format',
            formatHint: 'How is the champion decided?',
            formatGroupsKnockout: 'Groups + knockout',
            formatGroupsKnockoutDesc: 'Round-robin and then brackets up to the final.',
            formatLeague: 'League · round-robin',
            formatLeagueDesc: 'A single table; champion = first at the end.',
            homeAndAway: 'Home and away',
            homeAndAwayDesc: 'Each matchup is played twice (home and away).',
            venuesLabel: 'Available spaces',
            venuesHint: 'Courts, consoles or tables available in parallel',

            playersLabel: 'Players',
            playersHint: 'Add the tournament participants',
            playerNamePlaceholder: 'Player name',
            addPlayer: '+ Add',
            noPlayers: 'No players',
            playersCount: '{n} player | {n} players',
            emptyPlayers: 'No players yet. Add at least 2!',
            teamsLabel: 'Teams',
            teamsHint: 'Add teams and their members (optional)',
            teamNamePlaceholder: 'Team name',
            addTeam: '+ Add team',
            memberPlaceholder: 'Team player',
            noTeams: 'No teams',
            emptyTeams: 'No teams yet. Add at least 2!',
            membersCount: '{n} members',
            estimatedMatches: 'Estimated matches',
            estimateLeague: '{total} league matches{extra}',
            estimateLeagueHomeAway: ' (home and away)',
            estimateKnockout: '{total} matches ({group} group{extra} + {knockout} knockout)',
            estimateKnockoutHomeAway: ' home and away',
            estimateParallel: ' · {tv} {tvLabel} in parallel.',
            estimateTvSingular: 'space',
            estimateTvPlural: 'spaces',
            estimateDisclaimer: 'Rough estimate, not counting breaks between matches.',
            emailReminder: 'Email reminder',
            optional: '· optional',
            emailReminderDesc: 'We will email you on this date to remind you that you have a pending tournament.',
            confirmationEmail: 'Send me a confirmation email now',
            previous: 'Previous',
            next: 'Next',
            creating: 'Creating...',
            startTournament: 'Start Tournament',
            pickSportFirst: 'Pick a sport to continue',
            teamPlayersHint: 'Players are registered but the table and matches are per team.',
            sectionVirtual: 'Videojuegos / Consola',
            sectionPhysical: 'Campo Fisico',
            rulesLabel: 'Tournament rules',
            rulesHint: 'Each sport has its own rules. Adjust them for your tournament.',
            rulesGroupGeneral: 'General rules',
            rulesGroupTiempo: 'Time',
            rulesGroupSets: 'Sets',
            rulesGroupMarcador: 'Scoring',
            rulesGroupDesempate: 'Tiebreaker',
            venuesLabelVirtual: 'Available consoles',
            venuesLabelPhysical: 'Available courts',
            venuesHintVirtual: 'Consoles available in parallel',
            venuesHintPhysical: 'Courts available in parallel',
            estimateTvSingularVirtual: 'console',
            estimateTvPluralVirtual: 'consoles',
            estimateTvSingularPhysical: 'court',
            estimateTvPluralPhysical: 'courts',
            ruleOpt: {
                ilimitado: 'Unlimited',
                sin_reloj: 'No shot clock',
                solo_saque_anota: 'Side-out scoring',
                rally_point: 'Rally point',
                amateur: 'Amateur',
                semi_pro: 'Semi-pro',
                pro: 'Pro',
                world_class: 'World class',
                legendary: 'Legendary',
                judge: 'Judge decision',
                knockout_only: 'Knockout only',
                mixed: 'Mixed (judges + KO)',
            },
        },
    },
});

const { locale: globalLocale } = useI18n();

const props = defineProps({
    sports: {
        type: Object,
        required: true,
    },
    rules: {
        type: Object,
        required: true,
    },
});

const sportsList = computed(() => Object.values(props.sports));
const virtualSports = computed(() => sportsList.value.filter(s => s.mode === 'virtual'));
const physicalSports = computed(() => sportsList.value.filter(s => s.mode !== 'virtual'));

const form = useForm({
    name: '',
    sport: '',
    mode: 'virtual',
    consoles_count: 1,
    format: 'groups_knockout',
    home_and_away: false,
    rules: {},
    players: [],
    teams: [],
    reminder_at: '',
    notify_email: false,
});

const newPlayer = ref('');
const newTeam = ref('');
const newTeamMember = ref({});
const step = ref(1);

const currentSport = computed(() => (form.sport ? props.sports[form.sport] : null));
const isTeam = computed(() => currentSport.value?.type === 'team');

const isEn = computed(() => globalLocale.value === 'en');

/** Etiqueta según idioma actual (con fallback al label en español). */
function L(label, labelEn) {
    return isEn.value && labelEn ? labelEn : label;
}

function selectSport(key) {
    form.sport = key;
    const sportData = props.sports[key];
    if (sportData && sportData.mode) {
        form.mode = sportData.mode;
    }
    form.players = [];
    form.teams = [];
    const defaults = {};
    for (const def of props.rules[key] || []) {
        defaults[def.key] = def.default ?? (def.type === 'boolean' ? '0' : '');
    }
    form.rules = defaults;
}

// Reglas del deporte seleccionado, agrupadas para la UI (excluye tiempo por partido)
const rulesForSport = computed(() => (form.sport ? (props.rules[form.sport] || []).filter(r => r.key !== 'tiempo_partido_min') : []));
const rulesGroups = computed(() => {
    const groups = [];
    for (const def of rulesForSport.value) {
        const name = def.group || 'general';
        let g = groups.find((x) => x.name === name);
        if (!g) {
            g = { name, items: [] };
            groups.push(g);
        }
        g.items.push(def);
    }
    return groups;
});

const groupLabel = (name) => t(`rulesGroup${name.charAt(0).toUpperCase()}${name.slice(1)}`);
const ruleLabel = (d) => L(d.label, d.label_en);
const ruleNote = (d) => L(d.note, d.note_en);
const ruleOptLabel = (opt) => (t('ruleOpt')?.[opt] ?? opt);

const physical = computed(() => form.mode === 'physical');
const tvSingular = computed(() => t(physical.value ? 'estimateTvSingularPhysical' : 'estimateTvSingularVirtual'));
const tvPlural = computed(() => t(physical.value ? 'estimateTvPluralPhysical' : 'estimateTvPluralVirtual'));

// Estimador de partidos: round-robin + eliminatorias, en paralelo por espacio.
const estimate = computed(() => {
    const n = isTeam.value ? form.teams.length : form.players.length;
    const tv = Math.max(1, form.consoles_count);
    if (n < 2) return null;
    let group = (n * (n - 1)) / 2;
    if (form.home_and_away) group *= 2;
    let knockout = 0;
    if (form.format === 'groups_knockout') {
        let top = n <= 4 ? 4 : (n <= 8 ? 8 : 16);
        top = Math.min(top, n);
        top = Math.pow(2, Math.floor(Math.log2(top)));
        if (top < 2) top = 2;
        knockout = top >= 4 ? top : 1;
    }
    const total = group + knockout;
    const slots = Math.ceil(total / tv);
    return { group, knockout, total, tv };
});

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

function addTeam() {
    const name = newTeam.value.trim();
    if (!name) return;
    if (form.teams.some((tm) => tm.name === name)) return;
    form.teams.push({ name, players: [] });
    newTeam.value = '';
}

function removeTeam(index) {
    form.teams.splice(index, 1);
    delete newTeamMember.value[index];
}

function addMember(teamIndex) {
    const name = (newTeamMember.value[teamIndex] || '').trim();
    if (!name) return;
    const team = form.teams[teamIndex];
    if (team.players.includes(name)) return;
    team.players.push(name);
    newTeamMember.value[teamIndex] = '';
}

function removeMember(teamIndex, memberIndex) {
    form.teams[teamIndex].players.splice(memberIndex, 1);
}

function nextStep() {
    if (step.value === 1) { if (form.sport) step.value = 2; return; }
    if (step.value === 2) { if (form.name.trim()) step.value = 3; return; }
    if (step.value === 3) { step.value = 4; return; }
    step.value++;
}

function prevStep() {
    step.value--;
}

function submit() {
    if (isTeam.value) {
        if (form.teams.length < 2) return;
        form.transform((data) => ({
            ...data,
            sport: data.sport,
            teams: data.teams.map((tm) => ({ name: tm.name, players: tm.players })),
            players: [],
            reminder_at: data.reminder_at ? new Date(data.reminder_at).toISOString() : null,
        })).post(route('tournaments.store'));
    } else {
        if (form.players.length < 2) return;
        form.transform((data) => ({
            ...data,
            teams: [],
            reminder_at: data.reminder_at ? new Date(data.reminder_at).toISOString() : null,
        })).post(route('tournaments.store'));
    }
}

const playerCountText = computed(() => {
    const n = form.players.length;
    if (n === 0) return t('noPlayers');
    return t('playersCount', n, { named: { n } });
});

const teamCountText = computed(() => {
    const n = form.teams.length;
    if (n === 0) return t('noTeams');
    return t('playersCount', n, { named: { n } });
});

const canNext = computed(() => {
    if (step.value === 1) return !!form.sport;
    if (step.value === 2) return form.name.trim().length > 0;
    if (step.value === 3) return form.consoles_count >= 1;
    return isTeam.value ? form.teams.length >= 2 : form.players.length >= 2;
});

const stepCount = 4;
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
                    <template v-for="s in stepCount" :key="s">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div :class="[
                                'ucl-step shrink-0',
                                step === s ? 'ucl-step-active' :
                                (step > s || (s === 4 && canNext)) ? 'ucl-step-done' : 'ucl-step-pending'
                            ]">
                                <span v-if="step > s || (s === 4 && canNext)">✓</span>
                                <span v-else>{{ s }}</span>
                            </div>
                            <span class="hidden sm:block font-condensed text-xs tracking-[0.1em] uppercase"
                                  :class="step === s ? 'text-elite-secondary' : 'text-white/20'">
                                {{ s === 1 ? t('stepSport') : s === 2 ? t('stepName') : s === 3 ? t('stepSetup') : t('stepCompetitors') }}
                            </span>
                        </div>
                        <div v-if="s < stepCount" class="flex-1 h-px bg-white/10 last:hidden" />
                    </template>
                </div>

                <form @submit.prevent="submit">
                    <!-- Step 1: Sport -->
                    <div v-show="step === 1" class="ucl-card p-6 sm:p-8 animate-fade-up">
                        <div class="stars-overlay" />
                        <div class="relative">
                            <div class="text-center mb-5">
                                <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40">
                                    {{ t('sportLabel') }}
                                </label>
                                <p class="text-xs text-white/20 mt-1">{{ t('sportHint') }}</p>
                            </div>
                            <!-- Videojuegos / Consola -->
                            <div v-if="virtualSports.length" class="mb-6">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-lg">🎮</span>
                                    <h3 class="font-condensed text-xs tracking-[0.1em] uppercase text-elite-secondary">
                                        {{ t('sectionVirtual') }}
                                    </h3>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    <button v-for="sp in virtualSports" :key="sp.key ?? sp.slug" type="button"
                                            @click="selectSport(sp.key ?? sp.slug)"
                                            class="p-3 rounded-xl border text-left transition-all group"
                                            :class="form.sport === (sp.key ?? sp.slug)
                                                ? 'bg-elite-secondary/15 border-elite-secondary/40'
                                                : 'bg-white/5 border-white/5 hover:bg-white/10'">
                                        <div class="text-2xl leading-none">{{ sp.icon }}</div>
                                        <div class="mt-2 font-condensed font-bold text-xs sm:text-sm"
                                             :class="form.sport === (sp.key ?? sp.slug) ? 'text-elite-secondary' : 'text-white/70'">
                                            {{ sp.name }}
                                        </div>
                                        <div class="text-[10px] text-white/30 mt-0.5">
                                            {{ sp.type === 'team' ? t('teamTag') : t('individualTag') }}
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Campo Fisico -->
                            <div v-if="physicalSports.length">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-lg">⚽</span>
                                    <h3 class="font-condensed text-xs tracking-[0.1em] uppercase text-elite-secondary">
                                        {{ t('sectionPhysical') }}
                                    </h3>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    <button v-for="sp in physicalSports" :key="sp.key ?? sp.slug" type="button"
                                            @click="selectSport(sp.key ?? sp.slug)"
                                            class="p-3 rounded-xl border text-left transition-all group"
                                            :class="form.sport === (sp.key ?? sp.slug)
                                                ? 'bg-elite-secondary/15 border-elite-secondary/40'
                                                : 'bg-white/5 border-white/5 hover:bg-white/10'">
                                        <div class="text-2xl leading-none">{{ sp.icon }}</div>
                                        <div class="mt-2 font-condensed font-bold text-xs sm:text-sm"
                                             :class="form.sport === (sp.key ?? sp.slug) ? 'text-elite-secondary' : 'text-white/70'">
                                            {{ sp.name }}
                                        </div>
                                        <div class="text-[10px] text-white/30 mt-0.5">
                                            {{ sp.type === 'team' ? t('teamTag') : t('individualTag') }}
                                            · {{ sp.players_per_side }}v{{ sp.players_per_side }}
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <p v-if="!form.sport" class="text-center text-xs text-white/15 mt-5">{{ t('pickSportFirst') }}</p>
                        </div>
                    </div>

                    <!-- Step 2: Name -->
                    <div v-show="step === 2" class="ucl-card p-6 sm:p-8 animate-fade-up">
                        <div class="stars-overlay" />
                        <div class="relative">
                            <div class="text-center mb-3">
                                <span class="text-3xl">{{ currentSport?.icon }}</span>
                                <span class="block font-condensed text-xs tracking-[0.1em] uppercase text-elite-secondary mt-1">
                                    {{ currentSport?.name }}
                                </span>
                            </div>
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

                    <!-- Step 3: Format + venues -->
                    <div v-show="step === 3" class="ucl-card p-6 sm:p-8 animate-fade-up">
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
                                <label class="mt-2 flex items-center gap-3 cursor-pointer select-none rounded-xl border border-white/5 bg-white/5 p-3">
                                    <input v-model="form.home_and_away" type="checkbox"
                                           class="w-4 h-4 rounded accent-elite-secondary bg-white/10" />
                                    <span>
                                        <span class="block text-sm text-white/80 font-medium">{{ t('homeAndAway') }}</span>
                                        <span class="block text-[11px] text-white/30">{{ t('homeAndAwayDesc') }}</span>
                                    </span>
                                </label>
                            </div>

                            <!-- Reglas parametrizables del deporte -->
                            <div v-if="rulesForSport.length" class="pt-5 border-t border-white/5">
                                <div class="text-center mb-3">
                                    <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40">
                                        {{ t('rulesLabel') }}
                                    </label>
                                    <p class="text-xs text-white/20 mt-1">{{ t('rulesHint') }}</p>
                                </div>
                                <div v-if="form.errors.rules" class="mb-3 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-400">
                                    <template v-if="typeof form.errors.rules === 'string'">{{ form.errors.rules }}</template>
                                    <ul v-else class="list-disc list-inside space-y-0.5">
                                        <li v-for="(msg, k) in form.errors.rules" :key="k">{{ msg }}</li>
                                    </ul>
                                </div>
                                <div v-for="g in rulesGroups" :key="g.name" class="mt-4 first:mt-0">
                                    <h3 class="font-condensed text-[11px] tracking-[0.1em] uppercase text-elite-secondary mb-2">
                                        {{ groupLabel(g.name) }}
                                    </h3>
                                    <div class="space-y-2">
                                        <div v-for="d in g.items" :key="d.key"
                                             class="flex items-center justify-between gap-3 rounded-xl border border-white/5 bg-white/5 p-3">
                                            <div class="min-w-0">
                                                <span class="block text-sm text-white/80 leading-snug">{{ ruleLabel(d) }}</span>
                                                <span v-if="ruleNote(d)" class="block text-[11px] text-white/30 mt-0.5">{{ ruleNote(d) }}</span>
                                            </div>
                                            <div class="shrink-0">
                                                <!-- Boolean: toggle -->
                                                <button v-if="d.type === 'boolean'" type="button"
                                                        @click="form.rules[d.key] = form.rules[d.key] === '1' ? '0' : '1'"
                                                        class="relative w-11 h-6 rounded-full transition-all border"
                                                        :class="form.rules[d.key] === '1'
                                                            ? 'bg-elite-secondary/30 border-elite-secondary/50'
                                                            : 'bg-white/10 border-white/10'">
                                                    <span class="absolute top-0.5 w-5 h-5 rounded-full transition-all shadow"
                                                          :class="form.rules[d.key] === '1'
                                                              ? 'left-[22px] bg-elite-secondary'
                                                              : 'left-0.5 bg-white/40'" />
                                                </button>
                                                <!-- Number -->
                                                <input v-else-if="d.type === 'number'" type="number"
                                                       v-model.number="form.rules[d.key]"
                                                       :min="d.min" :max="d.max"
                                                       class="w-20 px-2 py-1.5 rounded-lg bg-white/10 border border-white/10
                                                              text-white text-sm text-center font-medium focus:outline-none focus:border-elite-secondary/50" />
                                                <!-- Select -->
                                                <select v-else v-model="form.rules[d.key]"
                                                        class="px-2 py-1.5 rounded-lg bg-white/10 border border-white/10
                                                               text-white text-sm font-medium focus:outline-none focus:border-elite-secondary/50
                                                               [&>option]:bg-slate-900">
                                                    <option v-for="opt in d.options" :key="opt" :value="opt">{{ ruleOptLabel(opt) }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center pt-5 border-t border-white/5">
                                <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40 mb-3">
                                    {{ form.mode === 'physical' ? t('venuesLabelPhysical') : t('venuesLabelVirtual') }}
                                </label>
                                <p class="text-xs text-white/20">{{ form.mode === 'physical' ? t('venuesHintPhysical') : t('venuesHintVirtual') }}</p>
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


                        </div>
                    </div>

                    <!-- Step 4: Competitors -->
                    <div v-show="step === 4" class="ucl-card p-6 sm:p-8 animate-fade-up">
                        <div class="stars-overlay" />
                        <div class="relative space-y-5">
                            <div class="text-center">
                                <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40 mb-3">
                                    {{ isTeam ? t('teamsLabel') : t('playersLabel') }}
                                </label>
                                <p class="text-xs text-white/20">{{ isTeam ? t('teamsHint') : t('playersHint') }}</p>
                            </div>

                            <!-- Individual: players -->
                            <template v-if="!isTeam">
                                <div class="flex gap-3">
                                    <input v-model="newPlayer" @keydown.enter.prevent="addPlayer" type="text"
                                           :placeholder="t('playerNamePlaceholder')"
                                           class="ucl-input flex-1" />
                                    <button @click="addPlayer" type="button"
                                            class="ucl-btn-primary min-h-touch px-5 text-xs">
                                        {{ t('addPlayer') }}
                                    </button>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-px bg-white/5" />
                                    <span class="text-xs font-condensed tracking-wider"
                                          :class="form.players.length >= 2 ? 'text-ucl-gold' : 'text-white/20'">
                                        {{ playerCountText }}
                                    </span>
                                    <div class="flex-1 h-px bg-white/5" />
                                </div>

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
                                <p v-else class="text-center text-xs text-white/15 py-4">{{ t('emptyPlayers') }}</p>
                            </template>

                            <!-- Teams -->
                            <template v-else>
                                <div class="flex gap-3">
                                    <input v-model="newTeam" @keydown.enter.prevent="addTeam" type="text"
                                           :placeholder="t('teamNamePlaceholder')"
                                           class="ucl-input flex-1" />
                                    <button @click="addTeam" type="button"
                                            class="ucl-btn-primary min-h-touch px-5 text-xs">
                                        {{ t('addTeam') }}
                                    </button>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-px bg-white/5" />
                                    <span class="text-xs font-condensed tracking-wider"
                                          :class="form.teams.length >= 2 ? 'text-ucl-gold' : 'text-white/20'">
                                        {{ teamCountText }}
                                    </span>
                                    <div class="flex-1 h-px bg-white/5" />
                                </div>

                                <p v-if="form.errors.teams" class="text-sm text-red-400 text-center">{{ form.errors.teams }}</p>

                                <div v-if="form.teams.length > 0" class="space-y-3">
                                    <div v-for="(team, ti) in form.teams" :key="ti"
                                         class="rounded-xl bg-white/5 border border-white/5 p-3 space-y-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-lg bg-elite-secondary/10 flex items-center justify-center
                                                         text-xs font-bold text-elite-secondary shrink-0">
                                                {{ ti + 1 }}
                                            </span>
                                            <input v-model="team.name" type="text"
                                                   class="ucl-input flex-1 py-2 text-sm font-semibold" />
                                            <button @click="removeTeam(ti)" type="button"
                                                    class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center
                                                           text-[10px] text-white/30 hover:bg-red-500/30 hover:text-red-400 transition-all shrink-0">
                                                ✕
                                            </button>
                                        </div>
                                        <div class="flex gap-2 pl-8">
                                            <input v-model="newTeamMember[ti]" @keydown.enter.prevent="addMember(ti)" type="text"
                                                   :placeholder="t('memberPlaceholder')"
                                                   class="ucl-input flex-1 py-2 text-sm" />
                                            <button @click="addMember(ti)" type="button"
                                                    class="ucl-btn-primary min-h-touch px-4 text-xs">
                                                {{ t('addPlayer') }}
                                            </button>
                                        </div>
                                        <div v-if="team.players.length" class="flex flex-wrap gap-1.5 pl-8">
                                            <div v-for="(member, mi) in team.players" :key="mi"
                                                 class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/5 border border-white/5">
                                                <span class="text-xs text-white/70">{{ member }}</span>
                                                <button @click="removeMember(ti, mi)" type="button"
                                                        class="text-[10px] text-white/30 hover:text-red-400">✕</button>
                                            </div>
                                        </div>
                                        <p class="pl-8 text-[11px] text-white/20">{{ t('membersCount', { n: team.players.length }) }}</p>
                                    </div>
                                </div>
                                <p v-else class="text-center text-xs text-white/15 py-4">{{ t('emptyTeams') }}</p>
                                <p class="text-center text-[11px] text-white/20">{{ t('teamPlayersHint') }}</p>
                            </template>

                            <p v-if="!isTeam && form.errors.players" class="text-sm text-red-400 text-center">{{ form.errors.players }}</p>

                            <!-- Estimador de partidos -->
                            <div v-if="estimate" class="rounded-2xl border border-elite-secondary/25 bg-elite-secondary/[0.06] p-4 sm:p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5">
                                        <svg class="w-5 h-5 text-elite-secondary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <circle cx="12" cy="12" r="9" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" />
                                        </svg>
                                        <span class="font-condensed text-sm tracking-[0.1em] uppercase text-white/50">{{ t('estimatedMatches') }}</span>
                                    </div>
                                    <span class="font-condensed font-bold text-2xl sm:text-3xl text-elite-secondary leading-none">{{ estimate.total }}</span>
                                </div>
                                <p class="text-xs text-white/30 mt-3 leading-relaxed">
                                    <template v-if="form.format === 'league'">
                                        {{ t('estimateLeague', { total: estimate.total, extra: form.home_and_away ? t('estimateLeagueHomeAway') : '' }) }}
                                    </template>
                                    <template v-else>
                                        {{ t('estimateKnockout', { total: estimate.total, group: estimate.group, extra: form.home_and_away ? t('estimateKnockoutHomeAway') : '', knockout: estimate.knockout }) }}
                                    </template>
                                    {{ t('estimateParallel', { tv: estimate.tv, tvLabel: estimate.tv === 1 ? tvSingular : tvPlural }) }}
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
                                <p class="text-xs text-white/25 leading-relaxed">{{ t('emailReminderDesc') }}</p>
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

                        <button v-if="step < stepCount" @click="nextStep" type="button" :disabled="!canNext"
                                class="ucl-btn-primary text-xs sm:text-sm">
                            {{ t('next') }}
                            <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <button v-else type="submit"
                                :disabled="form.processing || (isTeam ? form.teams.length < 2 : form.players.length < 2)"
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
