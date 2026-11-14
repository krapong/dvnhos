CREATE TABLE IF NOT EXISTS supplement_meta (
    meta_key VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    meta_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO supplement_meta (meta_key, meta_value) VALUES ('write_lock','1');
CREATE TABLE IF NOT EXISTS supplement_records (
    record_id VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    version BIGINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS supplement_fields (
    record_id VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    field_name VARCHAR(191) COLLATE utf8mb4_bin NOT NULL,
    field_value TEXT NOT NULL,
    updated_by VARCHAR(250) NOT NULL,
    updated_at VARCHAR(40) NOT NULL,
    PRIMARY KEY (record_id, field_name),
    CONSTRAINT fk_supplement_fields_record FOREIGN KEY (record_id) REFERENCES supplement_records(record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS supplement_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    record_id VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    field_name VARCHAR(191) COLLATE utf8mb4_bin NOT NULL,
    before_value TEXT NOT NULL,
    after_value TEXT NOT NULL,
    changed_by VARCHAR(250) NOT NULL,
    changed_at VARCHAR(40) NOT NULL,
    INDEX idx_supplement_audit_record (record_id, id),
    CONSTRAINT fk_supplement_audit_record FOREIGN KEY (record_id) REFERENCES supplement_records(record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
