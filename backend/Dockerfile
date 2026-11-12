
FROM php:8.4-cli

# Instalacja wymaganych bibliotek i rozszerzen PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Instalacja Composera
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Katalog aplikacji
WORKDIR /var/www/html

# Kopiowanie plikow backendu
COPY . .

# Tworzenie katalogow wymaganych przez Laravel
RUN mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 bootstrap/cache storage

# Usuwanie niewidocznego znaku BOM z konfiguracji Sanctum
RUN php -r '$file="config/sanctum.php"; $content=file_get_contents($file); if (str_starts_with($content, "\xEF\xBB\xBF")) file_put_contents($file, substr($content, 3));'

# Instalacja zaleznosci Laravel
RUN composer install --no-dev --optimize-autoloader

# Port Render
EXPOSE 10000

# Uruchomienie serwera Laravel
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
