# Repeater

`invue/forms` — `import { Repeater } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/Repeater.vue` — default implementation (add/remove a list of rows, each rendered by the consumer's own template)
- `packages/forms/resources/js/Components/Repeater.vue` — the resolving wrapper apps actually import
- Registry key: `forms.Repeater`

The first `invue/forms` field with no native HTML equivalent — there's no
`<input type="repeater">`. Matches Filament's `Repeater::make()`: a
dynamic, user-extendable list of sub-fields.

## Props

| Prop         | Type       | Default        | Notes                                                        |
|--------------|------------|----------------|------------------------------------------------------------------|
| `modelValue` | `Array`    | `[]`           | Use with `v-model`. Each entry is one row — shape is entirely up to you (an object, typically). |
| `newItem`    | `Function` | `() => ({})`   | Factory called with no arguments when "+ Adicionar" is clicked, returning a fresh row. Override this to seed new rows with the right shape (e.g. `() => ({ url: '' })`). |
| `label`      | `String`   | `null`         | Rendered above the list. Omit and use `#label` for custom markup. |
| `hint`       | `String`   | `null`         | Helper text below the list. Hidden automatically when `error` is set. |
| `required`   | `Boolean`  | `false`        | Only cosmetic — renders a red `*` next to the label. No validation; that's server-side. |
| `disabled`   | `Boolean`  | `false`        | Disables the "+ Adicionar" button and every row's "Remover" button. Does **not** disable whatever fields you render inside a row — that's on you if needed. |
| `error`      | `String`   | `null`         | Validation error message (e.g. an array-level `max:` rule). Hidden along with `hint`. Feed this from `useInvueField(form, name).error`. |

## The default slot — this is where your row's fields go

```vue
<Repeater v-model="links" :new-item="() => ({ url: '' })">
    <template #default="{ item, index, update }">
        <!-- render whatever fields this row needs -->
    </template>
</Repeater>
```

- `item` — the row's data (e.g. `{ url: '' }`).
- `index` — the row's position in the array.
- `update(newValue)` — replaces this row with `newValue` (immutably —
  every other `invue/forms` field emits a fresh array/value rather than
  mutating in place, and `Repeater` follows the same convention). Wire
  every field inside the row through this rather than mutating `item`
  directly:

```vue
<template #default="{ item, update }">
    <TextInput
        :model-value="item.url"
        placeholder="https://..."
        @update:model-value="(value) => update({ ...item, url: value })"
    />
</template>
```

For a row with multiple fields, spread `item` and override just the
changed key each time, same pattern as above (`update({ ...item, url: value })`,
`update({ ...item, label: value })`, etc.) — each field's own `@update:model-value`
handler only needs to know its own key.

There's no `#label`/`#hint`/`#error` override shown above because they
follow the same convention as every other field (see `TextInput`'s docs) —
omitted here only for brevity.

## Typical usage — wired to Inertia validation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { Repeater, TextInput, useInvueField } from 'invue/forms';

const form = useForm({ links: [] });
const { modelValue: links, error: linksError } = useInvueField(form, 'links');
</script>

<template>
    <form novalidate @submit.prevent="...">
        <Repeater v-model="links" :error="linksError" :new-item="() => ({ url: '' })" label="Links" hint="Ate 2 links">
            <template #default="{ item, update }">
                <TextInput
                    :model-value="item.url"
                    placeholder="https://..."
                    @update:model-value="(value) => update({ ...item, url: value })"
                />
            </template>
        </Repeater>
    </form>
</template>
```

Backend, array-shaped validation:

```php
'links' => ['array', 'max:2'],
'links.*.url' => ['required', 'url'],
```

Same caveat as `CheckboxGroup`: `useInvueField`'s error lookup only reads
the flat `form.errors[name]` key, so `linksError` surfaces array-level
violations (`max:2` on `links` itself) but not per-row `links.0.url`
errors — point a field at `form.errors['links.0.url']` directly if you
need to highlight a specific bad row.

## Customizing without forking

The default slot already gives you full control over each row's fields —
most customization needs (which fields, their layout, per-row validation
UI) are solved there without touching the component at all. Beyond that,
a full registry swap
(`invue.registry.register('forms.Repeater', MySortableRepeater))`) covers
things the Base implementation doesn't attempt — drag-to-reorder, a
max-rows cap enforced client-side, collapsible rows, etc. The replacement
must accept at least `modelValue` (Array) and `newItem`, emit
`update:modelValue`, and render the default slot with `{ item, index, update }`
to stay a drop-in.
