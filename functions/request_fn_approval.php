<?php
function requestlist_fullinfo($conn,$countryid,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue){
	if($shortby=="all"){
		$pqry="SELECT sno, request_date, request_no, requested_by, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, before_editamount, purpose_type, approval_status, status_by, requestapprovedate, paid_for_ref, authorized_by, countryid, companyid, subcompanyid, projectid, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountid, payto_accountno, payto_bankifsccode, status_cheque, pending_description, priority, is_intermediate_transfer, intermediate_transfer_type, reminder_mail_status FROM tbl_paymentrequest where (approval_status is null or approval_status='' or approval_status='pending' or approval_status='approved') and countryid='".$countryid."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and lcase(projectid)='".strtolower($projectid)."' order by sno desc";
	}else if($shortby=="requestno"){
		$pqry="SELECT sno, request_date, request_no, requested_by, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, before_editamount, purpose_type, approval_status, status_by, requestapprovedate, paid_for_ref, authorized_by, countryid, companyid, subcompanyid, projectid, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountid, payto_accountno, payto_bankifsccode, status_cheque, pending_description, priority, is_intermediate_transfer, intermediate_transfer_type, reminder_mail_status FROM tbl_paymentrequest where (approval_status is null or approval_status='' or approval_status='pending' or approval_status='approved') and countryid='".$countryid."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".strtolower($projectid)."' and request_no='".$shortbyvalue."' order by sno desc";


	}
	$pqury_rslt=$conn->query($pqry);
	if($pqury_rslt->num_rows>0){
		while($prow=$pqury_rslt->fetch_assoc()){
			$ppaymentlist[]=array('sno'=>$prow['sno'], 'request_date'=>$prow['request_date'], 'request_no'=>$prow['request_no'], 'requested_by'=>$prow['requested_by'], 'payto'=>$prow['payto'], 'payment_mode'=>$prow['payment_mode'], 'beneficiery_type'=>$prow['beneficiery_type'], 'beneficiery_name'=>$prow['beneficiery_name'], 'request_amount'=>$prow['request_amount'], 'before_editamount'=>$prow['before_editamount'], 'purpose_type'=>$prow['purpose_type'], 'approval_status'=>$prow['approval_status'], 'status_by'=>$prow['status_by'], 'requestapprovedate'=>$prow['requestapprovedate'], 'paid_for_ref'=>$prow['paid_for_ref'], 'authorized_by'=>$prow['authorized_by'], 'countryid'=>$prow['countryid'], 'companyid'=>$prow['companyid'], 'subcompanyid'=>$prow['subcompanyid'], 'projectid'=>$prow['projectid'], 'from_bankname'=>$prow['from_bankname'], 'from_accountid'=>$prow['from_accountid'], 'from_accountno'=>$prow['from_accountno'], 'payto_bankname'=>$prow['payto_bankname'], 'payto_accountid'=>$prow['payto_accountid'], 'payto_accountno'=>$prow['payto_accountno'], 'payto_bankifsccode'=>$prow['payto_bankifsccode'], 'status_cheque'=>$prow['status_cheque'], 'pending_description'=>$prow['pending_description'], 'priority'=>$prow['priority'], 'is_intermediate_transfer'=>$prow['is_intermediate_transfer'], 'intermediate_transfer_type'=>$prow['intermediate_transfer_type'], 'reminder_mail_status'=>$prow['reminder_mail_status']);
		}
	}else{
		$ppaymentlist=array();
	}
	return $ppaymentlist;
}
function request_fullinfo($conn,$countryid,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue){
	if($shortby=="all"){		
		$pqry="SELECT p.sno as psno, p.request_date, p.request_no, p.requested_by, p.payto, p.payment_mode, p.beneficiery_type, p.beneficiery_name, p.request_amount, p.before_editamount, p.purpose_type, p.approval_status, p.status_by, p.requestapprovedate, p.paid_for_ref, p.authorized_by, p.countryid, p.companyid, p.subcompanyid, p.projectid, p.from_bankname, p.from_accountid, p.from_accountno, p.payto_bankname, p.payto_accountid, p.payto_accountno, p.payto_bankifsccode, p.status_cheque, p.pending_description, p.priority, p.is_intermediate_transfer, p.intermediate_transfer_type, p.reminder_mail_status,b.sno as bsno,b.beneficiery_name,b.ammount,b.purpose_type,b.purpose_category,b.purpose,b.purpose_code,b.description,b.document_status,b.invoice_no,b.invoice_type,b.blobfileid,b.blobfile_tblname FROM tbl_paymentrequest p inner join tbl_paymentrequest_breakdown b where p.request_no=b.request_no and (p.approval_status is null or p.approval_status='' or p.approval_status='pending' or p.approval_status='approved') and (b.Tedit is null or b.Tedit='') and p.countryid='".$countryid."' and p.companyid='".$companyid."' and p.subcompanyid='".$subcompanyid."' and lcase(p.projectid)='".strtolower($projectid)."'";
	}else if($shortby=="requestno"){
		$pqry="SELECT p.sno as psno, p.request_date, p.request_no, p.requested_by, p.payto, p.payment_mode, p.beneficiery_type, p.beneficiery_name, p.request_amount, p.before_editamount, p.purpose_type, p.approval_status, p.status_by, p.requestapprovedate, p.paid_for_ref, p.authorized_by, p.countryid, p.companyid, p.subcompanyid, p.projectid, p.from_bankname, p.from_accountid, p.from_accountno, p.payto_bankname, p.payto_accountid, p.payto_accountno, p.payto_bankifsccode, p.status_cheque, p.pending_description, p.priority, p.is_intermediate_transfer, p.intermediate_transfer_type, p.reminder_mail_status,b.sno as bsno,b.beneficiery_name,b.ammount,b.purpose_type,b.purpose_category,b.purpose,b.purpose_code,b.description,b.document_status,b.invoice_no,b.invoice_type,b.blobfileid,b.blobfile_tblname FROM tbl_paymentrequest p inner join tbl_paymentrequest_breakdown b where p.request_no=b.request_no and (p.approval_status is null or p.approval_status='' or p.approval_status='pending' or p.approval_status='approved') and (b.Tedit is null or b.Tedit='') and p.countryid='".$countryid."' and p.companyid='".$companyid."' and p.subcompanyid='".$subcompanyid."' and lcase(p.projectid)='".strtolower($projectid)."' and p.request_no='".$shortbyvalue."'";


	}else if($shortby=="sno"){
		$pqry="SELECT p.sno as psno, p.request_date, p.request_no, p.requested_by, p.payto, p.payment_mode, p.beneficiery_type, p.beneficiery_name, p.request_amount, p.before_editamount, p.purpose_type, p.approval_status, p.status_by, p.requestapprovedate, p.paid_for_ref, p.authorized_by, p.countryid, p.companyid, p.subcompanyid, p.projectid, p.from_bankname, p.from_accountid, p.from_accountno, p.payto_bankname, p.payto_accountid, p.payto_accountno, p.payto_bankifsccode, p.status_cheque, p.pending_description, p.priority, p.is_intermediate_transfer, p.intermediate_transfer_type, p.reminder_mail_status,b.sno as bsno,b.beneficiery_name,b.ammount,b.purpose_type,b.purpose_category,b.purpose,b.purpose_code,b.description,b.document_status,b.invoice_no,b.invoice_type,b.blobfileid,b.blobfile_tblname FROM tbl_paymentrequest p inner join tbl_paymentrequest_breakdown b where p.request_no=b.request_no and (p.approval_status is null or p.approval_status='' or p.approval_status='pending' or p.approval_status='approved') and (b.Tedit is null or b.Tedit='') and p.countryid='".$countryid."' and p.companyid='".$companyid."' and p.subcompanyid='".$subcompanyid."' and lcase(p.projectid)='".strtolower($projectid)."' and b.sno='".$shortbyvalue."'";

	}
	$pqury_rslt=$conn->query($pqry);
	if($pqury_rslt->num_rows>0){
		while($prow=$pqury_rslt->fetch_assoc()){
			$paymentlist[]=array('psno'=>$prow['psno'], 'request_date'=>$prow['request_date'], 'request_no'=>$prow['request_no'], 'requested_by'=>$prow['requested_by'], 'payto'=>$prow['payto'], 'payment_mode'=>$prow['payment_mode'], 'beneficiery_type'=>$prow['beneficiery_type'], 'beneficiery_name'=>$prow['beneficiery_name'], 'request_amount'=>$prow['request_amount'], 'before_editamount'=>$prow['before_editamount'], 'purpose_type'=>$prow['purpose_type'], 'approval_status'=>$prow['approval_status'], 'status_by'=>$prow['status_by'], 'requestapprovedate'=>$prow['requestapprovedate'], 'paid_for_ref'=>$prow['paid_for_ref'], 'authorized_by'=>$prow['authorized_by'], 'countryid'=>$prow['countryid'], 'companyid'=>$prow['companyid'], 'subcompanyid'=>$prow['subcompanyid'], 'projectid'=>$prow['projectid'], 'from_bankname'=>$prow['from_bankname'], 'from_accountid'=>$prow['from_accountid'], 'from_accountno'=>$prow['from_accountno'], 'payto_bankname'=>$prow['payto_bankname'], 'payto_accountid'=>$prow['payto_accountid'], 'payto_accountno'=>$prow['payto_accountno'], 'payto_bankifsccode'=>$prow['payto_bankifsccode'], 'status_cheque'=>$prow['status_cheque'], 'pending_description'=>$prow['pending_description'], 'priority'=>$prow['priority'], 'is_intermediate_transfer'=>$prow['is_intermediate_transfer'], 'intermediate_transfer_type'=>$prow['intermediate_transfer_type'], 'reminder_mail_status'=>$prow['reminder_mail_status'], 'bsno'=>$prow['bsno'], 'beneficiery_name'=>$prow['beneficiery_name'], 'ammount'=>$prow['ammount'], 'purpose_type'=>$prow['purpose_type'], 'purpose_category'=>$prow['purpose_category'], 'purpose'=>$prow['purpose'], 'purpose_code'=>$prow['purpose_code'], 'description'=>$prow['description'], 'document_status'=>$prow['document_status'], 'invoice_no'=>$prow['invoice_no'], 'invoice_type'=>$prow['invoice_type'], 'blobfile'=>$prow['blobfileid'], 'blobfile_tblname'=>$prow['blobfile_tblname']);
		}
	}else{
		$paymentlist=array();
	}
	return $paymentlist;
}

function PaytoHaveImprestAccount($conn, $companyid, $subcompanyid, $projectid, $paytoid, $requestcategory,$requestAmount=0) {
    $projectIds = explode(",", $projectid);
    $projectIdStr = "'" . implode("','", array_map('trim', $projectIds)) . "'";    
    $imppaytoQuery = "SELECT p.sno, p.imprestpaytoid, p.payto, p.mobile_no, p.email_id, 
                             p.paytobankname, p.paytoaccountno, p.paytoifsccode, p.loginid, 
                             prj.companyid, prj.subcompanyid, prj.projectid 
                      FROM imprest_authorizedperson p 
                      JOIN imprest_authorizedproject prj ON p.imprestpaytoid = prj.imprestpaytoid 
                      WHERE (p.tedit IS NULL OR p.tedit = '') 
                        AND prj.companyid = '$companyid' 
                        AND prj.subcompanyid = '$subcompanyid' 
                        AND prj.projectid IN ($projectIdStr) 
                        AND LCASE(p.imprestpaytoid) = '" . strtolower($paytoid) . "'";
    
    $imppaytoResult = $conn->query($imppaytoQuery);

    if ($imppaytoResult && $imppaytoResult->num_rows > 0) {
        $immkey = $imppaytoResult->fetch_assoc();
        $paytoName = $immkey['payto'];
        $query = "SELECT imprest_payto, from_accountid, to_accountid, 
                         COALESCE(SUM(imprest_amount), 0) AS ttl_imprest, 
                         COALESCE(SUM(adjustment_amount), 0) AS ttl_padjustment, 
                         (COALESCE(SUM(imprest_amount), 0) - COALESCE(SUM(adjustment_amount), 0)) AS rstimprest 
                  FROM imprest_account 
                  WHERE (Tedit IS NULL OR Tedit = '') 
                    AND companyid = '$companyid' 
                    AND subcompanyid = '$subcompanyid' 
                    AND projectid = '$projectid' 
                    AND paytoid = '" . $immkey['imprestpaytoid'] . "'";
        
        $quryresult = $conn->query($query) or die($conn->error);
        $restimprest = 0;
        $war_message="";
        $war_action="";
        $war_statementbtn="";
        $paytoName = "";
        $from_accountid = "";
        $to_accountid = "";
        if ($quryresult->num_rows > 0) {
            $crow = $quryresult->fetch_assoc();
            $restimprest = $crow['rstimprest'] ?? 0;
            $paytoName = $crow['imprest_payto'] ?? '';
            $from_accountid = $crow['from_accountid'] ?? '';
            $to_accountid = $crow['to_accountid'] ?? '';
            $purposeType = strtolower($requestcategory);
            $requestAmount = $requestAmount;
            if ($restimprest >= 0) {
                if (($purposeType == 'expenditure' || $purposeType == 'payment') && $restimprest > 0) {
                    $war_message = "Pending imprest amount of ₹ $restimprest ";
                    $war_action="<span class='badge badge-warning m-2 change_purposetype_imprest'  style='cursor:pointer;'>Convert to Imprest Adjustment</span>";
                    $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewimpreststatement'>View imprest statement</span>";
                } else if ($purposeType == 'imprest adjustment') {                    
                    $war_message = "Pending imprest amount of ₹ $restimprest ";
                    $war_action="<span class='badge badge-warning m-2 change_purposetype_imprest'  style='cursor:pointer;'>Convert to Pay full Payment (amount ₹ $requestAmount)</span>";
                    $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewimpreststatement'>View imprest statement</span>";
                } else if ($restimprest > 0) {
                     $war_message = "Warning: An imprest amount ₹ $restimprest is still pending of $paytoName. Please take necessary action to adjust it.";
                    $war_action="<span class='badge badge-warning m-2 change_purposetype_imprest'  style='cursor:pointer;'>Convert to Imprest Adjustment</span>";
                    $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewimpreststatement'>View imprest statement</span>";
                }
            } else {
                if ($purposeType == 'expenditure' || $purposeType == 'payment') {
                    $war_message = " An Amount ₹ $restimprest has already been expended by $paytoName against imprest.";
                    $war_action="<span class='badge badge-warning m-2 change_purposetype_imprest'  style='cursor:pointer;'>Convert to Imprest Adjustment</span>";
                    $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewimpreststatement'>View imprest statement</span>";
                }else{
                    $war_message = " An Amount ₹ $restimprest has already been expended by $paytoName against imprest.";
                    $war_action="Cannot convert this request to imprest or adjustment. ";
                    $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewimpreststatement'>View imprest statement</span>";
                }
            }
            if($war_message){
                return "<tr data-paytoid='".$paytoid."' data-fromaccountid='".$from_accountid."'><td>Imprest</td><td>".$war_message."</td><td>".$war_statementbtn."</td><td>".$war_action."</td></tr>";
            }

        }
    }
}

function PaytoHavePartPayment($conn, $companyid, $subcompanyid, $projectid, $paytoid, $requestcategory,$requestAmount=0) {
    $query = "SELECT imprest_payto, from_accountid, to_accountid, 
                     COALESCE(SUM(imprest_amount), 0) AS ttl_partial, 
                     COALESCE(SUM(adjustment_amount), 0) AS ttl_padjustment, 
                     (COALESCE(SUM(imprest_amount), 0) - COALESCE(SUM(adjustment_amount), 0)) AS restpartial 
              FROM partial_account 
              WHERE (Tedit IS NULL OR Tedit = '') 
                AND companyid = '$companyid' 
                AND subcompanyid = '$subcompanyid' 
                AND projectid = '$projectid' 
                AND paytoid = '" . $paytoid . "'";
    
    $quryresult = $conn->query($query) or die($conn->error);
    $restimprest = 0;
    $war_message="";
    $war_action="";
    $war_statementbtn="";
    $paytoName = "";
    $from_accountid = "";
    $to_accountid = "";
    if ($quryresult->num_rows > 0) {
        $crow = $quryresult->fetch_assoc();
        $restpartial = $crow['restpartial'] ?? 0;
        $paytoName = $crow['imprest_payto'] ?? '';
        $from_accountid = $crow['from_accountid'] ?? '';
        $to_accountid = $crow['to_accountid'] ?? '';
        $purposeType = strtolower($requestcategory);
        if ($restpartial >= 0) {
            if ($purposeType == 'expenditure' || $purposeType == 'payment' && $restpartial>0) {
                $war_message = "We have already paid amount of ₹ $restpartial as Advance ";
                $war_action="<span class='badge badge-warning m-2 change_purposetype_partpayment'  style='cursor:pointer;'>Convert to Part Payment Adjustment</span>";
                $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewpartpaymentstatement'>View partpayment statement</span>";
            } else if ($purposeType == 'partail adjustment' || $purposeType == 'partial payment adjustment' || $purposeType == 'partpayment adjustment' || $purposeType =='part payment adjustment') {
                
                $war_message = "We have already paid amount of ₹ $restpartial ";
                $war_action="<span class='badge badge-warning m-2 change_purposetype_partpayment'  style='cursor:pointer;'>Convert to Pay full Payment (amount ₹ $requestAmount)</span>";
                $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewpartpaymentstatement'>View partpayment statement</span>";
            } else if ($purposeType == 'partial payment' || $purposeType == 'partial' || $purposeType == 'partpayment' || $purposeType =='part payment') {
                
                $war_message = "We have already paid amount of ₹ $restpartial ";
                $war_action="<span class='badge badge-warning m-2 change_purposetype_partpayment'  style='cursor:pointer;'>Convert to Pay full Payment (amount ₹ $requestAmount)</span>";
                $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewpartpaymentstatement'>View partpayment statement</span>";
            } else if ($restimprest > 0) {
                 $war_message = "Warning: We have already paid amount ₹ $restpartial is still pending of $paytoName. Please take necessary action to adjust it.";
                $war_action="<span class='badge badge-warning m-2 change_purposetype_partpayment'  style='cursor:pointer;'>Convert to partpayment adjustment</span>";
                $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewpartpaymentstatement'>View partpayment statement</span>";
            }
        }
        else {
            if ($purposeType == 'expenditure' || $purposeType == 'payment') {
                    $war_message = " An Amount ₹ $restpartial has already been expended by $paytoName against partpayment.";
                    $war_action="<span class='badge badge-warning m-2 change_purposetype_partpayment'  style='cursor:pointer;'>Convert to partpayment adjustment</span>";
                    $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewpartpaymentstatement'>View partpayment statement</span>";
                }else{
                     $war_message = " An Amount ₹ $restpartial has already been expended by $paytoName against partpayment.";
                    $war_action="Cannot convert this request to part payment or adjustment. ";
                     $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewpartpaymentstatement'>View partpayment statement</span>";
                }
        }
        if($war_message){
            return "<tr data-paytoid='".$paytoid."' data-fromaccountid='".$from_accountid."'><td>Part-Payment</td><td>".$war_message."</td><td>".$war_statementbtn."</td><td>".$war_action."</td></tr>";        
        }
    }
}

function PaytoHavePaymentOnAccount($conn, $companyid, $subcompanyid, $projectid, $paytoid, $requestcategory,$requestAmount=0) {
    $impq=$conn->query("SELECT sno, companyid, subcompanyid, projectid, paytoid, paytoname,bankname,accountno,ifsccode,panno,gstno,mobileno,emailaddress FROM supplier_authorizedperson WHERE (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid in ('".str_replace(",", "','", $projectid)."') and lcase(paytoid)='".strtolower($paytoid)."'");
    $paytoName = "";
    $from_accountid = "";
    $restbalance = 0;
    $war_message="";
    $war_action="";
    $war_statementbtn="";        
    if($impq->num_rows>0){
        $pcrow=$impq->fetch_assoc();
        $paytoName_OR = $pcrow['paytoname'] ?? '';
        $query = "SELECT paytoname, from_accountid, 
                         COALESCE(SUM(supplier_amount), 0) AS ttl_paidamount, 
                         COALESCE(SUM(adjustment_amount), 0) AS ttl_padjustment, 
                         (COALESCE(SUM(supplier_amount), 0) - COALESCE(SUM(adjustment_amount), 0)) AS restbalance 
        FROM supplier_account 
        WHERE (Tedit IS NULL OR Tedit = '') 
        AND companyid = '$companyid' 
        AND subcompanyid = '$subcompanyid' 
        AND projectid = '$projectid' 
        AND paytoid = '" . $paytoid . "'";
        $quryresult = $conn->query($query) or die($conn->error);
        if ($quryresult->num_rows > 0) {
            $crow = $quryresult->fetch_assoc();
            $restbalance = $crow['restbalance'] ?? 0;
            $paytoName = $crow['paytoname'] ?? '';
            $from_accountid = $crow['from_accountid'] ?? '';
            $purposeType = strtolower($requestcategory);
            if ($restbalance >= 0 && $paytoName!="") {
                if ($purposeType == 'expenditure' || $purposeType == 'payment' && $restbalance>0) {
                    $war_message = "We have already paid amount of ₹ $restbalance as Advance ";
                    $war_action="<span class='badge badge-warning m-2'>N/A</span>";
                    $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewsuppiyerstatement'>View suppliyer statement</span>";
                }
            }else if ($paytoName!=""){
                if ($purposeType == 'expenditure' || $purposeType == 'payment') {
                        $war_message = " An Amount ₹ $restbalance has already been expended by $paytoName against suppliyer invoices.";
                        $war_action="<span class='badge badge-warning m-2'>N/A</span>";
                        $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewpartpaymentstatement'>View suppliyer statement</span>";
                    }else{
                        $war_message = " An Amount ₹ $restbalance has already been expended by $paytoName against suppliyer invoices.";
                        $war_action="<span class='badge badge-warning m-2'>N/A</span>";
                        $war_statementbtn="<span class='badge badge-primary m-2' style='cursor:pointer;' id='viewpartpaymentstatement'>View suppliyer statement</span>";
                    }
            }else{
                $war_message = $paytoName_OR." is a registered with us as suppliyer";
                $war_action = "<span class='badge badge-warning m-2'>N/A</span>";
                $war_statementbtn="<span class='badge badge-primary m-2''>N/A</span>";
            }
            
            if($war_message!=""){
                return "<tr data-paytoid='".$paytoid."' data-fromaccountid='".$from_accountid."'><td>suppliyer-Payment</td><td>".$war_message."</td><td>".$war_statementbtn."</td><td>".$war_action."</td></tr>";        
            }
        }
    }
}

function PaytoHaveLoan($conn, $companyid, $subcompanyid, $projectid, $paytoid, $requestcategory,$requestAmount=0) {
    $query = "SELECT loan_payto, from_accountid, to_accountid, to_accountno, 
                     COALESCE(SUM(loan_amount), 0) AS ttl_loan, 
                     COALESCE(SUM(loan_returnamount), 0) AS ttl_returnloan, 
                     (COALESCE(SUM(loan_amount), 0) - COALESCE(SUM(loan_returnamount), 0)) AS restloan 
              FROM tbl_loanaccount_summary 
              WHERE (Tedit IS NULL OR Tedit = '') 
                AND companyid = '$companyid' 
                AND subcompanyid = '$subcompanyid' 
                AND projectid = '$projectid' 
                AND loanid = '" . $paytoid . "'";
    
    $quryresult = $conn->query($query) or die($conn->error);
    $war_message="";
    $war_action="";
    $war_statementbtn="";
    $ttr="";
    $restloan = "";
    $paytoName = "";
    $from_accountid = "";
    $to_accountid = "";
    if ($quryresult->num_rows > 0) {
        while($crow = $quryresult->fetch_assoc()){
            $restloan = $crow['restloan'] ?? 0;
            $paytoName = $crow['loan_payto'] ?? '';
            $from_accountid = $crow['from_accountid'] ?? '';
            $to_accountid = $crow['to_accountid'] ?? '';
            $ccq=$conn->query("SELECT accountid,paytoid,bankid,accountname,accountno FROM tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and accountid='".$from_accountid."' and accountid<>''");
            $fromaccountname ="";
            $fromaccountno ="";
            if($ccq->num_rows>0){
                $accrow=$ccq->fetch_assoc();
                $fromaccountname = $accrow['accountname'] ?? '';
                $fromaccountno = $accrow['accountno'] ?? '';
            }
            $purposeType = strtolower($requestcategory);
            if ($restloan >= 0) {
                if ($purposeType == 'expenditure' || $purposeType == 'payment' && $restloan>0) {
                    $war_message = "Balance loan amount is ₹ $restloan ";
                     $war_action="<span class='badge badge-warning m-2 change_purposetype_loan'  style='cursor:pointer;'>Request converted to as loan</span>";
                    $war_statementbtn="<span class='badge badge-primary m-2' id='viewloanstatement' style='cursor:pointer;'>View loan statement</span>";
                    $ttr .= "<tr data-paytoid='".$paytoid."' data-fromaccountid='".$from_accountid."' data-toaccountid='".$to_accountid."'><td>Loan</td><td>".$war_message."</td><td>".$war_statementbtn."</td><td>".$war_action."</td></tr>";
                }else if (($purposeType == 'loan' || $purposeType == 'loan payment') && $restloan>0) {
                    $war_message = "Balance loan amount is ₹ $restloan ";
                     $war_action="<span class='badge badge-warning m-2' onclick=\"ChangeCategoryOfRequestModal('hidden_rooturl','','','')\"  style='cursor:pointer;'>Request converte To</span>";
                    $war_statementbtn="<span class='badge badge-primary m-2' id='viewloanstatement' style='cursor:pointer;'>View loan statement</span>";
                    $ttr .= "<tr data-paytoid='".$paytoid."' data-fromaccountid='".$from_accountid."' data-toaccountid='".$to_accountid."'><td>Loan</td><td>".$war_message."</td><td>".$war_statementbtn."</td><td>".$war_action."</td></tr>";
                }else{

                }
            }
        }
        return $ttr;
    }
}

function PaytoHaveCashAccount($conn, $companyid, $subcompanyid, $projectid, $paytoid, $requestcategory,$requestAmount=0) {
    $query = "SELECT cash_payto, from_accountid, to_accountid, 
                     COALESCE(SUM(cash_amount), 0) AS ttl_cash, 
                     COALESCE(SUM(adjustment_amount), 0) AS ttl_padjustment, 
                     (COALESCE(SUM(cash_amount), 0) - COALESCE(SUM(adjustment_amount), 0)) AS restcash 
              FROM cash_account 
              WHERE (Tedit IS NULL OR Tedit = '') 
                AND companyid = '$companyid' 
                AND subcompanyid = '$subcompanyid' 
                AND projectid = '$projectid' 
                AND paytoid = '" . $paytoid . "'";
    
    $quryresult = $conn->query($query) or die($conn->error);
    $war_message="";
    $war_action="";
    $war_statementbtn="";
    $ttr="";
    $restcash = "";
    $paytoName = "";
    $from_accountid = "";
    $to_accountid = "";
    if ($quryresult->num_rows > 0) {
        while($crow = $quryresult->fetch_assoc()){
            $restcash = $crow['restcash'] ?? 0;
            $paytoName = $crow['cash_payto'] ?? '';
            $from_accountid = $crow['from_accountid'] ?? '';
            $to_accountid = $crow['to_accountid'] ?? '';
            $ccq=$conn->query("SELECT accountid,paytoid,bankid,accountname,accountno,account_type FROM tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and accountid='".$from_accountid."' and accountid<>''");
            $fromaccountname ="";
            $fromaccountno ="";
            if($ccq->num_rows>0){
                $accrow=$ccq->fetch_assoc();
                $fromaccountname = $accrow['accountname'] ?? '';
                $fromaccountno = $accrow['accountno'] ?? '';
            }

            $ccqq=$conn->query("SELECT accountid,paytoid,bankid,accountname,accountno,account_type FROM tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and accountid='".$to_accountid."' and accountid<>''");
            $toaccountname ="";
            $toaccountno ="";
            $account_type ="";
            if($ccqq->num_rows>0){
                $qaccrow=$ccqq->fetch_assoc();
                $toaccountname = $qaccrow['accountname'] ?? '';
                $toaccountno = $qaccrow['accountno'] ?? '';
                $account_type = $qaccrow['account_type'] ?? '';
            }

            $purposeType = strtolower($requestcategory);
            if ($restcash >= 0) {
                if ($purposeType == 'expenditure' || $purposeType == 'payment' && $restcash>0) {
                    $war_message = "Balance of cash amount is ₹ $restcash  in cash account <br/><span style='color:red;'><b>Warning :-</b> The request cannot proceed as a standard payment because it involves a cash account. You may either convert it to a cash account payment or reject the request.</span>";
                     $war_action="<span class='badge badge-warning m-2' onclick=\"ChangeCategoryOfRequestModal('hidden_rooturl','','','')\"  style='cursor:pointer;'>Request converte for cash account</span>";
                    $war_statementbtn="<span class='badge badge-primary m-2' id='viewcashstatement' style='cursor:pointer;'>View cash statement</span>";
                    $ttr .= "<tr data-paytoid='".$paytoid."' data-fromaccountid='".$from_accountid."' data-toaccountid='".$to_accountid."'><td>Cash-Account</td><td>".$war_message."</td><td>".$war_statementbtn."</td><td>".$war_action."</td></tr>";
                }else if (($purposeType == 'cash' || $purposeType == 'cash payment') && $restcash>0) {
                    $war_message = "Balance of cash amount is ₹ $restcash  in cash account ";
                     $war_action="<span class='badge badge-warning m-2'>N/A</span>";
                    $war_statementbtn="<span class='badge badge-primary m-2' id='viewcashstatement' style='cursor:pointer;'>View cash statement</span>";
                    $ttr .= "<tr data-paytoid='".$paytoid."' data-fromaccountid='".$from_accountid."' data-toaccountid='".$to_accountid."'><td>Cash-Account</td><td>".$war_message."</td><td>".$war_statementbtn."</td><td>".$war_action."</td></tr>";
                }
            }else if($purposeType == 'cash' || $purposeType == 'cash payment') {
                 $war_message = " An Amount ₹ ".$restcash." has already been expended by ".$paytoName." in cash account.";
                
                $war_action="<span class='badge badge-warning m-2'>N/A</span>";
                $war_statementbtn="<span class='badge badge-primary m-2' id='viewcashstatement' style='cursor:pointer;'>View cash statement</span>";

                $ttr .= "<tr data-paytoid='".$paytoid."' data-fromaccountid='".$from_accountid."' data-toaccountid='".$to_accountid."'><td>Cash-Account</td><td>".$war_message."</td><td>".$war_statementbtn."</td><td>".$war_action."</td></tr>";
            }
        }
        return $ttr;
    }
}

function checkPaytoIsInternalBak($conn, $companyid, $subcompanyid, $projectid, $paytoid) {
    $ccq=$conn->query("SELECT accountid,paytoid,bankid,account_type,accountname,accountno,ifsccode,bankname FROM tbl_master_account where (tedit is null or tedit='') and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and paytoid='".$paytoid."' and accountid<>'' and paytoid<>''");
    $intbank=array();
    if($ccq->num_rows>0){
        while($accrow=$ccq->fetch_assoc()){
            $intbank[] = $accrow;
        }
    }
    return $intbank;
}

?>