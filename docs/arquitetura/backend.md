# Arquitetura do Backend

## Objetivo

O backend em Laravel deve concentrar a regra de negócio da central de atendimento operacional e expor contratos estáveis para consumo do frontend e de integrações externas.

## Papel do Laravel no projeto

O framework será usado como base de produtividade e organização, não como lugar para concentrar toda a lógica diretamente em controllers e models.

As decisões iniciais são:

- rotas versionadas em `/api/v1`
- controllers finos, focados em entrada e saída HTTP
- validação por `FormRequest` quando os fluxos reais forem criados
- regras de negócio em services, actions ou casos de uso
- policies e gates para ACL
- jobs e events para processamento assíncrono
- resources para padronização das respostas da API
- testes de feature para contratos e testes unitários para regras críticas

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

## Temas transversais que já devem influenciar o desenho

### Multi-tenant

Mesmo antes da implementação completa, o backend deve ser pensado para carregar contexto organizacional nas consultas, autorizações, filtros e eventos.

Perguntas que a arquitetura precisa responder:

- como identificar o tenant ativo
- onde esse contexto será resolvido
- como evitar vazamento de dados entre contextos
- como refletir isso em testes e seeders

### ACL

Papéis e permissões devem impactar:

- acesso a rotas
- ações permitidas por recurso
- exibição de botões e opções no frontend
- execução de aprovações, reprocessamentos e administração

### Legado e integrações

Integrações com sistemas legados devem preferir adapters e serviços dedicados para evitar espalhar particularidades externas por controllers e models.

## Leitura arquitetural esperada

Uma leitura madura deste backend deve deixar claro que:

- Laravel foi escolhido para acelerar entrega com organização clara
- a API foi versionada para preservar contratos
- a regra de negócio não fica acoplada à camada HTTP
- multi-tenant e ACL foram considerados como requisitos estruturais
- Redis e RabbitMQ entram como ferramentas arquiteturais, não como adereços

## Etapa atual

Hoje o backend está na fundação técnica:

- infraestrutura preparada
- endpoint de status inicial
- testes padrão do framework
- sem domínio real ainda implementado

O próximo passo recomendado é transformar o primeiro fluxo de negócio em um módulo real, começando por atendimentos e filas operacionais.
