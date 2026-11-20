-- Run in karjeefarm database. Does not modify ERP stock tables.
CREATE TABLE IF NOT EXISTS Physical_Verification_Polyhouse_Confirmation (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 project_id VARCHAR(45) NOT NULL,
 location_id VARCHAR(95) NOT NULL,
 polyhouse_id INT NOT NULL,
 verification_date DATE NOT NULL,
 confirmed_by VARCHAR(100) NOT NULL,
 confirmed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_polyhouse_date (project_id,location_id,polyhouse_id,verification_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
