# RadioGroup

`invue/forms` — `import { RadioGroup } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/RadioGroup.vue` — default implementation (native `<input type="radio">` list inside a `<fieldset>`)
- `packages/forms/resources/js/Components/RadioGroup.vue` — the resolving wrapper apps actually import
- Registry key: `forms.RadioGroup`

## Props

| Prop         | Type              | Default | Notes                                                        |
|--------------|-------------------|---------|----------------------------------------------------------------|
| `modelValue` | `String, Number`  | `''`    | Use with `v-model`. Matched against each option's `value`.     |
| `options`    | `Array`           | `[]`    | Each entry is either a plain value (`'Pro'`) or a `{ value, label }` object. Plain values use themselves as the label. Same shape as `Select`'s `options`. |
| `label`      | `String`          | `null`  | Rendered as the `<fieldset>`'s `<legend>`. Omit and use `#label` for custom markup. |
| `hint`       | `String`          | `null`  | Helper text below the options. Hidden automatically when `error` is set. |
| `required`   | `Boolean`         | `false` | Only cosmetic — renders a red `*` next to the label. Does not add HTML5 `required` or any validation; validation is server-side via `FormRequest`. |
| `disabled`   | `Boolean`         | `false` | Passed to every `<input type="radio">` in the group.            |
| `error`      | `String`          | `null`  | Validation error message. Turns every radio's border red and hides `hint`. Feed this from `useInvueField(form, name).error`. |

Each instance generates its own random `name` attribute internally so
multiple `RadioGroup`s on the same page never cross-select each other's
options — nothing to configure for that.

## Slots

| Slot      | Purpose                                     |
|-----------|-----------------------------------------------|
| `#label`  | Replace the `<legend>` content.               |
| `#hint`   | Replace the helper text markup.               |
| `#error`  | Replace the error message markup.             |

No per-option slot — if you need custom option markup (icons, descriptions
per option, a card-style picker), that's a registry swap, not a slot.

## Typical usage — wired to Inertia validation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { RadioGroup, useInvueField } from 'invue/forms';

const form = useForm({ plan: '' });
const { modelValue: plan, error: planError } = useInvueField(form, 'plan');
</script>

<template>
    <RadioGroup
        v-model="plan"
        :error="planError"
        :options="['Free', 'Pro', 'Enterprise']"
        label="Plano"
        required
    />
</template>
```

## RadioGroup vs. Select

Both take the same `options` shape and cover the same "pick one of N"
case — use `RadioGroup` when every option should be visible at once (2-5
short options, or when the choice matters enough to not hide it behind a
click), and `Select` when the list is longer or screen space is tight.

## Customizing without forking

Same escalation path as the other fields: props for the common cases, a
full registry swap (`invue.registry.register('forms.RadioGroup', MyCardPicker)`)
for anything past a plain radio list — a card-style visual picker, icons
per option, etc. The replacement must accept at least `modelValue`,
`options`, and `error`, and emit `update:modelValue`, to stay a drop-in.
