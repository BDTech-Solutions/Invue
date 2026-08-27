<script setup>
import { ref } from 'vue'
import { Icon } from 'invue/core'
import ConfirmationModal from '../ConfirmationModal.vue'
import { useInvueAction } from '../../composables/useInvueAction'

// Static color map for menu-item text/icon tint — same shape as
// Base/ActionButton.vue's BUTTON_CLASSES, but text-only (a dropdown row,
// not a solid pill).
const ITEM_TEXT_CLASSES = {
    gray: 'text-gray-700',
    red: 'text-red-600',
    green: 'text-green-700',
    blue: 'text-blue-700',
    yellow: 'text-yellow-700',
    amber: 'text-amber-700',
    sky: 'text-sky-700',
    rose: 'text-rose-700',
    purple: 'text-purple-700',
    pink: 'text-pink-700',
}

const props = defineProps({
    // Each entry: { label, icon, color, url, method, data, disabled,
    // visible, requiresConfirmation, confirmationTitle, confirmationText,
    // confirmationButtonLabel }. `visible` (Boolean) hides an action
    // entirely — the "PHP annotates permission as data, Vue reacts to it"
    // pattern (see Invue\Tables\TableQuery::authorize()).
    actions: {
        type: Array,
        default: () => [],
    },
    triggerLabel: {
        type: String,
        default: 'Actions',
    },
})

const { confirming, processing, run, confirm, cancel } = useInvueAction()

const detailsEl = ref(null)

const visibleActions = () => props.actions.filter((action) => action.visible !== false)

function selectAction(action) {
    if (detailsEl.value) {
        detailsEl.value.open = false
    }

    run(action)
}
</script>

<template>
    <details ref="detailsEl" class="relative inline-block text-left">
        <summary
            class="flex h-7 w-7 cursor-pointer list-none items-center justify-center rounded-md text-gray-400 select-none hover:bg-gray-100 hover:text-gray-600"
            :aria-label="triggerLabel"
        >
            ⋯
        </summary>

        <div class="absolute right-0 z-10 mt-1 w-44 overflow-hidden rounded-md border border-gray-200 bg-white py-1 shadow-lg">
            <button
                v-for="(action, index) in visibleActions()"
                :key="action.label ?? index"
                type="button"
                class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                :class="ITEM_TEXT_CLASSES[action.color] ?? ITEM_TEXT_CLASSES.gray"
                :disabled="action.disabled"
                @click="selectAction(action)"
            >
                <Icon v-if="action.icon" :name="action.icon" class="h-4 w-4 shrink-0" />
                <span>{{ action.label }}</span>
            </button>
        </div>
    </details>

    <ConfirmationModal
        :open="confirming !== null"
        :title="confirming?.confirmationTitle ?? 'Are you sure?'"
        :text="confirming?.confirmationText"
        :confirm-label="confirming?.confirmationButtonLabel ?? confirming?.label ?? 'Confirm'"
        :color="confirming?.color ?? 'gray'"
        :processing="processing"
        @confirm="confirm"
        @cancel="cancel"
    />
</template>
