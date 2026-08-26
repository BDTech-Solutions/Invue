# invue/panels — architecture & design

Same translation exercise `invue/tables` did for Filament's Table Builder,
now applied to Filament's **Panel** + **Resource** concept: an admin area
(routes, navigation, layout shell) hosting one CRUD screen set per Eloquent
model. Filament is the *feature inspiration*, not a pattern to copy —
`make:invue-resource` doesn't build a runtime-interpreted PHP `form()`/
`table()` definition the way `make:filament-resource` does. It generates
**real, editable files** — a Controller, a FormRequest, and three `.vue`
pages wired to `invue/tables`/`invue/forms` — because Invue has no PHP UI
builder, on principle, same as forms and tables.

The one deliberate difference from Filament called out when this was
designed: in Filament, replacing something as fundamental as the sidebar
means forking the vendor package. In Invue, the **entire panel shell**
(sidebar, topbar, the layout that composes them) is swappable by any
consuming app — or a future third-party "store" package — with **one
`invue.registry.register()` call**, no fork, no vendor edit. That's the
actual point of this package, not an incidental nice-to-have.

## Package layout

```
packages/panels/
  composer.json                        invue/panels, PSR-4 Invue\Panels\
  src/
    Panel.php                          fluent per-panel config (path, middleware, brand, directory/namespace conventions)
    PanelProvider.php                  abstract base a consuming app extends once per admin area
    PanelManager.php                   singleton: registered panels, resource discovery, route registration
    Resource.php                       abstract base for generated {Model}Resource metadata classes — no form()/table()
    Http/Middleware/ShareInvuePanelData.php   shares the one `invuePanel` Inertia prop a Sidebar/Topbar needs
    Console/Commands/MakePanelCommand.php     `make:invue-panel`
    Console/Commands/MakeResourceCommand.php  `make:invue-resource`
    Console/Support/ColumnInference.php       reads Schema::getColumns(), infers a field "kind" per column
    Console/Support/FieldRenderer.php         kind -> Vue field/column markup + validation rule
  stubs/                               panel-provider, resource, controller, request, index.vue, create.vue, edit.vue
  resources/js/
    Components/
      PanelLayout.vue -> Base/PanelLayout.vue   composes the *resolved* Sidebar + Topbar around a slot
      Sidebar.vue -> Base/Sidebar.vue           reads `invuePanel.navigation`, Inertia <Link>s, active-path highlight, per-item icon via invue/core's <Icon>
      Topbar.vue -> Base/Topbar.vue             brand name/logo + a slot for app-specific content
```

Same Base + resolving-wrapper shape as every `forms`/`tables` component —
copy it, don't skip the wrapper (see the parent `SKILL.md`'s "Component
registry" section). `Base/PanelLayout.vue` imports the wrapper versions of
`Sidebar`/`Topbar` (not their `Base` implementations), so a registry swap
of just `panels.Sidebar` still applies inside the default layout — the
same "compose from the resolved public component" rule `KeyValue` follows
for `Repeater`/`TextInput`.

## Registry keys

`panels.Layout`, `panels.Sidebar`, `panels.Topbar`. No PHP-side
sidebar/topbar configuration exists on `Panel` at all — component swapping
is a 100% client-side concern:

```js
invue.registry.register('panels.Sidebar', MyCustomSidebar)   // swap just the sidebar
invue.registry.register('panels.Layout', MyCustomShell)      // or replace the whole shell wholesale
```

Verified end-to-end in `sandbox/`: registering a throwaway `panels.Sidebar`
in `app.js` replaced the rendered sidebar with zero changes to
`packages/panels` itself.

## Sidebar customization — two layers

There are deliberately two different ways to customize the sidebar, and
they solve different problems:

1. **Prop/slot customization of the default `Base/Sidebar.vue`** — for a
   consuming app (or a "store" package) that wants the built-in look with
   different colors/width/placement, not a rewrite. No registry call
   needed, just pass props/slots through `<PanelLayout>` → `<Sidebar>`
   (the resolving wrapper forwards `$attrs` and every named/scoped slot
   generically, so this works with zero changes to the wrapper).
2. **A full `panels.Sidebar` registry swap** — for a structurally different
   sidebar (different DOM shape, different framework for grouping/nesting,
   a rail instead of a panel, ...). This is the "N sidebars in the store"
   case and needs its own component from scratch, same as before.

`Base/Sidebar.vue` props:

| Prop | Type | Default | Purpose |
|---|---|---|---|
| `items` | `Array` | `null` | Overrides `page.props.invuePanel.navigation` — lets a Sidebar render (preview, test, reuse) without a real Inertia panel page behind it. Each item: `{ url, label, icon?, group? }`. |
| `selectedColor` | `String` | `'green'` | Active nav item's background/text color — one of the shared Invue color names (`gray`/`red`/`green`/`blue`/`yellow`/`amber`/`sky`/`rose`/`purple`/`pink`, same palette `invue/notifications`' `Toast` `color` prop uses). Resolved through a static class map in the component, **not** an arbitrary CSS color — Tailwind only scans literal class strings already present in `vendor/invue/**/*.vue`, so a color name outside this list silently falls back to `green` instead of erroring. |
| `width` | `String` | `'md'` | `'sm'` (`w-48`) / `'md'` (`w-56`) / `'lg'` (`w-64`). Same static-map reasoning as `selectedColor` — not an arbitrary Tailwind width. |

`Base/Sidebar.vue` slots:

| Slot | Scope | Purpose |
|---|---|---|
| `#header` | — | Content above the nav list (e.g. a workspace switcher). Omitted entirely (no wrapper `<div>`/border) when unused. |
| `#footer` | — | Content below the nav list — this is where a "profile tab at the bottom" variant hangs its `<UserMenu>` without forking the component, e.g. `<Sidebar><template #footer><UserMenu /></template></Sidebar>`. Also omitted entirely when unused. |
| `#item` | `{ item, active }` | Overrides a single row's markup (default: an icon + label `<Link>`, highlighted via `selectedColor` when `active`). Use this for badges, nested items, or any per-row markup the default can't express — still one `panels.Sidebar` registry entry away from a full rewrite if the row-level slot isn't enough. |

Icons are already fully data-driven and need no prop: each `item.icon` is a
*name* (e.g. `'file-text'`), resolved the same way as everywhere else in
Invue — through `invue/core`'s `<Icon>` registry (see the parent
`SKILL.md`'s "Icons" section). Nothing sidebar-specific to configure there
beyond registering the icon once, app-wide.

A genuinely different placement — say, the profile control living in the
sidebar's `#header` instead of `#footer`, or beside the nav items instead
of above/below them — is exactly what the two customization layers above
are for: `#header` vs. `#footer` covers the common case with zero new
code, and a full `panels.Sidebar` swap covers anything structurally
different (e.g. a rail that doesn't stack header/nav/footer vertically at
all).

## Panels vs. Resources

- A **Panel** (`Panel::make('admin')->path('admin')->middleware([...])`) is
  one admin area: a URL prefix, middleware, branding, and a set of
  directory/namespace conventions (resources, controllers, requests, Vue
  pages — all derived from the panel id, all individually overridable). A
  consuming app declares one `PanelProvider` subclass per panel, registered
  in `bootstrap/providers.php` — `make:invue-panel Admin` scaffolds this.
- A **Resource** (`{Model}Resource extends Invue\Panels\Resource`) is
  metadata for one model inside a panel: which model, its nav label/icon/
  group, and the (overridable) convention for finding its controller.
  `$navigationIcon` is an **icon name** (e.g. `'file-text'`), resolved
  client-side through `invue/core`'s `<Icon>` registry — see the parent
  `SKILL.md`'s "Icons" section; it silently doesn't render if the app
  hasn't registered that name. **No `form()`/`table()` methods** —
  field/column layout lives in the generated `.vue` pages, real files, not
  runtime PHP config.

## Resource discovery — directory convention, not explicit registration

Settled explicitly with the project owner over the alternative (an explicit
`->resources([...])` list in the PanelProvider): `PanelManager::discoverResources()`
globs every `*Resource.php` under the panel's `getResourcesDirectory()`
(default `app/Invue/{PanelId}/Resources`), builds each FQCN from
`getResourcesNamespace()`, and requires it to `extend Resource` — a
mismatch throws immediately naming the bad file, never a silent skip. This
means **no route wiring step** after `make:invue-resource` — the next
request just picks the new Resource up. Routes are registered per
discovered Resource as `Route::resource($resource::getSlug(), $resource::getControllerClass($panel))->except('show')`,
grouped under the panel's `path()`/`middleware()`/route-name-prefix, plus
`ShareInvuePanelData` in the middleware stack.

## `make:invue-resource` — schema-inferred, not a stub

Also settled explicitly: given the model's table is already migrated,
generate a **working** Table + Form from its real columns, not a minimal
stub to hand-fill (closer to `make:filament-resource --generate` than the
bare default). `ColumnInference::forTable()` reads `Schema::getColumns()`
(Laravel 11+, no doctrine/dbal) and skips, deliberately:

- the primary key, `created_at`/`updated_at`/`deleted_at`, `remember_token`
- any `*_id` column (relations need their own UI story — not in v1)
- any column whose name contains `password` (hashing needs to be wired
  into the generated controller's store/update — not guessed at here)

Every remaining column becomes a `FieldDescriptor` with a `kind`
(`boolean`/`text`/`date`/`datetime`/`number`/`email`/`string`), inferred
from the column's native DB type plus a couple of name-based overrides
(`is_`/`has_`/`can_`-prefixed or `active`/`published`/`enabled`/`verified`/
`featured` named `tinyint` columns count as `boolean`; anything containing
`email` gets the email kind). `FieldRenderer` turns each kind into one
`invue/tables` column, one `invue/forms` field, and one validation rule —
see `Console/Support/FieldRenderer.php` for the exact mapping if a kind
needs a new case. **This inference is a best-effort starting point, not a
guarantee** — eyeball the generated Table/Form after running the command,
the same way you'd eyeball any other `php artisan make:*` output.

Generated per resource, into the panel's configured directories:

```
{panel}/Resources/{Model}Resource.php
{panel}/Http/Controllers/{Model}Controller.php   index (via Invue\Tables\TableQuery, same pattern as sandbox's InvueTablesDemoController) / create / store / edit / update / destroy
{panel}/Http/Requests/{Model}Request.php
{panel}/Pages/{ModelPlural}/{Index,Create,Edit}.vue
```

`make:invue-resource {Name} --panel=admin --model=App\Models\Other --force`
— `--panel` defaults to the sole registered panel if there's no ambiguity;
`--model` only needed when the resource name differs from the model class;
`--force` overwrites files that already exist (refused by default, same as
Laravel's own generators).

## Verified in `sandbox/`

`make:invue-panel Admin` + `make:invue-resource Post --panel=admin` (a
`posts` table with `title`/`body`/`published`) produces a fully working
`/admin/posts` CRUD — list/search/create/edit/delete round-tripped in a
real browser (Playwright), zero console errors, zero hand-written route or
Vue code. This is the reference to re-run if `packages/panels` changes.

## V1 scope — what this deliberately doesn't attempt yet

Same "ship the common 80%, leave an explicit gap" posture as
`tables/README.md`'s own list:

**Not in v1** — real follow-ups, not oversights: relation fields (`*_id`
columns, belongsTo/hasMany pickers), password/auth fields (hashing needs
controller wiring, not just a form field), a `show`/view page (routes are
registered `->except('show')`), soft-delete-aware `destroy`, bulk/row
actions beyond the single per-row "Edit" link, multi-panel navigation
grouping in the default `Sidebar`, and a `make:invue-panel`/
`make:invue-resource` "undo" (regenerating with `--force` overwrites; there's
no scaffolding removal command).
