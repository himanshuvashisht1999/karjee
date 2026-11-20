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
	header("Location: ../index.php");
	exit;
}
include("../connection.php");
include("functions/hrm_FunctionMaster.php");
include("functions/admin_functionmaster.php");
include("functions/comanphp_functions.php");
include("functions/hrm_loginFunctions.php");

$key = isset($_GET['k']) ? $_GET['k'] : '';
$isValidProject = false;
$session_loginid = $_SESSION['loginid'] ?? '';
if (!empty($key)) {
	$key_decoded = base64url_decode($key);
	$key_parts = explode("|", $key_decoded);
	if (count($key_parts) >= 6) {
		$checkkey = base64url_decode($key_parts[count($key_parts) - 1]);
		$actkey = "";
		for ($i = 0; $i < count($key_parts) - 1; $i++) {
			$actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
		}
		if ($actkey == $checkkey) {
			$loginid = $key_parts[0];
			$loginroleid = $key_parts[1];
			$companyid = $key_parts[2];
			$subcompanyid = $key_parts[3];
			$projectid = $key_parts[4];
			$logintype = $key_parts[5];
			
			// Verify if the project actually exists
			$prjinfo = hrm_projectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
			if (count($prjinfo) > 0) {
				$projectname = $prjinfo[0]['projectname'];
				$isValidProject = true;
			}
		}
	}
}

if (!$isValidProject) {
	// Reconstruct the key and redirect to the project select page if they have a session
	if (!empty($session_loginid)) {
		// Fetch role details for key reconstruction
		$loginroleid = 99; // Default fallback
		$loginrolename = "Employee"; // Default fallback
		
		$stmt = $authconn->prepare("SELECT loginroleid, loginrolename FROM login_user_master WHERE loginid = ?");
		if ($stmt) {
			$stmt->bind_param("s", $session_loginid);
			$stmt->execute();
			$res = $stmt->get_result();
			if ($row = $res->fetch_assoc()) {
				$loginroleid = $row['loginroleid'];
				$loginrolename = $row['loginrolename'];
			}
			$stmt->close();
		}
		
		// Build the new key
		$qstring = $session_loginid . "|" . $loginroleid . "|x|x|x|" . $loginrolename;
		$enc_qstring = base64url_encode($qstring);
		$combined_key = $qstring . "|" . $enc_qstring;
		$enc_key = base64url_encode($combined_key);
		
		header("Location: ../loginCell/indexdisplayprojects.php?k=" . urlencode($enc_key));
		exit;
	} else {
		header("Location: ../index.php");
		exit;
	}
}

if ($loginroleid > 3 && (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'labourmaster.php')) {
	$redirect_url = "qr_scanner.php?k=" . urlencode($key);
	if (isset($_GET['mdid'])) {
		$redirect_url .= "&mdid=" . urlencode($_GET['mdid']);
	}
	header("Location: " . $redirect_url);
	exit;
}

$newkey = "x|x|" . $key_parts[2] . "|" . $key_parts[3] . "|" . $key_parts[4] . "|x";
$encodednewkey = base64url_encode($newkey);
$keycompany = base64url_encode($newkey . "|" . $encodednewkey);

// login details
if ($loginid) {
	$lginfo = hrm_loginuserinfo($authconn, $loginroleid, "loginid", $loginid);
	$loginname = $lginfo[0]['loginname'];
} else {
	echo "login id is missing";
}

$prjinfo = hrm_projectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
$projectname = "";
if (count($prjinfo) > 0) {
	$projectname = $prjinfo[0]['projectname'];
}
$protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
$url = basename($_SERVER["PHP_SELF"]);
$rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
$baseurl = $rooturl . str_replace($url, "", $_SERVER["PHP_SELF"]);
if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "192.168.1.65") {
	$prefix = $rooturl . "/krf_amanaccounting/labourpayment/";
} else {
	$prefix = $rooturl . "/labourpayment/";
}
$rooturl = $prefix;
if (isset($_GET['mdid'])) {
	$mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
	$groupid = $dddr[0]['groupid'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<!-- Meta Tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dashboard | Labour Payment CRM</title>
	<meta name="description" content="">
	<meta name="keywords" content="">
	<meta name="author" content="AmanMovement">
	<meta name="robots" content="index, follow">
	<!-- Apple Touch Icon -->
	<link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">

	<!-- Favicon -->
	<link rel="icon" href="assets/img/favicon.png" type="image/x-icon">
	<link rel="shortcut icon" href="assets/img/favicon.png" type="image/x-icon">

	<!-- jQuery -->
	<script src="assets/js/jquery-3.7.1.min.js"></script>

	<!-- Theme Script js -->
	<!-- <script src="assets/js/theme-script.js"></script> -->

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="assets/css/bootstrap.min.css">

	<!-- Feather CSS -->
	<link rel="stylesheet" href="assets/plugins/icons/feather/feather.css">

	<!-- Tabler Icon CSS -->
	<link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css">

	<!-- Select2 CSS -->
	<link rel="stylesheet" href="assets/plugins/select2/css/select2.min.css">

	<!-- Fontawesome CSS -->
	<link rel="stylesheet" href="assets/plugins/fontawesome/css/fontawesome.min.css">
	<link rel="stylesheet" href="assets/plugins/fontawesome/css/all.min.css">

	<!-- Datetimepicker CSS -->
	<link rel="stylesheet" href="assets/css/bootstrap-datetimepicker.min.css">

	<!-- Bootstrap Tagsinput CSS -->
	<link rel="stylesheet" href="assets/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css">

	<!-- Summernote CSS -->
	<link rel="stylesheet" href="assets/plugins/summernote/summernote-lite.min.css">

	<!-- Daterangepikcer CSS -->
	<link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">

	<!-- Datatable CSS -->
	<link rel="stylesheet" href="assets/css/dataTables.bootstrap5.min.css">

	<!-- Color Picker Css -->
	<link rel="stylesheet" href="assets/plugins/flatpickr/flatpickr.min.css">
	<link rel="stylesheet" href="assets/plugins/%40simonwep/pickr/themes/nano.min.css">


	<!-- Main CSS -->
	<link rel="stylesheet" href="assets/css/style.css">
	<link rel="stylesheet" href="assets/css/iziToast.css">
	<link rel="stylesheet" type="text/css"
		href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.min.css">
	<style>
		/* Iframe detection styles */
		.is-iframe .header,
		.is-iframe .sidebar,
		.is-iframe .sidebar-logo {
			display: none !important;
		}

		.is-iframe .page-wrapper {
			margin-left: 0 !important;
			padding-top: 0 !important;
		}

		.is-iframe body {
			background-color: #f2f2ed !important;
		}

		input[type="checkbox"] {
			transform: scale(1.5);
			/* 1.5 times bigger */
			-webkit-transform: scale(1.5);
			margin: 5px;
			/* adjust spacing if needed */
		}

		input[type="radio"] {
			transform: scale(1.5);
			/* 1.5 times bigger */
			-webkit-transform: scale(1.5);
			margin: 5px;
			/* adjust spacing if needed */
		}
	</style>
	<script>
		if (window.self !== window.top) {
			document.documentElement.classList.add('is-iframe');
		}
	</script>
</head>

<body data-key="<?php echo $key; ?>" data-routurl="<?php echo $rooturl; ?>" data-companyid="<?php echo $companyid; ?>"
	data-subcompanyid="<?php echo $subcompanyid; ?>" data-projectid="<?php echo $projectid; ?>"
	data-projectname="<?php echo $projectname; ?>" data-loginname="<?php echo $loginname; ?>"
	data-loginid="<?php echo $loginid; ?>" data-loginroleid="<?php echo $loginroleid; ?>"
	data-mdid="<?php echo $mdid; ?>">
	<input type="hidden" id="hidden_routurl" value="<?php echo $rooturl; ?>">
	<div id="global-loader">
		<div class="page-loader"></div>
	</div>
	<!-- Main Wrapper -->
	<div class="main-wrapper">
		<!-- Header -->
		<div class="header">
			<div class="main-header">
				<div class="header-left">
					<a href="<?php echo $rooturl; ?>index.php?k=<?php echo $key; ?>" class="logo">
						<!-- <img src="assets/img/logo.svg" alt="Logo"> -->
						HRM PLUS
					</a>
					<a href="<?php echo $rooturl; ?>index.php?k=<?php echo $key; ?>" class="dark-logo">
						<!-- <img src="assets/img/logo-white.svg" alt="Logo"> -->
						HRM+
					</a>
				</div>
				<a id="mobile_btn" class="mobile_btn" href="#sidebar">
					<span class="bar-icon">
						<span></span>
						<span></span>
						<span></span>
					</span>
				</a>
				<div class="header-user">
					<div class="nav user-menu nav-list">
						<div class="me-auto d-flex align-items-center" id="header-search">
							<a id="toggle_btn" href="javascript:void(0);" class="btn btn-menubar me-1">
								<i class="ti ti-arrow-bar-to-left"></i>
							</a>
							<a href="../admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 ms-2" style="border-radius: 8px; padding: 5px 12px; font-weight: 500;">
								<i class="ti ti-arrow-left fs-16"></i> Back to Dashboard
							</a>
						</div>

						<div class="d-flex align-items-center">
							<div class="me-1">
								<a href="#" class="btn btn-menubar btnFullscreen">
									<i class="ti ti-maximize"></i>
								</a>
							</div>
							<div class="d-none d-md-flex flex-column text-end me-3">
								<span class="text-dark" style="font-size: 14px; font-weight: 600; line-height: 1.2;"><?php echo $loginname; ?></span>
								<small class="text-muted" style="font-size: 11px; font-weight: 500;">(ID: <?php echo $loginid; ?>)</small>
							</div>
							<div class="dropdown profile-dropdown">
								<a href="javascript:void(0);" class="dropdown-toggle d-flex align-items-center"
									data-bs-toggle="dropdown">
									<span class="avatar avatar-sm online">
										<img src="assets/img/profiles/profile.jpg" alt="Img"
											class="img-fluid rounded-circle">
									</span>
								</a>
								<div class="dropdown-menu shadow-none">
									<div class="card mb-0">
										<div class="card-header">
											<div class="d-flex align-items-center">
												<span class="avatar avatar-lg me-2 avatar-rounded">
													<img src="assets/img/profiles/profile.jpg" alt="img">
												</span>
												<div>
													<h5 class="mb-0"><?php echo $loginname; ?></h5>
												</div>
											</div>
										</div>
										<div class="card-body">
											<a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
												href="#profile.html">
												<i class="ti ti-user-circle me-1"></i>My Profile
											</a>
										</div>
										<div class="card-footer">
											<a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
												href="logout.php">
												<i class="ti ti-login me-2"></i>Logout
											</a>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Mobile Menu -->
				<div class="dropdown mobile-user-menu">
					<a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"
						aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
					<div class="dropdown-menu dropdown-menu-end">
						<a class="dropdown-item" href="#profile.html">My Profile</a>
						<a class="dropdown-item" href="logout.php">Logout</a>
					</div>
				</div>
				<!-- /Mobile Menu -->
			</div>
		</div>
		<!-- /Header -->

		<!-- Sidebar -->
		<div class="sidebar" id="sidebar">
			<div class="sidebar-logo text-center mb-3">
				<a href="<?php echo $rooturl; ?>index.php?k=<?php echo $key; ?>" class="logo logo-normal">
					<!-- <img src="assets/img/logo.svg" alt="Logo"> -->
					<img src="assets/img/Asset1.png" alt="Logo">
					<!-- HRM PLUS -->
					<h4><?php echo strtoupper($projectname ?? '-'); ?></h4>
				</a>
				<a href="<?php echo $rooturl; ?>index.php?k=<?php echo $key; ?>" class="logo-small">
					<!-- <img src="assets/img/logo-small.svg" alt="Logo"> -->
					<img src="assets/img/icon.png" alt="Logo">
					<!-- HRM+ -->
				</a>
				<a href="<?php echo $rooturl; ?>index.php?k=<?php echo $key; ?>" class="dark-logo">
					<!-- <img src="assets/img/logo-white.svg" alt="Logo"> -->
					LABOUR PAYMENT SYSTEM
				</a>
			</div>
			<div class="sidebar-inner slimscroll">
				<div id="sidebar-menu" class="sidebar-menu mt-4">
					<ul>
						<li class="menu-title"><span>LABOUR MAIN MENU</span></li>
						<li>
							<ul>
								<?php if ($loginroleid == 4) { ?>
									<li>
										<a href="<?php echo $rooturl; ?>qr_scanner.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'qr_scanner.php') ? 'active' : ''; ?>">
											<i class="ti ti-qrcode"></i><span>QR Scanner (Attendance)</span>
										</a>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>qr_scanner_ot.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'qr_scanner_ot.php') ? 'active' : ''; ?>">
											<i class="ti ti-clock-plus"></i><span>QR Scanner (OT)</span>
										</a>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>labour_attendanceview.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_attendanceview.php') ? 'active' : ''; ?>">
											<i class="ti ti-calendar-check me-1"></i>Labour Attendance View</a>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>labour_ot_attendanceview.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_ot_attendanceview.php') ? 'active' : ''; ?>">
											<i class="ti ti-clock-check me-1"></i>Labour OT Attendance</a>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>labour_attphysicalverification.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_attphysicalverification.php') ? 'active' : ''; ?>">
											<i class="ti ti-user-check me-1"></i>Physical Verification</a>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>labour_salary_monthlyview.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_salary_monthlyview.php') ? 'active' : ''; ?>">
											<i class="ti ti-user-dollar"></i><span>Payment Disbursement</span>
										</a>
									</li>
								<?php } else  if ($loginroleid > 4) { ?>
									<li>
										<a href="<?php echo $rooturl; ?>qr_scanner.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'qr_scanner.php') ? 'active' : ''; ?>">
											<i class="ti ti-qrcode"></i><span>QR Scanner (Attendance)</span>
										</a>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>qr_scanner_ot.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'qr_scanner_ot.php') ? 'active' : ''; ?>">
											<i class="ti ti-clock-plus"></i><span>QR Scanner (OT)</span>
										</a>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>labour_attendanceview.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_attendanceview.php') ? 'active' : ''; ?>">
											<i class="ti ti-calendar-check me-1"></i>Labour Attendance View</a>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>labour_ot_attendanceview.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_ot_attendanceview.php') ? 'active' : ''; ?>">
											<i class="ti ti-clock-check me-1"></i>Labour OT Attendance</a>
									</li>
								<?php } else { ?>
									<li>
										<a href="<?php echo $rooturl; ?>index.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
											<i class="ti ti-chart-bar"></i><span>Attendance Analysis</span>
										</a>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>labourmaster.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labourmaster.php') ? 'active' : ''; ?>">
											<i class="ti ti-users-group"></i><span>Labour Master</span>
										</a>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>labour_bankverification.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_bankverification.php') ? 'active' : ''; ?>">
											<i class="ti ti-building-bank"></i><span>Bank Verification</span>
										</a>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>qr_scanner.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'qr_scanner.php') ? 'active' : ''; ?>">
											<i class="ti ti-qrcode"></i><span>QR Scanner (Attendance)</span>
										</a>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>qr_scanner_ot.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'qr_scanner_ot.php') ? 'active' : ''; ?>">
											<i class="ti ti-clock-plus"></i><span>QR Scanner (OT)</span>
										</a>
									</li>
									<li class="submenu d-none">
										<a href="javascript:void(0);">
											<i class="ti ti-layout-board-split"></i><span>Wages Calculation</span>
											<span class="menu-arrow"></span>
										</a>
										<ul>
											<li>
												<a
													href="<?php echo $rooturl; ?>labour_attendanceupload.php?k=<?php echo $key; ?>">Upload
													Labour Attendace</a>
											</li>
											
											<li>
												<a
													href="<?php echo $rooturl; ?>labour_calculateSalary.php?k=<?php echo $key; ?>">Save
													& Edit of Labour Wages</a>
											</li>
											<?php
											if ($loginroleid <= 3) {
												?>
												<li>
													<a
														href="<?php echo $rooturl; ?>labour_approveSalary.php?k=<?php echo $key; ?>">Approval
														of Labour Wages</a>
												</li>
												<?php
											}
											?>
											<li>
												<a
													href="<?php echo $rooturl; ?>labour_salary_monthlyview.php?k=<?php echo $key; ?>">Payment
													Disbursement</a>
											</li>
											<!-- <li>
												<a
													href="<?php echo $rooturl; ?>labour_payment_disbursement.php?k=<?php echo $key; ?>">Payment
													Disbursement Bulk</a>
											</li> -->
											<li>
												<a
													href="<?php echo $rooturl; ?>labour_updatePerDaywages.php?k=<?php echo $key; ?>">Update
													Per/Day Labour Wages</a>
											</li>
										</ul>
									</li>
									<li class="submenu">
										<a href="javascript:void(0);">
											<i class="ti ti-layout-board-split"></i><span>Attendance & Wages</span>
											<span class="menu-arrow"></span>
										</a>
										<ul>
											<li>
												<a href="<?php echo $rooturl; ?>labour_attendanceview.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_attendanceview.php') ? 'active' : ''; ?>">
													<i class="ti ti-calendar-check me-1"></i>Labour Attendance View</a>
											</li>
											<li>
												<a href="<?php echo $rooturl; ?>labour_ot_attendanceview.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_ot_attendanceview.php') ? 'active' : ''; ?>">
													<i class="ti ti-clock-check me-1"></i>Labour OT Attendance</a>
											</li>
											<li>
												<a href="<?php echo $rooturl; ?>labour_attphysicalverification.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_attphysicalverification.php') ? 'active' : ''; ?>">
													<i class="ti ti-user-check me-1"></i>Physical Verification</a>
											</li>
											<li>
												<a href="<?php echo $rooturl; ?>labour_attverify_locationsheets.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_attverify_locationsheets.php') ? 'active' : ''; ?>">
													<i class="ti ti-table me-1"></i>Location Sheets</a>
											</li>
											<li>
												<a href="<?php echo $rooturl; ?>labour_attendancecalculation.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_attendancecalculation.php') ? 'active' : ''; ?>">
													<i class="ti ti-calculator me-1"></i>Calculate Attendance</a>
											</li>
											<li>
												<a href="<?php echo $rooturl; ?>labour_calculatewages.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_calculatewages.php') ? 'active' : ''; ?>">
													<i class="ti ti-cash me-1"></i>Calculate Wages</a>
											</li>
											<?php
											if ($loginroleid <= 3) {
												?>
												<li>
													<a
														href="<?php echo $rooturl; ?>labour_approvewages.php?k=<?php echo $key; ?>">Wages Approval</a>
												</li>
												<?php
											}
											?>
											<li>
												<a href="<?php echo $rooturl; ?>labour_updatePerDaywages.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_updatePerDaywages.php') ? 'active' : ''; ?>">
													<i class="ti ti-edit-circle me-1"></i>Update Per/Day Labour Wages</a>
											</li>
										</ul>
									</li>
									<li>
										<a href="<?php echo $rooturl; ?>labour_salary_monthlyview.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'labour_salary_monthlyview.php') ? 'active' : ''; ?>">
											<i class="ti ti-user-dollar"></i><span>Payment Disbursement</span>
										</a>
									</li>
									<li class="submenu">
										<a href="javascript:void(0);">
											<i class="ti ti-file-export"></i><span>Reports</span>
											<span class="menu-arrow"></span>
										</a>
										<ul>
											<!-- <li>
												<a
													href="<?php echo $rooturl; ?>labour_report_labourwages.php?k=<?php echo $key; ?>">Labour
													Payment</a>
											</li> -->
											<li>
												<a
													href="<?php echo $rooturl; ?>labour_report_pendingwages.php?k=<?php echo $key; ?>">Pending
													Labour Payment</a>
											</li>
											<li>
												<a
													href="<?php echo $rooturl; ?>labour_report_paidwages.php?k=<?php echo $key; ?>">Paid
													Labour Payment</a>
											</li>
											<li>
												<a
													href="<?php echo $rooturl; ?>labour_report_partialwages.php?k=<?php echo $key; ?>">Partial
													Paid Labour</a>
											</li>
											<li>
												<a
													href="<?php echo $rooturl; ?>report_attendance_pa.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'report_attendance_pa.php') ? 'active' : ''; ?>">Labour Attendance P/A Report</a>
											</li>
											<li>
												<a
													href="<?php echo $rooturl; ?>report_attendance_time.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'report_attendance_time.php') ? 'active' : ''; ?>">Labour Attendance Time Report</a>
											</li>
											<li>
												<a
													href="<?php echo $rooturl; ?>report_labour_ot_attendance.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'report_labour_ot_attendance.php') ? 'active' : ''; ?>">Labour OT Attendance Report</a>
											</li>
											<li>
												<a
													href="<?php echo $rooturl; ?>report_attlabour_fixeddays.php?k=<?php echo $key; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'report_attlabour_fixeddays.php') ? 'active' : ''; ?>">Labour Attendance Fixed Days Report</a>
											</li>
										</ul>
									</li>
								<?php } ?>
							</ul>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<!-- /Sidebar -->
		<!-- Page Wrapper -->
		<div class="page-wrapper">