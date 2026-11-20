<?php
require('header.php');
if($companyid!=""){
	$modalwin = isset($_GET["modal"])?"1":"";
	$new =  isset($_GET["new"])?"1":"";
?>
<div class="main">
	<div class="row">
	    <div class="col-md-12 mt-1 mb-2 pt-3 pb-3 text-center" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">
			<span class="uppercase  bold" style="font-size: 1.75rem;text-shadow:2px 2px gray;">Purpose Code Master (Deposits)</span>
	    </div>
	</div>
	<div class="row" id="menubuttons">
	    <div class="col-md-12 mt-1 mb-2  text-center">
			<button class="btn btn-success mb-0" id="import">Upload/Create New Purpose</button>
			<?php 
				if($loginroleid<3){
			?>
			<button class="btn btn-success mb-0" id="editpurpose">View/Edit Existing Purpose</button>
			<?php
				} 
			?>
			<a class="btn btn-deepblue mb-0 mx-2" href="./importpurpose.php?k=<?php echo $key; ?>">
				<h6 class="uppercase  my-1">Back To Purpose Index</h6>
			</a>
	    </div>
	</div>
	<div class="main-container bg-light" style="display:none;">
		<div class="row bg-light p-2 m-2" id="importfacility">
			<div class="col-md-12 text-center">
				<label  class="text-dark lbl-text mt-2">Import From Excel File</label>
				<input type="file" id="excelfile" accept=".xlsx,.xls"/>
			</div>	
		</div>

		<div class="p-2 m-2" id="sub-container"> 
			<div class="row bg-light p-2 m-2">
				<div class="col-md-12 text-center">
					<button class='btn btn-success mb-0' id='savepurpose' data-source='manual'>Save Purposes</button>
				</div>	
			</div>

			
			<div class="row bg-light p-2 mx-2" id="exportfacility">
				<div class="col-md-6 text-left">
					<!-- <button class="btn-sm" id="exportexcel"  onclick="exporttoexcel()" style="cursor:pointer;">Export To Excel</button> -->
					<button class="btn-sm btn-success"  onclick="gridtoexcel_withfilter_chunk('tblpurpose', 'Purpose Code Master (Deposits)', 'landscape',function(res){})" style="cursor:pointer;"> <i class="fa fa-download" aria-hidden="true"></i> Export To Excel</button>
				</div>
				<div id="duplicateTableContainer"></div>
				<div class="col-md-6 text-right">
					<!-- <button class="btn-sm btn-success" id="addrow">Add New Row</button>
					<button class="btn-sm btn-danger" id="delrow">Remove Row</button> -->
					<!-- <button class="btn-sm btn-success" onclick="openModalForAddNewPurpose('hidden_rooturl','','','','credit')" id="addpurposedeposit">Add New Purpose</button> -->

					<button class="btn-sm btn-deepblue" onclick="AddNewPurposeName('hidden_rooturl','9','','','credit')" id="addpurpose">Add New Purpose New Fucntion</button>
	    			<button class="btn-sm btn-deepblue" onclick="EditorPreviewPurposeName('hidden_rooturl','','','','credit')" id="addpurpose">Edit/Preview Purpose</button>

					<button class="btn-sm btn-success" onclick="openModalForReplaceNewPurposeWithOld('hidden_rooturl','','','','credit')" id="addpurpose">Replacement of Purpoe</button>
					<button class="btn-sm btn-success refreshpurpose d-none" id="refresspurpose">Refresh Purpose</button>
				</div>
				<div class="col-md-12 mt-2 text-danger font-weight-bold" id="duplicatemsg"></div>
			</div>
			<div class="row bg-light p-2 m-2">
				<div class="col-md-12 table-responsive" style="height:fit-content;overflow-y:auto;">
					<table id="tblpurpose" class="table table-bordered">
						<thead class='bg-info'>
							<tr>
								<th colspan="4">Purpose Code Master List for Deposits</th>
							</tr>
							<tr>
								<th colspan="4">Project: <?php echo $projectname; ?></th>
							</tr>
							<tr>
								<th class='sticky-top bg-info d-none noexport'>Select</th>
								<th class='sticky-top bg-info d-none'>S.No</th>
								<th class='sticky-top bg-info'>Provided Code</th>
								<th class='sticky-top bg-info'>Purpose</th>
								<th class='sticky-top bg-info'>Category</th>
								<th class='sticky-top bg-info'>Sub Category</th>
							</tr>
							</thead>
							<tbody class="new">
								<!-- <tr class="active">
									<td><input type='radio' name='selpurpose'/></td>
									<td>1</td>
									<td class="providedcode" contenteditable="true"></td>
									<td class="purposename" contenteditable="true"></td>
									<td><select class="selectcategories form-control select2"></select></td>
									<td><select class="selectsubcategories form-control select2 select2Mannual"></select></td>
								</tr> -->
							</tbody>
							<tbody class="exist">

							</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<div class="edit-container bg-light"  style="display:none;">
		<div class="row">
		    <div class="col-md-12 mt-1 mb-1 pt-2 pb-2 text-center">
				<span class="uppercase text-dark" style="font-size: 1.25rem;font-weight:700;"><u>Edit Purpose Code</u></span>
		    </div>
		</div>

		<div class="row bg-light p-2 m-2">
			<div class="col-md-12 table-responsive" style="height:60vh;overflow-y:auto;">
				<div class="pull-right mb-1"><button class="btn-sm" id="exportexcel"  onclick="gridtoexcel_withfilter_chunk('tblexistpurpose','Purpose Code Master (Withdrawals)','landscape');" style="cursor:pointer;">Export To Excel</button></div>
				<table id="tblexistpurpose" class="table table-bordered">
					<thead class='bg-info'>
						<tr>
							<th colspan="5">Editable Purpose Code Master List for Deposits</th>
						</tr>
						<tr>
							<th colspan="5">Project: <?php echo $projectname; ?></th>
						</tr>
						<tr>
							<th class='sticky-top bg-info d-none'>S.No</th>
							<th class='sticky-top bg-info'>Category</th>
							<th class='sticky-top bg-info'>Sub Category</th>
							<th class='sticky-top bg-info'>Purpose</th>
							<th class='sticky-top bg-info'>Provided Code</th>
							<th class='sticky-top bg-info noexport'>Action</th>
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
$(document).on("show.bs.modal", "#newpurposedetails_modal", function(){
	setTimeout(function(){
		$('.select2').select2();
		$('.select2-selection').css('border-radius','2px');
		$('.select2-container').children().css('border-radius','0px');
		$('.select2-selection').css('height','36px');
		$('.select2').select2({
	    	width: '100%' // need to override the changed default
		});
        $(".select2Mannual").select2({
			tags: true,
			width: '100%'
		});
    }, 300);
});
$(document).on("click", ".refreshpurpose", function(){
	getcategories(function(res){
		$(".selectcategories").html(res);
	});
	getpurposedata('<?php echo $companyid;?>', '<?php echo $subcompanyid;?>', '<?php echo $projectid;?>',function(res){
		var purposedata = res;
		var html="";
		// var i=res.length||0;
		var i=1;
		var excel="0";
		purposedata.forEach(function(item) {
			html+="<tr class='existing' data-sno='"+item.sno+"'><td class='d-none noexport'><input type='radio' name='selpurpose' disabled/></td><td class='d-none noexport'>"+i+"</td><td class='providedcode'>"+item.providedcode+"</td><td class='purposename' data-purposecode='"+item.purposecode+"' data-purposeid='"+item.purposeid+"'>"+item.purpose+"</td><td data-catid='"+item.categoryid+"'>"+item.category+"</td><td data-subcatid='"+item.subcategoryid+"'>"+item.subcategory+"</td></tr>";
			if(excel=="0"){
				if(item.source=="excel"){
					excel="1";
				}
			}
			i++;
		});
		$("#tblpurpose tbody.exist").html(html);
		i=parseInt($("#tblpurpose tbody.exist tr:first").find("td:eq(1)").text())||0+1;
		$("#tblpurpose tbody.new tr:first").find("td:eq(1)").text(i);
		$(".existing").css("background-color","lightgrey");
		if(excel=="1"){
			$("#tblpurpose").data("exceluploaded",true);
			$("#excelfile").prop("disabled",true);
			$("#excelfile").parent().find("label").prop("disabled",true);
		}
		else{
			$("#tblpurpose").data("exceluploaded",false);
			$("#excelfile").prop("disabled",false);
			$("#excelfile").parent().find("label").prop("disabled",false);
		}

	});
    $("#sub-container").show();
	$(".edit-container").hide();
	$(".main-container").show();
});

var modalwin = "<?php echo $modalwin; ?>";
var newwin = "<?php echo $new; ?>";
if(modalwin){
    $(".navbar").hide();
    $(".companyheading").hide();
}
  $(document).ready(function(){
  	if(newwin){
	  	$("#import").trigger("click");
	  	$("#menubuttons").hide();
	  	$("#importfacility").hide();
	  	$("#exportfacility").hide();
  	}
  });
  
$(document).on("click", "#import", function(){
	getcategories(function(res){
		$(".selectcategories").html(res);
	});
	getpurposedata('<?php echo $companyid;?>', '<?php echo $subcompanyid;?>', '<?php echo $projectid;?>',function(res){
		var purposedata = res;
		var html="";
		// var i=res.length||0;
		var i=1;
		var excel="0";
		purposedata.forEach(function(item) {
			html+="<tr class='existing' data-sno='"+item.sno+"'><td class='d-none noexport'><input type='radio' name='selpurpose' disabled/></td><td class='d-none noexport'>"+i+"</td><td class='providedcode'>"+item.providedcode+"</td><td class='purposename' data-purposecode='"+item.purposecode+"' data-purposeid='"+item.purposeid+"'>"+item.purpose+"</td><td data-catid='"+item.categoryid+"'>"+item.category+"</td><td data-subcatid='"+item.subcategoryid+"'>"+item.subcategory+"</td></tr>";
			if(excel=="0"){
				if(item.source=="excel"){
					excel="1";
				}
			}
			i++;
		});
		$("#tblpurpose tbody.exist").html(html);
		i=parseInt($("#tblpurpose tbody.exist tr:first").find("td:eq(1)").text())||0+1;
		$("#tblpurpose tbody.new tr:first").find("td:eq(1)").text(i);
		$(".existing").css("background-color","lightgrey");
		if(excel=="1"){
			$("#tblpurpose").data("exceluploaded",true);
			$("#excelfile").prop("disabled",true);
			$("#excelfile").parent().find("label").prop("disabled",true);
		}
		else{
			$("#tblpurpose").data("exceluploaded",false);
			$("#excelfile").prop("disabled",false);
			$("#excelfile").parent().find("label").prop("disabled",false);
		}

	});
    $("#sub-container").show();
	$(".edit-container").hide();
	$(".main-container").show();
});

$(document).on("click", "#addrow", function(){
	var duplicates = checkforduplicates();

	if(duplicates.Status=="Success"){
		if($('.active').find("td:eq(4)").find("select").find(":selected").text().trim() && ($('.active').find("td:eq(5)").find("select").find(":selected").text().trim()||$('.active').find("td:eq(5)").text()) && $('.active').find("td:eq(3)").text().trim() && $('.active').find("td:eq(2)").text().trim()){
			$('#tblpurpose tbody.new tr').removeClass('active');
			var rows = $("#tblpurpose tr").length -1;
			$("#tblpurpose tbody.new").prepend("<tr class='active'><td><input type='radio' name='selpurpose'/></td><td class='d-none'>"+(rows+1)+"</td><td class='providedcode' contenteditable='true'></td><td class='purposename' contenteditable='true'></td><td><select class='selectcategories form-control select2'></select></td><td><select class='selectsubcategories form-control select2 select2Mannual'></select></td></tr>");
				$(".select2Mannual").select2({
					tags: true,
					width: '100%'
				});
			getcategories(function(res){
				$(".selectcategories:first").html(res);
			});
		}
		else{
			alert("Please fill all the Fields of the last row before inserting a new row.");
		}
	}
	else{
		alert(duplicates.Message);
		$("#tblpurpose tbody.new tr.active").find("td:eq("+duplicates.focus+")").focus();
	}
});

$(document).on("click", "#delrow", function(){
	$("#tblpurpose tbody.new tr.active").remove();
	if($("#tblpurpose tbody.new tr").length==0){
		var rows = $("#tblpurpose tr").length -1;
			$("#tblpurpose tbody.new").append("<tr class='active'><td><input type='radio' name='selpurpose'/></td><td class='d-none'>"+(rows+1)+"</td><td class='providedcode' contenteditable='true'></td><td class='purposename' contenteditable='true'></td><td><select class='selectcategories form-control select2'></select></td><td><select class='selectsubcategories form-control select2 select2Mannual'></select></td></tr>");
			getcategories(function(res){
				$(".selectcategories:last").html(res);
			});
	}
	else{
	$("#tblpurpose tbody.new tr:last").addClass("active");
	}
});

$(document).on("blur", ".providedcode", function(){
	if($(this).text()){
		var duplicates = checkforduplicates();
		if(duplicates.Status=="Error"){
			$("#duplicatemsg").text(duplicates.Message);
			$(this).css("background-color","red");
		}
		else{
			$("#duplicatemsg").text('');
			$(this).css("background-color","#fff");
			var providedcode=parseInt($(this).text());
			var retvalue = findcategoryidfromprovidedcode(providedcode);
			$(this).closest("tr").find("td:eq(4)").find(".selectcategories").val(retvalue).trigger("change");
			$(this).closest("tr").find("td:eq(5)").find(".selectsubcategories").data("oldval",providedcode);
			$(this).closest("tr").find("td:eq(5)").find(".selectsubcategories").val(providedcode).trigger("change");
		}
	}
	else{
		$("#duplicatemsg").text('');
		$(this).css("background-color","#fff");

	}
});

$(document).on("blur", ".purposename", function(){
	if($(this).text()){
		var duplicates = checkforduplicates();
		if(duplicates.Status=="Error"){
			$("#duplicatemsg").text(duplicates.Message);
			$(this).css("background-color","red");
		}
		else{
			$("#duplicatemsg").text('');
			$(this).css("background-color","#fff");
		}
	}
	else{
		$("#duplicatemsg").text('');
		$(this).css("background-color","#fff");
	}
});

$(document).on("click", "#editpurpose", function(){
	getpurposedata('<?php echo $companyid;?>', '<?php echo $subcompanyid;?>', '<?php echo $projectid;?>',function(res){
		var purposedata = res;
		var html = fillsaveddata(purposedata);
		$("#tblexistpurpose tbody").html(html);
	});
	
	$(".main-container").hide();
	$(".edit-container").show();
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

            $.ajax({
                url: 'modal/showanduploadpurpose.php?action=show',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response){
                    	$("#exportexcel").hide();
                    	$("#addrow").hide();
                    	if(response.includes("Error")){
                    		// alert(response);
                    		SweetAlertmessageFadeinwithoutrefresh("error", response);
                    	}
                    	else{
                    		$("#savepurpose").data("source","excel");
 							$("#tblpurpose tbody.new").html(response);
 							getcategories(function(res){
								$(".selectcategories").html(res);
							});
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

$(document).on("click","#tblpurpose tbody.new tr",function(){
	$('#tblpurpose tbody tr').removeClass('active');
	$(this).addClass('active');
	$(this).find('td:eq(0)').find('input').prop('checked',true);
});

$(document).on("click","input[name='selpurpose']",function(){
	$('#tblpurpose tbody.new tr').removeClass('active');
	if($(this).is(":checked")){
		$(this).closest('tr').addClass('active');
	}
});

$(document).on("click","#savepurpose",function(){
	if($(this).data("source")=="manual"){
		var duplicates = checkforduplicates();
		if(duplicates.Status!="Success"){
			$("#duplicatemsg").text(duplicates.Message);
			$("#tblpurpose tbody.new tr.active").find("td:eq("+duplicates.focus+")").css("background-color","red");
			return false;
		}
	}
	var purposedata = new Array();
	var i=0;
	$("#tblpurpose tbody.new tr").each(function(row,tr){
		if($(tr).find("td:eq(4)").find("select").find(":selected").text().trim() && $(tr).find("td:eq(4)").find("select").val() && ($(tr).find("td:eq(5)").find("select").find(":selected").text().trim()||$(tr).find("td:eq(5)").text()) && $(tr).find("td:eq(3)").text().trim() && $(tr).find("td:eq(2)").text().trim()){
			purposedata[i]={
				"companyid":"<?php echo $companyid;?>",
				"subcompanyid":"<?php echo $subcompanyid;?>",
				"projectid":"<?php echo $projectid;?>",
				"categoryid":$(tr).find("td:eq(4)").find("select").val(),
				"category":$(tr).find("td:eq(4)").find("select").find(":selected").text(),
				"subcategoryid":($(tr).find("td:eq(5)").find("select").length>0?$(tr).find("td:eq(5)").find("select").val():$(tr).find("td:eq(5)").data("subcategoryid")),
				"subcategory":($(tr).find("td:eq(5)").find("select").length>0?$(tr).find("td:eq(5)").find("select").find(":selected").text():$(tr).find("td:eq(5)").text()),
				"purpose":$(tr).find("td:eq(3)").text(),
				"providedcode":$(tr).find("td:eq(2)").text(),
				"source":$("#savepurpose").data("source")
			};
			i++;
		}
	});
	 // console.log(purposedata);
	 //return false;
	if(purposedata.length>0){
		var formData = new FormData();
		formData.append("purposedata",JSON.stringify(purposedata));
		$.ajax({
	        url: 'modal/showanduploadpurpose.php?action=savepurpose&type=credit',
	        type: 'POST',
	        data: formData,
	        processData: false,
	        contentType: false,
	        success: function(response) {
	            if(response){
	            	alert("Saved Successfully");
	            	location.reload();
	            }
	            else{
	            	alert("Save Failed");
	            }
	        }
	    });
	}
	else{
		alert("Category of the purpose not defined.\nPlease define the Category of the purpose first and then proceed to save.");
	}
});

$(document).on("change",".selectcategories",function(){
	var catid =$(this).val();
	if($(".selectsubcategories").length>0){
		var companyid="<?php echo $companyid;?>";
		var subcompanyid="<?php echo $subcompanyid;?>";
		var projectid="<?php echo $projectid;?>";
		getsubcategories(companyid,subcompanyid,projectid,catid,parseInt($(this).closest("tr").find("td:eq(2)").text()),function(res){
			$(".active").find(".selectsubcategories").html(res);
		});
	}
	else{
		var activegroup = $(this).closest("tr").prop("class").split(' ')[0];
		$("."+activegroup).each(function(){
			$(this).closest("tr").find("td:eq(4)").find("select").val(catid);
		});
	}
});



$(document).on("change",".selectsubcategories",function(){
	var subcat =$(this).find(":selected").text();
	if(subcat){
		var providedcode = parseInt($(this).closest("tr").find("td:eq(2)").text());
		if(!subcat.startsWith(providedcode)){
			alert("Please choose 'Sub-Category' corresponding to the 'Provided Code' or change the 'Provided Code'.");
			$(this).val($(this).data("oldval")).trigger("change");
		}
		/*else{
			$(this).data("oldval",$(this).val());
		}*/
	}
});

$(document).on("click", ".edit_pur", function(){ 
	var Epurposecode = $(this).closest('tr').find('td:eq(3)').data("purposecode");
   	var Epurpose = $(this).closest('tr').find('td:eq(3)').text();
   	var Epurposecategory = $(this).closest('tr').find('td:eq(1)').text();
   	var Epurposesubcategory = $(this).closest('tr').find('td:eq(2)').text();
   	var Ecatid = $(this).closest('tr').find('td:eq(1)').data("catid");
   	var Esubcatid = $(this).closest('tr').find('td:eq(2)').data("subcatid");
   	var EcodeX = $(this).closest('tr').find('td:eq(4)').text();

   	var Eamount = "";
  	var accountid = "";
	var projectid = '<?php echo $projectid; ?>';

  	if (Epurposecategory=='Unknown' && Epurposesubcategory=='Unknown' && Epurpose=='Unknown') {
  	  alert('All category are unknown so we can not edit all group of these unknown entries. If you want to edit the entry please go with single entry.');
  	  return false;
  	}
  	getallcreditpurposewithprojectidlist("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
      	//console.log(res);
      	$("#cmbpurpose").empty();
  		
        if(res.length>0){
        	var selected="";
			for (var i = 0; i < res.length; i++) {
				if(res[i].purpose==Epurpose){
					selected="selected";
					$(".clpurpose").data("Epurid",res[i].purposeid);
					$(".clpurposenew").data("Epurid",res[i].purposeid);
				}
				else{
					selected="";
				}
			  $("#cmbpurpose").append("<option value='"+res[i].purposeid+"' "+selected+">"+res[i].purpose+"</option>");
			}

			if($("#cmbpurpose").find(":selected").length<=0){
				$("#cmbpurpose").append("<option value='' selected>Select Purpose</option>");
		    }
		    else{
		    	$("#cmbpurpose").prepend("<option value=''>Select Purpose</option>");
		    }
        }
    });

	getallcreditpurposesubcategorywithcategoryidlist("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
      	//console.log(res);
      	$("#cmbsubcategort").empty();
  		var selected="";
        if(res.length>0){
			for (var i = 0; i < res.length; i++) {
				if(res[i].psub_category==Epurposesubcategory){
					selected="selected";
				}
				else{
					selected="";
				}
			  $("#cmbsubcategort").append("<option value='"+res[i].psub_categoryid+"' "+selected+">"+res[i].psub_category+"</option>");
			}
			 if($("#cmbsubcategort").find(":selected").length<=0){
				$("#cmbsubcategort").append("<option value='' selected>Select Sub-Category</option>");
		    }
		    else{
		    	$("#cmbsubcategort").prepend("<option value=''>Select Sub-Category</option>");
		    }
        }
    });

    getallcreditpurposecategorywithcategoryidlist("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",function(res){
      	//console.log(res);
      	$("#cmbcategort").empty();
  		
        if(res.length>0){
			for (var i = 0; i < res.length; i++) {
				if(res[i].pcategory==Epurposecategory){
					selected="selected";
				}
				else{
					selected="";
				}
			  $("#cmbcategort").append("<option value='"+res[i].pcategorycode+"' "+selected+">"+res[i].pcategory+"</option>");
			}
			 if($("#cmbcategort").find(":selected").length<=0){
				$("#cmbcategort").append("<option value='' selected>Select Category</option>");
		    }
		    else{
				$("#cmbcategort").prepend("<option value=''>Select Category</option>");
		    }
        }
    });
   

	$('.clcat').text(Epurposecategory);
	$('.clcat').data("Ecatid",Ecatid);
	$('.clsubcat').text(Epurposesubcategory);
	$('.clsubcat').data("Esubcatid",Esubcatid);
	$('.clpurpose').text(Epurpose);
	
	$('.clpurposecode').text(Epurposecode);
	$('.clpcod').text(EcodeX);
	$('.clamount').text(Eamount);
	if($(".clamount").text()==""){
  		$(".clamount").hide();
  		$(".clamounthead").hide();
	}

	$('.clcatnew').text(Epurposecategory);
	$('.clcatnew').data("Ecatid",Ecatid);
	$('.clsubcatnew').text(Epurposesubcategory);
	$('.clsubcatnew').data("Esubcatid",Esubcatid);
	$('.clpurposenew').text(Epurpose);

	$('.clpurposecodenew').text(Epurposecode);
	$('.clpcodnew').text(EcodeX);
	$('#textnewprovidedcode').val('');
	$('input[name="spelling"]').prop("disabled",true);
  	$('#editpurposedetails').modal("show");
  	
  	$('#editsno').hide();
  	$('#edit').show();     
});

function exporttoexcel(){
	var cloneTable = $('#tblpurpose').clone();
	cloneTable.attr('id', 'tblpurposeexl');
    cloneTable.find('tbody.new').remove(); 
    cloneTable.find('thead th:first-child').remove();
    
    cloneTable.find('tbody tr').each(function() {
        $(this).find('td:first-child').remove();
    });
    var tbody = cloneTable.find('tbody');

    tbody.each(function() {
        var rows = $(this).find('tr').get();

        // Sort rows based on the value in the first column (first <td>)
        rows.sort(function(a, b) {
            var keyA = $(a).children('td').eq(0).text();
            var keyB = $(b).children('td').eq(0).text();

            // Convert text to numbers for sorting
            return parseInt(keyA) - parseInt(keyB);
        });

        // Append the sorted rows back to the table body
        $.each(rows, function(index, row) {
            $(tbody).append(row);
        });
    });
    cloneTable.find('tbody tr').each(function() {
        // Get the current value in the second column
        var secondColValue = $(this).children('td').eq(1).text();
        
        // Convert the text to a floating point number using parseFloat
        var floatValue = parseFloat(secondColValue);

        // Update the second column with the parsed float value
        $(this).children('td').eq(1).text(floatValue);
    });
    $('#duplicateTableContainer').show();
    $('#duplicateTableContainer').html(cloneTable);
	gridtoexcel_withfilter_chunk('tblpurposeexl','Purpose Code Master (Withdrawals)','landscape',function(res){
		if(res){
			$('#duplicateTableContainer').hide();
		}
	});
}

function findcategoryidfromprovidedcode(providedcode){
	var catid = "";
	$("#tblpurpose tbody.exist tr").each(function(row,tr){
		if($(this).find("td:eq(5)").text().startsWith(providedcode) && catid==""){
			catid = $(this).find("td:eq(4)").data("catid");
		}
	});
	return catid;
}

function checkforduplicates(){
	var newpurpose = $("#tblpurpose tbody.new tr.active").find("td:eq(3)").text();
	var newprovidedcode = $("#tblpurpose tbody.new tr.active").find("td:eq(2)").text();
	var duplicate = {"Status":"Success","Message":""};
	$("#tblpurpose tbody.exist tr").each(function(row,tr){
		if(!$(tr).hasClass("active") && duplicate.Status=="Success"){
			if($(tr).find("td:eq(3)").text().toLowerCase().trim()==newpurpose.toLowerCase().trim()) {
				duplicate = {"Status":"Error","Message":"Purpose: "+newpurpose+" found to be duplicate with row "+$(tr).find("td:eq(1)").text()+".\n\nDetails of row "+$(tr).find("td:eq(3)").text()+":\nPurpose: "+$(tr).find("td:eq(3)").text()+"\nProvided Code: "+$(tr).find("td:eq(2)").text()+"\n\nPlease check.","focus": "3"};
			}
			else if($(tr).find("td:eq(2)").text().toLowerCase().trim()==newprovidedcode.toLowerCase().trim()){
				duplicate = {"Status":"Error","Message":"Provided Code: "+newprovidedcode+" found to be duplicate with row "+$(tr).find("td:eq(1)").text()+".\n\nDetails of row "+$(tr).find("td:eq(3)").text()+":\nPurpose: "+$(tr).find("td:eq(3)").text()+"\nProvided Code: "+$(tr).find("td:eq(2)").text()+"\n\nPlease check.","focus": "2"};
			}
		}
	});
	return duplicate;
}

function getsubcategoryidfrommaster(catid,subcatvalue,callback){
	var formData = new FormData();
	formData.append("catid",catid);
	formData.append("subcatvalue",subcatvalue);
	$.ajax({
        url: 'modal/showanduploadpurpose.php?action=getsubcatid&type=credit',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            callback(response);
        }
    });
}

function getcategories(callback){
	$.ajax({
	    url: 'modal/showanduploadpurpose.php?action=categories&type=credit',
	    processData: false,
	    contentType: false,
	    success: function(response) {
	        callback(response);
	    }
	});
}

function getsubcategories(companyid,subcompanyid,projectid,catid,providedcode=null,callback){
	var formData = new FormData();
	formData.append("companyid",companyid);
	formData.append("subcompanyid",subcompanyid);
	formData.append("projectid",projectid);
	formData.append("catid",catid);
	if(providedcode){
		formData.append("providedcode",providedcode);
	}
	$.ajax({
	    url: 'modal/showanduploadpurpose.php?action=subcategories&type=credit',
	    type: 'POST',
        data: formData,
	    processData: false,
	    contentType: false,
	    success: function(response) {
	        callback(response);
	    }
	});
}

function getpurposedata(companyid,subcompanyid,projectid,callback){
	var formData = new FormData();
	formData.append("companyid",companyid);
	formData.append("subcompanyid",subcompanyid);
	formData.append("projectid",projectid);
	$.ajax({
	    url: 'modal/showanduploadpurpose.php?action=purpose&type=credit',
	    type: 'POST',
	    data: formData,
	    processData: false,
	    contentType: false,
	    dataType: "json",
	    success: function(response) {
	        callback(response);
	    }
	});
}

function fillsaveddata(purposecodes){
	var html="";
	var i=1;
	purposecodes.forEach(function(item) {
		html+="<tr data-sno='"+item.sno+"'><td class='d-none'>"+i+"</td><td data-catid='"+item.categoryid+"'>"+item.category+"</td><td data-subcatid='"+item.subcategoryid+"'>"+item.subcategory+"</td><td data-purposecode='"+item.purposecode+"' data-purposeid='"+item.purposeid+"'>"+item.purpose+"</td><td>"+item.providedcode+"</td><td style='text-align:left'><input type='button' class='btn-sm btn-info mb-0 edit_pur' value='Edit'></td></tr>";
		i++;
	});
	return html;
}
</script>