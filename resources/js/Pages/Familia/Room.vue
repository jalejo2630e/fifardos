<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { getEcho, leaveChannel } from '@/echo';
import { getToken, getName, setName } from '@/familia/session';

const props = defineProps({
    code: String,
    room: Object,
    config: Object,
});

const token = getToken();
const room = ref(props.room);
const me = ref(null);
const joinNeeded = ref(false);
const joinName = ref(getName());
const joinError = ref('');
const myWord = ref('');
const chat = ref([]);
const guessText = ref('');
const now = ref(Date.now());
const copied = ref(false);

// Herramientas de dibujo
const colors = ['#111111', '#ff5f00', '#b6ff2e', '#2b7fff', '#ff3b6b', '#ffd23f', '#12b886', '#ffffff'];
const currentColor = ref('#111111');
const currentSize = ref(5);

const canvasRef = ref(null);
const stageRef = ref(null);

// --- Derivados ---
const status = computed(() => room.value?.status);
const members = computed(() => room.value?.members ?? []);
const myId = computed(() => me.value?.id ?? null);
const isHost = computed(() => !!me.value?.is_host);
const isDrawer = computed(() => !!room.value?.drawer_member_id && room.value.drawer_member_id === myId.value);
const drawerName = computed(() => members.value.find((m) => m.id === room.value?.drawer_member_id)?.name ?? '');
const canStart = computed(() => members.value.length >= (props.config?.min_families ?? 2));
const remaining = computed(() => {
    if (!room.value?.round_ends_at) return 0;
    return Math.max(0, Math.round((new Date(room.value.round_ends_at).getTime() - now.value) / 1000));
});
const wordHint = computed(() => {
    const len = room.value?.word_length;
    if (!len) return '';
    return Array.from({ length: len }).map(() => '_').join(' ');
});
const winner = computed(() => {
    if (status.value !== 'ended' || !members.value.length) return null;
    const max = Math.max(...members.value.map((m) => m.score));
    const top = members.value.filter((m) => m.score === max);
    return { tie: top.length > 1, names: top.map((m) => m.name), score: max };
});

// --- Canvas ---
let ctx = null, cssW = 0, cssH = 0, dpr = 1;
let segments = [];
let activeLocal = null, activeRemote = null;
let drawing = false;
let batch = [], batchBegin = false, flushTimer = null;

function setupCanvas() {
    const cv = canvasRef.value, stage = stageRef.value;
    if (!cv || !stage) return;
    const r = stage.getBoundingClientRect();
    cssW = r.width; cssH = r.height;
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    cv.width = Math.round(cssW * dpr);
    cv.height = Math.round(cssH * dpr);
    ctx = cv.getContext('2d');
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    redrawAll();
}
function line(a, b, color, size) {
    if (!ctx) return;
    ctx.strokeStyle = color; ctx.lineWidth = size;
    ctx.beginPath(); ctx.moveTo(a.x * cssW, a.y * cssH); ctx.lineTo(b.x * cssW, b.y * cssH); ctx.stroke();
}
function dot(p, color, size) {
    if (!ctx) return;
    ctx.fillStyle = color; ctx.beginPath();
    ctx.arc(p.x * cssW, p.y * cssH, size / 2, 0, Math.PI * 2); ctx.fill();
}
function redrawAll() {
    if (!ctx) return;
    ctx.clearRect(0, 0, cssW, cssH);
    for (const s of segments) {
        if (s.points.length === 1) dot(s.points[0], s.color, s.size);
        for (let i = 1; i < s.points.length; i++) line(s.points[i - 1], s.points[i], s.color, s.size);
    }
}
function clearCanvasLocal() {
    segments = []; activeLocal = null; activeRemote = null;
    if (ctx) ctx.clearRect(0, 0, cssW, cssH);
}
function applyRemote(e) {
    if (e.begin || !activeRemote) { activeRemote = { color: e.color, size: e.size, points: [] }; segments.push(activeRemote); }
    for (const p of e.points) {
        if (activeRemote.points.length) line(activeRemote.points[activeRemote.points.length - 1], p, activeRemote.color, activeRemote.size);
        else dot(p, activeRemote.color, activeRemote.size);
        activeRemote.points.push(p);
    }
}

function pos(ev) {
    const r = canvasRef.value.getBoundingClientRect();
    const cx = ev.touches ? ev.touches[0].clientX : ev.clientX;
    const cy = ev.touches ? ev.touches[0].clientY : ev.clientY;
    return { x: (cx - r.left) / r.width, y: (cy - r.top) / r.height };
}
function onDown(ev) {
    if (!isDrawer.value || status.value !== 'playing') return;
    ev.preventDefault();
    drawing = true;
    const p = pos(ev);
    activeLocal = { color: currentColor.value, size: currentSize.value, points: [p] };
    segments.push(activeLocal);
    dot(p, currentColor.value, currentSize.value);
    batch = [p]; batchBegin = true;
}
function onMove(ev) {
    if (!drawing) return;
    ev.preventDefault();
    const p = pos(ev);
    const prev = activeLocal.points[activeLocal.points.length - 1];
    line(prev, p, activeLocal.color, activeLocal.size);
    activeLocal.points.push(p);
    batch.push(p);
}
function onUp() {
    if (!drawing) return;
    drawing = false;
    flush();
    activeLocal = null;
}
function flush() {
    if (!batch.length) return;
    const points = batch; const begin = batchBegin;
    batch = []; batchBegin = false;
    axios.post(`/familia/${props.code}/stroke`, {
        token, points, color: currentColor.value, size: currentSize.value, begin,
    }).catch(() => {});
}
function clearBoard() {
    clearCanvasLocal();
    axios.post(`/familia/${props.code}/clear`, { token }).catch(() => {});
}

// --- Red / estado ---
let channel = null, heartbeat = null, ticker = null, timeoutSent = false, ro = null;

async function identify() {
    try {
        const { data } = await axios.post(`/familia/${props.code}/hello`, { token });
        room.value = data.room;
        me.value = data.me;
        joinNeeded.value = !data.me;
    } catch (e) { /* noop */ }
}

async function joinHere() {
    joinError.value = '';
    const n = joinName.value.trim();
    if (!n) { joinError.value = 'Escribí el nombre de tu familia.'; return; }
    try {
        setName(n);
        await axios.post(`/familia/${props.code}/join`, { name: n, token });
        await identify();
    } catch (e) {
        joinError.value = e.response?.data?.message || 'No se pudo unir.';
    }
}

function pushChat(e) {
    chat.value.push(e);
    if (chat.value.length > 120) chat.value.shift();
    nextTick(() => {
        const box = document.getElementById('fam-chat');
        if (box) box.scrollTop = box.scrollHeight;
    });
}

async function startGame() {
    try { await axios.post(`/familia/${props.code}/start`, { token }); }
    catch (e) { alert(e.response?.data?.message || 'No se pudo empezar.'); }
}
function sendGuess() {
    const t = guessText.value.trim();
    if (!t) return;
    guessText.value = '';
    axios.post(`/familia/${props.code}/guess`, { token, text: t }).catch(() => {});
}

async function fetchWord() {
    if (status.value === 'playing' && isDrawer.value && room.value?.has_word) {
        try { const { data } = await axios.get(`/familia/${props.code}/word`, { params: { token } }); myWord.value = data.word; }
        catch { myWord.value = ''; }
    } else {
        myWord.value = '';
    }
}

function copyCode() {
    navigator.clipboard?.writeText(props.code).then(() => {
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    });
}

// Reaccionar a cambios de ronda: limpiar canvas, buscar palabra, resetear timeout
watch(() => room.value?.round, () => {
    clearCanvasLocal();
    timeoutSent = false;
    fetchWord();
});
watch(() => [isDrawer.value, room.value?.has_word, status.value], fetchWord);

// El anfitrión avisa al server cuando se acaba el tiempo
watch(remaining, (r) => {
    if (status.value === 'playing' && r <= 0 && isHost.value && !timeoutSent && room.value?.round_ends_at) {
        timeoutSent = true;
        axios.post(`/familia/${props.code}/timeout`, { token }).catch(() => {});
    }
});

onMounted(async () => {
    await identify();

    channel = getEcho().channel(`family-room.${props.code}`);
    channel.listen('.RoomUpdated', (e) => { room.value = e.room; });
    channel.listen('.ChatPosted', (e) => pushChat(e));
    channel.listen('.DrawStroke', (e) => { if (e.from !== token) applyRemote(e); });
    channel.listen('.CanvasCleared', (e) => { if (e.from !== token) clearCanvasLocal(); });

    await nextTick();
    setupCanvas();
    ro = new ResizeObserver(setupCanvas);
    if (stageRef.value) ro.observe(stageRef.value);

    ticker = setInterval(() => (now.value = Date.now()), 250);
    flushTimer = setInterval(() => { if (batch.length) flush(); }, 80);
    heartbeat = setInterval(identify, 15000);
    fetchWord();
});

onBeforeUnmount(() => {
    if (ro) ro.disconnect();
    clearInterval(ticker); clearInterval(flushTimer); clearInterval(heartbeat);
    leaveChannel(`family-room.${props.code}`);
    try { navigator.sendBeacon?.(`/familia/${props.code}/leave`, new Blob([JSON.stringify({ token })], { type: 'application/json' })); } catch (e) { /* noop */ }
});
</script>

<template>
    <div class="fam">
        <Head :title="`Sala ${code} — Familia`" />

        <!-- Overlay: unirse si aún no soy miembro -->
        <div v-if="joinNeeded" class="fam-overlay">
            <div class="fam-join-card">
                <span class="tag">Sala {{ code }}</span>
                <h2>Sumate a esta sala</h2>
                <input v-model="joinName" maxlength="24" placeholder="Nombre de tu familia" @keyup.enter="joinHere" />
                <button class="btn btn-solid" @click="joinHere">Entrar a jugar</button>
                <p v-if="joinError" class="err">{{ joinError }}</p>
                <Link href="/familia" class="muted-link">← Volver</Link>
            </div>
        </div>

        <template v-else>
            <!-- Header -->
            <header class="rm-hdr">
                <Link href="/" class="rm-logo"><img src="/brand/logo-horizontal-dark.png" alt="FIFARDOS" /></Link>
                <div class="rm-code" @click="copyCode" :title="'Copiar código'">
                    <span class="rm-code-lbl">Código</span>
                    <span class="rm-code-val">{{ code }}</span>
                    <span class="rm-copy">{{ copied ? '¡copiado!' : 'copiar' }}</span>
                </div>
                <div v-if="status === 'playing'" class="rm-round">
                    <span>Ronda {{ room.round }}/{{ room.total_rounds }}</span>
                    <span class="rm-timer" :class="{ low: remaining <= 10 }">{{ remaining }}s</span>
                </div>
                <Link href="/familia" class="rm-leave">Salir</Link>
            </header>

            <main class="rm-main">
                <!-- Escenario / tablero -->
                <section class="rm-stage-wrap">
                    <!-- Aviso de turno -->
                    <div class="rm-turn" v-if="status === 'playing'">
                        <template v-if="isDrawer">
                            Te toca dibujar: <b class="word">{{ myWord || '…' }}</b>
                        </template>
                        <template v-else>
                            Dibuja <b>{{ drawerName }}</b> · adiviná: <span class="hint">{{ wordHint }}</span>
                        </template>
                    </div>

                    <div ref="stageRef" class="rm-stage">
                        <canvas
                            ref="canvasRef" class="rm-canvas"
                            :class="{ drawer: isDrawer && status === 'playing' }"
                            @pointerdown="onDown" @pointermove="onMove" @pointerup="onUp" @pointerleave="onUp"
                        ></canvas>

                        <!-- LOBBY -->
                        <div v-if="status === 'lobby'" class="rm-lobby">
                            <h2>Sala lista 🎨</h2>
                            <p>Compartí el código <b class="code-chip" @click="copyCode">{{ code }}</b> con las otras familias.
                               Cuando estén todas, el anfitrión arranca.</p>
                            <p class="rm-count">{{ members.length }} / {{ config.max_families }} familias</p>
                            <button v-if="isHost" class="btn btn-solid" :disabled="!canStart" @click="startGame">
                                {{ canStart ? 'Empezar a jugar →' : 'Esperando familias…' }}
                            </button>
                            <p v-else class="muted">Esperando que el anfitrión empiece…</p>
                        </div>

                        <!-- FIN -->
                        <div v-if="status === 'ended'" class="rm-end">
                            <h2 v-if="winner">{{ winner.tie ? '¡Empate!' : '🏆 ¡Ganó ' + winner.names[0] + '!' }}</h2>
                            <p v-if="winner">{{ winner.names.join(', ') }} · {{ winner.score }} pts</p>
                            <button v-if="isHost" class="btn btn-solid" @click="startGame">Jugar de nuevo</button>
                            <p v-else class="muted">El anfitrión puede iniciar otra ronda.</p>
                        </div>
                    </div>

                    <!-- Herramientas (solo dibujante) -->
                    <div v-if="status === 'playing' && isDrawer" class="rm-tools">
                        <button v-for="c in colors" :key="c" class="swatch" :class="{ on: currentColor === c }"
                                :style="{ background: c }" @click="currentColor = c"></button>
                        <span class="sep"></span>
                        <button v-for="s in [3, 6, 12]" :key="s" class="sizer" :class="{ on: currentSize === s }" @click="currentSize = s">
                            <span :style="{ width: s + 'px', height: s + 'px' }"></span>
                        </button>
                        <button class="rm-clear" @click="clearBoard">Borrar</button>
                    </div>
                </section>

                <!-- Sidebar: familias + chat -->
                <aside class="rm-side">
                    <div class="rm-scores">
                        <h3>Familias</h3>
                        <div v-for="m in members" :key="m.id" class="rm-fam" :class="{ drawing: m.id === room.drawer_member_id, off: !m.online }">
                            <span class="rm-fam-slot">{{ m.slot }}</span>
                            <span class="rm-fam-name">
                                {{ m.name }}
                                <b v-if="m.is_host" class="rm-host">host</b>
                                <b v-if="m.id === room.drawer_member_id" class="rm-draw">✏️</b>
                            </span>
                            <span class="rm-fam-score">{{ m.score }}</span>
                        </div>
                    </div>

                    <div class="rm-chatbox">
                        <h3>Adivinanzas</h3>
                        <div id="fam-chat" class="rm-chat">
                            <div v-for="(c, i) in chat" :key="i" class="msg" :class="c.kind">
                                <template v-if="c.kind === 'system'">{{ c.text }}</template>
                                <template v-else-if="c.kind === 'correct'">✅ {{ c.text }}</template>
                                <template v-else><b>{{ c.name }}:</b> {{ c.text }}</template>
                            </div>
                        </div>
                        <form v-if="status === 'playing' && !isDrawer" class="rm-guess" @submit.prevent="sendGuess">
                            <input v-model="guessText" maxlength="60" placeholder="Escribí tu respuesta…" />
                            <button class="btn btn-solid" type="submit">Enviar</button>
                        </form>
                        <p v-else-if="status === 'playing' && isDrawer" class="rm-guess-note">Estás dibujando — ¡que adivinen las demás!</p>
                    </div>
                </aside>
            </main>
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

/* Overlay join */
.fam-overlay { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 20px; background: radial-gradient(circle at 50% 0%, #14100c, #08080a 70%); }
.fam-join-card { width: 100%; max-width: 400px; background: var(--card); border: 1px solid var(--hair); padding: 28px; display: flex; flex-direction: column; gap: 12px; text-align: center; }
.fam-join-card .tag { font-family: var(--f-anton); letter-spacing: .2em; text-transform: uppercase; color: var(--accent); font-size: 13px; }
.fam-join-card h2 { font-family: var(--f-barlow); font-weight: 800; text-transform: uppercase; margin: 0; font-size: 26px; }
.fam-join-card .err { color: #ff7a6b; font-size: 14px; margin: 0; }
.muted-link { color: var(--tm); text-decoration: none; font-size: 13px; }
.muted-link:hover { color: var(--accent); }

input { width: 100%; background: var(--card2); border: 1px solid var(--hair); color: var(--tp); font-family: var(--f-body); font-size: 16px; padding: 12px 14px; outline: none; }
input:focus { border-color: var(--accent); }

.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; border: none; font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; letter-spacing: .08em; font-size: 16px; padding: 12px 20px; transition: background-color .18s, color .18s; }
.btn:disabled { opacity: .55; cursor: not-allowed; }
.btn-solid { background: var(--accent); color: #08080a; clip-path: polygon(11px 0, 100% 0, 100% calc(100% - 11px), calc(100% - 11px) 100%, 0 100%, 0 11px); }
.btn-solid:hover:not(:disabled) { background: var(--accent-hover); }

/* Header */
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

/* Main */
.rm-main { max-width: 1200px; margin: 0 auto; padding: 18px 20px 40px; display: grid; grid-template-columns: 1fr 320px; gap: 18px; align-items: start; }

.rm-turn { text-align: center; padding: 10px; font-size: 16px; color: var(--ts); background: var(--card); border: 1px solid var(--hair); border-bottom: none; }
.rm-turn .word { font-family: var(--f-barlow); font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--lime); font-size: 20px; }
.rm-turn .hint { font-family: var(--f-anton); letter-spacing: .3em; color: var(--tp); }

.rm-stage { position: relative; width: 100%; aspect-ratio: 4 / 3; background: #fff; border: 1px solid var(--hair); overflow: hidden; }
.rm-canvas { position: absolute; inset: 0; width: 100%; height: 100%; touch-action: none; cursor: default; }
.rm-canvas.drawer { cursor: crosshair; }

.rm-lobby, .rm-end { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; text-align: center; padding: 24px; background: rgba(8,8,10,.92); color: var(--tp); }
.rm-lobby h2, .rm-end h2 { font-family: var(--f-anton); text-transform: uppercase; font-size: clamp(26px, 5vw, 40px); margin: 0; }
.rm-lobby p, .rm-end p { color: var(--ts); margin: 0; max-width: 420px; }
.code-chip, .rm-count { font-family: var(--f-anton); color: var(--lime); letter-spacing: .16em; }
.code-chip { cursor: pointer; }
.rm-count { color: var(--accent); font-size: 20px; }
.muted { color: var(--tm); }

.rm-tools { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; padding: 10px; background: var(--card); border: 1px solid var(--hair); border-top: none; }
.swatch { width: 26px; height: 26px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; }
.swatch.on { border-color: var(--tp); box-shadow: 0 0 0 2px var(--bg) inset; }
.sep { width: 1px; height: 24px; background: var(--hair); margin: 0 4px; }
.sizer { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; background: var(--card2); border: 1px solid var(--hair); cursor: pointer; }
.sizer.on { border-color: var(--accent); }
.sizer span { display: block; background: var(--tp); border-radius: 50%; }
.rm-clear { margin-left: auto; background: transparent; border: 1px solid var(--hair); color: var(--ts); font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; padding: 7px 14px; cursor: pointer; }
.rm-clear:hover { border-color: #ff5f5f; color: #ff5f5f; }

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

.rm-chat { height: 260px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; padding-right: 4px; }
.msg { font-size: 14px; line-height: 1.35; }
.msg.system { color: var(--tm); font-style: italic; text-align: center; }
.msg.correct { color: var(--lime); font-weight: 700; }
.msg.guess b { color: var(--ts); }
.rm-guess { display: flex; gap: 8px; margin-top: 10px; }
.rm-guess .btn { padding: 10px 16px; font-size: 14px; }
.rm-guess-note { margin: 10px 0 0; color: var(--tm); font-size: 13px; text-align: center; }

@media (max-width: 900px) {
    .rm-main { grid-template-columns: 1fr; }
    .rm-chat { height: 180px; }
}
</style>
