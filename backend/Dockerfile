
FROM php:8.4-cli

# Instalacja bibliotek i rozszerzen PHP
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

# Usuwanie BOM i pustych znakow przed <?php
# ze wszystkich plikow PHP aplikacji
RUN php -r '$dirs=["app","bootstrap","config","routes","public"]; foreach($dirs as $dir){if(!is_dir($dir))continue; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)); foreach($it as $file){if(!$file->isFile() || strtolower($file->getExtension())!=="php")continue; $path=$file->getPathname(); $s=file_get_contents($path); $clean=preg_replace("/\A(?:\xEF\xBB\xBF|\s)*(?=<\?php)/", "", $s); if($clean!==$s){file_put_contents($path,$clean); echo "Cleaned: $path\n";}}}'

# Tworzenie katalogow wymaganych przez Laravel
RUN mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 bootstrap/cache storage

# Instalacja zaleznosci Laravel
RUN composer install --no-dev --optimize-autoloader

# Port Render
EXPOSE 10000

# Migracje PostgreSQL i uruchomienie serwera Laravel
CMD php artisan migrate --force && exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
