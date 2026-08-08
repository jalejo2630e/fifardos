<script setup>
import { ref, shallowRef, reactive, computed, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const canvasRef = ref(null);
const stageRef = ref(null);

const started = ref(false);
const loading = ref(false);
const failed = ref(false);

const mode = ref('solo');          // solo | duo
const difficulty = ref('medium');  // easy | medium | hard

const hud = reactive({
    phase: 'aim', shot: 0, shots: 5, goals: 0, saves: 0, misses: 0,
    power: 0, result: null, message: '',
});

const game = shallowRef(null);
let io = null;

function onUpdate(s) { Object.assign(hud, s); }

// Texto del resultado, localizado (el motor solo reporta result: goal|save|miss)
const flashText = computed(() => ({ goal: t('penalty.fGoal'), save: t('penalty.fSave'), miss: t('penalty.fMiss') }[hud.result] || ''));

async function play() {
    if (started.value || loading.value) return;
    loading.value = true;
    failed.value = false;
    try {
        // Carga en diferido: three + rapier viajan en su propio chunk.
        const { createPenaltyGame } = await import('@/game/penaltyGame.js');
        await new Promise((r) => requestAnimationFrame(r)); // asegura canvas montado
        game.value = await createPenaltyGame({
            canvas: canvasRef.value,
            container: stageRef.value,
            mode: mode.value,
            difficulty: difficulty.value,
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
        console.error('[PenaltyArena]', err);
        failed.value = true;
    } finally {
        loading.value = false;
    }
}

function setMode(m) {
    mode.value = m;
    if (game.value) { game.value.setMode(m); game.value.reset(); }
}
function setDifficulty(d) {
    difficulty.value = d;
    if (game.value) { game.value.setDifficulty(d); game.value.reset(); }
}
function again() { game.value && game.value.reset(); }

onBeforeUnmount(() => {
    if (io) io.disconnect();
    if (game.value) game.value.dispose();
});
</script>

<template>
    <section id="jugar" class="pen">
        <div class="wrap">
            <div class="pen-head" data-reveal>
                <span class="eyebrow">{{ t('penalty.eyebrow') }}</span>
                <h2>{{ t('penalty.h1') }}<br /><span class="accent">{{ t('penalty.h2') }}</span></h2>
                <p class="pen-note">{{ t('penalty.note') }}</p>
            </div>

            <div ref="stageRef" class="stage" data-reveal>
                <canvas ref="canvasRef" class="stage-canvas" :class="{ live: started }"></canvas>

                <!-- Póster / botón jugar -->
                <div v-if="!started" class="poster" :class="{ dim: loading }">
                    <div class="poster-mode">
                        <button :class="{ on: mode === 'solo' }" @click="mode = 'solo'">{{ t('penalty.p1') }}</button>
                        <button :class="{ on: mode === 'duo' }" @click="mode = 'duo'">{{ t('penalty.p2') }}</button>
                    </div>
                    <button class="poster-play" :disabled="loading" @click="play">
                        <span v-if="!loading" class="play-tri" aria-hidden="true">▶</span>
                        {{ loading ? t('penalty.loading') : t('penalty.play') }}
                    </button>
                    <p v-if="mode === 'duo'" class="poster-hint">{{ t('penalty.duoHint') }}</p>
                    <p v-if="failed" class="poster-fail">{{ t('penalty.failed') }}</p>
                </div>

                <!-- HUD en juego -->
                <template v-else>
                    <div class="hud hud-top">
                        <div class="score">
                            <span class="sc goal"><b>{{ hud.goals }}</b>{{ t('penalty.goals') }}</span>
                            <span class="sc save"><b>{{ hud.saves }}</b>{{ t('penalty.saves') }}</span>
                            <span class="sc miss"><b>{{ hud.misses }}</b>{{ t('penalty.miss') }}</span>
                        </div>
                        <span class="shotc">{{ Math.min(hud.shot + (hud.phase === 'gameover' ? 0 : 1), hud.shots) }} / {{ hud.shots }}</span>
                    </div>

                    <!-- Mensaje de resultado -->
                    <div v-if="hud.result && hud.phase === 'result'"
                         class="flash" :class="hud.result">{{ flashText }}</div>

                    <!-- Barra de potencia -->
                    <div class="hud hud-bottom">
                        <div class="power" v-show="hud.phase === 'charge' || hud.phase === 'aim'">
                            <div class="power-fill" :style="{ width: (hud.power * 100) + '%' }"></div>
                        </div>
                        <p class="controls">
                            <span>{{ t('penalty.ctrlShoot') }}</span>
                            <span v-if="mode === 'solo'">{{ t('penalty.ctrlCurve') }}</span>
                            <span v-if="mode === 'duo'" class="keeper-keys">{{ t('penalty.ctrlKeeper') }}</span>
                        </p>
                    </div>

                    <!-- Fin de tanda -->
                    <div v-if="hud.phase === 'gameover'" class="over">
                        <span class="over-title">{{ t('penalty.overTitle') }}</span>
                        <span class="over-score">{{ hud.goals }} / {{ hud.shots }} {{ t('penalty.goals') }}</span>
                        <button class="poster-play" @click="again">{{ t('penalty.again') }}</button>
                    </div>
                </template>
            </div>

            <!-- Controles bajo el escenario -->
            <div v-if="started" class="pen-ctl" data-reveal>
                <div class="seg">
                    <button :class="{ on: mode === 'solo' }" @click="setMode('solo')">{{ t('penalty.p1') }}</button>
                    <button :class="{ on: mode === 'duo' }" @click="setMode('duo')">{{ t('penalty.p2') }}</button>
                </div>
                <div v-if="mode === 'solo'" class="seg">
                    <button :class="{ on: difficulty === 'easy' }" @click="setDifficulty('easy')">{{ t('penalty.easy') }}</button>
                    <button :class="{ on: difficulty === 'medium' }" @click="setDifficulty('medium')">{{ t('penalty.medium') }}</button>
                    <button :class="{ on: difficulty === 'hard' }" @click="setDifficulty('hard')">{{ t('penalty.hard') }}</button>
                </div>
                <button class="seg-reset" @click="again">{{ t('penalty.reset') }}</button>
            </div>
        </div>
    </section>
</template>

<style scoped>
.pen { padding: 92px 0; background: var(--bg-alt, #0b0b0d); border-top: 1px solid rgba(255,255,255,.08); border-bottom: 1px solid rgba(255,255,255,.08); }
.wrap { max-width: 1240px; margin: 0 auto; padding: 0 24px; }
.pen-head { margin-bottom: 28px; text-align: center; }
.eyebrow { font-family: var(--f-anton, 'Anton', sans-serif); font-size: 12px; letter-spacing: .2em; text-transform: uppercase; color: var(--lime, #b6ff2e); }
.pen h2 { font-family: var(--f-anton, 'Anton', sans-serif); text-transform: uppercase; font-size: 52px; line-height: .95; letter-spacing: -.5px; margin: 10px 0 12px; }
.accent { color: var(--accent, #ff5f00); }
.pen-note { color: var(--tm, #8f8f8b); font-size: 16px; max-width: 560px; margin: 0 auto; }

/* Escenario 16:9 */
.stage { position: relative; width: 100%; aspect-ratio: 16 / 9; background: radial-gradient(circle at 50% 30%, #16181d, #08080a 70%); border: 1px solid rgba(255,255,255,.1); overflow: hidden; box-shadow: 0 40px 90px -30px rgba(0,0,0,.9); }
.stage-canvas { position: absolute; inset: 0; width: 100%; height: 100%; display: block; opacity: 0; transition: opacity .5s ease; touch-action: none; }
.stage-canvas.live { opacity: 1; }

/* Póster */
.poster { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 18px; z-index: 3;
    background: linear-gradient(180deg, rgba(8,8,10,.35), rgba(8,8,10,.75)), url('/header.webp') center/cover no-repeat; }
.poster.dim { filter: brightness(.6); }
.poster-mode { display: flex; gap: 8px; }
.poster-mode button, .seg button, .poster-play, .seg-reset { font-family: var(--f-barlow, 'Barlow Condensed', sans-serif); font-weight: 700; text-transform: uppercase; letter-spacing: .08em; cursor: pointer; transition: all .18s ease; }
.poster-mode button { background: rgba(0,0,0,.5); border: 1px solid rgba(255,255,255,.22); color: #ddd; padding: 8px 18px; font-size: 15px; }
.poster-mode button.on { background: rgba(255,95,0,.18); border-color: var(--accent, #ff5f00); color: var(--accent, #ff5f00); }
.poster-play { display: inline-flex; align-items: center; gap: 10px; background: var(--accent, #ff5f00); color: #08080a; border: none; font-size: 22px; padding: 15px 34px 13px;
    clip-path: polygon(14px 0, 100% 0, 100% calc(100% - 14px), calc(100% - 14px) 100%, 0 100%, 0 14px); }
.poster-play:hover:not(:disabled) { background: var(--accent-hover, #ff7a26); }
.poster-play:disabled { opacity: .8; cursor: wait; }
.play-tri { font-size: 15px; }
.poster-hint, .poster-fail { font-size: 13px; color: #cfcfca; letter-spacing: .04em; margin: 0; }
.poster-fail { color: #ff7a6b; }

/* HUD */
.hud { position: absolute; left: 0; right: 0; z-index: 4; pointer-events: none; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; }
.hud-top { top: 0; background: linear-gradient(180deg, rgba(8,8,10,.7), transparent); }
.hud-bottom { bottom: 0; flex-direction: column; gap: 8px; background: linear-gradient(0deg, rgba(8,8,10,.7), transparent); }
.score { display: flex; gap: 16px; }
.sc { font-family: var(--f-barlow, 'Barlow Condensed', sans-serif); font-size: 13px; letter-spacing: .1em; text-transform: uppercase; color: #cfcfca; display: inline-flex; align-items: baseline; gap: 6px; }
.sc b { font-family: var(--f-anton, 'Anton', sans-serif); font-size: 22px; }
.sc.goal b { color: var(--lime, #b6ff2e); }
.sc.save b { color: #6fb8ff; }
.sc.miss b { color: #ff7a6b; }
.shotc { font-family: var(--f-anton, 'Anton', sans-serif); font-size: 18px; color: #f2f2f0; }

.flash { position: absolute; inset: 0; z-index: 5; display: flex; align-items: center; justify-content: center; pointer-events: none;
    font-family: var(--f-anton, 'Anton', sans-serif); font-size: clamp(40px, 9vw, 110px); letter-spacing: 2px; text-transform: uppercase;
    text-shadow: 0 8px 40px rgba(0,0,0,.8); animation: flashIn .35s ease; }
.flash.goal { color: var(--lime, #b6ff2e); }
.flash.save { color: #6fb8ff; }
.flash.miss { color: #ff7a6b; }
@keyframes flashIn { from { opacity: 0; transform: scale(1.4); } to { opacity: 1; transform: none; } }

.power { width: min(420px, 70%); height: 10px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2); overflow: hidden; }
.power-fill { height: 100%; background: linear-gradient(90deg, var(--lime, #b6ff2e), var(--accent, #ff5f00)); }
.controls { margin: 0; font-size: 12px; letter-spacing: .06em; color: #b9b9b4; display: flex; gap: 16px; flex-wrap: wrap; justify-content: center; text-align: center; }
.keeper-keys { color: var(--lime, #b6ff2e); }

.over { position: absolute; inset: 0; z-index: 6; display: flex; flex-direction: column; gap: 14px; align-items: center; justify-content: center; background: rgba(8,8,10,.6); }
.over-title { font-family: var(--f-anton, 'Anton', sans-serif); font-size: clamp(28px, 6vw, 56px); text-transform: uppercase; color: #f2f2f0; }
.over-score { font-family: var(--f-barlow, 'Barlow Condensed', sans-serif); font-size: 20px; letter-spacing: .1em; text-transform: uppercase; color: var(--lime, #b6ff2e); }

/* Controles bajo el escenario */
.pen-ctl { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: center; margin-top: 18px; }
.seg { display: inline-flex; border: 1px solid rgba(255,255,255,.16); }
.seg button { background: transparent; border: none; color: #b9b9b4; padding: 9px 16px; font-size: 14px; border-right: 1px solid rgba(255,255,255,.12); }
.seg button:last-child { border-right: none; }
.seg button.on { background: rgba(255,95,0,.16); color: var(--accent, #ff5f00); }
.seg-reset { background: transparent; border: 1px solid rgba(255,255,255,.16); color: #b9b9b4; padding: 9px 16px; font-size: 14px; }
.seg-reset:hover { border-color: var(--accent, #ff5f00); color: var(--accent, #ff5f00); }

@media (max-width: 879px) {
    .pen { padding: 60px 0; }
    .pen h2 { font-size: 34px; }
    .sc b { font-size: 18px; }
    .controls { font-size: 11px; }
}
@media (prefers-reduced-motion: reduce) {
    .stage-canvas { transition: none; }
    .flash { animation: none; }
}
</style>
