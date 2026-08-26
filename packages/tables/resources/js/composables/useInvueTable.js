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
    })

    function reload() {
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

    let searchTimer
    watch(
        () => state.search,
        () => {
            state.page = 1
            clearTimeout(searchTimer)
            searchTimer = setTimeout(reload, debounceMs)
        },
    )

    watch(
        () => [state.sort, state.direction, state.filters, state.perPage],
        () => {
            state.page = 1
            reload()
        },
        { deep: true },
    )

    watch(() => state.page, reload)

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

    return {
        rows: computed(() => source().data),
        meta: computed(() => source().meta),
        state,
        toggleSort,
    }
}
