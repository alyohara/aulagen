FROM php:8.4-cli-bookworm

# System deps + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl ca-certificates gnupg \
        libpq-dev libzip-dev libicu-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_pgsql zip intl bcmath opcache pcntl sockets \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Node 22 (asset build) + Composer
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencies first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix

COPY package.json package-lock.json* ./
RUN npm install --no-audit --no-fund

COPY . .
RUN composer dump-autoload --optimize --no-interaction \
    && npm run build

RUN chown -R www-data:www-data storage bootstrap/cache || true

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
