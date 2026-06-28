#!/bin/sh
set -eu

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan storage:link || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

apache2-foreground
