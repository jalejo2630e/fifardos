<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    tournament: Object,
    prizes: Array,
    standings: Array,
    stats: { type: Object, default: () => ({ teams: 48, matches: 104, venues: 16, fans: '1.2M' }) },
});

const particlesRef = ref(null);

onMounted(() => {
    const container = particlesRef.value;
    if (!container) return;
    const colors = ['#e05a2b', '#ffffff', '#e8b84b'];
    for (let i = 0; i < 28; i++) {
        const p = document.createElement('div');
        const size = 2 + Math.random() * 3;
        p.className = 'particle';
        p.style.width = size + 'px';
        p.style.height = size + 'px';
        p.style.background = colors[i % colors.length];
        p.style.opacity = '0.4';
        p.style.left = (Math.random() * 100) + '%';
        p.style.top = '-10px';
        p.style.animationDuration = (6 + Math.random() * 6) + 's';
        p.style.animationDelay = (Math.random() * 8) + 's';
        container.appendChild(p);
    }
});

function goLogin() {
    router.visit(route('login'));
}

function goRegister() {
    router.visit(route('register'));
}
</script>

<template>
    <div class="landing">
        <Head title="FIFA 2026" />

        <div class="hero">
            <nav>
                <div class="logo">FIFA<span>26</span></div>

                <Link :href="route('login')" class="nav-cta">Iniciar sesión</Link>
            </nav>

            <div class="glow-arc"></div>

            <div class="particles" ref="particlesRef"></div>

            <div class="trophy-wrap">
                <svg viewBox="0 0 100 140" width="150" height="210">
                    <defs>
                        <linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#fbe6a8"/>
                            <stop offset="45%" stop-color="#e8b84b"/>
                            <stop offset="100%" stop-color="#8a6316"/>
                        </linearGradient>
                    </defs>
                    <path d="M28 8 H72 V20 C72 40 62 48 52 50 V70 H62 V84 H38 V70 H48 V50 C38 48 28 40 28 20 Z"
                          fill="url(#goldGrad)"/>
                    <rect x="30" y="84" width="40" height="10" rx="2" fill="url(#goldGrad)"/>
                    <rect x="20" y="94" width="60" height="12" rx="3" fill="url(#goldGrad)"/>
                </svg>
            </div>

            <div class="ball-wrap">
                <div class="ball">
                    <svg viewBox="0 0 200 200">
                        <g fill="none" stroke="#1a1a1a" stroke-width="2" opacity="0.6">
                            <circle cx="100" cy="100" r="94"/>
                            <polygon points="100,55 122,72 114,98 86,98 78,72" fill="#1a1a1a"/>
                            <line x1="100" y1="55" x2="100" y2="15"/>
                            <line x1="122" y1="72" x2="158" y2="50"/>
                            <line x1="114" y1="98" x2="140" y2="130"/>
                            <line x1="86" y1="98" x2="60" y2="130"/>
                            <line x1="78" y1="72" x2="42" y2="50"/>
                            <circle cx="100" cy="18" r="12" fill="#1a1a1a"/>
                            <circle cx="160" cy="52" r="12" fill="#1a1a1a"/>
                            <circle cx="146" cy="132" r="12" fill="#1a1a1a"/>
                            <circle cx="54" cy="132" r="12" fill="#1a1a1a"/>
                            <circle cx="40" cy="52" r="12" fill="#1a1a1a"/>
                        </g>
                    </svg>
                </div>
            </div>

            <div class="hero-body">
                <div class="content">
                    <span class="badge">TEMPORADA 2026</span>
                    <h1>ARMA TU TORNEO<br>Y DALE CON<br><em>LOS PANAS</em></h1>
                    <p class="sub">Organiza tus torneos de FIFA, carga los resultados de cada partido y mira quién es el mejor del grupo. Sin excusas, todo queda registrado.</p>
                    <div class="cta-row">
                        <button class="btn-primary" @click="goRegister">Register for free</button>
                    </div>
                </div>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-num">{{ stats.teams }}</div>
                    <div class="stat-label">Selecciones clasificadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num">{{ stats.matches }}</div>
                    <div class="stat-label">Partidos en vivo</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num">{{ stats.venues }}</div>
                    <div class="stat-label">Sedes oficiales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num">{{ stats.fans }}</div>
                    <div class="stat-label">Fanáticos registrados</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
:root {
    --bg-landing: #0a0a0a;
    --bg-2: #111111;
    --accent-landing: #e05a2b;
    --accent-2: #ff7a3d;
    --gold-landing: #e8b84b;
    --gold-2: #8a6316;
    --text-landing: #ffffff;
    --text-muted: #9c9a94;
    --border-landing: rgba(255, 255, 255, 0.08);
}
</style>

<style scoped>
.landing {
    background: var(--bg-landing);
    color: var(--text-landing);
    font-family: 'Helvetica Neue', Arial, sans-serif;
    overflow-x: hidden;
}

.hero {
    position: relative;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

nav {
    position: relative;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 28px 64px;
    border-bottom: 1px solid var(--border-landing);
}

.logo {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: 1px;
}

.logo span {
    color: var(--accent-2);
}

.nav-links {
    display: flex;
    gap: 36px;
    list-style: none;
}

.nav-links a {
    color: var(--text-muted);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: color .2s ease;
}

.nav-links a:hover {
    color: var(--text-landing);
}

.nav-cta {
    background: transparent;
    border: 1px solid var(--border-landing);
    color: var(--text-landing);
    padding: 10px 22px;
    border-radius: 24px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: border-color .2s ease, background .2s ease;
    text-decoration: none;
}

.nav-cta:hover {
    border-color: var(--accent-2);
    background: rgba(224, 90, 43, 0.08);
}

.hero-body {
    position: relative;
    flex: 1;
    display: flex;
    align-items: center;
    padding: 40px 64px 80px;
}

.glow-arc {
    position: absolute;
    top: 50%;
    right: 6%;
    width: 620px;
    height: 620px;
    transform: translateY(-50%);
    border: 1px dashed rgba(255, 255, 255, 0.10);
    border-radius: 50%;
    z-index: 1;
}

.glow-arc::before {
    content: '';
    position: absolute;
    inset: 60px;
    border: 1px dashed rgba(232, 184, 75, 0.14);
    border-radius: 50%;
}

.content {
    position: relative;
    z-index: 5;
    max-width: 640px;
}

.badge {
    display: inline-block;
    background: linear-gradient(90deg, var(--accent-landing), var(--accent-2));
    color: #1a0a03;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1.5px;
    padding: 7px 16px;
    border-radius: 20px;
    margin-bottom: 24px;
}

h1 {
    font-size: clamp(40px, 5.5vw, 68px);
    font-weight: 800;
    line-height: 1.02;
    letter-spacing: -1px;
    text-transform: uppercase;
    margin-bottom: 22px;
}

h1 em {
    font-style: normal;
    background: linear-gradient(90deg, var(--accent-2), var(--gold-landing));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.sub {
    font-size: 17px;
    color: var(--text-muted);
    line-height: 1.7;
    max-width: 460px;
    margin-bottom: 36px;
}

.cta-row {
    display: flex;
    align-items: center;
    gap: 20px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--accent-2), var(--accent-landing));
    color: #1a0a03;
    border: none;
    padding: 16px 32px;
    font-size: 15px;
    font-weight: 800;
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(224, 90, 43, 0.28);
    transition: transform .18s ease, box-shadow .18s ease;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 32px rgba(224, 90, 43, 0.4);
}

.btn-primary:active {
    transform: translateY(-1px) scale(0.97);
}

/* BALL */
.ball-wrap {
    position: absolute;
    top: 50%;
    right: 5%;
    transform: translateY(-50%);
    width: 420px;
    height: 420px;
    z-index: 3;
    animation: floatBall 5.5s ease-in-out infinite;
}

.ball {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    position: relative;
    background: radial-gradient(circle at 32% 28%, #ffffff 0%, #e8e8e8 18%, #b9b9b9 42%, #4a4a4a 78%, #1a1a1a 100%);
    box-shadow:
        inset -30px -30px 60px rgba(0, 0, 0, 0.55),
        inset 20px 20px 40px rgba(255, 255, 255, 0.15),
        0 30px 70px rgba(0, 0, 0, 0.55),
        0 0 90px rgba(224, 90, 43, 0.18);
    animation: spinBall 6.5s linear infinite;
}

.ball svg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    mix-blend-mode: multiply;
    opacity: 0.85;
}

@keyframes spinBall {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes floatBall {
    0%, 100% { transform: translateY(-50%) translateY(0); }
    50% { transform: translateY(-50%) translateY(-22px); }
}

/* TROPHY */
.trophy-wrap {
    position: absolute;
    bottom: 6%;
    right: 26%;
    width: 150px;
    height: 210px;
    z-index: 4;
    animation: floatTrophy 6s ease-in-out infinite;
    filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.5));
}

@keyframes floatTrophy {
    0%, 100% { transform: translateY(0) rotate(-2deg); }
    50% { transform: translateY(-14px) rotate(2deg); }
}

/* PARTICLES */
.particles {
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
}

.particle {
    position: absolute;
    border-radius: 50%;
    animation: fall linear infinite;
}

@keyframes fall {
    from { transform: translateY(-20px); opacity: 0; }
    8% { opacity: 1; }
    to { transform: translateY(110vh); opacity: 0; }
}

/* STAT CARDS */
.stats {
    position: relative;
    z-index: 5;
    display: flex;
    gap: 20px;
    padding: 0 64px 56px;
    flex-wrap: wrap;
}

.stat-card {
    background: var(--bg-2);
    border: 1px solid var(--border-landing);
    border-radius: 14px;
    padding: 20px 24px;
    min-width: 170px;
    transition: border-color .2s ease, transform .2s ease;
}

.stat-card:hover {
    border-color: rgba(224, 90, 43, 0.4);
    transform: translateY(-4px);
}

.stat-num {
    font-size: 28px;
    font-weight: 800;
    background: linear-gradient(90deg, var(--accent-2), var(--gold-landing));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.stat-label {
    font-size: 13px;
    color: var(--text-muted);
    margin-top: 4px;
}

@media (max-width: 900px) {
    .ball-wrap {
        width: 260px;
        height: 260px;
        right: -40px;
        opacity: 0.5;
    }
    .trophy-wrap { display: none; }
    .glow-arc { display: none; }
    nav { padding: 20px 24px; }
    .nav-links { display: none; }
    .hero-body { padding: 20px 24px 60px; }
    .stats { padding: 0 24px 40px; }
}

@media (prefers-reduced-motion: reduce) {
    .ball,
    .ball-wrap,
    .trophy-wrap,
    .particle {
        animation: none !important;
    }
}
</style>
