<script setup>
import { Toast } from 'invue/notifications'

// Same static-map rule as every vendor color prop: Tailwind only picks up
// literal class text, so `bg-${color}-600` would get purged in production.
const AVATAR_BG_CLASSES = {
    gray: 'bg-gray-600',
    red: 'bg-red-600',
    green: 'bg-green-600',
    blue: 'bg-blue-600',
    yellow: 'bg-yellow-600',
    amber: 'bg-amber-600',
    sky: 'bg-sky-600',
    rose: 'bg-rose-600',
    purple: 'bg-purple-600',
    pink: 'bg-pink-600',
}
</script>

<template>
    <div class="mx-auto max-w-lg p-8">
        <h1 class="mb-1 text-xl font-bold text-gray-900">Invue Notifications — Toast Demo</h1>
        <p class="mb-8 text-sm text-gray-500">
            Três variantes da mesma <code>Base/Toast.vue</code>, só trocando props/slots — nenhuma
            delas precisou de um novo componente registrado em <code>notifications.Toast</code>.
            Estes são previews estáticos (sem dismiss timer/store real).
        </p>

        <div class="space-y-6">
            <!-- 1. Default -->
            <div>
                <p class="mb-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">Padrão</p>
                <Toast title="Backup concluído" body="O backup de hoje terminou sem erros." icon="circle-check" color="green" />
            </div>

            <!-- 2. #icon customizado: avatar em vez de ícone -->
            <div>
                <p class="mb-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">
                    #icon (avatar em vez de ícone)
                </p>
                <Toast title="Novo comentário" body='Marina comentou: "Ótimo trabalho!"' color="purple">
                    <template #icon="{ color }">
                        <div
                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white"
                            :class="AVATAR_BG_CLASSES[color] ?? AVATAR_BG_CLASSES.gray"
                        >
                            M
                        </div>
                    </template>
                </Toast>
            </div>

            <!-- 3. #actions customizado: botão de desfazer -->
            <div>
                <p class="mb-2 text-xs font-semibold tracking-wide text-gray-400 uppercase">
                    #actions (botão "Desfazer")
                </p>
                <Toast title="Item movido para o lixo" body="'Relatório Q3.pdf' foi removido." icon="file-text" color="gray">
                    <template #actions="{ dismiss }">
                        <button type="button" class="mt-2 text-xs font-semibold text-gray-700 underline hover:text-gray-900" @click="dismiss">
                            Desfazer
                        </button>
                    </template>
                </Toast>
            </div>
        </div>
    </div>
</template>
