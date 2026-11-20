<?php
function Sendemailnew_t($sendto,$sendcc,$sendbcc,$m_subject,$m_msgbody,$attch = array())
{

	require_once "PHPMailer/PHPMailerAutoload.php";

	$mail = new PHPMailer;
	//$mail->SMTPDebug = 2;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'amanaccounting.in';                    // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'documents@amanaccounting.in';                     // SMTP username
    $mail->Password   = (require __DIR__ . '/mail_config.php')['password'];                               // SMTP password
    $mail->SMTPSecure = 'tls';         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    $mail->Port       = 587;    
	
	$mail->From = "documents@amanaccounting.in";
	$mail->FromName = "Aman Accounting";

	
	foreach ($sendto as $receiver)
	{
		$mail->addAddress($receiver,$receiver);
	}
	
	if($sendcc){
		foreach ($sendcc as $ccreceiver)
		{
			$mail->addAddress($ccreceiver,$ccreceiver);
		}
	}
	
	if($sendbcc){
		foreach ($sendbcc as $bccreceiver)
		{
			$mail->addAddress($bccreceiver,$bccreceiver);
		}
	}
	if($attch)
	{
		foreach ($attch as $value)
		{
			$displayname=basename($value);
			$mail->AddAttachment($value,$displayname);
		}
	}
	
	$mail->isHTML(true);

	$mail->Subject = $m_subject;
	
	$mail->Body = $m_msgbody;

	if(!$mail->send()) 
	{  
		return false;
	} 
	else 
	{ 
		return true;
	}
}
?>


