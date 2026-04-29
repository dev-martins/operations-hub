FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        curl \
        libzip-dev \
        libpq-dev \
        libicu-dev \
        libonig-dev \
        default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql bcmath intl sockets zip \
    && docker-php-ext-enable sockets \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["sh", "-lc", "composer install && php artisan serve --host=0.0.0.0 --port=8000"]
