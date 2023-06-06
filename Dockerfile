# Base image
FROM php:8.1-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    nano

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip

# Copy application files
COPY . /var/www/html

# Set file permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 storage/logs

# Install Composer
ENV COMPOSER_HOME /composer
ENV PATH $PATH:/composer/vendor/bin
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install project dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-plugins --no-scripts

# Build the frontend assets
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash -
RUN apt-get install -y nodejs
RUN npm install
RUN npm run build

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host", "0.0.0.0", "--port", "8000"]
