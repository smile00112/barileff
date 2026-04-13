#!/bin/bash

set -e

echo "=========================================="
echo "  Bagisto Docker Update Script"
echo "=========================================="
echo ""

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функция для вывода сообщений
info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

# Проверка Git
if [ ! -d .git ]; then
    error "Это не Git репозиторий. Обновление из Git невозможно."
fi

# Проверка изменений
if [ -n "$(git status --porcelain)" ]; then
    warn "Обнаружены незакоммиченные изменения. Продолжить? (y/n)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        info "Обновление отменено."
        exit 0
    fi
fi

# Получение текущей ветки
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
info "Текущая ветка: $CURRENT_BRANCH"

# Обновление кода из Git
info "Получение обновлений из Git..."
git fetch origin

# Проверка наличия обновлений
LOCAL=$(git rev-parse @)
REMOTE=$(git rev-parse @{u})

if [ "$LOCAL" = "$REMOTE" ]; then
    info "Нет новых обновлений. Приложение уже актуально."
    exit 0
fi

info "Обнаружены новые обновления. Начинаю обновление..."

# Переключение на последнюю версию
info "Обновление кода..."
git pull origin "$CURRENT_BRANCH"

# Пересборка образа app (код и конфиги живут в образе)
info "Пересборка Docker образа app..."
docker compose -f docker-compose.prod.yml build app

# Очистка старого кеша конфига (bootstrap/cache смонтирован с хоста)
info "Очистка старого кеша конфига..."
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-v7.php
rm -f bootstrap/cache/events.php

# Пересоздание контейнера app из нового образа (entrypoint выполнит config:cache и т.д.)
info "Пересоздание контейнера app из нового образа..."
docker compose -f docker-compose.prod.yml up -d --force-recreate app

# Ожидание готовности нового контейнера
info "Ожидание готовности нового контейнера..."
sleep 10

# Vite ассеты собираются на этапе docker build в Dockerfile
info "Пропуск runtime-сборки фронтенда (Vite собирается в Dockerfile)..."

# Миграции в работающем новом контейнере
info "Запуск миграций базы данных..."
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Перезапуск queue worker
info "Перезапуск queue worker..."
docker compose -f docker-compose.prod.yml restart queue

# Прогрев кеша nomenclature (чтобы первый запрос /api/v1/nomenclature не был медленным)
info "Прогрев кеша nomenclature..."
# docker compose -f docker-compose.prod.yml exec app php artisan nomenclature:warm-cache || true

# Прогрев кеша mobile-settings (чтобы первый запрос /api/v1/mobile-settings не был медленным)
info "Прогрев кеша mobile-settings..."
# docker compose -f docker-compose.prod.yml exec app php artisan mobile-settings:warm-cache || true

# Прогрев кеша catalog-v2 (чтобы первый запрос /api/v1/catalog-v2 не был медленным)
info "Прогрев кеша catalog-v2..."
# docker compose -f docker-compose.prod.yml exec app php artisan catalog-v2:warm-cache || true

# Перезапуск Nginx (если нужно)
if git diff HEAD@{1} HEAD --name-only | grep -q "docker/nginx/"; then
    info "Обнаружены изменения в конфигурации Nginx. Перезапуск..."
    docker compose -f docker-compose.prod.yml exec nginx nginx -t
    docker compose -f docker-compose.prod.yml exec nginx nginx -s reload
fi

# Проверка статуса
info "Проверка статуса сервисов..."
docker compose -f docker-compose.prod.yml ps

echo ""
info "=========================================="
info "  Обновление завершено!"
info "=========================================="
echo ""
info "Все сервисы обновлены и перезапущены."
info "Для просмотра логов: docker compose -f docker-compose.prod.yml logs -f"
echo ""
