<script setup>
import { computed } from 'vue'
// The resolved wrappers, not Base/ActionGroup.vue or Base/ActionButton.vue
// directly — a registry swap of `actions.ActionGroup`/`actions.ActionButton`
// should still apply here, same rule every composing component in Invue
// follows (see PanelLayout -> Sidebar/Topbar).
import { ActionButton, ActionGroup } from 'invue/actions'

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
    // 'menu' (default): a single "⋯" trigger, actions behind a dropdown —
    // right for a longer action list. 'inline': every visible action
    // renders as its own small ghost icon button directly in the cell —
    // right for the common 2-3 action case (edit/delete), always visible,
    // no click-to-discover step. make:invue-resource's default Index.vue
    // uses 'inline' for exactly that reason.
    trigger: {
        type: String,
        default: 'menu',
    },
})

const resolvedActions = computed(() => (typeof props.actions === 'function' ? props.actions(props.row) : props.actions))
const visibleActions = computed(() => resolvedActions.value.filter((action) => action.visible !== false))
</script>

<template>
    <div v-if="trigger === 'inline'" class="flex items-center gap-1">
        <ActionButton
            v-for="(action, index) in visibleActions"
            :key="action.label ?? index"
            variant="ghost"
            v-bind="action"
        />
    </div>
    <ActionGroup v-else :actions="resolvedActions" />
</template>
