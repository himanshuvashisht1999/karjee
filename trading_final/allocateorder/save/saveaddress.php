<?php 
    date_default_timezone_set("Asia/Kolkata");
    require('../../../connection.php');   
    // include ('savelogcommunication.php');
    include ('updateFreightAddress.php');

    $allocationData = json_decode($_POST['items'], true);
    if (!is_array($allocationData) || empty($allocationData)) {
        echo "No data found for updation of address";
        exit;
    }
    //$action = isset($_GET['action']) ? $_GET['action'] : '';
    $projectid          = $_POST['projectid']          ?? '';
    $communicatedby    = $_POST['communicatedby']          ?? '';
    $actionstatus    = $_POST['actionstatus']          ?? ''; 

    $krfconn->begin_transaction();
    try{   
        $addresstypearray=updateslocation($krfconn,$allocationData,$communicatedby,$projectid,$actionstatus);
        if (!is_array($addresstypearray)) { 
            echo json_encode("Issue in updating Address");
            exit;
        }          
        $krfconn->commit();
        echo json_encode("done");
    } catch (Exception $e) {
        $krfconn->rollback();
        //echo "Error: " . $e->getMessage();
        echo json_encode("Error: " . $e->getMessage());
    } 
   
?>
