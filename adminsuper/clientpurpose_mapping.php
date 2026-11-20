<?php
require('../header.php');
if($companyid!=""){
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
			<h3 class="uppercase ">Mapping of Client Purpose with Actual Purpoes Category & Sub-Category</h3>
	    </div>
	</div>
	<div class="main-container mt-4">
		<div class="row bg-info">
			<div class="col-md-6 mt-2">
				<label>Purpose Category Type</label><br/>
				<label><input type="radio" class="m-2"  name="purposecategirytype" value="debit" checked /> Withdrawal</label>
				<label><input type="radio" class="m-2" name="purposecategirytype" value="credit" /> Deposit</label>
			</div>
			<div class="col-md-6 mt-2">
				<label>Choose Sub-Category for Assigning to Client Purpose</label>
				<select class="form-control select2" id="actualsubcategorylist"></select>
			</div>
			<div class="col-md-12 text-center mt-4">
				<h4 id="selectedcategoryname"></h4>
			</div>
			<div class="col-md-12 mt-4">
				<div class="table-responsive" style="max-height:100vh;overflow-y:auto;">
					<table id="tblpurpose" class="table table-bordered">
						<thead>
							<tr>
								<th class='sticky-top bg-primary'>Select</th>
								<th class='sticky-top bg-primary'>Sub Category</th>
								<th class='sticky-top bg-primary'>Select</th>
								<th class='sticky-top bg-primary'>Provided Code</th>
								<th class='sticky-top bg-primary'>Purpose</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
			<div class="col-md-12">
				<button class="btn btn-deepblue" id="savemappingclientpurpose" style="position:fixed; right: 20px; bottom: 50px; display:none;" onclick="savemappingofclientpurpoe('tblpurpose','actualsubcategorylist','purposecategirytype','savemappingclientpurpose');">SAVE MAPPING OF CLIENT PURPOSE</button>
			</div>
		</div>		
	</div>
</div>
<?php
}
require('../footer.php');
?>
<script type="text/javascript">

// actualsubcategorylist

$(document).on("change", "#actualsubcategorylist", function(){
	$("#selectedcategoryname").html($("#actualsubcategorylist option:selected").text());
});

getdefaultcategorysubcategorylist('withdrawn','withdrawn','actualsubcategorylist');
purposecodeof_clientsidedata('hidden_rooturl','withdrawal','allactive','',function(res){
	// console.log(res);
	if(res.length>0){
		$("#tblpurpose tbody").html('');
		let oldsubcatid="";
		let subcatebtn = "";
		let seenSubCats = {}; // to track first occurrence
		let ppcarow = "";

		for (var i = 0; i < res.length; i++) {
		    // Check if this sub_categoryid_client is already seen
		    if (!seenSubCats[res[i].sub_categoryid_client]) {
		        seenSubCats[res[i].sub_categoryid_client] = true;
		        subcatebtn = "<td><input type='checkbox' class='subcatcheckformapping' /></td>";
		    } else {
		        subcatebtn = "<td></td>"; // Empty td for repeated sub_categoryid_client
		    }

		    ppcarow += "<tr data-clpdbsno='" + res[i].sno + "' data-trsubcategoryid='" + res[i].sub_categoryid_client + "' data-trprovidedcode='" + res[i].provided_code + "'>" +
		        subcatebtn +
		        "<td class='clt_subcategoryname' data-subcategoryid='" + res[i].sub_categoryid_client + "'>" + res[i].sub_category_client + "</td>" +
		        "<td><input type='checkbox' class='puposecheckformapping' /></td>" +
		        "<td class='clt_providedcode'>" + res[i].provided_code + "</td>" +
		        "<td class='clt_purposename'>" + res[i].purpose + "</td>" +
		        "</tr>";
		}
		if(ppcarow){
			$("#tblpurpose tbody").html(ppcarow);
			
		}
	}else{
		$("#tblpurpose tbody").append("<tr><td colspan='5' class='text-center'>No data available</td></tr>")
	}
});
$(document).on("click","input[name='purposecategirytype']", function(){
	$("#tblpurpose tbody").empty();
	if($(this).val()=="debit"){
		getdefaultcategorysubcategorylist('withdrawn','withdrawn','actualsubcategorylist');
		purposecodeof_clientsidedata('hidden_rooturl','withdrawal','allactive','',function(res){
			if(res.length>0){
				$("#tblpurpose tbody").html('');
				let oldsubcatid="";
				let subcatebtn = "";
				let seenSubCats = {}; // to track first occurrence
				let ppcarow = "";

				for (var i = 0; i < res.length; i++) {
				    // Check if this sub_categoryid_client is already seen
				    if (!seenSubCats[res[i].sub_categoryid_client]) {
				        seenSubCats[res[i].sub_categoryid_client] = true;
				        subcatebtn = "<td><input type='checkbox' class='subcatcheckformapping' /></td>";
				    } else {
				        subcatebtn = "<td></td>"; // Empty td for repeated sub_categoryid_client
				    }

				    ppcarow += "<tr data-clpdbsno='" + res[i].sno + "' data-trsubcategoryid='" + res[i].sub_categoryid_client + "' data-trprovidedcode='" + res[i].provided_code + "'>" +
				        subcatebtn +
				        "<td class='clt_subcategoryname' data-subcategoryid='" + res[i].sub_categoryid_client + "'>" + res[i].sub_category_client + "</td>" +
				        "<td><input type='checkbox' class='puposecheckformapping' /></td>" +
				        "<td class='clt_providedcode'>" + res[i].provided_code + "</td>" +
				        "<td class='clt_purposename'>" + res[i].purpose + "</td>" +
				        "</tr>";
				}
				if(ppcarow){
					$("#tblpurpose tbody").html(ppcarow);
					
				}
			}else{
				$("#tblpurpose tbody").append("<tr><td colspan='5' class='text-center'>No data available</td></tr>")
			}
		});
	}else{
		getdefaultcategorysubcategorylist('deposit','deposit','actualsubcategorylist');
		$(".ppcattypelbl").html("Deposit");
		purposecodeof_clientsidedata('hidden_rooturl','deposit','allactive','',function(res){
			if(res.length>0){
				$("#tblpurpose tbody").html('');
				let oldsubcatid="";
				let subcatebtn = "";
				let seenSubCats = {}; // to track first occurrence
				let ppcarow = "";

				for (var i = 0; i < res.length; i++) {
				    // Check if this sub_categoryid_client is already seen
				    if (!seenSubCats[res[i].sub_categoryid_client]) {
				        seenSubCats[res[i].sub_categoryid_client] = true;
				        subcatebtn = "<td><input type='checkbox' class='subcatcheckformapping' /></td>";
				    } else {
				        subcatebtn = "<td></td>"; // Empty td for repeated sub_categoryid_client
				    }

				    ppcarow += "<tr data-clpdbsno='" + res[i].sno + "' data-trsubcategoryid='" + res[i].sub_categoryid_client + "' data-trprovidedcode='" + res[i].provided_code + "'>" +
				        subcatebtn +
				        "<td class='clt_subcategoryname' data-subcategoryid='" + res[i].sub_categoryid_client + "'>" + res[i].sub_category_client + "</td>" +
				        "<td><input type='checkbox' class='puposecheckformapping' /></td>" +
				        "<td class='clt_providedcode'>" + res[i].provided_code + "</td>" +
				        "<td class='clt_purposename'>" + res[i].purpose + "</td>" +
				        "</tr>";
				}
				if(ppcarow){
					$("#tblpurpose tbody").html(ppcarow);					
				}
			}else{
				$("#tblpurpose tbody").append("<tr><td colspan='5' class='text-center'>No data available</td></tr>")
			}
		});
	}
});

$(document).on("change", ".subcatcheckformapping", function () {
    const $row = $(this).closest("tr");
    const subCatId = $row.find(".clt_subcategoryname").data("subcategoryid");
    const isChecked = $(this).is(":checked");
    

    // Check/uncheck all matching purpose checkboxes
    $(".clt_subcategoryname[data-subcategoryid='" + subCatId + "']").closest("tr").find(".puposecheckformapping").prop("checked", isChecked);

   if ($(".puposecheckformapping:checked").length > 0) {
	    $("#savemappingclientpurpose").show();
	} else {
	    $("#savemappingclientpurpose").hide();
	}
});

$(document).on("change", ".puposecheckformapping", function () {
    const $row = $(this).closest("tr");
    const subCatId = $row.find(".clt_subcategoryname").data("subcategoryid");
    const $all = $(".clt_subcategoryname[data-subcategoryid='" + subCatId + "']")
        .closest("tr")
        .find(".puposecheckformapping");
    const $checked = $all.filter(":checked");
    const shouldCheckSubCat = $all.length === $checked.length;
    if($checked.length>0){
    	$("#savemappingclientpurpose").show();
    }else{
    	$("#savemappingclientpurpose").hide();
    }
    // Find and update the related subcat checkbox (only the first occurrence)
    $(".clt_subcategoryname[data-subcategoryid='" + subCatId + "']")
        .closest("tr")
        .find(".subcatcheckformapping")
        .prop("checked", shouldCheckSubCat);
});
</script>