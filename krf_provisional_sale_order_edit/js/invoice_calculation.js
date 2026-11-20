function updateRowPayableAndRoundoff(row, unroundedPayable) {
    var rawPayable = parseFloat(unroundedPayable) || 0;
    var roundedPayable = Math.round(rawPayable);
    var roundOffAmt = (roundedPayable - rawPayable).toFixed(2);
    row.find("input.payableamount_td").val(roundedPayable.toFixed(2));
    if (row.find("input.roundoffamount_td").length > 0) {
        row.find("input.roundoffamount_td").val(roundOffAmt);
    }
    return roundedPayable;
}
window.updateRowPayableAndRoundoff = updateRowPayableAndRoundoff;

//Start GST Calculation as per Rate
$(document).on("input", ".ratecal", function(event){
    var tableId = $(this).closest('table').attr('id'); // Get the ID of the closest table
    var row = $(this).closest("tr");

    // Get values
    var qty      = parseFloat(row.find("input.qty_td").val())      || 0;
    var rate     = parseFloat(row.find("input.rate_td").val())     || 0;
    var cgstprt  = parseFloat(row.find("input.cgstprt_td").val())  || 0;
    var sgstprt  = parseFloat(row.find("input.sgstprt_td").val())  || 0;
    var igstprt  = parseFloat(row.find("input.igstprt_td").val())  || 0;
    var distprt  = parseFloat(row.find("input.distprt_td").val())  || 0;

    // Total rate before discount
    var ttlrate = (rate * qty).toFixed(2);

    // Discount calculation
    var distamount = ((ttlrate * distprt) / 100).toFixed(2);
    row.find("input.distamt_td").val(distamount);

    // Taxable amount calculation (exclusive tax assumed)
    var taxRate = cgstprt + sgstprt + igstprt;
    var ttltaxableamount = calculateTaxableAmount(ttlrate, taxRate, false, distamount); 
    
    // Tax amounts
    var cgstamt = ((ttltaxableamount * cgstprt) / 100).toFixed(2);
    var sgstamt = ((ttltaxableamount * sgstprt) / 100).toFixed(2);
    var igstamt = ((ttltaxableamount * igstprt) / 100).toFixed(2);

    // Set tax values
    row.find("input.cgstamt_td").val(cgstamt);
    row.find("input.sgstamt_td").val(sgstamt);
    row.find("input.igstamt_td").val(igstamt);

    // Set totals
    row.find("input.txableamount_td").val(ttltaxableamount.toFixed(2));
    row.find("input.total_amount_td").val(ttlrate);

    updateRowPayableAndRoundoff(row, parseFloat(ttlrate) - parseFloat(distamount));

    // Update grand totals
    Invoicegrandtotalgstamont(
        'totalamount',
        'gsttotalamount',
        'ttldiscountamount',
        'grandtotalamount',
        'payableamount_td',
        'cgstamt_td',
        'sgstamt_td',
        'igstamt_td',
        'distamt_td',
        tableId
    );
});

$(document).on("input", ".qtycal", function () {
    var tableId = $(this).closest('table').attr('id'); // Get the ID of the closest table
    var row = $(this).closest("tr");

    // Get values safely
    var qty      = parseFloat(row.find("input.qty_td").val())   || 0;
    var cgstprt  = parseFloat(row.find("input.cgstprt_td").val()) || 0;
    var sgstprt  = parseFloat(row.find("input.sgstprt_td").val()) || 0;
    var igstprt  = parseFloat(row.find("input.igstprt_td").val()) || 0;
    var distprt  = parseFloat(row.find("input.distprt_td").val()) || 0;
    var rate     = parseFloat(row.find("input.rate_td").val())    || 0;

    // Calculate trays and bonus plants (125 billed per 126 tray, 1 free plant/tray)
    if (qty > 0) {
        var trays = Math.ceil(qty / 125);
        var bonusPlants = trays;
        var physicalPlants = trays * 126;
        row.find(".tray_info_hint").html(
            '<span class="text-success"><i class="fa fa-boxes me-1"></i><b>' + trays.toLocaleString() + ' Trays</b></span>' +
            '<span class="text-muted d-block" style="font-size:9.5px;">(' + Number(qty).toLocaleString() + ' Billed + ' + bonusPlants.toLocaleString() + ' Free = ' + physicalPlants.toLocaleString() + ' Total)</span>'
        );
    } else {
        row.find(".tray_info_hint").empty();
    }

    // Total rate before discount/taxes
    var ttlrate = (rate * qty).toFixed(2);

    // Discount amount
    var distamount = ((ttlrate * distprt) / 100).toFixed(2);
    row.find("input.distamt_td").val(distamount);

    // Taxable amount (after discount, before tax)
    var taxRate = cgstprt + sgstprt + igstprt;
    var ttltaxableamount = calculateTaxableAmount(ttlrate, taxRate, true, distamount);

    // GST Calculations
    var cgstamt = ((ttltaxableamount * cgstprt) / 100).toFixed(2);
    row.find("input.cgstamt_td").val(cgstamt);

    var sgstamt = ((ttltaxableamount * sgstprt) / 100).toFixed(2);
    row.find("input.sgstamt_td").val(sgstamt);

    var igstamt = ((ttltaxableamount * igstprt) / 100).toFixed(2);
    row.find("input.igstamt_td").val(igstamt);

    // Final values
    row.find("input.total_amount_td").val(ttlrate);
    row.find("input.txableamount_td").val(ttltaxableamount);
    updateRowPayableAndRoundoff(row, parseFloat(ttlrate) - parseFloat(distamount));

    // Update grand totals
    Invoicegrandtotalgstamont(
        'totalamount',
        'gsttotalamount',
        'ttldiscountamount',
        'grandtotalamount',
        'payableamount_td',
        'cgstamt_td',
        'sgstamt_td',
        'igstamt_td',
        'distamt_td',
        tableId
    );
});


// $(document).on("input", ".cgstcal", function () {
//     var tableId = $(this).closest('table').attr('id'); 
//     var row = $(this).closest("tr");

//     // Base values
//     var mrp   = parseFloat(row.find("input.mrp_td").val())   || 0;
//     var qty   = parseFloat(row.find("input.qty_td").val())   || 0;
//     var rate  = parseFloat(row.find("input.rate_td").val())  || 0;
//     var ttlrate = (rate * qty).toFixed(2);

//     // Discount
//     var distamount = parseFloat(row.find("input.distamt_td").val()) || 0;
//     var distprct   = parseFloat(row.find("input.distprt_td").val()) || 0;

//     // Tax percentages
//     var cgstprt = parseFloat(row.find("input.cgstprt_td").val()) || 0;
//     var sgstprt = parseFloat(row.find("input.sgstprt_td").val()) || 0;
//     var igstprt = parseFloat(row.find("input.igstprt_td").val()) || 0;

//     var taxRate = cgstprt + sgstprt + igstprt;
//     var ttltaxableamount = calculateTaxableAmount(ttlrate, taxRate, true, distamount);
//     row.find("input.txableamount_td").val(ttltaxableamount);

//     var cgstamt = 0.00, sgstamt = 0.00, igstamt = 0.00;
//     var cgstprct = 0, sgstprct = 0, igstprct = 0;

//     // Case 1: User edited % field
//     if ($(this).hasClass('cgstprt_td')) {
//         var cgstval = parseFloat($(this).val()) || 0;
//         row.find("input.cgstprt_td").val(cgstval);

//         cgstamt = ((ttltaxableamount * cgstval) / 100).toFixed(2);
//         row.find("input.cgstamt_td").val(cgstamt);

//         // Mirror to SGST
//         row.find("input.sgstprt_td").val(cgstval);
//         row.find("input.sgstamt_td").val(cgstamt);

//         // Reset IGST
//         row.find("input.igstprt_td").val(0);
//         row.find("input.igstamt_td").val("0.00");
//     }

//     // Case 2: User edited amount field
//     if ($(this).hasClass('cgstamt_td')) {
//         var cgstvalAmt = parseFloat($(this).val()) || 0;
//         row.find("input.cgstamt_td").val(cgstvalAmt.toFixed(2));

//         cgstprct = ((cgstvalAmt * 100) / (ttltaxableamount || 1)).toFixed(2);
//         row.find("input.cgstprt_td").val(cgstprct);

//         // Mirror SGST
//         row.find("input.sgstprt_td").val(cgstprct);
//         row.find("input.sgstamt_td").val(cgstvalAmt.toFixed(2));

//         // Reset IGST
//         row.find("input.igstprt_td").val(0);
//         row.find("input.igstamt_td").val("0.00");
//     }

//     row.find("input.payableamount_td").val(ttltaxableamount);

//     // Update totals
//     Invoicegrandtotalgstamont(
//         'totalamount',
//         'gsttotalamount',
//         'ttldiscountamount',
//         'grandtotalamount',
//         'payableamount_td',
//         'cgstamt_td',
//         'sgstamt_td',
//         'igstamt_td',
//         'distamt_td',
//         tableId
//     );
// });

$(document).on("input", ".cgstcal", function () {
    var row     = $(this).closest("tr");
    var tableId = $(this).closest('table').attr('id');

    // Base values
    var mrp  = getTableCellValue(row.find("input.mrp_td").val());
    var qty  = getTableCellValue(row.find("input.qty_td").val());
    var rate = getTableCellValue(row.find("input.rate_td").val());

    // Total rate
    var ttlrate = (parseFloat(rate) * parseFloat(qty)).toFixed(2);

    // Discount
    var distamount = getTableCellValue(row.find("input.distamt_td").val());
    var distprct   = getTableCellValue(row.find("input.distprct_td").val());

    // Initially read tax %
    var cgstprt = parseFloat(row.find("input.cgstprt_td").val()) || 0;
    var sgstprt = parseFloat(row.find("input.sgstprt_td").val()) || 0;
    var igstprt = parseFloat(row.find("input.igstprt_td").val()) || 0;

    var inputClass = $(this).attr("class");

    /* ----------------------------------------------------------
        CASE 1: USER CHANGED CGST %
    ---------------------------------------------------------- */
    if ($(this).hasClass("cgstprt_td")) {
        cgstprt = parseFloat($(this).val()) || 0;

        // Set SGST = CGST
        row.find("input.sgstprt_td").val(cgstprt);

        // Reset IGST
        row.find("input.igstprt_td").val(0);
        igstprt = 0;
    }

    /* ----------------------------------------------------------
        CASE 2: USER CHANGED CGST AMOUNT
    ---------------------------------------------------------- */
    if ($(this).hasClass("cgstamt_td")) {

        var cgstamt = parseFloat($(this).val()) || 0;
        row.find("input.cgstamt_td").val(cgstamt.toFixed(2));

        // Calculate % from amount
        cgstprt = ((cgstamt * 100) / (ttlrate - distamount)).toFixed(2);
        row.find("input.cgstprt_td").val(cgstprt);

        // SGST mirror
        row.find("input.sgstprt_td").val(cgstprt);
        row.find("input.sgstamt_td").val(cgstamt.toFixed(2));

        // Reset IGST
        row.find("input.igstprt_td").val(0);
        row.find("input.igstamt_td").val("0.00");

        // Recalculate taxable amount
    }

    /* ----------------------------------------------------------
        RECALCULATE TAXABLE AMOUNT AFTER ALL PERCENT UPDATES
    ---------------------------------------------------------- */

    var taxRate = parseFloat(cgstprt) + parseFloat(sgstprt) + parseFloat(igstprt);
    var ttltaxableamount = calculateTaxableAmount(ttlrate, taxRate, true, distamount);
    row.find("input.txableamount_td").val(ttltaxableamount);

    /* ----------------------------------------------------------
        FINAL: CALCULATE GST AMOUNTS BASED ON UPDATED % 
    ---------------------------------------------------------- */

    var finalCgstAmt = ((ttltaxableamount * cgstprt) / 100).toFixed(2);
    var finalSgstAmt = ((ttltaxableamount * sgstprt) / 100).toFixed(2);
    var finalIgstAmt = ((ttltaxableamount * igstprt) / 100).toFixed(2);

    row.find("input.cgstamt_td").val(finalCgstAmt);
    row.find("input.sgstamt_td").val(finalSgstAmt);
    row.find("input.igstamt_td").val(finalIgstAmt);

    // PAYABLE AMOUNT = Total - Discount (Rounded)
    updateRowPayableAndRoundoff(row, parseFloat(ttlrate) - parseFloat(distamount));
    row.find("input.sgstprt_td").val(cgstprt).trigger("input");
    /* ----------------------------------------------------------
        UPDATE GRAND TOTAL FOOTER 
    ---------------------------------------------------------- */

    Invoicegrandtotalgstamont(
        'totalamount',
        'gsttotalamount',
        'ttldiscountamount',
        'grandtotalamount',
        'payableamount_td',
        'cgstamt_td',
        'sgstamt_td',
        'igstamt_td',
        'distamt_td',
        tableId
    );
});

// $(document).on("input", ".sgstcal", function () {
//     var tableId = $(this).closest('table').attr('id'); 
//     var row = $(this).closest("tr");

//     // Base values
//     var mrp   = parseFloat(row.find("input.mrp_td").val())   || 0;
//     var qty   = parseFloat(row.find("input.qty_td").val())   || 0;
//     var rate  = parseFloat(row.find("input.rate_td").val())  || 0;
//     var ttlrate = (rate * qty).toFixed(2);

//     // Discount
//     var distamount = parseFloat(row.find("input.distamt_td").val()) || 0;
//     var distprct   = parseFloat(row.find("input.distprt_td").val()) || 0;

//     // Tax percentages
//     var cgstprt = parseFloat(row.find("input.cgstprt_td").val()) || 0;
//     var sgstprt = parseFloat(row.find("input.sgstprt_td").val()) || 0;
//     var igstprt = parseFloat(row.find("input.igstprt_td").val()) || 0;

//     var taxRate = cgstprt + sgstprt + igstprt;
//     var ttltaxableamount = calculateTaxableAmount(ttlrate, taxRate, true, distamount);
//     row.find("input.txableamount_td").val(ttltaxableamount);

//     var cgstamt = 0.00, sgstamt = 0.00, igstamt = 0.00;
//     var cgstprct = 0, sgstprct = 0, igstprct = 0;

//     // Case 1: User edited SGST percentage
//     if ($(this).hasClass('sgstprt_td')) {
//         var sgstval = parseFloat($(this).val()) || 0;
//         row.find("input.sgstprt_td").val(sgstval);

//         sgstamt = ((ttltaxableamount * sgstval) / 100).toFixed(2);
//         row.find("input.sgstamt_td").val(sgstamt);

//         // Mirror CGST
//         row.find("input.cgstprt_td").val(sgstval);
//         row.find("input.cgstamt_td").val(sgstamt);

//         // Reset IGST
//         row.find("input.igstprt_td").val(0);
//         row.find("input.igstamt_td").val("0.00");
//     }

//     // Case 2: User edited SGST amount
//     if ($(this).hasClass('sgstamt_td')) {
//         var sgstvalAmt = parseFloat($(this).val()) || 0;
//         row.find("input.sgstamt_td").val(sgstvalAmt.toFixed(2));

//         sgstprct = ((sgstvalAmt * 100) / (ttltaxableamount || 1)).toFixed(2);
//         row.find("input.sgstprt_td").val(sgstprct);

//         // Mirror CGST
//         row.find("input.cgstprt_td").val(sgstprct);
//         row.find("input.cgstamt_td").val(sgstvalAmt.toFixed(2));

//         // Reset IGST
//         row.find("input.igstprt_td").val(0);
//         row.find("input.igstamt_td").val("0.00");
//     }

//     row.find("input.payableamount_td").val(ttltaxableamount);

//     // Update totals
//     Invoicegrandtotalgstamont(
//         'totalamount',
//         'gsttotalamount',
//         'ttldiscountamount',
//         'grandtotalamount',
//         'payableamount_td',
//         'cgstamt_td',
//         'sgstamt_td',
//         'igstamt_td',
//         'distamt_td',
//         tableId
//     );
// });

$(document).on("input", ".sgstcal", function () {

    var row     = $(this).closest("tr");
    var tableId = $(this).closest('table').attr('id');

    // Base fields
    var mrp  = getTableCellValue(row.find("input.mrp_td").val());
    var qty  = getTableCellValue(row.find("input.qty_td").val());
    var rate = getTableCellValue(row.find("input.rate_td").val());

    var ttlrate = (parseFloat(rate) * parseFloat(qty)).toFixed(2);

    // Discounts
    var distamount = getTableCellValue(row.find("input.distamt_td").val());
    var distprct   = getTableCellValue(row.find("input.distprct_td").val());

    // Current tax %
    var cgstprt = parseFloat(row.find("input.cgstprt_td").val()) || 0;
    var sgstprt = parseFloat(row.find("input.sgstprt_td").val()) || 0;
    var igstprt = parseFloat(row.find("input.igstprt_td").val()) || 0;

    /* ------------------------------------------------------------------
        CASE 1: SGST % EDITED
    ------------------------------------------------------------------ */
    if ($(this).hasClass("sgstprt_td")) {
        sgstprt = parseFloat($(this).val()) || 0;

        // Set SGST %
        row.find("input.sgstprt_td").val(sgstprt);

        // Mirror CGST %
        row.find("input.cgstprt_td").val(sgstprt);

        // Reset IGST
        row.find("input.igstprt_td").val(0);
        igstprt = 0;
    }

    /* ------------------------------------------------------------------
        CASE 2: SGST AMOUNT EDITED
    ------------------------------------------------------------------ */
    if ($(this).hasClass("sgstamt_td")) {

        var sgstAmt = parseFloat($(this).val()) || 0;
        row.find("input.sgstamt_td").val(sgstAmt.toFixed(2));

        // Back-calc SGST %
        sgstprt = ((sgstAmt * 100) / (ttlrate - distamount)).toFixed(2);
        row.find("input.sgstprt_td").val(sgstprt);

        // Mirror CGST % & amount
        row.find("input.cgstprt_td").val(sgstprt);
        row.find("input.cgstamt_td").val(sgstAmt.toFixed(2));

        // Reset IGST
        row.find("input.igstprt_td").val(0);
        row.find("input.igstamt_td").val("0.00");

        igstprt = 0;
    }

    /* ------------------------------------------------------------------
        RECALCULATE TAXABLE AMOUNT AFTER ALL % FINALIZED
    ------------------------------------------------------------------ */
    var taxRate = parseFloat(cgstprt) + parseFloat(sgstprt) + parseFloat(igstprt);
    var ttltaxableamount = calculateTaxableAmount(ttlrate, taxRate, true, distamount);

    row.find("input.txableamount_td").val(ttltaxableamount);


    /* ------------------------------------------------------------------
        FINAL GST AMOUNT CALCULATIONS
    ------------------------------------------------------------------ */
    var finalCgstAmt = ((ttltaxableamount * sgstprt) / 100).toFixed(2);
    var finalSgstAmt = finalCgstAmt; // same as CGST
    var finalIgstAmt = ((ttltaxableamount * igstprt) / 100).toFixed(2);

    row.find("input.cgstamt_td").val(finalCgstAmt);
    row.find("input.sgstamt_td").val(finalSgstAmt);
    row.find("input.igstamt_td").val(finalIgstAmt);

    // Payable = Total - Discount (Rounded)
    updateRowPayableAndRoundoff(row, parseFloat(ttlrate) - parseFloat(distamount));
    /* ------------------------------------------------------------------
        UPDATE FOOTER TOTALS
    ------------------------------------------------------------------ */
    Invoicegrandtotalgstamont(
        'totalamount',
        'gsttotalamount',
        'ttldiscountamount',
        'grandtotalamount',
        'payableamount_td',
        'cgstamt_td',
        'sgstamt_td',
        'igstamt_td',
        'distamt_td',
        tableId
    );
});

$(document).on("input", ".igstcal", function(event){
	var tableId = $(this).closest('table').attr('id'); // Get the ID of the closest table
	var row = $(this).closest("tr");

	// Get values safely
	var mrp  = getTableCellValue(row.find("input.mrp_td").val());
	var qty  = getTableCellValue(row.find("input.qty_td").val());
	var rate = getTableCellValue(row.find("input.rate_td").val());

	// Calculate total rate
	var ttlrate = (parseFloat(rate) * parseFloat(qty)).toFixed(2);

	// Discount percent and amount calculation
	var distamount = getTableCellValue(row.find("input.distamt_td").val());
	var distprct   = getTableCellValue(row.find("input.distprct_td").val()); // fixed wrong selector

	// Taxable amount calculation
	var taxRate = cgstprt + sgstprt + igstprt;
	var ttltaxableamount = calculateTaxableAmount(ttlrate, taxRate, true, distamount);

	// Set taxable amount
	row.find("input.txableamount_td").val(ttltaxableamount);

	// Initialize GST values
	var cgstamt = 0.00, sgstamt = 0.00, igstamt = 0.00;
	var cgstprct = 0, sgstprct = 0, igstprct = 0;

	// If IGST percent changed
	if ($(this).hasClass('igstprt_td')) {
		if ($(this).val() === "" || $(this).val() === "undefined" || $(this).val() === "null") {
			row.find("input.igstprt_td").val(0);
		}

		igstamt = (getTableCellValue(row.find("input.txableamount_td").val()) 
		          * getTableCellValue(row.find("input.igstprt_td").val() || 0) / 100).toFixed(2);

		// ✅ Correct way to set values
		row.find("input.igstamt_td").val(igstamt);

		row.find("input.cgstprt_td").val('0');
		row.find("input.cgstamt_td").val('0.00');
		row.find("input.sgstprt_td").val('0');
		row.find("input.sgstamt_td").val('0.00');
	}

	// If IGST amount changed
	if ($(this).hasClass('igstamt_td')) {
		if ($(this).val() === "" || $(this).val() === "undefined" || $(this).val() === "null") {
			row.find("input.igstamt_td").val('0.00');
		}

		igstprct = (100 * getTableCellValue(row.find("input.igstamt_td").val()) 
		           / getTableCellValue(row.find("input.txableamount_td").val() || '0.00')).toFixed(2);

		row.find("input.igstprt_td").val(igstprct);

		row.find("input.cgstprt_td").val('0');
		row.find("input.cgstamt_td").val('0.00');
		row.find("input.sgstprt_td").val('0');
		row.find("input.sgstamt_td").val('0.00');
	}

	// Final payable amount = Total - Discount (Rounded)
    updateRowPayableAndRoundoff(row, parseFloat(ttlrate) - parseFloat(distamount));

	// Recalculate totals
	Invoicegrandtotalgstamont(
		'totalamount','gsttotalamount','ttldiscountamount','grandtotalamount',
		'payableamount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td',tableId
	);
});


$(document).on("input", ".distcal", function (event) {
    var tableId = $(this).closest('table').attr('id'); // Get the ID of the closest table
    var row = $(this).closest("tr");

    var mrp = parseFloat(row.find("input.mrp_td").val()) || 0;
    var qty = parseFloat(row.find("input.qty_td").val()) || 0;
    var rate = parseFloat(row.find("input.rate_td").val()) || 0;
    var cgstprt = parseFloat(row.find("input.cgstprt_td").val()) || 0;
    var sgstprt = parseFloat(row.find("input.sgstprt_td").val()) || 0;
    var igstprt = parseFloat(row.find("input.igstprt_td").val()) || 0;
    var distprt = parseFloat(row.find("input.distprt_td").val()) || 0;
    var distamount = parseFloat(row.find("input.distamt_td").val()) || 0;

    var ttlrate = (rate * qty).toFixed(2);

    // Calculate taxable amount (assuming calculateTaxableAmount is correctly implemented elsewhere)
    var taxRate = cgstprt + sgstprt + igstprt;
    var ttltaxableamount = calculateTaxableAmount(ttlrate, taxRate, true, distamount);
    row.find("input.txableamount_td").val(ttltaxableamount.toFixed(2));

    // Discount percent and amount calculation
    if ($(this).hasClass('distprt_td')) {
        if ($(this).val() === "" || $(this).val() === "undefined" || $(this).val() === "null") {
            row.find("input.distprt_td").val('0.00');
            distprt = 0;
        }

        distamount = ((ttlrate * distprt) / 100).toFixed(2);
        row.find("input.distamt_td").val(distamount);
    }

    if ($(this).hasClass('distamt_td')) {
        if ($(this).val() === "" || $(this).val() === "undefined" || $(this).val() === "null") {
            row.find("input.distamt_td").val('0.00');
            distamount = 0;
        }

        distprt = ((100 * distamount) / ttlrate).toFixed(2);
        row.find("input.distprt_td").val(distprt);
    }

    // Calculate individual tax amounts
    var taxableAmount = parseFloat(row.find("input.txableamount_td").val()) || 0;

    var cgstamt = ((taxableAmount * cgstprt) / 100).toFixed(2);
    var sgstamt = ((taxableAmount * sgstprt) / 100).toFixed(2);
    var igstamt = ((taxableAmount * igstprt) / 100).toFixed(2);

    row.find("input.cgstamt_td").val(cgstamt);
    row.find("input.sgstamt_td").val(sgstamt);
    row.find("input.igstamt_td").val(igstamt);

    // Set payable amount (Rounded)
    updateRowPayableAndRoundoff(row, parseFloat(ttlrate) - parseFloat(distamount));

    // Update grand total
    Invoicegrandtotalgstamont(
        'totalamount',
        'gsttotalamount',
        'ttldiscountamount',
        'grandtotalamount',
        'payableamount_td',
        'cgstamt_td',
        'sgstamt_td',
        'igstamt_td',
        'distamt_td',
        tableId
    );
});


function getTableCellValue(val) {
    return val !== undefined && val !== null && val !== '' ? parseFloat(val) : 0;
}

function calculateTaxableAmount(totalAmount, taxRate, isInclusive = false, discount = 0, exemptions = 0) {
    let netAmount = totalAmount - discount - exemptions;
    let taxable, tax, grandTotal;
    if (isInclusive) {
        // Tax is already included
        taxable = netAmount / (1 + (taxRate / 100));
        tax = netAmount - taxable;
        grandTotal = netAmount;
    } else {
        // Tax is exclusive
        taxable = netAmount;
        tax = taxable * (taxRate / 100);
        grandTotal = taxable + tax;
    }
    return parseFloat(taxable.toFixed(2));
}

function Invoicegrandtotalgstamont(totalamountid, totalgstamontid, totaldiscountamount, grandttlamountid, totalamt_class, cgstamt_class, sgstamt_class, igstamt_class, discountamt_class, tableid) {
    var ttlamt = 0;
    var ttldistamt = 0;
    var ttlcgstamt = 0;
    var ttlsgstamt = 0;
    var ttligstamt = 0;
    var ttlgst = 0;
    var grandttlamt = 0;
    var ttl_before_discount = 0;

    var parentdivid = $("#" + tableid).data("parentdivid");
    $("#" + tableid + " tbody tr").each(function() {
        // Get the total amount (without discount) from the row
        var row_ttl_before = $(this).find("input.total_amount_td").val();
        ttl_before_discount += parseFloat(row_ttl_before) || 0;

        // Get the total amount (after discount/taxable) from the row
        var amt = $(this).find("input." + totalamt_class).val();
        ttlamt += parseFloat(amt) || 0; 

        // Get the discount amount from the row
        var distamt = $(this).find("input." + discountamt_class).val();
        ttldistamt += parseFloat(distamt) || 0; 

        // Get the CGST amount from the row
        var cgstamt = $(this).find("input." + cgstamt_class).val();
        ttlcgstamt += parseFloat(cgstamt) || 0; 

        // Get the SGST amount from the row
        var sgstamt = $(this).find("input." + sgstamt_class).val();
        ttlsgstamt += parseFloat(sgstamt) || 0; 

        // Get the IGST amount from the row
        var igstamt = $(this).find("input." + igstamt_class).val();
        ttligstamt += parseFloat(igstamt) || 0; 
    });

    // Calculate the total GST
    ttlgst = ttlcgstamt + ttlsgstamt + ttligstamt;

    // Calculate the grand total (Total - Discount)
    // Note: Since each row's payable is (Total - Discount), summing them up gives the final amount
    grandttlamt = ttlamt.toFixed(2);

    // Update the DOM elements
    $("#" + parentdivid + " #" + totalamountid).text(ttlamt.toFixed(2));
    $("#" + parentdivid + " #" + totalgstamontid).text(ttlgst.toFixed(2));
    $("#" + parentdivid + " #" + totaldiscountamount).text(ttldistamt.toFixed(2));
    $("#" + parentdivid + " #" + grandttlamountid).text(grandttlamt);
    
    // Custom logic for the new Total Amount field
    $("#" + parentdivid + " #grandtotal_before_discount").text(ttl_before_discount.toFixed(2));
    $("#" + parentdivid + " #grandtotalamount_final").text(grandttlamt);

    $("#" + tableid).data("ttlrows-amount", ttlamt.toFixed(2));
    $("#" + tableid).data("ttlgst-amount", ttlgst.toFixed(2));
    $("#" + tableid).data("ttldiscount-amount", ttldistamt.toFixed(2));
    $("#" + tableid).data("ttlpayable-amount", grandttlamt);
}

// jQuery function to calculate payable freight amount after discount %
function calculateFreightPayable(freightSelector, discountSelector, payableSelector) {
    $(document).on("input", freightSelector + ", " + discountSelector, function () {
        var freight = parseFloat($(freightSelector).val()) || 0;   // Freight Amount
        var discountPercent = parseFloat($(discountSelector).val()) || 0; // Discount %
        
        // Ensure discount does not exceed 100%
        if (discountPercent > 100) {
            discountPercent = 100;
            $(discountSelector).val(100);
        }
        // Calculate discount value
        var discountAmount = (freight * discountPercent) / 100;
        // Final payable amount
        var payable = freight - discountAmount;
        // Update payable field
        $(payableSelector).val(payable.toFixed(2));
    });
}