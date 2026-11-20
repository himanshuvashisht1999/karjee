<?php
require('../connection.php');
require('functions/login_functions.php');
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
<!DOCTYPE html>
<html lang="en">

<head>
	<title>Aman Accounting | Secure Login</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- Favicon -->
	<link rel="icon" type="image/png" href="../img/logo/logo.png" />

	<!-- Stylesheets -->
	<link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="css/iziToast.css">
	<link rel="stylesheet" type="text/css" href="css/modern_login.css">
</head>

<body>
	<input type="hidden" id="hidden_rooturl" value="<?php echo $rooturl; ?>" />

	<main class="login-wrapper-split">
		<!-- Left: Image Section -->
		<div class="login-image-side">
			<img src="https://images.unsplash.com/photo-1585314062340-f1a5a7c9328d?auto=format&fit=crop&q=80&w=1200"
				alt="Karjee Farm Greenhouse">
			<div class="login-image-overlay">
				<h2>Karjee Farms</h2>
				<p>Leading the way in sustainable, high-tech organic farming for a healthier future.</p>
			</div>
		</div>

		<!-- Right: Form Section -->
		<div class="login-form-side">
			<div class="logo-wrapper">
				<img src="img/logo.png" alt="Aman Accounting Logo" onerror="this.src='img/raggingposter.png'">
			</div>

			<div class="login-header">
				<h1>Secure Gateway</h1>
				<p>Enter your credentials to manage your account.</p>
			</div>

			<form class="validate-form" method="POST" id="loginForm">
				<div class="form-group">
					<label class="form-label">Username / User ID</label>
					<div class="input-wrapper">
						<i class="fa fa-envelope-o"></i>
						<input class="form-control" type="text" name="useruid" id="txtuserid"
							placeholder="Enter your User ID" autocomplete="off" required>
					</div>
				</div>

				<div class="form-group">
					<label class="form-label">Password</label>
					<div class="input-wrapper">
						<i class="fa fa-lock"></i>
						<input class="form-control" type="password" name="pass" id="txtpassword"
							placeholder="Enter your Password" autocomplete="off" required>
					</div>
				</div>

				<a href="#" class="forgot-link">Forgot Password?</a>

				<button class="btn-login" type="submit" id="loginuserbtn">
					<span>Sign In To System</span>
					<i class="fa fa-arrow-right"></i>
				</button>

				<div class="alternate-login">
					<p class="text-muted mb-3" style="font-size: 13px;">Looking for Employee Portal?</p>
					<a href="../loginHRM/" class="btn-employee">
						<i class="fa fa-user-circle-o mr-2"></i> Access Employee Login
					</a>
				</div>
			</form>
		</div>
	</main>

	<!-- Scripts -->
	<script type="text/javascript" src="vendor/jquery/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="vendor/bootstrap/js/popper.js"></script>
	<script type="text/javascript" src="vendor/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/iziToast.js"></script>
	<script type="text/javascript" src="js/main.js"></script>
	<script type="text/javascript" src="js/login.js"></script>
	<script type="text/javascript" src="js/actioncontrol.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

	<style>
		@keyframes float {
			0% {
				transform: translateY(0) rotate(0deg);
				opacity: 0.05;
			}

			50% {
				transform: translateY(-50px) rotate(45deg);
				opacity: 0.1;
			}

			100% {
				transform: translateY(0) rotate(0deg);
				opacity: 0.05;
			}
		}
	</style>
</body>

</html>
<script type="text/javascript">
	$("#loginForm").on("submit", function (e) {
		e.preventDefault();
		checkLoginDisplay('hidden_rooturl', $('#txtuserid').val(), $('#txtpassword').val(), 'loginuserbtn');
	});
</script>