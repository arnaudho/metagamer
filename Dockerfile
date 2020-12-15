FROM php:7.3-apache

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql

RUN a2enmod rewrite

COPY . /var/www/html/
RUN chown www-data -R /var/www/html/
