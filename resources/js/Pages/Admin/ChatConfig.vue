<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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
        <Head title="Configurar ChatBot" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-white">Configurar ChatBot</h2>
                <Link :href="route('dashboard.api-docs')"
                      class="text-sm text-elite-secondary hover:underline">
                    ← Volver
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
                    <h3 class="font-elite-condensed font-bold text-lg text-white">Prompt del sistema</h3>
                    <p class="text-xs text-elite-primary/40">Define la personalidad, reglas y conocimientos del asistente. El contexto de torneos, standings y premios se inyectará automáticamente después de este prompt.</p>
                    <textarea v-model="form.system_prompt"
                              rows="10"
                              class="w-full bg-white/5 border border-elite-outline/20 rounded-lg px-4 py-3 text-sm text-white
                                     placeholder:text-elite-primary/30 focus:outline-none focus:border-elite-secondary/50
                                     font-mono leading-relaxed resize-y"
                              placeholder="Escribe las instrucciones del agente..."></textarea>
                    <p class="text-xs text-elite-primary/30">Máx. 5000 caracteres · Usa este espacio para decirle cómo comportarse, qué responder y qué evitar.</p>
                </div>

                <div class="glass-panel p-6 space-y-4">
                    <h3 class="font-elite-condensed font-bold text-lg text-white">Temas prohibidos</h3>
                    <p class="text-xs text-elite-primary/40">Indica temas que el asistente NO debe tocar (política, religión, etc.). Se inyectará como advertencia en el prompt.</p>
                    <textarea v-model="form.forbidden_topics"
                              rows="3"
                              class="w-full bg-white/5 border border-elite-outline/20 rounded-lg px-4 py-3 text-sm text-white
                                     placeholder:text-elite-primary/30 focus:outline-none focus:border-elite-secondary/50
                                     font-mono leading-relaxed resize-y"
                              placeholder="Ej: política, religión, datos personales..."></textarea>
                </div>

                <div class="glass-panel p-6 space-y-4">
                    <h3 class="font-elite-condensed font-bold text-lg text-white">Configuración del modelo</h3>

                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                            <div class="w-9 h-5 bg-white/10 rounded-full peer peer-checked:bg-elite-secondary peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-black after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                        </label>
                        <span class="text-sm text-white/70">ChatBot activo</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-elite-primary/40 mb-1">Max tokens</label>
                            <input type="number" v-model.number="form.max_tokens" min="100" max="2000"
                                   class="w-full bg-white/5 border border-elite-outline/20 rounded-lg px-4 py-2.5 text-sm text-white
                                          focus:outline-none focus:border-elite-secondary/50">
                        </div>
                        <div>
                            <label class="block text-xs text-elite-primary/40 mb-1">Temperatura (0-2)</label>
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
                        {{ form.processing ? 'GUARDANDO...' : 'GUARDAR' }}
                    </button>
                </div>
            </form>

            <div class="mt-8 glass-panel p-6">
                <h3 class="font-elite-condensed font-bold text-lg text-white mb-3">📦 Datos que se inyectan automáticamente</h3>
                <ul class="text-xs text-elite-primary/50 space-y-1.5">
                    <li>• Todos los torneos con su estado, jugadores, fecha</li>
                    <li>• Tabla de posiciones de cada torneo (con partidos disputados)</li>
                    <li>• Goleadores (jugadores con más goles a favor)</li>
                    <li>• Premios configurados para cada torneo</li>
                    <li>• Información de registro y formato del torneo</li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
