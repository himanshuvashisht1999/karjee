function GetClientGroupsDetailsFromMaster(callback) {
    const formData = new FormData();
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    var routurl = $("body").data("routurl");
    $.ajax({
        type: "POST",
        url: routurl+"modulesCell/modal/modules_module.php?action=GetClientGroupsDetailsFromMaster",
        data: formData,                 // ✅ You missed this line
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function(res) {
            callback(res);
        },
        error: function(xhr, status, error) {
            console.error("Error fetching module details:", error);
            callback([]); // fallback to empty array or handle accordingly
        }
    });
}

function DeactivClientGroupFromMaster(groupid) {
	const formData = new FormData();
	formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("groupid", groupid);

    var routurl = $("body").data("routurl");
    $.ajax({
        type: "POST",
        url: routurl+"modulesCell/modal/modules_module.php?action=DeactivClientGroupFromMaster",
        data: formData,                 // ✅ You missed this line
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function(res) {
         	if(res.status=="success" || res.status=="Success"){
         		Swal.fire({
				  title: res.message,
				  confirmButtonText: "OK",
				}).then((result) => {
				  if (result.isConfirmed) {
				    location.reload();
				  }
				});
         	}else{
         		Swal.fire({
				  icon: "error",
				  title: "Oops...",
				  text: res.message
				});
         	}
        }
    });
}

function ActivClientGroupFromMaster(groupid) {
	const formData = new FormData();
	formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("groupid", groupid);
    var routurl = $("body").data("routurl");
    $.ajax({
        type: "POST",
        url: routurl+"modulesCell/modal/modules_module.php?action=ActivClientGroupFromMaster",
        data: formData,                 // ✅ You missed this line
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function(res) {
         	if(res.status=="success" || res.status=="Success"){
         		Swal.fire({
				  title: res.message,
				  confirmButtonText: "OK",
				}).then((result) => {
				  if (result.isConfirmed) {
				    location.reload();
				  }
				});
         	}else{
         		Swal.fire({
				  icon: "error",
				  title: "Oops...",
				  text: res.message
				});
         	}
        }
    });
}

function GetClientModulesDetailsFromMaster(callback) {
    const formData = new FormData();
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("projectid", $("body").data("projectid"));

    var routurl = $("body").data("routurl");
    $.ajax({
        type: "POST",
        url: routurl+"modulesCell/modal/modules_module.php?action=GetModulesDetailsFromMaster",
        data: formData,                 // ✅ You missed this line
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function(res) {
            callback(res);
        },
        error: function(xhr, status, error) {
            console.error("Error fetching module details:", error);
            callback([]); // fallback to empty array or handle accordingly
        }
    });
}

function ModulesDetailsforAllocation(callback) {
    const formData = new FormData();
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    var routurl = $("body").data("routurl");
    $.ajax({
        type: "POST",
        url: routurl+"modulesCell/modal/modules_module.php?action=ModulesDetailsforAllocation",
        data: formData,                 // ✅ You missed this line
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function(res) {
            callback(res);
        },
        error: function(xhr, status, error) {
            // console.error("Error fetching module details:", error);
            callback([]); // fallback to empty array or handle accordingly
        }
    });
}

function DeactivClientModulesFromMaster(moduleid) {
	const formData = new FormData();
	formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("moduleid", moduleid);
    var routurl = $("body").data("routurl");
    $.ajax({
        type: "POST",
        url: routurl+"modulesCell/modal/modules_module.php?action=DeactivClientModulesFromMaster",
        data: formData,                 // ✅ You missed this line
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function(res) {
         	if(res.status=="success" || res.status=="Success"){
         		Swal.fire({
				  title: res.message,
				  confirmButtonText: "OK",
				}).then((result) => {
				  if (result.isConfirmed) {
				    location.reload();
				  }
				});
         	}else{
         		Swal.fire({
				  icon: "error",
				  title: "Oops...",
				  text: res.message
				});
         	}
        }
    });
}

function ActivClientModulesFromMaster(moduleid) {
	const formData = new FormData();
	formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("moduleid", moduleid);

    var routurl = $("body").data("routurl");
    $.ajax({
        type: "POST",
        url: routurl+"modulesCell/modal/modules_module.php?action=ActivClientModulesFromMaster",
        data: formData,                 // ✅ You missed this line
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function(res) {
         	if(res.status=="success" || res.status=="Success"){
         		Swal.fire({
				  title: res.message,
				  confirmButtonText: "OK",
				}).then((result) => {
				  if (result.isConfirmed) {
				    location.reload();
				  }
				});
         	}else{
         		Swal.fire({
				  icon: "error",
				  title: "Oops...",
				  text: res.message
				});
         	}
        }
    });
}

function saveAllocationofGroup(groupid_eid, moduletblid) {
    const formData = new FormData();
    const groupid = $("#" + groupid_eid).val();

    if (!groupid || groupid === "undefined") {
        Swal.fire({
            icon: "error",
            title: "Oops...",
            text: "Group selection is missing, please choose the group"
        });
        return false;
    }

    const mdlprj = JSON.stringify(storeTblValues('#' + moduletblid));
    formData.append('modulesid', mdlprj);

    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("groupid", groupid);

    var routurl = $("body").data("routurl");
    $.ajax({
        type: "POST",
        url: routurl+"modulesCell/modal/modules_module.php?action=saveAllocationofGroup",
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function (res) {
            if (res.status.toLowerCase() === "success") {
                Swal.fire({
                    title: res.message,
                    icon: "success",
                    confirmButtonText: "OK"
                }).then(result => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: res.message
                });
            }
        }
    });

    function storeTblValues(tableSelector) {
        const tableData = [];
        $(tableSelector + ' tr').each(function () {
            const $row = $(this);
            const isChecked = $row.find('input.sltmoduleforallocation').is(":checked");

            if (isChecked) {
                const moduleid = $row.data('moduleid');
                if (moduleid) {
                    tableData.push({ "moduleid": moduleid });
                }
            }
        });
        return tableData;
    }
}

function authorizedlistFormodulepassword(moduleid,callback){
    var form_data = new FormData();
    form_data.append("companyid", $("body").data("companyid"));
    form_data.append("subcompanyid", $("body").data("subcompanyid"));
    form_data.append("projectid", $("body").data("projectid"));
    form_data.append("moduleid", moduleid);
    var routurl = $("body").data("routurl");
    $.ajax({
        type: "POST",
        url: routurl+"modulesCell/modal/modules_module.php?action=authorizedlistFormodulepassword",
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

function checkmodulepasswordWithLoginid(mdpasswrod_eid,moduleid,loginid,weburl){
    var form_data = new FormData();    
    form_data.append("companyid", $("body").data("companyid"));
    form_data.append("subcompanyid", $("body").data("subcompanyid"));
    form_data.append("projectid", $("body").data("projectid"));

    var modulepassword = $('#'+mdpasswrod_eid+'').val();
    if(modulepassword=="" || modulepassword=="undefined" || modulepassword==null){
        alertmessage('error', 'Module login password is missing or wrong');
        return false;
    }
    form_data.append("modulepassword", modulepassword);
    form_data.append("moduleid", moduleid);
    form_data.append("loginid", loginid);
    var routurl = $("body").data("routurl");
    $.ajax
    ({
      type: "POST",
      url: routurl+"modulesCell/modal/modules_module.php?action=checkmodulepasswordWithLoginid",
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
          // window.location.reload();
           window.location.href = weburl;
        }else{
          alertmessage('error', res.message);
        }
      }
    });
}

function updatepassword_inmoduleforuser(moduleid,datatable_eid){
    var form_data = new FormData();
    form_data.append("companyid", $("body").data("companyid"));
    form_data.append("subcompanyid", $("body").data("subcompanyid"));
    form_data.append("projectid", $("body").data("projectid"));
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
    var routurl = $("body").data("routurl");
    $.ajax
    ({
      type: "POST",
      url: routurl+"modulesCell/modal/modules_module.php?action=updatepassword_inmoduleforuser",
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
          Swal.fire({
                title: res.message,
                icon: "success",
                confirmButtonText: "OK"
            }).then(result => {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        }else{
          Swal.fire({
                icon: "error",
                title: "Oops...",
                text: res.message
            });
        }
      }
    });
}

function UpdateModulesViewOrderNo(button) {
    const formData = new FormData();
    let $row = $(button).closest("tr");
    let sno = $(button).data("sno");
    let moduleid = $(button).data("moduleid");
    let orderno = $row.find("input.vieworderno").val();
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("dbsno", sno);
    formData.append("vieworderno", orderno);
    var routurl = $("body").data("routurl");
    $.ajax
    ({
      type: "POST",
      url: routurl+"modulesCell/modal/modules_module.php?action=UpdateModulesViewOrderNo",
        data: formData,                 // ✅ You missed this line
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function(res) {
            if(res.status=="success" || res.status=="Success"){
                Swal.fire({
                  position: "top-end",
                  icon: "success",
                  title: res.message,
                  showConfirmButton: false,
                  timer: 500
                });
            }else{
                Swal.fire({
                  icon: "error",
                  title: "Oops...",
                  text: res.message
                });
            }
        }
    });
}

function UpdateGroupViewOrderNo(button) {
    const formData = new FormData();
    let $row = $(button).closest("tr");
    let sno = $(button).data("sno");
    let moduleid = $(button).data("moduleid");
    let orderno = $row.find("input.vieworderno").val();
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("dbsno", sno);
    formData.append("vieworderno", orderno);
    var routurl = $("body").data("routurl");
    $.ajax
    ({
      type: "POST",
      url: routurl+"modulesCell/modal/modules_module.php?action=UpdateGroupViewOrderNo",
        data: formData,                 // ✅ You missed this line
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function(res) {
            if(res.status=="success" || res.status=="Success"){
                Swal.fire({
                  position: "top-end",
                  icon: "success",
                  title: res.message,
                  showConfirmButton: false,
                  timer: 500
                });
            }else{
                Swal.fire({
                  icon: "error",
                  title: "Oops...",
                  text: res.message
                });
            }
        }
    });
}

function UnGroupModule(button){
   const formData = new FormData();
    let $row = $(button).closest("tr");
    let dbsno = $row.data("dbsno");
    if(!dbsno){
        alertmessage('warning', 'Module selection wrong');
        return false;
    }
    let moduleid = $row.data("moduleid");
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("dbsno", dbsno);
    var routurl = $("body").data("routurl");
    $.ajax
    ({
      type: "POST",
      url: routurl+"modulesCell/modal/modules_module.php?action=UnGroupModule",
        data: formData,                 // ✅ You missed this line
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function(res) {
            if(res.status=="success" || res.status=="Success"){
                Swal.fire({
                  position: "top-end",
                  icon: "success",
                  title: res.message,
                  showConfirmButton: false,
                  timer: 500
                });
            }else{
                Swal.fire({
                  icon: "error",
                  title: "Oops...",
                  text: res.message
                });
            }
        }
    });
}

function checkmoduleHaspasswordProtected(moduleid, weburl){
    var form_data = new FormData();
    form_data.append("companyid", $("body").data("companyid"));
    form_data.append("subcompanyid", $("body").data("subcompanyid"));
    form_data.append("projectid", $("body").data("projectid"));
    form_data.append("loginid", $("body").data("loginid"));
    form_data.append("loginroleid", $("body").data("loginroleid"));
    form_data.append("moduleid", moduleid);
    var routurl = $("body").data("routurl");

    $.ajax({
      type: "POST",
      url: routurl+"modulesCell/modal/modules_module.php?action=checkmoduleHaspasswordProtected",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      dataType:'JSON',
      success: function(res) {
        console.log("Module Info:", res);
        // Pass weburl along so modal knows final destination
        checkModulePasswordProtection(res, weburl);
      }
    });
}

function checkModulePasswordProtection(moduleInfo,weburl) {
  // Adjust condition based on actual API response
  if ( moduleInfo.ispasswordprotected === "yes" && moduleInfo.loginstatus == "logout" && moduleInfo.loginroleid>1
  ) {
    const modalHtml = `
      <div class="modal fade" id="modulelogin_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-success">
              <h5 class="modal-title">Module Login</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <label>Module Login Password</label>
              <input type="password" class="form-control" id="moduleloginpassword" />
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-deepblue" onclick="history.back();">BACK</button>
              <button class="btn btn-info"
                onclick="checkmodulepasswordWithLoginid('moduleloginpassword', '${moduleInfo.mdid}', '${moduleInfo.loginid}', '${weburl}')">Login</button>
            </div>
          </div>
        </div>
      </div>
    `;

    // Remove old modal if exists
    document.querySelector("#modulelogin_modal")?.remove();
    // Inject new modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalEl = document.getElementById('modulelogin_modal');
    const myModal = new bootstrap.Modal(modalEl, {
      backdrop: 'static',
      keyboard: false
    });
    modalEl.addEventListener('hidden.bs.modal', function () {
      modalEl.remove();
    });
    myModal.show();
  } else {
    window.location.href = weburl;
    console.log("🔒 Module not password protected OR already logged in");
  }
}


function toSentenceCase(text) {
  if (typeof text !== "string") return "";
  text = text.trim().toLowerCase();
  return text.charAt(0).toUpperCase() + text.slice(1);
}