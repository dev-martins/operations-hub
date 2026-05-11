# Pipeline de qualidade e entrega preparada

## Objetivo

Estabelecer uma camada de CI que valide, a cada push e pull request, se o projeto continua íntegro do ponto de vista de estilo, testes automatizados, build da interface e build das imagens Docker.

Nesta fase, o objetivo ainda não é executar deploy automático em ambiente público. A entrega foi preparada no GitHub Actions com destino previsto para o Google Artifact Registry, mas o passo final de publicação e deploy permanece desabilitado até existir um destino confiável para publicação da imagem e um conjunto de credenciais realmente segregado.

## Princípios adotados

- manter a regra do projeto de executar validações somente dentro do Docker
- reaproveitar os mesmos comandos usados localmente
- validar backend e frontend na mesma pipeline
- falhar cedo quando estilo, teste ou build quebrarem
- separar qualidade contínua de promoção para deploy
- não expor credenciais de infraestrutura em um repositório público sem necessidade real

## Workflow configurado

O workflow está em:

- `.github/workflows/quality.yml`
- `.github/workflows/delivery-disabled.yml`

Ele roda em:

- `pull_request`
- `push` para `main`
- `push` para `develop`
- `push` para `feature/**`
- `push` para `release/**`
- `push` para `hotfix/**`

## Etapas executadas

### 1. Subida da infraestrutura base

O pipeline sobe via Docker Compose:

- `mysql`
- `mysql_testing`
- `redis`
- `rabbitmq`
- `backend`

Isso permite respeitar o mesmo modelo usado no ambiente local.

### 2. Qualidade do backend

O backend executa:

```bash
docker compose exec -T backend composer lint
docker compose exec -T backend composer test
```

Na prática, esta etapa cobre:

- formatação com Laravel Pint em modo de verificação
- testes automatizados do Laravel usando o banco de testes containerizado

### 3. Qualidade do frontend

O frontend executa:

```bash
docker compose run --rm --entrypoint sh front -lc "npm ci && npm test"
docker compose run --rm --entrypoint sh front -lc "npm ci && npm run build"
```

Na prática, esta etapa cobre:

- testes da SPA com Vitest
- build de produção da interface

### 4. Validação de build das imagens Docker

O workflow de qualidade também valida que as imagens principais continuam buildando:

```bash
docker build -f docker/backend/Dockerfile -t operations-hub/backend:<sha> .
docker build -f docker/front/Dockerfile -t operations-hub/front:<sha> .
```

Na prática, isso ajuda a capturar cedo:

- quebra de Dockerfile
- dependência de sistema ausente na imagem
- regressão que só aparece no empacotamento do container

## Workflow de entrega preparado

O workflow `.github/workflows/delivery-disabled.yml` representa a trilha de CD já estruturada para o Google Artifact Registry, mas ainda não autorizada para publicar imagem nem acionar deploy.

Ele roda em:

- `workflow_dispatch`
- `push` para `main`
- `push` para `release/**`

Nesta fase, ele:

- registra explicitamente que o deploy está desabilitado
- valida novamente o build das imagens que seriam promovidas
- exibe o destino previsto das imagens no Artifact Registry
- mantém um job de publicação condicionado por `DEPLOY_ENABLED == true`

## Destino previsto das imagens

Quando a esteira for ativada, as imagens serão publicadas no formato:

```text
<regiao>-docker.pkg.dev/<project-id>/<repositorio>/<imagem>:<sha>
```

Exemplo previsto no workflow:

```text
us-central1-docker.pkg.dev/seu-projeto-gcp/operations-hub/backend:<sha>
us-central1-docker.pkg.dev/seu-projeto-gcp/operations-hub/front:<sha>
```

## Secrets e variáveis esperados para ativação

O workflow já está preparado para autenticação no GCP via Workload Identity Federation, evitando chave JSON estática no repositório.

Secrets esperados:

- `GCP_WORKLOAD_IDENTITY_PROVIDER`
- `GCP_SERVICE_ACCOUNT_EMAIL`

Variables esperadas no GitHub:

- `GCP_REGION`
- `GCP_PROJECT_ID`
- `GCP_ARTIFACT_REGISTRY_REPOSITORY`
- `BACKEND_IMAGE_NAME`
- `FRONTEND_IMAGE_NAME`

Esses valores devem ser configurados preferencialmente em:

- `Settings` -> `Secrets and variables` -> `Actions` -> `Variables`
- ou em `Settings` -> `Environments` -> `production`, quando houver necessidade de separar por ambiente

No workflow, esses valores já são consumidos via `vars.*`.

## Onde configurar no GitHub futuramente

Mapeamento recomendado:

- Repository ou Environment Variables:
- `GCP_PROJECT_ID`
- `GCP_REGION`
- `GCP_ARTIFACT_REGISTRY_REPOSITORY`
- `BACKEND_IMAGE_NAME`
- `FRONTEND_IMAGE_NAME`

- Environment Secrets:
- `GCP_WORKLOAD_IDENTITY_PROVIDER`
- `GCP_SERVICE_ACCOUNT_EMAIL`

Organização recomendada:

- manter valores não sensíveis em `Variables`
- manter credenciais somente em `Secrets`
- usar `Environment` como `production` para proteger a etapa de entrega com regras próprias

## Por que o deploy está desabilitado

O repositório está público e, neste momento, o projeto ainda não possui simultaneamente:

- registry Artifact Registry provisionado e validado para este projeto
- ambiente alvo estável para receber deploy
- credenciais dedicadas com princípio de menor privilégio
- estratégia de rotação, revogação e isolamento por ambiente

Desabilitar o deploy nesta fase não representa ausência de CI/CD. Representa uma decisão deliberada de maturidade operacional:

- o CI já protege integração e regressão
- o CD já está desenhado e pronto para ativação
- a promoção para produção não é liberada antes da infraestrutura mínima existir
- credenciais não são adicionadas ao projeto apenas para "mostrar deploy"

## Comandos de referência para uso local

Backend:

```bash
docker compose exec backend composer lint
docker compose exec backend composer test
```

Frontend:

```bash
docker compose run --rm --entrypoint sh front -lc "npm test"
docker compose run --rm --entrypoint sh front -lc "npm run build"
```

Espelhamento completo do CI antes de `push`:

```bash
./bin/pre-push-quality
```

Hook local opcional para falhar cedo antes do envio:

```bash
git config core.hooksPath .githooks
chmod +x .githooks/pre-push bin/pre-push-quality
```

Comportamento operacional do hook local:

- roda a validação completa quando o `push` envia commits ou atualiza refs remotas
- ignora a validação quando o envio é apenas deleção de branch remota, como em `git push --delete origin feature/minha-branch`

Esse fluxo mantém a regra do projeto:

- execução local sempre via Docker
- testes do backend usando o banco de testes containerizado
- validação antes do `push`, e não depois do envio ao remoto
- eliminação de custo desnecessário quando não há artefato novo sendo promovido ao remoto

## Leitura arquitetural desta decisão

Esta pipeline inicial reforça três pontos importantes do projeto:

- qualidade deixou de ser uma prática manual e passou a ser um critério de integração
- o ambiente containerizado não é apenas conveniência local, mas base real de validação
- a separação entre backend Laravel e frontend Vue continua explícita também na automação
- dependências sensíveis de runtime, como `vendor/` e `node_modules/`, ficam isoladas em volumes nomeados para reduzir instabilidade entre host e CI

## Monorepo e separação de entrega

Nesta fase, o projeto permanece em repositório único, com `backend/` e `front/` versionados juntos.

Essa escolha não impede CI/CD nem exige separação imediata em dois repositórios. A separação relevante para a esteira está no nível de artefato e entrega:

- o backend possui fluxo próprio de lint, teste e imagem Docker
- o frontend possui fluxo próprio de teste, build e imagem Docker
- a publicação futura no Artifact Registry já considera imagens distintas para backend e frontend
- o deploy futuro pode promover cada artefato separadamente, mesmo partindo do mesmo repositório

Com isso, o projeto preserva coerência de monorepo sem perder clareza arquitetural na automação.

## Limites desta fase

Ainda não fazem parte desta etapa:

- publicação automática de imagem no Artifact Registry
- deploy automatizado em ambiente GCP ou equivalente
- análise estática mais profunda em PHP
- cobertura mínima obrigatória
- verificações de segurança de dependências
- versionamento automático de release

## Próximos refinamentos naturais

- adicionar análise estática de backend quando o projeto já tiver volume suficiente para sustentar regras mais rígidas
- definir critérios de merge vinculados ao workflow de qualidade
- ativar publicação de imagem quando o Artifact Registry estiver provisionado e os secrets estiverem configurados
- conectar o workflow de entrega a um ambiente real, preferencialmente com `environment` protegido no GitHub
- avaliar cache de dependências e otimização de tempo de execução no CI

## Leitura complementar

Para a ativação futura do Artifact Registry no GCP, consultar:

- `docs/processos/ativacao-artifact-registry-gcp.md`
