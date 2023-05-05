FROM php:5.4-apache

ARG WWW_DATA_UID
ARG WWW_DATA_GID

# fix www-data uid and gid
ADD fix-www-data-user /usr/local/sbin/fix-www-data-user
RUN perl /usr/local/sbin/fix-www-data-user "${WWW_DATA_UID}" "${WWW_DATA_GID}"
RUN chown -R www-data:www-data /var/www

#RUN apt-get update && apt-get -y install git libzip-dev zip

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN docker-php-ext-install bcmath pdo pdo_mysql
RUN a2enmod rewrite
