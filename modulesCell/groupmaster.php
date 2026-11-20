<?php
include("header.php");
?>
  <div class="row mb-2 py-4">
    <div class="col-md-12 text-center">
      <h4>Group Master</h4>
    </div>
  </div>
  <div class="row mb-2">
    <div class="col-md-12">
      <button class="btn btn-sm btn-primary float-start" data-bs-toggle="modal" data-bs-target="#setmoduleOrder">
        <i class="fa fa-plus"></i> SET GROUP ORDER
      </button>
      <button class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#groupmasterModal">
        <i class="fa fa-plus"></i> NEW GROUP
      </button>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">GROUP Master</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="groupMasterTabl" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>GROUP Name</th>
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

<!-- GROUPING OF MODULE GROUP -->
<div class="modal fade" id="setmoduleOrder" tabindex="-1" aria-labelledby="setmoduleOrderLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="setmoduleOrderLabel">Group Order</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <div class="col-md-12">
              <div class="table-responsive">
                <table class="table" id="setGroupordertbl">
                  <thead>
                    <tr>
                      <th>Group Name</th>
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

<!-- Modal for Module Form -->
<div class="modal fade" id="groupmasterModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Group</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="groupName" class="form-label">Group Name</label>
            <label class="form-label">Status</label>
            <input type="text" class="form-control" id="groupName" name="groupName" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" id="savegroupdetailsbtn" data-groupid="" class="btn btn-success">Save Group Name</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
  </div>
</div>
<?php include("footer.php"); ?>
<script>

$(document).on("click", "#savegroupdetailsbtn", function() {
    const formData = new FormData();
    const groupId = $("#savegroupdetailsbtn").data("groupid");
    const groupName = document.getElementById("groupName").value.trim();

    if (!groupName) {
        alert("Group name is required.");
        return;
    }
    formData.append("companyid", $("body").data("companyid"));
    formData.append("subcompanyid", $("body").data("subcompanyid"));
    formData.append("projectid", $("body").data("projectid"));
    formData.append("groupId", groupId);
    formData.append("groupName", groupName);

    $.ajax({
        type: "POST",
        url: "modal/modules_module.php?action=savetodbnewClientGroupdetails",
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
            alert("Something went wrong while saving group.");
        }
    });
});

$(document).on('click', '.editgroup', function() {
  $("#savegroupdetailsbtn").data("groupid", $(this).closest("tr").data('datagroupid'));
  $("#groupName").val($(this).closest("tr").data('datagroupname'));
  $("#groupmasterModal").modal("show");
});

$(document).on('click', '.deactive-group', function() {
  const groupid = $(this).closest("tr").data('datagroupid');
  DeactivClientGroupFromMaster(groupid);
});

$(document).on('click', '.active-group', function() {
  const groupid = $(this).closest("tr").data('datagroupid');
  ActivClientGroupFromMaster(groupid);
});

GetClientGroupsDetailsFromMaster(function(res) {
  $("#groupMasterTabl tbody").empty();
  if (Array.isArray(res) && res.length > 0) {
    let mdtbltr = "";
    let grouptbltr = "";
    for (let i = 0; i < res.length; i++) {
      var statustr = (res[i].status === "active")
      ? `<span class="badge bg-info">${res[i].status}</span>`
      : `<span class="badge bg-danger">${res[i].status}</span>`;

    var actdectbutton = (res[i].status === "active")
      ? `<button class="btn btn-sm btn-danger deactive-group" data-groupid="${res[i].groupid}">Deactive</button>`
      : `<button class="btn btn-sm btn-success active-group" data-groupid="${res[i].groupid}">Active</button>`;

      mdtbltr += `
        <tr data-datagroupid="${res[i].groupid}" data-datagroupname="${res[i].groupname}">
          <td>${res[i].groupname}</td>
        <td>${statustr}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-primary editgroup" data-groupid="${res[i].groupid}">Edit</button>
            ${actdectbutton}
          </td>
        </tr>
      `;

      grouptbltr += `
        <tr data-datagroupid="${res[i].groupid}" data-datagroupname="${res[i].groupname}">
          <td>${res[i].groupname}</td>
        <td>
            <input type="text" 
                   class="form-control vieworderno" 
                   placeholder="Enter view group order no" 
                   value="${res[i].vieworderby ?? ""}">
          </td>
          <td class="text-center">
            <button class="btn btn-sm btn-primary updategrouporder" 
                    data-sno="${res[i].sno}" 
                    data-groupid="${res[i].groupid}" 
                    onclick="UpdateGroupViewOrderNo(this)">
              Update View Order
            </button>
          </td>
        </tr>
      `;

    }
    $("#groupMasterTabl tbody").append(mdtbltr);
    $("#setGroupordertbl tbody").append(grouptbltr);

  } else {
    console.log("No modules found or invalid response.");
  }
});
</script>