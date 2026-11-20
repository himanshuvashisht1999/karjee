<?php 
date_default_timezone_set("Asia/Calcutta");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require('../../../connection.php');  

$projectid = isset($_POST['projectid']) ? trim($_POST['projectid']) : '';
$clientid = isset($_POST['clientid']) ? trim($_POST['clientid']) : ''; 
$returnarray = [];
$krfconn->begin_transaction();
try {
      createstmt_client($krfconn, $projectid, $clientid);
      $qry = " SELECT  DATE_FORMAT(txn_date,'%d-%m-%Y') AS txn_date,  txn_type,  txn_no, description, debit_amount, credit_amount,  running_balance  FROM krf_tbl_client_statement WHERE client_id  = '{$clientid}'  AND project_id = '{$projectid}' ORDER BY txn_date, statement_id   ";
      $result = $krfconn->query($qry);
      if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                  $returnarray[] = [
                      'success'          => 'done',
                      'txn_date'         => $row['txn_date'],
                      'txn_type'         => $row['txn_type'],
                      'txn_no'           => $row['txn_no'],
                      'description'      => $row['description'],
                      'debit_amount'     => $row['debit_amount'] ?? '',
                      'credit_amount'    => $row['credit_amount'] ?? '',
                      'running_balance'  => $row['running_balance'] ?? ''
                  ];
            }
      }
      $krfconn->commit();
} catch (Exception $e) {
    $krfconn->rollback();
    $returnarray[] = [
        'success' => 'error',
        'message' => $e->getMessage()
    ];
}
echo json_encode($returnarray);
function createstmt_client($conn, $projectid, $clientid){
      $runningBalance=0;
      $sql = " SELECT dbsno, narration, txn_no, txt_type, txn_date, debitamt, creditamount FROM ( SELECT  GROUP_CONCAT(DISTINCT id) AS dbsno,
        CONCAT(
            GROUP_CONCAT(DISTINCT CONCAT('Sale Order No : ', sono) ORDER BY sono SEPARATOR ', '),
            ' | ',
            GROUP_CONCAT(DISTINCT CONCAT('Products : ', product_name) ORDER BY product_name SEPARATOR ', ')
        ) AS narration, IFNULL(invoiceno, '') AS txn_no, 'Invoice' AS txt_type,
        invoicedate AS txn_date, SUM(amount) AS debitamt,   0 AS creditamount  FROM krf_sales_order_items WHERE status = 'invoiced' AND clientid = '{$clientid}' AND IFNULL(invoiceno,'') <> ''  AND IFNULL(ledgerstatus,'') = ''  GROUP BY invoiceno, invoicedate 
    UNION ALL 
    SELECT sno AS dbsno, CONCAT(  'Received against sale order no : ',  s.sono,  ' Via ',
            UCASE(r.sourceofpayment) ) AS narration,
        r.utrnumber AS txn_no,  'Receipt' AS txt_type,  r.paymentdate AS txn_date,  0 AS debitamt,  r.advanceamount AS creditamount FROM advance_payment_transactions r JOIN krf_sales_order s   ON r.invoiceno = s.psono  WHERE r.clientid = '{$clientid}'  AND IFNULL(r.ledgerstatus,'') = '' ) t ORDER BY txn_date ASC ";
      $result = $conn->query($sql);

      if (!$result || $result->num_rows == 0) {
            return;
      } 
    /*  if ($result || $result->num_rows > 0) {
            $op_row = $result->fetch_assoc(); 
            $runningBalance = getopeingbal($conn, $projectid, $clientid, $op_row['txn_date']);
      }  */
      $k=0;  
      while ($row = $result->fetch_assoc()) {
            if($k<=0){
                  $runningBalance = getopeingbal($conn, $projectid, $clientid, $row['txn_date']);
            }
            $k++;
            $runningBalance =$runningBalance + (float)$row['debitamt']- (float)$row['creditamount'];
            $insertqry = " INSERT INTO krf_tbl_client_statement (project_id, client_id, txn_date, txn_type, txn_no, description,debit_amount, credit_amount, running_balance)  VALUES (
                      '{$projectid}',
                      '{$clientid}',
                      '{$row['txn_date']}',
                      '{$row['txt_type']}',
                      '{$row['txn_no']}',
                      '{$conn->real_escape_string($row['narration'])}',
                      '{$row['debitamt']}',
                      '{$row['creditamount']}',
                      '{$runningBalance}'
                  ) ";

            $conn->query($insertqry); 
            if($row['txt_type']==="Invoice"){
                   $uptbl ="update krf_sales_order_items set ledgerstatus ='done' where id in ({$row['dbsno']})";
              $conn->query($uptbl);
            }else if($row['txt_type']==="Receipt"){
                   $uptbl ="update advance_payment_transactions set ledgerstatus ='done' where sno in ({$row['dbsno']})"; 
              $conn->query($uptbl);
            }
      }
}     

function getopeingbal($conn, $projectid, $clientid, $txn_date){
    $qry = "SELECT running_balance  FROM krf_tbl_client_statement WHERE client_id = '{$clientid}' AND project_id = '{$projectid}'  ORDER BY statement_id DESC LIMIT 1";
    $chkresult = $conn->query($qry);
    if (!$chkresult || $chkresult->num_rows == 0) {
        $insertqry = "INSERT INTO krf_tbl_client_statement
            (project_id, client_id, txn_date, txn_type, txn_no, description, running_balance) VALUES (  '{$projectid}', '{$clientid}', '{$txn_date}',  'Opening', '', 'Opening Balance',  0 )  ";
        $conn->query($insertqry);
        return 0;
    }

    $row = $chkresult->fetch_assoc();
    return (float)$row['running_balance'];
}

?>
 
