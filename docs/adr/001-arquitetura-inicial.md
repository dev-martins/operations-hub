# ADR 001 - Arquitetura inicial do sistema

## Status

Aceita

## Contexto

O projeto foi iniciado com backend e frontend separados, executados via Docker, com banco principal, banco de testes, Redis, RabbitMQ e Nginx já previstos na infraestrutura local. A base atual ainda está em fase de fundação: existe um esqueleto técnico funcional, mas o domínio de atendimento e suas regras centrais ainda não foram implementados.

Ao mesmo tempo, o sistema precisa nascer com decisões que sustentem evolução gradual em direção a:

- API REST versionada e estável
- arquitetura legível para manutenção e refatoração
- testes automatizados em ambiente isolado
- preparação para cache e mensageria
- espaço para multi-tenant, ACL e integração com legado
- frontend capaz de refletir a arquitetura do backend e não apenas exibir telas

## Decisão

Adotar inicialmente uma arquitetura de monólito modular, com backend Laravel e frontend Vue em aplicações separadas, integradas por API REST versionada.

As decisões iniciais são:

- backend como fonte principal das regras de negócio e contratos
- frontend como cliente operacional da API, responsável por navegação, composição de telas, estado de interface e representação das capacidades do sistema
- versionamento de endpoints sob `/api/v1`
- Docker Compose como ambiente padrão de desenvolvimento e testes
- banco principal e banco de testes isolados desde o início
- Redis reservado para estratégias de cache e suporte a operações de desempenho
- RabbitMQ reservado para processamento assíncrono e desacoplamento entre fluxos
- documentação viva em `docs/` para registrar arquitetura, decisões e evolução

## Consequências

### Positivas

- reduz o custo inicial sem impedir evolução arquitetural posterior
- facilita demonstrar domínio de Laravel, APIs REST, versionamento, testes e infraestrutura local
- mantém o frontend livre para evoluir sem misturar regra de negócio com preocupação visual
- permite introduzir módulos de domínio progressivamente sem reescrever a base

### Negativas

- exige disciplina para não transformar o monólito em acoplamento desorganizado
- pode gerar duplicação indevida de regras se o frontend tentar assumir responsabilidades do backend
- requer documentação contínua para que decisões de estado, ACL e tenant não fiquem implícitas demais

## Direção de implementação

As próximas entregas devem seguir esta ordem:

1. formalizar arquitetura de backend e frontend na documentação
2. estruturar o primeiro módulo real do domínio
3. adicionar testes automatizados representativos
4. introduzir cache e mensageria em fluxos concretos
5. consolidar pipeline de qualidade e estratégia de versionamento com GitFlow
