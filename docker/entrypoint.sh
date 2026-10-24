#!/bin/sh
set -eu

cd /var/www/html
mkdir -p storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Configurar limites de subida para comprobantes de pago (HU-16)
cat << 'EOF' > /usr/local/etc/php/conf.d/uploads.ini
upload_max_filesize = 10M
post_max_size = 20M
memory_limit = 256M
EOF

# Conservar la clave entre reinicios para mantener sesiones y datos cifrados.
if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s storage/app/.app-key ]; then
        (umask 077; php -r 'echo "base64:".base64_encode(random_bytes(32));' > storage/app/.app-key)
    fi
    export APP_KEY="$(cat storage/app/.app-key)"
fi

chown -R www-data:www-data storage bootstrap/cache
php artisan config:clear
php artisan migrate --force --no-interaction
php artisan config:cache

exec "$@"
