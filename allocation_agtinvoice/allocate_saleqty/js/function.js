function getcustomer_invoice(){
    $("#truckno").val("");
    $("#drivername").val("");
    $("#drivermobileno").val("");  

    $("#tblbarcodedetail tbody").empty();
    invoicearray=[];
    arrayindex=0; 
    updateNavButtons();   
    $("#divproductdetails").html(""); 
    $(".scannerdiv").addClass("d-none");    
    $("#clientid").empty();
    $("#invoiceno").empty();
    $("#clientid").append('<option value="" selected disabled>Select</option>');
    Getcustomer_inv_callback(function(res){       
        if(res.length>0){       
            for (var i = 0; i < res.length; i++) {
               $("#clientid").append('<option value="'+res[i].client_id+'" data-invoicingaddress="'+res[i].invoicing_address+'" data-phones="'+res[i].phones+'" data-clientemail="'+res[i].client_email+'" data-statename="'+res[i].statename+'" data-districtname="'+res[i].districtname+'">'+res[i].client_name+'</option>');
            }          
        }
    });
}
function Getcustomer_inv_callback(callback) { 
    var projectid = $("#hidden_projectid").val();
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    invoicearray=[];
    updateNavButtons();
    $.ajax({
        type: "POST",
        url: "allocatequeries/condition_queries.php?action=getclientdata_inv",     
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {            
            if (res.status === "success") {
                callback(res.data);   
            } else {
                callback([]);        
            }
            
        }
    });
}
function getinvoiceno_pendingdespatch(client_id){ 
     $("#divproductdetails").html("");

    $("#truckno").val("");
    $("#drivername").val("");
    $("#drivermobileno").val("");
    $("#tblbarcodedetail tbody").empty();
    $(".scannerdiv").addClass("d-none"); 
    invoicearray=[];  
    arrayindex=0; 
    updateNavButtons(); 
    $("#invoiceno").empty();
    $("#invoiceno").append('<option value="" selected disabled>Select</option>');
    getinvoiceno_pendingdespatch_callback(client_id,function(res){       
        if(res.length>0){       
            for (let i = 0; i < res.length; i++) {
                $("#invoiceno").append(`
                    <option 
                        value="${res[i].psono}" 
                        data-sono="${res[i].sono}">
                        ${res[i].invoiceno}
                    </option>
                `);
            }        
        }
    });
}
function getinvoiceno_pendingdespatch_callback(client_id,callback) { 
    var projectid = $("#hidden_projectid").val();
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("clientid", client_id);
    $.ajax({
        type: "POST",
        url: "allocatequeries/condition_queries.php?action=invoiceno_pendingdespatch",     
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "JSON",
        success: function (res) {            
            if (res.status === "success") {
                callback(res.data);   
            } else {
                callback([]);        
            }            
        }
    });
}
function getdespatchitems(client_id, psono, sono,invoiceno) { 
    $("#divproductdetails").html("");
    $("#tblbarcodedetail tbody").empty();
    $(".scannerdiv").addClass("d-none");
    updateNavButtons();

    $("#truckno").val("");
    $("#drivername").val("");
    $("#drivermobileno").val("");
    const form_data = new FormData();
    form_data.append("projectid", $("#hidden_projectid").val());
    form_data.append("clientid", client_id);
    form_data.append("psono", psono);
    form_data.append("sono", sono);
    form_data.append("invno", invoiceno);
    $.ajax({
        type: "POST",
        url: "allocatequeries/condition_queries.php?action=getproduct_fordespatch",
        data: form_data,
        contentType: false,
        processData: false,
        dataType: "JSON",

        beforeSend: function () {
            $(".waitloaderfooter").modal("show");
        },

        success: function (res) {
            $(".waitloaderfooter").modal("hide");
            if (Array.isArray(res.data) && res.data.length > 0) {
                $("#lblcnt").html(res.data[0].cntlabel);
                 invoicearray=res.data;
                 arrayindex=0;
                 showproductdetails(arrayindex);
                 
            } else {
                 alert("No Data found");
            }
        },

        error: function () {
            $(".waitloaderfooter").modal("hide");
            alert("Error");
        }
    });
}  
function initScanner() {
    if (typeof Html5Qrcode === "undefined") {
      alert("QR Scanner library not loaded. Please check your internet connection or library URL.");
      return;
    }

    // Prevent multiple initializations
    if (qrReader) {
      try { qrReader.stop(); } catch (e) {}
    }

    qrReader = new Html5Qrcode("reader");

    const onScanSuccess = (decodedText) => {
      qrReader.stop().then(() => {
        console.log("✅ QR Code Scanned:", decodedText);
        $("#startScan").show();
        $("#stopScan").hide();
        fetchTrayData_diagnose(decodedText);
      }).catch(err => console.error("Stop failed:", err));
    };

    const onScanFailure = (error) => {
      // You can log scan errors if needed
      // console.log(`Scan error: ${error}`);
    };

    qrReader.start(
      { facingMode: "environment" }, // Use back camera
      { fps: 10, qrbox: 250 },
      onScanSuccess,
      onScanFailure
    ).then(() => {
      $("#startScan").hide();
      $("#stopScan").show();
      console.log("📷 Scanner started automatically");
    }).catch(err => {
      console.error("Scanner start failed:", err);
      alert("Unable to start QR Scanner. Please allow camera access.");
    });
} 
function showproductdetails(index){ 
    let selectedopt = $("#invoiceno option:selected");
    const invoiceno = selectedopt.text().trim();  
    $("#allocatedivcal").addClass("d-none") ;        
    $(".scannerdiv").addClass("d-none");
    const form_data = new FormData();
    form_data.append("projectid", $("#hidden_projectid").val()); 
    form_data.append("psono", invoicearray[index]['psono']);
    form_data.append("stockorder_no", invoicearray[index]['stockorder_no']);
    form_data.append("product_id", invoicearray[index]['product_id']);
    form_data.append("quantity", invoicearray[index]['quantity']);
    form_data.append("invoiceno", invoiceno);
    $.ajax({
        type: "POST",
        url: "allocatequeries/condition_queries.php?action=getreadystock",
        data: form_data,
        contentType: false,
        processData: false,
        dataType: "JSON",
        beforeSend: function () {
            $(".waitloaderfooter").modal("show");
        },
        success: function (res) {
            $(".waitloaderfooter").modal("hide");
            if (Array.isArray(res.data) && res.data.length > 0) { 
                d=res.data[0];
                 let maindetails = `
                    <div class="card mt-1">
                        <div class="card-body">
                            <h5 class="card-title text-center mb-3">Product detail</h5>
                            <div class="row g-2">                               
                                <div class="col-md-6 d-none">
                                    Stock Order No:
                                    <span class="stockorderno" style="font-weight:bold;">${d.stockorder_no ?? ''}</span>
                                </div>
                                <div class="col-md-6">
                                    Product Name: 
                                    <span class="product_name" style="font-weight:bold;">${d.productname ?? ''}</span>
                                </div>
                                <div class="col-md-6">
                                   Invoice Qty: 
                                    <span class="invoiceQty" style="font-weight:bold;">${invoicearray[index]['quantity']}</span>
                                </div>
                                <div class="col-md-6">
                                     Location: 
                                    <span class="location" style="font-weight:bold;">${d.locationname ?? ''}</span>
                                </div>
                                 <div class="col-md-6">
                                     Polyhouse Name: 
                                    <span class="polyhousename" style="font-weight:bold;">${d.polyhousename ?? ''}</span>
                                </div>                                             
                                <div class="col-md-6">
                                    Row No: 
                                    <span class="rowno" style="font-weight:bold;">${res.data[0].rowno ?? ''}</span>
                                </div>
                                <div class="col-md-6">
                                     Allocate Qty: 
                                    <span class="allocateqty" style="font-weight:bold;"></span>
                                </div> 
                            </div>
                        </div>
                    </div>
                `;
                  
                $("#divproductdetails").html(maindetails);
                initScanner();
                $(".scannerdiv").removeClass("d-none"); 
                if (Array.isArray(res.allocatedarray) && res.allocatedarray.length > 0) {
                    let rowsHtml = "";
                    let allocateqty = Number($(".allocateqty").text()) || 0;
                    res.allocatedarray.forEach(d => {
                        const rdyqty = Number(d.qtypertray) || 0;
                        rowsHtml += `
                            <tr data-productid="${d.productid}"
                                data-stockorder="${d.stockorder_no}"
                                data-dbid="${d.id}">
                                <td class="row-checkbox noexport d-none">
                                    <input type="checkbox" class="chkorder" checked>
                                </td>
                                <td class="barcode">${d.barcode}</td>
                                <td class="productname">${d.productname}</td>
                                <td class="saleqty">${d.qtypertray}</td>
                            </tr>`;

                        allocateqty += rdyqty;

                    });

                    $(".allocateqty").text(allocateqty);
                    $("#tblbarcodedetail tbody").append(rowsHtml); 
                     $("#allocatedivcal").removeClass("d-none").html(`Allocate Qty:
                        <span" style="font-weight:bold;">${allocateqty}</span>
                    `);
    
                }
                if (Array.isArray(res.truckdetails) && res.truckdetails.length > 0) {
                    d=res.truckdetails[0];
                    $("#truckno").val(d.truck_no);
                    $("#drivername").val(d.driver_name);
                    $("#drivermobileno").val(d.driver_mobile);

                }

                 
            } else {
                 alert("No Data found");
            }
        },

        error: function () {
            $(".waitloaderfooter").modal("hide");
             alert("Error")
        }
    });
 }

function getbarcode_data(barcode) {
    let invoiceno =$("#invoiceno option:selected").text().trim();
    let clientid =$("#clientid").val();     
    const form_data = new FormData();
    form_data.append("projectid", $("#hidden_projectid").val());
    form_data.append("barcode",barcode);    
    $.ajax({
        type: "POST",
        url: "allocatequeries/condition_queries.php?action=getbarcodedetails",
        data: form_data,
        contentType: false,
        processData: false,
        dataType: "JSON",

        beforeSend: function () {
            $(".waitloaderfooter").modal("show");
        },

        success: function (res) {
            $(".waitloaderfooter").modal("hide");

            if (Array.isArray(res.data) && res.data.length > 0) {

                const d = res.data[0];
                const rdyqty = Number(d.qtypertray) || 0; 
                if (isBarcodeAlreadyScanned(barcode)) {
                    alert("This barcode has already been scanned.");
                    $("#manualQrCode").val("").focus();
                    return;
                } 
                if (
                    invoicearray[arrayindex]['stockorder_no'] === d.stockorder_no &&
                    invoicearray[arrayindex]['product_id'] === d.productid
                ) {

                    const rows = `
                        <tr data-productid="${d.productid}"
                            data-stockorder="${d.stockorder_no}"
                            data-dbid="${d.id}">
                            <td class="row-checkbox noexport d-none">
                                <input type="checkbox" class="chkorder" checked>
                            </td>
                            <td class="barcode">${barcode}</td>
                            <td class="productname">${d.productname}</td>
                            <td class="saleqty">${d.qtypertray}</td>
                        </tr>`;

                    let allocateqty = Number($(".allocateqty").text()) || 0;
                    let tqty = allocateqty + rdyqty;
                    $(".allocateqty").text(tqty);
                    $("#tblbarcodedetail tbody").append(rows);
                    $("#allocatedivcal").removeClass("d-none").html(`Allocate Qty:
                        <span" style="font-weight:bold;">${allocateqty}</span>
                    `);
                    updatebarcode(clientid,invoiceno,d.id );
                    if (tqty >= Number(invoicearray[arrayindex]['quantity'])) {
                         $("#btnmovenext").click();
                    }else{
                         $("#startScan").click();
                         $("#manualQrCode").val("");
                    }
                } else {
                    alert("QR Code details do not match the selected product.");
                    $("#manualQrCode").val("").focus();
                }

            } else {
                alert("No data found for this barcode.");
                $("#manualQrCode").val("").focus();
            }
        },
        error: function () {
            $(".waitloaderfooter").modal("hide");
             alert("error");
        }
    });
}
function isBarcodeAlreadyScanned(barcode) {
    let exists = false;

    $("#tblbarcodedetail tbody tr").each(function () {
        const scanned = $(this).find(".barcode").text().trim();
        if (scanned === barcode.trim()) {
            exists = true;
            return false; // break loop
        }
    });

    return exists;
}

function updatebarcode(client_id,invoiceno,dbid) {  
    const form_data = new FormData();
    form_data.append("projectid", $("#hidden_projectid").val());
    form_data.append("clientid", client_id);
    form_data.append("dbid", dbid);
    form_data.append("invoiceno", invoiceno); 
    $.ajax({
        type: "POST",
        url: "save/saveallocation.php?action=assignallocatestk",
        data: form_data,
        contentType: false,
        processData: false,
        dataType: "JSON",

        beforeSend: function () {
            $(".waitloaderfooter").modal("show");
        },

        success: function (res) {
            $(".waitloaderfooter").modal("hide");
                resp=res.trim();
            if (resp!=="success") {
               alert ("error in saving allocation");
                 
            }  
        },

        error: function () {
            $(".waitloaderfooter").modal("hide");
             
        }
    });
} 
function updateNavButtons() {
    // Prev button
    if (arrayindex <= 0) {
        $("#btnmoveprev").prop("disabled", true);
    } else {
        $("#btnmoveprev").prop("disabled", false);
    }

    // Next button
    if (arrayindex >= invoicearray.length - 1) {
        $("#btnmovenext").prop("disabled", true);
    } else {
        $("#btnmovenext").prop("disabled", false);
    }
}