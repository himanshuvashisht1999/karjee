-- Run on the Accounting database used by $mconn.
CREATE TABLE IF NOT EXISTS tbl_client_ledger_account (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  companyid INT DEFAULT NULL, subcompanyid INT DEFAULT NULL, projectid INT NOT NULL,
  client_id VARCHAR(155) NOT NULL, client_name VARCHAR(255) DEFAULT NULL,
  account_code VARCHAR(50) NOT NULL,
  opening_debit DECIMAL(15,2) NOT NULL DEFAULT 0,
  opening_credit DECIMAL(15,2) NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'Active', created_by VARCHAR(100) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(id), UNIQUE KEY uq_client_ledger(projectid,client_id),
  UNIQUE KEY uq_client_account_code(projectid,account_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tbl_client_ledger_entry (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  companyid INT DEFAULT NULL, subcompanyid INT DEFAULT NULL, projectid INT NOT NULL,
  ledger_account_id BIGINT UNSIGNED NOT NULL, account_code VARCHAR(50) NOT NULL,
  client_id VARCHAR(155) NOT NULL, client_name VARCHAR(255) DEFAULT NULL,
  transaction_date DATETIME NOT NULL, source_type VARCHAR(40) NOT NULL,
  source_id BIGINT NOT NULL, source_no VARCHAR(100) DEFAULT NULL,
  psono VARCHAR(100) NOT NULL DEFAULT '', sono VARCHAR(100) DEFAULT NULL,
  trip_id VARCHAR(50) DEFAULT NULL, description VARCHAR(1000) DEFAULT NULL,
  debit_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  credit_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  financial_year VARCHAR(45) DEFAULT NULL,
  reversal_of_entry_id BIGINT UNSIGNED DEFAULT NULL, remarks VARCHAR(1000) DEFAULT NULL,
  created_by VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(id), UNIQUE KEY uq_client_ledger_source(projectid,source_type,source_id,psono),
  KEY idx_client_ledger_statement(projectid,client_id,transaction_date,id),
  KEY idx_client_ledger_account(ledger_account_id,transaction_date),
  CONSTRAINT fk_client_ledger_account FOREIGN KEY(ledger_account_id) REFERENCES tbl_client_ledger_account(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tbl_client_ledger_allocation (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, projectid INT NOT NULL,
  client_id VARCHAR(155) NOT NULL, credit_entry_id BIGINT UNSIGNED NOT NULL,
  debit_entry_id BIGINT UNSIGNED NOT NULL, psono VARCHAR(100) NOT NULL DEFAULT '',
  allocated_amount DECIMAL(15,2) NOT NULL, allocated_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  allocated_by VARCHAR(100) DEFAULT NULL, remarks VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY(id), UNIQUE KEY uq_ledger_allocation(credit_entry_id,debit_entry_id,psono),
  KEY idx_allocation_credit(credit_entry_id), KEY idx_allocation_debit(debit_entry_id),
  CONSTRAINT fk_allocation_credit FOREIGN KEY(credit_entry_id) REFERENCES tbl_client_ledger_entry(id),
  CONSTRAINT fk_allocation_debit FOREIGN KEY(debit_entry_id) REFERENCES tbl_client_ledger_entry(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
