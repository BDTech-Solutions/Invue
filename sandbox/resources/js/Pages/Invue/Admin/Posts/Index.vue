<script setup>
import { Table, TextColumn, IconColumn } from 'invue/tables'
import { useInvueTable } from 'invue/tables'
import { PanelLayout } from 'invue/panels'
import { Link } from '@inertiajs/vue3'

const table = useInvueTable('posts')
</script>

<template>
    <PanelLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Posts</h1>
            <Link
                :href="route('invue.admin.posts.create')"
                class="rounded-md bg-green-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-green-500"
            >
                New Post
            </Link>
        </div>

        <Table :table="table" searchable>
            <TextColumn field="title" label="Title" searchable sortable />
            <TextColumn field="body" label="Body" limit="60" />
            <IconColumn field="published" label="Published" boolean />
            <TextColumn
                field="id"
                label="Actions"
                :url="(value, row) => route('invue.admin.posts.edit', row.id)"
                :format-using="() => 'Edit'"
            />
        </Table>
    </PanelLayout>
</template>
