# Checklist futuro de ativação do Artifact Registry no GCP

## Objetivo

Mapear, de forma operacional, o que precisa existir para ativar a publicação de imagens Docker no Google Artifact Registry a partir do GitHub Actions, sem implementar isso agora.

Este documento serve para reduzir dúvida futura e deixar claro quais etapas pertencem ao GitHub, quais pertencem ao GCP e quais pré-requisitos devem existir antes de habilitar o deploy.

## Escopo desta fase

Neste momento, o projeto:

- já possui workflow preparado para publicação no Artifact Registry
- ainda não publica imagens automaticamente
- ainda não executa deploy em ambiente remoto
- ainda mantém `DEPLOY_ENABLED` desabilitado

## Decisão de segurança adotada

Quando esta integração for ativada, a preferência deve ser:

- usar `Workload Identity Federation`
- evitar chave JSON estática no GitHub
- separar `Variables` e `Secrets`
- proteger a etapa de entrega com `Environment` dedicado

## Checklist no GCP

### 1. Criar ou definir o projeto GCP

Definir qual projeto hospedará:

- o Artifact Registry
- a conta de serviço da automação
- o ambiente de deploy futuro, se ele também ficar no GCP

Valor que depois será refletido no GitHub:

- `GCP_PROJECT_ID`

### 2. Escolher a região

Definir a região do Artifact Registry.

Exemplo:

- `us-central1`

Valor que depois será refletido no GitHub:

- `GCP_REGION`

### 3. Criar o repositório no Artifact Registry

Criar um repositório Docker para receber as imagens do projeto.

Exemplo conceitual:

- repositório: `operations-hub`

Valor que depois será refletido no GitHub:

- `GCP_ARTIFACT_REGISTRY_REPOSITORY`

### 4. Definir nomes das imagens

Mapear como as imagens serão publicadas.

Exemplo:

- `backend`
- `front`

Valores que depois serão refletidos no GitHub:

- `BACKEND_IMAGE_NAME`
- `FRONTEND_IMAGE_NAME`

### 5. Criar a service account da automação

Criar uma conta de serviço exclusiva para o GitHub Actions.

Boa prática:

- não reutilizar service account humana
- não usar conta compartilhada com outras automações sem necessidade
- nomear de forma explícita para facilitar auditoria

Exemplo conceitual:

- `github-actions-artifact-registry@<project-id>.iam.gserviceaccount.com`

Valor que depois será refletido no GitHub:

- `GCP_SERVICE_ACCOUNT_EMAIL`

### 6. Conceder permissões mínimas

Para publicação no Artifact Registry, a service account deve receber somente o necessário.

Em princípio, o papel relevante tende a ser algo na linha de:

- permissão de escrita no Artifact Registry

Se no futuro a mesma automação também fizer deploy, avaliar os papéis separadamente para não misturar publicação de imagem com administração ampla de infraestrutura.

### 7. Configurar Workload Identity Federation

Criar a federação entre GitHub Actions e GCP para permitir autenticação sem chave estática.

Isso envolve:

- criar pool de identidade
- criar provider OIDC
- vincular o provider à service account
- restringir o principal autorizado ao repositório e, se fizer sentido, ao branch ou environment

Valor que depois será refletido no GitHub:

- `GCP_WORKLOAD_IDENTITY_PROVIDER`

## Checklist no GitHub

### 1. Criar ou revisar o environment `production`

No GitHub:

- `Settings` -> `Environments` -> `production`

Usar esse environment permite:

- proteger a etapa de entrega
- separar credenciais por ambiente
- adicionar aprovação manual no futuro, se necessário

### 2. Configurar variables não sensíveis

No GitHub:

- `Settings` -> `Secrets and variables` -> `Actions` -> `Variables`

Ou, se preferir isolar por ambiente:

- `Settings` -> `Environments` -> `production`

Variables esperadas:

- `GCP_PROJECT_ID`
- `GCP_REGION`
- `GCP_ARTIFACT_REGISTRY_REPOSITORY`
- `BACKEND_IMAGE_NAME`
- `FRONTEND_IMAGE_NAME`

### 3. Configurar secrets sensíveis

Preferencialmente no environment `production`.

Secrets esperados:

- `GCP_WORKLOAD_IDENTITY_PROVIDER`
- `GCP_SERVICE_ACCOUNT_EMAIL`

Observação:

- `GCP_SERVICE_ACCOUNT_EMAIL` não é segredo no mesmo nível de uma chave privada, mas faz sentido tratá-lo junto da configuração sensível da automação para evitar exposição desnecessária e manter consistência operacional

### 4. Não configurar chave JSON se não for necessário

Enquanto o caminho com Workload Identity Federation estiver viável, evitar:

- chaves longas em `Secrets`
- credenciais permanentes
- dependência de rotação manual

## Checklist no workflow

Antes de ativar a publicação, revisar:

- se o workflow continua usando `vars.*` para valores não sensíveis
- se o workflow continua usando `secrets.*` para autenticação
- se o `environment: production` está associado ao job de entrega
- se `DEPLOY_ENABLED` ainda está controlado explicitamente

## Ponto de ativação futura

Quando tudo estiver provisionado, a ativação prática tende a ser pequena:

1. preencher `Variables`
2. preencher `Secrets`
3. validar autenticação do GitHub com o GCP
4. revisar permissões mínimas da service account
5. mudar `DEPLOY_ENABLED` para `true`
6. disparar o workflow manualmente

## Validação esperada quando ativar

O que deve ser validado no primeiro teste real:

- autenticação no GCP concluída com sucesso
- `gcloud auth configure-docker` funcionando para a região escolhida
- build da imagem do backend
- push da imagem do backend
- build da imagem do frontend
- push da imagem do frontend
- imagens visíveis no Artifact Registry com tag baseada no SHA do commit

## Riscos a revisar antes da ativação

- permissões excessivas na service account
- provider OIDC amplo demais para múltiplos repositórios
- publicação em projeto ou região errados
- nomes de imagens inconsistentes com a estratégia futura de deploy
- ativação de publicação antes de existir ambiente de consumo real

## Critério de pronto

Esta trilha pode ser considerada pronta para ativação quando:

- o projeto GCP estiver definido
- o Artifact Registry existir
- a service account estiver criada com menor privilégio
- a federação OIDC estiver funcionando
- as variables e secrets estiverem configurados no GitHub
- o time decidir conscientemente habilitar a publicação
