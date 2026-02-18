FROM php:8.2-apache

WORKDIR /var/www/html

# Install required PostgreSQL libraries
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY . /var/www/html/

EXPOSE 80
