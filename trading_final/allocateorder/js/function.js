function orderdetails(tableid) {
    const selector = '#' + tableid;
    $(selector + " tbody").empty();
    $(selector + " tfoot").empty();

    const projectid  = $("#hidden_projectid").val();
    const promiseddt = $("#mydate").val() || "";

    const formData = new FormData();
    formData.append('projectid', projectid);
    formData.append('promiseddt', promiseddt);

    $.ajax({
        url: "sqlqueries/query_stage_new.php?action=pendinghistoryclient_stage1",
        type: "POST",
        data: formData,
        dataType: "json",
        contentType: false,
        processData: false,

        success: function (res) {

            if (!Array.isArray(res) || res.length === 0) {
                $(selector + " tbody").html(
                    '<tr><td colspan="11" class="text-center">No Data Found</td></tr>'
                );
                return;
            }

            let rows = "";
            let sno = 1;

            let lastGroupKey = "";
            let g_order = 0;
            let g_available = 0; 

            res.forEach(r => {

                const orderQty     = parseInt(r.order_qty, 10) || 0;
                const availableQty = parseInt(r.stage_qty ?? r.available_qty, 10) || 0;

                /* ✅ Correct grouping key */
                const groupKey = [
                    r.stockorder_no || '',
                    r.psono || '',
                    r.clientid || ''
                ].join('|');

                const isFirstRowOfGroup = (groupKey !== lastGroupKey);

                /* ✅ Totals */
                if (isFirstRowOfGroup) {
                    g_order += orderQty;
                }
                g_available += availableQty; 

                rows += `
                <tr class="order-row"
                    data-clientid="${r.clientid || ''}"
                    data-phoneno="${r.phoneno || ''}"
                    data-clientemail="${r.clientemail || ''}"
                    data-invoice_address="${r.invoice_address || ''}"
                    data-product_id="${r.product_id || ''}"
                    data-orderqty="${orderQty}"
                    data-expecteddelivery="${r.promisedt || ''}"
                    data-stockorder_no="${r.stockorder_no || ''}"
                    data-psono="${r.psono || ''}"
                    data-availabledt="${r.available_date || ''}"
                >
                    <td class="text-center">${isFirstRowOfGroup ? sno++ : ''}</td>
                `;

                /* ✅ Checkbox logic ONLY on first row */
                if (
                    isFirstRowOfGroup &&
                    r.stage === "Trader Warehouse" &&
                    availableQty >= orderQty
                ) {
                    rows += `
                        <td class="text-center">
                            <input type="checkbox" class="chkorder" style="transform:scale(1.2)">
                        </td>`;
                } else {
                    rows += `
                        <td class="text-center">
                            <input type="checkbox" class="chkorder" style="transform:scale(1.2)" disabled>
                        </td>`;
                }

                rows += `
                    <td class="clientname text-start">${isFirstRowOfGroup ? (r.client_name || '') : ''}</td>
                    <td class="productname text-start">${isFirstRowOfGroup ? (r.productname || '') : ''}</td>
                    <td class="expecteddelivery text-center">${isFirstRowOfGroup ? (r.promisedt || '') : ''}</td>
                    <td class="orderqty text-center">${isFirstRowOfGroup ? orderQty : ''}</td>

                    <td class='stockorder_no text-center'>${(r.stockorder_no || '') }</td>
                   
                    <td class="stockorder_qty text-center">${isFirstRowOfGroup ? (r.stockorder_qty || '') : ''}</td>
                    <td class="stage fw-bold text-primary text-center">${r.stage || ''}</td>
                    <td class="stageqty text-center">${availableQty}</td>
                    <td class="readydate text-center">${r.available_date || ''}</td>
                    <td class="text-center">${isFirstRowOfGroup ? (r.psono || '') : ''}</td>
                </tr>
                `;

                lastGroupKey = groupKey;
            });

            /* ===========================
               FOOTER TOTAL
            =========================== */
            const foothtml = `
            <tr class="fw-bold table-secondary">
                <td></td>
                <td></td>
                <td colspan="3" class="text-end pe-3">Total</td>
                <td class="text-center">${g_order}</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-center">${g_available}</td>
                <td></td>
                <td></td>
            </tr>`;

            $(selector + " tbody").html(rows);
            $(selector + " tfoot").html(foothtml);
        },

        error: function () {
            $(selector + " tbody").html(
                '<tr><td colspan="11" class="text-center text-danger">Error loading orders</td></tr>'
            );
        }
    });
}




/*function buildProductionRows(
    breakdown,
    clientid,
    productid,
    sono,
psono,
    stockorderNo,
    orderqty,
    colspan = 9,
) { 
    if (!Array.isArray(breakdown) || breakdown.length === 0) {
        return "";
    }

    let html = "";

    breakdown.forEach(b => {

        html += `
        <tr class="production-row bg-light d-none"
            data-clientid="${clientid}"
            data-product_id="${productid}"
            data-stockorder="${stockorderNo}"
            data-orderqty="${orderqty}"
            data-stage="${b.stage}"
        >
            <td></td>
        `;

         
        if (b.stage === "Trader Warehouse") {
            html += `
                <td>
                    <input type="checkbox" class="chkorder" style="transform:scale(1.2)">
                </td>
            `;
        } else {
            html += `<td></td>`;
        }

        html += `
            <td stage colspan="${colspan - 4}" class="ps-4 text-muted">
                ↳ ${b.stage}
            </td>
            <td class="fw-bold">${b.allocatedqty}</td>
            <td class=" fw-bold">${b.readydate || ''}</td>
            <td></td>
        </tr>`;
    });

    return html;
}
*/



 
function loadhistory_client(){    
    let clientid = $("#tblorder").data("clientid");
    let productid =  $("#tblorder").data("productid"); 
    let psono =  $("#tblorder").data("psono"); 
    let stockorder_no = $("#tblorder").data("stockorder_no");  
    let promiseddt =  $("#tblorder").data("expecteddelivery");

    $("#phonestage1").show();
    $(".initialquestion").empty();

    $("#phonestage2").hide();
    $(".deliverysummary").empty();


    $("#phonestage3").hide();
    $(".freightquestion").empty();


    $("#phonestage4").hide(); 

    $("#txttoemail").val('');
    $("#txtccemail").val('');
    $("#txtemailbody").val(''); 
    historydata(productid,psono,stockorder_no,clientid,promiseddt);
    getpayment_history();
}
function historydata(productid, psono, stockorder_no, clientid, promiseddt) { 
    let parts = promiseddt.split("-");
    newpromisedt = parts[2] + "-" + parts[1] + "-" + parts[0];
    let selector = '#tblhistory';
    $(selector + " tbody").empty();
    let projectid = $("#hidden_projectid").val();
    let formData = new FormData();
    formData.append('projectid', projectid);
    formData.append('productid', productid);
    formData.append('psono', psono);
    formData.append('stockorder_no', stockorder_no);
    formData.append('clientid', clientid);
    formData.append('promiseddt', newpromisedt);
    let chkcross_select=0; 
    let stage="";
    $('#btnextraqty').removeData();
    $.ajax({
       // url: "sqlqueries/queries.php?action=pendinghistoryclient",
        url: "sqlqueries/queries_final.php?action=pendinghistoryclient",
        
        type: "POST",
        data: formData,
        dataType: "json",
        contentType: false,
        processData: false,
        cache: false,
        success: function (res) {
            if (!res || res.length === 0) {
                $(selector + " tbody").html('<tr><td colspan="7" class="text-center">No Data Found</td></tr>');
                return;
            }
            let rows = "";
            let k = 1;
            let g_order = 0;
            let g_rorder = 0;
            for (let i = 0; i < res.length; i++) {
                let r = res[i]; 
                let quantity = parseInt((r.quantity || "0").toString().replace(/[, ]/g, ""), 10) || 0;
                let readyqty = parseInt((r.readyqty || "0").toString().replace(/[, ]/g, ""), 10) || 0;

                // Use string comparison for IDs to avoid type mismatch
                const samePsono = String(r.psono || "") === String(psono || "");
                const sameStockNo = String(r.stockorder_no || "") === String(stockorder_no || "");
                const sameProduct = String(r.product_id || "") === String(productid || "");
                const sameClient = String(r.clientid || "") === String(clientid || "");                 
                let rowStyle = "";
                let selectdchk = ""; 
                let classname=""
                if (samePsono && sameStockNo && sameProduct && sameClient && readyqty > 0  && r.stage== "Trader Warehouse") { 
                      selectdchk = "checked";//"checked disabled";
                    rowStyle = 'style="font-weight:bold;"';
                    if(chkcross_select==0){
                        classname ="class ='activerow'";
                    }                    
                    chkcross_select++;
                    stage = r.actionstatus ?? '';
                }
                g_order += quantity;
                g_rorder += readyqty;
                rows += `<tr ${classname}  ${rowStyle}
                    data-phoneno ="${r.phoneno}"
                    data-clientemail ="${r.clientemail}"
                    data-invoice_address ="${r.invoice_address}"

                    data-locationid ="${r.locationid}"
                    data-locationaddress ="${r.locationaddress}"
                    data-deliverycharges ="${r.deliverycharges}"
                    data-transporttype ="${r.transporttype}"
                    data-provisiondelivery_address ="${r.provisiondelivery_address}"
                    data-provisiontransporttype ="${r.provisiontransporttype}"
                    data-actionstatus ="${r.actionstatus}"
                    data-orderstatus ="${r.orderstatus}"                      
                    data-ready_date="${(r.ready_date || '').replace(/"/g, '&quot;')}"
                    data-bookingdt="${(r.bookingdt || '').replace(/"/g, '&quot;')}"
                    data-polyhouseplantid="${(r.polyhouseplantid || '').replace(/"/g, '&quot;')}"
                    data-clientid="${(r.clientid || '')}"
                    data-product_id="${(r.product_id || '')}" 
                    data-psono="${(r.psono || '')}"
                    data-sono="${(r.sono || '')}"
                    data-stockorder_no="${(r.stockorder_no || '')}"
                    data-expecteddelivery="${(r.deliverydate || '')}"
                    data-trayid="${(r.trayid || '')}"
                    data-trayqty="${(r.trayqty || '')}"
                    data-tray_code="${(r.tray_code || '')}"
                    data-tray_name="${(r.tray_name || '')}"
                    data-cavity_count="${(r.trayqty || '')}"
                    data-cavity_size="${(r.cavity_size || '')}"
                    data-quantity="${(r.quantity || '')}"
                    data-rate="${(r.rate || '')}" 
                    data-cgst_percent="${(r.cgst_percent || '')}" 
                    data-sgst_percent="${(r.sgst_percent || '')}" 
                    data-igst_percent="${(r.igst_percent || '')}" 
                    data-discount_percent="${(r.discount_percent || '')}" 
                    data-freight_charges="${(r.deliverycharges || '')}"
                    data-bankname="${(r.bankname || '')}"
                    data-accountno="${(r.accountno || '')}"
                    data-ifsccode="${(r.ifsccode || '')}"
                    data-branch="${(r.branch || '')}"
                    data-invoicedamount="${(r.invoicedamount || '')}"
                    data-receivedamount="${(r.receivedamount || '')}"
                    data-advanceamount ="${(r.advanceamount || '')}"
                    >
                    <td class="text-center">${k++}</td>
                    <td class='productname text-start'>${r.productname || ''}</td>
                    <td class='promiseddt text-center'>${r.ddfrmt || ''}</td>
                    <td class='orderqty text-center'>${r.quantity || ''}</td>
                    <td class='stage text-center'>${r.stage || ''}</td>
                    <td class='readyqty text-center'>${r.readyqty || ''}</td>
                    <td class='readydate text-center'>${r.ready_ddformat || ''}</td>
                   `
                   /* <td class='text-center'>
                        <button type='button' class='btn btn-outline-primary btn-table-action btn-history' productid='${res[i].product_id}'>
                            <i class='fa fa-history'></i> History
                        </button>
                    </td>*/
                if(r.stage== "Trader Warehouse"){
                    rows += `<td class="row-checkbox noexport d-none">
                        <input type="checkbox" class="chkhistory" style="transform: scale(1.2);" ${selectdchk}>
                    </td>`                    
                }else{
                    rows += `<td class="row-checkbox noexport d-none"> </td>`

                } 
                rows += `<td class='readydate d-none'>${r.days_to_dispatch || ''}</td>`;

                rows += `</tr>`;
            }

            if (rows) {
                rows += `
                <tr style='font-weight:bold;' class="table-light">
                    <td></td>
                    <td></td>
                    <td class="text-end pe-3">Total</td>
                    <td class="text-center">${g_order}</td>
                    <td></td>
                    <td class="text-center">${g_rorder}</td>
                    <td></td>
                    <td class='d-none'></td>
                </tr>`;
            }
            $(selector + " tbody").html(rows);            
            if(chkcross_select>1){
                alert("Some thing is issue in sw");
                return false;
            }
            let $chk = $(".activerow").find("input.chkhistory");
            $chk.prop("checked", true).trigger("change");
            $chk.prop("disabled", true); 
            
        },

        error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
            $(selector + " tbody").html('<tr><td colspan="7" class="text-center text-danger">Error loading orders</td></tr>');
        }
    });
} 
 
/*function getansweragtquestion(questionno,psono,stockorder_no,productid,clientid){  
    var form_data = new FormData(); 
    form_data.append("projectid", $("#hidden_projectid").val());
    form_data.append("questionno", questionno);
    form_data.append("psono", psono);
    form_data.append("stockorder_no", stockorder_no);
    form_data.append("productid", productid);
    form_data.append("clientid", clientid);
    $.ajax({                
        type: "POST",    
        url: "sqlqueries/queries.php?action=getquestionanswer",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        { 
           $(".waitloaderfooter").modal("hide"); 
        if(res.length>0){  
            if(questionno==1){
                if(res[0].answer=="partially"){
                $("#partiallyqty").val(res[0].saleqty);
                $("input[name='chkquestion1'][value='partially']").prop("checked", true).trigger("change");

               }else if(res[0].answer=="yes"){
                $("input[name='chkquestion1'][value='yes']").prop("checked", true).trigger("change");

               }else if(res[0].answer=="no"){
                $("input[name='chkquestion1'][value='no']").prop("checked", true).trigger("change");

               }
            }else if(questionno==2){
                 if(res[0].answer=="delivered"){
                    $("input[name='chkquestion1_1'][value='delivered']").prop("checked", true).trigger("change");
                 } else if(res[0].answer=="pickup"){
                    $("input[name='chkquestion1_1'][value='pickup']").prop("checked", true).trigger("change");
                 }
             }  
        }           
                                
        },beforeSend(res){
            $(".waitloaderfooter").modal("show");
        } 
    });
}*/
function getpayment_history() {
    
    $("#btnSaveAll").removeData("closingbal");

    $(".paymentstatus").addClass("d-none")
    $("#tblpaymenthistory tbody").empty(); 
    $("#paymenthistory").removeClass("d-none");
    $.ajax({
         url: "sqlqueries/createledger.php",
        type: "POST",
        dataType: "json",
        data: {
            projectid: $("#hidden_projectid").val(),
            clientid: $("#tblorder").data("clientid")
        },
        beforeSend: function () {
            $("#tblpaymenthistory tbody").append(
                `<tr><td colspan="6" class="text-center">Loading...</td></tr>`
            );
        },
        success: function (res) {
            $("#tblpaymenthistory tbody").empty();

            if (!res || res.length === 0) {
                $("#tblpaymenthistory tbody").append(
                    `<tr><td colspan="6" class="text-center">No records found</td></tr>`
                );
                return;
            }

            if (res[0].success === 'error') {
                alert(res[0].message);
                return;
            }

            $.each(res, function (i, row) {
                let debit  = row.debit_amount  ? parseFloat(row.debit_amount).toFixed(2)  : '';
                let credit = row.credit_amount ? parseFloat(row.credit_amount).toFixed(2) : '';
                let bal    = row.running_balance ? parseFloat(row.running_balance).toFixed(2) : '0.00';
                 $("#btnSaveAll").data("closingbal",bal);
                $("#tblpaymenthistory tbody").append(`
                    <tr>
                        <td>${row.txn_date}</td>
                        <td class='d-none'>${row.txn_type}</td>
                        <td>${row.txn_no ?? ''}</td>
                        <td>${row.description}</td>
                        <td class="text-right">${debit}</td>
                        <td class="text-right">${credit}</td>
                        <td class="text-right font-weight-bold">${bal}</td>
                    </tr>
                `);
            $("#btnSaveAll").data("closingbal", bal);
            }); 

            let invoiceCost = parseFloat($("#btnSaveAll").data("proformainvcost")) || 0;
            let closingBal  = parseFloat($("#btnSaveAll").data("closingbal")) || 0;

            let outst = invoiceCost - closingBal;

            $("#paymentstatus").html(
                "Outstanding Amount is Rs " + outst +
                " | Current Amount is Rs " + invoiceCost
            );

            $(".paymentstatus").removeClass("d-none");

        },
        error: function (xhr) {
            $("#tblpaymenthistory tbody").empty();
            $("#tblpaymenthistory tbody").append(
                `<tr><td colspan="7" class="text-danger text-center">
                    Server Error. Please try again.
                 </td></tr>`
            );
            console.error(xhr.responseText);
        }
    });
}

 
function validateEmailForm() {
    const toEmail   = $('#txttoemail').val().trim();
    const ccEmail   = $('#txtccemail').val().trim();
    const emailBody = $('#txtemailbody').val().trim();

    // Basic email regex (RFC-compatible enough for UI validation)
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // ---- TO EMAIL (Required) ----
    if (toEmail === '') {
        alert('Please enter To Email.');
        $('#txttoemail').focus();
        return false;
    }

    if (!emailRegex.test(toEmail)) {
        alert('Please enter a valid To Email address.');
        $('#txttoemail').focus();
        return false;
    }

    // ---- CC EMAIL (Optional, comma separated) ----
    if (ccEmail !== '') {
        const ccEmails = ccEmail.split(',').map(e => e.trim());

        for (let i = 0; i < ccEmails.length; i++) {
            if (!emailRegex.test(ccEmails[i])) {
                alert('Invalid CC Email: ' + ccEmails[i]);
                $('#txtccemail').focus();
                return false;
            }
        }
    }

    // ---- EMAIL BODY (Required) ----
    if (emailBody === '') {
        alert('Email body cannot be empty.');
        $('#txtemailbody').focus();
        return false;
    }

    return true; // All validations passed
} 
 
function stage1question($chk,$enquirytype="") {
    debugger;

    let returnval = false;
    let questionIndex = 0;
    let questionData = [];

    let diff_order_readyqty = 0; 
    const r = $chk.closest("tr")
    $("#tblorder").data("historycurrentrow",$chk);

    const readyqty     = parseInt(r.find('.readyqty').text().trim(), 10) || 0;
    const orderqty     = parseInt(r.find('.orderqty').text().trim(), 10) || 0;
    const processstage = r.find('.stage').text().trim();

    /* -------- VALIDATIONS -------- */

    if (processstage !== "Trader Warehouse") return false;
    if (readyqty < orderqty) return false;

    /* -------- PROCESS -------- */

    r.css("color", "blue");

    diff_order_readyqty = readyqty - orderqty;   // FIXED LOGIC
    questionIndex++;

    questionData.push({
        index        : questionIndex,
        orderqty     : orderqty,
        readyqty     : readyqty,
        productname  : r.find('.productname').text().trim(),
        rate         : parseFloat(r.data('rate')) || 0,
        traycount    : parseInt(r.data('cavity_count'), 10) || 1,
        productid    : r.data('product_id'),
        psono        : r.data('psono'),
        sono         : r.data('sono'),
        stockorder_no: r.data('stockorder_no'),
        clientid     : r.data('clientid'),
        locationid   : r.data('locationid'),
        locationaddress: r.data('locationaddress'),
        deliverycharges: r.data('deliverycharges'),
        transporttype: r.data('transporttype'),
        provisiondelivery_address: r.data('provisiondelivery_address'),
        provisiontransporttype: r.data('provisiontransporttype'), 
        cgst_percent:  r.data('cgst_percent'),
        sgst_percent:  r.data('sgst_percent'),
        igst_percent:  r.data('igst_percent'),
        discount_percent:  r.data('discount_percent'),
        freight_charges:  r.data('freight_charges'),
        bankname:  r.data('bankname'),
        accountno:  r.data('accountno'),
        ifsccode:  r.data('ifsccode'),
        branch:  r.data('branch'),
        invoicedamount:  r.data('invoicedamount'),
        receivedamount:  r.data('receivedamount'),
        advanceamount:  r.data('advanceamount'),
        ready_date:  r.data('ready_date'),
        readydateddformat:r.find(".readydate").text(),
        promiseddformat:r.find(".promiseddt").text(),
        enquirytype :$enquirytype,
         displayquestionno:$displayquestionno
    });

    returnval = true;

    /* -------- GENERATE HTML -------- */

    const totalQuestions = questionData.length;
    let html = [];

    questionData.forEach(q => {

        if (diff_order_readyqty === 0) {

            html.push(
                generatequestion_html_exact({
                    ...q,
                    totalQuestions
                })
            );

        }  else if (diff_order_readyqty > 0) {

            html.push(
                generatequestion_html_acess({
                    ...q,
                    totalQuestions
                })
            );
        }else if (diff_order_readyqty < 0) {

           alert("no condition")
        }
    });
    $displayquestionno++;
    $('.showquestion1').css({
    'background-color': '#fff3cd',
    'transition': 'background-color 0.4s ease'
});
    $('.initialquestion').append(html.join(''));

    $('#phonestage1').removeClass('d-none').prop('open', true);

    
    const $lastQuestion = $('.initialquestion').last();
    scrollToLastEnabled($lastQuestion);


    return returnval;
}
function showError($question, msg) {
    $question
        .removeClass('border-success')
        .addClass('border-danger');

    $question.find('.qtyremark')
        .removeClass('d-none')
        .text(msg);
}
 
 function generatequestion_html_exact(data) {
    debugger
    const {
        index, orderqty, readyqty, productname, rate,
        traycount, productid, psono, sono, stockorder_no,
        clientid, locationid, locationaddress, deliverycharges,
        transporttype, provisiondelivery_address, provisiontransporttype,
        readydate, cgst_percent, sgst_percent, igst_percent,
        discount_percent, freight_charges, bankname, accountno, ifsccode, branch,
        invoicedamount, receivedamount, advanceamount, enquirytype,ready_date,readydateddformat, promiseddformat,displayquestionno
    } = data;

    const uid = `${productid}_${index}`;
    const initialCost = orderqty * rate;
    const safeAdvance = Number(advanceamount) || 0;
    let balancepayment = safeAdvance-initialCost  ;
    const diffqty = readyqty - orderqty;
     balancepayment = balancepayment >= 0 ? 0 : Math.abs(balancepayment)
if(!enquirytype){
    return `
        <div class="showquestion1 border p-2 mb-3 ${index === 1 ? 'activerow' : ''}" 
            data-productid="${productid}"
            data-productname="${productname}"
            data-psono="${psono}"
            data-sono="${sono}"
            data-stockorder_no="${stockorder_no}"
            data-rate="${rate}"
            data-traycount="${traycount}"
            data-orderqty="${orderqty}"
            data-readyqty="${readyqty}"
            data-clientid="${clientid}"
            data-locationid="${locationid}"
            data-locationaddress="${locationaddress}"
            data-deliverycharges="${deliverycharges}"
            data-transporttype="${transporttype}"
            data-provisiondelivery_address="${provisiondelivery_address}"
            data-provisiontransporttype="${provisiontransporttype}"
            data-cgst_percent ="${cgst_percent}"
            data-sgst_percent ="${sgst_percent}"
            data-igst_percent ="${igst_percent}"
            data-discount_percent ="${discount_percent}"
            data-freight_charges ="${freight_charges}"
            data-bankname ="${bankname}"
            data-accountno ="${accountno}"
            data-ifsccode ="${ifsccode}"
            data-branch ="${branch}"
            data-invoicedamount ="${invoicedamount}"
            data-receivedamount ="${receivedamount}"
            data-advanceamount ="${advanceamount}"
            data-enquirytype ="${enquirytype}"
             data-ready_date ="${ready_date}"
             data-readydateddformat="${readydateddformat}"
             data-promiseddformat="${promiseddformat}"
             data-questiontype ="Exact match with order"
         >

        <div class="product-summary mb-2"> 
            <div class ="text-center"><b>Question No :${displayquestionno}</b> </div>
            <div>Your Order Number:<b> ${sono}</b></div>
            <div>For Product:<b> ${productname}</b></div>
            <div>No Of Plants:<b> ${readyqty}</b></div>
            <div>Is ready for delivery on:<b> ${readydateddformat}</b></div>
            <div>The agreed Rate: Rs.<b>${rate.toFixed(2)}</b></div>
            <div>Total Payable:<b> Rs. </b><span class="cost" style="font-weight:bold;">${initialCost.toFixed(2)}</span> </div>
            <div>You have paid Rs. <span class="advancepayment" style="font-weight:bold;">${safeAdvance.toFixed(2)}</span> in advance against this order.</div>
             <div>Balance: Rs.<span class="balancepayment" style= "color:blue;font-weight:bold;"> ${balancepayment.toFixed(2)}</span></div> 
              
             <div style="color:blue; arrangepayment"> Please arrange to pay Rs. <span class="balancepayment" style="font-weight:bold;">${balancepayment.toFixed(2)}</span>  for <b>${productname} </b> for  <span class="finalsaleqty" style="font-weight:bold;">${orderqty} plants.</span></div>


            <div class="qtyremark alert alert-warning py-1 px-2 mt-1 d-none"></div>
        </div>

         <div class="responsesummary mb-2">
            <b>Response</b>
            <div class="row mt-2">           

                <div class="col-md-6 col-12">
                    <label class="d-flex align-items-center mb-2">
                        <input type="radio" name="radiooption_${uid}" value="agreeorderqty" class="mr-2" checked>
                        Agree with the ordered quantity
                    </label>
                </div>
                <div class="col-md-6 col-12"></div>

                <div class="col-md-6 col-12">
                    <label class="d-flex align-items-center mb-2">
                        <input type="radio" name="radiooption_${uid}" value="partially" class="mr-2">
                        Sorry, I want partial quantity
                    </label>
                </div>

                <!-- Partial Qty Input -->
                <div class="col-md-6 col-12" id="partialQty_${uid}">
                    <input type="text"
                           class="form-control radiooption_partiallyqty"
                           placeholder="Qty"
                           style="max-width:120px;"
                           oninput="this.value=this.value.replace(/[^0-9]/g,'');">
                </div>

            </div>
        </div>


        <div class="text-center mt-3">
            <button type="button" class="btn btn-success btnSaveAll">Yes,Continue</button>
            <button type="button" class="btn btn-secondary ml-2 btnExit">Close Conversation </button>
        </div>
    </div>`;
} 
return `<div class="showquestion1 border p-2 mb-3 ${index === 1 ? 'activerow' : ''}"
    data-productid="${productid}"
    data-productname="${productname}"
    data-psono="${psono}"
    data-sono="${sono}"
    data-stockorder_no="${stockorder_no}"
    data-rate="${rate}"
    data-traycount="${traycount}"
    data-orderqty="${orderqty}"
    data-readyqty="${readyqty}"
    data-clientid="${clientid}"
    data-locationid="${locationid}"
    data-locationaddress="${locationaddress}"
    data-deliverycharges="${deliverycharges}"
    data-transporttype="${transporttype}"
    data-provisiondelivery_address="${provisiondelivery_address}"
    data-provisiontransporttype="${provisiontransporttype}"
    data-cgst_percent="${cgst_percent}"
    data-sgst_percent="${sgst_percent}"
    data-igst_percent="${igst_percent}"
    data-discount_percent="${discount_percent}"
    data-freight_charges="${freight_charges}"
    data-bankname="${bankname}"
    data-accountno="${accountno}"
    data-ifsccode="${ifsccode}"
    data-branch="${branch}"
    data-invoicedamount="${invoicedamount}"
    data-receivedamount="${receivedamount}"
    data-advanceamount="${advanceamount}"
    data-enquirytype="${enquirytype}"
    data-ready_date="${ready_date}"
    data-readydateddformat="${readydateddformat}"
    data-promiseddformat="${promiseddformat}" 
    data-questiontype="Consolidate order">

    <div class="consolideord-summary mb-2">
    <div class ="text-center"><b>Question No :${displayquestionno}</b> </div>
        <p>
            We have <b>${orderqty}</b> plants of <b>${productname}</b> ready against your order
            <b>${sono}</b> (Delivery Date: ${promiseddformat}). Would you like to combine the order?
        </p>

        <p> Agreed Rate: Rs. <b>${rate.toFixed(2)}</b></p>
        <p>Total Payable: Rs. <span class="cost" style="font-weight:bold;" >${initialCost.toFixed(2)}</span></p>

        <div class="qtyremark alert alert-warning py-1 px-2 mt-1 d-none"></div>
    </div>

     <div class="responsesummary mb-2">
        <b>Response</b>

        <div class="row mt-2">

            <div class="col-md-6 col-12">
                <label class="d-flex align-items-center mb-2">
                    <input type="radio" name="radiooption_${uid}" value="later" class="mr-2">
                    I want to decide later
                </label>
            </div>

            <div class="col-md-6 col-12">
                <label class="d-flex align-items-center mb-2">
                    <input type="radio" name="radiooption_${uid}" value="agreeorderqty" class="mr-2" checked>
                    Agree with the ordered quantity
                </label>
            </div>
 

            <div class="col-md-6 col-12">
                <label class="d-flex align-items-center mb-2">
                    <input type="radio" name="radiooption_${uid}" value="partially" class="mr-2">
                    Sorry, I want partial quantity
                </label>
            </div>

            <!-- Partial Qty Input -->
            <div class="col-md-6 col-12" id="partialQty_${uid}">
                <input type="text"
                       class="form-control radiooption_partiallyqty"
                       placeholder="Qty"
                       style="max-width:120px;"
                       oninput="this.value=this.value.replace(/[^0-9]/g,'');">
            </div>

        </div>
    </div>


    <div class="text-center mt-3">
        <button type="button" class="btn btn-success btnSaveAll">Yes, Continue</button>
        <button type="button" class="btn btn-secondary ml-2 btnExit">Close Conversation</button>
    </div>
</div>`


}
  
function generatequestion_html_acess(data) {
debugger
    const {
        index, orderqty, readyqty, productname, rate,
        traycount, productid, psono, sono, stockorder_no,
        clientid, locationid, locationaddress, deliverycharges,
        transporttype, provisiondelivery_address, provisiontransporttype,
        readydate, cgst_percent, sgst_percent, igst_percent,
        discount_percent, freight_charges, bankname, accountno, ifsccode, branch,
        invoicedamount, receivedamount, advanceamount, enquirytype, ready_date ,readydateddformat ,promiseddformat,displayquestionno
    } = data;

    const uid = `${productid}_${index}`;
    const initialCost = orderqty * rate;
    const safeAdvance = Number(advanceamount) || 0;
    let balancepayment = safeAdvance-initialCost;

    const diffqty = readyqty - orderqty;
   balancepayment = balancepayment >= 0 ? 0 : Math.abs(balancepayment)

    /* ================= NORMAL FLOW ================= */

    if (!enquirytype) {

        return `
<div class="showquestion1 border p-2 mb-3 ${index === 1 ? 'activerow' : ''}"
    data-productid="${productid}"
    data-productname="${productname}"
    data-psono="${psono}"
    data-sono="${sono}"
    data-stockorder_no="${stockorder_no}"
    data-rate="${rate}"
    data-traycount="${traycount}"
    data-orderqty="${orderqty}"
    data-readyqty="${readyqty}"
    data-clientid="${clientid}"
    data-locationid="${locationid}"
    data-locationaddress="${locationaddress}"
    data-deliverycharges="${deliverycharges}"
    data-transporttype="${transporttype}"
    data-provisiondelivery_address="${provisiondelivery_address}"
    data-provisiontransporttype="${provisiontransporttype}"
    data-cgst_percent="${cgst_percent}"
    data-sgst_percent="${sgst_percent}"
    data-igst_percent="${igst_percent}"
    data-discount_percent="${discount_percent}"
    data-freight_charges="${freight_charges}"
    data-bankname="${bankname}"
    data-accountno="${accountno}"
    data-ifsccode="${ifsccode}"
    data-branch="${branch}"
    data-invoicedamount="${invoicedamount}"
    data-receivedamount="${receivedamount}"
    data-advanceamount="${advanceamount}"
    data-enquirytype="${enquirytype}"
    data-ready_date="${ready_date}"
    data-readydateddformat="${readydateddformat}"

    data-promiseddformat="${promiseddformat}"
    data-questiontype="Exact match with order">

    <div class="product-summary mb-2">
    <div class ="text-center"><b>Question No :${displayquestionno}</b> </div>
        <div>Your Order Number: <b>${sono}</b></div>
        <div>For Product:<b>${productname}</b> </div>
        <div>Order Quantity : <b>${orderqty}</b></div>
        <div>Ready Quantity:<b>${readyqty}</b> </div>
        <div>Is ready for delivery on:<b>${readydateddformat}</b> </div>
        <div>The Agreed Rate: Rs.<b> ${rate.toFixed(2)}</b></div>
        <div>Total Payable is:Rs. <span class="cost" style="font-weight:bold;">${initialCost.toFixed(2)}</span> </div>
        <div>You have paid Rs. <span class="advancepayment" style="font-weight:bold;">${safeAdvance.toFixed(2)}</span> in advance against this order.</div>

        <div>Balance is Rs:<span class="balancepayment" style= "color:blue;font-weight:bold;"> ${balancepayment.toFixed(2)}</span></div> 

        <div>We have <b> ${diffqty} </b> extra plants for your order</div>
        
       
        <div style="color:blue; arrangepayment"> Please arrange to pay Rs. <span class="balancepayment" style="font-weight:bold;">${balancepayment.toFixed(2)}</span>  for <b>${productname} </b> for  <span class="finalsaleqty" style="font-weight:bold;">${orderqty} plants.</span></div>

            <div class="qtyremark alert alert-warning py-1 px-2 mt-1 d-none"></div>
    </div>
    <div class="responsesummary mb-2">
        <b>Response</b>

        <div class="row mt-2">
     

            <div class="col-md-6 col-12">
                <label class="d-flex align-items-center mb-2">
                    <input type="radio" name="radiooption_${uid}" value="agreeorderqty" class="mr-2" checked>
                    Agree with the ordered quantity
                </label>
            </div>

            <div class="col-md-6 col-12">
                <label class="d-flex align-items-center mb-2">
                    <input type="radio" name="radiooption_${uid}" value="agreeextraqty" class="mr-2" >
                    Agree with the extra quantity
                </label>
            </div>

            <div class="col-md-6 col-12">
                <label class="d-flex align-items-center mb-2">
                    <input type="radio" name="radiooption_${uid}" value="partially" class="mr-2">
                    Sorry, I want partial quantity
                </label>
            </div>

            <!-- Partial Qty Input -->
            <div class="col-md-6 col-12" id="partialQty_${uid}">
                <input type="text"
                       class="form-control radiooption_partiallyqty"
                       placeholder="Qty"
                       style="max-width:120px;"
                       oninput="this.value=this.value.replace(/[^0-9]/g,'');">
            </div>

        </div>
    </div>



    <div class="text-center mt-3">
        <button type="button" class="btn btn-success btnSaveAll">Yes, Continue</button>
        <button type="button" class="btn btn-secondary ml-2 btnExit">Close Conversation</button>
    </div>
</div>`;
    }

    /* ================= ENQUIRY / CONSOLIDATE FLOW ================= */

    return `
<div class="showquestion1 border p-2 mb-3 ${index === 1 ? 'activerow' : ''}"
    
    data-productid="${productid}"
    data-productname="${productname}"
    data-psono="${psono}"
    data-sono="${sono}"
    data-stockorder_no="${stockorder_no}"
    data-rate="${rate}"
    data-traycount="${traycount}"
    data-orderqty="${orderqty}"
    data-readyqty="${readyqty}"
    data-clientid="${clientid}"
    data-locationid="${locationid}"
    data-locationaddress="${locationaddress}"
    data-deliverycharges="${deliverycharges}"
    data-transporttype="${transporttype}"
    data-provisiondelivery_address="${provisiondelivery_address}"
    data-provisiontransporttype="${provisiontransporttype}"
    data-cgst_percent="${cgst_percent}"
    data-sgst_percent="${sgst_percent}"
    data-igst_percent="${igst_percent}"
    data-discount_percent="${discount_percent}"
    data-freight_charges="${freight_charges}"
    data-bankname="${bankname}"
    data-accountno="${accountno}"
    data-ifsccode="${ifsccode}"
    data-branch="${branch}"
    data-invoicedamount="${invoicedamount}"
    data-receivedamount="${receivedamount}"
    data-advanceamount="${advanceamount}"
    data-enquirytype="${enquirytype}"
    data-ready_date="${ready_date}"
    data-readydateddformat="${readydateddformat}"

    data-promiseddformat="${promiseddformat}"
    data-questiontype="Consolidate order">

    <div class="consolideord-summary mb-2">
    <div class ="text-center"><b>Question No :${displayquestionno}</b> </div>
        <p>
           We have <b>${orderqty}</b> plants of <b>${productname}</b> ready against your order
            <b>${sono}</b> (Delivery Date: ${promiseddformat}). The extra plants is
            <b>${diffqty}</b> for your order.<br>
            Would you like to combine the order?

            <div class="qtyremark alert alert-warning py-1 px-2 mt-1 d-none"></div>
        </p>
 
        <p>Agreed Rate: Rs.<b>${rate.toFixed(2)}</b></p>
        <p>Total Payable: Rs. <span class="cost" style="font-weight:bold;">${initialCost.toFixed(2)}</span></p>
    </div>

    
     <div class="responsesummary mb-2">
        <b>Response</b>

        <div class="row mt-2">

            <div class="col-md-6 col-12">
                <label class="d-flex align-items-center mb-2">
                    <input type="radio" name="radiooption_${uid}" value="later" class="mr-2">
                    I want to decide later
                </label>
            </div>

            <div class="col-md-6 col-12">
                <label class="d-flex align-items-center mb-2">
                    <input type="radio" name="radiooption_${uid}" value="agreeorderqty" class="mr-2" checked>
                    Agree with the ordered quantity
                </label>
            </div>

            <div class="col-md-6 col-12">
                <label class="d-flex align-items-center mb-2">
                    <input type="radio" name="radiooption_${uid}" value="agreeextraqty" class="mr-2">
                    Agree with the extra quantity
                </label>
            </div>
            <div class="col-md-6 col-12"></div>
            <div class="col-md-6 col-12">
                <label class="d-flex align-items-center mb-2">
                    <input type="radio" name="radiooption_${uid}" value="partially" class="mr-2">
                    Sorry, I want partial quantity
                </label>
            </div>

            <!-- Partial Qty Input -->
            <div class="col-md-6 col-12" id="partialQty_${uid}">
                <input type="text"
                       class="form-control radiooption_partiallyqty"
                       placeholder="Qty"
                       style="max-width:120px;"
                       oninput="this.value=this.value.replace(/[^0-9]/g,'');">
            </div>

        </div>
    </div>


    <div class="text-center mt-3">
        <button type="button" class="btn btn-success btnSaveAll">Yes, Continue</button>
        <button type="button" class="btn btn-secondary ml-2 btnExit">Close Conversation</button>
    </div>
</div>`;
}
function getconsolidateorder(activeorder_readydate) {
    let consolideord = [];
    const activeDate = new Date(activeorder_readydate);
    activeDate.setHours(0, 0, 0, 0);
    const maxDate = new Date(activeDate);
    maxDate.setDate(maxDate.getDate() + 5); // +5 days
    maxDate.setHours(23, 59, 59, 999);
    $('#tblhistory tbody tr').each(function () {
        const r = $(this);
        if (r.hasClass('activerow')) return;
        const consolidate_readyqty = parseInt(r.find('.readyqty').text(), 10) || 0;
        const consolidate_orderqty = parseInt(r.find('.orderqty').text(), 10) || 0;
        const processstage = r.find('.stage').text().trim();
        const availablereadydt = r.data('ready_date');
        if (!availablereadydt) return;
        const rowDate = new Date(availablereadydt);
        rowDate.setHours(0, 0, 0, 0);
        /* -------- DATE BETWEEN CHECK -------- */
        const isWithinRange =
            rowDate >= activeDate &&
            rowDate <= maxDate;
        if (processstage === "Trader Warehouse") {
            //if (consolidate_readyqty >= consolidate_orderqty &&  isWithinRange) {
            if (consolidate_readyqty >= consolidate_orderqty || isWithinRange) {
                r.css("color", "blue");
                consolideord.push({
                    productid: r.data('product_id'),
                    psono: r.data('psono'),
                    sono: r.data('sono'),
                    stockorder_no: r.data('stockorder_no'),
                    availablereadydate: availablereadydt,
                    tblrowevent: r,
                    clientstatus: ''


                });
            }
        }
    });

    if (!consolideord.length) return false;
    /* ---------- SORT BY READY DATE ---------- */
    consolideord.sort((a, b) =>
        new Date(a.availablereadydate) - new Date(b.availablereadydate)
    );
    $("#tblorder").data("consolidateorder", consolideord);
    /* ---------- LOAD FIRST PENDING ---------- */
    const firstItem = consolideord.find(o => o.clientstatus === '');
    if (firstItem) {
        stage1question(firstItem.tblrowevent,"consolidateorder");
    }
    return true
}
function updateClientStatusByKey(productid, psono, stockorder_no, status) {
    consolideord=$("#tblorder").data("consolidateorder")||[];
    const item = consolideord.find(o =>
        o.productid === productid &&
        o.psono === psono &&
        o.stockorder_no === stockorder_no
    );

    if (item) {
        item.clientstatus = status;
    }

    const firstItem = consolideord.find(o => o.clientstatus === '');
    if (firstItem) {
        stage1question(firstItem.tblrowevent,"consolidateorder");
        return true;
    }
    return false
}
function showMsgModal(message, options = {}) {
    const {
        proceedText = 'Proceed Anyway',
        seekText = 'Seek Permission',
        titleText = 'Permission Required',
        proceedClass = 'btn-success',
        seekClass = 'btn-info',
        showProceed = true,
        showSeek = true
    } = options;
    $('#msgTitle').text(titleText);
    $('#textMessage').html(message);
    const $btnYes = $('#btnyes');
    const $btnNo  = $('#btnno');
    // Reset buttons
    $btnYes
        .attr('class', 'btn ' + proceedClass)
        .find('.btn-text').text(proceedText);

    $btnNo
        .attr('class', 'btn ' + seekClass)
        .text(seekText);

    // Show / Hide buttons
    $btnYes.toggle(showProceed);
    $btnNo.toggle(showSeek);

    // Reset spinner
    $btnYes.prop('disabled', false);
    $btnYes.find('.spinner-border').addClass('d-none');
    $btnYes.find('.btn-text').removeClass('d-none');

    let resolved = false;

    return new Promise(function (resolve) {

        $('#msgModal').modal({
            backdrop: 'static',
            keyboard: false
        });

        $btnYes.off('click').on('click', function () {
            resolved = true;

            // Show spinner
            $btnYes.prop('disabled', true);
            $btnYes.find('.btn-text').addClass('d-none');
            $btnYes.find('.spinner-border').removeClass('d-none');

            setTimeout(() => {
                $('#msgModal').modal('hide');
                resolve('proceed');
            }, 600);
        });

        $btnNo.off('click').on('click', function () {
            resolved = true;
            $('#msgModal').modal('hide');
            resolve('seek');
        });

        $('#msgModal').off('hidden.bs.modal').on('hidden.bs.modal', function () {
            if (!resolved) resolve('cancel');
        });
    });
}
function question1save($question,allocationtype,saleqty){
    debugger;
    //$question.removeClass('bg-warning');
    $question.css('background-color', '');
    let allocationData = $("#tblorder").data("allocationorders") || []; 
    let proformainvcost = 0;
    const productid     = $question.data('productid');
    const productname   = $question.data('productname') || '';
    const psono         = $question.data('psono');
    const sono          = $question.data('sono');
    const stockorder_no = $question.data('stockorder_no');
    const clientid      = $question.data('clientid');
    const traycount     = parseInt($question.data('traycount'), 10) || 1;
    const orderqty      = parseInt($question.data('orderqty'), 10) || 0;
    const readyqty      = parseInt($question.data('readyqty'), 10) || 0;
    const invcost = parseFloat($question.find('.cost').text()) || 0; 
    let neworderqty = 0;  
    /* ---------------- PUSH PAYLOAD ---------------- */
    allocationData.push({
        productid,
        productname,
        psono,
        sono,
        stockorder_no,
        clientid,
        allocationtype,
        orderqty,
        readyqty,
        saleqty, 
        locationid: $question.data('locationid'),
        locationaddress: $question.data('locationaddress'),
        deliverycharges: $question.data('deliverycharges'),
        transporttype: $question.data('transporttype'),
        provisiondelivery_address: $question.data('provisiondelivery_address'),
        provisiontransporttype: $question.data('provisiontransporttype'),
        rate         : parseFloat($question.data('rate')) || 0,
        traycount    : traycount, 

        cgst_percent:  $question.data('cgst_percent'),
        sgst_percent:  $question.data('sgst_percent'),
        igst_percent:  $question.data('igst_percent'),
        discount_percent:  $question.data('discount_percent'),
        freight_charges:  $question.data('freight_charges'),
        bankname:  $question.data('bankname'),
        accountno:  $question.data('accountno'),
        ifsccode:  $question.data('ifsccode'),
        branch:  $question.data('branch'),
        invoicedamount:  $question.data('invoicedamount'),
        receivedamount:  $question.data('receivedamount'),
        advanceamount:  $question.data('advanceamount'),
        enquirytype : $question.data('enquirytype'),
        ready_date : $question.data('ready_date') 
    });
    /* ---------------- SAVE ---------------- */
    $.post('save/stockallocation_array.php', {
        allocationData: JSON.stringify(allocationData),
        projectid: $('#hidden_projectid').val(),
        recipientname: $('#recipientname').val(),
        contactno: $('#contactno').val(),
        communicationby: $('#hidden_loginname').val(),
        communication_date: $('#communicationdate').val(),
        questionno: 1,
        question: $question.data('questiontype') +' (Stock allocation decision)'
    }, function (res) {
        resp = res.trim();
        if (resp === "done") {
            //$question.addClass('bg-warning');

           $('.showquestion1').addClass('question-warning');

            const uniqueAllocationData = Array.from(
                new Map(
                    allocationData.map(item => [
                        `${item.productid}|${item.productname}|${item.psono}|${item.sono}|${item.stockorder_no}|${item.clientid}`,
                        item
                    ])
                ).values()
            );

            debugger ;
             $("#tblorder").data("allocationorders",uniqueAllocationData);


             if(!$question.data('enquirytype')){ 
                if(!getconsolidateorder($question.data('ready_date'))){ //true
                   // getpayment_history();
                    deliverysummary(); 
                } 
            }else{
                if(!updateClientStatusByKey(productid, psono, stockorder_no, "done")){
                   // getpayment_history();
                    deliverysummary(); 
                }
            }               
        }else {
            alert("Server Response: " + resp);
        }
    });
}


function recalcQuestion(box) {
    const traycount = parseInt(box.data('traycount'), 10) || 1;
    const orderqty  = parseInt(box.data('orderqty'), 10) || 0;
    const readyqty  = parseInt(box.data('readyqty'), 10) || 0;
    const rate      = parseFloat(box.data('rate')) || 0;
    const advanceamount      =parseInt(box.data('advanceamount'))
    const latestamt=  0;
    let balanceamt= advanceamount;
    const decision = box.find("input[type='radio']:checked").val() || '';
    let qty = 0;
    let trays = 0;
    let cost  = 0;
    const remarkBox = box.find('.qtyremark');
    remarkBox.addClass('d-none').text('');
    switch (decision) {
        case 'partially':
            qty = parseInt(
                box.find('.radiooption_partiallyqty').val() || '0',
                10
            );
            break;
        case 'agreeorderqty':
            qty = orderqty;
            break;

        case 'agreeextraqty':
            qty = readyqty; // includes extra qty
            break;

        case 'later':
        default:
            qty = 0; // no calculation yet
            break;
    }

    /* ---------- CALCULATION ---------- */

    if (qty > 0 && decision ==="partially") {

        // ❗ tray divisibility check
        if (qty % traycount !== 0) {

            let minQty = Math.floor(qty / traycount) * traycount;
            let maxQty = Math.ceil(qty / traycount) * traycount;

            if (minQty === 0) minQty = traycount;

            remarkBox
                .removeClass('d-none')
                .text(`Nearest valid quantities: ${minQty} or ${maxQty}`);
        }

        trays = Math.ceil(qty / traycount); 
        
    }else if (qty > 0){
        trays = Math.ceil(qty / traycount); 
    } 
    cost  = (qty * rate);
    balanceamt=advanceamount -cost;

    /* ---------- UPDATE UI ---------- */
debugger
    box.find('.trayreq').text(trays);
    box.find('.cost').text(cost.toFixed(2));
    balanceamt = balanceamt >= 0 ? 0 : Math.abs(balanceamt)
    box.find('.balancepayment').text(balanceamt.toFixed(2));

    /* if(balanceamt<=0)
     {
        box.find('.arrangepayment').addClass('d-none');
     }else{*/
    box.find('.arrangepayment').removeClass('d-none');

    box.find('.finalsaleqty').text(qty); 
     /*}*/

    
}

function createDeliveryTable() {
    const tableHtml = `
        <table id="deliveryTable" class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Order No</th>
                    <th>Product Name</th>
                    <th>Sale Qty</th>
                    <th>No. of Trays</th> 
                    <th>Rate</th> 
                    <th>Amount Payable</th>
                    <th>Advance</th> 
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-right">Total</th>
                    <th id="totalsaleqty">0</th>
                    <th id="totalTrays">0</th>
                    <th ></th>
                    <th id="totalamt">0</th>
                    <th id="totaladv">0</th>
                    <th id="totalbal"></th>
                </tr>
            </tfoot>
        </table>
    `;

    $("#phonestage2 .deliverysummary").html(tableHtml);
    $("#phonestage2").show();

}
function fillDeliveryTable() {
    const allocationData = $("#tblorder").data("allocationorders") || [];
    const $tbody = $("#deliveryTable tbody");
    $tbody.empty();
    let totalTrays    = 0;
    let totalsaleQty  = 0;
    let Gtotalamt     = 0;
    let Gadvance      = 0;
    let runningbal    = 0;
    // Track advance usage only once per PSO
    const usedAdvanceByPsono = new Set();
    allocationData.forEach(item => {
        /* ---------- BASIC CALCULATIONS ---------- */

        const saleQty   = parseFloat(item.saleqty) || 0;
        const trayCount = Math.max(1, parseInt(item.traycount, 10) || 1);
        const rate      = parseFloat(item.rate) || 0;

        const calamt = rate * saleQty;
        const trays  = Math.round(saleQty / trayCount);

        totalTrays   += trays;
        totalsaleQty += saleQty;
        Gtotalamt    += calamt;

        /* ---------- ADVANCE LOGIC (ONCE PER PSONO) ---------- */

        let advanceamount = 0;
        if (!usedAdvanceByPsono.has(item.psono)) {
            advanceamount = parseFloat(item.receivedamount) || 0;
            if (advanceamount > 0) {
                usedAdvanceByPsono.add(item.psono);
                Gadvance += advanceamount;
            }
        }
        /* ---------- RUNNING BALANCE ---------- */
        runningbal = runningbal + advanceamount - calamt;
        const row = `
            <tr>
                <td>${item.sono}</td>
                <td>${item.productname}</td>
                <td class="text-end">${saleQty}</td>
                <td class="text-end">${trays}</td>
                <td class="text-end">${rate.toFixed(2)}</td>
                <td class="text-end">${calamt.toFixed(2)}</td>
                <td class="text-end">${advanceamount ? advanceamount.toFixed(2) : ''}</td>
                <td class="text-end">${runningbal.toFixed(2)}</td>
            </tr>
        `;

        $tbody.append(row);
    });


    $("#totalTrays").text(totalTrays);
    $("#totalsaleqty").text(totalsaleQty.toFixed(2));
    $("#totalamt").text(Gtotalamt.toFixed(2));
    $("#totaladv").text(Gadvance.toFixed(2));
}

function deliverysummary() {
    createDeliveryTable();   // step 1
    fillDeliveryTable();     // step 2
    createPickupDeliveryUI();
};

// 1️⃣ Function to create the delivery options UI
function createPickupDeliveryUI() {
    const allocationData = $("#tblorder").data("allocationorders") || [];
    const $container = $(".freightquestion");

    $container.empty();
    
    allocationData.forEach((item, index) => {

        const saleQty   = parseFloat(item.saleqty) || 0;
        const trayCount = parseInt(item.traycount, 10) || 1;
        const trays     = Math.round(saleQty / trayCount);

        // Default selection = pickup
        const deliveryOption = item.deliveryOption || "pickup";
 
        const html = `<div class="border p-2 mb-3">

            <!-- PRODUCT INFO -->
            <div class="fw-bold mb-2">
                <div>Product: <span class="text-primary">${item.productname}</span></div>
                <div>Order No: <span class="text-success">${item.sono}</span></div>
                <div>No. of Trays: <span class="text-danger">${trays}</span></div>
            </div>

            <!-- DELIVERY OPTIONS -->
            <div class="mt-2">
                <label class="mr-3">
                    <input type="radio"
                           class="deliveryOptionRadio"
                           name="deliveryOption_${index}"
                           value="pickup"
                           data-index="${index}"
                           ${deliveryOption === "pickup" ? "checked" : ""}>
                    Pickup
                </label>

                <label>
                    <input type="radio"
                           class="deliveryOptionRadio"
                           name="deliveryOption_${index}"
                           value="delivered"
                           data-index="${index}"
                           ${deliveryOption === "delivered" ? "checked" : ""}>
                    Delivered
                </label>
            </div>

            <!-- PICKUP TABLE -->
            <div class="mt-2 ${deliveryOption === "pickup" ? "" : "d-none"}"
                 id="pickupTableBox_${index}">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>No. of Trays</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
         
           <!-- FREIGHT CHARGES -->
            <div class="mt-2 ${deliveryOption === "delivered" ? "" : "d-none"}"
                 id="divfreightcharges_${index}">
                 
                <div class="d-flex align-items-center gap-2">
                    <label class="mb-0 fw-bold">Freight Charges</label>

                    <input type="text"
                           class="form-control newfreightcharges"
                           style="max-width:120px"
                           value="${item.freightcharges || ""}"
                           data-index="${index}"
                           oninput="validateDecimal(this)">
                </div>
            </div>


            <!-- DELIVERY ADDRESS -->
            <div class="mt-2 ${deliveryOption === "delivered" ? "" : "d-none"}"
                 id="addressBox_${index}">
                <label class="fw-bold">Delivery Address</label>
                <textarea class="form-control addressInput"
                          rows="2"
                          data-index="${index}">${item.deliveryaddress || ""}</textarea>
            </div>

             <div class="text-center mt-3">
                <button type="button" class="btn btn-success btnupdateaddress">Update Address</button>
                <button type="button" class="btn btn-secondary ml-2 btnExit">Close Conversation</button>
            </div>

        </div>`;     

        $container.append(html);

        // Load pickup stock initially if pickup selected
        if (deliveryOption === "pickup") {
            loadPickupStock(index, item);
        }
    });
     
        html=`<div class="text-right mt-4">
                <button type="button" class="btn btn-warning btnnext">Send Email</button> 
            </div>`
            $container.append(html); 

    const $lastQuestion = $('.freightquestion').first();
    scrollToLastEnabled($lastQuestion);
    $("#phonestage3").show();

}

function loadPickupStock(index, item) {
    const saleQty = parseFloat(item.saleqty) || 0;
        const trayCount = parseInt(item.traycount, 10) || 1;
        const trays = Math.round(saleQty / trayCount);
    $.ajax({    
        url: "sqlqueries/queries.php?action=getpickuplocation_delivery", 
        type: "POST",
        data: {
            projectid: $("#hidden_projectid").val(),
            productid: item.productid,
            psono: item.psono,
            stockorder_no: item.stockorder_no,
            clientid: item.clientid, 
        },
        success: function (response) { 

            const $tbody = $("#pickupTableBox_" + index + " tbody");
            $tbody.empty();

            if (!response || response.length === 0) {
                $tbody.append(`
                    <tr>
                        <td colspan="2" class="text-center text-muted">
                            No stock available
                        </td>
                    </tr>
                `);
                return;
            }
            response.forEach(row => {
                $tbody.append(`
                    <tr>
                        <td>${row.locationname}</td>
                        <td>${row.nooftrays}</td>
                    </tr>
                `);
            });
        },
        error: function () {
            alert("Failed to load pickup stock details.");
        }
    });
}
function validateDecimal(el) {
    let value = el.value;
    // Remove invalid characters
    value = value.replace(/[^0-9.]/g, '');
    // Allow only one decimal point
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    // Prevent leading dot
    if (value.startsWith('.')) {
        value = '0' + value;
    }
    // Optional: limit to 2 decimal places
    if (value.includes('.')) {
        const [intPart, decPart] = value.split('.');
        value = intPart + '.' + decPart.slice(0, 2);
    }
    el.value = value;
}
function btnupdateaddress($container) {
    if (!$container || !$container.length) return;

    // Find all input, textarea, select, button inside the container that are enabled
    const $enabled = $container.find('input:enabled, textarea:enabled, select:enabled, button:enabled');

    if ($enabled.length) {
        const $lastEnabled = $enabled.last(); // last enabled element

        $('html, body').animate({
            scrollTop: $lastEnabled.offset().top - 80
        }, 500);

        $lastEnabled.focus(); // set focus
    }
}
function scrollToLastEnabled($container) {
    if (!$container || !$container.length) return;

    // Find all input, textarea, select, button inside the container that are enabled
    const $enabled = $container.find('input:enabled, textarea:enabled, select:enabled, button:enabled');

    if ($enabled.length) {
        const $lastEnabled = $enabled.last(); // last enabled element

        $('html, body').animate({
            scrollTop: $lastEnabled.offset().top - 80
        }, 500);

        $lastEnabled.focus(); // set focus
    }
}
function scrollAndFocus(selector) {
    const el = document.querySelector(selector);
    if (!el) return;

    // Scroll smoothly to the container
    el.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });

    // Focus the first input inside the container
    const input = el.querySelector('label, input, textarea, select');

    if (input) {
        setTimeout(() => input.focus(), 300);
    }
} 

/*function scrollAndFocusLastDiv(containerSelector) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    // Get the LAST dynamically added div
    const lastDiv = container.lastElementChild;
    if (!lastDiv) return;

    // Scroll so the div is aligned to top
    lastDiv.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });

    // Get all focusable controls inside the last div
    const inputs = lastDiv.querySelectorAll(
        'input:not([type="hidden"]):not([disabled]), ' +
        'textarea:not([disabled]), ' +
        'select:not([disabled]), ' +
        'button:not([disabled])'
    );

    if (!inputs.length) return;

    // Find the visually TOP control in the last div
    let topInput = null;
    let topPos = Infinity;

    inputs.forEach(input => {
        const rect = input.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return; // skip hidden

        if (rect.top < topPos) {
            topPos = rect.top;
            topInput = input;
        }
    });

    if (topInput) {
        // Wait for layout + scroll to settle
        setTimeout(() => {
            topInput.focus({ preventScroll: true });
        }, 300);
    }
}*/

/*function scrollAndFocus(selector) {
    const el = document.querySelector(selector);
    if (!el) return;

    // Scroll container into view (top aligned)
    el.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });

    // Get all focusable controls inside the container
    const inputs = el.querySelectorAll(
        'input:not([type="hidden"]):not([disabled]), ' +
        'textarea:not([disabled]), ' +
        'select:not([disabled]), ' +
        'button:not([disabled])'
    );

    if (!inputs.length) return;

    // Find the TOP-MOST visible element
    let topElement = null;
    let topOffset = Infinity;

    inputs.forEach(input => {
        const rect = input.getBoundingClientRect();

        // Skip invisible elements
        if (rect.width === 0 || rect.height === 0) return;

        if (rect.top < topOffset) {
            topOffset = rect.top;
            topElement = input;
        }
    });

    if (topElement) {
        setTimeout(() => {
            topElement.focus({ preventScroll: true });
        }, 300);
    }
}
*/










 