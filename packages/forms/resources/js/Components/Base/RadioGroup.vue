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

// Accepts either plain values ('Free') or { value, label } pairs in `options`.
const normalizedOptions = computed(() =>
    props.options.map((option) =>
        typeof option === 'object' && option !== null
            ? option
            : { value: option, label: String(option) },
    ),
)

// Radio inputs sharing a native `name` would collide across multiple RadioGroup
// instances on the same page, so each instance gets its own scoped name.
const groupName = `invue-radio-${Math.random().toString(36).slice(2)}`
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
                    type="radio"
                    :name="groupName"
                    :value="option.value"
                    :checked="modelValue === option.value"
                    :disabled="disabled"
                    :class="[
                        'shadow-sm focus:ring-green-500',
                        error ? 'border-red-400 text-red-500' : 'border-gray-300 text-green-600',
                    ]"
                    @change="$emit('update:modelValue', option.value)"
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
