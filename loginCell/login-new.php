<?php
include('header.php');

$editloginid = "";
if (isset($_GET['edlid'])) {
    $editloginid = $_GET['edlid'];
}

$pageTitle = "New User Registration";
$btntext = "Save Login Details";
$loginuserid = "";
$loginpassword = "";
$loginusername = "";
$loginroleid = "";
$loginrolename = "";
$loginmobileno = "";
$loginemailid = "";
$departmentid = "";

if (!empty($editloginid)) {
    $pageTitle = "Edit User Account Details";
    $btntext = "Update Login Details";
    $ud = loginuserdetailsfrommaster($authconn, "", "loginid", $editloginid);
    if (count($ud) > 0) {
        $loginuserid = $ud[0]['loginid'];
        $loginpassword = $ud[0]['loginpassword'];
        $loginusername = $ud[0]['loginname'];
        $loginroleid = $ud[0]['loginroleid'];
        $loginrolename = $ud[0]['loginrolename'];
        $loginmobileno = ($ud[0]['mobileno'] != 'N/A') ? $ud[0]['mobileno'] : '';
        $loginemailid = ($ud[0]['emailid'] != 'N/A') ? $ud[0]['emailid'] : '';
        $departmentid = $ud[0]['departmentid'];
    }
}
?>

<style>
.login-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.login-card-header {
    background: #f8fafc;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}

.login-card-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin: 0;
}

.login-form-label {
    font-weight: 600;
    font-size: 0.8rem;
    color: #334155;
    margin-bottom: 0.25rem;
}

.login-form-control {
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    padding: 0.32rem 0.65rem;
    height: 35px;
    font-size: 0.85rem;
    line-height: 1.4;
    color: #1e293b;
    transition: all 0.2s ease-in-out;
}

.login-form-control:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
}

.input-group-text-btn {
    border-radius: 0 6px 6px 0;
    border: 1px solid #cbd5e1;
    border-left: none;
    background-color: #f8fafc;
    color: #64748b;
    height: 35px;
    padding: 0 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.input-group-text-btn:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}

.section-badge {
    background-color: #e0f2fe;
    color: #0369a1;
    font-weight: 700;
    font-size: 0.72rem;
    padding: 0.2rem 0.65rem;
    border-radius: 16px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.btn-save-custom {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: #ffffff !important;
    border: none;
    padding: 0.45rem 1.25rem;
    border-radius: 7px;
    font-weight: 600;
    font-size: 0.84rem;
    box-shadow: 0 3px 10px rgba(2, 132, 199, 0.25);
    transition: all 0.2s;
}

.btn-save-custom:hover {
    background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(2, 132, 199, 0.35);
}

.btn-cancel-custom {
    background: #f1f5f9;
    color: #475569 !important;
    border: 1px solid #cbd5e1;
    padding: 0.45rem 1rem;
    border-radius: 7px;
    font-size: 0.84rem;
    font-weight: 600;
    transition: all 0.2s;
}

.btn-cancel-custom:hover {
    background: #e2e8f0;
    color: #1e293b !important;
}

/* Remove Browser Autofill background highlight */
input:-webkit-autofill,
input:-webkit-autofill:hover, 
input:-webkit-autofill:focus, 
input:-webkit-autofill:active,
select:-webkit-autofill,
select:-webkit-autofill:hover,
select:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
    -webkit-text-fill-color: #334155 !important;
    transition: background-color 5000s ease-in-out 0s;
}

.custom-table th {
    background-color: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 12px;
}

.custom-table td {
    padding: 8px 12px;
    font-size: 0.84rem;
}
</style>

<div class="content container-fluid px-4 py-3">
    <!-- Breadcrumb & Header Section -->
    <div class="d-md-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2" style="font-size: 1.25rem;">
                <i class="fa fa-user-plus text-primary mr-1"></i>
                <?php echo $pageTitle; ?>
            </h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 py-0" style="background: transparent; font-size: 0.8rem;">
                    <li class="breadcrumb-item"><a href="loginmanagement.php?k=<?php echo $_GET['k']; ?>" class="text-muted">User Management</a></li>
                    <li class="breadcrumb-item active text-primary" aria-current="page"><?php echo !empty($editloginid) ? "Edit User" : "New User"; ?></li>
                </ol>
            </nav>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="loginmanagement.php?k=<?php echo $_GET['k']; ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm" style="border-radius: 7px; padding: 0.4rem 0.9rem; font-size: 0.84rem;">
                <i class="fa fa-arrow-left mr-1"></i> Back to User List
            </a>
        </div>
    </div>

    <!-- Main Card -->
    <div class="login-card">
        <div class="login-card-header d-flex align-items-center justify-content-between">
            <div class="login-card-title">
                <i class="fa fa-id-card text-primary mr-1"></i>
                <span>User Account Information</span>
            </div>
            <span class="section-badge"><?php echo !empty($editloginid) ? "EDIT MODE" : "NEW RECORD"; ?></span>
        </div>

        <div class="card-body p-3">
            <form id="userAccountForm" autocomplete="off" onsubmit="return false;">
                <!-- Dummy inputs to absorb aggressive browser password manager autofill -->
                <input type="text" style="display:none !important;" aria-hidden="true" tabindex="-1" autocomplete="off" />
                <input type="password" style="display:none !important;" aria-hidden="true" tabindex="-1" autocomplete="new-password" />

                <!-- Row 1: Credentials -->
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="login-form-label">
                            <i class="fa fa-user text-muted mr-1"></i> Login ID <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="text" class="form-control login-form-control" placeholder="Enter unique login ID" id="userloginid" value="<?php echo htmlspecialchars($loginuserid); ?>" autocomplete="off" />
                            <small id="userloginid_status" class="d-block mt-1 fs-12 text-muted"></small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="login-form-label">
                            <i class="fa fa-key text-muted mr-1"></i> Login Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control login-form-control" placeholder="Enter login password" id="userloginpassword" value="<?php echo htmlspecialchars($loginpassword); ?>" autocomplete="new-password" style="border-top-right-radius: 0; border-bottom-right-radius: 0;" />
                            <button class="btn btn-outline-secondary input-group-text-btn" type="button" onclick="togglePasswordVisibility()">
                                <i class="fa fa-eye" id="togglePassIcon" style="font-size: 14px;"></i>
                            </button>
                        </div>
                        <small class="text-muted fs-12">Default password if left blank will be user mobile number.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="login-form-label">
                            <i class="fa fa-user text-muted mr-1"></i> User Full Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control login-form-control" placeholder="Enter person full name" id="userloginname" value="<?php echo htmlspecialchars($loginusername); ?>" autocomplete="off" />
                    </div>
                </div>

                <!-- Row 2: Contact & Role -->
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="login-form-label">
                            <i class="fa fa-phone text-muted mr-1"></i> Mobile Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control login-form-control" placeholder="Enter 10-digit mobile number" id="mobileloginusre" value="<?php echo htmlspecialchars($loginmobileno); ?>" autocomplete="off" />
                        <small id="mobileno_status" class="d-block mt-1 fs-12 text-muted"></small>
                    </div>

                    <div class="col-md-4">
                        <label class="login-form-label">
                            <i class="fa fa-envelope text-muted mr-1"></i> Email Address
                        </label>
                        <input type="email" class="form-control login-form-control" placeholder="Enter user email address" id="emailloginuser" value="<?php echo htmlspecialchars($loginemailid); ?>" autocomplete="off" />
                    </div>

                    <div class="col-md-4">
                        <label class="login-form-label">
                            <i class="fa fa-shield text-muted mr-1"></i> User Role <span class="text-danger">*</span>
                        </label>
                        <select class="form-select login-form-control" id="roleofuser"></select>
                    </div>
                </div>

                <!-- Row 3: Department & Status -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="login-form-label">
                            <i class="fa fa-building text-muted mr-1"></i> Department <span class="text-danger">*</span>
                        </label>
                        <select class="form-select login-form-control" id="departmentofuser"></select>
                    </div>

                    <div class="col-md-6" style="display: none;">
                        <label class="login-form-label"><i class="fa fa-toggle-on text-muted mr-1"></i> Is Active User?</label>
                        <div class="d-flex gap-4 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="isactiveuser" id="activeYes" value="yes" checked />
                                <label class="form-check-label text-dark fw-bold" for="activeYes">YES</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="isactiveuser" id="activeNo" value="no" />
                                <label class="form-check-label text-dark fw-bold" for="activeNo">NO</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Authorization Sections -->
                <div id="doauthorizedprj_div" style="display:none;" class="mb-3">
                    <div class="p-3 bg-light rounded-3 border">
                        <h6 class="fw-bold text-primary mb-2 fs-13"><i class="fa fa-folder-open mr-1"></i> Project(s) Authorization for <?php echo htmlspecialchars($companyname ?? ''); ?></h6>
                        <div class="table-responsive">
                            <table id="loginprojectlist_tbl" class="table custom-table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 60px; text-align: center;">Select</th>
                                        <th>Project Name</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="doauthorizedmdl_div" class="mb-3">
                    <div class="p-2 px-3 bg-light rounded-3 border d-flex align-items-center" style="border-radius: 8px; gap: 10px;">
                        <input type="checkbox" id="doauthorizedmodule" style="position: static !important; margin: 0 !important; width: 18px !important; height: 18px !important; cursor: pointer; flex-shrink: 0; display: inline-block; vertical-align: middle;" onclick="onclickcheckboxdivhideshow('doauthorizedmodule','moduleslistdiv');" />
                        <label for="doauthorizedmodule" class="fw-bold text-dark mb-0 fs-13" style="cursor: pointer; user-select: none; margin: 0 !important; position: static !important; display: inline-block;">
                            Do you want to authorize specific Modules for Accessing?
                        </label>
                    </div>

                    <div class="mt-2 p-3 bg-white rounded-3 border" id="moduleslistdiv" style="display: none;">
                        <h6 class="fw-bold text-dark mb-2 fs-13"><i class="fa fa-th-large mr-1 text-primary"></i> Module(s) for Authorization</h6>
                        <div class="table-responsive">
                            <table id="loginmodulelist_tbl" class="table custom-table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 80px; text-align: center;">Access</th>
                                        <th>Module Name</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex align-items-center justify-content-end gap-2 pt-2 border-top">
                    <a href="loginmanagement.php?k=<?php echo $_GET['k']; ?>" class="btn btn-cancel-custom">
                        <i class="fa fa-times mr-1"></i> Cancel
                    </a>
                    <button id="saveuserdetails" class="btn btn-save-custom d-inline-flex align-items-center gap-2" onclick="savetodbuserlogindetails('hidden_baseurl','hidden_companyid','hidden_subcompanyid','userloginid','userloginpassword','userloginname','mobileloginusre','emailloginuser','roleofuser','isactiveuser','loginprojectlist_tbl','loginmodulelist_tbl','saveuserdetails','departmentofuser');">
                        <i class="fa fa-floppy-o mr-1"></i>
                        <span><?php echo $btntext; ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script type="text/javascript">
    function togglePasswordVisibility() {
        const passInput = document.getElementById("userloginpassword");
        const passIcon = document.getElementById("togglePassIcon");
        if (passInput.type === "password") {
            passInput.type = "text";
            passIcon.className = "fa fa-eye-slash";
        } else {
            passInput.type = "password";
            passIcon.className = "fa fa-eye";
        }
    }

    var isEditMode = <?php echo !empty($editloginid) ? 'true' : 'false'; ?>;

    function checkAndAutofillLoginDetails(loginid) {
        var statusEl = $("#userloginid_status");
        if (!loginid) {
            statusEl.html('');
            $("#userloginpassword").val('');
            $("#userloginname").val('');
            $("#mobileloginusre").val('');
            $("#emailloginuser").val('');
            return;
        }

        logindetails_fromloginid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid",loginid,function(res){
            // Ensure input value has not changed while waiting for async response
            if ($("#userloginid").val().trim() !== loginid) return;

            if(res && res.length > 0){
                statusEl.html('<span class="text-warning fw-semibold"><i class="fa fa-exclamation-triangle mr-1"></i> Existing User ID loaded for editing.</span>');
                for(var j=0; j<res.length; j++){
                    $("#userloginpassword").val(res[j].loginpassword || '');
                    $("#userloginname").val(res[j].loginname || '');
                    $("#mobileloginusre").val((res[j].mobileno && res[j].mobileno !== 'N/A') ? res[j].mobileno : '');
                    $("#emailloginuser").val((res[j].emailid && res[j].emailid !== 'N/A') ? res[j].emailid : ''); 
                    
                    var targetRoleId = res[j].loginroleid || '';
                    var targetDeptId = res[j].departmentid || '';

                    if(targetDeptId) {
                        $("#departmentofuser").val(targetDeptId).trigger("change");
                    }
                    
                    user_rolelistdropdown_html("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid", targetRoleId, "roleofuser");
                    projectlist_forauthorizaton_html("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid",loginid,"loginprojectlist_tbl");
                    modulelist_frommaster_html("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid",loginid,"loginmodulelist_tbl");
                }
            } else {
                statusEl.html('<span class="text-success fw-semibold"><i class="fa fa-check-circle mr-1"></i> Login ID is available!</span>');
                // Reset fields for new user registration
                $("#userloginpassword").val('');
                $("#userloginname").val('');
                $("#mobileloginusre").val('');
                $("#emailloginuser").val('');

                user_rolelistdropdown_html("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","<?php echo $loginroleid; ?>","roleofuser");
                projectlist_forauthorizaton_html("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","<?php echo $loginuserid; ?>","loginprojectlist_tbl");
                modulelist_frommaster_html("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","<?php echo $loginuserid; ?>","loginmodulelist_tbl");
            }
        });
    }

    $(document).on("input change blur", "#userloginid", function(){
        var loginid = $(this).val().trim();
        checkAndAutofillLoginDetails(loginid);
    });

    $(document).ready(function(){
        department_html("hidden_baseurl", "departmentofuser","<?php echo $departmentid; ?>");
        user_rolelistdropdown_html("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","<?php echo $loginroleid; ?>","roleofuser");   
        projectlist_forauthorizaton_html("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","<?php echo $loginuserid; ?>","loginprojectlist_tbl");
        modulelist_frommaster_html("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","<?php echo $loginuserid; ?>","loginmodulelist_tbl");

        if (!isEditMode) {
            // New Record Mode: Clear any browser autofilled data on load
            function clearAutofill() {
                $("#userloginid").val("");
                $("#userloginpassword").val("");
                $("#userloginname").val("");
                $("#mobileloginusre").val("");
                $("#emailloginuser").val("");
                $("#userloginid_status").html("");
                $("#mobileno_status").html("");
            }
            clearAutofill();
            setTimeout(clearAutofill, 100);
            setTimeout(clearAutofill, 400);
        } else {
            var initialId = $("#userloginid").val().trim();
            if (initialId) {
                checkAndAutofillLoginDetails(initialId);
            }
        }
    });
</script>