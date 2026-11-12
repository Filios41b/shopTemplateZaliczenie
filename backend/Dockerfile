
FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Tworzenie wymaganych katalogow Laravel
RUN mkdir -p bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 bootstrap/cache storage

# Usuwanie BOM z pliku Sanctum
RUN php -r '$file="config/sanctum.php"; $content=file_get_contents($file); if (str_starts_with($content, "\xEF\xBB\xBF")) file_put_contents($file, substr($content, 3));'

# Instalacja zaleznosci
RUN composer install --no-dev --optimize-autoloader

# Sprawdzenie tras Laravel
RUN php artisan route:list

# Test diagnostyczny odpowiedzi HTTP Laravel
RUN php -d display_errors=1 -d error_reporting=-1 -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $request = Illuminate\Http\Request::create("/", "GET"); ob_start(); $app->handleRequest($request); $body = ob_get_clean(); echo "BODY: ".var_export($body, true).PHP_EOL;'

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
