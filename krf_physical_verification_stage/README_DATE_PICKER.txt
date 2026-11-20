Stage 5 - Date-wise report date picker (Indian display)

Replace date_wise_report.php in the existing Stage 5 application folder.
The visible date is DD-MM-YYYY (e.g. 02-11-2026).
Click the calendar icon or date field to open the browser's calendar picker.
The browser-native popup's month/day labels depend on browser locale, but the report field, table, Excel and PDF all display DD-MM-YYYY.
The server accepts DD-MM-YYYY and legacy YYYY-MM-DD links, and filters MySQL using YYYY-MM-DD.
No database changes. No third-party JavaScript or CDN required.
