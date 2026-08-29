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
    disabled: {
        type: Boolean,
        default: false,
    },
    onUpdate: {
        type: Function,
        default: null,
    },
})

// Local optimistic state — this column does NOT auto-persist. `onUpdate`
// is the escape hatch: wire your own router.patch()/put() to it if you
// want the change saved. Without it this is a display-only checkbox.
const checked = ref(Boolean(getNestedValue(props.row, props.field)))

watch(
    () => getNestedValue(props.row, props.field),
    (value) => {
        checked.value = Boolean(value)
    },
)

function onChange(event) {
    checked.value = event.target.checked
    props.onUpdate?.(checked.value, props.row)
}
</script>

<template>
    <input
        type="checkbox"
        :checked="checked"
        :disabled="disabled"
        class="h-4 w-4 rounded border border-gray-300 text-green-600 shadow-sm focus:ring-green-500"
        @change="onChange"
    />
</template>
