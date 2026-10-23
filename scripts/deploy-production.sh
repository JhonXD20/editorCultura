#!/bin/sh
set -eu
test -f .env.production || { echo '.env.production não encontrado.' >&2; exit 1; }
compose() {
  if [ -f .deployed-images.env ]; then
    docker compose --env-file .env.production --env-file .deployed-images.env -f compose.production.yaml "$@"
  else
    docker compose --env-file .env.production -f compose.production.yaml "$@"
  fi
}

# Imagens anteriores são preservadas para rollback; credenciais nunca são incluídas no artefato.
if docker inspect totemsync-app-1 >/dev/null 2>&1; then
  docker inspect --format '{{.Config.Image}}' totemsync-app-1 > .previous-app-image
  docker inspect --format '{{.Config.Image}}' totemsync-nginx-1 > .previous-web-image
fi
compose config --quiet
if ! compose run --rm --no-deps --entrypoint /bin/sh certbot -c 'test -s "/etc/letsencrypt/live/$APP_DOMAIN/fullchain.pem" && test -s "/etc/letsencrypt/live/$APP_DOMAIN/privkey.pem"'; then
  compose --profile tls-bootstrap run --rm --service-ports certbot-bootstrap
fi
compose pull app queue scheduler nginx mysql redis certbot backup
compose up -d mysql redis
if docker inspect totemsync-app-1 >/dev/null 2>&1; then
  # A failed pre-deploy backup stops the deployment before changing schema or application.
  compose run --rm --no-deps backup /usr/local/bin/totemsync-backup
fi
compose run --rm app php artisan migrate --force
# public/storage is created at image build time; production code is read-only.
compose up -d --force-recreate --wait app queue scheduler nginx
compose up -d certbot backup
compose exec -T app php artisan optimize
compose exec -T app php artisan queue:restart
printf 'APP_IMAGE=%s\nWEB_IMAGE=%s\nBACKUP_IMAGE=%s\n' \
  "$(docker inspect --format '{{.Config.Image}}' totemsync-app-1)" \
  "$(docker inspect --format '{{.Config.Image}}' totemsync-nginx-1)" \
  "$(docker inspect --format '{{.Config.Image}}' totemsync-backup-1)" > .deployed-images.env
compose ps
