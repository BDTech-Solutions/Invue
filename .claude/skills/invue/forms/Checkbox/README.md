# Checkbox

`invue/forms` — `import { Checkbox } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/Checkbox.vue` — default implementation (native `<input type="checkbox">`, styled via `@tailwindcss/forms`)
- `packages/forms/resources/js/Components/Checkbox.vue` — the resolving wrapper apps actually import
- Registry key: `forms.Checkbox`

## Props

| Prop         | Type      | Default | Notes                                                        |
|--------------|-----------|---------|----------------------------------------------------------------|
| `modelValue` | `Boolean` | `false` | Use with `v-model`.                                             |
| `label`      | `String`  | `null`  | Rendered **beside** the checkbox (not above it, unlike `TextInput`/`Select`). Omit and use `#label` for custom markup. |
| `hint`       | `String`  | `null`  | Helper text below the row. Hidden automatically when `error` is set. |
| `required`   | `Boolean` | `false` | Only cosmetic — renders a red `*` next to the label. Does not add HTML5 `required` or any validation; validation is server-side (e.g. Laravel's `accepted` rule). |
| `disabled`   | `Boolean` | `false` | Passed straight to the `<input>`.                               |
| `error`      | `String`  | `null`  | Validation error message. Turns the checkbox border red and hides `hint`. Feed this from `useInvueField(form, name).error`. |

## Slots

| Slot      | Purpose                                     |
|-----------|-----------------------------------------------|
| `#label`  | Replace the label text next to the checkbox.  |
| `#hint`   | Replace the helper text markup.               |
| `#error`  | Replace the error message markup.             |

No `#prefix`/`#suffix` — doesn't apply to a checkbox.

## Typical usage — "accept terms" wired to Inertia validation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { Checkbox, useInvueField } from 'invue/forms';

const form = useForm({ terms: false });
const { modelValue: terms, error: termsError } = useInvueField(form, 'terms');
</script>

<template>
    <Checkbox v-model="terms" :error="termsError" label="Aceito os termos" required />
</template>
```

Backend (`FormRequest`):

```php
public function rules(): array
{
    return ['terms' => ['accepted']];
}
```

Laravel's `accepted` rule matches Inertia's JSON-serialized booleans directly
(`true`/`false`) — no string coercion (`'on'`/`'1'`) needed on either side.

## Customizing without forking

Same escalation path as the other fields: `label`/`hint`/`error` props and
slots for the common cases, a full registry swap
(`invue.registry.register('forms.Checkbox', MyToggle)`) for anything past
that — e.g. a toggle-switch visual instead of a checkbox square. `MyToggle`
must accept `modelValue` (Boolean) and emit `update:modelValue` to stay a
drop-in replacement.
