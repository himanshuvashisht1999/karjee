	var excelarray;
	var tblfields="";
	var workbook;
	var beforeHeaderRow;
	var headers;
	var dataRows;
	var afterLastRow;
	var extraRows;
	
	function importrequest(xldata,startpoint,chunksize)
	{
		
		var endpoint = ((startpoint+chunksize)>xldata.length?xldata.length:(startpoint+chunksize));
		var arrpart = xldata.slice(startpoint,endpoint);
		if (arrpart.length>0)
		{
			var qry = PrepareImport(arrpart);
			let formData = new FormData();
			formData.append("qry",qry);
			$.ajax({
				url: 'dataprocess.php?module=import', 
				dataType: 'text', 
				cache: false,
				contentType: false,
				processData: false,
				data: formData,                         
				type: 'post',
				success: function(res){
					if(res=="1")
					{
						$("#saveinfo").html(endpoint + " Rows Imported.<br/>");
					}
					else
					{
						$("#saveinfo").html(res);
					}
				},
				
				error : function(){
					$("#saveinfo").html("OOPS!!There was some problem in importing data from this file. Please try again, after sometime.");
				},
				complete: function(){
					startpoint+=chunksize;
					importrequest(xldata,startpoint,chunksize);
				}
			});			
		}
		else
		{
			$("#saveinfo").html("Import Completed");
			setTimeout(function() {
				//location.reload();
			}, 5000);
		}
	}
	
   function PrepareImport(xldata)
   {
	    var mapped_fields="companyid, subcompanyid, projectid";
		var mapped_row_vals="";

		$('.tblfields').each(function() {
			if($(this).val()!="")
			{
				mapped_fields=(mapped_fields==""?$(this).val():mapped_fields + "," + $(this).val());
			}

		});
	    if(xldata.length>0)
	    {
		    for(var i=0;i<xldata.length;i++)
		    {
				var colvalref="";
				var colname="";
				var mapped_values="";
				$('.tblfields').each(function() {
					if($(this).val()!="")
					{
						colname = $(this).closest('tr').find('td.xlcol').text();
						colvalref= xldata[i][colname];
						if (colvalref) {
							if (isValidDateString(colvalref)) {
		                        // Check if the value is a date
		                        var dateVal = new Date(colvalref);
		                        if (!isNaN(dateVal.getTime())) { 
		                            // Format date to YYYY-MM-DD
		                            colvalref = dateVal.toISOString().split('T')[0];
		                        }
		                    }
							colvalref= (colvalref?colvalref.replace(/\\/g, "").replace(/'/g, "\\'"):"");
						}
						mapped_values=(mapped_values==""?"'" + colvalref + "'":mapped_values + "," + "'" + colvalref + "'");
					}
				});
				mapped_values = "'"+$("#hidden_companyid").val()+"','"+$("#hidden_subcompanyid").val()+"','"+$("#hidden_projectid").val()+"',"+mapped_values;
				mapped_row_vals=(mapped_row_vals==""?"(" + mapped_values + ")":mapped_row_vals + "," + "(" + mapped_values + ")");
		    }
	    }
		var qry = "insert into " + $('#tbl').val() + "(" + mapped_fields + ") values " + mapped_row_vals;
		console.log(qry);
		//return qry;
   }
   
	function isValidDateString(value) {
	    if (!value) return false;

	    const datePattern = new RegExp(
	        "^" +
	        "(\\d{2,4}[-/ ]\\d{1,2}[-/ ]\\d{1,2}" +        // YYYY-MM-DD, DD-MM-YYYY, YYYY/MM/DD, or with spaces
	        "|\\d{2,4}[// ]\\d{1,2}[// ]\\d{1,2}" +
	        "|\\d{1,2}[- ]?[A-Za-z]{3}[- ]?\\d{2,4}" +     // 12-Jan-2024, 12 Jan 2024, 12-Jan 24
	        "|\\d{1,2}[- ]?[A-Za-z]+[- ]?\\d{2,4}" +       // 12 January 2024, 12-January-24
	        ")" +
	        "( \\d{1,2}:\\d{1,2}(:\\d{1,2})?)?$"           // Optional time (HH:MM or HH:MM:SS)
	    );

	    return datePattern.test(value);
	}

    function GetColHeaders(wb,sheetno,headerrow,lastrow,fields) 
	{
		$('#maptbl tbody').html("");
		var columnSet = [];  
		var tdlfdata="<select class='form-control tblfields'>"+fields+"</select>"
		var ImpSheet = wb.SheetNames[sheetno];
		var sheet = wb.Sheets[ImpSheet];

		// Convert sheet to row object array with adjusted range
		const rawData = XLSX.utils.sheet_to_json(sheet, {
		  header: 1, // Read the first row of the new range as headers
		  defval: "" // Fill empty cells with ""
		});

		beforeHeaderRow = rawData.slice(0, headerrow||0);
		headers = rawData[parseInt(headerrow||0)]; 
		if(parseInt(lastrow||0)){
			dataRows = rawData.slice(parseInt(headerrow||0)+1,parseInt(lastrow));
			afterLastRow = rawData.slice(parseInt(lastrow)); // Rows after lastrow
		}
		else{
			dataRows = rawData.slice(parseInt(headerrow||0)+1);
		}

		if(beforeHeaderRow && afterLastRow){
			extraRows = JSON.stringify([...beforeHeaderRow, ...afterLastRow]);
		}
		else if(beforeHeaderRow && !afterLastRow){
			extraRows = JSON.stringify([...beforeHeaderRow]);
		}
		else if(!beforeHeaderRow && afterLastRow){
			extraRows = JSON.stringify([...afterLastRow]);
		}

		excelarray = dataRows.map(row => {
		    let obj = {};
		    headers.forEach((key, index) => {
		        obj[key] = row[index] || ""; 
		    });
		    return obj;
		});

		if (excelarray.length > 0) 
		{  
			var maphtml="";
			for(var i=0;i<headers.length;i++){
				maphtml+="<tr><td class='xlcol'>"+ headers[i] + "</td><td>"+ tdlfdata +"</td></tr>";
			}
			$('#maptbl tbody').html(maphtml);
			set_mapdetails($("#mid").data("mapdetails"));
		}
    }

	function ListXlSheets(file, selectid) 
	{
    	var sheetlist = "";  
    	// Validate the file extension
	    if (file.name.toLowerCase().endsWith(".xlsx")||file.name.toLowerCase().endsWith(".xls")) {
	        if (typeof (FileReader) != "undefined") {
	            var reader = new FileReader();

	            reader.onload = function (e) {
	                var data;

	                // Check for browser support
	                if (reader.readAsBinaryString) {
	                    data = e.target.result;
	                } else {
	                    var bytes = new Uint8Array(e.target.result);
	                    data = "";
	                    for (var i = 0; i < bytes.byteLength; i++) {
	                        data += String.fromCharCode(bytes[i]);
	                    }
	                }

	                // Read the Excel workbook
	                workbook = XLSX.read(data, { type: 'binary' });
	                var sheets = workbook.SheetNames;

	                // Populate the dropdown with sheet names
	                for (var i = 0; i < sheets.length; i++) {
	                	$("#" + selectid).append("<option value='" + i + "'>" + sheets[i] + "</option>");
	                }
	            };

	            // Read the file based on browser support
	            if (reader.readAsBinaryString) {
	                reader.readAsBinaryString(file);
	            } else {
	                reader.readAsArrayBuffer(file);
	            }
	        }
	    } else {
	        alert("Invalid file type. Please upload an Excel file (.xls or .xlsx).");
	    }
	}


	function loadtable()
	{
		$("#tbl").html('');
		$.ajax({
			url: 'dataprocess.php?module=table', 
			dataType: 'html', 
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			success: function(html){
				$("#tbl").html(html);
				$("#tbl").prop("disabled", false);
			}
		});	
	}

	function loadmapidentifiers()
	{
		$("#mid").html('');
		$.ajax({
			url: 'dataprocess.php?module=map', 
			dataType: 'html', 
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			success: function(html){
				$("#mid").html(html);
			}
		});	
	}

	function check_mappingdetails(tablename){
		$("#mid").removeAttr("data_mapdetails");
		let formData = new FormData();
		var projectid = $("#hidden_projectid").val();
		formData.append("projectid",projectid);
		formData.append("tablename",tablename);
		$.ajax({
			url: 'dataprocess.php?module=tablemapinfo', 
			dataType: 'json', 
			cache: false,
			contentType: false,
			processData: false,
			data: formData,                         
			type: 'post',
			success: function(res){
				if (res.length > 0) {
					const hrow = res[0].hrow;
	    			const map_identifier = res[0].map_identifier;
	    			$("#xlhrow").val(hrow);
	    			$("#mid").val(map_identifier);
					$("#mid").data("mapdetails",res[0].mapdetails);
				}
			}
		});
	}

	function set_mapdetails(mapdtls){
		if(mapdtls){
			const mapDetails = JSON.parse(mapdtls); 
			$("#maptbl tbody tr").each(function(idx, tr) {
			    const excelHeading = $(tr).find("td:first").text().trim(); 
			    const matchedDetail = mapDetails.find(detail => detail.excelcolumnheading === excelHeading);
			    if (matchedDetail) {
			        $(tr).find("select.tblfields").val(matchedDetail.fieldname);
			    }
			});
		}
	}

	function savemapping(){
		let formData = new FormData();
		var projectid = $("#hidden_projectid").val();
		formData.append("projectid",projectid);
		var tablename = $("#tbl").val();
		formData.append("tablename",tablename);
		var map_identifier = $("#mapid").val();
		formData.append("map_identifier",map_identifier);
		var hrow = $("#xlhrow").val();
		formData.append("hrow",hrow);
		var map_details = [];
		$('#maptbl tbody tr').each(function(index, tr) {
		    var fieldValue = $(tr).find("td").find(".tblfields").val(); // Get the input value

		    if (fieldValue !== "") {
		        map_details.push({
		            "fieldname": fieldValue,
		            "excelcolumnheading": $(tr).find("td:first").text().trim(), // Trim unwanted spaces
		            "excelcolumnpos": index
		        });
		    }
		});
		formData.append("map_details",JSON.stringify(map_details)); 
		$.ajax({
			url: 'dataprocess.php?module=savemap', 
			dataType: 'json', 
			cache: false,
			contentType: false,
			processData: false,
			data: formData,                         
			type: 'post',
			success: function(res){
				console.log(res);
			}
		});	
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

	$(document).ready(function(){
		$("#xlsheet").prop("disabled", true);
		$("#tbl").prop("disabled", true);
		$("#SignupForm").formToWizard();

		$(document).on("change","#fquest",function(){
		   var file = this.files[0];
		   if(file.size > 26214400){
		   this.value = "";
		   }
		   else{
			ListXlSheets(file,"xlsheet");
		   }
		});

		$('#fquest_auto').change(async function(){
			const fileUrl = $(this).val().trim();
			if (fileUrl !== "") {
		        try {
		            const file = await getFileFromLink(fileUrl);  // Fetch the file from the URL
		            ListXlSheets(file, "xlsheet");                 // Process the Excel file
		        } catch (error) {
		            console.error("Error fetching the file:", error);
		            alert("Failed to load the Excel file. Please check the URL.");
		        }
		    }
		});

		$('#mid').change(function(){
			$("#mapid").val($(this).find("option:selected").text());
			let formData = new FormData();
			var projectid = $("#hidden_projectid").val();
			formData.append("projectid",projectid);
			formData.append("sno",$(this).val());
			$.ajax({
				url: 'dataprocess.php?module=mapinfo', 
				dataType: 'json', 
				cache: false,
				contentType: false,
				processData: false,
				data: formData,                         
				type: 'post',
				success: function(res){
					if(res.length>0){
	    				const hrow = res[0].hrow;
	    				const tablename = res[0].tablename;
	    				$("#xlhrow").val(hrow);
	    				$("#tbl").val(tablename).trigger("change");
	    				$("#mid").data("mapdetails",res[0].mapdetails);
					}
				}	
			});
		});
		
		$('#tbl').change(function(){
			$("#mapid").val($(this).val() + "_importkeys");
			let formData = new FormData();
			var tbl = $(this).val();
			formData.append("tbl",tbl);
			$.ajax({
				url: 'dataprocess.php?module=field', 
				dataType: 'html', 
				cache: false,
				contentType: false,
				processData: false,
				data: formData,                         
				type: 'post',
				success: function(html){
					tblfields = html;
					check_mappingdetails(tbl);
				}
			});	
		});

		$('#savemap').click(function(){
			savemapping();
		});
		var filetoimport = $("#fquest_auto").data("filepath");
		$("#fquest_auto").val(filetoimport).trigger("change");
		loadmapidentifiers();
		loadtable();
	});