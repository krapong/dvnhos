#!/usr/bin/env bash
# AlmaLinux 9 / Apache HTTPS bootstrap for this specific installation.
set -euo pipefail
[[ $(id -u) -eq 0 ]] || { echo 'Run with sudo bash enable-https.sh'; exit 1; }
APP=/var/www/html/LR_HOSxP_Live
[[ -f "$APP/login.php" && -f "$APP/config.php" ]] || { echo 'login.php or config.php is missing in the application directory'; exit 1; }
command -v httpd >/dev/null
command -v openssl >/dev/null
rpm -q mod_ssl >/dev/null 2>&1 || dnf install -y mod_ssl
CERT=/etc/pki/tls/certs/lr-emr-192.0.2.20.crt
KEY=/etc/pki/tls/private/lr-emr-192.0.2.20.key
if [[ ! -f "$CERT" && ! -f "$KEY" ]]; then
 umask 077
 openssl req -x509 -newkey rsa:3072 -sha256 -nodes -days 365 -keyout "$KEY" -out "$CERT" -subj '/CN=192.0.2.20' -addext 'subjectAltName=IP:192.0.2.20' >/dev/null 2>&1
 chmod 600 "$KEY"
 chmod 644 "$CERT"
fi
[[ -f "$CERT" && -f "$KEY" ]] || { echo 'Certificate/key pair incomplete; no existing key overwritten'; exit 1; }
CONF=/etc/httpd/conf.d/00-lr-emr-https.conf
[[ ! -f "$CONF" ]] || cp -a "$CONF" "$CONF.backup-$(date +%Y%m%d-%H%M%S)"
cat > "$CONF" <<'APACHE'
<VirtualHost *:443>
 ServerName 192.0.2.20
 DocumentRoot /var/www/html/LR_HOSxP_Live
 Alias /LR_HOSxP_Live /var/www/html/LR_HOSxP_Live
 SSLEngine on
 SSLCertificateFile /etc/pki/tls/certs/lr-emr-192.0.2.20.crt
 SSLCertificateKeyFile /etc/pki/tls/private/lr-emr-192.0.2.20.key
 ErrorLog /var/log/httpd/lr-emr-error.log
 CustomLog /var/log/httpd/lr-emr-access.log combined
 <Directory /var/www/html/LR_HOSxP_Live>
  Options -Indexes
  AllowOverride All
  Require all granted
  DirectoryIndex index.php
 </Directory>
 <Directory /var/www/html/LR_HOSxP_Live/.private>
  Require all denied
 </Directory>
</VirtualHost>
APACHE
restorecon -RF "$APP" "$CERT" "$KEY" "$CONF"
setsebool -P httpd_can_network_connect_db on
install -d -o apache -g apache -m 0700 "$APP/.private"
if [[ -f "$APP/.private/login-attempts.php" ]]; then
 chown apache:apache "$APP/.private/login-attempts.php"
 chmod 600 "$APP/.private/login-attempts.php"
fi
# .private must be writable by the PHP-FPM pool; source code ownership is unchanged.
if command -v semanage >/dev/null; then
 semanage fcontext -a -t httpd_sys_rw_content_t "$APP/\.private(/.*)?" 2>/dev/null || semanage fcontext -m -t httpd_sys_rw_content_t "$APP/\.private(/.*)?"
 restorecon -RF "$APP/.private"
else
 echo 'Install policycoreutils-python-utils and label .private httpd_sys_rw_content_t for writable login throttle storage.'
fi
apachectl configtest
if systemctl is-active --quiet firewalld; then
 firewall-cmd --permanent --add-service=https
 firewall-cmd --reload
fi
systemctl reload httpd
printf '\nHTTPS configured: https://192.0.2.20/LR_HOSxP_Live/login.php\n'
printf 'The generated certificate is self-signed. Have your administrator trust it on clients or replace it with a trusted hospital certificate.\n'
printf 'Certificate SHA256 fingerprint:\n'
openssl x509 -in "$CERT" -noout -fingerprint -sha256
