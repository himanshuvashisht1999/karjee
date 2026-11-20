<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$key = $_GET['k'] ?? '';
$mdid = $_GET['mdid'] ?? '';
$isDz = isset($_GET['dz']);

// Suppress duplicate headers/strips from header.php
$hide_header = true;
$hide_heading = true;

if ($isDz) {
    require_once(__DIR__ . '/../connection.php');
    require_once(__DIR__ . '/../functions/function_master.php');
    if (!empty($key)) {
        $key_decoded = base64url_decode($key);
        $key_parts = explode("|", $key_decoded);
        $loginid = $key_parts[0] ?? '';
        $loginroleid = $key_parts[1] ?? '';
        $companyid = $key_parts[2] ?? 1;
        $subcompanyid = $key_parts[3] ?? 1;
        $projectid = $key_parts[4] ?? 1;
    }
} else {
    require_once(__DIR__ . '/../header.php');
}

$planId = (int)($_GET['id'] ?? 0);
$backUrl = "freight_discount_review.php?k=" . urlencode($key) . "&mdid=" . urlencode($mdid) . ($isDz ? "&dz=1" : "");

if ($planId <= 0) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Invalid Plan ID specified. <a href='{$backUrl}'>Go Back</a></div></div>";
    if (!$isDz) require_once(__DIR__ . '/../footer.php');
    exit;
}
?>
<!-- Styles and Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">

<style>
/* Suppress duplicate top navbar & dashboard strip */
.navbar-premium,
.company-header-strip,
body > nav.navbar,
body > .company-header-strip {
    display: none !important;
}

body {
    background-color: #f8fafc !important;
    padding-bottom: 20px !important;
}

:root {
    --fd-primary: #10b981;
    --fd-primary-dark: #059669;
    --fd-border: #e2e8f0;
}

.fd-wrapper {
    padding: 16px 20px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1e293b;
}

.fd-form-card {
    background: #ffffff;
    border: 1px solid var(--fd-border);
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 24px;
}

.fd-form-header {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 18px 24px;
    color: #1e293b;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.fd-form-header h3 {
    font-family: 'Outfit', sans-serif;
    font-size: 18px;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #0f172a;
}

.fd-section-title {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    color: #0369a1;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
    padding-bottom: 6px;
    border-bottom: 2px solid #e0f2fe;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group label {
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 5px;
}

.form-control, .form-select {
    border: 1.5px solid var(--fd-border);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    color: #1e293b;
    outline: none;
    transition: all 0.2s;
}

.form-control:focus, .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.required-star {
    color: #dc2626;
    margin-left: 2px;
}

.live-calc-box {
    background: #f0fdf4;
    border: 1.5px dashed #86efac;
    border-radius: 12px;
    padding: 18px;
    position: sticky;
    top: 20px;
}
</style>

<div class="fd-wrapper">

    <div class="fd-form-card">
        <div class="fd-form-header">
            <h3><i class="fa fa-edit text-warning"></i> Edit Freight Discount Plan</h3>
            <div class="d-flex align-items-center gap-2">
                <span class="badge badge-info px-3 py-2 font-weight-bold" id="header-plan-code" style="font-size:12px;">Loading...</span>
                <a href="<?php echo $backUrl; ?>" class="btn btn-outline-secondary btn-sm fw-bold px-3 ml-2">
                    <i class="fa fa-arrow-left mr-1"></i> Back to List
                </a>
            </div>
        </div>

        <div class="card-body p-4" id="form-loading-wrap">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted font-weight-bold">Loading plan details...</p>
            </div>
        </div>

        <div class="card-body p-4" id="form-content-wrap" style="display:none;">
            <form id="editPlanForm" onsubmit="event.preventDefault(); submitEditPlan();">
                <input type="hidden" name="id" id="plan_id" value="<?php echo $planId; ?>">

                <div class="row g-4">
                    
                    <!-- Left Form Area (8 cols) -->
                    <div class="col-lg-8">

                        <!-- Section 1: Target Segments -->
                        <div class="fd-section-title">
                            <i class="fa fa-users"></i> 1. Target Customer &amp; Crop Segment
                        </div>

                        <input type="hidden" name="plan_name" id="plan_name" value="">
                        <input type="hidden" name="plan_code" id="plan_code" value="">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label>Customer Segment <span class="required-star">*</span></label>
                                    <select class="form-control form-select" name="customer_type" id="customer_type" required onchange="autoGeneratePlanNameAndCode(); updateLiveCalc();">
                                        <option value="Farmer">👨‍🌾 Farmer (Individual Plan)</option>
                                        <option value="Dealer">🏢 Dealer (Individual Plan)</option>
                                        <option value="All">🌐 All Customers (Both Dealer &amp; Farmer)</option>
                                    </select>
                                    <small class="text-muted">Separate plans for Dealers and Farmers</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label>Crop / Plant Category <span class="required-star">*</span></label>
                                    <select class="form-control form-select" name="plant_type" id="plant_type" required onchange="autoGeneratePlanNameAndCode(); updateLiveCalc();">
                                        <option value="Grafted">🌿 Grafted Plants</option>
                                        <option value="Nongrafted">🌱 Non-Grafted Plants</option>
                                        <option value="Both">🌿+🌱 Both Categories</option>
                                    </select>
                                    <small class="text-muted">Individually maintain for Grafted / Non-Grafted</small>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Discount Mechanism -->
                        <div class="fd-section-title mt-4">
                            <i class="fa fa-percent"></i> 2. Discount Calculation Rule
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label>Discount Type <span class="required-star">*</span></label>
                                    <select class="form-control form-select" name="discount_type" id="discount_type" required onchange="handleDiscountTypeChange(); updateLiveCalc();">
                                        <option value="Percentage">Percentage (%) on Freight</option>
                                        <option value="Fixed_Amount">Flat Fixed Amount (₹)</option>
                                        <option value="Per_Plant">Per Plant Subsidy (₹ / Plant)</option>
                                        <option value="Per_KM">Per KM Subsidy (₹ / KM)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label>Discount Value <span class="required-star">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0.01" class="form-control" name="discount_value" id="discount_value" required oninput="updateLiveCalc()">
                                        <div class="input-group-append">
                                            <span class="input-group-text font-weight-bold" id="discount_unit_label">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label>Max Discount Cap (₹) <small class="text-muted">(0 = Unlimited)</small></label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="max_discount_amount" id="max_discount_amount" value="0.00" oninput="updateLiveCalc()">
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Eligibility Thresholds -->
                        <div class="fd-section-title mt-4">
                            <i class="fa fa-filter"></i> 3. Eligibility &amp; Threshold Rules
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="form-group">
                                    <label>Min Order Qty (Plants)</label>
                                    <input type="number" min="0" class="form-control" name="min_order_qty" id="min_order_qty" placeholder="0 = No Min" oninput="updateLiveCalc()">
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="form-group">
                                    <label>Max Order Qty (Plants)</label>
                                    <input type="number" min="0" class="form-control" name="max_order_qty" id="max_order_qty" placeholder="0 = Unlimited">
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="form-group">
                                    <label>Min Distance (KM)</label>
                                    <input type="number" min="0" step="1" class="form-control" name="min_distance_km" id="min_distance_km" placeholder="0 = No Min KM" oninput="updateLiveCalc()">
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="form-group">
                                    <label>Min Freight Amount (₹)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="min_freight_amount" id="min_freight_amount" placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Geographic Region & Validity -->
                        <div class="fd-section-title mt-4">
                            <i class="fa fa-map-marker-alt"></i> 4. Geographic Scope &amp; Validity Period
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label>Applicable State</label>
                                    <select class="form-control form-select" name="stateid" id="stateid" onchange="handleStateChange()">
                                        <option value="ALL">All States (Pan-India)</option>
                                    </select>
                                    <input type="hidden" name="statename" id="statename" value="All States">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label>Applicable District</label>
                                    <select class="form-control form-select" name="districtid" id="districtid" onchange="handleDistrictChange()">
                                        <option value="ALL">All Districts</option>
                                    </select>
                                    <input type="hidden" name="districtname" id="districtname" value="All Districts">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label>Valid From <span class="required-star">*</span></label>
                                    <input type="date" class="form-control" name="valid_from" id="valid_from" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label>Valid To <span class="required-star">*</span></label>
                                    <input type="date" class="form-control" name="valid_to" id="valid_to" required>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="form-group">
                                    <label>Status <span class="required-star">*</span></label>
                                    <select class="form-control form-select" name="status" id="status">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="form-group">
                                    <label>Priority Rank</label>
                                    <input type="number" class="form-control" name="priority" id="priority" value="5" min="1">
                                </div>
                            </div>
                        </div>

                        <!-- Section 5: Description -->
                        <div class="fd-section-title mt-4">
                            <i class="fa fa-align-left"></i> 5. Description
                        </div>

                        <div class="mb-4">
                            <textarea class="form-control" name="description" id="description" rows="2" placeholder="Brief notes regarding this discount scheme..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg px-4 font-weight-bold" id="btn-update-plan">
                                <i class="fa fa-save mr-1"></i> Update Discount Plan
                            </button>
                            <a href="<?php echo $backUrl; ?>" class="btn btn-outline-secondary btn-lg px-4">Cancel</a>
                        </div>

                    </div>

                    <!-- Right Sidebar: Interactive Live Calculator (4 cols) -->
                    <div class="col-lg-4">
                        <div class="live-calc-box">
                            <div class="font-weight-bold text-success fs-15 mb-3 d-flex align-items-center gap-2">
                                <i class="fa fa-bolt"></i> Live Discount Rule Preview
                            </div>
                            <p class="text-muted small">See how this updated plan calculates discounts for sample orders:</p>

                            <div class="bg-white p-3 rounded-3 border mb-3">
                                <div class="small text-muted font-weight-bold">TARGET CONFIGURATION</div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>Customer:</span> <b id="preview-cust">Farmer</b>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>Crop Type:</span> <b id="preview-crop">Grafted</b>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>Rule Type:</span> <b id="preview-rule">15% OFF</b>
                                </div>
                            </div>

                            <div class="bg-white p-3 rounded-3 border mb-3">
                                <div class="small text-muted font-weight-bold mb-2">SIMULATION SCENARIO</div>
                                <div class="form-group mb-2">
                                    <label class="small mb-1">Sample Order Qty (Plants):</label>
                                    <input type="number" id="test-qty" class="form-control form-control-sm" value="2500" oninput="updateLiveCalc()">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small mb-1">Sample Freight Charge (₹):</label>
                                    <input type="number" id="test-freight" class="form-control form-control-sm" value="3000" oninput="updateLiveCalc()">
                                </div>
                            </div>

                            <div class="p-3 bg-white rounded-3 border">
                                <div class="d-flex justify-content-between small">
                                    <span>Gross Freight:</span> <span id="res-gross">₹3,000.00</span>
                                </div>
                                <div class="d-flex justify-content-between small text-success mt-1">
                                    <span>Calculated Discount:</span> <b id="res-discount">- ₹450.00</b>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between font-weight-bold fs-16 text-primary">
                                    <span>Net Freight:</span> <span id="res-net">₹2,550.00</span>
                                </div>
                                <div id="res-notes" class="small text-muted mt-2"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

</div>

<script>
var targetPlanId = <?php echo $planId; ?>;
var backUrl = "<?php echo $backUrl; ?>";

$(document).ready(function() {
    loadStates(function() {
        loadPlanDetails();
    });
});

function loadStates(callback) {
    $.ajax({
        url: 'modal/freight_discount_modal.php?action=get_states',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.status && res.data) {
                var opts = '<option value="ALL">All States (Pan-India)</option>';
                res.data.forEach(function(s) {
                    opts += '<option value="' + s.stateid + '">' + s.statename + '</option>';
                });
                $('#stateid').html(opts);
            }
            if (callback) callback();
        },
        error: function() {
            if (callback) callback();
        }
    });
}

function loadPlanDetails() {
    $.ajax({
        url: 'modal/freight_discount_modal.php?action=get_plan',
        type: 'GET',
        data: { id: targetPlanId },
        dataType: 'json',
        success: function(res) {
            if (res.status && res.data) {
                populateForm(res.data);
                $('#form-loading-wrap').hide();
                $('#form-content-wrap').show();
            } else {
                $('#form-loading-wrap').html('<div class="alert alert-danger">' + (res.message || 'Error loading plan') + '</div>');
            }
        },
        error: function() {
            $('#form-loading-wrap').html('<div class="alert alert-danger">Network error loading plan details.</div>');
        }
    });
}

function autoGeneratePlanNameAndCode() {
    var cust = $('#customer_type').val() || 'Farmer';
    var plant = $('#plant_type').val() || 'Grafted';
    var custLabel = (cust === 'Dealer') ? 'Dealer' : (cust === 'Farmer' ? 'Farmer' : 'All Customers');
    var plantLabel = (plant === 'Grafted') ? 'Grafted Crops' : ((plant === 'Nongrafted') ? 'Non-Grafted Crops' : 'All Crops');
    
    var title = custLabel + ' - ' + plantLabel + ' Freight Discount Plan';
    var custCode = (cust === 'Dealer') ? 'DLR' : (cust === 'Farmer' ? 'FRM' : 'ALL');
    var plantCode = (plant === 'Grafted') ? 'GRAFT' : ((plant === 'Nongrafted') ? 'NONGR' : 'ALL');
    var planId = $('#plan_id').val() || '0';
    var code = 'FDR-' + custCode + '-' + plantCode + '-' + String(planId).padStart(3, '0');
    
    $('#plan_name').val(title);
    $('#plan_code').val(code);
    $('#header-plan-code').text(code);
}

function populateForm(p) {
    $('#header-plan-code').text(p.plan_code);
    $('#plan_name').val(p.plan_name);
    $('#plan_code').val(p.plan_code);
    $('#customer_type').val(p.customer_type);
    $('#plant_type').val(p.plant_type);
    $('#discount_type').val(p.discount_type);
    $('#discount_value').val(parseFloat(p.discount_value || 0));
    $('#max_discount_amount').val(parseFloat(p.max_discount_amount || 0));
    $('#min_order_qty').val(parseInt(p.min_order_qty || 0));
    $('#max_order_qty').val(parseInt(p.max_order_qty || 0));
    $('#min_distance_km').val(parseFloat(p.min_distance_km || 0));
    $('#min_freight_amount').val(parseFloat(p.min_freight_amount || 0));
    
    $('#valid_from').val(p.valid_from);
    $('#valid_to').val(p.valid_to);
    $('#status').val(p.status);
    $('#priority').val(p.priority || 1);
    $('#description').val(p.description || '');

    if (p.stateid && p.stateid !== 'ALL') {
        $('#stateid').val(p.stateid);
        $('#statename').val(p.statename || '');
        loadDistricts(p.stateid, p.districtid, p.districtname);
    } else {
        $('#stateid').val('ALL');
        $('#statename').val('All States');
    }

    handleDiscountTypeChange();
    updateLiveCalc();
}

function loadDistricts(stateid, selectedDistrictId, selectedDistrictName) {
    if (!stateid || stateid === 'ALL') {
        $('#districtid').html('<option value="ALL">All Districts</option>');
        $('#districtname').val('All Districts');
        return;
    }

    $.ajax({
        url: 'modal/freight_discount_modal.php?action=get_districts',
        type: 'GET',
        data: { stateid: stateid },
        dataType: 'json',
        success: function(res) {
            var opts = '<option value="ALL">All Districts</option>';
            if (res.status && res.data) {
                res.data.forEach(function(d) {
                    var isSel = (d.districtid == selectedDistrictId) ? 'selected' : '';
                    opts += '<option value="' + d.districtid + '" ' + isSel + '>' + d.districtname + '</option>';
                });
            }
            $('#districtid').html(opts);
            $('#districtname').val(selectedDistrictName || 'All Districts');
        }
    });
}

function handleStateChange() {
    var stateid = $('#stateid').val();
    var statename = $('#stateid option:selected').text();
    $('#statename').val(statename);
    loadDistricts(stateid, 'ALL', 'All Districts');
}

function handleDistrictChange() {
    var districtname = $('#districtid option:selected').text();
    $('#districtname').val(districtname);
}

function handleDiscountTypeChange() {
    var type = $('#discount_type').val();
    if (type === 'Percentage') $('#discount_unit_label').text('%');
    else if (type === 'Fixed_Amount') $('#discount_unit_label').text('₹ Flat');
    else if (type === 'Per_Plant') $('#discount_unit_label').text('₹ / Plant');
    else if (type === 'Per_KM') $('#discount_unit_label').text('₹ / KM');
}

function updateLiveCalc() {
    var cust = $('#customer_type').val();
    var crop = $('#plant_type').val();
    var discType = $('#discount_type').val();
    var discVal = parseFloat($('#discount_value').val() || 0);
    var maxCap = parseFloat($('#max_discount_amount').val() || 0);
    var minQty = parseInt($('#min_order_qty').val() || 0);

    $('#preview-cust').text(cust);
    $('#preview-crop').text(crop);
    
    var ruleStr = '';
    if (discType === 'Percentage') ruleStr = discVal + '% OFF';
    else if (discType === 'Fixed_Amount') ruleStr = '₹' + discVal + ' Flat';
    else if (discType === 'Per_Plant') ruleStr = '₹' + discVal + ' / plant';
    else if (discType === 'Per_KM') ruleStr = '₹' + discVal + ' / KM';
    $('#preview-rule').text(ruleStr);

    var sampleQty = parseInt($('#test-qty').val() || 0);
    var sampleFreight = parseFloat($('#test-freight').val() || 0);

    var discAmt = 0.0;
    var notes = '';

    if (minQty > 0 && sampleQty < minQty) {
        discAmt = 0.0;
        notes = '<span class="text-danger"><i class="fa fa-times-circle"></i> Does not qualify (Order Qty ' + sampleQty + ' < Min ' + minQty + ')</span>';
    } else {
        if (discType === 'Percentage') {
            discAmt = (sampleFreight * discVal) / 100.0;
        } else if (discType === 'Fixed_Amount') {
            discAmt = Math.min(discVal, sampleFreight);
        } else if (discType === 'Per_Plant') {
            discAmt = Math.min(sampleQty * discVal, sampleFreight);
        } else if (discType === 'Per_KM') {
            discAmt = Math.min(100 * discVal, sampleFreight);
        }

        if (maxCap > 0 && discAmt > maxCap) {
            discAmt = maxCap;
            notes = '<span class="text-warning"><i class="fa fa-info-circle"></i> Max Cap applied: ₹' + maxCap.toFixed(2) + '</span>';
        } else {
            notes = '<span class="text-success"><i class="fa fa-check-circle"></i> Valid Discount Applied</span>';
        }
    }

    var netFreight = Math.max(0, sampleFreight - discAmt);

    $('#res-gross').text('₹' + sampleFreight.toFixed(2));
    $('#res-discount').text('- ₹' + discAmt.toFixed(2));
    $('#res-net').text('₹' + netFreight.toFixed(2));
    $('#res-notes').html(notes);
}

function submitEditPlan() {
    var form = $('#editPlanForm')[0];
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    $('#btn-update-plan').prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Updating Plan...');

    $.ajax({
        url: 'modal/freight_discount_modal.php?action=update_plan',
        type: 'POST',
        data: $('#editPlanForm').serialize(),
        dataType: 'json',
        success: function(res) {
            $('#btn-update-plan').prop('disabled', false).html('<i class="fa fa-save mr-1"></i> Update Discount Plan');
            if (res.status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Plan Updated!',
                    text: res.message || 'Freight discount plan has been successfully updated.',
                    confirmButtonText: 'Back to Plans List'
                }).then(function() {
                    window.location.href = backUrl;
                });
            } else {
                Swal.fire('Error', res.message || 'Could not update plan', 'error');
            }
        },
        error: function() {
            $('#btn-update-plan').prop('disabled', false).html('<i class="fa fa-save mr-1"></i> Update Discount Plan');
            Swal.fire('Network Error', 'Could not connect to server.', 'error');
        }
    });
}
</script>

<?php
if (!$isDz) {
    require_once(__DIR__ . '/../footer.php');
}
?>
