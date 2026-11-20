(function () {
    // The application SearchSelect enhancer must not wrap this modal's Select2 controls.
    function cleanEnquirySearchSelect() {
        const modal = document.getElementById('newProductEnquiryModal');
        if (!modal) return;
        modal.querySelectorAll('select').forEach(function(select) {
            if (select.classList.contains('searchselect')) select.classList.remove('searchselect');
        });
        modal.querySelectorAll('.ss-container').forEach(function(wrapper) { wrapper.remove(); });
    }
    window.cleanEnquirySearchSelect = cleanEnquirySearchSelect;
    $(function() {
        cleanEnquirySearchSelect();
        const modal=document.getElementById('newProductEnquiryModal');
        if (modal) new MutationObserver(cleanEnquirySearchSelect).observe(modal, {
            childList:true, subtree:true, attributes:true, attributeFilter:['class']
        });
    });
    function addRow(focus) {
       /* const $row = $('<tr><td><select class="enq-product form-control"><option></option></select></td><td><div class="input-group enq-date-group"><input class="enq-date form-control" type="text" placeholder="dd-mm-yyyy" maxlength="10" aria-label="Expected Date"><span class="input-group-text enq-calendar-wrap"><i class="fa fa-calendar" aria-hidden="true"></i><input class="enq-date-picker" type="date" aria-label="Choose Expected Date"></span></div></td><td><input class="enq-qty form-control" type="number" min="0" step="any" aria-label="Quantity"></td><td><button type="button" class="enq-delete btn btn-sm btn-danger" title="Delete row">Delete</button></td></tr>');*/
        // Get tomorrow's date in local time (YYYY-MM-DD)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);

        const minDate = [
            tomorrow.getFullYear(),
            String(tomorrow.getMonth() + 1).padStart(2, '0'),
            String(tomorrow.getDate()).padStart(2, '0')
        ].join('-');

        const $row = $(`
        <tr>
            <td>
                <select class="enq-product form-control">
                    <option></option>
                </select>
            </td>
            <td>
                <div class="input-group enq-date-group">
                    <input class="enq-date form-control"
                           type="text"
                           placeholder="dd-mm-yyyy"
                           maxlength="10"
                           aria-label="Expected Date">

                    <span class="input-group-text enq-calendar-wrap">
                        <i class="fa fa-calendar"></i>
                        <input class="enq-date-picker"
                               type="date"
                               min="${minDate}"
                               aria-label="Choose Expected Date">
                    </span>
                </div>
            </td>
            <td>
                <input class="enq-qty form-control"
                       type="number"
                       min="0"
                       step="any"
                       aria-label="Quantity">
            </td>
            <td>
                <button type="button"
                        class="enq-delete btn btn-sm btn-danger"
                        title="Delete row">
                    Delete
                </button>
            </td>
        </tr>
        `);
        const $select = $row.find('.enq-product');
        const products = window.allProductsMap || {};
        Object.keys(products).forEach(id => $select.append(new Option((products[id].ProductName || '').replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, ''), id)));
        $('#enquiryItemsTable tbody').append($row);
        if ($('#newProductEnquiryModal').hasClass('show')) initializeSelect($select);
        if (focus) $select.select2('open');
        return $row;
    }
    function initializeSelect($select) {
        cleanEnquirySearchSelect();
        if ($select.hasClass('select2-hidden-accessible')) return;
        $select.select2({width:'100%', placeholder:'Select or type product',
            dropdownParent:$('#newProductEnquiryModal'), tags:true, multiple:false, closeOnSelect:true,
            templateResult:function(item){return item.text;}, templateSelection:function(item){return item.text;},
            createTag:function(params){const text=params.term.trim();return text?{id:'manual:'+text,text:text,newTag:true}:null;}});
    }
    function dateValue(text) {
        if (!text) return '';
        const match = /^(\d{2})-(\d{2})-(\d{4})$/.exec(text);
        if (!match) throw new Error('Enter Expected Date as dd-mm-yyyy.');
        const day=Number(match[1]), month=Number(match[2]), year=Number(match[3]);
        const date=new Date(year,month-1,day);
        if(date.getFullYear()!==year || date.getMonth()!==month-1 || date.getDate()!==day) throw new Error('Enter a valid Expected Date.');
        return match[3]+'-'+match[2]+'-'+match[1];
    }
    window.EnquiryGrid = {
        initialize:function() {$('#enquiryItemsTable .enq-product').each(function(){initializeSelect($(this));});},
        reset:function() {$('#enquiryItemsTable .enq-product').each(function(){if($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');}); $('#enquiryItemsTable tbody').empty(); addRow(false);},
        items:function() {
            const items=[];
            $('#enquiryItemsTable tbody tr').each(function() {
                const $row=$(this), id=String($row.find('.enq-product').val() || ''), qty=$row.find('.enq-qty').val(), date=$row.find('.enq-date').val().trim();
                if (!id && !qty && !date) return;
                if (!id || !Number.isFinite(Number(qty)) || Number(qty)<=0) throw new Error('Select or enter a product and enter Qty greater than zero for each row.');
                items.push({productid:id.startsWith('manual:') ? null : id, product_name:$row.find('.enq-product option:selected').text(), quantity:qty, expected_date:dateValue(date)});
            });
            if (!items.length) throw new Error('Enter at least one product requirement.');
            return items;
        }
    };
    $(document).on('change', '#enquiryItemsTable .enq-date-picker', function () {
        const parts=this.value.split('-');
        $(this).closest('.enq-date-group').find('.enq-date').val(parts.length===3 ? parts[2]+'-'+parts[1]+'-'+parts[0] : '').trigger('change');
    });
    $(document).on('change', '#enquiryItemsTable .enq-date', function () {
        try { $(this).closest('.enq-date-group').find('.enq-date-picker').val(dateValue(this.value.trim())); }
        catch(error) { $(this).closest('.enq-date-group').find('.enq-date-picker').val(''); }
    });
    $(document).on('click', '#enquiryItemsTable .enq-date-picker', function () {
        if (typeof this.showPicker === 'function') { try { this.showPicker(); } catch(error) {} }
    });
    $(document).on('keydown','#enquiryItemsTable .enq-qty',function(event) {
        if(event.key!=='Enter') return;
        event.preventDefault(); if(event.repeat) return;
        const $row=$(this).closest('tr');
        if(!$row.find('.enq-product').val() || !Number.isFinite(Number(this.value)) || Number(this.value)<=0) return;
        try {dateValue($row.find('.enq-date').val().trim());} catch(error) {Swal.fire('Expected Date',error.message,'warning');return;}
        if($row.is(':last-child')) addRow(true); else $row.next().find('.enq-product').select2('open');
    });
    $(document).on('click','#enquiryItemsTable .enq-delete',function() {
        const $row=$(this).closest('tr'); if ($row.find('.enq-product').hasClass('select2-hidden-accessible')) $row.find('.enq-product').select2('destroy'); $row.remove();
        if(!$('#enquiryItemsTable tbody tr').length) addRow(false);
    });
    $(function(){window.EnquiryGrid.reset();});
})();
