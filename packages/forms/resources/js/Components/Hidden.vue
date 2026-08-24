<script setup>
import { computed, inject } from 'vue'
import { InvueRegistryKey } from 'invue/core'
import BaseHidden from './Base/Hidden.vue'

const registry = inject(InvueRegistryKey, null)

const resolved = computed(() => registry?.resolve('forms.Hidden', BaseHidden) ?? BaseHidden)
</script>

<template>
    <component :is="resolved" v-bind="$attrs" />
</template>
