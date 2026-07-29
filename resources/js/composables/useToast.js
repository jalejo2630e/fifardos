import { reactive } from 'vue';

// Store singleton a nivel de módulo — compartido por toda la app
const state = reactive({
    toasts: [],
});

let counter = 0;

function push(message, type = 'success', options = {}) {
    if (!message) return null;
    const id = ++counter;
    const toast = {
        id,
        message,
        type, // success | error | info | warning
        title: options.title ?? null,
        duration: options.duration ?? 4200,
    };
    state.toasts.push(toast);

    if (toast.duration > 0) {
        setTimeout(() => dismiss(id), toast.duration);
    }
    return id;
}

function dismiss(id) {
    const i = state.toasts.findIndex((t) => t.id === id);
    if (i !== -1) state.toasts.splice(i, 1);
}

export function useToast() {
    return {
        toasts: state.toasts,
        toast: (message, options = {}) => push(message, 'success', options),
        success: (message, options = {}) => push(message, 'success', options),
        error: (message, options = {}) => push(message, 'error', options),
        info: (message, options = {}) => push(message, 'info', options),
        warning: (message, options = {}) => push(message, 'warning', options),
        dismiss,
    };
}
