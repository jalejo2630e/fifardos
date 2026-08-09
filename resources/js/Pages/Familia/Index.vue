<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { getToken, getName, setName } from '@/familia/session';

const GAMES = {
    pictionary: { icon: '🎨', name: 'Dibuja y Adivina' },
    trivia: { icon: '❓', name: 'Trivia' },
    tuttifrutti: { icon: '🔤', name: 'Tutti Frutti' },
    hangman: { icon: '🔠', name: 'Ahorcado' },
};

const name = ref('');
const game = ref('pictionary');
const joinCode = ref('');
const error = ref('');
const busy = ref(false);

onMounted(() => {
    name.value = getName();
    const flash = usePage().props?.flash?.error;
    if (flash) error.value = flash;
});

async function createRoom() {
    error.value = '';
    const n = name.value.trim();
    if (!n) { error.value = 'Escribí tu nombre.'; return; }
    busy.value = true;
    try {
        setName(n);
        const { data } = await axios.post('/familia', { name: n, token: getToken(), game: game.value });
        router.visit(`/familia/${data.code}`);
    } catch (e) {
        error.value = e.response?.data?.message || 'No se pudo crear la sala.';
        busy.value = false;
    }
}

async function joinRoom() {
    error.value = '';
    const n = name.value.trim();
    const code = joinCode.value.trim().toUpperCase();
    if (!n) { error.value = 'Escribí tu nombre.'; return; }
    if (!code) { error.value = 'Escribí el código de la sala.'; return; }
    busy.value = true;
    try {
        setName(n);
        const { data } = await axios.post(`/familia/${code}/join`, { name: n, token: getToken() });
        router.visit(`/familia/${data.code}`);
    } catch (e) {
        error.value = e.response?.data?.message || 'No se pudo unir a la sala.';
        busy.value = false;
    }
}
</script>

<template>
    <div class="fam">
        <Head title="Minijuegos — Jugá en vivo" />

        <header class="fam-hdr">
            <Link href="/" class="fam-logo"><img src="/brand/logo-horizontal-dark.png" alt="FIFARDOS" /></Link>
            <Link href="/" class="fam-back">← Volver al inicio</Link>
        </header>

        <main class="fam-main">
            <div class="fam-intro">
                <span class="fam-badge"><i class="dot"></i> En vivo · hasta 10 participantes</span>
                <h1>Jugá a los <span class="accent">minijuegos</span>,<br />estén donde estén.</h1>
                <p>Creá una sala, compartí el código y jueguen en tiempo real a <b>Dibuja y Adivina</b>, <b>Trivia</b> o <b>Tutti Frutti</b>. ¡Gana el participante que más puntos suma!</p>
            </div>

            <div class="fam-field">
                <label>Nombre del participante</label>
                <input v-model="name" maxlength="24" placeholder="Tu nombre" @keyup.enter="createRoom" />
            </div>

            <div class="fam-cards">
                <div class="fam-card">
                    <span class="fam-card-tag">Anfitrión</span>
                    <h2>Crear una sala</h2>
                    <p>Elegí un juego (podés cambiarlo después en la sala) y compartí el código.</p>
                    <div class="fam-games">
                        <button v-for="(g, key) in GAMES" :key="key" type="button" class="fam-game" :class="{ on: game === key }" @click="game = key">
                            <span class="fam-game-ic">{{ g.icon }}</span><span class="fam-game-nm">{{ g.name }}</span>
                        </button>
                    </div>
                    <button class="btn btn-solid" :disabled="busy" @click="createRoom">Crear sala →</button>
                </div>

                <div class="fam-card">
                    <span class="fam-card-tag">Invitado</span>
                    <h2>Unirse con código</h2>
                    <p>¿Ya te pasaron un código? Escribilo para entrar a la sala.</p>
                    <div class="fam-join">
                        <input v-model="joinCode" maxlength="8" placeholder="CÓDIGO" class="code-input" @keyup.enter="joinRoom" />
                        <button class="btn btn-outline" :disabled="busy" @click="joinRoom">Entrar</button>
                    </div>
                </div>
            </div>

            <p v-if="error" class="fam-error">{{ error }}</p>
        </main>
    </div>
</template>

<style scoped>
.fam {
    --accent: #ff5f00; --accent-hover: #ff7a26; --lime: #b6ff2e;
    --bg: #08080a; --card: #0e0e11; --hair: rgba(255,255,255,.1);
    --tp: #f2f2f0; --ts: #a8a8a3; --tm: #8f8f8b;
    --f-anton: 'Anton', Impact, sans-serif; --f-barlow: 'Barlow Condensed', sans-serif; --f-body: 'Chakra Petch', system-ui, sans-serif;
    min-height: 100vh; background: radial-gradient(circle at 50% -10%, #14100c, var(--bg) 60%);
    color: var(--tp); font-family: var(--f-body);
}
.fam * { box-sizing: border-box; }
.accent { color: var(--accent); }

.fam-hdr { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid var(--hair); }
.fam-logo img { height: 40px; display: block; }
.fam-back { color: var(--tm); text-decoration: none; font-size: 13px; letter-spacing: .1em; text-transform: uppercase; }
.fam-back:hover { color: var(--accent); }

.fam-main { max-width: 900px; margin: 0 auto; padding: 48px 24px 80px; }
.fam-intro { text-align: center; margin-bottom: 36px; }
.fam-badge { display: inline-flex; align-items: center; gap: 8px; border: 1px solid rgba(182,255,46,.4); color: var(--lime); font-size: 12px; letter-spacing: .16em; text-transform: uppercase; padding: 6px 12px; }
.fam-badge .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--lime); animation: fpulse 1.4s infinite; }
.fam-intro h1 { font-family: var(--f-anton); font-size: clamp(36px, 7vw, 64px); line-height: .95; text-transform: uppercase; margin: 18px 0 14px; }
.fam-intro p { color: var(--ts); font-size: 17px; max-width: 560px; margin: 0 auto; }

.fam-field { max-width: 460px; margin: 0 auto 24px; }
.fam-field label { display: block; font-size: 12px; letter-spacing: .14em; text-transform: uppercase; color: var(--tm); margin-bottom: 8px; }
.fam input { width: 100%; background: var(--card); border: 1px solid var(--hair); color: var(--tp); font-family: var(--f-body); font-size: 17px; padding: 13px 15px; outline: none; transition: border-color .15s; }
.fam input:focus { border-color: var(--accent); }

.fam-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; max-width: 720px; margin: 0 auto; }
.fam-card { background: var(--card); border: 1px solid var(--hair); padding: 24px 22px; display: flex; flex-direction: column; gap: 10px; }
.fam-card-tag { font-family: var(--f-anton); font-size: 12px; letter-spacing: .2em; text-transform: uppercase; color: var(--accent); }
.fam-card h2 { font-family: var(--f-barlow); font-weight: 800; font-size: 26px; text-transform: uppercase; letter-spacing: .02em; margin: 0; }
.fam-card p { color: var(--tm); font-size: 14.5px; margin: 0 0 6px; flex: 1; }

.fam-games { display: flex; gap: 6px; margin-bottom: 4px; }
.fam-game { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 3px; padding: 8px 4px; cursor: pointer;
    background: var(--bg); border: 1px solid var(--hair); color: var(--tp); transition: border-color .15s, background .15s; }
.fam-game.on { border-color: var(--accent); background: rgba(255,95,0,.1); }
.fam-game-ic { font-size: 20px; }
.fam-game-nm { font-family: var(--f-barlow); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .03em; text-align: center; }

.fam-join { display: flex; gap: 8px; }
.code-input { text-transform: uppercase; letter-spacing: .2em; font-family: var(--f-barlow); font-weight: 700; }

.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; border: none; font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; letter-spacing: .08em; font-size: 17px; padding: 13px 22px; transition: background-color .18s, border-color .18s, color .18s; }
.btn:disabled { opacity: .6; cursor: wait; }
.btn-solid { background: var(--accent); color: #08080a; clip-path: polygon(12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%, 0 12px); }
.btn-solid:hover:not(:disabled) { background: var(--accent-hover); }
.btn-outline { background: transparent; color: var(--tp); border: 1px solid rgba(255,255,255,.22); white-space: nowrap; }
.btn-outline:hover:not(:disabled) { border-color: var(--accent); color: var(--accent); }

.fam-error { text-align: center; margin-top: 20px; color: #ff7a6b; font-size: 15px; }

@keyframes fpulse { 0%,100% { opacity: 1; } 50% { opacity: .3; } }

@media (max-width: 719px) {
    .fam-cards { grid-template-columns: 1fr; }
}
</style>
