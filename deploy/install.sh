#!/usr/bin/env bash
# =============================================================================
# Bagisto Bare-Metal Installer
# Ubuntu 22.04 / 24.04
#
# Stack:
#   Nginx  → reverse proxy
#   PHP 8.3 CLI + Laravel Octane (RoadRunner) → application server
#   MySQL 8.0 → primary database
#   Redis 7   → cache / session / queue
#   Elasticsearch 7.17 → product search
#   Supervisor → queue workers + Reverb + scheduler
#   Certbot → Let's Encrypt SSL
#   Node.js 20 + npm → frontend build
#
# Usage:
#   sudo bash deploy/install.sh
# =============================================================================

set -euo pipefail
IFS=$'\n\t'

# ── Paths ─────────────────────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIGS_DIR="${SCRIPT_DIR}/configs"
ENV_FILE="${SCRIPT_DIR}/.env.deploy"

# ── Colors ───────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

log()    { echo -e "${GREEN}[+]${NC} $*"; }
warn()   { echo -e "${YELLOW}[!]${NC} $*"; }
error()  { echo -e "${RED}[✗] ERROR:${NC} $*" >&2; }
header() { echo -e "\n${BLUE}${BOLD}╔══ $* ══╗${NC}"; }
step()   { echo -e "${BLUE}  ›${NC} $*"; }
ok()     { echo -e "  ${GREEN}✓${NC} $*"; }

die() {
    error "$*"
    exit 1
}

# ── Load .env.deploy ──────────────────────────────────────────────────────────
load_env() {
    [[ -f "$ENV_FILE" ]] || die "$ENV_FILE not found. Copy deploy/.env.deploy and fill in values."

    # shellcheck source=/dev/null
    set -o allexport
    source "$ENV_FILE"
    set +o allexport

    # Required variables
    local required=(DOMAIN SSL_EMAIL DB_DATABASE DB_USERNAME DB_PASSWORD DB_ROOT_PASSWORD APP_REPO)
    local missing=()
    for var in "${required[@]}"; do
        [[ -n "${!var:-}" ]] || missing+=("$var")
    done
    [[ ${#missing[@]} -eq 0 ]] || die "Missing required variables in .env.deploy: ${missing[*]}"

    # Defaults
    APP_DIR="${APP_DIR:-/var/www/bagisto}"
    APP_USER="${APP_USER:-www-data}"
    APP_PORT="${APP_PORT:-8000}"
    APP_BRANCH="${APP_BRANCH:-main}"
    REVERB_PORT="${REVERB_PORT:-8080}"
    OCTANE_WORKERS="${OCTANE_WORKERS:-auto}"
    QUEUE_PROCESSES="${QUEUE_PROCESSES:-2}"
    RUN_MIGRATIONS="${RUN_MIGRATIONS:-false}"
    NODE_MAJOR="${NODE_MAJOR:-20}"
    RR_VERSION="${RR_VERSION:-2024.3.5}"
    ES_JAVA_HEAP="${ES_JAVA_HEAP:-512m}"
    REDIS_PASSWORD="${REDIS_PASSWORD:-}"
}

# ── Pre-flight ────────────────────────────────────────────────────────────────
preflight() {
    header "Pre-flight checks"

    [[ $EUID -eq 0 ]] || die "This script must be run as root (use: sudo bash deploy/install.sh)"

    # Ubuntu 22.04 / 24.04
    if [[ -f /etc/os-release ]]; then
        # shellcheck source=/dev/null
        source /etc/os-release
        [[ "$ID" == "ubuntu" ]] || warn "Detected OS: $PRETTY_NAME — script is tested on Ubuntu 22.04/24.04"
        case "${VERSION_ID:-}" in
            22.04|24.04) ok "Ubuntu $VERSION_ID" ;;
            *) warn "Ubuntu $VERSION_ID — proceed with caution" ;;
        esac
    fi

    # Check essential tools
    for cmd in curl wget git openssl; do
        command -v "$cmd" &>/dev/null || apt-get install -y -qq "$cmd"
    done

    ok "Pre-flight passed"
}

# ── System packages ───────────────────────────────────────────────────────────
install_base_packages() {
    header "System packages"

    export DEBIAN_FRONTEND=noninteractive

    step "apt update + upgrade"
    apt-get update -qq
    apt-get upgrade -y -qq

    step "Installing base packages"
    apt-get install -y -qq \
        curl wget gnupg2 git unzip zip ca-certificates lsb-release \
        software-properties-common apt-transport-https \
        nginx mysql-server redis-server supervisor \
        certbot python3-certbot-nginx \
        netcat-openbsd openssl acl \
        build-essential

    ok "Base packages installed"
}

# ── PHP 8.3 ──────────────────────────────────────────────────────────────────
install_php() {
    header "PHP 8.3"

    if ! dpkg -l php8.3-cli &>/dev/null 2>&1; then
        step "Adding ondrej/php PPA"
        add-apt-repository -y ppa:ondrej/php
        apt-get update -qq
    fi

    step "Installing PHP 8.3 CLI + extensions"
    apt-get install -y -qq \
        php8.3-cli \
        php8.3-mbstring \
        php8.3-xml \
        php8.3-bcmath \
        php8.3-curl \
        php8.3-gd \
        php8.3-zip \
        php8.3-intl \
        php8.3-opcache \
        php8.3-mysql \
        php8.3-pgsql \
        php8.3-redis \
        php8.3-pcntl \
        php8.3-sockets \
        php8.3-exif \
        php8.3-dom \
        php8.3-calendar \
        php8.3-tokenizer \
        php8.3-fileinfo

    # Tune OPcache for CLI (Octane keeps PHP process alive)
    local ini_file
    ini_file=$(php8.3 --ini | grep "Loaded Configuration" | awk '{print $NF}')
    local opcache_conf
    opcache_conf="$(dirname "$ini_file")/conf.d/99-bagisto-opcache.ini"

    cat > "$opcache_conf" <<'INI'
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1
INI

    ok "PHP $(php8.3 -r 'echo PHP_VERSION;') installed"
}

# ── Composer ─────────────────────────────────────────────────────────────────
install_composer() {
    header "Composer"

    if [[ -x /usr/local/bin/composer ]]; then
        ok "Composer already installed: $(composer --version --no-ansi 2>&1 | head -1)"
        return
    fi

    step "Downloading Composer"
    local tmp
    tmp=$(mktemp)
    curl -sS https://getcomposer.org/installer -o "$tmp"
    php8.3 "$tmp" --install-dir=/usr/local/bin --filename=composer --quiet
    rm -f "$tmp"

    ok "Composer $(composer --version --no-ansi 2>&1 | head -1)"
}

# ── Node.js ───────────────────────────────────────────────────────────────────
install_nodejs() {
    header "Node.js ${NODE_MAJOR} LTS"

    if command -v node &>/dev/null; then
        local installed_major
        installed_major=$(node -e "process.stdout.write(String(process.version.split('.')[0].replace('v','')))")
        if [[ "$installed_major" -ge "$NODE_MAJOR" ]]; then
            ok "Node.js $(node --version) already installed"
            return
        fi
    fi

    step "Adding NodeSource repository (Node.js ${NODE_MAJOR})"
    curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
    apt-get install -y -qq nodejs

    ok "Node.js $(node --version) / npm $(npm --version)"
}

# ── RoadRunner ────────────────────────────────────────────────────────────────
install_roadrunner() {
    header "RoadRunner ${RR_VERSION}"

    if [[ -x /usr/local/bin/rr ]]; then
        local current_ver
        current_ver=$(/usr/local/bin/rr --version 2>&1 | grep -oP '\d+\.\d+\.\d+' | head -1 || echo "unknown")
        if [[ "$current_ver" == "$RR_VERSION" ]]; then
            ok "RoadRunner $current_ver already installed"
            return
        fi
        warn "Replacing RoadRunner $current_ver → $RR_VERSION"
    fi

    step "Downloading RoadRunner ${RR_VERSION} (linux-amd64)"
    local tmpdir
    tmpdir=$(mktemp -d)
    local archive="roadrunner-${RR_VERSION}-linux-amd64.tar.gz"
    local url="https://github.com/roadrunner-server/roadrunner/releases/download/v${RR_VERSION}/${archive}"

    wget -q --show-progress -O "${tmpdir}/${archive}" "$url" \
        || die "Failed to download RoadRunner from $url"

    tar -xzf "${tmpdir}/${archive}" -C "$tmpdir"
    install -m 755 "${tmpdir}/rr" /usr/local/bin/rr
    rm -rf "$tmpdir"

    ok "RoadRunner $(/usr/local/bin/rr --version 2>&1 | grep -oP '\d+\.\d+\.\d+' | head -1)"
}

# ── Elasticsearch 7.17 ───────────────────────────────────────────────────────
install_elasticsearch() {
    header "Elasticsearch 7.17"

    if systemctl is-active --quiet elasticsearch 2>/dev/null; then
        ok "Elasticsearch already running"
        return
    fi

    if ! dpkg -l elasticsearch &>/dev/null 2>&1; then
        step "Adding Elastic 7.x repository"
        wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch \
            | gpg --dearmor -o /usr/share/keyrings/elasticsearch-keyring.gpg

        cat > /etc/apt/sources.list.d/elastic-7.x.list <<'EOF'
deb [signed-by=/usr/share/keyrings/elasticsearch-keyring.gpg] https://artifacts.elastic.co/packages/7.x/apt stable main
EOF
        apt-get update -qq

        step "Installing Elasticsearch 7.17"
        apt-get install -y -qq elasticsearch
    fi

    # System tuning required by ES
    step "Tuning vm.max_map_count"
    sysctl -w vm.max_map_count=262144
    echo 'vm.max_map_count=262144' > /etc/sysctl.d/99-elasticsearch.conf

    # Memory lock for ES process
    mkdir -p /etc/systemd/system/elasticsearch.service.d
    cat > /etc/systemd/system/elasticsearch.service.d/override.conf <<'EOF'
[Service]
LimitMEMLOCK=infinity
LimitNOFILE=65535
EOF

    # Elasticsearch config
    cp "${CONFIGS_DIR}/elasticsearch.yml" /etc/elasticsearch/elasticsearch.yml

    # JVM heap
    local jvm_opts_file="/etc/elasticsearch/jvm.options.d/bagisto-heap.options"
    cat > "$jvm_opts_file" <<EOF
-Xms${ES_JAVA_HEAP}
-Xmx${ES_JAVA_HEAP}
EOF

    systemctl daemon-reload
    systemctl enable elasticsearch
    systemctl start elasticsearch

    step "Waiting for Elasticsearch to become healthy"
    local attempts=0
    until curl -sf http://127.0.0.1:9200/_cluster/health &>/dev/null; do
        sleep 2
        attempts=$((attempts + 1))
        [[ $attempts -lt 30 ]] || { warn "Elasticsearch health check timed out — check manually"; break; }
    done

    ok "Elasticsearch ready at http://127.0.0.1:9200"
}

# ── MySQL 8.0 ─────────────────────────────────────────────────────────────────
setup_mysql() {
    header "MySQL 8.0"

    # Ensure MySQL is running
    systemctl enable mysql
    systemctl start mysql

    step "Creating database and user"

    # Use root socket auth (default on Ubuntu fresh install)
    mysql --defaults-file=<(echo -e "[client]\nuser=root\npassword=${DB_ROOT_PASSWORD:-}") \
          --connect-timeout=5 2>/dev/null <<SQL || \
    mysql -u root -e "SELECT 1" &>/dev/null && \
    mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost'
    IDENTIFIED WITH mysql_native_password BY '${DB_PASSWORD}';

GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'localhost';

ALTER USER 'root'@'localhost'
    IDENTIFIED WITH mysql_native_password BY '${DB_ROOT_PASSWORD}';

FLUSH PRIVILEGES;
SQL

    # Verify connection
    mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" -e "USE ${DB_DATABASE}; SELECT 1;" &>/dev/null \
        || die "Cannot connect to MySQL as ${DB_USERNAME} — check DB_PASSWORD in .env.deploy"

    ok "MySQL: database '${DB_DATABASE}', user '${DB_USERNAME}'"
}

# ── Redis ─────────────────────────────────────────────────────────────────────
setup_redis() {
    header "Redis"

    step "Writing Redis config"
    cp "${CONFIGS_DIR}/redis.conf" /etc/redis/redis.conf

    # Inject password if set
    if [[ -n "${REDIS_PASSWORD:-}" ]]; then
        sed -i "s|# requirepass REDIS_PASSWORD_PLACEHOLDER|requirepass ${REDIS_PASSWORD}|" \
            /etc/redis/redis.conf
    fi

    # Allow Redis to use more memory (disable THP)
    echo never > /sys/kernel/mm/transparent_hugepage/enabled || true
    {
        echo '#!/bin/sh'
        echo 'echo never > /sys/kernel/mm/transparent_hugepage/enabled'
    } > /etc/rc.local
    chmod +x /etc/rc.local

    systemctl enable redis-server
    systemctl restart redis-server

    # Quick connectivity test
    local redis_cmd="redis-cli"
    [[ -n "${REDIS_PASSWORD:-}" ]] && redis_cmd="redis-cli -a '${REDIS_PASSWORD}'"
    eval "$redis_cmd ping" | grep -q "PONG" \
        || die "Redis did not respond to PING — check /var/log/redis/redis-server.log"

    ok "Redis ready on 127.0.0.1:6379"
}

# ── Application ───────────────────────────────────────────────────────────────
deploy_app() {
    header "Application"

    # ── Clone or update repo ──
    if [[ -d "${APP_DIR}/.git" ]]; then
        warn "Repository already exists at ${APP_DIR} — pulling latest ${APP_BRANCH}"
        sudo -u "${APP_USER}" git -C "${APP_DIR}" fetch origin
        sudo -u "${APP_USER}" git -C "${APP_DIR}" checkout "${APP_BRANCH}"
        sudo -u "${APP_USER}" git -C "${APP_DIR}" pull origin "${APP_BRANCH}"
    elif [[ -d "${APP_DIR}" && -f "${APP_DIR}/artisan" ]]; then
        warn "Application files found at ${APP_DIR} (no .git — assuming pre-copied)"
    else
        step "Cloning repository"
        mkdir -p "$(dirname "${APP_DIR}")"
        git clone --branch "${APP_BRANCH}" --depth 1 "${APP_REPO}" "${APP_DIR}"
        chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}"
    fi

    # ── .env ──
    if [[ ! -f "${APP_DIR}/.env" ]]; then
        step "Generating .env"
        cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
    fi

    # Write all env values from .env.deploy into .env
    # (overwrite matching keys; add new keys if missing)
    _write_app_env

    # ── Directories & permissions ──
    step "Setting permissions"
    mkdir -p \
        "${APP_DIR}/storage/logs" \
        "${APP_DIR}/storage/framework/cache" \
        "${APP_DIR}/storage/framework/sessions" \
        "${APP_DIR}/storage/framework/views" \
        "${APP_DIR}/storage/app/public" \
        "${APP_DIR}/bootstrap/cache" \
        /var/log/bagisto

    chown -R "${APP_USER}:${APP_USER}" \
        "${APP_DIR}/storage" \
        "${APP_DIR}/bootstrap/cache" \
        /var/log/bagisto

    chmod -R 775 \
        "${APP_DIR}/storage" \
        "${APP_DIR}/bootstrap/cache"

    chown -R "${APP_USER}:${APP_USER}" /var/log/bagisto

    # ── Composer install ──
    step "composer install"
    sudo -u "${APP_USER}" composer install \
        --working-dir="${APP_DIR}" \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --quiet

    # ── Frontend build ──
    step "npm ci + npm run build"
    sudo -u "${APP_USER}" npm ci --prefix "${APP_DIR}" --silent
    sudo -u "${APP_USER}" npm run build --prefix "${APP_DIR}"

    # ── APP_KEY ──
    local current_key
    current_key=$(grep '^APP_KEY=' "${APP_DIR}/.env" | cut -d= -f2)
    if [[ -z "$current_key" || "$current_key" == "base64:" ]]; then
        step "Generating APP_KEY"
        sudo -u "${APP_USER}" php8.3 "${APP_DIR}/artisan" key:generate --force --no-interaction
    fi

    # ── Storage symlink ──
    sudo -u "${APP_USER}" php8.3 "${APP_DIR}/artisan" storage:link --no-interaction 2>/dev/null || true

    # ── Migrations ──
    if [[ "${RUN_MIGRATIONS:-false}" == "true" ]]; then
        step "Running migrations (RUN_MIGRATIONS=true)"
        sudo -u "${APP_USER}" php8.3 "${APP_DIR}/artisan" migrate --force --no-interaction
    else
        warn "Skipping migrations (set RUN_MIGRATIONS=true in .env.deploy to run them)"
    fi

    # ── Artisan caches ──
    step "Caching config / routes / views / events"
    local artisan="sudo -u ${APP_USER} php8.3 ${APP_DIR}/artisan"
    $artisan config:cache  --no-interaction
    $artisan route:cache   --no-interaction
    $artisan view:cache    --no-interaction
    $artisan event:cache   --no-interaction

    ok "Application deployed at ${APP_DIR}"
}

# Write key=value pairs from .env.deploy into app's .env
_write_app_env() {
    local app_env="${APP_DIR}/.env"

    _set_env_var() {
        local key="$1"
        local val="$2"
        if grep -q "^${key}=" "$app_env" 2>/dev/null; then
            sed -i "s|^${key}=.*|${key}=${val}|" "$app_env"
        else
            echo "${key}=${val}" >> "$app_env"
        fi
    }

    _set_env_var APP_ENV          "${APP_ENV:-production}"
    _set_env_var APP_DEBUG        "${APP_DEBUG:-false}"
    _set_env_var APP_URL          "${APP_URL:-https://${DOMAIN}}"
    _set_env_var APP_ADMIN_URL    "${APP_ADMIN_URL:-admin}"
    _set_env_var APP_TIMEZONE     "${APP_TIMEZONE:-UTC}"
    _set_env_var APP_LOCALE       "${APP_LOCALE:-ru}"
    _set_env_var APP_CURRENCY     "${APP_CURRENCY:-RUB}"

    _set_env_var DB_CONNECTION    "${DB_CONNECTION:-mysql}"
    _set_env_var DB_HOST          "127.0.0.1"
    _set_env_var DB_PORT          "${DB_PORT:-3306}"
    _set_env_var DB_DATABASE      "${DB_DATABASE}"
    _set_env_var DB_USERNAME      "${DB_USERNAME}"
    _set_env_var DB_PASSWORD      "${DB_PASSWORD}"

    _set_env_var REDIS_HOST       "127.0.0.1"
    _set_env_var REDIS_PORT       "${REDIS_PORT:-6379}"
    _set_env_var REDIS_PASSWORD   "${REDIS_PASSWORD:-}"

    _set_env_var CACHE_STORE             "${CACHE_STORE:-redis}"
    _set_env_var SESSION_DRIVER          "${SESSION_DRIVER:-redis}"
    _set_env_var QUEUE_CONNECTION        "${QUEUE_CONNECTION:-redis}"
    _set_env_var BROADCAST_CONNECTION    "${BROADCAST_CONNECTION:-reverb}"

    _set_env_var REVERB_APP_ID     "${REVERB_APP_ID:-}"
    _set_env_var REVERB_APP_KEY    "${REVERB_APP_KEY:-}"
    _set_env_var REVERB_APP_SECRET "${REVERB_APP_SECRET:-}"
    _set_env_var REVERB_HOST       "0.0.0.0"
    _set_env_var REVERB_PORT       "${REVERB_PORT:-8080}"
    _set_env_var REVERB_SCHEME     "${REVERB_SCHEME:-https}"

    _set_env_var RESPONSE_CACHE_ENABLED "${RESPONSE_CACHE_ENABLED:-true}"
    _set_env_var RESPONSE_CACHE_DRIVER  "${RESPONSE_CACHE_DRIVER:-redis}"

    _set_env_var LOG_CHANNEL  "${LOG_CHANNEL:-stack}"
    _set_env_var LOG_LEVEL    "${LOG_LEVEL:-warning}"

    # Optional integrations (only if non-empty)
    [[ -n "${YANDEX_MAPS_API_KEY:-}"   ]] && _set_env_var YANDEX_MAPS_API_KEY   "$YANDEX_MAPS_API_KEY"
    [[ -n "${GIGACHAT_CLIENT_ID:-}"     ]] && _set_env_var GIGACHAT_CLIENT_ID     "$GIGACHAT_CLIENT_ID"
    [[ -n "${GIGACHAT_CLIENT_SECRET:-}" ]] && _set_env_var GIGACHAT_CLIENT_SECRET "$GIGACHAT_CLIENT_SECRET"
    [[ -n "${OPENAI_API_KEY:-}"        ]] && _set_env_var OPENAI_API_KEY        "$OPENAI_API_KEY"
    [[ -n "${FCM_SERVER_KEY:-}"        ]] && _set_env_var FCM_SERVER_KEY        "$FCM_SERVER_KEY"

    chown "${APP_USER}:${APP_USER}" "$app_env"
    chmod 640 "$app_env"
}

# ── Nginx ─────────────────────────────────────────────────────────────────────
setup_nginx() {
    header "Nginx"

    # Main nginx.conf
    cp "${CONFIGS_DIR}/nginx.conf" /etc/nginx/nginx.conf

    # Proxy params snippet
    mkdir -p /etc/nginx/snippets
    cp "${CONFIGS_DIR}/proxy-params.conf" /etc/nginx/snippets/proxy-params.conf

    # ACME challenge webroot
    mkdir -p /var/www/certbot

    # Render site config from template
    step "Generating site config"
    sed \
        -e "s|__DOMAIN__|${DOMAIN}|g" \
        -e "s|__APP_DIR__|${APP_DIR}|g" \
        -e "s|__APP_PORT__|${APP_PORT}|g" \
        -e "s|__REVERB_PORT__|${REVERB_PORT}|g" \
        "${CONFIGS_DIR}/bagisto-site.conf.tpl" \
        > /etc/nginx/sites-available/bagisto

    # For the initial HTTP-only phase (before certbot), replace ssl_certificate
    # paths with a self-signed cert so nginx can start
    if [[ ! -d "/etc/letsencrypt/live/${DOMAIN}" ]]; then
        warn "SSL certificate not yet available — creating self-signed for initial nginx start"
        _create_self_signed_cert
        sed -i \
            -e "s|/etc/letsencrypt/live/${DOMAIN}/fullchain.pem|/etc/ssl/bagisto/self-signed.crt|g" \
            -e "s|/etc/letsencrypt/live/${DOMAIN}/privkey.pem|/etc/ssl/bagisto/self-signed.key|g" \
            -e "s|/etc/letsencrypt/live/${DOMAIN}/chain.pem|/etc/ssl/bagisto/self-signed.crt|g" \
            /etc/nginx/sites-available/bagisto
        # Also disable OCSP stapling (requires real cert)
        sed -i \
            -e 's|ssl_stapling on;|ssl_stapling off;|' \
            -e 's|ssl_stapling_verify on;|ssl_stapling_verify off;|' \
            /etc/nginx/sites-available/bagisto
    fi

    # Enable site
    ln -sf /etc/nginx/sites-available/bagisto /etc/nginx/sites-enabled/bagisto
    rm -f /etc/nginx/sites-enabled/default

    nginx -t || die "Nginx config test failed — check /etc/nginx/sites-available/bagisto"
    systemctl enable nginx
    systemctl restart nginx

    ok "Nginx configured and started"
}

_create_self_signed_cert() {
    mkdir -p /etc/ssl/bagisto
    openssl req -x509 -nodes -days 365 \
        -newkey rsa:2048 \
        -keyout /etc/ssl/bagisto/self-signed.key \
        -out    /etc/ssl/bagisto/self-signed.crt \
        -subj   "/CN=${DOMAIN}/O=Bagisto/C=RU" \
        2>/dev/null
}

# ── Octane systemd service ────────────────────────────────────────────────────
setup_octane() {
    header "Octane (systemd service)"

    step "Writing bagisto-octane.service"
    sed \
        -e "s|__APP_DIR__|${APP_DIR}|g" \
        -e "s|__APP_PORT__|${APP_PORT}|g" \
        -e "s|__OCTANE_WORKERS__|${OCTANE_WORKERS}|g" \
        "${CONFIGS_DIR}/bagisto-octane.service" \
        > /etc/systemd/system/bagisto-octane.service

    systemctl daemon-reload
    systemctl enable bagisto-octane
    systemctl start bagisto-octane

    # Wait for Octane to bind the port
    step "Waiting for Octane on port ${APP_PORT}"
    local attempts=0
    until nc -z 127.0.0.1 "${APP_PORT}" 2>/dev/null; do
        sleep 2
        attempts=$((attempts + 1))
        [[ $attempts -lt 30 ]] || { warn "Octane did not start within 60s — check: journalctl -u bagisto-octane -n 50"; break; }
    done

    ok "Octane running on 127.0.0.1:${APP_PORT}"
}

# ── Supervisor ────────────────────────────────────────────────────────────────
setup_supervisor() {
    header "Supervisor (queue workers + Reverb + scheduler)"

    step "Writing Supervisor configs"
    sed \
        -e "s|__APP_DIR__|${APP_DIR}|g" \
        -e "s|__REVERB_PORT__|${REVERB_PORT}|g" \
        -e "s|__QUEUE_PROCESSES__|${QUEUE_PROCESSES}|g" \
        "${CONFIGS_DIR}/bagisto-workers.conf" \
        > /etc/supervisor/conf.d/bagisto-workers.conf

    systemctl enable supervisor
    systemctl start supervisor

    supervisorctl reread
    supervisorctl update

    ok "Supervisor programs registered"
}

# ── SSL / Certbot ─────────────────────────────────────────────────────────────
setup_ssl() {
    header "SSL (Let's Encrypt)"

    if [[ -d "/etc/letsencrypt/live/${DOMAIN}" ]]; then
        ok "Certificate for ${DOMAIN} already exists — skipping issuance"
    else
        step "Obtaining certificate for ${DOMAIN}"
        certbot --nginx \
            -d "${DOMAIN}" \
            --non-interactive \
            --agree-tos \
            -m "${SSL_EMAIL}" \
            --redirect \
            || die "Certbot failed — ensure DNS for ${DOMAIN} points to this server and port 80 is open"
    fi

    # Replace self-signed cert paths in nginx config with real Let's Encrypt paths
    if grep -q "self-signed" /etc/nginx/sites-available/bagisto 2>/dev/null; then
        step "Updating nginx config with Let's Encrypt certificate paths"
        sed -i \
            -e "s|/etc/ssl/bagisto/self-signed.crt|/etc/letsencrypt/live/${DOMAIN}/fullchain.pem|g" \
            -e "s|/etc/ssl/bagisto/self-signed.key|/etc/letsencrypt/live/${DOMAIN}/privkey.pem|g" \
            -e 's|ssl_stapling off;|ssl_stapling on;|' \
            -e 's|ssl_stapling_verify off;|ssl_stapling_verify on;|' \
            /etc/nginx/sites-available/bagisto

        # Add trusted certificate for OCSP
        grep -q "ssl_trusted_certificate" /etc/nginx/sites-available/bagisto \
            || sed -i "/ssl_stapling_verify on;/a\\    ssl_trusted_certificate /etc/letsencrypt/live/${DOMAIN}/chain.pem;" \
                /etc/nginx/sites-available/bagisto

        nginx -t && systemctl reload nginx
    fi

    # Verify auto-renewal timer
    systemctl is-enabled certbot.timer &>/dev/null \
        && ok "Certbot auto-renewal timer active" \
        || warn "Certbot renewal timer not active — run: systemctl enable --now certbot.timer"

    ok "SSL configured for ${DOMAIN}"
}

# ── Log rotation ──────────────────────────────────────────────────────────────
setup_logrotate() {
    header "Log rotation"

    # Replace APP_DIR placeholder in logrotate config
    sed "s|/var/www/bagisto|${APP_DIR}|g" \
        "${CONFIGS_DIR}/logrotate-bagisto" \
        > /etc/logrotate.d/bagisto

    ok "Log rotation configured"
}

# ── Final health check ────────────────────────────────────────────────────────
final_check() {
    header "Health check"

    local all_ok=true

    _check_service() {
        local name="$1"
        if systemctl is-active --quiet "$name" 2>/dev/null; then
            ok "$name is running"
        else
            error "$name is NOT running"
            all_ok=false
        fi
    }

    _check_service nginx
    _check_service mysql
    _check_service redis-server
    _check_service elasticsearch
    _check_service supervisor
    _check_service bagisto-octane

    # Supervisor programs
    for prog in bagisto-worker bagisto-reverb bagisto-scheduler; do
        if supervisorctl status "$prog" 2>/dev/null | grep -q "RUNNING"; then
            ok "supervisor/$prog is RUNNING"
        else
            warn "supervisor/$prog is not in RUNNING state — check: supervisorctl status $prog"
        fi
    done

    # HTTP health check
    step "Checking application HTTP response"
    local http_status
    http_status=$(curl -skL -o /dev/null -w "%{http_code}" "http://127.0.0.1:${APP_PORT}/health" || echo "000")
    if [[ "$http_status" == "200" ]]; then
        ok "Octane health endpoint → 200 OK"
    else
        warn "Octane health endpoint returned HTTP ${http_status}"
    fi

    echo ""
    if $all_ok; then
        echo -e "${GREEN}${BOLD}All services are running!${NC}"
    else
        echo -e "${YELLOW}${BOLD}Some services need attention (see warnings above).${NC}"
    fi
}

# ── Summary ───────────────────────────────────────────────────────────────────
print_summary() {
    echo ""
    echo -e "${BLUE}${BOLD}╔════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}${BOLD}║           DEPLOYMENT COMPLETE              ║${NC}"
    echo -e "${BLUE}${BOLD}╚════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  ${BOLD}Application${NC}"
    echo -e "    URL:       https://${DOMAIN}"
    echo -e "    Admin:     https://${DOMAIN}/${APP_ADMIN_URL:-admin}"
    echo -e "    Directory: ${APP_DIR}"
    echo ""
    echo -e "  ${BOLD}Services${NC}"
    echo -e "    Octane:         127.0.0.1:${APP_PORT} (systemd: bagisto-octane)"
    echo -e "    Reverb WS:      127.0.0.1:${REVERB_PORT} (supervisor: bagisto-reverb)"
    echo -e "    MySQL:          127.0.0.1:3306  DB: ${DB_DATABASE}"
    echo -e "    Redis:          127.0.0.1:6379"
    echo -e "    Elasticsearch:  127.0.0.1:9200"
    echo ""
    echo -e "  ${BOLD}Useful commands${NC}"
    echo -e "    systemctl status bagisto-octane"
    echo -e "    supervisorctl status"
    echo -e "    journalctl -u bagisto-octane -f"
    echo -e "    tail -f ${APP_DIR}/storage/logs/laravel.log"
    echo ""
    if [[ "${RUN_MIGRATIONS:-false}" != "true" ]]; then
        echo -e "  ${YELLOW}${BOLD}Reminder:${NC} Run migrations when ready:"
        echo -e "    cd ${APP_DIR} && php8.3 artisan migrate --force"
        echo ""
    fi
}

# ── Main ──────────────────────────────────────────────────────────────────────
main() {
    echo -e "${BLUE}${BOLD}"
    echo "  ╔══════════════════════════════════════════╗"
    echo "  ║   Bagisto Bare-Metal Installer v1.0      ║"
    echo "  ║   Ubuntu 22.04/24.04 · PHP 8.3 · Octane  ║"
    echo "  ╚══════════════════════════════════════════╝"
    echo -e "${NC}"

    load_env
    preflight
    install_base_packages
    install_php
    install_composer
    install_nodejs
    install_roadrunner
    install_elasticsearch
    setup_mysql
    setup_redis
    deploy_app
    setup_nginx
    setup_octane
    setup_supervisor
    setup_ssl
    setup_logrotate
    final_check
    print_summary
}

main "$@"
