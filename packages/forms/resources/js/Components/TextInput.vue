<script setup>
import { computed, inject } from 'vue'
import { InvueRegistryKey } from 'invue/core'
import BaseTextInput from './Base/TextInput.vue'

const registry = inject(InvueRegistryKey, null)

const resolved = computed(() => registry?.resolve('forms.TextInput', BaseTextInput) ?? BaseTextInput)
</script>

<template>
    <component :is="resolved" v-bind="$attrs">
        <template v-for="(_, slotName) in $slots" #[slotName]="scope" :key="slotName">
            <slot :name="slotName" v-bind="scope" />
        </template>
    </component>
</template>
