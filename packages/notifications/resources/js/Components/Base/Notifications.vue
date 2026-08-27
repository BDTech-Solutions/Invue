<script setup>
// Composes the *resolved* Toast (the wrapper one directory up), not its
// Base implementation directly — so a registry swap of just
// `notifications.Toast` still applies here, same rule PanelLayout follows
// for Sidebar/Topbar and KeyValue follows for Repeater/TextInput.
import Toast from '../Toast.vue'
import { useInvueNotifications } from '../../composables/useInvueNotifications'

// Static per-position class maps — same reasoning as panels.Sidebar's
// WIDTH_CLASSES: Tailwind only scans literal class text already present in
// vendor/invue/**/*.vue, so `position` can't resolve through a
// runtime-built string.
const POSITION_CLASSES = {
    'top-right': 'inset-x-4 top-4 sm:inset-x-auto sm:right-4 sm:w-96',
    'top-left': 'inset-x-4 top-4 sm:inset-x-auto sm:left-4 sm:w-96',
    'top-center': 'inset-x-4 top-4 sm:inset-x-auto sm:left-1/2 sm:w-96 sm:-translate-x-1/2',
    'bottom-right': 'inset-x-4 bottom-4 sm:inset-x-auto sm:right-4 sm:w-96',
    'bottom-left': 'inset-x-4 bottom-4 sm:inset-x-auto sm:left-4 sm:w-96',
    'bottom-center': 'inset-x-4 bottom-4 sm:inset-x-auto sm:left-1/2 sm:w-96 sm:-translate-x-1/2',
}

// Toasts stack away from the edge they anchor to — bottom positions grow
// upward so the newest toast still lands next to the screen edge.
const STACK_CLASSES = {
    'top-right': 'flex-col',
    'top-left': 'flex-col',
    'top-center': 'flex-col',
    'bottom-right': 'flex-col-reverse',
    'bottom-left': 'flex-col-reverse',
    'bottom-center': 'flex-col-reverse',
}

const props = defineProps({
    // Matches the prop name the consuming app shares from its own
    // HandleInertiaRequests::share() — see Notification::flashed() in the
    // PHP package. Override if the app shared it under a different key.
    prop: {
        type: String,
        default: 'notifications',
    },
    position: {
        type: String,
        default: 'top-right',
    },
})

const { items, dismiss } = useInvueNotifications(props.prop)
</script>

<template>
    <div
        class="pointer-events-none fixed z-50 flex flex-col items-stretch gap-2"
        :class="POSITION_CLASSES[position] ?? POSITION_CLASSES['top-right']"
    >
        <TransitionGroup
            name="invue-notification"
            tag="div"
            class="flex gap-2"
            :class="STACK_CLASSES[position] ?? STACK_CLASSES['top-right']"
        >
            <!-- Reskin layer: fully override a single toast's markup while
                 the container keeps owning the list/positioning/timers.
                 Default falls back to the resolved <Toast>, same #item
                 pattern panels.Sidebar uses for nav rows. -->
            <slot
                v-for="item in items"
                :key="item.id"
                name="item"
                :item="item"
                :dismiss="() => dismiss(item.id)"
            >
                <Toast
                    :title="item.title"
                    :body="item.body"
                    :icon="item.icon"
                    :color="item.color"
                    :icon-color="item.iconColor"
                    @close="dismiss(item.id)"
                />
            </slot>
        </TransitionGroup>
    </div>
</template>

<style>
.invue-notification-enter-active,
.invue-notification-leave-active {
    transition: all 0.2s ease;
}

.invue-notification-enter-from,
.invue-notification-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
