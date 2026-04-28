FROM php:8.2-apache

RUN apt-get update && apt-get install -y libzip-dev zip && docker-php-ext-install zip

# Install system dependencies + common PHP extensions
RUN docker-php-ext-enable opcache \
    && a2enmod rewrite

# Optional: Copy custom php.ini settings
# COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www/html

# Copy application code (this is good for production, but volume overrides in dev)
COPY . /var/www/html/

# Set proper permissions (this helps when volume is not mounted or for initial files)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80