<script setup>
import { computed } from 'vue'
// Resolved wrapper, not Base/ActionButton.vue — same "compose the
// swappable wrapper, not its Base" rule as ActionsColumn.vue.
import { ActionButton } from 'invue/actions'

const props = defineProps({
    count: {
        type: Number,
        required: true,
    },
    // A static array, or a function of the selected ids — lets the caller
    // build each action's `data` (e.g. `{ ids: selectedIds }`) inline
    // instead of the bar inventing a request-body convention for them.
    actions: {
        type: [Array, Function],
        default: () => [],
    },
    selectedIds: {
        type: Array,
        required: true,
    },
})

defineEmits(['success'])

const resolvedActions = computed(() => (typeof props.actions === 'function' ? props.actions(props.selectedIds) : props.actions))
</script>

<template>
    <div v-if="count > 0" class="mb-3 flex flex-wrap items-center gap-3 rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
        <span class="text-sm font-medium text-gray-700">{{ count }} selected</span>
        <div class="flex flex-wrap gap-2">
            <ActionButton
                v-for="(action, index) in resolvedActions"
                :key="action.label ?? index"
                v-bind="action"
                @success="$emit('success')"
            />
        </div>
    </div>
</template>
