# Nginx site config template for Bagisto (bare-metal, Octane + RoadRunner)
# Placeholders replaced by install.sh:
#   __DOMAIN__        → e.g. example.com
#   __APP_DIR__       → e.g. /var/www/bagisto
#   __APP_PORT__      → e.g. 8000
#   __REVERB_PORT__   → e.g. 8080

# HTTP → HTTPS redirect + Let's Encrypt ACME challenge
server {
    listen 80;
    listen [::]:80;
    server_name __DOMAIN__;

    # Let's Encrypt ACME challenge (certbot webroot)
    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
        default_type "text/plain";
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

# HTTPS — main application
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name __DOMAIN__;

    root __APP_DIR__/public;

    # SSL — Let's Encrypt (filled in by certbot / install.sh)
    ssl_certificate     /etc/letsencrypt/live/__DOMAIN__/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/__DOMAIN__/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384';
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:50m;
    ssl_session_tickets off;

    ssl_stapling on;
    ssl_stapling_verify on;
    ssl_trusted_certificate /etc/letsencrypt/live/__DOMAIN__/chain.pem;
    resolver 8.8.8.8 8.8.4.4 valid=300s;
    resolver_timeout 5s;

    # Logging
    access_log /var/log/nginx/bagisto-access.log main;
    error_log  /var/log/nginx/bagisto-error.log warn;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Health check (nginx-level, no backend required)
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    # Deny hidden files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Static assets — serve directly, 1-year cache
    location ~* \.(jpg|jpeg|gif|png|webp|mp4|css|js|ico|xml|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }

    # Storage — serve files from storage/app/public (symlinked to public/storage)
    location ^~ /storage/ {
        alias __APP_DIR__/storage/app/public/;
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Intervention image cache — dynamic generation, must reach Octane
    location ^~ /cache/ {
        proxy_pass http://127.0.0.1:__APP_PORT__;
        include /etc/nginx/snippets/proxy-params.conf;
        proxy_connect_timeout 60s;
        proxy_send_timeout    60s;
        proxy_read_timeout    60s;
    }

    # API docs (Scribe / Swagger)
    location ^~ /api-docs/ {
        proxy_pass http://127.0.0.1:__APP_PORT__;
        include /etc/nginx/snippets/proxy-params.conf;
        proxy_connect_timeout 60s;
        proxy_send_timeout    60s;
        proxy_read_timeout    60s;
    }

    # WebSocket — Laravel Reverb
    location /app/ {
        proxy_pass http://127.0.0.1:__REVERB_PORT__;
        proxy_http_version 1.1;
        proxy_set_header Upgrade    $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host               $host;
        proxy_set_header X-Real-IP          $remote_addr;
        proxy_set_header X-Forwarded-For    $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto  $scheme;
        proxy_set_header X-Forwarded-Host   $host;
        proxy_set_header X-Forwarded-Port   $server_port;
        proxy_connect_timeout 7d;
        proxy_send_timeout    7d;
        proxy_read_timeout    7d;
        proxy_buffering off;
        proxy_request_buffering off;
    }

    # Prevent PHP files from being served as static
    location ~ \.php$ {
        proxy_pass http://127.0.0.1:__APP_PORT__;
        include /etc/nginx/snippets/proxy-params.conf;
        proxy_connect_timeout 60s;
        proxy_send_timeout    60s;
        proxy_read_timeout    60s;
        proxy_buffering off;
    }

    # Direct proxy for API / admin / installer prefixes
    location ~ ^/(api|admin|installer) {
        proxy_pass http://127.0.0.1:__APP_PORT__;
        include /etc/nginx/snippets/proxy-params.conf;
        proxy_connect_timeout 60s;
        proxy_send_timeout    60s;
        proxy_read_timeout    60s;
        proxy_buffering off;
    }

    # Everything else → try static file first, then Octane
    location / {
        try_files $uri @octane;
    }

    location @octane {
        proxy_pass http://127.0.0.1:__APP_PORT__;
        include /etc/nginx/snippets/proxy-params.conf;
        proxy_connect_timeout 60s;
        proxy_send_timeout    60s;
        proxy_read_timeout    60s;
        proxy_buffering off;
        proxy_request_buffering off;
    }
}
