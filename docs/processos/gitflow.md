# GitFlow do projeto

## Objetivo

O versionamento deste projeto deve seguir GitFlow pensando em contexto de equipe, mesmo quando apenas uma pessoa estiver implementando. O objetivo não é burocratizar o trabalho, mas criar um fluxo que simule o que costuma existir em times que mantêm evolução contínua, releases controladas e correções urgentes.

## Branches principais

- `main`: representa produção ou o código pronto para release
- `develop`: linha principal de integração do time
- `feature/*`: desenvolvimento de funcionalidades
- `release/*`: preparação de uma versão para entrega
- `hotfix/*`: correção urgente a partir de `main`

## Por que usar essa estratégia

- separa trabalho em andamento do código considerado estável
- facilita integrar várias features sem contaminar a branch principal
- cria um momento controlado para estabilização de release
- mantém um caminho claro para correções críticas em produção
- reforça um fluxo de colaboração previsível entre desenvolvedores

## Estado atual do repositório

Na etapa atual:

- o Git já foi inicializado
- a branch `main` já existe localmente e representa a última linha pronta para release
- a branch `develop` já existe localmente e segue como linha de integração
- a tag `v0.1.0` já existe e representa a primeira release consolidada
- o trabalho atual está seguindo em `feature/endurece-operacao-assincrona-release`

Isso significa que o próximo passo correto não é abrir a primeira feature nem preparar a release `0.1.0` novamente. O próximo ciclo natural do projeto é concluir a etapa atual em `feature/*`, integrar em `develop` e então abrir `release/0.2.0`.

## Próximo passo prático a partir do estado atual

Se a feature atual já estiver estabilizada e pronta para integração, siga esta sequência:

### 1. Integrar a feature em `develop`

```bash
git checkout develop
git pull origin develop
git merge --no-ff feature/endurece-operacao-assincrona-release
git push origin develop
```

Por que usar:

Consolida a etapa atual na linha de integração e preserva a feature como unidade lógica no histórico.

### 2. Abrir a próxima release

```bash
git checkout develop
git pull origin develop
git checkout -b release/0.2.0
```

Por que usar:

Separa estabilização, revisão final de documentação e preparo da entrega sem bloquear novas features futuras em paralelo.

### 3. Estabilizar e concluir a release

```bash
git add .
git commit -m "chore: prepara release 0.2.0"
```

Por que usar:

Mantém ajustes finais de release agrupados na branch certa, sem misturar estabilização com novas entregas.

## Passo a passo inicial

### 1. Inicializar o repositório local

```bash
git init
```

Por que usar:

Cria o repositório Git local e passa a registrar o histórico do projeto.

### 2. Definir a branch principal como `main`

```bash
git branch -M main
```

Por que usar:

Padroniza a branch principal com a convenção mais comum hoje e facilita integração futura com plataformas remotas.

### 3. Criar o primeiro commit da base

```bash
git add .
git commit -m "chore: estrutura inicial do projeto"
```

Por que usar:

Registra o ponto zero do projeto, incluindo infraestrutura, base Laravel, base Vue e documentação inicial.

### 4. Criar a branch `develop`

```bash
git checkout -b develop
```

Por que usar:

`develop` passa a ser a branch de integração contínua das próximas entregas. Em contexto de time, novas features não deveriam nascer diretamente de `main`.

## Fluxo para novas funcionalidades

### 1. Atualizar a branch de integração

```bash
git checkout develop
git pull origin develop
```

Por que usar:

Garante que a nova feature comece a partir da base mais recente integrada pelo time.

### 2. Criar uma branch de feature

```bash
git checkout -b feature/modulo-atendimentos
```

Por que usar:

Isola o trabalho da funcionalidade e evita mistura com outras mudanças em andamento.

### 3. Trabalhar com commits pequenos e intencionais

```bash
git add .
git commit -m "feat: adiciona endpoint inicial de atendimentos"
```

Por que usar:

Commits pequenos ajudam revisão, rastreabilidade e eventual reversão pontual.

### 4. Publicar a branch remota

```bash
git push -u origin feature/modulo-atendimentos
```

Por que usar:

Permite abrir pull request, compartilhar progresso e manter backup do trabalho.

### 5. Integrar em `develop`

Exemplo de fluxo local após aprovação:

```bash
git checkout develop
git pull origin develop
git merge --no-ff feature/modulo-atendimentos
git push origin develop
```

Por que usar:

O `--no-ff` preserva o merge da feature como unidade lógica no histórico, o que costuma ser útil para leitura do fluxo em equipe.

### 6. Encerrar a branch

```bash
git branch -d feature/modulo-atendimentos
git push origin --delete feature/modulo-atendimentos
```

Por que usar:

Mantém o repositório limpo e evita branches de trabalho já encerradas poluindo a navegação.

## Fluxo de release

Quando `develop` acumular um conjunto coerente de entregas, abrir uma branch de release:

```bash
git checkout develop
git pull origin develop
git checkout -b release/<versao>
```

Por que usar:

Essa branch serve para ajustes finais, revisão de documentação, versionamento e estabilização sem bloquear novas features futuras em paralelo.

Durante a release, exemplos de mudanças aceitáveis:

- ajustes finos
- correções pequenas
- documentação
- preparo de ambiente
- revisão de versão

Depois da estabilização:

```bash
git checkout main
git pull origin main
git merge --no-ff release/<versao>
git tag -a v<versao> -m "Release v<versao>"
git push origin main --tags
git checkout develop
git pull origin develop
git merge --no-ff release/<versao>
git push origin develop
```

Por que usar:

`main` recebe a versão liberada e `develop` recebe de volta qualquer ajuste final feito durante a estabilização.

Encerramento da branch:

```bash
git branch -d release/<versao>
git push origin --delete release/<versao>
```

### Próxima release prevista no projeto

Considerando a tag já existente `v0.1.0`, a próxima release prevista para este repositório é:

```bash
release/0.2.0
```

Ela deve ser aberta depois que a feature atual concluir pelo menos estes pontos:

- critérios operacionais explícitos para o worker assíncrono
- documentação consolidada de operação assíncrona e preparo de release
- validação completa da fase atual no Docker e no workflow de qualidade

## Fluxo de hotfix

Quando houver um problema crítico em produção, a correção deve sair de `main`, não de `develop`.

### 1. Criar a branch de hotfix

```bash
git checkout main
git pull origin main
git checkout -b hotfix/corrige-status-api
```

Por que usar:

Garante que a correção incida sobre a última versão efetivamente em produção.

### 2. Aplicar e registrar a correção

```bash
git add .
git commit -m "fix: corrige indisponibilidade do endpoint de status"
```

### 3. Integrar a correção

```bash
git checkout main
git merge --no-ff hotfix/corrige-status-api
git tag -a v0.1.1 -m "Hotfix v0.1.1"
git push origin main --tags
git checkout develop
git pull origin develop
git merge --no-ff hotfix/corrige-status-api
git push origin develop
```

Por que usar:

A correção precisa entrar tanto em `main` quanto em `develop`, para não ser perdida na próxima release.

### 4. Encerrar a branch

```bash
git branch -d hotfix/corrige-status-api
git push origin --delete hotfix/corrige-status-api
```

## Convenções recomendadas

- criar feature sempre a partir de `develop`
- criar hotfix sempre a partir de `main`
- criar release sempre a partir de `develop`
- evitar commits genéricos como `update`, `ajustes` ou `fixes`
- preferir mensagens com prefixos como `feat:`, `fix:`, `chore:`, `docs:`, `refactor:`, `test:`
- manter uma branch por assunto
- não misturar refatoração ampla com feature nova no mesmo commit

## Como vamos usar isso neste projeto

Sempre que houver avanço relevante, o trabalho deve ser pensado como se outras pessoas também estivessem integrando mudanças. Isso significa:

- partir da branch correta
- criar uma branch com nome claro
- manter commits explicáveis
- integrar de volta no fluxo certo
- registrar o motivo da estratégia adotada

Mesmo sozinho, esse processo ajuda a preservar um fluxo coerente de integração, releases e correções críticas, mantendo o projeto próximo do que se espera em ambientes com mais de um desenvolvedor.
