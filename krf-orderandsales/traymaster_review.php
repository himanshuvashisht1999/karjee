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
  include('functions/vendor_functions.php');
  include('functions/product_functions.php');
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
  // include('functions/orders_functions.php');
  include('functions/product_functions.php');
  include('functions/tray_functions.php');
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
<div class="container-main">
  <div class="card premium-card mt-1">
    <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
      <div>
        <h4 class="mb-0 fw-bold text-dark">Tray Master</h4>
        <small class="text-muted">Manage production layouts & material types</small>
      </div>
      <div class="header-actions d-flex align-items-center">
        <button class="btn btn-sm btn-outline-info" onclick="window.location.reload();"> <i class="fa fa-sync"></i>
          Refresh</button>
        <button class="btn btn-premium newTraymaster ms-2"><i class="fa fa-plus-circle"></i> Add New Tray </button>
        <div class="badge bg-soft-primary px-3 py-2 rounded-pill ms-2">
          <i class="fa fa-layer-group me-1"></i>
          <?php
          $prdata = getTrayData($krfconn);
          echo count($prdata);
          ?> Records
        </div>
      </div>
    </div>
    <div class="card-body bg-light-soft">
      <div id="trayContainer">
        <?php
        if (count($prdata) > 0):
          $sno = 1;
          foreach ($prdata as $row):
            $isInactive = (strtolower($row['status']) != 'active');
            $statusColor = $isInactive ? '#e53e3e' : '#38a169';
            ?>
            <div class="tray-strip-card <?php echo $isInactive ? 'inactive' : ''; ?>">
              <div class="tray-id-badge"
                style="background: <?php echo $isInactive ? '#cbd5e0' : 'linear-gradient(to bottom, #667eea, #764ba2)'; ?>">
              </div>
              <div class="row align-items-center g-3">
                <!-- Main Info -->
                <div class="col-lg-3 col-md-4">
                  <div class="data-label">Tray Information</div>
                  <div class="tray-name-main"><?php echo htmlspecialchars($row['tray_name']); ?></div>
                  <span class="category-pill"><?php echo htmlspecialchars($row['category_name']); ?></span>
                </div>

                <!-- Cavity Details -->
                <div class="col-lg-2 col-md-4 col-6">
                  <div class="data-label">Cavity Config</div>
                  <div class="data-value">
                    <i class="fa fa-th-large text-muted me-1"></i>
                    <?php echo htmlspecialchars($row['cavity_count'] ?: '0'); ?> Holes
                  </div>
                  <div class="small text-muted mt-1"><?php echo htmlspecialchars($row['cavity_size'] ?: 'N/A'); ?> inch size
                  </div>
                </div>

                <!-- Material & Status -->
                <div class="col-lg-2 col-md-4 col-6">
                  <div class="data-label">Material & Status</div>
                  <div class="data-value"><?php echo htmlspecialchars($row['material']); ?></div>
                  <div class="mt-1">
                    <span class="badge rounded-pill"
                      style="background: <?php echo $statusColor; ?>20; color: <?php echo $statusColor; ?>; border: 1px solid <?php echo $statusColor; ?>40;">
                      <i class="fa fa-circle me-1" style="font-size: 8px;"></i> <?php echo ucfirst($row['status']); ?>
                    </span>
                  </div>
                </div>

                <!-- Timestamps -->
                <div class="col-lg-2 d-none d-lg-block">
                  <div class="data-label">Last Updated</div>
                  <div class="data-value small"><?php echo date('d M, Y', strtotime($row['updated_at'])); ?></div>
                  <div class="small text-muted"><?php echo date('h:i A', strtotime($row['updated_at'])); ?></div>
                </div>

                <!-- Actions -->
                <div class="col-lg-3 col-md-12 text-lg-end">
                  <div class="action-toolbar">
                    <button class="btn action-btn btn-edit editTrayBtn" data-id="<?php echo $row['tray_id']; ?>"
                      title="Edit">
                      <i class="fa fa-edit"></i>
                    </button>

                    <?php if (!$isInactive): ?>
                      <button class="btn action-btn btn-delete deleteTrayBtn" data-id="<?php echo $row['tray_id']; ?>"
                        title="Deactivate">
                        <i class="fa fa-power-off"></i>
                      </button>
                    <?php else: ?>
                      <button class="btn action-btn btn-success activateTrayBtn" data-id="<?php echo $row['tray_id']; ?>"
                        title="Reactivate"
                        style="width:32px; height:32px; padding:0; border-radius:6px; background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9;">
                        <i class="fa fa-check-circle"></i>
                      </button>
                    <?php endif; ?>

                    <button class="btn action-btn btn-outline-danger permDeleteTrayBtn"
                      data-id="<?php echo $row['tray_id']; ?>" title="Permanent Delete">
                      <i class="fa fa-trash-alt"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <?php
          endforeach;
        else:
          ?>
          <div class="text-center py-5">
            <div class="opacity-25 mb-3">
              <i class="fa fa-box-open fa-4x"></i>
            </div>
            <h5 class="text-muted">No tray records found</h5>
            <p class="small text-muted">Click 'Add New Tray' to create your first production tray.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="TrayMasterModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="max-width:98%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tray Details</h5>
        <button type="button" class="close" onclick="$('#TrayMasterModal').modal('hide')">&times;</button>
      </div>
      <div class="modal-body" id="TrayMasterContent">
      </div>
    </div>
  </div>
</div>
<?php
if (isset($_GET['dz'])) {

} else {
  include('../footer.php');
}
?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<script>
  $(document).on("click", ".newTraymaster", function () {
    let routurl = $("body").data("routurl");
    let key = $("body").data("key");
    $("#TrayMasterContent").load(routurl + "krf-orderandsales/addNewTray.php?k=" + key + "&dz=11");
    $("#TrayMasterModal").modal("show");
  });

  $(document).on("click", ".editTrayBtn", function () {
    let tray_id = $(this).data("id");
    let routurl = $("body").data("routurl");
    let key = $("body").data("key");
    $("#TrayMasterContent").load(routurl + "krf-orderandsales/addNewTray.php?k=" + key + "&dz=11&tray_id=" + tray_id);
    $("#TrayMasterModal").modal("show");
  });

  $(document).on("click", ".deleteTrayBtn", function () {
    let tray_id = $(this).data("id");
    let routurl = $("body").data("routurl");

    Swal.fire({
      title: 'Deactivate Tray?',
      text: "This tray will be marked as InActive!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, deactivate!'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: routurl + "krf-orderandsales/modal/tray_modal.php?action=updateTrayStatus",
          type: "POST",
          data: { tray_id: tray_id, status: 'InActive' },
          dataType: "json",
          success: function (response) {
            if (response.status === "success") {
              Swal.fire('Deactivated!', response.message, 'success').then(() => {
                location.reload();
              });
            } else {
              Swal.fire('Error', response.message, 'error');
            }
          }
        });
      }
    });
  });

  $(document).on("click", ".activateTrayBtn", function () {
    let tray_id = $(this).data("id");
    let routurl = $("body").data("routurl");

    $.ajax({
      url: routurl + "krf-orderandsales/modal/tray_modal.php?action=updateTrayStatus",
      type: "POST",
      data: { tray_id: tray_id, status: 'Active' },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          Swal.fire('Activated!', response.message, 'success').then(() => {
            location.reload();
          });
        } else {
          Swal.fire('Error', response.message, 'error');
        }
      }
    });
  });

  $(document).on("click", ".permDeleteTrayBtn", function () {
    let tray_id = $(this).data("id");
    let routurl = $("body").data("routurl");

    Swal.fire({
      title: 'PERMANENT REMOVE?',
      text: "This action cannot be undone!",
      icon: 'error',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, DELETE PERMANENTLY!'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: routurl + "krf-orderandsales/modal/tray_modal.php?action=deleteTrayPermanent",
          type: "POST",
          data: { tray_id: tray_id },
          dataType: "json",
          success: function (response) {
            if (response.status === "success") {
              Swal.fire('Deleted!', response.message, 'success').then(() => {
                location.reload();
              });
            } else {
              Swal.fire('Error', response.message, 'error');
            }
          }
        });
      }
    });
  });
</script>