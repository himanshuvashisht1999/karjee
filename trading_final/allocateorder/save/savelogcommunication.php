<?php 
date_default_timezone_set("Asia/Calcutta");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); 
function savelog_phone($conn,$projectid,$clientid,$stockorderno,$psono,$productid, $productname,$recipientname,$contactno,$communicationmessage,$communicationby,$questiontype,$communication_date,$questionno,$question,$answer,$saleqty,$emailto="",$emailcc="",$emailsubject="",$emailattach="",$mainposono="",$mainstokorder="",$mainproducctid="",$mainproductname=""){ 

      $sql1 = "INSERT INTO krf_tbl_communications (projectid, clientid, stockorderno,  psono, productid, productname, recipient_name, contact_no,message, communicationby, communication_type,communication_date,questionno,question,answer,saleqty ,emailto,emailcc,emailsub,attachment ) VALUES ('$projectid', '$clientid', '$stockorderno', '$psono' , '{$productid}' , '{$productname}' , '" . addslashes($recipientname) . "','$contactno', '" . addslashes($communicationmessage) . "', '$communicationby' ,'{$questiontype}','{$communication_date}','" . addslashes($questionno) . "','{$question}','{$answer}','{$saleqty}'  ,'".addslashes($emailto)."','".addslashes($emailcc)."','".addslashes($emailsubject)."','".addslashes($emailattach)."')";
     $conn->query($sql1);
     return true; 
}
?>
 
 