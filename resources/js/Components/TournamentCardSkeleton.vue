<script setup>
import { computed } from 'vue';

const props = defineProps({
    index: { type: Number, default: 0 },
});

const delay = computed(() => props.index * 80 + 'ms');
</script>

<template>
    <div
        class="ucl-card p-5 sm:p-6 h-full flex flex-col animate-pulse"
        :style="{ animationDelay: delay }">
        <div class="stars-overlay" />

        <div class="relative flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/5" />
                <div class="space-y-2">
                    <div class="h-4 w-28 rounded bg-white/5" />
                    <div class="h-3 w-20 rounded bg-white/5" />
                </div>
            </div>
            <div class="h-6 w-24 rounded-full bg-white/5" />
        </div>

        <div class="relative space-y-2 mt-auto mb-4">
            <div class="flex items-center justify-between">
                <div class="h-3 w-16 rounded bg-white/5" />
                <div class="h-3 w-12 rounded bg-white/5" />
            </div>
            <div class="h-2.5 w-full rounded-full bg-white/5" />
        </div>

        <div class="relative flex items-center justify-between pt-4 border-t border-white/5">
            <div class="flex items-center gap-4">
                <div class="h-3 w-14 rounded bg-white/5" />
                <div class="h-3 w-16 rounded bg-white/5" />
            </div>
            <div class="w-4 h-4 rounded bg-white/5" />
        </div>
    </div>
</template>
