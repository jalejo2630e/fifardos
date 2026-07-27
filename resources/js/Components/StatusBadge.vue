<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, default: 'pending' },
});

const config = computed(() => {
    switch (props.status) {
        case 'completed':
            return { cls: 'ucl-badge-done', icon: '🏆', label: 'FINALIZADO', dot: false };
        case 'in_progress':
            return { cls: 'ucl-badge-live', icon: '⚡', label: 'EN CURSO', dot: true };
        default:
            return { cls: 'ucl-badge-pending', icon: '⚙', label: 'CONFIG', dot: false };
    }
});
</script>

<template>
    <span :class="config.cls">
        <span v-if="config.dot" class="relative flex h-2 w-2">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-ucl-cyan opacity-75" />
            <span class="relative inline-flex h-2 w-2 rounded-full bg-ucl-cyan" />
        </span>
        <span>{{ config.icon }}</span>
        <span>{{ config.label }}</span>
    </span>
</template>
