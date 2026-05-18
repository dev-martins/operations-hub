# Operação assíncrona com RabbitMQ

## Objetivo

Deixar explícito como o projeto opera hoje a fila assíncrona de integrações de atendimento e quais critérios operacionais já foram assumidos antes de avançar para cenários mais sofisticados, como dead-letter e roteamento mais granular.

## Fluxo atual

No estado atual do projeto:

- a criação de atendimento continua síncrona e transacional
- quando a integração assíncrona está habilitada, o backend registra `legacy_sync_requested`
- o job `PropagateAttendanceToLegacy` é despachado apenas após o commit da transação
- um worker dedicado consome a fila RabbitMQ e registra `legacy_sync_processed` ao concluir o processamento

Essa abordagem evita acoplar a criação do atendimento a uma integração legada no mesmo request HTTP.

## Configuração operacional atual

As variáveis do fluxo assíncrono ficam centralizadas em `backend/config/operations.php` e podem ser ajustadas por ambiente.

### Variáveis relevantes

- `ATTENDANCE_INTEGRATION_ENABLED`
- `ATTENDANCE_INTEGRATION_QUEUE_CONNECTION`
- `ATTENDANCE_INTEGRATION_QUEUE`
- `ATTENDANCE_INTEGRATION_WORKER_SLEEP`
- `ATTENDANCE_INTEGRATION_WORKER_TRIES`
- `ATTENDANCE_INTEGRATION_WORKER_TIMEOUT`

### Valores padrão atuais

- fila: `attendance-integrations`
- conexão: `rabbitmq`
- `sleep`: `1`
- `tries`: `3`
- `timeout`: `30`

## Execução local

Para subir o worker dedicado no ambiente local:

```bash
docker compose up -d backend_worker
```

O serviço já nasce com os parâmetros da fila de integração, evitando drift entre ambientes locais.

## Garantias já assumidas

- publicação do job apenas após commit bem-sucedido
- isolamento do processamento por `tenant_id`
- registro de evento de solicitação antes do consumo
- registro de evento de processamento ao concluir o job
- armazenamento de falhas em `failed_jobs`, usando a configuração padrão do Laravel

## Limites atuais

Esta etapa ainda não implementa:

- dead-letter exchange
- filas separadas por tipo de trigger
- retry com backoff customizado
- reprocessamento operacional via interface
- observabilidade dedicada do worker além de logs e `failed_jobs`

## Próximos passos naturais

- revisar política de retry quando surgirem integrações mais frágeis ou demoradas
- decidir quando vale separar filas por contexto de integração
- introduzir dead-letter quando existir necessidade real de inspeção e reprocessamento
- documentar um fluxo explícito de diagnóstico de falha operacional do worker
