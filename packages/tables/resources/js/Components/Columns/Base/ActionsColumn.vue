<script setup>
import { computed } from 'vue'
// The resolved wrapper, not Base/ActionGroup.vue directly — a registry
// swap of `actions.ActionGroup` should still apply here, same rule every
// composing component in Invue follows (see PanelLayout -> Sidebar/Topbar).
import { ActionGroup } from 'invue/actions'

const props = defineProps({
    row: {
        type: Object,
        required: true,
    },
    // A static array, or a function of `row` for per-row actions (which
    // action is offered, its url, whether it's `visible` — see
    // Invue\Tables\TableQuery::authorize() for where `row._can` comes
    // from). Same "function prop resolved against the row" convention
    // TextColumn already uses for `color`/`url`/`description`.
    actions: {
        type: [Array, Function],
        default: () => [],
    },
})

const resolvedActions = computed(() => (typeof props.actions === 'function' ? props.actions(props.row) : props.actions))
</script>

<template>
    <ActionGroup :actions="resolvedActions" />
</template>
