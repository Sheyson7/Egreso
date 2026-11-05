FROM php:8.3-apache

ARG APP_UID=33
ARG APP_GID=33

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl libcurl4-openssl-dev \
    && docker-php-ext-install curl pdo_mysql \
    && a2enmod rewrite \
    && groupmod --non-unique --gid "${APP_GID}" www-data \
    && usermod --non-unique --uid "${APP_UID}" --gid "${APP_GID}" www-data \
    && rm -rf /var/lib/apt/lists/*

COPY ops/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ops/php/security.ini /usr/local/etc/php/conf.d/zz-seberih-security.ini
COPY . /var/www/html

RUN mkdir -p /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod 0750 /var/www/html/storage /var/www/html/storage/logs

WORKDIR /var/www/html
