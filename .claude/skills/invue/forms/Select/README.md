# Select

`invue/forms` — `import { Select } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/Select.vue` — default implementation (native `<select>`)
- `packages/forms/resources/js/Components/Select.vue` — the resolving wrapper apps actually import
- Registry key: `forms.Select`

## Props

| Prop         | Type              | Default | Notes                                                        |
|--------------|-------------------|---------|----------------------------------------------------------------|
| `modelValue` | `String, Number`  | `''`    | Use with `v-model`. Matched against each option's `value`.     |
| `options`    | `Array`           | `[]`    | Each entry is either a plain value (`'Admin'`) or a `{ value, label }` object. Plain values use themselves as the label. |
| `label`      | `String`          | `null`  | Rendered above the select. Omit and use the `#label` slot for custom markup. |
| `hint`       | `String`          | `null`  | Helper text below the select. Hidden automatically when `error` is set. |
| `placeholder`| `String`          | `null`  | Renders a disabled first `<option value="">` with this text — shown when `modelValue` is empty. Not a validation constraint by itself. |
| `required`   | `Boolean`         | `false` | Only cosmetic — renders a red `*` next to the label. Does not add HTML5 `required` or any validation; validation is server-side via `FormRequest`. |
| `disabled`   | `Boolean`         | `false` | Passed straight to the `<select>`.                              |
| `error`      | `String`          | `null`  | Validation error message. Turns the border red and hides `hint`. Feed this from `useInvueField(form, name).error`. |

## Slots

Same set as `TextInput` minus `#prefix`/`#suffix` (a native `<select>`'s
dropdown arrow makes inline decorations impractical — swap the whole
component via the registry instead if you need that).

| Slot      | Purpose                                     |
|-----------|-----------------------------------------------|
| `#label`  | Replace the whole label area.                 |
| `#hint`   | Replace the helper text markup.               |
| `#error`  | Replace the error message markup.             |

## Typical usage — wired to Inertia validation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { Select, useInvueField } from 'invue/forms';

const form = useForm({ role: '' });
const { modelValue: role, error: roleError } = useInvueField(form, 'role');
</script>

<template>
    <Select
        v-model="role"
        :error="roleError"
        :options="['Admin', 'Editor', 'Viewer']"
        label="Funcao"
        placeholder="Selecione uma funcao"
        required
    />
</template>
```

With object options (independent `value`/`label`):

```vue
<Select
    v-model="userId"
    :options="users.map(u => ({ value: u.id, label: u.name }))"
    label="Usuario"
/>
```

## Customizing without forking

Same escalation path as `TextInput`: props for one-off tweaks, `#label`/
`#hint`/`#error` slots for markup overrides, and a full registry swap
(`invue.registry.register('forms.Select', MyCombobox)`) for anything a
native `<select>` can't do — a searchable combobox, multi-select, async
option loading, etc. `MyCombobox` must accept at least `modelValue`,
`options`, `error`, and emit `update:modelValue` to stay a drop-in
replacement, since the wrapper forwards `$attrs` and slots through
unmodified.

## Known limitation

Single-select only, native `<select>` semantics (no search, no multi-value,
no async options). This is deliberate for v1 — see the registry-swap
escape hatch above rather than adding that complexity to the Base
component.
