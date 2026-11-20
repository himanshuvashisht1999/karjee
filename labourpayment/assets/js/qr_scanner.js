/* ================================================================
   QR Scanner – Redesigned JS (v2)
   qr_scanner.js
   ================================================================ */

"use strict";

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

/* ── State ── */
let html5QrCode   = null;
let cameraRunning = false;
let lastScannedId = null;
let lastScanTime  = 0;
const SCAN_COOLDOWN_MS = 3000;

/* Cache for current/last verify for tab switcher resizing */
let lastScannedLabour = null;
let lastScannedAtt    = null;
let lastScannedType   = "idle";

/* Master name maps (id → name) */
let qrDeptMap  = {};
let qrDesigMap = {};
let qrLocMap   = {};    // locationId → LocationName

/* Session stats */
let stats = { granted: 0, denied: 0, total: 0 };
let scanHistory = [];

/* ================================================================
   Boot
   ================================================================ */
$(document).ready(function () {
    loadQrGateLocations();
    preloadMasterMaps();

    /* Camera toggle */
    $('#btn-cam-toggle').on('click', toggleCamera);

    /* Mobile Manual verify (Tab 2) */
    $('#btn-verify-mobile-manual').on('click', verifyMobileManualId);
    $('#mobile-manual-id-input').on('keypress', function (e) {
        if (e.which === 13) verifyMobileManualId();
    });

    /* Location change → reload polyhouses */
    $('#qr-gate-location').on('change', function () {
        onLocationChange();
    });

    /* Reset session */
    $('#btn-reset-session').on('click', resetSession);

    /* Tab switching control */
    $(document).on('click', '.qrs-tab-btn', function() {
        var tabId = $(this).data('tab');
        $('.qrs-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.qrs-input-panel').removeClass('active');
        if (tabId === 'on-device') {
            $('#qrs-panel-camera').addClass('active');
        } else if (tabId === 'mobile-scanner') {
            $('#qrs-panel-mobile-input').addClass('active');
            // Auto-focus the large input box
            setTimeout(function() {
                $('#mobile-manual-id-input').focus();
            }, 100);
        }
        
        // Re-render the feedback block
        showFeedback(lastScannedType, lastScannedLabour, lastScannedAtt);
    });

    // Check if URL has vw=1 parameter to adjust layout/classes on body
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('vw') === '1') {
        $('body').addClass('qrs-vw-mobile');
    }

    // Auto-focus manual entry input if manual entry tab is active by default
    if ($('.qrs-tab-btn[data-tab="mobile-scanner"]').hasClass('active')) {
        setTimeout(function() {
            $('#mobile-manual-id-input').focus();
        }, 300);
    }
});

/* ================================================================
   Load Gate Locations
   ================================================================ */
function loadQrGateLocations() {
    var baseurl      = $("body").data("routurl");
    var companyid    = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid    = $("body").data("projectid");

    var fd = new FormData();
    fd.append("companyid",    companyid);
    fd.append("subcompanyid", subcompanyid);
    fd.append("projectid",    projectid);
    fd.append("shortby",      "all");
    fd.append("shortbyvalue", "all");

    $.ajax({
        type: "POST",
        url:  baseurl + "modal/hrm_laboursalarymodal.php?action=labourworkingloactiondetails",
        data: fd,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (res) {
            var sel = $("#qr-gate-location");
            sel.empty().append('<option value="">— Select Location —</option>');
            if (res && res.length > 0) {
                res.forEach(function (loc) {
                    sel.append('<option value="' + loc.LocationId + '">' + loc.LocationName + '</option>');
                    qrLocMap[loc.LocationId] = loc.LocationName;
                });

                // Auto-select location from URL if available
                var urlParams = new URLSearchParams(window.location.search);
                var locIdParam = urlParams.get('locId');
                if (locIdParam) {
                    sel.val(locIdParam);
                }

                /* Call onLocationChange to enforce selecting location first */
                onLocationChange();
            }
        }
    });
}

/* ================================================================
   On Location Change → load polyhouses + update pill
   ================================================================ */
function onLocationChange() {
    var locId  = $("#qr-gate-location").val();
    var locName = qrLocMap[locId] || $("#qr-gate-location option:selected").text();

    /* Reset polyhouse dropdown and disable it */
    $("#qr-polyhouse").empty().append('<option value="">— Select Location First —</option>').prop('disabled', true);
    $("#active-loc-pill").hide();

    if (!locId) {
        checkLocationsSelected();
        return;
    }

    $("#qr-polyhouse").empty().append('<option value="">Loading polyhouses…</option>');

    var baseurl      = $("body").data("routurl");
    var projectid    = $("body").data("projectid");

    $.post(
        baseurl + "modal/hrm_laboursalarymodal.php?action=GetPolyhousesByLocation",
        { projectid: projectid, locationid: locId },
        function (raw) {
            var data = [];
            try { data = JSON.parse(raw); } catch (e) {}

            var sel = $("#qr-polyhouse");
            sel.empty().prop('disabled', false);

            // Always add "Main-Gate" as first fixed option
            sel.append('<option value="main-gate">Main-Gate</option>');

            if (data && data.length > 0) {
                data.forEach(function (p) {
                    sel.append('<option value="' + p.polyhouseid + '">' + p.polyhousename + '</option>');
                });
            }

            // Auto-select polyhouse from URL if available
            var urlParams = new URLSearchParams(window.location.search);
            var polyIdParam = urlParams.get('polyId');
            if (polyIdParam) {
                sel.val(polyIdParam);
            } else {
                // Set default value to 'main-gate'
                sel.val('main-gate');
            }

            /* Update active pill */
            updateActivePill();

            /* Trigger scanner section check */
            checkLocationsSelected();
        }
    );

    /* Update pill immediately with location name */
    updateActivePill();
    checkLocationsSelected();
}

/* Update the green active-location pill */
function updateActivePill() {
    var locName  = $("#qr-gate-location option:selected").text().trim();
    var polyName = $("#qr-polyhouse option:selected").text().trim();
    var locId    = $("#qr-gate-location").val();

    if (!locId) { $("#active-loc-pill").hide(); return; }

    var label = locName;
    if (polyName && polyName !== "— All Polyhouses —" && polyName !== "No polyhouses found" && polyName !== "Loading polyhouses…") {
        label += " - " + polyName;
    }
    $("#active-loc-text").text(label);
    $("#active-loc-pill").show();
}

/* Polyhouse change → update pill + check selection */
$(document).on("change", "#qr-polyhouse", function () {
    updateActivePill();
    checkLocationsSelected();
});

/* ================================================================
   Visibility Control & Input Focus Management
   ================================================================ */
function checkLocationsSelected() {
    var locId  = $("#qr-gate-location").val();
    var polyId = $("#qr-polyhouse").val();
    
    if (locId && polyId) {
        $("#qrs-scanner-section").slideDown(300);
        setTimeout(function() {
            refocusActiveInput();
        }, 350);
    } else {
        $("#qrs-scanner-section").slideUp(200);
    }
}

function refocusActiveInput() {
    $("#mobile-manual-id-input").val("");
    if ($('#qrs-panel-mobile-input').hasClass('active')) {
        $('#mobile-manual-id-input').focus();
    }
}

/* ================================================================
   Pre-load Dept / Desig maps
   ================================================================ */
function preloadMasterMaps() {
    var baseurl      = $("body").data("routurl");
    var companyid    = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid    = $("body").data("projectid");

    $.post(baseurl + "modal/hrm_laboursalarymodal.php?action=LoadLabourlist", {
        companyid:    companyid,
        subcompanyid: subcompanyid,
        projectid:    projectid,
        shortby:      "all",
        shortbyvalue: "all",
        only_masters: "true"
    }, function (raw) {
        try {
            var data = JSON.parse(raw);
            if (data.departments)  data.departments.forEach(function (d) { qrDeptMap[d.DepartmentID]   = d.DepartmentName; });
            if (data.designations) data.designations.forEach(function (d) { qrDesigMap[d.DesignationID] = d.DesignationName; });
        } catch (e) {}
    });
}

/* ================================================================
   Camera control
   ================================================================ */
function toggleCamera() {
    cameraRunning ? stopCamera() : startCamera();
}

function startCamera() {
    if (typeof Html5Qrcode === "undefined") {
        showFeedback("error", null, "QR library not loaded. Please check internet connection.");
        return;
    }

    html5QrCode = new Html5Qrcode("qr-video-reader");

    Html5Qrcode.getCameras().then(function (devices) {
        if (!devices || devices.length === 0) {
            showFeedback("error", null, "No camera found on this device.");
            return;
        }

        var camId = devices[0].id;
        devices.forEach(function (d) {
            if (/back|rear|environment/i.test(d.label)) camId = d.id;
        });

        html5QrCode.start(
            camId,
            { fps: 10, qrbox: { width: 200, height: 200 }, aspectRatio: 1.333 },
            onQrSuccess,
            function () {}
        ).then(function () {
            cameraRunning = true;
            updateCamBtn();
            $("#cam-placeholder").hide();
            $("#cam-viewport-wrap").addClass("scanning");
        }).catch(function (err) {
            showFeedback("error", null, "Could not start camera. Allow camera access in browser.");
            console.error(err);
        });

    }).catch(function () {
        showFeedback("error", null, "Camera access denied. Please allow browser camera permissions.");
    });
}

function stopCamera() {
    if (html5QrCode && cameraRunning) {
        html5QrCode.stop().then(function () {
            html5QrCode.clear();
            html5QrCode   = null;
            cameraRunning = false;
            updateCamBtn();
            $("#cam-placeholder").show();
            $("#cam-viewport-wrap").removeClass("scanning");
        }).catch(console.error);
    }
}

function updateCamBtn() {
    var $btn = $("#btn-cam-toggle");
    if (cameraRunning) {
        $btn.removeClass("start").addClass("stop")
            .html('<i class="ti ti-video-off"></i><span>Stop Camera</span>');
    } else {
        $btn.removeClass("stop").addClass("start")
            .html('<i class="ti ti-video"></i><span>Start Camera</span>');
    }
}

/* ================================================================
   QR Decode success
   ================================================================ */
function onQrSuccess(decodedText) {
    var now = Date.now();
    if (decodedText === lastScannedId && (now - lastScanTime) < SCAN_COOLDOWN_MS) return;
    lastScannedId = decodedText;
    lastScanTime  = now;
    playBeep();
    verifyEmployeeId(extractId(decodedText));
}

function extractId(raw) {
    raw = raw.trim();
    try { var o = JSON.parse(raw); return o.id || o.EmployeeID || o.employeeID || raw; } catch (e) {}
    return raw;
}

/* ================================================================
   Manual verify
   ================================================================ */
function verifyMobileManualId() {
    var locId = $("#qr-gate-location").val();
    if (!locId) {
        Swal.fire({
            title: "स्थान चुनें",
            text: "कृपया आगे बढ़ने से पहले स्थान (Location) का चयन करें।",
            icon: "warning",
            confirmButtonText: "ठीक है",
            confirmButtonColor: "#1b7a48"
        });
        return;
    }
    var val = $("#mobile-manual-id-input").val().trim();
    if (!val) { $("#mobile-manual-id-input").focus(); return; }
    $("#mobile-manual-id-input").val(''); // Clear the input field after submit
    verifyEmployeeId(val);
}

/* ================================================================
   Core AJAX verification → then save attendance
   ================================================================ */
function verifyEmployeeId(employeeId) {
    $("#mobile-manual-id-input").val(""); // Clear input box immediately
    var locId = $("#qr-gate-location").val();
    if (!locId) {
        speak("कृपया पहले स्थान चुनें");
        Swal.fire({
            title: "स्थान चुनें",
            text: "कृपया आगे बढ़ने से पहले स्थान (Location) का चयन करें।",
            icon: "warning",
            confirmButtonText: "ठीक है",
            confirmButtonColor: "#1b7a48"
        });
        return;
    }
    
    // Show loading SweetAlert asynchronously to yield the main thread for instant input clearing and repaint (optimizes INP)
    setTimeout(function() {
        Swal.fire({
            title: "सत्यापन जारी है...",
            text: "कृपया प्रतीक्षा करें, लेबर आईडी सत्यापित की जा रही है।",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }, 0);

    showLoading();

    var baseurl      = $("body").data("routurl");
    var companyid    = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid    = $("body").data("projectid");

    $.post(baseurl + "modal/hrm_laboursalarymodal.php?action=GetLabourFullDetails", {
        employeeID:   employeeId,
        companyid:    companyid,
        subcompanyid: subcompanyid,
        projectid:    projectid
    }, function (raw) {
        try {
            var data = JSON.parse(raw);
            if (data.status === "Success" && data.data) {
                var labour = data.data;

                // Guard: must be Active
                if (labour.Status && labour.Status.toLowerCase() !== "active") {
                    Swal.close();
                    showFeedback("denied", null, "Labour <strong>" + escHtml(employeeId) + "</strong> is no longer active.");
                    addHistory("ID " + employeeId, false);
                    speak("Access Denied. Labour is Inactive.");
                    Swal.fire({
                        title: "अनुमति नहीं है",
                        text: "लेबर सक्रिय (Active) नहीं है।",
                        icon: "error",
                        confirmButtonText: "ठीक है",
                        confirmButtonColor: "#d33"
                    }).then(() => {
                        refocusActiveInput();
                    });
                    return;
                }

                // ── Save attendance (keeps Swal loading open) ───────────────────────────
                saveAttendance(labour, employeeId);

            } else {
                Swal.close();
                showFeedback("denied", null, "No record found for ID <strong>" + escHtml(employeeId) + "</strong>.");
                addHistory("ID " + employeeId, false);
                speak("Access Denied. Unknown ID.");
                Swal.fire({
                    title: "अनुमति नहीं है",
                    text: "कोई रिकॉर्ड नहीं मिला। लेबर आईडी अज्ञात है।",
                    icon: "error",
                    confirmButtonText: "ठीक है",
                    confirmButtonColor: "#d33"
                }).then(() => {
                    refocusActiveInput();
                });
            }
        } catch (e) {
            Swal.close();
            showFeedback("error", null, "Server returned an invalid response.");
            Swal.fire({
                title: "त्रुटि",
                text: "सर्वर से अमान्य प्रतिक्रिया प्राप्त हुई।",
                icon: "error",
                confirmButtonText: "ठीक है",
                confirmButtonColor: "#d33"
            }).then(() => {
                refocusActiveInput();
            });
        }
    }).fail(function () {
        Swal.close();
        showFeedback("error", null, "Connection error. Please check network.");
        Swal.fire({
            title: "त्रुटि",
            text: "कनेक्शन त्रुटि। कृपया नेटवर्क की जांच करें।",
            icon: "error",
            confirmButtonText: "ठीक है",
            confirmButtonColor: "#d33"
        }).then(() => {
            refocusActiveInput();
        });
    });
}

/* ================================================================
   Save attendance after verification
   ================================================================ */
function saveAttendance(labour, employeeId, confirmEarlyCheckout, forceNewEntry, earlyCheckoutRemark, confirmUnder8) {
    var baseurl      = $("body").data("routurl");
    var projectid    = $("body").data("projectid");
    var loginname    = $("body").data("loginname") || "QR_SYSTEM";

    var scannerVal  = $("#qr-polyhouse").val()  || "main-gate";
    var scannerName = $("#qr-polyhouse option:selected").text().trim() || "Main-Gate";
    var locName     = $("#qr-gate-location option:selected").text().trim();
    var fullLocLabel = locName + (scannerName && scannerName !== locName ? " - " + scannerName : "");

    var locationId   = $("#qr-gate-location").val() || "";
    var loginroleid  = $("body").data("loginroleid") || "";

    var postData = {
        employeeID:  employeeId,
        projectid:   projectid,
        scannerType: scannerVal,
        scannerName: fullLocLabel,
        scannedBy:   loginname,
        locationId:  locationId,
        loginroleid: loginroleid
    };
    if (confirmEarlyCheckout) {
        postData.confirmEarlyCheckout = "true";
    }
    if (forceNewEntry) {
        postData.forceNewEntry = "true";
    }
    if (earlyCheckoutRemark) {
        postData.earlyCheckoutRemark = earlyCheckoutRemark;
    }
    if (confirmUnder8) {
        postData.confirmUnder8 = "true";
    }

    $.post(baseurl + "modal/hrm_laboursalarymodal.php?action=SaveQRAttendance", postData, function (raw) {
        try {
            var att = JSON.parse(raw);
            
            // ── Case 1: Early checkout warning (< 2 hours) ────────────────
            if (att.status === "early_checkout_warning") {
                Swal.fire({
                    title: "अति शीघ्र चेक-आउट (2 घंटे से कम)",
                    html: `
                        <div class="text-left py-1">
                            <div style="font-size:1.35rem;font-weight:800;color:#dc2626;text-align:center;margin-bottom:12px;">
                                <i class="ti ti-clock"></i> कुल कार्य समय: ${escHtml(att.workinghours || '--:--:--')}
                            </div>
                            <div class="p-3 rounded mb-2" style="background:#fef2f2;border:1px solid #fecaca;font-size:13px;color:#991b1b;line-height:1.5;">
                                <i class="ti ti-alert-circle-filled mr-1"></i>
                                <strong>नियम:</strong> काम के घंटे <strong>2 घंटे से कम</strong> हैं।<br>
                                2 घंटे से कम कार्य करने पर उपस्थिति मान्य नहीं होगी और भुगतान में शामिल नहीं होगी।
                            </div>
                        </div>
                    `,
                    input: 'text',
                    inputPlaceholder: 'जल्दी बाहर जाने का कारण (टिप्पणी) दर्ज करें...',
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#1b7a48",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "हाँ, कारण सहित आगे बढ़ें",
                    cancelButtonText: "रद्द करें",
                    inputValidator: (value) => {
                        if (!value || !value.trim()) {
                            return 'आगे बढ़ने के लिए कारण (टिप्पणी) लिखना अनिवार्य है!'
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        Swal.fire({
                            title: "उपस्थिति दर्ज की जा रही है...",
                            text: "कृपया प्रतीक्षा करें, लेबर डेटा सत्यापित किया जा रहा है।",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        saveAttendance(labour, employeeId, true, forceNewEntry, result.value, true);
                    } else {
                        refocusActiveInput();
                        showFeedback("denied", null, "चेक आउट रद्द कर दिया गया।");
                    }
                });
                return;
            }

            // ── Case 2: Half Day warning (>= 2 hr && < 6 hr) ───────────────
            if (att.status === "half_day_warning" || att.status === "under_8hr_warning") {
                Swal.fire({
                    title: "Half Day (आधा दिन) चेक-आउट पुष्टि",
                    html: `
                        <div class="text-left py-1">
                            <div style="font-size:1.35rem;font-weight:800;color:#d97706;text-align:center;margin-bottom:12px;">
                                <i class="ti ti-clock"></i> कुल कार्य समय: ${escHtml(att.workinghours || '--:--:--')}
                            </div>
                            <div class="p-3 rounded mb-2" style="background:#fefce8;border:1px solid #fef08a;font-size:13px;color:#854d0e;line-height:1.5;">
                                <i class="ti ti-alert-triangle-filled mr-1"></i>
                                <strong>उपस्थिति नियम:</strong><br>
                                • कार्य समय <strong>6 घंटे से कम</strong> (परंतु 2 घंटे से अधिक) है।<br>
                                • यह <strong>Half Day (आधा दिन)</strong> के रूप में दर्ज होगा।
                            </div>
                            <p class="mb-0 text-center font-weight-bold" style="color:#374151;">क्या आप चेक-आउट की पुष्टि करते हैं?</p>
                        </div>
                    `,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#1b7a48",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "हाँ, चेक-आउट करें (Half Day)",
                    cancelButtonText: "नहीं"
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "उपस्थिति दर्ज की जा रही है...",
                            text: "कृपया प्रतीक्षा करें, लेबर डेटा सत्यापित किया जा रहा है।",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        saveAttendance(labour, employeeId, confirmEarlyCheckout, forceNewEntry, 'Half Day Checkout (< 6 hrs)', true);
                    } else {
                        showFeedback("denied", null, "चेक आउट रद्द कर दिया गया।");
                        refocusActiveInput();
                    }
                });
                return;
            }

            if (att.status === "error") {
                Swal.close();
                showFeedback("denied", null, att.message);
                addHistory(labour.FullName || ("ID " + employeeId), false);
                speak("पहुंच अस्वीकृत। " + att.message);
                refocusActiveInput();
                Swal.fire({
                    title: "अनुमति नहीं है",
                    text: att.message,
                    icon: "error",
                    confirmButtonText: "ठीक है",
                    confirmButtonColor: "#d33"
                });
                return;
            }

            Swal.close();

            showFeedback("success", labour, att);
            var isGrant = (att.status === "success" || att.status === "already_complete");
            addHistory(labour.FullName || ("ID " + employeeId), isGrant);

            if (att.type === "checkin") {
                var voiceMsg = "चेक इन सफल रहा " + (labour.FirstName || labour.FullName || "");
                speak(voiceMsg);
                Swal.fire({
                    title: "चेक-इन सफल (Mark-In Successful)",
                    html: `
                        <div class="text-center py-2">
                            <div style="font-size:1.35rem;font-weight:800;color:#1b7a48;margin-bottom:8px;">
                                <i class="ti ti-login mr-1"></i> ${formatDT(att.time)}
                            </div>
                            <div class="p-2 rounded text-left mt-2" style="background:#f0fdf4;border:1px solid #bbf7d0;font-size:12px;color:#166534;line-height:1.5;">
                                <i class="ti ti-info-circle mr-1"></i> <strong>उपस्थिति नियम (Working Hours Rules):</strong><br>
                                • <strong>Full Day (पूरा दिन):</strong> &ge; 6.0 घंटे कार्य<br>
                                • <strong>Half Day (आधा दिन):</strong> &ge; 2.0 घंटे और &lt; 6.0 घंटे
                            </div>
                        </div>
                    `,
                    icon: "success",
                    timer: 2500,
                    confirmButtonText: "ठीक है",
                    confirmButtonColor: "#1b7a48"
                });
            } else if (att.type === "checkout") {
                var dayTag = att.day_type || 'Full Day';
                var dayTagStyle = (att.day_type_code === 'FULL_DAY') ? 'color:#166534;background:#dcfce7;border:1px solid #86efac;' :
                                  (att.day_type_code === 'HALF_DAY' ? 'color:#854d0e;background:#fef9c3;border:1px solid #fde047;' : 'color:#991b1b;background:#fee2e2;border:1px solid #fca5a5;');

                var voiceMsg = "चेक आउट सफल रहा " + (labour.FirstName || labour.FullName || "") + "। कार्य समय " + (att.workinghours || "") + "। " + (att.day_type || "");
                speak(voiceMsg);
                Swal.fire({
                    title: "चेक-आउट सफल (Mark-Out Successful)",
                    html: `
                        <div class="text-center py-2">
                            <div style="font-size:1.75rem;font-weight:900;color:#1b7a48;margin:6px 0;">
                                <i class="ti ti-clock mr-1"></i> ${escHtml(att.workinghours || '--:--:--')}
                            </div>
                            <div class="d-inline-block px-3 py-1 rounded-pill font-weight-bold mb-2" style="font-size:13px;${dayTagStyle}">
                                ${escHtml(dayTag)}
                            </div>
                            <div class="small text-muted mt-2 border-top pt-2">
                                जाने का समय: <strong>${formatDT(att.time)}</strong>
                                ${att.checkin_time ? ` &nbsp;|&nbsp; आने का समय: <strong>${formatDT(att.checkin_time)}</strong>` : ''}
                            </div>
                        </div>
                    `,
                    icon: "success",
                    timer: 3500,
                    confirmButtonText: "ठीक है",
                    confirmButtonColor: "#1b7a48"
                });
            } else if (att.status === "already_complete") {
                speak("आज की उपस्थिति पहले से ही पूरी हो चुकी है।");
                Swal.fire({
                    title: "आज पहले ही उपस्थिति दर्ज हो चुकी है",
                    text: "आने का समय (चेक-इन): " + formatDT(att.checkin) + "\nजाने का समय (चेक-आउट): " + formatDT(att.checkout) + "\n\nक्या आप इसी दिन के लिए एक और उपस्थिति दर्ज करना चाहते हैं?",
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonColor: "#1b7a48",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "हाँ, दर्ज करें",
                    cancelButtonText: "नहीं"
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "उपस्थिति दर्ज की जा रही है...",
                            text: "कृपया प्रतीक्षा करें, लेबर डेटा सत्यापित किया जा रहा है।",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        saveAttendance(labour, employeeId, false, true);
                    }
                });
            } else {
                speak("उपस्थिति दर्ज करने में त्रुटि।");
            }

            refocusActiveInput();
        } catch (e) {
            Swal.close();
            showFeedback("success", labour, { status: "error", message: "Attendance save failed." });
        }
    }).fail(function () {
        Swal.close();
        showFeedback("success", labour, { status: "error", message: "Attendance save: network error." });
        Swal.fire({
            title: "त्रुटि",
            text: "उपस्थिति सहेजने में नेटवर्क विफलता।",
            icon: "error",
            confirmButtonText: "ठीक है",
            confirmButtonColor: "#d33"
        });
    });
}

/* ================================================================
   Feedback renderers
   ================================================================ */
function showLoading() {
    $("#scan-feedback-body").html(`
        <div class="qrs-loading">
            <div class="qrs-spinner"></div>
            <span>पहचान सत्यापित की जा रही है…</span>
        </div>
    `);
}

function showFeedback(type, labour, att) {
    lastScannedType   = type;
    lastScannedLabour = labour;
    lastScannedAtt    = att;

    var html = "";
    var now  = new Date().toLocaleTimeString("en-IN", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
    var message = (typeof att === "string") ? att : (att && att.message ? att.message : "");

    var activeTab = $('.qrs-tab-btn.active').data('tab'); // 'on-device' or 'mobile-scanner'

    if (type === "idle") {
        if (activeTab === 'mobile-scanner') {
            html = `
            <div class="qrs-idle">
                <div class="qrs-idle-qr">
                    <i class="ti ti-keyboard" style="font-size:48px; color: var(--g); margin-bottom: 12px; display: inline-block;"></i>
                </div>
                <h5 class="qrs-idle-title" style="color: var(--g);">Ready for Manual Entry</h5>
                <p class="qrs-idle-desc">उपस्थिति दर्ज करने के लिए इनपुट बॉक्स में आईडी दर्ज करें या स्कैन करें</p>
                <div class="qrs-rule-hint-box" style="margin-top:14px;">
                    <i class="ti ti-info-circle-filled"></i>
                    <span>Full Day: <strong>&ge; 6 घंटे</strong> | Half Day: <strong>&ge; 2 घंटे और &lt; 6 घंटे</strong></span>
                </div>
            </div>`;
        } else {
            html = `
            <div class="qrs-idle">
                <div class="qrs-idle-qr">
                    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="4"  y="4"  width="26" height="26" rx="4" fill="#1b7a48" opacity=".15"/>
                        <rect x="11" y="11" width="12" height="12" rx="2" fill="#1b7a48"/>
                        <rect x="50" y="4"  width="26" height="26" rx="4" fill="#1b7a48" opacity=".15"/>
                        <rect x="57" y="11" width="12" height="12" rx="2" fill="#1b7a48"/>
                        <rect x="4"  y="50" width="26" height="26" rx="4" fill="#1b7a48" opacity=".15"/>
                        <rect x="11" y="57" width="12" height="12" rx="2" fill="#1b7a48"/>
                        <rect x="50" y="50" width="10" height="10" rx="2" fill="#1b7a48"/>
                        <rect x="64" y="50" width="10" height="10" rx="2" fill="#1b7a48" opacity=".4"/>
                        <rect x="50" y="64" width="10" height="10" rx="2" fill="#1b7a48" opacity=".4"/>
                        <rect x="64" y="64" width="10" height="10" rx="2" fill="#1b7a48"/>
                    </svg>
                </div>
                <h5 class="qrs-idle-title">Ready to Verify</h5>
                <p class="qrs-idle-desc">Activate the camera and point it at a <strong>Labour ID card QR</strong>, or enter the ID manually.</p>
                <div class="qrs-rule-hint-box" style="margin-top:14px;">
                    <i class="ti ti-info-circle-filled"></i>
                    <span>Full Day: <strong>&ge; 6 घंटे</strong> | Half Day: <strong>&ge; 2 घंटे और &lt; 6 घंटे</strong></span>
                </div>
            </div>`;
        }
    } else if (type === "success" && labour) {
        var defaultAvatar = generateSelfAvatarJS(labour.FirstName || labour.FullName, labour.LastName, '#e6f4ed', '#1b7a48', 128);
        var photo  = labour.profile_photo
            ? labour.profile_photo
            : defaultAvatar;
        var desig  = qrDesigMap[labour.DesignationID] || "-";
        var dept   = qrDeptMap[labour.DepartmentID]   || "-";
        var locNm  = qrLocMap[labour.locationid]      || $("#qr-gate-location option:selected").text() || "-";
        var polyNm = getSelectedPolyhouseName();

        var attBadge = "";
        if (att && typeof att === "object") {
            if (att.type === "checkin") {
                attBadge = `
                <div class="qrs-att-badge checkin">
                    <i class="ti ti-login"></i>
                    <span>आने का समय (चेक-इन) &nbsp;<strong>${formatDT(att.time)}</strong></span>
                </div>
                <div class="qrs-rule-hint-box">
                    <i class="ti ti-info-circle-filled"></i>
                    <span>न्यूनतम कार्य समय: <strong>Full Day &ge; 6 घंटे</strong> | <strong>Half Day &ge; 2 घंटे</strong></span>
                </div>`;
            } else if (att.type === "checkout") {
                var dayClass = (att.day_type_code === 'FULL_DAY') ? 'eval-full' :
                               (att.day_type_code === 'HALF_DAY' ? 'eval-half' : 'eval-none');
                var dayLabel = att.day_type || 'Checkout Complete';

                attBadge = `
                <div class="qrs-att-badge checkout">
                    <i class="ti ti-logout"></i>
                    <span>जाने का समय (चेक-आउट) &nbsp;<strong>${formatDT(att.time)}</strong></span>
                    <span class="qrs-att-hours"><i class="ti ti-clock"></i> ${escHtml(att.workinghours || "")}</span>
                </div>
                <div class="qrs-wh-box ${dayClass}">
                    <div class="qrs-wh-title">
                        <i class="ti ti-hourglass-high"></i>
                        <span>कुल कार्य समय (Working Hours): <strong>${escHtml(att.workinghours || "")}</strong></span>
                    </div>
                    <div class="qrs-wh-status">
                        ${escHtml(dayLabel)}
                    </div>
                </div>`;
            } else if (att.status === "already_complete") {
                attBadge = `
                <div class="qrs-att-badge already">
                    <i class="ti ti-check-box"></i>
                    <span>आज उपस्थिति पहले ही पूरी हो चुकी है &nbsp;·&nbsp; इन: <strong>${formatDT(att.checkin)}</strong> आउट: <strong>${formatDT(att.checkout)}</strong></span>
                </div>`;
            } else if (att.status === "error") {
                attBadge = `
                <div class="qrs-att-badge error">
                    <i class="ti ti-alert-circle"></i>
                    <span>${escHtml(att.message)}</span>
                </div>`;
            }
        }

        html = `
        <div class="qrs-result success">
            <div class="qrs-result-banner">
                <div class="qrs-result-icon"><i class="ti ti-check"></i></div>
                <div>
                    <div class="qrs-result-title">प्रवेश स्वीकृत (Access Granted)</div>
                    <div class="qrs-result-time">${now} &nbsp;·&nbsp; ${escHtml(locNm)}${polyNm ? ' - ' + escHtml(polyNm) : ''}</div>
                </div>
            </div>
            <div class="qrs-worker-row">
                <img src="${escHtml(photo)}" class="qrs-worker-photo"
                     onerror="this.src='${defaultAvatar}'">
                <div>
                    <div class="qrs-worker-name">${escHtml(labour.FullName || "-")}</div>
                    <span class="qrs-worker-badge">${escHtml(labour.EmployeeID || "")}</span>
                    <div class="qrs-worker-detail">
                        <span><strong>पद (Designation):</strong> ${escHtml(desig)}</span><br>
                        <span><strong>स्थान (Location):</strong> ${escHtml(locNm)}</span>
                        ${polyNm ? '<br><span><strong>पॉलीहाउस (Polyhouse):</strong> ' + escHtml(polyNm) + '</span>' : ''}
                    </div>
                </div>
            </div>
            ${attBadge}
        </div>`;

    } else if (type === "denied") {
        html = `
        <div class="qrs-result denied">
            <div class="qrs-result-banner">
                <div class="qrs-result-icon"><i class="ti ti-x"></i></div>
                <div>
                    <div class="qrs-result-title">अनुमति नहीं है (Access Denied)</div>
                    <div class="qrs-result-time">${now}</div>
                </div>
            </div>
            <div class="qrs-denied-msg">${message || "पहचान सत्यापित नहीं की जा सकी।"}</div>
        </div>`;

    } else {
        html = `
        <div class="qrs-result denied">
            <div class="qrs-result-banner" style="background:#fff8e1;">
                <div class="qrs-result-icon" style="background:#f59e0b;"><i class="ti ti-alert-triangle"></i></div>
                <div>
                    <div class="qrs-result-title" style="color:#92400e;">Error</div>
                    <div class="qrs-result-time">${message || "Unknown error."}</div>
                </div>
            </div>
        </div>`;
    }

    $("#scan-feedback-body").html(html);
}


/* Format a datetime string to a readable time */
function formatDT(dt) {
    if (!dt) return "-";
    try {
        var d = new Date(dt.replace(" ", "T"));
        return d.toLocaleTimeString("en-IN", { hour: "2-digit", minute: "2-digit", second: "2-digit" });
    } catch (e) { return dt; }
}

function getSelectedPolyhouseName() {
    var txt = $("#qr-polyhouse option:selected").text().trim();
    if (!txt || txt === "No polyhouses found" || txt === "Loading polyhouses…") return "";
    return txt;
}

/* ================================================================
   Session stats + history
   ================================================================ */
function addHistory(name, success) {
    if (success) { stats.granted++; } else { stats.denied++; }
    stats.total++;

    /* Update stat numbers */
    $("#stat-granted").text(stats.granted);
    $("#stat-denied").text(stats.denied);
    $("#stat-total").text(stats.total);

    var now = new Date().toLocaleTimeString("en-IN", { hour: "2-digit", minute: "2-digit" });
    var cls = success ? "s" : "d";

    scanHistory.unshift({ name: name, success: success, time: now });
    if (scanHistory.length > 20) scanHistory.pop();

    /* Re-render history list */
    var html = scanHistory.map(function (h) {
        var c = h.success ? "s" : "d";
        return `<div class="qrs-history-item ${c}">
                    <span class="qrs-hi-dot"></span>
                    <span class="qrs-hi-name">${escHtml(h.name)}</span>
                    <span class="qrs-hi-time">${h.time}</span>
                </div>`;
    }).join("");

    $("#qrs-history-list").html(html);
}

function resetSession() {
    stats = { granted: 0, denied: 0, total: 0 };
    scanHistory = [];
    $("#stat-granted").text(0);
    $("#stat-denied").text(0);
    $("#stat-total").text(0);
    $("#qrs-history-list").html(`
        <div class="qrs-history-empty">
            <i class="ti ti-clock-off"></i>
            <span>No scans yet this session</span>
        </div>`);
    lastScannedId = null;
    lastScannedLabour = null;
    lastScannedAtt = null;
    lastScannedType = "idle";
    showFeedback("idle");
}

/* ================================================================
   Audio + speech
   ================================================================ */
function playBeep() {
    try {
        var ctx  = new (window.AudioContext || window.webkitAudioContext)();
        var osc  = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type      = "sine";
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.25, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.18);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.18);
    } catch (e) {}
}

// function speak(text) {
//     if (!window.speechSynthesis) return;
//     window.speechSynthesis.cancel();
//     var u  = new SpeechSynthesisUtterance(text);
//     u.lang = "en-Hi";
//     u.rate = 1;
//     window.speechSynthesis.speak(u);
// }

function speak(text) {
    if (!('speechSynthesis' in window)) return;

    speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(text);

    utterance.lang = "hi-IN";
    utterance.rate = 0.85;      // Slower = clearer
    utterance.pitch = 1;
    utterance.volume = 1;

    const voices = speechSynthesis.getVoices();

    // Prefer Google Hindi
    const preferred =
        voices.find(v => v.name.includes("Google हिन्दी")) ||
        voices.find(v => v.name.includes("Google Hindi")) ||
        voices.find(v => v.lang === "hi-IN") ||
        voices.find(v => v.lang.startsWith("hi"));

    if (preferred) {
        utterance.voice = preferred;
        console.log("Using:", preferred.name);
    }

    speechSynthesis.speak(utterance);
}

// Wait until voices load
window.speechSynthesis.onvoiceschanged = () => {
    console.log(speechSynthesis.getVoices());
};

/* ================================================================
   Utility
   ================================================================ */
function escHtml(s) {
    if (s == null) return "";
    return String(s)
        .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
}
