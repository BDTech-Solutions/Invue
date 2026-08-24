<script setup>
import { computed, inject } from 'vue'
import { InvueRegistryKey } from 'invue/core'
import BaseCheckbox from './Base/Checkbox.vue'

const registry = inject(InvueRegistryKey, null)

const resolved = computed(() => registry?.resolve('forms.Checkbox', BaseCheckbox) ?? BaseCheckbox)
</script>

<template>
    <component :is="resolved" v-bind="$attrs">
        <template v-for="(_, slotName) in $slots" #[slotName]="scope" :key="slotName">
            <slot :name="slotName" v-bind="scope" />
        </template>
    </component>
</template>
