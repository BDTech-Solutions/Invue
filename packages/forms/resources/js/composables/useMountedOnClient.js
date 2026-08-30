import { onMounted, ref } from 'vue'

// Gates a <Teleport> so it renders nothing during SSR and hydration's
// first pass, only mounting once the client is definitely past
// hydration. Needed because a Teleport's SSR output doesn't reconcile
// cleanly against Inertia's SSR pipeline — a real "Hydration completed
// but contains mismatches" warning, first caught building invue/actions
// against an SSR-enabled consuming app (Invue-Docs). The teleported
// content here (Select's dropdown panel) is always closed on first
// render anyway, so skipping it server-side changes nothing visually —
// it just avoids asking Vue's SSR renderer to reconcile a teleport
// target it can't reliably match against the client.
export function useMountedOnClient() {
    const isMounted = ref(false)

    onMounted(() => {
        isMounted.value = true
    })

    return isMounted
}
