# invue/infolists — architecture & design

Read-only record display — the "view a Post without opening its edit
form" page. The whole package is three small layout components, because
the value-rendering logic (badges, colors, booleans, dates, money, ...)
already exists: `invue/tables`' column components (`TextColumn`,
`IconColumn`, `ImageColumn`, `ColorColumn`) take a `row` + `field` prop
and format a value — nothing about that is table-specific, they were
already fully usable standalone before this package existed. Infolists
just adds the label/grid/section chrome around them instead of
reinventing formatting.

## No PHP side, on purpose

Unlike `invue/tables`' `TableQuery` (real backend work: search/sort/
filter whitelisting, pagination), a single already-fetched record needs
none of that — `Inertia::render('Posts/Show', ['post' => $post])` is the
entire backend, same as any other Inertia page. `InfolistsServiceProvider`
exists purely for Laravel package-discovery consistency and registers
nothing, matching `invue/actions`.

## Package layout

```
packages/infolists/
  composer.json                        invue/infolists, PSR-4 Invue\Infolists\
  src/
    InfolistsServiceProvider.php       empty — see above
  resources/js/
    Components/
      Infolist.vue -> Base/Infolist.vue   the grid container
      Entry.vue -> Base/Entry.vue         one label/value row
      Section.vue -> Base/Section.vue     titled card chrome (independent of panels.RelationManager — see below)
```

Same Base + resolving-wrapper shape as every other Invue component.

## Composing a page

```vue
<script setup>
import { PanelLayout } from 'invue/panels'
import { Section, Infolist, Entry } from 'invue/infolists'
import { TextColumn, IconColumn } from 'invue/tables'

defineProps({ post: Object })
</script>

<template>
    <PanelLayout>
        <Section title="Post details">
            <Infolist :columns="2">
                <Entry label="Title">
                    <TextColumn :row="post" field="title" weight="medium" />
                </Entry>
                <Entry label="Published">
                    <IconColumn :row="post" field="published" boolean />
                </Entry>
                <Entry label="Body" :span="2">
                    <TextColumn :row="post" field="body" wrap />
                </Entry>
            </Infolist>
        </Section>
    </PanelLayout>
</template>
```

Every column prop already documented in `tables/README.md`'s "Column prop
catalog" works identically here — `badge`, `color`, `money`, `date`,
`since`, `copyable`, `boolean`, image sizing, all of it — because it's
the literal same component, not a re-implementation. An `<Entry>`'s
content doesn't have to be a column at all; plain text/markup is equally
valid slot content (e.g. a derived value with no column equivalent, like
a related-record count).

## Components

| Component | Registry key | Props |
|---|---|---|
| `Infolist` | `infolists.Infolist` | `columns` (Number, default `1`) — a responsive grid (`1`–`4`), resolved through a static class map, same Tailwind content-scanning reasoning as every other numeric/color prop in Invue. |
| `Entry` | `infolists.Entry` | `label` (String, required), `span` (Number, default `1`) — how many of the parent grid's columns this entry occupies (a long text field in a 2-column layout typically wants `span="2"`). |
| `Section` | `infolists.Section` | `title` (String), `description` (String). A titled, bordered card — `#actions` slot for a header-right button. |

`Section` is a separate, independent implementation from
`panels.RelationManager`'s card, not a shared import — despite very
similar visual chrome, `infolists` depending on `panels` would invert the
package graph (`panels` already composes `tables`/`actions`/
`notifications`; nothing should compose back into it from underneath).
The visual similarity is a documented convention (border-gray-200,
rounded-lg, p-4/p-5, `text-xs uppercase text-gray-400` header) to copy if
either card gets a registry swap, not a shared component to extract
today — revisit if a third "titled card" need shows up.

## Auto-generated via `make:invue-resource --view`

`packages/panels`' `MakeResourceCommand` can scaffold a `Show.vue` for you
— `--view`, or answering yes to the interactive prompt it asks otherwise
(default no, matching Filament's own List/Create/Edit-by-default,
View-as-opt-in posture). Gated behind
`class_exists(Invue\Infolists\InfolistsServiceProvider::class)`: `panels`
can't composer-depend on `infolists` (wrong direction — same reasoning as
the `notifications` bridge), so without the package installed the
question is never even asked, `--view` alone just warns and no-ops.

What actually makes `show` reachable: the generated `{Model}Resource`
gets `protected static bool $hasView = true;`, and
`PanelManager::registerRoutes()` reads `Resource::hasView()` to decide
whether to call `->except('show')` on that Resource's `Route::resource()`
— every other Resource still gets it excluded exactly as before. Same
static-metadata pattern `$navigationIcon`/`$navigationGroup` already use,
not a new mechanism.

The generated page is the same shape as the hand-written
`Posts/Show.vue` below: `Section` + `Infolist :columns="2"` + one `Entry`
per inferred field, each wrapping the same table column
(`TextColumn`/`IconColumn`) and kind-matched formatting option the
Index page's `Table` uses for that field (`FieldRenderer::infolistEntry()`,
mirroring `tableColumn()`). Breadcrumb trail is `{Plural} / {Model} #{id}`
— see `invue/panels`' Breadcrumbs section on the Panels doc page.

## What this doesn't attempt yet

- **No relation entries** (Filament's `TextEntry::make('author.name')`-
  style dot-notation into a related model) — `TextColumn`'s `field` prop
  already supports dot notation via `getNestedValue()`, so this likely
  already works for an eager-loaded relation without any change; not
  verified with a dedicated test yet.
- **No collapsible/repeatable sections** (Filament's `RepeatableEntry` for
  a hasMany shown inline, read-only) — `panels.RelationManager` already
  covers the *editable* hasMany case; a read-only equivalent hasn't come
  up as a real need yet.

## Verified in `sandbox/`

`Invue/Admin/Posts/Show.vue` (hand-written, predates `--view` — see
above for the generated equivalent) — a "View" row action added to
`Posts/Index.vue` links here; the page shows title/published/body/created/
comment-count (the last one plain text, not a column — no formatting
needed) in a 2-column `Section` + `Infolist`, with an `Edit` button and a
back link to the index. Checked with Playwright: `ActionsColumn` dropdown
correctly lists View alongside Edit/Delete, navigation both ways, zero
console errors, production `vite build` passes.

`make:invue-resource Gadget --panel=admin --view` against a disposable
model (string/text/boolean/number columns) — verified with Playwright:
`GadgetResource::$hasView` gets set, `show` route actually registers
(`route:list` — absent for a Resource without `--view`, e.g. `posts`'
generic `Route::resource()` set), `/admin/gadgets/{id}` renders the
breadcrumb trail (`Gadgets / Gadget #1`) and every inferred field with
correct values (including the `IconColumn` boolean), and the Edit button
navigates to the right URL. Cleaned up after (disposable model/migration/
generated files removed, not committed — `sandbox/` is gitignored
wholesale anyway).

**Gotcha hit while wiring this up, not a bug in the package itself:**
`@invue-domain/vite-plugin` discovers `vendor/invue/*` packages once, at Vite
dev-server startup (see its own `configResolved` hook) — running
`composer update` to add a brand-new `invue/*` package while `npm run
dev` is already running leaves that already-running Vite process unable
to resolve the new package's bare import (`Failed to resolve import
"invue/infolists"`) until it's restarted. Composer's autoload regenerates
live; Vite's package discovery does not. Restart `npm run dev` after
`composer require`/`composer update` adds a new `invue/*` package.
