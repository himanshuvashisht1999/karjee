<?php
require('../header.php');
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
<div class="main-container">
	<div class="row mb-4">
		<div class="col-md-12 text-center">
			<h4 class="uppercase  bold">Sync Data From Source</h4>
		</div>
	</div>
    <div class="row">
        <div class="col-md-12 pr-4">
          <a href="<?php echo $rooturl; ?>admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue pull-right backtohome">Back to Home</a>
        </div>
    </div>
    <div class="card">
    	<div class="card-body">
    		<div class="row">
    			<div class="col-md-12">
    				<button id="checkBtn" class="btn btn-primary mb-3">Sync Files</button>
    				<!-- <a href="" class="btn btn-deepblue pull-right">Check data not yet reconstructed</a> -->
    			</div>
    		</div>
    		<div class="row">
    			<div class="col-md-12">
    				<table class="table table-bordered table-striped" id="fileTable" style="display:none;">
    			        <thead>
    			            <tr>
    			                <th style="width: 50px;" class="d-none">Select</th>
    			                <th>File Name</th>
    			                <th style="width:250px;">Preview</th>
    			            </tr>
    			        </thead>
    			        <tbody></tbody>
    			    </table>
    			    <div id="noFilesMsg" class="alert alert-warning" style="display:none;">
    			        No files found in source folder.
    			    </div>
    			</div>
    		</div>
    		<div id="previewArea" class="mt-4"></div>
    		<div class="row" id="datatypeforimportdiv" style="display: none;">
    			<div class="col-md-12">
    				<label class="excelinputlbl"><input type="radio" name="datatypeforimport" data-fileformat="excel" value="bankstatement"> Is this Bank Statement Excel File ? </label><br/>
    				<label class="pdfinputlbl"><input type="radio" name="datatypeforimport" data-fileformat="pdf" value="bankstatement"> Is this Bank Statement PDF File ? </label><br/>
    				<label class="excelinputlbl"><input type="radio" name="datatypeforimport" data-fileformat="excel" value="expense"> Is This Expense Sheet Excel File ? </label><br/>
    				<label class="pdfinputlbl"><input type="radio" name="datatypeforimport" data-fileformat="pdf" value="expense"> Is This Expense Sheet PDF File ? </label><br/>
    			</div>
    		</div>
    	</div>
    </div>
</div>
<?php
require('../footer.php');
?>
<div id="digitizationArea" class="mt-4"></div>
 <script>
var routurl = $("#hidden_routurl").val();
var companyid = $("#hidden_companyid").val();
var subcompanyid = $("#hidden_subcompanyid").val();
var projectid = $("#hidden_projectid").val();
document.getElementById('checkBtn').addEventListener('click', async function () {
	syncdatafromsource(routurl,companyid,subcompanyid,projectid);
});

async function syncdatafromsource(routurl,companyid,subcompanyid,projectid) {
var form_data = new FormData();
form_data.append('companyid', companyid);
form_data.append('subcompanyid', subcompanyid);
form_data.append('projectid', projectid);
$.ajax({
    type: "POST",
    url: "syncfilesfromsource.php",
    data: form_data,
    contentType: false,
    processData: false,
    cache: false,
    success: function(res) {
        try {
            const json = typeof res === "string" ? JSON.parse(res) : res;
            console.log("Response:", json);
            getFilesFromServer(routurl,companyid,subcompanyid,projectid,'fileTable', 'noFilesMsg');
        } catch (e) {
            console.error("Invalid JSON response:", res);
        }
        $(".waitloaderfooter").hide();
    },beforSend: function(){
    	$(".waitloaderfooter").show();
    }, error: function(xhr, status, error) {
        console.error("AJAX Error:", status, error);
    }
});
}


function getFilesFromServer(routurl, companyid, subcompanyid, projectid, tableid, messagedivid) {
    const form_data = new FormData();
    form_data.append('companyid', companyid);
    form_data.append('subcompanyid', subcompanyid);
    form_data.append('projectid', projectid);

    $.ajax({
        type: "POST",
        url: "modal/sync_modal.php?action=undegitizedfiles",
        data: form_data,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'json',
        beforeSend: function () {
            $(".waitloaderfooter").show();
        },
        success: function (res) {
            $(".waitloaderfooter").hide();
            const rows = [];

            if (Array.isArray(res) && res.length > 0) {
                res.forEach(file => {
                    const filepath = `sync_reconstruct/sourcefile/${companyid}/${subcompanyid}/${projectid}/${file.filename}`;
                    rows.push(
                        `<tr data-filename="${file.filename}" data-dbid="${file.id}">
                            <td style="text-align:center;" class="d-none">
                                <input type="radio" name="checkformappingradio" class="checkformappingradio">
                            </td>
                            <td>${file.filename}</td>
                            <td>
                                <button class="btn btn-sm btn-success m-1" data-filepath="${routurl}/${filepath}" style="border-width: thin;" onclick="prevedocment('${filepath}');">
                                    <i class="fa fa-eye"></i> Preview
                                </button>
                                <a class="btn btn-sm btn-info m-1 downloadexcel" href="${routurl}/${filepath}" target="_blank">
                                    <i class="fa fa-download"></i> Download
                                </a>
                            </td>
                        </tr>`
                    );
                });

                $("#" + tableid + " tbody").html(rows.join(''));
                $("#" + tableid).show();
                $("#" + messagedivid).hide();
            } else {
                $("#" + tableid).hide();
                $("#" + messagedivid).show();
            }
        },
        error: function (xhr, status, error) {
            $(".waitloaderfooter").hide();
            console.error("AJAX Error:", status, error);
        }
    });
}


$(document).on("click", "input[type='radio'][name='checkformappingradio']", function () {
    var filename = $(this).closest("tr").data("filename");
    var extension = filename.substring(filename.lastIndexOf('.') + 1).toLowerCase();
    $("#datatypeforimportdiv").hide();
    $("#datatypeforimportdiv input[type='radio'][name='datatypeforimport']").prop("checked", false);
    $(".pdfinputlbl").hide();
    $(".excelinputlbl").hide();
    if ($("input[type='radio'][name='checkformappingradio']:checked").length > 0) {
        $("#datatypeforimportdiv").show();
        if (extension === "pdf") {
            $("#datatypeforimportdiv .pdfinputlbl").show();
        } else if (extension === "xls" || extension === "xlsx") {
            $("#datatypeforimportdiv .excelinputlbl").show();
        }
    }
});

$(document).on("click", "input[type='radio'][name='datatypeforimport']", function () {
    var filename = "";
    var extension = "";
    var fileformattype = $(this).data("fileformat");
    var filefor = $(this).val();
    var pdffilename="";
    var excelfilename="";
    var filedbid="";
    $("#fileTable tbody tr").each(function () {
        // Check only the radio in this row
        if ($(this).find("input[type='radio'][name='checkformappingradio']").is(":checked")) {
            filename = $(this).data("filename");
            filedbid = $(this).data("dbid");
            extension = filename.substring(filename.lastIndexOf('.') + 1).toLowerCase();
            if(extension=="pdf"){
            	pdffilename=filename;
            }else{
            	excelfilename=filename;
            }
        }
    });

    if(filename && fileformattype=="excel" && filefor=="bankstatement"){
    	$("#digitizationArea").load("uploadbankstatement_sync.php?k=<?php echo $key; ?>&cid="+companyid+"&sbcid="+subcompanyid+"&prjid="+projectid+"&excelfile="+excelfilename+"&pdffile="+pdffilename+"&filedbid="+filedbid, function(){
    		
    	});
    }else if(filename && fileformattype=="pdf" && filefor=="bankstatement"){
    	$("#digitizationArea").load("uploadbankstatement_sync.php?k=<?php echo $key; ?>&cid="+companyid+"&sbcid="+subcompanyid+"&prjid="+projectid+"&excelfile="+excelfilename+"&pdffile="+pdffilename+"&filedbid="+filedbid, function(){
    		
    	});
    }

});
</script>
