function checkLogin(baseurl_eid, loginidval, loginpasswordval, submitbtn) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();
  if (loginidval == "" || loginpasswordval == "") {
    alertmessage('error', 'invalid login details');
    return false;
  }
  form_data.append("baseurl", baseurl);
  form_data.append("loginidval", loginidval);
  form_data.append("loginpasswordval", loginpasswordval);
  $.ajax(
    {
      type: "POST",
      url: baseurl + "modal/login_modal.php?action=entertologin",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        // console.log(res);
        if (res.length > 0) {
          for (var i = 0; i < res.length; i++) {
            if (res[i].status == "Success") {
              alertmessage('success', res[i].message);
              var qstring = res[i].loginid + "|" + res[i].loginroleid + "|" + res[i].companyid + "|" + res[i].subcompanyid + "|" + res[i].projectid + "|" + res[i].loginrolename;
              var enc_qstring = url_btoa(qstring);
              var key = qstring + "|" + enc_qstring;
              var enc_key = url_btoa(key);
              // if(res[i].comments=="noproject"){
              //     window.location.href=baseurl+"module_client/request_module.php?k="+enc_key;
              // }else{
              // }
              window.location.href = baseurl + "indexprojects.php?k=" + enc_key;

            } else {
              alertmessage('error', res[i].message);
              return false;
            }
          }
        } else {
          alertmessage('error', 'Somthing wrong');
          return false;
        }
      }
    });
}

function logoutUser() {
  var rootUrl = $("#hidden_rooturl").val(); // Get the root URL from the hidden input
  if (!rootUrl) {
    console.error("Root URL is not defined.");
    alertmessage("error", "An unexpected error occurred. Please try again.");
    return false;
  }
  $.ajax({
    url: rootUrl + 'login/logout.php', // Logout endpoint
    type: 'POST',
    data: {}, // Add necessary data if required
    dataType: 'JSON',
    success: function (response) {
      if (response.status === "Success") {
        // Clear sidebar collapsed state if needed, preserve last_active_module_url
        localStorage.removeItem('sidebar_collapsed');

        alertmessage("success", "You have been logged out successfully.");
        window.location.href = rootUrl + '/index.php'; // Redirect to the home page
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

function logindetails_htmltableview(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, loginid_eid, viewtableid, callback) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();
  var companyid = $("#" + companyid_eid + "").val();
  form_data.append("companyid", companyid);

  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  form_data.append("subcompanyid", subcompanyid);

  var projectid = $("#" + projectid_eid + "").val();
  form_data.append("projectid", projectid);

  var loginid = $("#" + loginid_eid + "").val();
  form_data.append("loginid", loginid);
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=logindetails_htmltableview",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'html',
      success: function (res) {
        // console.log(res);
        $("#" + viewtableid + " tbody").html('');
        $("#" + viewtableid + " tbody").html(res);
        callback(res);
      }
    });
}

function logindetails_fromloginid(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, loginidval, callback) {

  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();
  var companyid = $("#" + companyid_eid + "").val();
  form_data.append("companyid", companyid);

  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  form_data.append("subcompanyid", subcompanyid);

  var projectid = $("#" + projectid_eid + "").val();
  form_data.append("projectid", projectid);

  // var loginid = $("#"+loginid_eid+"").val();
  form_data.append("loginid", loginidval);
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=logindetailswithloginid",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        // console.log(res);
        callback(res);
      }
    });
}

function removeuserloginfrommasterhtml(routurl_eid, userid, username) {
  var form_data = new FormData();
  var baseurl = $("#" + routurl_eid + "").val();
  form_data.append("userid", userid);
  form_data.append("username", username);
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=getdetailsfor_removeuserlogin",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'html',
      success: function (res) {
        // console.log(res);
        $("#modalheader").html("Remove login details of " + username);
        $("#webpagebodydiv").html(res);
        $("#webpagebodyview_modal").modal("show");
      }
    });
}

function update_removeuserlogin(routurl_eid, userid, authorizeprjtblid, isconfirm_eid) {
  var form_data = new FormData();
  var baseurl = $("#" + routurl_eid + "").val();
  form_data.append("userid", userid);

  if (!$("#" + isconfirm_eid).is(":checked")) {
    alertmessage('error', 'Please check the confirm checkbox');
    return false;
  }
  var Authprj;
  Authprj = storeTblValues1('#' + authorizeprjtblid + '');
  Authprj = JSON.stringify(Authprj);
  function storeTblValues1(mytable) {
    var TableData_prj = new Array();
    var mytableid = mytable + ' tr';
    var i = 0;
    $(mytableid).each(function (row, tr) {
      if ($(tr).closest('tr').find('td').find('input[type="checkbox"]').is(":checked")) {
        TableData_prj[i] = {
          "companyid": $(tr).closest('tr').data("rmcompanyid")
          , "subcompanyid": $(tr).closest('tr').data("rmsubcompanyid")
          , "projectid": $(tr).closest('tr').data("rmprojectid")
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

  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=update_removeuserlogin",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        // console.log(res);
        if (res.status == "Success") {
          SweetAlertmessageFadein("success", res.message);
        } else {
          alertmessage("error", res.message);
          return false;
        }
      }
    });
}

function user_rolelistdropdown_html(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, loginid_eid, alreadyexist, viewdropid) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();
  var loginid = $("#" + loginid_eid + "").val();
  form_data.append("loginid", loginid);
  var companyid = $("#" + companyid_eid + "").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  form_data.append("subcompanyid", subcompanyid);
  var projectid = $("#" + projectid_eid + "").val();
  form_data.append("projectid", projectid);
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=loginrolelistoption",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'html',
      success: function (res) {
        // console.log(res);
        $("#" + viewdropid + "").empty('');
        $("#" + viewdropid + "").append(res);
        if (alreadyexist) {
          $("#" + viewdropid + "").val(alreadyexist).trigger("change");
        }
      }
    });
}

function savetodbuserlogindetails(baseurl_eid, companyid_eid, subcompanyid_eid, loginid_eid, loginpassword_eid, username_eid, usermobileno_eid, useremail_eid, userloginroleid_eid, isactive_inpname, authorizedproject_tblid, authorizedmodule_tblid, savetodbloginname_eid, savetodb_btnid, departmentofuser) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();

  var companyid = $("#" + companyid_eid + "").val();
  form_data.append("companyid", companyid);
  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  form_data.append("subcompanyid", subcompanyid);

  var userid = $("#" + loginid_eid + "").val();
  if (userid == null || userid == "undefined" || userid == "") {
    alertmessage('error', 'User id missing/invalid');
    $("#" + loginid_eid + "").focus();
    return false;
  }
  form_data.append("userid", userid);


  var loginpassword = $("#" + loginpassword_eid + "").val();
  if (loginpassword == null || loginpassword == "undefined" || loginpassword == "") {
    alertmessage('error', 'Login passowrd is missing');
    $("#" + userid_eid + "").focus();
    return false;
  }
  form_data.append("loginpassword", loginpassword);

  var username = $("#" + username_eid + "").val();
  if (username == null || username == "undefined" || username == "") {
    alertmessage('error', 'User name missing');
    $("#" + username_eid + "").focus();
    return false;
  }
  form_data.append("username", username);
  var usermobileno = $("#" + usermobileno_eid + "").val();
  if (usermobileno == null || usermobileno == "undefined" || usermobileno == "") {
    alertmessage('error', 'User mobile no is missing');
    $("#" + usermobileno_eid + "").focus();
    return false;
  }
  form_data.append("usermobileno", usermobileno);

  var useremail = $("#" + useremail_eid + "").val();
  form_data.append("useremail", useremail);
  // alert(useremail);
  var userloginroleid = $("#" + userloginroleid_eid + "").val();
  if (userloginroleid == null || userloginroleid == "undefined" || userloginroleid == "") {
    alertmessage('error', 'User rol is missing');
    $("#" + userloginroleid_eid + "").focus();
    return false;
  }
  var departmentofuser = $("#" + departmentofuser + "").val();
  if (departmentofuser == null || departmentofuser == "undefined" || departmentofuser == "") {
    alertmessage('error', 'User Department is missing');
    $("#" + departmentofuser + "").focus();
    return false;
  }
  form_data.append("userloginroleid", userloginroleid);
  form_data.append("departmentofuser", departmentofuser);
  var isactivestatus = $("input[name='" + isactive_inpname + "']:checked").val();
  form_data.append("isactivestatus", isactivestatus);
  var Authprj;
  Authprj = storeTblValues1('#' + authorizedproject_tblid + '');
  Authprj = JSON.stringify(Authprj);
  function storeTblValues1(mytable) {
    var TableData_prj = new Array();
    var mytableid = mytable + ' tr';
    var i = 0;
    $(mytableid).each(function (row, tr) {
      if ($(tr).closest('tr').find('td').find('input.projectheckbox').is(":checked")) {
        TableData_prj[i] = {
          "projectid": $(tr).closest('tr').find("td.projectname_td").find('input.projectid_input').val()
        }
        i++;
      }
    });
    // TableData_prj.shift();  // first row will be empty - so remove
    return TableData_prj;
  }
  // alert(Authprj);
  // console.log(Authprj);
  form_data.append('authorizedproject', Authprj);
  if (Authprj.length <= 10) {
    alertmessage('error', 'Project authorization missing, Please atleast one project choose for authorization');
    // $("#"+userid_eid+"").focus();
    return false;
  }
  var Authmdl = chkmoduletbl(authorizedmodule_tblid);
  if (Authmdl.length <= 0) {
    alert("You have Not Selected Any Modules");
    return false;
  }
  Authmdl = JSON.stringify(Authmdl);
  function storeTblValues2(mytable) {
    var TableData_apj = new Array();
    var mytableid = mytable + ' tr';
    $(mytableid).each(function (row, tr) {
      if ($(tr).closest('tr').find('td').find('input.modulecheckbox').is(":checked")) {
        TableData_apj[row] = {
          "moduleid": $(tr).closest('tr').find("td.modulename_td").find('input.moduleid_input').val()
        }
      }
    });
    TableData_apj.shift();  // first row will be empty - so remove
    return TableData_apj;
  }
  form_data.append('authorizedmodule', Authmdl);
  var savebyname = $("#" + savetodbloginname_eid + "").val();
  form_data.append("savebyname", savebyname);
  var btnval = $("#" + savetodb_btnid + "").html();
  $("#" + savetodb_btnid + "").html('Please wait data...');
  $("#" + savetodb_btnid + "").prop('disabled', true);
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=addupdatelogindetails",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        // console.log(res);
        $("#" + savetodb_btnid + "").html(btnval);
        $("#" + savetodb_btnid + "").prop('disabled', false);
        if (res.length > 0) {
          for (var i = 0; i < res.length; i++) {
            if (res[i].status == "Success") {
              // alertmessage('success', res[i].message);
              SweetAlertmessageFadein(res[i].status, res[i].message);
              window.location.reload();
            } else {
              alertmessage('error', res[i].message);
              return false;
            }
          }
        }
      }
    });
}

function onlyprojectmoduleswithgrouphtml(routurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, viewtableid) {
  var form_data = new FormData();
  var baseurl = $("#" + routurl_eid + "").val();

  var companyid = $("#" + companyid_eid + "").val();
  form_data.append("companyid", companyid);

  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  form_data.append("subcompanyid", subcompanyid);

  var projectid = $("#" + projectid_eid + "").val();
  form_data.append("projectid", projectid);

  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=onlyprojectmoduleswithgrouphtml",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      // dataType:'JSON',
      success: function (res) {
        // console.log(res);
        $("#" + viewtableid + " tbody").html('');
        if (res) {
          $("#" + viewtableid + " tbody").html(res);
        }
      }
    });
}

function moduleswithgrouphtml(routurl_eid, viewtableid) {
  var form_data = new FormData();
  var baseurl = $("#" + routurl_eid + "").val();
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=moduleswithgrouphtml",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      // dataType:'JSON',
      success: function (res) {
        // console.log(res);
        $("#" + viewtableid + " tbody").html('');
        if (res) {
          $("#" + viewtableid + " tbody").html(res);
        }
      }
    });
}

function addupdate_moduleswithgroup(groupid, moduleid, modulename, modulefilepath, actionfordo, integrationtype = 'integreation') {
  var form_data = new FormData();
  var routurl = $("#hidden_rooturl").val();
  if (modulename == "" || modulename == null || modulename == "undefined") {
    alertmessage("error", "Module title name is missing");
    return false;
  } else if (modulefilepath == "" || modulefilepath == null || modulefilepath == "undefined") {
    alertmessage("error", "Redirection path/location is missing");
    return false;
  } else if (groupid == "" || groupid == null || groupid == "undefined") {
    alertmessage("error", "Group title selection is missing");
    return false;
  }
  form_data.append("groupid", groupid);
  form_data.append("moduleid", moduleid);
  form_data.append("modulename", modulename);
  form_data.append("modulefilepath", modulefilepath);
  form_data.append("integrationtype", integrationtype);
  form_data.append("actionfordo", actionfordo);
  //alert(integrationtype);

  $.ajax
    ({
      type: "POST",
      url: routurl + "modal/admindashboard_modal.php?action=addupdate_moduleswithgroup",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        // console.log(res);
        if (res.status == "Success") {
          SweetAlertmessageFadein("success", res.message);
        } else {
          alertmessage("error", res.message);
          return false;
        }
      }
    });
}

function grouptitlelist_callback(routurl_eid, callback) {
  var form_data = new FormData();
  var baseurl = $("#" + routurl_eid + "").val();
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=grouptitlelist_callback",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        callback(res);
      }
    });
}

function modulegroupingforcompanyhtml(routurl_eid) {
  var form_data = new FormData();
  var baseurl = $("#" + routurl_eid + "").val();
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=groupoftitlehtml",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'html',
      success: function (res) {
        // console.log(res);
        $("#modalheader").html("Group Titles of Modules");
        $("#webpagebodydiv").html(res);
        $("#webpagebodyview_modal").modal("show");
      }
    });
}

function groupoftitlehtml(routurl_eid) {
  var form_data = new FormData();
  var baseurl = $("#" + routurl_eid + "").val();
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=groupoftitlehtml",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'html',
      success: function (res) {
        // console.log(res);
        $("#modalheader").html("Group Titles of Modules");
        $("#webpagebodydiv").html(res);
        $("#webpagebodyview_modal").modal("show");
      }
    });
}

function addupdate_grpttlhtml(routurl_eid, grouptitleid, action) {
  var form_data = new FormData();
  var baseurl = $("#" + routurl_eid + "").val();
  form_data.append("grouptitleid", grouptitleid);
  form_data.append("action", action);
  if (action == "new") {
    var mdtitle = "Add New Group Title";
  } else if (action == "edit") {
    var mdtitle = "Edit of Group Title";
  } else if (action == "remove") {
    var mdtitle = "Remove of Group Title";
  }
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=addupdate_grpttlhtml",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'html',
      success: function (res) {
        // console.log(res);
        $("#modalheader1").html(mdtitle);
        $("#webpagebodydiv1").html(res);
        $("#webpagebodyview_modal1").modal("show");
      }
    });
}

function addupdate_grpttl(routurl_eid, grouptitleid, isconfirm_eid) {
  var form_data = new FormData();
  var baseurl = $("#" + routurl_eid + "").val();
  form_data.append("grouptitleid", grouptitleid);
  form_data.append("loginname", $("body").data("loginname"));
  if (!$("#" + isconfirm_eid).is(":checked")) {
    alertmessage('error', 'Please check the confirm checkbox');
    return false;
  }

  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=addupdate_grpttl",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'html',
      success: function (res) {
        // console.log(res);
        if (res.status == "Success") {
          SweetAlertmessageFadein("success", res.message);
        } else {
          alertmessage("error", res.message);
          return false;
        }
      }
    });
}

function uploadnewlogindetails_excelfile(routurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, uploadexcel_eid, clicktobtnid, callback) {

  var form_data = new FormData();
  var baseurl = $("#" + routurl_eid + "").val();
  var companyid = $("#" + companyid_eid + "").val();
  form_data.append("companyid", companyid);

  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  form_data.append("subcompanyid", subcompanyid);

  var projectid = $("#" + projectid_eid + "").val();
  form_data.append("projectid", projectid);

  var file = $("#" + uploadexcel_eid + "")[0].files[0];
  if (!file) {
    alert("Please select an Excel file.");
    return;
  }
  form_data.append("excelFile", file);

  var loginid = $("body").data("loginid");
  form_data.append("loginid", loginid);
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/uploadexcelsheet_modal.php?action=uploadnewlogindetails_excelfile",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        // console.log(res);
        callback(res);
      }
    });
}

function authorizedprojects_homehtmlview(baseurl_eid, loginidval, callback) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();
  form_data.append("loginid", loginidval);
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=authorizedprojects_homehtmlview",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        // console.log(res);
        callback(res);
      }
    });
}

function projectlist_forauthorizaton_html(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, loginidval, viewtableid) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();
  var companyid = $("#" + companyid_eid + "").val();
  form_data.append("companyid", companyid);

  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  form_data.append("subcompanyid", subcompanyid);

  var projectid = $("#" + projectid_eid + "").val();
  form_data.append("projectid", projectid);

  form_data.append("loginid", loginidval);
  // alert(loginidval);
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=projectlistforauthorizaton_html",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'html',
      success: function (res) {
        // console.log(res);
        $("#" + viewtableid + " tbody").html('');
        $("#" + viewtableid + " tbody").html(res);
      }
    });
}

function modulelist_frommaster_html(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, loginidval, viewtableid) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();
  var companyid = $("#" + companyid_eid + "").val();
  form_data.append("companyid", companyid);

  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  form_data.append("subcompanyid", subcompanyid);

  var projectid = $("#" + projectid_eid + "").val();
  form_data.append("projectid", projectid);
  form_data.append("userloginid", loginidval);
  form_data.append("loginedid", $("body").data("loginid"));
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=modulelistforauthorizaton_html",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'html',
      success: function (res) {
        // console.log(res);
        $("#" + viewtableid + " tbody").html('');
        $("#" + viewtableid + " tbody").html(res);
      }
    });
}

function modulelist_frommaster_registercompany_html(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, loginidval, viewtableid) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();
  var companyid = $("#" + companyid_eid + "").val();
  form_data.append("companyid", companyid);

  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  form_data.append("subcompanyid", subcompanyid);

  var projectid = $("#" + projectid_eid + "").val();
  form_data.append("projectid", projectid);
  form_data.append("userloginid", loginidval);
  form_data.append("loginedid", $("body").data("loginid"));
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=modulelist_frommaster_registercompany_html",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'html',
      success: function (res) {
        // console.log(res);
        $("#" + viewtableid + " tbody").html('');
        $("#" + viewtableid + " tbody").html(res);
      }
    });
}

function projectdetails_htmltableview(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, loginid_eid, viewtableid) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();
  var companyid = $("#" + companyid_eid + "").val();
  form_data.append("companyid", companyid);

  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  form_data.append("subcompanyid", subcompanyid);

  var projectid = $("#" + projectid_eid + "").val();
  form_data.append("projectid", projectid);

  var loginid = $("#" + loginid_eid + "").val();
  form_data.append("loginid", loginid);
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=projectdetails_htmltableview",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'html',
      success: function (res) {
        // console.log(res);
        $("#" + viewtableid + " tbody").html('');
        $("#" + viewtableid + " tbody").html(res);
      }
    });
}


function savetodbprojectdetails_tomaster(routurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, projectname_eid, projectemailaddress_eid, isactive_inpname, issubproject_inpname, subprojecttblid, savetodb_btnid) {
  var form_data = new FormData();
  var rooturl = $("#" + routurl_eid + "").val();

  var companyid = $("#" + companyid_eid + "").val();
  if (companyid == null || companyid == "undefined" || companyid == "") {
    alertmessage('error', 'Company name missing');
    $("#" + companyid_eid + "").focus();
    return false;
  }

  form_data.append("companyid", companyid);
  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  if (subcompanyid == null || subcompanyid == "undefined" || subcompanyid == "") {
    alertmessage('error', 'Subcompany name missing');
    $("#" + subcompanyid_eid + "").focus();
    return false;
  }
  form_data.append("subcompanyid", subcompanyid);

  var projectid = $("#" + projectid_eid + "").val();
  form_data.append("projectid", projectid);

  var projectname = $("#" + projectname_eid + "").val();
  if (projectname == null || projectname == "undefined" || projectname == "") {
    alertmessage('error', 'Project name missing/invalid');
    $("#" + projectname_eid + "").focus();
    return false;
  }
  form_data.append("projectname", projectname);

  var projectemailaddress = $("#" + projectemailaddress_eid + "").val();
  form_data.append("projectemailaddress", projectemailaddress);


  var loginname = $("body").data('loginname');
  form_data.append("loginname", loginname);
  var isactivestatus = $("input[name='" + isactive_inpname + "']:checked").val();
  form_data.append("isactivestatus", isactivestatus);

  var issubproject = $("input[name='" + issubproject_inpname + "']:checked").val();
  form_data.append("issubproject", issubproject);

  var Authsubprj;
  Authsubprj = storeTblValues1('#' + subprojecttblid + '');
  Authsubprj = JSON.stringify(Authsubprj);
  function storeTblValues1(mytable) {
    var TableData_prj = new Array();
    var mytableid = mytable + ' tr';
    var i = 0;
    $(mytableid).each(function (row, tr) {
      if ($(tr).closest('tr').find('td').find('input').val() != "") {
        // alert($(tr).closest('tr').data("subprojectdbsno"));
        TableData_prj[i] = {
          "subprojectdbsno": $(tr).closest('tr').data("subprojectdbsno"),
          "subprojectid": $(tr).closest('tr').data("subprojectidtd"),
          "subprojectname": $(tr).closest('tr').find("td").find('input').val()
        }
        i++;
      }
    });
    TableData_prj.shift();  // first row will be empty - so remove
    return TableData_prj;
  }
  if (issubproject == "yes") {
    form_data.append('subprojectlist', Authsubprj);
  } else {
    form_data.append('subprojectlist', '');
  }
  // alert(Authsubprj);
  if (Authsubprj.length <= 10 && issubproject == "yes") {
    alertmessage('error', 'Sub-Project missing, Please enter the sub-project details');
    // $("#"+userid_eid+"").focus();
    return false;
  }

  var orgprojectid = $("#hidden_projectid").val();
  form_data.append('orgprojectid', orgprojectid);

  var btnval = $("#" + savetodb_btnid + "").html();
  $("#" + savetodb_btnid + "").html('Please wait data...');
  $("#" + savetodb_btnid + "").prop('disabled', true);
  var hiddenkey = $("#hidden_key").val();
  $.ajax
    ({
      type: "POST",
      url: rooturl + "modal/admindashboard_modal.php?action=addupdateprojectdetails",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        // console.log(res);
        $("#" + savetodb_btnid + "").html(btnval);
        $("#" + savetodb_btnid + "").prop('disabled', false);
        if (res.length > 0) {
          for (var i = 0; i < res.length; i++) {
            if (res[i].status == "Success") {
              alertmessage('success', res[i].message);
              window.location.href = rooturl + '/adminmodule/project-new.php?k=' + hiddenkey + '&edprjid=' + res[i].prjid;
            } else {
              alertmessage('error', res[i].message);
              return false;
            }
          }
        }
      }
    });
}

function removeprojectdetails_frommaster(baseurl_eid, companyid_eid, subcompanyid_eid, projectidval, savetodbloginname_eid) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();

  var companyid = $("#" + companyid_eid + "").val();
  if (companyid == null || companyid == "undefined" || companyid == "") {
    alertmessage('error', 'Company name missing');
    $("#" + companyid_eid + "").focus();
    return false;
  }

  form_data.append("companyid", companyid);
  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  if (subcompanyid == null || subcompanyid == "undefined" || subcompanyid == "") {
    alertmessage('error', 'Subcompany name missing');
    $("#" + subcompanyid_eid + "").focus();
    return false;
  }
  form_data.append("subcompanyid", subcompanyid);

  form_data.append("projectid", projectidval);

  var loginname = $("#" + savetodbloginname_eid + "").val();
  form_data.append("loginname", loginname);

  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=removeprojectdetailsfrommaster",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        // console.log(res);
        if (res.length > 0) {
          for (var i = 0; i < res.length; i++) {
            if (res[i].status == "Success") {
              // alertmessage('success', res[i].message);
              SweetAlertmessageFadein(res[i].status, res[i].message);

            } else {
              alertmessage('error', res[i].message);
              return false;
            }
          }
        }

      }
    });
}


function savemodulewithgroupforcompany(companyid, subcompanyid, projectid, groupid, moduletblid, savetodbbtnid) {
  var form_data = new FormData();
  var baseurl = $("#hidden_rooturl").val();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("groupid", groupid);
  if (companyid == null || companyid == "undefined" || companyid == "") {
    alertmessage('error', 'Company selection is missing');
    return false;
  } else if (subcompanyid == null || subcompanyid == "undefined" || subcompanyid == "") {
    alertmessage('error', 'Subcompany selection is missing');
    return false;
  } else if (projectid == null || projectid == "undefined" || projectid == "") {
    alertmessage('error', 'Project selection is missing');
    return false;
  } else if (groupid == null || groupid == "undefined" || groupid == "") {
    alertmessage('error', 'Module group selection is missing');
    return false;
  }
  var Authmdg;
  Authmdg = storeTblValues1('#' + moduletblid + '');
  Authmdg = JSON.stringify(Authmdg);
  function storeTblValues1(mytable) {
    var TableData = new Array();
    var mytableid = mytable + ' tr';
    var i = 0;
    $(mytableid).each(function (row, tr) {
      if ($(tr).closest('tr').find('td').find('input[type="checkbox"]').is(":checked")) {
        TableData[i] = {
          "sno": $(tr).closest('tr').data("mdsno"),
        }
        i++;
      }
    });
    // TableData.shift();  // first row will be empty - so remove
    return TableData;
  }
  form_data.append('authmdsno', Authmdg);
  var loginname = $("#hidden_loginname").val();
  form_data.append("loginname", loginname);

  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=savemodulewithgroupforcompany",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        // console.log(res);
        $("#grpproject").prop("selectedIndex", 0);
        $("#mdgrplist").prop("selectedIndex", 0);
        if (res.status == "Success" || res.status == "success") {
          alertmessage(res.status, res.message);
          $("#grpproject").val(projectid).trigger("change");
        } else {
          alertmessage('error', res.message);
          return false;
        }
      }
    });
}

function standingorder_htmltableview(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, viewtableid) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();
  var companyid = $("#" + companyid_eid + "").val();
  form_data.append("companyid", companyid);

  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  form_data.append("subcompanyid", subcompanyid);

  var projectid = $("#" + projectid_eid + "").val();
  form_data.append("projectid", projectid);

  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=standingorder_htmltableview",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'html',
      success: function (res) {
        // console.log(res);
        $("#" + viewtableid + " tbody").html('');
        $("#" + viewtableid + " tbody").html(res);
      }
    });
}

function savetodb_standingorderdetails_tomaster(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, stdordno_eid, stdordname_eid, stdordpurpose_eid, stdordamount_eid, stdordstarddate_eid, stdordtermdays_eid, stdordnextdueddate_eid, stdorddesc_eid, isactive_inpname, savetodbloginname_eid, savetodb_btnid, taxamount, rmks, taxtype, currencyid) {
  var stdordid = $("#" + stdordname_eid + "").val();

  //var customerid = $("#"+stdordname_eid+"").val();  

  var stdordname = $("#" + stdordname_eid + "").find("option:selected").text();
  var purposename = $("#" + stdordpurpose_eid + "").find("option:selected").text();
  var stdordpurpose = $("#" + stdordpurpose_eid + "").val();
  var stdordstarddate = $("#" + stdordstarddate_eid + "").val();
  var stdordtermdays = $("#" + stdordtermdays_eid + "").val();
  var stdordnextdueddate = $("#" + stdordnextdueddate_eid + "").val();
  var stdorddesc = $("#" + stdorddesc_eid + "").val().trim();
  var stdordamount = $("#" + stdordamount_eid + "").val();
  var taxamount = $("#" + taxamount + "").val() || 0;

  var currencyid = $("#" + currencyid + "").val();

  var rmks = $("#" + rmks + "").val().trim();
  var stdordnextdueddate = $("#" + stdordnextdueddate_eid + "").val();
  if (stdordid == null || stdordid == "undefined" || stdordid == "") {
    alert("Select Customer Name");
    //alertmessage('error', 'Standing order name missing');
    $("#" + stdordname_eid + "").focus();
    return false;
  }

  if (stdordname == null || stdordname == "undefined" || stdordname == "") {
    alert("Select Customer Name");
    //alertmessage('error', 'Standing order name missing');
    $("#" + stdordname_eid + "").focus();
    return false;
  }
  if (stdordpurpose == null || stdordpurpose == "undefined" || stdordpurpose == "") {
    alert("Select Standing order purpose");
    //alertmessage('error', 'Standing order purpose missing');
    // $("#"+stdordpurpose_eid+"").focus();
    return false;
  }
  if (stdordamount == null || stdordamount == "undefined" || stdordamount == "" || parseFloat(stdordamount) <= 0) {

    alert("Standing order amount missing");
    // alertmessage('error', 'Standing order amount missing');  
    $("#" + stdordamount_eid + "").focus();
    return false;
  }
  if (currencyid == null || currencyid == "undefined" || currencyid == "" || parseFloat(currencyid) <= 0) {

    alert("select Currency");
    // alertmessage('error', 'Standing order amount missing');  
    $("#" + currencyid + "").focus();
    return false;
  }


  if (stdordstarddate == null || stdordstarddate == "undefined" || stdordstarddate == "") {
    alert('Standing order start date missing');
    //alertmessage('error', 'Standing order start date missing');
    $("#" + stdordstarddate_eid + "").focus();
    return false;
  }
  if (stdordtermdays == null || stdordtermdays == "undefined" || stdordtermdays == "") {
    alert('Standing order term days missing');
    //alertmessage('error', 'Standing order term days missing');
    $("#" + stdordtermdays_eid + "").focus();
    return false;
  }
  if (stdordnextdueddate == null || stdordnextdueddate == "undefined" || stdordnextdueddate == "") {
    alert('Standing order next due-date missing');
    //alertmessage('error', 'Standing order next due-date missing');
    $("#" + stdordnextdueddate_eid + "").focus();
    return false;
  }
  if (stdorddesc == null || stdorddesc == "undefined" || stdorddesc == "") {
    alert('Standing order description missing');
    //alertmessage('error', 'Standing order description missing');
    $("#" + stdorddesc_eid + "").focus();
    return false;
  }
  vatpercentage = 0;
  gstpercentage = 0;
  if (parseFloat(taxamount) > 0) {
    if (taxtype == "VAT") {
      vatpercentage = ((parseFloat(taxamount) / parseFloat(stdordamount)) * 100).toFixed(2);

    } else {
      gstpercentage = ((parseFloat(taxamount) / parseFloat(stdordamount)) * 100).toFixed(2);


    }

  }
  var isactivestatus = $("input[name='" + isactive_inpname + "']:checked").val() || "";
  var savebyname = $("#" + savetodbloginname_eid + "").val() || "";
  var baseurl = $("#" + baseurl_eid + "").val();
  var companyid = $("#" + companyid_eid + "").val();
  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  var projectid = $("#" + projectid_eid + "").val();
  var stdordno = $("#" + stdordno_eid + "").val();
  var btnval = $("#" + savetodb_btnid + "").html();
  var form_data = new FormData();
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  form_data.append("stdordno", stdordno);
  form_data.append("stdordid", stdordid);
  form_data.append("stdordname", stdordname);
  form_data.append("purposename", purposename);
  form_data.append("stdordpurpose", stdordpurpose);
  form_data.append("stdordamount", stdordamount);
  form_data.append("stdordstarddate", stdordstarddate);
  form_data.append("stdordtermdays", stdordtermdays);
  form_data.append("stdordnextdueddate", stdordnextdueddate);
  form_data.append("stdorddesc", stdorddesc);
  form_data.append("isactivestatus", isactivestatus);
  form_data.append("savebyname", savebyname);
  form_data.append("taxamount", taxamount);
  form_data.append("remarks", rmks);
  form_data.append("vatpercentage", vatpercentage);
  form_data.append("gstpercentage", gstpercentage);
  form_data.append("currencyid", currencyid);
  $("#" + savetodb_btnid + "").html('Please wait data...');
  $("#" + savetodb_btnid + "").prop('disabled', true);
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=addupdate_standingorder",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        // console.log(res);
        $("#" + savetodb_btnid + "").html(btnval);
        $("#" + savetodb_btnid + "").prop('disabled', false);
        if (res.length > 0) {
          for (var i = 0; i < res.length; i++) {
            if (res[i].status == "Success") {
              SweetAlertmessageFadein(res[i].status, res[i].message);
              // var unitqty=1;
              // var gstinpercentag=0;
              // var txtsgst=0;
              // var txtcgst=0;
              // var txtigst=0;
              // var txttotalgstamt=0;
              // var finalinvoiceamt=stdordamount;

              // createsimpleinvoice(baseurl,companyid,subcompanyid,projectid,stdordstarddate,stdordnextdueddate,stdordid,stdordname,stdordpurpose,purposename,stdordamount,unitqty,stdordamount,gstinpercentag,txtsgst,txtcgst,txtigst,txttotalgstamt,finalinvoiceamt,companyid_eid)
            } else {
              alertmessage('error', res[i].message);
              return false;
            }
          }
        }

      }
    });
}
function savetodb_foregin_exchange_service(baseurl_eid, companyid_eid, subcompanyid_eid, projectid_eid, bankaccountname_eid, categoryofservice_eid, hsnnumber_eid, billnumber_eid, currency_eid, billamount_eid, invoicenumberinforeign_eid, paymentstatus_eid, eventdate_eid, clientmaster_eid, additionalinfo_eid, additionalinfo2_eid, cgstwithdrawnamount_eid, sgstwithdrawnamount_eid, bankchargeinrupees_eid, bankaccountname2_eid, creditamountinforeign_eid, exchangerate_eid, amountcreditinindianrupes_eid, pdffileas_eid, advoiceno_eid) {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();
  var companyid = $("#" + companyid_eid + "").val();
  var subcompanyid = $("#" + subcompanyid_eid + "").val();
  var projectid = $("#" + projectid_eid + "").val();
  form_data.append("baseurl", baseurl);
  form_data.append("companyid", companyid);
  form_data.append("subcompanyid", subcompanyid);
  form_data.append("projectid", projectid);
  var bankaccountname = $("#" + bankaccountname_eid + " ").find("option:selected").text();
  var bankaccountname_id = $("#" + bankaccountname_eid + "").val();

  if (bankaccountname_id == "" || bankaccountname_id == null) {
    alert("Received in Bank can't empty");
    $("#" + bankaccountname_eid + "").focus();
    return false;
  }
  var categoryofservice = $("#" + categoryofservice_eid + "").val();
  if (categoryofservice == "") {
    alert("Category of Service can't empty");
    $("#" + categoryofservice_eid + "").focus();
    return false;
  }
  var hsnnumber = $("#" + hsnnumber_eid + "").val();
  if (hsnnumber == "") {
    alert("CHSN number can't empty");
    $("#" + hsnnumber_eid + "").focus();
    return false;
  }
  var billnumber = $("#" + billnumber_eid + "").val();
  if (billnumber == "") {
    alert("Bill number Or Remittance ID can't empty");
    $("#" + billnumber_eid + "").focus();
    return false;
  }
  var currency = $("#" + currency_eid + " ").find("option:selected").text();
  if (currency == "" || currency == null) {
    alert("Currency can't empty");
    $("#" + currency_eid + "").focus();
    return false;
  }
  var billamount = $("#" + billamount_eid + "").val();
  if (billamount == "") {
    alert("Bill Amount can't empty");
    $("#" + billamount_eid + "").focus();
    return false;
  }
  var invoicenumberinforeign = $("#" + invoicenumberinforeign_eid + "").val();
  if (invoicenumberinforeign == "") {
    alert("Invoice number in foreign currency can't empty");
    $("#" + invoicenumberinforeign_eid + "").focus();
    return false;
  }
  var paymentstatus = $("#" + paymentstatus_eid + "").val();
  if (paymentstatus == "" || paymentstatus == null) {
    alert("Payment Status can't empty");
    $("#" + paymentstatus_eid + "").focus();
    return false;
  }
  var eventdate = $("#" + eventdate_eid + "").val();
  if (eventdate == "") {
    alert("Date can't empty");
    $("#" + eventdate_eid + "").focus();
    return false;
  }
  var clientmaster = $("#" + clientmaster_eid + " ").find("option:selected").text();
  if (clientmaster == "" || clientmaster == null) {
    alert("Date can't empty");
    $("#" + clientmaster_eid + "").focus();
    return false;
  }
  var additionalinfo = $("#" + additionalinfo_eid + "").val();
  if (additionalinfo == "") {
    alert("Additional information can't empty");
    $("#" + additionalinfo_eid + "").focus();
    return false;
  }
  var additionalinfo2 = $("#" + additionalinfo2_eid + "").val();
  var cgstwithdrawnamount = $("#" + cgstwithdrawnamount_eid + "").val();
  if (cgstwithdrawnamount == "") {
    alert("CGST withdrawn Amount can't empty");
    $("#" + cgstwithdrawnamount_eid + "").focus();
    return false;
  }
  var sgstwithdrawnamount = $("#" + sgstwithdrawnamount_eid + "").val();
  if (sgstwithdrawnamount == "") {
    alert("SGST Withdrawn Amount can't empty");
    $("#" + sgstwithdrawnamount_eid + "").focus();
    return false;
  }
  var bankchargeinrupees = $("#" + bankchargeinrupees_eid + "").val();
  if (bankchargeinrupees == "") {
    alert("Bank Charges in Rupees can't empty");
    $("#" + bankchargeinrupees_eid + "").focus();
    return false;
  }
  var bankaccountname2_id = $("#" + bankaccountname2_eid + "").val();
  if (bankaccountname2_id == "" || bankaccountname2_id == null) {
    alert("Withdrawn from Bank Account can't empty");
    $("#" + bankaccountname2_eid + "").focus();
    return false;
  }
  var bankaccountname2 = $("#" + bankaccountname2_eid + " ").find("option:selected").text();
  var creditamountinforeign = $("#" + creditamountinforeign_eid + "").val();
  if (creditamountinforeign == "") {
    alert("Credit Amount in foreign currency can't empty");
    $("#" + creditamountinforeign_eid + "").focus();
    return false;
  }
  var exchangerate = $("#" + exchangerate_eid + "").val();
  if (exchangerate == "") {
    alert("Exchange Rate can't empty");
    $("#" + exchangerate_eid + "").focus();
    return false;
  }
  var amountcreditinindianrupes = $("#" + amountcreditinindianrupes_eid + "").val();
  if (amountcreditinindianrupes == "") {
    alert("Amount Credited in Indian Rupees can't empty");
    $("#" + amountcreditinindianrupes_eid + "").focus();
    return false;
  }


  const myArray = bankaccountname_id.split("|");
  const myArray2 = bankaccountname2_id.split("|");
  bankaccountname_id = myArray[0];
  bankaccountname2_id = myArray2[0];

  form_data.append("editadvoiceno", $("#" + advoiceno_eid + "").val());
  form_data.append("pdffiles", document.getElementById('pdffileas').files[0]);
  form_data.append("bankaccountname", bankaccountname);
  form_data.append("bankaccountname_id", bankaccountname_id);
  form_data.append("categoryofservice", categoryofservice);
  form_data.append("hsnnumber", hsnnumber);
  form_data.append("billnumber", billnumber);
  form_data.append("currency", currency);
  form_data.append("billamount", billamount);
  form_data.append("invoicenumberinforeign", invoicenumberinforeign);
  form_data.append("paymentstatus", paymentstatus);
  form_data.append("eventdate", eventdate);
  form_data.append("clientmaster", clientmaster);
  form_data.append("additionalinfo", additionalinfo);
  form_data.append("additionalinfo2", additionalinfo2);
  form_data.append("cgstwithdrawnamount", cgstwithdrawnamount);
  form_data.append("sgstwithdrawnamount", sgstwithdrawnamount);
  form_data.append("bankchargeinrupees", bankchargeinrupees);
  form_data.append("bankaccountname2", bankaccountname2);
  form_data.append("bankaccountname2_id", bankaccountname2_id);
  form_data.append("creditamountinforeign", creditamountinforeign);
  form_data.append("exchangerate", exchangerate);
  form_data.append("amountcreditinindianrupes", amountcreditinindianrupes);


  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=savetodbforeginexchangeservice",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {

        if (res.length > 0) {
          for (var i = 0; i < res.length; i++) {
            if (res[i].status == "Success") {
              SweetAlertmessageFadein(res[i].status, res[i].message);

            } else {
              alertmessage('error', res[i].message);
              return false;
            }
          }
        }

      }
    });
}

function department_html(baseurl_eid, controlid, valueexists = "") {
  var form_data = new FormData();
  var baseurl = $("#" + baseurl_eid + "").val();
  $("#" + controlid).empty();
  $("#" + controlid).append("<option value=''>Select Department</option>")
  $.ajax
    ({
      type: "POST",
      url: baseurl + "modal/admindashboard_modal.php?action=getdepartments",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        if (res.length > 0) {
          for (var i = 0; i < res.length; i++) {
            $("#" + controlid).append("<option value='" + res[i].id + "'>" + res[i].departmentname + "</option>");
          }
          if (valueexists) {

            $("#" + controlid).val(valueexists).trigger("change");
          }
        }
      }
    });
}
function createsimpleinvoice(baseurl_eid, companyid, subcompanyid, projectid, invoicedate, duedate, clientnameid, clientname, itemnameid, itemname, txttotalamt, unitqty, itmrateperqty, gstinpercentag, txtsgst, txtcgst, txtigst, txttotalgstamt, finalinvoiceamt, companyid_eid) {
  var form_data = new FormData();
  var invoiceno = "";
  getautogenrate_receivableinvoiceno("", companyid, subcompanyid, projectid, 'invoiceno', companyid_eid, function (res) {
    if (res != "") {
      invoiceno = res;
      var typeofinvoie = "finalinvoice";

      form_data.append('companyid', companyid);
      form_data.append('subcompanyid', subcompanyid);
      form_data.append('projectid', projectid);
      form_data.append('invoicedate', invoicedate);
      form_data.append('duedate', duedate);
      form_data.append('clientid', clientnameid);
      form_data.append('itemid', "");
      form_data.append('txttotalamt', txttotalamt);
      form_data.append('txtsgst', txtsgst);
      form_data.append('txtcgst', txtcgst);
      form_data.append('txtigst', txtigst);
      form_data.append('txttotalgstamt', txttotalgstamt);
      form_data.append('finalinvoiceamt', finalinvoiceamt);
      form_data.append('typeofinvoie', typeofinvoie);
      form_data.append('invoiceno', invoiceno);
      form_data.append('clientname', clientname);

      form_data.append('unitqty', unitqty);
      form_data.append('unitqtyprice', 0);
      form_data.append('gstinpercentag', 0);
      form_data.append('gstselection', "");

      form_data.append('catid', "");
      form_data.append('catname', itemname);
      form_data.append('itemid', itemnameid);
      form_data.append('itemname', itemname);
      form_data.append('unitcount', unitqty);
      form_data.append('unitprice', txttotalamt);
      form_data.append('descountpercentag', 0);
      form_data.append('descount', 0);
      form_data.append('unitvat', 0);
      form_data.append('unitvatamount', 0);
      form_data.append('amountwithvat', txttotalamt);
      form_data.append('availableqty', 0);
      $.ajax
        ({
          type: "POST",
          url: baseurl + "../createinvoice/modal/savetodb_invoices.php?action=SIN_Simple",
          data: form_data,
          contentType: false,
          processData: false,
          cache: false,
          dataType: 'JSON',
          success: function (res) {
            // console.log(res);

            if (res.length > 0) {

              if (res[0].status == 'Success') {
                alert(res[0].message);

                Simpleinvoiceinpdf(typeofinvoie, companyid, subcompanyid, projectid, res[0].id, res[0].invoiceno, "sgst");


              } else {
                alert(res[0].message);
              }
            }
          }
        });

    }
  });


}
function getautogenrate_receivableinvoiceno(countryid, companyid, subcompanyid, projectid, inputid, companyid_eid, callback) {
  var invoiceno = "";
  $.ajax({
    type: "POST",
    url: baseurl + "../createinvoice/modal/savetodb_invoices.php?action=getauto_receivableinvoiceno",
    data: { 'countryid': countryid, 'companyid': companyid, 'subcompanyid': subcompanyid, 'projectid': projectid },
    dataType: 'json',
    success: function (res) {
      // console.log(res);
      // $("#"+inputid+"").val(res);
      callback(res);

    }
  });
}

function Simpleinvoiceinpdf(typeofinvoie, companyid, subcompanyid, projectid, id, invoiceno, gstselection) {
  var form_data = new FormData();
  form_data.append('typeofinvoie', typeofinvoie);
  form_data.append('companyid', companyid);
  form_data.append('subcompanyid', subcompanyid);
  form_data.append('projectid', projectid);
  form_data.append('id', id);
  form_data.append('invoiceno', invoiceno);
  form_data.append('gstselection', gstselection);
  $.ajax
    ({
      type: "POST",
      url: baseurl + "../createinvoice/invoicesimple_pdf.php?action=PDF_Simple",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType: 'JSON',
      success: function (res) {
        //console.log(res);
        if (res) {

          window.open(res.filename, '_blank');
          location.reload();
        }

      }
    });
}

