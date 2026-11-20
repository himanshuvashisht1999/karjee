# Client Freight Invoice Module

- Standalone from Transport Planning, Loading, Gate Out and Documentation.
- Pending and Generated Invoice tabs.
- One or multiple eligible freight trips per invoice.
- Enforces one client per consolidated invoice.
- Finalized Transport Client Freight is read-only.
- Billing adjustments use Discount and Adjustment (+/-); reason is mandatory
  when either is applied.
- Creates one `tbl_invoices` header and one `tbl_invoices_breakdown` row per
  selected trip through `$mconn`.
- Does not modify `tbl_invoices.status`.
- Links the invoice identity back to `krf_transport_trip_client_freight` through
  `$krfconn` for duplicate prevention and Pending/Generated lists.
- Generates one stored consolidated PDF with View and Download actions.

Run `sql/01_FREIGHT_INVOICE_LINK.sql` once if its columns are not already
available. Configure the invoice series in `config_client_freight_invoice.php`.
