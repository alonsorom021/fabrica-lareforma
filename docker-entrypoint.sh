#!/bin/sh

# 1. Esperar a MySQL (Tu lógica actual está perfecta)
echo "Waiting for MySQL..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    echo "MySQL not ready, retrying in 2s..."
    sleep 2
done
echo "MySQL ready!"

# 2. Ajustar el puerto de Apache dinámicamente para Railway
# Esto cambia el puerto 80 por el que Railway te asigne ($PORT)
sed -i "s/Listen 80/Listen ${PORT:-8080}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT:-8080}>/g" /etc/apache2/sites-available/000-default.conf

# 3. Permisos
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 4. Comandos de Laravel
php artisan migrate --force
# Nota: Ten cuidado con el seed en cada restart, podría duplicar datos si no usas updateOrCreate
# php artisan db:seed --force 

# 5. EJECUTAR APACHE (En lugar de artisan serve)
echo "Starting Apache on port ${PORT:-8080}..."
exec apache2-foreground