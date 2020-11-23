FROM php:7.4-apache

RUN a2enmod rewrite

COPY . /var/www/html/
RUN chown www-data -R /var/www/html/
