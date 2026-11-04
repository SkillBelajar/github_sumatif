FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    nginx \
    php83-fpm \
    php83-cli \
    php83-mysql \
    php83-redis \
    php83-mbstring \
    php83-xml \
    php83-curl \
    php83-zip \
    php83-bcmath \
    php83-intl \
    php83-bz2 \
    && rm -rf /var/lib/apt/lists/* \
    && ln -sf /usr/sbin/php-fpm83 /usr/local/bin/php-fpm \
    && ln -sf /usr/bin/php83 /usr/bin/php

WORKDIR /var/www/html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN mkdir -p /run/php && echo "[www]\ndaemonize = no" > /etc/php/8.3/fpm/pool.d/zzz.conf

EXPOSE 8080

CMD ["sh", "-c", "php-fpm83 && nginx -g 'daemon off;'"]
