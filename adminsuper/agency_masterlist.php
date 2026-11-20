<?php
include('../header.php');
$mdid ="";
if(isset($_GET['mdid'])){
	$mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
if(count($dddr)>0){
	$groupid = $dddr[0]['groupid'];
}
?>
<div class="main-container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center ">Agency Master</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
            <a class="btn btn-deepblue btn-sm" href="<?php echo $rooturl; ?>admin_dashboard.php?k=<?php echo $_GET['k']; ?>&grpid=<?php echo $groupid; ?>">Back to Home</a>
        </div>
  		<div class="col-md-10">
            <button class="btn btn-info m-2 btn-sm float-right" onclick="openModalForAddAgency('hidden_rooturl');"><i class='fa fa-plus' style='font-size: 18px;'></i> Add New Agency </button>
            <button class="btn btn-success btn-sm m-2 pull-right" id="reloadaccountlist"><i class="fa fa-sync"></i> Refresh</button>
        </div>
    </div>
   <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table id="agencymasterlist_tbl" class="table table-bordered table-light table-striped">
      				<thead>
      					<tr>
      						<th style="width: 50px; text-align: center;">S.No</th>
                            <th>Agency Name</th>
                            <th>Responsible Person Name</th>
                            <th>Mobile No</th>
                            <th>WhatsApp No</th>
                            <th>Email Address</th>
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
   agencymasterlist_callback('hidden_rooturl','all','all',function(res){
        // console.log(res);
        $("#agencymasterlist_tbl tbody").empty();
        if(res.length>0){
          for (var i = 0; i < res.length; i++) {
            $("#agencymasterlist_tbl tbody").append("<tr data-sno='"+res[i].sno+"' data-agencyid='"+res[i].agencyid+"' data-agencyname='"+res[i].agencyname+"'><td style='width: 50px; text-align: center;'>"+(i + 1)+"</td><td class='ed_agencyname'>"+res[i].agencyname+"</td><td class='ed_responsibleperson'>"+res[i].responsibleperson+"</td><td class='ed_mobileno'>"+res[i].mobileno+"</td><td class='ed_whatsapno'>"+res[i].whatsapno+"</td><td class='ed_emailaddress'>"+res[i].emailaddress+"</td><td class='agyedit_td text-center d-none'><i class='fa fa-edit editagency' style='font-size: 28px; margin:4px; color:rgb(144, 12, 63); cursor:pointer;'></i> <i class='fa fa-trash removeagency' style='font-size: 28px; margin:4px; color:rgb(144, 12, 32); cursor:pointer;'></i></td></tr>");
          }
        }else{
            $("#agencymasterlist_tbl tbody").append("<tr><td colspan='7' class='text-center'>Data not available</td></tr>");
        }
    });

    $(document).on("click", "#reloadaccountlist", function(){
        agencymasterlist_callback('hidden_rooturl','all','all',function(res){
            // console.log(res);
            $("#agencymasterlist_tbl tbody").empty();
            if(res.length>0){
              for (var i = 0; i < res.length; i++) {
                $("#agencymasterlist_tbl tbody").append("<tr data-sno='"+res[i].sno+"' data-agencyid='"+res[i].agencyid+"' data-agencyname='"+res[i].agencyname+"'><td style='width: 50px; text-align: center;'>"+(i + 1)+"</td><td class='ed_agencyname'>"+res[i].agencyname+"</td><td class='ed_responsibleperson'>"+res[i].responsibleperson+"</td><td class='ed_mobileno'>"+res[i].mobileno+"</td><td class='ed_whatsapno'>"+res[i].whatsapno+"</td><td class='ed_emailaddress'>"+res[i].emailaddress+"</td><td class='agyedit_td text-center d-none'><i class='fa fa-edit editagency' style='font-size: 28px; margin:4px; color:rgb(144, 12, 63); cursor:pointer;'></i> <i class='fa fa-trash removeagency' style='font-size: 28px; margin:4px; color:rgb(144, 12, 32); cursor:pointer;'></i></td></tr>");
              }
            }else{
                $("#agencymasterlist_tbl tbody").append("<tr><td colspan='7' class='text-center'>Data not available</td></tr>");
            }
        });
    });

    // $(document).on("click", ".editagency", function(){
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

    // $(document).on("click", ".removeagency", function(){
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