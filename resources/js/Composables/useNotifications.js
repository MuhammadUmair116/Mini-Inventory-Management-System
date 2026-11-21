import { useNotificationStore } from '../Stores/notification';

export function useNotifications() {
    const notifications = useNotificationStore();

    return {
        success: (message, title = 'Success') => notifications.notify(message, 'success', title),
        error: (message, title = 'Error') => notifications.notify(message, 'error', title),
        info: (message, title = 'Information') => notifications.notify(message, 'info', title),
        dismiss: (id) => notifications.dismiss(id),
        items: notifications.notifications,
    };
}
