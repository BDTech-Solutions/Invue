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
    width: {
        type: [String, Number],
        default: 40,
    },
    height: {
        type: [String, Number],
        default: 40,
    },
    circular: {
        type: Boolean,
        default: false,
    },
    square: {
        type: Boolean,
        default: false,
    },
    defaultUrl: {
        type: String,
        default: null,
    },
    alt: {
        type: String,
        default: '',
    },
})

const src = computed(() => getNestedValue(props.row, props.field) || props.defaultUrl)

const sizePx = computed(() => ({
    width: typeof props.width === 'number' ? `${props.width}px` : props.width,
    height: typeof props.height === 'number' ? `${props.height}px` : props.height,
}))

const shapeClass = computed(() => (props.square ? 'rounded-none' : props.circular ? 'rounded-full' : 'rounded-md'))
</script>

<template>
    <img
        v-if="src"
        :src="src"
        :alt="alt"
        :style="sizePx"
        class="object-cover"
        :class="shapeClass"
    />
    <div
        v-else
        :style="sizePx"
        class="flex items-center justify-center bg-gray-100 text-gray-300"
        :class="shapeClass"
    >
        —
    </div>
</template>
