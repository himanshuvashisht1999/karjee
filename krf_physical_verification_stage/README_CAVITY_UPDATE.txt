Stage 5: Cavity selection and calculated plant quantity

1. Backup the current index.php and database.
2. Run upgrade_cavity_tracking.sql once in the karjeefarm database. If any of the three columns already exist, omit that ADD COLUMN statement.
3. Deploy updated index.php; keep report.php and assets/ alongside it.
4. Hard refresh browser (Ctrl+F5).

Behavior:
- Active trays from krf_tray_master, displayed by tray_name.
- Product's last successfully saved cavity auto-selected the next time that product is chosen, across sessions within the project.
- User may override cavity; a successful save updates the remembered choice.
- Total Plants = cavity_count * Number of Trays, readonly; server recalculates and validates.
- Physical count rows retain tray_id, tray_name, cavity_count snapshot for audit.
- Existing polyhouse summary totals, Excel and PDF totals continue to use total_quantity_plants.
- Existing historical stock rows have NULL cavity fields; no backfill is invented.

NOTE: This changes the prior physical-count rule: Total Plants is now calculated, not independently counted. Confirm this business change is intended.
