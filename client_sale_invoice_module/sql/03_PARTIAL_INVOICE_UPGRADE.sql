-- Run on the Karjee Farms database used by $krfconn before using V7.
-- Allows one physical scan to be billed across more than one invoice while
-- preventing the same scan from being inserted twice in the same invoice.
ALTER TABLE krf_transport_sale_invoice_link
  DROP INDEX uq_sale_invoice_scan,
  ADD UNIQUE KEY uq_sale_invoice_scan_invoice (projectid, scan_id, invoice_id);

CREATE INDEX idx_sale_invoice_scan_balance
  ON krf_transport_sale_invoice_link (projectid, scan_id, invoiced_qty);
