Stage 5 date-wise report: DD-MM-YYYY update

Replace date_wise_report.php with this version (or deploy the complete folder).

The date field, report heading, detail rows, Excel and PDF exports now use DD-MM-YYYY.
The report URL date parameter also uses DD-MM-YYYY, e.g. ?date=01-01-2026.
SQL filtering still uses YYYY-MM-DD internally.
No database changes or additional JavaScript libraries are required.
The date field is a validated text input because native HTML date inputs cannot reliably force Indian display format across browsers.
