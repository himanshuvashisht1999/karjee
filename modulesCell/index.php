<?php
include("header.php");
?>

  <div class="row mb-2 py-4">
    <div class="col-md-12 text-center">
      <h4>Modules Master</h4>
    </div>
  </div>
  <div class="row mb-2">
    <div class="col-md-12">
      <button class="btn btn-sm btn-primary float-start" data-bs-toggle="modal" data-bs-target="#setmoduleOrder">
        <i class="fa fa-plus"></i> SET MODULE ORDER
      </button>
      <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#moduleModal">
        <i class="fa fa-plus"></i> NEW MODULE
      </button>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">Module Master</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-8"></div>
            <div class="col-md-4">
              <input type="search" class="form-control" placeholder="Search...." id="searchModuleFromtbl" oninput="filterTable('#searchModuleFromtbl', '#moduleMasterTabl');">
            </div>
          </div>
          <div class="row mt-2">
            <div class="col-md-12">
              <div class="table-responsive">
                <table id="moduleMasterTabl" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>Module Name</th>
                      <th>Module URL</th>
                      <th class="d-none">Purpose</th>
                      <th>Status</th>
                      <th style="width:150px; text-align: center;">Action</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


<!-- Modal for Module Form -->
<div class="modal fade" id="moduleModal" tabindex="-1" aria-labelledby="moduleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="moduleModalLabel">Add New Module</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-12">
              <label for="moduleName" class="form-label">Module Name</label>
              <input type="text" class="form-control" id="moduleName" name="moduleName" required>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-12">
              <label for="moduleURL" class="form-label">Module URL</label>
              <input type="text" class="form-control" id="moduleURL" name="moduleURL" required>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label">Module Alignment</label><br/>
              <label><input type="radio" name="modulealign" value="left" checked /> Left</label>
              <label><input type="radio" name="modulealign" value="middle" /> Middle</label>
              <label><input type="radio" name="modulealign" value="right" /> Right</label>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label">Module Size</label><br/>
              <label><input type="radio" name="modulesize" value="full" checked /> Full</label>
              <label><input type="radio" name="modulesize" value="half" /> Half</label>
              <label><input type="radio" name="modulesize" value="quarter" /> Quarter</label>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-12">
              <label for="modulePurpose" class="form-label">Module Purpose</label>
              <textarea type="text" class="form-control" rows="3" id="modulePurpose" name="modulePurpose"></textarea>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-12">
              <label for="ispasswordprotected" class="form-label"><input type="checkbox" id="ispasswordprotected" /> Is this password?</label>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" id="savemoduledetailsbtn" data-moduleid="" data-groupid="" class="btn btn-success">Save Module</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
  </div>
</div>

<div class="modal fade" id="setmoduleOrder" tabindex="-1" aria-labelledby="setmoduleOrderLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="setmoduleOrderLabel">Module Order</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <div class="col-md-12">
              <label for="filterwithgrouplist" class="form-label">Filter with Group Name</label>
              <select class="form-control" id="filterwithgrouplist" name="filterwithgrouplist">
                <option value="" selected disabled>Choose the Group Name</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <div class="col-md-12">
              <div class="table-responsive">
                <table class="table" id="setmoduleordertbl">
                  <thead>
                    <tr>
                      <th>Module Name</th>
                      <th>Order No</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>            
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
  </div>
</div>

<?php include("footer.php"); ?>
<script>
$(document).on("click", "#savemoduledetailsbtn", function() {
    const formData = new FormData();
    const moduleId = $("#savemoduledetailsbtn").data("moduleid");
    const groupId = $("#savemoduledetailsbtn").data("groupid");
    const moduleName = document.getElementById("moduleName").value.trim();
    const moduleURL = document.getElementById("moduleURL").value.trim();
    const modulePurpose = document.getElementById("modulePurpose").value.trim();
    const moduleAlign = document.querySelector('input[name="modulealign"]:checked')?.value || null;
    const moduleSize = document.querySelector('input[name="modulesize"]:checked')?.value || null;
    const ispasswordprotected = document.getElementById("ispasswordprotected").checked ? 'yes' : 'no';
    if (!moduleName || !moduleURL) {
        alert("Both fields are required.");
        return;
    }
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("groupId", groupId);
    formData.append("moduleId", moduleId);
    formData.append("moduleName", moduleName);
    formData.append("moduleURL", moduleURL);
    formData.append("modulePurpose", modulePurpose);
    formData.append("moduleAlign", moduleAlign);
    formData.append("moduleSize", moduleSize);
    formData.append("ispasswordprotected", ispasswordprotected);    
    $.ajax({
        type: "POST",
        url: "modal/modules_module.php?action=savetodbnewClientModuledetails",
        data: formData,           // Corrected variable name
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function(res) {
          console.log(res)
          if (res.status === "success") {
              Swal.fire({
                title: res.message,
                confirmButtonText: "OK",
              }).then((result) => {
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
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", error);
            alert("Something went wrong while saving module.");
        }
    });
});

$(document).on('click', '.edit-module', function() {
  $("#savemoduledetailsbtn").data("moduleid", $(this).closest("tr").data('moduleid'));
  $("#savemoduledetailsbtn").data("groupid", $(this).closest("tr").data('groupid'));
  $("#moduleName").val($(this).closest("tr").data('modulename'));
  $("#moduleURL").val($(this).closest("tr").data('modulefoldername'));
  $("#modulePurpose").val($(this).closest("tr").data('purposetext'));
  $('input[name="modulealign"][value="'+$(this).closest("tr").data('modulealign')+'"]').prop('checked', true);
  $('input[name="modulesize"][value="'+$(this).closest("tr").data('modulesize')+'"]').prop('checked', true);
  if($(this).closest("tr").data('ispasswordprotected')=='yes'){
    $("#ispasswordprotected").prop("checked", true);
  }else{
    $("#ispasswordprotected").prop("checked", false);
  }
  $("#moduleModal").modal("show");
});

$(document).on('click', '.deactive-module', function() {
  const moduleid = $(this).closest("tr").data('moduleid');
  DeactivClientModulesFromMaster(moduleid);
});

$(document).on('click', '.active-module', function() {
  const moduleid = $(this).closest("tr").data('moduleid');
  ActivClientModulesFromMaster(moduleid);
});

GetClientModulesDetailsFromMaster(function(res) {
  $("#moduleMasterTabl tbody").empty();
  if (Array.isArray(res) && res.length > 0) {
    let mdtbltr = "";
    let mdordertbltr = "";

    for (let i = 0; i < res.length; i++) {
      var statustr = (res[i].status === "active")
      ? `<span class="badge bg-info">${res[i].status}</span>`
      : `<span class="badge bg-danger">${res[i].status}</span>`;

    var actdectbutton = (res[i].status === "active")
      ? `<button class="btn btn-sm btn-danger deactive-module" data-moduleid="${res[i].moduleid}">Deactive</button>`
      : `<button class="btn btn-sm btn-success active-module" data-moduleid="${res[i].moduleid}">Active</button>`;
      mdtbltr += `
        <tr data-groupid="${res[i].groupid}" data-moduleid="${res[i].moduleid}" data-modulename="${res[i].modulename}" data-modulefoldername="${res[i].modulefoldername}" data-purposetext="${res[i].purposetext}" data-ispasswordprotected="${res[i].ispasswordprotected}" data-modulealign="${res[i].modulealign}" data-modulesize="${res[i].modulesize}" >
          <td>${res[i].modulename} </td>         
          <td>${res[i].modulefoldername}</td>
          <td class="d-none">${res[i].purposetext}</td>
          <td>${statustr}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-primary edit-module" data-moduleid="${res[i].moduleid}">Edit</button>            
            ${actdectbutton}
          </td>
        </tr>
      `;
      mdordertbltr += `
        <tr data-groupid="${res[i].groupid}" 
            data-moduleid="${res[i].moduleid}" 
            data-modulename="${res[i].modulename}">
          
          <td>${res[i].modulename}</td>
          <td>${res[i].modulename}</td>
          
          <td>
            <input type="text" 
                   class="form-control vieworderno" 
                   placeholder="Enter view module order no" 
                   value="${res[i].vieworderby ?? ""}">
          </td>
          
          <td class="text-center">
            <button class="btn btn-sm btn-primary updatemoduleorder" 
                    data-sno="${res[i].sno}" 
                    data-moduleid="${res[i].moduleid}" 
                    onclick="UpdateModulesViewOrderNo(this)">
              Update View Order
            </button>
          </td>
        </tr>`;
    }
    $("#moduleMasterTabl tbody").append(mdtbltr);
    $("#setmoduleordertbl tbody").append(mdordertbltr);
  } else {
    console.log("No modules found or invalid response.");
  }
});

GetClientGroupsDetailsFromMaster(function(res) {
  $("#filterwithgrouplist").empty();
  if (Array.isArray(res) && res.length > 0) {
    let mdtbltr = "";
    if(res.length>0){
      mdtbltr += `<option value="all" selected>All Groups</option>`;
    }
    for (let i = 0; i < res.length; i++) {
      if(res[i].status === "active"){
        mdtbltr += `
          <option data-groupid="${res[i].groupid}" data-groupName="${res[i].groupname}" value="${res[i].groupid}">${res[i].groupname}</option>
        `;
      }
    }
    $("#filterwithgrouplist").append(mdtbltr);
  } else {
    console.log("No modules found or invalid response.");
  }
});

$(document).on("change", "#filterwithgrouplist", function () {
    let groupId = $(this).val();
    $("#setmoduleordertbl tbody tr").each(function () {
        let trgroupid = $(this).data("groupid");
        if (groupId === "all" || groupId == trgroupid) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
});
</script>