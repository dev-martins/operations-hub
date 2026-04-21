# Etapa atual do projeto

## Fase

Fundação técnica com arquitetura inicial em formalização.

## O que já existe

- backend Laravel criado
- frontend Vue criado
- Docker Compose com Nginx, backend, frontend, MySQL principal, MySQL de testes, Redis e RabbitMQ
- endpoint inicial de status em `/api/v1/status`
- dashboard inicial baseada em Adminator
- README público neutro
- estrutura de `docs/` preparada para crescer

## O que ainda não existe

- primeiro módulo real de domínio
- modelagem de atendimentos, filas, SLA e eventos
- arquitetura formal de estado no frontend
- autenticação
- ACL implementada
- estratégia de tenant implementada
- testes representativos do domínio
- pipeline CI/CD configurado
- fluxo Git/GitFlow inicializado no repositório

## Leitura da fase atual

O projeto já saiu do estágio de ideia e entrou no estágio de fundação concreta, mas ainda não chegou na fase em que demonstra maturidade mais ampla em arquitetura, testes e operações. A infraestrutura está pronta para receber os primeiros módulos reais, e a documentação agora começa a consolidar a base técnica em uma leitura arquitetural clara e sustentada.

## Próximo passo recomendado

O avanço mais coerente agora é:

1. inicializar Git com `main` e `develop`
2. registrar a base com o primeiro commit
3. abrir a primeira `feature/*`
4. implementar o primeiro slice de domínio focado em atendimentos e filas operacionais
5. adicionar testes cobrindo abertura, priorização e consulta desse fluxo
6. documentar como esse módulo conversa com tenant, ACL e integração com legado

## Leitura recomendada para a próxima fase

A próxima etapa não deve priorizar nova infraestrutura. O valor agora está em provar o domínio.

O melhor recorte inicial é um módulo que permita:

- registrar uma ocorrência operacional
- classificar origem, tipo e prioridade
- encaminhar para uma fila
- registrar eventos de tratamento
- exibir essa fila no frontend

Esse passo ajuda a transformar o projeto em uma solução reconhecível para cenários de varejo, logística, serviços corporativos e operações integradas com ERP, PDV e outros legados.
