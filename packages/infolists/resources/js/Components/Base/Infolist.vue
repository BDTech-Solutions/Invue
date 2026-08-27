<script setup>
// Static map — same Tailwind content-scanning rule as every other
// numeric/color prop across Invue: `grid-cols-${columns}` built at
// runtime would never survive a consuming app's production build.
const GRID_COLUMNS_CLASSES = {
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
}

defineProps({
    columns: {
        type: Number,
        default: 1,
    },
})
</script>

<template>
    <dl class="grid gap-x-6 gap-y-4" :class="GRID_COLUMNS_CLASSES[columns] ?? GRID_COLUMNS_CLASSES[1]">
        <slot />
    </dl>
</template>
