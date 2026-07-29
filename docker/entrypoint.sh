#!/bin/sh
set -e

cd /var/www/html

echo "[entrypoint] Preparando FIFARDOS…"

# Recrea la estructura de storage (por si se monta un volumen vacío encima)
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

# Limpia manifiestos/cachés stale (evita referencias a paquetes dev como Pail)
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php 2>/dev/null || true

# Permisos de escritura
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# APP_KEY es obligatoria en producción
if [ -z "${APP_KEY}" ]; then
    echo "[entrypoint] ⚠  APP_KEY no está definida. Genera una con 'php artisan key:generate --show' y configúrala en Dokploy."
fi

# SQLite: asegura el archivo (idealmente en un volumen persistente)
if [ "${DB_CONNECTION}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    [ -f "$DB_FILE" ] || touch "$DB_FILE"
    chown -R www-data:www-data "$(dirname "$DB_FILE")" 2>/dev/null || true
fi

# Descubrir paquetes y cachear config/vistas (con las env inyectadas en runtime)
php artisan package:discover --ansi || true
php artisan storage:link 2>/dev/null || true
php artisan config:clear || true
php artisan config:cache || true
php artisan view:cache || true
# Nota: NO se cachean rutas (route:cache) porque hay rutas con closures.

# Migraciones automáticas (desactivable con AUTO_MIGRATE=false)
if [ "${AUTO_MIGRATE:-true}" = "true" ]; then
    echo "[entrypoint] Ejecutando migraciones…"
    php artisan migrate --force || echo "[entrypoint] ⚠  Fallo en migraciones (continuando)."
fi

echo "[entrypoint] Listo. Iniciando servicios (nginx + php-fpm + scheduler + queue)…"
exec "$@"
