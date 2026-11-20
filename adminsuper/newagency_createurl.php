<?php
include('../header-registration.php');
?>

<?php
include('../footer.php');
?>
<script type="text/javascript">
    openModalForAddAgency('hidden_rooturl');  
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