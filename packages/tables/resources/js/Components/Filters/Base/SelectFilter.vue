<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: {
        type: [String, Number, Array, null],
        default: null,
    },
    label: {
        type: String,
        default: null,
    },
    options: {
        type: Array,
        default: () => [],
    },
    multiple: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: 'All',
    },
})

const emit = defineEmits(['update:modelValue'])

const normalizedOptions = computed(() =>
    props.options.map((option) =>
        typeof option === 'object' && option !== null ? option : { value: option, label: String(option) },
    ),
)

function onChange(event) {
    if (props.multiple) {
        const selected = Array.from(event.target.selectedOptions).map((option) => option.value)
        emit('update:modelValue', selected)
        return
    }

    emit('update:modelValue', event.target.value || null)
}
</script>

<template>
    <label class="flex items-center gap-2 text-sm text-gray-600">
        <span v-if="label">{{ label }}</span>
        <select
            :value="modelValue"
            :multiple="multiple"
            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
            @change="onChange"
        >
            <option v-if="!multiple" value="">{{ placeholder }}</option>
            <option v-for="option in normalizedOptions" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
    </label>
</template>
