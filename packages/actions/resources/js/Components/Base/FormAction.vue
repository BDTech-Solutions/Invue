<script setup>
import { Icon } from 'invue/core'
// The resolved wrapper, not Base/FormModal.vue directly — a registry swap
// of just `actions.FormModal` still needs to apply here, same rule
// Base/ActionButton.vue follows for ConfirmationModal.
import FormModal from '../FormModal.vue'

// Same two color maps as Base/ActionButton.vue, duplicated rather than
// imported — Tailwind only scans literal class strings inside
// vendor/invue/**/*.vue (see the parent skill's Tailwind
// content-scanning note), so a shared .js constant would never resolve
// to a real class here.
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

const GHOST_CLASSES = {
    gray: 'text-gray-500 hover:bg-gray-100 hover:text-gray-700',
    red: 'text-red-500 hover:bg-red-50 hover:text-red-700',
    green: 'text-green-600 hover:bg-green-50 hover:text-green-700',
    blue: 'text-blue-500 hover:bg-blue-50 hover:text-blue-700',
    yellow: 'text-yellow-600 hover:bg-yellow-50 hover:text-yellow-700',
    amber: 'text-amber-600 hover:bg-amber-50 hover:text-amber-700',
    sky: 'text-sky-500 hover:bg-sky-50 hover:text-sky-700',
    rose: 'text-rose-500 hover:bg-rose-50 hover:text-rose-700',
    purple: 'text-purple-500 hover:bg-purple-50 hover:text-purple-700',
    pink: 'text-pink-500 hover:bg-pink-50 hover:text-pink-700',
}

const props = defineProps({
    // Controls the modal — use with v-model:open, same as any other
    // v-model in Invue. FormAction never closes itself on submit: it has
    // no idea whether your form.post() succeeded, so it just emits
    // `submit` and leaves closing to you (typically inside your own
    // onSuccess callback) — same "this component shapes nothing about
    // what submitting actually does" boundary TableQuery/SelectOptions
    // keep on the backend side.
    open: {
        type: Boolean,
        default: false,
    },
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
    variant: {
        type: String,
        default: 'solid',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    // Modal chrome — defaults to `label` when omitted, same as
    // ActionButton's confirmationButtonLabel falling back to `label`.
    title: {
        type: String,
        default: null,
    },
    submitLabel: {
        type: String,
        default: 'Save',
    },
    cancelLabel: {
        type: String,
        default: 'Cancel',
    },
    size: {
        type: String,
        default: 'md',
    },
    // Bind your own form.processing here — disables Save/Cancel and
    // blocks a second submit while a request is already in flight.
    processing: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:open', 'submit'])

function openModal() {
    if (props.disabled) {
        return
    }

    emit('update:open', true)
}

function close() {
    emit('update:open', false)
}

function handleSubmit() {
    emit('submit')
}
</script>

<template>
    <button
        v-if="variant === 'ghost'"
        type="button"
        class="inline-flex h-7 w-7 items-center justify-center rounded-md disabled:cursor-not-allowed disabled:opacity-60"
        :class="GHOST_CLASSES[color] ?? GHOST_CLASSES.gray"
        :disabled="disabled"
        :aria-label="label"
        :title="label"
        @click="openModal"
    >
        <Icon v-if="icon" :name="icon" class="h-4 w-4" />
        <span v-if="!icon && label">{{ label }}</span>
    </button>
    <button
        v-else
        type="button"
        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-60"
        :class="BUTTON_CLASSES[color] ?? BUTTON_CLASSES.gray"
        :disabled="disabled"
        @click="openModal"
    >
        <Icon v-if="icon" :name="icon" class="h-4 w-4" />
        <span v-if="label">{{ label }}</span>
    </button>

    <FormModal
        :open="open"
        :title="title ?? label"
        :submit-label="submitLabel"
        :cancel-label="cancelLabel"
        :size="size"
        :processing="processing"
        @submit="handleSubmit"
        @cancel="close"
    >
        <template v-for="(_, slotName) in $slots" #[slotName]="scope" :key="slotName">
            <slot :name="slotName" v-bind="scope" />
        </template>
    </FormModal>
</template>
