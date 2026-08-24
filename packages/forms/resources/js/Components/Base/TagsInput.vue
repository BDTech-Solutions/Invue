<script setup>
import { ref } from 'vue'

const props = defineProps({
    modelValue: {
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
    placeholder: {
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

const draft = ref('')

function addTag() {
    const value = draft.value.trim()

    if (value && !props.modelValue.includes(value)) {
        emit('update:modelValue', [...props.modelValue, value])
    }

    draft.value = ''
}

function removeTag(index) {
    emit('update:modelValue', props.modelValue.filter((_, i) => i !== index))
}

function onKeydown(event) {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault()
        addTag()
        return
    }

    // Backspace on an empty draft pops the last tag — standard tag-input UX.
    if (event.key === 'Backspace' && draft.value === '' && props.modelValue.length > 0) {
        removeTag(props.modelValue.length - 1)
    }
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

        <div
            :class="[
                'flex flex-wrap items-center gap-1.5 rounded-md p-1.5 shadow-sm',
                error ? 'border border-red-400' : 'border border-gray-300',
            ]"
        >
            <span
                v-for="(tag, index) in modelValue"
                :key="tag"
                class="inline-flex items-center gap-1 rounded bg-green-50 px-2 py-0.5 text-sm text-green-700"
            >
                {{ tag }}
                <button
                    type="button"
                    :disabled="disabled"
                    class="text-green-400 hover:text-green-600 disabled:opacity-50"
                    @click="removeTag(index)"
                >
                    &times;
                </button>
            </span>

            <input
                v-model="draft"
                type="text"
                :placeholder="placeholder"
                :disabled="disabled"
                class="min-w-[8ch] flex-1 border-0 p-0.5 text-sm focus:ring-0"
                @keydown="onKeydown"
            />
        </div>

        <p v-if="hint && !error" class="mt-1 text-sm text-gray-500">
            <slot name="hint">{{ hint }}</slot>
        </p>

        <p v-if="error" class="mt-1 text-sm text-red-600">
            <slot name="error">{{ error }}</slot>
        </p>
    </div>
</template>
