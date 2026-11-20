<?php
include('../header.php');
?>
<div class="main-container">
    <div class="row text-center">
        <div class="col-sm-12">
            <h3 class=" thin">LOGIN MASTER </h3>
        </div>
  	</div>
  	<div class="row mt-2">
  		<div class="col-md-6">
            <a class="btn btn-deepblue btn-sm" href="<?php echo $baseurl; ?>index.php?k=<?php echo $_GET['k']; ?>"><i class="fa fa-arrow-left"></i> Back</a>
        </div>
  		<div class="col-md-6">
            <a class="btn btn-deepblue m-2 pull-right" id="uploadlogin_btn" href="login-upload.php?k=<?php echo $_GET['k']; ?>"><i class="fa fa-upload"></i> UPLOAD LOGIN DETAILS</a>
  			<a class="btn btn-deepblue m-2 pull-right" id="addeditlogin_btn" href="login-new.php?k=<?php echo $_GET['k']; ?>"><i class="fa fa-plus"></i> NEW USER</a>
  		</div>
  	</div>
  	<div class="row mt-2">
  		<div class="col-md-12">
  			<table id="loginlist_tbl" class="table table-bordered table-light table-striped">
  				<thead>
  					<tr>
  						<th class="text-center">#</th>
                        <th class="text-center">User Id</th>
  						<th>User Name</th>
  						<th>Role</th>
  						<th>Mobile No</th>
                        <th>Email Address</th>
  						<th>Authorized Project</th>
                        <th>Department</th>
  						<th class="text-center">Action</th>
  					</tr>
  				</thead>
  				<tbody></tbody>
  			</table>
  		</div>
  	</div>
</div>
<?php
include('../footer.php');
?>
<!-- <script type="text/javascript" src="../js/js_selft/admindashboard.js"></script> -->
<script type="text/javascript">

var loginid = "<?php echo $loginid; ?>";
var loginroleid = "<?php echo $loginroleid; ?>";
var companyid = "<?php echo $companyid; ?>";
var subcompanyid = "<?php echo $subcompanyid; ?>";
var projectid = "<?php echo $projectid; ?>";
var loginrolename = "<?php echo $loginrolename; ?>";
var rooturl = "<?php echo $rooturl; ?>";
logindetails_htmltableview('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','hidden_loginid','loginlist_tbl',function(res){
    let usercount=0;
    $("#loginlist_tbl tbody tr").each(function(row,tr){
        if($(tr).find('td:eq(3)').text()=="User"){
            usercount++;
        }
    });
    if(usercount>1){
        $("#uploadlogin_btn").hide();
    }
});
$(document).on("click", ".editlginfo", function(){ 
    var editloginid = $(this).closest("tr").find("td.lgloginid_td").text();
    var qstring = loginid+"|"+loginroleid+"|"+companyid+"|"+subcompanyid+"|"+projectid+"|"+loginrolename;
    var enc_qstring = url_btoa(qstring);
    var key = qstring + "|" + enc_qstring;
    var enc_key = url_btoa(key);
    window.location.href=rooturl+"adminmodule/login-new.php?k="+enc_key+"&edlid="+editloginid;
});
</script>
