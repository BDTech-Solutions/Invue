<script setup>
import { useForm } from '@inertiajs/vue3'
import { TextInput, Textarea, Checkbox, useInvueField } from 'invue/forms'
import { PanelLayout } from 'invue/panels'

const props = defineProps({
    post: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    title: props.post.title,
    body: props.post.body,
    published: props.post.published,
})

const { modelValue: title, error: titleError } = useInvueField(form, 'title')
const { modelValue: body, error: bodyError } = useInvueField(form, 'body')
const { modelValue: published, error: publishedError } = useInvueField(form, 'published')

function submit() {
    form.put(route('invue.admin.posts.update', props.post.id))
}
</script>

<template>
    <PanelLayout>
        <h1 class="mb-6 text-xl font-semibold text-gray-900">Edit Post</h1>

        <form class="max-w-lg space-y-4" novalidate @submit.prevent="submit">
            <TextInput v-model="title" :error="titleError" label="Title" />
            <Textarea v-model="body" :error="bodyError" label="Body" rows="4" />
            <Checkbox v-model="published" :error="publishedError" label="Published" />

            <button
                type="submit"
                class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500"
                :disabled="form.processing"
            >
                Save
            </button>
        </form>
    </PanelLayout>
</template>
