#!/bin/bash
set -e

# .env がなければ .env.example からコピー
if [ ! -f .env ]; then
    cp .env.example .env
    echo "[entrypoint] .env created from .env.example"
fi

# Composer install（vendor が空の場合）
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction
    echo "[entrypoint] composer install completed"
fi

# APP_KEY が未設定の場合は生成
if grep -q "^APP_KEY=$" .env; then
    php artisan key:generate --no-interaction
    echo "[entrypoint] APP_KEY generated"
fi

# npm install（node_modules が空の場合）
if [ ! -d node_modules/.package-lock.json ] && [ ! -d node_modules/.vite ]; then
    npm install
    echo "[entrypoint] npm install completed"
fi

# storage のパーミッション設定
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# マイグレーション実行（テーブルが未作成の場合のみ）
php artisan migrate --no-interaction --force
echo "[entrypoint] migrations checked"

# Vite dev サーバーをバックグラウンドで起動
npm run dev &
echo "[entrypoint] Vite dev server started"

# Supervisor（Nginx + PHP-FPM）を起動
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
