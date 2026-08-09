<script setup>
import { ref, shallowRef, reactive, computed, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const canvasRef = ref(null);
const stageRef = ref(null);

const started = ref(false);
const loading = ref(false);
const failed = ref(false);

const difficulty = ref('medium'); // easy | medium | hard

const hud = reactive({
    phase: 'kickoff', timeLeft: 120, home: 0, away: 0, message: '', resultText: '',
    charge: { kind: null, power: 0 },
});

const game = shallowRef(null);
let io = null;

function onUpdate(s) { Object.assign(hud, s); }

const clock = computed(() => {
    const s = Math.max(0, hud.timeLeft | 0);
    return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
});
const resultText = computed(() => ({
    win: t('football.win'), lose: t('football.lose'), draw: t('football.draw'),
}[hud.resultText] || ''));

async function play() {
    if (started.value || loading.value) return;
    loading.value = true;
    failed.value = false;
    try {
        const { createFootballGame } = await import('@/game/footballGame.js');
        await new Promise((r) => requestAnimationFrame(r)); // asegura canvas montado
        game.value = await createFootballGame({
            canvas: canvasRef.value,
            container: stageRef.value,
            difficulty: difficulty.value,
            duration: 120,
            onUpdate,
        });
        started.value = true;

        // Pausa cuando el juego sale del viewport (ahorra batería/CPU).
        io = new IntersectionObserver((entries) => {
            entries.forEach((e) => {
                if (!game.value) return;
                e.isIntersecting ? game.value.resume() : game.value.pause();
            });
        }, { threshold: 0.15 });
        io.observe(stageRef.value);
    } catch (err) {
        console.error('[FootballArena]', err);
        failed.value = true;
    } finally {
        loading.value = false;
    }
}

function setDifficulty(d) {
    difficulty.value = d;
    if (game.value) { game.value.setDifficulty(d); game.value.reset(); }
}
function again() { game.value && game.value.reset(); }

// ---- Joystick (pointer events: cubre touch/mouse/pen de forma uniforme) ----
const stick = reactive({ active: false, id: null, ox: 0, oy: 0, dx: 0, dy: 0 });
const STICK_R = 46;
function stickStart(e) {
    stick.active = true; stick.id = e.pointerId;
    stick.ox = e.clientX; stick.oy = e.clientY; stick.dx = 0; stick.dy = 0;
    e.currentTarget.setPointerCapture?.(e.pointerId);
}
function stickMove(e) {
    if (!stick.active || e.pointerId !== stick.id) return;
    let dx = e.clientX - stick.ox;
    let dy = e.clientY - stick.oy;
    const d = Math.hypot(dx, dy);
    if (d > STICK_R) { dx = dx / d * STICK_R; dy = dy / d * STICK_R; }
    stick.dx = dx; stick.dy = dy;
    // Arriba en pantalla = adelante (+z), por eso -dy.
    game.value && game.value.move(dx / STICK_R, -dy / STICK_R);
}
function stickEnd(e) {
    if (e && e.pointerId !== stick.id) return;
    stick.active = false; stick.dx = 0; stick.dy = 0;
    game.value && game.value.move(0, 0);
}

function passDown() { game.value && game.value.passDown(); }
function passUp() { game.value && game.value.passUp(); }
function shootDown() { game.value && game.value.shootDown(); }
function shootUp() { game.value && game.value.shootUp(); }

onBeforeUnmount(() => {
    if (io) io.disconnect();
    if (game.value) game.value.dispose();
});
</script>

<template>
    <section id="jugar" class="fb">
        <div class="wrap">
            <div class="fb-head" data-reveal>
                <span class="eyebrow">{{ t('football.eyebrow') }}</span>
                <h2>{{ t('football.h1') }}<br /><span class="accent">{{ t('football.h2') }}</span></h2>
                <p class="fb-note">{{ t('football.note') }}</p>
            </div>

            <div ref="stageRef" class="stage" data-reveal>
                <canvas ref="canvasRef" class="stage-canvas" :class="{ live: started }"></canvas>

                <!-- Póster / botón jugar -->
                <div v-if="!started" class="poster" :class="{ dim: loading }">
                    <div class="poster-diff">
                        <button :class="{ on: difficulty === 'easy' }" @click="difficulty = 'easy'">{{ t('football.easy') }}</button>
                        <button :class="{ on: difficulty === 'medium' }" @click="difficulty = 'medium'">{{ t('football.medium') }}</button>
                        <button :class="{ on: difficulty === 'hard' }" @click="difficulty = 'hard'">{{ t('football.hard') }}</button>
                    </div>
                    <button class="poster-play" :disabled="loading" @click="play">
                        <span v-if="!loading" class="play-tri" aria-hidden="true">▶</span>
                        {{ loading ? t('football.loading') : t('football.play') }}
                    </button>
                    <p class="poster-hint">{{ t('football.posterHint') }}</p>
                    <p v-if="failed" class="poster-fail">{{ t('football.failed') }}</p>
                </div>

                <!-- HUD en juego -->
                <template v-else>
                    <div class="hud hud-top">
                        <div class="scoreboard">
                            <span class="team home">{{ t('football.you') }}</span>
                            <span class="score">{{ hud.home }}<i>-</i>{{ hud.away }}</span>
                            <span class="team away">{{ t('football.cpu') }}</span>
                        </div>
                        <span class="clock" :class="{ low: hud.timeLeft <= 15 }">{{ clock }}</span>
                    </div>

                    <!-- Barra de fuerza (pase / tiro) -->
                    <div v-if="hud.charge.kind && hud.phase === 'play'" class="power" :class="hud.charge.kind">
                        <span class="power-lbl">{{ hud.charge.kind === 'shoot' ? t('football.bShoot') : t('football.bPass') }}</span>
                        <span class="power-bar"><i :style="{ width: (hud.charge.power * 100) + '%' }"></i></span>
                    </div>

                    <!-- Cuenta regresiva del saque -->
                    <div v-if="hud.phase === 'kickoff'" class="flash go">{{ t('football.kickoff') }}</div>
                    <!-- ¡Gol! -->
                    <div v-if="hud.phase === 'goal'" class="flash goal">{{ t('football.goal') }}</div>

                    <!-- Fin del partido -->
                    <div v-if="hud.phase === 'fulltime'" class="over">
                        <span class="over-title">{{ t('football.fulltime') }}</span>
                        <span class="over-score">{{ hud.home }} - {{ hud.away }}</span>
                        <span class="over-result" :class="hud.resultText">{{ resultText }}</span>
                        <button class="poster-play" @click="again">{{ t('football.again') }}</button>
                    </div>

                    <!-- Controles táctiles (móvil) -->
                    <div v-if="hud.phase !== 'fulltime'" class="touch">
                        <div class="joy"
                             @pointerdown.prevent="stickStart" @pointermove.prevent="stickMove"
                             @pointerup.prevent="stickEnd" @pointercancel.prevent="stickEnd">
                            <span class="joy-base"></span>
                            <span class="joy-knob" :style="{ transform: `translate(${stick.dx}px, ${stick.dy}px)` }"></span>
                        </div>
                        <div class="btns">
                            <button class="ab pass"
                                    @pointerdown.prevent="passDown" @pointerup.prevent="passUp" @pointercancel.prevent="passUp">{{ t('football.bPass') }}</button>
                            <button class="ab shoot"
                                    @pointerdown.prevent="shootDown" @pointerup.prevent="shootUp" @pointercancel.prevent="shootUp">{{ t('football.bShoot') }}</button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Ayuda de controles + reinicio -->
            <div v-if="started" class="fb-ctl" data-reveal>
                <p class="controls">
                    <span>{{ t('football.ctrlMove') }}</span>
                    <span>{{ t('football.ctrlPass') }}</span>
                    <span>{{ t('football.ctrlShoot') }}</span>
                </p>
                <div class="seg">
                    <button :class="{ on: difficulty === 'easy' }" @click="setDifficulty('easy')">{{ t('football.easy') }}</button>
                    <button :class="{ on: difficulty === 'medium' }" @click="setDifficulty('medium')">{{ t('football.medium') }}</button>
                    <button :class="{ on: difficulty === 'hard' }" @click="setDifficulty('hard')">{{ t('football.hard') }}</button>
                </div>
                <button class="seg-reset" @click="again">{{ t('football.reset') }}</button>
            </div>
        </div>
    </section>
</template>

<style scoped>
.fb { padding: 92px 0; background: var(--bg-alt, #0b0b0d); border-top: 1px solid rgba(255,255,255,.08); border-bottom: 1px solid rgba(255,255,255,.08); }
.wrap { max-width: 1240px; margin: 0 auto; padding: 0 24px; }
.fb-head { margin-bottom: 28px; text-align: center; }
.eyebrow { font-family: var(--f-anton, 'Anton', sans-serif); font-size: 12px; letter-spacing: .2em; text-transform: uppercase; color: var(--lime, #b6ff2e); }
.fb h2 { font-family: var(--f-anton, 'Anton', sans-serif); text-transform: uppercase; font-size: 52px; line-height: .95; letter-spacing: -.5px; margin: 10px 0 12px; }
.accent { color: var(--accent, #ff5f00); }
.fb-note { color: var(--tm, #8f8f8b); font-size: 16px; max-width: 620px; margin: 0 auto; }

/* Escenario 16:9 */
.stage { position: relative; width: 100%; aspect-ratio: 16 / 9; background: radial-gradient(circle at 50% 30%, #0f2417, #06100a 70%); border: 1px solid rgba(255,255,255,.1); overflow: hidden; box-shadow: 0 40px 90px -30px rgba(0,0,0,.9); }
.stage-canvas { position: absolute; inset: 0; width: 100%; height: 100%; display: block; opacity: 0; transition: opacity .5s ease; touch-action: none; }
.stage-canvas.live { opacity: 1; }

/* Póster */
.poster { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 18px; z-index: 3;
    background: linear-gradient(180deg, rgba(6,16,10,.35), rgba(6,16,10,.78)), url('/header.webp') center/cover no-repeat; }
.poster.dim { filter: brightness(.6); }
.poster-diff { display: flex; gap: 8px; }
.poster-diff button, .seg button, .poster-play, .seg-reset { font-family: var(--f-barlow, 'Barlow Condensed', sans-serif); font-weight: 700; text-transform: uppercase; letter-spacing: .08em; cursor: pointer; transition: all .18s ease; }
.poster-diff button { background: rgba(0,0,0,.5); border: 1px solid rgba(255,255,255,.22); color: #ddd; padding: 8px 18px; font-size: 15px; }
.poster-diff button.on { background: rgba(255,95,0,.18); border-color: var(--accent, #ff5f00); color: var(--accent, #ff5f00); }
.poster-play { display: inline-flex; align-items: center; gap: 10px; background: var(--accent, #ff5f00); color: #08080a; border: none; font-size: 22px; padding: 15px 34px 13px;
    clip-path: polygon(14px 0, 100% 0, 100% calc(100% - 14px), calc(100% - 14px) 100%, 0 100%, 0 14px); }
.poster-play:hover:not(:disabled) { background: var(--accent-hover, #ff7a26); }
.poster-play:disabled { opacity: .8; cursor: wait; }
.play-tri { font-size: 15px; }
.poster-hint, .poster-fail { font-size: 13px; color: #cfcfca; letter-spacing: .04em; margin: 0; }
.poster-fail { color: #ff7a6b; }

/* HUD superior: marcador + reloj */
.hud { position: absolute; left: 0; right: 0; z-index: 4; pointer-events: none; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; }
.hud-top { top: 0; background: linear-gradient(180deg, rgba(6,16,10,.75), transparent); }
.scoreboard { display: inline-flex; align-items: center; gap: 12px; }
.team { font-family: var(--f-barlow, 'Barlow Condensed', sans-serif); font-size: 14px; letter-spacing: .12em; text-transform: uppercase; }
.team.home { color: var(--accent, #ff5f00); }
.team.away { color: #6fa0ff; }
.score { font-family: var(--f-anton, 'Anton', sans-serif); font-size: 30px; color: #fff; letter-spacing: 2px; }
.score i { font-style: normal; color: #8f8f8b; margin: 0 4px; }
.clock { font-family: var(--f-anton, 'Anton', sans-serif); font-size: 26px; color: #f2f2f0; letter-spacing: 1px; }
.clock.low { color: #ff5f5f; }

/* Barra de fuerza (pase / tiro) */
.power { position: absolute; left: 50%; bottom: 16%; transform: translateX(-50%); z-index: 5; pointer-events: none;
    display: flex; flex-direction: column; align-items: center; gap: 6px; width: min(340px, 62%); }
.power-lbl { font-family: var(--f-barlow, 'Barlow Condensed', sans-serif); font-weight: 800; text-transform: uppercase; letter-spacing: .14em; font-size: 13px; color: #fff; text-shadow: 0 2px 8px rgba(0,0,0,.7); }
.power-bar { width: 100%; height: 12px; background: rgba(0,0,0,.55); border: 1px solid rgba(255,255,255,.35); overflow: hidden; }
.power-bar i { display: block; height: 100%; transition: width .04s linear; }
.power.pass .power-bar i { background: linear-gradient(90deg, #6fa0ff, #2f6bff); }
.power.pass .power-lbl { color: #9dc0ff; }
.power.shoot .power-bar i { background: linear-gradient(90deg, var(--lime, #b6ff2e), var(--accent, #ff5f00)); }
.power.shoot .power-lbl { color: var(--accent, #ff5f00); }

.flash { position: absolute; inset: 0; z-index: 5; display: flex; align-items: center; justify-content: center; pointer-events: none;
    font-family: var(--f-anton, 'Anton', sans-serif); font-size: clamp(40px, 10vw, 120px); letter-spacing: 2px; text-transform: uppercase;
    text-shadow: 0 8px 40px rgba(0,0,0,.85); animation: flashIn .35s ease; }
.flash.goal { color: var(--lime, #b6ff2e); }
.flash.go { color: #fff; font-size: clamp(28px, 7vw, 80px); }
@keyframes flashIn { from { opacity: 0; transform: scale(1.4); } to { opacity: 1; transform: none; } }

.over { position: absolute; inset: 0; z-index: 6; display: flex; flex-direction: column; gap: 12px; align-items: center; justify-content: center; background: rgba(6,16,10,.72); }
.over-title { font-family: var(--f-anton, 'Anton', sans-serif); font-size: clamp(28px, 6vw, 56px); text-transform: uppercase; color: #f2f2f0; }
.over-score { font-family: var(--f-anton, 'Anton', sans-serif); font-size: clamp(36px, 8vw, 72px); color: #fff; letter-spacing: 3px; }
.over-result { font-family: var(--f-barlow, 'Barlow Condensed', sans-serif); font-size: 22px; letter-spacing: .12em; text-transform: uppercase; }
.over-result.win { color: var(--lime, #b6ff2e); }
.over-result.lose { color: #ff7a6b; }
.over-result.draw { color: #cfcfca; }

/* Controles táctiles */
.touch { position: absolute; inset: 0; z-index: 4; pointer-events: none; }
.joy { position: absolute; left: 3%; bottom: 8%; width: 118px; height: 118px; pointer-events: auto; touch-action: none; }
.joy-base { position: absolute; inset: 0; border-radius: 50%; background: rgba(255,255,255,.08); border: 2px solid rgba(255,255,255,.22); }
.joy-knob { position: absolute; left: 50%; top: 50%; width: 52px; height: 52px; margin: -26px 0 0 -26px; border-radius: 50%; background: rgba(255,95,0,.85); border: 2px solid #fff; }
.btns { position: absolute; right: 3%; bottom: 8%; display: flex; gap: 14px; align-items: flex-end; pointer-events: auto; }
.ab { pointer-events: auto; width: 72px; height: 72px; border-radius: 50%; border: 2px solid rgba(255,255,255,.35); font-family: var(--f-barlow, 'Barlow Condensed', sans-serif); font-weight: 700; text-transform: uppercase; font-size: 14px; letter-spacing: .06em; color: #fff; touch-action: none; cursor: pointer; }
.ab.pass { background: rgba(47,107,255,.75); }
.ab.shoot { background: rgba(255,95,0,.85); width: 84px; height: 84px; font-size: 15px; }
.ab:active { transform: scale(.92); }

/* Controles bajo el escenario */
.fb-ctl { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; justify-content: center; margin-top: 18px; }
.controls { margin: 0; font-size: 12px; letter-spacing: .06em; color: #b9b9b4; display: flex; gap: 16px; flex-wrap: wrap; justify-content: center; text-align: center; }
.seg { display: inline-flex; border: 1px solid rgba(255,255,255,.16); }
.seg button { background: transparent; border: none; color: #b9b9b4; padding: 9px 16px; font-size: 14px; border-right: 1px solid rgba(255,255,255,.12); }
.seg button:last-child { border-right: none; }
.seg button.on { background: rgba(255,95,0,.16); color: var(--accent, #ff5f00); }
.seg-reset { background: transparent; border: 1px solid rgba(255,255,255,.16); color: #b9b9b4; padding: 9px 16px; font-size: 14px; }
.seg-reset:hover { border-color: var(--accent, #ff5f00); color: var(--accent, #ff5f00); }

@media (max-width: 879px) {
    .fb { padding: 60px 0; }
    .fb h2 { font-size: 34px; }
    .score { font-size: 24px; }
    .clock { font-size: 20px; }
    .controls { display: none; }   /* en móvil mandan los controles táctiles */
}
/* En escritorio con puntero fino, ocultamos el joystick táctil */
@media (hover: hover) and (pointer: fine) {
    .touch { display: none; }
}
@media (prefers-reduced-motion: reduce) {
    .stage-canvas { transition: none; }
    .flash { animation: none; }
}
</style>
