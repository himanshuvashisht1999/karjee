<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getdatabseandfoldername($mconn,$companyid)
{
	$querygetd="select databasename,companyfoldername from tbl_company_master where companyid='".$companyid."'";
	$resultd=$mconn->query($querygetd);
	if ($resultd->num_rows>0) {
		$drow=$resultd->fetch_assoc();
		$dbinfo=$drow['companyfoldername'];
	}else {
		$dbinfo="";
	}

	return $dbinfo;
}
function getcounteryname($mconn){
	$qry="Select countryid,countryname from tbl_country_master ";
	$result_c=$mconn->query($qry);
	$countryinfo=array();
	if ($result_c->num_rows>0) {
		while ($usrow=$result_c->fetch_assoc()) {
			$countryinfo[]=array('counid'=>$usrow['countryid'], 'countryname'=>$usrow['countryname']);
		}
	}


	return  json_encode($countryinfo);
}

function getcurrencytype($mconn){
	$qry="Select currency,currency_val from tbl_currency_master ";
	$result_c=$mconn->query($qry);
	$countryinfo=array();
	if ($result_c->num_rows>0) {
		while ($usrow=$result_c->fetch_assoc()) {
			$countryinfo[]=array('currency'=>$usrow['currency'], 'currencyval'=>$usrow['currency_val']);
		}
	}


	return  json_encode($countryinfo);
}




// function getuserdetails($conn,$companyid,$id,$dbname,$compfoldername)
// {
// 	$querygetu=" select loginname,logintype from login_authorized_master where cmp_id='".$companyid."' and id='".$id."'";
// 	$resultu=$conn->query($querygetu);
// 	if ($resultu->num_rows>0) {
// 		while ($urow=$resultu->fetch_assoc()) {
// 			$userinfo[]=array('loginname'=>$urow['loginname'], 'logintype'=>$urow['logintype'], 'message'=>'Success','dbname'=>$dbname,'companyfoldername'=>$compfoldername);
// 		}
		
// 		//$dbinfo=$drow['databasename']."@".$drow['companyfoldername'];
// 	}else {
// 		$userinfo[]=array('loginname'=>'', 'logintype'=>'', 'message'=>'UnSuccess','dbname'=>'','companyfoldername'=>'');
// 	}

// 	return $userinfo;
// }


 ?>