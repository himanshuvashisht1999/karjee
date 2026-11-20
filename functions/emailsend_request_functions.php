<?php
// function sendemail_AfterCreateRequestForApproval($authconn, $conn, $companyid, $subcompanyid, $projectid, $requestno, $routurl)
// {
//     try {
//         // 🟦 Step 0: Fetch project details
//         $select_prt = "SELECT projectname, project_email FROM tbl_project_master WHERE projectid = ?";
//         $stmt0 = $conn->prepare($select_prt);
//         $stmt0->bind_param("s", $projectid);
//         $stmt0->execute();
//         $prjqueryresult = $stmt0->get_result();
//         if ($prjqueryresult->num_rows === 0) {
//             return 'No project found.';
//         }

//         $prjrow = $prjqueryresult->fetch_assoc();
//         $projectname = $prjrow['projectname'];
//         $projectEmail = $prjrow['project_email'] ? "✉ " . $prjrow['project_email'] . "<br>" : "";
//         $projectContactno = "";

//         // 🟦 Step 1: Fetch request details
//         $select_req = "SELECT companyid, projectid, request_date, request_no, requested_by, payto, paytoid, request_amount, purpose_type 
//                        FROM tbl_paymentrequest 
//                        WHERE (tedit IS NULL OR tedit = '') AND projectid = ? AND request_no = ?";
//         $stmt1 = $conn->prepare($select_req);
//         $stmt1->bind_param("ss", $projectid, $requestno);
//         $stmt1->execute();
//         $reqresult = $stmt1->get_result();
//         if ($reqresult->num_rows === 0) {
//             return 'No such request found.';
//         }

//         $reqrow = $reqresult->fetch_assoc();
//         $requested_by = $reqrow['requested_by'];
//         $request_date = date('d-M-Y', strtotime($reqrow['request_date']));
//         $payto = $reqrow['payto'];
//         $request_amount = number_format($reqrow['request_amount'], 2);
//         $purpose_type = $reqrow['purpose_type'];

//         // 🟦 Step 2: Fetch multiple approvers’ details
//         $sql_approvers = "
//             SELECT 
//                 GROUP_CONCAT(DISTINCT u.loginname ORDER BY u.loginname ASC) AS approver_names,
//                 GROUP_CONCAT(DISTINCT u.emailid ORDER BY u.emailid ASC) AS approver_emails
//             FROM 
//                 login_modules_master_client AS c
//             JOIN 
//                 login_authorized_modules AS m 
//                 ON c.projectid = m.projectid 
//                 AND c.moduleid = m.moduleid 
//                 AND m.status = 'active'
//             JOIN 
//                 login_user_master AS u 
//                 ON m.loginid = u.loginid
//             WHERE 
//                 c.projectid = ?
//                 AND c.modulefoldername LIKE '%pending_approval.php'
//             GROUP BY 
//                 c.projectid
//         ";

//         $stmt2 = $authconn->prepare($sql_approvers);
//         $stmt2->bind_param("s", $projectid);
//         $stmt2->execute();
//         $approver_result = $stmt2->get_result();
//         if ($approver_result->num_rows === 0) {
//             return 'No approvers found.';
//         }

//         $approvers = $approver_result->fetch_assoc();
//         $approver_names = $approvers['approver_names'];
//         $approver_emails = explode(',', $approvers['approver_emails']);

//         // 🟦 Step 3: Fetch breakdown details
//         $psoprd = "SELECT beneficiery_name, ammount, purpose, description 
//                    FROM tbl_paymentrequest_breakdown 
//                    WHERE (tedit IS NULL OR tedit = '') AND projectid = ? AND request_no = ?";
//         $stmt3 = $conn->prepare($psoprd);
//         $stmt3->bind_param("ss", $projectid, $requestno);
//         $stmt3->execute();
//         $prdresult = $stmt3->get_result();

//         $prdresult_detail = '';
//         $TotalAmount = 0;
//         $sno = 1;
//         while ($br = $prdresult->fetch_assoc()) {
//             $TotalAmount += $br['ammount'];
//             $prdresult_detail .= "
//                 <tr>
//                     <td style='border:1px solid #ccc;padding:8px;text-align:center;'>{$sno}</td>
//                     <td style='border:1px solid #ccc;padding:8px;'>{$br['beneficiery_name']}</td>
//                     <td style='border:1px solid #ccc;padding:8px;text-align:right;'>₹" . number_format($br['ammount'], 2) . "</td>
//                     <td style='border:1px solid #ccc;padding:8px;text-align:center;'>{$br['purpose']}</td>
//                     <td style='border:1px solid #ccc;padding:8px;text-align:left;'>{$br['description']}</td>
//                 </tr>";
//             $sno++;
//         }

//         // 🟦 Step 4: Prepare email body
//         $m_msgbody = "
//         <!DOCTYPE html>
//         <html>
//         <body style='font-family: Arial, sans-serif; color: #333;'>
//           <div style='max-width: 1050px; margin: auto; border: 1px solid #ddd; border-radius: 10px; padding: 20px;'>
            
//             <h2 style='color: #007BFF; text-align:center;'>Payment Request Created for Approval</h2>
            
//             <p>Dear <strong>{$approver_names}</strong>,</p>
            
//             <p>A new payment request (<strong>{$requestno}</strong>) has been created for the project <strong>{$projectname}</strong> on <strong>{$request_date}</strong>.</p>

//             <p>Please review and approve the request as soon as possible.</p>

//             <table style='width:100%; border-collapse: collapse; margin: 20px 0;'>
//               <tr><td style='border: 1px solid #ccc; padding: 8px;'>Request No</td><td style='border: 1px solid #ccc; padding: 8px;'>{$requestno}</td></tr>
//               <tr><td style='border: 1px solid #ccc; padding: 8px;'>Requested By</td><td style='border: 1px solid #ccc; padding: 8px;'>{$requested_by}</td></tr>
//               <tr><td style='border: 1px solid #ccc; padding: 8px;'>Pay To</td><td style='border: 1px solid #ccc; padding: 8px;'>{$payto}</td></tr>
//               <tr><td style='border: 1px solid #ccc; padding: 8px;'>Request Date</td><td style='border: 1px solid #ccc; padding: 8px;'>{$request_date}</td></tr>
//               <tr><td style='border: 1px solid #ccc; padding: 8px;'>Request Amount</td><td style='border: 1px solid #ccc; padding: 8px;'>₹{$request_amount}</td></tr>
//             </table>

//             <h4 style='margin-top:20px;'>Breakdown Details:</h4>
//             <table style='width:100%; border-collapse: collapse; margin: 10px 0;'>
//               <thead>
//                 <tr>
//                   <th style='border:1px solid #ccc;padding:8px;'>S.No</th>
//                   <th style='border:1px solid #ccc;padding:8px;'>Beneficiary Name</th>
//                   <th style='border:1px solid #ccc;padding:8px;'>Amount</th>
//                   <th style='border:1px solid #ccc;padding:8px;'>Purpose</th>
//                   <th style='border:1px solid #ccc;padding:8px;'>Description</th>
//                 </tr>
//               </thead>
//               <tbody>
//                 {$prdresult_detail}
//               </tbody>
//             </table>
//             <hr style='border:none; border-top:1px solid #ddd; margin:20px 0;'>
//             <p style='font-size: 14px; color: #777;'>
//               Best Regards,<br>
//               <strong>{$projectname}</strong><br>
//               {$projectEmail}
//               {$projectContactno}
//             </p>
//           </div>
//         </body>
//         </html>";

//         // 🟦 Step 5: Send Email
//         $sendto = $approver_emails;
//         $sendcc = [];
//         $sendbcc = [];
//         $subject = "Payment Request {$requestno} - Approval Required for {$projectname}";

//         if (sendemailfrommailer_email($sendto, $sendcc, $sendbcc, $subject, $m_msgbody, [])) {
//             return 'sent';
//         } else {
//             return 'notsent';
//         }

//     } catch (Exception $e) {
//         return 'Error: ' . $e->getMessage();
//     }
// }

function sendemail_AfterCreateRequestForApproval($authconn, $conn, $companyid, $subcompanyid, $projectid, $requestno, $routurl)
{
    try {
        // 🟦 Step 0: Fetch project details
        $select_prt = "SELECT projectname, project_email FROM tbl_project_master WHERE projectid = ?";
        $stmt0 = $conn->prepare($select_prt);
        $stmt0->bind_param("s", $projectid);
        $stmt0->execute();
        $prjqueryresult = $stmt0->get_result();
        if ($prjqueryresult->num_rows === 0) {
            return 'No project found.';
        }

        $prjrow = $prjqueryresult->fetch_assoc();
        $projectname = $prjrow['projectname'];
        $projectEmail = $prjrow['project_email'] ? "✉ " . $prjrow['project_email'] . "<br>" : "";
        $projectContactno = "";

        // 🟦 Step 1: Fetch request details
        $select_req = "SELECT companyid, projectid, request_date, request_no, requested_by, payto, paytoid, request_amount, purpose_type 
                       FROM tbl_paymentrequest 
                       WHERE (tedit IS NULL OR tedit = '') AND projectid = ? AND request_no = ?";
        $stmt1 = $conn->prepare($select_req);
        $stmt1->bind_param("ss", $projectid, $requestno);
        $stmt1->execute();
        $reqresult = $stmt1->get_result();
        if ($reqresult->num_rows === 0) {
            return 'No such request found.';
        }

        $reqrow = $reqresult->fetch_assoc();
        $requested_by = $reqrow['requested_by'];
        $request_date = date('d-M-Y', strtotime($reqrow['request_date']));
        $payto = $reqrow['payto'];
        $request_amount = number_format($reqrow['request_amount'], 2);
        $purpose_type = $reqrow['purpose_type'];

        // 🟦 Step 2: Fetch multiple approvers’ details
        $sql_approvers = "
            SELECT 
                GROUP_CONCAT(DISTINCT u.loginname ORDER BY u.loginname ASC) AS approver_names,
                GROUP_CONCAT(DISTINCT u.emailid ORDER BY u.emailid ASC) AS approver_emails
            FROM 
                login_modules_master_client AS c
            JOIN 
                login_authorized_modules AS m 
                ON c.projectid = m.projectid 
                AND c.moduleid = m.moduleid 
                AND m.status = 'active'
            JOIN 
                login_user_master AS u 
                ON m.loginid = u.loginid
            WHERE 
                c.projectid = ?
                AND c.modulefoldername LIKE '%pending_approval.php%'
            GROUP BY 
                c.projectid
        ";

        $stmt2 = $authconn->prepare($sql_approvers);
        $stmt2->bind_param("s", $projectid);
        $stmt2->execute();
        $approver_result = $stmt2->get_result();
        if ($approver_result->num_rows === 0) {
            return 'No approvers found.';
        }

        $approvers = $approver_result->fetch_assoc();
        $approver_names = $approvers['approver_names'];
        $approver_emails = explode(',', $approvers['approver_emails']);

        // 🟦 Step 3: Fetch breakdown details
        $psoprd = "SELECT beneficiery_name, ammount, purpose, description 
                   FROM tbl_paymentrequest_breakdown 
                   WHERE (tedit IS NULL OR tedit = '') AND projectid = ? AND request_no = ?";
        $stmt3 = $conn->prepare($psoprd);
        $stmt3->bind_param("ss", $projectid, $requestno);
        $stmt3->execute();
        $prdresult = $stmt3->get_result();

        $prdresult_detail = '';
        $TotalAmount = 0;
        $sno = 1;
        while ($br = $prdresult->fetch_assoc()) {
            $TotalAmount += $br['ammount'];
            $prdresult_detail .= "
                <tr>
                    <td style='border:1px solid #ccc;padding:8px;text-align:center;'>{$sno}</td>
                    <td style='border:1px solid #ccc;padding:8px;'>{$br['beneficiery_name']}</td>
                    <td style='border:1px solid #ccc;padding:8px;text-align:right;'>₹" . number_format($br['ammount'], 2) . "</td>
                    <td style='border:1px solid #ccc;padding:8px;text-align:center;'>{$br['purpose']}</td>
                    <td style='border:1px solid #ccc;padding:8px;text-align:left;'>{$br['description']}</td>
                </tr>";
            $sno++;
        }

        // 🟦 Step 4: Prepare improved email body
        $m_msgbody = "
        <!DOCTYPE html>
        <html>
        <body style='font-family: Arial, sans-serif; color: #333;'>
          <div style='max-width: 1050px; margin: auto; border: 1px solid #ddd; border-radius: 10px; padding: 20px;'>
            
            <h2 style='color: #007BFF; text-align:center;'>New Payment Request Submitted for Approval</h2>
            
            <p>Dear <strong>{$approver_names}</strong>,</p>
            
            <p>A new payment request <strong>#{$requestno}</strong> has been submitted under the project <strong>{$projectname}</strong> on <strong>{$request_date}</strong> by <strong>{$requested_by}</strong>.</p>

            <p>Please review and approve this request at your earliest convenience.</p>

            <table style='width:100%; border-collapse: collapse; margin: 20px 0;'>
              <tr><td style='border: 1px solid #ccc; padding: 8px;'>Request No</td><td style='border: 1px solid #ccc; padding: 8px;'>{$requestno}</td></tr>
              <tr><td style='border: 1px solid #ccc; padding: 8px;'>Requested By</td><td style='border: 1px solid #ccc; padding: 8px;'>{$requested_by}</td></tr>
              <tr><td style='border: 1px solid #ccc; padding: 8px;'>Pay To</td><td style='border: 1px solid #ccc; padding: 8px;'>{$payto}</td></tr>
              <tr><td style='border: 1px solid #ccc; padding: 8px;'>Request Date</td><td style='border: 1px solid #ccc; padding: 8px;'>{$request_date}</td></tr>
              <tr><td style='border: 1px solid #ccc; padding: 8px;'>Request Amount</td><td style='border: 1px solid #ccc; padding: 8px;'>₹{$request_amount}</td></tr>
            </table>

            <h4 style='margin-top:20px;'>Breakdown Details:</h4>
            <table style='width:100%; border-collapse: collapse; margin: 10px 0;'>
              <thead>
                <tr>
                  <th style='border:1px solid #ccc;padding:8px;'>S.No</th>
                  <th style='border:1px solid #ccc;padding:8px;'>Beneficiary Name</th>
                  <th style='border:1px solid #ccc;padding:8px;'>Amount</th>
                  <th style='border:1px solid #ccc;padding:8px;'>Purpose</th>
                  <th style='border:1px solid #ccc;padding:8px;'>Description</th>
                </tr>
              </thead>
              <tbody>
                {$prdresult_detail}
              </tbody>
            </table>

            <p style='margin-top:20px;text-align:center;'>
              <a href='{$routurl}' style='background:#007BFF;color:#fff;text-decoration:none;padding:10px 20px;border-radius:5px;'>Review & Approve</a>
            </p>

            <hr style='border:none; border-top:1px solid #ddd; margin:20px 0;'>
            <p style='font-size: 14px; color: #777;'>
              Best Regards,<br>
              <strong>{$projectname}</strong><br>
              {$projectEmail}
              {$projectContactno}
            </p>
          </div>
        </body>
        </html>";

        // 🟦 Step 5: Send Email
        $sendto = $approver_emails;
        $sendcc = [$requested_by]; // ✅ CC to requester
        $sendbcc = [];
        $subject = "Approval Required: New Payment Request {$requestno} - {$projectname}";

        if (sendemailfrommailer_email($sendto, $sendcc, $sendbcc, $subject, $m_msgbody, [])) {
            return 'sent';
        } else {
            return 'notsent';
        }

    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}


function sendemail_AfterApprovalRequestForPaymentUpdate($authconn, $conn, $companyid, $subcompanyid, $projectid, $requestno, $routurl)
{
    try {
        // 🟦 Step 0: Fetch project details
        $select_prt = "SELECT projectname, project_email FROM tbl_project_master WHERE projectid = ?";
        $stmt0 = $conn->prepare($select_prt);
        $stmt0->bind_param("s", $projectid);
        $stmt0->execute();
        $prjqueryresult = $stmt0->get_result();
        if ($prjqueryresult->num_rows === 0) {
            return 'No project found.';
        }

        $prjrow = $prjqueryresult->fetch_assoc();
        $projectname = $prjrow['projectname'];
        $projectEmail = $prjrow['project_email'] ? "✉ " . $prjrow['project_email'] . "<br>" : "";
        $projectContactno = "";

        // 🟦 Step 1: Fetch request details
        $select_req = "SELECT companyid, projectid, request_date, request_no, requested_by, payto, paytoid, request_amount, purpose_type 
                       FROM tbl_paymentrequest 
                       WHERE (tedit IS NULL OR tedit = '') AND projectid = ? AND request_no = ?";
        $stmt1 = $conn->prepare($select_req);
        $stmt1->bind_param("ss", $projectid, $requestno);
        $stmt1->execute();
        $reqresult = $stmt1->get_result();
        if ($reqresult->num_rows === 0) {
            return 'No such request found.';
        }

        $reqrow = $reqresult->fetch_assoc();
        $requested_by = $reqrow['requested_by'];
        $request_date = date('d-M-Y', strtotime($reqrow['request_date']));
        $payto = $reqrow['payto'];
        $request_amount = number_format($reqrow['request_amount'], 2);
        $purpose_type = $reqrow['purpose_type'];

        // 🟦 Step 2: Fetch multiple approvers (payment transfer module)
        $sql_approvers = "
            SELECT 
                GROUP_CONCAT(DISTINCT u.loginname ORDER BY u.loginname ASC) AS approver_names,
                GROUP_CONCAT(DISTINCT u.emailid ORDER BY u.emailid ASC) AS approver_emails
            FROM 
                login_modules_master_client AS c
            JOIN 
                login_authorized_modules AS m 
                ON c.projectid = m.projectid 
                AND c.moduleid = m.moduleid 
                AND m.status = 'active'
            JOIN 
                login_user_master AS u 
                ON m.loginid = u.loginid
            WHERE 
                c.projectid = ?
                AND c.modulefoldername LIKE '%paymenttransfermodule/index.php%'
            GROUP BY 
                c.projectid
        ";

        $stmt2 = $authconn->prepare($sql_approvers);
        $stmt2->bind_param("s", $projectid);
        $stmt2->execute();
        $approver_result = $stmt2->get_result();
        if ($approver_result->num_rows === 0) {
            return 'No approvers found.';
        }

        $approvers = $approver_result->fetch_assoc();
        $approver_names = $approvers['approver_names'];
        $approver_emails = explode(',', $approvers['approver_emails']);

        // 🟦 Step 3: Fetch breakdown details
        $psoprd = "SELECT beneficiery_name, ammount, purpose, description 
                   FROM tbl_paymentrequest_breakdown 
                   WHERE (tedit IS NULL OR tedit = '') AND projectid = ? AND request_no = ?";
        $stmt3 = $conn->prepare($psoprd);
        $stmt3->bind_param("ss", $projectid, $requestno);
        $stmt3->execute();
        $prdresult = $stmt3->get_result();

        $prdresult_detail = '';
        $TotalAmount = 0;
        $sno = 1;
        while ($br = $prdresult->fetch_assoc()) {
            $TotalAmount += $br['ammount'];
            $prdresult_detail .= "
                <tr>
                    <td style='border:1px solid #ccc;padding:8px;text-align:center;'>{$sno}</td>
                    <td style='border:1px solid #ccc;padding:8px;'>{$br['beneficiery_name']}</td>
                    <td style='border:1px solid #ccc;padding:8px;text-align:right;'>₹" . number_format($br['ammount'], 2) . "</td>
                    <td style='border:1px solid #ccc;padding:8px;text-align:center;'>{$br['purpose']}</td>
                    <td style='border:1px solid #ccc;padding:8px;text-align:left;'>{$br['description']}</td>
                </tr>";
            $sno++;
        }

        // 🟦 Step 4: Prepare email body (After Approval Message)
        $m_msgbody = "
        <!DOCTYPE html>
        <html>
        <body style='font-family: Arial, sans-serif; color: #333;'>
          <div style='max-width: 1050px; margin: auto; border: 1px solid #ddd; border-radius: 10px; padding: 20px;'>
            
            <h2 style='color: #28a745; text-align:center;'>Payment Request Approved - Update Payment Transfer Details</h2>
            
            <p>Dear <strong>{$approver_names}</strong>,</p>
            
            <p>The payment request <strong>{$requestno}</strong> for the project <strong>{$projectname}</strong> has been <strong>approved</strong> on <strong>{$request_date}</strong>.</p>

            <p>Please proceed to update the <strong>payment transfer details</strong> in the system.</p>

            <table style='width:100%; border-collapse: collapse; margin: 20px 0;'>
              <tr><td style='border: 1px solid #ccc; padding: 8px;'>Request No</td><td style='border: 1px solid #ccc; padding: 8px;'>{$requestno}</td></tr>
              <tr><td style='border: 1px solid #ccc; padding: 8px;'>Requested By</td><td style='border: 1px solid #ccc; padding: 8px;'>{$requested_by}</td></tr>
              <tr><td style='border: 1px solid #ccc; padding: 8px;'>Pay To</td><td style='border: 1px solid #ccc; padding: 8px;'>{$payto}</td></tr>
              <tr><td style='border: 1px solid #ccc; padding: 8px;'>Request Date</td><td style='border: 1px solid #ccc; padding: 8px;'>{$request_date}</td></tr>
              <tr><td style='border: 1px solid #ccc; padding: 8px;'>Approved Amount</td><td style='border: 1px solid #ccc; padding: 8px;'>₹{$request_amount}</td></tr>
            </table>

            <h4 style='margin-top:20px;'>Breakdown Details:</h4>
            <table style='width:100%; border-collapse: collapse; margin: 10px 0;'>
              <thead>
                <tr>
                  <th style='border:1px solid #ccc;padding:8px;'>S.No</th>
                  <th style='border:1px solid #ccc;padding:8px;'>Beneficiary Name</th>
                  <th style='border:1px solid #ccc;padding:8px;'>Amount</th>
                  <th style='border:1px solid #ccc;padding:8px;'>Purpose</th>
                  <th style='border:1px solid #ccc;padding:8px;'>Description</th>
                </tr>
              </thead>
              <tbody>
                {$prdresult_detail}
              </tbody>
            </table>

            <p>You can update payment transfer details by clicking the link below:</p>
            <p><a href='{$routurl}' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Update Payment Transfer</a></p>

            <hr style='border:none; border-top:1px solid #ddd; margin:20px 0;'>
            <p style='font-size: 14px; color: #777;'>
              Best Regards,<br>
              <strong>{$projectname}</strong><br>
              {$projectEmail}
              {$projectContactno}
            </p>
          </div>
        </body>
        </html>";

        // 🟦 Step 5: Send Email
        $sendto = $approver_emails;
        $sendcc = [];
        $sendbcc = [];
        $subject = "Payment Request {$requestno} Approved - Update Payment Transfer Details for {$projectname}";

        if (sendemailfrommailer_email($sendto, $sendcc, $sendbcc, $subject, $m_msgbody, [])) {
            return 'sent';
        } else {
            return 'notsent';
        }

    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}





function sendemailfrommailer_email($sendto,$sendcc,$sendbcc,$m_subject,$m_msgbody,$attch = array())
{
	require_once "../PHPMailer/PHPMailerAutoload.php";
	$mail = new PHPMailer;
	//$mail->SMTPDebug = 2;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'amanaccounting.in';                    // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'documents@amanaccounting.in';                     // SMTP username
    $mail->Password   = (require __DIR__ . '/../mail_config.php')['password'];                               // SMTP password
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