# Arquitetura do Frontend

## Objetivo

O frontend em Vue deve servir à operação do sistema e, ao mesmo tempo, funcionar como camada visível da arquitetura adotada no backend. A meta não é aprofundar a discussão em detalhes isolados de HTML, CSS e JavaScript, mas deixar claro como o framework está sendo usado para compor telas, estado, navegação e integração com os contratos da API.

## Papel do Vue no projeto

O frontend deve:

- consumir a API REST versionada
- organizar o estado de interface e o estado derivado de dados remotos
- refletir ACL, tenant e fluxo operacional nas telas
- manter responsabilidades separadas entre componente, serviço, store e rota
- reutilizar a base visual do Adminator sem transformar tudo em markup estático

O frontend não deve:

- duplicar regra de negócio crítica
- esconder regras importantes apenas em comportamento visual
- assumir decisões que pertencem ao backend, como autorização real e integridade de dados

## Estrutura alvo

Estrutura recomendada para evoluir o `front/`:

- `src/router`
- `src/views`
- `src/components`
- `src/layouts`
- `src/services`
- `src/stores`
- `src/composables`
- `src/modules`

Uma divisão prática:

- `views`: páginas e composição de fluxos
- `components`: blocos reutilizáveis e widgets operacionais
- `services`: acesso HTTP e integração com endpoints
- `stores`: estado compartilhado entre áreas da aplicação
- `composables`: comportamento reutilizável e lógica de interface
- `modules`: agrupamento por domínio, como atendimentos, filas, autenticação ou administração

## Estratégia de estado

O estado deve ser pensado em três níveis:

- estado local de tela: filtros, modais, abas, paginação, ordenação
- estado compartilhado da aplicação: usuário autenticado, tenant ativo, permissões, contexto operacional
- estado remoto sincronizado com API: listas, detalhes, indicadores, configurações

O raciocínio arquitetural importante aqui é:

- nem todo dado precisa virar estado global
- estado global deve ser reservado para contexto reaproveitado em várias telas
- dados remotos precisam ter estratégia clara para loading, erro, retry e invalidação
- permissões e tenant impactam não só a renderização, mas também a navegação disponível

Na evolução atual do projeto, essa estratégia passou a ser explicitada por quatro categorias de store:

- stores de shell e contexto global: sessão autenticada, tenant ativo, permissões derivadas, status da API e navegação visível
- stores de governança compartilhada: filas administrativas, visão de ACL, feedback de mutação e sincronização de dados remotos usados em mais de uma tela administrativa
- stores de fluxo operacional: fila principal, detalhe do atendimento selecionado, formulários de mutação e sincronização do recorte ativo da operação
- stores de ciclo de sessão: limpeza coordenada de estados que não devem sobreviver a logout, perda de autenticação ou troca completa de contexto

## Integração com a API

O consumo da API deve ser centralizado em `services`, evitando chamadas HTTP espalhadas por componentes.

Pontos que a implementação deve deixar claros:

- URL base vinda de configuração de ambiente
- tratamento padronizado de erros
- possibilidade de interceptors para autenticação e telemetria
- separação entre payload bruto da API e dados transformados para a tela, quando necessário

## ACL e tenant no frontend

Mesmo que a autorização real pertença ao backend, o frontend precisa refletir contexto e permissões de forma explícita.

Isso afeta:

- menus disponíveis
- botões de ação
- visibilidade de dados
- filtros por contexto operacional
- mensagens de acesso negado ou indisponibilidade funcional

Na etapa atual, o frontend já usa o payload autenticado para:

- filtrar a navegação disponível
- bloquear acesso a rotas sem permissão
- esconder ações operacionais que o papel atual não pode executar
- exibir uma visão explícita da matriz inicial de ACL
- sustentar uma tela dedicada de atendimentos com filtros, paginação, detalhe e ações dependentes do recurso selecionado
- redefinir o shell, a governança administrativa e o fluxo operacional quando a sessão expira ou o usuário sai da aplicação

## Relação com o Adminator

O Adminator deve ser usado como base visual e estrutural, mas adaptado ao Vue.

A regra é:

- reaproveitar layouts, estilos e componentes visuais quando fizer sentido
- converter comportamento estático em componentes orientados a dados
- preferir composição de tela alinhada ao domínio, e não apenas replicação de páginas prontas

## Leitura arquitetural esperada

Uma boa leitura sobre o frontend deste projeto deve mostrar que:

- Vue foi usado como cliente estruturado da arquitetura
- a organização separa tela, estado e integração
- a interface respeita ACL e tenant
- o framework ajuda a manter previsibilidade, não apenas a renderizar HTML
- o frontend foi desenhado para conversar com contratos estáveis do backend

## Etapa atual

Hoje o frontend já sustenta um módulo operacional real com:

- autenticação inicial e hidratação da sessão
- tenant e permissões carregados a partir do payload autenticado
- navegação condicionada por ACL
- rota dedicada de atendimentos
- rota dedicada de filas com governança administrativa
- rota dedicada de ACL com resumo de papéis e usuários do tenant
- filtros, paginação e detalhe do atendimento
- formulários de status e atribuição refletindo regras de permissão
- testes automatizados para store, roteamento, composables, views e componentes críticos do módulo

Na evolução mais recente, o frontend passou a combinar dois movimentos complementares:

- reorganização do módulo de atendimentos para reduzir acoplamento da view principal
- introdução de telas administrativas de filas e ACL consumindo novos endpoints de governança

No estado atual, fica explícita a separação entre:

- `views`: composição da tela dedicada de atendimentos
- `components/attendances`: blocos visuais reutilizáveis do fluxo operacional
- `modules/attendances/composables`: orquestração do estado local da tela, carregamento remoto e ações do domínio
- `modules/attendances/constants`: opções de filtros, tons visuais e formatação compartilhada do domínio
- `services`: acesso HTTP centralizado aos endpoints da API
- `stores/authSession`: sessão autenticada, token, usuário e tenant ativo
- `stores/appShell`: contexto compartilhado do shell da aplicação, como navegação filtrada por ACL e status da API
- `stores/governanceContext`: estado administrativo compartilhado entre filas e ACL, com loading, erro, edição e reidratação dos dados remotos
- `stores/operationalQueueContext`: estado remoto e transitório da fila operacional, incluindo filtros, criação de atendimento, seleção atual e ações sobre o registro selecionado
- `stores/sessionBoundState`: orquestração de limpeza dos estados que dependem da sessão autenticada

Essa organização foi escolhida para evitar uma `view` monolítica e tornar mais fácil testar:

- renderização condicional por seleção de atendimento
- estados bloqueados por ACL
- filtros e paginação
- feedback visual após ações de status e atribuição

Também foi escolhida para deixar mais clara a fronteira entre:

- estado verdadeiramente global, que precisa sobreviver à troca de rota
- estado administrativo compartilhado entre mais de uma tela
- estado operacional compartilhado por blocos distintos da mesma visão
- estado local de uma view específica, que ainda pode continuar em composables ou `reactive` local quando não há reaproveitamento suficiente para store

Na implementação mais recente, essa fronteira ganhou duas regras operacionais explícitas:

- telas administrativas e operacionais podem reaproveitar contexto já carregado ao remontar, evitando nova busca desnecessária quando o estado continua válido
- logout, `401` e perda de autenticação limpam de forma coordenada o shell, a governança administrativa e o fluxo operacional, evitando que a interface preserve dados da sessão anterior

Isso foi materializado por carregamentos sob demanda, como `ensureQueuesOverview`, `ensureAclOverview` e `ensureOperationalView`, e por um reset centralizado de estado vinculado à sessão. O objetivo não é sofisticar o frontend sem necessidade, mas deixar visível que a aplicação tem critério sobre o que pode sobreviver à navegação e o que deve ser invalidado imediatamente.

Com isso, o frontend deixa de depender apenas de lógica distribuída em `views` e passa a mostrar de forma mais explícita como tenant, ACL, navegação e governança administrativa são coordenados pela aplicação.
