# Invue — roadmap para paridade com o Filament

Levantamento do que falta pro Invue cobrir o mesmo terreno que o Filament
cobre hoje. Não é uma lista de "features bacana de ter" — é organizada por
quanto cada peça destrava (ou bloqueia) as outras.

**Status (2026-08-27):** o Tier 1 inteiro está feito — #1–#3 (Tier 0),
#4 (Relation Managers), #5 (notificações persistidas), #6 (Infolists) e
#8 (ações de tables), todos implementados, testados via Playwright no
`sandbox/` (`/admin/posts`, o CRUD gerado por `make:invue-resource`,
agora com uma aba de Comments editável inline, um sininho de
notificações persistidas no Topbar, e uma página só-leitura em
`/admin/posts/{post}`) e documentados nos READMEs de `invue/actions`,
`invue/tables`, `invue/panels`, `invue/notifications` e
`invue/infolists`. Só falta #7 (Widgets/Dashboard, prioridade menor) pra
fechar o Tier 1 inteiro. Os Tiers 2–3 continuam como descritos abaixo —
não foram tocados ainda.

## Onde o Invue já está sólido

- **`invue/forms`** — 12 campos (`TextInput`, `Textarea`, `Select`,
  `Checkbox`, `CheckboxGroup`, `RadioGroup`, `ToggleButtons`, `TagsInput`,
  `FileUpload`, `Repeater`, `KeyValue`, `Hidden`), contrato uniforme
  (`v-model` + `useInvueField` + validação server-side).
- **`invue/tables`** — `Table` + colunas/filtros, tudo refazendo a busca no
  servidor a cada mudança de estado (sem re-filtrar página já carregada).
- **`invue/panels`** — `Sidebar`/`Topbar`/`PanelLayout`, customizáveis em
  duas camadas (props/slots pra reskin, registry pra troca estrutural), e
  `Panel::make()`/`Resource` pra scaffolding de CRUD.
- **`invue/notifications`** — toast efêmero (PHP builder + `Notify`
  client-only), duas peças trocáveis via registry.

## O que não pode se perder ao crescer

O diferencial real do Invue não é ter as mesmas features do Filament — é
que cada peça de UI é **Base + wrapper resolvido pelo registry**
(`registry.register('<pkg>.<Nome>', Componente)`), então trocar até o
Sidebar inteiro nunca exige fork. E o lado PHP fica estritamente
dado/trigger — nunca decide como algo renderiza. Toda peça nova abaixo
deveria nascer seguindo essas duas regras, não como exceção.

---

## Tier 0 — Fundacional (destrava o resto)

### 1. Sistema de Actions — ✅ feito (2026-08-27)
Novo pacote `invue/actions` (`ActionButton`, `ActionGroup`, `ConfirmationModal`
— Base+wrapper+registry, como todo o resto). Sem builder PHP — uma Action é
um objeto de props (`{ label, icon, color, url, method, data, visible,
requiresConfirmation, ... }`), autorada onde toda UI do Invue já é
autorada. Ver `.claude/skills/invue/actions/README.md`.

**O maior buraco.** No Filament, `Action`/`ActionGroup` é a abstração
central — botão de linha numa tabela, ação em massa, ação no header de
uma página, botão dentro de uma notificação: tudo a mesma peça (ícone,
cor, `requiresConfirmation()`, formulário embutido no modal). Hoje cada
pacote do Invue resolve "botão que faz algo" sozinho: `notifications`
precisou do slot `#actions` como gambiarra porque não existe um conceito
de Action reutilizável; `tables` não tem nenhuma ação de linha/em massa
(confirmado — não existe `bulkAction`/`rowAction` em lugar nenhum do
pacote).

Sem isso, cada pacote novo reinventa "botão com confirmação" do zero.
Resolver uma vez destrava #8 (ações de tabela) e turbina #4
(relation managers precisam de ações tipo "attach"/"detach").

### 2. Navegação de Panel mais profunda — ✅ parcial (2026-08-27)
Grupos (`item.group`, cabeçalho por grupo, ordem de primeira aparição) e
badge por item (`item.badge`/`badgeColor`, `Resource::getNavigationBadge()`)
implementados em `Base/Sidebar.vue`. Ainda faltam: grupos colapsáveis,
sub-navegação, e busca global no Topbar — não confundir "feito" com
"esgotado".

O item de navegação já carrega um campo `group` no shape
(`{ label, icon, group, url }`), mas `Base/Sidebar.vue` não renderiza
agrupamento nenhum — é só um `v-for` plano (confirmado lendo o
componente). Falta: cabeçalhos de grupo colapsáveis, badge no item,
sub-navegação, e busca global no Topbar. É a diferença entre "casca
bonita" (que já existe) e "navegação de admin real".

### 3. Camada de autorização — ✅ parcial (2026-08-27)
`Invue\Tables\TableQuery::authorize(['delete' => fn ($row) => ...])` anota
cada linha serializada com um mapa `_can`, que uma Action (`visible:
row._can.delete`) já consegue ler. Isso resolve o caso concreto de tables;
ainda não existe uma integração automática de Policy no nível de
`Resource`/Panel inteiro (ex: esconder o próprio link de navegação se o
usuário não pode `viewAny`) — esse pedaço maior continua em aberto.

Nenhum pacote (`panels`, `tables`) tem qualquer integração com Policies
do Laravel (confirmado — zero ocorrência de `authorize`/`Gate::`/`policy`
em `packages/panels` e `packages/tables`). No Filament, toda Action e
todo Resource já checam `can()` automaticamente. Isso é fundacional
porque #1 (Actions) e #4 (Relation Managers) só ficam seguros pra usar em
produção se já nascerem com esse hook.

---

## Tier 1 — Alto valor, esperado num admin real

### 4. Relation Managers — ✅ feito, parcial (2026-08-27)
`TableQuery::for()` agora aceita uma `Relation` direto (`$post->comments()`
— já vem com seu próprio `where` de escopo), e um novo `RelationManager`
(card chrome: título/contador/slot `#actions`) em `invue/panels` compõe
`invue/tables` + `invue/actions` por cima disso — nada novo do zero.
Provado ponta a ponta em `/admin/posts/{post}/edit` (uma relação
`Comment belongsTo Post`, adicionar via form inline, deletar via
`ActionsColumn` com confirmação). "Parcial" porque as rotas de
create/delete da relação ainda são escritas na mão (não existe convenção
de "recurso aninhado" no `PanelManager`/`make:invue-resource` ainda — ver
`invue/panels`' README). Ver README de `invue/panels`, seção "Relation
managers".

Gerenciar uma relação (`hasMany`/`belongsToMany`) inline, numa aba da
tela de edição de um recurso — sem sair da página. É o que faz o
Filament conseguir montar "Post" com uma aba "Comentários" editável ali
mesmo. Depende de #1 (as ações attach/detach/edit dentro da relation
manager são Actions) e se apoia em `invue/tables` já existente pra
renderizar a mini-tabela.

### 5. Notificações persistidas — ✅ feito (2026-08-27)
`Notification::sendToDatabase($notifiable)` (tabela própria
`invue_notifications`, migration embutida no pacote, roda sozinha com
`php artisan migrate`), trait `HasInvueNotifications` pro model que
recebe, `Notification::databaseFor()` pra compartilhar via Inertia (mesmo
padrão do `flashed()`), e um `Bell` (registry key `notifications.Bell`)
— sino + contador + dropdown + marcar como lida, montado no slot
`#topbar` do `PanelLayout` já existente (nenhuma mudança em `panels`).
Rotas de marcar-como-lida escritas na mão, mesma postura de bulk
actions/relation managers. Achado e corrigido 1 bug real: intersection
type `Model&HasInvueNotifications` nunca dava match (trait não é
interface pra fins de `instanceof`) — ver README de `invue/notifications`.

Já documentado como gap deliberado no próprio README do pacote: só existe
o toast efêmero (`->send()`, uma request). Falta o modo persistido —
tabela `notifications`, endpoint de fetch/mark-as-read, sininho com
contador de não lidas no Topbar. Layout natural: reaproveita o Topbar já
customizável (um slot novo ou um registry key `notifications.Bell`).

### 6. Infolists — ✅ feito (2026-08-27), gerador integrado (2026-09-04)
A decisão de arquitetura resolveu sozinha assim que foi olhada de perto:
nem pacote novo duplicando formatação, nem modo `readonly` em `forms` —
`invue/tables`' `TextColumn`/`IconColumn`/etc já são componentes
standalone (`row`+`field`, sem nada table-específico), então
`invue/infolists` novo é só chrome (`Infolist` grid, `Entry` label/valor,
`Section` card) por cima deles. Provado em `/admin/posts/{post}` (nova
página `Show.vue`, ação "View" na `ActionsColumn`, rota `show` escrita na
mão — ver README de `invue/infolists`).

A rota `show` escrita na mão em `/admin/posts` era o próprio gap: nenhum
outro Resource gerado tinha como ganhar uma página de visualização sem
repetir esse trabalho manual (Controller::show(), Show.vue, e destravar
a rota — `PanelManager` excluía `show` incondicionalmente). Fechado:
`make:invue-resource` pergunta (`--view` pula a pergunta) se o Resource
deve ganhar uma Show.vue de fábrica; quando sim, `{Model}Resource` marca
`$hasView = true` e é isso que `PanelManager::registerRoutes()` lê pra
decidir se `show` fica registrada. Zero classe nova pra decidir, mesmo
padrão de metadata estático que `$navigationIcon`/`$navigationGroup` já
usam.

Páginas só-leitura (visualizar um registro sem abrir formulário editável)
— hoje o Invue só tem `forms` (editável) e `tables` (lista). Um recurso
de "visualizar" força reusar `TextInput`/`Select` num modo read-only, o
que não é o mesmo contrato. Pacote novo (`invue/infolists`?) ou um modo
`readonly` nos campos existentes — decisão de arquitetura em aberto.

### 7. Widgets / Dashboard
Blocos reutilizáveis de dashboard — cards de estatística, gráficos. Não
existe nada disso hoje; a página inicial de um Panel é responsabilidade
100% do dev. Prioridade menor que #4–6 porque não bloqueia nada, mas é a
primeira coisa que um usuário novo do Filament sente falta.

### 8. Ações de linha e em massa nas tables — ✅ feito (2026-08-27)
`ActionsColumn` (dropdown de `ActionGroup` por linha) e
`<Table selectable :bulk-actions="...">` (checkbox de seleção + `BulkActionsBar`).
Verificado ponta a ponta em `/admin/posts`: editar, deletar com confirmação,
deletar em massa, e `TableQuery::authorize()` escondendo "Delete" numa
linha publicada — tudo com o servidor como segunda linha de defesa (a
linha protegida sobrevive mesmo se selecionada e enviada no bulk). Ver
`.claude/skills/invue/tables/README.md`.

Depende diretamente de #1. Sem isso, `invue/tables` é só visualização —
não dá pra "excluir selecionados" ou "editar" a partir da própria linha
sem sair pra outra página.

---

## Tier 2 — Completude de features

### 9. Campos mais ricos
`RichEditor` (WYSIWYG), `Wizard` (formulário multi-step), `Builder`
(blocos de conteúdo flexíveis, tipo page builder). Nenhum dos três existe
hoje (confirmado — nenhuma ocorrência em `packages/forms`). `Repeater` já
cobre parte do terreno do `Builder` (é a base dele no próprio Filament),
então `Builder` pode nascer como um `Repeater` de "tipos de bloco
diferentes" em vez de algo do zero.

### 10. Import/Export
Ações de tabela pra importar/exportar (CSV/Excel) registros em massa.
Depende de #1 e #8 (é literalmente uma bulk action).

### 11. Busca global
Busca cross-resource no Topbar (hoje o Topbar já tem espaço pro `#start`
customizado, mas nenhuma busca real implementada).

### 12. Multi-tenancy
Escopo automático de tenant num Panel inteiro — nenhum traço disso em
`Panel.php`/`PanelManager.php` hoje.

### 13. Tempo real
Tables/notifications hoje só atualizam por ação explícita do usuário
(Inertia refetch) — não há polling nem integração com Echo/broadcast.

---

## Tier 3 — Ecossistema e polimento

### 14. Clusters
Agrupar múltiplos Resources sob um sub-painel de navegação — organização,
não capacidade nova.

### 15. Helpers de teste
O Filament tem um plugin Pest inteiro pra testar formulários/tabelas
(`assertFormSet`, etc.). Invue por enquanto depende de teste manual via
Playwright no sandbox — funciona, mas não escala pra quem consome o
pacote.

### 16. Biblioteca de mídia
Reaproveitar uploads já feitos (hoje `FileUpload` não tem um "escolher da
biblioteca", só upload novo).

### 17. Scaffolding mais maduro
`make:invue-panel`/`make:invue-resource` já existem — mas crescem junto
com #1/#4/#6 (o gerador de Resource vai precisar saber gerar Actions e
Relation Managers também).

### 18. Documentação pública mais ampla
Site já cobre `forms`, `tables`, `notifications`, `panels` (customização
incluída). Falta documentar `Panel::make()`/`Resource` (o lado CRUD
scaffolding do `panels`, que hoje só existe no README interno) — e cada
item novo desse roadmap precisa da mesma passada de docs quando sair do
papel.

---

## Sequenciamento sugerido

**Actions primeiro.** ✅ Feito — junto com autorização (#3) e o pedaço de
navegação (#2), exatamente como planejado: não ficou pra depois, nasceu
junto com a abstração. #8 (ações de tables) foi o "prova real" que validou
o design do pacote `invue/actions` contra um caso de uso de verdade
(`/admin/posts`) em vez de ficar só na teoria.

**#4 também feito** — mesma lógica: `invue/actions` já pronto tornou o
delete-com-confirmação da relation manager trivial de compor, não algo pra
inventar no meio do trabalho. Ficou "parcial" de propósito (rotas
aninhadas escritas na mão) em vez de parar tudo pra generalizar
`PanelManager` numa convenção de "recurso aninhado" sem um segundo caso de
uso real pra validar o design primeiro.

**#5 também feito** — independente de #4, exatamente como previsto (sem
dependência entre eles). Reaproveitou o Topbar já customizável (slot
`#topbar`) sem precisar mudar `panels` — só `notifications` cresceu.

**#6 também feito** — e resolveu de vez a dúvida arquitetural que o item
original deixava em aberto: `invue/tables`' colunas já eram componentes
`row`+`field` standalone, então "Infolists" não precisava de nenhuma
lógica de formatação nova, só do chrome (grid/label/card) por cima. É o
mesmo padrão de #4/#8 se repetindo: quanto mais peças pequenas e
genéricas já existem (`actions`, `tables`' colunas), mais barato fica
cada feature nova — ela vira composição, não implementação do zero.

**Próximo:** só sobrou #7 (Widgets/Dashboard) pra fechar o Tier 1
inteiro — prioridade menor, não bloqueia nada. Tier 2 (completude de
feature) e Tier 3 (ecossistema) continuam atrás disso — é o que faz
alguém decidir usar o Invue pra um projeto real em vez de só pra uma
demo bonita.
