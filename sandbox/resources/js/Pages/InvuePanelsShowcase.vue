<script setup>
import { ref, computed } from 'vue'
import { Sidebar, Topbar } from 'invue/panels'
import { Checkbox } from 'invue/forms'
import { Icon } from 'invue/core'

// Vitrine de estilização máxima em cima da MESMA Base/Sidebar.vue — tudo
// abaixo é composição via props/slots, nenhuma linha nova em packages/panels.
// A navegação troca de "view" no client (sem reload), então o slot #item
// controla seu próprio estado ativo em vez de depender da comparação por
// URL que o Sidebar usa por padrão.

const rawNav = [
    { id: 'dashboard', label: 'Dashboard', icon: 'layout-dashboard', group: 'Geral' },
    { id: 'analytics', label: 'Analytics', icon: 'bar-chart-3', group: 'Geral' },
    { id: 'projects', label: 'Projetos', icon: 'folder-kanban', group: 'Gerenciamento', badge: 12 },
    { id: 'team', label: 'Equipe', icon: 'users', group: 'Gerenciamento' },
    { id: 'notifications', label: 'Notificações', icon: 'bell', group: 'Conta', badge: 3 },
    { id: 'settings', label: 'Configurações', icon: 'settings', group: 'Conta' },
]

// Marca o primeiro item de cada grupo pra desenhar o rótulo da seção
// dentro do próprio slot #item — o Sidebar não sabe o que é um "grupo",
// isso é 100% decidido por quem consome o componente.
let lastGroup = null
const items = rawNav.map((item) => {
    const isFirstOfGroup = item.group !== lastGroup
    lastGroup = item.group
    return { ...item, isFirstOfGroup }
})

const activeView = ref('dashboard')
const viewLabel = computed(() => rawNav.find((i) => i.id === activeView.value)?.label ?? 'Dashboard')

const profileOpen = ref(false)

const STAT_COLOR_CLASSES = {
    green: 'bg-green-50 text-green-600',
    blue: 'bg-blue-50 text-blue-600',
    amber: 'bg-amber-50 text-amber-600',
    purple: 'bg-purple-50 text-purple-600',
}

const stats = [
    { label: 'Usuários ativos', value: '8.204', delta: '+12,4%', trend: 'up', icon: 'users', color: 'green' },
    { label: 'Receita mensal', value: 'R$ 42.318', delta: '+8,1%', trend: 'up', icon: 'bar-chart-3', color: 'blue' },
    { label: 'Projetos abertos', value: '37', delta: '-3,2%', trend: 'down', icon: 'folder-kanban', color: 'amber' },
    { label: 'Conversão', value: '4,7%', delta: '+1,0%', trend: 'up', icon: 'trending-up', color: 'purple' },
]

const activity = [
    { title: 'Novo projeto criado', desc: '"Redesign do onboarding" por Daniel', time: 'há 12 min', icon: 'folder-kanban' },
    { title: 'Membro adicionado', desc: 'Marina entrou na equipe de Design', time: 'há 1 h', icon: 'users' },
    { title: 'Meta atingida', desc: 'Conversão passou de 4%', time: 'há 3 h', icon: 'trending-up' },
]

const team = [
    { name: 'Daniel Alvarez', role: 'Product Engineer', status: 'online' },
    { name: 'Marina Costa', role: 'Product Designer', status: 'online' },
    { name: 'Rafael Souza', role: 'Backend Engineer', status: 'ausente' },
    { name: 'Julia Prado', role: 'QA', status: 'offline' },
]

const projects = [
    { name: 'Redesign do onboarding', progress: 72, color: 'green' },
    { name: 'API de pagamentos v2', progress: 41, color: 'blue' },
    { name: 'Migração para Invue Panels', progress: 88, color: 'purple' },
    { name: 'App mobile — beta', progress: 15, color: 'amber' },
]

const PROGRESS_COLOR_CLASSES = {
    green: 'bg-green-500',
    blue: 'bg-blue-500',
    purple: 'bg-purple-500',
    amber: 'bg-amber-500',
}

const STATUS_DOT_CLASSES = {
    online: 'bg-green-500',
    ausente: 'bg-amber-500',
    offline: 'bg-gray-300',
}

const chartBars = [40, 65, 50, 80, 60, 95, 70, 85, 55, 90, 75, 68]

const notifyToggles = ref({ email: true, push: false, weekly: true })
</script>

<template>
    <div class="flex h-screen bg-gray-50 text-gray-900">
        <Sidebar :items="items" selected-color="green" width="lg">
            <template #header>
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-sm shadow-green-500/30">
                        <Icon name="sparkles" class="h-[18px] w-[18px]" />
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-gray-900">Invue</p>
                        <p class="truncate text-xs text-gray-400">Painel administrativo</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="mt-4 flex w-full items-center justify-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-500 active:bg-green-700"
                >
                    <Icon name="plus" class="h-4 w-4" />
                    Novo projeto
                </button>
            </template>

            <template #item="{ item }">
                <p
                    v-if="item.isFirstOfGroup"
                    class="px-3 pt-4 pb-1.5 text-[11px] font-semibold tracking-wider text-gray-400 uppercase first:pt-1"
                >
                    {{ item.group }}
                </p>

                <button
                    type="button"
                    class="group flex w-full items-center gap-2.5 rounded-lg border-l-2 px-2.5 py-2 text-sm font-medium transition"
                    :class="
                        activeView === item.id
                            ? 'border-green-600 bg-green-50 text-green-700'
                            : 'border-transparent text-gray-600 hover:border-gray-200 hover:bg-gray-50'
                    "
                    @click="activeView = item.id"
                >
                    <Icon
                        :name="item.icon"
                        class="h-4 w-4 shrink-0 transition"
                        :class="activeView === item.id ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-500'"
                    />
                    <span class="flex-1 text-left">{{ item.label }}</span>
                    <span
                        v-if="item.badge"
                        class="rounded-full px-1.5 py-0.5 text-[11px] font-semibold"
                        :class="activeView === item.id ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-600'"
                    >
                        {{ item.badge }}
                    </span>
                </button>
            </template>

            <template #footer>
                <div class="relative">
                    <div
                        v-if="profileOpen"
                        class="absolute bottom-full left-0 mb-2 w-full overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
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

                    <button
                        type="button"
                        class="flex w-full items-center gap-2.5 rounded-lg p-2 text-left transition hover:bg-gray-50"
                        @click="profileOpen = !profileOpen"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-green-400 to-emerald-600 text-xs font-semibold text-white">
                            DA
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900">Daniel Alvarez</p>
                            <p class="truncate text-xs text-gray-400">daniel@invue.dev</p>
                        </div>
                        <Icon name="chevron-down" class="h-4 w-4 shrink-0 text-gray-400" />
                    </button>
                </div>
            </template>
        </Sidebar>

        <div class="flex min-w-0 flex-1 flex-col">
            <Topbar>
                <div class="relative hidden w-64 sm:block">
                    <Icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input
                        type="text"
                        placeholder="Buscar..."
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 py-1.5 pr-3 pl-9 text-sm text-gray-700 placeholder-gray-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                    />
                </div>

                <button type="button" class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100" @click="activeView = 'notifications'">
                    <Icon name="bell" class="h-5 w-5" />
                    <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white" />
                </button>

                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-green-400 to-emerald-600 text-xs font-semibold text-white">
                    DA
                </div>
            </Topbar>

            <main class="flex-1 overflow-y-auto p-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">{{ viewLabel }}</h1>
                    <p class="text-sm text-gray-500">Bem-vindo de volta, Daniel. Aqui está um resumo de hoje.</p>
                </div>

                <!-- Dashboard -->
                <template v-if="activeView === 'dashboard'">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div v-for="stat in stats" :key="stat.label" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-lg"
                                    :class="STAT_COLOR_CLASSES[stat.color] ?? STAT_COLOR_CLASSES.green"
                                >
                                    <Icon :name="stat.icon" class="h-[18px] w-[18px]" />
                                </div>
                                <span
                                    class="flex items-center gap-0.5 text-xs font-semibold"
                                    :class="stat.trend === 'up' ? 'text-green-600' : 'text-red-500'"
                                >
                                    <Icon :name="stat.trend === 'up' ? 'trending-up' : 'trending-down'" class="h-3.5 w-3.5" />
                                    {{ stat.delta }}
                                </span>
                            </div>
                            <p class="mt-4 text-2xl font-bold text-gray-900">{{ stat.value }}</p>
                            <p class="text-sm text-gray-500">{{ stat.label }}</p>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-3">
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
                            <div class="mb-4 flex items-center justify-between">
                                <h2 class="text-sm font-semibold text-gray-900">Receita nos últimos 12 dias</h2>
                                <span class="text-xs text-gray-400">R$ / dia</span>
                            </div>
                            <div class="flex h-40 items-end gap-2">
                                <div
                                    v-for="(value, i) in chartBars"
                                    :key="i"
                                    class="flex-1 rounded-t-md bg-gradient-to-t from-green-500 to-emerald-400 transition hover:opacity-80"
                                    :style="{ height: value + '%' }"
                                />
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <h2 class="mb-4 text-sm font-semibold text-gray-900">Atividade recente</h2>
                            <ul class="space-y-4">
                                <li v-for="entry in activity" :key="entry.title" class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-50 text-green-600">
                                        <Icon :name="entry.icon" class="h-3.5 w-3.5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900">{{ entry.title }}</p>
                                        <p class="truncate text-xs text-gray-500">{{ entry.desc }}</p>
                                        <p class="text-[11px] text-gray-400">{{ entry.time }}</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </template>

                <!-- Analytics -->
                <template v-else-if="activeView === 'analytics'">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold text-gray-900">Visitantes por dia</h2>
                        <div class="flex h-56 items-end gap-3">
                            <div
                                v-for="(value, i) in chartBars"
                                :key="i"
                                class="flex-1 rounded-t-md bg-gradient-to-t from-blue-500 to-sky-400 transition hover:opacity-80"
                                :style="{ height: value + '%' }"
                            />
                        </div>
                    </div>
                </template>

                <!-- Projetos -->
                <template v-else-if="activeView === 'projects'">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div v-for="project in projects" :key="project.name" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-sm font-semibold text-gray-900">{{ project.name }}</p>
                            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                <div
                                    class="h-full rounded-full"
                                    :class="PROGRESS_COLOR_CLASSES[project.color] ?? PROGRESS_COLOR_CLASSES.green"
                                    :style="{ width: project.progress + '%' }"
                                />
                            </div>
                            <p class="mt-1.5 text-xs text-gray-500">{{ project.progress }}% concluído</p>
                        </div>
                    </div>
                </template>

                <!-- Equipe -->
                <template v-else-if="activeView === 'team'">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div
                            v-for="member in team"
                            :key="member.name"
                            class="flex items-center gap-3 border-b border-gray-100 px-5 py-3 last:border-0"
                        >
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                                {{ member.name.split(' ').map((n) => n[0]).join('') }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ member.name }}</p>
                                <p class="text-xs text-gray-500">{{ member.role }}</p>
                            </div>
                            <span class="flex items-center gap-1.5 text-xs text-gray-500">
                                <span class="h-2 w-2 rounded-full" :class="STATUS_DOT_CLASSES[member.status]" />
                                {{ member.status }}
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Notificações -->
                <template v-else-if="activeView === 'notifications'">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div v-for="entry in activity" :key="entry.title" class="flex items-start gap-3 border-b border-gray-100 px-5 py-4 last:border-0">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-50 text-green-600">
                                <Icon :name="entry.icon" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900">{{ entry.title }}</p>
                                <p class="text-sm text-gray-500">{{ entry.desc }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ entry.time }}</p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Configurações -->
                <template v-else-if="activeView === 'settings'">
                    <div class="max-w-md rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold text-gray-900">Preferências de notificação</h2>
                        <div class="space-y-4">
                            <Checkbox v-model="notifyToggles.email" label="Notificações por e-mail" hint="Resumo diário das atividades" />
                            <Checkbox v-model="notifyToggles.push" label="Notificações push" hint="Alertas em tempo real no navegador" />
                            <Checkbox v-model="notifyToggles.weekly" label="Resumo semanal" hint="Toda segunda-feira às 9h" />
                        </div>
                    </div>
                </template>
            </main>
        </div>
    </div>
</template>
