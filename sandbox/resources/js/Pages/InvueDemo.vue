<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { TextInput, Select, useInvueField } from 'invue/forms';

const page = usePage();

const form = useForm({ name: '', role: '' });
const { modelValue: name, error: nameError } = useInvueField(form, 'name');
const { modelValue: role, error: roleError } = useInvueField(form, 'role');

function submit() {
    form.post(route('invue.demo.store'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="mx-auto max-w-md p-8">
        <h1 class="mb-4 text-xl font-bold text-gray-900">Invue Demo</h1>

        <p v-if="page.props.flash?.status" class="mb-4 text-sm text-green-600">
            {{ page.props.flash.status }}
        </p>

        <form class="space-y-4" @submit.prevent="submit">
            <TextInput
                v-model="name"
                :error="nameError"
                label="Nome"
                hint="Minimo de 3 caracteres"
                required
            />

            <Select
                v-model="role"
                :error="roleError"
                :options="['Admin', 'Editor', 'Viewer']"
                label="Funcao"
                placeholder="Selecione uma funcao"
                required
            />

            <button
                type="submit"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                :disabled="form.processing"
            >
                Enviar
            </button>
        </form>
    </div>
</template>
