<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Icon } from 'invue/core'

// Per-component inline color/width-class maps, same shape as
// invue/notifications' Toast.vue (CARD_COLOR_CLASSES/ICON_COLOR_CLASSES) —
// Tailwind only scans vendor/invue/**/*.vue for literal class strings (see
// invue/core's tailwind.content.js + the parent skill's "Tailwind
// content-scanning gotcha"), so an arbitrary color/width string passed
// through a prop can never resolve to a real class — only a name looked up
// against a map whose values are already static text in this file can.
const ACTIVE_ITEM_CLASSES = {
    gray: 'bg-gray-100 text-gray-900',
    red: 'bg-red-50 text-red-700',
    green: 'bg-green-50 text-green-700',
    blue: 'bg-blue-50 text-blue-700',
    yellow: 'bg-yellow-50 text-yellow-700',
    amber: 'bg-amber-50 text-amber-700',
    sky: 'bg-sky-50 text-sky-700',
    rose: 'bg-rose-50 text-rose-700',
    purple: 'bg-purple-50 text-purple-700',
    pink: 'bg-pink-50 text-pink-700',
}

const WIDTH_CLASSES = {
    sm: 'w-48',
    md: 'w-56',
    lg: 'w-64',
}

const INACTIVE_ITEM_CLASSES = 'text-gray-600 hover:bg-gray-50'

const props = defineProps({
    // Overrides page.props.invuePanel.navigation — lets a Sidebar be used
    // (previewed, tested, reused outside a Panel) without an Inertia page
    // actually sharing invuePanel.
    items: {
        type: Array,
        default: null,
    },
    // One of the shared Invue color names (see invue/notifications' Toast
    // `color` prop) — the active nav item's background/text color.
    selectedColor: {
        type: String,
        default: 'green',
    },
    width: {
        type: String,
        default: 'md',
    },
})

const page = usePage()

const navigation = computed(() => props.items ?? page.props.invuePanel?.navigation ?? [])

function isActive(url) {
    const current = page.props.invuePanel?.current ?? (typeof window !== 'undefined' ? window.location.pathname : '')

    return current === url || current.startsWith(`${url}/`)
}
</script>

<template>
    <aside
        class="invue-sidebar flex shrink-0 flex-col border-r border-gray-200 bg-white"
        :class="WIDTH_CLASSES[width] ?? WIDTH_CLASSES.md"
    >
        <div v-if="$slots.header" class="border-b border-gray-200 px-4 py-3">
            <slot name="header" />
        </div>

        <nav class="flex-1 space-y-0.5 overflow-y-auto px-2 py-4">
            <template v-for="item in navigation" :key="item.url">
                <!-- Default row markup renders when the caller doesn't fill
                     #item — a scoped slot so a store variant can restyle or
                     enrich a row (badges, nesting, ...) without forking the
                     whole component just for that. -->
                <slot name="item" :item="item" :active="isActive(item.url)">
                    <Link
                        :href="item.url"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium"
                        :class="isActive(item.url) ? (ACTIVE_ITEM_CLASSES[selectedColor] ?? ACTIVE_ITEM_CLASSES.green) : INACTIVE_ITEM_CLASSES"
                    >
                        <Icon v-if="item.icon" :name="item.icon" class="h-4 w-4 shrink-0" />
                        <span>{{ item.label }}</span>
                    </Link>
                </slot>
            </template>
        </nav>

        <div v-if="$slots.footer" class="border-t border-gray-200 px-2 py-3">
            <slot name="footer" />
        </div>
    </aside>
</template>
