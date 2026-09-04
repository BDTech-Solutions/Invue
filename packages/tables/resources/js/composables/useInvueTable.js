import { computed, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

// `state` is a reactive() object, not separate computed() refs like
// useInvueField uses — deliberately. <Table> binds v-model="state.search"
// directly, and a plain object holding nested computed()/ref() values does
// NOT unwrap in templates the way a reactive object's own properties do.
// Don't "fix" this to match useInvueField's shape without understanding why
// they differ.
export function useInvueTable(propName, options = {}) {
    const debounceMs = options.debounce ?? 300
    const only = options.only ?? [propName]

    const page = usePage()
    const source = () => page.props[propName]

    const state = reactive({
        search: source().meta.search ?? '',
        sort: source().meta.sort ?? null,
        direction: source().meta.direction ?? 'asc',
        filters: { ...(source().meta.filters ?? {}) },
        page: source().meta.current_page ?? 1,
        perPage: source().meta.per_page ?? 15,
        // Row-selection ids for bulk actions — purely client-side (never
        // sent to the server by reload()), so it's fine to live alongside
        // the server-driven fields above without its own watcher wiring
        // into them.
        selected: [],
    })

    function reload() {
        // The visible row set is about to change — a stale selection
        // (an id no longer on screen) would silently keep "acting" on rows
        // the user can't see anymore.
        state.selected = []

        router.reload({
            only,
            preserveState: true,
            preserveScroll: true,
            data: {
                search: state.search || undefined,
                sort: state.sort ?? undefined,
                direction: state.direction,
                filters: state.filters,
                page: state.page,
                per_page: state.perPage,
            },
        })
    }

    // Resetting `state.page` to 1 below can itself trigger the `state.page`
    // watcher further down (when the page wasn't already 1), which would
    // call `reload()` a second time — immediately and un-debounced — right
    // alongside the reload these watchers already trigger themselves. That
    // silently defeats the search debounce and double-fires every
    // sort/filter/perPage change. This flag lets the page watcher recognize
    // "I was just reset by another watcher that's already handling the
    // reload" and skip its own redundant one.
    let suppressPageReload = false

    function resetToFirstPage() {
        if (state.page !== 1) {
            suppressPageReload = true
            state.page = 1
        }
    }

    let searchTimer
    watch(
        () => state.search,
        () => {
            resetToFirstPage()
            clearTimeout(searchTimer)
            searchTimer = setTimeout(reload, debounceMs)
        },
    )

    watch(
        () => [state.sort, state.direction, state.filters, state.perPage],
        () => {
            resetToFirstPage()
            reload()
        },
        { deep: true },
    )

    watch(() => state.page, () => {
        if (suppressPageReload) {
            suppressPageReload = false
            return
        }

        reload()
    })

    function toggleSort(column) {
        if (state.sort !== column) {
            state.sort = column
            state.direction = 'asc'
        } else if (state.direction === 'asc') {
            state.direction = 'desc'
        } else {
            state.sort = null
            state.direction = 'asc'
        }
    }

    function isSelected(id) {
        return state.selected.includes(id)
    }

    function toggleSelect(id) {
        const index = state.selected.indexOf(id)

        if (index === -1) {
            state.selected.push(id)
        } else {
            state.selected.splice(index, 1)
        }
    }

    function toggleSelectAll() {
        const ids = source().data.map((row) => row.id)
        const allSelected = ids.length > 0 && ids.every((id) => state.selected.includes(id))

        state.selected = allSelected ? [] : [...ids]
    }

    function clearSelection() {
        state.selected = []
    }

    return {
        rows: computed(() => source().data),
        meta: computed(() => source().meta),
        state,
        toggleSort,
        isSelected,
        toggleSelect,
        toggleSelectAll,
        clearSelection,
    }
}
