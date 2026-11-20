# Client Sale Invoice Module

Independent sale invoicing for physically dispatched client products. It does not alter freight invoices or frozen transport-planning rules.

## Install

1. Copy `client_sale_invoice_module` beside the existing application modules.
2. Run `sql/01_SALE_INVOICE_AND_LEDGER.sql` on the Accounting database used by `$mconn`.
3. Run `sql/02_TRANSPORT_SALE_INVOICE_LINK.sql` on the Karjee Farms database used by `$krfconn`.
4. For an existing installation, run `sql/03_PARTIAL_INVOICE_UPGRADE.sql` once on the database used by `$krfconn`.
5. Add a menu link to `client_sale_invoice_module/index.php`.
6. On the XAMPP server run PHP lint on `index.php`, `ajax/module.php`, `prints/invoice.php`, and `config.php`.

The module uses the same TCPDF installation and Karjee Farms document-storage configuration as Freight Invoice. Ensure the application has `pdfroutines/tcpdf/tcpdf.php` and write permission for `attachementfile/client_sale_invoices/`.

## Frozen rules implemented

- Eligible after Client dispatch/handover when the Trip is `In-Transit`, `In Transit`, `Delivered`, `Received` or `Completed`.
- Quantity comes from successful `krf_transport_trip_scan` rows with status `Loaded`.
- One client per invoice; several trips, PSOs, and products are supported.
- Original rate and discount come from the exact sales-order item. Invoice Qty, Rate, Discount %, Discount Amount and tax selection can be adjusted in review without modifying the sales order or dispatch scan.
- Discount % and Discount Amount recalculate vice versa. Tax mode is mutually exclusive: No Tax, CGST + SGST, or IGST.
- The review table shows separate writable `CGST %`, `SGST %`, and `IGST %` columns. Entering IGST clears CGST/SGST; entering CGST or SGST clears IGST.
- Invoice Qty accepts digits only and is validated again by PHP as a positive whole number not exceeding the available dispatched balance.
- Verified advances are read through `$krfconn`, matched by `advance_payment_transactions.paymentref_no = psono`, and applied FIFO.
- A scan can be invoiced partially; dispatched quantity minus previously invoiced quantity remains pending.
- Sale invoice creates a debit; verified advance creates a credit; application is recorded separately.
- Saving generates and stores a PDF in `attachementfile/client_sale_invoices/{year}/{month}/`. The relative path is written to `$mconn.tbl_invoices.invoicepath` and `$krfconn.krf_transport_sale_invoice_link.invoice_path`.

## Runtime checks

- Test one PSO with several products.
- Test several PSOs for the same client.
- Confirm another client's checkbox is rejected.
- Confirm only In-Transit/In Transit/Delivered/Received/Completed trips appear.
- Confirm advance, balance, ledger entries, and duplicate prevention.
- Open the generated invoice and use **Print / Save PDF**.
