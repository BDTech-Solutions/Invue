# invue/notifications — architecture & design

Same translation exercise as `invue/tables` and `invue/panels`: Filament's
Notification builder (`Notification::make()->title()->body()->icon()->color()->send()`)
is the *feature inspiration*, not a pattern to copy mechanically. The PHP
side stays a plain **data/trigger builder** — it never decides how a toast
looks, only what it says and roughly how it should read (title, body,
icon name, color, duration). Which Vue component actually renders that
data — and how — is a 100% client-side, registry-swappable concern, same
boundary `invue/panels`' `Panel` keeps from `panels.Sidebar`/`panels.Topbar`.

## Scope: toast only, no persisted/database notifications (v1)

Filament has two notification modes: ephemeral `->send()` (flashed for the
next request, auto-dismisses) and persisted `->sendToDatabase($recipient)`
(a `notifications` table, a bell icon with an unread count, mark-as-read).
**Settled explicitly with the project owner: v1 ships only the ephemeral
toast.** Persisted/database notifications are a real, meaningfully larger
follow-up (a migration, a fetch/mark-read endpoint, a bell+dropdown UI) —
not attempted here, same "ship the 80%, document the gap" posture as
`tables/README.md`'s row-actions note.

## Package layout

```
packages/notifications/
  composer.json                        invue/notifications, PSR-4 Invue\Notifications\
  src/
    Notification.php                   fluent builder: title/body/icon/color/duration, ->send(), ::flashed()
    NotificationsServiceProvider.php
  resources/js/
    Components/
      Notifications.vue -> Base/Notifications.vue   the container: mount once, reads the shared prop, owns dismiss timers
      Toast.vue -> Base/Toast.vue                    one notification card
    composables/
      useInvueNotifications.js         feeds server-sent (Inertia prop) notifications into store.js
    store.js                           module-level singleton: the actual items list + dismiss timers, shared by both entry points
    notify.js                          Notify — the client-only mirror of the PHP builder, pushes straight into store.js
    index.js
```

Same Base + resolving-wrapper shape as every other Invue component — copy
it, don't skip the wrapper. `Base/Notifications.vue` imports the **resolved
wrapper** `../Toast.vue` (not `Base/Toast.vue` directly), so a registry
swap of just `notifications.Toast` still applies inside the default
container — the same rule `PanelLayout` follows for `Sidebar`/`Topbar`.

## Customization — two layers (same as `panels.Sidebar`/`panels.Topbar`)

Most store variants won't need a full registry swap — the shipped
`Base/Notifications.vue`/`Base/Toast.vue` cover reskinning through props and
slots, same "layer 1 vs. layer 2" split used for panels:

**`Base/Toast.vue` (per-card reskin):**

| Prop/slot | Purpose |
|---|---|
| `title`/`body`/`icon`/`color`/`iconColor` | Unchanged — the original props. |
| `#icon` (scoped: `icon`, `color`) | Override the icon block entirely (e.g. an avatar circle instead of an `<Icon>`). Defaults to the original icon rendering. |
| `#actions` (scoped: `dismiss`) | Room for action buttons (e.g. "Desfazer") under the title/body. Empty by default — this is what closes the "no actions in v1" gap noted below, without a registry swap. |

**`Base/Notifications.vue` (container reskin):**

| Prop/slot | Purpose |
|---|---|
| `prop` | Unchanged — the shared Inertia prop name. |
| `position` | One of `top-right` (default) / `top-left` / `top-center` / `bottom-right` / `bottom-left` / `bottom-center`. Resolved through a static class map (Tailwind content-scanning rule — see below). Bottom positions stack in reverse so the newest toast still lands next to the anchor, matching how top positions already behaved. |
| `#item` (scoped: `item`, `dismiss`) | Fully override one toast's markup while the container keeps owning the list/positioning/dismiss-timers. Falls back to the resolved `<Toast>`, same `#item` pattern `panels.Sidebar` uses for nav rows. |

Layer 2 (`notifications.Toast`/`notifications.Container` registry swap,
below) is still the answer for a structurally different toast — sound
effects, a completely different stacking/animation model, etc.

## Registry keys — this is the actual point of the package

```js
invue.registry.register('notifications.Toast', MyToastStyle)         // swap just the card design
invue.registry.register('notifications.Container', MyToastPlacement) // or replace positioning/stacking/animation wholesale
```

No PHP-side styling config exists on `Notification` at all — a future
"store" package can ship an entirely different toast look (slide-in from
the bottom, a different card shape, sound effects, whatever) by registering
under these two keys, with zero changes to `packages/notifications` or to
any app code that already calls `Notification::make()->...->send()`.

## The one-shot delivery model — why there's no auto-registered middleware

Unlike `invue/panels`' `ShareInvuePanelData` (auto-pushed onto its own
route group, because the package owns those routes), `Notification::send()`
can be called from **any** controller in the app — the package doesn't own
the routes it fires from, so auto-registering a global middleware would be
the wrong kind of magic. Instead, wire the shared prop by hand, the exact
same way `sandbox/app/Http/Middleware/HandleInertiaRequests.php` already
hand-shares `flash.status`:

```php
use Invue\Notifications\Notification;

public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'notifications' => fn () => Notification::flashed(),
    ];
}
```

`Notification::flashed()` reads (never pulls) the session key notifications
were queued under — Laravel's own flash-data aging handles expiry after one
request, same as `flash.status`'s `session()->get('status')`.

## Mounting `<Notifications />`

Mount it **once**, app-wide, not per-page and not only inside
`invue/panels` layouts — Notifications is deliberately decoupled from
Panels (verified: it renders correctly on top of a `PanelLayout` page with
zero panel-specific wiring). In `app.js`:

```js
import { Notifications } from 'invue/notifications'

createApp({ render: () => [h(App, props), h(Notifications)] })
```

A render function returning an array is a valid Vue 3 root (a fragment) —
this is the whole integration, no slot/prop threading through every layout.

## `Notification` API

```php
use Invue\Notifications\Notification;

Notification::make()
    ->title('Post criado')
    ->body('"Hello Invue" foi salvo.')
    ->success()          // or ->warning() / ->danger() / ->info() / ->color('purple')
    ->send();
```

| Method | Filament equivalent | Notes |
|---|---|---|
| `title(string)` | `->title()` | |
| `body(string)` | `->body()` | |
| `icon(string)` | `->icon()` | An icon **name** (e.g. `'circle-check'`), resolved client-side through `invue/core`'s `<Icon>` registry — see the parent `SKILL.md`'s "Icons" section. Same convention `invue/tables`' `IconColumn` uses. Silently doesn't render if the app hasn't registered that name via `invue.registerIcons({...})`. |
| `color(string)` | `->color()` | One of the shared Invue color names (`gray`/`red`/`green`/`blue`/`yellow`/`amber`/`sky`/`rose`/`purple`/`pink`) — the same palette `TextColumn`/`IconColumn` already use for `color`/`badge`. |
| `iconColor(string)` | — | Overrides just the icon's color; defaults to `color`. |
| `duration(int $ms)` | `->duration()` | Auto-dismiss delay. |
| `persistent()` | `->persistent()` | No auto-dismiss — closed only via the toast's own × button. |
| `success()`/`warning()`/`danger()`/`info()` | same | Shortcuts: set `color` + a default `icon` (never override an `icon()` you already set explicitly). |
| `send()` | `->send()` | Queues onto the session for the next request; stacks with other `send()` calls in the same request/chain instead of clobbering. |
| `flashed()` (static) | — | What you wire into your app's `HandleInertiaRequests::share()`. |

## Client-only notifications (`Notify`)

Added after the PHP builder shipped, without changing it at all — `Notification::make()->...->send()` is untouched, byte-for-byte. `Notify` is a **second, independent entry point** into the exact same render pipeline, for a toast that should never round-trip the server (e.g. reacting to a client-side validation failure, a copy-to-clipboard confirmation, a WebSocket event):

```js
import { Notify } from 'invue/notifications'

Notify.make()
    .title('Copied')
    .success()
    .send();

Notify.dismiss(id); // returned by send() — e.g. to close a persistent() toast early
```

Same fluent shape, same status shortcuts, same icon-*name*/color-*name*
conventions as the PHP builder — learn one, know both. The symmetry is
deliberate; the name isn't `Notification` because that would shadow the
browser's own `Notification` API in any file that imports it.

**How the two entry points share one render pipeline:** `store.js` is a
module-level singleton (`@invue/vite-plugin` resolves `invue/notifications`
to one canonical module per app, so every import shares the same reactive
`items`/timers). `useInvueNotifications` — used internally by
`<Notifications />` — now just watches the Inertia `notifications` prop and
pushes each arrival into that shared store; `Notify.make()...send()` pushes
into the identical store directly, client-side, with zero network request.
Neither entry point knows the other exists; `<Notifications />` doesn't
care which one produced an item.

`/invue-notifications-toast-demo` (3 static `Toast` variants: default,
`#icon` avatar override, `#actions` "Desfazer" button) and
`/invue-notifications-showcase` (live `position` switcher + a fully custom
`#item` glass-card render, driven by `Notify`) exercise the customization
layer above — same demo/showcase split used for `panels.Sidebar`/`Topbar`.

## Verified in `sandbox/`

`/invue-notifications-demo` (`InvueNotificationsDemoController`) exercises
every variant — stacked toasts, persistent + manual close — and the
generated `make:invue-resource Post` controller was hand-edited to fire a
real `success()` notification from `store()`/`update()`/`destroy()`,
proving the package works from actual application code, not just its own
demo. Both checked with Playwright: correct colors/icons, stacking,
auto-dismiss timing, manual dismiss, zero console errors, and the toast
rendering correctly on top of an `invue/panels` page. `Notify` has its own
button on the same demo page — verified it fires with **zero network
requests** and stacks correctly alongside server-sent ones in the same
container.

## V1 scope — what this deliberately doesn't attempt yet

- **No persisted/database notifications** (see above) — no `->sendToDatabase()`,
  no bell icon, no unread count, no mark-as-read endpoint.
- **No PHP-side `->actions([Action::make(...)])` API** (Filament's data-driven
  action buttons declared from the builder). `Base/Toast.vue`'s `#actions`
  slot (see Customization above) covers the *client-side* per-app case —
  wire a button in the page/registry-swapped component — but there's still
  no way to declare an action from `Notification::make()` itself. A real
  follow-up if that data-driven shape turns out to be needed, same posture
  as tables' deferred row actions.
- **No broadcast/real-time delivery** — `send()` is strictly "queued for the
  next request/redirect", not pushed live via Echo/Reverb.
