<script setup>
const props = defineProps({
    label: { type: String, default: '' },
    value: { type: Number, default: 0 },
    color: { type: String, default: 'var(--accent-orange, #ff8a3d)' },
});
</script>

<template>
  <div class="metric-card">
    <div class="metric-label">{{ label }}</div>
    <div class="metric-value" :style="{ color: color }">{{ value }}</div>
  </div>
</template>

<style scoped>
.metric-card {
  background: var(--card-bg, #242b3d);
  border: 1px solid var(--card-border, #343d54);
  border-radius: 10px;
  padding: 12px;
  text-align: center;
}
.metric-label {
  font-size: 10px;
  color: var(--text-muted, #7a8299);
  font-weight: 500;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.metric-value {
  font-size: 24px;
  font-weight: 700;
  font-family: 'Bebas Neue', 'Oswald', sans-serif;
  line-height: 1;
}
</style>
