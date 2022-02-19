FROM php:7.4-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/website/public/

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN apt-get update
RUN apt-get install -y libzip-dev zip libicu-dev

RUN a2enmod rewrite
RUN docker-php-ext-install zip intl opcache