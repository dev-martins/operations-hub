# ACL inicial

## Objetivo

Formalizar a primeira camada de papéis e permissões da central operacional para que backend e frontend passem a refletir governança de acesso desde o começo do projeto.

## Papéis implementados

- `admin`: visão de ACL, leitura de usuários, leitura de filas, leitura de atendimentos, abertura de atendimento, atualização de status e atribuição
- `supervisor`: visão de ACL, leitura de usuários, leitura de filas, leitura de atendimentos, abertura de atendimento, atualização de status e atribuição
- `operator`: leitura de filas, leitura de atendimentos, abertura de atendimento e atualização de status
- `viewer`: leitura de filas e leitura de atendimentos

## Permissões iniciais

- `acl.view`
- `users.view`
- `queues.view`
- `attendances.view`
- `attendances.create`
- `attendances.update_status`
- `attendances.assign`

## Decisão de backend

A ACL inicial foi centralizada em um catálogo único no backend:

- `backend/app/Support/Acl/Role.php`
- `backend/app/Support/Acl/Permission.php`
- `backend/app/Support/Acl/AclCatalogue.php`

Essa escolha evita espalhar matrizes de acesso por controllers e permite:

- expor o papel e as permissões já no payload autenticado
- proteger rotas com middleware de permissão
- reutilizar a mesma definição para a tela de ACL

## Rotas protegidas

- `GET /api/v1/auth/acl` exige `acl.view`
- `GET /api/v1/users` exige `users.view`
- `GET /api/v1/queues` exige `queues.view`
- `GET /api/v1/attendances` e `GET /api/v1/attendances/{id}` exigem `attendances.view`
- `POST /api/v1/attendances` exige `attendances.create`
- `PATCH /api/v1/attendances/{id}/status` exige `attendances.update_status`
- `PATCH /api/v1/attendances/{id}/assignment` exige `attendances.assign`

O isolamento por tenant continua obrigatório. A ACL complementa esse filtro, não substitui o escopo multi-tenant.

## Reflexo no frontend

O frontend passou a usar as permissões retornadas pelo backend para:

- esconder rotas sem acesso
- esconder itens de navegação sem permissão
- mostrar o papel atual no topo da aplicação
- esconder ações de criação, atualização de status e atribuição quando o papel não pode executá-las
- apresentar uma tela de ACL com a matriz de papéis e permissões

## Seeders para demonstração

Esta etapa agora deixa o ambiente local com usuários previsíveis para cada papel de ACL por meio de `backend/database/seeders/AclUserSeeder.php`.

Usuários criados no tenant inicial:

- `alice.admin@example.com` com papel `admin`
- `sofia.supervisor@example.com` com papel `supervisor`
- `otavio.operator@example.com` com papel `operator`
- `vera.viewer@example.com` com papel `viewer`

Senha padrão do ambiente local:

- `password`

Essa base facilita:

- validar a diferença de navegação entre papéis
- demonstrar a tela de ACL sem depender de criação manual de usuários
- testar rapidamente negação e permissão no frontend e na API

## Testes

Os testes de feature passaram a cobrir:

- retorno do papel e das permissões no login e no `auth/me`
- acesso autorizado à visão de ACL
- bloqueio da visão de ACL para papéis sem `acl.view`
- bloqueio da listagem de usuários para papéis sem `users.view`
- bloqueio da atribuição de atendimento para papéis sem `attendances.assign`

## Execução dos testes

Neste projeto, os testes devem rodar somente dentro do Docker e usando o banco de testes do ambiente containerizado.

Comando recomendado:

```bash
docker compose exec backend composer test
```

Se o stack ainda não estiver de pé:

```bash
docker compose up -d
docker compose exec backend composer test
```

## Próximos refinamentos naturais

- separar permissões de supervisão e administração com mais granularidade
- introduzir policies por recurso quando surgirem regras dependentes do estado do atendimento
- refletir ACL também nas telas dedicadas de filas e atendimentos
- cobrir o frontend com testes automatizados quando a infraestrutura de teste da SPA entrar no projeto
