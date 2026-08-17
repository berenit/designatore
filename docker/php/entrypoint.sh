#!/bin/sh
set -e

# Il bind mount della repo su /var/www/html nasconde vendor/ e public/build/
# generati durante la build dell'immagine (che risiedono in /opt/app-build).
# Li riportiamo dentro il bind mount ad ogni avvio se mancanti O obsoleti:
# vendor/ confrontando composer.lock, public/build/ confrontando il contenuto
# degli asset compilati (non basta package-lock.json: le classi Tailwind
# derivano dalla scansione dei .blade.php, quindi un asset può cambiare anche
# senza toccare le dipendenze npm). Gira come root per poter scrivere anche se
# il bind mount è di proprietà di root (es. residuo di tentativi precedenti),
# poi cede i permessi a laravel.

vendor_hash="$(md5sum /opt/app-build/composer.lock | cut -d' ' -f1)"
if [ ! -f /var/www/html/vendor/autoload.php ] || [ "$(cat /var/www/html/vendor/.seed-hash 2>/dev/null)" != "$vendor_hash" ]; then
    echo "Seeding vendor/ dal build dell'immagine..."
    rm -rf /var/www/html/vendor
    mkdir -p /var/www/html/vendor
    cp -a /opt/app-build/vendor/. /var/www/html/vendor/
    echo "$vendor_hash" > /var/www/html/vendor/.seed-hash
fi

assets_hash="$(find /opt/app-build/public/build -type f ! -name '.seed-hash' -exec md5sum {} + | sort | md5sum | cut -d' ' -f1)"
if [ ! -f /var/www/html/public/build/manifest.json ] || [ "$(cat /var/www/html/public/build/.seed-hash 2>/dev/null)" != "$assets_hash" ]; then
    echo "Seeding public/build/ dal build dell'immagine..."
    rm -rf /var/www/html/public/build
    mkdir -p /var/www/html/public/build
    cp -a /opt/app-build/public/build/. /var/www/html/public/build/
    echo "$assets_hash" > /var/www/html/public/build/.seed-hash
fi

chown -R laravel:laravel /var/www/html/vendor /var/www/html/public/build

# Il master di php-fpm deve restare root (scrive il proprio error_log su
# /proc/self/fd/2); i worker vengono eseguiti come laravel via www.conf.
# Per qualsiasi altro comando (es. queue:work) passiamo invece a laravel.
if [ "$1" = "php-fpm" ]; then
    exec docker-php-entrypoint "$@"
fi

exec su-exec laravel docker-php-entrypoint "$@"
