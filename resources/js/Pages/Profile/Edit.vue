<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n({
    useScope: 'local',
    messages: {
        es: {
            title: 'Perfil',
            header: 'Perfil',
        },
        en: {
            title: 'Profile',
            header: 'Profile',
        },
    },
});

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    catalog: {
        type: Array,
        default: () => [],
    },
    securityQuestions: {
        type: Array,
        default: () => ['', '', ''],
    },
    hasSecuritySetup: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Head :title="t('title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-white/80">
                {{ t('header') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="ucl-card p-5 sm:p-6">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="ucl-card p-5 sm:p-6">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="ucl-card p-5 sm:p-6">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
