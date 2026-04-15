FROM php:8.3-apache

# System deps
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libonig-dev libxml2-dev libicu-dev unzip git curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli mbstring zip gd intl xml bcmath \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Apache: DocumentRoot → public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf

# Allow .htaccess overrides
RUN sed -ri 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# PHP config
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize = 10M" >> "$PHP_INI_DIR/conf.d/uploads.ini" \
    && echo "post_max_size = 12M" >> "$PHP_INI_DIR/conf.d/uploads.ini" \
    && echo "memory_limit = 256M" >> "$PHP_INI_DIR/conf.d/uploads.ini"

WORKDIR /var/www/html

# Install dependencies first (layer cache)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts 2>/dev/null \
    || composer install --no-interaction --no-scripts

# Copy project
COPY . .

# Ensure writable dirs
RUN mkdir -p writable/cache writable/logs writable/session writable/uploads \
    && chown -R www-data:www-data writable \
    && chmod -R 775 writable

EXPOSE 80

CMD ["apache2-foreground"]
