<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import LoginBackground from '@/Components/LoginBackground.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar Sesión" />

        <LoginBackground />

        <div class="login-form relative z-10">
            <div class="text-center mb-6">
                <h1 class="text-xl font-condensed font-bold uppercase tracking-[0.06em] text-white">INICIAR SESIÓN</h1>
                <p class="text-xs text-white/30 mt-1">Ingresa a tu cuenta FIFARDOS</p>
            </div>

            <div v-if="status" class="mb-4 text-sm font-medium" style="color: var(--accent-orange, #ff8a3d)">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel for="email" value="Correo electrónico" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1.5 block w-full"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="password" value="Contraseña" />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1.5 block w-full"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <Checkbox name="remember" v-model:checked="form.remember" />
                        <span class="text-sm text-white/50">Recordarme</span>
                    </label>
                    <Link
                        :href="route('security-questions.recover.form')"
                        class="text-sm transition-colors"
                        style="color: var(--accent-orange, #ff8a3d)"
                        hover-class="brightness-110"
                    >
                        ¿Olvidaste tus datos?
                    </Link>
                </div>

                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                    style="
                        background: linear-gradient(135deg, var(--accent-orange, #ff8a3d), #e67320);
                        box-shadow: 0 4px 16px rgba(255, 138, 61, 0.25);
                    "
                >
                    <svg v-if="form.processing" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    Ingresar
                </PrimaryButton>

                <p class="text-center text-sm text-white/30">
                    ¿No tienes cuenta?
                    <Link :href="route('register')" class="font-medium transition-colors" style="color: var(--accent-orange, #ff8a3d)">
                        Regístrate
                    </Link>
                </p>
            </form>
        </div>
    </GuestLayout>
</template>

<style>
:root {
    --login-bg: #1b2130;
    --card-bg: #242b3d;
    --card-border: #343d54;
    --accent-orange: #ff8a3d;
    --accent-blue: #3d9bff;
    --accent-gold: #ffb35e;
}

/* Override GuestLayout page background for login */
body:has(.login-form) {
    background: var(--login-bg) !important;
}

/* Override card styling */
body:has(.login-form) .ucl-card {
    background: var(--card-bg) !important;
    border-color: var(--card-border) !important;
}

body:has(.login-form) .ucl-card::before {
    display: none !important;
}

/* Override input styling */
body:has(.login-form) .ucl-input {
    background: var(--login-bg) !important;
    border: 1px solid var(--card-border) !important;
    box-shadow: none !important;
}

body:has(.login-form) .ucl-input:focus {
    border-color: var(--accent-orange) !important;
    box-shadow: 0 0 0 3px rgba(255, 138, 61, 0.15) !important;
}

body:has(.login-form) .ucl-input::placeholder {
    color: rgba(255, 255, 255, 0.15) !important;
}
</style>
