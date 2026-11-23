FROM php:8.3-apache
RUN docker-php-ext-install mysqli
# Configuración de producción: sin mostrar errores al usuario y con búfer de salida.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/uploads
