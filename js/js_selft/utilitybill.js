function openModalForAddNewBillType(routurl,billtypeid=null){
  var form_data = new FormData();
  var baseurl = $("#"+routurl+"").val();
  $("#webpagebodydiv").load(baseurl+"webpages/master_addbilltype.php", {
      billtypeid: billtypeid
  }, function(){
    $("#webpagebodyview_modal").css("z-index","10058")
      $("#webpagebodyview_modal").modal("show");
      $("#modalheader").html("New Bill Type");
  });  
}

function openModalForAddNewBillLocation(routurl,locatinid=null){
  var form_data = new FormData();
  var baseurl = $("#"+routurl+"").val();
  $("#webpagebodydiv").load(baseurl+"webpages/master_addbilllocation.php", {
      lcid: locatinid
  }, function(){
    $("#webpagebodyview_modal").css("z-index","10058")
      $("#webpagebodyview_modal").modal("show");
      $("#modalheader").html("Bill Location Details");
  });  
}

function openModalForAddNewPaytoForBill(routurl){
  var form_data = new FormData();
  var baseurl = $("#"+routurl+"").val();
  $("#newpaytodivid").load(baseurl+"expenditure_expencessheet/newpayto_authorization.php", function(){
    $("#newpaytodetails_modal").css("z-index","10058")
      $("#newpaytodetails_modal").modal("show");
      $("#paytohead").html("NEW SERVICE PROVIDER DETAILS");
  });  
}

function billtypewithoption(baseurl_eid,alreadyexistval,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/utilitybill_modal.php?action=billtypewithoption",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $("#"+dropdownid+"").empty();
        if(res.length>0){
          if (res.length>1) 
          {
            $("#"+dropdownid+"").append("<option value='' selected disabled>Bill Type</option>");
          }
          for (var i = 0; i < res.length; i++) {
            $("#"+dropdownid+"").append("<option value='"+res[i].id+"'>"+res[i].typename+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].id).trigger("change");
            }
          }
          if(alreadyexistval){
            $("#"+dropdownid+"").val(alreadyexistval).trigger("change");
          }

        }
      }
    });
}

function billdurationtypewithoption(baseurl_eid,alreadyexistval,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/utilitybill_modal.php?action=billdurationtypewithoption",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        console.log(res);
        if(res.length>0){
          if (res.length>1) 
          {
              $("#"+dropdownid+"").append("<option value='' selected disabled>Bill Duratin Type</option>");
          }
          for (var i = 0; i < res.length; i++) {
            $("#"+dropdownid+"").append("<option value='"+res[i].id+"' data-billdurationdays='"+res[i].durationdays+"'>"+res[i].durationname+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].id).trigger("change");
            }
          }
          $("#"+dropdownid+"").append("<option value='custom' data-billdurationdays='custom'>Custom</option>");
          if(alreadyexistval){
            $("#"+dropdownid+"").val(alreadyexistval).trigger("change");
          }
        }
      }
    });
}

function billlocationwithoption(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,alreadyexistval,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/utilitybill_modal.php?action=billlocationwithoption",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        if(res.length>0){
          if (res.length>1) 
          {
              $("#"+dropdownid+"").append("<option value='' selected disabled>Select</option>");
          }
          for (var i = 0; i < res.length; i++) {
            $("#"+dropdownid+"").append("<option value='"+res[i].id+"'>"+res[i].locationwithdepart+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].id).trigger("change");
            }
          }
          if(alreadyexistval){
            $("#"+dropdownid+"").val(alreadyexistval).trigger("change");
          }
        }
      }
    });
}

function billlocation_callback(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/utilitybill_modal.php?action=billlocation_callback",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        callback(res);
      }
    });
}


function update_billmaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,billinvoicetype_eid,billdurationtype_eid,billdurationdays_eid,billlocation_eid,billpaytoname_eid,billbeneficiaryname_eid,billpurpose_eid,responsiblepersonname_eid,billingdate_eid,billpaymentduedate_eid,billpaymentremark_eid,billattachement_eid,clicktosavebtnid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var subprojectid = $("#"+subprojectid_eid+"").val();
  form_data.append("subprojectid", subprojectid);
  
  var billdbsno = $("#"+clicktosavebtnid+"").data("billdbsno");
  form_data.append("billdbsno", billdbsno);

   var billtype = $("#"+billinvoicetype_eid+"").val();
  if(billtype=="" || billtype==null || billtype=="undefined"){
    alertmessage('error','Bill/Invoice type is missing');
    $("#"+billinvoicetype_eid+"").focus();
    return false;
  }
  form_data.append("billtype", billtype);
  
  var billpaytoname = $("#"+billpaytoname_eid+"").val();
  if(billpaytoname=="" || billpaytoname==null || billpaytoname=="undefined"){
    alertmessage('error','Bill payto name is missing');
    $("#"+billpaytoname_eid+"").focus();
    return false;
  }
  form_data.append("billpaytoname", billpaytoname);

  var billbeneficiaryname = $("#"+billbeneficiaryname_eid+"").val();
  if(billbeneficiaryname=="" || billbeneficiaryname==null || billbeneficiaryname=="undefined"){
    alertmessage('error','Bill beneficiary name is missing');
    $("#"+billbeneficiaryname_eid+"").focus();
    return false;
  }
  form_data.append("billbeneficiaryname", billbeneficiaryname);

  var billlocation = $("#"+billlocation_eid+"").val();
  if(billlocation=="" || billlocation==null || billlocation=="undefined"){
    alertmessage('error','Bill location is missing');
    $("#"+billlocation_eid+"").focus();
    return false;
  }
  form_data.append("billlocation", billlocation);

  var billpurpose = $("#"+billpurpose_eid+"").val();
  if(billpurpose=="" || billpurpose==null || billpurpose=="undefined"){
    alertmessage('error','Bill purpose is missing');
    $("#"+billpurpose_eid+"").focus();
    return false;
  }
  form_data.append("billpurpose", billpurpose);
   var billdurationtype = $("#"+billdurationtype_eid+"").val()??'0';
  if(billdurationtype=="" || billdurationtype==null || billdurationtype=="undefined"){
    alertmessage('error','Bill duration days is missing');
    $("#"+billdurationtype_eid+"").focus();
    return false;
  }
  form_data.append("billdurationtype", billdurationtype);


  // billdurationdays
  var billdurationdays = $("#"+billdurationdays_eid+"").val();
  if(billdurationdays=="" || billdurationdays==null || billdurationdays=="undefined"){
    alertmessage('error','Bill duration days is missing');
    $("#"+billdurationtype_eid+"").focus();
    return false;
  }
  form_data.append("billdurationdays", billdurationdays);
  
  var responsiblepersonname = $("#"+responsiblepersonname_eid+"").val();
  if(responsiblepersonname=="" || responsiblepersonname==null || responsiblepersonname=="undefined"){
    alertmessage('error','Responsible person name is missing');
    $("#"+responsiblepersonname_eid+"").focus();
    return false;
  }
  form_data.append("responsiblepersonname", responsiblepersonname);

  var billingdate = $("#"+billingdate_eid+"").val();
  if(billingdate=="" || billingdate==null || billingdate=="undefined"){
    alertmessage('error','Billing date is missing');
    $("#"+billingdate_eid+"").focus();
    return false;
  }
  form_data.append("billingdate", billingdate);

  var paymentduedate = $("#"+billpaymentduedate_eid+"").val();
  if(paymentduedate=="" || paymentduedate==null || paymentduedate=="undefined"){
    alertmessage('error','Payment due date is missing');
    $("#"+billpaymentduedate_eid+"").focus();
    return false;
  }
  form_data.append("paymentduedate", paymentduedate);

  var billremark = $("#"+billpaymentremark_eid+"").val();
  if(billremark=="" || billremark==null || billremark=="undefined"){
    alertmessage('error','Bill remark is miising');
    $("#"+billpaymentremark_eid+"").focus();
    return false;
  }
  form_data.append("billremark", billremark);

  var attachedfile = $("#"+billattachement_eid+"")[0].files[0];
  form_data.append("billattachedfile", attachedfile);
  
  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);
  
  var btnval=$("#"+clicktosavebtnid+"").html();
  $("#"+clicktosavebtnid+"").prop("disabled", true);
  $("#"+clicktosavebtnid+"").html("Please wait data saving");
  $.ajax
  ({
      type: "POST",
      url: baseurl+"modal/utilitybill_modal.php?action=update_billmaster",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $("#"+clicktosavebtnid+"").prop("disabled", false);
        $("#"+clicktosavebtnid+"").html(btnval);
        if(res.length>0){
          for(var i=0; i<res.length; i++){
            if(res[i].status=="Success"){
              SweetAlertmessageFadein('success',res[i].message);
            }else{
              alertmessage('error',res[i].message);
            }
          }
        }
      }
  });
}


function remove_billfrommaster(routurl_eid,billdbsno,billremoveremark){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  
  form_data.append("billdbsno", billdbsno);

  if(billremoveremark=="" || billremoveremark==null || billremoveremark=="undefined"){
    alertmessage('error','Remark is miising');
    $("#"+billpaymentremark_eid+"").focus();
    return false;
  }
  form_data.append("billremoveremark", billremoveremark);

  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);
  
  $.ajax
  ({
      type: "POST",
      url: baseurl+"modal/utilitybill_modal.php?action=remove_billfrommaster",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
          // console.log(res);
          if(res.status=="Success"){
            location.reload();
            alertmessage('success',res.message);
          }else{
            alertmessage('error',res.message);
          }
      }
  });
}

function historyofbillreport_htmlview(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,datefrom_eid,dateto_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var datefrom = $("#"+datefrom_eid+"").val();
  form_data.append("datefrom", datefrom);
  var dateto = $("#"+dateto_eid+"").val();
  form_data.append("dateto", dateto);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/utilitybill_modal.php?action=historyofbillreport_htmlview",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
        callback(res);
      }
    });
}

function billfinanciayyearlist_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var routurl = $("#"+routurl_eid+"").val();
  form_data.append("routurl", routurl);
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  $.ajax
    ({
      type: "POST",
      url: routurl+"modal/utilitybill_modal.php?action=billfinanciayyearlist",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
         callback(res);
      }
    });
}


function historyofbills_htmlview(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/utilitybill_modal.php?action=historyofbills_htmlview",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
        callback(res);
      }
    });
}

function pendingbills_forrequesthtml(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,tableid,shortbydata,callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("shortbydata", shortbydata);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/utilitybill_modal.php?action=pendingbills_forrequesthtml",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
        callback(res);
      }
    });
}

function overduependingbills_forrequesthtml(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,tableid,shortbydata,callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("shortbydata", shortbydata);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/utilitybill_modal.php?action=overduependingbills_forrequesthtml",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
        callback(res);
      }
    });
}

function billmasterlistwithhtmlgrid(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortbydata, callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("shortbydata", shortbydata);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/utilitybill_modal.php?action=billpaymenthtmlgrid",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
        callback(res);
      }
    });
}

function billwaitingforpaymentmasterlistwithhtmlgrid(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,tableid,shortbydata){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("shortbydata", shortbydata);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/utilitybill_modal.php?action=waitingforpayment_billhtmlgrid",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        $("#"+tableid+" tbody").empty();
        if(res){
          $("#"+tableid+" tbody").append(res);
        }
      }
    });
}

function billmasterlistCallback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var routurl = $("#"+routurl_eid+"").val();
  form_data.append("routurl", routurl);
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  $.ajax
    ({
      type: "POST",
      url: routurl+"modal/utilitybill_modal.php?action=billsmasterdetails",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
         callback(res);
      }
    });
}

function savetodbbilllocationdetails_tomaster(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,departmentname_eid,locationname_eid,bil_keyword_class,savetodb_btnid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var departmentname = $("#"+departmentname_eid+"").val();
  form_data.append("departmentname", departmentname);
  let locationkeywords = [];
  $("." + bil_keyword_class).each(function() {
      if ($(this).val() !== "") {
          locationkeywords.push($(this).val());
      }
  });
  locationkeywords = locationkeywords.join(", ");

  var locationname = $("#"+locationname_eid+"").val();
  // if(locationname==null || locationname=="undefined" || locationname==""){
  //   alertmessage('error', 'Location name of utility bill is missing');
  //   $("#"+locationname_eid+"").focus();
  //   return false;
  // }
  form_data.append("locationname", locationkeywords);
  form_data.append("loginid", $("body").data("loginid"));

  var btnval = $("#"+savetodb_btnid+"").html();
  var billlocationid = $("#"+savetodb_btnid+"").data("billlocationid");
  form_data.append("billlocationid", billlocationid);
  $("#"+savetodb_btnid+"").html('Please wait data...');
  $("#"+savetodb_btnid+"").prop('disabled', true);
  
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/utilitybill_modal.php?action=savetodbbilllocationdetails",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
      $("#"+savetodb_btnid+"").html(btnval);
      $("#"+savetodb_btnid+"").prop('disabled', false);
      if(res.status=="Success" || res.status=="success"){
        alertmessage('success', res.message);
        $("#"+departmentname_eid+"").val('');
        $("#"+locationname_eid+"").val('');
        $("."+bil_keyword_class+"").val('');

        $("#"+savetodb_btnid+"").data("billlocationid",'');
      }else{
        alertmessage('error', res.message);
        return false;
      }
    }
  });
}



function savetodbbillTypedetails_tomaster(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,billtypename_eid,billtypedescription_eid,savetodb_btnid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var billtypename = $("#"+billtypename_eid+"").val();
  if(billtypename==null || billtypename=="undefined" || billtypename==""){
    alertmessage('error', 'New bill type is missing');
    $("#"+billtypename_eid+"").focus();
    return false;
  }
  form_data.append("billtypename", billtypename);
  var billtypedescription = $("#"+billtypedescription_eid+"").val();  
  form_data.append("billtypedescription", billtypedescription);
  form_data.append("loginid", $("body").data("loginid"));

  var btnval = $("#"+savetodb_btnid+"").html();
  var billtypeid = $("#"+savetodb_btnid+"").data("billtypeid");
  form_data.append("billtypeid", billtypeid);

  $("#"+savetodb_btnid+"").html('Please wait data...');
  $("#"+savetodb_btnid+"").prop('disabled', true);
  
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/utilitybill_modal.php?action=savetodbbillTypedetails",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
      $("#"+savetodb_btnid+"").html(btnval);
      $("#"+savetodb_btnid+"").prop('disabled', false);
      if(res.status=="Success" || res.status=="success"){
        alertmessage('success', res.message);
        $("#"+billtypename_eid+"").val('');
        $("#"+billtypedescription_eid+"").val('');
        $("#"+savetodb_btnid+"").data("billtypeid",'');
      }else{
        alertmessage('error', res.message);
        return false;
      }
    }
  });
}