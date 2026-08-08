import { createI18n } from 'vue-i18n';

const SUPPORTED = ['es', 'en'];
const STORAGE_KEY = 'fifardos_locale';

export function detectLocale() {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved && SUPPORTED.includes(saved)) return saved;
    } catch (e) { /* noop */ }
    const nav = (typeof navigator !== 'undefined' && (navigator.language || navigator.userLanguage)) || 'es';
    return nav.toLowerCase().startsWith('en') ? 'en' : 'es';
}

const messages = {
    es: {
        common: {
            login: 'Iniciar sesión', register: 'Registrate', createAccount: 'Crear cuenta',
            createTournament: 'Crear torneo', logout: 'Cerrar sesión', profile: 'Perfil',
            close: 'Cerrar',
        },
        nav: {
            tournaments: 'Torneos', new: 'Nuevo', apiMcp: 'API · MCP', reports: 'Reportes',
            chatbot: 'Chatbot', users: 'Usuarios', myTournaments: 'Mis Torneos', newTournament: 'Nuevo Torneo',
        },
        landing: {
            badge: 'Gratis · sin instalar nada',
            heroL1: 'Se armó', heroL2: 'el torneo', heroL3: 'de la casa.',
            lead: 'Fútbol, básquet, vóley, tenis, ping pong y más. Grupos, resultados, tabla en vivo y eliminatorias automáticas para cualquier deporte, en consola o en cancha. Vos jugás, nosotros llevamos las cuentas.',
            ctaArm: 'Armar mi torneo', ctaLogin: 'Iniciar sesión',
            statSeconds: 's', statSecondsLbl: 'en armar el fixture',
            stat2: '2–32', stat2Lbl: 'jugadores por torneo', stat3: '$0', stat3Lbl: 'para siempre',
            navComoVa: 'Cómo va', navModos: 'Modos', navEnVivo: 'En vivo', navFaq: 'FAQ',
            sportsKicker: 'Todas las disciplinas',
            sportsH1: 'Cualquier deporte,', sportsH2: 'una sola plataforma.',
            sportsNote: 'De deportes individuales a los de equipos, en consola o en campo físico. Marcadores por goles, puntos o sets, con reglas configurables por torneo.',
            sports: [
                { icon: '🎮', name: 'FIFA / Consola' },
                { icon: '⚽', name: 'Fútbol' },
                { icon: '🥅', name: 'Futsal' },
                { icon: '🏀', name: 'Básquet' },
                { icon: '🏐', name: 'Vóley' },
                { icon: '🎾', name: 'Tenis' },
                { icon: '🏓', name: 'Pádel' },
                { icon: '🏸', name: 'Pickleball' },
                { icon: '🤾', name: 'Handball' },
                { icon: '🏉', name: 'Rugby' },
            ],
            liveTag: 'Tabla en vivo', liveMeta: 'Grupo A · J4',
            thPlayer: 'Jugador', boot: 'Líder', bootVal: 'Nico — 11 goles',
            howKicker: 'Cómo funciona', howH1: 'Cuatro pasos', howH2: 'y a jugar',
            howNote: 'Una cuenta gratis para organizar cualquier deporte, sin app ni planilla de Excel del primo que se ofende.',
            steps: [
                { t: 'Cargá a los participantes', d: 'Escribí quién juega: jugadores o equipos, dos personas o treinta y dos.' },
                { t: 'Elegí el deporte y el formato', d: 'Cualquier deporte, en consola o cancha. Liga, grupos + eliminatorias o mata-mata directo.' },
                { t: 'Anotá el marcador', d: 'Termina el partido, cargás el resultado (goles, puntos o sets) y la tabla se acomoda sola.' },
                { t: 'Coroná al campeón', d: 'Bracket, líderes de cada deporte y palmarés listos para el chat del grupo.' },
            ],
            featKicker: 'Todo incluido', featH1: 'Todo lo que', featH2: 'el grupo pide',
            features: [
                { tag: 'Fixture', t: 'Grupos armados en segundos', d: 'Sorteo balanceado, fechas ordenadas y nadie repite rival dos veces seguidas.' },
                { tag: 'Live', t: 'Tabla que se actualiza sola', d: 'Puntos, diferencia y desempates calculados al instante según el deporte, sin planillas.' },
                { tag: 'Playoffs', t: 'Eliminatorias automáticas', d: 'Los clasificados se cruzan solos: cuartos, semis y final sin dibujar nada.' },
                { tag: 'Stats', t: 'Líderes y récords', d: 'Máximo goleador, anotador o líder en sets, según el deporte de cada torneo.' },
                { tag: 'Share', t: 'Un link para todo el grupo', d: 'Mandás el link y todos ven la tabla desde el celular; ellos no necesitan cuenta.' },
                { tag: 'Mobile', t: 'Se carga desde el sillón', d: 'Pensado para usarlo con una mano mientras el otro elige equipo.' },
            ],
            brKicker: 'Eliminatorias', brH1: 'El bracket se', brH2: 'arma solo.',
            brP: 'Cuando termina la fase de grupos, FIFARDOS cruza los clasificados y genera las llaves. Cargás el resultado y el ganador avanza al toque.',
            brLink: 'Probarlo ahora', champion: 'Campeón',
            quotes: [
                { q: 'Se terminaron las discusiones de quién iba puntero. Está escrito.', a: 'Torneo del barrio · 12 jugadores' },
                { q: 'Lo armé en el entretiempo y ya estábamos jugando la fecha 1.', a: 'Liga de oficina · 8 jugadores' },
                { q: 'El bracket salió solo y quedó mejor que el del Mundial.', a: 'Copa de vacaciones · 16 jugadores' },
            ],
            faqKicker: 'Preguntas frecuentes', faqH1: 'Preguntas', faqH2: 'rápidas',
            faqs: [
                { q: '¿Hay que registrarse?', a: 'Solo el organizador: creás tu cuenta gratis y administrás el torneo. Los demás solo abren el link que compartís, sin registrarse.' },
                { q: '¿Sirve solo para FIFA?', a: 'No: funciona para cualquier deporte, individual o por equipos (fútbol, básquet, vóley, tenis, pádel, ping pong, handball, rugby…), en consola o en cancha.' },
                { q: '¿Cuántos participantes entran?', a: 'De 2 a 32 (o más con equipos), con o sin fase de grupos, partidos de ida y vuelta opcionales.' },
                { q: '¿Es gratis de verdad?', a: 'Sí. Sin límites de torneos ni funciones bloqueadas.' },
            ],
            ctaH1: 'Dejá de discutir.', ctaH2: 'Jugá el torneo.',
            ctaP: 'Creá el torneo de tu deporte, mandá el link al grupo y que gane el mejor (o el que agarre al Madrid).',
            ctaBtn: 'Crear torneo gratis', ctaSub: 'Cuenta gratis · sin descargas · en consola o en cancha',
            footNote: 'Hecho por fanáticos, no por EA. FIFA y EA Sports FC son marcas de sus dueños.',
            footMcp: 'Integración MCP',
            navGame: 'Jugar',
            navFamily: 'Familia',
            menu: 'Menú',
        },
        penalty: {
            eyebrow: 'Minijuego 3D',
            h1: 'Tirá tu', h2: 'penal.',
            note: 'Elegí 1 o 2 jugadores. Uno patea y el otro ataja. Físicas reales en 3D, directo en el navegador.',
            p1: '1 Jugador', p2: '2 Jugadores',
            play: 'Jugar', loading: 'Cargando motor 3D…',
            failed: 'No se pudo cargar el juego 3D. Probá recargar la página.',
            duoHint: 'Mejor en compu: el arquero juega con el teclado.',
            goals: 'Goles', saves: 'Atajadas', miss: 'Afuera',
            fGoal: '¡Gooool!', fSave: '¡Atajó!', fMiss: 'Afuera',
            ctrlShoot: 'Mouse: apuntá · Mantené clic: potencia · Soltá: patear',
            ctrlCurve: 'A/D: efecto',
            ctrlKeeper: 'Arquero: Q W E (arriba) · A S D (abajo)',
            easy: 'Fácil', medium: 'Normal', hard: 'Difícil',
            reset: 'Reiniciar', again: 'Jugar de nuevo',
            overTitle: 'Fin de la tanda',
        },
        mcp: {
            eyebrow: 'MCP · Model Context Protocol', title: 'Conectá FIFARDOS a tu IA',
            lead: 'Pedile a Claude, ChatGPT o Gemini que consulte tus torneos, tablas y goleadores — o que arme un torneo por vos — vía MCP.',
            step1: 'Generá un token en API tokens dentro de la app.',
            step2: 'Agregá el servidor MCP a tu asistente (config abajo).',
            step3: 'Listo: pedile "armame un torneo con Diego, Juan y Nico".',
            noteClaude: 'Claude Desktop / Cursor / Copilot — agregá a la config de MCP:',
            noteGpt: 'ChatGPT admite conectores MCP remotos por HTTP. Usá la URL y tu token:',
            noteGemini: 'Gemini aún no tiene MCP nativo. Usá la API de agentes de FIFARDOS (o un puente MCP):',
            tools: 'Herramientas: listar torneos · tabla de posiciones · goleadores · partidos · datos de jugador · crear torneo.',
        },
    },
    en: {
        common: {
            login: 'Log in', register: 'Sign up', createAccount: 'Create account',
            createTournament: 'Create tournament', logout: 'Log out', profile: 'Profile',
            close: 'Close',
        },
        nav: {
            tournaments: 'Tournaments', new: 'New', apiMcp: 'API · MCP', reports: 'Reports',
            chatbot: 'Chatbot', users: 'Users', myTournaments: 'My tournaments', newTournament: 'New tournament',
        },
        landing: {
            badge: 'Free · nothing to install',
            heroL1: 'The house', heroL2: 'tournament', heroL3: 'is on.',
            lead: 'Football, basketball, volleyball, tennis, table tennis and more. Groups, results, live table and automatic knockouts for any sport, on console or on the field. You play, we keep the score.',
            ctaArm: 'Set up my tournament', ctaLogin: 'Log in',
            statSeconds: 's', statSecondsLbl: 'to build the fixture',
            stat2: '2–32', stat2Lbl: 'players per tournament', stat3: '$0', stat3Lbl: 'forever',
            navComoVa: 'Standings', navModos: 'Modes', navEnVivo: 'Live', navFaq: 'FAQ',
            sportsKicker: 'Every discipline',
            sportsH1: 'Any sport,', sportsH2: 'one platform.',
            sportsNote: 'From individual sports to team sports, on console or on a physical field. Scores by goals, points or sets, with rules you configure per tournament.',
            sports: [
                { icon: '🎮', name: 'FIFA / Console' },
                { icon: '⚽', name: 'Football' },
                { icon: '🥅', name: 'Futsal' },
                { icon: '🏀', name: 'Basketball' },
                { icon: '🏐', name: 'Volleyball' },
                { icon: '🎾', name: 'Tennis' },
                { icon: '🏓', name: 'Padel' },
                { icon: '🏸', name: 'Pickleball' },
                { icon: '🤾', name: 'Handball' },
                { icon: '🏉', name: 'Rugby' },
            ],
            liveTag: 'Live table', liveMeta: 'Group A · MD4',
            thPlayer: 'Player', boot: 'Leader', bootVal: 'Nico — 11 goals',
            howKicker: 'How it works', howH1: 'Four steps', howH2: 'and play',
            howNote: 'One free account to organize any sport — no app, no cousin\'s Excel spreadsheet that gets offended.',
            steps: [
                { t: 'Add the participants', d: 'Type who plays: players or teams, two people or thirty-two.' },
                { t: 'Pick the sport and format', d: 'Any sport, on console or field. League, groups + knockouts or straight bracket.' },
                { t: 'Log the score', d: 'Match ends, you enter the result (goals, points or sets) and the table sorts itself.' },
                { t: 'Crown the champion', d: 'Bracket, sport leaders and honors ready for the group chat.' },
            ],
            featKicker: 'All included', featH1: 'Everything', featH2: 'the group wants',
            features: [
                { tag: 'Fixture', t: 'Groups built in seconds', d: 'Balanced draw, ordered rounds and nobody plays the same rival twice in a row.' },
                { tag: 'Live', t: 'A table that updates itself', d: 'Points, difference and tiebreakers computed instantly by sport, no spreadsheets.' },
                { tag: 'Playoffs', t: 'Automatic knockouts', d: 'Qualifiers are matched automatically: quarters, semis and final with nothing to draw.' },
                { tag: 'Stats', t: 'Leaders and records', d: 'Top scorer, top scorer-by-points or set leader, depending on each tournament\'s sport.' },
                { tag: 'Share', t: 'One link for the whole group', d: 'Send the link and everyone sees the table from their phone; they don\'t need an account.' },
                { tag: 'Mobile', t: 'Runs from the couch', d: 'Made to use with one hand while the other picks a team.' },
            ],
            brKicker: 'Knockouts', brH1: 'The bracket', brH2: 'builds itself.',
            brP: 'When the group stage ends, FIFARDOS matches the qualifiers and generates the bracket. You log the result and the winner advances instantly.',
            brLink: 'Try it now', champion: 'Champion',
            quotes: [
                { q: 'No more arguing about who was on top. It\'s written down.', a: 'Neighborhood cup · 12 players' },
                { q: 'I set it up at halftime and we were already playing matchday 1.', a: 'Office league · 8 players' },
                { q: 'The bracket came out on its own and looked better than the World Cup\'s.', a: 'Holiday cup · 16 players' },
            ],
            faqKicker: 'FAQ', faqH1: 'Quick', faqH2: 'answers',
            faqs: [
                { q: 'Do I have to sign up?', a: 'Only the organizer: create your free account and manage the tournament. Everyone else just opens the link you share, no signup.' },
                { q: 'Does it work only for FIFA?', a: 'No: it works for any sport, individual or team (football, basketball, volleyball, tennis, padel, table tennis, handball, rugby…), on console or on the field.' },
                { q: 'How many participants fit?', a: 'From 2 to 32 (or more with teams), with or without group stage, home & away optional.' },
                { q: 'Is it really free?', a: 'Yes. No tournament limits or locked features.' },
            ],
            ctaH1: 'Stop arguing.', ctaH2: 'Play the tournament.',
            ctaP: 'Create the tournament of your sport, send the link to the group and may the best win (or whoever grabs Madrid).',
            ctaBtn: 'Create tournament free', ctaSub: 'Free account · no downloads · on console or on the field',
            footNote: 'Made by fans, not by EA. FIFA and EA Sports FC are trademarks of their owners.',
            footMcp: 'MCP integration',
            navGame: 'Play',
            navFamily: 'Family',
            menu: 'Menu',
        },
        penalty: {
            eyebrow: '3D mini-game',
            h1: 'Take your', h2: 'penalty.',
            note: 'Pick 1 or 2 players. One shoots, the other saves. Real 3D physics, right in your browser.',
            p1: '1 Player', p2: '2 Players',
            play: 'Play', loading: 'Loading 3D engine…',
            failed: 'Could not load the 3D game. Try reloading the page.',
            duoHint: 'Best on desktop: the keeper plays with the keyboard.',
            goals: 'Goals', saves: 'Saves', miss: 'Wide',
            fGoal: 'Goal!', fSave: 'Saved!', fMiss: 'Wide',
            ctrlShoot: 'Mouse: aim · Hold click: power · Release: shoot',
            ctrlCurve: 'A/D: curve',
            ctrlKeeper: 'Keeper: Q W E (high) · A S D (low)',
            easy: 'Easy', medium: 'Normal', hard: 'Hard',
            reset: 'Restart', again: 'Play again',
            overTitle: 'Shootout over',
        },
        mcp: {
            eyebrow: 'MCP · Model Context Protocol', title: 'Connect FIFARDOS to your AI',
            lead: 'Ask Claude, ChatGPT or Gemini to check your tournaments, tables and top scorers — or to set up a tournament for you — via MCP.',
            step1: 'Generate a token in API tokens inside the app.',
            step2: 'Add the MCP server to your assistant (config below).',
            step3: 'Done: ask it "set up a tournament with Diego, Juan and Nico".',
            noteClaude: 'Claude Desktop / Cursor / Copilot — add to your MCP config:',
            noteGpt: 'ChatGPT supports remote MCP connectors over HTTP. Use the URL and your token:',
            noteGemini: 'Gemini has no native MCP yet. Use the FIFARDOS agents API (or an MCP bridge):',
            tools: 'Tools: list tournaments · standings · top scorers · matches · player data · create tournament.',
        },
    },
};

export const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: detectLocale(),
    fallbackLocale: 'es',
    messages,
});

export function setLocale(locale) {
    if (!SUPPORTED.includes(locale)) return;
    i18n.global.locale.value = locale;
    try { localStorage.setItem(STORAGE_KEY, locale); } catch (e) { /* noop */ }
    if (typeof document !== 'undefined') document.documentElement.lang = locale;
}

// Sincroniza <html lang> al arrancar
if (typeof document !== 'undefined') document.documentElement.lang = i18n.global.locale.value;
