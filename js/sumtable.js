function getsumrow(tableid, inclusion = "") {
    const actable = document.getElementById(tableid);
    const table = actable.querySelector('tbody');
    const rowCount = table.rows.length;
    const colCount = table.rows[0].cells.length;

    // Clone the last row and insert at the end
    const cloneRow = table.rows[rowCount - 1].cloneNode(true);
    table.appendChild(cloneRow);

    const includeCols = inclusion ? inclusion.split(",").map(Number) : null;

    for (let j = 0; j < colCount; j++) {
        const cell = cloneRow.cells[j];
        cell.style.fontWeight = "";  // Clear any old styling
        cell.style.textAlign = "";   // Clear old alignment
        cell.innerHTML = "";         // Clear content

        if (j === 0) {
            cell.innerHTML = "Total";
            cell.style.fontWeight = "bold";
            continue;
        }

        const shouldSum = !includeCols || includeCols.includes(j);
        if (!shouldSum) continue;

        let sum = 0;
        for (let i = 0; i < rowCount; i++) {
            const val = table.rows[i].cells[j].innerHTML.trim();
            const num = parseFloat(val === "&nbsp;" || val === "" ? 0 : val);
            sum += isNaN(num) ? 0 : num;
        }

        if (!isNaN(sum)) {
            cell.innerHTML = isFloat(sum) ? sum.toFixed(2) : sum;
            cell.style.fontWeight = "bold";
            cell.style.textAlign = "center";
        }
    }

    cloneRow.classList.add("mobile-hide");
}



function isFloat(value) {
    return Number(value) === value && value % 1 !== 0;
}



/*function getsumrow(tableid,inclusion = "")
{
	var actable = document.getElementById(tableid);
	var table = actable.getElementsByTagName('tbody')[0];
	var row = table.insertRow(table.rows.length);
	row.className = "mobile-hide";
	for(var j=0;j<table.rows[0].cells.length;j++)
	{
		row.insertCell(j);
		if(j==0)
		{
			table.rows[table.rows.length-1].cells[j].innerHTML = "Total";
			table.rows[table.rows.length-1].cells[j].style.fontWeight = "bold";
			continue;			
		}
		if(inclusion)
		{
			var inc = inclusion.split(",");
			if(inc.includes(j.toString())){
				var sum=0;
				for(var i=0;i<table.rows.length-1;i++)
				{
					var  _newcellval;
					var cellval = (table.rows[i].cells[j].innerHTML=="&nbsp;"||table.rows[i].cells[j].innerHTML=="")?0:table.rows[i].cells[j].innerHTML;
					sum=sum+parseFloat(cellval);
				}
				if (isNaN(sum))
				{
					table.rows[table.rows.length-1].cells[j].innerHTML = "";
				}
				else
				{
					if(isFloat(sum)){
						table.rows[table.rows.length-1].cells[j].innerHTML = sum.toFixed(2);
					}
					else{
						table.rows[table.rows.length-1].cells[j].innerHTML = sum;
					}
					table.rows[table.rows.length-1].cells[j].style.fontWeight = "bold";
					table.rows[table.rows.length-1].cells[j].style.textAlign = "center";
				}
			}
			else{
				table.rows[table.rows.length-1].cells[j].innerHTML = "";
			}
			
		}
		else
		{
			var sum=0;
			for(var i=1;i<table.rows.length-1;i++)
			{
				var cellval = table.rows[i].cells[j].innerHTML=="&nbsp;"?0:table.rows[i].cells[j].innerHTML;
				sum=sum+parseFloat(cellval);
			}
			if (isNaN(sum))
			{
				table.rows[table.rows.length-1].cells[j].innerHTML = "";
			}
			else
			{
				if(isFloat(sum)){
					table.rows[table.rows.length-1].cells[j].innerHTML = sum.toFixed(2);
				}
				else{
					table.rows[table.rows.length-1].cells[j].innerHTML = sum;
				}
				table.rows[table.rows.length-1].cells[j].style.fontWeight = "bold";
				table.rows[table.rows.length-1].cells[j].style.textAlign = "center";
			}
		}
	}
}

function isFloat(value) {
  if (typeof value === 'number' && !Number.isNaN(value) && !Number.isInteger(value)) {
    return true;
  }
  return false;
}*/

  