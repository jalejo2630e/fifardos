<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Restablecer Contraseña" />

        <div>
            <div class="text-center mb-5">
                <h1 class="text-xl font-condensed font-bold uppercase tracking-[0.06em] text-white">RESTABLECER CONTRASEÑA</h1>
                <p class="text-xs text-white/30 mt-1">Te enviaremos un enlace para crear una nueva</p>
            </div>

            <div v-if="status" class="mb-4 text-sm font-medium text-center" style="color: var(--accent-orange, #ff8a3d)">
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
                    Enviar enlace
                </PrimaryButton>

                <p class="text-center text-sm text-white/30">
                    <Link :href="route('login')" class="font-medium transition-colors" style="color: var(--accent-orange, #ff8a3d)">
                        ← Volver al inicio de sesión
                    </Link>
                </p>
            </form>
        </div>
    </GuestLayout>
</template>
