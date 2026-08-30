<script setup>
import { onBeforeUnmount, watch } from 'vue'
import { useMountedOnClient } from '../../composables/useMountedOnClient'

const isMounted = useMountedOnClient()

const SIZE_CLASSES = {
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
}

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
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
    processing: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: 'md',
    },
})

const emit = defineEmits(['submit', 'cancel'])

function handleKeydown(event) {
    if (event.key === 'Escape') {
        emit('cancel')
    }
}

// Same reasoning as ActionGroup's menu/Select's dropdown: only listen
// while actually open, and always clean up — a stray document listener
// left behind after this modal closes would swallow Escape everywhere
// else on the page.
watch(
    () => props.open,
    (open) => {
        if (open) {
            document.addEventListener('keydown', handleKeydown)
        } else {
            document.removeEventListener('keydown', handleKeydown)
        }
    },
)

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
    <!-- Teleported to <body>, same reasoning as ConfirmationModal — this
         should never inherit styling from wherever it was triggered. -->
    <Teleport v-if="isMounted" to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 text-left"
            @click.self="$emit('cancel')"
        >
            <form
                class="flex w-full max-h-[90vh] flex-col overflow-hidden rounded-lg bg-white shadow-xl"
                :class="SIZE_CLASSES[size] ?? SIZE_CLASSES.md"
                @submit.prevent="$emit('submit')"
            >
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                    <h2 class="text-base font-semibold text-gray-900">
                        <slot name="title">{{ title }}</slot>
                    </h2>
                    <button
                        type="button"
                        class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        aria-label="Close"
                        @click="$emit('cancel')"
                    >
                        &times;
                    </button>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    <slot />
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-3">
                    <button
                        type="button"
                        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        :disabled="processing"
                        @click="$emit('cancel')"
                    >
                        {{ cancelLabel }}
                    </button>
                    <button
                        type="submit"
                        class="rounded-md bg-green-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-green-500 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="processing"
                    >
                        {{ submitLabel }}
                    </button>
                </div>
            </form>
        </div>
    </Teleport>
</template>
