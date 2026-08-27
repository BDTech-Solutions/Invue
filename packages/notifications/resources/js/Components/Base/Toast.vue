<script setup>
import { Icon } from 'invue/core'

// Same per-component inline color-map shape as invue/tables' TextColumn
// (BADGE_CLASSES) / IconColumn (TEXT_COLOR_CLASSES) — Tailwind only scans
// vendor/invue/**/*.vue for class strings, so these can't live in a shared
// .js module (see invue/core's tailwind.content.js + the parent skill's
// "Tailwind content-scanning gotcha").
const CARD_COLOR_CLASSES = {
    gray: 'border-gray-200 bg-white',
    red: 'border-red-200 bg-red-50',
    green: 'border-green-200 bg-green-50',
    blue: 'border-blue-200 bg-blue-50',
    yellow: 'border-yellow-200 bg-yellow-50',
    amber: 'border-amber-200 bg-amber-50',
    sky: 'border-sky-200 bg-sky-50',
    rose: 'border-rose-200 bg-rose-50',
    purple: 'border-purple-200 bg-purple-50',
    pink: 'border-pink-200 bg-pink-50',
}

const ICON_COLOR_CLASSES = {
    gray: 'text-gray-400',
    red: 'text-red-500',
    green: 'text-green-600',
    blue: 'text-blue-500',
    yellow: 'text-yellow-500',
    amber: 'text-amber-500',
    sky: 'text-sky-500',
    rose: 'text-rose-500',
    purple: 'text-purple-500',
    pink: 'text-pink-500',
}

const props = defineProps({
    title: {
        type: String,
        default: null,
    },
    body: {
        type: String,
        default: null,
    },
    // An icon *name*, resolved through invue/core's <Icon> registry — same
    // convention as invue/tables' IconColumn.
    icon: {
        type: String,
        default: null,
    },
    color: {
        type: String,
        default: 'gray',
    },
    iconColor: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['close'])

function dismiss() {
    emit('close')
}
</script>

<template>
    <div
        class="invue-notification pointer-events-auto flex w-full items-start gap-3 rounded-lg border p-4 shadow-lg"
        :class="CARD_COLOR_CLASSES[color] ?? CARD_COLOR_CLASSES.gray"
        role="alert"
    >
        <!-- Reskin layer: override the icon block entirely (e.g. an avatar
             image instead of an <Icon>) without a registry swap. Default
             content preserves the exact old behaviour (nothing renders
             unless `icon` is set). -->
        <slot name="icon" :icon="icon" :color="iconColor ?? color">
            <Icon
                v-if="icon"
                :name="icon"
                class="mt-0.5 h-5 w-5 shrink-0"
                :class="ICON_COLOR_CLASSES[iconColor ?? color] ?? ICON_COLOR_CLASSES.gray"
            />
        </slot>

        <div class="min-w-0 flex-1">
            <p v-if="title" class="text-sm font-semibold text-gray-900">{{ title }}</p>
            <p v-if="body" class="mt-0.5 text-sm text-gray-600">{{ body }}</p>

            <!-- Reskin layer: room for action buttons (e.g. "Desfazer") next
                 to a toast's text, without needing a full registry swap.
                 Empty by default — the README's noted v1 gap ("no actions"),
                 now closeable per-app instead of forked. -->
            <slot name="actions" :dismiss="dismiss" />
        </div>

        <button
            type="button"
            class="shrink-0 text-gray-400 hover:text-gray-600"
            aria-label="Dismiss"
            @click="dismiss"
        >
            ✕
        </button>
    </div>
</template>
