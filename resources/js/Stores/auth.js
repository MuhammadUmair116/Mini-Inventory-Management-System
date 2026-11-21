import { router } from '@inertiajs/vue3';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);

    const isAuthenticated = computed(() => user.value !== null);
    const isAdmin = computed(() => user.value?.is_admin === true || user.value?.role === 'admin');

    function setUser(value) {
        user.value = value ?? null;
    }

    function logout() {
        router.post(route('logout'));
    }

    return { user, isAuthenticated, isAdmin, setUser, logout };
});
