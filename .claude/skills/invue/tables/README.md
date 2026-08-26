# invue/tables — architecture & design

This is the design spec for `invue/tables`, now built at `packages/tables/`
(see "Build gotcha" below for the one consumer-facing wiring change it
required that `invue/forms` never needed). It translates Filament's Table
Builder feature set into Invue's own philosophy: no PHP UI builder, no
Livewire. Filament is the *inspiration for the feature list*, not a pattern
to copy mechanically — the delivery mechanism is 100% Invue's own (real
`.vue` components + Inertia).

## The translation, in one paragraph

Filament defines a table as a PHP method chain
(`TextColumn::make('name')->searchable()->sortable()->badge()`) evaluated on
a Livewire component; every interaction (a keystroke, a sort click, a page
change) is a Livewire AJAX round-trip that reconstructs the underlying
Eloquent query from scratch and re-renders. Invue keeps the *same
one-toggle-per-feature ergonomics* (`searchable`, `sortable`, `badge` as
one-word switches) but as **Vue component props**, and keeps Filament's
*same always-refetch-from-the-server model* but via **Inertia partial
reloads** instead of Livewire — see "Performance model" below. Nothing here
is client-side-only filtering/sorting of an already-fetched array; that
would silently stop working correctly the moment a table has more rows than
one page holds.

## Package layout (mirrors `packages/forms/`)

```
packages/tables/
  composer.json                          invue/tables, PSR-4 Invue\Tables\
  src/
    TablesServiceProvider.php
    TableQuery.php                       backend query helper — see below
  resources/js/
    Components/
      Table.vue                          resolving wrapper -> Base/Table.vue
      Base/Table.vue
      Columns/
        TextColumn.vue + Base/TextColumn.vue
        IconColumn.vue + Base/IconColumn.vue
        ImageColumn.vue + Base/ImageColumn.vue
        ColorColumn.vue + Base/ColorColumn.vue
        CheckboxColumn.vue + Base/CheckboxColumn.vue
        ToggleColumn.vue + Base/ToggleColumn.vue
        SelectColumn.vue + Base/SelectColumn.vue
      Filters/
        SelectFilter.vue + Base/SelectFilter.vue
        TernaryFilter.vue + Base/TernaryFilter.vue
        (no dedicated "Filter" component — a custom filter is just
         invue/forms fields composed directly in #filters, see "Filters"
         below; there's no `tables.Filter` registry key for this reason)
    composables/
      useInvueTable.js                   the composable that does the work
    index.js
```

Same distribution model as every other package: no npm publish, no
`vendor:publish`, `@invue/vite-plugin` resolves `invue/tables` straight from
`vendor/invue/tables/resources/js` at build time. Same Base + resolving
wrapper shape as `invue/forms` for every single component listed above —
copy that shape exactly, don't skip the wrapper (see the parent skill's
"Component registry" section for why).

## Why `TableQuery` isn't a PHP UI builder

Invue's "no PHP UI builder" rule is about **presentation** — columns, their
formatting, their visibility, are defined in Vue, not PHP. `TableQuery` is
the opposite kind of thing: a **backend query-shaping helper**, in the same
family as Spatie's Laravel Query Builder. It never knows what a column
*looks like*; it only turns known HTTP request params (`search`, `sort`,
`direction`, `filter[...]`, `page`, `per_page`) into `where`/`orderBy`/
`paginate` calls against an Eloquent builder **you already own** in your own
controller. The controller decides what's searchable/sortable server-side
(a whitelist, for security — never blindly `orderBy($request->sort)`); the
Vue `<Column>` props decide what's *displayed* and *how the UI exposes* that
capability. Two decoupled concerns, exactly like `FormRequest` validation
rules are decoupled from a form field's Vue props.

```php
use Invue\Tables\TableQuery;

public function index(Request $request)
{
    return Inertia::render('Users/Index', [
        'users' => TableQuery::for(User::query())
            ->searchable(['name', 'email'])
            ->sortable(['name', 'email', 'created_at'])
            ->filterable(['role'])
            ->defaultSort('created_at', 'desc')
            ->paginate($request),
    ]);
}
```

`paginate()` returns a plain array shape (not a raw `LengthAwarePaginator`,
so it survives Inertia's JSON serialization predictably):

```php
[
    'data' => [...],          // the current page's rows, as-is from the query
    'meta' => [
        'total' => 132,
        'current_page' => 1,
        'per_page' => 15,
        'search' => 'ana',
        'sort' => 'created_at',
        'direction' => 'desc',
        'filters' => ['role' => 'admin'],
    ],
]
```

`useInvueTable` (below) expects exactly this shape. This is the one and
only contract between the PHP side and the Vue side — deliberately thin.

## `useInvueTable(propName, options?)` — the composable that does the heavy lifting

This is the tables equivalent of `useInvueField`: one call that turns a raw
Inertia prop into everything a `<Table>` needs, so a page component doesn't
hand-roll debouncing or query-string building itself.

```js
import { computed, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

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
    watch(() => state.search, () => {
        state.page = 1
        clearTimeout(searchTimer)
        searchTimer = setTimeout(reload, debounceMs)
    })

    watch(() => [state.sort, state.direction, state.filters, state.perPage], () => {
        state.page = 1
        reload()
    }, { deep: true })

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
```

**Gotcha this is designed to avoid before it happens once (see the parent
skill's `useInvueField` section for the same class of bug):** `state` here
is a `reactive()` object, not a set of separate `computed()` refs, and
that's deliberate — `<Table>` binds `v-model="state.search"` etc. directly,
and reactive-object-property bindings *do* unwrap correctly in templates
(unlike a plain returned object holding nested `ref`s). Don't refactor this
to return `{ search: ref(...), sort: ref(...) }` inside a plain object
without re-reading why `useInvueField` returns individual `computed()` refs
instead — the two composables solve superficially similar problems with
deliberately different internal shapes for this exact reason.

## The `<Table>` + `<Column>` composition pattern

Columns are **children**, not a JS array prop — this is the direct
translation of "just add `->searchable()`" into Vue terms: a prop you add
to a tag, not a config object you assemble by hand.

```vue
<script setup>
import { Table, TextColumn, IconColumn, SelectFilter } from 'invue/tables'
import { useInvueTable } from 'invue/tables'

const table = useInvueTable('users')
</script>

<template>
    <Table :rows="table.rows" :meta="table.meta" :state="table.state" searchable>
        <template #filters>
            <SelectFilter v-model="table.state.filters.role" label="Role" :options="['admin', 'editor', 'viewer']" />
        </template>

        <TextColumn field="name" label="Name" searchable sortable />
        <TextColumn field="email" label="Email" searchable copyable />
        <TextColumn
            field="role"
            label="Role"
            badge
            :color="(value) => ({ admin: 'green', editor: 'blue' })[value] ?? 'stone'"
        />
        <IconColumn field="is_verified" label="Verified" boolean />
        <TextColumn field="created_at" label="Joined" date="MMM D, YYYY" sortable />
    </Table>
</template>
```

`Table.vue` (Base) reads its default slot's children **not** to render them
directly, but to introspect each `<Column>`'s props (via `slots.default()`
returning VNodes, reading `.props` off each) to build the `<thead>` once,
then renders one row per item in `rows` re-invoking each column vnode's
render logic per cell. This mirrors exactly how `Base/KeyValue.vue`
composes `Repeater` + `TextInput` — a field (or here, a table) built from
other resolved public components, not raw markup, so a registry swap of
`tables.TextColumn` also applies inside every table that uses it.

## Column prop catalog

Every prop below is the direct Vue-prop translation of a Filament PHP
method. Table-wide props (`searchable`, pagination options) live on
`<Table>`; per-column props live on the `<Column>` tag. Anything marked
"closure in Filament" becomes a plain JS function prop in Invue — no
different than `Repeater`'s existing `:new-item="() => ({...})"` pattern.

### Shared by every column type

| Prop | Filament equivalent | Notes |
|---|---|---|
| `field` | `Column::make('name')` | Dot notation for relationships (`author.name`) — same as Filament. |
| `label` | `->label()` | Defaults to a title-cased `field`. |
| `sortable` | `->sortable()` | Boolean. Multi-DB-column sort (Filament's `sortable(['first','last'])`) isn't v1 — see scope below. |
| `default` | `->default()` | Fallback value treated as real state (formatters still apply). |
| `placeholder` | `->placeholder()` | Fallback *display* only, never formatted. |
| `align` | `->alignment()` | `'start' \| 'center' \| 'end'`. |
| `width` | `->width()` | Raw CSS value, e.g. `'1%'`, `'120px'`. |
| `hidden` | `->hidden()` | Boolean or a function of `state`/row. |
| `toggleable` | `->toggleable()` | Shows in a column-visibility dropdown in the table toolbar. |
| `toggledHiddenByDefault` | `->toggleable(isToggledHiddenByDefault: true)` | Starts hidden even though it's toggleable. |

### `TextColumn`

| Prop | Filament equivalent |
|---|---|
| `searchable` | `->searchable()` — participates in the table's single global search box (see Performance model; per-column dedicated search inputs are not v1). |
| `badge` | `->badge()` |
| `color` | `->color()` — string or `(value, row) => string`. |
| `icon` / `iconPosition` | `->icon()` / `->iconPosition()` |
| `money="USD"` / `numeric` / `date="Y-m-d"` / `dateTime` / `time` / `since` | `->money()` / `->numeric()` / `->date()` / `->dateTime()` / `->time()` / `->since()` — same format-string conventions (Day.js under the hood, not Carbon, since this runs client-side; the server sends raw values, Invue formats them for display). |
| `formatUsing` | `->formatStateUsing()` — `(value, row) => string` |
| `limit` / `words` | `->limit()` / `->words()` |
| `wrap` | `->wrap()` |
| `description` / `descriptionPosition` | `->description()` |
| `url` / `openInNewTab` | `->url()` / `->openUrlInNewTab()` |
| `copyable` | `->copyable()` |
| `weight` / `size` | `->weight()` / `->size()` — plain string unions, not enum classes. |

### `IconColumn`

| Prop | Filament equivalent |
|---|---|
| `icon` | `->icon()` |
| `boolean` | `->boolean()` — reads the value as truthy/falsy and swaps icon+color automatically. |
| `trueIcon` / `falseIcon` / `trueColor` / `falseColor` | same names, used with `boolean`. |
| `color` | `->color()` |
| `size` | `->size()` |

### `ImageColumn`

| Prop | Filament equivalent |
|---|---|
| `size` / `width` / `height` | `->imageSize()` / `->imageWidth()` / `->imageHeight()` |
| `square` / `circular` | `->square()` / `->circular()` |
| `stacked` / `ring` / `overlap` | `->stacked()` / `->ring()` / `->overlap()` — avatar-stack mode. |
| `limit` | `->limit()` — cap how many images render for a multi-image cell. |
| `defaultUrl` | `->defaultImageUrl()` |
| `alt` | `->alt()` |

### `ColorColumn`

| Prop | Filament equivalent |
|---|---|
| `copyable` | `->copyable()` |

### `CheckboxColumn` / `ToggleColumn` (inline-editable)

| Prop | Filament equivalent |
|---|---|
| `disabled` | `->disabled()` — **same caveat as Filament: does not auto-check policies**, gate it yourself. |
| `onUpdate` | `->afterStateUpdated()` — `(newValue, row) => void`, typically a `router.patch()` call you write yourself. Invue doesn't auto-persist inline edits; see scope note below. |

### `SelectColumn` (inline-editable)

| Prop | Filament equivalent |
|---|---|
| `options` | `->options()` — array or `{value, label}[]`, same shape as `forms/Select`. |
| `onUpdate` | `->afterStateUpdated()` |

## Filters

`<SelectFilter>` and `<TernaryFilter>` live inside `<Table>`'s `#filters`
slot and bind directly to `table.state.filters.<key>` (a `v-model`, no
separate composable API — the filter's own `v-model` write is what
triggers `useInvueTable`'s deep watcher and reloads). A fully custom filter
is just: put whatever `invue/forms` fields you want inside `#filters` and
wire them to `table.state.filters` yourself — same "compose from existing
resolved components" idea as `KeyValue`, not a new primitive.

| Component | Filament equivalent | Notes |
|---|---|---|
| `SelectFilter` | `SelectFilter::make()` | `options`, `multiple`, `placeholder`. |
| `TernaryFilter` | `TernaryFilter::make()` | Three states: `true` / `false` / `null`. `trueLabel`/`falseLabel`/`placeholder` props. |

Backend: `TableQuery::filterable(['role'])` whitelists which `filters[...]`
keys are honored — same security reasoning as the `sortable()`/`searchable()`
whitelists, never trust the request to name its own column.

## Performance model — same behavior as Filament, different delivery

Filament's table is a Livewire component: **every** interaction — a
debounced keystroke, a sort-header click, applying a filter, changing page
or per-page size, toggling a column — triggers a full Livewire round-trip
that reconstructs the Eloquent query from scratch server-side and
re-renders. There is no client-side-only re-filtering of an
already-fetched record set anywhere in Filament's model, and Invue
shouldn't invent one either — it would just be a different, *worse* bug
(silently wrong results past the first page). `useInvueTable` mirrors this
exactly, just over Inertia instead of Livewire:

- Every state change (`search`, `sort`, `direction`, `filters`, `page`,
  `perPage`) triggers `router.reload({ only: [propName], data: {...} })` —
  a **partial reload**: only this table's prop is re-fetched and
  re-rendered, not the whole page, keeping it cheap regardless of how much
  else lives on the page.
- Search is debounced (default 300ms) client-side before it ever reaches
  the server — search keystrokes don't each trigger a request the way a
  sort click does immediately.
- `TableQuery` whitelists `searchable`/`sortable`/`filterable` columns
  server-side — this isn't optional, it's the only thing standing between
  a request and `ORDER BY <anything the client sent>`.
- Eager-loading relationship columns (`field="author.name"`) is the
  **consuming controller's job**, via `->with(['author'])` on the base
  query passed into `TableQuery::for()` — same as Filament auto-eager-loads
  dot-notation columns, Invue doesn't hide this behind magic since the
  query is already sitting in your own controller code, in plain sight, one
  line away.
- `TableQuery::paginate()` uses standard offset pagination
  (`LengthAwarePaginator`) for v1. Cursor pagination for very large tables
  is a documented Filament option (`PaginationMode::Cursor`) worth adding
  later but isn't in the first cut.

## V1 scope — what this deliberately doesn't attempt yet

Matching how `invue/forms` shipped `TextInput` before `Repeater`, and
`Select` before ever considering a searchable combobox: ship the common
80% first, leave a clear escape hatch, don't chase every Filament feature
in the first pass.

**In v1:** the 7 column types above, their listed props, `SelectFilter` +
`TernaryFilter` + custom-slot filters, global per-table search, single-column
sort, offset pagination with a per-page selector, column show/hide
toggling, `useInvueTable` + `TableQuery`.

**Not in v1** (all real Filament features, all legitimate follow-ups once
the above is solid — don't build them speculatively now):
- **Row/bulk/header actions.** Filament's Action system is a large feature
  area on its own (confirmation modals, notifications, grouped dropdowns).
  For v1, render your own buttons/links inside a plain `<TextColumn>` `url`
  prop or a custom cell slot; a real `invue/actions`-style addition is a
  separate future package, not bolted onto tables.
- **Inline-edit persistence.** `CheckboxColumn`/`ToggleColumn`/`SelectColumn`
  expose `onUpdate` so *you* wire the save (typically one `router.patch()`
  call); Invue doesn't auto-PATCH on your behalf in v1.
- Grouping rows, drag-to-reorder, footer summaries/aggregates (`summarize()`),
  polling/auto-refresh, cursor pagination, responsive stacked-card layout,
  multi-column simultaneous sort, panel-wide cross-resource global search
  (that one's Filament-panel-specific and has no Invue equivalent at all —
  Invue has no admin-panel concept to search across).

## Registry keys

`tables.Table`, `tables.TextColumn`, `tables.IconColumn`,
`tables.ImageColumn`, `tables.ColorColumn`, `tables.CheckboxColumn`,
`tables.ToggleColumn`, `tables.SelectColumn`, `tables.SelectFilter`,
`tables.TernaryFilter`. (No `tables.Filter` — see the note in "Filters"
above; a custom filter is composed inline, not a registered component.)

## Build gotcha: `useInvueTable` needs `resolve.preserveSymlinks: true` in the consuming app

`useInvueTable` imports `router`/`usePage` from `@inertiajs/vue3` directly.
It's the **only** file in any `invue/*` package that imports a real npm
dependency other than `vue` itself — every `invue/forms` component only
imports `vue` and sibling `invue/*` packages (resolved by
`@invue/vite-plugin`), never a third npm package directly, so this class of
bug never showed up before tables.

This works fine under `vite`/`npm run dev` but **breaks `npm run build`**
with:
```
Rolldown failed to resolve import "@inertiajs/vue3" from
".../packages/tables/resources/js/composables/useInvueTable.js"
```

Why: Composer installs `vendor/invue/tables` as a **symlink** to
`packages/tables` (a real path entirely outside the consuming app, e.g.
outside `sandbox/`). Rolldown's production build resolves bare specifiers
by walking up `node_modules` from a file's *real* (symlink-resolved) path —
so from `packages/tables/resources/js/composables/`, it walks up through
`packages/tables/`, `packages/`, the monorepo root — never through the
consuming app's own `node_modules`, where `@inertiajs/vue3` actually lives
(guaranteed to be there, since every Invue app is necessarily an Inertia
app). `vue` itself doesn't hit this because Vite/plugin-vue alias it to a
single canonical instance regardless of the resolving file's location —
that's a `vue`-specific special case, not a general exemption for bare
imports, and it's *why this gotcha didn't surface until a package needed a
second real npm dependency*.

**Fix — required in every consuming app's `vite.config.js` once it uses
`invue/tables`:**
```js
export default defineConfig({
    resolve: {
        preserveSymlinks: true,
    },
    // ...plugins
});
```
This stops Vite from realpath-ing through the `vendor/invue/*` symlinks
before resolving further bare imports, so resolution walks up from the
symlink's *apparent* location (inside the consuming app) instead of its
real one. Document this in the app's Getting Started right next to the
`@invue/vite-plugin` + `tailwind.content.js` wiring steps — it's the same
"one-line opt-in every consumer needs" shape as those two.

**Rule for future package authors:** the moment any `invue/*` package
imports a real npm dependency other than `vue` (even one guaranteed
present in every consumer, like `@inertiajs/vue3`), this gotcha applies —
don't assume forms' history of "only `vue` + sibling `invue/*` imports
resolve fine" generalizes to other npm packages.

## Per-component docs

`tables/<ComponentName>/README.md` (this same skill directory) will hold
the detailed prop/slot API reference per column and filter, exactly
mirroring `forms/<ComponentName>/README.md` today.
