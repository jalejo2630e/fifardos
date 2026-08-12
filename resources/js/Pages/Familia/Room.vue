<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { getEcho, leaveChannel } from '@/echo';
import { getToken, getName, setName } from '@/familia/session';
import { useToast } from '@/composables/useToast';

const toast = useToast();

const props = defineProps({ code: String, room: Object, config: Object });

const GAMES = {
    pictionary: { name: 'Dibuja y Adivina', icon: '🎨', desc: 'Un participante dibuja y los otros adivinan.' },
    trivia: { name: 'Trivia', icon: '❓', desc: 'Respondan la pregunta lo más rápido posible.' },
    tuttifrutti: { name: 'Tutti Frutti', icon: '🔤', desc: 'Completá las categorías con la letra que salga.' },
    hangman: { name: 'Ahorcado', icon: '🔠', desc: 'Adiviná la palabra letra por letra.' },
    memoria: { name: 'Memoria', icon: '🧠', desc: 'Encontrá los pares de cartas iguales.' },
};
const HANG_KEYS = 'A B C D E F G H I J K L M N Ñ O P Q R S T U V W X Y Z'.split(' ');

const token = getToken();
const room = ref(props.room);
const me = ref(null);
const joinNeeded = ref(false);
const joinName = ref(getName());
const joinError = ref('');
const chat = ref([]);
const now = ref(Date.now());
const copied = ref(false);
const confirmClose = ref(false);   // modal de confirmación para cerrar la sala (host)
let leaving = false;               // evita redirigir dos veces
// Tanda (playlist) de juegos elegida por el anfitrión, en orden
const pl = ref(props.room.playlist && props.room.playlist.length ? [...props.room.playlist] : [props.room.game]);

// Pictionary
const myWord = ref('');
const guessText = ref('');
const colors = ['#111111', '#ff5f00', '#b6ff2e', '#2b7fff', '#ff3b6b', '#ffd23f', '#12b886', '#ffffff'];
const currentColor = ref('#111111');
const currentSize = ref(5);
const canvasRef = ref(null);
const stageRef = ref(null);

// Trivia
const myAnswer = ref(null);

// Tutti Frutti
const tf = reactive({});
let tfTimer = null;
const myRejects = ref([]);   // mis rechazos locales (feedback instantáneo, sin esperar el broadcast)

// --- Derivados ---
const game = computed(() => room.value?.game || 'pictionary');
const gs = computed(() => room.value?.game_state || {});
const phase = computed(() => gs.value.phase);
const reveal = computed(() => gs.value.reveal || null);
const isMyTurn = computed(() => gs.value.turn != null && gs.value.turn === myId.value);
const turnName = computed(() => members.value.find((m) => m.id === gs.value.turn)?.name ?? '');
const kick = computed(() => room.value?.kick || null);
const kickedMe = computed(() => !!kick.value && kick.value.member_id === myId.value);
const kickRemaining = computed(() => (kick.value ? Math.max(0, Math.ceil((new Date(kick.value.at).getTime() - now.value) / 1000)) : 0));
const status = computed(() => room.value?.status);
const members = computed(() => room.value?.members ?? []);
const myId = computed(() => me.value?.id ?? null);
const isHost = computed(() => !!me.value?.is_host);
const isDrawer = computed(() => game.value === 'pictionary' && gs.value.drawer_member_id === myId.value);
const drawerName = computed(() => members.value.find((m) => m.id === gs.value.drawer_member_id)?.name ?? '');
const canStart = computed(() => members.value.length >= (props.config?.min_families ?? 2));
const remaining = computed(() => {
    if (!room.value?.round_ends_at) return 0;
    return Math.max(0, Math.round((new Date(room.value.round_ends_at).getTime() - now.value) / 1000));
});
const iAnswered = computed(() => (gs.value.answered || []).includes(myId.value) || myAnswer.value !== null);
const winner = computed(() => {
    if (status.value !== 'ended' || !members.value.length) return null;
    const max = Math.max(...members.value.map((m) => m.score));
    const top = members.value.filter((m) => m.score === max);
    return { tie: top.length > 1, names: top.map((m) => m.name), score: max };
});

// ===================== Canvas (Pictionary) =====================
let ctx = null, cssW = 0, cssH = 0, dpr = 1;
let segments = [], activeLocal = null, activeRemote = null, drawing = false;
let batch = [], batchBegin = false, flushTimer = null;

function setupCanvas() {
    const cv = canvasRef.value, stage = stageRef.value;
    if (!cv || !stage) return;
    const r = stage.getBoundingClientRect();
    cssW = r.width; cssH = r.height;
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    cv.width = Math.round(cssW * dpr); cv.height = Math.round(cssH * dpr);
    ctx = cv.getContext('2d');
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.lineCap = 'round'; ctx.lineJoin = 'round';
    redrawAll();
}
function line(a, b, color, size) { if (!ctx) return; ctx.strokeStyle = color; ctx.lineWidth = size; ctx.beginPath(); ctx.moveTo(a.x * cssW, a.y * cssH); ctx.lineTo(b.x * cssW, b.y * cssH); ctx.stroke(); }
function dot(p, color, size) { if (!ctx) return; ctx.fillStyle = color; ctx.beginPath(); ctx.arc(p.x * cssW, p.y * cssH, size / 2, 0, Math.PI * 2); ctx.fill(); }
function redrawAll() { if (!ctx) return; ctx.clearRect(0, 0, cssW, cssH); for (const s of segments) { if (s.points.length === 1) dot(s.points[0], s.color, s.size); for (let i = 1; i < s.points.length; i++) line(s.points[i - 1], s.points[i], s.color, s.size); } }
function clearCanvasLocal() { segments = []; activeLocal = null; activeRemote = null; if (ctx) ctx.clearRect(0, 0, cssW, cssH); }
function applyRemote(e) {
    if (e.begin || !activeRemote) { activeRemote = { color: e.color, size: e.size, points: [] }; segments.push(activeRemote); }
    for (const p of e.points) { if (activeRemote.points.length) line(activeRemote.points[activeRemote.points.length - 1], p, activeRemote.color, activeRemote.size); else dot(p, activeRemote.color, activeRemote.size); activeRemote.points.push(p); }
}
function pos(ev) { const r = canvasRef.value.getBoundingClientRect(); const cx = ev.touches ? ev.touches[0].clientX : ev.clientX; const cy = ev.touches ? ev.touches[0].clientY : ev.clientY; return { x: (cx - r.left) / r.width, y: (cy - r.top) / r.height }; }
function onDown(ev) { if (!isDrawer.value || phase.value !== 'play') return; ev.preventDefault(); drawing = true; const p = pos(ev); activeLocal = { color: currentColor.value, size: currentSize.value, points: [p] }; segments.push(activeLocal); dot(p, currentColor.value, currentSize.value); batch = [p]; batchBegin = true; }
function onMove(ev) { if (!drawing) return; ev.preventDefault(); const p = pos(ev); const prev = activeLocal.points[activeLocal.points.length - 1]; line(prev, p, activeLocal.color, activeLocal.size); activeLocal.points.push(p); batch.push(p); }
function onUp() { if (!drawing) return; drawing = false; flush(); activeLocal = null; }
function flush() { if (!batch.length) return; const points = batch; const begin = batchBegin; batch = []; batchBegin = false; axios.post(`/minijuegos/${props.code}/stroke`, { token, points, color: currentColor.value, size: currentSize.value, begin }).catch(() => {}); }
function clearBoard() { clearCanvasLocal(); axios.post(`/minijuegos/${props.code}/clear`, { token }).catch(() => {}); }

// ===================== Acciones por juego =====================
function sendGuess() { const t = guessText.value.trim(); if (!t) return; guessText.value = ''; axios.post(`/minijuegos/${props.code}/guess`, { token, text: t }).catch(() => {}); }
function sendAnswer(i) { if (iAnswered.value || phase.value !== 'play') return; myAnswer.value = i; axios.post(`/minijuegos/${props.code}/answer`, { token, index: i }).catch(() => { myAnswer.value = null; }); }
function submitTf() { axios.post(`/minijuegos/${props.code}/submit`, { token, answers: { ...tf } }).catch(() => {}); }
function onTfInput() { if (tfTimer) clearTimeout(tfTimer); tfTimer = setTimeout(submitTf, 600); }
async function callBasta() { if (tfTimer) clearTimeout(tfTimer); await submitTf(); axios.post(`/minijuegos/${props.code}/stop`, { token }).catch(() => {}); }

// --- Validación (anti-trampa) ---
function tfNorm(s) { return (s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLowerCase(); }
function startsWithLetter(v) { const L = tfNorm(gs.value.letter); const s = tfNorm(v); return !!s && !!L && s.startsWith(L); }
function myRejected(owner, cat) { return myRejects.value.includes(owner + ':' + cat); }
function rejCount(owner, cat) { const v = gs.value.votes || {}; const key = owner + ':' + cat; let n = 0; for (const k in v) { if ((v[k] || []).includes(key)) n++; } return n; }
function isRejectedFinal(owner, cat) { return rejCount(owner, cat) * 2 > Math.max(1, members.value.length - 1); }
function toggleVote(owner, cat) {
    const key = owner + ':' + cat;
    const willReject = !myRejects.value.includes(key);
    myRejects.value = willReject ? [...myRejects.value, key] : myRejects.value.filter((k) => k !== key);
    axios.post(`/minijuegos/${props.code}/vote`, { token, owner, cat, accept: !willReject }).catch(() => {});
}

// --- Ahorcado ---
const solveText = ref('');
const pendingKeys = reactive(new Set());   // teclas recién tocadas (feedback instantáneo)
function letterUsed(k) { const lc = k.toLowerCase(); return (gs.value.guessed || []).includes(lc) || pendingKeys.has(lc); }
function letterClass(k) {
    if (!letterUsed(k)) return '';
    const lc = k.toLowerCase();
    return (gs.value.masked || []).some((c) => (c || '').toLowerCase() === lc) ? 'hit' : 'miss';
}
function guessLetter(k) {
    if (!isMyTurn.value) return;
    const lc = k.toLowerCase();
    if (letterUsed(k)) return;
    pendingKeys.add(lc);
    axios.post(`/minijuegos/${props.code}/letter`, { token, letter: lc }).catch(() => {});
}
function solveWord() {
    if (!isMyTurn.value) return;
    const t = solveText.value.trim();
    if (!t) return;
    solveText.value = '';
    axios.post(`/minijuegos/${props.code}/solve`, { token, text: t }).catch(() => {});
}

// --- Memoria ---
function flipCard(id) {
    if (!isMyTurn.value) return;
    axios.post(`/minijuegos/${props.code}/flip`, { token, card: id }).catch(() => {});
}

// --- Host: expulsar / cerrar ---
function goHome(msg) {
    if (leaving) return;
    leaving = true;
    if (msg) toast.info(msg);
    router.visit('/');
}
function onLeaveClick() {
    if (isHost.value) { confirmClose.value = true; } else { goHome(); }
}
function closeRoom() {
    confirmClose.value = false;
    axios.post(`/minijuegos/${props.code}/close`, { token }).catch(() => {});
    goHome();
}
function kickMember(id) {
    if (!isHost.value) return;
    axios.post(`/minijuegos/${props.code}/kick`, { token, member: id }).catch(() => {});
}
function acceptKick() {
    axios.post(`/minijuegos/${props.code}/kick-finalize`, { token }).catch(() => {});
    goHome('Saliste de la sala.');
}

// ===================== Red / estado =====================
let channel = null, heartbeat = null, ticker = null, lastTimeoutAt = 0, lastResolveAt = 0, lastKickAt = 0, ro = null;

async function identify() {
    try { const { data } = await axios.post(`/minijuegos/${props.code}/hello`, { token }); room.value = data.room; me.value = data.me; joinNeeded.value = !data.me; } catch (e) { /* noop */ }
}
async function joinHere() {
    joinError.value = '';
    const n = joinName.value.trim();
    if (!n) { joinError.value = 'Escribí tu nombre.'; return; }
    try { setName(n); await axios.post(`/minijuegos/${props.code}/join`, { name: n, token }); await identify(); }
    catch (e) { joinError.value = e.response?.data?.message || 'No se pudo unir.'; }
}
function pushChat(e) { chat.value.push(e); if (chat.value.length > 120) chat.value.shift(); nextTick(() => { const b = document.getElementById('fam-chat'); if (b) b.scrollTop = b.scrollHeight; }); }
async function startGame() {
    if (!pl.value.length) return;
    try { await axios.post(`/minijuegos/${props.code}/start`, { token, games: pl.value }); }
    catch (e) { alert(e.response?.data?.message || 'No se pudo empezar.'); }
}
function playlistIndex(key) { return pl.value.indexOf(key); }
function togglePlaylist(key) {
    if (!isHost.value) return;
    const idx = pl.value.indexOf(key);
    pl.value = idx >= 0 ? pl.value.filter((g) => g !== key) : [...pl.value, key];
    axios.post(`/minijuegos/${props.code}/playlist`, { token, games: pl.value }).catch(() => {});
}
function copyCode() { navigator.clipboard?.writeText(props.code).then(() => { copied.value = true; setTimeout(() => (copied.value = false), 1500); }); }
async function fetchWord() {
    if (game.value === 'pictionary' && phase.value === 'play' && isDrawer.value) {
        try { const { data } = await axios.get(`/minijuegos/${props.code}/word`, { params: { token } }); myWord.value = data.word; } catch { myWord.value = ''; }
    } else { myWord.value = ''; }
}

// Reset por ronda nueva
watch(() => room.value?.round, () => {
    clearCanvasLocal();
    myAnswer.value = null;
    Object.keys(tf).forEach((k) => delete tf[k]);
    myRejects.value = [];
    pendingKeys.clear();
    solveText.value = '';
    fetchWord();
});
// Al entrar a validación, sembramos mis rechazos desde el server (para reconexión).
watch(phase, (p) => {
    if (p === 'validate') myRejects.value = [...((gs.value.votes || {})[myId.value] || [])];
});
// La tanda mostrada sigue lo que hay en el server (para todos los participantes).
watch(() => room.value?.playlist, (v) => { pl.value = (v && v.length) ? [...v] : [room.value?.game].filter(Boolean); });
// El anfitrión cerró la sala → todos afuera.
watch(() => room.value?.status, (s) => { if (s === 'closed') goHome('El anfitrión cerró la sala.'); });
// Me sacaron (ya no figuro entre los participantes) → afuera.
watch(() => members.value.map((m) => m.id).join(','), () => {
    if (me.value && room.value?.status !== 'closed' && !members.value.some((m) => m.id === myId.value)) {
        goHome('Te sacaron de la sala.');
    }
});
watch(() => [isDrawer.value, phase.value, game.value], fetchWord);

// El canvas solo existe en el DOM cuando se está jugando a Pictionary. Cuando
// aparece (lobby → jugando) hay que (re)inicializar el contexto y el observer,
// porque el setupCanvas de onMounted corre cuando el canvas todavía no existe.
const showCanvas = computed(() => game.value === 'pictionary' && status.value === 'playing');
watch(showCanvas, async (v) => {
    if (v) {
        await nextTick();
        setupCanvas();
        if (ro && stageRef.value) ro.observe(stageRef.value);
    } else if (ro) {
        ro.disconnect();
    }
});

onMounted(async () => {
    await identify();
    channel = getEcho().channel(`family-room.${props.code}`);
    channel.listen('.RoomUpdated', (e) => { room.value = e.room; });
    channel.listen('.ChatPosted', (e) => pushChat(e));
    channel.listen('.DrawStroke', (e) => { if (e.from !== token) applyRemote(e); });
    channel.listen('.CanvasCleared', (e) => { if (e.from !== token) clearCanvasLocal(); });

    await nextTick(); setupCanvas();
    ro = new ResizeObserver(setupCanvas);
    if (stageRef.value) ro.observe(stageRef.value);
    ticker = setInterval(() => {
        now.value = Date.now();
        // Avance automático: cuando vence el deadline (jugar/validar/revelar) cualquier
        // cliente avisa al server. Reintenta cada ~1.5s hasta que avanza (no se traba).
        if (status.value === 'playing' && room.value?.round_ends_at && remaining.value <= 0
            && (now.value - lastTimeoutAt > 1500)) {
            lastTimeoutAt = now.value;
            axios.post(`/minijuegos/${props.code}/timeout`, { token }).catch(() => {});
        }
        // Memoria: volver a tapar las cartas que no coincidieron (cualquier cliente lo dispara).
        const ra = gs.value.resolve_at;
        if (status.value === 'playing' && ra && now.value >= new Date(ra).getTime() && (now.value - lastResolveAt > 1200)) {
            lastResolveAt = now.value;
            axios.post(`/minijuegos/${props.code}/resolve`, { token }).catch(() => {});
        }
        // Expulsión: el anfitrión la concreta cuando vencen los 5s (si el objetivo no salió antes).
        if (isHost.value && kick.value && now.value >= new Date(kick.value.at).getTime() && (now.value - lastKickAt > 1500)) {
            lastKickAt = now.value;
            axios.post(`/minijuegos/${props.code}/kick-finalize`, { token }).catch(() => {});
        }
    }, 250);
    flushTimer = setInterval(() => { if (batch.length) flush(); }, 80);
    heartbeat = setInterval(identify, 15000);
    fetchWord();
});
onBeforeUnmount(() => {
    if (ro) ro.disconnect();
    clearInterval(ticker); clearInterval(flushTimer); clearInterval(heartbeat); if (tfTimer) clearTimeout(tfTimer);
    leaveChannel(`family-room.${props.code}`);
    try { navigator.sendBeacon?.(`/minijuegos/${props.code}/leave`, new Blob([JSON.stringify({ token })], { type: 'application/json' })); } catch (e) { /* noop */ }
});
</script>

<template>
    <div class="fam">
        <Head :title="`Sala ${code} — Minijuegos`" />

        <!-- Unirse -->
        <div v-if="joinNeeded" class="fam-overlay">
            <div class="fam-join-card">
                <span class="tag">Sala {{ code }}</span>
                <h2>Sumate a esta sala</h2>
                <input v-model="joinName" maxlength="24" placeholder="Tu nombre" @keyup.enter="joinHere" />
                <button class="btn btn-solid" @click="joinHere">Entrar a jugar</button>
                <p v-if="joinError" class="err">{{ joinError }}</p>
                <Link href="/minijuegos" class="muted-link">← Volver</Link>
            </div>
        </div>

        <template v-else>
            <header class="rm-hdr">
                <Link href="/" class="rm-logo"><img src="/brand/logo-horizontal-dark.png" alt="FIFARDOS" /></Link>
                <div class="rm-code" @click="copyCode"><span class="rm-code-lbl">Código</span><span class="rm-code-val">{{ code }}</span><span class="rm-copy">{{ copied ? '¡copiado!' : 'copiar' }}</span></div>
                <div v-if="status === 'playing'" class="rm-round">
                    <span v-if="(room.playlist || []).length > 1" class="rm-seq">Juego {{ (room.playlist_pos || 0) + 1 }}/{{ room.playlist.length }}</span>
                    <span>{{ GAMES[game].icon }} {{ room.round }}/{{ room.total_rounds }}</span>
                    <span class="rm-timer" :class="{ low: remaining <= 10 }">{{ remaining }}s</span>
                </div>
                <button class="rm-leave" @click="onLeaveClick">Salir</button>
            </header>

            <main class="rm-main">
                <section class="rm-stage-wrap">
                    <!-- ============ LOBBY ============ -->
                    <div v-if="status === 'lobby'" class="rm-panel">
                        <h2>Sala lista</h2>
                        <p>Compartí el código <b class="code-chip" @click="copyCode">{{ code }}</b> con los otros participantes.</p>
                        <div class="rm-games">
                            <button v-for="(g, key) in GAMES" :key="key" class="rm-game" :class="{ on: playlistIndex(key) >= 0, lock: !isHost }"
                                    :disabled="!isHost" @click="togglePlaylist(key)">
                                <span v-if="playlistIndex(key) >= 0" class="rm-order">{{ playlistIndex(key) + 1 }}</span>
                                <span class="rm-game-ic">{{ g.icon }}</span>
                                <span class="rm-game-nm">{{ g.name }}</span>
                                <span class="rm-game-ds">{{ g.desc }}</span>
                            </button>
                        </div>
                        <p class="rm-hint" v-if="isHost">Tocá los juegos en el orden que quieras (1, 2, 3). Podés elegir uno solo.</p>
                        <p class="rm-count">{{ members.length }} / {{ config.max_families }} participantes</p>
                        <button v-if="isHost" class="btn btn-solid" :disabled="!canStart || !pl.length" @click="startGame">
                            {{ !canStart ? 'Esperando participantes…' : (pl.length > 1 ? `Empezar tanda (${pl.length}) →` : 'Empezar a jugar →') }}
                        </button>
                        <p v-else class="muted">Arma la tanda el anfitrión. Esperando que empiece…</p>
                    </div>

                    <!-- ============ FIN ============ -->
                    <div v-else-if="status === 'ended'" class="rm-panel">
                        <span class="rm-again-lbl">{{ gs.result?.label || 'Resultado' }}</span>
                        <h2>🏆 {{ gs.result?.winner || (winner ? winner.names[0] : '') }}</h2>
                        <ol class="rm-podium" v-if="gs.result?.podium?.length">
                            <li v-for="(p, i) in gs.result.podium" :key="i"><span>{{ i + 1 }}. {{ p.name }}</span><b>{{ p.pts }}</b></li>
                        </ol>
                        <template v-if="isHost">
                            <p class="rm-again-lbl">Armá la próxima tanda:</p>
                            <div class="rm-games">
                                <button v-for="(g, key) in GAMES" :key="key" class="rm-game" :class="{ on: playlistIndex(key) >= 0 }" @click="togglePlaylist(key)">
                                    <span v-if="playlistIndex(key) >= 0" class="rm-order">{{ playlistIndex(key) + 1 }}</span>
                                    <span class="rm-game-ic">{{ g.icon }}</span>
                                    <span class="rm-game-nm">{{ g.name }}</span>
                                </button>
                            </div>
                            <button class="btn btn-solid" :disabled="!pl.length" @click="startGame">{{ pl.length > 1 ? `Jugar tanda (${pl.length}) →` : 'Jugar de nuevo →' }}</button>
                        </template>
                        <p v-else class="muted">El anfitrión arma la próxima tanda…</p>
                    </div>

                    <!-- ============ INTERMEDIO ENTRE JUEGOS (tanda) ============ -->
                    <div v-else-if="phase === 'gameover'" class="rm-panel">
                        <span class="rm-again-lbl">Fin de {{ gs.result?.label }}</span>
                        <h2>🏆 {{ gs.result?.winner }}</h2>
                        <ol class="rm-podium" v-if="gs.result?.podium?.length">
                            <li v-for="(p, i) in gs.result.podium" :key="i"><span>{{ i + 1 }}. {{ p.name }}</span><b>{{ p.pts }}</b></li>
                        </ol>
                        <p class="muted">Sigue <b>{{ gs.result?.next }}</b> en {{ remaining }}s…</p>
                    </div>

                    <!-- ============ PICTIONARY ============ -->
                    <template v-else-if="game === 'pictionary'">
                        <div class="rm-turn" v-if="phase === 'play'">
                            <template v-if="isDrawer">Te toca dibujar: <b class="word">{{ myWord || '…' }}</b></template>
                            <template v-else>Dibuja <b>{{ drawerName }}</b> · adiviná: <span class="hint">{{ '_ '.repeat(gs.word_length || 0) }}</span></template>
                        </div>
                        <div ref="stageRef" class="rm-stage">
                            <canvas ref="canvasRef" class="rm-canvas" :class="{ drawer: isDrawer && phase === 'play' }"
                                    @pointerdown="onDown" @pointermove="onMove" @pointerup="onUp" @pointerleave="onUp"></canvas>
                            <div v-if="phase === 'reveal'" class="rm-reveal">
                                <span class="rv-title">{{ reveal?.winner ? '🎉 ¡' + reveal.winner + ' adivinó!' : '⏱ ¡Nadie adivinó!' }}</span>
                                <span class="rv-word">La palabra era <b>{{ reveal?.word }}</b></span>
                                <span class="rv-count">Siguiente ronda en <b>{{ remaining }}</b>…</span>
                            </div>
                        </div>
                        <div v-if="phase === 'play' && isDrawer" class="rm-tools">
                            <button v-for="c in colors" :key="c" class="swatch" :class="{ on: currentColor === c }" :style="{ background: c }" @click="currentColor = c"></button>
                            <span class="sep"></span>
                            <button v-for="s in [3, 6, 12]" :key="s" class="sizer" :class="{ on: currentSize === s }" @click="currentSize = s"><span :style="{ width: s + 'px', height: s + 'px' }"></span></button>
                            <button class="rm-clear" @click="clearBoard">Borrar</button>
                        </div>
                    </template>

                    <!-- ============ TRIVIA ============ -->
                    <template v-else-if="game === 'trivia'">
                        <div class="rm-quiz">
                            <h2 class="rm-q">{{ phase === 'reveal' ? reveal?.question : gs.question }}</h2>
                            <div class="rm-opts">
                                <button v-for="(opt, i) in (phase === 'reveal' ? reveal?.options : gs.options)" :key="i" class="rm-opt"
                                        :class="{ chosen: myAnswer === i, correct: phase === 'reveal' && reveal?.answer === i, wrong: phase === 'reveal' && myAnswer === i && reveal?.answer !== i, dim: phase === 'play' && iAnswered }"
                                        :disabled="phase !== 'play' || iAnswered" @click="sendAnswer(i)">
                                    <span class="rm-opt-k">{{ ['A', 'B', 'C', 'D'][i] }}</span>{{ opt }}
                                </button>
                            </div>
                            <p v-if="phase === 'play'" class="rm-quiz-note">{{ iAnswered ? 'Respuesta enviada — esperando a las demás…' : 'Elegí una opción' }} · {{ (gs.answered || []).length }}/{{ members.length }}</p>
                            <div v-else class="rm-quiz-note">
                                <span class="rv-count">Siguiente en <b>{{ remaining }}</b>s</span>
                                <span v-for="b in reveal?.breakdown" :key="b.member_id" class="rm-bd" :class="{ ok: b.correct }">{{ b.name }} {{ b.correct ? '+' + b.pts : '✗' }}</span>
                            </div>
                        </div>
                    </template>

                    <!-- ============ TUTTI FRUTTI ============ -->
                    <template v-else-if="game === 'tuttifrutti'">
                        <!-- Jugar -->
                        <div v-if="phase === 'play'" class="rm-tf">
                            <div class="rm-tf-head">Letra <b class="rm-letter">{{ gs.letter }}</b>
                                <button class="btn btn-basta" @click="callBasta">¡Basta!</button>
                            </div>
                            <div class="rm-tf-grid">
                                <label v-for="(cat, i) in gs.categories" :key="i" class="rm-tf-cell">
                                    <span>{{ cat }}</span>
                                    <input v-model="tf[i]" maxlength="40" :placeholder="gs.letter + '…'"
                                           :class="{ bad: tf[i] && !startsWithLetter(tf[i]) }" @input="onTfInput" @blur="submitTf" />
                                </label>
                            </div>
                        </div>

                        <!-- Validar (anti-trampa): cada familia destilda lo que crea trampa -->
                        <div v-else-if="phase === 'validate'" class="rm-tf-validate">
                            <div class="rm-tf-head">
                                <span>Validen · letra <b class="rm-letter">{{ gs.letter }}</b></span>
                                <span class="rv-count">Puntúa en <b>{{ remaining }}</b>s</span>
                            </div>
                            <p class="rm-tf-note">Destildá (✗) las respuestas que creas trampa. Las que no empiezan con «{{ gs.letter }}» ya quedan descartadas.</p>
                            <div class="rm-tf-table" :style="{ '--cols': (gs.entries || []).length }">
                                <div class="rm-tf-row head"><span>Categoría</span><span v-for="e in gs.entries" :key="e.owner_id">{{ e.name }}</span></div>
                                <div v-for="(cat, ci) in gs.categories" :key="ci" class="rm-tf-row">
                                    <span class="cat">{{ cat }}</span>
                                    <span v-for="e in gs.entries" :key="e.owner_id" class="vcell"
                                          :class="{ empty: !e.answers[ci].value, bad: e.answers[ci].value && !e.answers[ci].letter_ok, struck: isRejectedFinal(e.owner_id, ci) }">
                                        <template v-if="e.answers[ci].value">
                                            <span class="vval">{{ e.answers[ci].value }}</span>
                                            <button v-if="e.owner_id !== myId && e.answers[ci].letter_ok" class="vchk"
                                                    :class="{ off: myRejected(e.owner_id, ci) }" @click="toggleVote(e.owner_id, ci)">
                                                {{ myRejected(e.owner_id, ci) ? '✗' : '✓' }}
                                            </button>
                                            <span v-else-if="!e.answers[ci].letter_ok" class="vbad" title="No empieza con la letra">✗</span>
                                        </template>
                                        <template v-else>—</template>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Resultado -->
                        <div v-else class="rm-tf-result">
                            <h2>Letra {{ reveal?.letter }} <span class="rv-count">· siguiente en <b>{{ remaining }}</b>s</span></h2>
                            <div class="rm-tf-table" :style="{ '--cols': (reveal?.rows || []).length }">
                                <div class="rm-tf-row head"><span>Categoría</span><span v-for="r in reveal?.rows" :key="r.member_id">{{ r.name }}</span></div>
                                <div v-for="(cat, ci) in reveal?.categories" :key="ci" class="rm-tf-row">
                                    <span class="cat">{{ cat }}</span>
                                    <span v-for="r in reveal?.rows" :key="r.member_id" class="cell"
                                          :class="{ z: r.answers[ci].pts === 0, u: r.answers[ci].pts === 10, struck: r.answers[ci].rejected || (r.answers[ci].value && !r.answers[ci].letter_ok) }">
                                        {{ r.answers[ci].value || '—' }} <b v-if="r.answers[ci].pts">+{{ r.answers[ci].pts }}</b>
                                    </span>
                                </div>
                                <div class="rm-tf-row total"><span>Total</span><span v-for="r in reveal?.rows" :key="r.member_id">{{ r.total }}</span></div>
                            </div>
                        </div>
                    </template>

                    <!-- ============ AHORCADO ============ -->
                    <template v-else-if="game === 'hangman'">
                        <div v-if="phase === 'play'" class="rm-hang">
                            <div class="rm-hang-top">
                                <svg class="rm-gallows" viewBox="0 0 120 145">
                                    <g class="base">
                                        <line x1="6" y1="140" x2="70" y2="140" />
                                        <line x1="26" y1="140" x2="26" y2="8" />
                                        <line x1="26" y1="8" x2="82" y2="8" />
                                        <line x1="82" y1="8" x2="82" y2="22" />
                                    </g>
                                    <circle v-if="gs.misses >= 1" class="part" cx="82" cy="33" r="11" />
                                    <line v-if="gs.misses >= 2" class="part" x1="82" y1="44" x2="82" y2="84" />
                                    <line v-if="gs.misses >= 3" class="part" x1="82" y1="54" x2="66" y2="70" />
                                    <line v-if="gs.misses >= 4" class="part" x1="82" y1="54" x2="98" y2="70" />
                                    <line v-if="gs.misses >= 5" class="part" x1="82" y1="84" x2="70" y2="106" />
                                    <line v-if="gs.misses >= 6" class="part" x1="82" y1="84" x2="94" y2="106" />
                                </svg>
                                <div class="rm-hang-info">
                                    <p class="rm-turn-lbl" :class="{ mine: isMyTurn }">{{ isMyTurn ? '¡Es tu turno!' : ('Turno de ' + turnName) }}</p>
                                    <span v-if="gs.hint" class="rm-hint-lbl">Pista: <b>{{ gs.hint }}</b></span>
                                    <span class="rm-hang-misses">Errores: <b :class="{ danger: gs.misses >= gs.max_misses - 1 }">{{ gs.misses }}/{{ gs.max_misses }}</b></span>
                                </div>
                            </div>
                            <div class="rm-word">
                                <span v-for="(c, i) in gs.masked" :key="i" class="rm-slot" :class="{ gap: c === ' ', on: c && c !== ' ' }">{{ c === ' ' ? '' : (c || '_') }}</span>
                            </div>
                            <div class="rm-keys">
                                <button v-for="k in HANG_KEYS" :key="k" class="rm-key" :class="letterClass(k)" :disabled="letterUsed(k) || !isMyTurn" @click="guessLetter(k)">{{ k }}</button>
                            </div>
                            <form v-if="isMyTurn" class="rm-solve" @submit.prevent="solveWord">
                                <input v-model="solveText" maxlength="40" placeholder="¿Sabés la palabra? Escribila…" />
                                <button class="btn btn-solid" type="submit">Resolver</button>
                            </form>
                            <p v-else class="rm-turn-wait">Esperá tu turno… juega <b>{{ turnName }}</b></p>
                        </div>
                        <div v-else class="rm-panel">
                            <h2>{{ reveal?.solved ? '¡Adivinada!' : 'Se acabó' }}</h2>
                            <p>La palabra era <b class="word">{{ reveal?.word }}</b></p>
                            <p class="rv-count">Siguiente en <b>{{ remaining }}</b>s</p>
                        </div>
                    </template>

                    <!-- ============ MEMORIA ============ -->
                    <template v-else-if="game === 'memoria'">
                        <div v-if="phase === 'play'" class="rm-memo">
                            <div class="rm-hang-top">
                                <div class="rm-hang-info">
                                    <p class="rm-turn-lbl" :class="{ mine: isMyTurn }">{{ isMyTurn ? '¡Es tu turno!' : ('Turno de ' + turnName) }}</p>
                                    <span class="rm-hang-misses">Pares: <b>{{ gs.pairs_found }}/{{ gs.pairs_total }}</b></span>
                                </div>
                            </div>
                            <div class="rm-cards">
                                <button v-for="c in gs.cards" :key="c.id" class="rm-card" :class="{ up: c.up, found: c.found }"
                                        :disabled="!isMyTurn || c.up || !!gs.resolve_at" @click="flipCard(c.id)">
                                    <span v-if="c.up" class="rm-card-face">{{ c.value }}</span>
                                    <span v-else class="rm-card-back">?</span>
                                </button>
                            </div>
                            <p v-if="!isMyTurn" class="rm-turn-wait">Esperá tu turno… juega <b>{{ turnName }}</b></p>
                        </div>
                        <div v-else class="rm-panel">
                            <h2>¡Tablero completo! 🧠</h2>
                            <p class="rv-count">Siguiente en <b>{{ remaining }}</b>s</p>
                        </div>
                    </template>
                </section>

                <!-- Sidebar -->
                <aside class="rm-side">
                    <div class="rm-scores">
                        <h3>Participantes</h3>
                        <div v-for="m in members" :key="m.id" class="rm-fam" :class="{ drawing: m.id === gs.drawer_member_id, off: !m.online }">
                            <span class="rm-fam-slot">{{ m.slot }}</span>
                            <span class="rm-fam-name">{{ m.name }}<b v-if="m.is_host" class="rm-host">host</b><b v-if="m.id === gs.drawer_member_id" class="rm-draw">✏️</b></span>
                            <span class="rm-fam-score">{{ m.score }}<small v-if="(room.playlist || []).length > 1 && status === 'playing'" class="rm-fam-gs">+{{ m.game_score }}</small></span>
                            <button v-if="isHost && !m.is_host" class="rm-kick" title="Sacar de la sala" @click="kickMember(m.id)">✕</button>
                        </div>
                    </div>
                    <div class="rm-chatbox">
                        <h3>{{ game === 'pictionary' ? 'Adivinanzas' : 'Novedades' }}</h3>
                        <div id="fam-chat" class="rm-chat">
                            <div v-for="(c, i) in chat" :key="i" class="msg" :class="c.kind">
                                <template v-if="c.kind === 'system'">{{ c.text }}</template>
                                <template v-else-if="c.kind === 'correct'">✅ {{ c.text }}</template>
                                <template v-else><b>{{ c.name }}:</b> {{ c.text }}</template>
                            </div>
                        </div>
                        <form v-if="game === 'pictionary' && status === 'playing' && phase === 'play' && !isDrawer" class="rm-guess" @submit.prevent="sendGuess">
                            <input v-model="guessText" maxlength="60" placeholder="Escribí tu respuesta…" />
                            <button class="btn btn-solid" type="submit">Enviar</button>
                        </form>
                    </div>
                </aside>
            </main>

            <!-- Te van a sacar (cuenta regresiva de 5s) -->
            <div v-if="kickedMe" class="fam-overlay">
                <div class="fam-join-card">
                    <span class="tag">Atención</span>
                    <h2>El anfitrión te va a sacar</h2>
                    <p class="rm-kick-count">Salís en <b>{{ kickRemaining }}</b>s…</p>
                    <button class="btn btn-solid" @click="acceptKick">Salir ahora</button>
                </div>
            </div>

            <!-- Confirmar cerrar la sala (host) -->
            <div v-if="confirmClose" class="fam-overlay" @click.self="confirmClose = false">
                <div class="fam-join-card">
                    <span class="tag">Cerrar sala</span>
                    <h2>¿Salir y cerrar la sala?</h2>
                    <p class="rm-close-note">Se cerrará para <b>todos</b> los participantes.</p>
                    <button class="btn btn-solid" @click="closeRoom">Sí, cerrar la sala</button>
                    <button class="btn-cancel" @click="confirmClose = false">Cancelar</button>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.fam {
    --accent: #ff5f00; --accent-hover: #ff7a26; --lime: #b6ff2e;
    --bg: #08080a; --card: #0e0e11; --card2: #131317; --hair: rgba(255,255,255,.1);
    --tp: #f2f2f0; --ts: #a8a8a3; --tm: #8f8f8b;
    --f-anton: 'Anton', Impact, sans-serif; --f-barlow: 'Barlow Condensed', sans-serif; --f-body: 'Chakra Petch', system-ui, sans-serif;
    min-height: 100vh; background: var(--bg); color: var(--tp); font-family: var(--f-body);
}
.fam * { box-sizing: border-box; }
.fam-overlay { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 20px; background: radial-gradient(circle at 50% 0%, #14100c, #08080a 70%); }
.fam-join-card { width: 100%; max-width: 400px; background: var(--card); border: 1px solid var(--hair); padding: 28px; display: flex; flex-direction: column; gap: 12px; text-align: center; }
.fam-join-card .tag { font-family: var(--f-anton); letter-spacing: .2em; text-transform: uppercase; color: var(--accent); font-size: 13px; }
.fam-join-card h2 { font-family: var(--f-barlow); font-weight: 800; text-transform: uppercase; margin: 0; font-size: 26px; }
.err { color: #ff7a6b; font-size: 14px; margin: 0; }
.muted-link { color: var(--tm); text-decoration: none; font-size: 13px; }
.muted-link:hover { color: var(--accent); }
input { width: 100%; background: var(--card2); border: 1px solid var(--hair); color: var(--tp); font-family: var(--f-body); font-size: 16px; padding: 12px 14px; outline: none; }
input:focus { border-color: var(--accent); }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; border: none; font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; letter-spacing: .08em; font-size: 16px; padding: 12px 20px; transition: background-color .18s, color .18s; }
.btn:disabled { opacity: .55; cursor: not-allowed; }
.btn-solid { background: var(--accent); color: #08080a; clip-path: polygon(11px 0, 100% 0, 100% calc(100% - 11px), calc(100% - 11px) 100%, 0 100%, 0 11px); }
.btn-solid:hover:not(:disabled) { background: var(--accent-hover); }
.btn-basta { background: var(--lime); color: #08080a; }

.rm-hdr { display: flex; align-items: center; gap: 18px; padding: 12px 20px; border-bottom: 1px solid var(--hair); background: rgba(8,8,10,.9); }
.rm-logo img { height: 34px; display: block; }
.rm-code { margin-left: auto; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; border: 1px solid var(--hair); padding: 6px 12px; }
.rm-code-lbl { font-size: 10px; letter-spacing: .16em; text-transform: uppercase; color: var(--tm); }
.rm-code-val { font-family: var(--f-anton); letter-spacing: .18em; font-size: 20px; color: var(--lime); }
.rm-copy { font-size: 10px; text-transform: uppercase; color: var(--tm); }
.rm-round { display: inline-flex; align-items: center; gap: 12px; font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; font-size: 15px; color: var(--ts); }
.rm-timer { font-family: var(--f-anton); font-size: 22px; color: var(--tp); min-width: 44px; text-align: center; }
.rm-timer.low { color: #ff5f5f; }
.rm-leave { color: var(--tm); text-decoration: none; font-size: 13px; text-transform: uppercase; letter-spacing: .1em; }
.rm-leave:hover { color: var(--accent); }

.rm-main { max-width: 1200px; margin: 0 auto; padding: 18px 20px 40px; display: grid; grid-template-columns: 1fr 320px; gap: 18px; align-items: start; }

/* Paneles genéricos (lobby / fin) */
.rm-panel { background: var(--card); border: 1px solid var(--hair); padding: 30px 24px; display: flex; flex-direction: column; align-items: center; gap: 14px; text-align: center; min-height: 300px; justify-content: center; }
.rm-panel h2 { font-family: var(--f-anton); text-transform: uppercase; font-size: clamp(26px, 5vw, 40px); margin: 0; }
.rm-panel p { color: var(--ts); margin: 0; }
.code-chip, .rm-count { font-family: var(--f-anton); color: var(--lime); letter-spacing: .16em; }
.code-chip { cursor: pointer; }
.rm-count { color: var(--accent); font-size: 20px; }
.muted { color: var(--tm); }
.rm-again-lbl { color: var(--tm); font-size: 13px; letter-spacing: .04em; text-transform: uppercase; margin: 6px 0 -4px; }
.rm-games { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; width: 100%; max-width: 620px; margin: 6px 0; }
.rm-game { position: relative; background: var(--card2); border: 1px solid var(--hair); padding: 16px 12px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 6px; transition: border-color .15s; }
.rm-game.on { border-color: var(--accent); background: rgba(255,95,0,.1); }
.rm-order { position: absolute; top: 6px; right: 6px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; background: var(--accent); color: #08080a; font-family: var(--f-anton); font-size: 13px; border-radius: 50%; }
.rm-hint { color: var(--tm); font-size: 12.5px; margin: 2px 0 0; }
.rm-seq { color: var(--accent); font-weight: 700; }
.rm-game.lock { cursor: default; }
.rm-game-ic { font-size: 30px; }
.rm-game-nm { font-family: var(--f-barlow); font-weight: 800; text-transform: uppercase; font-size: 16px; }
.rm-game-ds { font-size: 12px; color: var(--tm); }

/* Pictionary */
.rm-turn { text-align: center; padding: 10px; font-size: 16px; color: var(--ts); background: var(--card); border: 1px solid var(--hair); border-bottom: none; }
.rm-turn .word { font-family: var(--f-barlow); font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--lime); font-size: 20px; }
.rm-turn .hint { font-family: var(--f-anton); letter-spacing: .3em; color: var(--tp); }
.rm-stage { position: relative; width: 100%; aspect-ratio: 4 / 3; background: #fff; border: 1px solid var(--hair); overflow: hidden; }
.rm-canvas { position: absolute; inset: 0; width: 100%; height: 100%; touch-action: none; cursor: default; }
.rm-canvas.drawer { cursor: crosshair; }
.rm-reveal { position: absolute; inset: 0; z-index: 5; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; text-align: center; padding: 20px;
    background: rgba(8,8,10,.86); color: var(--tp); animation: rvin .25s ease; }
.rv-title { font-family: var(--f-anton); text-transform: uppercase; font-size: clamp(24px, 5vw, 42px); color: var(--lime); }
.rv-word { font-family: var(--f-barlow); font-size: 20px; text-transform: uppercase; letter-spacing: .04em; color: var(--ts); }
.rv-word b { color: var(--tp); }
.rv-count { font-size: 14px; color: var(--tm); letter-spacing: .04em; }
.rv-count b { font-family: var(--f-anton); color: var(--accent); font-size: 18px; }
@keyframes rvin { from { opacity: 0; transform: scale(1.05); } to { opacity: 1; transform: none; } }
.rm-tools { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; padding: 10px; background: var(--card); border: 1px solid var(--hair); border-top: none; }
.swatch { width: 26px; height: 26px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; }
.swatch.on { border-color: var(--tp); box-shadow: 0 0 0 2px var(--bg) inset; }
.sep { width: 1px; height: 24px; background: var(--hair); margin: 0 4px; }
.sizer { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; background: var(--card2); border: 1px solid var(--hair); cursor: pointer; }
.sizer.on { border-color: var(--accent); }
.sizer span { display: block; background: var(--tp); border-radius: 50%; }
.rm-clear { margin-left: auto; background: transparent; border: 1px solid var(--hair); color: var(--ts); font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; padding: 7px 14px; cursor: pointer; }
.rm-clear:hover { border-color: #ff5f5f; color: #ff5f5f; }

/* Trivia */
.rm-quiz { background: var(--card); border: 1px solid var(--hair); padding: 26px 22px; min-height: 320px; display: flex; flex-direction: column; gap: 18px; }
.rm-q { font-family: var(--f-barlow); font-weight: 800; text-transform: uppercase; font-size: clamp(20px, 3.5vw, 30px); margin: 0; line-height: 1.1; }
.rm-opts { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.rm-opt { display: flex; align-items: center; gap: 10px; text-align: left; background: var(--card2); border: 1px solid var(--hair); color: var(--tp); font-family: var(--f-body); font-size: 17px; padding: 16px; cursor: pointer; transition: border-color .15s, background .15s; }
.rm-opt:hover:not(:disabled) { border-color: var(--accent); }
.rm-opt-k { font-family: var(--f-anton); color: var(--accent); width: 22px; }
.rm-opt.chosen { border-color: var(--accent); }
.rm-opt.correct { border-color: var(--lime); background: rgba(182,255,46,.12); }
.rm-opt.correct .rm-opt-k { color: var(--lime); }
.rm-opt.wrong { border-color: #ff5f5f; background: rgba(255,95,95,.1); }
.rm-opt.dim { opacity: .6; }
.rm-quiz-note { color: var(--tm); font-size: 14px; margin: 0; display: flex; gap: 10px; flex-wrap: wrap; }
.rm-bd { padding: 3px 10px; border: 1px solid var(--hair); border-radius: 999px; }
.rm-bd.ok { color: var(--lime); border-color: rgba(182,255,46,.4); }

/* Tutti Frutti */
.rm-tf { background: var(--card); border: 1px solid var(--hair); padding: 20px; }
.rm-tf-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; }
.rm-letter { font-family: var(--f-anton); font-size: 40px; color: var(--accent); }
.rm-tf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.rm-tf-cell { display: flex; flex-direction: column; gap: 4px; }
.rm-tf-cell span { font-size: 12px; letter-spacing: .1em; text-transform: uppercase; color: var(--tm); }
.rm-tf-result { background: var(--card); border: 1px solid var(--hair); padding: 20px; }
.rm-tf-result h2 { font-family: var(--f-anton); text-transform: uppercase; margin: 0 0 12px; }
.rm-tf-table { display: flex; flex-direction: column; font-size: 14px; overflow-x: auto; }
.rm-tf-row { display: grid; grid-template-columns: 150px repeat(var(--cols, 3), minmax(96px, 1fr)); gap: 6px; padding: 6px 4px; border-top: 1px solid rgba(255,255,255,.06); align-items: center; min-width: max(100%, calc(150px + var(--cols, 3) * 102px)); }
.rm-tf-row.head { color: var(--tm); text-transform: uppercase; font-size: 11px; letter-spacing: .08em; border-top: none; }
.rm-tf-row.total { font-family: var(--f-anton); color: var(--lime); border-top: 1px solid var(--hair); }
.rm-tf-row .cat { color: var(--ts); }
.rm-tf-row .cell b { color: var(--lime); }
.rm-tf-row .cell.z { color: var(--tdd, #6d6d69); }
.rm-tf-row .cell.u b { color: var(--accent); }
.rm-tf-row .cell.struck { text-decoration: line-through; opacity: .5; }

/* Input con letra incorrecta */
.rm-tf-cell input.bad { border-color: #ff5f5f; color: #ff8a8a; }

/* Fase de validación */
.rm-tf-validate { background: var(--card); border: 1px solid var(--hair); padding: 20px; }
.rm-tf-note { color: var(--tm); font-size: 13px; margin: 4px 0 14px; }
.vcell { display: inline-flex; align-items: center; justify-content: space-between; gap: 6px; min-width: 0; }
.vval { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.vcell.empty { color: var(--tdd, #6d6d69); }
.vcell.bad .vval { color: #ff7a6b; text-decoration: line-through; }
.vcell.struck .vval { text-decoration: line-through; opacity: .55; }
.vchk { flex-shrink: 0; width: 22px; height: 22px; border: 1px solid rgba(182,255,46,.4); background: rgba(182,255,46,.14); color: var(--lime); cursor: pointer; font-size: 12px; line-height: 1; }
.vchk.off { background: rgba(255,95,95,.14); color: #ff7a6b; border-color: rgba(255,95,95,.4); }
.vbad { flex-shrink: 0; color: #ff7a6b; font-size: 12px; }

/* Sidebar */
.rm-side { display: flex; flex-direction: column; gap: 16px; }
.rm-scores, .rm-chatbox { background: var(--card); border: 1px solid var(--hair); padding: 14px; }
.rm-side h3 { font-family: var(--f-barlow); font-weight: 800; text-transform: uppercase; letter-spacing: .06em; font-size: 15px; margin: 0 0 10px; color: var(--tm); }
.rm-fam { display: flex; align-items: center; gap: 10px; padding: 8px; border-top: 1px solid rgba(255,255,255,.06); }
.rm-fam:first-of-type { border-top: none; }
.rm-fam.drawing { background: rgba(255,95,0,.08); }
.rm-fam.off { opacity: .45; }
.rm-fam-slot { width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; background: var(--card2); font-family: var(--f-anton); font-size: 12px; color: var(--ts); }
.rm-fam-name { flex: 1; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
.rm-host { font-family: var(--f-barlow); font-weight: 700; font-size: 10px; text-transform: uppercase; color: var(--accent); }
.rm-fam-score { font-family: var(--f-anton); font-size: 20px; color: var(--lime); }
.rm-chat { height: 240px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; padding-right: 4px; }
.msg { font-size: 14px; line-height: 1.35; }
.msg.system { color: var(--tm); font-style: italic; text-align: center; }
.msg.correct { color: var(--lime); font-weight: 700; }
.msg.guess b { color: var(--ts); }
.rm-guess { display: flex; gap: 8px; margin-top: 10px; }
.rm-guess .btn { padding: 10px 16px; font-size: 14px; }

/* Podio (fin de partida / total) */
.rm-podium { list-style: none; margin: 6px 0; padding: 0; width: 100%; max-width: 340px; display: flex; flex-direction: column; gap: 4px; }
.rm-podium li { display: flex; justify-content: space-between; padding: 8px 12px; background: var(--card2); border: 1px solid var(--hair); }
.rm-podium li:first-child { border-color: var(--accent); background: rgba(255,95,0,.1); }
.rm-podium b { font-family: var(--f-anton); color: var(--lime); }
.rm-fam-gs { font-family: var(--f-body); font-size: 11px; color: var(--accent); margin-left: 4px; }

/* Ahorcado */
.rm-hang { background: var(--card); border: 1px solid var(--hair); padding: 20px; display: flex; flex-direction: column; gap: 18px; align-items: center; }
.rm-hang-top { width: 100%; display: flex; justify-content: center; align-items: center; gap: 22px; }
.rm-gallows { width: 110px; height: 132px; }
.rm-gallows .base line { stroke: var(--ts); stroke-width: 4; stroke-linecap: round; fill: none; }
.rm-gallows .part { stroke: #ff7a6b; stroke-width: 4; stroke-linecap: round; fill: none; }
.rm-hang-info { display: flex; flex-direction: column; gap: 6px; font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; color: var(--ts); }
.rm-turn-lbl { margin: 0; font-family: var(--f-anton); font-size: 22px; color: var(--tp); }
.rm-turn-lbl.mine { color: var(--lime); }
.rm-hint-lbl { font-size: 14px; }
.rm-hint-lbl b { color: var(--accent); font-family: var(--f-barlow); font-weight: 800; }
.rm-hang-misses b { font-family: var(--f-anton); color: var(--tp); }
.rm-hang-misses b.danger { color: #ff5f5f; }
.rm-turn-wait { color: var(--tm); font-size: 14px; text-align: center; margin: 0; }

/* Memoria */
.rm-memo { background: var(--card); border: 1px solid var(--hair); padding: 20px; display: flex; flex-direction: column; gap: 16px; align-items: center; }
.rm-cards { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; width: 100%; max-width: 560px; }
.rm-card { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; font-size: clamp(20px, 5vw, 30px); background: var(--card2); border: 1px solid var(--hair); cursor: pointer; transition: border-color .15s, background .15s, transform .1s; }
.rm-card:hover:not(:disabled) { border-color: var(--accent); }
.rm-card:active:not(:disabled) { transform: scale(.96); }
.rm-card:disabled { cursor: default; }
.rm-card.up { background: var(--bg); border-color: rgba(255,255,255,.2); }
.rm-card.found { background: rgba(182,255,46,.14); border-color: rgba(182,255,46,.5); }
.rm-card-back { font-family: var(--f-anton); font-size: 22px; color: var(--tm); }

/* Host: expulsar / cerrar */
.rm-kick { margin-left: 6px; width: 22px; height: 22px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; background: transparent; border: 1px solid var(--hair); color: var(--tm); font-size: 11px; cursor: pointer; }
.rm-kick:hover { border-color: #ff5f5f; color: #ff5f5f; }
.rm-kick-count { font-family: var(--f-anton); font-size: 22px; color: var(--tp); margin: 0; }
.rm-kick-count b { color: #ff5f5f; }
.rm-close-note { color: var(--tm); font-size: 14px; margin: 0; }
.rm-close-note b { color: #ff7a6b; }
.btn-cancel { background: transparent; border: 1px solid var(--hair); color: var(--ts); font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; letter-spacing: .06em; font-size: 14px; padding: 10px; cursor: pointer; }
.btn-cancel:hover { color: var(--tp); border-color: var(--tp); }
.rm-word { display: flex; flex-wrap: wrap; gap: 10px 12px; justify-content: center; font-family: var(--f-anton); font-size: clamp(30px, 7vw, 44px); }
.rm-slot { min-width: 28px; text-align: center; text-transform: uppercase; color: var(--ts); line-height: 1.1; }
.rm-slot.on { color: var(--lime); }
.rm-slot.gap { min-width: 18px; }
.rm-keys { display: grid; grid-template-columns: repeat(9, 1fr); gap: 6px; width: 100%; max-width: 520px; }
.rm-key { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; background: var(--card2); border: 1px solid var(--hair); color: var(--tp); font-family: var(--f-barlow); font-weight: 700; font-size: 16px; cursor: pointer; text-transform: uppercase; transition: all .12s; }
.rm-key:hover:not(:disabled) { border-color: var(--accent); }
.rm-key:disabled { cursor: default; }
.rm-key.hit { background: rgba(182,255,46,.18); border-color: rgba(182,255,46,.5); color: var(--lime); }
.rm-key.miss { background: rgba(255,95,95,.14); border-color: rgba(255,95,95,.4); color: #ff7a6b; opacity: .7; }
.rm-solve { display: flex; gap: 8px; width: 100%; max-width: 520px; }
.rm-solve input { flex: 1; }
.rm-solve .btn { padding: 12px 18px; font-size: 15px; }

@media (max-width: 900px) {
    .rm-main { grid-template-columns: 1fr; }
    .rm-opts { grid-template-columns: 1fr; }
    .rm-games { grid-template-columns: 1fr; }
    .rm-chat { height: 160px; }
    .rm-keys { grid-template-columns: repeat(7, 1fr); }
    .rm-word { font-size: 28px; gap: 8px 10px; }
    .rm-slot { min-width: 22px; }
}
</style>
