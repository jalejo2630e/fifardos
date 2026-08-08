<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    interval: { type: Number, default: 3200 },
});

const vp = ref(null);
const active = ref(0);

const reduceMotion = typeof window !== 'undefined' && window.matchMedia
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const behavior = reduceMotion ? 'auto' : 'smooth';

let timer = null, rafId = 0;

function slides() { return vp.value ? Array.from(vp.value.children) : []; }
function stepPx() {
    const s = slides();
    if (s.length > 1) return s[1].offsetLeft - s[0].offsetLeft;
    return s[0] ? s[0].offsetWidth : 300;
}

function scrollByCard(dir) {
    const el = vp.value;
    if (!el) return;
    // Loop en los extremos
    if (dir > 0 && el.scrollLeft + el.clientWidth >= el.scrollWidth - 2) {
        el.scrollTo({ left: 0, behavior }); return;
    }
    if (dir < 0 && el.scrollLeft <= 2) {
        el.scrollTo({ left: el.scrollWidth, behavior }); return;
    }
    el.scrollBy({ left: dir * stepPx(), behavior });
}

function goTo(i) {
    const s = slides()[i];
    if (s && vp.value) vp.value.scrollTo({ left: s.offsetLeft, behavior });
}

function onScroll() {
    if (rafId) return;
    rafId = requestAnimationFrame(() => {
        rafId = 0;
        const el = vp.value;
        if (!el) return;
        active.value = Math.max(0, Math.min(props.items.length - 1, Math.round(el.scrollLeft / stepPx())));
    });
}

function play() {
    if (reduceMotion || timer) return;
    timer = setInterval(() => scrollByCard(1), props.interval);
}
function pause() {
    if (timer) { clearInterval(timer); timer = null; }
}

function onVisibility() { document.hidden ? pause() : play(); }

onMounted(() => {
    play();
    document.addEventListener('visibilitychange', onVisibility);
});
onBeforeUnmount(() => {
    pause();
    if (rafId) cancelAnimationFrame(rafId);
    document.removeEventListener('visibilitychange', onVisibility);
});
</script>

<template>
    <div class="scar" @pointerenter="pause" @pointerleave="play" @focusin="pause" @focusout="play">
        <button class="scar-arrow prev" type="button" aria-label="Anterior" @click="scrollByCard(-1)">‹</button>

        <ul ref="vp" class="scar-vp" @scroll.passive="onScroll">
            <li v-for="(s, i) in items" :key="s.slug || i" class="scar-slide">
                <img :src="s.img" :alt="s.name" loading="lazy" draggable="false" />
                <div class="scar-cap">
                    <span class="scar-emoji" aria-hidden="true">{{ s.icon }}</span>
                    <span class="scar-name">{{ s.name }}</span>
                </div>
            </li>
        </ul>

        <button class="scar-arrow next" type="button" aria-label="Siguiente" @click="scrollByCard(1)">›</button>

        <div class="scar-dots" role="tablist">
            <button v-for="(s, i) in items" :key="'d' + i" type="button" class="scar-dot"
                    :class="{ on: i === active }" :aria-label="s.name" @click="goTo(i)"></button>
        </div>
    </div>
</template>

<style scoped>
.scar { position: relative; }

.scar-vp {
    list-style: none; margin: 0; padding: 4px;
    display: flex; gap: 16px;
    overflow-x: auto; scroll-snap-type: x mandatory;
    scrollbar-width: none; -ms-overflow-style: none;
}
.scar-vp::-webkit-scrollbar { display: none; }

.scar-slide {
    position: relative; flex: 0 0 auto;
    width: clamp(240px, 30%, 340px);
    aspect-ratio: 4 / 3;
    scroll-snap-align: start;
    border: 1px solid var(--bcard, rgba(255,255,255,.1));
    background: #0e0e11; overflow: hidden;
}
.scar-slide img {
    width: 100%; height: 100%; object-fit: cover; display: block;
    transition: transform .45s cubic-bezier(.22,.61,.36,1);
}
.scar-slide:hover img { transform: scale(1.06); }

.scar-cap {
    position: absolute; inset: auto 0 0 0; padding: 14px 16px;
    display: flex; align-items: center; gap: 10px;
    background: linear-gradient(0deg, rgba(8,8,10,.9), rgba(8,8,10,.35) 60%, transparent);
}
.scar-emoji { font-size: 22px; line-height: 1; }
.scar-name {
    font-family: var(--f-barlow, 'Barlow Condensed', sans-serif);
    font-weight: 700; font-size: 17px; letter-spacing: .05em;
    text-transform: uppercase; color: #f2f2f0;
}

/* Flechas */
.scar-arrow {
    position: absolute; top: calc(50% - 20px); transform: translateY(-50%);
    z-index: 2; width: 44px; height: 44px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; line-height: 1; color: #f2f2f0;
    background: rgba(8,8,10,.72); border: 1px solid var(--bcard, rgba(255,255,255,.14));
    backdrop-filter: blur(6px); transition: border-color .2s, color .2s, background .2s;
}
.scar-arrow:hover { border-color: var(--accent, #ff5f00); color: var(--accent, #ff5f00); background: rgba(8,8,10,.9); }
.scar-arrow.prev { left: -6px; }
.scar-arrow.next { right: -6px; }

/* Puntos */
.scar-dots { display: flex; justify-content: center; gap: 8px; margin-top: 18px; }
.scar-dot {
    width: 8px; height: 8px; padding: 0; cursor: pointer; border-radius: 50%;
    background: rgba(255,255,255,.22); border: none; transition: all .2s;
}
.scar-dot:hover { background: rgba(255,255,255,.45); }
.scar-dot.on { width: 22px; border-radius: 4px; background: var(--accent, #ff5f00); }

@media (max-width: 879px) {
    .scar-slide { width: 78%; }
    .scar-arrow { display: none; }   /* en móvil se navega por swipe */
    .scar-name { font-size: 15px; }
}
</style>
