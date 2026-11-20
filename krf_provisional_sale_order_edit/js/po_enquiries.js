$(function(){
 const $panel=$('#poEnquiryPanel');if(!$panel.length)return;
 const esc=v=>$('<span>').text(v??'').html();let rows=[],identity='',sequence=0,loading=false,adding=false,pendingRow=null;
 const displayName=v=>String(v||'').replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i,'');
 const normalized=v=>displayName(v).trim().replace(/\s+/g,' ').toLowerCase();
 const phone=()=>String($('#mobileno').val()||'').replace(/\D/g,'').slice(-10);
 const selectedInOrder=()=>new Set($('#salesOrderForm input[name="enquiry_selected_ids[]"]').map(function(){return Number(this.value);}).get());
 const api=(action,data={})=>$.ajax({url:'module_order/po_enquiries.php?action='+action,type:'POST',dataType:'json',data:{...data,phone:phone(),clientid:$('#clientid').val()||'',csrf:$panel.attr('data-csrf'),k:window.OrderModule?.config.key||new URL(location.href).searchParams.get('k')||''}});
 const date=v=>{const m=String(v||'').match(/^(\d{4})-(\d{2})-(\d{2})/);return m?m[3]+'-'+['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][Number(m[2])-1]+'-'+m[1]:'';};
 function render(){const used=selectedInOrder(),initial=new Set(window.poEnquiryInitialIds||[]);$('#poEnquiryRows').html(rows.map(r=>`<tr class="${used.has(Number(r.id))?'po-enquiry-agreed':''}"><td><input class="po-enquiry-choice" type="checkbox" value="${r.id}" ${used.has(Number(r.id))?'checked disabled':initial.has(Number(r.id))&&Number(r.id)===Number((window.poEnquiryInitialIds||[])[0])?'checked':''}></td><td>${esc(r.product_name)}${used.has(Number(r.id))?' <small>(Added)</small>':''}</td><td>${esc(r.quantity)}</td><td>${esc(date(r.expected_date))}</td></tr>`).join('')||'<tr><td colspan="4">No open enquiries for this mobile number.</td></tr>');$('#poEnquiryAll').prop('checked',false);}
 async function load(force=false){const next=phone()+'|'+String($('#clientid').val()||'');if(!force&&next===identity)return;if(phone().length!==10){identity=next;sequence++;rows=[];$panel.prop('hidden',true);return;}if(loading&&!force)return;identity=next;loading=true;const request=++sequence;$panel.prop('hidden',false);$('#poEnquiryMessage').text('Loading enquiries…');try{const res=await api('list');if(request!==sequence||next!==phone()+'|'+String($('#clientid').val()||''))return;rows=res.data.rows;$panel.prop('hidden',rows.length===0);render();$('#poEnquiryMessage').text('');}catch(x){if(request===sequence){rows=[];$panel.prop('hidden',true);render();$('#poEnquiryMessage').text(x.responseJSON?.message||'Enquiries could not be loaded. You can still create a PO normally.');}}finally{loading=false;}}
 $('#poEnquiryRefresh').on('click',()=>load(true));$('#poEnquiryRows').on('change','.po-enquiry-choice',function(){if(this.checked)$('.po-enquiry-choice:not(:disabled)').not(this).prop('checked',false);});
 // Explicitly associate checked enquiries with this PO without requiring a matching product.
 $('<button>',{type:'button',id:'poEnquiryLink',class:'btn btn-sm btn-outline-primary ms-2',text:'Link Selected Enquiry to PO'}).insertAfter('#poEnquiryAdd');
 $('#poEnquiryLink').on('click',async function(){
   const ids=$('.po-enquiry-choice:checked').map(function(){return Number(this.value);}).get();
   const existing=[...selectedInOrder()];const all=[...new Set([...existing,...ids])];
   if(!all.length){alertmessage('warning','Select at least one enquiry to link.');return;}
   const $btn=$(this).prop('disabled',true);
   try{const res=await api('prepare',{ids:JSON.stringify(all)});
     $('#salesOrderForm .po-enquiry-linked-input').remove();
     all.forEach(id=>$('<input>',{type:'hidden',name:'enquiry_selected_ids[]',value:id,class:'po-enquiry-linked-input'}).appendTo('#salesOrderForm'));
     let $token=$('#salesOrderForm input[name="enquiry_token"]');if(!$token.length)$token=$('<input>',{type:'hidden',name:'enquiry_token'}).appendTo('#salesOrderForm');$token.val(res.data.token);
     $('#poEnquiryMessage').text(all.length+' enquiry item(s) linked. They will be converted only after the PO saves successfully.');render();
   }catch(e){alertmessage('warning',e.responseJSON?.message||'Unable to link enquiry.');}finally{$btn.prop('disabled',false);}
 });
 $('#poEnquiryAdd').on('click',async function(){if(adding||!window.OrderModule?.ready)return;if(pendingRow&&$.contains(document,pendingRow[0])){alertmessage('warning','Complete stock allocation for the current enquiry item first.');return;}const used=selectedInOrder(),fresh=$('.po-enquiry-choice:checked:not(:disabled)').map(function(){return Number(this.value);}).get().filter(id=>!used.has(id));if(fresh.length>1){alertmessage('warning','Select one enquiry item at a time.');return;}if(!fresh.length){alertmessage('warning','Select enquiry products to add, or add new products normally.');return;}adding=true;$(this).prop('disabled',true);$('#poEnquiryMessage').text('Checking selected enquiries…');try{
  const res=await api('prepare',{ids:JSON.stringify([...used,...fresh])}),p=res.data,master=new Map(p.products.map(r=>[String(r.ProductID),r]));
  const groups=new Map(p.rows.filter(r=>fresh.includes(Number(r.id))).map(r=>[r.id,{...r,ids:[Number(r.id)]}]));
  const $body=$('#provisionorderitemsTable tbody'),$template=(window.OrderModule.itemTemplate||$body.find('tr:first')).clone(false,false);$template.find('.select2-container').remove();$template.find('[data-select2-id]').removeAttr('data-select2-id');$template.find('select').removeClass('select2-hidden-accessible').removeAttr('aria-hidden tabindex');$template.find('input').val('');$template.find('input[name="enquiry_selected_ids[]"],.po-enquiry-reference').remove();
  for(const g of groups.values()){
   const $row=$template.clone(false,false),$product=$row.find('.productname').empty().append(new Option('Select Product',''));
   p.products.forEach(product=>{const $option=$(new Option(displayName(product.ProductName),String(product.ProductID))).attr({'data-priceofproduct':product.PriceOfProduct,'data-isgrafted':['1','yes','grafted'].includes(String(product.IsGrafted).toLowerCase())?'Grafted':'NonGrafted'});$product.append($option);window.allProductsMap=window.allProductsMap||{};window.allProductsMap[product.ProductID]=product;});
   $row.removeData('edit-original');$row.find('.edit_item_id,.stockdbid,.expecteddelivery').val('');$row.find('.isreadytosale').val('0');$row.find('.cgstprt_td,.cgstamt_td,.sgstprt_td,.sgstamt_td,.igstprt_td,.igstamt_td,.distprt_td,.distamt_td').val('0');
   $body.find('tr').filter(function(){return !$(this).find('.productname').val()&&!$(this).find('input[name="enquiry_selected_ids[]"]').length;}).remove();$body.append($row);
   g.ids.forEach(id=>$('<input>',{type:'hidden',name:'enquiry_selected_ids[]',value:id}).appendTo($row.children('td').first()));
   const byId=master.get(String(g.productid||''));
   const matches=p.products.filter(product=>normalized(product.ProductName)===normalized(g.product_name));
   const matched=byId||(matches.length===1?matches[0]:null);
   $product.val(matched?String(matched.ProductID):'');window.OrderModule.initSelect($product,'Select Product');
   $row.find('.rate_td').val(matched?matched.PriceOfProduct:'');
   const requested=Number(g.quantity);$row.find('.qty_td').val(matched&&Number.isInteger(requested)&&requested>0?requested:'');
   if(matched)$row.data('edit-product-changed',true);
   if(!matched)$('<div class="small text-muted po-enquiry-reference">').text('Enquiry: '+g.product_name+' — select matching product manually.').appendTo($row.children('td').first());
   pendingRow=$row;$row.data('enquiry-allocation-pending',true);

  }
  let $token=$('#salesOrderForm input[name="enquiry_token"]');if(!$token.length)$token=$('<input>',{type:'hidden',name:'enquiry_token'}).appendTo('#salesOrderForm');$token.val(p.token);const $first=$body.find('tr').filter(function(){return fresh.includes(Number($(this).find('input[name="enquiry_selected_ids[]"]').first().val()))&&$(this).find('.productname').val();}).first();if($first.length)$first.find('.qty_td').trigger('focus');window.poEnquiryInitialIds=[];render();$('#poEnquiryMessage').text('Complete stock allocation for this item before selecting the next enquiry.');
 }catch(x){const message=x.responseJSON?.message||x.message||'Unable to add selected enquiries.';$('#poEnquiryMessage').text(message);alertmessage('warning',message);}finally{adding=false;$(this).prop('disabled',false);}});
 window.addEventListener('po-enquiry-allocation-complete',()=>{pendingRow=null;render();$('#poEnquiryMessage').text('Item allocated. You can select the next enquiry.');});
 window.addEventListener('po-enquiry-allocation-cancelled',()=>{pendingRow=null;render();$('#poEnquiryMessage').text('Allocation cancelled. Select the enquiry again when required.');});
 let lastUsed='';setInterval(()=>{if(!window.OrderModule?.ready||window.OrderModule.hydrating)return;load();const current=[...selectedInOrder()].join(',');if(current!==lastUsed){lastUsed=current;render();}},1200);
 window.addEventListener('po-enquiry-prefill-ready',()=>load(true));$('#mobileno,#clientid').on('change blur',()=>load());
});
