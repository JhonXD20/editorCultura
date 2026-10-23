# Operação em Ubuntu / Docker

Este runbook prepara uma futura instalação. Ainda é necessário validar as imagens e a restauração completa em staging Linux antes da primeira publicação. Não reutilizar Laragon como produção.

## Pré-requisitos e segurança

Ubuntu Server 24.04 LTS, Docker Engine/Compose v2, domínio apontando para a VPS e armazenamento externo compatível com Restic. Reservar capacidade para PHP, MySQL e Redis conforme carga real; os limites do Compose são iniciais, não dimensionamento comprovado.

- Acesso SSH apenas por chave; restringir a porta SSH aos operadores/VPN. Publicar somente 80/443; MySQL, Redis e FPM não têm portas de host.
- Manter atualizações do host e monitoramento de disco, certificados, containers e idade do último backup. A associação ao grupo Docker concede privilégios equivalentes a root: limitar operadores.
- Usar diretório dedicado, por exemplo `/opt/totemsync`; nunca implantar sobre `/`, `/home` ou outro diretório amplo.
- Criar `.env.production` a partir de `.env.production.example`, com acesso apenas ao operador (`chmod 600`). Gerar APP_KEY nova uma única vez; guardar sua cópia no cofre de recuperação e não regenerá-la a cada deploy.
- Preencher APP_URL/APP_DOMAIN, senhas fortes e diferentes para MySQL, Redis e Restic, e credenciais S3. Não versionar esse arquivo, dumps, mídia nem certificados. Não usar seeders demo em produção.
- Escolher SMTP real se notificações por e-mail forem necessárias; `MAIL_MAILER=log` não envia e-mail e pode registrar conteúdo de mensagens.

## Build e CI

```sh
docker build --target app --build-arg APP_RELEASE=COMMIT -t ghcr.io/OWNER/totemsync-app:COMMIT .
docker build --target web --build-arg APP_RELEASE=COMMIT -t ghcr.io/OWNER/totemsync-web:COMMIT .
docker build -f docker/backup/Dockerfile -t ghcr.io/OWNER/totemsync-backup:COMMIT .
docker compose --env-file .env.production -f compose.production.yaml config --quiet
```

Usar o mesmo commit nas três imagens; não usar `latest` como versão de implantação. As tags com `latest` do exemplo são placeholders, devem ser substituídas. Antes de subir produção, executar toda a pipeline e teste de boot em staging. O código e os assets ficam nas imagens; somente dados/certificados têm volumes.

No GitHub, habilitar Actions/GHCR e configurar environment `production` com aprovação obrigatória. Secrets esperados: `VPS_HOST`, `VPS_USER`, `VPS_PATH`, `VPS_SSH_KEY`, `VPS_KNOWN_HOSTS`, `GHCR_TOKEN` com leitura dos pacotes. Conferir fingerprint SSH por canal independente; não desabilitar a verificação de host. Proteger `master` com os checks `quality` e `browser`. Alterar o workflow caso a branch principal tenha outro nome. Somente após a homologação habilitar a variável de repositório `PRODUCTION_DEPLOY_ENABLED=true`; sem ela o job de deploy é ignorado.

## Primeira instalação e dados

1. Publicar imagens e enviar Compose, `docker/certbot` e scripts para o diretório dedicado.
2. Preencher `.env.production` com imagens por commit e inicializar o repositório externo: `docker compose --env-file .env.production -f compose.production.yaml run --rm --no-deps backup -c 'restic init'`. Guardar a senha Restic fora da VPS. Não reinicializar um repositório existente.
3. Se houver dados locais a preservar, exportar banco e `storage/app/public` com o app parado para escrita. Restaurar **primeiro em staging**, validar migrações e conferir mídia. Na primeira instalação de produção, importar apenas na base nova e vazia, antes de executar as migrações novas. Nunca importar por cima da única cópia dos dados.
4. Executar `sh scripts/deploy-production.sh`. No primeiro deploy, Certbot standalone usa a porta 80: nenhum outro servidor pode ocupá-la. Nas renovações seguintes é usado webroot.
5. Criar o administrador de forma controlada pelo Tinker (`compose exec app php artisan tinker`), preenchendo nome, e-mail, senha forte, `perfil=admin`, `ativo=true`. Não colocar senha na linha de comando/histórico. Em migração existente, conferir qual usuário foi promovido pela migração antes de liberar o acesso.
6. Validar `/up`, login/logout, papéis, upload e entrega de arquivos, publicação, revisão, pareamento e sincronização offline no equipamento real. Não abrir produção ao público antes dessa validação.

O script executa migração com `--force` uma vez por deploy, verifica a subida com `--wait`, otimiza o app e reinicia workers. Num upgrade existente exige backup externo bem-sucedido antes de migrar. Não executar dois deploys manuais simultâneos; o workflow usa concurrency `production`.

## HTTPS e renovação

```sh
docker compose --env-file .env.production -f compose.production.yaml run --rm certbot renew --dry-run
```

O container Certbot renova a cada 12 horas; o Nginx recarrega a cada 6 horas. O hook concede leitura das chaves somente ao grupo do proxy. Conferir data de validade depois do teste e criar alerta externo de expiração. Não adicionar chaves TLS ao Git nem disponibilizar `/etc/letsencrypt` pelo servidor web.

## Backup, retenção e restauração

O serviço de backup usa dump transacional e mídia do volume persistente; publica o snapshot somente depois de concluir o dump. A retenção é de 7 diários, 4 semanais e 6 mensais. `restic check` verifica o repositório, mas não substitui restauração funcional. O loop repete diariamente: configurar alerta externo de falhas/ausência de snapshot.

```sh
docker compose --env-file .env.production -f compose.production.yaml run --rm backup /usr/local/bin/totemsync-backup
docker compose --env-file .env.production -f compose.production.yaml run --rm backup -c 'restic snapshots'
```

Trimestralmente e antes da primeira produção:

1. Restaurar um snapshot selecionado com `restic restore ID --target DIRETORIO_ISOLADO`, usando cofre de segredos, nunca dentro da aplicação ativa. O dump está em `tmp/totemsync-backup/database.sql`; mídias em `data/media/app/public` no snapshot.
2. Importar em MySQL 8.4 separado; restaurar mídia em volume separado; usar domínio/APP_URL de staging e impedir envio de e-mails/telemetria para produção.
3. Executar migrações compatíveis, conferir quantidade de registros, login, publicação e `php artisan media:verify`. Abrir páginas e mídia no navegador; registrar snapshot, tempo de recuperação e resultado.
4. Guardar também APP_KEY, infraestrutura versionada e instruções de acesso ao repositório Restic no cofre externo. Dump e mídia sem esses itens não garantem recuperação operacional.

Para testar um dump local sem tocar na base original:

```sh
php scripts/verify-backup.php CAMINHO_DATABASE_SQL CAMINHO_CLIENT_MYSQL
```

O script cria uma base com nome aleatório reservado, restaura, verifica tabelas e remove somente essa base criada por ele. Requer permissão de criar/remover bases e recusa dumps com `USE`/`CREATE DATABASE`. O teste de 16/10/2026 usou MySQL 8.0 local; repetir com MySQL 8.4 no staging.

## Rollback e incidentes

```sh
sh scripts/rollback-production.sh
```

O rollback usa as imagens anteriores registradas no deploy e preserva dados. Não desfaz migrações; adotar migrações expand/contract compatíveis com a imagem anterior. Migração destrutiva exige plano específico e restauração testada, não `migrate:rollback` automático. Após rollback conferir `/up`, revisão/publicação, fila e manifesto. Os comandos operacionais devem carregar também `.deployed-images.env` após deploy para não selecionar placeholders antigos do exemplo.

Revogar dispositivo no painel em caso de perda; desligar usuários comprometidos e revogar suas sessões. Offline não permite apagar remotamente conteúdo já armazenado. Não incluir dumps, tokens, senhas ou documentos pessoais em logs/tickets.

## Evolução de disponibilidade

Uma única VPS é ponto único de falha. O backup externo reduz perda de dados, mas não elimina indisponibilidade. Documentar RPO/RTO medidos e evoluir para banco gerenciado, armazenamento compartilhado e múltiplos nós quando houver requisito que justifique essa complexidade.
