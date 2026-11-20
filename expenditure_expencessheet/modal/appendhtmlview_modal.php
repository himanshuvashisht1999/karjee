<?php
require('../../connection.php');
require('../../functions/function_master.php');
$action=$_GET['action'];
if($action=="getbeneficiaryviewhtml"){
	$ispaytobeneficiaryvalu = $_POST['ispaytobeneficiaryvalu'];
	$beneficiarycount = $_POST['beneficiarycount'];
	$paytoname = $_POST['paytoname'];
	$withdrowamount = $_POST['withdrowamount'];
	$projectid = $_POST['projectid'];
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$paymenttype = $_POST['paymenttype'];
	$ispatosameasbenf = $_POST['ispatosameasbenf'];
	$samepatoname = $_POST['samepatoname'];
	$bnfdiv="";
	//$purposelist=getpurposemaster($mconn,'','all');
	$purposesubcatlist=GetPurposeCategoryInfofrommaster($mconn,$companyid,$subcompanyid,$projectid,$paymenttype);
	// print_r($purposelist);
	 if($ispaytobeneficiaryvalu=="no" && $beneficiarycount==1){
	 	
	 	$b="b_".$i+1;
		$m="m_".$i+1;
		$p="p_".$i+1;
		$d="d_".$i+1;
		$scp="scp_".$i+1;
		$rbtBNF="rbtB_".$i+1;
		$cmbbnf="cmb_benf_".$i+1;
		$idBFM="idmaster_".$i+1;
		$idMNUL="idmanual_".$i+1;
		$fileid="filem_".$i+1;
		$rbtPUR="rbtP_".$i+1;
		$idPUCFM="idPUCmaster_".$i+1;
		$idPURFM="idPURmaster_".$i+1;
		$idfrommanual="idPURmanual_".$i+1;
		$pm="pm_".$i+1;
		$filename="filename_".$i+1;
		$bnfdiv.='<details>
			<summary>Please provide the Benefaicery information</summary>
			<div class="main-container bg-white">

					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Do you want to select beneficiary from master :</label>
							 <input type="radio" name='.$rbtBNF.' class="rbtBNF" value="yes" style="transform: scale(2);" /> <label>YES</label>
           					 <input type="radio" name='.$rbtBNF.' class="rbtBNF" value="no" style="transform: scale(2);" /> <label>NO</label>
						</div>
					</div>
					<div class="row p-2" id='.$idBFM.' style="display:none;">
						<div class="col-md-12">
							<label class="lbl-text">Select beneficiaryXXXX from master :</label>
							 <select class="form-control selectbenfM" id='.$cmbbnf.'>
             					<option selected>Select beneficiary from master</option>
          					 </select>
						</div>
					</div>

					<div class="row p-2" id='.$idMNUL.' style="display:none;">
						<div class="col-md-12">
							<label class="lbl-text">Enter beneficiary name :</label>
							<input type="text" id='.$b.' class="form-control beneficiary_input" value='.$samepatoname.'>
						</div>
					</div>

					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Amount  :</label>
							<input type="number" id='.$m.' class="form-control payableamount_input" />
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Do you want to select purpose from master :</label>
							 <input type="radio" name='.$rbtPUR.' class="rbtPUR" value="yes" style="transform: scale(2);" /> <label>YES</label>
           					 <input type="radio" name='.$rbtPUR.' class="rbtPUR" value="no" style="transform: scale(2);" /> <label>NO</label>
						</div>
					</div>
					<div class="row p-2" id='.$idPUCFM.' style="display:none;">
						<div class="col-md-12">
							<label class="lbl-text">Select purpose heading :</label>
							<select class="form-control purposeheading_select" id='.$scp.'>';
							$bnfdiv.='<option value="" selected>Select purpose heading</option>';
							foreach ($purposesubcatlist as $cpkey) {
								$bnfdiv.='<option value="'.$cpkey['subcategoryid'].'">'.$cpkey['subcategory'].'</option>';
							}
						$bnfdiv.='</select>
						</div>
					</div>
					<div class="row p-2" id='.$idPURFM.' style="display:none;">
						<div class="col-md-12">
							<label class="lbl-text">Select purpose name :</label>
							<select class="form-control purpose_select" id='.$p.'>';
							$bnfdiv.='<option value="" selected>Select purpose name</option>';
							// foreach ($purposelist as $pkey) {
							// 	$bnfdiv.='<option value="'.$pkey['purposecode'].'">'.$pkey['purpose'].'</option>';
							// }
						$bnfdiv.='</select>
						</div>
					</div>
					<div class="row p-2" id='.$idfrommanual.' style="display:none;">
						<div class="col-md-12">
							<label class="lbl-text">Enter Purpose Name :</label>
							<input type="text" id='.$pm.' class="form-control beneficiary_input">
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Remarks :</label>
							<input type="text" id='.$d.' class="form-control description_input" />
						</div>
					</div>

					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Do you have any supporting document for this beneficiary? :</label>
							<input type="file" name="files[]" id='.$fileid.' class="w3-input fileclass" multiple accept="image/*"/>
						</div>
					</div>
					<div class="row p-2" style="display:none;">
						<div class="col-md-12">
							<label class="lbl-text">File Name :</label>
							<input type="text" id='.$filename.' class="form-control description_input" />
						</div>
					</div>
				
				
			</div>
		</details>';
	}else if($ispaytobeneficiaryvalu=="no" && $beneficiarycount>1){
		for($i=0; $i<$beneficiarycount; $i++){
			if($i==0){
				$bnflbl=($i+1)."st";
			}else if($i==1){
				$bnflbl=($i+1)."nd";
			}else if($i==2){
				$bnflbl=($i+1)."rd";
			}else{
				$bnflbl=($i+1)."th";
			}
			$b="b_".$i+1;
			$m="m_".$i+1;
			$p="p_".$i+1;
			$d="d_".$i+1;
			$scp="scp_".$i+1;
			$rbtBNF="rbtB_".$i+1;
			$cmbbnf="cmb_benf_".$i+1;
			$idBFM="idmaster_".$i+1;
			$idMNUL="idmanual_".$i+1;
			$fileid="filem_".$i+1;
			$rbtPUR="rbtP_".$i+1;
			$idPUCFM="idPUCmaster_".$i+1;
		    $idPURFM="idPURmaster_".$i+1;
		    $idfrommanual="idPURmanual_".$i+1;
		    $filename="filename_".$i+1;
		    $pm="pm_".$i+1;
			$bnfdiv.='<details>
				<summary>Please provide the '.$bnflbl.' Benefaicery information</summary>
				<div class="main-container bg-white">
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Do you want to select beneficiary from master :</label>
							 <input type="radio" name='.$rbtBNF.' class="rbtBNF" value="yes" style="transform: scale(2);" /> <label>YES</label>
           					 <input type="radio" name='.$rbtBNF.' class="rbtBNF" value="no" style="transform: scale(2);" /> <label>NO</label>
						</div>
					</div>
					<div class="row p-2" id='.$idBFM.' style="display:none;">
						<div class="col-md-12">
							<label class="lbl-text">Select beneficiary from master :</label>
							 <select class="form-control selectbenfM" id='.$cmbbnf.'>
             					<option selected>Select beneficiary from master</option>
          					 </select>
						</div>
					</div>
					<div class="row p-2" id='.$idMNUL.' style="display:none;">
						<div class="col-md-12">
							<label class="lbl-text">Name of the  beneficiary</label>
							<input type="text" id='.$b.' class="form-control beneficiary_input" value='.$samepatoname.'>
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Amount :</label>
							<input type="number" id='.$m.' class="form-control payableamount_input" />
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Do you want to select purpose from master :</label>
							 <input type="radio" name='.$rbtPUR.' class="rbtPUR" value="yes" style="transform: scale(2);" /> <label>YES</label>
           					 <input type="radio" name='.$rbtPUR.' class="rbtPUR" value="no" style="transform: scale(2);" /> <label>NO</label>
						</div>
					</div>
					<div class="row p-2" id='.$idPUCFM.' style="display:none;">
						<div class="col-md-12">
							<label class="lbl-text">Select purpose heading :</label>
							<select class="form-control purposeheading_select" id='.$scp.'>';
							$bnfdiv.='<option value="" selected>Select purpose heading</option>';
							foreach ($purposesubcatlist as $cpkey) {
								$bnfdiv.='<option value="'.$cpkey['subcategoryid'].'">'.$cpkey['subcategory'].'</option>';
							}
						$bnfdiv.='</select>
						</div>
					</div>
					<div class="row p-2" id='.$idPURFM.' style="display:none;">
						<div class="col-md-12">
							<label class="lbl-text">Purpose name :</label>
							<select class="form-control purpose_select" id='.$p.'>';
							$bnfdiv.='<option value="" selected>Select Purpose</option>';
							// foreach ($purposelist as $pkey) {
							// 	$bnfdiv.='<option value="'.$pkey['purposecode'].'">'.$pkey['purpose'].'</option>';
							// }
						$bnfdiv.='</select>
						</div>
					</div>
					<div class="row p-2" id='.$idfrommanual.' style="display:none;">
						<div class="col-md-12">
							<label class="lbl-text">Enter Purpose Name :</label>
							<input type="text" id='.$pm.' class="form-control beneficiary_input">
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Remarks :</label>
							<input type="text" id='.$d.' class="form-control description_input" />
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Do you have any supporting document for this beneficiary? :</label>
							<input type="file" name="files[]" id='.$fileid.' class="w3-input fileclass" multiple accept="image/*"/>
						</div>
					</div>
					<div class="row p-2" style="display:none;">
						<div class="col-md-12">
							<label class="lbl-text">File Name :</label>
							<input type="text" id='.$filename.' class="form-control description_input" />
						</div>
					</div>

				</div>
			</details>';
		}
	}
	echo $bnfdiv;
}else if($action=="matchedbeneficiaryviewhtml") {
	$beneficiarycount = $_POST['beneficiarycount'];
	$companyid = $_POST['beneficiarycount'];
	$subcompanyid = $_POST['beneficiarycount'];
	$projectid = $_POST['beneficiarycount'];
	$tsno = $_POST['tsno'];
	$paymenttype="expenditure";
	//$paymenttype=getpaymenttype($mconn,$tsno);
	$purposelist=getpurposemaster($mconn);
	$purposesubcatlist=GetPurposeCategoryInfofrommaster($mconn,$companyid,$subcompanyid,$projectid,$paymenttype);
	$detailstrn=gettransactiondetails($mconn,$tsno);
	count($detailstrn);
	foreach ($detailstrn as $dkey){
			if($i==0){
				$bnflbl=($i+1)."st";
			}else if($i==1){
				$bnflbl=($i+1)."nd";
			}else if($i==2){
				$bnflbl=($i+1)."rd";
			}else{
				$bnflbl=($i+1)."th";
			}
			$b="b_".$i+1;
			$m="m_".$i+1;
			$p="p_".$i+1;
			$d="d_".$i+1;
			$scp="scp_".$i+1;
			$bnfdiv.='<details>
				<summary>Please provide the '.(count($detailstrn)>1?$bnflbl:"").' Beneficiary information</summary>
				<div class="main-container bg-white">
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Name of the  beneficiary</label>
							<input type="text" id='.$b.' class="form-control beneficiary_input" value="'.$dkey['beneficiaryname'].'">
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Amount Payable of the beneficiary</label>
							<input type="text" id='.$m.' class="form-control payableamount_input" value="'.$dkey['amount'].'" >
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Purpose Subcategory of paying the beneficiary</label>
							<select class="form-control purposesubcat_select" id='.$scp.'>';
							$bnfdiv.='<option value="" selected>Select Purpose Subcategory</option>';
							foreach ($purposesubcatlist as $cpkey) {
								$bnfdiv.='<option value="'.$cpkey['subcategoryid'].'">'.$cpkey['subcategory'].'</option>';
							}
							// $bnfdiv.='<option value="'.$dkey['subcategoryid'].'" selected>'.$dkey['subcategory'].'</option>';
						$bnfdiv.='</select>
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Purpose of paying the beneficiary</label>
							<select class="form-control purpose_select" id='.$p.'>';
							$bnfdiv.='<option value="" selected>Select Purpose</option>';
							foreach ($purposelist as $pkey) {
								$bnfdiv.='<option value="'.$pkey['purposecode'].'">'.$pkey['purpose'].'</option>';
							}
							//$bnfdiv.='<option value="'.$dkey['purposecode'].'" selected>'.$dkey['purpose'].'</option>';
						$bnfdiv.='</select>
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Description of the beneficiary</label>
							<input type="text" id='.$d.' class="form-control description_input" value="'.$dkey['description']." ".$dkey['remarks'].'">
						</div>
					</div>

				</div>
			</details>';
			$i++;
		}
		echo $bnfdiv;
}else if ($action=="getpaymenttypefromexpences") {
	$companyid = $_POST['beneficiarycount'];
	$subcompanyid = $_POST['beneficiarycount'];
	$projectid = $_POST['beneficiarycount'];
	$tsno = $_POST['tsno'];
	$paymenttype=getpaymenttype($mconn,$tsno);
	echo $paymenttype;
}else if ($action=="depositmatchedbeneficiaryviewhtml"){
	$matchentry = $_POST['matchentry'];
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$tsno = $_POST['tsno'];
	$paymenttype = $_POST['getpurpose'];
	$depostername = $_POST['depostername'];
	$depositamount = $_POST['depositamount'];
	$description = $_POST['description'];

	$purposesubcatlist=GetPurposeCategoryInfofrommasterdeposits($mconn,$companyid,$subcompanyid,$projectid,$paymenttype);
	$purposelist=getpurposemasterdepositer($mconn,$paymenttype);
	for($i=0; $i<$matchentry; $i++){
			if($i==0){
				$bnflbl=($i+1)."st";
			}else if($i==1){
				$bnflbl=($i+1)."nd";
			}else if($i==2){
				$bnflbl=($i+1)."rd";
			}else{
				$bnflbl=($i+1)."th";
			}
			$b="b_".$i+1;
			$m="m_".$i+1;
			$p="p_".$i+1;
			$d="d_".$i+1;
			$scp="scp_".$i+1;
			$bnfdiv.='<details open>
				<summary>Please provide the '.($matchentry>1?$bnflbl:"").' Depositer information</summary>
				<div class="main-container bg-white">
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Name of the  depositer</label>
							<input type="text" id='.$b.' class="form-control beneficiary_input" value="'.$depostername.'">
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Amount deposit by the  depositer</label>
							<input type="text" id='.$m.' class="form-control payableamount_input" value="'.$depositamount.'" />
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Purpose Subcategory of paying the  depositer</label>
							<select class="form-control purposesubcat_select" id='.$scp.'>';
							$bnfdiv.='<option value="" selected>Select Purpose Subcategory</option>';
							foreach ($purposesubcatlist as $cpkey) {
								
								if (count($purposesubcatlist)==1) {
										$bnfdiv.='<option value="'.$cpkey['subcategoryid'].'" selected>'.$cpkey['subcategory'].'</option>';
								}else {
									$bnfdiv.='<option value="'.$cpkey['subcategoryid'].'">'.$cpkey['subcategory'].'</option>';
								}
							}
						$bnfdiv.='</select>
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Purpose of paying the  depositer</label>
							<select class="form-control purpose_select" id='.$p.'>';
							$bnfdiv.='<option value="" selected>Select Purpose</option>';
							foreach ($purposelist as $pkey) {
								if ($pkey['purpose']==$paymenttype) {
									$bnfdiv.='<option value="'.$pkey['purposecode'].'" selected>'.$pkey['purpose'].'</option>';
								}else{
									$bnfdiv.='<option value="'.$pkey['purposecode'].'">'.$pkey['purpose'].'</option>';
								}
								
							}
						$bnfdiv.='</select>
						</div>
					</div>
					<div class="row p-2">
						<div class="col-md-12">
							<label class="lbl-text">Description of the  depositer</label>
							<input type="text" id='.$d.' class="form-control description_input" value="'.$description.'" />
						</div>
					</div>

				</div>
			</details>';
		}
		echo $bnfdiv;
}else if ($action=="getpurposeviewhtml") {
	$subcatid = $_POST['subcatid'];
	$subcatname = $_POST['subcatname'];
	$purposelist=getpurposemaster($mconn,$subcatid,'subcategory');
	echo json_encode($purposelist); 
}
function getpurposemaster($conn,$subcat,$shortby)
{
	if ($shortby=='all') {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary FROM purpose_code_master order by purpose");
	}else if ($shortby=='subcategory') {
		 // echo("select category,categoryid,sub_category,sub_categoryid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary FROM purpose_code_master where sub_categoryid='".$subcat."' order by purpose");
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary FROM purpose_code_master where sub_categoryid='".$subcat."' order by purpose");
	}
	
	if($sltp->num_rows>0){
		while($imrow = $sltp->fetch_assoc()){
			$comptpurpose = $imrow['purpose'].'('.$imrow['purposecode'].')';
			$purposewih_prov = $imrow['purpose'].'('.$imrow['provided_code'].')';
			$sltplist[] = array('pcategory'=>$imrow['category'], 'pcategorycode'=>$imrow['categoryid'], 'psub_category'=>$imrow['sub_category'], 'psub_categoryid'=>$imrow['sub_categoryid'],'purpose'=>$imrow['purpose'], 'purposecode'=>$imrow['purposecode'], 'purposewithprovidedcode'=>$purposewih_prov, 'purposewithcode'=>$comptpurpose,'isforstock'=>$imrow['isforstock'],'budget_permonth'=>$imrow['budget_per_month'],'total_peryear'=>$imrow['total_per_year'], 'provided_code'=>$imrow['provided_code'], 'default_beneficiary'=>$imrow['default_beneficiary']);
		}
	}else{
		$sltplist = array();
	}
	return $sltplist;
}

function getpurposemasterdepositer($conn,$category)
{
	if ($category) {
		$sltp = $conn->query("select category,categoryid,subcategory,subcategoryid,purpose,purpose_code,provided_code FROM tbl_purpose_master_credit where lcase(category)='".strtolower($category)."' order by purpose");
	}else {
		$sltp = $conn->query("select category,categoryid,subcategory,subcategoryid,purpose,purpose_code,provided_code FROM tbl_purpose_master_credit  order by purpose");
	}
	
	if($sltp->num_rows>0){
		while($imrow = $sltp->fetch_assoc()){
			$comptpurpose = $imrow['purpose'].'('.$imrow['purposecode'].')';
			$purposewih_prov = $imrow['purpose'].'('.$imrow['provided_code'].')';
			$sltplist[] = array('pcategory'=>$imrow['category'], 'pcategorycode'=>$imrow['categoryid'], 'psub_category'=>$imrow['subcategory'], 'psub_categoryid'=>$imrow['subcategoryid'],'purpose'=>$imrow['purpose'], 'purposecode'=>$imrow['purpose_code']);
		}
	}else{
		$sltplist = array();
	}
	return $sltplist;
}

function getpaymenttype($mconn,$tsno)
{
	$sltp = $mconn->query("select ifnull(paymenttype,'') as paymenttype FROM tbl_expencesinexcelsheet where tsno='".$tsno."'");
	if($sltp->num_rows>0){
		$imrow = $sltp->fetch_assoc();
			
		$sltplist = $imrow['paymenttype'];
		
	}else{
		$sltplist = "";
	}
	return $sltplist;
}

function gettransactiondetails($mconn,$tsno)
{	


	$sltp = $mconn->query("select beneficiaryname,amount,purpose,purpose_code,categoryid,category,sub_categoryid,sub_category,description,remarks,status FROM tbl_expencesinexcelsheet where ".$tsno."");
	if($sltp->num_rows>0){
		while($imrow = $sltp->fetch_assoc()){
			
			$sltplist[] = array('beneficiaryname'=>$imrow['beneficiaryname'], 'amount'=>$imrow['amount'], 'purpose'=>$imrow['purpose'], 'purposecode'=>$imrow['purpose_code'],'categoryid'=>$imrow['categoryid'], 'category'=>$imrow['category'], 'subcategoryid'=>$imrow['sub_categoryid'], 'subcategory'=>$imrow['sub_category'],'description'=>$imrow['description'],'remarks'=>$imrow['remarks'], 'source'=>$imrow['source']);
		}
	}else{
		$sltplist = array();
	}
	return $sltplist;
}
?>