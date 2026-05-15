FROM php:8.3-apache

# Instala extensões PDO MySQL e habilita mod_rewrite e mod_headers
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite headers

# Aponta o DocumentRoot para public/ e habilita AllowOverride All (necessário para o .htaccess)
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g'  /etc/apache2/apache2.conf           \
    && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

COPY . /var/www/html

WORKDIR /var/www/html

# Garante que o diretório de logs exista e seja gravável pelo Apache
RUN mkdir -p /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage
