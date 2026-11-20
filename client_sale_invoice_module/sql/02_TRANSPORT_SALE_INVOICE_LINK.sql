-- Run on the Karjee Farms / Transport database used by $krfconn.
CREATE TABLE IF NOT EXISTS krf_transport_sale_invoice_link (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, projectid INT NOT NULL,
  scan_id BIGINT UNSIGNED NOT NULL, invoice_id INT NOT NULL, invoice_no VARCHAR(50) NOT NULL,
  invoice_date DATE NOT NULL, invoice_path VARCHAR(1000) DEFAULT NULL,
  clientid VARCHAR(45) NOT NULL, trip_id VARCHAR(50) NOT NULL,
  dispatch_plan_detail_id BIGINT DEFAULT NULL, sales_order_item_id INT NOT NULL,
  psono VARCHAR(100) NOT NULL, sono VARCHAR(100) NOT NULL,
  productid VARCHAR(125) NOT NULL, invoiced_qty INT NOT NULL,
  created_by VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(id), UNIQUE KEY uq_sale_invoice_scan(projectid,scan_id),
  KEY idx_sale_invoice(projectid,invoice_id), KEY idx_sale_pending(projectid,clientid,trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
