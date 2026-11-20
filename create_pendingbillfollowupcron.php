<?php
require('connection.php');
require('functions/function_master.php');
require('sendemail.php');
$ggsoqry="SELECT companyid, subcompanyid, projectid, id, responsablepursonname FROM strs_billmaster_universal WHERE ifnull(tedit,'')='' group by responsablepursonname, projectid";
$ggsoqryrr=$conn->query($ggsoqry) or die($conn->error);
if($ggsoqryrr->num_rows>0){
	while($grow=$ggsoqryrr->fetch_assoc()){
		// responsablepursonname
		$eqq="SELECT loginname, loginid, mobileno, emailid FROM login_user_master WHERE (tedit is null or tedit='') and loginid='".$grow['responsablepursonname']."' and emailid<>'' and lcase(isActive)='yes'";
		$lguq=$conn->query($eqq);
		$sendtoemailaddress="";
		$sendtologinname="";
		if($lguq->num_rows>0){
			while($lrow=$lguq->fetch_assoc()){
				$sendtoemailaddress=$lrow['emailid'];
				$sendtologinname=$lrow['loginname'];
			}
			$soqry="SELECT sno, companyid, subcompanyid, projectid, id, typeofbill, subproject, identifierlocation, purposecode, responsablepursonname, billdescription, billduration, billduedate, paytoid, beneficiaryname, nextbilldate,followupdate, DATEDIFF(nextbilldate, ifnull(followupdate,CURDATE())) as diffday FROM strs_billmaster_universal WHERE ifnull(tedit,'')='' and companyid='".$grow['companyid']."' and subcompanyid='".$grow['subcompanyid']."' and projectid='".$grow['projectid']."' and responsablepursonname='".$grow['responsablepursonname']."' and DATEDIFF(ifnull(followupdate,CURDATE()),nextbilldate)in(1) and month(nextbilldate)=month(now()) and year(nextbilldate)=year(now())";
			$qqres=$conn->query($soqry);

			$insq="INSERT INTO strs_taskmanagement (`companyid`, `subcompanyid`, `projectid`, `id`, `to`, `descriptionoftask`, `complettiontime`, `radiovalue`, `loginid`) VALUES";
			$sqlrr="";
			$sssno=1;
			$blhtml="";
			if($qqres->num_rows>0){
				$blhtml="<table style='width:100%; border-collapse: collapse; border: 1px solid black; padding: 8px;'><thead><tr><th style='border: 1px solid black; padding: 8px; text-align:left;'>S.No</th><th style='border: 1px solid black; padding: 8px; text-align:left;'>Beneficiary of Bill</th><th style='border: 1px solid black; padding: 8px; text-align:left;'>Bill Type</th><th style='border: 1px solid black; padding: 8px; text-align:left;'>Location</th><th style='border: 1px solid black; padding: 8px; text-align:left;'>Bill Date</th></tr><thead><tbody>";
				while($bblkey=$qqres->fetch_assoc()){
					$descriptionbill="";
					//get location name
					$billoc=$conn->query($idlq="SELECT sno, companyid, subcompanyid, projectid, id, locationname FROM strs_idntlocation_master WHERE ifnull(tedit,'')='' and companyid='".$bblkey['companyid']."' and subcompanyid='".$bblkey['subcompanyid']."' and projectid='".$bblkey['projectid']."' and id='".$bblkey['identifierlocation']."'");
					$locationname="";
					if($billoc->num_rows>0){
						while($idlckey=$billoc->fetch_assoc()) {
							$locationname=$idlckey['locationname'];
						}
					}

					//billtype details
					$biltypq=$conn->query("SELECT sno, id, typename FROM strs_billtypemaster_universal WHERE ifnull(tedit,'')='' and id='".$bblkey['typeofbill']."'");
					$billtypename ="";
					if($biltypq->num_rows>0){
						while($bltykey= $biltypq->fetch_assoc()) {
							$billtypename = $bltykey['typename'];
						}
					}

					$billduedate = date_format(date_create($bblkey['billduedate']), 'd-M-Y');
					$nextbilldate = date_format(date_create($bblkey['nextbilldate']), 'd-M-Y');
					$dnoneradion="";
					
					$blhtml.="<tr><td style='border: 1px solid black; padding: 8px;'>".$sssno."</td><td style='border: 1px solid black; padding: 8px;'>".$bblkey['beneficiaryname']."</td><td style='border: 1px solid black; padding: 8px;'>".$billtypename."</td><td style='border: 1px solid black; padding: 8px;'>".$locationname."</td><td style='border: 1px solid black; padding: 8px;'>".$nextbilldate."</td></tr>";
					$sssno++;
					$maxqry = "SELECT IFNULL(MAX(CAST(id AS UNSIGNED)), 0) + 1 AS maxid FROM strs_taskmanagement where projectid='".$bblkey['projectid']."'";
					$result = $conn->query($maxqry);
					$row = $result->fetch_assoc();
					$maxid = $row['maxid']; 
					$descriptionbill= "Bill of ".$bblkey['beneficiaryname']." for location ".$bblkey['identifierlocation'].", Bill date is ".$nextbilldate." and due date is ".$billduedate;

					$days=getDaysDifference($billduedate,$nextbilldate);
					if($sqlrr){
			            $sqlrr= $sqlrr.",('".$bblkey['companyid']."', '".$bblkey['subcompanyid']."', '".$bblkey['projectid']."', '".$maxid."', '".addslashes($grow['responsablepursonname'])."', '".addslashes($descriptionbill)."', '".$billduedate."', '".addslashes($days)."','web')";
		            }else{
		            	$sqlrr= "('".$bblkey['companyid']."', '".$bblkey['subcompanyid']."', '".$bblkey['projectid']."', '".$maxid."', '".addslashes($grow['responsablepursonname'])."', '".addslashes($descriptionbill)."', '".$billduedate."', '".addslashes($days)."','web')";
		            }						
				}
				$blhtml.="</tbody></table>";
			}
			if($sqlrr){
				$qresult=$insq.$sqlrr;
    			$conn->query($qresult);
			}
			// $sendtoemailaddress="chatursingh011@gmail.com";
			$sendTo = [$sendtoemailaddress];
			$sendcc = [];
			$sendbcc = [];
			$attachments = [];
			$msubject = "Task Assigned for Bill Payment";
			$m_msgbody = " <html>
			    <body>
    				<p>Dear ".$sendtologinname.",</p><p>This is a reminder that a bill payment task has been assigned to you for processing. Please review the details below and ensure that payment is completed before the due date to avoid any late fees or service interruptions. Details mentioned as below.</p><br>".$blhtml."<br/> <p>Kindly prioritize this task and complete the payment at the earliest convenience. Once the payment has been processed, please update the system accordingly, or notify the relevant team.</p></body></html>";
    		// echo $m_msgbody;
    		if($blhtml){
				if(Sendemailnew($sendTo,$sendcc,$sendbcc,$msubject,$m_msgbody,$attch = array())){
					//echo "Send email";
				}else{
					//echo "Not send email";
				}
			}
		}
	}
}

?>