<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed, nextTick, reactive } from 'vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import { triggerConfetti } from '@/composables/useConfetti';
import { useI18n } from 'vue-i18n';

const { t } = useI18n({
    useScope: 'local',
    messages: {
        es: {
            // header
            headerPlayers: 'JUGADORES',
            headerTeams: 'EQUIPOS',
            headerMatches: 'PARTIDOS',
            headerBack: 'Volver',
            headerReplacePlayer: 'Reemplazar jugador',
            headerDeleteConfirm: '¿Eliminar torneo? Se borrarán todos los datos.',
            headerModeVirtual: 'VIRTUAL',
            headerModePhysical: 'CAMPO',
            venueConsoles: 'consolas',
            venueCourts: 'canchas',
            venueConsoleSingular: 'Consola',
            venueCourtSingular: 'Cancha',
            rulesTitle: 'Reglas del torneo',
            ruleYes: 'Sí',
            ruleNo: 'No',
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
            },
            // status bar
            statusFinished: 'FINALIZADO',
            statusInProgress: 'EN CURSO',
            statusMatchesPlayed: '{played} de {total} partidos jugados',
            // champion banner
            champion: 'Champion',
            championPts: '{pts} PTS',
            championRecord: '{pg}G {pe}E {pp}P',
            // tabs
            tabMatches: 'Partidos',
            tabStandings: 'Clasificación',
            tabKnockout: 'Eliminatorias',
            tabStats: 'Estadísticas',
            // matches
            round: 'Jornada {n}',
            roundMatches: '{n} PARTIDOS',
            matchFinal: '✓ FINAL',
            matchPending: 'PENDIENTE',
            matchEditResult: 'EDITAR RESULTADO',
            matchLoadResult: 'CARGAR RESULTADO',
            matchDatetimeLabel: 'Fecha y hora del partido',
            matchPenaltiesToggle: '+ Penales (desempate)',
            matchSetsLabel: 'Sets del partido',
            matchSetShort: 'S {n}',
            addSet: '+ Añadir set',
            setsSummary: '{sets} sets',
            matchAddStats: '+ Agregar estadísticas',
            matchPossession: 'Posesión',
            matchShots: 'Tiros',
            matchShotsTotalA: 'Totales A',
            matchShotsTotalB: 'Totales B',
            matchShotsOnTargetA: 'A puerta A',
            matchShotsOnTargetB: 'A puerta B',
            matchCards: 'Tarjetas',
            matchCardsA: 'A',
            matchCardsB: 'B',
            matchGoalScorers: 'Goleadores del partido',
            matchAddScorer: '+ Añadir goleador',
            matchScorerPlayer: 'Jugador',
            matchScorerSelect: 'Seleccionar',
            matchScorerGoals: 'Goles',
            matchScorerMinutes: 'Minutos',
            matchSaveResult: 'GUARDAR RESULTADO',
            matchCancel: 'CANCELAR',
            // standings
            standingsTitle: 'Tabla de posiciones',
            standingsCopied: 'Copiado!',
            standingsCopy: 'Copiar tabla',
            standingsColPlayer: 'Jugador',
            standingsColTeam: 'Equipo',
            standingsPts: 'PTS',
            standingsPj: 'PJ',
            standingsPg: 'PG',
            standingsPe: 'PE',
            standingsPp: 'PP',
            standingsGf: 'GF',
            standingsGc: 'GC',
            standingsDg: 'DG',
            standingsCopyHeaderPos: 'Pos',
            legendPts: 'PTS = Puntos',
            legendPj: 'PJ = Jugados',
            legendPg: 'PG = Ganados',
            legendPe: 'PE = Empatados',
            legendPp: 'PP = Perdidos',
            legendGf: 'GF = Goles Favor',
            legendGc: 'GC = Goles Contra',
            legendDg: 'DG = Diferencia',
            // knockout / bracket
            bracketNoGroups: 'No hay partidos de grupo para generar eliminatorias.',
            bracketGroupInProgress: 'Fase de grupos en curso',
            bracketGroupInProgressText: 'Completá todos los partidos de la fase de grupos para desbloquear las eliminatorias.',
            bracketTitle: 'Fase eliminatoria',
            bracketPhasesCount: '{phases} fase · {matches} partidos',
            bracketPhasesCountPlural: '{phases} fases · {matches} partidos',
            bracketTie: 'EMP',
            bracketPending: 'PENDIENTE',
            bracketEdit: 'EDITAR',
            bracketSave: 'GUARDAR',
            bracketWaitingRival: 'Esperando rival...',
            bracketAdvance: 'avanza',
            bracketGrandFinal: 'Gran Final',
            bracketNoPhases: 'No hay eliminatorias generadas aún.',
            // stats
            statsPoints: 'Puntos',
            statsGoalsFor: 'Goles a favor',
            statsGoalsAgainst: 'Goles en contra',
            statsGoalDiff: 'Diferencia de gol',
            statsPointsFor: 'Puntos a favor',
            statsPointsAgainst: 'Puntos en contra',
            statsPointsDiff: 'Diferencia de puntos',
            statsSetsWon: 'Sets ganados',
            statsSetsLost: 'Sets perdidos',
            statsSetsDiff: 'Diferencia de sets',
            statsNoData: 'Sin datos todavía',
            statsNoDataText: 'Las gráficas aparecerán cuando cargues resultados de los partidos.',
            // modal
            modalTitle: 'Reemplazar jugador',
            modalPlayerToReplace: 'Jugador a reemplazar',
            modalSelectPlayer: 'Seleccionar jugador',
            modalNewPlayer: 'Nuevo jugador',
            modalNewPlaceholder: 'Nombre del reemplazo',
            modalCancel: 'Cancelar',
            modalReplace: 'Reemplazar',
            modalNote: 'Los partidos pendientes del jugador saliente se asignarán al nuevo. Los historiales (goles, partidos finalizados) se mantienen.',
        },
        en: {
            // header
            headerPlayers: 'PLAYERS',
            headerTeams: 'TEAMS',
            headerMatches: 'MATCHES',
            headerBack: 'Back',
            headerReplacePlayer: 'Replace player',
            headerDeleteConfirm: 'Delete tournament? All data will be erased.',
            headerModeVirtual: 'VIRTUAL',
            headerModePhysical: 'ON-SITE',
            venueConsoles: 'consoles',
            venueCourts: 'courts',
            venueConsoleSingular: 'Console',
            venueCourtSingular: 'Court',
            rulesTitle: 'Tournament rules',
            ruleYes: 'Yes',
            ruleNo: 'No',
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
            },
            // status bar
            statusFinished: 'FINISHED',
            statusInProgress: 'IN PROGRESS',
            statusMatchesPlayed: '{played} of {total} matches played',
            // champion banner
            champion: 'Champion',
            championPts: '{pts} PTS',
            championRecord: '{pg}W {pe}D {pp}L',
            // tabs
            tabMatches: 'Matches',
            tabStandings: 'Standings',
            tabKnockout: 'Knockout',
            tabStats: 'Statistics',
            // matches
            round: 'Matchday {n}',
            roundMatches: '{n} MATCHES',
            matchFinal: '✓ FINAL',
            matchPending: 'PENDING',
            matchEditResult: 'EDIT RESULT',
            matchLoadResult: 'ENTER RESULT',
            matchDatetimeLabel: 'Match date and time',
            matchPenaltiesToggle: '+ Penalties (tiebreaker)',
            matchSetsLabel: 'Match sets',
            matchSetShort: 'S {n}',
            addSet: '+ Add set',
            setsSummary: '{sets} sets',
            matchAddStats: '+ Add statistics',
            matchPossession: 'Possession',
            matchShots: 'Shots',
            matchShotsTotalA: 'Total A',
            matchShotsTotalB: 'Total B',
            matchShotsOnTargetA: 'On target A',
            matchShotsOnTargetB: 'On target B',
            matchCards: 'Cards',
            matchCardsA: 'A',
            matchCardsB: 'B',
            matchGoalScorers: 'Match goal scorers',
            matchAddScorer: '+ Add scorer',
            matchScorerPlayer: 'Player',
            matchScorerSelect: 'Select',
            matchScorerGoals: 'Goals',
            matchScorerMinutes: 'Minutes',
            matchSaveResult: 'SAVE RESULT',
            matchCancel: 'CANCEL',
            // standings
            standingsTitle: 'Standings table',
            standingsCopied: 'Copied!',
            standingsCopy: 'Copy table',
            standingsColPlayer: 'Player',
            standingsColTeam: 'Team',
            standingsPts: 'PTS',
            standingsPj: 'MP',
            standingsPg: 'W',
            standingsPe: 'D',
            standingsPp: 'L',
            standingsGf: 'GF',
            standingsGc: 'GA',
            standingsDg: 'GD',
            standingsCopyHeaderPos: 'Pos',
            legendPts: 'PTS = Points',
            legendPj: 'MP = Matches Played',
            legendPg: 'W = Won',
            legendPe: 'D = Drawn',
            legendPp: 'L = Lost',
            legendGf: 'GF = Goals For',
            legendGc: 'GA = Goals Against',
            legendDg: 'GD = Goal Difference',
            // knockout / bracket
            bracketNoGroups: 'There are no group matches to generate the knockout stage.',
            bracketGroupInProgress: 'Group stage in progress',
            bracketGroupInProgressText: 'Complete all group stage matches to unlock the knockout stage.',
            bracketTitle: 'Knockout stage',
            bracketPhasesCount: '{phases} phase · {matches} matches',
            bracketPhasesCountPlural: '{phases} phases · {matches} matches',
            bracketTie: 'TIE',
            bracketPending: 'PENDING',
            bracketEdit: 'EDIT',
            bracketSave: 'SAVE',
            bracketWaitingRival: 'Waiting for opponent...',
            bracketAdvance: 'advances',
            bracketGrandFinal: 'Grand Final',
            bracketNoPhases: 'No knockout stage generated yet.',
            // stats
            statsPoints: 'Points',
            statsGoalsFor: 'Goals for',
            statsGoalsAgainst: 'Goals against',
            statsGoalDiff: 'Goal difference',
            statsPointsFor: 'Points for',
            statsPointsAgainst: 'Points against',
            statsPointsDiff: 'Points difference',
            statsSetsWon: 'Sets won',
            statsSetsLost: 'Sets lost',
            statsSetsDiff: 'Sets difference',
            statsNoData: 'No data yet',
            statsNoDataText: 'Charts will appear once you enter match results.',
            // modal
            modalTitle: 'Replace player',
            modalPlayerToReplace: 'Player to replace',
            modalSelectPlayer: 'Select player',
            modalNewPlayer: 'New player',
            modalNewPlaceholder: 'Replacement name',
            modalCancel: 'Cancel',
            modalReplace: 'Replace',
            modalNote: 'The outgoing player\'s pending matches will be assigned to the new one. Histories (goals, finished matches) are kept.',
        },
    },
});

const props = defineProps({
    tournament: Object,
    sport: Object,
    standings: Array,
    allPlayed: Boolean,
    rounds: Array,
    groupAllPlayed: Boolean,
    phases: Array,
    goalScorers: Array,
    estimatedMinutes: { type: Number, default: 0 },
    tournamentRules: { type: Object, default: () => ({}) },
    rulesList: { type: Array, default: () => [] },
});

const { locale: globalLocale } = useI18n();
const isEn = computed(() => globalLocale.value === 'en');

const venueLabel = computed(() => (props.tournament?.mode === 'physical' ? t('venueCourts') : t('venueConsoles')));
const venueSingular = computed(() => (props.tournament?.mode === 'physical' ? t('venueCourtSingular') : t('venueConsoleSingular')));
const modeChip = computed(() => (props.tournament?.mode === 'physical' ? t('headerModePhysical') : t('headerModeVirtual')));

const ruleTitle = (r) => (isEn.value && r.label_en ? r.label_en : r.label);
const ruleValue = (r) => {
    if (r.type === 'boolean') return r.value === '1' ? t('ruleYes') : t('ruleNo');
    if (r.type === 'select') return t('ruleOpt')?.[r.value] ?? r.value;
    return r.value;
};

function fmtDuration(min) {
    if (!min || min <= 0) return '—';
    const h = Math.floor(min / 60);
    const mm = min % 60;
    if (h === 0) return `${mm} min`;
    return mm === 0 ? `${h} h` : `${h} h ${mm} min`;
}

const activeTab = ref('matches');
const localRounds = ref([]);
const editingMatchId = ref(null);
const formData = reactive({
    score1: 0,
    score2: 0,
    played_at: '',
    showStats: false,
    possession_a: null,
    possession_b: null,
    shots_a: null,
    shots_b: null,
    shots_on_target_a: null,
    shots_on_target_b: null,
    cards_a: null,
    cards_b: null,
    penalties1: null,
    penalties2: null,
    sets: [],
    goalScorers: [],
});

const editingMatchRef = ref(null);
const localPhases = ref([]);

function initPhases() {
    localPhases.value = props.phases ? JSON.parse(JSON.stringify(props.phases)) : [];
}
initPhases();
watch(() => props.phases, () => { initPhases(); }, { deep: true });

function initRounds() {
    localRounds.value = JSON.parse(JSON.stringify(props.rounds));
}
initRounds();
watch(() => props.rounds, initRounds, { deep: true });

function openResultForm(match) {
    editingMatchId.value = match.id;
    editingMatchRef.value = match;
    formData.score1 = match.score1 ?? 0;
    formData.score2 = match.score2 ?? 0;
    formData.played_at = new Date().toISOString().slice(0, 16);
    formData.showStats = false;
    formData.possession_a = null;
    formData.possession_b = null;
    formData.shots_a = null;
    formData.shots_b = null;
    formData.shots_on_target_a = null;
    formData.shots_on_target_b = null;
    formData.cards_a = null;
    formData.cards_b = null;
    formData.penalties1 = null;
    formData.penalties2 = null;
    formData.sets = (match.sets && match.sets.length)
        ? JSON.parse(JSON.stringify(match.sets))
        : [{ a: 0, b: 0 }];
    formData.goalScorers = [];
}

function cancelResultForm() {
    editingMatchId.value = null;
}

function saveScore(match) {
    if (isSets.value) {
        const sets = (match.sets && match.sets.length)
            ? match.sets.map(s => ({ a: Math.max(0, parseInt(s.a, 10) || 0), b: Math.max(0, parseInt(s.b, 10) || 0) }))
            : [{ a: 0, b: 0 }];
        if (sets.some(s => s.a === s.b)) return;
        router.post(route('matches.score.update', [props.tournament.id, match.id]), { sets });
        return;
    }
    const s1 = parseInt(match.score1, 10);
    const s2 = parseInt(match.score2, 10);
    if (isNaN(s1) || isNaN(s2) || s1 < 0 || s2 < 0) return;
    router.post(route('matches.score.update', [props.tournament.id, match.id]), { score1: s1, score2: s2 });
}

function submitScore(match) {
    const payload = {};

    if (formData.played_at) {
        payload.played_at = formData.played_at;
    }

    if (isSets.value) {
        const sets = formData.sets.map(s => ({
            a: Math.max(0, parseInt(s.a, 10) || 0),
            b: Math.max(0, parseInt(s.b, 10) || 0),
        }));
        if (sets.length === 0 || sets.some(s => s.a === s.b)) return;
        payload.sets = sets;
    } else {
        const s1 = parseInt(formData.score1, 10);
        const s2 = parseInt(formData.score2, 10);
        if (isNaN(s1) || isNaN(s2) || s1 < 0 || s2 < 0) return;
        payload.score1 = s1;
        payload.score2 = s2;

        // Penalties (only if tied in knockout)
        if (formData.penalties1 !== null && formData.penalties2 !== null) {
            payload.penalties1 = formData.penalties1;
            payload.penalties2 = formData.penalties2;
        }
    }

    if (formData.showStats) {
        const stats = {};
        if (formData.possession_a !== null && formData.possession_b !== null) {
            stats.possession_a = formData.possession_a;
            stats.possession_b = formData.possession_b;
        }
        if (formData.shots_a !== null) stats.shots_a = formData.shots_a;
        if (formData.shots_b !== null) stats.shots_b = formData.shots_b;
        if (formData.shots_on_target_a !== null) stats.shots_on_target_a = formData.shots_on_target_a;
        if (formData.shots_on_target_b !== null) stats.shots_on_target_b = formData.shots_on_target_b;
        if (formData.cards_a !== null) stats.cards_a = formData.cards_a;
        if (formData.cards_b !== null) stats.cards_b = formData.cards_b;
        if (Object.keys(stats).length > 0) {
            payload.stats = stats;
        }
    }

    // Goal scorers
    const validScorers = isGoals.value ? formData.goalScorers.filter(gs => gs.player_id && gs.goals > 0) : [];
    if (validScorers.length > 0) {
        payload.goal_scorers = validScorers.map(gs => ({
            player_id: gs.player_id,
            goals: gs.goals,
            minutes: gs.minutes && gs.minutes.length > 0 ? gs.minutes : null,
        }));
    }

    router.post(route('matches.score.update', [props.tournament.id, match.id]), payload);
}

function editMatch(match) {
    router.post(route('matches.score.edit', [props.tournament.id, match.id]));
}

const color = computed(() => props.tournament.color || '#F97316');
const isLeague = computed(() => props.tournament?.format === 'league');

const sport = computed(() => props.sport || {});
const isTeam = computed(() => sport.value.type === 'team');
const isSets = computed(() => sport.value.scoring === 'sets');
const maxSets = computed(() => sport.value.max_sets || 3);
const scoring = computed(() => sport.value.scoring || 'goals');
const isGoals = computed(() => scoring.value === 'goals');
const statLabels = computed(() => {
    if (scoring.value === 'points') {
        return {
            forShort: 'PF', againstShort: 'PC', diffShort: 'DP',
            forLabel: t('statsPointsFor'), againstLabel: t('statsPointsAgainst'), diffLabel: t('statsPointsDiff'),
        };
    }
    if (scoring.value === 'sets') {
        return {
            forShort: 'SG', againstShort: 'SP', diffShort: 'DS',
            forLabel: t('statsSetsWon'), againstLabel: t('statsSetsLost'), diffLabel: t('statsSetsDiff'),
        };
    }
    return {
        forShort: 'GF', againstShort: 'GC', diffShort: 'DG',
        forLabel: t('statsGoalsFor'), againstLabel: t('statsGoalsAgainst'), diffLabel: t('statsGoalDiff'),
    };
});
const competitorsCount = computed(() => isTeam.value
    ? (props.tournament?.teams?.length ?? 0)
    : (props.tournament?.players?.length ?? 0));

function competitorName(match, num) {
    if (num === 1) return match?.team1?.name || match?.player1?.name || '—';
    return match?.team2?.name || match?.player2?.name || '—';
}

function competitorShort(match, num) {
    const full = competitorName(match, num);
    if (full === '—') return full;
    return full.split(' ')[0];
}

function compId(match, num) {
    if (num === 1) return match?.team1_id ?? match?.player1_id ?? null;
    return match?.team2_id ?? match?.player2_id ?? null;
}

function standingName(s) {
    return s?.competitor_name || s?.player_name || s?.team_name || '—';
}

function setsSummary(match) {
    if (!match?.sets || !match.sets.length) return '';
    return match.sets.map(s => `${s.a}-${s.b}`).join(' · ');
}

function removeSet(idx) {
    if (formData.sets.length > 1) formData.sets.splice(idx, 1);
}

// Gráficas de barras de la clasificación (pestaña Estadísticas)
function buildBars(field) {
    const rows = (props.standings || []).map((r) => ({ name: standingName(r), value: Number(r[field]) || 0 }));
    rows.sort((a, b) => b.value - a.value);
    const max = Math.max(1, ...rows.map((r) => Math.abs(r.value)));
    return rows.map((r) => ({ ...r, pct: Math.round((Math.abs(r.value) / max) * 100) }));
}
const chartPts = computed(() => buildBars('pts'));
const chartGf = computed(() => buildBars('gf'));
const chartDg = computed(() => buildBars('dg'));
const totalMatches = computed(() => props.rounds.reduce((a, r) => a + r.length, 0));
const playedMatches = computed(() => props.rounds.reduce((a, r) => a + r.filter(m => m.status === 'finished').length, 0));
const progress = computed(() => totalMatches.value ? Math.round(playedMatches.value / totalMatches.value * 100) : 0);
const champion = computed(() => props.allPlayed && props.standings.length > 0 ? props.standings[0] : null);
const bracketPlayed = computed(() => props.phases ? props.phases.reduce((a, p) => a + p.matches.filter(m => m.status === 'finished').length, 0) : 0);
const bracketTotal = computed(() => props.phases ? props.phases.reduce((a, p) => a + p.matches.length, 0) : 0);
const allMatchesTotal = computed(() => totalMatches.value + bracketTotal.value);
const allMatchesPlayed = computed(() => playedMatches.value + bracketPlayed.value);
const statusText = computed(() => {
    if (props.allPlayed) return t('statusFinished');
    return t('statusInProgress');
});
const statusClass = computed(() => {
    if (props.allPlayed) return 'status-completed';
    return 'status-in-progress';
});

function hexToRgb(hex) {
    const v = parseInt(hex.slice(1), 16);
    return `${(v >> 16) & 255}, ${(v >> 8) & 255}, ${v & 255}`;
}

const replaceModalOpen = ref(false);
const replacePlayerId = ref(null);
const replaceNewName = ref('');

function openReplaceModal() {
    replacePlayerId.value = null;
    replaceNewName.value = '';
    replaceModalOpen.value = true;
}

function submitReplace() {
    if (!replacePlayerId.value || !replaceNewName.value.trim()) return;
    router.post(route('tournaments.players.replace', [props.tournament.id, replacePlayerId.value]), {
        new_name: replaceNewName.value.trim(),
    });
    replaceModalOpen.value = false;
}

const confettiFired = ref(false);
watch(() => props.allPlayed, (val) => {
    if (val && !confettiFired.value) {
        confettiFired.value = true;
        triggerConfetti();
    }
});

const copiedStandings = ref(false);

function copyStandings() {
    const header = [
        t('standingsCopyHeaderPos'), t('standingsColPlayer'),
        t('standingsPts'), t('standingsPj'), t('standingsPg'), t('standingsPe'),
        t('standingsPp'), statLabels.value.forShort, statLabels.value.againstShort, statLabels.value.diffShort,
    ].join('\t');
    const rows = props.standings.map((s, i) =>
        `${i + 1}\t${standingName(s)}\t${s.pts}\t${s.pj}\t${s.pg}\t${s.pe}\t${s.pp}\t${s.gf}\t${s.gc}\t${s.dg > 0 ? '+' : ''}${s.dg}`
    );
    navigator.clipboard.writeText([header, ...rows].join('\n')).then(() => {
        copiedStandings.value = true;
        setTimeout(() => { copiedStandings.value = false; }, 2000);
    });
}

// Bracket helpers
const bracketPhases = computed(() => {
    if (!localPhases.value.length) return [];
    return localPhases.value.filter(p => p.key !== 'third_place');
});

const thirdPlacePhase = computed(() => {
    if (!localPhases.value.length) return null;
    return localPhases.value.find(p => p.key === 'third_place') || null;
});

const hasGroups = computed(() => props.rounds && props.rounds.length > 0);

const finalPhase = computed(() => {
    if (!bracketPhases.value.length) return null;
    return bracketPhases.value[bracketPhases.value.length - 1];
});

function isWinner(match, playerNum) {
    if (match.status !== 'finished') return false;
    if (match.score1 === null || match.score2 === null) return false;
    if (match.score1 > match.score2) return playerNum === 1;
    if (match.score2 > match.score1) return playerNum === 2;
    // Tied — check penalties
    if (match.penalties1 !== null && match.penalties2 !== null) {
        if (match.penalties1 > match.penalties2) return playerNum === 1;
        if (match.penalties2 > match.penalties1) return playerNum === 2;
    }
    return false;
}

function getPlayerName(match, playerNum) {
    const name = competitorName(match, playerNum);
    if (match.status === 'pending' && !compId(match, playerNum)) return '—';
    return name;
}

function getMatchGap(phaseIndex, totalPhases) {
    // As we move right, gaps between matches increase to allow for connector lines
    return Math.pow(2, phaseIndex);
}
</script>

<template>
    <Head :title="tournament.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4"
                  :style="{ '--t-color': color, '--t-rgb': hexToRgb(color) }">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="ucl-title-lg truncate" :style="{ color: 'var(--t-color)' }">{{ tournament.name }}</h1>
                        <StatusBadge :status="tournament.status" />
                    </div>
                    <div class="flex items-center gap-3 mt-1.5 text-xs sm:text-sm text-white/30 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-medium"
                              :style="{ background: `${color}18`, border: `1px solid ${color}33`, color }">
                            {{ sport.icon }} {{ sport.name }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-medium text-white/60 border border-white/10 bg-white/5">
                            {{ modeChip }}
                        </span>
                        <span>{{ competitorsCount }} {{ isTeam ? t('headerTeams') : t('headerPlayers') }}</span>
                        <span class="w-1 h-1 rounded-full bg-white/10" />
                        <span>{{ tournament.consoles_count }} {{ venueLabel }}</span>
                        <span class="w-1 h-1 rounded-full bg-white/10" />
                        <span>{{ totalMatches }} {{ t('headerMatches') }}</span>
                        <span class="w-1 h-1 rounded-full bg-white/10" />
                        <span class="inline-flex items-center gap-1 text-elite-secondary/80">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                            ~{{ fmtDuration(estimatedMinutes) }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a :href="route('tournaments.public.bracket', tournament.slug)" target="_blank"
                       class="ucl-btn-ghost text-xs min-h-touch px-4">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                    <Link :href="route('tournaments.index')" class="ucl-btn-ghost text-xs min-h-touch px-4">
                        <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ t('headerBack') }}
                    </Link>
                    <button @click="openReplaceModal" class="ucl-btn-ghost text-xs min-h-touch px-4" :title="t('headerReplacePlayer')">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-4 4m0 0l-4-4m4 4V3" />
                        </svg>
                    </button>
                    <Link :href="route('tournaments.destroy', tournament.id)" method="delete" as="button"
                          class="ucl-btn-danger text-xs min-h-touch px-4"
                          :onclick="`return confirm('${t('headerDeleteConfirm')}')`">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8 lg:py-10"
             :style="{ '--t-color': color, '--t-rgb': hexToRgb(color) }">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">

                <!-- Status bar -->
                <div class="ucl-card animate-fade-up">
                    <div class="flex items-center justify-between gap-4 p-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <span class="status-pill" :class="statusClass">{{ statusText }}</span>
                            <span class="text-sm text-white/40 font-condensed tracking-wide">
                                {{ t('statusMatchesPlayed', { played: allMatchesPlayed, total: allMatchesTotal }) }}
                            </span>
                        </div>
                        <div v-if="!allPlayed" class="flex-1 max-w-xs">
                            <ProgressBar
                                :value="playedMatches"
                                :max="totalMatches"
                                :detail="`${progress}%`" />
                        </div>
                    </div>
                </div>

                <!-- Champion Banner -->
                <div v-if="champion" class="ucl-champion animate-scale-in">
                    <div class="relative z-10">
                        <div class="text-5xl sm:text-7xl mb-3 sm:mb-4">🏆</div>
                        <h2 class="font-condensed font-bold text-xl sm:text-2xl tracking-[0.08em] uppercase mb-2"
                            :style="{ color: color }">
                            {{ t('champion') }}
                        </h2>
                        <div class="ucl-title-lg text-3xl sm:text-5xl lg:text-6xl text-white animate-gold-pulse"
                             :style="{ textShadow: `0 0 20px ${color}44, 0 0 40px ${color}22` }">
                            {{ standingName(champion) }}
                        </div>
                        <div class="flex items-center justify-center gap-4 sm:gap-6 mt-4 text-xs sm:text-sm text-white/40 font-medium">
                            <span class="font-bold" :style="{ color: color }">{{ t('championPts', { pts: champion.pts }) }}</span>
                            <span class="w-1 h-1 rounded-full bg-white/10" />
                            <span>{{ t('championRecord', { pg: champion.pg, pe: champion.pe, pp: champion.pp }) }}</span>
                            <span class="w-1 h-1 rounded-full bg-white/10" />
                            <span>{{ statLabels.diffShort }} {{ (champion.dg > 0 ? '+' : '') + champion.dg }}</span>
                        </div>
                    </div>
                </div>

                <!-- Reglas del torneo -->
                <div v-if="rulesList.length" class="ucl-card animate-fade-up">
                    <div class="p-4 sm:p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-elite-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 20V4a1 1 0 011-1h12a1 1 0 011 1v16l-3-2-3 2-3-2-3 2z" />
                            </svg>
                            <span class="font-condensed text-xs tracking-[0.1em] uppercase text-white/50">{{ t('rulesTitle') }}</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="r in rulesList" :key="r.key"
                                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/5 border border-white/5 text-xs">
                                <span class="text-white/50">{{ ruleTitle(r) }}</span>
                                <span class="font-semibold text-white/90">{{ ruleValue(r) }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="ucl-card p-1 flex animate-fade-up">
                    <button @click="activeTab = 'matches'"
                            class="flex-1 min-h-touch flex items-center justify-center gap-2 rounded-xl font-condensed text-xs sm:text-sm uppercase tracking-[0.08em] transition-all duration-200"
                            :class="activeTab === 'matches' ? 'text-white' : 'text-white/30 hover:text-white/60'"
                            :style="activeTab === 'matches' ? { background: color + '18', color: color } : {}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ t('tabMatches') }}
                    </button>
                    <button @click="activeTab = 'standings'"
                            class="flex-1 min-h-touch flex items-center justify-center gap-2 rounded-xl font-condensed text-xs sm:text-sm uppercase tracking-[0.08em] transition-all duration-200"
                            :class="activeTab === 'standings' ? 'text-white' : 'text-white/30 hover:text-white/60'"
                            :style="activeTab === 'standings' ? { background: color + '18', color: color } : {}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        {{ t('tabStandings') }}
                    </button>
                    <button v-if="!isLeague" @click="activeTab = 'knockout'"
                            class="flex-1 min-h-touch flex items-center justify-center gap-2 rounded-xl font-condensed text-xs sm:text-sm uppercase tracking-[0.08em] transition-all duration-200"
                            :class="activeTab === 'knockout' ? 'text-white' : 'text-white/30 hover:text-white/60'"
                            :style="activeTab === 'knockout' ? { background: color + '18', color: color } : {}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        {{ t('tabKnockout') }}
                    </button>
                    <button @click="activeTab = 'stats'"
                            class="flex-1 min-h-touch flex items-center justify-center gap-2 rounded-xl font-condensed text-xs sm:text-sm uppercase tracking-[0.08em] transition-all duration-200"
                            :class="activeTab === 'stats' ? 'text-white' : 'text-white/30 hover:text-white/60'"
                            :style="activeTab === 'stats' ? { background: color + '18', color: color } : {}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ t('tabStats') }}
                    </button>
                </div>

                <!-- ====== MATCHES ====== -->
                <div v-if="activeTab === 'matches'" class="space-y-8 sm:space-y-10">
                    <div v-for="(round, rIdx) in localRounds" :key="rIdx" class="animate-fade-up">
                        <div class="flex items-center gap-3 sm:gap-4 mb-4 sm:mb-5">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center font-condensed font-bold shrink-0"
                                 :style="{
                                     background: `${color}22`,
                                     border: `1px solid ${color}44`,
                                     color: color
                                 }">
                                {{ rIdx + 1 }}
                            </div>
                            <h3 class="font-condensed font-bold text-base sm:text-lg uppercase tracking-[0.06em] text-white/80">
                                {{ t('round', { n: rIdx + 1 }) }}
                            </h3>
                            <div class="flex-1 h-px bg-gradient-to-r from-white/5 to-transparent" />
                            <span class="text-[10px] sm:text-xs font-condensed text-white/20 tracking-wider">{{ t('roundMatches', { n: round.length }) }}</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
                            <div v-for="match in round" :key="match.id"
                                 class="ucl-match"
                                 :class="{
                                     'played': match.status === 'finished',
                                     'editing-form': editingMatchId === match.id
                                 }"
                                 :style="match.status === 'finished' ? { borderColor: `${color}33`, background: `linear-gradient(135deg, ${color}08, rgba(14,22,48,0.95))` } : {}">
                                <div class="stars-overlay" />
                                <div class="relative space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-condensed font-bold uppercase tracking-wider"
                                              :style="{ background: `${color}18`, border: `1px solid ${color}33`, color: color }">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            {{ venueSingular }} {{ match.tv_number }}
                                        </span>
                                        <span v-if="match.status === 'finished'" class="text-[10px] font-condensed text-ucl-gold uppercase tracking-wider">{{ t('matchFinal') }}</span>
                                        <span v-else class="text-[10px] font-condensed text-white/15 uppercase tracking-wider">{{ t('matchPending') }}</span>
                                    </div>

                                    <!-- Players row -->
                                    <div class="flex items-center justify-between gap-3 py-1">
                                        <div class="flex-1 text-right">
                                             <div class="ucl-player"
                                                  :class="match.status === 'finished' ? (match.score1 > match.score2 ? 'winner' : 'loser') : ''">
                                                  {{ competitorName(match, 1) }}
                                             </div>
                                        </div>

                                        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                                            <template v-if="match.status === 'finished'">
                                                <span class="min-w-[2.5rem] sm:min-w-[3rem] text-center text-2xl sm:text-3xl font-condensed font-bold"
                                                      :class="match.score1 > match.score2 ? 'text-ucl-gold' : match.score1 < match.score2 ? 'text-white/30' : 'text-white/60'">
                                                    {{ match.score1 }}
                                                </span>
                                                <span class="text-white/10 font-condensed font-bold text-sm tracking-widest">:</span>
                                                <span class="min-w-[2.5rem] sm:min-w-[3rem] text-center text-2xl sm:text-3xl font-condensed font-bold"
                                                      :class="match.score2 > match.score1 ? 'text-ucl-gold' : match.score2 < match.score1 ? 'text-white/30' : 'text-white/60'">
                                                    {{ match.score2 }}
                                                </span>
                                            </template>
                                            <template v-else>
                                                <span class="text-white/40 font-condensed font-bold text-lg">vs</span>
                                            </template>
                                        </div>

                                        <div class="flex-1 text-left">
                                            <div class="ucl-player"
                                                 :class="match.status === 'finished' ? (match.score2 > match.score1 ? 'winner' : 'loser') : ''">
                                                {{ competitorName(match, 2) }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sets summary -->
                                    <div v-if="match.status === 'finished' && isSets && match.sets && match.sets.length"
                                         class="flex items-center justify-center gap-1.5 flex-wrap">
                                        <span v-for="(set, si) in match.sets" :key="si"
                                              class="px-1.5 py-0.5 rounded text-[9px] font-condensed font-bold"
                                              :class="set.a > set.b ? 'text-ucl-gold bg-ucl-gold/10' : set.b > set.a ? 'text-white/30 bg-white/5' : 'text-white/20 bg-white/5'">
                                            {{ set.a }}-{{ set.b }}
                                        </span>
                                        <span class="text-[9px] text-white/20 font-condensed tracking-wider ml-1">
                                            {{ t('setsSummary', { sets: match.sets.length }) }}
                                        </span>
                                    </div>

                                    <!-- Finished: edit button -->
                                    <div v-if="match.status === 'finished'" class="pt-2 border-t border-white/5">
                                        <button @click="editMatch(match)"
                                                class="w-full min-h-touch rounded-xl bg-white/5 text-white/40 hover:text-white hover:bg-white/10 font-condensed text-xs uppercase tracking-wider transition-all duration-200">
                                            {{ t('matchEditResult') }}
                                        </button>
                                    </div>

                                    <!-- Pending: inline form -->
                                    <div v-else>
                                        <!-- Collapsed: Cargar resultado button -->
                                        <div v-if="editingMatchId !== match.id" class="pt-2 border-t border-white/5">
                                            <button @click="openResultForm(match)"
                                                    class="w-full min-h-touch rounded-xl font-condensed text-xs uppercase tracking-wider transition-all duration-200 text-black"
                                                    :style="{
                                                        background: `linear-gradient(135deg, ${color}, ${color}cc)`,
                                                        boxShadow: `0 4px 16px ${color}33`
                                                    }">
                                                <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                                {{ t('matchLoadResult') }}
                                            </button>
                                        </div>

                                        <!-- Expanded: form -->
                                        <div v-else class="pt-3 border-t border-white/5 space-y-3">
                                            <!-- Sets editor -->
                                            <div v-if="isSets" class="space-y-2">
                                                <label class="text-[9px] text-white/30 font-condensed tracking-wider block">{{ t('matchSetsLabel') }}</label>
                                                <div v-for="(set, si) in formData.sets" :key="si"
                                                     class="flex items-center justify-center gap-3">
                                                    <div class="flex flex-col items-center">
                                                        <label class="text-[9px] text-white/30 font-condensed tracking-wider mb-1">{{ competitorShort(match, 1) }}</label>
                                                        <input type="number" min="0" class="score-input text-center" v-model.number="set.a" />
                                                    </div>
                                                    <span class="text-white/20 font-condensed font-bold text-sm">—</span>
                                                    <div class="flex flex-col items-center">
                                                        <label class="text-[9px] text-white/30 font-condensed tracking-wider mb-1">{{ competitorShort(match, 2) }}</label>
                                                        <input type="number" min="0" class="score-input text-center" v-model.number="set.b" />
                                                    </div>
                                                    <button @click="removeSet(si)"
                                                            class="text-white/20 hover:text-red-400 text-sm px-1 pb-1">✕</button>
                                                </div>
                                                <button v-if="formData.sets.length < maxSets" @click="formData.sets.push({ a: 0, b: 0 })"
                                                        class="text-[10px] text-white/30 hover:text-white/60 font-condensed tracking-wider">
                                                    {{ t('addSet') }}
                                                </button>
                                            </div>

                                            <!-- Score inputs -->
                                            <div v-else class="flex items-center justify-center gap-2 sm:gap-3">
                                                <div class="flex flex-col items-center">
                                                    <label class="text-[9px] text-white/30 font-condensed tracking-wider mb-1">{{ competitorShort(match, 1) }}</label>
                                                    <input type="number" min="0" class="score-input text-center" v-model.number="formData.score1" />
                                                </div>
                                                <span class="text-white/20 font-condensed font-bold text-sm">—</span>
                                                <div class="flex flex-col items-center">
                                                    <label class="text-[9px] text-white/30 font-condensed tracking-wider mb-1">{{ competitorShort(match, 2) }}</label>
                                                    <input type="number" min="0" class="score-input text-center" v-model.number="formData.score2" />
                                                </div>
                                            </div>

                                            <!-- Date/time -->
                                            <div>
                                                <label class="text-[9px] text-white/30 font-condensed tracking-wider block mb-1">{{ t('matchDatetimeLabel') }}</label>
                                                <input type="datetime-local" v-model="formData.played_at"
                                                       class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-xs font-condensed focus:outline-none focus:border-white/20" />
                                            </div>

                                            <!-- Penalties (knockout only, shown when tied) -->
                                            <div v-if="!isSets && match.phase !== 'group' && match.phase !== 'third_place'"
                                                 class="pt-1">
                                                <details class="group">
                                                    <summary class="text-[10px] text-white/30 hover:text-white/50 font-condensed tracking-wider cursor-pointer select-none">
                                                        {{ t('matchPenaltiesToggle') }}
                                                    </summary>
                                                    <div class="mt-2 flex items-center justify-center gap-3">
                                                        <div class="flex flex-col items-center">
                                                            <label class="text-[9px] text-white/30 font-condensed tracking-wider mb-1">{{ competitorShort(match, 1) }}</label>
                                                            <input type="number" min="0" v-model.number="formData.penalties1"
                                                                   class="w-14 text-center bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-white text-sm" />
                                                        </div>
                                                        <span class="text-white/20 font-condensed font-bold text-xs">—</span>
                                                        <div class="flex flex-col items-center">
                                                            <label class="text-[9px] text-white/30 font-condensed tracking-wider mb-1">{{ competitorShort(match, 2) }}</label>
                                                            <input type="number" min="0" v-model.number="formData.penalties2"
                                                                   class="w-14 text-center bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-white text-sm" />
                                                        </div>
                                                    </div>
                                                </details>
                                            </div>

                                            <!-- Optional stats (collapsible, solo deportes de goles) -->
                                            <details v-if="isGoals" class="group" @toggle="formData.showStats = $event.target.open">
                                                <summary class="text-[10px] text-white/30 hover:text-white/50 font-condensed tracking-wider cursor-pointer select-none">
                                                    {{ t('matchAddStats') }}
                                                </summary>
                                                <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                                                    <div class="col-span-2 flex items-center justify-between border-b border-white/5 pb-1 mb-1">
                                                        <span class="text-[9px] text-white/20 font-condensed tracking-wider">{{ t('matchPossession') }}</span>
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">{{ competitorShort(match, 1) }} %</label>
                                                        <input type="number" min="0" max="100" v-model.number="formData.possession_a"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">{{ competitorShort(match, 2) }} %</label>
                                                        <input type="number" min="0" max="100" v-model.number="formData.possession_b"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>

                                                    <div class="col-span-2 flex items-center justify-between border-b border-white/5 pb-1 mb-1 mt-1">
                                                        <span class="text-[9px] text-white/20 font-condensed tracking-wider">{{ t('matchShots') }}</span>
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">{{ t('matchShotsTotalA') }}</label>
                                                        <input type="number" min="0" v-model.number="formData.shots_a"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">{{ t('matchShotsTotalB') }}</label>
                                                        <input type="number" min="0" v-model.number="formData.shots_b"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">{{ t('matchShotsOnTargetA') }}</label>
                                                        <input type="number" min="0" v-model.number="formData.shots_on_target_a"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">{{ t('matchShotsOnTargetB') }}</label>
                                                        <input type="number" min="0" v-model.number="formData.shots_on_target_b"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>

                                                    <div class="col-span-2 flex items-center justify-between border-b border-white/5 pb-1 mb-1 mt-1">
                                                        <span class="text-[9px] text-white/20 font-condensed tracking-wider">{{ t('matchCards') }}</span>
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">{{ t('matchCardsA') }}</label>
                                                        <input type="number" min="0" v-model.number="formData.cards_a"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>
                                                    <div>
                                                        <label class="text-[9px] text-white/30 block">{{ t('matchCardsB') }}</label>
                                                        <input type="number" min="0" v-model.number="formData.cards_b"
                                                               class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                    </div>

                                                    <!-- Goal scorers -->
                                                    <div class="col-span-2 flex items-center justify-between border-b border-white/5 pb-1 mb-1 mt-1">
                                                        <span class="text-[9px] text-white/20 font-condensed tracking-wider">{{ t('matchGoalScorers') }}</span>
                                                        <button @click="formData.goalScorers.push({ player_id: null, goals: 1, minutes: [] })"
                                                                class="text-[9px] text-white/30 hover:text-white font-condensed tracking-wider">
                                                            {{ t('matchAddScorer') }}
                                                        </button>
                                                    </div>
                                                    <div v-for="(gs, gsIdx) in formData.goalScorers" :key="gsIdx"
                                                         class="col-span-2 grid grid-cols-12 gap-2 items-end border border-white/5 rounded-lg p-2">
                                                        <div class="col-span-5">
                                                            <label class="text-[8px] text-white/30 font-condensed tracking-wider block">{{ t('matchScorerPlayer') }}</label>
                                                            <select v-model="gs.player_id"
                                                                    class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs">
                                                                <option value="" disabled>{{ t('matchScorerSelect') }}</option>
                                                                <option v-for="p in props.tournament.players" :key="p.id" :value="p.id">
                                                                    {{ p.name }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                        <div class="col-span-2">
                                                            <label class="text-[8px] text-white/30 font-condensed tracking-wider block">{{ t('matchScorerGoals') }}</label>
                                                            <input type="number" min="1" v-model.number="gs.goals"
                                                                   class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                        </div>
                                                        <div class="col-span-4">
                                                            <label class="text-[8px] text-white/30 font-condensed tracking-wider block">{{ t('matchScorerMinutes') }}</label>
                                                            <input type="text" placeholder="12, 45, 67" :value="(gs.minutes || []).join(', ')"
                                                                   @input="e => { gs.minutes = e.target.value.split(',').map(m => parseInt(m.trim())).filter(m => !isNaN(m)) }"
                                                                   class="w-full bg-white/5 border border-white/10 rounded px-2 py-1 text-white text-xs" />
                                                        </div>
                                                        <div class="col-span-1 flex items-end pb-1">
                                                            <button @click="formData.goalScorers.splice(gsIdx, 1)"
                                                                    class="text-white/20 hover:text-red-400 text-sm">✕</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </details>

                                            <!-- Action buttons -->
                                            <div class="flex gap-2 pt-1">
                                                <button @click="submitScore(match)"
                                                        class="flex-1 min-h-touch rounded-xl font-condensed text-xs uppercase tracking-wider transition-all duration-200 text-black"
                                                        :style="{
                                                            background: `linear-gradient(135deg, ${color}, ${color}cc)`,
                                                            boxShadow: `0 4px 16px ${color}33`
                                                        }">
                                                    <svg class="w-4 h-4 mr-1.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    {{ t('matchSaveResult') }}
                                                </button>
                                                <button @click="cancelResultForm"
                                                        class="px-4 min-h-touch rounded-xl bg-white/5 text-white/30 hover:text-white/60 font-condensed text-xs uppercase tracking-wider transition-all duration-200">
                                                    {{ t('matchCancel') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ====== STANDINGS ====== -->
                <div v-if="activeTab === 'standings'" class="ucl-card overflow-hidden animate-fade-up">
                    <div class="px-5 sm:px-6 py-3 border-b border-white/5 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-white/80">{{ t('standingsTitle') }}</h3>
                        <button @click="copyStandings"
                                class="text-xs px-3 py-1.5 rounded-md font-medium transition-all duration-200"
                                :class="copiedStandings
                                    ? 'bg-emerald-500/20 text-emerald-300'
                                    : 'bg-white/10 text-white/40 hover:bg-white/20 hover:text-white/60'">
                            {{ copiedStandings ? t('standingsCopied') : t('standingsCopy') }}
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="ucl-table">
                            <thead>
                                <tr class="bg-white/[0.02]">
                                    <th class="w-10 sm:w-12 text-center">#</th>
                                    <th>{{ t(isTeam ? 'standingsColTeam' : 'standingsColPlayer') }}</th>
                                    <th class="text-center">{{ t('standingsPts') }}</th>
                                    <th class="text-center hidden sm:table-cell">{{ t('standingsPj') }}</th>
                                    <th class="text-center hidden sm:table-cell">{{ t('standingsPg') }}</th>
                                    <th class="text-center hidden sm:table-cell">{{ t('standingsPe') }}</th>
                                    <th class="text-center hidden sm:table-cell">{{ t('standingsPp') }}</th>
                                    <th class="text-center">{{ statLabels.forShort }}</th>
                                    <th class="text-center">{{ statLabels.againstShort }}</th>
                                    <th class="text-center">{{ statLabels.diffShort }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(s, idx) in standings" :key="s.competitor_id ?? s.player_id ?? s.team_id"
                                     :style="idx === 0 && allPlayed ? { background: `linear-gradient(90deg, ${color}12, transparent)` } : idx === 0 ? { background: `${color}08` } : {}">
                                    <td class="text-center font-condensed font-bold text-lg"
                                        :style="idx === 0 ? { color: allPlayed ? '#FFD700' : color } : {}">
                                        <span v-if="idx === 0 && allPlayed">👑</span>
                                        <span v-else>{{ idx + 1 }}</span>
                                    </td>
                                    <td class="font-semibold"
                                        :style="idx === 0 && allPlayed ? { color: '#FFD700' } : idx === 0 ? { color: color } : {}">
                                        {{ standingName(s) }}
                                    </td>
                                    <td class="text-center font-condensed font-bold text-lg sm:text-xl"
                                        :style="idx === 0 && allPlayed ? { color: '#FFD700' } : idx === 0 ? { color: color } : {}">
                                        {{ s.pts }}
                                    </td>
                                    <td class="text-center text-white/40 hidden sm:table-cell">{{ s.pj }}</td>
                                    <td class="text-center hidden sm:table-cell" :class="s.pg > 0 ? 'text-green-400' : 'text-white/30'">{{ s.pg }}</td>
                                    <td class="text-center text-white/40 hidden sm:table-cell">{{ s.pe }}</td>
                                    <td class="text-center hidden sm:table-cell" :class="s.pp > 0 ? 'text-red-400' : 'text-white/30'">{{ s.pp }}</td>
                                    <td class="text-center text-white/70 font-semibold">{{ s.gf }}</td>
                                    <td class="text-center text-white/70 font-semibold">{{ s.gc }}</td>
                                    <td class="text-center font-bold font-condensed"
                                        :class="s.dg > 0 ? 'text-green-400' : s.dg < 0 ? 'text-red-400' : 'text-white/30'">
                                        {{ s.dg > 0 ? '+' : '' }}{{ s.dg }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 sm:px-6 py-3 border-t border-white/5 flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-white/20 font-condensed tracking-wider">
                        <span>{{ t('legendPts') }}</span>
                        <span>{{ t('legendPj') }}</span>
                        <span>{{ t('legendPg') }}</span>
                        <span>{{ t('legendPe') }}</span>
                        <span>{{ t('legendPp') }}</span>
                        <span>{{ statLabels.forLabel }}</span>
                        <span>{{ statLabels.againstLabel }}</span>
                        <span>{{ statLabels.diffLabel }}</span>
                    </div>
                </div>

                <!-- ====== KNOCKOUT BRACKET ====== -->
                <div v-if="activeTab === 'knockout'" class="animate-fade-up space-y-6">

                    <!-- No groups yet -->
                    <div v-if="!hasGroups && !localPhases.length"
                         class="ucl-card p-8 text-center">
                        <p class="text-white/30 text-sm">{{ t('bracketNoGroups') }}</p>
                    </div>

                    <!-- Waiting for group stage -->
                    <div v-else-if="!groupAllPlayed && !localPhases.length"
                         class="ucl-card p-8 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-white/5 flex items-center justify-center">
                            <svg class="w-8 h-8 text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">{{ t('bracketGroupInProgress') }}</h3>
                        <p class="text-sm text-white/40 max-w-md mx-auto">{{ t('bracketGroupInProgressText') }}</p>
                    </div>

                    <!-- Bracket -->
                    <div v-else-if="bracketPhases.length" class="space-y-6">

                        <!-- Bracket header -->
                        <div class="ucl-card px-5 py-3 border-l-4"
                             :style="{ borderLeftColor: color }">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-semibold text-white">{{ t('bracketTitle') }}</h3>
                                    <p class="text-xs text-white/30 mt-0.5">
                                        {{ t(bracketPhases.length > 1 ? 'bracketPhasesCountPlural' : 'bracketPhasesCount', { phases: bracketPhases.length, matches: bracketPhases.reduce((a, p) => a + p.matches.length, 0) }) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-white/40">
                                    <span v-for="p in bracketPhases" :key="p.key"
                                          class="px-2 py-1 rounded-md"
                                          :class="p.allPlayed ? 'bg-emerald-500/10 text-emerald-300' : 'bg-white/5'">
                                        {{ p.label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Bracket layout - horizontal scroll on mobile -->
                        <div class="ucl-card overflow-hidden">
                            <div class="p-4 sm:p-6 overflow-x-auto">
                                <div class="flex items-stretch gap-0 min-w-[600px] sm:min-w-[700px] lg:min-w-0">

                                    <!-- Phase columns -->
                                    <template v-for="(phase, pIdx) in bracketPhases" :key="phase.key">
                                        <div class="flex flex-col shrink-0" style="width: 240px;">
                                            <!-- Phase header -->
                                            <div class="text-center mb-4">
                                                <span class="text-sm font-condensed font-bold uppercase tracking-[0.1em]"
                                                      :class="phase.allPlayed ? 'text-emerald-400' : ''"
                                                      :style="{ color: phase.allPlayed ? undefined : color }">
                                                    {{ phase.label }}
                                                </span>
                                            </div>

                                            <!-- Match cards -->
                                            <div class="flex flex-col flex-1 justify-around gap-4">
                                                <div v-for="(match, mIdx) in phase.matches" :key="match.id"
                                                     class="bracket-match"
                                                     :class="{
                                                         'finished': match.status === 'finished',
                                                         'pending': match.status === 'pending' && compId(match, 1) && compId(match, 2),
                                                         'empty': match.status === 'pending' && (!compId(match, 1) || !compId(match, 2))
                                                     }"
                                                     :style="match.status === 'finished' ? {
                                                         '--match-accent': color,
                                                         borderColor: `${color}55`,
                                                     } : {}">

                                                    <!-- Player 1 -->
                                                    <div class="bracket-player">
                                                        <div class="flex-1 truncate text-xs sm:text-sm font-medium"
                                                             :class="isWinner(match, 1) ? 'text-white font-bold' : match.status === 'finished' ? 'text-white/35' : 'text-white/60'">
                                                            {{ getPlayerName(match, 1) }}
                                                        </div>
                                                         <div class="min-w-[3rem] text-center">
                                                             <template v-if="match.status === 'finished'">
                                                                 <span class="text-sm font-bold"
                                                                       :class="isWinner(match, 1) ? 'text-ucl-gold text-base' : 'text-white/30'">
                                                                     {{ match.score1 }}
                                                                 </span>
                                                             </template>
                                                             <template v-else-if="!isSets && compId(match, 1)">
                                                                 <input type="number" min="0" placeholder="-"
                                                                        class="score-input"
                                                                        v-model.number="match.score1" />
                                                             </template>
                                                             <span v-else class="text-white/15 text-xs">—</span>
                                                         </div>
                                                         <span v-if="isWinner(match, 1)" class="winner-indicator">✓</span>
                                                     </div>

                                                     <!-- Separator -->
                                                     <div class="bracket-vs">
                                                         <span class="text-[10px] font-condensed text-white/[0.07] font-bold tracking-[0.15em]">VS</span>
                                                         <span v-if="match.status === 'finished' && match.score1 === match.score2"
                                                               class="text-[9px] text-amber-400/50 font-condensed">{{ t('bracketTie') }}</span>
                                                         <span v-if="match.status === 'pending' && compId(match, 1) && compId(match, 2)"
                                                               class="text-[9px] text-white/15 font-condensed">{{ t('bracketPending') }}</span>
                                                     </div>

                                                     <!-- Player 2 -->
                                                     <div class="bracket-player">
                                                         <div class="flex-1 truncate text-xs sm:text-sm font-medium"
                                                              :class="isWinner(match, 2) ? 'text-white font-bold' : match.status === 'finished' ? 'text-white/35' : 'text-white/60'">
                                                             {{ getPlayerName(match, 2) }}
                                                         </div>
                                                         <div class="min-w-[3rem] text-center">
                                                             <template v-if="match.status === 'finished'">
                                                                 <span class="text-sm font-bold"
                                                                       :class="isWinner(match, 2) ? 'text-ucl-gold text-base' : 'text-white/30'">
                                                                     {{ match.score2 }}
                                                                 </span>
                                                             </template>
                                                             <template v-else-if="!isSets && compId(match, 2)">
                                                                 <input type="number" min="0" placeholder="-"
                                                                        class="score-input"
                                                                        v-model.number="match.score2" />
                                                            </template>
                                                            <span v-else class="text-white/15 text-xs">—</span>
                                                        </div>
                                                        <span v-if="isWinner(match, 2)" class="winner-indicator">✓</span>
                                                    </div>

                                                    <!-- Action button -->
                                                    <div v-if="match.status === 'finished'"
                                                         class="bracket-action">
                                                        <button @click="editMatch(match)"
                                                                class="bracket-btn-edit">
                                                            {{ t('bracketEdit') }}
                                                        </button>
                                                    </div>
                                                    <div v-else-if="compId(match, 1) && compId(match, 2) && !isSets && match.score1 >= 0 && match.score2 >= 0"
                                                         class="bracket-action">
                                                        <button @click="saveScore(match)"
                                                                class="bracket-btn-save">
                                                            {{ t('bracketSave') }}
                                                        </button>
                                                    </div>
                                                    <div v-else-if="!compId(match, 1) || !compId(match, 2)"
                                                         class="bracket-action">
                                                        <span class="text-[9px] text-white/10 font-condensed italic tracking-wider">{{ t('bracketWaitingRival') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Connector arrow between phases -->
                                        <div v-if="pIdx < bracketPhases.length - 1"
                                             class="flex flex-col items-center justify-center shrink-0 px-2">
                                            <div class="flex flex-col items-center gap-1">
                                                <svg class="w-6 h-6 text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                </svg>
                                                <span class="text-[9px] text-white/[0.07] font-condensed uppercase tracking-[0.15em]">{{ t('bracketAdvance') }}</span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Third place match -->
                                    <div v-if="thirdPlacePhase"
                                         class="flex flex-col shrink-0 ml-6" style="width: 240px;">
                                        <div class="text-center mb-4">
                                            <span class="text-sm font-condensed font-bold uppercase tracking-[0.1em]"
                                                  :style="{ color: color }">
                                                {{ thirdPlacePhase.label }}
                                            </span>
                                        </div>
                                        <div class="flex flex-col flex-1 justify-end pb-8">
                                            <div v-for="match in thirdPlacePhase.matches" :key="match.id"
                                                 class="bracket-match"
                                                 :class="{
                                                     'finished': match.status === 'finished',
                                                     'pending': match.status === 'pending' && compId(match, 1) && compId(match, 2),
                                                     'empty': match.status === 'pending' && (!compId(match, 1) || !compId(match, 2))
                                                 }"
                                                 :style="match.status === 'finished' ? { borderColor: `${color}55` } : {}">
                                                <div class="bracket-player">
                                                    <div class="flex-1 truncate text-xs font-medium"
                                                         :class="isWinner(match, 1) ? 'text-white font-bold' : match.status === 'finished' ? 'text-white/35' : 'text-white/60'">
                                                        {{ getPlayerName(match, 1) }}
                                                    </div>
                                                    <div class="min-w-[3rem] text-center">
                                                        <template v-if="match.status === 'finished'">
                                                            <span class="text-sm font-bold" :class="isWinner(match, 1) ? 'text-ucl-gold text-base' : 'text-white/30'">{{ match.score1 }}</span>
                                                        </template>
                                                        <template v-else-if="!isSets && compId(match, 1)">
                                                            <input type="number" min="0" placeholder="-"
                                                                   class="score-input"
                                                                   v-model.number="match.score1" />
                                                        </template>
                                                        <span v-else class="text-white/15 text-xs">—</span>
                                                    </div>
                                                    <span v-if="isWinner(match, 1)" class="winner-indicator">✓</span>
                                                </div>
                                                <div class="bracket-vs">
                                                    <span class="text-[10px] font-condensed text-white/[0.07] font-bold tracking-[0.15em]">VS</span>
                                                    <span v-if="match.status === 'finished' && match.score1 === match.score2" class="text-[9px] text-amber-400/50 font-condensed">{{ t('bracketTie') }}</span>
                                                    <span v-if="match.status === 'pending' && compId(match, 1) && compId(match, 2)" class="text-[9px] text-white/15 font-condensed">{{ t('bracketPending') }}</span>
                                                </div>
                                                <div class="bracket-player">
                                                    <div class="flex-1 truncate text-xs font-medium"
                                                         :class="isWinner(match, 2) ? 'text-white font-bold' : match.status === 'finished' ? 'text-white/35' : 'text-white/60'">
                                                        {{ getPlayerName(match, 2) }}
                                                    </div>
                                                    <div class="min-w-[3rem] text-center">
                                                        <template v-if="match.status === 'finished'">
                                                            <span class="text-sm font-bold" :class="isWinner(match, 2) ? 'text-ucl-gold text-base' : 'text-white/30'">{{ match.score2 }}</span>
                                                        </template>
                                                        <template v-else-if="!isSets && compId(match, 2)">
                                                            <input type="number" min="0" placeholder="-"
                                                                   class="score-input"
                                                                   v-model.number="match.score2" />
                                                        </template>
                                                        <span v-else class="text-white/15 text-xs">—</span>
                                                    </div>
                                                    <span v-if="isWinner(match, 2)" class="winner-indicator">✓</span>
                                                </div>
                                                <div v-if="match.status === 'finished'" class="bracket-action">
                                                    <button @click="editMatch(match)" class="bracket-btn-edit">{{ t('bracketEdit') }}</button>
                                                </div>
                                                <div v-else-if="compId(match, 1) && compId(match, 2) && !isSets && match.score1 >= 0 && match.score2 >= 0" class="bracket-action">
                                                    <button @click="saveScore(match)" class="bracket-btn-save">{{ t('bracketSave') }}</button>
                                                </div>
                                                <div v-else-if="!compId(match, 1) || !compId(match, 2)" class="bracket-action">
                                                    <span class="text-[9px] text-white/10 font-condensed italic tracking-wider">{{ t('bracketWaitingRival') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Final highlight -->
                        <div v-if="finalPhase && finalPhase.matches.length && finalPhase.matches[0].player1_id && finalPhase.matches[0].player2_id"
                             class="ucl-card overflow-hidden text-center py-6"
                             :style="{ borderColor: `${color}33`, background: `linear-gradient(135deg, ${color}08, transparent)` }">
                            <div class="text-3xl mb-2">🏆</div>
                            <h4 class="font-condensed font-bold text-sm uppercase tracking-[0.1em] text-white/50 mb-3">{{ t('bracketGrandFinal') }}</h4>
                            <div v-for="m in finalPhase.matches" :key="m.id"
                                 class="inline-flex items-center gap-4 sm:gap-6 text-lg sm:text-xl font-condensed font-bold">
                                <span :class="isWinner(m, 1) ? 'text-white' : m.status === 'finished' ? 'text-white/30' : 'text-white/60'">
                                    {{ getPlayerName(m, 1) }}
                                </span>
                                <span class="text-white/20 text-sm">VS</span>
                                <span :class="isWinner(m, 2) ? 'text-white' : m.status === 'finished' ? 'text-white/30' : 'text-white/60'">
                                    {{ getPlayerName(m, 2) }}
                                </span>
                            </div>
                        </div>

                    </div>

                    <!-- No bracket phases -->
                    <div v-else class="ucl-card p-8 text-center">
                        <p class="text-white/30 text-sm">{{ t('bracketNoPhases') }}</p>
                    </div>
                </div>

                <!-- ====== ESTADÍSTICAS ====== -->
                <div v-if="activeTab === 'stats'" class="animate-fade-up">
                    <div v-if="standings && standings.length" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Puntos -->
                        <div class="ucl-card p-5 sm:p-6">
                            <h3 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">{{ t('statsPoints') }}</h3>
                            <div class="space-y-3">
                                <div v-for="(r, i) in chartPts" :key="'pts' + i">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-white/70 truncate pr-2">{{ r.name }}</span>
                                        <span class="text-xs font-condensed font-bold shrink-0" :style="{ color }">{{ r.value }}</span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-white/5 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500" :style="{ width: r.pct + '%', background: color }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Goles/Puntos/Sets a favor -->
                        <div class="ucl-card p-5 sm:p-6">
                            <h3 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">{{ statLabels.forLabel }}</h3>
                            <div class="space-y-3">
                                <div v-for="(r, i) in chartGf" :key="'gf' + i">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-white/70 truncate pr-2">{{ r.name }}</span>
                                        <span class="text-xs font-condensed font-bold text-emerald-400 shrink-0">{{ r.value }}</span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-white/5 overflow-hidden">
                                        <div class="h-full rounded-full bg-emerald-400/80 transition-all duration-500" :style="{ width: r.pct + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Diferencia -->
                        <div class="ucl-card p-5 sm:p-6">
                            <h3 class="font-condensed font-bold text-lg tracking-wider text-white mb-4">{{ statLabels.diffLabel }}</h3>
                            <div class="space-y-3">
                                <div v-for="(r, i) in chartDg" :key="'dg' + i">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-white/70 truncate pr-2">{{ r.name }}</span>
                                        <span class="text-xs font-condensed font-bold shrink-0"
                                              :class="r.value > 0 ? 'text-emerald-400' : r.value < 0 ? 'text-red-400' : 'text-white/40'">
                                            {{ r.value > 0 ? '+' : '' }}{{ r.value }}
                                        </span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-white/5 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500"
                                             :class="r.value >= 0 ? 'bg-emerald-400/80' : 'bg-red-400/80'"
                                             :style="{ width: r.pct + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="ucl-card p-8 text-center">
                        <svg class="w-10 h-10 mx-auto mb-3 text-white/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <h3 class="text-sm font-bold text-white/50 mb-1 font-condensed tracking-wider uppercase">{{ t('statsNoData') }}</h3>
                        <p class="text-xs text-white/20 font-condensed">{{ t('statsNoDataText') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Replace player modal -->
        <Teleport to="body">
            <div v-if="replaceModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
                 @click.self="replaceModalOpen = false">
                <div class="bg-[#1b2130] border border-[#343d54] rounded-2xl p-6 w-full max-w-sm mx-4 shadow-2xl">
                    <h3 class="text-base font-bold text-[#f4f2ef] mb-4 font-condensed tracking-wider uppercase">{{ t('modalTitle') }}</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-[10px] text-[#7a8299] font-condensed tracking-wider block mb-1">{{ t('modalPlayerToReplace') }}</label>
                            <select v-model="replacePlayerId"
                                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-[#ff8a3d]">
                                <option value="" disabled>{{ t('modalSelectPlayer') }}</option>
                                <option v-for="p in tournament.players" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] text-[#7a8299] font-condensed tracking-wider block mb-1">{{ t('modalNewPlayer') }}</label>
                            <input type="text" v-model="replaceNewName" :placeholder="t('modalNewPlaceholder')"
                                   class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-[#ff8a3d]" />
                        </div>
                    </div>
                    <div class="flex gap-2 mt-5 justify-end">
                        <button @click="replaceModalOpen = false"
                                class="px-4 py-2 text-xs text-white/50 hover:text-white/80 font-condensed tracking-wider uppercase">{{ t('modalCancel') }}</button>
                        <button @click="submitReplace"
                                :disabled="!replacePlayerId || !replaceNewName.trim()"
                                class="px-4 py-2 text-xs font-bold font-condensed tracking-wider uppercase rounded-lg"
                                :class="replacePlayerId && replaceNewName.trim() ? 'bg-[#ff8a3d] text-black hover:bg-[#ffa05e]' : 'bg-white/5 text-white/20 cursor-not-allowed'">
                            {{ t('modalReplace') }}
                        </button>
                    </div>
                    <p class="mt-3 text-[10px] text-[#7a8299] font-condensed leading-relaxed">
                        {{ t('modalNote') }}
                    </p>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.status-pill {
    font-size: 10px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    font-family: 'Bebas Neue', 'Oswald', sans-serif;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.status-in-progress {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
}
.status-completed {
    background: rgba(52, 211, 153, 0.15);
    color: #34d399;
    border: 1px solid rgba(52, 211, 153, 0.3);
}
.ucl-match.editing-form {
    border-color: rgba(255,255,255,0.15);
}
</style>
