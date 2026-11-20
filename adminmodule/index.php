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
  <!-- <section> -->
    <div class="row">
        <div class="col-sm-12 text-center">
            <h3 class=" thin">Masters Module</h3>
        </div>
  	</div>
  	<div class="container" style="background-color: #CCD2D2; padding: 16px; margin-top: 16px; margin-bottom: 32px;">
      <div class="row">
      	<!-- <div class="col-md-12 mt-2">
  				<button onclick="history.back();" class="btn btn-deepblue btn-sm pull-right">Back</button>
      	</div> -->
      	<div class="col-md-12 mt-2">
          <a href="<?php echo $rooturl; ?>admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue pull-right backtohome">Back to Home</a>
        </div>

      	<div class="col-md-12 mt-2 <?php echo $loginid=='8923339447'?'':'d-none'?>">
	      	<a class="p5 btn" href="project_management.php?k=<?php echo $_GET['k']; ?>"  style="width:100%; height: auto; white-space: normal; background-color: #BA7D66;">
						<h5 style="color:#001B48;">Project Master</h5>
					</a>
				</div>
				<div class="col-md-12 mt-2">
	      	<a class="p5 btn" href="project-accountmaster.php?k=<?php echo $_GET['k']; ?>"  style="width:100%; height: auto; white-space: normal; background-color: #BA7D66;">
						<h5 style="color:#001B48;">Bank Accounts Master</h5>
					</a>
				</div>
				<div class="col-md-12 mt-2">
	      	<a class="p5 btn d-block" style="background-color: #BA7D66;" href="loginmanagement.php?k=<?php echo $_GET['k']; ?>">
						<h5 style="color:#001B48;">Users Login Master</h5>
					</a>
				</div>

				<div class="col-md-12 mt-2">
	      	<a class="p5 btn d-block" href="imprest_accountmaster.php?k=<?php echo $_GET['k']; ?>" style="background-color: #C09E94;">
						<h5 style="color:#001B48;">Authorized Imprest Master</h5>
					</a>
				</div>
				<div class="col-md-12 mt-2">
	      	<a class="p5 btn d-block" href="cash_accountmaster.php?k=<?php echo $_GET['k']; ?>" style="background-color: #C09E94;">
						<h5 style="color:#001B48;">Authorized Cash Master</h5>
					</a>
				</div>
				
				<div class="col-md-12 mt-2">
	      	<a class="p5 btn d-block" href="loan_accountmaster.php?k=<?php echo $_GET['k']; ?>" style="background-color: #C09E94;">
						<h5 style="color:#001B48;">Authorized Loan Master</h5>
					</a>
				</div>
				<div class="col-md-12 mt-2">
	      	<a class="p5 btn d-block" href="creditcard_accountmaster.php?k=<?php echo $_GET['k']; ?>" style="background-color: #C09E94;">
						<h5 style="color:#001B48;">Credit Card Master</h5>
					</a>
				</div>
				<div class="col-md-12 mt-2">
	      	<a class="p5 btn d-block" href="supplier_accountmaster.php?k=<?php echo $_GET['k']; ?>" style="background-color: #91524B;">
						<h5 style="color:#001B48;">Supplier Master</h5>
					</a>
				</div>
				<div class="col-md-12 mt-2">
	      	<a class="p5 btn d-block" href="purpose_master.php?k=<?php echo $_GET['k']; ?>" style="background-color: #91524B;">
						<h5 style="color:#001B48;">Purpose Master</h5>
					</a>
				</div>
				<div class="col-md-12 mt-2">
	      	<a class="p5 btn d-block" href="../adminsuper/modules_passwordprotected.php?k=<?php echo $_GET['k']; ?>" style="background-color: #91524B;">
						<h5 style="color:#001B48;">Module Password Management</h5>
					</a>
				</div>
				<div class="">
			</div>
		</div>
  <!-- </section> AUTHORISE PERSON TO WORK IN A MODULE -->
</div>

<div class="modal" id="gotosubcompanymanage" style="display: none;">
  <div class="modal-content" style="width:95%; margin-bottom: 50px; margin-top: 0px;">
    <header class="row " style="background-color: #A4E8F7; height:100px;">
      <h4 class="text-blue">Manage Subcompany Details<span onclick="document.getElementById('gotosubcompanymanage').style.display='none'" 
               class="btn float-right hover-red">&times;</span></h4>
    </header>
    <div class="row p-4" id="id_A">             
        <div class="col-lg-6 col-md-4 col-sm-12">
      			<div class="media p-3 btn-cardN mt-2" >
          		<img src="../assets/img/icon/icons8-add-80.png" class="rounded-circle11" style="width:50px;">
          		<div class="media-body">
              	<h4>Add Subcompany<br/></h4>
              	<button type="button" class="btn btn-outline-primary pull-right" id="goforsubcompanyadd_btn">Go For Add Subcompany</button>
          		</div>
      			</div>
  			</div>
  			<div class="col-lg-6 col-md-4 col-sm-12">
      			<div class="media p-3 btn-card mt-2">
          		<img src="../assets/img/icon/icons8-add-80.png" class="rounded-circle11" style="width:50px;">
          		<div class="media-body">
              	<h4>Edit Subcompany <br/></h4>
              	<button type="button" class="btn btn-outline-primary pull-right" id="goforeditsubcompany_btn">Go For Edit Subcompany Details</button>
          		</div>
      			</div>
  			</div>
    </div>
    <div class="p-4" id="add_subcompany" style="display:none;">
    		<details>
  				<summary>ADD NEW SUBCOMPANY</summary>
  				<div>
    			<div class="p-4 bg-info">
      		<div >
        		<div class="row">
				  		<div class="col-lg-6">
      	        <label>Subcompany Name</label>
                <input type="text" id="txt_subcompname" class="form-control"  placeholder="Enter Subcompany Name"/>           
    	    		</div>
    	   		 	<div class="col-lg-6">
    	    		  <label>Registration No.</label>
    	    		  <input type="text" id="txt_registrstionno" class="form-control"  placeholder="Enter Registration No."/> 
    	    		</div>
						</div>
						<div class="row">
				  		<div class="col-lg-6">
    	         <label>Email Id</label>
             <input type="text" id="txt_subcompemail" class="form-control" placeholder="Enter Email Id"/>           
    	    		</div>

    	   		 	<div class="col-lg-6">
    	    		 
    	    		 <label>Phone No./Mobile No.</label>
    	    		 <input type="text" id="txt_subcompmobileno" class="form-control"  placeholder="Enter Phone/Mobile No."/> 
    	    		</div>

						</div>

						<div class="row">
  						<div class="col-md-6">
    					<div class="form-group">
      				<!-- <input class="" type="radio" name="radio" id="id_withoutdoc"><span class="w-25 p-2 text-warning ">If You Want To Add Project In This Subcompany. Please Click.</span> -->
      				<input class="" type='checkbox' name='addprojectofsubcompany' style=' transform: scale(1);'><span class="w-25 p-2 text-warning ">If You Want To Add Project Of This Subcompany. Please Click.</span>
    					</div>
  						</div>
  					</div>

  					<div>
  						<table class="table" id="addedproject_list">
  							<thead>
  							  <tr>
  							    <th>Sno</th>
  							    <th>Project Name</th>
  							   
  							  </tr>
  							</thead>
  							<tbody></tbody>
							</table> 
  					</div>

  					<div class="row">
  						<div class="col-md-6">
    					<div class="form-group">
      				<input class="" type='checkbox' name='addadminofsubcompany' style=' transform: scale(1);'><span class="w-25 p-2 text-warning ">If You Want To Add Admin Of This Subcompany. Please Click.</span>
      				<!-- <input class="" type="radio" name="radio1" id="id_withoutdoc"><span class="w-25 p-2 text-warning ">If You Want To Add Admin In This Subcompany. Please Click.</span> -->
    					</div>
  						</div>
  					</div>

  					<div>
  						<table class="table" id="addadminuser_list">
  							<thead>
  							  <tr>
  							    <th>Sno</th>
  							    <th>Type</th>
  							    <th>Name</th>
  							    <th>Mobile No.</th>
  							    <th>Email</th>
  							  </tr>
  							</thead>
  							<tbody></tbody>
							</table> 
  					</div>
  					<div class="row text-center p-4">
  						<input type="button" class="btn btn-success mb-4" id="btn_submitsubcompany" value="SUBMIT" style="display: ;">
  					</div>
      		</div>
    			</div>
  				</div>
				</details>
    </div>
    <div class="p-4" id="edit_subcompany" style="display:none;">
    		<details id="id_B">
  				<summary>EDIT SUBCOMPANY DETAILS</summary>
  				<div>
    					<div class="p-4 bg-info">
      				<div >
        				<div class="row">
				  				<label class="col-lg-12 control-label">Select Subcompany Name</label>
          		    
          		    <div class="col-lg-10">
          		    <select class="form-control" id="cmb_subcompany" name="cmb_subcompany">
          		  </select>

								</div>

							
      				</div>
    					</div>
  						</div>
  				</div>
				</details>
				<details id="id_xx" style="display:none;">
					<summary>PROJECT DETAILS</summary>
					<div>
						<div class="p-4 bg-info">
							<div class="row">
								<label class="col-lg-12 control-label">Select Project Name</label>
								 <div class="col-lg-10">
          		    	<select class="form-control" id="cmb_project" name="cmb_project">
          		    	</select>

									</div>
									<div class="col-lg-2">
										 <div id="add_p"><img src="../assets/img/icon/icons8-add-80.png" class="rounded-circle11" style="width:50px;">
										 </div>
          		   			 
									</div>
							</div>
						</div>
					</div>
					<div class="shade9 p-3" style="margin-top: 10px; display:none;" id="id_paccdetails">
							<div class="row">
							<h4 class="">Project Accounts Details <span onclick="document.getElementById('reconcilesy').style.display='block'"  class="div_open_white"></span></h4>
							</div>
							<div id="reconcilesy" style=" display: none;">
							<p><span onclick="document.getElementById('reconcilesy').style.display='none'"  class="div_collapse"></span></p><br>
							<div id="add_acc"><img src="../assets/img/icon/icons8-add-80.png" class="rounded-circle11" style="width:50px;">
          		<h5 class="">Add New Account</h5>	
							</div>

								<div class="col-lg-12 p-4 bg-white">
								    <div class="table-responsive bg-light">
								      <table class="table" id="projectaccounts_tbl">
								        <thead class="bg-info">
								          <tr>
								            <th>S.No</th>
								            <th>Account Name</th>
								            <th>Bank Name</th>
								            <th>Account No.</th>
								            
								            <th style="display: none;">qbsno</th>
								          </tr>                     
								        </thead>
								        <tbody>
								          <tr>
								            <td colspan="5" style="text-align: center;">Data not available</td>
								          </tr>
								        </tbody>
								      </table>
									   </div>
								</div>
							</div>
					</div>
				</details>
				<details id="id_yy" style="display:none;">
  				<summary>LOGIN DETAILS</summary>
  					<div class="bg-info">
      				<div class="shade9 p-3" style="margin-top: 10px; display:;" id="id_paccdetails1">
									<div class="row">
									<h4 class="">Login Details <span onclick="document.getElementById('reconcilesy1').style.display='block'"  class="div_open_white"></span></h4>
									</div>
									<div id="reconcilesy1" style=" display: none;">
									<p><span onclick="document.getElementById('reconcilesy1').style.display='none'"  class="div_collapse"></span></p><br>
									<div id="add_user"><img src="../assets/img/icon/icons8-add-80.png" class="rounded-circle11" style="width:50px;">
          				<h5 class="">Add New User</h5>	
									</div>
											<div class="col-lg-12 p-4 bg-white">
								    		<div class="table-responsive bg-light">
								    			  <table class="table" id="logindetails_tbl">
								    			    <thead class="bg-info">
								    			      <tr>
								    			        <th>S.No</th>
								    			        <th>Name</th>
								    			        <th>Mobile No.</th>
								    			        <th>Email ID</th>
								    			        <th>Login Type</th>
								    			        <th style="display: none;">qbsno</th>
								    			        <th>Action</th>
								    			      </tr>                     
								    			    </thead>
								    			    <tbody>
								    			      <tr>
								    			        <td colspan="5" style="text-align: center;">Data not available</td>
								    			      </tr>
								    			    </tbody>
								    			  </table>
									   			</div>
											</div>
									</div>
					      </div>
  					</div>
  					
  				  <div class="bg-info" id="idadduser" style="display:none;">
      				  <div class="shade8 p-3" style="margin-top: 10px;" >
									<div class="row">
										<div class="text-center " id="he_projectwise" style="display:;">
    								  <h4 class=" bold">LOGIN AUTHORIZATION MODULE</h4>
										</div>
									</div>  											
								  <div class="row shade9">
								 	  <div class="col-md-6 text-center">
    								  <h4><label class=" bold" id="id_lablesubcomp"></label></h4>
    							  </div>
    								<div class="col-md-6">
        							<label  class="">Select User Type </label>
        							<select class="form-control select2" name="login_type" id="login_type" style="width:100%;" >
              				  <option value="" selected disabled>Select Login Name</option>
              				  <option value="Admin">Admin</option>
              				  <option value="User">User</option>
        							</select>
    								</div>
    							</div>
    							<div class="row" id="id_user">
    								<div class="col-md-4">
          						<label class="">Enter login name </label>
          						<input type="text" class="form-control" name="text" id="txt_loginname" placeholder="Enter Login Name">
    								</div>
    								<div class="col-md-4">
        							<label class="">Enter Mobile No. of login person. </label>
        							<input type="text" class="form-control" name="loginmobile" id="loginmobile" placeholder="Enter Mobile No">
    								</div>
    								<div class="col-md-4">
        							<label class="">Enter Email of login person. </label>
        							<input type="text" class="form-control" name="loginemail" id="loginemail" placeholder="Enter Email Address">
    								</div>
  								</div>
  								<div class="row" id="id_project">
                    <div class="col-md-12">
                      <label class=" p-3">Authorized for Project! <i class="text-red">*</i></label>
    								  <div class="table-responsive bg-light">
    								    <table id="project_tbl" class="table-all table-bordered">
      								    <thead>
        								    <tr>
          								    <th>Select</th>
          								    <th>Project Name</th>
          								    <th>Project ID</th>
        								    </tr>
      								    </thead>
    								      <tbody></tbody>
    								    </table>
    								  </div>
                    </div>
  								</div>
  								<div class="row" id="id_module">
                    <div class="col-md-12">
  								 		<label>Authorized for Module! <i class="text-red">*</i></label>
  								 	  <div class="table-responsive bg-light">
         						    <table id="slt_moudulename" class="table-all table-bordered">
         						        <thead>
             						      <tr>
               						      <th>Select</th>
               						      <th>Module Name</th>
             						      </tr>
         						        </thead>
       						          <tbody></tbody>
       						      </table>
         						  </div>
       						  </div>
                  </div>
                  <div class="row">
                    <div class="col-md-12">
                      <div class="table-responsive bg-light">
  											<table class="table" id="addadminpanel_list">
  												<thead>
  												  <tr>
  												    <th>Sno</th>
  							    					<th>Panel</th>
  												   	<th>Panel Folder</th>
  												  </tr>
  												</thead>
  												<tbody></tbody>
												</table> 
									    </div>
      						  </div>
    							</div>
								</div>
					  </div>

			    	<div class="row  bg-info">
			    		<div class="col-lg-6 p-4">
									<button type="button" class="btn btn-success" id="saveauthorization" >Submit</button>
							</div>
						</div>
				</details>
		</div>
	</div>
</div>

<div class="modal" id="idaddproject">
	<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" style="min-height: 110%;">
		<div class="modal-content" style="margin-bottom: 100px;">
			<div class="modal-header">				
				<h4 id="editheadermodal" class="">ADD NEW PROJECT <span onclick="document.getElementById('idaddproject').style.display='none'" class="btn float-right hover-red">&times;</span></h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class=""><b>Enter Project Name : </b> <input type="text" id="txt_enterprojectname" class="form-control hide_year " name="" style="width: 100%;"></div>
				</div>
				<div class="row">
					<div class=""><b>Enter Project Email Id : </b> <input type="text" id="txt_enterprojectemail" class="form-control hide_year " name="" style="width: 100%;"></div>
				</div>
				<div class="row">
					<div class=""><b>Enter Project Account Name : </b> <input type="text" id="txt_enterprojectaccountname" class="form-control hide_year " name="" style="width: 100%;"></div>
				</div>
				<div class="row">
					<div class=""><b>Enter Project Account No. : </b> <input type="text" id="txt_enterprojectaccountno" class="form-control hide_year " name="" style="width: 100%;"></div>
				</div>			
				<div class="row p-4-32 w3-center">
					<input type="button" class="btn btn-deepblue mb-4" id="addandexitproject" value="CLICK TO ADD PROJECT AND EXIT" style="display: ;">
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="idaddaccount">
	<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" style="min-height: 110%;">
		<div class="modal-content" style="margin-bottom: 100px;overflow-y:auto;">
			<header class="p-4 shade5">
				<h4 id="editheadermodal" class="">ADD NEW ACCOUNT <span onclick="document.getElementById('idaddaccount').style.display='none'"class="btn float-right hover-red">&times;</span></h4>
        <label class="" id="id_lableproject"></label>
			</header>
			<div class="modal-body" style="height:80vh;">
				<div class="row" style="display:none;">
					<div class="col-md-6">
						<label class="col-lg-12 control-label">Select Subcompany Name</label>
            <div class="p-2">
              <select class="form-control" id="cmb_subcompanyforaccount" name="cmb_subcompanyforaccount">
            	</select>
   					</div>
					</div>
        	<div class="col-md-6">
						<label class="col-lg-12 control-label">Select Project Name</label>
              <div class="p-2">
                <select class="form-control" id="cmb_projectnameacc" name="cmb_projectnameacc">
              	</select>
       				</div>
					</div>						
        </div>
				<div class="col-lg-12 p-4 bg-white">
        		<div class="table-responsive bg-light">
        				<table class="table" id="viewprojectaccounts_tbl">
        					<thead class="bg-info">
        						<tr>
        						  <th>S.No</th>
        						  <th>Account Name</th>
        						  <th>Bank Name</th>
        						  <th>Account No.</th>
        						  
        						  <th style="display: none;">qbsno</th>
        						</tr>                     
        					</thead>
        					<tbody>
        						<tr>
        						  <td colspan="5" style="text-align: center;">Data not available</td>
        						</tr>
        					</tbody>
        				</table>
         	  </div>
      	</div>
				<div class="row">
					<div class="col-md-6">
						<div class="p-2"><b>Select Country Name : </b>  <select class="form-control" id="cmb_country" name="cmb_country"></select></div>
					</div>
					<div class="col-md-6">
						<div class="p-2"><b>Select Currency Type : </b>  <select class="form-control" id="cmb_currency" name="cmb_currency"></select></div>
					</div>						
				</div>
				<div class="row">
					<div class="col-md-6">					
						<label><b>Enter Bank Name : </b></label>
						<input type="text" id="txt_enterbankname" class="form-control hide_year " name="" style="width: 100%;" />
					</div>
					<div class="col-md-6">
						<label><b>Enter Account No. : </b></label>
						<input type="text" id="txt_enteraccountno" class="form-control hide_year " name="" style="width: 100%;" />
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<label><b>Enter Account Name : </b></label>
						<input type="text" id="txt_enteraccountname" class="form-control hide_year " name="" style="width: 100%;" />						
				  </div>
				  <div class="col-md-6">
						<label><b>Enter Branch Name : </b></label>
						<input type="text" id="txt_enterbranchname" class="form-control hide_year " name="" style="width: 100%;" />
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<label><b>Enter Branch Address : </b></label>
						<input type="text" id="txt_enterbranchaddrerss" class="form-control hide_year " name="" style="width: 100%;">
				 	</div>
					<div class="col-md-6">
						<label><b>Enter IFSC CODE : </b></label>
						<input type="text" id="txt_enterifsccode" class="form-control hide_year " name="" style="width: 100%;">
					</div>
				</div>
				<div class="row p-4-32 text-center">
					<input type="button" class="btn btn-info mb-4" id="addanotheraccount" value="CLICK TO ADD ANOTHER ACCOUNT" style="display: ;">
					<input type="button" class="btn btn-deepblue mb-4" id="addandexitaccount" value="CLICK TO SAVE ACCOUNTS AND EXIT" style="display: ;">
				</div>
				<div class="bg-info" id="accountlist">
					<table class="table" id="account_list">
	          <thead>
	            <tr>
	              <th>Select</th>
	              <th>Bank Name</th>
	             	<th>Account Name</th>
	             	<th>Account No.</th>
	             	<th>Branch Name</th>
	             	<th>IFSC CODE</th>
	             	<th>Branch Address</th>
	            </tr>
	          </thead>
	          <tbody></tbody>
	        </table> 
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="idaddadmin">
	<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" style="min-height: 110%;">
		<div class="modal-content" style="margin-bottom: 100px;">
			<header class="p-4 shade5">				
				<h4 id="editheadermodal" class="">ADD NEW ADMIN/USER <span onclick="document.getElementById('idaddadmin').style.display='none'" class="btn float-right hover-red">&times;</span></h4>
			</header>
			<div class="modal-body">
        <div id="div_showadminuser" style="display:;">
					<div class="row">
						<div class=""><b>Enter Name : </b> <input type="text" id="txt_enteradminname" class="form-control hide_year " name="" style="width: 100%;"></div>
					</div>
					<div class="row">
						<div class=""><b>Enter Mobile No. : </b> <input type="text" id="txt_enteradminmobileno" class="form-control hide_year " name="" style="width: 100%;"></div>
					</div>
					<div class="row">
						<div class=""><b>Enter Email Id : </b> <input type="text" id="txt_enteradminemailid" class="form-control hide_year " name="" style="width: 100%;"></div>
					</div>
					<div class="row">
						<div class=""><b>User Type : </b> </div>
					</div>
					<div class="row">
	          <div class="col-md-6">
	           <div class="form-group">
	            <input class="" type='radio' name='adminuser' id="rbt_admin" style=' transform: scale(1);'><span class="w-25 p-2 text-black ">ADMIN</span>
	           </div>
	          </div>
	          <div class="col-md-6">
	           <div class="form-group">
	            <input class="" type='radio' name='adminuser' id="rbt_user" style=' transform: scale(1);'><span class="w-25 p-2 text-black ">USER</span>
	           </div>
	          </div>
	        </div>			
					<div class="row p-4-32 w3-center" id="div_btnadmin" style="display:;">
						<input type="button" class="btn btn-info mb-4" id="addanotheradmin" value="CLICK TO ADD ANOTHER ADMIN" style="display: ;">
						<input type="button" class="btn btn-deepblue mb-4" id="addandexitadmin" value="CLICK TO ADD ADMIN AND EXIT" style="display: ;">
					</div>
				</div>
				<div class="bg-info" id="adminuserlist" style="display:none;">
					<table class="table" id="adminuser_list">
	          <thead>
	            <tr>
	              <th>Select</th>
	              <th>Type</th>
	              <th>Name</th>
	              <th>Name</th>
	              <th>Email</th>
	            </tr>
	          </thead>
	          <tbody></tbody>
	        </table> 
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="idaddadmindetails">
	<div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" style="min-height: 110%;">
		<div class="modal-content" style="margin-bottom: 100px;">
			<header class="p-4 shade5">
				<h4 id="editheadermodal" class="">ADMIN/USER DETAILS <span onclick="document.getElementById('idaddadmindetails').style.display='none'" class="btn float-right hover-red">&times;</span></h4>
			</header>
			<div class="modal-body">
				<div class="row">
					<div class="col-lg-10">
						<div class=""><b>User Type : </b> <input type="text" id="txt_showadmintype" class="form-control hide_year " name="" style="width: 100%;"> 	
					</div>
					</div>
					<div class="col-lg-2 p-4" id="div_usertype">
						<img src="../assets/img/icon/report-icon.jpg" class="rounded-circle11" style="width:45px;">
					</div>
				</div>
				<div class="row">
					<div class="col-lg-10">
						<div class=""><b>Name : </b> <input type="text" id="txt_showadminname" class="form-control hide_year " name="" style="width: 100%;">	
					</div>
					</div>
					<div class="col-lg-2 p-4" id="div_name">
						<img src="../assets/img/icon/report-icon.jpg" class="rounded-circle11" style="width:45px;">
					</div>
				</div>
				<div class="row">
					<div class="col-lg-10">
						<div class=""><b>Mobile No.: </b> <input type="text" id="txt_showadminmobileno" class="form-control hide_year " name="" style="width: 100%;">	
					</div>
					</div>
					<div class="col-lg-2 p-4" id="div_mobile">
						<img src="../assets/img/icon/report-icon.jpg" class="rounded-circle11" style="width:45px;">
					</div>
				</div>
				<div class="row">
					<div class="col-lg-10">
						<div class=""><b>Email Id.: </b> <input type="text" id="txt_showadminemailid" class="form-control hide_year " name="" style="width: 100%;">	
					</div>
					</div>
					<div class="col-lg-2 p-4" id="div_emailid">
						<img src="../assets/img/icon/report-icon.jpg" class="rounded-circle11" style="width:45px;">
					</div>
				</div>
				<div class="row">
					<div class="col-lg-10">
						<div class=""><b>Login Id.: </b> <input type="text" id="txt_showadminloginid" class="form-control hide_year " name="" style="width: 100%;">	
					</div>
					</div>
					<div class="col-lg-2 p-4" id="div_loginid">
						<img src="../assets/img/icon/report-icon.jpg" class="rounded-circle11" style="width:45px;">
					</div>
				</div>
				<div class="row">
					<div class="col-lg-10">
						<div class=""><b>Password: </b> <input type="text" id="txt_showadminpassword" class="form-control hide_year " name="" style="width: 100%;">	
					</div>
					</div>
					<div class="col-lg-2 p-4" id="div_password">
						<img src="../assets/img/icon/report-icon.jpg" class="rounded-circle11" style="width:45px;">
					</div>
				</div>
				<div id="displayuserpanel" style="display:none;">
					<div class="p-2"><h4><b>USER PANELS</b></h4></div>
					<div class="bg-info" id="userassingpanel" style="display:;">
						<table class="table" id="userassingpanel_list">
		          <thead>
		            <tr>
		              <th>Select</th>
		              <th>Project Name</th>
		              <th>Panel Name</th>	              	             	
		            </tr>
		          </thead>
		          <tbody></tbody>
		        </table> 
				 	</div>
					<div class="row p-4">
	         <div class="col-md-8">
	          <div class="form-group">           
	           <input class="" type='checkbox' name='assignpanel' style=' transform: scale(1);'><span class="w-25 p-2 text-warning "><b>If You Want To Assign Panel of This User. Please Click.</b></span>
	          </div>
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
var companyid="<?php echo $companyid; ?>";

$('#goforsubcompany_btn').click(function(){
	$('#gotosubcompanymanage').show();
	$('#id_A').show();
	$('#id_B').show();
	pnlacc="F";
});

$('#goforproject_btn').click(function(){
	
	$('#gotosubcompanymanage').show();
	$('#id_A').hide();
	$('#id_B').show();	
	pnlacc="F";

	$('#edit_subcompany').show();
	//alert(companyid);
	getsubcomapnyname(companyid,'cmb_subcompany');
	$('#id_yy').hide();
});
var pnlacc="";
$('#goforaccount_btn').click(function(){
	$('#idaddaccount').show();
	getcountry();
	getcurrency();
	getsubcomapnyname(companyid,'cmb_subcompanyforaccount');
	pnlacc="T";
	
});

$('#goforsubcompanyadd_btn').click(function(){
	$('#add_subcompany').show();
	$('#edit_subcompany').hide();
});

$('#goforeditsubcompany_btn').click(function(){
	$('#edit_subcompany').show();
	$('#add_subcompany').hide();
	var companyid="<?php echo $companyid; ?>";
	getsubcomapnyname(companyid,'cmb_subcompany');
});

jQuery(document).delegate("input[name='addprojectofsubcompany']", "click", function(){

	$('#idaddproject').show();

});

jQuery(document).delegate("input[name='addadminofsubcompany']", "click", function(){

	$('#idaddadmin').show();

});

jQuery(document).delegate("input[name='unverifyradio_login']", "click", function(){
	 var loginid = $(this).closest("tr").find("td.td_loginid").text();
	 var name = $(this).closest("tr").find("td.td_username").text();
	 var mobileno = $(this).closest("tr").find("td.td_usermobile").text();
	 var email = $(this).closest("tr").find("td.td_useremail").text();
	 var usertype = $(this).closest("tr").find("td.td_usertype").text();
	 var userid = $(this).closest("tr").find("td.td_userid").text();
	 var userpassword = $(this).closest("tr").find("td.td_userpassword").text();	 
	//alert(loginid);
	$('#idaddadmindetails').show();
	$('#txt_showadminname').val(name);
	$('#txt_showadminmobileno').val(mobileno);
	$('#txt_showadminemailid').val(email);
	$('#txt_showadmintype').val(usertype);
	$('#txt_showadminpassword').val(userpassword);
	$('#txt_showadminloginid').val(userid);
	if (usertype=="User") 
	{
		$('#displayuserpanel').show();
	}

});

$('#addanotherproject').click(function(){
	var tableid='projectlist';
	var pname=$('#txt_enterprojectname').val();
	$("#"+tableid+" tbody").append("<tr><td class='text-left'><input type='radio' style='transform: scale(1.8);' name='ppp_radio_td' value='' /></td><td style='background-color:'>"+pname+"</td></tr>");
	$('#txt_enterprojectname').val('');
});

$('#addanotheraccount').click(function(){
	var tableid='account_list';
	var bankname=$('#txt_enterbankname').val();
	var accountname=$('#txt_enteraccountname').val();
	var accountno=$('#txt_enteraccountno').val();
	var branchname=$('#txt_enterbranchname').val();
	var ifsccode=$('#txt_enterifsccode').val();
	var branchaddress=$('#txt_enterbranchaddrerss').val();
	var countryid=$('#cmb_country').val();
	var currencytype=$('#cmb_currency').val();
	$("#"+tableid+" tbody").append("<tr><td class='text-left'><input type='radio' style='transform: scale(1.8);' name='acc_radio_td' value='' /></td><td style='background-color:'>"+bankname+"</td><td style='background-color:'>"+accountname+"</td><td style='background-color:'>"+accountno+"</td><td style='background-color:'>"+branchname+"</td><td style='background-color:'>"+ifsccode+"</td><td style='background-color:'>"+branchaddress+"</td><td style='background-color:'>"+countryid+"</td><td style='background-color:'>"+currencytype+"</td></tr>");
	$('#txt_enterbankname').val('');
	$('#txt_enteraccountname').val('');
	$('#txt_enteraccountno').val('');
	$('#txt_enterbranchname').val('');
	$('#txt_enterifsccode').val('');
	$('#txt_enterbranchaddrerss').val('');
});

$('#addandexitproject').click(function(){
	var form_data = new FormData();
	form_data.append('companyid', companyid);
	var subcompanyid=$('#cmb_subcompany').val();
	form_data.append('subcompanyid', subcompanyid);
	var projectname=$('#txt_enterprojectname').val();
	form_data.append('projectname', projectname);
    $.ajax({
        type: "POST",
        url: "model/usermodel.php?action=addnewproject",
        data: form_data,
        contentType: false,
        processData: false,
        cache:  false,
        dataType: "json",
        success: function(res){
        console.log(res);       
          if(res.length>0){
             alert(res);
             $('#idaddproject').hide();
             $('#txt_enterprojectname').val('');
             getprojectsname(companyid,subcompanyid,'cmb_project');
          }
          $("#beforesenmodal").hide();        
        },beforeSend: function(res){
          $("#beforesenmodal").show();
        }
    });       
});

$('#addandexitaccount').click(function(){
	var form_data = new FormData();
	var rowCountaccount = $('#account_list tr').length;
  var subcompid=$('#cmb_subcompany').val();
  var projid=$('#cmb_project').val();
  var subcompid2=$('#cmb_subcompanyforaccount').val();
  var projid2=$('#cmb_projectnameacc').val();
  if (subcompid=="" || subcompid==null) {
  	subcompid=subcompid2;
  }
  if (projid==""|| projid==null) 
  {
  	projid=projid2;
  }
   var TableData;
   if (rowCountaccount>1) 
   {
   	TableData = storeTblValues();
    TableData = JSON.stringify(TableData);
    function storeTblValues()
    {
      var TableData = new Array();
      $('#account_list tr').each(function(row, tr){        
          TableData[row]={
            "bankname" : $(tr).find('td:eq(1)').text()
            ,
            "accountname" : $(tr).find('td:eq(2)').text()
            ,
            "accountno" : $(tr).find('td:eq(3)').text()
            ,
            "branchname" : $(tr).find('td:eq(4)').text()
            ,
            "ifsccode" : $(tr).find('td:eq(5)').text()
            ,
            "branchaddress" : $(tr).find('td:eq(6)').text()
            ,
            "countryid" : $(tr).find('td:eq(7)').text()
            ,
            "currency" : $(tr).find('td:eq(8)').text()
          }          
      }); 
      TableData.shift();  // first row will be empty - so remove
      return TableData;
    }
    form_data.append('pTableData', TableData);
    form_data.append('subcompanyid', subcompid);
    form_data.append('projectid', projid);
    form_data.append('companyid', companyid);
   }else {
   	alert('Please Add Account');
   	return;
   }
     $.ajax({
        type: "POST",
        url: "model/usermodel.php?action=addnewaccount",
        data: form_data,
        contentType: false,
        processData: false,
        cache:  false,
        dataType: "json",
        success: function(res){
        console.log(res);        
        if (res==true) 
        {
        	alert("Account Successfully Added.");
        }           
             getprojectaccountdetails(companyid,subcompid,projid,'projectaccounts_tbl');
              getprojectaccountdetails(companyid,subcompid,projid,'viewprojectaccounts_tbl');
              if (pnlacc=="F") 
              {
              	  $('#idaddaccount').hide();
              }
              clearfunction();
        }
    });
});

$('#addanotheradmin').click(function(){
	
	$('#adminuserlist').show();
	var usertype="";
	if( $('#rbt_admin').is(':checked') ){
    usertype="Admin";
	}
	else if ($('#rbt_user').is(':checked')) {
	    usertype="User";
	}
	var tableid='adminuser_list';	
	var name=$('#txt_enteradminname').val();
	var mobile=$('#txt_enteradminmobileno').val();
	var email=$('#txt_enteradminemailid').val();

	$("#"+tableid+" tbody").append("<tr><td class='text-left'><input type='radio' style='transform: scale(1.8);' name='ppp_radio_td' value='' /></td><td style='background-color:'>"+usertype+"</td><td style='background-color:'>"+name+"</td><td style='background-color:'>"+mobile+"</td><td style='background-color:'>"+email+"</td></tr>");
	$('#txt_enteradminname').val('');
	$('#txt_enteradminmobileno').val('');
	$('#txt_enteradminemailid').val('');

});

$('#addandexitadmin').click(function(){
	var XYZ = [];
  var mytableid = "#adminuser_list" + ' tr';
  $(mytableid).each(function(row, tr)
  {
        XYZ.push($(tr).find('td:eq(1)').text()+'#'+$(tr).find('td:eq(2)').text()+'#'+$(tr).find('td:eq(3)').text()+'#'+$(tr).find('td:eq(4)').text());
  }); 
  for(var j=1; j<XYZ.length; j++){
    var z = j;
    arrayOfStrings = XYZ[j].split("#");
    $("#addadminuser_list tbody").append("<tr class='expenses_tr' style='background-color: #248f8f;'><td class='z_td'>"+z+"</td><td style='text-align:left;'>"+arrayOfStrings[0]+"</td><td style='text-align:left;'>"+arrayOfStrings[1]+"</td><td style='text-align:left;'>"+arrayOfStrings[2]+"</td><td style='text-align:left;'>"+arrayOfStrings[3]+"</td></tr>");
 	}
  $('#idaddadmin').hide();
});

$('#btn_submitsubcompany').click(function(){
	var form_data = new FormData();
	var companyid="<?php echo $companyid; ?>";
  form_data.append('companyid', companyid);
  var subcomapmyname=$('#txt_subcompname').val();
  form_data.append('subcomapmyname', subcomapmyname);
  var subcomapmyregistrationno=$('#txt_registrstionno').val();
  form_data.append('subcomapmyregistrationno', subcomapmyregistrationno);
  var subcomapmyemail=$('#txt_subcompemail').val();
  form_data.append('subcomapmyemail', subcomapmyemail);
  var subcomapmymobilno=$('#txt_subcompmobileno').val();
  form_data.append('subcomapmymobilno', subcomapmymobilno);
  var rowCountproject = $('#addedproject_list tr').length;
  var rowCountadmin = $('#addadminuser_list tr').length;
  form_data.append('rowCountproject', rowCountproject);
  form_data.append('rowCountadmin', rowCountadmin);
  var TableData;
  var TableData2;
  if (rowCountproject>1) 
  {
   	TableData = storeTblValues();
    TableData = JSON.stringify(TableData);
    function storeTblValues()
    {
      var TableData = new Array();
      $('#addedproject_list tr').each(function(row, tr){
          TableData[row]={
            "projectname" : $(tr).find('td:eq(1)').text()
          }          
      }); 
      TableData.shift();  // first row will be empty - so remove
      return TableData;
    }
	  //alert(TableData);
    form_data.append('pTableData', TableData);
  }
  if (rowCountadmin>1) 
  {
	 	TableData2 = storeTblValues2();
   	TableData2 = JSON.stringify(TableData2);
   	function storeTblValues2()
   	{
      var TableData2 = new Array();
      $('#addadminuser_list tr').each(function(row, tr){
          TableData2[row]={
            "usertype" : $(tr).find('td:eq(1)').text()
            ,"name" :$(tr).find('td:eq(2)').text()
            ,"mobileno" :$(tr).find('td:eq(3)').text()
            ,"email" :$(tr).find('td:eq(4)').text()
          }          
      }); 
      TableData2.shift();  // first row will be empty - so remove
      return TableData2;
    }
   	form_data.append('pTableData2', TableData2);
  }
  $.ajax({
    type: "POST",
    url: "model/usermodel.php?action=addnewsubcompany",
    data: form_data,
    contentType: false,
    processData: false,
    cache:  false,
    dataType: "json",
    success: function(res){
    //console.log(res);
      if(res.length>0){
         alert(res);
         $('#gotosubcompanymanage').hide();
      }
      $("#beforesenmodal").hide();
    
    },beforeSend: function(res){
      $("#beforesenmodal").show();
    }
  });
});

$("#cmb_subcompany").on("change", function(){
		$('#id_xx').show();
		$('#id_yy').show();
		var subid=$('#cmb_subcompany').val();
		getprojectsname(companyid,subid,'cmb_project');
		getlogindetails(companyid,subid,'logindetails_tbl');
		getcountry();
		getcurrency();
});

$("#cmb_subcompanyforaccount").on("change", function(){
	var subid=$('#cmb_subcompanyforaccount').val();
	getprojectsname(companyid,subid,'cmb_projectnameacc');
});
$('#add_p').click(function(){
	$('#idaddproject').show();
});
$('#add_acc').click(function(){
	$('#idaddaccount').show();
	var projectname=$("#cmb_project option:selected").text();
	$('#id_lableproject').text('For '+ projectname);
});

$('#add_user').click(function(){
	$('#idadduser').show();
	 var subcmpname=$("#cmb_subcompany option:selected").text();
	 $('#id_lablesubcomp').text('For The Subcompany: '+ subcmpname);
	  var subcompid=$('#cmb_subcompany').val();
	 getprojectsall(companyid,subcompid,'project_tbl');
	 getmodulelist(companyid,subcompid,'slt_moudulename');
});

$('#div_usertype').click(function(){
	alert('Edit user type');
});

$('#div_name').click(function(){
	alert('Edit Name');
});

$('#div_mobile').click(function(){
	alert('Edit mobile');
});
$('#div_emailid').click(function(){
	alert('Edit email');
});
$('#div_loginid').click(function(){
	alert('Edit user id');
});
$('#div_password').click(function(){
	alert('Edit user password');
});


$("#cmb_project").on("change", function(){
  		$('#id_paccdetails').show();
  		var projid=$('#cmb_project').val();
  		var subid=$('#cmb_subcompany').val();
  		// var companyid="<?php echo $companyid; ?>";
  		getprojectaccountdetails(companyid,subid,projid,'projectaccounts_tbl');
  });
$("#cmb_projectnameacc").on("change", function(){
  		
  		var projid=$('#cmb_projectnameacc').val();
  		var subid=$('#cmb_subcompanyforaccount').val();
  		// var companyid="<?php echo $companyid; ?>";
  		getprojectaccountdetails(companyid,subid,projid,'viewprojectaccounts_tbl');
  });
var select_projectname="";
var select_projectid="";

$(document).on('click', '.slt_forproject', function(){
	  select_projectname = $(this).closest("tr").find("td.projectname_td").text();
	  select_projectid = $(this).closest("tr").find("td.projectid_td").text();
});
$('#saveauthorization').click(function(){
  var form_data = new FormData();
 	var logintype=$('#login_type').val();
 	var loginname=$('#txt_loginname').val();
 	var loginmobile=$('#loginmobile').val();
 	var loginemail=$('#loginemail').val();
 	var subid=$('#cmb_subcompany').val();
 	form_data.append('companyid', companyid);
 	form_data.append('subcompanyid', subid);
 	form_data.append('logintype', logintype);
 	form_data.append('loginname', loginname);
 	form_data.append('loginmobile', loginmobile);
 	form_data.append('loginemail', loginemail);
	if (logintype=="") 
 	{
 		alert('Please Select Login Type..');

 		return;
 	}
 	if (select_projectname!="") 
 	{
 		form_data.append('select_projectname', select_projectname);
 		form_data.append('select_projectid', select_projectid);
 	}else{
 		alert('Please Select Project..');
 		return;
 	}
 	var XYZ = [];
  var mytableid = "#slt_moudulename" + ' tr';
  $(mytableid).each(function(row, tr)
  {
    if($(tr).find('td:eq(0) input[type="checkbox"]').prop('checked')==true)
    {
    	XYZ.push($(tr).find('td:eq(1)').text()+'#'+$(tr).find('td:eq(2)').text());
    }
  });
  $("#addadminpanel_list tbody").empty();
  for(var j=0; j<XYZ.length; j++){
      var z = j+1;     
       arrayOfStrings = XYZ[j].split("#");
      $("#addadminpanel_list tbody").append("<tr class='expenses_tr' style='background-color: #248f8f;'><td class='z_td'>"+z+"</td><td style='text-align:left;'>"+arrayOfStrings[0]+"</td><td style='text-align:left;'>"+arrayOfStrings[1]+"</td></tr>");
    } 
    var TableData;
    TableData = storeTblValues();
			TableData = JSON.stringify(TableData);
		 function storeTblValues()
		 {
      var TableData = new Array();
      $('#addadminpanel_list tr').each(function(row, tr){        
          TableData[row]={
            "panelname" : $(tr).find('td:eq(1)').text()
            ,
             "panelfolder" : $(tr).find('td:eq(2)').text()
          }          
      }); 
      TableData.shift();  // first row will be empty - so remove
      return TableData;
    }  
    form_data.append('pTableData', TableData);
 		$.ajax({
        type: "POST",
        url: "model/usermodel.php?action=addauthorization",
        data: form_data,
        contentType: false,
        processData: false,
        cache:  false,
        dataType: "json",
        success: function(res){
        // console.log(res);
          if(res=="Success"){
             alert("AUTHORIZATION SUCCESS.");
             $('#idadduser').hide();
             getlogindetails(companyid,subid,'logindetails_tbl');
          }
          $("#beforesenmodal").hide();
        
        },beforeSend: function(res){
          $("#beforesenmodal").show();
        }
    });
 });

function getsubcomapnyname(companyid,subcompid){
	var form_data = new FormData();
	form_data.append('companyid', companyid);	
	$.ajax({
        type: "POST",
        url: "model/usermodel.php?action=getsubcompanyname",
        data: form_data,
        contentType: false,
        processData: false,
        cache:  false,
       dataType: "json",
        success: function(res){
        //console.log(res);
        $("#"+subcompid).empty();
          if(res.length>0){
             if(res.length>0){
                $("#"+subcompid).append("<option value='' selected disabled>Select Subcompany Name</option");
              }
              for(var i=0; i<res.length; i++){
                $("#"+subcompid).append("<option value='"+res[i].subid+"'>"+res[i].subcompanyname+"</option>");
              }
              if (res.length==1) 
            	{
                //document.getElementById(subcompid).selectedIndex = "1";
                //$(subcompid).trigger("change");
          		}
          }
        }
    });
}

function getcountry() {
	var form_data = new FormData();	
	$.ajax({
        type: "POST",
        url: "model/usermodel.php?action=getcountry",
        data: form_data,
        contentType: false,
        processData: false,
        cache:  false,
        dataType: "json",
        success: function(res){
        console.log(res);        
        $("#cmb_country").empty();
          if(res.length>0){
            $("#cmb_country").append("<option value='' selected disabled>Select Country Name</option");
              for(var i=0; i<res.length; i++){
                $("#cmb_country").append("<option value='"+res[i].counid+"'>"+res[i].countryname+"</option>");
              }
          }
        }
    });
}

function getcurrency() {
	var form_data = new FormData();
	$.ajax({
        type: "POST",
        url: "model/usermodel.php?action=getcurrency",
        data: form_data,
        contentType: false,
        processData: false,
        cache:  false,
        dataType: "json",
        success: function(res){
        $("#cmb_currency").empty();
          if(res.length>0){
            $("#cmb_currency").append("<option value='' selected disabled>Select Currency Type</option");
            for(var i=0; i<res.length; i++){
              $("#cmb_currency").append("<option value='"+res[i].currency+"'>"+res[i].currencyval+"</option>");
            }
          }
        }
    });
}

function getprojectsname(companyid,subid,projid) {
	var form_data = new FormData();
	form_data.append('companyid', companyid);
	form_data.append('subcompanyid', subid);
	$.ajax({
        type: "POST",
        url: "model/usermodel.php?action=getprojectname",
        data: form_data,
        contentType: false,
        processData: false,
        cache:  false,
        dataType: "json",
        success: function(res){
        $("#"+projid).empty();
          if(res.length>0){
             if(res.length>0){
                $("#"+projid).append("<option value='' selected disabled>Select Project Name</option");
              }
              for(var i=0; i<res.length; i++){
                $("#"+projid).append("<option value='"+res[i].projectid+"'>"+res[i].projectname+"</option>");
              }
          }
        }
    });
}

function getprojectsall(companyid,subid,tblid) {
	var form_data = new FormData();
	form_data.append('companyid', companyid);
	form_data.append('subcompanyid', subid);

	$.ajax({
        type: "POST",
        url: "model/usermodel.php?action=getprojectname",
        data: form_data,
        contentType: false,
        processData: false,
        cache:  false,
        dataType: "json",
        success: function(res){
        //console.log(res);
         $("#"+tblid+" tbody").empty();
          if(res.length>0){
             
              for(var i=0; i<res.length; i++){
               
                $("#"+tblid+" tbody").append("<tr class='invoice_tr' style='background-color: ;'><td style='text-align:left;'><input type='radio' class='slt_forproject' name='slt_forproject' style='transform: scale(2.5);'  /></td><td class='projectname_td text-left'>"+res[i].projectname+"</td><td class='projectid_td text-left'>"+res[i].projectid+"</td></tr>");
              }
          }
         
        
        },beforeSend: function(res){
         
        }
    });
}
function getprojectaccountdetails(companyid,subid,projid,projectaccountstbl) {
	var form_data = new FormData();
	form_data.append('companyid', companyid);
	form_data.append('subcompanyid', subid);
	form_data.append('projectid', projid);
	$.ajax({
        type: "POST",
        url: "model/usermodel.php?action=getprojectaccounts",
        data: form_data,
        contentType: false,
        processData: false,
        cache:  false,
        dataType: "json",
        success: function(res){
        $("#"+projectaccountstbl+" tbody").empty();
          if(res.length>0){
            var z=0;
              for(var i=0; i<res.length; i++){
              	z=i+1;
                $("#"+projectaccountstbl+" tbody").append("<tr class=''><td class='text-center'>"+z+"</td><td style='text-align:center;' class=''>"+res[i].accountname+"</td><td  class=''>"+res[i].bankname+"</td><td  class=''>"+res[i].accountno+"</td><td  class='' style='display:none;'>"+res[i].accountid+"</td></tr>");
              }
          }
        }
    });
}
function getlogindetails(companyid,subid,projectaccountstbl) {
	var form_data = new FormData();
	form_data.append('companyid', companyid);
	form_data.append('subcompanyid', subid);		
	$.ajax({
        type: "POST",
        url: "model/usermodel.php?action=getlogindetails",
        data: form_data,
        contentType: false,
        processData: false,
        cache:  false,
        dataType: "json",
        success: function(res){
        $("#"+projectaccountstbl+" tbody").empty();
          if(res.length>0){
            var z=0;
              for(var i=0; i<res.length; i++){
              	z=i+1;
                $("#"+projectaccountstbl+" tbody").append("<tr class=''><td class='text-center'>"+z+"</td><td style='text-align:center;' class='td_username'>"+res[i].authorisedperson+"</td><td  class='td_usermobile'>"+res[i].mobileno+"</td><td  class='td_useremail'>"+res[i].email+"</td><td  class='td_usertype' style='display:;'>"+res[i].user_type+"</td><td  class='td_loginid' style='display:none;'>"+res[i].id+"</td><td><input type='radio' name='unverifyradio_login' class='unverifyradio_login form-control' style='transform: scale(2.3);' /></td><td  class='td_userid' style='display:none;'>"+res[i].userid+"</td><td  class='td_userpassword' style='display:none;'>"+res[i].password+"</td></tr>");
              }
          }
        }
    });
}
function getmodulelist(companyid,subcompanyid,tblmodulid){
  $.ajax
    ({
      type: "POST",
      url: "model/usermodel.php?action=fromlogingetmodulelist",
      data: {companyid: companyid, subcompanyid: subcompanyid},
      dataType:'JSON',
      success: function(res)
      {
        if(res.length>0){
      		$("#"+tblmodulid+" tbody").empty();
	        for (var j=0;j<res.length; j++){
	            $("#"+tblmodulid+" tbody").append("<tr class='module_tr' style='background-color: ;'><td style='text-align:left;'><input type='checkbox' class='slt_module' name='slt_module' style='transform: scale(2.5);'  /></td><td class='text-left'>"+res[j].modulename+"</td><td class='text-left' style='display:none;'>"+res[j].modulenamefolder+"</td></tr>");
	        }
    		}
      }
    });
}
function clearfunction() {
	$('#txt_enterbankname').val('');
	$('#txt_enteraccountno').val('');
	$('#txt_enteraccountname').val('');
	$('#txt_enterbranchname').val('');
	$('#txt_enterbranchaddrerss').val('');
	$('#txt_enterbranchaddrerss').val('');
	document.getElementById('cmb_country').selectedIndex = "0";
	document.getElementById('cmb_currency').selectedIndex = "0";
	$('#account_list').empty();
	pnlacc="";
}
</script>