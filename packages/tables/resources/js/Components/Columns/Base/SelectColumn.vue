<script setup>
import { computed, ref, watch } from 'vue'
import { getNestedValue } from '../../../support/getNestedValue'

const props = defineProps({
    row: {
        type: Object,
        required: true,
    },
    field: {
        type: String,
        required: true,
    },
    options: {
        type: Array,
        default: () => [],
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    onUpdate: {
        type: Function,
        default: null,
    },
})

// Same non-persisting contract as CheckboxColumn/ToggleColumn — wire
// `onUpdate` to your own router call if the change should be saved.
const value = ref(getNestedValue(props.row, props.field))

watch(
    () => getNestedValue(props.row, props.field),
    (next) => {
        value.value = next
    },
)

const normalizedOptions = computed(() =>
    props.options.map((option) =>
        typeof option === 'object' && option !== null ? option : { value: option, label: String(option) },
    ),
)

function onChange(event) {
    value.value = event.target.value
    props.onUpdate?.(value.value, props.row)
}
</script>

<template>
    <select
        :value="value"
        :disabled="disabled"
        class="block rounded-md border border-gray-300 px-2 py-1 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
        @change="onChange"
    >
        <option v-for="option in normalizedOptions" :key="option.value" :value="option.value">
            {{ option.label }}
        </option>
    </select>
</template>
