<?php
if(isset($_GET['vw'])==1){

}else{
  require('../header.php');
}
?>
<div class="container mt-2">
    <div class="form-row">
        <div class="col-md-12">
            <label for="NewDesignation">New Designation Name</label>
            <input type="text" class="form-control" id="NewDesignation" name="NewDesignation" />
        </div>
    </div>
    <div class="form-row mt-4">
        <div class="col-md-12 text-center">
            <button class="btn btn-success" data-designationid='' id="SaveNewDesignation" onclick="SaveOrUpdateDesignation('NewDesignation','SaveNewDesignation');">Save New Designation</button>
        </div>
    </div>
</div>
<?php
if(isset($_GET['vw'])==1){

}else{
  require('../footer.php'); 
}
?>