function ReconstructionUpdateFromBankData(routurl_eid,savetodbbtnid) { 
  var routurl = $("#"+routurl_eid).val();
  if ($(".activerecorder").length > 0) {
    alert("Please stop recorder and then save");
    return false;
  }

  let master_purposecode     = ($("#purposeselect_master").val() || "").trim();
  let master_purpose      = ($("#purposeselect_master option:selected").text() || "").trim();
  let purposemanual     = ($("#purposeselec_manual").val() || "").trim();  
  let master_paidbyid   = ($("#paytoname_recbook").val() || "").trim();
  let master_paidby   = ($("#paytoname_recbook  option:selected").text() || "").trim();
  let paytonamemanual   = ($("#paytoname_recbook_manual").val() || "").trim();
  var audioFile = $("#voicenewrecording_audioPlayback").data("audioFile");
  
  // Validate purpose
  if (!master_purposecode && !purposemanual && !audioFile) {
    alert('What is the purpose of this transaction? It is required.');
    return false;
  }
    // Validate payee name
  if (!master_paidbyid && !paytonamemanual && !audioFile) {
    alert(  'Who Deposited the Amount is missing. It is required.');
    return false;
  } 
  var selectedOption = $("#paytoname_recbook").find("option:selected");          
  var payertype =selectedOption.data("payertype");
  var selectedOption_purpose = $("#purposeselect_master").find("option:selected");
  var tblname = selectedOption_purpose.data("tblname") || "";
  var filepath = $("#importfile")[0].files[0];
  const form_data = new FormData(); 
  // Basic metadata
  form_data.append("companyid", $("#hidden_companyid").val());
  form_data.append("subcompanyid", $("#hidden_subcompanyid").val());
  form_data.append("projectid", $("#hidden_projectid").val());
  form_data.append("purposecode", master_purposecode);
  form_data.append("purpose_master", master_purpose);
  form_data.append("purpose_manual", purposemanual);

  form_data.append("paytoid", master_paidbyid);  
  form_data.append("paidby_master", master_paidby);
  form_data.append("paytonamemanual", paytonamemanual);  form_data.append("remarktxt", $("#remarkrecbook").val().trim());
  form_data.append("loginid", $("#hidden_loginid").val());
  form_data.append("banksno", $("#"+savetodbbtnid).data("banksno")); 

  form_data.append('audio', audioFile); 
  form_data.append('filepath', filepath); 
   
  form_data.append("transactiontype","Withdrawn"); 
  form_data.append("loginid",$("#hidden_loginid").val());


  $.ajax({
    url: routurl + "modal/book_reconstruction_modal.php?action=ReconstructionUpdateFromBankData",
    type: "POST",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
     dataType:'JSON',
    success: function (res) { 
      // console.log(res);
      if(res.length>0){
          alert(res[0].message);
          if(res[0].status=="Success" || res[0].status=="success"){
             getRowDataByIndex();
             $('html, body').animate({ scrollTop: 0 }, 'slow');
          }         
      }
      // Optionally show confirmation or reload data
    }
  });
}
function ReconstructionUpdateFromBankDataByAdmin(rooturleid, paytoid_eid, paytonamemanual_eid, purposecode_eid, purposenamemanual_eid, remarktxt_eid, tableid, btnid) {
  const form_data = new FormData();
  const routurl = $("#" + rooturleid).val();
  // Basic metadata
  form_data.append("companyid", $("#hidden_companyid").val());
  form_data.append("subcompanyid", $("#hidden_subcompanyid").val());
  form_data.append("projectid", $("#hidden_projectid").val());

  // Input fields
  form_data.append("paytoid", $("#" + paytoid_eid).val());
  form_data.append("paytonamemanual", $("#" + paytonamemanual_eid).val());
  form_data.append("purposecode", $("#" + purposecode_eid).val());
  form_data.append("purposenamemanual", $("#" + purposenamemanual_eid).val());
  form_data.append("remarktxt", $("#" + remarktxt_eid).val());
  form_data.append("loginid", $("#hidden_loginid").val());

  // Collect checked bank entries
  const closetobooksno = [];
  $("#" + tableid + " tbody tr").each(function () {
    if ($(this).find("td input[type='checkbox']").is(":checked")) {
      closetobooksno.push($(this).data("banksno"));
    }
  });
  form_data.append("closetobooksno", JSON.stringify(closetobooksno));

  // Optional: Save current button text
  const btntxt = $("#" + btnid).html();
  // $("#" + btnid).prop("disabled", true).html("Processing...");

  // AJAX request
  $.ajax({
    url: routurl + "modal/book_reconstruction_modal.php?action=ReconstructionUpdateFromBankDataByAdmin",
    type: "POST",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
     dataType:'JSON',
    success: function (res) {
      // console.log(res);
      if(res.length>0){
        for (var i = 0; i < res.length; i++) {
          if(res[i].status=="Success" || res[i].status=="success"){
            alertmessage("success", res[i].message);
            window.location.reload();
          }
        }
      }
      // Optionally show confirmation or reload data
    }
  });
}

function CreateBook_FromReconstructedBankData(rooturleid, tableid, btnid) {
  const form_data = new FormData();
  const routurl = $("#" + rooturleid).val();
  // Basic metadata
  form_data.append("companyid", $("#hidden_companyid").val());
  form_data.append("subcompanyid", $("#hidden_subcompanyid").val());
  form_data.append("projectid", $("#hidden_projectid").val());
  form_data.append("loginid", $("#hidden_loginid").val());

  // Collect checked bank entries
  const closetobooksno = [];
  $("#" + tableid + " tbody tr").each(function () {
    if ($(this).find("td input[type='checkbox']").is(":checked")) {
      closetobooksno.push($(this).data("banksno"));
    }
  });
  form_data.append("closetobooksno", JSON.stringify(closetobooksno));
  const btntxt = $("#" + btnid).html();
  $("#" + btnid).prop("disabled", true).html("Processing...");

  // AJAX request
  $.ajax({
    url: routurl + "modal/book_reconstruction_modal.php?action=CreateBook_FromReconstructedBankData",
    type: "POST",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
     dataType:'JSON',
    success: function (res) {
      // console.log(res);
      if(res.length>0){
        for (var i = 0; i < res.length; i++) {
          if(res[i].status=="Success" || res[i].status=="success"){
            alertmessage("success", res[i].message);
            window.location.reload();
          }
        }
      }
      // Optionally show confirmation or reload data
    }
  });
}

function sendtoemailaddress_adminlist_html(routurl,companyid,subcompanyid,projectid,dateFrom,dateTo,accountId,tableid){
  var form_data = new FormData();
  form_data.append("routurl", routurl);
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  // const selectedGroups = getFirstChildRowOfSelectedGroups(tableid);
  // if (selectedGroups.length === 0) {
  //   alert("Please select at least one group to send.");
  //   return;
  // }

  $.ajax
  ({
      type: "POST",
      url: routurl+"modal/book_reconstruction_modal.php?action=sendtoemailaddress_adminlist",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        var sendEmailHtml = "";
        // Clear the content of the webpage body div
        $("#webpagebodydiv").html('');
        if (res.length > 0) {
            // Start building the HTML for the modal content
            sendEmailHtml += `
                <div class="row">
                    <div class="col-md-12">
                        <label>Send Email Person Name</label>
                        <select class="form-control" id="sendEmailLoginId">`;
                        // Populate the select options with data from `res`
                        sendEmailHtml += `<option value="" data-emailaddress="" selected disabled>Choose send to</option>`;
                        
                        for (var i = 0; i < res.length; i++) {
                            sendEmailHtml += `<option value="${res[i].loginid}" data-emailaddress="${res[i].emailid}">${res[i].loginname}</option>`;
                        }
                        sendEmailHtml += `
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label>Email Address</label>
                        <input type="email" class="form-control" id="sendtoemailaddress" />
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-4"></div>
                    <div class="col-md-4 text-center">
                        <button 
                            class="btn btn-success" 
                            id="sendEmailBtn" 
                            onclick="sendemail_forreconstructionbankentry('${routurl}', '${companyid}', '${subcompanyid}', '${projectid}', '${dateFrom}', '${dateTo}', '${accountId}', $('#sendEmailLoginId').val(), $('#sendtoemailaddress').val(),'${tableid}')">
                            Send Email
                        </button>
                    </div>
                    <div class="col-md-4"></div>
                </div>`;
        } else {
            // Alert the user if no authorized person exists for the project
            alertmessage('error', 'No one authorized for login of this project');
            return false;
        }
        // If the HTML was generated, show the modal and set its content
        if (sendEmailHtml) {
            $("#webpagebodyview_modal").modal("show");
            $("#modalheader").html("Sending Email For Reconstruction of Bank Entry");
            $("#webpagebodydiv").html(sendEmailHtml);
        }

      }
    });
}


function sendemail_forreconstructionbankentry(routurl,companyid,subcompanyid,projectid,datefrom,dateto,accountid,sendtoemailloginid,sendtoemailaddress,tableid){
  var form_data = new FormData();
  form_data.append("routurl", routurl);
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);
  form_data.append("sendtoemailloginid", sendtoemailloginid);
  if(sendtoemailaddress=="" || sendtoemailaddress==null || sendtoemailaddress=="null" || sendtoemailaddress=="undefined"){
    alertmessage('warning', 'Email address is required to proceed with sending.');
    return false;
  }
  if(!validateEmailwithval(sendtoemailaddress)){
    alertmessage('warning', 'Invalid Email address');
    return false;    
  }
  form_data.append("sendtoemailaddress", sendtoemailaddress);
  form_data.append("datefrom", datefrom);
  form_data.append("dateto", dateto);
  form_data.append("accountid", accountid);
  const selectedGroups = getSelectedReconstructionRows(tableid);
  if (selectedGroups.length === 0) {
    alert("Please select at least one group to send.");
    return;
  }
  const dbsnoList = selectedGroups.map(item => item.dbsno).join(",");
  form_data.append("dbsnolist", dbsnoList);
  $.ajax
    ({
      type: "POST",
      url: routurl+"modal/book_reconstruction_modal.php?action=sendemail_forreconstructionbankentry",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
     dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $(".waitloaderfooter").modal("hide");
        if(res.status=="Success"){
          alertmessage('success',res.message);
          // $(".modal").modal("hide");
          window.location.reload();
        }else{
          alertmessage('error',res.message);
        }
      },beforeSend: function(res){
        $(".waitloaderfooter").modal("show");
      }
  });
}

function getBookReconstructFinancialYearsJS(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,transactiontype,dropdownid){
  var form_data = new FormData();
  var routurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid); 
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid); 
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid); 
  form_data.append("transactiontype", transactiontype);
  $.ajax
    ({
      type: "POST",
      url: routurl+"modal/book_reconstruction_modal.php?action=getBookReconstructFinancialYears",
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
            $("#"+dropdownid+"").append("<option value='"+res[i]+"'>"+res[i]+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i]).trigger("change");              
            }
          }
          $("#"+dropdownid+"").append("<option value='custom'>Custom</option>");         
        }        
      }
    });
}

function BookReconstructAccountlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,datefrom_eid,dateto_eid,transactiontype,dropdownid){
  var form_data = new FormData();
  var routurl = $("#"+routurl_eid+"").val();
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
  form_data.append("transactiontype", transactiontype);
  $.ajax
    ({
      type: "POST",
      url: routurl+"modal/book_reconstruction_modal.php?action=BookReconstructAccountlist",
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
            $("#"+dropdownid+"").append("<option value='"+res[i].account_id+"'>"+res[i].bankname+"-"+res[i].accountno+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].account_id).trigger("change");              
            }
          }         
        }else{
          $("#"+dropdownid+"").append("<option value='' disabled>Account not available</option>");
        }
        
      }
    });
}

function Accountlist_UnReconstructDataByClient(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,transactiontype,dropdownid){
  var form_data = new FormData();
  var routurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid); 
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid); 
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("transactiontype", transactiontype);
  $.ajax
    ({
      type: "POST",
      url: routurl+"modal/book_reconstruction_modal.php?action=Accountlist_UnReconstructDataByClient",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        // console.log(res);
        $("#"+dropdownid+"").empty();        
        if(res!="" && res.length>0){
          $("#"+dropdownid+"").append("<option value='' selected disabled>Select</option>");
          for(var i=0; i<res.length; i++){
            $("#"+dropdownid+"").append("<option value='"+res[i].accountid+"'>"+res[i].bankname+"-"+res[i].accountno+"</option>");
            if(res.length==1){
              $("#"+dropdownid+"").val(res[i].accountid).trigger("change");              
            }
          }         
        }else{
          $("#"+dropdownid+"").append("<option value='' disabled selected>No bank entries available for reconstruction from any account.</option>");
        }
        
      }
    });
}

function BookReconstructPurposeCodefrommaster(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,pcategoryidval,psubcategoryidval,alreadyexistval,dropdownid){
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
        url: baseurl+"modal/book_reconstruction_modal.php?action=BookReconstructPurposeCodefrommaster",
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

function BookReconstructPayto_dropdownlist(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,alreadyexist,dropdownid){
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
      url: baseurl+"modal/book_reconstruction_modal.php?action=BookReconstructPayto",
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

function showSimilarEntryProvidedText(rooturleid,companyid,subcompanyid,projectid,accountid,transactiontype,targetid,searchText,alreadydbsno,threshold = 0.9) {
  const routurl = $("#" + rooturleid).val();
  const targetTable = $("#" + targetid);
  const trimmedSearchText = searchText.trim().toLowerCase();
  const form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("accountid", accountid);
  form_data.append("transactiontype", transactiontype);
  targetTable.find("tbody").empty();
  $.ajax({
    url: routurl + "modal/book_reconstruction_modal.php?action=UnreconstructwithAccountid",
    type: "POST",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType: "json",
    success: function (res) {
      if (!Array.isArray(res) || res.length === 0) {
        targetTable.find("tbody").append(`<tr><td colspan="4">No data available</td></tr>`);
        return;
      }

      let matchCount = 0;
      let rowsHtml = "";

      res.forEach(entry => {
        const description = (entry.description_bank || "").toString().trim().toLowerCase();

        if (similarity(description, trimmedSearchText) >= threshold) {
          matchCount++;
          if(alreadydbsno==entry.sno){
            rowsHtml += `
            <tr class="similar-entry-row text-success d-none" 
              data-banksno="${entry.sno}" 
              data-unraccountid="${entry.account_id}" 
              data-undbsno="${entry.sno}">
              <td data-label="Select" class="text-center"><input type="checkbox" class="similartryradio" checked disabled /></td>
              <td data-label="Transfered Date" class="bankvaluedate">${formatDate(entry.value_date)}</td>
              <td data-label="Transfered Amount" class="bankamount">${entry.total_amount}</td>
              <td data-label="Bank Description" class="bankdescription">${entry.description_bank.toUpperCase()}</td>
            </tr>`;
          }else{
            rowsHtml += `
            <tr class="similar-entry-row" 
              data-banksno="${entry.sno}" 
              data-unraccountid="${entry.account_id}" 
              data-undbsno="${entry.sno}">
              <td data-label="Select" class="text-center"><input type="checkbox" class="similartryradio" /></td>
              <td data-label="Transfered Date" class="bankvaluedate">${formatDate(entry.value_date)}</td>
              <td data-label="Transfered Amount" class="bankamount">${entry.total_amount}</td>
              <td data-label="Bank Description" class="bankdescription">${entry.description_bank.toUpperCase()}</td>
            </tr>`;
          }
        }
      });

      if (matchCount > 0) {
        targetTable.find("tbody").append(rowsHtml);
      } else {
        targetTable.find("tbody").append(`<tr><td colspan="4">No similar entries found</td></tr>`);
      }
      $("#" + targetid).data("similarrows", matchCount);
      if (matchCount == 1) {
        $("#" + targetid+"div").hide();
      }else{
        $("#" + targetid+"div").show();
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", error);
      targetTable.find("tbody").append(`<tr><td colspan="4">Error loading data</td></tr>`);
    }
  });
}

function SimilarPaytoText_MatchedwithMaster(rooturleid, companyid, subcompanyid, projectid, targettblid, searchText, banksno=null, threshold = 0.75) {
  const routurl = $("#" + rooturleid).val();
  const targetTable = $("#" + targettblid);
  const trimmedSearchText = searchText.trim().toLowerCase();

  if (/^\d+$/.test(trimmedSearchText)) {
    alert("Search text cannot be numeric.");
    return;
  }

  const form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);

  targetTable.find("tbody").empty();

  $.ajax({
    url: routurl + "modal/book_reconstruction_modal.php?action=SimilarPaytoText_MatchedwithMaster",
    type: "POST",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType: "json",

    success: function (res) {
      if (!Array.isArray(res) || res.length === 0) {
        targetTable.find("tbody").append(`<tr><td colspan="4">No data available</td></tr>`);
        return;
      }

      let matchCount = 0;
      let rowsHtml = "";
      let hasExactMatch = false;

      res.forEach(entry => {
        const masterPayto = (entry.paytoname || "").toString().trim().toLowerCase();
        if (masterPayto === "") return;

        const matchScore = getMatchScore(masterPayto, trimmedSearchText);
        const displayPercent = Math.round(matchScore * 100);

        if (!isNaN(matchScore) && matchScore >= threshold) {
          const checked = displayPercent === 100 && !hasExactMatch ? "checked" : "";
          if (displayPercent === 100) hasExactMatch = true;

          rowsHtml += `
            <tr class="similar-entry-row text-success" data-textsource="payto" data-banksno="${banksno}"
                data-masterid="${entry.paytoid}">
              <td data-label="Existing Name" class="existpayto">${searchText}</td>
              <td data-label="Select" class="text-center">
                <input type="radio" name="similarPaytoRadio" class="similartryradio" ${checked} />
              </td>
              <td data-label="Name From Master" class="paytofrommaster">${entry.paytoname}</td>
              <td data-label="Matched %" class="matchedpercent">${displayPercent}%</td>
            </tr>`;
          matchCount++;
        }
      });

      // If no 100% match, allow new entry with searchText
      if (!hasExactMatch) {
        rowsHtml += `
          <tr class="similar-entry-row text-primary" data-textsource="payto" data-banksno="${banksno}" data-masterid="">
            <td data-label="Existing Name" class="existpayto">${searchText}</td>
            <td data-label="Select" class="text-center">
              <input type="radio" name="similarPaytoRadio" class="similartryradio" />
            </td>
            <td data-label="Name From Master" class="paytofrommaster">${searchText}</td>
            <td data-label="Matched %" class="matchedpercent">Add With Origina Text</td>
          </tr>`;
      }

      targetTable.find("tbody").append(rowsHtml);
    },

    error: function (xhr, status, error) {
      console.error("AJAX Error:", error);
      targetTable.find("tbody").append(`<tr><td colspan="4">Error loading data</td></tr>`);
    }
  });
}

function SimilarPurposeText_MatchedwithMaster(rooturleid, companyid, subcompanyid, projectid, targettblid, searchText, banksno = null, transactiontype= null, threshold = 0.75) {
  const routurl = $("#" + rooturleid).val();
  const targetTable = $("#" + targettblid);
  const trimmedSearchText = searchText.trim().toLowerCase();

  // Prevent numeric-only input
  if (/^\d+$/.test(trimmedSearchText)) {
    alert("Search text cannot be numeric.");
    return;
  }

  const form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("transactiontype", transactiontype);
  targetTable.find("tbody").empty();

  $.ajax({
    url: `${routurl}modal/book_reconstruction_modal.php?action=SimilarPurposeText_MatchedwithMaster`,
    type: "POST",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType: "json",
    success: function (res) {
      if (!Array.isArray(res) || res.length === 0) {
        targetTable.find("tbody").append(`<tr><td colspan="5" class="text-center">No data available</td></tr>`);
        return;
      }

      let hasFullMatch = false;
      let matchedEntries = [];

      // Filter and score matches
      res.forEach(entry => {
        const masterPurpose = (entry.purpose || entry.purposename || "").toString().trim().toLowerCase();
        if (!masterPurpose) return;

        const matchScore = getMatchScore(masterPurpose, trimmedSearchText);
        if (!isNaN(matchScore) && matchScore >= threshold) {
          matchedEntries.push({ entry, matchScore });
        }
      });

      // Sort by match score descending
      matchedEntries.sort((a, b) => b.matchScore - a.matchScore);

      let rowsHtml = "";

      matchedEntries.forEach(({ entry, matchScore }) => {
        const displayPercent = Math.round(matchScore * 100);
        const isChecked = displayPercent === 100 && !hasFullMatch ? "checked" : "";
        if (displayPercent === 100) hasFullMatch = true;

        let badgeClass = "bg-danger text-white";
        let matchLabel = "Low Match";

        if (displayPercent === 100) {
          badgeClass = "bg-success text-white";
          matchLabel = "Full Match";
        } else if (displayPercent > 70) {
          badgeClass = "bg-primary text-white";
          matchLabel = "High Match";
        } else if (displayPercent > 50) {
          badgeClass = "bg-warning text-dark";
          matchLabel = "Medium Match";
        }
        var bgrow = "";
        if(entry.purposecode && displayPercent===100){
          bgrow = "bg-warning";
        }else if(entry.purposecode && displayPercent<100){
          bgrow = "bg-info-light";
        }

        rowsHtml += `
          <tr class="similar-entry-row ${bgrow}" data-textsource="purpose" data-banksno="${banksno}" data-masteridfromtd="${entry.purposecode || ''}" data-defaultsnofromtd="${entry.defaultsno}" data-subcategoryidfromtd="${entry.subcategoryid}" data-transactiontype="${transactiontype}">
            <td data-label="Existing Name" class="existpayto">${searchText}</td>
            <td data-label="Select" class="text-center">
              <input type="radio" name="similarPurposeRadio" class="similartryradio" />
            </td>
            <td data-label="Name From Master" class="namefrommaster">${entry.purposename || entry.purpose}</td>
            <td data-label="Matched %" class="text-center matchedlabled"><span class="badge ${badgeClass} ms-1">${displayPercent}%  ${matchLabel}</span></td>
          </tr>`;
      });

      // Add option to insert new entry if no 100% match found
      if (!hasFullMatch) {
        rowsHtml += `
          <tr class="similar-entry-row text-primary" data-textsource="purpose" data-banksno="${banksno}" data-masteridfromtd="" data-defaultsnofromtd="" data-subcategoryidfromtd="" data-transactiontype="${transactiontype}">
            <td data-label="Existing Name" class="existpayto">${searchText}</td>
            <td data-label="Select" class="text-center">
              <input type="radio" name="similarPurposeRadio" class="similartryradio" />
            </td>
            <td data-label="Name From Master" class="namefrommaster">${searchText}</td>
            <td data-label="Matched %" class="text-center matchedpercent">Add New Origin Text</td>
          </tr>`;
      }

      targetTable.find("tbody").append(rowsHtml || `<tr><td colspan="5" class="text-center">No similar entries found</td></tr>`);
      
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", error);
      targetTable.find("tbody").append(`<tr><td colspan="5" class="text-center">Error loading data</td></tr>`);
    }
  });
}


function UpdatePurposeText_WithMasterData(rooturleid, companyid_eid, subcompanyid_eid, projectid_eid, suggestedname_eid, btnid) {
  const form_data = new FormData();
  const routurl = $("#" + rooturleid).val();
  var purposename = $("#"+suggestedname_eid).val();
  var purposecode = $("#"+suggestedname_eid).data("purposecode");
  var banksno = $("#"+suggestedname_eid).data("banksno");
  var subcategoryid = $("#"+suggestedname_eid).data("subcategoryid");
  var transactiontype = $("#"+suggestedname_eid).data("transactiontype");
  
  alert(purposename+"=pc: ="+purposecode+" =sno: ="+banksno+"=sb: ="+subcategoryid+"==="+transactiontype);
  // die();

  if(purposename=="" || purposename==null || purposename=="undefined"){
    alertmessage('warning','Purpose name is missing. Please given the purpose name.');
    return false;
  }else if(subcategoryid=="" || subcategoryid==null || subcategoryid=="undefined"){
    alertmessage('warning','Subcategory is missing. Please assign a subcategory for the given purpose name.');
    return false;
  }

  form_data.append("purposename", purposename);
  form_data.append("purposecode", purposecode);
  form_data.append("banksno", banksno);
  form_data.append("subcategoryid", subcategoryid);
  form_data.append("transactiontype", transactiontype);

  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid); 
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid); 
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var loginname = $("#hidden_loginname").val();
  form_data.append("loginname", loginname);

  $.ajax({
    url: routurl + "modal/book_reconstruction_modal.php?action=UpdatePurposeText_WithMasterData",
    type: "POST",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType: "json",
    success: function (res) {
      // console.log(res);
      if(res.status=="Success" || res.status=="success" || res.status=="Duplicate" || res.status=="duplicate"){
        alertmessage('success', res.message);
        location.reload();
      }else{
        alertmessage('warning', res.message);
        return false
      }
    }
  });
}

function UpdatePurposeText_DirecttoMasterTBLCELL(btn) {
  const form_data = new FormData();
   const $row = $(btn).closest("tr");
  const banksno = $row.data("sno");
  const purposetext = $row.data("purposetext");
  const transactiontype = $row.data("transactiontype");

  const routurl = $("#hidden_rooturl").val();
  var companyid = $("#hidden_companyid").val();
  form_data.append("companyid", companyid); 
  var subcompanyid = $("#hidden_subcompanyid").val();
  form_data.append("subcompanyid", subcompanyid); 
  var projectid = $("#hidden_projectid").val();
  form_data.append("projectid", projectid);
  var loginname = $("#hidden_loginname").val();
  form_data.append("loginname", loginname);

  if(purposetext=="" || purposetext==null || purposetext=="undefined"){
    alertmessage('warning','Purpose name is missing. Please given the purpose name.');
    return false;
  }else if(transactiontype=="" || transactiontype==null || transactiontype=="undefined"){
    alertmessage('warning','Transaction type is missing. Please choose the transaction type.');
    return false;
  }

  form_data.append("purposetext", purposetext);
  form_data.append("banksno", banksno);
  form_data.append("transactiontype", transactiontype);

  $.ajax({
    url: routurl + "modal/book_reconstruction_modal.php?action=UpdatePurposeText_DirecttoMaster",
    type: "POST",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType: "json",
    success: function (res) {
      // console.log(res);
      if(res.status=="Success" || res.status=="success" || res.status=="Duplicate" || res.status=="duplicate"){
        alertmessage('success', res.message);
        location.reload();
      }else{
        alertmessage('warning', res.message);
        return false
      }
    }
  });
}


function readyto_reconstructformaster(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,transactiontype,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("transactiontype", transactiontype);
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  // alert(shortby+'=='+shortbyvalue);
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/book_reconstruction_modal.php?action=readyto_reconstructformaster",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      callback(res);
      $(".waitloaderfooter").hide();
    },beforeSend: function(res){
      $(".waitloaderfooter").show();
    }
  });
}

function unreconstruct_closetobookdata(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,transactiontype,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("transactiontype", transactiontype);
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  // alert(shortby+'=='+shortbyvalue);
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/book_reconstruction_modal.php?action=unreconstruct_closetobookdata",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      callback(res);
      $(".waitloaderfooter").hide();
    },beforeSend: function(res){
      $(".waitloaderfooter").show();
    }
  });
}

function UnReconstructDataForReconstructByClient(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,transactiontype,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("transactiontype", transactiontype);
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  // alert(shortby+'=='+shortbyvalue);
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/book_reconstruction_modal.php?action=UnReconstructDataForReconstructByClient",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      callback(res);
      // console.log(res);
      $(".waitloaderfooter").hide();
    },beforeSend: function(res){
      $(".waitloaderfooter").show();
    }
  });
}

function ReconstructedHistoryData_Callback(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,transactiontype,shortby,shortbyvalue,callback){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);  
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);  
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("transactiontype", transactiontype);
  form_data.append("shortby", shortby);
  form_data.append("shortbyvalue", shortbyvalue);
  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/book_reconstruction_modal.php?action=ReconstructedHistoryData_Callback",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      callback(res);
      // console.log(res);
      $(".waitloaderfooter").hide();
    },beforeSend: function(res){
      $(".waitloaderfooter").show();
    }
  });
}

function openModalForReconstructedHistory(routurl_eid,shortby=null,shortbyvalue=null){
  var form_data = new FormData();
  var routurl = $("#"+routurl_eid+"").val();
  $("#webpagebodydiv").load(routurl+"reconcilation_reconstruct_book/reconsutructed_historyview.php", {
      shortby: shortby, shortbyvalue: shortbyvalue 
  }, function(){
      $("#webpagebodyview_modal").modal("show");
      $("#modalheader").html("Reconstructed History");
  });  
}

function UnReconciliedActualBookByChatur(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountidval,datefrom,dateto,tableid){
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
    url: baseurl+"modal/book_reconstruction_modal.php?action=UnReconciliedActualBookByChatur",
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

function UnReconciliedData_FromPostPayment(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountidval,datefrom,dateto,tableid){
  var form_data = new FormData();
  var routurl = $("#"+routurl_eid+"").val();
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
    url: routurl+"modal/book_reconstruction_modal.php?action=UnReconciliedData_FromPostPayment",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
      $("#" + tableid + " tbody").empty();
      var httrow="";
      if (res.length > 0) {
        let transactiondate = "";
        let httrow = "";
        for (var i = 0; i < res.length; i++) {
          var voicePath ="N/A";
          if(res[i].voicefilepath){
            voicePath = "<button type='button' onclick='playVoiceFile(\"" + routurl + res[i].voicefilepath + "\")' class='btn btn-sm btn-primary'>🔊</button>";
          }
          var paytowarning = "<span class='badge badge-warning'>not-verify</span>";
          if(res[i].paytoid){
            paytowarning="<span class='badge badge-success'>verify with master</span>";
          }

          var prpwarning = "<span class='badge badge-warning'>not-verify</span>";
          if(res[i].purposecode){
            prpwarning="<span class='badge badge-success'>verify with master</span>";
          }
          httrow += `
            <tr data-rfmsno='${res[i].sno}' data-date='${res[i].transactiondatedsp}' data-amount='${res[i].requestamount}' data-payto='${res[i].payto}' data-purpose='${res[i].purpose}' data-description='${res[i].description}'>
              <td><input type='radio' name='bookdataradio' class='bookdataradio' /></td>
              <td><span class='recunstructportpayment' style='cursor:pointer; color: blue; margin:4px;'><i class="fa-solid fa-pen-to-square fa-lg"></i> Edit</span> <span class='removepostpayment d-none' style="cursor:pointer; color: red; margin:4px;"><i class="fa-solid fa-trash fa-lg"></i> </span></td>
              <td>${res[i].transactiondatedsp}</td>
              <td>${res[i].transactionno}</td>
              <td>${res[i].requestamount}</td>
              <td>${res[i].payto+' '+paytowarning}</td>
              <td>${res[i].purpose+' '+prpwarning}</td>
              <td>${res[i].description}</td>
              <td style='text-align:center;'>${voicePath}</td>
            </tr>
          `;
        }
        if (httrow) {
          $("#" + tableid + " tbody").append(httrow);
        }
      } else {
        $("#" + tableid + " tbody").append("<tr><td colspan='8' class='text-center'>No data available</td></tr>");
      }
      $(".waitloaderfooter").hide();
    },beforeSend: function(res){
      $(".waitloaderfooter").show();
    }
  });
}

function ReconstructPostPaymentData(rec_paytoid,rec_purposecode,updbtnid){
  var form_data = new FormData();
  var routurl = $("#hidden_rooturl").val();
  var companyid = $("#hidden_companyid").val();
  var subcompanyid = $("#hidden_subcompanyid").val();
  var projectid = $("#hidden_projectid").val();

  var paytoid = $("#" + rec_paytoid).val();
  if (!paytoid || paytoid === "null" || paytoid === "undefined") {
    alertmessage("warning", "Payment recipient is missing.");
    return false;
  }
  var purposecode = $("#" + rec_purposecode).val();
  if (!purposecode || purposecode === "null" || purposecode === "undefined") {
    alertmessage("warning", "Purpose of this transaction is missing.");
    return false;
  }

  var dbsno = $("#"+updbtnid).data("dbsno");
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("paytoid", paytoid);
  form_data.append("purposecode", purposecode);
  form_data.append("dbsno", dbsno);
  $.ajax
  ({
    type: "POST",
    url: routurl+"modal/book_reconstruction_modal.php?action=ReconstructPostPaymentData",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
      $(".waitloaderfooter").hide();
      if(res.status=="Success" || res.status=="success"){
        alertmessage("success", res.message);
        $("#refreshppdata").trigger("click");
      }else{
        alertmessage("warning", res.message);
        return false;
      }
    },beforeSend: function(res){
      // $(".waitloaderfooter").show();
    }
  });
}

function ReconciliationmarkedtoPostpaymentAutoMatic(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("loginname", $("body").data("loginname"));
  form_data.append("loginid", $("body").data("loginid"));

  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/book_reconstruction_modal.php?action=ReconciliationmarkedtoPostpaymentAutoMatic",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
    }
  });
}

function ReconciliationmarkedtoPostpayment_manual(routurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,accountid_eid,bankdatatblid,bookdatatblid){
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
      alertmessage('error', 'Postpayment checkbox is not checked, Please checked it');
      return false;
  }
  form_data.append('bookdatadbsno', bookdata);


  var bankdata;
  bankdata = storeTblValuesBank('#'+bankdatatblid+'');
  bankdata = JSON.stringify(bankdata);
  function storeTblValuesBank(mytable)
  {
    var TableDataBank = new Array();
    var mytableid = mytable + ' tbody tr';
    var i=0;
    $(mytableid).each(function(row, tr){
      if($(tr).closest('tr').find('td:first').find('input[name="sltbankentry"]').is(":checked")){
        TableDataBank[i]={
          "banksno" :$(tr).closest('tr').data('banksno')
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

  

  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/book_reconstruction_modal.php?action=ReconciliationmarkedtoPostpayment_manual",
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
        for (var i = 0; i < res.length; i++) {
          if(res[i].status=="Success"){
            ('#'+bankdatatblid+'').hide();
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
              alertmessage('success', res[i].message);
            }
            $("#selectedbkamount").html(0);
            $("#selectedexpamount").html(0);
          }else{
            alertmessage('error', res[i].message);
          }
        }
      }
    },beforeSend: function(res){
      $(".waitloaderfooter").show();
    }
  });
}

function getReferenceNoDetails(referenceno, callback){
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val();
  var companyid = $("#hidden_companyid").val();
  var subcompanyid = $("#hidden_subcompanyid").val();
  var projectid = $("#hidden_projectid").val();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  if(referenceno){
    form_data.append("referenceno", referenceno);
  }else{
    alertmessage('warning', 'Reference number is missing for the record you want to preview.');
    return false;
  }

  $.ajax
  ({
    type: "POST",
    url: baseurl+"modal/book_reconstruction_modal.php?action=getReferenceNoDetails",
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

function CopyReferenceDetailsWithNewReference(referencenoinputid,reasonradioname,remarkinputid,btnid){
  Swal.fire({
    title: "Are you sure?",
    text: "You are attempting to make a duplicate entry because the original transaction has failed. This duplication allows the transaction to be reprocessed with a new reference number while preserving the original details.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, Proceed it!"
  }).then((result) => {
    if (result.isConfirmed) {
      var form_data = new FormData();
      var baseurl = $("#hidden_rooturl").val();
      var companyid = $("#hidden_companyid").val();
      var subcompanyid = $("#hidden_subcompanyid").val();
      var projectid = $("#hidden_projectid").val();
      var loginname = $("#hidden_loginname").val();
      var referenceno = $("#"+referencenoinputid).val();
      var reasonradioval =$("input[name='"+reasonradioname+"']:checked").val();
      var remarks = $("#"+remarkinputid).val();
      form_data.append("companyid", companyid);
      form_data.append("subcompanyid", subcompanyid);
      form_data.append("projectid", projectid);
      form_data.append("reasonradioval", reasonradioval);
      form_data.append("remarks", remarks);
      form_data.append("loginname", loginname);
      if(referenceno){
        form_data.append("referenceno", referenceno);
      }else{
        alertmessage('warning', 'Reference number is missing for the record you want to preview.');
        return false;
      }
      var btntxt = $("#"+btnid).html();
      $("#"+btnid).prop("disabled", true);
      $("#"+btnid).html("Please wait data updating...");
      $.ajax
      ({
        type: "POST",
        url: baseurl+"modal/book_reconstruction_modal.php?action=CopyReferenceDetailsWithNewReference",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType:'JSON',
        success: function(res)
        {
          // console.log(res);
          // callback(res);
          $("#"+btnid).prop("disabled", false);
          $("#"+btnid).html(btntxt);
          if(res.status=="success" || res.status=="Success"){
            SweetAlertmessageFadein('success', res.message);
          }else{
            alertmessage('warning', res.message);
            return false
          }
        }
      });
    }
  });
}