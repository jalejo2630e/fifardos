<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    endpoints: Array,
    baseUrl: String,
});

const search = ref('');
const copiedId = ref(null);

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
        </div>
    </AuthenticatedLayout>
</template>
