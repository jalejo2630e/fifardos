<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, onBeforeUnmount, ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import PenaltyArena from '@/Components/PenaltyArena.vue';
import SportsCarousel from '@/Components/SportsCarousel.vue';

const { t, tm } = useI18n();

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    tournament: Object,
    prizes: Array,
    standings: Array,
    stats: { type: Object, default: () => ({}) },
});

// ---- Contenido (constantes del módulo) ----
const tableRows = [
    { pos: 1, name: 'Nico', ini: 'NI', team: 'Real Madrid', pj: 4, dg: '+7', pts: 10 },
    { pos: 2, name: 'Maru', ini: 'MA', team: 'Inter', pj: 4, dg: '+4', pts: 9 },
    { pos: 3, name: 'El Chino', ini: 'CH', team: 'Liverpool', pj: 4, dg: '+1', pts: 6 },
    { pos: 4, name: 'Juanma', ini: 'JU', team: 'Boca', pj: 4, dg: '-3', pts: 3 },
    { pos: 5, name: 'Tincho', ini: 'TI', team: 'Newcastle', pj: 4, dg: '-9', pts: 1 },
];

const steps = computed(() => [0, 1, 2, 3].map((i) => ({
    n: String(i + 1).padStart(2, '0'),
    t: t(`landing.steps.${i}.t`),
    d: t(`landing.steps.${i}.d`),
})));

const features = computed(() => [0, 1, 2, 3, 4, 5].map((i) => ({
    tag: t(`landing.features.${i}.tag`),
    t: t(`landing.features.${i}.t`),
    d: t(`landing.features.${i}.d`),
})));

// Orden alineado con landing.sports (i18n). La imagen es independiente del idioma.
const SPORT_SLUGS = ['consola', 'futbol', 'futsal', 'basquet', 'voley', 'tenis', 'padel', 'pickleball', 'handball', 'rugby'];
const sports = computed(() => {
    // OJO: para mensajes que son array hay que usar tm() (t() devolvería el string de la clave).
    const raw = tm('landing.sports');
    return (Array.isArray(raw) ? raw : []).map((s, i) => ({
        icon: s.icon, name: s.name, slug: SPORT_SLUGS[i], img: `/sports/${SPORT_SLUGS[i]}.svg`,
    }));
});

const quotes = computed(() => [0, 1, 2].map((i) => ({
    q: t(`landing.quotes.${i}.q`),
    a: t(`landing.quotes.${i}.a`),
})));

const faqs = computed(() => [0, 1, 2, 3].map((i) => ({
    q: t(`landing.faqs.${i}.q`),
    a: t(`landing.faqs.${i}.a`),
})));


const bracket = {
    quarters: [{ name: 'Nico', score: 3 }, { name: 'Juanma', score: 1 }, { name: 'Maru', score: 2 }, { name: 'El Chino', score: 0 }],
    semis: [{ name: 'Nico', score: 2 }, { name: 'Maru', score: 1 }],
    champion: 'Nico',
};

// ---- Estado / animación ----
const scrolled = ref(false);
const glowRef = ref(null);
const cardRef = ref(null);
const progressRef = ref(null);
const heroSeconds = ref(0);
const showMcp = ref(false);
const mcpTab = ref('claude');
const mobileMenuOpen = ref(false);

function closeMenu() { mobileMenuOpen.value = false; }
// En móvil no cargamos el video del hero (solo la imagen webp liviana)
const isMobile = ref(typeof window !== 'undefined' && window.matchMedia
    ? window.matchMedia('(max-width: 899px)').matches : false);

let io = null, revealFallback = null, rafId = null, ticking = false, mql = null, onMql = null;
const reduceMotion = typeof window !== 'undefined'
    && window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function goRegister() {
    router.visit(props.canRegister ? route('register') : route('login'));
}

function goLogin() {
    router.visit(route('login'));
}

function animateValue(setter, to, duration = 700) {
    if (reduceMotion) { setter(to); return; }
    const start = performance.now();
    const tick = (now) => {
        const p = Math.min(1, (now - start) / duration);
        const eased = p * (2 - p); // easeOutQuad
        setter(Math.round(to * eased));
        if (p < 1) requestAnimationFrame(tick);
        else setter(to);
    };
    requestAnimationFrame(tick);
}

function animateBracketScores() {
    document.querySelectorAll('[data-countup]').forEach((el) => {
        const to = parseInt(el.getAttribute('data-countup'), 10);
        if (Number.isNaN(to)) return;
        if (reduceMotion) { el.textContent = String(to); return; }
        const start = performance.now();
        const tick = (now) => {
            const p = Math.min(1, (now - start) / 650);
            el.textContent = String(Math.round(to * (p * (2 - p))));
            if (p < 1) requestAnimationFrame(tick);
            else el.textContent = String(to);
        };
        requestAnimationFrame(tick);
    });
}

function onScroll() {
    if (ticking) return;
    ticking = true;
    rafId = requestAnimationFrame(() => {
        const y = window.scrollY || 0;
        scrolled.value = y > 40;
        if (progressRef.value) {
            const max = document.documentElement.scrollHeight - window.innerHeight;
            progressRef.value.style.transform = `scaleX(${max > 0 ? y / max : 0})`;
        }
        if (!reduceMotion && window.innerWidth >= 900) {
            if (glowRef.value) glowRef.value.style.transform = `translateY(${y * 0.12}px)`;
            if (cardRef.value) cardRef.value.style.transform = `translateY(${y * -0.04}px)`;
        }
        ticking = false;
    });
}

onMounted(() => {
    // Detectar móvil (para no cargar el video del hero)
    if (window.matchMedia) {
        mql = window.matchMedia('(max-width: 899px)');
        isMobile.value = mql.matches;
        onMql = (e) => { isMobile.value = e.matches; };
        mql.addEventListener('change', onMql);
    }

    // Reveal on-scroll (one-shot)
    const reveals = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window && !reduceMotion) {
        io = new IntersectionObserver((entries) => {
            entries.forEach((e) => {
                if (!e.isIntersecting) return;
                e.target.classList.add('is-in');
                if (e.target.hasAttribute('data-bracket')) animateBracketScores();
                io.unobserve(e.target);
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -10% 0px' });
        reveals.forEach((el) => io.observe(el));
        // Fallback: nunca dejar contenido oculto
        revealFallback = setTimeout(() => {
            reveals.forEach((el) => el.classList.add('is-in'));
            animateBracketScores();
        }, 2600);
    } else {
        reveals.forEach((el) => el.classList.add('is-in'));
        animateBracketScores();
    }

    // Count-up del stat del hero (entra en load)
    animateValue((v) => (heroSeconds.value = v), 40, 800);

    // Scroll: header + progreso + parallax
    document.documentElement.style.scrollBehavior = 'smooth';
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});

onBeforeUnmount(() => {
    if (io) io.disconnect();
    if (revealFallback) clearTimeout(revealFallback);
    if (rafId) cancelAnimationFrame(rafId);
    if (mql && onMql) mql.removeEventListener('change', onMql);
    window.removeEventListener('scroll', onScroll);
    document.documentElement.style.scrollBehavior = '';
});
</script>

<template>
    <div class="fd">
        <Head :title="$t('landing.heroL1') + ' ' + $t('landing.heroL2') + ' ' + $t('landing.heroL3')" />

        <div ref="progressRef" class="progress" aria-hidden="true"></div>

        <!-- HEADER -->
        <header class="hdr" :class="{ scrolled }">
            <a href="#top" class="logo" aria-label="FIFARDOS inicio"><img src="/brand/logo-horizontal-dark.png" alt="FIFARDOS" /></a>
            <nav class="nav">
                <a href="#como">{{ $t('landing.navComoVa') }}</a>
                <a href="#modos">{{ $t('landing.navModos') }}</a>
                <a href="#jugar">{{ $t('landing.navGame') }}</a>
                <a href="/familia">{{ $t('landing.navFamily') }}</a>
                <a href="#tabla">{{ $t('landing.navEnVivo') }}</a>
                <a href="#faq">{{ $t('landing.navFaq') }}</a>
            </nav>
            <div class="nav-cta-group">
                <LanguageSwitcher />
                <button v-if="canLogin" class="btn btn-ghost-nav" @click="goLogin">{{ $t('common.login') }}</button>
                <button class="btn btn-solid btn-nav" @click="goRegister">{{ $t('common.createTournament') }}</button>
            </div>
            <button class="burger" :class="{ open: mobileMenuOpen }" @click="mobileMenuOpen = !mobileMenuOpen" :aria-label="$t('landing.menu')" :aria-expanded="mobileMenuOpen">
                <span></span><span></span><span></span>
            </button>
        </header>

        <!-- Menú mobile (hamburguesa) -->
        <Teleport to="body">
            <div v-if="mobileMenuOpen" class="mm-overlay" @click.self="closeMenu">
                <nav class="mm-panel">
                    <a href="#como" @click="closeMenu">{{ $t('landing.navComoVa') }}</a>
                    <a href="#modos" @click="closeMenu">{{ $t('landing.navModos') }}</a>
                    <a href="#jugar" @click="closeMenu">{{ $t('landing.navGame') }}</a>
                    <a href="/familia" @click="closeMenu">{{ $t('landing.navFamily') }}</a>
                    <a href="#tabla" @click="closeMenu">{{ $t('landing.navEnVivo') }}</a>
                    <a href="#faq" @click="closeMenu">{{ $t('landing.navFaq') }}</a>
                    <div class="mm-actions">
                        <button v-if="canLogin" class="btn btn-outline btn-mm" @click="closeMenu(); goLogin()">{{ $t('common.login') }}</button>
                        <button class="btn btn-solid btn-mm" @click="closeMenu(); goRegister()">{{ $t('common.createTournament') }}</button>
                    </div>
                    <div class="mm-lang"><LanguageSwitcher /></div>
                </nav>
            </div>
        </Teleport>

        <span id="top"></span>

        <!-- HERO -->
        <section class="hero">
            <video v-if="!isMobile && !reduceMotion" class="hero-video" autoplay muted loop playsinline preload="metadata" poster="/header.webp" aria-hidden="true">
                <source src="/header.mp4" type="video/mp4" />
            </video>
            <img v-else class="hero-video" src="/header.webp" alt="" aria-hidden="true" />
            <div class="hero-video-overlay" aria-hidden="true"></div>
            <div ref="glowRef" class="glow glow-hero" aria-hidden="true"></div>
            <div class="wrap hero-grid">
                <div class="hero-copy">
                    <span class="badge anim" style="--d:0ms"><i class="dot dot-orange"></i>{{ $t('landing.badge') }}</span>
                    <h1 class="anim" style="--d:70ms">{{ $t('landing.heroL1') }}<br /><span class="accent">{{ $t('landing.heroL2') }}</span><br />{{ $t('landing.heroL3') }}</h1>
                    <p class="lead anim" style="--d:140ms">{{ $t('landing.lead') }}</p>
                    <div class="hero-btns anim" style="--d:210ms">
                        <button class="btn btn-solid btn-hero" @click="goRegister">{{ $t('landing.ctaArm') }} <span aria-hidden="true">→</span></button>
                        <button class="btn btn-outline btn-hero" @click="goLogin">{{ $t('landing.ctaLogin') }}</button>
                    </div>
                    <div class="stats anim" style="--d:280ms">
                        <div class="stat"><span class="num">{{ heroSeconds }} {{ $t('landing.statSeconds') }}</span><span class="lbl">{{ $t('landing.statSecondsLbl') }}</span></div>
                        <div class="stat"><span class="num">{{ $t('landing.stat2') }}</span><span class="lbl">{{ $t('landing.stat2Lbl') }}</span></div>
                        <div class="stat"><span class="num">{{ $t('landing.stat3') }}</span><span class="lbl">{{ $t('landing.stat3Lbl') }}</span></div>
                    </div>
                </div>

                <!-- Card Tabla en vivo -->
                <div ref="cardRef" id="tabla" class="live-card anim-card" style="--d:200ms">
                    <div class="live-head">
                        <span class="live-tag"><i class="dot dot-lime"></i>{{ $t('landing.liveTag') }}</span>
                        <span class="live-meta">{{ $t('landing.liveMeta') }}</span>
                    </div>
                    <div class="tbl">
                        <div class="tbl-h">
                            <span>#</span><span>{{ $t('landing.thPlayer') }}</span><span class="c">PJ</span><span class="c">DG</span><span class="r">PTS</span>
                        </div>
                        <div v-for="row in tableRows" :key="row.pos" class="tbl-row">
                            <span class="pos">{{ row.pos }}</span>
                            <span class="pl">
                                <span class="chip">{{ row.ini }}</span>
                                <span class="pl-txt"><span class="pl-name">{{ row.name }}</span><span class="pl-team">{{ row.team }}</span></span>
                            </span>
                            <span class="c dim">{{ row.pj }}</span>
                            <span class="c dim">{{ row.dg }}</span>
                            <span class="r pts">{{ row.pts }}</span>
                        </div>
                    </div>
                    <div class="boot">
                        <span class="boot-lbl">{{ $t('landing.boot') }}</span>
                        <span class="boot-val">{{ $t('landing.bootVal') }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- DEPORTES -->
        <section class="sec sec-alt sec-sports">
            <div class="wrap">
                <div class="sec-head" data-reveal>
                    <h2>{{ $t('landing.sportsH1') }}<br /><span class="accent">{{ $t('landing.sportsH2') }}</span></h2>
                    <p class="sec-note">{{ $t('landing.sportsNote') }}</p>
                </div>
                <div class="sports-carousel" data-reveal>
                    <SportsCarousel :items="sports" />
                </div>
            </div>
        </section>

        <!-- CÓMO FUNCIONA -->
        <section id="como" class="sec">
            <div class="wrap">
                <div class="sec-head" data-reveal>
                    <h2>{{ $t('landing.howH1') }}<br /><span class="accent">{{ $t('landing.howH2') }}</span></h2>
                    <p class="sec-note">{{ $t('landing.howNote') }}</p>
                </div>
                <div class="steps">
                    <div v-for="(s, i) in steps" :key="s.n" class="step" data-reveal :style="{ '--reveal-delay': (i * 90) + 'ms' }">
                        <span class="numeral">{{ s.n }}</span>
                        <h3>{{ s.t }}</h3>
                        <p>{{ s.d }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- MINIJUEGO DE PENALES (3D · lazy-load) -->
        <PenaltyArena />

        <!-- FEATURES -->
        <section id="modos" class="sec sec-alt">
            <div class="wrap">
                <h2 class="feat-h2" data-reveal>{{ $t('landing.featH1') }}<br /><span class="accent">{{ $t('landing.featH2') }}</span></h2>
                <div class="feats">
                    <div v-for="(f, i) in features" :key="f.t" class="feat" data-reveal
                         :style="{ '--reveal-delay': ((Math.floor(i / 3) * 120) + ((i % 3) * 80)) + 'ms' }">
                        <span class="feat-tag">{{ f.tag }}</span>
                        <h3>{{ f.t }}</h3>
                        <p>{{ f.d }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- BRACKET -->
        <section class="sec">
            <div class="wrap br-grid">
                <div class="br-copy" data-reveal>
                    <span class="eyebrow lime">{{ $t('landing.brKicker') }}</span>
                    <h2 class="br-h2">{{ $t('landing.brH1') }}<br /><span class="accent">{{ $t('landing.brH2') }}</span></h2>
                    <p>{{ $t('landing.brP') }}</p>
                    <button class="link-btn" @click="goRegister">{{ $t('landing.brLink') }}</button>
                </div>
                <div class="br-card" data-reveal data-bracket>
                    <div class="br-col">
                        <div v-for="(m, i) in bracket.quarters" :key="'q'+i" class="br-match" :style="{ '--reveal-delay': (i * 60) + 'ms' }">
                            <span class="br-name">{{ m.name }}</span>
                            <span class="br-score" :data-countup="m.score">0</span>
                        </div>
                    </div>
                    <div class="br-col br-col-semi">
                        <div v-for="(m, i) in bracket.semis" :key="'s'+i" class="br-match br-match-semi">
                            <span class="br-name">{{ m.name }}</span>
                            <span class="br-score" :data-countup="m.score">0</span>
                        </div>
                    </div>
                    <div class="br-col br-col-final">
                        <div class="br-champ">
                            <span class="br-champ-lbl">{{ $t('landing.champion') }}</span>
                            <span class="br-champ-name">{{ bracket.champion }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- QUOTES -->
        <section class="sec sec-alt">
            <div class="wrap quotes">
                <div v-for="(q, i) in quotes" :key="i" class="quote" data-reveal :style="{ '--reveal-delay': (i * 110) + 'ms' }">
                    <p class="quote-txt">“{{ q.q }}”</p>
                    <span class="quote-at">{{ q.a }}</span>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="sec">
            <div class="wrap wrap-narrow">
                <h2 class="faq-h2" data-reveal>{{ $t('landing.faqH1') }}<br /><span class="accent">{{ $t('landing.faqH2') }}</span></h2>
                <div class="faq">
                    <div v-for="(f, i) in faqs" :key="i" class="faq-row" data-reveal :style="{ '--reveal-delay': (i * 60) + 'ms' }">
                        <div class="faq-q">{{ f.q }}</div>
                        <div class="faq-a">{{ f.a }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA FINAL -->
        <section id="crear" class="cta">
            <div class="glow glow-cta" aria-hidden="true"></div>
            <div class="wrap wrap-cta" data-reveal>
                <h2>{{ $t('landing.ctaH1') }}<br /><span class="accent">{{ $t('landing.ctaH2') }}</span></h2>
                <p>{{ $t('landing.ctaP') }}</p>
                <button class="btn btn-solid btn-cta" @click="goRegister">{{ $t('landing.ctaBtn') }}</button>
                <span class="cta-sub">{{ $t('landing.ctaSub') }}</span>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="foot">
            <a href="#top" class="logo foot-logo"><img src="/brand/logo-horizontal-dark.png" alt="FIFARDOS" /></a>
            <p class="foot-note">{{ $t('landing.footNote') }}</p>
            <div class="foot-links">
                <button type="button" class="foot-mcp" @click="showMcp = true">
                    <span class="mcp-dot"></span> {{ $t('landing.footMcp') }}
                </button>
            </div>
        </footer>

        <!-- MODAL: Integración MCP -->
        <Teleport to="body">
            <div v-if="showMcp" class="mcp-overlay" @click.self="showMcp = false">
                <div class="mcp-modal">
                    <div class="mcp-accent"></div>
                    <button class="mcp-close" @click="showMcp = false" :aria-label="$t('common.close')">✕</button>
                    <div class="mcp-body">
                        <span class="mcp-eyebrow">{{ $t('mcp.eyebrow') }}</span>
                        <h3 class="mcp-title">{{ $t('mcp.title') }}</h3>
                        <p class="mcp-lead">{{ $t('mcp.lead') }}</p>

                        <div class="mcp-steps">
                            <span>1 · {{ $t('mcp.step1') }}</span>
                            <span>2 · {{ $t('mcp.step2') }}</span>
                            <span>3 · {{ $t('mcp.step3') }}</span>
                        </div>

                        <div class="mcp-tabs">
                            <button :class="{ active: mcpTab === 'claude' }" @click="mcpTab = 'claude'">Claude</button>
                            <button :class="{ active: mcpTab === 'gpt' }" @click="mcpTab = 'gpt'">ChatGPT</button>
                            <button :class="{ active: mcpTab === 'gemini' }" @click="mcpTab = 'gemini'">Gemini</button>
                        </div>

                        <div v-if="mcpTab === 'claude'" class="mcp-pane">
                            <p class="mcp-note">{{ $t('mcp.noteClaude') }}</p>
                            <pre class="mcp-code">{
  "mcpServers": {
    "fifardos": {
      "type": "http",
      "url": "https://fifardos.com/mcp",
      "headers": {
        "Authorization": "Bearer 1|tu_token"
      }
    }
  }
}</pre>
                        </div>
                        <div v-else-if="mcpTab === 'gpt'" class="mcp-pane">
                            <p class="mcp-note">{{ $t('mcp.noteGpt') }}</p>
                            <pre class="mcp-code">URL:     https://fifardos.com/mcp
Header:  Authorization: Bearer 1|tu_token

# Agregalo como conector MCP remoto (Streamable HTTP)</pre>
                        </div>
                        <div v-else class="mcp-pane">
                            <p class="mcp-note">{{ $t('mcp.noteGemini') }}</p>
                            <pre class="mcp-code">GET  https://fifardos.com/api/agent/schema
Authorization: Bearer 1|tu_token

# El schema describe todas las herramientas para el modelo</pre>
                        </div>

                        <p class="mcp-tools">{{ $t('mcp.tools') }}</p>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.fd {
    --bg-base: #08080a;
    --bg-alt: #0b0b0d;
    --bg-card: #0e0e11;
    --bg-card-2: #131317;
    --bg-card-hover: #0f0d0c;
    --accent: #ff5f00;
    --accent-hover: #ff7a26;
    --accent-soft: #ff8a3d;
    --lime: #b6ff2e;
    --tp: #f2f2f0;
    --ts: #a8a8a3;
    --tm: #8f8f8b;
    --td: #7a7a76;
    --tdd: #6d6d69;
    --ttick: #5c5c58;
    --hair: rgba(255, 255, 255, .08);
    --bcard: rgba(255, 255, 255, .1);
    --f-anton: 'Anton', Impact, sans-serif;
    --f-barlow: 'Barlow Condensed', sans-serif;
    --f-body: 'Chakra Petch', system-ui, sans-serif;

    background: var(--bg-base);
    color: var(--tp);
    font-family: var(--f-body);
    overflow-x: clip;
}
.fd * { box-sizing: border-box; }
.fd ::selection { background: var(--accent); color: var(--bg-base); }
.fd p { text-wrap: pretty; }
.fd h1, .fd h2 { text-wrap: balance; }

.wrap { max-width: 1240px; margin: 0 auto; padding-left: 24px; padding-right: 24px; }
.wrap-narrow { max-width: 900px; }
.wrap-cta { max-width: 1000px; }

.accent { color: var(--accent); }
.dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.dot-orange { background: var(--accent); animation: fd-pulse 1.6s infinite; }
.dot-lime { background: var(--lime); animation: fd-pulse 1.4s infinite; }

/* Progress bar */
.progress { position: fixed; top: 0; left: 0; height: 2px; width: 100%; background: var(--accent); transform: scaleX(0); transform-origin: left; z-index: 60; }

/* Botones */
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 9px; cursor: pointer; border: none; font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; letter-spacing: .08em; text-decoration: none; transition: border-color .2s ease, background-color .2s ease, color .15s ease; }
.btn-solid { background: var(--accent); color: var(--bg-base); }
.btn-solid:hover { background: var(--accent-hover); }
.btn-outline { background: transparent; color: var(--tp); border: 1px solid rgba(255, 255, 255, .18); }
.btn-outline:hover { border-color: var(--accent); color: var(--accent-soft); }
.btn-nav { font-size: 16px; letter-spacing: .1em; padding: 10px 20px 9px; clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px); }
.nav-cta-group { display: flex; align-items: center; gap: 10px; }
.btn-ghost-nav { background: transparent; border: 1px solid rgba(255,255,255,.18); color: var(--tp); font-family: var(--f-barlow); font-weight: 700; text-transform: uppercase; letter-spacing: .1em; font-size: 15px; padding: 9px 16px; cursor: pointer; transition: border-color .2s ease, color .2s ease; }
.btn-ghost-nav:hover { border-color: var(--accent); color: var(--accent-soft); }
.btn-hero { font-size: 21px; padding: 15px 28px 13px; clip-path: polygon(14px 0, 100% 0, 100% calc(100% - 14px), calc(100% - 14px) 100%, 0 100%, 0 14px); }
.btn-cta { font-size: 24px; padding: 18px 44px 16px; clip-path: polygon(16px 0, 100% 0, 100% calc(100% - 16px), calc(100% - 16px) 100%, 0 100%, 0 16px); }

/* Header */
.hdr { position: sticky; top: 0; z-index: 40; display: flex; align-items: center; gap: 32px; padding: 14px 24px; background: rgba(8, 8, 10, .82); backdrop-filter: blur(14px); border-bottom: 1px solid var(--hair); transition: background-color .2s ease, box-shadow .2s ease; }
.hdr.scrolled { background: rgba(8, 8, 10, .94); box-shadow: 0 10px 30px -18px rgba(0, 0, 0, .9); }
.logo { display: inline-flex; align-items: center; text-decoration: none; }
.logo img { height: 46px; width: auto; display: block; }
.nav { margin-left: auto; display: flex; gap: 26px; }
.nav a { color: #9a9a96; text-decoration: none; font-family: var(--f-body); font-weight: 600; font-size: 13px; letter-spacing: .12em; text-transform: uppercase; transition: color .15s ease; }
.nav a:hover { color: var(--tp); }

/* Botón hamburguesa (solo mobile) */
.burger { display: none; margin-left: auto; flex-direction: column; justify-content: center; gap: 5px; width: 44px; height: 40px; padding: 8px; background: transparent; border: 1px solid rgba(255,255,255,.18); cursor: pointer; }
.burger span { display: block; height: 2px; width: 100%; background: var(--tp); transition: transform .25s ease, opacity .2s ease; }
.burger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.burger.open span:nth-child(2) { opacity: 0; }
.burger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* Hero */
.hero { position: relative; }
.hero-grid { display: grid; grid-template-columns: 1.15fr .85fr; gap: 56px; align-items: center; padding: 76px 24px 40px; }
.glow { position: absolute; pointer-events: none; z-index: 0; }
.glow-hero { width: 620px; height: 620px; top: -120px; left: -160px; background: radial-gradient(circle, rgba(255, 95, 0, .16), transparent 62%); }
.hero-video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0; opacity: .32; pointer-events: none; }
.hero-video-overlay { position: absolute; inset: 0; z-index: 0; pointer-events: none;
    background: linear-gradient(180deg, rgba(8,8,10,.72), rgba(8,8,10,.62) 45%, var(--bg-base) 100%); }
.hero-copy { position: relative; z-index: 1; }
.badge { display: inline-flex; align-items: center; gap: 8px; border: 1px solid rgba(255, 95, 0, .45); color: var(--accent-soft); font-weight: 700; font-size: 12px; letter-spacing: .18em; text-transform: uppercase; padding: 7px 13px; margin-bottom: 26px; }
.hero h1 { font-family: var(--f-anton); font-size: 84px; line-height: .89; letter-spacing: -1.5px; text-transform: uppercase; margin: 0 0 22px; }
.lead { color: var(--ts); font-size: 18px; line-height: 1.55; max-width: 480px; margin: 0 0 30px; }
.hero-btns { display: flex; flex-wrap: wrap; gap: 14px; }
.stats { margin-top: 38px; padding-top: 26px; border-top: 1px solid var(--hair); display: flex; gap: 30px; flex-wrap: wrap; }
.stat .num { display: block; font-family: var(--f-anton); font-size: 30px; color: var(--accent); line-height: 1; }
.stat .lbl { display: block; margin-top: 5px; font-size: 12px; letter-spacing: .14em; text-transform: uppercase; color: var(--td); }

/* Live card */
.live-card { position: relative; z-index: 1; background: var(--bg-card); border: 1px solid var(--bcard); padding: 18px; box-shadow: 0 40px 90px -30px rgba(0, 0, 0, .9); }
.live-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.live-tag { display: inline-flex; align-items: center; gap: 8px; color: var(--lime); font-size: 11px; letter-spacing: .2em; text-transform: uppercase; font-weight: 700; }
.live-meta { color: var(--tdd); font-size: 12px; }
.tbl-h, .tbl-row { display: grid; grid-template-columns: 26px 1fr 34px 34px 40px; gap: 0 8px; align-items: center; }
.tbl-h { padding: 0 8px 8px; font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--tdd); }
.tbl-h .c, .tbl-row .c { text-align: center; }
.tbl-h .r, .tbl-row .r { text-align: right; }
.tbl-row { padding: 11px 8px; border-top: 1px solid rgba(255, 255, 255, .06); }
.tbl-row .pos { font-family: var(--f-anton); font-size: 16px; color: var(--accent); }
.tbl-row .pl { display: flex; align-items: center; gap: 9px; min-width: 0; }
.chip { width: 24px; height: 24px; background: rgba(255, 255, 255, .07); display: inline-flex; align-items: center; justify-content: center; font-family: var(--f-anton); font-size: 11px; color: #cfcfca; flex-shrink: 0; }
.pl-txt { min-width: 0; display: flex; flex-direction: column; }
.pl-name { font-size: 15px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pl-team { font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--tdd); }
.tbl-row .dim { color: #9a9a96; font-size: 14px; }
.tbl-row .pts { font-family: var(--f-anton); font-size: 17px; }
.boot { margin-top: 16px; padding: 13px 14px; background: rgba(255, 95, 0, .09); border-left: 3px solid var(--accent); display: flex; align-items: center; justify-content: space-between; }
.boot-lbl { font-family: var(--f-anton); font-size: 13px; letter-spacing: .14em; text-transform: uppercase; color: var(--accent-soft); }
.boot-val { font-size: 15px; font-weight: 700; }

/* Sections */
.sec { padding: 92px 0; }
.sec-alt { background: var(--bg-alt); border-top: 1px solid var(--hair); border-bottom: 1px solid var(--hair); }
.sec h2, .cta h2 { font-family: var(--f-anton); text-transform: uppercase; margin: 0; }
.sec-head { display: flex; align-items: flex-end; gap: 24px; margin-bottom: 44px; }
.sec-head h2 { font-size: 52px; line-height: .95; letter-spacing: -.5px; }
.sec-note { margin-left: auto; max-width: 330px; text-align: right; color: var(--tm); font-size: 16px; }

.steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.step { background: var(--bg-card); border: 1px solid var(--bcard); padding: 24px 20px 26px; transition: border-color .2s ease; }
.step:hover { border-color: rgba(255, 95, 0, .5); }
.numeral { display: block; font-family: var(--f-anton); font-size: 46px; line-height: 1; color: rgba(255, 255, 255, .13); margin-bottom: 14px; }
.step h3 { font-family: var(--f-barlow); font-weight: 700; font-size: 23px; letter-spacing: .04em; text-transform: uppercase; margin: 0 0 8px; }
.step p { color: var(--tm); font-size: 15px; line-height: 1.5; margin: 0; }

.feat-h2 { font-size: 52px; line-height: .95; letter-spacing: -.5px; margin-bottom: 44px; }
.feats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.feat { background: var(--bg-base); border: 1px solid var(--bcard); padding: 26px 22px 28px; transition: border-color .2s ease, background-color .2s ease; }
.feat:hover { border-color: rgba(255, 95, 0, .5); background: var(--bg-card-hover); }
.feat-tag { display: block; font-family: var(--f-anton); font-size: 12px; letter-spacing: .2em; text-transform: uppercase; color: var(--accent); margin-bottom: 14px; }
.feat h3 { font-family: var(--f-barlow); font-weight: 700; font-size: 27px; line-height: 1.05; letter-spacing: .02em; text-transform: uppercase; margin: 0 0 8px; }
.feat p { color: var(--tm); font-size: 16px; line-height: 1.5; margin: 0; }

/* Sports carousel */
.sec-sports .sec-head { margin-bottom: 32px; }

/* Bracket */
.br-grid { display: grid; grid-template-columns: .8fr 1.2fr; gap: 48px; align-items: center; }
.eyebrow { font-family: var(--f-anton); font-size: 12px; letter-spacing: .2em; text-transform: uppercase; color: var(--accent); }
.eyebrow.lime { color: var(--lime); }
.br-h2 { font-size: 48px; line-height: 44px; letter-spacing: -.5px; margin: 12px 0 16px; }
.br-copy p { color: var(--tm); font-size: 17px; line-height: 1.55; margin: 0 0 22px; }
.link-btn { background: none; border: none; cursor: pointer; color: var(--tp); font-family: var(--f-barlow); font-weight: 700; font-size: 19px; letter-spacing: .1em; text-transform: uppercase; border-bottom: 2px solid var(--accent); padding: 0 0 3px; }
.link-btn:hover { color: var(--accent-soft); }
.br-card { background: var(--bg-card); border: 1px solid var(--bcard); padding: 28px 24px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; align-items: center; }
.br-col { display: flex; flex-direction: column; gap: 12px; }
.br-col-semi { gap: 34px; }
.br-col-final { align-items: center; }
.br-match { display: flex; align-items: center; justify-content: space-between; gap: 10px; background: var(--bg-card-2); border: 1px solid var(--bcard); padding: 9px 11px; }
.br-match-semi { border-color: rgba(255, 95, 0, .4); padding: 11px; font-weight: 700; }
.br-name { font-size: 14px; font-weight: 600; }
.br-match-semi .br-name { font-weight: 700; }
.br-score { font-family: var(--f-anton); color: var(--accent); }
.br-champ { width: 100%; text-align: center; border: 1px solid var(--accent); background: rgba(255, 95, 0, .1); padding: 18px 12px; }
.br-champ-lbl { display: block; font-size: 10px; letter-spacing: .2em; text-transform: uppercase; color: var(--accent-soft); margin-bottom: 6px; }
.br-champ-name { font-family: var(--f-anton); font-size: 22px; text-transform: uppercase; }

/* Quotes */
.quotes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.quote { padding: 26px 24px; border-left: 2px solid var(--accent); }
.quote-txt { font-size: 18px; line-height: 1.5; font-weight: 600; margin: 0 0 14px; }
.quote-at { font-size: 12px; letter-spacing: .16em; text-transform: uppercase; color: var(--td); }

/* FAQ */
.faq-h2 { font-size: 48px; line-height: 44px; letter-spacing: -.5px; margin-bottom: 24px; }
.faq-row { border-top: 1px solid var(--bcard); padding: 22px 0; display: grid; grid-template-columns: 1fr 1.3fr; gap: 28px; }
.faq-q { font-family: var(--f-barlow); font-weight: 700; font-size: 22px; letter-spacing: .03em; text-transform: uppercase; }
.faq-a { color: var(--tm); font-size: 16px; line-height: 1.55; }

/* CTA final */
.cta { position: relative; padding: 100px 24px 110px; text-align: center; overflow: hidden; }
.glow-cta { inset: 0; width: 100%; height: 100%; background: radial-gradient(ellipse at 50% 120%, rgba(255, 95, 0, .22), transparent 60%); }
.wrap-cta { position: relative; z-index: 1; margin: 0 auto; }
.cta h2 { font-size: 76px; line-height: .9; letter-spacing: -1.5px; }
.cta p { color: var(--ts); font-size: 18px; max-width: 460px; margin: 20px auto 30px; }
.cta-sub { display: block; margin-top: 18px; font-size: 12px; letter-spacing: .16em; text-transform: uppercase; color: var(--tdd); }

/* Footer */
.foot { background: var(--bg-alt); border-top: 1px solid var(--hair); padding: 30px 24px; display: flex; flex-wrap: wrap; align-items: center; gap: 20px; }
.foot-logo img { height: 26px; }
.foot-note { color: var(--tdd); font-size: 13px; margin: 0; }
.foot-links { margin-left: auto; display: flex; align-items: center; gap: 22px; flex-wrap: wrap; }
.foot-links a { color: var(--tm); text-decoration: none; font-size: 12px; letter-spacing: .14em; text-transform: uppercase; transition: color .15s ease; }
.foot-links a:hover { color: var(--accent); }
.foot-mcp { display: inline-flex; align-items: center; gap: 7px; cursor: pointer; background: none; border: 1px solid rgba(255,95,0,.35); color: var(--accent-soft); font-family: var(--f-body); font-size: 12px; letter-spacing: .14em; text-transform: uppercase; padding: 7px 12px; transition: border-color .2s, background .2s; }
.foot-mcp:hover { border-color: var(--accent); background: rgba(255,95,0,.08); }
.mcp-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--accent); box-shadow: 0 0 8px var(--accent); }

/* Scroll reveal */
[data-reveal] { opacity: 0; transform: translateY(24px); transition: opacity .6s cubic-bezier(.22,.61,.36,1), transform .6s cubic-bezier(.22,.61,.36,1); transition-delay: var(--reveal-delay, 0ms); will-change: opacity, transform; }
[data-reveal].is-in { opacity: 1; transform: none; }

/* Entrada del hero (en load) */
.anim { opacity: 0; animation: fd-in .5s ease forwards; animation-delay: var(--d, 0ms); }
.anim-card { opacity: 0; animation: fd-in-x .55s ease forwards; animation-delay: var(--d, 0ms); }

@keyframes fd-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .25; } }
@keyframes fd-in { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
@keyframes fd-in-x { from { opacity: 0; transform: translateX(28px); } to { opacity: 1; transform: none; } }

/* Anclas con header sticky */
.sec, .cta, .hero { scroll-margin-top: 80px; }
#tabla { scroll-margin-top: 90px; }

/* Responsive */
@media (max-width: 1119px) {
    .hero h1 { font-size: 64px; }
    .sec-head h2, .feat-h2 { font-size: 42px; }
    .hero-grid { grid-template-columns: 1fr; gap: 40px; }
    .live-card { max-width: 520px; }
    .steps { grid-template-columns: repeat(2, 1fr); }
    .feats { grid-template-columns: repeat(2, 1fr); }
    .br-grid { grid-template-columns: 1fr; gap: 32px; }
}
@media (max-width: 879px) {
    .nav { display: none; }
    .nav-cta-group { display: none; }
    .burger { display: flex; }
    .hdr { gap: 16px; padding: 12px 20px; }
    .btn-nav { padding: 12px 18px; }
    .hero-grid { padding: 56px 20px 32px; }
    .hero h1 { font-size: 46px; line-height: .95; }
    .cta h2 { font-size: 40px; }
    .sec-head h2, .feat-h2, .br-h2, .faq-h2 { font-size: 34px; line-height: 1; }
    .sec { padding: 60px 0; }
    .wrap { padding-left: 20px; padding-right: 20px; }
    .sec-head { flex-direction: column; align-items: flex-start; gap: 14px; }
    .sec-note { margin-left: 0; text-align: left; }
    .steps { grid-template-columns: 1fr; }
    .feats { grid-template-columns: 1fr; }
    .quotes { grid-template-columns: 1fr; }
    .faq-row { grid-template-columns: 1fr; gap: 8px; }
    .stats { gap: 20px; }
    .br-card { overflow-x: auto; }
    .br-card > * { min-width: 150px; }
    .btn-cta { width: 100%; }
    .glow-hero { left: -40%; width: 420px; height: 420px; }
}
@media (prefers-reduced-motion: reduce) {
    [data-reveal] { opacity: 1; transform: none; transition: none; }
    .anim, .anim-card { opacity: 1; animation: none; }
    .dot-orange, .dot-lime { animation: none !important; }
}
</style>

<style>
/* Modal MCP (teleportado a body → estilos globales) */
.mcp-overlay { position: fixed; inset: 0; z-index: 90; display: flex; align-items: center; justify-content: center; padding: 20px; background: rgba(0,0,0,.78); backdrop-filter: blur(6px); }
.mcp-modal { position: relative; width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto; background: #0e0e11; border: 1px solid rgba(255,255,255,.1); font-family: 'Chakra Petch', system-ui, sans-serif; color: #f2f2f0; }
.mcp-accent { height: 4px; background: linear-gradient(90deg, #ff5f00, transparent); }
.mcp-close { position: absolute; top: 12px; right: 12px; background: none; border: none; color: #8f8f8b; font-size: 16px; cursor: pointer; padding: 6px; line-height: 1; }
.mcp-close:hover { color: #fff; }
.mcp-body { padding: 26px 28px 28px; }
.mcp-eyebrow { font-family: 'Anton', sans-serif; font-size: 12px; letter-spacing: .2em; text-transform: uppercase; color: #b6ff2e; }
.mcp-title { font-family: 'Anton', sans-serif; text-transform: uppercase; font-size: 30px; letter-spacing: -.5px; margin: 8px 0 10px; }
.mcp-lead { color: #a8a8a3; font-size: 15px; line-height: 1.6; margin: 0 0 18px; }
.mcp-lead strong { color: #f2f2f0; }
.mcp-steps { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
.mcp-steps span { font-size: 13.5px; color: #8f8f8b; }
.mcp-steps b { color: #ff8a3d; }
.mcp-tabs { display: flex; gap: 6px; margin-bottom: 12px; }
.mcp-tabs button { flex: 1; cursor: pointer; background: #131317; border: 1px solid rgba(255,255,255,.1); color: #a8a8a3; font-family: 'Barlow Condensed', sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; font-size: 14px; padding: 9px; transition: all .15s ease; }
.mcp-tabs button:hover { color: #fff; }
.mcp-tabs button.active { background: rgba(255,95,0,.12); border-color: rgba(255,95,0,.5); color: #ff5f00; }
.mcp-note { font-size: 13px; color: #8f8f8b; margin: 0 0 8px; }
.mcp-code { background: #08080a; border: 1px solid rgba(255,255,255,.08); padding: 14px; font-family: 'JetBrains Mono', monospace; font-size: 12.5px; line-height: 1.55; color: #d4d4d0; white-space: pre; overflow-x: auto; margin: 0; }
.mcp-tools { margin: 16px 0 0; font-size: 13px; color: #8f8f8b; }
.mcp-tools b { color: #ff8a3d; }

/* Menú mobile (teleportado a body) */
.mm-overlay { position: fixed; inset: 0; z-index: 38; background: rgba(8,8,10,.55); backdrop-filter: blur(4px);
    /* El panel vive fuera de .fd, así que redefinimos aquí las variables de marca */
    --accent: #ff5f00; --accent-hover: #ff7a26; --bg-base: #08080a; --tp: #f2f2f0;
    --f-barlow: 'Barlow Condensed', sans-serif; }
.mm-panel { position: absolute; top: 0; left: 0; right: 0; background: #0b0b0d; border-bottom: 1px solid rgba(255,255,255,.1);
    padding: 80px 20px 24px; display: flex; flex-direction: column; gap: 2px; box-shadow: 0 24px 50px -22px rgba(0,0,0,.95); animation: mmslide .22s ease; }
.mm-panel a { color: #e8e8e4; text-decoration: none; font-family: 'Barlow Condensed', sans-serif; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; font-size: 21px; padding: 13px 6px; border-bottom: 1px solid rgba(255,255,255,.06); }
.mm-panel a:hover { color: #ff5f00; }
.mm-actions { display: flex; flex-direction: column; gap: 10px; margin-top: 18px; }
.mm-actions .btn-mm { width: 100%; font-size: 18px; padding: 14px 18px; }
.mm-lang { margin-top: 16px; display: flex; justify-content: center; }
@keyframes mmslide { from { transform: translateY(-14px); opacity: 0; } to { transform: none; opacity: 1; } }
</style>
