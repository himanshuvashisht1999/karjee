function genderlist_callback(callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=genderlist_callback",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res);
                callback(res);
            }
        });
}

function departmentlist_callback(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=departmentlist_dropdown",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res);
                callback(res);
            }
        });
}

function departmentlist_dropdown(alreadyexist, dropdownid) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=departmentlist_dropdown",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                if (res.length > 0) {
                    var departlist = '';
                    if (res.length > 1) {
                        departlist += "<option value='' selected disabled>Choose department</option>";
                    }
                    for (var i = 0; i < res.length; i++) {
                        departlist += "<option value='" + res[i].DepartmentID + "'>" + res[i].DepartmentName + "</option>";
                    }
                    if (departlist) {
                        $("#" + dropdownid + "").append(departlist);
                    }
                    if (alreadyexist) {
                        $("#" + dropdownid + "").val(alreadyexist).trigger("change");
                    }
                }
            }
        });
}


function linkDateFields(fromId, toId) {
    const $from = $('#' + fromId);
    const $to = $('#' + toId);

    $from.on('change', function () {
        const fromDate = $(this).val();
        $to.attr('min', fromDate);
        if ($to.val() && $to.val() < fromDate) {
            $to.val(fromDate);
        }
    });
}


function RemoveFileFromPathLB(filePath) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    form_data.append("filePath", filePath);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=RemoveFileFromPath",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                console.log(res);
            }
        });
}

function CheckUploadedAttendanceWithLocation(locationInputId, dateFromId, dateToId, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);

    var locationId = $("#" + locationInputId).val();
    var locationName = $("#" + locationInputId + " option:selected").text();

    let selectedIndex = $("#" + locationInputId).prop("selectedIndex"); // current index
    let totalOptions = $("#" + locationInputId).prop("options").length; // total options
    let nextIndex = selectedIndex + 1;

    var dateFrom = $("#" + dateFromId).val();
    var dateTo = $("#" + dateToId).val();
    if (!locationId) {
        alertmessage("warning", "Location is required.");
        return false;
    }
    if (!dateFrom || !dateTo) {
        alertmessage("warning", "Please select both From and To dates.");
        return false;
    }

    form_data.append('locationId', locationId);
    form_data.append('locationName', locationName);
    form_data.append('dateFrom', dateFrom);
    form_data.append('dateTo', dateTo);

    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=CheckUploadedAttendanceWithLocation",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            }
        });
}

function RemovedAlreadyUploadedLabourAttendance(locationId, locationName, dateFrom, dateTo) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    // 🟡 Validate inputs
    if (!locationId) {
        alertmessage("warning", "Location is required.");
        return false;
    }
    if (!dateFrom || !dateTo) {
        alertmessage("warning", "Please select both From and To dates.");
        return false;
    }

    // 🟢 Append data
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("locationId", locationId);
    form_data.append("dateFrom", dateFrom);
    form_data.append("dateTo", dateTo);

    // 🔶 SweetAlert confirmation
    Swal.fire({
        title: "Remove Already Uploaded Attendance?",
        html: "Are you sure you want to permanently delete uploaded attendance records for:<br><b>Location:</b> " + locationName + "<br><b>Date Range:</b> " + dateFrom + " to " + dateTo + "?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, Remove It",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: "POST",
                url: baseurl + "modal/hrm_laboursalarymodal.php?action=RemovedAlreadyUploadedLabourAttendance",
                data: form_data,
                contentType: false,
                processData: false,
                cache: false,
                dataType: "JSON",
                success: function (res) {
                    if (res.status === "success") {
                        Swal.fire({
                            icon: "success",
                            title: "Removed!",
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload(); // 🔁 Refresh the page
                        });
                    } else if (res.status === "info") {
                        Swal.fire("Info", res.message, "info");
                    } else {
                        Swal.fire("Error", res.message, "error");
                    }
                },
                error: function (xhr, status, error) {
                    Swal.fire("Error", "Something went wrong: " + error, "error");
                }
            });
        }
    });
}


function UploadLabourAttenanceSheet(locationInputId, dateFromId, dateToId, excelFileId, buttonId) {
    // Collect FormData
    var form_data = new FormData();
    var routurl = $("body").data("routurl");

    if (!routurl) {
        alertmessage("warning", "Route URL is not defined!");
        return;
    }

    // Extract metadata from body
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    var loginname = $("body").data("loginname");
    var key = $("body").data("key");
    var mdid = $("body").data("mdid");

    form_data.append('companyid', companyid);
    form_data.append('subcompanyid', subcompanyid);
    form_data.append('projectid', projectid);
    form_data.append('loginid', loginid);
    form_data.append('loginname', loginname);

    // Collect input values
    var locationId = $("#" + locationInputId).val();
    var locationName = $("#" + locationInputId + " option:selected").text();

    let selectedIndex = $("#" + locationInputId).prop("selectedIndex"); // current index
    let totalOptions = $("#" + locationInputId).prop("options").length; // total options
    let nextIndex = selectedIndex + 1;
    // alert(totalOptions+'=='+nextIndex);

    var dateFrom = $("#" + dateFromId).val();
    var dateTo = $("#" + dateToId).val();

    if (!locationId) {
        alertmessage("warning", "Location is required.");
        return false;
    }
    if (!dateFrom || !dateTo) {
        alertmessage("warning", "Please select both From and To dates.");
        return false;
    }

    form_data.append('locationId', locationId);
    form_data.append('locationName', locationName);
    form_data.append('dateFrom', dateFrom);
    form_data.append('dateTo', dateTo);

    // File validation
    var excelFile = $("#" + excelFileId)[0].files[0];
    if (!excelFile) {
        alertmessage("error", "Attendance Excel file is missing");
        $(excelFileId).focus();
        return false;
    }
    form_data.append('lbAttendancefile', excelFile);

    // AJAX request
    $.ajax({
        url: routurl + "modal/uploadexcelsheet_modal.php?action=UploadLabourAttenanceExcelsheet",
        type: "POST",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'json',
        beforeSend: function () {
            $("#" + buttonId).prop("disabled", true).text("Uploading...");
        },
        success: function (response) {
            // console.log(response);
            if (response.length > 0) {
                response.forEach(function (res) {
                    if (res.status.toLowerCase() === "success") {
                        // alertmessage('success', res.message);
                        // window.location.reload();
                        $("#" + excelFileId).val("");
                        if (nextIndex == totalOptions) {
                            var titletext = "All location attendance updated success.";
                            Swal.fire({
                                title: titletext,
                                text: "Attendance uploaded between " + dateFrom + " and " + dateTo + " for Location " + locationName,
                                icon: "success",
                                showCancelButton: true,
                                confirmButtonColor: "#3085d6",
                                cancelButtonColor: "#09C1D1",
                                confirmButtonText: "Yes, Go to Approval & Edit Wages",
                                cancelButtonText: "No, I want stay here",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = routurl + "labour_calculateSalary.php?k=" + key + "&mdid=" + mdid;
                                } else {
                                    window.location.reload();
                                }
                            });
                        } else {
                            var titletext = "Do you want to upload another attendance sheet?";
                            Swal.fire({
                                title: titletext,
                                text: "Attendance uploaded between " + dateFrom + " and " + dateTo + " for Location " + locationName,
                                icon: "success",
                                showCancelButton: true,
                                confirmButtonColor: "#3085d6",
                                cancelButtonColor: "#09C1D1",
                                confirmButtonText: "Yes, Upload Another Sheet",
                                cancelButtonText: "No,",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    if (nextIndex < totalOptions) {
                                        $("#" + locationInputId).prop("selectedIndex", nextIndex);      // set next option
                                    } else if (nextIndex == totalOptions) {
                                        $("#" + locationInputId).prop("selectedIndex", 0);
                                        $("#" + dateFromId).val('');
                                        $("#" + dateToId).val('');
                                        Swal.fire({
                                            title: "Success!",
                                            text: "All location attendance updated success",
                                            icon: "success"
                                        });
                                    }
                                } else {
                                    window.location.reload();
                                    // window.location.href = routurl + "labour_calculateSalary.php?k=" + key + "&mdid=" + mdid;
                                }
                            });
                        }



                    } else if (res.status.toLowerCase() === "unknownemployee") {
                        Swal.fire({
                            text: res.message,
                            showCancelButton: false,
                            confirmButtonColor: "#3085d6",
                            confirmButtonText: "Refresh"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        });
                    } else {
                        alertmessage('error', res.message || "Unknown error occurred.");
                    }
                });
            }
        },
        complete: function () {
            $("#" + buttonId).prop("disabled", false).text("Upload");
        }
    });
}

function EmplistforCalculateSalaryLabour_htmlcallback(attyearmonth_eid, daterange_eid, locationid, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginroleid = $("body").attr("data-loginroleid");
    if (loginroleid === undefined || loginroleid === null || loginroleid === "") {
        loginroleid = $("#hidden_loginroleid").val();
    }
    if (loginroleid === undefined || loginroleid === null || loginroleid === "") {
        loginroleid = (typeof window.loginroleid !== "undefined") ? window.loginroleid : "";
    }
    var attyearmonth = $("#" + attyearmonth_eid + "").val();
    var daterange = $("#" + daterange_eid + "").val();

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginroleid", loginroleid);
    form_data.append("attyearmonth", attyearmonth);
    form_data.append("daterange", daterange);
    form_data.append("locationid", locationid);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourlistforCalculateSalaryhtml",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            callback(res);
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
            // Pass an error object to the callback so the UI can reset and show error
            if (typeof callback === "function") {
                callback({
                    status: "error",
                    message: "Failed to load data. please try again.",
                    data: "" // Ensure data is empty so UI clears or shows empty state
                });
            }
        }
    });
}

function LabourMasterDataViewTablehtml(shortby, shortbyvalue, tableId) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);

    form_data.append('shortby', shortby);
    form_data.append('shortbyvalue', shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourMasterDataViewTablehtml",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // callback(res);
                console.log(res);
                if (res) {
                    $("#" + tableId + " tbody").append(res);
                }
            }
        });
}

function LabourSearchfromMasterDataforaddatt(labourid, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append('labourid', labourid);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourSearchfromMasterDataforaddatt",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            }
        });
}


function updatemissinglabatt(btn) {

    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");

    let row = $(btn).closest("tr");

    let labourid = row.find(".labourid").val().trim();
    let labourname = row.find(".labourname").val().trim();
    let attdatefrom = row.find(".attdatefrom").val();
    let attdateto = row.find(".attdateto").val();
    let attdays = parseFloat(row.find(".attdays").val());
    let locationid = row.find("input.locationid").val();
    
    // ✅ Validation (FIXED CONDITIONS)
    if (!labourid) {
        return showSwal("warning", "Missing labour id");
    }

    if (!labourname) {
        return showSwal("warning", "Missing labour name");
    }

    if (!attdatefrom) {
        return showSwal("warning", "Missing attendance from date");
    }

    if (!attdateto) {
        return showSwal("warning", "Missing attendance to date");
    }

    if (!attdays || attdays <= 0) {
        return showSwal("warning", "Invalid attendance days");
    }

    if (!locationid) {
        return showSwal("warning", "Location missing");
    }

    // ✅ Disable button to prevent double click
    $(btn).prop("disabled", true).text("Saving...");

    // ✅ Append data
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginname", loginname);
    form_data.append("labourid", labourid);
    form_data.append("labourname", labourname);
    form_data.append("attdatefrom", attdatefrom);
    form_data.append("attdateto", attdateto);
    form_data.append("attdays", attdays);
    form_data.append("locationid", locationid);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=UpdateMissingLabourAtt",
        data: form_data,
        contentType: false,
        processData: false,
        dataType: "json",

        success: function (res) {

            let status = (res.status || "error").toLowerCase();
            let message = res.message || "Unknown response";
            showSwal(status, message);
            if (status === "success") {
                $("#wageslocationlist").prop("selectedIndex", 0);
                $("#wageslocationlist").val(locationid).trigger("change");
                $("#addmissinglabourattmodal").modal("hide");
                // ✅ Lock row after success
                row.find("input, select").prop("disabled", true);
                $(btn).removeClass("btn-success").addClass("btn-secondary").text("Saved");

                // Optional highlight
                row.css("background-color", "#e6ffe6");
            } else {
                // Re-enable button if failed
                $(btn).prop("disabled", false).text("Retry");
            }
        },

        error: function (xhr) {

            console.error(xhr.responseText);

            showSwal("error", "Server error. Please try again.");

            $(btn).prop("disabled", false).text("Retry");
        }
    });
}


// ✅ Common Swal handler
function showSwal(type, message) {

    let iconMap = {
        success: "success",
        error: "error",
        warning: "warning",
        info: "info"
    };

    Swal.fire({
        icon: iconMap[type] || "info",
        title: type.toUpperCase(),
        text: message,
        timer: 2000,
        showConfirmButton: false
    });
}

function LabourMasterData(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);

    form_data.append('shortby', shortby);
    form_data.append('shortbyvalue', shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourMasterData",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            }
        });
}

function RemoveLabourFromMasterData(Labourid) {
    Swal.fire({
        title: "Enter Remark",
        input: "text",
        inputPlaceholder: "Reason for removing labour...",
        showCancelButton: true,
        confirmButtonText: "Submit",
        inputValidator: (value) => {
            if (!value) {
                return "Remark is required!";
            }
        }
    }).then((result) => {

        if (!result.isConfirmed) {
            return; // user cancelled
        }

        let Remark = result.value; // get remark from swal

        var form_data = new FormData();
        var baseurl = $("body").data("routurl");
        var companyid = $("body").data("companyid");
        var subcompanyid = $("body").data("subcompanyid");
        var projectid = $("body").data("projectid");
        var loginname = $("body").data("loginname");
        var loginid = $("body").data("loginid");
        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid);
        form_data.append("loginname", loginname);
        form_data.append("loginid", loginid);
        form_data.append("Labourid", Labourid);
        form_data.append("Remark", Remark);
        $.ajax({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=RemoveLabourFromMasterData",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: "json",
            success: function (res) {
                if (res.status === "Success") {
                    Swal.fire("Removed!", res.message, "success");
                    // reload list if needed
                    loadLabours();
                } else {
                    Swal.fire("Error", res.message, "error");
                }
            }
        });

    });
}


function UpdatePresentDaysToCalculateAttendance(NewPrestDays, maxlengthdays, IncentiveGraftingamount, IncentiveSeasionalamount, OvertimeAmount, Remark, btnid) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");
    var EmployeeId = $("#" + btnid + "").data("employeeid");
    var DateFrom = $("#" + btnid + "").data("datefrom");
    var DateTo = $("#" + btnid + "").data("dateto");
    var LocationId = $("#" + btnid + "").data("locationid");
    
    maxlengthdays = (maxlengthdays + 1);
    var daysVal = parseFloat(NewPrestDays);
    
    if (NewPrestDays === "" || NewPrestDays === undefined || NewPrestDays === null || isNaN(daysVal)) {
        alertmessage('warning', 'New present days is missing');
        return false;
    } else if (daysVal < 0) {
        alertmessage('warning', 'New present days cannot be less than 0');
        return false;
    } else if (daysVal > maxlengthdays) {
        alertmessage('warning', 'New present days ' + NewPrestDays + ' is greater than attendance length ' + maxlengthdays);
        return false;
    } else if (Remark == "" || Remark == null || Remark == undefined) {
        alertmessage('warning', 'Remark is missing.');
        return false;
    } else if (LocationId == "all") {
        alertmessage('warning', 'You cannot edit present-day records when multiple or all locations are selected.');
        return false;
    }
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginname", loginname);
    form_data.append("NewPrestDays", NewPrestDays);
    form_data.append("IncentiveGraftingamount", IncentiveGraftingamount);
    form_data.append("IncentiveSeasionalamount", IncentiveSeasionalamount);
    form_data.append("OvertimeAmount", OvertimeAmount);
    form_data.append("Remark", Remark);
    form_data.append("EmployeeId", EmployeeId);
    form_data.append("DateFrom", DateFrom);
    form_data.append("DateTo", DateTo);
    form_data.append("LocationId", LocationId);
    $("#" + btnid + "").prop("disabled", true);
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=UpdatePresentDaysToCalculateAttendance",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            $("#" + btnid + "").prop("disabled", false);
            if (res.status.toLowerCase() === "success") {
                alertmessage(res.status, res.message);
                window.location.reload();
            } else if (res.status.toLowerCase() === "morethan") {
                alertmessage("warning", res.message);

            } else {
                alertmessage(res.status, res.message);
            }
        },
        error: function () {
            alertmessage("error", "Something went wrong while updating payment.");
        }
    });
}

function UpdatePayPerDayPayment(btn) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");

    let row = $(btn).closest("tr");   // get respective row
    let employeeId = row.data("employeeid");
    let perDayPay = row.find("input.labourPerDayPayment").val();

    if (!perDayPay > 0) {
        alertmessage('warning', 'Pay Per Day Payment Missing');
        return false;
    } else if (!employeeId > 0) {
        alertmessage('warning', 'Something wrong, Labour id is missing.');
        return false;
    }
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginname", loginname);
    form_data.append("EmployeeId", employeeId);
    form_data.append("PayPerDayAmount", perDayPay);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=UpdatePayPerDayPayment",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // console.log(res);
            if (res.status.toLowerCase() === "success") {
                alertmessage(res.status, res.message);
                // Remove the row of the clicked button
                if (btn) {
                    row.css("background-color", "");
                    row.find("button.editpaymentbtn").removeClass("d-none");
                    row.find("button.updatepaymentbtn").addClass("d-none");
                    row.find("input").prop("disabled", true);
                    row.find("select").prop("disabled", true);
                }
            } else {
                alertmessage(res.status, res.message);
            }
        },
        error: function () {
            alertmessage("error", "Something went wrong while updating payment.");
        }
    });
}

function UpdatePayPerDayPaymentWithOtherDetails(btn) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");

    let row = $(btn).closest("tr");   // get respective row
    let employeeId = row.data("employeeid");
    let perDayPay = row.find(".labourPerDayPayment").val();
    let departmentid = row.find(".labourDepartment").val();
    let designationid = row.find(".labourDesignation").val();
    let graftingskill = row.find(".labourGraftingSkill").val();
    let locationid = row.find(".labourLocation").val();

    if (!perDayPay > 0) {
        alertmessage('warning', 'Pay Per Day Payment Missing');
        return false;
    } else if (!employeeId > 0) {
        alertmessage('warning', 'Something wrong, Labour id is missing.');
        return false;
    }
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginname", loginname);
    form_data.append("EmployeeId", employeeId);
    form_data.append("PayPerDayAmount", perDayPay);
    form_data.append("departmentid", departmentid);
    form_data.append("designationid", designationid);
    form_data.append("graftingskill", graftingskill);
    form_data.append("locationid", locationid);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=UpdatePayPerDayPaymentWithOtherDetails",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // console.log(res);
            if (res.status.toLowerCase() === "success") {
                alertmessage(res.status, res.message);
                // Remove the row of the clicked button
                if (btn) {
                    row.css("background-color", "");
                    row.find("button.editpaymentbtn").removeClass("d-none");
                    row.find("button.updatepaymentbtn").addClass("d-none");
                    row.find("input").prop("disabled", true);
                    row.find("select").prop("disabled", true);
                }
            } else {
                alertmessage(res.status, res.message);
            }
        },
        error: function () {
            alertmessage("error", "Something went wrong while updating payment.");
        }
    });
}


function CalculatedSalaryData_Labour(attyearmonth_eid, daterange_eid, locationid, callback) {
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var attyearmonth = $("#" + attyearmonth_eid).val();
    var daterange = $("#" + daterange_eid).val();
    if (!attyearmonth || !daterange) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Data',
            text: 'Please select Wages month and date range'
        });
        return;
    }
    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("attyearmonth", attyearmonth);
    form_data.append("daterange", daterange);
    form_data.append("locationid", locationid);

    /* 🔄 Loading Swal */
    Swal.fire({
        title: 'Labour Wages',
        text: 'Please wait...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=CalculatedSalaryData_Labour",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'json',

        success: function (res) {
            Swal.close();
            if (typeof callback === "function") {
                callback(res);
            }
        },

        error: function (xhr, status, error) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Request Failed',
                text: 'Unable to calculate labour Wages. Please try again.'
            });
            console.error("AJAX Error:", status, error);
        }
    });
}


function CalculatedSalaryDataLabour_forreport(datefrom, dateto, locationid, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("datefrom", datefrom);
    form_data.append("dateto", dateto);
    form_data.append("locationid", locationid);
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=CalculatedSalaryDataLabour_forreport",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            callback(res);
        }
    });
}

function PendingPaymentLabourWagesData(attyearmonth, daterange, locationid, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("attyearmonth", attyearmonth);
    form_data.append("daterange", daterange);
    form_data.append("locationid", locationid);
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=PendingPaymentLabourWagesData",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            callback(res);
        }
    });
}


function InsertOrUpdateCalculatedSalaryLabour_Payroll(attyearmonth_eid, daterange_eid, attenancetblid, locationid, callback, saveMode) {
    saveMode = saveMode || 'all'; // 'all' or 'only_new'
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var attyearmonth = $("#" + attyearmonth_eid).val();
    var daterange = $("#" + daterange_eid).val();
    var loginname = $("body").data("loginname");

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("attyearmonth", attyearmonth);
    form_data.append("daterange", daterange);
    form_data.append("locationid", locationid);
    form_data.append("loginname", loginname);
    form_data.append("saveMode", saveMode);

    var ArraySLDataLIst = [];
    let upcount = 0;
    $("#" + attenancetblid + " tbody tr").each(function () {
        var calstatus = $(this).data("calstatus") || "";
        var apprdstatus = $(this).data("apprdstatus") || "";

        // If mode is 'only_new', skip already calculated records
        if (saveMode === 'only_new' && calstatus === 'Calculated') {
            return;
        }

        var payperday = $(this).find("input.payperdayinput").val() ?? 0;
        var presentdays = $(this).data("presentdays") ?? 0;
        var netpayable = $(this).find("td.netpayable").text().trim();
        var basicpayment = $(this).find("td.basicpayment").text().trim();
        if (netpayable && parseFloat(netpayable) > 0 && apprdstatus == "") {
            upcount++;
            ArraySLDataLIst.push({
                "companyid": $(this).data("companyid"),
                "subcompanyid": $(this).data("subcompanyid"),
                "projectid": $(this).data("projectid"),
                "employeeid": $(this).data("employeeid"),
                "employeename": $(this).data("employeename"),
                "payperday": payperday,
                "presentdays": presentdays,
                "incentivegrafting": $(this).data("incentivegrafting"),
                "incentiveseasional": $(this).data("incentiveseasional"),
                "overtimepayment": $(this).data("overtimepayment"),
                "otherpayment": $(this).data("otherpayment"),
                "basiclabourpayment": basicpayment,
                "netpayableamount": netpayable
            });
        }
    });
    form_data.append("empSalaryData", JSON.stringify(ArraySLDataLIst));
    if (upcount == 0) {
        Swal.fire({
            title: "Warning!",
            text: "No eligible unapproved labour payment records to save.",
            icon: "warning",
            confirmButtonColor: "#3085d6",
            confirmButtonText: "OK"
        });
        if (typeof callback === "function") callback([]);
        return;
    }
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=InsertOrUpdateCalculatedSalaryLabour_Payroll",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",  // Ensuring JSON response
        success: function (res) {
            if (typeof callback === "function") callback(res);
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
            if (typeof callback === "function") callback([]);
        }
    });
}

function LabourAttendanceLocationList(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourAttendanceLocationList",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            }
        });
}

function LabourAttendanceMonthYearList(shortby, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("shortby", shortby);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourAttendanceMonthYearList",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            }
        });
}

function LabourAttendanceDateRangeList(yearmonth, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("yearmonth", yearmonth);

    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourAttendanceDateRangeList",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            }
        });
}

function LabourSalaryMonthYearList(shortby, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("shortby", shortby);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourSalaryMonthYearList",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            }
        });
}


function LabourSalaryDateRangeList(yearmonth, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("yearmonth", yearmonth);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourSalaryDateRangeList",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            }
        });
}

function getLabourSalaryLocationList(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=getLabourSalaryLocationList",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            }
        });
}

function LabourPendingSalaryLocationList(yearmonth, daterange, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("yearmonth", yearmonth);
    form_data.append("daterange", daterange);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourPendingSalaryLocationList",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            }
        });
}

function getlabourworkinglocation(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=labourworkingloactiondetails",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                callback(res);
            }
        });
}

function getlabourworkinglocationdropdown(dropdownid,shortby, shortbyvalue, alreadyexist='') {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl") || $("#hidden_routurl").val() || "";
    if (baseurl && !baseurl.endsWith('/')) {
        baseurl += '/';
    }
    
    var companyid = $("body").data("companyid");
    if (!companyid || companyid === "undefined") {
        companyid = "all";
    }
    var subcompanyid = $("body").data("subcompanyid");
    if (!subcompanyid || subcompanyid === "undefined") {
        subcompanyid = "all";
    }
    var projectid = $("body").data("projectid");
    if (!projectid || projectid === "undefined") {
        projectid = "all";
    }

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=labourworkingloactiondetails",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            $("#" + dropdownid).empty();
            var workinglocationlist = '';
            if (res && res.length > 0) {
                workinglocationlist += "<option value='' selected disabled>Choose location</option>";
                for (var i = 0; i < res.length; i++) {
                    workinglocationlist += "<option value='" + res[i].LocationId + "'>" + res[i].LocationName + "</option>";
                }
            } else {
                workinglocationlist += "<option value='' selected disabled>No locations available</option>";
            }
            $("#" + dropdownid).append(workinglocationlist);
            if (alreadyexist) {
                $("#" + dropdownid).val(alreadyexist).trigger("change");
            }
        },
        error: function(xhr, status, error) {
            let res = null;
            try {
                let raw = (xhr && xhr.responseText) ? xhr.responseText : "";
                let jsonStart = raw.indexOf('[');
                if (jsonStart === -1) jsonStart = raw.indexOf('{');
                if (jsonStart !== -1) {
                    let clean = raw.substring(jsonStart);
                    res = JSON.parse(clean);
                }
            } catch(e) {}

            if (res && Array.isArray(res)) {
                $("#" + dropdownid).empty();
                var workinglocationlist = '';
                if (res.length > 0) {
                    workinglocationlist += "<option value='' selected disabled>Choose location</option>";
                    for (var i = 0; i < res.length; i++) {
                        workinglocationlist += "<option value='" + res[i].LocationId + "'>" + res[i].LocationName + "</option>";
                    }
                } else {
                    workinglocationlist += "<option value='' selected disabled>No locations available</option>";
                }
                $("#" + dropdownid).append(workinglocationlist);
                if (alreadyexist) {
                    $("#" + dropdownid).val(alreadyexist).trigger("change");
                }
            } else {
                console.error("AJAX Error populating location dropdown:", error);
                $("#" + dropdownid).empty().append("<option value='' selected disabled>Error loading locations</option>");
            }
        }
    });
}

function LabourPendingBookFromCalculatedSalaryData(attyearmonth_eid, daterange_eid, locationid, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var daterange = $("#" + daterange_eid + "").val();
    var attyearmonth = $("#" + attyearmonth_eid + "").val();
    var daterange = $("#" + daterange_eid + "").val();

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("attyearmonth", attyearmonth);
    form_data.append("daterange", daterange);
    form_data.append("locationid", locationid);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourPendingBookFromCalculatedSalaryData",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res);
                callback(res);
            }
        });
}

function LabourCreateBookFromCalculatedSalaryData(crttableid, savetobtnid) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");
    var loginid = $("body").data("loginid");
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginname", loginname);
    form_data.append("loginid", loginid);

    var BookSLDataLIst = [];
    $("#" + crttableid + " tbody tr").each(function () {
        if ($(this).find("input.lbslrdbookcheckbox").is(":checked")) {
            BookSLDataLIst.push({
                "locationid": $(this).data("locationid"),
                "employeeid": $(this).data("employeeid"),  // must match data-employeeid
                "salaryyear": $(this).data("salaryyear"),
                "salarymonth": $(this).data("salarymonth"),
                "datefrom": $(this).data("datefrom"),
                "dateto": $(this).data("dateto"),
                "netsalary": $(this).data("netsalary")
            });
        }
    });

    if (BookSLDataLIst.length < 1) {
        // alertmessage('error', 'selection of labour is missing');
        Swal.fire({
            // title: "Are you sure?",
            title: "Warning!",
            text: "Selection missing from labour list!",
            icon: "warning",
            showCancelButton: false,
            confirmButtonColor: "#3085d6",
            confirmButtonText: "Ok"
        }).then((result) => {
            if (result.isConfirmed) {
                // window.location.reload();
            }
        });
        return false;
    }
    form_data.append("empSalaryData", JSON.stringify(BookSLDataLIst));
    var btntext = $("#" + savetobtnid + "").html();
    $("#" + savetobtnid + "").prop("disabled", true);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourCreateBookFromCalculatedSalaryData",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res);
                // alert(res.status);
                $("#" + savetobtnid + "").html(btntext);
                $("#" + savetobtnid + "").prop("disabled", false);
                if (res.status == "success" || res.status == "Success") {
                    Swal.fire({
                        title: "Success!",
                        text: res.message,
                        icon: "success",
                        showCancelButton: false,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        timer: 1000
                    }).then(() => {
                        // reload only after alert closes
                        window.location.reload();
                    });
                } else {
                    alertmessage('error', res.message)
                }
            }
        });
}

function LabourCreateBookFromCalculatedSalaryDataWithApproval(crttableid) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");
    var loginid = $("body").data("loginid");
    var key = $("body").data("key");
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginname", loginname);
    form_data.append("loginid", loginid);
    var BookSLDataLIst = [];
    $("#" + crttableid + " tbody tr").each(function () {
        // if ($(this).find("input.lbslrdbookcheckbox").is(":checked")) { 
        BookSLDataLIst.push({
            "employeeid": $(this).data("employeeid"),  // must match data-employeeid
            "salaryyear": $(this).data("attyear"),
            "salarymonth": $(this).data("attmonth"),
            "datefrom": $(this).data("datefrom"),
            "dateto": $(this).data("dateto"),
            "incentivegrafting": $(this).data("incentivegrafting"),
            "incentiveseasional": $(this).data("incentiveseasional"),
            "overtimepayment": $(this).data("overtimepayment"),
            "otherpayment": $(this).data("otherpayment"),
            "basiclabourpayment": $(this).data("basiclabourpayment"),
            "netsalary": $(this).data("netpayable")
        });
        // }
    });

    if (BookSLDataLIst.length < 1) {
        // alertmessage('error', 'selection of labour is missing');
        Swal.fire({
            // title: "Are you sure?",
            title: "Warning!",
            text: "Selection missing from labour list!",
            icon: "warning",
            showCancelButton: false,
            confirmButtonColor: "#3085d6",
            confirmButtonText: "Ok"
        }).then((result) => {
            if (result.isConfirmed) {
                // window.location.reload();
            }
        });
        return false;
    }
    form_data.append("empSalaryData", JSON.stringify(BookSLDataLIst));
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_laboursalarymodal.php?action=LabourCreateBookFromCalculatedSalaryData",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                console.log(res);
                // alert(res.status);
                if (res.status == "success" || res.status == "Success") {
                    Swal.fire({
                        title: res.message,
                        text: "Are you download the labour payment for disbursement.",
                        icon: "success",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.reload();
                        } else {
                            window.location.href = "labour_payment_disbursement.php?k=" + key;
                        }
                    });

                } else {
                    alertmessage('error', res.message)
                }

            }, beforeSend: function (res) {
                // Swal.fire({
                //   title: "Please wait",
                //   html: "Labour wages calculated, Now book creating for cash withdrawal.",
                // });
            }
        });
}

function AllUpdateFinalPaymentStatus(ReferencFilpath_eid, tableId) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");
    let fileInput = $('#' + ReferencFilpath_eid)[0];

    let salaryids = "";
    $("#" + tableId + " tbody tr").each(function () {
        let status = ($(this).data("paymentstatus") || "").toLowerCase();
        let checkbox = $(this).find("input.payperdaycheckbox");

        // If checkbox is checked, skip this row and continue loop
        if (checkbox.is(":checked")) {
            return true; // continue
        }

        // Add salaryid if not paid
        if (status !== "paid") {
            salaryids += $(this).data("salaryid") + ",";
        }
    });

    // Remove trailing comma if it exists
    if (salaryids.endsWith(",")) {
        salaryids = salaryids.slice(0, -1);
    }

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginname", loginname);
    form_data.append("ReferencFil", fileInput.files[0]);
    form_data.append("SalaryIds", salaryids);
    form_data.append("Remark", '');
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=AllUpdateFinalPaymentStatus",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            if (res.status.toLowerCase() === "success") {
                alertmessage(res.status, res.message);
                window.location.reload();
            } else if (res.status.toLowerCase() === "morethan") {
                alertmessage("warning", res.message);
            } else {
                alertmessage(res.status, res.message);
            }
        },
        error: function () {
            alertmessage("error", "Something went wrong while updating payment.");
        }
    });
}


// function UpdateFinalPaymentStatus(PaymentStatus, actualAmount, paidAmount, PaymentDate, Remark, lpdcamerainputid, lpdcanvasinputid, btnid
// ) {

//     let $btn = $("#" + btnid);

//     let baseurl      = $("body").data("routurl");
//     let companyid    = $("body").data("companyid");
//     let subcompanyid = $("body").data("subcompanyid");
//     let projectid    = $("body").data("projectid");
//     let loginname    = $("body").data("loginname");

//     let LabourId = $btn.data("employeeid");
//     let SalaryId = $btn.data("salaryid");
//     let rowindex = $btn.data("rowindex");
//     let tableid  = $btn.data("tableid");

//     /* 🔴 Validations */
//     if (!PaymentStatus) {
//         alertmessage("warning", "Payment status is required.");
//         return;
//     }

//     if (!LabourId || !SalaryId) {
//         alertmessage("warning", "Invalid employee or salary reference.");
//         return;
//     }

//     if (!paidAmount || parseFloat(paidAmount) <= 0) {
//         alertmessage("warning", "Paid amount must be greater than zero.");
//         return;
//     }

//     /* 📸 Capture Photo (optional, safe) */
//     capturePhoto(lpdcamerainputid, lpdcanvasinputid)
//         .catch(() => null) // photo optional
//         .then(blob => {

//             let form_data = new FormData();

//             if (blob instanceof Blob) {
//                 form_data.append("Photo", blob, "labourpayment.jpg");
//             }

//             form_data.append("companyid", companyid);
//             form_data.append("subcompanyid", subcompanyid);
//             form_data.append("projectid", projectid);
//             form_data.append("loginname", loginname);

//             form_data.append("PaymentStatus", PaymentStatus);
//             form_data.append("actualAmount", actualAmount);
//             form_data.append("paidAmount", paidAmount);
//             form_data.append("PaymentDate", PaymentDate || "");
//             form_data.append("Remark", Remark || "");
//             form_data.append("LabourId", LabourId);
//             form_data.append("SalaryId", SalaryId);

//             $.ajax({
//                 type: "POST",
//                 url: baseurl + "modal/hrm_laboursalarymodal.php?action=UpdateFinalPaymentStatus",
//                 data: form_data,
//                 contentType: false,
//                 processData: false,
//                 cache: false,
//                 dataType: "json",

//                 success: function (res) {

//                     if (res?.status?.toLowerCase() === "success") {

//                         alertmessage("success", res.message);

//                         $("#" + tableid + " tbody tr")
//                             .eq(rowindex)
//                             .fadeOut(200, function () {
//                                 $(this).remove();
//                             });

//                         $(".modal").modal("hide");

//                     } else if (res?.status?.toLowerCase() === "morethan") {
//                         alertmessage("warning", res.message);
//                     } else {
//                         alertmessage("error", res?.message || "Payment update failed.");
//                     }
//                 },

//                 error: function (xhr, status, error) {
//                     console.error("AJAX ERROR:", xhr.responseText);
//                     alertmessage("error", "Something went wrong while updating payment.");
//                 }
//             });
//         });
// }





function getPaidPaymentCurrentLocation(forceRetry = false) {

    return new Promise((resolve, reject) => {

        if (!navigator.geolocation) {
            reject("Geolocation not supported");
            return;
        }

        navigator.geolocation.getCurrentPosition(
            position => {
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                });
            },
            error => {

                // 🔴 Permission denied or blocked
                if (error.code === error.PERMISSION_DENIED) {

                    Swal.fire({
                        icon: "warning",
                        title: "Location Permission Required",
                        html: `
                            Location access is required to complete this payment.<br><br>
                            <b>Please enable location:</b><br>
                            • Allow location permission<br>
                            • Turn ON GPS (Mobile)<br>
                            • Refresh page if needed
                        `,
                        showCancelButton: true,
                        confirmButtonText: "Enable & Try Again",
                        cancelButtonText: "Cancel"
                    }).then(result => {

                        if (result.isConfirmed) {
                            // 🔁 Retry location request
                            getPaidPaymentCurrentLocation(true)
                                .then(resolve)
                                .catch(reject);
                        } else {
                            reject("Location permission denied");
                        }
                    });

                } else {
                    reject(error.message);
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    });
}



function UpdateFinalPaymentStatusMultiple(EmployeeId, SalaryId, PaymentStatus, PaymentDate, Remark = "Update Labour Payment", callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");
    if (PaymentStatus == "" || PaymentStatus == null || PaymentStatus == "null" || PaymentStatus == "undefined") {
        alertmessage('warning', 'Payment Status Missing, Please check the radio button');
        return false;
    } else if (EmployeeId == "" || EmployeeId == null || EmployeeId == "undefined") {
        alertmessage('warning', 'Something wrong, Employee selection.');
        return false;
    }
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginname", loginname);
    form_data.append("PaymentStatus", PaymentStatus);
    form_data.append("PaymentDate", PaymentDate);
    form_data.append("Remark", Remark);
    form_data.append("EmployeeId", EmployeeId);
    form_data.append("SalaryId", SalaryId);
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_laboursalarymodal.php?action=UpdateFinalPaymentStatus",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            callback(res);
        },
        error: function () {
            alertmessage("error", "Something went wrong while updating payment.");
        }
    });
}


let globalCameraStreams = {};
// Start Camera (Global)
async function startCamera(videoId, facingMode = "environment") {
    const video = document.getElementById(videoId);
    if (!video) return console.error("Invalid Video ID:", videoId);

    try {
        // Stop previous camera stream if exists
        if (globalCameraStreams[videoId]) {
            globalCameraStreams[videoId].getTracks().forEach(t => t.stop());
        }

        // Start new camera
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: facingMode }
        });

        globalCameraStreams[videoId] = stream;
        video.srcObject = stream;
        await video.play();

    } catch (err) {
        alert("Unable to access camera. Please allow camera permission.");
        console.error("Camera start error:", err);
    }
}

// Capture Photo (Global)
async function capturePhoto(videoId, canvasId) {
    const video = document.getElementById(videoId);
    const canvas = document.getElementById(canvasId);

    if (!video || !canvas) return console.error("Invalid IDs in capturePhoto()");

    try {
        if (video.readyState < 2) {
            await new Promise((resolve) => (video.onloadedmetadata = resolve));
        }

        await new Promise((r) => setTimeout(r, 500)); // Autofocus delay

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext("2d");
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        return await new Promise((resolve, reject) =>
            canvas.toBlob(
                (blob) => (blob ? resolve(blob) : reject("Image capture failed")),
                "image/jpeg",
                0.9
            )
        );
    } catch (err) {
        console.error("Capture error:", err);
        throw err;
    }
}

// Stop Specific Camera
function stopCamera(videoId) {
    if (globalCameraStreams[videoId]) {
        globalCameraStreams[videoId].getTracks().forEach(t => t.stop());
        delete globalCameraStreams[videoId];
    }
}

function removeTableRow(tableId, rowIndex) {
    const table = $("#" + tableId);
    const row = table.find("tr").eq(rowIndex);

    if (row.length) {
        row.remove();
    } else {
        console.warn("Row not found:", rowIndex);
    }
}

function openPopup(url, title = "Popup", popupWidth = 900, popupHeight = 700) {

    // Center popup
    let left = (screen.width - popupWidth) / 2;
    let top = (screen.height - popupHeight) / 2;

    // Open window
    window.open(
        url,
        title,
        `width=${popupWidth},height=${popupHeight},
         top=${top},left=${left},
         resizable=yes,scrollbars=yes,
         toolbar=no,location=no,menubar=no`
    );
}