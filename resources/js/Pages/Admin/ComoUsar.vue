<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const activeSection = ref('');

function scrollTo(id) {
    activeSection.value = id;
    const el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function copy(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = 'Copiado!';
        setTimeout(() => { btn.textContent = 'Copiar'; }, 2000);
    });
}

const curlExample = `curl -X GET https://tudominio.com/api/agent/tournaments/1/standings \\
  -H "Authorization: Bearer TU_TOKEN_AQUI"`;

const tokenCommand = `php artisan tinker
$user = User::where('email', 'admin@ejemplo.com')->first();
$token = $user->createToken('agent-n8n')->plainTextToken;
echo $token;`;

const apiRows = [
    { method: 'GET', route: '/api/agent/tournaments', desc: 'Lista de torneos activos (incluye líder actual)' },
    { method: 'GET', route: '/api/agent/tournaments/{id}/standings', desc: 'Tabla de posiciones completa' },
    { method: 'GET', route: '/api/agent/tournaments/{id}/top-scorer', desc: 'Goleador del torneo' },
    { method: 'GET', route: '/api/agent/tournaments/{id}/matches?status=', desc: 'Partidos (filtrable ?status=pending|finished)' },
    { method: 'GET', route: '/api/agent/players/{id}', desc: 'Datos y estadísticas de un jugador' },
    { method: 'GET', route: '/api/agent/schema', desc: 'Documentación completa para agentes IA' },
];

const navItems = [
    { id: 'sec-dashboard', label: 'Dashboard' },
    { id: 'sec-torneos', label: 'Torneos' },
    { id: 'sec-jugadores', label: 'Jugadores y Partidos' },
    { id: 'sec-api', label: 'API para Agentes' },
    { id: 'sec-auth', label: 'Autenticación' },
];
</script>

<template>
    <Head title="Admin · Cómo usar FIFARDOS" />

    <div class="min-h-screen bg-gray-50 text-gray-900 font-sans">

        <!-- Top bar -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-14 sm:h-16 items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Link :href="route('dashboard')" class="flex items-center gap-2.5 shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-elite-secondary to-orange-700
                                        flex items-center justify-center text-black shadow-sm">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="6" width="20" height="12" rx="3"/>
                                    <circle cx="8" cy="12" r="1.5"/>
                                    <circle cx="16" cy="12" r="1.5"/>
                                </svg>
                            </div>
                            <span class="font-bold text-base tracking-wider text-gray-800 hidden sm:block">
                                FIFARDOS
                            </span>
                        </Link>
                        <span class="hidden sm:flex items-center gap-1.5 text-xs text-gray-400">
                            <span>/</span>
                            <span>Documentación</span>
                        </span>
                    </div>
                    <Link :href="route('dashboard')"
                          class="text-sm text-gray-400 hover:text-gray-700 transition-colors px-3 py-2">
                        &larr; Volver al panel
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <div class="bg-white border-b border-gray-200">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-medium mb-4">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Documentación Admin
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight">
                    Cómo usar FIFARDOS
                </h1>
                <p class="mt-3 text-gray-500 text-base sm:text-lg max-w-2xl leading-relaxed">
                    Guía completa del panel de administración: torneos, jugadores, partidos,
                    y la API REST para integración con agentes externos.
                </p>
            </div>
        </div>

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8 sm:py-10">

            <!-- ===================== NAVEGACIÓN RÁPIDA ===================== -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5 mb-8">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Navegación rápida</p>
                <div class="flex flex-wrap gap-2">
                    <button v-for="item in navItems" :key="item.id"
                            @click="scrollTo(item.id)"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                            :class="activeSection === item.id
                                ? 'bg-gray-900 text-white shadow-sm'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 hover:text-gray-800'">
                        {{ item.label }}
                    </button>
                </div>
            </div>

            <!-- ===================== DASHBOARD ===================== -->
            <section id="sec-dashboard" class="scroll-mt-20 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-sky-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Dashboard</h2>
                        </div>
                        <div class="space-y-3 text-sm text-gray-600 leading-relaxed">
                            <p><strong class="text-gray-800">Dónde:</strong> Menú principal → Dashboard</p>
                            <p>Panel con resumen de torneos activos, jugadores registrados, partidos jugados y pendientes. Desde aquí puedes acceder rápidamente a cualquier torneo o crear uno nuevo.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== TORNEOS ===================== -->
            <section id="sec-torneos" class="scroll-mt-20 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Torneos</h2>
                        </div>
                        <div class="space-y-3 text-sm text-gray-600 leading-relaxed">
                            <p><strong class="text-gray-800">Dónde:</strong> Menú → Torneos</p>
                            <ul class="list-disc list-inside space-y-1 pl-2">
                                <li>Haz clic en <strong>"+ Crear Torneo"</strong> desde el Dashboard o la navegación.</li>
                                <li>Asigna un nombre, color y número de consolas (TVs) disponibles.</li>
                                <li>Dentro del torneo, ve a la pestaña <strong>"Jugadores"</strong> para agregar participantes.</li>
                                <li>Usa <strong>"Generar Partidos"</strong> para crear automáticamente el calendario Round Robin (todos contra todos).</li>
                                <li>En la pestaña <strong>"Partidos"</strong>, marca los resultados de cada encuentro. Los standings se actualizan automáticamente.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== JUGADORES Y PARTIDOS ===================== -->
            <section id="sec-jugadores" class="scroll-mt-20 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Jugadores y Partidos</h2>
                        </div>
                        <div class="space-y-3 text-sm text-gray-600 leading-relaxed">
                            <p><strong class="text-gray-800">Dónde:</strong> Dentro de cada torneo</p>
                            <p>Los <strong>standings</strong> se calculan automáticamente con el sistema de puntuación estándar:</p>
                            <ul class="list-disc list-inside space-y-1 pl-2">
                                <li><strong>Victoria:</strong> 3 puntos</li>
                                <li><strong>Empate:</strong> 1 punto</li>
                                <li><strong>Derrota:</strong> 0 puntos</li>
                            </ul>
                            <p>La tabla incluye: posición, nombre, puntos, partidos jugados (PJ), ganados (PG), empatados (PE), perdidos (PP), goles a favor (GF), goles en contra (GC), y diferencia de gol (DG).</p>
                            <div class="flex items-start gap-2.5 p-3 rounded-lg bg-amber-50 border border-amber-200 mt-2">
                                <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <p class="text-sm text-amber-800"><strong>Nota:</strong> Los goles se registran por partido (score1 / score2), no hay tracking de goleador individual por jugador. El endpoint "top-scorer" calcula los goles sumando los resultados de cada partido en los que participa el jugador.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== API PARA AGENTES (n8n) ===================== -->
            <section id="sec-api" class="scroll-mt-20 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">API para Agentes (n8n)</h2>
                        </div>
                        <div class="space-y-3 text-sm text-gray-600 leading-relaxed">
                            <p>FIFARDOS expone una API REST en <code class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded text-gray-700">/api/agent/*</code> diseñada para que un agente de IA (n8n, LLM, etc.) consulte torneos, standings, goleador, partidos y jugadores en tiempo real, sin necesidad de acceso manual al panel.</p>
                            <div class="flex items-start gap-2.5 p-3 rounded-lg bg-blue-50 border border-blue-200 mt-1">
                                <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-blue-800">
                                    <strong>Importante:</strong> Consulta <code class="font-mono text-xs bg-blue-100 px-1.5 py-0.5 rounded">GET /api/agent/schema</code> para ver la documentación completa de cada endpoint, sus parámetros y ejemplos de respuesta. Este endpoint está pensado para que un LLM lo lea como contexto inicial.
                                </p>
                            </div>
                            <p>Ejemplo de petición:</p>
                            <div class="relative">
                                <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-sm font-mono overflow-x-auto leading-relaxed">{{ curlExample }}</pre>
                                <button @click="copy(curlExample, $el)"
                                        class="copy-btn absolute top-2 right-2">Copiar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== AUTENTICACIÓN ===================== -->
            <section id="sec-auth" class="scroll-mt-20 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m0 0v2m0-2h2m-2 0H10m9-11a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Autenticación</h2>
                        </div>
                        <div class="space-y-3 text-sm text-gray-600 leading-relaxed">
                            <p>Todos los endpoints de la API requieren un <strong>Personal Access Token</strong> de Laravel Sanctum. Para generar un token exclusivo para el agente:</p>
                            <div class="relative">
                                <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-sm font-mono overflow-x-auto leading-relaxed">{{ tokenCommand }}</pre>
                                <button @click="copy(tokenCommand, $el)"
                                        class="copy-btn absolute top-2 right-2">Copiar</button>
                            </div>
                            <p>Incluye el token en el header <code class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded text-gray-700">Authorization</code> de cada petición:</p>
                            <div class="relative">
                                <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-sm font-mono overflow-x-auto leading-relaxed">{{ curlExample }}</pre>
                                <button @click="copy(curlExample, $el)"
                                        class="copy-btn absolute top-2 right-2">Copiar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== TABLA DE APIs ===================== -->
            <section class="mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 sm:px-7 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">APIs disponibles</h2>
                        </div>
                        <p class="text-sm text-gray-500 mb-4">Todas requieren API key del agente (Bearer token).</p>
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider w-20">Método</th>
                                        <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Ruta</th>
                                        <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Descripción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="row in apiRows" :key="row.route"
                                        class="hover:bg-gray-50/70 transition-colors">
                                        <td class="px-4 py-3">
                                            <span class="inline-block font-mono text-xs font-bold px-2 py-0.5 rounded
                                                        bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ row.method }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs text-gray-700 break-all">{{ row.route }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ row.desc }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== ACCESOS RÁPIDOS ===================== -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-6">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Accesos rápidos</p>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('dashboard')"
                          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Ir al Dashboard
                    </Link>
                    <Link :href="route('dashboard')"
                          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                        Ver Torneos
                    </Link>
                    <a href="/api/agent/schema" target="_blank"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition-colors border border-gray-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        Ver API Schema
                    </a>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <footer class="border-t border-gray-200 mt-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-6 text-center text-xs text-gray-400">
                FIFARDOS — Documentación del panel de administración
            </div>
        </footer>
    </div>
</template>

<style scoped>
.copy-btn {
    @apply px-2.5 py-1 text-[11px] font-medium rounded-md bg-gray-800 text-gray-300
           hover:bg-gray-700 hover:text-white transition-colors cursor-pointer;
    font-family: inherit;
}
.copy-btn:active {
    @apply bg-gray-600;
}
pre {
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.1) transparent;
}
</style>
