<?php
function fetchVendorDetails($conn, $VendorID = null, $ProjectID = null) {
    try {
        if ($VendorID) {
            // Fetch single vendor by ID
            $stmt = $conn->prepare("SELECT * FROM vendor_master WHERE VendorID = ?");
            $stmt->bind_param("s", $VendorID);
        } elseif ($ProjectID) {
            // Fetch vendors for a specific project
            $stmt = $conn->prepare("SELECT * FROM vendor_master WHERE ProjectID = ?");
            $stmt->bind_param("i", $ProjectID);
        } else {
            // Fetch all vendors
            $stmt = $conn->prepare("SELECT * FROM vendor_master");
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $vendors = [];
        while ($row = $result->fetch_assoc()) {
            $vendors[] = $row;
        }
        return $vendors;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}


function insertVendor($conn, $data) {
    $newVendorID = generateVendorID($conn);
    $stmt = $conn->prepare("INSERT INTO vendor_master
        (VendorID, VendorName, ContactPerson, Email, Phone, AlternatePhone, Address, City, State, Pincode, Country,
         GSTNo, PANNo, BankName, AccountNo, IFSCCode, Status, CreatedBy, ProjectID)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssssssssssi",
        $newVendorID, $data['VendorName'], $data['ContactPerson'], $data['Email'], $data['Phone'], $data['AlternatePhone'],
        $data['Address'], $data['City'], $data['State'], $data['Pincode'], $data['Country'],
        $data['GSTNo'], $data['PANNo'], $data['BankName'], $data['AccountNo'], $data['IFSCCode'],
        $data['Status'], $data['CreatedBy'], $data['ProjectID']
    );

    if (!$stmt->execute()) {
        throw new Exception("Insert Error: " . $stmt->error);
    }

    return $newVendorID;
}

function updateVendor($conn, $data) {
    $stmt = $conn->prepare("UPDATE vendor_master SET
        VendorName=?, ContactPerson=?, Email=?, Phone=?, AlternatePhone=?, Address=?, City=?, State=?, Pincode=?, Country=?,
        GSTNo=?, PANNo=?, BankName=?, AccountNo=?, IFSCCode=?, Status=?, ProjectID=?
        WHERE VendorID=?");
    $stmt->bind_param("ssssssssssssssssss",
        $data['VendorName'], $data['ContactPerson'], $data['Email'], $data['Phone'], $data['AlternatePhone'],
        $data['Address'], $data['City'], $data['State'], $data['Pincode'], $data['Country'],
        $data['GSTNo'], $data['PANNo'], $data['BankName'], $data['AccountNo'], $data['IFSCCode'],
        $data['Status'], $data['ProjectID'], $data['VendorID']
    );

    if (!$stmt->execute()) {
        throw new Exception("Update Error: " . $stmt->error);
    }

    return $data['VendorID'];
}

function generateVendorID($conn) {
    $sql = "SELECT count(VendorID) as countVendorID FROM vendor_master ORDER BY VendorID DESC LIMIT 1";
    $res = $conn->query($sql);
    $nextNo = 1;

    if ($res && $row = $res->fetch_assoc()) {
        $nextNo = $row['countVendorID'] + 1;
    }
    return "V" . str_pad($nextNo, 2, "0", STR_PAD_LEFT);
}
?>