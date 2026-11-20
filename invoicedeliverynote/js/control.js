$(document).on("change", ".closmissingmod", function () {
    $("#missingSchedule").modal("hide");
    getcustomer();
});

$(document).on("change", "#clientid", function () {
    const client_id = $("#clientid").val();
    if (!client_id) {
        alert("You have not choose client name");
        return false;
    }

    const $opt = $("#clientid option:selected");
    $("#ctcperson").val($opt.text());
    $("#ctcno").val($opt.data("phones"));
    $("#email_id").val($opt.data("clientemail"));
    $("#inv_add").val($opt.data("invoicingaddress"));
    loadPendingOrders(client_id);
});

function getSelectedPsonos() {
    let orders = [];
    $("#tblpendingorders tbody tr").each(function () {
        const row = $(this);
        if (row.find(".chkorder").is(":checked")) {
            orders.push({
                id: row.data("id"),
                psono: row.data("psono"),
                stockorderNo: row.data("stockorder-no"),
                discPer: getNum(row.find(".row-disc-per").text()),
                discAmt: getNum(row.find(".row-disc-amt").text())
            });
        }
    });
    return orders;
}

$(document).on("change", ".chkorder", function () {
    let allSelectedAllocation = true;
    let allSelectedGatepass = true;
    let allSelectedDeliverynote = true;
    let allSelectedFreight = true;
    let count = 0;

        $("#tblpendingorders tbody tr").each(function () {
            const row = $(this);
            if (row.find(".chkorder").is(":checked")) {
                count++;
                if (row.data("has-allocation") != "1") allSelectedAllocation = false;
                if (row.data("has-gatepass") != "1") allSelectedGatepass = false;
                if (row.data("has-deliverynote") != "1") allSelectedDeliverynote = false;
                if (row.data("has-freight") != "1") allSelectedFreight = false;
            }
        });

        if (count > 0) {
            // Step 1: Vehicle Allocation
            $("#btn_vehicle_allotment").prop("disabled", false);
            
            // Step 2: Gate Pass (Needs Allocation)
            if (allSelectedAllocation) {
                $("#generate_gatepass").prop("disabled", false);
            } else {
                $("#generate_gatepass").prop("disabled", true);
            }

            // Step 3: Delivery Note (Needs Gate Pass)
            if (allSelectedAllocation && allSelectedGatepass) {
                $("#generate_deliverynote").prop("disabled", false);
            } else {
                $("#generate_deliverynote").prop("disabled", true);
            }

            // Step 4: Final Invoice (Needs All Previous)
            if (allSelectedAllocation && allSelectedGatepass && allSelectedDeliverynote && allSelectedFreight) {
                $("#generatefinal_inv").prop("disabled", false);
            } else {
                $("#generatefinal_inv").prop("disabled", true);
            }
        } else {
        $("#btn_vehicle_allotment").prop("disabled", true);
        $("#generate_gatepass").prop("disabled", true);
        $("#generate_deliverynote").prop("disabled", true);
        $("#generatefinal_inv").prop("disabled", true);
    }
});

// Discount percen input
$("#tblpendingorders").on("input", ".row-disc-per", function () {
    const row = $(this).closest("tr");
    let discPer = getNum($(this).text());
    if (discPer > 100) { discPer = 100; $(this).text("100"); }
    if (discPer < 0) { discPer = 0; $(this).text("0"); }
    
    const baseValue = getNum(row.data("ordervalue"));
    const discAmt = baseValue * (discPer / 100);
    row.find(".row-disc-amt").text(discAmt.toFixed(2));
    
    const finalValue = baseValue - discAmt;
    row.find(".row-final-value").text("₹" + finalValue.toFixed(2));
});

// Discount amount input
$("#tblpendingorders").on("input", ".row-disc-amt", function () {
    const row = $(this).closest("tr");
    let discAmt = getNum($(this).text());
    const baseValue = getNum(row.data("ordervalue"));
    
    if (discAmt > baseValue) {
        discAmt = baseValue;
        $(this).text(baseValue.toFixed(2));
    }
    if (discAmt < 0) { discAmt = 0; $(this).text("0"); }
    
    const discPer = baseValue > 0 ? (discAmt / baseValue) * 100 : 0;
    row.find(".row-disc-per").text(discPer.toFixed(2));
    
    const finalValue = baseValue - discAmt;
    row.find(".row-final-value").text("₹" + finalValue.toFixed(2));
});

// Editable bounds handlers
$("#tblpendingorders").on("keypress", ".editable-decimal", function (e) {
    const char = String.fromCharCode(e.which);
    if (char === ".") {
        if ($(this).text().includes(".")) { e.preventDefault(); }
    } else if (!/[0-9]/.test(char)) { e.preventDefault(); }
});

$("#tblpendingorders").on("blur", ".editable-decimal", function () {
    let val = $(this).text().replace(/[^0-9.]/g, "");
    const parts = val.split(".");
    if (parts.length > 2) { val = parts[0] + "." + parts.slice(1).join(""); }
    $(this).text(val || 0);
});

$(document).on("click", "#btn_vehicle_allotment", function () {
    triggerVehicleAllotment();
});

function triggerVehicleAllotment() {
    let clientid = $("#clientid").val();
    let selectedOrders = getSelectedPsonos();
    if (selectedOrders.length === 0) {
        Swal.fire("Warning", "Please select at least one order to allot vehicle.", "warning");
        return;
    }
    
    // UI Initialization (Moved from old logic)
    $("#editinv_vehicleno, #editnv_drivername, #editnv_drivercontactno, #editinv_freightcharges").val("");
    $("#capacity_status_box").addClass("d-none");
    $("#alloc_vehicle_id").val("");
    $("#alloc_driver_id").val("");
    $("#alloc_driver_contact").val("");
    $("#freight_details_container").addClass("d-none");
    $("#freight_details_body").empty();

    // Load Masters
    loadVehicleMaster();
    loadDriverMaster();

    // Fetch info for the selected orders and auto-fill transport details
    getprovisional_freightcharges(clientid, selectedOrders, function(data) {
        if (data && data.length > 0) {
            $("#freight_details_container").removeClass("d-none");
            let rows = "";
            let totalPayable = 0;
            let firstTransport = null;
            let freightPath = null;

            data.forEach(item => {
                const p = Number(item.payable_freightamount) || 0;
                totalPayable += p;
                if (item.vehicle_no && !firstTransport) firstTransport = item;
                if (item.freightinvoicepath) freightPath = item.freightinvoicepath;

                rows += `
                <tr class="freight-data-row" data-psono="${item.psono}" data-amount="${item.freight_amount}">
                    <td class="ps-2 fw-bold text-primary">${item.psono}</td>
                    <td class="text-end">₹${Number(item.freight_amount).toFixed(2)}</td>
                    <td contenteditable="true" class="text-center fw-bold freight-row-disc">${Number(item.freight_discountpercent).toFixed(2)}</td>
                    <td class="text-end fw-bold text-success freight-row-payable">₹${p.toFixed(2)}</td>
                    <td class="text-center">
                        <button class="btn btn-link btn-sm p-0 m-0 ${item.freightinvoicepath ? '' : 'disabled'}" onclick="openDocumentPreview('${encodeURIComponent(item.freightinvoicepath || '')}', 'Freight Invoice - ${item.psono}')">
                            <i class="fa fa-file-pdf text-danger"></i>
                        </button>
                    </td>
                </tr>`;
            });
            $("#freight_details_body").html(rows);
            $(".freight-total-summary-amt").text("₹" + totalPayable.toFixed(2));
            $("#editinv_freightcharges").val(totalPayable.toFixed(2));

            // ✅ Auto-fill Transport Details from Allotment History
            if (firstTransport) {
                if (firstTransport.deliverydate) {
                    $("#alloc_delivery_date").val(firstTransport.deliverydate);
                }
                if (firstTransport.vehicle_id) {
                    $("#alloc_vehicle_id").val(firstTransport.vehicle_id).trigger('change');
                }
                if (firstTransport.driver_name) {
                    // Try to find driver by name if ID isn't enough (history stores name)
                    $("#alloc_driver_id option").each(function() {
                        if ($(this).text() === firstTransport.driver_name) {
                            $(this).prop('selected', true);
                            return false;
                        }
                    });
                    $("#alloc_driver_id").trigger('change');
                }
            }

            // ✅ Show Download button if path exists
            if (freightPath) {
                $("#download_freight_pdf_btn").removeClass("d-none").off('click').on('click', function() {
                    openDocumentPreview(decodeURIComponent(freightPath), "Freight Invoice Preview");
                });
            } else {
                $("#download_freight_pdf_btn").addClass("d-none");
            }
        }
    });

    $("#vehicleAllotmentModal").modal("show");
}

$(document).on("input", ".freight-row-disc", function () {
    const row = $(this).closest("tr");
    let disc = getNum($(this).text());
    if (disc > 100) { disc = 100; $(this).text("100"); }
    if (disc < 0) { disc = 0; $(this).text("0"); }
    
    const amt = parseFloat(row.data("amount")) || 0;
    const payable = amt * (1 - disc / 100);
    row.find(".freight-row-payable").text("₹" + payable.toFixed(2));
    
    recalculateTotalFreight();
});

$(document).on("click", ".view-freight-pdf", function () {
    const path = decodeURIComponent($(this).data("path"));
    openDocumentPreview(path, "Existing Freight Invoice");
});

function recalculateTotalFreight() {
    let total = 0;
    $(".freight-row-payable").each(function() {
        total += getNum($(this).text().replace("₹", ""));
    });
    $(".freight-total-summary-amt").text("₹" + total.toFixed(2));
    $("#editinv_freightcharges").val(total.toFixed(2));
}

function loadVehicleMaster() {
    const pid = $("#hidden_projectid").val();
    $.post("queries/invoicequeries.php?action=get_vehicle_master", { projectid: pid }, function(res) {
        if (res.status === "success") {
            let options = '<option value="">-- Select Vehicle --</option>';
            res.data.forEach(v => {
                options += `<option value="${v.vehicleid}" data-no="${v.vehicleno}" data-name="${v.vehiclename}">${v.vehiclename} (${v.vehicleno})</option>`;
            });
            $("#alloc_vehicle_id").html(options);
        }
    });
}

function loadDriverMaster(selectedId = null) {
    const pid = $("#hidden_projectid").val();
    $.post("queries/invoicequeries.php?action=get_driver_master", { projectid: pid }, function(res) {
        if (res.status === "success") {
            let options = '<option value="">-- Select Driver --</option>';
            res.data.forEach(d => {
                options += `<option value="${d.driverid}" data-phone="${d.drivermobileno}">${d.drivername}</option>`;
            });
            $("#alloc_driver_id").html(options).data("drivers", res.data);
            if (selectedId) $("#alloc_driver_id").val(selectedId).trigger("change");
        }
    });
}

$(document).on("change", "#alloc_delivery_date, #alloc_vehicle_id", function() {
    const vid = $("#alloc_vehicle_id").val();
    const date = $("#alloc_delivery_date").val();
    const pid = $("#hidden_projectid").val();
    const totalQty = Number($("#alloc_qty_text").data("totalqty")) || 0;

    if (!vid || !date) {
        $("#capacity_status_box").addClass("d-none");
        return;
    }

    $.post("queries/invoicequeries.php?action=check_vehicle_occupancy", { vehicleid: vid, date: date, projectid: pid }, function(res) {
        if (res.status === "success") {
            const max = res.max_plants || 0;
            const used = res.used_plants || 0;
            const avail = max - used;
            const perc = max > 0 ? (used / max) * 100 : 0;
            
            $("#plants_cap_text").text(`${used} / ${max}`);
            $("#plants_cap_progress").css("width", perc + "%").removeClass("bg-danger bg-warning bg-success");
            
            if (perc > 90) $("#plants_cap_progress").addClass("bg-danger");
            else if (perc > 70) $("#plants_cap_progress").addClass("bg-warning");
            else $("#plants_cap_progress").addClass("bg-success");

            $("#capacity_status_box").removeClass("d-none");
            
            if (totalQty > avail) {
                $("#capacity_warning").removeClass("d-none");
                $("#btn_save_vehicle_modal").prop("disabled", true);
            } else {
                $("#capacity_warning").addClass("d-none");
                $("#btn_save_vehicle_modal").prop("disabled", false);
            }
        }
    });
});

$(document).on("change", "#alloc_driver_id", function() {
    const phone = $(this).find("option:selected").data("phone") || "";
    $("#alloc_driver_contact").val(phone);
});

$(document).on("click", "#btn_add_driver_master", function() {
    Swal.fire({
        title: 'Add New Driver',
        target: document.getElementById('vehicleAllotmentModal'),
        html: `<div class="text-start px-2">
                 <label class="form-label mb-1">Driver Name <span class="text-danger">*</span></label>
                 <input id="swal_driver_name" class="form-control mb-3" placeholder="Enter name">
                 <label class="form-label mb-1">Mobile Number</label>
                 <input id="swal_driver_phone" class="form-control" placeholder="Enter mobile no.">
               </div>`,
        showCancelButton: true,
        confirmButtonText: 'Save Driver',
        confirmButtonColor: '#667eea',
        didOpen: () => {
            // Force focus on the first input once Swal opens
            const nameInput = document.getElementById('swal_driver_name');
            if (nameInput) nameInput.focus();
        },
        preConfirm: () => {
            const name = document.getElementById('swal_driver_name').value;
            const phone = document.getElementById('swal_driver_phone').value;
            if (!name) { 
                Swal.showValidationMessage('Driver name is required'); 
                return false; 
            }
            return { name: name, phone: phone };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("queries/invoicequeries.php?action=save_new_driver", { 
                projectid: $("#hidden_projectid").val(),
                drivername: result.value.name,
                drivermobileno: result.value.phone
            }, function(res) {
                if (res.status === "success") {
                    Swal.fire("Saved!", "Driver added successfully.", "success");
                    loadDriverMaster(res.driverid);
                } else {
                    Swal.fire("Error", "Failed to save driver.", "error");
                }
            });
        }
    });
});

$(document).on("click", "#btn_save_vehicle_modal", function () {
    const client_id = $("#clientid").val();
    let psonos = getSelectedPsonos();
    
    const divDate = $("#alloc_delivery_date").val();
    const vid = $("#alloc_vehicle_id").val();
    const vno = $("#alloc_vehicle_id option:selected").data("no");
    const vname = $("#alloc_vehicle_id option:selected").data("name");
    const did = $("#alloc_driver_id").val();
    const dname = $("#alloc_driver_id option:selected").text();
    const dcontact = $("#alloc_driver_contact").val();
    const freight = $("#editinv_freightcharges").val();

    if (!divDate) {
        Swal.fire("Missing Info", "Please select Delivery Date.", "warning");
        return;
    }else if (!vno) {
        Swal.fire("Missing Info", "Please select Vehicle.", "warning");
        return;
    }else if (!did) {
        Swal.fire("Missing Info", "Please select Delivery Driver.", "warning");
        return;
    }

    // Extract Freight Details from the structured table
    const freightDetails = [];
    $("#freight_details_body tr.freight-data-row").each(function() {
        const row = $(this);
        freightDetails.push({
            psono: row.data("psono"),
            amount: parseFloat(row.data("amount")) || 0,
            discount: getNum(row.find(".freight-row-disc").text()),
            payable: getNum(row.find(".freight-row-payable").text().replace("₹", ""))
        });
    });

    // 1. Generate Freight Invoices and update status
    if (freightDetails.length > 0) {
        saveFreightAndGenerateInvoice(client_id, freightDetails, vno, did, dname, dcontact, vid, divDate, function(res) {
            if (res.status === 'success') {
                if (res.details && res.details.length > 0) {
                    const firstPath = res.details[0].path;
                    openDocumentPreview(firstPath, "Freight Invoice Generated");
                }
            }
        });
    }
});

$(document).on("click", "#showfreightinvoice", function () {
    $(this).addClass("active").siblings().removeClass("active");
    const path = $("#generatefinal_inv").data("freightinvoice");
    if (path) {
        openDocumentPreview(decodeURIComponent(path), "Freight Invoice Preview");
    } else {
        $("#displaypreview").html('<div class="text-center p-5 text-muted"><i class="fa fa-info-circle me-2"></i>No freight invoice generated for this allocation.</div>');
    }
});

$(document).on("click", ".backpreview", function () {
    $("#ordersContainer").removeClass("d-none");
    $("#previewinvoice").addClass("d-none");
    $(".mainheading").removeClass("d-none");
    if ($("#generatefinal_inv").hasClass("generatedinvoice")) {
        getcustomer();
    }
});

$(document).on("click", "#btncreateinve", function () {
    const client_id = $("#clientid").val();
    let psonos = getSelectedPsonos();
    if (psonos.length === 0) { alert("Please select at least one order."); return; }

    const freightcharges = $("#editinv_freightcharges").val();
    const vehicleno = $("#editinv_vehicleno").val();
    const drivername = $("#editnv_drivername").val();
    const drivercontactno = $("#editnv_drivercontactno").val();

    datasave("tempinv", client_id, drivername, drivercontactno, vehicleno, freightcharges, psonos);
});

$(document).on("click", "#regenerate", function () {
    const client_id = $("#clientid").val();
    let psonos = getSelectedPsonos();
    if (psonos.length === 0) { alert("Please select at least one order."); return; }

    const freightcharges = $("#editinv_freightcharges").val();
    const vehicleno = $("#editinv_vehicleno").val();
    const drivername = $("#editnv_drivername").val();
    const drivercontactno = $("#editnv_drivercontactno").val();

    datasave("tempinv", client_id, drivername, drivercontactno, vehicleno, freightcharges, psonos);
});

$(document).on("click", "#generate_gatepass", function () {
    triggerGatePassGeneration();
});

$(document).on("click", "#generate_deliverynote", function () {
    triggerDeliveryNoteGeneration();
});

$(document).on("click", "#generatefinal_inv", function () {
    triggerFinalInvoiceGeneration();
});

// Dedicated Generation Functions
function triggerGatePassGeneration() {
    const data = getActionParams();
    if (data) datasave("gatepass", data.client_id, data.drivername, data.drivercontactno, data.vehicleno, data.freightcharges, data.psonos);
}

function triggerDeliveryNoteGeneration() {
    const data = getActionParams();
    if (data) datasave("deliverynote", data.client_id, data.drivername, data.drivercontactno, data.vehicleno, data.freightcharges, data.psonos);
}

function triggerFinalInvoiceGeneration() {
    const psonos = getSelectedPsonos();
    const missing = [];
    
    // Comprehensive Readiness Check
    $("#tblpendingorders tbody tr").each(function() {
        const $row = $(this);
        if ($row.find(".chkorder").is(":checked")) {
            const p = $row.data("psono");
            if ($row.data("has-freight") != "1") missing.push(`Freight Invoice for ${p}`);
            if ($row.data("has-gatepass") != "1") missing.push(`Gate Pass for ${p}`);
            if ($row.data("has-deliverynote") != "1") missing.push(`Delivery Note for ${p}`);
        }
    });

    if (missing.length > 0) {
        Swal.fire({
            title: "Documents Missing",
            html: "Please create following documents first: <br><br><ul class='text-start'>" + missing.map(m => `<li>${m}</li>`).join('') + "</ul>",
            icon: "warning",
            confirmButtonColor: "#667eea"
        });
        return;
    }

    const data = getActionParams();
    if (data) datasave("finalinvoice", data.client_id, data.drivername, data.drivercontactno, data.vehicleno, data.freightcharges, data.psonos);
}

function getActionParams() {
    const client_id = $("#clientid").val();
    let psonos = getSelectedPsonos();
    if (psonos.length === 0) { 
        Swal.fire("Warning", "Please select at least one order.", "warning"); 
        return null; 
    }

    return {
        client_id: client_id,
        psonos: psonos,
        freightcharges: $("#editinv_freightcharges").val(),
        vehicleno: $("#editinv_vehicleno").val(),
        drivername: $("#editnv_drivername").val(),
        drivercontactno: $("#editnv_drivercontactno").val()
    };
}

$(document).on("click", "#showinvoice", function () {
    $(this).addClass("active").siblings().removeClass("active");
    const fileParam = decodeURIComponent($("#generatefinal_inv").data("invoicepath") || "");
    if (fileParam) openDocumentPreview(fileParam, "Final Invoice Preview");
});

$(document).on("click", "#showdeliverynote", function () {
    $(this).addClass("active").siblings().removeClass("active");
    const fileParam = decodeURIComponent($("#generatefinal_inv").data("deliverynote") || "");
    if (fileParam) openDocumentPreview(fileParam, "Delivery Note Preview");
});

$(document).on("click", "#showgatepass", function () {
    $(this).addClass("active").siblings().removeClass("active");
    const fileParam = decodeURIComponent($("#generatefinal_inv").data("gatepass") || "");
    if (fileParam) openDocumentPreview(fileParam, "Gate Pass Preview");
});
