<?php
error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/orders_functions.php");
include("../functions/vendor_functions.php");
$action = $_GET['action'];
if($action=="saveupdatevendordetails"){
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $krfconn->begin_transaction();

        try {
            // collect form data
            $data = [
                'VendorID'       => trim($_POST['VendorID'] ?? ''),
                'VendorName'     => trim($_POST['VendorName']),
                'ContactPerson'  => trim($_POST['ContactPerson']),
                'Email'          => trim($_POST['Email']),
                'Phone'          => trim($_POST['Phone']),
                'AlternatePhone' => trim($_POST['AlternatePhone']),
                'Address'        => trim($_POST['Address']),
                'City'           => trim($_POST['City']),
                'State'          => trim($_POST['State']),
                'Pincode'        => trim($_POST['Pincode']),
                'Country'        => trim($_POST['Country']),
                'GSTNo'          => trim($_POST['GSTNo']),
                'PANNo'          => trim($_POST['PANNo']),
                'BankName'       => trim($_POST['BankName']),
                'AccountNo'      => trim($_POST['AccountNo']),
                'IFSCCode'       => trim($_POST['IFSCCode']),
                'Status'         => trim($_POST['Status'] ?? 'Active'),
                'CreatedBy'      => "Admin",
                'ProjectID'      => intval($_POST['ProjectID'] ?? 1)
            ];

            if (empty($data['VendorName'])) {
                throw new Exception("Vendor Name is required");
            }

            if (empty($data['VendorID'])) {
                $VendorID = insertVendor($krfconn, $data);
            } else {
                $VendorID = updateVendor($krfconn, $data);
            }

            $krfconn->commit();
            echo json_encode(["status" => "success", "VendorID" => $VendorID]);

        } catch (Exception $e) {
            $krfconn->rollback();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }
}else if($action=="VendorDataOnlyOption"){
  $ProjectID = $_POST['projectid'] ?? 0;
  $VendorID = $_POST['Vendorid']??"";
  $seeddt=fetchVendorDetails($krfconn, $VendorID, $ProjectID);
  echo json_encode($seeddt);
}


?>