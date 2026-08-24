# KeyValue

`invue/forms` — `import { KeyValue } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/KeyValue.vue` — default implementation, **composed from `Repeater` + `TextInput`** rather than its own primitive markup
- `packages/forms/resources/js/Components/KeyValue.vue` — the resolving wrapper apps actually import
- Registry key: `forms.KeyValue`

Not a new interaction pattern — it's a `Repeater` whose row shape is
always `{ key, value }`, with the row template (two `TextInput`s side by
side) built in so you don't have to write it yourself. `Base/KeyValue.vue`
literally renders `<Repeater>` internally, importing the **public
wrapper** versions of `Repeater` and `TextInput` (not their `Base`
components) — a global registry swap of `forms.Repeater` or
`forms.TextInput` automatically applies inside every `KeyValue` row too,
since they're the same component instances everywhere else.

## Props

| Prop               | Type      | Default   | Notes                                                        |
|--------------------|-----------|-----------|------------------------------------------------------------------|
| `modelValue`       | `Array`   | `[]`      | Use with `v-model`. Array of `{ key, value }` objects — **not** a plain object map. See "Why an array" below. |
| `keyPlaceholder`   | `String`  | `'Chave'` | Placeholder for the key column's `TextInput`.                    |
| `valuePlaceholder` | `String`  | `'Valor'` | Placeholder for the value column's `TextInput`.                  |
| `label`            | `String`  | `null`    | Forwarded to the underlying `Repeater`.                          |
| `hint`             | `String`  | `null`    | Forwarded to the underlying `Repeater`. Hidden automatically when `error` is set. |
| `required`         | `Boolean` | `false`   | Forwarded to the underlying `Repeater` — cosmetic only.          |
| `disabled`         | `Boolean` | `false`   | Forwarded to the underlying `Repeater`, and to both `TextInput`s in every row. |
| `error`            | `String`  | `null`    | Forwarded to the underlying `Repeater`. Feed this from `useInvueField(form, name).error`. |

No slots of its own — the row template (key/value side by side) is fixed.
If you need a different row shape or extra columns, use `Repeater`
directly instead of `KeyValue`.

## Why an array, not a plain object

An editable key-value UI needs to tolerate an in-progress duplicate key
or a temporarily-empty key while the user is typing — a plain JS object
can't represent that (keys must be unique, and reactivity on dynamic keys
is awkward in Vue). `KeyValue` sidesteps this by modeling data as an array
of pairs, same as `Repeater`. If your backend wants a plain object, map it
at the boundary:

```js
const asObject = Object.fromEntries(metadata.value.map((pair) => [pair.key, pair.value]));
```

## Typical usage — wired to Inertia validation

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { KeyValue, useInvueField } from 'invue/forms';

const form = useForm({ metadata: [] });
const { modelValue: metadata, error: metadataError } = useInvueField(form, 'metadata');
</script>

<template>
    <KeyValue v-model="metadata" :error="metadataError" label="Metadata" hint="Ate 2 pares" />
</template>
```

Backend: `'metadata' => ['array', 'max:2']` (add `'metadata.*.key' => ['required_with:metadata.*.value']`
etc. if empty keys with a filled value shouldn't be allowed — same
per-item-error caveat as `Repeater`/`CheckboxGroup` applies to
`useInvueField`, which only reads the flat `metadata` key).

## Customizing without forking

For most cases, don't reach for the registry at all — write your own
`Repeater` usage instead (see its docs) if `KeyValue`'s fixed two-column
row doesn't fit (e.g. a value type picker per row, or more than two
columns). The registry swap
(`invue.registry.register('forms.KeyValue', MyKeyValue)`) is for replacing
the whole component wholesale while keeping the `forms.KeyValue` name
other code might reference; for a one-off different row shape, using
`Repeater` directly is simpler than fighting `KeyValue`'s fixed template.
