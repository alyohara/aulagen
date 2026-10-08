#!/bin/sh
set -e

cd /var/www/html

# The host bind mount can carry bootstrap/cache manifests generated with a
# different dependency set (e.g. dev packages). Regenerate them inside the
# container so they match the installed vendor/.
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php
php artisan package:discover --ansi >/dev/null 2>&1 || true

# Wait for the database so migrations do not race the DB container.
if [ "$DB_CONNECTION" = "pgsql" ] && [ -n "$DB_HOST" ]; then
  echo "Waiting for database ${DB_HOST}:${DB_PORT:-5432} ..."
  php -r '
    $host = getenv("DB_HOST"); $port = getenv("DB_PORT") ?: "5432";
    $db = getenv("DB_DATABASE"); $user = getenv("DB_USERNAME"); $pass = getenv("DB_PASSWORD");
    for ($i = 0; $i < 60; $i++) {
        try {
            new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [PDO::ATTR_TIMEOUT => 2]);
            echo "database ready\n"; exit(0);
        } catch (Throwable $e) { sleep(1); }
    }
    echo "database not available, continuing anyway\n";
  '
fi

if [ "$RUN_MIGRATIONS" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

exec "$@"
