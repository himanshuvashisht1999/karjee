/* ================================================================
   OT QR Scanner – JS (v8)
   qr_scanner_ot.js
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

/* Available OT purposes from DB */
let otPurposesList = [];

/* Session stats */
let stats = { granted: 0, denied: 0, total: 0 };
let scanHistory = [];

/* ================================================================
   Helper: Extract Real Labour Name
   ================================================================ */
function getLabourName(labour, att, fallbackId) {
    var empId = fallbackId || (att ? att.employeeID : null) || (labour ? labour.EmployeeID : "");
    var candidate = "";

    if (labour && typeof labour === "object") {
        if (labour.FullName && labour.FullName.trim() !== "" && labour.FullName.trim().toLowerCase() !== "labour") {
            candidate = labour.FullName.trim();
        }
        if (!candidate && (labour.FirstName || labour.LastName)) {
            var fn = (labour.FirstName || "").trim();
            var mn = (labour.MiddleName || "").trim();
            var ln = (labour.LastName || "").trim();
            candidate = [fn, mn, ln].filter(Boolean).join(" ");
        }
        if (!candidate) {
            candidate = (labour.LabourName || labour.Name || labour.EmployeeName || "").trim();
        }
    }

    if (!candidate && att && att.labourName && att.labourName.trim() !== "" && att.labourName.trim().toLowerCase() !== "labour") {
        candidate = att.labourName.trim();
    }

    if (!candidate || candidate.toLowerCase() === "labour" || candidate.toLowerCase() === "unknown labour") {
        candidate = empId ? ("ID " + empId) : "Labour";
    }

    return candidate;
}

/* ================================================================
   Boot
   ================================================================ */
$(document).ready(function () {
    loadQrGateLocations();
    preloadMasterMaps();

    // Restore saved OT Purpose from localStorage if available
    var savedPurpose = localStorage.getItem('krf_ot_purpose');
    if (savedPurpose && savedPurpose.trim().length > 0) {
        $('#ot-purpose-input').val(savedPurpose);
    }

    loadOtPurposes();

    /* Camera toggle */
    $('#btn-cam-toggle').on('click', toggleCamera);

    /* Click Handler for OT Purpose Tags (Toggle & Multi-select) */
    $(document).on('click', '.ot-tag-btn', function () {
        var tagVal = $(this).attr('data-purpose') || $(this).text().trim();
        toggleOtPurposeTag(tagVal);
    });

    /* Manual typing in Textarea → Sync highlight state of tags */
    $(document).on('input keyup change', '#ot-purpose-input', function () {
        syncTagsWithTextarea();
    });

    /* Click Handler for "+ Add New" Purpose Button */
    $(document).on('click', '#btn-add-ot-purpose', function () {
        addNewOtPurpose();
    });

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
            setTimeout(function() {
                $('#mobile-manual-id-input').focus();
            }, 100);
        }
        
        showFeedback(lastScannedType, lastScannedLabour, lastScannedAtt);
    });

    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('vw') === '1') {
        $('body').addClass('qrs-vw-mobile');
    }

    if ($('.qrs-tab-btn[data-tab="mobile-scanner"]').hasClass('active')) {
        setTimeout(function() {
            $('#mobile-manual-id-input').focus();
        }, 300);
    }

    checkOtTimeWindowScreen();
    setInterval(checkOtTimeWindowScreen, 10000);
});

/* ================================================================
   Load & Render Master OT Purposes from SQL Database
   ================================================================ */
function loadOtPurposes(selectNewPurpose) {
    var baseurl      = $("body").data("routurl");
    var companyid    = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid    = $("body").data("projectid");

    $.post(baseurl + "modal/scanning_attendanceforot.php?action=getOTPurposes", {
        companyid: companyid,
        subcompanyid: subcompanyid,
        projectid: projectid
    }, function (raw) {
        try {
            var res = (typeof raw === 'object') ? raw : JSON.parse(raw);
            if (res.status === 'success' && res.data) {
                otPurposesList = res.data;
                renderOtPurposeTags();
                if (selectNewPurpose) {
                    toggleOtPurposeTag(selectNewPurpose, true);
                } else {
                    syncTagsWithTextarea();
                }
            }
        } catch (e) {
            console.error("Error loading OT purposes:", e);
        }
    });
}

function renderOtPurposeTags() {
    var $container = $('#ot-tags-container');
    $container.empty();

    if (otPurposesList.length === 0) {
        $container.append('<span class="text-muted small">कोई पूर्व-निर्धारित उद्देश्य नहीं। नया जोड़ने के लिए + नया जोड़ें पर क्लिक करें।</span>');
    } else {
        otPurposesList.forEach(function (p) {
            var name = p.purpose_name;
            var html = `<button type="button" class="ot-tag-btn" data-purpose="${escHtml(name)}">${escHtml(name)}</button>`;
            $container.append(html);
        });
    }

    var addBtnHtml = `<button type="button" class="ot-add-btn" id="btn-add-ot-purpose">
        <i class="ti ti-plus" style="font-size: 13px;"></i> + नया जोड़ें
    </button>`;
    $container.append(addBtnHtml);
}

/* ================================================================
   OT Tag Toggle & Textarea Sync Logic
   ================================================================ */
function toggleOtPurposeTag(tagVal, forceAdd) {
    var rawText = $('#ot-purpose-input').val();
    var parts = rawText.split(',').map(function (s) { return s.trim(); }).filter(function (s) { return s.length > 0; });

    var existingIdx = parts.findIndex(function (p) {
        return p.toLowerCase() === tagVal.toLowerCase();
    });

    if (forceAdd) {
        if (existingIdx === -1) {
            parts.push(tagVal);
        }
    } else {
        if (existingIdx !== -1) {
            parts.splice(existingIdx, 1);
        } else {
            parts.push(tagVal);
        }
    }

    $('#ot-purpose-input').val(parts.join(', '));
    syncTagsWithTextarea();
}

function syncTagsWithTextarea() {
    var rawText = $('#ot-purpose-input').val();
    localStorage.setItem('krf_ot_purpose', rawText);
    var parts = rawText.split(',').map(function (s) { return s.trim().toLowerCase(); }).filter(function (s) { return s.length > 0; });

    $('.ot-tag-btn[data-purpose]').each(function () {
        var tagVal = ($(this).attr('data-purpose') || '').trim().toLowerCase();
        if (parts.indexOf(tagVal) !== -1) {
            $(this).addClass('active selected');
        } else {
            $(this).removeClass('active selected');
        }
    });
}

/* ================================================================
   Add New Purpose to SQL Database
   ================================================================ */
function addNewOtPurpose() {
    var baseurl      = $("body").data("routurl");
    var companyid    = $("body").data("companyid");

    Swal.fire({
        title: "नया OT उद्देश्य जोड़ें",
        text: "कृपया नए OT कार्य/उद्देश्य का नाम दर्ज करें:",
        input: 'text',
        inputPlaceholder: 'उदा. नाइट शिफ्ट, केमिकल स्प्रे, लोडिंग...',
        showCancelButton: true,
        confirmButtonText: 'सहेजें',
        cancelButtonText: 'रद्द करें',
        confirmButtonColor: '#1b7a48',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'कृपया OT का उद्देश्य दर्ज करें!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            var newPurpose = result.value.trim();

            $.post(baseurl + "modal/scanning_attendanceforot.php?action=addOTPurpose", {
                companyid: companyid,
                purpose_name: newPurpose
            }, function (raw) {
                try {
                    var res = (typeof raw === 'object') ? raw : JSON.parse(raw);
                    if (res.status === 'success') {
                        Swal.fire({
                            title: "सफलता",
                            text: "नया OT उद्देश्य सफलतापूर्वक जोड़ा गया।",
                            icon: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadOtPurposes(newPurpose);
                    } else {
                        Swal.fire("त्रुटि", res.message || "सहेजने में विफल।", "error");
                    }
                } catch (e) {
                    Swal.fire("त्रुटि", "सर्वर से अमान्य प्रतिक्रिया प्राप्त हुई।", "error");
                }
            });
        }
    });
}

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
        url:  baseurl + "modal/scanning_attendanceforot.php?action=labourworkingloactiondetails",
        data: fd,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (res) {
            var sel = $("#qr-gate-location");
            sel.empty().append('<option value="">— स्थान का चयन करें —</option>');
            if (res && res.length > 0) {
                res.forEach(function (loc) {
                    sel.append('<option value="' + loc.LocationId + '">' + loc.LocationName + '</option>');
                    qrLocMap[loc.LocationId] = loc.LocationName;
                });

                var urlParams = new URLSearchParams(window.location.search);
                var locIdParam = urlParams.get('locId');
                if (locIdParam) {
                    sel.val(locIdParam);
                }

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

    $("#qr-polyhouse").empty().append('<option value="">— पहले स्थान चुनें —</option>').prop('disabled', true);
    $("#active-loc-pill").hide();

    if (!locId) {
        checkLocationsSelected();
        return;
    }

    $("#qr-polyhouse").empty().append('<option value="">पॉलीहाउस लोड हो रहे हैं…</option>');

    var baseurl   = $("body").data("routurl");
    var projectid = $("body").data("projectid");

    $.post(
        baseurl + "modal/scanning_attendanceforot.php?action=GetPolyhousesByLocation",
        { projectid: projectid, locationid: locId },
        function (raw) {
            var data = [];
            try { data = JSON.parse(raw); } catch (e) {}

            var sel = $("#qr-polyhouse");
            sel.empty().prop('disabled', false);
            sel.append('<option value="main-gate">Main-Gate</option>');

            if (data && data.length > 0) {
                data.forEach(function (p) {
                    sel.append('<option value="' + p.polyhouseid + '">' + p.polyhousename + '</option>');
                });
            }

            var urlParams = new URLSearchParams(window.location.search);
            var polyIdParam = urlParams.get('polyId');
            if (polyIdParam) {
                sel.val(polyIdParam);
            } else {
                sel.val('main-gate');
            }

            updateActivePill();
            checkLocationsSelected();
        }
    );

    updateActivePill();
    checkLocationsSelected();
}

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
    
    if (!isOtTimeAllowed()) {
        $("#qrs-scanner-section").slideUp(200);
        $("#ot-time-warning-banner").slideDown(300);
        return;
    }

    $("#ot-time-warning-banner").slideUp(200);

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

    $.post(baseurl + "modal/scanning_attendanceforot.php?action=LoadLabourlist", {
        companyid:    companyid,
        subcompanyid: subcompanyid,
        projectid:    projectid
    }, function (raw) {
        try {
            var data = (typeof raw === "object") ? raw : JSON.parse(raw);
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

/* Check if current time is within OT allowed window (6:30 PM to 7:00 AM) */
function isOtTimeAllowed() {
    var now = new Date();
    var totalMins = (now.getHours() * 60) + now.getMinutes();
    // Allowed: 6:30 PM (1110 mins) to 7:00 AM (420 mins)
    return !(totalMins > 420 && totalMins < 1110);
}

function checkOtTimeWindowScreen() {
    if (!isOtTimeAllowed()) {
        $('#ot-time-warning-banner').slideDown(200);
        $('#qrs-scanner-section').slideUp(200);
    } else {
        $('#ot-time-warning-banner').slideUp(200);
        checkLocationsSelected();
    }
}

function startCamera() {
    if (typeof Html5Qrcode === "undefined") {
        showFeedback("error", null, "कैमरा लाइब्रेरी लोड नहीं हुई है।");
        return;
    }

    if (!isOtTimeAllowed()) {
        speak("OT Allowed Between 6:30 PM To 7 AM only");
        showFeedback("denied", null, "OT Allowed Between 6:30 PM To 7 AM only");
        Swal.fire({
            title: "Warning",
            text: "OT Allowed Between 6:30 PM To 7 AM only (ओ.टी. स्कैन केवल शाम 6:30 बजे से सुबह 7:00 बजे के बीच ही अनुमत है)।",
            icon: "warning",
            confirmButtonText: "ठीक है",
            confirmButtonColor: "#d33"
        });
        return;
    }

    var purposeOfOt = $("#ot-purpose-input").val().trim();
    if (!purposeOfOt) {
        speak("कृपया OT का उद्देश्य दर्ज करें");
        Swal.fire({
            title: "OT का उद्देश्य दर्ज करें",
            text: "आगे बढ़ने से पहले कृपया OT का उद्देश्य दर्ज करें।",
            icon: "warning",
            confirmButtonText: "ठीक है",
            confirmButtonColor: "#1b7a48"
        }).then(() => {
            $("#ot-purpose-input").focus();
        });
        return;
    }

    html5QrCode = new Html5Qrcode("qr-video-reader");

    Html5Qrcode.getCameras().then(function (devices) {
        if (!devices || devices.length === 0) {
            showFeedback("error", null, "इस डिवाइस पर कोई कैमरा नहीं मिला।");
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
            showFeedback("error", null, "कैमरा शुरू करने में समस्या आई।");
            console.error(err);
        });

    }).catch(function () {
        showFeedback("error", null, "कैमरा एक्सेस की अनुमति नहीं मिली।");
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
            .html('<i class="ti ti-video-off"></i><span>कैमरा बंद करें</span>');
    } else {
        $btn.removeClass("stop").addClass("start")
            .html('<i class="ti ti-video"></i><span>कैमरा शुरू करें</span>');
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
        speak("कृपया पहले स्थान का चयन करें");
        Swal.fire({
            title: "स्थान चुनें",
            text: "आगे बढ़ने से पहले कृपया स्थान (Location) का चयन करें।",
            icon: "warning",
            confirmButtonText: "ठीक है",
            confirmButtonColor: "#1b7a48"
        });
        return;
    }

    var purposeOfOt = $("#ot-purpose-input").val().trim();
    if (!purposeOfOt) {
        speak("कृपया OT का उद्देश्य दर्ज करें");
        Swal.fire({
            title: "OT का उद्देश्य दर्ज करें",
            text: "उपस्थिति दर्ज करने से पहले OT का उद्देश्य लिखना आवश्यक है।",
            icon: "warning",
            confirmButtonText: "ठीक है",
            confirmButtonColor: "#1b7a48"
        }).then(() => {
            $("#ot-purpose-input").focus();
        });
        return;
    }

    var val = $("#mobile-manual-id-input").val().trim();
    if (!val) { $("#mobile-manual-id-input").focus(); return; }
    $("#mobile-manual-id-input").val('');
    verifyEmployeeId(val);
}

/* ================================================================
   Core AJAX verification → then save OT attendance
   ================================================================ */
function verifyEmployeeId(employeeId) {
    $("#mobile-manual-id-input").val("");
    var locId = $("#qr-gate-location").val();
    if (!locId) {
        speak("कृपया पहले स्थान का चयन करें");
        Swal.fire({
            title: "स्थान चुनें",
            text: "आगे बढ़ने से पहले कृपया स्थान का चयन करें।",
            icon: "warning",
            confirmButtonText: "ठीक है",
            confirmButtonColor: "#1b7a48"
        });
        return;
    }

    var purposeOfOt = $("#ot-purpose-input").val().trim();
    if (!purposeOfOt) {
        speak("कृपया OT का उद्देश्य दर्ज करें");
        Swal.fire({
            title: "OT का उद्देश्य दर्ज करें",
            text: "उपस्थिति दर्ज करने से पहले OT का उद्देश्य लिखना आवश्यक है।",
            icon: "warning",
            confirmButtonText: "ठीक है",
            confirmButtonColor: "#1b7a48"
        }).then(() => {
            $("#ot-purpose-input").focus();
        });
        return;
    }
    
    setTimeout(function() {
        Swal.fire({
            title: "सत्यापन जारी है...",
            text: "कृपया प्रतीक्षा करें, लेबर आईडी जांची जा रही है।",
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

    $.post(baseurl + "modal/scanning_attendanceforot.php?action=GetLabourFullDetails", {
        employeeID:   employeeId,
        companyid:    companyid,
        subcompanyid: subcompanyid,
        projectid:    projectid
    }, function (raw) {
        try {
            var data = (typeof raw === "object") ? raw : JSON.parse(raw);
            if (data.status === "Success" && data.data) {
                var labour = data.data;

                if (labour.Status && labour.Status.toLowerCase() !== "active") {
                    Swal.close();
                    var realName = getLabourName(labour, null, employeeId);
                    var label = realName + (realName.indexOf(employeeId) === -1 ? (" (" + employeeId + ")") : "");
                    showFeedback("denied", labour, "लेबर <strong>" + escHtml(realName) + "</strong> वर्तमान में निष्क्रिय (Inactive) है।");
                    addHistory(label, false);
                    speak("लेबर आईडी अमान्य या निष्क्रिय है");
                    Swal.fire({
                        title: "अनुमति नहीं है",
                        text: "यह लेबर सक्रिय नहीं है।",
                        icon: "error",
                        confirmButtonText: "ठीक है",
                        confirmButtonColor: "#d33"
                    }).then(() => {
                        refocusActiveInput();
                    });
                    return;
                }

                saveAttendanceOT(labour, employeeId, false);

            } else {
                Swal.close();
                var dummyLabour = { FullName: "ID " + employeeId, EmployeeID: employeeId };
                showFeedback("denied", dummyLabour, "आईडी <strong>" + escHtml(employeeId) + "</strong> का कोई रिकॉर्ड नहीं मिला।");
                addHistory("ID " + employeeId, false);
                speak("लेबर आईडी नहीं मिली");
                Swal.fire({
                    title: "अनुमति नहीं है",
                    text: "लेबर का कोई रिकॉर्ड नहीं मिला।",
                    icon: "error",
                    confirmButtonText: "ठीक है",
                    confirmButtonColor: "#d33"
                }).then(() => {
                    refocusActiveInput();
                });
            }
        } catch (e) {
            Swal.close();
            showFeedback("error", null, "सर्वर से अमान्य प्रतिक्रिया मिली।");
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
        showFeedback("error", null, "कनेक्शन त्रुटि। कृपया नेटवर्क जांचें।");
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
   Save OT attendance into hrm_labourattendance_forot
   ================================================================ */
function saveAttendanceOT(labour, employeeId, confirmCheckout) {
    var baseurl      = $("body").data("routurl");
    var projectid    = $("body").data("projectid");
    var loginname    = $("body").data("loginname") || "QR_SYSTEM_OT";

    var scannerVal  = $("#qr-polyhouse").val()  || "main-gate";
    var scannerName = $("#qr-polyhouse option:selected").text().trim() || "Main-Gate";
    var locName     = $("#qr-gate-location option:selected").text().trim();
    var fullLocLabel = locName + (scannerName && scannerName !== locName ? " - " + scannerName : "");

    var locationId   = $("#qr-gate-location").val() || "";
    var loginroleid  = $("body").data("loginroleid") || "";
    var purposeOfOt  = $("#ot-purpose-input").val().trim();

    var postData = {
        employeeID:  employeeId,
        projectid:   projectid,
        scannerType: scannerVal,
        scannerName: fullLocLabel,
        scannedBy:   loginname,
        locationId:  locationId,
        loginroleid: loginroleid,
        purposeofot: purposeOfOt
    };
    if (confirmCheckout) {
        postData.confirmCheckout = "true";
    }

    var realName = getLabourName(labour, null, employeeId);
    var historyLabel = realName + (realName.indexOf(employeeId) === -1 ? (" (" + employeeId + ")") : "");

    $.post(baseurl + "modal/scanning_attendanceforot.php?action=SaveQRAttendanceOT", postData, function (raw) {
        try {
            var att = (typeof raw === "object") ? raw : JSON.parse(raw);

            if (att.status === "confirm_checkout") {
                Swal.close();
                var countdownSeconds = 5;
                var autoSaveTimer = null;

                if (att.isUnder1Hour) {
                    speak("ध्यान दें, OT एक घंटे से कम है");
                }

                var warningHtml = att.isUnder1Hour ? `
                    <div style="background: #fff3cd; border: 1.5px solid #ffeeba; color: #856404; border-radius: 8px; padding: 10px 14px; font-size: 12px; margin-bottom: 12px; text-align: left; font-weight: 600; line-height: 1.4;">
                        <i class="ti ti-alert-triangle" style="font-size: 16px; vertical-align: middle; margin-right: 4px; color: #d39e00;"></i>
                        ${escHtml(att.under1HourMsg)}
                    </div>
                ` : '';

                var promptTextHtml = att.isUnder1Hour ? `
                    <p style="font-size: 13px; color: #dc3545; font-weight: 600; margin-bottom: 0;">
                        1 घंटे से कम समय होने के कारण यह स्वतः सहेजा नहीं जाएगा। सहेजने के लिए कृपया नीचे <strong>सहेजें (Save)</strong> बटन पर क्लिक करें।
                    </p>
                ` : `
                    <p style="font-size: 13px; color: #666; margin-bottom: 0;">
                        सहेजने के लिए <strong>सहेजें (Save)</strong> पर क्लिक करें या यह <span id="auto-save-sec" style="font-weight: 700; color: #d33;">5</span> सेकंड में स्वतः सहेजा जाएगा।
                    </p>
                `;

                Swal.fire({
                    title: "OT चेक-आउट की पुष्टि",
                    html: `
                        <div style="text-align: center; padding: 5px;">
                            <h4 style="color: #1b7a48; font-weight: 800; margin-bottom: 12px; font-size: 20px;">${escHtml(realName)} (${escHtml(employeeId)})</h4>
                            <div style="background: #e6f4ed; border: 1px solid #b3dfc9; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                                <div style="font-size: 13px; color: #555;">कुल OT कार्य समय (Total OT Worked Time):</div>
                                <div style="font-size: 26px; font-weight: 800; color: #1b7a48; margin: 4px 0;">${escHtml(att.workinghours || '00:00:00')}</div>
                            </div>
                            ${warningHtml}
                            ${promptTextHtml}
                        </div>
                    `,
                    icon: att.isUnder1Hour ? "warning" : "question",
                    showCancelButton: true,
                    confirmButtonColor: "#1b7a48",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "सहेजें (Save)",
                    cancelButtonText: "रद्द करें",
                    allowOutsideClick: false,
                    didOpen: () => {
                        if (!att.isUnder1Hour) {
                            autoSaveTimer = setInterval(function() {
                                countdownSeconds--;
                                $('#auto-save-sec').text(countdownSeconds);
                                if (countdownSeconds <= 0) {
                                    clearInterval(autoSaveTimer);
                                    autoSaveTimer = null;
                                    Swal.clickConfirm();
                                }
                            }, 1000);
                        }
                    },
                    willClose: () => {
                        if (autoSaveTimer) {
                            clearInterval(autoSaveTimer);
                            autoSaveTimer = null;
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "OT चेक-आउट सहेजा जा रहा है...",
                            text: "कृपया प्रतीक्षा करें।",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        saveAttendanceOT(labour, employeeId, true);
                    } else {
                        refocusActiveInput();
                        showFeedback("idle");
                    }
                });
                return;
            }

            if (att.status === "error") {
                Swal.close();
                showFeedback("denied", labour, att);
                addHistory(historyLabel, false);
                var speechMsg = "अनुमति नहीं है";
                var isOtTimeWarn = att.message && (att.message.includes("6:30") || att.message.includes("6:30 PM"));
                if (att.message && att.message.includes("15 मिनट")) {
                    speechMsg = "कृपया 15 मिनट बाद प्रयास करें";
                } else if (isOtTimeWarn) {
                    speechMsg = "OT Allowed Between 6:30 PM To 7 AM only";
                } else if (att.message && (att.message.includes("main-gate") || att.message.includes("पंचआउट") || att.message.includes("markout"))) {
                    speechMsg = "कृपया पहले मेन गेट पर उपस्थिति का मार्कआउट करें";
                }
                speak(speechMsg);
                refocusActiveInput();
                Swal.fire({
                    title: isOtTimeWarn ? "Warning" : "अनुमति नहीं है",
                    text: att.message,
                    icon: isOtTimeWarn ? "warning" : "error",
                    confirmButtonText: "ठीक है",
                    confirmButtonColor: "#d33"
                });
                return;
            }

            Swal.close();

            showFeedback("success", labour, att);
            var isGrant = (att.status === "success");
            addHistory(historyLabel, isGrant);

            if (att.type === "checkin") {
                speak("OT चेक-इन सफल रहा");
                Swal.fire({
                    title: "OT चेक-इन सफल",
                    text: att.message || "OT चेक-इन सफलतापूर्वक दर्ज हो गया है।",
                    icon: "success",
                    timer: 2000,
                    confirmButtonText: "ठीक है",
                    confirmButtonColor: "#1b7a48"
                });
            } else if (att.type === "checkout") {
                speak("OT चेक-आउट सफल रहा");
                Swal.fire({
                    title: "OT चेक-आउट सफल",
                    text: att.message || "OT चेक-आउट सफलतापूर्वक दर्ज हो गया है।",
                    icon: "success",
                    timer: 2000,
                    confirmButtonText: "ठीक है",
                    confirmButtonColor: "#1b7a48"
                });
            }

            refocusActiveInput();
        } catch (e) {
            Swal.close();
            showFeedback("denied", labour, "उपस्थिति सहेजने में विफलता हुई।");
        }
    }).fail(function () {
        Swal.close();
        showFeedback("denied", labour, "उपस्थिति सहेजने में नेटवर्क विफलता।");
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
    var activeTab = $('.qrs-tab-btn.active').data('tab');

    if (type === "idle") {
        if (!isOtTimeAllowed()) {
            html = `
            <div class="qrs-idle" style="background: #fff8e6; border: 1.5px solid #ffe8a1; border-radius: 12px; padding: 24px 16px;">
                <div class="qrs-idle-qr">
                    <i class="ti ti-alert-triangle" style="font-size:48px; color: #d97706; margin-bottom: 12px; display: inline-block;"></i>
                </div>
                <h5 class="qrs-idle-title" style="color: #b45309; font-weight: 800; font-size: 16px; margin-bottom: 6px;">⚠️ Warning: OT Allowed Between 6:30 PM To 7 AM only</h5>
                <p class="qrs-idle-desc" style="color: #d97706; font-weight: 600; margin-top: 6px;">ओ.टी. उपस्थिति दर्ज करने का समय केवल शाम 06:30 बजे से सुबह 07:00 बजे तक ही अनुमत है।</p>
            </div>`;
        } else if (activeTab === 'mobile-scanner') {
            html = `
            <div class="qrs-idle">
                <div class="qrs-idle-qr">
                    <i class="ti ti-clock-plus" style="font-size:48px; color: var(--g); margin-bottom: 12px; display: inline-block;"></i>
                </div>
                <h5 class="qrs-idle-title" style="color: var(--g);">OT मैनुअल प्रविष्टि हेतु तैयार</h5>
                <p class="qrs-idle-desc">OT उपस्थिति दर्ज करने के लिए आईडी दर्ज करें या स्कैन करें</p>
            </div>`;
        } else {
            html = `
            <div class="qrs-idle">
                <div class="qrs-idle-qr">
                    <i class="ti ti-clock-plus" style="font-size:48px; color: var(--g); margin-bottom: 12px; display: inline-block;"></i>
                </div>
                <h5 class="qrs-idle-title">OT स्कैन हेतु तैयार</h5>
                <p class="qrs-idle-desc">OT का उद्देश्य दर्ज करें और <strong>लेबर क्यूआर कार्ड</strong> स्कैन करें।</p>
            </div>`;
        }
    } else if (type === "success" && labour) {
        var realName = getLabourName(labour, att, att ? att.employeeID : null);
        var empId    = (labour && labour.EmployeeID) ? labour.EmployeeID : (att && att.employeeID ? att.employeeID : "");

        var defaultSuccessAvatar = generateSelfAvatarJS(realName, '', '#e6f4ed', '#1b7a48', 128);
        var photo  = (labour && labour.profile_photo)
            ? labour.profile_photo
            : defaultSuccessAvatar;
        var locNm  = (labour && qrLocMap[labour.locationid]) ? qrLocMap[labour.locationid] : ($("#qr-gate-location option:selected").text() || "-");
        var polyNm = getSelectedPolyhouseName();
        var otPurposeText = (att && att.purposeofot) ? att.purposeofot : ($("#ot-purpose-input").val().trim() || "-");

        var attBadge = "";
        if (att && typeof att === "object") {
            if (att.type === "checkin") {
                attBadge = `<div class="qrs-att-badge checkin">
                    <i class="ti ti-login"></i>
                    <span>OT चेक-इन (आने का समय) &nbsp;<strong>${formatDT(att.time)}</strong></span>
                </div>`;
            } else if (att.type === "checkout") {
                attBadge = `<div class="qrs-att-badge checkout">
                    <i class="ti ti-logout"></i>
                    <span>OT चेक-आउट (जाने का समय) &nbsp;<strong>${formatDT(att.time)}</strong></span>
                    <span class="qrs-att-hours"><i class="ti ti-clock"></i> ${escHtml(att.workinghours || "")}</span>
                </div>`;
            } else if (att.status === "already_complete") {
                attBadge = `<div class="qrs-att-badge already">
                    <i class="ti ti-check-box"></i>
                    <span>आज की OT उपस्थिति पूरी हो चुकी है &nbsp;·&nbsp; इन: <strong>${formatDT(att.checkin)}</strong> आउट: <strong>${formatDT(att.checkout)}</strong></span>
                </div>`;
            } else if (att.status === "error") {
                attBadge = `<div class="qrs-att-badge error">
                    <i class="ti ti-alert-circle"></i>
                    <span>${escHtml(att.message)}</span>
                </div>`;
            }
        }

        html = `
        <div class="qrs-result success">
            <div class="qrs-result-banner" style="background: #1b7a48; color: #ffffff;">
                <div class="qrs-result-icon"><i class="ti ti-check"></i></div>
                <div>
                    <div class="qrs-result-title" style="color: #ffffff; font-weight: 700;">OT प्रवेश स्वीकृत (OT Access Granted)</div>
                    <div class="qrs-result-time" style="color: #ffffff !important; opacity: 0.95; font-size: 13px; font-weight: 600; margin-top: 2px;">${now} &nbsp;·&nbsp; ${escHtml(locNm)}${polyNm ? ' - ' + escHtml(polyNm) : ''}</div>
                </div>
            </div>
            <div class="qrs-worker-row" style="padding: 16px; display: flex; align-items: center; gap: 16px;">
                <img src="${escHtml(photo)}" class="qrs-worker-photo"
                     style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid #1b7a48; object-fit: cover;"
                     onerror="this.src='${defaultSuccessAvatar}'">
                <div>
                    <div class="qrs-worker-name" style="font-size: 26px; font-weight: 900; color: #1b7a48; margin-bottom: 4px; line-height: 1.2;">
                        ${escHtml(realName)}
                    </div>
                    <span class="qrs-worker-badge" style="font-size: 16px; font-weight: 800; background: #e6f4ed; color: #1b7a48; padding: 4px 12px; border-radius: 6px; display: inline-block; margin-bottom: 6px;">
                        ID: ${escHtml(empId)}
                    </span>
                    <div class="qrs-worker-detail" style="font-size: 13px; color: #444; line-height: 1.5;">
                        <span><strong>स्थान (Location):</strong> ${escHtml(locNm)}</span>
                        ${polyNm ? '<br><span><strong>पॉलीहाउस:</strong> ' + escHtml(polyNm) + '</span>' : ''}
                        <br><span style="color:#1b7a48; font-weight: 700;"><strong>OT उद्देश्य (Purpose):</strong> ${escHtml(otPurposeText)}</span>
                    </div>
                </div>
            </div>
            ${attBadge}
        </div>`;

    } else if (type === "denied") {
        var msg = (typeof att === "string") ? att : (att && att.message ? att.message : "अमान्य लेबर आईडी।");
        
        var realName = getLabourName(labour, att, att ? att.employeeID : null);
        var empId    = (labour && labour.EmployeeID) ? labour.EmployeeID : (att && att.employeeID ? att.employeeID : "");

        var defaultDeniedAvatar = generateSelfAvatarJS(realName, '', '#ffe6e6', '#d33', 128);
        var photo = (labour && labour.profile_photo)
            ? labour.profile_photo
            : defaultDeniedAvatar;
        var locNm = (labour && qrLocMap[labour.locationid]) ? qrLocMap[labour.locationid] : ($("#qr-gate-location option:selected").text() || "-");

        var workerBlock = `
        <div class="qrs-worker-row" style="padding: 16px; display: flex; align-items: center; gap: 16px; background: #fff5f5; border-bottom: 1px solid #ffccd5;">
            <img src="${escHtml(photo)}" class="qrs-worker-photo"
                 style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid #d33; object-fit: cover;"
                 onerror="this.src='${defaultDeniedAvatar}'">
            <div>
                <div class="qrs-worker-name" style="font-size: 26px; font-weight: 900; color: #d33; margin-bottom: 4px; line-height: 1.2;">
                    ${escHtml(realName)}
                </div>
                <span class="qrs-worker-badge" style="font-size: 16px; font-weight: 800; background: #ffe6e6; color: #d33; padding: 4px 12px; border-radius: 6px; display: inline-block; margin-bottom: 6px;">
                    ID: ${escHtml(empId)}
                </span>
                <div class="qrs-worker-detail" style="font-size: 13px; color: #666; line-height: 1.4;">
                    <span><strong>स्थान (Location):</strong> ${escHtml(locNm)}</span>
                </div>
            </div>
        </div>`;

        html = `
        <div class="qrs-result error">
            <div class="qrs-result-banner" style="background: #d33; color: #ffffff;">
                <div class="qrs-result-icon"><i class="ti ti-x"></i></div>
                <div>
                    <div class="qrs-result-title" style="color: #ffffff; font-weight: 700;">प्रवेश अस्वीकृत (Access Denied)</div>
                    <div class="qrs-result-time" style="color: #ffffff !important; opacity: 0.95; font-size: 13px; font-weight: 600; margin-top: 2px;">${now}</div>
                </div>
            </div>
            ${workerBlock}
            <div style="padding: 16px; background: #fff;">
                <p class="qrs-err-msg" style="color: #d33; font-weight: 700; font-size: 14px; margin-bottom: 0;">${escHtml(msg)}</p>
            </div>
        </div>`;

    } else if (type === "error") {
        var msgErr = (typeof att === "string") ? att : (att && att.message ? att.message : "कोई त्रुटि हुई।");
        html = `
        <div class="qrs-result error">
            <div class="qrs-result-banner" style="background: #d33; color: #ffffff;">
                <div class="qrs-result-icon"><i class="ti ti-alert-triangle"></i></div>
                <div>
                    <div class="qrs-result-title" style="color: #ffffff; font-weight: 700;">सिस्टम त्रुटि</div>
                    <div class="qrs-result-time" style="color: #ffffff !important; opacity: 0.95; font-size: 13px; font-weight: 600; margin-top: 2px;">${now}</div>
                </div>
            </div>
            <div style="padding: 16px;">
                <p class="qrs-err-msg">${escHtml(msgErr)}</p>
            </div>
        </div>`;
    }

    $("#scan-feedback-body").html(html);
}

function getSelectedPolyhouseName() {
    var val = $("#qr-polyhouse").val();
    if (!val || val === "main-gate") return "";
    var txt = $("#qr-polyhouse option:selected").text().trim();
    return (txt && txt !== "— Select Polyhouse —") ? txt : "";
}

/* ================================================================
   Stats & History
   ================================================================ */
function addHistory(name, granted) {
    if (granted) stats.granted++; else stats.denied++;
    stats.total++;

    $("#stat-granted").text(stats.granted);
    $("#stat-denied").text(stats.denied);
    $("#stat-total").text(stats.total);

    var now = new Date().toLocaleTimeString("en-IN", { hour: "2-digit", minute: "2-digit" });
    scanHistory.unshift({ name: name, granted: granted, time: now });

    renderHistory();
}

function renderHistory() {
    var $list = $("#qrs-history-list");
    if (scanHistory.length === 0) {
        $list.html(`
            <div class="qrs-history-empty">
                <i class="ti ti-clock-off"></i>
                <span>इस सत्र में कोई OT स्कैन नहीं हुआ</span>
            </div>`);
        return;
    }

    var html = scanHistory.slice(0, 10).map(function (item) {
        var cls  = item.granted ? "granted" : "denied";
        var icon = item.granted ? "ti-check" : "ti-x";
        return `
        <div class="qrs-history-item ${cls}">
            <div class="qrs-hist-icon"><i class="ti ${icon}"></i></div>
            <div class="qrs-hist-name">${escHtml(item.name)}</div>
            <div class="qrs-hist-time">${item.time}</div>
        </div>`;
    }).join("");

    $list.html(html);
}

function resetSession() {
    stats = { granted: 0, denied: 0, total: 0 };
    scanHistory = [];

    $("#stat-granted").text(0);
    $("#stat-denied").text(0);
    $("#stat-total").text(0);
    renderHistory();

    showFeedback("idle");
    refocusActiveInput();
}

/* ================================================================
   Utilities
   ================================================================ */
function escHtml(str) {
    if (!str) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function formatDT(dtStr) {
    if (!dtStr) return "";
    var d = new Date(dtStr.replace(/-/g, "/"));
    if (isNaN(d.getTime())) return dtStr;
    var h = d.getHours();
    var m = String(d.getMinutes()).padStart(2, '0');
    var ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12;
    h = h ? h : 12;
    return h + ':' + m + ' ' + ampm;
}

function playBeep() {
    try {
        var ctx  = new (window.AudioContext || window.webkitAudioContext)();
        var osc  = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type     = "sine";
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.15);
    } catch (e) {}
}

function speak(text) {
    if ("speechSynthesis" in window) {
        window.speechSynthesis.cancel();
        var ut = new SpeechSynthesisUtterance(text);
        ut.lang = "hi-IN";
        ut.rate = 1.05;
        window.speechSynthesis.speak(ut);
    }
}
