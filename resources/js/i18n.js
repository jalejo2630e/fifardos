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
            chatbot: 'Chatbot', myTournaments: 'Mis Torneos', newTournament: 'Nuevo Torneo',
        },
        landing: {
            badge: 'Gratis · sin instalar nada',
            heroL1: 'Se armó', heroL2: 'el torneo', heroL3: 'de la casa.',
            lead: 'Grupos, resultados, tabla en vivo, eliminatorias automáticas y goleador. Vos jugás el FC, nosotros llevamos las cuentas — para que nadie discuta quién iba puntero.',
            ctaArm: 'Armar mi torneo', ctaLogin: 'Iniciar sesión',
            statSeconds: 's', statSecondsLbl: 'en armar el fixture',
            stat2: '2–32', stat2Lbl: 'jugadores por torneo', stat3: '$0', stat3Lbl: 'para siempre',
            navComoVa: 'Cómo va', navModos: 'Modos', navEnVivo: 'En vivo', navFaq: 'FAQ',
            liveTag: 'Tabla en vivo', liveMeta: 'Grupo A · J4',
            thPlayer: 'Jugador', boot: 'Bota de oro', bootVal: 'Nico — 11 goles',
            howKicker: 'Cómo funciona', howH1: 'Cuatro pasos', howH2: 'y a jugar',
            howNote: 'Una cuenta gratis para organizar, sin app ni planilla de Excel del primo que se ofende.',
            steps: [
                { t: 'Cargá los nombres', d: 'Escribí quién juega y con qué equipo. Dos personas o treinta y dos.' },
                { t: 'Elegí el formato', d: 'Liga, grupos + eliminatorias o mata-mata directo. Ida y vuelta opcional.' },
                { t: 'Anotá resultados', d: 'Termina el partido, cargás el 3-2 y la tabla se acomoda sola.' },
                { t: 'Coroná al campeón', d: 'Bracket, goleador y palmarés listos para el chat del grupo.' },
            ],
            featKicker: 'Todo incluido', featH1: 'Todo lo que', featH2: 'el grupo pide',
            features: [
                { tag: 'Fixture', t: 'Grupos armados en segundos', d: 'Sorteo balanceado, fechas ordenadas y nadie repite rival dos veces seguidas.' },
                { tag: 'Live', t: 'Tabla que se actualiza sola', d: 'Puntos, diferencia de gol y desempates calculados al instante, sin planillas.' },
                { tag: 'Playoffs', t: 'Eliminatorias automáticas', d: 'Los clasificados se cruzan solos: cuartos, semis y final sin dibujar nada.' },
                { tag: 'Stats', t: 'Bota de oro y récords', d: 'Goleador, mejor defensa, goleadas históricas y la racha del que no gana nunca.' },
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
                { q: '¿Sirve para FIFA viejo?', a: 'Sirve para cualquier edición: FIFA, EA Sports FC, y también para PES si insistís.' },
                { q: '¿Cuántos jugadores entran?', a: 'De 2 a 32, con o sin fase de grupos, partidos de ida y vuelta opcionales.' },
                { q: '¿Es gratis de verdad?', a: 'Sí. Sin límites de torneos ni funciones bloqueadas.' },
            ],
            ctaH1: 'Dejá de discutir.', ctaH2: 'Jugá el torneo.',
            ctaP: 'Creá el torneo, mandá el link al grupo y que gane el mejor (o el que agarre al Madrid).',
            ctaBtn: 'Crear torneo gratis', ctaSub: 'Cuenta gratis · sin descargas · PS5, Xbox y PC',
            footNote: 'Hecho por fanáticos, no por EA. FIFA y EA Sports FC son marcas de sus dueños.',
            footMcp: 'Integración MCP',
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
            chatbot: 'Chatbot', myTournaments: 'My tournaments', newTournament: 'New tournament',
        },
        landing: {
            badge: 'Free · nothing to install',
            heroL1: 'The house', heroL2: 'tournament', heroL3: 'is on.',
            lead: 'Groups, results, live table, automatic knockouts and top scorer. You play FC, we keep the score — so nobody argues about who was on top.',
            ctaArm: 'Set up my tournament', ctaLogin: 'Log in',
            statSeconds: 's', statSecondsLbl: 'to build the fixture',
            stat2: '2–32', stat2Lbl: 'players per tournament', stat3: '$0', stat3Lbl: 'forever',
            navComoVa: 'Standings', navModos: 'Modes', navEnVivo: 'Live', navFaq: 'FAQ',
            liveTag: 'Live table', liveMeta: 'Group A · MD4',
            thPlayer: 'Player', boot: 'Golden boot', bootVal: 'Nico — 11 goals',
            howKicker: 'How it works', howH1: 'Four steps', howH2: 'and play',
            howNote: 'One free account to organize — no app, no cousin\'s Excel spreadsheet that gets offended.',
            steps: [
                { t: 'Add the names', d: 'Type who plays and with which team. Two people or thirty-two.' },
                { t: 'Pick the format', d: 'League, groups + knockouts or straight bracket. Home & away optional.' },
                { t: 'Log results', d: 'Match ends, you enter the 3-2 and the table sorts itself.' },
                { t: 'Crown the champion', d: 'Bracket, top scorer and honors ready for the group chat.' },
            ],
            featKicker: 'All included', featH1: 'Everything', featH2: 'the group wants',
            features: [
                { tag: 'Fixture', t: 'Groups built in seconds', d: 'Balanced draw, ordered rounds and nobody plays the same rival twice in a row.' },
                { tag: 'Live', t: 'A table that updates itself', d: 'Points, goal difference and tiebreakers computed instantly, no spreadsheets.' },
                { tag: 'Playoffs', t: 'Automatic knockouts', d: 'Qualifiers are matched automatically: quarters, semis and final with nothing to draw.' },
                { tag: 'Stats', t: 'Golden boot and records', d: 'Top scorer, best defense, historic thrashings and the streak of the one who never wins.' },
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
                { q: 'Does it work for old FIFA?', a: 'It works for any edition: FIFA, EA Sports FC, and even PES if you insist.' },
                { q: 'How many players fit?', a: 'From 2 to 32, with or without group stage, home & away optional.' },
                { q: 'Is it really free?', a: 'Yes. No tournament limits or locked features.' },
            ],
            ctaH1: 'Stop arguing.', ctaH2: 'Play the tournament.',
            ctaP: 'Create the tournament, send the link to the group and may the best win (or whoever grabs Madrid).',
            ctaBtn: 'Create tournament free', ctaSub: 'Free account · no downloads · PS5, Xbox and PC',
            footNote: 'Made by fans, not by EA. FIFA and EA Sports FC are trademarks of their owners.',
            footMcp: 'MCP integration',
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
