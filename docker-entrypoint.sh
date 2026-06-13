#!/bin/sh
set -e

DB_FILE="${DB_PATH:-/var/www/html/data/restaurant.db}"

# Auto-initialise the database on first start
if [ ! -f "$DB_FILE" ]; then
    echo "[entrypoint] Database not found at $DB_FILE — running schema init..."
    php /var/www/html/schema.php
    echo "[entrypoint] Schema created. Run setup.php manually to seed restaurants and users."
fi

exec apache2-foreground
