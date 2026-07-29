<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const form = useForm({
    name: '',
    consoles_count: 1,
    minutes_per_match: 6,
    players: [],
    reminder_at: '',
    notify_email: false,
});

const newPlayer = ref('');
const step = ref(1);

// Estimador de duración: grupos (todos contra todos) + eliminatorias, en paralelo por TV
const estimate = computed(() => {
    const p = form.players.length;
    const tv = Math.max(1, form.consoles_count);
    const m = Math.max(1, form.minutes_per_match);
    if (p < 2) return null;
    const group = (p * (p - 1)) / 2;
    let top = p <= 4 ? 4 : (p <= 8 ? 8 : 16);
    top = Math.min(top, p);
    top = Math.pow(2, Math.floor(Math.log2(top)));
    if (top < 2) top = 2;
    const knockout = top >= 4 ? top : 1;
    const total = group + knockout;
    const slots = Math.ceil(total / tv);
    return { group, knockout, total, minutes: slots * m, tv, m };
});

function fmtDuration(min) {
    if (!min || min <= 0) return '—';
    const h = Math.floor(min / 60);
    const mm = min % 60;
    if (h === 0) return `${mm} min`;
    return mm === 0 ? `${h} h` : `${h} h ${mm} min`;
}

const minReminder = computed(() => {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 16);
});

function addPlayer() {
    const name = newPlayer.value.trim();
    if (!name) return;
    if (form.players.includes(name)) return;
    form.players.push(name);
    newPlayer.value = '';
}

function removePlayer(index) {
    form.players.splice(index, 1);
}

function nextStep() {
    if (step.value === 1 && !form.name.trim()) return;
    if (step.value === 2) { step.value = 3; return; }
    step.value++;
}

function prevStep() {
    step.value--;
}

function submit() {
    if (form.players.length < 2) return;
    // El input datetime-local entrega hora LOCAL sin zona; la convertimos a ISO/UTC
    // para que el servidor (TZ UTC) la guarde y compare en el instante correcto.
    form.transform((data) => ({
        ...data,
        reminder_at: data.reminder_at ? new Date(data.reminder_at).toISOString() : null,
    })).post(route('tournaments.store'));
}

const playerCountText = computed(() => {
    const n = form.players.length;
    if (n === 0) return 'Sin jugadores';
    return `${n} jugador${n !== 1 ? 'es' : ''}`;
});

const canNext = computed(() => {
    if (step.value === 1) return form.name.trim().length > 0;
    if (step.value === 2) return form.consoles_count >= 1;
    return form.players.length >= 2;
});
</script>

<template>
    <Head title="Nuevo Torneo" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="ucl-title-lg">
                    Nuevo <span class="text-elite-secondary">Torneo</span>
                </h1>
                <p class="ucl-meta mt-1">Configura tu torneo FIFA Champions</p>
            </div>
        </template>

        <div class="py-6 sm:py-8 lg:py-10">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <!-- Steps indicator -->
                <div class="flex items-center gap-3 sm:gap-4 mb-8 sm:mb-10">
                    <template v-for="s in 3" :key="s">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div :class="[
                                'ucl-step shrink-0',
                                step === s ? 'ucl-step-active' :
                                (step > s || (s === 3 && form.players.length >= 2)) ? 'ucl-step-done' : 'ucl-step-pending'
                            ]">
                                <span v-if="step > s || (s === 3 && form.players.length >= 2)">✓</span>
                                <span v-else>{{ s }}</span>
                            </div>
                            <span class="hidden sm:block font-condensed text-xs tracking-[0.1em] uppercase"
                                  :class="step === s ? 'text-elite-secondary' : 'text-white/20'">
                                {{ s === 1 ? 'Nombre' : s === 2 ? 'TVs' : 'Jugadores' }}
                            </span>
                        </div>
                        <div v-if="s < 3" class="flex-1 h-px bg-white/10 last:hidden" />
                    </template>
                </div>

                <form @submit.prevent="submit">
                    <!-- Step 1: Name -->
                    <div v-show="step === 1" class="ucl-card p-6 sm:p-8 animate-fade-up">
                        <div class="stars-overlay" />
                        <div class="relative">
                            <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40 mb-3">
                                Nombre del Torneo
                            </label>
                            <input v-model="form.name" type="text"
                                   placeholder="Ej: FIFA WORLD CUP 2026"
                                   class="ucl-input-lg text-base sm:text-xl tracking-wider text-center h-14"
                                   maxlength="40" />
                            <p class="text-xs text-white/20 text-center mt-3">Elige un nombre épico para tu torneo</p>
                            <p v-if="form.errors.name" class="text-sm text-red-400 mt-2 text-center">{{ form.errors.name }}</p>
                        </div>
                    </div>

                    <!-- Step 2: Consoles -->
                    <div v-show="step === 2" class="ucl-card p-6 sm:p-8 animate-fade-up">
                        <div class="stars-overlay" />
                        <div class="relative space-y-6">
                            <div class="text-center">
                                <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40 mb-3">
                                    Televisores / Consolas
                                </label>
                                <p class="text-xs text-white/20">¿Cuántas pantallas disponibles?</p>
                            </div>

                            <div class="flex items-center justify-center gap-5">
                                <button type="button" @click="form.consoles_count = Math.max(1, form.consoles_count - 1)"
                                        class="min-h-touch min-w-touch flex items-center justify-center rounded-xl bg-white/10 text-white font-bold text-xl hover:bg-white/20 transition-all">
                                    −
                                </button>
                                <div class="w-20 h-16 rounded-xl bg-elite-secondary/10 border border-elite-secondary/20
                                            flex items-center justify-center font-condensed font-bold text-3xl text-elite-secondary">
                                    {{ form.consoles_count }}
                                </div>
                                <button type="button" @click="form.consoles_count = Math.min(20, form.consoles_count + 1)"
                                        class="min-h-touch min-w-touch flex items-center justify-center rounded-xl bg-white/10 text-white font-bold text-xl hover:bg-white/20 transition-all">
                                    +
                                </button>
                            </div>

                            <!-- TV indicators -->
                            <div class="flex justify-center gap-2 flex-wrap">
                                <div v-for="i in Math.min(form.consoles_count, 12)" :key="i"
                                     class="w-8 h-8 rounded-lg bg-gradient-to-br from-elite-secondary/20 to-orange-700/10
                                            border border-elite-secondary/20 flex items-center justify-center
                                            font-condensed font-bold text-xs text-elite-secondary">
                                    {{ i }}
                                </div>
                                <div v-if="form.consoles_count > 12"
                                     class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-xs text-white/30">
                                    +{{ form.consoles_count - 12 }}
                                </div>
                            </div>

                            <!-- Minutos por partido -->
                            <div class="pt-5 border-t border-white/5">
                                <div class="text-center mb-3">
                                    <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40">
                                        Minutos por partido
                                    </label>
                                    <p class="text-xs text-white/20 mt-1">Duración de cada partido</p>
                                </div>
                                <div class="flex justify-center flex-wrap gap-2">
                                    <button v-for="m in [4, 5, 6, 8, 10, 12]" :key="m" type="button"
                                            @click="form.minutes_per_match = m"
                                            class="px-4 py-2 rounded-xl font-condensed font-bold text-sm border transition-all"
                                            :class="form.minutes_per_match === m
                                                ? 'bg-elite-secondary/15 border-elite-secondary/40 text-elite-secondary'
                                                : 'bg-white/5 border-white/5 text-white/50 hover:text-white hover:bg-white/10'">
                                        {{ m }} min
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Players -->
                    <div v-show="step === 3" class="ucl-card p-6 sm:p-8 animate-fade-up">
                        <div class="stars-overlay" />
                        <div class="relative space-y-5">
                            <div class="text-center">
                                <label class="block font-condensed text-sm tracking-[0.1em] uppercase text-white/40 mb-3">
                                    Jugadores
                                </label>
                                <p class="text-xs text-white/20">Añade a los participantes del torneo</p>
                            </div>

                            <!-- Add input -->
                            <div class="flex gap-3">
                                <input v-model="newPlayer" @keydown.enter.prevent="addPlayer" type="text"
                                       placeholder="Nombre del jugador"
                                       class="ucl-input flex-1" />
                                <button @click="addPlayer" type="button"
                                        class="ucl-btn-primary min-h-touch px-5 text-xs">
                                    + Añadir
                                </button>
                            </div>

                            <!-- Player count -->
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-px bg-white/5" />
                                <span class="text-xs font-condensed tracking-wider"
                                      :class="form.players.length >= 2 ? 'text-ucl-gold' : 'text-white/20'">
                                    {{ playerCountText }}
                                </span>
                                <div class="flex-1 h-px bg-white/5" />
                            </div>

                            <!-- Player tags -->
                            <div v-if="form.players.length > 0" class="flex flex-wrap gap-2">
                                <div v-for="(player, idx) in form.players" :key="idx"
                                     class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 border border-white/5
                                            group/tag transition-all hover:border-white/10">
                                    <span class="w-6 h-6 rounded-lg bg-elite-secondary/10 flex items-center justify-center
                                                 text-xs font-bold text-elite-secondary shrink-0">
                                        {{ idx + 1 }}
                                    </span>
                                    <span class="text-sm font-medium text-white/80 truncate max-w-[120px]">{{ player }}</span>
                                    <button @click="removePlayer(idx)" type="button"
                                            class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center
                                                   text-[10px] text-white/30 hover:bg-red-500/30 hover:text-red-400 transition-all shrink-0">
                                        ✕
                                    </button>
                                </div>
                            </div>
                            <p v-else class="text-center text-xs text-white/15 py-4">
                                No hay jugadores aún. ¡Añade al menos 2!
                            </p>

                            <p v-if="form.errors.players" class="text-sm text-red-400 text-center">{{ form.errors.players }}</p>

                            <!-- Simulador de duración -->
                            <div v-if="estimate" class="rounded-2xl border border-elite-secondary/25 bg-elite-secondary/[0.06] p-4 sm:p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5">
                                        <svg class="w-5 h-5 text-elite-secondary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <circle cx="12" cy="12" r="9" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" />
                                        </svg>
                                        <span class="font-condensed text-sm tracking-[0.1em] uppercase text-white/50">Duración estimada</span>
                                    </div>
                                    <span class="font-condensed font-bold text-2xl sm:text-3xl text-elite-secondary leading-none">≈ {{ fmtDuration(estimate.minutes) }}</span>
                                </div>
                                <p class="text-xs text-white/30 mt-3 leading-relaxed">
                                    {{ estimate.total }} partidos ({{ estimate.group }} de grupos + {{ estimate.knockout }} de eliminatorias) ·
                                    {{ estimate.tv }} {{ estimate.tv === 1 ? 'TV/cancha' : 'TVs/canchas' }} en paralelo · {{ estimate.m }} min por partido.
                                </p>
                                <p class="text-[11px] text-white/20 mt-1">Estimado aproximado, sin contar descansos entre partidos.</p>
                            </div>

                            <!-- Recordatorio por email (opcional) -->
                            <div class="pt-5 mt-1 border-t border-white/5 space-y-3">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-elite-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <label class="font-condensed text-sm tracking-[0.1em] uppercase text-white/40">
                                        Recordatorio por email
                                        <span class="text-white/20 normal-case tracking-normal">· opcional</span>
                                    </label>
                                </div>
                                <input v-model="form.reminder_at" type="datetime-local" :min="minReminder"
                                       class="ucl-input w-full" />
                                <p class="text-xs text-white/25 leading-relaxed">
                                    Te enviaremos un correo en esta fecha recordándote que tienes un torneo pendiente.
                                </p>
                                <label class="flex items-center gap-3 cursor-pointer select-none">
                                    <input v-model="form.notify_email" type="checkbox"
                                           class="w-4 h-4 rounded accent-elite-secondary bg-white/10" />
                                    <span class="text-sm text-white/60">Enviarme un email de confirmación ahora</span>
                                </label>
                                <p v-if="form.errors.reminder_at" class="text-sm text-red-400">{{ form.errors.reminder_at }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="flex items-center justify-between mt-6 sm:mt-8">
                        <button v-if="step > 1" @click="prevStep" type="button"
                                class="ucl-btn-ghost text-xs sm:text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            Anterior
                        </button>
                        <div v-else />

                        <button v-if="step < 3" @click="nextStep" type="button" :disabled="!canNext"
                                class="ucl-btn-primary text-xs sm:text-sm">
                            Siguiente
                            <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <button v-else type="submit" :disabled="form.processing || form.players.length < 2"
                                class="ucl-btn-gold text-xs sm:text-sm">
                            <svg v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            {{ form.processing ? 'Creando...' : 'Iniciar Torneo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
