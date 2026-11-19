FROM php:8.5-cli
# Instala dependencias
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    libsqlite3-dev
# Instala extensiones PHP
RUN docker-php-ext-install pdo pdo_sqlite
# Instala extension Redis
RUN pecl install redis && docker-php-ext-enable redis
# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# Copia el código de la aplicación
WORKDIR /var/www
CMD php artisan serve --host=0.0.0.0 --port=8000