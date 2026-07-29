<script setup>
const props = defineProps({
    home: { type: String, default: 'Local' },
    away: { type: String, default: 'Visitante' },
    homeScore: { type: Number, default: 0 },
    awayScore: { type: Number, default: 0 },
    minute: { type: Number, default: 0 },
    tournament: { type: String, default: '' },
});
</script>

<template>
  <div class="live-match">
    <!-- Field -->
    <div class="field">
      <div class="field-center-circle" />
      <div class="field-center-line" />

      <!-- Players -->
      <div class="player-dot player-home" />
      <div class="player-dot player-away" />
      <div class="ball-dot" />

      <!-- Score overlay -->
      <div class="score-overlay">
        <span class="score-team score-home">{{ home }}</span>
        <span class="score-value">{{ homeScore }}</span>
        <span class="score-separator">:</span>
        <span class="score-value">{{ awayScore }}</span>
        <span class="score-team score-away">{{ away }}</span>
      </div>

      <!-- Live indicator -->
      <div class="live-indicator">
        <span class="live-dot" />
        <span class="live-text">EN CURSO · {{ tournament ? tournament + ' · ' : '' }}{{ minute }}'</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.live-match {
  border-radius: 12px;
  overflow: hidden;
}
.field {
  position: relative;
  background: #1a3324;
  height: 130px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.field-center-line {
  position: absolute;
  left: 50%;
  top: 0;
  bottom: 0;
  width: 1px;
  background: rgba(255,255,255,0.08);
}
.field-center-circle {
  position: absolute;
  left: 50%;
  top: 50%;
  width: 50px;
  height: 50px;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 50%;
  transform: translate(-50%, -50%);
}
.player-dot {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  position: absolute;
}
.player-home {
  background: var(--accent-blue, #3d9bff);
  box-shadow: 0 0 8px rgba(61, 155, 255, 0.5);
  animation: moveHome 4s ease-in-out infinite;
}
.player-away {
  background: #3ba85a;
  box-shadow: 0 0 8px rgba(59, 168, 90, 0.5);
  animation: moveAway 4s ease-in-out infinite;
}
.ball-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #fff;
  position: absolute;
  animation: moveBall 4s ease-in-out infinite;
  box-shadow: 0 0 6px rgba(255,255,255,0.6);
}
.score-overlay {
  position: absolute;
  top: 12px;
  right: 14px;
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(0,0,0,0.5);
  padding: 4px 10px;
  border-radius: 8px;
  font-family: 'Bebas Neue', 'Oswald', sans-serif;
}
.score-team {
  font-size: 9px;
  color: rgba(255,255,255,0.6);
  max-width: 50px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.score-value {
  font-size: 16px;
  font-weight: 700;
  color: #fff;
  min-width: 16px;
  text-align: center;
}
.score-separator {
  font-size: 12px;
  color: rgba(255,255,255,0.3);
}
.live-indicator {
  position: absolute;
  bottom: 10px;
  left: 14px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.live-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #ef4444;
  animation: pulse-live 1.2s ease-in-out infinite;
}
.live-text {
  font-size: 9px;
  color: rgba(255,255,255,0.4);
  font-family: 'Bebas Neue', 'Oswald', sans-serif;
  letter-spacing: 0.05em;
}

@keyframes pulse-live {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(1.4); }
}

@keyframes moveHome {
  0%, 100% { top: 42%; left: 22%; }
  25% { top: 28%; left: 26%; }
  50% { top: 55%; left: 20%; }
  75% { top: 38%; left: 24%; }
}

@keyframes moveAway {
  0%, 100% { top: 56%; left: 72%; }
  25% { top: 44%; left: 68%; }
  50% { top: 68%; left: 74%; }
  75% { top: 50%; left: 70%; }
}

@keyframes moveBall {
  0%, 100% { top: 48%; left: 40%; }
  20% { top: 44%; left: 48%; }
  40% { top: 52%; left: 36%; }
  60% { top: 46%; left: 60%; }
  80% { top: 54%; left: 44%; }
}
</style>
