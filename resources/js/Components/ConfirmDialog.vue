<script setup>
import Modal from './Modal.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Are you sure?',
    },
    message: {
        type: String,
        default: '',
    },
    confirmLabel: {
        type: String,
        default: 'Confirm',
    },
    cancelLabel: {
        type: String,
        default: 'Cancel',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    danger: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <Modal :show="show" max-width="md" :label="title" @close="emit('close')">
        <div class="p-6">
            <h3 class="text-base font-semibold text-gray-900">{{ title }}</h3>
            <p v-if="message" class="mt-2 text-sm leading-6 text-gray-500">{{ message }}</p>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    :disabled="loading"
                    @click="emit('close')"
                >
                    {{ cancelLabel }}
                </button>
                <button
                    type="button"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                    :class="danger ? 'bg-rose-600 hover:bg-rose-700' : 'bg-indigo-600 hover:bg-indigo-700'"
                    :disabled="loading"
                    @click="emit('confirm')"
                >
                    {{ loading ? 'Please wait…' : confirmLabel }}
                </button>
            </div>
        </div>
    </Modal>
</template>
