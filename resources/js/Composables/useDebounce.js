import { onBeforeUnmount, ref } from 'vue';

export function useDebounce(callback, delay = 300) {
    const timer = ref(null);

    function debounced(...args) {
        if (timer.value) {
            clearTimeout(timer.value);
        }

        timer.value = setTimeout(() => callback(...args), delay);
    }

    onBeforeUnmount(() => {
        if (timer.value) {
            clearTimeout(timer.value);
        }
    });

    return debounced;
}
