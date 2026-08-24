import { computed } from 'vue'

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
