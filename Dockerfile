# استخدم نسخة Alpine
FROM php:8.4-fpm-alpine

# تثبيت متطلبات النظام وامتداد الـ zip
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# إعداد مجلد العمل
WORKDIR /var/www
COPY . .

# تثبيت المكتبات
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# إعداد الصلاحيات
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

RUN php artisan storage:link \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# تشغيل السيرفر
EXPOSE 8000
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000