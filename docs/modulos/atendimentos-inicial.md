# Módulo de atendimentos inicial

## Objetivo

Este documento registra o primeiro slice real do domínio operacional já implementado no projeto. A ideia é descrever o módulo a partir do código atual, deixando explícitos:

- quais entidades participam do fluxo
- como o backend organiza os contratos da API
- como o frontend consome esse módulo
- quais hipóteses já antecipam tenant, ACL e evolução para integrações

## Leitura funcional do módulo

O módulo cobre o ciclo mínimo de atendimento operacional:

- abertura de atendimento
- entrada em fila operacional
- consulta da fila com filtros básicos
- visualização de detalhe do atendimento
- atualização de status
- atribuição de responsável
- trilha de eventos para rastreabilidade

Com a autenticação inicial já introduzida nesta fase, o desenho passa a sustentar alguns conceitos importantes:

- cada atendimento nasce com `tenant_id`
- cada atendimento pertence a uma fila
- mudanças relevantes geram evento histórico
- o frontend trabalha sobre contratos versionados em `/api/v1`

## Entidades do módulo

### Atendimento

Representa a ocorrência operacional tratada pela central.

Campos persistidos no backend:

- `id`
- `tenant_id`
- `protocol`
- `title`
- `description`
- `type`
- `origin`
- `priority`
- `status`
- `queue_id`
- `assigned_to`
- `created_by`
- `resolution_notes`
- `opened_at`
- `first_response_at`
- `resolved_at`
- `created_at`
- `updated_at`

Enums usados pelo modelo:

- `type`: `incident`, `request`, `integration`, `financial`
- `origin`: `manual`, `erp`, `pdv`, `portal`, `api`
- `priority`: `low`, `medium`, `high`, `critical`
- `status`: `open`, `in_progress`, `waiting_external`, `resolved`, `cancelled`

### Fila operacional

Agrupa os atendimentos por contexto de trabalho da operação.

Campos principais:

- `id`
- `tenant_id`
- `name`
- `code`
- `description`
- `active`
- `created_at`
- `updated_at`

### Evento do atendimento

Materializa a rastreabilidade mínima do fluxo.

Campos principais:

- `id`
- `attendance_id`
- `tenant_id`
- `type`
- `description`
- `metadata`
- `created_by`
- `created_at`

Eventos atualmente gerados:

- `created`
- `assigned`
- `status_changed`
- `legacy_sync_requested`
- `legacy_sync_processed`

### Operador atribuível

Nesta fase, a atribuição usa a tabela padrão de usuários do Laravel como base para operadores.

Uso atual:

- alimentar o seletor de responsável no frontend
- validar `assigned_to` em `PATCH /api/v1/attendances/{id}/assignment`

Essa modelagem continua intencionalmente simples, mas já está vinculada ao tenant do usuário autenticado e agora também conversa com a ACL inicial do projeto para filtrar ações por papel e por recurso.

## Fluxo implementado

### 1. Abertura do atendimento

O operador informa:

- título
- descrição
- tipo
- origem
- prioridade
- fila

No backend:

- o protocolo é gerado automaticamente
- o status inicial sempre nasce como `open`
- `opened_at` é preenchido no momento da criação
- um evento `created` é registrado
- se houver responsável inicial, um evento `assigned` também é registrado
- quando a integração assíncrona estiver habilitada, um evento `legacy_sync_requested` é registrado e um job é publicado no RabbitMQ após o commit da transação

### 2. Autenticação e contexto

Antes de consumir o módulo, o frontend executa `POST /api/v1/auth/login`.

Depois do login:

- o backend emite um token `Bearer` com Laravel Passport
- o frontend carrega `GET /api/v1/auth/me`
- o tenant inicial passa a ser resolvido a partir do usuário autenticado
- as consultas do módulo retornam apenas dados do tenant ativo

### 2.1. Propagação assíncrona para legado

Após a abertura do atendimento, o backend pode publicar um job assíncrono para sincronização legada.

Nesta primeira implementação:

- a API não espera a integração externa terminar para responder
- o job é enfileirado apenas depois do commit do atendimento
- o worker registra `legacy_sync_processed` quando conclui o processamento
- a trilha de eventos deixa explícita a diferença entre intenção de integração e processamento efetivo

### 3. Consulta da fila

O frontend consome `GET /api/v1/attendances` com filtros opcionais:

- `status`
- `priority`
- `queue_id`

Esse retorno alimenta:

- cards com métricas rápidas
- tabela principal da fila
- seleção do atendimento ativo para o painel lateral de detalhe

### 4. Visualização do detalhe

Ao selecionar um atendimento, o frontend consulta `GET /api/v1/attendances/{id}` e recebe:

- dados principais do atendimento
- fila relacionada
- responsável atual, quando existir
- linha do tempo de eventos

### 5. Atualização de status

O frontend envia `PATCH /api/v1/attendances/{id}/status`.

Regras atuais:

- `status` é obrigatório e validado por enum
- `resolution_notes` passa a ser obrigatório quando o novo status é `resolved`
- ao mover para `in_progress`, o backend registra `first_response_at` na primeira movimentação
- ao mover para `resolved`, o backend registra `resolved_at`
- toda mudança gera evento `status_changed`

### 6. Atribuição de responsável

O frontend consulta `GET /api/v1/users` para montar a lista de operadores e depois envia `PATCH /api/v1/attendances/{id}/assignment`.

Regras atuais:

- `assigned_to` é obrigatório
- o usuário informado precisa existir na tabela `users`
- a alteração gera evento `assigned`

## Contratos atuais da API

Base: `/api/v1`

### `GET /status`

Objetivo:

- healthcheck simples da API
- exibir metadados básicos no frontend

### `GET /queues`

Objetivo:

- listar filas disponíveis para operação e cadastro

Resposta por item:

```json
{
  "id": 1,
  "tenant_id": 1,
  "name": "Integrações",
  "code": "INT",
  "description": "Fila responsável por integrações críticas.",
  "active": true,
  "waiting_count": 3,
  "created_at": "2026-04-21T12:00:00Z",
  "updated_at": "2026-04-21T12:00:00Z"
}
```

### `GET /users`

Objetivo:

- listar operadores disponíveis para atribuição no frontend

Resposta por item:

```json
{
  "id": 1,
  "name": "Test User",
  "email": "test@example.com"
}
```

### `POST /auth/login`

Objetivo:

- autenticar o operador com Laravel Passport
- iniciar o contexto de tenant da SPA

Payload:

```json
{
  "email": "test@example.com",
  "password": "password"
}
```

### `GET /auth/me`

Objetivo:

- recuperar o usuário autenticado e o tenant ativo pelo token atual

### `POST /auth/logout`

Objetivo:

- revogar o token atual e encerrar a sessão da SPA

### `GET /attendances`

Objetivo:

- listar atendimentos da fila operacional

Parâmetros opcionais:

- `status`
- `priority`
- `queue_id`

Observação:

- a resposta é paginada pelo Laravel
- o backend filtra por `tenant_id` do usuário autenticado

Resposta por item:

```json
{
  "id": 10,
  "tenant_id": 1,
  "protocol": "AT-500001",
  "title": "Webhook sem retorno",
  "description": "Integração financeira sem confirmação.",
  "type": "integration",
  "type_label": "Integração",
  "origin": "api",
  "origin_label": "API",
  "priority": "critical",
  "priority_label": "Crítica",
  "status": "open",
  "status_label": "Aberto",
  "resolution_notes": null,
  "queue": {
    "id": 1,
    "name": "Integrações",
    "code": "INT"
  },
  "assignee": {
    "id": 1,
    "name": "Test User",
    "email": "test@example.com"
  },
  "assigned_to": 1,
  "created_by": 1,
  "opened_at": "2026-04-21T12:00:00Z",
  "first_response_at": null,
  "resolved_at": null,
  "created_at": "2026-04-21T12:00:00Z",
  "updated_at": "2026-04-21T12:00:00Z"
}
```

### `POST /attendances`

Objetivo:

- abrir uma nova ocorrência operacional

Payload:

```json
{
  "title": "Falha de sincronização com ERP",
  "description": "O payload de faturamento não retornou confirmação.",
  "type": "integration",
  "origin": "erp",
  "priority": "high",
  "queue_id": 1,
  "assigned_to": 1
}
```

Validações principais:

- `title`, `description`, `type`, `origin`, `priority` e `queue_id` são obrigatórios
- `tenant_id` não vem mais do frontend
- o tenant é resolvido pelo backend a partir do usuário autenticado
- `assigned_to` é opcional e precisa existir em `users` do mesmo tenant

### `GET /attendances/{id}`

Objetivo:

- obter o detalhe completo de um atendimento

Comportamento:

- carrega fila
- carrega responsável atual, quando existir
- carrega eventos
- exige `attendances.view`
- retorna `404` quando o recurso pertence a outro tenant

### `PATCH /attendances/{id}/status`

Payload:

```json
{
  "status": "resolved",
  "resolution_notes": "Fila normalizada após reprocessamento."
}
```

Regras:

- `status` é obrigatório
- `resolution_notes` é obrigatório quando `status = resolved`
- exige `attendances.update_status` na rota
- aplica autorização por recurso para diferenciar atualização comum, resolução e cancelamento
- operador só pode atualizar quando o atendimento está sem responsável ou atribuído a ele
- atendimentos encerrados não podem voltar ao fluxo operacional

### `PATCH /attendances/{id}/assignment`

Payload:

```json
{
  "assigned_to": 1
}
```

Regras:

- `assigned_to` é obrigatório
- o operador precisa existir
- exige `attendances.assign`
- atendimentos encerrados não podem ser reatribuídos

### `GET /attendances/{id}/events`

Objetivo:

- listar somente a trilha histórica do atendimento

Uso previsto:

- timeline dedicada
- auditoria
- futura composição de painéis ou reprocessamentos

Comportamento atual:

- exige `attendances.view`
- respeita o escopo do tenant autenticado

## Como o frontend usa o módulo

Na implementação atual do `front/`:

- `src/services/attendanceService.js` centraliza as chamadas HTTP do módulo
- `src/views/OperationalQueueView.vue` concentra a tela operacional inicial
- a UI reaproveita a base visual do Adminator, mas já adapta a página para consumo orientado a dados

Essa organização ajuda a sustentar uma leitura arquitetural importante:

- o componente compõe a experiência da tela
- o service concentra contratos HTTP
- a tela já começa a separar criação, listagem, detalhe e ações operacionais

## Hipóteses estruturais já consideradas

### Tenant

O módulo já grava `tenant_id` e agora resolve o contexto inicial pelo usuário autenticado. Nesta fase:

- o tenant é associado ao usuário autenticado
- seeders e testes usam um tenant inicial previsível para simplificar o ambiente
- a próxima etapa deve aprofundar troca de contexto, isolamento e políticas por tenant

### ACL

O módulo já possui autorização real em duas camadas:

- middleware de permissão nas rotas
- `AttendancePolicy` para decisões dependentes do recurso e do estado atual

Na prática, isso já cobre:

- visualização do atendimento e do histórico apenas para usuários autorizados
- bloqueio de alteração de status por operador fora da responsabilidade do atendimento
- bloqueio de resolução para papéis sem permissão específica
- bloqueio de reatribuição em atendimentos encerrados
- negação por tenant antes de qualquer ação sobre o recurso

### Legado e integrações

Os enums `type` e `origin`, além do desenho do histórico, já deixam o módulo pronto para crescimento em cenários como:

- incidentes de ERP
- ocorrências vindas de API
- falhas de PDV
- solicitações abertas por portal corporativo

## Testes que sustentam o módulo

O backend já possui testes de feature cobrindo:

- autenticação com Passport
- recuperação do usuário autenticado e tenant ativo
- logout
- criação de atendimento
- validação de campos obrigatórios
- listagem com fila relacionada e escopo por tenant
- visualização de detalhe com eventos
- atualização de status com exigência de `resolution_notes`
- bloqueio de atualização por operador não responsável
- bloqueio de resolução por papel sem permissão
- atribuição de responsável
- bloqueio de reatribuição em atendimento encerrado
- retorno `404` ao acessar recursos de outro tenant
- listagem de filas com contagem no tenant autenticado
- listagem de operadores para atribuição no tenant autenticado

## Próximos passos recomendados

Dentro da mesma feature, a ordem mais coerente de evolução continua sendo:

1. consolidar este módulo e sua documentação
2. fechar a etapa atual com revisão final de testes e narrativa pública
3. ampliar o estado compartilhado do frontend para permissões e contexto operacional
4. só então preparar a integração da branch em `develop`
