-- DBA only: replace WEB_SERVER_IP and password; import supplement.sql before GRANT.
CREATE DATABASE IF NOT EXISTS lr_supplement CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lr_supplement_app'@'WEB_SERVER_IP' IDENTIFIED BY 'REPLACE_WITH_STRONG_PASSWORD';
GRANT SELECT,INSERT,UPDATE ON lr_supplement.supplement_records TO 'lr_supplement_app'@'WEB_SERVER_IP';
GRANT SELECT,INSERT,DELETE ON lr_supplement.supplement_fields TO 'lr_supplement_app'@'WEB_SERVER_IP';
GRANT SELECT,INSERT ON lr_supplement.supplement_audit TO 'lr_supplement_app'@'WEB_SERVER_IP';
GRANT SELECT,UPDATE ON lr_supplement.supplement_meta TO 'lr_supplement_app'@'WEB_SERVER_IP';
