<?php
session_start();
$_SESSION['starttime']=0;
$email = $_SESSION['email'];
$email_replace = str_replace(".","dot", $email);
$new_email = str_replace("@","at", $email_replace);
require('../Connection.php');
require('../functions/request_fn.php');
require('../functions/request_fn_master.php');
if(isset($_SESSION['name'])){
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>UPDATE TRANSACTION NO OF REQUESTED PAYMENT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="../../js/jquery.min.js"></script>
    <link href="../../css/icps/theme-gunmetal.css" rel="stylesheet" type="text/css" media="all" />
    <!-- <link rel="stylesheet" href="../css/w3.css"> -->
    <link rel="stylesheet" href="../../css/w3.css">
    <link rel="stylesheet" href="../../css/bootstrap_manrega.css">
    <link rel="stylesheet" href="../../css/fontraleway.css">
    <link href="../../css/icps/details.css" rel="stylesheet" type="text/css" media="all" >
    <!-- <link href="../css/icps/askt_table.css" rel="stylesheet" type="text/css" media="all" > -->
  	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script src="https://mozilla.github.io/pdf.js/build/pdf.js"></script>

    
  </head>
<body class="shade1">
  <div class="w3-row p-2 w3-border-gray w3-border-bottom">
    <div class="float-left p-2">
      <a><h5 class="w3- bold">
          Welcome, <?php echo $_SESSION['name'];?>
        </h5>
      </a>
    </div>
    <div class="float-right p-2">
      <a href="javascript:history.go(-1)"> 
        <h5 class=" bold">
        Go Back
        </h5>
      </a>
    </div>
  </div>
  <div class="w3-row">
    <br>
  	<div class="text-center w3-margin-top">
      <h4 class=" bold">
        ACCOUNT LINNK WITH EMAIL <b class="w3-text-blue"><?php echo strtoupper($email); ?></b>
      </h4>
    </div>
  </div>
  <div class="shade2 p-3" style="margin: 50px 10px;">
		<div class="w3-row">
    		<h6 class="w3- ">Bank Account <span onclick="document.getElementById('accountlinkdiv').style.display='block'"  class="div_open_white"></span></h6>
    	</div>
    	<div class="w3-row shade1" id="accountlinkdiv">
    		<p><span onclick="document.getElementById('accountlinkdiv').style.display='none'"  class="div_collapse"></span></p>
    		<table class="table table-borderless w3-">
            <?php
            $pshortby='emailid';
            $pshortbyvalue=$email;
            $projectlist = projectdetails($conn,$pshortby,$pshortbyvalue);
            foreach ($projectlist as $prjlist) {
            	$accountid = $prjlist['accountid'];
            	$acclist= getaccountinfo($conn,$accountid);
            	foreach ($acclist as $accnrow) {
            	echo "<tr><td><h4>".$accnrow['bankname'].'['.$accnrow['accountno']."]</h4></td><td><input type='radio' class='w3-radio proceedtorequest' name='sltradiobank' value='".$accnrow['accountid']."' /></td></tr>";	
            	}
            }
            ?>	
            </table>
    	</div>
    </div>
</body>
</html>
<?php
}else{
  echo "<meta http-equiv='refresh' content='0;url=../dashboard.php' />";
}
?>
<script type="text/javascript">
	$(document).ready(function(){
		$("input[name='sltradiobank']").click(function(){
			var accountid = $(this).val();
			window.location.href="viewpendingtransfer.php?accountid="+accountid;
		});
	});
</script>