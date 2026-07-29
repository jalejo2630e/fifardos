<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    catalog: { type: Array, default: () => [] },
    existing_questions: { type: Array, default: () => ['', '', ''] },
    has_setup: { type: Boolean, default: false },
});

const selected = ref([-1, -1, -1]);
const showForm = ref(false);

const form = useForm({
    current_password: '',
    questions: [
        { question: props.existing_questions[0] || '', answer: '' },
        { question: props.existing_questions[1] || '', answer: '' },
        { question: props.existing_questions[2] || '', answer: '' },
    ],
});

// Pre-fill selected indices if user has existing questions
if (props.has_setup) {
    props.existing_questions.forEach((q, i) => {
        const idx = props.catalog.indexOf(q);
        if (idx >= 0) selected.value[i] = idx;
    });
}

function availableQuestions(index) {
    const usedIndices = selected.value.map((s, i) => (i !== index && s >= 0) ? s : -1).filter(i => i >= 0);
    return props.catalog
        .map((text, idx) => ({ text, idx }))
        .filter(item => !usedIndices.includes(item.idx));
}

function selectQuestion(qIndex, catIndex) {
    selected.value[qIndex] = catIndex;
    form.questions[qIndex].question = props.catalog[catIndex];
}

const canSave = computed(() => {
    return form.current_password.length >= 1
        && form.questions.every(q => q.question && q.answer.length >= 2);
});

function submit() {
    router.put(route('security-questions.profile.update'), form.data());
}

function toggleForm() {
    showForm.value = !showForm.value;
    if (!showForm.value) {
        form.current_password = '';
        form.questions.forEach(q => q.answer = '');
    }
}
</script>

<template>
    <div>
        <h3 class="text-sm font-semibold text-white mb-1">Preguntas de seguridad</h3>
        <p class="text-xs text-white/30 mb-3">
            {{ has_setup ? 'Tienes preguntas configuradas. Para cambiarlas, ingresa tu contraseña actual.' : 'Configura preguntas para recuperar tu cuenta si olvidas la contraseña.' }}
        </p>

        <div v-if="has_setup && !showForm" class="space-y-2 mb-3">
            <div v-for="(q, i) in existing_questions" :key="i" class="text-xs text-white/50">
                <span class="text-white/30">P{{ i + 1 }}:</span> {{ q }}
            </div>
            <button @click="toggleForm" class="text-xs text-elite-secondary hover:text-orange-300 transition-colors font-medium">
                Cambiar preguntas
            </button>
        </div>

        <form v-if="showForm || !has_setup" @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-white/60 mb-1">Contraseña actual <span class="text-red-400">*</span></label>
                <input type="password" v-model="form.current_password"
                       class="ucl-input text-sm py-2" placeholder="Ingresa tu contraseña para confirmar" required />
                <p v-if="form.errors.current_password" class="text-red-400 text-xs mt-1">{{ form.errors.current_password }}</p>
            </div>

            <div v-for="n in 3" :key="n" class="p-3 rounded-xl bg-white/[0.03] border border-white/5 space-y-2">
                <label class="text-xs font-medium text-white/60">Pregunta {{ n }}</label>
                <select v-model.number="selected[n - 1]" @change="selectQuestion(n - 1, selected[n - 1])"
                        class="ucl-input text-sm py-2">
                    <option :value="-1" disabled>Selecciona una pregunta...</option>
                    <option v-for="item in availableQuestions(n - 1)" :key="item.idx" :value="item.idx">
                        {{ item.text }}
                    </option>
                </select>

                <label class="text-xs font-medium text-white/60">Respuesta {{ n }}</label>
                <input type="text" v-model="form.questions[n - 1].answer"
                       class="ucl-input text-sm py-2" placeholder="Tu respuesta" minlength="2" maxlength="100" required />
            </div>

            <p v-if="form.errors.questions" class="text-red-400 text-xs">{{ form.errors.questions }}</p>

            <div class="flex gap-2">
                <button type="submit" :disabled="form.processing || !canSave"
                        class="ucl-btn-primary text-sm px-5">
                    {{ has_setup ? 'Actualizar preguntas' : 'Guardar preguntas' }}
                </button>
                <button v-if="has_setup" type="button" @click="toggleForm"
                        class="ucl-btn-ghost text-sm px-5">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</template>
