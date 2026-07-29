<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    tournament: Object,
    prizes: Array,
    standings: Array,
    stats: { type: Object, default: () => ({ teams: 48, matches: 104, venues: 16, fans: '1.2M' }) },
});

const particlesRef = ref(null);
const openFaq = ref(0);
let observer = null;
let revealFallback = null;

const faqs = [
    {
        q: '¿Qué es FIFARDOS?',
        a: 'Una plataforma gratuita para organizar torneos de fútbol entre amigos —de videojuego (EA Sports FC/FIFA, eFootball) o de fútbol real (fulbito, F5, F7, F11)—: fase de grupos, eliminatorias, tabla en vivo y estadísticas como el goleador.',
    },
    {
        q: '¿Cómo organizo un torneo?',
        a: 'Crea tu cuenta, pulsa "Nuevo torneo", pon el nombre, cuántas consolas tienes y la lista de jugadores. Generamos el fixture de grupos automáticamente y, al terminar, armamos las eliminatorias con los mejores.',
    },
    {
        q: '¿Es gratis?',
        a: 'Sí, totalmente. No necesitas instalar nada: funciona desde el navegador en computador y móvil, con soporte de app (PWA).',
    },
    {
        q: '¿Puede un asistente de IA armarme el torneo?',
        a: 'Sí. Con nuestro servidor MCP, asistentes como Claude, ChatGPT o Copilot pueden consultar tus torneos y crear uno nuevo por ti.',
    },
];

const steps = [
    { n: '01', t: 'Arma el torneo', d: 'Nombre, consolas o canchas y jugadores. El fixture de todos contra todos se genera solo.' },
    { n: '02', t: 'Carga los resultados', d: 'Después de cada partido cargas el marcador y la tabla se actualiza al instante.' },
    { n: '03', t: 'Corona al campeón', d: 'Al cerrar los grupos armamos las eliminatorias y avanzamos a los ganadores hasta la final.' },
];

const features = [
    { icon: 'grid', t: 'Fase de grupos automática', d: 'Round-robin equilibrado y repartido entre tus consolas.' },
    { icon: 'trophy', t: 'Eliminatorias', d: 'Octavos, cuartos, semis, final y tercer puesto con avance automático.' },
    { icon: 'chart', t: 'Tabla en vivo', d: 'Puntos, diferencia de gol y desempates calculados en tiempo real.' },
    { icon: 'ball', t: 'Goleadores', d: 'Ranking de máximos artilleros y estadísticas por jugador.' },
    { icon: 'tv', t: 'Multi-consola / cancha', d: 'Reparte los partidos entre varias TVs o canchas para que nadie espere.' },
    { icon: 'bot', t: 'API + MCP', d: 'Conecta Claude, ChatGPT o Copilot para consultar y crear torneos.' },
];

function goLogin() { router.visit(route('login')); }
function goRegister() { router.visit(props.canRegister ? route('register') : route('login')); }

onMounted(() => {
    // Partículas
    const container = particlesRef.value;
    if (container) {
        const colors = ['#ff5f00', '#ffb599', '#ffffff'];
        for (let i = 0; i < 26; i++) {
            const p = document.createElement('div');
            const size = 2 + Math.random() * 3;
            p.className = 'particle';
            p.style.width = size + 'px';
            p.style.height = size + 'px';
            p.style.background = colors[i % colors.length];
            p.style.left = (Math.random() * 100) + '%';
            p.style.animationDuration = (7 + Math.random() * 7) + 's';
            p.style.animationDelay = (Math.random() * 9) + 's';
            container.appendChild(p);
        }
    }

    // Reveal on scroll
    const reveals = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((e) => {
                    if (e.isIntersecting) {
                        e.target.classList.add('is-visible');
                        observer.unobserve(e.target);
                    }
                });
            },
            { threshold: 0.12 },
        );
        reveals.forEach((el) => observer.observe(el));
        // Fallback de seguridad: nunca dejar contenido oculto (si el usuario no hace
        // scroll o el observer no dispara, revela todo tras 1.4s).
        revealFallback = setTimeout(() => reveals.forEach((el) => el.classList.add('is-visible')), 1400);
    } else {
        reveals.forEach((el) => el.classList.add('is-visible'));
    }
});

onBeforeUnmount(() => {
    if (observer) observer.disconnect();
    if (revealFallback) clearTimeout(revealFallback);
});
</script>

<template>
    <div class="landing">
        <Head title="Organiza torneos de FIFA con tus amigos" />

        <!-- NAV -->
        <header class="nav">
            <Link href="/" class="brand" aria-label="FIFARDOS inicio">
                <img src="/brand/icon.png" alt="FIFARDOS" class="brand-mark" />
                <span class="brand-word">FIFA<span>RDOS</span></span>
            </Link>
            <nav class="nav-actions">
                <Link href="/rules" class="nav-link">Reglas</Link>
                <Link v-if="canLogin" :href="route('login')" class="nav-ghost">Iniciar sesión</Link>
                <button v-if="canRegister" @click="goRegister" class="nav-cta">Crear cuenta</button>
            </nav>
        </header>

        <!-- HERO -->
        <section class="hero">
            <div class="pitch-lines" aria-hidden="true"></div>
            <div class="glow-arc" aria-hidden="true"></div>
            <div class="particles" ref="particlesRef" aria-hidden="true"></div>

            <div class="hero-inner">
                <div class="hero-copy">
                    <span class="eyebrow">Temporada 2026 · Gratis</span>
                    <h1>
                        ARMA TU TORNEO<br />
                        Y DALE CON<br />
                        <em>LOS PANAS</em>
                    </h1>
                    <p class="lead">
                        Organiza torneos de fútbol —de consola o de cancha—, carga los resultados
                        de cada partido y mira quién es el mejor del grupo. Sin excusas, todo queda registrado.
                    </p>
                    <div class="cta-row">
                        <button class="btn-primary" @click="goRegister">
                            {{ canRegister ? 'Crear cuenta gratis' : 'Entrar' }}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </button>
                        <Link href="/rules" class="btn-ghost">Ver reglas</Link>
                    </div>
                </div>

                <div class="hero-visual" aria-hidden="true">
                    <div class="ball">
                        <svg viewBox="0 0 200 200">
                            <g fill="none" stroke="#111" stroke-width="2" opacity="0.55">
                                <circle cx="100" cy="100" r="94"/>
                                <polygon points="100,55 122,72 114,98 86,98 78,72" fill="#111"/>
                                <line x1="100" y1="55" x2="100" y2="15"/>
                                <line x1="122" y1="72" x2="158" y2="50"/>
                                <line x1="114" y1="98" x2="140" y2="130"/>
                                <line x1="86" y1="98" x2="60" y2="130"/>
                                <line x1="78" y1="72" x2="42" y2="50"/>
                                <circle cx="100" cy="18" r="12" fill="#111"/>
                                <circle cx="160" cy="52" r="12" fill="#111"/>
                                <circle cx="146" cy="132" r="12" fill="#111"/>
                                <circle cx="54" cy="132" r="12" fill="#111"/>
                                <circle cx="40" cy="52" r="12" fill="#111"/>
                            </g>
                        </svg>
                    </div>
                    <div class="trophy">
                        <svg viewBox="0 0 100 140" width="120" height="168">
                            <defs>
                                <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#ffd9a8"/>
                                    <stop offset="45%" stop-color="#ff9248"/>
                                    <stop offset="100%" stop-color="#b3560f"/>
                                </linearGradient>
                            </defs>
                            <path d="M28 8 H72 V20 C72 40 62 48 52 50 V70 H62 V84 H38 V70 H48 V50 C38 48 28 40 28 20 Z" fill="url(#g)"/>
                            <rect x="30" y="84" width="40" height="10" rx="2" fill="url(#g)"/>
                            <rect x="20" y="94" width="60" height="12" rx="3" fill="url(#g)"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- STATS -->
            <div class="stats">
                <div class="stat"><span class="num">{{ stats.teams }}</span><span class="lbl">Jugadores</span></div>
                <div class="stat"><span class="num">{{ stats.matches }}</span><span class="lbl">Partidos jugados</span></div>
                <div class="stat"><span class="num">{{ stats.venues }}</span><span class="lbl">Consolas</span></div>
                <div class="stat"><span class="num">{{ stats.fans }}</span><span class="lbl">Comunidad</span></div>
            </div>
        </section>

        <!-- CÓMO FUNCIONA -->
        <section class="section reveal">
            <div class="section-head">
                <span class="kicker">Cómo funciona</span>
                <h2>De la lista de panas al campeón en 3 pasos</h2>
            </div>
            <div class="steps">
                <div v-for="s in steps" :key="s.n" class="step-card">
                    <span class="step-n">{{ s.n }}</span>
                    <h3>{{ s.t }}</h3>
                    <p>{{ s.d }}</p>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
        <section class="section reveal">
            <div class="section-head">
                <span class="kicker">Todo incluido</span>
                <h2>Hecho para que solo te preocupes por ganar</h2>
            </div>
            <div class="features">
                <div v-for="f in features" :key="f.t" class="feat-card">
                    <div class="feat-icon">
                        <svg v-if="f.icon==='grid'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                        <svg v-else-if="f.icon==='trophy'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4z"/><path d="M17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/></svg>
                        <svg v-else-if="f.icon==='chart'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>
                        <svg v-else-if="f.icon==='ball'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7l3 2-1 4h-4l-1-4z"/></svg>
                        <svg v-else-if="f.icon==='tv'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="5" width="20" height="13" rx="2"/><path d="M8 21h8"/></svg>
                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="8" width="16" height="12" rx="3"/><path d="M12 4v4M9 14h.01M15 14h.01"/></svg>
                    </div>
                    <h3>{{ f.t }}</h3>
                    <p>{{ f.d }}</p>
                </div>
            </div>
        </section>

        <!-- TORNEO DESTACADO -->
        <section v-if="tournament" class="section reveal">
            <div class="feature-strip">
                <div class="fs-left">
                    <span class="kicker">Torneo destacado</span>
                    <h2>{{ tournament.name }}</h2>
                    <p class="fs-status">
                        <span class="dot" :style="{ background: tournament.color || '#ff5f00' }"></span>
                        {{ tournament.status === 'completed' ? 'Finalizado' : 'En curso' }}
                    </p>
                    <Link :href="route('tournaments.public.bracket', tournament.id)" class="btn-primary sm">
                        Ver bracket
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </Link>
                </div>
                <div v-if="standings && standings.length" class="fs-board">
                    <div v-for="(row, i) in standings.slice(0, 5)" :key="i" class="board-row" :class="{ leader: i === 0 }">
                        <span class="pos">{{ i + 1 }}</span>
                        <span class="pl">{{ row.player_name }}</span>
                        <span class="pts">{{ row.pts }} pts</span>
                    </div>
                </div>
                <div v-else class="fs-live">
                    <div class="live-pill"><span class="live-dot"></span> En vivo</div>
                    <p>La tabla de posiciones se va armando a medida que se cargan los resultados de cada partido.</p>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="section reveal">
            <div class="section-head">
                <span class="kicker">Preguntas frecuentes</span>
                <h2>Lo que todos preguntan</h2>
            </div>
            <div class="faq">
                <div v-for="(item, i) in faqs" :key="i" class="faq-item" :class="{ open: openFaq === i }">
                    <button class="faq-q" @click="openFaq = openFaq === i ? -1 : i">
                        <span>{{ item.q }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="faq-a"><p>{{ item.a }}</p></div>
                </div>
            </div>
        </section>

        <!-- CTA FINAL -->
        <section class="cta-band reveal">
            <h2>¿Listo para armar el torneo?</h2>
            <p>Junta a los panas, prende la consola y que empiece la liga.</p>
            <button class="btn-primary lg" @click="goRegister">
                {{ canRegister ? 'Empezar gratis' : 'Entrar' }}
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </button>
        </section>

        <!-- FOOTER -->
        <footer class="foot">
            <div class="brand">
                <img src="/brand/icon.png" alt="FIFARDOS" class="brand-mark sm" />
                <span class="brand-word">FIFA<span>RDOS</span></span>
            </div>
            <p>Organiza tus torneos de FIFA con los panas.</p>
            <div class="foot-links">
                <Link href="/rules">Reglas</Link>
                <Link v-if="canLogin" :href="route('login')">Iniciar sesión</Link>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.landing {
    --bg: #0a0a0c;
    --surface: #141416;
    --surface-2: #1b1b1e;
    --line: rgba(255, 255, 255, 0.08);
    --brand: #ff5f00;
    --brand-2: #ff9248;
    --peach: #ffb599;
    --text: #f4f4f6;
    --muted: #9a9aa2;
    --font-display: 'Barlow Condensed', 'Arial Narrow', sans-serif;
    --font-body: 'Hanken Grotesk', system-ui, sans-serif;
    --font-mono: 'JetBrains Mono', monospace;

    background: var(--bg);
    color: var(--text);
    font-family: var(--font-body);
    overflow-x: clip;
    width: 100%;
    max-width: 100vw;
    min-height: 100vh;
}
.landing *, .landing *::before, .landing *::after { box-sizing: border-box; }

/* NAV */
.nav {
    position: sticky;
    top: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px clamp(20px, 5vw, 64px);
    background: rgba(10, 10, 12, 0.72);
    backdrop-filter: blur(14px);
    border-bottom: 1px solid var(--line);
}
.brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--text); }
.brand-mark { width: 36px; height: 36px; border-radius: 9px; }
.brand-mark.sm { width: 30px; height: 30px; }
.brand-word { font-family: var(--font-display); font-weight: 800; font-size: 22px; letter-spacing: 1px; }
.brand-word span { color: var(--brand); }
.nav-actions { display: flex; align-items: center; gap: 10px; }
.nav-link { color: var(--muted); text-decoration: none; font-weight: 600; font-size: 14px; padding: 8px 12px; transition: color .2s; }
.nav-link:hover { color: var(--text); }
.nav-ghost {
    color: var(--text); text-decoration: none; font-weight: 600; font-size: 14px;
    padding: 9px 18px; border: 1px solid var(--line); border-radius: 999px; transition: border-color .2s, background .2s;
}
.nav-ghost:hover { border-color: var(--brand); background: rgba(255, 95, 0, 0.08); }
.nav-cta {
    background: var(--brand); color: #180a02; border: none; cursor: pointer;
    font-weight: 800; font-size: 14px; padding: 10px 20px; border-radius: 999px; transition: transform .15s, box-shadow .2s;
    box-shadow: 0 6px 18px rgba(255, 95, 0, 0.28);
}
.nav-cta:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(255, 95, 0, 0.4); }

/* HERO */
.hero { position: relative; padding: clamp(48px, 8vw, 96px) clamp(20px, 5vw, 64px) 0; overflow: hidden; }
.pitch-lines {
    position: absolute; inset: 0;
    background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
    background-size: 64px 64px;
    mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, #000 40%, transparent 100%);
    z-index: 0;
}
.glow-arc {
    position: absolute; top: -10%; right: 4%; width: min(620px, 60vw); height: min(620px, 60vw);
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 95, 0, 0.16), transparent 62%);
    filter: blur(10px); z-index: 0; animation: pulse 7s ease-in-out infinite;
}
@keyframes pulse { 0%,100% { opacity: .7; } 50% { opacity: 1; } }
.particles { position: absolute; inset: 0; z-index: 1; pointer-events: none; }
:deep(.particle) { position: absolute; top: -12px; border-radius: 50%; opacity: .5; animation: fall linear infinite; }
@keyframes fall { from { transform: translateY(-20px); opacity: 0; } 8% { opacity: .6; } to { transform: translateY(105vh); opacity: 0; } }

.hero-inner {
    position: relative; z-index: 2; display: grid; grid-template-columns: 1.1fr 0.9fr;
    align-items: center; gap: 32px; max-width: 1200px; margin: 0 auto; min-height: min(64vh, 560px);
}
.eyebrow {
    display: inline-block; font-family: var(--font-mono); font-size: 12px; letter-spacing: 2px; text-transform: uppercase;
    color: var(--brand-2); background: rgba(255, 95, 0, 0.1); border: 1px solid rgba(255, 95, 0, 0.25);
    padding: 6px 14px; border-radius: 999px; margin-bottom: 22px;
    animation: fadeUp .6s .05s both;
}
.hero-copy h1 {
    font-family: var(--font-display); font-weight: 800; text-transform: uppercase;
    font-size: clamp(42px, 6.4vw, 82px); line-height: 0.94; letter-spacing: -0.5px; margin: 0 0 20px;
    animation: fadeUp .6s .12s both;
}
.hero-copy h1 em {
    font-style: normal;
    background: linear-gradient(100deg, var(--brand), var(--peach));
    -webkit-background-clip: text; background-clip: text; color: transparent;
}
.lead { font-size: clamp(15px, 1.4vw, 18px); color: var(--muted); line-height: 1.65; max-width: 480px; margin: 0 0 30px; animation: fadeUp .6s .2s both; }
.cta-row { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; animation: fadeUp .6s .28s both; }

.btn-primary {
    display: inline-flex; align-items: center; gap: 9px; cursor: pointer; text-decoration: none;
    background: linear-gradient(135deg, var(--brand-2), var(--brand)); color: #180a02;
    border: none; font-family: var(--font-body); font-weight: 800; font-size: 15px;
    padding: 15px 28px; border-radius: 12px; box-shadow: 0 10px 26px rgba(255, 95, 0, 0.3);
    transition: transform .16s ease, box-shadow .2s ease;
}
.btn-primary svg { width: 18px; height: 18px; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 16px 34px rgba(255, 95, 0, 0.42); }
.btn-primary:active { transform: translateY(0) scale(.98); }
.btn-primary.sm { padding: 11px 20px; font-size: 14px; }
.btn-primary.lg { padding: 17px 34px; font-size: 16px; }
.btn-ghost {
    display: inline-flex; align-items: center; text-decoration: none; color: var(--text);
    border: 1px solid var(--line); font-weight: 600; font-size: 15px; padding: 15px 24px; border-radius: 12px; transition: border-color .2s, background .2s;
}
.btn-ghost:hover { border-color: var(--brand); background: rgba(255, 95, 0, 0.06); }

/* Hero visual */
.hero-visual { position: relative; height: 100%; min-height: 360px; }
.ball {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    width: min(360px, 34vw); height: min(360px, 34vw); border-radius: 50%;
    background: radial-gradient(circle at 32% 28%, #fff 0%, #e8e8e8 18%, #b9b9b9 42%, #4a4a4a 78%, #171717 100%);
    box-shadow: inset -28px -28px 56px rgba(0,0,0,.55), inset 18px 18px 36px rgba(255,255,255,.14),
                0 30px 70px rgba(0,0,0,.55), 0 0 90px rgba(255, 95, 0, 0.22);
    animation: spin 7s linear infinite, floaty 5.5s ease-in-out infinite;
}
.ball svg { position: absolute; inset: 0; width: 100%; height: 100%; mix-blend-mode: multiply; opacity: .85; }
.trophy {
    position: absolute; bottom: 6%; right: 8%; z-index: 3;
    filter: drop-shadow(0 18px 26px rgba(0,0,0,.5)); animation: floatTrophy 6s ease-in-out infinite;
}
@keyframes spin { to { transform: translate(-50%, -50%) rotate(360deg); } }
@keyframes floaty { 0%,100% { margin-top: 0; } 50% { margin-top: -16px; } }
@keyframes floatTrophy { 0%,100% { transform: translateY(0) rotate(-3deg); } 50% { transform: translateY(-12px) rotate(3deg); } }

/* STATS */
.stats {
    position: relative; z-index: 2; display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;
    max-width: 1200px; margin: clamp(40px, 6vw, 72px) auto 0; padding-bottom: clamp(40px, 6vw, 72px);
}
.stat {
    background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 22px 24px;
    transition: transform .2s, border-color .2s;
}
.stat:hover { transform: translateY(-4px); border-color: rgba(255, 95, 0, 0.4); }
.stat .num { display: block; font-family: var(--font-display); font-weight: 800; font-size: clamp(28px, 3.4vw, 40px); background: linear-gradient(100deg, var(--brand), var(--peach)); -webkit-background-clip: text; background-clip: text; color: transparent; line-height: 1; }
.stat .lbl { display: block; margin-top: 6px; font-size: 13px; color: var(--muted); }

/* SECTIONS */
.section { max-width: 1120px; margin: 0 auto; padding: clamp(56px, 8vw, 104px) clamp(20px, 5vw, 40px); }
.section-head { text-align: center; margin-bottom: clamp(36px, 5vw, 56px); }
.kicker { font-family: var(--font-mono); font-size: 12px; letter-spacing: 2.5px; text-transform: uppercase; color: var(--brand); }
.section-head h2 { font-family: var(--font-display); font-weight: 800; text-transform: uppercase; font-size: clamp(28px, 4vw, 48px); line-height: 1.05; margin: 12px 0 0; letter-spacing: -.5px; }

.steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
.step-card { position: relative; background: var(--surface); border: 1px solid var(--line); border-radius: 18px; padding: 30px 26px; overflow: hidden; }
.step-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; background: linear-gradient(90deg, var(--brand), transparent); }
.step-n { font-family: var(--font-display); font-weight: 800; font-size: 40px; color: rgba(255, 95, 0, 0.28); line-height: 1; }
.step-card h3 { font-family: var(--font-display); text-transform: uppercase; font-weight: 700; font-size: 22px; margin: 12px 0 8px; letter-spacing: .3px; }
.step-card p { color: var(--muted); font-size: 15px; line-height: 1.6; margin: 0; }

.features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
.feat-card { background: var(--surface); border: 1px solid var(--line); border-radius: 18px; padding: 26px 24px; transition: transform .2s, border-color .2s, background .2s; }
.feat-card:hover { transform: translateY(-5px); border-color: rgba(255, 95, 0, 0.4); background: var(--surface-2); }
.feat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--brand); background: rgba(255, 95, 0, 0.1); border: 1px solid rgba(255, 95, 0, 0.2); margin-bottom: 16px; }
.feat-icon svg { width: 22px; height: 22px; }
.feat-card h3 { font-family: var(--font-display); text-transform: uppercase; font-weight: 700; font-size: 20px; margin: 0 0 7px; letter-spacing: .3px; }
.feat-card p { color: var(--muted); font-size: 14.5px; line-height: 1.6; margin: 0; }

/* TORNEO DESTACADO */
.feature-strip {
    display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: center;
    background: linear-gradient(140deg, var(--surface), var(--bg)); border: 1px solid var(--line); border-radius: 24px; padding: clamp(28px, 4vw, 44px);
}
.fs-left h2 { font-family: var(--font-display); text-transform: uppercase; font-weight: 800; font-size: clamp(26px, 3.4vw, 40px); margin: 12px 0 10px; }
.fs-status { display: flex; align-items: center; gap: 9px; color: var(--muted); font-size: 14px; margin: 0 0 22px; }
.fs-status .dot { width: 9px; height: 9px; border-radius: 50%; box-shadow: 0 0 10px currentColor; }
.fs-board { display: flex; flex-direction: column; gap: 8px; }
.board-row { display: grid; grid-template-columns: 28px 1fr auto; align-items: center; gap: 12px; background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 12px 16px; }
.board-row.leader { border-color: rgba(255, 95, 0, 0.5); background: rgba(255, 95, 0, 0.07); }
.board-row .pos { font-family: var(--font-display); font-weight: 800; color: var(--brand); font-size: 18px; }
.board-row .pl { font-weight: 600; font-size: 15px; }
.board-row .pts { font-family: var(--font-mono); font-size: 13px; color: var(--muted); }
.fs-live { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; padding: 26px; display: flex; flex-direction: column; gap: 14px; justify-content: center; }
.live-pill { display: inline-flex; align-items: center; gap: 8px; align-self: flex-start; font-family: var(--font-mono); font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--brand-2); background: rgba(255,95,0,.1); border: 1px solid rgba(255,95,0,.25); padding: 6px 14px; border-radius: 999px; }
.live-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--brand); box-shadow: 0 0 10px var(--brand); animation: pulse 1.8s ease-in-out infinite; }
.fs-live p { color: var(--muted); font-size: 15px; line-height: 1.6; margin: 0; }

/* FAQ */
.faq { max-width: 760px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }
.faq-item { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; overflow: hidden; transition: border-color .2s; }
.faq-item.open { border-color: rgba(255, 95, 0, 0.4); }
.faq-q { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 16px; background: none; border: none; cursor: pointer; color: var(--text); font-family: var(--font-body); font-weight: 700; font-size: 16px; text-align: left; padding: 18px 20px; }
.faq-q svg { width: 20px; height: 20px; color: var(--brand); flex-shrink: 0; transition: transform .25s; }
.faq-item.open .faq-q svg { transform: rotate(180deg); }
.faq-a { max-height: 0; overflow: hidden; transition: max-height .3s ease; }
.faq-item.open .faq-a { max-height: 240px; }
.faq-a p { color: var(--muted); font-size: 15px; line-height: 1.65; margin: 0; padding: 0 20px 20px; }

/* CTA BAND */
.cta-band {
    text-align: center; max-width: 900px; margin: 0 auto clamp(56px, 8vw, 96px); padding: clamp(40px, 6vw, 68px) 24px;
    background: radial-gradient(ellipse at center, rgba(255, 95, 0, 0.12), transparent 70%), var(--surface);
    border: 1px solid rgba(255, 95, 0, 0.2); border-radius: 26px;
}
.cta-band h2 { font-family: var(--font-display); text-transform: uppercase; font-weight: 800; font-size: clamp(28px, 4vw, 46px); margin: 0 0 10px; }
.cta-band p { color: var(--muted); font-size: 16px; margin: 0 0 26px; }

/* FOOTER */
.foot { border-top: 1px solid var(--line); text-align: center; padding: 40px 24px; color: var(--muted); }
.foot .brand { justify-content: center; margin-bottom: 12px; }
.foot p { font-size: 14px; margin: 0 0 14px; }
.foot-links { display: flex; justify-content: center; gap: 22px; }
.foot-links a { color: var(--muted); text-decoration: none; font-size: 14px; font-weight: 600; transition: color .2s; }
.foot-links a:hover { color: var(--brand); }

/* REVEAL */
.reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s ease, transform .7s ease; }
.reveal.is-visible { opacity: 1; transform: none; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }

/* RESPONSIVE */
@media (max-width: 900px) {
    .hero-inner { grid-template-columns: 1fr; min-height: auto; }
    .hero-visual { display: none; }
    .stats { grid-template-columns: repeat(2, 1fr); }
    .steps, .features { grid-template-columns: 1fr; }
    .feature-strip { grid-template-columns: 1fr; }
    .nav-link { display: none; }
}
@media (max-width: 520px) {
    .stats { grid-template-columns: repeat(2, 1fr); }
    .nav-ghost { display: none; }
    .nav { padding: 14px 16px; }
    .brand-word { font-size: 19px; }
    .nav-cta { padding: 9px 15px; font-size: 13px; }
    .hero { padding-left: 16px; padding-right: 16px; }
    .hero-copy h1 { font-size: clamp(34px, 11vw, 46px); }
    .section-head h2, .cta-band h2, .fs-left h2 { font-size: clamp(24px, 7.5vw, 34px); }
    .section { padding-left: 16px; padding-right: 16px; }
}
@media (prefers-reduced-motion: reduce) {
    .ball, .trophy, .glow-arc, :deep(.particle) { animation: none !important; }
    .reveal { opacity: 1; transform: none; transition: none; }
    .eyebrow, .hero-copy h1, .lead, .cta-row { animation: none !important; }
}
</style>
