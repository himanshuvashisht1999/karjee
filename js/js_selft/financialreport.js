function getpurposecatsubcat(baseurl_eid,companyid,subcompanyid,projectid,purid,callback){
  var baseurl = $("#"+baseurl_eid+"").val();
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("purposeid", purid);
  $.ajax({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=purposedataparts",
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
function getcreditpurposecatsubcat(baseurl_eid,companyid,subcompanyid,projectid,purid,callback){
  var baseurl = $("#"+baseurl_eid+"").val();
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("purposeid", purid);
  $.ajax({
      type: "POST",
      url: baseurl+"modal/masters_modal.php?action=creditpurposedataparts",
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
function getfinancialyearlist_frdata(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,dropdownid){
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
      url: baseurl+"modal/financialreport_modal.php?action=financialyearlist_frdata",
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
            $("#"+dropdownid+"").append("<option value='' selected disabled>Choose financial year</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i].financialyear+"'>"+res[i].financialyear+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].financialyear).trigger("change");              
            }
          }
        }else{
          $("#"+dropdownid+"").append("<option value=''>financial year not available</option>");
        }
      }
    });
}
function getfinancialreporttypelist(baseurl_eid,dropdownid){
	var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/financialreport_modal.php?action=financialreporttypelist",
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
            $("#"+dropdownid+"").append("<option value='' selected disabled>Choose report type</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i].reporttype+"'>"+res[i].reporttype+"</option>");  
          }
        }
      }
    });
}
function getfinancialreportperiodlist(baseurl_eid,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/financialreport_modal.php?action=financialreportperiodlist",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        $("#"+dropdownid+"").empty();
        if(res.length>0){
            $("#"+dropdownid+"").append("<option value='' selected disabled>Choose time period</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i].reportperiod+"'>"+res[i].reportperiod+"</option>");  
          }
        }
      }
    });
}
function getfinancialreportdetails_htmlview(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,financialyear_eid,reporttype_eid,timeperiod_eid,reportviewid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var financialyear = $("#"+financialyear_eid+"").val();
  form_data.append("financialyear", financialyear);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/financialreport_modal.php?action=financialreport_htmlview",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      // dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $("#"+reportviewid+"").html('');
        $("#"+reportviewid+"").html(res);          
      }
    });
}
function getnot4summary_htmlview(baseurl,companyid,subcompanyid,projectid,accountid,fromdate,todate,reporttype,reporthtmlviewid,edit,callback) {    
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("accountid", accountid);
  form_data.append("newfromdata", fromdate);
  form_data.append("newtomdata", todate);
  form_data.append("reporttype", reporttype);
  form_data.append("edit", edit);
  $.ajax({                
      type: "POST",   
      url: baseurl+"modal/allnotes_modal.php?action=notes4summary_htmlview",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
          callback(res);
      }
  });
}
function getnot4summary_htmlviewcredit(baseurl,companyid,subcompanyid,projectid,accountid,fromdate,todate,reporttype,reporthtmlviewid,edit,callback) {    
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("accountid", accountid);
  form_data.append("newfromdata", fromdate);
  form_data.append("newtomdata", todate);
  form_data.append("reporttype", reporttype);
  form_data.append("edit", edit);
  $.ajax({                
      type: "POST",   
      url: baseurl+"modal/allnotes_modal.php?action=notes4summary_htmlviewcredit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
          callback(res);
      }
  });
}
function getnot4summary_htmlview_note4(baseurl,companyid,subcompanyid,projectid,accountid,fromdate,todate,reporttype,reporthtmlviewid,edit,callback) {    
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("accountid", accountid);
  form_data.append("newfromdata", fromdate);
  form_data.append("newtomdata", todate);
  form_data.append("reporttype", reporttype);
  form_data.append("edit", edit);
  $.ajax({                
      type: "POST",   
      url: baseurl+"modal/allnotes_modal.php?action=notes4summary_htmlviewnote4",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
          // console.log(res);
        callback(res,reporttype);
      }
  });
}
function getnot4creditsummary_htmlview_note4(baseurl,companyid,subcompanyid,projectid,accountid,fromdate,todate,reporttype,reporthtmlviewid,edit,callback) {    
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("accountid", accountid);
  form_data.append("newfromdata", fromdate);
  form_data.append("newtomdata", todate);
  form_data.append("reporttype", reporttype);
  form_data.append("edit", edit);
  $.ajax({                
      type: "POST",   
      url: baseurl+"modal/allnotes_modal.php?action=notes4creditsummary_htmlviewnote4",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
          // console.log(res);
        callback(res,reporttype);
      }
  });
}
function getnot4summarydetails_htmlview(baseurl,companyid,subcompanyid,projectid,accountid,fromdate,todate,reporttype,purposecode,purpose,appenddatadivid) {    
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("accountid", accountid);
  form_data.append("newfromdata", fromdate);
  form_data.append("newtomdata", todate);
  form_data.append("reporttype", reporttype);
  form_data.append("purposecode", purposecode);
  form_data.append("purpose", purpose);
  $.ajax({                
      type: "POST",   
       url: baseurl+"modal/allnotes_modal.php?action=notes4summarydetails_htmlview",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
          // console.log(res);
          $("#"+appenddatadivid+"").empty();
          $("#"+appenddatadivid+"").html(res);
          $("#modelviewnote4detailspurpose").modal("show");
      }
  });
}
function getnot4summarydetails_accountwise(baseurl,companyid,subcompanyid,projectid,projectname,accountid,fromdate,todate,reporttype,purposecode,providedcode,purpose,cat,subcat,callback) {    
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("projectname", projectname);
  form_data.append("accountid", accountid);
  form_data.append("newfromdata", fromdate);
  form_data.append("newtomdata", todate);
  form_data.append("reporttype", reporttype);
  form_data.append("purposecode", purposecode);
  form_data.append("providedcode", providedcode);
  form_data.append("purpose", purpose);
  form_data.append("cat", cat);
  form_data.append("subcat", subcat);
  $.ajax({                
      type: "POST",   
      url: baseurl+"modal/allnotes_modal.php?action=notes4summarydetails_accountwise",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
          callback(res);
      }
  });
}
function getnot4creditsummarydetails_accountwise(baseurl,companyid,subcompanyid,projectid,projectname,accountid,fromdate,todate,reporttype,purposecode,providedcode,purpose,cat,subcat,callback) {    
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("projectname", projectname);
  form_data.append("accountid", accountid);
  form_data.append("newfromdata", fromdate);
  form_data.append("newtomdata", todate);
  form_data.append("reporttype", reporttype);
  form_data.append("purposecode", purposecode);
  form_data.append("providedcode", providedcode);
  form_data.append("purpose", purpose);
  form_data.append("cat", cat);
  form_data.append("subcat", subcat);
  $.ajax({                
      type: "POST",   
      url: baseurl+"modal/allnotes_modal.php?action=notes4creditsummarydetails_accountwise",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
          callback(res);
      }
  });
}
function getnot4summarydetails_seprataccount(baseurl,companyid,subcompanyid,projectid,projectname,accountid,fromdate,todate,reporttype,purposecode,providedcode='',category,subcategory,purpose,callback) {    
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("projectname", projectname);
  form_data.append("accountid", accountid);
  form_data.append("newfromdata", fromdate);
  form_data.append("newtomdata", todate);
  form_data.append("reporttype", reporttype);
  form_data.append("purposecode", purposecode);
  form_data.append("providedcode", providedcode);
  //form_data.append("allaccounts", allaccounts);
  form_data.append("category", category);
  form_data.append("subcategory", subcategory);
  form_data.append("purpose", purpose);
  //form_data.append("accountno", accountno);
  $.ajax({                
      type: "POST",   
      url: baseurl+"modal/allnotes_modal.php?action=notes4summarydetails_seprataccountwise",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
         callback(res);
      }
  });
}

function getnot4summarydetails_seprataccountcredit(baseurl,companyid,subcompanyid,projectid,projectname,accountid,fromdate,todate,reporttype,purposecode,providedcode='',category,subcategory,purpose,callback) {    
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("projectname", projectname);
  form_data.append("accountid", accountid);
  form_data.append("newfromdata", fromdate);
  form_data.append("newtomdata", todate);
  form_data.append("reporttype", reporttype);
  form_data.append("purposecode", purposecode);
  form_data.append("providedcode", providedcode);
  //form_data.append("allaccounts", allaccounts);
  form_data.append("category", category);
  form_data.append("subcategory", subcategory);
  form_data.append("purpose", purpose);
  //form_data.append("accountno", accountno);
  $.ajax({                
      type: "POST",   
      url: baseurl+"modal/allnotes_modal.php?action=notes4summarydetails_seprataccountwisecredit",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
         callback(res);
        $(".waitloaderfooter").hide();     
      },beforeSend: function(res){
        $(".waitloaderfooter").show();
      }
  });
}

// reconciliation
function getunreconcilieddatafrombook(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountidval,datefrom,dateto,tableid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("accountid", accountidval);
  form_data.append("datefrom", datefrom);
  form_data.append("dateto", dateto);

  $("#"+tableid+" tbody").empty();
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=getunreconcilieddatafrombook",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
      var httrow="";
      if(res.length>0){
        let transactiondate ="";
        for (var i = 0; i < res.length; i++) {
          httrow += "<tr data-rfmsno='"+res[i].msno+"' data-rfrferenceno='"+res[i].referenceno+"' data-rfbookdate='"+res[i].bookdate+"' data-rfbookamount='"+res[i].bookamount+"' data-rftransferdate='"+res[i].transactiondate+"' data-rftransferamount='"+res[i].transferedamount+"' data-rftransactionno='"+res[i].transactionno+"' data-rfpaytoid='"+res[i].paytoid+"' data-rfpaytoname='"+res[i].payto+"' data-refbrkdataarray='"+JSON.stringify(res[i].refbreakdataarray).replace(/"/g, "&quot;")+"'><td>"+   (i + 1) +" <input type='checkbox' name='bookdataradio' class='bookdataradio' /> </td><td>"+res[i].dispbookdate+"</td><td>"+res[i].transactiondate+"</td><td>"+res[i].transactionno+"</td><td>"+res[i].payto+"</td><td>"+res[i].transferedamount+"</td><td>"+res[i].providedcode+"</td><td>"+res[i].purposename+"</td><td>"+res[i].descriptionbank+"</td></tr>";
        }

        if(httrow){
          $("#"+tableid+" tbody").append(httrow);
        }
      }else{
        $("#"+tableid+" tbody").append("<tr><td colspan='8' class='text-center'>No data available</td></tr>");
      }
      $(".waitloaderfooter").hide();     
    },beforeSend: function(res){
      $(".waitloaderfooter").show();
    }
  });
}

function reconciliationmarkedtobook_manual(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountid_eid,bankdatatblid,bookdatatblid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  var projectid = $("#"+projectid_eid+"").val();
  var accountid = $("#"+accountid_eid+"").val();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("accountid", accountid);
  form_data.append("loginname", $("body").data("loginname"));
  var bankdata;
  bankdata = storeTblValuesBank('#'+bankdatatblid+'');
  bankdata = JSON.stringify(bankdata);
  function storeTblValuesBank(mytable)
  {
    var TableDataBank = new Array();
    var mytableid = mytable + ' tbody tr';
    var i=0;
    $(mytableid).each(function(row, tr){
      if($(tr).closest('tr').find('td:first').find('input[name="slt_bankentry"]').is(":checked")){
        // alert($(tr).closest('tr').data('undbsno'));
        TableDataBank[i]={
          "banksno" :$(tr).closest('tr').data('undbsno')
        }
        i++;
      }    
    }); 
    // TableDataBank.shift();  // first row will be empty - so remove
    return TableDataBank;
  }
  if (bankdata.length === 0) {
      alertmessage('error', 'Bank selection checkbox is not checked. Please check it.');
      return false;
  }
  // alert(bankdata);
  form_data.append('bankdatadbsno', bankdata);

  var bookdata;
  bookdata = storeTblValuesBook('#'+bookdatatblid+'');
  bookdata = JSON.stringify(bookdata);
  function storeTblValuesBook(mytable)
  {
    var TableDataBook = new Array();
    var mytableid = mytable + ' tbody tr';
    var j=0;
    $(mytableid).each(function(row, tr){
      if($(tr).closest('tr').find('td:first').find('input[name="bookdataradio"]').is(":checked")){
        TableDataBook[j]={
          "booksno" :$(tr).closest('tr').data('rfmsno')
        }
        j++;
      }    
    }); 
    // TableDataBook.shift();  // first row will be empty - so remove
    return TableDataBook;
  }
  if (bookdata.length === 0) {
      alertmessage('error', 'Book selection checkbox is not checked, Please checked it');
      return false;
  }
  form_data.append('bookdatadbsno', bookdata);

  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=reconciliationmarkedtobook_manual",
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
        
        $("#" + bankdatatblid + " tbody tr").each(function(index, tr) {
            if ($(tr).find('td:first input[name="slt_bankentry"]').is(":checked")) {
                $(tr).remove();
            }
        });

        $("#" + bookdatatblid + " tbody tr").each(function(index, tr) {
            if ($(tr).find('td:first input[name="bookdataradio"]').is(":checked")) {
                $(tr).remove();
            }
        });
        if($("#" + bankdatatblid + " tbody tr").length==0){
          SweetAlertmessageFadeinwithoutrefresh('success','All entries for this bank have been reconciled. Please choose another bank for reconciliation.');
        }else if($("#" + bookdatatblid + " tbody tr").length==0){
          SweetAlertmessageFadein('success','All entries of book have been reconciled');
        }else{
          alertmessage('success', res.message);
        }
        $("#selectedbkamount").html(0);
        $("#selectedexpamount").html(0);
      }else{
        alertmessage('error', res.message);
      }
    },beforeSend: function(res){
      $(".waitloaderfooter").show();
    }
  });

}

function reconciliationmarkedtobook_automatic(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountid_eid,datefrom_eid,dateto_eid,processtype,processbtnid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  var projectid = $("#"+projectid_eid+"").val();
  var datefrom = $("#"+datefrom_eid+"").val();
  var dateto = $("#"+dateto_eid+"").val();
  if(accountid_eid){
    var accountid = $("#"+accountid_eid+"").val();
  }else{
     alertmessage('warning', 'From bank is missing, Please choose the bank');
      return false;
  }
  if(datefrom=="" || datefrom==null || datefrom=="undefined"){
    alertmessage('warning', 'filter date from is missing');
    return false;
  }else if(dateto=="" || dateto==null || dateto=="undefined"){
    alertmessage('warning', 'filter date to is missing');
    return false;
  }
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("accountid", accountid);
  form_data.append("processtype", processtype);
  form_data.append("datefrom", datefrom);
  form_data.append("dateto", dateto);
  form_data.append("loginname", $("body").data("loginname"));
  // $("#"+processbtnid+"").prop("disabled", true);
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/digitization_modal.php?action=reconciliationmarkedtobook_automatic",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
      $(".waitloaderfooter").hide();
      $("#"+processbtnid+"").prop("disabled", false);
      var msgs="";
      if(res.length>0){
        $("#webpagebodydiv").html('');
        for (var i = 0; i < res.length; i++) {
          if(res[i].status=="Success"){
            $("#webpagebodydiv").append("<p class='text-success'>"+res[i].message+"</p>");
            $("#"+accountid_eid+"").val(accountid).trigger("change");
          }else{
            $("#webpagebodydiv").append("<p class='text-danger'>"+res[i].message+"</p>");
          }
        }
        $("#modalheader").html("Reconcilation Process Result Message");
        $("#webpagebodyview_modal").modal("show");
        $(".bankdata_refreshbtn").trigger("click");
      }
    },beforeSend: function(res){
      $(".waitloaderfooter").show();
    }
  });

}

function tellyformattransactions_htmlview(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid, datefrom_eid, dateto_eid,callback){
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
      url: baseurl+"modal/reporting_modal.php?action=tellyformattransactions_htmlview",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
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

function tdshistoryofbeneficiary_callback(datefrom,dateto,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val();
  var companyid = $("#hidden_companyid").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#hidden_subcompanyid").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#hidden_projectid").val();
  form_data.append("projectid", projectid);
  form_data.append("datefrom", datefrom);
  form_data.append("dateto", dateto);
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/reporting_modal.php?action=tdshistoryofbeneficiary",
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

function getUndegitzedexcelFilesFromServer(routurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, callback) {
    const form_data = new FormData();
    var routurl = $("#"+routurl_eid+"").val();
    var companyid = $("#"+companyid_eid+"").val();
    var subcompanyid = $("#"+subcompanyid_eid+"").val();
    var projectid = $("#"+projectid_eid+"").val();
    form_data.append('companyid', companyid);
    form_data.append('subcompanyid', subcompanyid);
    form_data.append('projectid', projectid);

    $.ajax({
        type: "POST",
        url: routurl+"modal/digitization_modal.php?action=undegitizedfilesentry",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'json',
        success: function (res) {
           callback(res);
        }
    });
}

function gettdstransactions_datareport(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,subprojectid_eid,tdstype_eid,tdsduration_eid,viewdatadivid){
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
  var tdstype = $("#"+tdstype_eid+"").val();
  form_data.append("tdstype", tdstype);
  var tdsduration = $("#"+tdsduration_eid+"").val();
  form_data.append("tdsduration", tdsduration);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/tds_modal.php?action=gettdstransactions_datareport",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
        $("#"+viewdatadivid+"").html();
        if(res.length>0){
          $("#"+viewdatadivid+"").html(res);
        }
      }
    });
}

function incometaxdeducted_paytolist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,shortby,shortbyvalue,dropdownid){
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
      url: baseurl+"modal/tds_modal.php?action=incometaxdeducted_paytolist",
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
            $("#"+dropdownid+"").append("<option value='all'>All</option>");
          }
          for (var i = 0; i < res.length; i++) {
            $("#"+dropdownid+"").append("<option value='"+res[i].paytoid+"'>"+res[i].paytoname+"</option>");
            if (res.length==1) 
            {
              $("#"+dropdownid+"").val(res[i].paytoid).trigger("change");
            }
          }
        }else{
          $("#"+dropdownid+"").append("<option value='' selected disabled>Data not available</option>");
        }
      }
    });
}

function incometaxdeducted_months(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,financialyear_eid,dropdownid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  if(financialyear_eid){
    var financialyear = $("#"+financialyear_eid+"").val();
  }else{
    var financialyear = "";
  }
  form_data.append("financialyear", financialyear);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/tds_modal.php?action=incometaxdeducted_months",
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

function getincometaxdeducted_data(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,paytoid_eid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var paytoid = $("#"+paytoid_eid+"").val();
  if(paytoid=="" || paytoid==null || paytoid=="undefined"){
    paytoid="all";
  }
  form_data.append("paytoid", paytoid);
  $.ajax
    ({
      type: "POST",
      url: baseurl+"modal/tds_modal.php?action=getincometaxdeducted_data",
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

function postpaymenthistorywetweendate(datefrom_eid,dateto_eid,callback){
  var form_data = new FormData();
  var routurl = $("#hidden_rooturl").val();
  var companyid = $("#hidden_companyid").val();
  form_data.append("companyid", companyid);  
  var subcompanyid = $("#hidden_subcompanyid").val();
  form_data.append("subcompanyid", subcompanyid);  
  var projectid = $("#hidden_projectid").val();
  form_data.append("projectid", projectid);

  var datefrom = $("#"+datefrom_eid).val();
  form_data.append("datefrom", datefrom);
  var dateto = $("#"+dateto_eid).val();
  form_data.append("dateto", dateto);

    $.ajax
    ({
      type: "POST",
      url: routurl+"modal/reporting_modal.php?action=postpaymenthistorywetweendate",
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
