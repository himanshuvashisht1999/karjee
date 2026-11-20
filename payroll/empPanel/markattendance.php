<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
date_default_timezone_set('Asia/Kolkata');
require('header.php');
$sessionkey = session_id();
$empid = $_GET['empid'] ?? '';
if (!empty($isResigned)) {
    echo "<div class='container mt-5 text-center'><div class='alert alert-warning py-4'><h4><i class='ti ti-alert-triangle me-2'></i>Access Restricted</h4><p class='mb-3'>Mark Attendance is disabled because this employee is marked as resigned.</p><a href='index.php?k=" . urlencode($key) . "&empid=" . urlencode($empid) . "&accessby=" . urlencode($accessby) . "' class='btn btn-primary'>Back to Profile</a></div></div>";
    include('footer.php');
    exit;
}
$attendancedate = date('Y-m-d'); // today's date

$stmt = $hrmconn->prepare("
    SELECT e.EmployeeID, e.FirstName,e.MiddleName,e.LastName, e.ProjectID, e.attendanceType, 
           a.AttendanceID, a.CheckInTime, a.CheckOutTime, 
           a.WorkingHours, e.WorkLocation 
    FROM hrm_EmployeesMaster e 
    LEFT JOIN hrm_EmployeeAttendance a 
        ON e.EmployeeID = a.EmployeeID 
        AND a.AttendanceDate = ? 
        AND (a.tedit IS NULL OR a.tedit = '')
    WHERE e.EmployeeID = ? 
    ORDER BY a.AttendanceID DESC 
    LIMIT 1
");

$stmt->bind_param("ss", $attendancedate, $empid);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

$officeLat = "0";
$officeLng = "0";

if ($row) {

    // ✅ Step 1: Check Exemption Location
    $locationid = '';
    $EmployeeName = implode(' ', array_filter([
        trim($row['FirstName'] ?? ''),
        trim($row['MiddleName'] ?? ''),
        trim($row['LastName'] ?? '')
    ]));

    $exStmt = $hrmconn->prepare("
        SELECT LocationId 
        FROM hrm_EmployeeAttLocationExemption 
        WHERE ProjectID = ? 
        AND EmployeeID = ? 
        AND AttendanceDate = ?
        LIMIT 1
    ");

    $exStmt->bind_param("sss", $row['ProjectID'], $row['EmployeeID'], $attendancedate);
    $exStmt->execute();
    $exRes = $exStmt->get_result();

    if ($exRes->num_rows > 0) {
        $mrow = $exRes->fetch_assoc();
        $locationid = !empty($mrow['LocationId']) ? strtolower($mrow['LocationId']) : '';
    } else {
        $locationid = !empty($row['WorkLocation']) ? strtolower($row['WorkLocation']) : '';
    }

    $exStmt->close();

    // ✅ Step 2: Fetch Office Coordinates
    if (!empty($locationid)) {

        $latlngStmt = $hrmconn->prepare("
            SELECT OfficeLat, OfficeLng 
            FROM hrm_AttendanceLocationMaster 
            WHERE ProjectID = ? 
            AND LOWER(LocationId) = ? 
            LIMIT 1
        ");

        $latlngStmt->bind_param("ss", $row['ProjectID'], $locationid);
        $latlngStmt->execute();
        $latlngRes = $latlngStmt->get_result()->fetch_assoc();

        if ($latlngRes) {
            $officeLat = $latlngRes['OfficeLat'] ?? "0";
            $officeLng = $latlngRes['OfficeLng'] ?? "0";
        }

        $latlngStmt->close();
    }
}
// ✅ Default status
$nextAction = 'MarkIn';
$message = 'Welcome! Please punch in to start.';
$attendanceType = '';
$ProjectID = '';
$EmployeeID = $empid;

if ($row) {
  $EmployeeID = $row['EmployeeID'];
  $ProjectID = $row['ProjectID'];
  $CheckInTime = $row['CheckInTime'] ?? null;
  $CheckOutTime = $row['CheckOutTime'] ?? null;
  $attendanceType = $row['attendanceType'] ?? '';
  $WorkingHours = $row['WorkingHours'] ?? '-';

  if (empty($CheckInTime)) {
    $nextAction = 'MarkIn';
    $message = 'Mark In required.';
  } elseif (empty($CheckOutTime)) {
    $nextAction = 'MarkOut';
    $message = 'Mark Out required.';
  } else {
    $nextAction = 'MarkIn';
    $message = 'Previous session completed. New Mark In required.';
  }
}
?>

<div class="content">
  <!-- Header & Breadcrumbs -->
  <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-4">
    <div class="my-auto mb-2">
      <h2 class="mb-1 fw-bold">Attendance Dashboard</h2>
      <nav>
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="index.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"><i
                class="ti ti-smart-home"></i></a></li>
          <li class="breadcrumb-item active text-primary" aria-current="page">Real-time Attendance</li>
        </ol>
      </nav>
    </div>
    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
      <div class="mb-2">
        <h5 class="text-primary fw-bold mb-0" id="todayDate"></h5>
      </div>
    </div>
  </div>

  <!-- Permission Alerts -->
  <div id="permissionAlert" class="mb-3"></div>

  <!-- Main Punch-in Control -->
  <div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-body p-0">
      <div class="row g-0">
        <div class="col-lg-5 bg-light d-flex flex-column align-items-center justify-content-center p-3"
          style="min-height: 240px; border-right: 1px solid #f1f5f9;">
          <!-- Camera Feed -->
          <div class="w-100 position-relative rounded-3 overflow-hidden" style="height: 180px;">
            <video id="camera" class="w-100 h-100 object-fit-cover" autoplay playsinline></video>
            <canvas id="canvas" style="display:none;"></canvas>
            <div
              class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center pointer-events-none">
              <div class="camera-frame border border-dark border-2 rounded-4" style="width: 60%; height: 70%;"></div>
            </div>
          </div>
          
          <!-- Alert message container -->
          <div id="alertmessagediv" class="w-100 mt-2 text-center" style="z-index: 15;"></div>

          <!-- Action Buttons (Mark In / Mark Out) just below camera -->
          <div class="d-flex justify-content-center gap-3 mt-3 w-100" style="z-index: 15;">
            <?php if ($nextAction == 'MarkIn'): ?>
              <button id="markInBtn" class="btn btn-success px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2 d-none"
                onclick="submitAttendance('MarkIn')">
                <i class="ti ti-login fs-20"></i>
                <span>Mark In</span>
              </button>
            <?php else: ?>
              <button id="markOutBtn" class="btn btn-danger px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2 d-none"
                onclick="submitAttendance('MarkOut')">
                <i class="ti ti-logout fs-20"></i>
                <span>Mark Out</span>
              </button>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-7 p-3 d-flex flex-column justify-content-center bg-white">
          <div class="text-center mb-3">
            <!-- 1. Employee Name & ID -->
            <?php if (!empty($EmployeeName)): ?>
              <div class="d-flex align-items-center justify-content-center gap-2 mb-2 flex-wrap">
                <span class="fs-16 fw-bold text-dark">
                  <i class="ti ti-user-circle text-primary me-1 fs-18"></i>
                  <?php echo htmlspecialchars($EmployeeName); ?>
                </span>
                <?php if (!empty($EmployeeID)): ?>
                  <span class="badge bg-light text-muted fs-11 border">ID: <?php echo htmlspecialchars($EmployeeID); ?></span>
                <?php endif; ?>
                <!-- Status Badge just after ID -->
                <span class="badge bg-soft-info text-info border border-info-subtle fs-11">
                  <i class="ti ti-info-circle me-1" style="font-size: 0.75rem;"></i> <?php echo $message; ?>
                </span>
              </div>
            <?php endif; ?>
          </div>

          <input type="hidden" id="EmployeeID" name="EmployeeID" value="<?php echo $empid; ?>">
          <input type="hidden" id="Source" name="Source" value="Web">
          <input type="hidden" id="Latitude" name="Latitude">
          <input type="hidden" id="Longitude" name="Longitude">
          <input type="hidden" id="Distance" name="Distance">
          <input type="hidden" id="locationid" name="locationid" value="<?php echo $locationid ?? ''; ?>">

          <input type="hidden" id="ProjectID" name="ProjectID" value="<?php echo $ProjectID ?? ''; ?>">
          <input type="hidden" id="OfficeLat" value="<?php echo $officeLat; ?>">
          <input type="hidden" id="OfficeLng" value="<?php echo $officeLng; ?>">

          <!-- Summary Grid containing Distance, Working Hours, and Live Status -->
          <div class="p-2 bg-light rounded-3">
            <div class="row g-2 text-center align-items-center">
              <!-- Column 1: Distance -->
              <div class="col-4 border-end">
                <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 0.62rem; letter-spacing: 0.3px;">Distance</div>
                <div class="small fw-bold text-primary" id="displayDistance" style="font-size: 0.8rem;">0.00 KM</div>
              </div>
              
              <!-- Column 2: Resized Working Hours -->
              <div class="col-4 border-end">
                <div class="text-muted extra-small text-uppercase fw-bold mb-1" style="font-size: 0.62rem; letter-spacing: 0.3px;">Working Hours</div>
                <?php if ($nextAction == 'MarkOut' && !empty($CheckInTime)): ?>
                  <div id="liveWorkHours" class="fw-bold text-warning-d" style="font-size: 0.76rem; line-height: 1.2;">
                    <i class="ti ti-alarm me-1"></i> Calculating...
                  </div>
                  <div class="text-muted" style="font-size: 0.55rem; margin-top: 1px;">
                    In: <?php echo date('h:i A', strtotime($CheckInTime)); ?>
                  </div>
                  <input type="hidden" id="checkInTimeRaw" value="<?php echo htmlspecialchars($CheckInTime); ?>">
                <?php else: ?>
                  <div class="text-muted" style="font-size: 0.8rem; font-weight: 500;">--:--</div>
                <?php endif; ?>
              </div>
              
              <!-- Column 3: Live Status -->
              <div class="col-4">
                <div class="text-muted extra-small text-uppercase fw-bold" style="font-size: 0.62rem; letter-spacing: 0.3px;">Live Status</div>
                <div class="small fw-bold <?php echo $nextAction == 'MarkOut' ? 'text-success' : 'text-danger'; ?>" style="font-size: 0.8rem;">
                  <?php echo $nextAction == 'MarkOut' ? 'In Office' : 'Checked Out'; ?>
                </div>
              </div>
              
              <div class="col-12 border-top mt-2 pt-1">
                <div class="extra-small text-muted" style="font-size: 0.65rem;">
                  <i class="ti ti-map-pin me-1"></i> <span id="displayCoords">Detecting location...</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Summary Cards (Animated Gradients) -->
  <div class="row g-2 mb-4">
    <div class="col-md-4 col-6">
      <div class="card leave-card bg-earn-leave py-2">
        <div class="card-body py-1">
          <div class="d-flex align-items-center justify-content-between">
            <div class="leave-info">
              <p class="mb-0 small opacity-75">Month Records</p>
              <h5 id="totalDays" class="mb-0 fw-bold">0</h5>
            </div>
            <div class="leave-icon small d-none d-sm-flex">
              <i class="ti ti-calendar fs-20"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-6">
      <div class="card leave-card bg-sick-leave py-2">
        <div class="card-body py-1">
          <div class="d-flex align-items-center justify-content-between">
            <div class="leave-info">
              <p class="mb-0 small opacity-75">Present Days</p>
              <h5 id="presentDays" class="mb-0 fw-bold">0</h5>
            </div>
            <div class="leave-icon small d-none d-sm-flex">
              <i class="ti ti-user-check fs-20"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-12">
      <div class="card leave-card bg-casual-leave py-2">
        <div class="card-body py-1">
          <div class="d-flex align-items-center justify-content-between">
            <div class="leave-info">
              <p class="mb-0 small opacity-75">MIS Incidents</p>
              <h5 id="missDays" class="mb-0 fw-bold">0</h5>
            </div>
            <div class="leave-icon small d-none d-sm-flex">
              <i class="ti ti-alert-triangle fs-20"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- Attendance History (Info Strips) -->
  <div class="mt-4">
    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
      <h4 class="mb-0 fw-bold">Attendance Snapshot</h4>

      <a href="empAttendance.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"
        class="btn btn-outline-primary btn-sm rounded-pill px-3">Detailed View</a>
    </div>

    <div id="attendanceStripsWrapper" class="info-strip-container">
      <!-- Data populated via AJAX -->
      <div class="text-center py-5">
        <div class="spinner-border text-primary spinner-border-sm"></div>
        <p class="small text-muted mt-2">Syncing attendance records...</p>
      </div>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div id="loadingOverlay"
    style="display:none; position:fixed; inset:0; background:rgba(255,255,255,0.9); backdrop-filter: blur(4px); z-index:9999; align-items:center; justify-content:center; flex-direction:column;">
    <div class="spinner-grow text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
    <div id="loadingText" class="fw-bold text-primary fs-18">Processing...</div>
  </div>
</div>

<?php
include('footer.php');
?>

<style>
  .camera-frame {
    border: 2px solid #000;
    border-radius: 12px;
    pointer-events: none;
    box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.2);
    z-index: 10;
  }

  .extra-small {
    font-size: 0.7rem;
  }

  .attendance-status-badge .badge {
    letter-spacing: 0.5px;
    font-weight: 600;
  }

  #camera {
    background: #000;
    transform: scaleX(-1);
  }
  .text-warning-d {
    color: #b45309 !important; /* Dark orange/amber color */
  }
  @keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
  .spin-slow-icon {
    display: inline-block;
    animation: spin-slow 8s linear infinite;
  }
</style>

<script>
  // Helper to format raw database datetime to clean 12-hour time (HH:MM:SS AM/PM)
  function formatTimeOnly(dateTimeStr) {
    if (!dateTimeStr) return '--:--';
    if (dateTimeStr.includes('AM') || dateTimeStr.includes('PM')) return dateTimeStr;
    
    let timeStr = dateTimeStr;
    const parts = dateTimeStr.split(' ');
    if (parts.length === 2) {
      timeStr = parts[1];
    }
    
    const timeSubparts = timeStr.split(':');
    if (timeSubparts.length >= 2) {
      let hours = parseInt(timeSubparts[0], 10);
      const minutes = timeSubparts[1];
      const seconds = timeSubparts[2] || '00';
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12;
      hours = hours ? hours : 12; // '0' becomes '12'
      return `${hours}:${minutes}:${seconds} ${ampm}`;
    }
    return dateTimeStr;
  }

  // Helper to calculate exact working hours duration between In and Out datetimes
  function calculateWorkingHours(dateTimeIn, dateTimeOut, isToday) {
    if (!dateTimeIn) return '--';
    
    let inStr = dateTimeIn;
    if (!inStr.includes(' ')) {
      const todayYmd = new Date().toISOString().split('T')[0];
      inStr = todayYmd + ' ' + inStr;
    }
    const inDate = new Date(inStr.replace(/-/g, "/"));
    
    let outDate;
    let isActive = false;
    
    if (dateTimeOut) {
      let outStr = dateTimeOut;
      if (!outStr.includes(' ')) {
        const todayYmd = new Date().toISOString().split('T')[0];
        outStr = todayYmd + ' ' + outStr;
      }
      outDate = new Date(outStr.replace(/-/g, "/"));
    } else if (isToday) {
      outDate = new Date();
      isActive = true;
    } else {
      return '--';
    }
    
    const diffMs = outDate - inDate;
    if (diffMs <= 0) return '00:00:00';
    
    const totalSecs = Math.floor(diffMs / 1000);
    const hours = Math.floor(totalSecs / 3600);
    const mins = Math.floor((totalSecs % 3600) / 60);
    const secs = totalSecs % 60;
    
    const pad = (num) => String(num).padStart(2, '0');
    return `${pad(hours)}h ${pad(mins)}m ${pad(secs)}s` + (isActive ? ' (Active)' : '');
  }

  // ==============================
  // ✅ 0. LIVE WORK HOURS TIMER
  // ==============================
  (function() {
    window.addEventListener("load", () => {
      const checkInInput = document.getElementById("checkInTimeRaw");
      if (checkInInput && checkInInput.value) {
        const checkInTimeStr = checkInInput.value; // Format: YYYY-MM-DD HH:MM:SS
        // Cross-browser safe Date parsing (replace dashes with slashes)
        const checkInDate = new Date(checkInTimeStr.replace(/-/g, "/"));
        
        function updateLiveWorkHours() {
          const now = new Date();
          const diffMs = now - checkInDate;
          const hoursEl = document.getElementById("liveWorkHours");
          
          if (hoursEl && diffMs > 0) {
            const totalSecs = Math.floor(diffMs / 1000);
            const hours = Math.floor(totalSecs / 3600);
            const mins = Math.floor((totalSecs % 3600) / 60);
            const secs = totalSecs % 60;
            
            const pad = (num) => String(num).padStart(2, '0');
            
            hoursEl.innerHTML = `<i class="ti ti-alarm me-1 text-warning spin-slow-icon"></i> ${pad(hours)}h ${pad(mins)}m ${pad(secs)}s`;
          }
        }
        
        updateLiveWorkHours();
        setInterval(updateLiveWorkHours, 1000);
      }
    });
  })();

  // ==============================
  // ✅ 1. GLOBAL LOADING HANDLER
  // ==============================
  const attType = '<?php echo $attendanceType; ?>';
  let MAX_ALLOWED_DISTANCE_KM = 0.8; // 500 meters
  if (attType === 'outoffice') {
    MAX_ALLOWED_DISTANCE_KM = 200000;
  }

  function showLoading(show, text = "Please wait...") {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) {
      document.getElementById("loadingText").innerText = text;
      overlay.style.display = show ? "flex" : "none";
    }
    if (show) {
      Swal.fire({
        title: text,
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
    } else {
      Swal.close();
    }
  }

  function detectmobile() {
    const agnt = navigator.userAgent.toLowerCase();
    const isMobile = /android|iphone|ipad|ipod|opera mini|iemobile|wpdesktop/.test(agnt);
    document.getElementById("Source").value = isMobile ? "Mobile" : "Desktop";
  }

  // ==============================
  // ✅ 2. CAMERA HANDLING
  // ==============================
  async function startCamera() {
    const video = document.getElementById("camera");
    const pAlert = document.getElementById("permissionAlert");
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } });
      video.srcObject = stream;
      pAlert.innerHTML = ""; // Clear if successful
    } catch (err) {
      $("#alertmessagediv").html(""); // Clear geofencing alert
      pAlert.innerHTML = `
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-0">
          <i class="ti ti-camera-off me-3 fs-24"></i>
          <div>
            <div class="fw-bold">Camera Access Required</div>
            <div class="small opacity-75">Please allow camera permissions in your browser settings to continue.</div>
          </div>
        </div>`;
      //setTimeout(() => { pAlert.innerHTML = ""; }, 3000);
      iziToast.error({ title: 'Camera Error', message: 'Unable to access camera.' });
    }
  }

  async function capturePhoto() {
    const video = document.getElementById("camera");
    const canvas = document.getElementById("canvas");
    try {
      let w = (video && video.videoWidth > 0) ? video.videoWidth : (video ? video.clientWidth : 320);
      let h = (video && video.videoHeight > 0) ? video.videoHeight : (video ? video.clientHeight : 240);
      if (!w || w <= 0) w = 320;
      if (!h || h <= 0) h = 240;

      canvas.width = w;
      canvas.height = h;
      const ctx = canvas.getContext("2d");

      if (video && video.videoWidth > 0 && video.videoHeight > 0) {
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      } else {
        // Fallback canvas frame when camera is not ready
        ctx.fillStyle = "#1e293b";
        ctx.fillRect(0, 0, w, h);
        ctx.fillStyle = "#ffffff";
        ctx.font = "14px Arial";
        ctx.textAlign = "center";
        ctx.fillText("Camera Stream Offline", w / 2, h / 2);
      }

      let blob = await new Promise((resolve) => {
        try {
          canvas.toBlob((b) => resolve(b), "image/jpeg", 0.85);
        } catch (e) {
          resolve(null);
        }
      });

      if (!blob || !(blob instanceof Blob)) {
        try {
          const dataUrl = canvas.toDataURL("image/jpeg", 0.85);
          const arr = dataUrl.split(',');
          const mime = (arr[0] && arr[0].match(/:(.*?);/)) ? arr[0].match(/:(.*?);/)[1] : "image/jpeg";
          const bstr = atob(arr[1] || "");
          let n = bstr.length;
          const u8arr = new Uint8Array(n);
          while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
          }
          blob = new Blob([u8arr], { type: mime });
        } catch (e) {
          blob = new Blob(["placeholder"], { type: "image/jpeg" });
        }
      }

      return blob;
    } catch (err) {
      console.warn("Photo capture exception fallback:", err);
      return new Blob(["placeholder"], { type: "image/jpeg" });
    }
  }

  function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return (R * c).toFixed(2);
  }

  function getLocation() {
    const pAlert = document.getElementById("permissionAlert");
    
    // Unset old location
    document.getElementById("Latitude").value = "";
    document.getElementById("Longitude").value = "";
    document.getElementById("Distance").value = "";
    document.getElementById("displayDistance").innerText = "0.00 KM";
    document.getElementById("displayCoords").innerText = "Detecting location...";
    showLoading(true, "Detecting Location and finding address...");

    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        showLoading(false);
        return reject(new Error("Geolocation not supported."));
      }
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          pAlert.innerHTML = ""; // Clear on success
          const lat = pos.coords.latitude;
          const lng = pos.coords.longitude;
          document.getElementById("Latitude").value = lat;
          document.getElementById("Longitude").value = lng;
          const oLat = parseFloat(document.getElementById("OfficeLat").value);
          const oLng = parseFloat(document.getElementById("OfficeLng").value);
          let dist = 0;
          let isOfficeCoordsConfigured = !isNaN(oLat) && !isNaN(oLng) && oLat !== 0 && oLng !== 0;

          if (isOfficeCoordsConfigured) {
            dist = calculateDistance(lat, lng, oLat, oLng);
            document.getElementById("displayDistance").innerText = dist + " KM";
          } else {
            document.getElementById("displayDistance").innerText = "Bypassed (No Office GPS)";
          }
          document.getElementById("Distance").value = dist;
          document.getElementById("displayCoords").innerText = lat.toFixed(6) + ", " + lng.toFixed(6);

          if (isOfficeCoordsConfigured && dist > MAX_ALLOWED_DISTANCE_KM) {
            $("#alertmessagediv").html(`
              <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-0 p-3">
                <i class="ti ti-map-2 me-3 fs-30"></i>
                <div>
                  <div class="fw-bold">Out of Office Range</div>
                  <div class="small">You are <strong>${dist} KM</strong> away. Please move closer to the office (within ${MAX_ALLOWED_DISTANCE_KM} KM) to enable punch controls.</div>
                </div>
              </div>
            `);
            $('#markInBtn, #markOutBtn').addClass('d-none');
          } else {
            $("#alertmessagediv").html("");
            $('#markInBtn, #markOutBtn').removeClass('d-none');
          }
          showLoading(false);
          resolve(true);
        },
        (err) => {
          $("#alertmessagediv").html(""); // Clear geofencing alert
          pAlert.innerHTML = `
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mt-2">
              <i class="ti ti-map-pin-off me-3 fs-24"></i>
              <div>
                <div class="fw-bold">Location Required</div>
                <div class="small opacity-75">Please enable GPS/Location services to verify your punch.</div>
              </div>
            </div>`;
          //setTimeout(() => { pAlert.innerHTML = ""; }, 3000);
          showLoading(false);
          reject(new Error("Please enable location access."));
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
      );
    });
  }


  async function submitAttendance(actionType) {
    try {
      showLoading(true, "Detecting location and finding address...");
      await getLocation();

      const lat = document.getElementById("Latitude").value;
      const lng = document.getElementById("Longitude").value;
      if (!lat || !lng) {
        showLoading(false);
        iziToast.error({ title: "Location Error", message: "GPS Location is required to mark attendance. Please enable location access." });
        return;
      }

      const distance = parseFloat(document.getElementById("Distance").value);
      if (distance > MAX_ALLOWED_DISTANCE_KM) {
        showLoading(false);
        iziToast.warning({ title: "Distance Alert", message: `Verification failed. Distance: ${distance} KM` });
        return;
      }

      showLoading(true, "Capturing Face...");
      let photoBlob;
      try {
        photoBlob = await capturePhoto();
      } catch (e) {
        photoBlob = null;
      }

      if (!photoBlob || !(photoBlob instanceof Blob)) {
        photoBlob = new Blob(["placeholder"], { type: "image/jpeg" });
      }

      showLoading(true, "Syncing Records...");

      const formData = new FormData();
      formData.append("ActionType", actionType);
      formData.append("Photo", photoBlob, "punch.jpg");
      formData.append("EmployeeID", document.getElementById("EmployeeID").value);
      formData.append("Source", document.getElementById("Source").value);
      formData.append("Latitude", document.getElementById("Latitude").value);
      formData.append("Longitude", document.getElementById("Longitude").value);
      formData.append("Distance", document.getElementById("Distance").value);
      formData.append("locationid", document.getElementById("locationid").value);
      formData.append("ProjectID", document.getElementById("ProjectID").value);

      const response = await fetch("attendance_submit.php", { method: "POST", body: formData });
      const resultText = await response.text();
      let result;
      try {
          result = JSON.parse(resultText);
      } catch (e) {
          console.error("Malformed JSON:", resultText);
          throw new Error("Invalid server response format.");
      }

      if (result.status === "success") {
          iziToast.success({
              title: "Success",
              message: result.message || `${actionType} completed!`,
              onClosing: () => window.location.reload()
          });
      } else {
          throw new Error(result.message || "Failed to save attendance.");
      }

    } catch (err) {
      iziToast.error({ title: "Error", message: err.message || "Failed to process punch." });
      setTimeout(() => window.location.reload(), 1000);
    } finally {
      showLoading(false);
    }
  }

  async function renderEmpAttendanceTable() {
    const wrapper = document.getElementById("attendanceStripsWrapper");
    wrapper.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary spinner-border-sm"></div></div>`;

    try {
      showLoading(true, "Syncing records...");
      const eid = document.getElementById("EmployeeID").value.trim();
      const response = await fetch("get_attendance_data.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "EmployeeID=" + encodeURIComponent(eid),
      });

      const text = await response.text();
      let result;
      try {
        result = JSON.parse(text);
      } catch (e) {
        console.error("JSON Parse Error. Server returned:", text);
        throw new Error("Invalid server response format.");
      }

      if (result.status !== "success") throw new Error(result.message);

      const data = result.data;
      const todayStr = new Date().toLocaleDateString('en-GB').replace(/\//g, '-');

      // Filter: Show Today OR MIS (In but no Out)
      const filteredData = data.filter(row => {
        const isToday = row.date === todayStr;
        const isMIS = row.in && !row.out;
        return isToday || isMIS;
      });

      if (filteredData.length === 0) {
        wrapper.innerHTML = `<div class="alert alert-light text-center border-dashed py-5"><i class="ti ti-notes-off fs-30 d-block mb-2 text-muted"></i>No active records or MIS found.</div>`;
        showLoading(false);
        return;
      }

      let stripsHtml = "";
      filteredData.forEach((row) => {
        let statusText = "Absent", sClass = "status-rejected", sIcon = "ti-circle-x";
        if (row.in && row.out) { statusText = "Present"; sClass = "status-approved"; sIcon = "ti-circle-check"; }
        else if (row.in) { statusText = "MIS"; sClass = "status-pending"; sIcon = "ti-alert-triangle"; }

        let dayNum = row.date.split('-')[0];
        let isToday = row.date === todayStr;

        stripsHtml += `
        <div class="info-strip shadow-sm ${isToday ? 'border-primary' : ''}">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <div class="info-strip-icon ${isToday ? 'bg-primary text-white' : 'bg-soft-primary text-primary'} font-weight-bold">${dayNum}</div>
                </div>
                <div class="col">
                    <div class="row g-2">
                        <div class="col-md-3 col-6">
                            <div class="info-strip-label">Date ${isToday ? '(Today)' : ''}</div>
                            <div class="info-strip-value">${row.date}</div>
                        </div>
                        <div class="col-md-3 col-6 text-md-center">
                            <div class="info-strip-label">In Time</div>
                            <div class="info-strip-value text-success fw-bold">${formatTimeOnly(row.in)}</div>
                        </div>
                        <div class="col-md-3 col-6 text-md-center">
                            <div class="info-strip-label">Out Time</div>
                            <div class="info-strip-value text-danger fw-bold">${formatTimeOnly(row.out)}</div>
                        </div>
                        <div class="col-md-3 col-6 text-md-center">
                            <div class="info-strip-label">Duration</div>
                            <div class="info-strip-value fw-bold">${calculateWorkingHours(row.in, row.out, isToday)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-auto text-end border-start ps-4 d-none d-lg-block">
                    <span class="status-badge ${sClass} px-3" style="min-width: 110px; justify-content: center;">
                        <i class="ti ${sIcon}"></i> ${statusText}
                    </span>
                </div>
            </div>
        </div>`;
      });
      wrapper.innerHTML = stripsHtml;

      // Update summary counts based on FULL DATA (for context)
      const now = new Date();
      document.getElementById("totalDays").textContent = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
      document.getElementById("presentDays").textContent = data.filter(d => d.in && d.out).length;
      document.getElementById("missDays").textContent = data.filter(d => d.in && !d.out).length;

      showLoading(false);
    } catch (error) {
      showLoading(false);
      wrapper.innerHTML = `<div class="alert alert-danger mb-0">${error.message}</div>`;
    }
  }


  const today = new Date();
  document.getElementById("todayDate").textContent = today.toLocaleDateString("en-GB", { day: "2-digit", month: "long", year: "numeric" });

  window.addEventListener("load", async () => {
    startCamera();
    detectmobile();
    try {
      await getLocation();
    } catch (err) {
      console.warn("Location prompt error on load:", err);
    }
    renderEmpAttendanceTable();
  });
</script>