#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/var/www/hospital-meal-system}"
WEB_USER="${WEB_USER:-apache}"

if [ ! -f "${APP_DIR}/artisan" ]; then
  echo "artisan not found in ${APP_DIR}"
  echo "Usage: sudo bash deploy/almalinux9-setup.sh /var/www/hospital-meal-system"
  exit 1
fi

cd "${APP_DIR}"

if [ ! -f .env ]; then
  if [ -f deploy/env.almalinux.example ]; then
    cp deploy/env.almalinux.example .env
    echo "Created .env from deploy/env.almalinux.example. Review it before opening the app."
  else
    echo ".env not found. Create .env before running this script."
    exit 1
  fi
fi

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database

if grep -q '^DB_CONNECTION=sqlite' .env && [ ! -f database/database.sqlite ]; then
  touch database/database.sqlite
fi

chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache database
chmod -R ug+rwX storage bootstrap/cache database

php artisan key:generate --force --no-interaction
php artisan migrate --force --no-interaction
php artisan hospital-meal:ensure-auth-users --no-interaction
php artisan storage:link --force --no-interaction || true
php artisan optimize:clear --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

echo "Hospital Meal System setup completed."
echo "Default demo password is: password"
echo "Please change demo passwords after first login."
