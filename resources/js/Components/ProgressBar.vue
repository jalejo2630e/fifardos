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
            <span v-if="detail" class="text-xs font-condensed font-bold text-elite-secondary">{{ detail }}</span>
        </div>
        <div class="ucl-progress">
            <div
                class="h-full rounded-full transition-all duration-500 ease-out"
                :style="{
                    width: Math.min(value / max * 100, 100) + '%',
                    background: 'linear-gradient(90deg, #F97316, #ea580c)'
                }"
            />
        </div>
    </div>
</template>
