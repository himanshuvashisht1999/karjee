<?php
// Order editor helpers only. Retained function bodies preserve the supplied logic.
function sendemail_WhileProvistionalOrderCreate($authconn, $conn, $krfconn, $companyid, $subcompanyid, $projectid, $clientid, $psono)
{
  require_once __DIR__ . '/pdf_generation_functions.php';
  // 🟦 Step 0: Fetch project details
  $select_prt = "SELECT projectname,project_email FROM tbl_project_master 
        WHERE projectid = ?
    ";
  $stmt0 = $conn->prepare($select_prt);
  $stmt0->bind_param("s", $projectid);
  $stmt0->execute();
  $prjqueryresult = $stmt0->get_result();
  if ($prjqueryresult->num_rows === 0) {
    return 'No such request found.';
  }

  $prjrow = $prjqueryresult->fetch_assoc();
  $projectname = $prjrow['projectname'];
  $projectEmail = "";
  if ($prjrow['project_email']) {
    $projectEmail = "✉ " . $prjrow['project_email'] . "<br>";
  }
  $projectContactno = "";

  // 🟦 Step 1: Fetch order master details
  $select_rjrqst = "
        SELECT clientname, emailid, delivery_address, districtname, statename, mobileno, created_by, created_at 
        FROM krf_provisional_sales_order 
        WHERE projectid = ? AND psono = ? AND clientid = ?
    ";
  $stmt = $krfconn->prepare($select_rjrqst);
  $stmt->bind_param("sss", $projectid, $psono, $clientid);
  $stmt->execute();
  $query_result = $stmt->get_result();

  if ($query_result->num_rows === 0) {
    return 'No such request found.';
  }

  $row = $query_result->fetch_assoc();
  $clientName = $row['clientname'];
  $sendtoemail = $row['emailid'] ?? '';
  $deliveryDate = date('d-M-Y', strtotime($row['created_at']));
  $deliveryNo = $psono;

  // 🟦 Step 2: Fetch product details
  $psoprd = "
        SELECT product_sku, product_id, product_name, quantity, rate, cgst_amount, sgst_amount, igst_amount, discount_amount, amount, expecteddeliverydate 
        FROM krf_provisional_sales_order_items 
        WHERE projectid = ? AND psono = ? AND clientid = ?
    ";
  $stmt2 = $krfconn->prepare($psoprd);
  $stmt2->bind_param("sss", $projectid, $psono, $clientid);
  $stmt2->execute();
  $prdresult = $stmt2->get_result();

  $prdresult_detail = '';
  $orderAmount = 0;
  $sno = 1;

  while ($br = $prdresult->fetch_assoc()) {
    $orderAmount += $br['amount'];
    $expdeliverydate = date('d-M-Y', strtotime($br['expecteddeliverydate']));
    $prdresult_detail .= "
            <tr>
                <td style='border:1px solid #ccc;padding:8px;text-align:center;'>{$sno}</td>
                <td style='border:1px solid #ccc;padding:8px;'>{$br['product_name']}</td>
                <td style='border:1px solid #ccc;padding:8px;text-align:center;'>{$br['quantity']}</td>
                <td style='border:1px solid #ccc;padding:8px;text-align:right;'>{$br['rate']}</td>
                <td style='border:1px solid #ccc;padding:8px;text-align:right;'>{$br['discount_amount']}</td>
                <td style='border:1px solid #ccc;padding:8px;text-align:right;'>{$br['amount']}</td>
                <td style='border:1px solid #ccc;padding:8px;text-align:center;'>{$expdeliverydate}</td>
            </tr>";
    $sno++;
  }

  // 🟦 Step 3: Prepare email body (HTML)
  $m_msgbody = "
    <!DOCTYPE html>
    <html>
    <body style='font-family: Arial, sans-serif; color: #333;'>
      <div style='max-width: 1050px; margin: auto; border: 1px solid #ddd; border-radius: 10px; padding: 20px;'>
        
        <h2 style='color: #007BFF; text-align:center;'>Provisional Order Generated Successfully</h2>
        
        <p>Dear <strong>{$clientName}</strong>,</p>
        
        <p>Thank you for choosing <strong>{$projectname}</strong>. Your provisional order 
        <strong>{$deliveryNo}</strong> has been successfully generated on <strong>{$deliveryDate}</strong>.</p>

        <p>To confirm and process your order, please pay the <strong>advance amount</strong> within 
        <strong>3 days</strong>. If payment is not received within this period, the provisional order 
        will be <strong>automatically canceled</strong>.</p>

        <table style='width:100%; border-collapse: collapse; margin: 20px 0;'>
          <tr>
            <td style='border: 1px solid #ccc; padding: 8px;'>Order Number</td>
            <td style='border: 1px solid #ccc; padding: 8px;'>{$deliveryNo}</td>
          </tr>
          <tr>
            <td style='border: 1px solid #ccc; padding: 8px;'>Client Name</td>
            <td style='border: 1px solid #ccc; padding: 8px;'>{$clientName}</td>
          </tr>
          <tr>
            <td style='border: 1px solid #ccc; padding: 8px;'>Order Date</td>
            <td style='border: 1px solid #ccc; padding: 8px;'>{$deliveryDate}</td>
          </tr>
          <tr>
            <td style='border: 1px solid #ccc; padding: 8px;'>Order Amount</td>
            <td style='border: 1px solid #ccc; padding: 8px;'>₹{$orderAmount}</td>
          </tr>
        </table>

        <h4 style='margin-top:20px;'>Product Details:</h4>
        <table style='width:100%; border-collapse: collapse; margin: 10px 0;'>
          <thead>
            <tr>
              <th style='border:1px solid #ccc;padding:8px;'>S.No</th>
              <th style='border:1px solid #ccc;padding:8px;'>Product Name</th>
              <th style='border:1px solid #ccc;padding:8px;'>Qty</th>
              <th style='border:1px solid #ccc;padding:8px;'>Rate</th>
              <th style='border:1px solid #ccc;padding:8px;'>Discount</th>
              <th style='border:1px solid #ccc;padding:8px;'>Amount</th>
              <th style='border:1px solid #ccc;padding:8px;'>Expected Delivery</th>
            </tr>
          </thead>
          <tbody>
            {$prdresult_detail}
          </tbody>
        </table>

        <p>If you have already made the payment, please ignore this message or share your transaction details with us.</p>

        <p>Thank you for your prompt action and continued trust in <strong>{$projectname}</strong>.</p>

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

  // 🟦 Step 4: Generate PDF
  $tempDir = __DIR__ . '/../temp/';
  if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
  }
  $pdfPath = $tempDir . 'ProvisionalOrder_' . $psono . '.pdf';

  $logFile = __DIR__ . '/../debug_email_pdf.log';
  file_put_contents($logFile, date('Y-m-d H:i:s') . " - calling createProvisionalOrderPDF for $psono in sendemail_WhileProvistionalOrderCreate\n", FILE_APPEND);

  // Generate the PDF file
  $pdfResult = createProvisionalOrderPDF($conn, $authconn, $krfconn, $companyid, $subcompanyid, $projectid, $psono, 'F', $pdfPath);

  $attachments = [];
  if ($pdfResult && file_exists($pdfPath)) {
    $attachments[] = $pdfPath;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - PDF generation successful: $pdfPath\n", FILE_APPEND);
  } else {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - PDF generation failed or file not found.\n", FILE_APPEND);
  }

  // 🟦 Step 5: Send Email
  $sendto = [$sendtoemail ?: 'chatursingh011@gmail.com'];
  $sendcc = [];
  $sendbcc = [];
  $subject = "Provisional Order Generated - Please Confirm Your Order with Advance Payment";

  $mailSent = sendemailfrommailer($sendto, $sendcc, $sendbcc, $subject, $m_msgbody, $attachments);

  // Cleanup: Delete the temp file
  if (file_exists($pdfPath)) {
    unlink($pdfPath);
  }

  if ($mailSent) {
    return 'sent';
  } else {
    return 'notsend';
  }
}

function sendemailfrommailer($sendto, $sendcc, $sendbcc, $m_subject, $m_msgbody, $attch = array())
{
  require_once __DIR__ . '/../../PHPMailer/PHPMailerAutoload.php';
  $mail = new PHPMailer;
  //$mail->SMTPDebug = 2;                      // Enable verbose debug output
  $mail->isSMTP();                                            // Send using SMTP
  $mail->Host = 'amanaccounting.in';                    // Set the SMTP server to send through
  $mail->SMTPAuth = true;                                   // Enable SMTP authentication
  $mail->Username = 'documents@amanaccounting.in';                     // SMTP username
  $mail->Password = (require __DIR__ . '/../../mail_config.php')['password'];                               // SMTP password
  $mail->SMTPSecure = 'tls';         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
  $mail->Port = 587;

  $mail->From = "documents@amanaccounting.in";
  $mail->FromName = "Aman Accounting";

  $mail->SMTPOptions = array(
    'ssl' => array(
      'verify_peer' => false,
      'verify_peer_name' => false,
      'allow_self_signed' => true
    )
  );

  foreach ($sendto as $receiver) {
    $mail->addAddress($receiver);
  }

  if ($sendcc) {
    foreach ($sendcc as $ccreceiver) {
      $mail->addCC($ccreceiver);
    }
  }

  if ($sendbcc) {
    foreach ($sendbcc as $bccreceiver) {
      $mail->addBCC($bccreceiver);
    }
  }

  if ($attch) {
    foreach ($attch as $value) {
      $displayname = basename($value);
      $mail->AddAttachment($value, $displayname);
    }
  }

  $mail->isHTML(true);

  $mail->Subject = $m_subject;

  $mail->Body = $m_msgbody;

  if (!$mail->send()) {
    // Log error if needed
    // file_put_contents(__DIR__ . '/../mail_error.log', date('Y-m-d H:i:s') . " - Mailer Error: " . $mail->ErrorInfo . "\n", FILE_APPEND);
    return false;
  } else {
    return true;
  }
}
