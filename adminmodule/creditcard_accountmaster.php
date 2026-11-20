<?php
include('../header.php');
?>
<div class="main-container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center ">Credit Card Account(s) Master</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
            <a class="btn btn-deepblue btn-sm" href="<?php echo $baseurl; ?>index.php?k=<?php echo $_GET['k']; ?>">Back</a>
        </div>
  		<div class="col-md-10">
            <button class="btn btn-info m-2 btn-sm float-right" onclick="openModalForAddCreditCard('hidden_rooturl');"><i class='fa fa-plus' style='font-size: 18px;'></i> Add New Credit Card</button>

            <button class="btn btn-success btn-sm m-2 pull-right" id="reloadaccountlist"><i class="fa fa-sync"></i> Refresh</button>
        </div>
    </div>
   <div class="row">
        <div class="col-md-12">
            <div class="table-responsive bg-light">
                <table id="creditcardlist_tbl" class="table table-bordered table-light table-striped">
      				<thead>
      					<tr>
      						<th style="width: 50px; text-align: center;">S.No</th>
                            <th>Bank Name</th>
                            <th>Account Number</th>
                            <th>Card Number</th>
                            <th>Card Type</th>
                            <th>Card Holder Name</th>
                            <th>Expiry</th>
                            <th>Issue To</th>
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
    creditcarddetailsfrommaster('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','allwithprojectid','',function(res){
        $("#creditcardlist_tbl tbody").empty();
        if(res.length>0){
          for (var i = 0; i < res.length; i++) {
            $("#creditcardlist_tbl tbody").append("<tr data-ccsno='"+res[i].sno+"' data-ccid='"+res[i].ccid+"' data-accid='"+res[i].accountid+"' data-ccno='"+res[i].cardnumber+"' data-cctype='"+res[i].cardtype+"' data-cctype='"+res[i].cardtype+"' data-cccvv='"+res[i].cvv+"' data-ccexpm='"+res[i].expirymonth+"' data-ccexpy='"+res[i].expiryyear+"'><td style='width: 50px; text-align: center;'>"+(i + 1)+"</td><td class='ed_ccbankname'>"+res[i].bankname+"</td><td class='ed_ccaccountno'>"+res[i].accountno+"</td><td class='ed_cccardno'>"+res[i].cardnumber+"</td><td class='cctype'>"+res[i].cardtype+"</td><td class='ed_ccholdername'>"+res[i].cardholdername+"</td><td class='ccexpmy'>"+res[i].expirymonth+'/'+res[i].expiryyear+"</td><td class='ed_authbtn'>"+res[i].paytonamebtn+"</td><td class='accedit_td text-center d-none'><i class='fa fa-edit editaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 63); cursor:pointer;'></i> <i class='fa fa-trash removeimpaccount' style='font-size: 28px; margin:4px; color:rgb(144, 12, 32); cursor:pointer;'></i></td></tr>");
          }
        }else{
            $("#creditcardlist_tbl tbody").append("<tr><td colspan='8' class='text-center'>Data not available</td></tr>");
        }
    });

    $(document).on("click", "#reloadaccountlist", function(){
        creditcarddetailsfrommaster('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid','allwithprojectid','',function(res){
            $("#creditcardlist_tbl tbody").empty();
            if(res.length>0){
              for (var i = 0; i < res.length; i++) {
                $("#creditcardlist_tbl tbody").append("<tr data-ccsno='"+res[i].sno+"' data-ccid='"+res[i].ccid+"' data-accid='"+res[i].accountid+"' data-ccno='"+res[i].cardnumber+"' data-cctype='"+res[i].cardtype+"' data-cctype='"+res[i].cardtype+"' data-cccvv='"+res[i].cvv+"' data-ccexpm='"+res[i].expirymonth+"' data-ccexpy='"+res[i].expiryyear+"'><td style='width: 50px; text-align: center;'>"+(i + 1)+"</td><td class='ed_ccbankname'>"+res[i].bankname+"</td><td class='ed_ccaccountno'>"+res[i].accountno+"</td><td class='ed_cccardno'>"+res[i].cardnumber+"</td><td class='cctype'>"+res[i].cardtype+"</td><td class='ed_ccholdername'>"+res[i].cardholdername+"</td><td class='ccexpmy'>"+res[i].expirymonth+'/'+res[i].expiryyear+"</td><td class='ed_authbtn'>"+res[i].paytonamebtn+"</td><td class='accedit_td text-center d-none'><i class='fa fa-edit editaccinfo' style='font-size: 28px; margin:4px; color:rgb(144, 12, 63); cursor:pointer;'></i> <i class='fa fa-trash removeimpaccount' style='font-size: 28px; margin:4px; color:rgb(144, 12, 32); cursor:pointer;'></i></td></tr>");
              }
            }else{
                $("#creditcardlist_tbl tbody").append("<tr><td colspan='8' class='text-center'>Data not available</td></tr>");
            }
        });
    });
  
</script>