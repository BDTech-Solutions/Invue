# FileUpload

`invue/forms` — `import { FileUpload } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/FileUpload.vue` — default implementation (native `<input type="file">`)
- `packages/forms/resources/js/Components/FileUpload.vue` — the resolving wrapper apps actually import
- Registry key: `forms.FileUpload`

## Props

| Prop         | Type      | Default | Notes                                                        |
|--------------|-----------|---------|----------------------------------------------------------------|
| `modelValue` | `File \| null` | `null` | See "One-way binding" below — this is **not** a normal two-way `v-model` field. |
| `label`      | `String`  | `null`  | Rendered above the input. Omit and use `#label` for custom markup. |
| `hint`       | `String`  | `null`  | Helper text below the input. Hidden automatically when `error` is set. |
| `accept`     | `String`  | `null`  | Native `accept` attribute (e.g. `"image/*"`, `".pdf"`).         |
| `required`   | `Boolean` | `false` | Only cosmetic — renders a red `*` next to the label. Does not add HTML5 `required` or any validation; validation is server-side via `FormRequest`. |
| `disabled`   | `Boolean` | `false` | Passed straight to the `<input>`.                               |
| `error`      | `String`  | `null`  | Validation error message. Tints the "Choose File" button red and hides `hint`. Feed this from `useInvueField(form, name).error`. |

## Slots

| Slot      | Purpose                                     |
|-----------|-----------------------------------------------|
| `#label`  | Replace the whole label area.                 |
| `#hint`   | Replace the helper text markup.               |
| `#error`  | Replace the error message markup.             |

No `#prefix`/`#suffix` — doesn't apply to a file input.

## One-way binding — read before wiring this up

A native `<input type="file">` is **uncontrolled**: browsers refuse to let
JS set its displayed value for security reasons. So unlike every other
`invue/forms` field, `v-model` here only flows **out** of the component —
`modelValue` is set from the file the user picked, but there's no way to
push a `File` back into the native input to make it "selected" in the UI.
`useInvueField`'s `set` still runs (`form[name] = value`) so the Inertia
form updates correctly; you just can't pre-fill or programmatically reset
what the input visually shows without also resetting the DOM element
itself (e.g. by changing a `:key` to force a remount).

There's also no visible border — file inputs render inconsistently
enough across browsers that a colored border isn't reliable. The error
state instead tints the native "Choose File" button
(`file:bg-red-50 file:text-red-600`) via Tailwind's `file:` pseudo-element
variant, which *is* consistently stylable.

## Typical usage — wired to Inertia validation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { FileUpload, useInvueField } from 'invue/forms';

const form = useForm({ avatar: null });
const { modelValue: avatar, error: avatarError } = useInvueField(form, 'avatar');
</script>

<template>
    <FileUpload
        v-model="avatar"
        :error="avatarError"
        accept="image/*"
        label="Avatar"
        hint="PNG ou JPG, ate 2MB"
    />
</template>
```

Backend: `'avatar' => ['nullable', 'file', 'image', 'max:2048']`
(`max` is in kilobytes, per Laravel's file validation rules).

**No extra wiring needed for multipart uploads.** Inertia's `form.post()`
detects `File`/`FileList`/`Blob` values anywhere in the form data and
automatically switches the request to `multipart/form-data` — assigning
`form.avatar = someFile` (which is exactly what `useInvueField`'s setter
does) is all it takes.

## Customizing without forking

Same escalation path: `accept`/`hint` props for the common cases, a full
registry swap (`invue.registry.register('forms.FileUpload', MyDropzone)`)
for anything past a plain native picker — drag-and-drop, image preview,
progress bar, multi-file selection. The replacement must accept at least
`modelValue` and `error`, and emit `update:modelValue`, to stay a drop-in.
