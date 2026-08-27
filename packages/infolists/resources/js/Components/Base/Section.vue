<script setup>
defineProps({
    title: {
        type: String,
        default: null,
    },
    description: {
        type: String,
        default: null,
    },
})
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-5">
        <div v-if="title || $slots.actions" class="mb-4 flex items-start justify-between gap-3">
            <div v-if="title">
                <h3 class="text-sm font-semibold text-gray-900">{{ title }}</h3>
                <p v-if="description" class="mt-0.5 text-sm text-gray-500">{{ description }}</p>
            </div>
            <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <slot />
    </div>
</template>
