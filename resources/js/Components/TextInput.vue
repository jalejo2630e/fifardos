<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-lg border border-white/10 bg-elite-bg/80 px-4 py-2.5 text-sm text-white
               placeholder-white/30 shadow-sm transition-all duration-200
               focus:border-elite-secondary focus:outline-none focus:ring-1 focus:ring-elite-secondary"
        v-model="model"
        ref="input"
    />
</template>
