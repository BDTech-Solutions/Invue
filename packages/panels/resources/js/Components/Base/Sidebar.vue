<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Icon } from 'invue/core'

const page = usePage()

const navigation = computed(() => page.props.invuePanel?.navigation ?? [])

function isActive(url) {
    const current = page.props.invuePanel?.current ?? (typeof window !== 'undefined' ? window.location.pathname : '')

    return current === url || current.startsWith(`${url}/`)
}
</script>

<template>
    <aside class="flex w-56 shrink-0 flex-col border-r border-gray-200 bg-white py-4">
        <nav class="flex-1 space-y-0.5 px-2">
            <Link
                v-for="item in navigation"
                :key="item.url"
                :href="item.url"
                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium"
                :class="isActive(item.url) ? 'bg-green-50 text-green-700' : 'text-gray-600 hover:bg-gray-50'"
            >
                <Icon v-if="item.icon" :name="item.icon" class="h-4 w-4 shrink-0" />
                <span>{{ item.label }}</span>
            </Link>
        </nav>
    </aside>
</template>
