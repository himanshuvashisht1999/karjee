-- Execute in karjeefarm. Backup existing data first.
ALTER TABLE Physically_Verified_Stock
  ADD COLUMN tray_id INT NULL,
  ADD COLUMN tray_name VARCHAR(100) NULL,
  ADD COLUMN cavity_count INT NULL;

-- Latest successfully saved cavity for each product within a project.
CREATE TABLE IF NOT EXISTS Physical_Verification_Product_Tray_Preference (
  project_id VARCHAR(45) NOT NULL,
  product_id INT NOT NULL,
  tray_id INT NOT NULL,
  updated_by VARCHAR(100) NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (project_id, product_id),
  KEY idx_tray_id (tray_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
