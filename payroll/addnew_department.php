<?php
if(isset($_GET['vw'])==1){

}else{
  require('../header.php');
}
?>
<div class="container mt-2">
    <div class="form-row">
        <div class="col-md-12">
            <label for="NewDepartment">New Department Name</label>
            <input type="text" class="form-control" id="NewDepartment" name="NewDepartment" />
        </div>
    </div>
    <div class="form-row mt-4">
        <div class="col-md-12 text-center">
            <button class="btn btn-success" data-departmentid='' id="SaveNewDepartment" onclick="SaveOrUpdateDepartment('NewDepartment','SaveNewDepartment');">Save New Department</button>
        </div>
    </div>
</div>
<?php
if(isset($_GET['vw'])==1){

}else{
  require('../footer.php'); 
}
?>