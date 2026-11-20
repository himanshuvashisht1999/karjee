window.renderConfirmedSowing = function(rows) {
    const $body = $('#confirmedSowingRows').empty();
    for (const row of rows) {
        const $tr = $('<tr>');
        const $identity = $('<td>').appendTo($tr);
        $('<div>').text(row.stockorder_no ?? '').appendTo($identity);
        $('<small>', {class:'d-block text-muted'}).text([row.product_name,row.roottype].filter(Boolean).join(' — ')).appendTo($identity);
        for (const key of ['stock_qty','order_qty','planned','actual','remaining','status']) $('<td>').text(row[key] ?? '').appendTo($tr);
        $body.append($tr);
    }
    if (!rows.length) $body.append($('<tr>').append($('<td>',{colspan:7,text:'No production records available.'})));
};

window.handleConfirmedQuantityEdit = function($row,originalItem,allocate){
    if (window.OrderModule.isConfirmed() && originalItem && String($row.find('.productname').val()) === String(originalItem.product_id)) {
        const $input=$row.find('.qty_td');
        const requested = Number($input.val()), baseQty = Number(originalItem.quantity);
        if (!Number.isInteger(requested) || requested <= 0) { alertmessage('warning','Enter a positive whole plant quantity.'); $input.val(baseQty); return true; }
        if (requested <= baseQty) { $row.find('.ratecal').trigger('input'); $input.data('edit-last-qty', requested); $row.data('edit-qty-changed', false); return true; }
        const delta = requested - baseQty;
        $input.val(baseQty); $row.find('.ratecal').trigger('input');
        const beforeCount=$('#provisionorderitemsTable tbody tr').length;
        $('#addRow').trigger('click');
        const $extra = $('#provisionorderitemsTable tbody tr:last');
        if ($('#provisionorderitemsTable tbody tr').length !== beforeCount+1 || $extra[0] === $row[0]) { alertmessage('warning','Complete the current item before adding an allocation.'); return true; }
        $extra.find('.productname').val(originalItem.product_id).trigger('change.select2');
        $extra.find('.rate_td').val($row.find('.rate_td').val());
        $extra.find('.distprt_td').val($row.find('.distprt_td').val());
        $extra.find('.qty_td').val(delta).data('edit-last-qty',delta);
        $extra.find('.expecteddelivery').val($row.find('.expecteddelivery').val());
        $extra.find('.edit_item_id,.stockdbid,.isreadytosale').val('');
        $extra.removeData('edit-original').data('confirmed-additional',true).data('confirmed-base-qty',baseQty).data('confirmed-requested-qty',requested).data('edit-qty-pending',true);
        $extra.find('.ratecal').trigger('input');
        allocate($extra);
        return true;
      }
    return false;
};

// An empty optional discount/tax field represents zero; leave other input untouched.
window.normaliseConfirmedOptionalAmounts = function($form) {
    $form.find('[name="distprt[]"],[name="distamt[]"],[name="cgstprt[]"],[name="cgstamt[]"],[name="sgstprt[]"],[name="sgstamt[]"],[name="igstprt[]"],[name="igstamt[]"]').each(function() {
        const $input = $(this);
        if (String($input.val() ?? '').trim() === '') $input.val('0');
    });
};
