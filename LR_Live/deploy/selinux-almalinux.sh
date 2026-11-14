#!/usr/bin/env bash
# Inspect paths first. Existing rules use semanage fcontext -m rather than -a.
set -euo pipefail
[[ $(id -u) -eq 0 ]] || { echo 'Run as root'; exit 1; }
[[ -d /var/www/html/LR_HOSxP_Live/.private ]] || { echo 'Install the release first'; exit 1; }
semanage fcontext -a -t httpd_sys_content_t '/var/www/html/LR_HOSxP_Live(/.*)?'
semanage fcontext -a -t httpd_sys_rw_content_t '/var/www/html/LR_HOSxP_Live/\.private(/.*)?'
restorecon -Rv /var/www/html/LR_HOSxP_Live
setsebool -P httpd_can_network_connect_db on
# Reference: Red Hat SELinux guide, Apache types and booleans.
# https://docs.redhat.com/en/documentation/red_hat_enterprise_linux/7/html/selinux_users_and_administrators_guide/sect-managing_confined_services-the_apache_http_server-types
