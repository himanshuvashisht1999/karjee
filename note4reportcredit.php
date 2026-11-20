<?php
require('header.php');
if($companyid!=""){
?>
<div class="main">
	<div class="row">
	    <div class="col-md-12 mt-1 mb-2 pt-3 pb-3 text-center" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">
			<span class="uppercase  bold" style="font-size: 1.75rem;text-shadow:2px 2px gray;">Note-4 Report</span>
	    </div>
	</div>
 
	<div class="row  bg-light align-items-center" style="border-bottom:1px solid black;">
		<div class="col-md-6 bg-warning">
			<div class="row">
				<div class="col-md-4 mt-1 mb-2  text-center">
			    	<input type="radio" name="optdate" id="unaudit" /><label class="mx-1 text-dark lbl-text" for="unaudit">Unaudited Till Date</label>
			    </div>
			    <div class="col-md-4 mt-1 mb-2  text-center">
			    	<input type="radio" name="optdate" id="finyr" /><label class="mx-1 text-dark lbl-text" for="finyr">Financial Year</label>
			    </div>
			    <div class="col-md-4 mt-1 mb-2  text-center">
			    	<input type="radio" name="optdate" id="cusdt" /><label class="mx-1 text-dark lbl-text" for="cusdt">Custom Date Range</label>
			    	<input class="d-none" type="radio" name="optdate"/>
			    </div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="row justify-content-center">
						<div class="col-md-4 mt-1 mb-2  text-center finyearsection" style="display:none;">
					    	<label class="mx-1 text-dark lbl-text" for="financialyear">Select Financial Year</label>
					    	<select id="financialyear" class="select2 form-control" >
				        		<option selected disabled>Select Financial Year</option>
				        	</select>
					    </div>
					    <div class="col-md-12 mt-1 mb-2  text-center datesection"  style="display:none;">
					    	<label class="mx-1 text-dark lbl-text" for="dtfrom">Date From</label><input type="date" id="dtfrom" />
					    	<label class="mx-1 text-dark lbl-text" for="dtto">Date To</label><input type="date" id="dtto" />
					    </div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="row">
			    <div class="col-md-6 mt-1 mb-2  text-center">
			    	<input type="radio" name="optrep" id="allbnk" /><label class="mx-1 text-dark lbl-text" for="allbnk">All Bank Accounts</label>
			    	<input class="d-none" type="radio" name="optrep"/>
			    </div>
			    <div class="col-md-6 mt-1 mb-2  text-center">
			    	<input type="radio" name="optrep" id="selbnk" /><label class="mx-1 text-dark lbl-text" for="selbnk">Selected Bank Account</label>
			    </div>
			</div>
			<div class="row justify-content-center">
				<div class="col-md-7 mt-1 mb-2 accountsection" style="display:none;">
					<label class="mx-1 text-dark lbl-text">Select Bank Account</label>
					<select id="accountlist" class="form-control select2 actioninput" style="margin-top: 0px">
						<option selected disabled>Select Account</option>
					</select>
				</div>
			</div>
		</div>
	</div>
	<div class="row  bg-light justify-content-center" id="genreportdiv" style="display:none;">
	    <div class="col-md-6 mt-1 mb-2 text-center">
	    	<button class="btn btn-success mb-0" id="genreport">Generate Report</button>
	    </div>
	</div>
	<div class="row bg-light" id="reportdiv" style="display:none;">
	    <div class="col-md-12 mt-1 mb-2" id="reportsection">
	    	<div class="bg-info border border-black" id="note3" style="color:black;">
	      		<div class="col-md-12">
	      			<button class="btn btn-waring m-2 pull-right" id="btnexpn4main" onclick="gridtoexcel_withfilter_chunk('not4data_tbl',$('#genreport').data('filename'),'landscape',function(){});"><i class="fa fa-file-excel" style="color:green;"></i> Export</button>
	      			<button class="btn btn-waring m-2 pull-right d-none" id="savenote4bs" name="savenote4bs"><i class="fa fa-file-save" style="color:green;"></i> Save Note4 For BS</button>
			        <div class="table-responsive" style="margin: 0px 5px 5px 5px;overflow-y:auto;">
		  				<table class="table table-bordered table-striped table-light" id="not4data_tbl">
		            		<thead class="sticky-top">
		            			<tr class="bg-light border-0">
		            				<th colspan="8" class="sticky-top text-dark border-0 repname px-1" style="font-size: 22px;"></th>
		            			</tr>
		            			<tr class="bg-light border-0">
		            				<th colspan="8" class="sticky-top text-dark border-0 repprname p-1" style="font-size: 16px;"></th>
		            			</tr>
		            			<tr class="bg-light border-0">
		            				<th colspan="8" class="sticky-top text-dark border-0 selbankname p-1" style="font-size: 16px;"></th>
		            			</tr>
		            			<tr class="bg-light border-0">
		            				<th colspan="8" class="sticky-top text-dark border-0 titledate p-1" style="font-size: 16px;"></th>
		            			</tr>
		            			<tr class="bg-light border-0">
		            				<th colspan="8" class="sticky-top text-dark border-0 totalentries p-1" style="font-size: 16px;"></th>
		            			</tr>
		            			<tr class="bg-light border-0">
		            				<th colspan="8" class="sticky-top text-dark border-0 titlemain p-1" style="font-size: 16px;"></th>
		            			</tr>
		            			<tr class="bg-light border-0">
		            				<th colspan="8" class="sticky-top text-dark border-0 repno p-1" style="font-size: 16px;"></th>
		            			</tr>
		              			<tr class="bg-info border-0">
					                <th class="bg-info sticky-top" style="width:60px; text-align:center;">S.No</th>
					                <th class="bg-info sticky-top">Purpose Category</th>
					                <th class="bg-info sticky-top">Purpose Sub Category</th>
					                <th class="bg-info sticky-top">Purpose</th>
					                <th class="bg-info sticky-top">Provided Code</th>
					                <th class="bg-info sticky-top">Total Amount</th>
					                <th class="bg-info sticky-top">Reconstruction (%)</th>
					                <th class="bg-info sticky-top">Total Entries</th>
		              			</tr>
		            		</thead>
		            		<tbody>
		            		</tbody>
		    			</table>
		        	</div>
      			</div>
    		</div>
	    </div>
	    <div class="col-md-12 mt-1 mb-2 d-none" id="hiddenreportsectionall">
	        <div class="table-responsive" style="margin: 0px 5px 5px 5px;height:60vh;overflow-y:auto;">
  				<table class="table table-bordered table-striped table-light" id="hiddenallnot4data_tbl">
            		<thead class="bg-info">
              			<tr class="bg-info">
			                <th class="bg-info sticky-top" style="width:60px; text-align:center;">S.No</th>
			                <th class="bg-info sticky-top" style="'.$editstyle.'">Purpose Category</th>
			                <th class="bg-info sticky-top">Purpose Sub Category</th>
			                <th class="bg-info sticky-top">Purpose Description</th>
			                <th class="bg-info sticky-top">Purpose Code</th>
			                <th class="bg-info sticky-top">Provided Code</th>
			                <th class="bg-info sticky-top">Total Amount</th>
			                <th class="bg-info sticky-top noexport" style="'.$editstyle.'">Action</th>
			                <th class="bg-info sticky-top noexport" style="display:none;">Category</th>
			                <th class="bg-info sticky-top noexport" style="display:none;">Subcategory</th>
			                <th class="bg-info sticky-top noexport" style="display:none;">Account Id</th>
			                <th class="bg-info sticky-top noexport" style="display:none;">Account Id</th>
			                <th class="bg-info sticky-top noexport" style="display:none;">Date From</th>
			                <th class="bg-info sticky-top noexport" style="display:none;">Date To</th>
              			</tr>
            		</thead>
            		<tbody>
            		</tbody>
    			</table>
        	</div>
	        <div class="table-responsive" style="margin: 0px 5px 5px 5px;height:60vh;overflow-y:auto;">
  				<table class="table table-bordered table-striped table-light" id="hiddennot4data_tbl">
            		<thead class="bg-info">
              			<tr class="bg-info">
			                <th class="bg-info sticky-top" style="width:60px; text-align:center;">S.No</th>
			                <th class="bg-info sticky-top" style="'.$editstyle.'">Purpose Category</th>
			                <th class="bg-info sticky-top">Purpose Sub Category</th>
			                <th class="bg-info sticky-top">Purpose Description</th>
			                <th class="bg-info sticky-top">Purpose Code</th>
			                <th class="bg-info sticky-top">Provided Code</th>
			                <th class="bg-info sticky-top">Total Amount</th>
			                <th class="bg-info sticky-top noexport" style="'.$editstyle.'">Action</th>
			                <th class="bg-info sticky-top noexport" style="display:none;">Category</th>
			                <th class="bg-info sticky-top noexport" style="display:none;">Subcategory</th>
			                <th class="bg-info sticky-top noexport" style="display:none;">Account Id</th>
			                <th class="bg-info sticky-top noexport" style="display:none;">Account Id</th>
			                <th class="bg-info sticky-top noexport" style="display:none;">Date From</th>
			                <th class="bg-info sticky-top noexport" style="display:none;">Date To</th>

              			</tr>
            		</thead>
            		<tbody>
            		</tbody>
    			</table>
        	</div>
    	</div>
	</div>
</div> 
<?php
}
require('footer.php');
?>
<script type="text/javascript">
	$(document).ready(function(){
		getallbankaccounts('<?php echo $companyid; ?>','<?php echo $subcompanyid; ?>','<?php echo $projectid; ?>',function(res){
   			// console.log(res);
           $("#accountlist").empty();
            if(res.length>0){
              	for (var i = 0; i < res.length; i++) {
                	$("#accountlist").append("<option value='"+res[i].accid+"'>"+res[i].accname+"-"+res[i].accno+"-"+res[i].bankname+"</option>");
              	}
			}
		});
		$(document).on("change", "input[name='optdate']", function(){
			if($(this).is(":checked")){
				if($(this).attr("id")=="unaudit"){
					//var unauditeddates = 
					getunauditeddaterange("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
						if(res.length>0){
							var startdate = res[0].mindate;
							var enddate = res[0].maxdate;
							$("#dtfrom").val(startdate);
							$("#dtto").val(enddate);
							$(".datesection").find("*").hide();
							$(".datesection").show();
						}
					});
					
				}else if($(this).attr("id")=="finyr"){
					var today = new Date();
					var currentfinyear= getFinancialYear(today,$("#hidden_finyeartype").val());
					var form_data = new FormData();
					var companyid ='<?php echo $companyid; ?>';
					var subcompanyid ='<?php echo $subcompanyid; ?>';
					var projectid = '<?php echo $projectid; ?>';
					form_data.append("companyid",companyid);
					form_data.append("subcompanyid",subcompanyid);
					form_data.append("projectid",projectid);
					$.ajax({
		              	type: "POST",   
		              	url: "modal/allnotes_modal.php?action=getfinancialyear",
		              	data: form_data,
		              	contentType: false,
		              	processData: false,
		              	cache: false,
		              	dataType:'JSON',
		              	success: function(res)
		              	{
		                	//console.log(res);
		                  	if(res.length>0){
		                    	$("#financialyear").empty();
		                    	$("#financialyear").append("<option selected disabled value=''>Select Financial Year</option>");
		                    	for (var i = 0; i < res.length; i++) {
		                      		$("#financialyear").append("<option value='"+res[i].financialyear+"'>"+res[i].financialyear+"</option>");
		                    	}
		                  	}else{
		                  		$("#financialyear").empty();
		                    	$("#financialyear").append("<option disabled value=''>Select Financial Year</option>");
		                    	$("#financialyear").append("<option value='"+currentfinyear+"'>"+currentfinyear+"</option>");
		                  	}
							$(".datesection").find("*").hide();
							$(".finyearsection").show();
							$(".datesection").show();
							if($("input[name='optrep']").is(":checked")){
								$("#genreportdiv") .show();
						    }
		              	}
		          	});
				}
				else if($(this).attr("id")=="cusdt"){
					$(".datesection").find("*").show();
					$(".finyearsection").hide();
					$(".datesection").show();
					if($("input[name='optrep']").is(":checked")){
						$("#genreportdiv") .show();
				    }
				}
			}
		});
		$(document).on("change", "#financialyear", function(){
			var selectedval = $(this).val();
			var finyrarr = selectedval.split("-");
			var startyr = finyrarr[0];
			var endyr = finyrarr[1];
			$("#dtfrom").val(startyr+"-"+"04"+"-"+"01");
			$("#dtto").val(endyr+"-"+"03"+"-"+"31");
		});
		$(document).on("change", "input[name='optrep']", function(){
			if($(this).is(":checked")){
				if($(this).attr("id")=="selbnk"){
				    $(".accountsection").show();
				    if($("input[name='optdate']").is(":checked")){
						$("#genreportdiv") .show();
				    }
				}
				else if($(this).attr("id")=="allbnk"){
					$(".accountsection").hide();
					if($("input[name='optdate']").is(":checked")){
						$("#genreportdiv") .show();
				    }
				}
			}
		});
		$(document).on("click", "#genreport", function(){
			var accountid = "";
			if($("input[name='optrep']:checked").prop("id")=="selbnk"){
				accountid = $("#accountlist").val();
				$(".selbankname").text("Bank Account: "+$("#accountlist").find("option:selected").text());
			}
			else if($("input[name='optrep']:checked").prop("id")=="allbnk"){
				accountid = "all";
				$(".selbankname").text("Bank Account: All Bank Accounts");
			}
			
			$(".repname").text("Summary of Deposits");
			$(".repprname").text("Project Name: "+$("#hidden_projectname").val());
			var fname = "N4C_<?php echo $companyid; ?>_<?php echo $subcompanyid; ?>_<?php echo $projectid; ?>_"+accountid.toUpperCase()+"("+formatDate($("#dtfrom").val())+"_To_"+formatDate($("#dtto").val())+")";
			$(".repno").text("Name of Excel File: "+fname);
			$(this).data("filename",fname);
			$(".titledate").html('From Date '+formatDate($("#dtfrom").val())+' To '+formatDate($("#dtto").val()));
			getnot4summary_htmlviewcredit("<?php echo $rooturl; ?>","<?php echo $companyid; ?>","<?php echo $subcompanyid; ?>","<?php echo $projectid; ?>",accountid,$("#dtfrom").val(),$("#dtto").val(),"","not4data_tbl","edit",function(reslt){
					if(reslt.length>0){
			          	$("#not4data_tbl tbody").empty();
			          	$("#not4data_tbl tbody").html(reslt);
						$(".titlemain").html('Total Withdrawal: Rs '+$(".td_note4totalamount").text());
						var totalent=0;
						$("#not4data_tbl tbody tr").each(function(){
							if($(this).index()<$("#not4data_tbl tbody tr").length-1){
								totalent+=parseInt($(this).find("td.td_note4entries").text());
							}
						});
						$(".totalentries").text("Total number of Entries: "+ totalent);
						$(".sumtotalentries").text(totalent);
					}
				$("#reportdiv").show();

			});
		/*	getallbankaccounts('<?php echo $companyid; ?>','<?php echo $subcompanyid; ?>','<?php echo $projectid; ?>',function(res){
		        if(res.length>0){
		        	$("#hiddennot4data_tbl tbody").empty();
		        	$("#hiddenallnot4data_tbl tbody").empty();
		        	var j=0;
		          	for (var i = 0; i < res.length; i++) {	
		          		getnot4summary_htmlview_note4("<?php echo $rooturl; ?>","<?php echo $companyid; ?>","<?php echo $subcompanyid; ?>","<?php echo $projectid; ?>",res[i].accid,$("#dtfrom").val(),$("#dtto").val(),"save","hiddenallnot4data_tbl","edit",function(resp,reporttype){
	          				if(reporttype){
					            $("#hiddenallnot4data_tbl tbody").append(resp);
					        }
					        else{
					            $("#hiddenallnot4data_tbl tbody").empty();
					            //$("#titlemain").html('Total amount spent for the Project '+'<br/> From date '+fromdate+' To '+todate);
					            $("#hiddenallnot4data_tbl tbody").html(resp);
					        }
					        j++;
							if(j>=res.length){
								$("#savenote4bs").trigger("click");
							}
		          		});
		          	}
		   
		          	for (var i = 0; i < res.length; i++) {
		          		getnot4summary_htmlview("<?php echo $rooturl; ?>","<?php echo $companyid; ?>","<?php echo $subcompanyid; ?>","<?php echo $projectid; ?>",res[i].accid,$("#dtfrom").val(),$("#dtto").val(),"save","hiddennot4data_tbl","edit",function(reslt){
		          			if(reslt.length>0){
					          	$("#hiddennot4data_tbl tbody").append(reslt);
							}
							
		          		});
		          	}
		          	$("#reportdiv").show();
				}
			});*/
		});
	
		$(document).on('click', '#savenote4bs', function(){
      		savenot4bs(function(res){
      			savenot4(function(res){
      			});
      		});
      		
    	});
	});	

$(document).on("click","input[name='chkentry']",function(){
	if($("input[name='chkentry']:checked").length==0){
		$("#purposeeditsection").addClass("d-none");
	}
});

$(document).on("click","#editpurpose",function(){
	if($("input[name='chkentry']:checked").length>0){
		$("#purposeeditsection").removeClass("d-none");
	}
	else{
		alert("No entries selected!!\nFirst Select the enteries whose purpose you want to edit.");
	}
});

$(document).on("click","#replacepurpose",function(){
	if(!$("#newpurpose").val()){
		alert("Please Select the Purpose to Replace with.");
	}
	else{
	    var selectedsnos = "";
	    $("input[name='chkentry']:checked").each(function(){
	    	if(selectedsnos){
				selectedsnos = selectedsnos+","+$(this).closest("tr").data("sno");
	    	}
	    	else{
    	  		selectedsnos = $(this).closest("tr").data("sno");
    	  	}
	    });

		var form_data = new FormData();
	    form_data.append("selectedsnos", selectedsnos);
	    var categoryid = $("#newpurpose").find("option:selected").data("categoryid");
	    form_data.append("categoryid",categoryid);
	    var category = $("#newpurpose").find("option:selected").data("category");
	    form_data.append("category",category);
	    var subcategoryid = $("#newpurpose").find("option:selected").data("subcategoryid");
	    form_data.append("subcategoryid",subcategoryid);
	    var subcategory = $("#newpurpose").find("option:selected").data("subcategory");
	    form_data.append("subcategory",subcategory);
	    var providedcode = $("#newpurpose").find("option:selected").data("providedcode");
	    form_data.append("providedcode",providedcode);
	    var purpose = $("#newpurpose").find("option:selected").data("purpose");
	    form_data.append("purpose",purpose);
	    var purposecode = $("#newpurpose").val();
	    form_data.append("purposecode",purposecode);

	    $.ajax({
	        type: "POST",   
	        url: "modal/allnotes_modal.php?action=updatepurposecredit",
	        data: form_data,
	        contentType: false,
	        processData: false,
	        cache: false,
	        dataType:'JSON',
	        success: function(res)
	        {
	           if(res.status="Success"){
	           	alert(res.message);
	           	location.reload();
	           }
	           else{
	           	alert(res.message);
	           }
			}
	    });
	}
});

$(document).on("change","#faccountlist",function(){
	var selaccount = $(this).find("option:selected").text();
	if(selaccount="Show All"){
		$("#breakdownofpurposetbl tbody tr").show();
	}
	else{
		var accountdetails = selaccount.split("-");
		var accname = accountdetails[0];
		var accno = accountdetails[1];
		var accbank = accountdetails[2];
		$("#breakdownofpurposetbl tbody tr").hide();
		$("#breakdownofpurposetbl tbody tr").each(function(row,tr){
			if($(tr).find("td.distbankname_td").text()==accbank && $(tr).find("td.distaccountno_td").text()==accno){
				$(tr).show();
			}
		});
	}
});


function getallbankaccounts(companyid,subcompanyid,projectid,callback){
	var form_data = new FormData();
	form_data.append("companyid",companyid);
	form_data.append("subcompanyid",subcompanyid);
    form_data.append("projectid", projectid);
    $.ajax({
        type: "POST",   
        url: "modal/masters_modal.php?action=getaccountinfobyproject",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
           callback(res);
		}
    });
}
	function savenot4bs(callback) {
	  	var form_data = new FormData();
	  	var datefrom = $("#dtfrom").val();
	  	var dateto = $("#dtto").val();
	  	var companyid ='<?php echo $companyid; ?>';
		var subcompanyid ='<?php echo $subcompanyid; ?>';
		var projectid = '<?php echo $projectid; ?>';
	  	form_data.append("projectid", projectid);
	  	form_data.append("companyid", companyid);
	  	form_data.append("subcompanyid", subcompanyid);
	  	form_data.append("datefrom", datefrom);
	  	form_data.append("dateto", dateto);
	  	var rowCount = $('#hiddenallnot4data_tbl tbody tr').length;
	  	if (rowCount<2) {
	    	return false;
	  	}
	  	var TableData1;
	  	TableData1 = storeTblValues1('#hiddenallnot4data_tbl tbody');
	  	TableData1 = JSON.stringify(TableData1);
	  	function storeTblValues1(mytable)
	  	{
	    	var TableData_A1 = new Array();
	    	var mytableid = mytable + ' tr';
	    	$(mytableid).each(function(row, tr)
	    	{             
	      		TableData_A1[row]={
	      			"accountid": $(tr).find('td:eq(11)').text(), 
			        "purpose_category" : $(tr).find('td:eq(1)').text(),
			        "purpose_subcategory" : $(tr).find('td:eq(2)').text(),
			        "purpose_description" : $(tr).find('td:eq(3)').text(),
			        "purpose_code" : $(tr).find('td:eq(4)').text(),
			        "provided_code" : $(tr).find('td:eq(5)').text(),
			        "total_amount" : $(tr).find('td:eq(6)').text()
	      		}
	    	}); 
	    	return TableData_A1;
	  	}
	  	form_data.append('pTableData_A4BS', TableData1);
	  	$.ajax({
	    	type: "POST",   
	    	url: "modal/allnotes_modal.php?action=save_notes4BS",
	    	data: form_data,
	    	contentType: false,
	    	processData: false,
	    	cache: false,
	    	dataType:'JSON',
	    	success: function(res)
	    	{
	        	callback(res);
	    	}
	  	});
	}
	function savenot4(callback) {
	  	var form_data = new FormData();
	  	var datefrom = $("#dtfrom").val();
		var dateto = $("#dtto").val();
		var companyid ='<?php echo $companyid; ?>';
		var subcompanyid ='<?php echo $subcompanyid; ?>';
		var projectid = '<?php echo $projectid; ?>';
		form_data.append("projectid", projectid);
		form_data.append("companyid", companyid);
		form_data.append("subcompanyid", subcompanyid);
		form_data.append("datefrom", datefrom);
		form_data.append("dateto", dateto);
		var rowCount = $('#hiddennot4data_tbl tbody tr').length;
		if (rowCount<2) {
		  return false;
		}
		var TableData1;
		TableData1 = storeTblValues1('#hiddennot4data_tbl tbody');
		TableData1 = JSON.stringify(TableData1);
		function storeTblValues1(mytable)
		{
		  var TableData_A1 = new Array();
		  var mytableid = mytable + ' tr';
		  $(mytableid).each(function(row, tr)
		  {             
		    TableData_A1[row]={
		    	"accountid": $(tr).find('td:eq(11)').text(),
		      	"purpose_category" : $(tr).find('td:eq(1)').text(),
	      		"purpose_subcategory" : $(tr).find('td:eq(2)').text(),
		      	"purpose_description" : $(tr).find('td:eq(3)').text(),
		      	"purpose_code" : $(tr).find('td:eq(4)').text(),
		      	"provided_code" : $(tr).find('td:eq(5)').text(),
		      	"total_amount" : $(tr).find('td:eq(6)').text()
		    }
		  }); 
		  return TableData_A1;
		}
		form_data.append('pTableData_A4BS', TableData1);
		$.ajax({
		    type: "POST",   
		    url: "modal/allnotes_modal.php?action=save_notes4only",
		    data: form_data,
		    contentType: false,
		    processData: false,
		    cache: false,
		    dataType:'JSON',
		    success: function(res)
		    {
		      callback(res);
		    }
		});
	}
</script>
