<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const AVATARS = ['⚽', '🥅', '🏆', '⭐', '🔥', '💥', '👑', '🦁', '🐺', '🦅', '🐉', '⚡', '💀', '🎯', '🚀', '👹'];

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    avatar: '',
});

const avatarFile = ref(null);
const avatarPreview = ref('');
const showEmojiPicker = ref(false);

function selectEmoji(emoji) {
    form.avatar = emoji;
    avatarFile.value = null;
    avatarPreview.value = emoji;
    showEmojiPicker.value = false;
}

function onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    avatarFile.value = file;
    form.avatar = '';
    const reader = new FileReader();
    reader.onload = (ev) => { avatarPreview.value = ev.target.result; };
    reader.readAsDataURL(file);
}

const submit = () => {
    if (avatarFile.value) {
        form.post(route('register'), {
            onFinish: () => form.reset('password', 'password_confirmation'),
            forceFormData: true,
        });
    } else {
        form.post(route('register'), {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    }
};
</script>

<template>
    <GuestLayout>
        <Head title="Crear Cuenta" />

        <div class="text-center mb-6">
            <h1 class="ucl-title-lg text-white text-xl">CREAR CUENTA</h1>
            <p class="text-xs text-white/30 mt-1">Únete a FIFARDOS</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="name" value="Nombre" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1.5 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Correo electrónico" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1.5 block w-full"
                    v-model="form.email"
                    required
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
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirmar Contraseña" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1.5 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div>
                <InputLabel value="Avatar (opcional)" />
                <div class="mt-1.5 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-2xl overflow-hidden flex-shrink-0">
                        <img v-if="avatarPreview && avatarPreview.startsWith('data:')" :src="avatarPreview" class="w-full h-full object-cover" />
                        <span v-else>{{ avatarPreview || '?' }}</span>
                    </div>
                    <button type="button" @click="showEmojiPicker = !showEmojiPicker"
                        class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-sm text-white/70 hover:bg-white/10 transition-colors">
                        😀 Emoji
                    </button>
                    <label class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-sm text-white/70 hover:bg-white/10 transition-colors cursor-pointer">
                        📷 Subir foto
                        <input type="file" accept="image/*" class="hidden" @change="onFileChange" />
                    </label>
                </div>
                <div v-if="showEmojiPicker" class="mt-2 grid grid-cols-8 gap-1.5 p-3 rounded-lg bg-white/5 border border-white/10">
                    <button type="button" v-for="emoji in AVATARS" :key="emoji"
                        @click="selectEmoji(emoji)"
                        class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-white/10 transition-colors text-lg"
                        :class="{ 'bg-orange-500/20 ring-1 ring-orange-500': form.avatar === emoji }">
                        {{ emoji }}
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                <svg v-if="form.processing" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Registrarse
            </PrimaryButton>

            <p class="text-center text-sm text-white/30">
                ¿Ya tienes cuenta?
                <Link :href="route('login')" class="text-elite-secondary hover:text-orange-300 transition-colors font-medium">
                    Inicia sesión
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
