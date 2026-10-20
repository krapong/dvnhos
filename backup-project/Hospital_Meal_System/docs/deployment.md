# Deployment

## Target stack

- Apache
- PHP 8.2+
- Laravel 11
- Vue 3
- MariaDB 11+

## Recommended steps

1. Build application image
2. Set Apache document root to `/public`
3. Run migrations and seed data
4. Configure `APP_KEY`
5. Configure `DB_*`, `HOSXP_*`, and `TELEGRAM_*`
6. Schedule Laravel scheduler every minute

## Apache notes

- Enable `mod_rewrite`
- Allow `.htaccess`
- Point document root to `public/`

## Production checklist

- HTTPS enabled
- Queue worker configured
- Scheduler configured
- Backup DB configured
- Log rotation configured
- Read-only HOSxP credentials tested
