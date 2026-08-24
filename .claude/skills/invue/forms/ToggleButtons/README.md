# ToggleButtons

`invue/forms` — `import { ToggleButtons } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/ToggleButtons.vue` — default implementation (a row of `<button>` elements acting as a single-select)
- `packages/forms/resources/js/Components/ToggleButtons.vue` — the resolving wrapper apps actually import
- Registry key: `forms.ToggleButtons`

Same data model and `options` shape as `RadioGroup`/`Select` — "exactly
one of N" — but rendered as clickable buttons instead of radio circles or
a dropdown. Kept as its own component (not a `RadioGroup` registry swap)
because the underlying markup and interaction genuinely differ: plain
`<button type="button">` elements with a `@click` handler, not native
`<input type="radio">` elements with native radio-group keyboard/form
semantics.

## Props

| Prop         | Type              | Default | Notes                                                        |
|--------------|-------------------|---------|----------------------------------------------------------------|
| `modelValue` | `String, Number`  | `''`    | Use with `v-model`. Matched against each option's `value`.     |
| `options`    | `Array`           | `[]`    | Each entry is either a plain value (`'Gold'`) or a `{ value, label }` object. Same shape as `Select`/`RadioGroup`/`CheckboxGroup`. |
| `label`      | `String`          | `null`  | Rendered as the `<fieldset>`'s `<legend>`. Omit and use `#label` for custom markup. |
| `hint`       | `String`          | `null`  | Helper text below the buttons. Hidden automatically when `error` is set. |
| `required`   | `Boolean`         | `false` | Only cosmetic — renders a red `*` next to the label. No validation; that's server-side. |
| `disabled`   | `Boolean`         | `false` | Disables every button in the group.                             |
| `error`      | `String`          | `null`  | Validation error message. Outlines unselected buttons red and hides `hint`. Feed this from `useInvueField(form, name).error`. |

Buttons are individually pill-shaped with a gap between them, not a hard
connected segmented control — simpler to get right across button counts
and label lengths than border-collapsing a true segmented strip.

## Slots

| Slot      | Purpose                                     |
|-----------|-----------------------------------------------|
| `#label`  | Replace the `<legend>` content.               |
| `#hint`   | Replace the helper text markup.               |
| `#error`  | Replace the error message markup.             |

No per-option slot — same reasoning as `RadioGroup`/`CheckboxGroup`.

## Typical usage — wired to Inertia validation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { ToggleButtons, useInvueField } from 'invue/forms';

const form = useForm({ tier: '' });
const { modelValue: tier, error: tierError } = useInvueField(form, 'tier');
</script>

<template>
    <ToggleButtons v-model="tier" :error="tierError" :options="['Bronze', 'Silver', 'Gold']" label="Tier" required />
</template>
```

## ToggleButtons vs. RadioGroup vs. Select

All three share the same `options` shape and "exactly one of N" model.
`ToggleButtons` reads as more prominent/visual — reach for it when the
choice deserves emphasis (a plan tier, a priority level) rather than a
plain settings field; `RadioGroup` for a conventional labeled-options
list; `Select` when the list is long or space is tight.

## Customizing without forking

Same escalation path as the other fields: props for the common cases, a
full registry swap
(`invue.registry.register('forms.ToggleButtons', MySegmentedControl)`) for
a true connected segmented strip, icons per option, etc. The replacement
must accept at least `modelValue`, `options`, and `error`, and emit
`update:modelValue`, to stay a drop-in.
