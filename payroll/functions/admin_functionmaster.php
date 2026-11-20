<?php
//modified by ajay 27-05-2025
ini_set('display_errors', 1);
set_time_limit(500);
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED & ~E_NOTICE);
// Created By Chatur Singh
function getcompanydetails($conn,$shortby,$shortbyvalue){
	if($shortby=="all"){
		$comq="select sno, countryid, companyid, subcompanyid, companyname, stateid, districtid, pincode, address, contact_number, email_id, registration_no, logo, footer FROM tbl_company_master where (tedit is null or tedit='')";
	}else if($shortby=="companyid"){
		$comq="select sno, countryid, companyid, subcompanyid, companyname, stateid, districtid, pincode, address, contact_number, email_id, registration_no, logo, footer FROM tbl_company_master where (tedit is null or tedit='') and companyid in ('".str_replace(",", "','", $shortbyvalue)."')";
	}else if($shortby=="sno"){
		$comq="select sno, countryid, companyid, subcompanyid, companyname, stateid, districtid, pincode, address, contact_number, email_id, registration_no, logo, footer FROM tbl_company_master where (tedit is null or tedit='') and sno='".$shortbyvalue."'";
	}
	$comq_result=$conn->query($comq);
	$companylist = array();
	if($comq_result->num_rows>0){
		while($cmprow=$comq_result->fetch_assoc()){
			$companylist[]=array('sno'=>$cmprow['sno'], 'countryid'=>$cmprow['countryid'], 'companyid'=>$cmprow['companyid'], 'subcompanyid'=>$cmprow['subcompanyid'], 'company_name'=>$cmprow['companyname'], 'address'=>$cmprow['address'], 'stateid'=>$cmprow['stateid'], 'districtid'=>$cmprow['districtid'], 'pincode'=>$cmprow['pincode'], 'contact_number'=>$cmprow['contact_number'], 'email'=>$cmprow['email_id'], 'registration_no'=>$cmprow['registration_no'], 'logo'=>$cmprow['logo'], 'footer'=>$cmprow['footer']);
		}
	}
	return $companylist;
}
// Created By Chatur Singh
function getsubcompanydetails($conn,$companyid,$subcompanyid,$shortby,$shortbyvalue){
	if($shortby=="all"){
		$ssbq="select sno, registrationdate, countryid, companyid, subcompanyid, subcompanyname, stateid, districtid, financialyear_start, identifier, emailid as invoicing_emailid FROM tbl_company_submaster WHERE companyid='".$companyid."' and (tedit is null or tedit='')";
	}
	else if($shortby=="subcompanyid"){
		 $ssbq="select sno, registrationdate, countryid, companyid, subcompanyid, subcompanyname, stateid, districtid, financialyear_start, identifier, emailid as invoicing_emailid FROM tbl_company_submaster WHERE (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid in ('".str_replace(",", "','", $shortbyvalue)."')";
	}
	else if($shortby=="allsubcompanywithcopanyid"){
		$ssbq="select sno, registrationdate, countryid, companyid, subcompanyid, subcompanyname, stateid, districtid, financialyear_start, identifier, emailid as invoicing_emailid FROM tbl_company_submaster WHERE (tedit is null or tedit='') and companyid in ('".str_replace(",", "','", $shortbyvalue)."')";
	}
	$ssbq_result=$conn->query($ssbq) or die($conn->error);
	$sblist=array();
	if($ssbq_result->num_rows>0){
		while($sbrow=$ssbq_result->fetch_assoc()){
			$sblist[]=array('sno'=>$sbrow['sno'], 'registrationdate'=>$sbrow['registrationdate'], 'countryid'=>$sbrow['countryid'], 'subcompanyid'=>$sbrow['subcompanyid'], 'companyid'=>$sbrow['companyid'], 'subcompanyname'=>$sbrow['subcompanyname'], 'stateid'=>$sbrow['stateid'], 'districtid'=>$sbrow['districtid'], 'financialyear_start'=>$sbrow['financialyear_start'], 'identifier'=>$sbrow['identifier'], 'invoicing_emailid'=>$sbrow['invoicing_emailid']);
		}
	}
	return $sblist;
}
// Created By Chatur Singh
function getprojectdetailwithcmpsubcmp($conn,$companyid,$subcompanyid,$shortby,$shortbyvalue){
	if($shortby=="all"){
		$ppq=$conn->query("SELECT p.companyid, p.subcompanyid, p.projectid, p.projectname, cm.companyname, scm.subcompanyname FROM tbl_project_master p LEFT JOIN tbl_company_master cm ON p.companyid = cm.companyid AND (cm.tedit IS NULL OR cm.tedit = '') AND (p.tedit IS NULL OR p.tedit='') AND cm.companyname IS NOT NULL LEFT JOIN tbl_company_submaster scm ON p.companyid = scm.companyid AND p.subcompanyid = scm.subcompanyid AND (scm.tedit IS NULL OR scm.tedit = '') AND scm.subcompanyname IS NOT NULL WHERE p.companyid IS NOT NULL AND p.companyid <> '' AND p.subcompanyid IS NOT NULL AND p.subcompanyid <> '' AND cm.companyname IS NOT NULL AND scm.subcompanyname IS NOT NULL order by p.companyid, p.subcompanyid, p.projectid");
	}else if($shortby=="withauthrizedcompanyid"){
		$ppq=$conn->query("SELECT p.companyid, p.subcompanyid, p.projectid, p.projectname, cm.companyname, scm.subcompanyname FROM tbl_project_master p LEFT JOIN tbl_company_master cm ON p.companyid = cm.companyid AND (cm.tedit IS NULL OR cm.tedit = '') AND (p.tedit IS NULL OR p.tedit='') AND cm.companyname IS NOT NULL LEFT JOIN tbl_company_submaster scm ON p.companyid = scm.companyid AND p.subcompanyid = scm.subcompanyid AND (scm.tedit IS NULL OR scm.tedit = '') AND scm.subcompanyname IS NOT NULL WHERE p.companyid IS NOT NULL AND p.companyid <> '' AND p.subcompanyid IS NOT NULL AND p.subcompanyid <> '' AND cm.companyname IS NOT NULL AND scm.subcompanyname IS NOT NULL and p.companyid in('".str_replace(",", "','", $companyid)."') order by p.companyid, p.subcompanyid, p.projectid");
	}else if($shortby=="withauthrizedsubcompanyid"){
		$ppq=$conn->query("SELECT p.companyid, p.subcompanyid, p.projectid, p.projectname, cm.companyname, scm.subcompanyname FROM tbl_project_master p LEFT JOIN tbl_company_master cm ON p.companyid = cm.companyid AND (cm.tedit IS NULL OR cm.tedit = '') AND (p.tedit IS NULL OR p.tedit='') AND cm.companyname IS NOT NULL LEFT JOIN tbl_company_submaster scm ON p.companyid = scm.companyid AND p.subcompanyid = scm.subcompanyid AND (scm.tedit IS NULL OR scm.tedit = '') AND scm.subcompanyname IS NOT NULL WHERE p.companyid IS NOT NULL AND p.companyid <> '' AND p.subcompanyid IS NOT NULL AND p.subcompanyid <> '' AND cm.companyname IS NOT NULL AND scm.subcompanyname IS NOT NULL and p.companyid in('".str_replace(",", "','", $companyid)."')  and p.subcompanyid in('".str_replace(",", "','", $subcompanyid)."') order by p.companyid, p.subcompanyid, p.projectid");
	}else if($shortby=="withauthrizedprojects"){
		$ppq=$conn->query("SELECT p.companyid, p.subcompanyid, p.projectid, p.projectname, cm.companyname, scm.subcompanyname FROM tbl_project_master p LEFT JOIN tbl_company_master cm ON p.companyid = cm.companyid AND (cm.tedit IS NULL OR cm.tedit = '') AND (p.tedit IS NULL OR p.tedit='') AND cm.companyname IS NOT NULL LEFT JOIN tbl_company_submaster scm ON p.companyid = scm.companyid AND p.subcompanyid = scm.subcompanyid AND (scm.tedit IS NULL OR scm.tedit = '') AND scm.subcompanyname IS NOT NULL WHERE p.companyid IS NOT NULL AND p.companyid <> '' AND p.subcompanyid IS NOT NULL AND p.subcompanyid <> '' AND cm.companyname IS NOT NULL AND scm.subcompanyname IS NOT NULL and p.companyid in('".str_replace(",", "','", $companyid)."')  and p.subcompanyid in('".str_replace(",", "','", $subcompanyid)."')  and p.projectid in('".str_replace(",", "','", $shortbyvalue)."') order by p.companyid, p.subcompanyid, p.projectid");
	}
	$pplist=array();
	if($ppq->num_rows>0){
		while($prow=$ppq->fetch_assoc()){
			$pplist[]=array('companyid'=>$prow['companyid'], 'subcompanyid'=>$prow['subcompanyid'], 'projectid'=>$prow['projectid'], 'projectname'=>$prow['projectname'], 'companyname'=>$prow['companyname'], 'subcompanyname'=>$prow['subcompanyname']);
		}
	}
	return $pplist;
}

function hrm_projectdetails($conn,$companyid,$subcompanyid,$shortby,$shortbyvalue){
  $ssbq="";
  if($shortby=="all"){
    $ssbq="select companyid, subcompanyid, projectid,projectname, ifnull(finyr,'4') as finyr, ifnull(taxtype,'') as  taxtype , ifnull(invformat,'') as invformat, ifnull(proformaformat,'4') as  proformaformat ,ifnull(currencyid,'') as  currencyid, ifnull(creditnoteformat,'') as  creditformat,ifnull(project_email,'') as project_email FROM tbl_project_master WHERE companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and (tedit is null or tedit='')";
  }else if($shortby=="subcompanyid"){
    $ssbq="select companyid, subcompanyid, projectid,projectname , ifnull(finyr,'4') as finyr, ifnull(taxtype,'') as  taxtype , ifnull(invformat,'') as invformat, ifnull(proformaformat,'4') as  proformaformat ,ifnull(currencyid,'') as  currencyid , ifnull(creditnoteformat,'') as  creditformat,ifnull(project_email,'') as project_email FROM tbl_project_master WHERE companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and (tedit is null or tedit='')";
  }else if($shortby=="projectid"){
    $ssbq="select companyid, subcompanyid, projectid,projectname , ifnull(finyr,'4') as finyr, ifnull(taxtype,'') as  taxtype , ifnull(invformat,'') as invformat, ifnull(proformaformat,'') as  proformaformat ,ifnull(currencyid,'') as  currencyid , ifnull(creditnoteformat,'') as  creditformat,ifnull(project_email,'') as project_email FROM tbl_project_master WHERE companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid in ('".str_replace(',','","',$shortbyvalue)."') and (tedit is null or tedit='')";
  } 
  $sblist=array();
  if($ssbq){
    $ssbq_result=$conn->query($ssbq) or die($conn->error);
    if($ssbq_result->num_rows>0){
      while($sbrow=$ssbq_result->fetch_assoc()){
        $sblist[]=array('companyid'=>$sbrow['companyid'],'subcompanyid'=>$sbrow['subcompanyid'],'projectid'=>$sbrow['projectid'],'projectname'=>$sbrow['projectname'] ,'finyr'=>$sbrow['finyr'] ,'taxtype'=>$sbrow['taxtype'],'invformat'=>$sbrow['invformat'] ,'proformaformat'=>$sbrow['proformaformat'],'currencyid'=>$sbrow['currencyid'] ,'creditformat'=>$sbrow['creditformat'],'emailid'=>$sbrow['project_email']);
      }
    }
  }
  return $sblist;
}

function getprojectdetails($conn,$companyid,$subcompanyid,$shortby,$shortbyvalue){
	$ssbq="";
	if($shortby=="all"){
		$ssbq="select companyid, subcompanyid, projectid, projectname, ifnull(finyr,'4') as finyr, ifnull(taxtype,'') as  taxtype , ifnull(invformat,'') as invformat, ifnull(proformaformat,'4') as  proformaformat ,ifnull(currencyid,'') as  currencyid, ifnull(creditnoteformat,'') as  creditformat,ifnull(project_email,'') as project_email FROM tbl_project_master WHERE companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and (tedit is null or tedit='')";
	}else if($shortby=="subcompanyid"){
		$ssbq="select companyid, subcompanyid, projectid,projectname , ifnull(finyr,'4') as finyr, ifnull(taxtype,'') as  taxtype , ifnull(invformat,'') as invformat, ifnull(proformaformat,'4') as  proformaformat ,ifnull(currencyid,'') as  currencyid , ifnull(creditnoteformat,'') as  creditformat,ifnull(project_email,'') as project_email FROM tbl_project_master WHERE companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and (tedit is null or tedit='')";
	}else if($shortby=="projectid"){
		$ssbq="select companyid, subcompanyid, projectid,projectname , ifnull(finyr,'4') as finyr, ifnull(taxtype,'') as  taxtype , ifnull(invformat,'') as invformat, ifnull(proformaformat,'') as  proformaformat ,ifnull(currencyid,'') as  currencyid , ifnull(creditnoteformat,'') as  creditformat,ifnull(project_email,'') as project_email FROM tbl_project_master WHERE companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid in ('".str_replace(',','","',$shortbyvalue)."') and (tedit is null or tedit='')";
	} 
	$sblist=array();
	if($ssbq){
		$ssbq_result=$conn->query($ssbq) or die($conn->error);
		if($ssbq_result->num_rows>0){
			while($sbrow=$ssbq_result->fetch_assoc()){
				$sblist[]=array('companyid'=>$sbrow['companyid'],'subcompanyid'=>$sbrow['subcompanyid'],'projectid'=>$sbrow['projectid'],'projectname'=>$sbrow['projectname'] ,'finyr'=>$sbrow['finyr'] ,'taxtype'=>$sbrow['taxtype'],'invformat'=>$sbrow['invformat'] ,'proformaformat'=>$sbrow['proformaformat'],'currencyid'=>$sbrow['currencyid'] ,'creditformat'=>$sbrow['creditformat'],'emailid'=>$sbrow['project_email']);
			}
		}
	}
	return $sblist;
}

function getpurposenamewithcat_hrm($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue)
{  

    if($shortby=="all"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'  and ifnull(tedit,'')='' and visiblestatus='yes' order by categoryid, sub_categoryid_client ,sub_categoryid, provided_code");
    }else if($shortby=="checkpurpose_providedbyproject"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'  and ifnull(tedit,'')='' and visiblestatus='yes' and source<>'auto' order by categoryid, sub_categoryid_client ,sub_categoryid, provided_code");
    }else if($shortby=="allwithoutvisiblestatus"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'  and ifnull(tedit,'')='' order by categoryid, sub_categoryid_client, sub_categoryid, provided_code");
    }else if($shortby=="allmarkbynote4"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and markbynote4 is not null and markbynote4<>''  and ifnull(tedit,'')='' and visiblestatus='yes' order by purpose");
    }else if($shortby == "allmarkbynote4_withsubcategory"){
        $ppexp = explode("|", $shortbyvalue);
        $categoryid = $ppexp[0];
        $subcategoryid = $ppexp[1];
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and markbynote4 is not null and markbynote4<>'' and categoryid in('".$categoryid."', '".str_pad($categoryid, 2, "0", STR_PAD_LEFT)."') and sub_categoryid='".$subcategoryid."'  and ifnull(tedit,'')='' and visiblestatus='yes'");

    }else if($shortby=="purposecode"){      
        
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and visiblestatus='yes' and purposecode='".$shortbyvalue."' order by purpose");
    }else if($shortby=="purposecode_all"){      
    
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'   and purposecode='".$shortbyvalue."' order by purpose");

    }else if($shortby=="purposeid"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and ifnull(tedit,'')='' and purposeid='".$shortbyvalue."' order by purpose");
    }else if($shortby == "purposename"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(purpose)='".strtolower($shortbyvalue)."'  and ifnull(tedit,'')='' order by purpose");
    }else if($shortby == "purposecategory" || $shortby == "purposecategoryid"){
         $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and categoryid='".$shortbyvalue."' and ifnull(tedit,'')='' and visiblestatus='yes' order by purpose");
    }else if($shortby == "purposesubcategory"){ 
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and sub_category like'%".$shortbyvalue."' and ifnull(tedit,'')='' and visiblestatus='yes' order by purpose");
    }else if($shortby == "subcategoryid"){  
    
          $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and sub_categoryid='".$shortbyvalue."' and ifnull(tedit,'')='' and visiblestatus='yes' order by purpose");
    }else if($shortby == "purposecategoryname"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(category)='".strtolower($shortbyvalue)."' and ifnull(tedit,'')='' and visiblestatus='yes'");
    }else if($shortby == "purposecategorynamelikely"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(category) like '%".strtolower($shortbyvalue)."%' and ifnull(tedit,'')='' and visiblestatus='yes'");
    }else if($shortby == "purposesubcategoryname"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(sub_category)='".strtolower($shortbyvalue)."' and ifnull(tedit,'')='' and visiblestatus='yes'");
    }else if($shortby == "purposesubcategorynamelikely"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(sub_category) like '%".strtolower($shortbyvalue)."%' and ifnull(tedit,'')='' and visiblestatus='yes'");
    }else if($shortby == "clientcategorynamelikely"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(sub_category_client) like '%".strtolower($shortbyvalue)."%' and ifnull(tedit,'')='' and visiblestatus='yes'");
    }else if($shortby == "subcatlistwithlikelysubcatnamewithcatid"){
        $ppexp = explode("|", $shortbyvalue);
        $categoryid = $ppexp[0];
        $liklysubcatname = $ppexp[1];
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and categoryid='".$categoryid."' and lcase(sub_category) like '%".strtolower($liklysubcatname)."%' and ifnull(tedit,'')='' and visiblestatus='yes' group by sub_categoryid");
    }else if($shortby == "clientsubcategoryliswithlikelysubcategory"){
        $ppexp = explode("|", $shortbyvalue);
        $categoryid = $ppexp[0];
        $liklysubcatname = $ppexp[1];
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and categoryid='".$categoryid."' and lcase(sub_category_client) like '%".strtolower($liklysubcatname)."%' and ifnull(tedit,'')='' and visiblestatus='yes' group by sub_categoryid");
    }else if($shortby == "purposesubcategoryname_notlikely"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(sub_category) not like '%".strtolower($shortbyvalue)."%' and ifnull(tedit,'')='' and visiblestatus='yes'");
    }else if($shortby == "clientsubcategoryname_notlikely"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(sub_category_client) not like '%".strtolower($shortbyvalue)."%' and ifnull(tedit,'')='' and visiblestatus='yes'");
    }else if($shortby == "provided_purposecode"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and provided_code='".$shortbyvalue."' and ifnull(tedit,'')='' and visiblestatus='yes'");
    }else if($shortby == "clientprovided_purposecode"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and provided_code_client='".$shortbyvalue."' and ifnull(tedit,'')='' and visiblestatus='yes'");
    }else if($shortby == "provided_purposename"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(purpose)='".strtolower($shortbyvalue??"")."' and ifnull(tedit,'')=''");
    }else if($shortby == "provided_purposenamelikly"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(purpose) like '%".strtolower($shortbyvalue??"")."%' and ifnull(tedit,'')=''");
    }else if($shortby == "allcaterylist"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and ifnull(tedit,'')='' and visiblestatus='yes' group by categoryid");
    }else if($shortby == "allsubcaterylist_withcategoryid"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and categoryid in('".$shortbyvalue."', '".str_pad($shortbyvalue, 2, "0", STR_PAD_LEFT)."') and ifnull(tedit,'')='' and visiblestatus='yes' group by sub_categoryid");
    }else if($shortby == "allsubcaterylist_withoutcategoryid"){
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and ifnull(tedit,'')='' and visiblestatus='yes' group by sub_categoryid");
    }else if($shortby == "withcategory_subcategory"){
        $ppexp = explode("|", $shortbyvalue);
        $categoryid = $ppexp[0];
        $subcategoryid = $ppexp[1];
        $sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and categoryid in('".$categoryid."', '".str_pad($categoryid, 2, "0", STR_PAD_LEFT)."') and sub_categoryid='".$subcategoryid."' and ifnull(tedit,'')='' and visiblestatus='yes'");
    } 
    $sltplist = array();
    if($sltp->num_rows>0){
        while($imrow = $sltp->fetch_assoc()){
            $comptpurpose = $imrow['purpose'].'('.$imrow['purposecode'].')';
            
            if($imrow['provided_code_client']){
                $purposewih_prov = $imrow['provided_code_client'].' - '.$imrow['purpose'];
            }else{
                $purposewih_prov = $imrow['provided_code'].' - '.$imrow['purpose'];
            }

            $sltplist[] = array('pcategory'=>$imrow['category'], 'pcategorycode'=>$imrow['categoryid'], 'psub_category'=>$imrow['sub_category'], 'psub_categoryid'=>$imrow['sub_categoryid'],'purposeid'=>$imrow['purposeid'],'purpose'=>$imrow['purpose'], 'purposecode'=>$imrow['purposecode'], 'purposewithprovidedcode'=>$purposewih_prov, 'purposewithcode'=>$comptpurpose,'isforstock'=>$imrow['isforstock'],'budget_permonth'=>$imrow['budget_per_month'],'total_peryear'=>$imrow['total_per_year'], 'provided_code'=>$imrow['provided_code'], 'default_beneficiary'=>$imrow['default_beneficiary'], 'visiblestatus'=>$imrow['visiblestatus'], 'sub_categoryid_client'=>$imrow['sub_categoryid_client'], 'sub_category_client'=>$imrow['sub_category_client'], 'provided_code_client'=>$imrow['provided_code_client']);
        }
    }
    return $sltplist;

}

function getpaytoinfo_hrm($conn,$companyid,$subcompanyid,$shortby,$shortbyvalue){
	if($shortby=='all'){
		$allqry = "select sno, paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, address, city, state, postalcode, country, taxid, taxtype, taxrate FROM tbl_payto_master where paytoname is not null and paytoname<>''  and cmp_id='".$companyid."' and subcmp_id = '".$subcompanyid."' and (tedit is null or tedit='') order by paytoname";
	}else if($shortby=='paytoname'){
		 $allqry = "select sno, paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, address, city, state, postalcode, country, taxid, taxtype, taxrate FROM tbl_payto_master where  (tedit is null or tedit='') and cmp_id='".$companyid."' and subcmp_id = '".$subcompanyid."' and lcase(paytoname)='".strtolower($shortbyvalue)."' and paytoname is not null and paytoname<>'' order by paytoname";
	}else if($shortby=='projectid'){
		  $allqry = "select sno,paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, address, city, state, postalcode, country, taxid, taxtype, taxrate FROM tbl_payto_master where cmp_id='".$companyid."' and subcmp_id = '".$subcompanyid."' and projectid='".$shortbyvalue."' and (tedit is null or tedit='') and paytoname is not null and paytoname<>'' order by paytoname";
	}else if($shortby=='likelypayto'){
		 $allqry = "select sno,paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, address, city, state, postalcode, country, taxid, taxtype, taxrate FROM tbl_payto_master where cmp_id='".$companyid."' and subcmp_id = '".$subcompanyid."' and match(paytoname) against ('".$shortbyvalue."') and (tedit is null or tedit='') and paytoname is not null and paytoname<>'' order by paytoname";
	}else if($shortby=='paytoid'){
		$allqry = "select sno,paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, address, city, state, postalcode, country, taxid, taxtype, taxrate FROM tbl_payto_master where cmp_id='".$companyid."' and subcmp_id = '".$subcompanyid."' and (tedit is null or tedit='') and paytoid = '".$shortbyvalue."' and (paytoname is not null and paytoname<>'')";
	}else if($shortby=='distinctpayto'){
		 $allqry = "select  distinct paytoname, sno, paytoid, accountno, bankname, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, address, city, state, postalcode, country, taxid, taxtype, taxrate FROM tbl_payto_master where cmp_id='".$companyid."' and subcmp_id = '".$subcompanyid."' and projectid='".$shortbyvalue."' and (tedit is null or tedit='') and (paytoname is not null and paytoname<>'') order by paytoname";
	}

	$paytoqry = $conn->query($allqry);
	$paytoarray = array();
	if($paytoqry->num_rows>0){
		while($pyrow = $paytoqry->fetch_assoc()){
			$paytoarray[] = array('psno' =>$pyrow['sno'],'paytoid'=>$pyrow['paytoid'], 'payto' =>strtoupper($pyrow['paytoname']), 'paytoname' =>strtoupper($pyrow['paytoname']), 'tobankname' =>$pyrow['bankname'],'toaccountno' =>$pyrow['accountno'], 'tobankifsc' =>$pyrow['ifsccode'], 'iban'=>$pyrow['iban'], 'swift'=>$pyrow['swift'], 'sortcode'=>$pyrow['sortcode'], 'paymentterms'=>$pyrow['paymentterms'], 'mobileno'=>$pyrow['mobileno'], 'emailaddress'=>$pyrow['emailaddress'], 'address'=>$pyrow['address'], 'city'=>$pyrow['city'], 'state'=>$pyrow['state'], 'postalcode'=>$pyrow['postalcode'], 'country'=>$pyrow['country'], 'taxid'=>$pyrow['taxid'], 'taxtype'=>$pyrow['taxtype'], 'taxrate'=>$pyrow['taxrate'],'status'=>'Success');
		}
	}
	 return $paytoarray;
}

function getnewreferenceno_hrm($conn, $companyid, $subcompanyid, $projectid, $fromaccountno) {
    $newreferenceno = '';
    $retryCount = 0;
    $maxRetries = 3;
    while ($retryCount < $maxRetries) {
        try {
            $uuid = bin2hex(random_bytes(6)); // 12 hex chars
            $suffix = strtoupper(substr($uuid, 0, 8));

            if (strlen($fromaccountno) > 5) {
                $newreferenceno = 'REF-' . $companyid . '-' . $subcompanyid . '-' . $projectid . '-' . substr($fromaccountno, -4) . '-' . $suffix;
            } else {
                $newreferenceno = 'REF-' . $companyid . '-' . $subcompanyid . '-' . $projectid . '-' . $suffix;
            }

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_maintransaction WHERE reference_no = ?");
            if (!$stmt) {
                throw new Exception("SQL Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("s", $newreferenceno);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($result['total'] == 0) {
                return $newreferenceno; // Unique and ready
            }

        } catch (Exception $e) {
            error_log("Error generating reference: " . $e->getMessage());
            return null; // or return a fallback value
        }

        $retryCount++;
    }

    return null; // Failed to generate after maxRetries
}

function savetodbnewpayto_hrm($conn,$newname,$newaccountno,$newbankname,$newifsccode,$newiban,$newswift,$newsortcode,$companyid,$subcompanyid,$projectid,$newmobileno,$newemailaddress,$loginname,$maxempid=null){
	if($maxempid){
		$maxid=$maxempid;
	}else{
		$maxid = generateUniquePaytoId_hrm($conn, $companyid, $subcompanyid, $newname);
	}
	$paytoq = $conn->query("select paytoname,paytoid FROM tbl_payto_master where lcase(paytoname)='".strtolower(addslashes(trim($newname)))."' and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')") or die($conn->error) ;
	$message=array();
	if($paytoq->num_rows>0){
		$exrow=$paytoq->fetch_assoc();
		if($newbankname && $newaccountno){
			if($newemailaddress){
				$upemailaddress=", emailaddress='".addslashes($newemailaddress)."'";
			}
			if($newmobileno){
				$upmobileno=", mobileno='".addslashes($newmobileno)."'";
			}
			$conn->query("UPDATE tbl_payto_master SET bankname='".addslashes(trim($newbankname))."', accountno='".addslashes(trim($newaccountno))."', ifsccode='".addslashes(trim($newifsccode))."', iban='".addslashes($newiban)."', swift='".addslashes($newswift)."', sortcode='".addslashes($newsortcode)."', mobileno='".addslashes($newmobileno)."',  uploadflag=null ".$upemailaddress." ".$upmobileno." WHERE lcase(paytoname)='".strtolower(addslashes($newname))."'  and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'");
			if(mysqli_affected_rows($conn)>0){
				$bnfq = $conn->query("select name FROM tbl_beneficiaries_master where lcase(name)='".strtolower(trim($newname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'") or die($conn->error);
				if($bnfq->num_rows>0){
					$conn->query("UPDATE tbl_beneficiaries_master SET name='".$newname."' where lcase(name)='".strtolower(trim($oldpaytoname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
					if(mysqli_affected_rows($conn)>0){
						$message[] = array("status"=> 'Success', 'message'=>"Service Provider details updated successfully", "paytoid"=>$exrow['paytoid']);
					}
				}else{
					$conn->query("INSERT INTO tbl_beneficiaries_master (countryid,companyid,subcompanyid,projectid,name,addby) VALUES('".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".strtoupper(addslashes(trim($newname)))."', '".$loginname."')") or die($conn->error);
					if(mysqli_affected_rows($conn)>0){
						$message[] = array("status"=> 'Success', 'message'=>"Service Provider details saved successfully", "paytoid"=>$exrow['paytoid']);
					}
				}
			}else{
				$message[] = array("status"=> 'alreadyupdated', 'message'=>"Service Provider details did not update, due to somthing wrong", "paytoid"=>$exrow['paytoid']);
			}
		}else{
			$message[] = array("status"=> 'Success', 'message'=>"Already updated", "paytoid"=>$exrow['paytoid']);
		}
	}else{
		$conn->query("INSERT INTO tbl_payto_master (paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, cmp_id, subcmp_id, projectid, paytoaddby) VALUES('".$maxid."', '".strtoupper(addslashes(trim($newname)))."', '".addslashes(trim($newaccountno))."', '".strtoupper(addslashes(trim($newbankname)))."', '".strtoupper(addslashes(trim($newifsccode)))."', '".strtoupper(addslashes(trim($newiban)))."', '".strtoupper($newswift)."', '".strtoupper($newsortcode)."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$loginname."' ) ") or die($conn->error);
		if(mysqli_affected_rows($conn)>0){
			$bnfq = $conn->query("select name FROM tbl_beneficiaries_master where lcase(name)='".strtolower(trim($newname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'") or die($conn->error);
				if($bnfq->num_rows>0){
					$conn->query("UPDATE tbl_beneficiaries_master SET name='".$newname."' where lcase(name)='".strtolower(trim($oldpaytoname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
					if(mysqli_affected_rows($conn)>0){
						$message[] = array("status"=> 'Success', 'message'=>"Service Provider details updated successfully", "paytoid"=>$exrow['paytoid']);
					}
				}else{
					$conn->query("INSERT INTO tbl_beneficiaries_master (countryid,companyid,subcompanyid,projectid,name,addby) VALUES('".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".strtoupper(addslashes(trim($newname)))."', '".$loginname."')") or die($conn->error);
					if(mysqli_affected_rows($conn)>0){
						$message[] = array("status"=> 'Success', 'message'=>"Service Provider details added successfully", "paytoid"=>$maxid);
					}
				}
		}else{
			$message[] = array("status"=> 'Error', 'message'=>"Service Provider details did not update in master table, due to somthing wrong", "paytoid"=>"");
		}
	}
	return $message;
}

// function reconstructBookFromSalary($mconn, $companyid, $subcompanyid, $projectid, $subprojectid, $bookamount, $paytoid, $employeeid, $transaction_type, $breakdowndata, $loginname = null) {
//     $response = ['status' => false, 'message' => '', 'referenceno' => ''];
//     $bookdate = date('Y-m-d');
//     $book_source = 'SalaryModule';
//     $paymenttype = 'Payment';
//     if(count($breakdowndata)==1){
// 	    $beneficierie = "Individual";
//     }else if(count($breakdowndata)>1){
// 	    $beneficierie = "Multiple";
//     } 
//     $request_no = '';

//     // --- Get payee details ---
//     $paytoname = '';
//     $paytobankname = '';
//     $paytoaccountno = '';
//     $paytoaccountifsc = '';
//     $paytoaccountid = '';
//     if($paytoid){
// 	    $ptinfo = getpaytoinfo_hrm($mconn, $companyid, $subcompanyid, "paytoid", $paytoid);
// 	    if (count($ptinfo) > 0) {
// 	        $paytoname = $ptinfo[0]['payto'];
// 	        $paytobankname = $ptinfo[0]['tobankname'];
// 	        $paytoaccountno = $ptinfo[0]['toaccountno'];
// 	        $paytoaccountifsc = $ptinfo[0]['tobankifsc'];
// 	        $paytoaccountid = $ptinfo[0]['toaccountid'];
// 	    }else{

// 	    }
//     }

//     // --- Financial year & transaction refs ---
//     $projectDetails = getprojectdetails($mconn, $companyid, $subcompanyid, "projectid", $projectid);
//     $finyeartype = $projectDetails[0]['finyr'];
//     $financialYear = getFinancialYearHrm($bookdate, $finyeartype);
//     $tsno = GetMaxRefTempSnoHRM($mconn);
//     $newreference_no = getnewreferenceno_hrm($mconn, $companyid, $subcompanyid, $projectid, $fromaccountno);

//     $response['referenceno'] = $newreference_no;

//     // --- Build breakdown multi-query ---
//     $breakdown_sql = "";
//     $total_breakdown_amount = 0;

//     if (count($breakdowndata) > 0) {
//         foreach ($breakdowndata as $brkdkey) {
//             $beneficiaryname = $brkdkey['beneficiaryname'] ?? '';
//             $beneficiaryamount = floatval($brkdkey['beneficiaryamount'] ?? 0);
//             $purposecode = $brkdkey['purposecode'] ?? '';
//             $textremark = $brkdkey['textremark'] ?? '';

//             $purposecategory = '';
//             $purposecategoryid = '';
//             $purposesubcategory = '';
//             $purposesubcategoryid = '';
//             $purposename = '';

//             $purposedetails = getpurposenamewithcat_hrm($mconn, $companyid, $subcompanyid, $projectid, "purposecode", $purposecode);
//             if (count($purposedetails) > 0) {
//                 $purposecategory = $purposedetails[0]['pcategory'];
//                 $purposecategoryid = $purposedetails[0]['pcategorycode'];
//                 $purposesubcategory = $purposedetails[0]['psub_category'];
//                 $purposesubcategoryid = $purposedetails[0]['psub_categoryid'];
//                 $purposename = $purposedetails[0]['purpose'];
//                 $purposecode = $purposedetails[0]['purposecode'];
//                 $paymenttype = $purposedetails[0]['pcategory'];
//             }

//             $total_breakdown_amount += $beneficiaryamount;

//             $breakdown_sql .= "
//                 INSERT INTO tbl_maintransaction_breakdown (
//                     companyid, subcompanyid, projectid, subprojectid, reference_no, beneficiary_name, amount,
//                     purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory,
//                     purpose, purpose_code, description, empid
//                 ) VALUES (
//                     '$companyid', '$subcompanyid', '$projectid', '$subprojectid', '$newreference_no', '$beneficiaryname', '$beneficiaryamount',
//                     '$purposecategoryid', '$purposecategory', '$purposesubcategoryid', '$purposesubcategory',
//                     '$purposename', '$purposecode', '$textremark', '$employeeid'
//                 );
//             ";
//         }
//     }

//     // --- Validate total amount ---
//     if (round($total_breakdown_amount, 2) != round($bookamount, 2)) {
//         $response['message'] = "Breakdown total ($total_breakdown_amount) does not match Book Amount ($bookamount)";
//         return $response;
//     }

//     // --- Begin transaction ---
//     mysqli_begin_transaction($mconn);

//     // --- Execute breakdown multi-query ---
//     if (!empty($breakdown_sql)) {
//         if (!mysqli_multi_query($mconn, $breakdown_sql)) {
//             mysqli_rollback($mconn);
//             $response['message'] = "Breakdown insert failed: " . mysqli_error($mconn);
//             return $response;
//         }
//         while (mysqli_more_results($mconn)) {
//             mysqli_next_result($mconn);
//         }
//     }

//     // --- Insert into main transaction ---
//     $insmain = "INSERT INTO tbl_maintransaction (
//         tsno, companyid, subcompanyid, projectid, subprojectid, finyear, request_no, reference_no, bookdate, book_amount, beforeedit_amount,
//         transaction_type, payto, paytoid, paytoaccountno, paytobankname,
//         paytoifsc, paytoaccountid, payfrombankname, payfromaccountno, payfromaccountid,
//         payfrombankid, paymenttype, beneficierie, book_source, book_status, empid
//     ) VALUES (
//         '$tsno', '$companyid', '$subcompanyid', '$projectid', '$subprojectid', '$financialYear', '$request_no', '$newreference_no', '$bookdate',
//         '$bookamount', '$bookamount', '$transaction_type', '$paytoname', '$paytoid', '$paytoaccountno', '$paytobankname',
//         '$paytoaccountifsc', '$paytoaccountid', '$frombankname', '$fromaccountno', '$fromaccountid',
//         '', '$paymenttype', '$beneficierie', '$book_source', 'completed', '$employeeid'
//     )";

//     if (!mysqli_query($mconn, $insmain)) {
//         mysqli_rollback($mconn);
//         $response['message'] = "Main transaction insert failed: " . mysqli_error($mconn);
//         return $response;
//     }

//     // --- All good, commit ---
//     mysqli_commit($mconn);
//     $response['status'] = true;
//     $response['message'] = "Transaction successfully recorded.";

//     return $response;
// }

function reconstructBookFromSalary($mconn, $companyid, $subcompanyid, $projectid, $subprojectid, $bookamount, $paytoid, $employeeid, $transaction_type, $breakdowndata, $loginname = null, $fromaccountno = '', $frombankname = '', $fromaccountid = '')
{
    $response = ['status' => 'error', 'message' => 'Wrong', 'referenceno' => '', 'paytoid' => $paytoid];
    $bookdate = date('Y-m-d');
    $book_source = 'SalaryModule';
    $paymenttype = 'Payment';

    // --- Beneficiary type ---
    if (count($breakdowndata) == 1) {
        $beneficierie = "Individual";
    } elseif (count($breakdowndata) > 1) {
        $beneficierie = "Multiple";
    } else {
        $beneficierie = "";
    }

    $request_no = '';

    // --- Get payee details ---
    $paytoname = '';
    $paytobankname = '';
    $paytoaccountno = '';
    $paytoaccountifsc = '';
    $paytoaccountid = '';
    if ($paytoid) {
        $ptinfo = getpaytoinfo_hrm($mconn, $companyid, $subcompanyid, "paytoid", $paytoid);
        if (count($ptinfo) > 0) {
            $paytoname       = $ptinfo[0]['payto'];
            $paytobankname   = $ptinfo[0]['tobankname'];
            $paytoaccountno  = $ptinfo[0]['toaccountno'];
            $paytoaccountifsc= $ptinfo[0]['tobankifsc'];
            $paytoaccountid  = $ptinfo[0]['toaccountid'];
        }
    }

    // --- Financial year & transaction refs ---
    $projectDetails   = getprojectdetails($mconn, $companyid, $subcompanyid, "projectid", $projectid);
    $finyeartype      = $projectDetails[0]['finyr'] ?? '';
    $financialYear    = getFinancialYearHrm($bookdate, $finyeartype);
    $tsno             = GetMaxRefTempSnoHRM($mconn);
    $newreference_no  = getnewreferenceno_hrm($mconn, $companyid, $subcompanyid, $projectid, $fromaccountno);

    $response['referenceno'] = $newreference_no;

    // --- Build breakdown multi-query ---
    $breakdown_sql = "";
    $total_breakdown_amount = 0;

    if (count($breakdowndata) > 0) {
        foreach ($breakdowndata as $brkdkey) {
            $beneficiaryname   = $brkdkey['beneficiaryname'] ?? '';
            $beneficiaryamount = floatval($brkdkey['beneficiaryamount'] ?? 0);
            $purposecode       = $brkdkey['purposecode'] ?? '';
            $textremark        = $brkdkey['textremark'] ?? '';

            $purposecategory   = '';
            $purposecategoryid = '';
            $purposesubcategory= '';
            $purposesubcategoryid = '';
            $purposename       = '';

            $purposedetails = getpurposenamewithcat_hrm($mconn, $companyid, $subcompanyid, $projectid, "purposecode", $purposecode);
            if (count($purposedetails) > 0) {
                $purposecategory     = $purposedetails[0]['pcategory'];
                $purposecategoryid   = $purposedetails[0]['pcategorycode'];
                $purposesubcategory  = $purposedetails[0]['psub_category'];
                $purposesubcategoryid= $purposedetails[0]['psub_categoryid'];
                $purposename         = $purposedetails[0]['purpose'];
                $purposecode         = $purposedetails[0]['purposecode'];
                $paymenttype         = $purposedetails[0]['pcategory'];
            }

            $total_breakdown_amount += $beneficiaryamount;

            $breakdown_sql .= "
                INSERT INTO tbl_maintransaction_breakdown (
                    companyid, subcompanyid, projectid, subprojectid, reference_no, beneficiary_name, amount,
                    purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory,
                    purpose, purpose_code, description, empid
                ) VALUES (
                    '$companyid', '$subcompanyid', '$projectid', '$subprojectid', '$newreference_no', '$beneficiaryname', '$beneficiaryamount',
                    '$purposecategoryid', '$purposecategory', '$purposesubcategoryid', '$purposesubcategory',
                    '$purposename', '$purposecode', '$textremark', '$employeeid'
                );
            ";
        }
    }

    // --- Validate total amount ---
    if (round($total_breakdown_amount, 2) != round($bookamount, 2)) {
    	$response['status'] = "error";
        $response['message'] = "Breakdown total ($total_breakdown_amount) does not match Book Amount ($bookamount)";
        return $response;
    }

    // --- Begin transaction ---
    mysqli_begin_transaction($mconn);

    // --- Execute breakdown multi-query ---
    if (!empty($breakdown_sql)) {
        if (!mysqli_multi_query($mconn, $breakdown_sql)) {
            mysqli_rollback($mconn);
            $response['status'] = "error";
            $response['message'] = "Breakdown insert failed: " . mysqli_error($mconn);
            return $response;
        }
        while (mysqli_more_results($mconn) && mysqli_next_result($mconn)) {;}
    }

    // --- Insert into main transaction ---
    $insmain = "INSERT INTO tbl_maintransaction (
        tsno, companyid, subcompanyid, projectid, subprojectid, finyear, request_no, reference_no, bookdate, book_amount, beforeedit_amount,
        transaction_type, payto, paytoid, paytoaccountno, paytobankname,
        paytoifsc, paytoaccountid, payfrombankname, payfromaccountno, payfromaccountid,
        payfrombankid, paymenttype, beneficierie, book_source, book_status, empid
    ) VALUES (
        '$tsno', '$companyid', '$subcompanyid', '$projectid', '$subprojectid', '$financialYear', '$request_no', '$newreference_no', '$bookdate',
        '$bookamount', '$bookamount', '$transaction_type', '$paytoname', '$paytoid', '$paytoaccountno', '$paytobankname',
        '$paytoaccountifsc', '$paytoaccountid', '$frombankname', '$fromaccountno', '$fromaccountid',
        '', '$paymenttype', '$beneficierie', '$book_source', 'completed', '$employeeid'
    )";

    if (!mysqli_query($mconn, $insmain)) {
        mysqli_rollback($mconn);
        $response['status'] = "error";
        $response['message'] = "Main transaction insert failed: " . mysqli_error($mconn);
        return $response;
    }

    // --- All good, commit ---
    mysqli_commit($mconn);
	$response['status'] = "success";
	$response['message'] = "Transaction successfully recorded.";

    return $response;
}

function getFinancialYearHrm($date, $startMonth = 4) {
    $timestamp = strtotime($date);
    $year = (int)date('Y', $timestamp);
    $month = (int)date('n', $timestamp);
    if($startMonth==1){
        $startYear = $year;
        $endYear = $year;
    }else 
    {
        if ($month >= $startMonth) {
            $startYear = $year;
            $endYear = $year + 1;
        } else {
            $startYear = $year - 1;
            $endYear = $year;
        }
    }

    return $startYear . '-' . $endYear;
}

function GetMaxRefTempSnoHRM($conn){
	$mtso=$conn->query("SELECT (ifnull(max(tsno),0) + 1) as maxtsno FROM tbl_maintransaction");
	$tsno=1;
	if($mtso->num_rows>0){
		$trow=$mtso->fetch_assoc();
		$tsno=$trow['maxtsno'];
	}
	return $tsno;
}

function generateUniquePaytoId_hrm($conn, $companyid, $subcompanyid, $paytoname) {
    // Check if the paytoname already exists
    $stmt = $conn->prepare("SELECT paytoid FROM tbl_payto_master WHERE cmp_id = ? AND subcmp_id = ? AND paytoname = ?");
    $stmt->bind_param('iis', $companyid, $subcompanyid, $paytoname); // Bind parameters
    $stmt->execute();
    $stmt->bind_result($existingPaytoId);
    $stmt->fetch();
    $stmt->close();

    // If a matching paytoname is found, return the existing paytoid
    if (!empty($existingPaytoId)) {
        return $existingPaytoId;
    }

    // Generate a unique paytoid
    do {
        // Generate a random 5-digit number
        $uniqueId = mt_rand(10000, 99999);

        // Check if the uniqueId already exists in the database
        $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_payto_master WHERE paytoid = ?");
        $stmt->bind_param('i', $uniqueId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        // Check if the ID exists
        $exists = $count > 0;
    } while ($exists); // Repeat if the ID already exists

    // Return the new unique ID
    return $uniqueId;
}

function getAccountDetails($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue){
	$accinq="";
	if($shortby=="projectid"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE account_type='Bank' and (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."' order by account_type, accountname";
	}else if($shortby=="accountid"){
		  $accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid in('".str_replace(",", "','", $projectid)."') and accountid in('".str_replace(",", "','", $shortbyvalue)."')";
	}else if($shortby=="paytoid"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid in('".str_replace(",", "','", $projectid)."') and paytoid in('".str_replace(",", "','", $shortbyvalue)."')";
	}else if($shortby=="accountno"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid in('".str_replace(",", "','", $projectid)."') and accountno in('".str_replace(",", "','", $shortbyvalue)."')";
	}else if($shortby=="accountnowithloanaccount"){
        $accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid in('".str_replace(",", "','", $projectid)."') and accountno in('".str_replace(",", "','", $shortbyvalue)."') and lcase(account_type)='loan'";
    }else if($shortby=="fd_accountno"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid in('".str_replace(",", "','", $projectid)."') and accountno in('".str_replace(",", "','", $shortbyvalue)."') and lcase(account_type)='fd'";
	}else if($shortby=="onlyfd_accountlist"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid in('".str_replace(",", "','", $projectid)."')  and lcase(account_type)='fd'";
	}else if($shortby=="accountidW"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='')  and accountid in('".str_replace(",", "','", $shortbyvalue)."') and accountname not like '%_FD'";
	}else if($shortby=="subcompanyid"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and accountname not like '%_FD'";
	}else if($shortby=="subcompanyid_withoutproject"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid not in ('".str_replace(",", "','", $projectid)."') and accountname not like '%_FD'";
	}else if($shortby=="companyid"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and account_type not like '%FD'";
	}else if($shortby=="forlogin"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE account_type='Bank' and (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid in('".str_replace(",", "','", $shortbyvalue)."') and accountname not like '%_FD'";
	}else if($shortby=="bankname"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE account_type='Bank' and (tedit is null or tedit='') and cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."' and lcase(bankname) like '%".strtolower(trim($shortbyvalue))."%' and accountname not like '%_FD'";
	}else if($shortby=="cash"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE lcase(account_type)='cash' and (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."'";
	}else if($shortby=="cashaccountwithlikepayto"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE lcase(account_type)='cash' and (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."' and lcase(accountname) like '%".strtolower($shortbyvalue)."%'";
	}else if($shortby=="creditcard"){
		 $accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE lcase(account_type)='credit card' and (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."'";
	}else if($shortby=="supplieraccount"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE lcase(account_type)='supplier' and (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."'";
	}else if($shortby=="Imprest"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE lcase(account_type)='imprest' and (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."'";
	}else if($shortby=="Imprestwithaccountid"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE account_type='Imprest' and (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."' and accountid='".$shortbyvalue."'";
	}else if($shortby=="Imprestwithname"){
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE account_type='Imprest' and (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."' and lcase(accountname)='".strtolower($shortbyvalue)."'";
	}else if ($shortby=="allaccountofproject") {
		$accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE  (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."' ";
	}else if ($shortby=="allaccountofprojectonlybank") {
		 $accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE  (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."' and lcase(account_type)='bank'";
	}else if ($shortby=="allaccountofprojectonlybankandcreditcard") {
		  $accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE  (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."' and (lcase(account_type)='bank' or lcase(account_type)='creditcard')";
	}else if ($shortby=="allloanaccount") {
          $accinq="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE  (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id= '".$subcompanyid."' and projectid ='".$projectid."' and lcase(account_type)='loan'";
    }
	$accountlist=array();
	// echo $accinq; 
	$accinq_rs=$conn->query($accinq);	
	if($accinq_rs->num_rows>0){
		while($accrow=$accinq_rs->fetch_assoc()){
			$accountlist[]=array('sno'=>$accrow['sno'], 'countryid'=>$accrow['countryid'], 'cmp_id'=>$accrow['cmp_id'], 'subcmp_id'=>$accrow['subcmp_id'], 'projectid'=>$accrow['projectid'], 'accountid'=>$accrow['accountid'], 'bankid'=>$accrow['bankid'], 'account_type'=>$accrow['account_type'], 'uploadsheet_formatid'=>$accrow['uploadsheet_formatid'], 'accountname'=>$accrow['accountname'], 'paytoname'=>$accrow['accountname'], 'accountno'=>$accrow['accountno'], 'ifsccode'=>$accrow['ifsccode']?$accrow['ifsccode']:"N/A", 'bankname'=>$accrow['bankname'], 'accountholdername'=>$accrow['accountholdername'], 'branchname'=>$accrow['branchname']?$accrow['branchname']:"N/A", 'branchaddress'=>$accrow['branchaddress']?$accrow['branchaddress']:"N/A", "paytoid"=>$accrow['paytoid']);
		}
	}
	return $accountlist;
}
?>