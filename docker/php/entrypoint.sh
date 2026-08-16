#!/bin/sh
set -e

# Il bind mount della repo su /var/www/html nasconde vendor/ e public/build/
# generati durante la build dell'immagine (che risiedono in /opt/app-build).
# Li riportiamo dentro il bind mount ad ogni avvio, se mancanti.

if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "Seeding vendor/ dal build dell'immagine..."
    cp -a /opt/app-build/vendor /var/www/html/vendor
fi

if [ ! -f /var/www/html/public/build/manifest.json ]; then
    echo "Seeding public/build/ dal build dell'immagine..."
    mkdir -p /var/www/html/public/build
    cp -a /opt/app-build/public/build/. /var/www/html/public/build/
fi

exec docker-php-entrypoint "$@"
