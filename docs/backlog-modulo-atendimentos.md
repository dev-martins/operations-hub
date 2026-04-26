# Backlog do primeiro módulo de atendimentos

## Objetivo da feature

A primeira `feature/*` do projeto deve transformar a fundação técnica em um fluxo real de negócio.

O objetivo inicial não é resolver todo o domínio de uma vez, mas entregar um recorte que já demonstre:

- abertura de atendimento
- classificação operacional
- entrada em fila
- visualização para operação
- rastreabilidade mínima do fluxo
- base para testes automatizados

## Nome sugerido da branch

```bash
git checkout develop
git pull origin develop
git checkout -b feature/modulo-atendimentos-inicial
```

## Escopo da primeira entrega

Esta primeira entrega deve cobrir apenas o ciclo mínimo de atendimento operacional:

- cadastrar um atendimento
- listar atendimentos em fila
- visualizar detalhes de um atendimento
- atualizar status do atendimento
- atribuir responsável
- registrar eventos básicos do histórico

Fica fora desta primeira entrega:

- autenticação completa
- ACL completa
- automação avançada de filas
- integrações reais com legados
- SLA automatizado com escalonamento
- reprocessamento assíncrono com RabbitMQ

## Entidades iniciais

### Atendimento

Representa a ocorrência operacional a ser tratada.

Campos iniciais sugeridos:

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
- `opened_at`
- `first_response_at`
- `resolved_at`
- `created_at`
- `updated_at`

### Fila

Representa a agrupação operacional responsável pelo tratamento.

Campos iniciais sugeridos:

- `id`
- `tenant_id`
- `name`
- `code`
- `description`
- `active`
- `created_at`
- `updated_at`

### Evento de atendimento

Representa a trilha histórica do atendimento.

Campos iniciais sugeridos:

- `id`
- `attendance_id`
- `tenant_id`
- `type`
- `description`
- `metadata`
- `created_by`
- `created_at`

## Regras iniciais de negócio

O primeiro slice deve ter poucas regras, mas elas precisam ser explícitas.

### Regra 1. Todo atendimento nasce vinculado a um tenant

Mesmo que a estratégia de multi-tenant ainda não esteja completa, o modelo já deve prever isolamento de contexto.

### Regra 2. Todo atendimento nasce em uma fila

Na primeira entrega, a fila pode ser definida manualmente no momento da criação ou por regra simples baseada no tipo.

### Regra 3. Prioridade deve ser padronizada

Valores iniciais sugeridos:

- `low`
- `medium`
- `high`
- `critical`

### Regra 4. Status deve refletir fase operacional

Valores iniciais sugeridos:

- `open`
- `in_progress`
- `waiting_external`
- `resolved`
- `cancelled`

### Regra 5. Mudança importante gera evento de histórico

Sempre que houver criação, alteração de status, atribuição ou troca de fila, um evento deve ser registrado.

### Regra 6. Não encerrar atendimento sem rastreabilidade mínima

Na primeira versão, isso pode significar exigir descrição de resolução ao marcar como `resolved`.

## Endpoints iniciais da API

Base sugerida:

- `GET /api/v1/queues`
- `POST /api/v1/attendances`
- `GET /api/v1/attendances`
- `GET /api/v1/attendances/{id}`
- `PATCH /api/v1/attendances/{id}/status`
- `PATCH /api/v1/attendances/{id}/assignment`
- `GET /api/v1/attendances/{id}/events`

## Casos de uso do backend

Estrutura inicial sugerida para os casos de uso:

- `CreateAttendance`
- `ListAttendances`
- `ShowAttendance`
- `ChangeAttendanceStatus`
- `AssignAttendance`
- `ListAttendanceEvents`

## Requests e validações iniciais

### Criação de atendimento

Campos obrigatórios:

- `title`
- `description`
- `type`
- `origin`
- `priority`
- `queue_id`

Campos opcionais:

- `assigned_to`

### Alteração de status

Campos obrigatórios:

- `status`

Campo condicional:

- `resolution_notes` quando `status = resolved`

### Atribuição

Campos obrigatórios:

- `assigned_to`

## Telas iniciais do frontend

O frontend deve refletir o fluxo operacional mínimo sem exagerar em complexidade.

### 1. Lista de atendimentos

Objetivo:

- mostrar a fila operacional
- permitir leitura rápida do backlog
- exibir prioridade, status, fila e responsável

Componentes esperados:

- tabela principal
- filtros por status, prioridade, fila e origem
- indicadores simples no topo

### 2. Cadastro de atendimento

Objetivo:

- abrir uma nova ocorrência operacional

Campos:

- título
- descrição
- tipo
- origem
- prioridade
- fila
- responsável inicial opcional

### 3. Detalhe do atendimento

Objetivo:

- centralizar leitura do contexto
- permitir mudança de status
- permitir atribuição
- exibir histórico de eventos

Blocos visuais:

- resumo do atendimento
- metadados operacionais
- ações rápidas
- timeline de eventos

## Testes prioritários

Esta primeira feature já deve nascer com testes representativos.

### Testes de feature

- cria atendimento com payload válido
- rejeita criação com payload inválido
- lista atendimentos
- exibe detalhe de atendimento
- altera status com regra de validação
- atribui responsável

### Testes unitários

- aplica regra de prioridade válida
- exige observação ao resolver atendimento
- registra evento ao alterar status

## Ordem recomendada de implementação

1. migrations e models iniciais
2. seeders mínimos de filas e atendimentos
3. requests, resources e rotas da API
4. casos de uso e regras principais
5. testes de feature e unitários
6. services do frontend para consumo da API
7. telas de lista, criação e detalhe
8. documentação da decisão arquitetural do módulo

## Resultado esperado da feature

Ao final desta primeira `feature/*`, o projeto já deve ser capaz de demonstrar uma operação simples, mas real:

- uma demanda entra no sistema
- ela é classificada
- vai para uma fila
- pode ser atribuída
- tem histórico rastreável
- pode ser consultada pela interface

Isso já sustenta a leitura de que o sistema resolve exceções operacionais em vez de apenas armazenar tickets.
