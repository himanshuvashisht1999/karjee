<?php
require('../header.php');
?>
<div class="container-fluid my-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient-info text-white d-flex justify-content-between align-items-center py-3">
            <h3 class="card-title mb-0">
                <i class="fa fa-map-marker-alt mr-2"></i> Missing Sowing Location Alerts
            </h3>
            <div>
                <button class="btn btn-success btn-sm font-weight-bold shadow-sm mr-2" onclick="initiateBulkLocationUpdate();">
                    <i class="fa fa-layer-group mr-1"></i> Bulk Update Location
                </button>
                <button class="btn btn-light btn-sm font-weight-bold shadow-sm" onclick="loadLocationAlerts();">
                    <i class="fa fa-sync-alt mr-1"></i> Refresh List
                </button>
            </div>
        </div>
        <div class="card-body bg-light">
            <div class="alert alert-custom alert-indicator-top indicator-info bg-white shadow-sm mb-4" role="alert">
                <div class="alert-content">
                    <span class="alert-title text-info">Process Information</span>
                    <span class="alert-text">These production orders are confirmed but missing a designated sowing location. Update them to ensure smooth production tracking.</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered bg-white shadow-sm" id="alertTable">
                    <thead class="thead-dark">
                        <tr>
                            <th width="40" class="text-center">
                                <input type="checkbox" id="selectAllAlerts" onclick="toggleAllAlerts(this)">
                            </th>
                            <th width="50">#</th>
                            <th>Stock Order No</th>
                            <th>Product Name</th>
                            <th>Seed Name</th>
                            <th class="text-right">Qty</th>
                            <th class="text-center">Sowing Date</th>
                            <th class="text-center">Created At</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="alertBody">
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="spinner-border text-info" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Scanning for alerts...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Location Update Modal -->
<div class="modal fade" id="locationModal" tabindex="-1" role="dialog" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="locationModalLabel">
                    <i class="fa fa-edit mr-2"></i> Update Sowing Location
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="locationForm">
                <input type="hidden" id="alert_ids" name="alert_ids">
                <div class="modal-body p-4">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark">Select Sowing Location <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="sowinglocationid" name="sowinglocationid" required style="width: 100%;">
                            <option value="">Loading sowing location...</option>
                        </select>
                    </div>
                    <div class="alert alert-light border small text-muted">
                        <i class="fa fa-info-circle mr-1"></i> This will update the sowing location for all selected production orders.
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info font-weight-bold px-4 shadow-sm">
                        <i class="fa fa-check-circle mr-1"></i> Update Location
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-gradient-info {
        background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
    }
    .alert-custom {
        border-left: 5px solid #17a2b8;
    }
    .table td { vertical-align: middle; }
    .select2-container .select2-selection--single { height: 38px !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px !important; }
</style>

<script>
$(document).ready(function() {
    loadLocationAlerts();
    loadPolyhouses();
    
    // Initialize Select2 if available
    if($.fn.select2) {
        $('#sowinglocationid').select2({
            dropdownParent: $('#locationModal')
        });
    }

    $('#locationForm').on('submit', function(e) {
        e.preventDefault();
        const alertIds = $('#alert_ids').val();
        const sowinglocationid = $('#sowinglocationid').val();

        if (!sowinglocationid) {
            Swal.fire('Required', 'Please select a sowing location.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Confirm Update',
            text: `Are you sure you want to update the location for ${alertIds.split(',').length} orders?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            confirmButtonText: 'Yes, Update Now'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'modal/sowing_location_modal.php?action=updateSowingLocation',
                    type: 'POST',
                    data: {
                        alert_ids: alertIds,
                        sowinglocationid: sowinglocationid,
                        projectid: $('#hidden_projectid').val()
                    },
                    success: function(response) {
                        try {
                            const res = typeof response === 'string' ? JSON.parse(response) : response;
                            if (res.status === 'success') {
                                Swal.fire('Success', res.message, 'success');
                                $('#locationModal').modal('hide');
                                loadLocationAlerts();
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        } catch(e) {
                            console.error(e);
                            Swal.fire('Error', 'Invalid server response.', 'error');
                        }
                    }
                });
            }
        });
    });
});

function loadLocationAlerts() {
    const projectid = $('#hidden_projectid').val();
    $.ajax({
        url: 'modal/sowing_location_modal.php?action=getSowingLocationAlerts',
        type: 'POST',
        data: { projectid: projectid },
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (data && data.status === 'error') {
                    $('#alertBody').html(`<tr><td colspan="9" class="text-center py-4 text-danger"><i class="fa fa-exclamation-triangle mr-2"></i> Error: ${data.message}</td></tr>`);
                    return;
                }

                let html = '';
                if (data && Array.isArray(data) && data.length > 0) {
                    data.forEach((row, index) => {
                        html += `<tr>
                            <td class="text-center">
                                <input type="checkbox" class="alert-check" value="${row.id}" data-orderno="${row.stockorder_no}">
                            </td>
                            <td>${index + 1}</td>
                            <td class="font-weight-bold"><code>${row.stockorder_no}</code></td>
                            <td>${row.productname}</td>
                            <td class="text-primary">${row.seedname}</td>
                            <td class="text-right font-weight-bold">${parseFloat(row.order_qty).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                            <td class="text-center font-weight-bold text-info">${changeDateFormatYmd_dMY(row.sowing_date)}</td>
                            <td class="text-center small">${row.created_at}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info rounded-pill px-3" onclick="openUpdateModal('${row.id}')">
                                    <i class="fa fa-edit mr-1"></i> Set Location
                                </button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = `<tr><td colspan="9" class="text-center py-4 text-muted"><i class="fa fa-check-circle text-success mr-2"></i> All confirmed orders have sowing locations assigned.</td></tr>`;
                }
                $('#alertBody').html(html);
            } catch(e) {
                console.error("Parsing Error:", e, response);
                $('#alertBody').html(`<tr><td colspan="9" class="text-center py-4 text-danger">Error processing server response.</td></tr>`);
            }
        }
    });
}

function loadPolyhouses() {
    const companyid = $('body').data("companyid");
    const subcompanyid = $('body').data("subcompanyid");
    const projectid = $('body').data("projectid");
    $.ajax({
        url: 'modal/sowing_location_modal.php?action=GetSowingLocation',
        type: 'POST',
        data: {companyid: companyid, subcompanyid: subcompanyid, projectid: projectid, shortby: 'all', shortbyvalue: '' },
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (data && data.status === 'error') {
                    console.error("Location Error:", data.message);
                    $('#sowinglocationid').html('<option value="">Error loading locations</option>');
                    return;
                }

                let options = '<option value="">Select Sowing Location</option>';
                if (data && Array.isArray(data) && data.length > 0) {
                    data.forEach(p => {
                        options += `<option value="${p.LocationId}">${p.LocationName}</option>`;
                    });
                }
                $('#sowinglocationid').html(options);
            } catch(e) { 
                console.error("Parsing Error:", e, response); 
            }
        }
    });
}

function toggleAllAlerts(source) {
    $('.alert-check').prop('checked', source.checked);
}

function openUpdateModal(alertId) {
    $('#alert_ids').val(alertId);
    $('#locationModal').modal('show');
}

function initiateBulkLocationUpdate() {
    const selected = $('.alert-check:checked');
    if (selected.length === 0) {
        Swal.fire('No Selection', 'Please select at least one alert.', 'warning');
        return;
    }
    const ids = [];
    selected.each(function() { ids.push($(this).val()); });
    $('#alert_ids').val(ids.join(','));
    $('#locationModal').modal('show');
}

function changeDateFormatYmd_dMY(dateStr) {
    if(!dateStr || typeof dateStr !== 'string' || dateStr == '0000-00-00') return 'N/A';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    return d.getDate() + "-" + months[d.getMonth()] + "-" + d.getFullYear();
}
</script>
<?php include('../footer.php'); ?>
