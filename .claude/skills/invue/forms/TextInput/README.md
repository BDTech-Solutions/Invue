# TextInput

`invue/forms` — `import { TextInput } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/TextInput.vue` — default implementation
- `packages/forms/resources/js/Components/TextInput.vue` — the resolving wrapper apps actually import
- Registry key: `forms.TextInput`

## Props

| Prop         | Type      | Default | Notes                                                        |
|--------------|-----------|---------|----------------------------------------------------------------|
| `modelValue` | `String`  | `''`    | Use with `v-model`.                                            |
| `label`      | `String`  | `null`  | Rendered above the input. Omit and use the `#label` slot for custom markup. |
| `hint`       | `String`  | `null`  | Helper text below the input. Hidden automatically when `error` is set. |
| `prefix`     | `String`  | `null`  | Short text pinned inside the input's left edge (e.g. `"R$"`).  |
| `suffix`     | `String`  | `null`  | Short text pinned inside the input's right edge (e.g. `"kg"`). |
| `required`   | `Boolean` | `false` | Only cosmetic — renders a red `*` next to the label. Does not add HTML5 `required` or any validation; validation is server-side via `FormRequest`. |
| `disabled`   | `Boolean` | `false` | Passed straight to the `<input>`.                               |
| `error`      | `String`  | `null`  | Validation error message. Turns the border red and hides `hint`. Feed this from `useInvueField(form, name).error` — see below. |

## Slots

All slots fall back to the corresponding prop's plain text if not provided.

| Slot      | Purpose                                                        |
|-----------|-----------------------------------------------------------------|
| `#label`  | Replace the whole label area (including the required-`*` if you want it). |
| `#prefix` | Replace the left inline decoration — put an icon component here instead of text. |
| `#suffix` | Replace the right inline decoration.                            |
| `#hint`   | Replace the helper text markup.                                 |
| `#error`  | Replace the error message markup.                               |

Passing a prop (e.g. `prefix="R$"`) or providing the matching named slot
both work — the slot wins if both are given, since the prop is only the
slot's fallback content.

## Typical usage — wired to Inertia validation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { TextInput, useInvueField } from 'invue/forms';

const form = useForm({ name: '' });
// Destructure — see the parent SKILL.md's useInvueField gotcha.
const { modelValue: name, error: nameError } = useInvueField(form, 'name');
</script>

<template>
    <TextInput v-model="name" :error="nameError" label="Nome" required />
</template>
```

## Customizing without forking

Cheapest → most invasive:

1. **Props** — `hint`, `prefix`, `suffix` cover most one-off tweaks.
2. **Slots** — `#prefix`/`#suffix` etc. accept full markup/components, not just strings.
3. **Global swap via the registry** — replace the implementation everywhere
   `<TextInput>` is used, without touching call sites:
   ```js
   import { createInvue } from 'invue/core';
   const invue = createInvue();
   invue.registry.register('forms.TextInput', MyTextInput);
   app.use(invue);
   ```
   `MyTextInput` must accept the same prop/slot contract above (or a
   superset) since the wrapper forwards `$attrs` and all slots through
   unmodified.

## Known styling gotcha

Any Tailwind class added to `Base/TextInput.vue` (or a new field's Base
component) needs the consuming app's `tailwind.config.js` to include
`invue/core`'s `tailwind.content.js` glob — see the parent skill's Tailwind
section. Without it, new classes silently don't render, same as the
`border-red-400` bug this component already hit once.
