<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
// Prevent browser caching to disable history back button access after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (file_exists(__DIR__ . "/../connection.php")) {
	include_once(__DIR__ . "/../connection.php");
} else if (file_exists(__DIR__ . "/../../connection.php")) {
	include_once(__DIR__ . "/../../connection.php");
} else {
	@include_once("connection.php");
}

$payrollFuncDir = __DIR__ . "/functions/";
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
	header("Location: ../index.php");
	exit;
}
$key = $_GET['k'];
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
if (isset($_GET['mdid'])) {
	$mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
	$groupid = $dddr[0]['groupid'];
}
// Active Menu Logic
$dashboard_pages = ['index.php', 'employee-dashboard.php'];
$admin_master_pages = ['employees.php', 'employeeIncrement.php', 'holidayMaster.php', 'leaveTypeMaster.php', 'employeeLeaveApproval.php', 'employeeAttendanceApproval.php', 'employeesLoginPassowrd.php','EmployeeAdvanceList.php', 'employeeWeekOffApproval.php'];
$employee_pages = ['employeeResigtration.php', 'employeeAttendanceView.php', 'employeeSalary.php', 'HolidayCalendar.php', 'employeeLeaveApply.php', 'manualAttendance.php'];
$salary_pages = ['SalaryCalculation.php', 'approveSalary.php', 'salary_monthlyview.php', 'salaryreadytoTransfer.php', 'epfEsicApplicableList.php'];
$settings_pages = ['addUpdateLocationCoordinates.php', 'addUpdateExemptionConfiguration.php', 'addUpdateWorkingHours.php'];
$report_pages = ['report_attendance_pa.php', 'report_attendance_time.php', 'report_labour_ot_attendance.php'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<!-- Meta Tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dashboard | SmartHR, Payroll & CRM</title>
	<meta name="description" content="">
	<meta name="keywords" content="">
	<meta name="author" content="AmanMovement">
	<meta name="robots" content="index, follow">
	<!-- Apple Touch Icon -->
	<link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">

	<!-- Favicon -->
	<link rel="icon" href="assets/img/favicon.png" type="image/x-icon">
	<link rel="shortcut icon" href="assets/img/favicon.png" type="image/x-icon">

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
	<link rel="stylesheet" href="assets/css/custom_payroll.css">
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
					HRM PLUS
					</a>
					<a href="<?php echo $rooturl; ?>index.php?k=<?php echo $key; ?>" class="dark-logo">
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
							<div class="dropdown profile-dropdown">
								<a href="javascript:void(0);" class="dropdown-toggle d-flex align-items-center"
									data-bs-toggle="dropdown">
									<span class="me-2 fw-medium d-none d-sm-inline-block"><?php echo strtoupper($loginname); ?> (<?php echo $loginid; ?>)</span>
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
												href="<?php echo $rooturl; ?>logout.php">
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
						<a class="dropdown-item" href="<?php echo $rooturl; ?>logout.php">Logout</a>
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
					<img src="assets/img/Asset1.png" alt="Logo">
					<h4><?php echo strtoupper($projectname ?? '-'); ?></h4>
				</a>
				<a href="<?php echo $rooturl; ?>index.php?k=<?php echo $key; ?>" class="logo-small">
					<img src="assets/img/icon.png" alt="Logo">
				</a>
				<a href="<?php echo $rooturl; ?>index.php?k=<?php echo $key; ?>" class="dark-logo">
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
										class="<?php echo in_array($url, $dashboard_pages) ? 'active' : ''; ?>">
										<i class="ti ti-smart-home"></i>
										<span>Dashboard</span>
										<span class="badge badge-danger fs-10 fw-medium text-white p-1">Hot</span>
										<span class="menu-arrow"></span>
									</a>
									<ul
										style="<?php echo in_array($url, $dashboard_pages) ? 'display: block;' : ''; ?>">
										<li><a href="<?php echo $rooturl; ?>index.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'index.php') ? 'active' : ''; ?>">Admin
												Dashboard</a></li>
										<li><a href="employee-dashboard.php"
												class="<?php echo ($url == 'employee-dashboard.php') ? 'active' : ''; ?>">Employee
												Dashboard</a></li>
									</ul>
								</li>
							</ul>
						</li>
						<li class="menu-title mt-4"><span>EMPLOYEE MAIN MENU</span></li>
						<li>
							<ul>

								<li class="submenu <?php echo ($loginroleid >= 3 ? 'd-none' : ''); ?>">
									<a href="javascript:void(0);"
										class="<?php echo in_array($url, $admin_master_pages) ? 'active' : ''; ?>">
										<i class="ti ti-users"></i><span>Administrative Masters</span>
										<span class="menu-arrow"></span>
									</a>
									<ul
										style="<?php echo in_array($url, $admin_master_pages) ? 'display: block;' : ''; ?>">
										<li><a href="<?php echo $rooturl; ?>employees.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'employees.php') ? 'active' : ''; ?>"><i
													class="ti ti-users-group"></i> Employee Master</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>employeeIncrement.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'employeeIncrement.php') ? 'active' : ''; ?>">
												<i class="ti ti-trending-up"></i> Employee Increment
											</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>employeeAttendanceView.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'employeeAttendanceView.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Employee Attendance
											</a>
										</li>
										<li>											
											<a href="<?php echo $rooturl; ?>holidayMaster.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'holidayMaster.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Holiday Master
											</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>leaveTypeMaster.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'leaveTypeMaster.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Leave Type Master
											</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>employeeLeaveApproval.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'employeeLeaveApproval.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Leave Approval
											</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>employeeAttendanceApproval.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'employeeAttendanceApproval.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Attendance Approval
											</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>employeeWeekOffApproval.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'employeeWeekOffApproval.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Week-off Approval
											</a>
										</li>

										<li>
											<a href="<?php echo $rooturl; ?>employeesLoginPassowrd.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'employeesLoginPassowrd.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Login Id & Password
											</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>EmployeeAdvanceList.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'EmployeeAdvanceList.php') ? 'active' : ''; ?>">Employee
												Advance</a>
										</li>

									</ul>
								</li>
								<li class="submenu">
									<a href="javascript:void(0);"
										class="<?php echo in_array($url, $employee_pages) ? 'active' : ''; ?>">
										<i class="ti ti-users"></i><span>Employee</span>
										<span class="menu-arrow"></span>
									</a>
									<ul style="<?php echo in_array($url, $employee_pages) ? 'display: block;' : ''; ?>">

										<li><a href="<?php echo $rooturl; ?>employeeResigtration.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'employeeResigtration.php') ? 'active' : ''; ?>"><i
													class="ti ti-user-plus"></i> Register Employee</a></li>
										
										<li class="d-none"><a
												href="<?php echo $rooturl; ?>employeeSalary.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'employeeSalary.php') ? 'active' : ''; ?>">Salary
												Components</a></li>
										<li>
											<a href="<?php echo $rooturl; ?>HolidayCalendar.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'HolidayCalendar.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Holiday Calendar
											</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>employeeLeaveApply.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'employeeLeaveApply.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Leave Apply
											</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>manualAttendance.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'manualAttendance.php') ? 'active' : ''; ?>">
												<i class="ti ti-list-check"></i> Manual Attendance
											</a>
										</li>

									</ul>
								</li>
								<li class="submenu">
									<a href="javascript:void(0);"
										class="<?php echo in_array($url, $salary_pages) ? 'active' : ''; ?>">
										<i class="ti ti-layout-board-split"></i><span>Salary Calculation</span>
										<span class="menu-arrow"></span>
									</a>
									<ul style="<?php echo in_array($url, $salary_pages) ? 'display: block;' : ''; ?>">
										<!-- <li>
											<a href="<?php echo $rooturl; ?>salary_uploadAttendanceMachineSheet.php?k=<?php echo $key; ?>">Upload Machine Attendace Excel</a>
										</li> -->
										<li>
											<a href="<?php echo $rooturl; ?>SalaryCalculation.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'SalaryCalculation.php') ? 'active' : ''; ?>">Salary
												Calculation</a>
										</li>
										<li class="<?php echo ($loginroleid >= 3 ? 'd-none' : ''); ?>">
											<a href="<?php echo $rooturl; ?>approveSalary.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'approveSalary.php') ? 'active' : ''; ?>">Salary
												Approval</a>
										</li>
										<li><a href="<?php echo $rooturl; ?>salary_monthlyview.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'salary_monthlyview.php') ? 'active' : ''; ?>">Salary
												View & Report</a></li>
										<li>
											<a href="<?php echo $rooturl; ?>salaryreadytoTransfer.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'salaryreadytoTransfer.php') ? 'active' : ''; ?>">Ready
												to Pay Salary</a>
										</li>
										
										<li>
											<a href="<?php echo $rooturl; ?>epfEsicApplicableList.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'epfEsicApplicableList.php') ? 'active' : ''; ?>">EPF & ESIC List</a>
										</li>
									</ul>
								</li>
								<li class="submenu <?php echo ($loginroleid >= 3 ? 'd-none' : ''); ?>">
									<a href="javascript:void(0);"
										class="<?php echo in_array($url, $settings_pages) ? 'active' : ''; ?>"><i
											class="ti ti-settings me-1"></i>
										<span>Settings</span>
										<span class="menu-arrow"></span>
									</a>
									<ul style="<?php echo in_array($url, $settings_pages) ? 'display: block;' : ''; ?>">
										<li>
											<a href="<?php echo $rooturl; ?>leaveTypeMaster.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'leaveTypeMaster.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Leave Type Master
											</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>addUpdateLocationCoordinates.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'addUpdateLocationCoordinates.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Office Location
											</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>addUpdateExemptionConfiguration.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'addUpdateExemptionConfiguration.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Exempt Attendnace Location
											</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>addUpdateWorkingHours.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'addUpdateWorkingHours.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Office Working Hourse
											</a>
										</li>
									</ul>
								</li>
								<li class="submenu <?php echo ($loginroleid >= 3 ? 'd-none' : ''); ?>">
									<a href="javascript:void(0);"
										class="<?php echo in_array($url, $report_pages) ? 'active' : ''; ?>"><i
											class="ti ti-file-analytics me-1"></i>
										<span>Reports</span>
										<span class="menu-arrow"></span>
									</a>
									<ul style="<?php echo in_array($url, $report_pages) ? 'display: block;' : ''; ?>">
										<li>
											<a href="<?php echo $rooturl; ?>report_attendance_pa.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'report_attendance_pa.php') ? 'active' : ''; ?>">
												<i class="ti ti-calendar-event"></i> Attendance P/A
											</a>
										</li>
										<li>
											<a href="<?php echo $rooturl; ?>report_attendance_time.php?k=<?php echo $key; ?>"
												class="<?php echo ($url == 'report_attendance_time.php') ? 'active' : ''; ?>">
												<i class="ti ti-clock"></i> Attendance Time IN/OUT
											</a>
										</li>
									</ul>
								</li>
								<!-- <li>
									<a href="<?php echo $rooturl; ?>employees.php?k=<?php echo $key; ?>">Employee Master</a>
								</li> -->

								<!-- <li class="d-none">
									<a href="<?php echo $rooturl; ?>salary_uploadmanual.php?k=<?php echo $key; ?>">Upload Attendace Manual Excel</a>
								</li>
								<li>
									<a href="<?php echo $rooturl; ?>salary_uploadAttendanceMachineSheet.php?k=<?php echo $key; ?>">Upload Machine Attendace Excel</a>
								</li>
								<li>
									<a href="<?php echo $rooturl; ?>approveSalary.php?k=<?php echo $key; ?>">Salary Approval</a>
								</li>
								<li><a href="<?php echo $rooturl; ?>salary_monthlyview.php?k=<?php echo $key; ?>">Salary View & Report</a></li>
								<li>
									<a href="<?php echo $rooturl; ?>salaryreadytoTransfer.php?k=<?php echo $key; ?>">Ready to Pay Salary</a> -->
						</li>
						<!-- <li>
									<a href="<?php echo $rooturl; ?>holidayMaster.php?k=<?php echo $key; ?>">
										<i class="ti ti-calendar-event"></i><span>Holidays</span>
									</a>
								</li>-->
					</ul>
					</li>
					</ul>
				</div>
			</div>
		</div>
		<!-- /Sidebar -->
		<!-- Page Wrapper -->
		<div class="page-wrapper">