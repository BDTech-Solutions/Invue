# Textarea

`invue/forms` — `import { Textarea } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/Textarea.vue` — default implementation (native `<textarea>`)
- `packages/forms/resources/js/Components/Textarea.vue` — the resolving wrapper apps actually import
- Registry key: `forms.Textarea`

## Props

| Prop         | Type              | Default | Notes                                                        |
|--------------|-------------------|---------|----------------------------------------------------------------|
| `modelValue` | `String`          | `''`    | Use with `v-model`.                                             |
| `label`      | `String`          | `null`  | Rendered above the textarea. Omit and use `#label` for custom markup. |
| `hint`       | `String`          | `null`  | Helper text below the textarea. Hidden automatically when `error` is set. |
| `placeholder`| `String`          | `null`  | Native placeholder text.                                        |
| `rows`       | `String, Number`  | `3`     | Native `rows` attribute — controls the textarea's height.       |
| `required`   | `Boolean`         | `false` | Only cosmetic — renders a red `*` next to the label. Does not add HTML5 `required` or any validation; validation is server-side via `FormRequest`. |
| `disabled`   | `Boolean`         | `false` | Passed straight to the `<textarea>`.                             |
| `error`      | `String`          | `null`  | Validation error message. Turns the border red and hides `hint`. Feed this from `useInvueField(form, name).error`. |

No auto-resize / character counter — same "no complexity in Base, swap via
the registry if you need it" philosophy as the other fields.

## Slots

Same set as `TextInput` minus `#prefix`/`#suffix` (impractical to pin inline
decorations on a multi-line, resizable field).

| Slot      | Purpose                                     |
|-----------|-----------------------------------------------|
| `#label`  | Replace the whole label area.                 |
| `#hint`   | Replace the helper text markup.               |
| `#error`  | Replace the error message markup.             |

## Typical usage — wired to Inertia validation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { Textarea, useInvueField } from 'invue/forms';

const form = useForm({ bio: '' });
const { modelValue: bio, error: bioError } = useInvueField(form, 'bio');
</script>

<template>
    <Textarea v-model="bio" :error="bioError" label="Bio" rows="4" />
</template>
```

Backend: `'bio' => ['nullable', 'string', 'max:500']` (or whatever limit
fits — nothing in the component enforces length, it's purely server-side).

## Customizing without forking

Same escalation path as the other fields: props/slots for the common
cases, a full registry swap
(`invue.registry.register('forms.Textarea', MyRichEditor)`) for anything
past plain text — a rich-text/WYSIWYG editor, an auto-growing textarea,
a markdown preview pane, etc. The replacement must accept at least
`modelValue` and `error`, and emit `update:modelValue`, to stay a drop-in.
