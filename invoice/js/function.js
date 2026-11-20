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
        url: "queries/invoicequeries.php?action=getorderno_forinv",     
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
function getproductdetailsforinv(client_id, psono, sono) {
    $("#tblproductdetail tbody").empty();
    resetToBackState();;
    const form_data = new FormData();
    form_data.append("projectid", $("#hidden_projectid").val());
    form_data.append("clientid", client_id);
    form_data.append("psono", psono);
    form_data.append("sono", sono);
    $.ajax({
        type: "POST",
        url: "queries/invoicequeries.php?action=getproduct_forinv",
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

                let rows = "";

                for (let i = 0; i < res.data.length; i++) {

                    const qty   = Number(res.data[i].quantity) || 0;
                    const avail = Number(res.data[i].availableqty) || 0;
                    const sellqty = qty;//avail < qty ? avail : qty;
                    const rate  = Number(res.data[i].rate) || 0;
                    const disc  = Number(res.data[i].discount_percent) || 0;

                    rows += `
                    <tr data-productid="${res.data[i].product_id}"
                        data-stockorder="${res.data[i].stockorder_no}"
                        data-freightamount="${res.data[i].freight_amount}"
                        data-freightdiscountpercent="${res.data[i].freight_discountpercent}"
                        data-payablefreightamount="${res.data[i].payable_freightamount}"
                        data-drivername="${res.data[i].drivername}"
                        data-drivercontactno="${res.data[i].drivercontactno}"
                        data-vehicleno="${res.data[i].vehicleno}"
                        data-deliveryaddress="${res.data[i].deliveryaddress}"
                        >

                        <td class="row-checkbox noexport">
                            <input type="checkbox"
                                   class="chkorder"
                                    >
                        </td>
                        <td class="promisedt">${res.data[i].deliverydate}</td>
                        <td class="productname">${res.data[i].productname}</td>
                        <td class="orderqty">${qty}</td>
                        <td class="availqty">${avail}</td>

                        <td class="sellqty editable-int"
                            contenteditable>${sellqty}</td>

                        <td class="rate editable-decimal"
                            contenteditable>${rate}</td>

                        <td class="taxableamt"></td>

                        <td class="discountper editable-decimal"
                            contenteditable>${disc}</td>

                        <td class="discountamt editable-decimal"
                            contenteditable></td>

                        <td class="payableamt text-end fw-bold"></td>
                    </tr>`;
                }

                $("#tblproductdetail tbody").html(rows); 
                $("#tblproductdetail tbody tr").each(function () {
                    recalcRow($(this), "per");
                });

            } else {
                $("#tblproductdetail tbody").html(
                    '<tr><td colspan="11" class="text-center text-danger">No product found.</td></tr>'
                );
            }
        },

        error: function () {
            $(".waitloaderfooter").modal("hide");
            $("#tblproductdetail tbody").html(
                '<tr><td colspan="11" class="text-center text-danger">Error loading data.</td></tr>'
            );
        }
    });
} 
 
function getNum(val) {
    val = $.trim(val);
    return val === "" ? 0 : parseFloat(val) || 0;
}

function setTextIfPositive($el, value) {
    $el.text(value > 0 ? value.toFixed(2) : "");
}

function recalcRow(row, source) {
    const sellqty = getNum(row.find(".sellqty").text());
    const rate    = getNum(row.find(".rate").text());
    const taxable = sellqty * rate;
    let discPer = getNum(row.find(".discountper").text());
    let discAmt = getNum(row.find(".discountamt").text());
    if (taxable <= 0) {
        row.find(".taxableamt, .discountamt").text("");
        row.find(".payableamt").text(0);
        return;
    }

    // 🔒 hard limits
    if (discPer > 100) discPer = 100;
    if (discAmt > taxable) discAmt = taxable;

    if (source === "per") {
        discAmt = taxable * discPer / 100;
        if (discAmt > taxable) discAmt = taxable;
        row.find(".discountamt").text(discAmt > 0 ? discAmt.toFixed(2) : "");
    }

    if (source === "amt") {
        discPer = (discAmt / taxable) * 100;
        if (discPer > 100) discPer = 100;
        row.find(".discountper").text(discPer > 0 ? discPer.toFixed(2) : "");
    }

    row.find(".taxableamt").text(taxable.toFixed(2));
    row.find(".payableamt").text((taxable - discAmt).toFixed(2));
}


function recalcFooterIfVisible() {

    const $tfoot = $("#tblproductdetail tfoot");

    // ❗ Do nothing if footer is hidden
    if ($tfoot.hasClass("d-none")) return;

    let totalSellQty = 0;
    let totalTaxable = 0;
    let totalDiscount = 0;
    let totalPayable = 0;

    $("#tblproductdetail tbody tr:visible").each(function () {

        const row = $(this);

        if (!row.find(".chkorder").is(":checked")) return;

        totalSellQty += getNum(row.find(".sellqty").text());
        totalTaxable += getNum(row.find(".taxableamt").text());
        totalDiscount += getNum(row.find(".discountamt").text());
        totalPayable += getNum(row.find(".payableamt").text());
    });

    $(".ft-sellqty").text(totalSellQty || "");
    $(".ft-taxable").text(totalTaxable ? totalTaxable.toFixed(2) : "");
    $(".ft-discount").text(totalDiscount ? totalDiscount.toFixed(2) : "");
    $(".ft-payable").text(totalPayable ? totalPayable.toFixed(2) : "");
}
function resetToBackState(){
    $("#tblproductdetail tbody tr").show(); 
    $(".ft-sellqty, .ft-taxable, .ft-discount, .ft-payable").text("");
    $("#tblproductdetail tfoot").addClass("d-none");
    $(".hidebtn").addClass("d-none");
    $("#btnnext").removeClass("d-none");
     $("#productdetail").removeClass("step-active");

    $("#stepHeader").addClass("d-none");
    $(".mainheading").removeClass("d-none");
}
function datasave(invoicetype,client_id,psono,sono,drivername,drivercontactno,vehicleno,freightcharges){
    let cheksaleqty= true;
     $("#tblproductdetail tbody tr").each(function () {
        const row = $(this);
        if (!row.find(".chkorder").is(":checked")) return;
        if(getNum(row.find(".sellqty").text())<=0){
          cheksaleqty=false;  
        } 
    });
     if(!cheksaleqty){
        alert("Sale qty can't be empty");
        return false;
     }

    let clientData = {
        clientid         : client_id,
        clientname       : $("#ctcperson").val(),
        mobileno         : $("#ctcno").val(),
        emailid          : $("#email_id").val(),
        delivery_address : $("#inv_add").val(),
        psono            : psono,
        sono             : sono,       
        status           : "Ready for despatch"
    };
    $("#generatefinal_inv").removeData();
    let allocationData = [];
    //$("#tblproductdetail tbody tr:visible").each(function () {
    $("#tblproductdetail tbody tr").each(function () {
        // debugger;
        const row = $(this);
        if (!row.find(".chkorder").is(":checked")) return;
        allocationData.push({
            productid      : row.data("productid"),
            productname    : row.find(".productname").text(),
            stockorder_no  : row.data("stockorder"),
            clientid       : client_id,
            psono          : psono,
            sono           : sono,
            orderqty       : row.find(".orderqty").text(),
            readyqty       : getNum(row.find(".availqty").text()),
            saleqty        : getNum(row.find(".sellqty").text()),
            rate           : getNum(row.find(".rate").text()),
            taxableamt     : getNum(row.find(".taxableamt").text()),
            discount_percent    : getNum(row.find(".discountper").text()),
            discountamt    : getNum(row.find(".discountamt").text()),
            payableamt     : getNum(row.find(".payableamt").text()),
            cgst_percent    :0,
            sgst_percent    :0,
            igst_percent    :0,
        });
    });

    if (allocationData.length === 0) {
        alert("Please select at least one row");
        return;
    }
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
        saledetailproductwise: JSON.stringify(allocationData),
        clientData: JSON.stringify(clientData),
        clientid: client_id
    },

    beforeSend: function () {
        $(".waitloaderfooter").modal("show");
    },

    success: function (res) {
        console.log(res);

        $(".waitloaderfooter").modal("hide");

        if (res.status !== "success") {
            alert(res.message || "Invoice generation failed");
            return;
        }
        if(invoicetype==="tempinv"){
                $(".productdetail").addClass("d-none");
                $("#previewinvoice").removeClass("d-none");
                $(".mainheading").addClass("d-none");
                $("#regenerate").prop("disabled", false);
                $("#generatefinal_inv").prop("disabled", false);
                $("#generatefinal_inv").removeClass("generatedinvoice");
        }else{
            
                $("#regenerate").prop("disabled", true);
                $("#generatefinal_inv").prop("disabled", true);
                $("#generatefinal_inv").addClass("generatedinvoice");
        }
        // ✅ encode to avoid URL break
        debugger;
        const fileParam = encodeURIComponent(res.file);
         const deliverynoteParam = encodeURIComponent(res.deliverynote);
        $("#generatefinal_inv").data("invoicepath",fileParam)
        $("#generatefinal_inv").data("deliverynote",deliverynoteParam)
        // debugger;
        $("#showinvoice").addClass("active").siblings().removeClass("active");
        //$("#displaypreview").load("../preview.php?f=" + fileParam);
        $('#displaypreview').attr('src', "../preview.php?f=" + fileParam);
    },

    error: function (xhr) {
        $(".waitloaderfooter").modal("hide");
        alert("Server error while saving invoice");
        console.error(xhr.responseText);
    }
});

/*
    $.ajax({
        url: "save/saveinv.php",
        type: "POST",
       // dataType: "json",
        data: {
            companyid: $("#hidden_companyid").val(),
            subcompanyid: $("#hidden_subcompanyid").val(),
            projectid: $("#hidden_projectid").val(),
            communicatedby: $("#hidden_loginname").val(),
            companyname: $("#hidden_companyname").val(),
            psono: psono,
            sono:sono,
            invoicetyype:invoicetype,
            saledetailproductwise: JSON.stringify(allocationData),
            clientData: JSON.stringify(clientData), 
            clientid: client_id,
        },

        beforeSend: function () {
            $(".waitloaderfooter").modal("show");
        },

        success: function (res) {
            $(".waitloaderfooter").modal("hide"); 

            $("#previewinvoice").show();
            var resf=res.trim();
            $("#displaypreview").load("../preview.php?f="+resf);  
        }
    });*/
}


 