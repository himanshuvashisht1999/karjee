<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
// Prevent browser caching to disable history back button access after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (file_exists(__DIR__ . "/../../connection.php")) {
	include_once(__DIR__ . "/../../connection.php");
} else if (file_exists(__DIR__ . "/../connection.php")) {
	include_once(__DIR__ . "/../connection.php");
} else {
	@include_once("connection.php");
}

$payrollFuncDir = __DIR__ . "/../functions/";
$loadPayrollFunc = function($filename) use ($payrollFuncDir) {
	if (file_exists($payrollFuncDir . $filename)) {
		include_once($payrollFuncDir . $filename);
		return true;
	}
	$lower = strtolower($filename);
	if (file_exists($payrollFuncDir . $lower)) {
		include_once($payrollFuncDir . $lower);
		return true;
	}
	if (is_dir($payrollFuncDir)) {
		$scanned = scandir($payrollFuncDir);
		if ($scanned) {
			foreach ($scanned as $f) {
				if (strtolower($f) === $lower) {
					include_once($payrollFuncDir . $f);
					return true;
				}
			}
		}
	}
	return false;
};

$loadPayrollFunc("hrm_FunctionMaster.php");
$loadPayrollFunc("admin_functionmaster.php");
$loadPayrollFunc("comanphp_functions.php");
$loadPayrollFunc("hrm_loginFunctions.php");

if (!isset($_SESSION['sessionid']) || $_SESSION['sessionid'] !== session_id()) {
	header("Location: ../../index.php");
	exit;
}
$key = $_GET['k'];
$empid = $_GET['empid'];
$accessby = $_GET['accessby'];
$key_decoded = base64url_decode($key);
//print_r($key_decoded);
$key_parts = explode("|", $key_decoded);
//print_r($key_parts);
$checkkey = base64url_decode($key_parts[count($key_parts) - 1]);

$actkey = "";
for ($i = 0; $i < count($key_parts) - 1; $i++) {
	$actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
}
// echo "<br>".$actkey."<br>";
// echo $checkkey;
if ($actkey != $checkkey) {
	echo "<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
	die;
}
// print_r($key_parts);
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

// login details
$lginfo = hrm_loginuserinfo($authconn, $loginroleid, "loginid", $loginid);
$loginname = $lginfo[0]['loginname'];

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
	$prefix = $rooturl . "/krf_amanaccounting/payroll/";
} else {
	$prefix = $rooturl . "/payroll/";
}
$rooturl = $prefix;
$mdid = "";
if (isset($_GET['mdid'])) {
	$mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
	$groupid = $dddr[0]['groupid'];
}

$adminback = 0;
if ($accessby == "adminu" && $loginroleid < 4) {
	$adminback = 1;
} else {
	//$accessby."===".$loginroleid;
}

$empData = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $empid);
if (empty($empData)) {
	header("Location: ../../loginHRM/index.php");
	exit;
}
$EmployeeName = $empData[0]['FullName'] ?? '';

$isResigned = false;
if (!empty($empData)) {
	$empStatus = strtolower(trim($empData[0]['Status'] ?? ''));
	$empDor = trim($empData[0]['DateOfResignation'] ?? '');
	if ($empStatus === 'resigned' || (!empty($empDor) && $empDor !== '0000-00-00')) {
		$isResigned = true;
	}
}

// Active Menu Logic
$dashboard_pages = ['index.php'];
$profile_pages = ['index.php'];
$attendance_pages = ['empAttendance.php', 'markattendance.php']; // markattendance.php is Mark Attendance
$salary_pages = ['empSalary.php'];
$leave_pages = ['empLeave.php'];
$holiday_pages = ['holidayCalendar.php'];
$weekoff_pages = ['weeklyoffapply.php'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<!-- Meta Tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<title>Dashboard | HR+, Payroll & CRM</title>
	<meta name="description" content="">
	<meta name="keywords" content="">
	<meta name="author" content="AmanMovement">
	<meta name="robots" content="index, follow">

	<!-- Apple Touch Icon -->
	<link rel="apple-touch-icon" sizes="180x180" href="../assets/img/apple-touch-icon.png">

	<!-- Favicon -->
	<link rel="icon" href="../assets/img/favicon.png" type="image/x-icon">
	<link rel="shortcut icon" href="../assets/img/favicon.png" type="image/x-icon">

	<!-- Theme Script js -->
	<!-- <script src="../assets/js/theme-script.js"></script> -->

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="../assets/css/bootstrap.min.css">

	<!-- Feather CSS -->
	<link rel="stylesheet" href="../assets/plugins/icons/feather/feather.css">

	<!-- Tabler Icon CSS -->
	<link rel="stylesheet" href="../assets/plugins/tabler-icons/tabler-icons.min.css">

	<!-- Select2 CSS -->
	<link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">

	<!-- Fontawesome CSS -->
	<link rel="stylesheet" href="../assets/plugins/fontawesome/css/fontawesome.min.css">
	<link rel="stylesheet" href="../assets/plugins/fontawesome/css/all.min.css">

	<!-- Datetimepicker CSS -->
	<link rel="stylesheet" href="../assets/css/bootstrap-datetimepicker.min.css">

	<!-- Bootstrap Tagsinput CSS -->
	<link rel="stylesheet" href="../assets/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css">

	<!-- Summernote CSS -->
	<link rel="stylesheet" href="../assets/plugins/summernote/summernote-lite.min.css">

	<!-- Daterangepikcer CSS -->
	<link rel="stylesheet" href="../assets/plugins/daterangepicker/daterangepicker.css">

	<!-- Datatable CSS -->
	<link rel="stylesheet" href="../assets/css/dataTables.bootstrap5.min.css">

	<!-- Color Picker Css -->
	<link rel="stylesheet" href="../assets/plugins/flatpickr/flatpickr.min.css">
	<link rel="stylesheet" href="../assets/plugins/%40simonwep/pickr/themes/nano.min.css">


	<!-- Main CSS -->
	<link rel="stylesheet" href="../assets/css/style.css">
	<link rel="stylesheet" href="../assets/css/custom_payroll.css">
	<link rel="stylesheet" href="../assets/css/iziToast.css">
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

		/* Perfect Circle Top Menu Profile Photo */
		.profile-dropdown .avatar {
			width: 36px !important;
			height: 36px !important;
			min-width: 36px !important;
			min-height: 36px !important;
			max-width: 36px !important;
			max-height: 36px !important;
			border-radius: 50% !important;
			display: inline-flex !important;
			align-items: center !important;
			justify-content: center !important;
			position: relative !important;
		}
		.profile-dropdown .avatar img {
			width: 36px !important;
			height: 36px !important;
			min-width: 36px !important;
			min-height: 36px !important;
			max-width: 36px !important;
			max-height: 36px !important;
			border-radius: 50% !important;
			object-fit: cover !important;
			aspect-ratio: 1 / 1 !important;
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
	data-mdid="<?php echo $mdid; ?>" data-empid="<?php echo $empid; ?>">
	<div id="global-loader">
		<div class="page-loader"></div>
	</div>
	<!-- Main Wrapper -->
	<div class="main-wrapper">
		<!-- Header -->
		<div class="header">
			<div class="main-header">
				<div class="header-left">
					<a href="<?php echo $rooturl; ?>empPanel/index.php?k=<?php echo $key; ?>" class="logo">
						<!-- <img src="../assets/img/logo.svg" alt="Logo"> -->
						HRM PLUS
					</a>
					<a href="<?php echo $rooturl; ?>empPanel/index.php?k=<?php echo $key; ?>" class="dark-logo">
						<!-- <img src="../assets/img/logo-white.svg" alt="Logo"> -->
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
							<a href="../employees.php?k=<?php echo $key; ?>"
								class="btn btn-sm btn-primary <?php echo $adminback == 0 ? 'd-none' : '' ?>"><i
									class="fa fa-chevron-left"></i> back</a>
						</div>

						<div class="d-flex align-items-center">
							<div class="me-1">
								<a href="#" class="btn btn-menubar btnFullscreen">
									<i class="ti ti-maximize"></i>
								</a>
							</div>
							<div class="me-1">
								<?php echo $EmployeeName; ?> <span style="color: #2563eb; font-weight: 600; font-size: 0.85em;">(<?php echo $empid; ?>)</span>
							</div>
							<div class="dropdown profile-dropdown">
								<a href="javascript:void(0);" class="dropdown-toggle d-flex align-items-center"
									data-bs-toggle="dropdown">
									<span class="avatar avatar-sm online">
										<?php
										$displayPhoto = "../assets/img/profiles/profile.jpg";
										$rawPhotoPath = "";
										
										// Always query hrm_EmployeeDocuments for the latest ProfilePhoto of this employee
										$docQuery = "SELECT DocumentPath FROM hrm_EmployeeDocuments WHERE EmployeeID = ? AND DocumentType IN ('ProfilePhoto', 'Profile Photo', 'profile_photo') AND (tedit IS NULL OR tedit = '') AND DocumentPath IS NOT NULL AND DocumentPath != '' AND DocumentPath NOT LIKE '%No file%' ORDER BY DocumentID DESC LIMIT 1";
										if ($stmt = $hrmconn->prepare($docQuery)) {
											$stmt->bind_param("s", $empid);
											$stmt->execute();
											$res = $stmt->get_result();
											if ($docRow = $res->fetch_assoc()) {
												$rawPhotoPath = trim($docRow['DocumentPath'] ?? '');
											}
											$stmt->close();
										}
										
										if (empty($rawPhotoPath)) {
											$rawPhotoPath = trim($empData[0]['empPhotopath'] ?? $empData[0]['ProfilePhoto'] ?? '');
										}

										$lowPhoto = strtolower($rawPhotoPath);
										if (!empty($rawPhotoPath) && strpos($lowPhoto, 'no file') === false && strpos($lowPhoto, 'error') === false) {
											if (strpos($rawPhotoPath, 'http://') === 0 || strpos($rawPhotoPath, 'https://') === 0) {
												$displayPhoto = $rawPhotoPath;
											} else {
												$cleanPath = ltrim(preg_replace('/^(\.\.\/|\.\/)+/', '', $rawPhotoPath), '/');
												$displayPhoto = "../" . $cleanPath;
											}
										}
										?>
										<img src="<?php echo $displayPhoto; ?>" alt="Img"
											class="img-fluid rounded-circle" style="width: 34px; height: 34px; object-fit: cover;" onerror="this.onerror=null; this.src='../assets/img/profiles/profile.jpg';">
									</span>
								</a>
								<div class="dropdown-menu shadow-none">
									<div class="card mb-0">
										<div class="card-header">
											<div class="d-flex align-items-center">
												<span class="avatar avatar-lg me-2 avatar-rounded font-semibold" style="width: 45px; height: 45px;">
													<img src="<?php echo $displayPhoto; ?>" alt="img" style="width: 45px; height: 45px; object-fit: cover;" onerror="this.onerror=null; this.src='../assets/img/profiles/profile.jpg';">
												</span>
												<div>
													<h5 class="mb-0"><?php echo $EmployeeName; ?> <span style="color: #2563eb; font-weight: 600; font-size: 0.8em; margin-left: 4px;">(<?php echo $empid; ?>)</span></h5>
												</div>
											</div>
										</div>
										<div class="card-body">
											<a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
												href="index.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>">
												<i class="ti ti-user-circle me-1"></i>My Profile
											</a>
											<?php if (intval($loginroleid) <= 3) { ?>
											<a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
												href="index.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>&op=edit">
												<i class="ti ti-edit me-1"></i>Edit Profile
											</a>
											<?php } ?>
											<a class="dropdown-item d-inline-flex align-items-center p-0 py-2"
												href="index.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>&op=changepassword">
												<i class="ti ti-key me-1"></i>Change Password
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
						<a class="dropdown-item"
							href="index.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>">My
							Profile</a>
						<?php if (intval($loginroleid) <= 3) { ?>
						<a class="dropdown-item"
							href="index.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>&op=edit">Edit Profile</a>
						<?php } ?>
						<a class="dropdown-item"
							href="index.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>&op=changepassword">Change Password</a>
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
				<a href="<?php echo $rooturl; ?>empPanel/index.php?k=<?php echo $key; ?>" class="logo logo-normal">
					<!-- <img src="../assets/img/logo.svg" alt="Logo"> -->
					<img src="../assets/img/Asset1.png" alt="Logo">
					<!-- HRM PLUS -->
					<h4><?php echo strtoupper($projectname ?? '-'); ?></h4>
				</a>
				<a href="<?php echo $rooturl; ?>empPanel/index.php?k=<?php echo $key; ?>" class="logo-small">
					<!-- <img src="../assets/img/logo-small.svg" alt="Logo"> -->
					<img src="../assets/img/icon.png" alt="Logo">
					<!-- HRM+ -->
				</a>
				<a href="<?php echo $rooturl; ?>empPanel/index.php?k=<?php echo $key; ?>" class="dark-logo">
					<!-- <img src="../assets/img/logo-white.svg" alt="Logo"> -->
					PAYROLL SYSTEM
				</a>
			</div>
			<div class="sidebar-inner slimscroll">
				<div id="sidebar-menu" class="sidebar-menu" style="margin-top: 64px;">
					<ul>
						<!-- <li class="menu-title"><span>MAIN MENU</span></li> -->
						<li class="d-none">
							<ul>
								<li class="submenu">
									<a href="javascript:void(0);"
										class="<?php echo in_array($url, $dashboard_pages) ? 'active subdrop' : ''; ?>">
										<i class="ti ti-smart-home"></i>
										<span>Dashboard</span>
										<span class="badge badge-danger fs-10 fw-medium text-white p-1">Hot</span>
										<span class="menu-arrow"></span>
									</a>
									<ul
										style="<?php echo in_array($url, $dashboard_pages) ? 'display: block;' : ''; ?>">
										<li><a href="<?php echo $rooturl; ?>empPanel/index.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"
												class="<?php echo ($url == 'index.php') ? 'active' : ''; ?>">Employee
												Dashboard</a></li>
										<li><a href="#">Employee Dashboard</a></li>
									</ul>
								</li>
							</ul>
						</li>
						<li class="menu-title"><span>EMPLOYEE MAIN MENU</span></li>
						<li>
							<ul>
								<li class="<?php echo in_array($url, $profile_pages) ? 'active' : ''; ?>">
									<a href="<?php echo $rooturl; ?>empPanel/index.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"
										class="<?php echo in_array($url, $profile_pages) ? 'active' : ''; ?>">
										<i class="ti ti-user-plus"></i> <span>Profile</span></a>
								</li>
								<?php if (!$isResigned) { ?>
								<li class="<?php echo ($url == 'markattendance.php') ? 'active' : ''; ?>">
									<a href="<?php echo $rooturl; ?>empPanel/markattendance.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"
										class="<?php echo ($url == 'markattendance.php') ? 'active' : ''; ?>">
										<i class="ti ti-user-plus"></i> <span>Mark Attendance</span></a>
								</li>
								<?php } ?>
								<li class="<?php echo ($url == 'empAttendance.php') ? 'active' : ''; ?>">
									<a href="<?php echo $rooturl; ?>empPanel/empAttendance.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"
										class="<?php echo ($url == 'empAttendance.php') ? 'active' : ''; ?>">
										<i class="ti ti-calendar-event"></i> <span>View Attendance</span></a>
								</li>
								<?php if (intval($loginroleid) <= 3) { ?>
								<li class="<?php echo in_array($url, $salary_pages) ? 'active' : ''; ?>">
									<a href="<?php echo $rooturl; ?>empPanel/empSalary.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"
										class="<?php echo in_array($url, $salary_pages) ? 'active' : ''; ?>">
										<i class="ti ti-calendar-event"></i> <span>Salary Details</span>
									</a>
								</li>
								<?php } ?>
								<li class="<?php echo in_array($url, $leave_pages) ? 'active' : ''; ?>">
									<a href="<?php echo $rooturl; ?>empPanel/empLeave.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"
										class="<?php echo in_array($url, $leave_pages) ? 'active' : ''; ?>">
										<i class="ti ti-calendar-event"></i> <span>Leave Mangement</span>
									</a>
								</li>
								<li class="<?php echo in_array($url, $holiday_pages) ? 'active' : ''; ?>">
									<a href="<?php echo $rooturl; ?>empPanel/holidayCalendar.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"
										class="<?php echo in_array($url, $holiday_pages) ? 'active' : ''; ?>">
										<i class="ti ti-calendar-event"></i> <span>Holiday Calendar</span>
									</a>
								</li>
								<?php if (!$isResigned) { ?>
								<li class="<?php echo in_array($url, $weekoff_pages) ? 'active' : ''; ?>">
									<a href="<?php echo $rooturl; ?>empPanel/weeklyoffapply.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"
										class="<?php echo in_array($url, $weekoff_pages) ? 'active' : ''; ?>">
										<i class="ti ti-calendar-event"></i> <span>Weekly Off Apply</span>
									</a>
								</li>
								<?php } ?>
							</ul>
						</li>


						<li>
							<a href="logout.php">
								<i class="ti ti-calendar-event"></i> <span>Log Out</span>
							</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<!-- /Sidebar -->
		<!-- Page Wrapper -->
		<div class="page-wrapper">