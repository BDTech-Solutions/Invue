<script setup>
import { computed, inject } from 'vue'
import { InvueRegistryKey } from 'invue/core'
import BaseKeyValue from './Base/KeyValue.vue'

const registry = inject(InvueRegistryKey, null)

const resolved = computed(() => registry?.resolve('forms.KeyValue', BaseKeyValue) ?? BaseKeyValue)
</script>

<template>
    <component :is="resolved" v-bind="$attrs" />
</template>
