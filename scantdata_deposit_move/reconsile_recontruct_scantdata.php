<?php

 //modiifed by ajay 03-06-2025 add back btn
	date_default_timezone_set("Asia/Calcutta");
	require('../header.php');
	if(isset($_GET['subaction'])){
		$reconcilation=$_GET['subaction']; 
	}     
?> 
<div class="main-container">
	<div class="col-md-12 mt-1 text-center"  >
		<span class="uppercase  bold" style="font-size: 2rem;">3. R1:Create Deposit Side Book With Scant Data</span>
	</div>
</div>

 
<div class="bg-warning p-1" id="maindiv">	
	<div class="row mt-2 justify-content-center">
		<div class ="col-md-3" > 
			<input type="text" id='srchmain' oninput="filterTable('#srchmain', '#tblcreditadvice');" class="form-control" placeholder="Type for Searching ......">
		</div> 
		<div class="col-md-3 text-center">
			<button class="btn btn-info recon" id="auto_recon_cond" style="font-size: large;">Step -1: Automatic Reconciliation</button>
		</div> 	
		<div class="col-md-3 text-center">
			<!-- <button class="btn btn-light recon" style="font-size: large;" onclick="gridtoexcel_withfilter_chunk('tblcreditadvice','Pending Reconcile Credit Advice(Structured)','landscape',function(){});">Export Grid To Excel</button> -->
		</div> 	

        <div class="col-md-3 mt-2 text-center">
            <button class="btn btn-success"  style="font-size: large;" onclick="window.location.href='<?php echo $prefix; ?>admin_dashboard.php?k=<?php echo $_GET['k']; ?>'">Back</button>
        </div> 
	</div>	
</div> 
<div id="div_creditadvice">

	<div class="table-responsive" style="background-color:white;max-height:35vh; overflow-x: auto;">
		<table id="tblcreditadvice" class="table table-bordered" style="font-size: 16px !important;">
			<thead class="bg-info sticky-top align-middle">
                <tr style="display:none; font-weight: bold;font-size: 24px;">
                    <th colspan="12">Report No: REC_CR:1 Pending Report For Reconsile Credit Advice (Structured)</th>
                </tr>  
               <tr style="display: none;font-weight: bold;font-size: 22px;">
				    <th colspan="12" id="title1">Company Name</th>
				</tr> 
               <tr style="display: none;font-weight: bold;font-size: 22px;">
				    <th colspan="12" id="title2">Project Name</th>
				</tr> 
               <tr style="display: none;font-weight: bold;font-size: 18px;">
				    <th colspan="12" id="title4">Generated Date: <?php echo date('d-m-Y h:i A'); ?></th>
				</tr>				 
				<tr> 

					<th class="bg-info">Select</th>
					<th class="bg-info">Transaction Date</th>
					<th class="bg-info">Transaction No</th>
					<th class="bg-info">Depositor Name</th>
					<th class="bg-info">Deposit Amount</th>
					<th class="bg-info">Purpose</th>
					<th class="bg-info">Description</th>
					<th class="bg-info">Voice File</th>
					<th class="bg-info">Document Path</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>

</div>

<h5 class ="" style="text-align: left;font-weight: bold;margin-top: 10px;"> Bank Statement </h5>
<div id="div_bank">
	<div class="table-responsive" style="background-color:white;max-height:500px; overflow-x: auto;">
		<table id="tblbank" class="table table-bordered" style="font-size: 16px !important;">
			<thead class="bg-info sticky-top align-middle">	
				<tr>
					  <th colspan="5" class="noexport"><input type="text" id='srchshort' oninput="filterTable('#srchshort', '#tblbank');" class="form-control" placeholder="Type for Searching ......"></th>

					<th colspan ='4'><button class="btn btn-warning recon" id="cred_adv_reconcile"  >Click to Reconcile</button> </th> 
				</tr>			 
				<tr>
					<th class="bg-info sticky-top align-middle">S.No</th>
					<th class="bg-info sticky-top align-middle">#</th> 
					<th class="bg-info sticky-top align-middle">Date</th>
					<th class="bg-info sticky-top align-middle">Amount</th>
					<th class="bg-info sticky-top align-middle">Description</th>
					<th class="bg-info sticky-top align-middle">Cheque No</th>
					<th class="bg-info sticky-top align-middle">Bank Name</th>
					<th class="bg-info sticky-top align-middle sortable" >Account Name</th>
					<th class="bg-info sticky-top align-middle">Account No</th>

					<th style="display: none;">Bank id</th>
					<th style="display: none;">Account id</th>
					<th style="display: none;">sno</th>
					<th style="display: none;">Bank Sno</th>  
					<th style="display: none;">Actual date </th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
</div>

<div class="modal" id="confirm_modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Confirm?</h5>
            </div>
            <div class="modal-body">
                <div class="col-md-12 mb-3 confirm_modal_message"></div>
                <div class="col-md-12 text-center">
                    <button class="confirm_modal_btnok btn btn-deepblue"></button>
                    <button class="confirm_modal_btncancel btn btn-deepblue"></button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
	require('../footer.php'); 
?>
<script type="text/javascript">
var companyid = <?=$companyid;?>;
var subcompanyid = <?=$subcompanyid;?>;
var projectid = <?=$projectid;?>;
var  loginid = "<?=$loginid;?>";
$("#title1").text( "Company Name :<?php echo $companyname; ?>" );
$("#title2").text( "Project Name :<?php echo $projectname; ?>" ); 
$("#title4").text("Generated Date: <?php echo date('d-m-Y h:i A'); ?>" );
 
$(document).ready(function(){ 
		getadvicedata(function(res){}); 	 
	$("#manual_recon_cond").on("click",function(){ 
		getadvicedata(function(res){
		}); 
	})
	$("#auto_recon_cond").on("click",function(){ 
		var form_data = new FormData();	 
		form_data.append('companyid', companyid);
		form_data.append('subcompanyid', subcompanyid);
		form_data.append('projectid', projectid); 
		form_data.append('transactiontype',"deposit");  
		form_data.append('loginid',loginid);
		$.ajax({
			type: "POST",	
			url: "modal/save_scantdata.php?action=automatic_scantdata",
			data: form_data,
		    contentType: false,
		    processData: false,
		    cache: false,
		 //   dataType:'JSON',
			success: function(res){
            	$(".waitloaderfooter").modal("hide"); 
            	alert(res)	;
			 	getadvicedata(function(res){}); 
			},beforeSend(res){
            	$(".waitloaderfooter").modal("show"); 
        	}						
		});
		  
	});
	$(".confirm_modal_btnok").on("click",function(){		
		reconsilesave();
	});
	$("#cred_adv_reconcile").on("click",function(){
	 
		if ($("input[name='creditdataradio']:checked").length <= 0) {
		    
			alert("You have not selected any data from Scant Data /Advice");
			return false;
		} 
		if ($("input[name='bankdataradio']:checked").length <= 0) {
		    
			alert("You have not selected any data from bank Statement");
			return false;
		}
		var adviceamount=$("#tblcreditadvice").data("adviceamount")||0;
		var amount= $("#tblbank").data("amount")||0;
		message="Credit Advice Amount is: "+ adviceamount +"\nDeposit In Banks is :"+amount+"\nAre you Sure You Want Reconcile it." ;
		showConfirmModal(message,"Reconcile","Cancel" );
		$("#confirm_modal").modal("show");
		return false;
	}); 
 
 jQuery(document).delegate(".creditdataradio", "change", function () {
    $('#srchshort').val('');
    $("#tblbank tbody").empty();
    $("#tblbank").removeData();
    $("#tblcreditadvice").removeData();
    var $row = $(this).closest("tr");  

    if ($(this).is(":checked")) { 
        $(".creditdataradio").not(this).prop("checked", false);
        $("#tblcreditadvice tbody tr").addClass("hide").removeClass("show"); 
        $(this).closest("tr").removeClass("hide").addClass("show");

        // Get bankref and deposit amount from this row
        var transactionDate = $row.data("transactiondate");
        var dbsno           = $row.data("dbsno");
        var id              = $row.data("id");
        var accountId       = $row.data("accountid");
        var purposeCode     = $row.data("purposecode");
        var clientId        = $row.data("clientid");  


		var bankref = $row.find(".bankdescription").text().trim();
		var transactionno = $row.find(".transactionno").text().trim();
		var adviceAmt = $row.find(".depositamt").text();

        $("#tblcreditadvice").data("adviceamount", adviceAmt);
        $("#tblcreditadvice").data("dbsno", dbsno);
        $("#tblcreditadvice").data("id", id);
        $("#tblcreditadvice").data("account_id", accountId);

        getbankdata(accountId,bankref,transactionno);
    } else {
        // Show all rows again when unchecked
        $("#tblcreditadvice tbody tr").removeClass("hide").addClass("show");
    }
});


	jQuery(document).delegate(".bankdataradio", "change", function () {
	    var current = $(this); 
	    $(".bankdataradio").prop("checked", false); 
	    current.prop("checked", true);
	    $("#tblbank").removeData();
	    if (current.is(":checked")) {     
	        $("#tblbank").data("amount", current.closest("tr").find("td:eq(3)").text());  
	        $("#tblbank").data("bank_sno", current.closest("tr").find("td:eq(12)").text());  
	    }  
	});
});
function getadvicedata(callback){
    $("#tblcreditadvice tbody").empty();
	var form_data = new FormData();	 
	form_data.append('companyid', companyid);
	form_data.append('subcompanyid', subcompanyid);
	form_data.append('projectid', projectid); 
	routurl= $("#hidden_rooturl").val();
	$.ajax({
		type: "POST",	
		url: "modal/reconcile_query.php?action=creditadvice_data_scantdata",
		data: form_data,
	    contentType: false,
	    processData: false,
	    cache: false,
	    dataType:'JSON',
		success: function(res){
			$(".waitloaderfooter").modal("hide"); 				 
			$("#tblcreditadvice tbody").html(res);				
		    if (res.length > 0) { 
		        let httrow = "";
		        for (var i = 0; i < res.length; i++) {
		          var voicePath ="N/A";
		          if(res[i].voicefilepath){
		            voicePath = "<button type='button' onclick='playVoiceFile(\"" + routurl + res[i].voicefilepath + "\")' class='btn btn-sm btn-primary'>🔊</button>";
		          }
		          var filename="NA";
		           if(res[i].filename){
		            filename = "<button type='button' onclick='showPreview_document(\"" + routurl + res[i].filename + "\")' class='btn btn-sm btn-primary'>📄</button>";
		          }
		          httrow += `

		            <tr data-transactiondate='${res[i].transactiondate}' data-dbsno='${res[i].sno}' data-id='${res[i].id}'  data-accountid='${res[i].account_id}' data-purposecode='${res[i].purposecode}'data-clientid='${res[i].clientid}' data-voicepath='${res[i].voicepath}'  data-docpath='${res[i].filename}' >
		              <td> <input type='checkbox' name='creditdataradio' class='creditdataradio' /></td>
		              <td>${res[i].displaydate}</td>
		              <td class="transactionno">${res[i].transactionno}</td>
		              <td class="clientname">${res[i].clientname}</td>
		              <td class="depositamt">${res[i].amount}</td>
		              <td class="purpose">${res[i].purpose}</td>
		              <td class="bankdescription">${res[i].bankdesciption}</td>
		              <td style='text-align:center;'>${voicePath}</td>
		              <td style='text-align:center;'>${filename}</td>
		            </tr>
		          `;
		        }
		        if (httrow) {
		          $("#tblcreditadvice tbody").html(httrow);
		        }
		    }
		},beforeSend(res){
            $(".waitloaderfooter").modal("show"); 
        }					
	});
}
function getbankdata(account_id,bankref,transactionno ){
    $("#tblbank tbody").empty();
	var form_data = new FormData();	 
	form_data.append('companyid', companyid);
	form_data.append('subcompanyid', subcompanyid);
	form_data.append('projectid', projectid);   
	form_data.append('account_id', account_id);   
	form_data.append('bankref', bankref);   
	form_data.append('transactionno', transactionno);
	$.ajax({
		type: "POST",	
		url: "modal/reconcile_query.php?action=bankdata_data",
		data: form_data,
	    contentType: false,
	    processData: false,
	    cache: false,
	 //   dataType:'JSON',
		success: function(res){
			$(".waitloaderfooter").modal("hide"); 				 
			$("#tblbank tbody").html(res);
			 						
		},beforeSend(res){
            $(".waitloaderfooter").modal("show"); 
        }						
	});
}
function showConfirmModal(message,buttonok,buttoncancel="") {
    return new Promise((resolve) => {
        // Set modal content
        $(".confirm_modal_message").html(message);
        $(".confirm_modal_btnok").html(buttonok);
        if(buttoncancel){
            $(".confirm_modal_btncancel").html(buttoncancel);
            $(".confirm_modal_btncancel").show();
        }else{
            $(".confirm_modal_btncancel").hide();
        }
        // Set up button click events
        $(document).one("click", ".confirm_modal_btnok", function () {
            $("#confirm_modal").data("response", $(this).text());
            $("#confirm_modal").modal("hide");
            resolve($(this).text());
        });

        $(document).one("click", ".confirm_modal_btncancel", function () {
            $("#confirm_modal").data("response", $(this).text());
            $("#confirm_modal").modal("hide");
            resolve($(this).text());
        });

        // Show the modal
        $("#confirm_modal").modal("show");
    });
}
  function reconsilesave(){		
		 debugger; 
		var credit_array=creditadvice_array("tblcreditadvice");
		if(credit_array.length<=0)	{
			alert("You have not select any data from Credit Advice");
			return false;
		}
		creditadvice_final = JSON.stringify(credit_array);  
		var bank_array = [];
		var k = 0;

		$("input[name='bankdataradio']:checked").each(function () {
		    var $row = $(this).closest("tr");
		    bank_array[k] = {
		        "bank_date": $row.data("transactiondate"), // direct data attr
		        "bankamount": $row.find(".bankamount").text().trim(),
		        "bank_descript": $row.find(".description_bank").text().trim(),
		        "accountname": $row.find(".accountname").text().trim(),
		        "accountno": $row.find(".accountno").text().trim(),
		        "bankid": $row.data("bankid"),
		        "accountid": $row.data("accountid"), 
		        "banksno": $row.data("dbsno")
		    };

		    k++;
		});	 

		if(bank_array.length<=0)	{
			alert("You have not select any data from Bank Statement");
			return false;
		}
		bank_array = JSON.stringify(bank_array);  
		var form_data = new FormData();	 
		form_data.append('companyid', companyid);
		form_data.append('subcompanyid', subcompanyid);
		form_data.append('projectid', projectid); 
		form_data.append('transactiontype',"deposit");      

		form_data.append('credit_array',creditadvice_final);
		form_data.append('bank_array',bank_array);
		form_data.append('reconcilefrom',"scant  data"); 
		form_data.append('loginid',loginid);
		$.ajax({
			type: "POST",	
			url: "modal/save_scantdata.php?action=save_scantdata",
			data: form_data,
		    contentType: false,
		    processData: false,
		    cache: false,
		 //   dataType:'JSON',
			success: function(res){	
				$(".waitloaderfooter").modal("hide"); 
				resp=res.trim();				
				alert(resp);
				if (resp=="Reconcile Saved Successfully"){					
					window.location.reload();
				} 	
			},beforeSend(res){
            	$(".waitloaderfooter").modal("show"); 
        	}					
		});
  }

  function creditadvice_array(tableid) {
  	var k=0;
	var Authprj;
	Authprj = storeTblValues1('#'+tableid+' tbody');
	function storeTblValues1(mytable)
	{
		var TableData_apj = new Array();
		var mytableid = mytable + ' tr';
		$(mytableid).each(function(row, tr){					  
			if($(tr).find('td').find('input[type="checkbox"]').is(":checked")){			 
				TableData_apj[k]={ 
					"paid" :$(tr).closest('tr').find(".clientname").text(),
					"paidid" :$(tr).closest('tr').data("clientid")||"",
					"purposecode" :$(tr).closest('tr').data("purposecode")||"",
					"purpose" :$(tr).closest('tr').find(".purpose").text()||"",
					"documentpath" :$(tr).closest('tr').data("docpath")||"",
					"voiceinstruction" :$(tr).closest('tr').data("voicepath")||"",
					"scandatasno" :$(tr).closest('tr').data("dbsno")||"",
					"scandataid" :$(tr).closest('tr').data("id")||""	,
					"scandata_amount":	$(tr).closest('tr').find(".depositamt").text()||"",		 
				} 
				k++;				
			} 				 	 		   
		}); 
		return TableData_apj;
	}
	 return Authprj;
 
}
 

</script>
