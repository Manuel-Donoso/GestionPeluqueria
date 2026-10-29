# Define la imagen base oficial: PHP en su versión 8.4 con servidor web Apache preinstalado
FROM php:8.4-apache

# Instala la extensión de PHP para conectar con MySQL/MariaDB (pdo_mysql) 
# y activa dos módulos esenciales de Apache: 'rewrite' (para URLs amigables) y 'headers' (para gestión de cabeceras HTTP)
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers

# Define una variable de entorno para cambiar la ruta pública por defecto de Apache 
# (muy común en frameworks como Laravel o proyectos modernos que apuntan a una carpeta /public)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Busca en los archivos de configuración de Apache la ruta antigua (/var/www/html) 
# y la reemplaza globalmente por la nueva ruta definida en la variable anterior (${APACHE_DOCUMENT_ROOT})
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Establece el directorio de trabajo por defecto dentro del contenedor para los siguientes comandos
WORKDIR /var/www/html

# Copia todo el contenido del directorio actual de tu proyecto local 
# hacia el directorio de trabajo (/var/www/html) dentro del contenedor
COPY . /var/www/html

# Cambia los permisos y la propiedad de los archivos copiados para que pertenezcan 
# al usuario y grupo del servidor web (www-data), evitando problemas de permisos al escribir o leer archivos
RUN chown -R www-data:www-data /var/www/html

# Indica que el contenedor escuchará peticiones a través del puerto 80 (puerto HTTP estándar)
EXPOSE 80