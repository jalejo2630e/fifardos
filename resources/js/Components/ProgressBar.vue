<script setup>
defineProps({
    value: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
    label: { type: String, default: '' },
    detail: { type: String, default: '' },
});
</script>

<template>
    <div class="space-y-2.5">
        <div v-if="label || detail" class="flex items-center justify-between">
            <span v-if="label" class="text-xs font-medium text-white/40">{{ label }}</span>
            <span v-if="detail" class="text-xs font-condensed font-bold text-ucl-cyan">{{ detail }}</span>
        </div>
        <div class="ucl-progress">
            <div class="ucl-progress-bar" :style="{ width: Math.min(value / max * 100, 100) + '%' }" />
        </div>
    </div>
</template>
