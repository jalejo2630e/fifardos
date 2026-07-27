<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const form = useForm({
    name: '',
    consoles_count: 1,
    players: [],
});

const newPlayer = ref('');

function addPlayer() {
    const name = newPlayer.value.trim();
    if (!name) return;
    if (form.players.includes(name)) {
        alert('Ese jugador ya está inscrito');
        return;
    }
    form.players.push(name);
    newPlayer.value = '';
}

function removePlayer(index) {
    form.players.splice(index, 1);
}

function addByEnter(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addPlayer();
    }
}

function submit() {
    if (form.players.length < 2) {
        alert('Necesitas al menos 2 jugadores para empezar');
        return;
    }
    form.post(route('tournaments.store'));
}

const playerCountText = computed(() => {
    const n = form.players.length;
    if (n === 0) return 'Aún no has añadido jugadores';
    if (n === 1) return '1 jugador — necesitas al menos 2';
    return `${n} jugadores listos para el torneo`;
});
</script>

<template>
    <Head title="Crear Torneo" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-3xl font-black font-display tracking-tight">
                    <span class="text-gaming-cyan neon-text">Nuevo</span>
                    <span class="text-white"> Torneo</span>
                </h1>
                <p class="text-white/40 text-sm mt-1">Configura tu torneo FIFA antes de empezar</p>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Name -->
                    <div class="glass-card p-8 animate-fade-up">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-gaming-cyan to-cyan-700 text-black font-black text-lg">1</span>
                            <div>
                                <h2 class="text-xl font-bold font-display text-white">Nombre del Torneo</h2>
                                <p class="text-white/40 text-sm">Ponle un nombre épico a tu torneo</p>
                            </div>
                        </div>
                        <div class="relative">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <input v-model="form.name" type="text"
                                   placeholder="Ej: FIFA World Cup 2026"
                                   class="input-gaming pl-12 text-lg font-bold" />
                        </div>
                        <p v-if="form.errors.name" class="mt-2 text-sm text-gaming-red">{{ form.errors.name }}</p>
                    </div>

                    <!-- Consoles / TVs -->
                    <div class="glass-card p-8 animate-fade-up animate-fade-up-delay-1">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-gaming-purple to-purple-700 text-white font-black text-lg">2</span>
                            <div>
                                <h2 class="text-xl font-bold font-display text-white">Televisores / Consolas</h2>
                                <p class="text-white/40 text-sm">¿Cuántas pantallas tenéis disponibles?</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-4">
                                <button type="button"
                                        @click="form.consoles_count = Math.max(1, form.consoles_count - 1)"
                                        class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-white font-bold text-xl hover:bg-white/20 transition-colors">
                                    −
                                </button>
                                <div class="relative">
                                    <input v-model="form.consoles_count" type="number" min="1" max="20"
                                           class="input-gaming w-24 text-center text-2xl font-bold" />
                                </div>
                                <button type="button"
                                        @click="form.consoles_count = Math.min(20, form.consoles_count + 1)"
                                        class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-white font-bold text-xl hover:bg-white/20 transition-colors">
                                    +
                                </button>
                            </div>
                            <div class="flex gap-1.5">
                                <div v-for="i in Math.min(form.consoles_count, 8)" :key="i"
                                     class="w-8 h-8 rounded-lg bg-gradient-to-br from-gaming-cyan/30 to-gaming-purple/30 border border-gaming-cyan/30 flex items-center justify-center text-xs font-bold text-gaming-cyan">
                                    {{ i }}
                                </div>
                                <div v-if="form.consoles_count > 8" class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center text-xs text-white/40">
                                    +{{ form.consoles_count - 8 }}
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-white/30">Los partidos se repartirán entre {{ form.consoles_count }} TV(s)</p>
                        <p v-if="form.errors.consoles_count" class="mt-2 text-sm text-gaming-red">{{ form.errors.consoles_count }}</p>
                    </div>

                    <!-- Players -->
                    <div class="glass-card p-8 animate-fade-up animate-fade-up-delay-2">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-gaming-gold to-amber-700 text-black font-black text-lg">3</span>
                            <div>
                                <h2 class="text-xl font-bold font-display text-white">Jugadores</h2>
                                <p class="text-white/40 text-sm">Añade a todos los participantes</p>
                            </div>
                        </div>

                        <!-- Add player input -->
                        <div class="flex gap-3 mb-6">
                            <div class="relative flex-1">
                                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <input v-model="newPlayer" @keydown="addByEnter" type="text"
                                       placeholder="Nombre del jugador"
                                       class="input-gaming pl-12" />
                            </div>
                            <button @click="addPlayer" type="button"
                                    class="btn-primary px-6 whitespace-nowrap">
                                Añadir
                            </button>
                        </div>

                        <!-- Player count -->
                        <div class="flex items-center gap-2 mb-4">
                            <div class="flex-1 h-px bg-white/10"></div>
                            <span class="text-sm font-medium px-4 py-1.5 rounded-full"
                                  :class="form.players.length >= 2 ? 'bg-gaming-green/20 text-gaming-green' : 'bg-white/10 text-white/40'">
                                {{ playerCountText }}
                            </span>
                            <div class="flex-1 h-px bg-white/10"></div>
                        </div>

                        <!-- Player tags -->
                        <div v-if="form.players.length > 0" class="flex flex-wrap gap-3">
                            <div v-for="(player, index) in form.players" :key="index"
                                 class="player-tag group/tag">
                                <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-gaming-cyan/30 to-gaming-purple/30 flex items-center justify-center text-xs font-bold text-gaming-cyan">
                                    {{ index + 1 }}
                                </span>
                                <span class="text-white font-semibold">{{ player }}</span>
                                <button @click="removePlayer(index)" type="button"
                                        class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center text-xs text-white/40 hover:bg-gaming-red/30 hover:text-gaming-red transition-all">
                                    ✕
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.players" class="mt-3 text-sm text-gaming-red">{{ form.errors.players }}</p>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-between items-center animate-fade-up animate-fade-up-delay-3">
                        <p class="text-sm text-white/30">
                            {{ form.players.length >= 2 ? '¡Todo listo!' : 'Añade al menos 2 jugadores' }}
                        </p>
                        <button type="submit" :disabled="form.processing || form.players.length < 2"
                                class="btn-primary text-base px-10 py-4 flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            {{ form.processing ? 'Creando Torneo...' : 'Iniciar Torneo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
