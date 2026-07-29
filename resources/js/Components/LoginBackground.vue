<template>
  <div class="bg-decoration" aria-hidden="true">
    <!-- Ball -->
    <div class="ball">
      <svg viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg" class="ball-svg">
        <circle cx="35" cy="35" r="33" fill="#2a2a2a" stroke="#444" stroke-width="2"/>
        <path d="M35 2v66M2 35h66" stroke="#444" stroke-width="1.5" opacity="0.5"/>
        <polygon points="35,8 44,22 59,22 48,33 52,48 35,40 18,48 22,33 11,22 26,22" fill="#f5f5f5" opacity="0.9"/>
        <polygon points="35,14 41,23 52,23 44,31 47,42 35,36 23,42 26,31 18,23 29,23" fill="#e0e0e0" opacity="0.6"/>
      </svg>
    </div>

    <!-- Trophy -->
    <div class="trophy">
      <svg viewBox="0 0 64 90" fill="none" xmlns="http://www.w3.org/2000/svg" class="trophy-svg">
        <path d="M16 12h32v4c0 8-4 20-16 28-12-8-16-20-16-28v-4z" fill="#ffb35e" stroke="#d4893a" stroke-width="1.5"/>
        <rect x="22" y="44" width="20" height="6" rx="2" fill="#d4893a"/>
        <rect x="20" y="50" width="24" height="4" rx="2" fill="#b87333"/>
        <rect x="26" y="54" width="12" height="8" rx="2" fill="#b87333"/>
        <rect x="22" y="62" width="20" height="20" rx="3" fill="#ffb35e" stroke="#d4893a" stroke-width="1.5"/>
        <rect x="16" y="82" width="32" height="6" rx="3" fill="#d4893a"/>
        <ellipse cx="32" cy="10" rx="8" ry="6" fill="#ffe066" opacity="0.8"/>
        <circle cx="32" cy="10" r="3" fill="#fff9c4" opacity="0.6"/>
        <path d="M12 16c-4 0-8 4-8 8s4 6 8 4" stroke="#ffb35e" stroke-width="2.5" fill="none" stroke-linecap="round"/>
        <path d="M52 16c4 0 8 4 8 8s-4 6-8 4" stroke="#ffb35e" stroke-width="2.5" fill="none" stroke-linecap="round"/>
        <path d="M12 24c-2-1-3-3-2-5" stroke="#ffe066" stroke-width="1.5" opacity="0.6" stroke-linecap="round"/>
        <path d="M52 24c2-1 3-3 2-5" stroke="#ffe066" stroke-width="1.5" opacity="0.6" stroke-linecap="round"/>
      </svg>
    </div>

    <!-- Confetti -->
    <div class="confetti confetti-1" />
    <div class="confetti confetti-2" />
    <div class="confetti confetti-3" />
    <div class="confetti confetti-4" />
    <div class="confetti confetti-5" />
    <div class="confetti confetti-6" />
  </div>
</template>

<style>
.bg-decoration {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.ball {
  position: absolute;
  bottom: 70px;
  left: 70px;
  width: 70px;
  height: 70px;
  opacity: 0.8;
  animation: bounce-ball 6s ease-in-out infinite;
}

.ball-svg {
  width: 100%;
  height: 100%;
}

.trophy {
  position: absolute;
  top: 60px;
  right: 60px;
  width: 64px;
  height: 90px;
  opacity: 0.9;
  animation: trophy-glow 3.5s ease-in-out infinite;
}

.trophy-svg {
  width: 100%;
  height: 100%;
}

.confetti {
  position: absolute;
  top: -10px;
  width: 8px;
  height: 8px;
  border-radius: 2px;
  opacity: 0;
  animation: fall linear infinite;
}

.confetti-1 {
  left: 20%;
  width: 7px;
  height: 7px;
  background: #ff8a3d;
  animation-duration: 5s;
  animation-delay: 0s;
  border-radius: 50%;
}

.confetti-2 {
  left: 38%;
  width: 6px;
  height: 6px;
  background: #3d9bff;
  animation-duration: 4.5s;
  animation-delay: 0.6s;
}

.confetti-3 {
  left: 55%;
  width: 8px;
  height: 8px;
  background: #ffd35e;
  animation-duration: 5.5s;
  animation-delay: 1s;
  border-radius: 50%;
}

.confetti-4 {
  left: 75%;
  width: 6px;
  height: 6px;
  background: #ff8a3d;
  animation-duration: 4s;
  animation-delay: 1.8s;
}

.confetti-5 {
  left: 88%;
  width: 7px;
  height: 7px;
  background: #3d9bff;
  animation-duration: 5.2s;
  animation-delay: 2.4s;
  border-radius: 50%;
}

.confetti-6 {
  left: 30%;
  width: 6px;
  height: 6px;
  background: #ffd35e;
  animation-duration: 4.8s;
  animation-delay: 3.2s;
}

@keyframes bounce-ball {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-24px) rotate(160deg);
  }
}

@keyframes trophy-glow {
  0%, 100% {
    filter: brightness(1);
    transform: scale(1);
  }
  50% {
    filter: brightness(1.35);
    transform: scale(1.04);
  }
}

@keyframes fall {
  0% {
    transform: translateY(-10px) rotate(0deg);
    opacity: 0;
  }
  10% {
    opacity: 0.9;
  }
  100% {
    transform: translateY(100vh) rotate(300deg);
    opacity: 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .bg-decoration * {
    animation: none !important;
  }
}
</style>
