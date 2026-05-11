# Etapa atual do projeto

## Fase

Consolidação de qualidade, testes e governança técnica sobre o primeiro módulo operacional já autenticado, multi-tenant e protegido por ACL.

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
- testes unitários iniciais cobrindo regras isoláveis de domínio e policy no backend
- testes de frontend cobrindo filtros, formulários, permissões, estados bloqueados e renderização condicional do módulo
- pipeline de qualidade no GitHub Actions com backend e frontend validados no Docker
- validação de build das imagens Docker no CI
- workflow de entrega preparado no GitHub Actions com destino previsto para Artifact Registry e deploy mantido desabilitado por segurança operacional
- script local de pré-push espelhando a esteira de qualidade via Docker
- documentação do módulo em `docs/modulos/atendimentos-inicial.md`
- documentação da ACL inicial em `docs/modulos/acl-inicial.md`
- ADR da estratégia de evolução de acesso a dados em `docs/adr/002-evolucao-acesso-dados-atendimentos.md`
- README público neutro
- estrutura de `docs/` preparada para crescer

## O que ainda não existe

- deploy automatizado ativo em ambiente remoto
- fluxo Git/GitFlow inicializado no repositório

## Leitura da fase atual

O projeto já demonstra um fluxo operacional protegido por autenticação, contexto inicial de tenant, ACL aplicada em rotas e ações da interface e uma visão dedicada de atendimentos orientada ao domínio. Nesta evolução, a autorização deixou de ser apenas proteção do fluxo transacional e passou a governar também catálogos administrativos reais do tenant, como filas operacionais e distribuição de papéis de acesso.

No recorte de entrega, a base continua organizada como monorepo, com frontend e backend separados por diretório, mas com esteiras independentes de validação e preparo de imagem. Isso permite sustentar um repositório único agora sem abrir mão de promoção separada de artefatos no futuro.

Isso cria evidência real de que o projeto já sustenta:

- governança inicial por papel e permissão
- negação explícita de ações quando o recurso não pertence ao tenant autenticado
- bloqueio de mudança de status, resolução e reatribuição em cenários indevidos
- proteção do frontend por ACL refletida a partir do payload autenticado
- distinção entre autoria de abertura e responsabilidade operacional atual
- recorte operacional coerente, sem misturar itens encerrados à fila ativa
- manutenção do catálogo de filas sem sair do contexto multi-tenant
- atualização controlada de papel de usuários com restrição explícita para evitar autoalteração indevida
- formalização inicial de estado compartilhado no frontend para shell da aplicação, governança administrativa e fluxo operacional

## Próxima fase em andamento

Após o fechamento da consolidação de testes, o projeto iniciou a etapa de formalização da arquitetura de estado compartilhado do frontend.

Nesta evolução, o objetivo deixa de ser apenas "ter telas funcionando" e passa a ser demonstrar de forma mais explícita:

- onde vive o contexto global da aplicação
- quais dados administrativos precisam sobreviver à troca de rota
- como o frontend separa sessão, navegação, governança e estado local de tela
- como a interface conversa com a API sem espalhar sincronização remota por várias views

O recorte atual dessa nova fase já começou com:

- store de shell da aplicação para navegação visível, status da API e contexto derivado da sessão
- store de governança compartilhada para filas administrativas e visão de ACL
- store de fluxo operacional para lista principal, detalhe selecionado e mutações da fila
- reaproveitamento de estado já carregado ao navegar entre telas administrativas e ao remontar a visão operacional
- critérios explícitos de invalidação de estado em logout, `401` e perda de autenticação
- redução de lógica remota diretamente dentro das views administrativas
- redução de lógica remota diretamente dentro da visão operacional principal
- testes dedicados para essas novas stores do frontend
- início do processamento assíncrono de atendimentos com RabbitMQ para integrações legadas desacopladas
- worker dedicado no Docker para consumo da fila operacional assíncrona

## Próximo passo recomendado

Com a pipeline de qualidade já materializada e a trilha de entrega preparada, o avanço mais coerente agora é consolidar a estratégia de crescimento da suíte e endurecer os critérios de cobertura. Nesta etapa, o projeto deve:

1. explicitar o critério de cobertura para backend, frontend e testes manuais
2. mapear lacunas dos fluxos críticos do domínio e da governança administrativa
3. começar a abrir espaço para testes mais leves e isolados quando surgirem unidades com regra relevante
4. medir o custo de execução da suíte antes de decidir por paralelismo ou fragmentação do pré-push
5. registrar essa decisão de qualidade em `docs/processos/estrategia-testes.md`

Esse fechamento já foi iniciado com a criação dos primeiros testes unitários do backend e com a medição explícita da suíte atual em Docker. O restante da fase deve ser lido mais como endurecimento de critério do que como ausência de cobertura automatizada relevante.

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

O próximo passo é fazer esse fluxo operar com governança de qualidade mais explícita, para que a evolução para cache, mensageria, integrações e CI/CD aconteça sobre uma base funcional real e testável.

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
- identificação unitária de status terminais e status operacionais do atendimento
- validação unitária da regra de atualização por operador responsável ou supervisão
- validação unitária de negação por tenant e por atendimento terminal no policy
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
- reaproveitamento de estado já carregado nas telas administrativas
- reaproveitamento da visão operacional sem recarga desnecessária
- limpeza coordenada do shell e dos stores ligados à sessão em logout e expiração de autenticação

Neste projeto, a execução dos testes continua sendo feita somente dentro do Docker e usando o banco de testes isolado do ambiente containerizado:

```bash
docker compose up -d
docker compose up -d mysql_testing
docker compose exec backend composer lint
docker compose exec backend composer test
docker compose run --rm --entrypoint sh front -lc "npm test"
docker compose run --rm --entrypoint sh front -lc "npm run build"
```

Também existe um fluxo único para falhar cedo antes do envio ao remoto:

```bash
./bin/pre-push-quality
```

## Fechamento objetivo da etapa

Com a fotografia atual, a fase de consolidação de testes fica substancialmente atendida porque agora o projeto já combina:

- cobertura de feature para contrato, ACL e tenant
- cobertura de frontend para sessão, navegação e bloqueios visuais
- primeiros testes unitários reais para regras isoláveis do backend
- medição concreta do custo da suíte antes de qualquer discussão sobre paralelismo
- documentação explícita das lacunas que permanecem para a próxima evolução

Na medição local final desta etapa, o backend executou `38 testes` com `122 assertions` em `7.18s`, enquanto o frontend executou `45 testes` em `5.49s`, sempre no Docker.
