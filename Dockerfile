# Use an official PHP image that includes the Apache web server
FROM php:8.2-apache

RUN apt-get update && apt-get install -y libzip-dev zip && docker-php-ext-install zip

# ADD THESE LINES: Create the data directory and grant write permissions
# RUN mkdir -p /var/www/html/data && chown -R www-data:www-data /var/www/html/data

# Copy your entire application code into the default Apache web root directory.
COPY . /var/www/html/

# Expose the default HTTP port
EXPOSE 80