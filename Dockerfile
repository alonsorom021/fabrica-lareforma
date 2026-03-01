FROM php:8.3-apache

# 1. Instalar dependencias del sistema
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

# 2. Instalar extensiones de PHP
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd intl zip

# 3. Configurar Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN <<EOF cat > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
    DocumentRoot /var/www/html/public
    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
</VirtualHost>
EOF

RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

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

# 7. Optimización de Laravel
RUN php artisan filament:assets \
    && php artisan route:clear \
    && php artisan config:clear \
    && php artisan view:cache

# 8. Script de inicio
COPY --chmod=755 <<EOF /usr/local/bin/docker-php-entrypoint-custom.sh
#!/bin/sh
php artisan migrate --force
php artisan db:seed --force
apache2-foreground
EOF

ENTRYPOINT ["docker-php-entrypoint-custom.sh"]