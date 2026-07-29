<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n({
    useScope: 'local',
    messages: {
        es: {
            heading: 'Eliminar Cuenta',
            subheading: 'Una vez eliminada tu cuenta, todos sus recursos y datos se eliminarán permanentemente. Antes de eliminar tu cuenta, descarga cualquier dato o información que desees conservar.',
            deleteAccount: 'Eliminar Cuenta',
            confirmTitle: '¿Estás seguro de que quieres eliminar tu cuenta?',
            confirmText: 'Una vez eliminada tu cuenta, todos sus recursos y datos se eliminarán permanentemente. Ingresa tu contraseña para confirmar que deseas eliminar tu cuenta permanentemente.',
            password: 'Contraseña',
            passwordPlaceholder: 'Contraseña',
            cancel: 'Cancelar',
        },
        en: {
            heading: 'Delete Account',
            subheading: 'Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.',
            deleteAccount: 'Delete Account',
            confirmTitle: 'Are you sure you want to delete your account?',
            confirmText: 'Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
            password: 'Password',
            passwordPlaceholder: 'Password',
            cancel: 'Cancel',
        },
    },
});

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                {{ t('heading') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ t('subheading') }}
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">{{ t('deleteAccount') }}</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2
                    class="text-lg font-medium text-gray-900"
                >
                    {{ t('confirmTitle') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ t('confirmText') }}
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        :value="t('password')"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        :placeholder="t('passwordPlaceholder')"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        {{ t('cancel') }}
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        {{ t('deleteAccount') }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
