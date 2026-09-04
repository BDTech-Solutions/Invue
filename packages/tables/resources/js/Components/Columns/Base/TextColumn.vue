<script setup>
import { computed } from 'vue'
import { formatDate, timeAgo } from '../../../support/formatDate'
import { getNestedValue } from '../../../support/getNestedValue'

// Kept inline (not in a shared .js support file) on purpose: Tailwind's JIT
// scanner only globs `.vue` files inside vendor/invue/** (see
// invue/core's tailwind.content.js) — class strings living in a plain .js
// module would never be seen and would get silently purged in consumer apps.
const BADGE_CLASSES = {
    gray: 'bg-gray-100 text-gray-700',
    red: 'bg-red-100 text-red-700',
    green: 'bg-green-100 text-green-700',
    blue: 'bg-blue-100 text-blue-700',
    yellow: 'bg-yellow-100 text-yellow-800',
    amber: 'bg-amber-100 text-amber-800',
    sky: 'bg-sky-100 text-sky-700',
    rose: 'bg-rose-100 text-rose-700',
    purple: 'bg-purple-100 text-purple-700',
    pink: 'bg-pink-100 text-pink-700',
}

const props = defineProps({
    row: {
        type: Object,
        required: true,
    },
    field: {
        type: String,
        required: true,
    },
    default: {
        default: null,
    },
    placeholder: {
        type: String,
        default: '—',
    },
    badge: {
        type: Boolean,
        default: false,
    },
    color: {
        type: [String, Function],
        default: 'gray',
    },
    money: {
        type: String,
        default: null,
    },
    numeric: {
        type: Boolean,
        default: false,
    },
    date: {
        type: String,
        default: null,
    },
    dateTime: {
        type: String,
        default: null,
    },
    since: {
        type: Boolean,
        default: false,
    },
    formatUsing: {
        type: Function,
        default: null,
    },
    limit: {
        type: Number,
        default: null,
    },
    words: {
        type: Number,
        default: null,
    },
    wrap: {
        type: Boolean,
        default: false,
    },
    description: {
        type: [String, Function],
        default: null,
    },
    descriptionPosition: {
        type: String,
        default: 'below',
    },
    url: {
        type: [String, Function],
        default: null,
    },
    openInNewTab: {
        type: Boolean,
        default: false,
    },
    copyable: {
        type: Boolean,
        default: false,
    },
    weight: {
        type: String,
        default: null,
    },
})

const rawValue = computed(() => getNestedValue(props.row, props.field) ?? props.default)

const resolvedColor = computed(() =>
    typeof props.color === 'function' ? props.color(rawValue.value, props.row) : props.color,
)

const displayValue = computed(() => {
    const value = rawValue.value

    if (value == null || value === '') {
        return props.placeholder
    }

    if (props.formatUsing) {
        return props.formatUsing(value, props.row)
    }

    if (props.money) {
        const amount = Number(value)

        return Number.isNaN(amount)
            ? String(value)
            : new Intl.NumberFormat(undefined, { style: 'currency', currency: props.money }).format(amount)
    }

    if (props.numeric) {
        const amount = Number(value)

        return Number.isNaN(amount) ? String(value) : new Intl.NumberFormat().format(amount)
    }

    if (props.since) {
        return timeAgo(value)
    }

    if (props.date || props.dateTime) {
        return formatDate(value, props.date ?? props.dateTime)
    }

    let text = String(value)

    if (props.words) {
        const parts = text.split(/\s+/)

        if (parts.length > props.words) {
            text = `${parts.slice(0, props.words).join(' ')}…`
        }
    } else if (props.limit && text.length > props.limit) {
        text = `${text.slice(0, props.limit)}…`
    }

    return text
})

const resolvedUrl = computed(() =>
    typeof props.url === 'function' ? props.url(rawValue.value, props.row) : props.url,
)

const resolvedDescription = computed(() =>
    typeof props.description === 'function' ? props.description(rawValue.value, props.row) : props.description,
)

const weightClass = computed(() => ({ bold: 'font-bold', medium: 'font-medium', semibold: 'font-semibold' })[props.weight] ?? '')

async function copy() {
    if (!navigator.clipboard) {
        return
    }

    await navigator.clipboard.writeText(String(rawValue.value ?? ''))
}
</script>

<template>
    <div class="flex items-center gap-1.5">
        <div v-if="resolvedDescription && descriptionPosition === 'above'" class="text-xs text-gray-400">
            {{ resolvedDescription }}
        </div>

        <span
            v-if="badge"
            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
            :class="BADGE_CLASSES[resolvedColor] ?? BADGE_CLASSES.gray"
        >
            {{ displayValue }}
        </span>

        <a
            v-else-if="resolvedUrl"
            :href="resolvedUrl"
            :target="openInNewTab ? '_blank' : undefined"
            :rel="openInNewTab ? 'noopener noreferrer' : undefined"
            class="text-green-700 hover:underline"
            :class="[weightClass, wrap ? 'whitespace-normal' : 'whitespace-nowrap']"
        >
            {{ displayValue }}
        </a>

        <span v-else class="text-gray-700" :class="[weightClass, wrap ? 'whitespace-normal' : 'whitespace-nowrap']">
            {{ displayValue }}
        </span>

        <button
            v-if="copyable"
            type="button"
            class="text-gray-400 hover:text-gray-600"
            title="Copy"
            @click="copy"
        >
            ⧉
        </button>

        <div v-if="resolvedDescription && descriptionPosition !== 'above'" class="text-xs text-gray-400">
            {{ resolvedDescription }}
        </div>
    </div>
</template>
