<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    tournament: { type: Object, required: true },
});

function urgencyLabel(t) {
    if (t.urgency === 'final') return 'Final hoy';
    if (t.urgency === 'soon') return 'Final mañana';
    if (t.urgency === 'normal') return t.remaining + ' días';
    return t.remaining + ' días';
}

function urgencyClass(t) {
    if (t.urgency === 'final' || t.urgency === 'soon') return 'badge-urgent';
    if (t.urgency === 'normal') return 'badge-normal';
    return 'badge-relaxed';
}
</script>

<template>
  <Link :href="route('tournaments.show', tournament.id)" class="ending-card">
    <div class="ending-top">
      <div class="ending-name">{{ tournament.name }}</div>
      <div class="ending-badge" :class="urgencyClass(tournament)">{{ urgencyLabel(tournament) }}</div>
    </div>
    <div class="ending-progress-track">
      <div class="ending-progress-fill" :style="{ width: tournament.pct + '%' }" />
    </div>
    <div class="ending-bottom">
      <span class="ending-count">{{ tournament.played }} de {{ tournament.total }} partidos jugados</span>
      <span class="ending-players">{{ tournament.players_count }} jug.</span>
    </div>
  </Link>
</template>

<style scoped>
.ending-card {
  background: var(--card-bg, #242b3d);
  border: 1px solid var(--card-border, #343d54);
  border-radius: 10px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  transition: transform .15s ease;
}
.ending-card:hover {
  transform: translateY(-2px);
}

.ending-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.ending-name {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-primary, #f4f2ef);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ending-badge {
  font-size: 9px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 10px;
  white-space: nowrap;
  font-family: 'Bebas Neue', 'Oswald', sans-serif;
  letter-spacing: 0.05em;
}
.badge-urgent {
  background: var(--accent-orange-dark, #33261a);
  color: var(--accent-orange, #ff8a3d);
  border: 1px solid #5a3820;
}
.badge-normal {
  background: var(--accent-blue-dark, #1e2a3d);
  color: var(--accent-blue, #3d9bff);
  border: 1px solid #2a3a55;
}
.badge-relaxed {
  background: rgba(255,255,255,0.05);
  color: var(--text-muted, #7a8299);
  border: 1px solid var(--card-border, #343d54);
}
.ending-progress-track {
  height: 4px;
  background: rgba(255,255,255,0.06);
  border-radius: 4px;
  overflow: hidden;
}
.ending-progress-fill {
  height: 100%;
  background: var(--accent-orange, #ff8a3d);
  border-radius: 4px;
  transition: width 0.6s ease;
}
.ending-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.ending-count {
  font-size: 10px;
  color: var(--text-secondary, #9aa3bb);
}
.ending-players {
  font-size: 10px;
  color: var(--text-muted, #7a8299);
}
</style>
