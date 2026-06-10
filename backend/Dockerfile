FROM php:8.3-fpm

# تثبيت الحزم الأساسية لي كيحتاجها Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# تنظيف الكاش
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# تثبيت إضافات PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# تثبيت وتفعيل Redis
RUN pecl install redis && docker-php-ext-enable redis

# جلب Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# إعطاء الصلاحيات
RUN chown -R www-data:www-data /var/www/backend

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]