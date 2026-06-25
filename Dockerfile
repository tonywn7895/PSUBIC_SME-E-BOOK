# --- Stage 1: Build PHP dependencies ---
FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# --- Stage 2: Build Frontend Assets ---
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
# Copy PHP vendor files so Vite can resolve Flux UI resources (e.g. CSS files)
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# --- Stage 3: Final Production Image ---
FROM serversideup/php:8.3-fpm-nginx

# Configure PHP settings via environment variables
ENV PHP_OPCACHE_ENABLE=1 \
    AUTORUN_ENABLED=true \
    AUTORUN_LOG_LINK=true

# Copy application files (with correct user permissions for security)
COPY --chown=www-data:www-data . /var/www/html

# Copy vendors and compiled assets from previous stages
COPY --chown=www-data:www-data --from=vendor /app/vendor /var/www/html/vendor
COPY --chown=www-data:www-data --from=frontend /app/public/build /var/www/html/public/build
