let activeInput = null;
// Detect which field user edits
$(document).on("focus", "#confirmed_tray_count, #damaged_tray_count", function () {
    activeInput = this.id;
});

function clamp(val, min, max) {
    return Math.max(min, Math.min(val, max));
}

// Two-way recalculation
function recalcTwoWay() {
    const total = parseInt($("#total_tray_count").val()) || 0;
    let confirmed = parseInt($("#confirmed_tray_count").val()) || 0;
    let damaged = parseInt($("#damaged_tray_count").val()) || 0;

    if (total === 0) {
        $("#confirmed_tray_count").val(0);
        $("#damaged_tray_count").val(0);
        return;
    }

    if (activeInput === "confirmed_tray_count") {
        confirmed = clamp(confirmed, 0, total);
        damaged = total - confirmed;
    }

    if (activeInput === "damaged_tray_count") {
        damaged = clamp(damaged, 0, total);
        confirmed = total - damaged;
    }

    $("#confirmed_tray_count").val(confirmed);
    $("#damaged_tray_count").val(damaged);
}

// Trigger recalculation
$(document).on("input", "#total_tray_count, #confirmed_tray_count, #damaged_tray_count", function () {
    recalcTwoWay();
});

// Confirm button
$(document).on("click", "#ConfirmUpdateSowingTrayoslot", function () {
    const total = parseInt($("#total_tray_count").val()) || 0;
    const confirmed = parseInt($("#confirmed_tray_count").val()) || 0;
    const damaged = parseInt($("#damaged_tray_count").val()) || 0;
    const roottype = $("#ConfirmUpdateSowingTrayoslot").data("roottype");
    // Validation
    if (total !== (confirmed + damaged)) {
        Swal.fire({
            icon: "warning",
            title: "Tray count mismatch",
            text: "Total trays must equal Confirmed + Damaged.",
            confirmButtonColor: "#4e73df"
        });
        return;
    }

    // Confirmation
    Swal.fire({
        title: "Confirm Sowing Update?",
        html: `
        <b>Total:</b> ${total}<br>
        <b>Confirmed:</b> ${confirmed}<br>
        <b>Damaged/Lost:</b> ${damaged}
    `,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, Update",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#1cc88a"
    }).then((result) => {
        if (result.isConfirmed) {
            const payload = {
                stockorderno: $("#ConfirmUpdateSowingTrayoslot").data("stockorderno"),
                lotno: $("#ConfirmUpdateSowingTrayoslot").data("lotno"),
                roottype: $("#ConfirmUpdateSowingTrayoslot").data("roottype"),
                total: total,
                confirmed: confirmed,
                damaged: damaged
            };
            let stockordernol = $("#ConfirmUpdateSowingTrayoslot").data("stockorderno");

            updateConfirmedTrayCount(payload).done(function (res) {
                if (res.status === "success") {
                    generateSeedLotCardsByStockOrder(stockordernol, roottype);
                    Swal.fire({
                        icon: "success",
                        title: "Updated!",
                        text: res.message || "Sowing tray details updated successfully.",
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $("#ConfirmSowingQtymodal").modal("hide");
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Update Failed",
                        text: res.message || "Unable to update sowing tray data."
                    });
                }
            }).fail(function (xhr) {
                Swal.fire({
                    icon: "error",
                    title: "Server Error",
                    text: "Something went wrong while updating data."
                });
                console.error(xhr.responseText);
            }).always(function () {
                $("#ConfirmUpdateSowingTrayoslot").prop("disabled", false);
            });

        }
    });
});

$(document).ready(function () {
    let projectid = $("body").data("projectid");

    if ($("#sowingweekSelector").length > 0) {
        // Week-wise logic for showingplan_starting.php
        loadSowingScheduleStarting("current");
    } else if ($("#sowingDateList").length > 0) {
        // Old date-list logic
        GetActveSowingdates(function (sdres) {
            let dropdwnid = "sowingDateList";
            let $dd = $("#" + dropdwnid);
            $dd.empty();
            if (sdres.length > 0) {
                let dropdoption = `<option value="" selected disabled>Select</option>`;
                for (let i = 0; i < sdres.length; i++) {
                    dropdoption += `<option value="${sdres[i].frsowingdate}">${sdres[i].rdsowingdate}</option>`;
                }
                $dd.append(dropdoption);
                if (sdres.length === 1) {
                    $dd.val(sdres[0].frsowingdate).trigger("change");
                }
            }
        });
    }
});

$(document).on("change", "#sowingDateList", function () {
    let sowDate = $("#sowingDateList").val();
    if (!sowDate) return;
    GetProductionStockOrderDataWithDate(sowDate, sowDate, function (res) {
        renderSowingTable(res);
    });
});

// --- Week-wise Event Handlers ---
$(document).on("change", "#sowingmonthFilter", function () {
    loadSowingScheduleStarting("first");
});

$(document).on("change", "#sowingweekSelector", function () {
    let range = $(this).val();
    if (range) {
        let [start, end] = range.split(" to ");
        fetchAndRenderSowingData(start, end);
    }
});

$(document).on("click", "#sowingprevWeekBtn", function () {
    let $sel = $("#sowingweekSelector");
    let idx = $sel.prop("selectedIndex");

    if (idx > 0) {
        $sel.prop("selectedIndex", idx - 1).trigger("change");
    } else {
        // First week of month -> Ask to go to PREVIOUS month
        let $monthSel = $("#sowingmonthFilter");
        let mIdx = $monthSel.prop("selectedIndex");
        if (mIdx > 1) { // 0 is 'all', so check > 1 for months
            Swal.fire({
                title: "Previous Month?",
                text: "This is the first week of the selected month. Do you want to go to the previous month?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Yes, Go",
                cancelButtonText: "Stay"
            }).then((result) => {
                if (result.isConfirmed) {
                    $monthSel.prop("selectedIndex", mIdx - 1);
                    loadSowingScheduleStarting("last");
                }
            });
        }
    }
});

$(document).on("click", "#sowingnextWeekBtn", function () {
    let $sel = $("#sowingweekSelector");
    let count = $sel.find("option").length;
    let idx = $sel.prop("selectedIndex");

    if (idx < count - 1) {
        $sel.prop("selectedIndex", idx + 1).trigger("change");
    } else {
        // Last week of month -> Ask to go to NEXT month
        let $monthSel = $("#sowingmonthFilter");
        let mIdx = $monthSel.prop("selectedIndex");
        let mCount = $monthSel.find("option").length;
        if (mIdx < mCount - 1) {
            Swal.fire({
                title: "Next Month?",
                text: "This is the last week of the selected month. Do you want to go to the next month?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Yes, Go",
                cancelButtonText: "Stay"
            }).then((result) => {
                if (result.isConfirmed) {
                    $monthSel.prop("selectedIndex", mIdx + 1);
                    loadSowingScheduleStarting("first");
                }
            });
        }
    }
});

$(document).on("click", ".CloseForTodaybtn", function () {
    $("#sowingQRCodeModal").modal("hide");
    $("#germpackingQRCodeModal").modal("hide");

    if ($("#sowingweekSelector").length > 0) {
        let range = $("#sowingweekSelector").val();
        if (range) {
            let [start, end] = range.split(" to ");
            fetchAndRenderSowingData(start, end);
        }
    } else {
        let sowDate = $("#sowingDateList").val();
        if (!sowDate) return;
        GetProductionStockOrderDataWithDate(sowDate, sowDate, function (res) {
            renderSowingTable(res);
        });
    }
});

// sowing qr code print process
$(document).on("click", ".printqrcodebtntd", function () {
    // Load dropdowns

    const $row = $(this).closest("tr");

    const expecteddeliverydate = $row.data("expecteddeliverydate");
    const SeedCatId = $row.data("productcategoryid");
    const RootSeedId = $row.data("productrootseedid");
    const ScionSeedId = $row.data("productscionseedid");
    const roottype = $row.data("roottype");
    const roottrayid = $row.data("roottrayid");
    const sciontrayid = $row.data("sciontrayid");
    const sowinglocationid = $row.data("sowinglocationid");
    if (sowinglocationid == "" || sowinglocationid == null || sowinglocationid == "undefined") {
        Swal.fire({
            icon: "error",
            title: "Sowing Location",
            text: "Sowing location is missing. Please update sowing location first."
        });
        return false;
    }


    const clrootid = (roottype === "Scion") ? sciontrayid : roottrayid;

    GetTrayDataWithOption("sowingtraytype", '', '', clrootid);


    const SeedIdVal = (roottype === "Scion") ? ScionSeedId : RootSeedId;

    // alert(SeedIdVal + '===' + SeedCatId + '---' + RootSeedId);
    // GetSeedDataOnlyOption("SeedNameForSowing","categoryid",SeedCatId, SeedIdVal);
    GetSeedDataOnlyOption("SeedNameForSowing", "all", "all", SeedIdVal);
    $("#SeedNameForSowing").data('roottype', roottype);
    $("#seednamelable").html(roottype + " Seed Name");

    const mdOrderNo = $row.data("productionstockorderno") || "";
    const mdProductName = $row.data("productname") || "";
    const mdProductId = $row.data("productid") || "";
    const seedid = $row.data("seedid") || "";
    const sowingtraycount = parseInt($row.data("sowingtraycount")) || 0;
    const mdOrderQty = parseInt($row.data("orderqty")) || 0;
    const failurebufferpercent = parseFloat($row.data("failurebufferpercent")) || 30;
    const totalsowingqty = parseInt($row.data("totalsowingqty")) || 0;
    const sowingdate = $row.data("sowingdate") || '';

    // askChangeExpectedDeliveryDate(mdOrderNo, mdProductId, sowingdate);

    // If lotdetails came as string → parse JSON
    let raw = $row.data("lotdetails");
    let lotdetails = [];
    if (raw) {
        lotdetails = (typeof raw === 'string') ? JSON.parse(decodeURIComponent(raw)) : raw;
    }
    // Reset modal data
    $(".productNamemd, .productOrderNo, .productOrderQty").html("");
    $("#resttoprintQrCode").text(0);
    $("#sowingQRCodeModal input").val("");
    $("#seedlotdetailsdiv").empty();

    $("#otherorderdetails").data("dataStockOrderNo", "").data("dataOrderQty", "").data("dataProductName", "").data("dataProductId", "");
    // -----------------------------
    // Set modal header data
    // -----------------------------
    if (mdOrderNo && mdOrderQty > 0) {
        $(".productOrderNo").text(mdOrderNo);
        $(".productNamemd").text(mdProductName);
        $(".productOrderQty").text(mdOrderQty);
        $("#GerminationFailure").val(failurebufferpercent).trigger("input");

        // Force populate saved values (overriding calculation if tray type missing)
        if (sowingtraycount > 0) $("#TraysRequired").val(sowingtraycount);
        if (totalsowingqty > 0) $("#SowingQuantity").val(totalsowingqty);

        // Re-run this to update badges based on the forced values
        calculateRemainingQRCode();

        $("#otherorderdetails").data("dataStockOrderNo", mdOrderNo).data("dataOrderQty", mdOrderQty).data("dataProductName", mdProductName).data("dataProductId", mdProductId);
        $("#sowingQRCodeModal").modal("show");
    }
    // -----------------------------
    // Lot details rendering
    // -----------------------------
    if (Array.isArray(lotdetails) && lotdetails.length > 0) {
        // console.log(lotdetails);
        let lotrowhtml = "";
        lotdetails.forEach(item => {
            const bgClass = getNextBGClass();
            const traycount = parseInt(item.tray_count) || 0;
            const traycavity = parseInt(item.traycavity) || 0;
            const ttlseed = traycount * traycavity;
            lotrowhtml += `
                <div class="seedLotCard card shadow-sm p-3 mb-3 ${bgClass}">
                    <div class="row">
                        <div class="col-sm-3">
                            <label>Lot Number<br/><small> As per Seed Packet</small></label>
                            <input type="text" class="form-control LotNumberofSeed" value="${item.lotno || ""}" readonly>
                        </div>

                        <div class="col-sm-2">
                            <label>Seed Qty<br/><small> Available in Packate</small> </label>
                            <input type="text" class="form-control SeedQtyofLotno" value="${ttlseed}" readonly>
                        </div>

                        <div class="col-sm-2">
                            <label>QR Code Qty<br/><small>for Trays</small></label>
                            <input type="text" class="form-control QRCodeQtyofLotno" value="${traycount}" readonly>
                        </div>

                        <div class="col-sm-5 d-flex align-items-end">
                            <button 
                                class="btn btn-primary me-2 SowingPrintViewQrBtn d-none" data-roottype="${item.roottype}"
                                data-qrcodedata='${JSON.stringify(item.tray_details || [])}'
                                data-vtraycount="${traycount}"  data-lotno="${item.lotno}">
                                View/Remove QR Code
                            </button>

                            <button 
                                class="btn btn-primary me-2 ConfirmSowingQrcodeBtn" data-roottype="${item.roottype}"
                                data-qrcodedata='${JSON.stringify(item.tray_details || [])}'
                                data-vtraycount="${traycount}"  data-lotno="${item.lotno}">
                                Confirm Damage/Missing QR
                            </button>

                            <button 
                                class="btn btn-primary me-2 PrintSavedQrBtn" data-roottype="${item.roottype}" data-vtraycount="${traycount}"
                                    data-lotno="${item.lotno}"
                                    data-sowingtraycount="${sowingtraycount}"
                                    data-productname="${mdProductName}"
                                    data-seedid="${item.seedid}"
                                    data-seedname="${item.seedname}"
                                    data-stockorderno="${mdOrderNo}"
                                    data-deliverydate="${expecteddeliverydate}"
                                    data-sowingdate="${item.sowing_date}"
                                    data-qrcodedata='${JSON.stringify(item.tray_details || [])}'>
                                Print QR Code
                            </button>

                            <button class="btn btn-danger RemoveLotNoRow d-none">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        $("#seedlotdetailsdiv").append(lotrowhtml);
    }
    if (seedid) {
        setTimeout(function () {
            $("#SeedNameForSowing").val(seedid).trigger("change");
        }, 1000); // 1000 ms = 1 seconds
    }
});


// Germination Modal
$(document).on("show.bs.modal", "#germpackingQRCodeModal", function () {
    if (!$("#packingdate").val()) {
        let todayDateStr = new Date().toISOString().split('T')[0];
        $("#packingdate").val(todayDateStr);
    }
});

$(document).on("click", ".startgerbtntd", function () {
    let swOrderNo = $(this).closest("tr").data("productionstockorderno");
    let swProductName = $(this).closest("tr").data("productname");
    let swOrderQty = $(this).closest("tr").data("orderqty");
    let totalSowingQty = $(this).closest("tr").data("totalsowingqty");
    let swProductId = $(this).closest("tr").data("productid");
    let swTraysCout = $(this).closest("tr").data("sowingtraycount");
    let roottype = $(this).closest("tr").data("roottype");
    $(".productNamemd_pck").html('');
    $(".productOrderNo_pck").html('');
    $(".productOrderQty_pck").html('');
    $(".sowingOrderQty_pck").html('');
    $(".sowingTrayQty_pck").html('');

    $("#saveGerminationDetailsBtnpck").data("datastockordernopck", '');
    $("#saveGerminationDetailsBtnpck").data("dataorderqtypck", '');
    $("#saveGerminationDetailsBtnpck").data("dataproductnamepck", '');
    $("#saveGerminationDetailsBtnpck").data("dataproductidpck", '');
    $("#saveGerminationDetailsBtnpck").data("roottype", '');
    $("#resttoprintQrCode_pck").text(0);
    $("#sowingQRCodeModal_pck input").val('');
    if (swOrderNo && totalSowingQty > 0) {
        $(".productNamemd_pck").html(swProductName);
        $(".productOrderNo_pck").html(swOrderNo);
        $(".productOrderQty_pck").html(swOrderQty);
        $(".sowingOrderQty_pck").html(totalSowingQty);
        $(".sowingTrayQty_pck").html(swTraysCout);
        $("#germpackingQRCodeModal").modal("show");
        $("#saveGerminationDetailsBtnpck").data("datastockordernopck", swOrderNo);
        $("#saveGerminationDetailsBtnpck").data("dataorderqtypck", swOrderQty);
        $("#saveGerminationDetailsBtnpck").data("dataproductnamepck", swProductName);
        $("#saveGerminationDetailsBtnpck").data("dataproductidpck", swProductId);
        $("#saveGerminationDetailsBtnpck").data("swtrayscount", swTraysCout);
        $("#saveGerminationDetailsBtnpck").data("roottype", roottype);
    }
})
// calculate germination box
$(document).on("input", "#traycountinbox", function () {
    let clsowingTraysCout = parseInt($(".sowingTrayQty_pck").text()) || 0;
    let boxTrayCount = parseInt($(this).val()) || 0;
    if (boxTrayCount <= 0 || clsowingTraysCout <= 0) {
        $("#totalboxesforgerm").val("");
        $("#counttiprintqrcode").val("");
        $("#boxBreakdownContainer").slideUp(200);
        return;
    }

    let totalBoxes = Math.ceil(clsowingTraysCout / boxTrayCount);
    let fullBoxes = Math.floor(clsowingTraysCout / boxTrayCount);
    let partialTrays = clsowingTraysCout % boxTrayCount;

    $("#totalboxesforgerm").val(totalBoxes);
    $("#counttiprintqrcode").val(totalBoxes);
    $("#counttiprintqrcode").prop("max", totalBoxes);

    let breakdownText = "";
    let badgesHtml = "";

    if (partialTrays === 0) {
        breakdownText = `<b>${fullBoxes} Full Box${fullBoxes > 1 ? 'es' : ''}</b> (${boxTrayCount} trays each)`;
        badgesHtml = `<span class="badge bg-primary px-3 py-2 fs-6"><i class="fa fa-box me-1"></i> ${fullBoxes} Full Box${fullBoxes > 1 ? 'es' : ''} (${boxTrayCount} trays/box)</span>`;
    } else {
        breakdownText = `<b>${fullBoxes} Full Box${fullBoxes > 1 ? 'es' : ''}</b> (${boxTrayCount} trays/box) + <b>1 Partial Box</b> (${partialTrays} rest trays)`;
        badgesHtml = `
            <span class="badge bg-success px-3 py-2 fs-6"><i class="fa fa-box me-1"></i> ${fullBoxes} Full Box${fullBoxes > 1 ? 'es' : ''} (${boxTrayCount} trays each)</span>
            <span class="badge bg-warning text-dark px-3 py-2 fs-6"><i class="fa fa-cubes me-1"></i> 1 Partial Box (${partialTrays} rest trays)</span>
        `;
    }

    $("#boxBreakdownText").html(breakdownText);
    $("#boxBreakdownBadges").html(badgesHtml);
    $("#boxBreakdownContainer").slideDown(200);
});

$(document).on("input", "#boxopenafterdays", function () {
    let packingDate = $("#packingdate").val();
    let openafteDays = $("#boxopenafterdays").val();
    if (openafteDays <= 0) {
        $("#dateofopening").val("");
        return;
    }
    $("#dateofopening").val(adddaysingivedate(packingDate, openafteDays));
});

$(document).on("click", "#saveGerminationDetailsBtnpck", function () {
    let StockOrderNo = $("#saveGerminationDetailsBtnpck").data("datastockordernopck");
    let ProductName = $("#saveGerminationDetailsBtnpck").data("dataproductnamepck");
    let ProductId = $("#saveGerminationDetailsBtnpck").data("dataproductidpck");
    let roottype = $("#saveGerminationDetailsBtnpck").data("roottype");

    let traycountinbox = $("#traycountinbox").val().trim();
    let packingdate = $("#packingdate").val().trim();
    let dateofopening = $("#dateofopening").val().trim();
    // 🔴 Validation
    if (!StockOrderNo) {
        Swal.fire({
            icon: "warning",
            title: "Missing Stock Order No",
            text: "Stock Order Number is required to save germination details."
        });
        return;
    }

    if (!traycountinbox || traycountinbox <= 0) {
        $("#traycountinbox").focus();
        Swal.fire({
            icon: "warning",
            title: "Invalid Tray Count",
            text: "Please enter a valid number of trays per box."
        });
        return;
    }

    if (!packingdate) {
        $("#packingdate").focus();
        Swal.fire({
            icon: "warning",
            title: "Missing Packing Date",
            text: "Please select the packing date."
        });
        return;
    }

    if (!dateofopening) {
        $("#dateofopening").focus();
        Swal.fire({
            icon: "warning",
            title: "Missing Opening Date",
            text: "Please calculate the opening date properly."
        });
        return;
    }
    // 🚀 AJAX Call
    SaveAndUpdateGerminationData(StockOrderNo, ProductId, ProductName, traycountinbox, packingdate, dateofopening, roottype, function (res) {
        if (res.status == "success") {
            Swal.fire({
                icon: "success",
                title: "Saved!",
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            });
            $("#germpackingQRCodeModal").modal("hide");
            $(".CloseForTodaybtn").trigger("click");
        } else {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: res.message
            });
        }
    });
});

$(document).on("click", ".PrintSavedQrBtn", async function () {

    let productionorderno = $(this).data("stockorderno") ?? '';
    let seedname = $(this).data("seedname") ?? '';
    let lotno = $(this).data("lotno") ?? '';
    let deliverydate = $(this).data("deliverydate") ?? '';
    let sowingdate = $(this).data("sowingdate") ?? '';
    let sowingtraycount = $(this).data("sowingtraycount") ?? '';
    let productname = $(this).data("productname") ?? '';
    let traw = $(this).data("qrcodedata");
    let qrcodedata = [];
    if (traw) {
        // If it's already an object (jQuery auto-parses), use it. Otherwise, decode and parse.
        qrcodedata = (typeof traw === 'string') ? JSON.parse(decodeURIComponent(traw)) : traw;
    }
    // 1. Basic Parameter Validation
    if (!productionorderno || !seedname) {
        Swal.fire({
            icon: 'warning',
            title: 'Required Data Missing',
            text: 'Production Order No or Seed Name is missing. Please refresh the page and try again.'
        });
        return;
    }


    let traycountinlot = qrcodedata.length;
    let grcodelist = [];
    let traycavity = "";
    qrcodedata.forEach(item => {
        if (item.barcode) {
            traycavity = item.traycavity;
            grcodelist.push({
                qrcode: item.barcode
            });
        }
    });
    // return false;
    // 2. Content & Date Validation
    if (grcodelist.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No QR Codes',
            text: 'No QR codes found for this box to print.'
        });
        return;
    }

    if (!deliverydate || !sowingdate) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Dates',
            text: 'Sowing Date or Deliver Date is missing for these items.'
        });
        return;
    }

    // alert(deliverydate+'==='+sowingdate);
    // return;

    const formData = new FormData();
    formData.append('productionorderno', productionorderno);
    formData.append('seedname', seedname);
    formData.append('lotno', lotno);
    formData.append('traycountinlot', traycountinlot);
    formData.append('deliverydate', deliverydate);
    formData.append('sowingdate', sowingdate);
    formData.append('sowingtraycount', sowingtraycount);
    formData.append('traycavity', traycavity);

    formData.append('grcodelist', JSON.stringify(grcodelist));

    try {
        const response = await fetch('qrcode_print_sowingtray.php', {
            method: 'POST',
            body: formData
        });
        const textData = await response.text();

        if (textData.trim() === "error") {
            Swal.fire({
                icon: 'error',
                title: 'Processing Error',
                text: 'The server was unable to generate the print data. Please check if dates are valid.'
            });
            return;
        }

        // Trigger actual printing via QZ Tray
        printLabel(textData);

    } catch (error) {
        console.error("Printing request failed:", error);
        Swal.fire({
            icon: 'error',
            title: 'Network error',
            text: 'Failed to connect to the label generation service.'
        });
    }

});

$(document).on("click", ".readytoprintgBoxbtntd", async function () {
    let raw = $(this).data("gboxdata");
    let productionorderno = $(this).closest("tr").data("productionstockorderno") ?? '';
    let seedname = $(this).closest("tr").data("seedname") ?? '';

    // 1. Basic Parameter Validation
    if (!productionorderno || !seedname) {
        Swal.fire({
            icon: 'warning',
            title: 'Required Data Missing',
            text: 'Production Order No or Seed Name is missing. Please refresh the page and try again.'
        });
        return;
    }

    let gboxdata = [];
    if (raw) {
        gboxdata = (typeof raw === 'string') ? JSON.parse(decodeURIComponent(raw)) : raw;
    }
    let totalboxes = gboxdata.length;
    let grcodelist = [];
    let packingdatelb = "";
    let openingdatelb = "";
    let traycountinaboxlb = "";
    gboxdata.forEach(item => {
        if (item.qrcode) {
            packingdatelb = item.boxpackingdate;
            openingdatelb = item.boxopeningdate;
            traycountinaboxlb = item.traycountinbox;
            grcodelist.push({
                barcode: item.qrcode
            });
        }
    });
    // return false;
    // 2. Content & Date Validation
    if (grcodelist.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No QR Codes',
            text: 'No QR codes found for this box to print.'
        });
        return;
    }

    if (!packingdatelb || !openingdatelb) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Dates',
            text: 'Packing Date or Opening Date is missing for these items.'
        });
        return;
    }

    const formData = new FormData();
    formData.append('productionorderno', productionorderno);
    formData.append('seedname', seedname);
    formData.append('packingdate', packingdatelb);
    formData.append('openingdate', openingdatelb);
    formData.append('traycountinaboxlb', traycountinaboxlb);
    formData.append('totalboxcount', totalboxes); // Use totalboxes variable

    formData.append('grcodelist', JSON.stringify(grcodelist));

    try {
        const response = await fetch('qrcode_print_label.php', {
            method: 'POST',
            body: formData
        });
        const textData = await response.text();

        if (textData.trim() === "error") {
            Swal.fire({
                icon: 'error',
                title: 'Processing Error',
                text: 'The server was unable to generate the print data. Please check if dates are valid.'
            });
            return;
        }

        // Trigger actual printing via QZ Tray
        printLabel(textData);

    } catch (error) {
        console.error("Printing request failed:", error);
        Swal.fire({
            icon: 'error',
            title: 'Network error',
            text: 'Failed to connect to the label generation service.'
        });
    }
});


async function printLabel(tsplData) {
    if (typeof qz === 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Print Library Missing',
            text: 'The QZ Tray library was not loaded properly. Please refresh the page or check your internet connection.'
        });
        return;
    }

    try {
        if (!qz.websocket.isActive()) {
            await qz.websocket.connect();
        }

        // Get all printers to find the best match
        let printers = await qz.printers.find("TSC");
        let printer = null;

        if (Array.isArray(printers) && printers.length > 0) {
            printer = printers[0];
            console.log("Found TSC printer:", printer);
        } else {
            printer = await qz.printers.getDefault();
            console.log("TSC printer not found, using default:", printer);
        }

        if (!printer) {
            throw new Error("No printer found. Please check your printer connection and QZ Tray.");
        }

        let config = qz.configs.create(printer, {
            encoding: 'ISO-8859-1',
            forceRaw: true
        });

        let data = [{
            type: 'raw',
            format: 'command',
            data: tsplData
        }];

        await qz.print(config, data);
        console.log("Print job sent successfully to " + printer);

        // Optionally show a small toast instead of alert for better UX
        iziToast.success({
            title: 'Success',
            message: 'Print job sent to ' + printer,
            position: 'topRight'
        });

    } catch (e) {
        console.error("Printing failed", e);
        Swal.fire({
            icon: 'error',
            title: 'Printing Failed',
            text: e.message || "Printer is not accepting the job. Please check if it's online and ready.",
            footer: 'Check QZ Tray status and printer queue.'
        });
    }
    // Removed disconnect() to prevent socket closing before job handover
}


GetPolihouseDetailsList('all', 'all', '', 'PolyhouseTemplate');
// Recalculate on row remove

function calculateTotalBoxes() {
    let total = 0;
    $(".noofboxforpolyhouse").each(function () {
        total += parseInt($(this).val()) || 0;
    });
    $("#TotalPolyhouseBoxes").text(total);
    // Germinated box count
    let germinatedBoxes = parseInt($("#GerminatedBoxCount").text()) || 0;
    if (total > germinatedBoxes) {
        $("#BoxLimitWarning").removeClass("d-none");
        $("#TotalPolyhouseBoxes").addClass("text-danger").removeClass("text-success");
        return false;
    } else {
        $("#BoxLimitWarning").addClass("d-none");
        $("#TotalPolyhouseBoxes").removeClass("text-danger").addClass("text-success");
        return true;
    }
}
// end of germination

$(document).on("change", "#sowingtraytype", function () {
    // debugger;
    let cavitycount = $("#sowingtraytype option:selected").data("cavitycount") || 0;
    let bufferpercent = $("#GerminationFailure").val() || 0;
    let OrderQty = $(".productOrderQty").text() || 0;
    let trClData = TotalcalculateTraysRequired(OrderQty, bufferpercent, cavitycount);
    $("#TraysRequired").val(trClData.totaltrays);
    $("#SowingQuantity").val(trClData.totalquantity);
    $("#resttoprintQrCode").text(0);
    // $(".QRCodeQtyofLotno").val(0);
    calculateRemainingQRCode();
});

$(document).on("input", "#GerminationFailure", function () {
    // debugger;
    let cavitycount = $("#sowingtraytype option:selected").data("cavitycount") || 0;
    let bufferpercent = $("#GerminationFailure").val() || 0;
    let OrderQty = $(".productOrderQty").text() || 0;
    let trClData = TotalcalculateTraysRequired(OrderQty, bufferpercent, cavitycount);
    $("#TraysRequired").val(trClData.totaltrays);
    $("#SowingQuantity").val(trClData.totalquantity);
    $("#resttoprintQrCode").text(0);
    // $(".QRCodeQtyofLotno").val(0);
    calculateRemainingQRCode();
});

// Trigger calculation on input
$(document).on("input", ".QRCodeQtyofLotno", function () {
    const $row = $(this).closest(".seedLotCard");
    const cavityCount = parseInt(
        $("#sowingtraytype option:selected").data("cavitycount")
    ) || 0;
    const qrTrayQty = parseInt($(this).val()) || 0;
    const $seedQtyInput = $row.find(".SeedQtyofLotno");
    // 🚫 No tray selected
    if (cavityCount <= 0) {
        $seedQtyInput.val(0);
        return;
    }
    // 🧮 Seed qty = trays × cavities
    const seedQtyRequired = qrTrayQty > 0
        ? Math.ceil(qrTrayQty * cavityCount)
        : 0;
    // ✅ Update without triggering loop
    if (parseInt($seedQtyInput.val()) !== seedQtyRequired) {
        $seedQtyInput.val(seedQtyRequired);
    }
    // 🔄 Update totals
    calculateRemainingQRCode();
});


$(document).on("input", ".SeedQtyofLotno", function () {
    const cavitycount = parseInt(
        $("#sowingtraytype option:selected").data("cavitycount"),
        10
    ) || 0;
    const seedQty = parseInt($(this).val(), 10) || 0;
    const row = $(this).closest(".seedLotCard");
    const qrInput = row.find(".QRCodeQtyofLotno");
    // Reset UI
    qrInput.css({ background: "" });
    row.find(".seed-warning-msg, .remaining-seeds-msg").remove();
    // ❌ No cavity → no calculation
    if (cavitycount === 0) {
        qrInput.val(0);
        calculateRemainingQRCode();
        return;
    }
    // ==============================
    // ✅ CORRECT TRAY CALCULATION
    // ==============================
    let trays = Math.floor(seedQty / cavitycount);
    // Minimum 1 tray if seeds exist
    if (seedQty > 0 && trays === 0) {
        trays = 1;
    }
    qrInput.val(trays);
    // ==============================
    // ✅ VISUAL FEEDBACK
    // ==============================
    if (seedQty === 0) {
        qrInput.css({ background: "#ffffff" });
    }
    else if (seedQty < cavitycount) {
        qrInput.val(0);
        qrInput.css({ background: "#ffe8b3" }); // partial tray
        row.append(`
            <div class="seed-warning-msg" style="color:#d93e26;font-size:12px;margin-top:5px;">
                ⚠ Partial tray (less than cavity ${cavitycount})
            </div>
        `);
    }
    else if (seedQty % cavitycount !== 0) {
        qrInput.css({ background: "#ffe8b3" }); // partial tray
    }
    else {
        qrInput.css({ background: "#d7ffd7" }); // perfect tray(s)
    }

    // ==============================
    // ✅ CORRECT REMAINING SEEDS LOGIC
    // ==============================
    let remainder = seedQty % cavitycount;
    let remainingToFillTray = 0;

    if (seedQty > 0 && remainder !== 0) {
        remainingToFillTray = cavitycount - remainder;
    }

    row.append(`
        <div class="remaining-seeds-msg" style="font-size:12px;margin-top:3px;color:#333;">
            Remaining seeds to fill tray: <b>${remainingToFillTray}</b>
        </div>
    `);

    // ==============================
    // 🔄 UPDATE GLOBAL COUNTS
    // ==============================
    calculateRemainingQRCode();
});


$(document).on("click", ".SowingPrintViewQrBtn", function () {
    const dataStockOrderNo = $("#otherorderdetails").data("dataStockOrderNo") || "";
    const productname = $("#otherorderdetails").data("dataProductName") || "";
    const lotno = $(this).data("lotno") || "";
    let trqdata = $(this).attr("data-qrcodedata"); // read raw string
    try {
        trqdata = JSON.parse(trqdata);
    } catch (e) {
        trqdata = [];
    }
    $("#lotqrcodeprvtbl tbody").empty();
    if (!Array.isArray(trqdata) || trqdata.length === 0) {
        Swal.fire("No Data", "No QR codes found for this lot", "warning");
        return;
    }
    // Header info
    $("#prdnamelbl").text(productname);
    $("#ltnolbl").text(lotno);
    // console.log(trqdata);
    let qrcdtr = "";
    trqdata.forEach((row, index) => {
        qrcdtr += `
            <tr>
                <td style="width:50px; text-align:center;">${index + 1}</td>
                <td>${row.barcode}</td>
                <td style="width:180px; text-align:center;">
                    <i class="fa fa-trash text-danger font-lg removeQrRow"
                       data-barcode="${row.barcode}">
                    </i>
                </td>
            </tr>
        `;
    });
    $("#lotqrcodeprvtbl tbody").append(qrcdtr);
    $("#viewqrcode_modal").modal("show");
});

// ConfirmSowingQrcodeBtn
$(document).on("click", ".ConfirmSowingQrcodeBtn", function () {
    const dataStockOrderNo = $("#otherorderdetails").data("dataStockOrderNo") ?? "";
    const productname = $("#otherorderdetails").data("dataProductName") ?? "";
    const lotdetaildata = $(this).data("lotdetaildata");
    const lotno = $(this).data("lotno") || "";
    const roottype = $(this).data("roottype") || "";
    let trqdata = $(this).attr("data-qrcodedata"); // read raw string
    try {
        trqdata = JSON.parse(trqdata);
    } catch (e) {
        trqdata = [];
    }
    // Header info
    $("#confirm_prdnamelbl").text(productname);
    $("#confirm_ltnolbl").text(lotno);
    $("#total_tray_count").val(trqdata.length);
    $("#ConfirmUpdateSowingTrayoslot").data("stockorderno", dataStockOrderNo);
    $("#ConfirmUpdateSowingTrayoslot").data("lotno", lotno);
    $("#ConfirmUpdateSowingTrayoslot").data("lotdetaildata", lotdetaildata);
    $("#ConfirmUpdateSowingTrayoslot").data("roottype", roottype);
    $("#ConfirmSowingQtymodal").modal("show");
});

$(document).on("click", ".removeQrRow", function () {
    const stockorderno = $("#otherorderdetails").data("dataStockOrderNo");
    const qrcode = $(this).data("barcode");
    const el = this;
    Swal.fire({
        title: "Are you sure?",
        text: "This QR code will be removed",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, remove it"
    }).then((result) => {
        if (result.isConfirmed) {
            RemoveQRCodeofsowingTray(stockorderno, qrcode, el);
        }
    });
});

// Also recalc when trays required changes
$("#TraysRequired").on("input", function () {
    calculateRemainingQRCode();
});

// Add new seed lot card
$("#AddLotNoRow").click(function () {
    let bgClass = getNextBGClass();
    let newCard = `
    <div class="seedLotCard card shadow-sm p-3 mb-3 ${bgClass}">
        <div class="row">
            <div class="col-sm-3">
                <label>Lot Number<br/><small> As per Seed Packet</small></label>
                <input type="text" class="form-control LotNumberofSeed">
            </div>

            <div class="col-sm-3">
                <label>Approx Seed Qty<br/><small> Available in Packate</small> </label>
                <input type="text" class="form-control SeedQtyofLotno">
            </div>

            <div class="col-sm-3">
                <label>QR Code Qty <br/><small>for Trays</small></label>
                <input type="text" class="form-control QRCodeQtyofLotno">
            </div>

            <div class="col-sm-3 d-flex align-items-end">
                <button class="btn btn-primary me-2 SaveQRCodeDetailsBtn">Save QRCode Details</button>
                <button class="btn btn-danger RemoveLotNoRow"><i class="fa fa-trash"></i></button>
            </div>
        </div>
    </div>`;
    $("#seedlotdetailsdiv").append(newCard);
});

// Remove card
$(document).on("click", ".RemoveLotNoRow", function () {
    $(this).closest(".seedLotCard").remove();
});

// save seed lot details with qrcode
$(document).on("click", ".SaveQRCodeDetailsBtn", function () {
    let card = $(this).closest(".seedLotCard");

    let printlot = card.find(".LotNumberofSeed").val();
    let printqty = card.find(".QRCodeQtyofLotno").val();

    if (!printlot || !printqty) {
        alertmessage("warning", "Please enter Lot Number & QR Qty");
        return;
    }
    $("#printbarcodefrom").data("barcodestring", '');
    let projectid = $("body").data("projectid");
    let trayid = $("#sowingtraytype").val();
    let SeedId = $("#SeedNameForSowing").val();
    let roottype = $("#SeedNameForSowing").data("roottype");
    // alert(roottype);
    let sowingquantity = $("#SowingQuantity").val();
    let failurebufferpecent = $("#GerminationFailure").val();
    let SeedName = $("#SeedNameForSowing option:selected").data("seedname");
    let SeedVarietyName = $("#SeedNameForSowing option:selected").data("varietyname");
    let GerminationFailure = $("#GerminationFailure").val();
    let TraysRequired = $("#TraysRequired").val();
    let LotNumberofSeed = printlot;

    let cavitycount = $("#sowingtraytype option:selected").data("cavitycount");
    let StockOrderNo = $("#otherorderdetails").data("dataStockOrderNo");
    let OrderQty = $("#otherorderdetails").data("dataOrderQty");
    let ProductName = $("#otherorderdetails").data("dataProductName");
    let ProductId = $("#otherorderdetails").data("dataProductId");

    if (!trayid) {
        alertmessage("warning", "Please choose the tray, want to use for sowing");
        return;
    } else if (!SeedId) {
        alertmessage("warning", "Please choose the seed, want to use for sowing");
        return;
    } else if (TraysRequired <= 0 || !TraysRequired) {
        alertmessage("warning", "Number of tray required is missing");
        return;
    }
    // Show loading
    Swal.fire({
        title: 'Generating QR Codes...',
        text: 'Please wait while we prepare your tray records.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    SaveAndUpdateSowingDataForQRCodeGenrate(StockOrderNo, ProductId, OrderQty, failurebufferpecent, sowingquantity, trayid, SeedId, printlot, printqty, roottype, function (rrss) {
        Swal.close();
        alertmessage(rrss.status, rrss.message);
        if (rrss.status == "success") {
            generateSeedLotCardsByStockOrder(rrss.stockorderNo, roottype);
        }
    });
});



/**
 * formatedDMY – format date string to DD-MM-YYYY
 */
function formatedDMY(dateStr) {
    if (!dateStr) return "";
    let d = new Date(dateStr);
    let day = String(d.getDate()).padStart(2, '0');
    let month = String(d.getMonth() + 1).padStart(2, '0');
    let year = d.getFullYear();
    return `${day}-${month}-${year}`;
}


// Update Sowing Location Logic
$(document).on("click", ".updswlocationtd", function () {
    let currentLoginRoleId = parseInt($("body").data("loginroleid") || $("body").attr("data-loginroleid") || $("#hidden_loginroleid").val() || (typeof loginroleid !== 'undefined' ? loginroleid : 0)) || 0;
    if (currentLoginRoleId >= 3) {
        return;
    }
    // Find the closest tr to the clicked element (which might be the td or a child of td)
    let row = $(this).closest("tr");

    // If the clicked element IS the td, then `this` is the td.
    // If the button inside the td was clicked (e.g. "Location Missing"), we still want the row.

    let stockorderno = row.data("productionstockorderno");
    let productname = row.data("productname");
    // If data attributes are on the TD, we might need to look there instead:
    // Let's check if the row has the data, OR the clicked element has it.

    // Fallback: check if the TD itself has data attributes (based on previous edits, the TD had the class)
    // But usually data is on the TR. Let's assume TR for now, but be robust.
    if (!stockorderno) {
        stockorderno = $(this).data("productionstockorderno");
    }
    if (!productname) {
        productname = $(this).data("productname");
    }

    let sowinglocationid = row.data("sowinglocationid");
    if (!sowinglocationid) {
        sowinglocationid = $(this).data("sowinglocationid");
    }

    let roottype = row.data("roottype") || $(this).data("roottype") || "";
    let sowingschedulid = row.data("sowingschedulid") || $(this).data("sowingschedulid") || "";

    let projectid = $("body").data("projectid");

    $("#upl_stockorderno").val(stockorderno);
    $("#upl_stockorderno_disp").val(stockorderno);
    $("#upl_productname").val(productname);
    $("#upl_projectid").val(projectid);
    $("#upl_roottype").val(roottype);
    $("#upl_sowingschedulid").val(sowingschedulid);

    LoadProductionLocations(projectid, sowinglocationid);
    // This is actually redundant if LoadProductionLocations shows it, but as a fallback:
    $("#updateSowingLocationModal").modal("show");
    // Better to let the function handle it.
});


function LoadProductionLocations(projectid, selectedId) {
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");

    $.ajax({
        url: "modal/product_modal.php?action=GetProductionLocationList",
        type: "POST",
        data: { projectid: projectid, companyid: companyid, subcompanyid: subcompanyid },
        dataType: "json",
        success: function (res) {
            console.log(res);
            let options = '<option value="">Choose...</option>';
            if (Array.isArray(res)) {
                res.forEach(function (loc) {
                    options += `<option value="${loc.LocationId}">${loc.LocationName}</option>`;
                });
            }
            $("#upl_sowinglocation").html(options);
            if (selectedId && selectedId != 0 && selectedId != "0") {
                $("#upl_sowinglocation").val(selectedId);
                // Trigger change to load polyhouse details if location is already selected
                $("#upl_sowinglocation").trigger("change");
            }
            $("#updateSowingLocationModal").modal("show");
        }
    });
}

// On change showing polyhouse data or capacity
$("#upl_sowinglocation").change(function () {
    let locationid = $(this).val();
    let projectid = $("body").data("projectid");

    if (!locationid) {
        $("#polyhouseCapacityDetails").html("");
        return;
    }

    $.ajax({
        url: "modal/polihouse_modal.php?action=GetPolyhouseTrayUsageByLocation",
        type: "POST",
        data: {
            locationid: locationid,
            projectid: projectid
        },
        dataType: "json",
        success: function (res) {
            let html = "";
            if (Array.isArray(res) && res.length > 0) {
                let totalCap = 0;
                let totalUsed = 0;
                let totalRemaining = 0;

                html += `<table class="table table-bordered table-sm mt-3">
                                <thead>
                                    <tr>
                                        <th>Polyhouse</th>
                                        <th>Structure</th>
                                        <th>Total Cap</th>
                                        <th>Used</th>
                                        <th>Remaining</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                res.forEach(function (ph) {
                    let cap = parseInt(ph.total_tray_capacity) || 0;
                    let used = parseInt(ph.total_tray_exists) || 0;
                    let remaining = parseInt(ph.remaining_total_tray) || 0;
                    totalCap += cap;
                    totalUsed += used;
                    totalRemaining += remaining;
                    html += `<tr>
                                    <td>${ph.polyhousename}</td>
                                    <td>${ph.structure_type}</td>
                                    <td>${ph.total_tray_capacity}</td>
                                    <td>${ph.total_tray_exists}</td>
                                    <td>${ph.remaining_total_tray}</td>
                                    </tr>`;
                });
                html += `</tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="2">TOTAL</td>
                                        <td>${totalCap}</td>
                                        <td>${totalUsed}</td>
                                        <td>${totalRemaining}</td>
                                    </tr>
                                </tfoot>
                            </table>`;
            } else {
                html = "<p class='text-muted mt-2'>No polyhouses found for this location.</p>";
            }
            $("#polyhouseCapacityDetails").html(html);
        },
        error: function (err) {
            console.error("Error fetching polyhouse usage:", err);
            $("#polyhouseCapacityDetails").html("<p class='text-danger mt-2'>Error fetching details.</p>");
        }
    });
});

$("#saveSowingLocationBtn").click(function () {
    let stockorderno = $("#upl_stockorderno").val();
    let sowinglocationid = $("#upl_sowinglocation").val();
    let projectid = $("#upl_projectid").val();
    let loginid = $("body").data("loginid");
    let roottype = $("#upl_roottype").val() || "";
    let sowingschedulid = $("#upl_sowingschedulid").val() || "";

    if (!sowinglocationid) {
        alert("Please select a location");
        return;
    }

    $.ajax({
        url: "modal/product_modal.php?action=UpdateSowingLocation",
        type: "POST",
        data: { stockorder_no: stockorderno, sowinglocationid: sowinglocationid, projectid: projectid, loginid: loginid, roottype: roottype, sowingschedulid: sowingschedulid },
        dataType: "json",
        success: function (res) {
            if (res.status == "success") {
                $("#updateSowingLocationModal").modal("hide");
                // Refresh
                if ($("#sowingweekSelector").length > 0) {
                    loadSowingScheduleStarting($("#sowingweekSelector").val());
                } else {
                    $("#sowingDateList").trigger("change");
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: 'Sowing location updated successfully.',
                    timer: 1500
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
});

function getSowingRowBgStyle(sowingDate, pendingQty) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const sowDate = new Date(sowingDate);
    sowDate.setHours(0, 0, 0, 0);

    pendingQty = parseInt(pendingQty) || 0;

    // ✅ Completed on/before today
    if (sowDate <= today && pendingQty === 0) {
        return "background: linear-gradient(120deg, #e6ffee, #f3ffff);";
    }

    // 🔴 Future date & pending exists
    if (sowDate < today && pendingQty > 0) {
        return "background: linear-gradient(120deg, #fdeaea, #fff5f5);";
    }

    // 🔵 Future date & pending exists
    if (sowDate > today && pendingQty > 0) {
        return "background: linear-gradient(120deg, #eff1ff, #fff3ea);";
    }

    // 🟡 Overdue & pending exists
    if (sowDate <= today && pendingQty > 0) {
        return "background: linear-gradient(120deg, #fff8e1, #fffdf3);";
    }
    return "";
}

function loadSowingScheduleStarting(selectedWeek = "current") {
    GetActveSowingdates(function (sdres) {
        // Ensure sdres is an array
        if (!Array.isArray(sdres)) sdres = [];

        // 1. Build Week List
        let weekMap = {};
        sdres.forEach(item => {
            if (!item.frsowingdate) return;
            let d = new Date(item.frsowingdate);
            let day = d.getDay();
            let diffToMonday = (day === 0) ? -6 : (1 - day);
            let monday = new Date(d);
            monday.setDate(d.getDate() + diffToMonday);
            monday.setHours(0, 0, 0, 0);

            let sunday = new Date(monday);
            sunday.setDate(monday.getDate() + 6);
            sunday.setHours(23, 59, 59, 999);

            let ws = monday.toISOString().split("T")[0];
            let we = sunday.toISOString().split("T")[0];
            weekMap[ws] = we;
        });

        // 2. Current Week & Month Logic
        let today = new Date();
        let curMonthStr = today.toISOString().substring(0, 7); // yyyy-mm

        let tDay = today.getDay();
        let tDiff = (tDay === 0) ? -6 : (1 - tDay);
        let tMon = new Date(today);
        tMon.setDate(today.getDate() + tDiff);
        let tSun = new Date(tMon);
        tSun.setDate(tMon.getDate() + 6);
        let tWS = tMon.toISOString().split("T")[0];
        let tWE = tSun.toISOString().split("T")[0];
        let currentWeekRange = `${tWS} to ${tWE}`;

        if (!weekMap[tWS]) {
            weekMap[tWS] = tWE;
        }

        let sortedWeeks = Object.keys(weekMap).sort();

        // 3. Populate Month Filter once
        if ($("#sowingmonthFilter option").length <= 1) {
            let months = new Set();
            sortedWeeks.forEach(ws => months.add(ws.substring(0, 7)));
            months.add(curMonthStr);

            let monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

            Array.from(months).sort().forEach(m => {
                let [y, mi] = m.split("-");
                let mName = monthNames[parseInt(mi) - 1];
                let display = `${mName}-${y}`;
                $("#sowingmonthFilter").append(`<option value="${m}">${display}</option>`);
            });

            $("#sowingmonthFilter").val(curMonthStr);
        }

       let selectedMonth = $("#sowingmonthFilter").val() || "all";
        let displayWeeks = sortedWeeks.filter(ws => {
            if (selectedMonth === "all") return true;
            return ws.substring(0, 7) === selectedMonth;
        });

        let $week = $("#sowingweekSelector");
        $week.html("");
        displayWeeks.forEach(ws => {
            let range = `${ws} to ${weekMap[ws]}`;
            $week.append(`<option value="${range}">${range}</option>`);
        });

        // 5. Select Week
        if (selectedWeek === "current") {
            selectedWeek = currentWeekRange;
        } else if (selectedWeek === "first" && displayWeeks.length > 0) {
            selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
        } else if (selectedWeek === "last" && displayWeeks.length > 0) {
            let lastWs = displayWeeks[displayWeeks.length - 1];
            selectedWeek = `${lastWs} to ${weekMap[lastWs]}`;
        }

        if ($week.find(`option[value="${selectedWeek}"]`).length === 0) {
            if (displayWeeks.length > 0) {
                selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
            } else {
                if ($week.find(`option[value="${currentWeekRange}"]`).length === 0) {
                    $week.append(`<option value="${currentWeekRange}">${currentWeekRange}</option>`);
                }
                selectedWeek = currentWeekRange;
            }
        }
        $week.val(selectedWeek);

        // 6. Fetch Data
        let [start, end] = selectedWeek.split(" to ");
        fetchAndRenderSowingData(start, end);
    });
}

function fetchAndRenderSowingData(start, end) {
    GetProductionStockOrderDataWithDate(start, end, function (res) {
        renderSowingTable(res);
    });
}

function renderSowingTable(res) {
    let prdotr = "";
    if (Array.isArray(res) && res.length > 0) {
        // Sort by Sowing Date ASC
        res.sort((a, b) => new Date(a.sowingdate) - new Date(b.sowingdate));

        for (let i = 0; i < res.length; i++) {
            const sowingTrayCount = parseInt(res[i].sowingtraycount) || 0;
            const gerBtnDisabled = sowingTrayCount > 0 ? "" : "disabled";
            const germinationboxinfo = Array.isArray(res[i].germinatinboxdetails) ? res[i].germinatinboxdetails : [];
            const lotdetails = Array.isArray(res[i].lotdetails) ? res[i].lotdetails : [];

            let germinationbtn = "";
            let sowingtrybtn = "";

            if (germinationboxinfo.length > 0) {
                let btnColor = res[i].pendingsowingqty > 0 ? "background: linear-gradient(120deg, #f6d365, #fda085);" : "background: linear-gradient(120deg, #2af598, #009efd);";
                germinationbtn = `
                    <button class="btn btn-sm readytoprintgBoxbtntd" style="${btnColor}" data-gboxdata="${encodeURIComponent(JSON.stringify(germinationboxinfo))}">
                        Print QR Code<br/><small>For Germination</small>
                    </button>`;
            } else {
                germinationbtn = `
                    <button style="background: linear-gradient(120deg, #a1c4fd, #c2e9fb);" class="btn btn-sm btn-info startgerbtntd" ${gerBtnDisabled}>
                        Make a Box<br/><small>For Germination</small>
                    </button>`;
            }

            if (res[i].totalsowingqty > 0) {
                let btnColor = res[i].pendingsowingqty === 0 ? "background: linear-gradient(120deg, #2af598, #009efd);" : "background: linear-gradient(120deg, #f6d365, #fda085);";
                sowingtrybtn = `
                    <button class="btn btn-sm printqrcodebtntd" style="${btnColor}">
                        Print QR Code<br/><small>For Sowing</small>
                    </button>`;
            } else {
                sowingtrybtn = `
                    <button class="btn btn-sm printqrcodebtntd" style="background: linear-gradient(120deg, #a1c4fd, #c2e9fb);">
                        Print QR Code<br/><small>For Sowing</small>
                    </button>`;
            }

            let currentLoginRoleId = parseInt($("body").data("loginroleid") || $("body").attr("data-loginroleid") || $("#hidden_loginroleid").val() || (typeof loginroleid !== 'undefined' ? loginroleid : 0)) || 0;

            let missingLocationDisplay = "";
            if (currentLoginRoleId < 3) {
                missingLocationDisplay = `
                    <button class="btn btn-sm updswlocationtd" style="background: linear-gradient(220deg, #a1c4fd, #c2f9ff);">
                        Location Missing<br/><small>For Sowing</small>
                    </button>`;
            } else {
                missingLocationDisplay = `<span class="fw-bold" style="color: red;">Missing sowing location</span>`;
            }

            let sowinglocation = (res[i].sowinglocationid && res[i].sowinglocationid != "0") ? res[i].sowinglocationname : missingLocationDisplay;

            const style = getSowingRowBgStyle(res[i].sowingdate, res[i].pendingsowingqty);
            let isGrafted = false;
            let rootSeedId = (res[i].productRootSeedid || '').toString().trim();
            let scionSeedId = (res[i].productScionSeedid || '').toString().trim();
            let prdName = (res[i].product_name || '').toString().toLowerCase();

            if (res[i].isgrafted === 'yes' || res[i].isgrafted === 'Yes') {
                isGrafted = true;
            } else if (rootSeedId && scionSeedId && rootSeedId !== scionSeedId) {
                isGrafted = true;
            } else if (prdName.indexOf('nongrafted') === -1 && prdName.indexOf('non-grafted') === -1 && (prdName.startsWith('grafted') || prdName.indexOf('grafted') > -1)) {
                isGrafted = true;
            }

            let seedVarietyDisplay = res[i].seedname || '';
            if (isGrafted && res[i].roottype) {
                seedVarietyDisplay += ` (${res[i].roottype})`;
            }

            prdotr += `
                <tr data-productionstockorderno="${res[i].stockorder_no}" data-expecteddeliverydate="${res[i].expecteddeliverydate}"
                    data-sowingschedulid="${res[i].sowingschedulid || ''}"
                    data-productcategoryid="${res[i].productCategoryid}" data-productrootseedid="${res[i].productRootSeedid}"
                    data-productscionseedid="${res[i].productScionSeedid}" data-roottrayid="${res[i].roottrayid}"
                    data-sciontrayid="${res[i].sciontrayid}" data-productid="${res[i].productid}"
                    data-productname="${res[i].product_name}" data-sowingdate="${res[i].sowingdate}"
                    data-roottype="${res[i].roottype}" data-seedid="${res[i].seedid}"
                    data-seedname="${res[i].seedname}" data-orderqty="${res[i].orderqty}"
                    data-sowinglocationid="${res[i].sowinglocationid}" data-failurebufferpercent="${res[i].failurebufferpercent}"
                    data-totalsowingqty="${res[i].totalsowingqty}" data-sowingtraycount="${sowingTrayCount}"
                    data-lotdetails="${encodeURIComponent(JSON.stringify(lotdetails))}" data-gboxdata="${encodeURIComponent(JSON.stringify(germinationboxinfo))}">
                    <td style="${style}">${i + 1}</td>
                    <td style="${style}">${sowinglocation}</td>
                    <td style="${style}">${res[i].stockorder_no}</td>
                    <td style="${style}">${changeDateFormatYmd_dMY(res[i].sowingdate)}</td>
                    <td style="${style}">${seedVarietyDisplay}</td>
                    <td style="${style}">${res[i].product_name}</td>
                    <td style="${style}">${res[i].orderqty}</td>
                    <td style="${style}">${res[i].totalsowingqty}</td>
                    <td style="${style}">${res[i].pendingsowingqty}</td>
                    <td style="${style}">${sowingtrybtn}</td>
                    <td style="${style}">${germinationbtn}</td>                            
                </tr>`;
        }
    } else {
        prdotr = `<tr><td colspan="11" class="text-center text-muted">No records found for this week</td></tr>`;
    }
    $("#scheduleTable").html(prdotr);
}

// ==========================================
// 📄 STICKER & PDF DOWNLOAD HELPERS (RESTORED)
// ==========================================

$('#viewbarcodelistforadjt_modal').on('shown.bs.modal', function () {
    if (!window.qrRenderData) return;

    window.qrRenderData.forEach((row, index) => {
        const id = index + 1;
        let box = document.getElementById(`qr_${id}`);
        if (!box) return;

        box.innerHTML = "";
        new QRCode(box, {
            text: row.barcode,
            width: 75,
            height: 75
        });
    });
});

window.downloadQRStickerPDF = function (containerId) {
    const source = document.getElementById(containerId);
    if (!source) return;

    const pdffilename = source.dataset.pdffilename || "stickers";

    // 1. Check for thermal sheet (created by renderSowingQRCodes)
    const thermalSheet = source.querySelector(".thermal-sheet");

    if (thermalSheet) {
        // === THERMAL PDF MODE ===

        // Clone the element to isolate it from modal styles/scrolls
        const clone = thermalSheet.cloneNode(true);

        // Convert Canvas to Images
        const originalCanvases = thermalSheet.querySelectorAll('canvas');
        const clonedCanvases = clone.querySelectorAll('canvas');

        originalCanvases.forEach((orig, i) => {
            const dest = clonedCanvases[i];
            if (dest) {
                try {
                    const img = document.createElement('img');
                    img.src = orig.toDataURL("image/png");
                    img.style.width = '100%'; // Ensure it fills container
                    img.style.height = '100%';
                    dest.parentNode.replaceChild(img, dest);
                } catch (e) {
                    console.error("Canvas to Image conversion failed:", e);
                }
            }
        });

        // Set styles to ensure it starts at 0,0 of the viewport
        clone.style.cssText = `
            position: fixed; 
            top: 0; 
            left: 0; 
            z-index: 99999; 
            margin: 0; 
            padding: 0; 
            background: #fff; 
            width: auto;
            height: auto;
        `;

        // Temporarily add to body
        document.body.appendChild(clone);

        // Calculate dimensions from the now DOM-attached clone
        const wPx = $(clone).outerWidth();
        const hPx = $(clone).outerHeight();

        // Add a small buffer for safety
        const wMm = (wPx / 3.78) + 0.5;
        const hMm = (hPx / 3.78) + 0.5;

        const opt = {
            margin: 0,
            filename: pdffilename + ".pdf",
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                windowWidth: document.documentElement.scrollWidth,
                windowHeight: document.documentElement.scrollHeight
            },
            jsPDF: {
                unit: 'mm',
                format: [wMm, hMm],
                orientation: 'portrait'
            }
        };

        // Render the thermal sheet directly as-is
        html2pdf().from(clone).set(opt).save().then(function () {
            document.body.removeChild(clone);
        });

    } else {
        // === FALLBACK A4 MODE ===
        const stickers = source.querySelectorAll(".barcode-box");
        if (stickers.length === 0) {
            alert("No stickers to download");
            return;
        }

        const wrapper = document.createElement("div");
        let page;

        stickers.forEach((sticker, index) => {
            // New page after every 24 stickers
            if (index % 24 === 0) {
                page = document.createElement("div");
                page.className = "print-area"; // This class enforces A4 width/height in CSS
                wrapper.appendChild(page);
            }
            page.appendChild(sticker.cloneNode(true));
        });

        const opt = {
            margin: 0,
            filename: pdffilename + ".pdf",
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
            pagebreak: { mode: ["css"] }
        };
        html2pdf().from(wrapper).set(opt).save();
    }
};

window.printThermalSheet = function (targetId) {
    var $target = $("#" + targetId);
    if ($target.length === 0) {
        console.error("Print target not found: " + targetId);
        return;
    }

    var $overlay = $("#printing-overlay");
    if ($overlay.length === 0) {
        $overlay = $('<div id="printing-overlay"></div>');
        $("body").append($overlay);
    }

    var content = $target.html();
    $overlay.html(content);

    $("body").addClass("is-printing");

    setTimeout(function () {
        window.print();
    }, 200);

    window.onafterprint = function () {
        $("body").removeClass("is-printing");
        $overlay.empty();
    };
};














































        // 4. Populate weekSelector based on month filter






        // 5. Select Week
        if (selectedWeek === "current") {
            selectedWeek = currentWeekRange;
        } else if (selectedWeek === "first" && displayWeeks.length > 0) {
            selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
        } else if (selectedWeek === "last" && displayWeeks.length > 0) {
            let lastWs = displayWeeks[displayWeeks.length - 1];
            selectedWeek = `${lastWs} to ${weekMap[lastWs]}`;

























        }



