FROM debian:trixie

ARG PHP_VERSION=8.4
ENV PHPVER="$PHP_VERSION"

COPY mikenopa-docker-utils /tmp/mikenopa-docker-utils
COPY installer /tmp/installer
RUN /tmp/installer

ENV LG_WD=/tmp


COPY php-fpm.conf /etc/php/$PHPVER/fpm/php-fpm.conf.template
COPY entrypoint.sh /usr/bin/entrypoint.sh
COPY bootenv /usr/bin/bootenv
COPY x /usr/bin/x
COPY xdebug.ini /etc/php/$PHPVER/mods-available/xdebug.ini.template
COPY nginx.conf /etc/nginx/nginx.conf.template
COPY worker.conf /etc/nginx/worker.conf.template

COPY gpio.py /usr/local/bin/gpiotest 

EXPOSE 80
WORKDIR /app
CMD ["entrypoint.sh"]
