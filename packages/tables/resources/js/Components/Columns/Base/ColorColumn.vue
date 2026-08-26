<script setup>
import { computed } from 'vue'
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
    copyable: {
        type: Boolean,
        default: false,
    },
})

const value = computed(() => getNestedValue(props.row, props.field))

async function copy() {
    if (!navigator.clipboard || !value.value) {
        return
    }

    await navigator.clipboard.writeText(String(value.value))
}
</script>

<template>
    <div v-if="value" class="flex items-center gap-2">
        <span class="h-5 w-5 rounded border border-gray-200" :style="{ backgroundColor: value }" />
        <span class="font-mono text-xs text-gray-500">{{ value }}</span>
        <button v-if="copyable" type="button" class="text-gray-400 hover:text-gray-600" title="Copy" @click="copy">
            ⧉
        </button>
    </div>
</template>
