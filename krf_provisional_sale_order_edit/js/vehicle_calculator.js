/**
 * Reusable Freight Calculator Function
 * @param {number} km - The initial distance in kilometers (optional)
 * @param {number} projectid - The current project ID
 * @param {string} rooturl - The base URL of the application
 * @param {string} inputid - Optional: The jQuery selector for an input to fill with the selected rate
 */
var freightCalcProjectId = '';
var freightCalcRootUrl = '';
var freightCalcCallback = null;
var freightCalcQuantity = 0;
var freightCalcModalInstance = null;
function toggleFreightModal(show) {
    var element = document.getElementById('freightCalcModal');
    if (window.bootstrap && bootstrap.Modal && typeof bootstrap.Modal.getInstance === 'function') {
        freightCalcModalInstance = bootstrap.Modal.getInstance(element) || new bootstrap.Modal(element);
        freightCalcModalInstance[show ? 'show' : 'hide']();
    } else {
        $('#freightCalcModal').modal(show ? 'show' : 'hide');
    }
}
$(document).on('click.freightClose', '#freightCalcModal .freight-close', function(e) {
    e.preventDefault();
    toggleFreightModal(false);
});
$(document).on('input change', '.freight-trip-input', function() {
    var trips = Math.max(1, Math.floor(Number(this.value) || 1));
    var row = $(this).closest('tr');
    row.find('.freight-trip-total').text('₹' + (Number(row.attr('data-per-trip')) * trips).toFixed(2));
});

/**
 * Modern Freight Calculator Function (Adaptive Signature)
 * 
 * Signature 1 (New): openFreightCalculator(projectid, rooturl, callback)
 * Signature 2 (Old): openFreightCalculator(km, projectid, rooturl, ...)
 * 
 * We use an adaptive approach to prevent issues if many users have cached JS files.
 */
function openFreightCalculator() {
    var projectid, rooturl, callback, km = 0;
    
    // Extract callback function from any argument position
    for (var i = 0; i < arguments.length; i++) {
        if (typeof arguments[i] === "function") {
            callback = arguments[i];
            break;
        } else if (typeof arguments[i] === "string" && typeof window[arguments[i]] === "function") {
            callback = window[arguments[i]];
            break;
        }
    }

    if (typeof arguments[0] === "number" || (typeof arguments[0] === "string" && !isNaN(parseFloat(arguments[0])) && isFinite(arguments[0]))) {
        // Signature: (km, projectid, rooturl, callback)
        km = parseFloat(arguments[0]) || 0;
        projectid = arguments[1];
        rooturl = arguments[2];
    } else {
        // Signature: (projectid, rooturl, callback)
        projectid = arguments[0];
        rooturl = arguments[1];
    }

    // Ensure all critical parameters are updated globally every time the modal is triggered
    freightCalcProjectId = projectid || '';
    freightCalcRootUrl = rooturl || '';
    freightCalcCallback = callback || null;
    freightCalcQuantity = Math.max(0, Number(arguments[4]) || 0);

    // 1. Ensure the calculator modal exists in DOM, if not append it
    if ($('#freightCalcModal').length === 0) {
        $('body').append(
            '<div class="modal fade" id="freightCalcModal" tabindex="-1" role="dialog" style="z-index: 9999;">' +
            '<div class="modal-dialog" role="document" style="max-width: 95%; margin: 10px auto;">' +
            '<div class="modal-content border-0 shadow-lg">' +
            '<div class="modal-header bg-primary text-white">' +
            '<h5 class="modal-title"><i class="fa fa-calculator me-2"></i>Freight Charge Estimation</h5>' +
            '<button type="button" class="btn-close btn-close-white freight-close" aria-label="Close">&times;</button>' +
            '</div>' +
            '<div class="modal-body p-4">' +
            '<!-- Input Section -->' +
            '<div class="row mb-4 align-items-end">' +
            '<div class="col-md-8">' +
            '<label class="form-label fw-bold text-muted small uppercase">Enter Distance (Kilometers)</label>' +
            '<div class="input-group">' +
            '<span class="input-group-text"><i class="fa fa-road"></i></span>' +
            '<input type="number" id="calc_km_input" class="form-control form-control-lg" placeholder="e.g. 150" min="1">' +
            '<button type="button" class="btn btn-primary px-4" id="btn_trigger_calc">' +
            '<i class="fa fa-search me-1"></i> Get Rates' +
            '</button>' +
            '</div>' +
            '</div>' +
            '<div class="col-md-4 text-end">' +
                '<span class="text-muted small uppercase fw-bold">Matched Slab</span>' +
                '<h5 class="mb-0 text-primary" id="calc_range_display">---</h5>' +
            '</div>' +
            '</div>' +
 
            '<!-- Results Table -->' +
            '<div class="table-responsive rounded border">' +
            '<table class="table table-hover align-middle mb-0" id="calc_results_table">' +
            '<thead class="bg-light">' +
            '<tr>' +
            '<th>Vehicle Type</th>' +
            '<th>Capacity (T/P)</th>' +
            '<th class="text-center">Rate (₹)</th>' +
            '<th class="text-center">Action</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>' +
            '<!-- Content loaded via AJAX -->' +
            '<tr><td colspan="4" class="text-center py-4 text-muted">Enter kilometers above to see estimates</td></tr>' +
            '</tbody>' +
            '<tfoot class="bg-light small text-muted d-none" id="calc_debug_info">' +
            '<tr>' +
            '<td colspan="4" class="py-1 px-3 text-end">Searching in Project ID: <span id="debug_projectid"></span></td>' +
            '</tr>' +
            '</tfoot>' +
            '</table>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>'
        );

        // Attach event listener for the modal search button
        $(document).on('click', '#btn_trigger_calc', function () {
            var inputKm = $('#calc_km_input').val();
            if (!inputKm || inputKm <= 0) {
                Swal.fire('Input Required', 'Please enter a valid distance in KM.', 'warning');
                return;
            }
            // Use global state variables so they are always current
            performFreightCalculation(inputKm, freightCalcProjectId, freightCalcRootUrl);
        });

        // Allow pressing Enter in the input field
        $(document).on('keypress', '#calc_km_input', function (e) {
            if (e.which == 13) {
                e.preventDefault();
                $('#btn_trigger_calc').click();
            }
        });
    }

    // Show the modal
    if (km > 0) {
        $('#calc_km_input').val(km);
        performFreightCalculation(km, freightCalcProjectId, freightCalcRootUrl);
    } else {
        // Reset state for fresh entry
        $('#calc_km_input').val('');
        $('#calc_range_display').text('---');
        $('#calc_results_table tbody').html('<tr><td colspan="4" class="text-center py-4 text-muted">Enter kilometers above to see estimates</td></tr>');
    }

    toggleFreightModal(true);
}

// Map the old name to the new adaptive function just in case
var calculateFreightSuggestions = openFreightCalculator;

/**
 * Internal function to execute the AJAX request
 */
function performFreightCalculation(km, projectid, rooturl) {
    // Smart URL construction to prevent duplicated folder segments
    var cleanRoot = rooturl || '';
    if (cleanRoot.endsWith('/')) {
        cleanRoot = cleanRoot.substring(0, cleanRoot.length - 1);
    }
    
    // Check if rooturl already includes the project directory
    var subPath = "/krf_provisional_sale_order_edit/modal/vehicle_modal.php?action=calculateFreight";
    
    var ajaxUrl = cleanRoot + subPath;

    // Show loading state
    $('#calc_range_display').text('Searching...');
    $('#calc_results_table tbody').html(
        '<tr><td colspan="4" class="text-center py-5">' +
        '<i class="fa fa-spinner fa-spin fa-2x text-primary mb-3"></i><br>Calculating best rates...' +
        '</td></tr>'
    );
    $('#calc_debug_info').addClass('d-none');

    $.ajax({
        url: ajaxUrl,
        type: "POST",
        data: { km: km, projectid: projectid, total_qty: freightCalcQuantity },
        dataType: "JSON",
        success: function (res) {
            if (res.status === 'success') {
                $('#calc_range_display').text(res.range_label);

                // Show debug info
                $('#debug_projectid').text(res.projectid);
                $('#calc_debug_info').removeClass('d-none');

                var rows = '';
                if (res.suggestions && res.suggestions.length > 0) {
                    var isDelivered = ($('input[name="pickupfrom"]:checked').val() || $("#orderDeliveryFreightForm input[name='pickupfrom']:checked").val() || 'delivered') === 'delivered';

                    res.suggestions.forEach(function (v) {
                        var isRate = (v.calculationtype || '').toLowerCase() === 'rate';
                        var rawCharge = parseFloat(v.raw_charge || 0);
                        var calculatedCharge = parseFloat(v.charge || 0);

                        // When calculation type is rate/km and delivery is delivered (by company), calculate for double KM (onward + return journeys)
                        if (isRate && isDelivered) {
                            calculatedCharge = rawCharge * parseFloat(res.km || km) * 2;
                        }

                        var calcNote = isRate ? '<div class="small text-muted fw-normal">(Rate: ₹' + rawCharge + '/KM' + (isDelivered ? ' x 2 Journeys' : '') + ')</div>' : '';
                        var chargeDisplay = calculatedCharge > 0 ? '₹' + calculatedCharge.toFixed(2) + calcNote : '<span class="text-danger">Negotiable</span>';
                        var vehicleNo = v.vehicleno || 'No Plate';
                        var trayCap = v.traycapacity || '0';
                        var plantCap = v.plantscapacity || '0';
                        var trips = Number(plantCap) > 0 && freightCalcQuantity > 0 ? Math.max(1, Math.ceil(freightCalcQuantity / Number(plantCap))) : 1;
                        
                        // Pass parameters individually for better reliability
                        var selectCall = "selectFreightVehicle(" + 
                            calculatedCharge.toFixed(2) + ", " + 
                            "'" + v.vehiclename.replace(/'/g, "\\'") + "', " + 
                            "'" + vehicleNo.replace(/'/g, "\\'") + "', " + 
                            res.km + ", " +
                            "'" + (v.calculationtype || 'fixed') + "', " +
                            rawCharge + ", this, " + plantCap +
                        ")";

                        rows += '<tr data-per-trip="' + calculatedCharge + '">' +
                            '<td>' +
                            '<div class="fw-bold text-dark">' + v.vehiclename + '</div>' +
                            '<div class="small text-muted">' + vehicleNo + '</div>' +
                            '</td>' +
                            '<td>' +
                            '<div class="small"><b>Trays:</b> ' + trayCap + '</div>' +
                            '<div class="small"><b>Plants:</b> ' + plantCap + '</div>' +
                            '</td>' +
                            '<td class="text-center fs-5 fw-bold text-primary">' + chargeDisplay + '<div class="small mt-2"><label>Trips <input type="number" class="freight-trip-input form-control form-control-sm d-inline-block" style="width:75px" min="1" step="1" value="' + trips + '"></label></div><div class="freight-trip-total small">₹' + (calculatedCharge * trips).toFixed(2) + '</div></td>' +
                            '<td class="text-center">' +
                            '<button type="button" class="btn btn-sm btn-outline-primary px-3 shadow-sm" onclick="' + selectCall + '">' +
                            '<i class="fa fa-check me-1"></i> SELECT' +
                            '</button>' +
                            '</td>' +
                            '</tr>';
                    });
                } else {
                    rows = '<tr><td colspan="4" class="text-center text-muted py-4">No active vehicles found for this distance.</td></tr>';
                }
                $('#calc_results_table tbody').html(rows);
            } else {
                Swal.fire('Error', res.message || 'Failed to fetch suggestions', 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Server Error: Failed to calculate freight.', 'error');
        }
    });
}

/**
 * Global callback for vehicle selection
 */
function selectFreightVehicle(charge, vehiclename, vehicleno, calc_km, calculationtype, raw_charge, button, capacity) {
    var trips = button ? Math.max(1, Math.floor(Number($(button).closest('tr').find('.freight-trip-input').val()) || 1)) : 1;
    charge = (Number(charge) || 0) * trips;
    var selectedData = { 
        charge: parseFloat(charge) || 0, 
        vehiclename: vehiclename, 
        vehicleno: vehicleno, 
        calc_km: calc_km,
        calculationtype: calculationtype || 'fixed',
        raw_charge: raw_charge || 0,
        trips_needed: trips,
        plantscapacity: Number(capacity) || 0
    };

    // Execute callback if provided
    var cb = freightCalcCallback;
    if (cb) {
        if (typeof cb === "function") {
            cb(selectedData);
        } else if (typeof window[cb] === "function") {
            window[cb](selectedData);
        }
    }

    // Modern toast notification
    Swal.fire({
        title: 'Rate Applied',
        text: vehiclename + ' at ₹' + charge + ' for ' + calc_km + 'KM',
        icon: 'success',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    toggleFreightModal(false);
}


function getDistanceInKm(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in KM
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * 
        Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c;
    return distance; // in KM
}
