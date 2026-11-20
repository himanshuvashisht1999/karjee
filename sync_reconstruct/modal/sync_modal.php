<?php 
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ALL);
require('../../connection.php');
require('../../functions/function_master.php');
$action=$_GET['action'];
$stringvalue=array();
if($action=="undegitizedfiles"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$dddt=getSyncedFilesByCondition($conn, $companyid, $subcompanyid, $projectid, 'undigitized', '0');
	echo json_encode($dddt);	
}else if($action=="markdigitizedentry_syncfile"){
	$companyid    = $_POST['companyid'] ?? 0;
	$subcompanyid = $_POST['subcompanyid'] ?? 0;
	$projectid    = $_POST['projectid'] ?? 0;
	$filedbid     = $_POST['filedbid'] ?? 0;
	$response = ['success' => false, 'message' => ''];
	if (!is_numeric($filedbid) || $filedbid <= 0) {
	    $response['message'] = "Invalid file ID.";
	    echo json_encode($response);
	    exit;
	}
	$digitizedDate = date('Y-m-d H:i:s');
	// Use a prepared statement to prevent SQL injection
	$stmt = $conn->prepare("UPDATE tbl_synced_files SET digitized = ? WHERE id = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?");
	$stmt->bind_param("siiii", $digitizedDate, $filedbid, $companyid, $subcompanyid, $projectid);
	if ($stmt->execute()) {
	    $response['success'] = true;
	    $response['message'] = "File marked as digitized.";
	} else {
	    $response['message'] = "Update failed: " . $stmt->error;
	}
	$stmt->close();
	echo json_encode($response);
} 
?>