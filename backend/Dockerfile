
FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN mkdir -p bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 bootstrap/cache storage

# Usuwamy BOM przed uruchomieniem Laravela
RUN php -r '$file="config/sanctum.php"; $content=file_get_contents($file); if (str_starts_with($content, "\xEF\xBB\xBF")) file_put_contents($file, substr($content, 3));'

RUN composer install --no-dev --optimize-autoloader

# Sprawdzamy dostepne trasy
RUN php artisan route:list

# Test odpowiedzi Laravela
RUN php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class); $request = Illuminate\Http\Request::create("/", "GET"); $response = $kernel->handle($request); echo "STATUS: ".$response->getStatusCode().PHP_EOL; echo "BODY: ".$response->getContent().PHP_EOL; $kernel->terminate($request, $response);'

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
