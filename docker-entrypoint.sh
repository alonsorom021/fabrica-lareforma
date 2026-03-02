#!/bin/sh

a2dismod mpm_event
a2enmod mpm_prefork

echo "Waiting for MySQL..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    echo "MySQL not ready, retrying in 2s..."
    sleep 2
done

echo "MySQL ready!"
php artisan migrate --force
php artisan db:seed --force
exec apache2-foreground