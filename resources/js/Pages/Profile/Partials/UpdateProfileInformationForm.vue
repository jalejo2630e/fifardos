<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
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
            // Modal de carga
            uploadTitle: 'Subir foto de perfil',
            dropHere: 'Arrastrá una imagen acá',
            orClick: 'o hacé clic para seleccionarla',
            allowedTypes: 'JPG, PNG, WEBP o GIF · máx. 4 MB',
            chooseAnother: 'Elegir otra',
            useThisPhoto: 'Usar esta foto',
            uploading: 'Subiendo…',
            cancel: 'Cancelar',
            errType: 'Formato no permitido. Solo JPG, PNG, WEBP o GIF.',
            errSize: 'La imagen supera los 4 MB.',
            errCorrupt: 'El archivo no es una imagen válida.',
            secureNote: 'La imagen se valida y se reprocesa en el servidor para eliminar cualquier contenido que no sea la foto.',
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
            // Upload modal
            uploadTitle: 'Upload profile photo',
            dropHere: 'Drag an image here',
            orClick: 'or click to select it',
            allowedTypes: 'JPG, PNG, WEBP or GIF · max 4 MB',
            chooseAnother: 'Choose another',
            useThisPhoto: 'Use this photo',
            uploading: 'Uploading…',
            cancel: 'Cancel',
            errType: 'Format not allowed. Only JPG, PNG, WEBP or GIF.',
            errSize: 'The image is larger than 4 MB.',
            errCorrupt: 'The file is not a valid image.',
            secureNote: 'The image is validated and re-processed on the server to strip anything that is not the photo.',
        },
    },
});

const AVATARS = ['⚽', '🥅', '🏆', '⭐', '🔥', '💥', '👑', '🦁', '🐺', '🦅', '🐉', '⚡', '💀', '🎯', '🚀', '👹'];

const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
const MAX_BYTES = 4 * 1024 * 1024;

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const user = page.props.auth.user;

const currentAvatar = user.avatar || '';
const isEmojiAvatar = currentAvatar && !currentAvatar.startsWith('http') && !currentAvatar.startsWith('/');

const form = useForm({
    // Method spoofing: se envía como POST con _method=patch para que PHP parsee
    // correctamente el multipart/form-data (PHP no lo hace en PATCH/PUT).
    _method: 'patch',
    name: user.name,
    email: user.email,
    avatar: isEmojiAvatar ? currentAvatar : '',
});

const avatarPreview = ref(user.avatar_url || (isEmojiAvatar ? currentAvatar : ''));
const showEmojiPicker = ref(false);

// --- Modal de carga de foto ---
const showUploadModal = ref(false);
const dragOver = ref(false);
const uploadError = ref('');
const uploadProgress = ref(0);
const selectedFile = ref(null);
const modalPreview = ref('');
const fileInput = ref(null);

function selectEmoji(emoji) {
    form.avatar = emoji;
    avatarPreview.value = emoji;
    showEmojiPicker.value = false;
}

function openUploadModal() {
    uploadError.value = '';
    uploadProgress.value = 0;
    selectedFile.value = null;
    modalPreview.value = '';
    showUploadModal.value = true;
}

function closeUploadModal() {
    if (form.processing) return;
    showUploadModal.value = false;
}

function triggerFileDialog() {
    fileInput.value?.click();
}

function onDrop(e) {
    dragOver.value = false;
    const file = e.dataTransfer?.files?.[0];
    handleFile(file);
}

function onSelect(e) {
    handleFile(e.target.files?.[0]);
    e.target.value = '';
}

// Verifica los primeros bytes (magic numbers) para confirmar que es una imagen real,
// no un archivo renombrado.
function isRealImage(file) {
    return new Promise((resolve) => {
        const fr = new FileReader();
        fr.onload = () => {
            const bytes = new Uint8Array(fr.result);
            const hex = [...bytes.subarray(0, 12)].map((b) => b.toString(16).padStart(2, '0')).join('');
            const ok =
                hex.startsWith('ffd8ff') ||                                   // JPEG
                hex.startsWith('89504e47') ||                                 // PNG
                hex.startsWith('474946') ||                                   // GIF
                (hex.startsWith('52494646') && hex.substring(16, 24) === '57454250'); // WEBP (RIFF....WEBP)
            resolve(ok);
        };
        fr.onerror = () => resolve(false);
        fr.readAsArrayBuffer(file.slice(0, 12));
    });
}

async function handleFile(file) {
    uploadError.value = '';
    if (!file) return;
    if (!ALLOWED_TYPES.includes(file.type)) {
        uploadError.value = t('errType');
        return;
    }
    if (file.size > MAX_BYTES) {
        uploadError.value = t('errSize');
        return;
    }
    if (!(await isRealImage(file))) {
        uploadError.value = t('errCorrupt');
        return;
    }
    selectedFile.value = file;
    const reader = new FileReader();
    reader.onload = (ev) => { modalPreview.value = ev.target.result; };
    reader.readAsDataURL(file);
}

function uploadAvatar() {
    if (!selectedFile.value) return;
    form.avatar = selectedFile.value; // el File va dentro del form
    uploadProgress.value = 0;
    form.post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
        onProgress: (event) => { uploadProgress.value = event?.percentage ? Math.round(event.percentage) : 0; },
        onSuccess: () => {
            avatarPreview.value = page.props.auth.user.avatar_url || modalPreview.value;
            form.avatar = '';
            showUploadModal.value = false;
            selectedFile.value = null;
            modalPreview.value = '';
            uploadProgress.value = 0;
        },
        onError: () => { uploadProgress.value = 0; },
    });
}

const submit = () => {
    if (form.avatar instanceof File) {
        // Con archivo: POST + _method=patch (spoofing) en multipart, que PHP sí parsea.
        form.post(route('profile.update'), { preserveScroll: true, forceFormData: true });
    } else {
        // Sin archivo: PATCH real (JSON). Evita el bug de multipart en PATCH.
        form.patch(route('profile.update'), { preserveScroll: true });
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
                        <img v-if="avatarPreview && (avatarPreview.startsWith('http') || avatarPreview.startsWith('/') || avatarPreview.startsWith('data:'))" :src="avatarPreview" class="w-full h-full object-cover" />
                        <span v-else>{{ avatarPreview || '?' }}</span>
                    </div>
                    <button type="button" @click="showEmojiPicker = !showEmojiPicker"
                        class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-sm text-white/70 hover:bg-white/10 transition-colors">
                        {{ t('emoji') }}
                    </button>
                    <button type="button" @click="openUploadModal"
                        class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-sm text-white/70 hover:bg-white/10 transition-colors">
                        {{ t('uploadPhoto') }}
                    </button>
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

        <!-- Modal: subir foto -->
        <Modal :show="showUploadModal" max-width="md" @close="closeUploadModal">
            <div class="p-6 bg-[#0e0e11] border border-white/10">
                <h3 class="text-base font-semibold text-white mb-4">{{ t('uploadTitle') }}</h3>

                <!-- Dropzone / preview -->
                <div v-if="!selectedFile"
                     @click="triggerFileDialog"
                     @dragover.prevent="dragOver = true"
                     @dragleave.prevent="dragOver = false"
                     @drop.prevent="onDrop"
                     class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-8 cursor-pointer transition-colors"
                     :class="dragOver ? 'border-orange-500 bg-orange-500/5' : 'border-white/15 hover:border-white/30 bg-white/[0.02]'">
                    <svg class="w-9 h-9 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V18a3 3 0 003 3h12a3 3 0 003-3v-1.5M7.5 9L12 4.5m0 0L16.5 9M12 4.5v12" />
                    </svg>
                    <p class="text-sm text-white/70 font-medium">{{ t('dropHere') }}</p>
                    <p class="text-xs text-white/40">{{ t('orClick') }}</p>
                    <p class="text-[11px] text-white/25 mt-1">{{ t('allowedTypes') }}</p>
                </div>

                <div v-else class="flex flex-col items-center gap-4">
                    <div class="w-32 h-32 rounded-full overflow-hidden border border-white/10 bg-white/5">
                        <img :src="modalPreview" class="w-full h-full object-cover" alt="preview" />
                    </div>
                    <!-- Barra de progreso -->
                    <div v-if="form.processing" class="w-full">
                        <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                            <div class="h-full bg-orange-500 transition-all duration-150" :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                        <p class="text-xs text-white/50 mt-1 text-center">{{ t('uploading') }} {{ uploadProgress }}%</p>
                    </div>
                </div>

                <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="onSelect" />

                <p v-if="uploadError" class="mt-3 text-sm text-red-400">{{ uploadError }}</p>
                <p v-if="form.errors.avatar" class="mt-3 text-sm text-red-400">{{ form.errors.avatar }}</p>
                <p class="mt-3 text-[11px] text-white/30 leading-relaxed">🔒 {{ t('secureNote') }}</p>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" @click="closeUploadModal" :disabled="form.processing"
                            class="px-4 py-2 rounded-lg text-sm text-white/60 hover:text-white/90 hover:bg-white/5 transition-colors disabled:opacity-40">
                        {{ t('cancel') }}
                    </button>
                    <button v-if="selectedFile && !form.processing" type="button" @click="selectedFile = null; modalPreview = ''"
                            class="px-4 py-2 rounded-lg text-sm bg-white/5 border border-white/10 text-white/70 hover:bg-white/10 transition-colors">
                        {{ t('chooseAnother') }}
                    </button>
                    <button v-if="selectedFile" type="button" @click="uploadAvatar" :disabled="form.processing"
                            class="px-4 py-2 rounded-lg text-sm font-semibold bg-orange-500 text-black hover:bg-orange-400 transition-colors disabled:opacity-50">
                        {{ form.processing ? t('uploading') : t('useThisPhoto') }}
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
