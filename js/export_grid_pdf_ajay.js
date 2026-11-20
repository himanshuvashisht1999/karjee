 
function generatemultitablePDF_ajay(reportsdiv, reportname, rowhide = true) {

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'pt', 'a4');   // Landscape (universal safe)
    const filename = reportname + ".pdf";

    const container = document.querySelector('.' + reportsdiv);
    if (!container) {
        alert("Report container not found");
        return;
    }

    /* ------------------------------------------------
       Temporarily show hidden rows (optional)
    ------------------------------------------------ */
    let hiddenRows = [];
    if (rowhide === false) {
        hiddenRows = container.querySelectorAll(
            'tr.d-none, tr[style*="display:none"]'
        );
        hiddenRows.forEach(r => {
            r.dataset._oldDisplay = r.style.display;
            r.style.display = '';
        });
    }

    const tables = container.querySelectorAll('table');
    let startY = 100; // Safe vertical offset

    tables.forEach(table => {

        const { head, exportIndexes } = buildMultiRowHeader(table);
        const body = buildBody(table, exportIndexes);

        if (!head.length || !body.length) return;

        doc.autoTable({
            head: head,
            body: body,

            startY: startY,
            margin: { top: 100, left: 40, right: 40 },
            tableWidth: 'auto',

            showHead: 'everyPage',

            styles: {
                fontSize: 9,
                cellPadding: 6,
                overflow: 'linebreak',   // 🔑 prevents overlap
                valign: 'middle'
            },

            headStyles: {
                fontStyle: 'bold',
                halign: 'left',
                valign: 'middle', 
                //fillColor: [235, 235, 235],
                fillColor: false,
                textColor: 20,
                minCellHeight: 30,        // 🔑 header row safety
                lineWidth: 0.5,          // header grid
                lineColor: [0, 0, 0]
            },
            bodyStyles: {
                lineWidth: 0.5,          // body grid
                lineColor: [0, 0, 0]
            },

            /* ❌ No filename printed at top */
            didDrawPage: function () {
                doc.setFontSize(9);
                doc.setFont(undefined, 'normal');
                doc.text(
                    'Generated on: ' + new Date().toLocaleString(),
                    doc.internal.pageSize.width - 40,
                    60,
                    { align: 'right' }
                );
            }
        });

        startY = doc.lastAutoTable.finalY + 40;
    });

    /* ------------------------------------------------
       Footer (page numbers)
    ------------------------------------------------ */
    const pages = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pages; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.text(
            `Page ${i} of ${pages}`,
            doc.internal.pageSize.width / 2,
            doc.internal.pageSize.height - 10,
            { align: 'center' }
        );
    }

    doc.save(filename);

    /* ------------------------------------------------
       Restore hidden rows
    ------------------------------------------------ */
    if (rowhide === false) {
        hiddenRows.forEach(r => {
            r.style.display = r.dataset._oldDisplay || 'none';
            delete r.dataset._oldDisplay;
        });
    }
}


 function buildMultiRowHeader(table) {
    const head = [];
    const exportIndexes = [];

    const headerRows = table.querySelectorAll('thead tr');
    if (!headerRows.length) {
        return { head: [], exportIndexes: [] };
    }

    /* Determine exportable columns from LAST header row */
    let colCounter = 0;
    headerRows[headerRows.length - 1]
        .querySelectorAll('th')
        .forEach(th => {
            const colspan = parseInt(th.getAttribute('colspan')) || 1;
            for (let i = 0; i < colspan; i++) {
                if (
                    !th.classList.contains('no-export') &&
                    !th.classList.contains('noexport')
                ) {
                    exportIndexes.push(colCounter);
                }
                colCounter++;
            }
        });

    /* Build each header row with proper colSpan */
    headerRows.forEach(tr => {
        const row = [];
        let colIndex = 0;

        tr.querySelectorAll('th').forEach(th => {
            const colspan = parseInt(th.getAttribute('colspan')) || 1;
            const text = th.innerText.trim();

            let exportableSpan = 0;
            for (let i = 0; i < colspan; i++) {
                if (exportIndexes.includes(colIndex + i)) {
                    exportableSpan++;
                }
            }

            if (exportableSpan > 0) {
                row.push({
                    content: text,
                    colSpan: exportableSpan
                });
            }

            colIndex += colspan;
        });

        if (row.length) {
            head.push(row);
        }
    });

    return { head, exportIndexes };
}
function buildBody(table, exportIndexes) {
    const body = [];

    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach((td, idx) => {
            if (exportIndexes.includes(idx)) {
                row.push(td.innerText.trim());
            }
        });
        if (row.length) body.push(row);
    });

    return body;
}


 