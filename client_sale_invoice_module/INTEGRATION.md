# Integration

Expected application connections: `$krfconn` for transport, sales-order, client, and `advance_payment_transactions` data; `$mconn` for accounting invoices and the client ledger. The module includes `../../connection.php` once and does not redeclare connection functions.

V7 requires the one-time `$krfconn` migration `sql/03_PARTIAL_INVOICE_UPGRADE.sql` on an existing installation. This preserves the uninvoiced balance when Invoice Qty is less than the available dispatched quantity.

V9 generates the PDF during invoice creation using the same Karjee Farms/TCPDF document pattern as Freight Invoice. `prints/invoice.php?invoice_id={id}&mode=inline` streams the stored PDF for viewing/printing, while `mode=download` downloads the same file.
