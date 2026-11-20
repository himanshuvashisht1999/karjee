<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_GET['vw']) && $_GET['vw'] == 1) {
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
        $loginname = $lginfo[0]['loginname'] ?? 'MOBILE_VERIFIER';
    } else {
        $loginname = 'MOBILE_VERIFIER';
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
        <title>Physical Verification</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link class="qrs-core-css" rel="stylesheet" href="assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css">
        <script src="assets/js/jquery-3.7.1.min.js"></script>
        <script src="assets/js/sweetalert2@11.js"></script>
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

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* --- Design Variables --- */
:root {
    --ver-green-deep: #105c35;
    --ver-green-light: #28a745;
    --ver-green-bg: #e6f4ed;
    --ver-blue-deep: #1e3a8a;
    --ver-border: #e2e8f0;
    --ver-gray-light: #f8fafc;
    --ver-text-muted: #64748b;
    --ver-shadow-sm: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.02);
    --ver-shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.02);
}

.ver-container {
    font-family: 'Inter', sans-serif;
    background: #f8fafc;
    min-height: 100vh;
    padding: 24px;
}

.bg-soft-primary {
    background-color: #eff6ff !important;
}
.bg-soft-success {
    background-color: #f0fdf4 !important;
}
.bg-soft-warning {
    background-color: #fffbeb !important;
}
.summary-card {
    transition: all 0.25s ease;
    border-width: 1px !important;
}
.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.summary-card.active-card {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    border-width: 2px !important;
}
.summary-card.active-card.border-primary-active {
    border-color: #3b82f6 !important;
}
.summary-card.active-card.border-success-active {
    border-color: #22c55e !important;
}
.summary-card.active-card.border-warning-active {
    border-color: #eab308 !important;
}

/* --- Topbar Branding Banner --- */
.ver-banner {
    background: linear-gradient(135deg, var(--ver-green-deep) 0%, #1e3d2f 100%);
    color: #fff;
    padding: 20px 24px;
    border-radius: 12px;
    margin-bottom: 24px;
    box-shadow: var(--ver-shadow-md);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}
.ver-banner-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.ver-banner-icon {
    width: 44px;
    height: 44px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #4ade80;
}
.ver-banner h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 18px;
    font-weight: 700;
    margin: 0;
    color: #ffffff !important;
}
.ver-banner p {
    font-size: 11px;
    opacity: 0.85;
    margin: 0;
}
.ver-clock-badge {
    background: rgba(74, 222, 128, 0.1);
    border: 1px solid rgba(74, 222, 128, 0.2);
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    color: #4ade80;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* --- Filter Container Card --- */
.ver-filter-card {
    background: #ffffff;
    border: 1px solid var(--ver-border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: var(--ver-shadow-sm);
}
.ver-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    align-items: flex-end;
}
.ver-filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.ver-filter-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--ver-text-muted);
    display: flex;
    align-items: center;
    gap: 6px;
}
.ver-select-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.ver-select-wrap i.ti-left {
    position: absolute;
    left: 12px;
    color: var(--ver-text-muted);
    font-size: 16px;
    pointer-events: none;
}
.ver-select-wrap select, .ver-input-date {
    width: 100%;
    padding: 10px 12px 10px 38px;
    border: 1.5px solid var(--ver-border);
    border-radius: 8px;
    font-size: 13px;
    background: var(--ver-gray-light);
    color: #1e293b;
    appearance: none;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 500;
}
.ver-select-wrap select:focus, .ver-input-date:focus {
    outline: none;
    border-color: var(--ver-green-light);
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
}
.ver-select-wrap i.ti-chevron {
    position: absolute;
    right: 12px;
    color: var(--ver-text-muted);
    font-size: 14px;
    pointer-events: none;
}

/* --- Table Card View --- */
.ver-table-card {
    background: #ffffff;
    border: 1px solid var(--ver-border);
    border-radius: 12px;
    box-shadow: var(--ver-shadow-sm);
    overflow: hidden;
}
.ver-table-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--ver-border);
    background: var(--ver-gray-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.ver-table-header h4 {
    font-family: 'Outfit', sans-serif;
    font-size: 15px;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}

/* --- Table Styles --- */
.ver-table-responsive {
    overflow-x: auto;
    width: 100%;
}
.ver-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}
.ver-table th {
    background: var(--ver-gray-light);
    padding: 12px 18px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--ver-text-muted);
    border-bottom: 1px solid var(--ver-border);
    text-align: left;
}
.ver-table td {
    padding: 14px 18px;
    font-size: 13px;
    color: #334155;
    border-bottom: 1px solid var(--ver-border);
    vertical-align: middle;
}
.ver-table tbody tr:hover {
    background: #fdfdfd;
}

/* --- Avatar & Badges --- */
.ver-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    background: #e2e8f0;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
}
.ver-no-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    color: var(--ver-text-muted);
}
.ver-labour-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 2px;
}
.ver-labour-id {
    font-size: 11px;
    color: var(--ver-text-muted);
    font-weight: 500;
}
.ver-time-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 500;
}
.ver-time-in { background: #e0f2fe; color: #0369a1; }
.ver-time-out { background: #fee2e2; color: #b91c1c; }

/* --- Action Button Verification --- */
.ver-status-btn {
    border: none;
    border-radius: 20px;
    padding: 6px 16px;
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.btn-unverified {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}
.btn-unverified:hover {
    background: #28a745;
    color: #fff;
    border-color: #28a745;
    box-shadow: 0 2px 6px rgba(40,167,69,0.25);
}
.btn-verified {
    background: var(--ver-green-bg);
    color: var(--ver-green-deep);
    border: 1px solid rgba(40,167,69,0.15);
    cursor: default;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.rotate {
    animation: spin 1s linear infinite;
    display: inline-block;
}

/* --- Summary Report Bar Styles --- */
.work-loc-summary-wrap {
    background: #ffffff;
    border: 1px solid var(--ver-border);
    border-radius: 12px;
    padding: 12px 18px;
    margin-bottom: 20px;
    box-shadow: var(--ver-shadow-sm);
}
.av-summary-btn {
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 14px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #475569;
}
.av-summary-btn:hover {
    border-color: #3b82f6;
    color: #1d4ed8;
    background: #eff6ff;
}
.av-summary-btn.active {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 2px 6px rgba(37,99,235,0.25);
}
.av-summary-btn .badge-num {
    background: rgba(0,0,0,0.08);
    padding: 2px 7px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
}
.av-summary-btn.active .badge-num {
    background: rgba(255,255,255,0.25);
    color: #ffffff;
}
</style>

<div class="content container-fluid ver-container" id="verification-container"
     data-key="<?php echo isset($key) ? $key : ''; ?>"
     data-routurl="<?php echo isset($rooturl) ? $rooturl : ''; ?>"
     data-companyid="<?php echo isset($companyid) ? $companyid : ''; ?>"
     data-subcompanyid="<?php echo isset($subcompanyid) ? $subcompanyid : ''; ?>"
     data-projectid="<?php echo isset($projectid) ? $projectid : ''; ?>"
     data-loginname="<?php echo isset($loginname) ? $loginname : ''; ?>">

    <!-- Top Banner -->
    <div class="ver-banner">
        <div class="ver-banner-left">
            <div class="ver-banner-icon"><i class="ti ti-user-check"></i></div>
            <div>
                <h3>Physical Verification</h3>
                <p>Monitor, audit, and approve the physical presence of checked-in workers at locations.</p>
            </div>
        </div>
        <div class="ver-clock-badge">
            <i class="ti ti-clock"></i>
            <span id="live-clock">--:--:--</span>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="ver-filter-card">
        <div class="ver-filter-grid">
            
            <!-- Location Selector -->
            <div class="ver-filter-group">
                <label class="ver-filter-label"><i class="ti ti-map-pin"></i> Location — Main Gate</label>
                <div class="ver-select-wrap">
                    <i class="ti ti-building-estate ver-icon ti-left"></i>
                    <select id="qr-gate-location">
                        <option value="">Loading locations…</option>
                    </select>
                    <i class="ti ti-chevron-down ver-icon ti-chevron"></i>
                </div>
            </div>

            <!-- Polyhouse Selector -->
            <div class="ver-filter-group">
                <label class="ver-filter-label"><i class="ti ti-map-2"></i> Attendance QR Location</label>
                <div class="ver-select-wrap">
                    <i class="ti ti-tree ver-icon ti-left"></i>
                    <select id="qr-polyhouse" disabled>
                        <option value="">— Select Location First —</option>
                    </select>
                    <i class="ti ti-chevron-down ver-icon ti-chevron"></i>
                </div>
            </div>

            <!-- Attendance Date -->
            <div class="ver-filter-group">
                <label class="ver-filter-label"><i class="ti ti-calendar"></i> Attendance Date</label>
                <div class="ver-select-wrap">
                    <i class="ti ti-calendar-event ver-icon ti-left"></i>
                    <input type="date" id="attendance-date" class="ver-input-date" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

        </div>
    </div>

    <!-- Summary Cards & Table Grid (Hidden until all 3 filter values are selected) -->
    <div id="verification-content-wrapper" style="display: none;">
        <!-- Summary Cards Grid -->
        <div class="row g-2 g-md-3 mb-4" id="verification-summary-cards">
            <div class="col-4">
                <div class="card summary-card bg-soft-primary active-card border-primary-active" onclick="filterVerificationList('all')" style="cursor: pointer; border-radius: 12px; border: 1px solid #bfdbfe;">
                    <div class="card-body p-2 p-md-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold mb-1" id="total-count-lbl" style="font-size: 24px !important; font-weight: 700; color: #1e293b; line-height: 1.2;">0</div>
                                <p class="mb-0 text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 0.5px;">Total Checked-In</p>
                            </div>
                            <div class="icon-circle bg-primary text-white d-flex align-items-center justify-content-center d-none d-sm-flex" style="width: 36px; height: 36px; border-radius: 50%;">
                                <i class="ti ti-users fs-16"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card summary-card bg-soft-success" onclick="filterVerificationList('verified')" style="cursor: pointer; border-radius: 12px; border: 1px solid #bbf7d0;">
                    <div class="card-body p-2 p-md-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold mb-1" id="verified-count-lbl" style="font-size: 24px !important; font-weight: 700; color: #1e293b; line-height: 1.2;">0</div>
                                <p class="mb-0 text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 0.5px;">Verified Presence</p>
                            </div>
                            <div class="icon-circle bg-success text-white d-flex align-items-center justify-content-center d-none d-sm-flex" style="width: 36px; height: 36px; border-radius: 50%;">
                                <i class="ti ti-circle-check fs-16"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card summary-card bg-soft-warning" onclick="filterVerificationList('pending')" style="cursor: pointer; border-radius: 12px; border: 1px solid #fef08a;">
                    <div class="card-body p-2 p-md-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold mb-1" id="pending-count-lbl" style="font-size: 24px !important; font-weight: 700; color: #1e293b; line-height: 1.2;">0</div>
                                <p class="mb-0 text-uppercase fw-bold text-muted" style="font-size: 10px; letter-spacing: 0.5px;">Un-Verified</p>
                            </div>
                            <div class="icon-circle bg-warning text-white d-flex align-items-center justify-content-center d-none d-sm-flex" style="width: 36px; height: 36px; border-radius: 50%;">
                                <i class="ti ti-clock-hour-4 fs-16"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Work Location Summary Report Bar (Shown ONLY for Total Checked-In card) -->
        <div id="work-location-summary-wrap" style="display:none;" class="work-loc-summary-wrap">
            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 font-12 fw-bold">
                        <i class="ti ti-report-analytics me-1"></i> Labour Location Wise Summary Report
                    </span>
                    <span class="text-muted font-12 fw-semibold" id="work-loc-summary-title">Work Location: All</span>
                </div>
                <span class="text-muted font-11" id="work-loc-summary-info"><i class="ti ti-info-circle me-1"></i>Click any badge to filter labours by home location below</span>
            </div>
            <div id="work-location-badges-container" class="d-flex flex-wrap gap-2 align-items-center pt-1">
                <!-- Dynamic location summary badges injected by JS -->
            </div>
        </div>

        <!-- Details Card Table -->
        <div class="ver-table-card">
            <div class="ver-table-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-0">Attendance Details for Verification</h4>
                    <span class="badge bg-secondary-subtle text-secondary mt-1 d-inline-block" id="total-badge">Total: 0</span>
                </div>
                <!-- Search Option from Grid -->
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 320px; min-width: 200px;">
                    <div class="input-group">
                        <span class="input-group-text bg-white border border-end-0 text-muted py-1.5 px-2">
                            <i class="ti ti-search fs-14"></i>
                        </span>
                        <input type="text" class="form-control border border-start-0 ps-0 py-1.5 fs-13" id="labourSearchInput" placeholder="Search labour name or ID..." onkeyup="filterLabourTable()">
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 px-3 py-2 fw-semibold text-white border-0" id="btn-export-csv" type="button" onclick="exportFilteredGrid()" style="border-radius: 20px;">
                        <i class="ti ti-download fs-14"></i> Export CSV
                    </button>
                    <button class="btn btn-success btn-sm d-flex align-items-center gap-1 px-3 py-2 fw-semibold text-white border-0" id="btn-fetch-sheet" type="button" style="border-radius: 20px;">
                        <i class="ti ti-table-share fs-14"></i> Fetch Google Sheet
                    </button>
                </div>
            </div>
            <div class="ver-table-responsive">
                <table class="ver-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 8%;">Photo</th>
                            <th style="width: 32%;">Labour Name & ID</th>
                            <th style="width: 25%;">Punch Log</th>
                            <th style="width: 15%;">Hours</th>
                            <th style="width: 15%;">Verification Status</th>
                        </tr>
                    </thead>
                    <tbody id="attendance-list-body">
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-database-off fs-24 d-block mb-2"></i>
                                Select a location, QR location, and date above to load checked-in workers.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script type="text/javascript">
$(document).ready(function() {
    var verLocMap = {};

    // Live clock update
    function startClock() {
        setInterval(function() {
            var now = new Date();
            var timeStr = now.toLocaleTimeString('en-US', { hour12: false });
            $('#live-clock').text(timeStr);
        }, 1000);
    }
    startClock();

    var $container = $('#verification-container');
    var baseurl = $container.data('routurl');
    var projectid = $container.data('projectid');
    var key = $container.data('key');
    var loginname = $container.data('loginname') || 'SYSTEM';

    // 1. Fetch Location list on page load
    function loadLocations() {
        var fd = new FormData();
        fd.append("projectid", projectid);
        fd.append("shortby", "all");
        fd.append("shortbyvalue", "all");

        $.ajax({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=labourworkingloactiondetails",
            data: fd,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(res) {
                var sel = $("#qr-gate-location");
                sel.empty().append('<option value="">— Select Location —</option>');
                if (res && res.length > 0) {
                    res.forEach(function(loc) {
                        sel.append('<option value="' + loc.LocationId + '">' + loc.LocationName + '</option>');
                        verLocMap[loc.LocationId] = loc.LocationName;
                    });
                }
            }
        });
    }
    loadLocations();

    // 2. Location select change listener
    $("#qr-gate-location").on('change', function() {
        var locId = $(this).val();
        var polySel = $("#qr-polyhouse");
        
        polySel.empty().append('<option value="">— Select Location First —</option>').prop('disabled', true);
        clearTable();

        if (!locId) return;

        polySel.empty().append('<option value="">Loading polyhouses…</option>');

        $.post(
            baseurl + "modal/hrm_laboursalarymodal.php?action=GetPolyhousesByLocation",
            { projectid: projectid, locationid: locId },
            function(raw) {
                var data = [];
                try { data = JSON.parse(raw); } catch (e) {}

                polySel.empty().prop('disabled', false);
                polySel.append('<option value="">— Select Attendance QR Location —</option>');
                polySel.append('<option value="main-gate">Main-Gate</option>');

                if (data && data.length > 0) {
                    data.forEach(function(p) {
                        polySel.append('<option value="' + p.polyhouseid + '">' + p.polyhousename + '</option>');
                    });
                }
            }
        );
    });

    // Active filter tracking variables
    window.currentActiveFilter = 'all';
    window.currentHomeLocFilter = 'all';
    window.currentAttendanceData = [];

    // Summary report helper for Home Location distribution
    function renderWorkLocationSummaryReport(data) {
        var selLocName = $('#qr-gate-location option:selected').text().trim() || 'All Locations';
        if (selLocName === '— Select Location —') selLocName = 'All Locations';
        $('#work-loc-summary-title').text('Work Location: ' + selLocName);

        var homeLocCounts = {};
        var totalCount = data.length;

        data.forEach(function(r) {
            var hLoc = r.labourlocationid || r.locationid || 'Unknown';
            homeLocCounts[hLoc] = (homeLocCounts[hLoc] || 0) + 1;
        });

        var badgesHtml = '';
        var allActive = (window.currentHomeLocFilter === 'all') ? 'active' : '';
        badgesHtml += '<button type="button" class="av-summary-btn ' + allActive + '" onclick="filterByHomeLoc(\'all\')">' +
            '<i class="ti ti-layout-grid"></i> All Home Locations <span class="badge-num">' + totalCount + '</span></button>';

        Object.keys(homeLocCounts).forEach(function(locId) {
            var locName = verLocMap[locId] || locId;
            var count = homeLocCounts[locId];
            var isSelected = (window.currentHomeLocFilter === locId);
            var activeClass = isSelected ? 'active' : '';
            
            badgesHtml += '<button type="button" class="av-summary-btn ' + activeClass + '" onclick="filterByHomeLoc(\'' + escapeHtml(locId) + '\')">' +
                '<i class="ti ti-map-pin"></i> ' + escapeHtml(locName) + ' <span class="badge-num">' + count + '</span></button>';
        });

        $('#work-location-badges-container').html(badgesHtml);
    }

    window.filterByHomeLoc = function(locId) {
        window.currentHomeLocFilter = locId;
        applyVerificationFilters();
        renderWorkLocationSummaryReport(window.currentAttendanceData || []);
    };

    // Filter event handler for summary cards
    window.filterVerificationList = function(filterType) {
        window.currentActiveFilter = filterType;
        window.currentHomeLocFilter = 'all';
        
        // Update active class on summary cards
        $('.summary-card').removeClass('active-card border-primary-active border-success-active border-warning-active');
        if (filterType === 'all') {
            $('.summary-card').eq(0).addClass('active-card border-primary-active');
            $('#work-location-summary-wrap').show();
        } else if (filterType === 'verified') {
            $('.summary-card').eq(1).addClass('active-card border-success-active');
            $('#work-location-summary-wrap').hide();
        } else if (filterType === 'pending') {
            $('.summary-card').eq(2).addClass('active-card border-warning-active');
            $('#work-location-summary-wrap').hide();
        }
        
        applyVerificationFilters();
    };

    // Keyboard search handler
    window.filterLabourTable = function() {
        applyVerificationFilters();
    };

    function applyVerificationFilters() {
        var data = window.currentAttendanceData || [];
        var filterType = window.currentActiveFilter || 'all';
        var homeLocFilter = window.currentHomeLocFilter || 'all';
        var searchVal = $('#labourSearchInput').val().toLowerCase().trim();

        // 1. Filter by Card selection
        var filteredData = data;
        if (filterType === 'verified') {
            filteredData = data.filter(function(r) { return r.approvalstatus === 'approved'; });
        } else if (filterType === 'pending') {
            filteredData = data.filter(function(r) { return r.approvalstatus !== 'approved'; });
        }

        // 2. Filter by Home Location sub-filter
        if (homeLocFilter !== 'all') {
            filteredData = filteredData.filter(function(r) {
                var hLoc = r.labourlocationid || r.locationid || 'Unknown';
                return hLoc === homeLocFilter;
            });
        }

        // 3. Filter by Search input
        if (searchVal) {
            filteredData = filteredData.filter(function(r) {
                var name = (r.FullName || '').toLowerCase();
                var id = (r.labourid || '').toLowerCase();
                return name.indexOf(searchVal) > -1 || id.indexOf(searchVal) > -1;
            });
        }

        var body = $('#attendance-list-body');
        body.empty();

        if (filteredData.length === 0) {
            body.html(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="ti ti-checkbox fs-24 d-block mb-2 text-warning"></i>
                        No workers found matching the active filters.
                    </td>
                </tr>
            `);
            return;
        }

        filteredData.forEach(function(row, idx) {
            var photoHtml = '<div class="ver-no-avatar">' + row.FullName.charAt(0).toUpperCase() + '</div>';

            var punchInHtml = row.checkintime ? 
                '<span class="ver-time-badge ver-time-in"><i class="ti ti-arrow-right-bar fs-12"></i> ' + row.checkintime.substring(11, 16) + '</span>' : 
                '<span class="text-muted small">—</span>';

            var punchOutHtml = row.checkouttime ? 
                '<span class="ver-time-badge ver-time-out"><i class="ti ti-arrow-left-bar fs-12"></i> ' + row.checkouttime.substring(11, 16) + '</span>' : 
                '<span class="text-muted small">—</span>';

            var hoursHtml = row.workinghours ? 
                '<span class="fw-semibold text-dark">' + row.workinghours + ' Hrs</span>' : 
                '<span class="text-muted small">—</span>';

            var verBtnHtml = '';
            if (row.approvalstatus === 'approved') {
                verBtnHtml = `
                    <button class="ver-status-btn btn-verified" disabled>
                        <i class="ti ti-circle-check-filled fs-14"></i> Verified by ${row.approvedby || 'System'}
                    </button>
                `;
            } else {
                verBtnHtml = `
                    <button class="ver-status-btn btn-unverified" data-id="${row.attendanceid}">
                        <i class="ti ti-discount-check fs-14"></i> Verify Present
                    </button>
                `;
            }

            var tr = $(`
                <tr>
                    <td class="fw-bold text-muted">${idx + 1}</td>
                    <td>${photoHtml}</td>
                    <td>
                        <div class="ver-labour-name">${row.FullName}</div>
                        <div class="ver-labour-id">${row.labourid}</div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            ${punchInHtml}
                            <i class="ti ti-arrow-right text-muted small"></i>
                            ${punchOutHtml}
                        </div>
                    </td>
                    <td>${hoursHtml}</td>
                    <td>${verBtnHtml}</td>
                </tr>
            `);

            body.append(tr);
        });
    }

    // 3. Clear Table Helper
    function clearTable() {
        $('#verification-content-wrapper').hide();
        $('#work-location-summary-wrap').hide();
        $('#total-badge').text('Total: 0').removeClass('bg-success-subtle text-success').addClass('bg-secondary-subtle text-secondary');
        $('#total-count-lbl').text('0');
        $('#verified-count-lbl').text('0');
        $('#pending-count-lbl').text('0');
        $('#labourSearchInput').val('');
        window.currentActiveFilter = 'all';
        window.currentHomeLocFilter = 'all';
        window.currentAttendanceData = [];
        $('.summary-card').removeClass('active-card border-primary-active border-success-active border-warning-active');
        $('.summary-card').eq(0).addClass('active-card border-primary-active');

        $('#attendance-list-body').html(`
            <tr>
                <td colspan="6" class="text-center text-muted py-5">
                    <i class="ti ti-database-off fs-24 d-block mb-2"></i>
                    Select a location, QR location, and date above to load checked-in workers.
                </td>
            </tr>
        `);
    }

    // 4. Trigger Load on change of any filter
    $(document).on('change', '#qr-gate-location, #qr-polyhouse, #attendance-date', function() {
        checkFiltersAndLoad();
    });

    function checkFiltersAndLoad() {
        var locId = $("#qr-gate-location").val();
        var polyId = $("#qr-polyhouse").val();
        var attDate = $("#attendance-date").val();
        if (!locId || !polyId || !attDate) {
            $('#verification-content-wrapper').hide();
            clearTable();
            return;
        }

        $('#verification-content-wrapper').show();

        // Reset search bar and filter card selector on fresh load
        $('#labourSearchInput').val('');
        window.currentActiveFilter = 'all';
        window.currentHomeLocFilter = 'all';
        $('.summary-card').removeClass('active-card border-primary-active border-success-active border-warning-active');
        $('.summary-card').eq(0).addClass('active-card border-primary-active');

        $('#attendance-list-body').html(`
            <tr>
                <td colspan="6" class="text-center text-muted py-5">
                    <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                    Loading attendance mark details…
                </td>
            </tr>
        `);

        $.post(
            baseurl + "modal/hrm_laboursalarymodal.php?action=GetPhysicalVerificationAttendance",
            {
                projectid: projectid,
                locationid: locId,
                polyhouseid: polyId,
                attendancedate: attDate
            },
            function(res) {
                var response = null;
                try {
                    response = (typeof res === 'object') ? res : JSON.parse(res);
                } catch (e) {
                    $('#attendance-list-body').html(`
                        <tr>
                            <td colspan="6" class="text-center text-danger py-5">
                                <i class="ti ti-alert-triangle fs-24 d-block mb-2"></i>
                                Error: Invalid response format from server.<br>
                                <small class="text-muted d-block mt-2" style="max-width: 600px; margin: 0 auto; word-break: break-all;">
                                    Raw Response: ${res}
                                </small>
                            </td>
                        </tr>
                    `);
                    return;
                }
                if (response.status === 'Success') {
                    console.log(response.data);
                    renderAttendanceList(response.data);
                } else {
                    $('#attendance-list-body').html(`
                        <tr>
                            <td colspan="6" class="text-center text-danger py-5">
                                <i class="ti ti-alert-triangle fs-24 d-block mb-2"></i>
                                Error: ${response.message}
                            </td>
                        </tr>
                    `);
                }
            }
        ).fail(function() {
            $('#attendance-list-body').html(`
                <tr>
                    <td colspan="6" class="text-center text-danger py-5">
                        <i class="ti ti-alert-triangle fs-24 d-block mb-2"></i>
                        Connection error. Failed to retrieve data.
                    </td>
                </tr>
            `);
        });
    }

    // 5. Render list of workers
    function renderAttendanceList(data) {
        window.currentAttendanceData = data || [];
        
        var total = window.currentAttendanceData.length;
        var verified = window.currentAttendanceData.filter(function(r) { return r.approvalstatus === 'approved'; }).length;
        var pending = total - verified;

        $('#total-badge').text('Total: ' + total).removeClass('bg-secondary-subtle text-secondary').addClass('bg-success-subtle text-success');
        
        $('#total-count-lbl').text(total);
        $('#verified-count-lbl').text(verified);
        $('#pending-count-lbl').text(pending);

        renderWorkLocationSummaryReport(window.currentAttendanceData);
        if (window.currentActiveFilter === 'all') {
            $('#work-location-summary-wrap').show();
        } else {
            $('#work-location-summary-wrap').hide();
        }

        applyVerificationFilters();
    }

    // 6. Action: Verify Attendance
    $(document).on('click', '.btn-unverified', function() {
        var $btn = $(this);
        var attendanceId = $btn.data('id');

        $btn.prop('disabled', true).html(`
            <span class="spinner-border spinner-border-sm text-warning" role="status" aria-hidden="true"></span> Verifying…
        `);

        $.post(
            baseurl + "modal/hrm_laboursalarymodal.php?action=VerifyLabourAttendance",
            {
                attendanceid: attendanceId,
                approvalstatus: 'approved',
                loginname: loginname
            },
            function(res) {
                console.log(res);
                var response = null;
                try {
                    response = (typeof res === 'object') ? res : JSON.parse(res);
                } catch (e) {
                    $btn.prop('disabled', false).html('<i class="ti ti-discount-check fs-14"></i> Verify Present');
                    Swal.fire({
                        icon: 'error',
                        title: 'Verification Failed',
                        text: 'Invalid response format from server.',
                        confirmButtonColor: '#d33'
                    });
                    return;
                }
                if (response.status === 'Success') {
                    // Update the row state in local memory data array
                    if (window.currentAttendanceData) {
                        var row = window.currentAttendanceData.find(function(r) { return r.attendanceid == attendanceId; });
                        if (row) {
                            row.approvalstatus = 'approved';
                            row.approvedby = loginname;
                        }
                    }

                    // Update summary counts
                    var total = window.currentAttendanceData.length;
                    var verified = window.currentAttendanceData.filter(function(r) { return r.approvalstatus === 'approved'; }).length;
                    var pending = total - verified;

                    $('#total-count-lbl').text(total);
                    $('#verified-count-lbl').text(verified);
                    $('#pending-count-lbl').text(pending);

                    // Re-run filter system (updates list rendering smoothly)
                    applyVerificationFilters();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Verified',
                        text: 'Labour physical presence has been successfully verified.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    $btn.prop('disabled', false).html('<i class="ti ti-discount-check fs-14"></i> Verify Present');
                    Swal.fire({
                        icon: 'error',
                        title: 'Verification Failed',
                        text: response.message,
                        confirmButtonColor: '#d33'
                    });
                }
            }
        ).fail(function() {
            $btn.prop('disabled', false).html('<i class="ti ti-discount-check fs-14"></i> Verify Present');
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Connection failed. Please check network connection.',
                confirmButtonColor: '#d33'
            });
        });
    });

    window.exportFilteredGrid = function() {
        var data = window.currentAttendanceData || [];
        var filterType = window.currentActiveFilter || 'all';
        var searchVal = $('#labourSearchInput').val().toLowerCase().trim();

        // 1. Filter by Card selection
        var filteredData = data;
        if (filterType === 'verified') {
            filteredData = data.filter(function(r) { return r.approvalstatus === 'approved'; });
        } else if (filterType === 'pending') {
            filteredData = data.filter(function(r) { return r.approvalstatus !== 'approved'; });
        }

        // 2. Filter by Search input
        if (searchVal) {
            filteredData = filteredData.filter(function(r) {
                var name = (r.FullName || '').toLowerCase();
                var id = (r.labourid || '').toLowerCase();
                return name.indexOf(searchVal) > -1 || id.indexOf(searchVal) > -1;
            });
        }

        if (filteredData.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Data to Export',
                text: 'There are no records in the grid matching your selected filters.',
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        var locName = $('#qr-gate-location option:selected').text().trim() || 'All';
        var polyName = $('#qr-polyhouse option:selected').text().trim() || 'All';
        var attDate = $('#attendance-date').val() || '';

        // Build CSV content
        var csvContent = [];
        
        // Report header lines
        csvContent.push('PHYSICAL VERIFICATION ATTENDANCE REPORT');
        csvContent.push('Date: ' + attDate);
        csvContent.push('Location: ' + locName + ' (' + polyName + ')');
        csvContent.push('Filter Type: ' + (filterType === 'pending' ? 'UN-VERIFIED' : filterType.toUpperCase()));
        csvContent.push(''); // Blank spacer line

        // Grid column headers
        csvContent.push([
            '#',
            'Labour ID',
            'Labour Name',
            'Check-In Time',
            'Check-Out Time',
            'Working Hours',
            'Verification Status',
            'Verified By'
        ].map(function(val) {
            return '"' + val.replace(/"/g, '""') + '"';
        }).join(','));

        // Grid rows
        filteredData.forEach(function(row, idx) {
            var statusStr = row.approvalstatus === 'approved' ? 'Verified' : 'Un-Verified';
            var checkin = row.checkintime ? row.checkintime.substring(11, 19) : '—';
            var checkout = row.checkouttime ? row.checkouttime.substring(11, 19) : '—';
            var whours = row.workinghours ? row.workinghours + ' Hrs' : '—';
            var verby = row.approvedby || '—';

            var line = [
                idx + 1,
                row.labourid || '',
                row.FullName || '',
                checkin,
                checkout,
                whours,
                statusStr,
                verby
            ].map(function(val) {
                return '"' + String(val).replace(/"/g, '""') + '"';
            }).join(',');
            csvContent.push(line);
        });

        // Generate download blob
        var csvString = csvContent.join('\r\n');
        var blob = new Blob(["\ufeff" + csvString], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement("a");
        var url = URL.createObjectURL(blob);
        
        var cleanLocName = locName.replace(/[^a-zA-Z0-9]/g, '_');
        var fileName = 'Physical_Verification_' + attDate + '_' + cleanLocName + '.csv';

        link.setAttribute("href", url);
        link.setAttribute("download", fileName);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    // Helper to escape HTML characters
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return str.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Click handler for Fetch Google Sheet button
    $('#btn-fetch-sheet, #modal-refresh-sheet').on('click', function() {
        var $btn = $(this);
        var originalHtml = $btn.html();
        
        // Fetch selected parameters
        var locId = $('#qr-gate-location').val();
        var locName = $('#qr-gate-location option:selected').text().trim() || 'Location';
        var polyId = $('#qr-polyhouse').val();
        var attDate = $('#attendance-date').val();

        if (!locId || !polyId || !attDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Selection Required',
                text: 'Please select Main Gate Location, Attendance QR Location, and Attendance Date first.',
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        // Update modal title dynamically
        $('#google-sheet-modal .modal-title').html('<i class="ti ti-table-share fs-20"></i> Google Sheet — ' + escapeHtml(locName) + ' Location Attendance');
        $('#sheet-sync-desc').text('Viewing real-time attendance log synchronized from the ' + locName + ' Location Google Sheet.');

        // Show loading state
        $btn.prop('disabled', true).html('<i class="ti ti-loader rotate fs-14"></i> Fetching...');
        
        // Open modal first if called from main button
        if ($btn.attr('id') === 'btn-fetch-sheet') {
            $('#google-sheet-modal').modal('show');
        }

        $('#google-sheet-table').html(`
            <thead>
                <tr>
                    <th class="text-center py-5 text-muted" colspan="100%">
                        <div class="spinner-border text-success spinner-border-sm me-2" role="status"></div>
                        Connecting to Google Sheets, please wait...
                    </th>
                </tr>
            </thead>
        `);

        $.post(
            baseurl + "modal/hrm_laboursalarymodal.php?action=FetchGoogleSheet",
            {
                locationid: locId,
                projectid: projectid,
                polyhouseid: polyId,
                attendancedate: attDate
            },
            function(res) {
                $btn.prop('disabled', false).html(originalHtml);
                var response = null;
                try {
                    response = (typeof res === 'object') ? res : JSON.parse(res);
                } catch (e) {
                    $('#google-sheet-table').html(`
                        <thead>
                            <tr>
                                <th class="text-center text-danger py-5" colspan="100%">
                                    <i class="ti ti-alert-triangle fs-24 d-block mb-2"></i>
                                    Error: Invalid response format from server.<br>
                                    <small class="text-muted d-block mt-2" style="font-weight: normal; word-break: break-all;">
                                        Raw Response: ${res}
                                    </small>
                                </th>
                            </tr>
                        </thead>
                    `);
                    return;
                }

                if (response.status === 'Success') {
                    var data = response.data;
                    window.lastFetchedGoogleSheetData = data;
                    if (!Array.isArray(data) || data.length === 0) {
                        $('#google-sheet-table').html(`
                            <thead>
                                <tr>
                                    <th class="text-center text-muted py-5" colspan="100%">
                                        <i class="ti ti-database-off fs-24 d-block mb-2"></i>
                                        No data found in the Google Sheet.
                                    </th>
                                </tr>
                            </thead>
                        `);
                        return;
                    }

                    // Detect actual table header row (skip top summary rows like Total IN/OUT)
                    var headerRowIdx = 0;
                    for (var r = 0; r < Math.min(5, data.length); r++) {
                        var rowItems = data[r] || [];
                        var joined = rowItems.join(' ').toUpperCase();
                        if (joined.indexOf('TIMESTAMP') > -1 || joined.indexOf('MACHINE') > -1 || (joined.indexOf('ID') > -1 && joined.indexOf('NAME') > -1)) {
                            headerRowIdx = r;
                            break;
                        }
                    }

                    // Check if summary values exist above header row (e.g. TOTAL IN / TOTAL OUT)
                    var summaryStatsHtml = '';
                    if (headerRowIdx > 0 && data[0]) {
                        var r0 = data[0];
                        var totalIn = '';
                        var totalOut = '';
                        for (var c = 0; c < r0.length; c++) {
                            var cellVal = (r0[c] || '').toString().trim();
                            if (/TOTAL\s*IN/i.test(cellVal) && r0[c+1]) totalIn = r0[c+1];
                            if (/TOTAL\s*OUT/i.test(cellVal) && r0[c+1]) totalOut = r0[c+1];
                        }
                        if (totalIn || totalOut) {
                            summaryStatsHtml = `<div class="d-inline-flex gap-2 ms-2">
                                ${totalIn ? `<span class="badge bg-success-subtle text-success border border-success font-11">Total In: <strong>${escapeHtml(totalIn)}</strong></span>` : ''}
                                ${totalOut ? `<span class="badge bg-primary-subtle text-primary border border-primary font-11">Total Out: <strong>${escapeHtml(totalOut)}</strong></span>` : ''}
                            </div>`;
                        }
                    }

                    // Update title & description with active sheet name and date tab
                    if (response.sheetName) {
                        $('#google-sheet-modal .modal-title').html('<i class="ti ti-table-share fs-20"></i> Google Sheet — ' + escapeHtml(locName) + ' (' + escapeHtml(response.sheetName) + ')');
                        $('#sheet-sync-desc').html('Viewing real-time attendance log synchronized from the <strong>' + escapeHtml(locName) + '</strong> Location Google Sheet (Tab Date: <strong>' + escapeHtml(response.sheetName) + '</strong>).' + summaryStatsHtml);
                    }

                    let html = '<thead><tr class="table-success">';
                    const headers = data[headerRowIdx] || data[0];
                    headers.forEach(h => {
                        html += `<th class="fw-bold text-dark py-2 px-3">${escapeHtml(h)}</th>`;
                    });
                    html += '</tr></thead><tbody>';

                    for (let i = headerRowIdx + 1; i < data.length; i++) {
                        html += '<tr>';
                        const row = data[i];
                        for (let j = 0; j < headers.length; j++) {
                            html += `<td class="py-2 px-3">${escapeHtml(row[j] || '')}</td>`;
                        }
                        html += '</tr>';
                    }
                    html += '</tbody>';
                    $('#google-sheet-table').html(html);
                } else {
                    $('#google-sheet-table').html(`
                        <thead>
                            <tr>
                                <th class="text-center text-danger py-5" colspan="100%" style="font-weight: 500; font-size: 13px; max-width: 600px; line-height: 1.6;">
                                    <i class="ti ti-alert-triangle fs-30 d-block mb-2 text-warning"></i>
                                    ${escapeHtml(response.message)}
                                </th>
                            </tr>
                        </thead>
                    `);
                }
            }
        ).fail(function() {
            $btn.prop('disabled', false).html(originalHtml);
            $('#google-sheet-table').html(`
                <thead>
                    <tr>
                        <th class="text-center text-danger py-5" colspan="100%">
                            <i class="ti ti-wifi-off fs-24 d-block mb-2"></i>
                            Failed to connect. Please check your network connection.
                        </th>
                    </tr>
                </thead>
            `);
        });
    });

    // Click handler for Verification with Google Sheet
    $('#modal-verify-sheet').on('click', function() {
        var $btn = $(this);
        var originalHtml = $btn.html();

        if (!window.lastFetchedGoogleSheetData || window.lastFetchedGoogleSheetData.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Data Fetched',
                text: 'Please fetch Google Sheet data first by clicking Refresh Data.',
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        if (!window.currentAttendanceData || window.currentAttendanceData.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Attendance Loaded',
                text: 'No attendance records loaded on the main screen to verify.',
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        var sheetData = window.lastFetchedGoogleSheetData;
        var headerRowIdx = -1;

        // Locate header row containing MACHINE ID (or MACHINE_ID, ID)
        for (var i = 0; i < sheetData.length; i++) {
            var row = sheetData[i];
            if (!Array.isArray(row)) continue;
            for (var j = 0; j < row.length; j++) {
                var cellHeader = (row[j] || '').toString().toUpperCase().trim();
                if (/MACHINE\s*ID/i.test(cellHeader) || cellHeader === 'ID' || cellHeader === 'LABOUR ID') {
                    headerRowIdx = i;
                    break;
                }
            }
            if (headerRowIdx !== -1) break;
        }

        // Helper to extract clean digits / ID string
        function cleanId(id) {
            if (!id) return '';
            var str = id.toString().trim();
            var digits = str.replace(/\D/g, '');
            return digits || str;
        }

        var headers = (headerRowIdx !== -1 && Array.isArray(sheetData[headerRowIdx])) ? sheetData[headerRowIdx] : [];
        var idCols = [];
        var nameCols = [];

        headers.forEach(function(h, idx) {
            var cell = (h || '').toString().toUpperCase().trim();
            if (cell === 'S.N' || cell === 'SN') return;
            if (/MACHINE\s*ID/i.test(cell) || cell === 'ID' || cell === 'LABOUR ID' || cell === 'LABOUR_ID') {
                idCols.push(idx);
            } else if (/NAME/i.test(cell) && !/DRIVER/i.test(cell) && !/VEHICLE/i.test(cell) && !/FARM/i.test(cell)) {
                nameCols.push(idx);
            }
        });

        // Collect unique labours from Google Sheet
        var sheetLaboursMap = new Map(); // cleanId -> { rawId, cleanId, name }

        var startRow = (headerRowIdx !== -1) ? (headerRowIdx + 1) : 0;
        for (var i = startRow; i < sheetData.length; i++) {
            var row = sheetData[i];
            if (!row || !Array.isArray(row)) continue;

            if (idCols.length > 0) {
                idCols.forEach(function(colIdx) {
                    var val = (row[colIdx] || '').toString().trim();
                    if (!val || val === '0' || val.toUpperCase() === 'ID' || val.toUpperCase() === 'MACHINE ID') return;

                    var cId = cleanId(val);
                    if (!cId) return;

                    // Find closest NAME column for this idCol
                    var closestNameCol = -1;
                    var minDiff = 999;
                    nameCols.forEach(function(nCol) {
                        var diff = Math.abs(nCol - colIdx);
                        if (diff < minDiff && diff <= 3) {
                            minDiff = diff;
                            closestNameCol = nCol;
                        }
                    });

                    var nameVal = (closestNameCol !== -1 && row[closestNameCol]) ? row[closestNameCol].toString().trim() : '';

                    if (!sheetLaboursMap.has(cId)) {
                        sheetLaboursMap.set(cId, {
                            rawId: val,
                            cleanId: cId,
                            name: nameVal || val
                        });
                    } else {
                        var existing = sheetLaboursMap.get(cId);
                        if (!existing.name || existing.name === existing.rawId) {
                            if (nameVal) existing.name = nameVal;
                        }
                        if (val.length > existing.rawId.length && /\D/.test(val)) {
                            existing.rawId = val;
                        }
                    }
                });
            } else {
                row.forEach(function(val) {
                    var rawVal = (val || '').toString().trim();
                    if (!rawVal || rawVal.length < 2) return;
                    var cId = cleanId(rawVal);
                    if (cId && !sheetLaboursMap.has(cId)) {
                        sheetLaboursMap.set(cId, {
                            rawId: rawVal,
                            cleanId: cId,
                            name: rawVal
                        });
                    }
                });
            }
        }

        // Build lookup sets for Google Sheet
        var sheetCleanIdSet = new Set();
        var sheetRawIdSet = new Set();
        var sheetLaboursMatchedSet = new Set();

        sheetLaboursMap.forEach(function(item) {
            if (item.cleanId) sheetCleanIdSet.add(item.cleanId);
            if (item.rawId) sheetRawIdSet.add(item.rawId.toUpperCase());
        });

        var toVerifyIds = [];
        var notVerifiedInSheet = []; // List 3: Mark Attendance labours missing in Google Sheet
        var matchedAttendanceCount = 0;

        window.currentAttendanceData.forEach(function(row) {
            var rawId = (row.labourid || '').toString().trim();
            var cId = cleanId(rawId);
            var upperRawId = rawId.toUpperCase();

            var isMatched = (cId && sheetCleanIdSet.has(cId)) || sheetRawIdSet.has(upperRawId);

            if (isMatched) {
                matchedAttendanceCount++;
                if (row.approvalstatus !== 'approved') {
                    toVerifyIds.push(row.attendanceid);
                }
                // Mark matching sheet labour as matched
                sheetLaboursMap.forEach(function(sItem) {
                    if (sItem.cleanId === cId || sItem.rawId.toUpperCase() === upperRawId) {
                        sheetLaboursMatchedSet.add(sItem.cleanId);
                    }
                });
            } else {
                notVerifiedInSheet.push({
                    id: rawId,
                    name: row.FullName || 'Unknown'
                });
            }
        });

        // List 2: Google Sheet labours missing in Mark Attendance
        var notVerifiedInAttendance = [];
        sheetLaboursMap.forEach(function(sItem) {
            if (!sheetLaboursMatchedSet.has(sItem.cleanId)) {
                notVerifiedInAttendance.push({
                    id: sItem.rawId,
                    name: sItem.name
                });
            }
        });

        var totalAttendanceCount = window.currentAttendanceData.length;
        var totalGoogleSheetCount = sheetLaboursMap.size;
        var verifiedCount = matchedAttendanceCount;

        function showSummaryReportModal() {
            var isFullMatch = (notVerifiedInAttendance.length === 0 && notVerifiedInSheet.length === 0);

            var htmlMsg = `
            <div class="text-start" style="font-size: 13px;">
                <!-- 1. Verified Summary -->
                <div class="p-3 mb-3 rounded" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                        <span class="fw-bold text-dark fs-14">
                            <i class="ti ti-checkbox text-success me-1"></i> 1. Verified Summary
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-12 fw-bold">
                            ${verifiedCount} Verified Matched
                        </span>
                    </div>
                    <div class="row text-center g-2 pt-1">
                        <div class="col-6">
                            <div class="p-2 rounded bg-white border">
                                <div class="text-muted fs-11 fw-semibold text-uppercase">Count of Attendance</div>
                                <div class="fs-16 fw-bold text-primary mt-1">${totalAttendanceCount}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-white border">
                                <div class="text-muted fs-11 fw-semibold text-uppercase">Count of Google Sheet</div>
                                <div class="fs-16 fw-bold text-info mt-1">${totalGoogleSheetCount}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. List of Not Verified Google Sheet Labour with Mark Attendance -->
                <div class="mb-3 p-3 rounded" style="background-color: #fffbeb; border: 1px solid #fef3c7;">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-warning-subtle">
                        <span class="fw-bold text-warning-emphasis fs-13">
                            <i class="ti ti-alert-circle text-warning me-1"></i> 2. List of Not Verified Google Sheet Labour with Mark Attendance
                        </span>
                        <span class="badge bg-warning text-dark fw-bold">${notVerifiedInAttendance.length}</span>
                    </div>
                    ${notVerifiedInAttendance.length === 0 ? 
                        '<div class="text-success fs-12 ms-1 fw-medium"><i class="ti ti-check me-1"></i>All Google Sheet labours are verified with Mark Attendance.</div>' :
                        '<ul class="mb-0 text-start fs-12 text-secondary ps-3" style="max-height: 140px; overflow-y: auto; list-style-type: square; line-height: 1.8;">' + 
                            notVerifiedInAttendance.map(function(item) {
                                return '<li>' + escapeHtml(item.name) + ' (ID: ' + escapeHtml(item.id) + ') — <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Not in Mark Attendance</span></li>';
                            }).join('') + 
                        '</ul>'
                    }
                </div>

                <!-- 3. List of Not Verified Mark Attendance with Google Sheet -->
                <div class="p-3 rounded" style="background-color: #fef2f2; border: 1px solid #fee2e2;">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-danger-subtle">
                        <span class="fw-bold text-danger fs-13">
                            <i class="ti ti-alert-triangle text-danger me-1"></i> 3. List of Not Verified Mark Attendance with Google Sheet
                        </span>
                        <span class="badge bg-danger text-white fw-bold">${notVerifiedInSheet.length}</span>
                    </div>
                    ${notVerifiedInSheet.length === 0 ? 
                        '<div class="text-success fs-12 ms-1 fw-medium"><i class="ti ti-check me-1"></i>All Mark Attendance records are verified with Google Sheet.</div>' :
                        '<ul class="mb-0 text-start fs-12 text-secondary ps-3" style="max-height: 140px; overflow-y: auto; list-style-type: square; line-height: 1.8;">' + 
                            notVerifiedInSheet.map(function(item) {
                                return '<li>' + escapeHtml(item.name) + ' (ID: ' + escapeHtml(item.id) + ') — <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Missing in Google Sheet</span></li>';
                            }).join('') + 
                        '</ul>'
                    }
                </div>
            </div>
            `;

            Swal.fire({
                icon: isFullMatch ? 'success' : 'warning',
                title: isFullMatch ? 'Verification Complete' : 'Attendance Verification Report Summary',
                html: htmlMsg,
                confirmButtonColor: isFullMatch ? '#28a745' : '#ffc107',
                confirmButtonText: 'OK',
                width: '650px'
            });
        }

        if (toVerifyIds.length === 0) {
            $('#google-sheet-modal').modal('hide');
            showSummaryReportModal();
            return;
        }

        // Show loading state on button
        $btn.prop('disabled', true).html('<i class="ti ti-loader rotate fs-14"></i> Verifying...');

        // Call batch VerifyLabourAttendance action
        $.post(
            baseurl + "modal/hrm_laboursalarymodal.php?action=VerifyLabourAttendance",
            {
                attendanceid: toVerifyIds.join(','),
                approvalstatus: 'approved',
                loginname: loginname
            },
            function(raw) {
                $btn.prop('disabled', false).html(originalHtml);
                var response = null;
                try {
                    response = JSON.parse(raw);
                } catch(e) {}

                if (response && response.status === 'Success') {
                    // Reload main attendance grid
                    checkFiltersAndLoad();
                    
                    // Hide the modal
                    $('#google-sheet-modal').modal('hide');

                    // Show the full verification report summary modal
                    showSummaryReportModal();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Verification Failed',
                        text: (response && response.message) ? response.message : 'Failed to update verification status.',
                        confirmButtonColor: '#d33'
                    });
                }
            }
        ).fail(function() {
            $btn.prop('disabled', false).html(originalHtml);
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Connection failed. Please check network connection.',
                confirmButtonColor: '#d33'
            });
        });
    });
});
</script>

<!-- Google Sheet Data Modal -->
<div class="modal fade" id="google-sheet-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title d-flex align-items-center gap-2 text-white m-0" style="font-family: 'Outfit', sans-serif; font-weight: 600;">
                    <i class="ti ti-table-share fs-20"></i>
                    Google Sheet — Sargwan Location Attendance
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto; background-color: #f8fafc;">
                <div class="alert alert-info d-flex align-items-center gap-2 mb-3" style="border-radius: 8px;">
                    <i class="ti ti-info-circle fs-20 text-info"></i>
                    <span id="sheet-sync-desc">Viewing real-time attendance log synchronized from the Google Sheet.</span>
                </div>
                <div class="table-responsive bg-white border" style="border-radius: 8px; overflow: hidden;">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0" id="google-sheet-table" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th class="text-center py-5 text-muted" colspan="100%">No data loaded.</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="border-radius: 20px; font-weight: 600;">Close</button>
                <button type="button" class="btn btn-warning btn-sm d-flex align-items-center gap-1 text-dark border-0" id="modal-verify-sheet" style="border-radius: 20px; font-weight: 600;">
                    <i class="ti ti-discount-check"></i> Attendance Verify With Google Sheet
                </button>
                <button type="button" class="btn btn-success btn-sm d-flex align-items-center gap-1 text-white border-0" id="modal-refresh-sheet" style="border-radius: 20px; font-weight: 600;">
                    <i class="ti ti-refresh"></i> Refresh Data
                </button>
            </div>
        </div>
    </div>
</div>

<?php
if (!isset($_GET['vw']) || $_GET['vw'] != 1) {
    require('footer.php');
}
?>
