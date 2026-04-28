FROM php:8.2-apache

# Install system dependencies + common PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        zip \
        gd \
        pdo_mysql \
        mysqli \
        exif \
        intl \
        bcmath \
    && docker-php-ext-enable opcache \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

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