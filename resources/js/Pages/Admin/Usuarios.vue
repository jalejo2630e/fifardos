<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';

const { t } = useI18n({
    useScope: 'local',
    messages: {
        es: {
            title: 'Usuarios',
            h1Lead: 'Usuarios',
            h1Accent: 'de la plataforma',
            subtitle: 'Gestioná los usuarios y sus permisos de administrador',
            searchPlaceholder: 'Buscar por nombre o email...',
            colUser: 'Usuario',
            colTournaments: 'Torneos',
            colCreated: 'Registro',
            colRole: 'Rol',
            colAction: 'Acción',
            admin: 'Administrador',
            user: 'Usuario',
            you: 'Vos',
            makeAdmin: 'Hacer admin',
            removeAdmin: 'Quitar admin',
            empty: 'No hay usuarios que coincidan.',
            tournaments: '{n} torneos',
        },
        en: {
            title: 'Users',
            h1Lead: 'Platform',
            h1Accent: 'users',
            subtitle: 'Manage users and their administrator permissions',
            searchPlaceholder: 'Search by name or email...',
            colUser: 'User',
            colTournaments: 'Tournaments',
            colCreated: 'Signed up',
            colRole: 'Role',
            colAction: 'Action',
            admin: 'Administrator',
            user: 'User',
            you: 'You',
            makeAdmin: 'Make admin',
            removeAdmin: 'Remove admin',
            empty: 'No matching users.',
            tournaments: '{n} tournaments',
        },
    },
});

const props = defineProps({
    users: Array,
    filters: Object,
});

const page = usePage();
const toast = useToast();
const search = ref(props.filters?.q || '');
const togglingId = ref(null);

let searchTimer = null;
function onSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('admin.usuarios'), { q: search.value }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
}

function toggleAdmin(u) {
    if (u.is_self || togglingId.value) return;
    togglingId.value = u.id;
    router.post(route('admin.usuarios.toggle-admin', u.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            const s = page.props.flash?.success;
            const e = page.props.flash?.error;
            if (e) toast.error(e); else if (s) toast.success(s);
        },
        onError: () => toast.error('No se pudo cambiar el rol.'),
        onFinish: () => { togglingId.value = null; },
    });
}
</script>

<template>
    <Head :title="t('title')" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="ucl-title-lg">{{ t('h1Lead') }} <span class="text-elite-secondary">{{ t('h1Accent') }}</span></h1>
                <p class="ucl-meta mt-1">{{ t('subtitle') }}</p>
            </div>
        </template>

        <div class="py-6 sm:py-8 lg:py-10">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- Search -->
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                    </svg>
                    <input v-model="search" @input="onSearch" type="text" :placeholder="t('searchPlaceholder')"
                           class="w-full pl-9 pr-3 py-2.5 rounded-lg bg-white/5 border border-white/10 text-sm text-white placeholder-white/30 focus:outline-none focus:border-elite-secondary/50" />
                </div>

                <!-- Users table -->
                <div class="ucl-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-white/[0.02] text-white/40 text-xs uppercase tracking-wider">
                                    <th class="text-left font-semibold px-5 py-3">{{ t('colUser') }}</th>
                                    <th class="text-center font-semibold px-3 py-3 hidden sm:table-cell">{{ t('colTournaments') }}</th>
                                    <th class="text-center font-semibold px-3 py-3 hidden md:table-cell">{{ t('colCreated') }}</th>
                                    <th class="text-center font-semibold px-3 py-3">{{ t('colRole') }}</th>
                                    <th class="text-right font-semibold px-5 py-3">{{ t('colAction') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="u in users" :key="u.id" class="border-t border-white/5 hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-9 h-9 rounded-full bg-gradient-to-br from-elite-secondary/30 to-orange-700/20 border border-white/10 flex items-center justify-center text-xs font-bold text-white/80 shrink-0">
                                                {{ u.name.charAt(0).toUpperCase() }}
                                            </span>
                                            <div class="min-w-0">
                                                <div class="font-medium text-white/90 truncate">
                                                    {{ u.name }}
                                                    <span v-if="u.is_self" class="ml-1 text-[10px] uppercase tracking-wide text-white/30">· {{ t('you') }}</span>
                                                </div>
                                                <div class="text-xs text-white/40 truncate">{{ u.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center px-3 py-3 text-white/50 hidden sm:table-cell">{{ u.tournaments_count }}</td>
                                    <td class="text-center px-3 py-3 text-white/40 hidden md:table-cell">{{ u.created_at }}</td>
                                    <td class="text-center px-3 py-3">
                                        <span v-if="u.is_admin" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-semibold bg-elite-secondary/15 text-elite-secondary">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6l7-3z"/></svg>
                                            {{ t('admin') }}
                                        </span>
                                        <span v-else class="text-[11px] text-white/40">{{ t('user') }}</span>
                                    </td>
                                    <td class="text-right px-5 py-3">
                                        <button v-if="!u.is_self" @click="toggleAdmin(u)" :disabled="togglingId === u.id"
                                                class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all disabled:opacity-40"
                                                :class="u.is_admin
                                                    ? 'bg-white/5 text-white/60 border border-white/10 hover:bg-red-500/15 hover:text-red-300'
                                                    : 'bg-elite-secondary/15 text-elite-secondary hover:bg-elite-secondary/25'">
                                            {{ u.is_admin ? t('removeAdmin') : t('makeAdmin') }}
                                        </button>
                                        <span v-else class="text-xs text-white/20">—</span>
                                    </td>
                                </tr>
                                <tr v-if="!users.length">
                                    <td colspan="5" class="px-5 py-8 text-center text-sm text-white/30">{{ t('empty') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
:deep(.ucl-card) { background: #0e0e11; border: 1px solid rgba(255,255,255,.1); border-radius: 0; box-shadow: none; }
:deep(.ucl-card)::before { display: none; }
:deep(.ucl-title-lg) { font-family: 'Anton', Impact, sans-serif; text-transform: uppercase; letter-spacing: -.5px; }
</style>
