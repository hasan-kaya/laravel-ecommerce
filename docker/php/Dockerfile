FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    curl \
    procps \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

# OpCache configuration for production performance
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# PHP execution timeout
RUN { \
    echo 'max_execution_time=60'; \
    echo 'max_input_time=60'; \
    echo 'memory_limit=256M'; \
} > /usr/local/etc/php/conf.d/performance.ini

# PHP-FPM pool configuration
COPY www.conf /usr/local/etc/php-fpm.d/www.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
