# Инструкция по развертыванию Bagisto на Ubuntu сервере

## Оглавление
1. [Требования](#требования)
2. [Начальная настройка сервера](#начальная-настройка-сервера)
3. [Установка Docker и Docker Compose](#установка-docker-и-docker-compose)
4. [Настройка брандмауэра](#настройка-брандмауэра)
5. [Подготовка проекта](#подготовка-проекта)
6. [Настройка переменных окружения](#настройка-переменных-окружения)
7. [Подготовка Dockerfile](#подготовка-dockerfile)
8. [Первоначальное развертывание](#первоначальное-развертывание)
9. [Настройка SSL сертификатов](#настройка-ssl-сертификатов)
10. [Проверка работоспособности](#проверка-работоспособности)
11. [Обновление приложения](#обновление-приложения)
12. [Мониторинг и логи](#мониторинг-и-логи)
13. [Резервное копирование](#резервное-копирование)
14. [Устранение неполадок](#устранение-неполадок)

---

## Требования

### Минимальные системные требования:
- Ubuntu 22.04 LTS или новее
- 4 GB RAM (рекомендуется 8 GB)
- 40 GB свободного места на диске
- 2 CPU cores (рекомендуется 4)
- Статический IP адрес или настроенный домен

### Необходимое ПО:
- Docker Engine 24.0+
- Docker Compose v2.20+
- Git
- OpenSSL

---

## Начальная настройка сервера

### 1. Обновление системы

```bash
# Обновление списка пакетов
sudo apt update

# Обновление установленных пакетов
sudo apt upgrade -y

# Установка базовых утилит
sudo apt install -y \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git \
    unzip \
    vim \
    htop \
    net-tools \
    openssl
```

### 2. Настройка временной зоны

```bash
# Просмотр текущей временной зоны
timedatectl

# Установка временной зоны (например, Europe/Moscow)
sudo timedatectl set-timezone Europe/Moscow

# Проверка
timedatectl
```

### 3. Настройка hostname

```bash
# Установка имени хоста
sudo hostnamectl set-hostname your-server-name

# Редактирование /etc/hosts
sudo vim /etc/hosts
# Добавить строку:
# 127.0.0.1 your-server-name
```

### 4. Создание пользователя для развертывания (опционально)

```bash
# Создание пользователя
sudo adduser deploy

# Добавление в группу sudo
sudo usermod -aG sudo deploy

# Переключение на нового пользователя
su - deploy
```

---

## Установка Docker и Docker Compose

### 1. Удаление старых версий Docker (если есть)

```bash
sudo apt remove -y docker docker-engine docker.io containerd runc
```

### 2. Установка Docker

```bash
# Добавление официального GPG ключа Docker
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

# Добавление репозитория Docker
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Обновление индекса пакетов
sudo apt update

# Установка Docker Engine, containerd и Docker Compose
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Проверка установки
docker --version
docker compose version
```

### 3. Настройка Docker

```bash
# Добавление текущего пользователя в группу docker (чтобы не использовать sudo)
sudo usermod -aG docker $USER

# Активация изменений в группах (или перелогиньтесь)
newgrp docker

# Включение автозапуска Docker
sudo systemctl enable docker
sudo systemctl enable containerd

# Проверка статуса
sudo systemctl status docker
```

### 4. Настройка vm.max_map_count для Elasticsearch

```bash
# Проверка текущего значения
sysctl vm.max_map_count

# Временная установка (до перезагрузки)
sudo sysctl -w vm.max_map_count=262144

# Постоянная установка
echo "vm.max_map_count=262144" | sudo tee -a /etc/sysctl.conf

# Применение изменений
sudo sysctl -p
```

### 5. Настройка логирования Docker

```bash
# Создание конфигурационного файла
sudo mkdir -p /etc/docker
sudo tee /etc/docker/daemon.json > /dev/null <<EOF
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  },
  "storage-driver": "overlay2"
}
EOF

# Перезапуск Docker
sudo systemctl restart docker
```

---

## Настройка брандмауэра

### UFW (Uncomplicated Firewall)

```bash
# Установка UFW (если не установлен)
sudo apt install -y ufw

# Разрешение SSH (ВАЖНО! Сделать до включения UFW)
sudo ufw allow 22/tcp
sudo ufw allow OpenSSH

# Разрешение HTTP и HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Опционально: Adminer (только если нужен внешний доступ)
# sudo ufw allow 2028/tcp

# Опционально: Kibana (только если нужен внешний доступ)
# sudo ufw allow 2029/tcp

# Включение UFW
sudo ufw enable

# Проверка статуса
sudo ufw status verbose

# Проверка открытых портов
sudo ss -tulpn
```

### Настройка fail2ban (защита от brute-force атак)

```bash
# Установка fail2ban
sudo apt install -y fail2ban

# Создание локальной конфигурации
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local

# Редактирование конфигурации
sudo vim /etc/fail2ban/jail.local
# Убедитесь что включены разделы [sshd] и [nginx-http-auth]

# Запуск и автозапуск
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Проверка статуса
sudo fail2ban-client status
```

---

## Подготовка проекта

### 1. Клонирование репозитория

```bash
# Переход в домашнюю директорию
cd ~

# Клонирование проекта
git clone https://github.com/your-username/my_bagisto.git
cd my_bagisto

# Переключение на production ветку
git checkout add_product_import_add_fcm
```

### 2. Создание директорий

```bash
# Создание необходимых директорий
mkdir -p storage/{logs,framework/{cache,sessions,views},app/public}
mkdir -p bootstrap/cache
mkdir -p docker/nginx/{conf.d,ssl,logs}
mkdir -p docker/certbot/www
mkdir -p docker/mysql/init

# Установка прав доступа
chmod -R 775 storage bootstrap/cache
```

---

## Настройка переменных окружения

### 1. Копирование и настройка .env файла

```bash
# Копирование примера
cp .env.example .env

# Редактирование .env файла
vim .env
```

### 2. Основные переменные для настройки

```env
# === Основные настройки ===
APP_NAME=YourAppName
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_ADMIN_URL=admin
APP_TIMEZONE=Europe/Moscow
APP_LOCALE=ru
APP_CURRENCY=RUB

# APP_KEY будет сгенерирован автоматически скриптом deploy.sh
APP_KEY=

# === База данных MySQL ===
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=bagisto_prod
DB_USERNAME=bagisto_user
DB_PASSWORD=secure_password_here
DB_ROOT_PASSWORD=secure_root_password_here

# === PostgreSQL (опционально) ===
PGSQL_DATABASE=bagisto_pg
PGSQL_USERNAME=bagisto_pg
PGSQL_PASSWORD=secure_pg_password_here

# === Redis ===
REDIS_HOST=redis
REDIS_PASSWORD=secure_redis_password_here
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# === Reverb (WebSockets) ===
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=yourdomain.com
REVERB_PORT=8080
REVERB_SCHEME=https

# === Домен и SSL ===
DOMAIN=yourdomain.com
SSL_EMAIL=admin@yourdomain.com

# === Порты ===
HTTP_PORT=80
HTTPS_PORT=443
ADMINER_PORT=2028
KIBANA_PORT=2029
FORWARD_DB_PORT=3306
FORWARD_REDIS_PORT=6379
FORWARD_PGSQL_PORT=5432
FORWARD_ES_PORT=9200

# === Миграции (по умолчанию false, включать только при необходимости) ===
RUN_MIGRATIONS=false

# === Логирование ===
LOG_CHANNEL=stack
LOG_LEVEL=warning

# === Кеширование ===
RESPONSE_CACHE_ENABLED=true

# === Mail (настроить согласно вашему провайдеру) ===
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# === Push уведомления (FCM) ===
FCM_SERVER_KEY=your_fcm_server_key

# === GigaChat (AI) ===
GIGACHAT_CLIENT_ID=your_client_id
GIGACHAT_CLIENT_SECRET=your_client_secret

# === OpenAI (опционально) ===
OPENAI_API_KEY=your_openai_key

# === Yandex Maps ===
YANDEX_MAPS_API_KEY=your_yandex_maps_key

# === Telescope (только для отладки) ===
TELESCOPE_ENABLED=false
```

### 3. Генерация паролей

```bash
# Генерация безопасных паролей
openssl rand -base64 32  # для DB_PASSWORD
openssl rand -base64 32  # для DB_ROOT_PASSWORD
openssl rand -base64 32  # для REDIS_PASSWORD
openssl rand -base64 32  # для PGSQL_PASSWORD
openssl rand -hex 16     # для REVERB_APP_ID
openssl rand -base64 32  # для REVERB_APP_KEY
openssl rand -base64 32  # для REVERB_APP_SECRET
```

---

## Подготовка Dockerfile

### ВАЖНО: Создание Dockerfile в корне проекта

По документации проекта, `docker-compose.prod.yml` ожидает Dockerfile в корне проекта, но исходный файл находится в `docker/php/Dockerfile`. Необходимо **либо скопировать**, **либо создать симлинк**.

#### Вариант 1: Копирование (рекомендуется)

```bash
cp docker/php/Dockerfile ./Dockerfile
```

#### Вариант 2: Создание симлинка

```bash
ln -s docker/php/Dockerfile ./Dockerfile
```

#### Вариант 3: Модификация docker-compose.prod.yml

Отредактируйте `docker-compose.prod.yml` и измените путь к Dockerfile:

```yaml
services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile  # Изменить эту строку
```

### Проверка требований Dockerfile

Убедитесь, что существуют файлы:
```bash
ls -l docker/php/php.ini
ls -l docker/php/php-cli.ini
```

Если их нет, создайте базовые конфигурации:

```bash
# Создание php.ini
cat > docker/php/php.ini << 'EOF'
[PHP]
post_max_size = 100M
upload_max_filesize = 100M
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
date.timezone = Europe/Moscow

opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1
EOF

# Создание php-cli.ini
cat > docker/php/php-cli.ini << 'EOF'
[PHP]
memory_limit = 1G
max_execution_time = 0
date.timezone = Europe/Moscow
EOF
```

---

## Первоначальное развертывание

### 1. Проверка конфигурации

```bash
# Проверка синтаксиса docker-compose
docker compose -f docker-compose.prod.yml config

# Проверка наличия всех необходимых файлов
ls -la .env Dockerfile docker-compose.prod.yml deploy.sh
```

### 2. Установка прав на скрипты

```bash
chmod +x deploy.sh update.sh
```

### 3. Запуск развертывания

```bash
# Первоначальное развертывание
./deploy.sh
```

### Что делает скрипт deploy.sh:

1. Проверяет наличие Docker и Docker Compose
2. Создает .env если его нет
3. Создает необходимые директории
4. Устанавливает права доступа
5. Собирает Docker образы
6. Запускает MySQL и Redis
7. Ожидает готовности баз данных
8. Генерирует APP_KEY если не установлен
9. Создает или получает SSL сертификаты через Let's Encrypt
10. Запускает все сервисы
11. Выполняет миграции базы данных
12. Кеширует конфигурацию, маршруты, представления
13. Прогревает кеши (nomenclature, mobile-settings, catalog-v2)

### 4. Мониторинг процесса развертывания

```bash
# В отдельном терминале можно следить за логами
docker compose -f docker-compose.prod.yml logs -f

# Или за конкретным сервисом
docker compose -f docker-compose.prod.yml logs -f app
```

---

## Настройка SSL сертификатов

### Автоматическая настройка через Let's Encrypt

Скрипт `deploy.sh` автоматически получает SSL сертификаты через Certbot. Убедитесь что:

1. **DNS настроен правильно**: домен указывает на IP вашего сервера
2. **Порты 80 и 443 открыты** в брандмауэре
3. **В .env указаны корректные значения**:
   ```env
   DOMAIN=yourdomain.com
   SSL_EMAIL=admin@yourdomain.com
   ```

### Ручное получение сертификата (если автоматика не сработала)

```bash
# Остановите nginx если запущен
docker compose -f docker-compose.prod.yml stop nginx

# Получите сертификат
docker compose -f docker-compose.prod.yml run --rm --entrypoint "" certbot \
    certbot certonly --standalone \
    --preferred-challenges http \
    --email admin@yourdomain.com \
    --domain yourdomain.com \
    --agree-tos \
    --no-eff-email \
    --non-interactive

# Запустите nginx
docker compose -f docker-compose.prod.yml up -d nginx
```

### Проверка срока действия сертификата

```bash
# Проверка даты истечения
docker compose -f docker-compose.prod.yml exec certbot certbot certificates

# Ручное обновление (certbot делает это автоматически каждые 12 часов)
docker compose -f docker-compose.prod.yml exec certbot certbot renew

# Перезагрузка nginx после обновления
docker compose -f docker-compose.prod.yml exec nginx nginx -s reload
```

### Тестирование SSL

```bash
# Проверка через curl
curl -I https://yourdomain.com

# Онлайн проверка
# Откройте в браузере: https://www.ssllabs.com/ssltest/
```

---

## Проверка работоспособности

### 1. Проверка статуса контейнеров

```bash
# Просмотр всех контейнеров
docker compose -f docker-compose.prod.yml ps

# Все контейнеры должны быть в статусе "Up" и "healthy"
```

### 2. Проверка логов

```bash
# Логи приложения
docker compose -f docker-compose.prod.yml logs app

# Логи nginx
docker compose -f docker-compose.prod.yml logs nginx

# Логи MySQL
docker compose -f docker-compose.prod.yml logs mysql

# Логи queue worker
docker compose -f docker-compose.prod.yml logs queue

# Все логи
docker compose -f docker-compose.prod.yml logs
```

### 3. Проверка здоровья сервисов

```bash
# Проверка app
docker compose -f docker-compose.prod.yml exec app php artisan octane:status

# Проверка MySQL
docker compose -f docker-compose.prod.yml exec mysql mysqladmin ping -h localhost --silent

# Проверка Redis
docker compose -f docker-compose.prod.yml exec redis redis-cli ping
```

### 4. Проверка доступности

```bash
# HTTP
curl -I http://yourdomain.com

# HTTPS
curl -I https://yourdomain.com

# Health endpoint (если настроен)
curl https://yourdomain.com/health

# Adminer
curl -I http://yourdomain.com:2028

# Kibana
curl -I http://yourdomain.com:2029
```

### 5. Проверка портов

```bash
# Проверка открытых портов
sudo ss -tulpn | grep LISTEN

# Должны быть открыты:
# 80   - HTTP (nginx)
# 443  - HTTPS (nginx)
# 2028 - Adminer (nginx)
# 2029 - Kibana (nginx)
```

### 6. Тестовый вход в админ-панель

1. Откройте браузер: `https://yourdomain.com/admin`
2. Войдите с учетными данными администратора (созданными при установке)

---

## Обновление приложения

### Автоматическое обновление через update.sh

```bash
# Переход в директорию проекта
cd ~/my_bagisto

# Запуск скрипта обновления
./update.sh
```

### Что делает скрипт update.sh:

1. Проверяет наличие Git репозитория
2. Проверяет uncommitted изменения
3. Получает обновления из Git
4. Проверяет наличие новых коммитов
5. Обновляет код через `git pull`
6. Пересобирает Docker образ app
7. Очищает кеш конфигурации
8. Пересоздает контейнер app
9. Запускает миграции базы данных
10. Перезапускает queue worker
11. Прогревает кеши
12. Перезапускает nginx при необходимости

### Ручное обновление

```bash
# 1. Резервное копирование (см. раздел "Резервное копирование")

# 2. Получение изменений
git fetch origin
git pull origin add_product_import_add_fcm

# 3. Пересборка образа
docker compose -f docker-compose.prod.yml build app

# 4. Очистка кеша
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-v7.php
rm -f bootstrap/cache/events.php

# 5. Пересоздание контейнера app
docker compose -f docker-compose.prod.yml up -d --force-recreate app

# 6. Ожидание готовности
sleep 10

# 7. Миграции
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# 8. Перезапуск queue worker
docker compose -f docker-compose.prod.yml restart queue

# 9. Прогрев кешей
docker compose -f docker-compose.prod.yml exec app php artisan nomenclature:warm-cache
docker compose -f docker-compose.prod.yml exec app php artisan mobile-settings:warm-cache
docker compose -f docker-compose.prod.yml exec app php artisan catalog-v2:warm-cache

# 10. Проверка статуса
docker compose -f docker-compose.prod.yml ps
```

---

## Мониторинг и логи

### Просмотр логов

```bash
# Логи всех сервисов в реальном времени
docker compose -f docker-compose.prod.yml logs -f

# Логи конкретного сервиса
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml logs -f nginx
docker compose -f docker-compose.prod.yml logs -f mysql
docker compose -f docker-compose.prod.yml logs -f queue
docker compose -f docker-compose.prod.yml logs -f redis

# Последние N строк логов
docker compose -f docker-compose.prod.yml logs --tail=100 app

# Логи за определенный период
docker compose -f docker-compose.prod.yml logs --since 30m app
docker compose -f docker-compose.prod.yml logs --since 2024-01-01 app
```

### Логи Laravel

```bash
# Просмотр логов Laravel
docker compose -f docker-compose.prod.yml exec app tail -f storage/logs/laravel.log

# Просмотр с фильтрацией
docker compose -f docker-compose.prod.yml exec app tail -f storage/logs/laravel.log | grep ERROR
```

### Логи Nginx

```bash
# Access log
tail -f docker/nginx/logs/access.log

# Error log
tail -f docker/nginx/logs/error.log
```

### Мониторинг ресурсов

```bash
# Статистика использования ресурсов контейнерами
docker stats

# Использование дискового пространства Docker
docker system df

# Детальная информация
docker system df -v
```

### Мониторинг системы

```bash
# Использование CPU и памяти
htop

# Использование диска
df -h

# Использование inode
df -i

# Сетевые подключения
sudo ss -tulpn

# Проверка load average
uptime
```

---

## Резервное копирование

### 1. Скрипт резервного копирования

Создайте скрипт `backup.sh`:

```bash
#!/bin/bash

set -e

BACKUP_DIR="/backup/bagisto"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
PROJECT_DIR="$HOME/my_bagisto"

# Создание директории для бэкапов
mkdir -p "$BACKUP_DIR"

echo "=== Начало резервного копирования: $TIMESTAMP ==="

# 1. Бэкап базы данных MySQL
echo "Создание дампа MySQL..."
docker compose -f "$PROJECT_DIR/docker-compose.prod.yml" exec -T mysql \
    mysqldump -u root -p"${DB_ROOT_PASSWORD}" "${DB_DATABASE}" | \
    gzip > "$BACKUP_DIR/mysql_${TIMESTAMP}.sql.gz"

# 2. Бэкап базы данных PostgreSQL (если используется)
if docker compose -f "$PROJECT_DIR/docker-compose.prod.yml" ps postgres | grep -q "Up"; then
    echo "Создание дампа PostgreSQL..."
    docker compose -f "$PROJECT_DIR/docker-compose.prod.yml" exec -T postgres \
        pg_dump -U "${PGSQL_USERNAME}" "${PGSQL_DATABASE}" | \
        gzip > "$BACKUP_DIR/postgres_${TIMESTAMP}.sql.gz"
fi

# 3. Бэкап storage
echo "Архивирование storage..."
tar -czf "$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz" -C "$PROJECT_DIR" storage

# 4. Бэкап .env
echo "Копирование .env..."
cp "$PROJECT_DIR/.env" "$BACKUP_DIR/env_${TIMESTAMP}"

# 5. Бэкап конфигураций nginx
echo "Архивирование конфигураций nginx..."
tar -czf "$BACKUP_DIR/nginx_${TIMESTAMP}.tar.gz" -C "$PROJECT_DIR" docker/nginx

# Удаление старых бэкапов (старше 7 дней)
echo "Удаление старых бэкапов (>7 дней)..."
find "$BACKUP_DIR" -type f -mtime +7 -delete

echo "=== Резервное копирование завершено: $(date +"%Y%m%d_%H%M%S") ==="
echo "Бэкапы сохранены в: $BACKUP_DIR"
```

```bash
# Установка прав
chmod +x backup.sh

# Создание директории для бэкапов
sudo mkdir -p /backup/bagisto
sudo chown $USER:$USER /backup/bagisto
```

### 2. Автоматизация через Cron

```bash
# Редактирование crontab
crontab -e

# Добавление задания (ежедневно в 3:00)
0 3 * * * /home/deploy/my_bagisto/backup.sh >> /var/log/bagisto-backup.log 2>&1
```

### 3. Восстановление из резервной копии

```bash
#!/bin/bash

# Остановка сервисов
docker compose -f docker-compose.prod.yml down

# Восстановление MySQL
zcat /backup/bagisto/mysql_20240101_030000.sql.gz | \
    docker compose -f docker-compose.prod.yml exec -T mysql \
    mysql -u root -p"${DB_ROOT_PASSWORD}" "${DB_DATABASE}"

# Восстановление storage
rm -rf storage
tar -xzf /backup/bagisto/storage_20240101_030000.tar.gz

# Восстановление .env
cp /backup/bagisto/env_20240101_030000 .env

# Восстановление nginx конфигураций
tar -xzf /backup/bagisto/nginx_20240101_030000.tar.gz

# Запуск сервисов
docker compose -f docker-compose.prod.yml up -d
```

---

## Устранение неполадок

### Контейнеры не запускаются

```bash
# Проверка логов контейнера
docker compose -f docker-compose.prod.yml logs app

# Попробуйте пересоздать контейнеры
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d

# Очистка неиспользуемых ресурсов
docker system prune -a --volumes
```

### Ошибки при сборке образа

```bash
# Пересборка без кеша
docker compose -f docker-compose.prod.yml build --no-cache app

# Проверка Dockerfile
cat Dockerfile
```

### MySQL не запускается

```bash
# Проверка логов
docker compose -f docker-compose.prod.yml logs mysql

# Проверка прав на volume
docker volume inspect my_bagisto_mysql_data

# Удаление и пересоздание volume (ВНИМАНИЕ: удалит все данные!)
docker compose -f docker-compose.prod.yml down -v
docker compose -f docker-compose.prod.yml up -d
```

### Elasticsearch не запускается

```bash
# Проверка vm.max_map_count
sysctl vm.max_map_count

# Если значение меньше 262144, установите:
sudo sysctl -w vm.max_map_count=262144
echo "vm.max_map_count=262144" | sudo tee -a /etc/sysctl.conf

# Перезапуск Elasticsearch
docker compose -f docker-compose.prod.yml restart elasticsearch
```

### Ошибки прав доступа

```bash
# Установка правильных прав
chmod -R 775 storage bootstrap/cache

# Если используется Docker, проверьте владельца
ls -la storage/

# Изменение владельца (замените www-data на пользователя из контейнера)
docker compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage bootstrap/cache
```

### Проблемы с SSL

```bash
# Проверка наличия сертификатов
ls -la docker/nginx/ssl/live/yourdomain.com/

# Проверка конфигурации nginx
docker compose -f docker-compose.prod.yml exec nginx nginx -t

# Ручное получение сертификата
docker compose -f docker-compose.prod.yml run --rm --entrypoint "" certbot \
    certbot certonly --webroot \
    --webroot-path=/var/www/certbot \
    --email admin@yourdomain.com \
    --domain yourdomain.com \
    --agree-tos \
    --no-eff-email \
    --non-interactive

# Перезапуск nginx
docker compose -f docker-compose.prod.yml restart nginx
```

### Очистка кеша приложения

```bash
# Очистка всех кешей
docker compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker compose -f docker-compose.prod.yml exec app php artisan config:clear
docker compose -f docker-compose.prod.yml exec app php artisan route:clear
docker compose -f docker-compose.prod.yml exec app php artisan view:clear

# Пересоздание кешей
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache
```

### Перезапуск queue worker

```bash
# Мягкий перезапуск (завершит текущие задачи)
docker compose -f docker-compose.prod.yml exec queue php artisan queue:restart

# Жесткий перезапуск контейнера
docker compose -f docker-compose.prod.yml restart queue
```

### Проверка использования дискового пространства

```bash
# Общее использование
df -h

# Docker volumes
docker system df -v

# Логи контейнеров
du -sh /var/lib/docker/containers/*/*-json.log

# Очистка старых образов и контейнеров
docker system prune -a
```

### Низкая производительность

```bash
# Проверка нагрузки
htop
docker stats

# Проверка запросов к БД (slow query log)
docker compose -f docker-compose.prod.yml exec mysql \
    mysql -u root -p"${DB_ROOT_PASSWORD}" -e "SHOW VARIABLES LIKE 'slow_query%';"

# Включение opcache (должно быть включено по умолчанию)
docker compose -f docker-compose.prod.yml exec app php -i | grep opcache

# Проверка Redis
docker compose -f docker-compose.prod.yml exec redis redis-cli INFO stats
```

### Проблемы с WebSocket (Reverb)

```bash
# Проверка статуса Reverb
docker compose -f docker-compose.prod.yml logs reverb

# Проверка подключения
curl http://reverb:8080

# Перезапуск Reverb
docker compose -f docker-compose.prod.yml restart reverb
```

---

## Дополнительная настройка

### Настройка Logrotate для логов Docker

```bash
sudo tee /etc/logrotate.d/docker-containers > /dev/null <<EOF
/var/lib/docker/containers/*/*.log {
    rotate 7
    daily
    compress
    missingok
    delaycompress
    copytruncate
}
EOF
```

### Мониторинг через systemd

Создайте systemd service для автоматического запуска при загрузке системы:

```bash
sudo tee /etc/systemd/system/bagisto.service > /dev/null <<EOF
[Unit]
Description=Bagisto E-commerce Platform
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=/home/deploy/my_bagisto
ExecStart=/usr/bin/docker compose -f docker-compose.prod.yml up -d
ExecStop=/usr/bin/docker compose -f docker-compose.prod.yml down
ExecReload=/usr/bin/docker compose -f docker-compose.prod.yml restart

[Install]
WantedBy=multi-user.target
EOF

# Активация
sudo systemctl daemon-reload
sudo systemctl enable bagisto.service

# Управление
sudo systemctl start bagisto
sudo systemctl stop bagisto
sudo systemctl restart bagisto
sudo systemctl status bagisto
```

---

## Заключение

После выполнения всех шагов у вас должен быть полностью функциональный production-ready сервер с Bagisto.

### Контрольный чек-лист:

- [ ] Ubuntu сервер обновлен
- [ ] Docker и Docker Compose установлены
- [ ] Брандмауэр настроен (порты 80, 443, 22 открыты)
- [ ] fail2ban установлен и настроен
- [ ] vm.max_map_count установлен в 262144
- [ ] Проект склонирован из Git
- [ ] .env файл настроен со всеми необходимыми переменными
- [ ] Dockerfile находится в корне проекта
- [ ] deploy.sh выполнен успешно
- [ ] SSL сертификаты получены
- [ ] Все контейнеры запущены и в статусе healthy
- [ ] Веб-интерфейс доступен через HTTPS
- [ ] Настроено автоматическое резервное копирование
- [ ] Настроен systemd service для автозапуска

### Полезные команды для быстрого доступа:

```bash
# Алиасы (добавьте в ~/.bashrc)
alias dc='docker compose -f docker-compose.prod.yml'
alias dcup='docker compose -f docker-compose.prod.yml up -d'
alias dcdown='docker compose -f docker-compose.prod.yml down'
alias dcrestart='docker compose -f docker-compose.prod.yml restart'
alias dclogs='docker compose -f docker-compose.prod.yml logs -f'
alias dcps='docker compose -f docker-compose.prod.yml ps'
alias artisan='docker compose -f docker-compose.prod.yml exec app php artisan'
```

### Поддержка

Если возникли проблемы с развертыванием, проверьте:
1. Логи контейнеров: `docker compose -f docker-compose.prod.yml logs`
2. Логи системы: `journalctl -xe`
3. Логи Docker: `sudo journalctl -u docker.service`
4. Github Issues проекта
5. Официальную документацию Bagisto: https://devdocs.bagisto.com/
