<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');
$action=$_GET['action'];
if($action=="billlocationwithoption"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	//get location name
	$idlc=identificationlocationmaster_bychatur($conn,$companyid,$subcompanyid,$projectid,"all","");
	echo json_encode($idlc);
}else if($action=="update_billmaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$billdbsno = $_POST['billdbsno'];
	$billtype = $_POST['billtype'];
	$billdurationtype = $_POST['billdurationtype'];
	$billdurationdays = $_POST['billdurationdays'];
	$billlocation = $_POST['billlocation'];
	$billpaytoid = $_POST['billpaytoname'];
	$billbeneficiaryname = $_POST['billbeneficiaryname'];
	$billpurpose = $_POST['billpurpose'];
	$responsiblepersonname = $_POST['responsiblepersonname'];
	$billingdate = $_POST['billingdate'];
	$paymentduedate = $_POST['paymentduedate'];
	$billremark = $_POST['billremark'];
	$loginname = $_POST['loginname'];
	$uploadreferencefiles="";
	$editbillmaster=array();
	if (isset($_FILES['billattachedfile']) && !empty($_FILES['billattachedfile']['name'])) {
      $distpath = "../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/IntegratedBillDocument/" . date('Ymd') . "/";

      // Validate file upload
      if ($_FILES["billattachedfile"]["error"] !== UPLOAD_ERR_OK) {
          $editbillmaster[] = array('status' => 'Error', 'message' => "File upload error: " . $_FILES["billattachedfile"]["error"], 'dataupdatewith' => '');
          echo json_encode($editbillmaster);
          die();
      }

      // Validate and create directory
      if (!is_dir($distpath) && !mkdir($distpath, 0755, true)) {
          $editbillmaster[] = array('status' => 'Error', 'message' => "Failed to create directory.", 'dataupdatewith' => '');
          echo json_encode($editbillmaster);
          die();
      }

      // Validate file extension
      $allowedExtensions = ['jpg', 'png', 'pdf', 'jpeg'];
      $temp = explode(".", $_FILES["billattachedfile"]["name"]);
      $extension = strtolower(end($temp));
      if (!in_array($extension, $allowedExtensions)) {
          $editbillmaster[] = array('status' => 'Error', 'message' => "Invalid file type.", 'dataupdatewith' => '');
          echo json_encode($editbillmaster);
          die();
      }

      // Generate new filename and sanitize input
      $newfilename = round(microtime(true)) . '_' . date('His') . '.' . $extension;
      $filepath = $distpath . $newfilename;

      // Move uploaded file
      if (move_uploaded_file($_FILES["billattachedfile"]["tmp_name"], $filepath)) {
          $uploadreferencefiles = str_replace('../', '', $filepath);
          // $editbillmaster[] = array(
          //     'status' => 'Success',
          //     'message' => 'File uploaded successfully.',
          //     'uploaded_path' => str_replace("../", "", $filepath)
          // );
      } else {
          $editbillmaster[] = array('status' => 'Error', 'message' => "Failed to move uploaded file.", 'dataupdatewith' => '');
        echo json_encode($editbillmaster);
        die();
      }
  	}
	
	$cjkk=$conn->query("SELECT sno FROM strs_billmaster_universal WHERE sno='".$billdbsno."'");
	if($cjkk->num_rows>0){
		if($uploadreferencefiles){
	      $uploadreferencefiles = ", uploadreferencefiles='".str_replace('../', '', $filepath)."'";
	    }else{
	      $uploadreferencefiles="";
	    }
		$conn->query("UPDATE strs_billmaster_universal SET subproject='".$subprojectid."', typeofbill='".$billtype."', billduration='".$billdurationtype."', billdurationdays='".$billdurationdays."', identifierlocation='".$billlocation."', paytoid='".$billpaytoid."', beneficiaryname='".$billbeneficiaryname."', purposecode='".$billpurpose."', responsablepursonname='".$responsiblepersonname."', nextbilldate='".$billingdate."', billduedate='".$paymentduedate."', billdescription='".$billremark."' ".$uploadreferencefiles." where sno='".$billdbsno."'");
		if(mysqli_affected_rows($conn)>0){
			$editbillmaster[]=array('status'=>'Success', 'message'=>'Bill details updated successfully.');
		}else{
			$editbillmaster[]=array('status'=>'Error', 'message'=>'Bill details did not update, due to technical issuce');
		}
	}else{
	    $cjkk=$conn->query("SELECT sno FROM strs_billmaster_universal WHERE companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and typeofbill='".$billtype."' and billduration='".$billdurationtype."' and identifierlocation='".$billlocation."' and paytoid='".$billpaytoid."' and purposecode='".$billpurpose."' and billduedate='".$paymentduedate."'");
	    if($cjkk->num_rows>0){
	      $editbillmaster[]=array('status'=>'Success', 'message'=>'New bill already registered.'); 
	    }else{
	        $conn->query("INSERT INTO strs_billmaster_universal (companyid,subcompanyid,projectid,subproject,id,typeofbill,billduration,billdurationdays,identifierlocation,paytoid,beneficiaryname,purposecode,responsablepursonname,nextbilldate,billduedate,billdescription,uploadreferencefiles) VALUES (
	        '".$companyid."',
	        '".$subcompanyid."',
	        '".$projectid."',
	        '".$subprojectid."',
	        '".uniqid()."',
	        '".addslashes($billtype)."',
	        '".addslashes($billdurationtype)."',
	        '".addslashes($billdurationdays)."',
	        '".addslashes($billlocation)."',
	        '".$billpaytoid."',
	        '".addslashes($billbeneficiaryname)."',
	        '".addslashes($billpurpose)."',
	        '".addslashes($responsiblepersonname)."',
	        '".addslashes($billingdate)."',
	        '".addslashes($paymentduedate)."',
	        '".addslashes($billremark)."',
	        '".addslashes($uploadreferencefiles)."')") or die($conn->error);
	      if(mysqli_affected_rows($conn)>0){
	          $editbillmaster[]=array('status'=>'Success', 'message'=>'New bill registration saved successfully.');        
	      }else{
	        $editbillmaster[]=array('status'=>'Error', 'message'=>'Bill details did not save, due to technical issuce');
	      }
	    }
	  }
	echo json_encode($editbillmaster);	
}else if($action=="remove_billfrommaster"){
	$billdbsno = $_POST['billdbsno'];
	$loginname = $_POST['loginname'];
	$billremoveremark = $_POST['billremoveremark'].' : removed by '.$loginname.' on date '.date('Y-m-d h:is');
	$uploadreferencefiles="";
	$rmstatus=array();
	
	$cjkk=$conn->query("SELECT sno FROM strs_billmaster_universal WHERE sno='".$billdbsno."'");
	if($cjkk->num_rows>0){
		$conn->query("UPDATE strs_billmaster_universal SET tedit='".$billremoveremark."' where sno='".$billdbsno."'");
		if(mysqli_affected_rows($conn)>0){
			$rmstatus=array('status'=>'Success', 'message'=>'Bill removed from utility bill master successfully.');
		}else{
			$rmstatus=array('status'=>'Error', 'message'=>'Bill did not remove, due to technical issuce');
		}
	}else{
		$rmstatus=array('status'=>'Error', 'message'=>'Bill details did not find our master list or already removed');
	}
	echo json_encode($rmstatus);
}else if($action=="historyofbillreport_htmlview"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	$datefrom = $_POST['datefrom'] ?? null;
  	$dateto = $_POST['dateto'] ?? null;
	
	if (!$companyid || !$subcompanyid || !$projectid) {
	    echo "Required parameters missing.";
	    exit;
	}

	$bblm=billmasteruniversal_bychatur($conn,$companyid,$subcompanyid,$projectid,"all","all");
	// print_r($bblm);
	$blhtml="";
	if(count($bblm)>0){
		foreach ($bblm as $blmkey) {
			// Get transaction details
	        $bbl = billtransactionshistorywithdate($conn, $companyid, $subcompanyid, $projectid,$datefrom,$dateto, "historywithbillid", $blmkey['id']);
	        // Function to fetch data
	        $paytoname = fetchSingleValue(getpaytoinfo($conn, $companyid, $subcompanyid, "paytoid", $blmkey['paytoid']), 'paytoname');
	        $locationname = fetchSingleValue(identificationlocationmaster_bychatur($conn, $companyid, $subcompanyid, $projectid, "id", $blmkey['identifierlocation']), 'locationname');
	        $billtypename = fetchSingleValue(billtypemasterlist_bychatur($conn, "id", $blmkey['typeofbillid']), 'typename');
	        // print_r($bbl);
			if(count($bbl)>0){
				foreach ($bbl as $bblkey) {
					
					$billingdate = date_format(date_create($bblkey['billingdate']), 'd-m-Y');
					$billduedate = date_format(date_create($bblkey['billduedate']), 'd-m-Y');
					$transactionno="";
					$transactiondate="";
					$transferedamount="";
					if ($bblkey['referenceno']) {
					    $checkpaidstatus = checkbeneficiarypaymentPaidorNot($conn, $companyid, $subcompanyid, $projectid, "requestno", $bblkey['referenceno'], $bblkey['referenceno'], $bblkey['referenceno']);
					    // print_r($checkpaidstatus);
					    if (!empty($checkpaidstatus)) {
					    	if($checkpaidstatus[0]['transactionno']){

					    		$ddt = getDaysDifference($bblkey['billduedate'], $bblkey['transactiondate']);
					            if(intval($ddt)<0){
					              $trstatus = "<span class='text-success'><i class='fa fa-check-circle'></i> Paid under due date</span>";
					            }else if(intval($ddt)==0){
					              $trstatus = "<span class='text-success'><i class='fa fa-check-circle'></i> Paid on due date</span>";
					            }else{
					              $trstatus = "<span class='text-success'><i class='fa fa-check-circle'></i> Paid after due date</span>";
					            }
						       
						        $transactiondate = $bblkey['transactiondate']!=""?date_format(date_create($bblkey['transactiondate']), 'd-m-Y'):"N/A";
								$transferedamount = $bblkey['transferedamount']!=""?$bblkey['transferedamount']:"N/A";
					    	}else{
						        $trstatus = "<span class='text-info'><i class='fa fa-clock'></i> Bill submitted But not paid</span>";
						        $transactiondate = "";
								$transferedamount = "";
					    	}
					    } else {
					        $rrq = getrequestdetails($conn, $companyid, $subcompanyid, $projectid, "requestno", $bblkey['referenceno']);
					        $trstatus = !empty($rrq) ? "<span class='text-dark'><i class='fa fa-clock'></i> Payment request made</span>" : "<span class='text-danger'><i class='fa fa-times-circle'></i> Not Available</span>";
					    }
					} else {
					    $trstatus = "<span class='text-danger'><i class='fa fa-clock'></i> Un-Paid</span>";
					}
					$docpath ="";
					$docpathexst="";
					if($bblkey['uploadreferencefiles']){
						$docpath = str_replace("../", "", $bblkey['uploadreferencefiles']);
						if (file_exists("../".$docpath)) {
						    $docpathexst="<a href='".$docpath."' target='_blank'>Prevew File</a>";
						}else{
							$docpathexst="N/A";
						}
					}
					$blhtml.="<tr  data-billidtr='".$bblkey['billid']."' data-bpaytoid='".$bblkey['paytoid']."' data-billdbsnotr='".$bblkey['sno']."' data-billtypetr='".$bblkey['typeofbillid']."'  data-billlocationid='".$bblkey['locationid']."' data-billduedate='".$bblkey['billduedate']."' data-billingdate='".$bblkey['billingdate']."'><td data-label='Service Provider Name' class='paytoname_btd' data-bpaytoid='".$bblkey['paytoid']."'>".$paytoname."</td><td data-label='Consumer Account Details' class='beneficiaryname_btd'>".$bblkey['beneficiaryname']."</td><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td><td data-label='Bill Date' class='billingdate_btd'>".$billingdate."</td><td data-label='Bill No' class='invoiceno_btd'>".$bblkey['billno']."</td><td data-label='Payment Due Date' class='billduedate_btd'>".$billduedate."</td><td data-label='Bill Amount' class='billamount_btd'>".$bblkey['billamount']."</td><td data-label='Bill Description' class='billdescription_btd'>".$bblkey['billdescription']."</td><td data-label='Amount Paid' class='transactionamount_btd'>".$bblkey['transferedamount']."</td><td data-label='Date of Payment' class='transactiondate_btd'>".$transactiondate."</td><td data-label='Status' class='billstatus_btd'>".$trstatus."</td><td data-label='Status' class='previewdoc_btd noexport'>".$docpathexst."</td></tr>";
				}
			}
		}
		if(!$blhtml){
	       $blhtml.="<tr  data-billidtr='' data-bpaytoid='' data-billdbsnotr='' data-billtypetr=''  data-billlocationid='' data-billduedate='' data-billingdate=''><td class='text-center' colspan='13'>No data available</td></tr>";
	    }
	}
	echo $blhtml;
}else if($action=="billfinanciayyearlist"){
  $companyid = $_POST['companyid'];
  $subcompanyid = $_POST['subcompanyid'];
  $projectid = $_POST['projectid'];
  $fffnt=getFinancialYearList_FromUtilistBill($conn,$companyid,$subcompanyid,$projectid);
  echo json_encode($fffnt);
}else  if($action=="historyofbills_htmlview"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	if (!$companyid || !$subcompanyid || !$projectid) {
	    echo "Required parameters missing.";
	    exit;
	}

	$bblm=billmasteruniversal_bychatur($conn,$companyid,$subcompanyid,$projectid,"all","");
	$blhtml="";
	if(count($bblm)>0){
		foreach ($bblm as $blmkey) {
			// Get transaction details
	        $bbl = billtransactionsdetails($conn, $companyid, $subcompanyid, $projectid, "historywithbillid", $blmkey['id']);
	        // Function to fetch data
	        $paytoname = fetchSingleValue(getpaytoinfo($conn, $companyid, $subcompanyid, "paytoid", $blmkey['paytoid']), 'paytoname');
	        $purposename = fetchSingleValue(getpurposenamewithcat($conn, $companyid, $subcompanyid, $projectid, "purposecode", $blmkey['purposecode']), 'purpose');
	        $locationname = fetchSingleValue(identificationlocationmaster_bychatur($conn, $companyid, $subcompanyid, $projectid, "id", $blmkey['identifierlocation']), 'locationname');
	        $billtypename = fetchSingleValue(billtypemasterlist_bychatur($conn, "id", $blmkey['typeofbillid']), 'typename');

			if(count($bbl)>0){
				foreach ($bbl as $bblkey) {			
					$billingdate = date_format(date_create($bblkey['billingdate']), 'd-m-Y');
					$billduedate = date_format(date_create($bblkey['billduedate']), 'd-m-Y');
					$dnoneradion="";
					if($bblkey['referenceno']){
						$dnoneradion="disabled";
					}
					$transactionno="";
					if ($bblkey['referenceno']) {
					    $checkpaidstatus = checkbeneficiarypaymentPaidorNot($conn, $companyid, $subcompanyid, $projectid, "requestno", $bblkey['referenceno'], $bblkey['referenceno'], '');

					    if (!empty($checkpaidstatus)) {
					        $trstatus = $checkpaidstatus[0]['transactionno'] ? "<span class='text-success'><i class='fa fa-check-circle'></i> Paid</span>" : "<span class='text-info'><i class='fa fa-clock'></i> Bill submitted But not paid</span>";
					    } else {
					        $rrq = getrequestdetails($conn, $companyid, $subcompanyid, $projectid, "requestno", $bblkey['referenceno']);
					        $trstatus = !empty($rrq) ? "<span class='text-dark'><i class='fa fa-clock'></i> Payment request made</span>" : "<span class='text-danger'><i class='fa fa-times-circle'></i> Not Available</span>";
					    }
					} else {
					    $trstatus = "<span class='text-danger'><i class='fa fa-clock'></i> No action so for</span>";
					}
					$blhtml.="<tr  data-billidtr='".$bblkey['billid']."' data-billdbsnotr='".$bblkey['sno']."' data-billtypetr='".$bblkey['typeofbillid']."' data-billduedate='".$bblkey['billduedate']."' data-billingdate='".$bblkey['billingdate']."'><td data-label='Billing Date' class='billingdate_btd'>".$billingdate."</td><td data-label='Beneficiary Name' class='beneficiaryname_btd'>".$bblkey['beneficiaryname']."</td><td data-label='Bill Type' class='billamount_btd'>".$bblkey['billamount']."</td><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td><td data-label='Bill Due Date' class='transactiondate_btd'>".$bblkey['transactiondate']."</td><td data-label='Status' class='billstatus_btd'>".$trstatus."</td></tr>";
				}
			}
		}
	}
	echo $blhtml;
}else if($action=="pendingbills_forrequesthtml"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	
	if (!$companyid || !$subcompanyid || !$projectid) {
	    echo "Required parameters missing.";
	    exit;
	}

	$bblm=billmasteruniversal_bychatur($conn,$companyid,$subcompanyid,$projectid,"all","");
	$blhtml="";
	if(count($bblm)>0){
		foreach ($bblm as $blmkey) {
			// Get transaction details
	        $bbl = billtransactionsdetails($conn, $companyid, $subcompanyid, $projectid, "pendingbillwithbillid", $blmkey['id']);
	        // Function to fetch data
	        $paytoname = fetchSingleValue(getpaytoinfo($conn, $companyid, $subcompanyid, "paytoid", $blmkey['paytoid']), 'paytoname');
	        // $purposename = fetchSingleValue(getpurposenamewithcat($conn, $companyid, $subcompanyid, $projectid, "purposecode", $blmkey['purposecode']), 'purpose');
	        $locationname = fetchSingleValue(identificationlocationmaster_bychatur($conn, $companyid, $subcompanyid, $projectid, "id", $blmkey['identifierlocation']), 'locationname');
	        $billtypename = fetchSingleValue(billtypemasterlist_bychatur($conn, "id", $blmkey['typeofbillid']), 'typename');
	        // print_r($bbl);
	        $statusinno="";
			if(count($bbl)>0){
				foreach ($bbl as $bblkey) {
					$trstatus = "";
					$statusinno="";
					$billingdate = date_format(date_create($bblkey['billingdate']), 'd-m-Y');
					$billduedate = date_format(date_create($bblkey['billduedate']), 'd-m-Y');
					$dnoneradion="";
					if($bblkey['referenceno']){
						$dnoneradion="disabled";
					}
					$transactionno="";
					if ($bblkey['referenceno']) {
					    $trstatus="";
					    // $checkpaidstatus = checkbeneficiarypaymentPaidorNot($conn, $companyid, $subcompanyid, $projectid, "referenceno", $bblkey['referenceno'], $bblkey['referenceno'], '');
					    // if ($checkpaidstatus!="") {
					    // 	if($checkpaidstatus[0]['paymentduedate']<date('Y-m-d')){
					    // 		$statusinno='-1';
						//         $trstatus = $checkpaidstatus[0]['transactionno']!="" ? "" : "<span class='text-danger'><i class='fa fa-clock'></i> Over Due of Bill</span>";
					    // 	}else{
					    // 		$statusinno='1';
						//         $trstatus = $checkpaidstatus[0]['transactionno']!="" ? "" : "<span class='text-info'><i class='fa fa-clock'></i> Bill submitted But not paid</span>";
					    // 	}
					    // } else {
					    //     $rrq = getrequestdetails($conn, $companyid, $subcompanyid, $projectid, "requestno", $bblkey['referenceno']);
					    //     $trstatus = !empty($rrq) ? "<span class='text-dark'><i class='fa fa-clock'></i> Payment request made</span>" : "<span class='text-danger'><i class='fa fa-times-circle'></i> Not Available</span>";
					    // }
					} else {
						$statusinno='0';
					    $trstatus = "<span class='text-warning'><i class='fa fa-clock'></i> No action so for</span>";
					}
					if($trstatus){
						$blhtml.="<tr  data-statusno='".$statusinno."' data-billidtr='".$bblkey['billid']."' data-billdbsnotr='".$bblkey['sno']."' data-billtypetr='".$bblkey['typeofbillid']."' data-billduedate='".$bblkey['billduedate']."' data-billingdate='".$bblkey['billingdate']."'><td data-label='Select' class='noexport'><input type='radio' name='billreqradio' value='".$bblkey['sno']."' ".$dnoneradion." /></td><td data-label='Payto Name' class='noexport d-none paytoname_btd' data-bpaytoid='".$bblkey['paytoid']."'>".$paytoname."</td><td data-label='Beneficiary Name' class='beneficiaryname_btd'>".$bblkey['beneficiaryname']."</td><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td><td data-label='Next Bill Date' class='billduedate_btd'>".$billingdate."</td><td data-label='Bill Due Date' class='billduedate_btd'>".$billduedate."</td><td data-label='Status' class='billstatus_btd'>".$trstatus."</td></tr>";
					}
				}
			}else{
				$billingdate = date_format(date_create($blmkey['nextbilldate']), 'd-m-Y');
				$billduedate = date_format(date_create($blmkey['billduedate']), 'd-m-Y');
				$dnoneradion="";
				$statusinno='0';
				$trstatus="<span class='text-warning'><i class='fa fa-clock'></i> No action so for</span>";
				$blhtml.="<tr data-statusno='".$statusinno."' data-billidtr='".$blmkey['id']."' data-billdbsnotr='' data-billtypetr='".$blmkey['typeofbillid']."' data-billduedate='".$blmkey['billduedate']."' data-billingdate='".$blmkey['nextbilldate']."'><td data-label='Select' class='noexport'><input type='radio' name='billreqradio' value='' ".$dnoneradion." /></td><td data-label='Payto Name' class='noexport d-none paytoname_btd' data-bpaytoid='".$blmkey['paytoid']."'>".$paytoname."</td><td data-label='Beneficiary Name' class='beneficiaryname_btd'>".$blmkey['beneficiaryname']."</td><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td><td data-label='Next Bill Date' class='billduedate_btd'>".$billingdate."</td><td data-label='Bill Due Date' class='billduedate_btd'>".$billduedate."</td><td data-label='Status' class='billstatus_btd'>".$trstatus."</td></tr>";
			}
		}
	}
	echo $blhtml;
}else if($action=="overduependingbills_forrequesthtml"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	
	if (!$companyid || !$subcompanyid || !$projectid) {
	    echo "Required parameters missing.";
	    exit;
	}

	$bblm=billmasteruniversal_bychatur($conn,$companyid,$subcompanyid,$projectid,"all","");
	// print_r($bblm)
	$blhtml="";
	if(count($bblm)>0){
		foreach ($bblm as $blmkey) {
			// Get transaction details
	        $bbl = billtransactionsdetails($conn, $companyid, $subcompanyid, $projectid, "pendingbillwithbillid", $blmkey['id']);
	        // Function to fetch data
	        $paytoname = fetchSingleValue(getpaytoinfo($conn, $companyid, $subcompanyid, "paytoid", $blmkey['paytoid']), 'paytoname');
	        // $purposename = fetchSingleValue(getpurposenamewithcat($conn, $companyid, $subcompanyid, $projectid, "purposecode", $blmkey['purposecode']), 'purpose');
	        $locationname = fetchSingleValue(identificationlocationmaster_bychatur($conn, $companyid, $subcompanyid, $projectid, "id", $blmkey['identifierlocation']), 'locationname');
	        // echo $blmkey['typeofbillid'];
	        $billtypename = fetchSingleValue(billtypemasterlist_bychatur($conn, "id", $blmkey['typeofbillid']), 'typename');
	        $statusinno="";
	        // print_r($bbl);
	        $billingdate ="";
			$billduedate ="";
			if(count($bbl)>0){
				foreach ($bbl as $bblkey) {
					$trstatus = "";
					$statusinno="";
					if($blmkey['billingdate']){
						$billingdate = date_format(date_create($bblkey['billingdate']), 'd-m-Y');
					}
					if($blmkey['billduedate']){
						$billduedate = date_format(date_create($bblkey['billduedate']), 'd-m-Y');
					}

					$dnoneradion="";
					$transactionno="";
					if ($bblkey['referenceno']!="") {
						$dnoneradion="disabled";
					    $trstatus="";
					    // $checkpaidstatus = checkbeneficiarypaymentPaidorNot($conn, $companyid, $subcompanyid, $projectid, "referenceno", $bblkey['referenceno'], $bblkey['referenceno'], '');
					    // if ($checkpaidstatus!="") {
					    // 	if($checkpaidstatus[0]['paymentduedate']<date('Y-m-d')){
					    // 		$trstatus = "";
						//         // $trstatus = $checkpaidstatus[0]['transactionno']!="" ? "" : "<span class='text-danger'><i class='fa fa-clock'></i> Over Due of Bill</span>";
					    // 	}else{
					    // 		$statusinno='1';
						//         $trstatus = "";
						//         // $trstatus = $checkpaidstatus[0]['transactionno']!="" ? "" : "<span class='text-info'><i class='fa fa-clock'></i> Bill submitted But not paid</span>";
					    // 	}
					    // } else {
					    //     $rrq = getrequestdetails($conn, $companyid, $subcompanyid, $projectid, "requestno", $bblkey['referenceno']);
					    //     $trstatus = !empty($rrq) ? "<span class='text-dark'><i class='fa fa-clock'></i> Payment request made</span>" : "<span class='text-danger'><i class='fa fa-times-circle'></i> Not Available</span>";
					    // }
					} else {
						$statusinno='0';
						if($bblkey['billduedate']<date('Y-m-d')){
						    $trstatus = "<span class='text-danger'><i class='fa fa-clock'></i> Over due for bill</span>";
						}else{
							$trstatus="";
						}
					}
					if($trstatus){
						$blhtml.="<tr  data-statusno='".$statusinno."' data-billidtr='".$bblkey['billid']."' data-billdbsnotr='".$bblkey['sno']."' data-billtypetr='".$bblkey['typeofbillid']."' data-billduedate='".$bblkey['billduedate']."' data-billingdate='".$bblkey['billingdate']."'><td data-label='Select' class='noexport'><input type='radio' name='billreqradio' value='".$bblkey['sno']."' ".$dnoneradion." /></td><td data-label='Payto Name' class='noexport d-none paytoname_btd' data-bpaytoid='".$bblkey['paytoid']."'>".$paytoname."</td><td data-label='Beneficiary Name' class='beneficiaryname_btd'>".$bblkey['beneficiaryname']."</td><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td><td data-label='Next Bill Date' class='billduedate_btd'>".$billingdate."</td><td data-label='Bill Due Date' class='billduedate_btd'>".$billduedate."</td><td data-label='Status' class='billstatus_btd'>".$trstatus."</td></tr>";
					}
				}
			}else{
				if($blmkey['nextbilldate']){
					$billingdate = date_format(date_create($blmkey['nextbilldate']), 'd-m-Y');
				}
				if($blmkey['billduedate']){
					$billduedate = date_format(date_create($blmkey['billduedate']), 'd-m-Y');
				}
				$dnoneradion="";
				$statusinno='0';
				if($blmkey['billduedate']<date('Y-m-d')){
				    $trstatus = "<span class='text-danger'><i class='fa fa-clock'></i> Over due for bill</span>";
				}else{
					$trstatus="";
				}
				if($trstatus!=""){
					$blhtml.="<tr data-statusno='".$statusinno."' data-billidtr='".$blmkey['id']."' data-billdbsnotr='' data-billtypetr='".$blmkey['typeofbillid']."' data-billduedate='".$blmkey['billduedate']."' data-billingdate='".$blmkey['nextbilldate']."'><td data-label='Select' class='noexport'><input type='radio' name='billreqradio' value='' ".$dnoneradion." /></td><td data-label='Payto Name' class='noexport d-none paytoname_btd' data-bpaytoid='".$blmkey['paytoid']."'>".$paytoname."</td><td data-label='Beneficiary Name' class='beneficiaryname_btd'>".$blmkey['beneficiaryname']."</td><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td><td data-label='Next Bill Date' class='billduedate_btd'>".$billingdate."</td><td data-label='Bill Due Date' class='billduedate_btd'>".$billduedate."</td><td data-label='Status' class='billstatus_btd'>".$trstatus."</td></tr>";
				}
			}
		}
	}
	echo $blhtml;
}else if($action=="billpaymenthtmlgrid"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortbydata = $_POST['shortbydata'];
	$bbl=billmasteruniversal_bychatur($conn,$companyid,$subcompanyid,$projectid,"all","");
	$blhtml="";
	if(count($bbl)>0){
		foreach ($bbl as $bblkey) {			
			$pty=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$bblkey['paytoid']);
			$paytoname="";
			foreach ($pty as $ptykey) {
				$paytoname=$ptykey['paytoname'];
			}

			$pppty=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$bblkey['purposecode']);
			$purposename="";
			foreach ($pppty as $pptykey) {
				$purposename=$pptykey['purpose'];
			}

			//get location name
			$idlc=identificationlocationmaster_bychatur($conn,$companyid,$subcompanyid,$projectid,"id",$bblkey['identifierlocation']);
			$locationname="";
			if(count($idlc)>0){
				foreach ($idlc as $idlckey) {
					$locationname=$idlckey['locationname'];
				}
			}

			//billtype details
			$biltyp=billtypemasterlist_bychatur($conn,"id",$bblkey['typeofbillid']);
			$billtypename ="";
			if(count($biltyp)>0){
				foreach ($biltyp as $bltykey) {
					$billtypename = $bltykey['typename'];
				}
			}
			$billduedate = date_format(date_create($bblkey['billduedate']), 'd-M-Y');
			$nextbilldate = date_format(date_create($bblkey['nextbilldate']), 'd-M-Y');
			$dnoneradion="";

			if(getDaysDifference(date('Y-m-d'),$nextbilldate)>0){
				$dnoneradion="disabled";
			}
			$transactionno="";
			if($bblkey['lastreferenceno']){
				$checkpaidstatus=checkbeneficiarypaymentPaidorNot($conn,$companyid,$subcompanyid,$projectid,"bybeneficiaryname",$bblkey['beneficiaryname'],$bblkey['lastreferenceno'],'');
				if(count($checkpaidstatus)>0){
					foreach ($checkpaidstatus as $ccbkey) {
						if($ccbkey['transactionno']){
							$transactionno=$ccbkey['transactionno'];
							$trstatus="<span class='text-success'><i class='fa fa-check-circle'></i> Payment Transfered</span>";
							$trstatusmsg="Transfer with Transaction No ".$ccbkey['transactionno']." on Date ".date('d-M-Y', strtotime($ccbkey['transactiondate']));
						}else{
							$transactionno="pending";
							$trstatus="<span class='text-dark'><i class='fa fa-clock'></i> Pending for Transaction</span>";
							$trstatusmsg="Pending for Transaction";
						}
					}

				}else{
					$rrq=getrequestdetails($conn,$companyid,$subcompanyid,$projectid,"requestno",$bblkey['lastreferenceno']);
					if(count($rrq)>0){
						$trstatus="<span class='text-dark'><i class='fa fa-clock'></i> Pending for Approval</span>";
						$trstatusmsg="Request created but pending for approval";
		
					}else{
						$trstatus="<span class='text-danger'><i class='fa fa-times-circle'></i> Not Available</span>";
						$trstatusmsg="Request Not Available";
					}
				}
			}else{
				$checkpaidstatus=checkbeneficiarypaymentPaidorNot($conn,$companyid,$subcompanyid,$projectid,"bybeneficiaryname",$bblkey['beneficiaryname']);
				if(count($checkpaidstatus)>0){
					foreach ($checkpaidstatus as $ccbkey) {
						if($ccbkey['transactionno']){
							$transactionno=$ccbkey['transactionno'];
							$trstatus="<span class='text-success'><i class='fa fa-check-circle'></i> Payment Transfered</span>";
							$trstatusmsg="Transfer with Transaction No ".$ccbkey['transactionno']." on Date ".date('d-M-Y', strtotime($ccbkey['transactiondate']));
						}else{
							$transactionno="pending";
							$trstatus="<span class='text-dark'><i class='fa fa-clock'></i> Pending for Transaction</span>";
							$trstatusmsg="Pending for Transaction";
						}
					}

				}else{
					$rrq=getrequestdetails($conn,$companyid,$subcompanyid,$projectid,"requestno",$bblkey['lastreferenceno']);
					if(count($rrq)>0){
						$trstatus="<span class='text-danger'><i class='fa fa-clock'></i> Pending for Approval</span>";
						$trstatusmsg="Request created but pending for approval";
		
					}else{
						$trstatus="<span class='text-danger'><i class='fa fa-times-circle'></i> Not Available</span>";
						$trstatusmsg="Request Not Available";
					}
				}
			}
			// echo "\n oo: ".$dnoneradion;
			if($shortbydata==="pending" && !$dnoneradion){
				$blhtml.="<tr  data-billidtr='".$bblkey['id']."' data-billdbsnotr='".$bblkey['sno']."' data-billtypetr='".$bblkey['typeofbillid']."' data-billlocationid='".$bblkey['identifierlocation']."' data-billduedate='".$bblkey['billduedate']."' data-billingdate='".$bblkey['nextbilldate']."'><td data-label='Select' class='noexport'><input type='radio' name='billreqradio' value='".$bblkey['sno']."' ".$dnoneradion." /></td><td data-label='Payto Name' class='paytoname_btd' data-bpaytoid='".$bblkey['paytoid']."'>".$paytoname."</td><td data-label='Beneficiary Name' class='beneficiaryname_btd'>".$bblkey['beneficiaryname']."</td><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td><td data-label='Next Bill Date' class='billduedate_btd'>".$nextbilldate."</td><td data-label='Bill Due Date' class='billduedate_btd'>".$billduedate."</td></tr>";
			}else if($shortbydata=="done" && $dnoneradion=="disabled"){
				$blhtml.="<tr data-billdbsno_tr='".$bblkey['sno']."' data-billtypetr='".$bblkey['typeofbillid']."' data-billlocationid='".$bblkey['identifierlocation']."' data-billduedate='".$bblkey['billduedate']."' data-billingdate='".$bblkey['nextbilldate']."'><td data-label='Select' class='noexport'><input type='radio' name='billreqradio' value='".$bblkey['sno']."' ".$dnoneradion." /></td><td data-label='Payto Name' class='paytoname_btd' data-bpaytoid='".$bblkey['paytoid']."'>".$paytoname."</td><td data-label='Beneficiary Name' class='beneficiaryname_btd'>".$bblkey['beneficiaryname']."</td><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td><td data-label='Next Bill Date' class='billduedate_btd'>".$nextbilldate."</td><td data-label='Bill Due Date' class='billduedate_btd'>".$billduedate."</td></tr>";
			}else if($shortbydata=="all"){
				$blhtml.="<tr data-billdbsno_tr='".$bblkey['sno']."' data-billtypetr='".$bblkey['typeofbillid']."' data-billlocationid='".$bblkey['identifierlocation']."' data-billduedate='".$bblkey['billduedate']."' data-billingdate='".$bblkey['nextbilldate']."'><td data-label='Select' class='noexport'><input type='radio' name='billreqradio' value='".$bblkey['sno']."' ".$dnoneradion." /></td><td data-label='Payto Name' class='paytoname_btd' data-bpaytoid='".$bblkey['paytoid']."'>".$paytoname."</td><td data-label='Beneficiary Name' class='beneficiaryname_btd'>".$bblkey['beneficiaryname']."</td><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td><td data-label='Next Bill Date' class='billduedate_btd'>".$nextbilldate."</td><td data-label='Bill Due Date' class='billduedate_btd'>".$billduedate."</td></tr>";
			}else if($shortbydata=="allwithmasterlist"){
				$blhtml.="<tr data-billdbsno_tr='".$bblkey['sno']."' data-billtypetr='".$bblkey['typeofbillid']."' data-billlocationid='".$bblkey['identifierlocation']."' data-billduedate='".$bblkey['billduedate']."' data-billingdate='".$bblkey['nextbilldate']."'><td data-label='Select' class='noexport'><input type='radio' name='billreqradio' value='".$bblkey['sno']."' /></td><td data-label='Payto Name' class='paytoname_btd' data-bpaytoid='".$bblkey['paytoid']."'>".$paytoname."</td><td data-label='Beneficiary Name' class='beneficiaryname_btd'>".$bblkey['beneficiaryname']."</td><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td><td data-label='Next Bill Date' class='d-none noexport billduedate_btd text-center'>".date('d-M', strtotime($nextbilldate))."</td><td data-label='Bill Due Date' class='d-none noexport billduedate_btd text-center'>".date('d-M', strtotime($billduedate))."</td></tr>";
			}else if($shortbydata=="allforreport"){
				$blhtml.="<tr data-billdbsno_tr='".$bblkey['sno']."' data-billtypetr='".$bblkey['typeofbillid']."' data-billlocationid='".$bblkey['identifierlocation']."' data-billduedate='".$bblkey['billduedate']."' data-billingdate='".$bblkey['nextbilldate']."'><td data-label='Select' class='noexport'><input type='radio' name='billreqradio' value='".$bblkey['sno']."' /></td><td data-label='Payto Name' class='paytoname_btd' data-bpaytoid='".$bblkey['paytoid']."'>".$paytoname."</td><td data-label='Beneficiary Name' class='beneficiaryname_btd'>".$bblkey['beneficiaryname']."</td><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td><td data-label='Next Bill Date' class='billduedate_btd'>".$nextbilldate."</td><td data-label='Bill Due Date' class='billduedate_btd'>".$billduedate."</td><td data-label='Transaction Status' class='billtransactionstatus_btd'>".$trstatus."</td><td data-label='Transaction Status Message' class='billtransactionstatusmsg_btd'>".$trstatusmsg."</td></tr>";

			}else if($shortbydata=="waitingforpayment" && $transactionno=="pending"){
				$blhtml.="<tr data-billdbsno_tr='".$bblkey['sno']."' data-billtypetr='".$bblkey['typeofbillid']."' data-billlocationid='".$bblkey['identifierlocation']."' data-billduedate='".$bblkey['billduedate']."' data-billingdate='".$bblkey['nextbilldate']."'><td data-label='Select' class='noexport'><input type='radio' name='billreqradio' value='".$bblkey['sno']."' /></td><td data-label='Payto Name' class='paytoname_btd' data-bpaytoid='".$bblkey['paytoid']."'>".$paytoname."</td><td data-label='Beneficiary Name' class='beneficiaryname_btd'>".$bblkey['beneficiaryname']."</td><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td><td data-label='Next Bill Date' class='billduedate_btd'>".$nextbilldate."</td><td data-label='Bill Due Date' class='billduedate_btd'>".$billduedate."</td><td data-label='Transaction Status' class='billtransactionstatus_btd d-none'>".$trstatus."</td><td data-label='Transaction Status Message' class='billtransactionstatusmsg_btd d-none'>".$trstatusmsg."</td></tr>";

			}

		}
	}
	echo $blhtml;
}else if($action=="waitingforpayment_billhtmlgrid"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$bilmastedata=billmasteruniversal_bychatur($conn,$companyid,$subcompanyid,$projectid,"all","");
	// print_r($bilmastedata);
	$blhtml="";
	if(count($bilmastedata)>0){
		foreach ($bilmastedata as $blmkey) {
			$bbl = billtransactionsdetails($conn, $companyid, $subcompanyid, $projectid, "pendingpaymenttransferwithbillid", $blmkey['id']);
	        // Function to fetch data
	        $paytoname = fetchSingleValue(getpaytoinfo($conn, $companyid, $subcompanyid, "paytoid", $blmkey['paytoid']), 'paytoname');
	        // $purposename = fetchSingleValue(getpurposenamewithcat($conn, $companyid, $subcompanyid, $projectid, "purposecode", $blmkey['purposecode']), 'purpose');
	        $locationname = fetchSingleValue(identificationlocationmaster_bychatur($conn, $companyid, $subcompanyid, $projectid, "id", $blmkey['identifierlocation']), 'locationname');
	        $billtypename = fetchSingleValue(billtypemasterlist_bychatur($conn, "id", $blmkey['typeofbillid']), 'typename');
	        $statusinno="";
	        // print_r($bbl);
			$ssno=1;
			if(count($bbl)>0){
				foreach ($bbl as $bblkey) {
					$trstatus = "";
					$statusinno="";
					$billingdate = date_format(date_create($bblkey['billingdate']), 'd-m-Y');
					$billduedate = date_format(date_create($bblkey['billduedate']), 'd-m-Y');

					// if(getDaysDifference(date('Y-m-d'),$nextbilldate)>0){
					// 	$dnoneradion="disabled";
					// }
					// <td data-label='Select' class='noexport'><input type='radio' name='waitingforpaymentbillradio' value='".$bblkey['sno']."' /></td>
					// <td data-label='S.No'>".$ssno."</td>
					$blhtml.="<tr data-billdbsno_tr='".$bblkey['sno']."' data-billtypetr='".$billtypename."' data-billtypeid='".$blmkey['typeofbillid']."' data-billlocationid='".$bblkey['identifierlocation']."' data-billduedate='".$bblkey['billduedate']."' data-billingdate='".$bblkey['billingdate']."'><td data-label='Bill Type' class='billtype_btd'>".$billtypename."</td><td data-label='Payment Recipient Name' class='paytoname_btd' data-bpaytoid='".$bblkey['paytoid']."'>".$paytoname."</td><td data-label='Payment Made For' class='beneficiaryname_btd'>".$bblkey['beneficiaryname']."</td><td data-label='Bill Date' class='billdate_btd'>".$billingdate."</td><td data-label='Bill No' class='billno_btd'>".$bblkey['billno']."</td><td data-label='Bill Amount' class='billamount_btd'>".$bblkey['billamount']."</td><td data-label='Payment Due Date' class='billduedate_btd'>".$billduedate."</td><td data-label='Location' class='locationid_btd'>".$locationname."</td></tr>";
					
				}
			}else{
				$blhtml.="<tr data-billdbsno_tr='' data-billtypetr='' data-billtypeid='' data-billduedate='' data-billingdate=''><td data-label='Bill Type' class='text-center' colspan='8'>No data available</td></tr>";
			}
		}
	}
	echo $blhtml;
}else if($action=="billsmasterdetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$projectid = $_POST['projectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$routurl = $_POST['routurl'];
	$bbl="";
	if(strtolower($shortby)=="all"){
		$bbl=billmasteruniversal_bychatur($conn,$companyid,$subcompanyid,$projectid,"all","",$routurl);
	}else if(strtolower($shortby)=="sno"){
		$bbl=billmasteruniversal_bychatur($conn,$companyid,$subcompanyid,$projectid,"sno",$shortbyvalue,$routurl);
	}else if(strtolower($shortby)=="id"){
		$bbl=billmasteruniversal_bychatur($conn,$companyid,$subcompanyid,$projectid,"sno",$shortbyvalue,$routurl);
	}
	// print_r($bbl);
	if($bbl){
	}else{
		$bbl=array();
	}
	echo json_encode($bbl);
}else if($action=="savetodbbilllocationdetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$departmentname = $_POST['departmentname'];
	$locationname = $_POST['locationname'];
	$loginid = $_POST['loginid'];
	$locationid = $_POST['billlocationid'];
	$rrsvb=saveOrUpdateBillLocation($conn, $companyid, $subcompanyid, $projectid, $locationid, $departmentname, $locationname, $loginid);
	echo json_encode($rrsvb);
}else if($action=="savetodbbillTypedetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$billtypename = $_POST['billtypename'];
	$billtypedescription = $_POST['billtypedescription'];
	$billtypeid = $_POST['billtypeid'];
	$loginid = $_POST['loginid'];
	$rrstvb=saveOrUpdateBillType($conn, $companyid, $subcompanyid, $projectid, $billtypeid, $billtypename, $billtypedescription, $loginid);
	echo json_encode($rrstvb);
}else if($action=="billdurationtypewithoption"){
	$billduration=billdurationmaster_bychatur($conn,"all","");
	echo json_encode($billduration);
}else if($action=="billtypewithoption"){
	$blltyp=billtypemasterlist_bychatur($conn,"all","");
	echo json_encode($blltyp);
}
?>