import { reactive } from 'vue'

// A module-level singleton — @invue-domain/vite-plugin resolves 'invue/notifications'
// to one canonical module per app, so every import of this file (whether
// from useInvueNotifications' Inertia-prop watcher or from Notify.send())
// shares the exact same `items`/timers. This is what lets a server-sent
// notification (Notification::make()->...->send() in PHP) and a
// client-only one (Notify.make()...send() in JS) render through the
// identical <Notifications /> stack without either one knowing the other
// exists.
const state = reactive({ items: [] })
const timers = new Map()
let counter = 0

function nextId() {
    counter += 1

    return `client-${Date.now()}-${counter}`
}

export function notificationItems() {
    return state.items
}

/**
 * Pushes an already-built notification object (server-sent, with a real id
 * from Notification::toArray(), or client-built via Notify) into the
 * shared list and arms its auto-dismiss timer.
 */
export function pushNotification(notification) {
    const item = { id: notification.id ?? nextId(), ...notification }

    state.items.push(item)

    if (item.duration) {
        timers.set(
            item.id,
            setTimeout(() => dismissNotification(item.id), item.duration),
        )
    }

    return item.id
}

export function dismissNotification(id) {
    const index = state.items.findIndex((item) => item.id === id)

    if (index !== -1) {
        state.items.splice(index, 1)
    }

    const timer = timers.get(id)

    if (timer) {
        clearTimeout(timer)
        timers.delete(id)
    }
}
