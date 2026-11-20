
(function () {
    let lastClient = '';
    let clientRequest = null;
    const productRequests = new WeakMap();
    const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    function showItems(items, productOnly) {
        if (!items.length) return;
        const rows = items.map(item => `<tr><td>${escape(item.psono)}</td><td style="text-align:left">${escape(item.product_name)}</td><td>${escape(item.quantity)}</td><td>${escape(item.rate)}</td><td>${escape(item.created_by)}</td></tr>`).join('');
        Swal.fire({icon: productOnly ? 'warning' : 'info', title: productOnly ? 'Product already in Draft orders' : 'Existing Draft orders for this client', width: 900,
            html: `<div style="overflow:auto;max-height:380px"><table class="table table-bordered table-sm"><thead><tr><th>PSO No.</th><th>Product Name</th><th>Qty</th><th>Rate</th><th>Created By</th></tr></thead><tbody>${rows}</tbody></table></div>`, confirmButtonText:'OK'});
    }
    function request(clientid, productid) {
        return $.ajax({url:'modal/client_draft_orders.php', type:'POST', dataType:'json', data:{projectid:$('body').data('projectid'), clientid, productid:productid || '', exclude_psono:$('#edit_psono').val() || ''}});
    }
    window.checkClientDraftOrders = function () {
        if (window.OrderModule && window.OrderModule.hydrating) return;
        const clientid = String($('#clientid').val() || '');
        if (clientid === lastClient) return;
        lastClient = clientid;
        if (clientRequest) clientRequest.abort();
        if (!clientid) return;
        clientRequest = request(clientid, '').done(res => {
            if (String($('#clientid').val() || '') === clientid && res.status === 'success') showItems(res.items || [], false);
        }).fail((xhr, status) => {if (status !== 'abort') {lastClient = ''; Swal.fire({icon:'error',text:'Unable to check Draft orders. Please try selecting the client again.'});}});
    };
    function checkProduct(select) {
        if (window.OrderModule && window.OrderModule.hydrating) return;
        const clientid = String($('#clientid').val() || '');
        const productid = String($(select).val() || '');
        const previous = productRequests.get(select);
        if (previous) previous.abort();
        if (!clientid || !productid) return;
        productRequests.set(select, request(clientid, productid).done(res => {
            if (String($('#clientid').val() || '') === clientid && String($(select).val() || '') === productid && document.contains(select) && res.status === 'success') showItems(res.items || [], true);
        }).fail((xhr, status) => {if (status !== 'abort') Swal.fire({icon:'error',text:'Unable to check this product in Draft orders.'});}));
    }
    $(document).on('change', '#clientid', window.checkClientDraftOrders);
    $(document).on('select2:select', '#provisionorderitemsTable .productname', function () {checkProduct(this);});
    $(document).on('change', '#provisionorderitemsTable .productname', function (event) {
        if (event.originalEvent && !$(this).hasClass('select2-hidden-accessible')) checkProduct(this);
    });
})();

