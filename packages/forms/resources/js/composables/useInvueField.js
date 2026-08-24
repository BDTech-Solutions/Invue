import { computed } from 'vue'

// Destructure the return value at the call site (`const { modelValue, error } = ...`) —
// Vue only auto-unwraps refs that are top-level template bindings, not ones nested
// inside a plain returned object (e.g. `field.modelValue` would render "[object Object]").
export function useInvueField(form, name) {
    return {
        modelValue: computed({
            get: () => form[name],
            set: (value) => {
                form[name] = value
            },
        }),
        error: computed(() => form.errors?.[name] ?? null),
    }
}
