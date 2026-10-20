# Deployment Guide

## Server requirements
- PHP 8.4.1 or newer, 64-bit
- Apache 2.4 with `mod_rewrite`
- MySQL database `drugstock_alert`

## Create the database
Before running migrations, create the database with `utf8mb4` encoding:
```sql
CREATE DATABASE IF NOT EXISTS drugstock_alert
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

## Manual import option
If Artisan is not available on the server, import this file instead:
- `database/sql/drugstock_alert_fresh.sql`

That file creates the required tables and seeds:
- users
- units
- drug categories
- locations
- warehouses
- drugs
- warehouse stocks and lots
- permissions and roles
- alert rules and sample notifications

## Recommended folder layout
Upload the project to:
`/var/www/html/drugstock_alert`

## Apache vhost
Use `deploy_apache_vhost.conf` as a guide.
The important points are:
- Expose the app with `Alias /drugstock_alert /var/www/html/drugstock_alert/public`
- `<Directory ...>` must have `AllowOverride All`
- `mod_rewrite` must be enabled

## Environment
Update `.env` on the server:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=http://YOUR_PRIVATE_SERVER/drugstock_alert`
- `DB_CONNECTION=mysql`
- `DB_HOST=YOUR_PRIVATE_SERVER`
- `DB_PORT=3306`
- `DB_DATABASE=drugstock_alert`
- `DB_USERNAME=sa`
- `DB_PASSWORD=Maechaem@11120`

## Post-deploy steps
1. Run `composer install --no-dev --optimize-autoloader`
2. Run `php artisan key:generate` only if `.env` has no key yet
3. Run `php artisan migrate --force`
4. Run `php artisan db:seed --force`
   - If this is a completely fresh empty database, you can use `php artisan migrate:fresh --seed --force` instead.
5. Run `php artisan config:cache`
6. Run `php artisan route:cache`
7. Run `php artisan view:cache`
8. Ensure `storage` and `bootstrap/cache` are writable

## Quick checks
- Open `http://YOUR_PRIVATE_SERVER/drugstock_alert`
- If blank page appears, check Apache error log and PHP version first
- If `Forbidden` appears, verify `Alias`, `DocumentRoot`, and `AllowOverride`
