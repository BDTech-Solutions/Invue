import { createRegistry } from './registry'

export const InvueRegistryKey = Symbol('invue-registry')

export function createInvue() {
    const registry = createRegistry()

    return {
        registry,
        install(app) {
            app.provide(InvueRegistryKey, registry)
            app.config.globalProperties.$invue = registry
        },
    }
}
