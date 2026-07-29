<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    catalog: { type: Array, default: () => [] },
    is_required: { type: Boolean, default: false },
});

const step = ref(1);
const selected = ref([-1, -1, -1]);

const form = useForm({
    questions: [
        { question: '', answer: '' },
        { question: '', answer: '' },
        { question: '', answer: '' },
    ],
});

function availableQuestions(index) {
    const usedIndices = selected.value.map((s, i) => (i !== index && s >= 0) ? s : -1).filter(i => i >= 0);
    return props.catalog.filter((_, i) => !usedIndices.includes(i));
}

function selectQuestion(qIndex, catIndex) {
    selected.value[qIndex] = catIndex;
    form.questions[qIndex].question = props.catalog[catIndex];
}

const canProceed = computed(() => {
    return form.questions.every(q => q.question && q.answer.length >= 2);
});

function submit() {
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

                <select v-model.number="selected[n - 1]" @change="selectQuestion(n - 1, selected[n - 1])"
                        class="ucl-input text-sm py-2">
                    <option :value="-1" disabled>Selecciona una pregunta...</option>
                    <option v-for="(q, i) in availableQuestions(n - 1)" :key="i" :value="i">
                        {{ q }}
                    </option>
                </select>

                <label class="text-xs font-medium text-white/60">Respuesta {{ n }}</label>
                <input type="text" v-model="form.questions[n - 1].answer"
                       class="ucl-input text-sm py-2" placeholder="Tu respuesta" minlength="2" maxlength="100" required />
            </div>

            <p v-if="form.errors.questions" class="text-red-400 text-xs">{{ form.errors.questions }}</p>

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
    </GuestLayout>
</template>
