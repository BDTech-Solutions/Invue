<script setup>
// Built on top of the public Repeater/TextInput wrappers (not their Base
// components) on purpose: a global registry swap of forms.Repeater or
// forms.TextInput automatically applies inside every KeyValue row too.
import Repeater from '../Repeater.vue'
import TextInput from '../TextInput.vue'

defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    keyPlaceholder: {
        type: String,
        default: 'Chave',
    },
    valuePlaceholder: {
        type: String,
        default: 'Valor',
    },
    label: {
        type: String,
        default: null,
    },
    hint: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
})

defineEmits(['update:modelValue'])
</script>

<template>
    <Repeater
        :model-value="modelValue"
        :new-item="() => ({ key: '', value: '' })"
        :label="label"
        :hint="hint"
        :required="required"
        :disabled="disabled"
        :error="error"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <template #default="{ item, update }">
            <div class="flex gap-2">
                <TextInput
                    :model-value="item.key"
                    :placeholder="keyPlaceholder"
                    :disabled="disabled"
                    class="flex-1"
                    @update:model-value="(value) => update({ ...item, key: value })"
                />
                <TextInput
                    :model-value="item.value"
                    :placeholder="valuePlaceholder"
                    :disabled="disabled"
                    class="flex-1"
                    @update:model-value="(value) => update({ ...item, value: value })"
                />
            </div>
        </template>
    </Repeater>
</template>
