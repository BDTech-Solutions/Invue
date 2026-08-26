<script setup>
import { ref, watch } from 'vue'
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

// Display-only unless `onUpdate` is wired — see CheckboxColumn's note, same
// deal here (this does not auto-check policies or persist by itself).
const enabled = ref(Boolean(getNestedValue(props.row, props.field)))

watch(
    () => getNestedValue(props.row, props.field),
    (value) => {
        enabled.value = Boolean(value)
    },
)

function toggle() {
    if (props.disabled) {
        return
    }

    enabled.value = !enabled.value
    props.onUpdate?.(enabled.value, props.row)
}
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="enabled"
        :disabled="disabled"
        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-50"
        :class="enabled ? 'bg-green-600' : 'bg-gray-200'"
        @click="toggle"
    >
        <span
            class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
            :class="enabled ? 'translate-x-4.5' : 'translate-x-1'"
        />
    </button>
</template>
