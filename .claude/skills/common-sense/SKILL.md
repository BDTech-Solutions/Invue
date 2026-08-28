---
name: common-sense
description: The standing product philosophy behind Invue — MUST be loaded before any work on invue:install, make:invue-panel, make:invue-resource, Panel/Sidebar/Topbar defaults, or anything else that shapes what a developer gets right after installing Invue. Always applicable, not task-specific — load this first, every time, before designing or judging any "what should the default behavior be" question anywhere in this monorepo.
---

# Invue is a plug-and-play admin panel, from the very first command

Invue exists to reach feature parity with Filament. That comparison is not
decorative — it is the literal bar every default behavior gets judged
against. **If Filament gives you something for free after its standard
install flow, Invue must give you the equivalent for free too.** Never
design a "you'll need to wire this up yourself" step where Filament
wouldn't require one.

## What "plug and play" concretely means

After a user runs the standard flow —

```
composer require invue/invue:^1.1
npm install @invue-domain/vite-plugin
php artisan invue:install     # answering yes to every prompt
php artisan make:invue-panel
```

— they must already have, with zero manual file-editing:

- A real, working **panel** registered and routable.
- A real, working **login page**, styled, functional, backed by a real
  test user whose credentials are printed to the console.
- A **Dashboard** page that actually exists and is what login lands on —
  empty of business content (no real data exists yet, that's expected),
  but the *page itself* is not missing, not a 404, and not the framework's
  own default splash screen.
- A real **Sidebar**, showing real navigation — at minimum linking back to
  the Dashboard itself, not an empty nav with nothing in it.
- A real **Topbar**, showing the actual application name (from `.env` /
  `config('app.name')`, never a hardcoded framework brand name) and a
  user-identity affordance (an avatar/initials), the same baseline
  chrome Filament's own topbar ships by default.
- Styling that is **complete**, not merely present — every field, button,
  and layout element in this default shell must look finished (correct
  padding, borders, spacing) without depending on an undocumented
  external Tailwind plugin the user was never told to install.

None of the above is business-specific. None of it requires knowing the
user's actual domain model. All of it is pure framework-shell
responsibility — which is exactly why it belongs in the zero-config
default path, not in documentation telling the user to build it by hand.

## The test to apply before shipping any "default behavior" decision

Ask, concretely: **would a fresh Filament install already give the
developer this, without them asking for it?**

- Yes → Invue's equivalent command/component must default to it too. Do
  not gate it behind an extra flag, an extra prompt, or a "see the docs"
  pointer, unless Filament itself gates the equivalent the same way.
- No (it's genuinely business/domain-specific — real resources, the
  user's actual nav structure, their actual field choices) → that's
  correctly left to `make:invue-resource` and hand-editing. Don't
  overreach into inventing business content Invue can't know.

The failure mode this skill exists to prevent is landing on the *first*
plausible-sounding boundary ("this runs from `invue/core`, which can't
depend on `invue/panels`, so I won't wire the dashboard into a real panel
shell") and treating it as final, when a very small amount of additional
work — runtime `class_exists()` detection instead of a hard composer
dependency, an explicit standalone-mode prop instead of assuming shared
Inertia state — closes the gap completely and correctly. Every gap listed
above was closed exactly that way already; do not reintroduce a version of
one of them by taking the same first-plausible-boundary shortcut on a
similar future decision.

## Load-bearing mechanisms already built to satisfy this (don't rebuild, extend)

- **`php artisan invue:install`** (`packages/core/src/Console/Commands/InstallCommand.php`)
  is the single entry point that chains: bootstrap Inertia + Vue (if
  missing) → bootstrap a minimal auth system + Dashboard page + `/dashboard`
  route (if missing) → wire the Vite plugin, Vue plugin, and Tailwind
  content glob. Every step is gated behind an interactive confirm
  (default yes) and is a strict no-op under `--no-interaction`/CI — never
  silently mutate a project outside an interactive terminal.
- **Runtime capability detection, not a hard composer dependency**: when a
  generated file's *ideal* shape depends on a package this command's own
  package (`invue/core`) architecturally cannot require (`invue/forms`,
  `invue/panels`), check `class_exists(SomeOtherPackage\ServiceProvider::class)`
  at runtime and branch — the richer version when it's there, a plain
  fallback when it isn't. Used for: the login page's fields
  (`invue/forms` `TextInput`/`Checkbox` vs. plain `<input>`), and the
  Dashboard's chrome (`invue/panels` `Sidebar`+`Topbar` vs. a bare page).
- **Explicit standalone-mode props over relying on shared Inertia state**:
  a route outside any real `Panel`'s route group never receives
  `invuePanel.navigation`/`brandName` — so the generated Dashboard passes
  `Sidebar` an explicit `items` array (`Sidebar`'s own documented
  standalone escape hatch) instead of composing the bare `PanelLayout`,
  which would have silently rendered an empty nav.
- **`Panel::getBrandName()`** defaults to `config('app.name')`, not the
  panel id. **`Topbar.vue`**'s client-side brand fallback reads
  `import.meta.env.VITE_APP_NAME`, not a hardcoded string — both exist
  specifically so a fresh panel shows the real app name without anyone
  calling `->brandName()`.
- **`HandleInertiaRequests::share()`**, as generated by `invue:install`,
  shares `'auth' => ['user' => $request->user()]` by default — the same
  convention Breeze/Jetstream use — which is what lets `Topbar.vue` show
  a real user-initials avatar with no extra wiring.
- **Every `invue/forms` field component** (`TextInput`, `Textarea`,
  `Select`, `Checkbox`, `RadioGroup`, `CheckboxGroup`) carries its own
  explicit padding/border/sizing utility classes — never rely on
  `@tailwindcss/forms` or any other undocumented external plugin to make
  a field look finished.
- **Cross-package UI bridging goes through the registry, never a direct
  import** — `invue/panels` can't composer-depend on `invue/notifications`
  (same wrong-direction problem as forms), so `Topbar.vue`'s notification
  bell isn't imported there at all: it resolves
  `registry.resolve('panels.topbarBell', null)`, and `invue:install`'s
  generated app entry is what calls
  `invue.registry.register('panels.topbarBell', Bell)` — only when
  `class_exists(NotificationsServiceProvider::class)`. The bell (like the
  Dashboard's icon) needs its own icon registered too (`bell`), not just
  the component. The brand (app name + logo) lives in `Sidebar`, not
  `Topbar` — matches Filament's own layout — via `Topbar`'s `showBrand`
  prop, which `PanelLayout` sets `false` by default; `Topbar`'s own
  `brandName`/`badge`/`color` props stay valid for standalone use outside
  a Sidebar. `Topbar`'s user avatar is a real `<details>` dropdown with a
  "Log out" action (`POST` to `logoutUrl`, default `/logout`) — not just a
  static initials badge — so `invue:install`'s generated Dashboard no
  longer needs its own hand-rolled logout button, and neither does any
  `make:invue-panel`-generated page, since they all compose `PanelLayout`.
- **`invue/core`'s `Icon.vue` renders nothing for an unregistered name** —
  by design, no icon library is bundled. Any icon name a generator emits
  on its own (right now: `'layout-dashboard'`, from
  `PanelManager::navigationFor()`'s synthetic Dashboard nav entry) has to
  be registered by default too, or it silently never appears. `invue:install`
  installs `@lucide/vue` and wires
  `invue.registerIcons({ 'layout-dashboard': LayoutDashboard })` into the
  generated/patched app entry for exactly this reason — if a future
  generator starts emitting a new icon name (e.g. an action-column
  `pencil`/`trash`), add it to that same default registration, don't leave
  it to the user to discover the gap.

## When you catch a gap against this bar

Fix it the same day, in the same session, without waiting to be asked a
second time — this mirrors the standing rule already recorded for keeping
`Invue-Lib` and `Invue-Docs` in sync (see that project memory if unsure
how far "fix it now" extends). A user pointing out "why doesn't X just
work already" is this skill's test being applied *by them* instead of by
you — treat it as a signal to re-run the test yourself on every adjacent
default before considering the fix complete, not just the one spot they
happened to notice.
