<?php
date_default_timezone_set('Asia/Kolkata');
require('../header.php');
include('functions/orders_functions.php');
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css">
<!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css"> -->
<style>
/* Scoped: QR request module only. No global project layout changes. */
#qrRequestPage{font-size:14px;min-width:0} #qrRequestPage .qr-panel{border:1px solid #dce3ec;border-radius:10px;background:#fff;padding:16px;margin-bottom:14px}
#qrRequestPage .qr-heading{font-size:1.2rem;font-weight:700;margin:0} #qrRequestPage .qr-subheading{font-weight:650;font-size:1rem;margin-bottom:12px}
#qrRequestPage label,.qr-detail-filter label{font-size:.83rem;font-weight:650;margin-bottom:5px;color:#344054}
#qrRequestPage .form-control,#qrRequestPage .form-select,.qr-detail-filter .form-control,.qr-detail-filter .form-select{min-height:38px;font-size:14px}
#qrRequestPage table{font-size:13px;vertical-align:middle} #qrRequestPage th{white-space:nowrap;background:#f3f6fa} #qrRequestPage td{vertical-align:middle}
#qrRequestPage .qr-result{color:#667085;font-size:12px} #qrRequestPage .qr-action{white-space:normal;min-width:135px}
#unverifiedBedModal .modal-dialog{width:100vw;max-width:100vw;height:100dvh;max-height:100dvh;margin:0}#unverifiedBedModal .modal-content{width:100%;height:100dvh;border:0;border-radius:0}#unverifiedBedModal .modal-body{min-height:0;overflow-y:auto}#unverifiedBedModal .modal-header,#unverifiedBedModal .modal-footer{flex-shrink:0}#unverifiedBedModal table{font-size:13px;vertical-align:middle} #unverifiedBedModal th{white-space:nowrap;background:#f3f6fa}
@media(max-width:767px){#qrRequestPage .qr-panel{padding:11px}#qrRequestPage .qr-heading{font-size:1.06rem}#qrRequestPage .qr-table thead,#unverifiedBedModal .qr-detail-table thead{display:none}#qrRequestPage .qr-table tbody tr,#unverifiedBedModal .qr-detail-table tbody tr{display:block;border:1px solid #dce3ec;border-radius:9px;margin-bottom:10px;padding:8px;background:#fff}#qrRequestPage .qr-table tbody td,#unverifiedBedModal .qr-detail-table tbody td{display:flex;justify-content:space-between;align-items:center;gap:12px;border:0;border-bottom:1px solid #eef1f5;padding:7px 4px;text-align:right!important;overflow-wrap:anywhere}#qrRequestPage .qr-table tbody td:last-child,#unverifiedBedModal .qr-detail-table tbody td:last-child{border:0}#qrRequestPage .qr-table tbody td:before,#unverifiedBedModal .qr-detail-table tbody td:before{content:attr(data-label);font-weight:650;text-align:left;color:#475467;flex:0 0 42%}#qrRequestPage .qr-table tbody .qr-message:before,#unverifiedBedModal .qr-detail-table tbody .qr-message:before{content:none}#unverifiedBedModal .modal-footer{flex-wrap:wrap}#unverifiedBedModal .modal-footer button{flex:1 1 auto}}
#qrRequestPage .qr-stat{padding:16px;background:#f3f6fa;border:1px solid #dce3ec;border-radius:8px;color:#475467}#qrRequestPage .qr-stat strong{display:block;font-size:27px;color:#173b55;margin-top:5px}
#qrRequestPage .select2-container .select2-selection--single,#unverifiedBedModal .select2-container .select2-selection--single{height:38px;border:1px solid #ced4da}#qrRequestPage .select2-selection__rendered,#unverifiedBedModal .select2-selection__rendered{line-height:36px!important}#qrRequestPage .select2-selection__arrow,#unverifiedBedModal .select2-selection__arrow{height:36px!important}#unverifiedBedModal .form-check{display:flex;align-items:center;gap:8px;padding-left:0}#unverifiedBedModal #chkAllBarcode{position:static;float:none;margin:0;flex-shrink:0}#unverifiedBedModal .form-check-label{margin:0}#qrRequestPage thead th{background:#f3f6fa;position:sticky;top:0;z-index:1}
@media(max-width:767px){#unverifiedBedModal .qr-detail-table thead{display:block}#unverifiedBedModal .qr-detail-table thead tr{display:block}#unverifiedBedModal .qr-detail-table thead th{display:none}#unverifiedBedModal .qr-detail-table thead th:first-child{display:block;padding:10px;border:1px solid #dce3ec;border-radius:6px;margin-bottom:10px}}
#qrRequestPage .qr-small-cards{display:flex;flex-wrap:wrap;gap:10px}#qrRequestPage .qr-small-card{flex:0 1 190px}#qrRequestPage .qr-stat{padding:10px 14px}#qrRequestPage .qr-stat strong{font-size:22px;margin-top:3px}@media(max-width:575px){#qrRequestPage .qr-small-card{flex:1 1 130px}}
</style>
<div class="container-main" id="qrRequestPage"><div class="qr-panel mt-2">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h4 class="qr-heading">QR Code Request Management</h4><div class="text-muted small d-none">Find pending barcode requests by location, client, stock order and sowing date.</div></div></div>
<div class="row g-2 align-items-end">
<div class="col-12 col-md-4 col-lg-3"><label for="locationlist">Production Location <span class="text-danger">*</span></label><select class="form-select" id="locationlist" required><option value="">-- Select Location --</option></select></div>
<div class="col-12 col-md-4 col-lg-3"><label for="qrClientFilter">Client Name</label><select class="form-select" id="qrClientFilter"><option value="">All Clients</option></select></div>
<div class="col-12 col-md-4 col-lg-3"><label for="qrStockFilter">Stock Order No.</label><select class="form-select" id="qrStockFilter"><option value="">All Stock Orders</option></select></div><div class="col-12 col-lg-3"><button type="button" class="btn btn-outline-secondary" id="qrReset">Reset Filters</button></div>
<div class="col-6 col-lg-2 d-none"><label for="qrDateFrom">Sowing Date From</label><input class="form-control" id="qrDateFrom" type="date"></div>
<div class="col-6 col-lg-2 d-none"><label for="qrDateTo">Sowing Date To</label><input class="form-control" id="qrDateTo" type="date"></div>
</div><div class="qr-small-cards my-3" aria-live="polite">
<div class="qr-small-card"><div class="qr-stat">Total Verified Qty<strong id="qrVerifiedTotal">0</strong></div></div>
<div class="qr-small-card"><div class="qr-stat">Requested Qty<strong id="qrRequestedTotal">0</strong></div></div>
<div class="qr-small-card"><div class="qr-stat">Unverified Qty<strong id="qrUnverifiedTotal">0</strong></div></div>
</div><div class="row g-2 mb-3"><div class="col-12"><input class="form-control" id="qrGlobalSearch" placeholder="Search client, stock order, sowing lot, date or quantity" autocomplete="off"></div></div><div class="table-responsive"><table class="table table-bordered table-hover qr-table" id="tblbarcode_alldata"><thead><tr><th>#</th><th>Stock Order No.</th><th>Client Name</th><th>Sowing Date</th><th>Polyhouse Rec. Date</th><th>Verified Trays</th><th>QR Sticker Requested</th><th>Unverified Trays</th><th>Total Trays</th></tr></thead><tbody><tr><td colspan="9" class="text-center qr-message">Select a production location to load data.</td></tr></tbody></table></div></div></div>
<div class="modal fade" id="unverifiedBedModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="qrModalTitle" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="qrModalTitle">Step 2 — Request for QR Code</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="modal-body"><div class="row g-2 mb-3"><div class="col-6 col-md-3"><div class="text-muted small">Client Name</div><strong id="modal_client"></strong></div><div class="col-6 col-md-3"><div class="text-muted small">Stock Order No.</div><strong id="modal_stockorder"></strong></div><div class="col-6 col-md-3"><div class="text-muted small">Sowing Date</div><strong id="modal_sowingdate"></strong></div><div class="col-6 col-md-3"><div class="text-muted small" id="modal_qty_label">Unverified Trays</div><strong class="text-danger" id="modal_unverifiedqty"></strong></div></div><hr>
<div class="qr-detail-filter row g-2 align-items-end mb-3"><div class="col-12 col-sm-6 col-lg-3"><label for="qrDetailSearch">Search Barcode / Product</label><input class="form-control" id="qrDetailSearch" placeholder="Enter barcode or product"></div><div class="col-6 col-lg-3"><label for="qrDetailPolyhouse">Polyhouse</label><select class="form-select" id="qrDetailPolyhouse"><option value="">All Polyhouses</option></select></div><div class="col-6 col-lg-2"><label for="qrDetailRack">Rack Name</label><select class="form-select" id="qrDetailRack"><option value="">All Racks</option></select></div>
<div class="col-12 col-lg-2 d-none"><label for="qrDetailBed">Bed No.</label><input class="form-control" id="qrDetailBed" placeholder="Search bed"></div><div class="col-12 col-lg-2 d-flex gap-2"><button type="button" class="btn btn-primary btn-sm flex-fill" id="qrDetailSearchBtn">Search</button><button type="button" class="btn btn-outline-secondary btn-sm flex-fill" id="qrDetailReset">Reset</button></div></div>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">

    <span class="small text-muted" id="qrDetailCount"></span>

</div>
<div class="table-responsive" id="unverifiedBedDetails"><table class="table table-bordered table-hover qr-detail-table" id="tblbeddetails"><thead><tr><th><div class="form-check"><input class="form-check-input" type="checkbox" id="chkAllBarcode" aria-label="Select all visible barcodes"><label class="form-check-label" for="chkAllBarcode">Select all</label></div></th><th>Barcode No.</th><th>Product Name</th><th>Polyhouse Name</th><th>Rack Name</th><th>Bed No.</th><th>Plants / Tray</th><th>QR Request Status</th></tr></thead><tbody><tr><td colspan="8" class="text-center qr-message">No data available.</td></tr></tbody></table></div></div>
<div class="modal-footer"><span class="me-auto small">Selected: <strong id="selectedBarcodeCount">0</strong></span><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-success" id="requestqrcode" disabled>Request for QR Code</button></div></div></div></div>
<?php
if(isset($_GET['dz'])){

}else{
  include('../footer.php');
}
?>
<script type="text/javascript" src="<?php echo $prefix;?>krf-orderandsales/js/pjs_function.js"></script>
<!-- Bootstrap JS + dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

  <!-- QR Scanner (html5-qrcode) -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script>
$(function () {
    const api = 'modal/barcode_verification.php';
    let summaryRows = [], detailRows = [], summaryRequest = null, detailRequest = null, saving = false;
    function initSelect2(){
        if (!$.fn.select2) { console.error('Select2 failed to load.'); return; }
        $('#locationlist,#qrClientFilter,#qrStockFilter').select2({width:'100%'});
        $('#qrDetailPolyhouse,#qrDetailRack').select2({width:'100%',dropdownParent:$('#unverifiedBedModal')});
    }
    initSelect2();
    function totals(rows){
        const sum=key=>rows.reduce((n,r)=>n+(Number(r[key])||0),0).toLocaleString('en-IN');
        $('#qrVerifiedTotal').text(sum('verified_qty'));$('#qrRequestedTotal').text(sum('requested_qty'));$('#qrUnverifiedTotal').text(sum('unverified_qty'));
    }
    const esc = v => $('<div>').text(v == null ? '' : String(v)).html().replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    const empty = (message, cols) => `<tr><td colspan="${cols}" class="text-center text-muted qr-message py-3">${esc(message)}</td></tr>`;
    const dateKey = v => String(v || '').substring(0,10);
    const uniqueSorted = values => [...new Set(values.map(v=>String(v||'').trim()).filter(Boolean))].sort((a,b)=>a.localeCompare(b,undefined,{numeric:true,sensitivity:'base'}));
    function fillSelect(selector, values, caption, keep) {
        const $s=$(selector), current=keep?$s.val():'';
        $s.empty().append($('<option>',{value:'',text:caption}));
        uniqueSorted(values).forEach(v=>$s.append($('<option>',{value:v,text:v})));
        if(current && $s.find('option').filter(function(){return this.value===current}).length) $s.val(current);
        $s.trigger('change.select2');
    }
    ProductionLocationMastercallback('all','all',function(res){
        const $s=$('#locationlist').empty().append($('<option>',{value:'',text:'-- Select Location --'}));
        (Array.isArray(res)?res:[]).forEach(r=>$s.append($('<option>',{value:r.LocationId,text:r.LocationName})));$s.trigger('change.select2');
    });
    function renderSummary(){
        const client=$('#qrClientFilter').val(), stock=$('#qrStockFilter').val()||'', term=$('#qrGlobalSearch').val().trim().toLowerCase(), from=$('#qrDateFrom').val(), to=$('#qrDateTo').val();
        if(from && to && from>to){$('#tblbarcode_alldata tbody').html(empty('Sowing Date From cannot be after Sowing Date To.',9));$('#qrResultCount').text('');return;}
        const rows=summaryRows.filter(r=>(!client||String(r.client_name||'')===client)&&(!stock||String(r.stockorder_no||'')===stock)&&(!from||dateKey(r.sowingdate_internal)>=from)&&(!to||dateKey(r.sowingdate_internal)<=to)&&(!term||[r.stockorder_no,r.client_name,r.sowinglotno_internal,r.displydt,r.sowingdate_internal,r.polyhouse_recdate,r.verified_qty,r.requested_qty,r.unverified_qty,r.total_qty].some(v=>String(v??'').toLowerCase().includes(term))));
        totals(rows);
        $('#tblbarcode_alldata tbody').html(rows.length?rows.map((r,i)=>{
            const cells=[i+1,r.stockorder_no,r.client_name,r.displydt||r.sowingdate_internal,r.polyhouse_recdate,Number(r.verified_qty)||0,Number(r.requested_qty)||0,Number(r.unverified_qty)||0,Number(r.total_qty)||0];
            const labels=['#','Stock Order No.','Client Name','Sowing Date','Polyhouse Rec. Date','Verified Trays','QR Requested','Unverified Trays','Total Trays'];
            return `<tr>${cells.map((c,j)=>`<td data-label="${labels[j]}" class="${j>4?'text-center':''}">${j===5?`<button type="button" class="btn btn-sm btn-outline-success openVerifiedBed" data-index="${summaryRows.indexOf(r)}" ${Number(r.verified_qty)>0?'':'disabled'}>Request Sticker (${esc(c)})</button>`:j===7?`<button type="button" class="btn btn-sm btn-outline-primary openUnverifiedBed" data-index="${summaryRows.indexOf(r)}" ${Number(r.unverified_qty)>0?'':'disabled'}>Request Sticker (${esc(c)})</button>`:esc(c)}</td>`).join('')}</tr>`;
        }).join(''):empty('No records match the selected filters.',9));
    }
    function loadSummary(keepFilters=false){
        const oldClient=keepFilters?$('#qrClientFilter').val():'',oldStock=keepFilters?$('#qrStockFilter').val():'';
        const locationid=$('#locationlist').val(), projectid=$('#hidden_projectid').val();
        if(summaryRequest)summaryRequest.abort();summaryRows=[];totals([]);fillSelect('#qrStockFilter',[],'All Stock Orders');
        fillSelect('#qrClientFilter',[],'All Clients');$('#qrResultCount').text('');
        if(!locationid||!projectid){$('#tblbarcode_alldata tbody').html(empty('Please select a production location.',9));return;}
        $('#tblbarcode_alldata tbody').html(empty('Loading QR request summary...',9));
        summaryRequest=$.ajax({url:api,type:'POST',dataType:'json',data:{action:'fetchunveriedbed',projectid,locationid},success(res){
            if(res.status!==true||!Array.isArray(res.data)){ $('#tblbarcode_alldata tbody').html(empty(res.message||'Unable to load summary.',9));return; }
            summaryRows=res.data;fillSelect('#qrStockFilter',summaryRows.map(r=>r.stockorder_no),'All Stock Orders',true);fillSelect('#qrClientFilter',summaryRows.map(r=>r.client_name),'All Clients',true);$('#qrClientFilter').val(oldClient).trigger('change.select2');$('#qrStockFilter').val(oldStock).trigger('change.select2');renderSummary();
        },error(xhr,status){if(status!=='abort'){$('#tblbarcode_alldata tbody').html(empty('Unable to load summary. Please retry.',9));console.error('Summary error:',xhr.responseText);}}});
    }
    $('#locationlist').on('change',()=>loadSummary(false));
    $('#qrSearch').on('click',renderSummary);
    $('#qrClientFilter').on('change',renderSummary);
    $('#qrStockFilter').on('change',renderSummary);$('#qrGlobalSearch').on('input',renderSummary);
    $('#qrDateFrom,#qrDateTo').on('change',renderSummary);
    $('#qrReset').on('click',function(){$('#locationlist,#qrClientFilter,#qrStockFilter').val('').trigger('change.select2');$('#qrDateFrom,#qrDateTo,#qrGlobalSearch').val('');loadSummary();});
    function selectedCount(){const n=$('#tblbeddetails .chkbarcode:checked').length;$('#selectedBarcodeCount').text(n);$('#requestqrcode').prop('disabled',saving||n===0);const $v=$('#tblbeddetails .chkbarcode:visible:not(:disabled)');$('#chkAllBarcode').prop('checked',$v.length>0&&$v.filter(':checked').length===$v.length);}
    function renderDetail(){
        const term=$('#qrDetailSearch').val().trim().toLowerCase(), ph=$('#qrDetailPolyhouse').val(), rack=$('#qrDetailRack').val(), bed=$('#qrDetailBed').val().trim().toLowerCase();
        const rows=detailRows.filter(r=>(!term||[r.barcode,r.productname].some(v=>String(v||'').toLowerCase().includes(term)))&&(!ph||String(r.polyhousename||'')===ph)&&(!rack||String(r.bedrackname||'')===rack)&&(!bed||String(r.bed_no||'').toLowerCase().includes(bed)));
        $('#qrDetailCount').text(`${rows.length} of ${detailRows.length} barcode(s) displayed`);
        $('#tblbeddetails tbody').html(rows.length?rows.map(r=>{
            const requested=!!r.qrcode_request;const cells=[r.barcode,r.productname,r.polyhousename,r.bedrackname,r.bed_no,Number(r.qtypertray)||0];
            const labels=['Barcode No.','Product Name','Polyhouse Name','Rack Name','Bed No.','Plants / Tray'];
            return `<tr data-barcode="${esc(r.barcode)}" data-polyhouseid="${esc(r.polyhouseid)}" data-productid="${esc(r.productid)}"><td data-label="Select"><input type="checkbox" class="form-check-input chkbarcode" aria-label="Select barcode ${esc(r.barcode)}" ${requested?'disabled':''}></td>${cells.map((v,j)=>`<td data-label="${labels[j]}">${esc(v)}</td>`).join('')}<td data-label="QR Request Status">${requested?'<span class="badge bg-info text-dark">Already Requested</span>':'<span class="badge bg-warning text-dark">Pending</span>'}</td></tr>`;
        }).join(''):empty('No barcodes match the selected filters.',8));
        $('#chkAllBarcode').prop('checked',false);selectedCount();
    }
    function detailFilters(){fillSelect('#qrDetailPolyhouse',detailRows.map(r=>r.polyhousename),'All Polyhouses',true);fillSelect('#qrDetailRack',detailRows.map(r=>r.bedrackname),'All Racks',true);}
    $(document).on('click','#qrRequestPage .openUnverifiedBed,#qrRequestPage .openVerifiedBed',function(){
        const verified=$(this).hasClass('openVerifiedBed');
        const r=summaryRows[Number($(this).attr('data-index'))];if(!r)return;
        const selected={projectid:$('#hidden_projectid').val(),locationid:$('#locationlist').val(),stockorder_no:r.stockorder_no,sowingdate_internal:r.sowingdate_internal,sowinglotno_internal:r.sowinglotno_internal};
        $('#unverifiedBedModal').data('selectedRow',selected);
        $('#modal_client').text(r.client_name||'—');$('#modal_stockorder').text(r.stockorder_no);$('#modal_sowingdate').text(r.displydt||r.sowingdate_internal);$('#modal_unverifiedqty').text(verified?r.verified_qty:r.unverified_qty);$('#modal_qty_label').text(verified?'Verified Trays':'Unverified Trays');$('#qrModalTitle').text(verified?'Verified Trays — Request Sticker':'Unverified Trays — Request Sticker');
        $('#qrDetailSearch,#qrDetailBed').val('');fillSelect('#qrDetailPolyhouse',[],'All Polyhouses');fillSelect('#qrDetailRack',[],'All Racks');
        detailRows=[];$('#tblbeddetails tbody').html(empty('Loading barcode details...',8));$('#qrDetailCount').text('');$('#chkAllBarcode').prop('checked',false);selectedCount();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('unverifiedBedModal'),{backdrop:'static',keyboard:false}).show();
        if(detailRequest)detailRequest.abort();
        detailRequest=$.ajax({url:api,type:'POST',dataType:'json',data:{action:verified?'fetchverifiedbeddetails':'fetchunverifiedbeddetails',...selected},success(res){
            if(res.status!==true||!Array.isArray(res.data)){$('#tblbeddetails tbody').html(empty(res.message||'Unable to load barcode details.',8));return;}
            detailRows=res.data;detailFilters();renderDetail();
        },error(xhr,status){if(status!=='abort'){$('#tblbeddetails tbody').html(empty('Unable to load barcode details.',8));console.error('Detail error:',xhr.responseText);}}});
    });
    $('#qrDetailSearchBtn').on('click',renderDetail);
    $('#qrDetailSearch,#qrDetailBed').on('input',renderDetail);
    $('#qrDetailPolyhouse,#qrDetailRack').on('change',renderDetail);
    $('#qrDetailReset').on('click',function(){$('#qrDetailSearch,#qrDetailBed').val('');$('#qrDetailPolyhouse,#qrDetailRack').val('').trigger('change.select2');renderDetail();});
    $('#chkAllBarcode').on('change',function(){$('#tblbeddetails .chkbarcode:visible:not(:disabled)').prop('checked',this.checked);selectedCount();});
    $(document).on('change','#tblbeddetails .chkbarcode',selectedCount);
    // $('#requestqrcode').on('click',function(){
    //     const $btn=$(this), selected=$('#unverifiedBedModal').data('selectedRow');if(!selected)return;
    //     const items=$('#tblbeddetails .chkbarcode:checked').map(function(){const $tr=$(this).closest('tr');return {barcode:$tr.attr('data-barcode'),polyhouseid:$tr.attr('data-polyhouseid'),productid:$tr.attr('data-productid')};}).get();
    //     if(!items.length){alertmessage('warning','Please select at least one barcode.');return;}
    //     if(!confirm(`Request QR code for ${items.length} selected barcode(s)?`))return;

    //     $btn.prop('disabled',true).text('Submitting request...');
    //     $.ajax({url:api,type:'POST',dataType:'json',data:{action:'savebedverification',...selected,communicatedby:$('#hidden_loginname').val(),items:JSON.stringify(items)},success(res){
    //         if(res.status===true){alertmessage('warning',res.message||'QR code request saved.');bootstrap.Modal.getOrCreateInstance(document.getElementById('unverifiedBedModal')).hide();loadSummary();}
    //         else alertmessage('warning',res.message||'Unable to save QR request.');
    //     },error(xhr){console.error('Save error:',xhr.responseText);alertmessage('warning','Unable to save QR request. Please retry.');},complete(){$btn.text('Request for QR Code');selectedCount();}});
    // });
    $('#requestqrcode').on('click', async function () {
    const $btn = $(this);
    if(saving)return;
    const selected = $('#unverifiedBedModal').data('selectedRow');
    if (!selected) return;

    const items = $('#tblbeddetails .chkbarcode:checked').map(function () {
        const $tr = $(this).closest('tr');
        return {
            barcode: $tr.attr('data-barcode'),
            polyhouseid: $tr.attr('data-polyhouseid'),
            productid: $tr.attr('data-productid')
        };
    }).get();

    if (!items.length) {
        alertmessage('warning', 'Please select at least one barcode.');
        return;
    }

    saving=true;$btn.prop('disabled',true);
    let result;
    try { result = await Swal.fire({
        icon: 'warning',
        title: 'Request QR Code?',
        text: `Request QR code for ${items.length} selected barcode(s)?`,
        showCancelButton: true,
        confirmButtonText: 'Yes, Request',
        cancelButtonText: 'Cancel'
    });

    } catch(e){saving=false;selectedCount();return;}
    if (!result.isConfirmed) {saving=false;selectedCount();return;}

    $btn.prop('disabled', true).text('Submitting request...');

    $.ajax({
        url: api,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'savebedverification',
            ...selected,
            communicatedby: $('#hidden_loginname').val(),
            items: JSON.stringify(items)
        },
        success(res) {
            if (res.status === true) {
                alertmessage('success', res.message || 'QR code request saved.');
                bootstrap.Modal.getOrCreateInstance(
                    document.getElementById('unverifiedBedModal')
                ).hide();
                loadSummary(true);
            } else {
                alertmessage('warning', res.message || 'Unable to save QR request.');
            }
        },
        error(xhr) {
            console.error('Save error:', xhr.responseText);
            alertmessage('warning', 'Unable to save QR request. Please retry.');
        },
        complete() {
            saving=false;$btn.text('Request for QR Code');
            selectedCount();
        }
    });
});
});
</script>
