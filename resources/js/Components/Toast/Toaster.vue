<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const { toasts, success, error, info, dismiss } = useToast();
const page = usePage();

// Conectar los flash messages de Inertia con el toaster
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        if (flash.success) success(flash.success);
        if (flash.error) error(flash.error);
        if (flash.info) info(flash.info);
        if (flash.message) info(flash.message);
    },
    { deep: true, immediate: true },
);

const styles = {
    success: { ring: 'border-emerald-500/30', bar: 'bg-emerald-400', icon: 'text-emerald-400' },
    error:   { ring: 'border-rose-500/30',    bar: 'bg-rose-400',    icon: 'text-rose-400' },
    info:    { ring: 'border-sky-500/30',     bar: 'bg-sky-400',     icon: 'text-sky-400' },
    warning: { ring: 'border-amber-500/30',   bar: 'bg-amber-400',   icon: 'text-amber-400' },
};

const defaultTitles = {
    success: '¡Listo!',
    error: 'Ups…',
    info: 'Info',
    warning: 'Atención',
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed z-[100] bottom-4 right-4 left-4 sm:left-auto flex flex-col items-stretch sm:items-end gap-3 pointer-events-none">
            <TransitionGroup name="toast">
                <div
                    v-for="t in toasts"
                    :key="t.id"
                    class="pointer-events-auto w-full sm:w-80 max-w-sm overflow-hidden rounded-xl border bg-elite-surface/95 backdrop-blur-xl shadow-2xl shadow-black/40"
                    :class="(styles[t.type] || styles.info).ring"
                    role="status"
                    aria-live="polite"
                >
                    <div class="flex items-start gap-3 p-3.5">
                        <div class="mt-0.5 shrink-0" :class="(styles[t.type] || styles.info).icon">
                            <!-- success -->
                            <svg v-if="t.type === 'success'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <!-- error -->
                            <svg v-else-if="t.type === 'error'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                            <!-- warning -->
                            <svg v-else-if="t.type === 'warning'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
                            <!-- info -->
                            <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-white leading-tight">
                                {{ t.title || defaultTitles[t.type] || 'Info' }}
                            </p>
                            <p class="mt-0.5 text-sm text-white/70 leading-snug break-words">{{ t.message }}</p>
                        </div>

                        <button
                            @click="dismiss(t.id)"
                            class="shrink-0 -mt-1 -mr-1 p-1.5 rounded-lg text-white/40 hover:text-white hover:bg-white/10 transition"
                            aria-label="Cerrar notificación"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="h-0.5 w-full opacity-70" :class="(styles[t.type] || styles.info).bar" />
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(24px) scale(0.96);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(24px) scale(0.96);
}
.toast-leave-active {
    position: absolute;
    width: 100%;
}
@media (prefers-reduced-motion: reduce) {
    .toast-enter-active,
    .toast-leave-active {
        transition: opacity 0.2s ease;
    }
    .toast-enter-from,
    .toast-leave-to {
        transform: none;
    }
}
</style>
