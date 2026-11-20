<?php
  require('../header.php');
  $prj = projectdetails($mconn,$companyid,$subcompanyid,"projectid",$projectid);
  foreach ($prj as $prjkey) {
    $projectname = $prjkey['projectname'];
  }
  //companyinfo
  $ccmp=getcompanydetails($mconn,"companyid",$companyid);
  foreach ($ccmp as $cmpkey) {
    $countryid=$cmpkey['countryid'];
  }

$mdid ="";
if(isset($_GET['mdid'])){
	$mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
if(count($dddr)>0){
	$groupid = $dddr[0]['groupid'];
}
?>
<style>
  /* Background color for active tab */
  .nav-tabs .nav-link.active {
    background-color: #FFF; /* Bootstrap primary color */
    color: #000;
    border-color: #dee2e6 #dee2e6 #fff;
  }

  /* Background and border for all tabs */
  .nav-tabs .nav-link {
    background-color: #8a8888;
    border: 1px solid #dee2e6;
    margin-right: 2px;
    color: #FFF;
  }

  /* Optional: border for tab content */
  .tab-content {
    border: 1px solid #dee2e6;
    border-top: none;
    padding: 15px;
    background-color: #fff;
  }
</style>
<div class="main-container">
  <div class="row">
  	<div class="col-md-12 text-center">
      <h3 class="">REQUEST APPROVAL FOR PAYMENT</h3>
    </div>
    <div class="col-md-12">
      <a href="<?php echo $rooturl; ?>admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue mt-2 mb-2 pull-right backtohome">Back to Home</a>
    </div>
  </div>
  <div id="voiceinstructionhtmlview" class="mt-2 mb-2"></div>

  <ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="activrequest-tab" data-toggle="tab" href="#activrequest" role="tab" aria-controls="activrequest" aria-selected="true">Active Request</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="addedinwaiting-tab" data-toggle="tab" href="#addedinwaiting" role="tab"  aria-controls="addedinwaiting" aria-selected="false">Added In Waiting Request</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="changeamountwhiletransfered-tab" data-toggle="tab" href="#changeamountwhiletransfered" role="tab"  aria-controls="changeamountwhiletransfered" aria-selected="false">Modification in Approved & Transferred Amount</a>
    </li>
  </ul>
  <div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active p-3" id="activrequest" role="tabpanel" aria-labelledby="activrequest-tab">
      <div class="row">
        <div class="col-md-12">
          <div class="table-responsive">
            <table class="table-mobile" border="1" id="pendingrequesttbl">
              <thead>
                <tr>
                    <th colspan="6" style="font-size:large;">Pending Request's for Approval</th>
                    <th colspan="3"><input type="text" id="pendingrqlistfilter" oninput="filterTable('#pendingrqlistfilter', '#pendingrequesttbl');" class="form-control" placeholder="Type to Search ......" /></th>
                </tr>
                <tr>
                  <th>S.No</th>
                  <th>Request Date</th>
                  <th>Request Type</th>
                  <th>Request No</th>
                  <th>Payto Name</th>
                  <th>Amount</th>
                  <th>Payment Due Date</th>
                  <th>Requested By</th>
                  <th>Request Status</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $reqsinfo= getrequestdetails($mconn,$companyid,$subcompanyid,$projectid,"allunapprovedwithprojectid","all");
                $etsno=1;
                foreach ($reqsinfo as $etrow) {
                  if($etrow['approval_status']==""){
                    $approvalstatus="Awaiting approval";
                    $style="style='color: blue'";
                  }else{
                    $approvalstatus = ucwords($etrow['approval_status']);
                    $style="style='color: green'";

                  }
                  $paymentduedate ="";
                  if($etrow['paymentduedate']){
                    if(isValidDate($etrow['paymentduedate'],'Y-m-d')==1){
                      $paymentduedate = date('d-M-Y', strtotime($etrow['paymentduedate']));
                    }
                  }
                  echo "<tr>
                    <td data-label='SNO'>".$etsno++."</td>
                    <td data-label='REQUEST DATE'>".date('d-M-Y',strtotime($etrow['request_date']))."</td>
                    <td data-label='REQUEST TYPE'>".ucwords(strtolower($etrow['purpose_type']))."</td>
                    <td data-label='REQUEST NUMBER'>".$etrow['request_no']."</td>
                    <td data-label='PAYTO NAME'>".ucwords(strtolower($etrow['payto']))."</td>
                    <td data-label='REQUESTED AMOUNT'>".$etrow['request_amount']."</td>
                    <td data-label='PAYMENT DUE-DATE'>".ucwords(strtolower($paymentduedate))."</td>
                    <td data-label='REQUESTED BY'>".ucwords(strtolower($etrow['requested_by']))."</td>
                    <td data-label='REQUEST STATUS'><a href='request_preview.php?requestno=".base64_encode($etrow['request_no'])."&k=".$_GET["k"]."&lid=".$_GET['lid']."&mdid=".$_GET['mdid']."' type='button' ".$style.">".$approvalstatus."</a></td>
                    
                  </tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- added waiting list for approval -->
    <div class="tab-pane fade p-3" id="addedinwaiting" role="tabpanel" aria-labelledby="addedinwaiting-tab">
        <div class="row">
          <div class="col-md-12">
            <div class="table-responsive">
              <table class="table table-light table-bordered" id="waitingrequesttbl">
                <thead>
                  <tr>
                      <th colspan="6" style="font-size:large;">Added in Waiting Request's for Approval</th>
                      <th colspan="3"><input type="text" id="waitingrqlistfilter" oninput="filterTable('#waitingrqlistfilter', '#waitingrequesttbl');" class="form-control" placeholder="Type to Search ......" /></th>
                  </tr>
                  <tr>
                    <th>S.No</th>
                    <th>Request Date</th>
                    <th>Request Type</th>
                    <th>Request No</th>
                    <th>Payto Name</th>
                    <th>Amount</th>
                    <th>Payment Due Date</th>
                    <th>Requested By</th>
                    <th>Request Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $reqsinfo= getrequestdetails($mconn,$companyid,$subcompanyid,$projectid,"allunapprovedwaitingforapproval","");
                  $etsno=1;
                  foreach ($reqsinfo as $etrow) {
                    if($etrow['approval_status']==""){
                      $approvalstatus="Awaiting approval";
                      $style="style='color: blue'";
                    }else{
                      $approvalstatus = ucwords($etrow['approval_status']);
                      $style="style='color: green'";

                    }
                    $paymentduedate ="";
                    if($etrow['paymentduedate']){
                      if(isValidDate($etrow['paymentduedate'],'Y-m-d')==1){
                        $paymentduedate = date('d-M-Y', strtotime($etrow['paymentduedate']));
                      }
                    }
                    echo "<tr>
                      <td>".$etsno++."</td>
                      <td>".date('d-M-Y',strtotime($etrow['request_date']))."</td>
                      <td>".ucwords(strtolower($etrow['purpose_type']))."</td>
                      <td>".$etrow['request_no']."</td>
                      <td>".ucwords(strtolower($etrow['payto']))."</td>
                      <td>".$etrow['request_amount']."</td>
                      <td>".ucwords(strtolower($paymentduedate))."</td>
                      <td>".ucwords(strtolower($etrow['requested_by']))."</td>
                      <td><a href='request_preview.php?requestno=".base64_encode($etrow['request_no'])."&k=".$_GET["k"]."&lid=".$_GET['lid']."&mdid=".$_GET['mdid']."' type='button' ".$style.">".$approvalstatus."</a></td>
                      
                    </tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
    </div>
    <div class="tab-pane fade p-3" id="changeamountwhiletransfered" role="tabpanel" aria-labelledby="changeamountwhiletransfered-tab">
        <div class="row">
          <div class="col-md-12">
            <div class="table-responsive">
              <table class="table-mobile table-bordered" id="modifyapprovedamounttbl">
                <thead>
                  <tr>
                      <th colspan="6" style="font-size:large;">Modification in Approved & Transferred Amount</th>
                      <th colspan="3"><input type="text" id="modifyamountlistfilter" oninput="filterTable('#modifyamountlistfilter', '#modifyapprovedamounttbl');" class="form-control" placeholder="Type to Search ......" /></th>
                  </tr>
                  <tr>
                    <th>S.No</th>
                    <th>Reference No</th>
                    <th>Payto Name</th>
                    <th>Approval Date</th>
                    <th>Transfered Date</th>
                    <th>Approved Amount</th>
                    <th>Transfered Amount</th>
                    <!-- <th>Transfered By</th> -->
                    <!-- <th>Approved By</th> -->
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $datefrom = date('Y-m-d', strtotime('-360 days'));
                  $dateto=date('Y-m-d');
                  $mddata=modificationamountwhiletransaction($conn,$companyid,$subcompanyid,$projectid,$datefrom,$dateto);
                  $etsno=1;
                  foreach ($mddata as $mdrow) {
                    
                    $bookdate ="";
                    if($mdrow['bookdate']){
                      if(isValidDate($mdrow['bookdate'],'Y-m-d')==1){
                        $bookdate = date('d-M-Y', strtotime($mdrow['bookdate']));
                      }
                    }
                    $transfereddate ="";
                    if($mdrow['transaction_date']){
                      if(isValidDate($mdrow['transaction_date'],'Y-m-d')==1){
                        $transfereddate = date('d-M-Y', strtotime($mdrow['transaction_date']));
                      }
                    }
                    echo "<tr>
                      <td data-label='S.No'>".$etsno++."</td>
                      <td data-label='Reference No'>".$mdrow['reference_no']."</td>
                      <td data-label='Payto Name'>".$mdrow['payto']."</td>
                      <td data-label='Approval Date'>".$bookdate."</td>
                      <td data-label='Transfered Date'>".$transfereddate."</td>
                      <td data-label='Approved Amount'>".$mdrow['book_amount']."</td>
                      <td data-label='Tranfered Amount'>".$mdrow['transfered_amount']."</td>
                    </tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
    </div>
  </div>
</div>
<?php
 require('../footer.php');
?>
<!-- <script>
var moduleid = '<?php //echo $mdid; ?>';
if(moduleid){
  checkmoduleHaspasswordProtected(moduleid);
}
</script> -->