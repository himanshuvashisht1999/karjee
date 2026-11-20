Stage 5 Select2 change-event correction

Root cause: Select2 selection changes were not invoking the existing native JavaScript change listeners used for Location -> Polyhouse -> Bed loading.

Fix: Bridge Select2 select/clear events to native change events. Preserve existing AJAX, Save, bed status, and report logic.

Install: Replace the existing index.php with the file in this folder. Keep report.php and the assets folder. Refresh with Ctrl+F5.

NOTE: Select2 CSS and JS are still loaded from jsDelivr CDN, so an internet connection is required for Select2. If CDN fails, the native dropdowns remain functional. No DB changes.
