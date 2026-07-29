<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    catalog: { type: Array, default: () => [] },
    is_required: { type: Boolean, default: false },
});

const step = ref(1);
const selected = ref([-1, -1, -1]);
const openDropdown = ref(null);
const toast = ref(null);
let toastTimer = null;

function onKeydown(e) {
    if (e.key === 'Escape') openDropdown.value = null;
}

function onClickOutside(e) {
    if (openDropdown.value === null) return;
    const dd = document.getElementById('q-dropdown-' + openDropdown.value);
    if (dd && !dd.contains(e.target)) {
        openDropdown.value = null;
    }
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    document.addEventListener('click', onClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
    document.removeEventListener('click', onClickOutside);
});

function showToast(msg) {
    toast.value = msg;
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.value = null; }, 4000);
}

const form = useForm({
    questions: [
        { question: '', answer: '' },
        { question: '', answer: '' },
        { question: '', answer: '' },
    ],
});

function availableQuestions(index) {
    const usedIndices = selected.value.map((s, i) => (i !== index && s >= 0) ? s : -1).filter(i => i >= 0);
    return props.catalog
        .map((text, idx) => ({ text, idx }))
        .filter(item => !usedIndices.includes(item.idx));
}

function selectQuestion(qIndex, catIndex) {
    selected.value[qIndex] = catIndex;
    form.questions[qIndex].question = props.catalog[catIndex];
    openDropdown.value = null;
}

function toggleDropdown(n) {
    openDropdown.value = openDropdown.value === n ? null : n;
}

function selectedLabel(n) {
    const idx = selected.value[n - 1];
    if (idx < 0) return 'Selecciona una pregunta...';
    return props.catalog[idx] || 'Selecciona una pregunta...';
}

const canProceed = computed(() => {
    return form.questions.every(q => q.question && q.answer.length >= 2);
});

function submit() {
    const empty = form.questions.some(q => !q.question || !q.answer.trim());
    if (empty) {
        showToast('Completá todas las preguntas y respuestas antes de guardar.');
        return;
    }
    router.post(route('security-questions.setup.store'), form.data());
}
</script>

<template>
    <GuestLayout>
        <Head title="Preguntas de Seguridad" />

        <div class="text-center mb-6">
            <h1 class="ucl-title-lg text-white text-xl">PREGUNTAS DE SEGURIDAD</h1>
            <p class="text-xs text-white/30 mt-1">Configura tus preguntas para recuperar tu cuenta</p>
            <p v-if="is_required" class="text-xs text-elite-secondary mt-1 font-semibold">* Obligatorio para continuar</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div v-for="n in 3" :key="n" class="p-3 rounded-xl bg-white/[0.03] border border-white/5 space-y-2">
                <label class="text-xs font-medium text-white/60">Pregunta {{ n }}</label>

                <!-- Custom dropdown -->
                <div :id="'q-dropdown-' + n" class="relative">
                    <button type="button" @click="toggleDropdown(n)"
                            :class="[
                                'w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl text-sm text-left transition-all duration-150',
                                openDropdown === n
                                    ? 'ring-2 ring-[#2563eb] border-transparent'
                                    : 'border border-white/10 hover:border-white/20',
                                selected[n - 1] >= 0 ? 'text-white' : 'text-white/30'
                            ]"
                            style="background: #1a1a1a;">
                        <span class="truncate">{{ selectedLabel(n) }}</span>
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200"
                             :class="{ 'rotate-180': openDropdown === n }"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <Transition name="dropdown">
                        <div v-if="openDropdown === n"
                             class="absolute left-0 right-0 top-full mt-1 z-50 rounded-xl border border-white/10 shadow-2xl overflow-hidden"
                             style="background: #1a1a1a;">
                            <button v-for="item in availableQuestions(n - 1)" :key="item.idx" type="button"
                                    @click="selectQuestion(n - 1, item.idx)"
                                    class="w-full text-left px-3 py-2.5 text-sm transition-colors duration-100"
                                    :class="selected[n - 1] === item.idx
                                        ? 'text-white font-semibold'
                                        : 'text-white/60 hover:text-white hover:bg-white/5'"
                                    :style="selected[n - 1] === item.idx ? { background: '#2563eb' } : {}">
                                {{ item.text }}
                            </button>
                            <div v-if="availableQuestions(n - 1).length === 0"
                                 class="px-3 py-2.5 text-sm text-white/20 text-center">
                                No hay más preguntas disponibles
                            </div>
                        </div>
                    </Transition>
                </div>

                <label class="text-xs font-medium text-white/60">Respuesta {{ n }}</label>
                <input type="text" v-model="form.questions[n - 1].answer"
                       class="ucl-input text-sm py-2" placeholder="Tu respuesta" minlength="2" maxlength="100" required />
            </div>

            <button type="submit" :disabled="form.processing || !canProceed"
                    class="ucl-btn-primary w-full justify-center">
                {{ is_required ? 'Guardar y continuar' : 'Guardar preguntas' }}
            </button>

            <p v-if="!is_required" class="text-center text-xs text-white/30">
                <a :href="route('dashboard')" class="text-elite-secondary hover:text-orange-300 transition-colors">
                    Saltar por ahora
                </a>
            </p>
        </form>

        <!-- Floating toast -->
        <Teleport to="body">
            <Transition name="toast">
                <div v-if="toast"
                     class="fixed top-20 left-1/2 -translate-x-1/2 z-[70] px-5 py-3 rounded-xl shadow-2xl text-sm font-medium text-center max-w-sm"
                     style="background: #ff8a3d; color: #1a0a03;">
                    {{ toast }}
                </div>
            </Transition>
        </Teleport>
    </GuestLayout>
</template>

<style scoped>
.dropdown-enter-active { transition: all 0.15s ease-out; }
.dropdown-leave-active { transition: all 0.1s ease-in; }
.dropdown-enter-from { opacity: 0; transform: translateY(-6px); }
.dropdown-leave-to { opacity: 0; transform: translateY(-4px); }
.toast-enter-active { transition: all 0.25s ease-out; }
.toast-leave-active { transition: all 0.2s ease-in; }
.toast-enter-from { opacity: 0; transform: translateX(-50%) translateY(-12px); }
.toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(-8px); }
</style>
