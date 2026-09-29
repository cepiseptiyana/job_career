#!/bin/sh
set -e 

echo "Clearing configuration..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

exec "$@"