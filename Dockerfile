# Imagen base con PHP + Apache
FROM php:8.1-apache

# Copiar archivos del proyecto al contenedor
COPY . /var/www/html/

# Cambiar permisos
RUN chown -R www-data:www-data /var/www/html

# Instalar extensiones necesarias para MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Configurar Apache para que escuche en el puerto 8080
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/80/8080/g' /etc/apache2/ports.conf

# Cloud Run usa el puerto 8080
EXPOSE 8080

# Iniciar Apache en primer plano
CMD ["apache2-foreground"]
