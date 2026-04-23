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

Hoje o frontend já possui uma dashboard inicial baseada em Adminator e um consumo simples do endpoint de status da API. Ainda faltam:

- roteamento real
- estrutura formal de estado
- módulos de domínio
- autenticação
- navegação condicionada por permissões
- documentação de decisões por fluxo implementado

O próximo passo recomendado é iniciar o primeiro módulo de domínio com uma estrutura explícita de `views`, `services` e `stores`.
