<script setup>
import { Topbar } from 'invue/panels'
import { Icon } from 'invue/core'
</script>

<template>
    <div class="p-8">
        <h1 class="mb-1 text-xl font-bold text-gray-900">Invue Panels — Topbar Demo</h1>
        <p class="mb-8 max-w-2xl text-sm text-gray-500">
            Três variantes da mesma Base/Topbar.vue, só trocando props/slots — nenhuma delas
            precisou de um novo componente registrado em <code>panels.Topbar</code>.
        </p>

        <div class="space-y-8">
            <!-- 1. Default: sem nenhuma prop -->
            <div>
                <p class="mb-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">Padrão</p>
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <Topbar />
                </div>
            </div>

            <!-- 2. brandName/color/badge -->
            <div>
                <p class="mb-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">
                    brand-name="Acme Analytics" color="purple" badge="Beta"
                </p>
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <Topbar brand-name="Acme Analytics" color="purple" badge="Beta">
                        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-100">
                            Ajuda
                        </button>
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 text-xs font-semibold text-purple-700">
                            AC
                        </div>
                    </Topbar>
                </div>
            </div>

            <!-- 3. #brand + #start totalmente customizados -->
            <div>
                <p class="mb-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">
                    #brand (logo custom) + #start (abas) + color="blue"
                </p>
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <Topbar color="blue">
                        <template #brand>
                            <div class="flex shrink-0 items-center gap-2">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-white">
                                    <Icon name="bar-chart-3" class="h-4 w-4" />
                                </div>
                                <span class="text-base font-semibold text-gray-900">Nimbus</span>
                            </div>
                        </template>

                        <template #start>
                            <nav class="flex items-center gap-1">
                                <button type="button" class="rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
                                    Visão geral
                                </button>
                                <button type="button" class="rounded-full px-3 py-1 text-sm font-medium text-gray-500 hover:bg-gray-100">
                                    Relatórios
                                </button>
                                <button type="button" class="rounded-full px-3 py-1 text-sm font-medium text-gray-500 hover:bg-gray-100">
                                    Equipe
                                </button>
                            </nav>
                        </template>

                        <button type="button" class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100">
                            <Icon name="bell" class="h-5 w-5" />
                            <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white" />
                        </button>
                    </Topbar>
                </div>
            </div>
        </div>
    </div>
</template>
