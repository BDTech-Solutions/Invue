<script setup>
defineProps({
    // Intentionally untyped: native <input type="file"> is uncontrolled (the browser
    // won't let JS set its displayed value), so modelValue only ever flows one way,
    // out of the component — it holds whatever File the user picked, or null.
    modelValue: {
        type: null,
        default: null,
    },
    label: {
        type: String,
        default: null,
    },
    hint: {
        type: String,
        default: null,
    },
    accept: {
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
            type="file"
            :accept="accept"
            :disabled="disabled"
            :class="[
                'block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:px-3 file:py-1.5 file:text-sm file:font-semibold',
                error
                    ? 'file:bg-red-50 file:text-red-600 hover:file:bg-red-100'
                    : 'file:bg-green-50 file:text-green-600 hover:file:bg-green-100',
            ]"
            @change="$emit('update:modelValue', $event.target.files[0] ?? null)"
        />

        <p v-if="hint && !error" class="mt-1 text-sm text-gray-500">
            <slot name="hint">{{ hint }}</slot>
        </p>

        <p v-if="error" class="mt-1 text-sm text-red-600">
            <slot name="error">{{ error }}</slot>
        </p>
    </div>
</template>
