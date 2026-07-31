<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n({
    useScope: 'local',
    messages: {
        es: {
            headTitle: 'Configurar ChatBot',
            pageTitle: 'Configurar ChatBot',
            back: '← Volver',
            systemPrompt: 'Prompt del sistema',
            systemPromptDesc: 'Define la personalidad, reglas y conocimientos del asistente. El contexto de torneos, standings y premios se inyectará automáticamente después de este prompt.',
            systemPromptPlaceholder: 'Escribe las instrucciones del agente...',
            systemPromptHint: 'Máx. 5000 caracteres · Usa este espacio para decirle cómo comportarse, qué responder y qué evitar.',
            forbiddenTopics: 'Temas prohibidos',
            forbiddenTopicsDesc: 'Indica temas que el asistente NO debe tocar (política, religión, etc.). Se inyectará como advertencia en el prompt.',
            forbiddenTopicsPlaceholder: 'Ej: política, religión, datos personales...',
            modelConfig: 'Configuración del modelo',
            chatbotActive: 'ChatBot activo',
            maxTokens: 'Max tokens',
            temperature: 'Temperatura (0-2)',
            saving: 'GUARDANDO...',
            save: 'GUARDAR',
            injectedTitle: '📦 Datos que se inyectan automáticamente',
            injected1: '• Todos los torneos con su estado, jugadores, fecha',
            injected2: '• Tabla de posiciones de cada torneo (con partidos disputados)',
            injected3: '• Líderes de cada torneo (más goles, puntos o sets según el deporte)',
            injected4: '• Premios configurados para cada torneo',
            injected5: '• Información de registro y formato del torneo',
        },
        en: {
            headTitle: 'Configure ChatBot',
            pageTitle: 'Configure ChatBot',
            back: '← Back',
            systemPrompt: 'System prompt',
            systemPromptDesc: "Define the assistant's personality, rules and knowledge. The context of tournaments, standings and prizes will be injected automatically after this prompt.",
            systemPromptPlaceholder: "Write the agent's instructions...",
            systemPromptHint: 'Max. 5000 characters · Use this space to tell it how to behave, what to answer and what to avoid.',
            forbiddenTopics: 'Forbidden topics',
            forbiddenTopicsDesc: 'Indicate topics the assistant must NOT touch (politics, religion, etc.). It will be injected as a warning in the prompt.',
            forbiddenTopicsPlaceholder: 'E.g.: politics, religion, personal data...',
            modelConfig: 'Model configuration',
            chatbotActive: 'ChatBot active',
            maxTokens: 'Max tokens',
            temperature: 'Temperature (0-2)',
            saving: 'SAVING...',
            save: 'SAVE',
            injectedTitle: '📦 Data injected automatically',
            injected1: '• All tournaments with their status, players, date',
            injected2: '• Standings table of each tournament (with matches played)',
            injected3: '• Leaders of each tournament (most goals, points or sets depending on the sport)',
            injected4: '• Prizes configured for each tournament',
            injected5: '• Tournament registration and format information',
        },
    },
});

const props = defineProps({
    config: Object,
});

const form = useForm({
    system_prompt: props.config.system_prompt,
    forbidden_topics: props.config.forbidden_topics || '',
    is_active: props.config.is_active,
    max_tokens: props.config.max_tokens,
    temperature: props.config.temperature,
});

function submit() {
    form.put(route('admin.chat-config.update'));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('headTitle')" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-white">{{ t('pageTitle') }}</h2>
                <Link :href="route('dashboard.api-docs')"
                      class="text-sm text-elite-secondary hover:underline">
                    {{ t('back') }}
                </Link>
            </div>
        </template>

        <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div v-if="$page.props.flash?.success"
                 class="mb-6 p-4 rounded-lg bg-green-500/10 border border-green-500/30 text-green-400 text-sm">
                {{ $page.props.flash.success }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="glass-panel p-6 space-y-4">
                    <h3 class="font-elite-condensed font-bold text-lg text-white">{{ t('systemPrompt') }}</h3>
                    <p class="text-xs text-elite-primary/40">{{ t('systemPromptDesc') }}</p>
                    <textarea v-model="form.system_prompt"
                              rows="10"
                              class="w-full bg-white/5 border border-elite-outline/20 rounded-lg px-4 py-3 text-sm text-white
                                     placeholder:text-elite-primary/30 focus:outline-none focus:border-elite-secondary/50
                                     font-mono leading-relaxed resize-y"
                              :placeholder="t('systemPromptPlaceholder')"></textarea>
                    <p class="text-xs text-elite-primary/30">{{ t('systemPromptHint') }}</p>
                </div>

                <div class="glass-panel p-6 space-y-4">
                    <h3 class="font-elite-condensed font-bold text-lg text-white">{{ t('forbiddenTopics') }}</h3>
                    <p class="text-xs text-elite-primary/40">{{ t('forbiddenTopicsDesc') }}</p>
                    <textarea v-model="form.forbidden_topics"
                              rows="3"
                              class="w-full bg-white/5 border border-elite-outline/20 rounded-lg px-4 py-3 text-sm text-white
                                     placeholder:text-elite-primary/30 focus:outline-none focus:border-elite-secondary/50
                                     font-mono leading-relaxed resize-y"
                              :placeholder="t('forbiddenTopicsPlaceholder')"></textarea>
                </div>

                <div class="glass-panel p-6 space-y-4">
                    <h3 class="font-elite-condensed font-bold text-lg text-white">{{ t('modelConfig') }}</h3>

                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                            <div class="w-9 h-5 bg-white/10 rounded-full peer peer-checked:bg-elite-secondary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-black after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                        </label>
                        <span class="text-sm text-white/70">{{ t('chatbotActive') }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-elite-primary/40 mb-1">{{ t('maxTokens') }}</label>
                            <input type="number" v-model.number="form.max_tokens" min="100" max="2000"
                                   class="w-full bg-white/5 border border-elite-outline/20 rounded-lg px-4 py-2.5 text-sm text-white
                                          focus:outline-none focus:border-elite-secondary/50">
                        </div>
                        <div>
                            <label class="block text-xs text-elite-primary/40 mb-1">{{ t('temperature') }}</label>
                            <input type="number" v-model.number="form.temperature" min="0" max="2" step="0.1"
                                   class="w-full bg-white/5 border border-elite-outline/20 rounded-lg px-4 py-2.5 text-sm text-white
                                          focus:outline-none focus:border-elite-secondary/50">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing"
                            class="px-8 py-3 rounded-xl bg-elite-secondary text-black font-bold font-elite-condensed
                                   text-sm uppercase tracking-widest hover:brightness-110
                                   transition-all duration-200 disabled:opacity-40">
                        {{ form.processing ? t('saving') : t('save') }}
                    </button>
                </div>
            </form>

            <div class="mt-8 glass-panel p-6">
                <h3 class="font-elite-condensed font-bold text-lg text-white mb-3">{{ t('injectedTitle') }}</h3>
                <ul class="text-xs text-elite-primary/50 space-y-1.5">
                    <li>{{ t('injected1') }}</li>
                    <li>{{ t('injected2') }}</li>
                    <li>{{ t('injected3') }}</li>
                    <li>{{ t('injected4') }}</li>
                    <li>{{ t('injected5') }}</li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Retheme esports (landing) para páginas de administración */
:deep(.ucl-card) { background: #0e0e11; border: 1px solid rgba(255,255,255,.1); border-radius: 0; box-shadow: none; }
:deep(.ucl-card)::before { display: none; }
:deep(.ucl-title-lg), :deep(.font-condensed) { font-family: 'Anton', 'Bebas Neue', sans-serif; letter-spacing: 0; }
h1, h2 { font-family: 'Anton', 'Bebas Neue', sans-serif; text-transform: uppercase; letter-spacing: -.5px; }
</style>
