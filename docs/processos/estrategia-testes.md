# Estratégia de testes e crescimento da suíte

## Objetivo

Definir como a cobertura automatizada do projeto deve crescer sem transformar a suíte em um gargalo operacional desnecessário.

Neste projeto, qualidade não significa apenas "ter mais testes". Significa escolher o tipo certo de teste para cada risco, manter a execução previsível dentro do Docker e documentar o critério usado para cobrir fluxos críticos do domínio.

## Princípios adotados

- testar comportamento relevante antes de testar detalhe de implementação
- preservar o banco de testes isolado no ambiente containerizado
- priorizar fluxos críticos de negócio, contrato da API, ACL e isolamento por tenant
- evitar duplicidade excessiva entre backend, frontend e teste manual
- crescer a suíte por risco e valor arquitetural, não por contagem de casos
- adiar otimizações de paralelismo até existir necessidade real medida

## Regra operacional do projeto

Neste repositório, os testes continuam rodando somente dentro do Docker.

Backend:

```bash
docker compose up -d mysql_testing
docker compose exec backend composer test
```

Frontend:

```bash
docker compose run --rm --entrypoint sh front -lc "npm test"
docker compose run --rm --entrypoint sh front -lc "npm run build"
```

Validação integrada antes de `push`:

```bash
./bin/pre-push-quality
```

O backend deve usar o banco de testes do ambiente containerizado, separado do banco principal.

Quando a validação for feita fora do script `./bin/pre-push-quality`, o container `mysql_testing` precisa estar ativo antes da execução do backend.

## Pirâmide adaptada para este projeto

### 1. Testes de feature do backend

Devem cobrir principalmente:

- contrato da API REST
- autenticação
- autorização por papel e por recurso
- isolamento por tenant
- validação de payload
- transições críticas do domínio
- respostas de erro importantes para consumo do frontend

Esses testes são a camada mais valiosa hoje porque o projeto ainda está consolidando comportamento de negócio e governança.

### 2. Testes de frontend

Devem cobrir principalmente:

- renderização condicional por permissão
- guardas de rota
- fluxo autenticado
- comportamento de formulários e feedback visual
- composição entre view, componente e composable quando houver risco real de regressão

O frontend não substitui a validação de autorização no backend. Ele valida consistência de experiência e reflexo correto das regras já protegidas pela API.

### 3. Testes unitários e de baixo acoplamento

Devem crescer quando o projeto tiver mais regras encapsuladas em:

- services
- value objects
- enums com comportamento
- regras de transformação
- classes de apoio com lógica não trivial

Essa camada é importante para reduzir o peso da suíte de feature no futuro. A orientação da etapa atual é começar a abrir espaço para ela, mas sem forçar teste unitário artificial onde ainda não existe unidade relevante bem isolada.

### 4. Teste manual orientado a fluxo

Continua necessário quando a mudança afetar:

- percepção operacional da interface
- integração entre backend e frontend
- coerência de navegação por papel
- qualidade da experiência em telas administrativas

Teste manual não substitui automação, mas complementa a leitura do sistema como produto operacional.

## Critério de cobertura por tipo de risco

### Cobrir primeiro

- regras que podem gerar acesso indevido
- fluxos que podem vazar dados entre tenants
- transições que afetam rastreabilidade do atendimento
- contratos usados diretamente pelo frontend
- cenários negativos que já quebraram antes ou têm alto risco de regressão

### Cobrir com parcimônia

- variações pequenas do mesmo fluxo sem mudança real de regra
- detalhes visuais sem impacto funcional
- repetição do mesmo cenário em várias camadas sem ganho de confiança

### Evitar

- testes acoplados a estrutura interna irrelevante
- testes longos cobrindo regras demais de uma vez
- duplicidade entre testes de feature e frontend para a mesma garantia central
- aumento de cobertura só para inflar número

## Como a suíte deve crescer

### Backend

Cada novo endpoint ou mudança relevante deve responder:

1. qual contrato público mudou
2. qual regra de autorização mudou
3. qual boundary de tenant pode regredir
4. qual caso negativo precisa existir para dar confiança

Se a alteração responder "sim" a qualquer um desses pontos, ela deve nascer com teste automatizado correspondente.

### Frontend

Cada mudança em tela, navegação ou estado deve responder:

1. a ação depende de permissão
2. a tela muda conforme o payload autenticado
3. existe bloqueio visual importante para evitar operação indevida
4. o fluxo de erro ou sucesso influencia a operação

Se a resposta for positiva, a mudança deve nascer com teste da interface correspondente.

## Gestão de custo da suíte

À medida que a cobertura crescer, o custo de execução vai subir. A estratégia desta fase é controlar isso por desenho, não por atalhos prematuros.

### Decisão atual

- ainda não adotar paralelismo no backend como padrão do projeto
- primeiro organizar melhor a divisão entre teste de feature, teste unitário, teste de frontend e teste manual
- observar tempo de execução real da suíte antes de adicionar complexidade operacional

### Medição atual da suíte

Medição local desta etapa, com execução no Docker em 26/04/2026:

- backend: `38 testes` e `122 assertions` em `7.18s`
- frontend: `45 testes` em `5.49s`

Leitura atual:

- o tempo do backend ainda não justifica paralelismo
- o frontend já tem custo semelhante ao backend, então o pré-push precisa continuar sendo lido como fluxo completo de qualidade, não apenas como tempo do PHPUnit
- a principal lacuna desta etapa não era performance da suíte, mas distribuição melhor entre testes de feature e testes unitários

### Por que o paralelismo ainda não entra

- a suíte atual ainda é pequena
- o maior custo do pré-push hoje não vem só do PHPUnit, mas do fluxo completo de qualidade
- testes paralelos aumentam consumo de CPU e memória
- testes com banco exigem mais cuidado com isolamento, setup e previsibilidade

### Quando reconsiderar paralelismo

Paralelismo passa a fazer sentido quando pelo menos um destes sinais aparecer:

- a suíte de backend crescer a ponto de prejudicar o ciclo normal de trabalho
- os testes de feature passarem a concentrar grande parte do tempo total do CI
- houver volume suficiente de testes independentes para justificar a complexidade adicional

Quando isso acontecer, a decisão deve ser documentada com:

- ganho esperado de tempo
- custo de CPU e memória no CI e no ambiente local
- estratégia de banco para execução paralela
- impacto sobre debugging e reprodutibilidade

## Pré-push local e CI

Nesta fase, a orientação continua sendo manter o pré-push local como espelho da esteira principal. Isso reforça disciplina de integração e reduz a chance de descobrir regressão só no remoto.

Se a suíte ficar pesada no futuro, há três caminhos possíveis:

- manter pré-push completo e otimizar a suíte
- rodar um subconjunto local e deixar a suíte completa para o CI
- separar checks por nível de branch ou estágio da entrega

Essa decisão ainda não foi tomada porque o custo atual não justifica fragmentar a governança.

## O que esta etapa deve produzir

- política explícita de crescimento da suíte
- critério para decidir o que vira teste de feature, frontend, unitário ou manual
- documentação pública da estratégia de qualidade
- documentação privada do estudo acoplado e da narrativa técnica da etapa

## Mapa atual de cobertura e lacunas

### Backend já bem coberto

- autenticação da API e retorno do contexto autenticado
- contrato principal de atendimentos
- listagem operacional com recorte de status
- ACL administrativa de filas e papéis
- isolamento por tenant em leitura e mutação
- cenários negativos de autorização em status, resolução e atribuição

### Frontend já bem coberto

- fluxo de login e sessão autenticada
- guardas de rota
- renderização condicional por ACL
- formulários críticos do módulo de atendimentos
- governança administrativa de filas e ACL

### Lacunas prioritárias já identificadas

- ampliar testes unitários do backend para regras isoláveis de domínio e policy
- mapear critérios mínimos por tipo de alteração para evitar que novas features cresçam só em feature tests
- registrar checklists de teste manual por fluxo administrativo quando houver mudanças de navegação ou experiência operacional

### Lacunas mapeadas, mas não prioritárias nesta etapa

- análise estática mais profunda no backend
- cobertura dedicada para futuras regras de estado compartilhado no frontend
- qualquer discussão de paralelismo, fragmentação do pré-push ou cobertura percentual

## Próximos refinamentos naturais

- mapear lacunas de cobertura por módulo
- começar a introduzir testes unitários em regras mais isoláveis
- definir critérios mínimos por tipo de mudança
- medir o tempo do backend separadamente no CI antes de discutir paralelismo
- avaliar análise estática de PHP quando o volume de código justificar
