<?php 
    date_default_timezone_set("Asia/Kolkata");
    require('../../../connection.php');      
    $action = isset($_GET['action']) ? $_GET['action'] : '';  
    $projectid          = $_POST['projectid']          ?? ''; 
    if ($action === "assignallocatestk") {      
        $clientid          = $_POST['clientid']          ?? ''; 
        $dbid          = $_POST['dbid']          ?? ''; 
        $invoiceno          = $_POST['invoiceno']          ?? ''; 
       
        $krfconn->begin_transaction();
        try {
            $sql = "  UPDATE krf_readytosale_stocks SET invoiceno = ?, assigntoclientid = ? ,finalclientid =?  WHERE id = ?    AND projectid = ?
            ";
            $stmt = $krfconn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $krfconn->error);
            }
            $stmt->bind_param(
                "sssss",
                $invoiceno,
                $clientid,
                $clientid,
                $dbid,
                $projectid
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            if ($stmt->affected_rows === 0) {
                throw new Exception("No rows updated. Invalid barcode or already assigned.");
            }

            $krfconn->commit();

            echo json_encode(  'success' );

        } catch (Exception $e) {

            $krfconn->rollback();

            echo json_encode(   $e->getMessage()
             );
        }  
    }  
     
    


?>
