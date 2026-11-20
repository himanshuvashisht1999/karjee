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
        $loginname = $lginfo[0]['loginname'] ?? 'MOBILE_SCANNER_OT';
    } else {
        $loginname = 'MOBILE_SCANNER_OT';
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
        <title>Mobile OT QR Scanner</title>
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

<link rel="stylesheet" href="assets/css/qr_scanner.css">

<style>
.ot-purpose-card {
    background: #ffffff;
    border: 1px solid #c2e5d3;
    border-radius: 12px;
    padding: 18px 22px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.ot-purpose-title {
    font-size: 15px;
    font-weight: 700;
    color: #1b7a48;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}
.ot-purpose-textarea {
    width: 100%;
    border: 1.5px solid #1b7a48;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    resize: vertical;
    min-height: 52px;
}
.ot-purpose-textarea:focus {
    border-color: #125431;
    box-shadow: 0 0 0 3px rgba(27, 122, 72, 0.15);
}
.ot-tags-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}
.ot-tag-btn {
    background: #e6f4ed;
    color: #1b7a48;
    border: 1.5px solid #b3dfc9;
    border-radius: 20px;
    padding: 5px 16px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}
.ot-tag-btn:hover {
    background: #cdecdb;
    color: #1b7a48;
}
.ot-tag-btn.active, .ot-tag-btn.selected {
    background: #1b7a48 !important;
    color: #ffffff !important;
    border-color: #125431 !important;
    box-shadow: 0 3px 8px rgba(27, 122, 72, 0.35);
}
.ot-add-btn {
    background: #ffffff;
    color: #1b7a48;
    border: 1.5px dashed #1b7a48;
    border-radius: 20px;
    padding: 5px 16px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}
.ot-add-btn:hover {
    background: #1b7a48;
    color: #ffffff;
    border-style: solid;
}
.ot-time-banner {
    background: #fff3cd;
    border: 2px solid #ffeeba;
    border-left: 6px solid #d97706;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.12);
}
.ot-time-banner-icon {
    font-size: 32px;
    color: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ot-time-banner-content {
    flex: 1;
}
.ot-time-banner-title {
    font-size: 16px;
    font-weight: 800;
    color: #92400e;
    margin: 0 0 4px 0;
}
.ot-time-banner-sub {
    font-size: 13px;
    color: #b45309;
    margin: 0;
    font-weight: 600;
}
.ot-time-banner-badge {
    background: #d97706;
    color: #ffffff;
    font-weight: 700;
    font-size: 13px;
    padding: 6px 14px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
</style>

<div class="content container-fluid">
    <!-- TOP BAR – Title + Clock + Location breadcrumb -->
    <div class="qrs-topbar">
        <div class="qrs-topbar-left">
            <div class="qrs-topbar-icon" style="background: linear-gradient(135deg, #1b7a48, #2db36b); color: #fff;">
                <i class="ti ti-clock-plus"></i>
            </div>
            <div>
                <h3 class="qrs-title">OT QR Scanner (ओ.टी. क्यूआर स्कैनर)</h3>
                <p class="qrs-subtitle">Scan labour ID cards to mark Overtime (OT) attendance in real-time.</p>
            </div>
        </div>
        <div class="qrs-topbar-right">
            <div class="qrs-clock-badge">
                <i class="ti ti-clock-hour-4"></i>
                <span id="live-clock">--</span>
            </div>
        </div>
    </div>

    <!-- OT TIME WINDOW WARNING BANNER (Shown dynamically on screen when outside 6:30 PM to 7 AM) -->
    <div id="ot-time-warning-banner" class="ot-time-banner" style="display: none;">
        <div class="ot-time-banner-icon">
            <i class="ti ti-alert-triangle-filled"></i>
        </div>
        <div class="ot-time-banner-content">
            <h4 class="ot-time-banner-title">⚠️ Warning: OT Allowed Between 6:30 PM To 7 AM only</h4>
            <p class="ot-time-banner-sub">ओ.टी. उपस्थिति दर्ज करने का समय केवल शाम 06:30 बजे से अगली सुबह 07:00 बजे तक ही है। वर्तमान समय में ओ.टी. स्कैनिंग की अनुमति नहीं है।</p>
        </div>
        <div class="ot-time-banner-badge">
            <i class="ti ti-clock"></i> 6:30 PM - 7:00 AM ONLY
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

    <!-- MAIN SCANNER CONTAINER -->
    <div id="qrs-scanner-section" style="display: none;">

        <!-- ── PURPOSE OF OT INPUT SECTION (Required before scanning) ── -->
        <div class="ot-purpose-card">
            <div class="ot-purpose-title">
                <i class="ti ti-notes" style="font-size: 20px;"></i>
                Purpose of OT / ओ.टी. का उद्देश्य <span style="color: #dc3545;">*</span>
            </div>
            <textarea id="ot-purpose-input" class="ot-purpose-textarea" rows="2" 
                      placeholder="स्कैन करने से पहले OT का उद्देश्य दर्ज करें (उदा. Extra Shift, Maintenance, Harvesting, Packing etc.)..."></textarea>
            
            <div style="margin-top: 6px; font-size: 12px; color: #1b7a48; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                <i class="ti ti-circle-check" style="font-size: 15px;"></i>
                यह उद्देश्य लगातार सभी लेबर स्कैन पर लागू रहेगा (Auto-applied for all labour scans in this session).
            </div>

            <div class="ot-tags-wrap" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 12px;">
                <span class="text-muted small align-self-center me-1" style="font-size: 12px; font-weight: 600; color: #1b7a48;">त्वरित चयन (Quick Select):</span>
                <div id="ot-tags-container" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                    <!-- Loaded dynamically from hrm_otpurpose_master database table -->
                </div>
            </div>
        </div>
        
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
                            <span>LIVE CAMERA (OT SCANNER)</span>
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
                            <span>MANUAL OT ENTRY</span>
                        </div>
                        <div class="qrs-mobile-input-body" style="padding: 24px;">
                            <div class="qrs-input-showcase">
                                <div class="qrs-large-icon" style="margin-bottom: 12px;">
                                    <i class="ti ti-keyboard" style="font-size: 36px;"></i>
                                </div>
                                <h4 class="qrs-input-title" style="font-size: 16px;">Enter Labour ID for OT</h4>
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
                                    <i class="ti ti-shield-check"></i> Verify & Mark OT
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
                    <span>OT SCAN RESULT</span>
                </div>
                <div id="scan-feedback-body" class="qrs-feedback-body">
                    <!-- Idle state -->
                    <div class="qrs-idle" style="min-height: auto;">
                        <div class="qrs-idle-qr">
                            <i class="ti ti-clock-plus" style="font-size:48px; color: var(--g); margin-bottom: 12px; display: inline-block;"></i>
                        </div>
                        <h5 class="qrs-idle-title" style="color: var(--g);">Ready for OT Attendance</h5>
                        <p class="qrs-idle-desc" style="max-width: 100%; font-size:12px; margin-top:4px;">OT उपस्थिति दर्ज करने के लिए ऊपर OT उद्देश्य दर्ज करें और ID स्कैन/टाइप करें</p>
                    </div>
                </div>
            </div>

            <!-- ── COL 3: Stats + History ──────────────────── -->
            <div class="qrs-card qrs-stats-card">
                <div class="qrs-card-header">
                    <i class="ti ti-chart-bar" style="font-size:16px;"></i>
                    <span>OT SESSION STATS</span>
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
                <div class="qrs-section-label">Recent OT Scans</div>

                <!-- History list -->
                <div id="qrs-history-list" class="qrs-history-list">
                    <div class="qrs-history-empty">
                        <i class="ti ti-clock-off"></i>
                        <span>No OT scans yet this session</span>
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

<!-- OT QR Scanner JS -->
<script src="assets/js/qr_scanner_ot.js" defer></script>

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
