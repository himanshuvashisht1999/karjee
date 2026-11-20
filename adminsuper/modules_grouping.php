<?php
include('../header.php');
?>
<div class="main-container">
    <div class="row mt-2">
        <div class="col-sm-12 text-center">
            <h2 class="thin ">Grouping Of Modules</h2>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-12">
            <button class="pull-right btn btn-sm btn-success m-2" onclick="groupoftitlehtml('hidden_routurl');">Group Titles</button>
            <button class="btn btn-success" id="addnewgroupnamebtn" onclick="AddGroupName('hidden_rooturl');">Add New Group Name</button>
            <a class="pull-right btn btn-sm btn-success m-2" href="<?php echo $rooturl; ?>adminsuper/modules_grouping_cmp.php?k=<?php echo $key; ?>" >Modules Grouping For Project</a>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="table-responsive bg-light">
                <table class="table table-bordered table-light" id="modulesgrptbl">
                    <thead>
                        <tr>
                            <th colspan="3">Modules Master with Group</th>
                            <th colspan="3"><input type="search" id="filterinputmdwg" class="form-control" oninput="filterTable('#filterinputmdwg', '#modulesgrptbl');" /></th>
                        </tr>
                        <tr>
                            <th>S.No</th>
                            <th>Module Name</th>
                            <th>Redirection Path</th>
                            <th>Group Name</th>
                            <th style="width:150px;">Is Integrated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include('../footer.php');
?>
<script type="text/javascript">
// onlyprojectmoduleswithgrouphtml("hidden_routurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","modulesgrptbl");
moduleswithgrouphtml("hidden_routurl","modulesgrptbl");



$(document).on("click", ".addblackrowmodule", function () {
    const $currentRow = $(this).closest("tr"); // Get the current row
    const currentGroupId = $currentRow.data("grpid"); // Get current group ID from data attribute

    // Call the function to fetch the group title list
    grouptitlelist_callback("hidden_rooturl", function (res) {
        // Create the dropdown
        let $select = $("<select>", { class: "form-control egroupname" });
        if (res.length > 1) {
            $select.append($("<option>", { value: "", text: "select group", selected: true, disabled: true }));
        }

        res.forEach(ggkey => {
            let $option = $("<option>", {
                value: ggkey.groupid,
                text: ggkey.groupname,
                selected: ggkey.groupid === currentGroupId
            });
            $select.append($option);
        });

        // Insert a new row after the current one
        $currentRow.after(`
            <tr data-grpid="" data-mldid="" data-mldsno="">
                <td></td>
                <td style="background-color:#79d6df;">
                    <input type="text" class="form-control emodulename" value="" />
                </td>
                <td style="background-color:#79d6df;">
                    <input type="text" class="form-control emodulefilepath" value="" />
                </td>
                <td style="background-color:#79d6df;">
                    ${$select.prop("outerHTML")}
                </td>
                <td class="text-center">
                    <label><input type='radio' class='typeofIntegration m-1' name='typeofIntegration' value='integration' checked /> Yes</label>
                    <label><input type='radio' class='typeofIntegration m-1' name='typeofIntegration' value='nonintegration' /> No</label>

                </td>
                <td>
                    <button class='btn btn-sm btn-success addnewmodulebtn'><i class="fa fa-save"></i> SAVE</button>
                </td>
            </tr>
        `);
    });
});

$(document).on("click", ".addnewmodulebtn", function(){
    let moduleid="";
    let groupid = $(this).closest("tr").find("select.egroupname").val();
    let modulename = $(this).closest("tr").find("input.emodulename").val();
    let modulefilepath = $(this).closest("tr").find("input.emodulefilepath").val();
    let integration = $(this).closest("tr").find("input.typeofIntegration:checked").val();
    addupdate_moduleswithgroup(groupid,moduleid,modulename,modulefilepath,'new',integration);
});

$(document).on("click", ".editsavemodule", function(){
    let moduleid=$(this).closest("tr").data("mldid");
    let groupid = $(this).closest("tr").find("select.egroupname").val();
    let modulename = $(this).closest("tr").find("input.emodulename").val();
    let modulefilepath = $(this).closest("tr").find("input.emodulefilepath").val();
    let integration = $(this).closest("tr").find("input.typeofIntegration:checked").val();
    addupdate_moduleswithgroup(groupid,moduleid,modulename,modulefilepath,'edit',integration);
});

$(document).on("click", ".removemodule", function(){
    let moduleid=$(this).closest("tr").data("mldid");
    let groupid = $(this).closest("tr").find("select.egroupname").val();
    let modulename = $(this).closest("tr").find("input.emodulename").val();
    let modulefilepath = $(this).closest("tr").find("input.emodulefilepath").val();
    let integration = $(this).closest("tr").find("input.typeofIntegration:checked").val();
    addupdate_moduleswithgroup(groupid,moduleid,modulename,modulefilepath,'remove',integration);
});

$(document).on("click", ".editmodule", function(){
    $(this).closest("tr").find(".editmodule").hide();
    $(this).closest("tr").find(".addblackrowmodule").hide();
    $(this).closest("tr").find(".removemodule").hide();
    $(this).closest("tr").find(".editsavemodule").show();
    $(this).closest("tr").find(".undomodule").show();
    $(this).closest("tr").find("input").prop("disabled", false);
    $(this).closest("tr").find("select").prop("disabled", false);
});

$(document).on("click", ".undomodule", function(){
    $(this).closest("tr").find(".editmodule").show();
    $(this).closest("tr").find(".addblackrowmodule").show();
    $(this).closest("tr").find(".removemodule").show();
    $(this).closest("tr").find(".editsavemodule").hide();
    $(this).closest("tr").find(".undomodule").hide();
    $(this).closest("tr").find("input").prop("disabled", true);
    $(this).closest("tr").find("select").prop("disabled", true);
});
</script>