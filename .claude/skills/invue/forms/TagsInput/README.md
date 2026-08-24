# TagsInput

`invue/forms` — `import { TagsInput } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/TagsInput.vue` — default implementation (a free-form chip/token input)
- `packages/forms/resources/js/Components/TagsInput.vue` — the resolving wrapper apps actually import
- Registry key: `forms.TagsInput`

Like `CheckboxGroup`, `modelValue` is an `Array`, but unlike `CheckboxGroup`
there's no fixed `options` list — the user types whatever values they want.
Distinct from `Repeater` too: rows here are plain strings, not objects with
their own sub-fields.

## Props

| Prop         | Type      | Default | Notes                                                        |
|--------------|-----------|---------|----------------------------------------------------------------|
| `modelValue` | `Array`   | `[]`    | Use with `v-model`. Array of strings.                           |
| `label`      | `String`  | `null`  | Rendered above the input. Omit and use `#label` for custom markup. |
| `hint`       | `String`  | `null`  | Helper text below the input. Hidden automatically when `error` is set. |
| `placeholder`| `String`  | `null`  | Placeholder in the text-entry box (not on the chips).           |
| `required`   | `Boolean` | `false` | Only cosmetic — renders a red `*` next to the label. No validation; that's server-side. |
| `disabled`   | `Boolean` | `false` | Disables the text box and every chip's remove button.           |
| `error`      | `String`  | `null`  | Validation error message. Turns the container border red and hides `hint`. Feed this from `useInvueField(form, name).error`. |

## Interaction

- **Enter** or **comma** commits the current text as a tag and clears the
  box. Duplicate values are silently ignored (the array only ever holds
  unique entries).
- **Backspace** on an empty box removes the last tag — standard tag-input
  UX, lets you correct a mistake without reaching for the mouse.
- Click the `×` on any chip to remove it directly.

No max-tag-count enforcement built in — pass a `max:` rule server-side and
surface it via `error` the same as every other field; there's no
client-side equivalent since that would duplicate validation logic instead
of just doing it once in the `FormRequest`.

## Slots

| Slot      | Purpose                                     |
|-----------|-----------------------------------------------|
| `#label`  | Replace the whole label area.                 |
| `#hint`   | Replace the helper text markup.               |
| `#error`  | Replace the error message markup.             |

No per-chip slot — a custom chip appearance is a registry swap, same
reasoning as `RadioGroup`/`CheckboxGroup`'s missing per-option slot.

## Typical usage — wired to Inertia validation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { TagsInput, useInvueField } from 'invue/forms';

const form = useForm({ tags: [] });
const { modelValue: tags, error: tagsError } = useInvueField(form, 'tags');
</script>

<template>
    <TagsInput v-model="tags" :error="tagsError" label="Tags" placeholder="nova tag..." />
</template>
```

Backend: `'tags' => ['array', 'max:3']`.

## Customizing without forking

Same escalation path as the other fields: `placeholder`/`hint` props for
the common cases, a full registry swap
(`invue.registry.register('forms.TagsInput', MyAutocompleteTags)`) for
anything past free-form typing — tag autocomplete/suggestions, a fixed
color per tag, async-validated tags, etc. The replacement must accept at
least `modelValue` (Array) and `error`, and emit `update:modelValue`, to
stay a drop-in.
