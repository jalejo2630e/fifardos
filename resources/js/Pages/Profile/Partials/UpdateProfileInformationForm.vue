<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n({
    useScope: 'local',
    messages: {
        es: {
            heading: 'Información del Perfil',
            subheading: 'Actualiza tu información y avatar.',
            name: 'Nombre',
            email: 'Email',
            avatar: 'Avatar',
            emoji: '😀 Emoji',
            uploadPhoto: '📷 Subir foto',
            emailNotVerified: 'Tu email no está verificado.',
            resendVerification: 'Reenviar verificación',
            verificationLinkSent: 'Se ha enviado un nuevo link de verificación.',
            save: 'Guardar',
            saved: 'Guardado.',
        },
        en: {
            heading: 'Profile Information',
            subheading: 'Update your information and avatar.',
            name: 'Name',
            email: 'Email',
            avatar: 'Avatar',
            emoji: '😀 Emoji',
            uploadPhoto: '📷 Upload photo',
            emailNotVerified: 'Your email is not verified.',
            resendVerification: 'Resend verification',
            verificationLinkSent: 'A new verification link has been sent.',
            save: 'Save',
            saved: 'Saved.',
        },
    },
});

const AVATARS = ['⚽', '🥅', '🏆', '⭐', '🔥', '💥', '👑', '🦁', '🐺', '🦅', '🐉', '⚡', '💀', '🎯', '🚀', '👹'];

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const currentAvatar = user.avatar || '';
const isEmojiAvatar = currentAvatar && !currentAvatar.startsWith('http') && !currentAvatar.startsWith('/');

const form = useForm({
    name: user.name,
    email: user.email,
    avatar: isEmojiAvatar ? currentAvatar : '',
});

const avatarFile = ref(null);
const avatarPreview = ref(user.avatar_url || '');
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
        form.patch(route('profile.update'), {
            forceFormData: true,
        });
    } else {
        form.patch(route('profile.update'));
    }
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-white/80">
                {{ t('heading') }}
            </h2>
            <p class="mt-1 text-sm text-white/40">
                {{ t('subheading') }}
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <InputLabel for="name" :value="t('name')" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" :value="t('email')" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel :value="t('avatar')" />
                <div class="mt-1.5 flex items-center gap-3">
                    <div class="w-14 h-14 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-3xl overflow-hidden flex-shrink-0">
                        <img v-if="avatarPreview && (avatarPreview.startsWith('http') || avatarPreview.startsWith('data:'))" :src="avatarPreview" class="w-full h-full object-cover" />
                        <span v-else>{{ avatarPreview || '?' }}</span>
                    </div>
                    <button type="button" @click="showEmojiPicker = !showEmojiPicker"
                        class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-sm text-white/70 hover:bg-white/10 transition-colors">
                        {{ t('emoji') }}
                    </button>
                    <label class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-sm text-white/70 hover:bg-white/10 transition-colors cursor-pointer">
                        {{ t('uploadPhoto') }}
                        <input type="file" accept="image/*" class="hidden" @change="onFileChange" />
                    </label>
                </div>
                <div v-if="showEmojiPicker" class="mt-2 grid grid-cols-8 gap-1.5 p-3 rounded-lg bg-white/5 border border-white/10">
                    <button type="button" v-for="emoji in AVATARS" :key="emoji"
                        @click="selectEmoji(emoji)"
                        class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-white/10 text-lg"
                        :class="{ 'bg-orange-500/20 ring-1 ring-orange-500': form.avatar === emoji }">
                        {{ emoji }}
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-white/60">
                    {{ t('emailNotVerified') }}
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-orange-400 underline hover:text-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                    >
                        {{ t('resendVerification') }}
                    </Link>
                </p>
                <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-400">
                    {{ t('verificationLinkSent') }}
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">{{ t('save') }}</PrimaryButton>
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-400">
                        {{ t('saved') }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
