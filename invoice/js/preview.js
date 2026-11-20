function preview(url,target){
	var extension = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
	if(extension == "pdf"){
		previewpdf(url,target);
	}
	else if(extension == "doc"){
		previewdoc(url,target);
	}
	else if(extension == "docx"){
		previewdoc(url,target);
	}else if(extension == "odt"){
		previewdoc(url,target);
	}
	else if(extension == "xls"){
		previewexcelold(url,target);
	}
	else if(extension == "xlsx"){
		previewexcel(url,target);
	}
	else if(extension == "gif" || extension == "jpeg" || extension == "jpg" || extension == "png"){
		previewimage(url,target);
	}
	else {
		var container = document.getElementById(target);
		container.innerHTML = '<span> <strong> Unsupported Format : File Cannot Be Rendered. </strong> </span>';
	}
}
async function getFileFromLink(url) {
  const fileRes = await fetch(url);
  const blob = await fileRes.blob();
  let fileName = url.split("/").pop();
  const file = new File([blob], fileName, {
    type: blob.type,
  });
  return file;
}

async function previewexcelold(url,target) {
	var file = await getFileFromLink(url);
	var reader = new FileReader();
	reader.onloadend = function(event) {
		var arrayBuffer = reader.result;

		var options = {cellHTML: true};
		var workbook = XLSX.read(arrayBuffer, options);

		var sheetName = workbook.SheetNames[0];
		var sheet = workbook.Sheets[sheetName];
		var myTable=XLSX.utils.sheet_to_html(sheet);
		myTable=myTable.replace(/<table/i,"<table border='1' style='border-collapse: collapse; border: 1px dotted black;'");
		var ExcelTable = document.getElementById(target);
		ExcelTable.innerHTML = myTable;
	};
	reader.readAsArrayBuffer(file);
}

async function previewexcel(url,target){
	const file = await getFileFromLink(url);
    const reader = new FileReader();
    reader.onload = function(e) {
      const data = e.target.result;
      const workbook = new ExcelJS.Workbook();
      workbook.xlsx.load(data)
        .then(() => {
			const worksheet = workbook.getWorksheet(1);
			const merges = worksheet._merges;
			const columncount = worksheet.columnCount;
			var html = "";
			worksheet.eachRow({ includeEmpty: true }, (row, rowNumber) => {
				if(html){
					html = html + "</tr><tr>";
				}
				else{
					html = "<tr>";
				}
				var cellno = 1;
				var style = "";
				row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
					const cellValue = cell.html;
					const cellStyle = cell.style;
					style="min-width:64px;";
					if(cell.font && cell.font.hasOwnProperty('bold')){
						style = style + "font-weight:700;";
					}
					if(cell.font && cell.font.hasOwnProperty('size')){
						style=style + "font-size:" + cell.font.size + "pt;";
					}
					if(cell.border && Object.keys(cell.border).length>0){
						style=style + "border:1px solid black;";
					}
					else{
						style=style + "border:1px dotted black;";
					}
					if(cell.font && cell.font.color && cell.font.color.hasOwnProperty('theme')){
						style=style + "color:" + rgbToHex(getcolorcomponents(getrgbcolorfromindex(cell.font.color.theme)))+";";
					}
					else if(cell.font && cell.font.color && cell.font.color.hasOwnProperty('argb')){
						style=style + "color:" + argbToHex(cell.font.color.argb)+";";
					}
					if(cell.alignment){
						if(cell.alignment.horizontal){
							style=style + "text-align:" + cell.alignment.horizontal + ";";
						}
						if(cell.alignment.vertical){
							style=style + "vertical-align:" + cell.alignment.vertical + ";";
						}
						if(cell.alignment.wrapText){
							style=style + "text-wrap: wrap;word-break: break-word;";
						}
						else{
							style=style + "text-wrap: nowrap;";
						}
					}
					else{
						style=style + "text-wrap: nowrap;";
					}
					if(row.values.length>2){
						style=style + "max-width:"+ (cell._column.width*9) + "px;";
					}
					if(cell.isMerged && (cell.master._address == cell._address)){
						var top = merges[cell._address].top;
						var bottom = merges[cell._address].bottom;
						var left = merges[cell._address].left;
						var right = merges[cell._address].right;
						var rspan = "";
						var cspan = "";
						if(top!=bottom){
							rspan = "rowspan = '" + (parseInt(bottom-top)+1) + "'";
						}
						if(left!=right){
							cspan = "colspan = '" + (parseInt(right-left)+1) + "'";
						}
						
						if (cell.type === ExcelJS.ValueType.Date) {
							var datevalue = new Date(cellValue).toLocaleDateString('en-GB');
							html = html + "<td "+ cspan +" "+ rspan +" style='"+ style +"'>" + (cellValue?datevalue:"&nbsp;") + "</td>";
						}
						else{
							html = html + "<td "+ cspan +" "+ rspan +" style='"+ style +"'>" + (cellValue?cellValue:"&nbsp;") + "</td>";	
						}
					}
					else{
						if(cell.master._address==cell._address){
							if (cell.type === ExcelJS.ValueType.Date) {
							var datevalue = new Date(cellValue).toLocaleDateString('en-GB');
							html = html + "<td "+ cspan +" "+ rspan +" style='"+ style +"'>" + (cellValue?datevalue:"&nbsp;") + "</td>";
							}
							else{
								html = html + "<td style='"+ style +"'>" + (cellValue?cellValue:"&nbsp;") + "</td>";	
							}
						}
					}
					
					cellno++;
				});
				cellno--;
				while(cellno<columncount){
					if(style==""){
						style="min-width:64px;border:1px dotted black;";
					}
					html = html + "<td style='"+ style +"'>&nbsp;</td>";
					cellno++;
				}
			});
			html = html + "</tr>";
			
			const outputDiv = document.getElementById(target);
			outputDiv.innerHTML = ""; 
			const cellOutput = document.createElement('table');
			cellOutput.setAttribute("cellpadding","1");
			cellOutput.setAttribute("cellspacing","0");
			cellOutput.innerHTML = html;
			outputDiv.appendChild(cellOutput);
        })
        .catch(error => {
          console.error('Error reading Excel file:', error);
        });
    };
    reader.readAsArrayBuffer(file);
}

function previewdoc(url,target){
	var container = document.getElementById(target);
	container.innerHTML = '<iframe src="https://view.officeapps.live.com/op/embed.aspx?src='+url+'&amp;wdPrint=0&amp;wdEmbedCode=0" frameborder="0" style="width:100%; min-height:790px;">This is an embedded <a target="_blank" href="https://office.com">Microsoft Office</a> document, powered by <a target="_blank" href="https://office.com/webapps">Office</a>.</iframe>';
}

async function previewdocx(url,target) {
 var doc = await getFileFromLink(url);
        if (doc != null) {
            var docxOptions = Object.assign(docx.defaultOptions, {
                useMathMLPolyfill: true,
				ignoreWidth:true
            });
            var container = document.getElementById(target);
 
            docx.renderAsync(doc, container, null, docxOptions);
        }
    }
  
function previewpdf(url,target){
	var container = document.getElementById(target);
	container.innerHTML = '<object data="'+url+'" type="application/pdf" style="top:0;width:100%;min-height:790px;" />';
}

function previewimage(url,target){
	var container = document.getElementById(target);
	container.innerHTML = '<img src="'+url+'" style="height:auto;width:auto;" />';
}

function getrgbcolorfromindex(index){
		var rgbcol = [,"RGB(0,0,0)","RGB(255,255,255)","RGB(255,0,0)","RGB(0,255,0)","RGB(0,0,255)","RGB(255,255,0)","RGB(255,0,255)","RGB(0,255,255)","RGB(128,0,0)","RGB(0,128,0)","RGB(0,0,128)","RGB(128,128,0)","RGB(128,0,128)","RGB(0,128,128)","RGB(192,192,192)","RGB(128,128,128)","RGB(153,153,255)","RGB(153,51,102)","RGB(255,255,204)","RGB(204,255,255)","RGB(102,0,102)","RGB(255,128,128)","RGB(0,102,204)","RGB(204,204,255)","RGB(0,0,128)","RGB(255,0,255)","RGB(255,255,0)","RGB(0,255,255)","RGB(128,0,128)","RGB(128,0,0)","RGB(0,128,128)","RGB(0,0,255)","RGB(0,204,255)","RGB(204,255,255)","RGB(204,255,204)","RGB(255,255,153)","RGB(153,204,255)","RGB(255,153,204)","RGB(204,153,255)","RGB(255,204,153)","RGB(51,102,255)","RGB(51,204,204)","RGB(153,204,0)","RGB(255,204,0)","RGB(255,153,0)","RGB(255,102,0)","RGB(102,102,153)","RGB(150,150,150)","RGB(0,51,102)","RGB(51,153,102)","RGB(0,51,0)RGB(51,51,0)","RGB(153,51,0)","RGB(153,51,102)","RGB(51,51,153)","RGB(51,51,51)"];
		return rgbcol[index];
	}
function argbToHex(argb) {
	return '#' + argb.substring(2, argb.length);
}
 
function rgbToHex(rgb) {
	var r = rgb["r"];
	var g = rgb["g"];
	var b = rgb["b"];
	return "#" + (1 << 24 | r << 16 | g << 8 | b).toString(16).slice(1);
}
function getcolorcomponents(rgbstring){
	var rgb = 'rgb(200, 12, 53)'.match(/\d+/g);
	return rgb;
}
// Function to format a date
function formatDate(date, format) {
  format = convertExcelDateFormat(format);
  // Check if the date is valid
  if (!(date instanceof Date) || isNaN(date.getTime())) {
    return 'Invalid Date';
  }

  // Use the format parameter if provided, otherwise use a default format
  format = format || 'MM/DD/YYYY';

  // Object to store date components
  var dateComponents = {
    DD: padZero(date.getDate()),
    MM: padZero(date.getMonth() + 1),
    YYYY: date.getFullYear(),
    // Add more components as needed
  };

  // Replace placeholders with corresponding date components
  var formattedDate = format.replace(/DD|MM|YYYY/g, function (match) {
    return dateComponents[match];
  });

  return formattedDate;
}

// Function to pad single-digit numbers with a leading zero
function padZero(num) {
  return num < 10 ? '0' + num : num;
}

// Function to convert Excel date format to JavaScript date format
function convertExcelDateFormat(excelFormat) {
  const formatMapping = {
    'm': 'MM',   // Month
    'd': 'DD',   // Day
    'yy': 'YYYY', // Year (two digits)
    'yyyy': 'YYYY',// Year (four digits)
    'h': 'HH',   // Hours
    'mm': 'mm',   // Minutes
	'"h"':"HH"
    // Add more mappings as needed
  };

  // Replace Excel format codes with JavaScript format codes
  const jsFormat = excelFormat.replace(/m|d|yy|yyyy|h|mm|\"h\"/g, match => formatMapping[match] || match);

  return jsFormat;
}
