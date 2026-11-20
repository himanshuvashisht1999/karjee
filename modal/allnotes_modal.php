<?php 
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');
$action=$_GET['action'];
$totalexpendature_for1=0;
$totalbankcharge_for1=0;
if($action=="notes4summary_htmlview"){
	$accountid=$_POST['accountid'];
	$projectid=$_POST['projectid'];
	$reporttype=$_POST['reporttype'];
	$datefrom=$_POST['newfromdata'];
	$dateto=$_POST['newtomdata'];
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$edit=$_POST['edit'];
	$prj=getprojectdetails($mconn,$companyid,$subcompanyid,"projectid",$projectid);
	foreach ($prj as $ppjkey) {
		$projectname = $ppjkey['projectname'];
	}
	$note4dd=note4_summary_getfunction($mconn,$accountid,$projectid,$datefrom,$dateto,$companyid,$subcompanyid);
	if ($edit=='edit') {
    	$editstyle="";
    }else{
    	$editstyle="text-align:left; display:none;";
    }	
	$nt4html="";
	if(count($note4dd)>0){
    $j=1;
    $total_amount = 0.0;
    foreach ($note4dd as $nt4key) {
    	if ($nt4key['purposecategory']=='Unknown') {
        $bgc='#F1948A';
      }else{
        $bgc='';
      }  
      if($nt4key['purposecode']!=""){
      	$nt4html.='<tr class="tr_note4details" data-note4finyear="'.$nt4key['finyear'].'" data-purposecode="'.$nt4key['purposecode'].'" data-datefrom="'.$datefrom.'" data-dateto="'.$dateto.'" data-categoryid="'.$nt4key['catid'].'" data-subcategoryid="'.$nt4key['subcatid'].'" data-accountid="'.$accountid.'" data-recon="'.$nt4key['reconentries'].'" style="cursor:pointer; background-color:'.$bgc.'">
        	<td style="width:60px; text-align:center;">'.$j++.'</td>
        	<td class="clickable td_note4cat" style="'.$editstyle.'">'.$nt4key['purposecategory'].'</td>
        	<td class="clickable td_note4subcat">'.$nt4key['subpurposecategory'].'</td>
        	<td class="clickable td_note4purpose">'.$nt4key['purpose'].'</td>
        	<td class="clickable td_codeX" style="text-align:left;">'.$nt4key['Xcode'].'</td>
        	<td class="clickable td_note4amount" style="text-align:left;">'.$nt4key['totalamount'].'</td>
        	<td class="clickable td_note4reconpc">'.(((int)$nt4key['reconentries']/(int)$nt4key['totalentries'])*100).'</td>
        	<td class="clickable td_note4entries">'.$nt4key['totalentries'].'</td>
      	</tr>';
      	$total_amount+=(float)$nt4key['totalamount'];
      }
    }
    if(!$reporttype){
    	$nt4html.='<tr class="tr_note4details bg-info font-weight-bold" style="cursor:pointer;">
				<td colspan="5">Total Expenditure</td>
				<td class="td_note4totalamount" style="text-align:left;">'.$total_amount.'</td>
				<td>&nbsp;</td><td class="sumtotalentries">&nbsp;</td>
				</tr>';
		}
	}else{
		$nt4html.='<h4 style="text-align:center;">Data not available</h4>';
	}
	echo $nt4html;
}else if($action=="notes4summary_htmlviewcredit"){
	$accountid=$_POST['accountid'];
	$projectid=$_POST['projectid'];
	$reporttype=$_POST['reporttype'];
	$datefrom=$_POST['newfromdata'];
	$dateto=$_POST['newtomdata'];
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$edit=$_POST['edit'];
	$prj=getprojectdetails($mconn,$companyid,$subcompanyid,"projectid",$projectid);
	foreach ($prj as $ppjkey) {
		$projectname = $ppjkey['projectname'];
	}
	$note4dd=note4_summary_getfunctioncredit($mconn,$accountid,$projectid,$datefrom,$dateto,$companyid,$subcompanyid);
	if ($edit=='edit') {
    	$editstyle="";
    }else{
    	$editstyle="text-align:left; display:none;";
    }	
	$nt4html="";
	if(count($note4dd)>0){
    $j=1;
    $total_amount = 0.0;
    foreach ($note4dd as $nt4key) {
    	if ($nt4key['purposecategory']=='Unknown') {
        $bgc='#F1948A';
      }else{
        $bgc='';
      }  
      if($nt4key['purposecode']!=""){
      	$nt4html.='<tr class="tr_note4details" data-note4finyear="'.$nt4key['finyear'].'" data-purposecode="'.$nt4key['purposecode'].'" data-datefrom="'.$datefrom.'" data-dateto="'.$dateto.'" data-categoryid="'.$nt4key['catid'].'" data-subcategoryid="'.$nt4key['subcatid'].'" data-accountid="'.$accountid.'" data-recon="'.$nt4key['reconentries'].'" style="cursor:pointer; background-color:'.$bgc.'">
        	<td style="width:60px; text-align:center;">'.$j++.'</td>
        	<td class="clickablecredit td_note4cat" style="'.$editstyle.'">'.$nt4key['purposecategory'].'</td>
        	<td class="clickablecredit td_note4subcat">'.$nt4key['subpurposecategory'].'</td>
        	<td class="clickablecredit td_note4purpose">'.$nt4key['purpose'].'</td>
        	<td class="clickablecredit td_codeX" style="text-align:left;">'.$nt4key['Xcode'].'</td>
        	<td class="clickablecredit td_note4amount" style="text-align:left;">'.$nt4key['totalamount'].'</td>
        	<td class="clickablecredit td_note4reconpc">'.round(((int)$nt4key['reconentries']/(int)$nt4key['totalentries'])*100,2).'</td>
        	<td class="clickablecredit td_note4entries">'.$nt4key['totalentries'].'</td>
      	</tr>';
      	$total_amount+=(float)$nt4key['totalamount'];
      }
    }
    if(!$reporttype){
    	$nt4html.='<tr class="tr_note4details bg-info font-weight-bold" style="cursor:pointer;">
				<td colspan="5">Total Expenditure</td>
				<td class="td_note4totalamount" style="text-align:left;">'.$total_amount.'</td>
				<td>&nbsp;</td><td class="sumtotalentries">&nbsp;</td>
				</tr>';
		}
	}else{
		$nt4html.='<h4 style="text-align:center;">Data not available</h4>';
	}
	echo $nt4html;
}else if ($action=="notes4summary_htmlviewnote4") {
	$accountid=$_POST['accountid'];
	$projectid=$_POST['projectid'];
	$reporttype=$_POST['reporttype'];
	$datefrom=$_POST['newfromdata'];
	$dateto=$_POST['newtomdata'];
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$edit=$_POST['edit'];
	$note4dd=note4_summary_getfunction_note4($mconn,$accountid,$projectid,$datefrom,$dateto,$companyid,$subcompanyid);
	if ($edit=='edit') {
    	$editstyle="";
    }else{
    	$editstyle="text-align:left; display:none;";
    }	
	$nt4html="";
	if(count($note4dd)>0){
	  $j=1;
	  $total_amount = 0.0;
	  foreach ($note4dd as $nt4key) {
	  	updatemarkedbynote4($conn,$companyid,$subcompanyid,$projectid,"markedaspurpose",$nt4key['purposecode']);
	  	if ($nt4key['purposecategory']=='Unknown') {
	      $bgc='#F1948A';
	    }else{
	      $bgc='';
	    }  
	    if($nt4key['purposecode']!=""){
	    	$nt4html.='<tr class="tr_note4details" style="cursor:pointer; background-color:'.$bgc.'">
	      	<td style="width:60px; text-align:center;">'.$j++.'</td>
	      	<td class="td_note4cat" style="'.$editstyle.'">'.$nt4key['purposecategory'].'</td>
	      	<td class="td_note4subcat">'.$nt4key['subpurposecategory'].'</td>
	      	<td class="td_note4purpose">'.$nt4key['purpose'].'</td>
	      	<td class="td_note4pcode" style="text-align:center; color:blue;">'.$nt4key['pcode'].'</td>
	      	<td class="td_codeX" style="text-align:left;">'.$nt4key['Xcode'].'</td>
	      	<td class="td_note4amount" style="text-align:left;">'.$nt4key['totalamount'].'</td>
	      	<td class="td_edit" style="'.$editstyle.'"><button class="btn btn-deepblue editinfo btn-sm mb-1">Edit Purpose</button></td>
	      	<td class="td_note4purposecode" style="display:none;">'.$nt4key['purposecode'].'</td>
	      	<td class="td_catid" style="display:none;">'.$nt4key['catid'].'</td>
	      	<td class="td_subcatid" style="display:none;">'.$nt4key['subcatid'].'</td>
	      	<td class="td_accid" style="display:none;">'.$accountid.'</td>
	      	<td class="td_d1id" style="display:none;">'.$datefrom.'</td>
	      	<td class="td_d2id" style="display:none;">'.$dateto.'</td>
	    	</tr>';
	    	$total_amount+=(float)$nt4key['totalamount'];
	    }
	  }
    if(!$reporttype){
    	$nt4html.='<tr class="tr_note4details bg-info" style="cursor:pointer;">
				<th colspan="6">Total Expenditure</th>
				<th class="td_note4amount" style="text-align:left;">'.$total_amount.'</th>
				<th colspan="7">&nbsp;</th>';
		}
	}else{
		$nt4html.='<h4 style="text-align:center;">Data not available</h4>';
	}
	echo $nt4html;
}else if ($action=="notes4creditsummary_htmlviewnote4") {
	$accountid=$_POST['accountid'];
	$projectid=$_POST['projectid'];
	$reporttype=$_POST['reporttype'];
	$datefrom=$_POST['newfromdata'];
	$dateto=$_POST['newtomdata'];
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$edit=$_POST['edit'];
	$prj=getprojectdetails($mconn,$companyid,$subcompanyid,"projectid",$projectid);
	foreach ($prj as $ppjkey) {
		$projectname = $ppjkey['projectname'];
	}
	$note4dd=note4credit_summary_getfunction_note4($mconn,$accountid,$projectid,$datefrom,$dateto,$companyid,$subcompanyid);
	if ($edit=='edit') {
    	$editstyle="";
    }else{
    	$editstyle="text-align:left; display:none;";
    }	
	$nt4html="";
	if(count($note4dd)>0){
	  $j=1;
	  $total_amount = 0.0;
	  foreach ($note4dd as $nt4key) {
	  	updatemarkedbynote4credit($conn,$companyid,$subcompanyid,$projectid,"markedaspurpose",$nt4key['purposecode']);
	  	if ($nt4key['purposecategory']=='Unknown') {
	      $bgc='#F1948A';
	    }else{
	      $bgc='';
	    }  
	    if($nt4key['purposecode']!=""){
	    	$nt4html.='<tr class="tr_note4details" style="cursor:pointer; background-color:'.$bgc.'">
	      	<td style="width:60px; text-align:center;">'.$j++.'</td>
	      	<td class="td_note4cat" style="'.$editstyle.'">'.$nt4key['purposecategory'].'</td>
	      	<td class="td_note4subcat">'.$nt4key['subpurposecategory'].'</td>
	      	<td class="td_note4purpose">'.$nt4key['purpose'].'</td>
	      	<td class="td_note4pcode" style="text-align:center; color:blue;">'.$nt4key['pcode'].'</td>
	      	<td class="td_codeX" style="text-align:left;">'.$nt4key['Xcode'].'</td>
	      	<td class="td_note4amount" style="text-align:left;">'.$nt4key['totalamount'].'</td>
	      	<td class="td_edit" style="'.$editstyle.'"><button class="btn btn-deepblue editinfo btn-sm mb-1">Edit Purpose</button></td>
	      	<td class="td_note4purposecode" style="display:none;">'.$nt4key['purposecode'].'</td>
	      	<td class="td_catid" style="display:none;">'.$nt4key['catid'].'</td>
	      	<td class="td_subcatid" style="display:none;">'.$nt4key['subcatid'].'</td>
	      	<td class="td_accid" style="display:none;">'.$accountid.'</td>
	      	<td class="td_d1id" style="display:none;">'.$datefrom.'</td>
	      	<td class="td_d2id" style="display:none;">'.$dateto.'</td>
	    	</tr>';
	    	$total_amount+=(float)$nt4key['totalamount'];
	    }
	  }
    if(!$reporttype){
    	$nt4html.='<tr class="tr_note4details bg-info" style="cursor:pointer;">
				<th colspan="6">Total Expenditure</th>
				<th class="td_note4amount" style="text-align:left;">'.$total_amount.'</th>
				<th colspan="7">&nbsp;</th>';
		}
	}else{
		$nt4html.='<h4 style="text-align:center;">Data not available</h4>';
	}
	echo $nt4html;
}else if($action=="notes4summarydetails_htmlview"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$accountid=$_POST['accountid'];
	$reporttype=$_POST['reporttype'];
	$purposecode=$_POST['purposecode'];
	$purpose=$_POST['purpose'];
	$datefrom=$_POST['newfromdata'];
	$dateto=$_POST['newtomdata'];
	$cmppurpose=$purpose."(".$purposecode.")";
	$prj=getprojectdetails($mconn,$companyid,$subcompanyid,"projectid",$projectid);
	foreach ($prj as $ppjkey) {
		$projectname = $ppjkey['projectname'];
	}
	$note4dd=note4summarywithpurpose_getfunction($mconn,$companyid,$subcompanyid,$projectid,$accountid,$datefrom,$dateto,$purposecode);
	$nt4html="empty";
	if(count($note4dd)>0){
		$nt4html.='<div class="modal" id="modelviewnote4detailspurpose">
		  <div class="modal-dialog" style="max-width: 98%;" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h4 class="modal-title">THE DETAILS OF THE CODE-'.$cmppurpose.'</h4>
		        <button type="button" class="close" data-dismiss="modal">&times;</button>
		      </div>
		      <div class="modal-body">
		      	<div class="row p-2">
		      		<button class="btn btn-waring m-2 pull-right" onclick="gridtoexcel_withfilter_chunk(\'note4detailswithpurposr_tbl\',\'Details of Purpose Code\',\'landscape\');"><i class="fa fa-file-excel" style="color:green;"></i> Export</button>
		          <div class="table-responsive">
		            <table class="table table-bordered text-black" id="note4detailswithpurposr_tbl">
		              <thead>
		                <tr>
		                  <th style="width:60px; text-align:center;">S.No</th>
		                  <th style="width:150px; text-align:center;">Date</th>
		                  <th style="width:150px; text-align:center;">Reference No.</th>
		                  <th style="width:250px; text-align:center;">Account Info</th>
		                  <th style="width:150px; text-align:center;">Payto</th>
		                  <th style="width:150px; text-align:center;">Beneficiary Name</th>
		                  <th style="width:100px; text-align:center;">Amount</th>
		                  <th style="width:250px;">Description</th>
		                  <th style="width:250px;">Bank Description</th>
		                  <th style="width:150px; text-align:center;">Documents</th>
		                  <th class="noexport" style="display:none;">Purpose</th>
		                  <th class="noexport" style="display:none;">Purpose Code</th> 
		                  <th class="noexport" style="display:none;">Transaction Type</th>
		                  <th class="noexport" style="display:none;" class="noexport">Document path</th>
		                </tr>
		              </thead>
		              <tbody>';
		              $j=1;
		              foreach ($note4dd as $nt4dkey) {
		              	if ($nt4dkey['description']=='') {
		              		$nt4dkey['description']=$nt4dkey['description_bank'];
		              	}
		              	$nt4html.='<tr style="cursor:pointer;" class="getreference">';
		              	$nt4html.='<td style="width:60px;text-align:center;">'.$j++.'</td>';
		              	$nt4html.='<td style="width:150px;text-align:center;">'.$nt4dkey['date'].'</td>';
		              	$nt4html.='<td style="width:150px;text-align:center;" class="td_referenceno">'.$nt4dkey['reference_no'].'</td>';
		              	$nt4html.='<td style="width:250px;">'.$nt4dkey['compaccinfo'].'</td>';
		              	$nt4html.='<td style="width:150px; text-align:center;">'.$nt4dkey['payto'].'</td>';
		              	$nt4html.='<td style="width:150px; text-align:center;">'.$nt4dkey['beneficiary_name'].'</td>';
		              	$nt4html.='<td style="width:100px; text-align:center;">'.$nt4dkey['totalamount'].'</td>';
		              	$nt4html.='<td style="width:250px;">'.$nt4dkey['description'].'</td>';
		              	$nt4html.='<td style="width:250px;">'.$nt4dkey['description_bank'].'</td>';
		              	$nt4html.='<td style="width:150px; text-align:center;" class="td_filestatus">'.$nt4dkey['documentstatus'].'</td>';
		              	$nt4html.='<td style="display:none;">'.$nt4dkey['purpose'].'</td>';
		              	$nt4html.='<td style="display:none;">'.$nt4dkey['pcode'].'</td>';
		              	$nt4html.='<td style="display:none;" class="td_beneficery">'.$nt4dkey['beneficierie'].'</td>';
		              	$nt4html.='<td style="display:none;" class="td_filepath">'.$nt4dkey['documentation_path'].'</td>';
		              	$nt4html.='</tr>';
		              }
		              $nt4html.='</tbody>
		            </table>
		          </div>
		        </div>
		      </div>
		    </div>
		  </div>
		</div>';
	}else{
		$nt4html.='<h4 style="text-align:center;">Data not available</h4>';
	}
	echo $nt4html;
}else if ($action=="notes4summarydetails_accountwise") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$projectname =$_POST['projectname'];
	$accountid=$_POST['accountid'];
	$reporttype=$_POST['reporttype'];
	$purposecode=$_POST['purposecode'];
	$providedcode=$_POST['providedcode'];
	$purpose=$_POST['purpose'];
	$cat=$_POST['cat'];
	$subcat=$_POST['subcat'];
	$datefrom=$_POST['newfromdata'];
	$dateto=$_POST['newtomdata'];	
	
	$nt4html="empty";
	if ($accountid=='all') {
		$uryacc="select distinct group_concat(accountid) as accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and account_type='Bank' and (tedit is null or tedit='')";
		$resutall=$conn->query($uryacc);
		if ($resutall->num_rows>0) {
			$arow=$resutall->fetch_assoc();
			$accountid=$arow['accountid'];
		}
	}
	$repno = 'N4(A)_'.$companyid.'_'.$subcompanyid.'_'.$projectid.'('.date_format(date_create($datefrom),'d-m-Y').'_TO_'.date_format(date_create($dateto),'d-m-Y').')';
	$nt4html= '<button class="btn btn-waring m-2 pull-right" onclick="gridtoexcel_withfilter_chunk(\'accountsummarydetailstbl\',\''.$repno.'\',\'landscape\',function(){});"><i class="fa fa-file-excel" style="color:green;"></i> Export</button>
		<div class="table-responsive">';

	$nt4html.='<table class="table table-bordered table-striped bg-light" id="accountsummarydetailstbl">
	<colgroup>
		<col style="width:50px;">
		<col style="width:auto;">
		<col style="width:auto;">
	</colgroup>
		<thead class="sticky-top">';
	$nt4html.='<tr class="bg-light" style="border:0;">
		<th class="noexport td_d1id2" style="display:none;">'.$datefrom.'</th>
		<th class="noexport td_d2id2" style="display:none;">'.$dateto.'</th> 
		<th class="noexport td_prp2" style="display:none;">'.$purposecode.'</th></tr>';
	$nt4html.='<tr class="bg-light border-0">
	<th colspan="3" class="border-0 px-1" style="font-size:22px;">'.$projectname.'</th> </tr>
	<tr class="bg-light border-0">
			<th colspan="3" class="border-0 p-1" style="font-size:14px;">Report: Note-4(A)- Summarized Accountwise Distribution of Purpose</th></tr>
		<tr class="bg-light border-0">
			<th colspan="3" class="border-0 p-1" style="font-size:14px;">'.$repno.'</th></tr>
		<tr class="bg-light border-0">
			<th colspan="3" class="border-0 p-1 category" style="font-size:14px;">Category: '.$cat.'</th></tr>
		<tr class="bg-light" style="border:0;">
			<th colspan="3" class="border-0 p-1 subcategory" style="border:0;font-size:14px;">Sub-Category: '.$subcat.'</th></tr>
		<tr class="bg-light purpose" style="border:0;" data-provided="'.$providedcode.'">
			<th colspan="3" class="border-0 p-1 purposename" style="border:0;font-size:14px;">Purpose: '.$purpose.' ['.$providedcode.']</th></tr>
		<tr class="bg-warning noexport">
			<th colspan="3"><div class="pull-right"><input type="checkbox" id="hidezeros"><label class="text-black">Hide Zero Amount Entries</label></div></th>
		</tr>
		<tr>
			<th class="bg-info">S.No</th><th class="bg-info">Account No</th><th class="bg-info">Amount</th>
		</tr>
		</thead>
		<tbody>';
		 	$totoamt=0;	 
		 	$qryac=$conn->query("select accountid,accountname,accountno,bankname from tbl_master_account where accountid in('".str_replace(",", "','", $accountid)."') ");
					if ($qryac->num_rows>0) {
						$i=1;	
						$accarray=[];			
						while ($hdkey=$qryac->fetch_assoc()) {
							$nt4html.="<tr class='accclass' data-accountid='".$hdkey['accountid']."' data-accdtls='".$hdkey['bankname']." (".$hdkey['accountno'].")' style='cursor:pointer;'>";
							$accarray[]=array("accountid"=>$hdkey['accountid'],"accountno"=>$hdkey['bankname']." (".$hdkey['accountno'].")");	
							$u4bs = $conn->query("select  sum(round(b.amount,2)) as total_amount,m.payfromaccountid as account_id from  tbl_maintransaction_breakdown b, tbl_maintransaction m where m.payfromaccountid ='".$hdkey['accountid']."' and (b.Tedit is null or b.Tedit='')  and (m.Tedit is null or m.Tedit='') and (m.reference_no=b.reference_no) and (ifnull(m.paymenttype,'')<>'Imprest Adjustment' ) and (date(m.bookdate) between '".$datefrom."' and '".$dateto."')   and (m.reverse_entry is null or m.reverse_entry='') and (m.projectid='".$projectid."') and b.purpose_code='".$purposecode."' group by m.payfromaccountid ");
								if ($u4bs->num_rows>0) {
						 		$bsrow=$u4bs->fetch_assoc();
						 			$nt4html.="<td>".$i."</td><td>".$hdkey['bankname']." (".$hdkey['accountno'].")</td><td>".$bsrow['total_amount']."</td></tr>";
						 			$totoamt=$totoamt+$bsrow['total_amount'];
						 		}else{
						 			$nt4html.="<td>".$i."</td><td>".$hdkey['bankname']." (".$hdkey['accountno'].")</td><td>0</td></tr>";
		 							$totoamt=$totoamt+0;
						 		}
						 		$i++;
						}
					}	
					$accdtls = json_encode($accarray)	;			
					$nt4html.="<tr class='bg-info font-weight-bold' data-allaccounts='".$accdtls."' ><td>&nbsp;</td><td>Total</td><td>".$totoamt."</td></tr>";
				$nt4html.="</tbody></table></div>";
	echo $nt4html;
}else if ($action=="notes4creditsummarydetails_accountwise") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$projectname =$_POST['projectname'];
	$accountid=$_POST['accountid'];
	$reporttype=$_POST['reporttype'];
	$purposecode=$_POST['purposecode'];
	$providedcode=$_POST['providedcode'];
	$purpose=$_POST['purpose'];
	$cat=$_POST['cat'];
	$subcat=$_POST['subcat'];
	$datefrom=$_POST['newfromdata'];
	$dateto=$_POST['newtomdata'];	
	$nt4html="empty";
	if ($accountid=='all') {
		$uryacc="select distinct group_concat(accountid) as accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and account_type='Bank' and (tedit is null or tedit='')";
		$resutall=$conn->query($uryacc);
		if ($resutall->num_rows>0) {
			$arow=$resutall->fetch_assoc();
			$accountid=$arow['accountid'];
		}
	}
	$repno = 'N4(A)_'.$companyid.'_'.$subcompanyid.'_'.$projectid.'('.date_format(date_create($datefrom),'d-m-Y').'_TO_'.date_format(date_create($dateto),'d-m-Y').')';
	$nt4html= '<button class="btn btn-waring m-2 pull-right" onclick="gridtoexcel_withfilter_chunk(\'accountsummarydetailstbl\',\''.$repno.'\',\'landscape\',function(){});"><i class="fa fa-file-excel" style="color:green;"></i> Export</button>
		<div class="table-responsive">';

	$nt4html.='<table class="table table-bordered table-striped bg-light" id="accountsummarydetailstbl">
	<colgroup>
		<col style="width:50px;">
		<col style="width:auto;">
		<col style="width:auto;">
	</colgroup>
		<thead class="sticky-top">';
	$nt4html.='<tr class="bg-light" style="border:0;">
		<th class="noexport td_d1id2" style="display:none;">'.$datefrom.'</th>
		<th class="noexport td_d2id2" style="display:none;">'.$dateto.'</th> 
		<th class="noexport td_prp2" style="display:none;">'.$purposecode.'</th></tr>';
	$nt4html.='<tr class="bg-light border-0">
	<th colspan="3" class="border-0 px-1" style="font-size:22px;">'.$projectname.'</th> </tr>
	<tr class="bg-light border-0">
			<th colspan="3" class="border-0 p-1" style="font-size:14px;">Report: Note-4(A)- Summarized Accountwise Distribution of Purpose</th></tr>
		<tr class="bg-light border-0">
			<th colspan="3" class="border-0 p-1" style="font-size:14px;">'.$repno.'</th></tr>
		<tr class="bg-light border-0">
			<th colspan="3" class="border-0 p-1 category" style="font-size:14px;" data-category="'.$cat.'">Category: '.$cat.'</th></tr>
		<tr class="bg-light" style="border:0;">
			<th colspan="3" class="border-0 p-1 subcategory" style="border:0;font-size:14px;" data-subcategory="'.$subcat.'">Sub-Category: '.$subcat.'</th></tr>
		<tr class="bg-light purpose" style="border:0;" data-provided="'.$providedcode.'">
			<th colspan="3" class="border-0 p-1 purposename" style="border:0;font-size:14px;" data-purpose="'.$purpose.'">Purpose: '.$purpose.' ['.$providedcode.']</th></tr>
		<tr class="bg-warning noexport">
			<th colspan="3"><div class="pull-right"><input type="checkbox" id="hidezeros"><label class="text-black">Hide Zero Amount Entries</label></div></th>
		</tr>
		<tr>
			<th class="bg-info">S.No</th><th class="bg-info">Account No</th><th class="bg-info">Amount</th>
		</tr>
		</thead>
		<tbody>';
		 	$totoamt=0;	 
		 	$qryac=$conn->query("select accountid,accountname,accountno,bankname from tbl_master_account where accountid in('".str_replace(",", "','", $accountid)."') ");
					if ($qryac->num_rows>0) {
						$i=1;	
						$accarray=[];			
						while ($hdkey=$qryac->fetch_assoc()) {
							$nt4html.="<tr class='creditaccclass' data-accountid='".$hdkey['accountid']."' data-accdtls='".$hdkey['bankname']." (".$hdkey['accountno'].")' style='cursor:pointer;'>";
							$accarray[]=array("accountid"=>$hdkey['accountid'],"accountno"=>$hdkey['bankname']." (".$hdkey['accountno'].")");	
							$u4bs = $conn->query("select  sum(round(b.amount,2)) as total_amount,m.payfromaccountid as account_id from  tbl_maintransaction_credit_breakdown b, tbl_maintransaction_credit m where m.payfromaccountid ='".$hdkey['accountid']."' and (b.Tedit is null or b.Tedit='')  and (m.Tedit is null or m.Tedit='') and (m.reference_no=b.reference_no) and (m.paymenttype<>'Imprest Adjustment' ) and (m.bookdate between '".$datefrom."' and '".$dateto."')   and (m.reverse_entry is null or m.reverse_entry='') and (m.projectid='".$projectid."') and b.purpose_code='".$purposecode."' group by m.payfromaccountid ");
								if ($u4bs->num_rows>0) {
						 		$bsrow=$u4bs->fetch_assoc();
						 			$nt4html.="<td>".$i."</td><td>".$hdkey['bankname']." (".$hdkey['accountno'].")</td><td>".$bsrow['total_amount']."</td></tr>";
						 			$totoamt=$totoamt+$bsrow['total_amount'];
						 		}else{
						 			$nt4html.="<td>".$i."</td><td>".$hdkey['bankname']." (".$hdkey['accountno'].")</td><td>0</td></tr>";
		 							$totoamt=$totoamt+0;
						 		}
						 		$i++;
						}
					}	
					$accdtls = json_encode($accarray)	;			
					$nt4html.="<tr class='bg-info font-weight-bold' data-allaccounts='".$accdtls."' ><td>&nbsp;</td><td>Total</td><td>".$totoamt."</td></tr>";
				$nt4html.="</tbody></table></div>";
	echo $nt4html;
}else if ($action=="notes4summarydetails_seprataccountwise") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$projectname=$_POST['projectname'];
	$accountid=$_POST['accountid'];
	$reporttype=$_POST['reporttype'];
	$purposecode=$_POST['purposecode'];
	$providedcode=$_POST['providedcode'];
	$datefrom=$_POST['newfromdata'];
	$dateto=$_POST['newtomdata'];
	$category=$_POST['category'];
	$subcategory=$_POST['subcategory'];
	$purpose=$_POST['purpose'];
	$repno = "N4(B)_".$companyid."_".$subcompanyid."_".$projectid."(".date_format(date_create($datefrom),'d-m-Y')."_TO_".date_format(date_create($dateto),'d-m-Y').")";
	$nt4html="empty";
	if($accountid=="all"){
		$accountname = "All Bank Accounts";
	}
	else{
		$qryac=$conn->query("select accountid,accountname,accountno,bankname from tbl_master_account where accountid='".$accountid."'");
		if ($qryac->num_rows>0) {
			$acrow=$qryac->fetch_assoc();
			$accountname = $acrow["accountname"]."-".$acrow["accountno"]."-".$acrow["bankname"];
		}
	}

	$qrypur=$conn->query("SELECT categoryid,category,sub_categoryid,sub_category,purposecode,purpose,provided_code FROM purpose_code_master where categoryid=1;");
	$options="";
	if ($qrypur->num_rows>0) {
		while($purrow=$qrypur->fetch_assoc()){
			if($options){
				$options = $options." <option data-categoryid='".$purrow['categoryid']."' data-category='".$purrow['category']."' data-subcategoryid='".$purrow['sub_categoryid']."' data-subcategory='".$purrow['sub_category']."' data-providedcode='".$purrow['provided_code']."' data-purpose='".$purrow['purpose']."' value='".$purrow['purposecode']."'>".$purrow['purpose']."[".$purrow['provided_code']."]</option>";
			}
			else{
				$options ="<option  data-categoryid='".$purrow['categoryid']."' data-category='".$purrow['category']."' data-subcategoryid='".$purrow['sub_categoryid']."' data-subcategory='".$purrow['sub_category']."' data-providedcode='".$purrow['provided_code']."' data-purpose='".$purrow['purpose']."' value='".$purrow['purposecode']."'>".$purrow['purpose']."[".$purrow['provided_code']."]</option>";
			}
		}
	}

	$nt4html= '<div class="row justify-content-center">
	<div class="col-md-2">
	<button class="btn btn-deepblue m-2" id="addpurpose" onclick="AddNewPurposeName(\'hidden_rooturl\',\'1\',\'\',\'\',\'withdrawn\')">Add New Purpose</button>
	</div>
	<div class="col-md-2">
	<button class="btn btn-deepblue m-2" id="viewpurpose" onclick="EditorPreviewPurposeName(\'hidden_rooturl\',\'1\',\'\',\'\',\'withdrawn\')">View Purpose</button>
	</div>
	<div class="col-md-2">
	<button class="btn btn-deepblue m-2" id="editpurpose">Replace Purpose</button>
	</div>
	<div class="col-md-2">
	<button class="btn btn-deepblue m-2 " onclick="gridtoexcel_withfilter_chunk(\'breakdownofpurposetbl\',\''.$repno.'\',\'landscape\');"><i class="fa fa-file-excel" style="color:green;"></i> Export</button>
	</div>
	</div>
	<div class="row mb-3 p-2 justify-content-center align-items-center d-none" id="purposeeditsection" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);background-color:aquamarine;">
		<div class="col-md-5 text-right">
			<label style="font-size:18px;">Replace in Selected Entries</label><span class="ml-2 font-weight-bold text-danger" id="existingpurpose" style="font-size:18px;">'.$purpose.'['.$providedcode.']'.'</span>
		</div>
		<div class="col-md-4">
			<label class="d-block" style="font-size:18px;">By Purpose:</label><select id="newpurpose" class="select2 form-control"><option value="" selected disabled>Choose Purpose</option>'.$options.'</select>
		</div>
		<div class="col-md-2">
			<button class="btn btn-deepblue m-2" id="replacepurpose">Replace</button>
		</div>
	</div>
	<div class="table-responsive bg-light mt-2" style="overflow:hidden;overflow-y:auto;" >
    <table class="table table-bordered bg-light" id="breakdownofpurposetbl">
    <colgroup>
    	<col style="width:50px;">
    	<col style="width:100px;">
    </colgroup>
      <thead class="sticky-top">
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark px-1" style="font-size:22px;">Distribution of Selected Purpose</th>
      	</tr>
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark p-1" style="font-size:16px;">Project Name: '.$projectname.'</th>
      	</tr>
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark p-1" style="font-size:16px;">Bank Account: '.$accountname.'</th>
      	</tr>
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark p-1"  style="font-size:16px;">Category: '.$category.'</th>
      	</tr>
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark p-1"  style="font-size:16px;">Sub-Category: '.$subcategory.'</th>
      	</tr>
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark p-1"  style="font-size:16px;">Purpose: '.$purpose.'['.$providedcode.']'.'</th>
      	</tr>
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark p-1"  style="font-size:16px;">Name of Excel File: '.$repno.'</th>
      	</tr>
      	<tr class="sticky-top noexport" style="background-color: powderblue;padding-right: 17px;margin-right: -17px;border: 1px solid lightblue;">
      	<th class="border-0 p-1 noexport">&nbsp;</th>
      	<th colspan="4" class="border-0 text-dark p-1"  style="background-color:powderblue;">
      		<label class="mx-1 text-dark lbl-text">Filter Bank Account</label>
			<select id="faccountlist" class="form-control select2 actioninput w3-padding" style="margin-top: 0px">
				<option selected disabled>Select Account</option>
			</select>
		</th>
		<th colspan="7" class="border-0 text-dark p-1"  style="background-color:powderblue;">&nbsp;</th>
      	</tr>
      	<tr class="sticky-top bg-info">
      		<th class="noexport">Select</th>
      		<th>Sno</th>
      		<th>Bank Name</th>
      		<th>Account No No</th>
      		<th>Date</th>
      		<th>Reference No</th>
      		<th>Reconstructed</th>
      		<th>Description</th>
      		<th>Payto</th>
      		<th>Beneficiary</th>
      		<th>Amount</th>
      		<th>Documents</th>
      	</tr>
      </thead>
      <tbody>';
			 	$totoamt=0;			 
			 	$u4bs=$conn->query("select a.bankname,a.accountno,m.finyear,b.sno,b.purpose_code,b.amount as amount,p.purpose,p.category as purpose_category,p.sub_category as purpose_subcategory,m.transactionno,m.reference_no,date_format(m.bookdate,'%d-%m-%Y') as bookdate,m.payto,m.beneficierie,case when (m.reconcilation_process like 'Reconst%') then 'YES' else 'No' end as reconstatus,case when (m.reconcilation_process like 'Reconst%') then c.description_bank else m.description end as description, b.documentation_path,b.beneficiary_name,m.payfromaccountid,p.categoryid as purpose_categoryid,p.sub_categoryid as purpose_subcategoryid from tbl_maintransaction_breakdown b join tbl_maintransaction m on (m.reference_no=b.reference_no) join purpose_code_master p on (p.purposecode=b.purpose_code) join tbl_closetobook c on (c.account_id=m.payfromaccountid and c.banksno=reconciliation_banksno) join tbl_master_account a on (a.accountid=m.payfromaccountid) where (b.Tedit is null or b.Tedit='') and (m.Tedit is null or m.Tedit='') and ifnull(m.paymenttype,'')<>'Imprest Adjustment' and (date(m.bookdate) between '".$datefrom."' and '".$dateto."') and (m.reverse_entry is null or m.reverse_entry='') and (m.projectid='".$projectid."') and b.purpose_code='".$purposecode."'");
	 			$j=0;
			 	if ($u4bs->num_rows>0) {
			 		while ($bsrow=$u4bs->fetch_assoc()) {
			 			$nt4html.="<tr class='dist_td'  data-note4finyear='".$bsrow['finyear']."' data-accountid='".$accountid."'  data-category='".$bsrow['purpose_category']."' data-subcategory='".$bsrow['purpose_subcategory']."' data-purpose='".$bsrow['purpose']."' data-purposecode='".$bsrow['purpose_code']."' data-categoryid='".$bsrow['purpose_categoryid']."' data-subcategoryid='".$bsrow['purpose_subcategoryid']."' data-sno='".$bsrow['sno']."' style='cursor:pointer;'>";
			 			if (!$bsrow['documentation_path']) {
			 				$bsrow['documentation_path']="No Document";
			 			}
			 			$nt4html.="<td><input type='checkbox' name='chkentry' /></td>
			 			<td>".++$j."</td>
			 			<td class='distbankname_td'>".$bsrow['bankname']."</td>
			 			<td class='distaccountno_td'>".$bsrow['accountno']."</td>
			 			<td class='distdate_td'>".$bsrow['bookdate']."</td>
			 			<td class='distreferenceno_td'>".$bsrow['reference_no']."</td>
			 			<td class='distreconstatus_td'>".$bsrow['reconstatus']."</td>
			 			<td class='distdesc_td'>".$bsrow['description']."</td>
			 			<td class='distpayto_td'>".$bsrow['payto']."</td>
			 			<td class='distbeneficery_td'>".$bsrow['beneficiary_name']."</td>
						<td class='distamount_td'>".$bsrow['amount']."</td>
			 			<td>".$bsrow['documentation_path']."</td>";
			 			$totoamt=$totoamt+$bsrow['amount'];
						$nt4html.='</tr>';
			 		}
			 	}
				$nt4html.='<tr class="bg-info">
					<td class="font-weight-bold">&nbsp;</td>
					<td colspan="9" class="font-weight-bold">Total Amount</td>
					<td class="font-weight-bold">'.$totoamt.'</td>
					<td>&nbsp;</td>
				</tr>
			</tbody>
		</table>
	</div>';
  echo $nt4html;
}else if ($action=="notes4summarydetails_seprataccountwisecredit") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$projectname=$_POST['projectname'];
	$accountid=$_POST['accountid'];
	$reporttype=$_POST['reporttype'];
	$purposecode=$_POST['purposecode'];
	$providedcode=$_POST['providedcode'];
	$datefrom=$_POST['newfromdata'];
	$dateto=$_POST['newtomdata'];
	$category=$_POST['category'];
	$subcategory=$_POST['subcategory'];
	$purpose=$_POST['purpose'];
	
	$repno = "N4C(B)_".$companyid."_".$subcompanyid."_".$projectid."(".date_format(date_create($datefrom),'d-m-Y')."_TO_".date_format(date_create($dateto),'d-m-Y').")";
	$nt4html="empty";
	if($accountid=="all"){
		$accountname = "All Bank Accounts";
	}
	else{
		$qryac=$conn->query("select accountid,accountname,accountno,bankname from tbl_master_account where accountid='".$accountid."'");
		if ($qryac->num_rows>0) {
			$acrow=$qryac->fetch_assoc();
			$accountname = $acrow["accountname"]."-".$acrow["accountno"]."-".$acrow["bankname"];
		}
	}

	$qrypur=$conn->query("SELECT distinct categoryid,category,sub_categoryid,sub_category,purposecode,purpose,provided_code FROM purpose_code_master_credit where categoryid=1");
	$options="";
	if ($qrypur->num_rows>0) {
		while($purrow=$qrypur->fetch_assoc()){
			if($options){
				$options = $options." <option data-categoryid='".$purrow['categoryid']."' data-category='".$purrow['category']."' data-subcategoryid='".$purrow['sub_categoryid']."' data-subcategory='".$purrow['sub_category']."' data-providedcode='".$purrow['provided_code']."' data-purpose='".$purrow['purpose']."' value='".$purrow['purposecode']."'>".$purrow['purpose']."[".$purrow['provided_code']."]</option>";
			}
			else{
				$options ="<option  data-categoryid='".$purrow['categoryid']."' data-category='".$purrow['category']."' data-subcategoryid='".$purrow['sub_categoryid']."' data-subcategory='".$purrow['sub_category']."' data-providedcode='".$purrow['provided_code']."' data-purpose='".$purrow['purpose']."' value='".$purrow['purposecode']."'>".$purrow['purpose']."[".$purrow['provided_code']."]</option>";
			}
		}
	}

	$nt4html= '<div class="row justify-content-center">
	<div class="col-md-2">
	<button class="btn btn-deepblue m-2" id="addpurpose" onclick="AddNewPurposeName(\'hidden_rooturl\',\'1\',\'\',\'\',\'withdrawn\')">Add New Purpose</button>
	</div>
	<div class="col-md-2">
	<button class="btn btn-deepblue m-2" id="viewpurpose" onclick="EditorPreviewPurposeName(\'hidden_rooturl\',\'1\',\'\',\'\',\'withdrawn\')">View Purpose</button>
	</div>
	<div class="col-md-2">
	<button class="btn btn-deepblue m-2" id="editpurpose">Replace Purpose</button>
	</div>
	<div class="col-md-2">
	<button class="btn btn-deepblue m-2 " onclick="gridtoexcel_withfilter_chunk(\'breakdownofpurposetbl\',\''.$repno.'\',\'landscape\');"><i class="fa fa-file-excel" style="color:green;"></i> Export</button>
	</div>
	</div>
	<div class="row mb-3 p-2 justify-content-center align-items-center d-none" id="purposeeditsection" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);background-color:aquamarine;">
		<div class="col-md-5 text-right">
			<label style="font-size:18px;">Replace in Selected Entries</label><span class="ml-2 font-weight-bold text-danger" id="existingpurpose" style="font-size:18px;">'.$purpose.'['.$providedcode.']'.'</span>
		</div>
		<div class="col-md-4">
			<label class="d-block" style="font-size:18px;">By Purpose:</label><select id="newpurpose" class="select2 form-control"><option value="" selected disabled>Choose Purpose</option>'.$options.'</select>
		</div>
		<div class="col-md-2">
			<button class="btn btn-deepblue m-2" id="replacepurpose">Replace</button>
		</div>
	</div>
	<div class="table-responsive bg-light mt-2" style="overflow:hidden;overflow-y:auto;" >
    <table class="table table-bordered bg-light" id="breakdownofpurposetbl">
    <colgroup>
    	<col style="width:50px;">
    	<col style="width:100px;">
    </colgroup>
      <thead class="sticky-top">
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark px-1" style="font-size:22px;">Distribution of Selected Purpose</th>
      	</tr>
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark p-1" style="font-size:16px;">Project Name: '.$projectname.'</th>
      	</tr>
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark p-1" style="font-size:16px;">Bank Account: '.$accountname.'</th>
      	</tr>
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark p-1"  style="font-size:16px;">Category: '.$category.'</th>
      	</tr>
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark p-1"  style="font-size:16px;">Sub-Category: '.$subcategory.'</th>
      	</tr>
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark p-1"  style="font-size:16px;">Purpose: '.$purpose.'['.$providedcode.']'.'</th>
      	</tr>
      	<tr class="sticky-top bg-light border-0">
      		<th class="border-0 p-1  noexport">&nbsp;</th>
      		<th colspan="11" class="border-0 text-dark p-1"  style="font-size:16px;">Name of Excel File: '.$repno.'</th>
      	</tr>
      	<tr class="sticky-top noexport" style="background-color: powderblue;padding-right: 17px;margin-right: -17px;border: 1px solid lightblue;">
      	<th class="border-0 p-1 noexport">&nbsp;</th>
      	<th colspan="4" class="border-0 text-dark p-1"  style="background-color:powderblue;">
      		<label class="mx-1 text-dark lbl-text">Filter Bank Account</label>
			<select id="faccountlist" class="form-control select2 actioninput w3-padding" style="margin-top: 0px">
				<option selected disabled>Select Account</option>
			</select>
		</th>
		<th colspan="7" class="border-0 text-dark p-1"  style="background-color:powderblue;">&nbsp;</th>
      	</tr>
      	<tr class="sticky-top bg-info">
      		<th class="noexport">Select</th>
      		<th>Sno</th>
      		<th>Bank Name</th>
      		<th>Account No No</th>
      		<th>Date</th>
      		<th>Reference No</th>
      		<th>Reconstructed</th>
      		<th>Description</th>
      		<th>Payto</th>
      		<th>Beneficiary</th>
      		<th>Amount</th>
      		<th>Documents</th>
      	</tr>
      </thead>
      <tbody>';
			 	$totoamt=0;			 
				$u4bs=$conn->query("select a.bankname,a.accountno,'' as finyear,b.sno,b.purposecode,b.amount as amount,p.purpose,p.category as purpose_category,p.sub_category as purpose_subcategory,'' as transactionno,b.referenceno_unique as reference_no,date_format(b.bank_date,'%d-%m-%Y') as bookdate,b.paidby as payto,b.actualpayee as beneficiary_name,case when (lcase(b.reconcilefrom) <> 'book') then 'YES' else 'No' end as reconstatus,case when (lcase(b.reconcilefrom) <> 'book') then c.description_bank else b.descriptionbyagent end as description, '' as documentation_path,b.actualpayee,b.accountid,p.categoryid as purpose_categoryid,p.sub_categoryid as purpose_subcategoryid from t2c_creditadvice b join purpose_code_master_credit p on (p.purposecode=b.purposecode) join tbl_closetobook c on (c.account_id=b.accountid and c.banksno=b.bank_sno) join tbl_master_account a on (a.accountid=b.accountid) where (date(bank_date) between '".$datefrom."' and '".$dateto."') and (b.projectid='".$projectid."') and b.purposecode='".$purposecode."'");

	 			$j=0;
			 	if ($u4bs->num_rows>0) {
			 		while ($bsrow=$u4bs->fetch_assoc()) {
			 			$nt4html.="<tr class='dist_td'  data-note4finyear='".$bsrow['finyear']."' data-accountid='".$accountid."'  data-category='".$bsrow['purpose_category']."' data-subcategory='".$bsrow['purpose_subcategory']."' data-purpose='".$bsrow['purpose']."' data-purposecode='".$bsrow['purposecode']."' data-categoryid='".$bsrow['purpose_categoryid']."' data-subcategoryid='".$bsrow['purpose_subcategoryid']."' data-sno='".$bsrow['sno']."' style='cursor:pointer;'>";
			 			if (!$bsrow['documentation_path']) {
			 				$bsrow['documentation_path']="No Document";
			 			}
			 			$nt4html.="<td><input type='checkbox' name='chkentry' /></td>
			 			<td>".++$j."</td>
			 			<td class='distbankname_td'>".$bsrow['bankname']."</td>
			 			<td class='distaccountno_td'>".$bsrow['accountno']."</td>
			 			<td class='distdate_td'>".$bsrow['bookdate']."</td>
			 			<td class='distreferenceno_td'>".$bsrow['reference_no']."</td>
			 			<td class='distreconstatus_td'>".$bsrow['reconstatus']."</td>
			 			<td class='distdesc_td'>".$bsrow['description']."</td>
			 			<td class='distpayto_td'>".$bsrow['payto']."</td>
			 			<td class='distbeneficery_td'>".$bsrow['beneficiary_name']."</td>
						<td class='distamount_td'>".$bsrow['amount']."</td>
			 			<td>".$bsrow['documentation_path']."</td>";
			 			$totoamt=$totoamt+$bsrow['amount'];
						$nt4html.='</tr>';
			 		}
			 	}
				$nt4html.='<tr class="bg-info">
					<td class="font-weight-bold">&nbsp;</td>
					<td colspan="9" class="font-weight-bold">Total Amount</td>
					<td class="font-weight-bold">'.$totoamt.'</td>
					<td>&nbsp;</td>
				</tr>
			</tbody>
		</table>
	</div>';
  echo $nt4html;
}
else if ($action=="updatepurpose") {
	$selectedsnos = $_POST['selectedsnos'];
	$categoryid = $_POST['categoryid'];
	$category = $_POST['category'];
	$subcategoryid = $_POST['subcategoryid'];
	$subcategory = $_POST['subcategory'];
	$providedcode = $_POST['providedcode'];
	$purposecode = $_POST['purposecode'];
	$purpose = $_POST['purpose'];
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

	$mconn->begin_transaction();
	$rst=array();
	try{
		$conn->query("update tbl_maintransaction_breakdown set purpose_category='".$category."',purpose_categoryid='".$categoryid."',purpose_subcategory='".$subcategory."',purpose='".$purpose."',purpose_code='".$purposecode."',provided_code='".$providedcode."' where sno in (".$selectedsnos.")");
		//$conn->commit();
		$rst=array('status'=>'Success', 'message'=>"Purpose Replaced Successfully");
	}
	catch (mysqli_sql_exception $e) {
    	$conn->rollback();
    	$rst=array('status'=>'Error', 'message'=>$e->getMessage());
	}
	echo json_encode($rst);

}else if ($action=="updatepurposecredit") {
	$selectedsnos = $_POST['selectedsnos'];
	$categoryid = $_POST['categoryid'];
	$category = $_POST['category'];
	$subcategoryid = $_POST['subcategoryid'];
	$subcategory = $_POST['subcategory'];
	$providedcode = $_POST['providedcode'];
	$purposecode = $_POST['purposecode'];
	$purpose = $_POST['purpose'];
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

	$mconn->begin_transaction();
	$rst=array();
	try{
		$conn->query("update t2c_creditadvice set purposecode='".$purposecode."' where sno in (".$selectedsnos.")");
		//$conn->commit();
		$rst=array('status'=>'Success', 'message'=>"Purpose Replaced Successfully");
	}
	catch (mysqli_sql_exception $e) {
    	$conn->rollback();
    	$rst=array('status'=>'Error', 'message'=>$e->getMessage());
	}
	echo json_encode($rst);

}else if ($action=="save_notes4BS"){
	$subcompanyid=$_POST['subcompanyid'];
	$companyid=$_POST['companyid'];
	$projectid=$_POST['projectid'];
	$datefrom=$_POST['datefrom'];
	$dateto=$_POST['dateto'];
  $tableData_A1 = $_POST['pTableData_A4BS'];
  echo json_encode(save_note4BS_getfunction($conn,$companyid,$subcompanyid,$projectid,$datefrom,$dateto,$tableData_A1));
}else if ($action=="save_notes4only"){
	$subcompanyid=$_POST['subcompanyid'];
	$companyid=$_POST['companyid'];
	$projectid=$_POST['projectid'];
	$datefrom=$_POST['datefrom'];
	$dateto=$_POST['dateto'];
  $tableData_A1 = $_POST['pTableData_A4BS'];
  echo json_encode(save_note4only_getfunction($conn,$companyid,$subcompanyid,$projectid,$datefrom,$dateto,$tableData_A1));
}else if ($action=="save_notes4BScredit"){
	$subcompanyid=$_POST['subcompanyid'];
	$companyid=$_POST['companyid'];
	$projectid=$_POST['projectid'];
	$datefrom=$_POST['datefrom'];
	$dateto=$_POST['dateto'];
  $tableData_A1 = $_POST['pTableData_A4BS'];
  echo json_encode(save_note4BScredit_getfunction($conn,$companyid,$subcompanyid,$projectid,$datefrom,$dateto,$tableData_A1));
}else if ($action=="save_notes4creditonly"){
	$subcompanyid=$_POST['subcompanyid'];
	$companyid=$_POST['companyid'];
	$projectid=$_POST['projectid'];
	$datefrom=$_POST['datefrom'];
	$dateto=$_POST['dateto'];
  $tableData_A1 = $_POST['pTableData_A4BS'];
  echo json_encode(save_note4onlycredit_getfunction($conn,$companyid,$subcompanyid,$projectid,$datefrom,$dateto,$tableData_A1));
}else if ($action=="getfinancialyear"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	echo json_encode(GetfinancialyearlistfromBankstatment($conn,$companyid,$subcompanyid,$projectid));
}else if($action=="markunknownfromReconciledentry"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$purposecode=$_POST['purposecode'];
	$finyear=$_POST['finyear'];
	$updateby=$_POST['updateby'];
	$updatebyvalue=$_POST['updatebyvalue'];
	$loginname=$_POST['loginname'];
	$ssq="";	
	$resultmessage = array();
	if ($updateby == "markedunknowningroup") {
    // Use parameterized queries to prevent SQL injection
    $stmt = $conn->prepare("SELECT COUNT(m.payfromaccountid) AS payfromaccountcount, COUNT(b.reference_no) AS noofreference, m.payfromaccountid, b.reference_no, b.purpose_code, b.purpose FROM tbl_maintransaction_breakdown b RIGHT JOIN tbl_maintransaction m ON m.companyid = b.companyid AND m.subcompanyid = b.subcompanyid AND m.projectid = b.projectid AND m.reference_no = b.reference_no WHERE m.companyid = ? AND m.subcompanyid = ? AND m.projectid = ? AND b.purpose_code = ? AND m.finyear = ? GROUP BY m.payfromaccountid, b.reference_no ORDER BY m.payfromaccountid");    
    // Bind parameters to prevent SQL injection
    $stmt->bind_param("sssss", $companyid, $subcompanyid, $projectid, $purposecode, $finyear);    
    // Execute the statement
    $stmt->execute();    
    // Fetch results
    $ssq = $stmt->get_result();
    $chkcountref = 0;
    if ($ssq) {
      if ($ssq->num_rows > 0) {
        while ($ccrow = $ssq->fetch_assoc()) {
            // Check for multiple entries
            if ($ccrow['payfromaccountcount'] > 1) {
                $resultmessage = array("status" => "Error", "message" => "There are multiple account entries, so you cannot mark unknown in the whole group");
                echo json_encode($resultmessage);
                die();
            } elseif ($ccrow['noofreference'] > 1) {
                $resultmessage = array("status" => "Error", "message" => "There are multiple entries in reference number " . $ccrow['reference_no'] . ", so you cannot mark unknown in the whole group");
                echo json_encode($resultmessage);
                die();
            } else {
                // Update statements with parameterized queries
                $teditremark = "markunknown on date " . date('Y-m-d H:i:s') . " by " . $loginname;

                // Update `tbl_maintransaction`
                $stmt1 = $conn->prepare("UPDATE tbl_maintransaction SET tedit = 'markunknown', tedit_remark = ? WHERE reference_no = ?");
                $stmt1->bind_param("ss", $teditremark, $ccrow['reference_no']);
                $stmt1->execute();

                // Update `tbl_maintransaction_breakdown`
                $stmt2 = $conn->prepare("UPDATE tbl_maintransaction_breakdown SET tedit = 'markunknown', tedit_remark = ? WHERE reference_no = ?");
                $stmt2->bind_param("ss", $teditremark, $ccrow['reference_no']);
                $stmt2->execute();

                // Update `tbl_closetobook`
                $stmt3 = $conn->prepare("UPDATE tbl_closetobook SET book_status = null, status=null, actule_referenceno = NULL WHERE actule_referenceno = ?");
                $stmt3->bind_param("s", $ccrow['reference_no']);
                $stmt3->execute();
                reconstructbookwith_unknown($conn,$companyid,$subcompanyid,$projectid,$ccrow['payfromaccountid'],'withdrawn');
                $resultmessage = array("status" => "Success", "message" => "All entries updated as unknown successfully");
           	}
        }
      } else {
          $resultmessage = array("status" => "Error", "message" => "Data not available or something went wrong");
      }
    } else {
        $resultmessage = array("status" => "Error", "message" => "Something went wrong. Contact the web admin.");
    }
	}else if($updateby=="markedunknowninwithsno"){
		// Use parameterized queries to prevent SQL injection
		$stmt = $conn->prepare("SELECT COUNT(m.payfromaccountid) AS payfromaccountcount, COUNT(b.reference_no) AS noofreference, m.payfromaccountid, b.reference_no, b.purpose_code, b.purpose FROM tbl_maintransaction_breakdown b JOIN tbl_maintransaction m ON m.companyid = b.companyid AND m.subcompanyid = b.subcompanyid AND m.projectid = b.projectid AND m.reference_no = b.reference_no WHERE m.companyid = ? AND m.subcompanyid = ? AND m.projectid = ? AND b.purpose_code = ? AND m.finyear = ? AND b.sno=? GROUP BY m.payfromaccountid, b.reference_no ORDER BY m.payfromaccountid");    
    // Bind parameters to prevent SQL injection
    $stmt->bind_param("ssssss", $companyid, $subcompanyid, $projectid, $purposecode, $finyear, $updatebyvalue);    
    // Execute the statement
    $stmt->execute();    
    // Fetch results
    $ssq = $stmt->get_result();
    $chkcountref = 0;
    if ($ssq) {
      if ($ssq->num_rows > 0) {
        while ($ccrow = $ssq->fetch_assoc()) {
            // Check for multiple entries
            if ($ccrow['payfromaccountcount'] > 1) {
                $resultmessage = array("status" => "Error", "message" => "There are multiple account entries, so you cannot mark unknown in the whole group");
                echo json_encode($resultmessage);
                die();
            } elseif ($ccrow['noofreference'] > 1) {
                $resultmessage = array("status" => "Error", "message" => "There are multiple entries in reference number " . $ccrow['reference_no'] . ", so you cannot mark unknown in the whole group");
                echo json_encode($resultmessage);
                die();
            } else {
                // Update statements with parameterized queries
                $teditremark = "markunknown on date " . date('Y-m-d H:i:s') . " by " . $loginname;

                // Update `tbl_maintransaction`
                $stmt1 = $conn->prepare("UPDATE tbl_maintransaction SET tedit = 'markunknown', tedit_remark = ? WHERE reference_no = ?");
                $stmt1->bind_param("ss", $teditremark, $ccrow['reference_no']);
                $stmt1->execute();

                // Update `tbl_maintransaction_breakdown`
                $stmt2 = $conn->prepare("UPDATE tbl_maintransaction_breakdown SET tedit = 'markunknown', tedit_remark = ? WHERE reference_no = ?");
                $stmt2->bind_param("ss", $teditremark, $ccrow['reference_no']);
                $stmt2->execute();

                // Update `tbl_closetobook`
                $stmt3 = $conn->prepare("UPDATE tbl_closetobook SET book_status = null, status=null, actule_referenceno = NULL WHERE actule_referenceno = ?");
                $stmt3->bind_param("s", $ccrow['reference_no']);
                $stmt3->execute();
                reconstructbookwith_unknown($conn,$companyid,$subcompanyid,$projectid,$ccrow['payfromaccountid'],'withdrawn');
                $resultmessage = array("status" => "Success", "message" => "Selected entry updated as unknown successfully");
           	}
        }
      } else {
          $resultmessage = array("status" => "Error", "message" => "Data not available or something went wrong");
      }
    } else {
        $resultmessage = array("status" => "Error", "message" => "Something went wrong. Contact the web admin.");
    }
	}
  echo json_encode($resultmessage);
}else if($action=="markunknownfromReconciledentry_credit"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$purposecode=$_POST['purposecode'];
	$finyear=$_POST['finyear'];
	$updateby=$_POST['updateby'];
	$updatebyvalue=$_POST['updatebyvalue'];
	$loginname=$_POST['loginname'];
	$ssq="";	
	$resultmessage = array();
	if ($updateby == "markedunknowningroup") {
    // Use parameterized queries to prevent SQL injection
    $stmt = $conn->prepare("SELECT COUNT(m.payfromaccountid) AS payfromaccountcount, COUNT(b.reference_no) AS noofreference, m.payfromaccountid, b.reference_no, b.purpose_code, b.purpose FROM tbl_maintransaction_credit_breakdown b RIGHT JOIN tbl_maintransaction_credit m ON m.companyid = b.companyid AND m.subcompanyid = b.subcompanyid AND m.projectid = b.projectid AND m.reference_no = b.reference_no WHERE m.companyid = ? AND m.subcompanyid = ? AND m.projectid = ? AND b.purpose_code = ? AND m.finyear = ? GROUP BY m.payfromaccountid, b.reference_no ORDER BY m.payfromaccountid");    
    // Bind parameters to prevent SQL injection
    $stmt->bind_param("sssss", $companyid, $subcompanyid, $projectid, $purposecode, $finyear);    
    // Execute the statement
    $stmt->execute();    
    // Fetch results
    $ssq = $stmt->get_result();
    $chkcountref = 0;
    if ($ssq) {
      if ($ssq->num_rows > 0) {
        while ($ccrow = $ssq->fetch_assoc()) {
            // Check for multiple entries
            if ($ccrow['payfromaccountcount'] > 1) {
                $resultmessage = array("status" => "Error", "message" => "There are multiple account entries, so you cannot mark unknown in the whole group");
                echo json_encode($resultmessage);
                die();
            } elseif ($ccrow['noofreference'] > 1) {
                $resultmessage = array("status" => "Error", "message" => "There are multiple entries in reference number " . $ccrow['reference_no'] . ", so you cannot mark unknown in the whole group");
                echo json_encode($resultmessage);
                die();
            } else {
                // Update statements with parameterized queries
                $teditremark = "markunknown on date " . date('Y-m-d H:i:s') . " by " . $loginname;

                // Update `tbl_maintransaction`
                $stmt1 = $conn->prepare("UPDATE tbl_maintransaction_credit SET tedit = 'markunknown', tedit_remark = ? WHERE reference_no = ?");
                $stmt1->bind_param("ss", $teditremark, $ccrow['reference_no']);
                $stmt1->execute();

                // Update `tbl_maintransaction_breakdown`
                $stmt2 = $conn->prepare("UPDATE tbl_maintransaction_credit_breakdown SET tedit = 'markunknown', tedit_remark = ? WHERE reference_no = ?");
                $stmt2->bind_param("ss", $teditremark, $ccrow['reference_no']);
                $stmt2->execute();

                // Update `tbl_closetobook`
                $stmt3 = $conn->prepare("UPDATE tbl_closetobook SET book_status = null, status=null, actule_referenceno = NULL WHERE actule_referenceno = ?");
                $stmt3->bind_param("s", $ccrow['reference_no']);
                $stmt3->execute();
                reconstructbookwith_unknown_credit($conn,$companyid,$subcompanyid,$projectid,$ccrow['payfromaccountid'],'deposit');
                $resultmessage = array("status" => "Success", "message" => "All entries updated as unknown successfully");
           	}
        }
      } else {
          $resultmessage = array("status" => "Error", "message" => "Data not available or something went wrong");
      }
    } else {
        $resultmessage = array("status" => "Error", "message" => "Something went wrong. Contact the web admin.");
    }
	}else if($updateby=="markedunknowninwithsno"){
		// Use parameterized queries to prevent SQL injection
		$stmt = $conn->prepare("SELECT COUNT(m.payfromaccountid) AS payfromaccountcount, COUNT(b.reference_no) AS noofreference, m.payfromaccountid, b.reference_no, b.purpose_code, b.purpose FROM tbl_maintransaction_credit_breakdown b JOIN tbl_maintransaction_credit m ON m.companyid = b.companyid AND m.subcompanyid = b.subcompanyid AND m.projectid = b.projectid AND m.reference_no = b.reference_no WHERE m.companyid = ? AND m.subcompanyid = ? AND m.projectid = ? AND b.purpose_code = ? AND m.finyear = ? AND b.sno=? GROUP BY m.payfromaccountid, b.reference_no ORDER BY m.payfromaccountid");    
    // Bind parameters to prevent SQL injection
    $stmt->bind_param("ssssss", $companyid, $subcompanyid, $projectid, $purposecode, $finyear, $updatebyvalue);    
    // Execute the statement
    $stmt->execute();    
    // Fetch results
    $ssq = $stmt->get_result();
    $chkcountref = 0;
    if ($ssq) {
      if ($ssq->num_rows > 0) {
        while ($ccrow = $ssq->fetch_assoc()) {
            // Check for multiple entries
            if ($ccrow['payfromaccountcount'] > 1) {
                $resultmessage = array("status" => "Error", "message" => "There are multiple account entries, so you cannot mark unknown in the whole group");
                echo json_encode($resultmessage);
                die();
            } elseif ($ccrow['noofreference'] > 1) {
                $resultmessage = array("status" => "Error", "message" => "There are multiple entries in reference number " . $ccrow['reference_no'] . ", so you cannot mark unknown in the whole group");
                echo json_encode($resultmessage);
                die();
            } else {
                // Update statements with parameterized queries
                $teditremark = "markunknown on date " . date('Y-m-d H:i:s') . " by " . $loginname;

                // Update `tbl_maintransaction`
                $stmt1 = $conn->prepare("UPDATE tbl_maintransaction_credit SET tedit = 'markunknown', tedit_remark = ? WHERE reference_no = ?");
                $stmt1->bind_param("ss", $teditremark, $ccrow['reference_no']);
                $stmt1->execute();

                // Update `tbl_maintransaction_breakdown`
                $stmt2 = $conn->prepare("UPDATE tbl_maintransaction_credit_breakdown SET tedit = 'markunknown', tedit_remark = ? WHERE reference_no = ?");
                $stmt2->bind_param("ss", $teditremark, $ccrow['reference_no']);
                $stmt2->execute();

                // Update `tbl_closetobook`
                $stmt3 = $conn->prepare("UPDATE tbl_closetobook SET book_status = null, status=null, actule_referenceno = NULL WHERE actule_referenceno = ?");
                $stmt3->bind_param("s", $ccrow['reference_no']);
                $stmt3->execute();
                reconstructbookwith_unknown_credit($conn,$companyid,$subcompanyid,$projectid,$ccrow['payfromaccountid'],'deposit');
                $resultmessage = array("status" => "Success", "message" => "Selected entry updated as unknown successfully");
           	}
        }
      } else {
          $resultmessage = array("status" => "Error", "message" => "Data not available or something went wrong");
      }
    } else {
        $resultmessage = array("status" => "Error", "message" => "Something went wrong. Contact the web admin.");
    }
	}
  echo json_encode($resultmessage);
}

function note4_summary_getfunction($conn,$accountid,$projectid,$date1,$date2,$companyid,$subcompanyid)
{
	$purpose="";
	$bankcharge_purposecode="";
	$sumtotalamount=0;
	$totalexpendature=0;
	$bankcharge=0;
	$imprestamount=0;
	$imprest_purposecode="";
	$purposecategory="";
	$pcid="";
	$pcsid="";
	$pcsid2="";
	$bpcid="";
	$bpcsid="";
	$purposecodeX="";
	$purposecodeXB="";

	if ($accountid=='all') {
		$uryacc="select group_concat(accountid) as accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')";
		$resutall=$conn->query($uryacc);
		if ($resutall->num_rows>0) {
			$arow=$resutall->fetch_assoc();
			$accountid=$arow['accountid'];
		}
	}
	
	$query_3 = "select distinct b.purpose_code, count(1) as totalentries,SUM(CASE WHEN m.reconcilation_process LIKE 'Reconst%' THEN 1 ELSE 0 END) AS reconentries,sum(round(b.amount,2)) as amount,b.purpose,b.purpose_category,b.purpose_subcategory,b.purpose_categoryid,b.purpose_subcategoryid,b.provided_code, m.finyear from  tbl_maintransaction_breakdown b join tbl_maintransaction m on (m.reference_no=b.reference_no) where m.payfromaccountid in(".$accountid.") and (b.Tedit is null or b.Tedit='') and (m.Tedit is null or m.Tedit='') and (ifnull(m.paymenttype,'')<>'Imprest Adjustment' ) and (date(m.value_date) between '".$date1."' and '".$date2."')   and (m.reverse_entry is null or m.reverse_entry='') and (m.projectid='".$projectid."') and m.reconciliation='Done' group by b.purpose_code order by b.purpose_category,b.purpose_subcategory";
	$result_3=$conn->query($query_3);
	if ($result_3->num_rows>0) 
	{
		while ($rows3=$result_3->fetch_assoc()) 
		{
		 	$pcode=$rows3['purpose_code'];
			$note3list[]=array('purposecategory'=>$rows3['purpose_category'],'subpurposecategory'=>$rows3['purpose_subcategory'],'purpose'=>$rows3['purpose'],'purposecode'=>$rows3['purpose_code'],'totalamount'=>$rows3['amount'],'statustotal'=>'','pcode'=>$pcode,'Xcode'=>$rows3['provided_code'],'catid'=>$rows3['purpose_categoryid'],'subcatid'=>$rows3['purpose_subcategoryid'],'finyear'=>$rows3['finyear'],'totalentries'=>$rows3['totalentries'],'reconentries'=>$rows3['reconentries']);
			$totl_amt=$rows3['amount'];
			$sumtotalamount=$sumtotalamount+$totl_amt;
		}
		$totalexpendature=$sumtotalamount;
		$note3list[]=array('purposecategory'=>'','subpurposecategory'=>'','purpose'=>'Total Expenditure','purposecode'=>'','totalamount'=>$totalexpendature,'statustotal'=>'total','pcode'=>'','Xcode'=>'','catid'=>'','subcatid'=>'','finyear'=>'','totalentries'=>'','reconentries'=>'');
		
	}else {
		$note3list[]=array();
	}
	return $note3list;
}
function note4_summary_getfunctioncredit($conn,$accountid,$projectid,$date1,$date2,$companyid,$subcompanyid)
{
	$purpose="";
	$bankcharge_purposecode="";
	$sumtotalamount=0;
	$totalexpendature=0;
	$bankcharge=0;
	$imprestamount=0;
	$imprest_purposecode="";
	$purposecategory="";
	$pcid="";
	$pcsid="";
	$pcsid2="";
	$bpcid="";
	$bpcsid="";
	$purposecodeX="";
	$purposecodeXB="";

	if ($accountid=='all') {
		$uryacc="select group_concat(accountid) as accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')";
		$resutall=$conn->query($uryacc);
		if ($resutall->num_rows>0) {
			$arow=$resutall->fetch_assoc();
			$accountid=$arow['accountid'];
		}
	}
	echo $query_3 = "select distinct b.purposecode, count(1) as totalentries,SUM(CASE WHEN lcase(b.reconcilefrom) <> 'book' THEN 1 ELSE 0 END) AS reconentries,sum(round(b.amount,2)) as amount,p.purpose,p.category,p.sub_category,p.categoryid,p.sub_categoryid,p.provided_code from  t2c_creditadvice b join purpose_code_master_credit p on (p.purposecode=b.purposecode) where b.accountid in(".$accountid.") and (date(bank_date) between '".$date1."' and '".$date2."') and (b.projectid='".$projectid."') and b.reconciliation='Complete' group by b.purposecode order by p.category,p.sub_category;";
	$result_3=$conn->query($query_3);
	if ($result_3->num_rows>0) 
	{
		while ($rows3=$result_3->fetch_assoc()) 
		{
		 	$pcode=$rows3['purpose_code'];
			$note3list[]=array('purposecategory'=>$rows3['category'],'subpurposecategory'=>$rows3['sub_category'],'purpose'=>$rows3['purpose'],'purposecode'=>$rows3['purposecode'],'totalamount'=>$rows3['amount'],'statustotal'=>'','pcode'=>$pcode,'Xcode'=>$rows3['provided_code'],'catid'=>$rows3['categoryid'],'subcatid'=>$rows3['sub_categoryid'],'finyear'=>'','totalentries'=>$rows3['totalentries'],'reconentries'=>$rows3['reconentries']);
			$totl_amt=$rows3['amount'];
			$sumtotalamount=$sumtotalamount+$totl_amt;
		}
		$totalexpendature=$sumtotalamount;
		$note3list[]=array('purposecategory'=>'','subpurposecategory'=>'','purpose'=>'Total Expenditure','purposecode'=>'','totalamount'=>$totalexpendature,'statustotal'=>'total','pcode'=>'','Xcode'=>'','catid'=>'','subcatid'=>'','finyear'=>'','totalentries'=>'','reconentries'=>'');
		
	}else {
		$note3list[]=array();
	}
	return $note3list;
}
function note4_summary_getfunction_note4($conn,$accountid,$projectid,$date1,$date2,$companyid,$subcompanyid)
{
	$purpose="";
	$bankcharge_purposecode="";
	$sumtotalamount=0;
	$totalexpendature=0;
	$bankcharge=0;
	$imprestamount=0;
	$imprest_purposecode="";
	$purposecategory="";
	$pcid="";
	$pcsid="";
	$pcsid2="";
	$bpcid="";
	$bpcsid="";
	$purposecodeX="";
	$purposecodeXB="";

	if ($accountid=='all') {
		$uryacc="select group_concat(accountid) as accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')";
		$resutall=$conn->query($uryacc);
		if ($resutall->num_rows>0) {
			$arow=$resutall->fetch_assoc();
			$accountid=$arow['accountid'];
		}
	}
	$purpose2="";
	$purposecategory2="";
	$subpurposesubcategory2="";

	$query_bankcharg4="select round(sum(amount),2) as bank_charge,purpose_code from tbl_bankcharge where account_id in('".str_replace(",", "','", $accountid)."') and transaction_date between '".$date1."' and '" .$date2. "' and projectid='" .$projectid. "'";
	$result_bankcharge4=$conn->query($query_bankcharg4);
	if ($result_bankcharge4->num_rows>0) 
	{
		$row_bankchrg4=$result_bankcharge4->fetch_assoc();
		if (is_null($row_bankchrg4['bank_charge'])) {
			$bankcharge=0;
			
		}else {
			$purposeinfo=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$row_bankchrg4['purpose_code']);
			foreach ($purposeinfo as $reprojrow) 
			{
				$purpose2=$reprojrow['purpose'];
				$purposecategory2=$reprojrow['pcategory'];
				$subpurposesubcategory2=$reprojrow['psub_category'];
				$pcid=$reprojrow['pcategorycode'];
				$pcsid=str_replace(".", "", $reprojrow['psub_categoryid']);
				$bpcid=$reprojrow['pcategorycode'];
				$bpcsid=$reprojrow['psub_categoryid'];
				$purposecodeXB=$reprojrow['provided_code'];
			}
			$bankcharge=$row_bankchrg4['bank_charge'];
			$bankcharge_purposecode=$row_bankchrg4['purpose_code'];
			// $pieces = explode($companyid.$subcompanyid.$projectid.$pcid.$pcsid, $row_bankchrg4['purpose_code']);
			// $purposecodeX=$pieces[1];
		}
	}
	
	$query_3 = "select distinct b.purpose_code, sum(round(b.amount,2)) as amount,b.purpose,b.purpose_category,b.purpose_subcategory from  tbl_maintransaction_breakdown b, tbl_maintransaction m where m.payfromaccountid in(".$accountid.") and (b.Tedit is null or b.Tedit='')  and (m.Tedit is null or m.Tedit='') and (m.reference_no=b.reference_no) and (m.paymenttype<>'Imprest Adjustment' ) and (m.bookdate between '".$date1."' and '".$date2."')   and (m.reverse_entry is null or m.reverse_entry='') and (m.projectid='".$projectid."')  group by b.purpose_code order by b.purpose_category,b.purpose_subcategory desc";
	$result_3=$conn->query($query_3);
	if ($result_3->num_rows>0) 
	{
		while ($rows3=$result_3->fetch_assoc()) 
		{
			$purpose="";
			$totl_amt=0;
			$purposeinfo=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$rows3['purpose_code']);
			foreach ($purposeinfo as $reprojrow) 
			{
				$purpose=$reprojrow['purpose'];
				$purposecategory=$reprojrow['pcategory'];
				$subpurposesubcategory=$reprojrow['psub_category'];
				$pcid=$reprojrow['pcategorycode'];
			  $pcsid=str_replace(".", "", $reprojrow['psub_categoryid']);
			  $pcsid2=$reprojrow['psub_categoryid'];
			  $purposecodeX=$reprojrow['provided_code'];
			}
		 	$pcode=$rows3['purpose_code'];
			$note3list[]=array('purposecategory'=>$purposecategory,'subpurposecategory'=>$subpurposesubcategory,'purpose'=>$purpose,'purposecode'=>$rows3['purpose_code'],'totalamount'=>$rows3['amount'],'statustotal'=>'','pcode'=>$pcode,'Xcode'=>$purposecodeX,'catid'=>$pcid,'subcatid'=>$pcsid2);
			$totl_amt=$rows3['amount'];
			$sumtotalamount=$sumtotalamount+$totl_amt;
		}

		if ($bankcharge>0) {
		$note3list[]=array('purposecategory'=>$purposecategory2,'subpurposecategory'=>$subpurposesubcategory2,'purpose'=>$purpose2,'purposecode'=>$bankcharge_purposecode,'totalamount'=>$bankcharge,'statustotal'=>'Bank Charge','pcode'=>$bankcharge_purposecode,'Xcode'=>$purposecodeXB,'catid'=>$bpcid,'subcatid'=>$bpcsid);
		}
		$totalexpendature=$sumtotalamount+$bankcharge ;
		$note3list[]=array('purposecategory'=>'','subpurposecategory'=>'','purpose'=>'Total Expenditure','purposecode'=>'','totalamount'=>$totalexpendature,'statustotal'=>'total','pcode'=>'','Xcode'=>'','catid'=>'','subcatid'=>'');
		
	}else {
		$note3list[]=array();
	}
	return $note3list;
}
function note4credit_summary_getfunction_note4($conn,$accountid,$projectid,$date1,$date2,$companyid,$subcompanyid)
{
	$purpose="";
	$bankcharge_purposecode="";
	$sumtotalamount=0;
	$totalexpendature=0;
	$bankcharge=0;
	$imprestamount=0;
	$imprest_purposecode="";
	$purposecategory="";
	$pcid="";
	$pcsid="";
	$pcsid2="";
	$bpcid="";
	$bpcsid="";
	$purposecodeX="";
	$purposecodeXB="";

	if ($accountid=='all') {
		$uryacc="select group_concat(accountid) as accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')";
		$resutall=$conn->query($uryacc);
		if ($resutall->num_rows>0) {
			$arow=$resutall->fetch_assoc();
			$accountid=$arow['accountid'];
		}
	}
	$purpose2="";
	$purposecategory2="";
	$subpurposesubcategory2="";

	$query_bankcharg4="select round(sum(amount),2) as bank_charge,purpose_code from tbl_bankcharge where account_id in('".str_replace(",", "','", $accountid)."') and transaction_date between '".$date1."' and '" .$date2. "' and projectid='" .$projectid. "'";
	$result_bankcharge4=$conn->query($query_bankcharg4);
	if ($result_bankcharge4->num_rows>0) 
	{
		$row_bankchrg4=$result_bankcharge4->fetch_assoc();
		if (is_null($row_bankchrg4['bank_charge'])) {
			$bankcharge=0;
			
		}else {
			$purposeinfo=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$row_bankchrg4['purpose_code']);
			foreach ($purposeinfo as $reprojrow) 
			{
				$purpose2=$reprojrow['purpose'];
				$purposecategory2=$reprojrow['pcategory'];
				$subpurposesubcategory2=$reprojrow['psub_category'];
				$pcid=$reprojrow['pcategorycode'];
				$pcsid=str_replace(".", "", $reprojrow['psub_categoryid']);
				$bpcid=$reprojrow['pcategorycode'];
				$bpcsid=$reprojrow['psub_categoryid'];
				$purposecodeXB=$reprojrow['provided_code'];
			}
			$bankcharge=$row_bankchrg4['bank_charge'];
			$bankcharge_purposecode=$row_bankchrg4['purpose_code'];
			// $pieces = explode($companyid.$subcompanyid.$projectid.$pcid.$pcsid, $row_bankchrg4['purpose_code']);
			// $purposecodeX=$pieces[1];
		}
	}
	
	$query_3 = "select distinct b.purpose_code, sum(round(b.amount,2)) as amount,b.purpose,b.purpose_category,b.purpose_subcategory from  tbl_maintransaction_credit_breakdown b, tbl_maintransaction_credit m where m.payfromaccountid in(".$accountid.") and (b.Tedit is null or b.Tedit='')  and (m.Tedit is null or m.Tedit='') and (m.reference_no=b.reference_no) and (m.paymenttype<>'Imprest Adjustment' ) and (m.bookdate between '".$date1."' and '".$date2."')   and (m.reverse_entry is null or m.reverse_entry='') and (m.projectid='".$projectid."')  group by b.purpose_code order by b.purpose_category,b.purpose_subcategory desc";
	$result_3=$conn->query($query_3);
	if ($result_3->num_rows>0) 
	{
		while ($rows3=$result_3->fetch_assoc()) 
		{
			$purpose="";
			$totl_amt=0;
			$purposeinfo=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$rows3['purpose_code']);
			foreach ($purposeinfo as $reprojrow) 
			{
				$purpose=$reprojrow['purpose'];
				$purposecategory=$reprojrow['pcategory'];
				$subpurposesubcategory=$reprojrow['psub_category'];
				$pcid=$reprojrow['pcategorycode'];
			  $pcsid=str_replace(".", "", $reprojrow['psub_categoryid']);
			  $pcsid2=$reprojrow['psub_categoryid'];
			  $purposecodeX=$reprojrow['provided_code'];
			}
		 	$pcode=$rows3['purpose_code'];
			$note3list[]=array('purposecategory'=>$purposecategory,'subpurposecategory'=>$subpurposesubcategory,'purpose'=>$purpose,'purposecode'=>$rows3['purpose_code'],'totalamount'=>$rows3['amount'],'statustotal'=>'','pcode'=>$pcode,'Xcode'=>$purposecodeX,'catid'=>$pcid,'subcatid'=>$pcsid2);
			$totl_amt=$rows3['amount'];
			$sumtotalamount=$sumtotalamount+$totl_amt;
		}

		if ($bankcharge>0) {
		$note3list[]=array('purposecategory'=>$purposecategory2,'subpurposecategory'=>$subpurposesubcategory2,'purpose'=>$purpose2,'purposecode'=>$bankcharge_purposecode,'totalamount'=>$bankcharge,'statustotal'=>'Bank Charge','pcode'=>$bankcharge_purposecode,'Xcode'=>$purposecodeXB,'catid'=>$bpcid,'subcatid'=>$bpcsid);
		}
		$totalexpendature=$sumtotalamount+$bankcharge ;
		$note3list[]=array('purposecategory'=>'','subpurposecategory'=>'','purpose'=>'Total Expenditure','purposecode'=>'','totalamount'=>$totalexpendature,'statustotal'=>'total','pcode'=>'','Xcode'=>'','catid'=>'','subcatid'=>'');
		
	}else {
		$note3list[]=array();
	}
	return $note3list;
}
function note4summarywithpurpose_getfunction($conn,$companyid,$subcompanyid,$projectid,$accountid,$date1,$date2,$purposecode){
	if ($accountid=='all') {
		$uryacc="select group_concat(accountid) as accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')";
		$resutall=$conn->query($uryacc);
		if ($resutall->num_rows>0) {
			$arow=$resutall->fetch_assoc();
			$accountid=$arow['accountid'];
		}
	}
	$sumtotalamount=0;
	$totalexpendature=0;
	$documentstatus="";
	 $query_3 = " select b.purpose_code,b.amount as amount,b.purpose,b.purpose_category,b.purpose_subcategory,m.transactionno,m.reference_no,m.bookdate,m.payto,m.beneficierie,b.description,b.description_bank,b.documentation_path,b.beneficiary_name,m.payfromaccountid from  tbl_maintransaction_breakdown b, tbl_maintransaction m where (m.payfromaccountid in('".str_replace(",", "','", $accountid)."')) and (b.Tedit is null or b.Tedit='')  and (m.Tedit is null or m.Tedit='') and (m.reference_no=b.reference_no) and (m.paymenttype<>'Imprest Adjustment' and m.paymenttype<>'Investment') and (m.bookdate between '".$date1."' and '".$date2."')   and (m.reverse_entry is null or m.reverse_entry='') and (m.projectid='".$projectid."')  and b.purpose_code='".$purposecode."'";
	$result_3=$conn->query($query_3);
	if ($result_3->num_rows>0) 
	{
		while ($rows3=$result_3->fetch_assoc()) 
		{
			$purpose="";
			$totl_amt=0;
		 	$purpose=$rows3['purpose'];
		 	$subpurposesubcategory=$rows3['purpose_subcategory'];
		 	$purposecategory=$rows3['purpose_category'];
		 	$purposecodeexplod=explode('-', $rows3['purpose_code']);
		 	$pcode=$purposecodeexplod[1];
		 	if ($rows3['documentation_path']=="") {
		 		$documentstatus="No Documents";
		 	}else{
		 		$documentstatus="Show Documents";
		 	}
		 	$compaccinfo="";
		 	$accinfo=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,'accountid',$rows3['payfromaccountid']);
		 	foreach ($accinfo as $reprojrow) 
			{
				$accountname=$reprojrow['accountname'];
				$bankname=$reprojrow['bankname'];
				$accountno=$reprojrow['accountno'];
				$compaccinfo=$accountname."_".$bankname."_".$accountno;
			}


			$note3list[]=array('purposecategory'=>$purposecategory,'subpurposecategory'=>$subpurposesubcategory,'purpose'=>$purpose,'purposecode'=>$rows3['purpose_code'],'totalamount'=>$rows3['amount'],'statustotal'=>'','pcode'=>$pcode,'date'=>date('d-m-Y',strtotime($rows3['bookdate'])),'reference_no'=>$rows3['reference_no'],'description'=>$rows3['description'],'description_bank'=>$rows3['description_bank'],'transactionno'=>$rows3['transactionno'],'payto'=>$rows3['payto'],'beneficierie'=>$rows3['beneficierie'],'beneficiary_name'=>$rows3['beneficiary_name'],'documentstatus'=>$documentstatus,'documentation_path'=>$rows3['documentation_path'],'compaccinfo'=>$compaccinfo);
			$totl_amt=$rows3['amount'];
			$sumtotalamount=$sumtotalamount+$totl_amt;
		}
		$totalexpendature=$sumtotalamount ;
		$note3list[]=array('purposecategory'=>'','subpurposecategory'=>'','purpose'=>'','purposecode'=>'','totalamount'=>$totalexpendature,'statustotal'=>'','pcode'=>'','date'=>'','reference_no'=>'','description'=>'','description_bank'=>'','transactionno'=>'','payto'=>'','beneficierie'=>'','beneficiary_name'=>'Total Amount','documentstatus'=>'','documentation_path'=>'','documentation_path'=>'');
		
	}else {
		$note3list[]=array();
	}

	return $note3list;
}

function save_note4BS_getfunction($conn,$companyid,$subcompanyid,$projectid,$date1,$date2,$tableData_A1){
	$status="";
  $conn->query("Update note_4BS set Tedit='Tedit' where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and account_id='".$accountid."' and from_date='".$date1."' and to_date='".$date2."'");
	$tableData_A1 = json_decode($tableData_A1,TRUE);
  foreach($tableData_A1 as $row1){
		foreach($row1 as $key1 => $value1){
			if($key1=='accountid'){
				 $accountid =$value1;
			}elseif($key1=='purpose_description'){
				$purpose_description =addslashes($value1);
			}else if($key1=='purpose_code'){
				$purpose_code =$value1;
			}else if($key1=='total_amount'){
				$total_amount =$value1;
			}else if($key1=='provided_code'){
				$provided_code =$value1;
			}else if($key1=='purpose_category'){
				$purpose_category =addslashes($value1);
			}else if($key1=='purpose_subcategory'){
				$purpose_subcategory =addslashes($value1);
			}
		}

		/*$getdata2="select sno from note_4BS where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and account_id='".$accountid."' and from_date='".$date1."' and to_date='".$date2."' and purpose_code='".$purpose_code."' and (Tedit is not null and Tedit <>'')";
	  $resultbs2=$conn->query($getdata2);
	  if ($resultbs2->num_rows>0) {
	  	$rowsno=$resultbs2->fetch_assoc();
	  	$sql_insert_note4="Update note_4BS set Tedit=null,total_amount='".$total_amount."' where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and account_id='".$accountid."' and from_date='".$date1."' and to_date='".$date2."' and sno='".$rowsno['sno']."'";
	  }else{*/
	  	$sql_insert_note4="insert into note_4BS(company_id,subcompany_id,project_id,account_id,from_date,to_date,purpose_description,purpose_code,total_amount,provided_code,purpose_category,purpose_subcategory)values('".$companyid."','".$subcompanyid."','".$projectid."','".$accountid."','".$date1."','".$date2."','".$purpose_description."','".$purpose_code."','".$total_amount."','".$provided_code."','".$purpose_category."','".$purpose_subcategory."')  ";
	  //}
	 	if ($conn->query($sql_insert_note4)) 
	 	{
	 		$status="Success";
	 	}else{
	 		$status="UnSuccess";
	 	}
	}
	$note4result[]=array("Status" =>$status);
	return $note4result;
}

function save_note4only_getfunction($conn,$companyid,$subcompanyid,$projectid,$date1,$date2,$tableData_A1){
	$status="";
  $conn->query("Update note_4 set Tedit='Tedit' where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and account_id='".$accountid."' and from_date='".$date1."' and to_date='".$date2."'");
	$tableData_A1 = json_decode($tableData_A1,TRUE);
    foreach($tableData_A1 as $row1){
		foreach($row1 as $key1 => $value1){
			if($key1=='accountid'){
				 $accountid =$value1;
			}elseif($key1=='purpose_description'){
				 $purpose_description =addslashes($value1);
			}else if($key1=='purpose_code'){
					$purpose_code =$value1;
			}
			else if($key1=='total_amount'){
					$total_amount =$value1;
			}else if($key1=='provided_code'){
					$provided_code =$value1;
			}else if($key1=='purpose_category'){
					$purpose_category =addslashes($value1);
			}else if($key1=='purpose_subcategory'){
					$purpose_subcategory =addslashes($value1);
			}
		}

		
	  $sql_insert_note4="insert into note_4(company_id,subcompany_id,project_id,account_id,from_date,to_date,purpose_description,purpose_code,total_amount,provided_code,purpose_category,purpose_subcategory)values('".$companyid."','".$subcompanyid."','".$projectid."','".$accountid."','".$date1."','".$date2."','".$purpose_description."','".$purpose_code."','".$total_amount."','".$provided_code."','".$purpose_category."','".$purpose_subcategory."')  ";
	  if ($conn->query($sql_insert_note4)) 
	 	{
	 		$status="Success";
	 	}else{
	 		$status="UnSuccess";
	 	}
	}
	$note4result[]=array("Status" =>$status);
	return $note4result;
}

function save_note4BScredit_getfunction($conn,$companyid,$subcompanyid,$projectid,$date1,$date2,$tableData_A1){
	$status="";
  $conn->query("Update note_4BS_credit set Tedit='Tedit' where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and account_id='".$accountid."' and from_date='".$date1."' and to_date='".$date2."'");
	$tableData_A1 = json_decode($tableData_A1,TRUE);
  foreach($tableData_A1 as $row1){
		foreach($row1 as $key1 => $value1){
			if($key1=='accountid'){
				 $accountid =$value1;
			}elseif($key1=='purpose_description'){
				$purpose_description =addslashes($value1);
			}else if($key1=='purpose_code'){
				$purpose_code =$value1;
			}else if($key1=='total_amount'){
				$total_amount =$value1;
			}else if($key1=='provided_code'){
				$provided_code =$value1;
			}else if($key1=='purpose_category'){
				$purpose_category =addslashes($value1);
			}else if($key1=='purpose_subcategory'){
				$purpose_subcategory =addslashes($value1);
			}
		}

	  	$sql_insert_note4="insert into note_4BS_credit(company_id,subcompany_id,project_id,account_id,from_date,to_date,purpose_description,purpose_code,total_amount,provided_code,purpose_category,purpose_subcategory)values('".$companyid."','".$subcompanyid."','".$projectid."','".$accountid."','".$date1."','".$date2."','".$purpose_description."','".$purpose_code."','".$total_amount."','".$provided_code."','".$purpose_category."','".$purpose_subcategory."')  ";
	  //}
	 	if ($conn->query($sql_insert_note4)) 
	 	{
	 		$status="Success";
	 	}else{
	 		$status="UnSuccess";
	 	}
	}
	$note4result[]=array("Status" =>$status);
	return $note4result;
}

function save_note4onlycredit_getfunction($conn,$companyid,$subcompanyid,$projectid,$date1,$date2,$tableData_A1){
	$status="";
  $conn->query("Update note_4_credit set Tedit='Tedit' where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and account_id='".$accountid."' and from_date='".$date1."' and to_date='".$date2."'");
	$tableData_A1 = json_decode($tableData_A1,TRUE);
    foreach($tableData_A1 as $row1){
		foreach($row1 as $key1 => $value1){
			if($key1=='accountid'){
				 $accountid =$value1;
			}elseif($key1=='purpose_description'){
				 $purpose_description =addslashes($value1);
			}else if($key1=='purpose_code'){
					$purpose_code =$value1;
			}
			else if($key1=='total_amount'){
					$total_amount =$value1;
			}else if($key1=='provided_code'){
					$provided_code =$value1;
			}else if($key1=='purpose_category'){
					$purpose_category =addslashes($value1);
			}else if($key1=='purpose_subcategory'){
					$purpose_subcategory =addslashes($value1);
			}
		}

		
	  $sql_insert_note4="insert into note_4_credit(company_id,subcompany_id,project_id,account_id,from_date,to_date,purpose_description,purpose_code,total_amount,provided_code,purpose_category,purpose_subcategory)values('".$companyid."','".$subcompanyid."','".$projectid."','".$accountid."','".$date1."','".$date2."','".$purpose_description."','".$purpose_code."','".$total_amount."','".$provided_code."','".$purpose_category."','".$purpose_subcategory."')  ";
	  if ($conn->query($sql_insert_note4)) 
	 	{
	 		$status="Success";
	 	}else{
	 		$status="UnSuccess";
	 	}
	}
	$note4result[]=array("Status" =>$status);
	return $note4result;
}
?>