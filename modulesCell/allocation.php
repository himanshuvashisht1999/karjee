<?php
include("header.php");
?>
  <div class="row mb-2 py-4">
    <div class="col-md-12 text-center">
      <h4>Modules Allocation</h4>
    </div>
  </div>
  <div class="row mb-3 align-items-end">
    <div class="col-md-8">
      <label for="modulegrouplist" class="form-label">Group Name</label>
      <select class="form-control" id="modulegrouplist" name="modulegrouplist">
        <option value="" selected disabled>Choose the Group Name</option>
      </select>
    </div>
    <div class="col-md-4 text-end">
      <button type="button" class="btn btn-success w-100" id="savetodballocation" onclick="saveAllocationofGroup('modulegrouplist', 'moduleallocationMasterTabl')">
        Save Allocation
      </button>
    </div>
  </div>
  <div class="card">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">Modules for Allocation</h5>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-8"></div>
        <div class="col-md-4">
          <input type="search" class="form-control" placeholder="Search...." id="searchalloModuleFromtbl" oninput="filterTable('#searchalloModuleFromtbl', '#moduleallocationMasterTabl');">
        </div>
      </div>
      <div class="row mt-2">
        <div class="col-md-12">
          <div class="table-responsive">
            <table id="moduleallocationMasterTabl" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th style="width:50px; text-align: center;">Select</th>
                  <th>Module Name</th>
                  <th>Group Name</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
    
<?php include("footer.php"); ?>
<script>
$(document).on("click", "#savemoduledetailsbtn", function() {
    const formData = new FormData();
    const moduleId = $("#savemoduledetailsbtn").data("moduleid");
    const moduleName = document.getElementById("moduleName").value.trim();
    const moduleURL = document.getElementById("moduleURL").value.trim();

    if (!moduleName || !moduleURL) {
        alert("Both fields are required.");
        return;
    }
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("moduleId", moduleId);
    formData.append("moduleName", moduleName);
    formData.append("moduleURL", moduleURL);
    $.ajax({
        type: "POST",
        url: "modal/modules_module.php?action=savetodbnewClientModuledetails",
        data: formData,           // Corrected variable name
        contentType: false,
        processData: false,
        cache: false,
        dataType: "json",
        success: function(res) {
          // console.log(res)
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
GetClientGroupsDetailsFromMaster(function(res) {
  $("#modulegrouplist").empty();
  if (Array.isArray(res) && res.length > 0) {
    let mdtbltr = "";
    if(res.length>0){
      mdtbltr += `<option value="" selected disabled>Choose the Group Name</option>`;
    }
    for (let i = 0; i < res.length; i++) {
      if(res[i].status === "active"){
        mdtbltr += `
          <option data-groupid="${res[i].groupid}" data-groupName="${res[i].groupname}" value="${res[i].groupid}">${res[i].groupname}</option>
        `;
      }
    }
    $("#modulegrouplist").append(mdtbltr);
  } else {
    console.log("No modules found or invalid response.");
  }
});

ModulesDetailsforAllocation(function(res) {
  console.log(res);
  $("#moduleallocationMasterTabl tbody").empty();
  if (Array.isArray(res) && res.length > 0) {
    let mdtbltr = "";
    for (let i = 0; i < res.length; i++) {
      if(res[i].status === "active"){
        mdtbltr += `
          <tr data-dbsno="${res[i].sno}" data-groupid="${res[i].groupid}" data-moduleid="${res[i].moduleid}" data-modulename="${res[i].modulename}" data-modulefoldername="${res[i].modulefoldername}">
            <td><input type="checkbox" class="sltmoduleforallocation" /></td>
            <td>${res[i].modulename}</td>
            <td>${res[i].groupname}</td>
            <td><button class="btn btn-sm btn-danger removegroup" onclick="UnGroupModule(this)"><i class="fa fa-trash"></i> Un-Group</button>
            </td>
          </tr>
        `;
      }
    }
    $("#moduleallocationMasterTabl tbody").append(mdtbltr);
  } else {
    console.log("No modules found or invalid response.");
  }
});
</script>