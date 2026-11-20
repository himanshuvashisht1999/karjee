
$(document).on("click", ".stage", function () {

    const $orderRow = $(this).closest("tr");
    let $next = $orderRow.next();

    while ($next.length && $next.hasClass("production-row")) {
        $next.toggleClass("d-none");
        $next = $next.next();
    }
});


$(document).on('change', '#mydate', function () {
    orderdetails("tblorder");
});
$(document).on('change', '#tblorder .chkorder', function () {
    $('#tblorder .chkorder').not(this).prop('checked', false); 
    $("#tblorder").removeData();
    $("#tblwarehouse tbody").empty();
    $("#spanwarehouseqty").text('');
    // debugger;
    if ($(this).is(':checked')) {
        let row = $(this).closest('tr'); 
        let productid = row.data('product_id'); 
        let expecteddelivery = row.data('expecteddelivery'); 
        let availabledt = row.data('availabledt');          
        let psono = row.data('psono'); 
        //let sono = row.find('.sono').text();
        let stockorder_no = row.data('stockorder_no');
        let phoneno = row.data('phoneno'); 
        let clientemail = row.data('clientemail');
        let invoice_address = row.data('invoice_address');
       // let actionstatus = row.data('actionstatus'); 
        //let orderstatus = row.data('orderstatus'); 
        let clientid = row.data('clientid');
        let clientname = row.find('.clientname').text(); 
        let productname = row.find('.productname').text();
        let quantity = row.find('.orderqty').text(); 
        //let bookingdt = row.find('.bookingdt').text();
        let stage = row.find('.stage').text().trim();

         if (stage!="Trader Warehouse"){  
            alert("Product confirmation Details avilable only for stage Ready stock ie trader ware house\nCurrent stage is " +stage ) ;
         }
            $("#tblorder").data("productid",productid);
            $("#tblorder").data("expecteddelivery",expecteddelivery);
            $("#tblorder").data("availabledt",availabledt);
            $("#tblorder").data("psono",psono);
            //$("#tblorder").data("sono",sono);
            $("#tblorder").data("stockorder_no",stockorder_no);
            $("#tblorder").data("clientid",clientid);
            $("#tblorder").data("clientname",clientname);
            $("#tblorder").data("phoneno",phoneno); 
            $("#tblorder").data("clientemail",clientemail);
            $("#tblorder").data("invoice_address",invoice_address);  
            //$("#tblorder").data("bookingdt",bookingdt);
            //$("#tblorder").data("swstage",actionstatus); 
            //$("#tblorder").data("orderstatus",orderstatus);
            $("#tblorder").data("productname",productname);
            $("#tblorder").data("orderqty",quantity); 
              $("#recipientname") .val( $("#tblorder").data("clientname")); 
            $("#contactno").val( $("#tblorder").data("phoneno"));
            $("#lblclientname").text("Active Order History Screen Of " +$("#tblorder").data("clientname"));
            loadhistory_client();

            $('#tblorder .chkorder').prop('checked', false); 
            $("#history_modal").modal("show");
       /* }else{
            alert ("Active stage is" +stage);
        }*/
    } else { 
        $('#tblorder tbody tr').show();
        $("#divreadysale").addClass("d-none");
    }
});
$(document).on('click', '.btnSaveAll', function () { 
    const $btn = $(this);
    const $question = $btn.closest('.showquestion1');
    //const isChecked = $question.find("input[type='checkbox']:checked").length > 0;
    const readyqty      = parseInt($question.data('readyqty'), 10) || 0;
    const orderqty      = parseInt($question.data('orderqty'), 10) || 0;
    const minAllowedQty = Math.ceil(orderqty * 0.90);
    const traycount     = parseInt($question.data('traycount'), 10) || 1; 
    const selectedVal = $question.find("input[type='radio']:checked").val();
    if(!selectedVal){
         alert( 'You have not select any option');
            return false;
    } 
    let msg = "";
    if (selectedVal==="partially") {
        const partiallyqty = parseInt(
            $question.find('.radiooption_partiallyqty').val() || '0',
            10
        );
        if (!partiallyqty || partiallyqty <= 0) {
            alert( 'Partial quantity cannot be empty or zero.');
            return false;
        }
        if (partiallyqty % traycount !== 0) {
            alert(`Partial quantity must be a multiple of ${traycount}.`);
            return false;
        }

        if (partiallyqty > readyqty) {
            alert( `Partial quantity cannot exceed ${readyqty}.`);
            return false;
        }
        if (partiallyqty < minAllowedQty) {
            msg = `<b>The partially quantity is less than 90% of the ordered quantity.</b><br><br>`
         } 
        msg += `Ordered Qty : <b>${orderqty}</b><br>
         Partial Agree Qty : <b>${partiallyqty}</b><br>`;

        showMsgModal(msg, {
            proceedText: 'Continue anyway',
            seekText: 'Seek Approval',
            titleText: 'Approval Required',
           // showProceed: false
        }).then(function (action) {
            if (action === 'proceed') {
                question1save($question,"partially",partiallyqty);

            } else if (action === 'seek') {
               question1save($question,"seek",partiallyqty)
               // $('.btnSeekpermision').trigger('click');

            } else { 
                // cancel → do nothing
            }
        });       
    }else if (selectedVal==="agreeextraqty") {
        msg = `<b>Agreed extra qty of the ordered quantity.</b><br><br>`
        msg =  `Ordered Qty : <b>${orderqty}</b><br>
        Agree Qty : <b>${readyqty}</b><br> `;
        showMsgModal(msg, {
            proceedText: 'Are you want to save it',
            seekText: 'Agree',
            titleText: 'Agree',
            showSeek: false
        }).then(function (action) {
            if (action === 'proceed') {
                question1save($question,"agreeextraqty",readyqty); 

            }  else { 
                // cancel → do nothing
            }
        });
    }else if (selectedVal==="agreeorderqty") {
        msg = `<b>Agreed  ordered quantity.</b><br><br>`
        msg =  `Ordered Qty : <b>${orderqty}</b><br>
        Agree Qty : <b>${orderqty}</b><br> `;
        showMsgModal(msg, {
            proceedText: 'Are you want to save it',
            seekText: 'Agree',
            titleText: 'Agree',
            showSeek: false
        }).then(function (action) {
            if (action === 'proceed') {
                question1save($question,"agreeorderqty",orderqty); 

            }  else { 
                // cancel → do nothing
            }
        });
    } else if (selectedVal==="later") {
        //getpayment_history();
        deliverysummary(); 
    }     
    return false;
}); 
$(document).on('click', '.btnExit', function () {
    $("#tblorder").removeData();
    $("#history_modal").modal("hide");
    orderdetails("tblorder");
}); 

$(document).on('change', '#tblhistory .chkhistory', function () {
    let $chk = $(this);
    let $tr  = $chk.closest('tr'); 
    // debugger;
   if ($tr.hasClass('activerow') ) { //&& $chk.prop('disabled')
      $('.initialquestion').empty();
      $displayquestionno =1;
        //display_swstage_new($chk);
      stage1question($chk,"");
    }else if( $chk.prop('checked')){ 
        //display_swstage_new($chk);
        stage1question($chk);
    }
});
/*
$(document).on('change', '.showquestion2 input[type="radio"]', function () {
alert("hi");
    const $row = $(this).closest('.showquestion2');
    const selectedVal = $(this).val();

    if (selectedVal === 'delivered') {
        $row.find('.delivery-fields').removeClass('d-none');
    } else {
        $row.find('.delivery-fields').addClass('d-none');

        // Optional cleanup on pickup
        $row.find('.delivery_Locationid').val('').trigger('change');
        $row.find('.deliveryaddress').val('');
        $row.find('.newfreightcharges').val('');
    }
});

$(document).on("change", "input[type='radio'][name='chkquestion1_1']", function() {
    $("#deliveryaddress").addClass("d-none");
    $(".save_address_div").addClass("d-none")
    let selectedOption = $('input[name="chkquestion1_1"]:checked').val();

    $("#invoicevalue").val( $("#newfreightcharges").data("finalamt")||"");
    if (selectedOption==="delivered"){
        $("#deliveryaddress").removeClass("d-none"); 
        $(".save_address_div").removeClass("d-none")
        
    }       
}); */
$(document).on('change', "input[type='radio'][name^='radiooption_']", function () {

    const $question = $(this).closest('.showquestion1');
    const selectedVal = $(this).val();

    const $partialQtyWrapper = $question.find("[id^='partialQty_']");
    const $partialQtyInput   = $question.find('.radiooption_partiallyqty');

    if (selectedVal === 'partially') {

        // Show & enable partial qty
        //$partialQtyWrapper.removeClass('d-none');
        $partialQtyInput.prop('disabled', false).focus();

    } else {

        // Hide & reset partial qty
        //$partialQtyWrapper.addClass('d-none');
        $partialQtyInput.prop('disabled', true).val('');
    }
    recalcQuestion($question);
});
$(document).on('input', '.radiooption_partiallyqty', function () {

    const $question = $(this).closest('.showquestion1');
    const $partialRadio = $question
        .find("input[type='radio'][value='partially']");

    if ($(this).val().trim() !== '') {

        // Auto-select "Partially"
        $partialRadio.prop('checked', true).trigger('change');
    }
    recalcQuestion($question);
});
$(document).on('focus', '.radiooption_partiallyqty', function () {

    const $question = $(this).closest('.showquestion1');
    const $partialRadio = $question
        .find("input[type='radio'][value='partially']");

    if (!$partialRadio.is(':checked')) {
        $partialRadio.prop('checked', true).trigger('change');
    }
    recalcQuestion($question);
}); 

 $(document).on("change", ".deliveryOptionRadio", function () {
    const index = $(this).data("index");
    const value = $(this).val();

    const allocationData = $("#tblorder").data("allocationorders");
    if (!allocationData || !allocationData[index]) return;

    allocationData[index].deliveryOption = value;
    $("#tblorder").data("allocationorders", allocationData);

    if (value === "pickup") {

        $("#pickupTableBox_" + index).removeClass("d-none");
        $("#divfreightcharges_" + index).addClass("d-none");
        $("#addressBox_" + index).addClass("d-none");

        loadPickupStock(index, allocationData[index]);

    } else {

        $("#pickupTableBox_" + index).addClass("d-none");
        $("#divfreightcharges_" + index).removeClass("d-none");
        $("#addressBox_" + index).removeClass("d-none");
    }
});
 
$(document).on("input", ".newfreightcharges", function () {
    const index = $(this).data("index");
    const allocationData = $("#tblorder").data("allocationorders") || [];

    allocationData[index].freightcharges = $(this).val();
    $("#tblorder").data("allocationorders", allocationData);
});
 
$(document).on("input", ".addressInput", function () {
    const index = $(this).data("index");
    const allocationData = $("#tblorder").data("allocationorders") || [];

    allocationData[index].deliveryaddress = $(this).val();
    $("#tblorder").data("allocationorders", allocationData);
});

 

$(document).on("click",".btnnext",function(){ 
    let clientEmail = $("#tblorder").data("clientemail") || "";
    let clientname = $("#tblorder").data("clientname") || "";

    let emailbody = "Dear " + clientname +
        ",\nPlease find attached the proforma invoice for your reference.\n\n\n";

    emailbody += "Regards,\n" + ($("#hidden_loginname").val() || "");

    $("#txttoemail").val(clientEmail);
    $("#txtemailbody").html(emailbody);
    $("#phonestage4").removeClass("d-none");
    $("#phonestage4").show();


});

$(document).on('click', '.btnupdateaddress', function () {
    const $box = $(this).closest('.border');
    const index = $box.index(); // 🔹 CURRENT INDEX ONLY

    const allocationData = $("#tblorder").data("allocationorders") || [];
    const src = allocationData[index];

    if (!src) {
        alert('Invalid item index');
        return;
    }

    const deliveryType = $box.find('.deliveryOptionRadio:checked').val();
    const address = $box.find('.addressInput').val()?.trim() || '';
    const freight = $box.find('.newfreightcharges').val() || '';

    /* ---------- VALIDATION ---------- */

    if (deliveryType === 'delivered') {
        if (!address) {
            alert('Delivery Address cannot be empty');
            scrollAndFocus(`#addressBox_${index}`);
            return;
        }
        if (!freight || parseFloat(freight) <= 0) {
            alert('Freight Charges are required');
            scrollAndFocus(`#divfreightcharges_${index}`);
            return;
        }
    }

    /* ---------- BUILD every entry ITEM ---------- */
    let rowsData = $("#tblorder").data("allocationorders")||[];     
    if (rowsData.length === 0) {
        alert('Please select at least one row');
        return;
    } 
    /* ---------- AJAX (ONE ITEM ONLY) ---------- */
    $.ajax({
        url: "save/saveaddress.php",
        type: 'POST',
        dataType: 'json',
        data: {
            projectid: $('#hidden_projectid').val(),
            items: JSON.stringify(rowsData),
            communicatedby: $("#hidden_loginname").val(),
            actionstatus :"3" 
        },
        success: function (res) {
            alert('Address updated successfully');
        },
        error: function (xhr, status, error) {
            console.error('AJAX Error:', status, error);
        }
    });
});


 $(document).on("click","#btnsentemail",function(){  
    let currentdt = serverCurrentDate;

     if(!validateEmailForm()){
        return false;
     }   
    let rowsData = $("#tblorder").data("allocationorders")||[];
     
    if (rowsData.length === 0) {
        alert('Please select at least one row');
        return;
    } 
    let clientData = {
        clientname       : $("#tblorder").data("clientname"), 
        mobileno         : $("#tblorder").data("phoneno"),
        emailid          : $("#tblorder").data("clientemail"),
        delivery_address : $("#tblorder").data("invoice_address"), 
        psono:"",
        created_at: currentdt,
        status: "Ready for despatch"
    };
    
    $.ajax({
        url: "save/sendattachments.php",
        type: 'POST',
        data: {
            companyid: $("#hidden_companyid").val(),
            subcompanyid: $("#hidden_subcompanyid").val(),
            projectid: $("#hidden_projectid").val(),
            communicatedby: $("#hidden_loginname").val(),
            companyname: $("#hidden_companyname").val(),   
            saledetailproductwise: JSON.stringify(rowsData),
            clientData: JSON.stringify(clientData),
            toemail: $("#txttoemail").val().trim(),
            ccemail: $("#txtccemail").val().trim(),
            emailbody: $("#txtemailbody").val().trim(), 
            communication_date:$("#communicationdate").val(),
            clientid:$("#tblorder").data("clientid"),
            actionstatus:"4",
        },
         dataType: 'json',
        success: function (res) {
           $(".waitloaderfooter").modal("hide");
             alert(res['status'] )
             if(res['status'] ==="Update successfully"){
                $("#tblorder").removeData();
                    $("#history_modal").modal("hide");
                    orderdetails("tblorder");
             }
           
        },beforeSend(res){
            $(".waitloaderfooter").modal("show");
        } 
    }); 

 });

$(document).on('click', '#btcloseconservation', function () {
    $("#tblorder").removeData();
    $("#history_modal").modal("hide");
    orderdetails("tblorder");
}); 






         