<script setup>
import { computed } from 'vue'
import { getNestedValue } from '../../../support/getNestedValue'

const TEXT_COLOR_CLASSES = {
    gray: 'text-gray-400',
    red: 'text-red-500',
    green: 'text-green-600',
    blue: 'text-blue-500',
    yellow: 'text-yellow-500',
    amber: 'text-amber-500',
    sky: 'text-sky-500',
    rose: 'text-rose-500',
    purple: 'text-purple-500',
    pink: 'text-pink-500',
}

const props = defineProps({
    row: {
        type: Object,
        required: true,
    },
    field: {
        type: String,
        required: true,
    },
    boolean: {
        type: Boolean,
        default: false,
    },
    trueIcon: {
        type: String,
        default: '✓',
    },
    falseIcon: {
        type: String,
        default: '✕',
    },
    trueColor: {
        type: String,
        default: 'green',
    },
    falseColor: {
        type: String,
        default: 'red',
    },
    icon: {
        type: [String, Function],
        default: null,
    },
    color: {
        type: [String, Function],
        default: 'gray',
    },
    size: {
        type: String,
        default: 'md',
    },
})

const rawValue = computed(() => getNestedValue(props.row, props.field))

const resolvedIcon = computed(() => {
    if (props.boolean) {
        return rawValue.value ? props.trueIcon : props.falseIcon
    }

    return typeof props.icon === 'function' ? props.icon(rawValue.value, props.row) : props.icon
})

const resolvedColor = computed(() => {
    if (props.boolean) {
        return rawValue.value ? props.trueColor : props.falseColor
    }

    return typeof props.color === 'function' ? props.color(rawValue.value, props.row) : props.color
})

const sizeClass = computed(() => ({ sm: 'text-sm', md: 'text-base', lg: 'text-lg' })[props.size] ?? 'text-base')
</script>

<template>
    <span
        v-if="resolvedIcon"
        :class="[TEXT_COLOR_CLASSES[resolvedColor] ?? TEXT_COLOR_CLASSES.gray, sizeClass]"
        aria-hidden="true"
    >
        {{ resolvedIcon }}
    </span>
</template>
