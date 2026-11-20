class FlexGrid {
  constructor(containerId, columns, data, tableId = null, titlerows = 0, titlerowsData = [],tableWidth = null , paginate = false, pageSize = 10, fexport = false, fsearch = true) {
    this.container = document.getElementById(containerId);
    this.columns = columns.map(col => ({
	  ...col,
	  hidden: col.hidden === true,
	  align: col.align || 'left', // ✅ default to left
	  editable: col.editable || 'false'
	}));

  this.data = data;
	this.originalData = [...data]; // Keep the unfiltered dataset
	this._containerbackground = this.container.style.backgroundColor;
	this._highlightedRows = new Map();
	this.searchrendered = false;
	
  this.tableWidth = tableWidth;
  this.tableId = tableId;
  this.paginate = paginate; // <--- Pagination option
  this.pageSize = pageSize;  // <--- Rows per page
  this.currentPage = 1;      // <--- Current page number
	
	this.titlerows = titlerows;           // <--- Number of title rows
	this.titlerowsData = titlerowsData;     // <--- Array of title row content
    
  this.sortColumn = null;
  this.sortAsc = true;
	this.fexport = fexport;
	this.fsearch = fsearch;
	
	// Default styles
  this.cellPadding = '8px';
  this.headerBackground = '#f9f9f9';
  this.rowBackgroundColor = '#ffffff';
  this.rowHoverColor = '#f1f1f1';
  this.borderColor = '#ccc';
  this.headerTextColor = '#333';
  this.alternateRowColor = '#f7f7f7';
	
	this.searchDiv = document.createElement('div');
	this.searchDiv.className = 'flexgrid-search mb-2';
	this.searchInput = document.createElement('input');
	this.searchInput.type = 'search';
	this.searchInput.placeholder = 'Search...';
	this.searchInput.style.padding = '6px';
	this.searchInput.style.width = '100%';
	this.searchInput.style.margin = '5px';
	this.searchInput.style.border = '1px solid #ccc';
	this.searchInput.style.fontSize = '18px';

	this._onResize = this._onResize.bind(this);  // bind once
	window.addEventListener('resize', this._onResize);
  
	this.searchInput.addEventListener('input', () => {
		const query = this.searchInput.value.toLowerCase();
		this.data = this.originalData.filter(row => {
			return this.columns.some(col => {
				if(!col.hidden){
					const val = row[col.key];
					return val != null && String(val).toLowerCase().includes(query);
				}
				else{
					return false;
				}
			});
		});
	
		this.currentPage = 1;
		
		this._updateTableBody();
	});
	this.render();
}
	destroy() {
	  window.removeEventListener('resize', this._onResize);
	}
	
	_updateTableBody() {
	  const table = this.container.querySelector('table');
	  const oldTbody = table.querySelector('tbody');
	  const newTbody = this._renderBody(); // reuse your existing logic
	  table.replaceChild(newTbody, oldTbody);

	  // Re-render pagination too if needed
	  const oldPagination = this.container.querySelector('.pagination');
	  if (oldPagination) oldPagination.remove();
	  if (this.paginate) {
		this._renderPaginationControls();
	  }
	  this._reapplyhighlight();
	}
	
	_onResize() {
	  clearTimeout(this._resizeTimer);
	  this._resizeTimer = setTimeout(() => {
		this.render();
	  }, 200); // debounce to prevent too many calls
	}

   _isMobile() {
	  return window.innerWidth <= 600;
	}


  render() {
	let toolbarWrapper = document.getElementById("flexgrid-toolbar");
	if (!toolbarWrapper) {
	  toolbarWrapper = document.createElement('div');
	  toolbarWrapper.id = "flexgrid-toolbar";
	  toolbarWrapper.style.display = 'flex';
	  toolbarWrapper.style.flexWrap = 'wrap';
	  if(this._isMobile()){
		toolbarWrapper.style.justifyContent = 'center';
	  }else{
	  	toolbarWrapper.style.justifyContent = 'space-between';
	  }
	  toolbarWrapper.style.alignItems = 'center';
	  toolbarWrapper.style.marginBottom = '0px';
	  toolbarWrapper.style.width = '100%';
	  toolbarWrapper.style.backgroundColor = this._containerbackground;
	  toolbarWrapper.style.padding = '5px';

	  if (this.container.parentNode) {
		this.container.parentNode.insertBefore(toolbarWrapper, this.container);
	  }
	}
	if (this.fsearch) {
		if (!this.searchInput.parentNode || this.searchInput.parentNode !== toolbarWrapper) {
		  if (!this.searchrendered) {
				  if(!toolbarWrapper.querySelector("input[type='search']")){
						toolbarWrapper.appendChild(this.searchInput);
						this.searchrendered = true;
				  }
			}
		}
	}

	if (this.fexport) {
	  const exportButtons = this._addExportButtons();
	  toolbarWrapper.appendChild(exportButtons);
	}

    const table = document.createElement('table');
    table.className = 'flexgrid';
    
    if (this.tableId) {
      table.id = this.tableId;
    }

    table.style.width = this.tableWidth || this._calculateTotalWidth();
    table.style.borderCollapse = 'collapse';
    table.style.tableLayout = 'fixed';
	
	const isMobile = window.innerWidth <= 600;
	if (!isMobile) {
		const colgroup = document.createElement("colgroup");
		this.columns.forEach(col => {
			const colEl = document.createElement("col");
			if (!col.hidden && col.width) colEl.style.width = col.width;
			if (col.hidden) {
				colEl.style.width = "auto";
				colEl.style.visibility = 'collapse';
			}
			colgroup.appendChild(colEl);
		  });
		table.appendChild(colgroup);
	}
    const thead = this._renderHeader();
    const tbody = this._renderBody();

    table.appendChild(thead);
    table.appendChild(tbody);

    this.container.innerHTML = '';
    this.container.appendChild(table);
	
	
    // Render pagination controls if enabled
    if (this.paginate) {
      this._renderPaginationControls();
    }

    this._addMobileCSS();
	this._reapplyhighlight();
  }

  _renderHeader() {
 
    const thead = document.createElement('thead');
	
	if (this.titlerows > 0 && Array.isArray(this.titlerowsData)) {
		for (let i = 0; i < this.titlerows && i < this.titlerowsData.length; i++) {
		  const tr = document.createElement('tr');
		  const rowData = this.titlerowsData[i];

		  const th = document.createElement('th');
		  th.textContent = rowData || '';
		  th.colSpan = this.columns.length;
		  th.style.textAlign = 'left';
		  th.style.padding = this.cellPadding;
		  th.style.backgroundColor = this.headerBackground;
		  th.style.color = this.headerTextColor;
		  th.style.borderBottom = `2px solid ${this.borderColor}`;
		  th.style.fontSize = `16px`;
		  th.style.fontFamily = `'Poppins',Verdana, sans-serif`;
		  tr.appendChild(th);

		  thead.appendChild(tr);
		}
	}
    const tr = document.createElement('tr');

    this.columns.forEach((col, index) => {
      const th = document.createElement('th');
	   if (col.hidden) {
			if (this._isMobile()) {
				th.style.display = 'none';
			} else {
				th.style.visibility = 'collapse';
			}
		th.className = "noexport";
		th.style.whiteSpace = 'nowrap';
		th.style.height = '0px';
		th.style.padding = '0';
	  } 
	  th.style.textAlign = col.align || 'left';
      th.style.padding = this.cellPadding;
      th.style.backgroundColor = this.headerBackground;
      th.style.color = this.headerTextColor;
      th.style.cursor = 'pointer';
      
      // Sticky header properties
      th.style.position = 'sticky';
      th.style.top = '0';
      th.style.zIndex = '2'; 
      
      // Optional: Add bottom border for visibility
      th.style.borderBottom = `2px solid ${this.borderColor}`;

      th.textContent = col.label;

      th.addEventListener('click', () => {
        this._sortBy(index);
      });

      tr.appendChild(th);
    });

    thead.appendChild(tr);
    return thead;
}

  _renderBody() {
    const tbody = document.createElement('tbody');

    let displayData = this.data;

    if (this.paginate) {
        const start = (this.currentPage - 1) * this.pageSize;
        const end = start + this.pageSize;
        displayData = this.data.slice(start, end);
    }
    if(displayData.length>0)
    {
	    displayData.forEach((row, index) => {
	        const tr = document.createElement('tr');
					tr.setAttribute("id",index);
	        if (index % 2 === 0) {
	            tr.style.backgroundColor = this.rowBackgroundColor;
	        } else {
	            tr.style.backgroundColor = this.alternateRowColor;
	        }

	        this.columns.forEach((col) => {
	            const td = document.createElement('td');
				 
				td.style.textAlign = col.align || 'left';
	       		td.style.padding = this.cellPadding;
	       		td.style.border = `1px solid ${this.borderColor}`;
	       		td.style.fontSize = `14px`;
			  	td.style.fontFamily = `'Poppins',Verdana, sans-serif`;
			  	td.style.whiteSpace = 'wrap';
				td.style.wordWrap = 'break-word';				
				if (col.hidden) {
					if (this._isMobile()) {
						td.style.display = 'none';
					} else {
						td.style.visibility = 'collapse';
					}
					td.classList.add("noexport");
					td.style.whiteSpace = 'nowrap';				 
					td.style.height = '0px';
					td.style.padding = '0';
				}
			  	if (col.editable) {
					td.setAttribute('contenteditable', col.editable);
				}
	      		if (col.hidden) {
					td.setAttribute('class', col.key + " noexport");
				}
				else{
					td.setAttribute('class', col.key);
				}
				td.setAttribute('data-label', col.label);

	            const cellData = row[col.key];

	            if (typeof cellData === 'object' && cellData !== null) {
	                if (cellData.type === 'button') {
	                    const btn = document.createElement('button');
	                    btn.textContent = cellData.label || 'Button';
	                    btn.className = cellData.className || '';
	                    btn.onclick = cellData.onclick || function() {};
	                    td.appendChild(btn);
	                } else if (cellData.type === 'input') {
	                    const input = document.createElement('input');
	                    input.type = cellData.inputType || 'text';
	                    input.value = cellData.value || '';
	                    input.className = cellData.className || '';
	                    input.onchange = cellData.onchange || function() {};
	                    td.appendChild(input);
	                } else if (cellData.type === 'checkbox') {
	                    const checkbox = document.createElement('input');
	                    checkbox.type = 'checkbox';
	                    checkbox.checked = cellData.checked || false;
	                    checkbox.className = cellData.className || '';
	                    checkbox.onchange = cellData.onchange || function() {};
	                    td.appendChild(checkbox);
	                } else if (cellData.type === 'radio') {
	                    const radio = document.createElement('input');
	                    radio.type = 'radio';
	                    radio.name = cellData.name || `radio_${col.key}`;
	                    radio.checked = cellData.checked || false;
	                    radio.className = cellData.className || '';
	                    radio.onchange = cellData.onchange || function() {};
	                    td.appendChild(radio);
	                } else if (cellData.type === 'html') {
	                    td.innerHTML = cellData.html || '';
						if (!col.label || col.label.trim() === "") {
							td.removeAttribute('data-label');
							td.classList.add('no-label-html');
						}
	                }
	            } else {
	                td.textContent = (cellData !== undefined && cellData !== null) ? cellData : '';
	            }

	            tr.appendChild(td);
	        });

			tr.onmouseover = () => {
			  if (!tr.hasAttribute('data-bg-locked')) {
				tr.style.backgroundColor = this.rowHoverColor;
			  }
			};
			tr.onmouseout = () => {
			  if (!tr.hasAttribute('data-bg-locked')) {
				tr.style.backgroundColor = (index % 2 === 0)
				  ? this.rowBackgroundColor
				  : this.alternateRowColor;
			  }else{
				  if(tr.hasAttribute('data-highlight')){
					 tr.removeAttribute('data-bg-locked');
					 tr.style.backgroundColor = tr.hasAttribute('data-highlight');
					 tr.setAttribute('data-bg-locked',true);
				  }
			  }
			};

	        tbody.appendChild(tr);
	    });
		}
		else{
			const tr = document.createElement('tr');
			const td = document.createElement('td');
	    td.colSpan = this.columns.length;
	    td.style.textAlign = "center";
	    td.textContent = 'No data available';
	    tr.appendChild(td);
	    tbody.appendChild(tr);
		}

    return tbody;
}

_renderPaginationControls() {
  const paginationDiv = document.createElement('div');
  paginationDiv.className = "pagination";
  paginationDiv.style.textAlign = 'left';
  paginationDiv.style.marginTop = '10px';

  const totalPages = Math.ceil(this.data.length / this.pageSize);

  const createBtn = (label, disabled, onClick) => {
    const btn = document.createElement('button');
    btn.textContent = label;
    btn.disabled = disabled;
    btn.style.margin = '0 5px';
    btn.style.padding = '5px 10px';
    btn.style.cursor = 'pointer';
    btn.style.border = '1px solid #ccc';
    btn.style.backgroundColor = disabled ? '#eee' : '#fff';
    btn.onclick = onClick;
    return btn;
  };

  paginationDiv.appendChild(createBtn('First', this.currentPage === 1, () => {
    this.currentPage = 1;
    this.render();
  }));

  paginationDiv.appendChild(createBtn('Previous', this.currentPage === 1, () => {
    this.currentPage--;
    this.render();
  }));

  const label = document.createElement('span');
  label.textContent = `Page ${this.currentPage} of ${totalPages}`;
  label.style.margin = '0 10px';
  paginationDiv.appendChild(label);

  paginationDiv.appendChild(createBtn('Next', this.currentPage === totalPages, () => {
    this.currentPage++;
    this.render();
  }));

  paginationDiv.appendChild(createBtn('Last', this.currentPage === totalPages, () => {
    this.currentPage = totalPages;
    this.render();
  }));

  this.container.appendChild(paginationDiv);
}

  _sortBy(index) {
    const key = this.columns[index].key;
    if (this.sortColumn === key) {
      this.sortAsc = !this.sortAsc;
    } else {
      this.sortColumn = key;
      this.sortAsc = true;
    }

    this.data.sort((a, b) => {
      const valA = a[key] || '';
      const valB = b[key] || '';
      if (valA < valB) return this.sortAsc ? -1 : 1;
      if (valA > valB) return this.sortAsc ? 1 : -1;
      return 0;
    });

    this.currentPage = 1; // Reset to first page after sort
    this.render();
  }

	_calculateTotalWidth() {
	  let total = 0;
	  let allArePercent = true;

	  this.columns.forEach(col => {
		if (col.hidden) return;
		const width = col.width || '100px';
		if (width.endsWith('%')) {
		  total += parseFloat(width);
		} else {
		  allArePercent = false;
		  total += parseInt(width); // px assumed
		}
	  });

	  return allArePercent ? total + '%' : total + 'px';
	}
	
	_addExportButtons() {
	const oldWrapper = document.getElementById("export-buttons-wrapper");
	if (oldWrapper) oldWrapper.remove();

	const wrapper = document.createElement("div");
	wrapper.id = "export-buttons-wrapper";
	if(this._isMobile()){
		wrapper.style.display = "flex";
	}else{
		wrapper.style.display = "block";
	}
	
	wrapper.innerHTML = `
		<button class="btn btn-light m-2 border border-black" onclick="PrintFlex('${this.container.id}')">
		  <i class="fa fa-print" style="color:grey;"></i> Print
		</button>
		<button class="btn btn-light m-2 border border-black" onclick="PDFFlex('${this.container.id}', '${this.tableId}')">
		  <i class="fa fa-file-pdf" style="color:red;"></i> PDF
		</button>
		<button class="btn btn-light m-2 border border-black" onclick="ExcelFlex('${this.tableId}', '${this.tableId}', 'landscape', function(){})">
		  <i class="fa fa-file-excel" style="color:green;"></i> Excel
		</button>
	`;
	return wrapper;
}


	
  _addMobileCSS() {
    const style = document.createElement('style');
    style.innerHTML = `
      @media screen and (max-width: 600px) {
        .flexgrid {
          border: 0;
          color: #000;
		  width: 100%!important;
        }

        .flexgrid caption {
          font-size: 1.3em;
          font-family: 'Poppins', Verdana, sans-serif !important;
        }

        .flexgrid thead.headings tr {
          border: none !important;
          margin-bottom: 0 !important;
          display: table-row !important;
        }

        .flexgrid thead.headings td,
        .flexgrid thead.headings th {
          border: none !important;
          display: table-cell !important;
          text-align: left !important;
          font-size: 0.9em;
          font-weight: bold;
          background: #d6e6fd;
        }

        .flexgrid thead.headings td::before,
        .flexgrid thead.headings th::before {
          content: none !important;
        }

        .flexgrid thead:not(.headings) {
          border: none;
          clip: rect(0 0 0 0);
          height: 1px;
          margin: -1px;
          overflow: hidden;
          padding: 0;
          position: absolute;
          width: 1px;
        }

        .flexgrid tr {
          border-bottom: 1px solid #333131;
          display: block;
          margin-bottom: 0.4em;
        }

        .flexgrid td:last-child {
          border-bottom: 0;
        }

        .flexgrid td {
          display: flex;
          justify-content: space-between;
          align-items: center;
          text-align: right;
          border-bottom: 1px solid #807d7d;
          font-size: .8em;
          font-family: 'Poppins', Verdana, sans-serif !important;
          padding: 4px 8px;
        }

        .flexgrid td::before {
          content: attr(data-label);
          font-weight: bold;
          text-transform: uppercase;
          margin-right: auto;
          text-align: left;
          flex: 1;
        }

        .flexgrid td span {
          flex: 1;
          text-align: right;
        }
		
		.flexgrid td.no-label-html {
		  justify-content: center;
		  padding-left: 8px;
		  padding-right: 8px;
		}

		.flexgrid td.no-label-html::before {
		  content: none !important;
		  display: none !important;
		}

        .flexgrid .mobile-hide {
          display: none !important;
        }
      }
    `;
    document.head.appendChild(style);
  }

	  // Set table cell padding
	set cellpadding(value) {
		this.cellPadding = value;
		this.render();
	}

	  // Set header background color
	set headerbackground(value) {
		this.headerBackground = value;
		this.render();
	}

	  // Set background color for all rows
	set rowbackground(value) {
		this.rowBackgroundColor = value;
		this.render();
	}

	  // Set hover color for rows
	set rowhovercolor(value) {
		this.rowHoverColor = value;
		this.render();
	}

	  // Set border color
	set bordercolor(value) {
		this.borderColor = value;
		this.render();
	}

	  // Set header text color
	set headertextcolor(value) {
		this.headerTextColor = value;
		this.render();
	}

	  // Set alternate row color
	set alternaterowcolor(value) {
		this.alternateRowColor = value;
		this.render();
	}
		
	highlightRow(id, color) {
		if (!this._highlightedRows) this._highlightedRows = new Map();
		this._highlightedRows.set(id, color);
		this._reapplyhighlight();
	}

	resetAllHighlights() {
		this._highlightedRows.clear();
		const rows = this.container.querySelectorAll('tbody tr');
		rows.forEach((tr, i) => {
		  tr.removeAttribute('data-bg-locked');
		  tr.removeAttribute('data-highlight');
		  tr.style.backgroundColor = (i % 2 === 0)
			? this.rowBackgroundColor
			: this.alternateRowColor;
		});
	}
	
	_reapplyhighlight() {
		if (!this._highlightedRows || this._highlightedRows.size === 0) return;

		const table = this.container.querySelector('table');
		const rows = table.querySelectorAll('tbody tr');

		for (let i = 0; i < this.data.length; i++) {
		  const row = this.data[i];
		  const color = this._highlightedRows.get(row.sno);

		  if (color && rows[i]) {
			const tr = rows[i];
			tr.style.backgroundColor = color;
			tr.setAttribute('data-highlight', color);
			tr.setAttribute('data-bg-locked', 'true');
		  }
		}
	}

	resetRowHighlight(rowIndex) {
		const table = this.container.querySelector('table');
		const rows = table.querySelectorAll('tbody tr');

		if (rowIndex >= 0 && rowIndex < rows.length) {
			const tr = rows[rowIndex];
			const row = this.data[rowIndex];

			// Remove highlight from Map (based on `sno`)
			if (row && row.sno != null && this._highlightedRows) {
			  this._highlightedRows.delete(row.sno);
			}

			// Reset styling
			tr.removeAttribute('data-bg-locked');
			tr.removeAttribute('data-highlight');
			const isEven = rowIndex % 2 === 0;
			tr.style.backgroundColor = isEven
			  ? this.rowBackgroundColor
			  : this.alternateRowColor;
		}
	}
}

function PrintFlex(reportsdiv) {
	var container = document.querySelector('#' + reportsdiv);
    if (!container) return;
	var clone = container.cloneNode(true);
	var pagination = clone.querySelector('.pagination');
    if (pagination) pagination.remove();
	var divContents = clone.innerHTML;
	
    var iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    document.body.appendChild(iframe);
    var doc = iframe.contentWindow.document;
    doc.open();
    doc.write('<html><head><title>Print</title>');
    doc.write('</head><body>');
    doc.write(divContents);
    doc.write('</body></html>');
    doc.close();

    setTimeout(function () {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        setTimeout(function () {
            document.body.removeChild(iframe);
        }, 1000);
    }, 500); 
}

 function PDFFlex(reportsdiv, reportname) {
	var filename = reportname + ".pdf";
	
	var divElement = document.querySelector('#' + reportsdiv);
	if (!divElement) {
		alert("Report container not found.");
		return;
	}
	var rect = divElement.getBoundingClientRect();
	var orientation = rect.width > rect.height ? 'l' : 'p';
	var doc = new jsPDF(orientation, 'pt', 'a4');
	var tbls = divElement.getElementsByTagName('table');
	var linepos = 0;

	for (var i = 0; i < tbls.length; i++) {
		var startPos = (doc.lastAutoTable?.finalY || linepos) + 50;
		var clonedTable = tbls[i].cloneNode(true);
		var allCells = clonedTable.querySelectorAll('th, td');
		
		allCells.forEach(cell => {
			if (cell.style.visibility === 'collapse' || cell.style.visibility === 'hidden') {
			  cell.style.display = 'none';
			}
		});
		var theadRows = clonedTable.querySelectorAll('thead > tr');
		_visible = 0;
		theadRows[theadRows.length-1].querySelectorAll('th').forEach(cell => {
			if(cell.style.display !== 'none'){
				_visible++;
			}
		});
		if (theadRows.length > 1) { 
			for (let irow = 0; irow < theadRows.length - 1; irow++) { 
				const currentRow = theadRows[irow];
				const currentRowCells = currentRow.querySelectorAll('th');
				if (currentRowCells.length > 0) {
					currentRowCells[0].setAttribute('colspan', _visible);
				}
			}
		}
		const tempContainer = document.createElement('div');
		tempContainer.style.position = 'absolute'; 
		tempContainer.style.left = '-9999px';      
		tempContainer.style.top = '-9999px';
		tempContainer.appendChild(clonedTable);
		document.body.appendChild(tempContainer);
		
		doc.autoTable({
			startY: startPos,
			html: clonedTable,
			useCss: true
		});
		document.body.removeChild(tempContainer);
	}

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
 

async function ExcelFlex(tblid, filename, orientationtype, callback, filter = true, frozen = true) {
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

function rgbToHexAlt(rgbString) {
  const match = rgbString.match(/\d+/g);
  if (!match) return '#000000';
  const [r, g, b] = match.map(Number);
  return rgbToHexString(r, g, b);
}

function rgbToHexArray(rgbString) {
  const match = rgbString.match(/\d+/g);
  if (!match) return [255, 255, 255];
  return match.slice(0, 3).map(Number);
}

function rgbToHexString(r, g, b) {
  return "#" + [r, g, b].map(x => x.toString(16).padStart(2, '0')).join('');
}
