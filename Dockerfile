# syntax=docker/dockerfile:1

##########################################################################
# FIFARDOS — imagen de producción (Laravel 12 + Inertia/Vue + Vite)
# Pensada para Dokploy: un solo contenedor con nginx + php-fpm + supervisor
# (php-fpm, nginx, scheduler para recordatorios y worker de colas).
##########################################################################

# ---------- Stage 1: dependencias PHP (Composer) ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ---------- Stage 2: build de assets (Node + Vite) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
# Ziggy se importa desde vendor/ dentro de resources/js/app.js
COPY --from=vendor /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy
RUN npm run build

# ---------- Stage 3: runtime (PHP-FPM + Nginx + Supervisor) ----------
FROM php:8.3-fpm-alpine AS app

# Dependencias del sistema + extensiones PHP
RUN apk add --no-cache \
        nginx supervisor bash tzdata \
        icu-dev libzip-dev oniguruma-dev libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev \
        sqlite sqlite-dev postgresql-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql pdo_pgsql pdo_sqlite mbstring intl zip bcmath opcache pcntl gd \
    && apk del .build-deps

WORKDIR /var/www/html

# Código de la app + vendor (optimizado) + assets compilados
COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY --from=assets /app/public/build /var/www/html/public/build

# Configuración de servicios
COPY docker/nginx.conf       /etc/nginx/nginx.conf
COPY docker/php.ini          /usr/local/etc/php/conf.d/zz-fifardos.ini
COPY docker/www.conf         /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
