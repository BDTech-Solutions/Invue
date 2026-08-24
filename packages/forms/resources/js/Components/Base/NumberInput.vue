<script setup>
defineProps({
    modelValue: {
        type: [Number, String],
        default: '',
    },
    label: {
        type: String,
        default: null,
    },
    hint: {
        type: String,
        default: null,
    },
    min: {
        type: [Number, String],
        default: null,
    },
    max: {
        type: [Number, String],
        default: null,
    },
    step: {
        type: [Number, String],
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

// A native <input type="number">'s event.target.value is always a string.
// Coerce to a real Number on the way out (keeping '' for "empty" instead of
// letting it collapse to 0 or NaN) so consumers get the type they expect.
function onInput(event) {
    const raw = event.target.value
    emit('update:modelValue', raw === '' ? '' : Number(raw))
}
</script>

<template>
    <div class="invue-form-field">
        <label v-if="label || $slots.label" class="mb-1 block text-sm font-medium text-gray-700">
            <slot name="label">
                {{ label }}
                <span v-if="required" class="text-red-500">*</span>
            </slot>
        </label>

        <input
            :value="modelValue"
            type="number"
            :min="min"
            :max="max"
            :step="step"
            :disabled="disabled"
            :class="[
                'block w-full rounded-md shadow-sm sm:text-sm',
                error ? 'border-red-400 focus:border-red-400' : 'border-gray-300 focus:border-indigo-500',
                'focus:ring-indigo-500',
            ]"
            @input="onInput"
        />

        <p v-if="hint && !error" class="mt-1 text-sm text-gray-500">
            <slot name="hint">{{ hint }}</slot>
        </p>

        <p v-if="error" class="mt-1 text-sm text-red-600">
            <slot name="error">{{ error }}</slot>
        </p>
    </div>
</template>
