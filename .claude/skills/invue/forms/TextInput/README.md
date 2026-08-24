# TextInput

`invue/forms` — `import { TextInput } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/TextInput.vue` — default implementation
- `packages/forms/resources/js/Components/TextInput.vue` — the resolving wrapper apps actually import
- Registry key: `forms.TextInput`

## Props

| Prop         | Type              | Default | Notes                                                        |
|--------------|-------------------|---------|----------------------------------------------------------------|
| `modelValue` | `String, Number`  | `''`    | Use with `v-model`. A `String` for every `type` except `"number"` — see the type-coercion section below. |
| `type`       | `String`          | `'text'`| Any native `<input>` type that's just a variant of the same element: `'text'`, `'number'`, `'email'`, `'password'`, `'tel'`, `'url'`, etc. Not its own component — see "Why type is a prop" below. |
| `label`      | `String`          | `null`  | Rendered above the input. Omit and use the `#label` slot for custom markup. |
| `hint`       | `String`          | `null`  | Helper text below the input. Hidden automatically when `error` is set. |
| `placeholder`| `String`          | `null`  | Native `placeholder` attribute.                                  |
| `prefix`     | `String`          | `null`  | Short text pinned inside the input's left edge (e.g. `"R$"`).  |
| `suffix`     | `String`          | `null`  | Short text pinned inside the input's right edge (e.g. `"kg"`). |
| `min`        | `Number, String`  | `null`  | Native `min` attribute. Only meaningful for `type="number"` (ignored by the browser otherwise) — see the `novalidate` warning below. |
| `max`        | `Number, String`  | `null`  | Native `max` attribute. Same caveat as `min`.                   |
| `step`       | `Number, String`  | `null`  | Native `step` attribute (controls the spinner arrows' increment on `type="number"`). |
| `required`   | `Boolean`         | `false` | Only cosmetic — renders a red `*` next to the label. Does not add HTML5 `required` or any validation; validation is server-side via `FormRequest`. |
| `disabled`   | `Boolean`         | `false` | Passed straight to the `<input>`.                               |
| `error`      | `String`          | `null`  | Validation error message. Turns the border red and hides `hint`. Feed this from `useInvueField(form, name).error` — see below. |

## Why `type` is a prop, not its own component

Filament uses one `TextInput` field with modifiers (`->numeric()`,
`->email()`, `->password()`) rather than separate field classes, because
`text`/`number`/`email`/`password`/`tel`/`url` all render through the exact
same native `<input>` element — only the `type` attribute (and, for
`number`, the value's JS type) differs. `invue/forms` mirrors that: this
component briefly existed split into `TextInput` + a separate
`NumberInput`, and was merged back into one component with a `type` prop
once that redundancy became obvious (identical markup, identical
label/hint/error handling, duplicated in two files for no real reason).

The dividing line for *this* framework's components: if two fields render
through the same native element and differ only in attributes/behavior,
they're one component with a prop. If they render through genuinely
different native elements or interaction models (`Textarea`, `Select`,
`Checkbox`/`CheckboxGroup`, `RadioGroup`, `FileUpload`), they stay separate
components. Keep this in mind before adding `EmailInput`/`PasswordInput`/
`UrlInput` as new files — they almost certainly want to be
`<TextInput type="email">` etc. instead.

## Type coercion for `type="number"`

A native `<input type="number">`'s `event.target.value` is **always a
string**, even though the browser only lets the user type valid numbers.
This component coerces it before emitting, but only for `type="number"`:

```js
function onInput(event) {
    const raw = event.target.value
    if (props.type === 'number') {
        emit('update:modelValue', raw === '' ? '' : Number(raw))
        return
    }
    emit('update:modelValue', raw)
}
```

So with `type="number"`, `modelValue` is either `''` (empty) or a real
`Number` — never a numeric string like `"25"`. Every other `type` keeps
`modelValue` as a plain string, same as always.

## ⚠️ `type="number"` + `min`/`max` needs `novalidate` on the `<form>`

`min`/`max` render as real native HTML attributes (needed for the spinner
arrows and keyboard up/down to clamp), and native `min`/`max` are also
HTML5 **constraint validation** — the browser blocks form submission
before your `@submit` handler (and therefore Inertia, and therefore your
`FormRequest`) ever runs, showing its own unstyled validation bubble
instead of this component's `error` state. No network request, no console
error, nothing happens until you notice the browser's tooltip.

This contradicts every field's promise that validation is server-side, and
already cost real debugging time once (see the sandbox demo commit
history). **Any `<form>` using `invue/forms` fields needs `novalidate`**:

```vue
<form novalidate @submit.prevent="submit">
```

This isn't optional or specific to number inputs — see the parent
`SKILL.md`'s native-validation gotcha section.

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

Numeric variant:

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { TextInput, useInvueField } from 'invue/forms';

const form = useForm({ age: '' });
const { modelValue: age, error: ageError } = useInvueField(form, 'age');
</script>

<template>
    <form novalidate @submit.prevent="...">
        <TextInput v-model="age" :error="ageError" type="number" label="Idade" min="18" max="120" required />
    </form>
</template>
```

Backend: `'age' => ['required', 'integer', 'min:18', 'max:120']`.

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
