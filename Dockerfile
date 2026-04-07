FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY . .

RUN cp .env.example .env || true
RUN php artisan key:generate --force || true

EXPOSE 10000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]
