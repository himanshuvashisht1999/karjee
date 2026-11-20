<?php
include('../header.php');
include('../krf-orderandsales/functions/product_functions.php');
include('../krf-orderandsales/functions/seed_functions.php');
include('../krf-orderandsales/functions/vendor_functions.php');
include('../krf-orderandsales/functions/orders_functions.php');

date_default_timezone_set("Asia/Calcutta");
?>

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

                <!-- Filters Section -->
                <div class="card mb-4 shadow-sm border-0">
                    <div class="card-header bg-light py-2 border-bottom-0">
                        <h6 class="mb-0 text-primary font-weight-bold"><i class="fa fa-filter mr-2"></i>Filter Demand Data</h6>
                    </div>
                    <div class="card-body py-3">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold text-muted">Product Name</label>
                                    <select class="form-control select2" id="filter_product_name" multiple="multiple" data-placeholder="Select Products" onchange="applyFilters()">
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold text-muted">Seed Name</label>
                                    <select class="form-control select2" id="filter_seed_name" multiple="multiple" data-placeholder="Select Seeds" onchange="applyFilters()">
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold text-muted">Sowing Date</label>
                                    <select class="form-control select2" id="filter_sowing_date" multiple="multiple" data-placeholder="Select Dates" onchange="applyFilters()">
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-2 border-top text-right">
                        <button class="btn btn-sm btn-outline-secondary" onclick="clearFilters()"><i class="fa fa-times mr-1"></i> Clear Filters</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered bg-white shadow-sm" id="demandTable">
                        <thead class="thead-dark">
                            <tr>
                                <th width="40" class="text-center">
                                    <input type="checkbox" id="selectAllDemand" onclick="toggleAllDemand(this)">
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
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-muted small py-3">
            * Packet calculations are based on "Count Per Packet" defined in the seed master.
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
</style>
<script>
let globalDemandData = [];

$(document).ready(function() {
    loadDemandData();
    loadVendors();
});

function loadVendors() {
    $.ajax({
        url: '../krf-orderandsales/modal/purchase_modal.php?action=getVendors',
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
    console.log("Loading demand for Project ID:", projectid);
    $.ajax({
        url: '../krf-orderandsales/modal/purchase_modal.php?action=getSystemGeneratedDemand',
        type: 'POST',
        data: { projectid: projectid },
        dataType: "JSON",
        success: function(response) {
            try {
                const data = response;
                globalDemandData = data || [];
                populateFilterOptions(globalDemandData);
                renderDemandTable(globalDemandData);
            } catch(e) {
                console.error("Demand Parse Error", e);
                $('#demandBody').html(`<tr><td colspan="10" class="text-center py-4 text-danger"><i class="fa fa-exclamation-triangle mr-2"></i> Error parsing demand data.</td></tr>`);
            }
        },
        error: function(xhr, status, error) {
            console.error(error);
            $('#demandBody').html(`<tr><td colspan="10" class="text-center py-4 text-danger"><i class="fa fa-exclamation-triangle mr-2"></i> Failed to load demand data.</td></tr>`);
        }
    });
}

function renderDemandTable(data) {
    let html = '';
    if (data && data.length > 0) {
        data.forEach((row, index) => {
            html += `<tr data-alert='${JSON.stringify(row).replace(/'/g, "&apos;")}'>
                <td class="text-center">
                    <input type="checkbox" class="demand-check" value="${row.id}">
                </td>
                <td>${index + 1}</td>
                <td class="font-weight-bold text-dark"><code>${row.stockorder_no}</code></td>
                <td>${row.productname}</td>
                <td class="text-primary font-weight-bold">${row.seedname}</td>
                <td><span class="badge badge-outline-dark">${row.vendor_id || 'Not Set'}</span></td>
                <td class="text-center font-weight-bold">${parseFloat(row.TotalDemandQty).toLocaleString()}</td>
                <td class="text-center">
                    <span class="badge badge-pill badge-primary">${row.packets_needed} Pkts</span>
                </td>
                <td class="text-center font-weight-bold text-success">${changeDateFormatYmd_dMY(row.sowing_date)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-primary rounded-circle" onclick="initiatePOFromAlert(${row.id}, '${row.vendor_id}', '${row.seedid}', ${row.packets_needed}, '${row.seedname}')" title="Create PO">
                        <i class="fa fa-shopping-cart"></i>
                    </button>
                </td>
            </tr>`;
        });
    } else {
        html = `<tr><td colspan="10" class="text-center py-4 text-muted"><i class="fa fa-info-circle mr-2"></i> No pending demand found.</td></tr>`;
    }
    $('#demandBody').html(html);
}

function populateFilterOptions(data) {
    const products = new Set();
    const seeds = new Set();
    const dates = new Set();

    if (data && data.length > 0) {
        data.forEach(row => {
            if (row.productname) products.add(row.productname.trim());
            if (row.seedname) seeds.add(row.seedname.trim());
            if (row.sowing_date) dates.add(row.sowing_date.trim());
        });
    }

    populateSelect('#filter_product_name', Array.from(products).sort());
    populateSelect('#filter_seed_name', Array.from(seeds).sort());
    
    const sortedDates = Array.from(dates).sort();
    let dateOptions = '';
    sortedDates.forEach(val => {
        dateOptions += `<option value="${val}">${changeDateFormatYmd_dMY(val)}</option>`;
    });
    $('#filter_sowing_date').html(dateOptions);

    // Initialize Select2 on the dynamically loaded filters with checkboxes
    $('.select2').select2({
        width: '100%',
        closeOnSelect: false,
        templateResult: function(data) {
            if (!data.id) { return data.text; }
            var isSelected = data.element && data.element.selected;
            var $checkbox = $('<input type="checkbox" style="margin-right: 8px; pointer-events: none;" />').prop('checked', isSelected);
            var $res = $('<span></span>').append($checkbox).append($('<span></span>').text(data.text));
            return $res;
        }
    }).on('select2:select select2:unselect', function(e) {
        setTimeout(function() {
            $('.select2-results__option').each(function() {
                var isSelected = $(this).attr('aria-selected') === 'true';
                $(this).find('input[type="checkbox"]').prop('checked', isSelected);
            });
        }, 10);
    });
}

function populateSelect(selector, optionsArray) {
    let html = '';
    optionsArray.forEach(val => {
        html += `<option value="${val}">${val}</option>`;
    });
    $(selector).html(html);
}

function applyFilters() {
    const selectedProducts = $('#filter_product_name').val() || [];
    const selectedSeeds = $('#filter_seed_name').val() || [];
    const selectedDates = $('#filter_sowing_date').val() || [];

    const filteredData = globalDemandData.filter(row => {
        let matchProduct = true;
        let matchSeed = true;
        let matchDate = true;

        if (selectedProducts.length > 0) {
            matchProduct = selectedProducts.includes(row.productname ? row.productname.trim() : '');
        }
        if (selectedSeeds.length > 0) {
            matchSeed = selectedSeeds.includes(row.seedname ? row.seedname.trim() : '');
        }
        if (selectedDates.length > 0) {
            matchDate = selectedDates.includes(row.sowing_date ? row.sowing_date.trim() : '');
        }

        return matchProduct && matchSeed && matchDate;
    });

    renderDemandTable(filteredData);
    $('#selectAllDemand').prop('checked', false);
}

function clearFilters() {
    $('#filter_product_name').val([]).trigger('change');
    $('#filter_seed_name').val([]).trigger('change');
    $('#filter_sowing_date').val([]).trigger('change');
    renderDemandTable(globalDemandData);
    $('#selectAllDemand').prop('checked', false);
}

function toggleAllDemand(source) {
    $('.demand-check').prop('checked', source.checked);
}

function initiateBulkPO() {
    const selectedRows = $('.demand-check:checked');
    if (selectedRows.length === 0) {
        Swal.fire('No Selection', 'Please select at least one demand alert.', 'warning');
        return;
    }

    $('#poForm')[0].reset();
    $('#poItemBody').empty();
    
    let firstVendor = '';
    const alertIds = [];

    selectedRows.each(function() {
        const rowData = JSON.parse($(this).closest('tr').attr('data-alert'));
        alertIds.push(rowData.id);
        if (!firstVendor) firstVendor = rowData.vendor_id;
        
        addPORow(rowData.seedid, rowData.packets_needed, rowData.seedname);
    });

    $('#po_alert_id').val(alertIds.join(','));
    $('#po_vendor_id').val(firstVendor);
    $('#poModal').modal('show');
}

function initiatePOFromAlert(alertId, vendorId, seedId, packets, seedName) {
    $('#poForm')[0].reset();
    $('#po_alert_id').val(alertId);
    $('#po_vendor_id').val(vendorId);
    $('#poItemBody').empty();
    
    addPORow(seedId, packets, seedName);
    $('#poModal').modal('show');
}

function addPORow(seedId = '', packets = 1, seedName = 'Select Item') {
    const rowId = Date.now();
    const rowHtml = `<tr id="row_${rowId}">
        <td>
            <select class="form-control po-item-select" name="seed_id[]">
                <option value="${seedId}">${seedName}</option>
            </select>
        </td>
        <td><input type="text" class="form-control po-unit" name="unit[]" readonly value="Packet"></td>
        <td><input type="number" class="form-control po-qty" name="quantity[]" value="${packets}" oninput="calculateRowTotal(${rowId})"></td>
        <td><input type="number" class="form-control po-price" name="price[]" value="0.00" step="0.01" oninput="calculateRowTotal(${rowId})"></td>
        <td class="font-weight-bold text-dark row-total">₹0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removePORow(${rowId})"><i class="fa fa-trash"></i></button></td>
    </tr>`;
    $('#poItemBody').append(rowHtml);
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
                url: '../krf-orderandsales/modal/purchase_modal.php?action=saveNewPurchaseOrder',
                type: 'POST',
                data: formData + '&projectid=<?php echo $_SESSION['projectid'] ?? 1; ?>',
                success: function(response) {
                    try {
                        const res = typeof response === 'string' ? JSON.parse(response) : response;
                        if(res.status === 'success') {
                            Swal.fire('Success', 'Purchase Order Created Successfully!', 'success');
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
    if (!dateStr || dateStr === '0000-00-00' || dateStr.includes('0000-00-00')) return 'N/A';
    const date = new Date(dateStr);
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    return `${date.getDate().toString().padStart(2, '0')}-${months[date.getMonth()]}-${date.getFullYear()}`;
}
</script>
