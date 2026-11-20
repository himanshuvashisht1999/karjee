function checkLoginDisplay(baseurl_eid,loginidval,loginpasswordval,submitbtn){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  let companyid = "";
  if(loginidval=="" || loginidval==null || loginidval=="undefined"){
    alertmessage('error','Login id is missing');
    return false;
  }else if(loginpasswordval=="" || loginpasswordval==null || loginpasswordval=="undefined"){
    alertmessage('error','Login passowrd is missing');
    return false;
  }
  form_data.append("baseurl", baseurl);
  form_data.append("companyid", companyid);
  form_data.append("loginidval", loginidval);
  form_data.append("loginpasswordval", loginpasswordval);
  var routurl = $("body").data("routurl");
  $.ajax({
    type: "POST",
    url: "modal/login_modal.php?action=entertologin",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success:function(res)
    {
      //console.log(res);
      // alert(res);
      if(res.length>0){
        for (var i = 0; i<res.length; i++) {
          if(res[i].status=="Success"){
            // alert(res[i].loginrolename);
            alertmessage('success',res[i].message);
            var qstring = res[i].loginid+"|"+res[i].loginroleid+"|"+res[i].companyid+"|"+res[i].subcompanyid+"|"+res[i].projectid+"|"+res[i].loginrolename;
            var enc_qstring = url_btoa(qstring);
            var key = qstring + "|" + enc_qstring;
            var enc_key = url_btoa(key);
            window.location.href="indexdisplayprojects.php?k="+enc_key;
          }else{
            alertmessage('error',res[i].message);
            return false;
          }
        }
      }else{
        alertmessage('error','Somthing wrong');
        return false;
      }  
    }
  });
}

function logoutUser() {
    var routurl = $("body").data("routurl");
    $.ajax({
        url: routurl+'loginCell/logout.php', // Logout endpoint
        type: 'POST',
        data: {}, // Add necessary data if required
        dataType:'JSON',
        success: function (response) {
            if(response.status === "Success") {
                // Clear sidebar collapsed state if needed, preserve last_active_module_url
                localStorage.removeItem('sidebar_collapsed');
                
                alertmessage("success", "You have been logged out successfully.");
                window.location.href = routurl+'loginCell/index.php'; // Redirect to the home page
            } else {
                alertmessage("error", response.message || "An error occurred while logging out. Please try again.");
            }
        },
        error: function (xhr, status, error) {
            console.error('Logout failed:', error);
            alertmessage("error", "A server error occurred while logging out. Please try again.");
        }
    });
}

function authorizedprojects_homehtmlview(baseurl,loginidval,callback){
  var form_data = new FormData();
  form_data.append("loginid", loginidval);
  var routurl = $("body").data("routurl");
  $.ajax({
    type: "POST",
    url: routurl+"loginCell/modal/login_modal.php?action=authorizedprojects_homehtmlview",
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

function logindetails_htmltableview(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,loginid_eid,viewtableid,callback){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
    
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
    
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  
  var loginid = $("#"+loginid_eid+"").val();
  form_data.append("loginid", loginid);
  var routurl = $("body").data("routurl");
  $.ajax({
    type: "POST",
    url: routurl+"loginCell/modal/login_modal.php?action=logindetails_htmltableview",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
        $("#"+viewtableid+" tbody").html('');
        $("#"+viewtableid+" tbody").html(res);          
        callback(res);
      }
    });
}

function user_rolelistdropdown_html(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,loginid_eid,alreadyexist,viewdropid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var loginid = $("#"+loginid_eid+"").val();
  form_data.append("loginid", loginid);
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  var routurl = $("body").data("routurl");
  $.ajax({
    type: "POST",
    url: routurl+"loginCell/modal/login_modal.php?action=loginrolelistoption",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
        $("#"+viewdropid+"").empty('');
        $("#"+viewdropid+"").append(res);
        if(alreadyexist){
          $("#"+viewdropid+"").val(alreadyexist).trigger("change");
        }
      }
    });
}

function department_html(baseurl_eid,controlid,valueexists=""){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  $("#"+controlid).empty();
   $("#"+controlid).append("<option value=''>Select Department</option>")  
  var routurl = $("body").data("routurl");
  $.ajax({
    type: "POST",
    url: routurl+"loginCell/modal/login_modal.php?action=getdepartments",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res)
      {
        if(res.length>0){     
          for(var i=0; i<res.length; i++){ 
            $("#"+controlid).append("<option value='" + res[i].id + "'>" + res[i].departmentname + "</option>");
          }
          if(valueexists)
          {

           $("#"+controlid).val(valueexists).trigger("change");
          }
      }
      }
    });
}

function logindetails_fromloginid(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,loginidval,callback){

  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
    
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
    
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  
  // var loginid = $("#"+loginid_eid+"").val();
  form_data.append("loginid", loginidval);
  var routurl = $("body").data("routurl");
  $.ajax({
    type: "POST",
    url: routurl+"loginCell/modal/login_modal.php?action=logindetailswithloginid",
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

function projectlist_forauthorizaton_html(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,loginidval,viewtableid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
    
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
    
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  
  form_data.append("loginid", loginidval);
  var routurl = $("body").data("routurl");
  $.ajax({
    type: "POST",
    url: routurl+"loginCell/modal/login_modal.php?action=projectlistforauthorizaton_html",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
        $("#"+viewtableid+" tbody").html('');
        $("#"+viewtableid+" tbody").html(res);          
      }
    });
}

function modulelist_frommaster_html(baseurl_eid,companyid_eid,subcompanyid_eid,projectid_eid,loginidval,viewtableid){
  var form_data = new FormData();
  var baseurl = $("#"+baseurl_eid+"").val();
  var companyid = $("#"+companyid_eid+"").val();
  form_data.append("companyid", companyid);
    
  var subcompanyid = $("#"+subcompanyid_eid+"").val();
  form_data.append("subcompanyid", subcompanyid);
    
  var projectid = $("#"+projectid_eid+"").val();
  form_data.append("projectid", projectid);
  form_data.append("userloginid", loginidval);
  form_data.append("loginedid", $("body").data("loginid"));
  var routurl = $("body").data("routurl");
  $.ajax({
    type: "POST",
    url: routurl+"loginCell/modal/login_modal.php?action=modulelistforauthorizaton_html",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'html',
      success: function(res)
      {
        // console.log(res);
        $("#"+viewtableid+" tbody").html('');
        $("#"+viewtableid+" tbody").html(res);          
      }
    });
}

function savetodbuserlogindetails(baseurl_eid, companyid_eid, subcompanyid_eid, loginid_eid, loginpassword_eid, username_eid, usermobileno_eid, useremail_eid, userloginroleid_eid, isactive_inpname, authorizedproject_tblid, authorizedmodule_tblid,
  savetodb_btnid, departmentofuser_eid)
{
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid).val();
  var companyid = $("#" + companyid_eid).val();
  form_data.append("companyid", companyid);

  var subcompanyid = $("#" + subcompanyid_eid).val();
  form_data.append("subcompanyid", subcompanyid);

  var userid = $("#" + loginid_eid).val();
  if (!userid) {
    alertmessage('error', 'User id missing/invalid');
    $("#" + loginid_eid).focus();
    return false;
  }
  form_data.append("userid", userid);

  var loginpassword = $("#" + loginpassword_eid).val();
  if (!loginpassword) {
    alertmessage('error', 'Login password is missing');
    $("#" + loginpassword_eid).focus();
    return false;
  }
  form_data.append("loginpassword", loginpassword);

  var username = $("#" + username_eid).val();
  if (!username) {
    alertmessage('error', 'User name missing');
    $("#" + username_eid).focus();
    return false;
  }
  form_data.append("username", username);

  var usermobileno = $("#" + usermobileno_eid).val();
  if (!usermobileno) {
    alertmessage('error', 'User mobile no is missing');
    $("#" + usermobileno_eid).focus();
    return false;
  }
  form_data.append("usermobileno", usermobileno);

  var useremail = $("#" + useremail_eid).val();
  form_data.append("useremail", useremail);

  var userloginroleid = $("#" + userloginroleid_eid).val();
  if (!userloginroleid) {
    alertmessage('error', 'User role is missing');
    $("#" + userloginroleid_eid).focus();
    return false;
  }

  var departmentVal = $("#" + departmentofuser_eid).val();
  if (!departmentVal) {
    alertmessage('error', 'User Department is missing');
    $("#" + departmentofuser_eid).focus();
    return false;
  }

  form_data.append("userloginroleid", userloginroleid);
  form_data.append("departmentofuser", departmentVal);

  var isactivestatus = $("input[name='" + isactive_inpname + "']:checked").val();
  form_data.append("isactivestatus", isactivestatus);

  // ✅ collect projects
  var AuthprjArr = [];
  $('#' + authorizedproject_tblid + ' tr').each(function (row, tr) {
    if ($(tr).find('input.projectheckbox').is(":checked")) {
      AuthprjArr.push({
        "projectid": $(tr).find("input.projectid_input").val()
      });
    }
  });
  if (AuthprjArr.length <= 0) {
    alertmessage('error', 'Project authorization missing, Please choose at least one project');
    return false;
  }
  form_data.append('authorizedproject', JSON.stringify(AuthprjArr));

  // ✅ collect modules
  var AuthmdlArr = [];
  if ($("#doauthorizedmodule").is(":checked")) {
    $('#' + authorizedmodule_tblid + ' tr').each(function (row, tr) {
      if ($(tr).find('input.modulecheckbox').is(":checked")) {
        AuthmdlArr.push({
          "moduleid": $(tr).find("input.moduleid_input").val(),
          "ismodulepasskey": $(tr).find("input.ispasskey").val() || "",
          "modulepassword": $(tr).find("input.modulepasskey").val() || ""
        });
      }
    });
    if (AuthmdlArr.length <= 0) {
      alertmessage('error', 'Please select at least one module since module authorization is checked.');
      return false;
    }
  }
  form_data.append('authorizedmodule', JSON.stringify(AuthmdlArr));

  var savebyname = $("body").data("loginname");
  form_data.append("savebyname", savebyname);

  var btnval = $("#" + savetodb_btnid).html();
  $("#" + savetodb_btnid).html('Saving details...');
  $("#" + savetodb_btnid).prop('disabled', true);

  var routurl = $("body").data("routurl");

  $.ajax({
    type: "POST",
    url: routurl + "loginCell/modal/login_modal.php?action=addupdatelogindetails",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType: 'JSON',
    success: function (res) {
      $("#" + savetodb_btnid).html(btnval);
      $("#" + savetodb_btnid).prop('disabled', false);

      if (res && res.length > 0) {
        var hasError = false;
        var errorMsg = "";
        var successMsg = "";
        
        for (var i = 0; i < res.length; i++) {
          if (res[i].status == "Error") {
            hasError = true;
            errorMsg = res[i].message;
            break;
          } else if (res[i].status == "Success") {
            successMsg = res[i].message;
          }
        }

        if (hasError) {
          Swal.fire({
            title: "Error!",
            text: errorMsg,
            icon: "error",
            confirmButtonColor: "#ef4444"
          });
        } else if (successMsg) {
          Swal.fire({
            title: "Success!",
            text: successMsg,
            icon: "success",
            confirmButtonText: "OK",
            confirmButtonColor: "#0284c7"
          }).then((result) => {
            var loginid_val = $("body").data("loginid");
            var loginroleid_val = $("body").data("loginroleid");
            var companyid_val = $("#" + companyid_eid).val();
            var subcompanyid_val = $("#" + subcompanyid_eid).val();
            var projectid_val = $("body").data("projectid") || "1";
            var loginrolename_val = $("body").data("loginrolename");
            
            var qstring = loginid_val + "|" + loginroleid_val + "|" + companyid_val + "|" + subcompanyid_val + "|" + projectid_val + "|" + loginrolename_val;
            var enc_qstring = url_btoa(qstring);
            var fullkey = qstring + "|" + enc_qstring;
            var enc_key = url_btoa(fullkey);
            window.location.href = routurl + "loginCell/loginmanagement.php?k=" + enc_key;
          });
        }
      }
    },
    error: function() {
      $("#" + savetodb_btnid).html(btnval);
      $("#" + savetodb_btnid).prop('disabled', false);
      Swal.fire({
        title: "Error!",
        text: "Server error occurred while updating user details.",
        icon: "error",
        confirmButtonColor: "#ef4444"
      });
    }
  });
}


function updatedomainsubdonainToCompanyMaster(companyid, domainname)
{
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("domainname", domainname);
  var routurl = $("body").data("routurl");
  $.ajax({
    type: "POST",
    url: routurl + "loginCell/modal/login_modal.php?action=updatedomainsubdonainToCompanyMaster",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType: 'JSON',
    success: function (res) {
      if (res.status == "Success" || res.status == "success") {
          SweetAlertmessageFadein(res.status, res.message);
          window.location.reload();
        } else {
          alertmessage('error', res.message);
          return false;
        }
    }
  });
}

function removeuserloginfrommasterhtml(routurl_eid,userid,username){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();  
  form_data.append("userid", userid);
  form_data.append("username", username);
  var routurl = $("body").data("routurl");
  $.ajax({
    type: "POST",
    url: routurl+"loginCell/modal/login_modal.php?action=getdetailsfor_removeuserlogin",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'html',
    success: function(res)
    {
      // console.log(res);
      $("#modalheader").html("Remove login details of "+username);
      $("#webpagebodydiv").html(res);
      $("#webpagebodyview_modal").modal("show");      
    }
  });  
}

function update_removeuserlogin(routurl_eid,userid,authorizeprjtblid,isconfirm_eid){
  var form_data = new FormData();
  var baseurl = $("#"+routurl_eid+"").val();
  form_data.append("userid", userid); 

  if (!$("#" + isconfirm_eid).is(":checked")) {
      alertmessage('error', 'Please check the confirm checkbox');
      return false;
  }
  var Authprj;
  Authprj = storeTblValues1('#'+authorizeprjtblid+'');
  Authprj = JSON.stringify(Authprj);
  function storeTblValues1(mytable)
  {
    var TableData_prj = new Array();
    var mytableid = mytable + ' tr';
    var i=0;
    $(mytableid).each(function(row, tr){
      if($(tr).closest('tr').find('td').find('input[type="checkbox"]').is(":checked")){
        TableData_prj[i]={
          "companyid" :$(tr).closest('tr').data("rmcompanyid")
          ,"subcompanyid" :$(tr).closest('tr').data("rmsubcompanyid")
          ,"projectid" :$(tr).closest('tr').data("rmprojectid")
        }
        i++;
      }    
    });
    // TableData_prj.shift();  // first row will be empty - so remove
    return TableData_prj;
  }
  form_data.append('authorizedproject', Authprj);

  var loginname = $("body").data("loginname");
  form_data.append("loginname", loginname);

  var routurl = $("body").data("routurl");
  $.ajax({
    type: "POST",
    url: routurl+"loginCell/modal/login_modal.php?action=update_removeuserlogin",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    dataType:'JSON',
    success: function(res)
    {
      // console.log(res);
      if(res.status=="Success"){
         SweetAlertmessageFadein("success",res.message);
      }else{
        alertmessage("error", res.message);
        return false;
      }
    }
  });
}