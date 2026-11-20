CREATE TABLE IF NOT EXISTS krf_product_enquiry_followup (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 companyid VARCHAR(50) NOT NULL, subcompanyid VARCHAR(50) NOT NULL, projectid VARCHAR(50) NOT NULL,
 enquiry_id INT NOT NULL, enquiry_no VARCHAR(50) DEFAULT NULL,
 reason VARCHAR(80) NOT NULL, conversation TEXT NOT NULL, next_followup_date DATETIME DEFAULT NULL,
 psono VARCHAR(100) DEFAULT NULL, created_by VARCHAR(100) NOT NULL,created_by_name VARCHAR(255) NOT NULL DEFAULT '',created_at DATETIME NOT NULL,
 PRIMARY KEY(id),KEY idx_enquiry_history(enquiry_id,projectid,id),KEY idx_due(projectid,next_followup_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- No change to existing enums; enquiry status is already VARCHAR.
