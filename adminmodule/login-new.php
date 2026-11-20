<?php
include('../header.php');
$editloginid="";
$loginuserid ="";
$loginusername ="";
$loginrolename ="";
$logintype ="";
$loginroleid ="";
$loginmobileno = "";
$loginemailid = "";
if(isset($_GET['edlid'])){
    $editloginid=$_GET['edlid'];
    $logininfo = loginuserdetailsfrommaster($authconn,$editloginid,"loginid",$editloginid);
    if(count($logininfo)>0){
        foreach ($logininfo as $lgkey) {
            $loginuserid = $lgkey['loginid'];
            $loginusername = $lgkey['loginname'];
            $loginrolename = $lgkey['loginrolename'];
            $logintype = $lgkey['loginrolename'];
            $loginroleid = $lgkey['loginroleid'];   
            $loginmobileno = $lgkey['mobileno'];   
            $loginemailid = $lgkey['emailid'];
            $loginpassword =  $lgkey['loginpassword'];
            $departmentid =  $lgkey['departmentid'];
        }
    }
    $btntext="Update Login Details";
}else{
    $btntext="Save Login Details";
}
?>
<div class="container">
    <div class="card">
        <div class="card-header text-center">
            <h3><?php echo $loginusername!=""?strtoupper($loginusername):"New User"; ?> Login Detail </h3>

        </div>
        <div class="cord-body p-2">
            
          	<div class="row mt-2">
          		<div class="col-md-8">
                    <!-- <p class="text-dark">Note:-</p>
                    <ul>
                        <li class="text-info">** if login passowrd is not set than default password should be user mobile no.</li>
                    </ul> -->
                </div>
          		<div class="col-md-4">
          			<a class="btn btn-info pull-right" id="addnewloginbtn" href="<?php echo $baseurl; ?>loginmanagement.php?k=<?php echo $_GET['k']; ?>">Back</a>
          		</div>
          	</div>
            <div class="row mt-2">
                <div class="col-md-4 mt-2">
                    <label class="text-dark">What is the login id?</label>
                    <input type="text" class="form-control" placeholder="Enter login id" id="userloginid" value="<?php echo $loginuserid; ?>" autocomplete="off" />
                </div>
                <div class="col-md-4 mt-2">
                    <label class="text-dark">What is the login password?<sup>**</sup> </label>
                    <input type="text" class="form-control" placeholder="Enter login password" id="userloginpassword" value="<?php echo $loginpassword; ?>" autocomplete="off" />
                </div>
                <div class="col-md-4 mt-2">
                    <label class="text-dark">What is the login person name?</label>
                    <input type="text" class="form-control" placeholder="Enter user name" id="userloginname" value="<?php echo $loginusername; ?>" autocomplete="off" />
                </div>        
            </div>
            <div class="row mt-2">
                <div class="col-md-4 mt-2">
                    <label class="text-dark">What is the user mobile no?</label>
                    <input type="text" class="form-control" placeholder="Enter user mobile no" id="mobileloginusre" value="<?php echo $loginmobileno; ?>" autocomplete="off" />
                </div>
                <div class="col-md-4 mt-2">
                    <label class="text-dark">What is the user email address?</label>
                    <input type="text" class="form-control" placeholder="Enter user email address" id="emailloginuser"  value="<?php echo $loginemailid; ?>" autocomplete="off" />
                </div>
                <div class="col-md-4 mt-2">
                    <label class="text-dark">What is the role of login user?</label>
                    <select class="form-control" id="roleofuser"></select>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6 mt-2">
                    <label class="text-dark">What is the Departement of login user?</label>
                    <select class="form-control" id="departmentofuser"></select>
                </div>
                <div class="col-md-6" style="display: none;">
                    <p><b class="text-dark">Is this active user?</b></p>
                    <label class="text-dark"><input type="radio" name="isactiveuser" value="yes" checked /> YES</label>
                    <label class="text-dark"><input type="radio" name="isactiveuser" value="no" /> NO</label>
                </div>
            </div>
            <div id="doauthorizedprj_div" style="display:none;">
                <div class="row mt-4" id="projectlistdiv">
                    <div class="col-md-12">
                        <h5 class="text-center text-info">Project(s) for Authorization of <?php echo $companyname; ?></h5>
                    </div>
              		<div class="col-md-12">
                        <table id="loginprojectlist_tbl" class="table table-bordered table-light table-striped">
              				<thead>
              					<tr>
              						<th style="width: 50px; text-align: center;">#</th>
                                    <th>Project Name</th>
              					</tr>
              				</thead>
              				<tbody></tbody>
              			</table>
              		</div>
                </div>
            </div>
            <div id="doauthorizedmdl_div" style="display:block;">
                <div class="row mt-4">
                    <div class="col-md-12">
                        <label class="text-dark"><input type="checkbox" id="doauthorizedmodule" onclick="onclickcheckboxdivhideshow('doauthorizedmodule','moduleslistdiv');" /> Do you want to authorized Module for Accessing?</label>
                    </div>
                </div>
                <div class="row mt-4" id="moduleslistdiv" style="display: none;">
                    <div class="col-md-12">
                        <!-- <label class="text-dark">Modules For Authorization</label> -->
                        <div class="col-md-12">
                            <h5 class="text-center">Module(s) for Authorization</h5>
                        </div>
                        <table id="loginmodulelist_tbl" class="table table-bordered table-light table-striped">
                            <thead>
                                <tr>
                                    <th style="width:100px; text-align: center;">  </th>
                                    <th>Module Name</th> 
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
              	</div>
            </div>
            <div class="row mt-4">
                <div class="col-md-12 text-center">
                    <button id="saveuserdetails" class="btn btn-info" onclick="savetodbuserlogindetails('hidden_rooturl','hidden_companyid','hidden_subcompanyid','userloginid','userloginpassword','userloginname','mobileloginusre','emailloginuser','roleofuser','isactiveuser','loginprojectlist_tbl','loginmodulelist_tbl','loginname','saveuserdetails','departmentofuser');"><?php echo $btntext; ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include('../footer.php');
?>
<!-- <script type="text/javascript" src="../js/js_selft/admindashboard.js"></script> -->
<script type="text/javascript">

    $(document).on("input", "#userloginid", function(){
        var loginid=$(this).val();
        logindetails_fromloginid("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",loginid,function(res){
            $("#userloginpassword").val('');
            $("#userloginname").val('');
            $("#mobileloginusre").val('');
            $("#emailloginuser").val('');
            $("#roleofuser").val('').trigger("change");
             // console.log(res);
            if(res.length>0){
                for(var j=0; j<res.length; j++){
                    $("#userloginpassword").val(res[j].loginpassword);
                    $("#userloginname").val(res[j].loginname);
                    $("#mobileloginusre").val(res[j].mobileno);
                    $("#emailloginuser").val(res[j].emailid); 
                    $("#roleofuser").val(res[j].loginroleid).trigger("change");
                    $("#departmentofuser").val(res[j].departmentid).trigger("change");
                    user_rolelistdropdown_html("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid",res[j].loginroleid,"roleofuser");
    
                    projectlist_forauthorizaton_html("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",loginid,"loginprojectlist_tbl");
                    modulelist_frommaster_html("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid",loginid,"loginmodulelist_tbl");

                }
            }else{
                 user_rolelistdropdown_html("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","<?php echo $loginroleid; ?>","roleofuser");
    
                projectlist_forauthorizaton_html("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","<?php echo $loginuserid; ?>","loginprojectlist_tbl");
                modulelist_frommaster_html("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","<?php echo $loginuserid; ?>","loginmodulelist_tbl");
            }
        });
    });

    department_html("hidden_rooturl", "departmentofuser","<?php echo $departmentid; ?>");
    user_rolelistdropdown_html("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","hidden_loginid","<?php echo $loginroleid; ?>","roleofuser");   
    
    projectlist_forauthorizaton_html("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","<?php echo $loginuserid; ?>","loginprojectlist_tbl");
    modulelist_frommaster_html("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","<?php echo $loginuserid; ?>","loginmodulelist_tbl");
 

</script>