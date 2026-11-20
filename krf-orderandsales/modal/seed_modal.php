<?php
error_reporting(0);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/seed_functions.php");
include("../functions/product_functions.php");
$action = $_GET['action'];
if ($action == "deleteSeed") {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $SeedID = $_POST['SeedID'] ?? '';
    $result = deleteSeed($krfconn, $SeedID);
    echo json_encode($result);
  } else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
  }
} else if ($action == "GetSuggestedSKUSeed") {
  $ProjectId = $_POST['projectid'] ?? 0;
  $CategoryID = $_POST['CategoryID'] ?? 0;
  $SubCategoryID = $_POST['SubCategoryID'] ?? 0;
  $VarietyName = $_POST['VarietyName'] ?? '';
  $TypeOfSeed = $_POST['TypeOfSeed'] ?? '';
  $BrandName = $_POST['BrandName'] ?? '';
  $SeedID = $_POST['SeedID'] ?? null;
  $Seedsku = generateSeedSKU($krfconn, $ProjectId, $CategoryID, $SubCategoryID, $VarietyName, $TypeOfSeed, $BrandName, $SeedID);
  echo json_encode($Seedsku);
} else if ($action == "GetExistingSeedSpecs") {
  $ProjectId = $_POST['projectid'] ?? 0;
  $CategoryID = $_POST['CategoryID'] ?? '';
  $SubCategoryID = $_POST['SubCategoryID'] ?? '';

  $data = [];
  if (!empty($SubCategoryID)) {
    $sql = "SELECT WeightPerPacket, CountPerPackate, growingdays, COUNT(*) as usage_count 
            FROM seed_master 
            WHERE (
                SubCategoryID = ? 
                OR SubCategoryID IN (
                    SELECT SubCategoryID FROM plant_subcategory_master 
                    WHERE SubCategoryName = (SELECT SubCategoryName FROM plant_subcategory_master WHERE SubCategoryID = ? LIMIT 1)
                )
            )
            AND ((WeightPerPacket IS NOT NULL AND WeightPerPacket != '' AND WeightPerPacket != '0')
              OR (CountPerPackate IS NOT NULL AND CountPerPackate != '' AND CountPerPackate != '0'))
            GROUP BY WeightPerPacket, CountPerPackate, growingdays
            ORDER BY usage_count DESC, WeightPerPacket ASC
            LIMIT 10";
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("ss", $SubCategoryID, $SubCategoryID);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $data[] = [
        'weight' => trim($row['WeightPerPacket']),
        'count' => trim($row['CountPerPackate']),
        'growingdays' => trim($row['growingdays']),
        'usage_count' => (int)$row['usage_count']
      ];
    }
    $stmt->close();
  }
  echo json_encode($data);
} else if ($action == "saveupdateseeddetails") {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mainPhotoPath = '';
    $otherPhotoPaths = [];
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';

    $targetDir = '../../attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/ProductSeed/' . date('Y-m-d') . "/";

    if (!is_dir($targetDir))
      mkdir($targetDir, 0777, true);

    // Upload Main Photo
    if (isset($_FILES['uploadMainPhoto']) && $_FILES['uploadMainPhoto']['error'] === 0) {
      $filename = time() . '_' . basename($_FILES['uploadMainPhoto']['name']);
      $targetPath = $targetDir . $filename;

      if (move_uploaded_file($_FILES['uploadMainPhoto']['tmp_name'], $targetPath)) {
        $mainPhotoPath = $targetPath;
      }
      if ($mainPhotoPath) {
        $mainPhotoPath = str_replace("../", "", $mainPhotoPath);
      }
    }
    // $print_r($_FILES);
    // Upload Other Photos (Multiple)
    if (isset($_FILES['uploadOtherPhotos'])) {
      foreach ($_FILES['uploadOtherPhotos']['tmp_name'] as $key => $tmpName) {
        if ($_FILES['uploadOtherPhotos']['error'][$key] === 0) {
          $filename = time() . '_' . basename($_FILES['uploadOtherPhotos']['name'][$key]);
          $targetPath = $targetDir . $filename;

          if (move_uploaded_file($tmpName, $targetPath)) {
            $otherPhotoPaths[] = str_replace("../", "", $targetPath);
          }
        }
      }
    }

    $otherPhotosSerialized = json_encode($otherPhotoPaths, JSON_UNESCAPED_SLASHES);
    // Example function call
    $result = saveSeedMaster($krfconn, $_POST, $mainPhotoPath, $otherPhotosSerialized);

    echo json_encode($result);
  } else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
  }
} else if ($action == "SeedDataOnlyOption") {
  $projectid = $_POST['projectid'] ?? 0;
  $shortby = $_POST['shortby'] ?? '';
  $shortbyvalue = $_POST['shortbyvalue'] ?? '';
  if ($shortby == "all") {
    $shortBy = ['all' => 'all'];
  }else if ($shortby == "categoryid") {
    $shortBy = ['CategoryID' => $shortbyvalue];
  } else if ($shortby == "onlyrootstock") {
    $shortBy = ['CategoryID' => $shortbyvalue];
  } else if ($shortby == "categoryidwithsubcategory") {
    $expcatsb = explode("|", $shortbyvalue);
    $categoryid = $expcatsb[0];
    $subcategoryid = $expcatsb[1];
    $shortBy = ['CategoryID' => $categoryid, 'SubCategoryID' => $subcategoryid];
  } else {
    $shortBy = [];
  }
  $seeddt = getSeedData($krfconn, $shortBy, 'SeedName', "ASC");
  echo json_encode($seeddt);
}
?>