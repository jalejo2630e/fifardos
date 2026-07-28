import { ref, watch } from 'vue';

function easeOutQuad(t) {
    return t * (2 - t);
}

export function useCountUp(target, duration = 800) {
    const animated = ref(0);
    const targetRef = typeof target === 'number' ? ref(target) : target;
    let rafId = null;
    let startTime = null;
    let fromValue = 0;

    function animate(timestamp) {
        if (!startTime) startTime = timestamp;
        const elapsed = timestamp - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = easeOutQuad(progress);
        animated.value = Math.round(fromValue + (targetRef.value - fromValue) * eased);
        if (progress < 1) {
            rafId = requestAnimationFrame(animate);
        }
    }

    function start() {
        cancelAnimationFrame(rafId);
        startTime = null;
        fromValue = animated.value;
        rafId = requestAnimationFrame(animate);
    }

    watch(targetRef, () => start(), { flush: 'post' });

    start();

    return animated;
}
