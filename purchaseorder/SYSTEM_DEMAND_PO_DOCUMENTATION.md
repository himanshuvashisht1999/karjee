# Complete Documentation: System Demand Aggregation & Purchase Order Linking

**Project**: `krf_amanaccounting`  
**Module**: Purchase Order & Demand Management (`purchaseorder/`, `krf-orderandsales/`)  
**Date**: August 26, 2026  

---

## 1. Database Schema Changes

### A. Table: `krf_production_stockorder`
Two new columns were added to link created Purchase Orders back to their corresponding Production Orders:

| Column Name | Data Type | Nullable | Default | Description |
|---|---|---|---|---|
| `po_number` | `VARCHAR(100)` | `YES` | `NULL` | Stores generated PO Number (e.g. `PO-2026-0008`) against `stockorder_no` |
| `po_id` | `INT(11)` | `YES` | `NULL` | Stores primary key ID of `inventory_purchase_orders` |

#### SQL Migration Executed:
```sql
ALTER TABLE krf_production_stockorder 
  ADD COLUMN po_number VARCHAR(100) NULL AFTER status,
  ADD COLUMN po_id INT(11) NULL AFTER po_number;
```

---

### B. Table: `inventory_purchase_demand_alerts`
- **Status Lifecycle Update**:
  - When a PO is created, records are updated to `status = 'Ordered'` and `po_id = <po_id>`.
  - Filter queries in `purchase_modal.php` ensure only pending records (`status = 'Pending' AND (po_id IS NULL OR po_id = 0)`) are fetched.

---

## 2. Modified Files & Change Details

### 1. `purchaseorder/view_system_demand.php`
- **Total Packets & Required Qty Footers**:
  - Added dynamic `<tfoot>` in `#demandTable` to show total `Required Qty` and total `Packets Needed` badge (`{Total} Pkts`).
  - Added summary total badge in the card footer (`Total Packets Needed: {Total} Pkts`).
- **Product-Wise Auto-Aggregation on "Create PO"**:
  - Clicking the cart button on any row automatically identifies all pending demand alerts sharing the same `productname`.
  - Sums their quantities (e.g. `25 + 99 + 56 = 180 Packets`) into the PO modal items table.
  - Displays a banner in the modal showing all linked Production Orders (`KFP2822, KFP3159, KFP3401`) and the combined packet count.
- **Unit Price Input Placeholder**:
  - Changed Unit Price input from pre-filled `value="0.00"` to `placeholder="0.00"`.
- **Single-Product Checkbox Auto-Filtering**:
  - Removed `#selectAllDemand` (multi-checkbox select all) from the table header and replaced with a filter icon.
  - Clicking any row's checkbox activates single-product filter (`activeSelectedProduct`) to display **only** rows matching that `Product Name`.
  - Recalculates total packets needed and required quantity specifically for that product.
  - Unchecking resets the filter and restores all products.
  - Checking a different product directly switches the filter to that product.
  - `initiateBulkPO()` creates a PO for all demand rows belonging to the active checked product.

---

### 2. `krf-orderandsales/modal/purchase_modal.php`
- **`getSystemGeneratedDemand`**:
  - Added condition `AND (po_id IS NULL OR po_id = 0)` to ensure demands linked to a created PO are excluded from the demand list.
- **`saveNewPurchaseOrder`**:
  - When a PO is saved:
    1. Extracts all linked alert IDs and fetches their `stockorder_no` values from `inventory_purchase_demand_alerts`.
    2. Updates `inventory_purchase_demand_alerts` with `status = 'Ordered'` and `po_id = ?`.
    3. Updates `krf_production_stockorder` with `po_number = ?`, `po_id = ?`, and `updated_at = NOW()` for each linked `stockorder_no`.
    4. Returns `po_number` and updated `orders` list in the JSON response.

---

### 3. `krf-orderandsales/functions/orders_functions.php`
- **`processInventoryAlertsAndPO()`**:
  - Excluded production stock orders where `po_number` is already assigned (`AND (stk.po_number IS NULL OR stk.po_number = '')`).
  - In `ON DUPLICATE KEY UPDATE`, changed unconditional `status = 'Pending'` to:
    ```sql
    status = IF(status = 'Ordered' OR (po_id IS NOT NULL AND po_id > 0), status, 'Pending')
    ```
  - Prevents background scans and cron jobs from resetting already ordered demands back to `Pending`.

---

### 4. `purchaseorder/po_functions.php`
- **`getPurchaseOrders()`**:
  - Added `vendor_master` mapping (`VendorID` => `VendorName`) alongside `supplier_authorizedperson`.
  - Fixes vendor name display in **Purchase Orders Directory** so seed vendor IDs (such as `V01` for *MANSOON AGRO AGENCY* and `V02` for *VNR SEEDS PVT. LTD.*) display their actual Vendor Name instead of `N/A`.
  - Updated search query filter to support searching by vendor names from `vendor_master`.
- **`getPurchaseOrderById()`**:
  - Added fallback lookup to `vendor_master` when fetching PO details.

---

### 5. `purchaseorder/ajax_po.php`
- **Vendor Name Lookup in Receipt Views**:
  - Added `vendor_master` fallback in `get_receipt_details` so vendor names are resolved when viewing goods receipts.

---

## 3. Summary of Modified Files

| # | File Path | Status | Key Changes |
|---|---|---|---|
| 1 | `purchaseorder/view_system_demand.php` | Modified | Total packets in footer, auto-grouping by product on PO creation, placeholder `0.00`, single-product checkbox filter |
| 2 | `krf-orderandsales/modal/purchase_modal.php` | Modified | PO creation logic, links `po_number` to `krf_production_stockorder`, marks alerts as `Ordered` |
| 3 | `krf-orderandsales/functions/orders_functions.php` | Modified | Excludes orders with PO and preserves `Ordered` status in alert generator |
| 4 | `purchaseorder/po_functions.php` | Modified | Maps `vendor_master` (`V01`, `V02`) in Purchase Orders Directory |
| 5 | `purchaseorder/ajax_po.php` | Modified | Maps `vendor_master` for receipt and PO views |

---

## 4. End-to-End User Flow

1. **Viewing Demand**:
   - User opens `view_system_demand.php`.
   - The table displays pending demands with live totals in table and card footers.
2. **Filtering by Product**:
   - Checking any row's checkbox filters the table to show all demand items for that product and recalculates the totals.
3. **Creating Purchase Order**:
   - User clicks the Cart action button on any row of a product (or clicks **Bulk Create PO**).
   - All pending demands for that product are automatically aggregated into the PO Form modal with summed packet quantities.
   - Unit price shows `0.00` placeholder for quick pricing entry.
4. **Submitting PO**:
   - User confirms the PO.
   - The system generates `po_number` (e.g. `PO-2026-0009`), updates `krf_production_stockorder` with `po_number` against all linked `stockorder_no`s, marks demand alerts as `Ordered`, and removes them from the pending demand view.
5. **Viewing in Directory**:
   - User opens **Purchase Orders Directory** (`purchaseorder/index.php`).
   - The newly created PO displays the correct vendor name (e.g. *MANSOON AGRO AGENCY*).
