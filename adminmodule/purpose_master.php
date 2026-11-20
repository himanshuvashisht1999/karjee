<?php
include('../header.php');
?>
<div class="main-container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center ">PURPOSE MASTER</h2>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-2">
            <a class="btn btn-deepblue btn-sm" onclick="history.back();">Back</a>
        </div>
        <div class="col-md-10">
            <!-- <button class="btn btn-info m-2 btn-sm float-right" onclick="openModalForAddCreditCard('hidden_rooturl');"><i class='fa fa-plus' style='font-size: 18px;'></i> Add New Credit Card</button> -->

            <button class="btn btn-success btn-sm m-2 pull-right reloadpurpose" id="reloadpurposemaster"><i class="fa fa-sync"></i> Refresh</button>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="btn-deepblue p-2">
                <label style="margin: 4px 8px;"><input type="radio"  name="purposetype" class="purposetype" value="widthrown" checked />  EXPENSES PURPOSE</label>
                <label style="margin: 4px 8px;"><input type="radio"  name="purposetype" class="purposetype" value="deposit" />  DEPOSIT PURPOSE</label>
                <button class="btn btn-sm mb-0 mr-1 ml-1 btn-deepblue pull-right" onclick="gridtoexcel_withfilter_chunkNEW('purposelistwithcat_tbl', 'purposemasterlist', 'landscape',function(res){})"><i class="fa fa-download" aria-hidden="true"></i> Export All Purpose</button>
                <button class="btn btn-sm mb-0 mr-1 ml-1 btn-success pull-right" id="previewactivepurpose"><i class="fa fa-eye" aria-hidden="true"></i> Previe visible Purpose</button>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive bg-light">
                <table id="purposelistwithcat_tbl" class="table table-bordered table-light table-striped">
                    <thead>
                        <tr>
                            <th colspan="5"><h4>Categorized purpose master of project <b><?php echo $projectname; ?></b></h4></th>
                            <th colspan="2" class="noexport"><input type="text" class="form-control" id="purposefilterinput" oninput="filterTable('#purposefilterinput', '#purposelistwithcat_tbl')" placeholder="search here....." /></th>
                        </tr>
                        <tr>
                            <th style="width: 50px; text-align: center;">S.No</th>
                            <th class="sortable" style="cursor:pointer;">Category Name <i class="fa fa-sort" style="color:blue;"></i></th>
                            <th class="sortable" style="cursor:pointer;">Sub-Category Name <i class="fa fa-sort" style="color:blue;"></i></th>
                            <th>Purpose Name</th>
                            <th>Provide Code</th>
                            <th class="noexport" style="width: 150px; text-align: center;">Visible</th>
                            <th class="noexport" style="width: 100px; text-align: center;">Action</th>
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
    var purposetype = $("input[name='purposetype']:checked").val();
    getallpurposewithprojectid_withstatus('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid', function (res){
        const tbody = $("#purposelistwithcat_tbl tbody");
        tbody.empty(); // Clear the table body
        if (res.length > 0) {
            // console.log(res);
            res.forEach((item, index) => {
                const isVisibleYes = item.visiblestatus === 'yes' ? 'checked' : '';
                const isVisibleNo = item.visiblestatus === 'no' ? 'checked' : '';
                let array = ['0,3,4,6,7,8'];
                let elements = array[0].split(',');
                
                let isPlusButton = elements.includes(item.pcategorycode) ? " Fixed" : '<i class="fa fa-plus addrowprp" style="font-size: 22px; margin: 2px; color: rgb(144, 12, 63); cursor: pointer;"></i>';
                let isEditButton = elements.includes(item.pcategorycode) ? " Fixed" : '<i class="fa fa-edit editrowprp" style="font-size: 20px; margin: 2px; color: rgb(120, 112, 63); cursor: pointer;"></i>';
                
                tbody.append(`<tr data-mpptype="widthrown" data-mpcatid="${item.pcategorycode}" data-mpcatname="${item.pcategory}" data-mpsubcatid="${item.psub_categoryid}" data-mpsubcatname="${item.psub_category}" data-mpcode="${item.purposecode}" data-mpname="${item.purpose}" data-mprvcode="${item.provided_code}">
                        <td style="width: 50px; text-align: center;">${index + 1}</td>
                        <td class="ed_mpcategoryname">${item.pcategory}</td>
                        <td class="ed_mpsubcategoryname">${item.psub_category}</td>
                        <td class="ed_mppurposename">${item.purpose}</td>
                        <td class="ed_mpprvcode">${item.provided_code}</td>
                        <td class="ed_mpvisible">
                            <label"><input type="radio" name="visiblep_${index}" class="visiblep" value="yes" ${isVisibleYes} /> YES</label>
                            <label><input type="radio" name="visiblep_${index}" class="visiblep" value="no" ${isVisibleNo} /> NO</label>
                        </td>
                        <td class="accedit_td text-center">
                            <button class='btn btn-sm btn-deepblue updpurposebtn d-none' id='updpurposebtn_${index}' style='margin:2px; color:rgb(144, 12, 32); cursor:pointer;'> <i class="fa fa-save" style='color: rgb(144, 12, 32)'></i> update</button>
                            ${isPlusButton} ${isEditButton}
                        </td>
                    </tr>
                `);
            });
        } else {
            tbody.append(`
                <tr>
                    <td colspan="7" class="text-center">Data not available</td>
                </tr>
            `);
        }
    });


    $(document).on("click", ".purposetype, .reloadpurpose", function(){
        var purposetype = $("input[name='purposetype']:checked").val();
        if(purposetype=="deposit" || purposetype=="credit"){
            getallpurposewithprojectid_creditwithstatus('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid', function (res){
                const tbody = $("#purposelistwithcat_tbl tbody");
                tbody.empty(); // Clear the table body
                if (res.length > 0) {
                    // console.log(res);
                    res.forEach((item, index) => {
                        const isVisibleYes = item.visiblestatus === 'yes' ? 'checked' : '';
                        const isVisibleNo = item.visiblestatus === 'no' ? 'checked' : '';
                        let array = ['0'];
                        let elements = array[0].split(',');
                        let isPlusButton = elements.includes(item.pcategorycode) ? " Fixed" : '<i class="fa fa-plus addrowprp" style="font-size: 28px; margin: 4px; color: rgb(144, 12, 63); cursor: pointer;"></i>';
                        let isEditButton = elements.includes(item.pcategorycode) ? " Fixed" : '<i class="fa fa-edit editrowprp" style="font-size: 20px; margin: 2px; color: rgb(120, 112, 63); cursor: pointer;"></i>';

                        tbody.append(`<tr data-mpptype="deposit" data-mpcatid="${item.pcategorycode}" data-mpcatname="${item.pcategory}" data-mpsubcatid="${item.psub_categoryid}" data-mpsubcatname="${item.psub_category}" data-mpcode="${item.purposecode}" data-mpname="${item.purpose}" data-mprvcode="${item.provided_code}">
                                <td style="width: 50px; text-align: center;">${index + 1}</td>
                                <td class="ed_mpcategoryname">${item.pcategory}</td>
                                <td class="ed_mpsubcategoryname">${item.psub_category}</td>
                                <td class="ed_mppurposename">${item.purpose}</td>
                                <td class="ed_mpprvcode">${item.provided_code}</td>
                                <td class="ed_mpvisible">
                                    <label"><input type="radio" name="visiblep_${index}" class="visiblep" value="yes" ${isVisibleYes} /> YES</label>
                                    <label><input type="radio" name="visiblep_${index}" class="visiblep" value="no" ${isVisibleNo} /> NO</label>
                                </td>
                                <td class="accedit_td text-center">
                                    <button class='btn btn-sm btn-deepblue updpurposebtn d-none' id='updpurposebtn_${index}' style='margin:2px; color:rgb(144, 12, 32); cursor:pointer;'> <i class="fa fa-save" style='color: rgb(144, 12, 32)'></i> update</button>
                                    ${isPlusButton} ${isEditButton}
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    tbody.append(`
                        <tr>
                            <td colspan="7" class="text-center">Data not available</td>
                        </tr>
                    `);
                }
            });
        }else{
            getallpurposewithprojectid_withstatus('hidden_rooturl','hidden_companyid','hidden_subcompanyid','hidden_projectid', function (res){
                const tbody = $("#purposelistwithcat_tbl tbody");
                tbody.empty(); // Clear the table body
                if (res.length > 0) {
                    // console.log(res);
                    res.forEach((item, index) => {
                        const isVisibleYes = item.visiblestatus === 'yes' ? 'checked' : '';
                        const isVisibleNo = item.visiblestatus === 'no' ? 'checked' : '';
                        let array = ['0,3,4,6,7,8'];
                        let elements = array[0].split(',');
                        let isPlusButton = elements.includes(item.pcategorycode) ? " Fixed" : '<i class="fa fa-plus addrowprp" style="font-size: 28px; margin: 4px; color: rgb(144, 12, 63); cursor: pointer;"></i>';
                        let isEditButton = elements.includes(item.pcategorycode) ? " Fixed" : '<i class="fa fa-edit editrowprp" style="font-size: 20px; margin: 2px; color: rgb(120, 112, 63); cursor: pointer;"></i>';
                        // let isBulkButton = elements.includes(item.pcategorycode) ? " Fixed" : '<div><i class="fa fa-plus addwithbulkprp" style="font-size: 28px; margin: 4px; color: rgb(144, 12, 63); cursor: pointer;"></i> Bulk</div>';

                        tbody.append(`<tr data-mpptype="widthrown" data-mpcatid="${item.pcategorycode}" data-mpcatname="${item.pcategory}" data-mpsubcatid="${item.psub_categoryid}" data-mpsubcatname="${item.psub_category}" data-mpcode="${item.purposecode}" data-mpname="${item.purpose}" data-mprvcode="${item.provided_code}">
                                <td style="width: 50px; text-align: center;">${index + 1}</td>
                                <td class="ed_mpcategoryname">${item.pcategory}</td>
                                <td class="ed_mpsubcategoryname">${item.psub_category}</td>
                                <td class="ed_mppurposename">${item.purpose}</td>
                                <td class="ed_mpprvcode">${item.provided_code}</td>
                                <td class="ed_mpvisible">
                                    <label><input type="radio" name="visiblep_${index}" class="visiblep" value="yes" ${isVisibleYes} /> YES</label>
                                    <label><input type="radio" name="visiblep_${index}" class="visiblep" value="no" ${isVisibleNo} /> NO</label>
                                </td>
                                <td class="accedit_td text-center">
                                    <button class='btn btn-sm btn-deepblue updpurposebtn d-none' id='updpurposebtn_${index}' style='margin:2px; color:rgb(144, 12, 32); cursor:pointer;'> <i class="fa fa-save" style='color: rgb(144, 12, 32)'></i> update</button>
                                    ${isPlusButton} ${isEditButton}
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    tbody.append(`
                        <tr>
                            <td colspan="7" class="text-center">Data not available</td>
                        </tr>
                    `);
                }
            });
        }
    });

    $(document).on("click", ".addrowprp", function () {
        var rowlength = $("#purposelistwithcat_tbl tbody tr").length +2;
        var $currentRow = $(this).closest("tr"); // Get the current row
        var mpptype = $currentRow.data("mpptype");
        var pcatid = $currentRow.data("mpcatid");
        var pcatname = $currentRow.data("mpcatname");
        var psubcatid = $currentRow.data("mpsubcatid");
        var psubcatname = $currentRow.data("mpsubcatname");
        // Call the function to fetch the new provided code
        maxnew_providedcode('hidden_rooturl', 'hidden_companyid', 'hidden_subcompanyid', 'hidden_projectid', pcatid, psubcatid, mpptype, function (res) {
            $currentRow.after(
                `<tr data-mpptype='${mpptype}' data-mpcatid='${pcatid}' data-mpcatname='${pcatname}' data-mpsubcatid='${psubcatid}' data-mpsubcatname='${psubcatname}' data-mpname='' data-mpcode='' data-mprvcode=''>
                    <td style='width: 50px; text-align: center;'></td>
                    <td class='ed_mpcategoryname'>${pcatname}</td>
                    <td class='ed_mpsubcategoryname'>${psubcatname}</td>
                    <td style='background-color:#79d6df;' class='ed_mppurposename'> <textarea rows=2 class='form-control mpnewname'></textarea></td>
                    <td style='background-color:#79d6df;' class='ed_mpprvcode'><input type='text' class='form-control mpnewprvcode' value='${res.providedcode}' /></td>
                    <td style='background-color:#79d6df;' class='ed_mpvisible'><label><input type='radio' name='visiblep' class='visiblep' value='yes' checked /> YES</label><label><input type='radio' name='visiblep' class='visiblep' value='no' / > NO</label></td>
                    <td style='background-color:#79d6df;' class='text-center'>
                        <button class='btn btn-sm btn-deepblue updpurposebtn' id='updpurposebtn_${rowlength}' style='margin:2px; color:rgb(144, 12, 32); cursor:pointer;'> <i class="fa fa-save" style='color: rgb(144, 12, 32)'></i> update</button>
                    </td>
                </tr>`
            );
        });
    });

    $(document).on("click", ".editrowprp", function () {
        var $currentRow = $(this).closest("tr"); // Get the current row
        $currentRow.find("td.accedit_td").find(".editrowprp").addClass("d-none");
        var $purposeCell = $currentRow.find("td.ed_mppurposename");

        // Show the update button and hide the add button & edit button
        $currentRow.find("td").find("button.updpurposebtn").removeClass("d-none"); // Show update button
        $currentRow.find("td").find("svg.addrowprp").addClass("d-none"); // Hide add button

        // Set the cell to be editable and focus on it
        $purposeCell.attr("contenteditable", "true").focus();
    });


    $(document).on("click", ".updpurposebtn, .visiblep", function(){
        var $upcurrentRow = $(this).closest("tr"); // Get the current row
        var updbtnid = $(this).closest("tr").find("button.updpurposebtn").prop("id");
        var purposetype = $upcurrentRow.data("mpptype");
        var categoryidid = $upcurrentRow.data("mpcatid");
        var categoryidname = $upcurrentRow.data("mpcatname");
        var subcategoryid = $upcurrentRow.data("mpsubcatid");
        var subcategoryname = $upcurrentRow.data("mpsubcatname");
        var oldpurposename = $upcurrentRow.data("mpname");
        if(oldpurposename){
            var purposename = $upcurrentRow.find("td.ed_mppurposename").text(); 
            var purposecode = $upcurrentRow.data("mpcode");
            var providcode = $upcurrentRow.data("mprvcode");
        }else{
            var purposename = $upcurrentRow.closest("tr").find("textarea.mpnewname").val();
            var purposecode = $upcurrentRow.data("mpcode");
            var providcode = $upcurrentRow.closest("tr").find("input.mpnewprvcode").val();
        }
        var visiblestatus = $upcurrentRow.closest("tr").find("input[class='visiblep']:checked").val();
        // alert(purposename+'=='+purposecode+'=='+providcode+'=='+visiblestatus);
        addnewpurpose_withprovidedcode(categoryidid,subcategoryid,purposename,purposecode,providcode,visiblestatus,purposetype,updbtnid);
    });

    $(document).on("click", "#previewactivepurpose", function () {
        var projecname=$("#hidden_projectname").val();
        let prvrow = `<button class='btn btn-sm mb-0 mr-1 ml-1 btn-deepblue pull-right' 
                onclick="gridtoexcel_withfilter_chunkNEW('tempprvpurposetbl', 'Purpose Master', 'landscape', function(res){})">
                <i class='fa fa-download' aria-hidden='true'></i> Export Active Purpose
            </button>`;
        prvrow += `<div class='table-responsive'>`;
            prvrow += "<table class='table table-light' id='tempprvpurposetbl'><thead><tr><th colspan='3'><h4>All ACTIVE PURPOSE FOR PROJECT: <b>"+projecname+"</b></h4></th><th colspan=2><input type='text' class='form-control' id='temppurposefilterinput' oninput=\"filterTable('#temppurposefilterinput', '#tempprvpurposetbl')\" placeholder='search here.....' /></th><tr><tr><th>S.No</th><th>Purpose Category</th><th>Purpose Sub-Category</th><th>Purpose Name</th><th>Provide Code</th><tr></thead><tbody>";
        let snof=1;
        $("#purposelistwithcat_tbl tbody tr").each(function () {
            if($(this).closest("tr").find("input[type='radio'][value='yes']").is(":checked")){
                let row = "<tr><td>"+snof+"</td>";
                $(this).find("td").each(function (index) {
                    // alert(index);
                    if (![0, 5, 6].includes(index)) { // Index is zero-based                    
                        row += `<td>${$(this).html()}</td>`;
                    }
                });
                row += "</tr>";
                prvrow += row;
                snof++;
            }
        });
        prvrow += "</tbody></table></div>";
        // console.log(prvrow);
        $("#webpagebodydiv").html(prvrow);
        $("#modalheader").html("PREVIEW OR ACTIVE PURPOSE");
        $("#webpagebodyview_modal").modal("show");
        $("#webpagebodyview_modal").css("z-index","9999");
    });
</script>