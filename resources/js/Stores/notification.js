import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref([]);
    let sequence = 0;

    function notify(message, type = 'success', title = null) {
        const id = ++sequence;

        notifications.value.push({ id, message, type, title });

        setTimeout(() => dismiss(id), 5000);

        return id;
    }

    function dismiss(id) {
        notifications.value = notifications.value.filter((item) => item.id !== id);
    }

    return { notifications, notify, dismiss };
});
