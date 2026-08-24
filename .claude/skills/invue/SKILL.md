---
name: invue
description: Context and conventions for the Invue framework monorepo (bdtech-solutions/invue) — a Filament alternative for Laravel built on Inertia.js + Vue 3 + Tailwind instead of the TALL stack. Load this before adding/editing anything under packages/, sandbox/, or the root composer.json/package.json — it covers the monorepo layout, the vendor/-resolution distribution model, the component registry pattern, the useInvueField gotcha, the Tailwind content-scanning gotcha, the native-HTML5-validation-vs-novalidate gotcha, and how to test changes in the sandbox app. Per-component API docs live in forms/<ComponentName>/.
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
  vite-plugin/    @invue/vite-plugin — resolves `invue/{pkg}` imports from vendor/
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
`resources/js/`, and `@invue/vite-plugin` resolves the bare specifier
`invue/{package}` (and subpaths) straight from `vendor/invue/{package}/resources/js`
at build time — no publish step, edits to the package are picked up
immediately by anything requiring it via a path repo.

- `import { createInvue } from 'invue/core'` → `vendor/invue/core/resources/js/index.js`
- `import { TextInput } from 'invue/forms'` → `vendor/invue/forms/resources/js/index.js`
- A consuming app's `vite.config.js` must include the `invue()` plugin from `@invue/vite-plugin`.
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

## Known gotcha: native HTML5 validation silently fights server-side validation

Every field's docs say "validation is server-side via `FormRequest`", and
most props that look like validation (`required`) are deliberately
cosmetic-only for exactly that reason. But a few native HTML attributes
*are* real browser constraint validation — `NumberInput`'s `min`/`max`
being the current example (needed for the spinner arrows to clamp
correctly) — and those silently **block form submission before your
`@submit` handler ever runs**: no network request, no console error, no
visible sign anything happened except the browser's own unstyled
validation bubble. This cost real debugging time once already (see git log
around the NumberInput commit).

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
install of `@invue/vite-plugin`. It exists specifically so changes to
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

## Git commits in this repo

This user does not want AI-attribution trailers (`Co-Authored-By: Claude...`)
in commits — the repo's author identity is just `daniel`. Commit normally,
without adding that trailer, unless told otherwise.

## Per-component docs

`forms/<ComponentName>/` holds the API reference for each shipped form
field (props, slots, registry key, usage examples). Check there before
assuming a component's prop surface — don't guess from the source alone if
a doc exists.
