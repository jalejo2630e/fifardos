<script setup>
const props = defineProps({
    probability: { type: Number, default: 0 },
    home: { type: String, default: 'Equipo A' },
    away: { type: String, default: 'Equipo B' },
    compact: { type: Boolean, default: false },
});
</script>

<template>
  <div class="prob-card" :class="{ compact }">
    <div v-if="!compact" class="prob-header">
      <span class="prob-title">{{ label || 'Probabilidad' }}</span>
    </div>
    <div class="prob-teams">
      <span class="prob-team">{{ home }}</span>
      <span class="prob-team">{{ away }}</span>
    </div>
    <div class="prob-bar-track">
      <div class="prob-bar-segment prob-win" :style="{ width: probability * 100 + '%' }" />
      <div class="prob-bar-segment prob-draw" :style="{ width: (1 - probability) * 50 + '%' }" />
      <div class="prob-bar-segment prob-loss" :style="{ width: (1 - probability) * 50 + '%' }" />
    </div>
    <div v-if="!compact" class="prob-labels">
      <span class="prob-label win-label">{{ Math.round(probability * 100) }}%</span>
      <span class="prob-label draw-label">{{ Math.round((1 - probability) * 50) }}%</span>
      <span class="prob-label loss-label">{{ Math.round((1 - probability) * 50) }}%</span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    label: { type: String, default: '' },
    probability: { type: Number, default: 0 },
    home: { type: String, default: 'Equipo A' },
    away: { type: String, default: 'Equipo B' },
    compact: { type: Boolean, default: false },
  }
}
</script>

<style scoped>
.prob-card {
  background: var(--card-bg, #242b3d);
  border: 1px solid var(--card-border, #343d54);
  border-radius: 10px;
  padding: 12px;
}
.prob-card.compact {
  padding: 8px 12px;
}
.prob-header {
  margin-bottom: 8px;
}
.prob-title {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--text-muted, #7a8299);
  font-family: 'Bebas Neue', 'Oswald', sans-serif;
}
.prob-teams {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  font-weight: 500;
  color: var(--text-secondary, #9aa3bb);
  margin-bottom: 6px;
}
.prob-bar-track {
  height: 6px;
  display: flex;
  border-radius: 4px;
  overflow: hidden;
}
.prob-bar-segment {
  height: 100%;
  transition: width 0.6s ease;
}
.prob-win {
  background: var(--accent-blue, #3d9bff);
}
.prob-draw {
  background: var(--accent-gold, #ffb35e);
}
.prob-loss {
  background: var(--accent-orange, #ff8a3d);
}
.prob-labels {
  display: flex;
  justify-content: space-between;
  margin-top: 4px;
  font-size: 9px;
  font-weight: 600;
  font-family: 'Bebas Neue', 'Oswald', sans-serif;
}
.win-label { color: var(--accent-blue, #3d9bff); }
.draw-label { color: var(--accent-gold, #ffb35e); }
.loss-label { color: var(--accent-orange, #ff8a3d); }
</style>
