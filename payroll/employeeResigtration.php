<?php
if(isset($_GET['vw'])==1){

}else{
  require('header.php');
}
?>
<style>
.my-swal-popup {
  max-width: 90vw !important; /* responsive large width */
  width: 900px !important;     /* fixed width for desktop */
}

.my-swal-html {
  max-height: 70vh;            /* allow scrolling if content is long */
  overflow-y: auto;
  text-align: left;
}

.my-swal-html table {
  font-size: 14px;
  width: 100%;
}
</style>
<div class="container">
    <div id="uploadEmployeeDetails" class="d-none">
        <div class="row">
            <div class="col-md-12">
                <h4 class="text-center">Upload Employee Details in Bulk</h4>
                <button class="btn btn-sm btn-outline-primary float-end optiontoemployeeregisteration" id="gotoregisterform"><i class="ti ti-user"></i> Registration Form</button>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-header">
                <strong>Upload Employee Details</strong>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-8">
                        <label>Excel Sheet Upload<span class="d-none d-lg-inline"> – Employee Records</span></label><br/>
                        <input type="file" name="bulkempdetails" id="bulkempdetails" accept=".xls,.xlsx" required><br/>
                        <b><span class="d-none d-lg-inline">Do you want to download sample file</span> <a class="text-primary" href="<?php echo $rooturl; ?>samplefile/sample_bulkupload_employeedetailsy.xls" target="_blank">Download sample sheet</a></b>
                    </div>
                    <div class="col-4">
                        <button type="button" class="btn btn-primary float-end mt-2" id="bulkuploademployeedatabtn"><i class="fa fa-upload"></i> Upload Excel <span class="d-none d-lg-inline"> of Employee Details</span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="EmployeeRegistration" class="">
        <div class="row">
            <div class="col-md-12">
                <h4 class="text-center">Employee Registration</h4>
                <button class="btn btn-sm btn-outline-primary float-end optiontoemployeeregisteration" id="gotouploadexcel"><i class="ti ti-users"></i> Bulk Upload via Excel </button>
            </div>
        </div>
        <!-- Personal Information Section -->
        <div class="card mt-4" id="card1">
            <div class="card-header">
                <strong>Employee Personal Details</strong>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label for="TitleName" class="form-label">Title <sup><i class="fa fa-star text-danger small"></i></sup></label>
                        <select class="form-select" id="TitleName" name="TitleName">
                            <option value="Mr.">Mr.</option>
                            <option value="Ms.">Ms.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Miss">Miss</option>
                        </select>
                    </div>
                    <div class="col-md-10">
                        <label for="FirstName" class="form-label">First Name <sup><i class="fa fa-star text-danger small"></i></sup></label>
                        <input type="text" class="form-control no-space" id="FirstName" name="FirstName" required>
                    </div>
                    <div class="col-md-6">
                        <label for="MiddleName" class="form-label">Middle Name</label>
                        <input type="text" class="form-control no-space" id="MiddleName" name="MiddleName">
                    </div>
                    <div class="col-md-6">
                        <label for="LastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control no-space" id="LastName" name="LastName">
                    </div>
                    <div class="col-md-6">
                        <label for="DateOfBirth" class="form-label">Date Of Birth <sup><i class="fa fa-star text-danger small"></i></sup></label>
                        <input type="date" class="form-control" id="DateOfBirth" name="DateOfBirth" required>
                    </div>
                    <div class="col-md-6">
                        <label for="GenderName" class="form-label">Gender <sup><i class="fa fa-star text-danger small"></i></sup></label>
                        <select class="form-select select2" id="GenderName" name="GenderName"></select>
                    </div>

                    <!-- Hidden Fields (Use d-none for hiding) -->
                    <div class="col-md-6 d-none">
                        <label for="MaritalStatus" class="form-label">Marital Status</label>
                        <select class="form-select select2" id="MaritalStatus" name="MaritalStatus">
                            <option value="" selected disabled>Choose Marital Status</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-none">
                        <label for="SpouseName" class="form-label">Spouse Name</label>
                        <input type="text" class="form-control" id="SpouseName" name="SpouseName">
                    </div>
                    <div class="col-md-6 d-none">
                        <label for="FatherName" class="form-label">Father's Name</label>
                        <input type="text" class="form-control" id="FatherName" name="FatherName">
                    </div>
                    <div class="col-md-6 d-none">
                        <label for="MotherName" class="form-label">Mother's Name</label>
                        <input type="text" class="form-control" id="MotherName" name="MotherName">
                    </div>

                    <div class="col-md-6">
                        <label for="Nationality" class="form-label">Nationality</label>
                        <select class="form-select select2" id="Nationality" name="Nationality"></select>
                    </div>
                    <div class="col-md-6">
                        <label for="PassportNumber" class="form-label">Passport Number</label>
                        <input type="text" class="form-control" id="PassportNumber" name="PassportNumber">
                    </div>
                    <div class="col-md-6">
                        <label for="PanNumber" class="form-label">PAN Number</label>
                        <input type="text" class="form-control" id="PanNumber" name="PanNumber">
                    </div>
                    <div class="col-md-6">
                        <label for="AadharNumber" class="form-label">Aadhar Number</label>
                        <input type="text" class="form-control" id="AadharNumber" name="AadharNumber">
                    </div>
                    <!-- Documents -->
                    <div class="col-md-6">
                        <label for="PanDocument" class="form-label">Upload PAN Document</label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="PanDocument" name="PanDocument" accept="image/*;capture=camera">
                            <button class="btn btn-outline-secondary" type="button" onclick="showPreview_document(document.getElementById('PanDocument'), 'blob');">Show Preview</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="AadharDocument" class="form-label">Upload Aadhar Document</label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="AadharDocument" name="AadharDocument" accept="image/*;capture=camera">
                            <button class="btn btn-outline-secondary" type="button" onclick="showPreview_document(document.getElementById('AadharDocument'), 'blob');">Show Preview</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                         <!-- <sup><i class="fa fa-star text-danger small"></i> -->
                        <label for="ProfilePhoto" class="form-label">Upload Profile Photo</sup></label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="ProfilePhoto" name="ProfilePhoto" accept="image/*;capture=camera">
                            <button class="btn btn-outline-secondary" type="button" onclick="showPreview_document(document.getElementById('ProfilePhoto'), 'blob');">Show Preview</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                         <!-- <sup><i class="fa fa-star text-danger small"></i></sup> -->
                        <label for="Cv" class="form-label">Upload CV</label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="Cv" name="Cv" accept="image/*;capture=camera">
                            <button class="btn btn-outline-secondary" type="button" onclick="showPreview_document(document.getElementById('Cv'), 'blob');">Show Preview</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Details Section -->
        <div class="card mt-4" id="card2">
            <div class="card-header">
                <strong>Bank Details</strong>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="BankName" class="form-label">Bank Name <sup><i class="fa fa-star text-danger" style="font-size: 6px;"></i></sup></label>
                        <input type="text" class="form-control" id="BankName" name="BankName" required>
                    </div>
                    <div class="col-md-4">
                        <label for="AccountNumber" class="form-label">Account Number <sup><i class="fa fa-star text-danger" style="font-size: 6px;"></i></sup></label>
                        <input type="text" class="form-control" id="AccountNumber" name="AccountNumber" required>
                    </div>

                    <div class="col-md-4">
                        <label for="IFSCCode" class="form-label">IFSC Code <sup><i class="fa fa-star text-danger" style="font-size: 6px;"></i></sup></label>
                        <input type="text" class="form-control" id="IFSCCode" name="IFSCCode" required>
                    </div>
                    <div class="col-md-12">
                        <!-- <sup><i class="fa fa-star text-danger" style="font-size: 6px;"></i></sup> -->
                        <label for="CanceledCheque" class="form-label">Upload Canceled Cheque</label>
                        <div class="d-flex align-items-center gap-4">
                            <input type="file" class="form-control" id="CanceledCheque" name="CanceledCheque" accept="image/*; capture=camera" />
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="showPreview_document(document.getElementById('CanceledCheque'), 'blob');">Show Preview</button>
                        </div>
                    </div>

                    <div class="col-md-6 d-none">
                        <label for="SwiftCode" class="form-label">Swift Code</label>
                        <input type="text" class="form-control" id="SwiftCode" name="SwiftCode">
                    </div>
                    <div class="col-md-6 d-none">
                        <label for="IbanNumber" class="form-label">Iban Number</label>
                        <input type="text" class="form-control" id="IbanNumber" name="IbanNumber" required>
                    </div>

                    <div class="col-md-6 d-none">
                        <label for="SortCode" class="form-label">Sort Code</label>
                        <input type="text" class="form-control" id="SortCode" name="SortCode">
                    </div>
                    <div class="col-md-6 d-none">
                        <label for="CurrencyCode" class="form-label">Currency</label>
                        <select class="form-select select2" id="CurrencyCode" name="CurrencyCode"></select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Section -->
        <div class="card mt-4" id="card3">
            <div class="card-header">
                <strong>Contact Details & Address</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="MobileNumber">Mobile Number <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
                        <input type="text" class="form-control" id="MobileNumber" name="MobileNumber">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="EmailAddress">Email Address</label>
                        <input type="text" class="form-control" id="EmailAddress" name="EmailAddress">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="AddressLine1">Address Line 1 <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
                        <input type="text" class="form-control" id="AddressLine1" name="AddressLine1" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="AddressLine2">Address Line 2</label>
                        <input type="text" class="form-control" id="AddressLine2" name="AddressLine2">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="AddressLine3">Address Line 3</label>
                        <input type="text" class="form-control" id="AddressLine3" name="AddressLine3">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="AddressLine4">Address Line 4</label>
                        <input type="text" class="form-control" id="AddressLine4" name="AddressLine4">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="PostalCode">Postal Code <sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>
                        <input type="text" class="form-control" id="PostalCode" name="PostalCode">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="CountryName">Country</label>
                        <select class="form-control select2" id="CountryName" name="CountryName"></select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Person Details -->
        <div class="card mt-4" id="card4">
            <div class="card-header">
                <strong>Contact Person Details</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="ContactPersone1" class="form-label">Name of Contact Person 1</label>
                            <input type="text" class="form-control" id="ContactPersone1" name="ContactPersone1">
                        </div>
                        <div class="mb-3">
                            <label for="cp1Relationship" class="form-label">Relationship</label>
                            <select class="form-select select2" id="cp1Relationship" name="cp1Relationship"></select>
                        </div>
                        <div class="mb-3">
                            <label for="cp1MobileNumber" class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" id="cp1MobileNumber" name="cp1MobileNumber">
                        </div>
                        <div class="mb-3">
                            <label for="cp1EmailAddress" class="form-label">Email Address</label>
                            <input type="text" class="form-control" id="cp1EmailAddress" name="cp1EmailAddress">
                        </div>
                        <div class="mb-3">
                            <label for="cp1Address" class="form-label">Address</label>
                            <textarea class="form-control" id="cp1Address" name="cp1Address"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="cp1PostalCode" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="cp1PostalCode" name="cp1PostalCode">
                        </div>
                        <div class="mb-3">
                            <label for="cp1Note" class="form-label">Note</label>
                            <textarea class="form-control" id="cp1Note" name="cp1Note"></textarea>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input text-success" id="isPrimaryRadio1" name="isPrimaryRadio" value="cp1">
                            <label class="form-check-label" for="isPrimaryRadio1">Is This Primary Contact Details?</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="ContactPersone2" class="form-label">Name of Contact Person 2</label>
                            <input type="text" class="form-control" id="ContactPersone2" name="ContactPersone2">
                        </div>
                        <div class="mb-3">
                            <label for="cp2Relationship" class="form-label">Relationship</label>
                            <select class="form-select select2" id="cp2Relationship" name="cp2Relationship"></select>
                        </div>
                        <div class="mb-3">
                            <label for="cp2MobileNumber" class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" id="cp2MobileNumber" name="cp2MobileNumber">
                        </div>
                        <div class="mb-3">
                            <label for="cp2EmailAddress" class="form-label">Email Address</label>
                            <input type="text" class="form-control" id="cp2EmailAddress" name="cp2EmailAddress">
                        </div>
                        <div class="mb-3">
                            <label for="cp2Address" class="form-label">Address</label>
                            <textarea class="form-control" id="cp2Address" name="cp2Address"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="cp2PostalCode" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="cp2PostalCode" name="cp2PostalCode">
                        </div>
                        <div class="mb-3">
                            <label for="cp2Note" class="form-label">Note</label>
                            <textarea class="form-control" id="cp2Note" name="cp2Note"></textarea>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" id="isPrimaryRadio2" name="isPrimaryRadio" value="cp2">
                            <label class="form-check-label fw-large" for="isPrimaryRadio2">Is This Primary Contact Details?</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-center">
                        <button type="button" class="btn btn-primary mt-4" data-btnEmployeeID='' id="submitEmployeeRegistrationbtn">Submit</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4 mb-3">
            <div class="col-md-12">
                <!-- Navigation Buttons -->
                <button type="button" class="btn btn-sm btn-secondary float-start d-none" id="prevButton"
                        onclick="navigateCardWithContainerid('prev','prevButton','nextButton','EmployeeRegistration')">
                    <i class="fa fa-arrow-left"></i> Previous
                </button>

                <button type="button" class="btn btn-sm btn-primary float-end" id="nextButton"
                        onclick="navigateCardWithContainerid('next','prevButton','nextButton','EmployeeRegistration')">
                    Next <i class="fa fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>


<?php
if(isset($_GET['vw'])==1){

}else{
  require('footer.php'); 
}
?>
<script type="text/javascript">
// Initialize by showing the first card
// let currentCard = 0;
// let currentCard = 1;
$(document).on("click", ".optiontoemployeeregisteration", function () {
    // Remove d-none from both sections first
    $("#uploadEmployeeDetails").removeClass('d-none');
    $("#EmployeeRegistration").removeClass('d-none');

    // Toggle visibility based on clicked ID
    if ($(this).prop("id") == "gotoregisterform") {
        $("#uploadEmployeeDetails").addClass('d-none');
    } else if ($(this).prop("id") == "gotouploadexcel") {
        $("#EmployeeRegistration").addClass('d-none');
    }
});
navigateCardWithContainerid('next','prevButton','nextButton','EmployeeRegistration');
// gender list
genderlist_callback(function(res) {
    if (res && res.length > 0) {
        var gntlist = "";
        if (res.length > 1) {
            gntlist += "<option value='' selected disabled>Choose Gender</option>";
        }
        for (var i = 0; i < res.length; i++) {
            gntlist += "<option value='" + res[i].GenderName + "'>" + res[i].GenderName + "</option>";
        }
        if (gntlist) {
            $("#GenderName").append(gntlist);
        }
    } else {
        console.log("No genders found.");
    }
});

// Nationality
countrylist_callback('all', 'all', function(res) {
    $("#Nationality").empty();
    if (res && res.length > 0) {
        if (res.length > 1) {
            crlist += "<option value='' selected disabled>Choose Country Name</option>";
        }
        var crlist = "";
        for (var i = 0; i < res.length; i++) {
            var selected = res[i].countryid=="IN"?"selected":"";
            crlist += "<option value='" + res[i].countryid + "' "+selected+">" + 
                      res[i].countryname + "</option>";
        }
        // Append the options to the select element if crlist is not empty
        if (crlist) {
            $("#Nationality").append(crlist);
        }
    }else{
         $("#Nationality").append("<option value=''>No data available</option>");
    }
});

// CountryName list
countrylist_callback('all', 'all', function(res) {
    // console.log(res);
    $("#CountryName").empty();
    if (res && res.length > 0) {
        if (res.length > 1) {
            crlist += "<option value='' selected disabled>Choose Country Name</option>";
        }
        var crlist = "";
        for (var i = 0; i < res.length; i++) {
            var selected = res[i].countryid=="IN"?"selected":"";
            crlist += "<option value='" + res[i].countryid + "' "+selected+">" + 
                      res[i].countryname + "</option>";
        }
        // Append the options to the select element if crlist is not empty
        if (crlist) {
            $("#CountryName").append(crlist);
        }
    }else{
         $("#CountryName").append("<option value=''>No data available</option>");
    }
});
// Currency list
courrencylist_callback('all', 'all', function(res) {
    if (res && res.length > 0) {
        if (res.length > 1) {
            crlist += "<option value='' selected disabled>Choose Currency Name</option>";
        }
        var crlist = "";
        for (var i = 0; i < res.length; i++) {
            var selected = res[i].CurrencyCode=="INR"?"selected":"";
            crlist += "<option value='" + res[i].CurrencyCode + "' " +
                      "data-currencyname='" + res[i].CurrencyName + "' " +
                      "data-currencycode='" + res[i].CurrencyCode + "' " +
                      "data-currencysymbol='" + res[i].CurrencySymbol + "' "+selected+">" + 
                      res[i].CurrencyName + "</option>";
        }
        // Append the options to the select element if crlist is not empty
        if (crlist) {
            $("#CurrencyCode").append(crlist);
        }
    } else {
        $("#CurrencyCode").append("<option value='' disabled>No data available</option>");
    }
});

// Relationship
relationshiplist_callback('all', 'all', function(res) {
    var crplist="";
    if (res && res.length > 0) {
        if (res.length > 1) {
            crplist += "<option value='' selected disabled>Choose Relationship</option>";
        }
        for (var i = 0; i < res.length; i++) {
            crplist += "<option value='" + res[i].RelationshipId + "'>" + res[i].RelationshipName + "</option>";
        }
        // Append the options to the select element if crlist is not empty
        if (crplist) {
            $("#cp1Relationship").append(crplist);
            $("#cp2Relationship").append(crplist);
        }
    } else {
        $("#cp1Relationship").append("<option value='' disabled>No data available</option>");
        $("#cp2Relationship").append("<option value='' disabled>No data available</option>");
    }
});
</script>