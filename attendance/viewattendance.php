<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
$centre_data=parse_ini_file("../ini/confignew.ini");
$key=session_id();

$emp = $_GET['emp'];

$attendancemonth = $_GET['mth'];
$centreid=$_GET['centreid'];
//$identifier = $centre_data['name'];
$servername = $centre_data['servername'];
$username = $centre_data['username'];
$password = $centre_data['password'];
$dbname = $centre_data['attdbname'];


$conn = mysqli_connect($servername, $username, convert_uudecode($password), $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$sql="SELECT centreid,name,latitude,longitude FROM tblcentreloc where centreid='".$centreid."'"; 
$result=mysqli_query($conn, $sql) or die('Query Failed.'.mysqli_error($conn));

if (mysqli_num_rows($result)>0)
  {  
  $row1 = mysqli_fetch_array($result);
  $centrename=$row1["name"];
  }

function convertTime($dec)
{
    // start by converting to seconds
    $seconds = ($dec * 3600);
    // we're given hours, so let's get those the easy way
    $hours = floor($dec);
    // since we've "calculated" hours, let's remove them from the seconds variable
    $seconds -= $hours * 3600;
    // calculate minutes left
    $minutes = floor($seconds / 60);
    // remove those from seconds as well
    $seconds -= $minutes * 60;
    // return the time formatted HH:MM:SS
    return lz($hours)." H ".lz($minutes)." M ";
}

// lz = leading zero
function lz($num)
{
    return (strlen($num) < 2) ? "0{$num}" : $num;
}
?>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> -->
<link rel="stylesheet" href="../css/w3.css">
</head>
<body>

<center>
<fieldset style='background-color:lightgreen;border: 0px solid darkgreen;width:99%'>
<center>
<b><font size=6><?php echo $centrename;?></font></b></center>
</fieldset>
</center>
<fieldset style='background-color:darkgreen;border: 0px;width:99%'>
<center><b><font size=5 color='yellow'>Employee Daily Attendance System</font></b></center>
</fieldset>
<!--<fieldset style='background-color:darkgreen;border: 1px solid darkgreen;height:75%;padding-top:5px;padding-bottom:5%;'>-->
<?php
$sql = "SELECT attendancedate,ifnull(timein,'') as timein,ifnull(timeout,'') as timeout,ROUND(duration/60/60,2) as duration FROM attendance where employeeid='".$emp."' and month(attendancedate)='".$attendancemonth."' and year(attendancedate)='".date('Y')."' and centreid='".$centreid."' order by attendancedate";
	$result=mysqli_query($conn, $sql) or die('Query Failed.'.mysqli_error($conn));
	if(mysqli_num_rows($result)>0)
	{
?>
<div class="w3-display-container w3-grey">
<table class="w3-table w3-striped  w3-display-topmiddle">
<tr> <th> S.No </th> <th> Attendance Date </th> <th> Time In </th> <th> Time Out </th><th> Duration </th></tr>
<?php
$i=1;
		while($row=mysqli_fetch_assoc($result))
		{
		$timein=$row["timein"]=="" ?" ":date('h:i a',strtotime($row["timein"]));
		$timeout=$row["timeout"]=="" ?" ":date('h:i a',strtotime($row["timeout"]));
		$duration=$row["duration"];	
		echo "<tr> <td> ".$i." </td> <td> " .date('d-m-Y',strtotime($row["attendancedate"])). " </td> <td> " .$timein. " </td> <td> " .$timeout. " </td><td> " .convertTime($duration). " </td></tr>";
		$i++;
		}
	}
mysqli_close($conn);
?>
</table>
</div>
<!--</fieldset>-->
</body>
</html>

