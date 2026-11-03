
# Imagen oficial de PHP 8.4 con Apache
FROM php:8.4-apache

# Instalar extensiones necesarias para conectar PHP con MariaDB
RUN docker-php-ext-install pdo_mysql

# Activar módulos de Apache
RUN a2enmod rewrite headers

# Directorio público de la API
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Configurar Apache para utilizar public como raíz
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf

# Permitir el uso de .htaccess en el directorio público
RUN printf '<Directory ${APACHE_DOCUMENT_ROOT}>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/public-directory.conf \
    && a2enconf public-directory

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar el código del backend
COPY . /var/www/html

# Asignar permisos al usuario de Apache
RUN chown -R www-data:www-data /var/www/html

# Puerto interno de Apache
EXPOSE 80
