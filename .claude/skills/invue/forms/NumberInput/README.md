# NumberInput

`invue/forms` — `import { NumberInput } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/NumberInput.vue` — default implementation (native `<input type="number">`)
- `packages/forms/resources/js/Components/NumberInput.vue` — the resolving wrapper apps actually import
- Registry key: `forms.NumberInput`

## Props

| Prop         | Type              | Default | Notes                                                        |
|--------------|-------------------|---------|----------------------------------------------------------------|
| `modelValue` | `Number, String`  | `''`    | Use with `v-model`. `''` represents "empty"; any non-empty value is a real JS `Number`, never a numeric string — see below. |
| `label`      | `String`          | `null`  | Rendered above the input. Omit and use `#label` for custom markup. |
| `hint`       | `String`          | `null`  | Helper text below the input. Hidden automatically when `error` is set. |
| `min`        | `Number, String`  | `null`  | Native `min` attribute — see the `novalidate` warning below.   |
| `max`        | `Number, String`  | `null`  | Native `max` attribute — see the `novalidate` warning below.   |
| `step`       | `Number, String`  | `null`  | Native `step` attribute (controls the spinner arrows' increment). |
| `required`   | `Boolean`         | `false` | Only cosmetic — renders a red `*` next to the label. Does not add an HTML5 `required` attribute or any validation; validation is server-side via `FormRequest`. |
| `disabled`   | `Boolean`         | `false` | Passed straight to the `<input>`.                               |
| `error`      | `String`          | `null`  | Validation error message. Turns the border red and hides `hint`. Feed this from `useInvueField(form, name).error`. |

## Type coercion — read before assuming the emitted value's type

A native `<input type="number">`'s `event.target.value` is **always a
string**, even though the browser only lets the user type valid numbers.
`Base/NumberInput.vue` coerces it before emitting:

```js
function onInput(event) {
    const raw = event.target.value
    emit('update:modelValue', raw === '' ? '' : Number(raw))
}
```

So `modelValue` is either `''` (empty) or a real `Number` — never a numeric
string like `"25"`. This matters for validation rules downstream (e.g. if
you were ever tempted to check `form.age.length`, that won't exist on a
Number) and for anything serializing the form manually.

## ⚠️ `min`/`max` trigger real browser validation — your `<form>` needs `novalidate`

Unlike `required` (cosmetic-only in every `invue/forms` field), `min` and
`max` here **are** rendered as real native HTML attributes, because that's
what makes the spinner arrows and keyboard up/down clamp correctly. But
native `min`/`max` attributes are also HTML5 **constraint validation** —
the browser blocks form submission before your `@submit` handler (and
therefore before Inertia, and therefore before your `FormRequest`) ever
runs, showing its own unstyled validation bubble instead of this
component's `error` state.

This contradicts every other field's promise that "validation is
server-side" — a form with a `NumberInput` silently stops working (no
network request, no console error, nothing) unless the `<form>` element
has `novalidate`:

```vue
<form novalidate @submit.prevent="submit">
```

This bit us once already — see the sandbox demo (`resources/js/Pages/InvueDemo.vue`)
for the fix in place. **Any app using `invue/forms` should add `novalidate`
to its forms as a matter of course**, not just ones using `NumberInput` —
it's the only way to guarantee the framework's server-authoritative
validation model actually holds for every field, present and future.

## Typical usage — wired to Inertia validation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { NumberInput, useInvueField } from 'invue/forms';

const form = useForm({ age: '' });
const { modelValue: age, error: ageError } = useInvueField(form, 'age');
</script>

<template>
    <form novalidate @submit.prevent="...">
        <NumberInput v-model="age" :error="ageError" label="Idade" min="18" max="120" required />
    </form>
</template>
```

Backend: `'age' => ['required', 'integer', 'min:18', 'max:120']`.

## Customizing without forking

Same escalation path as the other fields: `min`/`max`/`step` props for the
common cases, a full registry swap
(`invue.registry.register('forms.NumberInput', MyStepper)`) for anything
past a plain native input — a stepper with visible +/- buttons, a
currency/formatted-number input, etc. The replacement must accept at least
`modelValue` and `error`, and emit `update:modelValue`, to stay a drop-in.
