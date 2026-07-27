import { ref, onMounted, onUnmounted } from 'vue';

export function useScrollReveal(options = {}) {
    const elements = ref([]);
    let observer = null;

    const observe = (el) => {
        if (!el) return;
        elements.value.push(el);
        if (observer) {
            observer.observe(el);
        }
    };

    onMounted(() => {
        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: options.threshold || 0.1, ...options });

        elements.value.forEach((el) => observer.observe(el));
    });

    onUnmounted(() => {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
    });

    return { observe };
}
