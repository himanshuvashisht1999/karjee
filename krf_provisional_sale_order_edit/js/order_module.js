/* Shared Add/Edit controller. Saved allocations are preserved only in Edit mode. */
window.OrderModule = {
    ready: false,
    hydrating: false,
    snapshot: null,
    config: {},
    itemTemplate: null,
    freightFingerprint: '',
    value(row, names, fallback = '') {
        for (const name of names) if (row[name] !== null && row[name] !== undefined) return row[name];
        return fallback;
    },
    message(text, error = false) {
        $('#editOrderMessage').removeClass('alert-info alert-success alert-danger')
            .addClass(error ? 'alert-danger' : 'alert-success').text(text);
    },
    isConfirmed() { return this.config.kind === 'confirmed'; },
    isEdit() { return this.config.mode === 'edit' || this.isAddress(); },
    isAddress() { return this.config.mode === 'address'; },
    async start() {
        const params = new URL(window.location.href).searchParams;
        if (params.get('mode') === 'address') this.config.mode = 'address';
        if (!this.config.orderno) this.config.orderno = params.get('orderno') || params.get('psono') || '';
        if (this.isEdit()) return this.load();
        this.hydrating = true;
        try {
            const res = await $.ajax({url:'module_order/load_add_masters.php',type:'POST',dataType:'json'});
            if (res.status !== 'success') throw new Error(res.message || 'Unable to load masters.');
            stateDistrictArray = res.states || [];
            this.snapshot = {header:{},items:[],delivery:[]};
            this.fillClient({}, {});
            this.itemTemplate = $('#provisionorderitemsTable tbody tr:first').clone(false, false);
            this.ready = true;
            this.message('New provisional order');
            const enquiryToken=params.get('enquiry_token');
            if(enquiryToken){
                const p=await $.ajax({url:'module_order/enquiry_prefill.php',type:'POST',dataType:'json',data:{token:enquiryToken,k:this.config.key}});
                let client=p.client,header=p.header;
                if(p.matches.length===1){client=p.matches[0];header={...header,clientid:client.client_id,clientname:client.client_name};}
                if(p.matches.length>1){const options={};p.matches.forEach(c=>options[c.client_id]=c.client_name+' | '+c.phones+' | '+(c.invoicing_address||''));const choice=await Swal.fire({title:'Select enquiry client',input:'select',inputOptions:options,inputPlaceholder:'Select correct client',showCancelButton:true,inputValidator:v=>v?undefined:'Select the correct client.'});if(!choice.isConfirmed)throw new Error('Client selection cancelled. Return to enquiry follow-up.');client=p.matches.find(c=>String(c.client_id)===String(choice.value));header={...header,clientid:client.client_id,clientname:client.client_name};}
                const back=new URL('../krf_enquiry_followup/'+(p.admin?'enquiry_followup_admin.php':'enquiry_followup.php'),location.href);back.searchParams.set('k',this.config.key||params.get('k')||'');back.searchParams.set('iframe','true');
                window.poEnquiryReturnUrl=back.href;
                $('.gotoprovisionalorderlist').html('<i class="fa fa-arrow-left me-2"></i>Back to Enquiry Follow-up');
                this.fillClient(header,client);
                window.poEnquiryInitialIds=p.ids||[];
                window.dispatchEvent(new Event('po-enquiry-prefill-ready'));
                this.message('Enquiry → New provisional order. Choose enquiry products from Client Enquiries if interested, or add new products normally.');
            }

        } catch(error) { this.ready=false;this.message(error.responseJSON ? error.responseJSON.message : error.message, true); }
        finally { this.hydrating = false; $('#salesOrderForm').attr('aria-busy','false'); }
    },
    async load() {
        const self = this;
        if (!self.config.orderno) { self.message('Pass the PSO number using ?orderno=YOUR_PSO_NO', true); return; }
        self.hydrating = true;
        try {
            const res = await $.ajax({url: self.isConfirmed() ? 'module_order/load_confirmed_order.php' : 'module_order/load_edit_order.php', type: 'POST', dataType: 'json',
                data: {orderno: self.config.orderno, projectid: self.config.projectid, k: self.config.key, mode:self.config.mode}});
            if (res.status !== 'success') throw new Error(res.message || 'Unable to load order.');
            self.snapshot = res.data; self.snapshotToken = res.snapshot_token || ""; if(self.isConfirmed() && window.renderConfirmedSowing) window.renderConfirmedSowing(res.sowing || []);
            stateDistrictArray = res.states || [];
            self.fillClient(res.data.header, res.client || {});
            self.fillItems(res.data.items, res.products || []);
            $('#edit_psono').val(res.data.header.psono);
            $('#edit_sono').val(res.data.header.sono || '');
            $('#salesOrderForm textarea[name="remarks"]').val(res.data.header.remarks || '');
            const mode = res.data.header.pickupfrom || 'pickup';
            $("input[name='pickupfrom']").prop('checked', false).filter(function () { return this.value === mode; }).prop('checked', true);
            $('#deliveryaddressdiv').toggle(mode === 'delivered');
            $('#deliveryboxdiv').toggleClass('d-none', mode !== 'boxpacking');
            $('#btnSkipDeliveryStep').toggleClass('d-none', mode === 'boxpacking');
            if (mode === 'delivered') self.fillDelivered(res.data.delivery);
            if (mode === 'boxpacking') await self.fillBox(res.data.delivery);
            window.saleOrderRules.generateFreightGroups(true);
            self.restoreFreight();
            self.freightFingerprint = self.inputsFingerprint();
            self.boxFingerprint = self.boxSourceFingerprint();
            self.hydrating = false;
            self.ready = true;
            if (self.isAddress() && window.goToStep) window.goToStep(3);
            $('#salesOrderForm').attr('aria-busy', 'false');
            self.message('PSO No: ' + res.data.header.psono + (self.isConfirmed() ? ' | SO No: ' + res.data.header.sono : '') + ' — saved details loaded.');
            if (window.renderFreightEligibilityRules) window.renderFreightEligibilityRules(false);
            if(self.isConfirmed() && res.data.editing_allowed===false){self.ready=false;self.statusBlocked=true;$('#salesOrderForm').find('input,select,textarea,button').not('[id*=Back],[id*=back],.btn-back').prop('disabled',true);self.message(res.data.editing_warning,true);alertmessage('warning',res.data.editing_warning);}
        } catch (e) {
            self.hydrating = false;
            self.ready = false;
            self.message(e.responseJSON ? e.responseJSON.message : e.message || 'Unable to load order.', true);
        }
    },
    fillClient(header, client) {
        // Set identity first so shared address handlers do not treat this as a new client.
        $('#clientid').val(header.clientid || '');
        $('#customer_type').val(String(client.is_dealer) === '1' ? 'Dealer' : 'Farmer');
        $('#clientname').val(header.clientname || client.client_name || '');
        $('#mobileno').val(header.mobileno || client.phones || '');
        $('#emailid').val(header.emailid || client.client_email || '');
        $('#cityname').val(client.cityname || '');
        $('#tahseel').val(client.tahseel || '');
        $('#delivery_address').val(header.delivery_address || client.invoicing_address || '');
        const stateId = String(client.stateid || '');
        const districtId = String(client.districtid || '');
        const $state = $('#statename').empty().append(new Option('Select State', ''));
        const states = new Set();
        stateDistrictArray.forEach(row => {
            const id = String(row.stateid);
            if (states.has(id)) return;
            states.add(id);
            const option = new Option(row.statename, row.statename);
            $(option).attr('data-stateid', id);
            $state.append(option);
        });
        const saved = stateDistrictArray.find(row => String(row.stateid) === stateId)
            || stateDistrictArray.find(row => row.statename === header.statename);
        if (saved) $state.val(saved.statename);
        this.initSelect($state, 'Select State');
        $state.trigger('change.select2');
        $('#stateid').val(saved ? saved.stateid : '');
        // Shared pjs helper uses state/district names as option values and IDs as metadata.
        const fillDistricts = selected => {
            const selectedState = String($('#stateid').val() || '');
            const $district = $('#districtname').empty().append(new Option('Select District', ''));
            const used = new Set();
            let choice = '';
            stateDistrictArray.forEach(row => {
                const id = String(row.districtid || '');
                if (!selectedState || String(row.stateid) !== selectedState || !id || used.has(id)) return;
                used.add(id);
                const option = new Option(row.districtname, row.districtname);
                $(option).attr('data-districtid', id);
                $district.append(option);
                if (id === String(selected) || row.districtname === selected) choice = row.districtname;
            });
            this.initSelect($district, 'Select District');
            $district.val(choice).trigger('change.select2');
            $('#districtid').val($district.find('option:selected').attr('data-districtid') || '');
        };
        fillDistricts(districtId || header.districtname || '');
        const updateAddress = () => {
            if (window.OrderModule.hydrating) return;
            const address = ['cityname', 'tahseel', 'districtname', 'statename']
                .map(id => String($('#' + id).val() || '').trim())
                .filter(Boolean).join(', ');
            $('#delivery_address').val(address).trigger('change');
        };
        $state.off('change.editOrder').on('change.editOrder', function () {
            $('#stateid').val($(this).find('option:selected').attr('data-stateid') || '');
            fillDistricts('');
            updateAddress();
        });
        $('#districtname').off('change.editAddress').on('change.editAddress', function () {
            $('#districtid').val($(this).find('option:selected').attr('data-districtid') || '');
            updateAddress();
        });
        $('#tahseel, #cityname').off('input.editAddress change.editAddress')
            .on('input.editAddress change.editAddress', updateAddress);
    },
    initSelect($select, placeholder) {
        if ($.fn.select2 && !$select.hasClass('select2-hidden-accessible')) $select.select2({width: '100%', placeholder});
    },
    fillItems(items, products) {
        const $body = $('#provisionorderitemsTable tbody');
        const $template = $body.find('tr:first').clone(false);
        $template.find('.select2-container').remove();
        $template.find('[data-select2-id]').removeAttr('data-select2-id');
        $template.find('select').removeClass('select2-hidden-accessible').removeAttr('aria-hidden tabindex');
        $template.find('input').val('');
        this.itemTemplate = $template;
        $body.empty();
        globalProductOptions = '<option value="">Select Product</option>';
        const $options = $('<select>').append(new Option('Select Product', ''));
        window.allProductsMap = window.allProductsMap || {};
        products.forEach(product => {
            const option = new Option(product.ProductName, String(product.ProductID));
            const grafted = ['1','yes','grafted'].includes(String(product.IsGrafted).toLowerCase()) ? 'Grafted' : 'NonGrafted';
            $(option).attr({'data-priceofproduct': product.PriceOfProduct, 'data-isgrafted': grafted});
            $options.append(option);
            window.allProductsMap[product.ProductID] = product;
        });
        globalProductOptions = $options.html();
        items.forEach(item => {
            const $row = $template.clone(false);
            const $product = $row.find('.productname').html(globalProductOptions);
            if (!products.some(p => String(p.ProductID) === String(item.product_id))) {
                $product.append(new Option(item.product_name, String(item.product_id)));
            }
            $product.val(String(item.product_id));
            const fields = {'.edit_item_id': item.id, '.rate_td': item.rate, '.qty_td': item.quantity,
                '.txableamount_td': item.taxableamount, '.cgstprt_td': item.cgst_percent, '.cgstamt_td': item.cgst_amount,
                '.sgstprt_td': item.sgst_percent, '.sgstamt_td': item.sgst_amount, '.igstprt_td': item.igst_percent,
                '.igstamt_td': item.igst_amount, '.distprt_td': item.discount_percent, '.distamt_td': item.discount_amount,
                '.payableamount_td': item.amount, '.roundoffamount_td': item.roundoffamount ?? 0, '.expecteddelivery': String(item.expecteddeliverydate || '').slice(0, 10),
                '.stockdbid': item.edit_stock_id, '.isreadytosale': '0',
                '.total_amount_td': (Number(item.quantity) * Number(item.rate)).toFixed(2)};
            Object.entries(fields).forEach(([selector, value]) => {
                const optional = ['.cgstprt_td','.cgstamt_td','.sgstprt_td','.sgstamt_td','.igstprt_td','.igstamt_td','.distprt_td','.distamt_td'];
                if (this.isConfirmed() && optional.includes(selector) && (value == null || String(value).trim() === '')) value = 0;
                $row.find(selector).val(value ?? '');
            });
            $row.data('edit-original', item);
            $row.find('.qty_td').data('old-date', String(item.expecteddeliverydate || '').slice(0, 10));
            $row.find('.qty_td').data('edit-last-qty', item.quantity);
            $body.append($row);
            this.initSelect($product, 'Select Product');
        });
        if (!items.length) $body.append($template.clone(false));
        const role = parseInt($('#hidden_loginroleid').val() || $('body').data('loginroleid') || this.config.loginroleid || '0', 10);
        $body.find('.rate_td').prop('readonly', role > 2);
        $body.find('.distprt_td,.distamt_td').prop('disabled', false).prop('readonly', role > 3);
        $body.find('.expecteddelivery').removeAttr('min').removeAttr('max').prop('readonly', false).removeAttr('tabindex');
        Invoicegrandtotalgstamont('totalamount', 'gsttotalamount', 'ttldiscountamount', 'grandtotalamount', 'payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'provisionorderitemsTable');
    },
    fillDelivered(rows) {
        const self = this;
        $('#deliveryaddresstbl tbody').empty();
        rows.forEach(row => {
            $('#addBlankAddressRow').trigger('click');
            const $row = $('#deliveryaddresstbl tbody tr:last');
            const stateId = String(self.value(row, ['stateid', 'dest_stateid']));
            const districtId = String(self.value(row, ['districtid', 'dest_districtid']));
            if (self.isAddress() || self.isConfirmed()) $row.append($('<input>', {type:'hidden',name:'delivery_location_id[]',value:row.id || ''}));
            $row.find('.delivery_product_select').val(String(self.value(row, ['productid', 'addressforproduct'])));
            $row.find('.delivery_qty_input').val(self.value(row, ['delivery_qty', 'deliveryqty', 'productqty', 'quantity']));
            $row.find('.delivery_distance_input').val(self.value(row, ['totaldistance', 'distance'], 0));
            $row.find('.del_state').html(getStateOptions(stateId)).val(stateId);
            $row.find('.del_district').html(getDistrictOptions(stateId, districtId)).val(districtId);
            $row.find('.delivery_stateid').val(stateId);
            $row.find('.delivery_district_id').val(districtId);
            $row.find('.delivery_state').val(row.dest_state || $row.find('.del_state option:selected').text());
            $row.find('.delivery_district').val(row.dest_district || $row.find('.del_district option:selected').text());
            $row.find('[name="delivery_city[]"]').val(row.dest_city || '');
            $row.find('[name="delivery_pincode[]"]').val(row.dest_pincode || '');
            $row.find('[name="delivery_fulladdress[]"]').val(row.deliveryaddress || '');
            $row.find('[name="delivery_latlong[]"]').val(row.dest_latitude != null && row.dest_longitude != null ? row.dest_latitude + ',' + row.dest_longitude : '');
        });
    },
    async fillBox(rows, recalculate = false) {
        await window.fillEditBoxRows();
        const self = this;
        const unused = rows.slice();
        $('#deliveryboxtbl tbody tr.box-delivery-row').each(function () {
            const $row = $(this);
            const i = unused.findIndex(row => String(self.value(row, ['productid', 'addressforproduct'])) === String($row.find('.box_productid').val()));
            if (i < 0) return;
            const saved = unused.splice(i, 1)[0];
            if (self.isAddress() || self.isConfirmed()) $row.append($('<input>', {type:'hidden',name:'box_location_id[]',value:saved.id || ''}));
            $row.find('.state_box').val(String(self.value(saved, ['stateid', 'dest_stateid']))).trigger('change');
            $row.find('.district_box').val(String(self.value(saved, ['districtid', 'dest_districtid']))).trigger('change');
            $row.find('.city_box').val(String(self.value(saved, ['cityid', 'dest_cityid']))).trigger('change');
            $row.find('.pickuppoint').val(saved.pickuppoint || '').trigger('change');
            const newQty = Number($row.find('.box_product_qty').val()) || 0;
            const oldQty = Number(self.value(saved, ['delivery_qty', 'deliveryqty', 'productqty', 'quantity'], newQty));
            const changedQty = recalculate && Math.abs(newQty - oldQty) > 0.001;
            if (!changedQty) {
                $row.find('.plants_per_box').val(saved.boxperpack || '');
                $row.find('.no_of_box').val(saved.noofbox || '');
                $row.find('.freighcharges').val(self.value(saved, ['chargesperbox', 'boxchargesperpack', 'deliverycharges'], 0));
                $row.find('.freightamount').val(self.value(saved, ['freightamt', 'boxtotalamt'], 0));
            } else {
                const perBox = Number($row.find('.plants_per_box').val()) || Number(saved.boxperpack) || 1;
                const boxes = Math.ceil(newQty / perBox);
                $row.find('.no_of_box').val(boxes);
                $row.find('.freightamount').val((boxes * (Number($row.find('.freighcharges').val()) || 0)).toFixed(2));
            }
        });
    },
    boxSourceFingerprint() {
        return JSON.stringify($('#provisionorderitemsTable tbody tr').map(function () {
            const $row = $(this);
            return [$row.find('.productname').val(), $row.find('.qty_td').val(), $row.find('.expecteddelivery').val()].join('|');
        }).get());
    },
    async syncBoxRows() {
        if (!this.isEdit()) { if ($("input[name='pickupfrom']:checked").val() === 'boxpacking') await window.fillEditBoxRows(); return; }
        if ($("input[name='pickupfrom']:checked").val() !== 'boxpacking') return;
        const fingerprint = this.boxSourceFingerprint();
        if (fingerprint === this.boxFingerprint) return;
        const rows = [];
        $('#deliveryboxtbl tbody tr.box-delivery-row').each(function () {
            const $row = $(this);
            rows.push({productid: $row.find('.box_productid').val(), delivery_qty: $row.find('.box_product_qty').val(),
                stateid: $row.find('.state_box').val(), districtid: $row.find('.district_box').val(), cityid: $row.find('.city_box').val(),
                pickuppoint: $row.find('.pickuppoint').val(), boxperpack: $row.find('.plants_per_box').val(), noofbox: $row.find('.no_of_box').val(),
                chargesperbox: $row.find('.freighcharges').val(), freightamt: $row.find('.freightamount').val()});
        });
        await this.fillBox(rows, true);
        this.boxFingerprint = fingerprint;
    },
    restoreFreight() {
        const self = this;
        const delivered = this.snapshot.delivery;
        $('#freightGroupTable tbody tr.group-row').each(function () {
            const $row = $(this);
            const key = $row.find('[name="f_address_key[]"]').val() || '';
            const address = key.split('@@@')[0];
            const saved = delivered.find(row => [row.dest_city || '', row.dest_district || '', row.deliveryaddress || ''].join('|') === address);
            if (!saved) return;
            const amount = Number(self.value(saved, ['deliverycharges'], self.value(saved, ['freightamt', 'boxtotalamt'], 0)));
            const payable = Number(self.value(saved, ['freightamt', 'boxtotalamt'], amount));
            $row.find('.f_amt_input').val(amount.toFixed(2));
            $row.find('.f_disc_input').val(amount > 0 ? ((amount - payable) / amount * 100).toFixed(2) : '0');
            $row.find('.f_payable_input').val(payable.toFixed(2));
            $row.find('.f_vehicle_info').val(saved.vehicleno || '');
            $row.find('.f_totaldistance_input,.f_distance_info').val(saved.totaldistance || 0);
            $row.find('.f_freedistance_input').val(saved.freedistance || 0);
            $row.find('.f_payabledistance_input').val(saved.distance || 0);
            $row.find('.f_narration_input').val(saved.remark || '');
            $row.find('.auto-vehicle-badge').text(saved.vehicleno || 'Saved freight');
        });
        const freight = this.snapshot.freight[0];
        const $groups = $('#freightGroupTable tbody tr.group-row');
        if (!delivered.length && freight && $groups.length === 1) {
            $groups.find('.f_amt_input').val(freight.freight_amount);
            $groups.find('.f_disc_input').val(freight.freight_discountpercent);
            $groups.find('.f_payable_input').val(freight.payable_freightamount);
        }
        if (window.calculateGrandTotalFreight) window.calculateGrandTotalFreight();
    },
    inputsFingerprint() {
        const names = /^(pickupfrom|clientid|customer_type|stateid|districtid|cityname|delivery_address|productname|quantity|expecteddelivery|delivery_|del_state|del_district|box_|state_box|district_box|city_box|plants_per_box|no_of_box|pickuppoint)/;
        return JSON.stringify($('#salesOrderForm').serializeArray().filter(field => names.test(field.name)));
    },
    keepFreight() {
        return this.isEdit() && this.ready && this.inputsFingerprint() === this.freightFingerprint && $('#freightGroupTable tbody').children().length > 0;
    },
    async mergeAllocatedStock(rows, $activeRow) {
        if (!this.isEdit()) return rows;
        // Server already excludes matching PSO stock and deducts saved allocations.
        const used = new Set((this.snapshot.items || []).map(item => String(item.edit_stock_id)));
        const selectable = Array.isArray(rows) ? rows.filter(row => !this.isConfirmed() || !used.has(String(row.stksno))).map(row => ({...row})) : [];
        return selectable.map(row => {
            const ownSaved = this.snapshot.items.filter(item => String(item.edit_stock_id) === String(row.stksno) && String(item.product_id) === String(row.productid)).reduce((total, item) => total + Number(item.quantity), 0);
            let otherStaged = 0;
            $('#provisionorderitemsTable tbody tr').each(function () {
                if (this === $activeRow[0]) return;
                const $row = $(this);
                if (String($row.find('.stockdbid').val()) === String(row.stksno) && String($row.find('.productname').val()) === String(row.productid)) otherStaged += Number($row.find('.qty_td').val()) || 0;
            });
            const extraStaged = Math.max(0, otherStaged - ownSaved);
            row.availableqty = Math.max(0, Number(row.availableqty || 0) - extraStaged);
            row.unconfirmed_allocated = Math.max(0, Number(row.unconfirmed_allocated || 0) + extraStaged);
            return row;
        });
    },
    validateAddressRows() {
        const mode = $("input[name='pickupfrom']:checked").val();
        if (mode === 'pickup') return true;
        const box = mode === 'boxpacking';
        const $rows = $(box ? '#deliveryboxtbl tbody tr' : '#deliveryaddresstbl tbody tr');
        if (!$rows.length) { alertmessage('warning', box ? 'Please fill the box-packing address details.' : 'Please add a delivery address, or choose Skip & Update Later.'); return false; }
        let invalid = false;
        $rows.each(function () {
            const $r = $(this);
            const fields = box ? ['box_productid','box_state_id','box_district_id','box_city_name','pickuppoint'] : ['delivery_productid','delivery_stateid','delivery_district_id','delivery_city','delivery_pincode','delivery_fulladdress'];
            let bad = fields.some(name => !String($r.find('[name="' + name + '[]"]').val() || '').trim());
            const qty = Number($r.find('[name="' + (box ? 'box_product_qty' : 'delivery_qty') + '[]"]').val());
            bad = bad || !Number.isFinite(qty) || qty <= 0;
            $r.toggleClass('table-danger', bad); invalid = invalid || bad;
        });
        if (invalid) alertmessage('warning', box ? 'Please complete Product, Quantity, State, District, City and Pickup Point for every box-packing row.' : 'Please complete Product, Quantity, State, District, City, Pincode and Full Address for every delivery row.');
        return !invalid;
    },
    serialize($form) {
        if(!this.isEdit()){
            const mapping=[];$('#provisionorderitemsTable tbody tr').each(function(){const $row=$(this);$row.find('input[name="enquiry_selected_ids[]"]').each(function(){mapping.push({id:Number(this.value),product:String($row.find('.productname').val()||''),quantity:String($row.find('.qty_td').val()||'')});});});
            let $map=$form.find('input[name="enquiry_item_map"]');if(!$map.length)$map=$('<input>',{type:'hidden',name:'enquiry_item_map'}).appendTo($form);$map.val(JSON.stringify(mapping));
        }

        // Disabled computed freight/discount controls must still occupy their array positions.
        const $tables = $('#deliveryaddresstbl, #deliveryboxtbl');
        const $controls = $form.find(':input[name]').add($tables.find(':input[name]'));
        const $disabled = $controls.filter(':disabled');
        $disabled.prop('disabled', false);
        if (this.isConfirmed() && window.normaliseConfirmedOptionalAmounts) window.normaliseConfirmedOptionalAmounts($form);
        const serialized = $controls.serialize();
        $disabled.prop('disabled', true);
        let emergency = false;
        $('#provisionorderitemsTable tbody tr').each(function () {
            if ($(this).data('edit-emergency-sowing') === true) emergency = true;
        });
        return serialized + '&orderkind=' + encodeURIComponent(this.config.kind || 'provisional') + '&csrf=' + encodeURIComponent(this.config.csrf || '') + '&snapshot_token=' + encodeURIComponent(this.snapshotToken || '') + '&mode=' + encodeURIComponent(this.config.mode) + '&emergency_sowing=' + (emergency ? '1' : '0') + '&k=' + encodeURIComponent(this.config.key || '');
    }
};

// Keep delivery/packing quantities aligned with edited product totals.
$(function(){
 let busy=false;
 function syncOrderDelivery(){
  if(busy||!window.OrderModule?.isEdit()||window.OrderModule.hydrating||window.OrderModule.isAddress())return;
  busy=true;
  try{
   const totals=new Map();$('#provisionorderitemsTable tbody tr').each(function(){const id=String($(this).find('.productname').val()||'');if(id)totals.set(id,(totals.get(id)||0)+(Number($(this).find('.qty_td').val())||0));});
   for(const [table,product,quantity] of [['#deliveryaddresstbl','.delivery_product_select','.delivery_qty_input'],['#deliveryboxtbl','.box_productid','.box_product_qty']]){
    const groups=new Map();$(table+' tbody tr').each(function(){const id=String($(this).find(product).val()||'');if(!id)return;if(!totals.has(id)){$(this).remove();return;}if(!groups.has(id))groups.set(id,[]);groups.get(id).push($(this));});
    for(const [id,rows] of groups){const total=totals.get(id);let remaining=total;rows.forEach(($row,i)=>{const $qty=$row.find(quantity);const value=i===rows.length-1?remaining:Math.min(remaining,Number($qty.val())||0);remaining-=value;if(Number($qty.val())!==value)$qty.val(value).trigger('input').trigger('change');});}
   }
  }finally{busy=false;}
 }
 $('.next-step').on('click',syncOrderDelivery);
 const tbody=document.querySelector('#provisionorderitemsTable tbody');if(tbody)new MutationObserver(syncOrderDelivery).observe(tbody,{childList:true});
 $('#provisionorderitemsTable').on('change','.qty_td,.productname',syncOrderDelivery);
});
