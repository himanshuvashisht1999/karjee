<?php
include('../header.php');
?>
<div class="main-container">
    <div class="row text-center mt-4">
        <div class="col-sm-12">
            <!-- <h2 class=" thin">PROJECT'S OF <?php //echo strtoupper($companyname); ?> </h2> -->
            <h4 class=" mb0 "><?php //echo $loginrolename; ?> </h4><br>
        </div>
  	</div>
  	<div class="row">
  		<div class="col-md-2">
            <a class="btn btn-deepblue btn-sm" href="<?php echo $baseurl; ?>index.php?k=<?php echo $_GET['k']; ?>">Back</a>
        </div>
  		<div class="col-md-10">
  			<a class="btn btn-deepblue pull-right" id="addeditlogin_btn" href="project-new.php?k=<?php echo $_GET['k']; ?>">Add New Project</a>
  		</div>
  	</div>
  	<div class="row mt-2">
  		<div class="col-md-12">
  			<table id="projectlist_tbl" class="table table-bordered table-light table-striped">
  				<thead>
  					<tr>
  						<th class="text-center">S.No</th>
                        <th>Project Name</th>
                        <th>Sub-Project</th>
  						<th>Project Email Address</th>
  						<th style="display: none;">Project Id</th>
                        <th style="display: none;">Company Id</th>
                        <th style="display: none;">Sub-company Id</th>
  						<th>Action</th>
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
<script type="text/javascript">
var loginid = "<?php echo $loginid; ?>";
var loginroleid = "<?php echo $loginroleid; ?>";
var loginrolename = "<?php echo $loginrolename; ?>";
var rooturl = "<?php echo $rooturl; ?>";
projectdetails_htmltableview('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','hidden_loginid','projectlist_tbl');
$(document).on("click", ".editprjinfo", function(){
    var projectid = $(this).closest("tr").find("td.prjid_td").text();
    var companyid = $(this).closest("tr").find("td.prjcmpid_td").text();
    var subcompanyid = $(this).closest("tr").find("td.prjsbcmpid_td").text();
    var qstring = loginid+"|"+loginroleid+"|"+companyid+"|"+subcompanyid+"|"+projectid+"|"+loginrolename;
    var enc_qstring = url_btoa(qstring);
    var key = qstring + "|" + enc_qstring;
    var enc_key = url_btoa(key);
    window.location.href=rooturl+"adminmodule/project-new.php?k="+enc_key+"&edprjid="+projectid;
});

$(document).on("click", ".removeprjinfo", function(){
    var projectid = $(this).closest("tr").find("td.prjid_td").text();
    var companyid = $(this).closest("tr").find("td.prjcmpid_td").text();
    var subcompanyid = $(this).closest("tr").find("td.prjsbcmpid_td").text();
    var qstring = loginid+"|"+loginroleid+"|"+companyid+"|"+subcompanyid+"|"+projectid+"|"+loginrolename;
    var enc_qstring = url_btoa(qstring);
    var key = qstring + "|" + enc_qstring;
    var enc_key = url_btoa(key);
    removeprojectdetails_frommaster('hidden_rooturl','hidden_companyid','hidden_subcompanyid',projectid,'hidden_loginname');
});


// $(document).on("click", ".subprjname_td", function(){
//     var projectid = $(this).closest("tr").find("td.prjid_td").text();
//     var companyid = $(this).closest("tr").find("td.prjcmpid_td").text();
//     var subcompanyid = $(this).closest("tr").find("td.prjsbcmpid_td").text();
    
// });
</script>
