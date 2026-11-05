FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    nginx \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    libonig-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql zip gd curl xml mbstring bcmath intl \
    && pecl install redis && docker-php-ext-enable redis \
    && mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && mkdir -p /run/php

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Bind-mount dijalankan sebagai uid 1000; FPM jalan sebagai www-data (uid 33).
# Bersihkan artefak build root supaya storage/logs bisa ditulis FPM.
RUN mkdir -p storage/logs storage/framework/{cache/data,sessions,views} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["sh", "-c", "php-fpm && nginx -g 'daemon off;'"]
