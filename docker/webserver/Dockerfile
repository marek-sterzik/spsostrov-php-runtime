FROM php:7.4-apache

ARG WWW_DATA_UID
ARG WWW_DATA_GID

# fix www-data uid and gid
ADD fix-www-data-user /usr/local/sbin/fix-www-data-user
RUN fix-www-data-user "${WWW_DATA_UID}" "${WWW_DATA_GID}"
RUN chown -R www-data:www-data /var/www

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN a2enmod rewrite
