#!/bin/sh

set -e

echo "Waiting for MySQL to be ready..."
# Wait for MySQL to be ready (simple check)
until php -r "try { new PDO('mysql:host=mysql;port=3306', '${DB_USERNAME:-app_user}', '${DB_PASSWORD:-app_password}'); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
    echo "MySQL is unavailable - sleeping"
    sleep 2
done

echo "MySQL is up - executing commands"

# Install dependencies if vendor doesn't exist
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader || true
fi

# Generate application key if not set (check if key exists in .env)
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "Generating application key..."
    php artisan key:generate --force || true
fi

# Set permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

echo "Application is ready!"

exec "$@"

