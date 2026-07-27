import { ref } from 'vue';

export function useMouseSpotlight() {
    const x = ref(50);
    const y = ref(50);
    let el = null;

    function onMove(e) {
        if (!el) return;
        const rect = el.getBoundingClientRect();
        x.value = ((e.clientX - rect.left) / rect.width) * 100;
        y.value = ((e.clientY - rect.top) / rect.height) * 100;
    }

    function onEnter(instance) {
        el = instance?.$el || instance;
    }

    function onLeave() {
        x.value = 50;
        y.value = 50;
    }

    return { x, y, onMove, onEnter, onLeave };
}
