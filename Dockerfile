# ---------- Dockerfile multi-stage para Render ----------
# Stage 1: vendor (Composer)
FROM composer:2 AS vendor
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-ansi --no-progress --no-scripts

# Stage 2: frontend (Vite)
FROM node:20 AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY resources/ resources/
COPY public/ public/
COPY vite.config.js ./
RUN npm run build

# Stage 3: runtime (PHP-FPM + Nginx + Supervisor)
FROM php:8.3-fpm-bullseye

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor git unzip gettext-base libpq-dev libzip-dev libicu-dev \
 && docker-php-ext-install pdo_pgsql intl bcmath opcache \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY --from=frontend /app/public/build /var/www/html/public/build

COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

RUN chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R ug+rwX storage bootstrap/cache

ENV PORT=10000
EXPOSE 10000

CMD ["/entrypoint.sh"]
