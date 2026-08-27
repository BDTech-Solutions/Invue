<script setup>
import { Comment, Fragment, Text, computed, ref, useSlots } from 'vue'
// Resolved wrapper, not Base/BulkActionsBar.vue — same composing rule
// every parent that owns a swappable child follows (PanelLayout ->
// Sidebar/Topbar, ActionsColumn -> ActionGroup).
import BulkActionsBar from '../BulkActionsBar.vue'

const props = defineProps({
    table: {
        type: Object,
        required: true,
    },
    searchable: {
        type: Boolean,
        default: false,
    },
    searchPlaceholder: {
        type: String,
        default: 'Search…',
    },
    emptyMessage: {
        type: String,
        default: 'No results found.',
    },
    // Turns on the leading checkbox column + BulkActionsBar. Off by
    // default so every table that doesn't need bulk actions stays exactly
    // as it rendered before this existed.
    selectable: {
        type: Boolean,
        default: false,
    },
    // A static array, or a function of the selected ids — see
    // Base/BulkActionsBar.vue.
    bulkActions: {
        type: [Array, Function],
        default: () => [],
    },
})

const slots = useSlots()

// `table` is the object returned by useInvueTable(). Its `rows`/`meta` are
// computed() refs (so useInvueTable can keep a single reactive `state`
// object instead — see that composable's own comment), but a ref nested
// inside a plain object passed as a prop does NOT auto-unwrap in templates.
// Rebind them here as top-level script-setup values so the template below
// can use `rows`/`meta` directly.
const rows = computed(() => props.table.rows.value)
const meta = computed(() => props.table.meta.value)

function toBool(value) {
    return value === true || value === '' || value === 'true'
}

function readProp(vnodeProps, key) {
    if (!vnodeProps) {
        return undefined
    }

    if (key in vnodeProps) {
        return vnodeProps[key]
    }

    const kebab = key.replace(/[A-Z]/g, (c) => `-${c.toLowerCase()}`)

    return vnodeProps[kebab]
}

function titleCase(field) {
    if (!field) {
        return ''
    }

    return field
        .split('.')
        .pop()
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase())
}

// <Column> children are never mounted directly — they exist only as slot
// content for us to introspect (field/label/sortable/etc via vnode.props)
// so we can build the <thead> once. Each cell in the body is rendered by
// re-mounting the SAME vnode.type with a `row` prop added (see below), which
// is what actually invokes the column's own cell-rendering logic.
function flattenColumnVNodes(vnodes) {
    const result = []

    for (const vnode of vnodes ?? []) {
        if (vnode.type === Comment || vnode.type === Text) {
            continue
        }

        if (vnode.type === Fragment) {
            result.push(...flattenColumnVNodes(vnode.children))
            continue
        }

        result.push(vnode)
    }

    return result
}

// Metadata keys that Table.vue itself consumes to build the header/toolbar.
// They're stripped before v-binding the vnode's props onto the cell below —
// otherwise, since column components don't declare them, Vue would forward
// them as raw DOM attributes onto the cell's root element. `hidden` is the
// dangerous one: it's a real global HTML attribute, so leaking it through
// would actually hide the cell via the browser, not just look messy.
const COLUMN_METADATA_KEYS = ['label', 'sortable', 'align', 'width', 'hidden', 'toggleable', 'toggledHiddenByDefault']

function cellProps(vnodeProps) {
    if (!vnodeProps) {
        return {}
    }

    const result = {}

    for (const [key, value] of Object.entries(vnodeProps)) {
        const camelKey = key.replace(/-([a-z0-9])/g, (_, c) => c.toUpperCase())

        if (!COLUMN_METADATA_KEYS.includes(camelKey)) {
            result[key] = value
        }
    }

    return result
}

const columns = computed(() =>
    flattenColumnVNodes(slots.default ? slots.default() : [])
        .map((vnode) => ({
            vnode,
            cellProps: cellProps(vnode.props),
            field: readProp(vnode.props, 'field'),
            label: readProp(vnode.props, 'label') ?? titleCase(readProp(vnode.props, 'field')),
            sortable: toBool(readProp(vnode.props, 'sortable')),
            align: readProp(vnode.props, 'align') ?? 'start',
            width: readProp(vnode.props, 'width'),
            hidden: toBool(readProp(vnode.props, 'hidden')),
            toggleable: toBool(readProp(vnode.props, 'toggleable')),
        }))
        .filter((column) => !column.hidden),
)

// Column visibility toggling is a purely client-side display preference
// (matches Filament's own ->toggleable() — it never round-trips to the
// server), so it lives in local state here instead of useInvueTable's state.
const manuallyHidden = ref(
    new Set(
        flattenColumnVNodes(slots.default ? slots.default() : [])
            .filter(
                (vnode) =>
                    toBool(readProp(vnode.props, 'toggleable')) &&
                    toBool(readProp(vnode.props, 'toggledHiddenByDefault')),
            )
            .map((vnode) => readProp(vnode.props, 'field')),
    ),
)

function toggleColumn(field) {
    const next = new Set(manuallyHidden.value)

    if (next.has(field)) {
        next.delete(field)
    } else {
        next.add(field)
    }

    manuallyHidden.value = next
}

const toggleableColumns = computed(() => columns.value.filter((column) => column.toggleable))
const hasToggleableColumns = computed(() => toggleableColumns.value.length > 0)
const visibleColumns = computed(() => columns.value.filter((column) => !manuallyHidden.value.has(column.field)))

function alignClass(align) {
    return { start: 'text-left', center: 'text-center', end: 'text-right' }[align] ?? 'text-left'
}

const hasNextPage = computed(() => {
    const m = meta.value

    if (!m) {
        return false
    }

    return m.current_page * m.per_page < m.total
})

const paginationSummary = computed(() => {
    const m = meta.value

    if (!m || m.total === 0) {
        return ''
    }

    const start = (m.current_page - 1) * m.per_page + 1
    const end = Math.min(m.current_page * m.per_page, m.total)

    return `Showing ${start}–${end} of ${m.total}`
})

const allRowsSelected = computed(() => rows.value.length > 0 && rows.value.every((row) => props.table.isSelected(row.id)))
const someRowsSelected = computed(() => rows.value.some((row) => props.table.isSelected(row.id)))

// Native checkboxes have no `indeterminate` attribute — it's a DOM
// property only, so it has to be set imperatively via a directive instead
// of a template binding.
const vIndeterminate = {
    mounted(el, binding) {
        el.indeterminate = binding.value
    },
    updated(el, binding) {
        el.indeterminate = binding.value
    },
}
</script>

<template>
    <div class="invue-table">
        <div
            v-if="searchable || $slots.filters || hasToggleableColumns"
            class="mb-3 flex flex-wrap items-center gap-3"
        >
            <input
                v-if="searchable"
                v-model="table.state.search"
                type="search"
                :placeholder="searchPlaceholder"
                class="block w-full max-w-xs rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
            />

            <slot name="filters" />

            <details v-if="hasToggleableColumns" class="relative ml-auto">
                <summary
                    class="cursor-pointer list-none rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-600 select-none"
                >
                    Columns
                </summary>
                <div
                    class="absolute right-0 z-10 mt-1 w-48 rounded-md border border-gray-200 bg-white p-2 shadow-lg"
                >
                    <label
                        v-for="column in toggleableColumns"
                        :key="column.field"
                        class="flex items-center gap-2 rounded px-1 py-1 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        <input
                            type="checkbox"
                            class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                            :checked="!manuallyHidden.has(column.field)"
                            @change="toggleColumn(column.field)"
                        />
                        {{ column.label }}
                    </label>
                </div>
            </details>
        </div>

        <BulkActionsBar
            v-if="selectable"
            :count="table.state.selected.length"
            :actions="bulkActions"
            :selected-ids="table.state.selected"
            @success="table.clearSelection()"
        />

        <div class="overflow-x-auto rounded-md border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th v-if="selectable" scope="col" class="w-10 px-3 py-2">
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                :checked="allRowsSelected"
                                v-indeterminate="someRowsSelected && !allRowsSelected"
                                @change="table.toggleSelectAll()"
                            />
                        </th>
                        <th
                            v-for="column in visibleColumns"
                            :key="column.field"
                            scope="col"
                            class="px-3 py-2 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                            :class="[alignClass(column.align), column.sortable ? 'cursor-pointer select-none' : '']"
                            :style="column.width ? { width: column.width } : null"
                            @click="column.sortable && table.toggleSort(column.field)"
                        >
                            <span class="inline-flex items-center gap-1">
                                {{ column.label }}
                                <span v-if="column.sortable" class="text-gray-400">
                                    <template v-if="table.state.sort === column.field">{{
                                        table.state.direction === 'asc' ? '↑' : '↓'
                                    }}</template>
                                    <template v-else>↕</template>
                                </span>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr v-if="rows.length === 0">
                        <td :colspan="(visibleColumns.length || 1) + (selectable ? 1 : 0)" class="px-3 py-6 text-center text-gray-400">
                            {{ emptyMessage }}
                        </td>
                    </tr>
                    <tr v-for="(row, rowIndex) in rows" :key="row.id ?? rowIndex">
                        <td v-if="selectable" class="px-3 py-2">
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                :checked="table.isSelected(row.id)"
                                @change="table.toggleSelect(row.id)"
                            />
                        </td>
                        <td
                            v-for="column in visibleColumns"
                            :key="column.field"
                            class="px-3 py-2"
                            :class="alignClass(column.align)"
                        >
                            <component :is="column.vnode.type" v-bind="column.cellProps" :row="row" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="meta" class="mt-3 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500">
            <p>{{ paginationSummary }}</p>
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    :disabled="table.state.page <= 1"
                    class="rounded-md border border-gray-300 px-2.5 py-1 text-gray-600 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="table.state.page--"
                >
                    Prev
                </button>
                <button
                    type="button"
                    :disabled="!hasNextPage"
                    class="rounded-md border border-gray-300 px-2.5 py-1 text-gray-600 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="table.state.page++"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>
