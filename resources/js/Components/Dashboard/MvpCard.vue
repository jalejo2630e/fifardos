<script setup>
const props = defineProps({
    name: { type: String, default: '' },
    goals: { type: Number, default: 0 },
    matches: { type: Number, default: 0 },
    initials: { type: String, default: '??' },
    score: { type: Number, default: 85 },
});
</script>

<template>
  <div class="mvp-card">
    <div class="mvp-header">
      <svg class="mvp-crown" width="14" height="10" viewBox="0 0 14 10" fill="none">
        <path d="M7 0L8.57 3.18L12 3.68L9.5 6.12L10.14 9.5L7 7.92L3.86 9.5L4.5 6.12L2 3.68L5.43 3.18L7 0Z" fill="#ffb35e"/>
      </svg>
      <span class="mvp-label">MVP DEL DÍA</span>
    </div>
    <div class="mvp-body">
      <div class="mvp-avatar">{{ initials }}</div>
      <div class="mvp-info">
        <div class="mvp-name">{{ name }}</div>
        <div class="mvp-position">Delantero</div>
        <div class="mvp-line"><span class="mvp-stat-label">Goles:</span> <span class="mvp-stat-value">{{ goals }}</span></div>
        <div class="mvp-line"><span class="mvp-stat-label">Partidos:</span> <span class="mvp-stat-value">{{ matches }}</span></div>
      </div>
      <div class="mvp-badge">{{ score }}</div>
    </div>
  </div>
</template>

<style scoped>
.mvp-card {
  background: var(--card-bg, #242b3d);
  border: 1px solid var(--card-border, #343d54);
  border-radius: 12px;
  padding: 14px;
}
.mvp-header {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 10px;
}
.mvp-crown {
  flex-shrink: 0;
}
.mvp-label {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--accent-gold, #ffb35e);
  font-family: 'Bebas Neue', 'Oswald', sans-serif;
}
.mvp-body {
  display: flex;
  align-items: center;
  gap: 10px;
}
.mvp-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--accent-orange, #ff8a3d);
  color: #000;
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-family: 'Bebas Neue', 'Oswald', sans-serif;
}
.mvp-info {
  flex: 1;
  min-width: 0;
}
.mvp-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary, #f4f2ef);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.mvp-position {
  font-size: 10px;
  color: var(--text-muted, #7a8299);
  margin-bottom: 3px;
}
.mvp-line {
  font-size: 10px;
  color: var(--text-secondary, #9aa3bb);
}
.mvp-stat-label {
  color: var(--text-muted, #7a8299);
}
.mvp-stat-value {
  font-weight: 600;
  color: var(--text-primary, #f4f2ef);
}
.mvp-badge {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--accent-orange, #ff8a3d);
  color: #000;
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-family: 'Bebas Neue', 'Oswald', sans-serif;
}
</style>
