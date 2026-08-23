#!/usr/bin/env bash
set -euo pipefail

mkdir -p \
  /var/www/html/storage/framework/cache \
  /var/www/html/storage/framework/sessions \
  /var/www/html/storage/framework/views \
  /var/www/html/storage/fonts \
  /var/www/html/storage/app/dompdf \
  /var/www/html/bootstrap/cache \
  /var/www/html/database

touch /var/www/html/database/database.sqlite

# Named volumes are created as root; fix ownership for Apache runtime writes.
chown -R www-data:www-data \
  /var/www/html/storage \
  /var/www/html/bootstrap/cache \
  /var/www/html/database

chmod -R ug+rwX \
  /var/www/html/storage \
  /var/www/html/bootstrap/cache \
  /var/www/html/database

exec apache2-foreground