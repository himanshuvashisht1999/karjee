<?php
require('../connection.php');
require('functions/login_functions.php');
// include("/functions/module_functions.php");
$key=$_GET['k'];
$key_decoded = base64url_decode_lg($key);
$key_parts = explode("|",$key_decoded);
$checkkey = base64url_decode_lg($key_parts[count($key_parts)-1]);
$actkey = "";
for($i=0;$i<count($key_parts)-1 ;$i++){
  $actkey = $actkey?$actkey."|".$key_parts[$i]:$key_parts[$i];
}
if($actkey!=$checkkey){
  echo"<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
  die;
}
$loginid = $key_parts[0];
$loginroleid = $key_parts[1];
$companyid = $key_parts[2];
$subcompanyid = $key_parts[3];
$projectid = $key_parts[4];
$logintype = $key_parts[5];
$newkey = "x|x|".$key_parts[2]."|".$key_parts[3]."|".$key_parts[4]."|x";
$encodednewkey = base64url_encode_lg($newkey);
$keycompany=base64url_encode_lg($newkey."|".$encodednewkey);
$logininfo = loginuserinfo($authconn,$loginid,"loginid",$loginid);
$loginname ="";
$loginrolename ="";
if(count($logininfo)>0){
  foreach ($logininfo as $lgkey) {
    $loginroleid = $lgkey['loginroleid']; 
    $loginname = $lgkey['loginname'];
    $loginrolename = $lgkey['loginrolename'];
    $logintype = $lgkey['loginrolename'];
  }

}

$protocol=$_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
$url = basename( $_SERVER["PHP_SELF"]);
$rooturl = $protocol.'://'.$_SERVER['HTTP_HOST'];
$baseurl= $rooturl.str_replace($url, "", $_SERVER["PHP_SELF"]);
if($_SERVER['HTTP_HOST']=="localhost"||$_SERVER['HTTP_HOST']=="192.168.1.65"){
	$prefix=$rooturl."/krf_amanaccounting/";
}else{
	$prefix=$rooturl."/";
}
$rooturl =$prefix;
$roleidarray = [0, 1, 99];
if (in_array($loginroleid, $roleidarray)) {
    $dispdiv = "";
} else {
    $dispdiv = "d-none";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Login::Aman Accounting System</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="css/main.css">
	<link rel="stylesheet" type="text/css" href="css/iziToast.css">
</head>
<body data-key='<?php echo $key; ?>' data-loginid='<?php echo $loginid; ?>' data-loginroleid='<?php echo $loginroleid; ?>' data-companyid='<?php echo $companyid; ?>' data-subcompanyid='<?php echo $subcompanyid; ?>' data-projectid='<?php echo $projectid; ?>' data-routurl='<?php echo $rooturl; ?>' data-baseurl='<?php echo $baseurl; ?>'>
<div class="container">
	<div class="row mt-4 registredprojectclass" style="display:none;">
		<div class="col-md-12  text-center">
			<h2>Authorized Project(s)</h2>
		</div>
	</div>
	<div class="row d-none mt-4 <?php echo $dispdiv; ?>">
		<div class="col-md-12 text-center">
			<div id="quickregistrationview"></div>
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
</body>
<script type="text/javascript" src="vendor/jquery/jquery-3.2.1.min.js"></script>
<script type="text/javascript" src="vendor/bootstrap/js/popper.js"></script>
<script type="text/javascript" src="vendor/bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="vendor/tilt/tilt.jquery.min.js"></script>
<script type="text/javascript" src="js/iziToast.js"></script>
<script type="text/javascript" src="js/main.js"></script>
<script type="text/javascript" src="js/login.js"></script>
<script type="text/javascript" src="js/actioncontrol.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* Put this in your CSS file */
.swal2-input {
  border: 1px solid #ccc !important;
  border-radius: 4px !important;
  padding: 6px !important;
  box-shadow: none !important;
}
.swal2-input:focus {
  border-color: #3b82f6 !important; /* blue highlight on focus */
  outline: none !important;
}

</style>
<script type="text/javascript">
var loginid = "<?php echo $loginid; ?>";
var companyid = "<?php echo $companyid; ?>";
var localfolder = "krf_amanaccounting";
var routurl = $("body").data("routurl");
var baseurl = $("body").data("baseurl");
var domainname = "<?php echo $_SERVER['HTTP_HOST']; ?>";
var quickhtml = "";
authorizedprojects_homehtmlview(baseurl,loginid,function(res){
	let isthis=0;
	if(res.length>0){
		for(var i=0; i<res.length; i++){
			// if(companyid==res[i].companyid){
				if(res[i].companyfoldername=="" || res[i].companyfoldername=="undefined" || res[i].companyfoldername=="null" || res[i].companyfoldername==null){
					$("#authorizedprjwithcompany tbody").append(
					  '<tr data-companyfoldername="' + res[i].companyfoldername + '" class="d-none">' +
					    '<td><input type="radio" class="chooseprjradio" name="chooseprjradio" disabled /></td>' +
					    '<td>' + res[i].projectname + '</td>' +
					    '<td>' + res[i].companyname + 
					      ' <br/> <span class="badge badge-warning upddoaminname" ' +
					      'onclick="updatedomainname(\'' + res[i].companyfoldername + '\', \'' + res[i].companyid + '\');" ' +
					      'style="cursor:pointer">Update Domain Name</span></td>' +
					    '<td>' + res[i].subcompanyname + '</td>' +
					    '<td class="pprj_td" style="display: none;">' + res[i].projectid + '</td>' +
					    '<td class="ccmp_td" style="display: none;">' + res[i].companyid + '</td>' +
					    '<td class="sccmp_td" style="display: none;">' + res[i].subcompanyid + '</td>' +
					  '</tr>'
					);
				}else{
					if(domainname=="localhost"){
						var companyfoldernameparts = res[i].companyfoldername.split(".");
						var localdomain = companyfoldernameparts[0]+"_"+companyfoldernameparts[1];
						if(localfolder==localdomain){
							isthis++;
							$("#authorizedprjwithcompany tbody").append('<tr data-companyfoldername="'+res[i].companyfoldername+'"><td><input type="radio" class="chooseprjradio" name="chooseprjradio" /></td><td>'+res[i].projectname+'</td><td>'+res[i].companyname+'</td><td>'+res[i].subcompanyname+'</td><td class="pprj_td" style="display: none;">'+res[i].projectid+'</td><td class="ccmp_td" style="display: none;">'+res[i].companyid+'</td><td class="sccmp_td" style="display: none;">'+res[i].subcompanyid+'</td></tr>');
						}
					} else if(res[i].companyfoldername==domainname){
						isthis++;
						$("#authorizedprjwithcompany tbody").append('<tr data-companyfoldername="'+res[i].companyfoldername+'"><td><input type="radio" class="chooseprjradio" name="chooseprjradio" /></td><td>'+res[i].projectname+'</td><td>'+res[i].companyname+'</td><td>'+res[i].subcompanyname+'</td><td class="pprj_td" style="display: none;">'+res[i].projectid+'</td><td class="ccmp_td" style="display: none;">'+res[i].companyid+'</td><td class="sccmp_td" style="display: none;">'+res[i].subcompanyid+'</td></tr>');
					}
				}
			// }
		}
		if(res.length==1 && isthis==1){
			$("input[name='chooseprjradio']").trigger("click");
		}else if(res.length>1 && isthis==1){
			$("input[name='chooseprjradio']").trigger("click");
			$(".registredprojectclass").show();
		}else if(res.length>1 && isthis>=1){
			$(".registredprojectclass").show();
		}else{
			Swal.fire({
			  // title: "Login",
			  text: "Your user ID and password did not match with any company.",
			  icon: "warning",
			  confirmButtonColor: "#3085d6",
			  confirmButtonText: "Back, Relogin Again"
			}).then((result) => {
			  if (result.isConfirmed) {
			    window.location.href="../loginCell/index.php";
			  }
			});
			// quickhtml += "<p class=''>Your user ID and password did not match the selected company.</p><br/>";	
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
	var companyfoldername = $(this).closest("tr").data("companyfoldername");
	let loginroleid = $("body").data("loginroleid");
	let loginid = $("body").data("loginid");
	let loginrolename = $("body").data("logintype");
	if(companyfoldername=="" || companyfoldername==null || companyfoldername=="null" || companyfoldername=="undefined"){
		iziToast.warning({
		    title: 'Caution',
		    message: "Your authorized company is not defined properly. Please contact the web administrator.",
		});
		return false;
	}else{
		gotodashbord(routurl,companyfoldername,companyid,subcompanyid,projectid,loginid,loginroleid,loginrolename);
	}
});

function gotodashbord(routurl,companyfoldername,companyid,subcompanyid,projectid,loginid,loginroleid,loginrolename){
	var qstring = loginid+"|"+loginroleid+"|"+companyid+"|"+subcompanyid+"|"+projectid+"|"+loginrolename;
    var enc_qstring = url_btoa(qstring);
    var key = qstring + "|" + enc_qstring;
    var enc_key = url_btoa(key);
    window.location.href=routurl+"admin_dashboard.php?k="+enc_key;
}

async function updatedomainname(companyfoldername, companyid) {
	const { value: foldername } = await Swal.fire({
		title: "Domain/Sub-domain Name",
		input: "text",
		inputLabel: "Your company domain/sub-domain name",
		inputPlaceholder: "Enter company domain/sub-domain name",
		inputValue: companyfoldername && companyfoldername.trim() !== "" ? companyfoldername : ""
	});
	if (foldername) {
		// alert(foldername);
		updatedomainsubdonainToCompanyMaster(companyid, foldername);
	}
}

function getDomainName(url) {
  const hostname = new URL(url).hostname;
  const parts = hostname.split('.');
  const len = parts.length;
  if (len >= 2) {
    return parts.slice(-2).join('.');
  }
  return hostname;
}
</script>