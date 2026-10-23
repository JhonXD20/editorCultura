#!/bin/sh
set -eu
chgrp -R 101 /etc/letsencrypt /etc/letsencrypt/live /etc/letsencrypt/archive
chmod 750 /etc/letsencrypt
find /etc/letsencrypt/live /etc/letsencrypt/archive -type d -exec chmod 750 {} \;
find /etc/letsencrypt/archive -type f -exec chmod 640 {} \;
