#!/bin/sh
set -e

php artisan config:clear
php artisan migrate --force
php artisan db:seed --force

# ${PORT:-10000} : utilise le port donné par l'hébergeur (Render, Railway...),
# ou 10000 par défaut si la variable n'est pas définie.
php artisan serve --host 0.0.0.0 --port "${PORT:-10000}"
