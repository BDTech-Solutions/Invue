<script setup>
// Static map — see Infolist.vue's own note on this.
const SPAN_CLASSES = {
    1: '',
    2: 'sm:col-span-2',
    3: 'sm:col-span-2 lg:col-span-3',
    4: 'sm:col-span-2 lg:col-span-4',
}

defineProps({
    label: {
        type: String,
        required: true,
    },
    // How many of the parent <Infolist>'s grid columns this entry
    // occupies — e.g. a long body/description field in a 2+ column
    // layout typically wants `span="2"`.
    span: {
        type: Number,
        default: 1,
    },
})
</script>

<template>
    <div :class="SPAN_CLASSES[span] ?? ''">
        <dt class="text-xs font-semibold tracking-wide text-gray-400 uppercase">{{ label }}</dt>
        <!-- The value is always composed content — typically a
             resolved invue/tables column (<TextColumn>, <IconColumn>,
             ...) bound to the same record, reusing its formatting/
             badge/color logic instead of Infolist reinventing it. -->
        <dd class="mt-1 text-sm text-gray-900">
            <slot />
        </dd>
    </div>
</template>
