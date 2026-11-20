<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../../connection.php');
require('../../functions/function_master.php');
require('../../functions/function_savetodb.php');

$action=$_GET['action'];

if($action=="newemployeeregistration"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	
	$employeetype = $_POST['employeetype'];
	$employeename = $_POST['employeename'];
	$dob = $_POST['empdob'];
	$gender = $_POST['empgender'];
	$maritalstatus = $_POST['empmaritalstatus'];
	$religion = $_POST['empreligion'];
	$contactnumber = $_POST['contactnumber'];
	$contactnumber2 = $_POST['emernumber'];
	$presaddress = $_POST['presaddress'];
	$prmaddress = $_POST['prmaddress'];
	$nationality = $_POST['nationality'];
	$emailaddress = $_POST['eemail'];
	$adharnumber = $_POST['adharnumber'];
	$pannumber = $_POST['pannumber'];
	$highest_qualification = $_POST['highest_qualification'];
	$specialisation = $_POST['specialisation'];
	$institution = $_POST['institution'];
	$yearofpassing = $_POST['yearofpassing'];
	$experience = $_POST['experience'];
	$doj = $_POST['doj'];
	$intime = $_POST['intime'];
	$outtime = $_POST['outtime'];
	$paymentmode = $_POST['paymentmode'];
	$totalsalary = $_POST['totalsalary'];
	$bankname = $_POST['bankname'];
	$accountno = $_POST['accountno'];
	$ifsccode = $_POST['ifsccode'];

	$fathername = $_POST['fathername'];
	$mothername = $_POST['mothername'];
	$loginname = $_POST['loginname'];

	$uploadphoto = $_POST['uploadphoto'];
	$uploadadhar = $_POST['uploadadhar'];
	$uploadpan = $_POST['uploadpan'];
	$uploadresume = $_POST['uploadresume'];
	$uploadbank = $_POST['uploadbank'];
	$uploadeducation = $_POST['uploadeducation'];

	$photodir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/empErpDocuments/photo-document/";
	$aadhardir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/empErpDocuments/aadhar-document/";
	$pandir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/empErpDocuments/pan-document/";
	$cvdir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/empErpDocuments/cv-document/";
	$bankdir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/empErpDocuments/bank-document/";
	$educationdir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/empErpDocuments/education-document/";

	$cci=getcompanydetails($conn,"companyid",$companyid);
	$countryid="";
	foreach ($cci as $cckey) {
		$countryid=$cckey['countryid'];
	}

	$sq=$conn->query("SELECT (count(sno) + 1) as mxsno FROM payroll_employeemaster where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
	$empid="";
	if($sq->num_rows>0){
		$erow=$sq->fetch_assoc();
		$empid=$erow['mxsno'];
	}
	$ssst=array();
	if($empid){
		$schk=$conn->query("SELECT sno FROM payroll_employeemaster where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(empname)='".$employeename."' and dob='".$dob."'");
		if($schk->num_rows>0){
			$ssst=array('status'=>'Error', 'message'=>'Already rgister with these details', 'empid'=>'');
		}else{
			$photofilepath ="";
			if($_FILES['uploadphoto']['name']){
				if (!is_dir( $photodir ) ) {
				    mkdir($photodir, 0755, true);       
				}
		        $temp = explode(".", $_FILES["uploadphoto"]["name"]);
				$extension=end($temp);
				$newfilename = strtolower(str_replace(" ", "", $employeename)) . '.' . end($temp);
		        move_uploaded_file($_FILES["uploadphoto"]["tmp_name"], $photodir . $newfilename);
		        $photofilepath = $photodir . $newfilename;
			}
			$aadharfilepath ="";
			if($_FILES['uploadadhar']['name']){
				if ( !is_dir( $aadhardir ) ) {
				    mkdir($aadhardir, 0755, true);       
				}
		        $temp = explode(".", $_FILES["uploadadhar"]["name"]);
				$extension=end($temp);
				$newfilename = strtolower(str_replace(" ", "", $employeename)) . '.' . end($temp);
		        move_uploaded_file($_FILES["uploadadhar"]["tmp_name"], $aadhardir . $newfilename);
		        $aadharfilepath = $aadhardir . $newfilename;
			}
			$panfilepath = "";
			if($_FILES['uploadpan']['name']){
				if ( !is_dir( $pandir ) ) {
				    mkdir($pandir, 0755, true);       
				}
		        $temp = explode(".", $_FILES["uploadpan"]["name"]);
				$extension=end($temp);
				$newfilename = strtolower(str_replace(" ", "", $employeename)) . '.' . end($temp);
		        move_uploaded_file($_FILES["uploadpan"]["tmp_name"], $pandir . $newfilename);
		        $panfilepath = $pandir . $newfilename;
			}
			 $cvfilepath ="";
			if($_FILES['uploadresume']['name']){
				if ( !is_dir( $cvdir ) ) {
				    mkdir($cvdir, 0755, true);       
				}
		        $temp = explode(".", $_FILES["uploadresume"]["name"]);
				$extension=end($temp);
				$newfilename = strtolower(str_replace(" ", "", $employeename)) . '.' . end($temp);
		        move_uploaded_file($_FILES["uploadresume"]["tmp_name"], $cvdir . $newfilename);
		        $cvfilepath = $cvdir . $newfilename;
			}
			$bankfilepath ="";
			if($_FILES['uploadbank']['name']){
				if ( !is_dir( $bankdir ) ) {
				    mkdir($bankdir, 0755, true);       
				}
		        $temp = explode(".", $_FILES["uploadbank"]["name"]);
				$extension=end($temp);
				$newfilename = strtolower(str_replace(" ", "", $employeename)) . '.' . end($temp);
		        move_uploaded_file($_FILES["uploadbank"]["tmp_name"], $bankdir . $newfilename);
		        $bankfilepath = $bankdir . $newfilename;
			}
			 $educationfilepath ="";
			if($_FILES['uploadeducation']['name']){
				if ( !is_dir( $educationdir ) ) {
				    mkdir($educationdir, 0755, true);       
				}
		        $temp = explode(".", $_FILES["uploadeducation"]["name"]);
				$extension=end($temp);
				$newfilename = strtolower(str_replace(" ", "", $employeename)) . '.' . end($temp);
		        move_uploaded_file($_FILES["uploadeducation"]["tmp_name"], $educationdir . $newfilename);
		        $educationfilepath = $educationdir . $newfilename;
			}
			
			// echo  "INSERT INTO payroll_employeemaster(companyid, subcompanyid, projectid, empname, empid, dob, doj, intime, outtime, mstatus, gender, mobileno, emailid, religion, presentaddress, permanentaddress, nationality, experiance, pancardno, adharcardno, bankname, accountno, ifsccode, payment_mode, basicsalary, hra, conveyance, otherallowance, institution, highestqualificationn, specialisation, fathername, mothername, empphoto, upload_bank, upload_education, upload_resume, adhardoc, pandoc, employmenttype, updateby) VALUES('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$employeename."', '".$empid."', '".$dob."', '".$doj."', '".$intime."', '".$outtime."', '".$maritalstatus."', '".$gender."', '".$contactnumber."', '".$emailaddress."', '".$religion."', '".$presaddress."', '".$prmaddress."', '".$nationality."', '".$experience."', '".$pannumber."', '".$adharnumber."', '".$bankname."', '".$accountno."', '".$ifsccode."', '".$paymentmode."', '".$totalsalary."', '0','0','0', '".$institution."', '".$highest_qualification."', '".$specialisation."', '".$fathername."', '".$mothername."', '".str_replace("../", "", $photofilepath)."', '".str_replace("../", "", $bankfilepath)."', '".str_replace("../", "", $educationfilepath)."', '".str_replace("../", "", $uploadresume)."', '".str_replace("../", "", $aadharfilepath)."', '".str_replace("../", "", $panfilepath)."', '".$employeetype."', '".$loginname."')";

			$conn->query("INSERT INTO payroll_employeemaster(companyid, subcompanyid, projectid, empname, empid, dob, doj, intime, outtime, mstatus, gender, mobileno, emailid, religion, presentaddress, permanentaddress, nationality, experiance, pancardno, adharcardno, bankname, accountno, ifsccode, payment_mode, basicsalary, hra, conveyance, otherallowance, institution, highestqualificationn, specialisation, fathername, mothername, empphoto, upload_bank, upload_education, upload_resume, adhardoc, pandoc, employmenttype, updateby) VALUES('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$employeename."', '".$empid."', '".$dob."', '".$doj."', '".$intime."', '".$outtime."', '".$maritalstatus."', '".$gender."', '".$contactnumber."', '".$emailaddress."', '".$religion."', '".$presaddress."', '".$prmaddress."', '".$nationality."', '".$experience."', '".$pannumber."', '".$adharnumber."', '".$bankname."', '".$accountno."', '".$ifsccode."', '".$paymentmode."', '".$totalsalary."', '0','0','0', '".$institution."', '".$highest_qualification."', '".$specialisation."', '".$fathername."', '".$mothername."', '".str_replace("../", "", $photofilepath)."', '".str_replace("../", "", $bankfilepath)."', '".str_replace("../", "", $educationfilepath)."', '".str_replace("../", "", $uploadresume)."', '".str_replace("../", "", $aadharfilepath)."', '".str_replace("../", "", $panfilepath)."', '".$employeetype."', '".$loginname."')");
			if(mysqli_affected_rows($conn)>0){
				savetodbnewpayto_details($conn,$employeename,$accountno,$bankname,$ifsccode,'','','',$companyid,$subcompanyid,$projectid,$contactnumber,$emailaddress,$loginname,$empid);
				$ssst=array('status'=>'Success', 'message'=>'Employee registration successfully', 'empid'=>$empid);
			}else{
				$ssst=array('status'=>'Error', 'message'=>'Employee registration did not save, due to technical issue.', 'empid'=>'');
			}
		}
	}else{
		$ssst=array('status'=>'Error', 'message'=>'Employee registration did not save, due to employee id did not genrate, please try again.', 'empid'=>'');
	}
	echo json_encode($ssst);
}
?>