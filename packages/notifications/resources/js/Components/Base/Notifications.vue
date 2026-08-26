<script setup>
// Composes the *resolved* Toast (the wrapper one directory up), not its
// Base implementation directly — so a registry swap of just
// `notifications.Toast` still applies here, same rule PanelLayout follows
// for Sidebar/Topbar and KeyValue follows for Repeater/TextInput.
import Toast from '../Toast.vue'
import { useInvueNotifications } from '../../composables/useInvueNotifications'

const props = defineProps({
    // Matches the prop name the consuming app shares from its own
    // HandleInertiaRequests::share() — see Notification::flashed() in the
    // PHP package. Override if the app shared it under a different key.
    prop: {
        type: String,
        default: 'notifications',
    },
})

const { items, dismiss } = useInvueNotifications(props.prop)
</script>

<template>
    <div class="pointer-events-none fixed inset-x-4 top-4 z-50 flex flex-col items-stretch gap-2 sm:inset-x-auto sm:right-4 sm:w-96">
        <TransitionGroup name="invue-notification" tag="div" class="flex flex-col gap-2">
            <Toast
                v-for="item in items"
                :key="item.id"
                :title="item.title"
                :body="item.body"
                :icon="item.icon"
                :color="item.color"
                :icon-color="item.iconColor"
                @close="dismiss(item.id)"
            />
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
