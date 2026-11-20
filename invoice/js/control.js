$(document).on("change",".closmissingmod",function(){
    $("#missingSchedule").modal("hide");
    getcustomer();
});

$(document).on("change", "#clientid", function () {
    const client_id= $("#clientid").val();
    if (!client_id){
        alert("You have not choose client name");
        return false;
    }
   getorderno(client_id); 
});
$(document).on("change", "#orderno", function () {
    const client_id= $("#clientid").val();
    const psono= $("#orderno").val();
    const sono = $("#orderno option:selected").text();
    if (!client_id){
        alert("You have not choose client name");
        return false;
    }else if (!psono){
        alert("You have not choose order no");
        return false;
    }
    getproductdetailsforinv(client_id,psono,sono); 
}); 
$("#tblproductdetail").on("keypress", ".editable-int", function (e) {
    const char = String.fromCharCode(e.which);

    if (!/[0-9]/.test(char)) {
        e.preventDefault();
    }
});

// Decimal (0–9 and only one dot)
$("#tblproductdetail").on("keypress", ".editable-decimal", function (e) {
    const char = String.fromCharCode(e.which);

    if (char === ".") {
        if ($(this).text().includes(".")) {
            e.preventDefault(); // block second dot
        }
    } else if (!/[0-9]/.test(char)) {
        e.preventDefault();
    }
});
$("#tblproductdetail").on("blur", ".editable-int", function () {
    const val = $(this).text().replace(/\D/g, "");
    $(this).text(val);
});

$("#tblproductdetail").on("blur", ".editable-decimal", function () {
    let val = $(this).text().replace(/[^0-9.]/g, "");

    const parts = val.split(".");
    if (parts.length > 2) {
        val = parts[0] + "." + parts.slice(1).join("");
    }

    $(this).text(val);
});

$("#tblproductdetail").on("input", ".sellqty, .rate", function () {
    const row = $(this).closest("tr");
    recalcRow(row, "per"); // default recalculation
     recalcFooterIfVisible();
}); 


$("#tblproductdetail").on("input", ".discountper", function () {

    const row = $(this).closest("tr");

    let discPer = getNum($(this).text());

    // cap percentage
    if (discPer > 100) {
        discPer = 100;
        $(this).text("100");
    }

    if (discPer < 0) {
        discPer = 0;
        $(this).text("0");
    }

    recalcRow(row, "per");
    recalcFooterIfVisible();
});
$("#tblproductdetail").on("input", ".discountamt", function () {

    const row = $(this).closest("tr");

    let discAmt = getNum($(this).text());
    const taxable = getNum(row.find(".taxableamt").text());

    // ❗ discount amount cannot exceed taxable
    if (discAmt > taxable) {
        discAmt = taxable;
        $(this).text(taxable > 0 ? taxable.toFixed(2) : "");
    }

    if (discAmt < 0) {
        discAmt = 0;
        $(this).text("");
    }

    recalcRow(row, "amt");
    recalcFooterIfVisible();
});


/*$("#tblproductdetail").on("input", ".discountamt", function () {
    const row = $(this).closest("tr");
    recalcRow(row, "amt");
     recalcFooterIfVisible();
}); */
$("#btnnext").on("click", function () {
    let totalSellQty = 0;
    let totalTaxable = 0;
    let totalDiscount = 0;
    let totalPayable = 0;
    const checkedRows = $(".chkorder:checked");
    if (checkedRows.length === 0) {
        alert("Please select at least one product.");
        return;
    }

    $("#tblproductdetail tbody tr").each(function () {
        const row = $(this);
        const chk = row.find(".chkorder");
        if (chk.is(":checked")) {
            row.show();
            // 3️⃣ Sum values (contenteditable safe)
            totalSellQty += getNum(row.find(".sellqty").text());
            totalTaxable += getNum(row.find(".taxableamt").text());
            totalDiscount += getNum(row.find(".discountamt").text());
            totalPayable += getNum(row.find(".payableamt").text());

        } else {
            row.hide();
        }
    });
    $("#productdetail").addClass("step-active");
    $(".ft-sellqty").text(totalSellQty || "");
    $(".ft-taxable").text(totalTaxable ? totalTaxable.toFixed(2) : "");
    $(".ft-discount").text(totalDiscount ? totalDiscount.toFixed(2) : "");
    $(".ft-payable").text(totalPayable ? totalPayable.toFixed(2) : "");

    $("#tblproductdetail tfoot").removeClass("d-none");
    $("#btnnext").addClass("d-none");
    $(".hidebtn").removeClass("d-none");
    $("#stepHeader").removeClass("d-none");
    $(".mainheading").addClass("d-none"); // Hide header when moving to Step 2
});
$("#btnback").on("click", function () {
     resetToBackState();
});


$("#tblproductdetail").on("change", ".chkorder", function () {

    const $table = $(this).closest("table");
    const $tfoot = $table.find("tfoot");

    if ($tfoot.hasClass("d-none")) return;

    const checkedCount = $table.find("tbody .chkorder:checked").length;

    console.log("Checked Count:", checkedCount);

    if (checkedCount === 0) {
        $("#invactionbtndiv").hide();
        resetToBackState();
        return;
    }

    $("#invactionbtndiv").show();

    $table.find("tbody tr").hide();
    $table.find(".chkorder:checked").closest("tr").show();

    
    $table.find(".chkorder:checked").closest("tr").data("freightamount");
    $table.find(".chkorder:checked").closest("tr").data("freightdiscountpercent");
    

    $("#editinv_freightcharges").val($table.find(".chkorder:checked").closest("tr").data("payablefreightamount"));

    $("#editinv_deliveraddress").val($table.find(".chkorder:checked").closest("tr").data("deliveryaddress"));
    $("#editnv_drivername").val($table.find(".chkorder:checked").closest("tr").data("drivername"));
    $("#editnv_drivercontactno").val($table.find(".chkorder:checked").closest("tr").data("drivercontactno"));
    $("#editinv_vehicleno").val($table.find(".chkorder:checked").closest("tr").data("vehicleno"));
    recalcFooterIfVisible();
});


$(document).on("click", ".backpreview", function () {
    $(".productdetail").removeClass("d-none");
    $("#previewinvoice").addClass("d-none");
    $(".mainheading").removeClass("d-none"); 
    if ($("#generatefinal_inv").hasClass("generatedinvoice")) {
        getcustomer();
    }
});
$(document).on("click", "#btncreateinve", function () {
    const client_id = $("#clientid").val();
    const psono     = $("#orderno").val();
    const sono      = $("#orderno option:selected").text();
    const freightcharges     = $("#editinv_freightcharges").val();
    const vehicleno     = $("#editinv_vehicleno").val();
    const distancekm     = $("#editinv_distancekm").val();
    const drivername = $("#editnv_drivername").val();
    const drivercontactno = $("#editnv_drivercontactno").val();

    if (!client_id) {
        alert("You have not chosen client name");
        return;
    }
    if (!psono) {
        alert("You have not chosen order no");
        return;
    }

    const $opt = $("#clientid option:selected");
    $("#ctcperson").val($opt.text());
    $("#ctcno").val($opt.data("phones"));
    $("#email_id").val($opt.data("clientemail"));
    $("#inv_add").val($opt.data("invoicingaddress"));
debugger;
    datasave("tempinv",client_id,psono,sono,drivername,drivercontactno,vehicleno,freightcharges) ;

});
$(document).on("click", "#regenerate", function () {
    const client_id = $("#clientid").val();
    const psono     = $("#orderno").val();
    const freightcharges     = $("#editinv_freightcharges").val();
    const vehicleno     = $("#editinv_vehicleno").val();
    const distancekm     = $("#editinv_distancekm").val();
    const drivername = $("#editnv_drivername").val();
    const drivercontactno = $("#editnv_drivercontactno").val();
    
    
    const sono      = $("#orderno option:selected").text();
    if (!client_id) {
        alert("You have not chosen client name");
        return;
    }
    if (!psono) {
        alert("You have not chosen order no");
        return;
    }
   
    datasave("tempinv",client_id,psono,sono,drivername,drivercontactno,vehicleno,freightcharges);

});
$(document).on("click", "#generatefinal_inv", function () {
    const client_id = $("#clientid").val();
    const psono     = $("#orderno").val();
    const sono      = $("#orderno option:selected").text();
    const freightcharges     = $("#editinv_freightcharges").val();
    const vehicleno     = $("#editinv_vehicleno").val();
    const distancekm     = $("#editinv_distancekm").val();
    const drivername = $("#editnv_drivername").val();
    const drivercontactno = $("#editnv_drivercontactno").val();
    
    if (!client_id) {
        alert("You have not chosen client name");
        return;
    }
    if (!psono) {
        alert("You have not chosen order no");
        return;
    }
   
    datasave("finalinvoice",client_id,psono,sono,drivername,drivercontactno,vehicleno,freightcharges) ;

});

$(document).on("click", "#showinvoice", function () {
    $(this).addClass("active").siblings().removeClass("active");
    fileParam=$("#generatefinal_inv").data("invoicepath")||"" ;

        $("#displaypreview").load("../preview.php?f=" + fileParam);
});   
 
$(document).on("click", "#showdeliverynote", function () {
    $(this).addClass("active").siblings().removeClass("active");
    fileParam=$("#generatefinal_inv").data("deliverynote")||"" 

    $("#displaypreview").load("../preview.php?f=" + fileParam);
});   



