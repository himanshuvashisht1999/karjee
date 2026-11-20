function getprojectlist_fordropdown(routurl_eid,companyid_eid,subcompanyid_eid,alreadyexist,dropdownid){
	var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=projectlist_fordropdown",
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
          $("#"+dropdownid+"").append("<option value='' selected disabled>Select</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i].projectid+"'>"+res[i].projectname+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].projectid).trigger("change");              
            }
          }
          if(alreadyexist){
            $("#"+dropdownid+"").val(alreadyexist).trigger("change");
          }
        }else{
          $("#"+dropdownid+"").append("<option value='' disabled>Project not available</option>");
        }
        // $('.select2').select2();
        // $('.select2-selection').css('border-radius','0px');
        // $('.select2-container').children().css('border-radius','0px');
        // $('.select2-selection').css('height','38px');
      }
    });
}

function getbanknamefrommaster(baseurl_eid,companyid,dropdownid){
  var form_data = new FormData();
  var baseurl = baseurl_eid;  
  form_data.append("companyid", companyid); 
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=banklist",
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
          $("#"+dropdownid+"").append("<option value='' selected disabled>Select</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i].id+"'>"+res[i].bankname+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].id).trigger("change");              
            }
          }
         
        }else{
          $("#"+dropdownid+"").append("<option value='' disabled>Bank not available</option>");
        }
        
      }
    });
}

function getcomponantforkey(baseurl_eid,dropdownid) {
  var form_data = new FormData();
  var baseurl = baseurl_eid;
   
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=componantforkey",
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
          $("#"+dropdownid+"").append("<option value='' selected disabled>Select</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i].id+"' data-table='"+res[i].keytablename+"'>"+res[i].keyforname+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].id).trigger("change");              
            }
          }
         
        }else{
          $("#"+dropdownid+"").append("<option value='' disabled>Bank not available</option>");
        }
        
      }
    });
}

function getaccountfrommaster(baseurl_eid,companyid,subcompanyid,projectid,dropdownid){
  var form_data = new FormData();
  var baseurl = baseurl_eid;
 form_data.append("companyid", companyid); 
 form_data.append("subcompanyid", subcompanyid);
 form_data.append("projectid", projectid);
 
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=accountlist",
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
          $("#"+dropdownid+"").append("<option value='' selected disabled>Select</option>");
          for(var i=0; i<res.length; i++){
            var valid=res[i].accountid+"|"+res[i].bankid;
            var textval=res[i].accountname+"|"+res[i].accountno+"|"+res[i].bankname;
            $("#"+dropdownid+"").append("<option value='"+valid+"'>"+textval+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].id).trigger("change");              
            }
          }
         
        }else{
          $("#"+dropdownid+"").append("<option value='' disabled>Account not available</option>");
        }
        
      }
    });
}

function getonlyaccountlistfrommaster(dropdownid){
  var form_data = new FormData();
  var routurl = $("#hidden_rooturl").val();
  form_data.append("companyid", $("#hidden_companyid").val()); 
  form_data.append("subcompanyid", $("#hidden_subcompanyid").val());
  form_data.append("projectid", $("#hidden_projectid").val()); 
  $.ajax
    ({
      type: "POST",
      url: routurl+"modal/masters_modal.php?action=getaccountinfobyproject",
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
          $("#"+dropdownid+"").append("<option value='' selected disabled>Select</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i].accountid+"'>"+res[i].bankname+"-"+res[i].accountno+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].accountid).trigger("change");              
            }
          }         
        }else{
          $("#"+dropdownid+"").append("<option value='' disabled>Account not available</option>");
        }
        
      }
    });
}

function getaccountfrommasterwithcreditcard(baseurl_eid,companyid,subcompanyid,projectid,dropdownid){
  var form_data = new FormData();
  var baseurl = baseurl_eid;
 form_data.append("companyid", companyid); 
 form_data.append("subcompanyid", subcompanyid);
 form_data.append("projectid", projectid);
 
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=accountlistwithcreditcard",
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
          $("#"+dropdownid+"").append("<option value='' selected disabled>Select</option>");
          for(var i=0; i<res.length; i++){
            var valid=res[i].accountid+"|"+res[i].bankid;
            var textval=res[i].accountname+"|"+res[i].accountno+"|"+res[i].bankname;
            $("#"+dropdownid+"").append("<option value='"+valid+"'>"+textval+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].id).trigger("change");              
            }
          }
         
        }else{
          $("#"+dropdownid+"").append("<option value='' disabled>Account not available</option>");
        }
        
      }
    });
}

function savetodbsubprojectdetails_tomaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectname,emailid){
  var form_data = new FormData();
  var baseurl = baseurl_eid;
  var companyid = companyid_eid;
  form_data.append("companyid", companyid);
  var subcompanyid = subcompanyid_eid;
  form_data.append("subcompanyid", subcompanyid);
  var projectid = projectid_eid;
  form_data.append("projectid", projectid);
  var subprojectname = subprojectname;
  form_data.append("subprojectname", subprojectname);
  var emailid = emailid;
  form_data.append("emailid", emailid);
 
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=subproject_save",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        if(res.length>0){
          if (res[0].status=='Success') {}
          alert(res[0].message);
          location.reload();
        }
        
      }
    });
}

function subprojectdetails_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=subprojectdetails",
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

function getcreditcardlist_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,loginid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var loginid = $("#"+loginid_eid+"").val();
  form_data.append("loginid", loginid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=creditcardlistdetails",
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

function getfunctionfrommaster_callback(routurl_eid) {
    var form_data = new FormData();
     var baseurl = $("#"+routurl_eid+"").val();
    $.ajax({
           
            url: baseurl+"modal/masters_modal.php?action=getfunctionfrommasterpnl",
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            dataType:'JSON',
            success: function(res) {
              // $(".waitloader").hide();
              callback(response);
            },beforeSend(res){
            // $(".waitloader").show();
          }
        });
  }

function authorizedcompanylist_frommaster(baseurl_eid,loginidval,alreadyexist,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  form_data.append("loginid", loginidval);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=companylist_frommaster",
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
          if(res.length>1){
            $("#"+dropdownid+"").append("<option value='' disabled selected>Choose company name</option>");
          }
          for (var i = 0; i < res.length; i++) {
            $("#"+dropdownid+"").append("<option value='"+res[i].companyid+"'>"+res[i].company_name+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].companyid).trigger("change");
            }
          }
          if(alreadyexist){
            $("#"+dropdownid+"").val(alreadyexist).trigger("change");
          }
        }
      }
    });
}

function authorizedsubcompanylist_frommaster(baseurl_eid,loginidval,companyid_eid,alreadyexist,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  form_data.append("loginid", loginidval); 
  $.ajax
    ({
      type: "POST",
       url: baseurl+"modal/masters_modal.php?action=subcompanylist_frommaster",
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
          $("#"+dropdownid+"").append("<option value='' disabled selected>Choose sub-company name</option>");
          for (var i = 0; i < res.length; i++) {
            $("#"+dropdownid+"").append("<option value='"+res[i].subcompanyid+"'>"+res[i].subcompanyname+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].subcompanyid).trigger("change");
            }
          }
          if(alreadyexist){
            $("#"+dropdownid+"").val(alreadyexist).trigger("change");
          }
        }
      }
    });
}

function authorizedprojectlist_frommaster(baseurl_eid,companyid_eid,subcompanyid_eid,dropdownid,alreadyexistval=null){
  var form_data = new FormData();
  var baseurl = baseurl_eid;
  var companyid = companyid_eid;
  form_data.append("companyid", companyid);
  var subcompanyid = subcompanyid_eid;
  form_data.append("subcompanyid", subcompanyid);
  // alert(companyid+'=='+subcompanyid); 
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=projectlist_fordropdown",
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
          $("#"+dropdownid+"").append("<option value='' selected disabled>Choose project name</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i].projectid+"'>"+res[i].projectname+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].projectid).trigger("change");              
            }
          }
          if(alreadyexistval){
            $("#"+dropdownid+"").val(alreadyexistval).trigger("change");
          }         
        }else{
          $("#"+dropdownid+"").append("<option value='' disabled>Project not available</option>");
        }
        
      }
    });
}

function bankaccountlistwithcallback_frmmaster(baseurl,companyid,subcompanyid,projectid,callback){
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  $.ajax
    ({
      type: "POST",
       url: baseurl+"modal/masters_modal.php?action=bankaccountlist_frommaster",
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

function AddNewPurposeName(routurl,purposecategoryid,purposesubcategoryid,purposecode,type){
  var form_data = new FormData();
  var baseurl = $("#"+routurl+"").val();
  if(type=="credit" || type=="deposit"){
    var type="DEPOSIT";
  }else{
    var type="WITHDRAWAL";
  }
  $("#webpagebodydiv1").load(baseurl+"webpages/purpose_addnewname.php?pcategoryid="+purposecategoryid+"&psubcategoryid="+purposesubcategoryid+"&purposecode="+purposecode+"&type="+type, function(){
    $("#webpagebodyview1_modal").modal("show");
    $("#modalheader1").html("NEW PURPOSE FOR "+type);
   $('.modal-backdrop').not('.modal-stack').css('z-index', 1040 + 10 * $('.modal:visible').length).addClass('modal-stack');
    $('#webpagebodyview1_modal').css('z-index', 1050 + 10 * $('.modal:visible').length);
  });  
}

function EditorPreviewPurposeName(routurl,purposecategoryid,purposesubcategoryid,purposecode,type){
  var form_data = new FormData();
  var baseurl = $("#"+routurl+"").val();
  if(type=="credit" || type=="deposit"){
    var distype="DEPOSIT";
  }else{
    var distype="WITHDRAWAL";
  }
  $("#webpagebodydiv1").load(baseurl+"webpages/purpose_editpreviewname.php?pcategoryid="+purposecategoryid+"&psubcategoryid="+purposesubcategoryid+"&purposecode="+purposecode+"&type="+type, function(){
    $("#webpagebodyview1_modal").modal("show");
    $("#modalheader1").html("PREVIEW/EDIT PURPOSE FOR "+distype);
  $('.modal-backdrop').not('.modal-stack').css('z-index', 1040 + 10 * $('.modal:visible').length).addClass('modal-stack');
    $('#webpagebodyview1_modal').css('z-index', 1050 + 10 * $('.modal:visible').length);
  });  
}

function openModalForUpdatePurpose_expenseshee(routurl,purposecategoryid,purposesubcategoryid,purposecode,type){
  var form_data = new FormData();
  var baseurl = $("#"+routurl+"").val();
  if(type=="credit" || type=="deposit"){
    var type="DEPOSIT";
  }else{
    var type="WITHDRAWAL";
  }
  $("#webpagebodydiv1").load(baseurl+"webpages/purpose_updateforexp.php?pcategoryid="+purposecategoryid+"&psubcategoryid="+purposesubcategoryid+"&purposecode="+purposecode+"&type="+type, function(){
    $("#webpagebodyview1_modal").modal("show");
    $("#modalheader1").html("PURPOS UPDATE FOR "+type);
  });  
}

function openModalForAddNewPurpose(routurl,purposecategoryid,purposesubcategoryid,purposecode,type){
  var form_data = new FormData();
  var baseurl = $("#"+routurl+"").val();
  if(type=="credit" || type=="deposit"){
    var type="DEPOSIT";
  }else{
    var type="WITHDRAWAL";
  }
  $("#newpurposedivid").load(baseurl+"webpages/purpose_newadd.php?pcategoryid="+purposecategoryid+"&psubcategoryid="+purposesubcategoryid+"&purposecode="+purposecode+"&type="+type, function(){
    $("#newpurposedetails_modal").css("z-index","10058")
      $("#newpurposedetails_modal").modal("show");
      $("#purposeheader").html("NEW PURPOSE FOR "+type);
  });  
}


function openModalForReplaceNewPurposeWithOld(routeUrl, purposeCategoryId, purposeSubCategoryId, purposeCode, type, ...breakdownsNoArray) {
    var form_data = new FormData();
    var baseUrl = $("#" + routeUrl).val();
    // Determine the type
    type = (type === "credit" || type === "deposit") ? "DEPOSIT" : "WITHDRAWAL";
    // Serialize the breakdowns array for use in the query string
    // var breakdownsNoArrayJson = encodeURIComponent(JSON.stringify(breakdownsNoArray));
    // Load the replacement purpose details into the modal
    $("#replacement_purposedivid").load(
        `${baseUrl}webpages/purpose_replace.php?pcategoryid=${purposeCategoryId}&psubcategoryid=${purposeSubCategoryId}&purposecode=${purposeCode}&type=${type}&breakdownsnoarray=${breakdownsNoArray}`,
        function () {
            $("#replacement_purposedetails_modal").modal("show");
            $("#replacement_purposeheader").html(`REPLACEMENT PURPOSE FOR ${type}`);
        }
    );
}

function openModalForAddNewBank(routurl,paytoname=null,paytoid=null){
  var form_data = new FormData();
  var baseurl = $("#"+routurl+"").val();
  if(paytoname){
    var paytoname = paytoname;
  }else{
    var paytoname="";
  }
  if(paytoid){
    var paytoid = paytoid;
  }else{
    var paytoid="";
  }
  $("#webpagebodydiv").load(baseurl+"webpages/master_addnewbank.php", {
      ptname: paytoname,
      ptid: paytoid
  }, function(){
    $("#webpagebodyview_modal").css("z-index","10058")
      $("#webpagebodyview_modal").modal("show");
      $("#modalheader").html("NEW BANK REGISTRATION");
  });  
}

function openModalForAddNewFDBank(routurl,paytoname=null,paytoid=null){
  var form_data = new FormData();
  var baseurl = $("#"+routurl+"").val();
  if(paytoname){
    var paytoname = paytoname;
  }else{
    var paytoname="";
  }
  if(paytoid){
    var paytoid = paytoid;
  }else{
    var paytoid="";
  }
  $("#webpagebodydiv").load(baseurl + "webpages/master_addnewfdaccount.php", {
      ptname: paytoname,
      ptid: paytoid
  }, function() {
      $("#webpagebodyview_modal").css("z-index", "10058");
      $("#webpagebodyview_modal").modal("show");
      $("#modalheader").html("NEW FD REGISTRATION");
  });  
}

function openModalForAddCashAccount(routurl,paytoname=null,paytoid=null){
  var form_data = new FormData();
  var routurl = $("#"+routurl+"").val();
  if(paytoname){
    var paytoname = paytoname;
  }else{
    var paytoname="";
  }
  if(paytoid){
    var paytoid = paytoid;
  }else{
    var paytoid="";
  }
  $("#webpagebodydiv").load(routurl+"webpages/master_addcashaccount.php", {
      ptname: paytoname,
      ptid: paytoid
  }, function(){
    $("#webpagebodyview_modal").modal("show");
    $("#webpagebodyview_modal").css("z-index","9999");
    $("#modalheader").html("CASH ACCOUNT REGISTRATION");
  });  
}

$(document).on("click", ".opencashauthorizedmodal", function(){
  var routurl = $("#hidden_rooturl").val();
  $("#webpagebodydiv").load(routurl+"webpages/master_addcashaccount.php", function(){
    $("#webpagebodyview_modal").modal("show");
    $("#webpagebodyview_modal").css("z-index","9999");
    $("#modalheader").html("CASH ACCOUNT REGISTRATION");
  });
});

$(document).on("click", ".openmodalfornewbankclass", function(){
  openModalForAddNewBank('hidden_rooturl');
});

$(document).on("click", ".newbanktype", function(){
    if($(this).val()=="Bank"){
      $(".lbl_accountname").text("Name of Account");
      $(".lbl_bankname").text("Bank Name");
      $(".lbl_accountno").text("Bank Account Number");
      $(".lbl_accountifsc").text("Bank IFSC Code");
      $(".lbl_branchname").text("Branch Name of Bank");
      $(".lbl_branchaddress").text("Bank Branch Address");
      $("#div_accountifsc").show();
      $("#div_branchname").show();
      $("#div_branchaddress").show();
      $("#div_accountotherinfo").show();
    }else if($(this).val()=="FD"){
      $("#div_accountifsc").hide();
      $("#div_branchname").hide();
      $("#div_branchaddress").hide();
      $("#div_accountotherinfo").hide();
      $(".lbl_accountname").text("Name of FD Account");
      $(".lbl_bankname").text("Bank Name of FD  Account");
      $(".lbl_accountno").text("Account Number FD");
      $(".lbl_accountifsc").text("IFSC Code of FD Bank");
      $(".lbl_branchname").text("Branch Name of FD Account");
      $(".lbl_branchaddress").text("Branch Address of FD");
    }else if($(this).val()=="Credit Card"){
      $("#div_accountifsc").show();
      $("#div_branchname").show();
      $("#div_branchaddress").show();
      $("#div_accountotherinfo").hide();
      $(".lbl_accountname").text("Account Name of Credit Card");
      $(".lbl_bankname").text("Bank name of Credit Card Account");
      $(".lbl_accountno").text("Account Number Credit Card");
      $(".lbl_accountifsc").text("Bank IFSC Code of Credit Card");
      $(".lbl_branchname").text("Branch Name of Credit Card Bank");
      $(".lbl_branchaddress").text("Branch Address of Credit Card Bank");
    }else if($(this).val()=="Cash"){
      $("#div_accountifsc").show();
      $("#div_branchname").hide();
      $("#div_branchaddress").hide();
      $("#div_accountotherinfo").hide();
      $(".lbl_accountname").text("Account Name of Cash");
      $(".lbl_bankname").text("Bank name of Cash Bank");
      $(".lbl_accountno").text("Account Number Cash");
      $(".lbl_accountifsc").text("Bank IFSC Code of Cash");
      $(".lbl_branchname").text("Branch Name of Cash");
      $(".lbl_branchaddress").text("Branch Address of Cash Bank");
    }
});

function openModalForAddSupplierAccount(routurl){
  var form_data = new FormData();
  var routurl = $("#"+routurl+"").val();
  $("#webpagebodydiv").load(routurl+"webpages/master_addsupplierccount.php", function(){
    $("#webpagebodyview_modal").modal("show");
    $("#webpagebodyview_modal").css("z-index","9999");
    $("#modalheader").html("REGISTRATION OF NEW SUPPLIER");
  });  
}

$(document).on("click", ".opensupplieredmodal", function(){
  var routurl = $("#hidden_rooturl").val();
  $("#webpagebodydiv").load(routurl+"webpages/master_addsupplierccount.php", function(){
    $("#webpagebodyview_modal").modal("show");
    $("#webpagebodyview_modal").css("z-index","9999");
    $("#modalheader").html("REGISTRATION OF NEW SUPPLIER");
  });
});

function openModalForAddImprestAccount(routurl,paytoname=null,paytoid=null){
  var form_data = new FormData();
  var routurl = $("#"+routurl+"").val();
  if(paytoname){
    var paytoname = paytoname;
  }else{
    var paytoname="";
  }
  if(paytoid){
    var paytoid = paytoid;
  }else{
    var paytoid="";
  }
  $("#newimprespaytodivid").load(routurl+"webpages/master_addimprestaccount.php", {
      ptname: paytoname,
      ptid: paytoid
  }, function(){
    $("#newimprestpaytodetails_modal").modal("show");
    $("#newimprestpaytodetails_modal").css("z-index","9999");
  });  
}

$(document).on("click", ".openimprestauthorizedmodal", function(){
  var routurl = $("#hidden_rooturl").val();
  $("#newimprespaytodivid").load(routurl+"webpages/master_addimprestaccount.php", function(){
    $("#newimprestpaytodetails_modal").modal("show");
    $("#newimprestpaytodetails_modal").css("z-index","9999");
  });
});

function openModalForAddLoanAccount(routurl){
    var form_data = new FormData();
    var routurl = $("#"+routurl+"").val();
    $("#webpagebodydiv").load(routurl+"webpages/master_addloanaccount.php", function(){
    $("#webpagebodyview_modal").modal("show");
    $("#webpagebodyview_modal").css("z-index","9999");
    $("#modalheader").html("LOAN ACCOUNT REGISTRATION");
  });  
}

$(document).on("click", ".openloanauthorizedmodal", function(){
    var routurl = $("#hidden_rooturl").val();
    $("#webpagebodydiv").load(routurl+"webpages/master_addloanaccount.php", function(){
    $("#webpagebodyview_modal").modal("show");
    $("#webpagebodyview_modal").css("z-index","9999");
    $("#modalheader").html("LOAN ACCOUNT REGISTRATION");
  });
});


function openModalForAddAgency(routurl){
  var form_data = new FormData();
  var routurl = $("#"+routurl+"").val();
  $("#webpagebodydiv").load(routurl+"webpages/master_addagency.php", function(){
    $("#webpagebodyview_modal").modal("show");
    // $("#webpagebodyview_modal").css("z-index","9999");
    $("#modalheader").html("NEW AGENCY REGISTRATION");
  });  
}

function openModalForAddNewBankCharges(routurl,accountid=null){
  var form_data = new FormData();
  var baseurl = $("#"+routurl+"").val();
  $("#webpagebodydiv").load(baseurl+"webpages/master_addbankchargeskey.php", {
      accountid: accountid
  }, function(){
    $("#webpagebodyview_modal").css("z-index","10058")
      $("#webpagebodyview_modal").modal("show");
      $("#modalheader").html("CREATE BANK CHEARGES KEY");
  });  
}

function openModalForAddNewPayto(routurl){
  var form_data = new FormData();
  var baseurl = $("#"+routurl+"").val();
  $("#newpaytodivid").load(baseurl+"expenditure_expencessheet/newpayto_authorization.php", function(){
    $("#newpaytodetails_modal").css("z-index","10058")
      $("#newpaytodetails_modal").modal("show");
      $("#paytohead").html("NEW PAYTO DETAILS");
  });  
}

$(document).on("click", ".opennewbeneficiarymodal", function(){
  $("#newbeneficiarydetails_modal").modal("show");
});



function savetodbbankchargeskey(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,bankaccountid_eid,bankchargeskey_eid,loginname_eid,savetobtnid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var keyid = $("#"+savetobtnid+"").data("bankchargekey")??'';
  
  var bankaccountid = $("#"+bankaccountid_eid+"").val();
  var bankchargeskey = $("#"+bankchargeskey_eid+"").val();
  var loginname = $("#"+loginname_eid+"").val();
  if(bankaccountid=="" || bankaccountid=="null" || bankaccountid==null || bankaccountid=="undefined"){
    alertmessage('warning', 'Bank account name is required to create a key. Please select one.');
    return false;
  }else if(bankchargeskey=="" || bankchargeskey=="null" || bankchargeskey==null || bankchargeskey=="undefined"){
    alertmessage('warning', 'Enter the bank charges text to create a key');
    return false;
  }
  form_data.append("bankaccountid", bankaccountid);
  form_data.append("bankchargeskey", bankchargeskey);
  form_data.append("keyid", keyid);
  form_data.append("loginname", loginname);
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/masters_modal.php?action=savetodbbankchargeskey",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      if(res.status=="Success" || res.status=="success"){
        alertmessage('success', res.message);
        alert(bankaccountid);
        BankChargesUpdatetoBankdescription('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid',bankaccountid,'hidden_logingname');
        $("#"+bankchargeskey_eid+"").val('');
        $("#"+bankaccountid_eid+"").prop("selectedIndex", 0);
      }else{
        alertmessage('error', res.message);
      }
    }
  });
}

function BankChargesUpdatetoBankdescription(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,bankaccountid,loginname_eid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var loginname = $("#"+loginname_eid+"").val();
  if(bankaccountid=="" || bankaccountid=="null" || bankaccountid==null || bankaccountid=="undefined"){
    alertmessage('warning', 'Bank account name is required to create a key. Please select one.');
    return false;
  }
  form_data.append("bankaccountid", bankaccountid);
  form_data.append("loginname", loginname);
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/masters_modal.php?action=BankChargesUpdatetoBankdescription",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      if(res.status=="Success" || res.status=="success"){
        alertmessage('success', res.message);
      }else if(res.status=="Warning" || res.status=="warning"){
        alertmessage('warning', res.message);
      }else{
        alertmessage('error', res.message);
      }
    }
  });
}

function GetBankChargeskey_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,bankaccountid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  
  if(bankaccountid=="" || bankaccountid=="null" || bankaccountid==null || bankaccountid=="undefined"){
    alertmessage('warning', 'Bank account name is required to get a key. Please select one.');
    return false;
  }
  form_data.append("bankaccountid", bankaccountid);
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/masters_modal.php?action=GetBankChargeskey_callback",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      callback(res);
    }
  });
}

function ChangeCategoryOfRequestModal(routurl,requestno,requestcategoryid,reqbrkcount=''){
  
    var form_data = new FormData();
    var baseurl = $("#"+routurl+"").val();
    if(!requestno){
      var reqbrkcount = $("#brkcount").val();
      var requestno = $("#reqno").val();
      var requestcategoryid = $("#catid").val();
    }
    $("#replacement_purposedivid").load(baseurl+"webpages/master_changeRequestCategory.php?reqno="+requestno+"&reqcatid="+requestcategoryid+"&brkcount="+reqbrkcount, function(){
    // $("#webpagebodyview_modal").css("z-index","10058");
    $("#replacement_purposedetails_modal").modal("show");
      $("#replacement_purposeheader").html("Change the Category of Request ("+requestno+")");
    });
}

function checkpaytohaveOtherTypeoAccount(requestno,requestcategoryid,callback){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val();
  form_data.append("companyid", $("#hidden_companyid").val());
  form_data.append("subcompanyid", $("#hidden_subcompanyid").val());
  form_data.append("projectid", $("#hidden_projectid").val());
  form_data.append("requestno", requestno);
  form_data.append("requestcategoryid", requestcategoryid);
  // alert(requestcategoryid);
  $.ajax
  ({
      type: "POST",
      url: baseurl+"modal/expencesinfoofotheraccount.php?action=checkpaytohaveOtherTypeoAccount",
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

function savetodb_requestcategorytype(requestcategoryid_eid,isconfirm_eid,savetobtnid){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val();
  var requestcategoryid = $("input[name="+requestcategoryid_eid+"]:checked").val();
  if(!requestcategoryid){
    alertmessage('warning', 'Request category type is not defined, Please choose the request category type');
    return false;
  }
  if (!$("#" + isconfirm_eid).is(":checked")) {
    alertmessage('warning', 'Please check the checkbox to confirm. Do you want to make a change?');
    return false;
  }
  var requestno = $("#"+savetobtnid+"").data("datarequestno");
  if(!requestno){
    alertmessage('warning', 'Request not defined, Please refresh the page and try again');
    return false;
  }
  form_data.append("companyid", $("#hidden_companyid").val());
  form_data.append("subcompanyid", $("#hidden_subcompanyid").val());
  form_data.append("projectid", $("#hidden_projectid").val());
  form_data.append("requestno", requestno);
  form_data.append("requestcategoryid", requestcategoryid);
  $.ajax
  ({
      type: "POST",
      url: baseurl+"modal/expencesinfoofotheraccount.php?action=savetodb_requestcategorytype",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        if(res.status=="Success" || res.status=="success"){
         SweetAlertmessageFadein(res.status,res.message);
        }else{
          alertmessage(res.status,res.message);
          return false;
        }
      }
  });
}


function agencymasterlist_callback(routurl_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=agencymasterlist_callback",
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

function addupdate_agencydetails(routurl_eid,agencyname_eid,authpersonname_eid,authmobileno_eid,authwhatsappno_eid,authemailaddress_eid,agencyidval,savetobtnid){
    var form_data = new FormData();
    var baseurl = $("#"+routurl_eid+"").val();

    form_data.append("agencysno", $("#"+savetobtnid+"").data("agencysno"));
    form_data.append("agencyauthsno", $("#"+savetobtnid+"").data("agencyauthsno"));

    var agencyname = $('#'+agencyname_eid+'').val();
    form_data.append("agencyname", agencyname);

    var authpersonname = $('#'+authpersonname_eid+'').val();
    form_data.append("authpersonname", authpersonname);
    
    var authmobileno = $('#'+authmobileno_eid+'').val();
    form_data.append("authmobileno", authmobileno);
    
    var authwhatsappno = $('#'+authwhatsappno_eid+'').val();
    form_data.append("authwhatsappno", authwhatsappno);

    var authemailaddress = $('#'+authemailaddress_eid+'').val();
    form_data.append("authemailaddress", authemailaddress);    

    form_data.append("loginname", $("body").data("loginname"));

    $.ajax({
        url: baseurl+"modal/masters_modal.php?action=addupdate_agencydetails",
        type: 'POST',
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res) {
          // console.log(res);
          if(res.status=="Success"){
            // alertmessage('success', res.message);
            SweetAlertmessageFadein('success', res.message);
            // location.reload();
          }else{
            alertmessage('error', res.message);
            return false
          }
        }
    });
  // });
}


function checkexist_imprestpaytofrommaster(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,imprestpaytoname_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  
  var imprestpaytoname = $("#"+imprestpaytoname_eid+"").val();
  form_data.append("imprestpaytoname", imprestpaytoname);
  
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/masters_modal.php?action=checkexist_imprestpayto",
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

function checkexist_paytofrommaster(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoname_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  
  var paytoname = $("#"+paytoname_eid+"").val();
  form_data.append("paytoname", paytoname);
  
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/masters_modal.php?action=checkexist_payto",
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
function savetodbprojectaccountdetails_tomaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountname_eid,bannkname_eid,bannkaccountno_eid,bannkaccountifsccode_eid,bannkaccountsban_eid,bannkaccountswift_eid,bannkaccountsortcode_eid,bannkbranchname_eid,bannkbranchaddress_eid,banktype_ename,isremovecheckbox_eid,savetodbloginname_eid,savetodb_btnid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var isremove='';
  if($("#"+isremovecheckbox_eid+"").is(":checked")){
    form_data.append("readytoremove", 'yes');
    isremove="yes";
  }else{
    isremove="";
    form_data.append("readytoremove", 'no');
  }
  // alert(isremove);
  
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var accountname = $("#"+accountname_eid+"").val();
  // alert(accountname_eid +'==='+ accountname);
  if(accountname==null || accountname=="undefined" || accountname==""){
    if(isremove==""){
      alertmessage('error', 'Account name missing');
      $("#"+accountname_eid+"").focus();
      return false;
    }
  }
  form_data.append("accountname", accountname);
  var bannkname = $("#"+bannkname_eid+"").val();
  if(bannkname==null || bannkname=="undefined" || bannkname==""){
    if(isremove==""){
      alertmessage('error', 'Bank name missing');
      $("#"+bannkname_eid+"").focus();
      return false;
    }
  }
  form_data.append("bannkname", bannkname);
  var bannkaccountno = $("#"+bannkaccountno_eid+"").val();
  // alert(bannkaccountno);
  if(bannkaccountno==null || bannkaccountno=="undefined" || bannkaccountno=="" || bannkaccountno=="N/A"){
    if(isremove==""){
      // alert('hello');
      alertmessage('error', 'Bank account number is missing');
      $("#"+bannkaccountno_eid+"").focus();
      return false;
    }
  }

  form_data.append("bannkaccountno", bannkaccountno);
  var ifsccode = $("#"+bannkaccountifsccode_eid+"").val();
  form_data.append("ifsccode", ifsccode);
  var bannkbranchname = $("#"+bannkbranchname_eid+"").val();

  var bannkaccountsban = $("#bannkaccountsban_eid").val();
  form_data.append("bannkaccountsban", bannkaccountsban);

  var bannkaccountswift = $("#bannkaccountswift_eid").val();
  form_data.append("bannkaccountswift", bannkaccountswift);
  var bannkaccountsortcode = $("#bannkaccountsortcode_eid").val();
  form_data.append("bannkaccountsortcode", bannkaccountsortcode);

  form_data.append("bannkbranchname", bannkbranchname);
  var bannkbranchaddress = $("#"+bannkbranchaddress_eid+"").val();
  form_data.append("bannkbranchaddress", bannkbranchaddress);
  var dbsno = $("#"+bannkaccountno_eid+"").data("data_dbsno");
  form_data.append("dbsno", dbsno);

  // banktype_ename
  var banktype = $("input[name='"+banktype_ename+"']:checked").val();
  if(banktype==null || banktype=="undefined" || banktype=="" || banktype=="N/A"){
      alertmessage('error', 'Bank type selection is missing');
      return false;
  }
  form_data.append("banktype", banktype);

  form_data.append("loginname", $("body").data("loginname"));
  

  var savebyname = $("#"+savetodbloginname_eid+"").val();
  form_data.append("savebyname", savebyname);
  var btnval = $("#"+savetodb_btnid+"").html();
  $("#"+savetodb_btnid+"").html('Please wait data...');
  $("#"+savetodb_btnid+"").prop('disabled', true);
  
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/masters_modal.php?action=addupdateprojectaccuntdetails",
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
      if(res.length>0){
        for(var i=0; i<res.length; i++){
          if(res[i].status=="Success"){
            window.location.reload();
            alertmessage('success', res[i].message);
          }else{
            alertmessage('error', res[i].message);
            return false;
          }
        }
      }
    }
  });
}

function savetodbprojectFDaccountdetails_tomaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountname_eid,bannkname_eid,bannkaccountno_eid,bannkaccountifsccode_eid,savetodb_btnid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var isremove='';
  
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);

  var accountname = $("#"+accountname_eid+"").val();
  if(accountname==null || accountname=="undefined" || accountname==""){
      alertmessage('error', 'Account name missing');
      $("#"+accountname_eid+"").focus();
      return false;
  }
  form_data.append("accountname", accountname);
  
  var bannkname = $("#"+bannkname_eid+"").val();
  form_data.append("bannkname", bannkname);
  
  var bannkaccountno = $("#"+bannkaccountno_eid+"").val();
  form_data.append("bannkaccountno", bannkaccountno);
  
  var ifsccode = $("#"+bannkaccountifsccode_eid+"").val();
  form_data.append("ifsccode", ifsccode);
  
  var dbsno = $("#"+bannkaccountno_eid+"").data("data_dbsno");
  form_data.append("dbsno", dbsno);

  form_data.append("banktype", "FD");
  form_data.append("loginname", $("body").data("loginname"));
  
  var btnval = $("#"+savetodb_btnid+"").html();
  $("#"+savetodb_btnid+"").html('Please wait data...');
  $("#"+savetodb_btnid+"").prop('disabled', true);
  
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/masters_modal.php?action=savetodbprojectFDaccountdetails_tomaster",
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
      if(res.length>0){
        for(var i=0; i<res.length; i++){
          if(res[i].status=="Success"){
            // window.location.reload();
            alertmessage('success', res[i].message);
          }else{
            alertmessage('error', res[i].message);
            return false;
          }
        }
      }
    }
  });
}

function savetodb_supplierpaymentauthorization(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoname_eid,bankname_eid,accountnumber_eid,bankifsccode_eid,panno_eid,gstno_eid,paytomobileno_eid,paytoemailaddress_eid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);

  var paytoname = $("#"+paytoname_eid+"").val();
  form_data.append("paytoname", paytoname);
  var authpaytoid = $("#"+paytoname_eid+"").data("authpaytoid");
  form_data.append("authpaytoid", authpaytoid);
  
  var bankname = $("#"+bankname_eid+"").val();
  form_data.append("bankname", bankname);
  var accountnumber = $("#"+accountnumber_eid+"").val();
  form_data.append("accountnumber", accountnumber); 

  var bankifsccode = $("#"+bankifsccode_eid+"").val();
  form_data.append("bankifsccode", bankifsccode);

  var panno = $("#"+panno_eid+"").val();
  form_data.append("panno", panno);

  var gstno = $("#"+gstno_eid+"").val();
  form_data.append("gstno", gstno);

  var paytomobileno = $("#"+paytomobileno_eid+"").val();
  form_data.append("paytomobileno", paytomobileno);

  var paytoemailaddress = $("#"+paytoemailaddress_eid+"").val();
  form_data.append("paytoemailaddress", paytoemailaddress);


  if(paytoname=="" || paytoname==null || paytoname=="undefined"){
    alertmessage('error','Supplier name is missing');
    $("#"+paytoname_eid+"").focus();
    return false;
  }else if(bankname=="" || bankname==null || bankname=="undefined"){
    alertmessage('error','Supplier bank name is missing');
    $("#"+bankname_eid+"").focus();
    return false;
  }else if(accountnumber=="" || accountnumber==null || accountnumber=="undefined"){
    alertmessage('error','Supplier bank account number is missing');
    $("#"+accountnumber_eid+"").focus();
    return false;
  }else if(bankifsccode=="" || bankifsccode==null || bankifsccode=="undefined"){
    alertmessage('error','Supplier bank ifsc code is missing');
    $("#"+bankifsccode_eid+"").focus();
    return false;
  }
  // else if(panno=="" || panno==null || panno=="undefined"){
  //   alertmessage('error','Supplier Pan no is missing');
  //   $("#"+panno_eid+"").focus();
  //   return false;
  // }
  /*else if(gstno=="" || gstno==null || gstno=="undefined"){
    alertmessage('error','Supplier GST no is missing');
    $("#"+gstno_eid+"").focus();
    return false;
  }else if(paytomobileno=="" || paytomobileno==null || paytomobileno=="undefined"){
    alertmessage('error','Supplier mobile no is missing');
    $("#"+paytomobileno_eid+"").focus();
    return false;
  }*/
  var addedby = $("body").data("loginid");
  form_data.append("addedby", addedby);
  
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/masters_modal.php?action=supplierauthorization",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
      if(res.status=="Success"){
        alertmessage('success',res.message);
        // location.reload();
        $(".modal.show").modal("hide");
      }else{
        alertmessage('error',res.message);
      }
    }
  });
}

function savetodb_cashpaymentauthorization(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoname_eid,bankname_eid,accountnumber_eid,bankifsccode_eid,paytomobileno_eid,paytoemailaddress_eid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);

  var paytoname = $("#"+paytoname_eid+"").val();
  form_data.append("paytoname", paytoname);

  form_data.append("authpaytoid", $("#"+paytoname_eid+"").data("authpaytoid"));
  
  var bankname = $("#"+bankname_eid+"").val();
  form_data.append("bankname", bankname);
  var accountnumber = $("#"+accountnumber_eid+"").val();

  form_data.append("accountnumber", accountnumber); 
  var bankifsccode = $("#"+bankifsccode_eid+"").val();

  form_data.append("bankifsccode", bankifsccode);

  var paytomobileno = $("#"+paytomobileno_eid+"").val();
  form_data.append("paytomobileno", paytomobileno);

  var paytoemailaddress = $("#"+paytoemailaddress_eid+"").val();
  form_data.append("paytoemailaddress", paytoemailaddress);


  if(paytoname=="" || paytoname==null || paytoname=="undefined"){
    alertmessage('error','Payto name is missing');
    $("#"+paytoname_eid+"").focus();
    return false;
  }
  // else if(bankname=="" || bankname==null || bankname=="undefined"){
  //   alertmessage('error','Payto bank name is missing');
  //   $("#"+bankname_eid+"").focus();
  //   return false;
  // }else if(accountnumber=="" || accountnumber==null || accountnumber=="undefined"){
  //   alertmessage('error','Payto bank account number is missing');
  //   $("#"+accountnumber_eid+"").focus();
  //   return false;
  // }else if(bankifsccode=="" || bankifsccode==null || bankifsccode=="undefined"){
  //   alertmessage('error','Payto bank ifsc code is missing');
  //   $("#"+bankifsccode_eid+"").focus();
  //   return false;
  // }else if(paytomobileno=="" || paytomobileno==null || paytomobileno=="undefined"){
  //   alertmessage('error','Payto mobile no is missing');
  //   $("#"+paytomobileno_eid+"").focus();
  //   return false;
  // }
  var addedby = $("body").data("loginid");
  form_data.append("addedby", addedby);
  
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/masters_modal.php?action=cashpaymentauthorization",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
      if(res.status=="Success"){
        alertmessage('success',res.message);
        location.reload();
      }else{
        alertmessage('error',res.message);
      }
    }
  });
}

function savetodb_imprestauthorization(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoname_eid,bankname_eid,accountnumber_eid,bankifsccode_eid,projectauthorizationlist_tblid,paytomobileno_eid,paytoemailaddress_eid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var paytoname = $("#"+paytoname_eid+"").val();
  form_data.append("paytoname", paytoname);
  
  var bankname = $("#"+bankname_eid+"").val();
  form_data.append("bankname", bankname);
  var accountnumber = $("#"+accountnumber_eid+"").val();

  form_data.append("accountnumber", accountnumber); 
  var bankifsccode = $("#"+bankifsccode_eid+"").val();

  form_data.append("bankifsccode", bankifsccode);
  var loginid = $("body").data("loginid");
  form_data.append("loginid", loginid);

  var paytomobileno = $("#"+paytomobileno_eid+"").val();
  form_data.append("paytomobileno", paytomobileno);

  var paytoemailaddress = $("#"+paytoemailaddress_eid+"").val();
  form_data.append("paytoemailaddress", paytoemailaddress);


  if(paytoname=="" || paytoname==null || paytoname=="undefined"){
    alertmessage('error','Payto name is missing');
    $("#"+paytoname_eid+"").focus();
    return false;
  }else if(bankname=="" || bankname==null || bankname=="undefined"){
    alertmessage('error','Payto bank name is missing');
    $("#"+bankname_eid+"").focus();
    return false;
  }else if(accountnumber=="" || accountnumber==null || accountnumber=="undefined"){
    alertmessage('error','Payto bank account number is missing');
    $("#"+accountnumber_eid+"").focus();
    return false;
  }else if(bankifsccode=="" || bankifsccode==null || bankifsccode=="undefined"){
    alertmessage('error','Payto bank ifsc code is missing');
    $("#"+bankifsccode_eid+"").focus();
    return false;
  }else if(paytomobileno=="" || paytomobileno==null || paytomobileno=="undefined"){
    alertmessage('error','Payto mobile no is missing');
    $("#"+paytomobileno_eid+"").focus();
    return false;
  }
  var Authprj;
    Authprj = storeTblValues1('#'+projectauthorizationlist_tblid+'');
    Authprj = JSON.stringify(Authprj);
    function storeTblValues1(mytable)
    {
      var TableData_apj = new Array();
      var mytableid = mytable + ' tr';
      $(mytableid).each(function(row, tr){
        if($(tr).closest('tr').find('td').find('input.checkallprjl').is(":checked")){
          TableData_apj[row]={
            "companyid" :$(tr).closest('tr').find('td.cmpid_td').text()
            , "subcompanyid" :$(tr).closest('tr').find('td.subcmpid_td').text()
            , "projectid" :$(tr).closest('tr').find('td.projectid_td').text()
          }
        }    
      }); 
      TableData_apj.shift();  // first row will be empty - so remove
      return TableData_apj;
    }
    form_data.append('authorizedproject', Authprj);
    $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=imprestauthorizationwithproject",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        if(res.status=="Success"){
          alertmessage('success',res.message);
          $("#"+paytoname_eid+"").val('');
          $("#"+bankname_eid+"").val('');
          $("#"+accountnumber_eid+"").val('');
          $("#"+bankifsccode_eid+"").val('');
          // location.reload();
          if($("#checkallprj").is(":checked")){
            $("#checkallprj").trigger("click");
          }else{
            $(".checkallprjl").prop("checked", false);
          }
          $("#newimprestpaytodetails_modal").modal("hide");
          request_imprestpaytofrommaster_todropdown("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","","imp_paytoname");
        }else{
          alertmessage('error',res.message);
        }
      }
    });
}

function addupdate_loanaccount(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,typeofloan_ename,paytoname_eid,bankname_eid,accountnumber_eid,bankifsccode_eid,paytomobileno_eid,paytoemailaddress_eid,savetodbbtnid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);

  var loantype = $("input[name='"+typeofloan_ename+"']:checked").val();
  form_data.append("loantype", loantype);

  var paytoname = $("#"+paytoname_eid+"").val();
  form_data.append("paytoname", paytoname);
  
  var authpaytoid = $("#"+paytoname_eid+"").data("authpaytoid");
  form_data.append("authpaytoid", authpaytoid);
  
  var bankname = $("#"+bankname_eid+"").val();
  form_data.append("bankname", bankname);
  
  var accountnumber = $("#"+accountnumber_eid+"").val();
  form_data.append("accountnumber", accountnumber);

  var bankifsccode = $("#"+bankifsccode_eid+"").val();
  form_data.append("bankifsccode", bankifsccode);

  var paytomobileno = $("#"+paytomobileno_eid+"").val();
  form_data.append("paytomobileno", paytomobileno);

  var paytoemailaddress = $("#"+paytoemailaddress_eid+"").val();
  form_data.append("paytoemailaddress", paytoemailaddress);

  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);

  if(paytoname=="" || paytoname==null || paytoname=="undefined"){
    alertmessage('error','Payto name is missing');
    $("#"+paytoname_eid+"").focus();
    return false;
  }else if(bankname=="" || bankname==null || bankname=="undefined"){
    alertmessage('error','Payto bank name is missing');
    $("#"+bankname_eid+"").focus();
    return false;
  }else if(accountnumber=="" || accountnumber==null || accountnumber=="undefined"){
    alertmessage('error','Payto bank account number is missing');
    $("#"+accountnumber_eid+"").focus();
    return false;
  }else if(bankifsccode=="" || bankifsccode==null || bankifsccode=="undefined"){
    alertmessage('error','Payto bank ifsc code is missing');
    $("#"+bankifsccode_eid+"").focus();
    return false;
  }
  
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/masters_modal.php?action=addupdateloanaccount",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
   dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
      if(res.status=="Success"){
        alertmessage('success',res.message);
        $("#"+paytoname_eid+"").data("authpaytoid","");
        $(".modal.show").modal("hide");
      }else{
        alertmessage('error',res.message);
      }
    }
  });
}

function creditcarddetailsfrommaster(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  // alert(shortby+'==='+shortbyvalue);
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/masters_modal.php?action=creditcarddetailsfrommaster",
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

function openModalForAddCreditCard(routurl){
  var form_data = new FormData();
  var routurl = $("#"+routurl+"").val();
  $("#webpagebodydiv").load(routurl+"webpages/master_addcreditcard.php", function(){
    $("#webpagebodyview_modal").modal("show");
    $("#webpagebodyview_modal").css("z-index","9999");
    $("#modalheader").html("NEW CREDIT CARD REGISTRATION");
  });  
}

function addupdate_creditcardetails(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,submitbtnid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();

  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);

  var bankname = $('#ccbankname').val();
  form_data.append("bankname", bankname);
  var accountno = $('#ccaccountno').val();
  form_data.append("accountno", accountno);
  var billingaddress = $('#billingaddress').val();
  form_data.append("billingaddress", billingaddress);

  var cardtype = $('#cardtype').val();
  form_data.append("cardtype", cardtype);

  var cardholdername = $('#cardholdername').val();
  form_data.append("cardholdername", cardholdername);

  var cardnumber = $('#cardnumber').val();
  form_data.append("cardnumber", cardnumber);

  var expirymonth = $('#expirymonth').val();
  form_data.append("expirymonth", expirymonth);

  var expiryyear = $('#expiryyear').val();
  form_data.append("expiryyear", expiryyear);

  var cvv = $('#cvv').val();
  form_data.append("cvv", cvv);

  var loginname = $('body').data("loginname");
  form_data.append("loginname", loginname);
  $.ajax({
      url: baseurl+"modal/masters_modal.php?action=addupdatecreditcarddetails",
      type: 'POST',
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res) {
        // console.log(res);
        if(res.status=="Success"){
          alertmessage('success', res.message);
          location.reload();
        }else{
          alertmessage('error', res.message);
          return false
        }
      },
      error: function(xhr, status, error) {
          alert('An error occurred: ' + error);
      }
  });
}

function openModalForAuthorizationCCD(routurl,paytoid,ccid,ccdno,ccdtype){
  var form_data = new FormData();
  var routurl = $("#"+routurl+"").val();
  $("#webpagebodydiv").load(routurl+"webpages/master_addcreditcardauth.php?ptid="+paytoid+"&ccid="+ccid, function(){
    $("#webpagebodyview_modal").modal("show");
    $("#webpagebodyview_modal").css("z-index","9999");
    $("#modalheader").html("Update of Credit Card ("+ccdno+"-"+ccdtype+") Authorization");
  }); 
}

function addupdate_creditcarauthorization(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,authpersonname_eid,authmobileno_eid,authemailaddress_eid,authbankname_eid,authaccountno_eid,authbankifsccode_eid,updateccdauthbtnid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();

  // $('#'+submitbtnid+'').click(function() {
    var companyid = $("#"+companyid_eid+"").val();
    form_data.append("companyid", companyid);
    var subcompanyid = $("#"+subcompanyid_eid+"").val();
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("#"+projectid_eid+"").val();
    form_data.append("projectid", projectid);

    var authpersonname = $('#'+authpersonname_eid+'').val();
    form_data.append("authpersonname", authpersonname);
    form_data.append("authpaytoid", $('#'+authpersonname_eid+'').data('authpaytoid'));
    form_data.append("authccid", $('#'+authpersonname_eid+'').data('authccid'));


    var authmobileno = $('#'+authmobileno_eid+'').val();
    form_data.append("authmobileno", authmobileno);
    var authemailaddress = $('#'+authemailaddress_eid+'').val();
    form_data.append("authemailaddress", authemailaddress);

    var authbankname = $('#'+authbankname_eid+'').val();
    form_data.append("authbankname", authbankname);

    var authaccountno = $('#'+authaccountno_eid+'').val();
    form_data.append("authaccountno", authaccountno);

    var authbankifsccode = $('#'+authbankifsccode_eid+'').val();
    form_data.append("authbankifsccode", authbankifsccode);

    var loginname = $('body').data("loginname");
    form_data.append("loginname", loginname);
    $.ajax({
        url: baseurl+"modal/masters_modal.php?action=addupdate_creditcarauthorization",
        type: 'POST',
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res) {
          // console.log(res);
          if(res.status=="Success"){
            alertmessage('success', res.message);
            location.reload();
          }else{
            alertmessage('error', res.message);
            return false
          }
        },
        error: function(xhr, status, error) {
            alert('An error occurred: ' + error);
        }
    });
  // });
}


function gettotalentriesfrombook(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,Epurposecode,callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("purposecode", Epurposecode);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=totalentriesfrombook",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      success: function(res)
      {
        callback(res);
      }
    });
}
function update_replacenewpurposewitholdpurpose(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,oldpurposename_eid,newpurposename_eid,isconfirm_eid,savetobtnid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);

  var oldpurposename = $("#"+oldpurposename_eid+"").val();
  if(oldpurposename=="" || oldpurposename==null || oldpurposename=="undefined"){
    alertmessage("error", "Wrong/Missing replace from purpose");
    return false;
  }
  form_data.append("oldpurposename", oldpurposename);

  var newpurposename = $("#"+newpurposename_eid+"").val();
  if(newpurposename=="" || newpurposename==null || newpurposename=="undefined"){
    alertmessage("error", "Wrong/Missing replace to purpose");
    return false;
  }
  form_data.append("newpurposename", newpurposename);

  if(!$("#"+isconfirm_eid+"").is(":checked")){
    alertmessage("error", "Please checked and confirm it want to replace selected purpose");
    return false;
  }
  var purposetype = $("#"+savetobtnid+"").data("purposetypefrmdata");
  form_data.append("purposetype", purposetype);

  var breakdwonsnos = $("#"+savetobtnid+"").data("rpbreakdwonsno");
  form_data.append("breakdwonsnoarray", breakdwonsnos);
  // console.log(breakdwonsnos);  

  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);
  // alert(appenddropid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=replacenewpurposewithfromold",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
          // console.log(res);
        if(res.status=="Success"){
          alertmessage('success',res.message);
          $("#replacement_purposedetails_modal").modal("hide");
        }else if(res.status=="Error"){
          alertmessage('error',res.message);
          return false;
        }
        $(".refreshpurpose").trigger("click");
      }
    });
}

// new function created by Chatur Singh
function addnewpurpose_withprovidedcode(purposecategoryid,purposesubcategoryid,purposename,purposecode,providedcode,visiblestatus,purposetype,savetobtnid){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val();
  var companyid = $("#hidden_companyid").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#hidden_subcompanyid").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#hidden_projectid").val();
  form_data.append("projectid", projectid);
  
  form_data.append("purposecategoryid", purposecategoryid);
  form_data.append("purposesubcategoryid", purposesubcategoryid);
  
  if(purposename=="" || purposename==null || purposename=="undefined"){
    alertmessage("error", "Purpose name is a required");
    return false;
  }
  form_data.append("purposename", purposename);
  
  if(providedcode=="" || providedcode==null || providedcode=="undefined"){
    alertmessage("error", "Provide code is required");
    return false;
  }
  form_data.append("purposecode", purposecode);
  form_data.append("providedcode", providedcode);


  form_data.append("visiblestatus", visiblestatus);
  form_data.append("purposetype", purposetype);

  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=addnewpurposewithprovidedcode",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
          // console.log(res);
          if(res.length>0){
            for (var i = 0; i < res.length; i++) {
              if(res[i].status=="Success"){
                alertmessage('success',res[i].message);
              }else if(res.status=="Error"){
                alertmessage('error',res[i].message);
                // return false;
              }              
            }
            $(".waitloaderfooter").hide();
            $(".reloadpurpose").trigger("click");
          }
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}

function savetodb_newpurposedetails(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,purposecategoryid_eid,purposesubcategoryid_eid,purposename_eid,isconfirm_eid,savetobtnid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  if(!$("#"+isconfirm_eid+"").is(":checked")){
    alertmessage("error", "Please checked and confirm it all details are correct");
    return false;
  }
  var purposecategoryid = $("#"+purposecategoryid_eid+"").val();
  if(purposecategoryid=="" || purposecategoryid==null || purposecategoryid=="undefined"){
    alertmessage("error", "Wrong/Missing category of purpose");
    return false;
  }
  form_data.append("purposecategoryid", purposecategoryid);

  var purposesubcategoryid = $("#"+purposesubcategoryid_eid+"").val();
  if(purposesubcategoryid=="" || purposesubcategoryid==null || purposesubcategoryid=="undefined"){
    alertmessage("error", "Wrong/Missing sub-category of purpose");
    return false;
  }
  form_data.append("purposesubcategoryid", purposesubcategoryid);
  var purposename = $("#"+purposename_eid+"").val();
  if(purposename=="" || purposename==null || purposename=="undefined"){
    alertmessage("error", "Please enter the purpose name");
    return false;
  }
  form_data.append("purposename", purposename);
  var appenddropid = $("#"+purposename_eid+"").data("purposeappenddropid");
  var purposetype = $("#"+savetobtnid+"").data("purposetypefrmdata");
  form_data.append("purposetype", purposetype);

  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);

  var refreshsubcatid = $("#"+savetobtnid+"").data("subcaterefreshid");
  var tablidsubcat = $("#"+savetobtnid+"").data("subcatetblid");
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=addnewpurposewithother",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
          if(res.status=="Success"){
            alertmessage('success',res.message);
            if(appenddropid){
              $("#"+appenddropid+"").append("<option value='"+res.purposecode+"'>"+res.purposename+"</option>");
              $("#"+appenddropid+"").val(res.purposecode).trigger("change");
            }
            
            if(refreshsubcatid){
              $("#"+refreshsubcatid+"").trigger("click");
            }
            // alert(purposesubcategoryid);
            setTimeout(() => {
              if (tablidsubcat) {
                const checkboxes = $("#" + tablidsubcat + " input[type='checkbox']");
                if (checkboxes.length) {
                  checkboxes.prop("checked", false);
                  checkboxes.each(function () {
                    if ($(this).val() === purposesubcategoryid) {
                      $(this).prop("checked", true).trigger("click");
                    }
                  });
                } else {
                  console.error("No checkbox found inside #" + tablidsubcat);
                }
              } else {
                console.error("tablidsubcat is undefined or null.");
              }
            }, 2000);


          }else if(res.status=="Error"){
            alertmessage('error',res.message);
            return false;
          }
          $(".refreshpurpose").trigger("click");
      }
    });
}

function updatetodb_purposedetails(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,purposecategoryid_eid,purposesubcategoryid_eid,purposecode_eid,purposename_eid,isconfirm_eid,savetobtnid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  if(!$("#"+isconfirm_eid+"").is(":checked")){
    alertmessage("error", "Please checked and confirm it all details are correct");
    return false;
  }
  var purposecategoryid = $("#"+purposecategoryid_eid+"").val();
  if(purposecategoryid=="" || purposecategoryid==null || purposecategoryid=="undefined"){
    alertmessage("error", "Wrong/Missing category of purpose");
    return false;
  }
  form_data.append("purposecategoryid", purposecategoryid);

  var purposesubcategoryid = $("#"+purposesubcategoryid_eid+"").val();
  if(purposesubcategoryid=="" || purposesubcategoryid==null || purposesubcategoryid=="undefined"){
    alertmessage("error", "Wrong/Missing sub-category of purpose");
    return false;
  }
  form_data.append("purposesubcategoryid", purposesubcategoryid);

  var purposecode = $("#"+purposecode_eid+"").val();
  if(purposecode=="" || purposecode==null || purposecode=="undefined"){
    alertmessage("error", "Missing the purpose code");
    return false;
  }
  form_data.append("purposecode", purposecode);

  var purposename = $("#"+purposename_eid+"").val();
  if(purposename=="" || purposename==null || purposename=="undefined"){
    alertmessage("error", "Please enter the purpose name");
    return false;
  }
  form_data.append("purposename", purposename);

  var purposetype = $("#"+savetobtnid+"").data("purposetypefrmdata");
  var appenddropid = $("#"+purposename_eid+"").data("purposeappenddropid");
  form_data.append("purposetype", purposetype);

  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);

  var refreshsubcatid = $("#"+savetobtnid+"").data("subcaterefreshid");
  var tablidsubcat = $("#"+savetobtnid+"").data("subcatetblid");
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=updateeditedpurpose",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
          if(res.status=="Success"){
            alertmessage('success',res.message);
            if(appenddropid){
              $("#"+appenddropid+"").append("<option value='"+res.purposecode+"'>"+res.purposename+"</option>");
              $("#"+appenddropid+"").val(res.purposecode).trigger("change");
            }
            $(".refreshpurpose").trigger("click");
            $("#"+purposecode_eid+"").val('');
            $("#"+purposename_eid+"").val('');
            if(isconfirm_eid){
              $("#"+isconfirm_eid+"").prop("checked", false);
            }
          }else if(res.status=="Error"){
            alertmessage('error',res.message);
            return false;
          }
         
      }
    });
}


function preview_newpurposedetails(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,purposecategoryid_eid, purposesubcategoryid_eid, purposename_eid,previewid,savetobtnid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var purposecategoryid = $("#"+purposecategoryid_eid+"").val();
  form_data.append("purposecategoryid", purposecategoryid);
  var purposesubcategoryid = $("#"+purposesubcategoryid_eid+"").val();
  form_data.append("purposesubcategoryid", purposesubcategoryid);
  var purposename = $("#"+purposename_eid+"").val();
  form_data.append("purposename", purposename);
  var appenddropid = $("#"+purposename_eid+"").data("purposeappenddropid");
  var purposetype = $("#"+savetobtnid+"").data("purposetypefrmdata");
  form_data.append("purposetype", purposetype);
  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);
  // alert(purposecategoryid+'=='+purposesubcategoryid+'=='+purposename+'=='+purposetype);
  if(purposecategoryid && purposesubcategoryid && purposename && purposetype){
    $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=preview_newpurposedetails",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $("#"+previewid+"").html();
        for(var i=0; i<res.length; i++){
          if(res[i].status=="Success"){
            if(previewid){
              $("#"+previewid+"").html("<h4>Preview of the purpose with category & subcategory before save</h4><table class='table table-all' style='font-size:20px; border:1px;'><thead><tr><th>Provide Code</th><th>Purpose</th><th>Category</th><th>Sub-Category</th></tr></thead><tbody><tr><td>"+res[i].providedcode+"</td><td>"+res[i].purposename+"</td><td>"+res[i].category+"</td><td>"+res[i].subcategory+"</td></tr></tbody></table>");
            }
          }else if(res[i].status=="Error"){
            alertmessage('error',res[i].message);
            return false;
          }
        }
      }
    });
  }
}

function maxnew_providedcode(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,purposecategoryid, purposesubcategoryid,purposetype,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("purposecategoryid", purposecategoryid);
  form_data.append("purposesubcategoryid", purposesubcategoryid);
  form_data.append("purposetype", purposetype);
  if(purposecategoryid && purposesubcategoryid && purposetype){
    $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=maxnew_providedcode",
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
}

function maxnew_subcategoryid_client(routurl_eid,purposetype,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#hidden_companyid").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#hidden_subcompanyid").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#hidden_projectid").val();
  form_data.append("projectid", projectid);
  form_data.append("purposetype", purposetype);
  if(purposetype){
    $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=maxnew_subcategoryid_client",
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
}

function maxnew_providedcode_client(routurl_eid,purposesubcategoryid,purposetype,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#hidden_companyid").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#hidden_subcompanyid").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#hidden_projectid").val();
  form_data.append("projectid", projectid);
  form_data.append("purposesubcategoryid", purposesubcategoryid);
  form_data.append("purposetype", purposetype);
  if(purposesubcategoryid && purposetype){
    $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=maxnew_providedcode_client",
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
}

function subcategorylistwithcategoryid_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,categoryidval,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("categoryid", categoryidval);
  // alert(baseurl);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=subcategorylistwithcategoryid",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function subcategorylistFromMainMaster_callback(routurl_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  // alert(baseurl);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=subcategorylistfrommastertbl",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function subcategorylistwithcategoryid_credit_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,categoryidval,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("categoryid", categoryidval);
  // alert(baseurl);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=subcategorylistwithcategoryid_credit",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function getallpurposewithprojectidlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  // alert(baseurl);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=getallpurposelistofproject",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}
function getallpurposewithprojectidlist_credit(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
   // alert(baseurl);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=getallpurposelistofproject_credit",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function getallpurposewithprojectid_withstatus(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  // alert(baseurl);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=getallpurposewithprojectid_withstatus",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function getallpurposewithprojectid_creditwithstatus(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  // alert(baseurl);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=getallpurposewithprojectid_creditwithstatus",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function getallpurposewithprojectidlist_deposit(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  // alert(baseurl);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=getallcreditpurposelistofproject",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function getallpurposewithprojectidlist_credit(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  // alert(baseurl);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=getallpurposelistofproject_credit",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function purposelist_withcategorysubcategory_credit(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,categoryid,subcategoryid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("categoryid", categoryid);
  form_data.append("subcategoryid", subcategoryid);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=purposeliswithcategoryandsubcategory_credit",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function purposelist_withcategoryid(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,categoryid,alreadyexist,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("categoryid", categoryid);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=purposelist_withcategoryid",
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
                $("#"+dropdownid+"").append("<option value='' selected>Select purpose name</option>");
            }
            for (var i = 0; i < res.length; i++) {
              $("#"+dropdownid+"").append("<option value='"+res[i].purposecode+"'>"+res[i].purposewithprovidedcode+"</option>");
              if(res.length==1){
                $("#"+dropdownid+"").val(res[i].purposecode).trigger("change");
              }
            }
            if(alreadyexist){
              $("#"+dropdownid+"").val(alreadyexist).trigger("change");
            }
          }
        }
    });
}

function purposelist_withcategorysubcategory(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,categoryid,subcategoryid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("categoryid", categoryid);
  form_data.append("subcategoryid", subcategoryid);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=purposeliswithcategoryandsubcategory",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function purposelist_withlikelysubcategory(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,likelysubcategoryname,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("likelysubcategoryname", likelysubcategoryname);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=purposeliswithlikelysubcategory",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function purposesubcategorylist_withlikelysubcategory(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,categoryid,likelysubcategoryname,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("categoryid", categoryid);
  form_data.append("likelysubcategoryname", likelysubcategoryname);
  // alert(categoryid+'--'+likelysubcategoryname);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=subcategoryliswithlikelysubcategory",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function purposesubcategorylist_withlikelyclientsubcategory(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,categoryid,likelysubcategoryname,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("categoryid", categoryid);
  form_data.append("likelysubcategoryname", likelysubcategoryname);
  // alert(categoryid+'--'+likelysubcategoryname);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=clientsubcategoryliswithlikelysubcategory",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function purposelist_all(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,likelysubcategoryname,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("purposecategory", likelysubcategoryname);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=purposelistwithcategory",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          callback(res);
        }
    });
}

function purposecodefrommaster_creditside(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,pcategoryidval,psubcategoryidval,alreadyexistval,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("categoryidval", pcategoryidval);
  form_data.append("subcategoryidval", psubcategoryidval);
  
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=purposecodefrommaster_creditside",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
           //console.log(res);
          $("#"+dropdownid+"").empty();
          if(res.length>0){
            if (res.length>=1) 
            {
                $("#"+dropdownid+"").append("<option value='' selected>Select purpose name</option>");
            }
            for (var i = 0; i < res.length; i++) {
              $("#"+dropdownid+"").append("<option data-category='"+res[i].pcategory+"' data-categoryid='"+res[i].pcategorycode+"' data-subcategory='"+res[i].psub_category+"' data-subcategoryid='"+res[i].psub_categoryid+"' data-purposename='"+res[i].purpose+"' data-purposecode='"+res[i].purposecode+"' data-purposeid='"+res[i].purposeid+"' data-providedcode='"+res[i].provided_code+"'  value='"+res[i].purposecode+"'>"+res[i].purpose+"</option>");
              if(res.length==1){
                $("#"+dropdownid+"").val(res[i].purposecode).trigger("change");
              }
            }
            if(alreadyexistval){
              $("#"+dropdownid+"").val(alreadyexistval).trigger("change");
            }
          }else{
            $("#"+dropdownid+"").append("<option value='' selected>Not available purpoe</option>");
          }
        }
    });
}
function purposecodefrommaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,pcategoryidval,psubcategoryidval,alreadyexistval,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("categoryidval", pcategoryidval);
  form_data.append("subcategoryidval", psubcategoryidval);
  
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=purposecodefrommaster",
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
            if (res.length>=1) 
            {
                $("#"+dropdownid+"").append("<option value='' selected>Select purpose name</option>");
            }
            for (var i = 0; i < res.length; i++) {
              $("#"+dropdownid+"").append("<option data-category='"+res[i].pcategory+"' data-categoryid='"+res[i].pcategorycode+"' data-subcategory='"+res[i].psub_category+"' data-subcategoryid='"+res[i].psub_categoryid+"' data-purposename='"+res[i].purpose+"' data-purposecode='"+res[i].purposecode+"' data-purposeid='"+res[i].purposeid+"' data-providedcode='"+res[i].provided_code+"' value='"+res[i].purposecode+"'>"+res[i].purpose+"</option>");
              if(res.length==1){
                $("#"+dropdownid+"").val(res[i].purposecode).trigger("change");
              }
            }
            if(alreadyexistval){
              $("#"+dropdownid+"").val(alreadyexistval).trigger("change");
            }
          }else{
            $("#"+dropdownid+"").append("<option value='' selected>Not available purpoe</option>");
          }
        }
    });
}

function payerdetailsfrommaster_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  // alert(projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=payerlist_callback",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $(".waitloaderfooter").hide();
        callback(res);
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}


function ispaytodetailshave_callback(routurl_eid,companyid_eid,subcompanyid_eid,paytoid,ishavedetails,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("paytoid", paytoid);
  form_data.append("ishavedetails", ishavedetails);
  // alert(projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=ispaytohavedetails_callback",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $(".waitloaderfooter").hide();
        callback(res);
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}

function paytodetailsfrommaster_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  // alert(projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=paytolist_callback",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $(".waitloaderfooter").hide();
        callback(res);
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}

function paytodetailsfrommaster_dropdownlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,alreadyexist,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  // alert(projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=paytolist_callback",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        $("#"+dropdownid+"").empty();
          if(res.length>0){
            if (res.length>1) 
            {
                $("#"+dropdownid+"").append("<option value='' selected>Select</option>");
            }
            for (var i = 0; i < res.length; i++) {
              $("#"+dropdownid+"").append("<option data-optionpaytoname='"+res[i].payto+"' value='"+res[i].paytoid+"'>"+res[i].payto+"</option>");
              if(res.length==1){
                $("#"+dropdownid+"").val(res[i].paytoid).trigger("change");
              } 
            }
            if(alreadyexist!=""){
                $("#"+dropdownid+"").val(alreadyexist).trigger("change");
            }
          }
      }
    });
}

function beneficiarydetailsfrommaster_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  // alert(projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=beneficiarydetailsfrommaster_callback",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $(".waitloaderfooter").hide();
        callback(res);
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}

function loginuserdetails_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  // alert(projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=loginuserdetails_callback",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $(".waitloaderfooter").hide();
        callback(res);
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}

function paytobankdetailsfrommaster_callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoidval,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("paytoidval", paytoidval);
  // alert(paytoidval);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=paytofulldetails_callback",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        $(".waitloaderfooter").hide();
        callback(res);
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}

function updateeditedpaytodetailsfrommasterlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoname_eid,paytobankname_eid,paytobankaccountno_eid,paytobankifscocde_eid,updateto_class,clicktoupdatebtnid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);

  var paytoidval = $("#"+clicktoupdatebtnid+"").data("paytoidval");
  form_data.append("paytoid", paytoidval);
  var paytoname = $("#"+paytoname_eid+"").val();
  form_data.append("paytoname", paytoname);
  var paytobankname = $("#"+paytobankname_eid+"").val();
  form_data.append("paytobankname", paytobankname);
  var paytobankaccountno = $("#"+paytobankaccountno_eid+"").val();
  form_data.append("paytobankaccountno", paytobankaccountno);
  var paytobankifscocde = $("#"+paytobankifscocde_eid+"").val();
  form_data.append("paytobankifscocde", paytobankifscocde);
  let checkedValues = [];
  $('.'+updateto_class+':checked').each(function() {
      checkedValues.push($(this).val());
  });
  form_data.append("paytofor", checkedValues);
  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=updatepaytodetails",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $(".waitloaderfooter").hide();
        if(res.length>0){
          for(var i=0; i<res.length; i++){
            if(res[i].status=="Success"){
              alertmessage('success',res[i].message);
            }else{
              alertmessage('error',res[i].message);
              return false;
            }
          }
          $("#loadpaytomaster").trigger("click");
        }
        
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}

function editedpayto_fulldetails(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoname_eid,paytobankname_eid,paytobankaccountno_eid,paytobankifscocde_eid,paytobankiban_eid,paytobanswiftcocde_eid,paytobanksortcode_eid,paytomobileno_eid,paytoemailaddress_eid,paytoaddress_eid,paytocityaddress_eid,paytopostalcodeaddress_eid,paytocountryaddress_eid,taxtype_ename,gstorvalno_eid,updateto_class,clicktoupdatebtnid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);

  var paytoid = $("#"+paytoname_eid+"").data("epaytoid");
  form_data.append("paytoid", paytoid);
  var paytoname = $("#"+paytoname_eid+"").val();
  form_data.append("paytoname", paytoname);
  var paytobankname = $("#"+paytobankname_eid+"").val();
  form_data.append("paytobankname", paytobankname);
  var paytobankaccountno = $("#"+paytobankaccountno_eid+"").val();
  form_data.append("paytobankaccountno", paytobankaccountno);
  var paytobankifscocde = $("#"+paytobankifscocde_eid+"").val();
  form_data.append("paytobankifscocde", paytobankifscocde);

  var paytobankiban = $("#"+paytobankiban_eid+"").val();
  form_data.append("paytobankiban", paytobankiban);
  var paytobanswiftcocde = $("#"+paytobanswiftcocde_eid+"").val();
  form_data.append("paytobanswiftcocde", paytobanswiftcocde);
  var paytobanksortcode = $("#"+paytobanksortcode_eid+"").val();
  form_data.append("paytobanksortcode", paytobanksortcode);
  var paytomobileno = $("#"+paytomobileno_eid+"").val();
  form_data.append("paytomobileno", paytomobileno);
  var paytoemailaddress = $("#"+paytoemailaddress_eid+"").val();
  form_data.append("paytoemailaddress", paytoemailaddress);
  var paytoaddress = $("#"+paytoaddress_eid+"").val();
  form_data.append("paytoaddress", paytoaddress);
  var paytocityaddress = $("#"+paytocityaddress_eid+"").val();
  form_data.append("paytocityaddress", paytocityaddress);
  var paytopostalcodeaddress = $("#"+paytopostalcodeaddress_eid+"").val();
  form_data.append("paytopostalcodeaddress", paytopostalcodeaddress);
  var paytocountryaddress = $("#"+paytocountryaddress_eid+"").val();
  form_data.append("paytocountryaddress", paytocountryaddress);
  var taxtype = $("input[name='"+taxtype_ename+"']:checked").val();

  form_data.append("taxtype", taxtype);
  var gstorvalno = $("#"+gstorvalno_eid+"").val();
  form_data.append("gstorvalno", gstorvalno);

  let checkedValues = [];
  $('.'+updateto_class+':checked').each(function() {
      checkedValues.push($(this).val());
  });
  form_data.append("paytofor", checkedValues);
  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=updatepayto_fulldetails",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $(".waitloaderfooter").hide();
        if(res.length>0){
          for(var i=0; i<res.length; i++){
            if(res[i].status=="Success"){
              alertmessage('success',res[i].message);
            }else{
              alertmessage('error',res[i].message);
              return false;
            }
          }
          $("#loadpaytomaster").trigger("click");
        }
        
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}

function removedfrompaytodetailsfrommasterlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoidval){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("paytoid", paytoidval);
  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=removedpaytodetailsfrommasterlist",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $(".waitloaderfooter").hide();
        if(res.length>0){
          for(var i=0; i<res.length; i++){
            if(res[i].status=="Success"){
              alertmessage('success',res[i].message);
            }else{
              alertmessage('error',res[i].message);
              return false;
            }
          }
          $("#loadpaytomaster").trigger("click");
        }
        
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });
}


function proccedingforstockentry_htmlgrid(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,tableid,shortbydata){
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
      url: baseurl+"modal/reporting_modal.php?action=proceedingforstockentry",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
         $("#"+tableid+" tbody").empty();
        if(res){
          $("#"+tableid+" tbody").append(res);
        }
      }
    });
}

function advancepayment_againststock(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,tableid,shortbydata,purposecode){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("shortbydata", shortbydata);
  form_data.append("purposecode", purposecode);


  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/reporting_modal.php?action=advancepayment_stock",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
         $("#"+tableid+" tbody").empty();
        if(res){
          $("#"+tableid+" tbody").append(res);
        }
      }
    });
}



function checkinvoicenumberexist(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, invoicenoval) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid).val();
  var companyid = $("#" + companyid_eid).val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#" + subcompanyid_eid).val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#" + projectid_eid).val();
  form_data.append("projectid", projectid);
  form_data.append("invoiceno", invoicenoval);
  // alert(invoicenoval);
  if(invoicenoval){
    $.ajax({
      type: "POST",
      url: baseurl + "modal/masters_modal.php?action=checkinvoicenoexist_withrequest",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function(res) {
        // console.log(res);
        $("#webpagebodydiv").html('');
        $("#modalheader").html('');
        if (res.length > 0) {
          $("#webpagebodyview_modal").modal("show");
          $("#webpagebodyview_modal").css("z-index","99999");
          $("#modalheader").html("Invoice Number " + invoicenoval + " Already Exists");

          var rrhtml = "<table><thead><tr><th>Beneficiary Name</th><th>Amount</th><th>Invoice No</th><th>Invoice Date</th><th>Purpose Name</th></tr></thead><tbody>";
          for (var i = 0; i < res.length; i++) {
            rrhtml += "<tr><td>" + res[i].beneficiaryname + "</td><td>" + res[i].amount + "</td><td>" + res[i].invoiceno + "</td><td>" + res[i].invoicedate + "</td><td>" + res[i].purposename + "</td></tr>";
          }
          rrhtml += "</tbody></table>";  // Append closing tags correctly

          $("#webpagebodydiv").html(rrhtml);  // Use # for ID selector
          $("#webpagebodydiv").before("<h5 style='color: red; margin-bottom:16px;'>Note:- Please insure entered invoice no is correct</h5>");
        }
      }
    });
  }
}

function checkTransactionnumberexist(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, accountidval, transactionnoval) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid).val();
  var companyid = $("#" + companyid_eid).val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#" + subcompanyid_eid).val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#" + projectid_eid).val();
  form_data.append("projectid", projectid);
  form_data.append("accountid", accountidval);
  form_data.append("transactionno", transactionnoval);

  $.ajax({
      type: "POST",
      url: baseurl + "modal/masters_modal.php?action=checktransactionnoexist_withaccountid",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function(res) {
        // console.log(res);
        if (res.length > 0) {
          for (var i = 0; i < res.length; i++) {
            SweetAlertmessageFadeinwithoutrefresh('error', 'Your entered transaction no already exist with another reference');
            return false;
          }
        }
      }
    });
}

function excelsheetfunctioninfo(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,functionstype,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);

  form_data.append("functionstype", functionstype);
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/masters_modal.php?action=excelsheetfunctioninfo",
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

function tdsdeducted_durationlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,shortby,shortbyvalue,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var subprojectid = $("#"+subprojectid_eid+"").val();
  form_data.append("subprojectid", subprojectid);
  // alert(subprojectid_eid+'=='+subprojectid);

  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/tds_modal.php?action=tdsdeducted_durationlist",
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
          $("#gettdsdatabtn").show();
          $("#"+dropdownid+"").append("<option value='' selected disabled>Year Month</option>");
          for (var i = 0; i < res.length; i++) {
            $("#"+dropdownid+"").append("<option value='"+res[i].valuedate+"'>"+res[i].displaydate+"</option>");
            if (res.length==1) 
            {
              $("#"+dropdownid+"").val(res[i].valuedate).trigger("change");
            }
          }
        }else{
          $("#"+dropdownid+"").append("<option value='' selected disabled>Data not available</option>");
        }
      }
    });
}

function tdsdeducted_financialyearlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var subprojectid = $("#"+subprojectid_eid+"").val();
  form_data.append("subprojectid", subprojectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/tds_modal.php?action=tdsdeducted_financialyearlist",
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
          $("#gettdsdatabtn").show();
          $("#"+dropdownid+"").append("<option value='' selected disabled>Financial Year</option>");
          for (var i = 0; i < res.length; i++) {
            $("#"+dropdownid+"").append("<option value='"+res[i].firstdate+"|"+res[i].lastdate+"'>"+res[i].financialyear+"</option>");
            if (res.length==1) 
            {
              $("#"+dropdownid+"").val(res[i].firstdate+"|"+res[i].lastdate).trigger("change");
            }
          }
        }else{
          $("#"+dropdownid+"").append("<option value='' selected disabled>Data not available</option>");
        }
      }
    });
}

function tdsdeducted_quaters_withfinyear(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,financialyear_eid,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var subprojectid = $("#"+subprojectid_eid+"").val();
  form_data.append("subprojectid", subprojectid);
  var financialyear = $("#"+financialyear_eid+"").val();
  form_data.append("financialyear", financialyear);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/tds_modal.php?action=tdsdeducted_quaters_withfinyear",
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
          $("#"+dropdownid+"").append("<option value='' selected disabled>Financial Quarter</option>");
          for (var i = 0; i < res.length; i++) {
            $("#"+dropdownid+"").append("<option value='"+res[i].firstdate+"|"+res[i].lastdate+"'>"+res[i].quartername+"</option>");
            if (res.length==1) 
            {
              $("#"+dropdownid+"").val(res[i].firstdate+"|"+res[i].lastdate).trigger("change");
            }
          }
        }else{
          $("#"+dropdownid+"").append("<option value='' selected disabled>Data not available</option>");
        }
      }
    });
}

function tdsdeducted_months_withfinyear(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,financialyear_eid,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var subprojectid = $("#"+subprojectid_eid+"").val();
  form_data.append("subprojectid", subprojectid);
  var financialyear = $("#"+financialyear_eid+"").val();
  form_data.append("financialyear", financialyear);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/tds_modal.php?action=tdsdeducted_months_withfinyear",
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
          $("#"+dropdownid+"").append("<option value='' selected disabled>Month Name</option>");
          for (var i = 0; i < res.length; i++) {
            $("#"+dropdownid+"").append("<option value='"+res[i].firstdate+"|"+res[i].lastdate+"'>"+res[i].displaymonth+"</option>");
            if (res.length==1) 
            {
              $("#"+dropdownid+"").val(res[i].firstdate+"|"+res[i].lastdate).trigger("change");
            }
          }
        }else{
          $("#"+dropdownid+"").append("<option value='' selected disabled>Data not available</option>");
        }
      }
    });
}

function tdsdeducted_type(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,yearmonth_eid,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var subprojectid = $("#"+subprojectid_eid+"").val();
  form_data.append("subprojectid", subprojectid);
  if(yearmonth_eid){
    var yearmonth = $("#"+yearmonth_eid+"").val();
    form_data.append("yearmonth", yearmonth);
  }else{
    form_data.append("yearmonth", '');
  }
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/tds_modal.php?action=tdsdeducted_type",
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
          $("#"+dropdownid+"").append("<option value='' selected disabled>TDS Type</option>");
          for (var i = 0; i < res.length; i++) {
            $("#"+dropdownid+"").append("<option value='"+res[i].tdsid+"'>"+res[i].tdstype+"</option>");
            if (res.length==1) 
            {
            $("#"+dropdownid+"").val(res[i].tdsid).trigger("change");;
            }
          }
          if(res.length>1){
            $("#"+dropdownid+"").append("<option value='all'>All</option>");
          }
        }else{
          $("#"+dropdownid+"").append("<option value='' selected disabled>Data not available</option>");
        }
      }
    });
}

function tdstypefrommaster_callback(routurl_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=tdstypefrommaster_callback",
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

function tdsdeducted_percent(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,yearmonth_eid,tdstype_eid,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var subprojectid = $("#"+subprojectid_eid+"").val();
  form_data.append("subprojectid", subprojectid);

  var yearmonth = $("#"+yearmonth_eid+"").val();
  form_data.append("yearmonth", yearmonth);
  var tdstype = $("#"+tdstype_eid+"").val();
  form_data.append("tdstype", tdstype);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/tds_modal.php?action=tdsdeducted_percent",
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
          $("#"+dropdownid+"").append("<option value='' selected disabled>TDS (%)</option>");
          for (var i = 0; i < res.length; i++) {
            $("#"+dropdownid+"").append("<option value='"+res[i].tdspercent+"'>"+res[i].tdspercent+"</option>");
            if (res.length==1) 
            {
            $("#"+dropdownid+"").val(res[i].tdspercent).trigger("change");;
            }
          }
        }else{
          $("#"+dropdownid+"").append("<option value='' selected disabled>Data not available</option>");
        }
      }
    });
}

function gettdsdeducted_data(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subproject_eid,yearmonth_eid,tdstype_eid,tdspercent_eid,loginname_eid,clicktobtnid,viewdatadivid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var subprojectid = $("#"+subproject_eid+"").val();
  form_data.append("subprojectid", subprojectid);
  var yearmonth = $("#"+yearmonth_eid+"").val();
  form_data.append("yearmonth", yearmonth);
  var tdstype = $("#"+tdstype_eid+"").val();
  form_data.append("tdstype", tdstype);
  var tdspercent = $("#"+tdspercent_eid+"").val();
  form_data.append("tdspercent", tdspercent);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/tds_modal.php?action=tdsdeducted_data",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
        if(res.length>0){
          $("#"+viewdatadivid+"").html();
          $("#"+viewdatadivid+"").html(res);
          $("#tdsotherinfodiv").show();
        }
      }
    });
}

function savetodbtdsdeducted_request(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,yearmonth_eid,tdstype_eid,tdspercent_eid,purpose_eid,modeoftransfer_eid,paymentduedate_eid,loginname_eid,clicktobtnid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var subprojectid = $("#"+subprojectid_eid+"").val();
  form_data.append("subprojectid", subprojectid);

  var loginname = $("#"+loginname_eid+"").val();
  form_data.append("loginname", loginname);

  var yearmonth = $("#"+yearmonth_eid+"").val();
  if(yearmonth=="" || yearmonth==null || yearmonth=="undefined"){
    alertmessage('error','TDS deducted month is missing');
    return false;
  }
  form_data.append("yearmonth", yearmonth);
  var tdstype = $("#"+tdstype_eid+"").val();
  if(tdstype=="" || tdstype==null || tdstype=="undefined"){
    alertmessage('error','TDS category missing');
    return false;
  }
  form_data.append("tdstype", tdstype);
  var tdspercent = $("#"+tdspercent_eid+"").val();
  if(tdspercent=="" || tdspercent==null || tdspercent=="undefined"){
    alertmessage('error','TDS duducted percent is missing');
    return false;
  }
  form_data.append("tdspercent", tdspercent);

  var purpose = $("#"+purpose_eid+"").val();
  if(purpose=="" || purpose==null || purpose=="undefined"){
    alertmessage('error','Choose the purpose of request');
    return false;
  }
  form_data.append("purpose", purpose);
  
  var modeoftransfer = $("#"+modeoftransfer_eid+"").val();
  alert(modeoftransfer);
  form_data.append("modeoftransfer", modeoftransfer);

  var paymentduedate = $("#"+paymentduedate_eid+"").val();
  form_data.append("paymentduedate", paymentduedate);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/tds_modal.php?action=tdsdeducted_createrequest",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        if(res.length>0){
          for(var i=0; i<res.length; i++){
            if(res[i].status=="Success"){
              alertmessage('success',res[i].message);
              // $("#gettdsdatabtn").trigger("click");
              window.location.reload("true");
            }else{
              alertmessage('error',res[i].message);
              return false              
            }
          } 
        }
      }
    });
}


function check_uploadexpensesheet_format(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,viewdatadivid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=check_uploadexpensesheet_format",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
        if(res.length>0){
          $("#"+viewdatadivid+"").html("Formated Expense Sheet Required");
         for (var i = 0; i < res.length; i++) {
           
         }
        }else{
          $("#"+viewdatadivid+"").html("Expense sheet format not available");
        }
      }
    });
}

function getpurposecategorylistform_mainmaster(routurl_eid,purposetype,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  form_data.append("purposetype", purposetype);
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=getpurposecategorylistform_mainmaster",
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

function supplierauthorizedlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
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
        url: baseurl+"modal/masters_modal.php?action=supplierauthorizedlist",
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

function partialpaymentauthorized(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
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
        url: baseurl+"modal/masters_modal.php?action=partialpaymentauthorized",
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

function loanauthorized(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
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
        url: baseurl+"modal/masters_modal.php?action=loanauthorized",
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

function cashpaymentauthorized(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
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
        url: baseurl+"modal/masters_modal.php?action=cashpaymentauthorized",
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

function checkimprestauthorized(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
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
        url: baseurl+"modal/masters_modal.php?action=checkimprestauthorized",
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

function projectofsubcompany_htmltablewithcheckbox(routurl_eid,companyid_eid,subcompanyid_eid,projectappenddivid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", $('#hidden_projectid').val());
  
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=projectwithsubcompajy_htmltableview",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          // console.log(res);
          $("#"+projectappenddivid+"").empty();
          $("#"+projectappenddivid+"").html(res);
                    
        }
    });
}

function TransactionsFinancialYear(dropdownid,alreadyexistval){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val(); 
  var companyid = $("#hidden_companyid").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#hidden_subcompanyid").val();
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", $('#hidden_projectid').val());
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=TransactionsFinancialYear",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
           //console.log(res);
          $("#"+dropdownid+"").empty();
          if(res.length>0){
            if (res.length>=1) 
            {
                $("#"+dropdownid+"").append("<option value='' selected>Select Financial Year</option>");
            }
            for (var i = 0; i < res.length; i++) {
              $("#"+dropdownid+"").append("<option   value='"+res[i].financialyear+"'>"+res[i].financialyear+"</option>");
              if(res.length==1){
                $("#"+dropdownid+"").val(res[i].financialyear).trigger("change");
              }
            }
            if(alreadyexistval){
              $("#"+dropdownid+"").val(alreadyexistval).trigger("change");
            }
          }else{
            $("#"+dropdownid+"").append("<option value='' selected>Not available financial year</option>");
          }
        }
    });
}

function FillDateInputChangeonFinancialYear(financialyear_eid, datefrom_eid, dateto_eid, finstartmonthno = 4) {
    let financialyears = $("#" + financialyear_eid).val();

    let years = financialyears.split("-");
    if (years.length === 2) {
        let fromYear = parseInt(years[0].trim());
        let toYear = parseInt(years[1].trim());

        // Format month to two digits (e.g., "04")
        let startMonthStr = finstartmonthno.toString().padStart(2, '0');

        // Determine the end month and day (last day of the previous month of the next financial year)
        let endMonthNo = finstartmonthno === 1 ? 12 : finstartmonthno - 1;
        let endMonthStr = endMonthNo.toString().padStart(2, '0');
        let endYear = (finstartmonthno === 1) ? toYear - 1 : toYear;

        // Get the last day of the end month
        let lastDay = new Date(endYear, endMonthNo, 0).getDate();

        let Fdatefrom = `${fromYear}-${startMonthStr}-01`;
        let Fdateto = `${endYear}-${endMonthStr}-${lastDay.toString().padStart(2, '0')}`;

        $("#" + datefrom_eid).val(Fdatefrom);
        $("#" + dateto_eid).val(Fdateto);
    } else {
        console.error("Invalid financial year format");
    }
}




function currency_master(baseurl_eid,dropdownid,alreadyexistval){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val(); 
  form_data.append("shortby", "all");
  form_data.append("shortbyvalue", "");  
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=currencydetailsall",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
           //console.log(res);
          $("#"+dropdownid+"").empty();
          if(res.length>0){
            if (res.length>=1) 
            {
                $("#"+dropdownid+"").append("<option value='' selected>Select</option>");
            }
            for (var i = 0; i < res.length; i++) {
              $("#"+dropdownid+"").append("<option   value='"+res[i].currencyid+"'>"+res[i].currency_val+"</option>");
              if(res.length==1){
                $("#"+dropdownid+"").val(res[i].currencyid).trigger("change");
              }
            }
            if(alreadyexistval){
              $("#"+dropdownid+"").val(alreadyexistval).trigger("change");
            }
          }else{
            $("#"+dropdownid+"").append("<option value='' selected>Not available Currency</option>");
          }
        }
    });
}

function requestapprovalpersonlist(callback){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val(); 
  var companyid = $("#hidden_companyid").val(); 
  var subcompanyid = $("#hidden_subcompanyid").val(); 
  var projectid = $("#hidden_projectid").val(); 
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  $.ajax
    ({
        type: "POST",
        url: baseurl+"modal/masters_modal.php?action=requestapprovalpersonlist",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          //console.log(res);
          callback(res);
        }
    });
}

function confirmInterBankTransferedDetails(btnid) {
    var form_data = new FormData();
    var routurl = $("#hidden_rooturl").val();
    var companyid = $("body").data("companyid");
    form_data.append("companyid", companyid);
    var subcompanyid = $("body").data("subcompanyid");
    form_data.append("subcompanyid", subcompanyid);
    var projectid = $("body").data("projectid");
    form_data.append("projectid", projectid);

    var oldpaytoid = $("#"+btnid).data("oldpaytoid");
    var referenceno = $("#"+btnid).data("referenceno");
    var interbankpaytoid = $("#interbankpaytoname").val();
    var paytonamelist = $("#paytonamelist").val();

    var newpurpose = $("#newpurpose").val();
    var paytoid="";
    var isinterbank="";

    if(oldpaytoid==interbankpaytoid){
      $("#confirminternaltransfer_modal").modal("hide");
      $("#updutr_formultipleentry_modal").modal("show");
      return false;
    }else if(interbankpaytoid=="notavailable"){
      isinterbank="no";
      paytoid = paytonamelist;
      if(paytonamelist=="" || paytonamelist==null || paytonamelist=="undefined"){
        alertmessage("error", "New payto name missing");
        return false;
      }else if(newpurpose=="" || newpurpose==null || newpurpose=="undefined"){
        alertmessage("error", "New purpose missing");
        return false;
      }
    }else{
      isinterbank="yes";
      paytoid = interbankpaytoid;
    }

    form_data.append("isinterbank", isinterbank);
    form_data.append("paytoid", paytoid);
    form_data.append("purposecode", newpurpose);
    form_data.append("referenceno", referenceno);
     $.ajax
      ({
        type: "POST",
        url: routurl+"modal/transfer_modal.php?action=confirmInterBankTransferedAndUpdate",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          // console.log(res);
          if(res.length>0){
            for(var i=0; i<res.length; i++){
              if(res[i].status="Success"){
                alertmessage('success',res[i].message);
                window.location.reload();
              }else{
                alertmessage('error',res[i].message);
                return false;
              }
            }
          }
        }
      }); 
}

// Function definition
function updateUTRDetails() {
    var utrno = $("#txtutrno").val();
    var utrdate = $("#txtutrdate").val();
    var txtutramount = $("#txtutramount").val();
    var refcount = $("#utrformultipleentry_tbl tbody tr").length;
    var payfromaccid = $("#payfrombank_utr").val();
    var transactiontype = $("input[name='transactiontyperadio']:checked").val();
    var paytobanknametemp = $("#paytobanknametd").text();
    var paytobankaccountnotemp = $("#paytobankaccountnotd").text();
    var bankifsccodetemp = $("#bankifsccodetd").text();
    let issplittransaction = $("input#issplitamount").is(":checked") ? "yes" : "";
    
    var ttttype="";
    if (transactiontype!="" && transactiontype === "online") {
        ttttype="Transaction";
        if (!paytobanknametemp || paytobanknametemp === "null" || paytobanknametemp === "undefined") {
            alertmessage('error', 'Payto bank name is missing/wrong');
            return false;
        } else if (!paytobankaccountnotemp || paytobankaccountnotemp === "null" || paytobankaccountnotemp === "undefined") {
            alertmessage('error', 'Payto bank account number is missing/wrong');
            return false;
        } else if (!bankifsccodetemp || bankifsccodetemp === "null" || bankifsccodetemp === "undefined") {
            alertmessage('error', 'Payto bank IFSC code is missing/wrong');
            return false;
        }
    }else if (transactiontype!="" && transactiontype === "cheque"){
      ttttype="Cheque";
      if(!utrno || utrno === "null" || utrno === "undefined"){
        alertmessage('error', 'Cheque no is missing');
        $("#payfrombank_utr").focus();
        return false;
      }
    }else{
      alertmessage('error', 'Transaction type missing, Please choose the transaction type');
      return false;
    }

    if (!payfromaccid || payfromaccid === "null" || payfromaccid === "undefined" || refcount === "0") {
        alertmessage('error', 'Pay from bank missing');
        $("#payfrombank_utr").focus();
        return false;
    } else if (!txtutramount || txtutramount === "null" || txtutramount === "undefined") {
        alertmessage('error', ttttype+' amount is missing or wrong');
        $("#txtutramount").focus();
        return false;
    } else if (!utrdate || utrdate === "null" || utrdate === "undefined") {
        alertmessage('error', ttttype+' date is missing or wrong');
        $("#txtutrdate").focus();
        return false;
    } else if (!refcount || refcount === "null" || refcount === "undefined" || refcount === "0") {
        alertmessage('error', 'No reference available for update UTR number and date');
        return false;
    }

    updateutrnoto_multipleBookentry("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","payfrombank_utr","utrformultipleentry_tbl",transactiontype,utrno,utrdate,txtutramount,'transferreceipt','updateutrno_mltentry', issplittransaction,function(res) {
            var utrtblrowlenght = $("#utrformultipleentry_tbl tbody tr").length;
            var checkedboxlenght = $("#utrformultipleentry_tbl tbody tr input[type='checkbox']:checked").length;
            if (res.length > 0) {
                for (var i = 0; i < res.length; i++) {
                    if (res[i].status === "Success") {
                        alertmessage('success', res[i].message);
                        if(utrtblrowlenght==checkedboxlenght){
                          setTimeout(function() {
                              window.location.reload();
                          }, 1000);
                        }else{
                          $("#utrformultipleentry_tbl tbody tr").has("input[type='checkbox']:checked").remove();
                        }
                    } else if (res[i].status === "Error") {
                        alertmessage('error', res[i].message);
                        return false;
                    }
                }
            }
        }
    );
}

function CancelPayments() {
  // removetransactions_tbl
  // checktorejectpayment
  // removedtransaction_btn
  var routurl = $("#hidden_rooturl").val();
  var form_data = new FormData();
  var rejectremark = $("#removetransactions_remarks").val();
  if (!rejectremark || rejectremark === "null" || rejectremark === "undefined") {
      alertmessage('warning', 'Please write the our remark, Why are you cancel the payment');
      $("#removetransactions_remarks").focus();
      return false;
  }else if(!$("#checktorejectpayment").is(":checked")){
      alertmessage('warning', 'Checked the check to confirm, you want to cancel the above mentioned payment');
      return false;
  }
  var rmpaymentdata;
  rmpaymentdata = storeTblValues2('#removetransactions_tbl tbody');
  rmpaymentdata = JSON.stringify(rmpaymentdata);  
  function storeTblValues2(mytable)
  {
      var TableData_A2 = new Array();
      var mytableid = mytable + ' tr';
      $(mytableid).each(function(row, tr){
          TableData_A2[row]={
            "referenceno" :$(tr).find('td.refno_ut').text()
            , "paytoname" :$(tr).find('td.payto_ut').text()
            , "bookamount" :$(tr).find('td.bookamount_ut').text()
            , "dbsno" :$(tr).find('td.dbsno_ut').text()
          }
      });
      // TableData_A2.shift();  // first row will be empty - so remove
      return TableData_A2;
  }
  form_data.append('referencenodata', rmpaymentdata);
  form_data.append('rejectremark', rejectremark);

  var btnval=$("#removedtransaction_btn").html();
  $("#removedtransaction_btn").prop("disabled", true);
  $("#removedtransaction_btn").html('Please wait transaction updating..');
  var utrtblrowlenght = $("#removetransactions_tbl tbody tr").length;
  var checkedboxlenght = $("#removetransactions_tbl tbody tr input[type='checkbox']:checked").length;

  $.ajax
    ({
      type: "POST",
      url: routurl+"modal/transfer_modal.php?action=cancelpaymentsupdate",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // itax_
       if (res.length > 0) {
            for (var i = 0; i < res.length; i++) {
                if (res[i].status === "Success") {
                    alertmessage('success', res[i].message);
                    setTimeout(function() {
                          window.location.reload();
                      }, 1000);
                } else if (res[i].status === "Error") {
                    alertmessage('error', res[i].message);
                    return false;
                }
            }
        }
        $("#"+clicktobtnid+"").prop("disabled", false);
        $("#"+clicktobtnid+"").html(btnval);
      }
    });

    /*updateutrnoto_multipleBookentry("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","payfrombank_utr","utrformultipleentry_tbl",transactiontype,utrno,utrdate,txtutramount,'transferreceipt','updateutrno_mltentry',function(res) {
            var utrtblrowlenght = $("#utrformultipleentry_tbl tbody tr").length;
            var checkedboxlenght = $("#utrformultipleentry_tbl tbody tr input[type='checkbox']:checked").length;
            if (res.length > 0) {
                for (var i = 0; i < res.length; i++) {
                    if (res[i].status === "Success") {
                        alertmessage('success', res[i].message);
                        if(utrtblrowlenght==checkedboxlenght){
                          setTimeout(function() {
                              window.location.reload();
                          }, 1000);
                        }else{
                          $("#utrformultipleentry_tbl tbody tr").has("input[type='checkbox']:checked").remove();
                        }
                    } else if (res[i].status === "Error") {
                        alertmessage('error', res[i].message);
                        return false;
                    }
                }
            }
        }
    );*/
}

function SendEmailToReminder_ForUrgentApproval(urgemailto,urgemailcc,urgemailsubject,urgemailmessage,sendurgemailbtn){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val();
  var emailto = $("#"+urgemailto+"").val();
  var emailcc = $("#"+urgemailcc+"").val();
  var emailsubject = $("#"+urgemailsubject+"").val();
  var emailmessage = $("#"+urgemailmessage+"").val();
  var requestdbno = $("#"+sendurgemailbtn+"").data("reqsdbsno");
  var requestno = $("#"+sendurgemailbtn+"").data("reqssno");
  if(emailto=="" || emailto==null || emailto=="undefined"){
    alertmessage('error', 'Recipient email address is required to send the email.');
    return false;
  }else if(emailsubject=="" || emailsubject==null || emailsubject=="undefined"){
    alertmessage('error', 'Subject of email is required.');
    return false;
  }else if(emailmessage=="" || emailmessage==null || emailmessage=="undefined"){
    alertmessage('error', 'Your remark is required.');
    return false;
  }else if(requestdbno=="" || requestdbno==null || requestdbno=="undefined"){
    alertmessage('error', 'Request selection is missing.');
    return false;
  }

  var loginname = $("#hidden_loginname").val();
  form_data.append("emailto", emailto);
  form_data.append("emailcc", emailcc);
  form_data.append("emailsubject", emailsubject);
  form_data.append("emailmessage", emailmessage);
  form_data.append("requestdbno", requestdbno);
  form_data.append("requestno", requestno);
  form_data.append("loginname", loginname);
  form_data.append("routurl", baseurl);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/sendingemail_modal.php?action=SendEmailToReminder_ForUrgentApproval",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $(".waitloaderfooter").hide();
        if(res.status=="Success"){
          alertmessage('success', res.message);
        }else{
          alertmessage('error', res.message);
        }
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });

}

function SendEmailAfterNewRequest_ForApproval(requestno){
  var form_data = new FormData();
  var routurl = $("#hidden_rooturl").val();
  if(requestno=="" || requestno==null || requestno=="undefined"){
    alertmessage('error', 'Request is missing.');
    return false;
  }
  var loginname = $("#hidden_loginname").val();
  form_data.append("companyid", $("body").data("companyid"));
  form_data.append("subcompanyid", $("body").data("subcompanyid"));
  form_data.append("projectid", $("body").data("projectid"));
  form_data.append("requestno", requestno);
  form_data.append("loginname", loginname);
  form_data.append("routurl", routurl);
  $.ajax
    ({
      type: "POST",
      url: routurl+"modal/sendingemail_modal.php?action=SendEmailAfterNewRequest_ForApproval",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      // dataType:'JSON',
      success: function(res)
      {
        console.log(res);
        $(".waitloaderfooter").hide();
        // if(res.status=="Success"){
        //   alertmessage('success', res.message);
        // }else{
        //   alertmessage('error', res.message);
        // }
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });

}

function sendemail_AfterApprovalRequestForPaymentUpdate(requestno){
  var form_data = new FormData();
  var routurl = $("#hidden_rooturl").val();
  if(requestno=="" || requestno==null || requestno=="undefined"){
    alertmessage('error', 'Request is missing.');
    return false;
  }
  var loginname = $("#hidden_loginname").val();
  form_data.append("companyid", $("body").data("companyid"));
  form_data.append("subcompanyid", $("body").data("subcompanyid"));
  form_data.append("projectid", $("body").data("projectid"));
  form_data.append("requestno", requestno);
  form_data.append("loginname", loginname);
  form_data.append("routurl", routurl);
  $.ajax
    ({
      type: "POST",
      url: routurl+"modal/sendingemail_modal.php?action=sendemail_AfterApprovalRequestForPaymentUpdate",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      // dataType:'JSON',
      success: function(res)
      {
        console.log(res);
        // $(".waitloaderfooter").hide();
        // if(res.status=="Success"){
        //   alertmessage('success', res.message);
        // }else{
        //   alertmessage('error', res.message);
        // }
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
    });

}

$(document).on("click", ".open_editpayto_modal", function(){
  var routurll =$("#hidden_rooturl").val();
  $("#webpagebodydiv").load(routurll+"webpages/paytodetail_edit.php?vwin=2&paytoid="+$(this).data("epaytoid"));
  $("#modalheader").html("Payto details for edit");
  $("#webpagebodyview_modal").modal("show");
});

function moduleslistonly(baseurl_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
   form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=moduleslistonly",
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

function moduleslistonlywithCompanyAuthoriztion(companyid,subcompanyid,projectid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val();
   form_data.append("companyid", companyid);
   form_data.append("subcompanyid", subcompanyid);
   form_data.append("projectid", projectid);
   form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=moduleslistonlywithCompanyAuthoriztion",
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

function Removemodule_FromCompanyAuthoriztion(companyid,subcompanyid,projectid,authsno,callback){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("authsno", authsno);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=Removemodule_FromCompanyAuthoriztion",
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

function modulesgrouplistonly(baseurl_eid,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=modulesgrouplistonly",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        callback(res);
      }
    });
}

function AddGroupName(routurl){
  var baseurl = $("#"+routurl+"").val();
  $("#webpagebodydiv1").load(baseurl+"webpages/master_addgroupname.php", function(){
    $("#webpagebodyview1_modal").modal("show");
    $("#modalheader1").html("ADD NEW GROUP NAME");
  $('.modal-backdrop').not('.modal-stack').css('z-index', 1040 + 10 * $('.modal:visible').length).addClass('modal-stack');
    $('#webpagebodyview1_modal').css('z-index', 1050 + 10 * $('.modal:visible').length);
  });  
}

function addupdate_groupname(routurl_eid,groupname_eid,savetobtnid){
    var form_data = new FormData();
    var baseurl = $("#"+routurl_eid+"").val();

    form_data.append("groupmainsno", $("#"+savetobtnid+"").data("groupmainsno"));
    
    var groupname = $('#'+groupname_eid+'').val();
    form_data.append("groupname", groupname);

    form_data.append("loginname", $("body").data("loginname"));

    $.ajax({
        url: baseurl+"modal/masters_modal.php?action=addupdate_groupname",
        type: 'POST',
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res) {
          if(res.status=="Success"){
            alertmessage('success', res.message);
            location.reload();
          }else{
            alertmessage('error', res.message);
            return false
          }
        },
        error: function(xhr, status, error) {
            alert('An error occurred: ' + error);
        }
    });
}


function authorizedlogin_projectslist(baseurl_eid,companyid_eid,subcompanyid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $('#'+companyid_eid+'').val();
  form_data.append("companyid", companyid);
  var subcompanyid = $('#'+subcompanyid_eid+'').val();
  form_data.append("subcompanyid", subcompanyid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=authorizedlogin_projectslist",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        callback(res);
      }
    });
}

function authorizedmodules_forprojects(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $('#'+companyid_eid+'').val();
  form_data.append("companyid", companyid);
  var subcompanyid = $('#'+subcompanyid_eid+'').val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $('#'+projectid_eid+'').val();
  form_data.append("projectid", projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=authorizedmodules_forprojects",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        callback(res);
      }
    });
}

function authorizedloginmoduleid_ofprojects(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,moduleid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $('#'+companyid_eid+'').val();
  form_data.append("companyid", companyid);
  var subcompanyid = $('#'+subcompanyid_eid+'').val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $('#'+projectid_eid+'').val();
  form_data.append("projectid", projectid);
  var moduleid = $('#'+moduleid_eid+'').val();
  form_data.append("moduleid", moduleid);

  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=authorizedloginmoduleid_ofprojects",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        callback(res);
      }
    });
}

function updatepassword_inmoduleforuser(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,moduleid_eid,datatable_eid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $('#'+companyid_eid+'').val();
  form_data.append("companyid", companyid);
  var subcompanyid = $('#'+subcompanyid_eid+'').val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $('#'+projectid_eid+'').val();
  form_data.append("projectid", projectid);
  var moduleid = $('#'+moduleid_eid+'').val();
  form_data.append("moduleid", moduleid);
  var mdpass;
  mdpass = storeTblValues('#'+datatable_eid+' tbody');
  mdpass = JSON.stringify(mdpass);
  function storeTblValues(mytable)
  {
      var TableData_mdp = new Array();
      var mytableid = mytable + ' tr';
      $(mytableid).each(function(row, tr){
          if($(tr).closest('tr').data('mdsno')){
            TableData_mdp[row]={
              "dbsno" :$(tr).closest('tr').data('mdsno')
              , "loginid" :$(tr).closest('tr').data('loginid')
              , "mdlpass" :$(tr).closest('tr').find('input.modulepassword').val()
            }
          }    
      }); 
      // TableData_mdp.shift();  // first row will be empty - so remove
      return TableData_mdp;
  }
  form_data.append('modulepassword', mdpass);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=updatepassword_inmoduleforuser",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // callback(res);
        // console.log(res);
        if(res.status=="Success" || res.status=="success"){
          $('#'+moduleid_eid+'').prop("selectedIndex", 0);
          alertmessage('success', res.message);
          $('#'+moduleid_eid+'').val(moduleid).trigger("change");
        }else{
          alertmessage('errir', res.message);
        }
      }
    });
}

function checkmodulelogingwithpassword(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,mdpasswrod_eid,moduleid,loginid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $('#'+companyid_eid+'').val();
  form_data.append("companyid", companyid);
  var subcompanyid = $('#'+subcompanyid_eid+'').val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $('#'+projectid_eid+'').val();
  form_data.append("projectid", projectid);
  var modulepassword = $('#'+mdpasswrod_eid+'').val();
  if(modulepassword=="" || modulepassword=="undefined" || modulepassword==null){
    alertmessage('error', 'Module login password is missing or wrong');
    return false;
  }
  form_data.append("modulepassword", modulepassword);
  form_data.append("moduleid", moduleid);
  form_data.append("loginid", loginid);

  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/login_modal.php?action=checkmodulelogingwithpassword",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        if(res.status=="Success"){
          alertmessage('success', res.message);
          window.location.reload();
        }else{
          alertmessage('error', res.message);
        }
      }
    });
}

function getdefaultcategorysubcategorylist(shortby,shortbyvalue,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val();
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=getdefaultcategorysubcategorylist",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        if(res.length>0){
        $("#"+dropdownid+"").empty();
          var gtlist="";
          if(res.length>1){
            gtlist += "<option value='' disabled selected>Select</option>";
          }
          for (var i = 0; i < res.length; i++) {
            gtlist += "<option data-defaultcatid='"+res[i].categorycode+"' value='"+res[i].subpcategorycode+"'>"+res[i].subpcategory+"</option>";
          }
          if(gtlist){
            $("#"+dropdownid+"").append(gtlist);
          }
        }
      }
    });
}


function purposecodeof_clientsidedata(routurl_eid,purposecategorytype,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $('#hidden_companyid').val();
  form_data.append("companyid", companyid);
  var subcompanyid = $('#hidden_subcompanyid').val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $('#hidden_projectid').val();
  form_data.append("projectid", projectid);
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  form_data.append("purposecategorytype", purposecategorytype);

  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/showanduploadpurpose.php?action=purposecodeof_clientsidedata",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        callback(res);
      }
    });
}

function addnewpurpose_clientpurpose(providedcode,subcategoryid,subcategoryname,purposename,purposecategorytype,dbsno,savetobtnid){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val();
  var companyid = $("#hidden_companyid").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#hidden_subcompanyid").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#hidden_projectid").val();
  form_data.append("projectid", projectid);
  
  
  if(providedcode=="" || providedcode==null || providedcode=="undefined"){
    alertmessage("error", "Provide code is required");
    return false;
  }
  form_data.append("providedcode", providedcode);

  if(subcategoryname=="" || subcategoryname==null || subcategoryname=="undefined"){
    alertmessage("error", "Sub-Category of purpose is required");
    return false;
  }
  form_data.append("subcategoryname", subcategoryname);
  form_data.append("subcategoryid", subcategoryid);

  if(purposename=="" || purposename==null || purposename=="undefined"){
    alertmessage("error", "Purpose name is a required");
    return false;
  }
  form_data.append("purposename", purposename);
  form_data.append("purposecategorytype", purposecategorytype);
  form_data.append("dbsno", dbsno);

  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/showanduploadpurpose.php?action=addnew_clientpurpose",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
          // console.log(res);
          $(".waitloaderfooter").hide();
          if(res.status=="Success"){
            alertmessage('success',res.message);
            location.reload();
          }else if(res.status=="alreadyexist"){
            alertmessage('warning',res.message);
          }else if(res.status=="Error"){
            alertmessage('error',res.message);
            // return false;
          }
          $(".reloadpurpose").trigger("click");
      },beforeSend: function(res){
          $(".waitloaderfooter").show();
      }
    });
}

function updateORremovepurpose_fromclientsidedata(routurl_eid,purposecategorytype,actiontype,dbsno){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $('#hidden_companyid').val();
  form_data.append("companyid", companyid);
  var subcompanyid = $('#hidden_subcompanyid').val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $('#hidden_projectid').val();
  form_data.append("projectid", projectid);
  form_data.append("loginname",$("#hidden_loginname").val());
  form_data.append("purposecategorytype", purposecategorytype);
  form_data.append("actiontype", actiontype);
  form_data.append("dbsno", dbsno);

  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/showanduploadpurpose.php?action=updateORremovepurpose_fromclientsidedata",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        if(res.status=="Success" || res.status=="success"){
          alertmessage('success', res.message);
          location.reload();
        }else{
          alertmessage('error', res.message);
          return false;
        }
      }
    });
}


function savemappingofclientpurpoe(clientpurposetblid,ativesubcatedropid,purposecategorytypename,clicktosavebtnid){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val();
  var companyid = $('#hidden_companyid').val();
  form_data.append("companyid", companyid);
  var subcompanyid = $('#hidden_subcompanyid').val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $('#hidden_projectid').val();
  form_data.append("projectid", projectid);

  var purposecategorytype=$("input[name='"+purposecategorytypename+"']:checked").val();
  var realpurposesubcategoryid = $("#"+ativesubcatedropid+"").val();

  if(purposecategorytype=="" || purposecategorytype==null || purposecategorytype=="null" || purposecategorytype=="undefined"){
    alertmessage("warning", "choose the purpose category type");
    return false;
  }else if(realpurposesubcategoryid=="" || realpurposesubcategoryid==null || realpurposesubcategoryid=="null" || realpurposesubcategoryid=="undefined"){
    alertmessage("warning", "choose the purpose category type");
    return false;
  }
  form_data.append('purposecategorytype', purposecategorytype);
  form_data.append('realpurposesubcategoryid', realpurposesubcategoryid);

  var cliprp;
  cliprp = storeTblValuescli('#'+clientpurposetblid+'');
  cliprp = JSON.stringify(cliprp);
  function storeTblValuescli(mytable)
  {
      var TableData_cli = new Array();
      var mytableid = mytable + ' tr';
      var i=0
      $(mytableid).each(function(row, tr){
        if($(tr).closest('tr').find('td').find('input.puposecheckformapping').is(":checked")){
          TableData_cli[i]={
            "cl_providedcode" :$(tr).closest('tr').find('td.clt_providedcode').text()
            , "cl_purposename" :$(tr).closest('tr').find('td.clt_purposename').text()
            , "cl_subcategoryname" :$(tr).closest('tr').find('td.clt_subcategoryname').text()
            , "cl_subcategoryid" :$(tr).closest('tr').find('td.clt_subcategoryname').data('subcategoryid')
          }
          i++;
        }    
      }); 
      TableData_cli.shift();  // first row will be empty - so remove
      return TableData_cli;
  }
  form_data.append('clientpurposedata', cliprp);

  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/showanduploadpurpose.php?action=savemappingofclientpurpoe",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        callback(res);
      }
    });
}