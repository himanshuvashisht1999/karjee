# Release 1 — 14 September 2026

- Added independent Client Sale Invoice workflow.
- Added pending and generated invoice tabs with client, vehicle, driver, and text filters.
- Added one-client multi-trip, multi-PSO, multi-product review modal.
- Added dispatched-quantity billing, sales-order rates/discounts, verified PSO advance application, and remaining balance.
- Added unified client ledger account, immutable entries, and allocation schema.
- Added scan-level duplicate invoice protection.
- Added printable Karjee Farms Client Sale Invoice.

## Runtime compatibility fix

- Removed the redundant dependency on `krf_transport_sale_invoice_link.invoice_amount`.
- Generated invoice amount and client name now come from `tbl_invoices` through `$mconn`.
- Added checked SQL preparation so database errors are reported clearly instead of `bind_param() on bool`.
- Pending advance display remains usable before ledger tables are installed; invoice saving still requires the supplied ledger SQL.
- Qualified every Generated Invoice field with its source-table alias, fixing the ambiguous `trip_id` error after joining the transport trip table.
- V4 removes the transport-trip JOIN from the Generated Invoice aggregate entirely. Vehicle and driver values are loaded afterward using a separately prepared, fully qualified lookup. The UI displays `V4-NO-JOIN` to verify the deployed build.
- V5 allows physically loaded Client trips after Gate Out/handover: `In-Transit`, `In Transit`, `Delivered`, `Received` and `Completed`. The same status set is enforced in both listing and save revalidation.
- V6 reads `advance_payment_transactions` through `$krfconn` in both the pending display and save-time FIFO allocation. Invoice and ledger operations remain on `$mconn`.
- V7 adds editable invoice quantity/rate/discount/tax fields, live totals, server-side recalculation, mandatory remarks for commercial changes, partial-quantity carry-forward, and confirmation layering above the review modal.
- V8 shows writable CGST %, SGST % and IGST % as separate main-table columns. Invoice Qty is restricted to digits-only positive whole numbers in both the browser and backend.
- V8.1 corrects the Sale Invoice and Advance Payment ledger `bind_param()` type counts (16 and 13 variables respectively).
- V8.2 adds a post-save **Download / Print Invoice** action that opens the newly created invoice by its returned `invoice_id`.
- V9 generates a TCPDF Sale Invoice during save, stores it under `attachementfile/client_sale_invoices`, writes the path to both invoice tables, and supplies inline and download endpoints.
