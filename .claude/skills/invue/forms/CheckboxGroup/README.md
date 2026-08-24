# CheckboxGroup

`invue/forms` — `import { CheckboxGroup } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/CheckboxGroup.vue` — default implementation (a list of native `<input type="checkbox">` inside a `<fieldset>`)
- `packages/forms/resources/js/Components/CheckboxGroup.vue` — the resolving wrapper apps actually import
- Registry key: `forms.CheckboxGroup`

The first `invue/forms` field with an **array-valued** `modelValue` — every
other field so far is scalar (`String`, `Number`, `Boolean`, or `File`).

## Props

| Prop         | Type              | Default | Notes                                                        |
|--------------|-------------------|---------|----------------------------------------------------------------|
| `modelValue` | `Array`           | `[]`    | Use with `v-model`. Holds the `value` of every checked option. |
| `options`    | `Array`           | `[]`    | Each entry is either a plain value (`'Design'`) or a `{ value, label }` object. Same shape as `Select`/`RadioGroup`. |
| `label`      | `String`          | `null`  | Rendered as the `<fieldset>`'s `<legend>`. Omit and use `#label` for custom markup. |
| `hint`       | `String`          | `null`  | Helper text below the options. Hidden automatically when `error` is set. |
| `required`   | `Boolean`         | `false` | Only cosmetic — renders a red `*` next to the label. Does not add HTML5 `required` or any validation; validation is server-side via `FormRequest`. |
| `disabled`   | `Boolean`         | `false` | Passed to every `<input type="checkbox">` in the group.         |
| `error`      | `String`          | `null`  | Validation error message. Turns every checkbox's border red and hides `hint`. Feed this from `useInvueField(form, name).error`. |

## Slots

| Slot      | Purpose                                     |
|-----------|-----------------------------------------------|
| `#label`  | Replace the `<legend>` content.               |
| `#hint`   | Replace the helper text markup.               |
| `#error`  | Replace the error message markup.             |

No per-option slot — same reasoning as `RadioGroup`: custom option markup
is a registry swap, not a slot.

## Typical usage — wired to Inertia validation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { CheckboxGroup, useInvueField } from 'invue/forms';

const form = useForm({ interests: [] });
const { modelValue: interests, error: interestsError } = useInvueField(form, 'interests');
</script>

<template>
    <CheckboxGroup
        v-model="interests"
        :error="interestsError"
        :options="['Design', 'Dev', 'Marketing']"
        label="Interesses"
        required
    />
</template>
```

Backend, array-shaped validation:

```php
'interests' => ['required', 'array', 'min:1'],
'interests.*' => ['string', 'in:Design,Dev,Marketing'],
```

`useInvueField`'s error lookup only reads `form.errors[name]` (a flat key),
so it surfaces the top-level `interests` rule violations (e.g. "required",
"min:1") correctly, but **not** individual `interests.0`/`interests.1`
per-item errors from the `interests.*` rule — those would need their own
lookup (`form.errors['interests.0']`) if you need to point at a specific
bad item, which this component doesn't attempt.

## CheckboxGroup vs. RadioGroup vs. Select

All three share the same `options` shape. Pick by cardinality:
`CheckboxGroup` for "zero or more of N", `RadioGroup`/`Select` for
"exactly one of N" (`RadioGroup` when every option should be visible at
once, `Select` when the list is longer or space is tight).

## Customizing without forking

Same escalation path as the other fields: props for the common cases, a
full registry swap
(`invue.registry.register('forms.CheckboxGroup', MyTagPicker)`) for
anything past a plain checkbox list — a tag/chip picker, a searchable
multi-select, etc. The replacement must accept at least `modelValue`
(Array), `options`, and `error`, and emit `update:modelValue`, to stay a
drop-in.
