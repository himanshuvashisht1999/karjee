<?php
ob_start();
require('../labourpayment/header.php');
$headerHtml = ob_get_clean();

// Fix relative paths for assets from labourpayment
$headerHtml = str_replace('href="assets/', 'href="../labourpayment/assets/', $headerHtml);
$headerHtml = str_replace('src="assets/', 'src="../labourpayment/assets/', $headerHtml);
$headerHtml = str_replace('href="logout.php', 'href="../labourpayment/logout.php', $headerHtml);

$customHead = '
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/labourdash.css?v=' . time() . '">
</head>
';

// Inject custom head elements before </head>
$headerHtml = str_replace('</head>', $customHead, $headerHtml);

echo $headerHtml;

$employeeID = isset($_GET['empid']) ? $_GET['empid'] : '';
if (!$employeeID) {
    echo "<div class='alert alert-danger m-4'>Employee ID is missing. <a href='../labourpayment/labourmaster.php?k=".$key."&mdid=".$mdid."'>Go Back</a></div>";
    require('../labourpayment/footer.php');
    exit;
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
if (strpos($_SERVER['REQUEST_URI'], '/krf_amanaccounting/') !== false) {
    $rooturl .= '/krf_amanaccounting/';
} else {
    $rooturl .= '/';
}
$routurl = $rooturl . 'labourpayment/';
?>

<div class="labourdash-wrapper" id="labourdash-app">

  <!-- Top Navigation Bar -->
  <div class="ldb-topbar">
    <div class="ldb-topbar-left">
      <a href="../labourpayment/labourmaster.php?k=<?php echo $key; ?>&mdid=<?php echo $mdid; ?>" class="ldb-backbtn" id="backToLabourList">
        <i class="ti ti-arrow-left"></i>
        <span>Labour Master</span>
      </a>
      <span class="ldb-breadcrumb-sep">/</span>
      <span class="ldb-breadcrumb-cur" id="topbar-name">Loading...</span>
    </div>
    <div class="ldb-topbar-right">
      <a href="../admin_dashboard.php?k=<?php echo $key; ?>&mdid=<?php echo $mdid; ?>" class="ldb-btn ldb-btn-outline">
        <i class="ti ti-home"></i> Admin Dashboard
      </a>
    </div>
  </div>

  <!-- Loading Skeleton -->
  <div id="ldb-loading" class="ldb-loading-container">
    <div class="ldb-loader-ring"></div>
    <p>Loading Labour Profile...</p>
  </div>

  <!-- Main Dashboard Content -->
  <div id="ldb-content" class="ldb-content" style="display:none;">

    <!-- Profile Hero Section -->
    <div class="ldb-hero-card" id="ldb-hero">
      <div class="ldb-hero-bg-gradient"></div>
      <div class="ldb-hero-body">
        <div class="ldb-hero-photo-wrap">
          <div class="ldb-hero-photo-ring" style="position: relative;">
            <img id="hero-photo" src="data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22256%22%20height%3D%22256%22%20viewBox%3D%220%200%20256%20256%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%234f46e5%22%20rx%3D%22128%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2254%25%22%20dominant-baseline%3D%22middle%22%20text-anchor%3D%22middle%22%20fill%3D%22%23ffffff%22%20font-family%3D%22system-ui%2C%20-apple-system%2C%20sans-serif%22%20font-size%3D%22108%22%20font-weight%3D%22700%22%3EL%3C%2Ftext%3E%3C%2Fsvg%3E" alt="Labour Photo" class="ldb-hero-photo">
            <label for="profilePhotoInput" class="ldb-photo-edit-overlay" style="position: absolute; bottom: 0; right: 0; background: var(--ldb-primary); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid white; box-shadow: var(--ldb-shadow-sm); margin-bottom: 0; transition: all 0.2s;">
              <i class="ti ti-camera" style="font-size: 16px;"></i>
            </label>
            <input type="file" id="profilePhotoInput" accept="image/*" style="display: none;">
          </div>
          <div class="ldb-hero-status-dot" id="hero-status-dot"></div>
        </div>
        <div class="ldb-hero-info">
          <h1 class="ldb-hero-name" id="hero-name">—</h1>
          <div class="ldb-hero-meta">
            <span class="ldb-badge ldb-badge-id" id="hero-empid">—</span>
            <span class="ldb-badge ldb-badge-skill" id="hero-skill">—</span>
          </div>
          <div class="ldb-hero-details-row">
            <span id="hero-phone"><i class="ti ti-phone"></i> —</span>
            <span id="hero-wage"><i class="ti ti-cash"></i> ₹ — /day</span>
            <span id="hero-location"><i class="ti ti-map-pin"></i> —</span>
          </div>
        </div>
        <div class="ldb-hero-actions">
          <button class="ldb-btn ldb-btn-light" id="downloadIDBtn">
            <i class="ti ti-id"></i> ID Card
          </button>
        </div>
      </div>
    </div>

    <!-- Tab Navigation -->
    <div class="ldb-tabs-container">
      <div class="ldb-tabs" id="ldb-tabs">
        <button class="ldb-tab active" data-tab="personal">
          <i class="ti ti-user-circle"></i> Personal Details
        </button>
        <button class="ldb-tab" data-tab="attendance">
          <i class="ti ti-calendar-check"></i> Attendance
        </button>
        <button class="ldb-tab" data-tab="payments">
          <i class="ti ti-cash"></i> Payments
        </button>
      </div>
    </div>

    <!-- Tab Content -->
    <div class="ldb-tab-content">

      <!-- ======================== PERSONAL DETAILS TAB ======================== -->
      <div class="ldb-tab-pane active" id="tab-personal">
        <div class="ldb-grid-2">

          <!-- Left Column -->
          <div style="display:flex; flex-direction:column; gap:20px;">
            
            <!-- Card 1: Basic Information -->
            <div class="ldb-card" id="card-basic">
              <div class="ldb-card-header ldb-accent-blue" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                  <i class="ti ti-user"></i> Basic Information
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                  <button class="ldb-card-edit-btn btn-edit-section" data-section="basic">
                    <i class="ti ti-pencil"></i> Edit
                  </button>
                  <button class="ldb-card-edit-btn btn-cancel-section" data-section="basic" style="display: none; background-color: #ef4444; color: white; border-color: #ef4444;">
                    <i class="ti ti-arrow-back-up"></i> Cancel
                  </button>
                </div>
              </div>
              <div class="ldb-card-body" id="basic-view">
                <div class="ldb-info-grid">
                  <div class="ldb-info-item">
                    <label>First Name</label>
                    <span id="pi-firstname">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>Middle Name</label>
                    <span id="pi-middlename">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>Last Name</label>
                    <span id="pi-lastname">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>Gender</label>
                    <span id="pi-gender">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>Date of Birth</label>
                    <span id="pi-dob">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>Marital Status</label>
                    <span id="pi-marital">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>Father Name</label>
                    <span id="pi-fathername">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>Mother Name</label>
                    <span id="pi-mothername">—</span>
                  </div>
                  <div class="ldb-info-item" id="pi-spousewrapper" style="display:none;">
                    <label id="pi-spouselabel">Spouse Name</label>
                    <span id="pi-spousename">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>ID Proof Type</label>
                    <span id="pi-idproof">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>ID Proof / Unique No</label>
                    <span id="pi-idproofno">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>ID Proof Document</label>
                    <span id="pi-idproofdoc">—</span>
                  </div>
                </div>
              </div>
              <div class="ldb-card-body d-none" id="basic-edit">
                <form class="form-edit-section" data-section="basic" enctype="multipart/form-data">
                  <div class="ldb-form-grid">
                    <div class="ldb-form-group">
                      <label>First Name <span class="req">*</span></label>
                      <input type="text" id="bef-firstname" class="ldb-input" required>
                    </div>
                    <div class="ldb-form-group">
                      <label>Middle Name</label>
                      <input type="text" id="bef-middlename" class="ldb-input">
                    </div>
                    <div class="ldb-form-group">
                      <label>Last Name</label>
                      <input type="text" id="bef-lastname" class="ldb-input">
                    </div>
                    <div class="ldb-form-group">
                      <label>Gender <span class="req">*</span></label>
                      <select id="bef-gender" class="ldb-input" required>
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                      </select>
                    </div>
                    <div class="ldb-form-group">
                      <label>Date of Birth</label>
                      <input type="date" id="bef-dob" class="ldb-input">
                      <div id="bef-dob-msg" style="font-size: 12px; margin-top: 4px;"></div>
                    </div>
                    <div class="ldb-form-group">
                      <label>Marital Status</label>
                      <select id="bef-marital" class="ldb-input">
                        <option value="">Select</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Divorced">Divorced</option>
                        <option value="Widowed">Widowed</option>
                      </select>
                    </div>
                    <div class="ldb-form-group">
                      <label>Father Name</label>
                      <input type="text" id="bef-fathername" class="ldb-input" placeholder="Enter father name">
                    </div>
                    <div class="ldb-form-group">
                      <label>Mother Name</label>
                      <input type="text" id="bef-mothername" class="ldb-input" placeholder="Enter mother name">
                    </div>
                    <div class="ldb-form-group" id="bef-spousewrapper" style="display:none;">
                      <label id="bef-spouselabel">Spouse Name</label>
                      <input type="text" id="bef-spousename" class="ldb-input" placeholder="Enter spouse name">
                    </div>
                    <div class="ldb-form-group">
                      <label>ID Proof Type</label>
                      <select id="bef-idproof" class="ldb-input">
                        <option value="">Select</option>
                        <option value="Aadhar Card">Aadhar Card</option>
                        <option value="PAN Card">PAN Card</option>
                        <option value="Voter ID">Voter ID</option>
                        <option value="Driving License">Driving License</option>
                        <option value="Passport">Passport</option>
                        <option value="Ration Card">Ration Card</option>
                        <option value="Bank Passbook">Bank Passbook</option>
                        <option value="Other">Other</option>
                      </select>
                    </div>
                    <div class="ldb-form-group">
                      <label id="bef-idproofno-label">Document Unique No / ID Proof No</label>
                      <input type="text" id="bef-idproofno" class="ldb-input" placeholder="Enter document unique no">
                      <div id="bef-idproofno-msg" style="font-size: 12px; margin-top: 4px;"></div>
                    </div>
                    <div class="ldb-form-group">
                      <label>Upload ID Proof Document</label>
                      <input type="file" id="bef-idproofdoc" class="ldb-input" accept="image/*,.pdf,.doc,.docx">
                      <div id="bef-idproofdoc-preview" style="font-size: 12px; margin-top: 4px;"></div>
                    </div>
                  </div>
                  <div class="ldb-form-actions">
                    <button type="submit" class="ldb-btn ldb-btn-primary">
                      <i class="ti ti-device-floppy"></i> Save
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <!-- Card 3: Contact Details -->
            <div class="ldb-card" id="card-contact">
              <div class="ldb-card-header ldb-accent-green" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                  <i class="ti ti-phone"></i> Contact Details
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                  <button class="ldb-card-edit-btn btn-edit-section" data-section="contact">
                    <i class="ti ti-pencil"></i> Edit
                  </button>
                  <button class="ldb-card-edit-btn btn-cancel-section" data-section="contact" style="display: none; background-color: #ef4444; color: white; border-color: #ef4444;">
                    <i class="ti ti-arrow-back-up"></i> Cancel
                  </button>
                </div>
              </div>
              <div class="ldb-card-body" id="contact-view">
                <div class="ldb-info-grid">
                  <div class="ldb-info-item">
                    <label>Mobile Number</label>
                    <span id="pi-mobile">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>Emergency Contact No</label>
                    <span id="pi-emergency">—</span>
                  </div>
                </div>
              </div>
              <div class="ldb-card-body d-none" id="contact-edit">
                <form class="form-edit-section" data-section="contact">
                  <div class="ldb-form-grid">
                    <div class="ldb-form-group">
                      <label>Mobile <span class="req">*</span></label>
                      <input type="text" id="coef-mobile" class="ldb-input" maxlength="10" placeholder="10-digit mobile number" required>
                      <div class="ldb-validation-msg" id="coef-mobile-feedback"></div>
                    </div>
                    <div class="ldb-form-group">
                      <label>Emergency Contact No</label>
                      <input type="text" id="coef-emergency" class="ldb-input" maxlength="10" placeholder="10-digit emergency contact number">
                      <div class="ldb-validation-msg" id="coef-emergency-feedback"></div>
                    </div>
                  </div>
                  <div class="ldb-form-actions">
                    <button type="submit" class="ldb-btn ldb-btn-primary">
                      <i class="ti ti-device-floppy"></i> Save
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <!-- Card 4: Address Details -->
            <div class="ldb-card" id="card-address">
              <div class="ldb-card-header ldb-accent-orange" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                  <i class="ti ti-map-pin"></i> Address Details
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                  <button class="ldb-card-edit-btn btn-edit-section" data-section="address">
                    <i class="ti ti-pencil"></i> Edit
                  </button>
                  <button class="ldb-card-edit-btn btn-cancel-section" data-section="address" style="display: none; background-color: #ef4444; color: white; border-color: #ef4444;">
                    <i class="ti ti-arrow-back-up"></i> Cancel
                  </button>
                </div>
              </div>
              <div class="ldb-card-body" id="address-view">
                <div class="ldb-info-grid">
                  <div class="ldb-info-item ldb-full-width">
                    <label>Current Address</label>
                    <span id="pi-address1">—</span>
                  </div>
                  <div class="ldb-info-item ldb-full-width">
                    <label>Permanent Address</label>
                    <span id="pi-address2">—</span>
                  </div>
                </div>
              </div>
              <div class="ldb-card-body d-none" id="address-edit">
                <form class="form-edit-section" data-section="address">
                  
                  <!-- Address Helper Generator Box -->
                  <div class="p-3 mb-3 rounded" style="background-color: #fff7ed; border: 1.5px dashed #fdba74;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <i class="ti ti-map-pin" style="font-size: 1.15rem; color: #ea580c;"></i>
                      <span style="font-weight: 700; font-size: 12.5px; color: #9a3412;">Address Generator / Auto-fill Helper</span>
                    </div>
                    <div class="ldb-form-grid">
                      <div class="ldb-form-group">
                        <label>State Name <span class="text-danger">*</span></label>
                        <select id="adef-state" class="ldb-input select2">
                          <option value="">Select State</option>
                        </select>
                      </div>
                      <div class="ldb-form-group">
                        <label>District Name <span class="text-danger">*</span></label>
                        <select id="adef-district" class="ldb-input select2" disabled>
                          <option value="">Select State First</option>
                        </select>
                      </div>
                      <div class="ldb-form-group">
                        <label>Tahseel / Block Name</label>
                        <input type="text" id="adef-tehsil" class="ldb-input" placeholder="Enter Tahseel / Block Name">
                      </div>
                      <div class="ldb-form-group">
                        <label>City / Village Name <span class="text-danger">*</span></label>
                        <input type="text" id="adef-cityvillage" class="ldb-input" placeholder="Enter City / Village Name">
                      </div>
                    </div>
                  </div>

                  <div class="ldb-form-grid">
                    <div class="ldb-form-group ldb-full-width">
                      <label>Current Address <span class="text-muted" style="font-weight: normal; font-size: 11px;">(Auto-filled: City/Village, Tahseel/Block, District, State)</span></label>
                      <textarea id="adef-current" class="ldb-input" rows="2" placeholder="Enter or auto-generate current address"></textarea>
                    </div>
                    <div class="ldb-form-group ldb-full-width">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <label style="margin-bottom: 0;">Permanent Address</label>
                        <label class="form-check-label d-inline-flex align-items-center gap-1 text-muted" style="font-size: 11.5px; cursor: pointer; text-transform: none; letter-spacing: 0; font-weight: 600;">
                          <input type="checkbox" id="adef-same-as-current" class="form-check-input" style="cursor: pointer; margin-top: 0;"> Same as Current Address
                        </label>
                      </div>
                      <textarea id="adef-permanent" class="ldb-input" rows="2" placeholder="Enter permanent address"></textarea>
                    </div>
                  </div>
                  <div class="ldb-form-actions">
                    <button type="submit" class="ldb-btn ldb-btn-primary">
                      <i class="ti ti-device-floppy"></i> Save
                    </button>
                  </div>
                </form>
              </div>
            </div>

          </div>

          <!-- Right Column -->
          <div style="display:flex; flex-direction:column; gap:20px;">

            <!-- Card 2: Bank Details -->
            <div class="ldb-card" id="card-bank">
              <div class="ldb-card-header ldb-accent-teal" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                  <i class="ti ti-building-bank"></i> Bank Details
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                  <button class="ldb-card-edit-btn btn-edit-section" data-section="bank">
                    <i class="ti ti-pencil"></i> Edit
                  </button>
                  <button class="ldb-card-edit-btn btn-cancel-section" data-section="bank" style="display: none; background-color: #ef4444; color: white; border-color: #ef4444;">
                    <i class="ti ti-arrow-back-up"></i> Cancel
                  </button>
                </div>
              </div>
              <div class="ldb-card-body" id="bank-view">
                <div class="ldb-info-grid">
                  <div class="ldb-info-item">
                    <label>Bank Name</label>
                    <span id="pi-bankname">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>Account Number</label>
                    <span id="pi-accountnumber">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>IFSC Code</label>
                    <span id="pi-ifsccode">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>Bank Reference Document</label>
                    <span id="pi-bankdoc">—</span>
                  </div>
                </div>
              </div>
              <div class="ldb-card-body d-none" id="bank-edit">
                <form class="form-edit-section" data-section="bank">
                  <div class="ldb-form-grid">
                    <div class="ldb-form-group">
                      <label>Bank Name <span class="text-danger">*</span></label>
                      <div class="ldb-autocomplete-wrapper">
                        <input type="text" id="bkef-bankname" class="ldb-input" required placeholder="e.g. STATE BANK OF INDIA" style="text-transform: uppercase;" autocomplete="off">
                        <div id="bkef-bank-suggestions" class="ldb-autocomplete-list"></div>
                      </div>
                      <div class="ldb-validation-msg" id="bkef-bankname-feedback"></div>
                    </div>
                    <div class="ldb-form-group">
                      <label>Account Number <span class="text-danger">*</span></label>
                      <input type="text" id="bkef-accountnumber" class="ldb-input" maxlength="18" required placeholder="Enter 9 to 18 digit account number" autocomplete="off">
                      <div class="ldb-validation-msg" id="bkef-accountnumber-feedback"></div>
                    </div>
                    <div class="ldb-form-group">
                      <label>IFSC Code <span class="text-danger">*</span></label>
                      <input type="text" id="bkef-ifsccode" class="ldb-input" maxlength="11" required placeholder="e.g. SBIN0001234" style="text-transform: uppercase;" autocomplete="off">
                      <div class="ldb-validation-msg" id="bkef-ifsccode-feedback"></div>
                    </div>
                    <div class="ldb-form-group">
                      <label>Upload Bank Document <span class="text-muted" style="font-size:11px;">(Passbook / Cheque / Statement)</span></label>
                      <input type="file" id="bkef-bankdoc" class="ldb-input" accept="image/*,application/pdf">
                      <div id="bkef-bankdoc-preview" class="mt-2"></div>
                    </div>
                  </div>
                  <div class="ldb-form-actions">
                    <button type="submit" class="ldb-btn ldb-btn-primary">
                      <i class="ti ti-device-floppy"></i> Save
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <!-- Card 5: Skills & Assignment -->
            <div class="ldb-card" id="card-skills">
              <div class="ldb-card-header ldb-accent-blue" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                  <i class="ti ti-bulb"></i> Skills & Assignment
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                  <button class="ldb-card-edit-btn btn-edit-section" data-section="skills">
                    <i class="ti ti-pencil"></i> Edit
                  </button>
                  <button class="ldb-card-edit-btn btn-cancel-section" data-section="skills" style="display: none; background-color: #ef4444; color: white; border-color: #ef4444;">
                    <i class="ti ti-arrow-back-up"></i> Cancel
                  </button>
                </div>
              </div>
              <div class="ldb-card-body" id="skills-view">
                <div class="ldb-info-grid">
                  <div class="ldb-info-item">
                    <label>Grafting Skill</label>
                    <span id="pi-skill">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>Working Location</label>
                    <span id="pi-location">—</span>
                  </div>
                  <div class="ldb-info-item">
                    <label>Per Day Wage</label>
                    <span id="pi-wage" class="ldb-wage-highlight">₹ —</span>
                  </div>
                </div>
              </div>
              <div class="ldb-card-body d-none" id="skills-edit">
                <form class="form-edit-section" data-section="skills">
                  <div class="ldb-form-grid">
                    <div class="ldb-form-group">
                      <label>Grafting Skill</label>
                      <select id="skef-grafting" class="ldb-input">
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                      </select>
                    </div>
                    <div class="ldb-form-group">
                      <label>Working Location</label>
                      <select id="skef-location" class="ldb-input"></select>
                    </div>
                    <div class="ldb-form-group">
                      <label>Per Day Wage (₹)</label>
                      <input type="number" id="skef-wage" class="ldb-input">
                    </div>
                  </div>
                  <div class="ldb-form-actions">
                    <button type="submit" class="ldb-btn ldb-btn-primary">
                      <i class="ti ti-device-floppy"></i> Save
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <!-- Card 6: Reference Persons -->
            <div class="ldb-card" id="card-refs">
              <div class="ldb-card-header ldb-accent-purple" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                  <i class="ti ti-users"></i> Reference Persons
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                  <button class="ldb-card-edit-btn btn-edit-section" data-section="refs">
                    <i class="ti ti-pencil"></i> Edit
                  </button>
                  <button class="ldb-card-edit-btn btn-cancel-section" data-section="refs" style="display: none; background-color: #ef4444; color: white; border-color: #ef4444;">
                    <i class="ti ti-arrow-back-up"></i> Cancel
                  </button>
                </div>
              </div>
              <div class="ldb-card-body" id="refs-view">
                <div id="references-container">
                  <div class="ldb-empty-state">
                    <i class="ti ti-user-question"></i>
                    <p>No references loaded.</p>
                  </div>
                </div>
              </div>
              <div class="ldb-card-body d-none" id="refs-edit">
                <form class="form-edit-section" data-section="refs">
                  <div id="refs-edit-inputs">
                    <!-- Dynamic inputs populated via JS -->
                  </div>
                  <div class="ldb-form-actions">
                    <button type="submit" class="ldb-btn ldb-btn-primary">
                      <i class="ti ti-device-floppy"></i> Save
                    </button>
                  </div>
                </form>
              </div>
            </div>

          </div>

        </div>
      </div>

      <!-- ======================== ATTENDANCE TAB ======================== -->
      <div class="ldb-tab-pane" id="tab-attendance">

        <!-- Month Selector Strip -->
        <div class="ldb-month-strip-wrap">
          <div class="ldb-month-strip-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div style="display:flex; align-items:center; gap:12px;">
              <h3 style="margin:0;"><i class="ti ti-calendar-month"></i> Monthly Attendance</h3>
              <button class="btn btn-success btn-sm" id="markManualAttBtn" style="background-color:#10b981; color:white; border:none; border-radius:6px; font-weight:600; font-size:12px; padding:6px 12px; display:inline-flex; align-items:center; gap:4px; transition: all 0.2s;">
                <i class="ti ti-calendar-plus" style="font-size:0.95rem;"></i> Mark Attendance
              </button>
            </div>
            <div class="ldb-year-nav">
              <button class="ldb-btn-icon" id="prevYearBtn"><i class="ti ti-chevron-left"></i></button>
              <span id="currentYearLabel" class="ldb-year-label">2025</span>
              <button class="ldb-btn-icon" id="nextYearBtn"><i class="ti ti-chevron-right"></i></button>
            </div>
          </div>
          <div class="ldb-month-strip" id="monthStrip">
            <!-- Month buttons generated by JS -->
          </div>
        </div>

        <!-- Attendance Summary for selected month -->
        <div id="att-month-detail" class="ldb-att-detail" style="display:none;">
          <div class="ldb-att-stats-row">
            <div class="ldb-att-stat-card ldb-stat-blue" id="card-mark-in" data-filter="mark-in">
              <div class="ldb-stat-icon"><i class="ti ti-login"></i></div>
              <div class="ldb-stat-info">
                <span class="ldb-stat-label">Mark-In</span>
                <span class="ldb-stat-value" id="att-mark-in">—</span>
              </div>
            </div>
            <div class="ldb-att-stat-card ldb-stat-green" id="card-present" data-filter="present">
              <div class="ldb-stat-icon"><i class="ti ti-calendar-check"></i></div>
              <div class="ldb-stat-info">
                <span class="ldb-stat-label">Present Days</span>
                <span class="ldb-stat-value" id="att-present">—</span>
                <small class="text-muted" id="att-present-breakdown" style="font-size:11px; font-weight:600; display:block; margin-top:2px;">—</small>
              </div>
            </div>
            <div class="ldb-att-stat-card ldb-stat-orange" id="card-missing" data-filter="missing" style="background-color:#fee2e2; border-color:#fca5a5;">
              <div class="ldb-stat-icon" style="color:#ef4444; background-color:#fef2f2;"><i class="ti ti-alert-circle"></i></div>
              <div class="ldb-stat-info">
                <span class="ldb-stat-label" style="color:#b91c1c;">Mark-Out Missing</span>
                <span class="ldb-stat-value" id="att-missing" style="color:#b91c1c;">—</span>
              </div>
            </div>
            <div class="ldb-att-stat-card ldb-stat-green" id="card-early" data-filter="early" style="background-color:#fffbeb; border-color:#fde68a;">
              <div class="ldb-stat-icon" style="color:#d97706; background-color:#fffdf5;"><i class="ti ti-clock-off"></i></div>
              <div class="ldb-stat-info">
                <span class="ldb-stat-label" style="color:#b45309;">Short Time (&le; 2h)</span>
                <span class="ldb-stat-value" id="att-early" style="color:#b45309;">—</span>
                <small style="font-size:11px; font-weight:600; color:#b45309; display:block; margin-top:2px;">Absent</small>
              </div>
            </div>
            <div class="ldb-att-stat-card ldb-stat-purple" id="card-payable" data-filter="payable">
              <div class="ldb-stat-icon"><i class="ti ti-coin-rupee"></i></div>
              <div class="ldb-stat-info">
                <span class="ldb-stat-label">Total Payable Days</span>
                <span class="ldb-stat-value" id="att-net">—</span>
                <small id="att-payable-breakdown" style="font-size:11px; color:#7c3aed; font-weight:600; display:block; margin-top:2px;">—</small>
              </div>
            </div>
          </div>

          <!-- Calculation Rules Alert Banner -->
          <div class="alert alert-info d-flex align-items-center my-3 py-2 px-3 border-info shadow-sm" style="border-radius: 8px; background-color: #eff6ff; color: #1e40af; border: 1px solid #93c5fd;">
            <i class="ti ti-info-circle me-2 fs-18" style="color: #2563eb; font-size: 18px; flex-shrink: 0;"></i>
            <span class="fs-13" style="font-size: 13px;"><strong>Calculation Rules:</strong> Half-Day (HD): Working Hours &gt; 2 hrs &amp; &lt; 6 hrs (0.5 day) | Full-Day (P): Working Hours &ge; 6 hrs (1.0 day) | Absent (A): Working Hours &le; 2 hrs</span>
          </div>

          <!-- Attendance Records Table -->
          <div class="ldb-card mt-3">
            <div class="ldb-card-header ldb-accent-teal" style="display:flex; justify-content:space-between; align-items:center;">
              <div>
                <i class="ti ti-list"></i> Attendance Records
                <span class="ldb-badge-sm" id="att-period-label"></span>
              </div>
              <button class="btn btn-sm" id="exportAttExcelBtn" style="font-size: 11.5px; padding: 4px 10px; border-radius: 6px; border: 1px solid #10b981; color: #10b981; background-color: transparent; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 4px;">
                <i class="ti ti-file-spreadsheet" style="font-size: 1rem;"></i> Export Excel
              </button>
            </div>
            <div class="ldb-card-body p-0">
              <div class="ldb-table-wrap">
                <table class="ldb-table" id="attRecordsTable">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Main Check-In</th>
                      <th>Main Check-Out</th>
                      <th>Main Hrs</th>
                      <th>QR Check-In</th>
                      <th>QR Check-Out</th>
                      <th>QR Hrs</th>
                      <th class="text-center">Status</th>
                    </tr>
                  </thead>
                  <tbody id="att-table-body">
                    <tr><td colspan="8" class="ldb-empty-row">Select a month to view records</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div id="att-empty" class="ldb-empty-full">
          <div class="ldb-empty-icon-wrap">
            <i class="ti ti-calendar-off"></i>
          </div>
          <h4>Select a Month</h4>
          <p>Click on any month above to view attendance records.</p>
        </div>

      </div>

      <!-- ======================== PAYMENTS TAB ======================== -->
      <div class="ldb-tab-pane" id="tab-payments">

        <!-- Date Range Filter Strip -->
        <div class="ldb-pay-filter-strip">
          <div class="ldb-pay-filter-header">
            <h3><i class="ti ti-receipt"></i> Payment History</h3>
          </div>
          <div class="ldb-pay-filter-controls">
            <div class="ldb-filter-group">
              <label><i class="ti ti-calendar-event"></i> From Date</label>
              <input type="date" id="payDateFrom" class="ldb-input-sm">
            </div>
            <div class="ldb-filter-group">
              <label><i class="ti ti-calendar-event"></i> To Date</label>
              <input type="date" id="payDateTo" class="ldb-input-sm">
            </div>
            <button class="ldb-btn ldb-btn-primary" id="loadPaymentsBtn">
              <i class="ti ti-search"></i> Load Payments
            </button>
            <button class="ldb-btn ldb-btn-outline" id="clearPayFilterBtn">
              <i class="ti ti-x"></i> Clear
            </button>
          </div>
        </div>

        <!-- Payment Summary Strip -->
        <div id="pay-summary-strip" class="ldb-pay-summary-strip" style="display:none;">
          <div class="ldb-pay-summary-card ldb-pay-blue">
            <i class="ti ti-calendar-stats"></i>
            <div>
              <span class="ldb-ps-label">Total Records</span>
              <span class="ldb-ps-value" id="pay-total-records">0</span>
            </div>
          </div>
          <div class="ldb-pay-summary-card ldb-pay-green">
            <i class="ti ti-circle-check"></i>
            <div>
              <span class="ldb-ps-label">Paid Amount</span>
              <span class="ldb-ps-value" id="pay-total-paid">₹ 0</span>
            </div>
          </div>
          <div class="ldb-pay-summary-card ldb-pay-orange">
            <i class="ti ti-clock"></i>
            <div>
              <span class="ldb-ps-label">Pending Amount</span>
              <span class="ldb-ps-value" id="pay-total-pending">₹ 0</span>
            </div>
          </div>
          <div class="ldb-pay-summary-card ldb-pay-purple">
            <i class="ti ti-coin-rupee"></i>
            <div>
              <span class="ldb-ps-label">Total Worked Days</span>
              <span class="ldb-ps-value" id="pay-total-days">0</span>
            </div>
          </div>
        </div>

        <!-- Payment Records -->
        <div class="ldb-card mt-4">
          <div class="ldb-card-header ldb-accent-green">
            <i class="ti ti-cash-register"></i> Payment Records
          </div>
          <div class="ldb-card-body p-0">
            <div class="ldb-table-wrap">
              <table class="ldb-table" id="payRecordsTable">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Period</th>
                    <th class="text-center">Days</th>
                    <th class="text-end">Wage/Day</th>
                    <th class="text-end">Basic Amt</th>
                    <th class="text-end">Incentive</th>
                    <th class="text-end">Overtime</th>
                    <th class="text-end">Net Pay</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Payment Date</th>
                  </tr>
                </thead>
                <tbody id="pay-table-body">
                  <tr><td colspan="10" class="ldb-empty-row">Use filters above to load payment records</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
      <!-- end tab payments -->

    </div><!-- end ldb-tab-content -->
  </div><!-- end ldb-content -->
</div><!-- end labourdash-wrapper -->

<!-- Manual Attendance Modal -->
<div class="modal fade" id="markManualAttModal" tabindex="-1" aria-labelledby="markManualAttModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 600px; width: 95%;">
    <div class="modal-content" style="border-radius:14px; border:none; box-shadow: 0 12px 36px rgba(0,0,0,0.18);">
      <div class="modal-header" style="background:linear-gradient(135deg, #10b981, #059669); color:white; border-top-left-radius:14px; border-top-right-radius:14px; padding: 18px 24px;">
        <h5 class="modal-title d-flex align-items-center gap-2" id="markManualAttModalLabel" style="font-weight:700; font-family:'Outfit',sans-serif; font-size:1.2rem;">
          <i class="ti ti-calendar-plus" style="font-size:1.35rem;"></i> Mark Manual Attendance
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="box-shadow:none;"></button>
      </div>
      <form id="markManualAttForm">
        <div class="modal-body" style="padding:26px 24px;">
          <div class="mb-3">
            <label for="att-date-input" class="form-label" style="font-weight:600; color:#374151; font-size:13.5px;">Attendance Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="att-date-input" required style="border-radius:8px; border:1px solid #d1d5db; padding: 10px 14px; font-size:14.5px;">
          </div>
          <div class="row">
            <div class="col-6 mb-2">
              <label for="att-in-time" class="form-label d-flex align-items-center justify-content-between" style="font-weight:600; color:#374151; font-size:13.5px; margin-bottom:6px;">
                <span>Check-In Time <span class="text-danger">*</span></span>
                <span class="badge bg-light text-secondary border" style="font-size:10.5px; font-weight:600;">24h Format</span>
              </label>
              <input type="time" class="form-control" id="att-in-time" value="09:00" step="60" required style="border-radius:8px; border:1px solid #d1d5db; padding: 10px 14px; font-size:15px; font-weight:600;">
            </div>
            <div class="col-6 mb-2">
              <label for="att-out-time" class="form-label d-flex align-items-center justify-content-between" style="font-weight:600; color:#374151; font-size:13.5px; margin-bottom:6px;">
                <span>Check-Out Time <span class="text-danger">*</span></span>
                <span class="badge bg-light text-secondary border" style="font-size:10.5px; font-weight:600;">24h Format</span>
              </label>
              <input type="time" class="form-control" id="att-out-time" value="18:00" step="60" required style="border-radius:8px; border:1px solid #d1d5db; padding: 10px 14px; font-size:15px; font-weight:600;">
            </div>
          </div>
          
          <!-- Live Calculated Duration Box (Shows minus value when negative) -->
          <div id="att-time-calc-alert" class="mt-3 p-2 px-3 rounded d-flex align-items-center justify-content-between" style="font-size:13px; display:none; background-color:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; border-radius:8px;">
            <div class="d-flex align-items-center gap-2">
              <i class="ti ti-clock-hour-4 text-primary" style="font-size:17px;"></i>
              <span id="att-calc-text">Working Hours: <strong id="att-calc-duration">0h 0m</strong></span>
            </div>
            <span id="att-calc-badge" class="badge bg-success" style="font-size:11.5px; padding:4px 10px;">Full Day</span>
          </div>

          <!-- Error Alert if Check-Out <= Check-In -->
          <div id="att-time-error-alert" class="mt-2 p-2 px-3 rounded d-flex align-items-center gap-2 text-danger" style="font-size:12.5px; display:none; background-color:#fef2f2; border:1px solid #fecaca; border-radius:8px;">
            <i class="ti ti-alert-triangle text-danger" style="font-size:17px; flex-shrink:0;"></i>
            <span><strong>Check-Out time must be greater than Check-In time.</strong></span>
          </div>

          <!-- 24-Hour Format Guidance Note -->
          <div class="alert alert-warning d-flex align-items-start gap-2 mt-3 mb-0 p-2 px-3 border-0" style="background-color:#fffbeb; border-left: 4px solid #f59e0b !important; border-radius: 8px; font-size:12.5px; color:#92400e;">
            <i class="ti ti-info-circle fs-5 mt-1" style="color:#d97706; flex-shrink:0;"></i>
            <div>
              <strong>Note:</strong> Mark-In and Mark-Out time must be in <strong>24-Hour Format (00:00 to 23:59)</strong>.<br>
              <span style="font-size:11.5px; color:#b45309;">(e.g., for afternoon <strong>03:46 PM</strong> enter <strong>15:46</strong>, and for evening <strong>06:30 PM</strong> enter <strong>18:30</strong>.)</span>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="background-color:#f9fafb; border-bottom-left-radius:14px; border-bottom-right-radius:14px; padding: 14px 24px; border-top:1px solid #f3f4f6; display:flex; justify-content:flex-end; gap:10px;">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:8px; font-weight:600; font-size:13.5px; padding:9px 18px; border: 1px solid #e5e7eb;">Cancel</button>
          <button type="submit" class="btn btn-success" id="submitManualAttBtn" style="border-radius:8px; font-weight:600; font-size:13.5px; padding:9px 22px; background-color:#10b981; border:none; display:inline-flex; align-items:center; gap:6px;">
            <i class="ti ti-device-floppy"></i> Save Attendance
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Document Preview Modal -->
<div class="modal fade" id="docPreviewModal" tabindex="-1" aria-labelledby="docPreviewModalLabel" aria-hidden="true" style="z-index: 1065;">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:12px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
      <div class="modal-header" style="background:linear-gradient(135deg, #4f46e5, #3730a3); color:white; border-top-left-radius:12px; border-top-right-radius:12px; padding: 16px 20px;">
        <h5 class="modal-title d-flex align-items-center gap-2" id="docPreviewModalLabel" style="font-weight:700; font-family:'Outfit',sans-serif; font-size:1.15rem; color:#ffffff;">
          <i class="ti ti-file-search" style="font-size:1.3rem;"></i> Document Preview
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="box-shadow:none;"></button>
      </div>
      <div class="modal-body text-center" id="docPreviewModalBody" style="padding:20px; min-height: 350px; display:flex; align-items:center; justify-content:center; background-color:#f8fafc;">
        <!-- Dynamic Content -->
      </div>
      <div class="modal-footer" style="background-color:#f9fafb; border-bottom-left-radius:12px; border-bottom-right-radius:12px; padding: 12px 20px; border-top:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center;">
        <a id="docPreviewDownloadBtn" href="#" target="_blank" class="btn btn-outline-primary btn-sm" style="border-radius:8px; font-weight:600;"><i class="ti ti-external-link me-1"></i> Open in New Tab</a>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal" style="border-radius:8px; font-weight:600;">Close</button>
      </div>
    </div>
  </div>
</div>

<?php 
ob_start();
require('../labourpayment/footer.php'); 
$footerHtml = ob_get_clean();
$footerHtml = str_replace('src="assets/', 'src="../labourpayment/assets/', $footerHtml);
echo $footerHtml;
?>

<!-- Dashboard-specific scripts -->
<script src="js/labourdash.js?v=<?php echo time(); ?>"></script>
<script>
  var rooturl = "<?php echo $rooturl; ?>";
  var routurl = "<?php echo $routurl; ?>";
  var key = "<?php echo $key; ?>";
  var mdid = "<?php echo isset($mdid) ? $mdid : ''; ?>";
  var groupid = "<?php echo isset($groupid) ? $groupid : ''; ?>";
  var companyid = "<?php echo isset($companyid) ? $companyid : ''; ?>";
  var subcompanyid = "<?php echo $subcompanyid; ?>";
  var projectid = "<?php echo $projectid; ?>";
  var loginname = "<?php echo $loginname; ?>";
  var currentEmployeeID = "<?php echo htmlspecialchars($employeeID); ?>";
  
  $(document).ready(function() {
    initLabourDash(currentEmployeeID);
  });
</script>
