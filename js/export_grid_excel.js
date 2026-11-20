async function gridtoexcel_withfilter_chunk(tblid, filename, orientationtype, callback, filter = true, frozen = true) {
    debugger;
    const table = document.getElementById(tblid);
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet(filename);

    // Get Header Rows
    const headerRows = [...table.querySelectorAll('thead tr:not(.noexport)')];
    const totalColumns = table.querySelectorAll('thead tr:last-child th:not(.noexport)').length;

    worksheet.headerFooter.oddHeader = '&C&H' + filename + '&C';
    worksheet.headerFooter.oddFooter = '&C&HPage &P of &N';
    worksheet.pageSetup.paperSize = 9; 
    worksheet.pageSetup.orientation = orientationtype;
    worksheet.pageSetup.margins = { left: 0.5, right: 0.5, top: 0.5, bottom: 0.5, header: 0.3, footer: 0.3 };

    let headerRowIndex = headerRows.length;
  
    headerRows.forEach((row, rowIndex) => {
        let colIndex = 1;  // Start at first column
        row.querySelectorAll('th:not(.noexport)').forEach((th) => {
            let colStart = colIndex;
            let colEnd = colStart+(totalColumns-1);

            let cell = worksheet.getCell(rowIndex + 1, colStart);
            cell.value = th.textContent.trim();
            cell.font = { bold: true };
            cell.alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };
            cell.border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } };
            if (th.colSpan > 1) {
                worksheet.mergeCells(rowIndex + 1, colStart, rowIndex + 1, colEnd);
                colIndex = colEnd + 1; // Move past merged cells
            } else {
                colIndex++; // Move to next column
            }
        });
    }); 
    if (frozen) {
        worksheet.views = [{
            state: 'frozen',
            xSplit: 0,
            ySplit: headerRowIndex // Freezing at data row
        }];
    } 
    const chunkSize = 500;
    const rows = [...table.querySelectorAll('tbody tr:not(.noexport)')];
    let startRow = 0;
    while (startRow < rows.length) {
        const endRow = Math.min(startRow + chunkSize, rows.length);
        await new Promise(resolve => setTimeout(resolve, 50));
        exportChunk(rows, startRow, endRow, worksheet, headerRowIndex + 1);
        startRow += chunkSize;
    }
 
    table.querySelectorAll('thead tr:last-child th:not(.noexport)').forEach((th, colIndex) => {
        let width = parseInt(th.style.width) || 100;
        worksheet.getColumn(colIndex + 1).width = Math.max(width / 7, 10);
    }); 
 
    if (filter) {
        worksheet.autoFilter = {
            from: { row: headerRowIndex, column: 1 },
            to: { row: worksheet.rowCount, column: totalColumns }
        };
    }
 
    worksheet.pageSetup.printTitlesRow = `1:${headerRowIndex}`; 
    workbook.xlsx.writeBuffer().then(buffer => {
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename + ".xlsx";
        a.click();
        callback("done");
    }).catch(console.error);
}


function exportChunk(rows, startRow, endRow, worksheet, newrowcnt) {
    for (let i = startRow; i < endRow; i++) {
        const row = rows[i];
        let prevColSpan = 1;

        row.querySelectorAll('td:not(.noexport)').forEach((td, colIndex) => {
            let colStart = prevColSpan > 1 ? prevColSpan + 1 : colIndex + 1;
            let cell = worksheet.getCell(i + newrowcnt, colStart);
            let text = td.textContent.trim();
            if (/^-?\d+(\.\d+)?$/.test(text)) {
                cell.value = parseFloat(text);
                cell.numFmt = text.includes('.') ? '0.00' : '0';
            } else {
                cell.value = text;
            }
            cell.font = getcssoftd(td, "fontcolor");
            cell.border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: getcssoftd(td, "background") };
            cell.alignment = {
                ...getcssoftd(td, "alignment"),
                wrapText: true  // This ensures the content wraps inside the cell
            };

            if (td.colSpan > 1) {
                worksheet.mergeCells(i + newrowcnt, colStart, i + newrowcnt, colStart + td.colSpan - 1);
                prevColSpan = colStart + td.colSpan - 1;
            } else {
                prevColSpan = colStart;
            }
        });
    }
}


// Optimized Function for Style Retrieval
function getcssoftd(td, reqtype) {
    let obj = {};
    if (reqtype === "fontcolor") {
        let fontSize = parseInt(getComputedStyle(td).fontSize) || 12;
        obj.size = (72 * fontSize) / 96;
        obj.color = { argb: rgbToHex(getComputedStyle(td).color) };
        obj.bold = parseInt(getComputedStyle(td).fontWeight) > 600;
    } else if (reqtype === "background") {
        let bgColor = getComputedStyle(td).backgroundColor;
        if (bgColor !== 'rgba(0, 0, 0, 0)') obj.argb = rgbToHex(bgColor);
    } else if (reqtype === "alignment") {
        obj.horizontal = getComputedStyle(td).textAlign;
        obj.wrapText = getComputedStyle(td).wordWrap === 'break-word';
    }
    return obj;
}

// Convert RGB to Hex
function rgbToHex(rgbString) {
    let rgb = rgbString.match(/\d+/g).map(Number);
    return (1 << 24 | rgb[0] << 16 | rgb[1] << 8 | rgb[2]).toString(16).slice(1);
} 

function pixeltoPoints(pixelval){
	var pxval = parseInt(pixelval);
	var ptval = (72*pxval)/96;
	return ptval;
}
