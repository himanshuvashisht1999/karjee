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
$activeTrays = getTrayData($krfconn);
?>
<link rel="stylesheet" href="<?php echo $prefix; ?>krf-orderandsales/css/custom_production.css">
<style>
/* ======================================================
   POLYHOUSE MASTER — Mobile-First Responsive Styles
   ====================================================== */

/* Push page content down below the sticky/fixed navbar */
.container-main {
  margin-top: 40px !important;
  padding: 15px 15px 40px !important;
}
@media (max-width: 768px) {
  .container-main {
    margin-top: 25px !important;
    padding: 10px 8px 30px !important;
  }
}

/* Modal Z-Index and Top Positioning Fix (Prevents header from covering modal) */
.modal {
  z-index: 100050 !important;
}
.modal-backdrop {
  z-index: 100040 !important;
}
.modal-dialog {
  margin-top: 85px !important;
  margin-bottom: 50px !important;
}
@media (max-width: 768px) {
  .modal-dialog {
    margin-top: 75px !important;
    margin-bottom: 30px !important;
    max-width: calc(100vw - 1rem) !important;
  }
}

/* Card transitions */
.polyhouse-strip-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-left: 5px solid #0284c7 !important;
}
.polyhouse-strip-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.15), 0 4px 10px -5px rgba(0,0,0,0.1) !important;
}

/* Page header */
.pm-page-header {
  background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
  border-radius: 12px 12px 0 0;
  padding: 1.15rem 1.35rem;
}

/* Header & Card Icon Avatars */
.pm-header-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.22) !important;
  color: #ffffff !important;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.pm-header-icon svg {
  width: 24px;
  height: 24px;
}

.polyhouse-icon-avatar {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: #e0f2fe !important;
  color: #0284c7 !important;
  border: 1px solid #bae6fd !important;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 5px rgba(2, 132, 199, 0.12);
}
.polyhouse-icon-avatar svg {
  width: 24px;
  height: 24px;
}

/* Structure Badge */
.polyhouse-structure-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #e0f2fe !important;
  color: #0369a1 !important;
  border: 1px solid #bae6fd !important;
  font-size: 0.75rem !important;
  font-weight: 600 !important;
  padding: 2px 8px !important;
  border-radius: 6px !important;
}

/* Summary stat badges */
.pm-stat-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.85rem;
  background: #fff;
  border-radius: 50px;
  border: 1px solid #e2e8f0;
  font-size: 0.82rem;
  white-space: nowrap;
}
.pm-stat-badge .pm-stat-num {
  font-weight: 700;
  font-size: 1rem;
  color: #0284c7;
}

/* Section heading pill */
.section-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  color: #1d4ed8;
  font-weight: 600;
  font-size: 0.8rem;
  padding: 0.3rem 0.75rem;
  border-radius: 50px;
  letter-spacing: 0.04em;
  margin-bottom: 0.75rem;
}

/* Quick-add location panel */
.quick-add-location-panel {
  background: #f0fdf4;
  border: 1px dashed #86efac;
  border-radius: 10px;
  padding: 1rem;
  margin-top: 0.75rem;
  display: none;
}

/* Bed table on mobile — stacked cards */
@media (max-width: 767.98px) {
  /* Stack bed-rack table rows as cards */
  #bedRackTable thead { display: none; }
  #bedRackTable tbody tr {
    display: block;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  }
  #bedRackTable tbody td {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.35rem 0.25rem;
    border: none !important;
    font-size: 0.85rem;
  }
  #bedRackTable tbody td::before {
    content: attr(data-label);
    font-weight: 600;
    color: #64748b;
    flex-basis: 50%;
    font-size: 0.78rem;
  }
  #bedRackTable tfoot { display: none; }

  /* Make modal nearly full-screen on mobile */
  .modal-dialog.modal-lg { margin: 0.5rem; max-width: calc(100vw - 1rem); }

  /* Compact strip cards on mobile */
  .strip-left-info { min-width: unset !important; }
  .polyhouse-strip-card .card-body { flex-direction: column; align-items: flex-start !important; gap: 0.75rem !important; }

  /* Header actions stack on mobile */
  .pm-header-actions { flex-direction: column; width: 100%; }
  .pm-header-actions > * { width: 100% !important; }
  #locationFilter { min-width: unset !important; width: 100% !important; }
}

/* Filter row */
.pm-filter-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.65rem;
}

/* Tab nav inside modal */
.pm-tab-nav .nav-link {
  border-radius: 8px 8px 0 0;
  font-size: 0.88rem;
  padding: 0.45rem 1rem;
  color: #64748b;
}
.pm-tab-nav .nav-link.active {
  background: #0284c7;
  color: #fff;
  border-color: #0284c7;
}

/* Btn size tweaks */
.btn-icon-sm {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  padding: 0;
}

/* Post-save refresh banner */
.pm-refresh-banner {
  background: linear-gradient(135deg, #ecfdf5, #d1fae5);
  border: 1px solid #6ee7b7;
  border-radius: 10px;
  padding: 0.65rem 1rem;
  display: none;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.88rem;
  color: #065f46;
  margin-bottom: 0.75rem;
}
</style>

<div class="container-main">
  <div class="card mt-1 shadow-sm border-0 rounded-3 overflow-hidden">

    <!-- ===================== PAGE HEADER ===================== -->
    <div class="pm-page-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="d-flex align-items-center gap-2">
        <div class="pm-header-icon me-2">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
          </svg>
        </div>
        <div>
          <h4 class="mb-0 fw-bold text-white">Polyhouse Master</h4>
          <small class="text-white opacity-75">Manage polyhouses &amp; movement locations</small>
        </div>
      </div>
      <div class="pm-header-actions d-flex align-items-center gap-2 flex-wrap">
        <!-- Location filter -->
        <div class="d-flex align-items-center gap-2 flex-grow-1">
          <i class="fa fa-filter text-white opacity-75"></i>
          <select id="locationFilter" class="form-select form-control" style="min-width:200px;height:36px;border-radius:8px;font-size:0.88rem;">
            <option value="all">All Locations</option>
          </select>
        </div>
        <!-- Refresh -->
        <button class="btn btn-sm btn-light text-primary fw-semibold" id="refreshBtn" title="Refresh List" style="height:36px;">
          <i class="fa fa-sync-alt me-1"></i> Refresh
        </button>
        <!-- New Location -->
        <button class="btn btn-sm btn-warning fw-semibold openLocationModal" title="Add Movement Location" style="height:36px;">
          <i class="fa fa-map-marker-alt me-1"></i> New Location
        </button>
        <!-- New Polyhouse -->
        <button class="btn btn-sm btn-light fw-semibold openpolihousemodal text-primary" title="Add Polyhouse" style="height:36px;">
          <i class="fa fa-plus-circle me-1"></i> New Polyhouse
        </button>
      </div>
    </div>

    <!-- ===================== STATS STRIP ===================== -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2" style="background:#f8fafc;border-bottom:1px solid #f1f5f9;">
      <div class="d-flex flex-wrap gap-2">
        <div class="pm-stat-badge">
          <i class="fa fa-warehouse text-primary" style="font-size:0.85rem;"></i>
          <span class="text-muted">Polyhouses:</span>
          <span class="pm-stat-num" id="totalPolyhousesCount">0</span>
        </div>
        <div class="pm-stat-badge">
          <i class="fa fa-layer-group text-success" style="font-size:0.85rem;"></i>
          <span class="text-muted">Total Beds:</span>
          <span class="pm-stat-num text-success" id="totalBedsCount">0</span>
        </div>
      </div>
      <div id="globalTrayTotals" class="d-flex gap-2 flex-wrap align-items-center"></div>
    </div>

    <!-- ===================== CARD BODY ===================== -->
    <div class="card-body p-2 p-md-3">

      <!-- Post-save refresh banner -->
      <div class="pm-refresh-banner" id="pmRefreshBanner">
        <i class="fa fa-check-circle text-success"></i>
        <span id="pmRefreshMsg">Saved successfully.</span>
        <button class="btn btn-sm btn-success ms-auto fw-semibold" id="pmBannerRefreshBtn">
          <i class="fa fa-sync-alt me-1"></i> Refresh List
        </button>
      </div>

      <!-- Strip Cards Container -->
      <div id="polyhouseListContainer" class="d-flex flex-column gap-2"></div>
    </div>
  </div>
</div>

<!-- =====================================================================
     MODAL 1: ADD / EDIT POLYHOUSE
     ===================================================================== -->
<div class="modal fade" id="polyhouseModal" tabindex="-1" aria-labelledby="polyhouseModalHeader" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">

      <!-- Header -->
      <div class="modal-header bg-primary text-white py-2 px-3 d-flex align-items-center justify-content-between">
        <h6 class="modal-title fw-bold d-flex align-items-center mb-0" id="polyhouseModalHeader">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
          </svg>
          <span id="polyhouseModalTitle">Add New Polyhouse</span>
        </h6>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity:0.95;font-size:1.75rem;line-height:1;background:transparent;border:none;cursor:pointer;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body p-3">
        <form id="polyhouseForm" method="post" novalidate>

          <!-- ── SECTION: Basic Information ── -->
          <div class="section-pill"><i class="fa fa-info-circle"></i> Basic Information</div>
          <div class="row g-2 mb-3">

            <!-- Polyhouse Location with inline add -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold mb-1" style="font-size:0.83rem;">
                <i class="fa fa-map-marker-alt text-danger me-1"></i>Polyhouse Location
              </label>
              <div class="input-group input-group-sm">
                <select class="form-select form-control" id="polyhouselocationid" name="polyhouselocationid" style="border-radius:8px 0 0 8px !important;"></select>
                <button type="button" class="btn btn-outline-success btn-sm px-2 toggleNewLocPanel" data-target="#inlineLocPanel" title="Add new location">
                  <i class="fa fa-plus"></i>
                </button>
              </div>
              <!-- Inline new location panel for polyhouse -->
              <div class="quick-add-location-panel" id="inlineLocPanel">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="fa fa-map-marker-alt text-success"></i>
                  <strong style="font-size:0.85rem;">Quick Add: New Location</strong>
                  <button type="button" class="btn btn-sm btn-link text-danger ms-auto p-0 toggleNewLocPanel" data-target="#inlineLocPanel" style="font-size:0.8rem;">✕ Cancel</button>
                </div>
                <div class="row g-2">
                  <div class="col-12 col-sm-6">
                    <input type="text" class="form-control form-control-sm" id="newLocName_ph" placeholder="Location Name *" />
                  </div>
                  <div class="col-12 col-sm-6">
                    <input type="text" class="form-control form-control-sm" id="newLocShortname_ph" placeholder="Short Name (optional)" />
                  </div>
                  <div class="col-12">
                    <input type="text" class="form-control form-control-sm" id="newLocAddress_ph" placeholder="Address (optional)" />
                  </div>
                  <div class="col-12 d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-success saveNewLocation" data-nameid="#newLocName_ph" data-shortid="#newLocShortname_ph" data-addrid="#newLocAddress_ph" data-target="#polyhouselocationid" data-panel="#inlineLocPanel">
                      <i class="fa fa-save me-1"></i> Save Location
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Polyhouse Name -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold mb-1" style="font-size:0.83rem;">
                Polyhouse Name <span class="text-danger">*</span>
              </label>
              <input type="text" class="form-control form-control-sm" id="polyhouse_name" name="polyhouse_name" placeholder="e.g. Polyhouse A" required style="border-radius:8px;">
            </div>

            <!-- Physical Location -->
            <div class="col-12">
              <label class="form-label fw-semibold mb-1" style="font-size:0.83rem;">Physical Location / Plot</label>
              <input type="text" class="form-control form-control-sm" id="physicallocation" name="physicallocation"
                placeholder="e.g. Plot No. 3, North Block" style="border-radius:8px;">
            </div>
          </div>

          <!-- ── SECTION: Structure Details ── -->
          <div class="section-pill"><i class="fa fa-cubes"></i> Structure Details</div>
          <div class="row g-2 mb-3">
            <div class="col-12">
              <label class="form-label fw-semibold mb-1" style="font-size:0.83rem;">
                <i class="fa fa-layer-group text-primary me-1"></i>Structure Type
              </label>
              <div class="input-group input-group-sm">
                <select class="form-select form-control form-control-sm StructureType" id="StructureType" name="StructureType" style="border-radius:8px 0 0 8px !important;">
                  <option value="PolyHouse" data-prefix="Bed-">Poly House</option>
                  <option value="CoolingChamber" data-prefix="Rack-">Cooling Chamber</option>
                  <option value="NetHouse" data-prefix="Bed-">Net House</option>
                  <option value="ShadeHouse" data-prefix="Bed-">Shade House</option>
                  <option value="GlassHouse" data-prefix="Bed-">Glass House</option>
                  <option value="OpenField" data-prefix="Bed-">Open Field</option>
                  <option value="Tunnel" data-prefix="Bed-">Tunnel / Low Tunnel</option>
                  <option value="GerminationRoom" data-prefix="Rack-">Germination Room</option>
                  <option value="HardeningChamber" data-prefix="Bed-">Hardening Chamber</option>
                  <option value="other">+ Add Custom Structure Type...</option>
                </select>
                <button type="button" class="btn btn-outline-primary btn-sm px-2 toggleNewStructurePanel" data-target="#inlineStructurePanel" title="Add new structure type">
                  <i class="fa fa-plus"></i>
                </button>
              </div>

              <!-- Inline quick-add structure type panel -->
              <div class="quick-add-location-panel" id="inlineStructurePanel" style="background:#eff6ff;border:1px dashed #93c5fd;">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="fa fa-cubes text-primary"></i>
                  <strong style="font-size:0.85rem;" class="text-primary">Quick Add: New Structure Type</strong>
                  <button type="button" class="btn btn-sm btn-link text-danger ms-auto p-0 toggleNewStructurePanel" data-target="#inlineStructurePanel" style="font-size:0.8rem;">✕ Cancel</button>
                </div>
                <div class="row g-2">
                  <div class="col-12 col-sm-7">
                    <input type="text" class="form-control form-control-sm" id="newStructureTypeName" placeholder="Structure Type Name * (e.g. Nursery Block 2, Drying Shed)" />
                  </div>
                  <div class="col-12 col-sm-5">
                    <select class="form-select form-control form-control-sm" id="newStructurePrefixType">
                      <option value="Bed-">Uses Beds (Bed-1, Bed-2...)</option>
                      <option value="Rack-">Uses Racks (Rack-1, Rack-2...)</option>
                    </select>
                  </div>
                  <div class="col-12 d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-primary" id="saveNewStructureTypeBtn">
                      <i class="fa fa-check me-1"></i> Add Structure Type
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ── SECTION: Bed / Rack Details ── -->
          <div id="bedSection" class="mb-3">
            <div class="section-pill"><i class="fa fa-table"></i> Bed / Rack Details</div>
            <div class="table-responsive rounded-2 border" style="max-height:320px;overflow-y:auto;">
              <table class="table table-bordered table-sm align-middle mb-0" id="bedRackTable" style="min-width:500px;">
                <thead class="table-primary sticky-top" style="top:0;z-index:1;">
                  <tr>
                    <th style="min-width:120px;">Bed / Rack Name</th>
                    <th style="min-width:90px;">Layers</th>
                    <?php foreach ($activeTrays as $tray): ?>
                      <th class="text-center" style="min-width:100px;"><?php echo $tray['tray_name']; ?>
                        <br/><small class="text-muted">(<?php echo $tray['cavity_count']; ?> Holes)</small>
                      </th>
                    <?php endforeach; ?>
                    <th class="text-center" style="width:60px;">
                      <button type="button" class="btn btn-success btn-sm px-2 py-0 addBedRow" title="Add Row">
                        <i class="fa fa-plus"></i>
                      </button>
                    </th>
                  </tr>
                </thead>
                <tbody></tbody>
                <tfoot class="table-info">
                  <tr>
                    <th colspan="2" class="text-end small">Total Capacity</th>
                    <?php foreach ($activeTrays as $tray): ?>
                      <th class="text-center totalTrayType small" data-trayid="<?php echo $tray['tray_id']; ?>">0</th>
                    <?php endforeach; ?>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- ── SECTION: Additional Details ── -->
          <div class="section-pill"><i class="fa fa-comment-alt"></i> Additional Details</div>
          <div class="row g-2 mb-3">
            <div class="col-12">
              <label class="form-label fw-semibold mb-1" style="font-size:0.83rem;">Description</label>
              <textarea class="form-control form-control-sm" id="description" name="description" rows="2" style="border-radius:8px;"></textarea>
            </div>
          </div>

          <!-- Hidden fields -->
          <input type="hidden" id="polyhouseid" name="polyhouseid">
          <input type="hidden" id="ProjectID" name="ProjectID" value="<?php echo $projectid; ?>">
          <input type="hidden" name="loginid" value="<?php echo $loginid; ?>">

          <!-- Footer -->
          <div class="d-flex justify-content-end gap-2 pt-2 border-top flex-wrap">
            <button type="reset" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
              <i class="fa fa-undo me-1"></i> Reset
            </button>
            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4" id="polyhouseSaveBtn">
              <i class="fa fa-save me-1"></i> Save Polyhouse
            </button>
          </div>

        </form>
      </div><!-- /modal-body -->
    </div>
  </div>
</div>

<!-- =====================================================================
     MODAL 2: ADD / EDIT MOVEMENT LOCATION
     ===================================================================== -->
<div class="modal fade" id="locationMasterModal" tabindex="-1" aria-labelledby="locationMasterModalHeader" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable" style="max-width:520px;">
    <div class="modal-content border-0 shadow">

      <!-- Header -->
      <div class="modal-header py-2 px-3" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);">
        <h6 class="modal-title fw-bold text-dark">
          <i class="fa fa-map-marker-alt me-2"></i>Movement Locations
        </h6>
        <button type="button" class="btn-close btn-sm" data-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-3">

        <!-- ── Add New Location Form ── -->
        <div class="section-pill"><i class="fa fa-plus-circle"></i> Add New Location</div>
        <form id="locationMasterForm" novalidate>
          <div class="row g-2 mb-3">
            <div class="col-12">
              <label class="form-label fw-semibold mb-1" style="font-size:0.83rem;">Location Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="loc_locationname" name="locationname" placeholder="e.g. Farm Gate, Dispatch Area" required style="border-radius:8px;">
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label fw-semibold mb-1" style="font-size:0.83rem;">Short Name</label>
              <input type="text" class="form-control form-control-sm" id="loc_shortname" name="shortname" placeholder="e.g. FG" style="border-radius:8px;">
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label fw-semibold mb-1" style="font-size:0.83rem;">Address</label>
              <input type="text" class="form-control form-control-sm" id="loc_address" name="locationaddress" placeholder="Optional" style="border-radius:8px;">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold mb-1" style="font-size:0.83rem;">Comments</label>
              <textarea class="form-control form-control-sm" id="loc_comments" name="comments" rows="2" placeholder="Optional" style="border-radius:8px;"></textarea>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
              <button type="reset" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="fa fa-undo me-1"></i> Clear
              </button>
              <button type="submit" class="btn btn-sm rounded-pill px-4 fw-semibold" id="saveLocationBtn" style="background:#f59e0b;border-color:#f59e0b;color:#1e293b;">
                <i class="fa fa-save me-1"></i> Save Location
              </button>
            </div>
          </div>
        </form>

        <hr class="my-2">

        <!-- ── Existing Locations List ── -->
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="section-pill mb-0"><i class="fa fa-list"></i> Existing Locations</div>
          <button class="btn btn-sm btn-outline-secondary rounded-pill" id="refreshLocationListBtn" title="Refresh">
            <i class="fa fa-sync-alt me-1"></i> Refresh
          </button>
        </div>
        <div id="existingLocationsList" style="max-height:220px;overflow-y:auto;">
          <div class="text-center py-3 text-muted"><i class="fa fa-spinner fa-spin me-1"></i> Loading...</div>
        </div>

      </div><!-- /modal-body -->
    </div>
  </div>
</div>

<!-- =====================================================================
     MODAL 3: POLYHOUSE DETAIL VIEW (legacy)
     ===================================================================== -->
<div class="modal fade" id="PolyhouseMasterModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="max-width:98%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Polyhouse Details</h5>
        <button type="button" class="close" onclick="$('#PolyhouseMasterModal').modal('hide')">&times;</button>
      </div>
      <div class="modal-body" id="PolyhouseMasterContent"></div>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // ----------------------------------------------------------------
  //  PAGE SCRIPT – Polyhouse Master Review
  // ----------------------------------------------------------------
  const activeTrays = <?php echo json_encode($activeTrays); ?>;

  // ================================================================
  //  DOCUMENT READY
  // ================================================================
  $(document).ready(function () {

    /* ── ADD BED ROW ── */
    $(document).on("click", ".addBedRow", function () {
      let rowCount = $("#bedRackTable tbody tr").length;
      $("#bedRackTable tbody").append(renderBedRow(null, rowCount));
    });

    /* ── REMOVE BED ROW ── */
    $(document).on("click", ".removeBedRow", function () {
      if ($("#bedRackTable tbody tr").length === 1) {
        Swal.fire({ icon: "warning", title: "Warning", text: "At least one Bed / Rack row is required." });
        return;
      }
      $(this).closest("tr").remove();
      renameAllBeds();
      calculateTotalTrayCapacity();
    });

    /* ── TRAY CAPACITY INPUT ── */
    $(document).on("input", ".traycapacity", function () {
      calculateTotalTrayCapacity();
    });

    /* ── TOGGLE NEW STRUCTURE TYPE PANEL ── */
    $(document).on("click", ".toggleNewStructurePanel", function () {
      let target = $(this).data("target");
      $(target).slideToggle(200);
      if ($(target).is(":visible")) {
        $("#newStructureTypeName").focus();
      }
    });

    /* ── STRUCTURE TYPE CHANGE ── */
    $("#StructureType").on("change", function () {
      let val = $(this).val();
      if (val === "other") {
        $("#inlineStructurePanel").slideDown(200);
        $("#newStructureTypeName").focus();
        let prev = $(this).data("prev") || "PolyHouse";
        $(this).val(prev);
        return;
      }
      if ($("#bedRackTable tbody tr").length === 0) return;
      let previousValue = $(this).data("prev") || "PolyHouse";
      let selectElement = this;
      Swal.fire({
        title: 'Are you sure?',
        text: "Changing structure type will update all Bed/Rack names.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0284c7',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, change it!'
      }).then((result) => {
        if (result.isConfirmed) {
          renameAllBeds();
          $(selectElement).data("prev", $(selectElement).val());
        } else {
          $(selectElement).val(previousValue);
        }
      });
    });

    /* ── SAVE NEW STRUCTURE TYPE (inline quick add) ── */
    $(document).on("click", "#saveNewStructureTypeBtn", function () {
      let name = $("#newStructureTypeName").val().trim();
      let prefix = $("#newStructurePrefixType").val() || "Bed-";
      if (!name) {
        alertmessage("warning", "Please enter a structure type name.");
        $("#newStructureTypeName").focus();
        return;
      }
      let optVal = name.replace(/\s+/g, '');
      let existing = $("#StructureType option").filter(function() {
        return $(this).val().toLowerCase() === optVal.toLowerCase() || $(this).text().toLowerCase() === name.toLowerCase();
      });
      if (existing.length > 0) {
        $("#StructureType").val(existing.val()).data("prev", existing.val());
      } else {
        let newOpt = `<option value="${optVal}" data-prefix="${prefix}" selected>${name}</option>`;
        if ($("#StructureType option[value='other']").length > 0) {
          $("#StructureType option[value='other']").before(newOpt);
        } else {
          $("#StructureType").append(newOpt);
        }
        $("#StructureType").val(optVal).data("prev", optVal);
      }
      renameAllBeds();
      $("#newStructureTypeName").val('');
      $("#inlineStructurePanel").slideUp(200);
      alertmessage("success", `Structure type "${name}" added.`);
    });

    /* ── INITIAL SETUP ── */
    renameAllBeds();
    fetchLocations();
    loadPolyhouseDataList('all', 'all');

  }); // end ready

  // ================================================================
  //  HELPERS
  // ================================================================
  function getTypePrefix() {
    let $opt = $("#StructureType option:selected");
    let customPrefix = $opt.data("prefix");
    if (customPrefix) return customPrefix;
    let s = ($("#StructureType").val() || "").toLowerCase();
    if (s.includes("cooling") || s.includes("chamber") || s.includes("rack") || s.includes("germination")) {
      return "Rack-";
    }
    return "Bed-";
  }
  function generateBedName(index) { return getTypePrefix() + index; }

  function calculateTotalTrayCapacity() {
    $(".totalTrayType").each(function () {
      let trayid = $(this).data("trayid");
      let total = 0;
      $(`.traycapacity[data-trayid="${trayid}"]`).each(function () { total += parseInt($(this).val()) || 0; });
      $(this).text(total);
    });
  }

  function renameAllBeds() {
    $("#bedRackTable tbody tr").each(function (i) {
      $(this).find(".bedname").val(generateBedName(i + 1));
    });
  }

  /* ── RENDER BED ROW ── */
  function renderBedRow(bed = null, index = 0) {
    let bedName    = bed ? bed.bedname    : generateBedName(index + 1);
    let layerCount = bed ? bed.layercount : 1;
    let trayColumns = "";
    let colIndex = 0;
    activeTrays.forEach(tray => {
      colIndex++;
      let trayObj = (bed && bed.traycapacity && bed.traycapacity[tray.tray_id]) ? bed.traycapacity[tray.tray_id] : null;
      let cap = trayObj ? trayObj.capacity : 0;
      if (cap === 0 && bed && bed.tray_capacities && bed.tray_capacities[tray.tray_id]) cap = bed.tray_capacities[tray.tray_id];
      if (cap === 0 && bed && bed.traycapacity && typeof bed.traycapacity === "number") cap = bed.traycapacity;

      trayColumns += `
        <td data-label="${tray.tray_name} (${tray.cavity_count} Holes)">
          <input type="number" class="form-control form-control-sm traycapacity"
                 name="traycapacity_${tray.tray_id}[]"
                 data-trayid="${tray.tray_id}"
                 value="${cap}" min="0" style="max-width:90px;border-radius:6px;">
        </td>`;
    });

    return `
      <tr>
        <td data-label="Bed / Rack Name">
          <input type="text" class="form-control form-control-sm bedname" name="bedname[]" value="${bedName}" style="border-radius:6px;min-width:100px;">
        </td>
        <td data-label="Layers">
          <input type="number" class="form-control form-control-sm layercount" name="layercount[]" value="${layerCount}" min="1" style="max-width:80px;border-radius:6px;">
        </td>
        ${trayColumns}
        <td data-label="Remove" class="text-center">
          <button type="button" class="btn btn-sm btn-danger btn-icon-sm removeBedRow" title="Remove">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      </tr>`;
  }

  // ================================================================
  //  OPEN POLYHOUSE MODAL (NEW)
  // ================================================================
  $(document).on("click", ".openpolihousemodal", function () {
    $("#polyhouseid").val("");
    $("#polyhouseModalTitle").text("Add New Polyhouse");
    $("#polyhouseForm")[0].reset();
    $("#bedRackTable tbody").empty();
    $(".addBedRow").trigger("click");
    $("#StructureType").val("PolyHouse").data("prev", "PolyHouse");
    $(".quick-add-location-panel").hide();
    $("#polyhouseModal").modal("show");
    fetchLocations();
  });

  // ================================================================
  //  OPEN POLYHOUSE MODAL (EDIT)
  // ================================================================
  $(document).on("click", ".editpolyhousebtn", function () {
    let polyhouseid    = $(this).data("polyhouseid");
    let card           = $(this).closest(".polyhouse-strip-card");
    let beds           = card.data("beds");
    let locationid     = card.data("locationid");
    let polyhousename  = card.data("polyhousename");
    let sType          = card.data("structuretype");
    let rawType        = card.data("type") || sType || "PolyHouse";

    $("#polyhouseid").val(polyhouseid);
    $("#polyhouseModalTitle").text("Edit Polyhouse");
    $("#polyhouse_name").val(polyhousename);

    // Match or create option in StructureType dropdown
    let matched = $("#StructureType option").filter(function() {
      return $(this).val().toLowerCase() === rawType.toLowerCase() || $(this).text().toLowerCase() === rawType.toLowerCase();
    });
    if (matched.length > 0) {
      $("#StructureType").val(matched.val()).data("prev", matched.val());
    } else {
      let isRack = (sType === "Rack" || rawType.toLowerCase().includes("rack") || rawType.toLowerCase().includes("chamber"));
      let pfx = isRack ? "Rack-" : "Bed-";
      if ($("#StructureType option[value='other']").length > 0) {
        $("#StructureType option[value='other']").before(`<option value="${rawType}" data-prefix="${pfx}" selected>${rawType}</option>`);
      } else {
        $("#StructureType").append(`<option value="${rawType}" data-prefix="${pfx}" selected>${rawType}</option>`);
      }
      $("#StructureType").val(rawType).data("prev", rawType);
    }
    $(".quick-add-location-panel").hide();

    $("#bedRackTable tbody").empty();
    if (beds && beds.length > 0) {
      beds.forEach((bed, index) => { $("#bedRackTable tbody").append(renderBedRow(bed, index)); });
    }
    calculateTotalTrayCapacity();

    ProductionLocationMastercallback("all", "all", function (res) {
      let locationlist = '<option value="">-- Select Location --</option>';
      if (res.length > 0) {
        for (let i = 0; i < res.length; i++) {
          locationlist += `<option value="${res[i].LocationId}">${res[i].LocationName}</option>`;
        }
      }
      $("#polyhouselocationid").html(locationlist).val(locationid);
    });

    $("#polyhouseModal").modal("show");
  });

  // ================================================================
  //  TOGGLE INLINE LOCATION PANEL
  // ================================================================
  $(document).on("click", ".toggleNewLocPanel", function () {
    let target = $(this).data("target");
    $(target).slideToggle(200);
  });

  // ================================================================
  //  SAVE NEW LOCATION (inline quick-add inside polyhouse modal)
  // ================================================================
  $(document).on("click", ".saveNewLocation", function () {
    let btn       = $(this);
    let nameId    = btn.data("nameid");
    let shortId   = btn.data("shortid");
    let addrId    = btn.data("addrid");
    let targetSel = btn.data("target");
    let panelId   = btn.data("panel");

    let locName    = $(nameId).val().trim();
    let locShort   = $(shortId).val().trim();
    let locAddr    = $(addrId).val().trim();

    if (!locName) {
      alertmessage("warning", "Location name is required.");
      $(nameId).focus();
      return;
    }

    btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');
    saveLocationAjax(locName, locShort, locAddr, "", function(res) {
      btn.prop("disabled", false).html('<i class="fa fa-save me-1"></i> Save Location');
      if (res.status === "success") {
        alertmessage("success", res.message);
        // Add to the target dropdown and auto-select
        $(targetSel).append(`<option value="${res.locationId}" selected>${res.LocationName}</option>`);
        $(targetSel).val(res.locationId);
        // Also reload filter dropdown
        refreshLocationFilter();
        // Clear & close panel
        $(nameId).val(''); $(shortId).val(''); $(addrId).val('');
        $(panelId).slideUp(200);
      } else {
        alertmessage("error", res.message || "Failed to save location.");
      }
    });
  });

  // ================================================================
  //  OPEN MOVEMENT LOCATION MODAL
  // ================================================================
  $(document).on("click", ".openLocationModal", function () {
    $("#locationMasterForm")[0].reset();
    $("#locationMasterModal").modal("show");
    loadExistingLocations();
  });

  /* Refresh list inside location modal */
  $(document).on("click", "#refreshLocationListBtn", function () { loadExistingLocations(); });

  /* Save from location modal form */
  $("#locationMasterForm").on("submit", function (e) {
    e.preventDefault();
    let locName  = $("#loc_locationname").val().trim();
    let locShort = $("#loc_shortname").val().trim();
    let locAddr  = $("#loc_address").val().trim();
    let locComm  = $("#loc_comments").val().trim();

    if (!locName) {
      alertmessage("warning", "Location name is required.");
      $("#loc_locationname").focus();
      return;
    }

    let btn = $("#saveLocationBtn");
    btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

    saveLocationAjax(locName, locShort, locAddr, locComm, function(res) {
      btn.prop("disabled", false).html('<i class="fa fa-save me-1"></i> Save Location');
      if (res.status === "success") {
        alertmessage("success", res.message);
        $("#locationMasterForm")[0].reset();
        loadExistingLocations();
        refreshLocationFilter();
        // Show auto-refresh banner on main list
        showRefreshBanner(res.message);
      } else {
        alertmessage("error", res.message || "Failed to save location.");
      }
    });
  });

  /* Load existing locations list inside modal */
  function loadExistingLocations() {
    let $list = $("#existingLocationsList");
    $list.html('<div class="text-center py-3 text-muted"><i class="fa fa-spinner fa-spin me-1"></i> Loading...</div>');
    ProductionLocationMastercallback("all", "all", function (res) {
      if (!res || res.length === 0) {
        $list.html('<div class="text-center text-muted py-3"><i class="fa fa-info-circle me-1"></i> No locations found.</div>');
        return;
      }
      let html = '';
      res.forEach(function(loc) {
        html += `
          <div class="d-flex align-items-center gap-2 p-2 mb-1 rounded-2 border" style="background:#fafafa;">
            <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-25" style="width:32px;height:32px;flex-shrink:0;">
              <i class="fa fa-map-marker-alt text-warning" style="font-size:0.85rem;"></i>
            </div>
            <div style="flex:1;min-width:0;">
              <div class="fw-semibold text-dark text-truncate" style="font-size:0.85rem;">${loc.LocationName}</div>
              ${loc.LocationAddress ? `<div class="text-muted text-truncate" style="font-size:0.75rem;">${loc.LocationAddress}</div>` : ''}
            </div>
            ${loc.shortname ? `<span class="badge bg-light text-secondary border" style="font-size:0.72rem;">${loc.shortname}</span>` : ''}
          </div>`;
      });
      $list.html(html);
    });
  }

  // ================================================================
  //  SHARED: SAVE LOCATION AJAX
  // ================================================================
  function saveLocationAjax(locName, locShort, locAddr, locComm, callback) {
    let routurl      = $("body").data("routurl");
    let companyid    = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid    = $("body").data("projectid");

    let fd = new FormData();
    fd.append("companyid",       companyid);
    fd.append("subcompanyid",    subcompanyid);
    fd.append("projectid",       projectid);
    fd.append("locationname",    locName);
    fd.append("shortname",       locShort);
    fd.append("locationaddress", locAddr);
    fd.append("comments",        locComm);

    $.ajax({
      url: routurl + "krf-orderandsales/modal/polihouse_modal.php?action=saveMovementLocation",
      type: "POST",
      data: fd,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function(res) { callback(res); },
      error: function(xhr, s, err) { callback({ status: "error", message: "Server Error: " + err }); }
    });
  }

  // ================================================================
  //  FETCH LOCATIONS (populates dropdowns)
  // ================================================================
  function fetchLocations() {
    ProductionLocationMastercallback("all", "all", function (res) {
      let filterlist   = '<option value="all">All Locations</option>';
      let locationlist = '<option value="">-- Select Location --</option>';
      if (res.length > 0) {
        for (let i = 0; i < res.length; i++) {
          let opt = `<option value="${res[i].LocationId}">${res[i].LocationName}</option>`;
          filterlist   += opt;
          locationlist += opt;
        }
      }
      $("#polyhouselocationid").html(locationlist);
      if ($("#locationFilter option").length <= 1) {
        $("#locationFilter").html(filterlist).val("all");
      }
    });
  }

  function refreshLocationFilter() {
    ProductionLocationMastercallback("all", "all", function (res) {
      let filterlist = '<option value="all">All Locations</option>';
      if (res.length > 0) {
        for (let i = 0; i < res.length; i++) {
          filterlist += `<option value="${res[i].LocationId}">${res[i].LocationName}</option>`;
        }
      }
      let curVal = $("#locationFilter").val();
      $("#locationFilter").html(filterlist).val(curVal || "all");
    });
  }

  // ================================================================
  //  SAVE POLYHOUSE FORM
  // ================================================================
  $("#polyhouseForm").on("submit", function (e) {
    e.preventDefault();
    const form     = this;
    const formData = new FormData(form);
    let bedDetails = [];
    let hasError   = false;

    $("#bedRackTable tbody tr").each(function () {
      let bedname    = $(this).find(".bedname").val().trim();
      let layercount = parseInt($(this).find(".layercount").val()) || 0;
      let traycapacity = {};

      $(this).find(".traycapacity").each(function () {
        let tid  = $(this).data("trayid");
        let cap  = parseInt($(this).val()) || 0;
        let tName = activeTrays.find(t => t.tray_id == tid)?.tray_name || tid;
        traycapacity[tid] = { capacity: cap, tray_name: tName, tray_id: tid };
      });

      if (bedname === "") {
        alertmessage("warning", "Bed / Rack name cannot be empty.");
        $(this).find(".bedname").focus();
        hasError = true;
        return false;
      }
      bedDetails.push({ bedname, layercount, traycapacity });
    });
    if (hasError) return;

    formData.append("bed_details", JSON.stringify(bedDetails));
    let btn = $("#polyhouseSaveBtn");
    $.ajax({
      url: "modal/polihouse_modal.php?action=saveupdatePolyhouseDetailsformasters",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      dataType: "json",
      beforeSend: function () { btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...'); },
      success: function (res) {
        btn.prop("disabled", false).html('<i class="fa fa-save me-1"></i> Save Polyhouse');
        if (res.status === "success") {
          alertmessage("success", res.message);
          form.reset();
          $("#bedRackTable tbody").html("");
          $("#polyhouseModal").modal("hide");
          showRefreshBanner(res.message);
          if (typeof loadPolyhouseList === "function") { loadPolyhouseList(); }
        } else {
          alertmessage("warning", res.message || "Unable to save data.");
        }
      },
      error: function (xhr, status, error) {
        btn.prop("disabled", false).html('<i class="fa fa-save me-1"></i> Save Polyhouse');
        alertmessage("error", "Server Error: " + error);
      }
    });
  });

  /* Reset handler */
  $("#polyhouseForm").on("reset", function () {
    $("#polyhouseid").val("");
    $("#polyhouseModalTitle").text("Add New Polyhouse");
  });

  // ================================================================
  //  POST-SAVE REFRESH BANNER
  // ================================================================
  function showRefreshBanner(msg) {
    $("#pmRefreshMsg").text(msg || "Saved successfully.");
    $("#pmRefreshBanner").css("display", "flex");
  }

  $(document).on("click", "#pmBannerRefreshBtn", function () {
    loadPolyhouseList();
    $("#pmRefreshBanner").hide();
  });

  // ================================================================
  //  LOAD POLYHOUSE LIST (with direct AJAX and complete error handling)
  // ================================================================
  const defaultPrefix = "<?php echo $prefix; ?>";

  function loadPolyhouseDataList(shortby, shortbyvalue) {
    shortby      = shortby      || 'all';
    shortbyvalue = shortbyvalue || 'all';

    var routurl      = $("body").data("routurl") || defaultPrefix || "../";
    if (routurl && !routurl.endsWith('/')) routurl += '/';

    var companyid    = $("body").data("companyid")    || "";
    var subcompanyid = $("body").data("subcompanyid") || "";
    var projectid    = $("body").data("projectid")    || "<?php echo $projectid; ?>" || "";

    let listContainer = $("#polyhouseListContainer");
    listContainer.html(`
      <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
        <p class="text-muted mt-2 small">Loading polyhouse details...</p>
      </div>
    `);

    var fd = new FormData();
    fd.append("companyid",    companyid);
    fd.append("subcompanyid", subcompanyid);
    fd.append("projectid",    projectid);
    fd.append("shortby",      shortby);
    fd.append("shortbyvalue", shortbyvalue);

    $.ajax({
      type:        "POST",
      url:         routurl + "krf-orderandsales/modal/polihouse_modal.php?action=GetPolihouseDetails",
      data:        fd,
      contentType: false,
      processData: false,
      cache:       false,
      dataType:    "json",
      success: function(res) {
        _renderPolyhouseList(res);
      },
      error: function(xhr, status, err) {
        listContainer.html(`
          <div class="alert alert-danger m-3 rounded-3 shadow-sm">
            <i class="fa fa-exclamation-triangle me-2"></i>
            <strong>Unable to load polyhouse data.</strong>
            <div class="mt-1 small text-muted">
              Status: ${status} | Error: ${err}<br>
              Response: ${xhr.responseText ? xhr.responseText.substring(0, 300) : '(empty)'}
            </div>
            <button class="btn btn-sm btn-outline-danger mt-2 fw-semibold" onclick="loadPolyhouseDataList()">
              <i class="fa fa-redo me-1"></i> Try Again
            </button>
          </div>`);
      }
    });
  }

  function _renderPolyhouseList(res) {
    let listContainer = $("#polyhouseListContainer");
    listContainer.empty();

    if (res && !Array.isArray(res) && res.data) res = res.data;

    let totalBeds = 0, totalPolyhouses = 0;
    let globalTrayTotals = {};
    activeTrays.forEach(t => { globalTrayTotals[t.tray_id] = 0; });

    if (!Array.isArray(res) || res.length === 0) {
      listContainer.append(`
        <div class="text-center py-5 border rounded-3 bg-light">
          <i class="fa fa-warehouse fa-2x text-muted mb-2"></i>
          <p class="text-muted mb-0">No polyhouse data found.</p>
        </div>`);
      $("#totalBedsCount").text(0);
      $("#totalPolyhousesCount").text(0);
      $("#globalTrayTotals").empty();
      return;
    }

    totalPolyhouses = res.length;

    $.each(res, function (index, row) {
      let beds = parseBeds(row.beddetails);
      totalBeds += beds.length;

      let trayTotals = {};
      activeTrays.forEach(t => { trayTotals[t.tray_id] = 0; });

      $.each(beds, function(bi, bed) {
        activeTrays.forEach(t => {
          let cap = 0;
          if (bed.traycapacity && bed.traycapacity[t.tray_id])                      cap = parseInt(bed.traycapacity[t.tray_id].capacity) || 0;
          else if (bed.tray_capacities && bed.tray_capacities[t.tray_id])           cap = parseInt(bed.tray_capacities[t.tray_id]) || 0;
          else if (bed.traycapacity && typeof bed.traycapacity === "number")        cap = bed.traycapacity;
          trayTotals[t.tray_id]       += cap;
          globalTrayTotals[t.tray_id] += cap;
        });
      });

      let traySummaryHTML = "";
      activeTrays.forEach(t => {
        let total = trayTotals[t.tray_id] || 0;
        if (total > 0) {
          traySummaryHTML += `
            <div class="text-center px-2 py-1 rounded-3" style="min-width:80px;background:#f0fdf4;border:1px solid #bbf7d0;">
              <span class="d-block fw-semibold" style="font-size:0.7rem;color:#166534;">${t.tray_name}</span>
              <strong style="font-size:0.95rem;color:#15803d;">${total}</strong>
            </div>`;
        }
      });

      listContainer.append(`
        <div class="polyhouse-strip-card card border-0 shadow-sm rounded-3 overflow-hidden"
             data-polyhouseid="${row.polyhouseid}"
             data-polyhousename="${row.polyhousename}"
             data-structuretype="${row.structure_type}"
             data-type="${row.type || ''}"
             data-locationid="${row.locationid}"
             data-beds='${JSON.stringify(beds).replace(/'/g, "&apos;")}'>
          <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">

            <!-- Left: Name & Type -->
            <div class="d-flex align-items-center gap-2 strip-left-info" style="min-width:220px;flex:1;">
              <div class="polyhouse-icon-avatar me-2">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                  <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
              </div>
              <div>
                <h6 class="mb-0 fw-bold text-dark text-capitalize" style="font-size:0.95rem;">${row.polyhousename}</h6>
                <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                  <span class="polyhouse-structure-badge">
                    <i class="fa fa-cubes me-1"></i>${row.type || row.structure_type}
                  </span>
                  ${row.physicallocation ? `<span class="text-muted ms-1" style="font-size:0.78rem;"><i class="fa fa-map-marker-alt text-danger me-1"></i>${row.physicallocation}</span>` : ''}
                </div>
              </div>
            </div>

            <!-- Middle: Bed count & Tray capacities -->
            <div class="d-flex flex-wrap gap-2 align-items-center">
              <div class="text-center px-3 py-1 rounded-3" style="min-width:72px;background:#f8fafc;border:1px solid #e2e8f0;">
                <span class="d-block text-muted text-uppercase fw-semibold" style="font-size:0.65rem;letter-spacing:.05em;">Beds</span>
                <strong class="text-dark" style="font-size:1rem;">${beds.length}</strong>
              </div>
              ${traySummaryHTML}
            </div>

            <!-- Right: Actions -->
            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-sm btn-outline-primary rounded-pill px-3 editpolyhousebtn fw-semibold" data-polyhouseid="${row.polyhouseid}">
                <i class="fa fa-edit me-1"></i>Edit
              </button>
            </div>
          </div>
        </div>`);
    });

    $("#totalBedsCount").text(totalBeds);
    $("#totalPolyhousesCount").text(totalPolyhouses);
    $("#totalBeds").text(totalBeds);

    let globalTrayHTML = "";
    activeTrays.forEach(t => {
      let total = globalTrayTotals[t.tray_id] || 0;
      if (total > 0) {
        globalTrayHTML += `
          <div class="badge bg-light text-dark border p-2 px-3 rounded-pill">
            <span class="text-muted" style="font-size:0.75rem;">${t.tray_name} Total:</span>
            <strong class="text-success ms-1">${total}</strong>
          </div>`;
      }
    });
    $("#globalTrayTotals").html(globalTrayHTML);
  }

  // Location filter
  $(document).on("change", "#locationFilter", function () {
    let v = $(this).val();
    loadPolyhouseDataList(v === 'all' ? 'all' : 'locationid', v === 'all' ? 'all' : v);
  });

  // Manual refresh
  $(document).on("click", "#refreshBtn", function () {
    let v = $("#locationFilter").val() || 'all';
    loadPolyhouseDataList(v === 'all' ? 'all' : 'locationid', v === 'all' ? 'all' : v);
    $("#pmRefreshBanner").hide();
  });

  // Global list reload (called from save callbacks)
  window.loadPolyhouseList = function() {
    let v = $("#locationFilter").val() || 'all';
    loadPolyhouseDataList(v === 'all' ? 'all' : 'locationid', v === 'all' ? 'all' : v);
    $("#pmRefreshBanner").hide();
  };

  function parseBeds(beddetails) {
    if (typeof beddetails === "string") { try { beddetails = JSON.parse(beddetails); } catch { beddetails = []; } }
    return Array.isArray(beddetails) ? beddetails : [];
  }

  /* Legacy modal handler */
  $(document).on("click", ".NewPolyhousemaster", function () {
    let routurl = $("body").data("routurl");
    let key     = $("body").data("key");
    $("#PolyhouseMasterContent").load(routurl + "krf-orderandsales/addNewPolihouse.php?k=" + key + "&dz=11");
    $("#PolyhouseMasterModal").modal("show");
  });
</script>
