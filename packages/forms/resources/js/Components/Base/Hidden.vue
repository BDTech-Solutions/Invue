<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
})

defineEmits(['update:modelValue'])
</script>

<template>
    <input
        type="hidden"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>
