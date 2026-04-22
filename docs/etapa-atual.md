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
- testes de feature cobrindo os contratos principais da API
- documentação do módulo em `docs/modulos/atendimentos-inicial.md`
- README público neutro
- estrutura de `docs/` preparada para crescer

## O que ainda não existe

- ACL implementada
- arquitetura formal de estado compartilhado no frontend
- pipeline CI/CD configurado
- fluxo Git/GitFlow inicializado no repositório

## Leitura da fase atual

O projeto já demonstra um fluxo operacional protegido por autenticação e com contexto inicial de tenant acoplado ao usuário. O próximo salto de maturidade está em aprofundar governança de permissões, fortalecer a camada de estado compartilhado do frontend e ampliar a automação de qualidade.

## Próximo passo recomendado

O avanço mais coerente agora é:

1. conectar ACL às ações de visualização, mudança de status e atribuição
2. formalizar estado compartilhado no frontend para permissões e contexto operacional mais amplo
3. preparar a integração da feature em `develop` com documentação e fluxo GitFlow registrados

## Leitura recomendada para a próxima fase

A próxima etapa não deve priorizar nova infraestrutura. O valor agora está em provar ACL, regras de autorização mais finas e consistência arquitetural sobre o domínio já autenticado.

O módulo atual já permite:

- registrar uma ocorrência operacional
- classificar origem, tipo e prioridade
- encaminhar para uma fila
- registrar eventos de tratamento
- exibir essa fila no frontend
- atualizar status
- atribuir responsável

O próximo passo é fazer esse fluxo operar com identidade, contexto e governança, para que a evolução para tenant, ACL, integrações e CI/CD aconteça sobre uma base funcional real.
