<script setup>
const props = defineProps({
    modelValue: {
        type: [String, null],
        default: null,
    },
    label: {
        type: String,
        default: null,
    },
    trueLabel: {
        type: String,
        default: 'Yes',
    },
    falseLabel: {
        type: String,
        default: 'No',
    },
    placeholder: {
        type: String,
        default: 'All',
    },
})

const emit = defineEmits(['update:modelValue'])

function onChange(event) {
    emit('update:modelValue', event.target.value || null)
}
</script>

<template>
    <label class="flex items-center gap-2 text-sm text-gray-600">
        <span v-if="label">{{ label }}</span>
        <select
            :value="modelValue ?? ''"
            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
            @change="onChange"
        >
            <option value="">{{ placeholder }}</option>
            <option value="true">{{ trueLabel }}</option>
            <option value="false">{{ falseLabel }}</option>
        </select>
    </label>
</template>
