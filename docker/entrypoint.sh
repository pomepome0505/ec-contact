#!/bin/sh
set -e

echo "=== Container starting ==="

# データベースマイグレーション実行
echo "Running database migrations..."
php artisan migrate --force

# アプリケーション起動
echo "Starting application..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
