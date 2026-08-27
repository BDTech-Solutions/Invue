---
name: invue
description: Context and conventions for the Invue framework monorepo (bdtech-solutions/invue) — a Filament alternative for Laravel built on Inertia.js + Vue 3 + Tailwind instead of the TALL stack. Load this before adding/editing anything under packages/, sandbox/, or the root composer.json/package.json — it covers the monorepo layout, the vendor/-resolution distribution model, the component registry pattern, the useInvueField gotcha, the Tailwind content-scanning gotcha, the native-HTML5-validation-vs-novalidate gotcha, the preserveSymlinks/vendor-npm-import build gotcha, and how to test changes in the sandbox app. Per-component API docs live in forms/<ComponentName>/. invue/tables (Filament Table Builder ideas translated to Invue's no-PHP-builder/no-Livewire philosophy) has its own full design spec in tables/README.md. invue/panels (Filament Panel/Resource CRUD scaffolding — make:invue-panel / make:invue-resource — translated the same way, plus the registry-swappable sidebar/topbar shell) has its own design spec in panels/README.md. invue/notifications (Filament's ephemeral toast Notification builder, registry-swappable Toast/Container, no persisted/database notifications in v1) has its own design spec in notifications/README.md.
---

# Invue

Invue is a native-Vue alternative to FilamentPHP: screens are written as real
`.vue` single-file components (Composition API, `<script setup>`) driven by
Inertia.js, not built via a PHP fluent API. There is deliberately **no PHP UI
builder** — validation is plain Laravel `FormRequest`, and field
customization happens in Vue (props/slots), not PHP method chains.

## Monorepo layout

```
composer.json / package.json   root: path-repo + npm workspace glue for packages/*
packages/
  core/           invue/core     — shared runtime: component registry + Vue plugin
  forms/          invue/forms    — form field components (TextInput, ...)
  tables/         invue/tables   — data tables (see tables/README.md for the full design/prop reference)
  vite-plugin/    @invue-domain/vite-plugin — resolves `invue/{pkg}` imports from vendor/
sandbox/          throwaway Laravel 13 + Breeze (Inertia+Vue+Tailwind) app used
                  to manually/browser-test the packages end-to-end. Not shipped.
```

Each `packages/<name>` that has a PHP side is a real Composer package
(`invue/<name>`), with `src/` (PSR-4 `Invue\<Name>\`) and `resources/js/`
(the package's own Vue components). `packages/vite-plugin` is npm-only (no
PHP side, no `composer.json`) since it's tooling, not a Laravel package.

Composer version conventions already settled: `illuminate/*` and
`inertiajs/inertia-laravel` target Laravel 12/13 (`^12.0|^13.0`,
`^2.0|^3.0`); sibling `invue/*` deps use `self.version` (they release in
lockstep); the root/sandbox composer.json path-repos them in via `@dev`.

## Distribution model — READ BEFORE importing anything cross-package

Vue components are **not** published to npm and **not** copied via
`vendor:publish`. A Composer package ships its raw `.vue`/`.js` source in
`resources/js/`, and `@invue-domain/vite-plugin` resolves the bare specifier
`invue/{package}` (and subpaths) straight from `vendor/invue/{package}/resources/js`
at build time — no publish step, edits to the package are picked up
immediately by anything requiring it via a path repo.

- `import { createInvue } from 'invue/core'` → `vendor/invue/core/resources/js/index.js`
- `import { TextInput } from 'invue/forms'` → `vendor/invue/forms/resources/js/index.js`
- A consuming app's `vite.config.js` must include the `invue()` plugin from `@invue-domain/vite-plugin`.
- This only works through the plugin. Plain relative imports within the same
  package (e.g. `./Base/TextInput.vue`) are normal Vite resolution and need
  nothing special.

**Known gotcha (fixed once, keep it fixed): Tailwind never scans `vendor/`.**
Tailwind v3 does NOT merge `content` contributed by a preset — only
`theme.extend` and `plugins` merge; `content` is fully overridden by the
project's own config. So a Tailwind *preset* cannot inject the
`vendor/invue/**/*.vue` glob automatically. The real fix: `invue/core` ships
`tailwind.content.js`, a plain array export, which every consuming app must
manually spread into its own `content`:

```js
import invueContent from './vendor/invue/core/tailwind.content.js';
export default {
    content: ['./resources/**/*.vue', ...invueContent],
};
```

Skipping this doesn't error — it silently purges every Tailwind class used
inside any `invue/*` component. If a component's styling looks unstyled or
wrong in a consuming app, check this first.

## Known gotcha: `vendor/` symlinks break `vite build` for any package that imports a real npm dependency

`vite`/`npm run dev` and `npm run build` resolve bare imports differently
for files reached through `vendor/invue/*`, which Composer installs as
**symlinks** to `packages/*`. Dev mode tolerates it; Rolldown's production
build follows the symlink to its real path first, then resolves further
bare imports by walking up `node_modules` from *that* real location — which
is outside the consuming app entirely, so it never finds a dependency that
only exists in the app's own `node_modules`.

This didn't matter for `invue/forms` (every component only imports `vue` —
aliased to a single instance regardless of resolving path, a `vue`-specific
exemption — and sibling `invue/*` packages via `@invue-domain/vite-plugin`). It
first hit `invue/tables`, whose `useInvueTable` composable imports
`router`/`usePage` from `@inertiajs/vue3` directly: `npm run build` failed
with `Rolldown failed to resolve import "@inertiajs/vue3"` even though
`npm run dev` worked fine. Fix, required in the consuming app's
`vite.config.js` the moment any `invue/*` package imports a real npm
dependency beyond `vue`:

```js
export default defineConfig({
    resolve: { preserveSymlinks: true },
    // ...plugins
});
```

Full writeup, including why `vue` itself doesn't need this: `tables/README.md`'s
"Build gotcha" section. Document this alongside the `tailwind.content.js`
step in any app's Getting Started — same "one-line opt-in every consumer
needs" shape.

## Known gotcha: native HTML5 validation silently fights server-side validation

Every field's docs say "validation is server-side via `FormRequest`", and
most props that look like validation (`required`) are deliberately
cosmetic-only for exactly that reason. But a few native HTML attributes
*are* real browser constraint validation — `TextInput`'s `min`/`max` props
(only meaningful with `type="number"`) being the current example (needed
for the spinner arrows to clamp correctly) — and those silently **block
form submission before your `@submit` handler ever runs**: no network
request, no console error, no visible sign anything happened except the
browser's own unstyled validation bubble. This cost real debugging time
once already (see git log around the "Add NumberInput" and the later
TextInput/NumberInput merge commits).

**Fix: put `novalidate` on every `<form>` that uses `invue/forms` fields.**
This isn't optional per-component — it's the only way to guarantee the
framework's server-authoritative validation model actually holds, for
every field, present and future (a future `DatePicker` with a native
`min`/`max` date, or anything ever using `type="email"`/`type="url"`,
would hit the identical trap otherwise).

```vue
<form novalidate @submit.prevent="submit">
```

## Component registry (`invue/core`)

`createInvue()` returns a Vue plugin exposing a `registry` with
`register(key, component)` / `resolve(key, fallback)`. This is how a
consuming app swaps a field's implementation globally, in one call, without
touching every usage site — the Invue equivalent of Filament's component
swapping:

```js
import { createInvue } from 'invue/core';
const invue = createInvue();
invue.registry.register('forms.TextInput', MyCustomTextInput);
app.use(invue);
```

## Icons (`invue/core`'s `<Icon>`)

`icon`/`trueIcon`/`falseIcon` props across `invue/tables` (`IconColumn`)
and `invue/notifications` (`Notification`/`Toast`) are **names**, not
literal glyphs — resolved client-side through `invue/core`'s `<Icon name="...">`.
`invue/core` does **not** bundle or import any icon library itself. The
consuming app statically imports whichever icons it actually uses (from
Lucide, `@lucide/vue` — ISC-licensed, fully free — or anything else) and
registers them once:

```js
import { createInvue } from 'invue/core'
import { Check, CircleCheck, X } from '@lucide/vue'

const invue = createInvue()
invue.registerIcons({ check: Check, 'circle-check': CircleCheck, x: X })
```

This is deliberate, not an oversight: Lucide's own docs explicitly
recommend *against* a built-in "resolve any icon name by string" component
in production, because it imports every icon into the build regardless of
which ones are actually used — see their [Dynamic Icon Component
docs](https://lucide.dev/guide/react/advanced/dynamic-icon-component).
Explicit per-icon registration keeps the bundle to exactly what's imported,
and isn't tied to Lucide specifically — any icon set (or hand-rolled SVG
components) can register under the same `icons.<name>` key.

`Icon.vue` is the **one exception** to the Base + resolving-wrapper rule
below — there's no single default implementation to swap, only an N-entry
name→component lookup the registry already provides directly. In dev mode
it `console.warn`s once per unregistered name actually rendered, instead of
failing silently.

## The forms component pattern

Every field in `invue/forms` follows the same two-file shape:

- `Components/Base/<Name>.vue` — the concrete default implementation (props,
  slots, markup).
- `Components/<Name>.vue` — a thin **resolving wrapper**: injects the
  registry, calls `registry.resolve('forms.<Name>', BaseComponent)`, renders
  the result via `<component :is="...">`, forwards `v-bind="$attrs"` and all
  slots through. This is the file re-exported from `resources/js/index.js`
  and the one app code actually imports.

When adding a new field, copy this shape — don't skip the wrapper, it's the
whole mechanism the registry override depends on.

**Before adding a new field file, check whether it's really a new native
element.** `TextInput` briefly had a sibling `NumberInput` that was just
`<input type="number">` with identical label/hint/error markup — merged
back into `TextInput` as a `type` prop once that redundancy was obvious,
matching how Filament uses one `TextInput` class with `->numeric()`/
`->email()`/`->password()` modifiers rather than separate field classes.
The rule: same native element, differs only by attribute/behavior → one
component with a prop (`type="email"`, `type="tel"`, `type="url"` all
belong on `TextInput`, not new files). Genuinely different native element
or interaction model (`Textarea`, `Select`, `Checkbox`/`CheckboxGroup`,
`RadioGroup`, `FileUpload`, `Repeater`, `TagsInput`, `ToggleButtons`) → its
own component. (`ToggleButtons` has the identical `options`/single-value
model as `RadioGroup`, but earned its own component anyway because the
markup is plain `<button>`s, not native `<input type="radio">` — same
data shape isn't automatically the same rule as same native element.)

**Every Base component's root element is a wrapping `<div class="invue-form-field">`
— an undeclared prop silently lands there, not on the actual `<input>`/`<textarea>`/`<select>`.**
This is plain Vue single-root attrs inheritance, not an Invue-specific
mechanism, but it bit us once: `Base/TextInput.vue` didn't declare
`placeholder`, so `<TextInput placeholder="...">` rendered a `placeholder`
attribute on the outer `<div>` (harmless there) while the actual `<input>`
silently had none — no error, no console warning, the prop just went to
the wrong element. Found only because `Repeater`'s test used a placeholder
and the input visibly had none. **When a field doesn't behave as expected
and no error explains why, check whether the prop was actually declared —
`$attrs` fallthrough hides the mistake instead of erroring.**

**A field can be a composition of other fields instead of raw markup.**
`Base/KeyValue.vue` isn't a new primitive — it's a `Repeater` whose row
template (two `TextInput`s) is built in, saving the caller from writing
that template themselves every time. It imports the **public wrapper**
versions (`../Repeater.vue`, `../TextInput.vue`), not their `Base`
components, specifically so a global registry swap of `forms.Repeater` or
`forms.TextInput` also applies inside every `KeyValue` row — composing
from the resolved public component, not the raw default, is what makes
that inheritance work. Reach for this shape before writing new raw markup
whenever a field is really "an existing field/pattern with a fixed
row/shape" (a date-range picker built from two `TextInput type="date"`,
say) rather than a genuinely new interaction.

## `useInvueField(form, name)` — destructure it, don't nest it

`packages/forms/resources/js/composables/useInvueField.js` returns
`{ modelValue, error }` as `computed()` refs, meant to bind an Inertia
`useForm()` field to a component.

**Gotcha that already bit us once:** Vue only auto-unwraps refs that are
*top-level* template bindings. A ref nested inside a plain returned object
does NOT auto-unwrap — `v-model="field.modelValue"` renders the literal
string `"[object Object]"` instead of the field's value.

```js
// Wrong — renders "[object Object]"
const field = useInvueField(form, 'name');
// <TextInput v-model="field.modelValue" :error="field.error" />

// Right — destructure at the call site
const { modelValue: name, error: nameError } = useInvueField(form, 'name');
// <TextInput v-model="name" :error="nameError" />
```

## Testing changes: use `sandbox/`

`sandbox/` is a real Laravel 13 + Breeze (Vue stack) app wired to the
monorepo packages via Composer path repositories and a local `file:`
install of `@invue-domain/vite-plugin`. It exists specifically so changes to
`packages/*` can be verified end-to-end instead of trusted on faith. Login:
`test@example.com` / `password` (seed with `php artisan db:seed` if the
sqlite db is empty — `create-project` runs migrations but not seeders).

To run it:
```bash
cd sandbox
php artisan serve --port=8971 &
npm run dev -- --port 5173 &
```
Demo route: `/invue-demo` (`resources/js/Pages/InvueDemo.vue`), backed by
`InvueDemoRequest` (`name` required, min 3) — a working example of the
`useInvueField` + validation-error round trip.

**Prefer browser-driven testing over curl/build-only checks.** A `vite build`
or an Inertia JSON `curl` round-trip proves modules resolve and validation
plumbing works, but every real bug found in this project so far (the
`v-model` unwrap bug, the CSS-specificity border bug, the Tailwind
content-scanning bug, the native-validation-blocks-submit bug) was only
visible by actually rendering the page — and, for the last one, actually
clicking submit and checking whether a request fired at all — in a
browser. This environment has no `chromium-cli`; Playwright + a locally
downloaded Chromium (`npx playwright install chromium`, no `--with-deps`,
since that needs interactive `sudo`) works fine as a fallback — see git log
around the "Fix v-model unwrapping..." commit for the driver script shape
(nav → fill → submit → screenshot → check `console --errors` equivalent).

After a manual test session, kill both dev servers by port
(`lsof -ti:PORT -sTCP:LISTEN | xargs -r kill`) rather than a broad `pkill`,
and `rm -rf sandbox/node_modules/.vite` if you changed `tailwind.config.js`
or `vite.config.js` and see stale output.

## The tables package (`invue/tables`)

Same translation exercise as forms: Filament's Table Builder
(`TextColumn::make('name')->searchable()->sortable()->badge()` on a
Livewire component, every interaction re-running the query server-side) is
the *feature inspiration*, never the delivery mechanism. Invue's version
keeps the one-word-toggle ergonomics (`searchable`, `sortable`, `badge` as
plain booleans) but as **props on `<Column>` children of a `<Table>`
component**, and keeps Filament's *always-refetch-from-the-server* model
but via **Inertia partial reloads** (`router.reload({ only: [...] })`)
instead of Livewire — never client-side-only filtering of an
already-fetched page of rows, that silently breaks past page one.

Read **`tables/README.md`** in full before writing anything under
`packages/tables/` — it has the full column/filter prop catalog translated
from Filament, the `useInvueTable` composable contract (the tables
equivalent of `useInvueField`, with its own destructuring-style gotcha to
avoid), the `TableQuery` backend helper (a query-shaping helper, not a UI
builder — same boundary as `FormRequest` validation being separate from a
field's Vue props), and an explicit v1-scope list (row/bulk actions,
grouping, reordering, and summaries are real Filament features intentionally
deferred past the first cut, not overlooked).

## Git commits in this repo

This user does not want AI-attribution trailers (`Co-Authored-By: Claude...`)
in commits — the repo's author identity is just `daniel`. Commit normally,
without adding that trailer, unless told otherwise.

## The panels package (`invue/panels`)

Filament's Panel + Resource concept (an admin area hosting per-model
CRUD screens), translated the same way tables and forms were: Filament is
the feature inspiration, never the delivery mechanism. `make:invue-resource`
doesn't build a runtime PHP `form()`/`table()` definition — it generates
real, editable Controller/FormRequest/`.vue` files wired to `invue/tables`
and `invue/forms`, because Invue has no PHP UI builder, on principle.

The deliberate difference from Filament: the entire panel shell (sidebar,
topbar) is swappable by a consuming app — or a future third-party "store"
package — with one `invue.registry.register('panels.Sidebar', ...)` call,
no vendor fork required. Read **`panels/README.md`** in full before
touching `packages/panels/` — it covers the `Panel`/`PanelProvider`/
`PanelManager`/`Resource` split, the directory-convention resource
discovery (no explicit route registration needed), and exactly how
`make:invue-resource` infers fields from an already-migrated table's real
columns via `Schema::getColumns()`.

## The notifications package (`invue/notifications`)

Filament's ephemeral `Notification::make()->title()->body()->icon()->color()->send()`,
translated the same way: PHP stays a plain data/trigger builder (never
markup), `->send()` flashes onto the session for the next request, and the
consuming app hand-wires `'notifications' => fn () => Notification::flashed()`
into its own `HandleInertiaRequests::share()` — no auto-registered global
middleware, since `send()` can be called from any controller, not just
routes the package owns. `<Notifications />` mounts once, app-wide, in
`app.js` (a fragment root render, not per-layout). Registry keys
`notifications.Toast`/`notifications.Container` are the actual point of the
package — a future "store" swaps toast styles with zero app changes.
`Notify.make()...send()` (from `invue/notifications`) is a client-only
mirror of the same builder — same shortcuts, same icon/color conventions —
that pushes straight into the shared `store.js` singleton instead of the
session, with zero server round-trip; the PHP `Notification` class is
untouched by this, it's a second entry point into the same render
pipeline. No persisted/database notifications (bell icon, unread count) in
v1 — see **`notifications/README.md`** in full before touching
`packages/notifications/`.

## Per-component docs

`forms/<ComponentName>/` holds the API reference for each shipped form
field (props, slots, registry key, usage examples). Check there before
assuming a component's prop surface — don't guess from the source alone if
a doc exists. `tables/README.md` is the equivalent design/API reference doc
for `invue/tables`; per-component `tables/<ComponentName>/` docs (mirroring
`forms/<ComponentName>/` today) are the next step once the doc site catches
up.
