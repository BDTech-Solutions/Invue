<script setup>
import { Sidebar } from 'invue/panels'

// items é uma prop nova do Base/Sidebar.vue: permite renderizar a sidebar
// standalone (sem depender de page.props.invuePanel, que só existe dentro
// de um Panel de verdade), então dá pra usar essa página como vitrine.
const items = [
    { url: '/invue-panels-demo', label: 'Início', icon: 'file-text' },
    { url: '/relatorios', label: 'Relatórios', icon: 'circle-check' },
    { url: '/config', label: 'Configurações', icon: 'triangle-alert' },
]

const itemsComBadge = [
    { url: '/inbox', label: 'Caixa de entrada', icon: 'file-text', count: 4 },
    { url: '/enviados', label: 'Enviados', icon: 'circle-check', count: 0 },
    { url: '/spam', label: 'Spam', icon: 'circle-x', count: 12 },
]
</script>

<template>
    <div class="p-8">
        <h1 class="mb-1 text-xl font-bold text-gray-900">Invue Panels — Sidebar Demo</h1>
        <p class="mb-8 max-w-2xl text-sm text-gray-500">
            Três variantes da mesma Base/Sidebar.vue, só trocando props/slots — nenhuma delas
            precisou de um novo componente registrado em <code>panels.Sidebar</code>. Uma sidebar
            estruturalmente diferente (não só reskin) ainda usaria o registry swap normal.
        </p>

        <div class="flex flex-wrap items-start gap-8">
            <!-- 1. Default: sem nenhuma prop -->
            <div>
                <p class="mb-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">Padrão</p>
                <div class="flex h-96 overflow-hidden rounded-lg border border-gray-200">
                    <Sidebar :items="items" />
                </div>
            </div>

            <!-- 2. selectedColor + width + header/footer (a "aba de perfil lá embaixo") -->
            <div>
                <p class="mb-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">
                    selected-color="purple" width="lg" + perfil no footer
                </p>
                <div class="flex h-96 overflow-hidden rounded-lg border border-gray-200">
                    <Sidebar :items="items" selected-color="purple" width="lg">
                        <template #header>
                            <div class="text-sm font-semibold text-gray-900">Minha Loja</div>
                        </template>
                        <template #footer>
                            <div class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-gray-700">
                                <div class="h-6 w-6 shrink-0 rounded-full bg-gray-300" />
                                Daniel
                            </div>
                        </template>
                    </Sidebar>
                </div>
            </div>

            <!-- 3. #item scoped slot: markup totalmente customizado por linha -->
            <div>
                <p class="mb-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">
                    #item com badge de contagem
                </p>
                <div class="flex h-96 overflow-hidden rounded-lg border border-gray-200">
                    <Sidebar :items="itemsComBadge" selected-color="sky" width="sm">
                        <template #item="{ item, active }">
                            <a
                                :href="item.url"
                                class="flex items-center justify-between rounded-md px-3 py-2 text-sm font-medium"
                                :class="active ? 'bg-sky-50 text-sky-700' : 'text-gray-600 hover:bg-gray-50'"
                            >
                                <span>{{ item.label }}</span>
                                <span
                                    v-if="item.count"
                                    class="rounded-full bg-sky-600 px-1.5 py-0.5 text-xs font-semibold text-white"
                                >
                                    {{ item.count }}
                                </span>
                            </a>
                        </template>
                    </Sidebar>
                </div>
            </div>
        </div>
    </div>
</template>
