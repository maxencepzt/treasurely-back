ARG PHP_VERSION=8.3
ARG NGINX_VERSION=1.27.3

FROM php:${PHP_VERSION}-fpm-alpine AS treasurely_php
# persistent / runtime deps
RUN apk add --no-cache \
        acl \
        fcgi \
        file \
        gettext \
        postgresql-dev \
    ;
ARG APCU_VERSION=5.1.21
RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
    ; \
    \
    docker-php-ext-configure zip; \
    docker-php-ext-install -j$(nproc) \
        intl \
        pdo_pgsql \
        zip \
    ; \
    pecl install \
        apcu-${APCU_VERSION} \
    ; \
    pecl clear-cache; \
    docker-php-ext-enable \
        apcu \
        opcache \
    ; \
    \
    runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )"; \
    apk add --no-cache --virtual .api-phpexts-rundeps $runDeps; \
    \
    apk del .build-deps

COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV PATH="${PATH}:/root/.composer/vendor/bin"
RUN ln -s $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini
COPY docker/php/conf.d/config.ini $PHP_INI_DIR/conf.d/config.ini
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN set -eux; \
composer global config --no-plugins allow-plugins.symfony/flex true; \
composer global require "symfony/flex" --prefer-dist --no-progress --classmap-authoritative; \
composer clear-cache
WORKDIR /sae5-01-back
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

FROM nginx:${NGINX_VERSION}-alpine AS treasurely_back_server
COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf
WORKDIR /sae5-01-back/public

FROM treasurely_php AS treasurely_php_prod
ENV APP_ENV=prod
COPY ./composer.json ./composer.json
COPY ./composer.lock ./composer.lock
COPY ./symfony.lock ./symfony.lock
RUN set -eux; \
    composer install --prefer-dist --no-dev --no-scripts --no-progress; \
    composer clear-cache
COPY ./.env ./
COPY ./assets/ ./assets/
COPY ./bin/ ./bin/
COPY ./config/ ./config/
COPY ./migrations/ ./migrations/
COPY ./public/ ./public/
COPY ./src/ ./src/
COPY ./templates/ ./templates/
COPY ./importmap.php ./importmap.php
RUN set -eux; \
    mkdir -p var/cache var/log; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    composer run-script --no-dev post-install-cmd; \
    chmod +x bin/console; sync; \
    bin/console tailwind:build --minify; \
    bin/console asset-map:compile; \
    bin/console importmap:install

FROM treasurely_back_server AS treasurely_back_server_prod
COPY --from=treasurely_php_prod ./sae5-01-back/public ./