# Checklist de preparação da release 0.2.0

## Objetivo

Registrar o critério mínimo para abrir e estabilizar a próxima release do projeto após a `v0.1.0`.

## Quando abrir a branch

A branch `release/0.2.0` deve ser aberta somente depois que a feature atual estiver integrada em `develop` e a fase de endurecimento da operação assíncrona e do preparo de release estiver concluída.

## Escopo mínimo esperado para a 0.2.0

- configuração operacional explícita do worker assíncrono
- documentação do fluxo assíncrono com RabbitMQ
- documentação do fluxo de release alinhada ao estado atual do GitFlow
- workflow de entrega preparado disparando também em `main` e `release/**`
- validação local e CI coerentes com a fase atual

## Checklist antes de abrir `release/0.2.0`

- `develop` atualizado com a feature atual
- `README.md` e `docs/` sem contradições sobre o estágio do projeto
- `docker compose` refletindo a operação real do worker
- `delivery-disabled.yml` alinhado ao fluxo de release documentado
- testes e lint executados via Docker

## Checklist dentro da branch de release

- revisar documentação pública da fase
- revisar documentação de arquitetura e processos
- validar pipeline de qualidade
- validar workflow de entrega preparada
- revisar nome da versão e tag final

## Comandos de referência

```bash
git checkout develop
git pull origin develop
git checkout -b release/0.2.0
```

Depois da estabilização:

```bash
git checkout main
git pull origin main
git merge --no-ff release/0.2.0
git tag -a v0.2.0 -m "Release v0.2.0"
git push origin main --tags
git checkout develop
git pull origin develop
git merge --no-ff release/0.2.0
git push origin develop
```
