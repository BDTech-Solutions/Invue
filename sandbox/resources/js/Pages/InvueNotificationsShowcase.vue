<script setup>
import { ref } from 'vue'
import { Notifications, Notify } from 'invue/notifications'
import { Icon } from 'invue/core'

// Vitrine em cima da MESMA Base/Notifications.vue + Base/Toast.vue — tudo
// abaixo é composição via props/slots, nenhuma linha nova em packages/notifications.

const POSITIONS = [
    { id: 'top-right', label: 'Superior direita' },
    { id: 'top-left', label: 'Superior esquerda' },
    { id: 'top-center', label: 'Superior centro' },
    { id: 'bottom-right', label: 'Inferior direita' },
    { id: 'bottom-left', label: 'Inferior esquerda' },
    { id: 'bottom-center', label: 'Inferior centro' },
]
const position = ref('bottom-right')

const ITEM_ICON_BG_CLASSES = {
    gray: 'bg-gray-500',
    red: 'bg-red-500',
    green: 'bg-green-500',
    blue: 'bg-blue-500',
    yellow: 'bg-yellow-500',
    amber: 'bg-amber-500',
    sky: 'bg-sky-500',
    rose: 'bg-rose-500',
    purple: 'bg-purple-500',
    pink: 'bg-pink-500',
}

const ITEM_RING_CLASSES = {
    gray: 'ring-gray-300/60',
    red: 'ring-red-300/60',
    green: 'ring-green-300/60',
    blue: 'ring-blue-300/60',
    yellow: 'ring-yellow-300/60',
    amber: 'ring-amber-300/60',
    sky: 'ring-sky-300/60',
    rose: 'ring-rose-300/60',
    purple: 'ring-purple-300/60',
    pink: 'ring-pink-300/60',
}

function fire(kind) {
    const builder = Notify.make()

    if (kind === 'success') builder.title('Alterações salvas').body('Seu perfil foi atualizado com sucesso.').success()
    else if (kind === 'warning') builder.title('Sessão expirando').body('Você será desconectado em 2 minutos.').warning()
    else if (kind === 'danger') builder.title('Falha ao publicar').body('Não foi possível salvar o rascunho.').danger()
    else if (kind === 'info') builder.title('Nova versão disponível').body('Recarregue a página para atualizar.').info()
    else if (kind === 'purple') builder.title('Convite enviado').body('daniel@exemplo.com foi convidado para o workspace.').icon('sparkles').color('purple')

    builder.send()
}

function fireStack() {
    fire('success')
    setTimeout(() => fire('info'), 150)
    setTimeout(() => fire('purple'), 300)
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 p-8 text-gray-900">
        <div class="mx-auto max-w-2xl">
            <h1 class="mb-1 text-2xl font-bold text-gray-900">Invue Notifications — Showcase</h1>
            <p class="mb-8 text-sm text-gray-500">
                Container reposicionável (<code>position</code>) + um <code>#item</code> totalmente customizado
                (cartão "glass"), tudo por cima da mesma <code>Base/Notifications.vue</code>. Um segundo toast,
                no estilo padrão, também aparece no canto superior direito — é o
                <code>&lt;Notifications /&gt;</code> global montado uma vez em <code>app.js</code>; os dois
                containers leem a mesma store, só a aparência muda.
            </p>

            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5">
                <p class="mb-3 text-xs font-semibold tracking-wide text-gray-400 uppercase">Posição do container</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="p in POSITIONS"
                        :key="p.id"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-sm font-medium transition"
                        :class="position === p.id ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        @click="position = p.id"
                    >
                        {{ p.label }}
                    </button>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <p class="mb-3 text-xs font-semibold tracking-wide text-gray-400 uppercase">Disparar notificações</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-md bg-green-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-green-500" @click="fire('success')">
                        success()
                    </button>
                    <button type="button" class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-400" @click="fire('warning')">
                        warning()
                    </button>
                    <button type="button" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-500" @click="fire('danger')">
                        danger()
                    </button>
                    <button type="button" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-sky-500" @click="fire('info')">
                        info()
                    </button>
                    <button type="button" class="rounded-md bg-purple-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-purple-500" @click="fire('purple')">
                        convite (purple)
                    </button>
                    <button type="button" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50" @click="fireStack">
                        empilhar 3
                    </button>
                </div>
            </div>
        </div>

        <!-- Container customizado: position dinâmico + #item totalmente
             próprio (cartão glass com blur), roteado pela MESMA store/timers
             que o container padrão montado em app.js. -->
        <Notifications :position="position">
            <template #item="{ item, dismiss }">
                <div
                    class="pointer-events-auto w-80 overflow-hidden rounded-2xl border border-white/60 bg-white/70 p-4 shadow-xl ring-1 backdrop-blur-md"
                    :class="ITEM_RING_CLASSES[item.color] ?? ITEM_RING_CLASSES.gray"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white shadow-sm"
                            :class="ITEM_ICON_BG_CLASSES[item.color] ?? ITEM_ICON_BG_CLASSES.gray"
                        >
                            <Icon v-if="item.icon" :name="item.icon" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p v-if="item.title" class="text-sm font-semibold text-gray-900">{{ item.title }}</p>
                            <p v-if="item.body" class="mt-0.5 text-sm text-gray-600">{{ item.body }}</p>
                            <button type="button" class="mt-2 text-xs font-semibold text-gray-700 underline hover:text-gray-900" @click="dismiss">
                                Desfazer
                            </button>
                        </div>
                        <button type="button" class="shrink-0 text-gray-400 hover:text-gray-600" aria-label="Dismiss" @click="dismiss">
                            ✕
                        </button>
                    </div>
                </div>
            </template>
        </Notifications>
    </div>
</template>
