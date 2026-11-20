<!DOCTYPE html>
<html lang="en">
<head>
	<title>Login::Aman Accounting System</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="css/vendor/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="css/vendor/animate/animate.css">
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="css/main.css">
	<link rel="stylesheet" type="text/css" href="css/iziToast.css">
</head>
<?php 
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
<input type="hidden" id="hidden_rooturl" value="<?php echo $rooturl; ?>" />
<body style="background-color: '#4E82A5';">
	<div id="div_1" class="d-none">
		<section>
      <div class="" style="margin-top:40px;">
        <div class="row text-center ">
          <div class="col-sm-12">
            <h1 class=" mb0 text-black">WELCOME</h1><br>
						<h3 class="text-black thin">AMAN ACCOUNTING MANAGEMENT SYSTEM </h3>
					</div>
				</div>
			</div>
			<br><br><br>
			<div class="shade11">
				<div class="w3-row w3-center">
					<h4 class="w3- uppercase bold" ></h4>
        </div>
        <div class="w3-row">
        	<div class="w3-col m4">&nbsp;</div>
        	<div class="w3-col m4">
        		<div class="w3-margin-top w3-padding" >
							<div class="" style="padding:5px;"></div>
						</div>
        	</div>
        	<div class="w3-col m4">&nbsp;</div>
        </div>	                
			</div>
    </section>
	</div>
	<div class="limiter d-none" id="choose_div">
		<div class="container-login100-form-btn">
			<div class="p5">
				<button class="login100-form-btn" type="button" id="btn_loginpage" style="width:258px;">Login</button>

				<button class="login100-form-btn mt-4" type="button" id="btn_noninttasklogin" style="width:258px;">Non Integrated Task Module Login</button> 
			</div>
		</div>
	</div>	
	<div class="limiter" id="login_div">
		<div class="container-login100">
			<div class="wrap-login100" style="background-image: url('<?php echo $rooturl; ?>img/raggingposter.png') no-repeat;">
				<div class="login100-pic js-tilt" data-tilt>
					<!-- <img src="images/img-01.png" alt="IMG"> -->
					<img src="<?php echo $rooturl; ?>/img/raggingposter.png" alt="IMG">
				</div>
				<form class="login100-form validate-form" method="POST" id="loginForm">
					<span class="login100-form-title">
						L O G I N
					</span>
					<div class="wrap-input100 validate-input">
						<input class="input100" type="text" name="useruid" id="txtuserid" placeholder="User Id" autocomplete="off">
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-envelope" aria-hidden="true"></i>
						</span>
					</div>
					<div class="wrap-input100 validate-input" data-validate = "Password is required">
						<input class="input100" type="password" name="pass" id="txtpassword" placeholder="Password" autocomplete="off">
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-lock" aria-hidden="true"></i>
						</span>
					</div>					
					<div class="container-login100-form-btn">
						<button class="login100-form-btn" type="submit" id="loginuserbtn">
							Login
						</button>
					</div>
					<div class="text-center p-t-12">
						<span class="txt1">
							Forgot
						</span>
						<a class="txt2" href="#">
							Username / Password (contact to your web administrator)?
						</a>
					</div>
				</form>
			</div>
		</div>
	</div>
	<script type="text/javascript" src="../vendor/jquery/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="../vendor/bootstrap/js/popper.js"></script>
	<script type="text/javascript" src="../vendor/bootstrap/js/bootstrap.min.js"></script>
	<!-- <script type="text/javascript" src="../vendor/select2/select2.min.js"></script> -->
	<script type="text/javascript" src="../vendor/tilt/tilt.jquery.min.js"></script>
	<script type="text/javascript" src="../js/iziToast.js"></script>
	<script type="text/javascript" src="../js/main.js"></script>
	<script type="text/javascript" src="../js/js_selft/masters.js"></script>
	<script type="text/javascript" src="../js/js_selft/admindashboard.js"></script>
	<script type="text/javascript" src="../js/actioncontrol.js"></script>
	<script >
		$('.js-tilt').tilt({
			scale: 1.1
		})
	</script>
</body>
</html>
<script type="text/javascript">
$("#loginForm").on("submit", function(e){
		e.preventDefault();
		 
	checkLogin('hidden_rooturl',$('#txtuserid').val(),$('#txtpassword').val(), 'loginuserbtn');
});
</script>