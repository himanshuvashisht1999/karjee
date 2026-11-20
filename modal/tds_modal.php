<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');
//require('../automation/automation_functionmaster.php');
$action=$_GET['action'];
if($action=="tdsdeducted_durationlist"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$subprojectid=$_POST['subprojectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$tdsyearmonth=getdeductedtds_durationlist($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$shortby,$shortbyvalue);
	echo json_encode($tdsyearmonth);
}else if($action=="tdsdeducted_months_withfinyear"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$subprojectid=$_POST['subprojectid'];
	$financialyear=$_POST['financialyear'];
	$monthlist=deductedtds_monthlist($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$financialyear);
	echo json_encode($monthlist);
}else if($action=="tdsdeducted_quaters_withfinyear"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$subprojectid=$_POST['subprojectid'];
	$financialyear=$_POST['financialyear'];
	$quarterlist=deductedtds_quarterlist($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$financialyear);
	echo json_encode($quarterlist);
}else if($action=="tdsdeducted_financialyearlist"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$subprojectid=$_POST['subprojectid'];
	$finyear=deductedtds_financialyearlist($conn,$companyid,$subcompanyid,$projectid,$subprojectid);
	echo json_encode($finyear);
}else if($action=="tdsdeducted_type"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$subprojectid=$_POST['subprojectid'];
	$yearmonth = $_POST['yearmonth'];
	$tdstypelist=getdeducted_tdstypelist($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$yearmonth);	
	echo json_encode($tdstypelist);
}else if($action=="tdsdeducted_percent"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$subprojectid=$_POST['subprojectid'];
	$yearmonth=$_POST['yearmonth'];
	$tdstype = $_POST['tdstype'];
	$tdspercent=getdeductedtds_percentlist($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$yearmonth,$tdstype);
	echo json_encode($tdspercent);
}else if($action=="tdsdeducted_data"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$subprojectid=$_POST['subprojectid'];
	$yearmonth=$_POST['yearmonth'];
	$tdstype = $_POST['tdstype'];
	$tdspercent = $_POST['tdspercent'];
	$tdsq=$conn->query("select sno, bookdate, countryid, companyid, subcompanyid, projectid, tds_type, request_no, reference_no, from_accountid, from_accountno, from_bankname, beneficiary_name, amount_paid, tds_deducted, tds_percent, tds_status, transactionno, transaction_date, bill_no, tds_challan_no, tds_challan_date FROM tds_account_summery WHERE (tedit is null or tedit='') and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and subprojectid='".$subprojectid."' and YEAR(bookdate)='".date('Y', strtotime($yearmonth))."' and MONTH(bookdate)='".date('m', strtotime($yearmonth))."' and tds_type='".$tdstype."' and tds_percent='".$tdspercent."'");
	$i=0;
	$total_emp_contribution=0;
	$tdshtml='<table class="table table-light table-bordered table-hover" id="tdsrequestdatatbl">
		<thead>
			<tr>
				<th>S No.</th>
				<th>Employee Name</th>
				<th>Reference No</th>
				<th>Bill No</th>
				<th>Bill Amount</th>
				<th>TDS Deduction</th>
			</tr>
		</thead>
		<tbody>';
		$btnsow=0;
		if($tdsq->num_rows>0)
		{
			$btnsow=1;
			while($tdsrow = $tdsq->fetch_assoc())
			{
				$reference_no = $tdsrow['reference_no'];
				$bill_amount = $tdsrow['amount_paid'] + $tdsrow['tds_deducted'];
				$tdshtml.='<tr>
					<td>'.++$i.'</td>
					<td>'.$tdsrow['beneficiary_name'].'</td>
					<td>'.$tdsrow['reference_no'].'</td>
					<td>'.$tdsrow['bill_no'].'</td>
					<td>'.$bill_amount.'</td>
					<td>'.round($tdsrow['tds_deducted']).'</td>
				</tr>';
				$total_emp_contribution += round($tdsrow['tds_deducted']);
			}
			$tdshtml.='<tr>
					<td colspan="4"></td>
					<td class="bg-success">Total</td>
					<td class="bg-info" id="total_emp_contribution">'.$total_emp_contribution.'</td>
				</tr>';
		}else{
			$tdshtml.='<tr>
				<td colspan="6" style="align:center;">No Data Available</td>
			</tr>';		
		}
			$tdshtml.='</tbody>
		</table>';
		if($btnsow==1){
			$tdshtml.='<div class="col-md-12 text-center"><button class="btn btn-success" id="savetodbtdsrequestbtn">Create TDS Request</button></div>';
		}
	echo $tdshtml;	
}else if($action=="tdsdeducted_createrequest"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$subprojectid=$_POST['subprojectid'];	
	$yearmonth=$_POST['yearmonth'];
	$tdstype = $_POST['tdstype'];
	$tdspercent = $_POST['tdspercent'];
	$purposecode = $_POST['purpose'];
	$modeoftransfer = $_POST['modeoftransfer']!=""?$_POST['modeoftransfer']:"Online";
	$paymentduedate = $_POST['paymentduedate'];
	$loginname = $_POST['loginname'];
	$requesttypename = "pre-payment";
	$resultarry=array();
	$cci=getcompanydetails($conn,"companyid",$companyid);
	$countryid="";
	foreach ($cci as $cckey) {
		$countryid=$cckey['countryid'];
	}
	//get purpose details
	$ppt=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	$category = "";
	$categorycode = "";
	$subcategory = "";
	$subcategorycode = "";
	$purposename = "";
	$purposecode = "";
	if(count($ppt)>0){
		foreach ($ppt as $pptkey) {
			$category = $pptkey['pcategory'];
			$categorycode = $pptkey['pcategorycode'];
			$subcategory = $pptkey['psub_category'];
			$subcategorycode = $pptkey['psub_categoryid'];
			$purposename = $pptkey['purpose'];
			$purposecode = $pptkey['purposecode'];
		}
	}else{
		$resultarry=array('status'=>'Error', 'message'=>'Purpose details did not available in our master list');
		echo json_encode($resultarry);
		die();
	}
	$paytoname = "Indian Bank A/C Income Tax";
	$ppyt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoname",$paytoname);
	if(count($ppyt)>0){
		foreach ($ppyt as $ppttkey) {
			$paytobank = $ppttkey['tobankname']!=""?$ppttkey['tobankname']:"online";
			$paytobankaccountno=$ppttkey['toaccountno']!=""?$ppttkey['toaccountno']:"online";
			$paytobankifsccode=$ppttkey['tobankifsc']!=""?$ppttkey['tobankifsc']:"online";
			$paytoid=$ppttkey['paytoid'];
		}
	}else{
		$paytobank = "online";
		$paytobankaccountno="online";
		$paytobankifsccode="online";
		$nnpayt=savetodbnewpayto_details($conn,$paytoname,$paytobankaccountno,$paytobank,$paytobankifsccode,"","","",$companyid,$subcompanyid,$projectid,'','','');
		$paytoid="";
		if(count($nnpayt)>0){
			foreach ($nnpayt as $ptnkey) {
				if($ptnkey['status']=="Success"){
					$paytoid=$ptnkey['paytoid'];
				}
			}
		}
	}
	$tdsq=$conn->query("select sno, bookdate, countryid, companyid, subcompanyid, projectid, tds_type, request_no, reference_no, from_accountid, from_accountno, from_bankname, beneficiary_name, amount_paid, tds_deducted, tds_percent, tds_status, transactionno, transaction_date, bill_no, tds_challan_no, tds_challan_date FROM tds_account_summery WHERE (tedit is null or tedit='') and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and subprojectid='".$subprojectid."' and YEAR(bookdate)='".date('Y', strtotime($yearmonth))."' and MONTH(bookdate)='".date('m', strtotime($yearmonth))."' and tds_type='".$tdstype."' and tds_percent='".$tdspercent."'");
	$voucherinfo=array();
	$withdrownamount=0;
	$updtdq="";
	$updsno="";
	$invoicedata=array();
	$rtnmsg=array();
	if($tdsq->num_rows>0){
		while($tdsrow=$tdsq->fetch_assoc()){
			if($updsno){
				$updsno=$updsno.",".$tdsrow['sno'];
			}else{
				$updsno=$tdsrow['sno'];
			}
			
			$withdrownamount=$withdrownamount+$tdsrow['tds_deducted'];
			$requestdescription = "TDS Deduction (".$tdsrow['tds_percent']."%) against bill no. ".$tdsrow['bill_no']." and reference no ".$tdsrow['reference_no'];

			$voucherinfo[]=array('beneficiaryname'=>$tdsrow['beneficiary_name'],'expenseamount'=>$tdsrow['tds_deducted'], 'invoiceno'=>'', 'invoicetype'=>'', 'invoicedate'=>'', 'description'=>$requestdescription, 'purposecode'=>$purposecode, 'purposename'=>$purposename, 'attachmentpath'=>'','invoicegstamount'=>'','taxableamount'=>'','ponumber'=>'', 'docsno'=>'','srm_attsno'=>'','srm_emailsno'=>'','invoicedata'=>$invoicedata,'totalrowsamount'=>'','totalgstamount'=>'','totalrowsdiscount'=>'','otherdiscount'=>'','totalpayableamount'=>'');
		}
		$isforproduction="";
		// $rtnmsg=reconstruct_requestbyapp_forother($conn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid,'',$modeoftransfer,'','',$withdrownamount,$paytoid,"","","","",$voucherinfo,$requesttypename,$loginname,$paymentduedate,$isforproduction);
		$rtnmsg=reconstruct_requestbyapp_forother($conn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid,'',$modeoftransfer,'','',$withdrownamount,$paytoid,"","","","",$voucherinfo,$requesttypename,$loginname,$paymentduedate,$isforproduction,$category);

		if(count($rtnmsg)>0){
			foreach ($rtnmsg as $tdukey) {
				if($tdukey['status']=="Success" && $updsno!=""){
					$tdsstatus = 'TDS Request has been Created on date('.date('Y-m-d H:i:s').') with Request No '.$tdukey['requestno'];
					$conn->query("update tds_account_summery set tds_status='".$tdsstatus."' where sno in('".str_replace(",", "','", $updsno)."') and (tds_status is null or tds_status='')");
					$resultarry=$rtnmsg;
				}else{
					$resultarry[]=array('status'=>'Error', 'message'=>'TDS request did not create, due to error');
				}
			}
		}else{
			$resultarry[]=array('status'=>'Error', 'message'=>'TDS request did not create, due to error');;
		}
	}
	echo json_encode($resultarry);
}else if($action=="gettdstransactions_datareport"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$subprojectid=$_POST['subprojectid'];
	$tdstype = $_POST['tdstype'];
	$tdsduration=$_POST['tdsduration'];
	if($tdstype=="All" || $tdstype=="all"){
		$tdstypecond="";
	}else{
		$tdstypecond=" and tds_type='".$tdstype."'";
	}

	if (strpos($tdsduration, '|') !== false) {
		$duration_exp = explode("|", $tdsduration);
		$mindate = $duration_exp[0];
		$maxdate = $duration_exp[1];
	}
	$select_qrt = "SELECT sno, bookdate, countryid, companyid, subcompanyid, projectid, tds_type, request_no, reference_no, from_accountid, from_accountno, from_bankname, beneficiary_name, amount_paid, tds_deducted, tds_percent, tds_status, transactionno, transaction_date, bill_no, tds_challan_no, tds_challan_date  FROM tds_account_summery where  (Tedit is null or Tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (DATE(bookdate) >= '".DATE($mindate)."' and DATE(bookdate)<='".DATE($maxdate)."') ".$tdstypecond."  order by bookdate desc";
	$qrt_result=$conn->query($select_qrt);
	$i=1;
	$output='<div class="col-md-12 text-right">
		<button class="btn btn-deepblue mb-0" onclick="gridtoexcel_withfilter_chunk(\'tdstransactiontbldata\', \'tdstransactiondata\', \'landscape\',function(res){})"><i class="fa fa-download" aria-hidden="true"></i> Export</button>
	</div>
	<div class="table-responsive mt-2">	
		<table class="table table-light table-bordered table-hover" id="tdstransactiontbldata">
			<thead>
				<tr>
					<th style="min-width: 30px;">S. No</th>
					<th style="min-width: 80px;">Deduction Date</th>
					<th style="min-width: 80px;">Bill No.</th>
					<th style="min-width: 80px;">Bill Date</th>
					<th style="min-width: 80px;">Paid by Ch. No.</th>
					<th style="min-width: 80px;">Payment Date</th>
					<th style="min-width: 150px;">Party Name</th>
					<th style="min-width: 80px;">PAN No.</th>
					<th style="min-width: 80px;">Bill Amount</th>
					<th style="min-width: 80px;">Payable Amount</th>
					<th style="min-width: 80px;">TDS Amount</th>
					<th style="min-width: 80px;">Rate %</th>
					<th style="min-width: 80px;">Challan No.</th>
					<th style="min-width: 80px;">Date Of Deposit</th>
					<th style="min-width: 80px;">Cheque No.</th>
					<th style="min-width: 80px;">Cheque Date</th>
					<th style="min-width: 80px;">Pay Amount</th>
				</tr>
			</thead>
			<tbody>';
			$bnf_panno="";
			$latefee =0;
			while($row = $qrt_result->fetch_assoc())
			{
				$bill_amount = $row['amount_paid'] + $row['tds_deducted'];
				$chkmaint=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$row['reference_no']);
				$transactionno="";
				$transaction_date="";
				$transfered_amount="";
				if(count($chkmaint)>0){
					foreach ($chkmaint as $cgggkey) {
						$transactionno = $cgggkey['transactionno'];
						$transaction_date = $cgggkey['transaction_date'];
						$transfered_amount = $cgggkey['transfered_amount'];
					}
				}
				
				$get_bnf_panno=GettdsBeneficiaryPanno($row['beneficiary_name'],$conn);
				if(!$get_bnf_panno==""){
					$bnf_panno=$get_bnf_panno;
				}else{
					$bnf_panno="";
				}
				$output.='<tr>
					<td style="min-width: 30px;"> '.$i++.'</td>
					<td style="min-width: 80px;"> '.date('Y-m-d', strtotime($row['bookdate'])).'</td>
					<td style="min-width: 80px;"> '.$row['bill_no'].'</td>
					<td style="min-width: 80px;"> &nbsp;</td>
					<td style="min-width: 80px;">'.$transactionno.'</td>
					<td style="min-width: 80px;"> '.$transaction_date.'</td>
					<td style="min-width: 150px;">'.$row['beneficiary_name'].'</td>
					<td style="min-width: 80px;"> '.$bnf_panno.'</td>
					<td style="min-width: 80px;"> '.$bill_amount.'</td>
					<td style="min-width: 80px;"> '.round($row['amount_paid']).'</td>
					<td style="min-width: 80px;"> '.round($row['tds_deducted']).'</td>
					<td style="min-width: 80px;"> '.$row['tds_percent'].'</td>
					<td style="min-width: 80px;"> '.$row['tds_challan_no'].'</td>
					<td style="min-width: 80px;"> </td>
					<td style="min-width: 80px;"> '.$transactionno.'</td>
					<td style="min-width: 80px;"> '.$transaction_date.'</td>
					<td style="min-width: 80px;"> '.$transfered_amount.'</td>
				</tr>';
			}
			$output.='</tbody>
		</table>
	</div>';
	echo $output;
}else if($action=="missing_tdschalandocument"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$loginid=$_POST['loginid'];
	$loginname=$_POST['loginname'];
	$loginroleid=$_POST['loginroleid'];
	$reqdatefrom=$_POST['datefrom'];
	$reqdateto=$_POST['dateto'];
	$bdatecond="";
	if($reqdatefrom && $reqdateto){
		$bdatecond=" and bookdate>='".$reqdatefrom."' and bookdate<='".$reqdateto."'";
	}
	$select_qrt = "SELECT tds_type, bookdate, reference_no,count(*) as total_bill,sum(tds_deducted) as total_tds  FROM tds_account_summery where  (Tedit is null or Tedit='')  and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'  and (tds_challan_no='' or tds_challan_no is null) ".$bdatecond." group by tds_status";
	 // and (tds_status<>'' or tds_status<>null)
	$qrt_result=$conn->query($select_qrt);
	$i=1;
	$output="";
	while($row = $qrt_result->fetch_assoc())
	{
		$output .= '<tr>
			<td> ' . $i++ . '</td>
			<td style="display:none;"> ' . $row['reference_no'] . '</td>
			<td> ' . date('M-Y', strtotime($row['bookdate'])) . '</td>
			<td> ' . $row['tds_type'] . '</td>
			<td> ' . $row['total_bill'] . '</td>
			<td> ' . $row['total_tds'] . '</td>
			<td><input type="text" id="tdschalanno_' . $i . '" class="form-control tdschalanno" /></td>
			<td><input type="date" id="tdschalandate_' . $i . '" class="form-control tdschalandate" /></td>
			<td><input type="file" id="tdschalanfile_' . $i . '" class="form-control tdschalanattachement" /></td>
			<td><button id="clicktoupdatebtn_' . $i . '" class="btn btn-sm btn-flat bg-warning btnUpdateChallan" onclick="update_tdschalandocument(\'hidden_rooturl\', \'hidden_companyid\', \'hidden_subcompanyid\', \'hidden_projectid\', \'tdschalanno_' . $i . '\', \'tdschalandate_' . $i . '\', \'tdschalanfile_' . $i . '\', \'' . $row['reference_no'] . '\', \'clicktoupdatebtn_' . $i . '\')">Update Challan</button></td>
		</tr>';

	}
	echo $output;
}else if($action=="update_tdschalandocument"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$chalanno=$_POST['chalanno'];
	$chalandate=$_POST['chalandate'];
	$referenceno=$_POST['referenceno'];
	$loginid=$_POST['loginid'];
	$loginname=$_POST['loginname'];	
	$select_qrt = "SELECT tds_status  FROM tds_account_summery where  (Tedit is null or Tedit='')  and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and reference_no='".$referenceno."'";
	$mmtq=$conn->query($select_qrt);
	$rrst=array();
	if($mmtq->num_rows>0){
		while ($mntkey =$mmtq->fetch_assoc()) {
			$receiptpath ="";
			if (isset($_FILES['filepath']) && !$_FILES['filepath']['error']) 
			{
		        // Directory where the audio file will be saved
		        $receiptpathDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/tdschalan_document/".$mntkey['reference_no']."/";
		        
		        // Create the uploads directory if it doesn't exist
		        if (!is_dir($receiptpathDir)) {
		            mkdir($receiptpathDir, 0755, true);
		        }
		        
		        //file name
		        $temp = explode(".", $_FILES["filepath"]["name"]);
				$receiptname = round(microtime(true)) .date('His'). '.' . end($temp);

		        // $filetype = $_FILES['filepath']['type'];
		        // File path
		        $uploadFile = $receiptpathDir . $receiptname;
		        $receiptpath = str_replace("../", "", $uploadFile);
		        // Move the uploaded file to the specified directory
		        move_uploaded_file($_FILES['filepath']['tmp_name'], $uploadFile);
		    }
		}
		if($receiptpath){
			$conn->query("UPDATE tds_account_summery SET tds_challan_no='".addslashes($chalanno)."', tds_challan_date='".addslashes($chalandate)."' where  (Tedit is null or Tedit='')  and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and reference_no='".$referenceno."'");
			if(mysqli_affected_rows($conn)>0){
				$sqlUpdate = "UPDATE tbl_maintransaction_breakdown set documentation_path='".$receiptpath."', uploadflag=null where request_no='".$request_no."' ";
				$rrst=array('status'=>'Success', 'message'=>'TDS chalan uploaded successfully');	
			}
		}else{
	    	$rrst=array('status'=>'Error', 'message'=>'TDS chalan file did not upload due source file path is incorrect, Please try again');
	    }
	}else{
		$rrst=array('status'=>'Error', 'message'=>'File did not upload due to somthing wrong, Please try again');
	}	
	echo json_encode($rrst);	
}else if($action=="incometaxdeducted_paytolist"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$shortby=$_POST['shortby'];
	$shortbyvalue=$_POST['shortbyvalue'];
	$paytolist=getItaxpaytolist($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	echo json_encode($paytolist);
}else if($action=="incometaxdeducted_months"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$financialyear=$_POST['financialyear'];
	$monthlist=deductedIncomeTax_monthlist($conn,$companyid,$subcompanyid,$projectid,$financialyear);
	echo json_encode($monthlist);
}else if($action=="getincometaxdeducted_data"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$paytoid=$_POST['paytoid'];
	if(strtolower($paytoid)=="all"){
		$itdsq=$conn->query("select sno,bookdate, paytoid, paytoname, itaxamount, paidamount, reference_no FROM itax_account_summery WHERE (tedit is null or tedit='') and (itaxstatus is null or itaxstatus='') and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
	}else{
		$itdsq=$conn->query("select sno,bookdate, paytoid, paytoname, itaxamount, paidamount, reference_no FROM itax_account_summery WHERE (tedit is null or tedit='') and (itaxstatus is null or itaxstatus='') and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and paytoid = '".$paytoid."'");
	}
	$i=0;
	$total_emp_contribution=0;
	$btnsow=0;
	$tdshtml="";
	if($itdsq->num_rows>0)
	{
			$tdshtml.='<table class="table table-light table-bordered table-hover" id="incometaxrequestdatatbl">
				<thead>
					<tr>
						<th>S No.</th>
						<th>Deducted Date</th>
						<th>Name</th>
						<th>Income-Tax Deducted Amount</th>
						<th>Deducted From Reference No</th>
					</tr>
				</thead>
				<tbody>';
			$btnsow=1;
			while($tdsrow = $itdsq->fetch_assoc())
			{
				$tdshtml.='<tr data-itaxsno="'.$tdsrow['sno'].'">
					<td>'.++$i.'</td>
					<td>'.date('d-m-Y', strtotime($tdsrow['bookdate'])).'</td>
					<td>'.$tdsrow['paytoname'].'</td>
					<td>'.round($tdsrow['itaxamount']).'</td>
					<td>'.$tdsrow['reference_no'].'</td>
				</tr>';
				$ttlcontribution += round($tdsrow['itaxamount']);
			}
			$tdshtml.='</tbody><tfoot>';
			$tdshtml.='<tr class="bg-success">
					<td colspan="3">Total</td>
					<td class="bg-info" id="total_contribution"><input type="hidden" id="itax_amount" value="'.$ttlcontribution.'" />'.$ttlcontribution.'</td>
					<td></td>
				</tr>';
			$tdshtml.='</tfoot>
		</table>';
		}		
	echo $tdshtml;	
}

function GettdsBeneficiaryPanno($bnfname,$conn){
	$gtpanquery="SELECT bnf_pan_no FROM tds_bnf_master where bnf_name like '%$bnfname%'";
	$gtpanquery_rslt=$conn->query($gtpanquery);
	if($gtpanquery_rslt->num_rows>0){
		$gtpan = $gtpanquery_rslt->fetch_assoc();
		$getpant = $gtpan['bnf_pan_no'];
	}else{
		$getpant='';
	}
	return $getpant;
}
?>