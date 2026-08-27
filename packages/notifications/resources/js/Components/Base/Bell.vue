<script setup>
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Icon } from 'invue/core'

// Same static-map rule as every other color prop across Invue.
const DOT_CLASSES = {
    gray: 'bg-gray-400',
    red: 'bg-red-500',
    green: 'bg-green-500',
    blue: 'bg-blue-500',
    yellow: 'bg-yellow-500',
    amber: 'bg-amber-500',
    sky: 'bg-sky-500',
    rose: 'bg-rose-500',
    purple: 'bg-purple-500',
    pink: 'bg-pink-500',
}

const props = defineProps({
    // Matches the prop name the consuming app shares from its own
    // HandleInertiaRequests::share() — see Notification::databaseFor()
    // in the PHP package. Override if shared under a different key.
    prop: {
        type: String,
        default: 'databaseNotifications',
    },
    // (item) => url — omit to render read-only (no mark-as-read requests).
    markAsReadUrl: {
        type: Function,
        default: null,
    },
    markAllAsReadUrl: {
        type: String,
        default: null,
    },
})

const page = usePage()
const source = computed(() => page.props[props.prop] ?? { items: [], unreadCount: 0 })
const items = computed(() => source.value.items ?? [])
const unreadCount = computed(() => source.value.unreadCount ?? 0)

function timeAgo(iso) {
    const seconds = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000))

    const units = [
        ['year', 31536000],
        ['month', 2592000],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ]

    for (const [unit, secondsPerUnit] of units) {
        const value = Math.floor(seconds / secondsPerUnit)

        if (value >= 1) {
            return `${value} ${unit}${value > 1 ? 's' : ''} ago`
        }
    }

    return 'just now'
}

function markAsRead(item) {
    if (item.read || !props.markAsReadUrl) {
        return
    }

    router.patch(props.markAsReadUrl(item), {}, { preserveScroll: true, preserveState: true })
}

function markAllAsRead() {
    if (!props.markAllAsReadUrl) {
        return
    }

    router.patch(props.markAllAsReadUrl, {}, { preserveScroll: true, preserveState: true })
}
</script>

<template>
    <details class="relative">
        <summary
            class="relative flex h-8 w-8 cursor-pointer list-none items-center justify-center rounded-md text-gray-500 select-none hover:bg-gray-100"
            aria-label="Notifications"
        >
            <Icon name="bell" class="h-5 w-5" />
            <span
                v-if="unreadCount > 0"
                class="absolute top-1 right-1 flex h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"
            />
        </summary>

        <div class="absolute right-0 z-10 mt-1 w-80 overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
                <span class="text-xs font-semibold tracking-wide text-gray-400 uppercase">Notifications</span>
                <button
                    v-if="markAllAsReadUrl && unreadCount > 0"
                    type="button"
                    class="text-xs font-medium text-gray-500 hover:text-gray-700"
                    @click="markAllAsRead"
                >
                    Mark all as read
                </button>
            </div>

            <p v-if="items.length === 0" class="px-3 py-6 text-center text-sm text-gray-400">No notifications yet.</p>

            <button
                v-for="item in items"
                :key="item.id"
                type="button"
                class="flex w-full items-start gap-2.5 border-b border-gray-50 px-3 py-2.5 text-left last:border-0 hover:bg-gray-50"
                @click="markAsRead(item)"
            >
                <span
                    class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full"
                    :class="item.read ? 'bg-transparent' : (DOT_CLASSES[item.color] ?? DOT_CLASSES.gray)"
                />
                <span class="min-w-0 flex-1">
                    <span class="block text-sm" :class="item.read ? 'text-gray-500' : 'font-medium text-gray-900'">{{ item.title }}</span>
                    <span v-if="item.body" class="mt-0.5 block truncate text-xs text-gray-500">{{ item.body }}</span>
                    <span class="mt-0.5 block text-xs text-gray-400">{{ timeAgo(item.createdAt) }}</span>
                </span>
            </button>
        </div>
    </details>
</template>
