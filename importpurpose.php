<?php
require('header.php');
if($companyid!=""){
?>
<div class="main">
	<div class="row">
	    <div class="col-md-12 mt-1 mb-2 pt-3 pb-3 text-center" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">
			<span class="uppercase  bold" style="font-size: 1.75rem;text-shadow:2px 2px gray;">Purpose Code Master</span>
	    </div>
	</div>
	<div class="row">
	    <div class="col-md-12 mt-1 mb-2  text-center">
	    	<a class="btn btn-success mb-0" href="./importpurposedebit.php?k=<?php echo $key; ?>">
					<h5 class="uppercase ">Purpose Master For Withdrawals</h5>
			</a>
			<a class="btn btn-success mb-0" href="./importpurposecredit.php?k=<?php echo $key; ?>">
					<h5 class="uppercase ">Purpose Master For Deposits</h5>
			</a>
	    </div>
	</div>
</div>
<?php
}
require('footer.php');
?>
