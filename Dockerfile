FROM composer:2 AS composer

FROM php:8.4-cli-alpine

LABEL Description="Equeo integration app skeleton"

ENV TZ=Europe/Moscow

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN set -eux; \
    apk add --no-cache oniguruma tzdata; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers oniguruma-dev; \
    docker-php-ext-install -j"$(nproc)" mbstring; \
    pecl install xdebug; \
    docker-php-ext-enable xdebug; \
    apk del .build-deps; \
    adduser -D -u 1200 -s /bin/sh app; \
    cp "/usr/share/zoneinfo/$TZ" /etc/localtime; \
    echo "$TZ" > /etc/timezone; \
    mkfifo /tmp/stdout; \
    chmod 666 /tmp/stdout

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader \
    && composer clear-cache

COPY . ./
COPY docker/config/php/custom.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/config/php/develop.ini /usr/local/etc/php/conf.d/99-xdebug.ini

RUN chown -R app:app /app

USER app
