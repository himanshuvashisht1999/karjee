# Client Freight Invoice Module — Release 1

## Included

- Separate Pending Freight and Generated Invoices views.
- Single-trip or multi-trip consolidated freight invoices.
- Strict one-client-per-invoice validation in both UI and backend.
- Eligibility restricted to finalized Client transport freight that is marked
  recoverable and has not already been invoiced.
- Read-only original freight with per-trip discount and adjustment controls.
- Mandatory reason whenever any discount or adjustment is used.
- One accounting invoice header and one breakdown row for every selected trip.
- Stored consolidated PDF with View and Download actions.
- Transport-side invoice linkage for duplicate prevention and reporting.
- Freight Invoice PDF now uses the same KARJEE FARMS branded header as Gate
  Pass and the other frozen Transport documents.
- Trip-wise freight table uses fixed column widths, wrapped trip/route values,
  right-aligned amounts and a separate totals block.
- Vehicle No and Driver Name filters are available in both Pending Freight and
  Generated Invoices, with the same values displayed in their tables.
- Freight review and invoice creation now open in a large responsive modal;
  the long review panel below Pending Freight has been removed.
- Confirmation and error alerts are explicitly layered above the custom review
  modal, fixing the apparent no-action Save button issue.

## Frozen boundaries

- No change to Transport Planning, Loading, Gate Out or Documentation business
  logic.
- No change to the original finalized client freight.
- No update to `tbl_invoices.status`.

## Installation

1. Copy `client_freight_invoice_module` into the application root.
2. Run `sql/01_FREIGHT_INVOICE_LINK.sql` once on the Transport database.
3. Review the invoice numbering values in
   `config_client_freight_invoice.php`.
4. Add the optional navigation link shown in `INTEGRATION.md`.
5. Test one single-trip invoice and one same-client multi-trip invoice on a
   staging database before production use.
