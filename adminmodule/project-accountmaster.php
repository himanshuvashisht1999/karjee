<?php
include('../header.php');
$prj_projectname = "";
$prjj=projectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
if(count($prjj)>0){
    foreach ($prjj as $lgkey) {
        $prj_sno = $lgkey['sno'];
        $companyid = $lgkey['companyid'];
        $subcompanyid = $lgkey['subcompanyid'];
        $projectid = $lgkey['projectid'];
        $prj_projectname = $lgkey['projectname'];
    }
}
?>
<input type="hidden" id="editprojectid" value="<?php echo $projectid; ?>" />
<div class="main-container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center ">Account(s) Master</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
            <button class="btn btn-deepblue btn-sm" onclick="history.back()">Back</button>
        </div>
  		<div class="col-md-10">
            <!-- <button class="btn btn-info m-2 btn-sm float-right addbankaccount_chk" id="addnewacc_btn"><i class='fa fa-plus' style='font-size: 18px;'></i> New Account</button> -->

            <button class="btn btn-info m-2 btn-sm float-right" onclick="openModalForAddNewBank('hidden_rooturl');"><i class='fa fa-plus' style='font-size: 18px;'></i> Add New Account </button>

            <button class="btn btn-success btn-sm m-2 pull-right" id="reloadaccountlist"><i class="fa fa-sync"></i> Refresh</button>
        </div>
    </div>
   <div class="row">
        <div class="col-md-12">
            <div class="table-responsive bg-light">
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
                            <th style="width: 100px; text-align: center; display:none;">Action</th>
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
    var rooturl = $("#hidden_rooturl").val();
    bankaccountlistwithcallback_frmmaster(rooturl,"<?php echo $companyid; ?>","<?php echo $subcompanyid; ?>","<?php echo $projectid; ?>",function(res){
        if(res.length>0){
          for (var i = 0; i < res.length; i++) {
            $("#projectaccountlist_tbl tbody").append("<tr><td style='width: 50px; text-align: center;'>"+(i + 1)+"</td><td class='ed_accountname'>"+res[i].accountname+"</td><td class='ed_bankname'>"+res[i].bankname+"</td><td class='ed_accountno'>"+res[i].accountno+"</td><td class='ed_ifsccode'>"+res[i].ifsccode+"</td><td class='ed_accounttype'>"+res[i].account_type+"</td><td class='ed_branchname'>"+res[i].branchname+"</td><td class='ed_branchaddress'>"+res[i].branchaddress+"</td><td class='accedit_td text-center d-none'><i class='fa fa-edit editaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 63); cursor:pointer;'></i> <i class='fa fa-trash removeaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 32); cursor:pointer;'></i><input type='hidden' class='ed_dbsno' value='"+res[i].sno+"' /></td></tr>");
          }
        }else{
            $("#projectaccountlist_tbl tbody").append("<tr><td colspan='9' class='text-center'>Data not available add new bank details of project</td></tr>");
        }
    });

    $(document).on("click", "#reloadaccountlist", function(){
        bankaccountlistwithcallback_frmmaster(rooturl,"<?php echo $companyid; ?>","<?php echo $subcompanyid; ?>","<?php echo $projectid; ?>",function(res){
            $("#projectaccountlist_tbl tbody").empty();
            if(res.length>0){
              for (var i = 0; i < res.length; i++) {
                $("#projectaccountlist_tbl tbody").append("<tr><td style='width: 50px; text-align: center;'>"+(i + 1)+"</td><td class='ed_accountname'>"+res[i].accountname+"</td><td class='ed_bankname'>"+res[i].bankname+"</td><td class='ed_accountno'>"+res[i].accountno+"</td><td class='ed_ifsccode'>"+res[i].ifsccode+"</td><td class='ed_accounttype'>"+res[i].account_type+"</td><td class='ed_branchname'>"+res[i].branchname+"</td><td class='ed_branchaddress'>"+res[i].branchaddress+"</td><td class='accedit_td text-center d-none'><i class='fa fa-edit editaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 63); cursor:pointer;'></i> <i class='fa fa-trash removeaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 32); cursor:pointer;'></i><input type='hidden' class='ed_dbsno' value='"+res[i].sno+"' /></td></tr>");
              }
            }else{
                $("#projectaccountlist_tbl tbody").append("<tr><td colspan='8' class='text-center'>Data not available add new bank details of project</td></tr>");
            }
        });
    });

    $(document).on("click", ".editaccinfo", function(){
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
        
        $("#nmd_accountname").val(ed_accountname);
        $("#nmd_bannkname").val(ed_bankname);
        $("#nmd_bannkaccountno").val(ed_accountno);
        $("#nmd_bannkaccountno").data("data_dbsno", ed_dbsno);
        $("#nmd_bankifsccode").val(ed_ifsccode);
        $("#nmd_branchname").val(ed_branchname);
        $("#nmd_branchaddress").val(ed_branchaddress);
        $("input[name='newbanktype'][value='"+ed_accounttype+"']").prop("checked","checked");
        // alert(ed_accountname+'=='+ed_bankname+'--'+ed_accountno);
    });

    $(document).on("click", ".removeaccinfo", function(){
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
        $("#nmd_accountname").val(ed_accountname);
        $("#nmd_bannkname").val(ed_bankname);
        $("#nmd_bannkaccountno").val(ed_accountno);
        $("#nmd_bannkaccountno").data("data_dbsno", ed_dbsno);
        $("#nmd_bankifsccode").val(ed_ifsccode);
        $("#nmd_branchname").val(ed_branchname);
        $("#nmd_branchaddress").val(ed_branchaddress);
        $("input[name='newbanktype'][value='"+ed_accounttype+"']").prop("checked","checked");
    });
</script>