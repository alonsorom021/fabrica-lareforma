#!/bin/sh
set -e

# 1. Esperar a MySQL
echo "Waiting for MySQL..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    echo "MySQL not ready, retrying in 2s..."
    sleep 2
done
echo "MySQL ready!"

# 2. CONFIGURACIÓN DINÁMICA DE PUERTO (Vital para Railway)
# Cambiamos el puerto 80 por el valor de $PORT que nos da Railway
echo "Configuring Apache to listen on port ${PORT:-8080}..."
sed -i "s/Listen 80/Listen ${PORT:-8080}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT:-8080}>/g" /etc/apache2/sites-available/000-default.conf

# 3. Permisos de última hora
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 4. Comandos de base de datos
php artisan migrate --force

# 5. EJECUTAR APACHE
# Usamos exec para que Apache sea el proceso principal y el contenedor no se cierre
echo "Starting Apache..."
exec apache2-foreground