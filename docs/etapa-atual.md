# Etapa atual do projeto

## Fase

Primeiro módulo de domínio implementado com autenticação inicial e contexto de tenant.

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
- testes de feature cobrindo os contratos principais da API
- documentação do módulo em `docs/modulos/atendimentos-inicial.md`
- documentação da ACL inicial em `docs/modulos/acl-inicial.md`
- README público neutro
- estrutura de `docs/` preparada para crescer

## O que ainda não existe

- arquitetura formal de estado compartilhado no frontend
- pipeline CI/CD configurado
- fluxo Git/GitFlow inicializado no repositório

## Leitura da fase atual

O projeto já demonstra um fluxo operacional protegido por autenticação, contexto inicial de tenant e uma primeira camada de ACL aplicada em rotas e ações da interface. O próximo salto de maturidade está em aprofundar governança fina por recurso, fortalecer a camada de estado compartilhado do frontend e ampliar a automação de qualidade.

## Próximo passo recomendado

O avanço mais coerente agora é:

1. aprofundar a ACL com rules por recurso e distinções mais fortes entre supervisão e administração
2. formalizar estado compartilhado do frontend para além da sessão autenticada
3. preparar a integração da feature em `develop` com documentação e fluxo GitFlow registrados

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
