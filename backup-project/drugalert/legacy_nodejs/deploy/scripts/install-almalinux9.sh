#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/opt/drugalret"
SERVICE_NAME="drugalret"
ENV_DIR="/etc/sysconfig"
ENV_FILE="${ENV_DIR}/${SERVICE_NAME}"
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
APACHE_FILE="/etc/httpd/conf.d/${SERVICE_NAME}.conf"

sudo dnf install -y nodejs npm httpd policycoreutils-python-utils
sudo systemctl enable --now httpd

sudo mkdir -p "${APP_DIR}"
sudo rsync -a --delete ./ "${APP_DIR}/"
cd "${APP_DIR}"
npm install

sudo mkdir -p "${ENV_DIR}"
if [ ! -f "${ENV_FILE}" ]; then
  sudo cp deploy/env/drugalret.env.example "${ENV_FILE}"
  echo "Edit ${ENV_FILE} and set the real MySQL/password values."
fi

sudo cp deploy/systemd/drugalret.service "${SERVICE_FILE}"
sudo cp deploy/apache/drugalret.conf "${APACHE_FILE}"

sudo systemctl daemon-reload
sudo systemctl enable --now "${SERVICE_NAME}"

sudo systemctl restart httpd
sudo setsebool -P httpd_can_network_connect 1

sudo firewall-cmd --permanent --add-service=http || true
sudo firewall-cmd --permanent --add-service=https || true
sudo firewall-cmd --reload || true

echo "Done. Check:"
echo "  sudo systemctl status ${SERVICE_NAME}"
echo "  sudo systemctl status httpd"
