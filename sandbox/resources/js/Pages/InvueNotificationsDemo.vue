<script setup>
import { router } from '@inertiajs/vue3'
import { Notify } from 'invue/notifications'

function trigger(type) {
    router.post(route('invue.notifications.demo.send'), { type }, { preserveScroll: true })
}

function triggerClientOnly() {
    Notify.make()
        .title('Sem ida ao servidor')
        .body('Isso foi criado 100% no client, com Notify.make()...send().')
        .color('purple')
        .icon('map-pin')
        .send()
}
</script>

<template>
    <div class="mx-auto max-w-md p-8">
        <h1 class="mb-1 text-xl font-bold text-gray-900">Invue Notifications Demo</h1>
        <p class="mb-6 text-sm text-gray-500">
            Cada botao dispara Notification::make()->...->send() no servidor; o toast aparece no canto
            superior direito (montado uma vez, globalmente, em app.js).
        </p>

        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                class="rounded-md bg-green-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-green-500"
                @click="trigger('success')"
            >
                success()
            </button>
            <button
                type="button"
                class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-400"
                @click="trigger('warning')"
            >
                warning()
            </button>
            <button
                type="button"
                class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-500"
                @click="trigger('danger')"
            >
                danger()
            </button>
            <button
                type="button"
                class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-sky-500"
                @click="trigger('info')"
            >
                info()
            </button>
            <button
                type="button"
                class="rounded-md bg-purple-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-purple-500"
                @click="trigger('persistent')"
            >
                persistent()
            </button>
        </div>

        <p class="mt-6 mb-2 text-sm text-gray-500">
            Este dispara sem passar pelo servidor — Notify.make()...send() direto no client:
        </p>
        <button
            type="button"
            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            @click="triggerClientOnly"
        >
            Notify.make()...send()
        </button>
    </div>
</template>
