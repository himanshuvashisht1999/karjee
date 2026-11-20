<?php
include("../connection.php");
$companyid = $_POST['companyid'];
$subcompanyid = $_POST['subcompanyid'];
$projectid = $_POST['projectid'];
$remoteListURL = 'https://erinmaal.amanaccounting.in/postpayment/nint_postpayment/listbankstatement_files.php?cid='.$companyid.'&sbcid='.$subcompanyid.'&prjid='.$projectid;
$destinationFolder = __DIR__ . '/sourcefile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/'; // Use local path for saving files
$result = fetchAllFilesFromRemoteFolder($conn,$companyid,$subcompanyid,$projectid,$remoteListURL,$destinationFolder);
echo json_encode($result);

function fetchAllFilesFromRemoteFolder($conn,$companyid,$subcompanyid,$projectid,$remoteListURL, $destinationFolder)
{
    $syncedFiles = [];

    // Get JSON file list from remote PHP
    $response = @file_get_contents($remoteListURL);
    if ($response === false) {
        return [
            "status" => "error",
            "message" => "Unable to connect to remote list endpoint.",
            "files" => []
        ];
    }

    $files = json_decode($response, true);
    if (!is_array($files)) {
        return [
            "status" => "error",
            "message" => "Invalid JSON format in response.",
            "files" => []
        ];
    }

    if (!is_dir($destinationFolder)) {
        mkdir($destinationFolder, 0777, true);
    }

    $remoteBaseURL = dirname($remoteListURL) . '/';

    foreach ($files as $file) {
        $remoteFile = $remoteBaseURL . $file;
        $localPath = rtrim($destinationFolder, '/') . '/' . basename($file);

        // Skip if the file already exists
        if (file_exists($localPath)) {
            continue;
        }

        // Download the remote file via cURL
        // Check if the file already exists in the database
        $filename = basename($file);
        $exists = checkIfFileExistsInDB($conn, $companyid, $subcompanyid, $projectid, $filename);

        if (!$exists) {
            // Download the remote file via cURL
            $data = getRemoteFileViaCurl($remoteFile);
            if ($data !== false) {
                $copied = file_put_contents($localPath, $data);
                if ($copied !== false) {
                    storeFileMetadataInDB($conn, $companyid, $subcompanyid, $projectid, $filename, $localPath);
                    $syncedFiles[] = $filename;
                }
            }
        }
    }

    return count($syncedFiles) > 0 ? [
        "status" => "success",
        "message" => count($syncedFiles) . " file(s) synced successfully.",
        "files" => $syncedFiles
    ] : [
        "status" => "info",
        "message" => "No files were synced.",
        "files" => []
    ];
}

function getRemoteFileViaCurl($url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false, // Use true in production with valid SSL
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (PHP cURL)'
    ]);

    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($data !== false && $httpCode === 200) {
        return $data;
    } else {
        // Optionally log or return error details
        // error_log("Failed to fetch $url - HTTP $httpCode - Error: $error");
        return false;
    }
}

function storeFileMetadataInDB($conn, $companyid, $subcompanyid, $projectid, $filename, $filepath) {
    // Sanitize and validate file
    if (!file_exists($filepath)) {
        error_log("File does not exist: " . $filepath);
        return false;
    }

    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $file_datetime = date("Y-m-d H:i:s", filemtime($filepath));
    $filesize = filesize($filepath);

    // Check if file already exists in DB
    $stmt = $conn->prepare("SELECT id FROM tbl_synced_files WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND filename = ?");
    $stmt->bind_param("iiis", $companyid, $subcompanyid, $projectid, $filename);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        return false; // Already exists
    }

    $stmt->close();

    // Insert file metadata
    $insert = $conn->prepare("
        INSERT INTO tbl_synced_files (
            companyid, subcompanyid, projectid, filename, filepath, file_extension, file_datetime, filesize
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param(
        "iiissssi",
        $companyid,
        $subcompanyid,
        $projectid,
        $filename,
        $filepath,
        $file_extension,
        $file_datetime,
        $filesize
    );

    if ($insert->execute()) {
        $insert->close();
        return true;
    } else {
        error_log("Insert Error: " . $insert->error);
        $insert->close();
        return false;
    }
}
function checkIfFileExistsInDB($conn, $companyid, $subcompanyid, $projectid, $filename) {
    $stmt = $conn->prepare("SELECT id FROM tbl_synced_files WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND filename = ?");
    $stmt->bind_param("iiis", $companyid, $subcompanyid, $projectid, $filename);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}
?>