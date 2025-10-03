# ---------- Dockerfile multi-stage para Render ----------
# Stage 1: vendor (Composer)
FROM composer:2 AS vendor
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-ansi --no-progress

# Stage 2: frontend (Vite)
FROM node:20 AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
# Si falla con "npm ci" por ausencia de package-lock, usa npm install:
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY resources/ resources/
COPY public/ public/
# 👇 Evita fallo de "COPY vite.config.*: not found" en algunos contextos
COPY vite.config.js ./
RUN npm run build

# Stage 3: runtime (PHP-FPM + Nginx + Supervisor)
FROM php:8.3-fpm-bullseye

# Paquetes del sistema + extensiones PHP
# (incluye gettext-base para 'envsubst')
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor git unzip gettext-base libpq-dev libzip-dev libicu-dev \
 && docker-php-ext-install pdo_pgsql intl bcmath opcache \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copiamos el código
COPY . /var/www/html

# Vendor desde stage vendor
COPY --from=vendor /app/vendor /var/www/html/vendor

# Assets compilados desde stage frontend
COPY --from=frontend /app/public/build /var/www/html/public/build

# Nginx + supervisord + entrypoint
COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Permisos Laravel
RUN chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R ug+rwX storage bootstrap/cache

# Render expone PORT dinámico
ENV PORT=10000
EXPOSE 10000

CMD ["/entrypoint.sh"]
