#!/bin/sh
set -e

# Функция проверки доступности порта
wait_for_port() {
    host=$1
    port=$2
    name=$3
    echo "Waiting for $name..."
    max_attempts=60
    attempt=0
    while [ $attempt -lt $max_attempts ]; do
        if nc -z "$host" "$port" 2>/dev/null; then
            echo "$name is ready!"
            return 0
        fi
        attempt=$((attempt + 1))
        sleep 1
    done
    echo "Warning: $name did not become ready after $max_attempts attempts"
}

# Нефатальный запуск artisan-команд (не должен срывать старт контейнера)
run_artisan_optional() {
    cmd="$*"
    if ! php artisan "$@"; then
        echo "Warning: php artisan $cmd failed, continuing startup"
    fi
}

# Ожидание готовности MySQL
wait_for_port mysql 3306 "MySQL"

# Ожидание готовности Redis
wait_for_port redis 6379 "Redis"

# Установка прав доступа
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Создание symlink storage -> public/storage
run_artisan_optional storage:link

# Оптимизация Laravel для production
if [ "$APP_ENV" = "production" ]; then
    echo "Optimizing Laravel for production..."
    run_artisan_optional config:cache
    run_artisan_optional route:cache
    run_artisan_optional view:cache
    run_artisan_optional event:cache
    run_artisan_optional nomenclature:warm-cache
    run_artisan_optional mobile-settings:warm-cache
    run_artisan_optional catalog-v2:warm-cache
fi

# Выполнение миграций (если нужно)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
fi

# Запуск команды
exec "$@"
