#!/usr/bin/env bash
# HBC_DB_ONLY_V1
set -Eeuo pipefail
umask 077

# Installed locally on each target server. Called by the central dashboard over SSH.
# Additional servers use a local administrator-managed database list.
IPS=" $(hostname -I) "
if [[ "$IPS" == *" 10.0.2.16 "* ]]; then
  SERVER=10.0.2.16
  DBS=(hospital_db hms drugalert drugstock_alert lr_supplement medical_request_db pd_dashboard_db bed-monitor)
elif [[ "$IPS" == *" 10.0.2.14 "* ]]; then
  SERVER=10.0.2.14
  DBS=(lr_supplement ncdappo nursing_center)
elif [[ -s /etc/hospital-backup/databases.list ]]; then
  SERVER=$(hostname)
  mapfile -t DBS < /etc/hospital-backup/databases.list
  for db in "${DBS[@]}"; do
    [[ "$db" =~ ^[a-zA-Z0-9_-]+$ ]] || { echo 'ERROR: Invalid database name'; exit 2; }
  done
else
  echo 'ERROR: Configure /etc/hospital-backup/databases.list on this server'; exit 2
fi

DEST_ROOT=/var/backup/hospital-center
if [[ "${1:-}" == '--prune-only' ]]; then
  mkdir -p "$DEST_ROOT"
  exec 9>"$DEST_ROOT/.backup.lock"
  flock -n 9 || exit 0
  exec python3 /usr/local/sbin/hospital-backup-prune.py "$DEST_ROOT"
fi
DATE=$(date +%Y%m%d_%H%M%S)
DEST="$DEST_ROOT/$DATE"
mkdir -p "$DEST_ROOT"
exec 9>"$DEST_ROOT/.backup.lock"
if ! flock -n 9; then echo 'ERROR: Another backup is running'; exit 3; fi
mkdir "$DEST"
SQL="$DEST/databases.sql.gz"

on_error() {
  echo "ERROR: Backup failed at line $1" >&2
  touch "$DEST/FAILED"
}
trap 'on_error "$LINENO"' ERR

[[ -f /root/.hospital-backup.cnf ]] || { echo 'ERROR: Missing /root/.hospital-backup.cnf' >&2; exit 5; }
[[ "$(stat -c %a /root/.hospital-backup.cnf)" == '600' ]] || { echo 'ERROR: DB credentials must be chmod 600' >&2; exit 6; }

echo 'STEP 1/2: Dumping MariaDB databases'
mariadb-dump --defaults-extra-file=/root/.hospital-backup.cnf \
  --databases "${DBS[@]}" --single-transaction --quick --routines --triggers --events \
  | gzip -1 > "$SQL"

echo 'STEP 2/2: Validating database archive'
gzip -t "$SQL"
printf '%s\n' "${DBS[@]}" > "$DEST/databases.txt"
sha256sum "$SQL" > "$DEST/SHA256SUMS"
cat > "$DEST/metadata.txt" <<META
server=$SERVER
created_at=$(date -Is)
databases=${#DBS[@]}
META
printf '%s\n' 'HBC_DB_ONLY_V1' > "$DEST/.hbc-complete"
python3 /usr/local/sbin/hospital-backup-prune.py "$DEST_ROOT" || echo 'WARNING: Retention cleanup failed' >&2
printf '%s\n' "SUCCESS|$DEST|$(stat -c %s "$SQL")"
