<script setup>
import { computed } from 'vue'
import { Icon } from 'invue/core'
// The resolved wrapper, not Base/ConfirmationModal.vue directly — a
// registry swap of just `actions.ConfirmationModal` still needs to apply
// here, same rule PanelLayout follows for Sidebar/Topbar.
import ConfirmationModal from '../ConfirmationModal.vue'
import { useInvueAction } from '../../composables/useInvueAction'

// Static color map — see the parent skill's Tailwind content-scanning
// note; this can't live in a shared .js module.
const BUTTON_CLASSES = {
    gray: 'bg-gray-800 text-white hover:bg-gray-700',
    red: 'bg-red-600 text-white hover:bg-red-500',
    green: 'bg-green-600 text-white hover:bg-green-500',
    blue: 'bg-blue-600 text-white hover:bg-blue-500',
    yellow: 'bg-yellow-500 text-white hover:bg-yellow-400',
    amber: 'bg-amber-500 text-white hover:bg-amber-400',
    sky: 'bg-sky-600 text-white hover:bg-sky-500',
    rose: 'bg-rose-600 text-white hover:bg-rose-500',
    purple: 'bg-purple-600 text-white hover:bg-purple-500',
    pink: 'bg-pink-600 text-white hover:bg-pink-500',
}

const props = defineProps({
    label: {
        type: String,
        default: null,
    },
    icon: {
        type: String,
        default: null,
    },
    color: {
        type: String,
        default: 'gray',
    },
    // Given, this behaves like a styled <Link> (an Inertia visit). Omitted,
    // clicking just emits `click` — for a caller that wants to run its own
    // handler (e.g. a client-only Notify() call) instead of navigating.
    url: {
        type: String,
        default: null,
    },
    method: {
        type: String,
        default: 'get',
    },
    data: {
        type: Object,
        default: () => ({}),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    requiresConfirmation: {
        type: Boolean,
        default: false,
    },
    confirmationTitle: {
        type: String,
        default: 'Are you sure?',
    },
    confirmationText: {
        type: String,
        default: null,
    },
    confirmationButtonLabel: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['click', 'success'])

const { confirming, processing, run, confirm, cancel } = useInvueAction()

const action = computed(() => ({
    url: props.url,
    method: props.method,
    data: props.data,
    disabled: props.disabled,
    requiresConfirmation: props.requiresConfirmation,
    onClick: () => emit('click'),
    onSuccess: () => emit('success'),
}))

function handleClick() {
    run(action.value)
}
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-60"
        :class="BUTTON_CLASSES[color] ?? BUTTON_CLASSES.gray"
        :disabled="disabled"
        @click="handleClick"
    >
        <Icon v-if="icon" :name="icon" class="h-4 w-4" />
        <span v-if="label">{{ label }}</span>
    </button>

    <ConfirmationModal
        :open="confirming !== null"
        :title="confirmationTitle"
        :text="confirmationText"
        :confirm-label="confirmationButtonLabel ?? label ?? 'Confirm'"
        :color="color"
        :processing="processing"
        @confirm="confirm"
        @cancel="cancel"
    />
</template>
