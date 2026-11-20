# Walkthrough: Product Demand Auto-Aggregation & Production Order PO Linking

We have implemented automatic product-level demand aggregation when creating Purchase Orders (PO), linked the created PO Number back to all associated Production Orders, and prevented completed/ordered demands from reappearing in **System Generated Purchase Demand**.

---

## What Was Changed

### 1. Database Schema
- Added `po_number VARCHAR(100) NULL` and `po_id INT(11) NULL` columns to the `krf_production_stockorder` table.

### 2. Frontend: Product-Level Auto-Aggregation
#### [view_system_demand.php](file:///c:/xampp/htdocs/krf_amanaccounting/purchaseorder/view_system_demand.php)
- **Auto-Grouping & Quantity Aggregation**:
  - Clicking the **Create PO** action button on any demand row automatically scans the dataset for all pending orders of the same `Product Name` (e.g. `NonGrafted-BITTER GOURD, BATUK (BATUK)`).
  - Merges their seed requirements (e.g., 25 + 99 + 56 = **180 Packets**).
  - Pre-fills the PO form with the combined total quantity for each seed item.
- **Linked Production Order Badges in PO Modal**:
  - Displays a visual strip inside the PO Form modal showing all linked `Prod. Order No`s (e.g. `KFP2822`, `KFP3159`, `KFP3401`) and the total packets.
- **Bulk PO Support**:
  - Enhanced `initiateBulkPO()` to also merge duplicate seeds into single item lines with summed packet quantities.

### 3. Backend: PO Creation & Production Order Linking
#### [purchase_modal.php](file:///c:/xampp/htdocs/krf_amanaccounting/krf-orderandsales/modal/purchase_modal.php)
- In `saveNewPurchaseOrder`:
  - When the PO is confirmed, all linked alert IDs are retrieved to identify their `stockorder_no` values.
  - Updates `inventory_purchase_demand_alerts`:
    ```sql
    UPDATE inventory_purchase_demand_alerts 
    SET status = 'Ordered', po_id = ? 
    WHERE id IN (...)
    ```
  - Updates `krf_production_stockorder`:
    ```sql
    UPDATE krf_production_stockorder 
    SET po_number = ?, po_id = ?, updated_at = NOW() 
    WHERE stockorder_no IN (...)
    ```
  - In `getSystemGeneratedDemand`:
    - Excludes demands where `po_id IS NOT NULL AND po_id > 0` or `status != 'Pending'`.

### 4. Alert Lifecycle Fix
#### [orders_functions.php](file:///c:/xampp/htdocs/krf_amanaccounting/krf-orderandsales/functions/orders_functions.php)
- In `processInventoryAlertsAndPO()`:
  - Excludes production orders that already have `po_number` set.
  - In `ON DUPLICATE KEY UPDATE`, preserves existing `Ordered` status:
    ```sql
    status = IF(status = 'Ordered' OR (po_id IS NOT NULL AND po_id > 0), status, 'Pending')
    ```
  - Prevents background scans/crons from resetting ordered demands back to `Pending`.

---

## Verification Results

- Database table `krf_production_stockorder` verified with `po_number` and `po_id` columns.
- Syntax linting passed across all modified PHP files:
  - `krf-orderandsales/functions/orders_functions.php`
  - `krf-orderandsales/modal/purchase_modal.php`
  - `purchaseorder/view_system_demand.php`
