/*$(document).on("input", ".ratecal", function (event) {
    // Get the current row
    var row = $(this).closest("tr");

    // Helper function to parse values safely
    function getCellValue(value) {
        return parseFloat(value) || 0; // Convert to float or return 0 if invalid
    }

    // Retrieve values from the row
    var mrp = getCellValue(row.find("input.mrp_td").val());
    var qty = getCellValue(row.find("input.qty_td").val());
    var rate = getCellValue(row.find("input.rate_td").val());
    var cgstPercent = getCellValue(row.find("input.cgstprt_td").val());
    var sgstPercent = getCellValue(row.find("input.sgstprt_td").val());
    var igstPercent = getCellValue(row.find("input.igstprt_td").val());
    var discountPercent = getCellValue(row.find("input.distprt_td").val());

    // Calculate total MRP and total rate
    var totalMRP = (mrp * qty).toFixed(2);
    var totalRate = (rate * qty).toFixed(2);

    // Calculate discount amount
    var discountAmount = (totalRate * discountPercent / 100).toFixed(2);

    // Calculate taxable amount (after discount)
    var taxableAmount = (totalRate - discountAmount).toFixed(2);

    // Calculate CGST, SGST, and IGST amounts
    var cgstAmount = (taxableAmount * cgstPercent / 100).toFixed(2);
    var sgstAmount = (taxableAmount * sgstPercent / 100).toFixed(2);
    var igstAmount = (taxableAmount * igstPercent / 100).toFixed(2);

    // Update the respective input fields
    row.find("input.ttlmrp_td").val(totalMRP); // Optional: Add if needed for total MRP
    row.find("input.distamt_td").val(discountAmount);
    row.find("input.txableamount_td").val(taxableAmount);
    row.find("input.cgstamt_td").val(cgstAmount);
    row.find("input.sgstamt_td").val(sgstAmount);
    row.find("input.igstamt_td").val(igstAmount);

    // Final amount (inclusive of taxes)
    var finalAmount = (parseFloat(taxableAmount) + parseFloat(cgstAmount) + parseFloat(sgstAmount) + parseFloat(igstAmount)).toFixed(2);
    row.find("input.famount_td").val(finalAmount);

    // Call a function to update the grand total (optional)
    grandTotalCalculation();
});*/

// Function to calculate grand total
function grandTotalCalculation() {
    // Retrieve all rows and calculate the grand totals
    var totalAmount = 0,
        totalCGST = 0,
        totalSGST = 0,
        totalIGST = 0,
        totalDiscount = 0;

    $("#invoicedetailstbl tbody tr").each(function () {
        var row = $(this);
        totalAmount += parseFloat(row.find("input.famount_td").val()) || 0;
        totalCGST += parseFloat(row.find("input.cgstamt_td").val()) || 0;
        totalSGST += parseFloat(row.find("input.sgstamt_td").val()) || 0;
        totalIGST += parseFloat(row.find("input.igstamt_td").val()) || 0;
        totalDiscount += parseFloat(row.find("input.distamt_td").val()) || 0;
    });

    // Update the summary fields
    $("#totalamount").text(totalAmount.toFixed(2));
    $("#gsttotalamount").text((totalCGST + totalSGST + totalIGST).toFixed(2));
    $("#ttldiscountamount").text(totalDiscount.toFixed(2));
    $("#grandtotalamount").text(totalAmount.toFixed(2));
}


/*
//Start GST Calculation as per Rate
$(document).on("input", ".ratecal", function(event){
    event.target;
    var row = $(this).closest("tr");
    var mrp = getCellValue(row.find("input.mrp_td").val());
    var qty = getCellValue(row.find("input.qty_td").val());
    var cgstprt = getCellValue(row.find("input.cgstprt_td").val());
    var sgstprt = getCellValue(row.find("input.sgstprt_td").val());
    var igstprt = getCellValue(row.find("input.igstprt_td").val());
    var distprt = getCellValue(row.find("input.distprt_td").val());
    var ttlmrp = (parseFloat(mrp)*parseFloat(qty)).toFixed(2);
    var rate = getCellValue(row.find("input.rate_td").val());
    var ttlrate = (parseFloat(rate)*parseFloat(qty)).toFixed(2);
    //discount percent and amount calculation
  var distamount = (getCellValue(ttlrate)*getCellValue(row.find("input.distprt_td").val())/100).toFixed(2);
    getCellValue(row.find("input.distamt_td").val(distamount));
    var ttltaxableamount =parseFloat(ttlrate) - parseFloat(distamount);
  
  //cgst percent and amount calculation
  var cgstamt = (ttltaxableamount*getCellValue(row.find("input.cgstprt_td").val())/100).toFixed(2);
    getCellValue(row.find("input.cgstamt_td").val(cgstamt));
  //sgst percent and amount calculation
  var sgstamt = (ttltaxableamount*getCellValue(row.find("input.sgstprt_td").val())/100).toFixed(2);
    getCellValue(row.find("input.sgstamt_td").val(sgstamt));
  //igst percent and amount calculation
  var igstamt = (ttltaxableamount*getCellValue(row.find("input.igstprt_td").val())/100).toFixed(2);
    getCellValue(row.find("input.igstamt_td").val(igstamt));

  row.find("input.txableamount_td").val(ttltaxableamount);
  row.find("input.famount_td").val(ttltaxableamount);
  // totalamount('totalamount','famount_td','invoicedetailstbl');
  grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','discounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td','invoicedetailstbl');
});

$(document).on("input", ".qtycal", function(event){
    event.target;
    var row = $(this).closest("tr");
    var mrp = getCellValue(row.find("input.mrp_td").val());
    var qty = getCellValue(row.find("input.qty_td").val());
    var cgstprt = getCellValue(row.find("input.cgstprt_td").val());
    var sgstprt = getCellValue(row.find("input.sgstprt_td").val());
    var igstprt = getCellValue(row.find("input.igstprt_td").val());
    var distprt = getCellValue(row.find("input.distprt_td").val());
    var ttlmrp = (parseFloat(mrp)*parseFloat(qty)).toFixed(2);
    var rate = getCellValue(row.find("input.rate_td").val());
    var ttlrate = (parseFloat(rate)*parseFloat(qty)).toFixed(2);
    
    //discount percent and amount calculation
  var distamount = (getCellValue(ttlrate)*getCellValue(row.find("input.distprt_td").val())/100).toFixed(2);
    getCellValue(row.find("input.distamt_td").val(distamount));
    var ttltaxableamount =parseFloat(ttlrate) -parseFloat(distamount);

  //cgst percent and amount calculation
  var cgstamt = (ttltaxableamount*getCellValue(row.find("input.cgstprt_td").val())/100).toFixed(2);
    getCellValue(row.find("input.cgstamt_td").val(cgstamt));
  //sgst percent and amount calculation
  var sgstamt = (ttltaxableamount*getCellValue(row.find("input.sgstprt_td").val())/100).toFixed(2);
    getCellValue(row.find("input.sgstamt_td").val(sgstamt));
  //igst percent and amount calculation
  var igstamt = (ttltaxableamount*getCellValue(row.find("input.igstprt_td").val())/100).toFixed(2);
    getCellValue(row.find("input.igstamt_td").val(igstamt));

  row.find("input.txableamount_td").val(ttltaxableamount);
  row.find("input.famount_td").val(ttltaxableamount);
  // totalamount('totalamount','famount_td','invoicedetailstbl');
  grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','discounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td','invoicedetailstbl');
});

$(document).on("input", ".cgstcal", function(event){
    event.target;
    var row = $(this).closest("tr");
    var mrp = getCellValue(row.find("input.mrp_td").val());
    var qty = getCellValue(row.find("input.qty_td").val());
    var ttlmrp = (parseFloat(mrp)*parseFloat(qty)).toFixed(2);
    var rate = getCellValue(row.find("input.rate_td").val());
    var ttlrate = (parseFloat(rate)*parseFloat(qty)).toFixed(2);
    //discount percent and amount calculation
    var distamount =getCellValue(row.find("input.distamt_td").val());
  var distprct =getCellValue(row.find("input.distamt_td").val()); 

  var ttltaxableamount =parseFloat(ttlrate) -parseFloat(distamount);
  row.find("input.txableamount_td").val(ttltaxableamount);
  //cgst percent and amount calculation
  var cgstamt =0.00;
  var sgstamt =0.00;
  var igstamt =0.00;
  var cgstprct =0;
  var sgstprct =0;
  var igstprct =0;
  if($(this).hasClass('cgstprt_td')){
    if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
        row.find("input.cgstprt_td").val(0);
    }
    cgstamt = (getCellValue(row.find("input.txableamount_td").val())*getCellValue(row.find("input.cgstprt_td").val()||0)/100).toFixed(2);
    getCellValue(row.find("input.cgstamt_td").val(cgstamt));
    
    getCellValue(row.find("input.sgstamt_td").val(cgstamt));
    getCellValue(row.find("input.sgstprt_td").val(getCellValue(row.find("input.cgstprt_td").val()||0)));
        
        getCellValue(row.find("input.igstprt_td").val(0));
    getCellValue(row.find("input.igstamt_td").val('0.00'));
  }

  if($(this).hasClass('cgstamt_td')){
    if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
        row.find("input.cgstamt_td").val('0.00');
    }
    cgstprct = (100*getCellValue(row.find("input.cgstamt_td").val()||0)/getCellValue(row.find("input.txableamount_td").val())).toFixed(2);
    getCellValue(row.find("input.cgstprt_td").val(cgstprct));

    getCellValue(row.find("input.sgstprt_td").val(cgstprct));
    getCellValue(row.find("input.sgstamt_td").val(getCellValue(row.find("input.cgstamt_td").val()||0)));

    getCellValue(row.find("input.igstprt_td").val('0'));
    getCellValue(row.find("input.igstamt_td").val('0.00'));
  }
  
  row.find("input.famount_td").val(ttltaxableamount);
  grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','discounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td','invoicedetailstbl');
});

$(document).on("input", ".sgstcal", function(event){
    event.target;
    var row = $(this).closest("tr");
    var mrp = getCellValue(row.find("input.mrp_td").val());
    var qty = getCellValue(row.find("input.qty_td").val());
    var ttlmrp = (parseFloat(mrp)*parseFloat(qty)).toFixed(2);
    var rate = getCellValue(row.find("input.rate_td").val());
    var ttlrate = (parseFloat(rate)*parseFloat(qty)).toFixed(2);
    //discount percent and amount calculation
    var distamount =getCellValue(row.find("input.distamt_td").val());
  var distprct =getCellValue(row.find("input.distamt_td").val()); 

  var ttltaxableamount =parseFloat(ttlrate) -parseFloat(distamount);
  row.find("input.txableamount_td").val(ttltaxableamount);
  //cgst percent and amount calculation
  var cgstamt =0.00;
  var sgstamt =0.00;
  var igstamt =0.00;
  var cgstprct =0;
  var sgstprct =0;
  var igstprct =0;
  if($(this).hasClass('sgstprt_td')){
    if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
        row.find("input.sgstprt_td").val(0);
    }
    sgstamt = (getCellValue(row.find("input.txableamount_td").val())*getCellValue(row.find("input.sgstprt_td").val()||0)/100).toFixed(2);
    getCellValue(row.find("input.sgstamt_td").val(sgstamt));    
    getCellValue(row.find("input.cgstamt_td").val(sgstamt));

    getCellValue(row.find("input.cgstprt_td").val(getCellValue(row.find("input.sgstprt_td").val())));
        
        getCellValue(row.find("input.igstprt_td").val(0));
    getCellValue(row.find("input.igstamt_td").val('0.00'));
  }

  if($(this).hasClass('sgstamt_td')){
    if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
        row.find("input.sgstamt_td").val('0.00');
    }
    sgstprct = (100*getCellValue(row.find("input.sgstamt_td").val()||'0.00')/getCellValue(row.find("input.txableamount_td").val())).toFixed(2);
    getCellValue(row.find("input.sgstprt_td").val(sgstprct));

    getCellValue(row.find("input.cgstprt_td").val(sgstprct));
    getCellValue(row.find("input.cgstamt_td").val(getCellValue(row.find("input.sgstamt_td").val())));

    getCellValue(row.find("input.igstprt_td").val('0'));
    getCellValue(row.find("input.igstamt_td").val('0.00'));
  }
  
  row.find("input.famount_td").val(ttltaxableamount);
  grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','discounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td','invoicedetailstbl');
});

$(document).on("input", ".igstcal", function(event){
    event.target;
    var row = $(this).closest("tr");
    var mrp = getCellValue(row.find("input.mrp_td").val());
    var qty = getCellValue(row.find("input.qty_td").val());
    var ttlmrp = (parseFloat(mrp)*parseFloat(qty)).toFixed(2);
    var rate = getCellValue(row.find("input.rate_td").val());
    var ttlrate = (parseFloat(rate)*parseFloat(qty)).toFixed(2);
    //discount percent and amount calculation
    var distamount =getCellValue(row.find("input.distamt_td").val());
  var distprct =getCellValue(row.find("input.distamt_td").val()); 

  var ttltaxableamount =parseFloat(ttlrate) -parseFloat(distamount);
  row.find("input.txableamount_td").val(ttltaxableamount);
  //cgst percent and amount calculation
  var cgstamt =0.00;
  var sgstamt =0.00;
  var igstamt =0.00;
  var cgstprct =0;
  var sgstprct =0;
  var igstprct =0;
  if($(this).hasClass('igstprt_td')){
    if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
        row.find("input.igstprt_td").val(0);
    }
    igstamt = (getCellValue(row.find("input.txableamount_td").val())*getCellValue(row.find("input.igstprt_td").val()||0)/100).toFixed(2);
    getCellValue(row.find("input.igstamt_td").val(igstamt));
    
    getCellValue(row.find("input.cgstprt_td").val('0'));
    getCellValue(row.find("input.cgstamt_td").val('0.00'));
    getCellValue(row.find("input.sgstprt_td").val('0'));
    getCellValue(row.find("input.sgstamt_td").val('0.00'));
  }

  if($(this).hasClass('igstamt_td')){
    if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
        row.find("input.igstamt_td").val('0.00');
    }
    igstprct = (100*getCellValue(row.find("input.igstamt_td").val())/getCellValue(row.find("input.txableamount_td").val()||'0.00')).toFixed(2);
    getCellValue(row.find("input.igstprt_td").val(igstprct));
    getCellValue(row.find("input.cgstprt_td").val('0'));
    getCellValue(row.find("input.cgstamt_td").val('0.00'));
    getCellValue(row.find("input.sgstprt_td").val('0'));
    getCellValue(row.find("input.sgstamt_td").val('0.00'));
  }
  
  row.find("input.famount_td").val(ttltaxableamount);
  grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','otherdiscounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td','invoicedetailstbl');
});

$(document).on("input", ".distcal", function(event){
    event.target;
    var row = $(this).closest("tr");
    var mrp = getCellValue(row.find("input.mrp_td").val());
    var qty = getCellValue(row.find("input.qty_td").val());
    var cgstprt = getCellValue(row.find("input.cgstprt_td").val());
    var sgstprt = getCellValue(row.find("input.sgstprt_td").val());
    var igstprt = getCellValue(row.find("input.igstprt_td").val());
    var distprt = getCellValue(row.find("input.distprt_td").val());
    var ttlmrp = (parseFloat(mrp)*parseFloat(qty)).toFixed(2);
    var rate = getCellValue(row.find("input.rate_td").val());
    var ttlrate = (parseFloat(rate)*parseFloat(qty)).toFixed(2);
    var ttltaxableamount =parseFloat(ttlrate) -parseFloat(getCellValue(row.find("input.distamt_td").val()));
  row.find("input.txableamount_td").val(ttltaxableamount);

    //discount percent and amount calculation
  if($(this).hasClass('distprt_td')){
    if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
        row.find("input.distprt_td").val(0);
    }
    var distamount = (getCellValue(ttlrate)*getCellValue(row.find("input.distprt_td").val()||'0.00')/100).toFixed(2);
    getCellValue(row.find("input.distamt_td").val(distamount));
  }
  if($(this).hasClass('distamt_td')){
    if($(this).val()=="" || $(this).val()=="undefined" || $(this).val()=="null"){
        row.find("input.distprt_td").val('0.00');
    }
    var distprct = (100*getCellValue(row.find("input.distamt_td").val()||0)/getCellValue(ttlrate)).toFixed(2);
    getCellValue(row.find("input.distprt_td").val(distprct));
    // $(".invrowcell").trigger("input");
  }


  getCellValue(row.find("input.cgstamt_td").val((getCellValue(row.find("input.txableamount_td").val())*getCellValue(row.find("input.cgstprt_td").val())/100).toFixed(2)));

  getCellValue(row.find("input.sgstamt_td").val((getCellValue(row.find("input.txableamount_td").val())*getCellValue(row.find("input.sgstprt_td").val())/100).toFixed(2)));
  getCellValue(row.find("input.igstamt_td").val((getCellValue(row.find("input.txableamount_td").val())*getCellValue(row.find("input.igstprt_td").val())/100).toFixed(2)));
  
  row.find("input.famount_td").val(ttltaxableamount);
  grandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','otherdiscounttotalamount','grandtotalamount','famount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td','invoicedetailstbl');
});
//End GST calculation as per Rate
*/