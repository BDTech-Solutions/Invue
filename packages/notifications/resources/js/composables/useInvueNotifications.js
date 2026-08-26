import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { dismissNotification, notificationItems, pushNotification } from '../store'

// The tables/forms equivalent of useInvueTable/useInvueField: the one place
// that turns the raw Inertia prop (Notification::flashed(), a plain array —
// same "thin server contract" idea as TableQuery's paginate() shape) into
// everything a Notifications container needs. `items`/dismiss timers
// actually live in ../store.js, a module-level singleton shared with
// Notify (see notify.js) — this composable's only job is feeding
// server-sent notifications into that same shared store, so a
// Notification::make()->...->send() from PHP and a client-only
// Notify.make()...send() render through the identical pipeline.
export function useInvueNotifications(propName = 'notifications') {
    const page = usePage()

    // Fires once for the initial page load, then again only when a *new*
    // Inertia response actually carries a fresh `notifications` array
    // (Inertia leaves props excluded from a partial reload's `only` list
    // untouched, so this doesn't re-fire — and re-fire — for every
    // unrelated partial reload on the page).
    watch(
        () => page.props[propName],
        (incoming) => {
            for (const notification of incoming ?? []) {
                pushNotification(notification)
            }
        },
        { immediate: true },
    )

    return { items: notificationItems(), dismiss: dismissNotification }
}
