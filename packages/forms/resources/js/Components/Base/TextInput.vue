<script setup>
const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    // Any native <input> type that's just a variant of the same element:
    // 'text' (default), 'number', 'email', 'password', 'tel', 'url', ...
    type: {
        type: String,
        default: 'text',
    },
    label: {
        type: String,
        default: null,
    },
    hint: {
        type: String,
        default: null,
    },
    placeholder: {
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
    // Only meaningful for type="number" — ignored by the browser for other types.
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

// type="number"'s event.target.value is always a string even though the browser only
// lets the user type valid numbers — coerce to a real Number on the way out (keeping ''
// for "empty" instead of letting it collapse to 0 or NaN). Every other type stays a string.
function onInput(event) {
    const raw = event.target.value

    if (props.type === 'number') {
        emit('update:modelValue', raw === '' ? '' : Number(raw))
        return
    }

    emit('update:modelValue', raw)
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

        <div class="relative flex items-center">
            <span
                v-if="prefix || $slots.prefix"
                class="pointer-events-none absolute left-3 text-sm text-gray-400"
            >
                <slot name="prefix">{{ prefix }}</slot>
            </span>

            <input
                :value="modelValue"
                :type="type"
                :placeholder="placeholder"
                :min="min"
                :max="max"
                :step="step"
                :disabled="disabled"
                :class="[
                    'block w-full rounded-md border px-3 py-1.5 shadow-sm sm:text-sm',
                    error ? 'border-red-400 focus:border-red-400' : 'border-gray-300 focus:border-green-500',
                    'focus:ring-green-500',
                    { 'pl-8': prefix || $slots.prefix, 'pr-8': suffix || $slots.suffix },
                ]"
                @input="onInput"
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
