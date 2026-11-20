<?php
include('header.php');
$roleidarray = [0, 1, 99];
if (in_array($loginroleid, $roleidarray)) {
    $dispdiv = "";
} else {
    $dispdiv = "d-none";
}

?>
<div class="container">
	<div class="row mt-4 registredprojectclass" style="display:none;">
		<div class="col-md-12  text-center">
			<h2>Authorized Project(s)</h2>
		</div>
	</div>
	<div class="row mt-4 <?php echo $dispdiv; ?>">
		<div class="col-md-12 text-center">
			<div id="quickregistrationview"></div>
		</div>
	</div>
	<div class="row <?php echo $loginroleid==0?'':'d-none'; ?>">
		<div class="col-md-12 p-4">
			<?php 
			$paneldetails = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"groupid",0);
			?>
			<details>
				<summary>Web Administrator</summary>
				<div class="p-4">
					<a class="btn btn-deepblue p5 mt-2 d-block" href="adminsuper/modules_grouping_cmp.php?k=<?php echo $key; ?>&subaction=" style="width:100%; height: auto; white-space: normal;">
						<h5 class="uppercase">Assign Modules to Groups for a Project (Company)</h5>
					</a>
					<a class="btn btn-deepblue p5 mt-2 d-block" href="adminsuper/modules_grouping.php?k=<?php echo $key; ?>&subaction=" style="width:100%; height: auto; white-space: normal;">
						<h5 class="uppercase">MODULES MASTERS DETAILS</h5>
					</a>
					<a class="btn btn-deepblue p5 mt-2 d-block" href="adminsuper/modules_passwordprotected.php?k=<?php echo $key; ?>&subaction=" style="width:100%; height: auto; white-space: normal;">
						<h5 class="uppercase">AUTHORISE PERSON TO WORK IN A MODULE</h5>
					</a>

					<a class="btn btn-deepblue p5 mt-2 d-block" href="companyregister/companyapproval/approvalpanel.php?k=<?php echo $key; ?>&subaction=" style="width:100%; height: auto; white-space: normal;">
						<h5 class="uppercase">PENDING APPROVAL OF NEW COMPANY </h5>
					</a>
					<a class="btn btn-deepblue p5 mt-2 d-block" href="registration_company/companyregistration_login.php?k=<?php echo $key; ?>&subaction=" style="width:100%; height: auto; white-space: normal;">
						<h5 class="uppercase">NEW COMPANY REGISTRATION</h5>
					</a>

					<a class="btn btn-deepblue p5 mt-2 d-block" href="adminsuper/agency_masterlist.php?k=<?php echo $key; ?>&subaction=" style="width:100%; height: auto; white-space: normal;">
						<h5 class="uppercase">NEW AGENCY REGISTRATION</h5>
					</a>

					<a class="btn btn-deepblue p5 mt-2 d-block" href="module_client/approve_clientmodule.php?k=<?php echo $key; ?>&subaction=" style="width:100%; height: auto; white-space: normal;">
						<h5 class="uppercase">PENDING APPROVAL OF  MODULES GROUP</h5>
					</a>
				</div>
			</details>
		</div>
	</div>
	<div class="row mt-4 registredprojectclass" style="display:none;">
		<div class="col-md-12">
			<div class="table-responsive">
				<table class="table table-bordered table-light table-striped" id="authorizedprjwithcompany">
					<thead>
						<tr>
							<th style="width:50px; text-align:center;">#</th>
							<th>Project Name</th>
							<th>Company Name</th>
							<th>Sub-Company Name</th>
							<th style="display: none;">Project Id</th>
							<th style="display: none;">Company Id</th>
							<th style="display: none;">Sub-Company Id</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
	</div>
	
</div>
<?php
include('footer.php');
?>
<script type="text/javascript">
var loginid = "<?php echo $loginid; ?>";
var companyid = $("#hidden_companyid").val();
var subcompanyid = $("#hidden_subcompanyid").val();
var projectid = $("#hidden_projectid").val();
var rooturl = $("#hidden_rooturl").val();
var quickhtml = "";
authorizedprojects_homehtmlview('hidden_rooturl',loginid,function(res){
	if(res.length>0){
		for(var i=0; i<res.length; i++){
			$("#authorizedprjwithcompany tbody").append('<tr><td><input type="radio" class="chooseprjradio" name="chooseprjradio" /></td><td>'+res[i].projectname+'</td><td>'+res[i].companyname+'</td><td>'+res[i].subcompanyname+'</td><td class="pprj_td" style="display: none;">'+res[i].projectid+'</td><td class="ccmp_td" style="display: none;">'+res[i].companyid+'</td><td class="sccmp_td" style="display: none;">'+res[i].subcompanyid+'</td></tr>');
		}
		if(res.length==1){
			$("input[name='chooseprjradio']").trigger("click");
		}else if(res.length>1){
			$(".registredprojectclass").show();
		}
	}else{
		quickhtml += "<p class=''>You don't currently have a registered company. Please complete the registration process first.</p><br/>";
	}
	quickhtml += "<a class='btn btn-deepblue' href='registration_company/companyregistration_login.php?k=<?php echo $key; ?>'>Add New Company</a>";
	$("#quickregistrationview").html(quickhtml);
});

$(document).on("click", "input[name='chooseprjradio']", function(){
	var companyid = $(this).closest("tr").find("td.ccmp_td").text();
	var subcompanyid = $(this).closest("tr").find("td.sccmp_td").text();
	var projectid = $(this).closest("tr").find("td.pprj_td").text();
	let loginroleid = $("body").data("loginroleid");
	let loginid = $("body").data("loginid");
	let loginrolename = $("body").data("logintype");
	gotodashbord(rooturl,companyid,subcompanyid,projectid,loginid,loginroleid,loginrolename);
});

function gotodashbord(rooturl,companyid,subcompanyid,projectid,loginid,loginroleid,loginrolename){
	var qstring = loginid+"|"+loginroleid+"|"+companyid+"|"+subcompanyid+"|"+projectid+"|"+loginrolename;
    var enc_qstring = url_btoa(qstring);
    var key = qstring + "|" + enc_qstring;
    var enc_key = url_btoa(key);
    window.location.href=rooturl+"home.php?k="+enc_key;
}
</script>