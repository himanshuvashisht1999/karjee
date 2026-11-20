<?php
error_reporting(0); // Suppress warnings/notices for JSON output
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');

$action = $_GET['action'] ?? '';

// Use output buffering to catch any stray output
ob_start();

try {
    if ($action == "getSowingLocationAlerts") {
        $projectid = (int)($_POST['projectid'] ?? 0);
        $returnarray = [];
        
        $qry = "SELECT id, stockorder_no, productname, seedid, seedname, sowing_date, order_qty, created_at 
                FROM krf_plants_sowinglocation_alert 
                WHERE status = 'Pending' 
                  AND projectid = ? 
                ORDER BY sowing_date ASC";
                
        $stmt = $krfconn->prepare($qry);
        if (!$stmt) throw new Exception($krfconn->error);
        
        $stmt->bind_param("i", $projectid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $returnarray[] = $row;
            }
        }
        ob_clean();
        echo json_encode($returnarray);
        exit;
    }

    if ($action == "updateSowingLocation") {
        $alert_ids_str = $_POST['alert_ids'] ?? '';
        $sowinglocationid = $_POST['sowinglocationid'] ?? '';
        $projectid = (int)($_POST['projectid'] ?? 0);

        if (empty($alert_ids_str) || empty($sowinglocationid)) {
            throw new Exception("Missing required parameters.");
        }

        $alertIds = explode(',', $alert_ids_str);
        $successCount = 0;

        $krfconn->begin_transaction();
        try {
            foreach ($alertIds as $aid) {
                $aid = (int)$aid;
                if ($aid <= 0) continue;

                $res = $krfconn->query("SELECT stockorder_no, seedid FROM krf_plants_sowinglocation_alert WHERE id = $aid");
                $alertData = $res->fetch_assoc();
                if ($alertData) {
                    $stockNo = $alertData['stockorder_no'];
                    
                    $updateProd = "UPDATE krf_production_stockorder 
                                 SET sowinglocationid = ? 
                                 WHERE stockorder_no = ? 
                                   AND projectid = ?";
                    $pstmt = $krfconn->prepare($updateProd);
                    $pstmt->bind_param("ssi", $sowinglocationid, $stockNo, $projectid);
                    $pstmt->execute();
                    $pstmt->close();

                    $krfconn->query("UPDATE krf_plants_sowinglocation_alert SET status = 'Updated' WHERE id = $aid");
                    $successCount++;
                }
            }
            $krfconn->commit();
            ob_clean();
            echo json_encode(["status" => "success", "message" => "Successfully updated location for $successCount records."]);
        } catch (Exception $e) {
            $krfconn->rollback();
            throw $e;
        }
        exit;
    } 
    
    if ($action == "GetSowingLocation") {
        require_once('../../krf-orderandsales/functions/labour_functions.php');
        $companyid = $_POST['companyid'] ?? '';
        $subcompanyid = $_POST['subcompanyid'] ?? '';
        $projectid = $_POST['projectid'] ?? '';
        $shortby = $_POST['shortby'] ?? 'all';
        $shortbyvalue = $_POST['shortbyvalue'] ?? 'all';
        
        $data = getProductionLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue);
        ob_clean();
        echo json_encode($data);
        exit;
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}
?>
