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
- modularização do frontend de atendimentos em componentes, composables e constantes por domínio
- catálogo administrativo de filas com criação e edição no tenant
- painel de ACL com leitura da matriz, resumo por papel e alteração de papel de usuários do tenant
- fila operacional exibindo apenas atendimentos ainda em fluxo
- detalhe do atendimento com identificação de quem abriu o registro para reforçar auditabilidade
- testes de feature cobrindo autenticação, contrato da API, isolamento por tenant e regras críticas de ACL
- testes de frontend cobrindo filtros, formulários, permissões, estados bloqueados e renderização condicional do módulo
- pipeline de qualidade no GitHub Actions com backend e frontend validados no Docker
- validação de build das imagens Docker no CI
- workflow de entrega preparado no GitHub Actions com deploy mantido desabilitado por segurança operacional
- documentação do módulo em `docs/modulos/atendimentos-inicial.md`
- documentação da ACL inicial em `docs/modulos/acl-inicial.md`
- ADR da estratégia de evolução de acesso a dados em `docs/adr/002-evolucao-acesso-dados-atendimentos.md`
- README público neutro
- estrutura de `docs/` preparada para crescer

## O que ainda não existe

- arquitetura formal de estado compartilhado no frontend
- deploy automatizado ativo em ambiente remoto
- fluxo Git/GitFlow inicializado no repositório

## Leitura da fase atual

O projeto já demonstra um fluxo operacional protegido por autenticação, contexto inicial de tenant, ACL aplicada em rotas e ações da interface e uma visão dedicada de atendimentos orientada ao domínio. Nesta evolução, a autorização deixou de ser apenas proteção do fluxo transacional e passou a governar também catálogos administrativos reais do tenant, como filas operacionais e distribuição de papéis de acesso.

Isso cria evidência real de que o projeto já sustenta:

- governança inicial por papel e permissão
- negação explícita de ações quando o recurso não pertence ao tenant autenticado
- bloqueio de mudança de status, resolução e reatribuição em cenários indevidos
- proteção do frontend por ACL refletida a partir do payload autenticado
- distinção entre autoria de abertura e responsabilidade operacional atual
- recorte operacional coerente, sem misturar itens encerrados à fila ativa
- manutenção do catálogo de filas sem sair do contexto multi-tenant
- atualização controlada de papel de usuários com restrição explícita para evitar autoalteração indevida

## Próximo passo recomendado

Com a pipeline de qualidade já materializada e a trilha de entrega preparada, o avanço mais coerente agora é:

1. aprofundar policies e regras de autorização por recurso para módulos administrativos
2. avaliar estado compartilhado mais explícito no frontend para contextos administrativos reutilizados
3. ativar a publicação de imagem e o deploy quando houver registry, ambiente alvo e credenciais segregadas

## Leitura recomendada para a próxima fase

A etapa atual já abriu a infraestrutura mínima de qualidade. O próximo ganho de maturidade está em usar essa base para endurecer critérios de arquitetura, integração e governança.

O módulo atual já permite:

- registrar uma ocorrência operacional
- classificar origem, tipo e prioridade
- encaminhar para uma fila
- registrar eventos de tratamento
- exibir essa fila no frontend
- atualizar status
- atribuir responsável
- administrar filas do tenant
- redistribuir papéis de acesso no tenant com governança explícita

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
- listagem operacional ignorando atendimentos encerrados
- retorno `404` para recursos de outro tenant
- criação e atualização de filas apenas para papel com `queues.manage`
- atualização de papel de usuário apenas para papel com `acl.manage`
- bloqueio de alteração do próprio papel na governança administrativa
- execução da verificação de estilo do backend com Pint dentro do Docker
- validação automatizada do backend e do frontend no workflow de qualidade
- validação do build das imagens Docker no CI

Cenários já cobertos no frontend:

- renderização condicional da tela de atendimentos
- estado vazio do detalhe e da timeline
- bloqueio visual por ACL em mudança de status e reatribuição
- filtros da visão dedicada
- formulários de status e atribuição
- sessão autenticada, roteamento protegido e fluxo de login
- estado bloqueado da governança de filas sem permissão administrativa
- criação e edição de filas na visão administrativa
- governança do tenant na tela de ACL com atualização de papel e modo somente leitura

Neste projeto, a execução dos testes continua sendo feita somente dentro do Docker e usando o banco de testes isolado do ambiente containerizado:

```bash
docker compose up -d
docker compose exec backend composer lint
docker compose exec backend composer test
docker compose run --rm --entrypoint sh front -lc "npm test"
docker compose run --rm --entrypoint sh front -lc "npm run build"
```
