<script setup>
import { computed, useAttrs, useId } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Select an option',
    },
    error: {
        type: String,
        default: '',
    },
    hint: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const attrs = useAttrs();
const generatedId = useId();
const selectId = computed(() => attrs.id ?? `select-${generatedId}`);
const hintId = computed(() => `${selectId.value}-hint`);
const errorId = computed(() => `${selectId.value}-error`);

const describedBy = computed(() => {
    const ids = [attrs['aria-describedby']];

    if (props.error) {
        ids.push(errorId.value);
    } else if (props.hint) {
        ids.push(hintId.value);
    }

    const existing = ids.filter(Boolean);

    return existing.length ? existing.join(' ') : undefined;
});

const accessibleLabel = computed(() => {
    if (props.label) {
        return undefined;
    }

    return attrs['aria-label'] || attrs.name || props.placeholder;
});
</script>

<template>
    <div>
        <label
            v-if="label"
            :for="selectId"
            class="mb-1.5 block text-sm font-medium text-gray-700"
        >
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>

        <select
            :id="selectId"
            :value="modelValue"
            :required="required"
            v-bind="$attrs"
            :aria-label="accessibleLabel"
            :aria-describedby="describedBy"
            :aria-invalid="error ? true : undefined"
            class="block w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2"
            :class="[
                error
                    ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/20'
                    : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/20',
                $attrs.disabled ? 'cursor-not-allowed bg-gray-50 text-gray-400' : '',
            ]"
            @change="emit('update:modelValue', $event.target.value)"
        >
            <option value="">{{ placeholder }}</option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>

        <p v-if="error" :id="errorId" class="mt-1 text-xs text-rose-600" role="alert">{{ error }}</p>
        <p v-else-if="hint" :id="hintId" class="mt-1 text-xs text-gray-400">{{ hint }}</p>
    </div>
</template>
