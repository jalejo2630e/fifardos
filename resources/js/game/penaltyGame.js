/**
 * FIFARDOS — Minijuego de penales (Three.js + Rapier)
 * ---------------------------------------------------------------------------
 * Motor 3D "realista estilizado" cargado en diferido (dynamic import) para no
 * penalizar el peso inicial del landing. Expone createPenaltyGame() que devuelve
 * un controlador con setMode / setDifficulty / reset / pause / resume / dispose.
 *
 * Convención de la cancha (metros, proporciones reales de FIFA):
 *   - Punto de penal en (0, BALL_R, 0). Arco en z = GOAL_Z (línea de gol).
 *   - Ancho de arco 7.32 m, alto 2.44 m. Cámara detrás del balón mirando al arco.
 *   - Único cuerpo dinámico de Rapier: el balón. El resto (piso, palos, travesaño,
 *     red) son colliders estáticos para rebotes realistas. El arquero es visual y
 *     su atajada se resuelve por proximidad (tunable y justo).
 */
import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import RAPIER from '@dimforge/rapier3d-compat';

// Modelo del arquero (humano riggeado, esqueleto Mixamo). Placeholder gratuito;
// se puede reemplazar por un arquero de Mixamo con los mismos nombres de huesos.
const KEEPER_MODEL_URL = '/models/keeper.glb';

// Clips de estirada reales (exportá de Mixamo "Without Skin" como .glb y dejalos
// en public/models/anims/). Si no están, se usa la estirada procedural de respaldo.
const KEEPER_ANIMS = [
    { key: 'left',       file: '/models/anims/dive_left.glb' },
    { key: 'right',      file: '/models/anims/dive_right.glb' },
    { key: 'left_high',  file: '/models/anims/dive_left_high.glb' },
    { key: 'right_high', file: '/models/anims/dive_right_high.glb' },
];
const DIVE_DURATION = 0.5; // seg — el clip se comprime para sincronizar con el vuelo

// ---- Constantes de cancha / física ----------------------------------------
const GOAL_W = 7.32;
const GOAL_H = 2.44;
const HALF_W = GOAL_W / 2;      // 3.66
const POST_R = 0.06;
const GOAL_Z = -10.5;           // línea de gol
const BALL_R = 0.11;
const GRAVITY = -9.81;

// Zonas del arco a las que apunta el tirador / se lanza el arquero.
const ZONE_X = { L: -2.55, C: 0, R: 2.55 };
const ZONE_Y = { low: 0.55, high: 1.75 };

// Alcance del arquero (radio de cobertura alrededor del punto de mano animado).
const KEEPER_REACH = 0.92;

// Colores de marca
const C_ACCENT = 0xff5f00;
const C_LIME = 0xb6ff2e;

const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
const lerp = (a, b, t) => a + (b - a) * t;
const easeOut = (t) => 1 - Math.pow(1 - t, 3);

// ---------------------------------------------------------------------------
// Texturas procedurales (sin descargas externas)
// ---------------------------------------------------------------------------
function makeCanvas(size) {
    const c = document.createElement('canvas');
    c.width = c.height = size;
    return c;
}

/** Césped con franjas de corte. */
function pitchTexture() {
    const c = makeCanvas(256);
    const g = c.getContext('2d');
    for (let i = 0; i < 8; i++) {
        g.fillStyle = i % 2 ? '#2f8f36' : '#2a8231';
        g.fillRect(0, (i * 256) / 8, 256, 256 / 8);
    }
    const tex = new THREE.CanvasTexture(c);
    tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
    tex.repeat.set(6, 10);
    tex.anisotropy = 8;
    tex.colorSpace = THREE.SRGBColorSpace;
    return tex;
}

/** Balón: base blanca con paneles oscuros tipo clásico. */
function ballTexture() {
    const c = makeCanvas(256);
    const g = c.getContext('2d');
    g.fillStyle = '#f4f4f2';
    g.fillRect(0, 0, 256, 256);
    g.fillStyle = '#15151a';
    const pent = (cx, cy, r) => {
        g.beginPath();
        for (let i = 0; i < 5; i++) {
            const a = (Math.PI * 2 * i) / 5 - Math.PI / 2;
            const x = cx + Math.cos(a) * r;
            const y = cy + Math.sin(a) * r;
            i ? g.lineTo(x, y) : g.moveTo(x, y);
        }
        g.closePath();
        g.fill();
    };
    pent(128, 128, 34);
    pent(40, 48, 22); pent(216, 48, 22);
    pent(40, 208, 22); pent(216, 208, 22);
    pent(128, 8, 18); pent(128, 248, 18);
    const tex = new THREE.CanvasTexture(c);
    tex.colorSpace = THREE.SRGBColorSpace;
    return tex;
}

/** Red: grilla blanca semitransparente (se usa como map+alphaMap). */
function netTexture() {
    const c = makeCanvas(128);
    const g = c.getContext('2d');
    g.clearRect(0, 0, 128, 128);
    g.strokeStyle = 'rgba(255,255,255,0.85)';
    g.lineWidth = 2;
    for (let i = 0; i <= 128; i += 12) {
        g.beginPath(); g.moveTo(i, 0); g.lineTo(i, 128); g.stroke();
        g.beginPath(); g.moveTo(0, i); g.lineTo(128, i); g.stroke();
    }
    const tex = new THREE.CanvasTexture(c);
    tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
    return tex;
}

// ---------------------------------------------------------------------------
// Motor principal
// ---------------------------------------------------------------------------
export async function createPenaltyGame({ canvas, container, onUpdate = () => {}, mode = 'solo', difficulty = 'medium' } = {}) {
    await RAPIER.init();

    // ---- Renderer / escena / cámara ----
    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: false, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    renderer.outputColorSpace = THREE.SRGBColorSpace;

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0a0a0e);
    scene.fog = new THREE.Fog(0x0a0a0e, 18, 40);

    const camera = new THREE.PerspectiveCamera(56, 16 / 9, 0.1, 200);
    camera.position.set(0, 1.75, 3.6);
    camera.lookAt(0, 1.05, GOAL_Z);

    // ---- Luces ----
    scene.add(new THREE.HemisphereLight(0x9fb8ff, 0x24371f, 0.55));
    const sun = new THREE.DirectionalLight(0xffffff, 2.2);
    sun.position.set(-6, 14, 4);
    sun.castShadow = true;
    sun.shadow.mapSize.set(2048, 2048);
    sun.shadow.camera.near = 1;
    sun.shadow.camera.far = 40;
    sun.shadow.camera.left = -12;
    sun.shadow.camera.right = 12;
    sun.shadow.camera.top = 14;
    sun.shadow.camera.bottom = -6;
    sun.shadow.bias = -0.0004;
    scene.add(sun);
    // Focos de estadio (frío, para dar realismo nocturno)
    const rim = new THREE.SpotLight(0xbcd2ff, 60, 45, Math.PI / 5, 0.4, 1.2);
    rim.position.set(7, 12, -4);
    rim.target.position.set(0, 0, GOAL_Z);
    scene.add(rim, rim.target);

    // ---- Piso / cancha ----
    const pitch = new THREE.Mesh(
        new THREE.PlaneGeometry(60, 60),
        new THREE.MeshStandardMaterial({ map: pitchTexture(), roughness: 0.95, metalness: 0 })
    );
    pitch.rotation.x = -Math.PI / 2;
    pitch.receiveShadow = true;
    scene.add(pitch);

    // Líneas: área y punto de penal
    const lineMat = new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.5 });
    const spot = new THREE.Mesh(new THREE.CircleGeometry(0.11, 24), lineMat);
    spot.rotation.x = -Math.PI / 2; spot.position.set(0, 0.011, 0);
    scene.add(spot);
    const areaLine = new THREE.Mesh(new THREE.RingGeometry(9.14, 9.26, 64, 1, Math.PI * 1.15, Math.PI * 0.7), lineMat);
    areaLine.rotation.x = -Math.PI / 2; areaLine.position.set(0, 0.011, GOAL_Z);
    scene.add(areaLine);

    // ---- Arco (palos + travesaño) ----
    const frameMat = new THREE.MeshStandardMaterial({ color: 0xf2f2f0, roughness: 0.35, metalness: 0.15 });
    const post = (x) => {
        const m = new THREE.Mesh(new THREE.CylinderGeometry(POST_R, POST_R, GOAL_H, 16), frameMat);
        m.position.set(x, GOAL_H / 2, GOAL_Z);
        m.castShadow = true;
        scene.add(m);
    };
    post(-HALF_W); post(HALF_W);
    const bar = new THREE.Mesh(new THREE.CylinderGeometry(POST_R, POST_R, GOAL_W + POST_R * 2, 16), frameMat);
    bar.rotation.z = Math.PI / 2;
    bar.position.set(0, GOAL_H, GOAL_Z);
    bar.castShadow = true;
    scene.add(bar);

    // ---- Red (visual) ----
    const netTex = netTexture();
    const NET_DEPTH = 1.6;
    const netMat = new THREE.MeshStandardMaterial({
        map: netTex, alphaMap: netTex, transparent: true, opacity: 0.5,
        side: THREE.DoubleSide, roughness: 1, metalness: 0, depthWrite: false,
    });
    const netBack = new THREE.Mesh(new THREE.PlaneGeometry(GOAL_W, GOAL_H), netMat);
    netBack.position.set(0, GOAL_H / 2, GOAL_Z - NET_DEPTH);
    scene.add(netBack);
    const netTop = new THREE.Mesh(new THREE.PlaneGeometry(GOAL_W, NET_DEPTH), netMat);
    netTop.rotation.x = Math.PI / 2;
    netTop.position.set(0, GOAL_H, GOAL_Z - NET_DEPTH / 2);
    scene.add(netTop);
    for (const s of [-1, 1]) {
        const side = new THREE.Mesh(new THREE.PlaneGeometry(NET_DEPTH, GOAL_H), netMat);
        side.rotation.y = Math.PI / 2;
        side.position.set(s * HALF_W, GOAL_H / 2, GOAL_Z - NET_DEPTH / 2);
        scene.add(side);
    }

    // ---- Balón ----
    const ballMesh = new THREE.Mesh(
        new THREE.SphereGeometry(BALL_R, 32, 32),
        new THREE.MeshStandardMaterial({ map: ballTexture(), roughness: 0.4, metalness: 0.05 })
    );
    ballMesh.castShadow = true;
    scene.add(ballMesh);

    // ---- Arquero (figura estilizada de primitivas) ----
    const keeper = new THREE.Group();
    const kitMat = new THREE.MeshStandardMaterial({ color: C_LIME, roughness: 0.6 });
    const skinMat = new THREE.MeshStandardMaterial({ color: 0xd9a06b, roughness: 0.7 });
    const gloveMat = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.5, emissive: 0x223300, emissiveIntensity: 0.2 });
    const torso = new THREE.Mesh(new THREE.CapsuleGeometry(0.22, 0.5, 6, 12), kitMat);
    torso.position.y = 1.05; torso.castShadow = true;
    const head = new THREE.Mesh(new THREE.SphereGeometry(0.16, 16, 16), skinMat);
    head.position.y = 1.55; head.castShadow = true;
    const legL = new THREE.Mesh(new THREE.CapsuleGeometry(0.1, 0.55, 4, 8), kitMat);
    legL.position.set(-0.13, 0.5, 0);
    const legR = legL.clone(); legR.position.x = 0.13;
    // Brazos como un grupo que "extiende" hacia la mano (se orientan en la estirada)
    const arms = new THREE.Group();
    const armGeo = new THREE.CapsuleGeometry(0.075, 0.5, 4, 8);
    const armL = new THREE.Mesh(armGeo, kitMat); armL.position.set(-0.32, 1.15, 0); armL.rotation.z = 0.5;
    const armR = new THREE.Mesh(armGeo, kitMat); armR.position.set(0.32, 1.15, 0); armR.rotation.z = -0.5;
    const gloveL = new THREE.Mesh(new THREE.SphereGeometry(0.1, 12, 12), gloveMat); gloveL.position.set(-0.5, 1.35, 0);
    const gloveR = new THREE.Mesh(new THREE.SphereGeometry(0.1, 12, 12), gloveMat); gloveR.position.set(0.5, 1.35, 0);
    arms.add(armL, armR, gloveL, gloveR);
    keeper.add(torso, head, legL, legR, arms);
    keeper.position.set(0, 0, GOAL_Z + 0.35);
    scene.add(keeper);

    // Sombra de contacto barata bajo el arquero
    const keeperShadow = new THREE.Mesh(
        new THREE.CircleGeometry(0.5, 24),
        new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0.3 })
    );
    keeperShadow.rotation.x = -Math.PI / 2;
    keeperShadow.position.set(0, 0.012, GOAL_Z + 0.35);
    scene.add(keeperShadow);

    // ---- Arquero riggeado (glTF) — reemplaza a las primitivas si carga bien ----
    // keeperRig es el grupo que trasladamos/inclinamos en la estirada; el mixer
    // solo anima los huesos internos (idle), así no peleamos con el transform raíz.
    const keeperRig = new THREE.Group();
    keeperRig.position.set(0, 0, GOAL_Z + 0.35);
    scene.add(keeperRig);

    let mixer = null, useModel = false, modelRoot = null;
    let idleAction = null, usingClip = false, currentDiveAction = null;
    const diveActions = {};   // key ('left'|'right'|'left_high'|'right_high') -> AnimationAction
    const kb = {};           // huesos del arquero
    const kbRest = {};       // cuaterniones de reposo de los huesos de brazos (para IK estable)

    new GLTFLoader().load(KEEPER_MODEL_URL, (gltf) => {
        if (disposed) { return; }
        modelRoot = gltf.scene;
        modelRoot.traverse((o) => { if (o.isMesh || o.isSkinnedMesh) { o.castShadow = true; o.frustumCulled = false; } });
        // Escalar a ~1.88 m y apoyar los pies en y=0
        const box = new THREE.Box3().setFromObject(modelRoot);
        modelRoot.scale.setScalar(1.88 / (box.max.y - box.min.y));
        const box2 = new THREE.Box3().setFromObject(modelRoot);
        modelRoot.position.y = -box2.min.y;
        modelRoot.rotation.y = Math.PI;    // mirar hacia el pateador (+Z)
        keeperRig.add(modelRoot);

        const find = (n) => modelRoot.getObjectByName(n);
        kb.armL = find('mixamorig:LeftArm');  kb.foreL = find('mixamorig:LeftForeArm');  kb.handL = find('mixamorig:LeftHand');
        kb.armR = find('mixamorig:RightArm'); kb.foreR = find('mixamorig:RightForeArm'); kb.handR = find('mixamorig:RightHand');
        [['armL', kb.armL], ['foreL', kb.foreL], ['armR', kb.armR], ['foreR', kb.foreR]]
            .forEach(([k, b]) => { if (b) kbRest[k] = b.quaternion.clone(); });

        mixer = new THREE.AnimationMixer(modelRoot);
        const idle = THREE.AnimationClip.findByName(gltf.animations, 'Idle') || gltf.animations[0];
        if (idle) { idleAction = mixer.clipAction(idle); idleAction.play(); }
        loadDiveClips();          // carga (si existen) los clips de estirada reales

        keeper.visible = false;   // ocultar primitivas
        useModel = true;
    }, undefined, (err) => { console.warn('[penalty] no cargó el modelo del arquero; uso primitivas', err); });

    // Carga los clips de estirada (animación pura). Solo intenta si el archivo
    // existe (HEAD), para no ensuciar la consola cuando aún no los pusiste.
    const _animLoader = new GLTFLoader();
    function stripPositionTracks(clip) {
        // Nos quedamos con rotaciones (la traslación del cuerpo la maneja el rig,
        // así evitamos que las unidades de Mixamo lancen al modelo lejos).
        clip.tracks = clip.tracks.filter((t) => !t.name.endsWith('.position'));
        return clip;
    }
    function loadDiveClips() {
        KEEPER_ANIMS.forEach(({ key, file }) => {
            fetch(file, { method: 'HEAD' }).then((r) => {
                if (!r.ok || disposed || !mixer) return;
                _animLoader.load(file, (g) => {
                    if (disposed || !mixer) return;
                    const clip = g.animations && g.animations[0];
                    if (!clip) return;
                    stripPositionTracks(clip);
                    const action = mixer.clipAction(clip, modelRoot);
                    action.loop = THREE.LoopOnce;
                    action.clampWhenFinished = true;
                    diveActions[key] = action;
                }, undefined, (e) => console.warn('[penalty] clip de estirada inválido:', file, e));
            }).catch(() => { /* sin red / sin archivo: respaldo procedural */ });
        });
    }
    const hasDiveClips = () => Object.keys(diveActions).length > 0;

    // ---- Retícula de puntería ----
    const reticle = new THREE.Mesh(
        new THREE.RingGeometry(0.16, 0.24, 24),
        new THREE.MeshBasicMaterial({ color: C_ACCENT, transparent: true, opacity: 0.9, side: THREE.DoubleSide })
    );
    reticle.position.set(0, 1.2, GOAL_Z + 0.02);
    scene.add(reticle);

    // =========================================================================
    // Física (Rapier)
    // =========================================================================
    const world = new RAPIER.World({ x: 0, y: GRAVITY, z: 0 });

    // Piso
    world.createCollider(
        RAPIER.ColliderDesc.cuboid(30, 0.1, 30).setTranslation(0, -0.1, 0).setRestitution(0.35).setFriction(0.8)
    );
    // Palos + travesaño (cilindros)
    const cyl = (hh, r, x, y, z, rotZ = 0) => {
        const d = RAPIER.ColliderDesc.cylinder(hh, r).setTranslation(x, y, z).setRestitution(0.55).setFriction(0.4);
        if (rotZ) { const q = new THREE.Quaternion().setFromEuler(new THREE.Euler(0, 0, rotZ)); d.setRotation({ x: q.x, y: q.y, z: q.z, w: q.w }); }
        world.createCollider(d);
    };
    cyl(GOAL_H / 2, POST_R, -HALF_W, GOAL_H / 2, GOAL_Z);
    cyl(GOAL_H / 2, POST_R, HALF_W, GOAL_H / 2, GOAL_Z);
    cyl(GOAL_W / 2, POST_R, 0, GOAL_H, GOAL_Z, Math.PI / 2);
    // Cage de red para contener el balón (poco rebote, mucha fricción)
    const net = (hx, hy, hz, x, y, z) => world.createCollider(
        RAPIER.ColliderDesc.cuboid(hx, hy, hz).setTranslation(x, y, z).setRestitution(0.05).setFriction(0.9)
    );
    net(HALF_W, GOAL_H / 2, 0.05, 0, GOAL_H / 2, GOAL_Z - NET_DEPTH);        // fondo
    net(HALF_W, 0.05, NET_DEPTH / 2, 0, GOAL_H, GOAL_Z - NET_DEPTH / 2);      // techo
    net(0.05, GOAL_H / 2, NET_DEPTH / 2, -HALF_W, GOAL_H / 2, GOAL_Z - NET_DEPTH / 2);
    net(0.05, GOAL_H / 2, NET_DEPTH / 2, HALF_W, GOAL_H / 2, GOAL_Z - NET_DEPTH / 2);

    // Balón dinámico
    const ballBody = world.createRigidBody(
        RAPIER.RigidBodyDesc.dynamic().setTranslation(0, BALL_R, 0).setLinearDamping(0.12).setAngularDamping(0.3).setCcdEnabled(true)
    );
    world.createCollider(
        RAPIER.ColliderDesc.ball(BALL_R).setRestitution(0.5).setFriction(0.6).setDensity(1.2),
        ballBody
    );

    // =========================================================================
    // Estado de juego
    // =========================================================================
    const st = {
        phase: 'aim',          // aim | charge | flight | result | gameover
        mode,                  // solo (vs IA) | duo (arquero humano)
        difficulty,
        shots: 5,
        shot: 0,
        goals: 0,
        saves: 0,
        misses: 0,
        power: 0,
        result: null,          // goal | save | miss
        message: '',
    };
    const emit = () => onUpdate({ ...st });

    // Puntería (target sobre el plano del arco)
    let aimX = 0, aimY = 1.2;
    // Potencia oscilante
    let charging = false, powerDir = 1;
    // Vuelo
    let resolved = false, flightTime = 0, curve = 0;
    // Arquero
    const keeperState = { committed: false, animT: 1, fromX: 0, fromY: 0, toX: 0, toY: 0, reactionLeft: 0 };
    let aiPlanned = null;

    const DIFF = {
        easy:   { react: 0.42, correct: 0.40, reach: 0.82 },
        medium: { react: 0.30, correct: 0.60, reach: 0.92 },
        hard:   { react: 0.20, correct: 0.80, reach: 1.02 },
    };

    // ---- Puntería con puntero (mouse / touch) ----
    const raycaster = new THREE.Raycaster();
    const ndc = new THREE.Vector2();
    const aimPlane = new THREE.Plane(new THREE.Vector3(0, 0, 1), -GOAL_Z); // z = GOAL_Z
    const hit = new THREE.Vector3();

    function pointerToAim(ev) {
        const r = canvas.getBoundingClientRect();
        const cx = ev.touches ? ev.touches[0].clientX : ev.clientX;
        const cy = ev.touches ? ev.touches[0].clientY : ev.clientY;
        ndc.x = ((cx - r.left) / r.width) * 2 - 1;
        ndc.y = -((cy - r.top) / r.height) * 2 + 1;
        raycaster.setFromCamera(ndc, camera);
        if (raycaster.ray.intersectPlane(aimPlane, hit)) {
            aimX = clamp(hit.x, -HALF_W - 0.9, HALF_W + 0.9);
            aimY = clamp(hit.y, 0.12, GOAL_H + 0.7);
        }
    }

    function onDown(ev) {
        if (st.phase !== 'aim') return;
        ev.preventDefault();
        pointerToAim(ev);
        charging = true; powerDir = 1; st.power = 0; st.phase = 'charge';
        emit();
    }
    function onMove(ev) {
        if (st.phase === 'aim' || st.phase === 'charge') pointerToAim(ev);
    }
    function onUp(ev) {
        if (st.phase !== 'charge') return;
        ev.preventDefault();
        charging = false;
        shoot();
    }

    // Curva con A/D mientras se carga
    const keys = new Set();
    function onKeyDown(e) {
        keys.add(e.key.toLowerCase());
        // Arquero humano (modo duo): teclas de estirada (solo durante el vuelo)
        if (st.mode === 'duo' && st.phase === 'flight' && !keeperState.committed) {
            const map = {
                q: ['L', 'high'], w: ['C', 'high'], e: ['R', 'high'],
                a: ['L', 'low'], s: ['C', 'low'], d: ['R', 'low'],
            };
            const z = map[e.key.toLowerCase()];
            if (z) commitKeeper(ZONE_X[z[0]], ZONE_Y[z[1]]);
        }
    }
    function onKeyUp(e) { keys.delete(e.key.toLowerCase()); }

    canvas.addEventListener('pointerdown', onDown);
    window.addEventListener('pointermove', onMove);
    window.addEventListener('pointerup', onUp);
    window.addEventListener('keydown', onKeyDown);
    window.addEventListener('keyup', onKeyUp);

    // ---- Disparo ----
    function shoot() {
        st.phase = 'flight';
        resolved = false; flightTime = 0;
        st.result = null; st.message = '';

        // Potencia -> tiempo de vuelo (más potencia = más rápido = menos tiempo).
        const p = st.power;
        const t = lerp(0.86, 0.52, p);
        const bx = 0, by = BALL_R, bz = 0;
        const vx = (aimX - bx) / t;
        const vz = (GOAL_Z - bz) / t;
        const vy = (aimY - by) / t - 0.5 * GRAVITY * t; // compensa gravedad para llegar a aimY

        // Curva: A/D mantenidos durante la carga (solo en modo 1 jugador; en 2
        // jugadores A/S/D son teclas del arquero y no deben curvar el tiro).
        curve = st.mode === 'solo' ? (keys.has('a') ? -1 : 0) + (keys.has('d') ? 1 : 0) : 0;
        curve *= lerp(4.5, 7.5, p);

        ballBody.setTranslation({ x: bx, y: by, z: bz }, true);
        ballBody.setLinvel({ x: vx, y: vy, z: vz }, true);
        ballBody.setAngvel({ x: -vz * 3, y: -curve * 2, z: 0 }, true);
        ballBody.wakeUp();

        // Arquero
        keeperState.committed = false;
        keeperState.animT = 1;
        aiPlanned = null;
        if (st.mode === 'solo') {
            const d = DIFF[st.difficulty] || DIFF.medium;
            // Zona real a la que va el balón
            const realX = aimX < -1 ? 'L' : aimX > 1 ? 'R' : 'C';
            const realY = aimY > 1.15 ? 'high' : 'low';
            let zx, zy;
            if (Math.random() < d.correct) { zx = realX; zy = realY; }
            else {
                const xs = ['L', 'C', 'R'].filter((k) => k !== realX);
                const ys = ['low', 'high'];
                zx = xs[Math.floor(Math.random() * xs.length)];
                zy = ys[Math.floor(Math.random() * ys.length)];
            }
            aiPlanned = { x: ZONE_X[zx], y: ZONE_Y[zy], reach: d.reach };
            keeperState.reactionLeft = d.react;
        }
        emit();
    }

    function commitKeeper(tx, ty) {
        keeperState.committed = true;
        keeperState.fromX = useModel ? keeperRig.position.x : keeper.position.x;
        keeperState.fromY = 0;
        keeperState.toX = tx * 0.82;   // el cuerpo no llega hasta el poste; los brazos completan
        keeperState.toY = ty;
        keeperState.animT = 0;

        // Estirada con clip real si hay uno para esta dirección/altura
        usingClip = false; currentDiveAction = null;
        if (useModel && mixer) {
            const dir = tx < -0.5 ? 'left' : tx > 0.5 ? 'right' : null;
            if (dir) {
                const action = (ty > 1.2 && diveActions[`${dir}_high`]) || diveActions[dir];
                if (action) {
                    action.reset().setDuration(DIVE_DURATION);
                    if (idleAction) idleAction.fadeOut(0.1);
                    action.fadeIn(0.1).play();
                    currentDiveAction = action;
                    usingClip = true;
                }
            }
        }
    }

    // Punto de "mano" del arquero para calcular cobertura
    const keeperHand = new THREE.Vector3();
    function keeperCoverPoint() {
        if (!keeperState.committed) { keeperHand.set(0, 0.9, GOAL_Z + 0.3); return keeperHand; }
        const t = easeOut(keeperState.animT);
        keeperHand.set(lerp(keeperState.fromX, keeperState.toX, t), lerp(0.9, keeperState.toY, t), GOAL_Z + 0.3);
        return keeperHand;
    }

    // ---- Resolución del disparo ----
    function resolve(result) {
        if (resolved) return;
        resolved = true;
        st.result = result;
        st.shot++;
        if (result === 'goal') { st.goals++; st.message = '¡GOOOL!'; }
        else if (result === 'save') { st.saves++; st.message = '¡ATAJADA!'; }
        else { st.misses++; st.message = 'AFUERA'; }
        st.phase = 'result';
        emit();
        setTimeout(() => {
            if (disposed) return;
            if (st.shot >= st.shots) {
                st.phase = 'gameover';
                st.message = st.goals > st.saves + st.misses ? '¡Gran tanda!' : 'Fin de la tanda';
                emit();
            } else {
                resetBall();
            }
        }, 1500);
    }

    function resetBall() {
        ballBody.setTranslation({ x: 0, y: BALL_R, z: 0 }, true);
        ballBody.setLinvel({ x: 0, y: 0, z: 0 }, true);
        ballBody.setAngvel({ x: 0, y: 0, z: 0 }, true);
        // Volver a idle si estábamos reproduciendo un clip de estirada
        if (mixer && usingClip) {
            if (currentDiveAction) currentDiveAction.fadeOut(0.25);
            if (idleAction) idleAction.reset().fadeIn(0.25).play();
        }
        usingClip = false; currentDiveAction = null;
        keeper.position.set(0, 0, GOAL_Z + 0.35);
        keeper.rotation.set(0, 0, 0);
        keeperRig.position.set(0, 0, GOAL_Z + 0.35);
        keeperRig.rotation.set(0, 0, 0);
        resetKeeperArms();
        keeperState.committed = false; keeperState.animT = 1;
        keeperState.fromX = 0; keeperState.toX = 0; keeperState.toY = 0.9;
        st.phase = 'aim'; st.power = 0; st.result = null; st.message = '';
        aimX = 0; aimY = 1.2;
        emit();
    }

    function fullReset() {
        st.shot = 0; st.goals = 0; st.saves = 0; st.misses = 0;
        resetBall();
    }

    // =========================================================================
    // Bucle
    // =========================================================================
    const clock = new THREE.Clock();
    let rafId = 0, disposed = false, running = true;

    // Devuelve los huesos de brazos a su pose de reposo (base estable para el IK).
    function resetKeeperArms() {
        if (kb.armL && kbRest.armL) kb.armL.quaternion.copy(kbRest.armL);
        if (kb.foreL && kbRest.foreL) kb.foreL.quaternion.copy(kbRest.foreL);
        if (kb.armR && kbRest.armR) kb.armR.quaternion.copy(kbRest.armR);
        if (kb.foreR && kbRest.foreR) kb.foreR.quaternion.copy(kbRest.foreR);
    }

    // IK de dos huesos en espacio-mundo (hombro→codo→mano) para alcanzar un punto.
    // weight (0..1) mezcla desde la pose de reposo hacia el estirado completo.
    const _a = new THREE.Vector3(), _b = new THREE.Vector3(), _c = new THREE.Vector3();
    const _qw = new THREE.Quaternion(), _dq = new THREE.Quaternion();
    function twoBoneIK(a, b, c, target, weight) {
        if (!a || !b || !c) return;
        a.getWorldPosition(_a); b.getWorldPosition(_b); c.getWorldPosition(_c);
        const lab = _a.distanceTo(_b), lcb = _b.distanceTo(_c);
        const lat = clamp(_a.distanceTo(target), 1e-3, lab + lcb - 1e-3);
        const ac = _c.clone().sub(_a), ab = _b.clone().sub(_a), bc = _c.clone().sub(_b);
        const ba = _a.clone().sub(_b), at = target.clone().sub(_a);
        if (ac.lengthSq() < 1e-8 || ab.lengthSq() < 1e-8) return;
        const nac = ac.clone().normalize();
        const acAb0 = Math.acos(clamp(nac.dot(ab.clone().normalize()), -1, 1));
        const baBc0 = Math.acos(clamp(ba.normalize().dot(bc.normalize()), -1, 1));
        const acAt0 = Math.acos(clamp(nac.dot(at.clone().normalize()), -1, 1));
        const acAb1 = Math.acos(clamp((lcb * lcb - lab * lab - lat * lat) / (-2 * lab * lat), -1, 1));
        const baBc1 = Math.acos(clamp((lat * lat - lab * lab - lcb * lcb) / (-2 * lab * lcb), -1, 1));
        const axis0 = ac.clone().cross(ab);
        if (axis0.lengthSq() < 1e-8) return;
        axis0.normalize();
        const axis1 = ac.clone().cross(at);
        const hasAim = axis1.lengthSq() > 1e-8;
        if (hasAim) axis1.normalize();
        // Convertir ejes-mundo a locales de cada hueso y aplicar los deltas (× weight)
        a.getWorldQuaternion(_qw); const invA = _qw.clone().invert();
        a.quaternion.multiply(_dq.setFromAxisAngle(axis0.clone().applyQuaternion(invA), (acAb1 - acAb0) * weight));
        if (hasAim) a.quaternion.multiply(_dq.setFromAxisAngle(axis1.clone().applyQuaternion(invA), acAt0 * weight));
        b.getWorldQuaternion(_qw); const invB = _qw.clone().invert();
        b.quaternion.multiply(_dq.setFromAxisAngle(axis0.clone().applyQuaternion(invB), (baBc1 - baBc0) * weight));
        if (Number.isNaN(a.quaternion.x) || Number.isNaN(b.quaternion.x)) { resetKeeperArms(); }
    }

    // Estira ambos brazos hacia la pelota (o el punto de cobertura) durante la estirada.
    const _ik = new THREE.Vector3();
    function applyKeeperArms(weight) {
        if (!kb.armL) return;
        resetKeeperArms();                 // base estable cada frame (evita acumulación)
        if (weight < 0.02) return;         // en reposo, mixer/idle controla los brazos
        const p = ballBody.translation();
        if (st.phase === 'flight' && p.z < GOAL_Z + 2.2) _ik.set(p.x, p.y, p.z);
        else { const c = keeperCoverPoint(); _ik.set(c.x, c.y, c.z); }
        // Matrices al día tras el mixer + reset, para que el IK lea posiciones reales.
        modelRoot.updateMatrixWorld(true);
        twoBoneIK(kb.armL, kb.foreL, kb.handL, _ik, weight);
        twoBoneIK(kb.armR, kb.foreR, kb.handR, _ik, weight);
    }

    function updateKeeperVisual() {
        const cover = keeperCoverPoint();
        const dive = keeperState.committed ? easeOut(keeperState.animT) : 0;
        const dir = Math.sign(keeperState.toX - keeperState.fromX) || 0;
        const root = useModel ? keeperRig : keeper;
        // La traslación del cuerpo hacia la zona la manejamos siempre por el rig
        // (el clip real va "in place"); el clip solo aporta la pose/inclinación.
        root.position.x = lerp(root.position.x, cover.x, 0.5);
        root.position.y = cover.y > 0.95 ? (cover.y - 0.9) * 0.5 : 0;
        if (useModel) {
            // Con clip real: el clip inclina el cuerpo y mueve los brazos → no lo pisamos.
            // Sin clip (respaldo procedural): inclinamos el rig y estiramos con IK.
            root.rotation.z = usingClip ? 0 : -dir * dive * 0.95;
            root.rotation.x = usingClip ? 0 : -dive * 0.12;
            if (!usingClip && !hasDiveClips()) applyKeeperArms(dive);
        } else {
            root.rotation.z = -dir * dive * 0.7;
        }
        keeperShadow.position.x = root.position.x;
        keeperShadow.material.opacity = 0.3 - root.position.y * 0.15;
    }

    function frame() {
        rafId = requestAnimationFrame(frame);
        if (!running) return;
        const dt = Math.min(clock.getDelta(), 0.033);

        // Retícula
        reticle.position.set(aimX, aimY, GOAL_Z + 0.03);
        reticle.visible = st.phase === 'aim' || st.phase === 'charge';

        // Potencia oscilante
        if (charging) {
            st.power += powerDir * dt * 1.4;
            if (st.power >= 1) { st.power = 1; powerDir = -1; }
            else if (st.power <= 0) { st.power = 0; powerDir = 1; }
            emit();
        }

        // Física + vuelo
        if (st.phase === 'flight') {
            flightTime += dt;
            // Fuerza Magnus (curva) mientras sube/viaja
            if (curve) ballBody.addForce({ x: curve * 0.9, y: 0, z: 0 }, true);

            // Arquero: reacción IA
            if (st.mode === 'solo' && aiPlanned && !keeperState.committed) {
                keeperState.reactionLeft -= dt;
                if (keeperState.reactionLeft <= 0) commitKeeper(aiPlanned.x, aiPlanned.y);
            }
            if (keeperState.committed && keeperState.animT < 1) {
                keeperState.animT = Math.min(1, keeperState.animT + dt / 0.26);
            }

            world.step();

            const p = ballBody.translation();
            ballMesh.position.set(p.x, p.y, p.z);
            const rot = ballBody.rotation();
            ballMesh.quaternion.set(rot.x, rot.y, rot.z, rot.w);

            // ¿Atajada? proximidad a la mano del arquero cerca de la línea
            if (!resolved && keeperState.committed && p.z < GOAL_Z + 1.15 && p.z > GOAL_Z - 0.3) {
                const cover = keeperCoverPoint();
                const reach = KEEPER_REACH * (st.mode === 'solo' && aiPlanned ? aiPlanned.reach : 1);
                const dx = p.x - cover.x, dy = p.y - cover.y;
                if (Math.hypot(dx, dy) < reach) {
                    // Rechaza el balón
                    ballBody.setLinvel({ x: dx * 6 + (Math.random() - 0.5) * 3, y: 3.5, z: 7 }, true);
                    resolve('save');
                }
            }

            // ¿Cruzó la línea?
            if (!resolved && p.z <= GOAL_Z) {
                const inFrame = Math.abs(p.x) <= HALF_W + BALL_R && p.y <= GOAL_H + BALL_R && p.y >= 0;
                resolve(inFrame ? 'goal' : 'miss');
            }
            // Timeout de seguridad
            if (!resolved && flightTime > 3) resolve(p.z < GOAL_Z + 0.5 ? 'goal' : 'miss');
        } else {
            // Fuera de vuelo igual avanzamos la física para que el balón asiente en la red
            world.step();
            const p = ballBody.translation();
            ballMesh.position.set(p.x, p.y, p.z);
            const rot = ballBody.rotation();
            ballMesh.quaternion.set(rot.x, rot.y, rot.z, rot.w);
        }

        if (mixer) mixer.update(dt);   // idle del modelo (antes de aplicar IK/pose)
        updateKeeperVisual();
        renderer.render(scene, camera);
    }

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

    fullReset();
    frame();

    // =========================================================================
    // API pública
    // =========================================================================
    function dispose() {
        disposed = true; running = false;
        cancelAnimationFrame(rafId);
        ro.disconnect();
        canvas.removeEventListener('pointerdown', onDown);
        window.removeEventListener('pointermove', onMove);
        window.removeEventListener('pointerup', onUp);
        window.removeEventListener('keydown', onKeyDown);
        window.removeEventListener('keyup', onKeyUp);
        if (mixer) mixer.stopAllAction();
        scene.traverse((o) => {
            if (o.geometry) o.geometry.dispose();
            if (o.material) {
                const mats = Array.isArray(o.material) ? o.material : [o.material];
                mats.forEach((m) => { if (m.map) m.map.dispose(); m.dispose(); });
            }
        });
        renderer.dispose();
        world.free();
    }

    return {
        get state() { return { ...st }; },
        setMode(m) { st.mode = m; emit(); },
        setDifficulty(d) { st.difficulty = d; emit(); },
        reset: fullReset,
        pause() { running = false; },
        resume() { if (!disposed) { running = true; clock.getDelta(); } },
        dispose,
    };
}
