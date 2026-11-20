<?php
date_default_timezone_set("Asia/Calcutta");
require('../../connection.php'); 
$action=$_GET['action'];

if($action=="getproductdata_growing"){  
    $projectid = $_POST['projectid'];       
    $returnarray=array();   
    $qry = " select p.isGrafted, p.productID as productid, p.ProductName as productname , ifnull(g.growingid,'') as growingid, ifnull(g.growingdays,'') as growingdays , ifnull(g.graftingdays,'') as graftingdays,  ifnull(g.seasonalcoveredtime,'') as  seasonalcoveredtime from  product_master p  left join krf_growingtime g on g.projectid =p.projectid and g.productid= p.productID where ifnull(p.Status,'Active') ='Active' and  p.projectid={$projectid} order by p.ProductName,p.isGrafted ";      
    $result=$krfconn->query($qry);      
    if ($result->num_rows>0) {
        while ($row=$result->fetch_assoc()) { 
              $returnarray[] = $row;                   
        }
    } 
    echo  json_encode($returnarray); 

}else   if ($action == "savegrowingmaster") {
    // Sanitize input values
    $projectid = mysqli_real_escape_string($krfconn, $_POST['projectid']);
    $loginid = mysqli_real_escape_string($krfconn, $_POST['loginid']);
    $productid = mysqli_real_escape_string($krfconn, $_POST['productid']);
    $growingid = mysqli_real_escape_string($krfconn, $_POST['growingid']);
    $productname = addslashes($_POST['productname']); // escape quotes
    $growingdays = mysqli_real_escape_string($krfconn, $_POST['growingdays']);
    $grafteddays = mysqli_real_escape_string($krfconn, $_POST['grafteddays']);
    $seasonalcorrectionday = mysqli_real_escape_string($krfconn, $_POST['seasonalcorrectionday']);

    // Decode JSON array safely
    $season_corr_array = $_POST['correctionarray'] ?? '[]';
    $season_array = json_decode($season_corr_array, true);

    // Start transaction
    $krfconn->begin_transaction();
    try {

        if ($growingid) { 
            // Update master record
            $qry = "UPDATE krf_growingtime   SET productid = '$productid',
                        productname = '$productname',
                        growingdays = '$growingdays',
                        graftingdays = '$grafteddays',
                        seasonalcoveredtime = '$seasonalcorrectionday',
                        updated_by = '$loginid'
                    WHERE growingid = '$growingid' 
                      AND projectid = '$projectid'";
            $krfconn->query($qry);

            // Update seasonal corrections
            foreach ($season_array as $data) {   
                $month = mysqli_real_escape_string($krfconn, $data['month']);
                $monthval = mysqli_real_escape_string($krfconn, $data['monthval']);

                $qry = "UPDATE krf_seasonalcorrection 
                        SET monthcorrectiondays = '$monthval'
                        WHERE growingid = '$growingid'  
                          AND projectid = '$projectid' 
                          AND month = '$month'";
                $krfconn->query($qry);
            }

        } else {
            // Generate new growingid
            $maxid = fetchsno("growingid", "krf_growingtime", "1=1", $krfconn);
            if (!$maxid) {
                echo json_encode(["status" => "Error", "message" => "Unable to fetch new growingid."]);
                exit;
            }

            // Insert master record
            $qry = "INSERT INTO krf_growingtime 
                    (projectid, growingid, productid, productname, growingdays, graftingdays, seasonalcoveredtime, created_by)
                    VALUES 
                    ('$projectid', '$maxid', '$productid', '$productname', '$growingdays', '$grafteddays', '$seasonalcorrectionday', '$loginid')";
            $krfconn->query($qry);

            // Insert seasonal correction rows
            foreach ($season_array as $data) {   
                $month = mysqli_real_escape_string($krfconn, $data['month']);
                $monthval = mysqli_real_escape_string($krfconn, $data['monthval']);

                $qry = "INSERT INTO krf_seasonalcorrection 
                        (projectid, growingid, productid, month, monthcorrectiondays) 
                        VALUES 
                        ('$projectid', '$maxid', '$productid', '$month', '$monthval')";
                $krfconn->query($qry);
            }
        }

        $krfconn->commit();
        echo json_encode([["status" => "Success", "message" => "Growing record saved successfully"]]);
    } 
    catch (mysqli_sql_exception $e) {
        $krfconn->rollback();
        echo json_encode([["status" => "Error", "message" => "Transaction failed: " . $e->getMessage()]]);
    }
}


function fetchsno($fldname ,$tblname ,$condqry,$mconn){
    $row_maxid=0;
      $max_qry= "select  IFNULL(MAX($fldname) + 1, 1)  as maxid from    $tblname   where  ". $condqry; 
    $result_max = $mconn->query($max_qry);
    if ($result_max->num_rows > 0) {
        $row_max=$result_max->fetch_assoc();        
       $row_maxid=$row_max['maxid']; 
    }
    return $row_maxid;
}  
?>