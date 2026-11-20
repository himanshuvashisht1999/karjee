$(document).on("change",".closmissingmod",function(){
    $("#missingSchedule").modal("hide");
    getcustomer_invoice();
});

$(document).on("change", "#clientid", function () {
    const client_id= $("#clientid").val();
    if (!client_id){
        alert("You have not choose client name");
        return false;
    }
   getinvoiceno_pendingdespatch(client_id); 
});
$(document).on("change", "#invoiceno", function () {
    const client_id= $("#clientid").val();
    let selectedopt = $("#invoiceno option:selected");
    const psono   = selectedopt.val();  
    const sono    = selectedopt.data("sono");        
    const invoice = selectedopt.text().trim();  
    if (!client_id){
        alert("You have not choose client name");
        return false;
    }else if (!psono){
        alert("You have not choose order no");
        return false;
    }
    getdespatchitems(client_id,psono,sono,invoice); 
}); 
 
$("#startScan").on("click", function() {
    $("#noResult").hide().text(""); 
    $("#startScan").hide();
    $("#stopScan").show();
    $("#manualQrCode").val('');  
    $("#reader").removeClass("d-none");
    initScanner();
}); 
$("#stopScan").on("click", function () { 
    $("#noResult").hide().text(""); 
    $("#startScan").show();
    $("#stopScan").hide();  
    $("#reader").addClass("d-none");
    if (qrReader && qrReader.getState && qrReader.getState() === Html5QrcodeScannerState.SCANNING) {
        qrReader.stop()
            .then(() => {
                console.log("Scanner stopped successfully");
            })
            .catch(err => {
                console.error("Stop failed:", err);
            });
    } else {
        console.warn("Scanner is not running.");
    }
}); 

$("#fetchManual").on("click", function() {
    const qrCodeValue = $("#manualQrCode").val().trim();
    getbarcode_data(qrCodeValue);
});
$("#drivermobileno").on("input", function () {
    this.value = this.value.replace(/[^0-9]/g, '');
});

$("#manualQrCode").on("input",function(){
    $("#scheduleplan").addClass('d-none');
    $("#history").addClass('d-none');
    $("#trayInfo").hide();
    $("#divdisease").addClass("d-none");
    $("#reader").addClass("d-none");
    $("#stopScan").hide();
    $("#startScan").show();
    $(".lblscan").addClass("d-none");
    $("#noResult").hide().text("");
    if (qrReader && qrReader.getState && qrReader.getState() === Html5QrcodeScannerState.SCANNING) {
        qrReader.stop()
            .then(() => {
                //console.log("Scanner stopped successfully");
            })
            .catch(err => {
                //console.error("Stop failed:", err);
            });
    }  
})

$("#manualQrCode").on("keypress", function(e) {
    if (e.which === 13) {
        $("#fetchManual").click();
    }
});
$("#btnnext").on("click", function () {
    if ($("#tblbarcodedetail tbody tr").length <= 0) {
        alert("You have not allocated any qty");
        return;
    }
    let form_data = new FormData();
    form_data.append("projectid", $("#hidden_projectid").val());
    form_data.append("clientid", $("#clientid").val());
    form_data.append("invoiceno", $("#invoiceno option:selected").text().trim());
    form_data.append("psono", $("#invoiceno").val());
    form_data.append("truckno", $("#truckno").val().trim());
    form_data.append("drivername", $("#drivername").val().trim());
    form_data.append("drivermobileno", $("#drivermobileno").val().trim());
    form_data.append("loginname", $("#hidden_loginname").val());

    $.ajax({
        type: "POST",
        url: "save/creategatepasspdf.php?action=gatepass",
        data: form_data,
        processData: false,
        contentType: false,
        dataType: "json",

        beforeSend: function () {
            $(".waitloaderfooter").modal("show");
        },
        success: function (res) {
            $(".waitloaderfooter").modal("hide");
            if (res.status === "success") {
                alert(res.message);
                // Open Gate Pass PDF in new tab
                window.open($("#hidden_rooturl").val()+res.deliverypath, "_blank");
            } else {
                alert(res.message);
            }
        },
        error: function () {
            $(".waitloaderfooter").modal("hide");
            alert("Server error while generating gate pass");
        }
    });
});

$("#btnmoveprev").on("click", function () {
    if (arrayindex > 0) {
        arrayindex--;
        showproductdetails(arrayindex);
        updateNavButtons();
    }
});
$("#btnmovenext").on("click", function () {
    if (arrayindex < invoicearray.length - 1) {
        arrayindex++;
        showproductdetails(arrayindex);
        updateNavButtons();
    } else {
        alert("No more products to dispatch");
    }
}); 
