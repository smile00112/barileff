#!/bin/sh
set -e

if [ -z "$MYSQL_USER" ]; then
    exit 0
fi

mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS testing;

    GRANT ALL PRIVILEGES ON testing.* TO '${MYSQL_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL
