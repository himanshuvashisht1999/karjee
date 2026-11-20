class FlexGrid {
  constructor(containerId, columns, data, tableWidth = null, tableId = null, paginate = false, pageSize = 10) {
    this.container = document.getElementById(containerId);
    this.columns = columns;
    this.data = data;
    this.tableWidth = tableWidth;
    this.tableId = tableId;
    
    this.paginate = paginate; // <--- Pagination option
    this.pageSize = pageSize;  // <--- Rows per page
    this.currentPage = 1;      // <--- Current page number
    
    this.sortColumn = null;
    this.sortAsc = true;

    // Default styles
    this.cellPadding = '8px';
    this.headerBackground = '#f9f9f9';
    this.rowBackgroundColor = '#ffffff';
    this.rowHoverColor = '#f1f1f1';
    this.borderColor = '#ccc';
    this.headerTextColor = '#333';
    this.alternateRowColor = '#f7f7f7';

    this.render();
  }

  render() {
    const table = document.createElement('table');
    table.className = 'flexgrid';
    
    if (this.tableId) {
      table.id = this.tableId;
    }

    table.style.width = this.tableWidth || this._calculateTotalWidth();
    table.style.borderCollapse = 'collapse';
    table.style.tableLayout = 'fixed';

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
  }

  _renderHeader() {
    const thead = document.createElement('thead');
    const tr = document.createElement('tr');

    this.columns.forEach((col, index) => {
      const th = document.createElement('th');
      th.style.width = col.width || '100px';
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

    displayData.forEach((row, index) => {
        const tr = document.createElement('tr');

        if (index % 2 === 0) {
            tr.style.backgroundColor = this.rowBackgroundColor;
        } else {
            tr.style.backgroundColor = this.alternateRowColor;
        }

        this.columns.forEach((col) => {
            const td = document.createElement('td');
            td.style.padding = this.cellPadding;
            td.style.border = `1px solid ${this.borderColor}`;
            td.setAttribute('class', col.key);
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
                }
            } else {
                td.textContent = (cellData !== undefined && cellData !== null) ? cellData : '';
            }

            tr.appendChild(td);
        });

        tr.onmouseover = () => tr.style.backgroundColor = this.rowHoverColor;
        tr.onmouseout = () => {
            if (index % 2 === 0) {
                tr.style.backgroundColor = this.rowBackgroundColor;
            } else {
                tr.style.backgroundColor = this.alternateRowColor;
            }
        };

        tbody.appendChild(tr);
    });

    return tbody;
}


  _renderPaginationControls() {
    const paginationDiv = document.createElement('div');
    paginationDiv.style.textAlign = 'left';
    paginationDiv.style.marginTop = '10px';

    const totalPages = Math.ceil(this.data.length / this.pageSize);

    for (let i = 1; i <= totalPages; i++) {
      const button = document.createElement('button');
      button.textContent = i;
      button.style.margin = '0 5px';
      button.style.padding = '5px 10px';
      button.style.cursor = 'pointer';
      button.style.border = '1px solid #ccc';
      button.style.backgroundColor = (this.currentPage === i) ? '#007BFF' : '#fff';
      button.style.color = (this.currentPage === i) ? '#fff' : '#333';
      button.onclick = () => {
        this.currentPage = i;
        this.render();
      };
      paginationDiv.appendChild(button);
    }

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
}