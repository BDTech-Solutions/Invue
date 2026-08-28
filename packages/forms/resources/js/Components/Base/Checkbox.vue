<script setup>
defineProps({
    modelValue: {
        type: Boolean,
        default: false,
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
</script>

<template>
    <div class="invue-form-field">
        <label class="flex items-center gap-2">
            <input
                type="checkbox"
                :checked="modelValue"
                :disabled="disabled"
                :class="[
                    'h-4 w-4 rounded border shadow-sm focus:ring-green-500',
                    error ? 'border-red-400 text-red-500' : 'border-gray-300 text-green-600',
                ]"
                @change="$emit('update:modelValue', $event.target.checked)"
            />
            <span v-if="label || $slots.label" class="text-sm text-gray-700">
                <slot name="label">
                    {{ label }}
                    <span v-if="required" class="text-red-500">*</span>
                </slot>
            </span>
        </label>

        <p v-if="hint && !error" class="mt-1 text-sm text-gray-500">
            <slot name="hint">{{ hint }}</slot>
        </p>

        <p v-if="error" class="mt-1 text-sm text-red-600">
            <slot name="error">{{ error }}</slot>
        </p>
    </div>
</template>
