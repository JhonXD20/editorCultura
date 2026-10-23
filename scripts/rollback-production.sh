#!/bin/sh
set -eu
test -s .previous-app-image && test -s .previous-web-image
APP_IMAGE=$(cat .previous-app-image)
WEB_IMAGE=$(cat .previous-web-image)
case "$APP_IMAGE:$WEB_IMAGE" in *ghcr.io/*) ;; *) echo 'Referências de rollback inválidas' >&2; exit 1;; esac
export APP_IMAGE WEB_IMAGE
if [ -f .deployed-images.env ]; then
  BACKUP_IMAGE=$(sed -n 's/^BACKUP_IMAGE=//p' .deployed-images.env)
  export BACKUP_IMAGE
fi
docker compose --env-file .env.production -f compose.production.yaml up -d --force-recreate --wait app queue scheduler nginx
printf 'APP_IMAGE=%s\nWEB_IMAGE=%s\nBACKUP_IMAGE=%s\n' "$APP_IMAGE" "$WEB_IMAGE" "${BACKUP_IMAGE:-}" > .deployed-images.env
# Rollback de código preserva o banco. Migrações destrutivas exigem restauração explícita do backup.
