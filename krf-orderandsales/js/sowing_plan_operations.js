/**
 * Abbreviates Grafted -> GR and NonGrafted -> NGR
 */
function formatGraftedName(name) {
    if (!name) return "";
    
    // 1. Check NonGrafted first
    if (/non[\s_-]?grafted/i.test(name)) {
        return name.replace(/non[\s_-]?grafted/i, 'NGR');
    }
    // 2. Check Grafted next
    if (/grafted/i.test(name)) {
        return name.replace(/grafted/i, 'GR');
    }
    
    // 3. Keep rest as-is
    return name;
}

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
    $("#fltlocation").val("");
    $("#fltproduct").val("");
    $("#oSearchInput").val("");
    $("#scheduleTable tr").show();

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
        generateSeedLotCardsByStockOrder(mdOrderNo, roottype);
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


$(document).on("click", ".btnnongrafted", function () {
    const $row = $(this).closest("tr");
    Swal.fire({
        title: 'Germination Option',
        text: 'Do you want to germinate by creating a box or without a box?',
        icon: 'question',

        showCancelButton: true,
        showDenyButton: true,

        confirmButtonText: 'Germinate Box',
        denyButtonText: 'Germinate Without Box',
        cancelButtonText: 'Cancel',

        confirmButtonColor: '#198754',
        denyButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',

        allowOutsideClick: false,
        allowEscapeKey: false

    }).then((result) => {

        if (result.isConfirmed) {
            startGermination($row);
        } else if (result.isDenied) {

            startGermination_withoutbox($row);
        

        } else if (result.dismiss === Swal.DismissReason.cancel) {

            // OPTION 3
            console.log('Cancelled');
            return;
        }
    });

});

$(document).on("click", ".startgerbtntd", function () {

    const $row = $(this).closest("tr");

    startGermination($row);
}); 
$(document).on("click", ".startgerbtntd_withoutbox", function () {

    const $row = $(this).closest("tr");

    startGermination_withoutbox($row);
}); 
$(document).on("click", ".undomakeboxbtn", function () {
    const $button = $(this);
    const $row = $button.closest("tr");
    const stockorderno = $row.data("productionstockorderno");
    const productid    = $row.data("productid");
    if (!stockorderno || !productid) {
        alert("Stock Order No or Product ID is missing.");
        return;
    }
    if (!confirm("Are you sure you want to undo Make a Box?")) {
        return;
    }
    $button.prop("disabled", true).text("Undoing...");
    $.ajax({
        url: "modal/direct_sowing.php?action=undoMakeGerminationBox",
        type: "POST",
        dataType: "json",
        data: { 
            stockorder_no: stockorderno,
            productid: productid
        },
        success: function (res) {
            if (res.success) {
                alert(res.message || "Make a Box successfully undone.");
                window.location.reload();
            } else {
                alert(res.message || "Unable to undo Make a Box.");
                $button.prop("disabled", false).text("Undo Make a Box");
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
            alert("Server error while undoing Make a Box.");
            $button.prop("disabled", false).text("Undo Make a Box");
        }
    });
}); 
function startGermination_withoutbox($row) {

    if (!$row || !$row.length) {
        console.error("Germination row not found.");
        return false;
    }
    // debugger;
    orderno=  $row.data("productionstockorderno")|| "";
    productid=  $row.data("productid") || "";
    const formData = new FormData(); 
    formData.append('projectid', $("#hidden_projectid").val());
    formData.append('loginid', $("#hidden_loginname").val());
    formData.append('pstockorderno', orderno);
    formData.append('productid', productid); 
    formData.append('changestatus', 'Tray Received At Germination'); 
    formData.append('tableactualstatus', 'Sowing'); 
    $.ajax({
        type: "POST",
        url: `modal/direct_sowing.php?action=Updatestatusstage1`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function (res) {
             if (!res || res.status === "error" ) {
                  alert(res.message);
                  return ;
             }
             window.location.reload();
        },
        error: function () {
            alert("error");
        }
    });

}
function startGermination($row) {

    if (!$row || !$row.length) {
        console.error("Germination row not found.");
        return false;
    } 
    // Get row data 
    const expecteddeliverydate = $row.data("expecteddeliverydate");
    const SeedCatId = $row.data("productcategoryid");
    const RootSeedId = $row.data("productrootseedid");
    const ScionSeedId = $row.data("productscionseedid");

    const roottype = $row.data("roottype");
    const roottrayid = $row.data("roottrayid");
    const sciontrayid = $row.data("sciontrayid");

    const sowinglocationid = $row.data("sowinglocationid");

    const productionstockorderno =
        $row.data("productionstockorderno") || "";

    const mdProductName =
        $row.data("productname") || "";

    const mdProductId =
        $row.data("productid") || "";

    const seedid =
        $row.data("seedid") || "";

    const sowingtraycount =
        parseInt($row.data("sowingtraycount")) || 0;

    const mdOrderQty =
        parseInt($row.data("orderqty")) || 0;

    const failurebufferpercent =
        parseFloat($row.data("failurebufferpercent")) || 30;

    const totalsowingqty =
        parseInt($row.data("totalsowingqty")) || 0;

    const sowingdate =
        $row.data("sowingdate") || "";


    // ---------------------------------
    // Validate sowing location
    // ---------------------------------
    if (
        !sowinglocationid ||
        sowinglocationid === "undefined"
    ) {

        Swal.fire({
            icon: "error",
            title: "Sowing Location",
            text: "Sowing location is missing. Please update sowing location first."
        });

        return false;
    }


    // ---------------------------------
    // Clear previous modal values
    // ---------------------------------
    $(".productNamemd_pck").empty();
    $(".productOrderNo_pck").empty();
    $(".productOrderQty_pck").empty();
    $(".sowingOrderQty_pck").empty();
    $(".sowingTrayQty_pck").empty();


    // ---------------------------------
    // Store data on save button
    // ---------------------------------
    $("#saveGerminationDetailsBtnpck")
        .data("datastockordernopck", productionstockorderno)
        .data("dataorderqtypck", mdOrderQty)
        .data("dataproductnamepck", mdProductName)
        .data("dataproductidpck", mdProductId)
        .data("swtrayscount", mdOrderQty)
        .data("roottype", roottype);


    // ---------------------------------
    // Packing date
    // ---------------------------------
    const todayDateStr =
        new Date().toISOString().split("T")[0];

    $("#packingdate").val(todayDateStr);


    // ---------------------------------
    // Fill modal
    // ---------------------------------
    $(".productNamemd_pck").html(mdProductName);
    $(".productOrderNo_pck").html(productionstockorderno);
    $(".productOrderQty_pck").html(mdOrderQty);
    $(".sowingOrderQty_pck").html(totalsowingqty);


    // ---------------------------------
    // Open modal
    // ---------------------------------
    $("#germpackingQRCodeModal").modal("show");


    // ---------------------------------
    // Load tray details
    // ---------------------------------
    sowingtrysofgerminationbox(
        productionstockorderno,
        roottype,
        "alreadyprintedboxqrcode"
    );

    pendingsowingtrysofgerminationbox(
        productionstockorderno,
        roottype
    );

    // Load suggested box open days for this product
    loadSuggestedBoxOpenDays(mdProductId, mdProductName, productionstockorderno);

    return true;
}
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

    let fullBoxes = Math.floor(clsowingTraysCout / boxTrayCount);
    let partialTrays = clsowingTraysCout % boxTrayCount;

    // Set to FULL boxes only - do not generate extra box
    $("#totalboxesforgerm").val(fullBoxes);
    $("#counttiprintqrcode").val(fullBoxes);
    $("#counttiprintqrcode").prop("max", fullBoxes);
    $("#counttiprintqrcode").prop("min", fullBoxes > 0 ? 1 : 0);

    let breakdownText = "";
    let badgesHtml = "";

    if (fullBoxes === 0) {
        breakdownText = `<span class="text-danger fw-bold"><i class="fa fa-exclamation-triangle me-1"></i> Not enough trays to form a full box (Requires ${boxTrayCount} trays, available: ${clsowingTraysCout})</span>`;
        badgesHtml = `<span class="badge bg-danger px-3 py-2 fs-6"><i class="fa fa-times me-1"></i> 0 Full Boxes</span>`;
    } else if (partialTrays === 0) {
        breakdownText = `<b>${fullBoxes} Full Box${fullBoxes > 1 ? 'es' : ''}</b> (${boxTrayCount} trays each)`;
        badgesHtml = `<span class="badge bg-primary px-3 py-2 fs-6"><i class="fa fa-box me-1"></i> ${fullBoxes} Full Box${fullBoxes > 1 ? 'es' : ''} (${boxTrayCount} trays/box)</span>`;
    } else {
        breakdownText = `<b>${fullBoxes} Full Box${fullBoxes > 1 ? 'es' : ''}</b> (${boxTrayCount} trays each) &nbsp;|&nbsp; <span class="text-secondary"><b>${partialTrays} Leftover Tray${partialTrays > 1 ? 's' : ''}</b> (remains in pending)</span>`;
        badgesHtml = `
            <span class="badge bg-success px-3 py-2 fs-6"><i class="fa fa-box me-1"></i> ${fullBoxes} Full Box${fullBoxes > 1 ? 'es' : ''} (${boxTrayCount} trays each)</span>
            <span class="badge bg-secondary px-3 py-2 fs-6" title="Leftover trays will remain in pending trays for future packing"><i class="fa fa-cubes me-1"></i> ${partialTrays} Leftover Tray${partialTrays > 1 ? 's' : ''}</span>
        `;
    }

    $("#boxBreakdownText").html(breakdownText);
    $("#boxBreakdownBadges").html(badgesHtml);
    $("#boxBreakdownContainer").slideDown(200);
});

$(document).on("input", "#counttiprintqrcode", function () {
    let clsowingTraysCout = parseInt($(".sowingTrayQty_pck").text()) || 0;
    let boxTrayCount = parseInt($("#traycountinbox").val()) || 0;
    let maxFullBoxes = boxTrayCount > 0 ? Math.floor(clsowingTraysCout / boxTrayCount) : (parseInt($("#totalboxesforgerm").val()) || 0);
    let printboxcount = parseInt($(this).val()) || 0;

    if (printboxcount > maxFullBoxes) {
        $(this).val(maxFullBoxes);
        Swal.fire({
            icon: 'warning',
            title: 'Limit Exceeded',
            text: 'You can only generate up to ' + maxFullBoxes + ' full box(es). Extra partial boxes cannot be generated.'
        });
    } else if (printboxcount < 0) {
        $(this).val(0);
    }
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

$(document).on("change input", "#packingdate", function () {
    let packingDate = $(this).val();
    let openafteDays = $("#boxopenafterdays").val();
    if (openafteDays > 0 && packingDate) {
        $("#dateofopening").val(adddaysingivedate(packingDate, openafteDays));
    }
});

$(document).on("click", "#saveGerminationDetailsBtnpck", function () {
    let btn = $(this);
    // Prevent double clicking / disabled state check
    if (btn.prop("disabled")) {
        return;
    }

    let StockOrderNo = btn.data("datastockordernopck");
    let ProductName = btn.data("dataproductnamepck");
    let ProductId = btn.data("dataproductidpck");
    let roottype = btn.data("roottype");

    let traycountinbox = $("#traycountinbox").val().trim();
    let countboxqrcodetoprint = $("#counttiprintqrcode").val().trim();

    let packingdate = $("#packingdate").val().trim();
    let dateofopening = $("#dateofopening").val().trim();

    // Validations
    if (!StockOrderNo) {
        Swal.fire({
            icon: "warning",
            title: "Missing Stock Order No",
            text: "Stock Order Number is required to save germination details."
        });
        return;
    }

    let trayCountNum = parseInt(traycountinbox, 10) || 0;
    if (trayCountNum <= 0) {
        $("#traycountinbox").focus();
        Swal.fire({
            icon: "warning",
            title: "Invalid Tray Count",
            text: "Please enter a valid number of trays per box."
        });
        return;
    }

    let clsowingTraysCout = parseInt($(".sowingTrayQty_pck").text()) || 0;
    let maxFullBoxes = Math.floor(clsowingTraysCout / trayCountNum);
    let printboxcount = parseInt(countboxqrcodetoprint, 10) || 0;

    if (printboxcount <= 0) {
        $("#counttiprintqrcode").focus();
        Swal.fire({
            icon: "warning",
            title: "Invalid Box Count",
            text: "Please enter at least 1 full box to generate QR code."
        });
        return;
    }

    if (printboxcount > maxFullBoxes) {
        $("#counttiprintqrcode").val(maxFullBoxes);
        Swal.fire({
            icon: "warning",
            title: "Limit Exceeded",
            text: "You can only generate up to " + maxFullBoxes + " full box(es)."
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

    // 🚀 Call SaveAndUpdateGerminationData (handles SweetAlert confirmation & button disable/enable)
    SaveAndUpdateGerminationData(StockOrderNo, ProductId, ProductName, trayCountNum, packingdate, dateofopening, printboxcount, roottype, function (res) {
        if (res.status == "success") {
            Swal.fire({
                icon: "success",
                title: "Saved!",
                text: res.message,
                timer: 1800,
                showConfirmButton: false
            });
            $("#germpackingQRCodeModal").modal("hide");
            $(".CloseForTodaybtn").trigger("click");
            $("#traycountinbox").val(0).trigger("input");
            let todayDateStr = new Date().toISOString().split('T')[0];
            $("#packingdate").val(todayDateStr);
            $("#dateofopening").val('');
            $("#boxopenafterdays").val('');

            if ($("#sowingweekSelector").length) {
                $("#sowingweekSelector").trigger("change");
            }
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
    let btn = $(this);
    let originalHtml = btn.data("original-html") || btn.html();
    btn.data("original-html", originalHtml);
    let projectid = $("body").data("projectid");
    // Prevent double clicking
    if (btn.prop("disabled")) return;

    btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Printing...');

    let productionorderno = btn.data("stockorderno") ?? '';
    let seedname = btn.data("seedname") ?? '';
    let lotno = btn.data("lotno") ?? '';
    let deliverydate = btn.data("deliverydate") ?? '';
    let sowingdate = btn.data("sowingdate") ?? '';
    let sowingtraycount = btn.data("sowingtraycount") ?? '';
    let productname = btn.data("productname") ?? '';

    let sowindateinternal = btn.data("sowindateinternal") ?? '';
    let dispatchdateinternal = btn.data("dispatchdateinternal") ?? '';
    // alert(sowindateinternal+'--'+dispatchdateinternal);

    let traw = btn.data("qrcodedata");
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
        btn.prop("disabled", false).html(originalHtml);
        return;
    }

    if (qrcodedata.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No QR Codes',
            text: 'No QR codes found to print.'
        });
        btn.prop("disabled", false).html(originalHtml);
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

    if (!deliverydate || !sowingdate) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Dates',
            text: 'Sowing Date or Deliver Date is missing for these items.'
        });
        btn.prop("disabled", false).html(originalHtml);
        return;
    }

    const formData = new FormData();
    formData.append('productionorderno', productionorderno);
    formData.append('seedname', seedname);
    formData.append('lotno', lotno);
    formData.append('traycountinlot', traycountinlot);
    formData.append('sowindateinternal', sowindateinternal);
    formData.append('dispatchdateinternal', dispatchdateinternal);
    formData.append('sowingtraycount', sowingtraycount);
    formData.append('traycavity', traycavity);
    formData.append('projectid', projectid);
    // alert(projectid);
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
        // console.log(textData);
        // Trigger actual printing via QZ Tray
        await printLabel(textData);

    } catch (error) {
        console.error("Printing request failed:", error);
        if (error.message && !error.message.includes("Printing Failed")) {
            Swal.fire({
                icon: 'error',
                title: 'Network error',
                text: 'Failed to connect to the label generation service or QZ Tray: ' + error.message
            });
        }
    } finally {
        btn.prop("disabled", false).html(originalHtml);
    }
});

$(document).on("click", ".readytoprintgBoxbtntd", async function () {
    let btn = $(this);
    let originalHtml = btn.data("original-html") || btn.html();
    btn.data("original-html", originalHtml);

    // Prevent double clicking
    if (btn.prop("disabled")) return;

    btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Printing...');

    let raw = btn.data("gboxdata");
    let productionorderno = btn.data("productionorderno") ?? '';
    let seedname = btn.data("seedname") ?? '';

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

    // 1. Basic Parameter Validation
    if (!productionorderno || !seedname) {
        Swal.fire({
            icon: 'warning',
            title: 'Required Data Missing',
            text: 'Production Order No or Seed Name is missing. Please refresh the page and try again.'
        });
        btn.prop("disabled", false).html(originalHtml);
        return;
    }

    if (grcodelist.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No QR Codes',
            text: 'No QR codes found for this box to print.'
        });
        btn.prop("disabled", false).html(originalHtml);
        return;
    }

    if (!packingdatelb || !openingdatelb) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Dates',
            text: 'Packing Date or Opening Date is missing for these items.'
        });
        btn.prop("disabled", false).html(originalHtml);
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
        // console.log(textData);
        await printLabel(textData);

    } catch (error) {
        console.error("Printing request failed:", error);
        if (error.message && !error.message.includes("Printing Failed")) {
            Swal.fire({
                icon: 'error',
                title: 'Network error',
                text: 'Failed to connect to the label generation service or QZ Tray: ' + error.message
            });
        }
    } finally {
        btn.prop("disabled", false).html(originalHtml);
    }
});



async function ensureConnected() {
    // Setup secure signing if available
    if (typeof qz !== 'undefined' && !qz.security.certHandler) {
        const routurl = $("body").data("routurl") || "";
        qz.security.setCertificatePromise(function(resolve, reject) {
            $.ajax({ 
                url: routurl + "krf-orderandsales/qz_certificate.txt", 
                cache: false, 
                dataType: "text" 
            }).then(resolve, reject);
        });

        qz.security.setSignaturePromise(function(toSign) {
            return function(resolve, reject) {
                $.post(routurl + "krf-orderandsales/qz_signer.php", { request: toSign })
                 .then(resolve, reject);
            };
        });
    }

    let connectOpts = {
        usingSecure: window.location.protocol === 'https:',
        port: {
            secure: [8181],
            insecure: [8182]
        }
    };

    if (!qz.websocket.isActive()) {
        try {
            await qz.websocket.connect(connectOpts);
        } catch (err) {
            throw new Error("Unable to establish connection with QZ Tray. Please ensure QZ Tray is running on your computer.");
        }
        return;
    }

    try {
        qz.websocket.getConnectionInfo();
    } catch (e) {
        let retries = 10;
        let success = false;
        while (retries > 0) {
            await new Promise(resolve => setTimeout(resolve, 500));
            try {
                if (!qz.websocket.isActive()) {
                    await qz.websocket.connect(connectOpts);
                    success = true;
                    break;
                }
                qz.websocket.getConnectionInfo();
                success = true;
                break;
            } catch (retryErr) {
                // Keep waiting
            }
            retries--;
        }
        if (!success) {
            // Force reset and connect
            try {
                await qz.websocket.disconnect();
            } catch (disErr) {}
            try {
                await qz.websocket.connect(connectOpts);
            } catch (err) {
                throw new Error("Unable to establish connection with QZ Tray. Please ensure QZ Tray is running on your computer.");
            }
        }
    }
}

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
        // Robust connection check using helper
        await ensureConnected();

        // Get all printers to find the best match (TSC or LP 46 Neo)
        let printer = null;
        const printerSearchTerms = ["TSC", "LP 46", "LP46", "LP", "Neo", "Thermal"];
        
        for (const term of printerSearchTerms) {
            try {
                let found = await qz.printers.find(term);
                if (Array.isArray(found) && found.length > 0) {
                    printer = found[0];
                    console.log(`Found printer matching '${term}':`, printer);
                    break;
                }
            } catch (err) {
                console.warn(`Error searching for printer term '${term}':`, err);
            }
        }

        if (!printer) {
            printer = await qz.printers.getDefault();
            console.log("No specific printer found matching terms, using default:", printer);
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
        
        if (e.message && e.message.includes("Unable to establish connection with QZ Tray")) {
            let isHttps = window.location.protocol === 'https:';
            let secureInstruction = '';
            if (isHttps) {
                secureInstruction = `
                    <div style="text-align: left; margin-top: 15px; font-size: 13px; background: #fff8f8; border-left: 4px solid #e53e3e; padding: 10px; border-radius: 4px; color: #2d3748;">
                        <strong style="color: #c53030;">🔒 HTTPS Secure Warning Bypass:</strong><br/>
                        Your browser might be blocking the connection because of local security certificates. To fix this:
                        <ul style="padding-left: 15px; margin-top: 5px; margin-bottom: 5px; line-height: 1.4;">
                            <li>Click <a href="https://localhost:8181" target="_blank" style="color: #3182ce; text-decoration: underline; font-weight: bold;">Link 1 (localhost)</a> or <a href="https://localhost.qz.io:8181" target="_blank" style="color: #3182ce; text-decoration: underline; font-weight: bold;">Link 2 (qz.io)</a>.</li>
                            <li>If you see a "Your connection is not private" warning, click <strong>"Advanced"</strong> and then click <strong>"Proceed to localhost"</strong>.</li>
                            <li>After bypassing, return to this tab and try printing again!</li>
                        </ul>
                    </div>
                `;
            }

            Swal.fire({
                icon: 'error',
                title: 'QZ Tray is not connected',
                html: `
                    <div style="text-align: left; font-size: 14px; line-height: 1.5; color: #2d3748;">
                        <p>We could not establish a connection to QZ Tray. Please ensure that:</p>
                        <ol style="padding-left: 20px; margin-bottom: 10px;">
                            <li>The <strong>QZ Tray Desktop App</strong> is installed and running on your computer (look for the green printer icon in your Windows system tray near the clock).</li>
                            <li>If it is not installed, download it from <a href="https://qz.io/download/" target="_blank" style="color: #3182ce; text-decoration: underline; font-weight: bold;">qz.io/download</a> and install it.</li>
                        </ol>
                        ${secureInstruction}
                    </div>
                `,
                confirmButtonText: 'I have started QZ Tray - Retry Printing',
                footer: '<a href="https://qz.io/wiki/faq" target="_blank" style="color: #3182ce; text-decoration: underline;">Need more help troubleshooting?</a>'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Printing Failed',
                text: e.message || "Printer is not accepting the job. Please check if it's online and ready.",
                footer: 'Check QZ Tray status and printer queue.'
            });
        }
        throw e; // Rethrow to let calling buttons handle the final loading state
    }
    // Removed disconnect() to prevent socket closing before job handover
}

function printSowingQRCodes(variable1, variable2, variable3, variable4, qrCodeData, startFrom) {
    if (!qrCodeData || !qrCodeData.length) {
        Swal.fire('Info', 'No QR codes to print.', 'info');
        return;
    }

    const rollWidthMM   = 77;    
    const labelWidthMM  = 37;    
    const labelHeightMM = 12;    
    const colGapMM      = 1.5;   
    const rowGapMM      = 1;     
    const paddingMM     = 0.5;   
    const qrSizePX      = 32;    

    const columns       = 2;

    const blankCount    = Math.max(0, (startFrom || 1) - 1);
    const totalSlots    = blankCount + qrCodeData.length;
    const numRows       = Math.ceil(totalSlots / columns);
    const rollHeightMM  = paddingMM + (numRows * labelHeightMM) + ((numRows - 1) * rowGapMM) + 1;

    const rows = qrCodeData.map((item, i) => ({
        barcode: item.barcode || item.qrcode || item,
        v1: variable1,
        v2: variable2,
        v3: variable3,
        v4: variable4,
        id: 'qr_sw_' + i
    }));

    const encodedRows = JSON.stringify(rows)
        .replace(/\\/g, '\\\\')
        .replace(/`/g, '\\`');

    let blanks = '';
    for (let b = 0; b < blankCount; b++) {
        const mr = ((b + 1) % columns === 0) ? '0mm' : colGapMM + 'mm';
        blanks += `<div class="label" style="border:none; visibility:hidden; margin-right:${mr};"></div>`;
    }

    let labelHtml = '';
    rows.forEach((row, i) => {
        const positionalIndex = blankCount + i;
        const mr = ((positionalIndex + 1) % columns === 0) ? '0mm' : colGapMM + 'mm';
        labelHtml += `
        <div class="label" style="margin-right:${mr}">
            <div class="qr-col"><div id="${row.id}"></div></div>
            <div class="text-col">
                <div class="t1">${row.v1}</div>
                <div>${row.v2}</div>
                <div>${row.v3}</div>
                <div>${row.v4}</div>
            </div>
        </div>`;
    });

    const printWindow = window.open('', '_blank', 'width=900,height=700');

    printWindow.document.write(`<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Sowing QR – ${variable1}</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
    @page {
        size: ${rollWidthMM}mm ${rollHeightMM.toFixed(2)}mm;
        margin: 0;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
        width: ${rollWidthMM}mm;
        height: ${rollHeightMM.toFixed(2)}mm;
        background: #fff;
        font-family: Arial, sans-serif;
        overflow: hidden;
    }

    .sheet {
        width: ${rollWidthMM}mm;
        display: flex;
        flex-wrap: wrap;
        padding: ${paddingMM}mm;
    }

    .label {
        width: ${labelWidthMM}mm;
        height: ${labelHeightMM}mm;
        border: 0.3mm solid #000;
        display: flex;
        align-items: center;
        overflow: hidden;
        padding: 0.5mm;
        margin-bottom: ${rowGapMM}mm;
    }

    .qr-col {
        width: 10mm;
        min-width: 10mm;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .qr-col canvas,
    .qr-col img { display: block; }

    .text-col {
        flex: 1;
        font-size: 5.5px;
        line-height: 1.2;
        padding-left: 0.8mm;
        overflow: hidden;
    }

    .t1 {
        font-weight: bold;
        font-size: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .text-col div {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
</head>
<body>
<div class="sheet">
${blanks}
${labelHtml}
</div>

<script>
(function() {
    var rows = ${encodedRows};
    rows.forEach(function(row) {
        var el = document.getElementById(row.id);
        if (!el) return;
        new QRCode(el, {
            text: row.barcode,
            width: ${qrSizePX},
            height: ${qrSizePX},
            correctLevel: QRCode.CorrectLevel.M
        });
    });
    setTimeout(function() {
        window.print();
        window.close();
    }, 1000);
})();
</script>
</body>
</html>`);

    printWindow.document.close();
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
    // // debugger;;
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
    // // debugger;;
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

// ConfirmSowingQrcodeBtn (Remove Damage QR)
$(document).on("click", ".ConfirmSowingQrcodeBtn", function () {
    let currentRoleId = 0;
    let rawRoleOps = $('body').data('loginroleid') ?? $('body').attr('data-loginroleid') ?? $('#hidden_loginroleid').val() ?? (typeof loginroleid !== 'undefined' ? loginroleid : undefined) ?? (typeof window.loginroleid !== 'undefined' ? window.loginroleid : undefined);
    if (rawRoleOps !== undefined && rawRoleOps !== null && rawRoleOps !== '') {
        let parsed = parseInt(rawRoleOps, 10);
        if (!isNaN(parsed)) currentRoleId = parsed;
    }

    if (currentRoleId >= 3) {
        Swal.fire({
            icon: "error",
            title: "Access Denied",
            text: "Only authorized managers (Role < 3) can remove damaged QR codes."
        });
        return;
    }

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
    // debugger;;
    let card = $(this).closest(".seedLotCard");

    let printlot = card.find(".LotNumberofSeed").val() ? card.find(".LotNumberofSeed").val().trim() : '';
    let printqty = card.find(".QRCodeQtyofLotno").val() ? card.find(".QRCodeQtyofLotno").val().trim() : '';

    if (!printlot || !printqty) {
        alertmessage("warning", "Please enter Lot Number & QR Qty");
        return;
    }

    let trayQtyNum = parseInt(printqty) || 0;
    if (trayQtyNum <= 0) {
        alertmessage("warning", "Please enter a valid Tray QR Quantity greater than 0");
        return;
    }

    $("#printbarcodefrom").data("barcodestring", '');
    let projectid = $("body").data("projectid");
    let trayid = $("#sowingtraytype").val();
    let SeedId = $("#SeedNameForSowing").val();
    let roottype = $("#SeedNameForSowing").data("roottype") || '';
    // alert(roottype);
    let sowingquantity = $("#SowingQuantity").val();
    let failurebufferpecent = $("#GerminationFailure").val();
    let SeedName = $("#SeedNameForSowing option:selected").data("seedname") || $("#SeedNameForSowing option:selected").text().trim();
    let SeedVarietyName = $("#SeedNameForSowing option:selected").data("varietyname");
    let GerminationFailure = $("#GerminationFailure").val();
    let TraysRequired = $("#TraysRequired").val();
    let LotNumberofSeed = printlot;

    let cavitycount = $("#sowingtraytype option:selected").data("cavitycount");
    let StockOrderNo = $("#otherorderdetails").data("dataStockOrderNo");
    let OrderQty = $("#otherorderdetails").data("dataOrderQty");
    let ProductName = $("#otherorderdetails").data("dataProductName") || $(".productNamemd").first().text().trim();
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

    // Confirmation Swal before saving
    Swal.fire({
        title: "Confirm Sowing Details",
        html: `
            <div class="text-start p-2" style="font-size: 0.95rem;">
                <div class="mb-3 p-2 rounded bg-light border">
                    <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Product Name</div>
                    <div class="fw-bold text-dark fs-6 text-truncate">${ProductName || 'N/A'}</div>
                    ${SeedName ? `<div class="text-muted small mt-1"><i class="fa fa-seedling text-success me-1"></i>${SeedName}</div>` : ''}
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <div class="p-2 rounded bg-light border text-center h-100">
                            <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Seed Lot No</div>
                            <div class="fw-bold text-primary fs-5 mt-1">${printlot}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-light border text-center h-100">
                            <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Tray Quantity</div>
                            <div class="fw-bold text-success fs-5 mt-1">${printqty} <small style="font-size: 0.8rem;">Trays</small></div>
                        </div>
                    </div>
                </div>
                <div class="text-center text-muted small mt-2">
                    <i class="fa fa-info-circle me-1 text-primary"></i>Every tray will be assigned a unique QR code.
                </div>
            </div>
        `,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-check me-1"></i> Confirm & Save',
        cancelButtonText: '<i class="fa fa-times me-1"></i> Cancel',
        confirmButtonColor: "#1976d2",
        cancelButtonColor: "#6c757d",
        reverseButtons: true,
        focusConfirm: true,
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Generating QR Codes...',
                text: 'Please wait while unique tray records are verified and saved.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            SaveAndUpdateSowingDataForQRCodeGenrate(StockOrderNo, ProductId, OrderQty, failurebufferpecent, sowingquantity, trayid, SeedId, printlot, printqty, roottype, function (rrss) {
                Swal.close();
                if (rrss && rrss.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Saved Successfully",
                        html: `<b>${rrss.message || "Tray QR codes generated successfully!"}</b><br><small class="text-muted">Batch: ${rrss.sowingbatchno || ''} | Trays: ${printqty}</small>`,
                        confirmButtonColor: "#1976d2"
                    });
                    generateSeedLotCardsByStockOrder(StockOrderNo, roottype);
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Save Failed",
                        html: `<b>${(rrss && rrss.message) ? rrss.message : "Error while saving tray details."}</b><br><small class="text-danger">All changes have been rolled back.</small>`,
                        confirmButtonColor: "#dc3545"
                    });
                }
            });
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
    // debugger;;
    let currentLoginRoleId = parseInt($("body").data("loginroleid") || $("body").attr("data-loginroleid") || $("#hidden_loginroleid").val() || (typeof loginroleid !== 'undefined' ? loginroleid : 0)) || 0;
    // if (currentLoginRoleId >= 3) {
    //     return;
    // }
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

function getSowingRowBgStyle(sowingDate, pendingQty, totalSowingQty = 0) {
    const total = parseInt(totalSowingQty) || 0;
    const pending = parseInt(pendingQty) || 0;

    if (total > 0 && pending === 0) {
        // Sowing completed -> light green, dark black text
        return "background-color: #dcfce7; color: #000000;";
    } else if (total > 0 && pending > 0) {
        // Incomplete / partial sowing -> light amber, dark black text
        return "background-color: #ffedd5 !important; color: #000000 !important;";
    } else {
        // Pending sowing -> white, dark black text
        return "background-color: #ffffff; color: #000000;";
    }
}

function loadSowingScheduleStarting(selectedWeek = "current") {
    // debugger;;
    
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

        // 4. Populate weekSelector based on month filter
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

        // 5. Select Week// 5. Select Week
        let fetchStart = "";
        let fetchEnd   = "";
        if (selectedWeek === "current") {
            selectedWeek = currentWeekRange;
        } /*else if (selectedWeek === "first" && displayWeeks.length > 0) {
            selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
        }*/ else if (selectedWeek === "first") {

            // ALL MONTHS => full range
            if (selectedMonth === "all" && sortedWeeks.length > 0) {

                let firstWs = sortedWeeks[0];
                let lastWs  = sortedWeeks[sortedWeeks.length - 1];

                fetchStart = firstWs;
                fetchEnd   = weekMap[lastWs];

                selectedWeek = `${fetchStart} to ${fetchEnd}`;

                // Add full-range option ONLY for All Months
                $week.prepend(`
                    <option value="${selectedWeek}">
                        All Weeks (${selectedWeek})
                    </option>
                `);

            }
         }   else if (selectedWeek === "last" && displayWeeks.length > 0) {
            let lastWs = displayWeeks[displayWeeks.length - 1];
            selectedWeek = `${lastWs} to ${weekMap[lastWs]}`;
        }
 
      /*  if ($week.find(`option[value="${selectedWeek}"]`).length === 0) {
            if (displayWeeks.length > 0) {
                selectedWeek = `${displayWeeks[0]} to ${weekMap[displayWeeks[0]]}`;
            } else {
                if ($week.find(`option[value="${currentWeekRange}"]`).length === 0) {
                    $week.append(`<option value="${currentWeekRange}">${currentWeekRange}</option>`);
                }
                selectedWeek = currentWeekRange;
            }
        }*/

        if ($week.find(`option[value="${selectedWeek}"]`).length === 0) {

            if (displayWeeks.length > 0) {

                let firstWs = displayWeeks[0];

                selectedWeek = `${firstWs} to ${weekMap[firstWs]}`;

            } else {

                $week.append(`
                    <option value="${currentWeekRange}">
                        ${currentWeekRange}
                    </option>
                `);

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

// ==========================================
// 📊 SOWING SCHEDULE DASHBOARD & PAGINATION
// ==========================================
let currentRawSowingData = [];
let currentFilteredSowingData = [];
let sowingCurrentPage = 1;
let sowingPageSize = 25;

function fillFilterDropdowns(data) {
    const locations = new Map();
    const products  = new Map();

    if (Array.isArray(data)) {
        data.forEach(item => {
            // Distinct Locations
            if (item.sowinglocationid && item.sowinglocationname) {
                locations.set(
                    String(item.sowinglocationid),
                    item.sowinglocationname
                );
            }

            // Distinct Products
            if (item.productid && item.product_name) {
                products.set(
                    String(item.productid),
                    item.product_name
                );
            }
        });
    }

    // -------------------------
    // Location Dropdown
    // -------------------------
    const $location = $('#fltlocation');
    const prevLoc = $location.val();
    $location.empty();
    $location.append('<option value="">All Locations</option>');

    locations.forEach((name, id) => {
        $location.append(
            $('<option>', {
                value: id,
                text: name
            })
        );
    });
    if (prevLoc && locations.has(String(prevLoc))) {
        $location.val(prevLoc);
    }

    // -------------------------
    // Product Dropdown
    // -------------------------
    const $product = $('#fltproduct');
    const prevProd = $product.val();
    $product.empty();
    $product.append('<option value="">All Products</option>');

    products.forEach((name, id) => {
        $product.append(
            $('<option>', {
                value: id,
                text: name
            })
        );
    });
    if (prevProd && products.has(String(prevProd))) {
        $product.val(prevProd);
    }
}

function toProperCase(str) {
    return String(str ?? '')
        .toLowerCase()
        .replace(/\b\w/g, char => char.toUpperCase());
}

function isItemGrafted(item) {
    const rootSeedId = String(item.productRootSeedid || '').trim();
    const scionSeedId = String(item.productScionSeedid || '').trim();
    const prdName = String(item.product_name || '').toLowerCase();
    const isg = String(item.isgrafted || '').trim().toLowerCase();

    if (isg === 'yes' || isg === 'grafted') {
        return true;
    }
    if (rootSeedId && scionSeedId && rootSeedId !== scionSeedId) {
        return true;
    }
    if (prdName.indexOf('nongrafted') === -1 && prdName.indexOf('non-grafted') === -1 && (prdName.startsWith('grafted') || prdName.indexOf('grafted') > -1)) {
        return true;
    }
    return false;
}

function isItemClientOrder(item) {
    const rawClientName = String(item.clientname || '').trim();
    return (rawClientName !== '' && rawClientName.toLowerCase() !== 'karjee farms') || 
           (item.psono && String(item.psono).toLowerCase().indexOf('kf-ps') !== -1);
}

function buildSowingRowHtml(item, displayIndex) {
    const sowingTrayCount = parseInt(item.sowingtraycount) || 0;
    const totalSowingQty   = parseInt(item.totalsowingqty) || parseInt(item.targetsowingqty) || 0;
    const sowingDoneQty    = parseInt(item.sowingdoneqty) || 0;
    const pendingSowingQty = parseInt(item.pendingsowingqty) || 0;

    const scheduleStatus = String(item.schedule_status || '').toLowerCase().trim();

    // 2. Completed Sowing
    const isSowingCompleted = (totalSowingQty > 0 && pendingSowingQty === 0) ||
                             (sowingDoneQty >= totalSowingQty && totalSowingQty > 0) ||
                             (scheduleStatus === 'done' && pendingSowingQty === 0);

    // 1. Incomplete / Partial Sowing (Sowing scheduled & pending quantity remains)
    const isIncomplete = !isSowingCompleted && (
        (totalSowingQty > 0 && pendingSowingQty > 0) ||
        (scheduleStatus === 'inprogress') ||
        (sowingDoneQty > 0) ||
        (sowingTrayCount > 0)
    );

    const gerBtnStyle = isSowingCompleted
        ? "background: #bdbdbd; color: #666; border-color: #bdbdbd; cursor: not-allowed;"
        : "background: linear-gradient(120deg, #a1c4fd, #c2e9fb);";

    let gerBtnDisabled = sowingTrayCount > 0 ? "" : "disabled";
    const germinationboxinfo = Array.isArray(item.germinatinboxdetails) ? item.germinatinboxdetails : [];
    const lotdetails = Array.isArray(item.lotdetails) ? item.lotdetails : [];
    const pendingswtrayforbox = Array.isArray(item.pendingforgerminationbox) ? item.pendingforgerminationbox : [];
    const hasUndoMakeBox =
        pendingswtrayforbox.length > 0 &&
        pendingswtrayforbox.every(x =>
            String(x.status || '').trim().toLowerCase() === 'tray received at germination' &&
            String(x.workingstatus || '').trim().toLowerCase() === 'withoutgermination'
        );
    let undoMakeBoxBtn = "";
    if (hasUndoMakeBox) {
        undoMakeBoxBtn = `
            <button type="button"
                    class="btn btn-sm btn-warning undomakeboxbtn mt-1"
                    style="background: linear-gradient(120deg, #f6d365, #fda085);">
                Undo Make a Box
            </button>
        `;
    }
    let germinationbtn = "";
    let sowingtrybtn = "";
    let flagA = germinationboxinfo.some(x =>
        String(x.productid) === String(item.productid) &&
        String(x.stockorderno) === String(item.stockorder_no)
    ); 
    let flagB = pendingswtrayforbox.some(x =>
        String(x.productid) === String(item.productid) &&
        String(x.germinationwithtray) === "yes"
    ); 
    let gerStyle = "";
    if (gerBtnDisabled === "disabled") {
        gerStyle = "background-color: rgba(148, 163, 184, 0.2); color: #64748b; border: 1px solid rgba(148, 163, 184, 0.35); cursor: not-allowed;";
    } else if (isSowingCompleted) {
        gerStyle = "background-color: #166534; color: #ffffff; border: 1px solid #14532d; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.12);";
    } else if (isIncomplete) {
        gerStyle = "background-color: #9a3412; color: #ffffff; border: 1px solid #7c2d12; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.12);";
    } else {
        gerStyle = "background-color: #6b21a8; color: #ffffff; border: 1px solid #581c87; font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.12);";
    }
    if (flagB) {
        const finalGerBtnDisabled = (gerBtnDisabled === "disabled") ? "disabled" : "";
        germinationbtn = `<button style="${gerStyle} font-size: 0.76rem; border-radius: 6px;" class="btn btn-sm startgerbtntd_withoutbox" ${finalGerBtnDisabled}>Germinate Without Box<br/><small style="font-size: 0.68rem;">For Germination</small></button>`;
    } else if (flagA) {
        germinationbtn = `<button style="${gerStyle} font-size: 0.76rem; border-radius: 6px;" class="btn btn-sm startgerbtntd" ${gerBtnDisabled}>Make a Box<br/><small style="font-size: 0.68rem;">For Germination</small></button>`;
    } else {
        germinationbtn = `<button style="${gerStyle} font-size: 0.76rem; border-radius: 6px;" class="btn btn-sm btnnongrafted" ${gerBtnDisabled}>Ask <br/><small style="font-size: 0.68rem;">For Germination</small></button>`;
    }

    if (isSowingCompleted) {
        sowingtrybtn = `
            <button class="btn btn-sm printqrcodebtntd shadow-sm" style="background-color: #15803d; color: #ffffff; border: 1px solid #14532d; font-weight: 600; font-size: 0.76rem; border-radius: 6px;">
                Print QR Code<br/><small style="font-size: 0.68rem; color: #f0fdf4;">For Sowing</small>
            </button>`;
    } else if (isIncomplete) {
        sowingtrybtn = `
            <button class="btn btn-sm printqrcodebtntd shadow-sm" style="background-color: #c2410c; color: #ffffff; border: 1px solid #9a3412; font-weight: 600; font-size: 0.76rem; border-radius: 6px;">
                Print QR Code<br/><small style="font-size: 0.68rem; color: #fff7ed;">For Sowing</small>
            </button>`;
    } else {
        sowingtrybtn = `
            <button class="btn btn-sm printqrcodebtntd shadow-sm" style="background-color: #0369a1; color: #ffffff; border: 1px solid #075985; font-weight: 600; font-size: 0.76rem; border-radius: 6px;">
                Print QR Code<br/><small style="font-size: 0.68rem; color: #f0f9ff;">For Sowing</small>
            </button>`;
    }

    let currentLoginRoleId = parseInt($("body").data("loginroleid") || $("body").attr("data-loginroleid") || $("#hidden_loginroleid").val() || (typeof loginroleid !== 'undefined' ? loginroleid : 0)) || 0;

    let missingLocationDisplay = `
            <button class="btn btn-sm updswlocationtd shadow-sm" style="background-color: #dc2626; color: #ffffff; border: 1px solid #b91c1c; font-weight: 600; font-size: 0.76rem; border-radius: 6px;">
                Location Missing<br/><small style="font-size: 0.68rem; color: #fef2f2;">For Sowing</small>
            </button>`;

    let hasSowingLocation =
        item.sowinglocationid &&
        item.sowinglocationid != "0";

    let sowinglocation = "";

    let updLocBtnStyle = "background-color: #0369a1; color: #ffffff; border: 1px solid #075985; font-weight: 600; font-size: 0.72rem; padding: 2px 7px; border-radius: 5px;";
    if (isIncomplete) {
        updLocBtnStyle = "background-color: #c2410c; color: #ffffff; border: 1px solid #9a3412; font-weight: 600; font-size: 0.72rem; padding: 2px 7px; border-radius: 5px;";
    } else if (isSowingCompleted) {
        updLocBtnStyle = "background-color: #15803d; color: #ffffff; border: 1px solid #14532d; font-weight: 600; font-size: 0.72rem; padding: 2px 7px; border-radius: 5px;";
    }

    if (hasSowingLocation) {
        sowinglocation = `<div class="fw-bold" style="font-size: 0.85rem; color: #000000 !important;">${item.sowinglocationname}</div>`;
        if (sowingDoneQty === 0 && sowingTrayCount === 0) {
            sowinglocation += `
                <button type="button"
                        class="btn btn-sm updswlocationtd shadow-sm mt-1"
                        style="${updLocBtnStyle}">
                    Update Location
                </button>
            `;
        }
    } else {
        sowinglocation = missingLocationDisplay;
    }

    let rowClass = "sowing-row-pending";
    let style = "background-color: #ffffff !important; color: #000000 !important;";

    if (isSowingCompleted) {
        // Sowing completed (in germination room or production order) -> light green, dark black text
        rowClass = "sowing-row-completed";
        style = "background-color: #dcfce7 !important; color: #000000 !important;";
    } else if (isIncomplete) {
        // Incomplete / partial sowing -> light amber, dark black text
        rowClass = "sowing-row-incomplete";
        style = "background-color: #ffedd5 !important; color: #000000 !important;";
    } else {
        // Pending sowing -> white, dark black text
        rowClass = "sowing-row-pending";
        style = "background-color: #ffffff !important; color: #000000 !important;";
    }

    let isGrafted = isItemGrafted(item);
    let seedVarietyDisplay = item.seedname || '';
    if (isGrafted && item.roottype) {
        seedVarietyDisplay += ` (${item.roottype})`;
    }

    const isClientOrder = isItemClientOrder(item);

    return `
        <tr class="${rowClass}" style="${style}"
            data-productionstockorderno="${item.stockorder_no}" data-expecteddeliverydate="${item.expecteddeliverydate}"
            data-sowingschedulid="${item.sowingschedulid || ''}"
            data-productcategoryid="${item.productCategoryid}" data-productrootseedid="${item.productRootSeedid}"
            data-productscionseedid="${item.productScionSeedid}" data-roottrayid="${item.roottrayid}"
            data-sciontrayid="${item.sciontrayid}" data-productid="${item.productid}"
            data-productname="${item.product_name}" data-sowingdate="${item.sowingdate}"
            data-roottype="${item.roottype}" data-seedid="${item.seedid}"
            data-seedname="${item.seedname}" data-orderqty="${item.orderqty}"
            data-sowinglocationid="${item.sowinglocationid}" data-failurebufferpercent="${item.failurebufferpercent}"
            data-totalsowingqty="${item.totalsowingqty}" data-sowingtraycount="${sowingTrayCount}"
            data-lotdetails="${encodeURIComponent(JSON.stringify(lotdetails))}" data-gboxdata="${encodeURIComponent(JSON.stringify(germinationboxinfo))}"
            data-createdbyname="${item.createdbyname || ''}"
            >
            <td style="${style}">${displayIndex}</td>
            <td style="${style}">${sowinglocation}</td>
            <td style="${style}">
                ${item.stockorder_no}${item.psono ? `
                    <span class="badge badge-pso ms-1" style="background-color: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; font-size: 0.75rem; padding: 3px 6px; font-weight: 600;">
                        (${item.psono})
                    </span>
                ` : ''}

                ${(currentLoginRoleId < 2 && sowingDoneQty === 0 && sowingTrayCount === 0) ? `
                    <button type="button" 
                            class="btn btn-sm py-0 px-1 ms-1 delete-stockorder-btn"
                            data-stockorderno="${item.stockorder_no}"
                            data-productname="${(item.product_name || '').replace(/"/g, '&quot;')}"
                            data-seedvariety="${(seedVarietyDisplay || '').replace(/"/g, '&quot;')}"
                            data-orderqty="${item.orderqty || 0}"
                            data-clientname="${(item.clientname || '').replace(/"/g, '&quot;')}"
                            data-clientid="${item.clientid || ''}"
                            data-psono="${item.psono || ''}"
                            data-sowingdate="${item.sowingdate || ''}"
                            data-expecteddeliverydate="${item.expecteddeliverydate || ''}"
                            data-isgrafted="${isGrafted ? '1' : '0'}"
                            data-isclientorder="${isClientOrder ? '1' : '0'}"
                            title="${isClientOrder ? 'Cancel Client Order' : 'Remove Stock Order'}"
                            style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; font-size: 0.72rem; padding: 2px 6px !important; border-radius: 4px; vertical-align: middle; line-height: 1.2;">
                        <i class="${isClientOrder ? 'fa fa-ban' : 'fa fa-trash'}"></i>
                    </button>
                ` : ''}

                ${item.clientname ? `
                    <br>
                    <span class="small fw-semibold">Client:</span>
                    <span class="badge badge-client mt-1" style="background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; font-size: 0.75rem; padding: 3px 7px; font-weight: 600;">
                        ${toProperCase(item.clientname)}
                    </span>
                ` : ''}

                ${item.createdbyname ? `
                    <br>
                    <span class="small fw-semibold">Created by:</span>
                    <span class="badge badge-createdby mt-1" style="background-color: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4; font-size: 0.75rem; padding: 3px 7px; font-weight: 600;">
                         ${toProperCase(item.createdbyname)}
                    </span>
                ` : ''}
            </td>
            <td style="${style}">${changeDateFormatYmd_dMY(item.sowingdate)}
                ${item.expecteddeliverydate ? `
                    <br>
                    <span class="small fw-semibold">Delivery Date:</span>
                    <span class="badge badge-delivery mt-1" style="background-color: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; font-size: 0.75rem; padding: 3px 7px; font-weight: 600;">
                         ${changeDateFormatYmd_dMY(item.expecteddeliverydate)}
                    </span>
                ` : ''}
            </td>
            <td style="${style}">${seedVarietyDisplay}</td>
            <td style="${style}">
                ${item.product_name}
                ${(currentLoginRoleId < 2 && sowingDoneQty === 0 && sowingTrayCount === 0) ? `
                    <button type="button" 
                            class="btn btn-sm py-0 px-1 ms-1 edit-product-btn"
                            data-stockorderno="${item.stockorder_no}"
                            data-productid="${item.productid}"
                            data-productname="${(item.product_name || '').replace(/"/g, '&quot;')}"
                            data-seedname="${(seedVarietyDisplay || '').replace(/"/g, '&quot;')}"
                            data-sowingdate="${item.sowingdate || ''}"
                            data-expecteddeliverydate="${item.expected_delivery_date || item.expecteddeliverydate || ''}"
                            data-orderqty="${item.orderqty || 0}"
                            data-clientname="${String(item.clientname ?? '').replace(/"/g, '&quot;')}"
                            data-clientid="${item.clientid || ''}"
                            data-psono="${item.psono || ''}"
                            data-isgrafted="${isGrafted ? '1' : '0'}"
                            title="Change Product / Seed Variety"
                            style="background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-size: 0.72rem; padding: 2px 6px !important; border-radius: 4px; vertical-align: middle; line-height: 1.2;">
                        <i class="fa fa-edit"></i>
                    </button>
                ` : ''}
            </td>
            <td style="${style} vertical-align: middle;">
                <div class="d-flex flex-column gap-1" style="min-width: 130px;">
                    <span class="badge text-start d-flex justify-content-between align-items-center shadow-xs" style="background-color: #dbeafe; color: #1e3a8a; border: 1px solid #bfdbfe; font-size: 0.75rem; padding: 3px 6px;">
                        <span style="font-weight: 600; color: #1e40af;">Ord. Qty:</span>
                        <span style="font-weight: 700; color: #000000; margin-left: 6px;">${Number(item.orderqty || 0).toLocaleString('en-IN')}</span>
                    </span>
                    <span class="badge text-start d-flex justify-content-between align-items-center shadow-xs" style="background-color: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; font-size: 0.75rem; padding: 3px 6px;">
                        <span style="font-weight: 600; color: #6d28d9;">SW. Qty:</span>
                        <span style="font-weight: 700; color: #000000; margin-left: 6px;">${Number(item.totalsowingqty || 0).toLocaleString('en-IN')}</span>
                    </span>
                    <span class="badge text-start d-flex justify-content-between align-items-center shadow-xs" style="background-color: ${pendingSowingQty > 0 ? '#ffedd5' : '#dcfce7'}; color: ${pendingSowingQty > 0 ? '#9a3412' : '#14532d'}; border: 1px solid ${pendingSowingQty > 0 ? '#fed7aa' : '#bbf7d0'}; font-size: 0.75rem; padding: 3px 6px;">
                        <span style="font-weight: 600; color: ${pendingSowingQty > 0 ? '#c2410c' : '#15803d'};">SW. Balance:</span>
                        <span style="font-weight: 700; color: #000000; margin-left: 6px;">${Number(item.pendingsowingqty || 0).toLocaleString('en-IN')}</span>
                    </span>
                </div>
            </td>
            <td style="${style}">${sowingtrybtn}</td>
            <td style="${style}">${germinationbtn} ${undoMakeBoxBtn}</td>                            
        </tr>`;
}

function updateSowingSummaryCards(filteredList) {
    let totalOrders = 0;
    let totalOrderQty = 0;
    let totalSowedQty = 0;
    let totalPendingQty = 0;
    let clientOrders = 0;
    let graftedBatches = 0;

    if (Array.isArray(filteredList)) {
        totalOrders = filteredList.length;
        filteredList.forEach(item => {
            const oQty = parseInt(item.orderqty) || 0;
            const sQty = parseInt(item.sowingdoneqty) || 0;
            const pQty = parseInt(item.pendingsowingqty) || 0;

            totalOrderQty += oQty;
            totalSowedQty += sQty;
            totalPendingQty += pQty;

            if (isItemClientOrder(item)) {
                clientOrders++;
            }
            if (isItemGrafted(item)) {
                graftedBatches++;
            }
        });
    }

    $("#stat_total_orders").text(totalOrders.toLocaleString('en-IN'));
    $("#stat_order_qty").text(totalOrderQty.toLocaleString('en-IN'));
    $("#stat_sowed_qty").text(totalSowedQty.toLocaleString('en-IN'));
    $("#stat_pending_qty").text(totalPendingQty.toLocaleString('en-IN'));
    $("#stat_client_orders").text(clientOrders.toLocaleString('en-IN'));
    $("#stat_grafted_orders").text(graftedBatches.toLocaleString('en-IN'));
}

function renderSowingPage() {
    const totalRecords = currentFilteredSowingData.length;
    const pageSize = sowingPageSize;
    const totalPages = Math.max(1, Math.ceil(totalRecords / pageSize));

    if (sowingCurrentPage > totalPages) sowingCurrentPage = totalPages;
    if (sowingCurrentPage < 1) sowingCurrentPage = 1;

    const startIdx = (sowingCurrentPage - 1) * pageSize;
    const endIdx = Math.min(startIdx + pageSize, totalRecords);
    const pageRecords = currentFilteredSowingData.slice(startIdx, endIdx);

    if (pageRecords.length === 0) {
        $("#scheduleTable").html(`
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                    <i class="fa fa-info-circle me-1"></i> No matching sowing schedule records found
                </td>
            </tr>
        `);
    } else {
        let html = "";
        pageRecords.forEach((item, idx) => {
            html += buildSowingRowHtml(item, startIdx + idx + 1);
        });
        $("#scheduleTable").html(html);
    }

    // Update Pagination Info Text
    if (totalRecords === 0) {
        $("#paginationInfo").text("Showing 0 to 0 of 0 entries");
    } else {
        $("#paginationInfo").text(`Showing ${startIdx + 1} to ${endIdx} of ${totalRecords.toLocaleString('en-IN')} entries`);
    }

    // Render Pagination Controls
    let controlsHtml = "";

    // Previous Button (Single-line non-breaking)
    const prevDisabled = sowingCurrentPage <= 1 ? "disabled" : "";
    controlsHtml += `
        <li class="page-item ${prevDisabled}">
            <a class="page-link sowing-page-btn" href="javascript:void(0);" data-page="${sowingCurrentPage - 1}" aria-label="Previous">&laquo;&nbsp;Prev</a>
        </li>
    `;

    // Dynamic Page Numbers with intelligent windowing
    const maxButtons = 7;
    if (totalPages <= maxButtons) {
        for (let p = 1; p <= totalPages; p++) {
            const active = p === sowingCurrentPage ? "active" : "";
            controlsHtml += `
                <li class="page-item ${active}">
                    <a class="page-link sowing-page-btn" href="javascript:void(0);" data-page="${p}">${p}</a>
                </li>
            `;
        }
    } else {
        // Page 1 always
        controlsHtml += `
            <li class="page-item ${sowingCurrentPage === 1 ? 'active' : ''}">
                <a class="page-link sowing-page-btn" href="javascript:void(0);" data-page="1">1</a>
            </li>
        `;

        let startP = Math.max(2, sowingCurrentPage - 1);
        let endP = Math.min(totalPages - 1, sowingCurrentPage + 1);

        if (startP > 2) {
            controlsHtml += `<li class="page-item disabled"><span class="page-link sowing-page-btn">...</span></li>`;
        }

        for (let p = startP; p <= endP; p++) {
            const active = p === sowingCurrentPage ? "active" : "";
            controlsHtml += `
                <li class="page-item ${active}">
                    <a class="page-link sowing-page-btn" href="javascript:void(0);" data-page="${p}">${p}</a>
                </li>
            `;
        }

        if (endP < totalPages - 1) {
            controlsHtml += `<li class="page-item disabled"><span class="page-link sowing-page-btn">...</span></li>`;
        }

        // Last Page always
        controlsHtml += `
            <li class="page-item ${sowingCurrentPage === totalPages ? 'active' : ''}">
                <a class="page-link sowing-page-btn" href="javascript:void(0);" data-page="${totalPages}">${totalPages}</a>
            </li>
        `;
    }

    // Next Button (Single-line non-breaking)
    const nextDisabled = sowingCurrentPage >= totalPages ? "disabled" : "";
    controlsHtml += `
        <li class="page-item ${nextDisabled}">
            <a class="page-link sowing-page-btn" href="javascript:void(0);" data-page="${sowingCurrentPage + 1}" aria-label="Next">Next&nbsp;&raquo;</a>
        </li>
    `;

    $("#paginationControls").html(controlsHtml);
}

function applySowingFiltersAndRender(resetPage = false) {
    if (resetPage) {
        sowingCurrentPage = 1;
    }

    const locFilter       = String($("#fltlocation").val() || "").trim();
    const prodFilter      = String($("#fltproduct").val() || "").trim();
    const orderTypeFilter = String($("#fltordertype").val() || "all").trim();
    const prodTypeFilter  = String($("#fltproducttype").val() || "all").trim();
    const statusFilter    = String($("#fltsowingstatus").val() || "all").trim();
    const searchText      = String($("#oSearchInput").val() || "").toLowerCase().trim();

    const pageSizeVal     = String($("#fltpagesize").val() || "25");
    sowingPageSize        = pageSizeVal === "all" ? 999999 : (parseInt(pageSizeVal, 10) || 25);

    // Toggle clear search button
    if (searchText !== "") {
        $("#btnClearSearch").show();
    } else {
        $("#btnClearSearch").hide();
    }

    if (!Array.isArray(currentRawSowingData)) {
        currentRawSowingData = [];
    }

    // Filter currentRawSowingData
    currentFilteredSowingData = currentRawSowingData.filter(item => {
        const rowLocId = String(item.sowinglocationid || "");
        const rowProdId = String(item.productid || "");

        // 1. Location match
        if (locFilter !== "" && rowLocId !== locFilter) {
            return false;
        }

        // 2. Product match
        if (prodFilter !== "" && rowProdId !== prodFilter) {
            return false;
        }

        // 3. Order Type match
        const isClient = isItemClientOrder(item);
        if (orderTypeFilter === "client" && !isClient) {
            return false;
        }
        if (orderTypeFilter === "stock" && isClient) {
            return false;
        }

        // 4. Product Type match
        const isGrafted = isItemGrafted(item);
        if (prodTypeFilter === "grafted" && !isGrafted) {
            return false;
        }
        if (prodTypeFilter === "nongrafted" && isGrafted) {
            return false;
        }

        // 5. Sowing Status match
        const totalSowingQty   = parseInt(item.totalsowingqty) || parseInt(item.targetsowingqty) || 0;
        const sowingDoneQty    = parseInt(item.sowingdoneqty) || 0;
        const sowingTrayCount  = parseInt(item.sowingtraycount) || 0;
        const pendingSowingQty = parseInt(item.pendingsowingqty) || 0;
        const scheduleStatus   = String(item.schedule_status || '').toLowerCase().trim();

        const isCompleted  = (totalSowingQty > 0 && pendingSowingQty === 0) ||
                             (sowingDoneQty >= totalSowingQty && totalSowingQty > 0) ||
                             (scheduleStatus === 'done' && pendingSowingQty === 0);

        const isInProgress = !isCompleted && (
            (totalSowingQty > 0 && pendingSowingQty > 0) ||
            (scheduleStatus === 'inprogress') ||
            (sowingDoneQty > 0) ||
            (sowingTrayCount > 0)
        );

        const isPending    = (pendingSowingQty > 0) || (!isCompleted && !isInProgress);

        if (statusFilter === "completed" && !isCompleted) {
            return false;
        }
        if (statusFilter === "inprogress" && !isInProgress) {
            return false;
        }
        if (statusFilter === "pending" && !isPending) {
            return false;
        }

        // 6. Quick Search across all relevant text fields
        if (searchText !== "") {
            const searchableStr = [
                item.stockorder_no || "",
                item.psono || "",
                item.clientname || "",
                item.product_name || "",
                item.seedname || "",
                item.roottype || "",
                item.sowinglocationname || "",
                item.createdbyname || "",
                item.sowingdate || "",
                item.expecteddeliverydate || ""
            ].join(" ").toLowerCase();

            if (searchableStr.indexOf(searchText) === -1) {
                return false;
            }
        }

        return true;
    });

    // Update active filter badge
    const activeFilters = [];
    if (locFilter !== "") activeFilters.push("Location");
    if (prodFilter !== "") activeFilters.push("Product");
    if (orderTypeFilter !== "all") activeFilters.push("Order Type");
    if (prodTypeFilter !== "all") activeFilters.push("Product Type");
    if (statusFilter !== "all") activeFilters.push("Status");
    if (searchText !== "") activeFilters.push("Search");

    if (activeFilters.length > 0) {
        $("#lblActiveFilterCount")
            .text(`Filtered: ${currentFilteredSowingData.length} of ${currentRawSowingData.length} orders (${activeFilters.join(", ")})`)
            .addClass("text-primary fw-bold")
            .removeClass("text-secondary fw-normal");
        $("#tableHeaderSummary")
            .html(`<i class="fa fa-filter text-primary me-1"></i>Showing ${currentFilteredSowingData.length} of ${currentRawSowingData.length} Orders`);
    } else {
        $("#lblActiveFilterCount")
            .text(`All ${currentRawSowingData.length} Orders Shown`)
            .addClass("text-secondary fw-normal")
            .removeClass("text-primary fw-bold");
        $("#tableHeaderSummary")
            .html(`<i class="fa fa-table text-primary me-1"></i>All ${currentRawSowingData.length} Orders Shown`);
    }

    $(".status-indicator-badge").css("outline", "none");
    if (statusFilter !== "all") {
        $(`.status-indicator-badge[data-filterstatus="${statusFilter}"]`).css("outline", "2px solid #2563eb");
    }

    // Update summary cards strictly with filtered data ONLY
    updateSowingSummaryCards(currentFilteredSowingData);

    // Update active summary card styling (differing background + animated border)
    updateActiveSummaryCardState();

    // Render paginated view
    renderSowingPage();
}

let currentActiveCardFilter = "all";

function updateActiveSummaryCardState() {
    const orderTypeFilter = String($("#fltordertype").val() || "all").trim();
    const prodTypeFilter  = String($("#fltproducttype").val() || "all").trim();
    const statusFilter    = String($("#fltsowingstatus").val() || "all").trim();

    // If dropdown filter was changed directly, map it to the right card
    if (currentActiveCardFilter !== "orderqty") {
        if (statusFilter === "completed") {
            currentActiveCardFilter = "completed";
        } else if (statusFilter === "pending") {
            currentActiveCardFilter = "pending";
        } else if (orderTypeFilter === "client") {
            currentActiveCardFilter = "client";
        } else if (prodTypeFilter === "grafted") {
            currentActiveCardFilter = "grafted";
        } else if (statusFilter === "all" && orderTypeFilter === "all" && prodTypeFilter === "all") {
            currentActiveCardFilter = "all";
        }
    }

    $(".sowing-stat-card").removeClass("active-card");
    const $target = $(`.sowing-stat-card[data-cardfilter="${currentActiveCardFilter}"]`);
    if ($target.length) {
        $target.first().addClass("active-card");
    } else {
        $(`.sowing-stat-card[data-cardfilter="all"]`).first().addClass("active-card");
    }

    // Update extra filter badge count
    let extraFilterCount = 0;
    if (orderTypeFilter !== "all") extraFilterCount++;
    if (prodTypeFilter !== "all") extraFilterCount++;
    if (statusFilter !== "all") extraFilterCount++;

    if (extraFilterCount > 0) {
        $("#extraFilterBadge").text(extraFilterCount).show();
    } else {
        $("#extraFilterBadge").hide();
    }
}

function renderSowingTable(res) {
    currentRawSowingData = Array.isArray(res) ? res : [];

    // Sort by Sowing Date ASC
    currentRawSowingData.sort((a, b) => new Date(a.sowingdate) - new Date(b.sowingdate));

    // Populate filter dropdowns
    fillFilterDropdowns(currentRawSowingData);

    // Apply active filters, update summary cards, and render page 1
    applySowingFiltersAndRender(true);
}

// ------------------------------------------
// Sowing Filter & Search Event Listeners
// ------------------------------------------
$(document).on("change", "#fltlocation, #fltproduct, #fltordertype, #fltproducttype, #fltsowingstatus, #fltpagesize", function () {
    // When dropdowns change, reset custom card filter flag
    if (currentActiveCardFilter === "orderqty") {
        currentActiveCardFilter = "all";
    }
    applySowingFiltersAndRender(true);
});

$(document).on("input", "#oSearchInput", function () {
    applySowingFiltersAndRender(true);
});

$(document).on("click", "#btnClearSearch", function () {
    $("#oSearchInput").val("");
    applySowingFiltersAndRender(true);
});

$(document).on("click", ".status-indicator-badge", function () {
    const status = $(this).data("filterstatus");
    if (!status) return;
    const current = $("#fltsowingstatus").val();
    if (current === status) {
        $("#fltsowingstatus").val("all").trigger("change");
    } else {
        $("#fltsowingstatus").val(status).trigger("change");
    }
});

$(document).on("click", "#btnResetFilters", function () {
    currentActiveCardFilter = "all";
    $("#fltlocation").val("");
    $("#fltproduct").val("").trigger("change");
    $("#fltordertype").val("all");
    $("#fltproducttype").val("all");
    $("#fltsowingstatus").val("all");
    $("#fltpagesize").val("25");
    $("#oSearchInput").val("");
    if ($('#moreFiltersCollapse').hasClass('show')) {
        $('#moreFiltersCollapse').collapse('hide');
    }
    applySowingFiltersAndRender(true);
});

// Toggle collapse icon & text on show/hide
$(document).on('show.bs.collapse', '#moreFiltersCollapse', function () {
    $('#iconToggleFilters').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    $('#lblToggleFilters').text('Less Filters');
});
$(document).on('hide.bs.collapse', '#moreFiltersCollapse', function () {
    $('#iconToggleFilters').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    $('#lblToggleFilters').text('More Filters');
});

// Summary Card Quick Filter Click Handlers
$(document).on("click", ".sowing-stat-card", function () {
    const cardFilter = $(this).data("cardfilter");
    if (!cardFilter) return;

    currentActiveCardFilter = cardFilter;

    if (cardFilter === "all" || cardFilter === "orderqty") {
        $("#fltordertype").val("all");
        $("#fltproducttype").val("all");
        $("#fltsowingstatus").val("all");
    } else if (cardFilter === "completed") {
        $("#fltsowingstatus").val("completed");
        $("#fltordertype").val("all");
        $("#fltproducttype").val("all");
    } else if (cardFilter === "pending") {
        $("#fltsowingstatus").val("pending");
        $("#fltordertype").val("all");
        $("#fltproducttype").val("all");
    } else if (cardFilter === "client") {
        $("#fltordertype").val("client");
        $("#fltsowingstatus").val("all");
        $("#fltproducttype").val("all");
    } else if (cardFilter === "grafted") {
        $("#fltproducttype").val("grafted");
        $("#fltsowingstatus").val("all");
        $("#fltordertype").val("all");
    }

    $(".sowing-stat-card").removeClass("active-card");
    $(this).addClass("active-card");

    applySowingFiltersAndRender(true);
});

// Pagination Navigation Button Click Handlers
$(document).on("click", ".sowing-page-btn", function (e) {
    e.preventDefault();
    const targetPage = parseInt($(this).data("page"), 10);
    if (!isNaN(targetPage) && targetPage > 0 && targetPage !== sowingCurrentPage) {
        sowingCurrentPage = targetPage;
        renderSowingPage();
        const tableCard = document.querySelector(".sowing-table-card");
        if (tableCard) {
            tableCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
});

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

// ----------------------------------------------------
// Production Stock Order Removal with Remark Handlers
// ----------------------------------------------------
$(document).on("click", ".delete-stockorder-btn", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const $btn         = $(this);
    const stockOrderNo = $btn.data("stockorderno") || "";
    const productName  = $btn.data("productname") || "";
    const seedVariety  = $btn.data("seedvariety") || "";
    const orderQty     = $btn.data("orderqty") || 0;
    const clientName   = $btn.data("clientname") || "";
    const clientId     = $btn.data("clientid") || "";
    const psoNo        = $btn.data("psono") || "";
    const sowingDate   = $btn.data("sowingdate") || "";
    const deliveryDate = $btn.data("expecteddeliverydate") || "";
    const isGrafted    = $btn.data("isgrafted") == "1";
    const isClientOrder = $btn.data("isclientorder") == "1" || (clientName && clientName.toString().trim().toLowerCase() !== "karjee farms") || (psoNo && psoNo.indexOf("KF-PS") !== -1);
    const projectId    = $("body").data("projectid") || 1;
    const appRoute     = $("body").data("routurl") || (typeof routurl !== "undefined" && routurl ? routurl : "../");

    // Fetch live linked preview & check sowing status
    $.ajax({
        url: `${appRoute}krf-orderandsales/modal/product_modal.php?action=getOrderRemovalPreview`,
        type: "POST",
        data: {
            stockorder_no: stockOrderNo,
            projectid: projectId
        },
        dataType: "json",
        success: function (res) {
            if (res.status === "success" && res.data) {
                const d = res.data;

                // Check if sowing has started (Root or Scion for grafted, all for non-grafted)
                if (d.sowing_started === 1 || d.sowing_started === true) {
                    Swal.fire({
                        icon: "error",
                        title: "Cannot " + (isClientOrder ? "Cancel Order" : "Remove Order"),
                        html: `
                            <div class="text-start" style="font-size: 0.9rem;">
                                <div class="alert alert-danger py-2 px-3 small mb-2 fw-bold">
                                    <i class="fa fa-exclamation-triangle me-1"></i>${d.cannot_remove_reason || 'Sowing has already started for this order.'}
                                </div>
                                <table class="table table-sm table-bordered mb-2">
                                    <tr><th style="width: 38%;" class="bg-light">Order No:</th><td><b>${d.stockorder_no}</b></td></tr>
                                    ${d.psono ? `<tr><th class="bg-light">PSO No:</th><td><span class="badge bg-primary">${d.psono}</span></td></tr>` : ''}
                                    ${d.clientname ? `<tr><th class="bg-light">Client:</th><td><b class="text-primary">${d.clientname}</b></td></tr>` : ''}
                                    <tr><th class="bg-light">Product:</th><td>${d.product_name}</td></tr>
                                    <tr><th class="bg-light">Type:</th><td><span class="badge ${d.is_grafted ? 'bg-info text-dark' : 'bg-secondary'}">${d.is_grafted ? 'Grafted' : 'Non-Grafted'}</span></td></tr>
                                    ${d.is_grafted ? `
                                        <tr><th class="bg-light">Root Sowing:</th><td>${d.root_sowed_cnt > 0 ? `<b class="text-danger">${d.root_sowed_cnt} trays sowed</b>` : '<span class="text-success">Not started</span>'}</td></tr>
                                        <tr><th class="bg-light">Scion Sowing:</th><td>${d.scion_sowed_cnt > 0 ? `<b class="text-danger">${d.scion_sowed_cnt} trays sowed</b>` : '<span class="text-success">Not started</span>'}</td></tr>
                                    ` : `
                                        <tr><th class="bg-light">Sowing Done:</th><td><b class="text-danger">${d.total_sowed_cnt} trays sowed</b></td></tr>
                                    `}
                                </table>
                                <div class="alert alert-warning py-1 px-2 small mb-0">
                                    <i class="fa fa-info-circle me-1"></i>Once sowing has started (for Root or Scion), orders cannot be cancelled or removed.
                                </div>
                            </div>
                        `
                    });
                    return;
                }

                // Sowing has NOT started -> configure & open modal
                $("#remove_stockorder_no").val(stockOrderNo);
                $("#remove_psono").val(d.psono || psoNo || "");
                $("#remove_is_forced").val(isClientOrder ? "1" : "0");
                $("body").data("is_client_order", isClientOrder ? "1" : "0");
                $("#remove_stockorder_remark").val("").removeClass("is-invalid");
                $("#remove_stockorder_remark_err").hide();

                // Option to update tedit column in krf_polyhouse_sowingschedule is ONLY visible if loginroleid < 2
                const roleIdForTedit = parseInt($("body").data("loginroleid") || $("body").attr("data-loginroleid") || $("#hidden_loginroleid").val() || (typeof loginroleid !== 'undefined' ? loginroleid : 0)) || 0;
                if (roleIdForTedit < 2) {
                    $("#wrapper_update_tedit_sowingschedule").show();
                    $("#chk_update_tedit_sowingschedule").prop("checked", false);
                } else {
                    $("#wrapper_update_tedit_sowingschedule").hide();
                    $("#chk_update_tedit_sowingschedule").prop("checked", false);
                }

                $("#lbl_remove_stockorderno").text(stockOrderNo);
                $("#lbl_remove_productname").text(d.product_name || productName);
                $("#lbl_remove_orderqty").text(d.quantity || orderQty);
                $("#lbl_remove_seedvariety").text(d.seed_variety || seedVariety || "N/A");
                $("#lbl_remove_producttype").text(d.is_grafted ? "Grafted" : "Non-Grafted")
                    .removeClass("bg-secondary bg-info text-dark")
                    .addClass(d.is_grafted ? "bg-info text-dark" : "bg-secondary text-white");

                const sowD = d.sowingdate || sowingDate;
                const delD = d.expected_delivery_date || deliveryDate;
                $("#lbl_remove_sowingdate").text(sowD ? changeDateFormatYmd_dMY(sowD) : "N/A");
                $("#lbl_remove_deliverydate").text(delD ? changeDateFormatYmd_dMY(delD) : "N/A");

                if (isClientOrder) {
                    const cName = d.clientname || clientName || "Client";
                    $("#lbl_modal_icon").attr("class", "fa fa-ban me-2");
                    $("#lbl_modal_title").html(`Cancel Client Production Order (<span class="text-warning">${toProperCase(cName)}</span>)`);
                    $("#lbl_remove_clientname").text(toProperCase(cName));
                    $("#lbl_remove_client_wrapper").show();
                    $("#remove_stockorder_client_text").html(`This production order belongs to Client: <b>${toProperCase(cName)}</b> (PSO: <b>${d.psono || psoNo}</b>). Cancelling it will cancel the provisional order and issue a Credit Note for any paid advance.`);
                    $("#remove_stockorder_client_alert").show();
                    $("#lbl_remove_remark_title").html(`Reason / Remark for Order Cancellation <span class="text-danger">*</span>`);
                    $("#remove_stockorder_remark").attr("placeholder", "Why are you cancelling this client order? (Required)");
                    $("#lbl_btn_remove_icon").attr("class", "fa fa-ban me-1");
                    $("#lbl_btn_remove_text").text("Cancel Client Order");
                } else {
                    $("#lbl_modal_icon").attr("class", "fa fa-trash me-2");
                    $("#lbl_modal_title").text("Remove Production Stock Order");
                    $("#lbl_remove_client_wrapper").hide();
                    $("#remove_stockorder_client_alert").hide();
                    $("#lbl_remove_remark_title").html(`Reason / Remark for Removal <span class="text-danger">*</span>`);
                    $("#remove_stockorder_remark").attr("placeholder", "Why are you removing this product? (Required)");
                    $("#lbl_btn_remove_icon").attr("class", "fa fa-trash me-1");
                    $("#lbl_btn_remove_text").text("Remove Stock Order");
                }

                if (d.psono && d.psono.trim() !== "") {
                    $("#lbl_remove_psono").html(`<span class="badge bg-primary fs-7">${d.psono}</span>`);
                    $("#lbl_remove_pso_wrapper").show();
                } else {
                    $("#lbl_remove_pso_wrapper").hide();
                }

                if (d.sono && d.sono.trim() !== "") {
                    $("#lbl_remove_sono").html(`<span class="badge bg-success fs-7">${d.sono}</span>`);
                    $("#lbl_remove_so_wrapper").show();
                } else {
                    $("#lbl_remove_so_wrapper").hide();
                }

                const paid = parseFloat(d.amount_paid || 0);
                if (paid > 0) {
                    $("#lbl_remove_paid_amount").text("₹ " + paid.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $("#lbl_remove_creditnote_notice").html(
                        `<i class="fa fa-info-circle text-info me-1"></i> A <strong>Credit Note</strong> of <strong>₹ ${paid.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong> will be automatically generated and credited to client's account balance upon cancellation.`
                    );
                    $("#remove_paid_amount_box").show();
                } else {
                    $("#remove_paid_amount_box").hide();
                }

                const modalEl = document.getElementById("removeStockOrderModal");
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.show();
                }
            }
        },
        error: function () {
            Swal.fire('Error', 'Unable to fetch order preview details.', 'error');
        }
    });
});

$(document).on("click", "#btnConfirmRemoveStockOrder", function () {
    const stockOrderNo = $("#remove_stockorder_no").val().trim();
    const remark = $("#remove_stockorder_remark").val().trim();
    const isForced = $("#remove_is_forced").val() || "0";
    const projectId = $("body").data("projectid") || 1;
    const isClientOrder = $("body").data("is_client_order") === "1";

    if (!remark) {
        $("#remove_stockorder_remark").addClass("is-invalid").focus();
        $("#remove_stockorder_remark_err").show();
        return;
    }

    $("#remove_stockorder_remark").removeClass("is-invalid");
    $("#remove_stockorder_remark_err").hide();

    // Confirmation prompt with complete product details before doing action
    const orderNo = $("#lbl_remove_stockorderno").text();
    const orderQty = $("#lbl_remove_orderqty").text();
    const productName = $("#lbl_remove_productname").text();
    const seedVariety = $("#lbl_remove_seedvariety").text();
    const productType = $("#lbl_remove_producttype").text();
    const sowingDate = $("#lbl_remove_sowingdate").text();
    const deliveryDate = $("#lbl_remove_deliverydate").text();
    const clientName = $("#lbl_remove_clientname").text();
    const psoNo = $("#lbl_remove_psono").text();
    const paidText = $("#lbl_remove_paid_amount").text();

    const actionTitle = isClientOrder ? 'Confirm Client Order Cancellation?' : 'Confirm Stock Order Removal?';
    const confirmBtnText = isClientOrder ? '<i class="fa fa-ban me-1"></i> Yes, Cancel Client Order' : '<i class="fa fa-trash me-1"></i> Yes, Remove Stock Order';

    const currentLoginRoleId = parseInt($("body").data("loginroleid") || $("body").attr("data-loginroleid") || $("#hidden_loginroleid").val() || (typeof loginroleid !== 'undefined' ? loginroleid : 0)) || 0;
    const willUpdateTedit = (currentLoginRoleId < 2 && $("#chk_update_tedit_sowingschedule").is(":checked"));

    Swal.fire({
        customClass: {
            container: 'swal2-top-layer'
        },
        didOpen: () => {
            $('.swal2-container').css('z-index', '999999');
        },
        title: actionTitle,
        html: `
            <div class="text-start" style="font-size: 0.9rem;">
                <div class="alert ${isClientOrder ? 'alert-danger' : 'alert-warning'} py-2 px-3 small mb-2 fw-bold">
                    <i class="fa ${isClientOrder ? 'fa-ban' : 'fa-trash'} me-1"></i>
                    Action: ${isClientOrder ? 'Cancel Client Production Order' : 'Remove Production Stock Order'}
                </div>
                <table class="table table-sm table-bordered mb-2">
                    <tbody>
                        <tr><th style="width: 38%;" class="bg-light">Order No:</th><td><b class="text-dark">${orderNo}</b></td></tr>
                        ${psoNo && psoNo !== '-' ? `<tr><th class="bg-light">PSO No:</th><td><span class="badge bg-primary">${psoNo}</span></td></tr>` : ''}
                        ${clientName && clientName !== '-' && isClientOrder ? `<tr><th class="bg-light">Client:</th><td><b class="text-primary">${clientName}</b></td></tr>` : ''}
                        <tr><th class="bg-light">Product Name:</th><td><b>${productName}</b></td></tr>
                        ${seedVariety && seedVariety !== '-' ? `<tr><th class="bg-light">Variety / Seed:</th><td>${seedVariety}</td></tr>` : ''}
                        <tr><th class="bg-light">Product Type:</th><td><span class="badge ${productType.indexOf('Grafted') !== -1 && productType.indexOf('Non') === -1 ? 'bg-info text-dark' : 'bg-secondary'}">${productType}</span></td></tr>
                        <tr><th class="bg-light">Order Quantity:</th><td><b>${orderQty} plants</b></td></tr>
                        ${sowingDate && sowingDate !== '-' ? `<tr><th class="bg-light">Sowing Date:</th><td>${sowingDate}</td></tr>` : ''}
                        ${deliveryDate && deliveryDate !== '-' ? `<tr><th class="bg-light">Expected Delivery:</th><td>${deliveryDate}</td></tr>` : ''}
                        ${paidText && paidText !== '₹ 0.00' ? `<tr><th class="bg-light">Advance Paid:</th><td><b class="text-success">${paidText}</b></td></tr>` : ''}
                        ${currentLoginRoleId < 2 ? `
                            <tr><th class="bg-light">Update tedit:</th><td>${willUpdateTedit ? '<span class="badge bg-danger">Yes (tedit = \'delete\' in krf_polyhouse_sowingschedule)</span>' : '<span class="badge bg-secondary">No (status only)</span>'}</td></tr>
                        ` : ''}
                        <tr><th class="bg-light">Reason:</th><td><i>${remark}</i></td></tr>
                    </tbody>
                </table>
                <p class="text-danger small mb-0 fw-bold"><i class="fa fa-exclamation-triangle me-1"></i>Are you sure you want to proceed? This will mark the order as Cancelled across all modules.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmBtnText,
        cancelButtonText: 'No, Keep Order',
        reverseButtons: true
    }).then((swalRes) => {
        if (!swalRes.isConfirmed) return;

        const $btn = $("#btnConfirmRemoveStockOrder");
        const originalText = $("#lbl_btn_remove_text").text();
        $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Processing...');

        const appRoute = $("body").data("routurl") || (typeof routurl !== "undefined" && routurl ? routurl : "../");

        $.ajax({
            url: `${appRoute}krf-orderandsales/modal/product_modal.php?action=removeProductionStockOrderWithRemark`,
            type: "POST",
            data: {
                stockorder_no: stockOrderNo,
                remark: remark,
                is_forced: isForced,
                projectid: projectId,
                update_tedit_sch: willUpdateTedit ? 1 : 0,
                loginroleid: currentLoginRoleId
            },
            dataType: "json",
            success: function (res) {
                $btn.prop("disabled", false).html('<i class="' + (isClientOrder ? 'fa fa-ban' : 'fa fa-trash') + ' me-1"></i> <span id="lbl_btn_remove_text">' + originalText + '</span>');
                if (res.status === "success") {
                    const modalEl = document.getElementById("removeStockOrderModal");
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }

                    Swal.fire({
                        icon: "success",
                        title: isClientOrder ? "Client Order Cancelled!" : "Order Removed!",
                        text: res.message,
                        timer: 4000,
                        showConfirmButton: true
                    });

                    if (typeof loadSowingScheduleStarting === "function") {
                        loadSowingScheduleStarting($("#sowingweekSelector").val() || "current");
                    }
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Failed",
                        text: res.message || "Could not process order."
                    });
                }
            },
            error: function (xhr) {
                $btn.prop("disabled", false).html('<i class="' + (isClientOrder ? 'fa fa-ban' : 'fa fa-trash') + ' me-1"></i> <span id="lbl_btn_remove_text">' + originalText + '</span>');
                Swal.fire({
                    icon: "error",
                    title: "Server Error",
                    text: "Network or server error occurred while processing order."
                });
            }
        });
    });
});

// ----------------------------------------------------
// Production Stock Order Edit Product / Variety Handlers
// ----------------------------------------------------
let cachedMasterProducts = null;

function loadMasterProducts(callback) {
    if (cachedMasterProducts && cachedMasterProducts.length > 0) {
        if (typeof callback === 'function') callback(cachedMasterProducts);
        return;
    }
    const projectId = $("body").data("projectid") || 1;
    const appRoute = $("body").data("routurl") || (typeof routurl !== "undefined" && routurl ? routurl : "../");

    if (typeof GetFullProductListFrommaster_callback === "function") {
        GetFullProductListFrommaster_callback("all", "all", function (res) {
            cachedMasterProducts = Array.isArray(res) ? res : [];
            if (typeof callback === 'function') callback(cachedMasterProducts);
        });
    } else {
        $.ajax({
            url: `${appRoute}krf-orderandsales/modal/product_modal.php?action=GetFullProductListFrommaster`,
            type: "POST",
            data: { projectid: projectId, shortby: "all", shortbyvalue: "all" },
            dataType: "json",
            success: function (res) {
                cachedMasterProducts = Array.isArray(res) ? res : [];
                if (typeof callback === 'function') callback(cachedMasterProducts);
            },
            error: function () {
                cachedMasterProducts = [];
                if (typeof callback === 'function') callback(cachedMasterProducts);
            }
        });
    }
}

$(document).on("click", ".edit-product-btn", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const $btn         = $(this);
    const stockOrderNo = $btn.data("stockorderno") || "";
    const productId    = $btn.data("productid") || "";
    const productName  = $btn.data("productname") || "";
    const seedName     = $btn.data("seedname") || "";
    const sowingDate   = $btn.data("sowingdate") || "";
    const deliveryDate = $btn.data("expecteddeliverydate") || "";
    const orderQty     = $btn.data("orderqty") || 0;
    const clientName   = $btn.data("clientname") || "";
    const clientId     = $btn.data("clientid") || "";
    const psono        = $btn.data("psono") || "";
    const projectId    = $("body").data("projectid") || 1;
    const appRoute     = $("body").data("routurl") || (typeof routurl !== "undefined" && routurl ? routurl : "../");

    // First check if sowing has started (Root & Scion check)
    $.ajax({
        url: `${appRoute}krf-orderandsales/modal/product_modal.php?action=getOrderRemovalPreview`,
        type: "POST",
        data: {
            stockorder_no: stockOrderNo,
            projectid: projectId
        },
        dataType: "json",
        success: function (res) {
            if (res.status === "success" && res.data) {
                const d = res.data;
                if (d.sowing_started === 1 || d.sowing_started === true) {
                    Swal.fire({
                        icon: "error",
                        title: "Cannot Change Variety",
                        html: `
                            <div class="text-start" style="font-size: 0.9rem;">
                                <div class="alert alert-danger py-2 px-3 small mb-2 fw-bold">
                                    <i class="fa fa-exclamation-triangle me-1"></i>${d.cannot_remove_reason || 'Sowing has already started for this order.'}
                                </div>
                                <table class="table table-sm table-bordered mb-2">
                                    <tr><th style="width: 38%;" class="bg-light">Order No:</th><td><b>${d.stockorder_no}</b></td></tr>
                                    <tr><th class="bg-light">Product:</th><td>${d.product_name}</td></tr>
                                    <tr><th class="bg-light">Type:</th><td><span class="badge ${d.is_grafted ? 'bg-info text-dark' : 'bg-secondary'}">${d.is_grafted ? 'Grafted' : 'Non-Grafted'}</span></td></tr>
                                    ${d.is_grafted ? `
                                        <tr><th class="bg-light">Root Sowing:</th><td>${d.root_sowed_cnt > 0 ? `<b class="text-danger">${d.root_sowed_cnt} trays sowed</b>` : '<span class="text-success">Not started</span>'}</td></tr>
                                        <tr><th class="bg-light">Scion Sowing:</th><td>${d.scion_sowed_cnt > 0 ? `<b class="text-danger">${d.scion_sowed_cnt} trays sowed</b>` : '<span class="text-success">Not started</span>'}</td></tr>
                                    ` : `
                                        <tr><th class="bg-light">Sowing Done:</th><td><b class="text-danger">${d.total_sowed_cnt} trays sowed</b></td></tr>
                                    `}
                                </table>
                                <div class="alert alert-warning py-1 px-2 small mb-0">
                                    <i class="fa fa-info-circle me-1"></i>Variety cannot be changed once sowing has started for either root or scion.
                                </div>
                            </div>
                        `
                    });
                    return;
                }
            }

            // Sowing not started -> open edit variety modal
            $("#edit_product_stockorder_no").val(stockOrderNo);
            $("#edit_product_clientid").val(clientId);
            $("#edit_product_psono").val(psono);
            $("#edit_product_old_productid").val(productId);
            $("#edit_product_old_productname").val(productName);
            $("#edit_product_old_seedname").val(seedName);
            $("#edit_product_old_sowingdate").val(sowingDate);
            $("#edit_product_old_deliverydate").val(deliveryDate);

            $("#lbl_edit_stockorderno").text(stockOrderNo);
            $("#lbl_edit_orderqty").text(orderQty);
            if (clientName) {
                $("#lbl_edit_clientname").text(toProperCase(clientName));
                $("#lbl_edit_client_wrapper").show();
            } else {
                $("#lbl_edit_client_wrapper").hide();
            }

            $("#lbl_edit_old_productname").text(productName);
            $("#lbl_edit_old_seedname").text(seedName || "N/A");
            $("#lbl_edit_old_sowingdate").text(sowingDate ? changeDateFormatYmd_dMY(sowingDate) : "N/A");
            $("#lbl_edit_old_deliverydate").text(deliveryDate ? changeDateFormatYmd_dMY(deliveryDate) : "N/A");

            function getTodayDateStr() {
                const now = new Date();
                const y = now.getFullYear();
                const m = String(now.getMonth() + 1).padStart(2, '0');
                const d = String(now.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }

            const todayStr = getTodayDateStr();
            $("#edit_new_sowing_date").attr("min", todayStr).val(todayStr);
            $("#edit_new_delivery_date").val("");
            $("#edit_product_remark").val("").removeClass("is-invalid");
            $("#edit_product_remark_err").hide();
            $("#edit_new_product_preview").hide();

            $("#edit_new_product_search").val("");
            $("#edit_new_product_id").val("").data("days", 0);
            $("#btnClearProductSearch").hide();
            $("#edit_product_autocomplete_list").empty().hide();

            // Preload Master Products
            loadMasterProducts(function () {
                const modalEl = document.getElementById("editProductVarietyModal");
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.show();
                }
                setTimeout(function () {
                    $("#edit_new_product_search").focus();
                }, 300);
            });
        },
        error: function () {
            Swal.fire('Error', 'Unable to verify order sowing status.', 'error');
        }
    });
});

let productSearchFocusIdx = -1;

function renderProductSuggestions(query) {
    const $list = $("#edit_product_autocomplete_list");
    $list.empty();
    productSearchFocusIdx = -1;

    if (!Array.isArray(cachedMasterProducts) || cachedMasterProducts.length === 0) {
        $list.html('<div class="p-3 text-center text-muted small">Loading products...</div>').show();
        return;
    }

    const q = (query || "").trim().toLowerCase();
    const matches = cachedMasterProducts.filter(p => {
        if (!q) return true;
        const pname = (p.ProductName || "").toLowerCase();
        const variety = (p.RootSeedVariety || p.RootSeedName || "").toLowerCase();
        const crop = (p.CropName || "").toLowerCase();
        const sku = (p.ProductSKU || p.sku || "").toLowerCase();
        return pname.indexOf(q) !== -1 || variety.indexOf(q) !== -1 || crop.indexOf(q) !== -1 || sku.indexOf(q) !== -1;
    }).slice(0, 30); // show top 30 matches for speed

    if (matches.length === 0) {
        $list.html('<div class="p-3 text-center text-muted small"><i class="fa fa-info-circle me-1"></i>No matching products found</div>').show();
        return;
    }

    matches.forEach(p => {
        const days = parseInt(p.fullsycledays || p.RootSeedgrowingdays) || 0;
        const varietyName = p.RootSeedVariety || p.RootSeedName || p.CropName || 'Standard';
        const isGrafted = (p.IsGrafted === 'yes' || p.IsGrafted === 'Yes') ? 'Grafted' : 'Non-Grafted';
        const cropDisplay = p.CropName ? ` <span class="badge bg-light text-secondary border">Crop: ${p.CropName}</span>` : '';
        const cycleDisplay = days > 0 ? `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">${days} Days</span>` : '';

        const $item = $(`
            <a href="javascript:void(0);" class="list-group-item list-group-item-action product-suggest-item py-2 px-3 border-bottom" style="cursor: pointer;">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark text-break">${p.ProductName}</span>
                    <span class="badge ${isGrafted === 'Grafted' ? 'bg-success' : 'bg-info text-dark'} ms-2">${isGrafted}</span>
                </div>
                <div class="small text-muted d-flex justify-content-between align-items-center mt-1">
                    <span>Variety: <strong class="text-secondary">${varietyName}</strong>${cropDisplay}</span>
                    <span>${cycleDisplay}</span>
                </div>
            </a>
        `);

        $item.data("product", p);
        $list.append($item);
    });

    $list.show();
}

function selectMasterProduct(p) {
    if (!p) return;
    const days = parseInt(p.fullsycledays || p.RootSeedgrowingdays) || 0;
    const variety = p.RootSeedVariety || p.RootSeedName || p.CropName || "N/A";
    const type = (p.IsGrafted === 'yes' || p.IsGrafted === 'Yes') ? 'Grafted' : 'Non-Grafted';

    $("#edit_new_product_id").val(p.ProductId).data("days", days);
    $("#edit_new_product_search").val(p.ProductName);
    $("#btnClearProductSearch").show();
    $("#edit_product_autocomplete_list").empty().hide();

    $("#lbl_edit_new_variety").text(variety);
    $("#lbl_edit_new_type").text(type);
    $("#lbl_edit_new_cycledays").text(days > 0 ? `${days} Days` : "Not specified");
    $("#edit_new_product_preview").slideDown();

    // Auto-calculate dates starting from today / current valid sowing date
    const now = new Date();
    const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    let curSow = $("#edit_new_sowing_date").val();
    if (!curSow || curSow < todayStr) {
        curSow = todayStr;
        $("#edit_new_sowing_date").val(todayStr);
    }
    $("#edit_new_sowing_date").attr("min", todayStr);

    if (days > 0) {
        let d = new Date(curSow + 'T00:00:00');
        d.setDate(d.getDate() + days);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const dt = String(d.getDate()).padStart(2, '0');
        const newDeliveryStr = `${y}-${m}-${dt}`;
        $("#edit_new_delivery_date").val(newDeliveryStr).attr("min", curSow);
    } else {
        $("#edit_new_delivery_date").val(curSow).attr("min", curSow);
    }
}

// Live typing in product search
$(document).on("input focus", "#edit_new_product_search", function () {
    const q = $(this).val();
    if (q) {
        $("#btnClearProductSearch").show();
    } else {
        $("#btnClearProductSearch").hide();
    }
    renderProductSuggestions(q);
});

// Clear product search input
$(document).on("click", "#btnClearProductSearch", function () {
    $("#edit_new_product_search").val("").focus();
    $("#edit_new_product_id").val("").data("days", 0);
    $("#btnClearProductSearch").hide();
    $("#edit_new_product_preview").slideUp();
    renderProductSuggestions("");
});

// Arrow Keys & Enter Navigation
$(document).on("keydown", "#edit_new_product_search", function (e) {
    const $list = $("#edit_product_autocomplete_list");
    const $items = $list.find(".product-suggest-item");
    const count = $items.length;

    if (!$list.is(":visible") || count === 0) {
        if (e.which === 40 || e.which === 38) {
            renderProductSuggestions($(this).val());
        }
        return;
    }

    if (e.which === 40) { // Arrow Down
        e.preventDefault();
        productSearchFocusIdx++;
        if (productSearchFocusIdx >= count) productSearchFocusIdx = 0;
        $items.removeClass("active bg-primary text-white").find(".text-dark, .text-muted, .text-secondary").removeClass("text-white");
        const $target = $items.eq(productSearchFocusIdx);
        $target.addClass("active bg-primary text-white").find(".text-dark, .text-muted, .text-secondary").addClass("text-white");
        $target[0].scrollIntoView({ block: "nearest" });
    } else if (e.which === 38) { // Arrow Up
        e.preventDefault();
        productSearchFocusIdx--;
        if (productSearchFocusIdx < 0) productSearchFocusIdx = count - 1;
        $items.removeClass("active bg-primary text-white").find(".text-dark, .text-muted, .text-secondary").removeClass("text-white");
        const $target = $items.eq(productSearchFocusIdx);
        $target.addClass("active bg-primary text-white").find(".text-dark, .text-muted, .text-secondary").addClass("text-white");
        $target[0].scrollIntoView({ block: "nearest" });
    } else if (e.which === 13) { // Enter Key
        e.preventDefault();
        if (productSearchFocusIdx >= 0 && productSearchFocusIdx < count) {
            const p = $items.eq(productSearchFocusIdx).data("product");
            selectMasterProduct(p);
        } else if (count > 0) {
            const p = $items.eq(0).data("product");
            selectMasterProduct(p);
        }
    } else if (e.which === 27) { // Escape Key
        $list.hide();
    }
});

// Click on product suggestion item
$(document).on("click", ".product-suggest-item", function () {
    const p = $(this).data("product");
    selectMasterProduct(p);
});

// Close suggestions when clicking outside
$(document).on("click", function (e) {
    if (!$(e.target).closest("#edit_new_product_search, #edit_product_autocomplete_list, #btnClearProductSearch").length) {
        $("#edit_product_autocomplete_list").hide();
    }
});

// Sowing date change updates delivery date (and prevents past dates)
$(document).on("change", "#edit_new_sowing_date", function () {
    const now = new Date();
    const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    let sowVal = $(this).val();

    if (sowVal && sowVal < todayStr) {
        Swal.fire('Notice', 'Sowing date cannot be before today.', 'info');
        sowVal = todayStr;
        $(this).val(todayStr);
    }

    const days = parseInt($("#edit_new_product_id").data("days")) || 0;
    if (sowVal && days > 0) {
        let d = new Date(sowVal + 'T00:00:00');
        d.setDate(d.getDate() + days);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const dt = String(d.getDate()).padStart(2, '0');
        $("#edit_new_delivery_date").val(`${y}-${m}-${dt}`).attr("min", sowVal);
    } else if (sowVal) {
        $("#edit_new_delivery_date").attr("min", sowVal);
    }
});

// Delivery date change
$(document).on("change", "#edit_new_delivery_date", function () {
    const curSow = $("#edit_new_sowing_date").val();
    const delVal = $(this).val();
    if (curSow && delVal && delVal < curSow) {
        Swal.fire('Notice', 'Expected delivery date cannot be before the sowing date.', 'info');
        $(this).val(curSow);
    }
});

$(document).on("click", "#btnConfirmEditProduct", function () {
    const stockOrderNo    = $("#edit_product_stockorder_no").val().trim();
    const oldProductId    = $("#edit_product_old_productid").val().trim();
    const newProductId    = $("#edit_new_product_id").val().trim();
    const newSowingDate   = $("#edit_new_sowing_date").val().trim();
    const newDeliveryDate = $("#edit_new_delivery_date").val().trim();
    const remark          = $("#edit_product_remark").val().trim();
    const projectId       = $("body").data("projectid") || 1;

    if (!newProductId) {
        Swal.fire('Warning', 'Please select a new product from the dropdown.', 'warning');
        return;
    }

    if (String(newProductId) === String(oldProductId)) {
        Swal.fire('Warning', 'Please choose a different product/variety than the current one.', 'warning');
        return;
    }

    if (!newSowingDate) {
        Swal.fire('Warning', 'Please specify a valid new Sowing Date.', 'warning');
        return;
    }

    if (!remark) {
        $("#edit_product_remark").addClass("is-invalid").focus();
        $("#edit_product_remark_err").show();
        return;
    }

    $("#edit_product_remark").removeClass("is-invalid");
    $("#edit_product_remark_err").hide();

    // Confirmation prompt with complete product details before saving
    const oldProductName = $("#lbl_edit_old_productname").text();
    const oldSeedName    = $("#lbl_edit_old_seedname").text();
    const newProductName = $("#edit_new_product_search").val();
    const newVariety     = $("#lbl_edit_new_variety").text();
    const orderQty       = $("#lbl_edit_orderqty").text();
    const clientName     = $("#lbl_edit_clientname").text();
    const psoNo          = $("#edit_product_psono").val();

    Swal.fire({
        title: 'Confirm Product Variety Change?',
        html: `
            <div class="text-start" style="font-size: 0.9rem;">
                <div class="alert alert-info py-2 px-3 small mb-2">
                    <i class="fa fa-info-circle me-1"></i><b>Notice:</b> Only this particular production order (<b>${stockOrderNo}</b>) will change its variety. Other items in this order will remain completely unaffected.
                </div>
                <table class="table table-sm table-bordered mb-2">
                    <tbody>
                        <tr><th style="width: 38%;" class="bg-light">Order No:</th><td><b>${stockOrderNo}</b></td></tr>
                        ${psoNo ? `<tr><th class="bg-light">PSO No:</th><td><span class="badge bg-primary">${psoNo}</span></td></tr>` : ''}
                        ${clientName && clientName !== '-' ? `<tr><th class="bg-light">Client:</th><td><b class="text-primary">${clientName}</b></td></tr>` : ''}
                        <tr><th class="bg-light">Current Product:</th><td><span class="text-danger">${oldProductName}</span></td></tr>
                        <tr><th class="bg-light">Current Variety:</th><td>${oldSeedName || 'N/A'}</td></tr>
                        <tr><th class="bg-light">New Product:</th><td><b class="text-success">${newProductName}</b></td></tr>
                        <tr><th class="bg-light">New Variety:</th><td><b class="text-success">${newVariety || 'N/A'}</b></td></tr>
                        <tr><th class="bg-light">Order Quantity:</th><td><b>${orderQty} plants</b></td></tr>
                        <tr><th class="bg-light">New Sowing Date:</th><td><b>${newSowingDate}</b></td></tr>
                        ${newDeliveryDate ? `<tr><th class="bg-light">New Delivery Date:</th><td><b>${newDeliveryDate}</b></td></tr>` : ''}
                        <tr><th class="bg-light">Reason:</th><td><i>${remark}</i></td></tr>
                    </tbody>
                </table>
                <p class="text-dark small mb-0 fw-bold">Are you sure you want to change the variety for this order?</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<span class="text-dark fw-bold"><i class="fa fa-save me-1"></i> Yes, Change Variety</span>',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((swalRes) => {
        if (!swalRes.isConfirmed) return;

        const $btn = $("#btnConfirmEditProduct");
    $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Updating & Notifying...');

    const appRoute = $("body").data("routurl") || (typeof routurl !== "undefined" && routurl ? routurl : "../");

    $.ajax({
            url: `${appRoute}krf-orderandsales/modal/product_modal.php?action=changeProductionStockOrderProduct`,
            type: "POST",
            data: {
                stockorder_no: stockOrderNo,
                new_product_id: newProductId,
                new_sowing_date: newSowingDate,
                new_delivery_date: newDeliveryDate,
                remark: remark,
                projectid: projectId
            },
            dataType: "json",
            success: function (res) {
                $btn.prop("disabled", false).html('<i class="fa fa-save me-1"></i> Save & Update Product');
                if (res.status === "success") {
                    const modalEl = document.getElementById("editProductVarietyModal");
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }

                    Swal.fire({
                        icon: "success",
                        title: "Product Updated!",
                        text: res.message,
                        timer: 2500,
                        showConfirmButton: false
                    });

                    if (typeof loadSowingScheduleStarting === "function") {
                        loadSowingScheduleStarting($("#sowingweekSelector").val() || "current");
                    }
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Failed",
                        text: res.message || "Could not update product details."
                    });
                }
            },
            error: function () {
                $btn.prop("disabled", false).html('<i class="fa fa-save me-1"></i> Save & Update Product');
                Swal.fire({
                    icon: "error",
                    title: "Server Error",
                    text: "Network or server error occurred while updating product."
                });
            }
        });
    });
});


/* =========================================================================
   SUGGESTED BOX OPEN AFTER DAYS & AUTO-FILL SWITCH HANDLERS
   ========================================================================= */
function loadSuggestedBoxOpenDays(productId, productName, stockOrderNo) {
    const routurl = $("body").data("routurl") || "";
    const projectid = $("body").data("projectid") || 0;

    $("#suggested_days_badges").html('<span class="spinner-border spinner-border-sm text-primary" role="status"></span>');
    $("#suggested_box_days_container").show();

    $.ajax({
        url: `${routurl}krf-orderandsales/modal/sowing_modal.php?action=getSuggestedBoxOpenDays`,
        type: "POST",
        dataType: "JSON",
        data: {
            projectid: projectid,
            productid: productId,
            productname: productName,
            stockorderno: stockOrderNo
        },
        success: function (res) {
            if (res && res.status === "success" && Array.isArray(res.days) && res.days.length > 0) {
                let html = "";
                const currentVal = parseInt($("#boxopenafterdays").val(), 10);
                res.days.forEach((day, idx) => {
                    const isLatest = (idx === 0);
                    const isActive = (currentVal === day);
                    const label = isLatest ? `${day}d <small class="opacity-75">★</small>` : `${day}d`;
                    const title = isLatest ? `Most recent: ${day} days` : `${day} days`;
                    html += `<button type="button" class="day-suggest-pill ${isActive ? 'active' : ''}" data-days="${day}" title="${title}">${label}</button>`;
                });
                $("#suggested_days_badges").html(html);
                $("#suggested_box_days_container").fadeIn(150);

                // Auto-fill handling based on switch preference in localStorage (defaults to true)
                const isAutoFillEnabled = (localStorage.getItem("germ_autofill_box_days") !== "false");
                $("#switch_autofill_box_days").prop("checked", isAutoFillEnabled);

                if (isAutoFillEnabled && res.latest) {
                    $("#boxopenafterdays").val(res.latest).trigger("input");
                    $("#suggested_days_badges .day-suggest-pill").removeClass("active");
                    $(`#suggested_days_badges .day-suggest-pill[data-days='${res.latest}']`).addClass("active");
                }
            } else {
                $("#suggested_box_days_container").hide();
            }
        },
        error: function () {
            $("#suggested_box_days_container").hide();
        }
    });
}

// Toggle switch to auto-fill
$(document).on("change", "#switch_autofill_box_days", function () {
    const isChecked = $(this).is(":checked");
    localStorage.setItem("germ_autofill_box_days", isChecked ? "true" : "false");

    if (isChecked) {
        const $firstPill = $("#suggested_days_badges .day-suggest-pill").first();
        if ($firstPill.length) {
            const days = $firstPill.data("days");
            $("#boxopenafterdays").val(days).trigger("input");
            $("#suggested_days_badges .day-suggest-pill").removeClass("active");
            $firstPill.addClass("active");
        }
    } else {
        $("#boxopenafterdays").val("").trigger("input");
        $("#suggested_days_badges .day-suggest-pill").removeClass("active");
    }
});

// Click on quick suggestion pill
$(document).on("click", ".day-suggest-pill", function (e) {
    e.preventDefault();
    const days = $(this).data("days");
    if (days !== undefined && days !== "") {
        $("#boxopenafterdays").val(days).trigger("input");
        $("#suggested_days_badges .day-suggest-pill").removeClass("active");
        $(this).addClass("active");
        $("#switch_autofill_box_days").prop("checked", true);
        localStorage.setItem("germ_autofill_box_days", "true");
    }
});

// Highlight matching pill when user manually changes boxopenafterdays
$(document).on("input", "#boxopenafterdays", function () {
    const val = parseInt($(this).val(), 10);
    $("#suggested_days_badges .day-suggest-pill").removeClass("active");
    if (!isNaN(val)) {
        $(`#suggested_days_badges .day-suggest-pill[data-days='${val}']`).addClass("active");
    }
});