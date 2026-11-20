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
$(document).on("input", "#counttiprintqrcode", function () {
    let totalBoxes = parseInt($("#totalboxesforgerm").val()) || 0;
    let printboxcount = parseInt($(this).val()) || 0;
    if (printboxcount > totalBoxes) {
        $(this).val(totalBoxes);
        Swal.fire({
            icon: 'warning',
            title: 'Limit Exceeded',
            text: 'Print box count cannot be greater than total boxes.'
        });
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

$(document).on("click", "#saveGerminationDetailsBtnpck", function () {
    let StockOrderNo = $("#saveGerminationDetailsBtnpck").data("datastockordernopck");
    let ProductName = $("#saveGerminationDetailsBtnpck").data("dataproductnamepck");
    let ProductId = $("#saveGerminationDetailsBtnpck").data("dataproductidpck");
    let roottype = $("#saveGerminationDetailsBtnpck").data("roottype");

    let traycountinbox = $("#traycountinbox").val().trim();
    let countboxqrcodetoprint = $("#counttiprintqrcode").val().trim();

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
    SaveAndUpdateGerminationData(StockOrderNo, ProductId, ProductName, traycountinbox, packingdate, dateofopening, countboxqrcodetoprint, roottype, function (res) {
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
            $("#traycountinbox").val(0).trigger("input");
            let todayDateStr = new Date().toISOString().split('T')[0];
            $("#packingdate").val(todayDateStr);
            $("#dateofopening").val('');
            $("#boxopenafterdays").val('');

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
    // debugger;;
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
            generateSeedLotCardsByStockOrder(StockOrderNo, roottype);
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

function fillFilterDropdowns(data) {

    const locations = new Map();
    const products  = new Map();

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


    // -------------------------
    // Location Dropdown
    // -------------------------
    const $location = $('#fltlocation');

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


    // -------------------------
    // Product Dropdown
    // -------------------------
    const $product = $('#fltproduct');

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
}

$(document).on("change", "#fltlocation, #fltproduct", function () {

    const locationId = String($("#fltlocation").val() || "");
    const productId  = String($("#fltproduct").val() || "");

    $("#scheduleTable tr").each(function () {

        const $row = $(this);

        const rowLocationId = String($row.data("sowinglocationid") || "");
        const rowProductId  = String($row.data("productid") || "");

        const locationMatch =
            locationId === "" || rowLocationId === locationId;

        const productMatch =
            productId === "" || rowProductId === productId;

        if (locationMatch && productMatch) {
            $row.show();
        } else {
            $row.hide();
        }
    });

});
/*function toProperCase(str) {
    return (str || '')
        .toLowerCase()
        .replace(/\b\w/g, char => char.toUpperCase());
}*/
function toProperCase(str) {
    return String(str ?? '')
        .toLowerCase()
        .replace(/\b\w/g, char => char.toUpperCase());
}
$(document).on("input", "#oSearchInput", function () {

    const searchText = ($(this).val() || "")
        .toLowerCase()
        .trim();

    $("#scheduleTable tr").each(function () {
        const $row = $(this);
        const rowText = $row.text()
            .toLowerCase()
            .trim();
        if (searchText === "" || rowText.indexOf(searchText) !== -1) {
            $row.show();
        } else {
            $row.hide();
        }
    });
});
function renderSowingTable(res) {
    let prdotr = "";
    fillFilterDropdowns(res);
 
    if (Array.isArray(res) && res.length > 0) {

        // Sort by Sowing Date ASC
        res.sort((a, b) => new Date(a.sowingdate) - new Date(b.sowingdate));

        for (let i = 0; i < res.length; i++) { 
            const sowingTrayCount = parseInt(res[i].sowingtraycount) || 0;
            const totalSowingQty   = parseInt(res[i].totalsowingqty) || 0;
            const pendingSowingQty = parseInt(res[i].pendingsowingqty) || 0;

            // Disable germination when sowing is completed
            const isSowingCompleted = totalSowingQty > 0 && pendingSowingQty === 0;

          

            const gerBtnStyle = isSowingCompleted
                ? "background: #bdbdbd; color: #666; border-color: #bdbdbd; cursor: not-allowed;"
                : "background: linear-gradient(120deg, #a1c4fd, #c2e9fb);";

            let gerBtnDisabled = sowingTrayCount > 0 ? "" : "disabled";
            const germinationboxinfo = Array.isArray(res[i].germinatinboxdetails) ? res[i].germinatinboxdetails : [];
            const lotdetails = Array.isArray(res[i].lotdetails) ? res[i].lotdetails : [];
            const pendingswtrayforbox = Array.isArray(res[i].pendingforgerminationbox) ? res[i].pendingforgerminationbox : [];
            const hasUndoMakeBox =
                pendingswtrayforbox.length > 0 &&
                pendingswtrayforbox.every(item =>
                    String(item.status || '').trim().toLowerCase() === 'tray received at germination' &&
                    String(item.workingstatus || '').trim().toLowerCase() === 'withoutgermination'
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
            const flagA = germinationboxinfo.some(item =>
                String(item.productid) === String(res[i].productid) &&
                String(item.stockorderno) === String(res[i].stockorder_no)
            ); 
            const flagB = pendingswtrayforbox.some(item =>
                String(item.productid) === String(res[i].productid) &&
                /*String(item.stockorder_no) === String(res[i].stockorder_no)
                && */String(item.germinationwithtray) === "yes"
            );
            if (flagB) {
                const finalGerBtnDisabled = (gerBtnDisabled === "disabled"   )  ? "disabled"  : "";
                 germinationbtn = `<button  style="background: linear-gradient(120deg, #a1c4fd, #c2e9fb);" class="btn btn-sm btn-info startgerbtntd_withoutbox" ${finalGerBtnDisabled}>Germinate Without Box<br/><small>For Germination</small></button>`;
            } else if (flagA) {
                 germinationbtn = `<button  style="background: linear-gradient(120deg, #a1c4fd, #c2e9fb);" class="btn btn-sm btn-info startgerbtntd" ${gerBtnDisabled}>Make a Box<br/><small>For Germination</small></button>`;
            } else if ( res[i].isgrafted === 'yes' || res[i].isgrafted === 'Yes') {
                 germinationbtn = `<button  style="background: linear-gradient(120deg, #a1c4fd, #c2e9fb);" class="btn btn-sm btn-info startgerbtntd" ${gerBtnDisabled}>Make a Box<br/><small>For Germination</small></button>`;
            } else{
                germinationbtn = `<button  style="background: linear-gradient(120deg, #a1c4fd, #c2e9fb);" class="btn btn-sm btn-info btnnongrafted" ${gerBtnDisabled}>Ask <br/><small>For Germination</small></button>`;
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
                // missingLocationDisplay = `<span class="fw-bold" style="color: red;">Missing sowing location</span>`;
                 missingLocationDisplay = `
                    <button class="btn btn-sm updswlocationtd" style="background: linear-gradient(220deg, #a1c4fd, #c2f9ff);">
                        Location Missing<br/><small>For Sowing</small>
                    </button>`;
            }

            // let sowinglocation = (res[i].sowinglocationid && res[i].sowinglocationid != "0") ? res[i].sowinglocationname : missingLocationDisplay;
            //let totalSowingQty = parseInt(res[i].totalsowingqty) || 0;

            let hasSowingLocation =
                res[i].sowinglocationid &&
                res[i].sowinglocationid != "0";

            let sowinglocation = "";

            if (hasSowingLocation) {

                // Existing location
                sowinglocation = `
                    <div>${res[i].sowinglocationname}</div>
                `;

                // Allow location change only before sowing starts
                if (totalSowingQty === 0) {

                    sowinglocation += `
                        <button type="button"
                                class="btn btn-sm updswlocationtd mt-1"
                                style="background: linear-gradient(220deg, #a1c4fd, #c2f9ff);">
                            Update Location
                        </button>
                    `;
                }

            } else {

                // Location missing
                sowinglocation = missingLocationDisplay;
            }



            let style = getSowingRowBgStyle(res[i].sowingdate, res[i].pendingsowingqty);
            // Highlight completed sowing row in orange ajay
            if (
                (parseInt(res[i].totalsowingqty) || 0) > 0 &&
                (parseInt(res[i].pendingsowingqty) || 0) === 0
            ) {
                style = "background-color: #ffcc80;";
            }

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
                    data-lotdetails="${encodeURIComponent(JSON.stringify(lotdetails))}" data-gboxdata="${encodeURIComponent(JSON.stringify(germinationboxinfo))}"
                     data-createdbyname="${res[i].createdbyname || ''}"
                    >
                    <td style="${style}">${i + 1}</td>
                    <td style="${style}">${sowinglocation}  </td>
                  <td style="${style}">
                   ${res[i].stockorder_no}${res[i].psono ? `
                        <span class="badge bg-success text-dark ms-1">
                            (${res[i].psono})
                        </span>
                    ` : ''}


                    ${(currentLoginRoleId < 2 && totalSowingQty === 0 && sowingTrayCount === 0) ? `
                        <button type="button" 
                                class="btn btn-sm btn-danger py-0 px-1 ms-1 delete-stockorder-btn"
                                data-stockorderno="${res[i].stockorder_no}"
                                data-productname="${res[i].product_name || ''}"
                                data-orderqty="${res[i].orderqty || 0}"
                                data-clientname="${res[i].clientname || ''}"
                                data-psono="${res[i].psono || ''}"
                                title="Remove Production Stock Order"
                                style="font-size: 0.72rem; padding: 2px 5px !important; border-radius: 4px; vertical-align: middle; line-height: 1.2;">
                            <i class="fa fa-trash"></i>
                        </button>
                    ` : ''}

                    ${res[i].clientname ? `
                        <br>
                        <span class="small fw-semibold">Client:</span>
                        <span class="badge bg-primary mt-1">
                            ${toProperCase(res[i].clientname)}
                        </span>
                    ` : ''}



                        ${res[i].createdbyname ? `
                            <br>
                            <span class="small fw-semibold">Created by:</span>
                            <span class="badge bg-success mt-1">
                                 ${toProperCase(res[i].createdbyname)}
                            </span>
                        ` : ''}
                </td>
                    <td style="${style}">${changeDateFormatYmd_dMY(res[i].sowingdate)}


                        ${res[i].expecteddeliverydate ? `
                            <br>
                            <span class="small fw-semibold">Delivery Date:</span>
                            <span class="badge bg-success mt-1">
                                 ${changeDateFormatYmd_dMY(res[i].expecteddeliverydate)}
                            </span>
                        ` : ''}

                    </td>
                    <td style="${style}">${seedVarietyDisplay}</td>
                    <td style="${style}">
                        ${res[i].product_name}
                        ${(currentLoginRoleId < 2 && totalSowingQty === 0 && sowingTrayCount === 0) ? `
                            <button type="button" 
                                    class="btn btn-sm btn-warning text-dark py-0 px-1 ms-1 edit-product-btn"
                                    data-stockorderno="${res[i].stockorder_no}"
                                    data-productid="${res[i].productid}"
                                    data-productname="${(res[i].product_name || '').replace(/"/g, '&quot;')}"
                                    data-seedname="${(seedVarietyDisplay || '').replace(/"/g, '&quot;')}"
                                    data-sowingdate="${res[i].sowingdate || ''}"
                                    data-expecteddeliverydate="${res[i].expected_delivery_date || res[i].expecteddeliverydate || ''}"
                                    data-orderqty="${res[i].orderqty || 0}"
                                   data-clientname="${String(res[i].clientname ?? '').replace(/"/g, '&quot;')}"
                                    data-clientid="${res[i].clientid || ''}"
                                    data-psono="${res[i].psono || ''}"
                                    title="Change Product / Seed Variety"
                                    style="font-size: 0.72rem; padding: 2px 5px !important; border-radius: 4px; vertical-align: middle; line-height: 1.2;">
                                <i class="fa fa-edit"></i>
                            </button>
                        ` : ''}
                    </td>
                    <td style="${style}">${res[i].orderqty}</td>
                    <td style="${style}">${res[i].totalsowingqty}</td>
                    <td style="${style}">${res[i].pendingsowingqty}</td>
                    <td style="${style}">${sowingtrybtn}</td>
                    <td style="${style}">${germinationbtn} ${undoMakeBoxBtn}</td>                            
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

// ----------------------------------------------------
// Production Stock Order Removal with Remark Handlers
// ----------------------------------------------------
$(document).on("click", ".delete-stockorder-btn", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const stockOrderNo = $(this).data("stockorderno") || "";
    const productName  = $(this).data("productname") || "";
    const orderQty     = $(this).data("orderqty") || 0;
    const clientName   = $(this).data("clientname") || "";
    const projectId    = $("body").data("projectid") || 1;
    const appRoute     = $("body").data("routurl") || (typeof routurl !== "undefined" && routurl ? routurl : "../");

    $("#remove_stockorder_no").val(stockOrderNo);
    $("#remove_is_forced").val(clientName ? "1" : "0");
    $("#remove_stockorder_remark").val("").removeClass("is-invalid");
    $("#remove_stockorder_remark_err").hide();

    $("#lbl_remove_stockorderno").text(stockOrderNo);
    $("#lbl_remove_productname").text(productName);
    $("#lbl_remove_orderqty").text(orderQty);

    // Initial reset of linked fields
    $("#lbl_remove_pso_wrapper").hide();
    $("#lbl_remove_psono").text("-");
    $("#lbl_remove_so_wrapper").hide();
    $("#lbl_remove_sono").text("-");
    $("#remove_paid_amount_box").hide();
    $("#lbl_remove_paid_amount").text("₹ 0.00");

    if (clientName && clientName.toString().trim() !== "") {
        $("#lbl_remove_clientname").text(toProperCase(clientName));
        $("#lbl_remove_client_wrapper").show();
        $("#remove_stockorder_client_text").html(`This production order is linked to Client: <b>${toProperCase(clientName)}</b>. Removing it will cancel linked Provisional Order (PSO), Final Sales Order (SO), and generate a Credit Note for any paid amount. Are you sure you want to forcibly remove it?`);
        $("#remove_stockorder_client_alert").show();
        $("#lbl_btn_remove_text").text("Force Remove Order");
    } else {
        $("#lbl_remove_client_wrapper").hide();
        $("#remove_stockorder_client_alert").hide();
        $("#lbl_btn_remove_text").text("Remove Order");
    }

    // Fetch live linked PSO, SO, Client, and Paid Amount preview
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
                if (d.clientname) {
                    $("#lbl_remove_clientname").text(toProperCase(d.clientname));
                    $("#lbl_remove_client_wrapper").show();
                    $("#remove_is_forced").val("1");
                    $("#lbl_btn_remove_text").text("Force Remove Order");
                }
                if (d.psono && d.psono.trim() !== "") {
                    $("#lbl_remove_psono").html(`<span class="badge bg-primary fs-7">${d.psono}</span>`);
                    $("#lbl_remove_pso_wrapper").show();
                }
                if (d.sono && d.sono.trim() !== "") {
                    $("#lbl_remove_sono").html(`<span class="badge bg-success fs-7">${d.sono}</span>`);
                    $("#lbl_remove_so_wrapper").show();
                }

                const paid = parseFloat(d.amount_paid || 0);
                if (paid > 0) {
                    $("#lbl_remove_paid_amount").text("₹ " + paid.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $("#lbl_remove_creditnote_notice").html(
                        `<i class="fa fa-info-circle text-info me-1"></i> A <strong>Credit Note</strong> of <strong>₹ ${paid.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong> will be automatically generated and credited to client's account balance upon cancellation.`
                    );
                    $("#remove_paid_amount_box").show();
                    $("#remove_stockorder_client_alert").show();
                    $("#remove_stockorder_client_text").html(
                        `This production order is linked to Client: <b>${toProperCase(d.clientname || clientName || 'Client')}</b> with <b>₹ ${paid.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</b> paid advance. Removing it will cancel linked Provisional Order (PSO) & Final Sales Order (SO), and automatically issue a <b>Credit Note</b>.`
                    );
                } else if (d.clientname || (d.psono && d.psono.trim() !== "")) {
                    $("#remove_paid_amount_box").hide();
                    $("#remove_stockorder_client_alert").show();
                }
            }
        },
        error: function () {
            // Silently fallback to basic data attributes
        }
    });

    const modalEl = document.getElementById("removeStockOrderModal");
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
});

$(document).on("click", "#btnConfirmRemoveStockOrder", function () {
    const stockOrderNo = $("#remove_stockorder_no").val().trim();
    const remark = $("#remove_stockorder_remark").val().trim();
    const isForced = $("#remove_is_forced").val() || "0";
    const projectId = $("body").data("projectid") || 1;

    if (!remark) {
        $("#remove_stockorder_remark").addClass("is-invalid").focus();
        $("#remove_stockorder_remark_err").show();
        return;
    }

    $("#remove_stockorder_remark").removeClass("is-invalid");
    $("#remove_stockorder_remark_err").hide();

    const $btn = $(this);
    const originalText = $("#lbl_btn_remove_text").text();
    $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin me-1"></i> Removing...');

    const appRoute = $("body").data("routurl") || (typeof routurl !== "undefined" && routurl ? routurl : "../");

    $.ajax({
        url: `${appRoute}krf-orderandsales/modal/product_modal.php?action=removeProductionStockOrderWithRemark`,
        type: "POST",
        data: {
            stockorder_no: stockOrderNo,
            remark: remark,
            is_forced: isForced,
            projectid: projectId
        },
        dataType: "json",
        success: function (res) {
            $btn.prop("disabled", false).html('<i class="fa fa-trash me-1"></i> <span id="lbl_btn_remove_text">' + originalText + '</span>');
            if (res.status === "success") {
                const modalEl = document.getElementById("removeStockOrderModal");
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }

                Swal.fire({
                    icon: "success",
                    title: "Removed & Processed!",
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
                    text: res.message || "Could not remove production stock order."
                });
            }
        },
        error: function (xhr) {
            $btn.prop("disabled", false).html('<i class="fa fa-trash me-1"></i> <span id="lbl_btn_remove_text">' + originalText + '</span>');
            Swal.fire({
                icon: "error",
                title: "Server Error",
                text: "Network or server error occurred while removing order."
            });
        }
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

    const stockOrderNo = $(this).data("stockorderno") || "";
    const productId    = $(this).data("productid") || "";
    const productName  = $(this).data("productname") || "";
    const seedName     = $(this).data("seedname") || "";
    const sowingDate   = $(this).data("sowingdate") || "";
    const deliveryDate = $(this).data("expecteddeliverydate") || "";
    const orderQty     = $(this).data("orderqty") || 0;
    const clientName   = $(this).data("clientname") || "";
    const clientId     = $(this).data("clientid") || "";
    const psono        = $(this).data("psono") || "";

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

    // Disable sowing dates before today and set default sowing date to today
    $("#edit_new_sowing_date").attr("min", todayStr).val(todayStr);
    $("#edit_new_delivery_date").val("");
    $("#edit_product_remark").val("").removeClass("is-invalid");
    $("#edit_product_remark_err").hide();
    $("#edit_new_product_preview").hide();

    // Reset input search and autocomplete list
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

    const $btn = $(this);
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




