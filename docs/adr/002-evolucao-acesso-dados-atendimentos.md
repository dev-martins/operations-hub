# ADR 002 - Evolução do acesso a dados no módulo de atendimentos

## Status

Aceita

## Contexto

O primeiro slice do módulo de atendimentos já nasceu com algumas decisões importantes:

- controllers finos para entrada e saída HTTP
- validação por `FormRequest`
- autorização por `policy`
- regras de negócio concentradas em `AttendanceService`
- isolamento por tenant aplicado antes da autorização

Ao revisar o código dos controllers de atendimentos, ficou evidente que o uso direto de `Attendance::query()` não representa hoje o principal problema arquitetural. A maior fragilidade atual está na duplicação da busca do recurso por tenant em mais de um controller.

Ao mesmo tempo, o projeto precisa sustentar dois objetivos:

- manter o código atual simples e legível neste estágio inicial
- preparar uma evolução previsível para cenários com consultas mais complexas, maior padronização e eventual abstração de acesso a dados

## Decisão

Não introduzir um `Repository` genérico imediatamente para o módulo de atendimentos.

Adotar, por enquanto, o seguinte critério de evolução:

- manter Eloquent como mecanismo de persistência explícito neste primeiro módulo
- evitar espalhar regra de negócio em controllers e models
- tratar a duplicação da resolução de atendimento por tenant como o primeiro alvo natural de refatoração
- formalizar a próxima evolução por extração de um ponto único de leitura do recurso, antes de criar uma camada completa de repository

Quando o módulo crescer, a ordem recomendada de evolução será:

1. extrair a busca de atendimento por tenant para um componente único
2. separar mais claramente leitura e escrita quando os casos de uso pedirem
3. introduzir contratos no domínio ou na aplicação apenas quando houver ganho real de desacoplamento
4. implementar `Repository` como detalhe de infraestrutura quando a abstração deixar de ser apenas preventiva e passar a proteger uma variação plausível do sistema

## Justificativa

Essa escolha foi adotada porque:

- padronização é importante, mas não deve ser confundida com aumento automático de camadas
- uma abstração criada cedo demais pode repetir a API do Eloquent sem agregar desacoplamento real
- neste estágio, a duplicação de critérios de busca e escopo por tenant é um risco mais concreto do que a troca imediata da fonte de dados
- manter a intenção arquitetural documentada preserva previsibilidade sem empurrar o código para uma abstração prematura

## Consequências

### Positivas

- preserva simplicidade no primeiro módulo real do domínio
- mantém o código aderente ao ecossistema Laravel sem esconder o Eloquent atrás de cascas artificiais
- cria uma trilha explícita de evolução para maior padronização
- reduz a chance de retrabalho causado por abstrações criadas sem caso de uso claro

### Negativas

- ainda existe duplicação de busca por tenant em alguns controllers
- a camada de acesso a dados ainda não possui contrato próprio
- a futura extração para `finder`, `query service`, `action` ou `repository` continuará sendo necessária à medida que o módulo crescer

## Direção de implementação

O próximo passo arquitetural recomendado para o módulo é introduzir um resolvedor único de atendimento por tenant, mantendo controller fino, policy explícita e service para as transições de estado.

Essa direção prepara o terreno para uma futura camada de contratos de acesso a dados, mas evita transformar a primeira entrega do módulo em uma arquitetura mais abstrata do que o problema atual exige.
