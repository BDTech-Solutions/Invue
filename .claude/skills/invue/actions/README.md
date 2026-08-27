# invue/actions — architecture & design

The "button that does something" primitive shared across Invue —
`invue/tables`' row/bulk actions are the first real consumer, but this
package doesn't know `tables` exists. Same relationship `invue/core`'s
`<Icon>` has to every other package: a small, generic piece the rest of
the framework composes, not something that only makes sense bolted onto
one feature.

## Why there's no PHP `Action` builder

Every other Invue package keeps a hard line: PHP is data/trigger only,
Vue owns presentation, no PHP UI builder (`Action::make()->icon()->...`
the way Filament does it). Actions are *authored* exactly where every
other piece of Invue UI is authored — as a plain prop object, in the
`.vue` file that needs one:

```js
{
    label: 'Delete',
    icon: 'trash',
    color: 'red',
    url: '/posts/3',
    method: 'delete',
    data: {},                          // extra request body
    visible: true,                     // or a boolean already resolved by the caller
    disabled: false,
    requiresConfirmation: true,
    confirmationTitle: 'Delete this post?',
    confirmationText: '"My Post" will be permanently removed.',
    confirmationButtonLabel: 'Delete',
}
```

What PHP *does* own is the data an action's `visible`/`url` reacts to —
see `Invue\Tables\TableQuery::authorize()` for the concrete pattern
(annotate each row with a `_can` map; the Vue side just reads booleans).
`packages/actions/src/ActionsServiceProvider.php` exists purely for
Laravel package-discovery consistency (every Invue package has one) — it
registers nothing.

## Package layout

```
packages/actions/
  composer.json                        invue/actions, PSR-4 Invue\Actions\
  src/
    ActionsServiceProvider.php          empty on purpose — see above
  resources/js/
    Components/
      ActionButton.vue -> Base/ActionButton.vue         a single button/link
      ActionGroup.vue -> Base/ActionGroup.vue            a "⋯" dropdown of several
      ConfirmationModal.vue -> Base/ConfirmationModal.vue
    composables/
      useInvueAction.js                shared "confirm, then run" logic
    index.js
```

Same Base + resolving-wrapper shape as every other Invue component.
`Base/ActionButton.vue` and `Base/ActionGroup.vue` both import the
**resolved** `../ConfirmationModal.vue`, not `Base/ConfirmationModal.vue`
directly — a registry swap of just `actions.ConfirmationModal` (a
different modal library, a slide-over instead of a centered dialog, ...)
still applies inside both, the same rule `PanelLayout` follows for
`Sidebar`/`Topbar`.

## `useInvueAction()` — the shared "confirm, then act" composable

```js
const { confirming, processing, run, confirm, cancel } = useInvueAction()
```

- `run(action)` — the entry point. If `action.requiresConfirmation`, sets
  `confirming` and returns (the component's template shows a
  `ConfirmationModal` when `confirming` is set); otherwise performs the
  action immediately.
- `confirm()` / `cancel()` — wired to the modal's own `@confirm`/`@cancel`.
- Performing an action: if it has a `url`, does an Inertia
  `router.visit(url, { method, data })`; otherwise calls `action.onClick`
  — this is what lets an `ActionButton` work both as a styled `<Link>`
  (pass `url`) and as a plain confirmable button (pass nothing, listen for
  `@click`, e.g. a client-only `Notify()` call).

**`ActionButton` vs. `ActionGroup`'s `confirming` check is deliberately
different** — `ActionButton` only ever has one possible action (itself),
so its modal opens on `confirming !== null`. Don't "fix" this to compare
against a specific action object; a `computed()`-wrapped action object is
not guaranteed to stay reference-equal to what was stored in `confirming`
across renders (a real bug caught while building this — the reference
check silently never opened the modal). `ActionGroup` has several actions
and needs to remember *which one* is confirming — it stores the whole
resolved action object in `confirming` and reads `confirming?.title`
etc. directly off it, same reasoning, no object-identity comparison needed
either way.

## Components

| Component | Registry key | Notes |
|---|---|---|
| `ActionButton` | `actions.ActionButton` | A solid colored button. Standalone use (a page header's "New Post"), or spread from a descriptor (`v-bind="action"`) — see `BulkActionsBar`. |
| `ActionGroup` | `actions.ActionGroup` | A "⋯" trigger opening a dropdown of menu-item-styled actions — what `tables.ActionsColumn` renders per row. Takes an `actions` array directly, not individual `ActionButton` children (dropdown items need their own lighter style, not a solid pill). |
| `ConfirmationModal` | `actions.ConfirmationModal` | Centered dialog, `open`/`title`/`text`/`confirmLabel`/`cancelLabel`/`color`/`processing` props, `@confirm`/`@cancel` emits. |

Colors (`color` on any action) are one of the shared Invue palette names,
resolved through a static class map inline in each `.vue` file — same
Tailwind content-scanning reasoning as every other color prop in the
framework (see `invue/notifications`' `Toast.vue`).

## What this doesn't attempt yet

- **No PHP-side `Action::make()`.** See "Why there's no PHP `Action`
  builder" above — this is deliberate, not a gap, unless a future package
  needs actions declared from data the *server* controls (e.g. a
  data-driven "list of allowed bulk operations" fetched from config) in a
  way a plain array literal in a `.vue` file can't express.
- **No header/page-level Action *registry*** (Filament's page-header
  actions bar as its own concept) — nothing stops using a bare
  `<ActionButton>` in a page's own markup today; a dedicated
  `PageActions`-style component is a `panels`-level follow-up, not an
  `actions`-level one.
- **No "select all across every page"** for bulk actions — `useInvueTable`
  only tracks selection within the currently loaded page of rows, and
  clears it on any reload (search/sort/filter/page change).

## Verified in `sandbox/`

`Invue/Admin/Posts/Index.vue` (the `make:invue-resource`-generated CRUD
page) uses all of it for real: row actions (Edit always, Delete gated by
`TableQuery::authorize()` so a published post can't be deleted from the
UI at all), a confirmation modal on delete, and bulk delete via
`selectable` + `bulkActions` with a hand-written `destroy-many` route.
Checked with Playwright: dropdown open/close, confirmation modal
open/cancel/confirm, single delete, bulk delete (published rows survive
being selected — the server-side `where('published', false)` guard, not
just the hidden-in-the-UI one), Sidebar badge count updating after each
delete, zero console errors throughout.
