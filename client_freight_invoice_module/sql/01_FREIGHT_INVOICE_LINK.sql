-- Step 5: Fast Transport -> Accounting Freight Invoice linkage
-- Run once on the KARJEE FARMS / Transport database (the $krfconn database).
-- This does NOT alter tbl_invoices and does NOT touch tbl_invoices_breakdown.

ALTER TABLE krf_transport_trip_client_freight
    ADD COLUMN freight_invoice_id BIGINT NULL AFTER payable_freight_amount,
    ADD COLUMN freight_invoice_no VARCHAR(100) NULL AFTER freight_invoice_id,
    ADD COLUMN freight_invoice_date DATE NULL AFTER freight_invoice_no,
    ADD COLUMN freight_invoice_finyr VARCHAR(20) NULL AFTER freight_invoice_date,
    ADD COLUMN freight_invoice_path VARCHAR(500) NULL AFTER freight_invoice_finyr,

    ADD COLUMN billing_discount_percent DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER freight_invoice_path,
    ADD COLUMN billing_discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER billing_discount_percent,
    ADD COLUMN billing_adjustment_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER billing_discount_amount,
    ADD COLUMN billing_adjustment_reason VARCHAR(500) NULL AFTER billing_adjustment_amount,
    ADD COLUMN billing_taxable_amount DECIMAL(12,2) NULL AFTER billing_adjustment_reason,
    ADD COLUMN billing_cgst_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER billing_taxable_amount,
    ADD COLUMN billing_sgst_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER billing_cgst_amount,
    ADD COLUMN billing_igst_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER billing_sgst_amount,
    ADD COLUMN billing_invoice_amount DECIMAL(12,2) NULL AFTER billing_igst_amount,

    ADD COLUMN freight_invoice_generated_at DATETIME NULL AFTER billing_invoice_amount,
    ADD COLUMN freight_invoice_generated_by VARCHAR(100) NULL AFTER freight_invoice_generated_at,

    ADD INDEX idx_tcf_invoice_lookup (projectid, trip_id, freight_recoverable, clientid),
    ADD INDEX idx_tcf_invoice_no (freight_invoice_no);
