<?php
include('header.php');
// echo $servername;
$grpid = "";
if(isset($_GET['grpid'])){
	$grpid = $_GET['grpid'];
}
$checkpp = getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"checkpurpose_providedbyproject","all");
$checkpurposemsg ="";
if(count($checkpp)==0){
	$checkpurposemsg = "<p style='color:red;'>The client purpose was not found. Please add or update it before proceeding. <a class='btn btn-sm btn-deepblue' href='{$routurl}importpurposedebit.php?k={$key}'> <i class='fa fa-plus-circle'></i> Add Purpose</a></p>";

}

$checkbnk = getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
$checkbankacmsg ="";
if(count($checkbnk)==0){
	$checkbankacmsg = "<p style='color:red;'>No bank account is registered with this process. Please update at least one bank account before proceeding. <button class='btn btn-sm btn-deepblue'  onclick=\"openModalForAddNewBank('hidden_rooturl');\"> <i class='fa fa-plus-circle'></i> ADD Bank Account</button></p>";
}

// print_r($checkpp);
?>
<div class="container-main">
	<div class="row">
		<div class="col-md-12">
			<a href="indexprojects.php?k=<?php echo $key; ?>" class="btn btn-deepblue btn-sm pull-right m-2" id="backtoprojects" style="display:none;">Back to Project Selection</a>
			
		</div>
	</div>
	<div class="card">
		<div class="card-header">
			<div class="row">
				<div class="col-md-6">
					<h3>AUTHORIZED MODULES</h3>
				</div>
			</div>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-12">
					<div class="mt-2"><?php //echo $checkpurposemsg; ?></div>
					<div class="mt-2"><?php //echo $checkbankacmsg; ?></div>
				</div>
				<div class="col-md-12">
				<?php
				if($companyid!="" && $companyid!="undefined" && $subcompanyid!="" && $subcompanyid!="undefined" && $projectid!="" && $projectid!="undefined")
				{ 
					$ggrp=OnlyProject_loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"allactivegroup","");
					if(count($ggrp)>0){
						foreach ($ggrp as $ggkey) {
							if($loginroleid==0){
								$paneldetails = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"groupid",$ggkey['groupid']);
							}else{
								$allmodules = allauthorizedmoduleid($authconn,$companyid,$subcompanyid,$projectid,$loginid);
								$ggpidiwthmoduleid=$ggkey['groupid']."|".$allmodules;
								$paneldetails=OnlyProject_loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"groupidwithmoduleid",$ggpidiwthmoduleid);
							}
							if(count($paneldetails)>0){
								$onlysuperadminaccess="";
								if($ggkey['groupid']==0 && $loginroleid>0){
									$onlysuperadminaccess="d-none";
								}
				?>
							<details class="text-center <?php echo $onlysuperadminaccess; ?>" id="groupname_<?php echo $ggkey['groupid']; ?>">
								<summary><?php echo strtoupper($ggkey['groupname']); ?></summary>
									<div id="buttonContainer">
										<?php
										foreach ($paneldetails as $rdetails) 
									 	{
									 		$frmid ="";
									 		if($rdetails['frmid']){
									 			$frmid = "&frmid=".$rdetails['frmid'];
									 		}
									 		if(count($paneldetails)==1 && count($ggrp)==1){
												echo '<script>window.location.href = "'.$rdetails['modulefoldername'].'?k='.$key.'&subaction='.$rdetails['subaction'].$frmid.'";</script>';
											}
										?>
    										<a class="btn modulebtn mt-2 btn-deepwhite w-100" href="<?php echo $rdetails['modulefoldername']; ?>?k=<?php echo $key; ?>&subaction=<?php echo $rdetails['subaction'].$frmid; ?>&mdid=<?php echo $rdetails['moduleid']; ?>" 
									           style="max-width:60%; height: auto; white-space: normal;">
									            <h6 class="uppercase"><?php echo strtoupper($rdetails['modulename']); ?></h6>
									        </a>
										<?php
									 	}
									 	?>
									 </div>
							</details>
							<?php
							}
						}
					}	
				}
				?>				
				</div>
			</div>
		</div>
	</div>
</div>
<?php
include('footer.php');
?>
<script type="text/javascript">
var grpid = "<?php echo $grpid; ?>";
authorizedprojects_homehtmlview('hidden_rooturl',$("body").data("loginid"),function(res){
	// console.log(res);
	if(res.length>1){
		$("#backtoprojects").show();
	}else{
		$("#backtoprojects").hide();
	}
});
var grpid = "<?php echo $grpid; ?>";
if(grpid){
	let detailId = "groupname_"+grpid; // Replace with your actual ID
	let $detailElem = $("#" + detailId);
	if ($detailElem.length > 0) {
	    $detailElem.prop("open", true); // Open the details tag

	    // Smooth scroll to the details tag
	    $("html, body").animate({
	        scrollTop: $detailElem.offset().top
	    }, 600); // 600 ms for smooth scroll duration
	}
}
</script>
