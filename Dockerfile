# Use official PHP image with FPM
# Choose a PHP version compatible with your Laravel project (e.g., 8.1, 8.2)
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install dependencies needed for PHP extensions and common tools
RUN apt-get update && apt-get install -y \
    build-essential \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev # Needed for mbstring

# Install system dependencies for Node.js (curl is already installed)
RUN apt-get update && apt-get install -y gnupg

# Add NodeSource repository for Node.js 20.x
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash -

# Install Node.js and npm
RUN apt-get install -y nodejs

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1000 -d /home/laravel_user laravel_user
RUN mkdir -p /home/laravel_user/.composer && \
    chown -R laravel_user:laravel_user /home/laravel_user

# Copy existing application directory contents
# COPY . /var/www # We mount the volume instead in docker-compose

# Copy existing application directory permissions
# COPY --chown=laravel_user:laravel_user . /var/www # Handled by volume mount

# Change current user to laravel_user
USER laravel_user

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
