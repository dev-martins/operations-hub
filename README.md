# Central de Atendimento Operacional

Sistema web para gestão de atendimentos, filas operacionais, prioridades, SLA e integrações corporativas. O backend em Laravel expõe uma API REST versionada, enquanto o frontend em Vue oferece uma interface para acompanhamento das rotinas operacionais.

## Visão geral

Este repositório implementa uma central de atendimento operacional voltada a cenários com alto volume de solicitações, necessidade de rastreabilidade, regras de prioridade e integração com sistemas corporativos. A base foi estruturada para permitir evolução incremental, com foco em:

- API REST com versionamento e contratos estáveis
- arquitetura modular e manutenção previsível
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
- Redis como base para estratégias de cache e suporte a cenários de desempenho.
- RabbitMQ como suporte a fluxos assíncronos e desacoplamento entre processos.
- Documentação arquitetural em `docs/` para registrar decisões e evolução do sistema.

## Ambientes e portas

- aplicação web: `http://localhost:8088`
- frontend Vite: `http://localhost:5178`
- MySQL principal: `localhost:3310`
- MySQL de testes: `localhost:3311`
- Redis: `localhost:6381`
- RabbitMQ: `localhost:15674`

## Comandos principais

```bash
docker compose up -d
docker compose exec backend php artisan migrate
docker compose exec backend php artisan test
docker compose exec backend composer test
docker compose exec front npm install
```

## Direção de evolução

O projeto pode ser expandido gradualmente nas seguintes frentes:

- modelagem do domínio de atendimento e endpoints versionados
- gestão de tickets, filas, responsáveis e SLA
- cobertura de testes unitários, integração e feature
- filas, processamento assíncrono e políticas de retry
- cache de leitura e estratégias de invalidação
- pipeline de qualidade com lint, análise estática e testes

## Próxima etapa recomendada

O próximo avanço mais importante do projeto é sair da fundação técnica e implementar o primeiro slice real do domínio.

Esse slice deve incluir:

- cadastro e abertura de atendimentos
- classificação por tipo, origem, prioridade e tenant
- filas operacionais com regras básicas de atribuição
- histórico de eventos do atendimento
- testes de feature e unitários cobrindo o contrato inicial e as regras de prioridade

Com isso, o sistema deixa de ser apenas uma base preparada e passa a demonstrar uma solução concreta para operações que dependem de triagem, execução e integração com legado.

## Documentação

Os artefatos de arquitetura, domínio, diagramas e decisões técnicas devem ser registrados em `docs/` conforme o sistema evolui.
