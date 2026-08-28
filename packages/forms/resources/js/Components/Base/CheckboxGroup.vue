<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    options: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: null,
    },
    hint: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['update:modelValue'])

// Accepts either plain values ('Design') or { value, label } pairs in `options`.
const normalizedOptions = computed(() =>
    props.options.map((option) =>
        typeof option === 'object' && option !== null
            ? option
            : { value: option, label: String(option) },
    ),
)

function toggle(value, checked) {
    const next = checked
        ? [...props.modelValue, value]
        : props.modelValue.filter((selected) => selected !== value)

    emit('update:modelValue', next)
}
</script>

<template>
    <fieldset class="invue-form-field">
        <legend v-if="label || $slots.label" class="mb-1 block text-sm font-medium text-gray-700">
            <slot name="label">
                {{ label }}
                <span v-if="required" class="text-red-500">*</span>
            </slot>
        </legend>

        <div class="space-y-2">
            <label
                v-for="option in normalizedOptions"
                :key="option.value"
                class="flex items-center gap-2"
            >
                <input
                    type="checkbox"
                    :checked="modelValue.includes(option.value)"
                    :disabled="disabled"
                    :class="[
                        'h-4 w-4 rounded border shadow-sm focus:ring-green-500',
                        error ? 'border-red-400 text-red-500' : 'border-gray-300 text-green-600',
                    ]"
                    @change="toggle(option.value, $event.target.checked)"
                />
                <span class="text-sm text-gray-700">{{ option.label }}</span>
            </label>
        </div>

        <p v-if="hint && !error" class="mt-1 text-sm text-gray-500">
            <slot name="hint">{{ hint }}</slot>
        </p>

        <p v-if="error" class="mt-1 text-sm text-red-600">
            <slot name="error">{{ error }}</slot>
        </p>
    </fieldset>
</template>
