<?php
// Function to get the client IP address
function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTPS_CLIENT_IP'))
        $ipaddress = getenv('HTTPS_CLIENT_IP');
    else if(getenv('HTTPS_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTPS_X_FORWARDED_FOR');
    else if(getenv('HTTPS_X_FORWARDED'))
        $ipaddress = getenv('HTTPS_X_FORWARDED');
    else if(getenv('HTTPS_FORWARDED_FOR'))
        $ipaddress = getenv('HTTPS_FORWARDED_FOR');
    else if(getenv('HTTPS_FORWARDED'))
       $ipaddress = getenv('HTTPS_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}
$client_ip=get_client_ip();
?>
<html>
<head>
<meta http-equiv="refresh" content="120;URL='index.php'" />
<title>
Employee Attendance System
</title>
<script type="text/javascript">
function trim(stringToTrim) 
{ 
    return stringToTrim.replace(/^\s+|\s+$/g,"");
}

</script>

<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {font-family: Arial, Helvetica, sans-serif;}

/* Full-width input fields */
input[type=text], input[type=password] {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    display: inline-block;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

/* Set a style for all buttons */
button {
    background-color: #4CAF50;
    color: white;
    padding: 14px 20px;
    margin: 8px 0;
    border: none;
    cursor: pointer;
    width: 100%;
}

button:hover {
    opacity: 0.8;
}

/* Center the image and position the close button */
.imgcontainer {
    text-align: center;
    margin: 24px 0 12px 0;
    position: relative;
}

img.avatar {
    width: 40%;
    border-radius: 50%;
	height:40%;
}

.container {
    padding: 16px;
}

span.psw {
    float: right;
    padding-top: 16px;
}

/* The Modal (background) */
.modal {
    width: 70%; /* Full width */
    height: 70%; /* Full height */
    overflow: none; /* Enable scroll if needed */
    background-color: rgb(0,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    padding-top: 60px;
    padding-bottom: 60px;
    text-align:left;
}

/* Modal Content/Box */
.modal-content {
    background-color: #fefefe;
    margin: 5% auto 15% auto; /* 5% from the top, 15% from the bottom and centered */
    border: 1px solid #888;
    width: 80%; /* Could be more or less, depending on screen size */
}


/* Add Zoom Animation */
.animate {
    -webkit-animation: animatezoom 0.6s;
    animation: animatezoom 0.6s
}

@-webkit-keyframes animatezoom {
    from {-webkit-transform: scale(0)} 
    to {-webkit-transform: scale(1)}
}
    
@keyframes animatezoom {
    from {transform: scale(0)} 
    to {transform: scale(1)}
}

/* Change styles for span and cancel button on extra small screens */
@media screen and (max-width: 300px) {
    span.psw {
       display: block;
       float: none;
    }
    .cancelbtn {
       width: 100%;
    }
}
</style>

</head>
<body bgcolor='darkgreen' onload="getLocation()">
<script>
var curlat;
var curlong;


function getLocation() {
document.getElementById("submit").style.display="none";
document.getElementById("locmsg").style.display="block";
    if (navigator.geolocation) {
	navigator.geolocation.getCurrentPosition(showPosition);
    }
}
function showPosition(position,showError) {
curlat=position.coords.latitude.toFixed(8);
curlong=position.coords.longitude.toFixed(8);
document.getElementById("attloc").value = curlat+"|"+curlong;
detectmobile();
showdistance();
}
function showError(error) {
  switch(error.code) {
    case error.PERMISSION_DENIED:
      document.getElementById("locmsg").innerHTML = "User denied the request for Geolocation."
      break;
    case error.POSITION_UNAVAILABLE:
      document.getElementById("locmsg").innerHTML = "Location information is unavailable."
      break;
    case error.TIMEOUT:
      document.getElementById("locmsg").innerHTML = "The request to get user location timed out."
      break;
    case error.UNKNOWN_ERROR:
      document.getElementById("locmsg").innerHTML = "An unknown error occurred."
      break;
  }
}
function getDistanceFromLatLonInKm(lat1,lon1,lat2,lon2) {
    var theta = lon1 - lon2;
    var miles = (Math.sin(deg2rad(lat1)) * Math.sin(deg2rad(lat2))) + (Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * Math.cos(deg2rad(theta)));
    miles = Math.acos(miles);
    miles = rad2deg(miles);
    miles = miles * 60 * 1.1515;
    var kilometers = miles * 1.609344;
    return kilometers;
}

function rad2deg(radians)
{
  return radians * (180/Math.PI);
}

function deg2rad(deg) {
  return deg * (Math.PI/180)
}

function detectmobile(){
   var agnt = navigator.userAgent;
   var isMobile = {
    Android: function() {
        return agnt.match(/Android/i);
    },
    BlackBerry: function() {
        return agnt.match(/BlackBerry/i);
    },
    iOS: function() {
        return agnt.match(/iPhone|iPad|iPod/i);
    },
    Opera: function() {
        return agnt.match(/Opera Mini/i);
    },
    Windows: function() {
        return agnt.match(/IEMobile/i) || navigator.userAgent.match(/WPDesktop/i);
    },
    any: function() {
        return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
    }
};

	if (isMobile.any()){
	document.getElementById("sbro").value = "Mobile";
	}
	else	{
	document.getElementById("sbro").value = "Desktop";
	}
}

function showdistance()
{
var cenlat = '28.442624';
var cenlong = '77.0408448';
var dist = getDistanceFromLatLonInKm(curlat,curlong,cenlat,cenlong);
document.getElementById("distmsg").innerHTML="You seem to be "+dist.toFixed(0)+" Kms. away from the office!!";
document.getElementById("submit").style.display="block";
document.getElementById("locmsg").style.display="none";
}

   
</script>

<fieldset style='background-color:lightgreen;border: 1px solid darkgreen;'>
<center>
<b><font size=6>Karjee Farm</font></b></center>
</fieldset>
<fieldset style='background-color:darkgreen;border: 0px;'>
<center><b><font size=5 color='yellow'>Employee Daily Attendance System</font></b></center>
</fieldset>
<fieldset style='background-color:darkgray;border: 1px solid darkgreen;height:73%;padding-top:35px;padding-bottom:35px;'>
<center>
<div  id="id01" class="modal">
<center><p id="locmsg" style="color:red;">Pl. switch on location</p><p id="distmsg" style="color:blue;"></p></center>

<form class="modal-content animate" action="login.php" method="post">
     <div class="container">
      <label for="loginid"><b>Login ID</b></label>
      <input type="text" placeholder="Enter Login id" name="loginid" id="loginid" required>

      <label for="loginpassword"><b>Password</b></label>
      <input type="password" placeholder="Enter Password" name="loginpassword" id="loginpassword" required>
      <input type="hidden" name="attloc" id="attloc">
      <input type="hidden" name="centrelat" id="centrelat" value="<?php echo $latitude;?>">
      <input type="hidden" name="centrelong" id="centrelong" value="<?php echo $longitude;?>">

      <input type="hidden" name="jdist" id="jdist">
      <input type="hidden" name="cip" id="cip"  value="<?php echo $client_ip;?>">
      <input type="hidden" name="sbro" id="sbro">
      <button type="submit" id="submit">Login</button>
    </div>
</form>
</div>
</center> 
</fieldset>
</body>
</html>