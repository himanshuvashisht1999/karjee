<?php
$psodate ="";
$clientname ="";
$mobileno ="";
$emailid ="";
$deliverydate ="";
$psoamount ="";
if(isset($_GET['dz'])){
  require('../connection.php');
  require('../functions/function_master.php');
  include('functions/orders_functions.php');
  $key=$_GET['k'];
  $key_decoded = base64url_decode($key);
  $key_parts = explode("|",$key_decoded);
  $checkkey = base64url_decode($key_parts[count($key_parts)-1]);
  $actkey = "";
  for($i=0;$i<count($key_parts)-1 ;$i++){
    $actkey = $actkey?$actkey."|".$key_parts[$i]:$key_parts[$i];
  }
  if($actkey!=$checkkey){
    echo"<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
    die;
  }
  $loginname ="";
  $loginid = $key_parts[0];
  $loginroleid = $key_parts[1];
  $companyid = $key_parts[2];
  $subcompanyid = $key_parts[3];
  $projectid = $key_parts[4];
  $logintype = $key_parts[5];
  $newkey = "x|x|".$key_parts[2]."|".$key_parts[3]."|".$key_parts[4]."|x";
  $encodednewkey = base64url_encode($newkey);
  $keycompany=base64url_encode($newkey."|".$encodednewkey);
  $cmpinfo=getcompanydetails($mconn,"companyid",$companyid);
   $companyname="";
   if(count($cmpinfo)>0){
    foreach ($cmpinfo as $cmphkey) {
      $companyname=$cmphkey['company_name'];
      $countryid=$cmphkey['countryid'];
    }
  }

  $subcmpinfo=getsubcompanydetails($mconn,$companyid,$subcompanyid,"subcompanyid",$subcompanyid);
  $subcompanyname="";
  if(count($subcmpinfo)>0){
    foreach ($subcmpinfo as $subcmphkey) {
      $subcompanyname=$subcmphkey['subcompanyname'];
    }
  }

  //projectinfo
  $prj=getprojectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
  // print_r($prj);
  $projectname ="";
  if(count($prj)>0){
    foreach ($prj as $pjkey) {
      $projectname = $pjkey['projectname'];
    }
  }

  
  $protocol=$_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
  $url = basename( $_SERVER["PHP_SELF"]);
  $rooturl = $protocol.'://'.$_SERVER['HTTP_HOST'];
  $baseurl= $rooturl.str_replace($url, "", $_SERVER["PHP_SELF"]);
  $countryid = "";
  if($_SERVER['HTTP_HOST']=="localhost"){
    $prefix=$rooturl."/krf_amanaccounting/";
  }else{
    $prefix=$rooturl."/";
  }
  $rooturl =$prefix;
}else{
  require('../header.php');
  include('functions/orders_functions.php');
  $rooturl; 
}
$mdid ="";
if(isset($_GET['mdid'])){
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
$groupid ="";
if(count($dddr)>0){
  $groupid = $dddr[0]['groupid'];
}
?>
<div class="container-main">
  <div class="row <?php echo isset($_GET['dz'])?'d-none':'' ?>">
    <div class="col-md-4">
      <a href="../admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue m-2">Back</a>
    </div>
    <div class="col-md-4 text-center">
      <h4>Growing Time Master</h4>
    </div>
    <div class="col-md-4">
      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</a>
    </div>
  </div>
  <div class="card mt-1">
   
    <div class="card-body">
      <div class="row"> 
        <div class="col-md-4"> 
          <input type="text" id='gftrggrftinput' oninput="filterTable('#gftrggrftinput', '#tblgrowingmaster');" class="form-control" placeholder="Type for Searching ......">
        </div>
      </div>
      <div class="row mt-2">
        <div class="col-md-12">
          <div class="table-responsive">
            <table class="table table-bordered table-striped" id="tblgrowingmaster">
              <thead>
                <tr> 
                  <th>Product Name</th> 
                  <th>Product Type</th> 
                  <th>Growing Days</th>
                  <th>Grafing Days</th>
                  <th>Seasonal Correction Days</th> 
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
</div>
<div class="modal fade" id="growingmasterModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="growingmasterModalheader">Add/Modify Growing Master</h5>
        <button type="button" class="close" onclick="$('#growingmasterModal').modal('hide')">&times;</button>
      </div>
      <div class="modal-body" id="growingmasterModalContent">        
          <div class="row mb-3">
              <div class="col-md-12">
                  <label for="productname" class="form-label">Product Name  </label>
                 <input type="text" id="productname"   class="form-control" readonly>
              </div>
              <div class="col-md-12">
                  <label for="producttype" class="form-label">Product Type  </label>
                 <input type="text" id="producttype"   class="form-control" readonly>
              </div>
          </div>

          <div class="row mb-3">
              <div class="col-md-12">
                  <label for="growingdays" class="form-label"> Growing Days
                  </label>
                  <input type="text" id="growingdays" class="form-control" oninput="this.value = this.value.replace(/[^0-9]/g, '');">

              </div>
          </div>
          <div class="row mb-3">
              <div class="col-md-12">
                  <label for="grafteddays" class="form-label"> Grafted Days
                  </label>
                  <input type="text" id="grafteddays" class="form-control" oninput="this.value = this.value.replace(/[^0-9]/g, '');">

              </div>
          </div>
          <div class="row mb-3">
              <div class="col-md-12">
                  <label for="seasonalcorrectionday" class="form-label"> Seasonal Correction Days
                  </label>
                  <input type="text" id="seasonalcorrectionday" class="form-control" oninput="this.value = this.value.replace(/[^0-9]/g, '');">

              </div>
          </div>


            <div class="row mt-2">
              <div class="col-md-12">
                <div class="table-responsive">
                  <table class="table table-bordered table-striped" id="tblcorrection">
                    <thead>
                      <tr> 
                        <th>Jan</th> 
                        <th>Feb</th> 
                        <th>Mar</th>
                        <th>Apr</th>
                        <th>May</th> 
                        <th>June</th>
                        <th>July</th>
                        <th>Aug</th>
                        <th>Sep</th>
                        <th>Oct</th>
                        <th>Nov</th>
                        <th>Dec</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr> 
                        <td contenteditable="true"> </td> 
                        <td contenteditable="true"> </td> 
                        <td contenteditable="true"> </td>
                        <td contenteditable="true"> </td>
                        <td contenteditable="true"> </td> 
                        <td contenteditable="true"> </td>
                        <td contenteditable="true"> </td>
                        <td contenteditable="true"> </td>
                        <td contenteditable="true"> </td>
                        <td contenteditable="true"> </td>
                        <td contenteditable="true"> </td>
                        <td contenteditable="true"> </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

          <div class="form-group mb-3 text-center">
              <button type="submit" id="updategrowingmasterdetailsbtn" class="btn btn-deepblue">Save growing master</button>
          </div>
      </div>
    </div>
  </div>
</div>
<?php
if(isset($_GET['dz'])){

}else{
  include('../footer.php');
}
?> 
<script>
  
$(document).ready(function(){
  getgrowingdata("tblgrowingmaster");
  $(document).on('click', '#updategrowingmasterdetailsbtn', function () {  
    let productid=$("#productname").data("productid")||"";
    let growingid=$("#productname").data("growingid")||"";
    let productname=$("#productname").val()||"";
    let growingdays=$("#growingdays").val()||"";
    let grafteddays=$("#grafteddays").val()||"";
    let seasonalcorrectionday=$("#seasonalcorrectionday").val()||"";

    if(!productid || !productname){
      alert("Product name can't be empty");
      return false;
    } else if(!growingdays || parseInt(growingdays)<=0 ) {
      alert("You have not mention how many days required for growing of seeds");
      $("#growingdays").focus();
      return false;
    } else if( !seasonalcorrectionday || parseInt(seasonalcorrectionday)<=0 ) {
      alert("You have not mention how many days required for seasonal correction of seeds");
      $("#seasonalcorrectionday").focus();
      return false;
    }
    let seasoncorrectiondata = [];

    $("#tblcorrection tbody tr").each(function(rowIndex) {
        let rowData = [];
        $(this).find("td").each(function(colIndex) {
            let val = $(this).text().trim() || 0;
            rowData.push({
                month: colIndex + 1,      
                monthval: parseInt(val)
            });
        });
        seasoncorrectiondata.push(rowData);
    });

    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');  
    const formData = new FormData(); 
    formData.append('projectid', projectid);
    formData.append('loginid', loginid);
    formData.append('productid', productid); 
    formData.append('growingid', growingid);
    formData.append('productname', productname); 
    formData.append('growingdays', growingdays);
    formData.append('grafteddays', grafteddays); 
    formData.append('seasonalcorrectionday', seasonalcorrectionday);
    //formData.append("correctionarray",JSON.stringify(seasoncorrectiondata));
    $.ajax({
      url: "modal/growing_function.php?action=savegrowingmaster",
      type: "POST",
      data: formData,
      dataType: "json",
      contentType: false,  // ✅ Required when using FormData
      processData: false,  // ✅ Required when using FormData
      cache: false,
      success: function(response) {
          // console.log(response);
          if (response.status === 'Success' || response.status === 'success') {
              alertmessage("success", response.message);
              location.reload();
          } else {
              alertmessage("error", "Error: " + response.message);
          }
      },
      error: function(xhr, status, error) {
          console.error("AJAX Error:", error);
          alertmessage("error", "Server error: " + error);
      }
    });
  });
  $(document).on("input", "#tblcorrection td[contenteditable='true']", function() {
      // Remove any non-digit characters
      let value = $(this).text().replace(/\D/g, '');
      $(this).text(value);
      
      // Move cursor to end after re-setting value
      let range = document.createRange();
      let sel = window.getSelection();
      range.selectNodeContents(this);
      range.collapse(false);
      sel.removeAllRanges();
      sel.addRange(range);
  });

  $(document).on("paste", "#tblcorrection td[contenteditable='true']", function(e) {
      e.preventDefault();
      let text = (e.originalEvent || e).clipboardData.getData('text/plain');
      text = text.replace(/\D/g, ''); // Allow only digits
      document.execCommand('insertText', false, text);
  });
  
});
function getgrowingdata(tableid){ 
    var $table = $("#" + tableid + " tbody") ;
    $table.empty();
    var routurl = $('body').data('routurl'); 
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');
        debugger          
     
    const formData = new FormData(); 
    formData.append('projectid', projectid); 

    $.ajax({
        type: 'POST',
        url: `${routurl}growingtime/modal/growing_function.php?action=getproductdata_growing`,
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'JSON',
        success: function(res) {
          if (res && res.length > 0) {
              var rows = ""; 
              for (var i = 0; i < res.length; i++) {  
                rows += `
                  <tr data-growingid="${res[i].growingid}" data-productid="${res[i].productid}">
                      <td class="productname">${res[i].productname || ''}</td> 
                      <td class="isGrafted">${res[i].isGrafted || ''}</td>
                      <td class="growingdays" style="text-align:center;">${res[i].growingdays && res[i].growingdays != 0 ? res[i].growingdays : ''}</td>
                      <td class="graftingdays" style="text-align:center;">${res[i].graftingdays && res[i].graftingdays != 0 ? res[i].graftingdays : ''}</td> 
                      <td class="seasonalcorrectionday" style="text-align:center;">${res[i].seasonalcoveredtime && res[i].seasonalcoveredtime != 0 ? res[i].seasonalcoveredtime : ''}</td>
                      <td class="text-center">
                          <button 
                              type="button"
                              class="btn btn-sm btn-primary"
                              data-id="${res[i].growingid}"
                              onclick="opengrowmastermodal(this.closest('tr'))">
                              <i class="fa fa-edit"></i>
                          </button>
                      </td>
                  </tr>
              `;

            }
            $table.append(rows);

          } else {
              $table.append('<tr><td colspan="6" class="text-center text-muted">No records found</td></tr>');
          }
        }
    });
}
function opengrowmastermodal(rowElement) {
    const $row = $(rowElement); 
    const productid = $row.data("productid");
    const growingid = $row.data("growingid");
    $("#productname").removeData();
    $("#productname").data("productid",productid);
    $("#productname").data("growingid",growingid); 
    $("#productname").val($row.find(".productname").text());
    $("#producttype").val($row.find(".isGrafted").text());
    $("#growingdays").val($row.find(".growingdays").text());
    $("#grafteddays").val($row.find(".graftingdays").text());
    $("#seasonalcorrectionday").val($row.find(".seasonalcorrectionday").text());
    // Show modal
    $("#growingmasterModal").modal("show");
}
</script>
