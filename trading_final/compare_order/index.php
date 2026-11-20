<?php 
  include('../../header.php');
  date_default_timezone_set("Asia/Calcutta");
  $mdid ="";
  if(isset($_GET['mdid'])){
    $mdid = $_GET['mdid'];
  }
  $dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
  $groupid ="";
  if(is_array($dddr) && count($dddr)>0){
    $groupid = $dddr[0]['groupid'];
  }
?>
<div class="container-fluid px-4 my-4">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <h3 class="card-title text-center text-primary mb-0 fw-bold"> 
                <i class="fa fa-clipboard-list me-2"></i>Status of stock on the basis of Product and stock order no
                <small class="text-muted d-block mt-1">As On <?php echo date('d-m-Y'); ?></small>
            </h3>
        </div>
    </div>
    <div class="row mt-1">
        <div class="col-md-3"> 
            <label>Date From</label>  
            <input type="date" class="form-control"  name="datefrom"  id="datefrom"
                   value="<?php echo date('Y-01-01'); ?>">
        </div>
        <div class="col-md-3">
            <label>Date To</label> 
            <input type="date"  class="form-control"  name="dateto"  id="dateto"  value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-6 d-flex align-items-end gap-2">
            <button type="button"
                    class="btn btn-success btn-sm"
                    id="Submit" onclick="comparedetails();">
                Submit
            </button> 

        </div>                

    </div>

    <div  id= "div_purposecode"class="table-responsive" style="background-color:#fff;">  
    </div>       
        
   
 
    <div class="modal" id="msgModal" tabindex="-1" role="dialog" aria-labelledby="partialQtyPermissionTitle" data-backdrop="static" data-keyboard="false"> 
        <div class="modal-dialog modal-lg modal-dialog-scrollable" style="max-width: 50%;">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="msgTitle">
                        Permission Required
                    </h5>                     
                </div>
                <div class="modal-body">
                    <p id="textMessage" class="mb-0"></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-success mx-2" id="btnyes">
                        <span class="btn-text">Proceed Anyway</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                    <button type="button" class="btn btn-info mx-2" id="btnno">
                        Seek Permission
                    </button>
                    <button type="button" class="btn btn-secondary mx-2" data-dismiss="modal">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
 </div>

<?php include('../../footer.php'); ?>

<script type="text/javascript" src="<?php echo $prefix;?>js/flexgrid.js"></script>
<script>
    window.serverCurrentDate = "<?= date('d-m-Y'); ?>";
    
</script>

<script type="text/javascript" src="js/control.js"></script>

<script type="text/javascript" src="js/function.js"></script>
<script>    
$(document).ready(function () {
    comparedetails();
    $('#datefrom').on('change', function () {
         $("#div_purposecode").empty();
        $("#div_purposecode_flexgrid-toolbar").empty();
        const dateFrom = $(this).val();
        $('#dateto').attr('min', dateFrom);
        if ($('#dateto').val() < dateFrom) {
            $('#dateto').val(dateFrom);
        }
 
    });
    $('#dateto').on('change', function () {
        $("#div_purposecode").empty();
        $("#div_purposecode_flexgrid-toolbar").empty();
        const dateFrom = $(this).val();
        $('#dateto').attr('min', dateFrom);
        if ($('#dateto').val() < dateFrom) {
            $('#dateto').val(dateFrom);

        }
    });
});

    function validateDateRange() {
        const dateFrom = $('#datefrom').val();
        const dateTo   = $('#dateto').val();
        if (!dateFrom) {
            alert('Please select Date From.');
            $('#datefrom').focus();
            return false;
        }
        if (!dateTo) {
            alert('Please select Date To.');
            $('#dateto').focus();
            return false;
        }
        if (dateTo < dateFrom) {
            alert('Date To cannot be less than Date From.');
            $('#dateto').focus();
            return false;
        }

        return true;
    }
    function comparedetails() {
        $("#div_purposecode").empty();
        $("#div_purposecode_flexgrid-toolbar").empty(); 
        if (!validateDateRange()) {
            return;
        }
        const dateFrom = $('#datefrom').val();
        const dateTo   = $('#dateto').val();     

        const projectid  = $("#hidden_projectid").val(); 
        const formData = new FormData();
        formData.append('projectid', projectid);
        formData.append('dateFrom', dateFrom);
        formData.append('dateTo', dateTo);

        $.ajax({
            url: "modal/query.php?action=compareorder",
            type: "POST",
            data: formData,
            dataType: "json",
            contentType: false,
            processData: false,

            success: function (res) {

                $(".waitloaderfooter").modal("hide"); 
                 
                if (res.status){ 
                    const columns = [
                        { key: 'sno', label: 'Sno', width: '5%' }, 
                        { key: 'client_name', label: 'Client Name', width: '15%' }, 
                        { key: 'stockorder_no', label: 'Stock Orderno', width: '15%' }, 
                        { key: 'product_name', label: 'Product Name', width: '15%'},
                        { key: 'dispatchdate', label: 'Exp. Ready Date', width: '12%' }, 
                        { key: 'quantity', label: 'Order Qty', width: '9%'},
                        { key: 'reserveqty', label: 'Unconf. Qty', width: '9%' }, 
                        { key: 'allocatedqty', label: 'Conf. Qty', width: '10%'},
                        { key: 'avaiqty', label: 'Balance Qty', width: '10%' },
                        { key: 'clientid', label: 'clientid', hidden:true }, 
                        { key: 'productid', label: 'productid', hidden:true }, 
                    ]; 
                    const data = (res.data || [])
                        .filter(item => Number(item.avaiqty || 0) > 0)
                        .map((item, index) => ({
                            ...item,
                            sno: index + 1
                        }));
                    const totalqty = data.reduce((sum, item) => sum + parseFloat(item.quantity || 0), 0);
                    const totalres = data.reduce((sum, item) => sum + parseFloat(item.reserveqty || 0), 0);
                    const totalallocatedqty = data.reduce((sum, item) => sum + parseFloat(item.allocatedqty || 0), 0);
                    const totalavaiqty = data.reduce((sum, item) => sum + parseFloat(item.avaiqty || 0), 0);

                    // 🔹 add total row at bottom
                    data.push({
                        sno: '',
                        client_name: '',
                        stockorder_no: '',
                        product_name: 'Total',
                        quantity: totalqty,
                        reserveqty: totalres,
                        allocatedqty: totalallocatedqty,
                        avaiqty :Number(totalavaiqty).toFixed(0)
                    });
                    const titlerows=4;                          
                    let projname ="Project Name: "+$("#hidden_projectname").val();  
                    let daterange = 'Order Date Range: '+formatDate_js($("#datefrom").val())+' To '+formatDate_js($("#dateto").val())
                    titlerowsdata=["Summary of product & order comparision report",projname,daterange] ;
                    const grid2 = new FlexGrid('div_purposecode', columns, data,'tbl_purposecode',titlerows,titlerowsdata,null,false,null,true,true);  
                    grid2.headerbackground="#e0f0fe" ;
                } else{
                    $("#div_purposecode").html("<h5 class='m-2'> No Record Found!</h5>");
                }
            },beforeSend: function() {
                $(".waitloaderfooter").modal("show");
             }
        });     
    };
 
    function formatDate_js(date) { 
        let formattedDate = moment(date, "YYYY-MM-DD").format("DD-MM-YYYY");
        return formattedDate;

    }

</script>
