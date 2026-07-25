# Stage 1: Composer Dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-dev --no-scripts --no-autoloader || true

# Stage 2: Node.js for Frontend Assets (Tailwind, Alpine, Vite)
FROM node:20-alpine AS node
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci || npm install
COPY . .
RUN npm run build

# Stage 3: PHP-FPM Production Image
FROM php:8.4-fpm AS base
WORKDIR /var/www/html

# Install system dependencies & PHP extensions (TERMASUK intl untuk Filament) & node JS
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip libicu-dev procps \
    && curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \    
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . .

# Copy built frontend assets from Node stage
COPY --from=node /app/public/build public/build

# Install composer dependencies (optimized for production)
RUN composer install --no-interaction --optimize-autoloader --no-dev || true

# Hardening: jalankan sebagai user non-root
RUN groupadd -g 1000 laravel && useradd -u 1000 -g laravel -m laravel \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

USER laravel

EXPOSE 9000
CMD ["php-fpm"]