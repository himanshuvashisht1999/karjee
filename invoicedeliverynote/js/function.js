function getcustomer(){
    
    $("#tblproductdetail tbody").empty();
     resetToBackState();
    $("#clientid").empty();
   $("#orderno").empty();
    $("#clientid").append('<option value="" selected disabled>Select</option>');
    Getcustomer_callback(function(res){       
        if(res.length>0){       
            for (var i = 0; i < res.length; i++) {
               $("#clientid").append('<option value="'+res[i].client_id+'" data-invoicingaddress="'+res[i].invoicing_address+'" data-phones="'+res[i].phones+'" data-clientemail="'+res[i].client_email+'" data-statename="'+res[i].statename+'" data-districtname="'+res[i].districtname+'">'+res[i].client_name+'</option>');
            }          
        }
    });
}
function Getcustomer_callback(callback) { 
    var projectid = $("#hidden_projectid").val();
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    $.ajax({
        type: "POST",
        url: "queries/invoicequeries.php?action=getclientdata",     
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
function getorderno(client_id){
    $("#tblproductdetail tbody").empty();
    resetToBackState();
    $("#orderno").empty();
    $("#orderno").append('<option value="" selected disabled>Select</option>');
    Getorder_forinv_callback(client_id,function(res){       
        if(res.length>0){       
            for (var i = 0; i < res.length; i++) {
               $("#orderno").append('<option value="'+res[i].psono+'">'+res[i].sono+'</option>');
            }          
        }
    });
}
function Getorder_forinv_callback(client_id,callback) { 
    var projectid = $("#hidden_projectid").val();
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("clientid", client_id);
    $.ajax({
        type: "POST",
        url: "queries/invoicequeries.php?action=get_pending_orders_grid",     
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

function getprovisional_freightcharges(clientid, psonos, callback) { 
    var projectid = $("#hidden_projectid").val();
    const form_data = new FormData();
    form_data.append("projectid", projectid);
    form_data.append("clientid", clientid);
    form_data.append("psonos", JSON.stringify(psonos));
    $.ajax({
        type: "POST",
        url: "queries/invoicequeries.php?action=getprovisional_freightcharges",     
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
function loadPendingOrders(client_id) {
    $("#tblpendingorders tbody").empty();
    resetToBackState();
    
    Getorder_forinv_callback(client_id, function(res) {
        if(res.length > 0) {
            let rows = "";
            for (let i = 0; i < res.length; i++) {
                rows += `
                <tr data-id="${res[i].id}" data-psono="${res[i].psono}" data-sono="${res[i].sono}" data-stockorder-no="${res[i].stockorder_no}" data-ordervalue="${res[i].ordervalue}" 
                    data-has-allocation="${res[i].has_allocation}" data-has-gatepass="${res[i].has_gatepass}" data-has-deliverynote="${res[i].has_deliverynote}" data-has-freight="${res[i].has_freight_invoice}" class="align-middle">
                    <td class="row-checkbox noexport" style="text-align: center;">
                        <input type="checkbox" class="chkorder">
                    </td>
                    <td><div class="fw-bold text-primary">${res[i].psono}</div></td>
                    <td>
                        <span class="badge bg-light text-dark border me-1">${res[i].product_id}</span>
                        <span class="fw-bold">${res[i].productname}</span>
                    </td>
                    <td>${res[i].deliverydate}</td>
                    <td class="text-center">${res[i].totalqty}</td>
                    <td class="fw-bold row-base-value">₹${Number(res[i].ordervalue).toFixed(2)}</td>
                    <td contenteditable="true" class="editable-decimal text-center row-disc-per">0</td>
                    <td contenteditable="true" class="editable-decimal text-center row-disc-amt">0</td>
                    <td class="text-end fw-bold text-success row-final-value">₹${Number(res[i].ordervalue).toFixed(2)}</td>
                </tr>`;
            }
            $("#tblpendingorders tbody").html(rows);
            $("#ordersContainer").removeClass("d-none");
        } else {
            $("#tblpendingorders tbody").html('<tr><td colspan="5" class="text-center text-muted py-4">No pending orders found for this client.</td></tr>');
            $("#ordersContainer").removeClass("d-none");
        }
    });
}

function getNum(val) {
    val = $.trim(val);
    return val === "" ? 0 : parseFloat(val) || 0;
}

function resetToBackState(){
    $("#tblpendingorders tbody tr").show();
    $("#ordersContainer").addClass("d-none");
    $("#previewinvoice").addClass("d-none");
    $(".mainheading").removeClass("d-none");
}
function datasave(invoicetype, client_id, drivername, drivercontactno, vehicleno, freightcharges, ordersData, deliveryDate = "", vehicleId = "", driverId = ""){
    // Determine the sonos mapping (for final invoice if needed)
    let psono = ordersData.length > 0 ? ordersData[0].psono : "";
    let sono = $("#tblpendingorders tbody tr").filter(function() { return $(this).data("psono") == psono; }).data("sono") || "";
    
    let clientData = {
        clientid         : client_id,
        clientname       : $("#ctcperson").val() || $("#clientid option:selected").text(),
        mobileno         : $("#ctcno").val() || $("#clientid option:selected").data("phones"),
        emailid          : $("#email_id").val() || $("#clientid option:selected").data("clientemail"),
        delivery_address : $("#inv_add").val() || $("#clientid option:selected").data("invoicingaddress"),
        companyname      : $("#hidden_companyname").val() || "Karjee Farms",
        psono            : psono,
        sono             : sono,       
        status           : "Ready for despatch"
    };
    
    let psonosArray = ordersData.map(o => o.psono);
    let itemIdsArray = ordersData.map(o => o.id);
    
    $("#generatefinal_inv").removeData();
    
    // Fetch product details for the selected products first before saving
    $.ajax({
        url: "queries/invoicequeries.php?action=fetch_order_products_batch",
        type: "POST",
        dataType: "json",
        data: {
            projectid: $("#hidden_projectid").val(),
            clientid: client_id,
            psonos: JSON.stringify(psonosArray),
            itemIds: JSON.stringify(itemIdsArray)
        },
        beforeSend: function () {
            $(".waitloaderfooter").modal("show");
        },
        success: function (productRes) {
            if (productRes.status === "success" && productRes.data.length > 0) {
                let allocationData = [];
                for(let i=0; i<productRes.data.length; i++) {
                    const row = productRes.data[i];
                    
                    // Match order level info
                    const orderLevelInfo = ordersData.find(o => o.psono == row.psono) || { discPer: 0 };
                    
                    const qty = Number(row.quantity) || 0;
                    const rate = Number(row.rate) || 0;
                    
                    // If UI has order-level discount %, apply it to products
                    const disc = orderLevelInfo.discPer > 0 ? orderLevelInfo.discPer : (Number(row.discount_percent) || 0);
                    
                    const baseAmount = qty * rate;
                    const discountAmt = baseAmount * (disc/100);
                    
                    allocationData.push({
                        productid      : row.product_id,
                        productname    : row.productname,
                        stockorder_no  : row.stockorder_no,
                        clientid       : client_id,
                        psono          : row.psono,
                        sono           : row.sono,
                        orderqty       : qty,
                        readyqty       : Number(row.availableqty) || 0,
                        saleqty        : qty, // Full sale
                        rate           : rate,
                        taxableamt     : baseAmount,
                        deliveryaddress: row.deliveryaddress || "",
                        discount_percent    : disc,
                        discountamt    : discountAmt,
                        payableamt     : baseAmount - discountAmt,
                        cgst_percent   : 0,
                        sgst_percent   : 0,
                        igst_percent   : 0
                    });
                }
                
                // Now submit to saveinv.php
                callSaveInvAjax(invoicetype, client_id, psono, sono, drivername, drivercontactno, vehicleno, freightcharges, allocationData, clientData, deliveryDate, vehicleId, driverId);
                
            } else {
                $(".waitloaderfooter").modal("hide");
                alert("Failed to fetch product details for selected orders.");
            }
        },
        error: function(xhr) {
            $(".waitloaderfooter").modal("hide");
            alert("Error fetching product data: " + xhr.responseText);
        }
    });

}

function callSaveInvAjax(invoicetype, client_id, psono, sono, drivername, drivercontactno, vehicleno, freightcharges, allocationData, clientData, deliveryDate = "", vehicleId = "", driverId = "") {
    $.ajax({
        url: "save/saveinv.php",
        type: "POST",
        dataType: "json",

        data: {
            companyid: $("#hidden_companyid").val(),
            subcompanyid: $("#hidden_subcompanyid").val(),
            projectid: $("#hidden_projectid").val(),
            communicatedby: $("#hidden_loginname").val(),
            companyname: $("#hidden_companyname").val(),

            psono: psono,
            sono: sono,
            invoicetype: invoicetype,
            drivername: drivername,
            drivercontactno: drivercontactno,
            vehicleno: vehicleno,
            freightcharges: freightcharges,
            deliverydate: deliveryDate,
            vehicleid: vehicleId,
            driverid: driverId,
            saledetailproductwise: JSON.stringify(allocationData),
            clientData: JSON.stringify(clientData),
            clientid: client_id
        },

        success: function (res) {
            $(".waitloaderfooter").modal("hide");

            if (res.status !== "success") {
                alert(res.message || "Invoice generation failed");
                return;
            }
            if (invoicetype === "tempinv") {
                $("#ordersContainer").addClass("d-none");
                $("#previewinvoice").removeClass("d-none");
                $(".mainheading").addClass("d-none");
                
                // Store all draft paths
                if (res.file) $("#generatefinal_inv").data("invoicepath", encodeURIComponent(res.file));
                if (res.gatepass) $("#generatefinal_inv").data("gatepass", encodeURIComponent(res.gatepass));
                if (res.deliverynote) $("#generatefinal_inv").data("deliverynote", encodeURIComponent(res.deliverynote));
                
                // Auto-show draft document (Prioritize Freight Invoice if generated, else Gate Pass)
                if ($("#generatefinal_inv").data("freightinvoice")) {
                    $("#showfreightinvoice").click();
                } else if ($("#generatefinal_inv").data("gatepass")) {
                    $("#showgatepass").click();
                }
            }
            
            if (res.file && invoicetype !== "tempinv") {
                const fileParam = encodeURIComponent(res.file);
                $("#generatefinal_inv").data("invoicepath", fileParam);
                $("#showinvoice").addClass("active").siblings().removeClass("active");
                openDocumentPreview(fileParam, "Document Preview");
            }
            if (res.deliverynote && invoicetype !== "tempinv") {
                const deliverynoteParam = encodeURIComponent(res.deliverynote);
                $("#generatefinal_inv").data("deliverynote",deliverynoteParam);
                $("#showdeliverynote").addClass("active").siblings().removeClass("active");

                openDocumentPreview(res.deliverynote, "Delivery Note Preview");
            }
            if (res.gatepass) {
                const gatepassParam = encodeURIComponent(res.gatepass);
                $("#generatefinal_inv").data("gatepass",gatepassParam);
                $("#showgatepass").addClass("active").siblings().removeClass("active");

                openDocumentPreview(res.gatepass, "Gate Pass Preview");
            }
            
            // Refresh grid if document is finalized (not a draft)
            if (invoicetype !== "tempinv") {
                loadPendingOrders(client_id);
            }
            
            if (invoicetype === "tempinv") {
                // Show preview area
                $("#ordersContainer").addClass("d-none");
                $("#previewinvoice").removeClass("d-none");
                $(".mainheading").addClass("d-none");
            }
        },

        error: function (xhr) {
            $(".waitloaderfooter").modal("hide");
            alert("Server error while saving invoice: " + xhr.responseText);
        }
    });
}

function openDocumentPreview(filePath, title) {
    if (!filePath) return;
    
    // Clean up filePath to ensure no leading slashes
    let cleanPath = filePath;
    if (cleanPath.startsWith("./")) cleanPath = cleanPath.substring(2);
    if (cleanPath.startsWith("/")) cleanPath = cleanPath.substring(1);

    const fileParam = encodeURIComponent(cleanPath);
    
    // Construct the absolute URL reliably
    const currentLoc = window.location.href;
    const baseIndex = currentLoc.indexOf('invoicedeliverynote/');
    let projectRoot = currentLoc.substring(0, baseIndex);
    
    // Ensure projectRoot ends with exactly one slash
    if (!projectRoot.endsWith('/')) projectRoot += '/';
    
    const fullURL = projectRoot + cleanPath;
    const previewURL = projectRoot + "preview.php?f=" + fileParam;
    console.log(fullURL);
    // console.log(previewURL);

    $("#pdfPreviewTitle").text(title);
    $("#btnDownloadPDF").attr("href", fullURL);
    
    // Use an iframe for isolated preview
    $("#modalPreviewContent").html('<iframe src="' + previewURL + '" style="width:100%; height:80vh; border:none;"></iframe>');
    $("#pdfPreviewModal").modal("show");
}

function saveFreightAndGenerateInvoice(clientid, freightData, vno, did, dname, dcontact, vid, ddate, callback) {
    const pid = $("#hidden_projectid").val();
    const company = $("#hidden_companyname").val() || "Karjee Farms";
    
    $.ajax({
        url: "queries/invoicequeries.php?action=generate_freight_invoice",
        type: "POST",
        data: {
            freightData: JSON.stringify(freightData),
            clientid: clientid,
            projectid: pid,
            companyname: company,
            drivername: dname,
            drivercontactno: dcontact,
            vehicleno: vno,
            did: did,
            vehicleid: vid,
            deliverydate: ddate
        },success: function(res) {
            try {
                const response = (typeof res === 'object') ? res : JSON.parse(res);
                if (response.status === 'success' && response.details && response.details.length > 0) {
                    // Store the first freight invoice path for preview
                    $("#generatefinal_inv").data("freightinvoice", encodeURIComponent(response.details[0].path));
                }
                if (callback) callback(response);
            } catch (e) {
                console.error("Error parsing freight response:", res);
                if (callback) callback({status: 'error', message: 'Invalid server response'});
            }
        },
        error: function(err) {
            console.error("AJAX error in freight generation:", err);
            if (callback) callback({status: 'error', message: 'Network error'});
        }
    });
}

 