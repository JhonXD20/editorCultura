#!/bin/bash
set -euo pipefail
umask 077
: "${RESTIC_REPOSITORY:?RESTIC_REPOSITORY não configurado}"
: "${RESTIC_PASSWORD:?RESTIC_PASSWORD não configurado}"
export MYSQL_PWD="${DB_PASSWORD}"
mkdir -p /tmp/totemsync-backup
mysqldump --single-transaction --quick --skip-lock-tables --no-tablespaces \
  --set-gtid-purged=OFF --host=mysql --user="${DB_USERNAME}" "${DB_DATABASE}" \
  > /tmp/totemsync-backup/database.sql
# O dump precisa terminar antes da publicação do snapshot. Falhas não geram backups falsamente válidos.
test -s /tmp/totemsync-backup/database.sql
restic backup /tmp/totemsync-backup/database.sql /data/media/app/public --tag totemsync-full --host totemsync
restic forget --tag totemsync-full --host totemsync --group-by host,tags --keep-daily 7 --keep-weekly 4 --keep-monthly 6 --prune
restic check
