#!/bin/sh
set -e

# 1. Esperar a MySQL
echo "Waiting for MySQL..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    echo "MySQL not ready, retrying in 2s..."
    sleep 2
done
echo "MySQL ready!"

# 2. Limpiar conflictos de módulos Apache (Fix Error MPM)
echo "Limpiando conflictos de módulos Apache..."
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/

# 3. CONFIGURACIÓN DINÁMICA DE PUERTO
echo "Configuring Apache to listen on port ${PORT:-8080}..."
sed -i "s/Listen 80/Listen ${PORT:-8080}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT:-8080}>/g" /etc/apache2/sites-available/000-default.conf

# 4. Permisos y Carpetas (Fix Error 500 / Livewire)
echo "Setting permissions..."
mkdir -p storage/app/livewire-tmp storage/framework/sessions storage/framework/views storage/framework/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 5. Base de Datos y Caché (Fix Unknown Column 'real')
echo "Running migrations and clearing cache..."
php artisan migrate:fresh --seed --force
php artisan config:clear
php artisan cache:clear

# 6. Iniciar Apache
echo "Starting Apache..."
exec apache2-foreground