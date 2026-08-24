<script setup>
defineProps({
    modelValue: {
        type: String,
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
    prefix: {
        type: String,
        default: null,
    },
    suffix: {
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

        <div class="relative flex items-center">
            <span
                v-if="prefix || $slots.prefix"
                class="pointer-events-none absolute left-3 text-sm text-gray-400"
            >
                <slot name="prefix">{{ prefix }}</slot>
            </span>

            <input
                :value="modelValue"
                type="text"
                :disabled="disabled"
                :class="[
                    'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    { 'pl-8': prefix || $slots.prefix, 'pr-8': suffix || $slots.suffix, 'border-red-400': error },
                ]"
                @input="$emit('update:modelValue', $event.target.value)"
            />

            <span
                v-if="suffix || $slots.suffix"
                class="pointer-events-none absolute right-3 text-sm text-gray-400"
            >
                <slot name="suffix">{{ suffix }}</slot>
            </span>
        </div>

        <p v-if="hint && !error" class="mt-1 text-sm text-gray-500">
            <slot name="hint">{{ hint }}</slot>
        </p>

        <p v-if="error" class="mt-1 text-sm text-red-600">
            <slot name="error">{{ error }}</slot>
        </p>
    </div>
</template>
