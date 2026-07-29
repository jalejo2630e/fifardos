<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const page = usePage();

const props = defineProps({
    endpoints: Array,
    baseUrl: String,
    tokens: Array,
});

const activeTab = ref('docs');
const search = ref('');
const copiedId = ref(null);
const newTokenName = ref('');
const showTokenModal = ref(false);
const generatedToken = ref('');
const generating = ref(false);
const revokingId = ref(null);

const filtered = computed(() => {
    if (!search.value) return props.endpoints;
    const q = search.value.toLowerCase();
    return props.endpoints.filter(e =>
        e.method.toLowerCase().includes(q) ||
        e.path.toLowerCase().includes(q) ||
        e.description.toLowerCase().includes(q)
    );
});

function generateCurl(ep) {
    let cleanPath = ep.path.replace('/api/agent', '');
    let curl = `curl -X ${ep.method} ${props.baseUrl}${cleanPath}`;
    curl += ` \\\n  -H "Authorization: Bearer TU_TOKEN_AQUI"`;
    if (ep.method === 'POST') {
        curl += ` \\\n  -H "Content-Type: application/json"`;
        const hasBody = ep.parameters?.some(p => p.in === 'body');
        if (hasBody) {
            const bodyParams = ep.parameters.filter(p => p.in === 'body');
            const body = Object.fromEntries(bodyParams.map(p => [p.name, `"${p.type}"`]));
            curl += ` \\\n  -d '${JSON.stringify(body)}'`;
        }
    }
    return curl;
}

function formatJson(obj) {
    try {
        return JSON.stringify(obj, null, 4);
    } catch {
        return String(obj);
    }
}

function copy(text, id) {
    navigator.clipboard.writeText(text).then(() => {
        copiedId.value = id;
        setTimeout(() => { copiedId.value = null; }, 2000);
    });
}

const methodColors = {
    GET: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
    POST: 'bg-blue-500/20 text-blue-300 border-blue-500/30',
};

function createToken() {
    if (!newTokenName.value.trim()) return;
    generating.value = true;
    router.post(route('api-tokens.store'), { name: newTokenName.value }, {
        preserveScroll: true,
        onSuccess: () => {
            newTokenName.value = '';
            generating.value = false;
        },
        onError: () => {
            generating.value = false;
        },
    });
}

watch(() => page.props.flash?.token, (val) => {
    if (val) {
        generatedToken.value = val;
        showTokenModal.value = true;
    }
});

function revokeToken(id) {
    if (!confirm('¿Estás seguro de que quieres revocar este token? Los agentes que lo usen dejarán de funcionar inmediatamente.')) return;
    revokingId.value = id;
    router.delete(route('api-tokens.destroy', id), {
        preserveScroll: true,
        onFinish: () => { revokingId.value = null; },
    });
}

function closeModal() {
    showTokenModal.value = false;
    generatedToken.value = '';
}
</script>

<template>
    <Head title="Cómo usar · FIFARDOS" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-purple-700 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white">Cómo usar FIFARDOS</h1>
                    <p class="text-sm text-white/40 mt-0.5">Documentación de los endpoints REST para agentes externos (n8n, LLM)</p>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-4">

            <!-- Tabs -->
            <div class="flex gap-1 p-1 rounded-xl bg-white/5 border border-white/10 w-fit">
                <button @click="activeTab = 'docs'"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                        :class="activeTab === 'docs' ? 'bg-white/10 text-white shadow-sm' : 'text-white/40 hover:text-white/60'">
                    Documentación
                </button>
                <button @click="activeTab = 'tokens'"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                        :class="activeTab === 'tokens' ? 'bg-white/10 text-white shadow-sm' : 'text-white/40 hover:text-white/60'">
                    Tokens de API
                </button>
            </div>

            <!-- ==================== TAB: DOCUMENTACIÓN ==================== -->
            <template v-if="activeTab === 'docs'">
                <!-- Search -->
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="search"
                           type="text"
                           placeholder="Buscar endpoints por nombre, ruta o descripción..."
                           class="w-full pl-10 pr-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white text-sm
                                  placeholder-white/20 focus:outline-none focus:border-elite-secondary/50 focus:ring-1 focus:ring-elite-secondary/30
                                  transition-all duration-200">
                    <span v-if="search" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs text-white/30">
                        {{ filtered.length }} / {{ endpoints.length }}
                    </span>
                </div>

                <!-- Endpoints -->
                <div v-for="(ep, i) in filtered" :key="ep.path"
                     class="rounded-xl border border-white/10 bg-white/[0.03] backdrop-blur-sm overflow-hidden
                            hover:border-white/20 transition-all duration-300">

                    <!-- Header -->
                    <div class="px-5 sm:px-6 py-4 border-b border-white/5">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold font-mono border"
                                  :class="methodColors[ep.method] || 'bg-gray-500/20 text-gray-300 border-gray-500/30'">
                                {{ ep.method }}
                            </span>
                            <code class="text-sm font-mono text-white/80 break-all">{{ ep.path }}</code>
                        </div>
                        <p class="mt-2 text-sm text-white/50 leading-relaxed">{{ ep.description }}</p>
                    </div>

                    <!-- Body -->
                    <div class="px-5 sm:px-6 py-4 space-y-5">
                        <!-- Parameters Table -->
                        <div v-if="ep.parameters && ep.parameters.length">
                            <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wider mb-2">Parámetros</h4>
                            <div class="overflow-x-auto rounded-lg border border-white/10">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-white/5 border-b border-white/10">
                                            <th class="text-left px-3 py-2 font-medium text-white/40 text-xs">Nombre</th>
                                            <th class="text-left px-3 py-2 font-medium text-white/40 text-xs">Tipo</th>
                                            <th class="text-left px-3 py-2 font-medium text-white/40 text-xs">Requerido</th>
                                            <th class="text-left px-3 py-2 font-medium text-white/40 text-xs">Descripción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/5">
                                        <tr v-for="p in ep.parameters" :key="p.name" class="hover:bg-white/[0.02]">
                                            <td class="px-3 py-2 font-mono text-xs text-white/70">{{ p.name }}</td>
                                            <td class="px-3 py-2 text-xs text-white/50">{{ p.type }}</td>
                                            <td class="px-3 py-2">
                                                <span class="text-xs px-1.5 py-0.5 rounded"
                                                      :class="p.required ? 'bg-red-500/20 text-red-300' : 'bg-white/10 text-white/40'">
                                                    {{ p.required ? 'Sí' : 'No' }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-xs text-white/50">{{ p.description }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Curl Example -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wider">Ejemplo cURL</h4>
                                <button @click="copy(generateCurl(ep), 'curl-' + i)"
                                        class="text-xs px-2.5 py-1 rounded-md font-medium transition-all duration-200"
                                        :class="copiedId === 'curl-' + i
                                            ? 'bg-emerald-500/20 text-emerald-300'
                                            : 'bg-white/10 text-white/40 hover:bg-white/20 hover:text-white/60'">
                                    {{ copiedId === 'curl-' + i ? 'Copiado!' : 'Copiar' }}
                                </button>
                            </div>
                            <pre class="bg-white/[0.04] border border-white/10 rounded-lg p-4 text-xs font-mono text-white/60 overflow-x-auto leading-relaxed">{{ generateCurl(ep) }}</pre>
                        </div>

                        <!-- Example Response -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-xs font-semibold text-white/40 uppercase tracking-wider">Respuesta de ejemplo</h4>
                                <button @click="copy(formatJson(ep.example_response), 'json-' + i)"
                                        class="text-xs px-2.5 py-1 rounded-md font-medium transition-all duration-200"
                                        :class="copiedId === 'json-' + i
                                            ? 'bg-emerald-500/20 text-emerald-300'
                                            : 'bg-white/10 text-white/40 hover:bg-white/20 hover:text-white/60'">
                                    {{ copiedId === 'json-' + i ? 'Copiado!' : 'Copiar' }}
                                </button>
                            </div>
                            <pre class="bg-white/[0.04] border border-white/10 rounded-lg p-4 text-xs font-mono text-white/60 overflow-x-auto leading-relaxed max-h-64 overflow-y-auto">{{ formatJson(ep.example_response) }}</pre>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!filtered.length"
                     class="text-center py-16 px-4">
                    <svg class="w-12 h-12 mx-auto text-white/10 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="text-white/30 text-sm">No hay endpoints que coincidan con "{{ search }}"</p>
                </div>
            </template>

            <!-- ==================== TAB: TOKENS DE API ==================== -->
            <template v-if="activeTab === 'tokens'">
                <!-- Create Form -->
                <div class="rounded-xl border border-white/10 bg-white/[0.03] backdrop-blur-sm p-5 sm:p-6">
                    <h3 class="text-sm font-semibold text-white mb-1">Generar nuevo token</h3>
                    <p class="text-xs text-white/40 mb-4">Los tokens permiten a agentes externos (n8n, ElevenLabs) autenticarse en la API REST.</p>
                    <form @submit.prevent="createToken" class="flex gap-3">
                        <input v-model="newTokenName"
                               type="text"
                               placeholder="Ej: n8n producción, ElevenLabs agente..."
                               class="flex-1 px-4 py-2.5 rounded-lg bg-white/5 border border-white/10 text-white text-sm
                                      placeholder-white/20 focus:outline-none focus:border-elite-secondary/50 focus:ring-1 focus:ring-elite-secondary/30
                                      transition-all duration-200">
                        <button type="submit" :disabled="generating || !newTokenName.trim()"
                                class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white
                                       bg-gradient-to-r from-elite-secondary to-purple-600
                                       hover:brightness-110 disabled:opacity-50 disabled:cursor-not-allowed
                                       transition-all duration-200 whitespace-nowrap">
                            {{ generating ? 'Generando...' : 'Generar token' }}
                        </button>
                    </form>
                </div>

                <!-- Token Table -->
                <div class="rounded-xl border border-white/10 bg-white/[0.03] backdrop-blur-sm overflow-hidden">
                    <div class="px-5 sm:px-6 py-4 border-b border-white/5">
                        <h3 class="text-sm font-semibold text-white">Tus tokens</h3>
                    </div>
                    <div v-if="tokens && tokens.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-white/5 border-b border-white/10">
                                    <th class="text-left px-5 py-3 font-medium text-white/40 text-xs">Nombre</th>
                                    <th class="text-left px-5 py-3 font-medium text-white/40 text-xs">Creado</th>
                                    <th class="text-left px-5 py-3 font-medium text-white/40 text-xs">Último uso</th>
                                    <th class="text-right px-5 py-3 font-medium text-white/40 text-xs">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-for="t in tokens" :key="t.id" class="hover:bg-white/[0.02]">
                                    <td class="px-5 py-3 text-white/80 font-medium">{{ t.name }}</td>
                                    <td class="px-5 py-3 text-white/50 text-xs">{{ t.created_at }}</td>
                                    <td class="px-5 py-3 text-white/50 text-xs">{{ t.last_used_at ?? 'Nunca' }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <button @click="revokeToken(t.id)" :disabled="revokingId === t.id"
                                                class="text-xs px-3 py-1.5 rounded-md font-medium
                                                       bg-red-500/10 text-red-400 hover:bg-red-500/20
                                                       disabled:opacity-50 transition-all duration-200">
                                            {{ revokingId === t.id ? 'Revocando...' : 'Revocar' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="px-5 sm:px-6 py-8 text-center">
                        <p class="text-white/30 text-sm">No has generado ningún token todavía.</p>
                    </div>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>

    <!-- Token Generated Modal -->
    <Teleport to="body">
        <div v-if="showTokenModal"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             @click.self="closeModal">
            <div class="w-full max-w-lg rounded-xl border border-white/10 bg-elite-dark/95 backdrop-blur-xl p-6 shadow-2xl">
                <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white text-center mb-2">Token generado</h3>
                <p class="text-sm text-amber-400/80 text-center mb-4 font-medium">
                    ⚠ Este token no se volverá a mostrar. Cópialo y guárdalo ahora.
                </p>
                <pre class="bg-white/[0.04] border border-white/10 rounded-lg p-4 text-xs font-mono text-white/80 break-all select-all">{{ generatedToken }}</pre>
                <div class="flex gap-3 mt-4">
                    <button @click="copy(generatedToken, 'token-copy')"
                            class="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white
                                   bg-gradient-to-r from-elite-secondary to-purple-600
                                   hover:brightness-110 transition-all duration-200">
                        {{ copiedId === 'token-copy' ? 'Copiado!' : 'Copiar token' }}
                    </button>
                    <button @click="closeModal"
                            class="py-2.5 px-5 rounded-lg text-sm font-medium text-white/60
                                   bg-white/5 hover:bg-white/10 transition-all duration-200">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Retheme esports (landing) para páginas de administración */
:deep(.ucl-card) { background: #0e0e11; border: 1px solid rgba(255,255,255,.1); border-radius: 0; box-shadow: none; }
:deep(.ucl-card)::before { display: none; }
:deep(.ucl-title-lg), :deep(.font-condensed) { font-family: 'Anton', 'Bebas Neue', sans-serif; letter-spacing: 0; }
h1, h2 { font-family: 'Anton', 'Bebas Neue', sans-serif; text-transform: uppercase; letter-spacing: -.5px; }
</style>
