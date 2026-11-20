<?php 
date_default_timezone_set("Asia/Calcutta");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);  
require('../../../connection.php');  
header('Content-Type: application/json; charset=utf-8');
$action = isset($_GET['action']) ? $_GET['action'] : ''; 
if ($action === "phonecommunication") { 
   $qids = $_POST['qid'];
    $answers = $_POST['answer'];

    $contactno = $_POST['contactno'];
    $communicationdate = $_POST['communicationdate'];
    $recipientname = $_POST['recipientname'];
    $communicationmessage = $_POST['communicationmessage'];

    // First insert communication master record
    $sql1 = "INSERT INTO tbl_communication_master (contactno, communicationdate, recipientname, message)
             VALUES ('$contactno', '$communicationdate', '$recipientname', '$communicationmessage')";
    $conn->query($sql1);

    $masterid = $conn->insert_id; // get inserted id

    // Insert each answer related to master
    for ($i = 0; $i < count($qids); $i++) {
        $qid = $qids[$i];
        $ans = $answers[$i];

        $sql2 = "INSERT INTO tbl_communication_answers (masterid, questionid, answer)
                 VALUES ('$masterid', '$qid', '$ans')";
        $conn->query($sql2);
    }

    mysqli_close($conn); 
}else if ($action === "emailcommunication") { 
    $emailto  = isset($_POST['emailto']) ? trim($_POST['emailto']) : '';
    $emailcc  = isset($_POST['emailcc']) ? trim($_POST['emailcc']) : '';
    $subject  = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message  = isset($_POST['message']) ? trim($_POST['message']) : '';
    if ($emailto === '' || $subject === '' || $message === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Required fields are missing.'
        ]);
        exit;
    }
    $uploadDir = "uploads/email_attachments/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $attachmentNames = [];
    if (!empty($_FILES['attachments']['name'][0])) {
        for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
            $originalName = $_FILES['attachments']['name'][$i];
            $tmpName      = $_FILES['attachments']['tmp_name'][$i];
            if ($tmpName == "") continue;
            $safeName = time() . "_" . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $originalName);
            $targetPath = $uploadDir . $safeName;

            if (move_uploaded_file($tmpName, $targetPath)) {
                $attachmentNames[] = $safeName;
            }
        }
    }
    $attachmentsStr = implode(",", $attachmentNames);

    $sql = "INSERT INTO krf_tbl_communications (emailto, emailcc, emailsub, attachment,message ,communication_type,login)
            VALUES ('$emailto', '$emailcc', '$subject', '$attachmentsStr','$message' ,'email')";

    if (mysqli_query($krfconn, $sql)) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }

}
 
 

?>
