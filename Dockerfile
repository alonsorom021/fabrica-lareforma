FROM php:8.3-apache

# 1. Instalar dependencias del sistema (incluyendo libicu para intl y libzip para zip)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl

# 2. Instalar extensiones de PHP (añadidas intl y zip)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

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
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

CMD ["apache2-foreground"]