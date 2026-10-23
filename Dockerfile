FROM node:24-alpine AS frontend
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.5-fpm-alpine AS php-base
RUN apk add --no-cache fcgi icu-libs libjpeg-turbo libpng libwebp libzip oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libjpeg-turbo-dev libpng-dev libwebp-dev libzip-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) bcmath gd intl mbstring opcache pcntl pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

FROM php-base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts

FROM php-base AS app
ARG APP_RELEASE=development
ENV APP_RELEASE=$APP_RELEASE
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /build/vendor ./vendor
COPY --from=frontend --chown=www-data:www-data /build/public/build ./public/build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-totemsync.ini
COPY docker/php/pool.conf /usr/local/etc/php-fpm.d/zz-totemsync.conf
RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage \
    && php artisan package:discover --ansi \
    && chown -R 82:101 storage bootstrap/cache \
    && chmod -R u=rwX,g=rX,o= storage bootstrap/cache
USER 82:101
EXPOSE 9000
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD SCRIPT_NAME=/fpm-ping SCRIPT_FILENAME=/fpm-ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q pong || exit 1
CMD ["php-fpm", "-F"]

FROM nginxinc/nginx-unprivileged:stable-alpine AS web
USER root
RUN apk add --no-cache curl
COPY --from=app /var/www/html/public /var/www/html/public
COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template
ENV NGINX_ENVSUBST_FILTER=APP_DOMAIN
USER 101:101
EXPOSE 8080 8443
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD curl -fk https://127.0.0.1:8443/up || exit 1
