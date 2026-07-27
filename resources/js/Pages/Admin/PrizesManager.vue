<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    tournament: Object,
    prizes: Array,
});

const editing = ref(null);
const form = useForm({
    position: '',
    label: '',
    amount: '',
    perks: [],
    icon: '',
    is_featured: false,
});

const newPerk = ref('');

function startCreate() {
    editing.value = 'new';
    form.reset();
    form.position = props.prizes.length + 1;
    form.perks = [];
}

function startEdit(prize) {
    editing.value = prize.id;
    form.position = prize.position;
    form.label = prize.label;
    form.amount = prize.amount;
    form.perks = prize.perks || [];
    form.icon = prize.icon || '';
    form.is_featured = prize.is_featured;
}

function cancel() {
    editing.value = null;
    form.reset();
    newPerk.value = '';
}

function addPerk() {
    if (newPerk.value.trim()) {
        form.perks = [...(form.perks || []), newPerk.value.trim()];
        newPerk.value = '';
    }
}

function removePerk(i) {
    form.perks = form.perks.filter((_, idx) => idx !== i);
}

function submit() {
    if (editing.value === 'new') {
        form.post(route('prizes.store', props.tournament.id), {
            onSuccess: () => cancel(),
        });
    } else {
        form.put(route('prizes.update', [props.tournament.id, editing.value]), {
            onSuccess: () => cancel(),
        });
    }
}

function destroy(prize) {
    if (confirm('¿Eliminar este premio?')) {
        router.delete(route('prizes.destroy', [props.tournament.id, prize.id]));
    }
}
</script>

<template>
    <Head title="Gestionar Premios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="ucl-title-lg text-white">
                        Premios: <span class="text-ucl-cyan">{{ tournament.name }}</span>
                    </h1>
                    <p class="ucl-meta mt-1">Configura los premios del torneo</p>
                </div>
                <Link :href="route('tournaments.show', tournament.id)"
                      class="ucl-btn-ghost text-sm px-5">
                    Volver al torneo
                </Link>
            </div>
        </template>

        <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Empty state -->
            <div v-if="prizes.length === 0 && editing !== 'new'"
                 class="ucl-card p-10 text-center">
                <p class="text-white/50 mb-4">No hay premios configurados aún.</p>
                <button @click="startCreate" class="ucl-btn-primary text-sm">
                    + Agregar premio
                </button>
            </div>

            <!-- Lista de premios -->
            <div v-if="prizes.length > 0 || editing === 'new'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="ucl-title-md text-white/80">Premios actuales</h2>
                    <button @click="startCreate" class="ucl-btn-primary text-xs px-4 py-2">
                        + Nuevo
                    </button>
                </div>

                <div v-for="prize in prizes" :key="prize.id"
                     class="ucl-card p-5 flex items-start justify-between gap-4">

                    <div v-if="editing !== prize.id" class="flex-1">
                        <div class="flex items-center gap-3 mb-1">
                            <span class="text-ucl-gold font-bold text-lg">#{{ prize.position }}</span>
                            <span class="font-bold text-white">{{ prize.label }}</span>
                            <span v-if="prize.is_featured" class="text-xs bg-ucl-gold/10 text-ucl-gold px-2 py-0.5 rounded-full">Destacado</span>
                        </div>
                        <p class="text-lg font-bold text-ucl-cyan">{{ prize.amount }}</p>
                        <p v-if="prize.icon" class="text-xs text-white/30">Icono: {{ prize.icon }}</p>
                        <ul v-if="prize.perks?.length" class="mt-2 space-y-0.5">
                            <li v-for="(perk, i) in prize.perks" :key="i"
                                class="text-xs text-white/40">• {{ perk }}</li>
                        </ul>
                    </div>

                    <div v-if="editing !== prize.id" class="flex items-center gap-2 shrink-0">
                        <button @click="startEdit(prize)"
                                class="text-xs text-ucl-cyan hover:text-white transition-colors">
                            Editar
                        </button>
                        <button @click="destroy(prize)"
                                class="text-xs text-red-400 hover:text-red-300 transition-colors">
                            Eliminar
                        </button>
                    </div>

                    <!-- Edit form -->
                    <div v-if="editing === prize.id" class="flex-1 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs text-white/40 block mb-1">Posición</label>
                                <input type="number" v-model="form.position"
                                       class="ucl-input text-sm" />
                            </div>
                            <div>
                                <label class="text-xs text-white/40 block mb-1">Destacado</label>
                                <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                    <input type="checkbox" v-model="form.is_featured"
                                           class="rounded border-white/20 bg-white/5 text-ucl-cyan focus:ring-ucl-cyan" />
                                    <span class="text-xs text-white/50">¿Destacar visualmente?</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-white/40 block mb-1">Label</label>
                            <input type="text" v-model="form.label" class="ucl-input text-sm" />
                        </div>
                        <div>
                            <label class="text-xs text-white/40 block mb-1">Monto</label>
                            <input type="text" v-model="form.amount" class="ucl-input text-sm" placeholder="Ej: £1,500" />
                        </div>
                        <div>
                            <label class="text-xs text-white/40 block mb-1">Icono (Material Symbols)</label>
                            <input type="text" v-model="form.icon" class="ucl-input text-sm" placeholder="Ej: stars, military_tech" />
                        </div>
                        <div>
                            <label class="text-xs text-white/40 block mb-1">Perks</label>
                            <div class="flex gap-2 mb-2">
                                <input type="text" v-model="newPerk" @keydown.enter.prevent="addPerk"
                                       class="ucl-input text-sm flex-1" placeholder="Agregar perk..." />
                                <button @click="addPerk" class="ucl-btn-primary text-xs px-3 py-2">+</button>
                            </div>
                            <div v-if="form.perks?.length" class="flex flex-wrap gap-1.5">
                                <span v-for="(perk, i) in form.perks" :key="i"
                                      class="inline-flex items-center gap-1 text-xs bg-white/5 text-white/60 px-2 py-1 rounded-full">
                                    {{ perk }}
                                    <button @click="removePerk(i)" class="text-red-400 hover:text-red-300">&times;</button>
                                </span>
                            </div>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button @click="submit" :disabled="form.processing"
                                    class="ucl-btn-primary text-xs px-5 py-2">
                                {{ form.processing ? 'Guardando...' : 'Guardar' }}
                            </button>
                            <button @click="cancel" class="ucl-btn-ghost text-xs px-4 py-2">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- New prize form -->
                <div v-if="editing === 'new'" class="ucl-card p-5 space-y-3">
                    <h3 class="text-sm font-bold text-white/80">Nuevo premio</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-white/40 block mb-1">Posición</label>
                            <input type="number" v-model="form.position"
                                   class="ucl-input text-sm" />
                        </div>
                        <div>
                            <label class="text-xs text-white/40 block mb-1">Destacado</label>
                            <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                <input type="checkbox" v-model="form.is_featured"
                                       class="rounded border-white/20 bg-white/5 text-ucl-cyan focus:ring-ucl-cyan" />
                                <span class="text-xs text-white/50">¿Destacar visualmente?</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-white/40 block mb-1">Label</label>
                        <input type="text" v-model="form.label" class="ucl-input text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-white/40 block mb-1">Monto</label>
                        <input type="text" v-model="form.amount" class="ucl-input text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-white/40 block mb-1">Icono</label>
                        <input type="text" v-model="form.icon" class="ucl-input text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-white/40 block mb-1">Perks</label>
                        <div class="flex gap-2 mb-2">
                            <input type="text" v-model="newPerk" @keydown.enter.prevent="addPerk"
                                   class="ucl-input text-sm flex-1" />
                            <button @click="addPerk" class="ucl-btn-primary text-xs px-3 py-2">+</button>
                        </div>
                        <div v-if="form.perks?.length" class="flex flex-wrap gap-1.5">
                            <span v-for="(perk, i) in form.perks" :key="i"
                                  class="inline-flex items-center gap-1 text-xs bg-white/5 text-white/60 px-2 py-1 rounded-full">
                                {{ perk }}
                                <button @click="removePerk(i)" class="text-red-400 hover:text-red-300">&times;</button>
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button @click="submit" :disabled="form.processing"
                                class="ucl-btn-primary text-xs px-5 py-2">
                            {{ form.processing ? 'Guardando...' : 'Crear' }}
                        </button>
                        <button @click="cancel" class="ucl-btn-ghost text-xs px-4 py-2">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
