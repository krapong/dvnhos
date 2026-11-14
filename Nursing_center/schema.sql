CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
 name VARCHAR(200) NOT NULL,
 password VARCHAR(255) NOT NULL,
 role ENUM('admin','executive','director','head','staff') NOT NULL,
 unit ENUM('all','opd','lr','ht','dm','ckd','warfarin','asthma','ipd_male','ipd_female') NOT NULL,
 active TINYINT UNSIGNED NOT NULL DEFAULT 1,
 must_change TINYINT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS attempts (
 ip VARCHAR(45) NOT NULL PRIMARY KEY,
 failures INT UNSIGNED NOT NULL,
 updated BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS reports (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 department ENUM('opd','lr','ht','dm','ckd','warfarin','asthma','ipd_male','ipd_female') NOT NULL,
 report_date DATE NOT NULL,
 shift ENUM('day','morning','evening','night') NOT NULL,
 payload JSON NOT NULL,
 version INT UNSIGNED NOT NULL DEFAULT 1,
 updated_by BIGINT UNSIGNED NOT NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY report_slot (department,report_date,shift),
 CONSTRAINT reports_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS report_history (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 report_id BIGINT UNSIGNED NOT NULL,
 version INT UNSIGNED NOT NULL,
 payload JSON NOT NULL,
 changed_by BIGINT UNSIGNED NOT NULL,
 changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT history_report FOREIGN KEY (report_id) REFERENCES reports(id),
 CONSTRAINT history_user FOREIGN KEY (changed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
