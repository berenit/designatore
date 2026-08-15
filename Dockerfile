FROM php:8.3-fpm-alpine

# Argomenti build (utile per ambienti diversi)
ARG UID=1000
ARG GID=1000

WORKDIR /var/www/html

# Dipendenze di sistema necessarie alle estensioni PHP di Laravel
RUN apk add --no-cache \
    bash \
    curl \
    freetype-dev \
    git \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    unzip \
    zip

# Estensioni PHP tipiche di un progetto Laravel
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Composer (copiato dall'immagine ufficiale, niente installer aggiuntivo)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Utente non privilegiato allineato a quello dell'host (evita problemi di permessi sui volumi)
RUN addgroup -g ${GID} laravel \
    && adduser -G laravel -u ${UID} -D laravel

# Configurazioni PHP e OPcache
COPY docker/php/php.ini /usr/local/etc/php/conf.d/laravel.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY --chown=laravel:laravel . /var/www/html

USER laravel

# Installa le dipendenze (in produzione: --no-dev --optimize-autoloader)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

EXPOSE 9000

ENTRYPOINT ["docker-php-entrypoint"]
CMD ["php-fpm"]
