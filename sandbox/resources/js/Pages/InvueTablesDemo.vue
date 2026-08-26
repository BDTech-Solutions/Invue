<script setup>
import { Table, TextColumn, IconColumn, SelectFilter, TernaryFilter, useInvueTable } from 'invue/tables'

const table = useInvueTable('users')

function roleColor(value) {
    return { admin: 'green', editor: 'blue', viewer: 'gray' }[value] ?? 'gray'
}
</script>

<template>
    <div class="mx-auto max-w-5xl p-8">
        <h1 class="mb-1 text-xl font-bold text-gray-900">Users</h1>
        <p class="mb-6 text-sm text-gray-500">invue/tables demo — search, sort, filter and paginate, all server-driven.</p>

        <Table :table="table" searchable search-placeholder="Search by name or email...">
            <template #filters>
                <SelectFilter
                    v-model="table.state.filters.role"
                    label="Role"
                    placeholder="All roles"
                    :options="[
                        { value: 'admin', label: 'Admin' },
                        { value: 'editor', label: 'Editor' },
                        { value: 'viewer', label: 'Viewer' },
                    ]"
                />
                <TernaryFilter
                    v-model="table.state.filters.is_active"
                    label="Status"
                    true-label="Active"
                    false-label="Inactive"
                />
            </template>

            <TextColumn field="name" label="Name" searchable sortable weight="medium" />
            <TextColumn field="email" label="Email" searchable copyable />
            <TextColumn field="role" label="Role" badge :color="roleColor" />
            <IconColumn field="is_active" label="Active" boolean />
            <IconColumn
                field="email_verified_at"
                label="Verified"
                boolean
                toggleable
            />
            <TextColumn field="created_at" label="Joined" date="YYYY-MM-DD" sortable />
        </Table>
    </div>
</template>
