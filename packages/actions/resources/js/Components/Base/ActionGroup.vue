<script setup>
import { ref } from 'vue'
import { Icon } from 'invue/core'
import ConfirmationModal from '../ConfirmationModal.vue'
import { useInvueAction } from '../../composables/useInvueAction'
import { useMountedOnClient } from '../../composables/useMountedOnClient'

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
const isMounted = useMountedOnClient()

const open = ref(false)
const triggerEl = ref(null)
const menuEl = ref(null)
const menuStyle = ref({})

const visibleActions = () => props.actions.filter((action) => action.visible !== false)

// This menu is <Teleport>-ed to <body> on purpose — an ActionGroup mounts
// most often inside an ActionsColumn cell, and a table wraps its rows in
// an `overflow-x-auto` container. Per the CSS spec, setting only
// `overflow-x` implicitly turns `overflow-y` into `auto` too, so an
// absolutely-positioned menu confined to that container gets clipped
// vertically the moment its row is anywhere but the very top — teleporting
// it to <body> and positioning it in viewport coordinates sidesteps that
// entirely, rather than fighting the table's own scroll container.
function updatePosition() {
    if (!triggerEl.value) {
        return
    }

    const rect = triggerEl.value.getBoundingClientRect()

    menuStyle.value = {
        position: 'fixed',
        top: `${rect.bottom + 4}px`,
        right: `${window.innerWidth - rect.right}px`,
    }
}

function handleOutsideClick(event) {
    if (triggerEl.value?.contains(event.target) || menuEl.value?.contains(event.target)) {
        return
    }

    close()
}

function handleKeydown(event) {
    if (event.key === 'Escape') {
        close()
    }
}

function openMenu() {
    updatePosition()
    open.value = true

    // Capture phase: a scroll inside the table's own container doesn't
    // bubble to window, but is still observable during capture.
    window.addEventListener('scroll', updatePosition, true)
    window.addEventListener('resize', updatePosition)
    document.addEventListener('mousedown', handleOutsideClick)
    document.addEventListener('keydown', handleKeydown)
}

function close() {
    open.value = false
    window.removeEventListener('scroll', updatePosition, true)
    window.removeEventListener('resize', updatePosition)
    document.removeEventListener('mousedown', handleOutsideClick)
    document.removeEventListener('keydown', handleKeydown)
}

function toggle() {
    if (open.value) {
        close()
    } else {
        openMenu()
    }
}

function selectAction(action) {
    close()
    run(action)
}
</script>

<template>
    <button
        ref="triggerEl"
        type="button"
        class="flex h-7 w-7 items-center justify-center rounded-md text-gray-400 select-none hover:bg-gray-100 hover:text-gray-600"
        :aria-label="triggerLabel"
        @click="toggle"
    >
        ⋯
    </button>

    <Teleport v-if="isMounted" to="body">
        <div
            v-if="open"
            ref="menuEl"
            class="z-50 w-44 overflow-hidden rounded-md border border-gray-200 bg-white py-1 shadow-lg"
            :style="menuStyle"
        >
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
    </Teleport>

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
