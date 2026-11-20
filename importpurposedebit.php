<?php
require('header.php');
if($companyid!=""){
	$modalwin = isset($_GET["modal"])?"1":"";
	$new =  isset($_GET["new"])?"1":"";
?>
<style type="text/css">
.editable-cell:empty:before {
  content: attr(data-placeholder);
  color: #FC7E01;
  font-style: italic;
  pointer-events: none;
}
</style>
<div class="main">
	<div class="row">
	    <div class="col-md-12 text-center">
			<h3 class="uppercase ">Purpose Code Master</h3>
	    </div>
	</div>
	<div class="row" id="menubuttons">
	    <div class="col-md-12 mt-1 mb-2  text-center">
			<?php 
			if($loginroleid<3){
			?>
			<button class="btn d-none btn-success mb-0" id="import">Upload/Create New Purpose</button>
			<button class="btn d-none btn-success mb-0" id="editpurpose">View/Edit Existing Purpose</button>
			<?php
			} 
			?>
			<a class="btn btn-deepblue btn-sm pull-right" href="admin_dashboard.php?k=<?php echo $key; ?>">Back To Home</a>
	    </div>
	    <div class="col-md-12">
	    	<!-- <button class="btn-sm btn-danger" onclick="openModalForAddNewPurpose('hidden_rooturl','','','','withdrawn')">Add New Purpose old Fucntion</button> -->
	    </div>
	</div>
	<div class="main-container bg-light">
		<div class="row" id="importfacility">
		    <div class="col-md-12 mt-1 mb-1 pt-2 pb-2 text-center">
				<!-- <h4><u>Import From Standared Format Excel Sheet</u></h4> -->
		    </div>
		</div>
		<div class="p-2 m-2" id="sub-container"> 
			<div class="row p-2 mx-2" id="exportfacility">
				<div class="col-md-6 text-left"></div>
				<div class="col-md-6 text-right d-none">
					<button class="btn-sm btn-deepblue" onclick="AddNewPurposeName('hidden_rooturl','1','','','withdrawn')" id="addpurpose">Add New Purpose New Fucntion</button>
	    			<button class="btn-sm btn-deepblue" onclick="EditorPreviewPurposeName('hidden_rooturl','1','','','withdrawn')" id="addpurpose">Edit/Preview Purpose</button>
					<?php 
					if($loginroleid<2){
					?>
					<button class="btn-sm btn-success" onclick="openModalForReplaceNewPurposeWithOld('hidden_rooturl','','','','withdrawn','');" id="addpurpose">Replacement of Purpoe</button>
					<?php 
					}
					?>
					<button class="btn-sm btn-success refreshpurpose" id="refresspurpose">Refresh Purpose</button>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6 mt-2">
					<label>Purpose Category Type</label><br/>
					<label><input type="radio" class="m-2"  name="purposecategirytype" value="debit" checked /> Withdrawal</label>
					<label><input type="radio" class="m-2" name="purposecategirytype" value="credit" /> Deposit</label>
				</div>
				<div class="col-md-6 mt-2" id="importexceldiv" style="display:none;">
					<label>Import From Excel File</label>
					<button class="btn m-1 btn-sm btn-deepblue float-right" id="downloadexcelpp"><i class="fa fa-download" aria-hidden="true"></i> Download Excel File(Format)</button>
					<input type="file" id="excelfile" class="form-control" accept=".xlsx,.xls"/>
				</div>
				<div class="col-md-6 mt-2" id="filterfromtbldiv" style="display:none;">
					<label>Filter</label>
					<input type="text" id='filterformtbl' oninput="filterTable('#filterformtbl', '#tblpurpose');" class="form-control" placeholder="Type for Searching ......">
				</div>	
				<?php //echo $projectid; ?>
				<div class="col-md-12 mt-2">
					<button class="btn btn-sm mb-2 btn-success" onclick="location.reload();"><i class="fa fa-sync"></i> Refresh Page</button>
					<button class="btn btn-deepblue btn-sm pull-right mb-2" id="addnewpurposerow"><i class="fa fa-plus-circle"></i> NEW PURPOSE WITH NEW CATEGORY</button>
					<div class="table-responsive bg-light" style="max-height:100vh;overflow-y:auto;">
						
						<table id="tblpurpose" class="table-mobile"  style="display:none;">
							<thead class="">
								<tr>
									<th colspan="5">Purpose Code Master List for <b class="ppcattypelbl">Withdrawals</b></th>
								</tr>
								<tr>
									<th colspan="5">Project: <?php echo $projectname; ?></th>
								</tr>
								<tr>
									<th class='sticky-top bg-primary' style="min-width:50px;">S.No</th>
									<th class='sticky-top bg-primary'>Provided Code</th>
									<th class='sticky-top bg-primary'>Category of Purpose</th>
									<th class='sticky-top bg-primary'>Purpose</th>
									<th class='sticky-top bg-primary' style="min-width:150px;">Action</th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
			</div>
			<button class='btn btn-deepblue' style="float:right; position:fixed; bottom:20px; right:50px; display:none;" id='savepurpose' data-source='manual'>Save Purposes</button>
		</div>
	</div>
</div>
<div class="d-none">
	<table id="uploadexcelformatfiletbl">
		<thead>
			<tr>
				<th>&nbsp;</th>
				<th><?php echo $projectname; ?></th>
			</tr>
			<tr>
				<th>&nbsp;</th>
				<th>List of Purpose</th>
			</tr>
			<tr>
				<th>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>
			<tr>
				<th>Purpose Code</th>
				<th>EXPENSE NAME & DETAIL</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
		</tbody>
	</table>
</div>
<?php
}
require('footer.php');
?>
<script type="text/javascript">
$(document).on("click", "#downloadexcelpp", function(){
	$("#uploadexcelformatfiletbl").parent("div").removeClass("d-none");
	gridtoexcel_withfilter_chunk('uploadexcelformatfiletbl', 'Upload_Purpose_Format_File', 'portrait',function(res){$("#uploadexcelformatfiletbl").parent("div").addClass("d-none");});
	
});

$(document).on("change", "#excelfile", function(){
	var file = this.files[0];
	if (file) {
        var fileType = file.type;
        var fileSize = file.size;
        // Check if file type is valid
        if (fileType !== 'application/vnd.ms-excel' && fileType !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            alert('Invalid file type. Please select an Excel file.');
            $(this).val(''); // Clear the file input
        } 
        else{
        	var formData = new FormData();
            formData.append('file', file);
            formData.append('orgcompanyid', $("#hidden_companyid").val());
            formData.append('orgsubcompanyid', $("#hidden_subcompanyid").val());
            formData.append('orgprojectid', $("#hidden_projectid").val());
            formData.append('loginname', $("#hidden_loginname").val());

            $.ajax({
                url: 'modal/showanduploadpurpose.php?action=show',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                	// console.log(response);
                    if(response=="projectnamenotmatched"){
                    	SweetAlertmessageFadeinwithoutrefresh("error", 'The project name provided in the first row of the Excel sheet does not match any project in our registered project master. Please recheck the project name.');
                    	return false;
                    }else if(response){
                    	if(response.includes("Error")){
                    		SweetAlertmessageFadeinwithoutrefresh("error", response);
                    	}
                    	else{
 							$("#tblpurpose tbody").html(response);
                    		$("#savepurpose").data("source","excel");
 							$("#savepurpose").show();
 							$("#tblpurpose").show();
                    	}
                    	$("#excelfile").val('');
                    }
                    else{
                    	alert('File upload failed');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('File upload failed: ' + textStatus);
                }
            });

        }
    }
});

$(document).on("click","#savepurpose",function(){
	var purposedata = new Array();
	var i=0;
	$("#tblpurpose tbody tr").each(function(row,tr){
		// console.log($(tr).find("td.clt_providedcode").text());
			if($(tr).find("td.clt_providedcode").text() && $(tr).find("td.clt_subcategoryname").text().trim() && $(tr).find("td.clt_purposename").text()){
				purposedata[i]={
					"companyid":"<?php echo $companyid;?>",
					"subcompanyid":"<?php echo $subcompanyid;?>",
					"projectid":"<?php echo $projectid;?>",
					"providedcode":$(tr).find("td.clt_providedcode").text(),
					"subcategoryid":$(tr).find("td.clt_subcategoryname").data("subcategoryid"),
					"subcategory":$(tr).find("td.clt_subcategoryname").text(),
					"purposename":$(tr).find("td.clt_purposename").text(),
					"source":$("#savepurpose").data("source")
				};
				i++;
			}
	});
	if(purposedata.length>0){
		var formData = new FormData();
		formData.append("purposedata",JSON.stringify(purposedata));
		formData.append("type",$("input[name='purposecategirytype']:checked").val());
		formData.append("loginname",$("#hidden_loginname").val());
		$.ajax({
	        url: 'modal/showanduploadpurpose.php?action=saveclientpurpose',
	        type: 'POST',
	        data: formData,
	        processData: false,
	        contentType: false,
	        dataType: "JSON",
	        success: function(resp) {
	        	// console.log(resp);
	        	if (resp.length>0) {
	        		for (var i = 0; i < resp.length; i++) {
	        			if(resp[i].status=="Success" || resp[i].status=="success"){
				    		SweetAlertmessageFadein('success', resp[i].message);
				    		// location.reload();
	        			} else {
						    alertmessage('error', resp[i].message);
						    return false;
						}
	        		}
				}
				$(".waitloaderfooter").modal("hide");	            
	        },beforeSend: function (res){
				$(".waitloaderfooter").modal("show");
			}
	    });
	}
	else{
		alert("Category of the purpose not defined.\nPlease define the Category of the purpose first and then proceed to save.");
	}
});

purposecodeof_clientsidedata('hidden_rooturl','withdrawal','allactive','',function(res){
	// console.log(res);
	if(res.length>0){
		$("#tblpurpose tbody").html('');
		var ppcarow='';
		let c=1;
		let isPlusButton="";
		for (var i = 0; i < res.length; i++) {
			isPlusButton = '<i class="fa fa-plus addrowprp" style="font-size: 20px; margin: 2px; color: rgb(144, 12, 63); cursor: pointer;"></i>';
			ppcarow += "<tr data-clpdbsno="+res[i].sno+"><td>"+ c +"</td><td class='clt_providedcode'>"+res[i].providedcode+"</td><td class='clt_subcategoryname' data-subcategoryid='"+res[i].subcategoryid+"'>"+res[i].subcategoryname+"</td><td class='clt_purposename'>"+res[i].purposename+"</td><td>"+isPlusButton+" <i class='fa fa-edit editclientpurpose' style='font-size: 20px; margin: 2px; cursor:pointer; color: blue;'></i> &nbsp; <i class='fa fa-trash removeclientpurpose' style='font-size: 20px; margin: 2px; cursor:pointer; color: red;'></i> &nbsp; <i class='fa fa-undo undorowdata' style='font-size: 20px; margin: 2px; cursor:pointer; color: green; display:none;'></i> <button class='btn btn-sm btn-deepblue updpurposebtn' id='updpurposebtn_"+res[i].sno+"' style='margin:2px; color:rgb(144, 12, 32); cursor:pointer; display:none;'> <i class='fa fa-save' style='color: rgb(144, 12, 32)'></i> Save</button></td></tr>";
			c++;
		}
		if(ppcarow){
			$("#tblpurpose tbody").html(ppcarow);
			$("#savepurpose").data("source","manual");
			// $("#savepurpose").show();
			$("#tblpurpose").show();
			$("#importexceldiv").hide();
			$("#filterfromtbldiv").show();
		}
	}else{
		$("#importexceldiv").show();
		$("#filterfromtbldiv").hide();
	}
});

$(document).on("click","input[name='purposecategirytype']", function(){
	$("#tblpurpose tbody").html('');
	if($(this).val()=="debit"){
		$(".ppcattypelbl").html("Withdrawals");
		purposecodeof_clientsidedata('hidden_rooturl','withdrawal','allactive','',function(res){
			// console.log(res);
			if(res.length>0){
				var ppcarow='';
				let c=1;
				let isPlusButton="";
				for (var i = 0; i < res.length; i++) {
					isPlusButton = '<i class="fa fa-plus addrowprp" style="font-size: 20px; margin: 4px; color: rgb(144, 12, 63); cursor: pointer;"></i>';
					ppcarow += "<tr data-clpdbsno="+res[i].sno+"><td>"+ c +"</td><td class='clt_providedcode'>"+res[i].providedcode+"</td><td class='clt_subcategoryname' data-subcategoryid='"+res[i].subcategoryid+"'>"+res[i].subcategoryname+"</td><td class='clt_purposename'>"+res[i].purposename+"</td><td>"+isPlusButton+" <i class='fa fa-edit editclientpurpose' style='font-size: 20px; margin: 2px; cursor:pointer; color: blue;'></i> &nbsp; <i class='fa fa-trash removeclientpurpose' style='font-size: 20px; margin: 2px; cursor:pointer; color: red;'></i> &nbsp; <i class='fa fa-undo undorowdata' style='font-size: 20px; margin: 2px; cursor:pointer; color: green; display:none;'></i> <button class='btn btn-sm btn-deepblue updpurposebtn' id='updpurposebtn_"+res[i].sno+"' style='margin:2px; color:rgb(144, 12, 32); cursor:pointer; display:none;'> <i class='fa fa-save' style='color: rgb(144, 12, 32)'></i> Save</button></td></tr>";

					c++;
				}
				if(ppcarow){
					$("#tblpurpose tbody").html(ppcarow);
					$("#savepurpose").data("source","manual");
					// $("#savepurpose").show();
					$("#tblpurpose").show();
					$("#importexceldiv").hide();
					$("#filterfromtbldiv").show();
				}
			}else{
				$("#importexceldiv").show();
				$("#filterfromtbldiv").hide();
			}
		});
	}else{
		$(".ppcattypelbl").html("Deposit");
		purposecodeof_clientsidedata('hidden_rooturl','deposit','allactive','',function(res){
			// console.log(res);
			if(res.length>0){
				var ppcarow='';
				let c=1;
				let isPlusButton="";
				for (var i = 0; i < res.length; i++) {
					isPlusButton = '<i class="fa fa-plus addrowprp" style="font-size: 20px; margin: 4px; color: rgb(144, 12, 63); cursor: pointer;"></i>';

					// ppcarow += "<tr data-clpdbsno="+res[i].sno+"><td>"+ c +"</td><td class='clt_providedcode'>"+res[i].providedcode+"</td><td class='clt_subcategoryname' data-subcategoryid='"+res[i].subcategoryid+"'>"+res[i].subcategoryname+"</td><td class='clt_purposename'>"+res[i].purposename+"</td><td>"+isPlusButton+" <i class='fa fa-edit editclientpurpose' style='font-size: 20px; margin: 2px; cursor:pointer; color: blue;'></i> &nbsp; <i class='fa fa-trash removeclientpurpose' style='font-size: 20px; margin: 2px; cursor:pointer; color: red;'></i> </td></tr>";
					ppcarow += "<tr data-clpdbsno="+res[i].sno+"><td>"+ c +"</td><td class='clt_providedcode'>"+res[i].providedcode+"</td><td class='clt_subcategoryname' data-subcategoryid='"+res[i].subcategoryid+"'>"+res[i].subcategoryname+"</td><td class='clt_purposename'>"+res[i].purposename+"</td><td>"+isPlusButton+" <i class='fa fa-edit editclientpurpose' style='font-size: 20px; margin: 2px; cursor:pointer; color: blue;'></i> &nbsp; <i class='fa fa-trash removeclientpurpose' style='font-size: 20px; margin: 2px; cursor:pointer; color: red;'></i> &nbsp; <i class='fa fa-undo undorowdata' style='font-size: 20px; margin: 2px; cursor:pointer; color: green; display:none;'></i> <button class='btn btn-sm btn-deepblue updpurposebtn' id='updpurposebtn_"+res[i].sno+"' style='margin:2px; color:rgb(144, 12, 32); cursor:pointer; display:none;'> <i class='fa fa-save' style='color: rgb(144, 12, 32)'></i> Save</button></td></tr>";
					c++;
				}
				if(ppcarow){
					$("#tblpurpose tbody").html(ppcarow);
					$("#savepurpose").data("source","manual");
					// $("#savepurpose").show();
					$("#tblpurpose").show();
					$("#importexceldiv").hide();
					$("#filterfromtbldiv").show();
				}
			}else{
				$("#importexceldiv").show();
				$("#filterfromtbldiv").hide();
			}
		});
		
	}
});

$(document).on("click", ".editclientpurpose", function () {
    var $upcurrentRow = $(this).closest("tr"); // Get the current row
    var clpdbsno = $upcurrentRow.data("clpdbsno"); // Properly get data attribute if needed

    // Clear background from all rows
    $("#tblpurpose tbody tr td").css("background-color", "");
    // Make the purpose name cell editable
    $upcurrentRow.find("td.clt_purposename")
        .prop("contenteditable", true)
        .focus()
        .css("background-color", "yellow");

    // Show/Hide action buttons within the current row
    $upcurrentRow.find(".editclientpurpose").hide();
    $upcurrentRow.find(".removeclientpurpose").hide();
    $upcurrentRow.find(".addrowprp").hide();
    $upcurrentRow.find(".undorowdata").show();
    $upcurrentRow.find(".updpurposebtn").show();

});

$(document).on("click", ".undorowdata", function(){
	var $upcurrentRow = $(this).closest("tr"); // Get the current row
	// Clear background from all rows
    $("#tblpurpose tbody tr td").css("background-color", "");
    $upcurrentRow.find("td.clt_purposename").prop("contenteditable", false);
	$upcurrentRow.find(".editclientpurpose").show();
	$upcurrentRow.find(".removeclientpurpose").show();
	$upcurrentRow.find(".addrowprp").show();
	$upcurrentRow.find(".undorowdata").hide();
	$upcurrentRow.find(".updpurposebtn").hide();
});

$(document).on("click", ".removeclientpurpose", function(){
	let dbsno=$(this).closest("tr").data("clpdbsno");
	let purposename=$(this).closest("tr").find("td.clt_purposename").text();
	var type = $("input[name='purposecategirytype']:checked").val();
	Swal.fire({
	  title: "Are you sure?",
	  text: "You wont to remove the purpose "+purposename+"!",
	  icon: "warning",
	  showCancelButton: true,
	  confirmButtonColor: "#3085d6",
	  cancelButtonColor: "#d33",
	  confirmButtonText: "Yes, delete it!"
	}).then((result) => {
	  if (result.isConfirmed) {
	    updateORremovepurpose_fromclientsidedata('hidden_rooturl',type,'rm',dbsno);
	  }
	});
});
$(document).on("click", ".addrowprp", function () {
    var rowlength = $("#tblpurpose tbody tr").length +2;
	var $currentRow = $(this).closest("tr"); // Get the current row
	// alert($currentRow);
    var subcategoryname = $currentRow.find("td.clt_subcategoryname").text();
    var subcategoryid = $currentRow.find("td.clt_subcategoryname").data("subcategoryid");
    var purposecategorytype = $("input[name='purposecategirytype']:checked").val();
    // Call the function to fetch the new provided code
    maxnew_providedcode_client('hidden_rooturl',subcategoryid,purposecategorytype,function(res){
	    $currentRow.after(
	        `<tr data-clpdbsno='' data-mpcatid=''>
	        	<td></td>
	            <td  class='clt_providedcode editable-cell' data-placeholder='Enter provided code' contenteditable='true' style='background-color:#EECEC1;'>${res.providedcode}</td>
	            <td class='clt_subcategoryname editable-cell' data-placeholder='Enter sub-category name' data-subcategoryid='${subcategoryid}'>${subcategoryname}</td>
	            <td class='clt_purposename editable-cell' data-placeholder='Enter purpose name' contenteditable='true' style='background-color:#EECEC1;'></td>
	            <td style='background-color:#79d6df;' class='text-center'>
	                <button class='btn btn-sm btn-deepblue updpurposebtn' id='updpurposebtn_${rowlength}' style='margin:2px; color:rgb(144, 12, 32); cursor:pointer;'> <i class="fa fa-save" style='color: rgb(144, 12, 32)'></i> Save</button>
	            </td>
	        </tr>`
	    );
	});
    
});

$(document).on("click", "#addnewpurposerow", function () {
    $("#tblpurpose").show();
    var rowlength = $("#tblpurpose tbody tr").length + 2;
    var subcategoryname = "";
    var purposecategorytype = $("input[name='purposecategirytype']:checked").val();

    // Fetch new subcategoryid
    maxnew_subcategoryid_client('hidden_rooturl', purposecategorytype, function (ress) {
        var subcategoryid = "";
        if (ress.status == "Success") {
            subcategoryid = ress.maxsubcatid;
        } else {
            alertmessage('warning', 'Something went wrong while getting subcategory id');
            return false;
        }
        // Fetch new provided code
        maxnew_providedcode_client('hidden_rooturl', subcategoryid, purposecategorytype, function (res) {
            var newRow = `
			    <tr data-clpdbsno='' data-mpcatid=''>
			        <td></td>
			        <td class="clt_providedcode editable-cell" contenteditable="true" style="background-color:#EECEC1;">${res.providedcode}</td>
			        <td class="clt_subcategoryname editable-cell" data-subcategoryid="${subcategoryid}" contenteditable="true" style="background-color:#EECEC1;">${subcategoryname}</td>
			        <td class="clt_purposename editable-cell" contenteditable="true" style="background-color:#EECEC1;"></td>
			        <td style="background-color:#79d6df;" class="text-center">
			            <button class="btn btn-sm btn-deepblue updpurposebtn" id="updpurposebtn_${rowlength}" style="margin:2px; color:rgb(144, 12, 32); cursor:pointer;">
			                <i class="fa fa-save" style="color: rgb(144, 12, 32);"></i> Save
			            </button>
			        </td>
			    </tr>`;

			var $tbody = $("#tblpurpose tbody");
			let $addedRow;

			if ($tbody.find("tr").length > 0) {
			    // Append at the end and reference the added row
			    $tbody.append(newRow);
			    $addedRow = $tbody.find("tr").last();
			} else {
			    // First row
			    $tbody.html(newRow);
			    $addedRow = $tbody.find("tr").first();
			}

			// Focus the subcategory name cell
			$addedRow.find("td.clt_subcategoryname").focus();
        });
    });
});

$(document).on("click", ".updpurposebtn", function(){
    var $upcurrentRow = $(this).closest("tr"); // Get the current row
    var clpdbsno = $upcurrentRow.data("clpdbsno");
    var updbtnid = $(this).closest("tr").find("button.updpurposebtn").prop("id");
    var purposecategorytype = $("input[name='purposecategirytype']:checked").val();
    var providedcode = $(this).closest("tr").find("td.clt_providedcode").text();
	var subcategoryid = $(this).closest("tr").find("td.clt_subcategoryname").data("subcategoryid");
	var subcategoryname = $(this).closest("tr").find("td.clt_subcategoryname").text();	
	var purposename = $(this).closest("tr").find("td.clt_purposename").text();
    addnewpurpose_clientpurpose(providedcode,subcategoryid,subcategoryname,purposename,purposecategorytype,clpdbsno,updbtnid);
});


</script>