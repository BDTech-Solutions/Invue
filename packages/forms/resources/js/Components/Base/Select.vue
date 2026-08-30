<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { Icon } from 'invue/core'
import { useMountedOnClient } from '../../composables/useMountedOnClient'

// A plain incrementing counter, not Vue 3.5's useId() — invue/forms
// doesn't pin a minimum Vue version, so this stays correct even against
// an older Vue 3.x. Only needs to be unique per mounted Select, not
// SSR/client-stable, since it's purely a DOM wiring detail (aria-controls/
// aria-activedescendant) that doesn't affect what gets rendered.
let selectIdCounter = 0
const listboxId = `invue-select-listbox-${++selectIdCounter}`

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: null,
    },
    options: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: null,
    },
    hint: {
        type: String,
        default: null,
    },
    placeholder: {
        type: String,
        default: 'Select an option',
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
    // Shows a search box at the top of the dropdown and filters `options`
    // client-side. Always implied `true` when `url` is set — a remote
    // search has nowhere to type otherwise.
    searchable: {
        type: Boolean,
        default: false,
    },
    // Caps the dropdown's height to roughly this many rows before it
    // scrolls — not a hard limit on how many options can exist, just how
    // tall the open panel gets.
    maxVisibleItems: {
        type: Number,
        default: 6,
    },
    // A GET endpoint returning the same `[{ value, label }]` (or plain
    // scalar) shape `options` accepts, for the current search term —
    // set this instead of preloading a table too large to ship client-side.
    // See Invue\Forms\Support\SelectOptions on the backend. Requests are
    // debounced and only the most recently issued one is applied, so a
    // slow response for a stale keystroke can never clobber a newer one.
    url: {
        type: String,
        default: null,
    },
    searchParam: {
        type: String,
        default: 'search',
    },
    debounce: {
        type: Number,
        default: 300,
    },
})

const emit = defineEmits(['update:modelValue'])

const isMounted = useMountedOnClient()
const isSearchable = computed(() => props.searchable || !!props.url)

function normalize(list) {
    return (list ?? []).map((option) =>
        typeof option === 'object' && option !== null
            ? option
            : { value: option, label: String(option) },
    )
}

const localOptions = ref(normalize(props.options))

watch(
    () => props.options,
    (value) => {
        localOptions.value = normalize(value)
    },
)

// null = never fetched yet, so a remote Select still shows its initial
// `options` (if any were passed) until the first real search runs.
const remoteOptions = ref(null)
const loading = ref(false)
const search = ref('')

const displayedOptions = computed(() => {
    if (props.url) {
        return remoteOptions.value ?? localOptions.value
    }

    if (search.value.trim() === '') {
        return localOptions.value
    }

    const needle = search.value.trim().toLowerCase()

    return localOptions.value.filter((option) => String(option.label).toLowerCase().includes(needle))
})

const selectedOption = computed(
    () =>
        localOptions.value.find((option) => option.value === props.modelValue)
        ?? remoteOptions.value?.find((option) => option.value === props.modelValue)
        ?? null,
)

let searchTimer = null
let requestId = 0

function runRemoteSearch() {
    if (!props.url) {
        return
    }

    const thisRequest = ++requestId
    loading.value = true

    const query = new URLSearchParams({ [props.searchParam]: search.value })

    fetch(`${props.url}?${query.toString()}`, { headers: { Accept: 'application/json' } })
        .then((response) => response.json())
        .then((data) => {
            // A slower earlier request landing after a faster later one
            // would otherwise flash stale results back onto the screen.
            if (thisRequest === requestId) {
                remoteOptions.value = normalize(data)
            }
        })
        .finally(() => {
            if (thisRequest === requestId) {
                loading.value = false
            }
        })
}

watch(search, () => {
    if (!props.url) {
        return
    }

    clearTimeout(searchTimer)
    searchTimer = setTimeout(runRemoteSearch, props.debounce)
})

const open = ref(false)
const triggerEl = ref(null)
const panelEl = ref(null)
const searchInputEl = ref(null)
const panelStyle = ref({})
const activeIndex = ref(-1)

watch(displayedOptions, () => {
    activeIndex.value = displayedOptions.value.length > 0 ? 0 : -1
})

// Teleported to <body>, same reasoning as invue/actions' ActionGroup menu —
// a Select living inside a scroll-clipped container (a modal, a narrow
// table cell) would otherwise get its dropdown cut off instead of floating
// above everything in viewport coordinates.
function updatePosition() {
    if (!triggerEl.value) {
        return
    }

    const rect = triggerEl.value.getBoundingClientRect()

    panelStyle.value = {
        position: 'fixed',
        top: `${rect.bottom + 4}px`,
        left: `${rect.left}px`,
        width: `${rect.width}px`,
    }
}

function handleOutsideClick(event) {
    if (triggerEl.value?.contains(event.target) || panelEl.value?.contains(event.target)) {
        return
    }

    close()
}

function handleGlobalKeydown(event) {
    if (event.key === 'Escape') {
        close()
        return
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault()
        activeIndex.value = Math.min(activeIndex.value + 1, displayedOptions.value.length - 1)
    } else if (event.key === 'ArrowUp') {
        event.preventDefault()
        activeIndex.value = Math.max(activeIndex.value - 1, 0)
    } else if (event.key === 'Enter') {
        const option = displayedOptions.value[activeIndex.value]

        if (option) {
            event.preventDefault()
            choose(option)
        }
    }
}

async function openPanel() {
    if (props.disabled) {
        return
    }

    updatePosition()
    open.value = true
    activeIndex.value = displayedOptions.value.findIndex((option) => option.value === props.modelValue)

    if (props.url && remoteOptions.value === null) {
        runRemoteSearch()
    }

    window.addEventListener('scroll', updatePosition, true)
    window.addEventListener('resize', updatePosition)
    document.addEventListener('mousedown', handleOutsideClick)
    document.addEventListener('keydown', handleGlobalKeydown)

    await nextTick()
    searchInputEl.value?.focus()
}

function close() {
    open.value = false
    search.value = ''
    window.removeEventListener('scroll', updatePosition, true)
    window.removeEventListener('resize', updatePosition)
    document.removeEventListener('mousedown', handleOutsideClick)
    document.removeEventListener('keydown', handleGlobalKeydown)
}

function toggle() {
    if (open.value) {
        close()
    } else {
        openPanel()
    }
}

function choose(option) {
    emit('update:modelValue', option.value)
    close()
}

// ~36px per row (py-1.5 + text-sm) — an estimate, not pixel-perfect; it
// only needs to be close enough that the cap feels intentional.
const maxHeight = computed(() => `${props.maxVisibleItems * 2.25}rem`)
</script>

<template>
    <div class="invue-form-field">
        <label v-if="label || $slots.label" class="mb-1 block text-sm font-medium text-gray-700">
            <slot name="label">
                {{ label }}
                <span v-if="required" class="text-red-500">*</span>
            </slot>
        </label>

        <button
            ref="triggerEl"
            type="button"
            aria-haspopup="listbox"
            :aria-expanded="open"
            :disabled="disabled"
            class="flex w-full items-center justify-between gap-2 rounded-md border px-3 py-1.5 text-left shadow-sm sm:text-sm"
            :class="[
                error ? 'border-red-400 focus:border-red-400' : 'border-gray-300 focus:border-green-500',
                disabled ? 'cursor-not-allowed bg-gray-50 text-gray-400' : 'bg-white text-gray-900',
            ]"
            @click="toggle"
        >
            <span class="truncate" :class="{ 'text-gray-400': !selectedOption }">
                {{ selectedOption?.label ?? placeholder }}
            </span>
            <Icon name="chevron-down" class="h-4 w-4 shrink-0 text-gray-400" />
        </button>

        <Teleport v-if="isMounted" to="body">
            <div
                v-if="open"
                ref="panelEl"
                class="z-50 overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg"
                :style="panelStyle"
            >
                <div v-if="isSearchable" class="border-b border-gray-100 p-1.5">
                    <div class="relative">
                        <Icon
                            name="search"
                            class="pointer-events-none absolute top-1/2 left-2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"
                        />
                        <input
                            ref="searchInputEl"
                            v-model="search"
                            type="text"
                            name="q"
                            placeholder="Search..."
                            role="combobox"
                            aria-autocomplete="list"
                            :aria-expanded="open"
                            :aria-controls="listboxId"
                            :aria-activedescendant="activeIndex >= 0 ? `${listboxId}-option-${activeIndex}` : undefined"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                            data-1p-ignore
                            data-lpignore="true"
                            class="w-full rounded border-0 bg-gray-50 py-1 pr-2 pl-7 text-sm focus:ring-1 focus:ring-green-500 focus:outline-none"
                        />
                    </div>
                </div>

                <div :id="listboxId" role="listbox" class="overflow-y-auto py-1" :style="{ maxHeight }">
                    <p v-if="loading" class="px-3 py-2 text-sm text-gray-400">Loading…</p>

                    <p v-else-if="displayedOptions.length === 0" class="px-3 py-2 text-sm text-gray-400">
                        No results
                    </p>

                    <template v-else>
                        <button
                            v-for="(option, index) in displayedOptions"
                            :id="`${listboxId}-option-${index}`"
                            :key="option.value"
                            type="button"
                            role="option"
                            :aria-selected="option.value === modelValue"
                            class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-sm hover:bg-gray-50"
                            :class="index === activeIndex ? 'bg-gray-50' : ''"
                            @click="choose(option)"
                            @mouseenter="activeIndex = index"
                        >
                            <span class="truncate">{{ option.label }}</span>
                            <Icon
                                v-if="option.value === modelValue"
                                name="check"
                                class="h-4 w-4 shrink-0 text-green-600"
                            />
                        </button>
                    </template>
                </div>
            </div>
        </Teleport>

        <p v-if="hint && !error" class="mt-1 text-sm text-gray-500">
            <slot name="hint">{{ hint }}</slot>
        </p>

        <p v-if="error" class="mt-1 text-sm text-red-600">
            <slot name="error">{{ error }}</slot>
        </p>
    </div>
</template>
