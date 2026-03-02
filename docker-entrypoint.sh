#!/bin/sh

a2dismod mpm_event
a2enmod mpm_prefork

# Configurar puerto dinámico de Railway
echo "Listen ${PORT:-80}" > /etc/apache2/ports.conf
sed -i "s|<VirtualHost \*:80>|<VirtualHost *:${PORT:-80}>|g" /etc/apache2/sites-available/000-default.conf

echo "Waiting for MySQL..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    echo "MySQL not ready, retrying in 2s..."
    sleep 2
done

echo "MySQL ready!"
php artisan migrate --force
php artisan db:seed --force
exec apache2-foreground