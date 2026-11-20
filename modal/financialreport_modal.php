<?php 

ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require('../connection.php');

require('../functions/function_master.php');

require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');

$action=$_GET['action'];

if($action=="financialreporttypelist"){

	$rrp=getfinancialreporttypelist($conn,"reporttypelist","");

	echo json_encode($rrp);

}else if($action=="financialreportperiodlist"){

	$rrp=getfinancialreporttypelist($conn,"reportperiodlist","");

	echo json_encode($rrp);

}else if($action=="financialyearlist_frdata"){

	$companyid = $_POST['companyid'];

	$subcompanyid = $_POST['subcompanyid'];

	$projectid = $_POST['projectid'];

	$rrpy=getfinancialyreportdetails($conn,$companyid,$subcompanyid,$projectid,"","","","","financialyearlist","");

	echo json_encode($rrpy);

}else if($action=="financialreport_htmlview"){

	$companyid = $_POST['companyid'];

	$subcompanyid = $_POST['subcompanyid'];

	$projectid = $_POST['projectid'];



	if($_POST['financialyear']==null || $_POST['financialyear']=='null' || $_POST['financialyear']==''){

		$financialyear = '2023-2024';

	}else{

		$financialyear = $_POST['financialyear'];

	}

	if($financialyear){

		$financialyearexp=explode("-", $financialyear);

		$firstyear=$financialyearexp[0];

		$secondyear=$financialyearexp[1];

		$datefrom =$firstyear.'-04-01'; 

		$dateto =$secondyear.'-03-31'; 

	}else{

		$datefrom="";

		$dateto="";

	}

	// $reporttype = $_POST['reporttype'];

	// $timeperiod = $_POST['timeperiod'];

	$rrp="SELECT (case when timeperiod='Monthly' then date_format(datefrom,'%M-%y') else timeperiod end) AS timeperiod, datefrom, dateto, reportfilepath FROM tbl_financialreport_details where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and financialyear='".$financialyear."' ORDER BY datefrom";



	// if(strtolower($reporttype)=="un-audited"){

	// 	echo $rrp="SELECT (case when timeperiod='Monthly' then date_format(datefrom,'%M-%y') else timeperiod end) AS timeperiod, MAX(CASE WHEN reporttype='Un-AuditedFull' THEN reportfilepath ELSE NULL END) AS 'fullfilepath', MAX(CASE WHEN reporttype='Un-AuditedAbridged' THEN reportfilepath ELSE NULL END) AS 'abridgedfilepath', MAX(CASE WHEN reporttype='Un-AuditedUCSOE_Chronological' THEN reportfilepath ELSE NULL END) AS 'chronologicalfilepath' FROM tbl_financialreport_details where timeperiod like '%".$timeperiod."%'  and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and financialyear='".$financialyear."' GROUP BY timeperiod ORDER BY datefrom";

	// }else if(strtolower($reporttype)=="audited"){

	// 	$rrp="SELECT (case when timeperiod='Monthly' then date_format(datefrom,'%M-%y') else timeperiod end) AS timeperiod, MAX(CASE WHEN reporttype='AuditedFull' THEN reportfilepath ELSE NULL END) AS 'fullfilepath', MAX(CASE WHEN reporttype='AuditedAbridged' THEN reportfilepath ELSE NULL END) AS 'abridgedfilepath', MAX(CASE WHEN reporttype='AuditedUCSOE_Chronological' THEN reportfilepath ELSE NULL END) AS 'chronologicalfilepath', datefrom, dateto FROM tbl_financialreport_details where timeperiod like '%".$timeperiod."%'  and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and financialyear='".$financialyear."' GROUP BY timeperiod ORDER BY datefrom";

	// }

	$rrpr=$conn->query($rrp) or die($conn->error);

	$rptht="";

	if($rrpr->num_rows>0){

		$rptht.="<div class='table-responsive'>

			<table class='table' border='1'>

				<thead>

					<tr>

						<th style='width: 60px; text-align:center'>S.No</th>

						<th style='text-align:center'>View Report</th>

						<th>Time Period</th>

						<th>Details & Documents</th>

					</tr>

				</thead>";

				$rptht.="<tbody>";

				$rpsno=1;

				while ($rrpkey=$rrpr->fetch_assoc()) {

					if($rrpkey['reportfilepath']=='Not Available' || $rrpkey['reportfilepath']=='' || $rrpkey['reportfilepath']==null){

						$reportfilepath="Not Available";

					}else{

						// echo $rrpkey['reportfilepath'];

						$reportfilepath="<a href='".$rrpkey['reportfilepath']."'  target='_Blank'><i class='fa fa-file-pdf' style='font-size:48px;color:red;'></i></a>";

					}

					

					$rptht.="<tr>

						<td style='width: 60px;'>".$rpsno++."</td>

						<td style='text-align:center;'>".$reportfilepath."</td>

						<td>".$rrpkey['timeperiod']."</td>

						<td class='datefrom_td' style='display:none;'>".$rrpkey['datefrom']."</td>

						<td class='dateto_td' style='display:none;'>".$rrpkey['dateto']."</td>

						<td class='datefromdsp_td' style='display:none;'>".date('d-M-Y', strtotime($rrpkey['datefrom']))."</td>

						<td class='datetodsp_td' style='display:none;'>".date('d-M-Y', strtotime($rrpkey['dateto']))."</td>

						<td class='viewnote4' style='cursor:pointer; color: blue;'>View details & documents</td>

					</tr>";	

				}

				$rptht.="</tbody>";

			$rptht.="</table>

		</div>";

	}else{

		$rptht.="<h5 style='text-align:center;'>data not available</h5>";

	}

	echo $rptht;

}

