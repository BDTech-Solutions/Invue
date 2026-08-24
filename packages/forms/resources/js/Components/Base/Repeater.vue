<script setup>
const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    // Factory for a fresh row when "Adicionar" is clicked.
    newItem: {
        type: Function,
        default: () => ({}),
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

function add() {
    emit('update:modelValue', [...props.modelValue, props.newItem()])
}

function remove(index) {
    emit('update:modelValue', props.modelValue.filter((_, i) => i !== index))
}

// Immutable replace, mirroring how every other invue/forms field emits — the
// default slot gets this instead of a raw index so consumers never mutate
// `modelValue` (or a row inside it) directly.
function update(index, value) {
    emit('update:modelValue', props.modelValue.map((item, i) => (i === index ? value : item)))
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

        <div class="space-y-3">
            <div
                v-for="(item, index) in modelValue"
                :key="index"
                class="flex items-start gap-2 rounded-md border border-gray-200 p-3"
            >
                <div class="flex-1 space-y-2">
                    <slot :item="item" :index="index" :update="(value) => update(index, value)" />
                </div>
                <button
                    type="button"
                    :disabled="disabled"
                    class="text-sm text-red-600 hover:text-red-700 disabled:opacity-50"
                    @click="remove(index)"
                >
                    Remover
                </button>
            </div>
        </div>

        <button
            type="button"
            :disabled="disabled"
            class="mt-2 text-sm font-medium text-green-600 hover:text-green-700 disabled:opacity-50"
            @click="add"
        >
            + Adicionar
        </button>

        <p v-if="hint && !error" class="mt-1 text-sm text-gray-500">
            <slot name="hint">{{ hint }}</slot>
        </p>

        <p v-if="error" class="mt-1 text-sm text-red-600">
            <slot name="error">{{ error }}</slot>
        </p>
    </div>
</template>
