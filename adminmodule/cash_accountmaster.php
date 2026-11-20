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
<div class="main-container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center ">Cash Account(s) Master</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
            <a class="btn btn-deepblue btn-sm" href="<?php echo $baseurl; ?>index.php?k=<?php echo $_GET['k']; ?>">Back</a>
        </div>
  		<div class="col-md-10">
            <button class="btn btn-info m-2 btn-sm float-right" onclick="openModalForAddCashAccount('hidden_rooturl');"><i class='fa fa-plus' style='font-size: 18px;'></i> Add New Cash Account </button>

            <button class="btn btn-success btn-sm m-2 pull-right" id="reloadaccountlist"><i class="fa fa-sync"></i> Refresh</button>
        </div>
    </div>
   <div class="row">
        <div class="col-md-12">
            <div class="table-responsive bg-light">
                <table id="cashccountlist_tbl" class="table table-bordered table-light table-striped">
      				<thead>
      					<tr>
      						<th style="width: 50px; text-align: center;">S.No</th>
                            <th>Account Name</th>
                            <th>Bank Name</th>
                            <th>Bank Account Number</th>
                            <th>Bank IFSC Code</th>
                            <th class="d-none" style="width: 100px; text-align: center;">Action</th>
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
   cashpaymentauthorized('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','all','all',function(res){
        $("#cashccountlist_tbl tbody").empty();
        if(res.length>0){
          for (var i = 0; i < res.length; i++) {
            $("#cashccountlist_tbl tbody").append("<tr data-impsno='"+res[i].sno+"' data-dtpaytoid='"+res[i].paytoid+"' data-dtppaytoname='"+res[i].paytoname+"' data-dtaccountno='"+res[i].accountno+"'><td style='width: 50px; text-align: center;'>"+(i + 1)+"</td><td class='ed_accountname'>"+res[i].paytoname+"</td><td class='ed_bankname'>"+res[i].bankname+"</td><td class='ed_accountno'>"+res[i].accountno+"</td><td class='ed_ifsccode'>"+res[i].ifsccode+"</td><td class='accedit_td text-center d-none'><i class='fa fa-edit editaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 63); cursor:pointer;'></i> <i class='fa fa-trash removeimpaccount' style='font-size: 28px; margin:4px; color:rgb(144, 12, 32); cursor:pointer;'></i></td></tr>");
          }
        }else{
            $("#cashccountlist_tbl tbody").append("<tr><td colspan='6' class='text-center'>Data not available</td></tr>");
        }
    });

    $(document).on("click", "#reloadaccountlist", function(){
        cashpaymentauthorized('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','all','all',function(res){
            $("#cashccountlist_tbl tbody").empty();
            if(res.length>0){
              for (var i = 0; i < res.length; i++) {
                $("#cashccountlist_tbl tbody").append("<tr data-impsno='"+res[i].sno+"' data-dtpaytoid='"+res[i].paytoid+"' data-dtppaytoname='"+res[i].paytoname+"' data-dtaccountno='"+res[i].accountno+"'><td style='width: 50px; text-align: center;'>"+(i + 1)+"</td><td class='ed_accountname'>"+res[i].paytoname+"</td><td class='ed_bankname'>"+res[i].bankname+"</td><td class='ed_accountno'>"+res[i].accountno+"</td><td class='ed_ifsccode'>"+res[i].ifsccode+"</td><td class='accedit_td text-center d-none'><i class='fa fa-edit editaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 63); cursor:pointer;'></i> <i class='fa fa-trash removeimpaccount' style='font-size: 28px; margin:4px; color:rgb(144, 12, 32); cursor:pointer;'></i></td></tr>");
              }
            }else{
                $("#cashccountlist_tbl tbody").append("<tr><td colspan='6' class='text-center'>Data not available</td></tr>");
            }
        });
    });

    // $(document).on("click", ".editaccinfo", function(){
    //     openModalForAddNewBank('hidden_rooturl');
    //     $("#isremovediv").hide();
    //     $("#isremovecheckbox").prop("checked", false);
    //     var ed_accountname = $(this).closest("tr").find("td.ed_accountname").text();
    //     var ed_bankname = $(this).closest("tr").find("td.ed_bankname").text();
    //     var ed_accounttype = $(this).closest("tr").find("td.ed_accounttype").text();
    //     var ed_accountno = $(this).closest("tr").find("td.ed_accountno").text();
    //     var ed_ifsccode = $(this).closest("tr").find("td.ed_ifsccode").text();
    //     var ed_branchname = $(this).closest("tr").find("td.ed_branchname").text();
    //     var ed_branchaddress = $(this).closest("tr").find("td.ed_branchaddress").text();
    //     var ed_dbsno = $(this).closest("tr").find("input.ed_dbsno").val();
    //     // alert(ed_accountname);
    //     // accountname
    //     // bannkname
    //     // bannkaccountno
    //     // bankifsccode
    //     // ibanno
    //     // swiftcode
    //     // sortcode
    //     // branchname
    //     // branchaddress

    //     $("#addeditbankdiv #accountname").val(ed_accountname);
    //     $("#addeditbankdiv #bannkname").val(ed_bankname);
    //     $("#addeditbankdiv #bannkaccountno").val(ed_accountno);
    //     $("#addeditbankdiv #bannkaccountno").data("data_dbsno", ed_dbsno);
    //     $("#addeditbankdiv #bankifsccode").val(ed_ifsccode);
    //     $("#addeditbankdiv #branchname").val(ed_branchname);
    //     $("#addeditbankdiv #branchaddress").val(ed_branchaddress);
    //     $("#addeditbankdiv input[name='newbanktype'][value='"+ed_accounttype+"']").prop("checked","checked");
    // });

    // $(document).on("click", ".removeimpaccount", function(){
    //      openModalForAddImprestAccount('hidden_rooturl');
    //     $("#isremovediv").show();
    //     $("#isremovecheckbox").prop("checked", true);
    //     var ed_accountname = $(this).closest("tr").find("td.ed_accountname").text();
    //     var ed_bankname = $(this).closest("tr").find("td.ed_bankname").text();
    //     var ed_accounttype = $(this).closest("tr").find("td.ed_accounttype").text();
    //     var ed_accountno = $(this).closest("tr").find("td.ed_accountno").text();
    //     var ed_ifsccode = $(this).closest("tr").find("td.ed_ifsccode").text();
    //     var ed_branchname = $(this).closest("tr").find("td.ed_branchname").text();
    //     var ed_branchaddress = $(this).closest("tr").find("td.ed_branchaddress").text();
    //     var ed_dbsno = $(this).closest("tr").find("input.ed_dbsno").val();
    //     $("#accountname").val(ed_accountname);
    //     $("#bannkname").val(ed_bankname);
    //     $("#bannkaccountno").val(ed_accountno);
    //     $("#bannkaccountno").data("data_dbsno", ed_dbsno);
    //     $("#bankifsccode").val(ed_ifsccode);
    //     $("#branchname").val(ed_branchname);
    //     $("#branchaddress").val(ed_branchaddress);
    //     $("input[name='newbanktype'][value='"+ed_accounttype+"']").prop("checked","checked");
    // });
</script>