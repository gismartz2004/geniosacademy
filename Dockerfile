FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copiar el código de la app al DocumentRoot de Apache
COPY . /var/www/html/

# Cambiar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exponer puerto que Cloud Run espera
EXPOSE 8080
ENV PORT 8080

# Apache ya está configurado para /var/www/html
CMD ["apache2-foreground"]
