<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    step: { type: String, default: 'email' },
    user_email: { type: String, default: '' },
    user_name: { type: String, default: '' },
    questions: { type: Array, default: () => [] },
    has_setup: { type: Boolean, default: false },
    token: { type: String, default: '' },
});

const emailForm = useForm({ email: '' });

const answerForm = useForm({
    email: props.user_email,
    answers: ['', '', ''],
});

const resetForm = useForm({
    email: props.user_email,
    token: props.token,
    password: '',
    password_confirmation: '',
});

function submitEmail() {
    router.post(route('security-questions.recover.verify-email'), emailForm.data());
}

function submitAnswers() {
    answerForm.email = props.user_email;
    router.post(route('security-questions.recover.verify-answers'), answerForm.data());
}

function submitReset() {
    resetForm.email = props.user_email;
    resetForm.token = props.token;
    router.post(route('security-questions.recover.reset'), resetForm.data());
}
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar Acceso" />

        <div class="text-center mb-6">
            <h1 class="ucl-title-lg text-white text-xl">RECUPERAR ACCESO</h1>
            <p class="text-xs text-white/30 mt-1">Verificación por preguntas de seguridad</p>
        </div>

        <!-- Step 1: Enter email -->
        <div v-if="step === 'email'">
            <form @submit.prevent="submitEmail" class="space-y-4">
                <div>
                    <label for="recover-email" class="block text-sm font-medium text-white/70 mb-1.5">Correo electrónico</label>
                    <input id="recover-email" type="email" v-model="emailForm.email"
                           class="ucl-input" placeholder="tu@email.com" required />
                    <p v-if="emailForm.errors.email" class="text-red-400 text-xs mt-1.5">{{ emailForm.errors.email }}</p>
                </div>
                <button type="submit" :disabled="emailForm.processing"
                        class="ucl-btn-primary w-full justify-center">
                    Verificar
                </button>
                <p class="text-center text-xs text-white/30">
                    <Link :href="route('login')" class="text-elite-secondary hover:text-orange-300 transition-colors">
                        Volver al inicio de sesión
                    </Link>
                </p>
            </form>
        </div>

        <!-- Step 2: Answer questions -->
        <div v-else-if="step === 'questions'">
            <div class="text-center mb-4">
                <p class="text-sm text-white/60">Responde tus preguntas de seguridad</p>
                <p class="text-xs text-white/20 mt-0.5">Cuenta: <span class="text-elite-secondary">{{ user_email }}</span></p>
            </div>
            <form @submit.prevent="submitAnswers" class="space-y-4">
                <div v-for="(q, i) in questions" :key="i">
                    <label class="block text-sm font-medium text-white/70 mb-1.5">{{ i + 1 }}. {{ q }}</label>
                    <input type="text" v-model="answerForm.answers[i]"
                           class="ucl-input" placeholder="Tu respuesta" required />
                </div>
                <p v-if="answerForm.errors.answers" class="text-red-400 text-xs">{{ answerForm.errors.answers }}</p>
                <button type="submit" :disabled="answerForm.processing"
                        class="ucl-btn-primary w-full justify-center">
                    Verificar respuestas
                </button>
                <p class="text-center text-xs text-white/30">
                    <Link :href="route('login')" class="text-elite-secondary hover:text-orange-300 transition-colors">
                        Volver al inicio de sesión
                    </Link>
                </p>
            </form>
        </div>

        <!-- Step 3: Reset password -->
        <div v-else-if="step === 'reset'">
            <div class="text-center mb-4">
                <div class="text-3xl mb-2">✅</div>
                <p class="text-sm text-emerald-400 font-semibold">Identidad verificada</p>
                <p class="text-xs text-white/40 mt-1">Usuario: <span class="text-white font-bold">{{ user_name }}</span></p>
                <p class="text-xs text-white/30">Correo: {{ user_email }}</p>
            </div>
            <form @submit.prevent="submitReset" class="space-y-4">
                <div>
                    <label for="new-password" class="block text-sm font-medium text-white/70 mb-1.5">Nueva contraseña</label>
                    <input id="new-password" type="password" v-model="resetForm.password"
                           class="ucl-input" placeholder="Mínimo 4 caracteres" required minlength="4" />
                    <p v-if="resetForm.errors.password" class="text-red-400 text-xs mt-1.5">{{ resetForm.errors.password }}</p>
                </div>
                <div>
                    <label for="new-password-confirm" class="block text-sm font-medium text-white/70 mb-1.5">Confirmar contraseña</label>
                    <input id="new-password-confirm" type="password" v-model="resetForm.password_confirmation"
                           class="ucl-input" placeholder="Repite la contraseña" required />
                </div>
                <button type="submit" :disabled="resetForm.processing"
                        class="ucl-btn-primary w-full justify-center">
                    Restablecer contraseña
                </button>
                <p class="text-center text-xs text-white/30">
                    <Link :href="route('login')" class="text-elite-secondary hover:text-orange-300 transition-colors">
                        Volver al inicio de sesión
                    </Link>
                </p>
            </form>
        </div>
    </GuestLayout>
</template>
