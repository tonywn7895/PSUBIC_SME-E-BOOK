# --- Stage 1: Build PHP dependencies ---
FROM php:8.4-cli-alpine AS vendor
WORKDIR /app

# Copy Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install system dependencies required by Composer (zip, git)
RUN apk add --no-cache git unzip libzip-dev \
    && docker-php-ext-install zip

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader --no-interaction

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
FROM serversideup/php:8.4-fpm-nginx

# Configure PHP settings via environment variables
ENV PHP_OPCACHE_ENABLE=1 \
    AUTORUN_ENABLED=true \
    AUTORUN_LOG_LINK=true

# Copy application files (with correct user permissions for security)
COPY --chown=www-data:www-data . /var/www/html

# Copy vendors and compiled assets from previous stages
COPY --chown=www-data:www-data --from=vendor /app/vendor /var/www/html/vendor
COPY --chown=www-data:www-data --from=frontend /app/public/build /var/www/html/public/build
