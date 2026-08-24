<script setup>
import { computed } from 'vue'

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

defineEmits(['update:modelValue'])

// Accepts either plain values ('Pro') or { value, label } pairs in `options`.
const normalizedOptions = computed(() =>
    props.options.map((option) =>
        typeof option === 'object' && option !== null
            ? option
            : { value: option, label: String(option) },
    ),
)
</script>

<template>
    <fieldset class="invue-form-field">
        <legend v-if="label || $slots.label" class="mb-1 block text-sm font-medium text-gray-700">
            <slot name="label">
                {{ label }}
                <span v-if="required" class="text-red-500">*</span>
            </slot>
        </legend>

        <div class="flex flex-wrap gap-2">
            <button
                v-for="option in normalizedOptions"
                :key="option.value"
                type="button"
                :disabled="disabled"
                :class="[
                    'rounded-md px-3 py-1.5 text-sm font-medium shadow-sm disabled:opacity-50',
                    modelValue === option.value
                        ? 'bg-green-600 text-white'
                        : error
                            ? 'border border-red-400 bg-white text-gray-700'
                            : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                ]"
                @click="$emit('update:modelValue', option.value)"
            >
                {{ option.label }}
            </button>
        </div>

        <p v-if="hint && !error" class="mt-1 text-sm text-gray-500">
            <slot name="hint">{{ hint }}</slot>
        </p>

        <p v-if="error" class="mt-1 text-sm text-red-600">
            <slot name="error">{{ error }}</slot>
        </p>
    </fieldset>
</template>
