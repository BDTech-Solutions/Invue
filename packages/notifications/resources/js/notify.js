import { dismissNotification, pushNotification } from './store'

// Client-only mirror of Invue\Notifications\Notification — same fluent
// shape (title/body/icon/color/iconColor/duration/persistent, the same
// success/warning/danger/info shortcuts, the same icon *names*), but
// ->send() pushes straight into the shared store instead of flashing to
// the session. Deliberately named `Notify`, not `Notification` — the
// latter would shadow the browser's own Notification API in any file
// that imports it. The PHP builder is untouched; this is a second,
// independent entry point into the exact same render pipeline.
const STATUS_DEFAULTS = {
    success: { color: 'green', icon: 'circle-check' },
    warning: { color: 'amber', icon: 'triangle-alert' },
    danger: { color: 'red', icon: 'circle-x' },
    info: { color: 'sky', icon: 'info' },
}

class NotifyBuilder {
    constructor() {
        this.data = {
            title: '',
            body: null,
            icon: null,
            color: 'gray',
            iconColor: null,
            duration: 5000,
        }
    }

    title(title) {
        this.data.title = title

        return this
    }

    body(body) {
        this.data.body = body

        return this
    }

    icon(icon) {
        this.data.icon = icon

        return this
    }

    color(color) {
        this.data.color = color

        return this
    }

    iconColor(color) {
        this.data.iconColor = color

        return this
    }

    duration(milliseconds) {
        this.data.duration = milliseconds

        return this
    }

    persistent() {
        this.data.duration = null

        return this
    }

    success() {
        return this.#status('success')
    }

    warning() {
        return this.#status('warning')
    }

    danger() {
        return this.#status('danger')
    }

    info() {
        return this.#status('info')
    }

    #status(kind) {
        const defaults = STATUS_DEFAULTS[kind]

        this.data.color = defaults.color
        this.data.icon = this.data.icon ?? defaults.icon

        return this
    }

    /**
     * @returns {string} the notification's id — pass it to Notify.dismiss()
     *                    to remove it early (e.g. after an async task
     *                    finishes and a `persistent()` toast is no longer
     *                    needed).
     */
    send() {
        return pushNotification(this.data)
    }
}

export const Notify = {
    make: () => new NotifyBuilder(),
    dismiss: dismissNotification,
}
