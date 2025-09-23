# Imagen base con PHP + Apache
FROM php:8.1-apache

# Copiar archivos del proyecto al contenedor
COPY . /var/www/html/

# Cambiar permisos
RUN chown -R www-data:www-data /var/www/html

# Cloud Run usa el puerto 8080
EXPOSE 8080
RUN sed -i 's/80/8080/' /etc/apache2/sites-available/000-default.conf

# Iniciar apache
CMD ["apache2-foreground"]
