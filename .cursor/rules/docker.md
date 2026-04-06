# Docker Infrastructure Specification

## Overview

The project uses two Docker Compose configurations:

| File | Purpose |
|---|---|
| `docker-compose.yml` | Local development via Laravel Sail |
| `docker-compose.prod.yml` | Production deployment |

There is **no custom `Dockerfile`** in the project root. The production `app`, `reverb`, and `queue` services reference a `Dockerfile` build context — it must be provided externally (e.g., generated or placed at project root before deployment). The dev environment uses the standard Sail runtime from `vendor/laravel/sail/runtimes/8.3`.

---

## Production Services (`docker-compose.prod.yml`)

### `app` — Laravel Application (RoadRunner / Octane)

- **Image**: custom build (`Dockerfile` in project root)
- **Internal port**: `8000` (RoadRunner HTTP)
- **Working dir**: `/var/www/html`
- **Mounted volumes**:
  - `./storage` → `/var/www/html/storage`
  - `./bootstrap/cache` → `/var/www/html/bootstrap/cache`
  - `./public` → `/var/www/html/public`
- **Entrypoint**: `docker/php/entrypoint.sh`
- **Depends on**: `mysql` (healthy), `redis` (healthy)
- **Health check**: `php artisan octane:status` or `nc -z localhost 8000`
- **Key env vars**: `APP_ENV`, `APP_KEY`, `OCTANE_SERVER=roadrunner`, `RUN_MIGRATIONS`

#### Startup sequence (entrypoint.sh)

1. Wait for MySQL on port 3306
2. Wait for Redis on port 6379
3. `chown/chmod` storage and bootstrap/cache
4. `php artisan storage:link`
5. If `APP_ENV=production`: cache config / routes / views / events + custom warm-cache commands
6. If `RUN_MIGRATIONS=true`: `php artisan migrate --force`
7. `exec "$@"` — hand off to the container command

### `nginx` — Reverse Proxy

- **Image**: `nginx:alpine`
- **Ports**:
  - `${HTTP_PORT:-80}:80`
  - `${HTTPS_PORT:-443}:443`
  - `${ADMINER_PORT:-2028}:2028`
- **Config files**:
  - `docker/nginx/nginx.conf` → `/etc/nginx/nginx.conf`
  - `docker/nginx/default.conf` → `/etc/nginx/conf.d/default.conf`
  - `docker/nginx/ssl.conf` → `/etc/nginx/templates/ssl-params.inc.template`
- **SSL certificate path**: `/etc/nginx/ssl/live/$DOMAIN/` (Let's Encrypt via Certbot)
- **Volumes**: `./public` (read-only), `./storage/app/public` (read-only), SSL dir, Certbot webroot

#### Routing rules (default.conf)

| Location | Upstream | Notes |
|---|---|---|
| `/.well-known/acme-challenge/` | filesystem | Let's Encrypt challenge |
| `/health` | inline 200 | Health check endpoint |
| `/storage/` | filesystem alias | Served directly, 1-year cache |
| `~* \.(jpg\|png\|css\|js\|…)$` | filesystem | Static assets, 1-year cache |
| `/cache/` | `app:8000` | Intervention image cache (dynamic) |
| `/api-docs/` | `app:8000` | Swagger / Scribe docs |
| `/app/` | `reverb:8080` | WebSocket (timeout 7d) |
| `~ ^/(api\|admin\|installer)` | `app:8000` | Laravel routes |
| `~ \.php$` | `app:8000` | PHP files via RoadRunner |
| `/` | `app:8000` via `@roadrunner` | Main application |
| Port 2028 | `adminer:8080` | Adminer UI (MySQL + PostgreSQL) |
| Port 2029 | `kibana:5601` | Kibana UI |
| Port 80 | redirect → HTTPS | All HTTP traffic |

### `mysql` — Database

- **Image**: `mysql:8.0`
- **Port**: `127.0.0.1:${FORWARD_DB_PORT:-3306}:3306`
- **Charset**: `utf8mb4` / `utf8mb4_unicode_ci`
- **Auth**: `mysql_native_password`
- **Volume**: named `mysql_data`
- **Init scripts**: `./docker/mysql/init/` → `/docker-entrypoint-initdb.d/`
- **Health check**: `mysqladmin ping -h localhost`

### `redis` — Cache / Session / Queues

- **Image**: `redis:7-alpine`
- **Port**: `127.0.0.1:${FORWARD_REDIS_PORT:-6379}:6379`
- **Max memory**: `512mb` (policy: `allkeys-lru`)
- **Persistence**: AOF enabled (`--appendonly yes`)
- **Password**: optional via `REDIS_PASSWORD`
- **Volume**: named `redis_data`
- **Health check**: `redis-cli ping` (with password if set)

### `reverb` — Laravel Reverb WebSocket Server

- **Image**: custom build (same `Dockerfile` as `app`)
- **Command**: `php artisan reverb:start --host=0.0.0.0 --port=8080`
- **Internal port**: `8080`
- **Depends on**: `redis` (healthy), `app` (healthy)
- **Health check**: `nc -z localhost 8080`

### `queue` — Queue Worker

- **Image**: custom build (same `Dockerfile` as `app`)
- **Command**: `php artisan queue:work redis --queue=default,broadcastable --sleep=3 --tries=3 --max-time=3600 --timeout=300`
- **Depends on**: `mysql`, `redis`, `app`, `reverb` — all healthy
- **Health check**: `php artisan queue:failed`

### `postgres` — PostgreSQL Database

- **Image**: `postgres:16-alpine`
- **Port**: `127.0.0.1:${FORWARD_PGSQL_PORT:-5432}:5432`
- **Env**: `POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD` из переменных `PGSQL_*`
- **Volume**: named `postgres_data`
- **Health check**: `pg_isready -U $POSTGRES_USER -d $POSTGRES_DB`
- **Подключение через Adminer**: выбрать «PostgreSQL», Server = `postgres`, Port = `5432`

### `elasticsearch` — Full-Text Search

- **Image**: `docker.elastic.co/elasticsearch/elasticsearch:7.17.0`
- **Port**: `127.0.0.1:${FORWARD_ES_PORT:-9200}:9200` (только localhost)
- **Settings**: `xpack.security.enabled=false`, `discovery.type=single-node`, `ES_JAVA_OPTS=-Xms512m -Xmx512m`
- **ulimits**: `memlock: -1/-1`, `nofile: 65536/65536`
- **Volume**: named `elasticsearch_data`
- **Health check**: `curl /_cluster/health` не содержит `"status":"red"`
- **start_period**: 60s

### `kibana` — Elasticsearch UI

- **Image**: `docker.elastic.co/kibana/kibana:7.17.0`
- **Expose**: `5601` (internal only — served through nginx on port 2029)
- **Env**: `ELASTICSEARCH_HOSTS=http://elasticsearch:9200`
- **Depends on**: `elasticsearch` (healthy)
- **Health check**: `curl /api/status` содержит `"level":"available"`
- **start_period**: 90s

### `adminer` — Database UI

- **Image**: `adminer:4`
- **Expose**: `8080` (internal only — served through nginx on port 2028)
- **Default server**: `mysql`
- **Поддерживаемые БД**: MySQL (`mysql:3306`) и PostgreSQL (`postgres:5432`)

### `certbot` — SSL Certificate Renewal

- **Image**: `certbot/certbot:latest`
- **Volumes**: `./docker/nginx/ssl` ↔ `/etc/letsencrypt`
- **Behaviour**: infinite loop renewing every 12 hours

---

## Development Services (`docker-compose.yml` — Laravel Sail)

| Service | Image | Ports |
|---|---|---|
| `laravel.test` | `sail-8.3/app` (Sail runtime) | `${APP_PORT:-80}`, `${VITE_PORT:-5173}` |
| `mysql` | `mysql/mysql-server:8.0` | `${FORWARD_DB_PORT:-3306}:3306` |
| `redis` | `redis:alpine` | `${FORWARD_REDIS_PORT:-6379}:6379` |
| `elasticsearch` | `elasticsearch:7.17.0` | `9200`, `9300` |
| `kibana` | `kibana:7.17.0` | `5601` |
| `mailpit` | `axllent/mailpit:latest` | `1025` (SMTP), `8025` (UI) |

**Elasticsearch** settings:
- `xpack.security.enabled=false`
- `discovery.type=single-node`
- `ES_JAVA_OPTS=-Xms256m -Xmx256m`
- `memlock ulimits: soft/hard -1`
- Volume: `sail-elasticsearch`

---

## PHP Configuration

### `docker/php/php.ini` (web requests via RoadRunner)

```ini
memory_limit = 512M
post_max_size = 256M
```

### `docker/php/php-cli.ini` (artisan / queue workers)

| Setting | Value |
|---|---|
| `memory_limit` | `512M` |
| `max_execution_time` | `0` (unlimited) |
| `post_max_size` | `100M` |
| `opcache.enable` | `1` |
| `opcache.enable_cli` | `1` |
| `opcache.memory_consumption` | `256` MB |
| `opcache.validate_timestamps` | `0` (OFF — no file-change detection) |
| `opcache.max_accelerated_files` | `20000` |
| `date.timezone` | `UTC` |
| `expose_php` | `Off` |

> **Important**: `opcache.validate_timestamps=0` means PHP will **not** detect file changes automatically. After deploying new code, the container must be restarted or OPcache must be cleared manually.

---

## Nginx Configuration

### `docker/nginx/nginx.conf` — Global settings

| Setting | Value |
|---|---|
| `worker_processes` | `auto` |
| `worker_connections` | `1024` |
| `client_max_body_size` | `256M` |
| `client_body_buffer_size` | `128k` |
| `keepalive_timeout` | `65` |
| `gzip` | enabled (level 6, min 1000 bytes) |
| `server_tokens` | `off` |

Security headers applied globally: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`.

### `docker/nginx/ssl.conf` — SSL parameters

- Protocols: `TLSv1.2`, `TLSv1.3`
- OCSP Stapling: enabled
- Session cache: `shared:SSL:50m`, timeout `1d`, tickets off
- Cert paths use `$DOMAIN` env variable (via nginx template)
- DNS resolver: `8.8.8.8`, `8.8.4.4`

---

## Required Environment Variables

These variables **must** be set in `.env` (or injected) before running production:

| Variable | Description |
|---|---|
| `APP_KEY` | Laravel application key |
| `APP_URL` | Full application URL (e.g. `https://example.com`) |
| `DOMAIN` | Domain for SSL certificates and Reverb host |
| `DB_DATABASE` | MySQL database name |
| `DB_USERNAME` | MySQL user |
| `DB_PASSWORD` | MySQL user password |
| `DB_ROOT_PASSWORD` | MySQL root password |
| `REVERB_APP_ID` | Laravel Reverb app ID |
| `REVERB_APP_KEY` | Laravel Reverb app key |
| `REVERB_APP_SECRET` | Laravel Reverb app secret |
| `PGSQL_PASSWORD` | PostgreSQL user password |

Optional but commonly set:

| Variable | Default | Description |
|---|---|---|
| `APP_ENV` | `production` | Application environment |
| `APP_DEBUG` | `false` | Debug mode |
| `APP_NAME` | `Surprise` | Used as container name prefix |
| `APP_LOCALE` | `ru` | Application locale |
| `APP_CURRENCY` | `RUB` | Default currency |
| `REDIS_PASSWORD` | _(empty)_ | Redis auth password |
| `RUN_MIGRATIONS` | `false` | Run migrations on container start |
| `TELESCOPE_ENABLED` | `false` | Enable Laravel Telescope |
| `RESPONSE_CACHE_ENABLED` | `true` | Enable response caching |
| `HTTP_PORT` | `80` | Host HTTP port |
| `HTTPS_PORT` | `443` | Host HTTPS port |
| `ADMINER_PORT` | `2028` | Host Adminer port |
| `PGSQL_DATABASE` | `app` | PostgreSQL database name |
| `PGSQL_USERNAME` | `app` | PostgreSQL user |
| `FORWARD_DB_PORT` | `3306` | Host MySQL port (localhost only) |
| `FORWARD_PGSQL_PORT` | `5432` | Host PostgreSQL port (localhost only) |
| `FORWARD_REDIS_PORT` | `6379` | Host Redis port (localhost only) |
| `FORWARD_ES_PORT` | `9200` | Host Elasticsearch port (localhost only) |
| `KIBANA_PORT` | `2029` | Host Kibana port (via nginx HTTPS) |
| `LOG_LEVEL` | `warning` | Log level |
| `SESSION_LIFETIME` | `120` | Session lifetime (minutes) |

---

## Networks & Volumes

### Production networks

- `app-network` (bridge) — shared by all production services

### Production named volumes

- `mysql_data` — MySQL data directory
- `redis_data` — Redis AOF persistence
- `postgres_data` — PostgreSQL data directory
- `elasticsearch_data` — Elasticsearch indices

### Dev (Sail) named volumes

- `sail-mysql`, `sail-redis`, `sail-elasticsearch`

---

## Agent Rules & Conventions

1. **No Dockerfile at root** — never reference or assume `Dockerfile` exists unless it has been explicitly created. The `app`, `reverb`, and `queue` services require a Dockerfile to build.

2. **Mounted directories** — only `storage/`, `bootstrap/cache/`, and `public/` are mounted into `app`. Do NOT mount the entire project root in production.

3. **Migrations** — are NOT run automatically. Set `RUN_MIGRATIONS=true` only for intentional migration runs (e.g. first deploy or post-deploy step).

4. **OPcache** — `validate_timestamps=0` in production. After code changes, the container must be restarted (`docker compose restart app`) for PHP to pick up new bytecode.

5. **Storage files** — served by nginx directly via `/storage/` → `/var/www/html/storage/app/public/` alias. Do NOT route storage requests to RoadRunner.

6. **WebSocket** — the `/app/` path is proxied to `reverb:8080` with infinite timeouts (`7d`). Do not add buffering to this location.

7. **SSL** — certificates are managed by Certbot. The `$DOMAIN` variable must match nginx template rendering. Self-signed fallback lines are commented in `ssl.conf`.

8. **Adminer** — exposed only on the internal Docker network (`expose`, not `ports`). Access is routed through nginx on port `2028` over HTTPS.

9. **Queue connection** — always `redis` in production. Queue worker processes `default` and `broadcastable` queues.

10. **Log rotation** — all custom-built services use `json-file` driver with `max-size: 10m`, `max-file: 3`.

11. **PostgreSQL** — separate from MySQL; used for secondary workloads. Connect via Adminer (port 2028) selecting PostgreSQL engine and server `postgres`.

12. **Elasticsearch memory** — requires `vm.max_map_count=262144` on the Docker host. Set permanently: `echo 'vm.max_map_count=262144' >> /etc/sysctl.conf && sysctl -p`. Without this, Elasticsearch will fail to start.

13. **Kibana** — accessible at `https://<DOMAIN>:2029`. Do not expose port 5601 directly to the host.

14. **Pinned image versions** — all images use fixed tags (not `latest`) to prevent unexpected breakage on re-pull: `nginx:1.27-alpine`, `adminer:4`, `certbot/certbot:v2`, `postgres:16-alpine`, `elasticsearch/kibana:7.17.0`.

11. **PostgreSQL** — separate from MySQL; used for secondary workloads. Connect via Adminer (port 2028) selecting PostgreSQL engine and server `postgres`.

12. **Elasticsearch memory** — requires `vm.max_map_count=262144` on the Docker host. Set permanently: `echo 'vm.max_map_count=262144' >> /etc/sysctl.conf && sysctl -p`. Without this, Elasticsearch will fail to start.

13. **Kibana** — accessible at `https://<DOMAIN>:2029`. Do not expose port 5601 directly to the host.

14. **Pinned image versions** — all images use fixed tags (not `latest`) to prevent unexpected breakage on re-pull: `nginx:1.27-alpine`, `adminer:4`, `certbot/certbot:v2`, `postgres:16-alpine`, `elasticsearch/kibana:7.17.0`.
