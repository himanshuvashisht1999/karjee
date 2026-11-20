<?php
include('../header.php');
?>
<div class="main-container">
    <div class="row text-center">
        <div class="col-sm-12">
            <h2 class=" thin">Upload New User Login Detail </h2>
        </div>
  	</div>
  	<div class="row mt-2">
  		<div class="col-md-8">
        </div>
  		<div class="col-md-4">
  		</div>
  	</div>
    <div class="row mt-2">
        <div class="col-md-6 mt-2">
            <label class="">Upload User Login Details</label>
            <a href="../attachementfile/formatedexcelsheet/newlogin_excel_format.xlsx" class="pull-right" target="_blank">Download upload formated sheet</a>
            <input type="file" class="form-control" name="uploaduserlogin" id="uploaduserlogin"  accept=".xlsx, .xls" />
        </div>
        <div class="col-md-6 mt-2"><br/>
            <button type="button" class="btn btn-success mt-2" id="uploaduserloginbtn">Upload User Login Info</button>
        </div>
    </div>
</div>
<?php
include('../footer.php');
?>
<script type="text/javascript">
$("#uploaduserloginbtn").on("click", function () {
    uploadnewlogindetails_excelfile("hidden_rooturl","hidden_companyid","hidden_subcompanyid","hidden_projectid","uploaduserlogin","uploaduserloginbtn",function (res) {
            if (res && res.length > 0) {
                // Start building the table
                var tableHtml = `
                    <table class='table table-light'>
                        <thead>
                            <tr>
                                <th>Login Id</th>
                                <th>Login Password</th>
                                <th>User Name</th>
                                <th>User Mobile No</th>
                                <th>User Email Address</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>`;
                // Add table rows dynamically
                for (var i = 0; i < res.length; i++) {
                    tableHtml += `
                        <tr>
                            <td>${res[i].loginid}</td>
                            <td>${res[i].loginpassword}</td>
                            <td>${res[i].username}</td>
                            <td>${res[i].usermobile}</td>
                            <td>${res[i].useremailaddress}</td>
                            <td>${res[i].message}</td>
                        </tr>`;
                }

                // Close table tags
                tableHtml += `
                        </tbody>
                    </table>`;

                // Append the table to the webpage body div
                $("#webpagebodydiv").html(tableHtml);

                // Update modal content and show it
                $("#modalheader").html("Status of New Login Upload Sheet");
                $("#webpagebodyview_modal").modal("show");
            } else {
                alert("No data available in the response!");
            }
        }
    );
});
</script>