<?php
$protocol=$_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
$url = basename( $_SERVER["PHP_SELF"]);
$rooturl = $protocol.'://'.$_SERVER['HTTP_HOST']."/";
$baseurl= $rooturl.str_replace($url, "", $_SERVER["PHP_SELF"]);
?>
<div class="row">
	<div class="col-md-12">
		<div class="form-group">
			<label>What is new purpose?</label>
			<input type="text" class="form-control" id="new_purposename" placeholder="Enter new purpose name" />
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12 text-center">
		<button class='btn btn-success' onclick='savetodb_newpurposedetails("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","<?php echo $ppcatid!=""?$ppcatid:""; ?>",$("#spe_purposesubcategory").val(),"new_purposename","spe_purposecode","hidden_loginname");'>Save Purpose Details</button>
	</div>
</div>