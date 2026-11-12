
FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Usuwanie BOM ze wszystkich plikow PHP projektu
RUN php -r '$dirs=["app","bootstrap","config","routes","public"]; foreach($dirs as $dir){if(!is_dir($dir))continue; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)); foreach($it as $file){if($file->isFile() && strtolower($file->getExtension())==="php"){ $path=$file->getPathname(); $content=file_get_contents($path); if(str_starts_with($content,"\xEF\xBB\xBF")){file_put_contents($path,substr($content,3)); echo "Removed BOM: $path\n";}}}}'

RUN mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    && chmod -R 775 bootstrap/cache storage

RUN composer install --no-dev --optimize-autoloader

RUN php -r '$f="public/index.php"; $s=file_get_contents($f); echo "INDEX FIRST BYTES: ".bin2hex(substr($s,0,24)).PHP_EOL; echo "INDEX SIZE: ".strlen($s).PHP_EOL;'

EXPOSE 10000

CMD php -S 0.0.0.0:${PORT:-10000} -t public public/index.php
