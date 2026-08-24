<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { TextInput, Select, Checkbox, Textarea, RadioGroup, FileUpload, CheckboxGroup, Repeater, useInvueField } from 'invue/forms';

const page = usePage();

const form = useForm({ name: '', role: '', terms: false, bio: '', plan: '', avatar: null, age: '', interests: [], links: [] });
const { modelValue: name, error: nameError } = useInvueField(form, 'name');
const { modelValue: role, error: roleError } = useInvueField(form, 'role');
const { modelValue: terms, error: termsError } = useInvueField(form, 'terms');
const { modelValue: bio, error: bioError } = useInvueField(form, 'bio');
const { modelValue: plan, error: planError } = useInvueField(form, 'plan');
const { modelValue: avatar, error: avatarError } = useInvueField(form, 'avatar');
const { modelValue: age, error: ageError } = useInvueField(form, 'age');
const { modelValue: interests, error: interestsError } = useInvueField(form, 'interests');
const { modelValue: links, error: linksError } = useInvueField(form, 'links');

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

        <form class="space-y-4" novalidate @submit.prevent="submit">
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

            <Checkbox
                v-model="terms"
                :error="termsError"
                label="Aceito os termos"
                required
            />

            <Textarea
                v-model="bio"
                :error="bioError"
                label="Bio"
                hint="Ate 20 caracteres"
                rows="2"
            />

            <RadioGroup
                v-model="plan"
                :error="planError"
                :options="['Free', 'Pro', 'Enterprise']"
                label="Plano"
                required
            />

            <FileUpload
                v-model="avatar"
                :error="avatarError"
                accept="image/*"
                label="Avatar"
                hint="PNG ou JPG, ate 2MB"
            />

            <TextInput
                v-model="age"
                :error="ageError"
                type="number"
                label="Idade"
                min="18"
                max="120"
                required
            />

            <CheckboxGroup
                v-model="interests"
                :error="interestsError"
                :options="['Design', 'Dev', 'Marketing']"
                label="Interesses"
                required
            />

            <Repeater
                v-model="links"
                :error="linksError"
                :new-item="() => ({ url: '' })"
                label="Links"
                hint="Ate 2 links"
            >
                <template #default="{ item, update }">
                    <TextInput
                        :model-value="item.url"
                        placeholder="https://..."
                        @update:model-value="(value) => update({ ...item, url: value })"
                    />
                </template>
            </Repeater>

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
