$(document).ready(function () {
    var routurl = $("body").data("routurl") || $("#hidden_routurl").val() || "";
    if (routurl && !routurl.endsWith('/')) {
        routurl += '/';
    }

    // Populate working location dropdown
    if (typeof getlabourworkinglocationdropdown === 'function') {
        getlabourworkinglocationdropdown('quick_workingLocation', 'all', 'all');
    }

    // Fetch Suggested Labour ID based on location selection
    $(document).on('change', '#quick_workingLocation', function() {
        var locId = $(this).val();
        if (!locId) {
            $('#quick_seriesid').val('');
            $('#quick_seriesid').data('fixed-prefix', '');
            return;
        }
        $.post(routurl + 'modal/hrm_laboursalarymodal.php?action=GetSuggestedLabourID', { locationid: locId }, function(res) {
            var data = (typeof res === 'object') ? res : JSON.parse(res);
            if (data.status === 'Success') {
                $('#quick_seriesid').val(data.suggested_id);
                let valStr = String(data.suggested_id);
                let fixedPrefix = valStr.substring(0, 2);
                // $('#quick_seriesid').data('fixed-prefix', fixedPrefix);
            }
        });
    });

    // Form submit handler
    $(document).on("submit", "#QuickLabourRegistrationForm", function (e) {
        e.preventDefault();

        let loc = $("#quick_workingLocation").val();
        if (!loc) {
            Swal.fire({
                icon: 'warning',
                title: 'Location Selection Missing',
                text: 'Please select a Working Location first to get a suggested Labour ID.',
                confirmButtonColor: '#0d6efd'
            });
            $("#quick_workingLocation").addClass('is-invalid');
            return;
        }

        // 1. Suggested ID Validation
        let seriesId = $("#quick_seriesid").val().trim();
        let prefix = $("#quick_seriesid").data('fixed-prefix') || "";
        if (prefix && !seriesId.startsWith(prefix)) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid ID',
                text: `Suggested ID must start with '${prefix}'.`,
                confirmButtonColor: '#0d6efd'
            });
            $("#quick_seriesid").addClass('is-invalid');
            return;
        }
        if (seriesId.length > 6) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid ID',
                text: 'Suggested ID cannot exceed 6 digits.',
                confirmButtonColor: '#0d6efd'
            });
            $("#quick_seriesid").addClass('is-invalid');
            return;
        }

        // 2. Labour Name Validation
        let labourName = $("#quick_labourName").val().trim();
        if (labourName.length < 2) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Name',
                text: 'Please enter a valid Labour Name (minimum 2 characters).',
                confirmButtonColor: '#0d6efd'
            });
            $("#quick_labourName").addClass('is-invalid');
            return;
        }

        // 3. Contact No Validation
        let contactNo = $("#quick_contactNo").val().trim();
        if (contactNo.length !== 10) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Contact Number',
                text: 'Please enter a valid 10-digit mobile number.',
                confirmButtonColor: '#0d6efd'
            });
            $("#quick_contactNo").addClass('is-invalid');
            return;
        }

        let formData = new FormData(this);
        formData.append("companyid", $("body").data("companyid") || "all");
        formData.append("subcompanyid", $("body").data("subcompanyid") || "all");
        formData.append("projectid", $("body").data("projectid") || "all");
        let currentLoginName = $("body").data("loginname") || (typeof loginname !== 'undefined' ? loginname : "");
        formData.append("addedby", currentLoginName);

        // Show loading state
        let submitBtn = $("#submitQuickRegistration");
        let originalBtnHtml = submitBtn.html();
        submitBtn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

        $.ajax({
            url: routurl + "modal/hrm_quicklabourmodal.php?action=QuickLabourRegistration",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            cache: false,
            success: function (response) {
                submitBtn.prop("disabled", false).html(originalBtnHtml);
                let data = (typeof response === 'object') ? response : JSON.parse(response);
                if (data.status === "Success") {
                    $("#QuickLabourRegistrationModal").modal('hide');
                    $("#QuickLabourRegistrationForm")[0].reset();
                    
                    // Reload grids if present
                    if (typeof loadLabours === 'function') {
                        loadLabours();
                    }
                    if (typeof loadAttendanceView === 'function') {
                        loadAttendanceView();
                    }

                    // Show custom SweetAlert styled success modal
                    Swal.fire({
                        html: `
                            <style>
                                .swal2-popup {
                                    padding: 2.5rem 2rem !important;
                                    border-radius: 16px !important;
                                }
                                .swal-success-container {
                                    text-align: center;
                                }
                                .swal-success-title {
                                    font-size: 28px;
                                    font-weight: 800;
                                    color: #10b981;
                                    margin-top: 15px;
                                    margin-bottom: 8px;
                                }
                                .swal-success-text {
                                    font-size: 15px;
                                    color: #4b5563;
                                    margin-bottom: 24px;
                                }
                                .swal-id-card {
                                    border: 1px dashed #10b981;
                                    border-radius: 12px;
                                    background-color: #f8fafc;
                                    padding: 16px;
                                    margin-bottom: 24px;
                                }
                                .swal-id-label {
                                    font-size: 11px;
                                    font-weight: 700;
                                    color: #6b7280;
                                    text-transform: uppercase;
                                    letter-spacing: 0.75px;
                                    margin-bottom: 4px;
                                    display: block;
                                }
                                .swal-id-value {
                                    font-size: 24px;
                                    font-weight: 800;
                                    color: #1f2937;
                                    display: block;
                                }
                                .swal-btn-download {
                                    background-color: #F26522 !important;
                                    color: #ffffff !important;
                                    border-radius: 30px !important;
                                    border: none !important;
                                    font-weight: 700 !important;
                                    font-size: 15px !important;
                                    padding: 12px 24px !important;
                                    width: 100% !important;
                                    display: inline-flex !important;
                                    align-items: center !important;
                                    justify-content: center !important;
                                    gap: 8px !important;
                                    margin-bottom: 16px !important;
                                    transition: all 0.2s ease !important;
                                    box-shadow: 0 4px 6px rgba(242, 101, 34, 0.15) !important;
                                }
                                .swal-btn-download:hover {
                                    background-color: #d95316 !important;
                                    transform: translateY(-1px) !important;
                                }
                                .swal2-confirm {
                                    background-color: #F26522 !important;
                                    color: #ffffff !important;
                                    border-radius: 30px !important;
                                    border: none !important;
                                    font-weight: 700 !important;
                                    font-size: 15px !important;
                                    padding: 12px 24px !important;
                                    width: 100% !important;
                                    box-shadow: 0 4px 6px rgba(242, 101, 34, 0.15) !important;
                                    transition: all 0.2s ease !important;
                                    outline: none !important;
                                }
                                .swal2-confirm:hover {
                                    background-color: #d95316 !important;
                                    transform: translateY(-1px) !important;
                                }
                            </style>
                            <div class="swal-success-container">
                                <h3 class="swal-success-title">Registration Successful!</h3>
                                <p class="swal-success-text">Labour record has been created successfully.</p>
                                
                                <div class="swal-id-card">
                                    <span class="swal-id-label">GENERATED ID</span>
                                    <span class="swal-id-value">${data.employeeID}</span>
                                </div>
                                
                                <button type="button" id="btnDownloadLabourIDCard" data-empid="${data.employeeID}" class="btn swal-btn-download">
                                    <i class="ti ti-download"></i> Download ID Card
                                </button>
                            </div>
                        `,
                        showConfirmButton: true,
                        confirmButtonText: 'Great, Continue',
                        buttonsStyling: false,
                        icon: 'success',
                        iconColor: '#10b981'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            },
            error: function () {
                submitBtn.prop("disabled", false).html(originalBtnHtml);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing the request.'
                });
            }
        });
    });

    // Handle Download ID Card Click
    $(document).on('click', '#btnDownloadLabourIDCard', function() {
        let btn = $(this);
        let empId = btn.attr('data-empid');
        let originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Downloading...');
        
        let postData = {
            employeeID: empId,
            companyid: $("body").data("companyid"),
            subcompanyid: $("body").data("subcompanyid"),
            projectid: $("body").data("projectid")
        };
        
        $.post(routurl + 'modal/hrm_laboursalarymodal.php?action=GenerateIDCard', postData, function(cardRes) {
            btn.prop('disabled', false).html(originalHtml);
            let cData = (typeof cardRes === 'object') ? cardRes : JSON.parse(cardRes);
            if (cData.status === 'Success') {
                let targetUrl = routurl + cData.path;
                if (targetUrl.indexOf('?') === -1) {
                    targetUrl += "?t=" + new Date().getTime();
                } else {
                    targetUrl += "&t_client=" + new Date().getTime();
                }
                let link = document.createElement('a');
                link.href = targetUrl;
                link.download = cData.path.substring(cData.path.lastIndexOf('/') + 1);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Download Failed',
                    text: cData.message || 'Unable to generate ID card PDF.'
                });
            }
        });
    });

    // ==========================================
    // Real-Time Form Validation Event Listeners
    // ==========================================

    // 1. Suggested ID Validation on input (Locks first 2 digits, limits to 6, removes non-digits)
    $(document).on('input keyup paste change', '#quick_seriesid, #reg_seriesid', function() {
        let input = $(this);
        let val = input.val();
        
        // Strip out non-digits
        val = val.replace(/[^0-9]/g, '');
        
        let prefix = input.data('fixed-prefix') || "";
        if (prefix.length >= 2) {
            if (!val.startsWith(prefix)) {
                if (val.length < prefix.length) {
                    val = prefix;
                } else {
                    val = prefix + val.substring(prefix.length);
                }
            }
        }
        
        // Enforce max 6 digits
        if (val.length > 6) {
            val = val.substring(0, 6);
        }
        
        input.val(val);
    });

    // Prevent backspace or delete from deleting the first 2 digits prefix
    $(document).on('keydown', '#quick_seriesid, #reg_seriesid', function(e) {
        let input = $(this);
        let prefix = input.data('fixed-prefix') || "";
        if (prefix.length >= 2) {
            let cursorPosition = this.selectionStart;
            // If they press backspace and cursor is at or before the prefix length
            if (e.key === 'Backspace' && cursorPosition <= prefix.length) {
                e.preventDefault();
            }
            // If they press delete and cursor is before the prefix length
            if (e.key === 'Delete' && cursorPosition < prefix.length) {
                e.preventDefault();
            }
        }
    });

    // 2. Name validation (letters and spaces only, no numbers allowed)
    $(document).on('input keyup paste change', '#quick_labourName, input[name="firstName"], input[name="middleName"], input[name="lastName"]', function() {
        let input = $(this);
        let val = input.val();
        
        // Strip out anything that is not a letter or space
        let clean = val.replace(/[^a-zA-Z\s]/g, '');
        if (val !== clean) {
            input.val(clean);
        }
    });

    // 3. Contact/Mobile number validation (numbers only, max 10 digits)
    $(document).on('input keyup paste change', '#quick_contactNo, input[name="mobileNo"]', function() {
        let input = $(this);
        let val = input.val();
        
        // Strip out non-digits
        let clean = val.replace(/[^0-9]/g, '');
        
        // Limit to 10 digits
        if (clean.length > 10) {
            clean = clean.substring(0, 10);
        }
        
        if (val !== clean) {
            input.val(clean);
        }
    });
});
