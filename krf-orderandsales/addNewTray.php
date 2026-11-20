<?php
$psodate = "";
$clientname = "";
$mobileno = "";
$emailid = "";
$deliverydate = "";
$psoamount = "";
if (isset($_GET['dz'])) {
  require('../connection.php');
  require('../functions/function_master.php');
  include('functions/orders_functions.php');
  $key = $_GET['k'];
  $key_decoded = base64url_decode($key);
  $key_parts = explode("|", $key_decoded);
  $checkkey = base64url_decode($key_parts[count($key_parts) - 1]);
  $actkey = "";
  for ($i = 0; $i < count($key_parts) - 1; $i++) {
    $actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
  }
  if ($actkey != $checkkey) {
    echo "<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
    die;
  }
  $loginname = "";
  $loginid = $key_parts[0];
  $loginroleid = $key_parts[1];
  $companyid = $key_parts[2];
  $subcompanyid = $key_parts[3];
  $projectid = $key_parts[4];
  $logintype = $key_parts[5];
  $newkey = "x|x|" . $key_parts[2] . "|" . $key_parts[3] . "|" . $key_parts[4] . "|x";
  $encodednewkey = base64url_encode($newkey);
  $keycompany = base64url_encode($newkey . "|" . $encodednewkey);
  $cmpinfo = getcompanydetails($mconn, "companyid", $companyid);
  $companyname = "";
  if (count($cmpinfo) > 0) {
    foreach ($cmpinfo as $cmphkey) {
      $companyname = $cmphkey['company_name'];
      $countryid = $cmphkey['countryid'];
    }
  }

  $subcmpinfo = getsubcompanydetails($mconn, $companyid, $subcompanyid, "subcompanyid", $subcompanyid);
  $subcompanyname = "";
  if (count($subcmpinfo) > 0) {
    foreach ($subcmpinfo as $subcmphkey) {
      $subcompanyname = $subcmphkey['subcompanyname'];
    }
  }

  //projectinfo
  $prj = getprojectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
  // print_r($prj);
  $projectname = "";
  if (count($prj) > 0) {
    foreach ($prj as $pjkey) {
      $projectname = $pjkey['projectname'];
    }
  }

  $tray_id = $_GET['tray_id'] ?? 0;
  $tray_data = [];
  if ($tray_id > 0) {
    include('functions/tray_functions.php');
    $trays = getTrayData($krfconn, 'tray_id', $tray_id);
    if (count($trays) > 0) {
      $tray_data = $trays[0];
    }
  }

  $protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
  $url = basename($_SERVER["PHP_SELF"]);
  $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
  $baseurl = $rooturl . str_replace($url, "", $_SERVER["PHP_SELF"]);
  $countryid = "";
  if ($_SERVER['HTTP_HOST'] == "localhost") {
    $prefix = $rooturl . "/krf_amanaccounting/";
  } else {
    $prefix = $rooturl . "/";
  }
  $rooturl = $prefix;
} else {
  require('../header.php');
  include('functions/orders_functions.php');
  $rooturl;
}
$mdid = "";
if (isset($_GET['mdid'])) {
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
  $groupid = $dddr[0]['groupid'];
}

?>
<link rel="stylesheet" href="<?php echo $prefix; ?>krf-orderandsales/css/custom_production.css">
<div class="<?php echo isset($_GET['dz']) ? 'container-main' : 'container'; ?>">
  <!-- Header -->
  <div class="text-center mb-4 <?php echo isset($_GET['dz']) ? 'd-none' : ''; ?>">
    <h2 class="fw-bold"><?php echo ($tray_id > 0) ? 'Edit' : 'New'; ?> Tray Details</h2>
  </div>

  <div class="row <?php echo isset($_GET['dz']) ? 'd-none' : ''; ?>">
    <div class="col-md-12">
      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</button>
    </div>
  </div>
  <div class="card premium-card mt-2">
    <div class="card-body">
      <form id="newTrayMasterForm" class="container py-3" method="post" action="#" novalidate>
        <div class="row g-3">
          <div class="col-md-6">
            <label for="TrayName" class="form-label">Tray Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="TrayName" name="TrayName"
              value="<?php echo $tray_data['tray_name'] ?? ''; ?>" placeholder="-- e.g. 98 Cavity Plastic Tray"
              required>
          </div>
          <div class="col-md-6">
            <label for="TrayCategoryId" class="form-label">Tray Category Name</label>
            <select class="form-control" id="TrayCategoryId" name="TrayCategoryId"
              data-selected="<?php echo $tray_data['category_id'] ?? ''; ?>"></select>
          </div>
        </div>
        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="CavityCount" class="form-label">Cavity Count</label>
            <input type="text" class="form-control" id="CavityCount" name="CavityCount"
              value="<?php echo $tray_data['cavity_count'] ?? ''; ?>" placeholder="e.g. 98, 128"
              onkeypress="return isNumberKey(event)" />
          </div>

          <div class="col-md-4">
            <label for="CavitySize" class="form-label">Cavity Size (inch)</label>
            <input type="text" class="form-control" id="CavitySize" name="CavitySize"
              value="<?php echo $tray_data['cavity_size'] ?? ''; ?>" placeholder="e.g. 1.5 inch, 1 inch"
              onkeypress="return isNumberKey(event)" />
          </div>

          <div class="col-md-4">
            <label for="material" class="form-label">Material of Tray</label>
            <input type="text" class="form-control" id="material" name="material"
              value="<?php echo $tray_data['material'] ?? ''; ?>" placeholder="-- Plastic, Thermocol etc." required>
          </div>

        </div>
        <div class="row g-3 mt-2">
          <div class="col-12">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description"
              rows="2"><?php echo $tray_data['description'] ?? ''; ?></textarea>
          </div>
        </div>
        <div class="row mt-4">
          <div class="col-md-12 text-center">
            <!-- Hidden fields -->
            <input type="hidden" name="tray_id" value="<?php echo $tray_id; ?>">
            <input type="hidden" name="TrayCode" id="TrayCode" value="<?php echo $tray_data['tray_code'] ?? ''; ?>">
            <input type="hidden" name="ProjectID" value="<?php echo $projectid; ?>">
            <input type="hidden" name="loginid" value="<?php echo $loginid; ?>">
            <button type="submit" class="btn btn-premium px-5"><?php echo ($tray_id > 0) ? 'Update' : 'Save'; ?> Tray
              Details</button>
            <button type="reset" class="btn btn-outline-secondary ms-2" <?php echo ($tray_id > 0) ? 'style="display:none;"' : ''; ?>>Reset</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<?php
if (isset($_GET['dz'])) {
} else {
  include("../footer.php");
}
?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>

<script>
  $(document).ready(function () {
    let selectedCat = $("#TrayCategoryId").attr("data-selected");
    GetTrayCategoryData_option('TrayCategoryId', selectedCat);

    $("#newTrayMasterForm").on("submit", function (e) {
      e.preventDefault(); // stop default form submit
      $.ajax({
        url: "modal/tray_modal.php?action=saveupdateTrayDetails",
        type: "POST",
        data: $(this).serialize(), // send all form fields
        dataType: "json",
        success: function (response) {
          if (response.status === "success") {
            Swal.fire('Success', response.message, 'success').then(() => {
              if ($("body").hasClass("modal-open")) {
                $("#TrayMasterModal").modal("hide");
                location.reload();
              }
            });
          } else {
            Swal.fire('Warning', response.message, 'warning');
          }
        },
        error: function (xhr, status, error) {
          Swal.fire('Error', 'AJAX Error: ' + error, 'error');
        }
      });
    });
  });
</script>