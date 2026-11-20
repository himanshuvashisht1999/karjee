<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Prevent browser caching to disable history back button access after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!isset($_SESSION['sessionid']) || $_SESSION['sessionid'] !== session_id()) {
    $protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
    if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "192.168.1.65") {
        $prefix = $rooturl . "/krf_amanaccounting/";
    } else {
        $prefix = $rooturl . "/";
    }
    $requested_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    echo "<script>
        try {
            if (" . json_encode($requested_url) . " && !" . json_encode($requested_url) . ".includes('loginCell') && !" . json_encode($requested_url) . ".includes('logout')) {
                localStorage.setItem('last_active_module_url', " . json_encode($requested_url) . ");
            }
        } catch(e){}
        top.location.href = '" . $prefix . "loginCell/index.php';
    </script>";
    exit;
}
include('connection.php');
include('functions/function_master.php');
include('functions/function_language.php');
include('functions/hrm_FunctionMaster.php');
include('functions/function_savetodb_recBook.php');
include('loginCell/functions/login_functions.php');
include('modulesCell/functions/module_functions.php');

//include('automation/automation_functionmaster.php');
$key = $_GET['k'];
$key_decoded = base64url_decode($key);
$key_parts = explode("|", $key_decoded);
$checkkey = base64url_decode($key_parts[count($key_parts) - 1]);
$actkey = "";
for ($i = 0; $i < count($key_parts) - 1; $i++) {
	$actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
}
if ($actkey != $checkkey) {
	echo "<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
	die;
}
$loginname = "";
$loginid = $key_parts[0];
$loginroleid = $key_parts[1];
$companyid = $key_parts[2];
$subcompanyid = $key_parts[3];
$projectid = $key_parts[4];
$logintype = $key_parts[5];
$newkey = "x|x|" . $key_parts[2] . "|" . $key_parts[3] . "|" . $key_parts[4] . "|x";
$encodednewkey = base64url_encode($newkey);
$keycompany = base64url_encode($newkey . "|" . $encodednewkey);

$finyeartype = getfinancialyeartype($mconn, $companyid);
$authprjcount = allauthorizedprojectid($conn, $loginid);

$cmpinfo = getcompanydetails($mconn, "companyid", $companyid);

$companyname = "";
if (count($cmpinfo) > 0) {
	foreach ($cmpinfo as $cmphkey) {
		$companyname = $cmphkey['company_name'];
	}
}

$subcmpinfo = getsubcompanydetails($mconn, $companyid, $subcompanyid, "subcompanyid", $subcompanyid);
$subcompanyname = "";
if (count($subcmpinfo) > 0) {
	foreach ($subcmpinfo as $subcmphkey) {
		$subcompanyname = $subcmphkey['subcompanyname'];
	}
}

//projectinfo
$prj = getprojectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
$projectname = "";
if (count($prj) > 0) {
	foreach ($prj as $pjkey) {
		$projectname = $pjkey['projectname'];
		$taxtype = $pjkey['taxtype'];
		$finyrtype = $pjkey['finyr'];
		$invformat = $pjkey['invformat'];
		$proformaformat = $pjkey['proformaformat'];
		$currencyid = $pjkey['currencyid'];
		$creditformat = $pjkey['creditformat'];
		$projectemail = $pjkey['emailid'];
	}
}

$logininfo = loginuserdetailsfrommaster($authconn, $loginid, "loginid", $loginid);
$loginname = "";
$loginrolename = "";
$logintype = "";
$loginroleid = "";
if (count($logininfo) > 0) {
	foreach ($logininfo as $lgkey) {
		$loginroleid = $lgkey['loginroleid'];
		$loginname = $lgkey['loginname'];
		$loginrolename = $lgkey['loginrolename'];
		$logintype = $lgkey['loginrolename'];
	}
	if ($loginroleid !== "") {
		$_SESSION['loginroleid'] = intval($loginroleid);
	}
}
$prefix = "";
$referrer = "";
$countryid = "";
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

$page = $_SERVER["PHP_SELF"];
$dirs = explode("/", $page);
$levels = count($dirs) - 2;
if ($dirs[2] == "indexprojects.php") {
	$homeurl = $rooturl . "indexprojects.php?k=" . $key;
} else {
	$homeurl = $rooturl . "admin_dashboard.php?k=" . $key;
}

if ($companyid != "" && $subcompanyid != "" && $projectid != "" && $projectid != "undefined") {
	insertdefaultdebitpurposecodes($conn, $companyid, $subcompanyid, $projectid);
	insertdefaultcreditpurposecodes($conn, $companyid, $subcompanyid, $projectid);
	// insertdefaultdebitpurposecodesinPNL($conn,$companyid,$subcompanyid,$projectid);
}
updatepaytoidinbook($conn);
updateInternalTransferAccountData($conn);
$ssup = updateEmployeeAdvancePaymentData($hrmconn, $conn);
?>
<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<title>Karjee Farms</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="<?php echo $prefix; ?>css/fontraleway.css">
	<link rel="stylesheet" href="<?php echo $prefix; ?>css/dropdown.css">
	<link rel="stylesheet" href="<?php echo $prefix; ?>css/excelimport/hipaformstyle.css" />
	<link rel="stylesheet" href="<?php echo $prefix; ?>css/color-palets/color-palets.css" />
	<link rel="stylesheet" href="<?php echo $prefix; ?>css/cssloader.css">
	<link rel="stylesheet" href="<?php echo $prefix; ?>css/scroll.css">
	<link rel="stylesheet" href="<?php echo $prefix; ?>css/iziToast.css">
	<link rel="stylesheet" href="<?php echo $prefix; ?>css/required.css">
	<link rel="stylesheet" href="<?php echo $prefix; ?>css/customcss.css?v=<?php echo time(); ?>">
	<link rel="stylesheet" href="<?php echo $prefix; ?>assets/css/custom_tables.css?v=<?php echo time(); ?>">

	<link href="<?php echo $prefix; ?>css/icps/theme-gunmetal.css" rel="stylesheet" type="text/css" media="all" />
	<link href="<?php echo $prefix; ?>css/icps/details.css" rel="stylesheet" type="text/css" media="all" />
	<link href="<?php echo $prefix; ?>css/font-awesome.css" rel="stylesheet" type="text/css" media="all" />
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
	<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css">
	<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.20/css/jquery.dataTables.css">
	<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css">
	<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<!-- <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script> -->
	<?php $is_payroll = (stripos($_SERVER['REQUEST_URI'], 'payroll') !== false); ?>
	<style type="text/css">
		<?php if (!$is_payroll): ?>
			/* Global Font Size Reduction - Optimized for Non-Payroll Modules */
			:root {
				font-size: 14px;
				/* Base font size reduction */
			}

			body {
				font-size: 0.875rem !important;
			}

			.form-control {
				font-size: 0.9rem !important;
				padding: 0.35rem 0.7rem !important;
				line-height: 1.4 !important;
			}

			table td,
			table th {
				font-size: 0.85rem !important;
				padding: 4px 6px !important;
			}

			.lbl-text {
				font-size: 1rem !important;
			}

			.navbar-premium .nav-link {
				font-size: 0.85rem !important;
			}

			.navbar-premium .navbar-brand {
				font-size: 1.25rem !important;
			}

			.company-header-strip h2 {
				font-size: 1.25rem !important;
			}

			.btn {
				font-size: 0.85rem !important;
				padding: 0.3rem 0.75rem !important;
			}

			.required {
				font-size: 1.2rem !important;
			}

		<?php endif; ?>

		:root {
			--theme-primary: #667eea;
			--theme-secondary: #764ba2;
			--theme-gradient: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-secondary) 100%);
			--theme-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
		}

		body {
			font-family: 'Inter', 'Segoe UI', sans-serif;
			background-color: #f8fafc;
			position: relative;
			<?php if (!(isset($_GET['iframe']) && $_GET['iframe'] == 'true')): ?>
				padding-bottom: 48px;
			<?php endif; ?>
		}

		html {
			scroll-padding-top: 85px;
		}

		.navbar-premium {
			background: var(--theme-gradient) !important;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
			padding: 0.75rem 1.5rem;
			border: none;
			margin-bottom: 12px;
		}

		.navbar-premium .navbar-brand {
			font-weight: 800;
			letter-spacing: -0.5px;
			font-size: 1.4rem;
			text-transform: uppercase;
			color: #fff !important;
		}

		.navbar-premium .nav-link {
			color: #fff !important;
			font-weight: 600;
			font-size: 0.95rem;
			transition: all 0.3s ease;
			padding: 0.5rem 1rem !important;
			border-radius: 8px;
		}

		.navbar-premium .nav-link:hover {
			background: rgba(255, 255, 255, 0.15);
			transform: translateY(-1px);
		}

		.navbar-premium .dropdown-menu {
			border: none;
			box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
			border-radius: 12px;
			padding: 0.5rem;
		}

		.navbar-premium .dropdown-item {
			font-weight: 500;
			border-radius: 8px;
			padding: 0.6rem 1rem;
			transition: all 0.2s;
			color: #4a5568 !important;
		}

		.navbar-premium .dropdown-item:hover {
			background-color: #f1f5f9;
			color: #667eea !important;
		}

		.user-avatar-btn {
			background: rgba(255, 255, 255, 0.2);
			border-radius: 50px;
			padding: 4px 15px 4px 8px !important;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.company-header-strip {
			background: #fff;
			border-bottom: 2px solid #e2e8f0;
			padding: 10px 0;
			margin-bottom: 15px;
		}

		.company-header-strip h2 {
			color: #667eea;
			margin: 0;
			font-size: 1.5rem;
			font-weight: 700;
		}

		/* Hide Back and Refresh buttons when in iframe */
		<?php if (isset($_GET['iframe']) && $_GET['iframe'] == 'true'): ?>
			.container1>.row.mb-3,
			.container>.row.mb-3,
			a.btn:has(.fa-arrow-left),
			button.btn:has(.fa-sync),
			button.btn:has(.fa-refresh),
			a[href*="indexdisplay.php"],
			button[onclick*="location.reload()"] {
				display: none !important;
			}

		<?php endif; ?>

		.required {
			color: red;
			font-size: 25px;
			font-weight: 900;
		}

		.lbl-text {
			color: #581845;
			font-size: 1.2em;
			font-weight: bolder;
		}

		.text-responsive {
			font-size: calc(100% + 1vw + 1vh);
		}

		input[type="radio"] {
			-ms-transform: scale(2);
			-webkit-transform: scale(2);
			transform: scale(2);
			margin: 0px 8px;
		}

		.btn-attach input {
			flex: 1;
		}

		.hide {
			display: none;
		}

		input[type="checkbox"] {
			-ms-transform: scale(2);
			-webkit-transform: scale(2);
			transform: scale(2);
			margin: 0px 8px;
		}

		/* Hide spinner arrows in Chrome, Safari */
		input[type="number"]::-webkit-inner-spin-button,
		input[type="number"]::-webkit-outer-spin-button {
			-webkit-appearance: none;
			margin: 0;
		}

		/* Hide spinner arrows in Firefox */
		input[type="number"] {
			-moz-appearance: textfield;
		}

		label {
			color: #30212D;
			font-weight: 700;
		}

		.no-label {
			/*color: #30212D;*/
			font-weight: 400 !important;
		}


		.form-control {
			display: block;
			width: 100%;
			padding: 0.375rem 0.75rem;
			font-size: 1.1rem;
			line-height: 1.5;
			color: #0c0c0c;
			background-color: #fff;
			background-clip: padding-box;
			border: 1px solid #ced4da;
			border-radius: 0.25rem;
			transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
		}

		table {
			width: 100% !important;
			border-collapse: collapse;
			color: #000;
		}

		tbody th,
		td {
			padding: 4px;
			border: 1px solid #333131;
			text-align: left;
			font-size: 0.8em;
			font-family: 'Poppins', sans-serif !important;
			/* Use Poppins (fallback to Verdana if unavailable) */
		}

		thead th,
		td {
			padding: 4px;
			border: 1px solid #333131;
			font-size: 0.8em;
			text-align: left;
			font-family: 'Poppins', sans-serif !important;
			/* Use Poppins (fallback to Verdana if unavailable) */
		}

		tfoot th,
		td {
			padding: 4px;
			border: 1px solid #333131;
			text-align: left;
			font-size: 0.8em;
			font-family: 'Poppins', Verdana, sans-serif !important;
			/* Use Poppins (fallback to Verdana if unavailable) */
		}

		.stripedalt>tbody>tr:nth-of-type(odd) {
			background-color: #c9c0c0c7;
		}

		thead {
			border: 1px solid #333131;
			background-color: #B1A7A7 !important;
			color: #000;
		}

		tr.drag-row {
			cursor: move;
		}

		tbody tr:hover {
			background-color: lavender;
		}

		.vertical-text {
			writing-mode: vertical-rl;
			text-orientation: mixed;
			transform: rotate(180deg);
			text-align: start;
		}

		@media screen and (max-width: 600px) {
			.table-mobile {
				border: 0;
				color: #000;
			}

			.table-mobile caption {
				font-size: 1.3em;
				font-family: 'Poppins', Verdana, sans-serif !important;
				/* Use Poppins (fallback to Verdana if unavailable) */
			}


			.table-mobile thead.headings tr {
				border: none !important;
				margin-bottom: 0 !important;
				display: table-row !important;
				/* to prevent mobile block display */
			}

			.table-mobile thead.headings td,
			.table-mobile thead.headings th {
				border: none !important;
				display: table-cell !important;
				text-align: left !important;
				font-size: 0.9em;
				font-weight: bold;
				background: #d6e6fd;
				/* optional to preserve style */
			}

			.table-mobile thead.headings td::before,
			.table-mobile thead.headings th::before {
				content: none !important;
			}

			.table-mobile thead:not(.headings) {
				border: none;
				clip: rect(0 0 0 0);
				height: 1px;
				margin: -1px;
				overflow: hidden;
				padding: 0;
				position: absolute;
				width: 1px;
			}

			.table-mobile tr {
				border-bottom: 1px solid #333131;
				display: block;
				margin-bottom: 0.4em;
			}

			.table-mobile td:last-child {
				border-bottom: 0;
			}


			.table-mobile td {
				display: flex;
				justify-content: space-between;
				align-items: center;
				text-align: right;
				/* This keeps the value on the right */
				border-bottom: 1px solid #807d7d;
				font-size: .8em;
				font-family: 'Poppins', Verdana, sans-serif !important;
				padding: 4px 8px;
			}

			.table-mobile td::before {
				content: attr(data-label);
				font-weight: bold;
				text-transform: uppercase;
				margin-right: auto;
				text-align: left;
				flex: 1;
			}

			.table-mobile td span {
				flex: 1;
				text-align: right;
			}

			.table-mobile .mobile-hide {
				display: none !important;
			}



		/* Robust iframe detection styles */
		.is-iframe .navbar-premium,
		.is-iframe .company-header-strip {
			display: none !important;
		}

		.is-iframe body {
			background-color: #f2f2ed !important;
		}

		/* Sticky Headers for Tables and Strips */
		thead th, .strips-header {
			position: sticky !important;
			z-index: 1000 !important;
			background-color: #fff;
			box-shadow: inset 0 -1px 0 #e2e8f0;
		}

		/* Adjust top position based on navbar visibility */
		thead th, .strips-header {
			top: 70px !important; /* Height of navbar-premium */
		}

		/* If in iframe, sticky should be at the very top */
		.is-iframe thead th, .is-iframe .strips-header {
			top: 0px !important;
		}

		/* Global styling for Search Inputs */
		input[type="search"],
		.dataTables_filter input,
		.search-wrapper input,
		input[placeholder*='Search' i],
		input[placeholder*='search' i] {
			background-color: #f8f9fa !important;
			border: 1px solid #ced4da !important;
			border-radius: 6px !important;
			padding: 8px 12px !important;
			transition: all 0.2s ease-in-out !important;
			font-size: 0.9rem !important;
		}

		input[type="search"]:focus,
		.dataTables_filter input:focus,
		.search-wrapper input:focus,
		input[placeholder*='Search' i]:focus {
			background-color: #fff !important;
			border-color: #80bdff !important;
			outline: 0 !important;
			box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
		}

		.strips-header {
			background-color: #e9ecef !important;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
		}
	</style>
	<script>
		// Detect if page is inside an iframe
		if (window.self !== window.top) {
			document.documentElement.classList.add('is-iframe');
		}
	</script>
	<link rel="stylesheet" href="<?php echo $prefix; ?>assets/css/custom_tables.css">
</head>

<body class="shade6111" style="background-color: #f2f2ed;" data-key="<?php echo $key; ?>"
	data-routurl="<?php echo $rooturl; ?>" data-companyid="<?php echo $companyid; ?>"
	data-subcompanyid="<?php echo $subcompanyid; ?>" data-projectid="<?php echo $projectid; ?>"
	data-projectname="<?php echo $projectname; ?>" data-loginroleid="<?php echo $loginroleid; ?>"
	data-logintype="<?php echo $logintype; ?>" data-loginid="<?php echo $loginid; ?>"
	data-loginname="<?php echo $loginname; ?>">
	<input type="hidden" id="hidden_baseurl" value="<?php echo $baseurl; ?>" />
	<input type="hidden" id="hidden_rooturl" value="<?php echo $rooturl; ?>" />
	<input type="hidden" id="hidden_routurl" value="<?php echo $rooturl; ?>" />
	<input type="hidden" id="hidden_countryid" value="<?php echo $countryid; ?>" />
	<input type="hidden" id="hidden_companyid" value="<?php echo $companyid; ?>" />
	<input type="hidden" id="hidden_companyname" value="<?php echo $companyname; ?>" />
	<input type="hidden" id="hidden_finyeartype" value="<?php echo $finyeartype; ?>" />
	<input type="hidden" id="hidden_subcompanyid" value="<?php echo $subcompanyid; ?>" />
	<input type="hidden" id="hidden_subcompanyname" value="<?php echo $subcompanyname; ?>" />
	<input type="hidden" id="hidden_projectid" value="<?php echo $projectid; ?>" />
	<input type="hidden" id="hidden_projectname" value="<?php echo $projectname; ?>" />
	<input type="hidden" id="hidden_loginid" value="<?php echo $loginid; ?>" />
	<input type="hidden" id="hidden_loginroleid" value="<?php echo $loginroleid; ?>" />
	<input type="hidden" id="hidden_logintype" value="<?php echo $logintype; ?>" />
	<input type="hidden" id="hidden_loginname" value="<?php echo $loginname; ?>" />
	<input type="hidden" id="hidden_key" value="<?php echo $key; ?>" />
	<?php
	$hide_header_final = (isset($hide_header) && $hide_header) || (isset($_GET['iframe']) && $_GET['iframe'] == 'true');
	if (!$hide_header_final):
		?>
		<nav class="navbar navbar-expand-lg navbar-dark navbar-premium sticky-top">
			<div class="d-flex align-items-center">
				<button class="btn btn-link text-white d-lg-none mr-2 p-1" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle Menu" id="btnTopMobileSidebarToggle" style="text-decoration:none;">
					<i class="fa fa-bars" style="font-size:1.35rem;"></i>
				</button>
				<a class="navbar-brand d-flex align-items-center" href="<?php echo $$homeurl; ?>">
					<img src="<?php echo $prefix; ?>img/logo/logo.png" alt="Karjee Farms"
						style="height: 45px; width: 45px; object-fit: contain; margin-right: 12px; background: white; border-radius: 50%; padding: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
					<span class="font-weight-bold">Karjee Farms</span>
				</a>
			</div>
			<button class="btn navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
				aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarNav">
				<!-- Profile Dropdown -->
				<ul class="navbar-nav ml-auto">
					<!-- Language Dropdown -->
					<li class="nav-item dropdown mr-2">
						<a class="nav-link dropdown-toggle user-avatar-btn" href="#" id="langDropdown" role="button"
							data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fa fa-language" style="font-size:20px;"></i>
							<span><?php echo (getCurrentLanguage() == 'hi') ? 'हिंदी' : 'English'; ?></span>
						</a>
						<div class="dropdown-menu dropdown-menu-right" aria-labelledby="langDropdown">
							<a class="dropdown-item <?php echo (getCurrentLanguage() == 'en') ? 'active bg-light' : ''; ?>"
								href="?<?php echo http_build_query(array_merge($_GET, ['lang' => 'en'])); ?>">
								English
							</a>
							<a class="dropdown-item <?php echo (getCurrentLanguage() == 'hi') ? 'active bg-light' : ''; ?>"
								href="?<?php echo http_build_query(array_merge($_GET, ['lang' => 'hi'])); ?>">
								हिंदी (Hindi)
							</a>
						</div>
					</li>
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle user-avatar-btn" href="#" id="profileDropdown" role="button"
							data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fa fa-user-circle" style="font-size:24px;"></i>
							<span><?php echo $loginname; ?></span>
						</a>
						<div class="dropdown-menu dropdown-menu-right" aria-labelledby="profileDropdown">
							<a class="dropdown-item" href="#"><i class="fa fa-user-edit mr-2"></i> Profile</a>
							<div class="dropdown-divider"></div>
							<a class="dropdown-item text-danger" href="#" onclick="logoutUser()"><i
									class="fa fa-sign-out-alt mr-2"></i> Logout</a>
						</div>
					</li>
				</ul>
			</div>
		</nav>
		<?php
		$hide_heading_final = (isset($hide_heading) && $hide_heading);
		if ($dirs[2] != "indexprojects.php" && !$hide_heading_final) {
			?>
			<div class="company-header-strip shadow-sm">
				<div class="container-fluid">
					<div class="row align-items-center">
						<div class="col-md-12 text-center">
							<h2 class="uppercase bold d-none" id="cmpnamediv"><?php echo $companyname; ?></h2>
							<h2 class="uppercase bold my-0 text-primary">
								<?php echo ($projectname ? "" . $projectname : "DASHBOARD"); ?>
							</h2>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
		?>
	<?php endif; ?>
	<div class="container-fluid1">