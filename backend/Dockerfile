
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

RUN composer install --no-dev --optimize-autoloader
RUN php -r '$file="config/sanctum.php"; $content=file_get_contents($file); if (str_starts_with($content, "\xEF\xBB\xBF")) file_put_contents($file, substr($content, 3));'

RUN php artisan route:list

RUN php -r 'foreach (["app","bootstrap","config","routes"] as $dir) { $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)); foreach ($it as $file) { if ($file->isFile() && str_ends_with($file->getFilename(), ".php")) { $f = fopen($file->getPathname(), "rb"); $start = fread($f, 3); fclose($f); if ($start === "\xEF\xBB\xBF") echo "BOM: ".$file->getPathname().PHP_EOL; } } }'


EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
