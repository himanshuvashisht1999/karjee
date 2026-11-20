function generatemultitablePDF(reportsdiv, reportname) {
	var filename = reportname + ".pdf";
	var doc = new jsPDF('p', 'pt', 'a4');

	var divElement = document.querySelector('.' + reportsdiv);
	if (!divElement) {
		alert("Report container not found.");
		return;
	}
	// ⭐ STEP 1: Show all hidden rows temporarily
	let hiddenRows = divElement.querySelectorAll("tr[style*='display:none'], tr[style*='display: none']");
    hiddenRows.forEach(row => {
        row.setAttribute("data-temp-hidden", "1"); // mark it
        row.style.display = ""; // show it
    });
    
	var tbls = divElement.getElementsByTagName('table');
	var linepos = 0;

	for (var i = 0; i < tbls.length; i++) {
		var startPos = (doc.lastAutoTable?.finalY || linepos) + 50;
		doc.autoTable({
			startY: startPos,
			html: tbls[i],
			useCss: true,
		});
	}
// ⭐ STEP 2: Hide previously hidden rows again
    hiddenRows.forEach(row => {
        if (row.getAttribute("data-temp-hidden") === "1") {
            row.style.display = "none";
            row.removeAttribute("data-temp-hidden");
        }
    });

	const addFooters = doc => {
		const pageCount = doc.internal.getNumberOfPages();
		doc.setFont('helvetica', 'italic');
		doc.setFontSize(8);
		const d = new Date().toLocaleString();

		for (var i = 1; i <= pageCount; i++) {
			doc.setPage(i);
			let pageWidth = doc.internal.pageSize.width;
			let pageHeight = doc.internal.pageSize.height;

			doc.text(reportname, 10, pageHeight - 10);
			doc.text('Generated on: ' + d, pageWidth - 10 - doc.getTextWidth('Generated on: ' + d), pageHeight - 10);
			doc.text('Page ' + i + ' of ' + pageCount, pageWidth / 2, pageHeight - 10, { align: 'center' });
		}
	};

	addFooters(doc);
	doc.save(filename);
}
