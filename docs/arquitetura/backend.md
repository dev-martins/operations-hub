# Arquitetura do Backend

## Objetivo

O backend em Laravel deve concentrar a regra de negócio da central de atendimento operacional e expor contratos estáveis para consumo do frontend e de integrações externas.

## Papel do Laravel no projeto

O framework já está sendo usado como base de produtividade e organização, sem concentrar toda a regra de negócio diretamente em controllers e models.

Na implementação atual, o backend já adota:

- rotas versionadas em `/api/v1`
- controllers finos, focados em entrada e saída HTTP
- validação por `FormRequest` nos fluxos de login, criação de atendimento, mudança de status e atribuição
- regras de negócio concentradas em services para criação, transição de status e registro de eventos
- middleware de permissão e policies para ACL em duas camadas
- resources para padronização das respostas da API
- cache de leitura por tenant para visões operacionais com invalidação explícita
- testes de feature cobrindo contrato, isolamento por tenant e regras críticas de autorização

## Estrutura alvo

Estrutura recomendada para a evolução do backend:

- `app/Http/Controllers/Api/V1`
- `app/Http/Requests`
- `app/Http/Resources`
- `app/Domain`
- `app/Application`
- `app/Policies`
- `app/Jobs`
- `app/Events`
- `app/Listeners`
- `app/Support`

Uma separação prática para começar:

- `Domain`: entidades, regras centrais, value objects e contratos internos
- `Application`: orquestração de casos de uso, serviços e coordenação entre domínio e infraestrutura
- `Http`: borda da aplicação, entrada e saída web
- `Support`: helpers, abstrações transversais e componentes compartilhados

## Regras de responsabilidade

- controller não decide regra de negócio complexa
- model não concentra fluxo operacional inteiro
- frontend não replica regra de autorização nem cálculo sensível
- cache deve ser aplicado como otimização, nunca como fonte de verdade
- filas devem ser usadas para processamento desacoplado, não para esconder lentidão de fluxo síncrono mal modelado

## Estratégia de evolução do acesso a dados

No estado atual, o backend ainda usa Eloquent diretamente em alguns controllers para resolver recursos dentro do tenant autenticado. Isso não invalida a arquitetura do módulo porque:

- a regra de negócio principal continua fora do controller
- a autorização continua centralizada em `policy`
- as transições operacionais seguem delegadas ao service

O principal sinal de evolução aqui não é a simples presença de model no controller, mas a duplicação dos critérios de busca por tenant em mais de um ponto da aplicação.

Por isso, a estratégia adotada para o projeto é:

- não introduzir `Repository` genérico apenas por antecipação
- extrair primeiro pontos únicos de leitura quando a duplicação começar a crescer
- separar leitura e escrita de forma mais explícita quando os casos de uso pedirem
- formalizar contratos de acesso a dados quando a abstração passar a proteger uma variação plausível da aplicação

Essa decisão foi registrada em `docs/adr/002-evolucao-acesso-dados-atendimentos.md`.

## Temas transversais que já devem influenciar o desenho

### Multi-tenant

Mesmo antes da implementação completa, o backend deve ser pensado para carregar contexto organizacional nas consultas, autorizações, filtros e eventos.

Perguntas que a arquitetura precisa responder:

- como identificar o tenant ativo
- onde esse contexto será resolvido
- como evitar vazamento de dados entre contextos
- como refletir isso em testes e seeders

### ACL

Papéis e permissões impactam:

- acesso a rotas
- ações permitidas por recurso
- exibição de botões e opções no frontend
- execução de aprovações, reprocessamentos e administração

Na etapa atual, a ACL inicial foi materializada com:

- catálogo central de papéis e permissões em `app/Support/Acl`
- payload autenticado já retornando papel e permissões do usuário
- middleware de permissão aplicado diretamente nas rotas da API
- `AttendancePolicy` registrada no `AppServiceProvider` para validar acesso por recurso
- manutenção do escopo por tenant como filtro complementar à autorização

Essa combinação já diferencia dois níveis de proteção:

- permissão de rota para bloquear acesso bruto ao endpoint
- policy por recurso para decidir o que cada papel pode fazer sobre um atendimento específico

No fluxo atual de atendimentos, isso já produz regras concretas:

- `operator` pode avançar status apenas quando o atendimento está sem responsável ou atribuído ao próprio usuário
- `operator` não resolve nem cancela atendimentos
- `supervisor` pode resolver e reatribuir atendimentos do tenant
- `admin` mantém o maior nível de governança operacional, incluindo cancelamento
- atendimentos encerrados não aceitam reatribuição nem retorno ao fluxo operacional

### Legado e integrações

Integrações com sistemas legados devem preferir adapters e serviços dedicados para evitar espalhar particularidades externas por controllers e models.

Na evolução atual, o primeiro caso real de mensageria foi introduzido na abertura do atendimento:

- o request principal continua criando o atendimento e registrando o evento síncrono de abertura
- a necessidade de propagação para legado passa a ser registrada como `legacy_sync_requested`
- a publicação do job ocorre apenas após o commit da transação
- um worker dedicado consome a fila RabbitMQ e registra `legacy_sync_processed`

Essa escolha permite mostrar um recorte arquitetural mais maduro sem transformar o fluxo principal em integração síncrona frágil.

## Leitura arquitetural esperada

Uma leitura madura deste backend deve deixar claro que:

- Laravel foi escolhido para acelerar entrega com organização clara
- a API foi versionada para preservar contratos
- a regra de negócio não fica acoplada à camada HTTP
- multi-tenant e ACL foram tratados como requisitos estruturais desde o primeiro módulo
- Redis e RabbitMQ entram como ferramentas arquiteturais, não como adereços

## Evidência atual

O backend já possui evidências técnicas que saíram do campo de plano:

- autenticação com Laravel Passport retornando contexto do usuário, tenant, papel e permissões
- rotas protegidas por middleware de permissão para ACL, usuários, filas e atendimentos
- autorização por recurso no módulo de atendimentos com `AttendancePolicy`
- isolamento de tenant aplicado na busca dos recursos antes da autorização
- registro transacional de eventos de domínio ao criar atendimento, mudar status e reatribuir responsável
- cache de leitura da visão de filas por tenant, com invalidação em criação de fila, abertura de atendimento e mudança de status
- cache de leitura da primeira visão operacional de atendimentos por tenant, restrita ao recorte `operational_only`, com invalidação simples em criação, reatribuição, mudança de status e atualização de fila
- despacho assíncrono pós-commit para propagação de atendimentos em RabbitMQ
- suíte de testes de feature cobrindo contrato da API, autenticação, ACL, isolamento por tenant e cenários negativos de autorização

Entre os cenários já cobertos em teste estão:

- retorno do catálogo de ACL apenas para papéis autorizados
- criação e listagem de atendimentos com escopo restrito ao tenant autenticado
- bloqueio de alteração de status para operador fora da responsabilidade do atendimento
- bloqueio de resolução por papel sem permissão específica
- bloqueio de reatribuição em atendimentos já encerrados
- retorno `404` quando um recurso pertence a outro tenant
- exclusão de atendimentos encerrados da fila operacional quando o recorte pede apenas itens em fluxo
- reaproveitamento de leitura cacheada em `/api/v1/queues` até haver invalidação por mudança relevante do domínio
- reaproveitamento de leitura cacheada em `/api/v1/attendances?operational_only=1` enquanto não houver mutação relevante no backlog operacional

## Etapa atual

Hoje o backend já saiu da fundação técnica e possui um primeiro fluxo operacional implementado:

- autenticação inicial com contexto de tenant
- módulo de atendimentos com criação, listagem, detalhe, mudança de status e atribuição
- ACL aplicada em rota e em recurso
- eventos de domínio registrados a cada transição relevante
- detalhe do atendimento distinguindo autor da abertura e responsável atual
- testes de feature representativos do contrato público da API

O próximo passo recomendado é expandir essa base para governança administrativa, filas e cobertura automatizada complementar, preservando o mesmo padrão de isolamento por tenant e autorização explícita.
