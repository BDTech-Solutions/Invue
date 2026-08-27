import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

// Shared by Base/ActionButton.vue and Base/ActionGroup.vue so "confirm,
// then either visit a URL or run a callback" is written once, not twice.
// `confirming` holds the specific action object mid-confirmation — only
// one action's modal can be open at a time, which is also the right UX
// for an ActionGroup with several actions.
export function useInvueAction() {
    const confirming = ref(null)
    const processing = ref(false)

    function perform(action) {
        if (action.url) {
            processing.value = true
            router.visit(action.url, {
                method: action.method ?? 'get',
                data: action.data ?? {},
                preserveScroll: true,
                onSuccess: () => action.onSuccess?.(),
                onFinish: () => {
                    processing.value = false
                    confirming.value = null
                },
            })

            return
        }

        action.onClick?.()
        confirming.value = null
    }

    function run(action) {
        if (!action || action.disabled) {
            return
        }

        if (action.requiresConfirmation) {
            confirming.value = action

            return
        }

        perform(action)
    }

    function confirm() {
        if (confirming.value) {
            perform(confirming.value)
        }
    }

    function cancel() {
        confirming.value = null
    }

    return { confirming, processing, run, confirm, cancel }
}
