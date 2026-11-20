<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../header.php');
?>

<style>
    .premium-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .10);
        border: 1px solid #e2e8f0;
        margin-bottom: 2rem;
    }

    .card-header-premium {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1.25rem 1.5rem;
        border-radius: 16px 16px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .charge-badge {
        min-width: 88px;
        display: inline-block;
        font-size: .85rem;
    }

    #boxPackingGrid th {
        white-space: nowrap;
    }

    .required-mark {
        color: #dc3545;
        margin-left: 2px;
    }
</style>

<div class="container-fluid px-4 py-3">
    <div class="premium-card">
        <div class="card-header-premium">
            <div class="d-flex align-items-center">
                <i class="fa fa-box fa-2x text-primary mr-3"></i>
                <h4 class="mb-0">Box Packing Freight Master</h4>
            </div>

            <div>
                <button type="button" class="btn btn-outline-primary mr-2" onclick="loadBoxPackingGrid()">
                    <i class="fa fa-sync mr-1"></i> Refresh
                </button>
                <button type="button" class="btn btn-primary" onclick="openBoxPackingModal()">
                    <i class="fa fa-plus mr-1"></i> Add New
                </button>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="boxPackingGrid" class="table table-hover table-bordered mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="60" class="text-center">S.No</th>
                            <th>State</th>
                            <th>District</th>
                            <th>Location</th>
                            <th class="text-center">Plants / Box</th>
                            <th class="text-right d-none">Amount (₹)</th> 
                            <th class="text-right">By Airport (₹)</th>
                            <th class="text-right">By Bus (₹)</th>
                            <th class="text-right">By Railway (₹)</th>
                            <th width="120" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Modal -->
<div class="modal fade" id="boxPackingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="boxPackingModalTitle">
                    <i class="fa fa-box mr-2"></i>Box Packing Freight Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="boxPackingForm" autocomplete="off">
                <div class="modal-body p-4">
                    <input type="hidden" id="boxpacking_id" name="id">
                    <input type="hidden" id="bp_projectid" name="projectid" value="<?php echo htmlspecialchars($projectid); ?>">
                    <input type="hidden" id="bp_loginname" name="loginname" value="<?php echo htmlspecialchars($loginname); ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>State <span class="required-mark">*</span></label>
                            <select class="form-control" id="bp_stateid" name="stateid" required>
                                <option value="">-- Select State --</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>District <span class="required-mark">*</span></label>
                            <select class="form-control" id="bp_districtid" name="districtid" required disabled>
                                <option value="">-- Select District --</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>City <span class="required-mark">*</span></label>

                            <input type="text" class="form-control" id="bp_location" name="location"
                                   maxlength="200" placeholder="Enter location" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>No. of Plants Contained (1 Box) <span class="required-mark">*</span></label>
                            <input type="number" class="form-control" id="bp_plants_per_box" name="plants_per_box"
                                   min="1" step="1" value="500" required>
                            <small class="text-muted">Default: 500 plants per box</small>
                        </div> 
                        <div class="col-md-6 mb-3 d-none"> 
                            <label>Amount <span class="required-mark">*</span></label>
                            <input type="number" class="form-control numeric-charge"
                                   id="bp_amount" name="boxpacking_amount"
                                   min="0" step="0.01" value="0" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>By Airport Charges</label>
                            <input type="number" class="form-control numeric-charge"
                                   id="bp_airport_charges" name="airport_charges"
                                   min="0" step="0.01" value="0">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>By Bus Charges</label>
                            <input type="number" class="form-control numeric-charge"
                                   id="bp_bus_charges" name="bus_charges"
                                   min="0" step="0.01" value="0">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>By Railway Charges</label>
                            <input type="number" class="form-control numeric-charge"
                                   id="bp_railway_charges" name="railway_charges"
                                   min="0" step="0.01" value="0">
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary px-5" id="btnSaveBoxPacking">
                        <i class="fa fa-save mr-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

<script>
    const boxPackingUrl = "<?php echo $rooturl; ?>BoxPacking_Freight_Master/modal/boxpackingfreight_modal.php";

    $(document).ready(function () {
        loadStates();
        loadBoxPackingGrid();
    });

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function money(value) {
        const n = Number(value || 0);
        return n.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function loadStates(selectedStateId = '') {
        $.ajax({
            url: boxPackingUrl + '?action=getStates',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (!res.status) {
                    Swal.fire('Error', res.message || 'Unable to load states.', 'error');
                    return;
                }

                let html = '<option value="">-- Select State --</option>';

                (res.data || []).forEach(function (row) {
                    html += `<option value="${escapeHtml(row.stateid)}">${escapeHtml(row.statename)}</option>`;
                });

                $('#bp_stateid').html(html);

                if (selectedStateId) {
                    $('#bp_stateid').val(selectedStateId);
                }
            },
            error: ajaxError
        });
    }

    function loadDistricts(stateid, selectedDistrictId = '') {
        $('#bp_districtid')
            .prop('disabled', true)
            .html('<option value="">Loading...</option>');

        if (!stateid) {
            $('#bp_districtid')
                .prop('disabled', true)
                .html('<option value="">-- Select District --</option>');
            return;
        }

        $.ajax({
            url: boxPackingUrl + '?action=getDistricts',
            type: 'GET',
            data: { stateid: stateid },
            dataType: 'json',
            success: function (res) {
                if (!res.status) {
                    $('#bp_districtid')
                        .prop('disabled', true)
                        .html('<option value="">-- Select District --</option>');
                    Swal.fire('Error', res.message || 'Unable to load districts.', 'error');
                    return;
                }

                let html = '<option value="">-- Select District --</option>';

                (res.data || []).forEach(function (row) {
                    html += `<option value="${escapeHtml(row.districtid)}">${escapeHtml(row.districtname)}</option>`;
                });

                $('#bp_districtid')
                    .html(html)
                    .prop('disabled', false);

                if (selectedDistrictId) {
                    $('#bp_districtid').val(selectedDistrictId);
                }
            },
            error: ajaxError
        });
    }

    $('#bp_stateid').on('change', function () {
        loadDistricts($(this).val(), '');
    });

    function loadBoxPackingGrid() {
        const projectid = $('#bp_projectid').val();

        $('#boxPackingGrid tbody').html(
            '<tr><td colspan="10" class="text-center text-muted py-4">Loading...</td></tr>'
        );

        $.ajax({
            url: boxPackingUrl + '?action=list',
            type: 'GET',
            data: { projectid: projectid },
            dataType: 'json',
            success: function (res) {
                if (!res.status) {
                    $('#boxPackingGrid tbody').html(
                        '<tr><td colspan="10" class="text-center text-danger py-4">' +
                        escapeHtml(res.message || 'Unable to load data.') +
                        '</td></tr>'
                    );
                    return;
                }

                const rows = res.data || [];

                if (!rows.length) {
                    $('#boxPackingGrid tbody').html(
                        '<tr><td colspan="10" class="text-center text-muted py-4">No box packing freight configured.</td></tr>'
                    );
                    return;
                }

                let html = '';

                rows.forEach(function (row, index) {
                    const json = encodeURIComponent(JSON.stringify(row));

                    html += `
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td>${escapeHtml(row.statename)}</td>
                            <td>${escapeHtml(row.districtname)}</td>
                            <td><strong>${escapeHtml(row.location)}</strong></td>
                            <td class="text-center"><strong>${parseInt(row.plants_per_box || 500, 10)}</strong></td>
 
                            <td class="text-right d-none">${money(row.boxpacking_amount)}</td>
 
                            <td class="text-right">${money(row.airport_charges)}</td>
                            <td class="text-right">${money(row.bus_charges)}</td>
                            <td class="text-right">${money(row.railway_charges)}</td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary mr-1"
                                        title="Edit"
                                        onclick="editBoxPacking('${json}')">
                                    <i class="fa fa-edit"></i>
                                </button>

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete"
                                        onclick="deleteBoxPacking(${parseInt(row.id, 10)})">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $('#boxPackingGrid tbody').html(html);
            },
            error: function (xhr, status, error) {
                $('#boxPackingGrid tbody').html(
                    '<tr><td colspan="10" class="text-center text-danger py-4">Server error while loading data.</td></tr>'
                );
                ajaxError(xhr, status, error);
            }
        });
    }

    function openBoxPackingModal() {
        $('#boxPackingForm')[0].reset();
        $('#boxpacking_id').val('');
        $('#bp_plants_per_box').val('500');
        $('#bp_amount').val('0');
        $('#bp_airport_charges').val('0');
        $('#bp_bus_charges').val('0');
        $('#bp_railway_charges').val('0');

        $('#bp_stateid').val('');
        $('#bp_districtid')
            .prop('disabled', true)
            .html('<option value="">-- Select District --</option>');

        $('#boxPackingModalTitle').html(
            '<i class="fa fa-box mr-2"></i>Add Box Packing Freight'
        );

        $('#boxPackingModal').modal('show');
    }

    function editBoxPacking(encodedRow) {
        const row = JSON.parse(decodeURIComponent(encodedRow));

        $('#boxpacking_id').val(row.id);
        $('#bp_location').val(row.location);
        $('#bp_plants_per_box').val(row.plants_per_box || 500);
        $('#bp_amount').val(row.boxpacking_amount);
        $('#bp_airport_charges').val(row.airport_charges);
        $('#bp_bus_charges').val(row.bus_charges);
        $('#bp_railway_charges').val(row.railway_charges);

        $('#boxPackingModalTitle').html(
            '<i class="fa fa-edit mr-2"></i>Edit Box Packing Freight'
        );

        loadStates(row.stateid);

        // State select is already normally loaded on page. Set it immediately too.
        $('#bp_stateid').val(row.stateid);
        loadDistricts(row.stateid, row.districtid);

        $('#boxPackingModal').modal('show');
    }

    function validMoney(value) {
        return /^\d+(\.\d{1,2})?$/.test(String(value).trim());
    }

    $('.numeric-charge').on('input', function () {
        if (Number(this.value) < 0) {
            this.value = 0;
        }
    });

    $('#boxPackingForm').on('submit', function (e) {
        e.preventDefault();

        const stateid = $('#bp_stateid').val();
        const districtid = $('#bp_districtid').val();
        const location = $('#bp_location').val().trim();

        if (!stateid) {
            Swal.fire('Validation', 'Please select State.', 'warning');
            $('#bp_stateid').focus();
            return;
        }

        if (!districtid) {
            Swal.fire('Validation', 'Please select District.', 'warning');
            $('#bp_districtid').focus();
            return;
        }

        if (!location) {
            Swal.fire('Validation', 'Please enter Location.', 'warning');
            $('#bp_location').focus();
            return;
        }

        const plantsPerBox = $('#bp_plants_per_box').val();
        if (!/^\d+$/.test(plantsPerBox) || parseInt(plantsPerBox, 10) <= 0) {
            Swal.fire('Validation', 'No. of Plants Contained (1 Box) must be a positive whole number.', 'warning');
            $('#bp_plants_per_box').focus();
            return;
        }

        let numericOk = true; 
        let fixamt=false; 

        $('.numeric-charge').each(function () {
            let val = $(this).val();

            if (val === '') {
                $(this).val('0');
                val = '0';
            }

            if (!validMoney(val)) {
                numericOk = false;
                $(this).focus();
                return false;
            } 
            if (!fixamt && val >0){
                fixamt =true;
            }
 
        });

        if (!numericOk) {
            Swal.fire(
                'Validation',
                'Amount and all charges must contain numeric values only, with maximum 2 decimal places.',
                'warning'
            );
            return;
        } 
        if(!fixamt){
            Swal.fire(
                'Validation',
                'Please fill in at least one field: Airport, Bus, or Railway.',
                'warning'
            );
            return;

        }
 

        const stateName = $('#bp_stateid option:selected').text().trim();
        const districtName = $('#bp_districtid option:selected').text().trim();

        const formData = new FormData(this);
        formData.append('statename', stateName);
        formData.append('districtname', districtName);

        const $btn = $('#btnSaveBoxPacking');
        $btn.prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin mr-1"></i> Saving...');

        $.ajax({
            url: boxPackingUrl + '?action=save',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (res) {
                if (res.status) {
                    Swal.fire('Success', res.message, 'success').then(function () {
                        $('#boxPackingModal').modal('hide');
                        loadBoxPackingGrid();
                    });
                } else {
                    Swal.fire('Error', res.message || 'Unable to save data.', 'error');
                }
            },
            error: ajaxError,
            complete: function () {
                $btn.prop('disabled', false)
                    .html('<i class="fa fa-save mr-1"></i> Save');
            }
        });
    });

    function deleteBoxPacking(id) {
        Swal.fire({
            title: 'Delete Box Packing Freight?',
            text: 'This record will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Delete'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: boxPackingUrl + '?action=delete',
                type: 'POST',
                dataType: 'json',
                data: {
                    id: id,
                    projectid: $('#bp_projectid').val()
                },
                success: function (res) {
                    if (res.status) {
                        Swal.fire('Deleted!', res.message, 'success')
                            .then(loadBoxPackingGrid);
                    } else {
                        Swal.fire('Error', res.message || 'Unable to delete record.', 'error');
                    }
                },
                error: ajaxError
            });
        });
    }

    function ajaxError(xhr, status, error) {
        console.error(xhr.responseText);
        Swal.fire(
            'Server Error',
            (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : (error || 'Unable to process request.'),
            'error'
        );
    }
</script>
