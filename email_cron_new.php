<?php
date_default_timezone_set("Asia/Calcutta");
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE );
ini_set('display_errors', '1');
//require('connection.php');
//require('sendemail.php');

function sendemaildireclty(){
	require('connection.php');
require('sendemail.php');
  $soqry="SELECT sendto,ifnull(sendcc,'') as sendcc, ifnull(sendbcc,'') as sendbcc ,m_subject,m_msgbody,sno,ifnull(attch,'') as attch FROM emailsendby_cron  where ifnull(status,'') ='' and mailtype='Company Registration' order by sno desc  ";  
	$res=$conn->query($soqry);
	if($res->num_rows>0){
		while($row=$res->fetch_assoc()){ 
			//print_r($row);
			$sendTo = explode(",", $row['sendto']);
			$sendcc=explode(",", $row['sendcc']);;
			$sendBcc=explode(",", $row['sendbcc']);;
			$m_subject=$row['m_subject'];
			$m_msgbody=$row['m_msgbody'];
			$attch =explode(",", $row['attch']);  
			$response=Sendemailnew($sendTo,$sendcc,$sendBcc,$m_subject,$m_msgbody,$attch = array()); 
			if($response){ 
				mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	    		$conn->begin_transaction();
	    		try{ 
					$updinv = "UPDATE emailsendby_cron set status ='Sent', sentdate = '".date('Y-m-d H:i:s')."' where sno='".$row["sno"]."'";
					$conn->query($updinv); 
					$conn->commit(); 
	    		}
	    		catch (mysqli_sql_exception $e) {
		        	$conn->rollback();
		        	//echo $e;
	    		}	
			}
		}
	}
}
?>