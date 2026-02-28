FROM php:8.3-apache

# 1. Instalar dependencias del sistema (Agregamos libpq-dev para Postgres)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl

# 2. Instalar extensiones de PHP (Cambiamos pdo_mysql por pdo_pgsql)
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd intl zip

# 3. Configurar Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# 4. Copiar archivos del proyecto
WORKDIR /var/www/html
COPY . .

# 5. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# 6. Permisos
RUN mkdir -p storage/framework/{sessions,views,cache} bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 7. Optimización de Laravel (Sin caché de config para evitar conflictos de variables)
RUN php artisan filament:assets \
    && php artisan route:cache \
    && php artisan view:cache

# 8. Script de inicio corregido
COPY --chmod=755 <<EOF /usr/local/bin/docker-php-entrypoint-custom.sh
#!/bin/sh
php artisan migrate --force
php artisan db:seed --force
apache2-foreground
EOF


ENTRYPOINT ["docker-php-entrypoint-custom.sh"]
