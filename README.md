# Central de Atendimento Operacional

Sistema web para gestão de atendimentos, filas operacionais, prioridades, SLA e integrações corporativas. O backend em Laravel expõe uma API REST versionada, enquanto o frontend em Vue oferece uma interface para acompanhamento das rotinas operacionais.

## Visão geral

Este repositório implementa uma central de atendimento operacional voltada a cenários com alto volume de solicitações, necessidade de rastreabilidade, regras de prioridade e integração com sistemas corporativos. A base foi estruturada para permitir evolução incremental, com foco em:

- API REST com versionamento e contratos estáveis
- arquitetura modular e manutenção previsível
- autenticação, ACL e isolamento por tenant
- testes automatizados em ambiente isolado
- integração entre aplicação, cache e mensageria
- execução local consistente via Docker

## O que o sistema é

A proposta do produto é funcionar como um hub operacional para tratamento de ocorrências, solicitações e pendências que não podem ficar espalhadas entre e-mail, planilhas, chats e telas de sistemas diferentes.

Na prática, o sistema concentra em um fluxo único:

- abertura e acompanhamento de atendimentos
- priorização por criticidade e SLA
- distribuição de filas por equipe ou canal
- histórico de eventos do atendimento
- governança de acesso por papel e permissão
- integração assíncrona com serviços externos e sistemas legados

Em vez de tratar apenas um "ticket", o sistema organiza o ciclo operacional de uma demanda: quem abriu, qual contexto originou o item, qual prioridade foi aplicada, quem assumiu, quais eventos aconteceram, qual prazo está em risco e qual integração precisa ser executada para concluir o processo.

## Problemas que o sistema resolve

O produto foi pensado para resolver dores comuns em operações que dependem de coordenação entre times, canais e sistemas:

- perda de contexto entre abertura, triagem, execução e retorno ao solicitante
- filas sem critério explícito de prioridade ou prazo
- baixa rastreabilidade sobre quem atuou e quando atuou
- dependência de consultas manuais em sistemas legados para concluir atendimento
- dificuldade para escalar operação sem transformar exceções em caos
- ausência de histórico operacional confiável para auditoria, gestão e melhoria contínua

## Onde esse sistema se aplica

Embora a base atual esteja descrita como central de atendimento operacional, ela pode atender diferentes domínios desde que exista uma rotina com fila, SLA, reprocessamento, integração e visibilidade operacional.

Alguns exemplos:

- varejo e operação omnichannel: tratar divergências de pedido, pagamento, entrega, devolução, estoque e falhas entre loja, ERP, PDV e e-commerce
- operações logísticas e portuárias: acompanhar pendências de embarque, documentação, liberação, eventos de transporte e exceções que exigem coordenação entre áreas
- serviços corporativos internos: centralizar solicitações entre áreas financeiras, cadastro, compliance, suporte e operação
- educação e plataformas digitais: organizar filas de integração, suporte acadêmico, incidentes de processamento e atendimento administrativo

## Relação com ERP, PDV e outros legados

O sistema não substitui um ERP, um PDV, um WMS ou um TMS. O papel dele é complementar esses sistemas quando a operação precisa de orquestração, visibilidade e tratamento de exceções.

Exemplos de uso:

- um ERP pode originar atendimentos quando identificar inconsistência cadastral, atraso de integração, falha de faturamento ou bloqueio de aprovação
- um PDV pode gerar ocorrências para divergência de pagamento, cancelamento, falha de sincronização ou ruptura de estoque entre loja física e retaguarda
- um sistema logístico pode publicar eventos de carga, embarque, atraso, documentação pendente ou reprocessamento necessário, alimentando filas operacionais específicas

Essa leitura posiciona o produto como camada de coordenação operacional entre sistemas transacionais e times responsáveis pela resolução.

## Estrutura

- `backend/`: aplicação Laravel
- `front/`: interface web em Vue com Vite
- `docker/`: imagens, configurações e suporte de infraestrutura local
- `docs/`: espaço para ADRs, diagramas e documentação evolutiva

## Estratégias técnicas adotadas

- Separação entre backend, frontend e serviços de apoio para reduzir acoplamento operacional.
- Uso de Docker Compose para padronizar o ambiente local e diminuir diferenças entre máquinas.
- Banco de dados principal e banco de testes isolados para aumentar previsibilidade na validação automatizada.
- Multi-tenant como restrição estrutural do domínio, da autorização e da navegação.
- ACL inicial com papéis, permissões e reflexo das regras no frontend.
- Redis como base para cache de leitura por tenant, com invalidação explícita.
- RabbitMQ como base para processamento assíncrono e integração desacoplada com legado.
- Documentação arquitetural em `docs/` para registrar decisões e evolução do sistema.

## Estado atual

O projeto já saiu da fase de fundação e possui um primeiro fluxo operacional funcional e protegido. Hoje a base já sustenta:

- autenticação da API com Laravel Passport
- contexto inicial de tenant carregado junto do usuário autenticado
- módulo de atendimentos com criação, listagem, detalhe, atribuição e atualização de status
- registro de eventos operacionais para auditabilidade do atendimento
- ACL inicial com papéis, permissões e bloqueio de rotas e ações
- catálogo administrativo de filas por tenant
- painel administrativo de ACL com atualização controlada de papel de usuários
- fila operacional filtrando apenas atendimentos ainda em fluxo
- stores no frontend para shell da aplicação, governança administrativa e fila operacional
- cache de leitura por tenant em visões operacionais e administrativas
- worker dedicado para processamento assíncrono de integrações

## Principais capacidades implementadas

### Backend

- API versionada em `/api/v1`
- autenticação com `auth/login`, `auth/me` e `auth/logout`
- proteção multi-tenant em leitura e mutação de recursos
- regras de ACL para atendimentos, filas e governança de usuários
- cache de leitura para `/api/v1/queues` e `/api/v1/attendances?operational_only=1`
- despacho assíncrono pós-commit para propagação de atendimento ao legado

### Frontend

- login e recuperação de sessão autenticada
- navegação protegida por rota e permissão
- tela operacional de atendimentos com filtros, detalhe, timeline e ações condicionadas
- tela administrativa de filas com criação e edição
- tela de ACL com visão da matriz e atualização de papel por tenant
- reaproveitamento de estado compartilhado para reduzir recargas desnecessárias entre telas

## Ambientes e portas

- aplicação web: `http://localhost:8088`
- frontend Vite: `http://localhost:5178`
- MySQL principal: `localhost:3310`
- MySQL de testes: `localhost:3311`
- Redis: `localhost:6381`
- RabbitMQ AMQP: `localhost:5674`
- RabbitMQ: `localhost:15674`

## Comandos principais

```bash
docker compose up -d
docker compose exec backend php artisan migrate
docker compose exec backend composer lint
docker compose exec backend php artisan test
docker compose exec backend composer test
docker compose run --rm --entrypoint sh front -lc "npm test"
docker compose run --rm --entrypoint sh front -lc "npm run build"
```

O ambiente já possui um serviço dedicado de consumo assíncrono no `docker-compose`:

```bash
docker compose up -d backend_worker
```

## Validação local antes do push

Para espelhar a esteira de qualidade localmente, usando Docker e banco de testes isolado, o projeto oferece:

```bash
./bin/pre-push-quality
```

Para instalar o hook local de `pre-push` e executar essa validação automaticamente antes de cada envio ao remoto:

```bash
git config core.hooksPath .githooks
chmod +x .githooks/pre-push bin/pre-push-quality
```

## Qualidade

O projeto já possui uma pipeline de qualidade em GitHub Actions para validar backend e frontend dentro do Docker, além de verificar o build das imagens da aplicação.

A estratégia atual combina:

- testes de feature do backend para contrato, ACL, isolamento por tenant e fluxos críticos
- testes unitários iniciais para regras isoláveis de domínio e policy
- testes de frontend para sessão, navegação, permissões e estados bloqueados
- validação local via `./bin/pre-push-quality`

Leituras úteis:

- `docs/processos/pipeline-qualidade.md`
- `docs/processos/estrategia-testes.md`
- `.github/workflows/quality.yml`

## Timeline de evolução

### Etapa 0. Fundação arquitetural

- separação entre backend Laravel e frontend Vue
- ambiente Docker com Nginx, MySQL principal, MySQL de testes, Redis e RabbitMQ
- definição da API REST versionada e documentação arquitetural inicial

### Etapa 1. Módulo inicial de atendimentos

- modelagem de filas, atendimentos e eventos
- criação, listagem, detalhe, atribuição e atualização de status
- primeira tela operacional consumindo contratos reais da API

### Etapa 2. Autenticação, tenant e testes isolados

- autenticação com Laravel Passport
- contexto inicial do tenant autenticado
- proteção de rotas no frontend
- execução de testes apenas no Docker com banco de testes separado

### Etapa 3. ACL, governança e qualidade

- ACL inicial com papéis e permissões explícitas
- governança administrativa de filas e papéis de usuário
- cobertura automatizada de backend e frontend para regras críticas
- pipeline de qualidade no GitHub Actions e `pre-push` local

### Etapa 4. Estado compartilhado, cache e mensageria

- stores do frontend para shell, governança e fila operacional
- cache de leitura por tenant com Redis e invalidação simples
- processamento assíncrono inicial com RabbitMQ para integração legada

## Próxima frente de evolução

O foco imediato do projeto é consolidar a fase atual de maturidade arquitetural. Isso envolve:

- fortalecer a estratégia de crescimento da suíte de testes
- fechar a documentação pública e técnica do uso de estado compartilhado, cache e mensageria
- evoluir a mensageria com critérios mais explícitos de retry e tratamento de falhas
- preparar o fluxo de release a partir de `develop`, `release/*` e `main`

## Direção de evolução

O projeto pode ser expandido gradualmente nas seguintes frentes:

- expansão do domínio operacional com SLA, priorização e novas visões administrativas
- evolução do cache para novos pontos de leitura estáveis
- políticas mais maduras de retry, observabilidade e dead-letter em fluxos assíncronos
- fortalecimento de análise estática e critérios de release
- evolução da esteira de entrega quando existir destino remoto confiável

## Documentação

Os artefatos de arquitetura, domínio, diagramas e decisões técnicas devem ser registrados em `docs/` conforme o sistema evolui.

Leituras já disponíveis:

- `docs/arquitetura/backend.md`
- `docs/arquitetura/frontend.md`
- `docs/arquitetura/dominio-operacional.md`
- `docs/etapa-atual.md`
- `docs/modulos/atendimentos-inicial.md`
- `docs/modulos/acl-inicial.md`
- `docs/adr/002-evolucao-acesso-dados-atendimentos.md`
- `docs/processos/pipeline-qualidade.md`
- `docs/processos/estrategia-testes.md`
