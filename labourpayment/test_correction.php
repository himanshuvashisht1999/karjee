<?php
require('connection.php');
$prefix = 'KRF-';
$companyid = 19;
$subcompanyid = 12;
$projectid = 17;
$locationid = 'LOC6905e0fd5ab05';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Labour Master Correction Utility</title>

    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap' rel='stylesheet'>

    <style>
        body{
            font-family:'Inter',sans-serif;
            background:#f3f4f6;
            padding:20px;
        }

        .container{
            max-width:1200px;
            margin:auto;
            background:#fff;
            padding:20px;
            border-radius:10px;
            box-shadow:0 2px 8px rgba(0,0,0,.15);
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:20px;
        }

        th,td{
            padding:10px;
            border:1px solid #ddd;
        }

        th{
            background:#f3f4f6;
        }

        .badge{
            padding:5px 10px;
            border-radius:5px;
            font-size:12px;
            font-weight:bold;
        }

        .badge-update{
            background:#d1fae5;
            color:#065f46;
        }

        .badge-error{
            background:#fee2e2;
            color:#991b1b;
        }

        .badge-notfound{
            background:#fef3c7;
            color:#92400e;
        }

    </style>

</head>

<body>

<div class='container'>

<h2>Labour Master Data Correction</h2>

<table>

<tr>
    <th>Old EmployeeID</th>
    <th>Target EmployeeID</th>
    <th>Name</th>
    <th>Project</th>
    <th>Status</th>
    <th>Message</th>
</tr>";

$sql = "
SELECT EmployeeID, projectid, fullname, Gender, DateOfJoining, dob FROM hrm_labourmaster_correction
ORDER BY CAST(EmployeeID AS UNSIGNED)
";
$result = $hrmconn->query($sql);
if($result && $result->num_rows>0)
{
    while($row=$result->fetch_assoc())
    {

        $oldEmployeeID=$row['EmployeeID'];
        $seriesid=$row['EmployeeID'];
        $fullname=trim($row['fullname']);
        $gender=$row['Gender'];
        $doj=$row['DateOfJoining'];
        $dob=$row['dob'];
        $projectid=$row['projectid'];

        // New EmployeeID
        $newEmployeeID=$prefix.$oldEmployeeID;

        // Split Name
        $parts=preg_split('/\s+/',trim($fullname));

        $firstName="";
        $middleName="";
        $lastName="";

        if(count($parts)==1)
        {
            $firstName=$parts[0];
        }
        elseif(count($parts)==2)
        {
            $firstName=$parts[0];
            $lastName=$parts[1];
        }
        else
        {
            $firstName=array_shift($parts);
            $lastName=array_pop($parts);
            $middleName=implode(" ",$parts);
        }

        // Check Employee Exists
        $check=$hrmconn->prepare("
        SELECT EmployeeID
        FROM hrm_LabourMaster
        WHERE projectid=?
        AND EmployeeID=?
        ");

        $check->bind_param("is",$projectid,$newEmployeeID);

        $check->execute();

        $res=$check->get_result();

        if($res->num_rows>0)
        {

            $update=$hrmconn->prepare("UPDATE hrm_LabourMaster SET FirstName=?, MiddleName=?, LastName=?, Gender=?, DateOfJoining=?, DateOfBirth=? WHERE projectid=? AND EmployeeID=?");
            $update->bind_param("ssssssis",$firstName, $middleName, $lastName, $gender, $doj, $dob, $projectid, $newEmployeeID);

            if($update->execute())
            {
                $editStatus = "UPDATED | " . date("Y-m-d H:i:s");

                $stmtEdit = $hrmconn->prepare("
                    UPDATE hrm_labourmaster_correction
                    SET tedit = ?
                    WHERE EmployeeID = ?
                ");

                $stmtEdit->bind_param("ss", $editStatus, $oldEmployeeID);

                if ($stmtEdit->execute()) {
                    echo "Edit column updated for EmployeeID : ".$oldEmployeeID."<br>";
                } else {
                    echo "Edit Update Error : ".$stmtEdit->error."<br>";
                }

                $stmtEdit->close();
                $status="<span class='badge badge-update'>UPDATED</span>";
                $message="Employee updated successfully.";
            }
            else
            {
                $status="<span class='badge badge-error'>ERROR</span>";
                $message=$update->error;
            }

            $update->close();

        }
        else
        {
            // Insert New Labour
    $stmtInsert = $hrmconn->prepare("
        INSERT INTO hrm_LabourMaster
        (
            EmployeeID,
            seriesid,
            companyid,
            subcompanyid,
            projectid,
            FirstName,
            MiddleName,
            LastName,
            Gender,
            DateOfBirth,
            DateOfJoining,
            FatherName,
            SpouseName,
            locationid
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?,?,?,?,?,?,?
        )
    ");

    $stmtInsert->bind_param(
        "ssssssssssssss",
        $newEmployeeID,
        $seriesid,
        $companyid,
        $subcompanyid,
        $projectid,
        $firstName,
        $middleName,
        $lastName,
        $gender,
        $dob,
        $doj,
        $row['fathername'],
        $row['spousename'],
        $locationid
    );

    if($stmtInsert->execute())
    {

        // Mark correction table
        $editStatus = "INSERTED | ".date("Y-m-d H:i:s");

        $stmtEdit = $hrmconn->prepare("
            UPDATE hrm_labourmaster_correction
            SET tedit=?
            WHERE EmployeeID=?
        ");

        $stmtEdit->bind_param("ss",$editStatus,$correction_id);
        $stmtEdit->execute();
        $stmtEdit->close();

        $status = "<span class='badge badge-insert'>INSERTED</span>";
        $message = "New Labour inserted successfully.";

    }
    else
    {

        $status = "<span class='badge badge-error'>ERROR</span>";
        $message = $stmtInsert->error;

    }

    $stmtInsert->close();
        }

        $check->close();

        echo "
        <tr>

            <td>{$oldEmployeeID}</td>

            <td>{$newEmployeeID}</td>

            <td>".htmlspecialchars($fullname)."</td>

            <td>{$projectid}</td>

            <td>{$status}</td>

            <td>{$message}</td>

        </tr>";

    }

}
else
{

    echo "<tr>

            <td colspan='6' align='center'>

            No Record Found

            </td>

          </tr>";

}

echo "

</table>

</div>

</body>

</html>";

?>