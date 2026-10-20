# Deploy: AlmaLinux 9 + PHP 8.2 + Apache

## 1. Install server packages

```bash
sudo dnf install -y httpd php php-cli php-common php-pdo php-mysqlnd php-mbstring php-xml php-tokenizer php-json php-curl php-zip php-sqlite3 unzip
sudo systemctl enable --now httpd
```

## 2. Upload and extract

```bash
sudo mkdir -p /var/www/hospital-meal-system
sudo unzip hospital-meal-system-almalinux9.zip -d /var/www/hospital-meal-system
cd /var/www/hospital-meal-system
```

The release package already includes `vendor/` and `public/build/`, so Node.js is not required on the server.

## 3. Configure environment

If `.env` is not included, copy the example:

```bash
sudo cp deploy/env.almalinux.example .env
```

Then edit:

```bash
sudo nano .env
```

Set:

- `APP_URL` to the real server URL or IP, for this deployment: `http://YOUR_PRIVATE_SERVER/hms/public`.
- `DB_PASSWORD` to the HMS application database password.
- `HOSXP_PASSWORD` to the HIS password.
- Telegram values if notifications are enabled.

## 4. Run setup

```bash
sudo bash deploy/almalinux9-setup.sh /var/www/hospital-meal-system
```

Default demo users are created by the setup command:

- `admin@hospital.local`
- `nurse@hospital.local`
- `nutrition@hospital.local`
- `kitchen@hospital.local`
- `manager@hospital.local`

Default password: `password`

Change these passwords after first login.

## 5. Configure Apache

```bash
sudo cp deploy/httpd-hospital-meal.conf /etc/httpd/conf.d/hospital-meal.conf
sudo nano /etc/httpd/conf.d/hospital-meal.conf
sudo apachectl configtest
sudo systemctl reload httpd
```

Set `ServerName` and `DocumentRoot` for your server. The important point is that Apache must point to:

```text
/var/www/hospital-meal-system/public
```

## 6. Scheduler

Laravel scheduler is required for HIS sync and Telegram scheduled notifications.

```bash
sudo crontab -u apache -e
```

Add:

```cron
* * * * * cd /var/www/hospital-meal-system && php artisan schedule:run >> /dev/null 2>&1
```

## 7. Verify

```bash
cd /var/www/hospital-meal-system
php artisan about
php artisan migrate --force
php artisan hospital-meal:sync-hosxp
```

Open the app in the browser and press:

- `ซิงก์ข้อมูล HIS`
- `วันนี้`
- `อาทิตย์นี้`
