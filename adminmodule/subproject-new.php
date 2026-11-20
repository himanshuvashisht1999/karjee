<?php
include('../header.php');
$editprjidid = "";
$prj_projectid = "";
$prj_projectname = "";
$prj_projectemail = "";
$prj_sno = "";
if (isset($_GET['edprjid'])) {
    $btntxt = "UPDATE PROJECT DETAILS";
    $editprjidid = $_GET['edprjid'];
    $prjj = projectdetails($conn, $companyid, $subcompanyid, "projectid", $editprjidid);
    if (count($prjj) > 0) {
        foreach ($prjj as $lgkey) {
            $prj_sno = $lgkey['sno'];
            $prj_companyid = $lgkey['companyid'];
            $prj_subcompanyid = $lgkey['subcompanyid'];
            $prj_projectid = $lgkey['projectid'];
            $prj_projectname = $lgkey['projectname'];
            $prj_projectemail = $lgkey['projectemail'];
        }
    }
} else {
    $btntxt = "SAVE SUB PROJECT DETAILS";
    $prj_companyid = $companyid;
}
?>
<input type="hidden" id="editprojectid" value="<?php echo $prj_projectid; ?>" />
<div class="main-container">
    <div class="row text-center mt-4">
        <div class="col-sm-12">
            <h2 class=" mb0 "><?php echo $companyname; ?> </h2>

        </div>
    </div>
    <div class="row mt-2">
        <div class="col-md-8"></div>
        <div class="col-md-4">
            <a class="btn btn-info pull-right"
                href="<?php echo $baseurl; ?>project_management.php?k=<?php echo $_GET['k']; ?>">Back</a>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-md-6">
            <label class="">What is the company name?</label>
            <select class="form-control" id="prj_companyid"
                onchange="authorizedsubcompanylist_frommaster('hidden_rooturl','<?php echo $loginid; ?>','prj_companyid','<?php echo $prj_subcompanyid; ?>','prj_subcompanyid');"></select>
        </div>
        <div class="col-md-6">
            <label class="">What is the sub-company name?</label>
            <select class="form-control" id="prj_subcompanyid"></select>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-md-6">
            <label class="">Name of Project</label>

            <select class="form-control" id="prj_projectid"></select>

        </div>
        <div class="col-md-6">
            <label class="">Name of Sub Project.</label>
            <input type="text" class="form-control" placeholder="Enter sub project name" id="prj_subprojectname"
                autocomplete="off" />
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-md-6">
            <label class="">Email address of Project.</label>
            <input type="text" class="form-control" placeholder="Enter email address of project" id="prj_emailaddress"
                value="<?php echo $prj_projectemail; ?>" autocomplete="off" />
        </div>
    </div>
    <div class="row mt-2" style="display:none;">
        <div class="col-md-6">
            <p class=""><b>Is this active project?</b></p>
            <label><input type="radio" name="isactiveuser" value="yes" checked /> YES</label>
            <label><input type="radio" name="isactiveuser" value="no" /> NO</label>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-12 text-center">
            <button id="newprojectbtn" class="btn btn-info"><?php echo $btntxt; ?></button>
        </div>
    </div>
    <?php
    if ($prj_projectid) {
        ?>
        <div id="accountsofprj_div mt-4">
            <div class="row mt-4">
                <div class="col-md-12">
                    <h2 class="text-center ">Account(s) of Project : <?php echo $prj_projectname; ?></h2>
                </div>
                <div class="col-md-12">
                    <button class="btn btn-info m-2 btn-sm float-right"
                        onclick="openModalForAddNewBank('hidden_rooturl');"><i class='fa fa-plus'
                            style='font-size: 18px;'></i> Add New Account </button>

                    <table id="projectaccountlist_tbl" class="table table-bordered table-light table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">S.No</th>
                                <th>Account Name</th>
                                <th>Bank Name</th>
                                <th>Bank Account Number</th>
                                <th>Bank IFSC Code</th>
                                <th>Account Type</th>
                                <th>Branch Name</th>
                                <th>Branch Address</th>
                                <th style="width: 100px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php
    } else {
        ?>
        <!-- <label><input type="checkbox" class="addbankaccount_chk" /> Do you want to add new bank account?</label> -->
    <?php
    }
    ?>

    <div class="modal fade" id="addnewaccount_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" style="width: 98%;">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title " id="accheader">NEW BANK ACCOUNT DETAILS ( <span id="accsubheader"
                            class="">For Project : <?php echo $prj_projectname; ?></span> )</h5>
                    <button type="button" class="btn close" data-dismiss="modal" aria-label="Close">X</button>
                </div>
                <div class="modal-body">
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label>Account Name</label>
                            <input type="text" class="form-control" name="accountname" id="accountname"
                                placeholder="Enter account name" />
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label>Bank Name</label>
                            <input type="text" class="form-control" name="bannkname" id="bannkname"
                                placeholder="Enter bank name" />
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label>Bank Account Number</label>
                            <input type="text" class="form-control" name="bannkaccountno" id="bannkaccountno"
                                placeholder="Enter bank account number" />
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label>Account IFSC Code</label>
                            <input type="text" class="form-control" name="bannkaccountifsccode"
                                id="bannkaccountifsccode" placeholder="Enter bank account ifsc code" />
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label>Bank Branch Name</label>
                            <input type="text" class="form-control" name="bannkbranchname" id="bannkbranchname"
                                placeholder="Enter bank branch name" />
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label>Bank Branch Address</label>
                            <input type="text" class="form-control" name="bannkbranchaddress" id="bannkbranchaddress"
                                placeholder="Enter bank branch address" />
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label>Bank Type</label><br />
                            <label><input type="radio" name="newpbanktype" value="Bank" checked /> Bank </label>
                            <label><input type="radio" name="newpbanktype" value="FD" /> FD </label>
                            <label><input type="radio" name="newpbanktype" value="Imprest" /> Imprest </label>
                        </div>
                    </div>
                    <div class="row mt-2" id="isremovediv" style="display: none;">
                        <div class="col-md-12">
                            <label><input type="checkbox" id="isremovecheckbox" /> Sure, You want to remove above
                                account from master list?</label>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12 text-center">
                            <button type="button" class="btn btn-info" id="saveupdateaccountdetails"
                                onclick="savetodbprojectaccountdetails_tomaster('hidden_rooturl','hidden_companyid','hidden_subcompanyid','editprojectid','accountname','bannkname','bannkaccountno','bannkaccountifsccode','bannkbranchname','bannkbranchaddress','newpbanktype','isremovecheckbox','hidden_loginname','saveupdateaccountdetails');">Save
                                Account Details of Bank</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include('../footer.php');
?>
<script type="text/javascript">

    var rooturl = $("#hidden_rooturl").val();
    authorizedcompanylist_frommaster("hidden_rooturl", "<?php echo $loginid; ?>", "<?php echo $prj_companyid; ?>", "prj_companyid");




    bankaccountlistwithcallback_frmmaster(rooturl, "<?php echo $prj_companyid; ?>", "<?php echo $prj_subcompanyid; ?>", "<?php echo $prj_projectid; ?>", function (res) {
        if (res.length > 0) {
            for (var i = 0; i < res.length; i++) {
                $("#projectaccountlist_tbl tbody").append("<tr><td style='width: 50px; text-align: center;'>" + (i + 1) + "</td><td class='ed_accountname'>" + res[i].accountname + "</td><td class='ed_bankname'>" + res[i].bankname + "</td><td class='ed_accountno'>" + res[i].accountno + "</td><td class='ed_ifsccode'>" + res[i].ifsccode + "</td><td class='ed_accounttype'>" + res[i].account_type + "</td><td class='ed_branchname'>" + res[i].branchname + "</td><td class='ed_branchaddress'>" + res[i].branchaddress + "</td><td class='accedit_td text-center'><i class='fa fa-edit editaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 63); cursor:pointer;'></i> <i class='fa fa-trash removeaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 32); cursor:pointer;'></i><input type='hidden' class='ed_dbsno' value='" + res[i].sno + "' /></td></tr>");
            }
        } else {
            $("#projectaccountlist_tbl tbody").append("<tr><td colspan='9' class='text-center'>Data not available add new bank details of project</td></tr>");
        }
    });

    $(document).on("click", ".editaccinfo", function () {
        openModalForAddNewBank('hidden_rooturl');
        $("#isremovediv").hide();
        $("#isremovecheckbox").prop("checked", false);
        var ed_accountname = $(this).closest("tr").find("td.ed_accountname").text();
        var ed_bankname = $(this).closest("tr").find("td.ed_bankname").text();
        var ed_accounttype = $(this).closest("tr").find("td.ed_accounttype").text();
        var ed_accountno = $(this).closest("tr").find("td.ed_accountno").text();
        var ed_ifsccode = $(this).closest("tr").find("td.ed_ifsccode").text();
        var ed_branchname = $(this).closest("tr").find("td.ed_branchname").text();
        var ed_branchaddress = $(this).closest("tr").find("td.ed_branchaddress").text();
        var ed_dbsno = $(this).closest("tr").find("input.ed_dbsno").val();
        // accountname
        // bannkname
        // bannkaccountno
        // bankifsccode
        // ibanno
        // swiftcode
        // sortcode
        // branchname
        // branchaddress

        $("#accountname").val(ed_accountname);
        $("#bannkname").val(ed_bankname);
        $("#bannkaccountno").val(ed_accountno);
        $("#bannkaccountno").data("data_dbsno", ed_dbsno);
        $("#bankifsccode").val(ed_ifsccode);
        $("#branchname").val(ed_branchname);
        $("#branchaddress").val(ed_branchaddress);
        $("input[name='newbanktype'][value='" + ed_accounttype + "']").prop("checked", "checked");
    });

    $(document).on("click", ".removeaccinfo", function () {
        openModalForAddNewBank('hidden_rooturl');
        $("#isremovediv").show();
        $("#isremovecheckbox").prop("checked", true);
        var ed_accountname = $(this).closest("tr").find("td.ed_accountname").text();
        var ed_bankname = $(this).closest("tr").find("td.ed_bankname").text();
        var ed_accounttype = $(this).closest("tr").find("td.ed_accounttype").text();
        var ed_accountno = $(this).closest("tr").find("td.ed_accountno").text();
        var ed_ifsccode = $(this).closest("tr").find("td.ed_ifsccode").text();
        var ed_branchname = $(this).closest("tr").find("td.ed_branchname").text();
        var ed_branchaddress = $(this).closest("tr").find("td.ed_branchaddress").text();
        var ed_dbsno = $(this).closest("tr").find("input.ed_dbsno").val();
        $("#accountname").val(ed_accountname);
        $("#bannkname").val(ed_bankname);
        $("#bannkaccountno").val(ed_accountno);
        $("#bannkaccountno").data("data_dbsno", ed_dbsno);
        $("#bankifsccode").val(ed_ifsccode);
        $("#branchname").val(ed_branchname);
        $("#branchaddress").val(ed_branchaddress);
        $("input[name='newbanktype'][value='" + ed_accounttype + "']").prop("checked", "checked");
    });

    $(document).on("change", "#prj_subcompanyid", function () {
        var companyid = $('#prj_companyid').val();
        var subcompanyid = $('#prj_subcompanyid').val();

        authorizedprojectlist_frommaster(rooturl, companyid, subcompanyid, 'prj_projectid');
    });

    $(document).on("click", "#newprojectbtn", function () {
        if ($('#prj_subprojectname').val() == '') {
            alert('Please enter sub project name.');
            $('#prj_subprojectname').focus();
            return false;
        }
        savetodbsubprojectdetails_tomaster(rooturl, $('#prj_companyid').val(), $('#prj_subcompanyid').val(), $('#prj_projectid').val(), $('#prj_subprojectname').val(), $('#prj_emailaddress').val());

    });

</script>