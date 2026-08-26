<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const panel = computed(() => page.props.invuePanel ?? null)
</script>

<template>
    <header class="flex h-14 items-center justify-between border-b border-gray-200 bg-white px-6">
        <div class="flex items-center gap-2 text-base font-semibold text-gray-900">
            <img v-if="panel?.brandLogoUrl" :src="panel.brandLogoUrl" alt="" class="h-6 w-6 rounded" />
            {{ panel?.brandName ?? 'Invue' }}
        </div>

        <div class="flex items-center gap-3">
            <slot />
        </div>
    </header>
</template>
