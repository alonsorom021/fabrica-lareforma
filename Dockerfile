FROM richarvey/nginx-php-fpm:latest

# Configuración de directorio de trabajo
WORKDIR /var/www/html
COPY . .

# Variables de entorno para la imagen
ENV SKIP_COMPOSER 0
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Permisos para Laravel
RUN chmod -R 777 storage bootstrap/cache

CMD ["/start.sh"]
