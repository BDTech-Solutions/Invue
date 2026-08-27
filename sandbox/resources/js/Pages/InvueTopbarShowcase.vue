<script setup>
import { ref, computed } from 'vue'
import { Topbar } from 'invue/panels'
import { Icon } from 'invue/core'

// Vitrine de estilização máxima em cima da MESMA Base/Topbar.vue — tudo
// abaixo é composição via props/slots, nenhuma linha nova em packages/panels.

const tabs = [
    { id: 'overview', label: 'Visão geral' },
    { id: 'reports', label: 'Relatórios' },
    { id: 'settings', label: 'Configurações' },
]
const activeTab = ref('overview')
const tabLabel = computed(() => tabs.find((t) => t.id === activeTab.value)?.label ?? '')

const workspaces = [
    { name: 'Invue Store', initials: 'IS', bg: 'bg-purple-600' },
    { name: 'Acme Corp', initials: 'AC', bg: 'bg-blue-600' },
    { name: 'Pessoal', initials: 'PE', bg: 'bg-green-600' },
]
const currentWorkspace = ref(workspaces[0])
const workspaceOpen = ref(false)

function selectWorkspace(ws) {
    currentWorkspace.value = ws
    workspaceOpen.value = false
}

const notifications = [
    { title: 'Novo projeto criado', desc: '"Redesign do onboarding" por Daniel', icon: 'folder-kanban' },
    { title: 'Membro adicionado', desc: 'Marina entrou na equipe de Design', icon: 'users' },
    { title: 'Meta atingida', desc: 'Conversão passou de 4%', icon: 'trending-up' },
]
const notifOpen = ref(false)
const profileOpen = ref(false)

const stats = [
    { label: 'Usuários ativos', value: '8.204', delta: '+12,4%', icon: 'users', color: 'bg-purple-50 text-purple-600' },
    { label: 'Receita mensal', value: 'R$ 42.318', delta: '+8,1%', icon: 'bar-chart-3', color: 'bg-blue-50 text-blue-600' },
    { label: 'Conversão', value: '4,7%', delta: '+1,0%', icon: 'trending-up', color: 'bg-green-50 text-green-600' },
]
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-900">
        <Topbar color="purple">
            <template #brand>
                <div class="relative shrink-0">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg py-1 pr-1 hover:bg-gray-50"
                        @click="workspaceOpen = !workspaceOpen"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-fuchsia-600 text-white shadow-sm shadow-purple-500/30">
                            <Icon name="sparkles" class="h-4 w-4" />
                        </div>
                        <span class="text-base font-semibold text-gray-900">{{ currentWorkspace.name }}</span>
                        <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-700">Pro</span>
                        <Icon name="chevron-down" class="h-4 w-4 text-gray-400" />
                    </button>

                    <div
                        v-if="workspaceOpen"
                        class="absolute top-full left-0 z-10 mt-2 w-56 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                    >
                        <button
                            v-for="ws in workspaces"
                            :key="ws.name"
                            type="button"
                            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm hover:bg-gray-50"
                            @click="selectWorkspace(ws)"
                        >
                            <div class="flex h-6 w-6 items-center justify-center rounded-md text-[11px] font-semibold text-white" :class="ws.bg">
                                {{ ws.initials }}
                            </div>
                            <span class="flex-1 text-gray-700">{{ ws.name }}</span>
                            <Icon v-if="ws.name === currentWorkspace.name" name="check" class="h-4 w-4 text-purple-600" />
                        </button>
                    </div>
                </div>
            </template>

            <template #start>
                <nav class="flex items-center gap-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-sm font-medium transition"
                        :class="activeTab === tab.id ? 'bg-purple-50 text-purple-700' : 'text-gray-500 hover:bg-gray-100'"
                        @click="activeTab = tab.id"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </template>

            <div class="relative hidden w-56 lg:block">
                <Icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input
                    type="text"
                    placeholder="Buscar..."
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 py-1.5 pr-3 pl-9 text-sm text-gray-700 placeholder-gray-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 focus:outline-none"
                />
            </div>

            <div class="relative">
                <button type="button" class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100" @click="notifOpen = !notifOpen">
                    <Icon name="bell" class="h-5 w-5" />
                    <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white" />
                </button>

                <div
                    v-if="notifOpen"
                    class="absolute top-full right-0 z-10 mt-2 w-72 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                >
                    <p class="px-3 py-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">Notificações</p>
                    <div v-for="n in notifications" :key="n.title" class="flex items-start gap-2.5 px-3 py-2 hover:bg-gray-50">
                        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-purple-50 text-purple-600">
                            <Icon :name="n.icon" class="h-3.5 w-3.5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ n.title }}</p>
                            <p class="truncate text-xs text-gray-500">{{ n.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative">
                <button
                    type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-fuchsia-600 text-xs font-semibold text-white"
                    @click="profileOpen = !profileOpen"
                >
                    DA
                </button>

                <div
                    v-if="profileOpen"
                    class="absolute top-full right-0 z-10 mt-2 w-48 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                >
                    <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">
                        <Icon name="settings" class="h-4 w-4 text-gray-400" />
                        Configurações
                    </button>
                    <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                        <Icon name="log-out" class="h-4 w-4" />
                        Sair
                    </button>
                </div>
            </div>
        </Topbar>

        <main class="p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">{{ tabLabel }}</h1>
                <p class="text-sm text-gray-500">Workspace atual: {{ currentWorkspace.name }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div v-for="stat in stats" :key="stat.label" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg" :class="stat.color">
                            <Icon :name="stat.icon" class="h-[18px] w-[18px]" />
                        </div>
                        <span class="text-xs font-semibold text-green-600">{{ stat.delta }}</span>
                    </div>
                    <p class="mt-4 text-2xl font-bold text-gray-900">{{ stat.value }}</p>
                    <p class="text-sm text-gray-500">{{ stat.label }}</p>
                </div>
            </div>
        </main>
    </div>
</template>
