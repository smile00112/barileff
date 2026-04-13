#!/bin/bash

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info() { echo -e "${GREEN}[INFO]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1" && exit 1; }

info "Проверка зависимостей..."
command -v docker >/dev/null 2>&1 || error "Docker не установлен"
docker compose version >/dev/null 2>&1 || error "Docker Compose не установлен"

# Проверка .env
if [ ! -f .env ]; then
    [ -f .env.example ] && cp .env.example .env || error ".env файл не найден"
    warn "Создан .env файл. Отредактируйте его перед продолжением!"
    exit 1
fi

# Загрузка переменных
source .env

# Некоторые версии docker compose могут пытаться интерполировать переменную "__".
# Явно задаем ее, чтобы убрать шумные WARN и не мешать деплою.
export __="${__:-}"

# Создание директорий
info "Создание необходимых директорий..."
mkdir -p docker/nginx/conf.d docker/nginx/ssl docker/nginx/logs docker/certbot/www
mkdir -p storage/{logs,framework/{cache,sessions,views}} bootstrap/cache

# Установка прав
info "Установка прав доступа..."
chmod -R 775 storage bootstrap/cache

# Удаляем version из docker-compose если есть
if [ -f docker-compose.prod.yml ]; then
    sed -i '/^version:/d' docker-compose.prod.yml
fi

# Сборка образов
info "Сборка Docker образов..."
docker compose -f docker-compose.prod.yml build

# Запуск контейнеров
info "Запуск MySQL и Redis..."
docker compose -f docker-compose.prod.yml up -d mysql redis

# Ожидание готовности MySQL
info "Ожидание готовности MySQL..."
timeout=60
while ! docker compose -f docker-compose.prod.yml exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; do
    timeout=$((timeout - 1))
    if [ $timeout -le 0 ]; then
        error "MySQL не запустился. Проверьте логи: docker compose -f docker-compose.prod.yml logs mysql"
    fi
    echo -n "."
    sleep 1
done
echo " MySQL готов!"

# Ожидание готовности Redis
#info "Ожидание готовности Redis..."
#timeout=30
#while ! docker compose -f docker-compose.prod.yml exec -T redis redis-cli ping 2>/dev/null | grep -q "PONG"; do
#    timeout=$((timeout - 1))
#    if [ $timeout -le 0 ]; then
#        warn "Redis не отвечает на ping. Проверяем логи..."
#        docker compose -f docker-compose.prod.yml logs redis
#        error "Redis не запустился"
#    fi
#    echo -n "."
#    sleep 2
#done
#echo " Redis готов!"
# Проверка Redis (упрощенная)
info "Проверка Redis..."
sleep 5
if docker compose -f docker-compose.prod.yml ps redis | grep -q "healthy"; then
    echo " Redis готов (статус healthy)"
else
    warn "Redis не в статусе healthy, но контейнер запущен. Продолжаем..."
    docker compose -f docker-compose.prod.yml ps redis
fi


# Генерация ключа если нужно
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    info "Генерация APP_KEY..."

    if command -v openssl >/dev/null 2>&1; then
        generated_key="base64:$(openssl rand -base64 32 | tr -d '\r\n')"
    elif command -v php >/dev/null 2>&1; then
        generated_key="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    else
        generated_key=$(docker compose -f docker-compose.prod.yml run --rm app php -r "echo 'base64:' . base64_encode(random_bytes(32));" 2>/dev/null | tr -d '\r\n')
    fi

    if [ -z "$generated_key" ]; then
        error "Не удалось сгенерировать APP_KEY"
    fi

    if grep -q '^APP_KEY=' .env; then
        sed -i "s|^APP_KEY=.*|APP_KEY=${generated_key}|" .env
    else
        echo "APP_KEY=${generated_key}" >> .env
    fi

    APP_KEY="$generated_key"
    export APP_KEY

    info "APP_KEY сохранен в .env"
fi

# SSL-сертификат: проверка / первичная генерация
DOMAIN="${DOMAIN:-surprise.softnova.ru}"
SSL_CERT_DIR="docker/nginx/ssl/live/${DOMAIN}"

if [ ! -f "${SSL_CERT_DIR}/fullchain.pem" ]; then
    info "SSL-сертификат не найден. Запуск certbot для получения сертификата..."

    # Полная очистка предыдущих certbot-артефактов, чтобы избежать создания
    # линейки с суффиксом -0001 из-за сломанных renewal-конфигов
    rm -rf docker/nginx/ssl/live
    rm -rf docker/nginx/ssl/archive
    rm -rf docker/nginx/ssl/renewal
    rm -rf docker/nginx/ssl/accounts

    mkdir -p docker/certbot/www "${SSL_CERT_DIR}"

    info "Генерация временного самоподписанного сертификата..."
    openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
        -keyout "${SSL_CERT_DIR}/privkey.pem" \
        -out "${SSL_CERT_DIR}/fullchain.pem" \
        -subj "/CN=${DOMAIN}" 2>/dev/null
    cp "${SSL_CERT_DIR}/fullchain.pem" "${SSL_CERT_DIR}/chain.pem"

    info "Запуск nginx для ACME-challenge..."
    docker compose -f docker-compose.prod.yml up -d nginx

    sleep 5

    # Удаляем всё содержимое certbot — nginx уже держит файлы в памяти.
    # Важно удалять всё (live, archive, renewal), иначе certbot может
    # создать линейку с суффиксом -0001 вместо ожидаемого имени домена.
    rm -rf docker/nginx/ssl/live
    rm -rf docker/nginx/ssl/archive
    rm -rf docker/nginx/ssl/renewal

    # --entrypoint "" сбрасывает entrypoint из docker-compose (бесконечный цикл renew),
    # позволяя выполнить certbot certonly напрямую
    docker compose -f docker-compose.prod.yml run --rm --entrypoint "" certbot \
        certbot certonly --webroot \
        --webroot-path=/var/www/certbot \
        --email "${SSL_EMAIL:-admin@${DOMAIN}}" \
        --domain "${DOMAIN}" \
        --agree-tos \
        --no-eff-email \
        --non-interactive || {
            warn "Не удалось получить SSL-сертификат. Nginx запустится, но HTTPS может не работать."
            warn "Проверьте DNS и повторите: docker compose -f docker-compose.prod.yml run --rm --entrypoint '' certbot certbot certonly ..."
        }

    docker compose -f docker-compose.prod.yml stop nginx

    # Certbot мог сохранить сертификат с суффиксом (напр. -0001),
    # если в момент запроса остались сломанные renewal-конфиги.
    # Переносим такой сертификат на ожидаемый путь.
    if [ ! -f "${SSL_CERT_DIR}/fullchain.pem" ] || [ ! -f "${SSL_CERT_DIR}/privkey.pem" ]; then
        SUFFIXED_DIR=$(find docker/nginx/ssl/live -maxdepth 1 -type d -name "${DOMAIN}-*" 2>/dev/null | head -1)
        if [ -n "${SUFFIXED_DIR}" ] && [ -f "${SUFFIXED_DIR}/fullchain.pem" ]; then
            warn "Certbot сохранил сертификат в ${SUFFIXED_DIR}. Переносим в ${SSL_CERT_DIR}..."
            mkdir -p "${SSL_CERT_DIR}"
            cp "${SUFFIXED_DIR}/fullchain.pem" "${SSL_CERT_DIR}/fullchain.pem"
            cp "${SUFFIXED_DIR}/privkey.pem"   "${SSL_CERT_DIR}/privkey.pem"
            cp "${SUFFIXED_DIR}/chain.pem"     "${SSL_CERT_DIR}/chain.pem" 2>/dev/null || \
                cp "${SUFFIXED_DIR}/fullchain.pem" "${SSL_CERT_DIR}/chain.pem"
            info "Сертификат перенесён в ${SSL_CERT_DIR}"
        fi
    fi

    # Если сертификат всё ещё не найден — генерируем самоподписанный как fallback
    if [ ! -f "${SSL_CERT_DIR}/fullchain.pem" ] || [ ! -f "${SSL_CERT_DIR}/privkey.pem" ]; then
        warn "Сертификат не найден после certbot. Генерация самоподписанного для запуска nginx..."
        mkdir -p "${SSL_CERT_DIR}"
        openssl req -x509 -nodes -newkey rsa:2048 -days 365 \
            -keyout "${SSL_CERT_DIR}/privkey.pem" \
            -out "${SSL_CERT_DIR}/fullchain.pem" \
            -subj "/CN=${DOMAIN}" 2>/dev/null
        cp "${SSL_CERT_DIR}/fullchain.pem" "${SSL_CERT_DIR}/chain.pem"
    fi
else
    info "SSL-сертификат найден: ${SSL_CERT_DIR}/fullchain.pem"
fi

# Запуск всех сервисов
info "Запуск всех сервисов..."
docker compose -f docker-compose.prod.yml up -d

# Ожидание готовности приложения
info "Ожидание готовности приложения..."
sleep 15

# Vite ассеты собираются на этапе docker build в Dockerfile
info "Пропуск runtime-сборки фронтенда (Vite собирается в Dockerfile)..."

# Миграции
info "Запуск миграций..."
docker compose -f docker-compose.prod.yml exec -e RUN_MIGRATIONS=true app php artisan migrate --force

# Оптимизация
info "Оптимизация Laravel..."
docker compose -f docker-compose.prod.yml exec app php artisan config:cache || true
docker compose -f docker-compose.prod.yml exec app php artisan route:cache || true
docker compose -f docker-compose.prod.yml exec app php artisan view:cache || true

# Прогрев кеша nomenclature (чтобы первый запрос /api/v1/nomenclature не был медленным)
info "Прогрев кеша nomenclature..."
# docker compose -f docker-compose.prod.yml exec app php artisan nomenclature:warm-cache || true

# Прогрев кеша mobile-settings (чтобы первый запрос /api/v1/mobile-settings не был медленным)
info "Прогрев кеша mobile-settings..."
# docker compose -f docker-compose.prod.yml exec app php artisan mobile-settings:warm-cache || true

# Прогрев кеша catalog-v2 (чтобы первый запрос /api/v1/catalog-v2 не был медленным)
info "Прогрев кеша catalog-v2..."
# docker compose -f docker-compose.prod.yml exec app php artisan catalog-v2:warm-cache || true

# Проверка статуса
info "Проверка статуса сервисов..."
docker compose -f docker-compose.prod.yml ps

info "=========================================="
info "Развертывание завершено!"
info "=========================================="
info "Приложение доступно по адресу: ${APP_URL:-http://localhost}"
info "Для просмотра логов: docker compose -f docker-compose.prod.yml logs -f"
