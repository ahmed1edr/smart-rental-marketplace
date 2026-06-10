#!/bin/sh
set -e

cd /var/www/backend

echo "Waiting for MySQL..."
until php artisan migrate --force 2>/dev/null; do
    echo "Database not ready, retrying in 3s..."
    sleep 3
done

echo "Seeding database..."
php artisan db:seed --force

if ! grep -qE '^JWT_SECRET=.+$' .env 2>/dev/null; then
    echo "Generating JWT secret..."
    php artisan jwt:secret --force
fi

php artisan storage:link --force 2>/dev/null || true

echo "Backend ready."
exec php-fpm
