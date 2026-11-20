<?php 
$ppcatid = isset($_GET['ppcatid'])?$_GET['ppcatid']:'';
if(!$ppcatid){
  die('Purpose category not defined');
}
$dz="";
if(isset($_GET['dz'])){
	$dz=$_GET['dz'];
}
$protocol=$_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
$url = basename( $_SERVER["PHP_SELF"]);
$rooturl = $protocol.'://'.$_SERVER['HTTP_HOST'];
$baseurl= $rooturl.str_replace($url, "", $_SERVER["PHP_SELF"]);
if($_SERVER['HTTP_HOST']=="localhost"){
	$prefix=$rooturl."/krf_amanaccounting/";
}else{
	$prefix=$rooturl."/";
}
$rooturl =$prefix;
if(isset($_GET['subaction'])){
	$subaction = $_GET['subaction'];
}
$subprojectid="";
if(isset($_GET['subprojectid'])){
	$subprojectid=$_GET['subprojectid'];
}
?>
<!-- <div class="row mt-2 subprojectdiv" id="subprojectdiv">
	<div class="col-md-12">
		<label><?php echo $subprojectid!=""?"Sub-project name":"Choose the Sub-project"; ?></label>
		<select class="form-control" id="subproject"></select>
	</div>
</div> -->
<div class="row mt-2">
	<div class="col-md-8"></div>
	<div class="col-md-4">
		<label>Filter With Bill Type</label>
		<select class="form-control select2" id="billtypefitter"></select>
	</div>
</div>
<div class="row mt-2">
	<div class="col-md-12">
		<div class="table-responsive">
			<!-- <table class="table table-bordered table-light" id="billrequesttbls">
				<thead>
					<tr>
						<th>#</th>
						<th class="d-none">Bill Payto</th>
						<th>Beneficiary Name</th>
						<th>Bill Type</th>
						<th>Location</th>
						<th style="width:150px;">Bill Date</th>
						<th style="width:150px;">Bill Due Date</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table> -->
			<table class="table-mobile table-bordered table-striped table-light" id="billrequesttbls">
				<thead>
					<tr>
						<th colspan="4"><h4>Bill Payment Request </h4> <span id="billhistory_rangelbl"></span></th>
                        <th class="noexport" colspan="4">
                            <input type="text" class="form-control pull-right" name="rqw_filterinput" id="rqw_filterinput" onclick="filterTable('#rqw_filterinput', '#billrequesttbls');" placeholder="Type to Search ......"  />
                        </th>
					</tr>
					<tr>
						<th class="noexport">#</th>
						<th class="noexport d-none">Bill Payto</th>
						<th class="sortable" style="cursor:pointer;">Beneficiary Name <i class="fa fa-sort" style="color:blue;"></i></th>
						<th class="sortable" style="cursor:pointer;">Bill Type <i class="fa fa-sort" style="color:blue;"></i></th>
						<th class="sortable" style="cursor:pointer;">Location <i class="fa fa-sort" style="color:blue;"></i></th>
						<th class="sortable billingdateth" style="cursor:pointer; width:150px;">Billing Date <i class="fa fa-sort" style="color:blue;"></i></th>
                        <th class="sortable billduedateth" style="cursor:pointer; width:150px;">Payment Due Date <i class="fa fa-sort" style="color:blue;"></i></th>
						<th class="sortable billstatusth" style="cursor:pointer; width:250px;">Status <i class="fa fa-sort" style="color:blue;"></i></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
</div>
<script type="text/javascript">
var subprjid = "<?php echo $subprojectid; ?>";
var alrd_transactionno = $("#htmlcontainerdiv").data("unrtransactionno");
var alrd_transactiondate = $("#htmlcontainerdiv").data("unrtransactiondate");
var alrd_transactionamount = $("#htmlcontainerdiv").data("unrtransactionamount");
var alrd_likelypayto = $("#htmlcontainerdiv").data("unrlikelypayto");
var alrd_bankdbsno = $("#htmlcontainerdiv").data("unrbankdbsno");
var alrd_bankaccountid = $("#htmlcontainerdiv").data("unrbankaccountid");
var alrd_bankaccountno = $("#htmlcontainerdiv").data("unrbankaccountno");
var alrd_bankaccounttype = $("#htmlcontainerdiv").data("unrbankaccounttype");
var alrd_bankdescription = $("#htmlcontainerdiv").data("unrbankdescription");
var alrd_bankname = $("#htmlcontainerdiv").data("unrbankname");
$('#billpayment_modal').on('hidden.bs.modal', function () {
	$('body').addClass('modal-open');
});

$('#webpagebodyview_modal').on('hidden.bs.modal', function () {
	$('body').addClass('modal-open');
});

billtypewithoption("hidden_rooturl","all","billtypefitter");
pendingbills_forrequesthtml("hidden_rooturl", "hidden_companyid", "hidden_subcompanyid", "hidden_projectid", "billrequesttbls", "pending", function(res){
    $("#billrequesttbls tbody").empty();
    if(res){
      $("#billrequesttbls tbody").append(res);
    }
    setTimeout(function(){
        $('th.sortable:contains("Billing Date")').trigger('click');
        // $("#billtype_reminderfitter").val("all").trigger("change");
    },1000);
    
});

$(document).on("change", "#billtypefitter", function() {
    var biltypefv = $("#billtypefitter").val();
    if (biltypefv == "all") {
        // Show all rows if "all" is selected
        $("#billrequesttbls tbody tr").removeClass("d-none");
    } else {
        // Hide all rows and then show only matching rows
        $("#billrequesttbls tbody tr").addClass("d-none");
        $("#billrequesttbls tbody tr").each(function() {
            if ($(this).data("billtypetr") == biltypefv) {
                $(this).removeClass("d-none");
            }
        });
    }
});

$(document).on("click", "input[name='billreqradio']", function(){
	$("#billpaymentsavetodb").data("billdbsno", '');
	var billdbsno = $(this).val();
	var paytoid = $(this).closest("tr").find("td.paytoname_btd").data("bpaytoid");
	var paytoname = $(this).closest("tr").find("td.paytoname_btd").text();
	var beneficiaryname = $(this).closest("tr").find("td.beneficiaryname_btd").text();
	var locationid = $(this).closest("tr").find("td.locationid_btd").text();
	var billduedate = $(this).closest("tr").find("td.billduedate_btd").text();
    var billtype = $(this).closest("tr").find("td.billtype_btd").text();
	var billid = $(this).closest("tr").data("billidtr");
    request_invoicetype("hidden_baseurl","Invoice","billinvoicetype");
	$("#billpayment_modal").modal("show");
	$("#billpaymentheader").html(""+billtype+" Bill Payment for location "+locationid);
	$("#billpaymentpaytoname").val(paytoname);
	$("#billpaymentbeneficiaryname").val(beneficiaryname);
    $("#billpaymentsavetodb").data("billdbsno", billdbsno);
	$("#billpaymentsavetodb").data("billid", billid);
});
</script>