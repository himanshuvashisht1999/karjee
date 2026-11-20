
$(document).on("change", "#leaveapplyleaveType", function () {
    let type = $(this).val();
    let balance = "";
    let label = "";
    $("#balancelbl").html('');

    let selectedOpt = $(this).find("option:selected");
    let leaveCategory = (selectedOpt.data("type") || "").toString();
    let leaveName = (selectedOpt.text() || "").trim();

    // Do NOT show balance line when dropdown is on "Select" or empty
    if (!type || type === "" || leaveName.toLowerCase() === "select") {
        return;
    }

    let isUnpaid = (leaveCategory.toLowerCase() === "unpaid" || 
                    leaveCategory.toLowerCase() === "un paid" ||
                    leaveName.toLowerCase().includes("unpaid") ||
                    leaveName.toLowerCase().includes("un-paid") ||
                    leaveName.toLowerCase().includes("un paid"));

    if (isUnpaid) {
        $("#balancelbl").html(`<small style="color: #64748b; font-size: 0.85rem; font-weight: 500;">Leave Category:</small> <span class="badge" style="background-color: #6c757d; color: white; font-weight: 700; border-radius: 4px; padding: 4px 8px; font-size: 0.85rem;">Unpaid Leave (No balance check)</span>`);
    } else {
        label = leaveName;
        if (type === "EL") {
            balance = $("#remainEarnleavslbl").text();
        } 
        else if (type === "SL") {
            balance = $("#remainSickleavelbl").text();
        } 
        else if (type === "CL") {
            balance = $("#remainCasualleavelbl").text();
        }

        if ((!balance || balance === "" || balance === "0") && window.currentLeaveSummaryData && Array.isArray(window.currentLeaveSummaryData)) {
            let match = window.currentLeaveSummaryData.find(item => item.leavetype === type || item.leavename === leaveName);
            if (match && match.balanceleave !== undefined) {
                balance = match.balanceleave.toString();
            }
        }

        let numBal = parseFloat(balance) || 0;
        let badgeBg = numBal > 0 ? '#00c853' : '#dc3545';
        $("#balancelbl").html(`<small style="color: #64748b; font-size: 0.85rem; font-weight: 500;">Available ${label} Balance:</small> <span class="badge" style="background-color: ${badgeBg}; color: white; font-weight: 700; border-radius: 4px; padding: 4px 8px; font-size: 0.85rem;">${numBal} Days</span>`);
    }

    // Trigger date validation if dates are already set
    if ($("#leaveapplyfromDate").val() && $("#leaveapplytoDate").val()) {
        $("#leaveapplytoDate").trigger("change");
    }
});

// Trigger leave type change when Apply Leave modal is shown
$(document).on("shown.bs.modal", "#applyLeavemodal", function () {
    $("#leaveapplyleaveType").trigger("change");
});

// Commented out to prevent forcing the To Date to be the next day (allows 1-day leave)
// linkDateFields('leaveapplyfromDate', 'leaveapplytoDate');

$(document).on("change", "#leaveapplyfromDate", function () {
    let fromDateVal = $(this).val();
    if (fromDateVal) {
        // Set min date of To Date to From Date value (enabling same-day or later leave selection)
        $("#leaveapplytoDate").attr("min", fromDateVal);

        // Auto fill To Date as same as From Date Value and trigger change
        $("#leaveapplytoDate").val(fromDateVal).trigger("change");
    }
});

// Custom File Upload Interactions
$(document).on("click", "#fakeUploadBtn, #fileNameDisplay, #fakeUploadIcon", function () {
    $("#attachment").trigger("click");
});

$(document).on("change", "#attachment", function () {
    let file = this.files[0];
    if (file) {
        $("#fileNameDisplay").text(file.name).css("color", "#334155");
    } else {
        $("#fileNameDisplay").text("No file chosen").css("color", "#94a3b8");
    }
});

$(document).on("change", "#leaveapplyfromDate, #leaveapplytoDate", function () {
    let ttlleavedays = 0;
    let actualbal = 0;
    let selectedOpt = $("#leaveapplyleaveType option:selected");
    let type = $("#leaveapplyleaveType").val();
    let leaveapplyleaveType = selectedOpt.text().trim();
    let fromDateVal = $("#leaveapplyfromDate").val();
    let toDateVal = $("#leaveapplytoDate").val();

    if (!type || type === "" || leaveapplyleaveType.toLowerCase() === "select") {
        return;
    }

    if (fromDateVal && toDateVal) {
        if (new Date(fromDateVal) > new Date(toDateVal)) {
            alertmessage("warning", "To Date must be on or after From Date.");
            $("#totalDays").val("");
            $("#leaveapplytoDate").val("");
            return false;
        }

        // Total leave days
        ttlleavedays = CountDaysBitweenDates(fromDateVal, toDateVal);
        $("#totalDays").val(ttlleavedays);

        // Check if unpaid leave is applied
        let leaveCategory = (selectedOpt.data("type") || "").toString();
        let isUnpaid = (leaveCategory.toLowerCase() === "unpaid" || 
                        leaveCategory.toLowerCase() === "un paid" ||
                        leaveapplyleaveType.toLowerCase().includes("unpaid") ||
                        leaveapplyleaveType.toLowerCase().includes("un-paid") ||
                        leaveapplyleaveType.toLowerCase().includes("un paid"));

        if (!isUnpaid && ttlleavedays > 0) {
            // Read numerical balance from respective balance elements or summary data
            if (type === "EL") {
                actualbal = parseFloat($("#remainEarnleavslbl").text()) || 0;
            } else if (type === "SL") {
                actualbal = parseFloat($("#remainSickleavelbl").text()) || 0;
            } else if (type === "CL") {
                actualbal = parseFloat($("#remainCasualleavelbl").text()) || 0;
            } else {
                let balText = $("#balancelbl span").text() || "";
                let match = balText.match(/[\d.]+/);
                actualbal = match ? (parseFloat(match[0]) || 0) : 0;
            }

            if (actualbal === 0 && window.currentLeaveSummaryData && Array.isArray(window.currentLeaveSummaryData)) {
                let match = window.currentLeaveSummaryData.find(item => item.leavetype === type || item.leavename === leaveapplyleaveType);
                if (match && match.balanceleave !== undefined) {
                    actualbal = parseFloat(match.balanceleave) || 0;
                }
            }

            // Check balance
            if (ttlleavedays > actualbal) {
                alertmessage("warning", "You have insufficient balance of " + leaveapplyleaveType + ". Available balance: " + actualbal + " day(s).");
                $("#totalDays").val("");
                $("#leaveapplytoDate").val("");
                return false;
            }
        }
    }
});

$(document).on("click", ".delete-leave-btn", function () {
    const leaveId = $(this).data("leaveid");
    const status = $(this).data("status");
    const rawRole = $("body").data("loginroleid");
    const loginroleid = (rawRole !== undefined && rawRole !== "") ? parseInt(rawRole) : 4;
    const statusLower = (status || '').toLowerCase();

    // If Admin (loginroleid < 3) and status is not Pending, demand remark
    if (loginroleid < 3 && statusLower !== 'pending') {
        Swal.fire({
            title: 'Delete Leave Request',
            text: 'Please enter the reason/remark for deleting this approved/rejected leave request:',
            input: 'text',
            inputPlaceholder: 'Reason for deletion...',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Delete',
            preConfirm: (value) => {
                if (!value || !value.trim()) {
                    Swal.showValidationMessage('Deletion remark is mandatory for approved/rejected leaves.');
                }
                return value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                performLeaveDeletion(leaveId, status, result.value);
            }
        });
    } else {
        // Standard user or pending leave: simple confirmation
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to delete this leave request?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                performLeaveDeletion(leaveId, status, '');
            }
        });
    }
});

function performLeaveDeletion(leaveId, status, remark) {
    const baseurl = $("body").data("routurl");
    const rawRole = $("body").data("loginroleid");
    const loginroleid = (rawRole !== undefined && rawRole !== "") ? parseInt(rawRole) : 4;
    const loginid = $("body").data("loginid") || '';
    
    const form_data = new FormData();
    form_data.append("LeaveID", leaveId);
    form_data.append("Status", status);
    form_data.append("loginroleid", loginroleid);
    form_data.append("loginid", loginid);
    form_data.append("remark", remark);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_modal.php?action=DeleteLeaveRequest",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            if (res.status === "Success") {
                Swal.fire("Deleted!", res.message, "success").then(() => {
                    // Reload leave data
                    const empid = $("body").data("empid");
                    const leaveyear = new Date().getFullYear();
                    EmployeeLeaveCountDetails(empid, leaveyear);
                });
            } else {
                Swal.fire("Error", res.message, "error");
            }
        },
        error: function () {
            Swal.fire("Error", "Something went wrong. Please try again.", "error");
        }
    });
}