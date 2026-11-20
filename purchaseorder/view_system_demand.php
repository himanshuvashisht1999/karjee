<?php
include('../header.php');
include('../krf-orderandsales/functions/product_functions.php');
include('../krf-orderandsales/functions/seed_functions.php');
include('../krf-orderandsales/functions/vendor_functions.php');
include('../krf-orderandsales/functions/orders_functions.php');

date_default_timezone_set("Asia/Calcutta");
?>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

<style>
    body, .container-fluid {
        font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
        font-size: 0.88rem !important;
        letter-spacing: -0.01em !important;
    }
    .font-monospace, .code-pill, .stock-sku, .stock-num, .sku-pill, .po-num-pill {
        font-family: 'JetBrains Mono', monospace !important;
    }
</style>

<div class="container-fluid my-4">
    <div class="card shadow-sm border-0">
            <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center py-3">
                <h3 class="card-title mb-0">
                    <i class="fa fa-calculator mr-2"></i> System Generated Purchase Demand
                </h3>
                <div>
                    <button class="btn btn-success btn-sm font-weight-bold shadow-sm mr-2" onclick="initiateBulkPO();">
                        <i class="fa fa-layer-group mr-1"></i> Bulk Create PO
                    </button>
                    <button class="btn btn-light btn-sm font-weight-bold shadow-sm" onclick="loadDemandData();">
                        <i class="fa fa-sync-alt mr-1"></i> Refresh Demand
                    </button>
                </div>
            </div>
            <div class="card-body bg-light">
                <div class="alert alert-custom alert-indicator-top indicator-primary bg-white shadow-sm mb-4" role="alert">
                    <div class="alert-content">
                        <span class="alert-title text-primary">Demand Overview</span>
                        <span class="alert-text">This list shows detailed seed requirements. Select multiple rows to combine them into one Purchase Order.</span>
                    </div>
                </div>

                <!-- Multiple Selection Filters -->
                <div class="card shadow-none border mb-4 bg-white">
                    <div class="card-body p-3">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold text-muted">Product Name</label>
                                    <select id="filter_product_name" class="form-control select2" multiple="multiple" data-placeholder="Select Products">
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold text-muted">Seed Name</label>
                                    <select id="filter_seed_name" class="form-control select2" multiple="multiple" data-placeholder="Select Seeds">
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold text-muted">Sowing Date</label>
                                    <select id="filter_sowing_date" class="form-control select2" multiple="multiple" data-placeholder="Select Dates">
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex">
                                    <button class="btn btn-primary btn-block mr-2 shadow-sm" onclick="applyFilters()">
                                        <i class="fa fa-filter mr-1"></i> Apply
                                    </button>
                                    <button class="btn btn-outline-secondary shadow-sm" onclick="clearFilters()" title="Clear Filters">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered bg-white shadow-sm" id="demandTable">
                        <thead class="thead-dark" style="position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            <tr>
                                <th width="40" class="text-center">
                                    <i class="fa fa-filter text-white-50" title="Select product to filter"></i>
                                </th>
                                <th width="50">#</th>
                                <th>Prod. Order No</th>
                                <th>Product Name</th>
                                <th>Seed Name (ID)</th>
                                <th>Vendor</th>
                                <th class="text-center">Required Qty</th>
                                <th class="text-center">Packets Needed</th>
                                <th class="text-center">Sowing Date</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                    <tbody id="demandBody">
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 mb-0">Processing production orders and generating alerts...</p>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot id="demandFoot">
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-muted small py-3 d-flex justify-content-between align-items-center flex-wrap">
            <span>* Packet calculations are based on "Count Per Packet" defined in the seed master.</span>
            <span class="font-weight-bold text-dark mt-2 mt-sm-0">Total Packets Needed: <span class="badge badge-pill badge-primary" id="cardTotalPackets" style="font-size: 0.85rem;">0 Pkts</span></span>
        </div>
    </div>
</div>
<!-- New Purchase Order Modal -->
<div class="modal fade" id="poModal" tabindex="-1" role="dialog" aria-labelledby="poModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="poModalLabel"><i class="fa fa-shopping-cart mr-2"></i> New Purchase Order Form</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body bg-light">
                <div id="poModalAlertInfo" class="alert alert-info py-2 px-3 small mb-3 border-0 shadow-sm" style="display: none; border-radius: 8px;"></div>
                <form id="poForm">
                    <input type="hidden" id="po_alert_id" name="alert_id">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-control-label">Vendor / Supplier <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="form-control" id="po_vendor_id" name="vendor_id" required>
                                        <option value="">Select Vendor</option>
                                    </select>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-primary" type="button" onclick="loadVendors()"><i class="fa fa-sync-alt"></i></button>
                                        <button class="btn btn-success" type="button"><i class="fa fa-plus"></i> Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-control-label">PO Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="po_date" name="po_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-control-label">Expected Delivery</label>
                                <input type="date" class="form-control" id="expected_delivery" name="expected_delivery">
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-3">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-primary font-weight-bold"><i class="fa fa-list mr-2"></i> Order Items</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary d-none" onclick="addPORow()"><i class="fa fa-plus mr-1"></i> Add Item Line</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-items-center table-flush mb-0" id="poItemTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 35%;">Item / Product</th>
                                        <th style="width: 15%;">Unit / Size</th>
                                        <th style="width: 15%;">Quantity (Packets)</th>
                                        <th style="width: 15%;">Unit Price (₹)</th>
                                        <th style="width: 15%;">Total (₹)</th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="poItemBody">
                                    <!-- Dynamic Rows -->
                                </tbody>
                                <tfoot>
                                    <tr class="bg-white">
                                        <td colspan="4" class="text-right font-weight-bold">Grand Total:</td>
                                        <td class="font-weight-bold text-primary" id="po_grand_total">₹0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-5" onclick="submitPO()"><i class="fa fa-check-circle mr-2"></i> Confirm & Create PO</button>
            </div>
        </div>
    </div>
</div>
<?php include('../footer.php'); ?>

<style>
    .bg-gradient-primary {
        background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%) !important;
    }
    .alert-custom {
        border-radius: 0.5rem;
        border: none;
        padding: 1.25rem;
    }
    .alert-indicator-top {
        border-top: 4px solid;
    }
    .indicator-primary {
        border-top-color: #5e72e4 !important;
    }
    .table thead th {
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        font-weight: 700;
        border: none;
    }
    .table td {
        vertical-align: middle;
        font-size: 0.875rem;
    }
    #poModal .modal-dialog {
        max-width: 95% !important;
        margin: 1.75rem auto;
    }

    /* Select2 Selection Box Styling */
    .select2-container--default .select2-selection--multiple {
        border-radius: 0.5rem !important;
        border: 1px solid #dee2e6 !important;
        min-height: 40px !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #5e72e4 !important;
    }
</style>
<script>
let globalDemandData = [];
let activeSelectedProduct = null;

$(document).ready(function() {
    loadDemandData();
    loadVendors();

    // Initialize Select2 with Multi-Select
    $('.select2').select2({
        width: '100%',
        closeOnSelect: false,
        templateResult: function(data) {
            return data.text;
        }
    });

    // Manually handle the visual tick marks for checkboxes in Select2
    $('.select2').on('select2:select select2:unselect', function (e) {
        var $select = $(this);
        var id = $select.attr('id');
        setTimeout(function() {
            var $container = $select.next('.select2-container');
        }, 0);
    });
});

function loadVendors() {
    $.ajax({
        url: 'modal/purchase_modal.php?action=getVendors',
        type: 'GET',
        success: function(response) {
            try {
                const vendors = typeof response === 'string' ? JSON.parse(response) : response;
                let options = '<option value="">Select Vendor</option>';
                vendors.forEach(v => {
                    options += `<option value="${v.VendorID}">${v.VendorName} (${v.VendorID})</option>`;
                });
                $('#po_vendor_id').html(options);
            } catch(e) { console.error("Vendor Parse Error", e); }
        }
    });
}

function loadDemandData() {
    const projectid = $('#hidden_projectid').val() || '<?php echo $_SESSION['projectid'] ?? 1; ?>';
    $.ajax({
        url: 'modal/purchase_modal.php?action=getSystemGeneratedDemand',
        type: 'POST',
        data: { projectid: projectid },
        dataType: "JSON",
        success: function(response) {
            globalDemandData = response || [];
            populateFilters(globalDemandData);
            renderDemandTable(globalDemandData);
        },
        error: function(xhr, status, error) {
            console.error(error);
            $('#demandBody').html(`<tr><td colspan="10" class="text-center py-4 text-danger"><i class="fa fa-exclamation-triangle mr-2"></i> Failed to load demand data.</td></tr>`);
            $('#demandFoot').html('');
            $('#cardTotalPackets').text('0 Pkts');
        }
    });
}

function populateFilters(data) {
    let products = new Set();
    let seeds = new Set();
    let sowingDates = new Set();

    data.forEach(row => {
        if (row.productname) products.add(row.productname.trim());
        if (row.seedname) seeds.add(row.seedname.trim());
        if (row.sowing_date) sowingDates.add(row.sowing_date.trim());
    });

    const productOptions = Array.from(products).sort().map(p => `<option value="${p}">${p}</option>`).join('');
    const seedOptions = Array.from(seeds).sort().map(s => `<option value="${s}">${s}</option>`).join('');
    
    const dateOptions = Array.from(sowingDates).sort().map(d => {
        return `<option value="${d}">${changeDateFormatYmd_dMY(d)}</option>`;
    }).join('');

    $('#filter_product_name').html(productOptions).trigger('change');
    $('#filter_seed_name').html(seedOptions).trigger('change');
    $('#filter_sowing_date').html(dateOptions).trigger('change');
}

function renderDemandTable(data) {
    let html = '';
    let footHtml = '';
    let totalPackets = 0;
    let totalDemandQty = 0;

    if (data && data.length > 0) {
        data.forEach((row, index) => {
            const packets = parseInt(row.packets_needed, 10) || 0;
            const demandQty = parseFloat(row.TotalDemandQty) || 0;
            totalPackets += packets;
            totalDemandQty += demandQty;
            const pName = (row.productname || '').trim();
            const isChecked = activeSelectedProduct !== null && activeSelectedProduct === pName;

            html += `<tr data-alert='${JSON.stringify(row).replace(/'/g, "&apos;")}'>
                <td class="text-center">
                    <input type="checkbox" class="demand-check" value="${row.id}" ${isChecked ? 'checked' : ''} onchange="toggleProductFilter(this, '${encodeURIComponent(pName)}')">
                </td>
                <td>${index + 1}</td>
                <td class="font-weight-bold text-dark"><code>${row.stockorder_no}</code></td>
                <td>${row.productname}</td>
                <td class="text-primary font-weight-bold">${row.seedname}</td>
                <td><span class="badge badge-outline-dark">${row.vendor_id || 'Not Set'}</span></td>
                <td class="text-center font-weight-bold">${demandQty.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>
                <td class="text-center">
                    <span class="badge badge-pill badge-primary">${packets} Pkts</span>
                </td>
                <td class="text-center font-weight-bold text-success">${changeDateFormatYmd_dMY(row.sowing_date)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-primary rounded-circle" onclick="initiatePOFromAlert(${row.id})" title="Create PO for this Product">
                        <i class="fa fa-shopping-cart"></i>
                    </button>
                </td>
            </tr>`;
        });

        footHtml = `<tr class="bg-light font-weight-bold" style="border-top: 2px solid #dee2e6;">
            <td colspan="6" class="text-right text-uppercase font-weight-bold text-dark" style="letter-spacing: 0.05em; vertical-align: middle;">Total:</td>
            <td class="text-center font-weight-bold text-dark" style="vertical-align: middle;">${totalDemandQty.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>
            <td class="text-center font-weight-bold" style="vertical-align: middle;">
                <span class="badge badge-pill badge-primary" style="font-size: 0.875rem; padding: 6px 12px;">${totalPackets.toLocaleString()} Pkts</span>
            </td>
            <td colspan="2"></td>
        </tr>`;
    } else {
        html = `<tr><td colspan="10" class="text-center py-4 text-muted"><i class="fa fa-info-circle mr-2"></i> No matching demand found.</td></tr>`;
        footHtml = '';
    }
    $('#demandBody').html(html);
    $('#demandFoot').html(footHtml);
    $('#cardTotalPackets').text(`${totalPackets.toLocaleString()} Pkts`);
}

function toggleProductFilter(checkbox, encodedProductName) {
    const productName = decodeURIComponent(encodedProductName);
    if (checkbox.checked) {
        activeSelectedProduct = productName;
    } else {
        activeSelectedProduct = null;
    }
    applyFilters();
}

function applyFilters() {
    const selectedProducts = $('#filter_product_name').val() || [];
    const selectedSeeds = $('#filter_seed_name').val() || [];
    const selectedDates = $('#filter_sowing_date').val() || [];

    const filteredData = globalDemandData.filter(row => {
        let matchProduct = true;
        let matchSeed = true;
        let matchDate = true;
        let matchCheckboxProduct = true;

        if (activeSelectedProduct) {
            matchCheckboxProduct = (row.productname ? row.productname.trim() : '') === activeSelectedProduct;
        }

        if (selectedProducts.length > 0) {
            matchProduct = selectedProducts.includes(row.productname ? row.productname.trim() : '');
        }
        if (selectedSeeds.length > 0) {
            matchSeed = selectedSeeds.includes(row.seedname ? row.seedname.trim() : '');
        }
        if (selectedDates.length > 0) {
            matchDate = selectedDates.includes(row.sowing_date ? row.sowing_date.trim() : '');
        }

        return matchCheckboxProduct && matchProduct && matchSeed && matchDate;
    });

    renderDemandTable(filteredData);
}

function clearFilters() {
    activeSelectedProduct = null;
    $('#filter_product_name').val([]).trigger('change');
    $('#filter_seed_name').val([]).trigger('change');
    $('#filter_sowing_date').val([]).trigger('change');
    renderDemandTable(globalDemandData);
}

function initiateBulkPO() {
    let targetRows = [];
    if (activeSelectedProduct) {
        targetRows = globalDemandData.filter(r => (r.productname ? r.productname.trim() : '') === activeSelectedProduct);
    } else {
        const checked = $('.demand-check:checked');
        if (checked.length === 0) {
            Swal.fire('No Selection', 'Please select a product checkbox to create a Purchase Order.', 'warning');
            return;
        }
        checked.each(function() {
            const rowData = JSON.parse($(this).closest('tr').attr('data-alert'));
            targetRows.push(rowData);
        });
    }

    if (targetRows.length === 0) {
        Swal.fire('No Selection', 'Please select a product checkbox.', 'warning');
        return;
    }

    $('#poForm')[0].reset();
    $('#poItemBody').empty();

    const seedGroups = {};
    const alertIds = [];
    const prodOrderNos = new Set();
    let firstVendor = '';
    let targetProductName = '';

    targetRows.forEach(rowData => {
        alertIds.push(rowData.id);
        if (rowData.stockorder_no) prodOrderNos.add(rowData.stockorder_no);
        if (!firstVendor && rowData.vendor_id) firstVendor = rowData.vendor_id;
        if (!targetProductName && rowData.productname) targetProductName = rowData.productname;

        const sId = rowData.seedid;
        const packets = parseInt(rowData.packets_needed, 10) || 0;
        const uPrice = parseFloat(rowData.unit_price) || 0;
        if (!seedGroups[sId]) {
            seedGroups[sId] = {
                seedid: sId,
                seedname: rowData.seedname,
                packets: 0,
                unit_price: uPrice
            };
        }
        seedGroups[sId].packets += packets;
        if ((!seedGroups[sId].unit_price || seedGroups[sId].unit_price <= 0) && uPrice > 0) {
            seedGroups[sId].unit_price = uPrice;
        }
    });

    $('#po_alert_id').val(alertIds.join(','));
    $('#po_vendor_id').val(firstVendor);

    let totalPacketsCombined = 0;
    Object.values(seedGroups).forEach(group => {
        totalPacketsCombined += group.packets;
        addPORow(group.seedid, group.packets, group.seedname, group.unit_price || 0);
    });

    const orderBadges = Array.from(prodOrderNos).map(no => `<span class="badge badge-secondary mr-1">${no}</span>`).join(' ');
    $('#poModalAlertInfo').html(`
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong>Product:</strong> <span class="text-primary font-weight-bold">${targetProductName || 'Selected Product'}</span>
                <div class="mt-1">
                    <strong>Linked Production Orders (${prodOrderNos.size}):</strong> ${orderBadges}
                </div>
            </div>
            <div class="text-right mt-2 mt-md-0">
                <span class="badge badge-pill badge-primary p-2" style="font-size: 0.9rem;">Total: ${totalPacketsCombined.toLocaleString()} Pkts</span>
            </div>
        </div>
    `).show();

    $('#poModal').modal('show');
}

function initiatePOFromAlert(alertId) {
    const targetRow = globalDemandData.find(r => r.id == alertId);
    if (!targetRow) {
        Swal.fire('Error', 'Demand alert not found.', 'error');
        return;
    }

    $('#poForm')[0].reset();
    $('#poItemBody').empty();

    const targetProduct = (targetRow.productname || '').trim();
    // Find all demand items with the same product name (or same seed if product name is empty)
    const matchingRows = globalDemandData.filter(r => {
        if (targetProduct && r.productname) {
            return r.productname.trim() === targetProduct;
        }
        return r.seedid == targetRow.seedid;
    });

    const seedGroups = {};
    const alertIds = [];
    const prodOrderNos = new Set();
    let vendorId = targetRow.vendor_id || '';

    matchingRows.forEach(r => {
        alertIds.push(r.id);
        if (r.stockorder_no) prodOrderNos.add(r.stockorder_no);
        if (!vendorId && r.vendor_id) vendorId = r.vendor_id;

        const sId = r.seedid;
        const packets = parseInt(r.packets_needed, 10) || 0;
        const uPrice = parseFloat(r.unit_price) || 0;
        if (!seedGroups[sId]) {
            seedGroups[sId] = {
                seedid: sId,
                seedname: r.seedname,
                packets: 0,
                unit_price: uPrice
            };
        }
        seedGroups[sId].packets += packets;
        if ((!seedGroups[sId].unit_price || seedGroups[sId].unit_price <= 0) && uPrice > 0) {
            seedGroups[sId].unit_price = uPrice;
        }
    });

    $('#po_alert_id').val(alertIds.join(','));
    $('#po_vendor_id').val(vendorId);

    // Populate rows
    let totalPacketsCombined = 0;
    Object.values(seedGroups).forEach(group => {
        totalPacketsCombined += group.packets;
        addPORow(group.seedid, group.packets, group.seedname, group.unit_price || 0);
    });

    const orderBadges = Array.from(prodOrderNos).map(no => `<span class="badge badge-secondary mr-1">${no}</span>`).join(' ');
    $('#poModalAlertInfo').html(`
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong>Product:</strong> <span class="text-primary font-weight-bold">${targetProduct || targetRow.seedname}</span>
                <div class="mt-1">
                    <strong>Linked Production Orders (${prodOrderNos.size}):</strong> ${orderBadges}
                </div>
            </div>
            <div class="text-right mt-2 mt-md-0">
                <span class="badge badge-pill badge-primary p-2" style="font-size: 0.9rem;">Total: ${totalPacketsCombined.toLocaleString()} Pkts</span>
            </div>
        </div>
    `).show();

    $('#poModal').modal('show');
}

function addPORow(seedId = '', packets = 1, seedName = 'Select Item', unitPrice = 0) {
    const rowId = Date.now() + Math.floor(Math.random() * 1000);
    const numPrice = parseFloat(unitPrice) || 0;
    const priceVal = (numPrice > 0) ? numPrice.toFixed(2) : '';
    const lineTotal = (packets * numPrice).toFixed(2);
    const rowHtml = `<tr id="row_${rowId}">
        <td>
            <select class="form-control po-item-select" name="seed_id[]">
                <option value="${seedId}">${seedName}</option>
            </select>
        </td>
        <td><input type="text" class="form-control po-unit" name="unit[]" readonly value="Packet"></td>
        <td><input type="number" class="form-control po-qty" name="quantity[]" value="${packets}" oninput="calculateRowTotal(${rowId})"></td>
        <td><input type="number" class="form-control po-price" name="price[]" value="${priceVal}" placeholder="0.00" step="0.01" oninput="calculateRowTotal(${rowId})"></td>
        <td class="font-weight-bold text-dark row-total">₹${lineTotal}</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removePORow(${rowId})"><i class="fa fa-trash"></i></button></td>
    </tr>`;
    $('#poItemBody').append(rowHtml);
    updateGrandTotal();
}

function removePORow(rowId) {
    $(`#row_${rowId}`).remove();
    updateGrandTotal();
}

function calculateRowTotal(rowId) {
    const qty = parseFloat($(`#row_${rowId} .po-qty`).val()) || 0;
    const price = parseFloat($(`#row_${rowId} .po-price`).val()) || 0;
    const total = qty * price;
    $(`#row_${rowId} .row-total`).text('₹' + total.toFixed(2));
    updateGrandTotal();
}

function updateGrandTotal() {
    let grandTotal = 0;
    $('.row-total').each(function() {
        grandTotal += parseFloat($(this).text().replace('₹', '')) || 0;
    });
    $('#po_grand_total').text('₹' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2}));
}

function submitPO() {
    const vendorId = $('#po_vendor_id').val();
    if(!vendorId) {
        Swal.fire('Error', 'Please select a vendor.', 'error');
        return;
    }

    const formData = $('#poForm').serialize();
    Swal.fire({
        title: 'Confirm Purchase Order',
        text: 'Are you sure you want to create this PO?',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Yes, Create It'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'modal/purchase_modal.php?action=saveNewPurchaseOrder',
                type: 'POST',
                data: formData + '&projectid=<?php echo $_SESSION['projectid'] ?? 1; ?>',
                success: function(response) {
                    try {
                        const res = typeof response === 'string' ? JSON.parse(response) : response;
                        if(res.status === 'success') {
                            const poNum = res.po_number || '';
                            const ordersText = res.orders && res.orders.length > 0 ? `<div class="mt-2 text-muted small">Updated Production Orders: <strong>${res.orders.join(', ')}</strong></div>` : '';
                            Swal.fire({
                                title: 'PO Created Successfully!',
                                html: `Purchase Order <strong>${poNum}</strong> created successfully.${ordersText}`,
                                icon: 'success'
                            });
                            $('#poModal').modal('hide');
                            loadDemandData();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    } catch(e) { Swal.fire('Error', 'Invalid response from server.', 'error'); }
                }
            });
        }
    });
}

function changeDateFormatYmd_dMY(dateStr) {
    if (!dateStr || typeof dateStr !== 'string' || dateStr === '0000-00-00' || dateStr.includes('0000-00-00')) return 'N/A';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    return `${date.getDate().toString().padStart(2, '0')}-${months[date.getMonth()]}-${date.getFullYear()}`;
}
</script>
