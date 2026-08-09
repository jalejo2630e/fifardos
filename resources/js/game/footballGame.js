/**
 * FIFARDOS — Minijuego 11 vs 11 (Three.js)
 * ---------------------------------------------------------------------------
 * Partido de fútbol arcade estilo "FC" con gráficos livianos para correr en el
 * navegador. Un jugador humano dirige a su equipo (controla siempre al jugador
 * más cercano a la pelota) contra un equipo de 11 manejado por la IA. Los
 * partidos duran 2 minutos.
 *
 * Se carga en diferido (dynamic import) para no penalizar el peso del landing.
 * NO usa motor de física pesado: la simulación de los 22 jugadores es cinemática
 * (steering simple sobre el plano X/Z) y la pelota tiene una física propia y
 * barata en 3D (X/Z + altura Y con gravedad y rebotes). Con eso se logra un
 * partido fluido y liviano.
 *
 * Convención de cancha (plano X/Z, alturas en Y):
 *   - X: ancho de cancha  [-HALF_W, HALF_W]
 *   - Z: largo de cancha  [-HALF_L, HALF_L]
 *   - El equipo HUMANO (team 0) ataca hacia +Z; la CPU (team 1) hacia -Z.
 *   - Cámara alta detrás del arco del humano mirando hacia +Z (el humano ataca
 *     "hacia arriba" en pantalla): W/↑ = adelante, S/↓ = atrás, A/D = izq/der.
 *
 * API pública (igual patrón que el minijuego de penales):
 *   createFootballGame({...}) -> {
 *     setDifficulty, reset, pause, resume, dispose,
 *     move(x,z), action(), shootDown(), shootUp()   // input táctil
 *   }
 */
import * as THREE from 'three';

// ---- Dimensiones de cancha (metros) ----------------------------------------
const HALF_W = 32;              // ancho total 64
const HALF_L = 48;              // largo total 96 (línea de gol en ±48)
const WALL_X = HALF_W - 1;      // pared lateral (la pelota rebota)
const GOAL_HALF = 6;            // arco de 12 m de ancho
const GOAL_H = 4;               // alto del arco (bajo el travesaño = gol)
const BALL_R = 0.35;
const GRAVITY = -22;            // gravedad "arcade" (más ágil que 9.81)

// ---- Ritmo de juego ---------------------------------------------------------
const CONTROL_R = 1.7;          // radio para tomar la pelota
const DRIBBLE_D = 1.35;         // la pelota queda adelante del que dribla
const KICK_COOLDOWN = 0.45;     // el pateador no puede recuperarla enseguida
const TACKLE_R = 2.2;           // radio para robar

// Colores de marca (equipos)
const C_HOME = 0xff5f00;        // humano — naranja de marca
const C_HOME_DK = 0x8a3100;
const C_AWAY = 0x2f6bff;        // CPU — azul
const C_AWAY_DK = 0x16306e;
const C_GK_H = 0xb6ff2e;        // arquero humano — lima
const C_GK_A = 0xffe14d;        // arquero CPU — amarillo
const C_LIME = 0xb6ff2e;
const C_ACCENT = 0xff5f00;

const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
const lerp = (a, b, t) => a + (b - a) * t;
const hyp = (x, z) => Math.hypot(x, z);

// Formación 4-4-2 del equipo local (ataca +Z, defiende -Z). Índice 0 = arquero.
// La CPU (team 1) usa estos mismos puntos con la Z espejada.
const FORMATION = [
    { role: 'GK',  x: 0,   z: -45 },
    { role: 'DEF', x: -19, z: -33 }, { role: 'DEF', x: -7, z: -35 },
    { role: 'DEF', x: 7,   z: -35 }, { role: 'DEF', x: 19, z: -33 },
    { role: 'MID', x: -22, z: -14 }, { role: 'MID', x: -8, z: -18 },
    { role: 'MID', x: 8,   z: -18 }, { role: 'MID', x: 22, z: -14 },
    { role: 'FWD', x: -9,  z: -3 },  { role: 'FWD', x: 9,  z: -3 },
];

const DIFF = {
    easy:   { speed: 12.5, react: 0.55, pass: 0.55, steal: 0.30 },
    medium: { speed: 14.5, react: 0.35, pass: 0.70, steal: 0.45 },
    hard:   { speed: 16.0, react: 0.20, pass: 0.85, steal: 0.60 },
};
const USER_SPEED = 16.5;
const ACCEL = 9;                // suavizado de aceleración

// ---------------------------------------------------------------------------
// Texturas procedurales (sin descargas)
// ---------------------------------------------------------------------------
function pitchTexture() {
    const c = document.createElement('canvas');
    c.width = c.height = 512;
    const g = c.getContext('2d');
    const stripes = 12;
    for (let i = 0; i < stripes; i++) {
        g.fillStyle = i % 2 ? '#2f8f36' : '#2a8231';
        g.fillRect(0, (i * 512) / stripes, 512, 512 / stripes);
    }
    const tex = new THREE.CanvasTexture(c);
    tex.wrapS = tex.wrapT = THREE.ClampToEdgeWrapping;
    tex.colorSpace = THREE.SRGBColorSpace;
    tex.anisotropy = 4;
    return tex;
}

// ---------------------------------------------------------------------------
// Motor principal
// ---------------------------------------------------------------------------
export async function createFootballGame({
    canvas, container, onUpdate = () => {}, difficulty = 'medium', duration = 120,
} = {}) {
    // ---- Renderer / escena / cámara ----
    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: false, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.02;

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0a1a10);
    scene.fog = new THREE.Fog(0x0a1a10, 90, 170);

    const camera = new THREE.PerspectiveCamera(52, 16 / 9, 0.5, 400);
    camera.position.set(0, 60, -86);
    camera.lookAt(0, 0, 6);

    // ---- Luces (baratas: sin sombras) ----
    scene.add(new THREE.HemisphereLight(0xdfefff, 0x1f3a22, 1.0));
    const sun = new THREE.DirectionalLight(0xffffff, 1.6);
    sun.position.set(-30, 60, -20);
    scene.add(sun);

    // ---- Césped ----
    const pitch = new THREE.Mesh(
        new THREE.PlaneGeometry(HALF_W * 2 + 8, HALF_L * 2 + 10),
        new THREE.MeshLambertMaterial({ map: pitchTexture() })
    );
    pitch.rotation.x = -Math.PI / 2;
    scene.add(pitch);

    // ---- Líneas de cancha ----
    const lineMat = new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.55 });
    const addLineBox = (w, d, x, z) => {
        const m = new THREE.Mesh(new THREE.PlaneGeometry(w, d), lineMat);
        m.rotation.x = -Math.PI / 2;
        m.position.set(x, 0.02, z);
        scene.add(m);
    };
    const LW = 0.22;
    // perímetro
    addLineBox(HALF_W * 2, LW, 0, -HALF_L);  addLineBox(HALF_W * 2, LW, 0, HALF_L);
    addLineBox(LW, HALF_L * 2, -HALF_W, 0);  addLineBox(LW, HALF_L * 2, HALF_W, 0);
    // línea media
    addLineBox(HALF_W * 2, LW, 0, 0);
    // círculo central
    const ring = new THREE.Mesh(new THREE.RingGeometry(9 - LW, 9, 48), lineMat);
    ring.rotation.x = -Math.PI / 2; ring.position.y = 0.02;
    scene.add(ring);
    // áreas
    for (const s of [-1, 1]) {
        addLineBox(30, LW, 0, s * (HALF_L - 16));           // línea del área grande
        addLineBox(LW, 16, -15, s * (HALF_L - 8));
        addLineBox(LW, 16, 15, s * (HALF_L - 8));
        addLineBox(13, LW, 0, s * (HALF_L - 6));            // área chica
        addLineBox(LW, 6, -6.5, s * (HALF_L - 3));
        addLineBox(LW, 6, 6.5, s * (HALF_L - 3));
    }

    // ---- Arcos (postes + travesaño + red) ----
    const postMat = new THREE.MeshLambertMaterial({ color: 0xf2f2f0 });
    const netMat = new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.14, side: THREE.DoubleSide });
    const buildGoal = (sign) => {
        const z = sign * HALF_L;
        const postGeo = new THREE.CylinderGeometry(0.14, 0.14, GOAL_H, 10);
        for (const sx of [-1, 1]) {
            const p = new THREE.Mesh(postGeo, postMat);
            p.position.set(sx * GOAL_HALF, GOAL_H / 2, z);
            scene.add(p);
        }
        const bar = new THREE.Mesh(new THREE.CylinderGeometry(0.14, 0.14, GOAL_HALF * 2 + 0.28, 10), postMat);
        bar.rotation.z = Math.PI / 2;
        bar.position.set(0, GOAL_H, z);
        scene.add(bar);
        const depth = 2.6;
        const back = new THREE.Mesh(new THREE.PlaneGeometry(GOAL_HALF * 2, GOAL_H), netMat);
        back.position.set(0, GOAL_H / 2, z + sign * depth);
        scene.add(back);
        const top = new THREE.Mesh(new THREE.PlaneGeometry(GOAL_HALF * 2, depth), netMat);
        top.rotation.x = Math.PI / 2;
        top.position.set(0, GOAL_H, z + sign * depth / 2);
        scene.add(top);
    };
    buildGoal(1); buildGoal(-1);

    // ---- Pelota ----
    const ballMesh = new THREE.Mesh(
        new THREE.SphereGeometry(BALL_R, 18, 14),
        new THREE.MeshStandardMaterial({ color: 0xf6f6f4, roughness: 0.45, metalness: 0.02 })
    );
    scene.add(ballMesh);
    const ballShadow = new THREE.Mesh(
        new THREE.CircleGeometry(BALL_R * 1.1, 16),
        new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0.28 })
    );
    ballShadow.rotation.x = -Math.PI / 2;
    scene.add(ballShadow);

    const ball = { pos: new THREE.Vector3(0, BALL_R, 0), vel: new THREE.Vector3() };

    // =========================================================================
    // Jugadores (mallas compartidas + material por equipo)
    // =========================================================================
    const bodyGeo = new THREE.CylinderGeometry(0.55, 0.6, 1.7, 10);
    const headGeo = new THREE.SphereGeometry(0.42, 12, 10);
    const noseGeo = new THREE.BoxGeometry(0.35, 0.5, 0.45);
    const shadowGeo = new THREE.CircleGeometry(0.8, 14);
    const skinMat = new THREE.MeshLambertMaterial({ color: 0xe4b48c });
    const shadowMat = new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0.25 });
    const teamMat = [
        new THREE.MeshLambertMaterial({ color: C_HOME }),
        new THREE.MeshLambertMaterial({ color: C_AWAY }),
    ];
    const gkMat = [
        new THREE.MeshLambertMaterial({ color: C_GK_H }),
        new THREE.MeshLambertMaterial({ color: C_GK_A }),
    ];
    const noseMat = [
        new THREE.MeshLambertMaterial({ color: C_HOME_DK }),
        new THREE.MeshLambertMaterial({ color: C_AWAY_DK }),
    ];

    // Anillo bajo el jugador controlado por el humano
    const activeRing = new THREE.Mesh(
        new THREE.RingGeometry(0.95, 1.25, 24),
        new THREE.MeshBasicMaterial({ color: C_LIME, transparent: true, opacity: 0.95, side: THREE.DoubleSide })
    );
    activeRing.rotation.x = -Math.PI / 2;
    activeRing.position.y = 0.03;
    scene.add(activeRing);

    function makePlayer(team, isGK) {
        const g = new THREE.Group();
        const mat = isGK ? gkMat[team] : teamMat[team];
        const body = new THREE.Mesh(bodyGeo, mat); body.position.y = 0.85;
        const head = new THREE.Mesh(headGeo, skinMat); head.position.y = 2.0;
        const nose = new THREE.Mesh(noseGeo, noseMat[team]); nose.position.set(0, 0.95, 0.55);
        g.add(body, head, nose);
        scene.add(g);
        const sh = new THREE.Mesh(shadowGeo, shadowMat);
        sh.rotation.x = -Math.PI / 2; sh.position.y = 0.02;
        scene.add(sh);
        return { group: g, shadow: sh };
    }

    // Estructura de cada jugador
    const players = [];
    for (let team = 0; team < 2; team++) {
        for (let i = 0; i < FORMATION.length; i++) {
            const f = FORMATION[i];
            // El local usa la formación tal cual; la CPU la espeja en Z.
            const home = team === 0 ? { x: f.x, z: f.z } : { x: f.x, z: -f.z };
            const vis = makePlayer(team, f.role === 'GK');
            players.push({
                team, role: f.role, index: i,
                home,
                pos: { x: home.x, z: home.z },
                vel: { x: 0, z: 0 },
                dir: team === 0 ? 0 : Math.PI,
                kickCd: 0,
                ...vis,
            });
        }
    }
    const homeTeam = players.filter((p) => p.team === 0);
    const awayTeam = players.filter((p) => p.team === 1);
    const attackDirOf = (team) => (team === 0 ? 1 : -1);      // hacia dónde ataca (Z)
    const goalZOf = (team) => attackDirOf(team) * HALF_L;      // arco al que apunta

    // =========================================================================
    // Estado de partido
    // =========================================================================
    const st = {
        phase: 'kickoff',      // kickoff | play | goal | fulltime
        timeLeft: duration,
        home: 0, away: 0,
        message: '',
        resultText: '',
        charge: { kind: null, power: 0 },   // barra de fuerza (pase/tiro)
    };
    const diff = { ...(DIFF[difficulty] || DIFF.medium) };
    let diffKey = DIFF[difficulty] ? difficulty : 'medium';

    let possessor = null;      // jugador con la pelota
    let userPlayer = null;     // jugador que maneja el humano
    let kickoffFor = 0;        // equipo que saca del medio
    let matchTime = duration;
    let kickoffTimer = 0;      // cuenta regresiva del saque
    let goalTimer = 0;         // pausa tras el gol

    // Input del humano. `charging` = 'pass' | 'shoot' | null; `power` sube 0→1
    // mientras se mantiene el botón (barra de fuerza) y se aplica al soltar.
    const input = { mx: 0, mz: 0, charging: null, power: 0 };
    const keys = new Set();
    const CHARGE_RATE = 1.35;   // qué tan rápido se llena la barra (por segundo)

    // ---- Emisión de estado (sólo cuando cambia algo relevante) ----
    let lastSig = '';
    function push() {
        const sig = `${st.phase}|${st.timeLeft}|${st.home}|${st.away}|${st.message}|${st.resultText}`
            + `|${st.charge.kind}|${Math.round(st.charge.power * 20)}`;
        if (sig === lastSig) return;
        lastSig = sig;
        onUpdate({ ...st, charge: { ...st.charge } });
    }

    // =========================================================================
    // Utilidades de simulación
    // =========================================================================
    function nearestBall(team, includeGK = false) {
        let best = null, bd = Infinity;
        for (const p of team === 0 ? homeTeam : awayTeam) {
            if (!includeGK && p.role === 'GK') continue;
            const d = hyp(p.pos.x - ball.pos.x, p.pos.z - ball.pos.z);
            if (d < bd) { bd = d; best = p; }
        }
        return best;
    }

    // Elige a quién controla el humano: el local más cercano a la pelota
    // (con histéresis para no saltar todo el tiempo).
    function updateUserPlayer() {
        // Regla de oro: controlás a quien tenga la pelota en tu equipo (menos el
        // arquero, que despeja solo). Así la IA nunca patea "por vos".
        if (possessor && possessor.team === 0 && possessor.role !== 'GK') { userPlayer = possessor; return; }
        const cand = nearestBall(0, false) || homeTeam[0];
        if (!userPlayer) { userPlayer = cand; return; }
        if (cand === userPlayer) return;
        const du = hyp(userPlayer.pos.x - ball.pos.x, userPlayer.pos.z - ball.pos.z);
        const dc = hyp(cand.pos.x - ball.pos.x, cand.pos.z - ball.pos.z);
        if (dc < du - 2.2) userPlayer = cand;   // histéresis: no saltar de golpe
    }

    // Objetivo de formación de un jugador (la línea se corre con la pelota).
    function formationTarget(p) {
        const dir = attackDirOf(p.team);
        let driftZ, xComp;
        if (p.role === 'GK')      { driftZ = 0.04; xComp = 0.35; }
        else if (p.role === 'DEF'){ driftZ = 0.22; xComp = 0.20; }
        else if (p.role === 'MID'){ driftZ = 0.34; xComp = 0.22; }
        else                      { driftZ = 0.42; xComp = 0.24; }
        let tx = p.home.x * (1 - xComp) + ball.pos.x * xComp;
        let tz = p.home.z + ball.pos.z * driftZ;
        // Los defensores no cruzan hacia el ataque. (El arquero se maneja aparte
        // en goalkeeper(), así que acá nunca entra un GK.)
        if (p.role === 'DEF') tz = dir > 0 ? Math.min(tz, 4) : Math.max(tz, -4);
        return { x: clamp(tx, -WALL_X, WALL_X), z: clamp(tz, -HALF_L + 1.5, HALF_L - 1.5) };
    }

    // Mueve un jugador hacia (tx,tz) a cierta velocidad, con suavizado.
    function steer(p, tx, tz, speed, dt) {
        const dx = tx - p.pos.x, dz = tz - p.pos.z;
        const d = hyp(dx, dz);
        let dvx = 0, dvz = 0;
        if (d > 0.15) { dvx = (dx / d) * speed; dvz = (dz / d) * speed; }
        p.vel.x = lerp(p.vel.x, dvx, clamp(ACCEL * dt, 0, 1));
        p.vel.z = lerp(p.vel.z, dvz, clamp(ACCEL * dt, 0, 1));
    }

    // Separación suave entre compañeros (evita que se amontonen).
    function separation(p, dt) {
        let sx = 0, sz = 0;
        const team = p.team === 0 ? homeTeam : awayTeam;
        for (const q of team) {
            if (q === p) continue;
            const dx = p.pos.x - q.pos.x, dz = p.pos.z - q.pos.z;
            const d = hyp(dx, dz);
            if (d > 0.001 && d < 3.2) { sx += dx / d * (3.2 - d); sz += dz / d * (3.2 - d); }
        }
        p.pos.x += sx * 0.6 * dt;
        p.pos.z += sz * 0.6 * dt;
    }

    function integrate(p, dt) {
        p.pos.x = clamp(p.pos.x + p.vel.x * dt, -WALL_X, WALL_X);
        p.pos.z = clamp(p.pos.z + p.vel.z * dt, -HALF_L + 1, HALF_L - 1);
        if (Math.abs(p.vel.x) + Math.abs(p.vel.z) > 0.4) {
            p.dir = Math.atan2(p.vel.x, p.vel.z);
        }
        if (p.kickCd > 0) p.kickCd -= dt;
    }

    // =========================================================================
    // Pelota + posesión
    // =========================================================================
    function updatePossession() {
        // Si alguien la tiene y la sigue teniendo cerca, se mantiene.
        let best = null, bd = CONTROL_R;
        for (const p of players) {
            if (p.kickCd > 0) continue;
            if (ball.pos.y > 1.6) continue;               // pelota muy alta: no se controla
            const d = hyp(p.pos.x - ball.pos.x, p.pos.z - ball.pos.z);
            if (d < bd) { bd = d; best = p; }
        }
        possessor = best;
    }

    function dribble(p, dt) {
        // La pelota queda adelante del jugador según su dirección.
        const fx = Math.sin(p.dir), fz = Math.cos(p.dir);
        const tx = p.pos.x + fx * DRIBBLE_D;
        const tz = p.pos.z + fz * DRIBBLE_D;
        ball.pos.x = lerp(ball.pos.x, tx, clamp(14 * dt, 0, 1));
        ball.pos.z = lerp(ball.pos.z, tz, clamp(14 * dt, 0, 1));
        ball.pos.y = BALL_R;
        ball.vel.set(p.vel.x, 0, p.vel.z);
    }

    function kick(p, dirX, dirZ, speed, loft) {
        const d = hyp(dirX, dirZ) || 1;
        ball.vel.set((dirX / d) * speed, loft, (dirZ / d) * speed);
        ball.pos.y = Math.max(ball.pos.y, BALL_R + 0.05);
        p.kickCd = KICK_COOLDOWN;
        possessor = null;
    }

    // Mejor compañero para un pase (adelantado hacia el arco rival y despejado).
    function bestPassTarget(p) {
        const team = p.team === 0 ? homeTeam : awayTeam;
        const opp = p.team === 0 ? awayTeam : homeTeam;
        const dir = attackDirOf(p.team);
        let best = null, bestScore = -Infinity;
        for (const q of team) {
            if (q === p || q.role === 'GK') continue;
            const dz = (q.pos.z - p.pos.z) * dir;           // >0 = más adelantado
            const dist = hyp(q.pos.x - p.pos.x, q.pos.z - p.pos.z);
            if (dist < 6 || dist > 45) continue;
            // ¿Hay un rival tapando la línea? (penaliza)
            let blocked = 0;
            for (const o of opp) {
                const od = hyp(o.pos.x - q.pos.x, o.pos.z - q.pos.z);
                if (od < 4) blocked += (4 - od);
            }
            const score = dz * 1.4 - blocked * 2 - dist * 0.05;
            if (score > bestScore) { bestScore = score; best = q; }
        }
        return best;
    }

    // =========================================================================
    // IA de un equipo (para la CPU y para los compañeros del humano)
    // =========================================================================
    function aiTeam(team, dt) {
        const mates = team === 0 ? homeTeam : awayTeam;
        const chaser = nearestBall(team, false);
        for (const p of mates) {
            if (p === userPlayer && team === 0) continue;   // lo maneja el humano
            const speed = team === 0 ? USER_SPEED * 0.92 : diff.speed;

            // El arquero se maneja aparte (incluye despejar si tiene la pelota).
            if (p.role === 'GK') { goalkeeper(p, dt); continue; }

            if (p === possessor) { aiWithBall(p, dt, speed); continue; }

            if (p === chaser && ballLooseOrTheirs(team)) {
                // Va a la pelota (interceptando su trayectoria).
                const px = ball.pos.x + ball.vel.x * 0.18;
                const pz = ball.pos.z + ball.vel.z * 0.18;
                steer(p, px, pz, speed, dt);
            } else {
                const t = formationTarget(p);
                steer(p, t.x, t.z, speed * 0.82, dt);
            }
        }
    }

    // ¿La pelota está suelta o la tiene el rival? (define si el chaser presiona)
    function ballLooseOrTheirs(team) {
        return !possessor || possessor.team !== team;
    }

    function aiWithBall(p, dt, speed) {
        const dir = attackDirOf(p.team);
        const goalZ = goalZOf(p.team);
        const toGoalZ = (goalZ - p.pos.z) * dir;            // distancia al arco (adelante)
        const distGoal = hyp(0 - p.pos.x, goalZ - p.pos.z);

        // ¿Rival encima?
        const opp = p.team === 0 ? awayTeam : homeTeam;
        let pressed = false, nearOppX = 0;
        for (const o of opp) {
            const d = hyp(o.pos.x - p.pos.x, o.pos.z - p.pos.z);
            if (d < 3.2) { pressed = true; nearOppX = o.pos.x; }
        }

        // Disparo si está cerca y de frente al arco.
        if (distGoal < 26 && toGoalZ > 0 && Math.random() < 0.05 + (26 - distGoal) * 0.01) {
            const aim = clamp((Math.random() - 0.5) * GOAL_HALF * 1.2, -GOAL_HALF + 0.5, GOAL_HALF - 0.5);
            kick(p, aim - p.pos.x, goalZ - p.pos.z, lerp(34, 46, clamp((26 - distGoal) / 26, 0, 1)), 1.2);
            return;
        }

        // Bajo presión: pasar (según habilidad) o despejar.
        if (pressed) {
            if (Math.random() < diff.pass) {
                const mate = bestPassTarget(p);
                if (mate) {
                    const lead = 1.2;
                    kick(p, mate.pos.x + mate.vel.x * lead - p.pos.x,
                            mate.pos.z + mate.vel.z * lead - p.pos.z,
                            clamp(hyp(mate.pos.x - p.pos.x, mate.pos.z - p.pos.z) * 1.5, 20, 34), 0.6);
                    return;
                }
            }
            // Despeje hacia adelante evitando al rival
            const away = p.pos.x > nearOppX ? 1 : -1;
            kick(p, away * 8, dir * 20, 30, 2.0);
            return;
        }

        // Sin presión: gambetea hacia el arco (esquivando levemente al centro).
        const tx = clamp(p.pos.x + (0 - p.pos.x) * 0.15, -WALL_X, WALL_X);
        const tz = p.pos.z + dir * 6;
        steer(p, tx, tz, speed, dt);
    }

    function goalkeeper(p, dt) {
        const goalZ = p.home.z;
        // Se queda en la línea siguiendo la X de la pelota.
        const tx = clamp(ball.pos.x, -GOAL_HALF - 1.5, GOAL_HALF + 1.5);
        // Sale a achicar si la pelota está muy cerca y del lado del arco.
        const ballNear = (ball.pos.z - goalZ) * attackDirOf(p.team) < 0 &&
                         Math.abs(ball.pos.z - goalZ) < 12 && Math.abs(ball.pos.x) < 14;
        const tz = ballNear ? lerp(goalZ, ball.pos.z, 0.5) : goalZ;
        steer(p, tx, tz, DIFF[diffKey].speed * 0.95, dt);
        // Si tiene la pelota, la juega/despeja.
        if (possessor === p) {
            const mate = bestPassTarget(p);
            if (mate && Math.random() < 0.7) {
                kick(p, mate.pos.x - p.pos.x, mate.pos.z - p.pos.z, 28, 0.8);
            } else {
                kick(p, (Math.random() - 0.5) * 20, attackDirOf(p.team) * 26, 32, 2.4);
            }
        }
    }

    // =========================================================================
    // Control del humano
    // =========================================================================
    function humanControl(dt) {
        const p = userPlayer;
        if (!p) return;
        // Vector de movimiento (teclado + táctil)
        let mx = input.mx, mz = input.mz;
        if (keys.has('a') || keys.has('arrowleft')) mx -= 1;
        if (keys.has('d') || keys.has('arrowright')) mx += 1;
        if (keys.has('w') || keys.has('arrowup')) mz += 1;
        if (keys.has('s') || keys.has('arrowdown')) mz -= 1;
        const m = hyp(mx, mz);
        if (m > 1) { mx /= m; mz /= m; }

        // La cámara mira hacia +Z (three.js es right-handed): "derecha de pantalla"
        // = world -X. Por eso invertimos la X del input para que D/joystick-derecha
        // muevan al jugador a la derecha visible. +Z ("adelante") sí coincide.
        const speed = USER_SPEED * (possessor === p ? 0.92 : 1);
        if (m > 0.05) {
            p.vel.x = lerp(p.vel.x, -mx * speed, clamp(ACCEL * dt, 0, 1));
            p.vel.z = lerp(p.vel.z, mz * speed, clamp(ACCEL * dt, 0, 1));
        } else {
            // Sin input: si no tiene la pelota, corre hacia ella (asistencia).
            if (possessor !== p) {
                steer(p, ball.pos.x, ball.pos.z, speed, dt);
            } else {
                p.vel.x = lerp(p.vel.x, 0, clamp(ACCEL * dt, 0, 1));
                p.vel.z = lerp(p.vel.z, 0, clamp(ACCEL * dt, 0, 1));
            }
        }

        // Barra de fuerza: mientras se mantiene el botón, sube la potencia (y se
        // frena al tope). Si perdemos la pelota, se cancela la carga.
        if (input.charging && possessor !== p) cancelCharge();
        if (input.charging) input.power = clamp(input.power + dt * CHARGE_RATE, 0, 1);
        st.charge.kind = input.charging;
        st.charge.power = input.charging ? input.power : 0;
    }

    function cancelCharge() { input.charging = null; input.power = 0; st.charge.kind = null; st.charge.power = 0; }

    // ---- Pase (con fuerza) ----
    function startPass() {
        const p = userPlayer;
        if (!p || st.phase !== 'play') return;
        if (possessor === p) { input.charging = 'pass'; input.power = 0; }
        else tackle(p);            // sin pelota: el botón de pase sirve para robar
    }
    function releasePass() {
        const p = userPlayer;
        if (input.charging !== 'pass') return;
        const power = input.power;
        cancelCharge();
        if (!p || possessor !== p || st.phase !== 'play') return;
        const mate = bestPassTarget(p) || homeTeam.find((q) => q !== p && q.role !== 'GK');
        if (mate) {
            const lead = 1.2 + power * 1.4;   // más fuerza = más pase al espacio
            kick(p, mate.pos.x + mate.vel.x * lead - p.pos.x,
                    mate.pos.z + mate.vel.z * lead - p.pos.z,
                    lerp(18, 42, power), 0.4 + power * 0.5);
        }
    }
    function tackle(p) {
        if (possessor && possessor.team !== 0) {
            const d = hyp(p.pos.x - possessor.pos.x, p.pos.z - possessor.pos.z);
            if (d < TACKLE_R && Math.random() < 0.55 + diff.steal * 0.3) {
                possessor.kickCd = KICK_COOLDOWN;
                ball.vel.set(p.vel.x, 0, p.vel.z);
                possessor = p;
            }
        }
    }

    // ---- Tiro (con fuerza) ----
    function startShoot() {
        const p = userPlayer;
        if (!p || st.phase !== 'play' || possessor !== p) return;
        input.charging = 'shoot'; input.power = 0;
    }
    function releaseShoot() {
        const p = userPlayer;
        if (input.charging !== 'shoot') return;
        const power = 0.35 + input.power * 0.65;
        cancelCharge();
        if (!p || possessor !== p || st.phase !== 'play') return;
        const goalZ = goalZOf(0);
        // Apunta al arco, sesgado por la dirección de movimiento del jugador.
        const aimX = clamp(p.pos.x * 0.3 + Math.sin(p.dir) * 3, -GOAL_HALF + 0.4, GOAL_HALF - 0.4);
        kick(p, aimX - p.pos.x, goalZ - p.pos.z, lerp(32, 48, power), 1.0 + power * 1.6);
    }

    // =========================================================================
    // Física de la pelota (cuando está suelta)
    // =========================================================================
    function stepBall(dt) {
        if (possessor) return;    // driblada por separado
        // Gravedad + integración
        ball.vel.y += GRAVITY * dt;
        ball.pos.x += ball.vel.x * dt;
        ball.pos.y += ball.vel.y * dt;
        ball.pos.z += ball.vel.z * dt;

        // Piso
        if (ball.pos.y < BALL_R) {
            ball.pos.y = BALL_R;
            if (ball.vel.y < 0) ball.vel.y *= -0.42;      // rebote
            if (Math.abs(ball.vel.y) < 1.2) ball.vel.y = 0;
            // Fricción de rodadura
            const f = clamp(1 - 1.4 * dt, 0.85, 1);
            ball.vel.x *= f; ball.vel.z *= f;
        }

        // Paredes laterales (rebote)
        if (ball.pos.x < -WALL_X) { ball.pos.x = -WALL_X; ball.vel.x = Math.abs(ball.vel.x) * 0.6; }
        if (ball.pos.x > WALL_X)  { ball.pos.x = WALL_X;  ball.vel.x = -Math.abs(ball.vel.x) * 0.6; }

        // Líneas de fondo → gol o rebote
        checkGoalLine();
    }

    function checkGoalLine() {
        for (const sign of [1, -1]) {
            const z = sign * HALF_L;
            const crossed = sign > 0 ? ball.pos.z >= z : ball.pos.z <= z;
            if (!crossed) continue;
            const inWidth = Math.abs(ball.pos.x) < GOAL_HALF;
            const underBar = ball.pos.y < GOAL_H;
            if (inWidth && underBar) {
                // team 0 ataca +Z → gol en z=+HALF_L lo hace el local.
                scoreGoal(sign > 0 ? 0 : 1);
                return;
            }
            // Rebote en la línea de fondo (fuera del arco)
            ball.pos.z = z - sign * 0.1;
            ball.vel.z *= -0.55;
        }
    }

    // =========================================================================
    // Gol / saques / fin
    // =========================================================================
    function scoreGoal(team) {
        if (st.phase !== 'play') return;
        if (team === 0) st.home++; else st.away++;
        st.phase = 'goal';
        st.message = 'goal';
        goalTimer = 2.2;
        kickoffFor = team === 0 ? 1 : 0;     // saca el que recibió el gol
        possessor = null;
        ball.vel.set(0, 0, 0);
        push();
    }

    function placeKickoff(forTeam) {
        // Ubica a todos en su formación (con la Z espejada para la CPU).
        for (const p of players) {
            p.pos.x = p.home.x;
            p.pos.z = p.home.z;
            p.vel.x = 0; p.vel.z = 0;
            p.kickCd = 0;
            p.dir = p.team === 0 ? 0 : Math.PI;
        }
        // Un delantero del equipo que saca se acerca al centro.
        const kicker = (forTeam === 0 ? homeTeam : awayTeam).find((p) => p.role === 'FWD');
        if (kicker) { kicker.pos.x = 0; kicker.pos.z = -attackDirOf(forTeam) * 1.5; }
        ball.pos.set(0, BALL_R, 0);
        ball.vel.set(0, 0, 0);
        possessor = null;
        userPlayer = null;
        cancelCharge();
        kickoffTimer = 1.4;
        st.phase = 'kickoff';
        st.message = 'kickoff';
        push();
    }

    function endMatch() {
        st.phase = 'fulltime';
        st.timeLeft = 0;
        st.resultText = st.home > st.away ? 'win' : st.home < st.away ? 'lose' : 'draw';
        st.message = '';
        possessor = null;
        push();
    }

    // =========================================================================
    // Bucle
    // =========================================================================
    const clock = new THREE.Clock();
    let rafId = 0, disposed = false, running = true;

    function frame() {
        rafId = requestAnimationFrame(frame);
        if (!running) return;
        const dt = Math.min(clock.getDelta(), 0.033);

        if (st.phase === 'kickoff') {
            kickoffTimer -= dt;
            if (kickoffTimer <= 0) { st.phase = 'play'; st.message = ''; push(); }
        } else if (st.phase === 'goal') {
            goalTimer -= dt;
            if (goalTimer <= 0) placeKickoff(kickoffFor);
        } else if (st.phase === 'play') {
            matchTime -= dt;
            st.timeLeft = Math.max(0, Math.ceil(matchTime));
            if (matchTime <= 0) { endMatch(); }
            else {
                updatePossession();
                updateUserPlayer();
                humanControl(dt);
                aiTeam(0, dt);
                aiTeam(1, dt);
                if (possessor) dribble(possessor, dt);
                for (const p of players) { separation(p, dt); integrate(p, dt); }
                stepBall(dt);
                push();
            }
        }

        // ---- Sincronizar visuales ----
        for (const p of players) {
            p.group.position.set(p.pos.x, 0, p.pos.z);
            p.group.rotation.y = p.dir;
            p.shadow.position.set(p.pos.x, 0.02, p.pos.z);
        }
        ballMesh.position.copy(ball.pos);
        ballMesh.rotation.x += ball.vel.z * dt * 0.5;
        ballMesh.rotation.z -= ball.vel.x * dt * 0.5;
        ballShadow.position.set(ball.pos.x, 0.03, ball.pos.z);
        const sc = clamp(1 - (ball.pos.y - BALL_R) * 0.06, 0.4, 1);
        ballShadow.scale.setScalar(sc);

        // Anillo del jugador activo
        if (userPlayer && st.phase !== 'fulltime') {
            activeRing.visible = true;
            activeRing.position.set(userPlayer.pos.x, 0.03, userPlayer.pos.z);
        } else {
            activeRing.visible = false;
        }

        // Cámara: sigue suavemente a la pelota
        const camTX = clamp(ball.pos.x * 0.25, -12, 12);
        const camTZ = clamp(ball.pos.z * 0.35, -18, 18);
        camera.position.x = lerp(camera.position.x, camTX, 0.04);
        camera.position.z = lerp(camera.position.z, -86 + camTZ * 0.4, 0.04);
        camera.lookAt(camTX * 0.5, 0, 6 + camTZ);

        renderer.render(scene, camera);
    }

    // =========================================================================
    // Input (teclado) — el táctil llega por los métodos públicos
    // =========================================================================
    function onKeyDown(e) {
        const k = e.key.toLowerCase();
        if ([' ', 'arrowup', 'arrowdown', 'arrowleft', 'arrowright'].includes(k)) e.preventDefault();
        if (keys.has(k)) return;
        keys.add(k);
        if (k === ' ' || k === 'j') startPass();
        if (k === 'k' || k === 'l' || k === 'x') startShoot();
    }
    function onKeyUp(e) {
        const k = e.key.toLowerCase();
        keys.delete(k);
        if (k === ' ' || k === 'j') releasePass();
        if (k === 'k' || k === 'l' || k === 'x') releaseShoot();
    }
    window.addEventListener('keydown', onKeyDown);
    window.addEventListener('keyup', onKeyUp);

    // ---- Resize ----
    function resize() {
        const w = container ? container.clientWidth : canvas.clientWidth;
        const h = container ? container.clientHeight : canvas.clientHeight;
        if (!w || !h) return;
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
    }
    const ro = new ResizeObserver(resize);
    ro.observe(container || canvas);
    resize();

    // Arranque
    function fullReset() {
        st.home = 0; st.away = 0; st.timeLeft = duration; st.resultText = '';
        matchTime = duration;
        placeKickoff(0);
    }
    fullReset();
    frame();

    // =========================================================================
    // API pública
    // =========================================================================
    function dispose() {
        disposed = true; running = false;
        cancelAnimationFrame(rafId);
        ro.disconnect();
        window.removeEventListener('keydown', onKeyDown);
        window.removeEventListener('keyup', onKeyUp);
        scene.traverse((o) => {
            if (o.geometry) o.geometry.dispose();
            if (o.material) {
                const mats = Array.isArray(o.material) ? o.material : [o.material];
                mats.forEach((m) => { if (m.map) m.map.dispose(); m.dispose(); });
            }
        });
        renderer.dispose();
    }

    return {
        get state() { return { ...st }; },
        setDifficulty(d) {
            if (DIFF[d]) { diffKey = d; Object.assign(diff, DIFF[d]); }
        },
        reset: fullReset,
        // ---- Input táctil ----
        move(x, z) { input.mx = clamp(x, -1, 1); input.mz = clamp(z, -1, 1); },
        passDown() { startPass(); },
        passUp() { releasePass(); },
        shootDown() { startShoot(); },
        shootUp() { releaseShoot(); },
        pause() { running = false; },
        resume() { if (!disposed) { running = true; clock.getDelta(); } },
        dispose,
    };
}
