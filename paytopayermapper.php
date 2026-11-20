<?php
require('header.php');
if($companyid!=""){
	$mdid ="";
	if(isset($_GET['mdid'])){
		$mdid = $_GET['mdid'];
	}
	$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
	$groupid ="";
	$modulename ="";
	if(count($dddr)>0){
		$groupid = $dddr[0]['groupid'];
		$modulename = $dddr[0]['modulename'];
	}
?>
<div class="container">
	<div class="row">
	    <div class="col-md-12 mt-1 mb-2 pt-3 pb-3 text-center">
			<h4><?php echo $modulename; ?></h4>
			<a href="<?php echo $rooturl; ?>admin_dashboard.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-sm btn-deepblue pull-right backtohome">Back to Home</a>
	    </div>
	</div>
	<div class="container bg-light shadow-lg">
		<div class="row">
		    <div class="col-md-12 mt-1 mb-2">
		    	<label class="m-2 text-dark">Transaction Type:</label>
		    	<div class="d-flex form-control" style="gap:30px;">
	                <label class="font-weight-normal my-0"><input type="radio" style="transform: scale(2);" name="transactiontyperadio" value="withdrawn"> Withdrawn</label>
	                <label class="font-weight-normal my-0"><input type="radio" style="transform: scale(2);" name="transactiontyperadio" value="deposit"> Deposit</label>
	            </div>
		    </div>
		</div>
		<div class="row">
			<div class="col-md-12 mt-1 mb-2">
				<label class="m-2 text-dark" id="unverifiedlist_lbl"></label>
	            <div id= "div_paytable" class="table-responsive div_paytable" style="max-height:50vh;background-color:#fff;">
	            </div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mt-1 mb-2 text-center">
				<button class="btn btn-outline-success font-weight-bold" id="applyfuzzymapper" style="display:none;">Apply Fuzzy Matcher & Refresh</button>
			</div>
		</div>
	</div>
</div>
<?php
}
require('footer.php');
?>
<script type="text/javascript">
$(document).ready(function(){
	$(document).on("change","input[name='transactiontyperadio']",function(){
		//alert($(this).val());
		const transactiontype = $(this).val();
		if(transactiontype==="withdrawn"){
			$("#unverifiedlist_lbl").text('Unverified PayTo List:');
			let fd = new FormData();
			fd.append("companyid", $("#hidden_companyid").val());
			fd.append("subcompanyid", $("#hidden_subcompanyid").val());
			fd.append("projectid", $("#hidden_projectid").val());
			fetch_postData(transactiontype,"JSON",function(jsonData){
				var columns = [ 
	                { key: 'sno', label: 'Sno', width: '5%' }, 
	                { key: 'paid', label: 'PayTo Name', width: '80%', editable: true },
	                { key: 'action', label: 'Action', width: '15%', align:'center' }, 
	            ]; 

		        const data = jsonData.map((item, index) => { 
	                return {
	                    ...item,
	                    sno: index + 1,
	                    action: { type: "button", className: "btn btn-info savetomaster "+ item.paid.replace(" ","___"), label:"Add To Master"},  
	                };
	            });
	            const titlerows=0;            
	            const titlerowsdata=[];  
	            const grid = new FlexGrid('div_paytable', columns, data,'tblunverifiedpay',titlerows,titlerowsdata,null,false,null,false);
	            if(jsonData.length>0)
	            	$("#applyfuzzymapper").show();
	            else
	            	$("#applyfuzzymapper").hide();

	            setTimeout(() => {
	            	fuzzy_logic(false);
				}, 200); 
			},fd);
		}
		else if(transactiontype==="deposit"){
			$("#unverifiedlist_lbl").text('Unverified Payer List:');
			let fd = new FormData();
			fd.append("companyid", $("#hidden_companyid").val());
			fd.append("subcompanyid", $("#hidden_subcompanyid").val());
			fd.append("projectid", $("#hidden_projectid").val());
			fetch_postData(transactiontype,"JSON",function(jsonData){
				var columns = [ 
	                { key: 'sno', label: 'Sno', width: '5%' }, 
	                { key: 'paid', label: 'Payer Name', width: '80%', editable: true },
	                { key: 'action', label: 'Action', width: '15%', align:'center' },
	            ]; 

		        const data = jsonData.map((item, index) => { 
	                return {
	                    ...item,
	                    sno: index + 1,
	                    action: { type: "button", className: "btn btn-info savetomaster "+ item.paid, label:"Add To Master"},  
	                };
	            });

	            const titlerows=0;            
	            const titlerowsdata=[];  
	            const grid = new FlexGrid('div_paytable', columns, data,'tblunverifiedpay',titlerows,titlerowsdata,null,false,null,false);
	             if(jsonData.length>0)
	            	$("#applyfuzzymapper").show();
	            else
	            	$("#applyfuzzymapper").hide();

	            setTimeout(() => {
	            	fuzzy_logic(false);
				}, 200); 
			},fd);
		}
	});

	$(document).on("click", "#applyfuzzymapper", function(){
		fuzzy_logic();
	});

	$(document).on("click",".savetomaster",function(){
		const row = $(this).closest("tr");
		const payname = $(row).find("td:eq(1)").text();
		const existingpayclass = $(this)[0].classList[$(this)[0].classList.length - 1];
		const existingpayname = existingpayclass.replace("___"," ");
		let fd = new FormData();
		fd.append("companyid", $("#hidden_companyid").val());
		fd.append("subcompanyid", $("#hidden_subcompanyid").val());
		fd.append("projectid", $("#hidden_projectid").val());
		fd.append("loginname", $("#hidden_loginname").val());
		fd.append("transactiontype",$("input[name='transactiontyperadio']").val());
		fd.append("existingpayname",existingpayname);
		fd.append("payname",payname);
		debugger;
		fetch_postData("savetomaster","JSON",function(res){
			debugger;
			if(res.length>0){
				if(res[0].status="Success")
					location.reload();
			}
		},fd);
	});	
});

function fuzzy_logic(reload=true){
	const rows = $("#tblunverifiedpay tbody tr");
	let unverifiedpaynames = [];
	$(rows).each(function(ind,row){
		unverifiedpaynames.push($(row).find("td:eq(1)").text());
	});
	let fd = new FormData();
	fd.append("unverifiedpaynames",JSON.stringify(unverifiedpaynames));
	fd.append("transactiontype",$("input[name='transactiontyperadio']").val());
	fd.append("companyid", $("#hidden_companyid").val());
	fd.append("subcompanyid", $("#hidden_subcompanyid").val());
	fd.append("projectid", $("#hidden_projectid").val());

	fetch_postData("applyfuzzymapper","text",function(){
		debugger;
		if(reload)
		location.reload();
	},fd);
}
function fetch_postData(action, outputdatatype, callback, data = null) {
	$(".waitloaderfooter").modal("show");
    $.ajax({
        type: "POST",
        url: "modal/paytopayermapper_controller.php?action=" + action,
        data: data, 
        contentType: data instanceof FormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
        processData: !(data instanceof FormData), 
        cache: false,
        dataType: outputdatatype,
        success: function (res) {
        	$(".waitloaderfooter").modal("hide");
            callback(res);
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
        }
    });
}
</script>