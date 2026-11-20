<?php
include('../header.php');
include('functions/product_functions.php');
include('functions/vendor_functions.php');
include('functions/seed_functions.php');
date_default_timezone_set("Asia/Calcutta");
$mdid = "";
if (isset($_GET['mdid'])) {
    $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
    $groupid = $dddr[0]['groupid'];
}
?>
<!-- Google Fonts: Outfit -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome & Bootstrap Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<style>
    :root {
        --primary-blue: #0d6efd;
        --secondary-blue: #6ea8fe;
        --primary-green: #1b9e77;
        --secondary-green: #4ae0b1;
        --accent-orange: #fda085;
        --glass-bg: rgba(255, 255, 255, 0.9);
        --glass-border: rgba(255, 255, 255, 0.2);
        --card-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.08);
        --text-dark: #1e293b;
        --text-muted: #64748b;
    }

    body {
        font-family: 'Outfit', sans-serif;
        background-color: #f0f4f8;
        color: var(--text-dark);
    }

    .premium-container {
        padding: 1rem;
        max-width: 1400px;
        margin: auto;
        padding-bottom: 120px;
    }

    /* --- Page Header --- */
    .page-header-card {
        background: linear-gradient(135deg, #1b9e77 0%, #0d6efd 100%);
        border-radius: 20px;
        padding: 0.75rem 1.5rem;
        color: white;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 25px rgba(27, 158, 119, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: none;
    }

    .page-header-card h5 {
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.5px;
        text-transform: uppercase;
        font-size: 1.15rem;
    }

    .btn-refresh {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 12px;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .btn-refresh:hover {
        background: white;
        color: var(--primary-blue);
        transform: translateY(-2px);
    }

    /* --- Content Cards --- */
    .content-card {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        border-radius: 24px;
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 2.5rem !important;
    }

    .card-title-premium {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .filter-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-bottom: 0.5rem;
        display: block;
    }

    /* Custom Checkbox / Radio styling */
    .position-radio-group {
        display: flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 12px;
        gap: 4px;
    }

    .position-radio-item {
        flex: 1;
    }

    .position-radio-item input {
        display: none;
    }

    .position-radio-item label {
        display: block;
        text-align: center;
        padding: 8px 4px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.8rem;
        color: #64748b;
        transition: all 0.2s ease;
        margin-bottom: 0;
    }

    .position-radio-item input#posStart:checked+label {
        background: #ffc107 !important;
        color: #000 !important;
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
    }

    .position-radio-item input#posMiddle:checked+label {
        background: #0d6efd !important;
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    }

    .position-radio-item input#posEnd:checked+label {
        background: #198754 !important;
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
    }

    .btn-allocate {
        background: linear-gradient(135deg, #198754 0%, #1b9e77 100%);
        color: white;
        font-weight: 700;
        border: none;
        border-radius: 12px;
        padding: 12px;
        box-shadow: 0 4px 15px rgba(25, 135, 84, 0.25);
        transition: all 0.3s ease;
    }

    .btn-allocate:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(25, 135, 84, 0.4);
        color: white;
    }

    /* Box list styled items */
    .box-item-container {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 15px;
        margin-bottom: 10px;
        transition: all 0.2s ease;
    }

    .box-item-container:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .box-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
</style>

<div class="premium-container" data-routurl="<?php echo $prefix; ?>" data-projectid="<?php echo $projectid; ?>" data-companyid="<?php echo $companyid; ?>" data-subcompanyid="<?php echo $subcompanyid; ?>" data-loginid="<?php echo $loginid; ?>">
    <!-- Page Header -->
    <div class="page-header-card shadow-lg">
        <div class="d-flex align-items-center gap-3">
            <div class="header-icon-circle bg-white text-primary d-flex align-items-center justify-content-center rounded-circle"
                style="width: 40px; height: 40px; font-size: 1.25rem;">
                <i class="bi bi-box-arrow-in-down"></i>
            </div>
            <h5>Manual Polyhouse Allocation & Bed Allocation</h5>
        </div>
        <button class="btn btn-refresh" onclick="window.location.reload();">
            <i class="fa fa-sync me-2"></i> Refresh
        </button>
    </div>

    <div class="row g-4">
        <!-- Manual Allocation Input Form -->
        <div class="col-lg-6">
            <div class="content-card shadow-sm">
                <!-- Step 1: Select Stock Order No -->
                <div class="card-title-premium border-bottom pb-2 mb-3">
                    <i class="bi bi-list-ol text-primary"></i> Step 1: Choose Stock Order
                </div>
                <div class="mb-3">
                    <label class="filter-label" for="stockOrderSelection">Select Production / Stock Order No</label>
                    <select id="stockOrderSelection" class="form-select border-primary-subtle" style="border-radius: 12px; height: 48px;">
                        <option value="">Loading stock orders...</option>
                    </select>
                </div>

                <!-- Product Name preview under stock order -->
                <div id="productNamePreview" class="alert alert-info py-2 px-3 d-none mb-3" style="border-radius: 10px;">
                    <i class="bi bi-info-circle me-1"></i> Product: <strong id="previewProdName">-</strong>
                </div>

                <!-- Step 2: Choose Box No Multiple -->
                <div id="step2BoxContainer" class="d-none mt-4">
                    <div class="card-title-premium border-bottom pb-2 mb-3">
                        <i class="bi bi-boxes text-primary"></i> Step 2: Select Box(es) & Enter Quantities
                    </div>
                    <div id="boxListContainer" class="mb-3" style="max-height: 300px; overflow-y: auto; padding-right: 5px;">
                        <!-- Dynamic Checkbox list injected here -->
                    </div>
                </div>

                <!-- Total trays count indicator and pending boxes -->
                <div id="selectedTraysCounterContainer" class="d-none mt-4 p-3 bg-light border border-primary-subtle rounded-3">
                    <div class="row text-center">
                        <div class="col-md-6 border-end">
                            <div class="h6 text-muted mb-1">TOTAL SELECTED TRAYS</div>
                            <div class="h4 mb-0 text-primary fw-bold" id="selectedTraysTotalSpan">0</div>
                        </div>
                        <div class="col-md-6">
                            <div class="h6 text-muted mb-1">PENDING BOXES FOR ALLOCATION</div>
                            <div class="h4 mb-0 text-warning fw-bold" id="pendingBoxesTotalSpan">0</div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Location / Polyhouse / Bed / Position -->
                <div id="targetLocationContainer" class="d-none mt-4">
                    <div class="card-title-premium border-bottom pb-2 mb-3">
                        <i class="bi bi-geo-alt-fill text-success"></i> Step 3: Target Location & Capacity
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="filter-label">Sowing Location</label>
                            <select id="targetLocationList" class="form-select border-primary-subtle" style="border-radius:10px;"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="filter-label">Target Polyhouse</label>
                            <select id="targetPolyhouseList" class="form-select border-primary-subtle" style="border-radius:10px;">
                                <option value="">Select Polyhouse</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="bedSelectionContainer">
                        <label class="filter-label">Target Bed Name/No</label>
                        <select id="targetBedList" class="form-select border-primary-subtle" style="border-radius:10px; height: 42px;">
                            <option value="">Select Bed</option>
                        </select>
                    </div>

                    <div id="allocationControls" class="d-none">
                        <div class="mb-4">
                            <label class="filter-label mb-2">Position in Bed <span class="text-danger">*</span></label>
                            <div class="position-radio-group">
                                <div class="position-radio-item">
                                    <input type="radio" name="trayPosition" id="posStart" value="Start">
                                    <label for="posStart">Start</label>
                                </div>
                                <div class="position-radio-item">
                                    <input type="radio" name="trayPosition" id="posMiddle" value="Middle">
                                    <label for="posMiddle">Middle</label>
                                </div>
                                <div class="position-radio-item">
                                    <input type="radio" name="trayPosition" id="posEnd" value="End">
                                    <label for="posEnd">End</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-allocate" id="submitManualAllocation">
                                <i class="bi bi-check-circle-fill me-2"></i> ALLOCATE AND SAVE TRAYS
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Target Polyhouse Capacity & Details Grid -->
        <div class="col-lg-6">
            <div class="content-card shadow-sm h-100">
                <div class="card-title-premium border-bottom pb-2 mb-3">
                    <i class="bi bi-building-check text-primary"></i> Target Polyhouse Capacity & Status
                </div>
                <div id="capacityGridDiv" class="table-responsive">
                    <p class="text-center text-muted py-5">
                        <i class="bi bi-info-circle d-block mb-2" style="font-size: 2rem;"></i>
                        Select a target polyhouse to view its capacity grid.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript" src="<?php echo $prefix;?>krf-orderandsales/js/pjs_function.js"></script>

<script>
$(document).ready(function() {
    const rooturl = $(".premium-container").data("routurl");
    const projectid = $(".premium-container").data("projectid");
    const companyid = $(".premium-container").data("companyid");
    const subcompanyid = $(".premium-container").data("subcompanyid");
    const loginid = $(".premium-container").data("loginid");

    let stockOrdersList = [];
    let boxesByStockOrder = [];
    let bedRemainingCapacity = {};

    // 1. Fetch allocated stock orders dropdown list
    function loadAllocatedStockOrders() {
        $("#stockOrderSelection").html('<option value="">Loading stock orders...</option>');
        $.ajax({
            url: rooturl + "krf-orderandsales/modal/polihouse_modal.php?action=GetAllocatedStockOrdersList",
            type: "POST",
            data: { projectid },
            dataType: "json",
            success: function (res) {
                console.log(res);
                stockOrdersList = res;
                let options = '<option value="">-- Choose Stock Order --</option>';
                if (res && res.length > 0) {
                 res.forEach(item => {
                        options += `<option value="${item.stockorderno}|${item.sowinglotno_internal}">${item.stockorderno} (${item.productname}) | Lot: ${item.sowinglotno_internal} | Sowing: ${item.sowingdate_internal} | Opening: ${item.boxopeningdate}</option>`;
                    });
                } else {
                    options = '<option value="">No allocated stock orders found</option>';
                }
                $("#stockOrderSelection").html(options);
            },
            error: function () {
                $("#stockOrderSelection").html('<option value="">Error loading stock orders</option>');
            }
        });
    }

    function LoadProductionLocations(projectid, selectedId, dropdownid) {
        $.ajax({
            url: rooturl + "krf-orderandsales/modal/product_modal.php?action=GetProductionLocationList",
            type: "POST",
            data: { projectid: projectid, companyid: companyid, subcompanyid: subcompanyid },
            dataType: "json",
            success: function (res) {
                let options = '<option value="">All Locations</option>';
                if (res && res.length > 0) {
                    res.forEach(loc => {
                        options += `<option value="${loc.LocationId}">${loc.LocationName}</option>`;
                    });
                }
                $("#" + dropdownid).html(options);
                if (selectedId) {
                    $("#" + dropdownid).val(selectedId).trigger("change");
                }
            }
        });
    }

    loadAllocatedStockOrders();
    LoadProductionLocations(projectid, '', 'targetLocationList');

    // Stock Order selection change handler
    $("#stockOrderSelection").on("change", function () {
        const selectedVal = $(this).val();
        // Reset selected trays display
        $("#selectedTraysCounterContainer").addClass("d-none");
        $("#selectedTraysTotalSpan").text(0);

        if (!selectedVal) {
            $("#productNamePreview").addClass("d-none");
            $("#step2BoxContainer").addClass("d-none");
            $("#targetLocationContainer").addClass("d-none");
            return;
        }

        const parts = selectedVal.split('|');
        const stockOrderNo = parts[0];
        const sowingLotNo = parts[1];

        const selectedOrder = stockOrdersList.find(o => o.stockorderno === stockOrderNo && o.sowinglotno_internal === sowingLotNo);
        if (selectedOrder) {
            $("#previewProdName").html(`${selectedOrder.productname} <br><small class="text-muted">Lot: ${selectedOrder.sowinglotno_internal} | Sowing Date: ${selectedOrder.sowingdate_internal} | Box Opening Date: ${selectedOrder.boxopeningdate}</small>`);
            $("#productNamePreview").removeClass("d-none");
            
            // Load boxes for this stock order and lot
            loadBoxesForStockOrder(stockOrderNo, sowingLotNo);
        }
    });

    function loadBoxesForStockOrder(stockOrderNo, sowingLotNo) {
        Swal.fire({
            title: 'Fetching Data...',
            html: 'Please wait while box details are being retrieved.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: rooturl + "krf-orderandsales/modal/polihouse_modal.php?action=GetBoxesByStockOrder",
            type: "POST",
            data: { projectid, stockorderno: stockOrderNo, sowinglotno_internal: sowingLotNo },
            dataType: "json",
            success: function (res) {
                Swal.close();
                boxesByStockOrder = res;
                let html = '';
                if (res && res.length > 0) {
                    res.forEach(box => {
                        html += `
                            <div class="box-item-container d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <input type="checkbox" class="box-checkbox" id="chk_box_${box.boxid}" value="${box.boxid}">
                                    <label class="mb-0 cursor-pointer" for="chk_box_${box.boxid}" style="cursor:pointer;">
                                        <strong>Box ID: #${box.boxid}</strong> <br>
                                        <small class="text-muted">Type: ${box.roottype} | Lot: ${box.sowinglotno_internal} | Max Trays: ${box.available_trays}</small>
                                    </label>
                                </div>
                                <div class="d-flex align-items-center gap-2" style="max-width: 140px;">
                                    <span class="text-secondary" style="font-size:0.8rem">Trays:</span>
                                    <input type="number" class="form-control form-control-sm box-qty-input" 
                                           id="qty_box_${box.boxid}" 
                                           min="1" 
                                           max="${box.available_trays}" 
                                           value="${box.available_trays}" 
                                           disabled 
                                           style="width: 70px; text-align: center;">
                                </div>
                            </div>
                        `;
                    });
                    
                    $("#boxListContainer").html(html);
                    $("#step2BoxContainer").removeClass("d-none");
                    $("#targetLocationContainer").removeClass("d-none");

                    if (res[0] && res[0].polyhouse_locationid) {
                        $("#targetLocationList").val(res[0].polyhouse_locationid).trigger("change");
                    }
                    updateSelectedTraysTotal();
                } else {
                    $("#boxListContainer").html('<p class="text-danger">No allocated boxes found for this stock order.</p>');
                }
            },
            error: function () {
                Swal.close();
                Swal.fire('Error', 'Failed to retrieve box details', 'error');
            }
        });
    }

    // Helper to calculate and display selected trays count and pending boxes
    function updateSelectedTraysTotal() {
        let total = 0;
        let checkedCount = 0;
        const totalBoxes = $(".box-checkbox").length;

        $(".box-checkbox:checked").each(function() {
            const boxId = $(this).val();
            const qtyVal = parseInt($(`#qty_box_${boxId}`).val()) || 0;
            total += qtyVal;
            checkedCount++;
        });

        const pendingBoxes = totalBoxes - checkedCount;

        if (totalBoxes > 0) {
            $("#selectedTraysCounterContainer").removeClass("d-none");
            $("#selectedTraysTotalSpan").text(total);
            $("#pendingBoxesTotalSpan").text(pendingBoxes);
        } else {
            $("#selectedTraysCounterContainer").addClass("d-none");
        }
    }

    // Toggle input field on box checkbox check
    $(document).on("change", ".box-checkbox", function() {
        const boxId = $(this).val();
        const isChecked = $(this).is(":checked");
        $(`#qty_box_${boxId}`).prop("disabled", !isChecked);
        updateSelectedTraysTotal();
    });

    $(document).on("input change", ".box-qty-input", function() {
        updateSelectedTraysTotal();
    });

    // Populate Location master
    LoadProductionLocations(projectid, '', 'targetLocationList');

    $(document).on("change", "#targetLocationList", function () {
        const locId = $(this).val();
        if (!locId) {
            $("#targetPolyhouseList").html('<option value="">Select Polyhouse</option>');
            $("#capacityGridDiv").html('<p class="text-center text-muted py-5">Select a target polyhouse to view its capacity grid.</p>');
            return;
        }
        GetPolihouseDetailsList('locationid', locId, '', 'targetPolyhouseList', function() {
            if (boxesByStockOrder[0] && boxesByStockOrder[0].polyhouseid) {
                $("#targetPolyhouseList").val(boxesByStockOrder[0].polyhouseid).trigger("change");
            }
        });
    });

    $(document).on("change", "#targetPolyhouseList", function () {
        const phId = $(this).val();
        const locId = $("#targetLocationList").val();
        if (!phId) {
            $("#capacityGridDiv").html('<p class="text-center text-muted py-5">Select a target polyhouse to view its capacity grid.</p>');
            $("#bedSelectionContainer").addClass("d-none");
            return;
        }
        fetchPolyhouseCapacity(phId, locId);
    });

    function fetchPolyhouseCapacity(phId, locId) {
        $.ajax({
            url: rooturl + "krf-orderandsales/modal/polihouse_modal.php?action=PolyhouseCapacitydetails",
            type: "POST",
            data: { projectid, locationid: locId, polyhouseid: phId },
            dataType: "json",
            success: function (res) {
                if (!res || !res.bed_wise_details) return;

                let html = `<table class="table table-sm table-bordered mt-2">
                    <thead class="table-light">
                        <tr>
                            <th>Bed Name/No</th>
                            <th>Total Capacity</th>
                            <th>Trays Exist</th>
                            <th>Available Space</th>
                        </tr>
                    </thead>
                    <tbody>`;

                let bedOptions = '<option value="">-- Choose Bed --</option>';
                bedRemainingCapacity = {};

                res.bed_wise_details.forEach(bed => {
                    bedRemainingCapacity[bed.bed_no] = bed.remaining_trays;
                    bedOptions += `<option value="${bed.bed_no}">${bed.bed_no} (Space: ${bed.remaining_trays})</option>`;

                    html += `<tr>
                        <td><div class="fw-bold">${bed.bed_no}</div></td>
                        <td>${bed.total_capacity}</td>
                        <td class="text-danger">${bed.used_trays}</td>
                        <td class="text-success fw-bold">${bed.remaining_trays}</td>
                    </tr>`;
                });

                html += `</tbody></table>`;
                $("#capacityGridDiv").html(html);

                // Populate Target Bed List dropdown
                $("#targetBedList").html(bedOptions);
                $("#bedSelectionContainer").removeClass("d-none");
            }
        });
    }

    $("#targetBedList").on("change", function() {
        const bedVal = $(this).val();
        if (bedVal) {
            $("#allocationControls").removeClass("d-none");
        } else {
            $("#allocationControls").addClass("d-none");
        }
    });

    // Handle Manual Allocation Submission
    $("#submitManualAllocation").on("click", function () {
        const bedNo = $("#targetBedList").val();
        const phId = $("#targetPolyhouseList").val();
        const trayPosition = $("input[name='trayPosition']:checked").val();

        // Collect all checked boxes and their quantities
        const allocations = [];
        let totalQty = 0;
        let validationError = null;

        $(".box-checkbox:checked").each(function() {
            const boxId = $(this).val();
            const qtyVal = parseInt($(`#qty_box_${boxId}`).val());
            const boxObj = boxesByStockOrder.find(b => b.boxid == boxId);
            
            if (!qtyVal || qtyVal <= 0) {
                validationError = `Please enter a valid tray count for Box #${boxId}`;
                return false;
            }
            if (qtyVal > boxObj.available_trays) {
                validationError = `Trays count for Box #${boxId} exceeds maximum available (${boxObj.available_trays})`;
                return false;
            }

            allocations.push({
                boxid: parseInt(boxId),
                qty: qtyVal
            });
            totalQty += qtyVal;
        });

        if (validationError) {
            iziToast.error({ title: 'Validation Error', message: validationError, position: 'topRight' });
            return;
        }

        if (allocations.length === 0) {
            iziToast.error({ title: 'No Box Selected', message: 'Please select at least one Germination Box.', position: 'topRight' });
            return;
        }

        const bedSpace = bedRemainingCapacity[bedNo] || 0;
        if (totalQty > bedSpace) {
            iziToast.error({ title: 'Exceeds Capacity', message: `Total trays to allocate (${totalQty}) exceeds available bed space (${bedSpace}).`, position: 'topRight' });
            return;
        }

        if (!trayPosition) {
            iziToast.error({ title: 'Selection Missing', message: 'Please select a Position (Start, Middle, or End).', position: 'topRight' });
            return;
        }

        Swal.fire({
            title: 'Confirm Allocation',
            text: `Are you sure you want to allocate a total of ${totalQty} trays from the selected boxes to Bed ${bedNo}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Allocate'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                
                $.ajax({
                    url: rooturl + "krf-orderandsales/modal/polihouse_modal.php?action=UpdateMultipleManualTrayAllocationsToPolyhouse",
                    type: "POST",
                    data: {
                        companyid,
                        subcompanyid,
                        projectid,
                        loginid,
                        polyhouseid: phId,
                        bedrackname: bedNo,
                        placeofrow: trayPosition,
                        allocations: JSON.stringify(allocations)
                    },
                    dataType: "json",
                    success: function (res) {
                        console.log(res);
                        Swal.close();
                        if (res.status === "success") {
                            iziToast.success({ title: 'Allocated!', message: res.message, position: 'topRight' });
                            
                            // Reset controls and reload data
                            $("input[name='trayPosition']").prop("checked", false);
                            $("#stockOrderSelection").val('').trigger("change");
                            
                            loadAllocatedStockOrders();
                            fetchPolyhouseCapacity(phId, $("#targetLocationList").val());
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.close();
                        Swal.fire('Error', 'Failed to communicate with server', 'error');
                    }
                });
            }
        });
    });
});
</script>
<?php
include('../footer.php');
?>
