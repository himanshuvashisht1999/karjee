<?php
require('connection.php');
$sql = "ALTER TABLE hrm_labourdocuments MODIFY EmployeeID VARCHAR(50)";
if ($hrmconn->query($sql)) {
    echo "Table altered successfully!";
} else {
    echo "Error: " . $hrmconn->error;
}
?>
