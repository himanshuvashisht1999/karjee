<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
date_default_timezone_set('Asia/Kolkata');
include("../connection.php");
// print_r($centre_data);
// require "distance.php";

$geoloc = $_POST["attloc"];
$bsrc = $_POST["sbro"];
$cip = $_POST["cip"];
$coord = explode("|", $geoloc);
$latitude = $coord[0];
$longitude = $coord[1];

$key = session_id();
$loginid = $_POST['loginid'] ?? '';
$loginpassword = $_POST['loginpassword'] ?? '';
// $loginid = '8923339447';
// $loginpassword = '9447';
$attendancedate = date('Y-m-d'); // today's date

$cheklogin = $authconn->query("
    SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, 
           isActive, isLogined, loginDatetime, comments  
    FROM login_user_master 
    WHERE (tedit IS NULL OR tedit = '') 
      AND loginid = '" . $authconn->real_escape_string($loginid) . "'  
      AND loginpassword = '" . $authconn->real_escape_string(trim($loginpassword)) . "'
");

$loginreturn = [];

if ($cheklogin && $cheklogin->num_rows === 1) {
  $lgrow = $cheklogin->fetch_assoc();

  // ✅ Get Employee and Attendance Status (Join)
  $stmt = $hrmconn->prepare("
        SELECT 
            e.EmployeeID, 
            e.ProjectID, 
            a.AttendanceID, 
            a.CheckInTime, 
            a.CheckOutTime
        FROM hrm_EmployeesMaster e
        LEFT JOIN hrm_EmployeeAttendance a 
            ON e.EmployeeID = a.EmployeeID 
            AND a.AttendanceDate = ?
        WHERE e.LoginID = ? ORDER BY a.AttendanceID DESC LIMIT 1
    ");
  $stmt->bind_param("ss", $attendancedate, $lgrow['loginid']);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();

  if (!$row) {
    $loginreturn = [
      'status' => 'error',
      'message' => 'Employee not found for LoginID: ' . $loginid
    ];
  } else {
    $EmployeeID = $row['EmployeeID'];
    $ProjectID = $row['ProjectID'];
    $CheckInTime = $row['CheckInTime'] ?? null;
    $CheckOutTime = $row['CheckOutTime'] ?? null;

    // ✅ Determine next required action
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
  <!doctype html>
  <html lang="en">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
      body {
        background: linear-gradient(135deg, #f6f8fc, #e8f0fe);
        font-family: 'Poppins', sans-serif;
      }

      h4.title {
        text-align: center;
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
      }

      /* Camera Section */
      #camera {
        width: 350px;
        max-height: 250px;
        border-radius: 15px;
        object-fit: cover;
        border: 3px solid #e0e0e0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      }

      /* Button Styles */
      .btn {
        border-radius: 25px;
        font-weight: 500;
      }

      .btn-success {
        background: linear-gradient(90deg, #28a745, #34d058);
        border: none;
      }

      .btn-danger {
        background: linear-gradient(90deg, #dc3545, #ff5c75);
        border: none;
      }

      /* Summary Cards */
      .summary-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        background: #fff;
        transition: transform 0.2s ease;
      }

      .summary-card:hover {
        transform: scale(1.03);
      }

      .summary-card .icon {
        font-size: 2rem;
        color: #0d6efd;
        margin-bottom: 5px;
      }

      .summary-card h4 {
        margin: 0;
        font-weight: 600;
      }

      /* Attendance Table */
      table {
        font-size: 0.9rem;
      }

      table thead {
        background-color: #0d6efd;
        color: #fff;
      }

      table tbody tr:hover {
        background-color: #f1f3f5;
      }

      .card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
      }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.css" />
  </head>

  <body>

    <div class="container py-4">
      <h4 class="title"><i class="fa fa-calendar-check me-2 text-primary"></i>Employee Attendance Dashboard</h4>

      <!-- Camera + Buttons -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="row g-3 align-items-center">
            <input type="hidden" id="LoginID" name="LoginID" value="<?php echo $loginid; ?>">
            <input type="hidden" id="Source" name="Source" value="Web">
            <input type="hidden" id="Latitude" name="Latitude">
            <input type="hidden" id="Longitude" name="Longitude">
            <input type="hidden" id="ProjectID" name="ProjectID" value="<?php echo $ProjectID ?? ''; ?>">
            <div class="col-md-6 text-center">
              <video id="camera" autoplay playsinline></video>
              <canvas id="canvas" style="display:none;"></canvas>
            </div>
            <div class="col-md-6 text-center">
              <h5 class="text-secondary mb-3" id="todayDate"></h5>
              <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                <button id="markInBtn" class="btn btn-success px-4" <?php echo $nextAction == 'MarkOut' ? 'disabled' : ''; ?>
                  onclick="submitAttendance('MarkIn')"><i class="fa fa-sign-in-alt me-1"></i>Mark In</button>
                <button id="markOutBtn" class="btn btn-danger px-4" <?php echo $nextAction == 'MarkIn' ? 'disabled' : ''; ?>
                  onclick="submitAttendance('MarkOut')"><i class="fa fa-sign-out-alt me-1"></i>Mark Out</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Loading Overlay -->
      <div id="loadingOverlay"
        style="display:none; position:fixed; inset:0; background:rgba(255,255,255,0.8); z-index:9999; align-items:center; justify-content:center; flex-direction:column;">
        <div class="spinner-border text-primary mb-3"></div>
        <div id="loadingText" class="fw-semibold">Please wait...</div>
      </div>

      <!-- Summary Cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card summary-card text-center py-3">
            <div class="card-body">
              <div class="icon"><i class="fa fa-calendar-days"></i></div>
              <h4 id="totalDays">0</h4>
              <p class="text-muted mb-0">Total Days</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card summary-card text-center py-3">
            <div class="card-body">
              <div class="icon text-success"><i class="fa fa-user-check"></i></div>
              <h4 id="presentDays">0</h4>
              <p class="text-muted mb-0">Present Days</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card summary-card text-center py-3">
            <div class="card-body">
              <div class="icon text-warning"><i class="fa fa-user-clock"></i></div>
              <h4 id="missDays">0</h4>
              <p class="text-muted mb-0">MIS Days (Marked In but No Out)</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Attendance Table -->
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="mb-3"><i class="fa fa-list-check me-2 text-primary"></i>Current Month Attendance</h5>
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mt-3">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Mark In</th>
                  <th>Mark Out</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="attendanceTableBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay"
      style="display:none; position:fixed; inset:0; background:rgba(255,255,255,0.8); z-index:9999; align-items:center; justify-content:center; flex-direction:column;">
      <div class="spinner-border text-primary mb-3"></div>
      <div id="loadingText" class="fw-semibold">Please wait...</div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>

    <script>
      // ==============================
      // ✅ 1. GLOBAL LOADING HANDLER
      // ==============================
      function showLoading(show, text = "Please wait...") {
        const overlay = document.getElementById("loadingOverlay");
        document.getElementById("loadingText").innerText = text;
        overlay.style.display = show ? "flex" : "none";
      }

      function detectmobile() {
        var agnt = navigator.userAgent;
        var isMobile = {
          Android: function () {
            return agnt.match(/Android/i);
          },
          BlackBerry: function () {
            return agnt.match(/BlackBerry/i);
          },
          iOS: function () {
            return agnt.match(/iPhone|iPad|iPod/i);
          },
          Opera: function () {
            return agnt.match(/Opera Mini/i);
          },
          Windows: function () {
            return agnt.match(/IEMobile/i) || navigator.userAgent.match(/WPDesktop/i);
          },
          any: function () {
            return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
          }
        };

        if (isMobile.any()) {
          document.getElementById("Source").value = "Mobile";
        }
        else {
          document.getElementById("Source").value = "Desktop";
        }
      }

      // ==============================
      // ✅ 2. CAMERA HANDLING FUNCTIONS
      // ==============================
      async function startCamera() {
        const video = document.getElementById("camera");
        try {
          const stream = await navigator.mediaDevices.getUserMedia({ video: true });
          video.srcObject = stream;
        } catch (err) {
          alert("Unable to access camera. Please allow camera permission.");
          console.error("Camera start error:", err);
        }
      }

      async function capturePhoto() {
        const video = document.getElementById("camera");
        const canvas = document.getElementById("canvas");

        try {
          const constraints = { video: { facingMode: "user" } };
          const stream = await navigator.mediaDevices.getUserMedia(constraints);
          video.srcObject = stream;

          // Wait for the video stream to load
          await new Promise((resolve) => (video.onloadedmetadata = resolve));

          // Small delay for auto-focus
          await new Promise((r) => setTimeout(r, 800));

          // Capture frame
          canvas.width = video.videoWidth;
          canvas.height = video.videoHeight;
          const ctx = canvas.getContext("2d");
          ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

          // Stop the camera
          stream.getTracks().forEach((track) => track.stop());

          // Convert to blob
          return new Promise((resolve, reject) => {
            canvas.toBlob(
              (blob) => (blob ? resolve(blob) : reject("Failed to capture image")),
              "image/jpeg",
              0.9
            );
          });
        } catch (err) {
          console.error("Camera capture error:", err);
          alert("Unable to access camera. Please allow camera permission.");
          throw err;
        }
      }

      // ==============================
      // ✅ 3. LOCATION HANDLER
      // ==============================
      function getLocation() {
        return new Promise((resolve, reject) => {
          if (!navigator.geolocation) return reject("Geolocation not supported.");

          navigator.geolocation.getCurrentPosition(
            (pos) => {
              document.getElementById("Latitude").value = pos.coords.latitude;
              document.getElementById("Longitude").value = pos.coords.longitude;
              resolve(true);
            },
            () => reject("Please enable location access to mark attendance."),
            { enableHighAccuracy: true, timeout: 10000 }
          );
        });
      }

      async function submitAttendance(actionType) {
        try {
          showLoading(true, "Getting location...");
          await getLocation();

          showLoading(true, "Capturing photo...");
          const photoBlob = await capturePhoto();

          showLoading(true, "Submitting attendance...");

          const formData = new FormData();
          formData.append("ActionType", actionType);
          formData.append("Photo", photoBlob, "attendance_photo.jpg");
          formData.append("LoginID", document.getElementById("LoginID").value);
          formData.append("Source", document.getElementById("Source").value);
          formData.append("Latitude", document.getElementById("Latitude").value);
          formData.append("Longitude", document.getElementById("Longitude").value);
          formData.append("ProjectID", document.getElementById("ProjectID").value);

          const response = await fetch("attendance_submit.php", {
            method: "POST",
            body: formData,
          });

          const result = await response.text();
          // console.log(result);

          iziToast.success({
            title: "OK",
            message: `${actionType} submitted successfully!`,
            timeout: 200,
            onClosing: function () {
              window.location.reload(); // ✅ Reload after toast closes
            },
          });

        } catch (err) {
          console.error("Attendance error:", err);
          iziToast.warning({
            title: "Warning",
            message: "Error while submitting attendance. Check console for details.",
          });
          // optional: reload after a delay if needed
          setTimeout(() => window.location.reload(), 200);
        } finally {
          showLoading(false);
        }
      }

      // ==============================
      // ✅ 5. DATE AND ATTENDANCE TABLE
      // ==============================
      const today = new Date();
      document.getElementById("todayDate").textContent = today.toLocaleDateString(
        "en-GB",
        { day: "2-digit", month: "long", year: "numeric" }
      );

      const totalDaysInMonth = new Date(
        today.getFullYear(),
        today.getMonth() + 1,
        0
      ).getDate();

      const attendanceData = [
        { date: `01-${today.getMonth() + 1}`, in: "09:05 AM", out: "06:10 PM" },
        { date: `02-${today.getMonth() + 1}`, in: "09:10 AM", out: "" },
        { date: `03-${today.getMonth() + 1}`, in: "09:00 AM", out: "06:00 PM" },
      ];

      async function renderAttendanceTable() {
        const tbody = document.getElementById("attendanceTableBody");
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">
      Loading attendance data...
    </td></tr>`;

        try {
          const loginID = document.getElementById("LoginID").value.trim();

          if (!loginID) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-danger text-center py-3">
          Please enter a valid Login ID.
        </td></tr>`;
            return;
          }

          const response = await fetch("get_attendance_data.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "LoginID=" + encodeURIComponent(loginID),
          });

          const text = await response.text();
          if (!text) throw new Error("Empty response from server");

          let result;
          try {
            result = JSON.parse(text);
          } catch (err) {
            console.error("Invalid JSON:", text);
            throw new Error("Invalid JSON format received from server.");
          }

          if (result.status !== "success" || !Array.isArray(result.data)) {
            throw new Error(result.message || "Invalid response from server.");
          }

          const attendanceData = result.data;
          tbody.innerHTML = "";

          if (attendanceData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">
          No attendance records found for this month.
        </td></tr>`;
            return;
          }

          // ✅ Render attendance rows
          attendanceData.forEach((row) => {
            const status =
              row.in && row.out
                ? '<span class="badge bg-success">Present</span>'
                : row.in && !row.out
                  ? '<span class="badge bg-warning text-dark">MIS</span>'
                  : '<span class="badge bg-secondary">Absent</span>';

            const tr = document.createElement("tr");
            tr.innerHTML = `
          <td>${row.date || "-"}</td>
          <td>${row.in || "-"}</td>
          <td>${row.out || "-"}</td>
          <td>${status}</td>
        `;
            tbody.appendChild(tr);
          });

          // ✅ Summary section
          const now = new Date();
          const totalDaysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
          const presentDays = attendanceData.filter((d) => d.in && d.out).length;
          const misDays = attendanceData.filter((d) => d.in && !d.out).length;

          document.getElementById("totalDays").textContent = totalDaysInMonth;
          document.getElementById("presentDays").textContent = presentDays;
          document.getElementById("missDays").textContent = misDays;

        } catch (error) {
          console.error("Error loading attendance:", error);
          tbody.innerHTML = `<tr><td colspan="4" class="text-danger text-center py-3">
        Failed to load attendance: ${error.message}
      </td></tr>`;
        }
      }

      // ==============================
      // ✅ 6. INITIALIZATION
      // ==============================
      window.addEventListener("load", () => {
        startCamera();
        detectmobile();
        getLocation();
        renderAttendanceTable();
      });
    </script>

  </body>

  </html>
<?php
} else {
  $loginreturn[] = array('status' => 'Error', 'message' => 'You are not registered with us, So Please do not try to login or please contact to web admin');
}

?>