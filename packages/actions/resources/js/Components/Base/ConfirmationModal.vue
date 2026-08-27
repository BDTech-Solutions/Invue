<script setup>
import { useMountedOnClient } from '../../composables/useMountedOnClient'

const isMounted = useMountedOnClient()

// Static color map — same Tailwind content-scanning rule as every other
// color prop across Invue (see invue/notifications' Toast.vue).
const CONFIRM_BUTTON_CLASSES = {
    gray: 'bg-gray-800 hover:bg-gray-700',
    red: 'bg-red-600 hover:bg-red-500',
    green: 'bg-green-600 hover:bg-green-500',
    blue: 'bg-blue-600 hover:bg-blue-500',
    yellow: 'bg-yellow-500 hover:bg-yellow-400',
    amber: 'bg-amber-500 hover:bg-amber-400',
    sky: 'bg-sky-600 hover:bg-sky-500',
    rose: 'bg-rose-600 hover:bg-rose-500',
    purple: 'bg-purple-600 hover:bg-purple-500',
    pink: 'bg-pink-600 hover:bg-pink-500',
}

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Are you sure?',
    },
    text: {
        type: String,
        default: null,
    },
    confirmLabel: {
        type: String,
        default: 'Confirm',
    },
    cancelLabel: {
        type: String,
        default: 'Cancel',
    },
    color: {
        type: String,
        default: 'gray',
    },
    processing: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['confirm', 'cancel'])
</script>

<template>
    <!-- Teleported to <body> so this never inherits styling from wherever
         it was triggered — e.g. a table cell with `text-align: right`
         (ActionsColumn's `align="end"`) would otherwise bleed into the
         modal's own text, since `position: fixed` only changes layout,
         not CSS inheritance. Same reasoning as ActionGroup's dropdown
         menu. -->
    <Teleport v-if="isMounted" to="body">
        <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 text-left" @click.self="$emit('cancel')">
            <div class="w-full max-w-sm rounded-lg bg-white p-5 shadow-xl">
                <h2 class="text-base font-semibold text-gray-900">{{ title }}</h2>
                <p v-if="text" class="mt-1.5 text-sm text-gray-600">{{ text }}</p>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        :disabled="processing"
                        @click="$emit('cancel')"
                    >
                        {{ cancelLabel }}
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                        :class="CONFIRM_BUTTON_CLASSES[color] ?? CONFIRM_BUTTON_CLASSES.gray"
                        :disabled="processing"
                        @click="$emit('confirm')"
                    >
                        {{ confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
