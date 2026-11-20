<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
include('../SimpleXLSX.php');
include('../SimpleXLS.php');
$action = $_GET['action'];
if($action == "savetablestructure"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$componantname = sanitizeString($_POST['componantname']);
	$componantnametext = $_POST['componantnametext'];
	$mastertype = $_POST['mastertype'];
	$groupid = $_POST['groupid'];
	$tablestructure = json_decode($_POST['tablestructure'],TRUE);
	$tblstr = $tablestructure;
	if ($mastertype=="universal"){
		$tablename="strs_".$componantname;
	}else{
		$tablename="strs_".$projectid."_".$componantname;
	}

	$savestatus = 0;
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();
    try{
		$loginrolename=$_POST['loginrolename'];
		$id=getidfrommaster($conn);
		$instqry="Insert into tbl_tablestructure_master(companyid, subcompanyid, projectid, id, formname, sqltablename, universalstructure, loginname, tabletype, groupid)values('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$id."', '".$componantnametext."', '".$tablename."', '".$mastertype."', '".$loginrolename."', '".$componantname."','".$groupid."')";
		$resultinst = $conn->query($instqry);
		$maxmoduleid="";
		$qrmxg=$conn->query("select max(moduleid) as moduleid from login_modules_master");
		if ($qrmxg->num_rows>0) {
			$rowmx=$qrmxg->fetch_assoc();
			$maxmoduleid=$rowmx['moduleid'];
			$maxmoduleid++;
		}
		$modulefoldername="automation/automation_htmlform.php";
		$insertmodule=$conn->query("Insert into login_modules_master(groupid,moduleid,modulename,modulefoldername,status,frmid)values('".$groupid."','".$maxmoduleid."','".$componantnametext."','".$modulefoldername."','active','".$id."')");
		$qryinset="Insert into tbl_tablestructure_details(companyid, subcompanyid, projectid,id, sqlfieldname, sqldatatype, sqldescription, sqlconstraints, referencetable, referencecolumn, defaultvalue, inputlablename, inputtype, inputvalidation, bindsqltablename, binddisplayname, bindvalue, displayfield, duplicatevalidation, displayorder)values";
		$qryinsetvals="";
		foreach ($tablestructure as $cvkey) {
			$columnname=sanitizeString($cvkey['columnname']);
			$lablename=desanitizeString($cvkey['columnname']);
			$datatype=$cvkey['datatype'];
			$description=$cvkey['description'];
			$constraints=$cvkey['constraints'];
			$referenceTable=$cvkey['referenceTable'];
			$referenceColumn=$cvkey['referenceColumn'];
			$defaultText=$cvkey['defaultText'];
			$controltype=$cvkey['controltype'];
			$bindtablename=$cvkey['bindtablename'];
			$binddisplayfield=$cvkey['binddisplayfield'];
			$bindvalue=$cvkey['bindvalue'];
			$combined=$cvkey['combined'];
			$validation=$cvkey['validation'];
			$display=$cvkey['display'];
			$duplicate=(stristr($constraints,"unique")?"yes":"no");
			$sno=$cvkey['sno'];
			$displayorder = $cvkey['displayorder'];
			if($combined){
				$binddisplayfield = $combined;
			}
		
			if(!$qryinsetvals){
				$qryinsetvals="('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$id."', '".$columnname."', '".addslashes($datatype)."', '".addslashes($description)."', '".$constraints."', '".$referenceTable."', '".$referenceColumn."', '".$defaultText."', '".$lablename."', '".$controltype."', '".$validation."', '".$bindtablename."', '".$binddisplayfield."', '".$bindvalue."', '".$display."', '".$duplicate."', '".$displayorder."')";
			}
			else{
				 $qryinsetvals.=",('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$id."', '".$columnname."', '".addslashes($datatype)."', '".addslashes($description)."', '".$constraints."', '".$referenceTable."', '".$referenceColumn."', '".$defaultText."', '".$lablename."', '".$controltype."', '".$validation."', '".$bindtablename."', '".$binddisplayfield."', '".$bindvalue."', '".$display."', '".$duplicate."', '".$displayorder."')";
			}
		}
		if($qryinsetvals){
			$qryinsetfinal = $qryinset.$qryinsetvals;
			$result2 = $conn->query($qryinsetfinal);
		}
		$conn->commit();
		$response = createOrUpdateTable($tblstr, $tablename, $mastertype, $conn);
		echo json_encode($response);
	}
	catch (mysqli_sql_exception $e) {
    	$conn->rollback();
    	$savestatus = 0;
    	$response[]=array("Status"=>"Error",'message'=>$e);
	}	
	
	//echo json_encode($response);

	/*$structure="";
	$sqlshow = "SHOW TABLES LIKE '".$tablename."' ";
	$resultshow=$conn->query($sqlshow);
	if ($resultshow->num_rows>0) {
		$bnflist[]=array("Status"=>"Error",'message'=>'Table already exists');
	}else{
		foreach ($tablestructure as $cvkey) {
			$dv="";
			$columnname=preg_replace('/[^a-zA-Z0-9]/', '',$cvkey['columnname']);
			$columnname=str_replace(' ', '',$columnname);
			$columnname=strtolower($columnname);
			if ($cvkey['duplicate']=="yes") {
				$dv="UNIQUE INDEX ".$columnname."_UNIQUE (".$columnname."  ASC)";
			}else{
				$dv="";
			}
			$datatype=$cvkey['datatype'];
			if ($datatype=="" || $datatype=="Select Option") {
				$datatype="VARCHAR(500)";
				$datatypevalue="text";
			}else{
				$array = explode("|", $datatype);
				$datatypevalue=$array[0];
				$datatype=$array[1];
			}
			
			//select|VARCHAR(500)
			
			if ($datatypevalue=="button") {
				// code...
			}else{
				if ($structure=="") {
					if ($dv) {
						$structure="`".$columnname."`"." ".$datatype." NULL ,".$dv."";
					}else{
						$structure="`".$columnname."`"." ".$datatype." NULL ";
					}
					
				}else{
					if ($dv) {
						$structure=$structure." ,"."`".$columnname."`"." ".$datatype." NULL ,".$dv."";
					}else{
						$structure=$structure." ,"."`".$columnname."`"." ".$datatype." NULL";
					}
					
				}	
			}
			
		}

		if ($mastertype=="U") {
			$typeofmaster="universal";
			$sql = "create table ".$tablename." (
      sno BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,id BIGINT(10),tedit VARCHAR(200),loginid VARCHAR(200),edatetime DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
      ".$structure.")";
		}else{
			$typeofmaster="";
			$sql = "create table ".$tablename." (
      sno BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,id BIGINT(10),companyid BIGINT(10),subcompanyid BIGINT(10),projectid BIGINT(10) ,tedit VARCHAR(200),loginid VARCHAR(200),edatetime DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
      ".$structure.")";
		}
			

		$result = $conn->query($sql) or die($conn->error);*/
		
	   /* if($result)
	    {*/
	    /*}else{
	    	$bnflist[]=array("Status"=>"Error",'message'=>'Table already exists');
	    }*/
	//}
	//echo json_encode($bnflist);
}else if ($action=="updatetablestructure") {
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$componantnameid = $_POST['componantname'];
	$connectedtable = $_POST['connectedtable'];
	$groupid = $_POST['groupid'];
	$tablename="";
	$sqltb="Select sqltablename from tbl_tablestructure_master where id='".$componantnameid."'";
	$resulttb=$conn->query($sqltb);
	if ($resulttb->num_rows>0) {
		$rowtb=$resulttb->fetch_assoc();
		$tablename=$rowtb['sqltablename'];
	}
	$qrygrp=$conn->query("Update tbl_tablestructure_master set groupid='".$groupid."' where id='".$componantnameid."'");
	$qrygrpmol=$conn->query("Update login_modules_master set groupid='".$groupid."' where frmid='".$componantnameid."'");

	$tablestructure = json_decode($_POST['tablestructure'],TRUE);
	$alterstm="";
	foreach ($tablestructure as $cvkey) {
		$columnname=$cvkey['columnname'];
		$columnname=strtolower($columnname);
		$lablename=$cvkey['columnname'];
		$lablename=ucwords($lablename);
		$datatype=$cvkey['datatype'];
		$bindtablename=$cvkey['bindtablename'];
		$binddisplayfield=$cvkey['binddisplayfield'];
		$bindvalue=$cvkey['bindvalue'];
		$validation=$cvkey['validation'];
		$display=$cvkey['display'];
		$project=$cvkey['project'];
		$duplicate=$cvkey['duplicate'];
		$displaysno=$cvkey['displaysno'];
		$sno=$cvkey['sno'];
			if ($validation==""|| $validation=="Select") {
				$validation="no";
			}
			if ($display==""|| $validation=="Select") {
				$display="yes";
			}

		if ($sno) {
			$array = explode("|", $datatype);
				$datatype=$array[0];
			if ($datatype!="select" && $datatype!="checkbox" && $datatype!="radio") {
					$bindtablename="";
					$binddisplayfield="";
					$bindvalue="";
					$project="";
				}
			$qryinset="Update tbl_tablestructure_details set inputtype='".$datatype."',inputvalidation='".$validation."',displayfield='".$display."',bindsqltablename='".$bindtablename."',binddisplayname='".$binddisplayfield."',bindvalue='".$bindvalue."',projectoption='".$project."',duplicatevalidation='".$duplicate."', displayorder='".$displaysno."',connectedtablename='".$connectedtable."' where sno='".$sno."'";		 

		}else{
			if ($datatype==""|| $datatype=="Select Option") {
			$datatype="text";
			$datatypevalue="VARCHAR(500)";
			}else{
				$array = explode("|", $datatype);
				$datatype=$array[0];
				$datatypevalue=$array[1];
			}
			$columnname=preg_replace('/[^a-zA-Z0-9]/', '',$columnname);
			$qryinset="Insert into tbl_tablestructure_details(companyid,subcompanyid,projectid,id,sqlfieldname,inputlablename,inputtype,inputvalidation,bindsqltablename,binddisplayname,bindvalue,displayfield,projectoption,duplicatevalidation,displayorder,connectedtablename) values('".$companyid."','".$subcompanyid."','".$projectid."','".$componantnameid."','".$columnname."','".$lablename."','".$datatype."','".$validation."','".$bindtablename."','".$binddisplayfield."','".$bindvalue."','".$display."','".$project."','".$duplicate."','".$displaysno."','".$connectedtable."')";
			//$alterstm="Add column ".$columnname." ".$datatypevalue. "  NULL " ;
			$alterstm="Add column `".$columnname."`".$datatypevalue."  NULL";
		  $qrymake= "Alter table ".$tablename." ". $alterstm;
			$qryupdate=$conn->query($qrymake);
		}

		$result2 = $conn->query($qryinset) or die($conn->error);
		if ($result2) {
			$bnflist[]=array("Status"=>"Done",'message'=>'Done');
		}
	}

	echo json_encode($bnflist);
}else if ($action=="gettablestructre") {
	$componantid = $_POST['componantid'];
	
	$strlist=array();
	
	  $qry="select ifnull(m.formname,'') as formname,ifnull(m.sqltablename,'') as sqltablename,ifnull(m.groupid,'') as groupid,b.sno,ifnull(b.sqlfieldname,'') as sqlfieldname,ifnull(b.inputlablename,'') as inputlablename,ifnull(b.inputtype,'') as inputtype,ifnull(b.inputvalidation,'') as inputvalidation,ifnull(b.bindsqltablename,'') as bindsqltablename,ifnull(b.binddisplayname,'') as binddisplayname,ifnull(b.bindvalue,'') as bindvalue,ifnull(b.projectoption,'') as projectoption,ifnull(b.duplicatevalidation,'') as duplicatevalidation,ifnull(b.displayorder,'') as displayorder,ifnull(b.connectedtablename,'') as connectedtablename from tbl_tablestructure_master as m join  tbl_tablestructure_details as b on m.id=b.id where m.id='".$componantid."' and (b.tedit is null or b.tedit='') and ifnull(b.inputlablename,'') <>''";
	
	$resltstr=$conn->query($qry);
	if ($resltstr->num_rows>0) {
		while ($rowstr=$resltstr->fetch_assoc()) {
			$strlist[]=array("fildname"=>$rowstr['sqlfieldname'],"filedtype"=>$rowstr['inputtype'],"filednamefinal"=>$rowstr['inputlablename'],"filedtypefinal"=>$rowstr['inputtype'],"sno"=>$rowstr['sno'],"componantid"=>'',"validation"=>$rowstr['inputvalidation'],"dbfiled"=>$rowstr['sqlfieldname'],"tablename"=>$rowstr['sqltablename'],"binddatafromtable"=>$rowstr['bindsqltablename'],"datadisplayname"=>$rowstr['binddisplayname'],"binddatafildnamefromtable"=>$rowstr['bindvalue'],"projectoption"=>$rowstr['projectoption'],"dublicatevalidation"=>$rowstr['duplicatevalidation'],"displaysno"=>$rowstr['displayorder'],"connectedtablename"=>$rowstr['connectedtablename'],'groupid'=>$rowstr['groupid']);
		}
	}
	echo json_encode($strlist);
}else if ($action=="getcomponantname") {
	
	$strlist=array();
	  $qry="Select distinct formname as componantname,id from tbl_tablestructure_master where (tedit is null or tedit='')";
	$resltstr=$conn->query($qry);
	if ($resltstr->num_rows>0) {
		while ($rowstr=$resltstr->fetch_assoc()) {
			$strlist[]=array("componantname"=>$rowstr['componantname'],"id"=>$rowstr['id']);
		}
	}
	echo json_encode($strlist);
}else if ($action=="updatestructrue") {
	$componantname = $_POST['componantname'];
	$structrinfo = json_decode($_POST['structrinfo'],TRUE);
	$status="";
	if(count($structrinfo)>0){
		foreach ($structrinfo as $cvkey) {
			
			$headername=$cvkey['headername'];
			$type=$cvkey['type'];
			$idofid=$cvkey['idofid'];
			$sno=$cvkey['sno'];
			$validation=$cvkey['validation'];
			$dbfildname=$cvkey['dbfildname'];
			$tablename=$cvkey['tablename'];
			$fieldname=$cvkey['fieldname'];
			$bindfieldname=$cvkey['bindfieldname'];
			$qryupdate=$conn->query("Update tbl_tablestrucrure set filedname_final='".$headername."',filedtype_final='".$type."',componant_id='".$idofid."',validation='".$validation."',dbfiled='".$dbfildname."',binddatafromtable='".$tablename."',datadisplayname='".$fieldname."',binddatafildnamefromtable='".$bindfieldname."' where sno='".$sno."'");
			if ($qryupdate) {
				$status="Done";
			}else{
				$status="Not Done";
				
			}

			
		}

		if ($status=='Done') {
			$returnmasg[]=array('Status'=>$status);
		}
	}

	echo json_encode($returnmasg);
}else if ($action=="gettablefromdb") {
  $dbname = "";
  $j=0;
  $qryt="Select TABLE_NAME  FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()";
  $resultt=$conn->query($qryt);
  if ($resultt->num_rows>0) {
  	while ($rowt=$resultt->fetch_assoc()) {
  		if ($j==0) {
  			$option="<option value=''>Select Table Name</option>";
  			$option=$option."<option value=".$rowt['TABLE_NAME'].">".$rowt['TABLE_NAME']."</option>";
  		}else{
  			$option=$option."<option value=".$rowt['TABLE_NAME'].">".$rowt['TABLE_NAME']."</option>";
  		}
  		$j++;
  		
  	}
  }
  echo $option;
}else if ($action=="gettablefieldname") {
	$tablename = $_POST['tablename'];
	$dbname = "";
  
  $qryt="Select COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() and TABLE_NAME='".$tablename."'";
  $resultt=$conn->query($qryt);
  if ($resultt->num_rows>0) {
  	while ($rowt=$resultt->fetch_assoc()) {
  		if ($j=0) {
  			$option="<option value=".$rowt['COLUMN_NAME']."'>".$rowt['COLUMN_NAME']."</option>";
  			$option="<option value=".$rowt['COLUMN_NAME']."'>".$rowt['COLUMN_NAME']."</option>";
  		}else{
  			$option=$option."<option value=".$rowt['COLUMN_NAME'].">".$rowt['COLUMN_NAME']."</option>";
  		}
  		
  	}
  }
  echo $option;
}else if($action=="tableprimarycols"){
	$table = $_GET["table"];
 	$result = $conn->query("SELECT column_name from information_schema.columns where table_schema = DATABASE() and table_name = '$table' and column_key = 'PRI'");
	$html="<option value=''>Select Column</option>";
	if($result->num_rows > 0) {
		while($prow = $result->fetch_assoc()) {
           $html.= "<option value='".$prow["column_name"]."'>".$prow["column_name"]."</option>";
        }
	}
	echo $html;
}else if ($action=="createandaltertable") {
	$tablename = $_POST['tablename'];
	$structrinfo = json_decode($_POST['structrinfo'],TRUE);
	$createstatus="";
	$sqlshow = "SHOW TABLES LIKE '".$tablename."' ";
	$resultshow=$conn->query($sqlshow);
	if ($resultshow->num_rows>0) {
		 $createstatus="C";
	}else{
		 $sqlcreatetable = "create table ".$tablename." (
        sno INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY)";
        $resulrcreate=$conn->query($sqlcreatetable);
        $createstatus="";

	}

	$alterstm="";

	if(count($structrinfo)>0){
		foreach ($structrinfo as $cvkey) {
			
			 $headername=$cvkey['headername'];
			 $datatype=$cvkey['datatype'];
			 $sno=$cvkey['sno'];
			 if ($datatype=='' || $datatype==null) {
			 	$datatype="VARCHAR(500)";
			 }
			 altertable($conn,$tablename,$headername,$datatype);
			// if ($alterstm=="") {
			// 	$alterstm="Add column ".$headername." ".$datatype. "  NULL AFTER sno" ;
			// }else{
			// 	$alterstm=$alterstm.",Add column ".$headername." ".$datatype. " NULL " ;
			// }
			
			

			
		}

		// $qrymake= "Alter table ".$tablename." ". $alterstm;
		// $qryupdate=$conn->query($qrymake);
		// if ($qrymake) {
		// 	$status="Done";
		// }else{
		// 	$status="Not Done";
			
		// }

		// if ($status=='Done') {
		// 	$returnmasg[]=array('Status'=>$status);
		// }
	}

	echo json_encode($returnmasg);
}else if ($action=="deletetedit") {
	$sno = $_POST['sno'];
	$qrytedit=$conn->query("Update tbl_tablestructure_details set tedit='Delet' where sno='".$sno."'");
	if ($qrytedit) {
		$returnmasg[]=array("Status"=>"Done","Message"=>"Delete");
	}else{
		$returnmasg[]=array("Status"=>"Error","Message"=>"Not Delete");
	}
	echo json_encode($returnmasg);
}else if ($action=="getcomponantfrommaster") {
	$sql="Select componantname,componantvalue,info from strs_txtcomponantmaster where (tedit is null or tedit='')";
	$resultcom=$conn->query($sql);
	if ($resultcom->num_rows>0) {
		while ($rowc=$resultcom->fetch_assoc()) {
			$complist[]=array('componantname'=>$rowc['componantname'],'componantvalue'=>$rowc['componantvalue'],'info'=>$rowc['info']);
		}
	}

	echo json_encode($complist);
}else if ($action=="getmodulefrommaster") {
	$complist=grouptitleofmodule($conn, "", "");

	echo json_encode($complist);
}else if ($action=="gettbldynamictype") {
	$sql="Select id, displayname,ifnull(tablename,'') as tablename from create_table_dynamic_type";
	$resultcom=$conn->query($sql);
	if ($resultcom->num_rows>0) {
		while ($rowc=$resultcom->fetch_assoc()) {
			$complist[]=array('id'=>$rowc['id'],'displayname'=>$rowc['displayname'],'tablename'=>$rowc['tablename']);
		}
	}
	echo json_encode($complist);
}else if ($action=="uploadexcelsheet") {
$fname= $_FILES['files'];
	if(is_array($fname)){
		$file_ext = pathinfo($fname['name'],PATHINFO_EXTENSION);
		$file_path=$fname['tmp_name'];
	}else{
		$file_ext = pathinfo($fname,PATHINFO_EXTENSION);
		$file_path = $fname;
	}
	if ($objectX = $file_ext=="xlsx"?SimpleXLSX::parse($file_path):SimpleXLS::parse($file_path)) {
		
	$stringvalue=standardizeKeys(constructAssociativeArray($objectX->rows()));
	}else {
		$stringvalue[]=array("xls"?SimpleXLS::parseError():SimpleXLSX::parseError());
	}
	echo json_encode($stringvalue);

}else if ($action=="getdetailsoftbldynamictype") {
	$componantid = $_POST['componantid'];
	$strlist=array();
	$qry="Select id, ifnull(displayname,'') as displayname,ifnull(tablename,'') as tablename,ifnull(defaultcolumns,'') as defaultcolumns,ifnull(defaultbindingtblname,'') as defaultbindingtblname,ifnull(bindingfieldname,'') as bindingfieldname,ifnull(chktablesexist,'') as chktablesexist  from create_table_dynamic_type where id='".$componantid."'";
	$resltstr=$conn->query($qry);
	$flag2="";
	if ($resltstr->num_rows>0) {
		while ($rowstr=$resltstr->fetch_assoc()) {

			$position = strpos($rowstr['defaultcolumns'], "|");
			$position_2 = strpos($rowstr['defaultbindingtblname'], "|");
			if ($position_2 !==false) {
				$flag2="Yes";
			}

			if ($position !== false) {
				$array = explode("|", $rowstr['defaultcolumns']);
				$countarr=count($array);
				
				for ($i=0; $i <$countarr ; $i++) { 
					$strlist[]=array("id"=>$rowstr['id'],"displayname"=>$rowstr['displayname'],"tablename"=>$rowstr['tablename'],"defaultcolumns"=>$array[$i],"defaultbindingtblname"=>$rowstr['defaultbindingtblname'],"bindingfieldname"=>$rowstr['bindingfieldname'],"chktablesexist"=>$rowstr['chktablesexist']);
				}
			}else{
				$strlist[]=array("id"=>$rowstr['id'],"displayname"=>$rowstr['displayname'],"tablename"=>$rowstr['tablename'],"defaultcolumns"=>$rowstr['defaultcolumns'],"defaultbindingtblname"=>$rowstr['defaultbindingtblname'],"bindingfieldname"=>$rowstr['bindingfieldname'],"chktablesexist"=>$rowstr['chktablesexist']);
			}

			
		}
	}
	
	echo json_encode($strlist);

}else if ($action=="gettableavilableoftbldynamictype") {
	$componantid = $_POST['componantid'];
	$projectid = $_POST['projectid'];
	$bnflist=array();
	$qry="Select id, ifnull(defaultbindingtblname,'') as defaultbindingtblname  from create_table_dynamic_type where id='".$componantid."' and (defaultbindingtblname is not null and defaultbindingtblname<>'')";
	$resltstr=$conn->query($qry);
	$flag2="";
	if ($resltstr->num_rows>0) {
		while ($rowstr=$resltstr->fetch_assoc()) {

			$position = strpos($rowstr['defaultbindingtblname'], "|");
			

			if ($position !== false) {
				$array = explode("|", $rowstr['defaultbindingtblname']);
				$countarr=count($array);
				
				for ($i=0; $i <$countarr ; $i++) { 
					$tblname="strs_".$projectid."_".$array[$i];
					$sqlshow = "SHOW TABLES LIKE '".$tblname."' ";
					$resultshow=$conn->query($sqlshow);
					if ($resultshow->num_rows>0) {
						
					}else{
						$bnflist[]=array("Status"=>"Error",'message'=>'Table already not exists','tablename'=>$tblname);
					}
					
				}
			}else{
				$tblname="strs_".$projectid."_".$rowstr['defaultbindingtblname'];
				
					$sqlshow = "SHOW TABLES LIKE '".$tblname."' ";
					$resultshow=$conn->query($sqlshow);
					if ($resultshow->num_rows>0) {
						//$bnflist[]=array("Status"=>"Succes",'message'=>'Table already exists','tablename'=>$tblname);
					}else{
						$bnflist[]=array("Status"=>"Error",'message'=>'Table already not exists','tablename'=>$tblname);
					}
			}

			
		}
	}
	
	echo json_encode($bnflist);

}

function altertable($conn,$tablename,$fieldname,$datatype){

    if ($result = $conn -> query("SELECT DATABASE()")) {
	  $row = $result -> fetch_row();
	  $dbname= $row[0];
  	}
   $qryt="Select COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$dbname."' and TABLE_NAME='".$tablename."' and COLUMN_NAME='".$fieldname."'";
   $resultcm=$conn->query($qryt);
   if ($resultcm->num_rows>0) {
   	 echo $sqlchang = "Alter TABLE ".$tablename."   CHANGE COLUMN ".$fieldname." ".$fieldname." ".$datatype."";

   }else {
   	echo $sqlchang = "Alter TABLE ".$tablename." ADD ".$fieldname." ".$datatype."";
   }	
}
function getidfrommaster($conn){
 	$sql="Select max(id) as maxid from tbl_tablestructure_master";
 	$resultget=$conn->query($sql);
 	if ($resultget->num_rows>0) {
 		$mrow=$resultget->fetch_assoc();
 		if (is_null($mrow['maxid'])) {
			$maxid = 1;
		}else{
			$maxid = $mrow['maxid']+1;
		}
 	}
 	return $maxid;
 }

 function createOrUpdateTable($tbldef, $tableName, $mastertype, $conn) {
    $result = ["status" => "success", "messages" => []];

    $generalFields = [
        ["columnname" => "companyid", "datatype" => "BIGINT(10)", "constraints" => "NOT NULL"],
        ["columnname" => "subcompanyid", "datatype" => "BIGINT(10)", "constraints" => "NOT NULL"],
        ["columnname" => "projectid", "datatype" => "BIGINT(10)", "constraints" => "NOT NULL"],
        ["columnname" => "tedit", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL"],
        ["columnname" => "loginid", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL"],
        ["columnname" => "edatetime", "datatype" => "DATETIME", "constraints" => "NULL DEFAULT CURRENT_TIMESTAMP"]
    ];

    $universalFields = [
        ["columnname" => "tedit", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL"],
        ["columnname" => "loginid", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL"],
        ["columnname" => "edatetime", "datatype" => "DATETIME", "constraints" => "NULL DEFAULT CURRENT_TIMESTAMP"]
    ];

    if ($mastertype === "general") {
        $tblstr = array_merge($tbldef, $generalFields);
    } elseif ($mastertype === "universal") {
        $tblstr = array_merge($tbldef, $universalFields);
    } else {
        $tblstr = $tbldef;
    }

    $query = "SHOW TABLES LIKE '$tableName'";
    $tableExists = mysqli_num_rows(mysqli_query($conn, $query)) > 0;

    if (!$tableExists) {
        $columns = [];
        $uniqueKeys = [];
        $primaryKeys = [];
        $foreignKeys = [];

        foreach ($tblstr as $column) {
            $colDef = "{$column['columnname']} {$column['datatype']}";
            if (!empty($column['constraints']) && strpos($column['constraints'], "FOREIGN KEY") === false && strpos($column['constraints'], "UNIQUE") === false) {
                $colDef .= " {$column['constraints']}";
            }
            $columns[] = $colDef;

						if (strpos($column['constraints'], "UNIQUE") !== false) {
                $uniqueKeys[] = $column['columnname'];
            }

            if (strpos($column['constraints'], "FOREIGN KEY") !== false && !empty($column['referenceTable']) && !empty($column['referenceColumn'])) {
            	if($column['referenceTable']==="self"){
									$foreignKeys[] = "FOREIGN KEY ({$column['columnname']}) REFERENCES {$tableName}({$column['referenceColumn']})";
	            	}
	            	else{
	                $foreignKeys[] = "FOREIGN KEY ({$column['columnname']}) REFERENCES {$column['referenceTable']}({$column['referenceColumn']})";
	            	}
            }
        }

        if (!empty($uniqueKeys)) {
            $columns[] = "UNIQUE (" . implode(", ", $uniqueKeys) . ")";
        }
   
        if (!empty($foreignKeys)) {
            $columns = array_merge($columns, $foreignKeys);
        }

        $createQuery = "CREATE TABLE $tableName (" . implode(", ", $columns) . ")ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $res = $conn->$createQuery;
        
        if($res){
            $result["messages"][] = "Table $tableName created successfully.";
        }
        else{
					$result["status"] = "error";
          $result["messages"][] = "Error creating table $tableName: ";
        }
    }else {
        // Fetch existing columns
        $existingColumnsQuery = "SHOW COLUMNS FROM $tableName";
        $existingColumnsResult = $conn->query($existingColumnsQuery);
        $existingColumns = [];

        while ($row = mysqli_fetch_assoc($existingColumnsResult)) {
            $existingColumns[$row['Field']] = [
                'datatype' => strtoupper($row['Type']),
                'null' => ($row['Null'] == "YES") ? "NULL" : "NOT NULL",
                'key' => $row['Key'],
                'default' => $row['Default']
            ];
        }

        // Identify columns to ADD and MODIFY
        $columnsToAdd = [];
        $columnsToModify = [];

        foreach ($tblstr as $column) {
        	$columnName = $column['columnname'];;
        	$newDefinition = "{$column['datatype']}";
            if (!empty($column['constraints']) && strpos($column['constraints'], "FOREIGN KEY") === false && strpos($column['constraints'], "UNIQUE") === false) {
            	if(stristr($column['constraints'], "PRIMARY KEY"))
            	{
                $newDefinition .= " {$column['constraints']}";
                $newDefinition = str_replace("PRIMARY KEY", "",$newDefinition);
            	}
            	else{
            		$newDefinition .= " {$column['constraints']}";
            	}
            }
          
            if (!array_key_exists($columnName, $existingColumns)) {
                // Column does not exist, add it
                $columnsToAdd[] = "ALTER TABLE $tableName ADD $columnName $newDefinition";
            } else {
                // Column exists, check if it needs modification
                $existingDefinition = "{$existingColumns[$columnName]['datatype']} {$existingColumns[$columnName]['null']}";
                if (!empty($existingColumns[$columnName]['key'])) {
                    $existingDefinition .= " " . strtoupper($existingColumns[$columnName]['key']);
                }
                if (!empty($existingColumns[$columnName]['default'])) {
                    $existingDefinition .= " DEFAULT '{$existingColumns[$columnName]['default']}'";
                }

                if (trim($existingDefinition) !== trim($newDefinition)) {
                    // Column definition is different, modify it
                    $columnsToModify[] = "ALTER TABLE $tableName MODIFY $columnName $newDefinition";
                }
            }
        }
        
        // Execute Alter Queries to Add Columns
        foreach ($columnsToAdd as $query) {
            if ($conn->query($query)) {
                $result["messages"][] = "Column added successfully.";
            } else {
                $result["status"] = "error";
                $result["messages"][] = "Error adding column: " . $conn->error . " | Query: " . $query;
            }
        }

        // Execute Alter Queries to Modify Columns
        foreach ($columnsToModify as $query) {
        	echo "\n".$query;
				    if ($conn->query($query)) {
				        $result["messages"][] = "Column modified successfully: " . $query;
				    } else {
				        $result["status"] = "error";
				        $result["messages"][] = "Error modifying column: " . $conn->error . " | Query: " . $query;
				    }
				}

        if (empty($columnsToAdd) && empty($columnsToModify)) {
            $result["messages"][] = "No changes needed for $tableName.";
        }
    }
    return $result;
}

/*function createOrUpdateTable($tbldef, $tableName, $mastertype, $conn) {
 $result = ["status" => "success", "messages" => []]; 
 //var_dump($tbldef);
 // Additional fields for "general" mastertype 
    $generalFields = [
        ["columnname" => "companyid", "datatype" => "BIGINT(10)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "subcompanyid", "datatype" => "BIGINT(10)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "projectid", "datatype" => "BIGINT(10)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "tedit", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "loginid", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "edatetime", "datatype" => "DATETIME", "constraints" => "NULL DEFAULT CURRENT_TIMESTAMP", "defaultText" => ""]
    ];

    // Additional fields for "universal" mastertype
    $universalFields = [
        ["columnname" => "tedit", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "loginid", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "edatetime", "datatype" => "DATETIME", "constraints" => "NULL DEFAULT CURRENT_TIMESTAMP", "defaultText" => ""]
    ];

        $tblstr = [];
    // If mastertype is "general", merge additional fields
    if ($mastertype === "general") {
        $tblstr = array_merge($tbldef, $generalFields);
    } elseif ($mastertype === "universal") {
        // If mastertype is "universal", merge additional fields
        $tblstr = array_merge($tbldef, $universalFields);
    } else {
        $tblstr = $tbldef; // If mastertype is neither, use the original definition
    }
        // Check if the table exists
    $query = "SHOW TABLES LIKE '$tableName'";
    $tableExists = mysqli_num_rows(mysqli_query($conn, $query)) > 0;

    if (!$tableExists) {
        // Create table if it doesn't exist
        $columns = [];
        $primaryKeys = [];
        $foreignKeys = [];

        foreach ($tblstr as $column) {
            $colDef = "{$column['columnname']} {$column['datatype']}";
            if (!empty($column['constraints'])) {
                $colDef .= " {$column['constraints']}";
            }
            if (strpos($column['constraints'], "DEFAULT") !== false && !empty($column['defaultText'])) {
                $colDef .= " DEFAULT '{$column['defaultText']}'";
            }
            $columns[] = $colDef;

              // Handle Foreign Key Constraints
            if (strpos($column['constraints'], "PRIMARY KEY") !== false) {
                $primaryKeys[] = "PRIMARY KEY ({$column['columnname']})";
            }

            // Handle Foreign Key Constraints
            if (strpos($column['constraints'], "FOREIGN KEY") !== false) {
                if (!empty($column['referenceTable']) && !empty($column['referenceColumn'])) {
                    $foreignKeys[] = "FOREIGN KEY ({$column['columnname']}) REFERENCES {$column['referenceTable']}({$column['referenceColumn']})";
                }
            }
        }
        $columns = array_merge($columns, $primaryKeys);
        $columns = array_merge($columns, $foreignKeys);
        echo "\n".$createQuery = "CREATE TABLE $tableName (" . implode(", ", $columns) . ")";
        
        if (mysqli_query($conn, $createQuery)) {
            $result["messages"][] = "Table $tableName created successfully.";
        } else {
            $result["status"] = "error";
            $result["messages"][] = "Error creating table $tableName: " . mysqli_error($conn);
        }
    }
 return $result;
}*/

function createOrUpdateTablexxx($tbldef, $tableName, $mastertype, $conn) {
    $result = ["status" => "success", "messages" => []]; 
    //var_dump($tbldef); // Debugging inside the function

    // Additional fields for "general" mastertype 
    $generalFields = [
        ["columnname" => "companyid", "datatype" => "BIGINT(10)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "subcompanyid", "datatype" => "BIGINT(10)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "projectid", "datatype" => "BIGINT(10)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "tedit", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "loginid", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "edatetime", "datatype" => "DATETIME", "constraints" => "NULL DEFAULT CURRENT_TIMESTAMP", "defaultText" => ""]
    ];

    // Additional fields for "universal" mastertype
    $universalFields = [
        ["columnname" => "tedit", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "loginid", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "edatetime", "datatype" => "DATETIME", "constraints" => "NULL DEFAULT CURRENT_TIMESTAMP", "defaultText" => ""]
    ];

    $tblstr = [];
    // If mastertype is "general", merge additional fields
    if ($mastertype === "general") {
        $tblstr = array_merge($tbldef, $generalFields);
    } elseif ($mastertype === "universal") {
        // If mastertype is "universal", merge additional fields
        $tblstr = array_merge($tbldef, $universalFields);
    } else {
        $tblstr = $tbldef; // If mastertype is neither, use the original definition
    }

    // Check if the table exists
    $query = "SHOW TABLES LIKE '$tableName'";
    $tableExists = mysqli_num_rows(mysqli_query($conn, $query)) > 0;

    if (!$tableExists) {
        // Create table if it doesn't exist
        $columns = [];
        $foreignKeys = [];

        foreach ($tblstr as $column) {
            $colDef = "{$column['columnname']} {$column['datatype']}";
            if (!empty($column['constraints'])) {
                $colDef .= " {$column['constraints']}";
            }
            if (strpos($column['constraints'], "DEFAULT") !== false && !empty($column['defaultText'])) {
                $colDef .= " DEFAULT '{$column['defaultText']}'";
            }
            $columns[] = $colDef;

            // Handle Foreign Key Constraints
            if (strpos($column['constraints'], "FOREIGN KEY") !== false) {
                if (!empty($column['referenceTable']) && !empty($column['referenceColumn'])) {
                    $foreignKeys[] = "FOREIGN KEY ({$column['columnname']}) REFERENCES {$column['referenceTable']}({$column['referenceColumn']})";
                }
            }
        }

        $columns = array_merge($columns, $foreignKeys);
        $createQuery = "CREATE TABLE $tableName (" . implode(", ", $columns) . ")";
        
        if (mysqli_query($conn, $createQuery)) {
            $result["messages"][] = "Table $tableName created successfully.";
        } else {
            $result["status"] = "error";
            $result["messages"][] = "Error creating table $tableName: " . mysqli_error($conn);
        }
    } else {
        // Fetch existing columns
        $existingColumnsQuery = "SHOW COLUMNS FROM $tableName";
        $existingColumnsResult = mysqli_query($conn, $existingColumnsQuery);
        $existingColumns = [];

        while ($row = mysqli_fetch_assoc($existingColumnsResult)) {
            $existingColumns[$row['Field']] = [
                'datatype' => strtoupper($row['Type']),
                'null' => ($row['Null'] == "YES") ? "NULL" : "NOT NULL",
                'key' => $row['Key'],
                'default' => $row['Default']
            ];
        }

        // Identify columns to ADD and MODIFY
        $columnsToAdd = [];
        $columnsToModify = [];

        foreach ($tblstr as $column) {
            $columnName = $column['columnname'];
            $newDefinition = "{$column['datatype']} {$column['constraints']}";
            if (strpos($column['constraints'], "DEFAULT") !== false && !empty($column['defaultText'])) {
                $newDefinition .= " DEFAULT '{$column['defaultText']}'";
            }

            if (!array_key_exists($columnName, $existingColumns)) {
                // Column does not exist, add it
                $columnsToAdd[] = "ALTER TABLE $tableName ADD $columnName $newDefinition";
            } else {
                // Column exists, check if it needs modification
                $existingDefinition = "{$existingColumns[$columnName]['datatype']} {$existingColumns[$columnName]['null']}";
                if (!empty($existingColumns[$columnName]['key'])) {
                    $existingDefinition .= " " . strtoupper($existingColumns[$columnName]['key']);
                }
                if (!empty($existingColumns[$columnName]['default'])) {
                    $existingDefinition .= " DEFAULT '{$existingColumns[$columnName]['default']}'";
                }

                if (trim($existingDefinition) !== trim($newDefinition)) {
                    // Column definition is different, modify it
                    $columnsToModify[] = "ALTER TABLE $tableName MODIFY $columnName $newDefinition";
                }
            }
        }

        // Execute Alter Queries to Add Columns
        foreach ($columnsToAdd as $query) {
            if (mysqli_query($conn, $query)) {
                $result["messages"][] = "Column added successfully.";
            } else {
                $result["status"] = "error";
                $result["messages"][] = "Error adding column: " . mysqli_error($conn);
            }
        }

        // Execute Alter Queries to Modify Columns
        foreach ($columnsToModify as $query) {
            if (mysqli_query($conn, $query)) {
                $result["messages"][] = "Column modified successfully.";
            } else {
                $result["status"] = "error";
                $result["messages"][] = "Error modifying column: " . mysqli_error($conn);
            }
        }

        if (empty($columnsToAdd) && empty($columnsToModify)) {
            $result["messages"][] = "No changes needed for $tableName.";
        }
    }

    return $result;
}
/*function createOrUpdateTable($tbldef, $tableName, $mastertype, $conn) {
    //$response = ["status" => "success", "messages" => []];
var_dump($tbldef);
exit;
	// Additional fields for "general" mastertype
    $generalFields = [
        ["columnname" => "companyid", "datatype" => "BIGINT(10)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "subcompanyid", "datatype" => "BIGINT(10)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "projectid", "datatype" => "BIGINT(10)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "tedit", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "loginid", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "edatetime", "datatype" => "DATETIME", "constraints" => "NULL DEFAULT CURRENT_TIMESTAMP", "defaultText" => ""]
    ];

    // Additional fields for "universal" mastertype
    $universalFields = [
        ["columnname" => "tedit", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "loginid", "datatype" => "VARCHAR(200)", "constraints" => "NOT NULL", "defaultText" => ""],
        ["columnname" => "edatetime", "datatype" => "DATETIME", "constraints" => "NULL DEFAULT CURRENT_TIMESTAMP", "defaultText" => ""]
    ];

    $tblstr=[];
    // If mastertype is "general", merge additional fields
    if ($mastertype === "general") {
        $tblstr = array_merge($tbldef, $generalFields);
    }
    else{// If mastertype is "universal", merge additional fields
        $tblstr = array_merge($tbldef, $universalFields);
    }



    // Check if the table exists
    $query = "SHOW TABLES LIKE '$tableName'";
    $tableExists = mysqli_num_rows(mysqli_query($conn, $query)) > 0;

    if (!$tableExists) {
        // Create table if it doesn't exist
        $columns = [];
        $foreignKeys = [];

        foreach ($tblstr as $column) {
            $colDef = "`{$column['columnname']}` {$column['datatype']}";
            if (!empty($column['constraints'])) {
                $colDef .= " {$column['constraints']}";
            }
            if (strpos($column['constraints'], "DEFAULT") !== false && !empty($column['defaultText'])) {
                $colDef .= " DEFAULT '{$column['defaultText']}'";
            }
            $columns[] = $colDef;

            // Handle Foreign Key Constraints
            if (strpos($column['constraints'], "FOREIGN KEY") !== false) {
                if (!empty($column['referenceTable']) && !empty($column['referenceColumn'])) {
                    $foreignKeys[] = "FOREIGN KEY (`{$column['columnname']}`) REFERENCES `{$column['referenceTable']}`(`{$column['referenceColumn']}`)";
                }
            }
        }

        $columns = array_merge($columns, $foreignKeys);
        $createQuery = "CREATE TABLE `$tableName` (" . implode(", ", $columns) . ")";
        
        if (mysqli_query($conn, $createQuery)) {
            $result["messages"][] = "Table `$tableName` created successfully.";
        } else {
            $result["status"] = "error";
            $result["messages"][] = "Error creating table `$tableName`: " . mysqli_error($conn);
        }
    } else {
        // Fetch existing columns
        $existingColumnsQuery = "SHOW COLUMNS FROM `$tableName`";
        $existingColumnsResult = mysqli_query($conn, $existingColumnsQuery);
        $existingColumns = [];

        while ($row = mysqli_fetch_assoc($existingColumnsResult)) {
            $existingColumns[$row['Field']] = [
                'datatype' => strtoupper($row['Type']),
                'null' => ($row['Null'] == "YES") ? "NULL" : "NOT NULL",
                'key' => $row['Key'],
                'default' => $row['Default']
            ];
        }

        // Identify columns to ADD and MODIFY
        $columnsToAdd = [];
        $columnsToModify = [];

        foreach ($tblstr as $column) {
            $columnName = $column['columnname'];
            $newDefinition = "{$column['datatype']} {$column['constraints']}";
            if (strpos($column['constraints'], "DEFAULT") !== false && !empty($column['defaultText'])) {
                $newDefinition .= " DEFAULT '{$column['defaultText']}'";
            }

            if (!array_key_exists($columnName, $existingColumns)) {
                // Column does not exist, add it
                $columnsToAdd[] = "ALTER TABLE `$tableName` ADD `$columnName` $newDefinition";
            } else {
                // Column exists, check if it needs modification
                $existingDefinition = "{$existingColumns[$columnName]['datatype']} {$existingColumns[$columnName]['null']}";
                if (!empty($existingColumns[$columnName]['key'])) {
                    $existingDefinition .= " " . strtoupper($existingColumns[$columnName]['key']);
                }
                if (!empty($existingColumns[$columnName]['default'])) {
                    $existingDefinition .= " DEFAULT '{$existingColumns[$columnName]['default']}'";
                }

                if (trim($existingDefinition) !== trim($newDefinition)) {
                    // Column definition is different, modify it
                    $columnsToModify[] = "ALTER TABLE `$tableName` MODIFY `$columnName` $newDefinition";
                }
            }
        }

        // Execute Alter Queries to Add Columns
        foreach ($columnsToAdd as $query) {
            if (mysqli_query($conn, $query)) {
                $result["messages"][] = "Column added successfully.";
            } else {
                $result["status"] = "error";
                $result["messages"][] = "Error adding column: " . mysqli_error($conn);
            }
        }

        // Execute Alter Queries to Modify Columns
        foreach ($columnsToModify as $query) {
            if (mysqli_query($conn, $query)) {
                $result["messages"][] = "Column modified successfully.";
            } else {
                $result["status"] = "error";
                $result["messages"][] = "Error modifying column: " . mysqli_error($conn);
            }
        }

        if (empty($columnsToAdd) && empty($columnsToModify)) {
            $result["messages"][] = "No changes needed for `$tableName`.";
        }
    }

    return $result;
}
*/

function sanitizeString($input) {
    // Remove special characters except spaces
    $input = preg_replace('/[^a-zA-Z0-9\s]/', '', $input);
    
    // Replace multiple spaces with a single space
    $input = preg_replace('/\s+/', ' ', $input);
    
    // change Case To lower
    $input = strtolower($input);

    // Replace spaces with underscores
    return str_replace(' ', '_', trim($input));
}

function desanitizeString($input) {
    // Replace spaces with underscores
    return ucwords(strtolower(str_replace(' ', '_', trim($input))));
}
?>