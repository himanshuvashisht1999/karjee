$(function(){
    const $page=$('#caPage');if(!$page.length)return;
    const root=String($('body').data('routurl')||'').replace(/\/?$/,'/');
    const base=new URL(root+'krf_provisional_sale_order_edit/',location.origin);
    const csrf=$page.attr('data-csrf');let review=null,busy=false;
    const $review=$page.find('#caReview');
    const $refundModal=$page.find('#caRefundModal');
    if($refundModal.length)$refundModal.appendTo(document.body);
    const $refundBackdrop=$('<div>').css({position:'fixed',inset:0,background:'rgba(0,0,0,.5)',zIndex:10990,display:'none'}).appendTo(document.body);
    function closeRefund(){if(busy)return;$refundModal.prop('hidden',true).hide();$refundBackdrop.hide();}
    $('.ca-refund-close').on('click',closeRefund);
    const $backdrop=$('<div class="ca-review-backdrop">').css({position:'fixed',inset:0,background:'rgba(0,0,0,.5)',zIndex:10990,display:'none'});
    if($review.length){
        $review.appendTo(document.body).css({display:'none'});
        $backdrop.appendTo(document.body);
    }
    function closeReview(){ $review.prop('hidden',true).hide();$backdrop.hide();review=null; }
    function openReview(){ $review.prop('hidden',false).show();$backdrop.show();$review.find('.ca-dialog-body').scrollTop(0);$review.find('#caClose').trigger('focus'); }

    const esc=v=>$('<span>').text(v==null?'':String(v)).html();
    const indianDate=v=>{const value=String(v||'');const m=value.match(/^(\d{4})-(\d{2})-(\d{2})(.*)$/);if(!m)return value;const months=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];return m[3]+'-'+months[Number(m[2])-1]+'-'+m[1]+m[4];};
    const warning=m=>alertmessage('warning',m);
    const context=url=>{const current=new URL(location.href);['k','mdid','iframe'].forEach(k=>{if(current.searchParams.has(k))url.searchParams.set(k,current.searchParams.get(k));});return url;};
    $('#caRefundLink').attr('href',context(new URL('confirmed_order_refunds_admin.php',base)).href);
    $('#caBackLink').attr('href',context(new URL('confirmed_order_cancellation_admin.php',base)).href);
    const api=(action,data)=>{const form=data instanceof FormData?data:new FormData();if(!(data instanceof FormData))Object.entries(data||{}).forEach(([k,v])=>form.append(k,v));form.append('csrf',csrf);const k=new URL(location.href).searchParams.get('k');if(k)form.append('k',k);return $.ajax({url:new URL('module_order/cancellation_api.php?action='+action,base).href,type:'POST',data:form,processData:false,contentType:false,dataType:'json'});};
    const fail=x=>warning(x.responseJSON?.message||'The request failed. Please refresh and try again.');
    const gridState=new Map();let $gridPopup=null;
    function closeGridPopup(){if($gridPopup)$gridPopup.remove();$gridPopup=null;}
    function stateFor(table){if(!gridState.has(table))gridState.set(table,{filters:{},sort:null});return gridState.get(table);}
    function applyGridFilters($table){
        const state=stateFor($table[0]),search=String($('#caSearch').val()||'').toLowerCase();
        if(state.sort){const {column,direction}=state.sort;const rows=$table.find('tbody tr').get();rows.sort((a,b)=>$(a).children('td').eq(column).text().trim().localeCompare($(b).children('td').eq(column).text().trim(),undefined,{numeric:true})*direction);$table.find('tbody').append(rows);}
        $table.find('tbody tr').each(function(){const $row=$(this);const matches=Object.entries(state.filters).every(([column,values])=>values.includes($row.children('td').eq(Number(column)).text().trim()));const globalMatch=!$table.is('#caOrders,#caSettlements')||$row.text().toLowerCase().includes(search);$row.toggle(matches&&globalMatch);});
        $table.find('.ca-column-filter').each(function(){$(this).toggleClass('active',Object.prototype.hasOwnProperty.call(state.filters,this.dataset.column));});
    }
    function refreshGridFilters(){
        $('#caOrders,#caSettlements').each(function(){
            const $table=$(this),$head=$table.find('thead');if(!$head.length)return;
            $head.find('.ca-filter-row').remove();$head.find('.ca-column-filter,.ca-grid-reset').remove();
            $head.find('tr').first().children('th').each(function(column){
                const title=$(this).text().trim();
                if(!/^(action|actions)$/i.test(title)&&column<$table.find('tbody tr').first().children('td').length&&!$table.find('tbody tr').first().children('td').eq(column).find('button').length){$(this).append($('<button type="button" class="ca-column-filter">').attr('data-column',column).attr('aria-label','Filter '+title).attr('title','Filter '+title).text('▼'));}
            });
            $head.find('th').last().append($('<button type="button" class="ca-grid-reset">').text('Reset filters'));
            applyGridFilters($table);
        });
    }
    $(document).on('click','.ca-column-filter',function(e){
        e.stopPropagation();closeGridPopup();const button=this,$table=$(this).closest('table'),column=Number(this.dataset.column),state=stateFor($table[0]);
        const choices=[...new Set($table.find('tbody tr').map(function(){return $(this).children('td').eq(column).text().trim();}).get())].sort((a,b)=>a.localeCompare(b,undefined,{numeric:true}));
        const chosen=new Set(state.filters[column]||choices);
        $gridPopup=$('<div class="ca-grid-popup" role="dialog" aria-label="Column filter">').appendTo(document.body);
        const $popup=$gridPopup;
        $popup.html('<div class="ca-grid-title"><strong></strong><button type="button" data-close>×</button></div><div class="ca-grid-actions"><button type="button" data-sort="1">Sort A → Z</button><button type="button" data-sort="-1">Sort Z → A</button></div><input type="search" data-find placeholder="Search values…" aria-label="Search filter values"><div class="ca-grid-actions"><button type="button" data-all>Select all</button><button type="button" data-none>Select none</button></div><div class="ca-grid-values"></div><div class="ca-grid-actions"><button type="button" data-apply>Apply</button><button type="button" data-clear>Clear column</button><button type="button" data-reset>Reset all</button></div>');
        $popup.find('strong').text(button.title.replace('Filter ',''));
        function render(){const q=String($popup.find('[data-find]').val()||'').toLowerCase();const $values=$popup.find('.ca-grid-values').empty();choices.forEach((value,index)=>{if(!(value||'(Blank)').toLowerCase().includes(q))return;const $check=$('<input type="checkbox">').prop('checked',chosen.has(value)).on('change',function(){if(this.checked)chosen.add(value);else chosen.delete(value);});$values.append($('<label>').append($check,$('<span>').text(value||'(Blank)')));});}
        const rect=button.getBoundingClientRect();$popup.css({left:Math.max(8,Math.min(rect.left,innerWidth-328)),top:8});render();$popup.css('top',Math.max(8,Math.min(rect.bottom+4,innerHeight-$popup.outerHeight()-8)));
        $popup.on('click',function(event){event.stopPropagation();});$popup.find('[data-find]').on('input',render);
        $popup.find('[data-close]').on('click',closeGridPopup);
        $popup.find('[data-all],[data-none]').on('click',function(){const q=String($popup.find('[data-find]').val()||'').toLowerCase();choices.filter(v=>(v||'(Blank)').toLowerCase().includes(q)).forEach(v=>{if(this.hasAttribute('data-all'))chosen.add(v);else chosen.delete(v);});render();});
        $popup.find('[data-apply]').on('click',function(){if(chosen.size===choices.length)delete state.filters[column];else state.filters[column]=[...chosen];applyGridFilters($table);closeGridPopup();});
        $popup.find('[data-clear]').on('click',function(){delete state.filters[column];applyGridFilters($table);closeGridPopup();});
        $popup.find('[data-reset]').on('click',function(){state.filters={};state.sort=null;applyGridFilters($table);closeGridPopup();});
        $popup.find('[data-sort]').on('click',function(){state.sort={column,direction:Number(this.dataset.sort)};applyGridFilters($table);closeGridPopup();});
    });
    $(document).on('click','.ca-grid-reset',function(){const $table=$(this).closest('table'),state=stateFor($table[0]);state.filters={};state.sort=null;if($table.is('#caOrders,#caSettlements'))$('#caSearch').val('');applyGridFilters($table);closeGridPopup();});
    $(document).on('click',closeGridPopup).on('keydown',function(e){if(e.key==='Escape')closeGridPopup();});
    $('#caSearch').on('input',function(){$('#caOrders,#caSettlements').each(function(){applyGridFilters($(this));});});
    function orders(){return api('list').done(res=>{$('#caOrders tbody').html(res.data.map(r=>`<tr><td>${esc(r.clientname)}</td><td>${esc(r.mobileno)}</td><td>${esc(r.psono)}</td><td>${esc(r.sono)}</td><td>${esc(indianDate(r.created_at))}</td><td><button class="btn btn-sm btn-danger ca-open" ${Number(r.editing_allowed)===0?'disabled title="Item status does not allow cancellation"':''} data-pso="${esc(r.psono)}">Cancel Order</button></td></tr>`).join(''));refreshGridFilters();}).fail(fail);}
    $('#caOrders').on('click','.ca-open',function(){if(busy)return;busy=true;closeReview();const $clicked=$(this);$clicked.prop('disabled',true).text('Loading…');api('preview',{psono:$(this).attr('data-pso')}).done(res=>{
        review=res.data;const s=review.snapshot;
        $('#caClient').text(s.header.clientname+' — '+s.header.psono+' / '+s.sale.sono);$('#caClientContact').text('Phone: '+(s.header.mobileno||'—')+' | Invoice address: '+(s.invoice_address||'—'));
        $('#caItems tbody').html(s.items.map(r=>`<tr><td>${esc(r.product_name)}</td><td>${esc(r.stockorder_no)}</td><td>${esc(r.quantity)}</td><td>${esc(r.rate)}</td><td>${esc(r.amount)}</td></tr>`).join(''));
        $('#caSowing tbody').html(s.production.flatMap(p=>p.schedules.filter(r=>!['removed','delete','notrequired'].includes(r.status)).map(r=>{const actual=p.trays.filter(t=>t.roottype===r.roottype).reduce((sum,t)=>sum+Number(t.traycavity||0),0);return `<tr><td>${esc(p.stock.stockorder_no)}</td><td>${esc(r.roottype)}</td><td>${esc(r.sowingqty)}</td><td>${actual}</td><td>${Math.max(0,Number(r.sowingqty)-actual)}</td><td>${esc(r.status)}</td></tr>`;})).join(''));
        let verified=0,unverified=0;
        $('#caPayments tbody').html(s.payments.map(p=>{const valid=p.verification==='verified';if(valid)verified+=Number(p.advanceamount);else unverified+=Number(p.advanceamount);return `<tr data-payment="${esc(p.sno)}"><td>${esc(p.sno)}</td><td>${esc(indianDate(p.paymentdate))}</td><td>${esc(p.utrnumber)}</td><td>${esc(p.verification)}</td><td>${esc(p.advanceamount)}</td></tr>`;}).join(''));
        $('#caPaymentNote').text('Verified ₹'+verified.toFixed(2)+'; unverified ₹'+unverified.toFixed(2)+(unverified>0?'. Resolve unverified payments before cancellation.':''));
        $('#caCancel').prop('disabled',unverified>0||s.editing_allowed===false);if(s.editing_allowed===false)warning(s.editing_warning);$('input[name="ca-settlement-choice"]').prop('checked',false);$('#caDecision').prop('hidden',verified<=0);$('#caReason').val('');refreshGridFilters();openReview();
    }).fail(fail).always(()=>{busy=false;$clicked.prop('disabled',false).text('Cancel Order');});});
    $('#caClose,#caCloseTop').on('click',closeReview);
    $('#caCancel').on('click',async function(){if(!review||busy)return;const reason=$('#caReason').val().trim();if(!reason){warning('Please enter the cancellation reason.');return;}const verified=review.snapshot.payments.filter(p=>p.verification==='verified'&&Number(p.advanceamount)>0);const choice=$('input[name="ca-settlement-choice"]:checked').val();if(verified.length&&!choice){warning('Please choose Refund the Amount or Mark as Advance.');return;}const split={};verified.forEach(p=>{split[String(p.sno)]={refund:choice==='refund'?String(p.advanceamount):'0',advance:choice==='advance'?String(p.advanceamount):'0'};});
        busy=true;try{const answer=await Swal.fire({title:'Cancel confirmed order?',text:(choice==='refund'?'The verified advance will be marked Refund Pending.':choice==='advance'?'The verified amount will be retained as client advance.':'This order has no verified advance to settle.')+' Actual sowing is retained.',icon:'warning',showCancelButton:true,confirmButtonText:'Yes, Cancel Order'});if(!answer.isConfirmed)return;
        const saved=await api('cancel',{psono:review.snapshot.header.psono,token:review.token,split:JSON.stringify(split),reason});alertmessage('success','Order cancelled. Refunds are pending; retained advances have been recorded.');closeReview();orders();if(saved.notification?.status==='failed')warning(saved.notification.message);}catch(x){fail(x);}finally{busy=false;}});
    function settlements(){return api('settlements').done(res=>{$('#caSettlements tbody').html(res.data.map(r=>`<tr><td>${esc(r.psono)}</td><td>${esc(r.sono)}</td><td>${esc(r.clientname||r.clientid)}</td><td>${esc(r.mobileno)}</td><td>${esc(r.payment_sno)}</td><td>${esc(r.refund_amount)}</td><td>${esc(r.retained_amount)}</td><td>${esc(r.refund_status)}</td><td>${esc(r.refund_utr||'')} ${esc(indianDate(r.refund_date))}</td><td>${r.refund_status==='Pending'?`<button class="btn btn-sm btn-primary ca-refund-open" data-id="${esc(r.id)}" data-amount="${esc(r.refund_amount)}" data-client="${esc(r.clientname||r.clientid)}" data-pso="${esc(r.psono)}" data-so="${esc(r.sono||'')}" data-payment="${esc(r.payment_sno)}" data-phone="${esc(r.mobileno)}" data-address="${esc(r.invoice_address)}">Record Refund</button>`:''}${r.refund_document?`<button class="btn btn-sm btn-secondary ca-document" data-id="${esc(r.id)}">Proof</button>`:''}</td></tr>`).join(''));refreshGridFilters();}).fail(fail);}
    $('#caSettlements').on('click','.ca-refund-open',function(){const f=$('#caRefundForm')[0];f.reset();f.elements.detail_id.value=$(this).attr('data-id');f.elements.amount.value=$(this).attr('data-amount');$('#caRefundClient').text($(this).attr('data-client'));$('#caRefundContact').text('Phone: '+($(this).attr('data-phone')||'—')+' | Invoice address: '+($(this).attr('data-address')||'—'));$('#caRefundOrder').text($(this).attr('data-pso')+' / '+($(this).attr('data-so')||'—'));$('#caRefundPayment').text('#'+$(this).attr('data-payment'));$('#caRefundAmount').text('₹'+Number($(this).attr('data-amount')).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}));$refundModal.prop('hidden',false).show();$refundBackdrop.show();$('#caRefundUTR').trigger('focus');});
    $('#caSettlements').on('click','.ca-document',function(){const f=$('<form>',{method:'POST',action:new URL('module_order/cancellation_api.php?action=document',base).href,target:'_blank'});Object.entries({detail_id:$(this).attr('data-id'),csrf,k:new URL(location.href).searchParams.get('k')||''}).forEach(([name,value])=>f.append($('<input>',{type:'hidden',name,value})));f.appendTo('body')[0].submit();f.remove();});
    $('#caRefundForm').on('submit',async function(e){e.preventDefault();if(busy)return;
        const utr=this.elements.utr.value.trim(),date=this.elements.transaction_date,proof=this.elements.proof.files[0];
        if(!utr){warning('Please enter the UTR / transaction number.');this.elements.utr.focus();return;}
        if(utr.length>45){warning('UTR / transaction number must not exceed 45 characters.');return;}
        if(!date.value||date.validity.badInput){warning('Please enter a valid refund transaction date.');date.focus();return;}
        const today=new Intl.DateTimeFormat('en-CA',{timeZone:'Asia/Kolkata',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(new Date());
        const part=type=>today.find(p=>p.type===type).value;date.max=part('year')+'-'+part('month')+'-'+part('day');
        if(date.value>date.max){warning('Refund transaction date cannot be later than today ('+indianDate(date.max)+').');date.focus();return;}
        if(!date.checkValidity()){warning('Please enter a valid refund transaction date.');date.focus();return;}
        if(!proof){warning('Please upload the refund transaction proof.');return;}
        if(proof.size>5*1024*1024){warning('Refund proof must not exceed 5 MB.');return;}
        if(!/\.(pdf|jpe?g|png)$/i.test(proof.name)){warning('Only PDF, JPG or PNG proof is allowed.');return;}
        busy=true;try{const confirm=await Swal.fire({title:'Record completed refund?',text:'Confirm that this amount has actually been paid to the client.',icon:'question',showCancelButton:true});if(!confirm.isConfirmed)return;const saved=await api('refund',new FormData(this));alertmessage('success','Refund transaction and proof recorded.');this.reset();$refundModal.prop('hidden',true).hide();$refundBackdrop.hide();settlements();if(saved.notification?.status==='failed')warning(saved.notification.message);}catch(x){fail(x);}finally{busy=false;}});
    if($page.attr('data-mode')==='refunds')settlements();else orders();
});
