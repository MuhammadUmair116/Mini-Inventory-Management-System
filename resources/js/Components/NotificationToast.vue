<script setup>
import { Bell, CheckCircle2, CircleAlert, X } from '@lucide/vue';
import { useNotificationStore } from '../Stores/notification';

const notifications = useNotificationStore();

const styles = {
    success: 'border-emerald-200 bg-white text-emerald-700',
    error: 'border-rose-200 bg-white text-rose-700',
    info: 'border-indigo-200 bg-white text-indigo-700',
};

const icons = {
    success: CheckCircle2,
    error: CircleAlert,
    info: Bell,
};
</script>

<template>
    <div
        class="pointer-events-none fixed bottom-4 right-4 z-50 flex w-full max-w-sm flex-col gap-2"
        role="status"
        aria-live="polite"
        aria-atomic="false"
    >
        <TransitionGroup
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-for="notification in notifications.notifications"
                :key="notification.id"
                class="pointer-events-auto flex items-start gap-3 rounded-xl border p-4 shadow-lg"
                :class="styles[notification.type] ?? styles.info"
                :role="notification.type === 'error' ? 'alert' : undefined"
            >
                <component
                    :is="icons[notification.type] ?? icons.info"
                    :size="18"
                    class="mt-0.5 shrink-0"
                />
                <div class="min-w-0 flex-1">
                    <p v-if="notification.title" class="text-sm font-semibold">
                        {{ notification.title }}
                    </p>
                    <p class="text-sm text-gray-600">{{ notification.message }}</p>
                </div>
                <button
                    type="button"
                    class="rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    aria-label="Dismiss notification"
                    @click="notifications.dismiss(notification.id)"
                >
                    <X :size="15" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
