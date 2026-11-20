<?php
include("../connection.php");
include("../modulesCell/functions/module_functions.php");
include("functions/login_functions.php");
$key = $_GET['k'];
$key_decoded = base64url_decode_mdl($key);
$key_parts = explode("|", $key_decoded);
$checkkey = base64url_decode_mdl($key_parts[count($key_parts) - 1]);

$actkey = "";
for ($i = 0; $i < count($key_parts) - 1; $i++) {
	$actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
}
if ($actkey != $checkkey) {
	echo "<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
	die;
}
$loginid = $key_parts[0];
$loginroleid = $key_parts[1];
$companyid = $key_parts[2];
$subcompanyid = $key_parts[3];
$projectid = $key_parts[4];
$logintype = $key_parts[5];
$newkey = "x|x|" . $key_parts[2] . "|" . $key_parts[3] . "|" . $key_parts[4] . "|x";
$encodednewkey = base64url_encode_mdl($newkey);
$keycompany = base64url_encode_mdl($newkey . "|" . $encodednewkey);

$logininfo = loginuserinfo($authconn, $loginid, "loginid", $loginid);
$loginname = "Chatur Singh";
$loginname = "";
$loginrolename = "";
if (count($logininfo) > 0) {
	foreach ($logininfo as $lgkey) {
		$loginroleid = $lgkey['loginroleid'];
		$loginname = $lgkey['loginname'];
		$loginrolename = $lgkey['loginrolename'];
		$logintype = $lgkey['loginrolename'];
	}

}

$projectname = "";
$prjinfo = getprojectdetailsmodules($conn, $companyid, $subcompanyid, "projectid", $projectid);
if (count($prjinfo) > 0) {
	$projectname = $prjinfo[0]['projectname'];
}
$protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
$url = basename($_SERVER["PHP_SELF"]);
$rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
$baseurl = $rooturl . str_replace($url, "", $_SERVER["PHP_SELF"]);
if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "192.168.1.65") {
	$prefix = $rooturl . "/krf_amanaccounting/";
} else {
	$prefix = $rooturl . "/";
}
$rooturl = $prefix;
?>
<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<title>Aman Accounting Management System </title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/iziToast.css" type="text/css" media="all">
	<link rel="stylesheet" href="css/font-awesome.css" type="text/css" media="all" />
	<link rel="stylesheet" href="css/custom.css" type="text/css" media="all" />

	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.20/css/jquery.dataTables.css">
	<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css">
	<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.css" rel="stylesheet">
	<style>
		/* Iframe detection styles */
		.is-iframe .navbar,
		.is-iframe .companyheading {
			display: none !important;
		}
	</style>
	<script>
		if (window.self !== window.top) {
			document.documentElement.classList.add('is-iframe');
		}
	</script>
</head>

<body class="shade6111" style="background-color: #f2f2ed;" data-logintype="<?php echo $logintype; ?>"
	data-loginid="<?php echo $loginid; ?>" data-loginname="<?php echo $loginname; ?>" data-key='<?php echo $key; ?>'
	data-loginroleid='<?php echo $loginroleid; ?>' data-companyid='<?php echo $companyid; ?>'
	data-subcompanyid='<?php echo $subcompanyid; ?>' data-projectid='<?php echo $projectid; ?>'
	data-baseurl='<?php echo $baseurl; ?>' data-routurl='<?php echo $rooturl; ?>'>
	<input type="hidden" id="hidden_baseurl" value="<?php echo $baseurl; ?>" />
	<input type="hidden" id="hidden_rooturl" value="<?php echo $rooturl; ?>" />
	<input type="hidden" id="hidden_companyid" value="<?php echo $companyid; ?>" />
	<input type="hidden" id="hidden_subcompanyid" value="<?php echo $subcompanyid; ?>" />
	<input type="hidden" id="hidden_projectid" value="<?php echo $projectid; ?>" />
	<input type="hidden" id="hidden_loginid" value="<?php echo $loginid; ?>" />
	<input type="hidden" id="hidden_loginroleid" value="<?php echo $loginroleid; ?>" />
	<input type="hidden" id="hidden_logintype" value="<?php echo $logintype; ?>" />
	<input type="hidden" id="hidden_loginname" value="<?php echo $loginname; ?>" />
	<input type="hidden" id="hidden_key" value="<?php echo $key; ?>" />
	<nav class="navbar navbar-expand-lg navbar-light sticky-top" style="background-color:#001B48; color: white;">
		<a class="navbar-brand text-white"><?php echo $projectname; ?></a>
		<button class="navbar-toggler  text-white" type="button" data-toggle="collapse" data-target="#navbarNav"
			aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon" style="background-color: white;"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarNav">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item active text-white">
					<!-- <a class="nav-link text-white home_nav" href="<?php echo $homeurl; ?>">Home <span class="sr-only">(current)</span></a> -->
				</li>
			</ul>

			<!-- Profile Dropdown -->
			<ul class="navbar-nav">
				<li class="nav-item text-white mr-3">
					<?php echo $loginname; ?>
				</li>
				<li class="nav-item">
					<button class="btn btn-outline-light mr-auto btn-sm" onclick="logoutUser()">Logout</button>
				</li>
			</ul>
		</div>
	</nav>
	<?php
	if ($dirs[2] != "indexprojects.php") {
		?>
		<div class="companyheading row py-1">
			<div class="col-md-12 uppercase text-center bold" style="font-size: 1.75rem;">
				<h2 class="uppercase bold d-none" id="cmpnamediv"><?php echo $companyname; ?></h2>
				<h2 class="uppercase bold  my-1"><?php echo ($projectname ? "" . $projectname : ""); ?></h2>
			</div>
		</div>
	<?php
	}
	?>
	<div class="container-fluid">