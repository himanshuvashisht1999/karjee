/* ===================================================
   LABOURDASH - Main JS Logic
   All functions: profile load, tabs, attendance,
   payments, edit personal details
   =================================================== */

// ---- Globals ----
// ---- Globals ----
/**
 * Generates an SVG Data URI avatar based on first letter of first name and last name.
 */
function generateSelfAvatarJS(firstName, lastName, bgColor, textColor, size) {
    bgColor   = bgColor   || '#1b7a48';
    textColor = textColor || '#ffffff';
    size      = size      || 128;

    var fn = (firstName || '').trim();
    var ln = (lastName || '').trim();

    if (!ln && fn.indexOf(' ') !== -1) {
        var parts = fn.split(/\s+/).filter(Boolean);
        fn = parts[0] || '';
        ln = parts.length > 1 ? parts[parts.length - 1] : '';
    }

    var firstInitial = fn ? fn.charAt(0).toUpperCase() : '';
    var lastInitial  = ln ? ln.charAt(0).toUpperCase() : '';
    var initials     = (firstInitial + lastInitial) || 'L';

    var fontSize = Math.round(size * 0.42);
    var rx       = Math.round(size / 2);

    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '" viewBox="0 0 ' + size + ' ' + size + '">' +
              '<rect width="100%" height="100%" fill="' + bgColor + '" rx="' + rx + '"/>' +
              '<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="' + textColor + '" font-family="system-ui, -apple-system, sans-serif" font-size="' + fontSize + '" font-weight="700">' + initials + '</text>' +
              '</svg>';

    return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
}

/**
 * Resolves relative or absolute path of documents/photos to valid web URLs.
 */
function resolveDocUrl(path) {
  if (!path || typeof path !== 'string' || path.trim() === '' || path === '—') return '';
  path = path.trim();
  if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:')) {
    return path;
  }
  var cleanPath = path.replace(/^(\.\.\/|\.\/)+/, '');
  if (typeof rooturl !== 'undefined' && rooturl && rooturl.trim() !== '') {
    return rooturl.replace(/\/+$/, '') + '/' + cleanPath;
  }
  return '../' + cleanPath;
}

var labourData        = {};
var labourRefs        = [];
var labourBank        = {};
var locOptionsCache   = "";
var currentYear       = new Date().getFullYear();
var selectedMonth     = null;
var attendanceSummary = {}; // key: "YYYY-M" => {days, gross, net}
var currentMonthlyRecords = [];
var currentAttFilter      = "all";

var MONTHS = [
  "Jan","Feb","Mar","Apr","May","Jun",
  "Jul","Aug","Sep","Oct","Nov","Dec"
];

/* ====================================================
   INIT
   ==================================================== */
function initLabourDash(empId) {
  loadLabourProfile(empId);
  buildMonthStrip();
  initTabs();
  initPayFilterControls(empId);
  setDefaultPayDates();
  initManualAttendance(empId);
  initSectionToggles();
  initSectionEdits();
  initProfilePhotoUpload();
}

/* ====================================================
   LOAD LABOUR PROFILE (personal + references)
   ==================================================== */
function loadLabourProfile(empId) {
  $("#ldb-loading").show();
  $("#ldb-content").hide();

  $.post(routurl + "modal/hrm_laboursalarymodal.php?action=GetLabourFullDetails", {
    employeeID   : empId,
    companyid    : companyid,
    subcompanyid : subcompanyid,
    projectid    : projectid
  }, function(res) {
    try {
      var data = (typeof res === "string") ? JSON.parse(res) : res;
      if (data.status === "Success") {
        labourData = data.data;
        labourRefs = data.references || [];
        labourBank = data.bank || {};
        renderHero(labourData, labourRefs);
        renderPersonalTab(labourData, labourRefs, labourBank);
        loadLocationsDropdown();

        // Also load attendance summary (dots)
        loadAttendanceSummaryForYear(empId, currentYear);

        $("#ldb-loading").hide();
        $("#ldb-content").fadeIn(300);
      } else {
        showError("Could not load labour profile: " + (data.message || "Unknown error"));
      }
    } catch(e) {
      showError("Error parsing labour data.");
    }
  }).fail(function() {
    showError("Server error loading labour profile.");
  });
}

/* ====================================================
   RENDER HERO SECTION
   ==================================================== */
function renderHero(labour, refs) {
  var name  = labour.FullName || "—";
  var empId = labour.EmployeeID || "—";
  var photo = labour.profile_photo
    ? resolveDocUrl(labour.profile_photo)
    : generateSelfAvatarJS(labour.FirstName || labour.FullName, labour.LastName, '#4f46e5', '#ffffff', 256);

  $("#hero-photo").attr("src", photo).attr("alt", name);
  $("#topbar-name").text(name);
  $("#hero-name").text(`${labour.FirstName} ${labour.MiddleName} ${labour.LastName}`.trim() || "Unknown");
  $("#hero-empid").text(`ID: ${labour.EmployeeID}`);
  $("#hero-skill").text(`Skill: ${(labour.grafting_skill === 'yes' || labour.graftingskill === 'yes') ? 'Grafting' : 'Regular'}`);
  $("#hero-phone").html(`<i class="ti ti-phone"></i> ${labour.mobileNo || labour.MobileNo || '—'}`);
  $("#hero-wage").html(`<i class="ti ti-cash"></i> ₹ ${labour.PayPerDayAmount || labour.PerDayPayment || '0'} /day`);
  $("#hero-location").html(`<i class="ti ti-map-pin"></i> ${labour.WorkingLocationName || labour.WorkLocation || labour.workingLocation || '—'}`);

  // ID Card button
  $("#downloadIDBtn").off("click").on("click", function() {
    downloadIDCard(labour.EmployeeID);
  });
}

/* ====================================================
   RENDER PERSONAL TAB
   ==================================================== */
function formatDateDisplay(dateStr) {
  if (!dateStr || dateStr === '0000-00-00' || dateStr === '—') return '—';
  var d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  var day = String(d.getDate()).padStart(2, '0');
  var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var month = months[d.getMonth()];
  var year = d.getFullYear();
  return `${day}-${month}-${year}`;
}

function renderPersonalTab(labour, refs, bank) {
  // View Mode - Basic Card
  $("#pi-firstname").text(labour.FirstName || "—");
  $("#pi-middlename").text(labour.MiddleName || "—");
  $("#pi-lastname").text(labour.LastName || "—");
  $("#pi-gender").text(labour.Gender || "—");
  $("#pi-dob").text(labour.DateOfBirth ? formatDateDisplay(labour.DateOfBirth) : "—");
  $("#pi-marital").text(labour.MaritalStatus || "—");
  
  var idProofTypeVal = labour.idprooftype || labour.IdProofType || "—";
  $("#pi-idproof").text(idProofTypeVal);

  var idNoVal = "—";
  if (idProofTypeVal === 'Aadhar Card') {
    idNoVal = labour.AadharNumber || labour.idProofNo || labour.IdProofNo || "—";
  } else if (idProofTypeVal === 'PAN Card') {
    idNoVal = labour.PanNumber || labour.idProofNo || labour.IdProofNo || "—";
  } else if (idProofTypeVal === 'Passport') {
    idNoVal = labour.PassportNumber || labour.idProofNo || labour.IdProofNo || "—";
  } else {
    idNoVal = labour.AadharNumber || labour.PanNumber || labour.PassportNumber || labour.idProofNo || labour.IdProofNo || "—";
  }
  $("#pi-idproofno").text(idNoVal);

  if (labour.idproofpath && labour.idproofpath.trim() !== '') {
    var docUrl = resolveDocUrl(labour.idproofpath);
    
    var docBadgeHtml = '<div class="d-flex align-items-center gap-2 flex-wrap mt-1">' +
      '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size:12px;"><i class="ti ti-check me-1"></i>Uploaded</span>' +
      '<button type="button" class="btn btn-sm btn-outline-primary btn-preview-doc" data-docurl="' + docUrl + '" data-title="ID Proof Document" style="border-radius:6px; font-weight:600; padding:2px 10px; font-size:12px;">' +
      '<i class="ti ti-eye me-1"></i> Preview Document</button>' +
      '</div>';
    
    $("#pi-idproofdoc").html(docBadgeHtml);
    
    var editDocBadgeHtml = '<div class="mt-2 p-2 rounded border d-flex align-items-center justify-content-between flex-wrap gap-2" style="background-color:#f8fafc; border-color:#e2e8f0 !important;">' +
      '<div class="d-flex align-items-center gap-2">' +
      '<i class="ti ti-file-check text-success" style="font-size:1.4rem;"></i>' +
      '<div><span style="font-weight:600; font-size:13px;" class="text-dark">Existing Document Uploaded</span><br><small class="text-muted">Click preview to view current file</small></div>' +
      '</div>' +
      '<button type="button" class="btn btn-sm btn-primary btn-preview-doc" data-docurl="' + docUrl + '" data-title="Current ID Proof Document" style="border-radius:6px; font-weight:600; padding:4px 12px; font-size:12.5px;">' +
      '<i class="ti ti-eye me-1"></i> Preview Document</button>' +
      '</div>';
      
    $("#bef-idproofdoc-preview").html(editDocBadgeHtml);
  } else {
    $("#pi-idproofdoc").text("—");
    $("#bef-idproofdoc-preview").html('<span class="text-muted" style="font-size:12px;">No document uploaded yet</span>');
  }

  $("#pi-fathername").text(labour.FatherName || "—");
  $("#pi-mothername").text(labour.MotherName || "—");

  // Spouse name display (conditional)
  var viewMarital = labour.MaritalStatus || '';
  var viewGender  = labour.Gender || '';
  if (viewMarital === 'Married') {
    $("#pi-spousewrapper").show();
    $("#pi-spousename").text(labour.SpouseName || "—");
    if (viewGender === 'Male') {
      $("#pi-spouselabel").text('Wife Name');
    } else if (viewGender === 'Female') {
      $("#pi-spouselabel").text('Husband Name');
    } else {
      $("#pi-spouselabel").text('Spouse Name');
    }
  } else {
    $("#pi-spousewrapper").hide();
  }

  // View Mode - Contact Card
  $("#pi-mobile").text(labour.MobileNo || "—");
  $("#pi-emergency").text(labour.emergencycontactno || "—");

  // View Mode - Address Card
  $("#pi-address1").text(labour.AddressLine1 || "—");
  $("#pi-address2").text(labour.AddressLine2 || "—");

  // View Mode - Skills Card
  $("#pi-skill").text((labour.grafting_skill === 'yes' || labour.graftingskill === 'yes') ? 'Yes (Grafting)' : 'No');
  $("#pi-location").text(labour.WorkingLocationName || labour.WorkLocation || labour.workingLocation || "—");
  $("#pi-wage").text("₹ " + (labour.PayPerDayAmount || labour.PerDayPayment || "—"));

  // View Mode - Bank Card
  $("#pi-bankname").text(bank.BankName || "—");
  $("#pi-accountnumber").text(bank.AccountNumber || "—");
  $("#pi-ifsccode").text(bank.IFSCCode || "—");

  if (bank.CancelChequePath && bank.CancelChequePath.trim() !== '') {
    var bankDocUrl = resolveDocUrl(bank.CancelChequePath);
    
    var bankDocBadgeHtml = '<div class="d-flex align-items-center gap-2 flex-wrap mt-1">' +
      '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size:12px;"><i class="ti ti-check me-1"></i>Uploaded</span>' +
      '<button type="button" class="btn btn-sm btn-outline-primary btn-preview-doc" data-docurl="' + bankDocUrl + '" data-title="Bank Reference Document" style="border-radius:6px; font-weight:600; padding:2px 10px; font-size:12px;">' +
      '<i class="ti ti-eye me-1"></i> Preview Document</button>' +
      '</div>';
    
    $("#pi-bankdoc").html(bankDocBadgeHtml);
    
    var editBankDocBadgeHtml = '<div class="mt-2 p-2 rounded border d-flex align-items-center justify-content-between flex-wrap gap-2" style="background-color:#f8fafc; border-color:#e2e8f0 !important;">' +
      '<div class="d-flex align-items-center gap-2">' +
      '<i class="ti ti-file-check text-success" style="font-size:1.4rem;"></i>' +
      '<div><span style="font-weight:600; font-size:13px;" class="text-dark">Existing Bank Document Uploaded</span><br><small class="text-muted">Click preview to view current file</small></div>' +
      '</div>' +
      '<button type="button" class="btn btn-sm btn-primary btn-preview-doc" data-docurl="' + bankDocUrl + '" data-title="Current Bank Document" style="border-radius:6px; font-weight:600; padding:4px 12px; font-size:12.5px;">' +
      '<i class="ti ti-eye me-1"></i> Preview Document</button>' +
      '</div>';
      
    $("#bkef-bankdoc-preview").html(editBankDocBadgeHtml);
  } else {
    $("#pi-bankdoc").text("—");
    $("#bkef-bankdoc-preview").html('<span class="text-muted" style="font-size:12px;">No document uploaded yet</span>');
  }

  // References Card
  renderReferences(refs);

  // Edit form pre-fill - Basic Card
  $("#bef-firstname").val(labour.FirstName || "");
  $("#bef-middlename").val(labour.MiddleName || "");
  $("#bef-lastname").val(labour.LastName || "");
  $("#bef-gender").val(labour.Gender || "");
  $("#bef-dob").val(labour.DateOfBirth || "");
  $("#bef-marital").val(labour.MaritalStatus || "");
  $("#bef-idproof").val(labour.idprooftype || labour.IdProofType || "");
  $("#bef-idproofno").val(idNoVal !== "—" ? idNoVal : "");
  $("#bef-fathername").val(labour.FatherName || "");
  $("#bef-mothername").val(labour.MotherName || "");
  $("#bef-spousename").val(labour.SpouseName || "");

  updateIDProofInputState();
  validateDOB();
  validateIDProofNo();

  // Spouse edit field show/hide
  var editMarital = labour.MaritalStatus || '';
  var editGender  = labour.Gender || '';
  if (editMarital === 'Married') {
    $("#bef-spousewrapper").show();
    if (editGender === 'Male') {
      $("#bef-spouselabel").text('Wife Name');
      $("#bef-spousename").attr('placeholder', 'Enter wife name');
    } else if (editGender === 'Female') {
      $("#bef-spouselabel").text('Husband Name');
      $("#bef-spousename").attr('placeholder', 'Enter husband name');
    } else {
      $("#bef-spouselabel").text('Spouse Name');
      $("#bef-spousename").attr('placeholder', 'Enter spouse name');
    }
  } else {
    $("#bef-spousewrapper").hide();
  }

  // Edit form pre-fill - Bank Card
  $("#bkef-bankname").val(bank.BankName || "");
  $("#bkef-accountnumber").val(bank.AccountNumber || "");
  $("#bkef-ifsccode").val(bank.IFSCCode || "");
  $("#bkef-bankdoc").val("");

  // Edit form pre-fill - Contact Card
  $("#coef-mobile").val(labour.MobileNo || "");
  $("#coef-emergency").val(labour.emergencycontactno || "");

  // Edit form pre-fill - Address Card
  $("#adef-current").val(labour.AddressLine1 || "");
  $("#adef-permanent").val(labour.AddressLine2 || "");

  // Edit form pre-fill - Skills Card
  $("#skef-grafting").val(labour.grafting_skill || "no");
  $("#skef-location").val(labour.locationid || "");
  $("#skef-wage").val(labour.PerDayPayment || "");

  // Edit form pre-fill - Reference Card
  renderReferenceEditInputs(refs);
}

/* ====================================================
   VALIDATION HELPERS (DOB AGE & AADHAAR / PAN)
   ==================================================== */
function calculateAge(dobString) {
  if (!dobString) return null;
  var parts = dobString.split('-');
  if (parts.length !== 3) return null;
  var year = parseInt(parts[0], 10);
  var month = parseInt(parts[1], 10) - 1;
  var day = parseInt(parts[2], 10);

  var dob = new Date(year, month, day);
  if (isNaN(dob.getTime())) return null;

  var today = new Date();
  var age = today.getFullYear() - dob.getFullYear();
  var m = today.getMonth() - dob.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
    age--;
  }
  return age;
}

function validateDOB() {
  var dobVal = $("#bef-dob").val();
  var $msg = $("#bef-dob-msg");
  if (!dobVal) {
    $msg.html('');
    return true;
  }
  var age = calculateAge(dobVal);
  if (age === null) {
    $msg.html('');
    return true;
  }

  var displayAge = age < 0 ? 0 : age;
  if (age < 18) {
    $msg.html('<div class="text-danger mt-1" style="font-weight:600; font-size:12.5px; display:flex; align-items:center; gap:4px;"><i class="ti ti-alert-triangle" style="font-size:1.1rem;"></i> Warning: Age is ' + displayAge + ' years (under 18 years). Minimum required age is 18 years.</div>');
    return false;
  } else {
    $msg.html('<div class="text-success mt-1" style="font-weight:600; font-size:12.5px; display:flex; align-items:center; gap:4px;"><i class="ti ti-circle-check" style="font-size:1.1rem;"></i> Age: ' + age + ' years</div>');
    return true;
  }
}

function isValidAadhaar(aadhaarNo) {
  var clean = aadhaarNo.replace(/\s+/g, '');
  var regex = /^[2-9]{1}[0-9]{11}$/;
  return regex.test(clean);
}

function isValidPAN(panNo) {
  var clean = panNo.trim().toUpperCase();
  var regex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
  return regex.test(clean);
}

function updateIDProofInputState() {
  var proofType = $("#bef-idproof").val();
  var $label = $("#bef-idproofno-label");
  var $input = $("#bef-idproofno");

  if (proofType === 'Aadhar Card') {
    $label.html('Aadhaar Card Number <span class="req">*</span>');
    $input.attr('placeholder', 'Enter 12-digit Aadhaar number (e.g. 9876 5432 1098)').attr('maxlength', '14');
  } else if (proofType === 'PAN Card') {
    $label.html('PAN Card Number <span class="req">*</span>');
    $input.attr('placeholder', 'Enter 10-character PAN (e.g. ABCDE1234F)').attr('maxlength', '10');
  } else if (proofType === 'Voter ID') {
    $label.html('Voter ID Number');
    $input.attr('placeholder', 'Enter Voter ID number').removeAttr('maxlength');
  } else if (proofType === 'Driving License') {
    $label.html('Driving License Number');
    $input.attr('placeholder', 'Enter Driving License number').removeAttr('maxlength');
  } else if (proofType === 'Passport') {
    $label.html('Passport Number');
    $input.attr('placeholder', 'Enter Passport number').removeAttr('maxlength');
  } else {
    $label.html('Document Unique No / ID Proof No');
    $input.attr('placeholder', 'Enter document unique no').removeAttr('maxlength');
  }
}

function validateIDProofNo() {
  var proofType = $("#bef-idproof").val();
  var proofNo = $("#bef-idproofno").val().trim();
  var $msg = $("#bef-idproofno-msg");

  if (!proofType || !proofNo) {
    $msg.html('');
    return true;
  }

  if (proofType === 'Aadhar Card') {
    if (!isValidAadhaar(proofNo)) {
      $msg.html('<div class="text-danger" style="font-weight:600;"><i class="ti ti-alert-circle me-1"></i> Invalid Aadhaar Number! Must be a 12-digit number (e.g. 9876 5432 1098).</div>');
      return false;
    } else {
      $msg.html('<div class="text-success" style="font-weight:600;"><i class="ti ti-circle-check me-1"></i> Valid Aadhaar Number</div>');
      return true;
    }
  } else if (proofType === 'PAN Card') {
    if (!isValidPAN(proofNo)) {
      $msg.html('<div class="text-danger" style="font-weight:600;"><i class="ti ti-alert-circle me-1"></i> Invalid PAN Number! Must be in format ABCDE1234F.</div>');
      return false;
    } else {
      $msg.html('<div class="text-success" style="font-weight:600;"><i class="ti ti-circle-check me-1"></i> Valid PAN Card Number</div>');
      return true;
    }
  } else {
    $msg.html('');
    return true;
  }
}

// Global Validation Event Listeners
$(document).on("input change keyup blur", "#bef-dob", function() {
  validateDOB();
});

$(document).on("change", "#bef-idproof", function() {
  updateIDProofInputState();
  validateIDProofNo();
});

$(document).on("input change keyup blur", "#bef-idproofno", function() {
  var proofType = $("#bef-idproof").val();
  if (proofType === 'PAN Card') {
    $(this).val($(this).val().toUpperCase());
  }
  validateIDProofNo();
});

// Instant client-side preview when selecting a NEW file
$(document).on("change", "#bef-idproofdoc", function(e) {
  var file = this.files[0];
  var $preview = $("#bef-idproofdoc-preview");
  if (!file) return;

  var reader = new FileReader();
  var fileName = file.name;
  var fileType = file.type;

  if (fileType.startsWith('image/')) {
    reader.onload = function(evt) {
      var dataUrl = evt.target.result;
      var html = '<div class="mt-2 p-2 rounded border" style="background-color:#f8fafc; border-color:#e2e8f0 !important;">' +
        '<div class="d-flex align-items-center justify-content-between mb-2">' +
        '<span style="font-weight:600; font-size:13px;" class="text-primary"><i class="ti ti-photo me-1"></i> Selected Image: ' + fileName + '</span>' +
        '<button type="button" class="btn btn-sm btn-outline-primary btn-preview-doc" data-docurl="' + dataUrl + '" data-title="Selected Image Preview" style="border-radius:6px; font-weight:600; padding:2px 10px; font-size:12px;"><i class="ti ti-eye me-1"></i> Preview Large</button>' +
        '</div>' +
        '<div class="text-center"><img src="' + dataUrl + '" class="img-thumbnail rounded" style="max-height:140px; object-fit:contain;"></div>' +
        '</div>';
      $preview.html(html);
    };
    reader.readAsDataURL(file);
  } else if (fileType === 'application/pdf') {
    reader.onload = function(evt) {
      var dataUrl = evt.target.result;
      var html = '<div class="mt-2 p-2 rounded border d-flex align-items-center justify-content-between flex-wrap gap-2" style="background-color:#f8fafc; border-color:#e2e8f0 !important;">' +
        '<div class="d-flex align-items-center gap-2">' +
        '<i class="ti ti-file-text text-danger" style="font-size:1.5rem;"></i>' +
        '<div><span style="font-weight:600; font-size:13px;" class="text-dark">Selected PDF: ' + fileName + '</span><br><small class="text-muted">Click preview to view PDF</small></div>' +
        '</div>' +
        '<button type="button" class="btn btn-sm btn-danger btn-preview-doc" data-docurl="' + dataUrl + '" data-title="Selected PDF Preview" style="border-radius:6px; font-weight:600; padding:4px 12px; font-size:12.5px;"><i class="ti ti-eye me-1"></i> Preview PDF</button>' +
        '</div>';
      $preview.html(html);
    };
    reader.readAsDataURL(file);
  } else {
    var html = '<div class="mt-2 p-2 rounded border d-flex align-items-center gap-2" style="background-color:#f8fafc;">' +
      '<i class="ti ti-file-description text-primary" style="font-size:1.5rem;"></i>' +
      '<span style="font-weight:600; font-size:13px;">Selected File: ' + fileName + '</span>' +
      '</div>';
    $preview.html(html);
  }
});

// Preview for Bank Document upload
$(document).on("change", "#bkef-bankdoc", function() {
  var file = this.files[0];
  var $preview = $("#bkef-bankdoc-preview");
  if (!file) return;

  var fileName = file.name;
  var fileType = file.type || '';
  var reader = new FileReader();

  if (fileType.startsWith('image/')) {
    reader.onload = function(evt) {
      var dataUrl = evt.target.result;
      var html = '<div class="mt-2 p-2 rounded border" style="background-color:#f8fafc; border-color:#e2e8f0 !important;">' +
        '<div class="d-flex align-items-center justify-content-between mb-2">' +
        '<span style="font-weight:600; font-size:13px;" class="text-primary"><i class="ti ti-photo me-1"></i> Selected Image: ' + fileName + '</span>' +
        '<button type="button" class="btn btn-sm btn-outline-primary btn-preview-doc" data-docurl="' + dataUrl + '" data-title="Selected Bank Document Preview" style="border-radius:6px; font-weight:600; padding:2px 10px; font-size:12px;"><i class="ti ti-eye me-1"></i> Preview Large</button>' +
        '</div>' +
        '<div class="text-center"><img src="' + dataUrl + '" class="img-thumbnail rounded" style="max-height:140px; object-fit:contain;"></div>' +
        '</div>';
      $preview.html(html);
    };
    reader.readAsDataURL(file);
  } else if (fileType === 'application/pdf') {
    reader.onload = function(evt) {
      var dataUrl = evt.target.result;
      var html = '<div class="mt-2 p-2 rounded border d-flex align-items-center justify-content-between flex-wrap gap-2" style="background-color:#f8fafc; border-color:#e2e8f0 !important;">' +
        '<div class="d-flex align-items-center gap-2">' +
        '<i class="ti ti-file-text text-danger" style="font-size:1.5rem;"></i>' +
        '<div><span style="font-weight:600; font-size:13px;" class="text-dark">Selected PDF: ' + fileName + '</span><br><small class="text-muted">Click preview to view PDF</small></div>' +
        '</div>' +
        '<button type="button" class="btn btn-sm btn-danger btn-preview-doc" data-docurl="' + dataUrl + '" data-title="Selected Bank Document Preview" style="border-radius:6px; font-weight:600; padding:4px 12px; font-size:12.5px;"><i class="ti ti-eye me-1"></i> Preview PDF</button>' +
        '</div>';
      $preview.html(html);
    };
    reader.readAsDataURL(file);
  } else {
    var html = '<div class="mt-2 p-2 rounded border d-flex align-items-center gap-2" style="background-color:#f8fafc;">' +
      '<i class="ti ti-file-description text-primary" style="font-size:1.5rem;"></i>' +
      '<span style="font-weight:600; font-size:13px;">Selected File: ' + fileName + '</span>' +
      '</div>';
    $preview.html(html);
  }
});

// Modal Preview Button Click Handler
$(document).on("click", ".btn-preview-doc", function(e) {
  e.preventDefault();
  var docUrl = $(this).data("docurl");
  var title = $(this).data("title") || "ID Proof Document Preview";
  if (!docUrl) return;

  $("#docPreviewModalLabel").html('<i class="ti ti-file-search me-1"></i> ' + title);
  $("#docPreviewDownloadBtn").attr("href", docUrl);

  var $body = $("#docPreviewModalBody");
  $body.empty();

  var isPdf = docUrl.toLowerCase().includes('.pdf') || docUrl.startsWith('data:application/pdf');
  var isImage = docUrl.toLowerCase().match(/\.(jpg|jpeg|png|gif|webp)$/) || docUrl.startsWith('data:image/');

  if (isImage) {
    $body.html('<img src="' + docUrl + '" class="img-fluid rounded shadow-sm" style="max-height: 480px; object-fit: contain;" onerror="this.onerror=null; this.parentElement.innerHTML=\'<div class=\\\'p-4 text-center\\\'><i class=\\\'ti ti-alert-triangle text-warning\\\' style=\\\'font-size:3rem;\\\'></i><h6 class=\\\'mt-3\\\'>Document could not be previewed directly</h6><a href=\\\'' + docUrl + '\\\' target=\\\'_blank\\\' class=\\\'btn btn-primary btn-sm mt-2\\\'><i class=\\\'ti ti-external-link me-1\\\'></i> Open in New Tab</a></div>\';">');
  } else if (isPdf) {
    $body.html('<iframe src="' + docUrl + '" style="width: 100%; height: 480px; border: none; border-radius: 8px;"></iframe>');
  } else {
    $body.html('<div class="p-4 text-center">' +
      '<i class="ti ti-file-download text-primary" style="font-size:3.5rem;"></i>' +
      '<h6 class="mt-3">Preview unavailable for this file type</h6>' +
      '<a href="' + docUrl + '" target="_blank" class="btn btn-primary btn-sm mt-2"><i class="ti ti-external-link me-1"></i> Open File</a>' +
      '</div>');
  }

  var modalEl = document.getElementById("docPreviewModal");
  if (window.bootstrap && bootstrap.Modal) {
    var modalInstance = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl);
    modalInstance.show();
  } else {
    $("#docPreviewModal").modal("show");
  }
});

/* ====================================================
   RENDER REFERENCES
   ==================================================== */
function renderReferences(refs) {
  var html = "";
  if (!refs || refs.length === 0) {
    html = '<div class="ldb-empty-state"><i class="ti ti-user-question"></i><p>No reference persons found.</p></div>';
  } else {
    refs.forEach(function(ref, idx) {
      html += '<div class="ldb-ref-card">' +
        '<div class="ldb-ref-name"><i class="ti ti-user-circle" style="color:var(--ldb-purple);margin-right:6px;"></i>' +
        (ref.FirstName || "—") + '</div>' +
        '<div class="ldb-ref-meta">' +
          '<span><i class="ti ti-users-group"></i> ' + (ref.Relation || "—") + '</span>' +
          '<span><i class="ti ti-phone"></i> ' + (ref.MobileNo || "—") + '</span>' +
        '</div>' +
        '</div>';
    });
  }
  $("#references-container").html(html);
}

/* ====================================================
   RENDER REFERENCE EDIT INPUTS
   ==================================================== */
var RELATION_OPTIONS = [
  "Father",
  "Mother",
  "Brother",
  "Sister",
  "Spouse",
  "Husband",
  "Wife",
  "Son",
  "Daughter",
  "Uncle",
  "Aunt",
  "Cousin",
  "Friend",
  "Relative",
  "Neighbor",
  "Guardian",
  "Other"
];

/* ====================================================
   INDIAN BANKS & BANK VALIDATION HELPERS (RBI RULES)
   ==================================================== */
var INDIAN_BANKS = [
  "STATE BANK OF INDIA",
  "PUNJAB NATIONAL BANK",
  "BANK OF BARODA",
  "CANARA BANK",
  "UNION BANK OF INDIA",
  "BANK OF INDIA",
  "INDIAN BANK",
  "CENTRAL BANK OF INDIA",
  "INDIAN OVERSEAS BANK",
  "UCO BANK",
  "BANK OF MAHARASHTRA",
  "PUNJAB & SIND BANK",
  "HDFC BANK",
  "ICICI BANK",
  "AXIS BANK",
  "KOTAK MAHINDRA BANK",
  "INDUSIND BANK",
  "YES BANK",
  "FEDERAL BANK",
  "IDBI BANK",
  "BANDHAN BANK",
  "IDFC FIRST BANK",
  "RBL BANK",
  "SOUTH INDIAN BANK",
  "CITY UNION BANK",
  "KARUR VYSYA BANK",
  "TAMILNAD MERCANTILE BANK",
  "JAMMU & KASHMIR BANK",
  "KARNATAKA BANK",
  "AU SMALL FINANCE BANK",
  "EQUITAS SMALL FINANCE BANK",
  "UTKARSH SMALL FINANCE BANK",
  "UJJIVAN SMALL FINANCE BANK",
  "JANA SMALL FINANCE BANK",
  "FINCARE SMALL FINANCE BANK",
  "PAYTM PAYMENTS BANK",
  "AIRTEL PAYMENTS BANK",
  "INDIA POST PAYMENTS BANK",
  "FINO PAYMENTS BANK",
  "NSDL PAYMENTS BANK",
  "BARODA RAJASTHAN KSHETRIYA GRAMIN BANK",
  "RAJASTHAN MARUDHARA GRAMIN BANK",
  "ARYAVART BANK",
  "PRATHAMA UP GRAMIN BANK",
  "MADHYA PRADESH GRAMIN BANK",
  "MADHYANCHAL GRAMIN BANK",
  "SARVA HARYANA GRAMIN BANK",
  "PUNJAB GRAMIN BANK",
  "HIMACHAL PRADESH GRAMIN BANK",
  "UTTARAKHAND GRAMIN BANK",
  "DAKSHIN BIHAR GRAMIN BANK",
  "UTTAR BIHAR GRAMIN BANK",
  "PASCHIM BANGA GRAMIN BANK",
  "BANGIYA GRAMIN VIKASH BANK",
  "ODISHA GRAMYA BANK",
  "UTKAL GRAMEEN BANK",
  "ASSAM GRAMIN VIKASH BANK",
  "ANDHRA PRADESH GRAMEENA VIKAS BANK",
  "ANDHRA PRAGATHI GRAMEENA BANK",
  "TELANGANA GRAMEENA BANK",
  "KARNATAKA GRAMIN BANK",
  "KARNATAKA VIKAS GRAMEENA BANK",
  "KERALA GRAMIN BANK",
  "TAMIL NADU GRAMA BANK",
  "MAHARASHTRA GRAMIN BANK",
  "VIDHARBHA KONKAN GRAMIN BANK",
  "SAURASHTRA GRAMIN BANK",
  "BARODA GUJARAT GRAMIN BANK",
  "CHHATTISGARH RAJYA GRAMIN BANK",
  "JHARKHAND RAJYA GRAMIN BANK"
];

var selectedBankIndex = -1;

function renderBankSuggestions(query) {
  var $list = $("#bkef-bank-suggestions");
  query = (query || "").trim().toUpperCase();
  selectedBankIndex = -1;

  if (!query) {
    $list.hide().html('');
    return;
  }

  var matches = INDIAN_BANKS.filter(function(b) {
    return b.indexOf(query) !== -1;
  });

  if (matches.length === 0) {
    $list.hide().html('');
    return;
  }

  var html = "";
  matches.forEach(function(bank, idx) {
    var regex = new RegExp("(" + query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ")", "gi");
    var highlighted = bank.replace(regex, '<strong style="color:#7c3aed;">$1</strong>');
    html += '<div class="ldb-autocomplete-item" data-index="' + idx + '" data-value="' + bank + '">' +
      '<i class="ti ti-building-bank"></i> ' +
      '<span>' + highlighted + '</span>' +
      '</div>';
  });

  $list.html(html).show();
}

function selectBankSuggestion(bankName) {
  if (!bankName) return;
  var upperName = bankName.toUpperCase();
  $("#bkef-bankname").val(upperName).removeClass("is-invalid").addClass("is-valid")
    .css({"border": "1.5px solid #10b981", "background-color": "#f0fdf4", "box-shadow": "0 0 0 3px rgba(16, 185, 129, 0.15)"});
  $("#bkef-bank-suggestions").hide().html('');
  validateBankName($("#bkef-bankname"), $("#bkef-bankname-feedback"), true);
}

function validateBankName($input, $feedback, isRequired) {
  if (!$input || !$input.length) return true;
  var raw = ($input.val() || "").toString().toUpperCase();
  $input.val(raw);
  var val = raw.trim();

  if (!val) {
    if (isRequired) {
      $input.removeClass("is-valid").addClass("is-invalid")
        .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
      if ($feedback && $feedback.length) {
        $feedback.removeClass("is-valid").addClass("is-invalid")
          .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle" style="color: #dc2626 !important; font-size: 13px;"></i> Bank name is required</span>');
      }
      return false;
    } else {
      $input.removeClass("is-valid is-invalid").css({"border": "", "background-color": "", "box-shadow": ""});
      if ($feedback && $feedback.length) $feedback.removeClass("is-valid is-invalid").html('');
      return true;
    }
  }

  if (val.length < 3) {
    $input.removeClass("is-valid").addClass("is-invalid")
      .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
    if ($feedback && $feedback.length) {
      $feedback.removeClass("is-valid").addClass("is-invalid")
        .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle" style="color: #dc2626 !important; font-size: 13px;"></i> Enter a valid bank name</span>');
    }
    return false;
  }

  $input.removeClass("is-invalid").addClass("is-valid")
    .css({"border": "1.5px solid #10b981", "background-color": "#f0fdf4", "box-shadow": "0 0 0 3px rgba(16, 185, 129, 0.15)"});
  if ($feedback && $feedback.length) {
    $feedback.removeClass("is-invalid").addClass("is-valid")
      .html('<span style="color: #16a34a !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-circle-check" style="color: #16a34a !important; font-size: 13px;"></i> Valid Bank Name</span>');
  }
  return true;
}

function validateAccountNumber($input, $feedback, isRequired) {
  if (!$input || !$input.length) return true;
  var raw = ($input.val() || "").toString();
  var digits = raw.replace(/\D/g, '').slice(0, 18);
  if (raw !== digits) {
    $input.val(digits);
  }

  if (!digits) {
    if (isRequired) {
      $input.removeClass("is-valid").addClass("is-invalid")
        .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
      if ($feedback && $feedback.length) {
        $feedback.removeClass("is-valid").addClass("is-invalid")
          .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle" style="color: #dc2626 !important; font-size: 13px;"></i> Account number is required</span>');
      }
      return false;
    } else {
      $input.removeClass("is-valid is-invalid").css({"border": "", "background-color": "", "box-shadow": ""});
      if ($feedback && $feedback.length) $feedback.removeClass("is-valid is-invalid").html('');
      return true;
    }
  }

  // RBI rule: Indian bank account numbers are 9 to 18 numeric digits
  if (digits.length < 9) {
    $input.removeClass("is-valid").addClass("is-invalid")
      .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
    if ($feedback && $feedback.length) {
      $feedback.removeClass("is-valid").addClass("is-invalid")
        .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle" style="color: #dc2626 !important; font-size: 13px;"></i> Account number must be 9 to 18 digits (' + digits.length + '/9-18)</span>');
    }
    return false;
  }

  $input.removeClass("is-invalid").addClass("is-valid")
    .css({"border": "1.5px solid #10b981", "background-color": "#f0fdf4", "box-shadow": "0 0 0 3px rgba(16, 185, 129, 0.15)"});
  if ($feedback && $feedback.length) {
    $feedback.removeClass("is-invalid").addClass("is-valid")
      .html('<span style="color: #16a34a !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-circle-check" style="color: #16a34a !important; font-size: 13px;"></i> Valid Account Number</span>');
  }
  return true;
}

function validateIFSCCode($input, $feedback, isRequired) {
  if (!$input || !$input.length) return true;
  var raw = ($input.val() || "").toString().toUpperCase();
  var clean = raw.replace(/[^A-Z0-9]/g, '').slice(0, 11);
  if (raw !== clean) {
    $input.val(clean);
  } else {
    $input.val(clean);
  }

  if (!clean) {
    if (isRequired) {
      $input.removeClass("is-valid").addClass("is-invalid")
        .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
      if ($feedback && $feedback.length) {
        $feedback.removeClass("is-valid").addClass("is-invalid")
          .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle" style="color: #dc2626 !important; font-size: 13px;"></i> IFSC code is required</span>');
      }
      return false;
    } else {
      $input.removeClass("is-valid is-invalid").css({"border": "", "background-color": "", "box-shadow": ""});
      if ($feedback && $feedback.length) $feedback.removeClass("is-valid is-invalid").html('');
      return true;
    }
  }

  // RBI rule: Exactly 11 chars (4 letters + 0 + 6 alphanumeric)
  if (clean.length < 11) {
    $input.removeClass("is-valid").addClass("is-invalid")
      .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
    if ($feedback && $feedback.length) {
      $feedback.removeClass("is-valid").addClass("is-invalid")
        .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle" style="color: #dc2626 !important; font-size: 13px;"></i> IFSC code must be 11 characters (' + clean.length + '/11)</span>');
    }
    return false;
  }

  if (!/^[A-Z]{4}/.test(clean)) {
    $input.removeClass("is-valid").addClass("is-invalid")
      .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
    if ($feedback && $feedback.length) {
      $feedback.removeClass("is-valid").addClass("is-invalid")
        .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle" style="color: #dc2626 !important; font-size: 13px;"></i> First 4 characters must be letters (e.g. SBIN)</span>');
    }
    return false;
  }

  if (clean.charAt(4) !== '0') {
    $input.removeClass("is-valid").addClass("is-invalid")
      .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
    if ($feedback && $feedback.length) {
      $feedback.removeClass("is-valid").addClass("is-invalid")
        .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle" style="color: #dc2626 !important; font-size: 13px;"></i> 5th character must be 0 (Zero) as per RBI rule</span>');
    }
    return false;
  }

  if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(clean)) {
    $input.removeClass("is-valid").addClass("is-invalid")
      .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
    if ($feedback && $feedback.length) {
      $feedback.removeClass("is-valid").addClass("is-invalid")
        .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle" style="color: #dc2626 !important; font-size: 13px;"></i> Invalid IFSC format (e.g. SBIN0001234)</span>');
    }
    return false;
  }

  $input.removeClass("is-invalid").addClass("is-valid")
    .css({"border": "1.5px solid #10b981", "background-color": "#f0fdf4", "box-shadow": "0 0 0 3px rgba(16, 185, 129, 0.15)"});
  if ($feedback && $feedback.length) {
    $feedback.removeClass("is-invalid").addClass("is-valid")
      .html('<span style="color: #16a34a !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-circle-check" style="color: #16a34a !important; font-size: 13px;"></i> Valid IFSC Code</span>');
  }
  return true;
}

/* ====================================================
   MOBILE NUMBER VALIDATION HELPERS
   ==================================================== */
function validateMobileInput($input, $feedback, isRequired) {
  if (!$input || !$input.length) return true;
  var raw = ($input.val() || "").toString();
  var digits = raw.replace(/\D/g, '').slice(0, 10);
  if (raw !== digits) {
    $input.val(digits);
  }

  // If empty
  if (!digits) {
    if (isRequired) {
      $input.removeClass("is-valid").addClass("is-invalid")
        .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
      if ($feedback && $feedback.length) {
        $feedback.removeClass("is-valid").addClass("is-invalid")
          .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle" style="color: #dc2626 !important; font-size: 13px;"></i> Mobile number is required</span>');
      }
      return false;
    } else {
      $input.removeClass("is-valid is-invalid")
        .css({"border": "", "background-color": "", "box-shadow": ""});
      if ($feedback && $feedback.length) {
        $feedback.removeClass("is-valid is-invalid").html('');
      }
      return true;
    }
  }

  // Validate starting digit (6, 7, 8, 9)
  if (!/^[6-9]/.test(digits)) {
    $input.removeClass("is-valid").addClass("is-invalid")
      .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
    if ($feedback && $feedback.length) {
      $feedback.removeClass("is-valid").addClass("is-invalid")
        .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle" style="color: #dc2626 !important; font-size: 13px;"></i> Must start with 6, 7, 8, or 9</span>');
    }
    return false;
  }

  // Check length < 10
  if (digits.length < 10) {
    $input.removeClass("is-valid").addClass("is-invalid")
      .css({"border": "1.5px solid #ef4444", "background-color": "#fef2f2", "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"});
    if ($feedback && $feedback.length) {
      $feedback.removeClass("is-valid").addClass("is-invalid")
        .html('<span style="color: #dc2626 !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-alert-circle" style="color: #dc2626 !important; font-size: 13px;"></i> Enter 10-digit number (' + digits.length + '/10)</span>');
    }
    return false;
  }

  // Valid 10 digit number
  $input.removeClass("is-invalid").addClass("is-valid")
    .css({"border": "1.5px solid #10b981", "background-color": "#f0fdf4", "box-shadow": "0 0 0 3px rgba(16, 185, 129, 0.15)"});
  if ($feedback && $feedback.length) {
    $feedback.removeClass("is-invalid").addClass("is-valid")
      .html('<span style="color: #16a34a !important; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-circle-check" style="color: #16a34a !important; font-size: 13px;"></i> Valid mobile number</span>');
  }
  return true;
}

function validateReferenceRowMobile($row) {
  if (!$row || !$row.length) return true;
  var name = ($row.find(".ref-input-name").val() || "").trim();
  var relation = ($row.find(".ref-input-relation").val() || "").trim();
  var $mobileInput = $row.find(".ref-input-mobile");
  var $feedback = $row.find(".ref-mobile-feedback");
  var mobileVal = ($mobileInput.val() || "").trim();

  // If any field in this row has a value or mobile has digits, validate mobile
  var isRequired = !!(name || relation || mobileVal);
  return validateMobileInput($mobileInput, $feedback, isRequired);
}

function renderReferenceEditInputs(refs) {
  var refHtml = "";
  for (var i = 0; i < 2; i++) {
    var ref = (refs && refs[i]) ? refs[i] : { FirstName: "", Relation: "", MobileNo: "" };
    var currentRel = (ref.Relation || "").trim();

    var optionsHtml = '<option value="">Select Relation</option>';
    var foundSelected = false;

    RELATION_OPTIONS.forEach(function(opt) {
      var isSelected = (currentRel && currentRel.toLowerCase() === opt.toLowerCase());
      if (isSelected) foundSelected = true;
      optionsHtml += '<option value="' + opt + '"' + (isSelected ? ' selected' : '') + '>' + opt + '</option>';
    });

    if (currentRel && !foundSelected) {
      optionsHtml += '<option value="' + currentRel + '" selected>' + currentRel + '</option>';
    }

    refHtml += `
      <div class="ldb-ref-edit-card p-3 mb-3 rounded" style="background-color: #faf5ff; border: 1.5px solid #e9d5ff;">
        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom" style="border-color: #f3e8ff !important;">
          <div style="width: 26px; height: 26px; border-radius: 50%; background: #7c3aed; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">${i+1}</div>
          <span style="font-weight: 700; font-size: 13px; color: #6b21a8;">Reference Person ${i+1}</span>
        </div>
        <div class="row g-3 ldb-ref-edit-row">
          <div class="col-md-4">
            <label class="form-label small text-muted" style="font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">
              <i class="ti ti-user me-1" style="color: #7c3aed;"></i> Name ${i === 0 ? '<span class="text-danger">*</span>' : ''}
            </label>
            <input type="text" class="ldb-input ref-input-name" placeholder="Enter Reference Person Name" value="${ref.FirstName || ''}">
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted" style="font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">
              <i class="ti ti-heart-handshake me-1" style="color: #7c3aed;"></i> Relation ${i === 0 ? '<span class="text-danger">*</span>' : ''}
            </label>
            <select class="ldb-input select2 ref-input-relation">
              ${optionsHtml}
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted" style="font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">
              <i class="ti ti-phone me-1" style="color: #7c3aed;"></i> Mobile ${i === 0 ? '<span class="text-danger">*</span>' : ''}
            </label>
            <input type="text" class="ldb-input ref-input-mobile" placeholder="10-digit mobile number" maxlength="10" value="${ref.MobileNo || ''}">
            <div class="ldb-validation-msg ref-mobile-feedback"></div>
          </div>
        </div>
      </div>
    `;
  }
  $("#refs-edit-inputs").html(refHtml);

  if ($.fn.select2) {
    $("#refs-edit-inputs .ref-input-relation").each(function() {
      if ($(this).data('select2')) {
        $(this).select2('destroy');
      }
      $(this).select2({
        placeholder: "Select Relation",
        allowClear: true,
        width: "100%"
      });
    });
  }

  // Validate mobile inputs on initial render
  $("#refs-edit-inputs .ldb-ref-edit-row").each(function() {
    validateReferenceRowMobile($(this));
  });
}

/* ====================================================
   LOAD LOCATIONS DROPDOWN (for edit form)
   ==================================================== */
function loadLocationsDropdown() {
  if (locOptionsCache) {
    $("#skef-location").html(locOptionsCache);
    if (labourData) {
      var locVal = labourData.locationid || labourData.LocationId || labourData.workingLocation || "";
      $("#skef-location").val(locVal);
    }
    return;
  }
  $.post(routurl + "modal/hrm_laboursalarymodal.php?action=GetWorkingLocations", {
    companyid    : companyid,
    subcompanyid : subcompanyid,
    projectid    : projectid
  }, function(res) {
    try {
      var data = (typeof res === "string") ? JSON.parse(res) : res;
      var html = '<option value="">Select Location</option>';
      if (Array.isArray(data) && data.length > 0) {
        data.forEach(function(loc) {
          var id = loc.LocationId || loc.locationid || loc.id;
          var name = loc.LocationName || loc.locationname || loc.name || ("Location #" + id);
          html += '<option value="' + id + '">' + name + '</option>';
        });
      }
      locOptionsCache = html;
      $("#skef-location").html(html);
      if (labourData) {
        var locVal = labourData.locationid || labourData.LocationId || labourData.workingLocation || "";
        $("#skef-location").val(locVal);
      }
    } catch(e) {
      console.error("Error parsing locations:", e);
    }
  }).fail(function(err) {
    console.error("Failed to load working locations", err);
  });
}

/* ====================================================
   LOAD STATES & DISTRICTS (from karjeefarm DB)
   ==================================================== */
var statesOptionsCache = "";
var districtsOptionsCache = {};

function loadAddressStatesDropdown(callback) {
  function initSelect2State() {
    if ($.fn.select2) {
      if ($("#adef-state").data('select2')) {
        $("#adef-state").select2('destroy');
      }
      $("#adef-state").select2({
        placeholder: "Select State",
        allowClear: true,
        width: "100%"
      });
    }
  }

  if (statesOptionsCache) {
    $("#adef-state").html(statesOptionsCache);
    initSelect2State();
    if (typeof callback === 'function') callback();
    return;
  }
  $.post(routurl + "modal/hrm_laboursalarymodal.php?action=GetMasterStates", function(res) {
    try {
      var data = (typeof res === "string") ? JSON.parse(res) : res;
      var html = '<option value="">Select State</option>';
      if (data && data.status === "Success" && Array.isArray(data.data)) {
        data.data.forEach(function(st) {
          html += '<option value="' + st.stateid + '" data-name="' + (st.statename || '') + '">' + st.statename + '</option>';
        });
      }
      statesOptionsCache = html;
      $("#adef-state").html(html);
      initSelect2State();
      if (typeof callback === 'function') callback();
    } catch(e) {
      console.error("Error loading states:", e);
    }
  });
}

function loadAddressDistrictsDropdown(stateid, selectedDistrictId, callback) {
  function initSelect2District(placeholderText) {
    if ($.fn.select2) {
      if ($("#adef-district").data('select2')) {
        $("#adef-district").select2('destroy');
      }
      $("#adef-district").select2({
        placeholder: placeholderText || "Select District",
        allowClear: true,
        width: "100%"
      });
    }
  }

  if (!stateid) {
    $("#adef-district").html('<option value="">Select State First</option>').prop("disabled", true);
    initSelect2District("Select State First");
    return;
  }
  $("#adef-district").html('<option value="">Loading districts...</option>').prop("disabled", true);
  initSelect2District("Loading districts...");

  if (districtsOptionsCache[stateid]) {
    $("#adef-district").html(districtsOptionsCache[stateid]).prop("disabled", false);
    if (selectedDistrictId) $("#adef-district").val(selectedDistrictId);
    initSelect2District("Select District");
    if (typeof callback === 'function') callback();
    return;
  }

  $.post(routurl + "modal/hrm_laboursalarymodal.php?action=GetMasterDistricts", { stateid: stateid }, function(res) {
    try {
      var data = (typeof res === "string") ? JSON.parse(res) : res;
      var html = '<option value="">Select District</option>';
      if (data && data.status === "Success" && Array.isArray(data.data)) {
        data.data.forEach(function(dt) {
          html += '<option value="' + dt.districtid + '" data-name="' + (dt.districtname || '') + '">' + dt.districtname + '</option>';
        });
      }
      districtsOptionsCache[stateid] = html;
      $("#adef-district").html(html).prop("disabled", false);
      if (selectedDistrictId) $("#adef-district").val(selectedDistrictId);
      initSelect2District("Select District");
      if (typeof callback === 'function') callback();
    } catch(e) {
      console.error("Error loading districts:", e);
      $("#adef-district").html('<option value="">Error loading districts</option>');
      initSelect2District("Error loading districts");
    }
  });
}

function updateAddressFromBuilder() {
  var stateName = $("#adef-state option:selected").data("name") || "";
  var districtName = $("#adef-district option:selected").data("name") || "";
  var tehsil = ($("#adef-tehsil").val() || "").trim();
  var cityVillage = ($("#adef-cityvillage").val() || "").trim();

  var parts = [];
  if (cityVillage) parts.push(cityVillage);
  if (tehsil) parts.push(tehsil);
  if (districtName) parts.push(districtName);
  if (stateName) parts.push(stateName);

  var composed = parts.join(", ");
  if (composed) {
    $("#adef-current").val(composed);
    if ($("#adef-same-as-current").is(":checked")) {
      $("#adef-permanent").val(composed);
    }
  }
}

/* ====================================================
   TABS
   ==================================================== */
function initTabs() {
  $(document).on("click", ".ldb-tab", function() {
    var tab = $(this).data("tab");
    switchTab(tab);
  });
}

function switchTab(tabName) {
  $(".ldb-tab").removeClass("active");
  $(".ldb-tab[data-tab='" + tabName + "']").addClass("active");
  $(".ldb-tab-pane").removeClass("active");
  $("#tab-" + tabName).addClass("active");
}

/* ====================================================
   SECTION EDIT MODE TOGGLES
   ==================================================== */
function initSectionToggles() {
  $(document).on("click", ".btn-edit-section", function() {
    var section = $(this).data("section");
    showSectionEdit(section);
  });

  $(document).on("click", ".btn-cancel-section", function() {
    var section = $(this).data("section");
    hideSectionEdit(section);
  });

  // Live spouse field toggle when editing basic section
  $(document).on("change", "#bef-marital, #bef-gender", function() {
    var marital = $("#bef-marital").val();
    var gender  = $("#bef-gender").val();
    if (marital === 'Married') {
      $("#bef-spousewrapper").show();
      if (gender === 'Male') {
        $("#bef-spouselabel").text('Wife Name');
        $("#bef-spousename").attr('placeholder', 'Enter wife name');
      } else if (gender === 'Female') {
        $("#bef-spouselabel").text('Husband Name');
        $("#bef-spousename").attr('placeholder', 'Enter husband name');
      } else {
        $("#bef-spouselabel").text('Spouse Name');
        $("#bef-spousename").attr('placeholder', 'Enter spouse name');
      }
    } else {
      $("#bef-spousewrapper").hide();
      $("#bef-spousename").val('');
    }
  });

  // Address builder live auto-fill events
  $(document).on("change", "#adef-state", function() {
    var stateid = $(this).val();
    loadAddressDistrictsDropdown(stateid, "", function() {
      updateAddressFromBuilder();
    });
    updateAddressFromBuilder();
  });

  $(document).on("change", "#adef-district", function() {
    updateAddressFromBuilder();
  });

  $(document).on("input change keyup", "#adef-tehsil, #adef-cityvillage", function() {
    updateAddressFromBuilder();
  });

  $(document).on("change", "#adef-same-as-current", function() {
    if ($(this).is(":checked")) {
      $("#adef-permanent").val($("#adef-current").val());
    }
  });

  $(document).on("input", "#adef-current", function() {
    if ($("#adef-same-as-current").is(":checked")) {
      $("#adef-permanent").val($(this).val());
    }
  });
  // Bank Name Autocomplete & Keyboard Navigation Events
  $(document).on("input keyup", "#bkef-bankname", function(e) {
    if (e.key === "ArrowDown" || e.key === "ArrowUp" || e.key === "Enter" || e.key === "Escape") {
      return;
    }
    renderBankSuggestions($(this).val());
    validateBankName($(this), $("#bkef-bankname-feedback"), true);
  });

  $(document).on("keydown", "#bkef-bankname", function(e) {
    var $list = $("#bkef-bank-suggestions");
    var $items = $list.find(".ldb-autocomplete-item");

    if (!$list.is(":visible") || $items.length === 0) {
      if (e.key === "ArrowDown" && $(this).val().trim()) {
        renderBankSuggestions($(this).val());
      }
      return;
    }

    if (e.key === "ArrowDown") {
      e.preventDefault();
      selectedBankIndex++;
      if (selectedBankIndex >= $items.length) selectedBankIndex = 0;
      $items.removeClass("is-active");
      var $cur = $items.eq(selectedBankIndex).addClass("is-active");
      if ($cur.length) $cur[0].scrollIntoView({ block: 'nearest' });
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      selectedBankIndex--;
      if (selectedBankIndex < 0) selectedBankIndex = $items.length - 1;
      $items.removeClass("is-active");
      var $cur = $items.eq(selectedBankIndex).addClass("is-active");
      if ($cur.length) $cur[0].scrollIntoView({ block: 'nearest' });
    } else if (e.key === "Enter") {
      if (selectedBankIndex >= 0 && selectedBankIndex < $items.length) {
        e.preventDefault();
        var val = $items.eq(selectedBankIndex).data("value");
        selectBankSuggestion(val);
      }
    } else if (e.key === "Escape") {
      $list.hide();
    }
  });

  $(document).on("click", ".ldb-autocomplete-item", function() {
    var val = $(this).data("value");
    selectBankSuggestion(val);
  });

  $(document).on("click", function(e) {
    if (!$(e.target).closest(".ldb-autocomplete-wrapper").length) {
      $("#bkef-bank-suggestions").hide();
    }
  });

  // Account Number & IFSC live validation events
  $(document).on("input keyup change blur", "#bkef-accountnumber", function() {
    validateAccountNumber($(this), $("#bkef-accountnumber-feedback"), true);
  });

  $(document).on("input keyup change blur", "#bkef-ifsccode", function() {
    validateIFSCCode($(this), $("#bkef-ifsccode-feedback"), true);
  });

  // Mobile number and Emergency Contact live validation events
  $(document).on("input keyup change blur", "#coef-mobile", function() {
    validateMobileInput($(this), $("#coef-mobile-feedback"), true);
  });

  $(document).on("input keyup change blur", "#coef-emergency", function() {
    validateMobileInput($(this), $("#coef-emergency-feedback"), false);
  });

  $(document).on("input keyup change blur", ".ref-input-mobile", function() {
    var $row = $(this).closest(".ldb-ref-edit-row");
    validateReferenceRowMobile($row);
  });

  $(document).on("input keyup change", ".ref-input-name", function() {
    var val = ($(this).val() || "").trim();
    if (val) {
      $(this).removeClass("is-invalid").css({"border": "", "background-color": "", "box-shadow": ""});
    }
    var $row = $(this).closest(".ldb-ref-edit-row");
    validateReferenceRowMobile($row);
  });

  $(document).on("change", ".ref-input-relation", function() {
    var val = ($(this).val() || "").trim();
    if (val) {
      $(this).next(".select2-container").find(".select2-selection").css({"border": "", "background-color": "", "box-shadow": ""});
    }
    var $row = $(this).closest(".ldb-ref-edit-row");
    validateReferenceRowMobile($row);
  });
}

function showSectionEdit(section) {
  var $card = $("#card-" + section);
  $card.find("#" + section + "-view").addClass("d-none");
  $card.find("#" + section + "-edit").removeClass("d-none");
  $card.find(".btn-edit-section[data-section='" + section + "']").hide();
  $card.find(".btn-cancel-section[data-section='" + section + "']").show();

  if (section === 'basic') {
    validateDOB();
    updateIDProofInputState();
    validateIDProofNo();
  } else if (section === 'bank') {
    validateBankName($("#bkef-bankname"), $("#bkef-bankname-feedback"), true);
    validateAccountNumber($("#bkef-accountnumber"), $("#bkef-accountnumber-feedback"), true);
    validateIFSCCode($("#bkef-ifsccode"), $("#bkef-ifsccode-feedback"), true);
  } else if (section === 'contact') {
    if (labourData) {
      $("#coef-mobile").val(labourData.MobileNo || "");
      $("#coef-emergency").val(labourData.emergencycontactno || "");
    }
    validateMobileInput($("#coef-mobile"), $("#coef-mobile-feedback"), true);
    validateMobileInput($("#coef-emergency"), $("#coef-emergency-feedback"), false);
  } else if (section === 'address') {
    if (labourData) {
      $("#adef-current").val(labourData.AddressLine1 || labourData.address || "");
      $("#adef-permanent").val(labourData.AddressLine2 || "");
      $("#adef-tehsil").val(labourData.tahseel || "");
      $("#adef-cityvillage").val(labourData.city || "");
      if (labourData.AddressLine1 && labourData.AddressLine1 === labourData.AddressLine2) {
        $("#adef-same-as-current").prop("checked", true);
      } else {
        $("#adef-same-as-current").prop("checked", false);
      }

      loadAddressStatesDropdown(function() {
        if (labourData.stateid) {
          $("#adef-state").val(labourData.stateid).trigger('change.select2');
          loadAddressDistrictsDropdown(labourData.stateid, labourData.districtid || "", function() {
            if (labourData.districtid) {
              $("#adef-district").val(labourData.districtid).trigger('change.select2');
            }
          });
        } else {
          $("#adef-state").val("").trigger('change.select2');
          $("#adef-district").html('<option value="">Select State First</option>').prop("disabled", true).trigger('change.select2');
        }
      });
    } else {
      loadAddressStatesDropdown();
    }
  } else if (section === 'skills') {
    loadLocationsDropdown();
    if (labourData) {
      $("#skef-grafting").val(labourData.grafting_skill || "no");
      $("#skef-location").val(labourData.locationid || labourData.LocationId || labourData.workingLocation || "");
      $("#skef-wage").val(labourData.PayPerDayAmount || labourData.PerDayPayment || "");
    }
  } else if (section === 'refs') {
    renderReferenceEditInputs(labourRefs || []);
  }
}

function hideSectionEdit(section) {
  var $card = $("#card-" + section);
  $card.find("#" + section + "-edit").addClass("d-none");
  $card.find("#" + section + "-view").removeClass("d-none");
  $card.find(".btn-cancel-section[data-section='" + section + "']").hide();
  $card.find(".btn-edit-section[data-section='" + section + "']").show();
}

/* ====================================================
   SAVE INDIVIDUAL CARD (SECTION) EDITS
   ==================================================== */
function initSectionEdits() {
  $(document).on("submit", ".form-edit-section", function(e) {
    e.preventDefault();
    var $form = $(this);
    var section = $form.data("section");
    var $btn = $form.find("button[type='submit']");
    var origHtml = $btn.html();
    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

    var formData = new FormData();
    formData.append("action", "UpdateLabourSpecificDetails");
    formData.append("employeeID", labourData.EmployeeID);
    formData.append("companyid", companyid);
    formData.append("subcompanyid", subcompanyid);
    formData.append("projectid", projectid);
    formData.append("section", section);

    if (section === 'basic') {
      if (!validateIDProofNo()) {
        $btn.prop("disabled", false).html(origHtml);
        Swal.fire({
          icon: "error",
          title: "Validation Error",
          text: "Please enter a valid document unique number according to the selected document type."
        });
        return;
      }

      formData.append("firstName", $("#bef-firstname").val());
      formData.append("middleName", $("#bef-middlename").val());
      formData.append("lastName", $("#bef-lastname").val());
      formData.append("gender", $("#bef-gender").val());
      formData.append("dateOfBirth", $("#bef-dob").val());
      formData.append("maritalStatus", $("#bef-marital").val());
      formData.append("idProofType", $("#bef-idproof").val());
      formData.append("idProofNo", $("#bef-idproofno").val());
      formData.append("fatherName", $("#bef-fathername").val());
      formData.append("motherName", $("#bef-mothername").val());
      formData.append("spouseName", $("#bef-spousename").val());
      
      var fileInput = $("#bef-idproofdoc")[0];
      if (fileInput && fileInput.files && fileInput.files[0]) {
        formData.append("idProofDoc", fileInput.files[0]);
      }
    } else if (section === 'bank') {
      var isBankNameValid = validateBankName($("#bkef-bankname"), $("#bkef-bankname-feedback"), true);
      var isAccNoValid = validateAccountNumber($("#bkef-accountnumber"), $("#bkef-accountnumber-feedback"), true);
      var isIfscValid = validateIFSCCode($("#bkef-ifsccode"), $("#bkef-ifsccode-feedback"), true);

      var bankName = ($("#bkef-bankname").val() || "").trim().toUpperCase();
      var accNo = ($("#bkef-accountnumber").val() || "").trim();
      var ifsc = ($("#bkef-ifsccode").val() || "").trim().toUpperCase();

      if (!isBankNameValid || !isAccNoValid || !isIfscValid) {
        $btn.prop("disabled", false).html(origHtml);
        var errMsgs = [];
        if (!isBankNameValid) errMsgs.push("Valid Bank Name");
        if (!isAccNoValid) errMsgs.push("Valid Account Number (9-18 digits)");
        if (!isIfscValid) errMsgs.push("Valid IFSC Code (11 characters starting with 4 letters and 5th char 0)");
        
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'warning',
          title: 'Please enter ' + errMsgs.join(', ') + '.',
          showConfirmButton: false,
          timer: 4500,
          timerProgressBar: true
        });
        return;
      }

      formData.append("bankName", bankName);
      formData.append("accountNumber", accNo);
      formData.append("ifscCode", ifsc);

      var bankFileInput = $("#bkef-bankdoc")[0];
      if (bankFileInput && bankFileInput.files && bankFileInput.files[0]) {
        formData.append("bankDoc", bankFileInput.files[0]);
      }
    } else if (section === 'contact') {
      var isMobileValid = validateMobileInput($("#coef-mobile"), $("#coef-mobile-feedback"), true);
      var isEmergencyValid = validateMobileInput($("#coef-emergency"), $("#coef-emergency-feedback"), false);

      if (!isMobileValid || !isEmergencyValid) {
        $btn.prop("disabled", false).html(origHtml);
        var msg = !isMobileValid ? 'Please enter a valid 10-digit mobile number.' : 'Please enter a valid 10-digit emergency contact number.';
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'warning',
          title: msg,
          showConfirmButton: false,
          timer: 3500,
          timerProgressBar: true
        });
        return;
      }
      formData.append("mobileNo", $("#coef-mobile").val());
      formData.append("emergencycontactno", $("#coef-emergency").val());
    } else if (section === 'address') {
      var stateVal = $("#adef-state").val() || "";
      var districtVal = $("#adef-district").val() || "";
      var cityVal = ($("#adef-cityvillage").val() || "").trim();

      var missing = [];
      if (!stateVal) missing.push("State");
      if (!districtVal) missing.push("District");
      if (!cityVal) missing.push("City/Village");

      if (missing.length > 0) {
        $btn.prop("disabled", false).html(origHtml);
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'warning',
          title: 'Please enter/select mandatory fields: ' + missing.join(', ') + '.',
          showConfirmButton: false,
          timer: 3500,
          timerProgressBar: true
        });
        return;
      }

      formData.append("current_address", $("#adef-current").val());
      formData.append("permanent_address", $("#adef-permanent").val());
      formData.append("stateid", stateVal);
      formData.append("districtid", districtVal);
      formData.append("tahseel", ($("#adef-tehsil").val() || "").trim());
      formData.append("city", cityVal);
      formData.append("address", $("#adef-current").val());
    } else if (section === 'skills') {
      formData.append("grafting_skill", $("#skef-grafting").val());
      formData.append("workingLocation", $("#skef-location").val());
      formData.append("PayPerDayAmount", $("#skef-wage").val());
    } else if (section === 'refs') {
      var refValidationErrors = [];

      $form.find(".ldb-ref-edit-row").each(function(idx) {
        var personNum = idx + 1;
        var $row = $(this);
        var $nameInput = $row.find(".ref-input-name");
        var $relationSelect = $row.find(".ref-input-relation");
        var $mobileInput = $row.find(".ref-input-mobile");
        var $feedback = $row.find(".ref-mobile-feedback");
        var $select2Container = $relationSelect.next(".select2-container");

        var name = ($nameInput.val() || "").trim();
        var relation = ($relationSelect.val() || "").trim();
        var mobile = ($mobileInput.val() || "").trim();

        // Row 1 is mandatory; Row 2 is mandatory only if any of its fields are filled
        var isRequired = (idx === 0) || !!(name || relation || mobile);

        if (isRequired) {
          var missingFields = [];

          if (!name) {
            missingFields.push("Name");
            $nameInput.addClass("is-invalid").css({
              "border": "1.5px solid #ef4444",
              "background-color": "#fef2f2",
              "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"
            });
          } else {
            $nameInput.removeClass("is-invalid").css({"border": "", "background-color": "", "box-shadow": ""});
          }

          if (!relation) {
            missingFields.push("Relation");
            $select2Container.find(".select2-selection").css({
              "border": "1.5px solid #ef4444",
              "background-color": "#fef2f2",
              "box-shadow": "0 0 0 3px rgba(239, 68, 68, 0.15)"
            });
          } else {
            $select2Container.find(".select2-selection").css({"border": "", "background-color": "", "box-shadow": ""});
          }

          var isMobileValid = validateMobileInput($mobileInput, $feedback, true);
          if (!isMobileValid) {
            if (!mobile) {
              missingFields.push("Mobile");
            } else {
              refValidationErrors.push("Reference Person " + personNum + ": Invalid 10-digit mobile number");
            }
          }

          if (missingFields.length > 0) {
            refValidationErrors.push("Reference Person " + personNum + " requires " + missingFields.join(", "));
          }
        } else {
          $nameInput.removeClass("is-invalid").css({"border": "", "background-color": "", "box-shadow": ""});
          $select2Container.find(".select2-selection").css({"border": "", "background-color": "", "box-shadow": ""});
          $mobileInput.removeClass("is-invalid is-valid").css({"border": "", "background-color": "", "box-shadow": ""});
          $feedback.removeClass("is-invalid is-valid").html('');
        }
      });

      if (refValidationErrors.length > 0) {
        $btn.prop("disabled", false).html(origHtml);
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'warning',
          title: 'Please fill all mandatory fields: ' + refValidationErrors[0] + '.',
          showConfirmButton: false,
          timer: 4000,
          timerProgressBar: true
        });
        return;
      }

      $form.find(".ldb-ref-edit-row").each(function() {
        var name = $(this).find(".ref-input-name").val();
        var relation = $(this).find(".ref-input-relation").val();
        var mobile = $(this).find(".ref-input-mobile").val();
        formData.append("ref_name[]", name);
        formData.append("ref_relation[]", relation);
        formData.append("ref_mobile[]", mobile);
      });
    }

    $.ajax({
      type: "POST",
      url: routurl + "modal/hrm_laboursalarymodal.php?action=UpdateLabourSpecificDetails",
      data: formData,
      contentType: false,
      processData: false,
      cache: false,
      success: function(res) {
        $btn.prop("disabled", false).html(origHtml);
        try {
          var data = (typeof res === "string") ? JSON.parse(res) : res;
          if (data.status === "Success") {
            Swal.fire({
              icon: "success",
              title: "Updated!",
              text: data.message || "Changes saved successfully.",
              toast: true, position: "top-end",
              showConfirmButton: false, timer: 3000
            });
            if (labourData && labourData.EmployeeID) {
              loadLabourProfile(labourData.EmployeeID); // Reload full profile data from backend
            }
            hideSectionEdit(section);
          } else {
            Swal.fire("Error", data.message || "Could not save details.", "error");
          }
        } catch(e) {
          Swal.fire("Error", "Unexpected response from server.", "error");
        }
      },
      error: function() {
        $btn.prop("disabled", false).html(origHtml);
        Swal.fire("Error", "Network error. Please try again.", "error");
      }
    });
  });
}

/* ====================================================
   PROFILE PHOTO UPLOAD
   ==================================================== */
function initProfilePhotoUpload() {
  $(document).on("change", "#profilePhotoInput", function() {
    var file = this.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      Swal.fire("Error", "Please select a valid image file.", "error");
      return;
    }

    var formData = new FormData();
    formData.append("profilePhoto", file);
    formData.append("employeeID", labourData.EmployeeID);
    formData.append("companyid", companyid);
    formData.append("subcompanyid", subcompanyid);
    formData.append("projectid", projectid);

    Swal.fire({
      title: "Uploading Image...",
      text: "Please wait",
      allowOutsideClick: false,
      didOpen: function() { Swal.showLoading(); }
    });

    $.ajax({
      url: routurl + "modal/hrm_laboursalarymodal.php?action=UploadLabourProfilePhoto",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function(raw) {
        Swal.close();
        try {
          var response = JSON.parse(raw);
          if (response.status === "Success") {
            Swal.fire({
              icon: "success",
              title: "Photo Updated!",
              text: "Profile photo changed successfully.",
              toast: true, position: "top-end",
              showConfirmButton: false, timer: 3000
            });
            var finalPath = response.path;
            if (finalPath.startsWith('../')) {
              finalPath = finalPath.substring(3);
            }
            var absoluteUrl = routurl + finalPath;
            $("#hero-photo").attr("src", absoluteUrl);
            labourData.profile_photo = absoluteUrl;
          } else {
            Swal.fire("Error", response.message || "Failed to update profile photo.", "error");
          }
        } catch(e) {
          Swal.fire("Error", "Failed to parse response.", "error");
        }
      },
      error: function() {
        Swal.close();
        Swal.fire("Error", "Connection error uploading photo.", "error");
      }
    });
  });
}

/* ====================================================
   DOWNLOAD ID CARD
   ==================================================== */
function downloadIDCard(empId) {
  Swal.fire({
    title: "Generating ID Card",
    text: "Please wait...",
    allowOutsideClick: false,
    didOpen: function() { Swal.showLoading(); }
  });
  $.post(routurl + "modal/hrm_laboursalarymodal.php?action=GenerateIDCard", {
    employeeID   : empId,
    companyid    : companyid,
    subcompanyid : subcompanyid,
    projectid    : projectid
  }, function(res) {
    try {
      var data = (typeof res === "string") ? JSON.parse(res) : res;
      if (data.status === "Success") {
        var cleanPath = data.path;
        if (cleanPath.startsWith('../')) {
          cleanPath = cleanPath.substring(3); // strip leading "../"
        }
        var absoluteUrl = routurl + cleanPath;
        if (absoluteUrl.indexOf('?') === -1) {
          absoluteUrl += "?t=" + new Date().getTime();
        } else {
          absoluteUrl += "&t_client=" + new Date().getTime();
        }

        var link = document.createElement("a");
        link.href = absoluteUrl;
        link.download = "ID_Card_" + empId + ".pdf";
        link.target = "_blank";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        Swal.close();
      } else {
        Swal.fire("Error", data.message || "Could not generate ID-- card.", "error");
      }
    } catch(e) { Swal.fire("Error", "Unexpected response.", "error"); }
  });
}

/* ====================================================
   ATTENDANCE — Month Strip
   ==================================================== */
function buildMonthStrip() {
  updateYearLabel();
  renderMonthButtons();

  $("#prevYearBtn").on("click", function() {
    currentYear--;
    updateYearLabel();
    renderMonthButtons();
    selectedMonth = null;
    $("#att-month-detail").hide();
    $("#att-empty").show();
    loadAttendanceSummaryForYear(currentEmployeeID, currentYear);
  });
  $("#nextYearBtn").on("click", function() {
    if (currentYear >= new Date().getFullYear()) return;
    currentYear++;
    updateYearLabel();
    renderMonthButtons();
    selectedMonth = null;
    $("#att-month-detail").hide();
    $("#att-empty").show();
    loadAttendanceSummaryForYear(currentEmployeeID, currentYear);
  });
}

function updateYearLabel() {
  $("#currentYearLabel").text(currentYear);
  var nowYear = new Date().getFullYear();
  $("#nextYearBtn").prop("disabled", currentYear >= nowYear);
}

function renderMonthButtons() {
  var html = "";
  var today = new Date();
  var thisYear = today.getFullYear();
  var thisMonth = today.getMonth() + 1; // 1-12

  MONTHS.forEach(function(mon, idx) {
    var monthNum = idx + 1;
    var key = currentYear + "-" + monthNum;
    var summary = attendanceSummary[key];
    var days = summary ? summary.days : 0;
    var net = summary ? summary.net : 0;

    var shouldShow = (currentYear < thisYear) || 
                     (monthNum <= thisMonth) || 
                     (summary && (days > 0 || net > 0));

    if (shouldShow) {
      var isSelected = (selectedMonth === monthNum);
      var selectedCls = isSelected ? " selected" : "";
      var hasCls = summary && (days > 0 || net > 0) ? " has-data" : "";
      var daysInMonth = new Date(currentYear, monthNum, 0).getDate();
      var daysDisplay = days + "/" + daysInMonth;

      html += '<button class="ldb-month-btn' + hasCls + selectedCls + '" data-month="' + monthNum + '">' +
        '<span class="ldb-mon-abbr">' + mon + '</span>' +
        '<span class="ldb-mon-days">' + daysDisplay + '</span>' +
        '<span class="ldb-mon-dot"></span>' +
        '</button>';
    }
  });
  $("#monthStrip").html(html);

  // Click handler
  $(document).off("click", ".ldb-month-btn").on("click", ".ldb-month-btn", function() {
    selectedMonth = parseInt($(this).data("month"), 10);
    $(".ldb-month-btn").removeClass("selected");
    $(this).addClass("selected");
    loadAttendanceForMonth(currentEmployeeID, currentYear, selectedMonth);
  });
}

/* ====================================================
   LOAD ATTENDANCE SUMMARY (for dots + day counts)
   ==================================================== */
function loadAttendanceSummaryForYear(empId, year) {
  $.post("modal/labourdash_modal.php?action=GetLabourAttendanceSummary", {
    employeeID   : empId,
    year         : year,
    companyid    : companyid,
    subcompanyid : subcompanyid,
    projectid    : projectid
  }, function(res) {
    try {
      var data = (typeof res === "string") ? JSON.parse(res) : res;
      if (data.status === "Success" && data.summary) {
        attendanceSummary = data.summary;
        renderMonthButtons(); // Re-render with dots
      }
    } catch(e) {}
  });
}

/* ====================================================
   ATTENDANCE FORMATTING HELPERS
   ==================================================== */
function formatTime(dtStr) {
  if (!dtStr || dtStr === '0000-00-00 00:00:00') return '—';
  var timePart = dtStr.includes(' ') ? dtStr.split(' ')[1] : dtStr;
  var p = timePart.split(':');
  var hrs = parseInt(p[0] || 0);
  var mins = p[1] || '00';
  var ampm = hrs >= 12 ? 'PM' : 'AM';
  var displayHrs = hrs % 12;
  if (displayHrs === 0) displayHrs = 12;
  var displayHrsStr = String(displayHrs).padStart(2, '0');
  return displayHrsStr + ':' + mins + ' ' + ampm;
}

function formatHrs(wh) {
  if (!wh || wh === '00:00:00') return '—';
  var p = wh.split(':');
  var hrs = parseInt(p[0] || 0);
  var mins = parseInt(p[1] || 0);
  if (mins === 0) return hrs + 'h';
  return hrs + 'h ' + mins + 'm';
}

function formatDateNice(dateStr) {
  if (!dateStr) return '—';
  var parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  var yr = parts[0];
  var mo = parseInt(parts[1]) - 1;
  var day = parts[2];
  return day + ' ' + MONTHS[mo] + ' ' + yr;
}

/* ====================================================
   LOAD ATTENDANCE FOR SPECIFIC MONTH
   ==================================================== */
function loadAttendanceForMonth(empId, year, month) {
  selectedMonth = parseInt(month, 10);
  currentYear = parseInt(year, 10);
  $(".ldb-month-btn").removeClass("selected");
  $('.ldb-month-btn[data-month="' + selectedMonth + '"]').addClass("selected");

  $("#att-empty").hide();
  $("#att-month-detail").show();
  $("#att-table-body").html('<tr><td colspan="8" class="ldb-empty-row"><span class="spinner-border spinner-border-sm me-2"></span>Loading attendance...</td></tr>');
  $("#att-mark-in,#att-present,#att-missing,#att-early,#att-net").text("—");

  var monthName = MONTHS[month - 1] + " " + year;
  $("#att-period-label").text(monthName);

  $.post("modal/labourdash_modal.php?action=GetLabourMonthAttendance", {
    employeeID   : empId,
    year         : year,
    month        : month,
    companyid    : companyid,
    subcompanyid : subcompanyid,
    projectid    : projectid
  }, function(res) {
    try {
      var data = (typeof res === "string") ? JSON.parse(res) : res;
      if (data.status === "Success" && data.records && data.records.length > 0) {
        var records = data.records;
        currentMonthlyRecords = records;
        currentAttFilter = "all";
        $(".ldb-att-stat-card").removeClass("active");

        var markIn = 0, fullDays = 0, halfDays = 0, shortDays = 0, missingOut = 0, payableDays = 0.0;

        records.forEach(function(rec) {
          var ciMN = rec.checkintime_mn;
          var coMN = rec.checkouttime_mn;
          var whMN = rec.workinghours_mn;

          var ciDP = rec.checkintime_dp;
          var coDP = rec.checkouttime_dp;
          var whDP = rec.workinghours_dp;

          var hasCI = (ciMN && ciMN !== '0000-00-00 00:00:00') || (ciDP && ciDP !== '0000-00-00 00:00:00');
          var hasCO = (coMN && coMN !== '0000-00-00 00:00:00') || (coDP && coDP !== '0000-00-00 00:00:00');

          if (hasCI) {
            markIn++;
            var maxHrs = 0.0;
            if (coMN && whMN && whMN !== '00:00:00') {
              var p = whMN.split(':');
              maxHrs = Math.max(maxHrs, parseInt(p[0] || 0, 10) + (parseInt(p[1] || 0, 10) / 60));
            }
            if (coDP && whDP && whDP !== '00:00:00') {
              var p = whDP.split(':');
              maxHrs = Math.max(maxHrs, parseInt(p[0] || 0, 10) + (parseInt(p[1] || 0, 10) / 60));
            }

            if (hasCO) {
              if (maxHrs >= 6.0) {
                fullDays++;
                payableDays += 1.0;
              } else if (maxHrs > 2.0 && maxHrs < 6.0) {
                halfDays++;
                payableDays += 0.5;
              } else {
                shortDays++;
                // maxHrs <= 2.0 is absent (0.0 payable)
              }
            } else {
              missingOut++;
              // missing out (0.0 payable)
            }
          }
        });

        var presentDaysCount = fullDays + halfDays;

        $("#att-mark-in").text(markIn);
        $("#att-present").text(presentDaysCount);
        $("#att-present-breakdown").text(fullDays + " Full, " + halfDays + " Half");
        $("#att-missing").text(missingOut);
        $("#att-early").text(shortDays);
        $("#att-net").text(payableDays.toFixed(1));
        $("#att-payable-breakdown").text("(" + fullDays + " \u00d7 1.0) + (" + halfDays + " \u00d7 0.5)");

        // Render filtered records
        renderFilteredAttendance();

        // Update summary cache
        var key = year + "-" + month;
        attendanceSummary[key] = { days: payableDays };
        renderMonthButtons();

      } else {
        currentMonthlyRecords = [];
        $("#att-table-body").html('<tr><td colspan="8" class="ldb-empty-row">No attendance records for ' + MONTHS[month-1] + ' ' + year + '.</td></tr>');
        $("#att-mark-in,#att-present,#att-missing,#att-early,#att-net").text("0");
        $("#att-present-breakdown,#att-payable-breakdown").text("—");
      }
    } catch(e) {
      console.error(e);
      $("#att-table-body").html('<tr><td colspan="8" class="ldb-empty-row text-danger">Error loading records.</td></tr>');
    }
  });
}

/* ====================================================
   RENDER FILTERED ATTENDANCE
   ==================================================== */
function renderFilteredAttendance() {
  var records = currentMonthlyRecords;
  var tbody = "";

  var filteredRecords = records.filter(function(rec) {
    var ciMN = rec.checkintime_mn;
    var coMN = rec.checkouttime_mn;
    var whMN = rec.workinghours_mn;

    var ciDP = rec.checkintime_dp;
    var coDP = rec.checkouttime_dp;
    var whDP = rec.workinghours_dp;

    var hasCI = (ciMN && ciMN !== '0000-00-00 00:00:00') || (ciDP && ciDP !== '0000-00-00 00:00:00');
    var hasCO = (coMN && coMN !== '0000-00-00 00:00:00') || (coDP && coDP !== '0000-00-00 00:00:00');

    var maxHrs = 0.0;
    if (coMN && whMN && whMN !== '00:00:00') {
      var p = whMN.split(':');
      maxHrs = Math.max(maxHrs, parseInt(p[0] || 0, 10) + (parseInt(p[1] || 0, 10)/60));
    }
    if (coDP && whDP && whDP !== '00:00:00') {
      var p = whDP.split(':');
      maxHrs = Math.max(maxHrs, parseInt(p[0] || 0, 10) + (parseInt(p[1] || 0, 10)/60));
    }

    if (currentAttFilter === "mark-in") {
      return hasCI;
    } else if (currentAttFilter === "present") {
      return hasCI && hasCO && maxHrs > 2.0; // Full Day + Half Day
    } else if (currentAttFilter === "missing") {
      return hasCI && !hasCO;
    } else if (currentAttFilter === "early") {
      return hasCI && hasCO && maxHrs <= 2.0; // Short Timing / Absent
    } else if (currentAttFilter === "payable") {
      return hasCI && hasCO && maxHrs > 2.0;
    }
    return true; // "all"
  });

  if (filteredRecords.length > 0) {
    filteredRecords.forEach(function(rec) {
      var ciMN = rec.checkintime_mn;
      var coMN = rec.checkouttime_mn;
      var whMN = rec.workinghours_mn;

      var ciDP = rec.checkintime_dp;
      var coDP = rec.checkouttime_dp;
      var whDP = rec.workinghours_dp;

      var hasCI = (ciMN && ciMN !== '0000-00-00 00:00:00') || (ciDP && ciDP !== '0000-00-00 00:00:00');
      var hasCO = (coMN && coMN !== '0000-00-00 00:00:00') || (coDP && coDP !== '0000-00-00 00:00:00');

      var statusBadgeStr = "";
      var rowStyle = "";

      if (hasCI) {
        var maxHrs = 0.0;
        if (coMN && whMN && whMN !== '00:00:00') {
          var p = whMN.split(':');
          maxHrs = Math.max(maxHrs, parseInt(p[0] || 0, 10) + (parseInt(p[1] || 0, 10)/60));
        }
        if (coDP && whDP && whDP !== '00:00:00') {
          var p = whDP.split(':');
          maxHrs = Math.max(maxHrs, parseInt(p[0] || 0, 10) + (parseInt(p[1] || 0, 10)/60));
        }

        if (hasCO) {
          if (maxHrs >= 6.0) {
            statusBadgeStr = '<span class="ldb-status" style="background-color:#dcfce7; color:#15803d; border:1px solid #bbf7d0; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;"><i class="ti ti-circle-check"></i> Full Day</span>';
          } else if (maxHrs > 2.0 && maxHrs < 6.0) {
            statusBadgeStr = '<span class="ldb-status" style="background-color:#fef3c7; color:#b45309; border:1px solid #fde68a; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;"><i class="ti ti-adjustments-half"></i> Half Day</span>';
          } else {
            statusBadgeStr = '<span class="ldb-status" style="background-color:#fee2e2; color:#b91c1c; border:1px solid #fecaca; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;" title="Working Hours <= 2 hrs (Absent)"><i class="ti ti-clock-off"></i> Absent (Short Time)</span>';
            rowStyle = 'style="background-color: #fff1f2 !important;" class="ldb-row-short-time"';
          }
        } else {
          statusBadgeStr = '<span class="ldb-status" style="background-color:#fee2e2; color:#ef4444; border:1px solid #fecaca; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;"><i class="ti ti-alert-circle"></i> Missing Out</span>';
        }
      } else {
        statusBadgeStr = '<span class="ldb-status" style="background-color:#f3f4f6; color:#6b7280; border:1px solid #e5e7eb; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;"><i class="ti ti-circle-x"></i> Absent</span>';
      }

      var rawCI = '';
      if (ciMN && ciMN !== '0000-00-00 00:00:00') {
        rawCI = ciMN.includes(' ') ? ciMN.split(' ')[1].substring(0, 5) : ciMN.substring(0, 5);
      } else if (ciDP && ciDP !== '0000-00-00 00:00:00') {
        rawCI = ciDP.includes(' ') ? ciDP.split(' ')[1].substring(0, 5) : ciDP.substring(0, 5);
      }

      var rawCO = '';
      if (coMN && coMN !== '0000-00-00 00:00:00') {
        rawCO = coMN.includes(' ') ? coMN.split(' ')[1].substring(0, 5) : coMN.substring(0, 5);
      } else if (coDP && coDP !== '0000-00-00 00:00:00') {
        rawCO = coDP.includes(' ') ? coDP.split(' ')[1].substring(0, 5) : coDP.substring(0, 5);
      }

      if (!rawCI) rawCI = '09:00';

      var editBtn = '<button class="btn-edit-att" data-date="' + rec.attendancedate + '" data-in="' + rawCI + '" data-out="' + rawCO + '" title="Edit Attendance"><i class="ti ti-edit"></i> Edit</button>';
      var removeBtn = '';
      if (hasCI) {
        removeBtn = ' <button class="btn-remove-att" data-id="' + rec.attendanceid + '" data-date="' + rec.attendancedate + '" title="Remove Attendance"><i class="ti ti-trash"></i> Remove</button>';
      }
      var statusCell = '<div class="d-flex align-items-center justify-content-center">' + statusBadgeStr + editBtn + removeBtn + '</div>';

      var coMNStr = formatTime(coMN);
      if ((ciMN && ciMN !== '0000-00-00 00:00:00') && (!coMN || coMN === '0000-00-00 00:00:00' || coMN === '')) {
        coMNStr = '<span class="badge bg-danger-subtle text-danger" style="font-size:11px; font-weight:700; padding:2px 6px; border:1px solid #fecaca;" title="Punchout Missing">MIS</span>';
      }

      var coDPStr = formatTime(coDP);
      if ((ciDP && ciDP !== '0000-00-00 00:00:00') && (!coDP || coDP === '0000-00-00 00:00:00' || coDP === '')) {
        coDPStr = '<span class="badge bg-danger-subtle text-danger" style="font-size:11px; font-weight:700; padding:2px 6px; border:1px solid #fecaca;" title="Punchout Missing">MIS</span>';
      }

      tbody += '<tr ' + rowStyle + '>' +
        '<td>' + formatDateNice(rec.attendancedate) + '</td>' +
        '<td>' + formatTime(ciMN) + '</td>' +
        '<td>' + coMNStr + '</td>' +
        '<td>' + formatHrs(whMN) + '</td>' +
        '<td>' + formatTime(ciDP) + '</td>' +
        '<td>' + coDPStr + '</td>' +
        '<td>' + formatHrs(whDP) + '</td>' +
        '<td class="text-center">' + statusCell + '</td>' +
        '</tr>';
    });
    $("#att-table-body").html(tbody);
  } else {
    $("#att-table-body").html('<tr><td colspan="8" class="ldb-empty-row">No matching attendance records found.</td></tr>');
  }
}

/* ====================================================
   CLICK HANDLERS FOR SUMMARY STAT CARDS (FILTERING)
   ==================================================== */
$(document).off("click", ".ldb-att-stat-card").on("click", ".ldb-att-stat-card", function() {
  var filter = $(this).data("filter");
  if (!filter) return;

  if (currentAttFilter === filter) {
    currentAttFilter = "all";
    $(".ldb-att-stat-card").removeClass("active");
  } else {
    currentAttFilter = filter;
    $(".ldb-att-stat-card").removeClass("active");
    $(this).addClass("active");
  }

  renderFilteredAttendance();
});

/* ====================================================
   EXPORT ATTENDANCE TO EXCEL
   ==================================================== */
$(document).off("click", "#exportAttExcelBtn").on("click", "#exportAttExcelBtn", function(e) {
  e.preventDefault();
  if (typeof XLSX === "undefined") {
    var script = document.createElement('script');
    script.src = "https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js";
    script.onload = function() {
      executeAttendanceExcelExport();
    };
    document.head.appendChild(script);
  } else {
    executeAttendanceExcelExport();
  }
});

function executeAttendanceExcelExport() {
  const table = document.getElementById("attRecordsTable");
  if (!table) return;

  const labourName = $("#topbar-name").text() || "Labour";
  const selectedPeriod = $("#att-period-label").text() || "";
  const titleText = `Attendance Report - ${labourName} - ${selectedPeriod}`;

  // Clone table
  const clone = table.cloneNode(true);

  // Remove edit buttons from clone
  clone.querySelectorAll("button").forEach(btn => btn.remove());

  // Clean up cells for Excel
  clone.querySelectorAll("td").forEach(td => {
    const statusText = td.textContent.trim();
    if (statusText) {
      td.textContent = statusText;
    }
  });

  const ws = XLSX.utils.aoa_to_sheet([[titleText], []]);
  XLSX.utils.sheet_add_dom(ws, clone, { origin: "A3" });

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Attendance");

  const fileName = `Attendance_${labourName.replace(/\s+/g, "_")}_${selectedPeriod.replace(/\s+/g, "_")}.xlsx`;
  XLSX.writeFile(wb, fileName);
}

/* ====================================================
   PAYMENTS — Filter & Load
   ==================================================== */
function setDefaultPayDates() {
  var now = new Date();
  var firstDay = new Date(now.getFullYear(), now.getMonth() - 2, 1);
  var lastDay  = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  $("#payDateFrom").val(toYMD(firstDay));
  $("#payDateTo").val(toYMD(lastDay));
}

function initPayFilterControls(empId) {
  $("#loadPaymentsBtn").on("click", function() {
    var from = $("#payDateFrom").val();
    var to   = $("#payDateTo").val();
    if (!from || !to) {
      Swal.fire("Missing Dates", "Please select both From and To dates.", "warning");
      return;
    }
    loadPaymentRecords(empId, from, to);
  });

  $("#clearPayFilterBtn").on("click", function() {
    setDefaultPayDates();
    $("#pay-summary-strip").hide();
    $("#pay-table-body").html('<tr><td colspan="10" class="ldb-empty-row">Use filters above to load payment records</td></tr>');
  });
}

function loadPaymentRecords(empId, from, to) {
  var $btn = $("#loadPaymentsBtn");
  $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span> Loading...');
  $("#pay-table-body").html('<tr><td colspan="10" class="ldb-empty-row"><span class="spinner-border spinner-border-sm me-2"></span>Fetching payment records...</td></tr>');
  $("#pay-summary-strip").hide();

  $.post("modal/labourdash_modal.php?action=GetLabourPaymentHistory", {
    employeeID   : empId,
    datefrom     : from,
    dateto       : to,
    companyid    : companyid,
    subcompanyid : subcompanyid,
    projectid    : projectid
  }, function(res) {
    $btn.prop("disabled", false).html('<i class="ti ti-search"></i> Load Payments');
    try {
      var data = (typeof res === "string") ? JSON.parse(res) : res;
      if (data.status === "Success" && data.records && data.records.length > 0) {
        renderPaymentRecords(data.records);
      } else {
        $("#pay-table-body").html('<tr><td colspan="10" class="ldb-empty-row">No payment records found for the selected period.</td></tr>');
      }
    } catch(e) {
      $("#pay-table-body").html('<tr><td colspan="10" class="ldb-empty-row text-danger">Error loading payment data.</td></tr>');
    }
  }).fail(function() {
    $btn.prop("disabled", false).html('<i class="ti ti-search"></i> Load Payments');
    $("#pay-table-body").html('<tr><td colspan="10" class="ldb-empty-row text-danger">Server error. Please try again.</td></tr>');
  });
}

function renderPaymentRecords(records) {
  var totalPaid    = 0;
  var totalPending = 0;
  var totalDays    = 0;
  var sno = 1;
  var tbody = "";

  records.forEach(function(rec) {
    var days     = parseFloat(rec.PresentDays || 0);
    var wage     = parseFloat(rec.PerDayPayment || 0);
    var basic    = parseFloat(rec.BasicAmount || (days * wage));
    var inc      = parseFloat(rec.IncentiveGrafting || 0) + parseFloat(rec.IncentiveSeasional || 0);
    var overtime = parseFloat(rec.OvertimeAmount || 0);
    var net      = parseFloat(rec.NetPayable || (basic + inc + overtime));
    var status   = (rec.PaymentStatus || rec.Status || "pending").toLowerCase();
    var payDate  = rec.PaymentDate || rec.TransactionDate || "—";

    totalDays += days;
    if (status === "paid" || status === "disbursed") {
      totalPaid += net;
    } else {
      totalPending += net;
    }

    tbody += '<tr>' +
      '<td class="text-muted">' + sno++ + '</td>' +
      '<td>' + formatDateRange(rec.DateFrom, rec.DateTo) + '</td>' +
      '<td class="text-center fw-bold">' + days + '</td>' +
      '<td class="text-end">₹ ' + fmtNum(wage) + '</td>' +
      '<td class="text-end">₹ ' + fmtNum(basic) + '</td>' +
      '<td class="text-end">₹ ' + fmtNum(inc) + '</td>' +
      '<td class="text-end">₹ ' + fmtNum(overtime) + '</td>' +
      '<td class="text-end text-success fw-bold">₹ ' + fmtNum(net) + '</td>' +
      '<td class="text-center">' + statusBadge(status) + '</td>' +
      '<td class="text-center text-muted">' + fmtDate(payDate) + '</td>' +
      '</tr>';
  });

  $("#pay-table-body").html(tbody);

  // Summary strip
  $("#pay-total-records").text(records.length);
  $("#pay-total-paid").text("₹ " + fmtNum(totalPaid));
  $("#pay-total-pending").text("₹ " + fmtNum(totalPending));
  $("#pay-total-days").text(totalDays);
  $("#pay-summary-strip").fadeIn(300);
}

/* ====================================================
   HELPER FUNCTIONS
   ==================================================== */
function fmtNum(n) {
  var num = parseFloat(n) || 0;
  return num.toLocaleString("en-IN", { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function toYMD(date) {
  var y = date.getFullYear();
  var m = String(date.getMonth() + 1).padStart(2, "0");
  var d = String(date.getDate()).padStart(2, "0");
  return y + "-" + m + "-" + d;
}

function fmtDate(dateStr) {
  if (!dateStr || dateStr === "—") return "—";
  try {
    var d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });
  } catch(e) { return dateStr; }
}

function formatDateRange(from, to) {
  return '<span style="font-size:12px;font-weight:600;">' +
    fmtDate(from) + ' &rarr; ' + fmtDate(to) +
    '</span>';
}

function statusBadge(status) {
  var s = (status || "pending").toLowerCase();
  var cls = "ldb-status-pending";
  var icon = "ti-clock";
  var label = status;

  if (s === "paid" || s === "disbursed" || s === "done") {
    cls = "ldb-status-paid"; icon = "ti-circle-check"; label = "Paid";
  } else if (s === "pending") {
    cls = "ldb-status-pending"; icon = "ti-clock"; label = "Pending";
  } else if (s === "partial" || s === "partialpaid") {
    cls = "ldb-status-partial"; icon = "ti-circle-half"; label = "Partial";
  } else if (s === "calculated") {
    cls = "ldb-status-calculated"; icon = "ti-calculator"; label = "Calculated";
  } else if (s === "approved") {
    cls = "ldb-status-approved"; icon = "ti-shield-check"; label = "Approved";
  }

  return '<span class="ldb-status ' + cls + '"><i class="ti ' + icon + '"></i> ' + label + '</span>';
}

function showError(msg) {
  $("#ldb-loading").html(
    '<div class="text-center" style="padding:40px;">' +
    '<i class="ti ti-alert-circle" style="font-size:48px;color:#dc2626;"></i>' +
    '<p style="margin-top:12px;color:#64748b;">' + msg + '</p>' +
    '<a href="../labourpayment/labourmaster.php?k=' + key + '&mdid=' + mdid + '" class="ldb-btn ldb-btn-outline" style="margin-top:12px;">Go Back</a>' +
    '</div>'
  );
}

function updateManualAttTimeCalc() {
  var inVal = ($("#att-in-time").val() || "").trim();
  var outVal = ($("#att-out-time").val() || "").trim();

  if (!inVal || !outVal) {
    $("#att-time-calc-alert").hide();
    $("#att-time-error-alert").hide();
    $("#att-out-time").css("border-color", "#d1d5db");
    $("#submitManualAttBtn").show().prop("disabled", false);
    return;
  }

  // Parse HH:mm in 24-hr format
  var inParts = inVal.split(":");
  var outParts = outVal.split(":");
  var inMins = (parseInt(inParts[0], 10) || 0) * 60 + (parseInt(inParts[1], 10) || 0);
  var outMins = (parseInt(outParts[0], 10) || 0) * 60 + (parseInt(outParts[1], 10) || 0);

  if (outMins <= inMins) {
    // Negative or Zero difference
    var diffMins = outMins - inMins;
    var absDiff = Math.abs(diffMins);
    var hrs = Math.floor(absDiff / 60);
    var mins = absDiff % 60;
    var decimalHrs = (diffMins / 60);

    var durationText = (diffMins === 0 ? "0h 0m (0.00 hrs)" : "-" + hrs + "h " + (mins > 0 ? mins + "m" : "0m") + " (" + decimalHrs.toFixed(2) + " hrs)");
    $("#att-calc-duration").text(durationText);

    // Negative duration styling
    $("#att-time-calc-alert")
      .css({
        "background-color": "#fff1f2",
        "border-color": "#fecdd3",
        "color": "#9f1239"
      })
      .show();

    $("#att-calc-badge")
      .removeClass("bg-success bg-warning text-dark text-white")
      .addClass("bg-danger text-white")
      .text("Invalid (Negative)");

    $("#att-time-error-alert").show();
    $("#att-out-time").css("border-color", "#ef4444");

    // Hide submit button when check-out <= check-in
    $("#submitManualAttBtn").hide();
  } else {
    // Valid Positive Duration
    $("#att-time-error-alert").hide();
    $("#att-out-time").css("border-color", "#10b981");

    var diffMins = outMins - inMins;
    var hrs = Math.floor(diffMins / 60);
    var mins = diffMins % 60;
    var decimalHrs = (diffMins / 60);
    
    var durationText = hrs + "h " + (mins > 0 ? mins + "m" : "0m") + " (" + decimalHrs.toFixed(2) + " hrs)";
    $("#att-calc-duration").text(durationText);

    // Rule badge: Full Day (>=6.0 hrs), Half Day (>2.0 & <6.0 hrs), Under 2 hrs (<=2.0 hrs)
    if (decimalHrs >= 6.0) {
      $("#att-time-calc-alert").css({
        "background-color": "#eff6ff",
        "border-color": "#bfdbfe",
        "color": "#1e40af"
      });
      $("#att-calc-badge")
        .removeClass("bg-warning bg-danger text-dark text-white")
        .addClass("bg-success text-white")
        .text("Full Day (1.0)");
    } else if (decimalHrs > 2.0 && decimalHrs < 6.0) {
      $("#att-time-calc-alert").css({
        "background-color": "#fffbeb",
        "border-color": "#fde68a",
        "color": "#92400e"
      });
      $("#att-calc-badge")
        .removeClass("bg-success bg-danger text-white")
        .addClass("bg-warning text-dark")
        .text("Half Day (0.5)");
    } else {
      $("#att-time-calc-alert").css({
        "background-color": "#fef2f2",
        "border-color": "#fecaca",
        "color": "#991b1b"
      });
      $("#att-calc-badge")
        .removeClass("bg-success bg-warning text-dark")
        .addClass("bg-danger text-white")
        .text("Under 2h (Absent)");
    }

    $("#att-time-calc-alert").show();

    // Show submit button when valid
    $("#submitManualAttBtn").show().prop("disabled", false);
  }
}

function initManualAttendance(empId) {
  // Live duration & validation on time inputs
  $(document).off("input change", "#att-in-time, #att-out-time").on("input change", "#att-in-time, #att-out-time", function() {
    updateManualAttTimeCalc();
  });

  $(document).off("click", "#markManualAttBtn").on("click", "#markManualAttBtn", function() {
    var today = new Date();
    var yyyy = today.getFullYear();
    var mm = String(today.getMonth() + 1).padStart(2, '0');
    var dd = String(today.getDate()).padStart(2, '0');
    var dateString = yyyy + '-' + mm + '-' + dd;
    
    $("#markManualAttModalLabel").html('<i class="ti ti-calendar-plus" style="font-size:1.3rem;"></i> Mark Manual Attendance');
    $("#att-date-input").val(dateString);
    $("#att-in-time").val("09:00");
    $("#att-out-time").val("18:00");
    updateManualAttTimeCalc();
    
    $("#markManualAttModal").modal("show");
  });

  $(document).off("click", ".btn-edit-att").on("click", ".btn-edit-att", function() {
    var date = $(this).attr("data-date");
    var inTime = $(this).attr("data-in") || "09:00";
    var outTime = $(this).attr("data-out") || "";

    // Normalize 24-hr format (HH:mm)
    if (inTime && inTime.length === 4 && inTime.indexOf(':') === 1) {
      inTime = '0' + inTime;
    }
    if (!outTime || outTime === "—") {
      outTime = "18:00"; // default 18:00 (24h) if missing
    } else if (outTime.length === 4 && outTime.indexOf(':') === 1) {
      outTime = '0' + outTime;
    }

    $("#markManualAttModalLabel").html('<i class="ti ti-calendar-event" style="font-size:1.3rem;"></i> Edit Labour Attendance');
    $("#att-date-input").val(date);
    $("#att-in-time").val(inTime);
    $("#att-out-time").val(outTime);
    updateManualAttTimeCalc();

    $("#markManualAttModal").modal("show");
  });

  $(document).off("submit", "#markManualAttForm").on("submit", "#markManualAttForm", function(e) {
    e.preventDefault();

    var inTime = ($("#att-in-time").val() || "").trim();
    var outTime = ($("#att-out-time").val() || "").trim();

    if (!inTime) {
      Swal.fire({
        icon: "warning",
        title: "Check-In Time Required",
        text: "Please select Check-In time in 24-hour format.",
        confirmButtonColor: "#10b981"
      });
      return false;
    }

    if (!outTime) {
      Swal.fire({
        icon: "warning",
        title: "Check-Out Time Required",
        text: "Please select Check-Out time in 24-hour format.",
        confirmButtonColor: "#10b981"
      });
      return false;
    }

    // Convert to minutes for strict 24-hour comparison
    var inParts = inTime.split(":");
    var outParts = outTime.split(":");
    var inMins = (parseInt(inParts[0], 10) || 0) * 60 + (parseInt(inParts[1], 10) || 0);
    var outMins = (parseInt(outParts[0], 10) || 0) * 60 + (parseInt(outParts[1], 10) || 0);

    if (outMins <= inMins) {
      Swal.fire({
        icon: "warning",
        title: "Invalid Time (अमान्य समय)",
        html: "Check-Out time (<strong>" + outTime + "</strong>) must be greater than Check-In time (<strong>" + inTime + "</strong>).<br><small class='text-muted'>चेक-आउट समय चेक-इन समय से अधिक होना चाहिए।</small>",
        confirmButtonColor: "#10b981"
      });
      return false;
    }

    var $btn = $("#submitManualAttBtn");
    var origHtml = $btn.html();
    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

    $.post("modal/labourdash_modal.php?action=MarkManualAttendance", {
      employeeID   : empId,
      projectid    : projectid,
      companyid    : companyid,
      subcompanyid : subcompanyid,
      attendancedate: $("#att-date-input").val(),
      checkin      : inTime,
      checkout     : outTime,
      loginname    : loginname
    }, function(res) {
      $btn.prop("disabled", false).html(origHtml);
      var data = (typeof res === "string") ? JSON.parse(res) : res;
      if (data.status === "Success") {
        Swal.fire({
          icon: "success",
          title: "Attendance Saved!",
          text: data.message,
          toast: true, position: "top-end",
          showConfirmButton: false, timer: 3000
        });
        $("#markManualAttModal").modal("hide");
        // Reload attendance summaries & list
        loadAttendanceSummaryForYear(empId, currentYear);
        if (selectedMonth) {
          loadAttendanceForMonth(empId, currentYear, selectedMonth);
        }
      } else {
        Swal.fire("Error", data.message || "Failed to mark attendance.", "error");
      }
    }).fail(function() {
      $btn.prop("disabled", false).html(origHtml);
      Swal.fire("Error", "Network error. Please try again.", "error");
    });
  });

  $(document).off("click", ".btn-remove-att").on("click", ".btn-remove-att", function() {
    var attId = $(this).attr("data-id");
    var attDate = $(this).attr("data-date");

    Swal.fire({
      title: "उपस्थिति हटाएं (Remove Attendance)",
      text: formatDateNice(attDate) + " की उपस्थिति हटाने की पुष्टि करें। कारण (टिप्पणी) लिखना अनिवार्य है।",
      input: 'text',
      inputPlaceholder: 'उपस्थिति हटाने का कारण (टिप्पणी) दर्ज करें...',
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#ef4444",
      cancelButtonColor: "#6b7280",
      confirmButtonText: "हाँ, हटाएं (Remove)",
      cancelButtonText: "नहीं (Cancel)",
      inputValidator: (value) => {
        if (!value || !value.trim()) {
          return 'आगे बढ़ने के लिए कारण (टिप्पणी) लिखना अनिवार्य है!'
        }
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        Swal.fire({
          title: "हटाया जा रहा है...",
          text: "कृपया प्रतीक्षा करें।",
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        $.post("modal/labourdash_modal.php?action=RemoveAttendance", {
          attendanceid: attId,
          remark: result.value,
          employeeID: empId,
          projectid: projectid,
          companyid: companyid,
          subcompanyid: subcompanyid
        }, function(res) {
          Swal.close();
          var data = (typeof res === "string") ? JSON.parse(res) : res;
          if (data.status === "Success") {
            Swal.fire({
              icon: "success",
              title: "सफल (Success)",
              text: data.message,
              toast: true,
              position: "top-end",
              showConfirmButton: false,
              timer: 3000
            });
            // Reload attendance list & summary
            loadAttendanceSummaryForYear(empId, currentYear);
            if (selectedMonth) {
              loadAttendanceForMonth(empId, currentYear, selectedMonth);
            }
          } else {
            Swal.fire("त्रुटि (Error)", data.message || "Failed to remove attendance.", "error");
          }
        }).fail(function() {
          Swal.close();
          Swal.fire("त्रुटि (Error)", "Network error. Please try again.", "error");
        });
      }
    });
  });
}
