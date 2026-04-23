# Etapa atual do projeto

## Fase

Primeiro módulo de domínio implementado com autenticação, contexto de tenant, ACL operacional e testes de feature já acoplados ao fluxo real.

## O que já existe

- backend Laravel criado
- frontend Vue criado
- Docker Compose com Nginx, backend, frontend, MySQL principal, MySQL de testes, Redis e RabbitMQ
- endpoint inicial de status em `/api/v1/status`
- dashboard inicial baseada em Adminator
- módulo inicial de atendimentos com filas, eventos, atualização de status e atribuição
- autenticação inicial da API com Laravel Passport
- tela de login no frontend com carregamento de usuário autenticado e tenant ativo
- rotas do módulo protegidas e filtradas pelo tenant do usuário autenticado
- ACL inicial com papéis, permissões e bloqueio de rotas no backend
- reflexo de permissões no frontend com navegação e ações condicionadas
- tela dedicada de atendimentos com paginação, detalhe e ações guiadas por ACL
- testes de feature cobrindo autenticação, contrato da API, isolamento por tenant e regras críticas de ACL
- documentação do módulo em `docs/modulos/atendimentos-inicial.md`
- documentação da ACL inicial em `docs/modulos/acl-inicial.md`
- README público neutro
- estrutura de `docs/` preparada para crescer

## O que ainda não existe

- arquitetura formal de estado compartilhado no frontend
- pipeline CI/CD configurado
- fluxo Git/GitFlow inicializado no repositório

## Leitura da fase atual

O projeto já demonstra um fluxo operacional protegido por autenticação, contexto inicial de tenant, ACL aplicada em rotas e ações da interface e uma visão dedicada de atendimentos orientada ao domínio. O ponto mais importante desta fase é que autorização e qualidade automatizada deixaram de ser promessa arquitetural: hoje já existem permissões aplicadas no backend, rules por recurso no atendimento e testes cobrindo cenários positivos e negativos do contrato público.

Isso cria evidência real de que o projeto já sustenta:

- governança inicial por papel e permissão
- negação explícita de ações quando o recurso não pertence ao tenant autenticado
- bloqueio de mudança de status, resolução e reatribuição em cenários indevidos
- proteção do frontend por ACL refletida a partir do payload autenticado

## Próximo passo recomendado

O avanço mais coerente agora é:

1. modularizar o frontend de atendimentos em componentes, composables e serviços por domínio
2. introduzir testes de frontend para permissões, estados bloqueados e renderização condicional
3. preparar a próxima etapa de filas, usuários e governança administrativa

## Leitura recomendada para a próxima fase

A próxima etapa não deve priorizar nova infraestrutura. O valor agora está em provar regras de autorização mais finas, ampliar a consistência arquitetural da ACL e fortalecer qualidade automatizada sobre o domínio já autenticado.

O módulo atual já permite:

- registrar uma ocorrência operacional
- classificar origem, tipo e prioridade
- encaminhar para uma fila
- registrar eventos de tratamento
- exibir essa fila no frontend
- atualizar status
- atribuir responsável

O próximo passo é fazer esse fluxo operar com governança mais detalhada por recurso, para que a evolução para tenant mais robusto, integrações e CI/CD aconteça sobre uma base funcional real.

## Evidência de testes nesta fase

Os testes automatizados já acompanham o módulo implementado e não estão mais restritos ao esqueleto do framework.

Cenários já cobertos no backend:

- autenticação com retorno do contexto completo do usuário autenticado
- acesso ao catálogo de ACL apenas para papéis autorizados
- criação e listagem de atendimentos dentro do tenant correto
- mudança de status com validação de resolução
- bloqueio de atualização por operador que não é responsável pelo atendimento
- bloqueio de resolução por papel sem permissão
- bloqueio de reatribuição em atendimento encerrado
- retorno `404` para recursos de outro tenant

Neste projeto, a execução dos testes continua sendo feita somente dentro do Docker e usando o banco de testes isolado do ambiente containerizado:

```bash
docker compose up -d
docker compose exec backend composer test
```
