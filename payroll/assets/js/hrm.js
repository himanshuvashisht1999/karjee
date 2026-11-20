$(document).on("click", "#uploadsalarysheetdata", function () {
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    if (!routurl) {
        alertmessage("warning", "Route URL is not defined!");
        return;
    }
    // Extract required metadata from body attributes
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    var loginname = $("body").data("loginname");

    var datakey = $("body").data("key");

    form_data.append('companyid', companyid);
    form_data.append('subcompanyid', subcompanyid);
    form_data.append('projectid', projectid);
    form_data.append('loginid', loginid);
    form_data.append('loginname', loginname);

    var slarymonthyear = $("#uploadsalarymonth").val();
    if (slarymonthyear == "" || slarymonthyear == null || slarymonthyear == "undefined") {
        alertmessage("warning", "Salary month is missing, Please choose the salary month");
        return false;
    }
    form_data.append('slarymonthyear', slarymonthyear);

    // Get the uploaded file
    var SalaryDocument = $('#salaryfile')[0].files[0];

    // Validation for file existence
    if (!SalaryDocument) {
        alertmessage("error", "Salary sheet file is missing");
        $('#salaryfile').focus();
        return false;
    }

    form_data.append('salaryfile', SalaryDocument);

    $.ajax({
        url: routurl + "modal/uploadexcelsheet_modal.php?action=uploadsalarydetails_excelfile",
        type: "POST",
        data: form_data,
        contentType: false, // Important for file uploads
        processData: false, // Don't process FormData
        cache: false,
        dataType: 'json',
        success: function (response) {
            // console.log(response);
            if (response.length > 0) {
                for (var i = 0; i < response.length; i++) {
                    if (response[i].status === "Success" || response[i].status === "success") {
                        $("#uploadsalarymonth").prop("selectedIndex", 0);
                        alertmessage('success', response[i].message);
                        // window.location.reload();
                        $("#uploadsalarymonth").val(slarymonthyear).trigger("change");
                    } else if (response[i].status === "Unknownemployee" || response[i].status === "unknownemployee") {
                        // alertmessage('success', response[i].message);
                        Swal.fire({
                            title: "Are you register as new employee?",
                            text: response[i].message,
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#3085d6",
                            cancelButtonColor: "#d33",
                            confirmButtonText: "Yes, Register"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.open(routurl + "employeeResigtration.php?k=" + datakey, "_blank");
                            }
                        });
                    } else {
                        alertmessage('error', response[i].message || "Unknown error occurred.");
                    }
                }
            }
        }
    });
});

function UploadEmployeeAttenanceMachineExcelSheet(locationInputId, slarymonthyear, dateFromId, dateToId, excelFileId, buttonId) {
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
    // Collect input values
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
        alertmessage("warning", "Salary month is missing, Please choose the salary month");
        return false;
    }



    // File validation
    var excelFile = $("#" + excelFileId)[0].files[0];
    if (!excelFile) {
        alertmessage("error", "Attendance Excel file is missing");
        $(excelFileId).focus();
        return false;
    }
    form_data.append('companyid', companyid);
    form_data.append('subcompanyid', subcompanyid);
    form_data.append('projectid', projectid);
    form_data.append('loginid', loginid);
    form_data.append('loginname', loginname);

    form_data.append('locationId', locationId);
    form_data.append('locationName', locationName);
    form_data.append('dateFrom', dateFrom);
    form_data.append('dateTo', dateTo);
    form_data.append('empAttendancefile', excelFile);

    // AJAX request
    $.ajax({
        url: routurl + "modal/uploadexcelsheet_modal.php?action=UploadEmployeeAttenanceMachineExcelSheet",
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
                            var titletext = "All location attendance updated success, Continue for calculation";
                            Swal.fire({
                                title: titletext,
                                text: "Attendance uploaded between " + dateFrom + " and " + dateTo + " for Location " + locationName,
                                icon: "success",
                                showCancelButton: true,
                                confirmButtonColor: "#3085d6",
                                cancelButtonColor: "#09C1D1",
                                confirmButtonText: "Yes, Calculate Now",
                                cancelButtonText: "No, I want stay here",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $("#uploadsalarymonth").val(slarymonthyear).trigger("change");
                                    // window.location.href = routurl + "salary_calculationindex.php?k=" + key + "&mdid=" + mdid;
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
                                    // window.location.reload();
                                    $("#uploadsalarymonth").val(slarymonthyear).trigger("change");
                                    // window.location.href = routurl + "labour_calculateSalary.php?k=" + key + "&mdid=" + mdid;
                                }
                            });
                        }
                    } else if (res.status.toLowerCase() === "unknownemployee") {
                        var rrstmsg = "";
                        rrstmsg += "<div class='table-responsive'>";
                        rrstmsg += "<table class='table table-bordered'>";
                        rrstmsg += "<thead class='table-light'><tr><th>Employee Name</th></tr></thead><tbody>";
                        let d = res.newemployeedata;
                        if (d.length > 0) {
                            for (var i = 0; i < d.length; i++) {
                                rrstmsg += "<tr><td>" + d[i].EmployeeName + "</td></tr>";
                            }
                        } else {
                            rrstmsg += "<tr><td class='text-center'>No data available</td></tr>";
                        }
                        rrstmsg += "</tbody></table>";
                        rrstmsg += "</div>";
                        Swal.fire({
                            title: "Employee Registration Missing",
                            html: rrstmsg,
                            showDenyButton: false,
                            showCancelButton: false,
                            confirmButtonText: "Refresh",
                            // denyButtonText: `Don't save`
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Swal.fire("Saved!", "", "success");
                                location.reload();
                            } else if (result.isDenied) {
                                Swal.fire("Changes are not saved", "", "info");
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

$(document).on("click", "#bulkuploademployeedatabtn", function () {
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    if (!routurl) {
        alertmessage("warning", "Route URL is not defined!");
        return;
    }
    // Extract required metadata from body attributes
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    var loginname = $("body").data("loginname");
    var key = $("body").data("key");

    form_data.append('companyid', companyid);
    form_data.append('subcompanyid', subcompanyid);
    form_data.append('projectid', projectid);
    form_data.append('loginid', loginid);
    form_data.append('loginname', loginname);

    // Get the uploaded file
    var bulkemployeefile = $('#bulkempdetails')[0].files[0];

    // Validation for file existence
    if (!bulkemployeefile) {
        alertmessage("error", "Bulk employee excel sheet file is missing");
        $('#bulkempdetails').focus();
        return false;
    }

    form_data.append('bulkemployeefile', bulkemployeefile);
    // $("#bulkuploademployeedatabtn").prop("disabled", true);

    $.ajax({
        url: routurl + "modal/uploadexcelsheet_modal.php?action=uploadbulkemployeedetails_excelfile",
        type: "POST",
        data: form_data,
        contentType: false, // Important for file uploads
        processData: false, // Don't process FormData
        cache: false,
        dataType: 'json',
        success: function (res) {
            Swal.close(); // Close loading alert
            $("#bulkuploademployeedatabtn").prop("disabled", false);
            if (res.length > 0) {
                for (var i = 0; i < res.length; i++) {
                    var rrstmsg = "";
                    rrstmsg += "<div class='table-responsive'>";
                    rrstmsg += "<table class='table table-bordered table-sm mb-0'>";
                    rrstmsg += "<thead class='table-light'><tr><th>Status</th><th>Message</th></tr></thead><tbody>";

                    if (res.length > 0) {
                        for (var i = 0; i < res.length; i++) {
                            rrstmsg += "<tr><td>" + res[i].status + "</td><td>" + res[i].message + "</td></tr>";
                        }
                    } else {
                        rrstmsg += "<tr><td colspan='2' class='text-center'>No data available</td></tr>";
                    }

                    rrstmsg += "</tbody></table>";
                    rrstmsg += "</div>";

                    Swal.fire({
                        title: "Bulk Employee Registration",
                        html: rrstmsg,
                        width: '900px', // Increase modal width
                        customClass: {
                            popup: 'my-swal-popup', // custom popup style
                            htmlContainer: 'my-swal-html' // custom content style
                        },
                        confirmButtonText: "Refresh",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });
                }
            }
        }, beforeSend: function (res) {
            Swal.fire({
                title: "Please wait...",
                html: "Employee registration data is being processed.",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading(); // Show loading spinner
                }
            });
        }
    });
});


// submit employee basic deails
$(document).on("click", "#submitEmployeeRegistrationbtn", function () {
    // e.preventDefault();
    // Create a FormData object to handle file uploads and form data
    var routurl = $("body").data("routurl");
    if (!routurl) {
        alert("Route URL is not defined!");
        return;
    }
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var form_data = new FormData();
    form_data.append('companyid', companyid);
    form_data.append('subcompanyid', subcompanyid);
    form_data.append('projectid', projectid);
    form_data.append('addedby', $("body").data("loginname"));

    var TitleName = $('#TitleName').val();
    form_data.append('TitleName', TitleName);
    var FirstName = $('#FirstName').val();
    if (FirstName == "" || FirstName == null || FirstName == "undefined") {
        alertmessage("error", "First Name Missing");
        $('#FirstName').focus();
        return false;
    }
    form_data.append('FirstName', FirstName);

    var MiddleName = $('#MiddleName').val();
    form_data.append('MiddleName', MiddleName);

    var LastName = $('#LastName').val();
    form_data.append('LastName', LastName);

    var DateOfBirth = $('#DateOfBirth').val();
    if (DateOfBirth == "" || DateOfBirth == null || DateOfBirth == "undefined") {
        alertmessage("error", "Date Of Birth is Missing");
        $('#DateOfBirth').focus();
        return false;
    }
    form_data.append('DateOfBirth', DateOfBirth);
    var GenderName = $('#GenderName').val();
    if (GenderName == "" || GenderName == null || GenderName == "undefined") {
        alertmessage("error", "Gender Name is Missing");
        $('#GenderName').focus();
        return false;
    }
    form_data.append('GenderName', GenderName);
    var MaritalStatus = $('#MaritalStatus').val();
    form_data.append('MaritalStatus', MaritalStatus);
    var SpouseName = $('#SpouseName').val();
    form_data.append('SpouseName', SpouseName);
    var FatherName = $('#FatherName').val();
    form_data.append('FatherName', FatherName);
    var MotherName = $('#MotherName').val();
    form_data.append('MotherName', MotherName);
    var Nationality = $('#Nationality').val();
    form_data.append('Nationality', Nationality);
    var PassportNumber = $('#PassportNumber').val();
    form_data.append('PassportNumber', PassportNumber);
    var PanNumber = $('#PanNumber').val();
    form_data.append('PanNumber', PanNumber);
    var AadharNumber = $('#AadharNumber').val();
    form_data.append('AadharNumber', AadharNumber);
    var MobileNumber = $('#MobileNumber').val();
    if (MobileNumber == "" || MobileNumber == null || MobileNumber == "undefined") {
        alertmessage("error", "Date Of Birth is Missing");
        $('#MobileNumber').focus();
        return false;
    }
    form_data.append('MobileNumber', MobileNumber);
    var EmailAddress = $('#EmailAddress').val();
    form_data.append('EmailAddress', EmailAddress);
    var AddressLine1 = $('#AddressLine1').val();
    if (AddressLine1 == "" || AddressLine1 == null || AddressLine1 == "undefined") {
        alertmessage("error", "Address Line1 is Missing");
        $('#AddressLine1').focus();
        return false;
    }
    form_data.append('AddressLine1', AddressLine1);
    var AddressLine2 = $('#AddressLine2').val();
    form_data.append('AddressLine2', AddressLine2);
    var AddressLine3 = $('#AddressLine3').val();
    form_data.append('AddressLine3', AddressLine3);
    var AddressLine4 = $('#AddressLine4').val();
    form_data.append('AddressLine4', AddressLine4);
    var PostalCode = $('#PostalCode').val();
    if (PostalCode == "" || PostalCode == null || PostalCode == "undefined") {
        alertmessage("error", "Postal Code is Missing");
        $('#PostalCode').focus();
        return false;
    }
    form_data.append('PostalCode', PostalCode);
    var CountryName = $('#CountryName').val();
    form_data.append('CountryName', CountryName);

    var BankName = $('#BankName').val();
    if (BankName == "" || BankName == null || BankName == "undefined") {
        alertmessage("error", "Bank Name is Missing");
        $('#BankName').focus();
        return false;
    }
    form_data.append('BankName', BankName);
    var AccountNumber = $('#AccountNumber').val();
    if (AccountNumber == "" || AccountNumber == null || AccountNumber == "undefined") {
        alertmessage("error", "Account Number is Missing");
        $('#AccountNumber').focus();
        return false;
    }
    form_data.append('AccountNumber', AccountNumber);
    var IFSCCode = $('#IFSCCode').val();
    if (IFSCCode == "" || IFSCCode == null || IFSCCode == "undefined") {
        alertmessage("error", "IFSC Code is Missing");
        $('#IFSCCode').focus();
        return false;
    }
    form_data.append('IFSCCode', IFSCCode);
    var SwiftCode = $("#SwiftCode").val();
    form_data.append("SwiftCode", SwiftCode);
    var IbanNumber = $("#IbanNumber").val();
    form_data.append("IbanNumber", IbanNumber);
    var SortCode = $("#SortCode").val();
    form_data.append("SortCode", SortCode);
    var CurrencyCode = $("#CurrencyCode").val();
    form_data.append("CurrencyCode", CurrencyCode);


    var PanDocument = $('#PanDocument')[0].files[0];
    if (PanNumber) {
        if (!PanDocument) {
            alertmessage("error", "Pan document file is missing");
            $('#PanDocument').focus();
            return false
        }
    }
    if (PanDocument) form_data.append('PanDocument', PanDocument);

    var AadharDocument = $('#AadharDocument')[0].files[0];
    // AadharNumber
    if (AadharNumber) {
        if (!AadharDocument) {
            alertmessage("error", "Aadhar document file is missing");
            $('#AadharDocument').focus();
            return false
        }
    }
    if (AadharDocument) form_data.append('AadharDocument', AadharDocument);

    var ProfilePhoto = $('#ProfilePhoto')[0].files[0];
    // if(!ProfilePhoto){
    //     alertmessage("error", "Profile Photo is missing");
    //     $('#ProfilePhoto').focus();
    //     return false
    // }
    if (ProfilePhoto) form_data.append('ProfilePhoto', ProfilePhoto);

    var Cv = $('#Cv')[0].files[0];
    // if(!Cv){
    //     alertmessage("error", "Cv is missing");
    //     $('#Cv').focus();
    //     return false
    // }
    if (Cv) form_data.append('Cv', Cv);
    var CanceledCheque = $('#CanceledCheque')[0].files[0];
    // if(!CanceledCheque){
    //     alertmessage("error", "Canceled cheque a copy is missing");
    //      $('#CanceledCheque').focus();
    //     return false
    // }
    if (CanceledCheque) form_data.append('CanceledCheque', CanceledCheque);


    var ContactPersone1 = $("#ContactPersone1").val();
    form_data.append("ContactPersone1", ContactPersone1);
    var cp1Relationship = $("#cp1Relationship").val();
    form_data.append("cp1Relationship", cp1Relationship);
    var cp1MobileNumber = $("#cp1MobileNumber").val();
    form_data.append("cp1MobileNumber", cp1MobileNumber);
    var cp1EmailAddress = $("#cp1EmailAddress").val();
    form_data.append("cp1EmailAddress", cp1EmailAddress);
    var cp1Address = $("#cp1Address").val();
    form_data.append("cp1Address", cp1Address);
    var cp1PostalCode = $("#cp1PostalCode").val();
    form_data.append("cp1PostalCode", cp1PostalCode);
    var cp1Note = $("#cp1Note").val();
    form_data.append("cp1Note", cp1Note);

    var ContactPersone2 = $("#ContactPersone2").val();
    form_data.append("ContactPersone2", ContactPersone2);
    var cp2Relationship = $("#cp2Relationship").val();
    form_data.append("cp2Relationship", cp2Relationship);
    var cp2MobileNumber = $("#cp2MobileNumber").val();
    form_data.append("cp2MobileNumber", cp2MobileNumber);
    var cp2EmailAddress = $("#cp2EmailAddress").val();
    form_data.append("cp2EmailAddress", cp2EmailAddress);
    var cp2Address = $("#cp2Address").val();
    form_data.append("cp2Address", cp2Address);
    var cp2PostalCode = $("#cp2PostalCode").val();
    form_data.append("cp2PostalCode", cp2PostalCode);
    var cp2Note = $("#cp2Note").val();
    form_data.append("cp2Note", cp2Note);

    var isPrimaryRadio = $("input[name='isPrimaryRadio']:checked").val();
    form_data.append("isPrimaryRadio", isPrimaryRadio);

    // var EmployeeID = $("#submitEmployeeRegistrationbtn").data("btnEmployeeID");
    // form_data.append("EmployeeID", EmployeeID);
    var EmployeeID = $("#submitEmployeeRegistrationbtn").data("btnEmployeeID");
    EmployeeID = (EmployeeID === undefined || EmployeeID === null) ? "" : EmployeeID;
    form_data.append("EmployeeID", EmployeeID);
    var key = $("body").data("key");
    $("#submitEmployeeRegistrationbtn").prop("disabled", true);
    $.ajax({
        url: routurl + "modal/hrm_modal.php?action=EmployeeRegistration",
        type: "POST",
        data: form_data,
        contentType: false, // Important for file uploads
        processData: false, // Prevent jQuery from processing the data
        cache: false,
        dataType: 'JSON',
        success: function (response) {
            // Handle the success response
            // console.log(response);
            if (response.status == "Success" || response.status == "success") {
                // alertmessage('success', response.message);
                if ($("body").data("loginroleid") < 4) {
                    location.href = routurl + "employeeWork.php?k=" + key + "&empid=" + response.empid;
                } else {
                    SweetAlertmessageFadein("success", response.message + " Employee Id is : " + response.empid);
                }
            } else {
                alertmessage('error', response.message);
            }
        },
        error: function (xhr, status, error) {
            $("#submitEmployeeRegistrationbtn").prop("disabled", false);
            console.error("Error:", error);
            console.trace();
            alert("An error occurred while submitting the form.");
        },
    });
});


// submit the work details of employee
$(document).on("click", "#employeeworkdetailupdbtn", function () {
    var routurl = $("body").data("routurl");
    if (!routurl) {
        alert("Route URL is not defined!");
        return;
    }
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    var form_data = new FormData();
    form_data.append('companyid', companyid);
    form_data.append('subcompanyid', subcompanyid);
    form_data.append('projectid', projectid);
    form_data.append('loginid', loginid);
    form_data.append('addedby', $("body").data("loginname"));
    var EmployeeId = $("#employeeworkdetailupdbtn").data("wrkemployeeid");
    form_data.append('EmployeeID', $("#employeeworkdetailupdbtn").data("wrkemployeeid"));
    var StartDate = $("#StartDate").val();
    if (StartDate == "" || StartDate == null || StartDate == "undefined") {
        alertmessage("error", "Date of Joining is Missing");
        $('#StartDate').focus();
        return false;
    }
    form_data.append('StartDate', StartDate);

    var employmentType = $("#employmentType").val();
    if (employmentType == "" || employmentType == null || employmentType == "undefined") {
        alertmessage("error", "Employment Type is Missing");
        $('#employmentType').focus();
        return false;
    }
    form_data.append('employmentType', employmentType);

    var department = $("#department").val();
    if (department == "" || department == null || department == "undefined") {
        alertmessage("error", "Department selection is Missing");
        $('#department').focus();
        return false;
    }
    form_data.append('department', department);
    var designation = $("#designation").val();
    if (designation == "" || designation == null || designation == "undefined") {
        alertmessage("error", "Designation is Missing");
        $('#designation').focus();
        return false;
    }
    form_data.append('designation', designation);

    let workinghour = $("#workinghourSelect").val();
    if (workinghour == "" || workinghour == null || workinghour == "undefined") {
        alertmessage("error", "Working Timing missing");
        $('#workinghourSelect').focus();
        return false;
    }
    form_data.append('workinghour', workinghour);


    let WorkingLocation = $("#WorkingLocation").val();
    if (WorkingLocation == "" || WorkingLocation == null || WorkingLocation == "undefined") {
        alertmessage("error", "Working Location Missing");
        $('#WorkingLocation').focus();
        return false;
    }
    form_data.append('WorkingLocation', WorkingLocation);



    let selectedWeekOff = $(".weekoffday:checked").map(function () {
        return $(this).val();
    }).get().join(",");
    form_data.append('WeekOff', selectedWeekOff);

    // Personal / statutory / bank fields (only sent when present on the page)
    if ($("#wrkFatherName").length) {
        let wrkMobile = ($("#wrkMobileNo").val() || "").trim();
        if (wrkMobile !== "" && !/^\+?[0-9]{10,13}$/.test(wrkMobile)) {
            alertmessage("error", "Enter a valid Mobile Number");
            $('#wrkMobileNo').focus();
            return false;
        }
        let wrkIfsc = ($("#wrkIFSCCode").val() || "").trim().toUpperCase();
        if (wrkIfsc !== "" && !/^[A-Z]{4}0[A-Z0-9]{6}$/.test(wrkIfsc)) {
            alertmessage("error", "Enter a valid 11-character IFSC Code (e.g. SBIN0001234)");
            $('#wrkIFSCCode').focus();
            return false;
        }
        form_data.append('FatherName', ($("#wrkFatherName").val() || "").trim());
        form_data.append('MobileNumber', wrkMobile);
        form_data.append('epf_regid', ($("#wrkEpfRegId").val() || "").trim());
        form_data.append('esic_regid', ($("#wrkEsicRegId").val() || "").trim());
        form_data.append('BankName', ($("#wrkBankName").val() || "").trim());
        form_data.append('AccountNumber', ($("#wrkAccountNumber").val() || "").trim());
        form_data.append('IFSCCode', wrkIfsc);
    }

    // Net Salary: only sent when changed, so saving work details never re-splits the salary by itself
    if ($("#wrkNetSalary").length) {
        let netNew = ($("#wrkNetSalary").val() || "").trim();
        let netOld = String($("#wrkNetSalary").data("original") || "");
        if (netNew === "" || !(parseFloat(netNew) > 0)) {
            alertmessage("error", "Enter a valid Net Salary amount");
            $('#wrkNetSalary').focus();
            return false;
        }
        if (parseFloat(netNew) !== parseFloat(netOld)) {
            form_data.append('NetSalary', netNew);
        }
    }


    $.ajax({
        url: routurl + "modal/hrm_modal.php?action=AddUpdateEmployeeWorkDetails",
        type: "POST",
        data: form_data,
        contentType: false, // Important for file uploads
        processData: false, // Prevent jQuery from processing the data
        cache: false,
        dataType: 'JSON',
        success: function (response) {
            // Handle the success response
            // console.log(response);
            if (response.status == "Success" || response.status == "success") {
                SweetAlertmessageFadein('success', response.message);
                gotoCompleteSalaryDetails(EmployeeId);
            } else {
                alertmessage('error', response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
            console.error("Error:", error);
            // Log the stack trace, which includes line numbers
            console.trace();
            alert("An error occurred while submitting the form.");
        },
    });


});

function fetchEmployeeWeekOffData_callback(EmployeeID, callback) {
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("EmployeeID", EmployeeID);
    $.ajax
        ({
            type: "POST",
            url: routurl + "modal/hrm_modal.php?action=fetchEmployeeWeekOffData_callback",
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

function fetchEmployeeDatawithOtherifno_callback(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("sortby", shortby);
    form_data.append("sortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: routurl + "modal/hrm_modal.php?action=fetchEmployeeDatawithOtherifno_callback",
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

function EmployeeSummaryListwithDetails() {
    let form_data = new FormData();
    let routurl = $("body").data("routurl");
    form_data.append("companyid", $("body").data("companyid"));
    form_data.append("subcompanyid", $("body").data("subcompanyid"));
    form_data.append("projectid", $("body").data("projectid"));
    $.ajax({
        type: "POST",
        url: routurl + "modal/hrm_modal.php?action=getEmployeeSummarywithDetails",
        data: form_data,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (res) {
            employeeSummaryData = res;
            // KPI Counters
            $(".counter[data-key='activeemployeecount']").text(res.Active.count);
            $(".counter[data-key='inactiveemployeecount']").text(res.Resigned.count);
            $(".counter[data-key='newjoineremployeecount']").text(res["New Joiner"].count);
            $(".counter[data-key='totalemployeecount']").text(res.Total.count);
            showDefaultEmployeeMasterView();
        }
    });
}

function showDefaultEmployeeMasterView() {
    let $default = $('.kpi-card[data-view="activeemployeedata"]');
    $(".kpi-card").removeClass("active");
    $default.addClass("active");
    renderPayrollEmployeeTable("Active");
}

// =====================================================================
// 👥 Employee List (employees.php): all details + column sort + inline edit
// =====================================================================
var empListSort = { key: null, dir: 'asc' };
var empListCurrentType = "Active";
var empEditOptions = null;       // { dept:[], desig:[], loc:[], hours:[] } loaded once
var empEditOptionsLoading = null;

function empEsc(v) {
    return String(v === undefined || v === null ? "" : v)
        .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
}

function empIsResigned(emp) {
    return String(emp.Status).toLowerCase() === "resigned" ||
        (emp.DateOfResignation && emp.DateOfResignation !== "0000-00-00" && emp.DateOfResignation !== "");
}

function empLocationText(emp) {
    if (emp.LocationName) return emp.LocationName;
    if (emp.LocationId === "outoffice" || emp.attendanceType === "outoffice") return "Outside Office";
    return "";
}

function empHoursText(emp) {
    if (!emp.InTime || !emp.OutTime) return "";
    return (typeof formatTime_Hi === "function")
        ? formatTime_Hi(emp.InTime) + " - " + formatTime_Hi(emp.OutTime)
        : emp.InTime + " - " + emp.OutTime;
}

function empDateText(d) {
    if (!d || d === "0000-00-00") return "";
    const p = String(d).split("-");
    return p.length === 3 ? p[2] + "/" + p[1] + "/" + p[0] : d;
}

function empNetSalary(emp) {
    return parseFloat(emp.NetSalary || emp.GrossSalary || 0) || 0;
}

// --- Sorting -----------------------------------------------------------
function empSortValue(emp, key) {
    let v;
    switch (key) {
        case "EmployeeID": return parseInt(emp.EmployeeID, 10) || 0;
        case "NetSalary": return empNetSalary(emp);
        case "LocationDisplay": v = empLocationText(emp); break;
        case "DateOfJoining": v = (emp.DateOfJoining && emp.DateOfJoining !== "0000-00-00") ? emp.DateOfJoining : ""; break; // YYYY-MM-DD sorts as text
        default: v = emp[key];
    }
    v = (v === undefined || v === null) ? "" : String(v).trim();
    return v === "" ? null : v.toLowerCase();
}

function empSortList(list) {
    if (!empListSort.key) return list.slice();
    const key = empListSort.key, mul = empListSort.dir === "asc" ? 1 : -1;
    return list
        .map((emp, idx) => ({ emp, idx, val: empSortValue(emp, key) }))
        .sort((a, b) => {
            if (a.val === null && b.val === null) return a.idx - b.idx;
            if (a.val === null) return 1;      // blanks always last
            if (b.val === null) return -1;
            if (typeof a.val === "number" && typeof b.val === "number") return (a.val - b.val) * mul || a.idx - b.idx;
            return String(a.val).localeCompare(String(b.val), undefined, { numeric: true }) * mul || a.idx - b.idx;
        })
        .map(x => x.emp);
}

function empUpdateSortIcons() {
    $("#emplistPayrolltbl thead th[data-sort]").each(function () {
        let icon = "ti-arrows-sort opacity-50";
        if (empListSort.key && $(this).data("sort") === empListSort.key) {
            icon = empListSort.dir === "asc" ? "ti-sort-ascending" : "ti-sort-descending";
        }
        $(this).find(".emp-sort-icon").attr("class", "ti " + icon + " emp-sort-icon ms-1");
    });
}

$(document).on("click", "#emplistPayrolltbl thead th[data-sort]", function () {
    const key = $(this).data("sort");
    if (key === "sno") {
        empListSort = { key: null, dir: "asc" };
    } else if (empListSort.key === key) {
        empListSort.dir = empListSort.dir === "asc" ? "desc" : "asc";
    } else {
        empListSort = { key: key, dir: "asc" };
    }
    renderPayrollEmployeeTable(empListCurrentType);
});

// --- Row building ------------------------------------------------------
function empEditCell(emp, field, text, extraClass) {
    const canEdit = !empIsResigned(emp);
    const shown = (text === "" || text === null || text === undefined)
        ? `<span class="emp-empty">-</span>` : empEsc(text);
    return `<td class="emp-edit-cell ${extraClass || ""}" data-field="${field}">
                <span class="emp-cell-val">${shown}</span>${canEdit ? `<i class="ti ti-pencil emp-edit-icon" tabindex="0" title="Edit"></i>` : ""}
            </td>`;
}

function empBuildRow(emp, sno) {
    const routurl = $("body").data("routurl");
    const key = $("body").data("key");
    const isResigned = empIsResigned(emp);

    let rawPhoto = emp.empPhotopath || emp.ProfilePhoto || "";
    let profilepath = routurl + "assets/img/profiles/profile.jpg";
    if (rawPhoto && rawPhoto.trim() !== "" && !rawPhoto.toLowerCase().includes("no file") && !rawPhoto.toLowerCase().includes("error")) {
        let cleanPhoto = rawPhoto.trim();
        profilepath = /^https?:\/\//.test(cleanPhoto) ? cleanPhoto : routurl + cleanPhoto.replace(/^(\.\.\/|\.\/)+/, "");
    }

    let joinCell = empEditCell(emp, "DateOfJoining", empDateText(emp.DateOfJoining));
    if (isResigned) {
        const remarkHtml = emp.remark ? `<br/><small class="text-muted"><strong>Remark:</strong> ${empEsc(emp.remark)}</small>` : "";
        joinCell = `<td class="emp_doj">
                <div class="text-dark fw-medium mb-1">${empEsc(empDateText(emp.DateOfJoining) || "-")}</div>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fw-semibold"><i class="ti ti-calendar-x me-1"></i>Resigned: ${empEsc(empDateText(emp.DateOfResignation) || "-")}</span>${remarkHtml}
            </td>`;
    }

    const statusBadge = emp.Status === "Active"
        ? `<span class="badge bg-success">Active</span>`
        : emp.Status === "Resigned"
            ? `<span class="badge bg-danger">Resigned</span>`
            : `<span class="badge bg-secondary">${empEsc(emp.Status || "-")}</span>`;

    const actionHtml = isResigned
        ? `<span class="badge bg-danger">Resigned</span>`
        : `<button class="btn btn-sm btn-primary" onclick="gotoCompletebasicdetails(${parseInt(emp.EmployeeID, 10)})">Edit / View</button>
           <i class="btn ti ti-trash text-danger deletemodal" style="font-size: 22px;" title="Remove"></i>`;

    const empLink = `${routurl}empPanel/index.php?k=${encodeURIComponent(key)}&empid=${encodeURIComponent(emp.EmployeeID)}&accessby=adminu`;

    const $tr = $(`
        <tr data-empid="${empEsc(emp.EmployeeID)}" data-empfullname="${empEsc(emp.FullName)}" data-dob="${empEsc(emp.DateOfBirth)}">
            <td class="text-center emp-sno">${sno}</td>
            <td class="emp_name emp-edit-cell" data-field="FullName">
                <div class="d-flex align-items-center">
                    <a href="${empLink}">
                        <img src="${empEsc(profilepath)}" onerror="this.onerror=null; this.src='${routurl}assets/img/profiles/profile.jpg';" style="width:26px; height:26px; object-fit:cover;" class="rounded-circle" alt="Profile" />
                    </a>
                    <div class="ms-2">
                        <a href="${empLink}"><strong>${empEsc(emp.EmployeeID)} - ${empEsc(emp.FullName)}</strong></a>${isResigned ? "" : `<i class="ti ti-pencil emp-edit-icon" tabindex="0" title="Edit name"></i>`}
                    </div>
                </div>
            </td>
            ${empEditCell(emp, "FatherName", emp.FatherName)}
            ${empEditCell(emp, "MobileNo", emp.MobileNo)}
            ${empEditCell(emp, "DepartmentID", emp.DepartmentName)}
            ${empEditCell(emp, "DesignationID", emp.DesignationName)}
            ${empEditCell(emp, "WorkLocation", empLocationText(emp), "emp_worklocation")}
            ${empEditCell(emp, "WorkHourseId", empHoursText(emp))}
            ${joinCell}
            ${empEditCell(emp, "EpfRegId", emp.EpfRegId)}
            ${empEditCell(emp, "EsicRegId", emp.EsicRegId)}
            ${empEditCell(emp, "BankName", emp.BankName)}
            ${empEditCell(emp, "AccountNumber", emp.AccountNumber)}
            ${empEditCell(emp, "IFSCCode", emp.IFSCCode)}
            ${empEditCell(emp, "NetSalary", "₹ " + empNetSalary(emp).toLocaleString("en-IN"), "text-end")}
            <td>${statusBadge}</td>
            <td class="noexport">${actionHtml}</td>
        </tr>
    `);
    $tr.data("empData", emp);
    return $tr;
}

function renderPayrollEmployeeTable(type) {
    empListCurrentType = type;
    const tbody = $("#emplistPayrolltbl tbody");
    $("#employeemasterheader").text(type + " Employee List");
    tbody.empty();
    empUpdateSortIcons();
    const list = (typeof employeeSummaryData !== "undefined" && employeeSummaryData[type]?.list) || [];
    if (!list.length) {
        tbody.append(`<tr><td colspan="17" class="text-center text-muted">No employee data available</td></tr>`);
        return;
    }

    empSortList(list).forEach(function (emp, i) {
        tbody.append(empBuildRow(emp, i + 1));
    });

    if (typeof populateWorkingLocations === "function") {
        populateWorkingLocations(list);
    }
    if (typeof filterEmployeeTable === "function") {
        filterEmployeeTable();
    }
}

// --- Inline edit -------------------------------------------------------
function empLoadEditOptions() {
    if (empEditOptions) return $.Deferred().resolve(empEditOptions).promise();
    if (empEditOptionsLoading) return empEditOptionsLoading;
    const dDept = $.Deferred(), dDesig = $.Deferred(), dLoc = $.Deferred(), dHours = $.Deferred();
    const safe = (fn, d) => { try { fn(r => d.resolve(Array.isArray(r) ? r : [])); } catch (e) { d.resolve([]); } setTimeout(() => d.resolve([]), 15000); };
    safe(cb => departmentlist_callback("", "", cb), dDept);
    safe(cb => designationlist_callback("", "", cb), dDesig);
    safe(cb => AttendanceLocationMaster_callback("all", "all", cb), dLoc);
    safe(cb => WorkingHoursDataCallback(cb), dHours);
    empEditOptionsLoading = $.when(dDept, dDesig, dLoc, dHours).then(function (dept, desig, loc, hours) {
        empEditOptions = { dept: dept, desig: desig, loc: loc, hours: hours };
        empEditOptionsLoading = null;
        return empEditOptions;
    });
    return empEditOptionsLoading;
}

function empSelectHtml(items, valueKey, labelFn, current) {
    let html = `<option value="" disabled ${current ? "" : "selected"}>Select</option>`;
    items.forEach(function (it) {
        const val = String(it[valueKey]);
        html += `<option value="${empEsc(val)}" ${String(current) === val ? "selected" : ""}>${empEsc(labelFn(it))}</option>`;
    });
    return `<select class="form-select emp-modal-input">${html}</select>`;
}

// --- Edit modal (one field of one employee) -----------------------------
var empEditCtx = null; // { empId, field }

const EMP_FIELD_LABELS = {
    FullName: "Employee Name", FatherName: "Father's Name", MobileNo: "Mobile Number",
    DepartmentID: "Department", DesignationID: "Designation", WorkLocation: "Working Location",
    WorkHourseId: "Working Hours", DateOfJoining: "Joining Date", EpfRegId: "EPF UAN",
    EsicRegId: "ESIC IP No", BankName: "Bank Name", AccountNumber: "Account Number",
    IFSCCode: "IFSC Code", NetSalary: "Net Salary (Monthly)"
};

function empFindEmp(empId) {
    const $tr = $(`#emplistPayrolltbl tbody tr[data-empid="${empEsc(empId)}"]`);
    return { $tr: $tr, emp: $tr.data("empData") };
}

function empOpenEditor($td) {
    const $tr = $td.closest("tr");
    const emp = $tr.data("empData");
    const field = $td.data("field");
    if (!emp || !field || empIsResigned(emp)) return;

    empEditCtx = { empId: emp.EmployeeID, field: field };
    const $modal = $("#empFieldEditModal");
    $("#empFieldEditTitle").text("Edit " + (EMP_FIELD_LABELS[field] || field));
    $("#empFieldEditWho").text(emp.EmployeeID + " - " + (emp.FullName || ""));
    $("#empFieldEditHint").text("");
    $("#empFieldEditSave").prop("disabled", false).html('<i class="ti ti-device-floppy me-1"></i>Save');

    const textInput = (val, attrs) => `<input type="text" class="form-control emp-modal-input" value="${empEsc(val || "")}" ${attrs || ""}>`;
    const setBody = (html, hint) => {
        $("#empFieldEditBody").html(`<label class="form-label fw-semibold">${empEsc(EMP_FIELD_LABELS[field] || field)}</label>` + html);
        if (hint) $("#empFieldEditHint").text(hint);
        setTimeout(() => $("#empFieldEditBody").find("input, select").first().trigger("focus").trigger("select"), 250);
    };

    switch (field) {
        case "FullName": {
            const titles = ["", "Mr.", "Ms.", "Mrs.", "Miss"];
            const tOpts = titles.map(t => `<option value="${t}" ${String(emp.TitleName || "") === t ? "selected" : ""}>${t || "-"}</option>`).join("");
            $("#empFieldEditBody").html(`
                <div class="row g-2">
                    <div class="col-4 col-md-3"><label class="form-label fw-semibold">Title</label><select class="form-select" id="empNameTitle">${tOpts}</select></div>
                    <div class="col-8 col-md-9"><label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="empNameFirst" maxlength="150" value="${empEsc(emp.FirstName || "")}"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Middle Name</label><input type="text" class="form-control" id="empNameMiddle" maxlength="150" value="${empEsc(emp.MiddleName || "")}"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Last Name</label><input type="text" class="form-control" id="empNameLast" maxlength="150" value="${empEsc(emp.LastName || "")}"></div>
                </div>`);
            setTimeout(() => $("#empNameFirst").trigger("focus"), 250);
            break;
        }
        case "FatherName": setBody(textInput(emp.FatherName, 'maxlength="150"')); break;
        case "MobileNo": setBody(textInput(emp.MobileNo, 'maxlength="15" inputmode="numeric"'), "10 digit mobile number (country code optional)."); break;
        case "EpfRegId": setBody(textInput(emp.EpfRegId, 'maxlength="95"'), "Saved as regid for DeductionID 1. Does not switch EPF on or off."); break;
        case "EsicRegId": setBody(textInput(emp.EsicRegId, 'maxlength="95"'), "Saved as regid for DeductionID 3. Does not switch ESIC on or off."); break;
        case "BankName": setBody(textInput(emp.BankName)); break;
        case "AccountNumber": setBody(textInput(emp.AccountNumber, 'inputmode="numeric"')); break;
        case "IFSCCode": setBody(textInput(emp.IFSCCode, 'maxlength="11" style="text-transform:uppercase"'), "11 characters, e.g. SBIN0001234."); break;
        case "DateOfJoining":
            setBody(`<input type="date" class="form-control emp-modal-input" value="${empEsc(emp.DateOfJoining && emp.DateOfJoining !== "0000-00-00" ? emp.DateOfJoining : "")}">`);
            break;
        case "NetSalary": {
            const parts = `Now: Basic ₹${(parseFloat(emp.BasicSalary) || 0).toLocaleString("en-IN")} · HRA ₹${(parseFloat(emp.HRA) || 0).toLocaleString("en-IN")} · Conveyance ₹${(parseFloat(emp.ConveyanceAllowance) || 0).toLocaleString("en-IN")} · Special ₹${(parseFloat(emp.SpecialAllowance) || 0).toLocaleString("en-IN")}. `;
            setBody(`<div class="input-group"><span class="input-group-text">₹</span><input type="number" min="1" step="0.01" class="form-control emp-modal-input" value="${empNetSalary(emp) || ""}"></div>`,
                parts + "The new amount is split the same way (Special Allowance takes the balance). Applies from the next salary calculation.");
            break;
        }
        default:
            $("#empFieldEditBody").html(`<div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading options...</div>`);
            empLoadEditOptions().then(function (opt) {
                if (!empEditCtx || empEditCtx.field !== field) return;
                const sel = empSelectHtml;
                if (field === "DepartmentID") {
                    setBody(sel(opt.dept, "DepartmentID", it => it.DepartmentName, emp.DepartmentID));
                } else if (field === "DesignationID") {
                    setBody(sel(opt.desig, "DesignationID", it => it.DesignationName, emp.DesignationID));
                } else if (field === "WorkLocation") {
                    const locs = opt.loc.slice();
                    locs.push({ LocationId: "outoffice", LocationName: "Outside Office" });
                    setBody(sel(locs, "LocationId", it => it.LocationName, emp.LocationId), "\"Outside Office\" lets the employee mark attendance from anywhere.");
                } else if (field === "WorkHourseId") {
                    const fmt = t => (typeof formatTime_Hi === "function" ? formatTime_Hi(t) : t);
                    setBody(sel(opt.hours, "id", it => `${it.working_hour_name} (${fmt(it.start_time)} - ${fmt(it.end_time)})`, emp.WorkHourseId), "Also updates the In / Out time used for late / early marking.");
                }
            });
    }
    $modal.modal("show");
}

// apply a saved change to every copy of this employee (Active / Total / New Joiner lists)
function empApplyChange(empId, changes) {
    if (typeof employeeSummaryData === "undefined") return;
    Object.keys(employeeSummaryData).forEach(function (k) {
        (employeeSummaryData[k]?.list || []).forEach(function (e) {
            if (String(e.EmployeeID) === String(empId)) Object.assign(e, changes);
        });
    });
}

function empSaveEditor() {
    if (!empEditCtx) return;
    const field = empEditCtx.field;
    const found = empFindEmp(empEditCtx.empId);
    const emp = found.emp;
    if (!emp) { $("#empFieldEditModal").modal("hide"); return; }

    const fd = new FormData();
    fd.append("addedby", $("body").data("loginname") || "");
    fd.append("companyid", $("body").data("companyid"));
    fd.append("subcompanyid", $("body").data("subcompanyid"));
    fd.append("projectid", $("body").data("projectid"));
    fd.append("EmployeeID", emp.EmployeeID);
    fd.append("field", field);

    let value = "", $input = $();
    if (field === "FullName") {
        const first = ($("#empNameFirst").val() || "").trim();
        if (!first) { alertmessage("error", "First Name is required"); $("#empNameFirst").focus(); return; }
        value = first;
        fd.append("TitleName", $("#empNameTitle").val() || "");
        fd.append("FirstName", first);
        fd.append("MiddleName", ($("#empNameMiddle").val() || "").trim());
        fd.append("LastName", ($("#empNameLast").val() || "").trim());
    } else {
        $input = $("#empFieldEditBody .emp-modal-input");
        if (!$input.length) return; // options still loading
        value = ($input.val() || "").trim();
        if (field === "IFSCCode") value = value.toUpperCase();

        if (field === "MobileNo" && value !== "" && !/^\+?[0-9]{10,13}$/.test(value)) {
            alertmessage("error", "Enter a valid Mobile Number"); $input.focus(); return;
        }
        if (field === "IFSCCode" && value !== "" && !/^[A-Z]{4}0[A-Z0-9]{6}$/.test(value)) {
            alertmessage("error", "Enter a valid 11-character IFSC Code (e.g. SBIN0001234)"); $input.focus(); return;
        }
        if (["DepartmentID", "DesignationID", "WorkLocation", "WorkHourseId", "DateOfJoining"].includes(field) && value === "") {
            alertmessage("error", "Please select a value"); $input.focus(); return;
        }
        if (field === "NetSalary") {
            const amt = parseFloat(value);
            if (!(amt > 0)) { alertmessage("error", "Enter a valid Net Salary amount"); $input.focus(); return; }
            if (amt === empNetSalary(emp)) { $("#empFieldEditModal").modal("hide"); return; } // unchanged
        }
    }
    fd.append("value", value);

    const $btn = $("#empFieldEditSave");
    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
    $.ajax({
        type: "POST",
        url: $("body").data("routurl") + "modal/hrm_modal.php?action=updateEmployeeSingleField",
        data: fd, contentType: false, processData: false, cache: false, dataType: "json",
        success: function (res) {
            if (!res || res.status !== "success") {
                alertmessage("error", (res && res.message) || "Update failed");
                $btn.prop("disabled", false).html('<i class="ti ti-device-floppy me-1"></i>Save');
                return;
            }
            const saved = (res.value !== undefined) ? res.value : value;
            const changes = {};
            const label = $input.is("select") ? $input.find("option:selected").text().trim() : saved;
            switch (field) {
                case "FullName":
                    Object.assign(changes, { FullName: saved, TitleName: res.TitleName, FirstName: res.FirstName, MiddleName: res.MiddleName, LastName: res.LastName });
                    break;
                case "DepartmentID": changes.DepartmentID = saved; changes.DepartmentName = label; break;
                case "DesignationID": changes.DesignationID = saved; changes.DesignationName = label; break;
                case "WorkLocation":
                    changes.LocationId = saved;
                    changes.attendanceType = res.attendanceType || (saved === "outoffice" ? "outoffice" : "office");
                    changes.LocationName = saved === "outoffice" ? "" : label;
                    break;
                case "WorkHourseId": changes.WorkHourseId = saved; changes.InTime = res.InTime || emp.InTime; changes.OutTime = res.OutTime || emp.OutTime; break;
                case "NetSalary": Object.assign(changes, res.breakup || { NetSalary: saved, GrossSalary: saved }); break;
                default: changes[field] = saved;
            }
            empApplyChange(emp.EmployeeID, changes);
            Object.assign(emp, changes);
            const $tr = empFindEmp(emp.EmployeeID).$tr;
            const $newTr = empBuildRow(emp, $tr.find(".emp-sno").text());
            $tr.replaceWith($newTr);
            $newTr.addClass("table-success");
            setTimeout(() => $newTr.removeClass("table-success"), 1500);
            if (field === "WorkLocation" && typeof populateWorkingLocations === "function") {
                populateWorkingLocations(employeeSummaryData[empListCurrentType]?.list || []);
            }
            empEditCtx = null;
            $("#empFieldEditModal").modal("hide");
            if (typeof iziToast !== "undefined") {
                iziToast.success({ title: "Saved", message: (EMP_FIELD_LABELS[field] || field) + " updated for " + emp.EmployeeID, position: "topRight", timeout: 1800 });
            }
        },
        error: function () {
            alertmessage("error", "Something went wrong while saving.");
            $btn.prop("disabled", false).html('<i class="ti ti-device-floppy me-1"></i>Save');
        }
    });
}

$(document).on("click", "#emplistPayrolltbl .emp-edit-icon", function (e) {
    e.preventDefault();
    e.stopPropagation();
    empOpenEditor($(this).closest("td"));
});
$(document).on("keydown", "#emplistPayrolltbl .emp-edit-icon", function (e) {
    if (e.key === "Enter" || e.key === " ") { e.preventDefault(); empOpenEditor($(this).closest("td")); }
});
$(document).on("click", "#empFieldEditSave", function () { empSaveEditor(); });
$(document).on("keydown", "#empFieldEditModal input", function (e) {
    if (e.key === "Enter") { e.preventDefault(); empSaveEditor(); }
});
$(document).on("hidden.bs.modal", "#empFieldEditModal", function () {
    empEditCtx = null;
    $("#empFieldEditBody").empty();
});


function removEmployeeFromMaster(rmemid, rmremark, dor, btnid, callback) {
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    var loginname = $("body").data("loginname");
    form_data.append("loginname", loginname);
    form_data.append("rmemid", rmemid);
    form_data.append("rmremark", rmremark);
    form_data.append("dor", dor);
    $.ajax
        ({
            type: "POST",
            url: routurl + "modal/hrm_modal.php?action=removEmployeeFromMaster",
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

function fetchEmployeeSalaryFromMaster_callback(EmployeeId, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("EmployeeId", EmployeeId);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_salarymodal.php?action=fetchEmployeeSalaryFromMaster_callback",
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

function fetchEmployeeAdvanceFromMaster_callback(shortby, shortbyvalue, callback) {
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
            url: baseurl + "modal/hrm_salarymodal.php?action=fetchEmployeeAdvanceFromMaster",
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

function EmployeeCalculatedSalary_callback(salaryyear, salarymonth, shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("salaryyear", salaryyear);
    form_data.append("salarymonth", salarymonth);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: routurl + "modal/hrm_modal.php?action=EmployeeCalculatedSalary_callback",
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

function EmployeeSalaryforpayslip(salaryyear, salarymonth, shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("salaryyear", salaryyear);
    form_data.append("salarymonth", salarymonth);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_salarymodal.php?action=EmployeeSalaryforpayslip",
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

function SalaryYear_callback(callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=SalaryYear_callback",
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

function EmployeeAttendanceYearMonth_callback(shortby, shortbyvalue, callback) {
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
            url: baseurl + "modal/hrm_modal.php?action=EmployeeAttendanceYearMonth",
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

function EmployeeAttendanceSummaryDetails(EmployeeId, shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("EmployeeId", EmployeeId);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=EmployeeAttendanceSummaryDetails",
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

function getEmployeeMonthlyAttendanceSummary(EmployeeId, attyear, attmonth, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("EmployeeId", EmployeeId);
    form_data.append("attyear", attyear);
    form_data.append("attmonth", attmonth);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=getEmployeeMonthlyAttendanceSummary",
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

function EmployeeAttendanceDetails(EmployeeId, shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("EmployeeId", EmployeeId);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=EmployeeAttendanceDetails",
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

function FullAttendanceDetailsOfEmployee(shortby, shortbyvalue, callback) {
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
            url: baseurl + "modal/hrm_modal.php?action=FullAttendanceDetailsOfEmployee",
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

function fetchEmployeeLoginIdPassword_callback(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
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
            url: routurl + "modal/hrm_modal.php?action=fetchEmployeeLoginIdPassword",
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

function UpdateEmployeeEditedAttendance(tableID, editattRemarkId, UpdateBtnID) {
    const form_data = new FormData();
    const baseurl = $("body").data("routurl");
    const companyid = $("body").data("companyid");
    const subcompanyid = $("body").data("subcompanyid");
    const projectid = $("body").data("projectid");
    const editremark = $("#" + editattRemarkId + "").val();

    if (!editremark || !editremark.trim()) {
        Swal.fire({
            icon: "warning",
            title: "Remarks Required",
            text: "Remarks / reason for edit is required.",
        });
        return;
    }

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginid", $("body").data("loginid") || "");
    form_data.append("loginroleid", $("body").data("loginroleid") || "");
    form_data.append("loginname", $("body").data("loginname") || "");
    form_data.append("editremark", editremark);
    const proofFile = $("#editattprooffile")[0].files[0];
    if (proofFile) {
        form_data.append("ProofFile", proofFile);
    }

    // ✅ Collect edited attendance data
    const ArrayEditAttDataList = [];
    let errorType = ""; // "" | "in" | "out" | "duration"

    $("#" + tableID + " tbody tr").each(function () {
        const isDeleted = $(this).attr("data-deleted") === "1" || $(this).data("deleted") == 1;
        const attId = $(this).data("attid") || $(this).attr("data-attid") || "";
        const empId = $(this).data("empid") || $(this).attr("data-empid") || "";

        if (isDeleted) {
            if (attId) {
                ArrayEditAttDataList.push({
                    "attendanceID": attId,
                    "EmployeeID": empId,
                    "isDeleted": 1
                });
            }
            return true;
        }

        const markIn = $(this).find("input.checkininput").val() || "";
        const markOut = $(this).find("input.checkoutinput").val() || "";
        const workingHours = $(this).find("span.workinghours").text().trim() || "";

        if (!markIn) {
            errorType = "in";
            return false;
        }
        if (!markOut) {
            errorType = "out";
            return false;
        }

        if (workingHours) {
            const parts = workingHours.split(":");
            const hours = parseInt(parts[0]) || 0;
            if (hours >= 12) {
                errorType = "duration";
                return false;
            }
        }

        ArrayEditAttDataList.push({
            "attendanceID": $(this).data("attid") || "",
            "EmployeeID": $(this).data("empid") || "",
            "MarkInTime": markIn,
            "MarkOutTime": markOut,
            "WorkingHours": workingHours
        });
    });

    if (errorType) {
        let title = "";
        let text = "";
        if (errorType === "in") {
            title = "Mark In Missing";
            text = "Please provide a Mark In time for all records before updating.";
        } else if (errorType === "out") {
            title = "Mark Out Missing";
            text = "Please provide a Mark Out time for all records before updating.";
        } else if (errorType === "duration") {
            title = "Invalid Duration";
            text = "Working duration must be less than 12 hours.";
        }
        Swal.fire({
            icon: "warning",
            title: title,
            text: text,
        });
        return;
    }

    // ✅ Validate
    if (ArrayEditAttDataList.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "No Records Found",
            text: "There are no attendance records to update.",
        });
        return;
    }
    console.log(ArrayEditAttDataList);
    form_data.append("EditedAttDataList", JSON.stringify(ArrayEditAttDataList));

    // ✅ Confirmation before update
    Swal.fire({
        title: "Confirm Update",
        text: "Are you sure you want to update the edited attendance records?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, Update",
        cancelButtonText: "Cancel",
    }).then((result) => {
        if (result.isConfirmed) {
            $("#" + UpdateBtnID).prop("disabled", true).text("Updating...");

            $.ajax({
                type: "POST",
                url: baseurl + "modal/hrm_modal.php?action=UpdateEmployeeEditedAttendance",
                data: form_data,
                contentType: false,
                processData: false,
                cache: false,
                dataType: "json",
                success: function (res) {
                    $("#" + UpdateBtnID).prop("disabled", false).text("Update");

                    if (res.status === "success") {
                        Swal.fire({
                            icon: "success",
                            title: "Attendance Updated",
                            text: res.message || "Employee attendance updated successfully!",
                            timer: 2000,
                            showConfirmButton: false,
                        });
                        $("#edit_attendance").modal("hide");
                        setTimeout(() => location.reload(), 500);
                    } else if (res.status === "exists") {
                        Swal.fire({
                            icon: "warning",
                            title: "Duplicate Entry",
                            text: res.message || "An attendance record with the same entry already exists.",
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Update Failed",
                            text: res.message || "Something went wrong while updating attendance.",
                        });
                    }
                },
                error: function () {
                    $("#" + UpdateBtnID).prop("disabled", false).text("Update");
                    Swal.fire({
                        icon: "error",
                        title: "Server Error",
                        text: "Unable to connect to the server. Please try again later.",
                    });
                },
            });
        }
    });
}

function UpdateEmployeeMissingAttendance(AttendanceDateInputId, MarkInInputId, MarkOutInputId, addattremarkId, UpdateBtnID) {
    const baseurl = $("body").data("routurl");
    const companyid = $("body").data("companyid");
    const subcompanyid = $("body").data("subcompanyid");
    const projectid = $("body").data("projectid");
    const loginid = $("body").data("loginid");
    const EmployeeId = $("body").data("empid");

    let AttendanceDate = $("#" + AttendanceDateInputId).val() || "";
    let MarkInDatetime = $("#" + MarkInInputId).val() || "";
    let MarkOutDatetime = $("#" + MarkOutInputId).val() || "";
    let Addattremark = $("#" + addattremarkId).val() || "";

    // ✅ Prevent 'undefined' or 'null' from being sent
    if (AttendanceDate === "undefined" || AttendanceDate === "null") AttendanceDate = "";
    if (MarkInDatetime === "undefined" || MarkInDatetime === "null") MarkInDatetime = "";
    if (MarkOutDatetime === "undefined" || MarkOutDatetime === "null") MarkOutDatetime = "";

    // ✅ Basic Validation
    if (!companyid || !projectid || !EmployeeId) {
        Swal.fire("Missing Data", "Project, Company, or Employee ID is missing.", "warning");
        return;
    }

    if (!AttendanceDate) {
        Swal.fire("Input Required", "Please enter Attendance Date.", "warning");
        return;
    }

    if (!MarkInDatetime) {
        Swal.fire("Input Required", "Please enter Check-In time.", "warning");
        return;
    }

    if (!MarkOutDatetime) {
        Swal.fire("Input Required", "Please enter Check-Out time.", "warning");
        return;
    }

    if (!Addattremark || !Addattremark.trim()) {
        Swal.fire("Input Required", "Remarks / reason for missing attendance is required.", "warning");
        return;
    }

    if (MarkInDatetime && MarkOutDatetime) {
        const timeToMinutes = (t) => {
            const parts = t.split(":");
            return (parseInt(parts[0]) || 0) * 60 + (parseInt(parts[1]) || 0);
        };
        const inMins = timeToMinutes(MarkInDatetime);
        const outMins = timeToMinutes(MarkOutDatetime);
        if (outMins - inMins >= 720) {
            Swal.fire("Invalid Duration", "Working duration must be less than 12 hours.", "warning");
            return;
        }
    }

    const form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginid", loginid);
    form_data.append("loginroleid", $("body").data("loginroleid") || "");
    form_data.append("loginname", $("body").data("loginname") || "");
    form_data.append("EmployeeId", EmployeeId);
    form_data.append("AttendanceDate", AttendanceDate);
    form_data.append("MarkInDatetime", MarkInDatetime);
    form_data.append("MarkOutDatetime", MarkOutDatetime);
    form_data.append("Addattremark", Addattremark);

    const proofFile = $("#addattprooffile")[0].files[0];
    if (proofFile) {
        form_data.append("ProofFile", proofFile);
    }

    // ✅ Confirmation before update
    Swal.fire({
        title: "Confirm Attendance Marking",
        text: "Do you want to mark missing attendance for this employee?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, Mark Attendance",
        cancelButtonText: "Cancel",
    }).then((result) => {
        if (!result.isConfirmed) return;

        const $btn = $("#" + UpdateBtnID);
        $btn.prop("disabled", true).text("Updating...");

        $.ajax({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=UpdateEmployeeMissingAttendance",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: "json",
            success: function (res) {
                $btn.prop("disabled", false).text("Update");

                if (res.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Attendance Marked",
                        text: res.message || "Attendance marked successfully and pending for approval.",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    $("#add_attendance").modal("hide");
                    setTimeout(() => location.reload(), 1500);
                }
                else if (res.status === "exists") {
                    Swal.fire({
                        icon: "warning",
                        title: "Attendance Exists",
                        text: res.message,
                    });
                }
                else {
                    Swal.fire({
                        icon: "error",
                        title: "Update Failed",
                        text: res.message || "Something went wrong while marking attendance.",
                    });
                }
            },
            error: function (xhr) {
                $btn.prop("disabled", false).text("Update");
                Swal.fire({
                    icon: "error",
                    title: "Server Error",
                    text: "Unable to connect to the server. Please try again later. (" + xhr.statusText + ")",
                });
            },
        });
    });
}



function fetchEmployeeSalarywithYear(salaryyear, shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("salaryyear", salaryyear);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=fetchEmployeeSalarywithYear",
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

function fetchEmployeeBankDetails_callback(EmployeeId, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("EmployeeId", EmployeeId);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=fetchEmployeeBankDetails_callback",
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

function fetchHolidayYearsFromMaster_callback(callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=fetchHolidayYearsFromMaster",
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

function fetchHolidayFromMaster_callback(holidayyear, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("holidayyear", holidayyear);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=fetchHolidayFromMaster_callback",
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

// function addupdate_holidaymaster(holidayid, holidayname, holidaydate, holidaytype, actionfordo) {
//     var form_data = new FormData();
//     var routurl = $("body").data("routurl");
//     var companyid = $("body").data("companyid");
//     var subcompanyid = $("body").data("subcompanyid");
//     var projectid = $("body").data("projectid");
//     var loginname = $("body").data("loginname");

//     form_data.append("holidayid", holidayid);
//     form_data.append("holidayname", holidayname);
//     form_data.append("holidaydate", holidaydate);
//     form_data.append("holidaytype", holidaytype);

//     if (holidayname == "" || holidayname == null || holidayname == "undefined") {
//         alertmessage("error", "Holiday name is missing");
//         return false;
//     } else if (holidaydate == "" || holidaydate == null || holidaydate == "undefined") {
//         alertmessage("error", "Holiday date is missing");
//         return false;
//     } else if (holidaytype == "" || holidaytype == null || holidaytype == "undefined") {
//         alertmessage("error", "Holiday type is missing");
//         return false;
//     }

//     form_data.append("companyid", companyid);
//     form_data.append("subcompanyid", subcompanyid);
//     form_data.append("projectid", projectid);
//     form_data.append("loginname", loginname);
//     form_data.append("actionfordo", actionfordo);
//     let holidayyear = $("#holidayyear").val();
//     $.ajax
//         ({
//             type: "POST",
//             url: routurl + "modal/hrm_modal.php?action=addupdate_holidaymaster",
//             data: form_data,
//             contentType: false,
//             processData: false,
//             cache: false,
//             dataType: 'JSON',
//             success: function (res) {
//                 // console.log(res);
//                 if (res.status == "Success" || res.status == "success") {
//                     SweetAlertmessageFadeinwithoutrefresh("success", res.message);
//                     $("#holidayyear").val(holidayyear).trigger("change")
//                 } else {
//                     alertmessage("error", res.message);
//                     return false;
//                 }
//             }
//         });
// }
function addupdate_holidaymaster(holidayid, holidayname, holidaydate, holidaytype, actionfordo) {

    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");

    // 🔹 Validation
    if (!holidayname || holidayname.trim() === "") {
        alertmessage("error", "Holiday name is missing");
        return false;
    }

    if (!holidaydate) {
        alertmessage("error", "Holiday date is missing");
        return false;
    }

    if (!holidaytype) {
        alertmessage("error", "Holiday type is missing");
        return false;
    }

    // 🔹 Get year from selected date
    // expected format: yyyy-mm-dd
    let holidayyear = new Date(holidaydate).getFullYear();

    if (!holidayyear || isNaN(holidayyear)) {
        alertmessage("error", "Invalid holiday date format");
        return false;
    }

    // 🔹 Append data
    form_data.append("holidayid", holidayid);
    form_data.append("holidayname", holidayname.trim());
    form_data.append("holidaydate", holidaydate);
    form_data.append("holidaytype", holidaytype);
    form_data.append("holidayyear", holidayyear); // optional if needed in backend

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginname", loginname);
    form_data.append("actionfordo", actionfordo);

    $.ajax({
        type: "POST",
        url: routurl + "modal/hrm_modal.php?action=addupdate_holidaymaster",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {

            if (res.status === "Success" || res.status === "success") {

                SweetAlertmessageFadeinwithoutrefresh("success", res.message);

                // 🔹 Set dropdown year automatically
                $("#holidayyear")
                    .val(holidayyear)
                    .trigger("change");

            } else {
                alertmessage("error", res.message);
            }
        },
        error: function () {
            alertmessage("error", "Something went wrong!");
        }
    });
}


function holidayType_callback(callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=holidayType_callback",
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

function gotoCompletebasicdetails(employeeid) {
    var routurl = $("body").data("routurl");
    var key = $("body").data("key");
    location.href = routurl + "employeeWork.php?k=" + key + "&empid=" + employeeid;
}

function EditEmployeeProfilemodal(employeeid) {

    var form_data = new FormData();
    var baseurl = $("body").data("routurl");

    form_data.append("companyid", $("body").data("companyid"));
    form_data.append("subcompanyid", $("body").data("subcompanyid"));
    form_data.append("projectid", $("body").data("projectid"));
    form_data.append("employeeid", employeeid);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_modal.php?action=EditEmployeeProfildata",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function (res) {
            // console.log(res);
            if (!res || !res.length) {
                Swal.fire("Error", "Employee data not found", "error");
                return;
            }
            let emp = res[0];
            /* =====================
               BASIC DETAILS
            ===================== */
            $("#titlename_edit").val(emp.TitleName || "").trigger("change");
            $("#firsname_edit").val(emp.FirstName || "");
            $("#middlename_edit").val(emp.MiddleName || "");
            $("#lastname_edit").val(emp.LastName || "");
            $("#dateofbirth_edit").val(emp.DateOfBirth || "");
            $("#gender_edit").val(emp.Gender || "").trigger("change");
            $("#email_edit").val(emp.Email || "");
            $("#phoneno_edit").val(emp.MobileNo || "");
            $("#joiningdate_edit").val(emp.DateOfJoining || "");

            /* =====================
               EMPLOYEE INFO
            ===================== */
            $("#empid_edit").html(emp.EmployeeID);
            $("#updateEmpProfile").data("editempid", emp.EmployeeID);

            // $("#designation_elbl").val(emp.DesignationID || "");
            // $("#department_elbl").val(emp.DepartmentID || "");

            /* =====================
               WORK TIMING
            ===================== */
            // $("#intime_elbl").val(emp.InTime || "09:00:00");
            // $("#outtime_elbl").val(emp.OutTime || "18:00:00");

            /* =====================
               STATUS / PROFILE INFO
            ===================== */
            // $("#employeestatus_elbl").val(emp.Status || "Active");

            // if (emp.basiccompletestatus) {
            //     $("#basicProfileStatus").html(emp.basiccompletestatus);
            // }

            /* =====================
               SHOW MODAL
            ===================== */
            $("#edit_employee").modal("show");
        },

        error: function (xhr) {
            console.error(xhr.responseText);
            Swal.fire("Error", "Failed to load employee data", "error");
        }
    });
}

function UpdateEmployeeProfileDetails(btnid) {

    var baseurl = $("body").data("routurl");
    var $btn = $("#" + btnid);
    var originalBtnText = $btn.html();
    var employeeid = $btn.data("editempid");

    /* =====================
       RESET FIELD ERRORS
    ===================== */
    $(".is-invalid").removeClass("is-invalid");

    /* =====================
       VALIDATION
    ===================== */
    if (!employeeid) {
        Swal.fire({
            icon: "warning",
            title: "Employee Missing",
            text: "Employee ID not found. Please reload the page."
        });
        return;
    }

    if ($("#firsname_edit").val().trim() === "") {
        $("#firsname_edit").addClass("is-invalid"); // 🔴 highlight field

        Swal.fire({
            icon: "warning",
            title: "Required Field",
            text: "First Name is required."
        });

        $("#firsname_edit").focus();
        return;
    }

    /* =====================
       FORM DATA
    ===================== */
    var form_data = new FormData();
    form_data.append("companyid", $("body").data("companyid"));
    form_data.append("subcompanyid", $("body").data("subcompanyid"));
    form_data.append("projectid", $("body").data("projectid"));
    form_data.append("EmployeeId", employeeid);

    form_data.append("titlename", $("#titlename_edit").val());
    form_data.append("firsname", $("#firsname_edit").val());
    form_data.append("middlename", $("#middlename_edit").val());
    form_data.append("lastname", $("#lastname_edit").val());
    form_data.append("dateofbirth", $("#dateofbirth_edit").val());
    form_data.append("gender", $("#gender_edit").val());
    form_data.append("email", $("#email_edit").val());
    form_data.append("phoneno", $("#phoneno_edit").val());
    form_data.append("joiningdate", $("#joiningdate_edit").val());
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_modal.php?action=UpdateEmployeeProfileDetails",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        beforeSend: function () {
            // 🔒 Disable button + change text
            $btn.prop("disabled", true).html("Updating…");
            // 🔔 Loading Swal
            Swal.fire({
                title: "Updating Profile",
                text: "Please wait...",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });
        },
        success: function (res) {
            console.log(res);
            if (res.status === "success") {
                Swal.fire({
                    icon: "success",
                    title: "Updated",
                    text: res.message || "Employee profile updated successfully."
                }).then(() => {
                    // ✅ Auto-close modal
                    $("#edit_employee").modal("hide");
                    // Small delay for smooth UX
                    setTimeout(() => {
                        location.reload();
                    }, 300);
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Failed",
                    text: res.message || "Unable to update employee profile."
                });
                // 🔓 Restore button
                $btn.prop("disabled", false).html(originalBtnText);
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
            Swal.fire("Error", "Server error. Please try again.", "error");
            // 🔓 Restore button
            $btn.prop("disabled", false).html(originalBtnText);
        }
    });
}




function gotoCompleteSalaryDetails(employeeid) {
    var routurl = $("body").data("routurl");
    var key = $("body").data("key");
    location.href = routurl + "employeeSalary.php?k=" + key + "&empid=" + employeeid;
}

function fetchEmployeeData_callback(shortby, shortbyvalue, callback) {
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
            url: baseurl + "modal/hrm_modal.php?action=fetchEmployeeData_callback",
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

function fetchEmployeeDataForLocaton_callback(shortby, shortbyvalue, callback) {
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
            url: baseurl + "modal/hrm_modal.php?action=fetchEmployeeDataForLocaton",
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

function countrylist_callback(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=countrylist_callback",
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

function courrencylist_callback(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=courrencylist_callback",
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

function designationlist_callback(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=designationlist_callback",
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

function designationlist_dropdwon(alreadyexist, dropdownid) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    form_data.append("shortby", '');
    form_data.append("shortbyvalue", '');
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=designationlist_callback",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res);
                if (res.length > 0) {
                    var disglist = '';
                    if (res.length > 1) {
                        disglist += "<option value='' selected disabled>Choose Designation</option>";
                    }
                    for (var i = 0; i < res.length; i++) {
                        disglist += "<option value='" + res[i].DesignationID + "'>" + res[i].DesignationName + "</option>";
                    }
                    if (disglist) {
                        $("#" + dropdownid + "").append(disglist);
                    }
                    if (alreadyexist) {
                        $("#" + dropdownid + "").val(alreadyexist).trigger("change");
                    }
                }
            }
        });
}

function relationshiplist_callback(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=relationshiplist_callback",
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

function jobIndustrieslist_callback(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=jobIndustrieslist_callback",
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

function jobtitlelist_callback(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=jobtitlelist_callback",
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

function employmenttypelist_callback(shortby, shortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=employmenttypelist_callback",
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

function hrm_deductioncomponentGlobal(shortby = null, shortbyvalue = null, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    // alert(baseurl+'=='+companyid);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=hrm_deductioncomponentGlobal",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                console.log(res);
                callback(res);
            }
        });
}
function hrm_deductioncomponent(shortby = null, shortbyvalue = null, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    // alert(baseurl+'=='+companyid);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=hrm_deductioncomponent",
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

function hrm_EmployeeDeductionComponent_callback(employeeid, callback) {
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("employeeid", employeeid);
    // alert(companyid+"--"+employeeid);
    $.ajax
        ({
            type: "POST",
            url: routurl + "modal/hrm_modal.php?action=hrm_EmployeeDeductionComponent",
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

function hrm_earningcomponentGlobal(shortby = null, shortbyvalue = null, callback) {
    var form_data = new FormData();
    var routurl = $("body").data("routurl");
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    // alert(baseurl+'=='+companyid);
    $.ajax
        ({
            type: "POST",
            url: routurl + "modal/hrm_modal.php?action=hrm_earningcomponentGlobal",
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
function hrm_earningcomponent(shortby = null, shortbyvalue = null, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=hrm_earningcomponent",
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

function hrm_earningcomponentnew(shortby = null, shortbyvalue = null, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    form_data.append("shortby", shortby);
    form_data.append("shortbyvalue", shortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=hrm_earningcomponentnew",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            // dataType:'JSON',
            success: function (res) {
                // console.log(res);
                callback(res);
            }
        });
}

function hrm_EarningComponentWithSalaryDetails(employeeid, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    form_data.append("companyid", $("body").data("companyid"));
    form_data.append("subcompanyid", $("body").data("subcompanyid"));
    form_data.append("projectid", $("body").data("projectid"));
    form_data.append("employeeid", employeeid);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=hrm_EmployeeEarningWithSalaryDetails",
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

function savetodb_EmpDeductions(containerDivId, btnid) {
    var baseurl = $("body").data("routurl");
    var form_data = new FormData();
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var employeeid = $("#" + btnid + "").data("employeeid");
    if (employeeid == "" || employeeid == null || employeeid == "undefined") {
        alertmessage('warning', '"Employee must be selected to proceed with adding or updating the deduction.');
        return false;
    }
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("employeeid", employeeid);
    let deductionid = [];
    $("input[name='deductioncomponent']").each(function () {
        let status = $(this).is(":checked") ? "Active" : "Deactive";
        deductionid.push({
            id: $(this).val(),
            status: status
        });
    });

    // send JSON string
    form_data.append("deductionid", JSON.stringify(deductionid));
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=AddUpdate_EmployeeDeduction",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res);
                if (res.status == "Success" || res.status == "success") {
                    alertmessage('success', res.message);
                    $("#nextButton").trigger("click");
                    // window.location.reload();
                } else {
                    alertmessage('error', res.message);
                }
            }
        });
}

function savetodb_EmpEarningComponents(containerDivId, btnid) {
    var baseurl = $("body").data("routurl");
    var form_data = new FormData();
    var companyid = $("body").data("companyid") || ''; // Use || for null/undefined check
    var subcompanyid = $("body").data("subcompanyid") || '';
    var projectid = $("body").data("projectid") || '';
    var currencyCode = $("#currencyofempsalary").val() || '';
    var ctcsalary = parseFloat($("#ctcsalary").val()) || 0; // Parse as float, use || for NaN check
    if (isNaN(ctcsalary) || ctcsalary <= 0) { // Check for NaN as well
        alertmessage('error', 'Employee CTC Amount is invalid');
        return false;
    } else {
        var employeeid = $("#" + btnid).data("employeeid"); // Make sure btnid is defined
        if (!employeeid) {  // Check if employeeid exists
            alertmessage('error', 'Employee ID is missing.');
            return false;
        }
        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid);
        form_data.append("currencyCode", currencyCode);
        form_data.append("ctcsalary", ctcsalary);
        form_data.append("employeeid", employeeid);
        var componentData = [];
        $("#" + containerDivId + " input.componentname").each(function () {
            var earningComponentId = $(this).data("earningcomponentid");
            var percentInputId = $(this).data("percentinputid");
            var componentAmount = parseFloat($(this).val()) || 0; // Parse as float, default to 0
            if (isNaN(componentAmount)) { // Check if componentAmount is a valid number.
                alertmessage('error', 'Component amount is not a valid number.');
                return false;
            }
            var percentValue = 0;
            if (percentInputId) {
                percentValue = parseFloat($("#" + percentInputId).val()) || 0; // Parse percent as float
                if (isNaN(percentValue)) {
                    alertmessage('error', 'Percentage is not a valid number.');
                    return false;
                }
            }
            componentData.push({
                componentId: earningComponentId,
                componentAmount: componentAmount,
                percentValue: percentValue
            });
        });
        form_data.append("componentData", JSON.stringify(componentData));
    }

    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=AddUpdate_EmployeeEarnings",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res);
                if (res.status == "Success" || res.status == "success") {
                    alertmessage('success', res.message);
                    $("#nextButton").trigger("click");
                    window.location.reload();
                } else {
                    alertmessage('error', res.message);
                }
            }
        });
}
function savetodb_EmpEarningComponentsnew(containerDivId, btnid) {
    var routurl = $("body").data("routurl");
    var key = $("body").data("key");
    var form_data = new FormData();
    var companyid = $("body").data("companyid") || ''; // Use || for null/undefined check
    var subcompanyid = $("body").data("subcompanyid") || '';
    var projectid = $("body").data("projectid") || '';
    var currencyCode = $("#currencyofempsalary").val() || '';
    var ctcsalary = parseFloat($("#ctcsalary").val()) || 0; // Parse as float, use || for NaN check
    if (isNaN(ctcsalary) || ctcsalary <= 0) { // Check for NaN as well
        alertmessage('error', 'Employee CTC Amount is invalid');
        return false;
    } else {
        var employeeid = $("#" + btnid).data("employeeid"); // Make sure btnid is defined
        if (!employeeid) {  // Check if employeeid exists
            alertmessage('error', 'Employee ID is missing.');
            return false;
        }
        form_data.append("companyid", companyid);
        form_data.append("subcompanyid", subcompanyid);
        form_data.append("projectid", projectid);
        form_data.append("currencyCode", currencyCode);
        form_data.append("ctcsalary", ctcsalary);
        form_data.append("employeeid", employeeid);
        var componentData = [];
        $("#" + containerDivId + " input.yearlycontribution").each(function () {
            var $tr = $(this).closest("tr");
            var earningComponentId = $tr.data("trcomponentid");
            var percentInputId = $(this).data("percentinputid");
            var yearlyAmount = parseFloat($(this).val()) || 0;
            var monthlyInput = $tr.find("input.monthlycontribution");
            var monthlyAmount = monthlyInput.length ? (parseFloat(monthlyInput.val()) || 0) : (yearlyAmount / 12);
            if (isNaN(yearlyAmount)) {
                alertmessage('error', 'Component amount is not a valid number.');
                return false;
            }
            var percentValue = 0;
            if (percentInputId) {
                percentValue = parseFloat($("#" + percentInputId).val()) || 0;
                if (isNaN(percentValue)) {
                    alertmessage('error', 'Percentage is not a valid number.');
                    return false;
                }
            }
            componentData.push({
                componentId: earningComponentId,
                componentAmount: yearlyAmount,
                monthlyAmount: monthlyAmount,
                percentValue: percentValue
            });
        });
        // console.log(componentData);
        form_data.append("componentData", JSON.stringify(componentData));
    }

    $.ajax
        ({
            type: "POST",
            url: routurl + "modal/hrm_modal.php?action=AddUpdate_EmployeeEarnings",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                if (res.status == "Success" || res.status == "success") {
                    alertmessage('success', res.message);
                    setTimeout(function () {
                        if ($("#nextButton").length > 0 && !$("#nextButton").hasClass("d-none")) {
                            $("#nextButton").trigger("click");
                        } else {
                            window.location.reload();
                        }
                    }, 800);
                } else {
                    alertmessage('error', res.message);
                }
            }
        });
}

// create new maste list
function opennewDepartment() {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    $("#webpagebodydivmd").load(baseurl + "addnew_department.php?vw=1", function () {
        $("#webpagebodyview_mdmodal").css("z-index", "10058")
        $("#webpagebodyview_mdmodal").modal("show");
        $("#modalheadermd").html("NEW DEPARTMENT NAME");
    });
}

function opennewDesignation() {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    $("#webpagebodydivmd").load(baseurl + "addnew_designation.php?vw=1", function () {
        $("#webpagebodyview_mdmodal").css("z-index", "10058")
        $("#webpagebodyview_mdmodal").modal("show");
        $("#modalheadermd").html("NEW DESIGNATION NAME");
    });
}

$(document).on("click", ".opennewDepartment", function () {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    $("#webpagebodydivmd").load(baseurl + "addnew_department.php?vw=1", function () {
        $("#webpagebodyview_mdmodal").css("z-index", "10058")
        $("#webpagebodyview_mdmodal").modal("show");
        $("#modalheadermd").html("NEW DEPARTMENT NAME");
    });
});

// Earning component list view in modal
$(document).on("click", ".addEarningtoEmployee", function () {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    $("#webpagebodydivmd").load(baseurl + "addEarningListtoEmphtml.php?vw=1", function () {
        $("#webpagebodyview_mdmodal").css("z-index", "10058")
        $("#webpagebodyview_mdmodal").modal("show");
        $("#modalheadermd").html("ADD NEW EARNING COMPONENT TO EMPLOYEE");
    });
});
// add new Earning component in master
$(document).on("click", ".addnewErCompttoMaster", function () {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    $("#webpagebodydiv1").load(baseurl + "addEarningtoMasterlisthtml.php?vw=1", function () {
        $("#webpagebodyview1_modal").css("z-index", "11060")
        $("#webpagebodyview1_modal").modal("show");
        $("#modalheader1").html("Create a Custom Earning");
    });
});

// Deduction component list view in modal
// $(document).on("click", ".addDeductiontoEmployee", function(){
//     var form_data = new FormData();
//     var baseurl = $("body").data("routurl");
//     $("#webpagebodydivmd").load(baseurl+"addDeductionListtoEmphtml.php?vw=1", function(){
//         $("#webpagebodyview_mdmodal").css("z-index","10058")
//         $("#webpagebodyview_mdmodal").modal("show");
//         $("#modalheadermd").html("ADD NEW DEDUCTION COMPONENT TO EMPLOYEE");
//     });
// });

$(document).on("click", ".addDeductiontoEmployee", function () {
    var form_data = new FormData(); // Not used here — you can remove if unused
    var baseurl = $("body").data("routurl");
    // Load content into modal body
    $("#webpagebodydivmd").load(baseurl + "addDeductionListtoEmphtml.php?vw=1", function () {
        // Set z-index if needed, but Bootstrap handles this internally
        $("#webpagebodyview_mdmodal").css("z-index", "1058"); // Optional, Bootstrap default is 1055+
        // Set header text
        $("#modalheadermd").html("ADD NEW DEDUCTION COMPONENT TO EMPLOYEE");
        // Show the modal (Bootstrap 5 syntax)
        const mdModal = new bootstrap.Modal(document.getElementById('webpagebodyview_mdmodal'), {
            backdrop: 'static',
            keyboard: false
        });
        mdModal.show();
    });
});

// add new deduction component in master
$(document).on("click", ".addnewDrCompttoMaster", function () {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    $("#webpagebodydiv1").load(baseurl + "addDeductiontoMasterlisthtml.php?vw=1", function () {
        $("#webpagebodyview1_modal").css("z-index", "11060")
        $("#webpagebodyview1_modal").modal("show");
        $("#modalheader1").html("Create a Custom Earning");
    });
});

// add new leave type to master list
$(document).on("click", ".openaddnewleavemodal", function () {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    $("#webpagebodydiv1").load(baseurl + "addLeavetoMasterlisthtml.php?vw=1", function () {
        $("#webpagebodyview1_modal").modal("show");
        $("#modalheader1").html("New Leave Details");
    });
});

function LeaveTypeMaster_callback(sortby, sortbyvalue, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    form_data.append("sortby", sortby);
    form_data.append("sortbyvalue", sortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=LeaveTypeMaster_callback",
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

function saveUpdateLeaveType(data, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=saveUpdateLeaveType",
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


function EmployeeLeaveCountDetails(EmployeeId, LeaveYear) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);
    form_data.append("EmployeeId", EmployeeId);
    form_data.append("LeaveYear", LeaveYear);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=EmployeeLeaveCountDetails",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res);
                $("#empLeaveDatatbl tbody").html('');
                let lysmdata = res.leavesummarydata;
                let balanceEL = "";
                let balanceCL = "";
                let balanceSL = "";
                let balanceOL = "";
                for (var i = 0; i < lysmdata.length; i++) {
                    if (lysmdata[i].leavetype == "EL") {
                        $("#totalEarnleavelbl").text(lysmdata[i].leaveallowed);
                        $("#remainEarnleavslbl").text(lysmdata[i].balanceleave);
                        balanceEL = `<span id="balanceEL" class="badge badge-defauld">Balance Earn Leave : ${lysmdata[i].balanceleave}</span>`;
                    } else if (lysmdata[i].leavetype == "SL") {
                        $("#totalSickleavelbl").text(lysmdata[i].leaveallowed);
                        $("#remainSickleavelbl").text(lysmdata[i].balanceleave);
                        balanceSL = `<span id="balanceSL" class="badge badge-defauld">Balance Sick Leave : ${lysmdata[i].balanceleave}</span>`;
                    } else if (lysmdata[i].leavetype == "CL") {
                        $("#totalCasualleavelbl").text(lysmdata[i].leaveallowed);
                        $("#remainCasualleavelbl").text(lysmdata[i].balanceleave);
                        balanceCL = `<span id="balanceCL" class="badge badge-defauld">Balance Casul Leave : ${lysmdata[i].balanceleave}</span>`;
                    } else if (lysmdata[i].leavetype == "LOP") {
                        $("#totalUnpaidleavelbl").text(lysmdata[i].leaveused);
                        $("#remainUnpaidleavelbl").text(lysmdata[i].leaveused);
                    } else {
                        balanceOL = `<span id="balanceCL" class="badge badge-defauld">Balance Other Leave : ${lysmdata[i].balanceleave}</span>`;
                    }
                }
                let emplydata = res.fullleavedetails || [];
                window.currentLeaveData = emplydata;
                window.currentLeaveSummaryData = lysmdata || [];

                // Refresh modal balance display if open
                if ($("#leaveapplyleaveType").val()) {
                    $("#leaveapplyleaveType").trigger("change");
                }

                // Calculate counts
                let appliedCount = 0;
                let approvedCount = 0;
                let pendingCount = 0;

                for (let i = 0; i < emplydata.length; i++) {
                    let status = emplydata[i].Status || '';
                    if (status.toLowerCase() !== 'removed' && status.toLowerCase() !== 'cancelled') {
                        appliedCount++;
                        if (status.toLowerCase() === 'approved') {
                            approvedCount++;
                        } else if (status.toLowerCase() === 'pending' || status === '') {
                            pendingCount++;
                        }
                    }
                }

                // Fill card text
                $("#totalAppliedLeavelbl").text(appliedCount);
                $("#totalApprovedLeavelbl").text(approvedCount);
                $("#totalPendingLeavelbl").text(pendingCount);

                // Render strips (default all)
                renderLeaveStrips(emplydata, "all");
            }
        });
}

window.renderLeaveStrips = function (emplydata, statusFilter) {
    let emplyStrips = "";
    for (var i = 0; i < emplydata.length; i++) {
        let status = emplydata[i].Status || 'Pending';
        let statusLower = status.toLowerCase();

        // Apply filter
        if (statusFilter !== "all") {
            if (statusFilter === "Approved" && statusLower !== "approved") continue;
            if (statusFilter === "Pending" && statusLower !== "pending" && status !== "") continue;
        }

        let leaveStatus = "";
        let statusClass = "status-pending";
        let statusIcon = "ti-clock";

        switch (status) {
            case "Approved":
                leaveStatus = "Approved";
                statusClass = "status-approved";
                statusIcon = "ti-circle-check";
                break;
            case "Rejected":
                leaveStatus = "Rejected";
                statusClass = "status-rejected";
                statusIcon = "ti-circle-x";
                break;
            default:
                leaveStatus = "Pending for Approval";
                statusClass = "status-pending";
                statusIcon = "ti-clock";
        }

        let typeIconClass = "icon-ol";
        let lType = emplydata[i].LeaveType.toUpperCase();
        if (lType == "EL") typeIconClass = "icon-el";
        else if (lType == "SL") typeIconClass = "icon-sl";
        else if (lType == "CL") typeIconClass = "icon-cl";

        let applyDate = emplydata[i].created_at ? changeDateFormatYmd_dMY(emplydata[i].created_at.split(' ')[0]) : '-';
        let leaveStart = emplydata[i].LeaveStartDate ? changeDateFormatYmd_dMY(emplydata[i].LeaveStartDate.split(' ')[0]) : '';
        let leaveEnd = emplydata[i].LeaveEndDate ? changeDateFormatYmd_dMY(emplydata[i].LeaveEndDate.split(' ')[0]) : '';
        let leaveDateStr = '-';
        if (leaveStart && leaveEnd) {
            leaveDateStr = (leaveStart === leaveEnd) ? leaveStart : `${leaveStart} to ${leaveEnd}`;
        } else if (leaveStart) {
            leaveDateStr = leaveStart;
        }

        let approvedByVal = (emplydata[i].approved_by && emplydata[i].approved_by !== "") ? emplydata[i].approved_by : "";
        let approvedDateVal = (emplydata[i].approved_at && emplydata[i].approved_at !== '0000-00-00' && emplydata[i].approved_at !== '0000-00-00 00:00:00') ? changeDateFormatYmd_dMY(emplydata[i].approved_at.split(' ')[0]) : "";

        let approvedByAndDate = '<span class="text-muted italic small">Awaiting...</span>';
        if (approvedByVal || approvedDateVal) {
            let byStr = approvedByVal ? approvedByVal : '-';
            let dateStr = approvedDateVal ? `<div class="small text-muted fw-normal">${approvedDateVal}</div>` : '';
            approvedByAndDate = `<div class="fw-bold">${byStr}</div>${dateStr}`;
        }

        emplyStrips += `
        <div class="info-strip shadow-sm" data-lytype='${emplydata[i].LeaveType}'>
            <div class="row align-items-center g-3">
                <!-- Type Icon -->
                <div class="col-auto">
                    <div class="info-strip-icon ${typeIconClass}">${lType}</div>
                </div>
                
                <!-- Main Detail -->
                <div class="col">
                    <div class="row g-2">
                        <div class="col-md-3 col-12">
                            <div class="info-strip-label">Leave Name</div>
                            <div class="info-strip-value">${emplydata[i].leaveName}</div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="info-strip-label">Leave Date</div>
                            <div class="info-strip-value"><i class="ti ti-calendar me-1 text-danger"></i>${leaveDateStr}</div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="info-strip-label">Apply Date</div>
                            <div class="info-strip-value"><i class="ti ti-calendar-event me-1 text-primary"></i>${applyDate}</div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="info-strip-label">Approved By / Date</div>
                            <div class="info-strip-value">${approvedByAndDate}</div>
                        </div>
                    </div>
                </div>
                
                <!-- Status Box -->
                <div class="col-auto text-end border-start ps-4">
                    <div class="strip-net-box py-1 shadow-none border-0 bg-transparent">
                        <div class="info-strip-label text-center">Current Status</div>
                        <span class="status-badge ${statusClass} mt-1 w-100 justify-content-center">
                            <i class="ti ${statusIcon}"></i> ${leaveStatus}
                        </span>
                    </div>
                </div>
                
                <!-- Action (Optional Details) -->
                <div class="col-auto d-flex gap-2">
                    ${(() => {
                        let rawRole = $("body").data("loginroleid");
                        let loginroleid = (rawRole !== undefined && rawRole !== "") ? parseInt(rawRole) : 4;
                        let html = '';
                        if (loginroleid < 3) {
                            // Admin (role < 3): show ONLY delete button (inplace of view details button)
                            html += `
                                <button class="strip-action-btn shadow-sm text-danger border-danger-subtle delete-leave-btn" data-leaveid="${emplydata[i].LeaveID}" data-status="${emplydata[i].Status || 'Pending'}" title="Delete Leave">
                                    <i class="ti ti-trash fs-20"></i>
                                </button>
                            `;
                        } else {
                            // Standard user (role >= 3):
                            // "user can view only before approval" => show eye icon (view details) ONLY if pending
                            if (statusLower === 'pending' || status === '') {
                                html += `
                                    <button class="strip-action-btn shadow-sm text-danger border-danger-subtle delete-leave-btn" data-leaveid="${emplydata[i].LeaveID}" data-status="${emplydata[i].Status || 'Pending'}" title="Delete Leave">
                                        <i class="ti ti-trash fs-20"></i>
                                    </button>
                                `;
                            }
                        }
                        return html;
                    })()}
                </div>
            </div>
        </div>`;
    }

    if (emplyStrips) {
        $("#empLeaveStripsWrapper").html(emplyStrips);
    } else {
        $("#empLeaveStripsWrapper").html('<div class="alert alert-light text-center border-dashed py-4"><i class="ti ti-info-circle fs-30 text-muted d-block mb-2"></i>No leave history found for this category.</div>');
    }
};

window.filterLeaveHistory = function (statusFilter) {
    // Manage active card styling
    $(".leave-card").removeClass("active-filter");
    if (statusFilter === "all") {
        $(".bg-applied-leaves").addClass("active-filter");
    } else if (statusFilter === "Approved") {
        $(".bg-approved-leaves").addClass("active-filter");
    } else if (statusFilter === "Pending") {
        $(".bg-pending-leaves").addClass("active-filter");
    }
    
    // Render
    if (window.currentLeaveData) {
        window.renderLeaveStrips(window.currentLeaveData, statusFilter);
    }
};

function GetEmployeeLeavesData_Callback(datefrom, dateto, sortby, sortbyvalue, callback) {
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
    form_data.append("sortby", sortby);
    form_data.append("sortbyvalue", sortbyvalue);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=GetEmployeeLeavesData",
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

function UpdateAppliedLeaveStatus(actionType, tableID, updbtnid) {
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    var loginname = $("body").data("loginname");

    var ArraySLDataList = [];

    // ✅ Collect selected rows (Visible only)
    $("#" + tableID + " tbody tr").each(function () {
        if ($(this).is(":visible") && !$(this).hasClass("d-none") && $(this).find("input[type='checkbox']").is(":checked")) {
            ArraySLDataList.push({
                "EmployeeID": $(this).data("employeeid"),
                "LeaveID": $(this).data("leaveid"),
                "loginid": loginid,
                "loginname": loginname
            });
        }
    });

    // ✅ Validate selection
    if (ArraySLDataList.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Selection!',
            text: 'Please select at least one leave to update.'
        });
        return;
    }

    // ✅ Handle different SweetAlert flows
    if (actionType === "Rejected") {
        // ---- REJECT FLOW ----
        Swal.fire({
            title: 'Enter Rejection Remark',
            input: 'textarea',
            inputPlaceholder: 'Type reason for rejection...',
            showCancelButton: true,
            confirmButtonText: 'Reject Leave',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'Please enter a rejection remark.';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                sendLeaveUpdateRequest(actionType, tableID, updbtnid, result.value, ArraySLDataList);
            }
        });

    } else if (actionType === "Approved") {
        // ---- APPROVE FLOW ----
        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to approve the selected leaves?',
            input: 'text',
            inputPlaceholder: 'Optional remark (if any)...',
            showCancelButton: true,
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const remark = result.value || '';
                sendLeaveUpdateRequest(actionType, tableID, updbtnid, remark, ArraySLDataList);
            }
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Action!',
            text: 'Action type not recognized.'
        });
    }
}



// ✅ Reusable AJAX handler
function sendLeaveUpdateRequest(actionType, tableID, updbtnid, approval_remark, ArraySLDataList) {
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    var loginname = $("body").data("loginname");

    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("actionType", actionType);
    form_data.append("loginid", loginid);
    form_data.append("loginname", loginname);
    form_data.append("approval_remark", approval_remark);
    form_data.append("empLeaveData", JSON.stringify(ArraySLDataList));

    $("#" + updbtnid).prop("disabled", true).text("Processing...");

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_modal.php?action=UpdateAppliedLeaveStatus",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'json',
        success: function (res) {
            // console.log(res);
            if (res.status === "success") {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: res.message || 'Leave status updated successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed!',
                    text: res.message || 'Unable to update leave status.'
                });
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'AJAX Error!',
                text: error
            });
        },
        complete: function () {
            $("#" + updbtnid).prop("disabled", false).text("Update Status");
        }
    });
}


function UpdateAppliedAttendanceApprovalReject(actionType, tableID, updbtnid) {
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    var loginname = $("body").data("loginname");

    var ArraySLDataList = [];

    // ✅ Collect selected rows (Visible only)
    $("#" + tableID + " tbody tr").each(function () {
        if ($(this).is(":visible") && !$(this).hasClass("d-none") && $(this).find("input[type='checkbox']").is(":checked")) {
            ArraySLDataList.push({
                "EmployeeID": $(this).data("employeeid"),
                "AttendanceID": $(this).data("attendanceid")
            });
        }
    });

    // ✅ Validate selection
    if (ArraySLDataList.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Selection!',
            text: 'Please select at least one leave to update.'
        });
        return;
    }

    // ✅ Handle different SweetAlert flows
    if (actionType === "Rejected") {
        // ---- REJECT FLOW ----
        Swal.fire({
            title: 'Enter Rejection Remark',
            input: 'textarea',
            inputPlaceholder: 'Type reason for rejection...',
            showCancelButton: true,
            confirmButtonText: 'Reject Attendance',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'Please enter a rejection remark.';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                sendAttendanceUpdateRequest(actionType, tableID, updbtnid, result.value, ArraySLDataList);
            }
        });

    } else if (actionType === "Approved" || actionType === "Present") {
        // ---- APPROVE FLOW ----
        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to approve the selected attendance?',
            input: 'text',
            inputPlaceholder: 'Optional remark (if any)...',
            showCancelButton: true,
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const remark = result.value || '';
                sendAttendanceUpdateRequest(actionType, tableID, updbtnid, remark, ArraySLDataList);
            }
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Action!',
            text: 'Action type not recognized.'
        });
    }
}

function UpdateAppliedAttendanceApproval(actionType, tableID, updbtnid) {
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    var loginname = $("body").data("loginname");

    var ArraySLDataList = [];

    // ✅ Collect selected rows (Visible only)
    $("#" + tableID + " tbody tr").each(function () {
        if ($(this).is(":visible") && !$(this).hasClass("d-none") && $(this).find("input[type='checkbox']").is(":checked")) {
            ArraySLDataList.push({
                "EmployeeID": $(this).data("employeeid"),
                "AttendanceID": $(this).data("attendanceid")
            });
        }
    });

    // ✅ Validate selection
    if (ArraySLDataList.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Selection!',
            text: 'Please select at least one leave to update.'
        });
        return;
    }

    // ✅ Handle different SweetAlert flows
    if (actionType === "Rejected") {
        // ---- REJECT FLOW ----
        Swal.fire({
            title: 'Enter Rejection Remark',
            input: 'textarea',
            inputPlaceholder: 'Type reason for rejection...',
            showCancelButton: true,
            confirmButtonText: 'Reject Attendance',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'Please enter a rejection remark.';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                sendAttendanceUpdateRequest(actionType, tableID, updbtnid, result.value, ArraySLDataList);
            }
        });

    } else if (actionType === "Approved" || actionType === "Present") {
        // ---- APPROVE FLOW ----
        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to approve the selected attendance?',
            input: 'text',
            inputPlaceholder: 'Optional remark (if any)...',
            showCancelButton: true,
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const remark = result.value || '';
                sendAttendanceUpdateRequest(actionType, tableID, updbtnid, remark, ArraySLDataList);
            }
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Action!',
            text: 'Action type not recognized.'
        });
    }
}

// ✅ Reusable AJAX handler for update attendance approval
function sendAttendanceUpdateRequest(actionType, tableID, updbtnid, approval_remark, ArraySLDataList) {
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginid = $("body").data("loginid");
    var loginname = $("body").data("loginname");

    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("actionType", actionType);
    form_data.append("loginid", loginid);
    form_data.append("loginname", loginname);
    form_data.append("approval_remark", approval_remark);
    form_data.append("empAttendanceData", JSON.stringify(ArraySLDataList));
    let btntext = $("#" + updbtnid).text();
    $("#" + updbtnid).prop("disabled", true).text("Processing...");

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_modal.php?action=UpdateAppliedAttendanceApproval",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'json',
        success: function (res) {
            // console.log(res);
            if (res.status === "success") {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: res.message || 'Leave status updated successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed!',
                    text: res.message || 'Unable to update leave status.'
                });
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'AJAX Error!',
                text: error
            });
        },
        complete: function () {
            $("#" + updbtnid).prop("disabled", false).text(btntext);
        }
    });
}

function linkDateFields(fromId, toId) {
    const $from = $('#' + fromId);
    const $to = $('#' + toId);

    $from.on('change', function () {
        const fromDate = $(this).val();
        if (fromDate) {
            $to.attr('min', fromDate);
            $to.val(fromDate).trigger('change');
        }
    });
}

function SaveOrUpdateAppliedLeave(EmpId_eid, LeaveType_eid, dateFrom_eid, dateTo_eid, remark_eid, AttFile_eid, savetodbbtnid) {
    const routurl = $("body").data("routurl") || '';
    const baseurl = routurl ? routurl + "/" : "";
    const form_data = new FormData();

    const companyid = $("body").data("companyid") || '';
    const subcompanyid = $("body").data("subcompanyid") || '';
    const projectid = $("body").data("projectid") || '';
    const loginname = $("body").data("loginname") || '';
    const loginid = $("body").data("loginid") || '';

    // Collect form data
    const empId = $("#" + EmpId_eid).val();
    const leaveType = $("#" + LeaveType_eid).val();
    const dateFrom = $("#" + dateFrom_eid).val();
    const dateTo = $("#" + dateTo_eid).val();
    const remarks = $("#" + remark_eid).val();

    if (!empId || !leaveType || !dateFrom || !dateTo) {
        Swal.fire({
            icon: "warning",
            title: "Missing Information",
            text: "Please fill all required fields before submitting."
        });
        return;
    }

    if (!remarks || !remarks.trim()) {
        Swal.fire({
            icon: "warning",
            title: "Missing Information",
            text: "Reason for Leave is mandatory."
        });
        return;
    }

    if (new Date(dateFrom) > new Date(dateTo)) {
        Swal.fire({
            icon: "warning",
            title: "Invalid Date Range",
            text: "From Date must be earlier than or equal to To Date."
        });
        return;
    }

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("loginid", loginid);
    form_data.append("EmployeeID", empId);
    form_data.append("LeaveType", leaveType);
    form_data.append("DateFrom", dateFrom);
    form_data.append("DateTo", dateTo);
    form_data.append("Remarks", remarks);

    // File upload
    const fileInput = document.getElementById(AttFile_eid);
    if (fileInput && fileInput.files.length > 0) {
        form_data.append("Attachment", fileInput.files[0]);
    }

    const modalId = $("#" + savetodbbtnid).closest(".modal").attr("id");

    // 🔹 Confirmation before submission
    Swal.fire({
        title: "Submit Leave Request?",
        text: "Are you sure you want to apply for leave with the selected dates?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, Submit",
        cancelButtonText: "Cancel",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {

            $.ajax({
                url: baseurl + "modal/hrm_modal.php?action=SaveOrUpdateAppliedLeave",
                type: "POST",
                data: form_data,
                contentType: false,
                processData: false,
                cache: false,
                dataType: "json",

                beforeSend: function () {
                    Swal.fire({
                        title: "Please wait...",
                        text: "Submitting your leave request...",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $("#" + savetodbbtnid)
                        .prop("disabled", true)
                        .html('<i class="fa fa-spinner fa-spin"></i> Saving...');
                },

                success: function (res) {
                    Swal.close();
                    $("#" + savetodbbtnid).prop("disabled", false).html("Save");

                    if (res.status === "Success") {
                        Swal.fire({
                            icon: "success",
                            title: "Leave Submitted!",
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // ✅ Reset form fields after success
                        $("#" + EmpId_eid).prop("selectedIndex", 0).trigger("change");
                        $("#" + LeaveType_eid).prop("selectedIndex", 0).trigger("change");
                        $("#" + dateFrom_eid).val("");
                        $("#" + dateTo_eid).val("");
                        $("#" + remark_eid).val("");
                        $("#" + AttFile_eid).val(""); // file input reset

                        // Optionally close modal
                        let modalId = $("#" + savetodbbtnid).closest(".modal").attr("id");
                        $("#" + modalId).modal("hide");
                        window.location.reload();

                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: res.message
                        });
                    }
                },

                error: function (xhr, status, error) {
                    Swal.close();
                    $("#" + savetodbbtnid).prop("disabled", false).html("Save");

                    Swal.fire({
                        icon: "error",
                        title: "An error occurred!",
                        text: error
                    });
                }
            });
        }
    });
}

function AddOrupdateContaineDivData(containerdivid, btnid, actiontext) {
    var baseurl = $("body").data("routurl");
    var formData = new FormData(); // FormData for handling file uploads
    var companyid = $("body").data("companyid") || ''; // Use || for null/undefined check
    var subcompanyid = $("body").data("subcompanyid") || '';
    var projectid = $("body").data("projectid") || '';
    formData.append("companyid", companyid);
    formData.append("subcompanyid", subcompanyid);
    formData.append("projectid", projectid);
    var isValid = true; // Track form validity
    $("#" + containerdivid).find("input, select, textarea").each(function () {
        var fieldName = $(this).attr("name");
        var fieldValue = $(this).val();
        var isRequired = $(this).prop("required");

        // Reset border color before validation
        $(this).css("border", "");

        // Check if field is required and empty
        if (isRequired && (!fieldValue || fieldValue.trim() === "")) {
            $(this).css("border", "2px solid red");
            isValid = false; // Mark form as invalid
        }

        if ($(this).attr("type") === "file") {
            // Handle file input
            if ($(this)[0].files.length > 0) {
                formData.append(fieldName.trim(), $(this)[0].files[0]);
            }
        } else if ($(this).attr("type") === "checkbox") {
            // Handle checkbox (send 1 if checked, 0 if not)
            formData.append(fieldName.trim(), $(this).prop("checked") ? "1" : "0");
        } else if ($(this).attr("type") === "radio") {
            // Handle radio button (only the selected one)
            if ($(this).prop("checked")) {
                formData.append(fieldName.trim(), fieldValue);
            }
        } else {
            // Handle all other input types
            formData.append(fieldName.trim(), fieldValue);
        }
    });

    // Stop form submission if required fields are missing
    if (!isValid) {
        alertmessage('error', 'Please fill in all required fields.');
        return false;
    }
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_modal.php?action=" + actiontext,
            data: formData,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res);
                if (res.status == "Success" || res.status == "success") {
                    // alertmessage('success', res.message);
                    // window.location.reload();
                    Swal.fire({
                        text: res.message,
                        title: "Are you continue this form?",
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: "YES",
                        denyButtonText: `NO`
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Swal.fire("Saved!", "", "success");
                            $("#" + containerdivid).find("input[type!='radio'][type!='checkbox'], textarea").val("");
                            $("#" + containerdivid).find("select").each(function () { // Iterate through selects
                                $(this).prop("selectedIndex", 0); // Or $(this).val(""); for clearing values, including when a default is set
                            });
                            $("#" + containerdivid).find("input[type='radio'], input[type='checkbox']").prop("checked", false);
                        } else if (result.isDenied) {
                            // Swal.fire("Changes are not saved", "", "info");
                            window.location.reload();
                        }
                    });
                } else {
                    alertmessage('error', res.message);
                }
            }
        });
}

function SaveOrUpdateEarningComponenttoPayroll(componentid_eid, description_eid, earpaytype_ename, caltype_ename, calval_eid, savetodbbtnid) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var componentid = $('#' + componentid_eid + '').val();
    form_data.append("componentid", componentid);

    var description = $('#' + description_eid + '').val();
    form_data.append("description", description);

    var earningpaytype = $("input[name='" + earpaytype_ename + "']:checked").val();
    form_data.append("earningpaytype", earningpaytype);

    var calculationtype = $("input[name='" + caltype_ename + "']:checked").val();
    form_data.append("calculationtype", calculationtype);

    var calculationval = $('#' + calval_eid + '').val();
    form_data.append("calculationval", calculationval);

    form_data.append("loginname", $("body").data("loginname"));
    let modalId = $("#" + savetodbbtnid + "").closest(".modal").attr("id");
    $.ajax({
        url: baseurl + "modal/hrm_modal.php?action=SaveOrUpdateEarningComponenttoPayroll",
        type: 'POST',
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            if (res.status == "Success") {
                alertmessage('success', res.message);
                $(".refresh_ErcompPayroll").trigger("click");
                $("#" + modalId).modal("hide");
            } else {
                alertmessage('error', res.message);
                return false
            }
        },
        error: function (xhr, status, error) {
            alert('An error occurred: ' + error);
        }
    });

}

function SaveOrUpdateDeductiontoPayroll(deductionname_eid, deductionsortname_eid, description_eid, deductionfrequency_ename, deductiontype_ename, empdeductionbase_ename, emplrdeductionbase_ename, employeededctionvalue_eid, employerdedctionvalue_eid, iscontributeinctc_eid, iscontributeinfbp_eid, iscontributeinproratabasis_eid, savetodbbtnid) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var deductionname = $('#' + deductionname_eid + ' option:selected').text();
    form_data.append("deductionname", deductionname);

    var deductionsortname = $('#' + deductionsortname_eid + '').val();
    form_data.append("deductionsortname", deductionsortname);

    var description = $('#' + description_eid + '').val();
    form_data.append("description", description);


    var deductionfrequency = $("input[name='" + deductionfrequency_ename + "']:checked").val();
    if (deductionfrequency == "" || deductionfrequency == null || deductionfrequency == "undefined") {
        form_data.append("deductionfrequency", '');
    } else {
        form_data.append("deductionfrequency", deductionfrequency);
    }

    var deductiontype = $("input[name='" + deductiontype_ename + "']:checked").val();
    if (deductiontype == "" || deductiontype == null || deductiontype == "undefined") {
        form_data.append("deductiontype", '');
    } else {
        form_data.append("deductiontype", deductiontype);
    }

    var empdeductionbase = $("input[name='" + empdeductionbase_ename + "']:checked").val();
    if (empdeductionbase == "" || empdeductionbase == null || empdeductionbase == "undefined") {
        form_data.append("empdeductionbase", '');
    } else {
        form_data.append("empdeductionbase", empdeductionbase);
    }

    var emplrdeductionbase = $("input[name='" + emplrdeductionbase_ename + "']:checked").val();
    if (emplrdeductionbase == "" || emplrdeductionbase == null || emplrdeductionbase == "undefined") {
        form_data.append("emplrdeductionbase", '');
    } else {
        form_data.append("emplrdeductionbase", emplrdeductionbase);
    }

    // alert(empdeductionbase+'=='+emplrdeductionbase);

    var employeededctionvalue = $('#' + employeededctionvalue_eid + '').val();
    form_data.append("employeededctionvalue", employeededctionvalue);

    var employerdedctionvalue = $('#' + employerdedctionvalue_eid + '').val();
    form_data.append("employerdedctionvalue", employerdedctionvalue);

    if ($("#" + iscontributeinctc_eid).is(":checked")) {
        form_data.append("iscontributeinctc", 'yes');
    } else {
        form_data.append("iscontributeinctc", '');
    }

    if ($("#" + iscontributeinfbp_eid).is(":checked")) {
        form_data.append("iscontributeinfbp", 'yes');
    } else {
        form_data.append("iscontributeinfbp", '');
    }

    if ($("#" + iscontributeinproratabasis_eid).is(":checked")) {
        form_data.append("iscontributeinproratabasis", 'yes');
    } else {
        form_data.append("iscontributeinproratabasis", '');
    }

    form_data.append("loginname", $("body").data("loginname"));
    var deductionid = $("#" + savetodbbtnid + "").data("deductionid");
    // alert(deductionid);
    if (deductionid == "" || deductionid == null || deductionid == "undefined") {
        alertmessage("warning", "Deduction selection faild");
        return false;
    }
    form_data.append("deductionid", deductionid);

    let modalId = $("#" + savetodbbtnid + "").closest(".modal").attr("id");
    $.ajax({
        url: baseurl + "modal/hrm_modal.php?action=SaveOrUpdateDeductiontoPayroll",
        type: 'POST',
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            if (res.status == "Success" || res.status == "success") {
                alertmessage('success', res.message);
                $(".refresh_DrcompPayroll").trigger("click");
                $("#" + modalId).modal("hide");
            } else {
                alertmessage('error', res.message);
                return false
            }
        },
        error: function (xhr, status, error) {
            alert('An error occurred: ' + error);
        }
    });

}

function SaveOrUpdateDepartment(departmentname_eid, savetobtnid) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var departmentname = $('#' + departmentname_eid + '').val();
    form_data.append("departmentname", departmentname);
    form_data.append("loginname", $("body").data("loginname"));
    form_data.append("departmentid", $("#" + savetobtnid + "").data("departmentid"));
    $.ajax({
        url: baseurl + "modal/hrm_modal.php?action=SaveOrUpdateDepartment",
        type: 'POST',
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            if (res.status == "Success" || res.status == "success") {
                alertmessage('success', res.message);
                $('#' + departmentname_eid + '').empty();
                $("#" + savetobtnid + "").data("departmentid", '');
                $('.refreshdepartment').trigger("click");
            } else {
                alertmessage('error', res.message);
                return false
            }
        },
        error: function (xhr, status, error) {
            alert('An error occurred: ' + error);
        }
    });
}

function SaveOrUpdateDesignation(designationname_eid, savetobtnid) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var designationname = $('#' + designationname_eid + '').val();
    form_data.append("designationname", designationname);
    form_data.append("loginname", $("body").data("loginname"));
    form_data.append("designationid", $("#" + savetobtnid + "").data("designationid"));
    $.ajax({
        url: baseurl + "modal/hrm_modal.php?action=SaveOrUpdateDesignation",
        type: 'POST',
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function (res) {
            // console.log(res);
            if (res.status == "Success" || res.status == "success") {
                alertmessage('success', res.message);
                $('#' + designationname_eid + '').empty();
                $("#" + savetobtnid + "").data("departmentid", '');
                $('.refreshdepartment').trigger("click");
            } else {
                alertmessage('error', res.message);
                return false
            }
        },
        error: function (xhr, status, error) {
            alert('An error occurred: ' + error);
        }
    });
}

function EmplistforCalculateAttendance_htmlcallback(salarymonthyear_eid, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var salaryyearmonth = $("#" + salarymonthyear_eid + "").val();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("salaryyearmonth", salaryyearmonth);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_salarymodal.php?action=EmployeelistforCalculateAttendancehtml",
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

function InsertOrUpdateCalculatedAttendance_Payroll(salarymonthyear_eid, attenancetblid, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var salaryyearmonth = $("#" + salarymonthyear_eid + "").val();
    var loginname = $("body").data("loginname");
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("salaryyearmonth", salaryyearmonth);
    form_data.append("loginname", loginname);

    var ArrayDataLIst = new Array();
    $("#" + attenancetblid + " tbody tr").each(function (row, tr) {
        if ($(tr).find('input.presentdays').val() > 0) {
            ArrayDataLIst[row] = {
                "companyid": $(tr).data('companyid')
                , "subcompanyid": $(tr).data('subcompanyid')
                , "projectid": $(tr).data('projectid')
                , "employeeid": $(tr).data('employeeid')
                , "employeename": $(tr).data('employeename')
                , "attendanceyear": $(tr).data('attendanceyear')
                , "attendancemonth": $(tr).data('attendancemonth')
                , "calculatepresentdays": $(tr).find('input.presentdays').val()
            }
        }
    });

    ArrayDataLIst = ArrayDataLIst;
    // ArrayDataLIst.shift();
    ArrayDataLIst = JSON.stringify(ArrayDataLIst);

    form_data.append('empattdata', ArrayDataLIst);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_salarymodal.php?action=InsertOrUpdateCalculatedAttendance_Payroll",
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

function EmplistforCalculateSalary_htmlcallback(salarymonthyear_eid, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var salaryyearmonth = $("#" + salarymonthyear_eid + "").val();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("salaryyearmonth", salaryyearmonth);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_salarymodal.php?action=EmplistforCalculateSalaryhtml",
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

function InsertOrUpdateCalculatedSalary_Payroll(salarymonthyear_eid, attenancetblid, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var salaryyearmonth = $("#" + salarymonthyear_eid).val();
    var loginname = $("body").data("loginname");
    if (!companyid && !subcompanyid && !projectid) {
        alertmessage("error", "Something wrong, Try again");
        return false;
    }
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("salaryyearmonth", salaryyearmonth);
    form_data.append("loginname", loginname);

    var ArraySLDataLIst = [];

    $("#" + attenancetblid + " tbody tr").each(function () {
        var netpayable = $(this).find("td.netpayable").text().trim();
        if (netpayable !== "" && !isNaN(parseFloat(netpayable)) && parseFloat(netpayable) >= 0) {
            ArraySLDataLIst.push({
                "companyid": $(this).data("companyid"),
                "subcompanyid": $(this).data("subcompanyid"),
                "projectid": $(this).data("projectid"),
                "employeeid": $(this).data("employeeid"),
                "employeename": $(this).data("employeename"),
                "attendanceyear": $(this).data("attendanceyear"),
                "attendancemonth": $(this).data("attendancemonth"),
                "advanceagainstdeduction": $(this).closest("tr").find("td.advdeduction").text(),
                "netpayableamount": netpayable
            });
        }
    });
    form_data.append("empSalaryData", JSON.stringify(ArraySLDataLIst));

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_salarymodal.php?action=InsertOrUpdateCalculatedSalary_Payroll",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",  // Ensuring JSON response
        success: function (res) {
            callback(res);
        }
    });
}

function EmployeeSalarySlipCreat(SalaryId) {
    if (!SalaryId) {
        alertmessage('error', 'Invalid Salary ID.');
        return;
    }
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    if (!baseurl) {
        baseurl = $("#hidden_routurl").val() || "";
    }
    if (baseurl && !baseurl.endsWith('/')) {
        baseurl += '/';
    }
    form_data.append("SalaryId", SalaryId);
    $.ajax
        ({
            type: "POST",
            url: (baseurl || "") + "modal/hrm_salarymodal.php?action=EmployeeSalarySlipCreat",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                if (res && (res.status == "Success" || res.status == "success")) {
                    if (res.filepath) {
                        window.open((baseurl || "") + res.filepath, '_blank');
                    } else {
                        alertmessage('error', 'Payslip file path not found.');
                    }
                } else {
                    alertmessage('error', (res && res.message) ? res.message : 'Error generating payslip');
                }
            },
            error: function(xhr, status, error) {
                console.error("Payslip generation AJAX error:", error, xhr.responseText);
                var errDetail = "Failed to generate payslip PDF.";
                try {
                    var parsed = JSON.parse(xhr.responseText);
                    if (parsed && parsed.message) errDetail = parsed.message;
                } catch(e){}
                alertmessage('error', errDetail);
            }
        });
}

function CalculatedSalaryData(salarymonthyear_eid, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var salaryyearmonth = $("#" + salarymonthyear_eid + "").val();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("salaryyearmonth", salaryyearmonth);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_salarymodal.php?action=CalculatedSalaryData",
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

function PendingBookFromCalculatedSalaryData(salarymonthyear_eid, callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var salaryyearmonth = $("#" + salarymonthyear_eid + "").val();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("salaryyearmonth", salaryyearmonth);
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_salarymodal.php?action=PendingBookFromCalculatedSalaryData",
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



function CreateBookFromCalculatedSalaryData(crttableid, savetobtnid) {
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
        if ($(this).find("input.slrdbookcheckbox").is(":checked")) {
            BookSLDataLIst.push({
                "employeeid": $(this).data("employeeid"),  // must match data-employeeid
                "salaryyear": $(this).data("salaryyear"),
                "salarymonth": $(this).data("salarymonth"),
                "netsalary": $(this).data("netsalary")
            });
        }
    });
    form_data.append("empSalaryData", JSON.stringify(BookSLDataLIst));
    $.ajax
        ({
            type: "POST",
            url: baseurl + "modal/hrm_salarymodal.php?action=CreateBookFromCalculatedSalaryData",
            data: form_data,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'JSON',
            success: function (res) {
                // console.log(res);
                // callback(res);
                var msgdis = "";
                let succescount = 0;
                let failedcount = 0;
                if (res.length > 0) {
                    for (var i = 0; i < res.length; i++) {
                        if (res[i].status == "success" || res[i].status == "Success") {
                            succescount++;
                            // msgdis +=res[i].message;
                        } else {
                            failedcount++;
                            // msgdis +=res[i].message;
                        }
                    }
                    if (succescount > 0) {
                        if (succescount === res.length) {
                            msgdis = "All employee salary has been approved and ready to transfer the payment.";
                        } else if (succescount < res.length) {
                            msgdis = succescount + " Employee salary has been approved and ready to transfer the payment.";
                        } else {
                            msgdis = " Employee salary did not approved for payment, due to something wrong.";
                        }
                        Swal.fire({
                            title: msgdis,
                            text: "Do you view & get report of salary",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#3085d6",
                            cancelButtonColor: "#09C1D1",
                            confirmButtonText: "Yes",
                            cancelButtonText: "No, Refresh",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                viewcalculatedSalaryData();
                            } else {
                                window.location.reload();
                            }
                        });
                    }
                }
            }
        });
}


function Download_SalaryReportExcel(salaryyear, salarymonth) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("salaryyear", salaryyear);
    form_data.append("salarymonth", salarymonth);
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_salarymodal.php?action=GetSalaryreporthtml",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "html",
        success: function (res) {
            if (res) {
                // Create temporary container
                var $tempDiv = $("<div>", { id: "tempSalaryReportContainer", style: "display:none;" }).appendTo("body");
                $tempDiv.html(res);

                // Trigger Excel download
                var filename = projectid + "_" + salaryyear + "_" + salarymonth + "_SALARY_REPORT.xls";
                gridtoexcel_withfilter_chunk('SalaryReporthtml', filename, 'landscape', function () {
                    // Destroy temporary container after download
                    $tempDiv.remove();
                });
            } else {
                alert("No salary report data received.");
            }
        },
        error: function (xhr, status, error) {
            console.error("Error fetching salary report:", error);
            alert("Failed to fetch salary report.");
        }
    });
}

function Download_BankSalaryReportExcel(salaryyear, salarymonth) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("salaryyear", salaryyear);
    form_data.append("salarymonth", salarymonth);
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_salarymodal.php?action=GetBankSalaryreporthtml",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "html",
        success: function (res) {
            if (res) {
                var $tempDiv = $("<div>", { id: "tempBankSalaryReportContainer", style: "display:none;" }).appendTo("body");
                $tempDiv.html(res);

                var filename = projectid + "_" + salaryyear + "_" + salarymonth + "_BANK_SALARY_REPORT.xls";
                gridtoexcel_withfilter_chunk('BankSalaryReporthtml', filename, 'landscape', function () {
                    $tempDiv.remove();
                });
            } else {
                alert("No bank salary report data received.");
            }
        },
        error: function (xhr, status, error) {
            console.error("Error fetching bank salary report:", error);
            alert("Failed to fetch bank salary report.");
        }
    });
}

function Download_EPFChalanEXCEL(salaryyear, salarymonth) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("salaryyear", salaryyear);
    form_data.append("salarymonth", salarymonth);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_salarymodal.php?action=GetEPFreporthtml",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "html",
        success: function (res) {
            if (res && $.trim(res) !== "") {
                // Create a temporary container
                var tempId = "tempContainer_" + Date.now();
                var $tempDiv = $("<div>", {
                    id: tempId,
                    css: { display: "none" }
                }).appendTo("body");
                // Append the received HTML
                $tempDiv.html(res);
                // Trigger Excel download
                gridtoexcel_withfilter_chunk(tempId, projectid + "_" + salaryyear + "_" + salarymonth + "_EPF_SHEET.xls",
                    "landscape", function () {
                        // Remove the temporary container after download
                        $("#" + tempId).remove();
                    }
                );
            } else {
                alert("No data received for EPF report.");
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
        }
    });
}


function Download_EPFChalanTEXT(salaryyear, salarymonth) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("salaryyear", salaryyear);
    form_data.append("salarymonth", salarymonth);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_salarymodal.php?action=GetEPFChallanText",
        data: form_data,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (res) {
            if (res && res.content) {
                // Create a blob and trigger download
                var blob = new Blob([res.content], { type: "text/plain" });
                var link = document.createElement("a");
                link.href = window.URL.createObjectURL(blob);
                link.download = res.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert("No EPF data available.");
            }
        },
        error: function (xhr, status, error) {
            console.error("Error fetching EPF data:", error);
            alert("Failed to fetch EPF data.");
        }
    });
}

function AttendanceLocationMaster_callback(shortby, shortbyvalue, callback) {
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
            url: baseurl + "modal/hrm_modal.php?action=AttendanceLocationMaster_callback",
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

function saveEmployeeAttendanceLocationExemption(EmployeeId, LocationId, dateFrom, dateTo, Comments, btnid) {
    if (EmployeeId == "" || LocationId == "" || dateFrom == "" || dateTo == "") {
        Swal.fire({
            icon: "warning",
            title: "Missing Details",
            text: "Employee, Location, From Date and To Date are required."
        });
        return;
    }
    $("#" + btnid).html('<i class="fa fa-spinner fa-spin"></i> Please Wait...').prop("disabled", true);
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    var loginname = $("body").data("loginname");
    var fd = new FormData();
    fd.append("companyid", companyid);
    fd.append("subcompanyid", subcompanyid);
    fd.append("projectid", projectid);
    fd.append("loginname", loginname);
    fd.append("EmployeeID", EmployeeId);
    fd.append("LocationId", LocationId);
    fd.append("AttDateFrom", dateFrom);
    fd.append("AttDateTo", dateTo);
    fd.append("Comments", Comments);
    Swal.fire({
        title: "Saving...",
        text: "Please wait while we process the request.",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    $.ajax({
        url: baseurl + "modal/hrm_modal.php?action=saveEmployeeAttendanceLocationExemption",
        type: "POST",
        data: fd,
        contentType: false,
        processData: false,
        success: function (res) {
            console.log(res);
            Swal.close();
            try {
                var json = JSON.parse(res);
                if (json.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: (json.action == "updated" ? "Updated Successfully!" : "Saved Successfully!"),
                        text: json.message
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Failed!",
                        text: json.message
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Unexpected response from server."
                });
            }
            // Reset Button
            $("#" + btnid).html("Save").prop("disabled", false);
        },
        error: function () {
            Swal.close();
            Swal.fire({
                icon: "error",
                title: "Request Failed!",
                text: "Cannot connect to server."
            });
            $("#" + btnid).html("Save").prop("disabled", false);
        }
    });
}

function loadEmployeeExemptedLocations(year, sortby = "all", sortbyvalue = "all") {
    var projectid = $("body").data("projectid");
    // Show loading spinner in table box
    $("#exemptedLocationTableBody").html(`
        <tr>
            <td colspan="8" class="text-center py-4">
                <div class="spinner-border" role="status"></div>
                <div class="mt-2">Loading...</div>
            </td>
        </tr>
    `);

    $.ajax({
        url: $("body").data("routurl") +
            "modal/hrm_modal.php?action=getEmployeeExemptedLocations",
        type: "POST",
        data: { ProjectID: projectid, ExemptYear: year, sortby: sortby, sortbyvalue: sortbyvalue },
        success: function (response) {
            if (response.length > 0) {
                renderExemptedLocationTable(response);
            }
        },
        error: function () {
            Swal.fire("Error", "Failed to load data", "error");
        }
    });
}

function renderExemptedLocationTable(data) {
    console.log(data);
    if (typeof data === "string") {
        data = JSON.parse(data);
    }

    let html = "";

    if (!data || data.length === 0) {
        html = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    No data found
                </td>
            </tr>
        `;
        $("#exemptedLocationTableBody").html(html);
        return;
    }

    data.forEach((row, i) => {
        html += `
            <tr data-EmpId="${row.EmployeeID}" data-LocId="${row.LocationId}" data-dbsno="${row.sno}">
                <td>${i + 1}</td>
                <td>${row.EmployeeFullName ?? "-"}</td>
                <td>${row.ExemptedLocationName ?? "-"}</td>
                <td>${row.AttendanceDate ?? "-"}</td>
                <td>${row.Comments ?? '-'}</td>
                <td>${row.created_by ?? '-'}</td>
                <td>${row.created_at ? row.created_at : "-"}</td>
            </tr>
        `;
    });

    $("#exemptedLocationTableBody").html(html);
}



function saveLocationlatlng(locationId, sno, lat, lng, btn) {

    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    // -------------------------------
    // VALIDATION
    // -------------------------------
    if (!companyid) {
        Swal.fire("Missing Company ID", "Company ID not found. Please refresh the page.", "warning");
        return;
    }

    if (!subcompanyid) {
        Swal.fire("Missing Sub-Company ID", "Sub-company ID not found.", "warning");
        return;
    }

    if (!projectid) {
        Swal.fire("Missing Project ID", "Project ID not found.", "warning");
        return;
    }

    if (!locationId) {
        Swal.fire("Missing Location", "Location ID is missing.", "warning");
        return;
    }

    if (!sno) {
        Swal.fire("Missing Row Number", "Database row ID (sno) missing.", "warning");
        return;
    }

    if (!lat || !lng) {
        Swal.fire("Coordinates Required", "Latitude and Longitude cannot be empty.", "warning");
        return;
    }

    // Button loading
    var originalBtnHtml = $(btn).html();
    $(btn).prop("disabled", true).html(`
        <span class="spinner-border spinner-border-sm"></span> Saving...
    `);

    // Build form data
    var form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("locationId", locationId);
    form_data.append("sno", sno);
    form_data.append("lat", lat);
    form_data.append("lng", lng);

    // AJAX
    $.ajax({
        url: baseurl + "/modal/hrm_modal.php?action=savelocationlatlng",
        type: "POST",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",

        success: function (res) {
            $(btn).prop("disabled", false).html(originalBtnHtml);

            if (res.status === "success") {
                Swal.fire("Saved!", "Location coordinates updated successfully.", "success");
            } else {
                Swal.fire("Failed!", res.message || "Something went wrong.", "error");
            }
        },
        error: function () {
            $(btn).prop("disabled", false).html(originalBtnHtml);
            Swal.fire("Error", "Unable to save data. Check your internet or server.", "error");
        }
    });

}



function saveLocationJs(nameId, addressId, commentsId, buttonId) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");

    let locationId = $("#" + buttonId).attr("data-locationid");
    let locationName = $("#" + nameId).val().trim();
    let locationAddress = $("#" + addressId).val().trim();
    let comments = $("#" + commentsId).val().trim();
    let shortname = $("#locationShortName").length ? $("#locationShortName").val().trim() : "";

    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("locationId", locationId);
    form_data.append("locationName", locationName);
    form_data.append("locationAddress", locationAddress);
    form_data.append("comments", comments);
    form_data.append("shortname", shortname);

    $.ajax({
        url: baseurl + "/modal/hrm_modal.php?action=addupdatelocation",
        type: "POST",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function (res) {
            if (res.status === "success") {
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
                $("#" + buttonId).attr("data-locationid", "");
                $("#" + nameId + ",#" + addressId + ",#" + commentsId).val("");
                $("#locationShortName").val("");
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: res.message,
                });
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", error);
        }
    });
}


function fetcheEmployeeCalculatedAttendance(yearmonthbtndropid, viewcalculatedAtttblid) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    let yearmonth = $("#" + yearmonthbtndropid + "").val();
    form_data.append("yearmonth", yearmonth);
    let yearMonthVal = yearmonth?.trim();
    let yearMonthValExp = yearMonthVal.split("-") || [];
    let attYearVal = yearMonthValExp[0] || "";
    let attMonthVal = yearMonthValExp[1] || "";
    autofillDatesFromMonthYear(attYearVal, attMonthVal, 'EmpAttendanceFrom', 'EmpAttendanceTo');

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_salarymodal.php?action=fetcheEmployeeCalculatedAttendance",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            // callback(res);
            if (res.length > 0) {
                $("#actionbtngroup").removeClass('d-none');
                $("#calattcard").removeClass('d-none');
                renderCalculatedAttendance(res, viewcalculatedAtttblid);
            } else {
                $("#calattcard").addClass('d-none');
                $("#actionbtngroup").addClass('d-none');
            }
        },
        error: function () {
            alertmessage("error", "Something went wrong while updating payment.");
        }
    });
}

function renderCalculatedAttendance(data, viewcalculatedAtttblid) {
    let tbody = document.querySelector("#" + viewcalculatedAtttblid + " tbody");
    tbody.innerHTML = ""; // Clear old rows
    let summary = {
        totalMonthDays: 0,
        totalPresent: 0,
        totalLeaves: 0,
        totalHolidays: 0,
        totalWeekOff: 0,
        totalAbsent: 0,
        totalSandwich: 0,
        totalLOP: 0,
        totalExtra: 0,
        totalLateDeduction: 0,
        totalPayable: 0
    };

    let countAll = 0;
    let countCalculated = 0;
    let countNotCalculated = 0;
    let countLateDed = 0;
    let countExtraDays = 0;
    let countLeaves = 0;
    let totalEmployeesWithLate = 0;
    let checkedLateCount = 0;

    data.forEach((row, index) => {
        if (row.TotalPayableDays == 0) return; // Skip this row

        let isCalc = (row.isCalculated == 1 || row.is_calculated == 1);
        let lateArrivalsCount = parseInt(row.LateArrivals || 0);
        let lateDeductionVal = parseFloat(row.LateDeductionDays || 0);
        let computedLateDed = parseFloat(row.computedLateDeductionDays || Math.floor(lateArrivalsCount / 3) || 0);
        let hasLate = (lateArrivalsCount > 0 || lateDeductionVal > 0);
        let hasExtra = (parseFloat(row.ExtraWorkingDays || 0) > 0);
        let hasLeaves = (parseFloat(row.Leaves || 0) > 0);

        // Checkbox status logic:
        // If already saved: if 'latedeductiondays' > 0 -> checked; otherwise -> unchecked
        // If not saved: if computedLateDed > 0 -> checked; otherwise -> unchecked
        let isLateChecked = isCalc ? (lateDeductionVal > 0) : (computedLateDed > 0);
        let chkChecked = isLateChecked ? 'checked' : '';

        if (hasLate) {
            totalEmployeesWithLate++;
            if (isLateChecked) {
                checkedLateCount++;
            }
        }

        countAll++;
        if (isCalc) {
            countCalculated++;
        } else {
            countNotCalculated++;
        }
        if (isLateChecked) {
            countLateDed++;
        }
        if (hasExtra) {
            countExtraDays++;
        }
        if (hasLeaves) {
            countLeaves++;
        }

        // Update summary
        summary.totalMonthDays += parseFloat(row.TotalDaysInMonth || 0);
        summary.totalPresent += parseFloat(row.CalculatedPresentDays || 0);
        summary.totalLeaves += parseFloat(row.Leaves || 0);
        summary.totalHolidays += parseFloat(row.Holidays || 0);
        summary.totalWeekOff += parseFloat(row.WeekOffs || 0);
        summary.totalAbsent += parseFloat(row.AbsentDays || 0);
        summary.totalSandwich += parseFloat(row.SandwichConverted || 0);
        summary.totalLOP += parseFloat(row.LOP || 0);
        summary.totalExtra += parseFloat(row.ExtraWorkingDays || 0);
        summary.totalLateDeduction += isLateChecked ? (lateDeductionVal > 0 ? lateDeductionVal : computedLateDed) : 0;
        summary.totalPayable += parseFloat(row.TotalPayableDays || 0);

        // Row color styling
        let absentColor = row.AbsentDays > 10 ? "background-color:#fff2e6;" : "";
        let lopColor = row.LOP > 10 ? "background-color:#ffe6e6;" : "";
        let sandwichBadge = `<span class="badge bg-primary">${row.SandwichConverted}</span>`;

        let lateCellContent = "";
        if (hasLate) {
            let lateRemarkEsc = (row.LateDeductionRemark || "").replace(/"/g, '&quot;');
            let displayDedDays = isLateChecked ? (lateDeductionVal > 0 ? lateDeductionVal : computedLateDed) : 0;
            let lateBadge = `<a href="javascript:void(0)" onclick='showLateDaysModal(${JSON.stringify(JSON.stringify(row))})' class="badge bg-danger text-white text-decoration-none shadow-sm late-ded-badge" title="Click to view ${lateArrivalsCount} late day details: ${lateRemarkEsc}"><i class="ti ti-clock-exclamation me-1"></i>${displayDedDays} (${lateArrivalsCount} Late)</a>`;
            
            lateCellContent = `
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <input type="checkbox" class="form-check-input late-ded-chk border-secondary" ${chkChecked} onchange="toggleLateDeductionForRow(this)" style="cursor:pointer; width: 17px; height: 17px;" title="Toggle Late Deduction for this employee">
                    <span>${lateBadge}</span>
                </div>
            `;
        } else {
            lateCellContent = `<span class="text-muted">0</span>`;
        }

        let baseurl = $("body").data("routurl") || "";
        let appKey = $("body").data("key") || (new URLSearchParams(window.location.search).get("k") || "");
        let empDashUrl = `${baseurl}empPanel/index.php?k=${appKey}&empid=${row.EmployeeID}&accessby=adminu`;
        let calcBadge = isCalc ? `<sup><span class="badge badge-pill badge-success ms-1">attendance calculated</span></sup>` : '';
        let empCombinedLink = `<a href="${empDashUrl}" target="_blank" class="text-decoration-none" title="Click to open Employee Dashboard for attendance edit/correction"><span class="fw-bold text-danger me-1">${row.EmployeeID}</span> - ${row.EmployeeName}</a> ${calcBadge}`;

        let grossSalaryVal = parseFloat(row.GrossSalary || 0);
        let formattedGrossSalary = grossSalaryVal > 0 ? grossSalaryVal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00';

        let tr = `
            <tr data-empid="${row.EmployeeID}" 
                data-is-calculated="${isCalc ? '1' : '0'}"
                data-late-ded="${isLateChecked ? '1' : '0'}"
                data-extra-days="${hasExtra ? '1' : '0'}"
                data-leaves="${hasLeaves ? '1' : '0'}"
                data-gross-salary="${grossSalaryVal}"
                data-PresentDays="${row.PresentDays}" 
                data-HalfDays="${row.HalfDays}" 
                data-ExtraHoursWorked="${row.ExtraHoursWorked}" 
                data-rowdata='${JSON.stringify(JSON.stringify(row))}'>
                <td style="text-align:center;">${countAll}</td>
                <td class="employeename text-start" style="text-align:left !important;">${empCombinedLink}</td>
                <td class="fw-semibold text-dark">${formattedGrossSalary}</td>
                <td>${row.TotalDaysInMonth}</td>
                <td>${row.CalculatedPresentDays}</td>
                <td>${row.Leaves}</td>
                <td>${row.Holidays}</td>
                <td>${row.WeekOffs}</td>
                <td style="${absentColor}">${row.AbsentDays}</td>
                <td>${sandwichBadge}</td>
                <td style="${lopColor}">${row.LOP}</td>
                <td>${row.ExtraWorkingDays || 0}</td>
                <td class="text-center">${lateCellContent}</td>
                <td class="text-center fw-bold text-primary" style="text-decoration: underline; cursor: pointer;" onclick='showEmployeeDetails(${JSON.stringify(JSON.stringify(row))})'>${row.TotalPayableDays}</td>
            </tr>
        `;

        tbody.insertAdjacentHTML("beforeend", tr);
    });

    // Update Header Master Checkbox state
    let isAllLateMasterChecked = (totalEmployeesWithLate > 0 && checkedLateCount === totalEmployeesWithLate);
    $("#toggle-all-late-ded").prop("checked", isAllLateMasterChecked);

    // Update KPI Card Numbers
    $("#cardCountAll").text(countAll);
    $("#cardCountCalculated").text(countCalculated);
    $("#cardCountNotCalculated").text(countNotCalculated);
    $("#cardCountLateDed").text(countLateDed);
    $("#cardCountExtraDays").text(countExtraDays);
    $("#cardCountLeaves").text(countLeaves);

    if (typeof calculateAttendanceSummary === "function") {
        calculateAttendanceSummary();
    }

    // Apply active filter if any
    if (typeof applyAttendanceCombinedFilter === "function") {
        applyAttendanceCombinedFilter();
    }
}

function toggleLateDeductionForRow(chk) {
    let $tr = $(chk).closest('tr');
    recalculateRowPayable($tr);
    updateCalculatedAttendanceSummary();
    if (typeof calculateAttendanceSummary === "function") {
        calculateAttendanceSummary();
    }
}

function toggleAllLateDeductions(masterChk) {
    let isChecked = $(masterChk).is(':checked');
    $("#attpreviewtbl tbody tr").each(function () {
        let $chk = $(this).find('.late-ded-chk');
        if ($chk.length > 0) {
            $chk.prop('checked', isChecked);
            recalculateRowPayable($(this));
        }
    });
    updateCalculatedAttendanceSummary();
    if (typeof calculateAttendanceSummary === "function") {
        calculateAttendanceSummary();
    }
}

function recalculateRowPayable($tr) {
    let rawRowData = $tr.attr("data-rowdata");
    let row = {};
    if (rawRowData) {
        try {
            let p1 = JSON.parse(rawRowData);
            row = (typeof p1 === "string") ? JSON.parse(p1) : p1;
        } catch(e) {}
    }

    let $chk = $tr.find('.late-ded-chk');
    let isLateApplied = $chk.length > 0 ? $chk.is(':checked') : false;
    let lateArrivalsCount = parseInt(row.LateArrivals || 0);
    let computedLateDed = parseFloat(row.computedLateDeductionDays || Math.floor(lateArrivalsCount / 3) || parseFloat(row.LateDeductionDays || 0) || 0);
    let lateDedDays = isLateApplied ? computedLateDed : 0;

    let totalDays = parseFloat(row.TotalDaysInMonth || $tr.find('td').eq(3).text() || 0);
    let calcPresent = parseFloat(row.CalculatedPresentDays || $tr.find('td').eq(4).text() || 0);
    let leaves = parseFloat(row.Leaves || $tr.find('td').eq(5).text() || 0);
    let holidays = parseFloat(row.Holidays || $tr.find('td').eq(6).text() || 0);
    let weekoffs = parseFloat(row.WeekOffs || $tr.find('td').eq(7).text() || 0);

    let rawPayable = calcPresent + leaves + holidays + weekoffs - lateDedDays;
    let netPayable = Math.max(0, Math.min(totalDays, rawPayable));
    let lop = Math.max(0, totalDays - netPayable);

    // Update row cells on screen (Col 10 = LOP, Col 13 = Net Payable)
    $tr.find('td').eq(10).text(lop);
    $tr.find('td').eq(13).text(netPayable);

    // Update late deduction badge text on screen
    $tr.find('.late-ded-badge').html(`<i class="ti ti-clock-exclamation me-1"></i>${lateDedDays} (${lateArrivalsCount} Late)`);

    // Update internal row object
    row.effectiveLateDeductionDays = lateDedDays;
    row.applyLateDeduction = isLateApplied;
    row.LateDeductionDays = lateDedDays;
    row.computedLateDeductionDays = computedLateDed;
    row.TotalPayableDays = netPayable;
    row.LOP = lop;
    $tr.attr('data-rowdata', JSON.stringify(JSON.stringify(row)));
    $tr.attr('data-late-ded', isLateApplied ? '1' : '0');
}

function updateCalculatedAttendanceSummary() {
    let summary = {
        totalGrossSalary: 0,
        totalMonthDays: 0,
        totalPresent: 0,
        totalLeaves: 0,
        totalHolidays: 0,
        totalWeekOff: 0,
        totalAbsent: 0,
        totalSandwich: 0,
        totalLOP: 0,
        totalExtra: 0,
        totalLateDeduction: 0,
        totalPayable: 0
    };

    $("#attpreviewtbl tbody tr").each(function () {
        let tds = $(this).find("td");
        if (tds.length < 14) return;
        let empid = $(this).data("empid");
        if (!empid) return;

        let $chk = $(this).find('.late-ded-chk');
        let isLateApplied = $chk.length > 0 ? $chk.is(':checked') : true;
        let rawRowData = $(this).attr("data-rowdata");
        let row = {};
        try {
            let p1 = JSON.parse(rawRowData);
            row = (typeof p1 === "string") ? JSON.parse(p1) : p1;
        } catch(e) {}

        summary.totalGrossSalary += parseFloat(row.GrossSalary || tds.eq(2).text().replace(/,/g, '') || 0);
        summary.totalMonthDays += parseFloat(tds.eq(3).text() || 0);
        summary.totalPresent += parseFloat(tds.eq(4).text() || 0);
        summary.totalLeaves += parseFloat(tds.eq(5).text() || 0);
        summary.totalHolidays += parseFloat(tds.eq(6).text() || 0);
        summary.totalWeekOff += parseFloat(tds.eq(7).text() || 0);
        summary.totalAbsent += parseFloat(tds.eq(8).text() || 0);
        summary.totalSandwich += parseFloat(row.SandwichConverted || tds.eq(9).find('.badge').text() || 0);
        summary.totalLOP += parseFloat(tds.eq(10).text() || 0);
        summary.totalExtra += parseFloat(tds.eq(11).text() || 0);
        summary.totalLateDeduction += isLateApplied ? parseFloat(row.LateDeductionDays || 0) : 0;
        summary.totalPayable += parseFloat(tds.eq(13).text() || 0);
    });

    let $lastTr = $("#attpreviewtbl tbody tr:last");
    if ($lastTr.find("td").first().text().trim() === "TOTAL") {
        let stds = $lastTr.find("td");
        stds.eq(1).text(summary.totalGrossSalary > 0 ? summary.totalGrossSalary.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00');
        stds.eq(2).text(summary.totalMonthDays);
        stds.eq(3).text(summary.totalPresent);
        stds.eq(4).text(summary.totalLeaves);
        stds.eq(5).text(summary.totalHolidays);
        stds.eq(6).text(summary.totalWeekOff);
        stds.eq(7).text(summary.totalAbsent);
        stds.eq(8).text(summary.totalSandwich);
        stds.eq(9).text(summary.totalLOP);
        stds.eq(10).text(summary.totalExtra);
        stds.eq(11).text(summary.totalLateDeduction);
        stds.eq(12).text(summary.totalPayable);
    }
}

// Excel Export
function exportSalaryToExcel(exporttblid) {
    let table = document.getElementById(exporttblid);
    let wb = XLSX.utils.table_to_book(table);
    XLSX.writeFile(wb, "Salary_Report.xlsx");
}

function saveCalculatedAttendance(yearmonthbtndropid, viewcalculatedAtttblid) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    form_data.append("companyid", $("body").data("companyid"));
    form_data.append("subcompanyid", $("body").data("subcompanyid"));
    form_data.append("projectid", $("body").data("projectid"));
    form_data.append("yearmonth", $("#" + yearmonthbtndropid).val());
    form_data.append("loginid", $("body").data("loginid"));
    form_data.append("loginname", $("body").data("loginname"));
    let rows = [];

    $("#" + viewcalculatedAtttblid + " tbody tr").each(function () {
        let tds = $(this).find("td");
        let empid = $(this).data("empid");
        if (!empid) return; // Skip summary or invalid rows

        let rawRowData = $(this).attr("data-rowdata");
        let parsedRow = {};
        if (rawRowData) {
            try {
                let p1 = JSON.parse(rawRowData);
                parsedRow = (typeof p1 === "string") ? JSON.parse(p1) : p1;
            } catch(e) {}
        }

        let isLateApplied = $(this).find('.late-ded-chk').is(':checked');
        let effLateDedDays = isLateApplied ? parseFloat(parsedRow.LateDeductionDays || 0) : 0;
        let effLateRemark = isLateApplied 
            ? (parsedRow.LateDeductionRemark || "") 
            : ((parsedRow.LateDeductionRemark || "") + " [Late Deduction Waived]");

        let rowData = {
            EmployeeID: empid,
            TotalDaysInMonth: parseFloat(tds.eq(3).text()),
            WorkingDaysInMonth: $(this).data("workingdays") ?? parsedRow.WorkingDaysInMonth ?? 0,
            PresentDays: $(this).data("presentdays") ?? parsedRow.PresentDays ?? 0,
            HalfDays: $(this).data("halfdays") ?? parsedRow.HalfDays ?? 0,
            ExtraHoursWorked: $(this).data("extrahoursworked") ?? parsedRow.ExtraHoursWorked ?? "00:00",
            CalculatedPresentDays: parseFloat(tds.eq(4).text()),
            Leaves: parseFloat(tds.eq(5).text()),
            HoliDays: parseFloat(tds.eq(6).text()),
            WeekOffs: parseFloat(tds.eq(7).text()),
            AbsentDays: parseFloat(tds.eq(8).text()),
            SandwichConverted: parseFloat(tds.eq(9).text()),
            LOP: parseFloat(tds.eq(10).text()),
            ExtraWorkingDays: parseFloat(tds.eq(11).text()),
            LateArrivals: parsedRow.LateArrivals ?? 0,
            LateDeductionDays: effLateDedDays,
            LateDeductionRemark: effLateRemark,
            TotalPayableDays: parseFloat(tds.eq(13).text()),
            SalaryMonth: $("#uploadsalarymonth").val()
        };

        // Skip invalid rows
        if (!rowData.EmployeeID) return;
        if (rowData.TotalPayableDays === 0) return;

        rows.push(rowData);
    });

    if (rows.length === 0) {
        Swal.fire({
            title: "No Data",
            text: "No employee attendance data available for calculation in the selected month.",
            icon: "info",
            confirmButtonColor: "#0284c7"
        });
        return;
    }

    // Add rows to form data
    form_data.append("attendanceData", JSON.stringify(rows));

    let tblRowCount = rows.length;

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_salarymodal.php?action=saveCalculatedAttendance",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function (res) {

            if (!res || res.status !== "success") {
                alertmessage("error", res?.message ?? "Error saving attendance.");
                return;
            }

            let result = res.result ?? [];
            let inserted = 0, updated = 0;

            // Count insert/update results
            result.forEach(r => {
                if (r.status === "inserted") inserted++;
                if (r.status === "updated") updated++;
            });

            let totalSaved = inserted + updated;
            let failed = Math.max(0, tblRowCount - totalSaved);

            // Create categorized color-coded HTML message
            let badgesHtml = "";
            if (inserted > 0) {
                badgesHtml += `<span class="badge bg-success p-2 fs-13 shadow-sm" style="font-size: 0.85rem;"><i class="ti ti-user-plus me-1"></i> ${inserted} New Employee(s) Attendance Saved</span> `;
            }
            if (updated > 0) {
                badgesHtml += `<span class="badge bg-primary p-2 fs-13 shadow-sm" style="font-size: 0.85rem;"><i class="ti ti-refresh me-1"></i> ${updated} Existing Employee(s) Attendance Updated</span> `;
            }
            if (failed > 0) {
                badgesHtml += `<span class="badge bg-danger p-2 fs-13 shadow-sm" style="font-size: 0.85rem;"><i class="ti ti-alert-triangle me-1"></i> ${failed} Failed</span> `;
            }

            let htmlMsg = `
                <div class="my-3 text-center">
                    <p class="fs-15 text-dark fw-bold mb-2">Attendance Processing Summary for Salary Calculation</p>
                    <div class="d-flex justify-content-center gap-2 flex-wrap my-3">
                        ${badgesHtml}
                    </div>
                    <p class="text-muted fs-13 mb-0 mt-2">Attendance has been successfully updated for salary calculation. Do you want to view salary calculations now?</p>
                </div>
            `;

            Swal.fire({
                title: "Attendance Saved Successfully",
                html: htmlMsg,
                icon: "success",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "View Salary Calculations",
                cancelButtonText: "Close",
            }).then((result) => {
                if (result.isConfirmed) {
                    showCalculatedSalary(yearmonthbtndropid);
                    if (typeof setActiveWorkflowStep === 'function') {
                        setActiveWorkflowStep(2);
                    }
                } else {
                    checkPendingApprovalAndFetchAttendance(yearmonthbtndropid, viewcalculatedAtttblid);
                }
            });
        },
        error: function () {
            alertmessage("error", "Something went wrong while saving attendance.");
        }
    });
}


function calculateSalaryAuditSummary() {
    let totalGross = 0;
    let totalPayableDays = 0;
    let zeroDaysEmpCount = 0;
    let totalBasic = 0;
    let totalHRA = 0;
    let totalConv = 0;
    let totalAllow = 0;
    let totalEPF = 0;
    let totalESIC = 0;
    let totalITax = 0;
    let totalAdv = 0;
    let totalOther = 0;
    let totalNetPayable = 0;
    let visibleCount = 0;

    $("#Salarycalculateddatatbl tbody tr:visible").each(function () {
        let tds = $(this).find("td");
        if (tds.length < 14) return; // Skip summary or empty rows

        let gross = parseFloat(tds.eq(2).text().replace(/[₹,]/g, '').trim()) || 0;
        let days = parseFloat(tds.eq(3).text().trim()) || 0;
        let basic = parseFloat(tds.eq(4).text().trim()) || 0;
        let hra = parseFloat(tds.eq(5).text().trim()) || 0;
        let conv = parseFloat(tds.eq(6).text().trim()) || 0;
        let allow = parseFloat(tds.eq(7).text().trim()) || 0;
        let epf = parseFloat(tds.eq(8).text().trim()) || 0;
        let esic = parseFloat(tds.eq(9).text().trim()) || 0;
        let itax = parseFloat(tds.eq(10).text().trim()) || 0;
        let adv = parseFloat(tds.eq(11).text().trim()) || 0;
        let other = parseFloat(tds.eq(12).text().trim()) || 0;
        let net = parseFloat(tds.eq(13).text().trim()) || 0;

        visibleCount++;
        totalGross += gross;
        totalPayableDays += days;
        if (days === 0) {
            zeroDaysEmpCount++;
        }
        totalBasic += basic;
        totalHRA += hra;
        totalConv += conv;
        totalAllow += allow;
        totalEPF += epf;
        totalESIC += esic;
        totalITax += itax;
        totalAdv += adv;
        totalOther += other;
        totalNetPayable += net;
    });

    // Update KPI Summary Cards
    $("#sumTotalDays").text(totalPayableDays.toFixed(1));
    $("#sumZeroDaysEmpCount").text(zeroDaysEmpCount);
    $("#sumTotalBasic").text('₹' + totalBasic.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#sumTotalHRA").text('₹' + totalHRA.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#sumTotalConv").text('₹' + totalConv.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#sumTotalAllow").text('₹' + totalAllow.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#sumTotalEPF").text('₹' + totalEPF.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#sumTotalESIC").text('₹' + totalESIC.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#sumTotalITax").text('₹' + totalITax.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#sumTotalAdv").text('₹' + totalAdv.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#sumTotalOther").text('₹' + totalOther.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#sumTotalNetPayable").text('₹' + Math.round(totalNetPayable).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 }));

    // Update Table Footer (Total Row at end of table)
    let tfootHtml = `
        <tr class="fw-bold bg-light text-dark" style="border-top: 2px solid #cbd5e1;">
            <td colspan="2" class="text-end fw-bold">TOTAL (${visibleCount} Emp):</td>
            <td id="tblTotalGross">${totalGross > 0 ? totalGross.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00'}</td>
            <td id="tblTotalDays">${totalPayableDays.toFixed(1)}</td>
            <td id="tblTotalBasic">${totalBasic.toFixed(2)}</td>
            <td id="tblTotalHRA">${totalHRA.toFixed(2)}</td>
            <td id="tblTotalConv">${totalConv.toFixed(2)}</td>
            <td id="tblTotalAllow">${totalAllow.toFixed(2)}</td>
            <td id="tblTotalEPF">${totalEPF.toFixed(2)}</td>
            <td id="tblTotalESIC">${totalESIC.toFixed(2)}</td>
            <td id="tblTotalITax">${totalITax.toFixed(2)}</td>
            <td id="tblTotalAdv" class="td-advdeduction-foot">${totalAdv.toFixed(2)}</td>
            <td id="tblTotalOther">${totalOther.toFixed(2)}</td>
            <td id="tblTotalNetPayable" class="bg-success text-white">${Math.round(totalNetPayable)}</td>
        </tr>
    `;

    $("#Salarycalculateddatatbl tfoot").html(tfootHtml);
}

function calculateReportSummary() {
    let totalPayableDays = 0;
    let zeroDaysEmpCount = 0;
    let totalBasic = 0;
    let totalHRA = 0;
    let totalConv = 0;
    let totalAllow = 0;
    let totalEPF = 0;
    let totalESIC = 0;
    let totalITax = 0;
    let totalOther = 0;
    let totalNetPayable = 0;
    let visibleCount = 0;

    $("#empPayslipsalry_tbl tbody tr:visible").each(function () {
        let tds = $(this).find("td");
        if (tds.length < 12) return;

        let days = parseFloat(tds.eq(2).text().trim()) || 0;
        let basic = parseFloat(tds.eq(3).text().trim()) || 0;
        let hra = parseFloat(tds.eq(4).text().trim()) || 0;
        let conv = parseFloat(tds.eq(5).text().trim()) || 0;
        let allow = parseFloat(tds.eq(6).text().trim()) || 0;
        let epf = parseFloat(tds.eq(7).text().trim()) || 0;
        let esic = parseFloat(tds.eq(8).text().trim()) || 0;
        let itax = parseFloat(tds.eq(9).text().trim()) || 0;
        let other = parseFloat(tds.eq(10).text().trim()) || 0;
        let net = parseFloat(tds.eq(11).text().trim()) || 0;

        visibleCount++;
        totalPayableDays += days;
        if (days === 0) {
            zeroDaysEmpCount++;
        }
        totalBasic += basic;
        totalHRA += hra;
        totalConv += conv;
        totalAllow += allow;
        totalEPF += epf;
        totalESIC += esic;
        totalITax += itax;
        totalOther += other;
        totalNetPayable += net;
    });

    // Update KPI Summary Cards for Report section
    $("#rpt_sumTotalDays").text(totalPayableDays.toFixed(1));
    $("#rpt_sumZeroDaysEmpCount").text(zeroDaysEmpCount);
    $("#rpt_sumTotalBasic").text('₹' + totalBasic.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#rpt_sumTotalHRA").text('₹' + totalHRA.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#rpt_sumTotalConv").text('₹' + totalConv.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#rpt_sumTotalAllow").text('₹' + totalAllow.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#rpt_sumTotalEPF").text('₹' + totalEPF.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#rpt_sumTotalESIC").text('₹' + totalESIC.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#rpt_sumTotalITax").text('₹' + totalITax.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#rpt_sumTotalOther").text('₹' + totalOther.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $("#rpt_sumTotalNetPayable").text('₹' + totalNetPayable.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

    // Update Table Footer
    let tfootHtml = `
        <tr class="fw-bold bg-light text-dark" style="border-top: 2px solid #cbd5e1;">
            <td colspan="2" class="text-end fw-bold">TOTAL (${visibleCount} Emp):</td>
            <td>${totalPayableDays.toFixed(1)}</td>
            <td>${totalBasic.toFixed(2)}</td>
            <td>${totalHRA.toFixed(2)}</td>
            <td>${totalConv.toFixed(2)}</td>
            <td>${totalAllow.toFixed(2)}</td>
            <td>${totalEPF.toFixed(2)}</td>
            <td>${totalESIC.toFixed(2)}</td>
            <td>${totalITax.toFixed(2)}</td>
            <td>${totalOther.toFixed(2)}</td>
            <td class="bg-primary text-white">${totalNetPayable.toFixed(2)}</td>
            <td></td>
        </tr>
    `;

    $("#empPayslipsalry_tbl tfoot").html(tfootHtml);
}

function calculateAttendanceSummary() {
    let totGrossSalary = 0;
    let totMonthDays = 0;
    let totPresentDays = 0;
    let totLeaves = 0;
    let totHolidays = 0;
    let totWeekOff = 0;
    let totAbsent = 0;
    let totSandwich = 0;
    let totLOP = 0;
    let totExtraDays = 0;
    let totNetPayable = 0;
    let visibleCount = 0;

    $("#attpreviewtbl tbody tr:visible").each(function () {
        let tds = $(this).find("td");
        if (tds.length < 14) return;

        let rawRowData = $(this).attr("data-rowdata");
        let parsedRow = {};
        if (rawRowData) {
            try {
                let p1 = JSON.parse(rawRowData);
                parsedRow = (typeof p1 === "string") ? JSON.parse(p1) : p1;
            } catch(e) {}
        }

        visibleCount++;
        let grossVal = parseFloat(parsedRow.GrossSalary) || parseFloat(tds.eq(2).text().replace(/,/g, '').trim()) || 0;
        totGrossSalary += grossVal;
        totMonthDays += parseFloat(tds.eq(3).text().trim()) || 0;
        totPresentDays += parseFloat(tds.eq(4).text().trim()) || 0;
        totLeaves += parseFloat(tds.eq(5).text().trim()) || 0;
        totHolidays += parseFloat(tds.eq(6).text().trim()) || 0;
        totWeekOff += parseFloat(tds.eq(7).text().trim()) || 0;
        totAbsent += parseFloat(tds.eq(8).text().trim()) || 0;
        totSandwich += parseFloat(tds.eq(9).text().trim()) || 0;
        totLOP += parseFloat(tds.eq(10).text().trim()) || 0;
        totExtraDays += parseFloat(tds.eq(11).text().trim()) || 0;
        totNetPayable += parseFloat(tds.eq(13).text().trim()) || 0;
    });

    let tfootHtml = `
        <tr class="fw-bold bg-light text-dark" style="border-top: 2px solid #cbd5e1;">
            <td colspan="2" class="text-end fw-bold">TOTAL (${visibleCount} Emp):</td>
            <td>${totGrossSalary > 0 ? totGrossSalary.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00'}</td>
            <td>${totMonthDays}</td>
            <td>${totPresentDays.toFixed(1)}</td>
            <td>${totLeaves}</td>
            <td>${totHolidays}</td>
            <td>${totWeekOff}</td>
            <td>${totAbsent}</td>
            <td>${totSandwich}</td>
            <td>${totLOP}</td>
            <td>${totExtraDays}</td>
            <td></td>
            <td class="bg-success text-white">${totNetPayable.toFixed(1)}</td>
        </tr>
    `;

    $("#attpreviewtbl tfoot").html(tfootHtml);
}

// Live calculation on editing advance deduction cell
$(document).on("keyup input change blur", "#Salarycalculateddatatbl .advdeduction", function () {
    let tr = $(this).closest("tr");
    let basic = parseFloat(tr.find(".basic").text()) || 0;
    let hra = parseFloat(tr.find(".hra").text()) || 0;
    let allowances = parseFloat(tr.find(".allowances").eq(0).text()) || 0;
    let specAllow = parseFloat(tr.find(".allowances").eq(1).text()) || 0;
    let epf = parseFloat(tr.find(".epfdeduction").text()) || 0;
    let esic = parseFloat(tr.find(".esicdeduction").text()) || 0;
    let itax = parseFloat(tr.find(".emptaxdeduction").text()) || 0;
    let other = parseFloat(tr.find(".otherdeduction").text()) || 0;
    let adv = parseFloat($(this).text()) || 0;

    let earnings = basic + hra + allowances + specAllow;
    let otherDeductions = epf + esic + itax + other;
    let maxAdvAllowed = Math.max(0, earnings - otherDeductions);

    if (adv > maxAdvAllowed) {
        adv = maxAdvAllowed;
        $(this).text(adv.toFixed(2));
        Swal.fire({
            title: "Advance Amount Capped",
            text: "Advance deduction cannot exceed available net salary. Advance has been set to equal available net salary.",
            icon: "warning",
            timer: 2000,
            showConfirmButton: false
        });
    }

    let exactNet = Math.max(0, maxAdvAllowed - adv);
    let netPayable = Math.round(exactNet);
    let roundOff = parseFloat((netPayable - exactNet).toFixed(2));

    tr.find(".netpayable").text(netPayable);
    tr.attr("data-netpayableamount", netPayable);
    tr.attr("data-roundoff", roundOff);
    calculateSalaryAuditSummary();
});

function showCalculatedSalary(yearmonthbtndropid) {
    CalculatedSalaryData(yearmonthbtndropid, function (res) {
        let companyid = $("body").data("companyid");
        let subcompanyid = $("body").data("subcompanyid");
        let projectid = $("body").data("projectid");
        var routurl = $("body").data("routurl");
        var key = $("body").data("key");
        var mdid = $("body").data("mdid");
        let yearmonthVal = $("#" + yearmonthbtndropid).val();
        $("#calattcard").addClass('d-none');
        $("#calsalarycard").removeClass('d-none');
        $("#reportsalarycard").addClass('d-none');
        if (res.length > 0) {
            EmplistforCalculateSalary_htmlcallback(yearmonthbtndropid, function (response) {
                if (response) {
                    $("#Salarycalculateddatatbl tbody").html(response);
                    calculateSalaryAuditSummary();
                    let calcc = 0;
                    $("#Salarycalculateddatatbl tbody tr").each(function () {
                        let status = $(this).data("calculatedstatus");
                        if (!status) {   // catches "", null, undefined
                            calcc++;
                        }
                    });
                    if (calcc === 0) {
                        Swal.fire({
                            title: "Salary has been already calculated",
                            text: "Do you want to view the report?",
                            icon: "info",
                            showCancelButton: true,
                            confirmButtonColor: "#3085d6",
                            cancelButtonColor: "#6c757d",
                            confirmButtonText: "Yes, View Report",
                            cancelButtonText: "Close",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                getSalaryReports(yearmonthVal);
                                if (typeof setActiveWorkflowStep === 'function') {
                                    setActiveWorkflowStep(4);
                                }
                            }
                        });
                    }
                } else {
                    $("#Salarycalculateddatatbl tbody").html(
                        "<tr><td colspan='14' class='text-center text-danger'>No salary data available for selected month.</td></tr>"
                    );
                    calculateSalaryAuditSummary();
                }
            });
        } else {
            // Calculate salary
            EmplistforCalculateSalary_htmlcallback(yearmonthbtndropid, function (response) {
                // console.log(res);
                if (response && response.length > 0) {
                    $("#Salarycalculateddatatbl tbody").html(response);
                    calculateSalaryAuditSummary();
                } else {
                    $("#Salarycalculateddatatbl tbody").html(
                        "<tr><td colspan='14' class='text-center text-danger'>No salary data available for selected month.</td></tr>"
                    );
                    calculateSalaryAuditSummary();
                }
            });
        }
    });
}

function saveCalculatedSalary(yearmonthbtndropid, CalculatedSalarytblid) {
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    var routurl = $("body").data("routurl");
    var key = $("body").data("key");
    var mdid = $("body").data("mdid");
    let $tableRows = $("#" + CalculatedSalarytblid + " tbody tr");
    let trlenghtsltbl = $tableRows.length;

    // Validate table data availability
    if (trlenghtsltbl === 0 || $tableRows.find("td").length <= 1) {
        Swal.fire({
            icon: "warning",
            title: "No Salary Data",
            text: "No calculated salary data found. Please calculate or view salary first before saving.",
            confirmButtonColor: "#0284c7"
        });
        return;
    }

    // Confirmation warning modal before proceeding to save salary
    Swal.fire({
        title: "Confirm Salary Finalization",
        html: `
            <div class="text-start mt-2">
                <p class="fs-15 text-dark fw-bold mb-2">Are you sure all corrections are done?</p>
                <div class="alert alert-warning border-0 p-2 fs-13 mb-3" style="background-color: #fffbeb; border-left: 4px solid #f59e0b !important;">
                    <i class="ti ti-alert-triangle me-1 text-warning fs-5 align-middle"></i>
                    If any correction is pending, please do it first and then proceed to calculate attendance for salary calculation.
                </div>
                <p class="text-danger fw-semibold fs-13 mb-0">
                    <i class="ti ti-lock me-1"></i> Note: Once salary is saved, all edits will be closed.
                </p>
            </div>
        `,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ea580c",
        cancelButtonColor: "#64748b",
        confirmButtonText: '<i class="ti ti-check me-1"></i> Yes, Proceed & Save',
        cancelButtonText: '<i class="ti ti-x me-1"></i> Cancel / Check Corrections',
        reverseButtons: true,
        allowOutsideClick: false
    }).then((confirmResult) => {
        if (!confirmResult.isConfirmed) {
            return;
        }

        // Show processing loader
        Swal.fire({
            title: "Saving Salary...",
            text: "Please wait while salary records are being processed and saved.",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        let sccsscount = 0;
        InsertOrUpdateCalculatedSalary_Payroll(yearmonthbtndropid, CalculatedSalarytblid, function (res) {
            if (typeof res === 'string') {
                try { res = JSON.parse(res); } catch (e) { }
            }
            if (res && res.length > 0) {
                let errorDetails = [];
                for (let i = 0; i < res.length; i++) {
                    if (res[i].status == "success" || res[i].status == "Success" || res[i].status == 1) {
                        sccsscount++;
                    } else {
                        errorDetails.push((res[i].employeename || 'Employee') + ": " + (res[i].message || "Error"));
                    }
                }
                if (sccsscount > 0) {
                    if (typeof setActiveWorkflowStep === 'function') {
                        setActiveWorkflowStep(4);
                    }

                    let summaryText = `Successfully processed salary for ${sccsscount} employee(s).`;
                    if (errorDetails.length > 0) {
                        summaryText += `<br><small class="text-danger">${errorDetails.join('<br>')}</small>`;
                    }
                    Swal.fire({
                        title: sccsscount === trlenghtsltbl ? "Salary Saved Successfully" : "Salary Partially Saved",
                        html: summaryText + "<br><br>Do you want to view the report?",
                        icon: sccsscount === trlenghtsltbl ? "success" : "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#6c757d",
                        confirmButtonText: "Yes, View Report",
                        cancelButtonText: "Close",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let dropyearmonthval = $("#" + yearmonthbtndropid).length ? $("#" + yearmonthbtndropid).val() : yearmonthbtndropid;
                            getSalaryReports(dropyearmonthval);
                        }
                    });
                } else {
                    let errText = errorDetails.length > 0 ? errorDetails.join('<br>') : "Failed to Save Salary. Please check error logs.";
                    Swal.fire({
                        title: "Failed to Save Salary",
                        html: errText,
                        icon: "error"
                    });
                }
            } else {
                Swal.fire("Failed to Save Salary", "No valid response received from server.", "error");
            }
        });
    });
}

function getSalaryReports(yearmonthbtndropid) {
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    var routurl = $("body").data("routurl");
    var key = $("body").data("key");
    var mdid = $("body").data("mdid");
    let yearMonthVal = ($("#" + yearmonthbtndropid).length ? $("#" + yearmonthbtndropid).val() : yearmonthbtndropid) || $("#AttendanceYearMonth").val() || "";
    let yearMonthValExp = yearMonthVal.split("-");
    let attYearVal = yearMonthValExp[0] || "";
    let attMonthVal = yearMonthValExp[1] || "";
    var yearmonthtext = yearMonthVal;
    EmployeeSalaryforpayslip(attYearVal, attMonthVal, 'all', 'all', function (res) {
        // console.log(res)
        $("#calsalarycard").addClass('d-none');
        $("#calattcard").addClass('d-none');
        $("#reportsalarycard").removeClass('d-none');
        var sslhtml = "";
        var epfesiexist = 0;
        if (res.length > 0) {
            var j = 1;
            for (var i = 0; i < res.length; i++) {
                // Convert month number to abbreviation
                if (res[i].EPFDeduction > 0 || res[i].ESICDeduction > 0) {
                    epfesiexist++;
                }
                let empId = res[i].EmployeeID || res[i].EmployeeId || '';
                let empDashUrl = `${routurl}empPanel/index.php?k=${key}&empid=${empId}&accessby=adminu`;
                let empCombinedLink = `<a href="${empDashUrl}" target="_blank" class="text-decoration-none" title="Click to open Employee Dashboard"><span class="fw-bold text-danger me-1">${empId}</span> - ${res[i].EmployeeName}</a>`;
                sslhtml += `<tr data-slid='${res[i].SalaryID}'><td>${j}</td><td style="text-align:left;">${empCombinedLink}</td><td>${res[i].CalculatedPresentDays}</td><td>${res[i].BasicSalary}</td><td>${res[i].HRA}</td><td>${res[i].Conveyance}</td><td>${res[i].Allowances}</td><td>${res[i].EPFDeduction}</td><td>${res[i].ESICDeduction}</td><td>${res[i].IncomeTaxDeduction}</td><td>${res[i].otherDeduction}</td><td>${res[i].NetSalary}</td><td class="downloadPaySlip noexport" style="cursor:pointer" onclick="EmployeeSalarySlipCreat(${res[i].SalaryID});"><i class="fa fa-download" style="color:red; font-size:20px;"></i></td></tr>`;
                j++;
            }
            if (sslhtml) {
                $("#empPayslipsalry_tbl tbody").html(sslhtml);
                $(".exportsheet").removeClass("d-none");
                if (typeof calculateReportSummary === "function") {
                    calculateReportSummary();
                }
            } else {
                $("#empPayslipsalry_tbl tbody").html("<tr><td colspan='13'>No data available for the month of " + yearmonthtext + "</td></tr>");
                if (typeof calculateReportSummary === "function") {
                    calculateReportSummary();
                }
            }
            if (parseInt(epfesiexist) > 0) {
                $("#DownloadEPfChalantext").closest(".exportsheet").removeClass("d-none");
                $("#DownloadEPfChalanexcel").closest(".exportsheet").removeClass("d-none");
                $("#DownloadEPfChalantext").removeClass("d-none");
                $("#DownloadEPfChalanexcel").removeClass("d-none");
            } else {
                $("#DownloadEPfChalantext").closest(".exportsheet").addClass("d-none");
                $("#DownloadEPfChalanexcel").closest(".exportsheet").addClass("d-none");
                $("#DownloadEPfChalantext").addClass("d-none");
                $("#DownloadEPfChalanexcel").addClass("d-none");
            }
        } else {
            $("#empPayslipsalry_tbl tbody").html("<tr><td colspan='13' class='text-center'>No data available for the month of " + yearmonthtext + "</td></tr>");
        }
    });
}

function saveWorkingHours() {
    let formData = new FormData();
    var baseurl = $("body").data("routurl");
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("loginid", $("body").data("loginid"));
    formData.append("loginname", $("body").data("loginname"));

    let id = $("#wh_id").val();  // empty = insert, value = update
    let workinghour = $("#workinghour_name").val();
    let start_time = $("#start_time").val();
    let end_time = $("#end_time").val();

    if (workinghour === "") {
        alertmessage("warning", "Please select Working Hours");
        return;
    }

    if (!start_time || !end_time) {
        alertmessage("warning", "Please select Start and End Time");
        return;
    }

    formData.append("id", id);
    formData.append("workinghour", workinghour);
    formData.append("start_time", start_time);
    formData.append("end_time", end_time);

    $.ajax({
        url: baseurl + "modal/hrm_modal.php?action=save_working_hours",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (res) {
            // console.log(res);
            try {
                let json = JSON.parse(res);

                if (json.status === "success") {
                    alertmessage("success", json.message);
                    $("#WorkingHourseModal").modal("hide");
                    getWorkingHoursCallback();
                } else {
                    alertmessage("warning", json.message);
                }

            } catch (e) {
                alertmessage("warning", "Unexpected response: " + res);
            }
        }
    });
}

function getWorkingHoursData() {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_modal.php?action=getWorkingHoursCallback",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            if (res.length > 0) {
                renderWorkingHoursinTable(res, 'workingHoursTable');
            }
        },
        error: function () {
            alertmessage("error", "Something went wrong while updating payment.");
        }
    });
}

function renderWorkingHoursinTable(data, tableID) {
    let tbody = document.querySelector("#" + tableID + " tbody");
    tbody.innerHTML = ""; // Clear old rows
    data.forEach((row, index) => {
        let tr = `
            <tr style="cursor:pointer;" data-dbid="${row.id}" data-whname="${row.working_hour_name}" data-starttime="${row.start_time}" data-endtime="${row.end_time}">
                <td style="text-align:center;">${index + 1}</td>
                <td>${row.working_hour_name}</td>
                <td>${row.start_time}</td>
                <td>${row.end_time}</td>
                <td>${row.total_hours}</td>
                <td><button class='btn btn-sm editpwh'><i class='fa fa-edit'></i> Edit</td>
            </tr>
        `;
        tbody.insertAdjacentHTML("beforeend", tr);
    });
}
function WorkingHoursDataCallback(callback) {
    var form_data = new FormData();
    var baseurl = $("body").data("routurl");
    var companyid = $("body").data("companyid");
    var subcompanyid = $("body").data("subcompanyid");
    var projectid = $("body").data("projectid");
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_modal.php?action=getWorkingHoursCallback",
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

function saveApplyWorkingHoursToEmployee() {
    let formData = new FormData();
    var baseurl = $("body").data("routurl");
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("loginid", $("body").data("loginid"));
    formData.append("loginname", $("body").data("loginname"));

    let workinghourTiming = $("#workinghourTiming").val();  // empty = insert, value = update
    let applytoworkinghouse = $("input[name='applytoworkinghouse']:checked").val();
    let AppyToEmployeesId = $("#AppyToEmployeesId").val();

    if (workinghourTiming === "") {
        alertmessage("warning", "Please select Working Hours");
        return;
    }

    formData.append("workinghourTiming", workinghourTiming);
    formData.append("applytoworkinghouse", applytoworkinghouse);
    formData.append("AppyToEmployeesId", AppyToEmployeesId);

    $.ajax({
        url: baseurl + "modal/hrm_modal.php?action=saveApplyWorkingHoursToEmployee",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (res) {
            // console.log(res);
            Swal.close();
            try {
                let json = JSON.parse(res);

                if (json.status === "success") {
                    alertmessage("success", json.message);
                    $(".modal").modal("hide");
                } else {
                    alertmessage("warning", json.message);
                }

            } catch (e) {
                alertmessage("warning", "Unexpected response: " + res);
            }
        }, beforeSend: function (res) {
            Swal.fire({
                title: "Please wait...",
                html: "Working hours update is being processed.",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading(); // Show loading spinner
                }
            });
        }
    });
}

function loadAttendanceSummary(daterange = "") {
    let baseurl = $("body").data("routurl");
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    $.ajax({
        url: baseurl + "modal/getAttendanceSummary.php",
        type: "POST",
        dataType: "json",
        data: {
            companyid: companyid,
            subcompanyid: subcompanyid,
            projectid: projectid,
            daterange: daterange
        },
        success: function (res) {
            console.log(res);
            if (!res.status) {
                console.warn(res.message);
                return;
            }
            // 🔐 Save full data for click view
            attendanceSummaryData = res.data;

            // 👥 Populate Employee Filter Dropdown
            let empDrop = $("#EmployeeListdrp");
            if (empDrop.length > 0 && res.data.employees) {
                let currentVal = empDrop.val() || "all";
                empDrop.empty();
                empDrop.append('<option value="all">All Employees</option>');
                $.each(res.data.employees, function (empId, empName) {
                    empDrop.append(`<option value="${empId}">${empId} - ${empName}</option>`);
                });
                empDrop.val(currentVal);
            }

            // update counters
            $(".counter").each(function () {
                let key = $(this).data("key");
                if (key === "markinemployeecount") {
                    let markin = res.data.markinemployeecount ?? 0;
                    let total = res.data.totalactivecount ?? 0;
                    $(this).text(markin + " / " + total);
                } else {
                    animateCounter(this, res.data[key] ?? 0);
                }
            });

            // 🔥 THIS LINE MAKES EVERYTHING WORK
            showDefaultAttendanceView();
        }
    });
}

function showDefaultAttendanceView() {
    let $activeCard = $(".kpi-card.active");
    // 🟢 If some KPI is already active, use it
    if ($activeCard.length) {
        let dataKey = $activeCard.data("view");
        let headerText = getKpiHeaderText(dataKey);
        renderAttendanceTable(dataKey, headerText);
        return;
    }

    // 🔵 Otherwise default to Present
    let $defaultCard = $('.kpi-card[data-view="markinemployeedata"]');
    if ($defaultCard.length) {
        $(".kpi-card").removeClass("active");
        $defaultCard.addClass("active");

        let dataKey = $defaultCard.data("view");
        let headerText = getKpiHeaderText(dataKey);
        renderAttendanceTable(dataKey, headerText);
    }
}

function getKpiHeaderText(dataKey) {
    const headerMap = {
        presentemployeedata: "Present Employees",
        markinemployeedata: "Mark-In Employees",
        misemployeedata: "MIS Attendance Employees",
        lateemployeedata: "Late Login Employees",
        earlylogoutdata: "Early Logout Employees",
        permissiondata: "Permission Employees",
        absentdata: "Absent Employees",
        uninformeddata: "Uninformed Employees"
    };
    return headerMap[dataKey] || "Employee Attendance";
}

// 🔃 Column sort state for #attendanceviewbyadmin (kept across auto-refresh)
var attendanceSort = { key: null, dir: 'asc' };

function attendanceSortValue(row, key) {
    let v = row[key];
    if (v === undefined || v === null) return null;
    v = String(v).trim();
    if (v === '' || v === '-' || (key === 'markout' && v === 'MIS')) return null; // blanks / missing mark-out always go last
    if (key === 'employeeid' || key === 'workingduration') {
        const n = parseFloat(v);
        return isNaN(n) ? v.toLowerCase() : n;
    }
    if (key === 'attendancedate') {
        const t = Date.parse(v.split(' to ')[0].replace(/-/g, ' '));
        return isNaN(t) ? v.toLowerCase() : t;
    }
    return v.toLowerCase(); // names, HH:MM:SS times, status, location
}

function sortAttendanceRows(rows) {
    if (!attendanceSort.key) return rows;
    const key = attendanceSort.key, mul = attendanceSort.dir === 'asc' ? 1 : -1;
    return rows
        .map((row, idx) => ({ row, idx, val: attendanceSortValue(row, key) }))
        .sort((a, b) => {
            if (a.val === null && b.val === null) return a.idx - b.idx;
            if (a.val === null) return 1;
            if (b.val === null) return -1;
            if (typeof a.val === 'number' && typeof b.val === 'number') return (a.val - b.val) * mul || a.idx - b.idx;
            return String(a.val).localeCompare(String(b.val), undefined, { numeric: true }) * mul || a.idx - b.idx;
        })
        .map(x => x.row);
}

function updateAttendanceSortIcons() {
    $("#attendanceviewbyadmin thead th[data-sort]").each(function () {
        const key = $(this).data("sort");
        let icon = 'ti-arrows-sort opacity-50';
        if (attendanceSort.key && key === attendanceSort.key) {
            icon = attendanceSort.dir === 'asc' ? 'ti-sort-ascending' : 'ti-sort-descending';
        }
        $(this).find(".att-sort-icon").attr("class", "ti " + icon + " att-sort-icon ms-1");
    });
}

$(document).on("click", "#attendanceviewbyadmin thead th[data-sort]", function () {
    const key = $(this).data("sort");
    if (key === 'sno') {
        attendanceSort = { key: null, dir: 'asc' }; // S.No → back to original order
    } else if (attendanceSort.key === key) {
        attendanceSort.dir = attendanceSort.dir === 'asc' ? 'desc' : 'asc';
    } else {
        attendanceSort = { key: key, dir: 'asc' };
    }
    const $activeCard = $(".kpi-card.active");
    if ($activeCard.length && typeof attendanceSummaryData !== 'undefined') {
        const dataKey = $activeCard.data("view");
        renderAttendanceTable(dataKey, getKpiHeaderText(dataKey));
    }
});

function renderAttendanceTable(dataKey, headerText) {
    let tableData = attendanceSummaryData[dataKey] || [];

    // Apply Employee Filter
    let selectedEmp = $("#EmployeeListdrp").val();
    if (selectedEmp && selectedEmp !== "all") {
        tableData = tableData.filter(row => row.employeeid == selectedEmp);
    }
    
    // Show/hide absent sub-filter container
    if (dataKey === 'absentdata') {
        $("#absentSubFilterContainer").removeClass('d-none');
    } else {
        $("#absentSubFilterContainer").addClass('d-none');
        $("#absentSubFilter").val("");
    }
    
    // Apply sub-filter for absent list
    let selectedSubFilter = $("#absentSubFilter").val();
    if (dataKey === 'absentdata' && selectedSubFilter) {
        tableData = tableData.filter(row => row.status === selectedSubFilter);
    }

    tableData = sortAttendanceRows(tableData);
    updateAttendanceSortIcons();

    let tbodyHtml = "";
    // 🔹 Update header
    $("#employeeattheader").text(headerText);
    if (tableData.length === 0) {
        tbodyHtml = `
            <tr>
                <td colspan="9" class="text-center text-muted">
                    No records found
                </td>
            </tr>
        `;
    } else {
        $.each(tableData, function (i, row) {
            let statusBadge = '-';
            if (row.status) {
                let badgeClass = 'bg-secondary';
                if (row.status === 'Present') badgeClass = 'bg-success';
                else if (row.status === 'Mark-In') badgeClass = 'bg-info';
                else if (row.status === 'MIS') badgeClass = 'bg-danger';
                else if (row.status === 'Late') badgeClass = 'bg-warning text-dark';
                else if (row.status === 'Early Out') badgeClass = 'bg-danger';
                else if (row.status === 'Permission') badgeClass = 'bg-primary';
                else if (row.status === 'Weekoff') badgeClass = 'bg-secondary';
                else if (row.status === 'Uninformed') badgeClass = 'bg-dark';
                
                statusBadge = `<span class="badge ${badgeClass}">${row.status}</span>`;
            }
            // Map pins for the exact punch GPS (In / Out)
            const mapPin = (lat, lng, label) => (lat && lng && parseFloat(lat) !== 0)
                ? ` <a href="https://www.google.com/maps?q=${encodeURIComponent(lat)},${encodeURIComponent(lng)}" target="_blank" rel="noopener" title="${label} location on map"><i class="ti ti-map-pin text-danger"></i></a>`
                : '';
            const locationHtml = (row.location || '-') + mapPin(row.inLat, row.inLng, 'Mark-In') + mapPin(row.outLat, row.outLng, 'Mark-Out');
            tbodyHtml += `
                <tr>
                    <td class="text-center">${i + 1}</td>
                    <td>${row.employeeid || '-'}</td>
                    <td>${row.empoyeename || '-'}</td>
                    <td>${row.attendancedate || '-'}</td>
                    <td>${row.markin || '-'}</td>
                    <td>${row.markout || '-'}</td>
                    <td>${row.workingduration || '-'}</td>
                    <td>${statusBadge}</td>
                    <td>${locationHtml}</td>
                </tr>
            `;
        });
    }

    $("#attendanceviewbyadmin tbody").html(tbodyHtml);
}

function getAttendancePAReport(projectid, datefrom, dateto) {
    var routurl = $("body").data("routurl");
    if (!routurl) {
        return;
    }
    var form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("datefrom", datefrom);
    form_data.append("dateto", dateto);

    $.ajax({
        type: "POST",
        url: routurl + "modal/hrm_reportmodal.php?action=getAttendancePAReport",
        data: form_data,
        contentType: false,
        processData: false,
        dataType: 'JSON',
        beforeSend: function () {
            $("#tblPAReport tbody").html('<tr><td colspan="100%" class="text-center">Loading...</td></tr>');
        },
        success: function (res) {
            if (res.status) {
                var dates = res.dates;
                var data = res.data;
                
                // Build Header
                
                var dSet = new Set(), desSet = new Set();
                data.forEach(e => {
                    if(e.Dept && e.Dept != '-') dSet.add(e.Dept);
                    if(e.Desg && e.Desg != '-') desSet.add(e.Desg);
                });
                var deptOpts = '<option value="">All Depts</option>';
                var desgOpts = '<option value="">All Desgs</option>';
                Array.from(dSet).sort().forEach(d => deptOpts += `<option value="${d}">${d}</option>`);
                Array.from(desSet).sort().forEach(d => desgOpts += `<option value="${d}">${d}</option>`);
                $("#pa_filterDept").html(deptOpts);
                $("#pa_filterDesg").html(desgOpts);

                var theadHtml = '<tr><th class="text-center">S.No</th><th>Employee Code</th><th>Employee Name</th><th class="export-col" style="display:none;">Dept</th><th class="export-col" style="display:none;">Desg</th>';
                for (var d = 0; d < dates.length; d++) {
                    var dateObj = new Date(dates[d]);
                    // var formattedDate = dateObj.getDate().toString().padStart(2, '0') + "-" + dateObj.toLocaleString('default', { month: 'short' }) + "-" + dateObj.getFullYear();
                    var formattedDate = dateObj.getDate().toString().padStart(2, '');
                    theadHtml += '<th>' + formattedDate + '</th>';
                }
                theadHtml += '<th>Total Days</th><th>Present</th><th>MIS</th><th>Absent</th><th>Leave</th><th>Permission</th><th>Week Off</th><th>Holiday</th></tr>';
                $("#tblPAReportHead").html(theadHtml);
                
                // Build Body
                var tbodyHtml = "";
                if (data.length > 0) {
                    for (var i = 0; i < data.length; i++) {
                        var emp = data[i];
                        tbodyHtml += "<tr>";
                        tbodyHtml += "<td class='text-center'>" + (i + 1) + "</td>";
                        tbodyHtml += "<td>" + emp.EmployeeCode + "</td>";
                        tbodyHtml += "<td><div class='fw-bold text-dark'>" + emp.EmployeeName + "</div><div class='emp-name-badge mt-1'><span class='badge bg-primary bg-opacity-10 text-primary border border-primary-subtle me-1'>" + emp.Dept + "</span> <span class='badge bg-success bg-opacity-10 text-success border border-success-subtle'>" + emp.Desg + "</span></div></td>";
                        tbodyHtml += "<td class='export-col' style='display:none;'>" + emp.Dept + "</td>";
                        tbodyHtml += "<td class='export-col' style='display:none;'>" + emp.Desg + "</td>";
                        
                        for (var d = 0; d < dates.length; d++) {
                            var status = emp.attendance[dates[d]];
                            var color = 'fw-bold ';
                            if(status === 'P') color += 'text-success';
                            else if(status === 'A') color += 'text-danger';
                            else if(status === 'W/O') color += 'text-secondary';
                            else if(status === 'H') color += 'text-info';
                            else if(status === 'Half') color += 'text-warning';
                            else if(status === 'L') color += 'text-primary';
                            else if(status === 'MIS') color += 'text-warning';
                            else color += 'text-dark';
                            
                            tbodyHtml += "<td class='" + color + "'>" + status + "</td>";
                        }
                        
                        var totalPresent = Number(emp.totals.P) + (Number(emp.totals.HalfDay) * 0.5);
                        tbodyHtml += "<td class='fw-bold'>" + emp.totals.Total + "</td>";
                        tbodyHtml += "<td class='fw-bold text-success col-present-cell' data-total-present='" + totalPresent + "' style='line-height: 1.4;'>F: " + emp.totals.P + "<br>H: " + emp.totals.HalfDay + "<br>Total: " + totalPresent + "</td>";
                        tbodyHtml += "<td class='fw-bold text-warning'>" + emp.totals.MIS + "</td>";
                        tbodyHtml += "<td class='fw-bold text-danger'>" + emp.totals.A + "</td>";
                        tbodyHtml += "<td class='fw-bold text-primary'>" + emp.totals.Leave + "</td>";
                        tbodyHtml += "<td class='fw-bold'>" + emp.totals.Permission + "</td>";
                        tbodyHtml += "<td class='fw-bold text-secondary'>" + emp.totals.WO + "</td>";
                        tbodyHtml += "<td class='fw-bold text-info'>" + emp.totals.H + "</td>";
                        tbodyHtml += "</tr>";
                    }
                } else {
                    var colCount = dates.length + 13;
                    tbodyHtml = "<tr><td colspan='" + colCount + "' class='text-center'>No data available for the selected date range.</td></tr>";
                }
                $("#tblPAReport tbody").html(tbodyHtml);
            } else {
                $("#tblPAReport tbody").html("<tr><td colspan='100%' class='text-center text-danger'>" + res.message + "</td></tr>");
            }
        },
        error: function () {
            $("#tblPAReport tbody").html('<tr><td colspan="100%" class="text-center text-danger">Error fetching report.</td></tr>');
        }
    });
}

function getAttendanceTimeReport(projectid, datefrom, dateto) {
    var routurl = $("body").data("routurl");
    if (!routurl) {
        return;
    }
    var form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("datefrom", datefrom);
    form_data.append("dateto", dateto);

    $.ajax({
        type: "POST",
        url: routurl + "modal/hrm_reportmodal.php?action=getAttendanceTimeReport",
        data: form_data,
        contentType: false,
        processData: false,
        dataType: 'JSON',
        beforeSend: function () {
            $("#tblTimeReport tbody").html('<tr><td colspan="100%" class="text-center">Loading...</td></tr>');
        },
        success: function (res) {
            if (res.status) {
                var dates = res.dates;
                var data = res.data;
                
                // Build Header
                
                var dSet = new Set(), desSet = new Set();
                data.forEach(e => {
                    if(e.Dept && e.Dept != '-') dSet.add(e.Dept);
                    if(e.Desg && e.Desg != '-') desSet.add(e.Desg);
                });
                var deptOpts = '<option value="">All Depts</option>';
                var desgOpts = '<option value="">All Desgs</option>';
                Array.from(dSet).sort().forEach(d => deptOpts += `<option value="${d}">${d}</option>`);
                Array.from(desSet).sort().forEach(d => desgOpts += `<option value="${d}">${d}</option>`);
                $("#time_filterDept").html(deptOpts);
                $("#time_filterDesg").html(desgOpts);

                var theadHtml = '<tr><th class="text-center">S.No</th><th>Employee Code</th><th>Employee Name</th><th class="export-col" style="display:none;">Dept</th><th class="export-col" style="display:none;">Desg</th>';
                for (var d = 0; d < dates.length; d++) {
                    var dateObj = new Date(dates[d]);
                    // var formattedDate = dateObj.getDate().toString().padStart(2, '0') + "-" + dateObj.toLocaleString('default', { month: 'short' }) + "-" + dateObj.getFullYear();
                    var formattedDate = dateObj.getDate().toString().padStart(2, '');
                    theadHtml += '<th class="text-center">' + formattedDate + '</th>';
                }
                theadHtml += '<th>Total Days</th><th>Present</th><th>MIS</th><th>Absent</th><th>Leave</th><th>Permission</th><th>Week Off</th><th>Holiday</th><th>Total Hrs</th></tr>';
                $("#tblTimeReport thead").html(theadHtml);
                
                // Build Body
                var tbodyHtml = "";
                if (data.length > 0) {
                    for (var i = 0; i < data.length; i++) {
                        var emp = data[i];
                        tbodyHtml += "<tr>";
                        tbodyHtml += "<td class='text-center'>" + (i + 1) + "</td>";
                        tbodyHtml += "<td>" + emp.EmployeeCode + "</td>";
                        tbodyHtml += "<td><div class='fw-bold text-dark'>" + emp.EmployeeName + "</div><div class='emp-name-badge mt-1'><span class='badge bg-primary bg-opacity-10 text-primary border border-primary-subtle me-1'>" + emp.Dept + "</span> <span class='badge bg-success bg-opacity-10 text-success border border-success-subtle'>" + emp.Desg + "</span></div></td>";
                        tbodyHtml += "<td class='export-col' style='display:none;'>" + emp.Dept + "</td>";
                        tbodyHtml += "<td class='export-col' style='display:none;'>" + emp.Desg + "</td>";
                        for (var d = 0; d < dates.length; d++) {
                            var status = emp.attendance[dates[d]];
                            var color = '';
                            if(status === 'A') color = 'fw-bold text-danger';
                            else if(status === 'W/O') color = 'fw-bold text-secondary';
                            else if(status === 'H') color = 'fw-bold text-info';
                            else if(status === 'L') color = 'fw-bold text-primary';
                            else if(status.indexOf('-') > -1) color = 'fw-bold text-success';
                            else color = 'fw-bold text-warning';
                            
                            tbodyHtml += "<td class='text-center text-nowrap " + color + "'>" + status + "</td>";
                        }
                        
                        var totalPresent = Number(emp.totals.P) + (Number(emp.totals.HalfDay) * 0.5);
                        tbodyHtml += "<td class='fw-bold'>" + emp.totals.Total + "</td>";
                        tbodyHtml += "<td class='fw-bold text-success col-present-cell' data-total-present='" + totalPresent + "' style='line-height: 1.4;'>F: " + emp.totals.P + "<br>H: " + emp.totals.HalfDay + "<br>Total: " + totalPresent + "</td>";
                        tbodyHtml += "<td class='fw-bold text-warning'>" + emp.totals.MIS + "</td>";
                        tbodyHtml += "<td class='fw-bold text-danger'>" + emp.totals.A + "</td>";
                        tbodyHtml += "<td class='fw-bold text-primary'>" + emp.totals.Leave + "</td>";
                        tbodyHtml += "<td class='fw-bold'>" + emp.totals.Permission + "</td>";
                        tbodyHtml += "<td class='fw-bold text-secondary'>" + emp.totals.WO + "</td>";
                        tbodyHtml += "<td class='fw-bold text-info'>" + emp.totals.H + "</td>";
                        tbodyHtml += "<td class='fw-bold'>" + emp.TotalHrsFormatted + "</td>";
                        tbodyHtml += "</tr>";
                    }
                } else {
                    var colCount = dates.length + 13;
                    tbodyHtml = "<tr><td colspan='" + colCount + "' class='text-center'>No data available for the selected date range.</td></tr>";
                }
                $("#tblTimeReport tbody").html(tbodyHtml);
            } else {
                $("#tblTimeReport tbody").html("<tr><td colspan='100%' class='text-center text-danger'>" + res.message + "</td></tr>");
            }
        },
        error: function () {
            $("#tblTimeReport tbody").html('<tr><td colspan="100%" class="text-center text-danger">Error fetching report.</td></tr>');
        }
    });
}

function showEmployeeDetails(row) {
    if (typeof row === 'string') {
        try {
            row = JSON.parse(row);
        } catch (e) {
            console.error("Failed to parse row: ", e);
        }
    }
    let baseurl = $("body").data("routurl");
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    let yearmonth = $("#AttendanceYearMonth").val();

    if (!yearmonth) {
        alert("Please select a month first");
        return;
    }

    // Set modal title & loading state
    $("#modalEmpName").text(row.EmployeeID + " - " + row.EmployeeName);
    $("#modalMonthYear").text("...");
    $("#sumPresent").text("...");
    $("#sumHalf").text("...");
    $("#sumAbsent").text("...");
    $("#sumLeave").text("...");
    $("#sumWeekoff").text("...");
    $("#sumHoliday").text("...");
    $("#modalAttTbl tbody").html('<tr><td colspan="5" class="text-center text-muted"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>Loading details...</td></tr>');
    
    // Show modal
    $("#attendanceDetailModal").modal("show");

    let form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("employeeid", row.EmployeeID);
    form_data.append("yearmonth", yearmonth);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_salarymodal.php?action=getEmployeeDailyAttendanceDetail",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            if (res.status === "success") {
                let baseurl = $("body").data("routurl") || "";
                let appKey = $("body").data("key") || (new URLSearchParams(window.location.search).get("k") || "");
                let empDashUrl = `${baseurl}empPanel/index.php?k=${appKey}&empid=${res.employeeid}&accessby=adminu`;
                $("#modalEmpName").html(`<a href="${empDashUrl}" target="_blank" class="fw-bold text-primary text-decoration-none me-1" title="Click to open Employee Dashboard for attendance edit/correction">${res.employeeid}</a> - ${res.employeename}`);
                $("#modalMonthYear").text(res.monthyear);
                
                // Populate summaries
                $("#sumPresent").text(res.summary.present);
                $("#sumHalf").text(res.summary.half);
                $("#sumAbsent").text(res.summary.absent);
                $("#sumLeave").text(res.summary.leave);
                $("#sumWeekoff").text(res.summary.weekoff);
                $("#sumHoliday").text(res.summary.holiday);
                
                // Calculate and populate calculation card details
                let totalDays = res.daily.length;
                let p = parseFloat(res.summary.present || 0);
                let h = parseFloat(res.summary.half || 0);
                let l = parseFloat(res.summary.leave || 0);
                let w = parseFloat(res.summary.weekoff || 0);
                let hol = parseFloat(res.summary.holiday || 0);
                
                let calcPresent = p + (h * 0.5);
                let rawPayable = calcPresent + l + w + hol;
                let lateDed = (typeof row !== 'undefined' && row && row.LateDeductionDays) ? parseFloat(row.LateDeductionDays || 0) : 0;
                let rawPayableAfterLate = Math.max(0, rawPayable - lateDed);
                let netPayable = Math.min(totalDays, rawPayableAfterLate);
                
                let workingDays = Math.max(0, totalDays - (w + hol));
                let extraDays = 0;
                if (calcPresent > workingDays) {
                    extraDays = calcPresent - workingDays;
                }
                
                let lateDedText = lateDed > 0 ? ` - ${lateDed} (Late Ded)` : '';
                $("#calcNetPayableText").html(`
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.85rem;">
                        <span>Total Days in Month:</span>
                        <span class="fw-bold">${totalDays}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.85rem;">
                        <span>Calculated Present (P + H*0.5):</span>
                        <span class="fw-bold">${p} + (${h} * 0.5) = ${calcPresent}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.85rem;">
                        <span>Paid Days (Present + Leave + Hol + WO - LateDed):</span>
                        <span class="fw-bold">${calcPresent} + ${l} + ${hol} + ${w}${lateDedText} = ${rawPayableAfterLate}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-1 border-top fw-bold text-success" style="font-size: 0.9rem;">
                        <span>Final Net Payable:</span>
                        <span>min(${totalDays}, ${rawPayableAfterLate}) = ${netPayable} Days</span>
                    </div>
                `);
                
                $("#calcExtraDaysText").html(`
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.85rem;">
                        <span>Total Days in Month:</span>
                        <span class="fw-bold">${totalDays}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.85rem;">
                        <span>Off Days (WeekOff + Holiday):</span>
                        <span class="fw-bold">${w} + ${hol} = ${w + hol}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.85rem;">
                        <span>Standard Working Days:</span>
                        <span class="fw-bold">${totalDays} - ${w + hol} = ${workingDays}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-1 border-top fw-bold text-info" style="font-size: 0.9rem;">
                        <span>Extra Days Worked:</span>
                        <span>max(0, ${calcPresent} - ${workingDays}) = ${extraDays} Days</span>
                    </div>
                `);
                
                // Build daily list
                let rowsHtml = "";
                if (res.daily.length > 0) {
                    res.daily.forEach(function(day) {
                        let badgeClass = "bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle";
                        if (day.statusclass === "success") {
                            badgeClass = "bg-success bg-opacity-10 text-success border border-success-subtle";
                        } else if (day.statusclass === "info") {
                            badgeClass = "bg-info bg-opacity-10 text-info border border-info-subtle";
                        } else if (day.statusclass === "danger") {
                            badgeClass = "bg-danger bg-opacity-10 text-danger border border-danger-subtle";
                        } else if (day.statusclass === "warning") {
                            badgeClass = "bg-warning bg-opacity-10 text-warning border border-warning-subtle";
                        } else if (day.statusclass === "primary") {
                            badgeClass = "bg-primary bg-opacity-10 text-primary border border-primary-subtle";
                        }
                        
                        rowsHtml += `
                            <tr>
                                <td class="ps-3 fw-medium">${day.date}</td>
                                <td>${day.day}</td>
                                <td class="text-center">
                                    <span class="badge ${badgeClass} px-3 py-2 fs-12">${day.status}</span>
                                </td>
                                <td class="text-center fw-semibold text-dark">${day.intime}</td>
                                <td class="text-center fw-semibold text-dark">${day.outtime}</td>
                            </tr>
                        `;
                    });
                } else {
                    rowsHtml = '<tr><td colspan="5" class="text-center text-muted">No details found.</td></tr>';
                }
                $("#modalAttTbl tbody").html(rowsHtml);
            } else {
                $("#modalAttTbl tbody").html(`<tr><td colspan="5" class="text-center text-danger">${res.message || "Failed to load details"}</td></tr>`);
            }
        },
        error: function () {
            $("#modalAttTbl tbody").html('<tr><td colspan="5" class="text-center text-danger">Error loading attendance details.</td></tr>');
        }
    });
}

function showLateDaysModal(row) {
    if (typeof row === 'string') {
        try { row = JSON.parse(row); } catch (e) {}
    }
    let baseurl = $("body").data("routurl");
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");
    let yearmonth = $("#AttendanceYearMonth").val();

    if (!yearmonth) {
        alert("Please select a month first");
        return;
    }

    let empName = row.EmployeeName || (row.EmployeeID + "");
    $("#lateModalEmpName").text(row.EmployeeID + " - " + empName);
    let lateCount = parseInt(row.LateArrivals || 0);
    let lateDed = parseFloat(row.LateDeductionDays || 0);
    
    $("#lateModalSummaryText").html(`Total Late Days: <strong>${lateCount}</strong> | Rule: 3 Late Days = 1 Day Deduction`);
    $("#lateModalDeductedBadge").text(`${lateDed} Day${lateDed != 1 ? 's' : ''} Deducted`);
    $("#lateModalTbl tbody").html('<tr><td colspan="6" class="text-center text-muted"><div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div>Loading late attendance records...</td></tr>');

    $("#lateDaysDetailModal").modal("show");

    let form_data = new FormData();
    form_data.append("companyid", companyid);
    form_data.append("subcompanyid", subcompanyid);
    form_data.append("projectid", projectid);
    form_data.append("employeeid", row.EmployeeID);
    form_data.append("yearmonth", yearmonth);

    $.ajax({
        type: "POST",
        url: baseurl + "modal/hrm_salarymodal.php?action=getEmployeeDailyAttendanceDetail",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {
            if (res.status === "success" && Array.isArray(res.daily)) {
                let tbodyHtml = "";
                let sno = 0;
                res.daily.forEach(function (day) {
                    if (day.is_late) {
                        sno++;
                        tbodyHtml += `
                            <tr>
                                <td class="ps-3 fw-semibold text-secondary">${sno}</td>
                                <td class="fw-bold text-dark">${day.date_formatted || day.date}</td>
                                <td><span class="badge bg-light text-dark border">${day.day_name || day.day}</span></td>
                                <td class="text-center font-monospace">${day.scheduled_in || '-'}</td>
                                <td class="text-center font-monospace text-danger fw-bold">${day.in_time || day.intime || '-'}</td>
                                <td class="text-center"><span class="badge bg-danger-subtle text-danger border border-danger-subtle">${day.late_by || 'Late'}</span></td>
                            </tr>
                        `;
                    }
                });

                if (sno === 0) {
                    tbodyHtml = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="ti ti-circle-check text-success fs-3 d-block mb-1"></i>No late check-in records found.</td></tr>';
                }

                $("#lateModalTbl tbody").html(tbodyHtml);
            } else {
                $("#lateModalTbl tbody").html('<tr><td colspan="6" class="text-center text-danger">Failed to load late details.</td></tr>');
            }
        },
        error: function () {
            $("#lateModalTbl tbody").html('<tr><td colspan="6" class="text-center text-danger">Error loading data.</td></tr>');
        }
    });
}
