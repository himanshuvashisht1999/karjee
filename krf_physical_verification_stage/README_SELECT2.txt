Stage 5 Select2 update

Replace index.php, keep report.php and SQL scripts from your existing Stage 5 deployment.
Local jQuery is included in assets/jquery.min.js. Select2 CSS/JS are loaded from jsDelivr CDN; without internet, native dropdowns remain functional. For fully offline usage, download Select2 v4.0.13 select2.min.js and select2.min.css to assets and change the two CDN references in index.php to assets/select2.min.js and assets/select2.min.css.

No database migrations required for Select2. Do not re-run SQL if already applied.
