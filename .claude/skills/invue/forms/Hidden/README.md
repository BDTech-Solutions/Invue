# Hidden

`invue/forms` — `import { Hidden } from 'invue/forms'`

Files:
- `packages/forms/resources/js/Components/Base/Hidden.vue` — default implementation (`<input type="hidden">`)
- `packages/forms/resources/js/Components/Hidden.vue` — the resolving wrapper apps actually import
- Registry key: `forms.Hidden`

The simplest field in `invue/forms` — no label, no hint, no error, no
visible UI at all. For values that ride along with a form submission
without ever being user-editable (a referral source, a resource ID, a
pre-computed token).

## Props

| Prop         | Type              | Default | Notes                                     |
|--------------|-------------------|---------|----------------------------------------------|
| `modelValue` | `String, Number`  | `''`    | Use with `v-model`. Whatever you set it to before render is what gets submitted — there's no user interaction to change it through the UI. |

No `label`/`hint`/`error`/`required`/`disabled` — none of them apply to a
field the user never sees. If a hidden value can fail validation in a way
worth surfacing to the user, it probably shouldn't be hidden.

## Typical usage

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';
import { Hidden, useInvueField } from 'invue/forms';

const form = useForm({ referral_source: 'invue-demo' });
const { modelValue: referralSource } = useInvueField(form, 'referral_source');
</script>

<template>
    <Hidden v-model="referralSource" />
</template>
```

Set the initial value directly in `useForm({...})` (as above) for a static
value, or assign `referralSource.value = ...` (if you keep the ref, rather
than destructuring only `modelValue`) for something computed at runtime.

## Customizing without forking

There's essentially nothing to customize — the whole point is a bare
`<input type="hidden">`. A registry swap
(`invue.registry.register('forms.Hidden', ...)`) exists for consistency
with every other field but there's no realistic reason to reach for it.
