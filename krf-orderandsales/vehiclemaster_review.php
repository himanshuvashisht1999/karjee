<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../header.php');
include('functions/vehicle_functions.php');



?>

<style>
    .premium-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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

    .card-header-premium h4 {
        margin: 0;
        font-weight: 700;
        color: #1e293b;
    }

    .nav-tabs-premium {
        border-bottom: 2px solid #f1f5f9;
        margin-bottom: 1.5rem;
    }

    .nav-tabs-premium .nav-link {
        border: none;
        color: #64748b;
        font-weight: 600;
        padding: 1rem 1.5rem;
        transition: all 0.2s;
    }

    .nav-tabs-premium .nav-link.active {
        color: #0d6efd;
        border-bottom: 3px solid #0d6efd;
        background: transparent;
    }

    .freight-input {
        width: 100px;
        text-align: center;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 0.4rem;
    }

    .freight-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        outline: none;
    }
</style>

<div class="container-fluid px-4 py-3">
    <div class="premium-card">
        <div class="card-header-premium">
            <div class="d-flex align-items-center">
                <i class="fa fa-truck fa-2x text-primary mr-3"></i>
                <h4 class="mb-0">Vehicle & Freight Management</h4>
            </div>
            <div class="d-flex btn-group">
                <button class="btn btn-outline-info" onclick="testCalculator()">
                    <i class="fa fa-calculator mr-1"></i> Test Calculator
                </button>
                <button class="btn btn-outline-primary" onclick="window.location.reload();">
                    <i class="fa fa-sync mr-1"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body p-4">
            <ul class="nav nav-tabs nav-tabs-premium" id="vehicleTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="vehicle-list-tab" data-toggle="tab" data-target="#vehicle-list"
                        type="button" role="tab">
                        <i class="fa fa-list mr-2"></i> Vehicle Master
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="freight-matrix-tab" data-toggle="tab" data-target="#freight-matrix"
                        type="button" role="tab">
                        <i class="fa fa-table mr-2"></i> Freight Maintenance
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="vehicleTabsContent">
                <!-- Vehicle List Tab -->
                <div class="tab-pane fade show active" id="vehicle-list" role="tabpanel">
                    <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-primary" onclick="openVehicleModal()">
                            <i class="fa fa-plus mr-1"></i> Add New Vehicle
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="bg-light" style="position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                <tr>
                                    <th width="80" class="text-center">S.No</th>
                                    <th>Vehicle Name</th>
                                    <th>Vehicle No.</th>
                                    <th>Owner Name</th>
                                    <th class="text-center">Tray Capacity</th>
                                    <th class="text-center">Plants Capacity</th>
                                    <th width="150" class="text-center">Status</th>
                                    <th width="150" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $vehicles = getVehicleData($krfconn, $projectid);
                                if ($vehicles) {
                                    foreach ($vehicles as $i => $v) {
                                        $badgeClass = $v['status'] == 'Active' ? 'bg-success' : 'bg-danger';
                                        echo "<tr>
                                            <td class='text-center'>" . ($i + 1) . "</td>
                                            <td><span class='fw-bold'>{$v['vehiclename']}</span></td>
                                            <td>{$v['vehicleno']}</td>
                                            <td>{$v['vehicleownername']}</td>
                                            <td class='text-center'><span class='badge bg-light text-dark shadow-sm border px-3'>{$v['traycapacity']} Trays</span></td>
                                            <td class='text-center'><span class='badge bg-light text-dark shadow-sm border px-3'>{$v['plantscapacity']} Plants</span></td>
                                            <td class='text-center'><span class='badge $badgeClass'>{$v['status']}</span></td>
                                            <td class='text-center'>
                                                <button class='btn btn-sm btn-outline-primary' onclick='editVehicle(" . json_encode($v) . ")'>
                                                    <i class='fa fa-edit'></i>
                                                </button>
                                                <button class='btn btn-sm btn-outline-danger' onclick='deleteVehicleData({$v['vehicleid']})'>
                                                    <i class='fa fa-trash'></i>
                                                </button>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center text-muted py-4'>No vehicles configured.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Freight Matrix Tab -->
                <div class="tab-pane fade" id="freight-matrix" role="tabpanel">
                    <?php
                    $fdata = getFreightMatrix($krfconn, $projectid);
                    $matrix = $fdata['matrix'];
                    $vTypes = $fdata['vehicles'];
                    ?>
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="fa fa-info-circle mr-3 fs-4"></i>
                        <div class="flex-grow-1">
                            <strong>Maintenance Guide:</strong> Update the values in the table below and click
                            <strong>Save Changes</strong> at the bottom. Values are in Rupees (₹).
                        </div>
                        <button class="btn btn-success ml-3" onclick="$('#freightRangeModal').modal('show')">
                            <i class="fa fa-plus mr-1"></i> Add New Distance Range
                        </button>
                    </div>
                    <div class="table-responsive border rounded bg-white">
                        <table class="table table-bordered mb-0 align-middle">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th rowspan="2" width="180">Distance (KM)</th>
                                    <th rowspan="2" width="150">Calculation Type</th>
                                    <th colspan="<?php echo count($vTypes); ?>">Vehicle Type & Charges (₹)</th>
                                </tr>
                                <tr>
                                    <?php foreach ($vTypes as $v)
                                        echo "<th>{$v['vehiclename']}</th>"; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($matrix as $rowIndex => $row): ?>
                                    <tr>
                                        <td class="text-center fw-bold bg-light">
                                            <?php echo ($row['dist_max'] > 9000) ? "Above {$row['dist_min']} km" : "{$row['dist_min']} - {$row['dist_max']} km"; ?>
                                            <input type="hidden" class="row-dist-min"
                                                value="<?php echo $row['dist_min']; ?>">
                                            <input type="hidden" class="row-dist-max"
                                                value="<?php echo $row['dist_max']; ?>">
                                        </td>
                                        <td class="text-center">
                                            <select class="form-control form-control-sm row-calc-type" style="width: 120px; margin: 0 auto;">
                                                <option value="fixed" <?php echo ($row['calculationtype'] ?? 'fixed') === 'fixed' ? 'selected' : ''; ?>>Fixed</option>
                                                <option value="rate" <?php echo ($row['calculationtype'] ?? 'fixed') === 'rate' ? 'selected' : ''; ?>>Rate (/KM)</option>
                                            </select>
                                        </td>
                                        <?php foreach ($vTypes as $v): ?>
                                            <td class="text-center">
                                                <input type="number" step="0.01"
                                                    class="freight-input charge-input-<?php echo $v['vehicleid']; ?>"
                                                    data-vehicleid="<?php echo $v['vehicleid']; ?>"
                                                    value="<?php echo $row['charges'][$v['vehicleid']]; ?>">
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-4">
                        <button class="btn btn-primary-premium px-5 py-3" onclick="saveFreightMatrix()">
                            <i class="fa fa-save mr-2"></i> Save Freight Configuration
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle Modal -->
<div class="modal fade" id="vehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-truck mr-2"></i>Vehicle Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <form id="vehicleForm">
                    <input type="hidden" name="vehicleid" id="vid">
                    <input type="hidden" name="projectid" value="<?php echo $projectid; ?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vehicle Name</label>
                        <input type="text" name="vehiclename" id="vname" class="form-control" required
                            placeholder="e.g. Pickup (Small)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vehicle Number</label>
                        <input type="text" name="vehicleno" id="vno" class="form-control"
                            placeholder="e.g. JH 01 AB 1234">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Owner Name</label>
                        <input type="text" name="vehicleownername" id="vowner" class="form-control"
                            placeholder="e.g. Rajesh Kumar">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tray Capacity (Count)</label>
                        <input type="number" name="traycapacity" id="vtray" class="form-control" placeholder="e.g. 200"
                            min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Plants Capacity (Count)</label>
                        <input type="number" name="plantscapacity" id="vplants" class="form-control"
                            placeholder="e.g. 5000" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" id="vstatus" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary px-5">Save Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Freight Range Modal -->
<div class="modal fade" id="freightRangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fa fa-layer-group mr-2"></i>New Distance Slab</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Min Distance (KM)</label>
                    <input type="number" id="new_dist_min" class="form-control" placeholder="e.g. 101">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Max Distance (KM)</label>
                    <input type="number" id="new_dist_max" class="form-control" placeholder="e.g. 150">
                    <small class="text-muted d-block mt-1">Use 99999 for "Above" ranges.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold d-block mb-2">Calculation Type</label>
                    <div class="d-flex align-items-center">
                        <div class="form-check form-check-inline mr-4 d-flex align-items-center">
                            <input class="form-check-input mr-2" type="radio" name="new_calc_type" id="calc_type_fixed" value="fixed" checked style="width: 15px; height: 15px; cursor: pointer;">
                            <label class="form-check-label mb-0" for="calc_type_fixed" style="cursor: pointer; margin-left: 4px;">Fixed</label>
                        </div>
                        <div class="form-check form-check-inline d-flex align-items-center">
                            <input class="form-check-input mr-2" type="radio" name="new_calc_type" id="calc_type_rate" value="rate" style="width: 15px; height: 15px; cursor: pointer;">
                            <label class="form-check-label mb-0" for="calc_type_rate" style="cursor: pointer; margin-left: 4px;">Rate (/KM)</label>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-success px-5" onclick="addNewRangeRow()">Add Row</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>
<script src="<?php echo $rooturl; ?>krf-orderandsales/js/vehicle_calculator.js"></script>

<script>
    function openVehicleModal() {
        $('#vid').val('');
        $('#vname').val('');
        $('#vno').val('');
        $('#vowner').val('');
        $('#vtray').val('0');
        $('#vplants').val('0');
        $('#vstatus').val('Active');
        $('#vehicleModal').modal('show');
    }

    function editVehicle(data) {
        $('#vid').val(data.vehicleid);
        $('#vname').val(data.vehiclename);
        $('#vno').val(data.vehicleno);
        $('#vowner').val(data.vehicleownername);
        $('#vtray').val(data.traycapacity);
        $('#vplants').val(data.plantscapacity);
        $('#vstatus').val(data.status);
        $('#vehicleModal').modal('show');
    }

    $('#vehicleForm').submit(function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        $.ajax({
            url: "<?php echo $rooturl; ?>krf-orderandsales/modal/vehicle_modal.php?action=saveVehicle",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "JSON",
            success: function (res) {
                if (res.status === 'success') {
                    Swal.fire('Success', res.message, 'success').then(() => {
                        $('#vehicleModal').modal('hide');
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                Swal.fire('Error', 'Server Error: ' + error, 'error');
            }
        });
    });

    function deleteVehicleData(id) {
        Swal.fire({
            title: 'Delete Vehicle?',
            text: "This will also remove all freight charges for this vehicle!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("<?php echo $rooturl; ?>krf-orderandsales/modal/vehicle_modal.php?action=deleteVehicle", { vehicleid: id, projectid: "<?php echo $projectid; ?>" }, function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Deleted!', '', 'success').then(() => window.location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, "JSON");
            }
        });
    }

    function addNewRangeRow() {
        const min = $('#new_dist_min').val();
        const max = $('#new_dist_max').val();
        const calcType = $('input[name="new_calc_type"]:checked').val() || 'fixed';

        if (min === '' || max === '') {
            Swal.fire('Error', 'Please enter both distance values.', 'error');
            return;
        }

        const label = (parseInt(max) > 9000) ? `Above ${min} km` : `${min} - ${max} km`;
        let rowHtml = `<tr>
            <td class="text-center fw-bold bg-light">
                ${label}
                <input type="hidden" class="row-dist-min" value="${min}">
                <input type="hidden" class="row-dist-max" value="${max}">
            </td>
            <td class="text-center">
                <select class="form-control form-control-sm row-calc-type" style="width: 120px; margin: 0 auto;">
                    <option value="fixed" ${calcType === 'fixed' ? 'selected' : ''}>Fixed</option>
                    <option value="rate" ${calcType === 'rate' ? 'selected' : ''}>Rate (/KM)</option>
                </select>
            </td>`;

        <?php if ($vTypes):
            foreach ($vTypes as $v): ?>
                rowHtml += `<td class="text-center">
                <input type="number" step="0.01" class="freight-input charge-input-<?php echo $v['vehicleid']; ?>" 
                    data-vehicleid="<?php echo $v['vehicleid']; ?>" value="0">
            </td>`;
            <?php endforeach; endif; ?>

        rowHtml += `</tr>`;
        $('#freight-matrix tbody').append(rowHtml);
        $('#freightRangeModal').modal('hide');
        $('#new_dist_min, #new_dist_max').val('');
        $('#calc_type_fixed').prop('checked', true);
    }

    function saveFreightMatrix() {
        const matrixData = [];
        $('#freight-matrix tbody tr').each(function () {
            const row = {
                dist_min: $(this).find('.row-dist-min').val(),
                dist_max: $(this).find('.row-dist-max').val(),
                calculationtype: $(this).find('.row-calc-type').val() || 'fixed',
                charges: {}
            };
            $(this).find('.freight-input').each(function () {
                row.charges[$(this).data('vehicleid')] = $(this).val();
            });
            matrixData.push(row);
        });

        if (matrixData.length === 0) {
            Swal.fire('Error', 'No data to save!', 'error');
            return;
        }

        Swal.fire({
            title: 'Updating Freight Configuration...',
            didOpen: () => Swal.showLoading()
        });

        $.post("<?php echo $rooturl; ?>krf-orderandsales/modal/vehicle_modal.php?action=saveFreightChargeMatrix", {
            projectid: "<?php echo $projectid; ?>",
            matrix_data: JSON.stringify(matrixData)
        }, function (res) {
            if (res.status === 'success') {
                Swal.fire('Updated!', res.message, 'success').then(() => window.location.reload());
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, "JSON");
    }

    function testCalculator() {
        Swal.fire({
            title: 'Enter Kilometers (KM)',
            input: 'number',
            inputAttributes: {
                min: 1,
                step: 1
            },
            showCancelButton: true,
            confirmButtonText: 'Calculate Rates'
        }).then((result) => {
            if (result.value) {
                calculateFreightSuggestions(result.value, "<?php echo $projectid; ?>", "<?php echo $rooturl; ?>");
            }
        });
    }
</script>