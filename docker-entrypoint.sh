#!/bin/sh
set -e

# ... (Mantén tu lógica de esperar a MySQL aquí) ...

echo "Limpiando conflictos de módulos Apache..."
# Forzar la eliminación de cualquier enlace simbólico de mpm_event
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf

# Asegurar que mpm_prefork esté habilitado
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/

# 2. CONFIGURACIÓN DINÁMICA DE PUERTO
echo "Configuring Apache to listen on port ${PORT:-8080}..."
sed -i "s/Listen 80/Listen ${PORT:-8080}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT:-8080}>/g" /etc/apache2/sites-available/000-default.conf

# ... (Mantén tus comandos de migrate y chown) ...

echo "Starting Apache..."
exec apache2-foreground