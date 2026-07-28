import { createApp, h, ref, onMounted } from 'vue';
import ChatBot from '../resources/js/Components/ChatBot.vue';
import '../resources/css/app.css';

const API = window.FIFARDOS_API_URL || '';

const App = {
    setup() {
        const tournaments = ref([]);
        const topScorers = ref([]);
        const loading = ref(true);

        onMounted(async () => {
            try {
                const [tRes, sRes] = await Promise.all([
                    fetch(`${API}/api/tournaments`),
                    fetch(`${API}/api/top-scorers`),
                ]);
                tournaments.value = (await tRes.json()).data || [];
                topScorers.value = (await sRes.json()).data || [];
            } catch {}
            loading.value = false;
        });

        return { tournaments, topScorers, loading };
    },
    render() {
        const { tournaments, topScorers, loading } = this;
        return h('div', { class: 'min-h-screen bg-[#0a0a0f] text-white font-sans' }, [
            h('header', { class: 'border-b border-white/10' }, [
                h('div', { class: 'max-w-4xl mx-auto px-4 py-6 text-center' }, [
                    h('h1', { class: 'text-3xl md:text-4xl font-elite-condensed font-black uppercase tracking-[0.2em] text-[#f97316]' }, 'FIFARDOS ELITE'),
                    h('p', { class: 'text-white/40 text-sm mt-1' }, 'Liga competitiva de FIFA'),
                ]),
            ]),
            h('main', { class: 'max-w-4xl mx-auto px-4 py-8' }, [
                loading.value
                    ? h('p', { class: 'text-center text-white/30 py-12' }, 'Cargando...')
                    : h('div', { class: 'grid gap-8' }, [
                        tournaments.value.length > 0 && h('section', [
                            h('h2', { class: 'text-lg font-elite-condensed font-bold uppercase tracking-wider text-white/60 mb-3' }, 'ÚLTIMOS TORNEOS'),
                            h('div', { class: 'grid gap-3' }, tournaments.value.slice(0, 3).map(t =>
                                h('div', { class: 'bg-white/5 border border-white/10 rounded-lg px-4 py-3' }, [
                                    h('p', { class: 'font-bold' }, t.name || t.title),
                                    h('p', { class: 'text-sm text-white/40' }, t.status || 'activo'),
                                ])
                            )),
                        ]),
                        topScorers.value.length > 0 && h('section', [
                            h('h2', { class: 'text-lg font-elite-condensed font-bold uppercase tracking-wider text-white/60 mb-3' }, 'MÁXIMOS GOLEADORES'),
                            h('div', { class: 'grid gap-2' }, topScorers.value.slice(0, 5).map(s =>
                                h('div', { class: 'flex justify-between bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-sm' }, [
                                    h('span', s.player_name || s.name),
                                    h('span', { class: 'text-[#f97316] font-bold' }, `${s.goals || 0} goles`),
                                ])
                            )),
                        ]),
                    ]),
                h('div', { class: 'mt-12' }, [
                    h('p', { class: 'text-center text-white/20 text-xs' }, 'Usa el asistente para consultar torneos, posiciones, premios y más.'),
                ]),
            ]),
            h(ChatBot),
        ]);
    },
};

const app = createApp(App);
app.mount('#app');
