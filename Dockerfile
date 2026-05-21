# ─── Stage 1: Frontend build ────────────────────────────────────────────────
FROM node:20-alpine AS frontend

WORKDIR /app

# Исходники для всех Vite-сборок (node_modules исключены в .dockerignore)
COPY package.json package-lock.json* vite.config.js ./
COPY resources/ resources/
COPY packages/Webkul/Admin/ packages/Webkul/Admin/
COPY packages/Webkul/Shop/ packages/Webkul/Shop/
COPY packages/Webkul/ManagerApp/ packages/Webkul/ManagerApp/

# Root build → public/build
RUN npm install --prefer-offline && npm run build

# Admin theme → public/themes/admin/default/build
RUN cd packages/Webkul/Admin && npm install --prefer-offline && npm run build

# Shop theme → public/themes/shop/default/build
RUN cd packages/Webkul/Shop && npm install --prefer-offline && npm run build

# ManagerApp → public/manager
RUN cd packages/Webkul/ManagerApp && npm install --prefer-offline && npm run build

# ─── Stage 2: PHP application ────────────────────────────────────────────────
FROM php:8.4-cli-alpine AS base

# Установка системных зависимостей (БЕЗ изменения /etc/resolv.conf)
RUN apk add --no-cache \
    libpq-dev \
    git \
    curl \
    curl-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    unzip \
    bash \
    supervisor \
    netcat-openbsd \
    linux-headers \
    autoconf \
    g++ \
    make \
    pcre-dev

# Установка PHP расширений
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    calendar \
    xml \
    dom \
    fileinfo \
    sockets

# Установка Redis через PECL с использованием curl (более надежно)
RUN curl -sSL https://pecl.php.net/get/redis -o /tmp/redis.tar.gz \
    && tar xzf /tmp/redis.tar.gz -C /tmp \
    && cd /tmp/redis-* \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/redis*

# Очистка
RUN apk del autoconf g++ make pcre-dev

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html


COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .

# Создаём директории, исключённые из .dockerignore (нужны для artisan-команд при сборке)
RUN mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs bootstrap/cache

RUN composer dump-autoload --optimize --no-dev

COPY docker/php/php.ini /usr/local/etc/php/php.ini

RUN php artisan package:discover --ansi || true

# Генерация .rr.yaml конфига для RoadRunner (требуется Octane)
RUN php artisan octane:install --server=roadrunner --no-interaction || true

# Установка RoadRunner (прямая загрузка, т.к. spiral/roadrunner-cli — dev-зависимость)
RUN set -eux; \
    ARCH=$(uname -m | sed 's/x86_64/amd64/;s/aarch64/arm64/'); \
    RR_VER=$(curl -sSfL https://api.github.com/repos/roadrunner-server/roadrunner/releases/latest \
        | grep '"tag_name"' | sed 's/.*"tag_name": "v\([^"]*\)".*/\1/'); \
    curl -sSfL "https://github.com/roadrunner-server/roadrunner/releases/download/v${RR_VER}/roadrunner-${RR_VER}-linux-${ARCH}.tar.gz" \
        -o /tmp/rr.tar.gz; \
    tar -xzf /tmp/rr.tar.gz -C /tmp; \
    find /tmp -maxdepth 3 -name 'rr' -type f | head -1 | xargs -I{} mv {} /usr/local/bin/rr; \
    chmod +x /usr/local/bin/rr; \
    rm -rf /tmp/rr*

# Копируем собранный фронтенд из Node-стейджа (перезаписывает всё из COPY . .)
COPY --from=frontend /app/public/build public/build
COPY --from=frontend /app/public/themes public/themes
COPY --from=frontend /app/public/manager public/manager

# Настройка прав
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php artisan octane:status || wget --quiet --tries=1 --spider http://localhost:8000 || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php", "artisan", "octane:start", "--server=roadrunner", "--host=0.0.0.0", "--port=8000"]
