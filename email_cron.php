<?php
date_default_timezone_set("Asia/Calcutta");
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '1');

require('connection.php');
require('sendemail.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$soqry = "SELECT sendto, IFNULL(sendcc, '') AS sendcc, IFNULL(sendbcc, '') AS sendbcc,
                 m_subject, m_msgbody, sno, IFNULL(attch, '') AS attch
          FROM emailsendby_cron
          WHERE IFNULL(status, '') = ''
          ORDER BY sno DESC";
$res = $conn->query($soqry);

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $sendTo = explode(",", $row['sendto']);
        $sendcc = explode(",", $row['sendcc']);
        $sendBcc = explode(",", $row['sendbcc']);
        $m_subject = $row['m_subject'];
        $m_msgbody = $row['m_msgbody'];
        $attch = explode(",", $row['attch']);

        $response = Sendemailnew($sendTo, $sendcc, $sendBcc, $m_subject, $m_msgbody, $attch);

        if ($response) {
            $conn->begin_transaction();
            try {
                $updinv = "UPDATE emailsendby_cron SET status = 'Sent', sentdate = '" . date('Y-m-d H:i:s') . "' WHERE sno='" . $row["sno"] . "'";
                $conn->query($updinv);
                $conn->commit();
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                // Log error here if needed
            }
        }
    }
}
?>
