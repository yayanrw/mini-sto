# Dev only. Produksi jalan di cPanel (PHP 8.2+ / MySQL), bukan container ini.
FROM php:8.2-cli

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql zip gd intl bcmath exif

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
