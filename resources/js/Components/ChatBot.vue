<script setup>
import { ref, watch, nextTick, onMounted } from 'vue';

const open = ref(false);
const messages = ref([]);
const input = ref('');
const loading = ref(false);
const history = ref([]);
const chatRef = ref(null);
const STORAGE_KEY = 'fifardos_chat';

const suggestions = [
    '¿Qué torneos hay?',
    '¿Quién es el máximo goleador?',
    'Tabla de posiciones',
    '¿Cómo me registro?',
    '¿Cuáles son los premios?',
    'Últimos partidos',
];

onMounted(() => {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
        try {
            const parsed = JSON.parse(saved);
            messages.value = parsed.messages || [];
            history.value = parsed.history || [];
        } catch {}
    }
    if (messages.value.length === 0) {
        messages.value.push({ role: 'bot', text: '¡Hola! Soy el asistente de FIFARDOS ELITE. ¿En qué puedo ayudarte?' });
    }
});

watch(messages, () => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
        messages: messages.value,
        history: history.value,
    }));
}, { deep: true });

watch(messages, () => {
    nextTick(() => {
        if (chatRef.value) chatRef.value.scrollTop = chatRef.value.scrollHeight;
    });
}, { deep: true });

function clearChat() {
    messages.value = [];
    history.value = [];
    localStorage.removeItem(STORAGE_KEY);
    messages.value.push({ role: 'bot', text: '¡Hola! Soy el asistente de FIFARDOS ELITE. ¿En qué puedo ayudarte?' });
}

async function send(msg) {
    const text = (msg || input.value).trim();
    if (!text || loading.value) return;
    if (!msg) input.value = '';
    messages.value.push({ role: 'user', text });
    loading.value = true;
    try {
        const res = await fetch('/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
            body: JSON.stringify({ message: text, history: history.value }),
        });
        const data = await res.json();
        messages.value.push({ role: 'bot', text: data.reply || 'Sin respuesta.' });
        if (data.history) history.value = data.history;
    } catch {
        messages.value.push({ role: 'bot', text: 'Error de conexión. Intenta de nuevo.' });
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50">
        <button @click="open = !open"
                class="w-14 h-14 rounded-full bg-elite-secondary text-black shadow-xl shadow-elite-secondary/30
                       hover:brightness-110 transition-all duration-200 flex items-center justify-center">
            <svg v-if="!open" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <Transition name="chat">
            <div v-if="open"
                 class="absolute bottom-16 right-0 w-80 sm:w-96 h-[32rem] glass-panel border border-elite-outline/30
                        flex flex-col overflow-hidden shadow-2xl">
                <!-- Header -->
                <div class="px-4 py-3 border-b border-elite-outline/20 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-elite-secondary animate-pulse"></div>
                        <span class="text-sm font-elite-condensed font-bold uppercase tracking-wider text-white">Asistente FIFARDOS</span>
                    </div>
                    <button @click="clearChat" title="Limpiar chat"
                            class="text-white/30 hover:text-white/70 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

                <!-- Messages -->
                <div ref="chatRef" class="flex-1 overflow-y-auto p-4 space-y-3 scrollbar-thin">
                    <div v-for="(msg, i) in messages" :key="i"
                         class="flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div class="max-w-[85%] px-3 py-2 rounded-xl text-sm leading-relaxed whitespace-pre-wrap"
                             :class="msg.role === 'user'
                                 ? 'bg-elite-secondary text-black rounded-br-sm'
                                 : 'bg-white/5 text-elite-primary/90 rounded-bl-sm'">
                            {{ msg.text }}
                        </div>
                    </div>
                    <div v-if="loading" class="flex justify-start">
                        <div class="bg-white/5 px-3 py-2 rounded-xl rounded-bl-sm text-sm text-elite-primary/50 flex gap-1">
                            <span class="animate-bounce [animation-delay:0ms]">.</span>
                            <span class="animate-bounce [animation-delay:150ms]">.</span>
                            <span class="animate-bounce [animation-delay:300ms]">.</span>
                        </div>
                    </div>

                    <!-- Suggestions (only show when empty history) -->
                    <div v-if="messages.length === 1 && !loading" class="pt-3 space-y-2">
                        <p class="text-[10px] text-elite-primary/30 uppercase tracking-wider text-center">Preguntas rápidas</p>
                        <div class="flex flex-wrap gap-1.5 justify-center">
                            <button v-for="(s, si) in suggestions" :key="si" @click="send(s)"
                                    class="text-[11px] px-2.5 py-1.5 rounded-full bg-white/5 border border-elite-outline/20
                                           text-elite-primary/60 hover:text-white hover:bg-white/10 hover:border-elite-secondary/30
                                           transition-all duration-200 whitespace-nowrap">
                                {{ s }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <form @submit.prevent="send()" class="border-t border-elite-outline/20 p-3 flex gap-2">
                    <input v-model="input"
                           type="text"
                           placeholder="Escribe un mensaje..."
                           class="flex-1 bg-white/5 border border-elite-outline/20 rounded-lg px-3 py-2 text-sm
                                  text-white placeholder:text-elite-primary/30 focus:outline-none focus:border-elite-secondary/50
                                  transition-colors">
                    <button type="submit" :disabled="loading || !input.trim()"
                            class="px-3 py-2 rounded-lg bg-elite-secondary text-black font-bold text-sm
                                   hover:brightness-110 transition-all disabled:opacity-40">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-7 7m7-7l7 7" />
                        </svg>
                    </button>
                </form>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.chat-enter-active { transition: all 0.2s ease-out; }
.chat-leave-active { transition: all 0.15s ease-in; }
.chat-enter-from, .chat-leave-to { opacity: 0; transform: translateY(10px) scale(0.96); }

.scrollbar-thin::-webkit-scrollbar { width: 4px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 99px; }
</style>
