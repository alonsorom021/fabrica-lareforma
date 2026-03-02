FROM php:8.3-apache

# 1. Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev libicu-dev libzip-dev \
    zip unzip git curl

# 2. Instalar extensiones de PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

# 3. Configurar Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# FIX MPM: Deshabilitar event y habilitar prefork para evitar el error AH00534
RUN a2dismod mpm_event && a2enmod mpm_prefork && a2enmod rewrite

# Configurar el DocumentRoot
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Copiar archivos del proyecto
WORKDIR /var/www/html
COPY . .

# 5. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# 6. Permisos
RUN mkdir -p storage/framework/{sessions,views,cache} bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 7. Optimización de Laravel
# Agregamos '|| true' para que el build no falle si no hay conexión a DB en este paso
RUN php artisan filament:assets || true
RUN php artisan route:clear && php artisan config:clear

# 8. Script de inicio
COPY docker-entrypoint.sh /usr/local/bin/docker-php-entrypoint-custom.sh
RUN chmod +x /usr/local/bin/docker-php-entrypoint-custom.sh

ENTRYPOINT ["/usr/local/bin/docker-php-entrypoint-custom.sh"]