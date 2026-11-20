<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(isset($_GET['vw']) && $_GET['vw'] == 1) {
    include("../connection.php");
    include("functions/admin_functionmaster.php");
    include("functions/comanphp_functions.php");
    include("functions/hrm_loginFunctions.php");

    $key = $_GET['k'] ?? '';
    $key_decoded = base64url_decode($key);
    $key_parts = explode("|", $key_decoded);
    
    // Validate key
    $checkkey = base64url_decode($key_parts[count($key_parts) - 1]);
    $actkey = "";
    for ($i = 0; $i < count($key_parts) - 1; $i++) {
        $actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
    }
    if ($actkey != $checkkey) {
        echo "<div class='col-md-12 text-center black'><h3>OOPS!! Invalid key.</h3></div>";
        die;
    }

    $loginid = $key_parts[0];
    $loginroleid = $key_parts[1];
    $companyid = $key_parts[2];
    $subcompanyid = $key_parts[3];
    $projectid = $key_parts[4];
    $logintype = $key_parts[5];

    $loginname = "";
    if ($loginid) {
        $lginfo = hrm_loginuserinfo($authconn, $loginroleid, "loginid", $loginid);
        $loginname = $lginfo[0]['loginname'] ?? 'MOBILE_SCANNER';
    } else {
        $loginname = 'MOBILE_SCANNER';
    }

    $prjinfo = hrm_projectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
    $projectname = count($prjinfo) > 0 ? $prjinfo[0]['projectname'] : "";
    
    $protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $url = basename($_SERVER["PHP_SELF"]);
    $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
    if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "192.168.1.65") {
        $prefix = $rooturl . "/krf_amanaccounting/labourpayment/";
    } else {
        $prefix = $rooturl . "/labourpayment/";
    }
    $rooturl = $prefix;
    $mdid    = $_GET['mdid'] ?? "";
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mobile QR Scanner</title>
        <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
        <link rel="preconnect" href="https://unpkg.com" crossorigin>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link class="qrs-core-css" rel="stylesheet" href="assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css">
        <script src="assets/js/jquery-3.7.1.min.js" defer></script>
    </head>
    <body class="qrs-vw-mobile" data-key="<?php echo $key; ?>" data-routurl="<?php echo $rooturl; ?>" data-companyid="<?php echo $companyid; ?>"
        data-subcompanyid="<?php echo $subcompanyid; ?>" data-projectid="<?php echo $projectid; ?>"
        data-projectname="<?php echo $projectname; ?>" data-loginname="<?php echo $loginname; ?>"
        data-loginid="<?php echo $loginid; ?>" data-loginroleid="<?php echo $loginroleid; ?>"
        data-mdid="<?php echo $mdid; ?>">
    <?php
} else {
    require('header.php');
}
?>

<link rel="stylesheet" href="assets/css/custom_labour.css">
<link rel="stylesheet" href="assets/css/qr_scanner.css">

<div class="content container-fluid">
    <!-- TOP BAR – Title + Clock + Location breadcrumb -->
    <div class="qrs-topbar">
        <div class="qrs-topbar-left">
            <div class="qrs-topbar-icon"><i class="ti ti-qrcode"></i></div>
            <div>
                <h3 class="qrs-title">QR Scanner</h3>
                <p class="qrs-subtitle">Scan labour ID cards to verify presence and identity in real-time.</p>
            </div>
        </div>
        <div class="qrs-topbar-right">
            <button id="quicklabourregistration" class="btn shadow-sm" data-bs-toggle="modal"
                data-bs-target="#QuickLabourRegistrationModal">
                <i class="ti ti-user-plus"></i> <span>Quick Registration</span>
            </button>
            <div class="qrs-clock-badge">
                <i class="ti ti-clock-hour-4"></i>
                <span id="live-clock">--</span>
            </div>
        </div>
    </div>

    <!-- FILTER BAR – Location + Polyhouse selectors -->
    <div class="qrs-filter-bar">
        <!-- Gate / Location -->
        <div class="qrs-filter-group">
            <label class="qrs-filter-label">
                <i class="ti ti-map-pin"></i> Location — Main Gate
            </label>
            <div class="qrs-select-wrap">
                <i class="ti ti-building-estate"></i>
                <select id="qr-gate-location">
                    <option value="">Loading locations…</option>
                </select>
                <i class="ti ti-chevron-down qrs-chevron"></i>
            </div>
        </div>

        <!-- Polyhouse (populated after location is chosen) -->
        <div class="qrs-filter-group">
            <label class="qrs-filter-label">
                <i class="ti ti-map-2"></i> Attendance QR Location
            </label>
            <div class="qrs-select-wrap" id="polyhouse-wrap">
                <i class="ti ti-tree"></i>
                <select id="qr-polyhouse">
                    <option value="">— Select Location First —</option>
                </select>
                <i class="ti ti-chevron-down qrs-chevron"></i>
            </div>
        </div>

        <!-- Active location pill (shows selected loc + polyhouse) -->
        <div class="qrs-active-loc" id="active-loc-pill" style="display:none;">
            <i class="ti ti-map-pin-filled"></i>
            <span id="active-loc-text">—</span>
        </div>
    </div>

    <!-- Calculation Rules Alert -->
    <div class="alert alert-info d-flex align-items-center my-3 py-2 px-3 border-info shadow-sm" style="border-radius: 8px; background-color: #eff6ff; color: #1e40af; border: 1px solid #93c5fd;">
        <i class="ti ti-info-circle me-2 fs-18" style="color: #2563eb; font-size: 18px; flex-shrink: 0;"></i>
        <span class="fs-13" style="font-size: 13px;"><strong>Calculation Rules:</strong> Half-Day (HD): Working Hours &gt; 2 hrs &amp; &lt; 6 hrs (0.5 day) | Full-Day (P): Working Hours &ge; 6 hrs (1.0 day) | Absent (A): Working Hours &le; 2 hrs</span>
    </div>

    <!-- MAIN SCANNER CONTAINER -->
    <div id="qrs-scanner-section" style="display: none;">
        
        <!-- Segmented Tab Control -->
        <div class="qrs-modes-tabs">
            <button class="qrs-tab-btn" data-tab="on-device">
                <i class="ti ti-camera"></i> Scan With Mobile
            </button>
            <button class="qrs-tab-btn active" data-tab="mobile-scanner">
                <i class="ti ti-keyboard"></i> Scanner Device or Manual
            </button>
        </div>

        <div class="qrs-main-grid">

            <!-- ── COL 1: Toggleable Input Panel ────────────────── -->
            <div class="qrs-input-column-wrap">
                
                <!-- Panel 1: On-Device Camera -->
                <div id="qrs-panel-camera" class="qrs-input-panel">
                    <div class="qrs-card qrs-cam-card">
                        <div class="qrs-card-header">
                            <span class="qrs-live-dot"></span>
                            <span>LIVE CAMERA</span>
                        </div>

                        <!-- Viewport -->
                        <div class="qrs-cam-wrap" id="cam-viewport-wrap">
                            <div class="qrs-cam-placeholder" id="cam-placeholder">
                                <div class="qrs-cam-icon-circle">
                                    <i class="ti ti-camera-off"></i>
                                </div>
                                <p class="qrs-cam-hint-title">Camera Inactive</p>
                                <p class="qrs-cam-hint-sub">Press Start Camera below</p>
                            </div>
                            <div id="qr-video-reader"></div>
                            <!-- Corner brackets -->
                            <div class="qrs-corners">
                                <div class="qrs-corner qrs-tl"></div>
                                <div class="qrs-corner qrs-tr"></div>
                                <div class="qrs-corner qrs-bl"></div>
                                <div class="qrs-corner qrs-br"></div>
                            </div>
                            <!-- Laser -->
                            <div class="qrs-laser" id="qrs-laser"></div>
                        </div>

                        <!-- Toggle button -->
                        <button class="qrs-cam-btn start" id="btn-cam-toggle">
                            <i class="ti ti-video"></i><span>Start Camera</span>
                        </button>
                    </div>
                </div>

                <!-- Panel 2: Scanner Device or Manual (Active by default) -->
                <div id="qrs-panel-mobile-input" class="qrs-input-panel active">
                    <div class="qrs-card qrs-mobile-input-card">
                        <div class="qrs-card-header">
                            <i class="ti ti-keyboard" style="font-size:16px;"></i>
                            <span>MANUAL ENTRY</span>
                        </div>
                        <div class="qrs-mobile-input-body" style="padding: 24px;">
                            <div class="qrs-input-showcase">
                                <div class="qrs-large-icon" style="margin-bottom: 12px;">
                                    <i class="ti ti-keyboard" style="font-size: 36px;"></i>
                                </div>
                                <h4 class="qrs-input-title" style="font-size: 16px;">Enter Labour ID Manually</h4>
                            </div>

                            <div class="qrs-large-input-wrap" style="margin-top: 16px;">
                                <div class="qrs-large-input-box" style="padding: 6px 12px;">
                                    <i class="ti ti-edit" style="font-size: 16px;"></i>
                                    <input type="text" id="mobile-manual-id-input" 
                                           placeholder="Enter/Scan Labour ID..." 
                                           autocomplete="off" spellcheck="false"
                                           style="font-size: 13px;">
                                </div>
                                <button class="qrs-btn-verify-large" id="btn-verify-mobile-manual" style="padding: 10px 16px; font-size: 13px;">
                                    <i class="ti ti-shield-check"></i> Verify & Mark
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── COL 2: Scan Feedback ─────────────────────── -->
            <div class="qrs-card qrs-feedback-card">
                <div class="qrs-card-header">
                    <i class="ti ti-user-check" style="font-size:16px;"></i>
                    <span>SCAN RESULT</span>
                </div>
                <div id="scan-feedback-body" class="qrs-feedback-body">
                    <!-- Idle state (Manual ready message active by default since manual tab is default) -->
                    <div class="qrs-idle" style="min-height: auto;">
                        <div class="qrs-idle-qr">
                            <i class="ti ti-keyboard" style="font-size:48px; color: var(--g); margin-bottom: 12px; display: inline-block;"></i>
                        </div>
                        <h5 class="qrs-idle-title" style="color: var(--g);">Ready for Manual Entry</h5>
                        <p class="qrs-idle-desc" style="max-width: 100%; font-size:12px; margin-top:4px;">उपस्थिति दर्ज करने के लिए इनपुट बॉक्स में आईडी दर्ज करें या स्कैन करें</p>
                    </div>
                </div>
            </div>

            <!-- ── COL 3: Stats + History ──────────────────── -->
            <div class="qrs-card qrs-stats-card">
                <div class="qrs-card-header">
                    <i class="ti ti-chart-bar" style="font-size:16px;"></i>
                    <span>SESSION STATS</span>
                </div>

                <!-- Stats row -->
                <div class="qrs-stat-row">
                    <div class="qrs-stat qrs-stat-green">
                        <span class="qrs-stat-num" id="stat-granted">0</span>
                        <span class="qrs-stat-lbl">Granted</span>
                    </div>
                    <div class="qrs-stat qrs-stat-red">
                        <span class="qrs-stat-num" id="stat-denied">0</span>
                        <span class="qrs-stat-lbl">Denied</span>
                    </div>
                    <div class="qrs-stat qrs-stat-blue">
                        <span class="qrs-stat-num" id="stat-total">0</span>
                        <span class="qrs-stat-lbl">Total</span>
                    </div>
                </div>

                <!-- Divider -->
                <div class="qrs-section-label">Recent Scans</div>

                <!-- History list -->
                <div id="qrs-history-list" class="qrs-history-list">
                    <div class="qrs-history-empty">
                        <i class="ti ti-clock-off"></i>
                        <span>No scans yet this session</span>
                    </div>
                </div>

                <!-- Reset session -->
                <button class="qrs-btn-reset" id="btn-reset-session">
                    <i class="ti ti-refresh"></i> Reset Session
                </button>
            </div>

        </div><!-- /qrs-main-grid -->
    </div><!-- /qrs-scanner-section -->
</div><!-- /content -->
<?php
if(isset($_GET['vw']) && $_GET['vw'] == 1) {
    ?>
    <!-- Quick Labour Registration Modal for Mobile Standalone View -->
    <div class="modal fade" id="QuickLabourRegistrationModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-0 px-4 pt-4">
            <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
              <span class="bg-primary-subtle text-primary rounded-2 p-1 me-2"><i class="ti ti-user-plus fs-20"></i></span>
              Quick Labour Registration
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="QuickLabourRegistrationForm" novalidate>
            <div class="modal-body px-4 pb-4">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Working Location <span class="text-danger">*</span></label>
                  <select name="workingLocation" id="quick_workingLocation" class="form-select bg-light border-0" required>
                    <option value="">Choose Location</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Suggested ID <span class="text-danger">*</span></label>
                  <input type="text" inputmode="numeric" name="seriesid" id="quick_seriesid" class="form-control bg-light border-0" required placeholder="Suggested ID">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Labour Name <span class="text-danger">*</span></label>
                  <input type="text" name="labourName" id="quick_labourName" class="form-control bg-light border-0" required placeholder="Enter Full Name">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted">Contact No <span class="text-danger">*</span></label>
                  <input type="text" name="contactNo" id="quick_contactNo" class="form-control bg-light border-0" required placeholder="10-digit mobile number" maxlength="10">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted d-block">Gender <span class="text-danger">*</span></label>
                  <div class="d-flex gap-4 align-items-center mt-2">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="gender" id="quick_gender_male_mob" value="Male" checked required>
                      <label class="form-check-label fw-bold" for="quick_gender_male_mob"><i class="ti ti-man text-primary me-1"></i> Male</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="gender" id="quick_gender_female_mob" value="Female" required>
                      <label class="form-check-label fw-bold" for="quick_gender_female_mob"><i class="ti ti-woman text-danger me-1"></i> Female</label>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold text-muted d-block">Is Grafted Skill? <span class="text-danger">*</span></label>
                  <div class="d-flex gap-4 align-items-center mt-2">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="grafting_skill" id="quick_skill_yes_mob" value="yes" required>
                      <label class="form-check-label fw-bold" for="quick_skill_yes_mob">Yes, Skilled</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="grafting_skill" id="quick_skill_no_mob" value="no" checked>
                      <label class="form-check-label fw-bold" for="quick_skill_no_mob">No</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex gap-2 justify-content-end">
              <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Discard</button>
              <button type="submit" id="submitQuickRegistration" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="ti ti-check me-1"></i> Register
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/hrm.js"></script>
    <script src="assets/js/quick_labour.js?v=<?php echo time(); ?>"></script>
    </body>
    </html>
    <?php
} else {
    require('footer.php');
}
?>

<!-- Html5-QrCode library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" defer></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.all.min.js" defer></script>

<!-- QR Scanner JS -->
<script src="assets/js/qr_scanner.js" defer></script>

<!-- Live clock -->
<script>
(function tickClock() {
    var el = document.getElementById('live-clock');
    if (!el) return;
    var now  = new Date();
    var h    = String(now.getHours()).padStart(2, '0');
    var m    = String(now.getMinutes()).padStart(2, '0');
    var s    = String(now.getSeconds()).padStart(2, '0');
    var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    var mons = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    el.textContent = days[now.getDay()] + ' ' + now.getDate() + ' ' +
                     mons[now.getMonth()] + ' ' + now.getFullYear() +
                     '  ' + h + ':' + m + ':' + s;
    setTimeout(tickClock, 1000);
})();
</script>
