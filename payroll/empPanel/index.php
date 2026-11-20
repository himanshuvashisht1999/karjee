<?php
if (isset($_GET['vw']) == 1) {

} else {
    require('header.php');
}
$empid = "";
if (isset($_GET['empid'])) {
    $empid = $_GET['empid'];
}
?>
<style>
    .name-action-icon {
        font-size: 1.1rem;
        transition: transform 0.2s, opacity 0.2s;
        opacity: 0.7;
        cursor: pointer;
        display: inline-block;
        vertical-align: middle;
    }
    .name-action-icon:hover {
        transform: scale(1.15);
        opacity: 1;
    }
    /* Light Color Card Headers */
    .hrm-card .card-header {
        background: #f0fdf4 !important;
        border-bottom: 1px solid #dcfce7 !important;
        color: #166534 !important;
        padding: 12px 18px !important;
        border-top-left-radius: 12px !important;
        border-top-right-radius: 12px !important;
    }
    .hrm-card .card-header h4,
    .hrm-card .card-header h5 {
        color: #166534 !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        margin: 0 !important;
    }

    .collapsable-header {
        cursor: pointer;
        user-select: none;
        transition: background-color 0.2s ease, opacity 0.2s ease;
    }
    .collapsable-header:hover {
        opacity: 0.92;
    }
    .collapse-toggle-btn {
        background: #dcfce7 !important;
        color: #166534 !important;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        transition: transform 0.2s, background 0.2s;
    }
    .collapsable-header:hover .collapse-toggle-btn {
        background: #bbf7d0 !important;
        transform: scale(1.1);
    }

    /* Redesigned Edit Employee Modal Styles */
    #editEmployeeModal .modal-content {
        border: none !important;
        border-radius: 16px !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        background-color: #ffffff;
    }
    #editEmployeeModal .modal-header {
        border-bottom: none !important;
        padding: 24px 28px 12px 28px !important;
    }
    #editEmployeeModal .modal-title {
        font-weight: 700 !important;
        color: #1e293b !important;
        font-size: 1.25rem !important;
    }
    #editEmployeeModal .modal-body {
        padding: 12px 28px 24px 28px !important;
    }
    #editEmployeeModal .modal-footer {
        border-top: none !important;
        padding: 12px 28px 24px 28px !important;
        gap: 12px;
    }

    /* Light Color Tabs */
    .edit-emp-tabs {
        border-bottom: none !important;
        gap: 6px;
        margin-bottom: 24px;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
        display: inline-flex;
        border: 1px solid #e2e8f0;
    }
    .edit-emp-tabs .nav-link {
        border: 1px solid transparent !important;
        border-radius: 8px !important;
        padding: 8px 18px !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        color: #64748b !important;
        background: transparent !important;
        transition: all 0.2s ease;
    }
    .edit-emp-tabs .nav-link:hover {
        color: #0f172a !important;
    }
    .edit-emp-tabs .nav-link.active {
        color: #0f172a !important;
        background: #ffffff !important;
        border-color: transparent !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        font-weight: 700 !important;
    }

    .custom-emp-label {
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #334155 !important;
        margin-bottom: 6px !important;
    }
    .custom-emp-input {
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        padding: 10px 14px !important;
        font-size: 14px !important;
        color: #1e293b !important;
        background-color: #ffffff !important;
        box-shadow: none !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }
    .custom-emp-input:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    }

    .btn-edit-emp-close {
        background-color: #3f6e78 !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 10px 28px !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        transition: background-color 0.2s !important;
    }
    .btn-edit-emp-close:hover {
        background-color: #2f565e !important;
        color: #ffffff !important;
    }

    .btn-edit-emp-save {
        background-color: #f97316 !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 10px 28px !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        transition: background-color 0.2s !important;
    }
    .btn-edit-emp-save:hover {
        background-color: #ea580c !important;
        color: #ffffff !important;
    }
</style>
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Profile Summary Section -->
        <div class="col-lg-4 mb-4">
            <div class="card hrm-card h-100">
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center justify-content-center text-center">
                        <div class="position-relative mb-3 d-inline-block">
                            <img src="../assets/img/profiles/profile.jpg" alt="Profile Picture" id="EmpProfilePic"
                                class="profile-pic border border-4 border-white shadow rounded-circle" style="width: 110px; height: 110px; object-fit: cover;">
                            <input type="file" id="directProfilePhotoInput" accept="image/*" style="display: none;" onchange="uploadDirectProfilePhoto(this)">
                            <?php if (intval($loginroleid) <= 3) { ?>
                            <button type="button" class="btn btn-primary btn-sm rounded-circle position-absolute bottom-0 end-0 p-2 shadow" id="profileCameraUploadBtn"
                                title="Upload Profile Photo" onclick="document.getElementById('directProfilePhotoInput').click()"
                                style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; background: #f97316; border: 2px solid #ffffff;">
                                <i class="fa fa-camera text-white"></i>
                            </button>
                            <?php } ?>
                        </div>
                        <h4 class="mb-1 fw-bold text-dark" id="pr_empname"></h4>
                        <p class="text-muted mb-3 badge bg-light text-dark border" id="pr_empdesignation"></p>

                        <?php if ($isResigned) { ?>
                        <div class="d-flex flex-column align-items-center mt-2 mb-4">
                            <span class="badge bg-danger fs-14 px-3 py-2 rounded-pill shadow-sm">
                                <i class="fa fa-user-times me-1"></i> Resigned <?php echo (!empty($empData[0]['DateOfResignation']) && $empData[0]['DateOfResignation'] !== '0000-00-00') ? ' (' . date('d/m/Y', strtotime($empData[0]['DateOfResignation'])) . ')' : ''; ?>
                            </span>
                            <?php if (!empty($empData[0]['remark'])) { ?>
                                <div class="alert alert-danger py-1 px-3 mt-2 mb-0 small text-center" style="max-width: 320px; font-size: 12px; line-height: 1.3;">
                                    <strong>Remark:</strong> <?php echo htmlspecialchars($empData[0]['remark']); ?>
                                </div>
                            <?php } ?>
                        </div>
                        <?php } else { ?>
                        <div class="d-flex gap-2 mt-2 mb-4 flex-wrap justify-content-center">
                            <!-- <button type="button" class="btn btn-sm btn-outline-primary hrm-action-btn" onclick="document.getElementById('directProfilePhotoInput').click()">
                                <i class="fa fa-upload me-1"></i> Upload Photo
                            </button> -->
                            <a href="markattendance.php?k=<?php echo $key; ?>&empid=<?php echo $empid; ?>&accessby=<?php echo $accessby; ?>"
                               class="btn btn-sm btn-success hrm-action-btn"><i
                                    class="fa fa-calendar-check-o me-1"></i> Mark Attendance</a>
                        </div>
                        <?php } ?>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3 collapsable-header">
                            <h6 class="text-uppercase text-muted fw-bold fs-12 mb-0">Basic Information</h6>
                            <span class="text-muted"><i class="fa fa-minus collapse-icon" style="font-size: 0.75rem;"></i></span>
                        </div>
                        <div id="basicinformationdiv" class="px-2">
                            <div class="text-center text-muted py-2">Loading...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Information Section -->
        <div class="col-lg-8">
            <!-- Personal Information -->
            <div class="card hrm-card mb-4">
                <div class="card-header collapsable-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="fa fa-id-card me-2"></i>Personal Information</h5>
                    <span class="collapse-toggle-btn"><i class="fa fa-minus collapse-icon"></i></span>
                </div>
                <div class="card-body">
                    <div class="row" id="personalinformationdiv">
                        <div class="col-12 text-center text-muted py-3">Loading...</div>
                    </div>
                </div>
            </div>

            <!-- Bank Information -->
            <div class="card hrm-card mb-4">
                <div class="card-header collapsable-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="fa fa-university me-2"></i>Bank Information</h5>
                    <span class="collapse-toggle-btn"><i class="fa fa-minus collapse-icon"></i></span>
                </div>
                <div class="card-body">
                    <div class="row" id="bankinformationdiv">
                        <div class="col-12 text-center text-muted py-3">Loading...</div>
                    </div>
                </div>
            </div>

            <!-- Salary Information -->
            <div class="card hrm-card mb-4" id="salarydetailsdiv" style="display:none">
                <div class="card-header collapsable-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="fa fa-money me-2"></i>Salary Information</h5>
                    <span class="collapse-toggle-btn"><i class="fa fa-minus collapse-icon"></i></span>
                </div>
                <div class="card-body">
                    <div class="row" id="salaryinformationdiv"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEmployeeModalLabel">Edit Employee Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editEmployeeForm">
                    <!-- Hidden Fields -->
                    <input type="hidden" name="companyid" id="edit_companyid">
                    <input type="hidden" name="subcompanyid" id="edit_subcompanyid">
                    <input type="hidden" name="projectid" id="edit_projectid">
                    <input type="hidden" name="EmployeeID" id="edit_EmployeeID">

                    <ul class="nav nav-tabs edit-emp-tabs flex-wrap" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="personal-tab" data-bs-toggle="tab"
                                data-bs-target="#personal-tab-pane" type="button" role="tab">Personal Information</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank-tab-pane"
                                type="button" role="tab">Bank Details</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contact-address-tab" data-bs-toggle="tab" data-bs-target="#contact-address-tab-pane"
                                type="button" role="tab">Contact Details & Address</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="contact-person-tab" data-bs-toggle="tab" data-bs-target="#contact-person-tab-pane"
                                type="button" role="tab">Contact Person Details</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <!-- Personal Info -->
                        <div class="tab-pane fade show active" id="personal-tab-pane" role="tabpanel">
                            <h6 class="fw-bold text-dark mb-3">Employee Personal Details</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label custom-emp-label">Title <span class="text-danger">*</span></label>
                                    <select class="form-select custom-emp-input" name="TitleName" id="edit_TitleName" onchange="toggleSpouseField()">
                                        <option value="Mr.">Mr.</option>
                                        <option value="Ms.">Ms.</option>
                                        <option value="Mrs.">Mrs.</option>
                                    </select>
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label custom-emp-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control custom-emp-input" name="FirstName" id="edit_FirstName">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Middle Name</label>
                                    <input type="text" class="form-control custom-emp-input" name="MiddleName" id="edit_MiddleName">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Last Name</label>
                                    <input type="text" class="form-control custom-emp-input" name="LastName" id="edit_LastName">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Father Name</label>
                                    <input type="text" class="form-control custom-emp-input" name="FatherName" id="edit_FatherName" placeholder="Father's full name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Mother Name</label>
                                    <input type="text" class="form-control custom-emp-input" name="MotherName" id="edit_MotherName" placeholder="Mother's full name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Date Of Birth <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control custom-emp-input" name="DateOfBirth" id="edit_DateOfBirth">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Gender <span class="text-danger">*</span></label>
                                    <select class="form-select custom-emp-input" name="GenderName" id="edit_GenderName" onchange="toggleSpouseField()"></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Marital Status</label>
                                    <select class="form-select custom-emp-input" name="MaritalStatus" id="edit_MaritalStatus" onchange="toggleSpouseField()">
                                        <option value="">Select</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="spouseNameContainer" style="display: none;">
                                    <label class="form-label custom-emp-label" id="spouseNameLabel">Husband / Wife Name</label>
                                    <input type="text" class="form-control custom-emp-input" name="SpouseName" id="edit_SpouseName" placeholder="Enter name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Nationality</label>
                                    <select class="form-select custom-emp-input" name="Nationality" id="edit_Nationality"></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Passport Number</label>
                                    <input type="text" class="form-control custom-emp-input" name="PassportNumber" id="edit_PassportNumber">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">PAN Number</label>
                                    <input type="text" class="form-control custom-emp-input" name="PanNumber" id="edit_PanNumber">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Aadhar Number</label>
                                    <input type="text" class="form-control custom-emp-input" name="AadharNumber" id="edit_AadharNumber">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label d-flex justify-content-between align-items-center">
                                        <span>Upload PAN Document</span>
                                        <span id="old_PanDocument_badge"></span>
                                    </label>
                                    <div class="input-group">
                                        <input type="file" class="form-control custom-emp-input" name="PanDocument" id="edit_PanDocument">
                                        <button class="btn btn-preview-doc" type="button" onclick="previewDoc('PanDocument')">Show Preview</button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label d-flex justify-content-between align-items-center">
                                        <span>Upload Aadhar Document</span>
                                        <span id="old_AadharDocument_badge"></span>
                                    </label>
                                    <div class="input-group">
                                        <input type="file" class="form-control custom-emp-input" name="AadharDocument" id="edit_AadharDocument">
                                        <button class="btn btn-preview-doc" type="button" onclick="previewDoc('AadharDocument')">Show Preview</button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label d-flex justify-content-between align-items-center">
                                        <span>Upload Profile Photo</span>
                                        <span id="old_ProfilePhoto_badge"></span>
                                    </label>
                                    <div class="input-group">
                                        <input type="file" class="form-control custom-emp-input" name="ProfilePhoto" id="edit_ProfilePhoto" accept="image/*">
                                        <button class="btn btn-preview-doc" type="button" onclick="previewDoc('ProfilePhoto')">Show Preview</button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label d-flex justify-content-between align-items-center">
                                        <span>Upload CV</span>
                                        <span id="old_CVFile_badge"></span>
                                    </label>
                                    <div class="input-group">
                                        <input type="file" class="form-control custom-emp-input" name="CvDocument" id="edit_CvDocument">
                                        <button class="btn btn-preview-doc" type="button" onclick="previewDoc('CVFile')">Show Preview</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Info -->
                        <div class="tab-pane fade" id="bank-tab-pane" role="tabpanel">
                            <h6 class="fw-bold text-dark mb-3">Bank Details</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label custom-emp-label">Bank Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control custom-emp-input" name="BankName" id="edit_BankName">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label custom-emp-label">Account Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control custom-emp-input" name="AccountNumber" id="edit_AccountNumber">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label custom-emp-label">IFSC Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control custom-emp-input" name="IFSCCode" id="edit_IFSCCode">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label custom-emp-label d-flex justify-content-between align-items-center">
                                        <span>Upload Canceled Cheque</span>
                                        <span id="old_CanceledCheque_badge"></span>
                                    </label>
                                    <div class="input-group">
                                        <input type="file" class="form-control custom-emp-input" name="CanceledCheque" id="edit_CanceledCheque">
                                        <button class="btn btn-preview-doc" type="button" onclick="previewDoc('CanceledCheque')">Show Preview</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Details & Address -->
                        <div class="tab-pane fade" id="contact-address-tab-pane" role="tabpanel">
                            <h6 class="fw-bold text-dark mb-3">Contact Details & Address</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Mobile Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control custom-emp-input" name="MobileNumber" id="edit_MobileNumber">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Email Address</label>
                                    <input type="text" class="form-control custom-emp-input" name="EmailAddress" id="edit_EmailAddress">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Address Line 1 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control custom-emp-input" name="AddressLine1" id="edit_AddressLine1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Address Line 2</label>
                                    <input type="text" class="form-control custom-emp-input" name="AddressLine2" id="edit_AddressLine2">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Address Line 3</label>
                                    <input type="text" class="form-control custom-emp-input" name="AddressLine3" id="edit_AddressLine3">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Address Line 4</label>
                                    <input type="text" class="form-control custom-emp-input" name="AddressLine4" id="edit_AddressLine4">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Postal Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control custom-emp-input" name="PostalCode" id="edit_PostalCode">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label custom-emp-label">Country</label>
                                    <select class="form-select custom-emp-input" name="CountryName" id="edit_CountryName"></select>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Person Details -->
                        <div class="tab-pane fade" id="contact-person-tab-pane" role="tabpanel">
                            <h6 class="fw-bold text-dark mb-3">Contact Person Details</h6>
                            <div class="row g-4">
                                <!-- Contact Person 1 -->
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 bg-light-subtle">
                                        <h6 class="fw-bold text-primary mb-3">Contact Person 1</h6>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Name of Contact Person 1</label>
                                                <input type="text" class="form-control custom-emp-input" name="ContactPerson1_Name" id="edit_ContactPerson1_Name">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Relationship</label>
                                                <select class="form-select custom-emp-input" name="ContactPerson1_Relation" id="edit_ContactPerson1_Relation">
                                                    <option value="">Choose Relationship</option>
                                                    <option value="Spouse">Spouse</option>
                                                    <option value="Father">Father</option>
                                                    <option value="Mother">Mother</option>
                                                    <option value="Brother">Brother</option>
                                                    <option value="Sister">Sister</option>
                                                    <option value="Friend">Friend</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Mobile Number</label>
                                                <input type="text" class="form-control custom-emp-input" name="ContactPerson1_Mobile" id="edit_ContactPerson1_Mobile">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Email Address</label>
                                                <input type="text" class="form-control custom-emp-input" name="ContactPerson1_Email" id="edit_ContactPerson1_Email">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Address</label>
                                                <input type="text" class="form-control custom-emp-input" name="ContactPerson1_Address" id="edit_ContactPerson1_Address">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Postal Code</label>
                                                <input type="text" class="form-control custom-emp-input" name="ContactPerson1_PostalCode" id="edit_ContactPerson1_PostalCode">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Note</label>
                                                <input type="text" class="form-control custom-emp-input" name="ContactPerson1_Note" id="edit_ContactPerson1_Note">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Contact Person 2 -->
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 bg-light-subtle">
                                        <h6 class="fw-bold text-primary mb-3">Contact Person 2</h6>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Name of Contact Person 2</label>
                                                <input type="text" class="form-control custom-emp-input" name="ContactPerson2_Name" id="edit_ContactPerson2_Name">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Relationship</label>
                                                <select class="form-select custom-emp-input" name="ContactPerson2_Relation" id="edit_ContactPerson2_Relation">
                                                    <option value="">Choose Relationship</option>
                                                    <option value="Spouse">Spouse</option>
                                                    <option value="Father">Father</option>
                                                    <option value="Mother">Mother</option>
                                                    <option value="Brother">Brother</option>
                                                    <option value="Sister">Sister</option>
                                                    <option value="Friend">Friend</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Mobile Number</label>
                                                <input type="text" class="form-control custom-emp-input" name="ContactPerson2_Mobile" id="edit_ContactPerson2_Mobile">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Email Address</label>
                                                <input type="text" class="form-control custom-emp-input" name="ContactPerson2_Email" id="edit_ContactPerson2_Email">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Address</label>
                                                <input type="text" class="form-control custom-emp-input" name="ContactPerson2_Address" id="edit_ContactPerson2_Address">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Postal Code</label>
                                                <input type="text" class="form-control custom-emp-input" name="ContactPerson2_PostalCode" id="edit_ContactPerson2_PostalCode">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label custom-emp-label">Note</label>
                                                <input type="text" class="form-control custom-emp-input" name="ContactPerson2_Note" id="edit_ContactPerson2_Note">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-edit-emp-close" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-edit-emp-save" onclick="updatedetailsByemployee()">Save Changes</button>
            </div>
        </div>
    </div>
</div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel"><i class="fa fa-key me-2"></i>Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="resetPasswordForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" placeholder="Confirm new password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPasswordBtn" onclick="toggleConfirmPasswordVisibility()">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning text-white" onclick="submitResetPassword()">Update Password</button>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_GET['vw']) == 1) {

} else {
    require('footer.php');
}
?>

<script type="text/javascript">
    var EmpId = "<?php echo $empid; ?>";
    var currectyear = "<?php echo date('Y'); ?>";

    // Helper function for Sidebar Info Items (Compact List)
    function createSidebarInfoItem(label, value, icon) {
        return `
            <div class="d-flex align-items-center border-bottom py-2">
                <div class="flex-shrink-0 me-3 text-center" style="width: 20px;">
                    <i class="fa ${icon} text-primary"></i>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <span class="text-muted d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">${label}</span>
                    <span class="text-dark fw-medium text-break" style="font-size: 0.9rem;">${value || 'N/A'}</span>
                </div>
            </div>
        `;
    }

    // Helper function for Main Content Grid Items (Cards)
    function createInfoItem(label, value, icon) {
        return `
            <div class="col-md-6 mb-3">
                <div class="d-flex align-items-center p-3 rounded border bg-light-subtle h-100">
                    <div class="flex-shrink-0 me-3">
                        <div class="avatar-sm bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center">
                            <i class="fa ${icon} text-primary fs-5"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">${label}</small>
                        <span class="fw-medium text-dark">${value || 'N/A'}</span>
                    </div>
                </div>
            </div>
        `;
    }

    fetchEmployeeDatawithOtherifno_callback('EmployeeID', EmpId, function (res) {
        var basichtml = "";
        var personalhtml = "";
        if (res && res.length > 0) {
            let emp = res[0];
            let loginroleid = parseInt($("body").data("loginroleid")) || 0;
            let editIcon = "";
            if (loginroleid <= 3) {
                editIcon = `<a href="javascript:void(0)" onclick="openEditEmployeeModal('${emp.EmployeeID}')" class="text-primary ms-2 name-action-icon" title="Edit Profile"><i class="fa fa-pencil"></i></a>`;
                $("#profileCameraUploadBtn").removeClass("d-none").show();
            } else {
                $("#profileCameraUploadBtn").addClass("d-none").hide();
            }
            let resetIcon = `<a href="javascript:void(0)" onclick="openResetPasswordModal()" class="text-warning ms-2 name-action-icon" title="Reset Password"><i class="fa fa-key"></i></a>`;
            $("#pr_empname").html(
                emp.TitleName + " " + emp.FirstName + " " + emp.MiddleName + " " + emp.LastName + 
                " <span style='color: #2563eb; font-size: 0.75em; font-weight: 600; margin-left: 6px;'>(" + emp.EmployeeID + ")</span>" +
                editIcon + resetIcon
            );
            $("#pr_empdesignation").html(emp.DesignationName);

            // Update profile pic if available
            let photoToShow = emp.empPhotopath || emp.ProfilePhoto;
            let fullPhotoUrl = resolveDocUrl(photoToShow);
            if (fullPhotoUrl) {
                $("#EmpProfilePic").attr("src", fullPhotoUrl);
            } else {
                $("#EmpProfilePic").attr("src", "../assets/img/profiles/profile.jpg");
            }

            // Basic Info Construction (Sidebar)
            basichtml += createSidebarInfoItem("Full Name", emp.TitleName + " " + emp.FirstName + " " + emp.MiddleName + " " + emp.LastName, "fa-user");
            basichtml += createSidebarInfoItem("Email Address", emp.Email, "fa-envelope");
            basichtml += createSidebarInfoItem("Mobile Number", emp.MobileNo, "fa-mobile");
            basichtml += createSidebarInfoItem("Gender", emp.Gender, "fa-venus-mars");
            basichtml += createSidebarInfoItem("Date of Joining", emp.DateOfJoining !== "" ? changeDateFormatYmd_dMY(emp.DateOfJoining) : "N/A", "fa-calendar-check-o");
            basichtml += createSidebarInfoItem("Department", emp.DepartmentName, "fa-building");
            basichtml += createSidebarInfoItem("Work Location", emp.WorkLocation, "fa-map-marker");
            if (String(emp.Status).toLowerCase() === 'resigned' || (emp.DateOfResignation && emp.DateOfResignation !== '' && emp.DateOfResignation !== '0000-00-00')) {
                basichtml += createSidebarInfoItem("Status", `<span class="badge bg-danger">Resigned</span>`, "fa-info-circle");
                if (emp.DateOfResignation && emp.DateOfResignation !== '0000-00-00') {
                    basichtml += createSidebarInfoItem("Resignation Date", changeDateFormatYmd_dMY(emp.DateOfResignation), "fa-calendar-times-o");
                }
                if (emp.remark) {
                    basichtml += createSidebarInfoItem("Resignation Remark", emp.remark, "fa-commenting-o");
                }
            }

            // Personal Info Construction (Main Area)
            personalhtml += createInfoItem("Date of Birth", emp.DateOfBirth != "" ? changeDateFormatYmd_dMY(emp.DateOfBirth) : 'N/A', "fa-birthday-cake");
            personalhtml += createInfoItem("Father's Name", emp.FatherName, "fa-male");
            personalhtml += createInfoItem("PAN Number", emp.PanNumber, "fa-id-card");
            personalhtml += createInfoItem("Aadhar Number", emp.AadharNumber, "fa-id-card-o");
            personalhtml += createInfoItem("Marital Status", emp.MaritalStatus || 'N/A', "fa-heart-o");

            let maritalStatus = (emp.MaritalStatus || '').trim().toLowerCase();
            let spouseName = (emp.SpouseName || '').trim();
            if (maritalStatus === 'married' && spouseName !== '' && spouseName.toUpperCase() !== 'N/A') {
                let gender = (emp.Gender || '').trim().toLowerCase();
                let title = (emp.TitleName || '').trim().toLowerCase();
                let spouseLabel = "Husband / Wife Name";
                let spouseIcon = "fa-heart";

                if (gender === 'female' || title === 'ms.' || title === 'mrs.') {
                    spouseLabel = "Husband Name";
                    spouseIcon = "fa-male";
                } else if (gender === 'male' || title === 'mr.') {
                    spouseLabel = "Wife Name";
                    spouseIcon = "fa-female";
                }
                personalhtml += createInfoItem(spouseLabel, spouseName, spouseIcon);
            }

            personalhtml += createInfoItem("Address", emp.AddressLine1, "fa-home");

            $("#basicinformationdiv").html(basichtml);
            $("#personalinformationdiv").html(personalhtml);
        } else {
            $("#basicinformationdiv").html('<div class="text-center text-muted">No data found</div>');
            $("#personalinformationdiv").html('<div class="col-12 text-center text-muted">No data found</div>');
        }
    });

    fetchEmployeeBankDetails_callback(EmpId, function (res) {
        var empBankhtml = "";
        if (res && res.length > 0) {
            let b = res[0];
            if (b.BankName) empBankhtml += createInfoItem("Bank Name", b.BankName, "fa-university");
            if (b.AccountNumber) empBankhtml += createInfoItem("Account Number", b.AccountNumber, "fa-credit-card");
            if (b.IFSCCode) empBankhtml += createInfoItem("IFSC Code", b.IFSCCode, "fa-key");
            if (b.SWIFTCode) empBankhtml += createInfoItem("SWIFT Code", b.SWIFTCode, "fa-globe");
            if (b.IBAN) empBankhtml += createInfoItem("IBAN", b.IBAN, "fa-globe");
            if (b.SortCode) empBankhtml += createInfoItem("Sort Code", b.SortCode, "fa-sort-numeric-asc");
            $("#bankinformationdiv").html(empBankhtml || '<div class="col-12 text-center text-muted">No bank details found</div>');
        } else {
            $("#bankinformationdiv").html('<div class="col-12 text-center text-muted">No bank details found</div>');
        }
    });

    fetchEmployeeSalaryFromMaster_callback(EmpId, function (res) {
        var empSalaryhtml = "";
        if (res && res.length > 0) {
            let s = res[0];
            let loginroleid = parseInt($("body").data("loginroleid")) || 0;
            let netSalaryVal = (s.NetSalary && parseFloat(s.NetSalary) > 0) ? s.NetSalary : (s.GrossSalary || "0.00");

            if (loginroleid <= 2) {
                $("#salarydetailsdiv").show();
                // Full salary breakup for Admin/Management (loginroleid <= 2)
                // if (s.CTC) empSalaryhtml += createInfoItem("CTC", s.CTC, "fa-money");
                if (s.GrossSalary) empSalaryhtml += createInfoItem("Gross Salary", s.GrossSalary, "fa-money");
                if (s.BasicSalary) empSalaryhtml += createInfoItem("Basic Salary", s.BasicSalary, "fa-money");
                if (s.HRA) empSalaryhtml += createInfoItem("HRA", s.HRA, "fa-home");
                if (s.ConveyanceAllowance) empSalaryhtml += createInfoItem("Conveyance Allowance", s.ConveyanceAllowance, "fa-car");
                if (s.SpecialAllowance) empSalaryhtml += createInfoItem("Special Allowance", s.SpecialAllowance, "fa-plus-circle");
                if (s.Allowances) empSalaryhtml += createInfoItem("Allowances", s.Allowances, "fa-plus-circle");
                if (s.Deductions) empSalaryhtml += createInfoItem("Deductions", s.Deductions, "fa-minus-circle");
                empSalaryhtml += createInfoItem("Net Salary", netSalaryVal, "fa-credit-card");
            } else {
                $("#salarydetailsdiv").hide();
                // Only Net Salary for Employee
                // empSalaryhtml += createInfoItem("Net Salary", netSalaryVal, "fa-credit-card");
            }
            $("#salaryinformationdiv").html(empSalaryhtml);
        } else {
            $("#salaryinformationdiv").html('<div class="col-12 text-center text-muted">No salary information available</div>');
        }
    });

    // Collapsable Card Header Click Handler
    $(document).on('click', '.collapsable-header', function () {
        var targetContent = $(this).next('.card-body, #basicinformationdiv');
        var icon = $(this).find('.collapse-icon');
        targetContent.slideToggle(200, function () {
            if ($(this).is(':visible')) {
                icon.removeClass('fa-plus').addClass('fa-minus');
            } else {
                icon.removeClass('fa-minus').addClass('fa-plus');
            }
        });
    });

    // --- Edit Profile JS ---

    function populateEditSelects(EmpId) {
        let routurl = $("body").data("routurl");
        // Gender
        $.post(routurl + "modal/hrm_modal.php?action=genderlist_callback", {}, function (res) {
            let opts = "";
            if (res && res.length > 0) {
                res.forEach(item => {
                    opts += `<option value="${item.GenderName}">${item.GenderName}</option>`;
                });
                $('#edit_GenderName').html(opts);
            }
        }, "json");
        // Country (Used for Nationality and CountryName)
        $.post(routurl + "modal/hrm_modal.php?action=countrylist_callback", { sortby: 'all', sortbyvalue: 'all' }, function (res) {
            let opts = "";
            if (res && res.length > 0) {
                res.forEach(item => {
                    let sel = item.countryid === "IN" ? "selected" : "";
                    opts += `<option value="${item.countryname}" ${sel}>${item.countryname}</option>`;
                });
                $('#edit_Nationality').html(opts);
                $('#edit_CountryName').html(opts);
            }
        }, "json");
        // Currency
        $.post(routurl + "modal/hrm_modal.php?action=courrencylist_callback", { shortby: 'all', shortbyvalue: 'all' }, function (res) {
            let opts = "";
            if (res && res.length > 0) {
                res.forEach(item => {
                    let sel = item.CurrencyCode === "INR" ? "selected" : "";
                    opts += `<option value="${item.CurrencyCode}" ${sel}>${item.CurrencyName} (${item.CurrencyCode})</option>`;
                });
                $('#edit_CurrencyCode').html(opts);
            }
        }, "json");
    }

    function toggleSpouseField() {
        let maritalStatus = ($('#edit_MaritalStatus').val() || '').trim().toLowerCase();
        let gender = ($('#edit_GenderName').val() || '').trim().toLowerCase();
        let title = ($('#edit_TitleName').val() || '').trim().toLowerCase();

        if (maritalStatus === 'married') {
            $('#spouseNameContainer').show();
            let label = 'Spouse Name';
            let placeholder = 'Enter spouse name';

            if (gender === 'female' || title === 'ms.' || title === 'mrs.') {
                label = 'Husband Name';
                placeholder = 'Enter husband name';
            } else if (gender === 'male' || title === 'mr.') {
                label = 'Wife Name';
                placeholder = 'Enter wife name';
            }

            $('#spouseNameLabel').text(label);
            $('#edit_SpouseName').attr('placeholder', placeholder);
        } else {
            $('#spouseNameContainer').hide();
        }
    }

    var currentEmpDocs = {};

    function resolveDocUrl(path) {
        if (!path || typeof path !== 'string') return '';
        var cleanPath = path.trim();
        if (cleanPath === '' || cleanPath.toLowerCase().includes('no file') || cleanPath.toLowerCase().includes('error')) return '';
        if (cleanPath.startsWith('http://') || cleanPath.startsWith('https://')) return cleanPath;
        
        cleanPath = cleanPath.replace(/^(\.\.\/|\.\/)+/, '');
        cleanPath = cleanPath.replace(/^\/+/, '');
        return '../' + cleanPath;
    }

    function updateOldDocBadges() {
        var docMap = {
            'PanDocument': { badge: 'old_PanDocument_badge', input: 'edit_PanDocument' },
            'AadharDocument': { badge: 'old_AadharDocument_badge', input: 'edit_AadharDocument' },
            'ProfilePhoto': { badge: 'old_ProfilePhoto_badge', input: 'edit_ProfilePhoto' },
            'CVFile': { badge: 'old_CVFile_badge', input: 'edit_CvDocument' },
            'CanceledCheque': { badge: 'old_CanceledCheque_badge', input: 'edit_CanceledCheque' }
        };
        for (var docType in docMap) {
            var item = docMap[docType];
            var badgeId = item.badge;
            var path = currentEmpDocs[docType];
            var $el = $('#' + badgeId);
            if ($el.length > 0) {
                var fullUrl = resolveDocUrl(path);
                if (fullUrl) {
                    $el.html(`<a href="${fullUrl}" target="_blank" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none px-2 py-1"><i class="fa fa-paperclip me-1"></i>View Current File</a>`);
                } else {
                    $el.html(`<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="fa fa-exclamation-triangle me-1"></i>Missing Document</span>`);
                }
            }
        }
    }

    function uploadDirectProfilePhoto(input) {
        let loginroleid = parseInt($("body").data("loginroleid")) || 0;
        if (loginroleid > 3) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Access Denied', 'You do not have permission to update profile photo.', 'error');
            } else {
                alert('You do not have permission to update profile photo.');
            }
            return;
        }
        if (input.files && input.files[0]) {
            var formData = new FormData();
            var companyid = $("body").data("companyid");
            var subcompanyid = $("body").data("subcompanyid");
            var projectid = $("body").data("projectid");
            var employeeid = $("body").data("employeeid") || "<?php echo $empid; ?>";

            formData.append('companyid', companyid);
            formData.append('subcompanyid', subcompanyid);
            formData.append('projectid', projectid);
            formData.append('EmployeeID', employeeid);
            formData.append('ProfilePhoto', input.files[0]);

            $.ajax({
                url: "../modal/hrm_modal.php?action=uploadDirectProfilePhoto",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function(res) {
                    let msg = (res && res.message) ? res.message : 'Profile photo updated successfully!';
                    if (res && (res.status === 'success' || res.status === 'Success')) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Success', msg, 'success').then(() => location.reload());
                        } else {
                            alert(msg);
                            location.reload();
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Error', msg, 'error');
                        } else {
                            alert(msg);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    let errStr = 'Upload failed: ' + error;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', errStr, 'error');
                    } else {
                        alert(errStr);
                    }
                }
            });
        }
    }

    function previewDoc(docType) {
        var docPath = currentEmpDocs[docType] || '';
        var fullUrl = resolveDocUrl(docPath);
        if (fullUrl) {
            window.open(fullUrl, '_blank');
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Info', 'No attached document found for preview.', 'info');
            } else {
                alert('No attached document found for preview.');
            }
        }
    }

    function openEditEmployeeModal(empid) {
        let loginroleid = parseInt($("body").data("loginroleid")) || 0;
        if (loginroleid > 3) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Access Denied', 'You do not have permission to edit employee profile.', 'error');
            } else {
                alert('You do not have permission to edit employee profile.');
            }
            return;
        }
        let targetEmpId = empid || $("body").data("employeeid") || typeof EmpId !== 'undefined' ? EmpId : '';
        let routurl = $("body").data("routurl") || "../";
        
        if ($('#edit_GenderName').children('option').length === 0) {
            populateEditSelects(targetEmpId);
        }
        let companyid = $("body").data("companyid");
        let subcompanyid = $("body").data("subcompanyid");
        let projectid = $("body").data("projectid");

        // Fetch Employee Data
        $.post(routurl + "modal/hrm_modal.php?action=EditEmployeeProfildata", { companyid: companyid, subcompanyid: subcompanyid, projectid: projectid, employeeid: targetEmpId }, function (res) {
            if (res && res.length > 0) {
                let d = res[0];
                $('#edit_companyid').val(d.companyid);
                $('#edit_subcompanyid').val(d.subcompanyid);
                $('#edit_projectid').val(d.projectid);
                $('#edit_EmployeeID').val(d.EmployeeID);

                $('#edit_TitleName').val(d.TitleName);
                $('#edit_FirstName').val(d.FirstName);
                $('#edit_MiddleName').val(d.MiddleName);
                $('#edit_LastName').val(d.LastName);
                $('#edit_FatherName').val(d.FatherName || '');
                $('#edit_MotherName').val(d.MotherName || '');
                $('#edit_DateOfBirth').val(d.DateOfBirth);
                $('#edit_MaritalStatus').val(d.MaritalStatus || '');
                $('#edit_SpouseName').val(d.SpouseName || '');

                setTimeout(() => {
                    if (d.Gender) $('#edit_GenderName').val(d.Gender);
                    if (d.Nationality) $('#edit_Nationality').val(d.Nationality);
                    if (d.CountryName) $('#edit_CountryName').val(d.CountryName);
                    toggleSpouseField();
                }, 500);

                toggleSpouseField();

                $('#edit_MobileNumber').val(d.MobileNo);
                $('#edit_EmailAddress').val(d.Email);
                $('#edit_PanNumber').val(d.PanNumber);
                $('#edit_AadharNumber').val(d.AadharNumber);
                $('#edit_PassportNumber').val(d.PassportNumber);
                $('#edit_AddressLine1').val(d.AddressLine1);
                $('#edit_AddressLine2').val(d.AddressLine2);
                $('#edit_AddressLine3').val(d.AddressLine3);
                $('#edit_AddressLine4').val(d.AddressLine4);
                $('#edit_PostalCode').val(d.PostalCode);

                // Contact Person 1 Auto-fill
                $('#edit_ContactPerson1_Name').val(d.ContactPerson1_Name || '');
                $('#edit_ContactPerson1_Relation').val(d.ContactPerson1_Relation || '');
                $('#edit_ContactPerson1_Mobile').val(d.ContactPerson1_Mobile || '');
                $('#edit_ContactPerson1_Email').val(d.ContactPerson1_Email || '');
                $('#edit_ContactPerson1_Address').val(d.ContactPerson1_Address || '');
                $('#edit_ContactPerson1_PostalCode').val(d.ContactPerson1_PostalCode || '');
                $('#edit_ContactPerson1_Note').val(d.ContactPerson1_Note || '');

                // Contact Person 2 Auto-fill
                $('#edit_ContactPerson2_Name').val(d.ContactPerson2_Name || '');
                $('#edit_ContactPerson2_Relation').val(d.ContactPerson2_Relation || '');
                $('#edit_ContactPerson2_Mobile').val(d.ContactPerson2_Mobile || '');
                $('#edit_ContactPerson2_Email').val(d.ContactPerson2_Email || '');
                $('#edit_ContactPerson2_Address').val(d.ContactPerson2_Address || '');
                $('#edit_ContactPerson2_PostalCode').val(d.ContactPerson2_PostalCode || '');
                $('#edit_ContactPerson2_Note').val(d.ContactPerson2_Note || '');

                // Store document paths for preview
                currentEmpDocs['PanDocument'] = d.empPanpath || '';
                currentEmpDocs['AadharDocument'] = d.empAadharpath || '';
                currentEmpDocs['CVFile'] = d.empCvpath || '';
                currentEmpDocs['CanceledCheque'] = d.empBankDocpath || '';
                currentEmpDocs['ProfilePhoto'] = d.empPhotopath || '';

                updateOldDocBadges();
            }
        }, "json");

        // Fetch Bank Details
        $.post("../modal/hrm_modal.php?action=fetchEmployeeBankDetails_callback", { companyid: companyid, subcompanyid: subcompanyid, projectid: projectid, EmployeeId: targetEmpId }, function (res) {
            if (res && Array.isArray(res) && res.length > 0 && res[0].BankName) {
                let b = res[0];
                $('#edit_BankName').val(b.BankName || '');
                $('#edit_AccountNumber').val(b.AccountNumber || '');
                $('#edit_IFSCCode').val(b.IFSCCode || '');
                if (b.CancelChequePath) {
                    currentEmpDocs['CanceledCheque'] = b.CancelChequePath;
                    updateOldDocBadges();
                }
            } else {
                $('#edit_BankName').val('');
                $('#edit_AccountNumber').val('');
                $('#edit_IFSCCode').val('');
            }
        }, "json");

        // Open Modal - Robust Method
        var modalEl = document.getElementById('editEmployeeModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        } else if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
            $('#editEmployeeModal').modal('show');
        } else {
            // Fallback or error
            console.error("Modal library not found");
            // Try valid jQuery one last time in case
            $('#editEmployeeModal').modal('show');
        }
    }

    function updatedetailsByemployee() {
        var form = document.getElementById('editEmployeeForm');
        var formData = new FormData(form);
        
        $.ajax({
            url: "../modal/hrm_modal.php?action=updatedetailsByemployee",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(res) {
                if (res.status === 'success' || res.status === 'Success') {
                    // Use SweetAlert if available, else alert
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Success', 'Profile Updated Successfully', 'success').then(() => location.reload());
                    } else {
                        alert("Profile Updated Successfully");
                        location.reload();
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', res.message, 'error');
                    } else {
                        alert("Error: " + res.message);
                    }
                }
            },
            error: function() {
                alert("Server Error. Please try again.");
            }
        });
    }

    function togglePasswordVisibility() {
        let pwd = document.getElementById("new_password");
        let btnIcon = document.querySelector("#togglePasswordBtn i");
        if (pwd.type === "password") {
            pwd.type = "text";
            btnIcon.classList.remove("fa-eye");
            btnIcon.classList.add("fa-eye-slash");
        } else {
            pwd.type = "password";
            btnIcon.classList.remove("fa-eye-slash");
            btnIcon.classList.add("fa-eye");
        }
    }

    function toggleConfirmPasswordVisibility() {
        let pwd = document.getElementById("confirm_password");
        let btnIcon = document.querySelector("#toggleConfirmPasswordBtn i");
        if (pwd.type === "password") {
            pwd.type = "text";
            btnIcon.classList.remove("fa-eye");
            btnIcon.classList.add("fa-eye-slash");
        } else {
            pwd.type = "password";
            btnIcon.classList.remove("fa-eye-slash");
            btnIcon.classList.add("fa-eye");
        }
    }

    function openResetPasswordModal() {
        $('#new_password').val('');
        $('#confirm_password').val('');
        $('#togglePasswordBtn i').removeClass('fa-eye-slash').addClass('fa-eye');
        $('#new_password').attr('type', 'password');
        $('#toggleConfirmPasswordBtn i').removeClass('fa-eye-slash').addClass('fa-eye');
        $('#confirm_password').attr('type', 'password');

        var modalEl = document.getElementById('resetPasswordModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            $('#resetPasswordModal').modal('show');
        }
    }

    function submitResetPassword() {
        let newPassword = $('#new_password').val().trim();
        let confirmPassword = $('#confirm_password').val().trim();
        let loginId = $("body").data("loginid");
        let routurl = $("body").data("routurl");

        if (!newPassword) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Warning', 'Please enter a new password', 'warning');
            } else {
                alert('Please enter a new password');
            }
            return;
        }

        if (newPassword !== confirmPassword) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Passwords do not match', 'error');
            } else {
                alert('Passwords do not match');
            }
            return;
        }

        $.ajax({
            url: routurl + "modal/hrm_modal.php?action=resetEmployeePassword",
            type: "POST",
            data: {
                loginid: loginId,
                new_password: newPassword
            },
            dataType: "json",
            success: function(res) {
                if (res.status === 'success' || res.status === 'Success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Success', 'Password updated successfully!', 'success').then(() => {
                            $('#resetPasswordModal').modal('hide');
                        });
                    } else {
                        alert('Password updated successfully!');
                        $('#resetPasswordModal').modal('hide');
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', res.message || 'Failed to update password', 'error');
                    } else {
                        alert('Error: ' + (res.message || 'Failed to update password'));
                    }
                }
            },
            error: function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Server Error. Please try again.', 'error');
                } else {
                    alert('Server Error. Please try again.');
                }
            }
        });
    }

    $(document).ready(function() {
        let urlParams = new URLSearchParams(window.location.search);
        let op = urlParams.get('op');
        let loginroleid = parseInt($("body").data("loginroleid")) || 0;
        // if (op === 'edit' && loginroleid <= 3) {
        if (op === 'edit' && loginroleid <= 3) {
            $("#salarydetailsdiv").show();
            setTimeout(function() {
                openEditEmployeeModal(EmpId);
            }, 600);
        } else if (op === 'changepassword') {
            $("#salarydetailsdiv").hide();
            setTimeout(function() {
                openResetPasswordModal();
            }, 600);
        }
    });
</script>