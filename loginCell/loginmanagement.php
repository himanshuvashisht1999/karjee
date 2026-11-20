<?php
include('header.php');
$mdid = "";
if (isset($_GET['mdid'])) {
    $mdid = $_GET['mdid'];
}
$dddr = OnlyProject_loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
    $groupid = $dddr[0]['groupid'];
}
?>

<style>
/* Premium Management Page Styling */
.mgmt-hero-card {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 12px;
    box-shadow: 0 8px 20px -4px rgba(15, 23, 42, 0.2);
    margin-bottom: 14px;
}

.mgmt-hero-title {
    font-size: 1.25rem;
    font-weight: 800;
    margin-bottom: 2px;
    color: #ffffff;
    letter-spacing: -0.3px;
}

.mgmt-hero-sub {
    color: #94a3b8;
    font-size: 0.8rem;
    margin: 0;
}

.btn-new-user-primary {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: #ffffff !important;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.82rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 3px 10px rgba(2, 132, 199, 0.3);
    transition: all 0.2s ease-in-out;
    text-decoration: none !important;
}

.btn-new-user-primary:hover {
    background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.4);
}

/* KPI Summary Cards */
.kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 16px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
    transition: all 0.2s ease-in-out;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.kpi-icon-wrapper {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

/* Filter Toolbar */
.toolbar-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 14px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    margin-bottom: 14px;
}

.search-box-group {
    position: relative;
    display: flex;
    align-items: center;
}

.search-box-group i {
    position: absolute;
    left: 12px;
    color: #94a3b8;
    font-size: 0.85rem;
    pointer-events: none;
}

.search-box-input {
    height: 36px;
    padding-left: 34px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 0.85rem;
    color: #0f172a;
    transition: all 0.2s;
}

.search-box-input:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
    outline: none;
}

.filter-dropdown {
    height: 36px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
    transition: all 0.2s;
}

.filter-dropdown:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
    outline: none;
}

/* Premium Table */
.table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    margin-bottom: 45px;
}

.mgmt-table {
    margin: 0;
    width: 100%;
}

.mgmt-table th {
    background-color: #f8fafc;
    color: #475569;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.74rem;
    letter-spacing: 0.4px;
    padding: 10px 14px;
    border-bottom: 2px solid #e2e8f0;
    border-top: none;
}

.mgmt-table td {
    padding: 9px 14px;
    vertical-align: middle;
    color: #1e293b;
    font-size: 0.85rem;
    border-bottom: 1px solid #f1f5f9;
    border-top: none;
}

.mgmt-table tbody tr {
    transition: background-color 0.15s ease-in-out;
}

.mgmt-table tbody tr:hover {
    background-color: #f8fafc;
}

/* Badges */
.role-pill {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 16px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.role-pill-admin {
    background-color: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}

.role-pill-user {
    background-color: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.role-pill-operator {
    background-color: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
}

.role-pill-default {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
}

.striketxt_red {
    text-decoration: line-through;
    color: #ef4444 !important;
    opacity: 0.75;
}
</style>

<div class="content container-fluid px-4 py-3">
    <!-- Hero Header Banner -->
    <div class="mgmt-hero-card d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h2 class="mgmt-hero-title d-flex align-items-center gap-2">
                <i class="fa fa-users text-info mr-2"></i> User Administration
            </h2>
            <p class="mgmt-hero-sub">Manage authenticated user accounts, roles, and module access permissions</p>
        </div>
        <div>
            <a class="btn-new-user-primary" id="addeditlogin_btn" href="login-new.php?k=<?php echo $_GET['k']; ?>">
                <i class="fa fa-user-plus mr-1"></i> New User
            </a>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-2 mb-3">
        <div class="col-md-4 col-sm-6">
            <div class="kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-bold text-uppercase d-block mb-1">Total Users</span>
                    <h3 class="fw-bold text-dark mb-0 fs-4" id="statTotalUsers">0</h3>
                </div>
                <div class="kpi-icon-wrapper bg-soft-primary text-primary">
                    <i class="fa fa-users"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-bold text-uppercase d-block mb-1">Active Accounts</span>
                    <h3 class="fw-bold text-success mb-0 fs-4" id="statActiveUsers">0</h3>
                </div>
                <div class="kpi-icon-wrapper bg-soft-success text-success">
                    <i class="fa fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="kpi-card d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-bold text-uppercase d-block mb-1">Roles Configured</span>
                    <h3 class="fw-bold text-info mb-0 fs-4" id="statTotalRoles">0</h3>
                </div>
                <div class="kpi-icon-wrapper bg-soft-info text-info">
                    <i class="fa fa-cogs"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar: Search & Role Filters -->
    <div class="toolbar-card">
        <div class="row g-2 align-items-center">
            <div class="col-md-7 col-lg-8">
                <div class="search-box-group">
                    <i class="fa fa-search"></i>
                    <input type="text" id="userSearch" class="form-control search-box-input" placeholder="Search users by name, user ID, mobile, or email...">
                </div>
            </div>

            <div class="col-md-5 col-lg-4 d-flex align-items-center gap-2">
                <select id="roleFilter" class="form-select filter-dropdown">
                    <option value="">All Roles</option>
                </select>
                <span class="badge bg-soft-secondary text-secondary px-3 py-2 fw-semibold fs-12 text-nowrap" id="userCounterBadge">0 Users</span>
            </div>
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="table-card">
        <div class="table-responsive">
            <table id="loginlist_tbl" class="table mgmt-table align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th class="text-center" style="width: 120px;">User ID</th>
                        <th>Full Name</th>
                        <th>Assigned Role</th>
                        <th>Mobile Number</th>
                        <th>Email Address</th>
                        <th class="text-center" style="width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>
                            Loading user accounts...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script type="text/javascript">
var loginid = $("body").data("loginid");
var loginroleid = $("body").data("loginroleid");
var companyid = "<?php echo $companyid; ?>";
var subcompanyid = "<?php echo $subcompanyid; ?>";
var projectid = "<?php echo $projectid; ?>";
var loginrolename = "<?php echo $loginrolename; ?>";
var rooturl = "<?php echo $rooturl; ?>";
var key = "<?php echo $key; ?>";

// Load user details table
logindetails_htmltableview('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','hidden_loginid','loginlist_tbl',function(res){
    applyTableStyles();
    updateKpiStats();
});

// Style badges and action buttons after AJAX data load
function applyTableStyles() {
    $("#loginlist_tbl tbody tr").each(function() {
        let roleTd = $(this).find('td:eq(3)');
        let roleText = roleTd.text().trim();
        if (roleText && !roleTd.find('.role-pill').length) {
            let pillClass = "role-pill-default";
            let iconClass = "fa-user";
            let lowerRole = roleText.toLowerCase();
            if (lowerRole.includes('admin') || lowerRole.includes('super')) {
                pillClass = "role-pill-admin";
                iconClass = "fa-user";
            } else if (lowerRole.includes('user') || lowerRole.includes('staff')) {
                pillClass = "role-pill-user";
                iconClass = "fa-user";
            } else if (lowerRole.includes('operator') || lowerRole.includes('manager')) {
                pillClass = "role-pill-operator";
                iconClass = "fa-cog";
            }
            roleTd.html(`<span class="role-pill ${pillClass}"><i class="fa ${iconClass} mr-1"></i>${roleText}</span>`);
        }
        
        let actionTd = $(this).find('td:eq(6)');
        let editIcon = actionTd.find('.editlginfo');
        let deleteIcon = actionTd.find('.removelginfo');
        
        if (editIcon.length && !actionTd.hasClass('action-styled')) {
            actionTd.addClass('action-styled');
            let deleteOnClick = deleteIcon.attr('onclick') || '';
            
            actionTd.html(`
                <div class="d-inline-flex align-items-center justify-content-center" style="gap: 8px;">
                    <button type="button" class="btn btn-sm btn-outline-primary editlginfo shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 8px; padding: 0;" title="Edit User">
                        <i class="fa fa-edit" style="font-size: 14px; margin: 0; pointer-events: none;"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger removelginfo shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 8px; padding: 0;" ${deleteOnClick ? `onclick="${deleteOnClick}"` : ''} title="Delete User">
                        <i class="fa fa-trash" style="font-size: 14px; margin: 0; pointer-events: none;"></i>
                    </button>
                </div>
            `);
        }
    });

    // Populate unique roles dropdown
    let uniqueRoles = new Set();
    $("#loginlist_tbl tbody tr").each(function() {
        let role = $(this).find('.role-pill').text().trim();
        if (role) {
            uniqueRoles.add(role);
        }
    });
    
    let currentSelected = $("#roleFilter").val();
    $("#roleFilter").html('<option value="">All Roles</option>');
    uniqueRoles.forEach(function(role) {
        $("#roleFilter").append(`<option value="${role}">${role}</option>`);
    });
    if (currentSelected) {
        $("#roleFilter").val(currentSelected);
    }
}

// Update KPI Stats
function updateKpiStats() {
    let total = 0;
    let active = 0;
    let rolesSet = new Set();

    $("#loginlist_tbl tbody tr").each(function() {
        if ($(this).find('td').length >= 7) {
            total++;
            let isStruck = $(this).find('.striketxt_red').length > 0;
            if (!isStruck) active++;

            let role = $(this).find('.role-pill').text().trim();
            if (role) rolesSet.add(role);
        }
    });

    $("#statTotalUsers").text(total);
    $("#statActiveUsers").text(active);
    $("#statTotalRoles").text(rolesSet.size);
    $("#userCounterBadge").text(total + " Users");
}

// Client-side Live Filter logic
function filterTable() {
    let searchText = $("#userSearch").val().toLowerCase().trim();
    let selectedRole = $("#roleFilter").val();
    let visibleCount = 0;

    $("#loginlist_tbl tbody tr").each(function() {
        let row = $(this);
        if (row.find('td').length < 7) return;

        let userId = row.find('td:eq(1)').text().toLowerCase();
        let userName = row.find('.lgname_td').text().toLowerCase();
        let role = row.find('.role-pill').text().toLowerCase();
        let mobile = row.find('td:eq(4)').text().toLowerCase();
        let email = row.find('td:eq(5)').text().toLowerCase();

        let matchesSearch = !searchText || 
                            userId.includes(searchText) || 
                            userName.includes(searchText) || 
                            mobile.includes(searchText) || 
                            email.includes(searchText);

        let matchesRole = !selectedRole || role === selectedRole.toLowerCase();

        if (matchesSearch && matchesRole) {
            row.show();
            visibleCount++;
        } else {
            row.hide();
        }
    });

    $("#userCounterBadge").text(visibleCount + " Users");
}

$(document).on("input", "#userSearch", filterTable);
$(document).on("change", "#roleFilter", filterTable);

$(document).on("click", ".editlginfo", function(){ 
    var editloginid = $(this).closest("tr").find("td.lgloginid_td").text().trim();
    var qstring = loginid+"|"+loginroleid+"|"+companyid+"|"+subcompanyid+"|"+projectid+"|"+loginrolename;
    var enc_qstring = url_btoa(qstring);
    var key = qstring + "|" + enc_qstring;
    var enc_key = url_btoa(key);
    window.location.href = rooturl + "loginCell/login-new.php?k=" + enc_key + "&edlid=" + editloginid;
});
</script>
