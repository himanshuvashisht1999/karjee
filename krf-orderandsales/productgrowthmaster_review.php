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
  include('functions/vendor_functions.php');
  include('functions/seed_functions.php');
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
<div class="container-main">
  <div class="card mt-1">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Product Growth Stages</h4>
      <div class="header-actions">
        <button class="btn btn-sm btn-outline-info" onclick="window.location.reload();"> <i class="fa fa-sync"></i>
          Refresh</button>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table custom-table mb-0" id="ProductionGrowthtbl">
          <thead class="table-light">
            <tr>
              <th>S.NO.</th>
              <th>Product Name</th>
              <th>Full Cycle Days</th>
              <th>Germination Days</th>
              <th>Early Seedling Days</th>
              <th>True Leaf Seedling Days</th>
              <th>Grafting Days</th>
              <th>Healing Chamber Days</th>
              <th>Hardening Days</th>
              <th>Clip Removal Days</th>
              <th>Sessional Correction Days</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="ProductGrowthStageModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="max-width:98%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="ProductGrowthStageHeader">Product Details</h5>
        <button type="button" class="close" onclick="$('#ProductGrowthStageModal').modal('hide')">&times;</button>
      </div>
      <div class="modal-body" id="ProductGrowthStageContent">
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

  GetGrowthstages_callback(function (response) {

    let PtableId = $("#ProductionGrowthtbl tbody");
    PtableId.empty(); // clear existing rows

    // ✅ Validate response
    if (!response || response.status !== "success") {
      PtableId.append(`
            <tr>
                <td colspan="12" class="text-center text-danger">
                    Failed to load data
                </td>
            </tr>
        `);
      return;
    }

    let data = response.data || [];

    if (data.length > 0) {

      let ptbltr = "";

      for (let i = 0; i < data.length; i++) {
        let row = data[i];

        /* ✅ Build session correction days display */
        let sessionHtml = "-";
        if (row.correctiondays && typeof row.correctiondays === "object") {
          let parts = [];
          for (let session in row.correctiondays) {
            let val = row.correctiondays[session];
            let sign = val > 0 ? "+" : "";
            parts.push(`${session}: ${sign}${val}`);
          }
          if (parts.length > 0) {
            sessionHtml = parts.join("<br>");
          }
        }

        ptbltr += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${row.ProductName ?? '-'}</td>
                    <td>${row.fullcycledays ?? '-'}</td>
                    <td>${row.SeedGerminationdays ?? '-'}</td>
                    <td>${row.EarlySeedlingdays ?? '-'}</td>
                    <td>${row.TrueLeafSeedlingdays ?? '-'}</td>
                    <td>${row.GraftingOperationdays ?? '-'}</td>
                    <td>${row.HealingChamberdays ?? '-'}</td>
                    <td>${row.Hardeningdays ?? '-'}</td>
                    <td>${row.ClipRemovaldays ?? '-'}</td>

                    <!-- ✅ Session Correction Days -->
                    <td class="text-start small">
                        ${sessionHtml}
                    </td>

                    <!-- ✅ Action -->
                    <td>
                        <button class="btn btn-warning btn-sm"  onclick="AddUpdateProductionGrowthhtml(${row.productid},${row.id});">
                            Edit
                        </button>
                    </td>
                </tr>
            `;
      }

      PtableId.append(ptbltr);

    } else {

      // ✅ No data case
      PtableId.append(`
            <tr>
                <td colspan="12" class="text-center text-muted">
                    No data available
                </td>
            </tr>
        `);
    }
  });


</script>