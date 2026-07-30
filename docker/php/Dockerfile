FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        icu-dev \
        libzip-dev \
        postgresql-dev \
        git \
        unzip \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        intl \
        zip \
        pdo_pgsql \
        opcache \
    && pecl install apcu \
    && docker-php-ext-enable apcu

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

EXPOSE 9000

CMD ["php-fpm"]
