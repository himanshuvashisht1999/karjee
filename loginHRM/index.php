<?php 
require('../connection.php');
require('functions/login_functions.php');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
	<meta name="description" content="HRM+">
	<meta name="keywords" content="">
	<meta name="author" content="">
	<meta name="robots" content="noindex, nofollow">
	<title>Sign In | HRM PLUS</title>
	<!-- Favicon -->
	<link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">

	<!-- Apple Touch Icon -->
	<link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">

	<!-- Google Fonts (Outfit) -->
	<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="assets/css/bootstrap.min.css">

	<!-- Tabler Icon CSS -->
    <link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css">

	<!-- Main CSS -->
	<link rel="stylesheet" href="assets/css/style.css">
	<link rel="stylesheet" href="css/iziToast.css" type="text/css" media="all">
	<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.css" rel="stylesheet">
	<style>
		:root {
			--font-primary: 'Outfit', sans-serif;
			--primary-green: #10b981;
			--dark-green: #059669;
		}

		body {
			font-family: var(--font-primary);
			color: #f3f4f6;
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0;
			position: relative;
			overflow-x: hidden;
		}

		@media (min-width: 992px) {
			body {
				min-height: 100vh;
				padding: 2rem;
				box-sizing: border-box;
			}
		}

		/* Animated Slideshow Background */
		.slideshow-background {
			position: fixed;
			top: 0;
			left: 0;
			width: 100vw;
			height: 100vh;
			z-index: -2;
			overflow: hidden;
		}

		.slide {
			position: absolute;
			width: 100%;
			height: 100%;
			background-size: cover;
			background-position: center;
			opacity: 0;
			animation: fadeSlideshow 24s infinite;
		}

		.slide:nth-child(1) {
			background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1920&q=80');
			animation-delay: 0s;
		}

		.slide:nth-child(2) {
			background-image: url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1920&q=80');
			animation-delay: 8s;
		}

		.slide:nth-child(3) {
			background-image: url('https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=1920&q=80');
			animation-delay: 16s;
		}

		@keyframes fadeSlideshow {
			0% { opacity: 0; transform: scale(1.05); }
			4% { opacity: 1; }
			33% { opacity: 1; }
			37% { opacity: 0; transform: scale(1); }
			100% { opacity: 0; }
		}

		/* Organic Green Overlay */
		.slideshow-overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100vw;
			height: 100vh;
			z-index: -1;
			background: linear-gradient(135deg, rgba(6, 47, 31, 0.88) 0%, rgba(11, 28, 21, 0.94) 100%);
		}

		/* Split Panel Design */
		.main-split-container {
			min-height: 100vh;
			width: 100%;
			display: flex;
			align-items: center;
		}

		@media (min-width: 992px) {
			.main-split-container {
				min-height: calc(100vh - 4rem);
			}
		}

		.left-panel {
			padding: 2rem;
			display: flex;
			justify-content: center;
			align-items: center;
		}

		@media (min-width: 992px) {
			.left-panel {
				min-height: calc(100vh - 4rem);
			}
		}

		.right-panel {
			padding: 3rem;
			display: flex;
			flex-direction: column;
			justify-content: center;
			color: #ffffff;
		}

		@media (min-width: 992px) {
			.right-panel {
				min-height: calc(100vh - 4rem);
			}
		}

		/* Glassmorphism Card (Left) */
		.login-card {
			background: rgba(17, 24, 39, 0.5);
			backdrop-filter: blur(20px);
			-webkit-backdrop-filter: blur(20px);
			border: 1px solid rgba(255, 255, 255, 0.08);
			border-radius: 18px;
			padding: 1.25rem 1.5rem;
			box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
			width: 100%;
			max-width: 320px;
			transition: all 0.3s ease;
			text-align: center;
		}

		.login-card:hover {
			border-color: rgba(255, 255, 255, 0.15);
			box-shadow: 0 30px 60px -10px rgba(16, 185, 129, 0.15);
		}

		.brand-logo-container {
			margin-bottom: 0.6rem;
			display: flex;
			justify-content: center;
		}

		.brand-logo-container img {
			max-height: 32px;
			filter: drop-shadow(0 2px 8px rgba(255,255,255,0.1));
		}

		.brand-title {
			font-size: 1.4rem;
			font-weight: 800;
			background: linear-gradient(135deg, #ffffff 30%, #34d399 100%);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			margin-bottom: 0.2rem;
			letter-spacing: -0.5px;
			text-align: center;
		}

		.brand-subtitle {
			color: #9ca3af;
			font-size: 0.8rem;
			font-weight: 400;
			margin-bottom: 1rem;
			text-align: center;
		}

		.form-label {
			color: #e5e7eb;
			font-weight: 500;
			font-size: 0.8rem;
			margin-bottom: 0.25rem;
			letter-spacing: 0.3px;
		}

		.input-group-custom {
			position: relative;
			margin-bottom: 0.8rem;
		}

		.input-group-custom input {
			width: 100%;
			background: rgba(255, 255, 255, 0.03);
			border: 1px solid rgba(255, 255, 255, 0.1);
			border-radius: 8px;
			padding: 0.55rem 0.9rem 0.55rem 2.5rem;
			color: #f3f4f6;
			font-size: 0.85rem;
			transition: all 0.2s ease;
		}

		.input-group-custom input:focus {
			outline: none;
			background: rgba(255, 255, 255, 0.06);
			border-color: var(--primary-green);
			box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
		}

		.input-group-custom .input-icon {
			position: absolute;
			left: 0.9rem;
			top: 50%;
			transform: translateY(-50%);
			color: #9ca3af;
			font-size: 1rem;
			pointer-events: none;
			transition: color 0.2s ease;
		}

		.input-group-custom input:focus ~ .input-icon {
			color: var(--primary-green);
		}

		.btn-submit-premium {
			background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%);
			border: none;
			color: #ffffff;
			padding: 0.65rem;
			font-size: 0.9rem;
			font-weight: 600;
			border-radius: 8px;
			width: 100%;
			transition: all 0.2s ease;
			box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
			letter-spacing: 0.5px;
			margin-top: 0.3rem;
		}

		.btn-submit-premium:hover {
			background: linear-gradient(135deg, #047857 0%, #059669 100%);
			transform: translateY(-2px);
			box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
		}

		.btn-submit-premium:active {
			transform: translateY(0);
		}

		.footer-copyright {
			color: #6b7280;
			font-size: 0.75rem;
			margin-top: 1rem;
			font-weight: 400;
		}

		/* Pass group customization */
		.pass-group-custom {
			position: relative;
		}

		.pass-group-custom .toggle-password-custom {
			position: absolute;
			right: 0.9rem;
			top: 50%;
			transform: translateY(-50%);
			color: #9ca3af;
			cursor: pointer;
			font-size: 1rem;
			transition: color 0.2s ease;
		}

		.pass-group-custom .toggle-password-custom:hover {
			color: #f3f4f6;
		}

		/* Info Side (Right) */
		.info-title {
			font-size: 2.3rem;
			font-weight: 800;
			line-height: 1.2;
			margin-bottom: 1rem;
			letter-spacing: -0.5px;
			color: #ffffff;
		}

		.info-tagline {
			color: var(--primary-green);
			font-size: 1.1rem;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 2px;
			margin-bottom: 0.5rem;
		}

		.info-desc {
			color: #9ca3af;
			font-size: 1rem;
			line-height: 1.6;
			margin-bottom: 1.5rem;
			max-width: 600px;
		}

		/* Features grid */
		.feature-card {
			background: rgba(255, 255, 255, 0.02);
			border: 1px solid rgba(255, 255, 255, 0.05);
			border-radius: 12px;
			padding: 0.9rem 1rem;
			transition: all 0.3s ease;
		}

		.feature-card:hover {
			background: rgba(255, 255, 255, 0.04);
			border-color: rgba(16, 185, 129, 0.2);
			transform: translateY(-5px);
		}

		.feature-icon-box {
			width: 32px;
			height: 32px;
			border-radius: 8px;
			background: rgba(16, 185, 129, 0.1);
			color: var(--primary-green);
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 1.1rem;
			margin-bottom: 0.5rem;
		}

		.feature-title {
			font-size: 0.9rem;
			font-weight: 600;
			margin-bottom: 0.25rem;
			color: #f3f4f6;
		}

		.feature-desc {
			font-size: 0.78rem;
			color: #9ca3af;
			line-height: 1.4;
			margin-bottom: 0;
		}

		@media (max-width: 991px) {
			.main-split-container {
				justify-content: center;
				padding: 2rem 0;
			}
			.right-panel {
				display: none !important;
			}
			.left-panel {
				padding: 1rem 1.5rem;
				width: 100%;
				display: flex;
				justify-content: center;
				align-items: center;
			}
		}
	</style>
</head>

<body>
	<!-- Slideshow Background & Tint Overlay -->
	<div class="slideshow-background">
		<div class="slide"></div>
		<div class="slide"></div>
		<div class="slide"></div>
	</div>
	<div class="slideshow-overlay"></div>

	<input type="hidden" id="hidden_rooturl" value="<?php echo $rooturl; ?>" />
	<div id="global-loader" style="display: none;">
		<div class="page-loader"></div>
	</div>

	<div class="container-fluid main-split-container">
		<div class="row w-100 g-0">
			<!-- Left Side: HRM Marketing/Description Content -->
			<div class="col-lg-7 right-panel">
				<div class="my-auto">
					<div class="info-tagline">Karjee Farm</div>
					<h2 class="info-title">Empower Your Workforce & Streamline Operations</h2>
					<div class="row g-4">
						<!-- Feature 1 -->
						<div class="col-sm-6">
							<div class="feature-card">
								<div class="feature-icon-box">
									<i class="ti ti-qrcode"></i>
								</div>
								<h3 class="feature-title">Smart QR Attendance</h3>
								<p class="feature-desc">Real-time attendance scanning with strict checkout buffers and voice verification.</p>
							</div>
						</div>
						<!-- Feature 2 -->
						<div class="col-sm-6">
							<div class="feature-card">
								<div class="feature-icon-box">
									<i class="ti ti-calendar-time"></i>
								</div>
								<h3 class="feature-title">Leave Pipeline</h3>
								<p class="feature-desc">Automatic week-off mapping, date validation, and conditional administrator deletions.</p>
							</div>
						</div>
						<!-- Feature 3 -->
						<div class="col-sm-6">
							<div class="feature-card">
								<div class="feature-icon-box">
									<i class="ti ti-receipt"></i>
								</div>
								<h3 class="feature-title">Calculated Payroll</h3>
								<p class="feature-desc">One-click salary sheets and automated ESI/EPF deductions directly ready for bank transfers.</p>
							</div>
						</div>
						<!-- Feature 4 -->
						<div class="col-sm-6">
							<div class="feature-card">
								<div class="feature-icon-box">
									<i class="ti ti-users"></i>
								</div>
								<h3 class="feature-title">Workforce Directory</h3>
								<p class="feature-desc">Unified master records for active & inactive workers with digital profile histories.</p>
							</div>
						</div>
					</div>
					<p class="info-desc mt-4">
						Welcome to HRM PLUS, a fully integrated Human Resource & payroll management platform tailored to handle smart attendance verification, leave application pipelines, and instant salary calculations.
					</p>
				</div>
			</div>

			<!-- Right Side: Login Card -->
			<div class="col-lg-5 left-panel">
				<div class="login-card shadow-lg">
					<div class="brand-logo-container">
						<img src="assets/img/loginlogo.png" class="img-fluid" alt="Logo">
					</div>
					
					<h2 class="brand-title">Sign In</h2>
					<p class="brand-subtitle">Please enter your credentials to access HR+</p>

					<form method="POST" id="loginForm">
						<div class="text-start mb-3">
							<label class="form-label">Login ID</label>
							<div class="input-group-custom">
								<input type="text" id="txtuserid" placeholder="Enter your Login ID" required autocomplete="username">
								<i class="ti ti-user input-icon"></i>
							</div>
						</div>

						<div class="text-start mb-3">
							<label class="form-label">Password</label>
							<div class="input-group-custom pass-group-custom">
								<input type="password" id="txtpassword" placeholder="Enter your password" required autocomplete="current-password">
								<i class="ti ti-lock input-icon"></i>
								<i class="ti ti-eye-off toggle-password-custom" id="togglePasswordBtn"></i>
							</div>
						</div>

						<button type="submit" id="loginuserbtn" class="btn btn-submit-premium">Sign In</button>
					</form>

					<p class="footer-copyright">Copyright &copy; <?php echo date('Y');?> - HR+</p>
				</div>
			</div>
		</div>
	</div>

	<!-- JavaScript Core -->
	<script src="assets/js/jquery-3.7.1.min.js"></script>
	<script src="assets/js/bootstrap.bundle.min.js"></script>
	<script src="assets/js/feather.min.js"></script>
	<script src="assets/js/script.js"></script>
	<script type="text/javascript" src="js/iziToast.js"></script>
	<script type="text/javascript" src="js/main.js"></script>
	<script type="text/javascript" src="js/actioncontrol.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script type="text/javascript" src="js/login.js"></script>

	<script type="text/javascript">
		// Handle Password Toggle Visibility
		$(document).on("click", "#togglePasswordBtn", function() {
			const passInput = $("#txtpassword");
			if (passInput.attr("type") === "password") {
				passInput.attr("type", "text");
				$(this).removeClass("ti-eye-off").addClass("ti-eye");
			} else {
				passInput.attr("type", "password");
				$(this).removeClass("ti-eye").addClass("ti-eye-off");
			}
		});

		// Submit logic unchanged
		$("#loginForm").on("submit", function(e){
			e.preventDefault();
			checkEmployeeLoginDisplay('hidden_rooturl',$('#txtuserid').val(),$('#txtpassword').val(), 'loginuserbtn');
		});
	</script>
</body>
</html>