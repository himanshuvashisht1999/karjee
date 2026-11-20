<?php
error_reporting(E_ALL);
require('../../../connection.php');
require('../../../loginCell/functions/login_functions.php');
include('../../functions/orders_functions.php');

if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset('utf8mb4');
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function masterCode($v) {
    $s = trim((string)$v);
    if ($s !== '' && ctype_digit($s)) {
        return str_pad($s, 2, '0', STR_PAD_LEFT);
    }
    return $s;
}

$orderid      = trim($_POST['orderid'] ?? '');
$clientid     = trim($_POST['clientid'] ?? '');
$companyid    = (int)($_POST['companyid'] ?? 0);
$subcompanyid = (int)($_POST['subcompanyid'] ?? 0);
$projectid    = (int)($_POST['projectid'] ?? 0);

if ($orderid === '' || $projectid <= 0) {
    echo '<div class="alert alert-danger m-3">Invalid order details.</div>';
    exit;
}

/* Order header */
$stmt = $krfconn->prepare("SELECT * FROM krf_provisional_sales_order WHERE projectid=? AND psono=? LIMIT 1");
$stmt->bind_param('is', $projectid, $orderid);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo '<div class="alert alert-danger m-3">Provisional order not found.</div>';
    exit;
}

$clientid   = (string)($order['clientid'] ?? $clientid);
$clientname = (string)($order['clientname'] ?? '');

/* Saved Step 2 products */
$products = [];
$stmt = $krfconn->prepare("SELECT id, product_id, product_name, quantity, expecteddeliverydate FROM krf_provisional_sales_order_items WHERE projectid=? AND psono=? ORDER BY id");
$stmt->bind_param('is', $projectid, $orderid);
$stmt->execute();
$rs = $stmt->get_result();
while ($r = $rs->fetch_assoc()) {
    $products[] = $r;
}
$stmt->close();

if (!$products) {
    echo '<div class="alert alert-warning m-3">No saved products found in this provisional order.</div>';
    exit;
}

/* Cascading State -> District -> Location master.
   krf_deliveryaddress_location.locationid stores krf_boxpacking_freight_master.id. */
$locationMaster = [];
$stmt = $krfconn->prepare("SELECT id, stateid, statename, districtid, districtname, location, plants_per_box, boxpacking_amount, airport_charges, bus_charges, railway_charges FROM krf_boxpacking_freight_master WHERE projectid=? ORDER BY statename,districtname,location");
$stmt->bind_param('i', $projectid);
$stmt->execute();
$rs = $stmt->get_result();
while ($r = $rs->fetch_assoc()) {
    $locationMaster[] = $r;
}
$stmt->close();

/* Existing product-wise delivery rows. Do NOT use LIMIT 1. */
$deliveryRows = [];
$stmt = $krfconn->prepare("SELECT * FROM krf_deliveryaddress_location WHERE projectid=? AND (psono=? OR sono=?) AND (tedit IS NULL OR tedit='') ORDER BY id");
$stmt->bind_param('iss', $projectid, $orderid, $orderid);
$stmt->execute();
$rs = $stmt->get_result();
while ($r = $rs->fetch_assoc()) {
    $deliveryRows[] = $r;
}
$stmt->close();

/* Existing consolidated freight */
$freight = [];
$stmt = $krfconn->prepare("SELECT * FROM krf_freightcharges WHERE projectid=? AND (psono=? OR (sono=? AND IFNULL(sono,'')<>'')) ORDER BY id DESC LIMIT 1");
$stmt->bind_param('iss', $projectid, $orderid, $orderid);
$stmt->execute();
$freight = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$jsProducts = [];
foreach ($products as $p) {
    $jsProducts[] = [
        'productid' => (string)$p['product_id'],
        'productname' => (string)$p['product_name'],
        'quantity' => (float)$p['quantity'],
        'date' => (string)($p['expecteddeliverydate'] ?? '')
    ];
}

$jsDelivery = [];
foreach ($deliveryRows as $r) {
    $jsDelivery[] = [
        'id' => (int)$r['id'],
        'productid' => (string)($r['productid'] ?? ''),
        'qty' => (float)($r['deliveryqty'] ?? 0),
        'distance' => (float)($r['distance'] ?? 0),
        'latlong' => (($r['dest_latitude'] ?? '') !== '' && ($r['dest_longitude'] ?? '') !== '') ? ((string)$r['dest_latitude'] . ',' . (string)$r['dest_longitude']) : '',
        'stateid' => masterCode($r['stateid'] ?? ''),
        'statename' => (string)($r['dest_state'] ?? ''),
        'districtid' => masterCode($r['districtid'] ?? ''),
        'districtname' => (string)($r['dest_district'] ?? ''),
        'cityid' => (string)($r['locationid'] ?? ''),
        'cityname' => (string)($r['dest_city'] ?? ''),
        'pincode' => (string)($r['dest_pincode'] ?? ''),
        'address' => (string)($r['deliveryaddress'] ?? '')
    ];
}
?>
<style>
#editDeliveryFreightPackage{--p:#6246c7;--border:#e3e8ef;--muted:#64748b;background:#f8fafc;border-radius:14px;padding:14px}
#editDeliveryFreightPackage .package-title{font-weight:800;font-size:1.1rem;color:#27364a}
#editDeliveryFreightPackage .wizard-strip{display:flex;gap:10px;margin:12px 0 16px}
#editDeliveryFreightPackage .wizard-pill{flex:1;text-align:center;border:1px solid var(--border);background:#fff;padding:11px;border-radius:10px;font-weight:700;color:#64748b}
#editDeliveryFreightPackage .wizard-pill.active{background:linear-gradient(135deg,#7350c7,#4f46e5);color:#fff;border-color:transparent}
#editDeliveryFreightPackage .wizard-pill.done{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
#editDeliveryFreightPackage .premium-card{background:#fff;border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:0 5px 20px rgba(15,23,42,.05)}
#editDeliveryFreightPackage .premium-head{padding:13px 16px;background:#f7f7ff;border-bottom:1px solid var(--border);font-weight:800;color:#4338ca;display:flex;justify-content:space-between;align-items:center;gap:10px}
#editDeliveryFreightPackage .premium-body{padding:15px}
#editDeliveryFreightPackage .table-wrap{overflow:auto;border:1px solid var(--border);border-radius:10px}
#editDeliveryFreightPackage table{margin-bottom:0;min-width:1280px}
#editDeliveryFreightPackage thead th{background:#f1f5f9;color:#475569;font-size:.74rem;text-transform:uppercase;white-space:nowrap;padding:9px}
#editDeliveryFreightPackage td{vertical-align:middle;padding:8px}
#editDeliveryFreightPackage .form-control,#editDeliveryFreightPackage .form-select{font-size:.84rem;border-radius:8px}
#editDeliveryFreightPackage .qty-note{font-size:.7rem;color:#64748b;margin-top:3px}
#editDeliveryFreightPackage .qty-card{border:1px solid var(--border);background:#fff;border-radius:9px;padding:9px 11px}
#editDeliveryFreightPackage .premium-btn{background:linear-gradient(135deg,#7350c7,#4f46e5);border:0;color:#fff;font-weight:700}
#editDeliveryFreightPackage .wizard-footer{display:flex;justify-content:space-between;gap:10px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border)}
#editDeliveryFreightPackage .row-invalid{background:#fff1f2!important}
</style>

<div id="editDeliveryFreightPackage">
<form id="editDeliveryFreightForm">
    <input type="hidden" name="orderid" value="<?=e($orderid)?>">
    <input type="hidden" name="clientid" value="<?=e($clientid)?>">
    <input type="hidden" name="companyid" value="<?=$companyid?>">
    <input type="hidden" name="subcompanyid" value="<?=$subcompanyid?>">
    <input type="hidden" name="projectid" value="<?=$projectid?>">
    <input type="hidden" name="pickupfrom" value="delivered">
    <input type="hidden" name="deleted_rowids" id="edf_deleted_ids" value="">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <div class="package-title"><i class="fa fa-truck me-2 text-primary"></i>Add / Update Delivery Address & Freight</div>
            <div class="small text-muted mt-1">Order <strong><?=e($orderid)?></strong> &nbsp; | &nbsp; Client <strong><?=e($clientname)?></strong></div>
        </div>
        <span class="badge bg-light text-primary border px-3 py-2">Product-wise Editing</span>
    </div>

    <div class="wizard-strip">
        <div class="wizard-pill active" data-step="3"><i class="fa fa-map-marker-alt me-1"></i> Step 03: Delivery Address</div>
        <div class="wizard-pill" data-step="4"><i class="fa fa-hand-holding-usd me-1"></i> Step 04: Freight Charges</div>
    </div>

    <section id="edf_step3">
        <div class="premium-card">
            <div class="premium-head">
                <span><i class="fa fa-map-marked-alt me-2"></i>Delivery Address Details</span>
                <button type="button" class="btn btn-outline-primary btn-sm" id="edf_add_row"><i class="fa fa-plus me-1"></i>Add Address Row</button>
            </div>
            <div class="premium-body">
                <div class="alert alert-light border small py-2 px-3">
                    Products come from the saved provisional order. State, District and City/Location are cascading dropdowns. The selected IDs are saved with the address row.
                </div>
                <div class="table-wrap">
                    <table class="table table-striped" id="edf_delivery_table">
                        <thead><tr>
                            <th>Product</th><th>Delivery Qty</th><th>Distance Km</th><th>State</th><th>District</th><th>City / Location</th><th>Pincode</th><th>Full Address</th><th>Action</th>
                        </tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="row g-2 mt-2" id="edf_qty_summary"></div>
            </div>
        </div>
        <div class="wizard-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn premium-btn px-4" id="edf_next">Next: Freight Charges <i class="fa fa-arrow-right ms-1"></i></button>
        </div>
    </section>

    <section id="edf_step4" class="d-none">
        <div class="premium-card">
            <div class="premium-head"><span><i class="fa fa-truck-moving me-2"></i>Freight Details</span></div>
            <div class="premium-body">
                <?php if (!empty($freight)): ?>
                <div class="alert alert-info small py-2">
                    Current freight snapshot: Amount ₹<?=number_format((float)($freight['freight_amount'] ?? 0),2)?>, Discount <?=number_format((float)($freight['freight_discountpercent'] ?? 0),2)?>%, Payable ₹<?=number_format((float)($freight['payable_freightamount'] ?? 0),2)?>.
                </div>
                <?php endif; ?>
                <div class="table-wrap">
                    <table class="table table-bordered" id="edf_freight_table">
                        <thead><tr><th>Delivery Location</th><th>Delivery Date</th><th>Items / Qty</th><th>Distance</th><th>Freight Amount</th><th>Discount %</th><th>Payable</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-8"><label class="form-label small fw-bold">Freight Remarks</label><input type="text" class="form-control" name="freight_remarks" value="<?=e($freight['remarks'] ?? '')?>"></div>
                    <div class="col-md-4"><div class="qty-card h-100"><div class="small text-muted">Total Payable Freight</div><div class="fs-4 fw-bold text-primary">₹ <span id="edf_total_payable">0.00</span></div></div></div>
                </div>
            </div>
        </div>
        <div class="wizard-footer">
            <button type="button" class="btn btn-outline-secondary" id="edf_back"><i class="fa fa-arrow-left me-1"></i>Previous</button>
            <button type="submit" class="btn premium-btn px-4" id="edf_save"><i class="fa fa-save me-1"></i>Save Changes</button>
        </div>
    </section>
</form>
</div>

<script>
(function(){
'use strict';
const PRODUCTS = <?=json_encode($jsProducts, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
const MASTER   = <?=json_encode($locationMaster, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
const SAVED    = <?=json_encode($jsDelivery, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
const OLD_FREIGHT = {
  amount: <?=json_encode((float)($freight['freight_amount'] ?? 0))?>,
  discount: <?=json_encode((float)($freight['freight_discountpercent'] ?? 0))?>
};
let seq=0, deleted=[];
const esc=v=>$('<div>').text(v==null?'':v).html();
const n=v=>isNaN(parseFloat(v))?0:parseFloat(v);
const product=id=>PRODUCTS.find(p=>String(p.productid)===String(id));

function productOptions(selected=''){
 let h='<option value="">-- Select Product --</option>';
 PRODUCTS.forEach(p=>h+=`<option value="${esc(p.productid)}" ${String(p.productid)===String(selected)?'selected':''}>${esc(p.productname)}</option>`);
 return h;
}
function stateOptions(selected=''){
 const m=new Map(); MASTER.forEach(x=>m.set(String(x.stateid),x.statename));
 let h='<option value="">-- Select State --</option>';
 m.forEach((name,id)=>h+=`<option value="${esc(id)}" ${String(id)===String(selected)?'selected':''}>${esc(name)}</option>`);
 return h;
}
function districtOptions(stateid,selected=''){
 const m=new Map(); MASTER.filter(x=>String(x.stateid)===String(stateid)).forEach(x=>m.set(String(x.districtid),x.districtname));
 let h='<option value="">-- Select District --</option>';
 m.forEach((name,id)=>h+=`<option value="${esc(id)}" ${String(id)===String(selected)?'selected':''}>${esc(name)}</option>`);
 return h;
}
function cityOptions(stateid,districtid,selected=''){
 let h='<option value="">-- Select City / Location --</option>';
 MASTER.filter(x=>String(x.stateid)===String(stateid)&&String(x.districtid)===String(districtid)).forEach(x=>{
   h+=`<option value="${x.id}" ${String(x.id)===String(selected)?'selected':''} data-location="${esc(x.location)}" data-airport="${x.airport_charges}" data-bus="${x.bus_charges}" data-railway="${x.railway_charges}">${esc(x.location)}</option>`;
 });
 return h;
}
function syncNames($r){
 $r.find('.edf-state-name').val($r.find('.edf-state option:selected').text().replace('-- Select State --','').trim());
 $r.find('.edf-district-name').val($r.find('.edf-district option:selected').text().replace('-- Select District --','').trim());
 $r.find('.edf-city-name').val($r.find('.edf-city option:selected').data('location')||'');
}
function addRow(d={}){
 seq++;
 const h=`<tr class="edf-row" data-db-id="${esc(d.id||'')}">
 <td><input type="hidden" name="delivery_rowid[]" value="${esc(d.id||'')}"><select class="form-select edf-product" name="delivery_productid[]">${productOptions(d.productid||'')}</select></td>
 <td><input type="text" class="form-control edf-qty" name="delivery_qty[]" value="${esc(d.qty||'')}" onkeypress="return isFloatNumber(event,this);"><div class="qty-note"></div></td>
 <td><input type="text" class="form-control edf-distance" name="delivery_distance[]" value="${esc(d.distance||0)}" onkeypress="return isFloatNumber(event,this);"><input type="hidden" name="delivery_latlong[]" value="${esc(d.latlong||'')}"></td>
 <td><select class="form-select edf-state" name="delivery_stateid[]">${stateOptions(d.stateid||'')}</select><input type="hidden" class="edf-state-name" name="delivery_state[]" value="${esc(d.statename||'')}"></td>
 <td><select class="form-select edf-district" name="delivery_districtid[]">${districtOptions(d.stateid||'',d.districtid||'')}</select><input type="hidden" class="edf-district-name" name="delivery_district[]" value="${esc(d.districtname||'')}"></td>
 <td><select class="form-select edf-city" name="delivery_cityid[]">${cityOptions(d.stateid||'',d.districtid||'',d.cityid||'')}</select><input type="hidden" class="edf-city-name" name="delivery_city[]" value="${esc(d.cityname||'')}"></td>
 <td><input type="text" class="form-control" name="delivery_pincode[]" value="${esc(d.pincode||'')}" maxlength="6" onkeypress="return isNumber(event);"></td>
 <td><input type="text" class="form-control edf-address" name="delivery_fulladdress[]" value="${esc(d.address||'')}"></td>
 <td><div class="d-flex gap-1"><button type="button" class="btn btn-outline-primary btn-sm edf-copy"><i class="fa fa-copy"></i></button><button type="button" class="btn btn-outline-danger btn-sm edf-remove"><i class="fa fa-trash"></i></button></div></td>
 </tr>`;
 const $r=$(h).appendTo('#edf_delivery_table tbody'); syncNames($r); updateQty(); return $r;
}
function allocated(pid){let t=0;$('#edf_delivery_table .edf-row').each(function(){if(String($(this).find('.edf-product').val())===String(pid))t+=n($(this).find('.edf-qty').val());});return t;}
function updateQty(){
 let h=''; PRODUCTS.forEach(p=>{const a=allocated(p.productid),rem=+(n(p.quantity)-a).toFixed(3),ok=Math.abs(rem)<.0001;h+=`<div class="col-md-6 col-xl-4"><div class="qty-card"><div class="d-flex justify-content-between"><strong>${esc(p.productname)}</strong><span class="badge ${ok?'bg-success':'bg-warning text-dark'}">${ok?'Matched':'Pending '+rem}</span></div><div class="small text-muted">Order ${p.quantity} | Allocated ${a}</div></div></div>`;});$('#edf_qty_summary').html(h);
 $('#edf_delivery_table .edf-row').each(function(){const p=product($(this).find('.edf-product').val());$(this).find('.qty-note').text(p?`Order Qty: ${p.quantity} | Allocated: ${allocated(p.productid)}`:'');});
}
function validateStep3(){
 if(!$('#edf_delivery_table .edf-row').length)return 'Please add at least one delivery address row.';
 let msg='';$('#edf_delivery_table .edf-row').each(function(){const $r=$(this);$r.removeClass('row-invalid');syncNames($r);if(!$r.find('.edf-product').val()||n($r.find('.edf-qty').val())<=0||!$r.find('.edf-state').val()||!$r.find('.edf-district').val()||!$r.find('.edf-city').val()||!$r.find('.edf-address').val().trim()){if(!msg)msg='Please fill Product, Qty, State, District, City/Location and Full Address in every row.';$r.addClass('row-invalid');}});if(msg)return msg;
 for(const p of PRODUCTS){const a=allocated(p.productid);if(Math.abs(a-n(p.quantity))>.0001)return `Delivery quantity for ${p.productname} must equal ordered quantity ${p.quantity}. Current allocated quantity: ${a}.`;}
 return '';
}
function groups(){const g={};$('#edf_delivery_table .edf-row').each(function(){const $r=$(this),pid=$r.find('.edf-product').val(),p=product(pid)||{},sid=$r.find('.edf-state').val(),did=$r.find('.edf-district').val(),cid=$r.find('.edf-city').val(),city=$r.find('.edf-city-name').val(),addr=$r.find('.edf-address').val(),date=p.date||'',key=[sid,did,cid,addr,date].join('|');if(!g[key])g[key]={sid,did,cid,city,addr,date,distance:n($r.find('.edf-distance').val()),items:[]};g[key].items.push({name:p.productname||pid,qty:n($r.find('.edf-qty').val())});});return Object.values(g);}
function calcFreight($r){const a=n($r.find('.edf-famount').val()),d=n($r.find('.edf-fdisc').val()),p=Math.max(0,a-a*d/100);$r.find('.edf-fpay').val(p.toFixed(2));let total=0;$('.edf-fpay').each(function(){total+=n(this.value)});$('#edf_total_payable').text(total.toFixed(2));}
function buildFreight(){
 const gs=groups();let h='';gs.forEach((g,i)=>{const amount=i===0?OLD_FREIGHT.amount:0,disc=i===0?OLD_FREIGHT.discount:0,pay=Math.max(0,amount-amount*disc/100),items=g.items.map(x=>`${esc(x.name)} <span class="badge bg-light text-dark border">${x.qty}</span>`).join('<br>');h+=`<tr class="edf-frow"><td><strong>${esc(g.city)}</strong><div class="small text-muted">${esc(g.addr)}</div><input type="hidden" name="f_address_key[]" value="${esc([g.sid,g.did,g.cid,g.addr,g.date].join('|'))}"></td><td>${esc(g.date||'-')}</td><td>${items}</td><td><input type="text" class="form-control" name="f_distance[]" value="${g.distance}"></td><td><input type="text" class="form-control edf-famount" name="f_amount[]" value="${amount.toFixed(2)}"></td><td><input type="text" class="form-control edf-fdisc" name="f_discount[]" value="${disc.toFixed(2)}"></td><td><input type="text" class="form-control edf-fpay fw-bold" name="f_payable[]" value="${pay.toFixed(2)}" readonly></td></tr>`;});$('#edf_freight_table tbody').html(h);$('.edf-frow').each(function(){calcFreight($(this));});
}
function setStep(s){$('#edf_step3').toggleClass('d-none',s!==3);$('#edf_step4').toggleClass('d-none',s!==4);$('.wizard-pill[data-step="3"]').toggleClass('active',s===3).toggleClass('done',s===4);$('.wizard-pill[data-step="4"]').toggleClass('active',s===4);}

$(document).off('.edf');
$(document).on('click.edf','#edf_add_row',()=>addRow({}));
$(document).on('change.edf','.edf-state',function(){const $r=$(this).closest('tr');$r.find('.edf-district').html(districtOptions(this.value,''));$r.find('.edf-city').html(cityOptions('','',''));syncNames($r);});
$(document).on('change.edf','.edf-district',function(){const $r=$(this).closest('tr');$r.find('.edf-city').html(cityOptions($r.find('.edf-state').val(),this.value,''));syncNames($r);});
$(document).on('change.edf','.edf-city',function(){syncNames($(this).closest('tr'));});
$(document).on('input.edf change.edf','.edf-qty,.edf-product',updateQty);
$(document).on('click.edf','.edf-remove',function(){const $r=$(this).closest('tr'),id=$r.find('input[name="delivery_rowid[]"]').val();if(id)deleted.push(id);$('#edf_deleted_ids').val([...new Set(deleted)].join(','));$r.remove();updateQty();});
$(document).on('click.edf','.edf-copy',function(){const $r=$(this).closest('tr');addRow({productid:$r.find('.edf-product').val(),stateid:$r.find('.edf-state').val(),districtid:$r.find('.edf-district').val(),cityid:$r.find('.edf-city').val(),cityname:$r.find('.edf-city-name').val(),distance:$r.find('.edf-distance').val(),address:$r.find('.edf-address').val()});});
$(document).on('click.edf','#edf_next',function(){const m=validateStep3();if(m){Swal.fire('Validation',m,'warning');return;}buildFreight();setStep(4);});
$(document).on('click.edf','#edf_back',()=>setStep(3));
$(document).on('input.edf change.edf','.edf-famount,.edf-fdisc',function(){calcFreight($(this).closest('tr'));});

$('#editDeliveryFreightForm').off('submit.edf').on('submit.edf',function(e){e.preventDefault();const m=validateStep3();if(m){setStep(3);Swal.fire('Validation',m,'warning');return;}let bad=false;$('.edf-fdisc').each(function(){if(n(this.value)<0||n(this.value)>100)bad=true;});if(bad){Swal.fire('Validation','Discount must be between 0 and 100.','warning');return;}const $b=$('#edf_save'),old=$b.html();$b.prop('disabled',true).html('<i class="fa fa-spinner fa-spin me-1"></i>Saving...');$.ajax({url:'modal/package/save_order_delivery_freight.php',type:'POST',data:$(this).serialize(),dataType:'json'}).done(res=>{if(res.status==='success'){Swal.fire({icon:'success',title:'Saved Successfully',text:res.message,timer:1400,showConfirmButton:false}).then(()=>{$('#orderDeliveryFreightModal').modal('hide');location.reload();});}else{Swal.fire('Error',res.message||'Save failed.','error');$b.prop('disabled',false).html(old);}}).fail(xhr=>{console.error(xhr.responseText);Swal.fire('Error','Server error while saving.','error');$b.prop('disabled',false).html(old);});});

if(SAVED.length){SAVED.forEach(addRow);}else{PRODUCTS.forEach(p=>addRow({productid:p.productid,qty:p.quantity}));}
updateQty();
})();
</script>
