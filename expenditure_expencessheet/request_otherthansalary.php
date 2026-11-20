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
?>
<div id="uploadsheetcontainerdiv">
	<div class="row">
		<div class="col-md-12 isformatsetdiv" style="display:none;">
			<b>Note:-</b>
			<ul style="color:red;">
				<li>Download the excel format sheet</li>
				<li>Check your excel sheet along with the your Excel format sheet.</li>
				<li>Please upload the Excel sheet that which is formatted. </li>
			</ul>
		</div>
		<div class="col-md-12">
			<div id="formatsheetdiv"></div>
		</div>
	</div>
	<div class="row mt-2 isformatsetdiv" style="display:none;">
		<div class="col-md-12">
			<input type="file" name="otherthansalarysheetatt" id="otherthansalarysheetatt" class="form-control mt-2" />
		</div>
	</div>
	<div class="row mt-4 isformatsetdiv" style="display:none;">
		<div class="col-md-2"></div>
		<div class="col-md-8 text-center">
			
			<button class="btn btn-info mt-2" id="otherthansalarytbtn" onclick="uploadotherthan_salarysheetforcreatebook('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','otherthansalarysheetatt','hidden_loginname','otherthansalarytbtn');">Upload Formated Expense excel sheet</button>
		</div>
		<div class="col-md-2"></div>
	</div>
	<div class="row mt-4">
		<div class="col-md-12 mt-4">
			<details id="pendingsalarydatadtl" open>
				<summary><h5>Expense data for creating a book</h5></summary>
				<div class="table-responsive">
					<table class="table table-bordered" id="pendingbookofexpnsedatatbl">
						<thead>
							<tr>
								<th>S.No</th>
								<th>Payto Name</th>
								<th style='display:none;'>Bank Name</th>
								<th style='display:none;'>A/C No</th>
								<th style='display:none;'>IFSC Code</th>
								<th>Transfer Salary</th>
								<th>Provided Purpose</th>
								<th style='display:none;'>datatype</th>
								<th style="width:230px;">Purpose</th>
							</tr>
						</thead>
						<tbody></tbody>
						<tfoot>
							<tr>
								<td colspan="4"></td>
								<td colspan="2" class="text-center"><button class="btn btn-info" id="otherthansalarydatabtn" onclick="createbook_excelsheetdata('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','hidden_loginname','pendingbookofexpnsedatatbl', 'otherthansalarydatabtn');">Create Book From Excel Data</button></td>
							</tr>
						</tfoot>
					</table>
				</div>
			</details>
		</div>
	</div>
</div>

<script type="text/javascript">
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

getdata_forreconsturctuct_otharthansalarydata("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","pendingbookofexpnsedatatbl");

var dwnforsheetbtn="";
var rooturl = $("#hidden_rooturl").val();
excelsheetfunctioninfo('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','otherthansalarysheet',function(ress){
  if(ress.length>0){
    var fileurl="";
    dwnforsheetbtn ="";
    for(var j=0; j<ress.length; j++){
      fileurl=rooturl+'attachementfile/formatedexcelsheet/'+ress[j].function_name+'.xls';
      dwnforsheetbtn = '<a href="'+fileurl+'" class="btn btn-sm btn-info float-right" target="_blank"><i class="fa fa-download"></i> Example of other than salary excel sheet format</a>';
      $(".isformatsetdiv").show();
    }
  }else{
    dwnforsheetbtn='<h4 style="color:red; text-align:center;">The other than salary excel sheet format is not defined in our system. Please contact the web administrator.</h4>';
  	$(".isformatsetdiv").hide();
  }
  $("#formatsheetdiv").append(dwnforsheetbtn);
});
</script>