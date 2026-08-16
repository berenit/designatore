FROM node:20-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

FROM php:8.3-fpm-alpine

# Argomenti build (utile per ambienti diversi)
ARG UID=1000
ARG GID=1000

WORKDIR /var/www/html

# Dipendenze di sistema necessarie alle estensioni PHP di Laravel
RUN apk add --no-cache \
    autoconf \
    bash \
    build-base \
    curl \
    freetype-dev \
    git \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    linux-headers \
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

# L'app viene costruita in /opt/app-build (fuori dal path del bind mount
# ./:/var/www/html) cosi' vendor/ e public/build/ generati qui sopravvivono
# e vengono seminati nel bind mount da entrypoint.sh ad ogni avvio.
COPY --chown=laravel:laravel . /opt/app-build
COPY --chown=laravel:laravel --from=assets /app/public/build /opt/app-build/public/build

USER laravel

# Installa le dipendenze (in produzione: --no-dev --optimize-autoloader)
RUN cd /opt/app-build && composer install --no-interaction --prefer-dist --optimize-autoloader

USER root
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
USER laravel

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
