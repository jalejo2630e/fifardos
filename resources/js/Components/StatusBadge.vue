<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, default: 'pending' },
});

const config = computed(() => {
    switch (props.status) {
        case 'completed':
            return { cls: 'ucl-badge-done', icon: '🏆', label: 'FINALIZADO' };
        case 'in_progress':
            return { cls: 'ucl-badge-live', icon: '⚡', label: 'EN CURSO' };
        default:
            return { cls: 'ucl-badge-pending', icon: '⚙', label: 'CONFIG' };
    }
});
</script>

<template>
    <span :class="config.cls">
        <span>{{ config.icon }}</span>
        <span>{{ config.label }}</span>
    </span>
</template>
