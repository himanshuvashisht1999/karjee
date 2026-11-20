
  let boxPackingMaster = []; 
  let stateDistrictArray = [];

  document.addEventListener("input", function (event) { 
    if (!event.target.classList.contains("restrict-double-quote")) {
        return;
    }

    if (event.target.value.includes('"')) {
        event.target.value = event.target.value.replace(/"/g, "");
    }
});
  function handleFreightSelection(data) {
    // console.log("Freight Data Received:", data);
    console.log(data);
    // Target fields
    const $freightAmount = $("#freight_amount");
    const $vehicleNo = $("#vehicleno_hidden");
    const $distanceKm = $("#distance_hidden");

    // 1. Populate the rate and trigger calculation logic
    $freightAmount.val(data.charge || 0).trigger('input');

    // 2. Populate visible/hidden details fields
    if ($("#vehiclename_hidden").length) $("#vehiclename_hidden").val(data.vehiclename || '').trigger('change');
    if ($vehicleNo.length) $vehicleNo.val(data.vehicleno || '').trigger('change');
    if ($distanceKm.length) $distanceKm.val(data.calc_km || '').trigger('change');

    // 3. Update Meta Info Display
    if (data.vehiclename) {
        $("#freight-meta-info").html(`
            <div class="d-flex flex-column gap-1">
                <span><i class="fa fa-shuttle-van me-1"></i> ${data.vehiclename}</span>
                <span><i class="fa fa-route me-1"></i> ${data.calc_km} KM</span>
            </div>
        `);
    }
  }
  
function validateClientNameStep1() {
    const $field = $('#clientname');
    const name = String($field.val() || '').trim();

    if (!/[a-zA-Z]/.test(name)) {
        alertmessage(
            'warning',
            'Client Name must contain at least one alphabetic character.'
        );
        $field.focus();
        return false;
    }

    return true;
}

  $(document).ready(function () {
    const target = document.getElementById('grandtotalamount');
    if (target) {
      const observer = new MutationObserver(function (mutations) {
        if (typeof calculateGrandTotalFreight === "function") {
          calculateGrandTotalFreight();
        } else {
          $("#grandtotalamount_final").text($(target).text());
        }
      });
      observer.observe(target, { childList: true, characterData: true, subtree: true });
    }

    // Wizard Navigation Logic
    $(".next-step").on("click", async function() { 

        if (!window.OrderModule.isEdit()) $(".freightratemodal").prop("disabled", true);
        const nextStep = $(this).data("next");
        const currentStep = nextStep - 1;

        // Validation logic for each step
        if (currentStep === 1) {
            
            const clientName = $("#clientname").val();
            const mobileNo = $("#mobileno").val();
            statename= $("#statename").val();
            districtname= $("#districtname").val();
            tahseel= $("#tahseel").val();
            cityname= $("#cityname").val();
            if (!clientName) {
                iziToast.warning({ title: 'Required Fields', message: 'Please fill client name ', position: 'topRight' });
                
                return;
            }
            if (!validateClientNameStep1()) {
                return false;
            }
            if (!mobileNo ) {
                iziToast.warning({ title: 'Required Fields', message: 'Please fill  mobile number to proceed.', position: 'topRight' });
                if (typeof window.validateMobileNumber === 'function') window.validateMobileNumber();
                return;
            }

            if (typeof window.validateMobileNumber === 'function' && !window.validateMobileNumber()) {
                iziToast.warning({ title: 'Invalid Mobile Number', message: 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.', position: 'topRight' });
                $("#mobileno").focus();
                return;
            }
            if (!statename ) {
                iziToast.warning({ title: 'Required Fields', message: 'Please fill state name', position: 'topRight' });
               
                return;
            }
            if (!districtname ) {
                iziToast.warning({ title: 'Required Fields', message: 'Please fill district name.', position: 'topRight' }); 
                return;
            }
            // if (!tahseel ) {
            //     iziToast.warning({ title: 'Required Fields', message: 'Please fill tehsil name.', position: 'topRight' }); 
            //     return;
            // }
            if (!cityname ) {
                iziToast.warning({ title: 'Required Fields', message: 'Please fill city name.', position: 'topRight' });
                if (typeof window.validateMobileNumber === 'function') window.validateMobileNumber();
                return;
            }
            if (!String($("#delivery_address").val() || "").trim()) {
                alertmessage("warning", "Please enter the client address before proceeding to Order Details.");
                $("#delivery_address").trigger("focus");
                return;
            }
              loginRoleId = parseInt($("#hidden_loginroleid").val() || 0, 10);
            $(".distprt_td").prop("disabled", loginRoleId > 3);
            $(".distamt_td").prop("disabled", loginRoleId > 3);


        } else if (currentStep === 2) {
            let isValid = true;
            let $rows = $("#provisionorderitemsTable tbody tr");

            if ($rows.length === 0) {
                iziToast.warning({ title: 'Missing Items', message: 'Please add at least one product to the order.', position: 'topRight' });
                return;
            }

            $rows.each(function() {
                let product = $(this).find(".productname").val();
                let qty = $(this).find(".qty_td").val();
                let rate = $(this).find(".rate_td").val();
                let deliveryDate = $(this).find(".expecteddelivery").val();

                if (!product || !qty || parseFloat(qty) <= 0 || !rate || !deliveryDate) {
                    isValid = false;
                    $(this).css("background-color", "#fff5f5");
                } else {
                    $(this).css("background-color", "");
                }
            });

            if (!isValid) {
                iziToast.warning({ 
                    title: 'Required Missing', 
                    message: 'Please ensure all items have a Product, Rate, Quantity, and Expected Delivery Date filled.', 
                    position: 'topRight' 
                });
                return;
            }
            if (window.OrderModule.isEdit()) {
                // Existing destinations belong to the saved order.
                try { await window.OrderModule.syncBoxRows(); }
                catch (error) { Swal.fire('Box Packing', error.message || 'Unable to update box rows.', 'error'); return; }
            } else {
                // Match the original Add wizard when leaving product details.
                $('input[name="pickupfrom"]').prop('checked', false);
                $('#deliveryboxdiv').addClass('d-none');
                $('#deliveryaddressdiv').fadeOut();
                $('#deliveryboxtbl tbody').empty();
                $('#deliveryaddresstbl tbody').empty();
            }
        } else if (currentStep === 3) {
          var pickupfrom = $('input[name="pickupfrom"]:checked').val();
          if (!pickupfrom){
            alert("You have Not select any option  Delivery Address")
            return false;
          }
          
            // Only validate address if 'delivered' is selected and user added rows

          if ($("#deliverbox_opt").is(":checked")) {

             $(".freightratemodal").prop("disabled",true);
                const $rows = $("#deliveryboxtbl tbody tr"); 
              if ($rows.length <= 0) {
                  Swal.fire(
                      "Validation",
                      "Please add at least one Box Packing detail.",
                      "warning"
                  );
                  return;
              }

              let isValid = true;

              $rows.each(function () {

                  const $row = $(this);

                  const pickupPoint = $row.find(".pickuppoint").val();
                  const stateId     = $row.find(".state_box").val();
                  const districtId  = $row.find(".district_box").val();
                  const locationId  = $row.find(".city_box").val();
                  const plantsPerBox = parseInt($row.find(".plants_per_box").val()) || 0;
                  const noOfBox      = parseInt($row.find(".no_of_box").val()) || 0;

                  if (!pickupPoint) {
                      Swal.fire("Validation", "Please select Pickup Point.", "warning");
                      $row.find(".pickuppoint").focus();
                      isValid = false;
                      return false;
                  }

                  if (!stateId) {
                      Swal.fire("Validation", "Please select State.", "warning");
                      $row.find(".state_box").focus();
                      isValid = false;
                      return false;
                  }

                  if (!districtId) {
                      Swal.fire("Validation", "Please select District.", "warning");
                      $row.find(".district_box").focus();
                      isValid = false;
                      return false;
                  }

                  if (!locationId) {
                      Swal.fire("Validation", "Please select Location.", "warning");
                      $row.find(".city_box").focus();
                      isValid = false;
                      return false;
                  }

                  if (plantsPerBox <= 0) {
                      Swal.fire("Validation", "Plants per Box is invalid.", "warning");
                      isValid = false;
                      return false;
                  }

                  if (noOfBox <= 0) {
                      Swal.fire("Validation", "No. of Boxes is invalid.", "warning");
                      isValid = false;
                      return false;
                  }
              });

              if (!isValid) {
                  return;
              }

          }  

          else if ($("#deliver_opt").is(":checked")) {

                const $rows = $("#deliveryaddresstbl tbody tr");
                if ($rows.length > 0) {
                    let incompleteRows = false;
                    $rows.each(function() {
                        const prod = $(this).find(".delivery_product_select").val();
                        const qty = $(this).find(".delivery_qty_input").val();
                        const addr = $(this).find("input[name='delivery_fulladdress[]']").val();
                        if (!prod || !qty || parseFloat(qty) <= 0 || !addr) {
                            incompleteRows = true;
                            $(this).css("background-color", "#fff5f5");
                        } else {
                            $(this).css("background-color", "");
                        }
                    });

                    if (incompleteRows) {
                        iziToast.error({ title: 'Incomplete Data', message: 'Please fill all mandatory fields (Product, Qty, Full Address) in the delivery table, or click "Skip & Update Later".', position: 'topRight' });
                        return;
                    }

                    // MATCHING QUANTITY VALIDATION
                    // 1. Get ordered quantities per product
                    const orderedMap = {};
                    $("#provisionorderitemsTable tbody tr").each(function() {
                        const pid = $(this).find(".productname").val();
                        const qty = parseFloat($(this).find(".qtycal").val()) || 0;
                        if (pid) {
                            orderedMap[pid] = (orderedMap[pid] || 0) + qty;
                        }
                    });

                    // 2. Get delivery quantities per product
                    const deliveryMap = {};
                    $rows.each(function() {
                        const pid = $(this).find(".delivery_product_select").val();
                        const qty = parseFloat($(this).find(".delivery_qty_input").val()) || 0;
                        if (pid && pid !== 'all') {
                            deliveryMap[pid] = (deliveryMap[pid] || 0) + qty;
                        }
                    });

                    // 3. Compare
                    let mismatched = [];
                    for (let pid in orderedMap) {
                        const oQty = orderedMap[pid];
                        const dQty = deliveryMap[pid] || 0;
                        if (Math.abs(oQty - dQty) > 0.001) {
                            const pName = $("#provisionorderitemsTable tbody tr").find(`.productname option[value="${pid}"]`).first().text();
                            mismatched.push(`<strong>${pName}</strong>: Ordered <b>${oQty}</b>, Assigned <b>${dQty}</b>`);
                        }
                    }

                    if (mismatched.length > 0) {
                        Swal.fire({
                            title: 'Quantity Mismatch!',
                            html: '<div class="text-start">Total delivery quantity must match ordered quantity for all products:<br><br><ul class="mb-0">' + 
                                  mismatched.map(m => `<li>${m}</li>`).join('') + '</ul></div>',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                        return;
                    }
                }
            }

        }

        if (nextStep === 4 && !window.OrderModule.validateAddressRows()) return;
        goToStep(nextStep);

        // Special logic for entering Step 4

        if (nextStep === 4) {
             
            generateFreightGroups();
        }
    });

    // Skip & Update Later Button Click Handler
    $(document).on("click", "#btnSkipDeliveryStep", function() {
      $("#pickup_opt").prop('checked', true).trigger('change');
      goToStep(4);
      generateFreightGroups(true);
    });

    $(".prev-step").on("click", function() {
        const prevStep = $(this).data("prev");
        goToStep(prevStep);
    });

    window.saleOrderRules = {goToStep, generateFreightGroups};
    window.goToStep = goToStep;
    function goToStep(stepNumber) {
        if (window.OrderModule && typeof window.OrderModule.isAddress === 'function' && window.OrderModule.isAddress() && stepNumber < 3) stepNumber = 3; 
        $(".wizard-content-step").hide();
        $("#step" + stepNumber).fadeIn(400);
        
        // Update Step Indicator
        $(".step-item").removeClass("active completed");

        for (let i = 1; i <= 4; i++) {
            const $item = $(".step-item[data-step='" + i + "']");
            const displayNumber = window.OrderModule && window.OrderModule.isAddress() ? i - 2 : i;
            if (i < stepNumber) {
                $item.addClass("completed");
                $item.html('<i class="fa fa-check"></i> <span class="step-label">' + getStepLabel(i) + '</span>');
            } else if (i == stepNumber) {
                $item.addClass("active");
                $item.html(displayNumber + ' <span class="step-label">' + getStepLabel(i) + '</span>');
            } else {
                $item.html(displayNumber + ' <span class="step-label">' + getStepLabel(i) + '</span>');
            }
        }

        $('html, body').animate({ scrollTop: $(".wizard-steps").offset().top - 20 }, 500);
    }

    function getStepLabel(step) {
        const labels = { 1: 'Client Info', 2: 'Order Details', 3: 'Delivery', 4: 'Freight' };
        return labels[step] || '';
    }
 

    $("#addBlankAddressRow").on("click", function() {
      const rowId = 'row_' + Date.now();
      const productOptions = getStep2ProductsAsOptions();

      const stateid = $("#stateid").val() || '';
      const districtid = $("#districtid").val() || '';

      const html = `
          <tr id="${rowId}">
              <td>
                  <select name="delivery_productid[]" class="form-select delivery_product_select" required>
                      ${productOptions}
                  </select>
              </td>

              <td>
                  <input type="text"
                         name="delivery_qty[]"
                         class="form-control delivery_qty_input"
                         placeholder="0"
                         onKeyPress="return isFloatNumber(event,this);"
                         required />
                  <div class="pending-qty-badge mt-1"
                       style="font-size: 0.75rem; display: none;"></div>
              </td>

              <td>
                  <input type="text"
                         name="delivery_distance[]"
                         class="form-control delivery_distance_input"
                         placeholder="0.0"
                         onKeyPress="return isFloatNumber(event,this);" />
              </td>

              <td class="d-none">
                  <input type="hidden"
                         name="delivery_latlong[]"
                         value="" />
              </td>

              <td>
                  <select
                      class="form-control del_state"
                      name="del_state[]"
                      required
                  >
                      ${getStateOptions(stateid)}
                  </select>

                  <input type="hidden"
                         class="delivery_state"
                         name="delivery_state[]"
                         value="">

                  <input type="hidden"
                         class="delivery_stateid"
                         name="delivery_stateid[]"
                         value="${stateid}">
              </td>

              <td>
                  <select
                      class="form-control del_district"
                      name="del_district[]"
                      required
                  >
                      ${getDistrictOptions(stateid, districtid)}
                  </select>

                  <input type="hidden"
                         class="delivery_district"
                         name="delivery_district[]"
                         value="">

                  <input type="hidden"
                         class="delivery_district_id"
                         name="delivery_district_id[]"
                         value="${districtid}">
              </td>

              <td>
                  <input type="text"
                         name="delivery_city[]"
                         class="form-control"
                         placeholder="City/Village" />
              </td>

              <td>
                  <input type="text"
                         name="delivery_pincode[]"
                         class="form-control"
                         placeholder="Pincode"
                         onKeyPress="return isNumber(event);" />
              </td>

              <td>
                  <input type="text"
                         name="delivery_fulladdress[]"
                         class="form-control"
                         placeholder="Full Address"
                         required />
              </td>

              <td class="text-center">
                  <div class="d-flex gap-1 justify-content-center">
                      <button type="button"
                              class="btn btn-outline-primary btn-sm"
                              onclick="copyDeliveryRow(this)"
                              title="Copy Row">
                          <i class="fa fa-copy"></i>
                      </button>

                      <button type="button"
                              class="btn btn-outline-danger btn-sm"
                              onclick="$(this).closest('tr').remove();">
                          <i class="fa fa-trash"></i>
                      </button>
                  </div>
              </td>
          </tr>
      `;

      $("#deliveryaddresstbl tbody").append(html);
  });

    // Show Old Delivery History
    $("#showOldDeliveryHistory").on("click", function() {
        const clientId = $("#clientid").val();
        if (!clientId) {
            Swal.fire('No Client Selected', 'Please select a client first.', 'warning');
            return;
        }
        
        $("#historyAddressTbl tbody").html('<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><br>Loading history...</td></tr>');
        $("#clientDeliveryHistoryModal").modal("show"); 
        GetUniqeClientDeliveryAddressData(clientId, function(res) {
            let html = '';
            if (res && res.length > 0) {
                res.forEach((row, i) => {
                    html += `
                        <tr>
                            <td class="text-center chk-cell">
                                <input type="checkbox" class="form-check-input history-checkbox" 
                                    data-row='${JSON.stringify(row).replace(/'/g, "&apos;")}'>
                            </td>
                            <td 
                              data-stateid="${row.stateid || ''}"
                              data-districtid="${row.districtid || ''}"
                              >${row.dest_state || '-'}<br><small class="text-muted">${row.dest_district || '-'}</small></td>
                            <td>${row.dest_city || '-'}<br><small class="text-muted">${row.dest_pincode || '-'}</small></td>
                            <td style="max-width:300px; white-space: normal;">${row.deliveryaddress || '-'}</td>
                            <td>${row.distance || 0} KM</td>
                        </tr>
                    `;
                });
            } else {
                html = '<tr><td colspan="5" class="text-center py-4 text-muted">No previous delivery addresses found for this client.</td></tr>';
            }
            $("#historyAddressTbl tbody").html(html);
        });
    });

    // Select All History
    $("#selectAllHistory").on("change", function() {
        $(".history-checkbox").prop('checked', $(this).is(':checked'));
    });

    // Apply Selected History
  /*  $("#btnApplySelectedHistory").on("click", function() {
        const selected = [];
        $(".history-checkbox:checked").each(function() {
            selected.push($(this).data('row'));
        });

        if (selected.length === 0) {
            Swal.fire('No Selection', 'Please select at least one address.', 'info');
            return;
        }

        const productOptionsHtml = getStep2ProductsAsOptions();

        selected.forEach(rowData => {

            const stateid = rowData.stateid || '';
            const districtid = rowData.districtid || '';

            const statename = rowData.dest_state || '';
            const districtname = rowData.dest_district || '';
            const $newRow = $(`
                <tr class="align-middle">
                    <td>
                        <select name="delivery_productid[]" class="form-select select2 delivery_product_select">${productOptionsHtml}</select>
                    </td>
                    <td>
                        <input type="text" name="delivery_qty[]" class="form-control delivery_qty_input" placeholder="0" onKeyPress="return isFloatNumber(event,this);" required />
                    </td>
                    <td>
                        <input type="text" name="delivery_distance[]" class="form-control delivery_distance_input" value="${rowData.distance || 0}" onKeyPress="return isFloatNumber(event,this);" />
                    </td>
                    <td class="d-none">
                        <input type="hidden" name="delivery_latlong[]" value="${rowData.dest_latitude || ''},${rowData.dest_longitude || ''}" />
                    </td>
                 
                  <!-- STATE -->
                  <td>
                      <select class="form-control del_state" name="del_state[]" required
                      >
                          ${getStateOptions(stateid)}
                      </select>
                      <input type="hidden" class="delivery_state"  name="delivery_state[]" value="${statename}">

                      <input type="hidden"
                             class="delivery_stateid"
                             name="delivery_stateid[]"
                             value="${stateid}">

                  </td>
                  <!-- DISTRICT -->
                  <td>
                      <select  class="form-control del_district"  name="del_district[]"
                          required
                      >
                          ${getDistrictOptions(stateid, districtid)}
                      </select>

                      <input type="hidden" class="delivery_district"  name="delivery_district[]"  value="${districtname}">

                      <input type="hidden"   class="delivery_district_id"   name="delivery_district_id[]"   value="${districtid}"> 
                  </td>

                    <td><input type="text" name="delivery_city[]" class="form-control" value="${rowData.dest_city || ''}"></td>
                    <td><input type="text" name="delivery_pincode[]" class="form-control" value="${rowData.dest_pincode || ''}"></td>
                    <td><input type="text" name="delivery_fulladdress[]" class="form-control" value="${rowData.deliveryaddress || ''}" required></td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyDeliveryRow(this)" title="Copy Row">
                                <i class="fa fa-copy"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rmdeliveryaddbtn">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `);

            $("#deliveryaddresstbl tbody").append($newRow);
            $newRow.find(".delivery_product_select").select2({ width: '100%' });
        });

        $("#clientDeliveryHistoryModal").modal("hide");
        iziToast.success({ title: 'Success', message: selected.length + ' addresses added.' });
    });*/
    $("#btnApplySelectedHistory").on("click", function() {

        const selected = [];

        $(".history-checkbox:checked").each(function() {
            selected.push($(this).data('row'));
        });

        if (selected.length === 0) {
            Swal.fire(
                'No Selection',
                'Please select at least one address.',
                'info'
            );
            return;
        }

        const productOptionsHtml = getStep2ProductsAsOptions();

        selected.forEach(rowData => {

            const stateid = rowData.stateid || '';
            const districtid = rowData.districtid || '';

            const statename = rowData.dest_state || '';
            const districtname = rowData.dest_district || '';

            const $newRow = $(`
                <tr class="align-middle">

                    <td>
                        <select name="delivery_productid[]"
                                class="form-select select2 delivery_product_select">
                            ${productOptionsHtml}
                        </select>
                    </td>

                    <td>
                        <input type="text"
                               name="delivery_qty[]"
                               class="form-control delivery_qty_input"
                               placeholder="0"
                               onKeyPress="return isFloatNumber(event,this);"
                               required />
                    </td>

                    <td>
                        <input type="text"
                               name="delivery_distance[]"
                               class="form-control delivery_distance_input"
                               value="${rowData.distance || 0}"
                               onKeyPress="return isFloatNumber(event,this);" />
                    </td>

                    <td class="d-none">
                        <input type="hidden"
                               name="delivery_latlong[]"
                               value="${rowData.dest_latitude || ''},${rowData.dest_longitude || ''}" />
                    </td>


                    <!-- STATE -->
                    <td>

                        <select
                            class="form-control del_state"
                            name="del_state[]"
                            required
                        >
                            ${getStateOptions(stateid)}
                        </select>

                        <input type="hidden"
                               class="delivery_state"
                               name="delivery_state[]"
                               value="${statename}">

                        <input type="hidden"
                               class="delivery_stateid"
                               name="delivery_stateid[]"
                               value="${stateid}">

                    </td>


                    <!-- DISTRICT -->
                    <td>

                        <select
                            class="form-control del_district"
                            name="del_district[]"
                            required
                        >
                            ${getDistrictOptions(stateid, districtid)}
                        </select>

                        <input type="hidden"
                               class="delivery_district"
                               name="delivery_district[]"
                               value="${districtname}">

                        <input type="hidden"
                               class="delivery_district_id"
                               name="delivery_district_id[]"
                               value="${districtid}">

                    </td>


                    <td>
                        <input type="text"
                               name="delivery_city[]"
                               class="form-control"
                               value="${rowData.dest_city || ''}">
                    </td>

                    <td>
                        <input type="text"
                               name="delivery_pincode[]"
                               class="form-control"
                               value="${rowData.dest_pincode || ''}">
                    </td>

                    <td>
                        <input type="text"
                               name="delivery_fulladdress[]"
                               class="form-control"
                               value="${rowData.deliveryaddress || ''}"
                               required>
                    </td>

                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">

                            <button type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    onclick="copyDeliveryRow(this)"
                                    title="Copy Row">
                                <i class="fa fa-copy"></i>
                            </button>

                            <button type="button"
                                    class="btn btn-sm btn-outline-danger rmdeliveryaddbtn">
                                <i class="fa fa-trash"></i>
                            </button>

                        </div>
                    </td>

                </tr>
            `);

            $("#deliveryaddresstbl tbody").append($newRow);

            $newRow
                .find(".delivery_product_select")
                .select2({
                    width: '100%'
                });
        });

        $("#clientDeliveryHistoryModal").modal("hide");

        iziToast.success({
            title: 'Success',
            message: selected.length + ' addresses added.'
        });

    });

    function getStep2ProductsAsOptions() {
        let options = '<option value="">Select Product</option>';
        options += '<option value="all" class="fw-bold text-primary">-- All Products (Entire Order) --</option>';
        $("#provisionorderitemsTable tbody tr").each(function() {
            const $select = $(this).find(".productname");
            const productId = $select.val();
            const productName = $select.find("option:selected").text();
            if (productId) {
                options += `<option value="${productId}">${productName}</option>`;
            }
        });
        return options;
    }

    function refreshProductDropdownsInDelivery() {
        const options = getStep2ProductsAsOptions();
        $(".delivery_product_select").each(function() {
            const currentVal = $(this).val();
            $(this).html(options).val(currentVal);
        });
    }

    // Real-time validation and Auto-fill for Delivery Quantities
    $(document).on("input change", ".delivery_qty_input, .delivery_product_select", function(e) {
        const $row = $(this).closest("tr");
        const productId = $row.find(".delivery_product_select").val();
        
        // Auto-fill Delivery Qty with PENDING qty when a specific product is selected
        if (e.type === 'change' && $(this).hasClass('delivery_product_select') && productId && productId !== 'all') {
            // 1. Total Ordered
            let totalOrderedQty = 0;
            $("#provisionorderitemsTable tbody tr").each(function() {
                if ($(this).find(".productname").val() === productId) {
                    totalOrderedQty += parseFloat($(this).find(".qtycal").val()) || 0;
                }
            });

            // 2. Already Allocated in OTHER rows
            let alreadyAllocated = 0;
            $("#deliveryaddresstbl tbody tr").not($row).each(function() {
                if ($(this).find(".delivery_product_select").val() === productId) {
                    alreadyAllocated += parseFloat($(this).find(".delivery_qty_input").val()) || 0;
                }
            });

            const remainingQty = Number((totalOrderedQty - alreadyAllocated).toFixed(3));
            
            // Only auto-fill if the input is currently empty or 0
            const currentQty = parseFloat($row.find(".delivery_qty_input").val()) || 0;
            if (currentQty === 0 && remainingQty > 0) {
                $row.find(".delivery_qty_input").val(remainingQty).trigger('input');
            }
        }

        if (productId === 'all') {
            handleAllProductsSelection($row);
            return;
        }

        if (!productId) return;
        validateProductQuantities(productId);
    });

    function handleAllProductsSelection($currentRow) {
        // 1. Get address details from current row

      const district = $currentRow.find("input[name='delivery_district[]']").val() || '';
      const districtid = $currentRow.find(".delivery_district_id").val() || '';

      const state = $currentRow.find("input[name='delivery_state[]']").val() || '';
      const stateid = $currentRow.find(".delivery_stateid").val() || ''; 
      const city = $currentRow.find("input[name='delivery_city[]']").val() || '';   
        
       
        const pincode = $currentRow.find("input[name='delivery_pincode[]']").val() || '';
        const address = $currentRow.find("input[name='delivery_fulladdress[]']").val() || ''; 
        const distance = $currentRow.find("input[name='delivery_distance[]']").val() || '0';
        const latlong = $currentRow.find("input[name='delivery_latlong[]']").val() || '';

        // 2. Get all products from Step 2
        const items = [];
        $("#provisionorderitemsTable tbody tr").each(function() {
            const id = $(this).find(".productname").val();
            const qty = parseFloat($(this).find(".qtycal").val()) || 0;
            if (id && qty > 0) {
                items.push({ id, qty });
            }
        });

        if (items.length === 0) {
            iziToast.warning({ title: 'Empty Order', message: 'No products found in Step 2.' });
            return;
        }

        // 3. Confirm with user
        Swal.fire({
            title: 'Ship All Products?',
            text: `This will allocate all ${items.length} items to this address.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            confirmButtonText: 'Yes, Apply All'
        }).then((result) => {
            if (result.isConfirmed) {
                const productOptions = getStep2ProductsAsOptions();

                // Add rows for each product
                items.forEach(item => {
                    const $newRow = $(`
                        <tr class="align-middle">
                            <td>
                                <select name="delivery_productid[]" class="form-select select2 delivery_product_select" required>
                                    ${productOptions}
                                </select>
                            </td>
                            <td>
                                <input type="text" name="delivery_qty[]" class="form-control delivery_qty_input is-valid" value="${item.qty}" placeholder="0" onKeyPress="return isFloatNumber(event,this);" required />
                                <div class="pending-qty-badge mt-1" style="font-size: 0.75rem; display: none;"></div>
                            </td>
                            <td>
                                <input type="text" name="delivery_distance[]" class="form-control delivery_distance_input" value="${distance}" onKeyPress="return isFloatNumber(event,this);" />
                            </td>
                            <td class="d-none">
                                <input type="hidden" name="delivery_latlong[]" value="${latlong}" />
                            </td>

                            <td>
                              <select
                                  class="form-control del_state"
                                  name="del_state[]"
                                    ${getStateOptions(stateid)}
                                  required
                              > 
                              </select>
                                 <input type="hidden" class="delivery_state" name="delivery_state[]" value="">
                                 <input type="hidden" class="delivery_stateid" name="delivery_stateid" value=""> 
                            </td>
                            <td>
                              <select
                                  class="form-control del_district"
                                  name="del_district[]"
                                   ${getDistrictOptions(stateid, districtid)}
                                  required
                              > 
                              </select>
                                 <input type="hidden" class="delivery_district" name="delivery_district[]" value="">
                                 <input type="hidden" class="delivery_district_id" name="delivery_district_id" value="">  
                           </td>

                            <td><input type="text" name="delivery_city[]" class="form-control" value="${city}" placeholder="City/Village" /></td>
                            <td><input type="text" name="delivery_pincode[]" class="form-control" value="${pincode}" placeholder="Pincode" /></td>
                            <td><input type="text" name="delivery_fulladdress[]" class="form-control" value="${address}" placeholder="Full Address" required /></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyDeliveryRow(this)" title="Copy Row">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rmdeliveryaddbtn">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `);
                    $("#deliveryaddresstbl tbody").append($newRow);
                    
                    // Set value and init select2
                    $newRow.find(".delivery_product_select").val(item.id).select2({ width: '100%' });
                });

                // 4. Remove the original "All Products" placeholder row
                $currentRow.remove();
                iziToast.success({ title: 'Applied', message: 'All items allocated to this address.' });
            } else {
                // Reset select if cancelled
                $currentRow.find(".delivery_product_select").val('');
            }
        });
    }

    window.copyDeliveryRow = function(btn) {
        const $row = $(btn).closest("tr");
        
        // Clone the row
        const $newRow = $row.clone();
        
        // 1. Clean up Select2 artifacts from the new row
        $newRow.find('.select2-container').remove();
        $newRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').removeAttr('aria-hidden').removeAttr('tabindex');
        
        // 2. Reset Product and Quantity for the new row
        $newRow.find(".delivery_product_select").val("");
        $newRow.find(".delivery_qty_input").val("");
        $newRow.find(".is-valid").removeClass("is-valid");
        $newRow.find(".pending-qty-badge").empty().hide();

        // 3. Append after current row
        $row.after($newRow);
        
        // 4. Initialize select2 for the new row
        $newRow.find(".delivery_product_select").select2({ width: '100%' });
        
        iziToast.info({ title: 'Copied', message: 'Row address details copied. Please select a product.', position: 'bottomRight' });
    }

    $(document).on("change", ".delivery_product_select, input[name='delivery_fulladdress[]']", function() {
        const $thisRow = $(this).closest("tr");
        const productId = $thisRow.find(".delivery_product_select").val();
        const address = $thisRow.find("input[name='delivery_fulladdress[]']").val();

        if (!productId || productId === 'all' || !address) return;

        let $match = null;
        $("#deliveryaddresstbl tbody tr").not($thisRow).each(function() {
            const p = $(this).find(".delivery_product_select").val();
            const a = $(this).find("input[name='delivery_fulladdress[]']").val();
            if (p === productId && a === address) {
                $match = $(this);
                return false;
            }
        });

        if ($match) {
            Swal.fire({
                title: 'Duplicate Entry!',
                text: 'This product is already mentioned with the same address. Please manage the order quantity in the existing highlighted row.',
                icon: 'warning',
                timer: 5000,
                timerProgressBar: true
            });

            // Highlight matching row for 5 seconds
            $match.addClass("table-warning");
            $match.css("transition", "background-color 0.5s ease");
            
            setTimeout(() => {
                $match.removeClass("table-warning");
            }, 5000);

            // Optional: reset current select to prevent duplicate
            $thisRow.find(".delivery_product_select").val("").trigger("change.select2");
        }
    });

    function validateProductQuantities(productId) {
        if (!productId || productId === 'all') return;

        // 1. Get total ordered qty from Step 2
        let totalOrderedQty = 0;
        $("#provisionorderitemsTable tbody tr").each(function() {
            if ($(this).find(".productname").val() === productId) {
                totalOrderedQty += parseFloat($(this).find(".qtycal").val()) || 0;
            }
        });

        // 2. Get total allocated qty in Step 3
        let totalAllocatedQty = 0;
        $("#deliveryaddresstbl tbody tr").each(function() {
            if ($(this).find(".delivery_product_select").val() === productId) {
                totalAllocatedQty += parseFloat($(this).find(".delivery_qty_input").val()) || 0;
            }
        });

        const pendingQty = Number((totalOrderedQty - totalAllocatedQty).toFixed(3));

        // 3. Update UI for all rows with this product
        $("#deliveryaddresstbl tbody tr").each(function() {
            if ($(this).find(".delivery_product_select").val() === productId) {
                const $input = $(this).find(".delivery_qty_input");
                const $badge = $(this).find(".pending-qty-badge");

                if (pendingQty > 0) {
                    $badge.html(`<span class="text-primary fw-bold"><i class="fa fa-info-circle"></i> Pending: ${pendingQty}</span>`).show();
                    $input.removeClass("is-invalid is-valid");
                } else if (pendingQty === 0) {
                    $badge.html(`<span class="text-success fw-bold"><i class="fa fa-check-circle"></i> Fully Assigned</span>`).show();
                    $input.addClass("is-valid").removeClass("is-invalid");
                } else {
                    $badge.html(`<span class="text-danger fw-bold"><i class="fa fa-exclamation-triangle"></i> Excess: ${Math.abs(pendingQty)}</span>`).show();
                    $input.addClass("is-invalid").removeClass("is-valid");
                }
            }
        });

        if (totalAllocatedQty > totalOrderedQty) {
            iziToast.destroy(); // Clear existing to avoid spam
            iziToast.error({ 
                title: 'Qty Overflow', 
                message: `Allocated (${totalAllocatedQty}) exceeds ordered (${totalOrderedQty}) for this product.`,
                position: 'bottomRight'
            });
        }
    }

    function getDaysDifference(d1, d2) {
        if (!d1 || !d2 || d1 === 'N/A' || d2 === 'N/A') return 999;
        try {
            const date1 = new Date(d1);
            const date2 = new Date(d2);
            if (isNaN(date1.getTime()) || isNaN(date2.getTime())) return 999;
            const diffTime = Math.abs(date2.getTime() - date1.getTime());
            return Math.floor(diffTime / (1000 * 3600 * 24));
        } catch(e) {
            return 999;
        }
    }

    function checkAndPromptConsolidatedDelivery(groups) {
        const groupKeys = Object.keys(groups);
        if (groupKeys.length < 2) return false;

        let candidates = [];
        for (let i = 0; i < groupKeys.length; i++) {
            for (let j = i + 1; j < groupKeys.length; j++) {
                const g1 = groups[groupKeys[i]];
                const g2 = groups[groupKeys[j]];
                
                const daysDiff = getDaysDifference(g1.date, g2.date);
                const kmDiff = Math.abs((parseFloat(g1.distance) || 0) - (parseFloat(g2.distance) || 0));

                if (daysDiff < 10 && kmDiff < 15) {
                    if (!candidates.includes(groupKeys[i])) candidates.push(groupKeys[i]);
                    if (!candidates.includes(groupKeys[j])) candidates.push(groupKeys[j]);
                }
            }
        }

        if (candidates.length < 2) return false;

        // Unique dates from candidate groups
        const candidateDates = [...new Set(candidates.map(k => groups[k].date))].filter(d => d && d !== 'N/A');
        if (candidateDates.length < 2) return false;

        // Build option list for date selection
        let dateOptionsHtml = '';
        candidateDates.forEach(d => {
            let formatted = d;
            try {
                let parts = d.split('-');
                if (parts.length === 3) formatted = `${parts[2]}-${parts[1]}-${parts[0]}`;
            } catch(e) {}
            dateOptionsHtml += `<option value="${d}">${formatted} (Existing Date)</option>`;
        });
        dateOptionsHtml += `<option value="manual">Custom / Manual Date...</option>`;

        Swal.fire({
            title: 'Consolidate Delivery Dates?',
            html: `
                <div class="text-start">
                    <p class="mb-2">Multiple delivery groups detected with delivery date difference <strong>&lt; 10 days</strong> (${candidateDates.join(', ')}) and distance difference <strong>&lt; 15 KM</strong>.</p>
                    <p class="fw-bold text-primary mb-0">Can all products be delivered once on a single date to save freight charge?</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Consolidate Delivery',
            cancelButtonText: 'No, Keep Separate',
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Select Delivery Date',
                    html: `
                        <div class="text-start p-2">
                            <label class="form-label fw-bold mb-2">Select single delivery date:</label>
                            <select id="swal_consolidated_date_select" class="form-select mb-3">
                                ${dateOptionsHtml}
                            </select>
                            <div id="swal_manual_date_wrapper" style="display:none;">
                                <label class="form-label fw-bold mb-1">Enter Manual Date:</label>
                                <input type="date" id="swal_manual_date_input" class="form-control" />
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Update Date & Recalculate Freight',
                    cancelButtonText: 'Cancel',
                    didOpen: () => {
                        $('#swal_consolidated_date_select').on('change', function() {
                            if ($(this).val() === 'manual') {
                                $('#swal_manual_date_wrapper').slideDown();
                            } else {
                                $('#swal_manual_date_wrapper').slideUp();
                            }
                        });
                    },
                    preConfirm: () => {
                        const sel = $('#swal_consolidated_date_select').val();
                        let finalDate = sel;
                        if (sel === 'manual') {
                            finalDate = $('#swal_manual_date_input').val();
                            if (!finalDate) {
                                Swal.showValidationMessage('Please select a valid manual date.');
                                return false;
                            }
                        }
                        return finalDate;
                    }
                }).then((dateResult) => {
                    if (dateResult.isConfirmed && dateResult.value) {
                        const chosenDate = dateResult.value;
                        
                        // Update delivery date for all items in Step 2 table
                        $("#provisionorderitemsTable tbody tr").each(function() {
                            $(this).find(".expecteddelivery").val(chosenDate);
                        });

                        iziToast.success({
                            title: 'Delivery Consolidated',
                            message: `Delivery date updated to ${chosenDate}. Freight recalculated for 1-time delivery!`,
                            position: 'topRight'
                        });

                        // Re-generate freight groups with skipPrompt = true
                        generateFreightGroups(true);
                    }
                });
            }
        });

        return true;
    }

    function generateFreightGroups(skipPrompt = false) {
      if (window.OrderModule && window.OrderModule.keepFreight()) {
          $('.freightratemodal').prop('disabled', $("input[name='pickupfrom']:checked").val() !== 'delivered');
          window.renderFreightEligibilityRules(false);
          return;
      }
      // Rebuilt groups become the current cache after synchronous row creation.
      if (window.OrderModule && window.OrderModule.ready) Promise.resolve().then(function () {
          window.OrderModule.freightFingerprint = window.OrderModule.inputsFingerprint();
      });

      var pickupfrom = $('input[name="pickupfrom"]:checked').val() || $("#orderDeliveryFreightForm input[name='pickupfrom']:checked").val() || 'delivered';
        const $tbody = $("#freightGroupTable tbody");
        $tbody.empty();

        if (pickupfrom === 'boxpacking'){ // ajay ak
          $("#freeFreightNotification").empty().hide();
          $("#spanratedbl").html("").addClass("d-none");
          const groups = {};
          /* =========================================================
             BOX PACKING -> FREIGHT GROUP TABLE
             Source: #deliveryboxtbl
          ========================================================= */

          $("#deliveryboxtbl tbody tr.box-delivery-row").each(function () {
              const $row = $(this);
              // -----------------------------------------------------
              // Product
              // -----------------------------------------------------
              const productId =  $row.find(".box_productid").val() || "";
              const productName = $row.find(".box_productname").val() || "";
              const qty =  parseFloat(
                      $row.find(".box_product_qty").val()
                  ) || 0;
 
              // -----------------------------------------------------
              const deliveryDate = $row.find(".boxdate").text().trim();
              // -----------------------------------------------------
              // State
              // -----------------------------------------------------
              const stateId = $row.find(".state_box").val() || "";

              const stateName =  $row.find(".state_box option:selected").text().trim();


              // -----------------------------------------------------
              // District
              // -----------------------------------------------------
              const districtId =
                  $row.find(".district_box").val() || "";

              const districtName =
                  $row.find(".district_box option:selected")
                      .text()
                      .trim();


              // -----------------------------------------------------
              // City / Village
              // -----------------------------------------------------
              const locationId =
                  $row.find(".city_box").val() || "";

              const locationName =
                  $row.find(".city_box option:selected")
                      .text()
                      .trim();


              // -----------------------------------------------------
              // Pickup Point
              // -----------------------------------------------------
              const pickupPoint =
                  $row.find(".pickuppoint").val() || "";


              // -----------------------------------------------------
              // Box Detail
              // -----------------------------------------------------
              const plantsPerBox =
                  parseFloat(
                      $row.find(".plants_per_box").val()
                  ) || 0;

              const noOfBoxes =
                  parseFloat(
                      $row.find(".no_of_box").val()
                  ) || 0;


              // -----------------------------------------------------
              // Freight Detail
              // -----------------------------------------------------
              const freightCharge =
                  parseFloat(
                      $row.find(".freighcharges").val()
                  ) || 0;

              const freightAmount =
                  parseFloat(
                      $row.find(".freightamount").val()
                  ) || 0;


              // -----------------------------------------------------
              // Group Key
              //
              // Same Pickup + State + District + City + Date
              // will become one freight row.
              // -----------------------------------------------------
              const groupKey = [
                  pickupPoint,
                  stateId,
                  districtId,
                  locationId,
                  deliveryDate,
                  freightCharge
              ].join("|");


              if (!groups[groupKey]) {

                  groups[groupKey] = {

                      pickupPoint: pickupPoint,

                      stateId: stateId,
                      stateName: stateName,

                      districtId: districtId,
                      districtName: districtName,

                      locationId: locationId,
                      locationName: locationName,

                      date: deliveryDate,

                      plantsPerBox: plantsPerBox,

                      freightCharge: freightCharge,

                      freightAmount: 0,

                      noOfBoxes: 0,

                      distance: 0,

                      items: []
                  };
              }


              // -----------------------------------------------------
              // Group Totals
              // -----------------------------------------------------
              groups[groupKey].freightAmount += freightAmount;

              groups[groupKey].noOfBoxes += noOfBoxes;


              // -----------------------------------------------------
              // Product Detail
              // -----------------------------------------------------
              groups[groupKey].items.push({

                  productId: productId,

                  name: productName,

                  qty: qty,

                  noOfBoxes: noOfBoxes
              });

          });

          let groupIndex = 0;

          const projectid = '<?php echo $projectid; ?>';
          const rooturl   = '<?php echo $rooturl; ?>';


          for (const key in groups) {

              const g = groups[key];


              // -----------------------------------------------------
              // Total Product Qty
              // -----------------------------------------------------
              const totalGroupQty =
                  g.items.reduce(
                      (sum, item) =>
                          sum + (parseFloat(item.qty) || 0),
                      0
                  );


              // -----------------------------------------------------
              // Freight already calculated in deliveryboxtbl
              // -----------------------------------------------------
              const freightAmount =
                  parseFloat(g.freightAmount) || 0;


              // -----------------------------------------------------
              // No map/distance logic for Box Packing
              // -----------------------------------------------------
              const distance = 0;


              const loginRoleId = parseInt($('body').data('loginroleid') || $('#hidden_loginroleid').val() || '<?php echo (int)($loginroleid ?? ($_SESSION['loginroleid'] ?? 0)); ?>') || 0;
              const isDiscReadOnly = (loginRoleId > 3);

              const html = `

                  <tr
                      class="group-row"
                      data-totalqty="${totalGroupQty}"
                  >

                      <!-- =========================================
                           ADDRESS
                           Pickup Point + State + District + City
                      ========================================== -->
                      <td>

                          <div class="d-flex align-items-start gap-2">

                              <i class="fa fa-map-marker-alt text-primary mt-1"></i>

                              <div>

                                  <!-- PICKUP POINT -->
                                  <div class="fw-bold text-dark">

                                      ${
                                          g.pickupPoint
                                              ? escapeHtml(g.pickupPoint)
                                              : "N/A"
                                      }

                                  </div>


                                  <!-- STATE -->
                                  <div class="small text-muted">

                                      ${
                                          g.stateName
                                              ? escapeHtml(g.stateName)
                                              : ""
                                      }

                                  </div>


                                  <!-- DISTRICT -->
                                  <div class="small text-muted">

                                      ${
                                          g.districtName
                                              ? escapeHtml(g.districtName)
                                              : ""
                                      }

                                  </div>


                                  <!-- CITY / VILLAGE -->
                                  <div
                                      class="small text-secondary"
                                      style="max-width:200px;"
                                  >

                                      ${
                                          g.locationName
                                              ? escapeHtml(g.locationName)
                                              : ""
                                      }

                                  </div>


                                  <!-- Existing field name kept same -->
                                  <input
                                      type="hidden"
                                      name="f_address_key[]"
                                      value="${escapeHtml(key)}"
                                  >


                                  <!-- Additional useful transaction values -->
                                  <input
                                      type="hidden"
                                      name="f_pickup_point[]"
                                      value="${escapeHtml(g.pickupPoint)}"
                                  >

                                  <input
                                      type="hidden"
                                      name="f_stateid[]"
                                      value="${escapeHtml(g.stateId)}"
                                  >

                                  <input
                                      type="hidden"
                                      name="f_statename[]"
                                      value="${escapeHtml(g.stateName)}"
                                  >

                                  <input
                                      type="hidden"
                                      name="f_districtid[]"
                                      value="${escapeHtml(g.districtId)}"
                                  >

                                  <input
                                      type="hidden"
                                      name="f_districtname[]"
                                      value="${escapeHtml(g.districtName)}"
                                  >

                                  <input
                                      type="hidden"
                                      name="f_locationid[]"
                                      value="${escapeHtml(g.locationId)}"
                                  >

                                  <input
                                      type="hidden"
                                      name="f_locationname[]"
                                      value="${escapeHtml(g.locationName)}"
                                  >

                              </div>

                          </div>

                      </td>


                      <!-- =========================================
                           DATE
                      ========================================== -->
                      <td class="text-center">

                          <div
                              class="fw-bold text-primary"
                              style="font-size:0.95rem;"
                          >

                              ${escapeHtml(String(g.date || '').replace(/^(\d{4})-(\d{2})-(\d{2})$/, '$3-$2-$1'))}

                          </div>

                      </td>


                      <!-- =========================================
                           PRODUCT DETAILS
                      ========================================== -->
                      <td>

                          <div class="d-flex flex-wrap gap-1 mb-1">

                              ${g.items.map(item => `

                                  <span
                                      class="
                                          badge
                                          bg-white
                                          text-dark
                                          border
                                          shadow-sm
                                          px-2
                                          py-1
                                      "
                                  >

                                      ${escapeHtml(item.name)}

                                      <strong class="text-primary">
                                          (${item.qty})
                                      </strong>

                                  </span>

                              `).join("")}

                          </div>


                          <!-- NO OF BOXES -->
                          <div
                              class="
                                  small
                                  text-muted
                                  d-flex
                                  align-items-center
                                  gap-2
                              "
                          >

                              <i class="fa fa-cubes me-1"></i>

                              No of Boxes:

                              <strong>
                                  ${g.noOfBoxes}
                              </strong>

                          </div>

                      </td>


                      <!-- =========================================
                           DISTANCE
                           No map for Box Packing
                      ========================================== -->
                      <td class="text-center">

                          <span class="fw-bold text-muted">
                              N/A
                          </span>

                          <br>

                          <small
                              class="text-muted text-uppercase fw-bold"
                              style="font-size:0.7rem;"
                          >
                              No Map
                          </small>

                      </td>


                      <!-- =========================================
                           FREIGHT AMOUNT
                           PICKED FROM deliveryboxtbl
                      ========================================== -->
                      <td>

                          <div class="input-group input-group-sm">

                              <span
                                  class="
                                      input-group-text
                                      bg-white
                                      border-end-0
                                  "
                              >
                                  Rs
                              </span>


                              <input
                                  type="text"
                                  name="f_amount[]"
                                  class="
                                      form-control
                                      f_amt_input
                                      border-start-0
                                      fw-bold
                                  "
                                  value="${freightAmount.toFixed(2)}"
                                  onKeyPress="
                                      return isFloatNumber(event,this);
                                  "
                              >

                          </div>


                          <!-- BOX CHARGE INFO -->
                          <div
                              class="
                                  small
                                  text-muted
                                  mt-1
                              "
                              style="font-size:0.75rem;"
                          >

                              Box Rate:

                              <strong>
                                  Rs ${g.freightCharge}
                              </strong>

                          </div>


                          <input
                              type="hidden"
                              name="f_box_freight_charge[]"
                              value="${g.freightCharge}"
                          >

                      </td>


                      <!-- =========================================
                           DISCOUNT
                           Existing name/class kept
                      ========================================== -->
                      <td>

                          <div class="input-group input-group-sm">

                              <input
                                  type="text"
                                  name="f_discount[]"
                                  class="
                                      form-control
                                      f_disc_input
                                      text-center
                                      ${isDiscReadOnly ? 'bg-light' : ''}
                                  "
                                  value="0"
                                  ${isDiscReadOnly ? 'readonly' : ''}
                                  onKeyPress="
                                      return isFloatNumber(event,this);
                                  "
                              >

                              <span
                                  class="
                                      input-group-text
                                      bg-white
                                      border-start-0
                                  "
                              >
                                  %
                              </span>

                          </div>

                      </td>


                      <!-- =========================================
                           PAYABLE
                      ========================================== -->
                      <td>

                          <div class="input-group input-group-sm">

                              <span
                                  class="
                                      input-group-text
                                      bg-light
                                      border-end-0
                                  "
                              >
                                  Rs
                              </span>


                              <input
                                  type="text"
                                  name="f_payable[]"
                                  class="
                                      form-control
                                      f_payable_input
                                      bg-light
                                      fw-bold
                                      text-primary
                                      border-start-0
                                  "
                                  readonly
                                  value="${freightAmount.toFixed(2)}"
                              >

                          </div>

                      </td>


                      <!-- =========================================
                           ACTION / INFORMATION
                      ========================================== -->
                      <td class="text-center">

                          <div class="f_vehicle_badge mt-1">

                              <span
                                  class="
                                      badge
                                      bg-info-subtle
                                      text-info
                                      border
                                      border-info-subtle
                                  "
                                  style="font-size:0.75rem;"
                              >

                                  <i class="fa fa-box me-1"></i>

                                  Box Packing

                              </span>

                          </div>


                          <!-- Existing name/class kept -->
                          <input
                              type="hidden"
                              name="f_vehicle[]"
                              class="f_vehicle_info"
                              value="${escapeHtml(g.pickupPoint)}"
                          >


                          <!-- Existing field retained -->
                          <input
                              type="hidden"
                              name="f_distance[]"
                              class="f_distance_info"
                              value="0"
                          >
                          <input type="hidden" name="f_totaldistance[]" class="f_totaldistance_input" value="0" />
                          <input type="hidden" name="f_freedistance[]" class="f_freedistance_input" value="0" />
                          <input type="hidden" name="f_payabledistance[]" class="f_payabledistance_input" value="0" />
                          <input type="hidden" name="f_narration[]" class="f_narration_input" value="" />

                      </td>

                  </tr>
              `;


              $tbody.append(html);

              groupIndex++;
          }

          if (groupIndex === 0) {
              $tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">No box packing data found. Please check Step 3.</td></tr>');
          }

          $("#freeFreightNotification").empty().hide();
          $("#spanratedbl").html("").addClass("d-none");
          calculateGrandTotalFreight();
          updateDiscountRemarksNarration();

             if(pickupfrom ==="delivered"){
                $(".f_disc_input").prop("disabled", false);
                $(".f_amt_input").prop("disabled", false);

             }else{
                  $(".f_disc_input").prop("disabled", true);
                  $(".f_amt_input").prop("disabled", true);
             }

               if(pickupfrom ==="delivered"){
                $(".f_disc_input").prop("disabled", false);
                $(".f_amt_input").prop("disabled", false);

             }else{
                  $(".f_disc_input").prop("disabled", true);
                  $(".f_amt_input").prop("disabled", true);
             }
          return;
        } 

        if (pickupfrom === 'pickup') {
            $("#freeFreightNotification").empty().hide();
            $("#spanratedbl").html("").addClass("d-none");
            $tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">Self Pickup selected. No freight charges applicable.</td></tr>');
             if(pickupfrom ==="delivered"){
                $(".f_disc_input").prop("disabled", false);
                $(".f_amt_input").prop("disabled", false);

             }else{
                  $(".f_disc_input").prop("disabled", true);
                  $(".f_amt_input").prop("disabled", true);
             }
            calculateGrandTotalFreight();
            updateDiscountRemarksNarration();
             if(pickupfrom ==="delivered"){
                $(".f_disc_input").prop("disabled", false);
                $(".f_amt_input").prop("disabled", false);

             }else{
                  $(".f_disc_input").prop("disabled", true);
                  $(".f_amt_input").prop("disabled", true);
             }
            return;
        } 
        // 1. Map Product -> Delivery Date and Grafted Status from Step 2
        const productInfo = {};
        $("#provisionorderitemsTable tbody tr").each(function() {
            const $pSelect = $(this).find(".productname");
            const productId = $pSelect.val();
            const date = $(this).find(".expecteddelivery").val();
            const selectedOpt = $pSelect.find("option:selected");
            const optText = selectedOpt.text() || '';
            const optIsGrafted = selectedOpt.data("isgrafted");
            
            let isGrafted = false;
            if (optIsGrafted) {
                isGrafted = String(optIsGrafted).toLowerCase() === 'grafted';
            } else if (window.allProductsMap && window.allProductsMap[productId]) {
                const prodData = window.allProductsMap[productId];
                const gVal = prodData.IsGrafted || (prodData.sku && prodData.sku.toUpperCase().includes('NONGRAFTED') ? 'NonGrafted' : (prodData.sku && prodData.sku.toUpperCase().includes('GRAFTED') ? 'Grafted' : ''));
                isGrafted = String(gVal).toLowerCase() === 'grafted';
            } else {
                isGrafted = (/^grafted/i.test(optText) && !/^nongrafted|non-grafted/i.test(optText));
            }

            if (productId && date) {
                productInfo[productId] = {
                    date: date,
                    isGrafted: isGrafted
                };
            }
        });

        // 2. Group Step 3 rows by Address + Date
        const groups = {};
        $("#deliveryaddresstbl tbody tr").each(function() {
            const productId = $(this).find(".delivery_product_select").val();
            const productName = $(this).find(".delivery_product_select option:selected").text();
            const qty = parseFloat($(this).find(".delivery_qty_input").val()) || 0;
            const distance = parseFloat($(this).find(".delivery_distance_input").val()) || 0;
            const pInfo = productInfo[productId] || {};
            const date = pInfo.date || 'N/A';
            const isGrafted = pInfo.isGrafted || false;
            
            // Address details
            const city = $(this).find("input[name='delivery_city[]']").val() || '';
            const district = $(this).find("input[name='delivery_district[]']").val() || '';
            const address = $(this).find("input[name='delivery_fulladdress[]']").val() || '';
            
            const addressKey = `${city}|${district}|${address}`;
            const groupKey = `${addressKey}@@@${date}`;

            if (!groups[groupKey]) {
                groups[groupKey] = {
                    address: { city, district, address },
                    date: date,
                    distance: distance, // Use the distance from the first row in the group
                    items: []
                };
            }
            groups[groupKey].items.push({ name: productName, qty: qty, isGrafted: isGrafted, productId: productId });
        });

        // Fallback if Step 3 delivery rows were skipped
        if (Object.keys(groups).length === 0) {
            const defaultAddress = $("#delivery_address").val() || 'Client Delivery Address (Update Later)';
            const defaultCity = $("#cityname").val() || '';
            const defaultDistrict = $("#districtname").val() || '';
            const defaultDate = Object.values(productInfo)[0] ? Object.values(productInfo)[0].date : 'N/A';

            const defaultItems = [];
            $("#provisionorderitemsTable tbody tr").each(function() {
                const $pSelect = $(this).find(".productname");
                const productId = $pSelect.val();
                const pName = $pSelect.find("option:selected").text() || $pSelect.val();
                const pQty = parseFloat($(this).find(".qty_td").val()) || 0;
                const pInfo = productInfo[productId] || {};
                const isGrafted = pInfo.isGrafted || false;
                if (pName && pQty > 0) {
                    defaultItems.push({ name: pName, qty: pQty, isGrafted: isGrafted, productId: productId });
                }
            });

            if (defaultItems.length > 0) {
                const groupKey = `${defaultCity}|${defaultDistrict}|${defaultAddress}@@@${defaultDate}`;
                groups[groupKey] = {
                    address: { city: defaultCity, district: defaultDistrict, address: defaultAddress },
                    date: defaultDate,
                    distance: 0,
                    items: defaultItems
                };
            }
        }

        if (!skipPrompt && Object.keys(groups).length > 1) {
            checkAndPromptConsolidatedDelivery(groups);
        }

        // 3. Render Groups
        let groupIndex = 0;
        const projectid = '<?php echo $projectid; ?>';
        const rooturl = '<?php echo $rooturl; ?>';
        const loginRoleId = parseInt($('body').data('loginroleid') || $('#hidden_loginroleid').val() || '<?php echo (int)($loginroleid ?? ($_SESSION['loginroleid'] ?? 0)); ?>') || 0;
        const isDiscReadOnly = (loginRoleId > 3);

        for (const key in groups) {
            const g = groups[key];
            const totalGroupQty = g.items.reduce((sum, i) => sum + (parseFloat(i.qty) || 0), 0);
            const totalGraftedQty = g.items.filter(i => i.isGrafted).reduce((sum, i) => sum + (parseFloat(i.qty) || 0), 0);
            const plantType = (totalGraftedQty > 0 && totalGraftedQty === totalGroupQty) ? 'Grafted' : (totalGraftedQty === 0 ? 'Nongrafted' : 'Both');
            const km = parseFloat(g.distance) || 0;

            const html = `
                <tr class="group-row" data-totalqty="${totalGroupQty}" data-graftedqty="${totalGraftedQty}" data-planttype="${plantType}" data-stateid="${g.address.stateid || ''}" data-districtid="${g.address.districtid || ''}">
                    <td>
                        <div class="d-flex align-items-start gap-2">
                            <i class="fa fa-map-marker-alt text-primary mt-1"></i>
                            <div>
                                <div class="fw-bold text-dark">${g.address.city || 'N/A'}</div>
                                <div class="small text-muted">${g.address.district}</div>
                                <div class="small text-secondary" style="max-width: 200px;">${g.address.address}</div>
                                <input type="hidden" name="f_address_key[]" value="${key}" />
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="fw-bold text-primary" style="font-size: 0.95rem;">${escapeHtml(String(g.date || '').replace(/^(\d{4})-(\d{2})-(\d{2})$/, '$3-$2-$1'))}</div>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1 mb-1">
                            ${g.items.map(i => `<span class="badge bg-white text-dark border shadow-sm px-2 py-1">${i.name} <strong class="text-primary">(${i.qty})</strong> ${i.isGrafted ? '<span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size:0.65rem;">Grafted</span>' : '<span class="badge bg-warning-subtle text-dark border border-warning-subtle ms-1" style="font-size:0.65rem;">Non-Grafted</span>'}</span>`).join('')}
                        </div>
                        <div class="small text-muted d-flex align-items-center gap-2">
                            <span><i class="fa fa-boxes me-1"></i>Total Qty: <strong>${totalGroupQty.toLocaleString()}</strong></span>
                            ${totalGraftedQty > 0 ? `<span class="text-success ms-2"><i class="fa fa-seedling me-1"></i>Grafted: <strong>${totalGraftedQty.toLocaleString()}</strong></span>` : ''}
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold text-dark fs-5">${g.distance}</span><br>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Km</small>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">Rs</span>
                            <input type="text" name="f_amount[]" class="form-control f_amt_input border-start-0 fw-bold" value="0" onKeyPress="return isFloatNumber(event,this);" />
                        </div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="text" name="f_discount[]" class="form-control f_disc_input text-center ${isDiscReadOnly ? 'bg-light' : ''}" value="0" ${isDiscReadOnly ? 'readonly' : ''} onKeyPress="return isFloatNumber(event,this);" />
                            <span class="input-group-text bg-white border-start-0">%</span>
                        </div>
                        <div class="f_discount_info small text-success mt-1" style="font-size: 0.72rem; display:none;"></div>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">Rs</span>
                            <input type="text" name="f_payable[]" class="form-control f_payable_input bg-light fw-bold text-primary border-start-0" readonly value="0.00" />
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center align-items-center">
                            <button type="button" class="btn btn-primary btn-sm rounded-circle freightratemodal" onclick="openFreightRowCalculator(this, ${g.distance}, ${totalGroupQty})" title="Calculate Freight">
                                <i class="fa fa-calculator"></i>
                            </button>
                        </div>
                        <div class="f_vehicle_badge mt-1">
                            <span class="badge bg-info-subtle text-info border border-info-subtle auto-vehicle-badge" style="font-size: 0.75rem;"><i class="fa fa-spinner fa-spin me-1"></i>Auto...</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1 justify-content-center align-items-center mt-2">
                            <label class="small mb-0">Trips</label>
                            <input type="number" class="form-control form-control-sm f_trips_input text-center" min="1" step="1" value="1" style="width:65px" aria-label="Number of trips" />
                            <button type="button" class="btn btn-outline-primary btn-sm f_recalculate_trips">Recalculate</button>
                        </div>
                        <input type="hidden" name="f_vehicle[]" class="f_vehicle_info" value="" />
                        <input type="hidden" name="f_distance[]" class="f_distance_info" value="${g.distance}" />
                        <input type="hidden" name="f_totaldistance[]" class="f_totaldistance_input" value="${g.distance}" />
                        <input type="hidden" name="f_freedistance[]" class="f_freedistance_input" value="0" />
                        <input type="hidden" name="f_payabledistance[]" class="f_payabledistance_input" value="${g.distance}" />
                        <input type="hidden" name="f_narration[]" class="f_narration_input" value="" />
                    </td>
                </tr>
            `;
            $tbody.append(html);
            groupIndex++;
        }

        if (!window.OrderModule || !window.OrderModule.hydrating) renderFreightEligibilityRules();

        if (groupIndex === 0) {
            $tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">No delivery data found. Please check Step 3.</td></tr>');
        } else {
            // Auto-trigger calculation for group rows
            $("#freightGroupTable tbody tr.group-row").each(function() {
                if (!window.OrderModule || !window.OrderModule.hydrating) autoCalculateRowFreight($(this), projectid, rooturl);
            });
            calculateGrandTotalFreight();
        }
    }

    /**
     * Render the Freight Eligibility Rules banner dynamically with full-width row layout and Apply/Clear buttons
     * Only displays when delivery is by Company ('delivered') and only lists matched/eligible rules.
     */
    window.renderFreightEligibilityRules = function(autoApply = true) {
        const $notif = $("#freeFreightNotification");
        const customerType = $("#customer_type").val() || 'Farmer';
        
        // 1. Check Delivery Method: Only show Freight Eligibility Rules when Delivery by Company ('delivered') is selected
        const pickupfrom = $('input[name="pickupfrom"]:checked').val() || 
                           $("#orderDeliveryFreightForm input[name='pickupfrom']:checked").val();

        if (pickupfrom !== 'delivered') {
            $notif.empty().hide();
            return;
        }

        var rooturl = $('body').data('routurl') || '../';
        var cleanRoot = rooturl || '';
        if (cleanRoot.endsWith('/')) {
            cleanRoot = cleanRoot.substring(0, cleanRoot.length - 1);
        }
        var ajaxUrl = cleanRoot + "/freight_discount_module/modal/freight_discount_modal.php?action=get_active_rules";

        // Collect current delivery row states
        let orderRows = [];
        $("#freightGroupTable tbody tr.group-row").each(function() {
            const $r = $(this);
            const qty = parseFloat($r.data("totalqty")) || 0;
            const dist = parseFloat($r.find(".f_distance_info").val()) || 0;
            const plantType = $r.data("planttype") || 'Grafted';
            const discInput = parseFloat($r.find(".f_disc_input").val()) || 0;
            const payable = parseFloat($r.find(".f_payable_input").val()) || 0;
            const amt = parseFloat($r.find(".f_amt_input").val()) || 0;
            const isCleared = $r.attr("data-discount-cleared") === "true";
            if (qty > 0 || dist > 0 || amt > 0) {
                orderRows.push({
                    qty: qty,
                    distance: dist,
                    plantType: plantType,
                    discount: discInput,
                    payable: payable,
                    amt: amt,
                    isCleared: isCleared,
                    row: $r
                });
            }
        });

        if (orderRows.length === 0) {
            $notif.empty().hide();
            return;
        }

        $.ajax({
            url: ajaxUrl,
            type: "POST",
            data: {
                customer_type: customerType,
                plant_type: 'All'
            },
            dataType: "JSON",
            success: function(res) {
                if (!res || !res.status || !Array.isArray(res.data) || res.data.length === 0) {
                    $notif.empty().hide();
                    return;
                }

                const rules = res.data;
                window.currentFreightRules = rules;
                let anyEligible = false;
                let matchedRuleNames = [];
                let matchedRulesToAutoApply = [];
                let rulesHtmlArray = [];

                rules.forEach(function(rule) {
                    const minQty = parseInt(rule.min_order_qty) || 0;
                    const maxQty = parseInt(rule.max_order_qty) || 0;
                    const minDist = parseFloat(rule.min_distance_km) || 0;
                    const maxDist = parseFloat(rule.max_distance_km) || 0;
                    const discVal = parseFloat(rule.discount_value) || 0;
                    const discType = rule.discount_type;

                    // Condition string for Qty: order qt >= min qty && <= max qty
                    let qtyStr = '';
                    if (minQty > 0 && maxQty > 0) {
                        qtyStr = `Order Qty &ge; <strong>${minQty.toLocaleString()}</strong> && &le; <strong>${maxQty.toLocaleString()}</strong>`;
                    } else if (minQty > 0) {
                        qtyStr = `Order Qty &ge; <strong>${minQty.toLocaleString()}</strong>`;
                    } else if (maxQty > 0) {
                        qtyStr = `Order Qty &le; <strong>${maxQty.toLocaleString()}</strong>`;
                    } else {
                        qtyStr = `Any Order Qty`;
                    }

                    // Condition string for Distance: distance <=
                    let distStr = '';
                    if (minDist > 0 && maxDist > 0) {
                        distStr = `Distance &ge; <strong>${minDist} KM</strong> && &le; <strong>${maxDist} KM</strong>`;
                    } else if (minDist > 0) {
                        distStr = `Distance &le; <strong>${minDist} KM</strong>`;
                    } else if (maxDist > 0) {
                        distStr = `Distance &le; <strong>${maxDist} KM</strong>`;
                    } else {
                        distStr = `Any Distance`;
                    }

                    // Benefit badge
                    let benefitBadge = '';
                    let benefitText = '';
                    if (discType === 'Percentage') {
                        if (discVal >= 100) {
                            benefitBadge = '<span class="badge bg-success text-white px-2 py-1 fs-7"><i class="fa fa-gift me-1"></i>100% Free Freight</span>';
                            benefitText = '100% Free Freight';
                        } else {
                            benefitBadge = `<span class="badge bg-info text-dark px-2 py-1 fs-7"><i class="fa fa-percent me-1"></i>${discVal}% Off</span>`;
                            benefitText = `${discVal}% Off`;
                        }
                    } else if (discType === 'Fixed_Amount') {
                        benefitBadge = `<span class="badge bg-primary text-white px-2 py-1 fs-7">Rs ${discVal} Off</span>`;
                        benefitText = `Rs ${discVal} Off`;
                    } else if (discType === 'Per_Plant') {
                        benefitBadge = `<span class="badge bg-primary text-white px-2 py-1 fs-7">Rs ${discVal}/Plant Off</span>`;
                        benefitText = `Rs ${discVal}/Plant Off`;
                    } else if (discType === 'Per_KM') {
                        benefitBadge = `<span class="badge bg-primary text-white px-2 py-1 fs-7">Rs ${discVal}/KM Off</span>`;
                        benefitText = `Rs ${discVal}/KM Off`;
                    }

                    // Plant type badge
                    let plantBadge = '';
                    if (rule.plant_type === 'Grafted') {
                        plantBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle me-1"><i class="fa fa-seedling me-1"></i>Grafted</span>';
                    } else if (rule.plant_type === 'Nongrafted') {
                        plantBadge = '<span class="badge bg-warning-subtle text-dark border border-warning-subtle me-1"><i class="fa fa-leaf me-1"></i>Non-Grafted</span>';
                    } else {
                        plantBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1">All Crops</span>';
                    }

                    // Customer type badge
                    let custBadge = '';
                    if (rule.customer_type === 'Dealer') {
                        custBadge = '<span class="badge bg-info-subtle text-info border border-info-subtle me-1"><i class="fa fa-briefcase me-1"></i>Dealer</span>';
                    } else if (rule.customer_type === 'Farmer') {
                        custBadge = '<span class="badge bg-light text-dark border me-1"><i class="fa fa-user me-1"></i>Farmer</span>';
                    } else {
                        custBadge = '<span class="badge bg-light text-muted border me-1">All Customers</span>';
                    }

                    // Check if any row in current order satisfies this rule: distance and order qty matched
                    let isMatched = false;
                    let isApplied = false;
                    let canAutoApplyThisRule = false;
                    for (let r of orderRows) {
                        const custOk = (rule.customer_type === 'All' || rule.customer_type === customerType);
                        const plantOk = (rule.plant_type === 'Both' || rule.plant_type === 'All' || rule.plant_type.toLowerCase() === r.plantType.toLowerCase());
                        const qtyOk = (minQty === 0 || r.qty >= minQty) && (maxQty === 0 || r.qty <= maxQty);
                        const distOk = (maxDist === 0 || r.distance <= maxDist);

                        if (custOk && plantOk && qtyOk && distOk && r.qty > 0 && r.distance > 0) {
                            isMatched = true;
                            anyEligible = true;
                            if (r.discount > 0) {
                                isApplied = true;
                            } else if (!r.isCleared && r.amt > 0) {
                                canAutoApplyThisRule = true;
                            }
                            if (!matchedRuleNames.includes(rule.plan_name)) {
                                matchedRuleNames.push(rule.plan_name);
                            }
                        }
                    }

                    if (canAutoApplyThisRule && !matchedRulesToAutoApply.includes(rule.id)) {
                        matchedRulesToAutoApply.push(rule.id);
                    }

                    // ONLY render matched rules in the list!
                    if (!isMatched) {
                        return;
                    }

                    const cardClass = 'border-success bg-success-subtle shadow-sm';

                    rulesHtmlArray.push(`
                        <div class="col-12 mb-2">
                            <div class="card ${cardClass} rounded-3 border p-2 p-md-3" style="transition: all 0.2s ease;">
                                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                                    <!-- Left Column: Plan Title, Badges & Criteria -->
                                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2 gap-md-4 flex-grow-1">
                                        <div style="min-width: 290px;">
                                            <div class="fw-bold text-success fs-6 mb-1">
                                                <i class="fa fa-file-invoice text-success me-1"></i>${escapeHtml(rule.plan_name)}
                                            </div>
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <span class="badge bg-light text-muted border" style="font-size:0.72rem;">${escapeHtml(rule.plan_code)}</span>
                                                ${custBadge}
                                                ${plantBadge}
                                                <span class="badge bg-success text-white"><i class="fa fa-check-circle me-1"></i>Eligible & Matched</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Criteria in the middle -->
                                        <div class="border-start-md ps-md-3 text-secondary small flex-grow-1" style="font-size: 0.84rem; line-height: 1.5;">
                                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                                <div><i class="fa fa-road text-muted me-1"></i>${distStr}</div>
                                                <div><i class="fa fa-boxes text-muted me-1"></i>${qtyStr}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column: Benefit & Action Buttons (Apply & Clear) -->
                                    <div class="d-flex align-items-center justify-content-between justify-content-lg-end gap-2 mt-2 mt-lg-0 pt-2 pt-lg-0 border-top border-top-lg-0">
                                        <div class="me-2">${benefitBadge}</div>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-success px-3 shadow-sm" onclick="applyPlanDiscountToFreight(${rule.id})" title="Apply ${benefitText} to matching ${rule.plant_type} delivery rows">
                                                <i class="fa fa-check-circle me-1"></i>Apply
                                            </button>
                                            <button type="button" class="btn btn-outline-danger px-3" onclick="clearPlanDiscountFromFreight(${rule.id})" title="Clear discount for ${rule.plant_type} delivery rows">
                                                <i class="fa fa-times me-1"></i>Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                });

                // Auto apply matched rules if any
                if (autoApply && matchedRulesToAutoApply.length > 0) {
                    matchedRulesToAutoApply.forEach(function(rId) {
                        applyPlanDiscountToFreight(rId, true);
                    });
                    return;
                }

                // If no rules matched, hide banner completely
                if (!anyEligible || rulesHtmlArray.length === 0) {
                    $notif.empty().hide();
                    return;
                }

                let rulesHtml = rulesHtmlArray.join('');

                let bannerHtml = `
                    <div class="alert alert-success border-success shadow-sm rounded-3 mb-3 p-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-check-circle text-success fs-5"></i>
                                <strong class="fs-6 text-success">Freight Eligibility Rules</strong>
                                <span class="badge bg-white text-secondary border ms-1">${customerType} Active Plans</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success text-white px-3 py-2 fs-7 shadow-sm"><i class="fa fa-gift me-1"></i>Free Freight / Discount Applied!</span>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" onclick="clearPlanDiscountFromFreight(0)" title="Clear all applied discounts">
                                    <i class="fa fa-undo me-1"></i>Clear All Discounts
                                </button>
                            </div>
                        </div>
                        <div class="row g-2">
                            ${rulesHtml}
                        </div>
                    </div>
                `;

                $notif.html(bannerHtml).slideDown(200);
            },
            error: function() {
                $notif.empty().hide();
            }
        });
    };

    /**
     * Apply a specific discount plan to matching delivery group rows in the freight table
     */
    window.applyPlanDiscountToFreight = function(planId, isAuto = false) {
        const pickupfrom = $('input[name="pickupfrom"]:checked').val() || 
                           $("#orderDeliveryFreightForm input[name='pickupfrom']:checked").val();
        if (pickupfrom !== 'delivered') {
            return;
        }

        const rules = window.currentFreightRules || [];
        const plan = rules.find(r => String(r.id) === String(planId));
        if (!plan) {
            if (!isAuto) {
                Swal.fire('Notice', 'Plan details not found. Please refresh the rules.', 'warning');
            }
            return;
        }

        let appliedCount = 0;
        const planPlant = String(plan.plant_type || '').toLowerCase();
        const discType = plan.discount_type;
        const discVal = parseFloat(plan.discount_value) || 0;
        const maxDiscAmt = parseFloat(plan.max_discount_amount) || 0;
        const minQty = parseInt(plan.min_order_qty) || 0;
        const maxQty = parseInt(plan.max_order_qty) || 0;
        const minDist = parseFloat(plan.min_distance_km) || 0;
        const maxDist = parseFloat(plan.max_distance_km) || 0;
        const customerType = $("#customer_type").val() || 'Farmer';

        $("#freightGroupTable tbody tr.group-row").each(function() {
            const $row = $(this);
            const rowPlant = String($row.data("planttype") || '').toLowerCase();
            const rowQty = parseFloat($row.data("totalqty")) || 0;
            const rowDist = parseFloat($row.find(".f_distance_info").val()) || 0;
            const rowAmt = parseFloat($row.find(".f_amt_input").val()) || 0;

            if (rowAmt <= 0) return;

            // Check strict conditions per rule criteria for THIS specific row:
            // 1. Customer Type check
            const custOk = (plan.customer_type === 'All' || plan.customer_type === customerType);
            // 2. Plant Type check (Grafted / Nongrafted / Both)
            const plantOk = (planPlant === 'both' || planPlant === 'all' || planPlant === rowPlant || rowPlant === 'both');
            // 3. Order Qty check: rowQty >= minQty && (maxQty == 0 || rowQty <= maxQty)
            const qtyOk = (minQty === 0 || rowQty >= minQty) && (maxQty === 0 || rowQty <= maxQty);
            // 4. Distance KM check: rowDist > 0 && (maxDist == 0 || rowDist <= maxDist)
            const distOk = (rowDist > 0) && (maxDist === 0 || rowDist <= maxDist);

            if (custOk && plantOk && qtyOk && distOk) {
                $row.removeAttr("data-discount-cleared");

                // Calculate discount amount & percentage & free/payable distances
                let discountAmt = 0;
                let discPct = 0;
                let freeDist = 0;
                let payableDist = rowDist;

                if (discType === 'Percentage') {
                    if (discVal >= 100) {
                        if (minDist > 0 && rowDist > minDist) {
                            // Up to minDist is free (0 freight), remaining distance (rowDist - minDist) is chargeable
                            freeDist = minDist;
                            payableDist = Math.max(0, rowDist - minDist);
                            const freeRatio = minDist / rowDist;
                            discountAmt = rowAmt * freeRatio;
                            discPct = freeRatio * 100;
                        } else {
                            freeDist = rowDist;
                            payableDist = 0;
                            discPct = 100;
                            discountAmt = rowAmt;
                        }
                    } else {
                        freeDist = (rowDist * discVal) / 100.0;
                        payableDist = Math.max(0, rowDist - freeDist);
                        discPct = discVal;
                        discountAmt = (rowAmt * discVal) / 100.0;
                    }
                } else if (discType === 'Fixed_Amount') {
                    discountAmt = Math.min(discVal, rowAmt);
                    discPct = (rowAmt > 0) ? (discountAmt / rowAmt) * 100 : 0;
                    freeDist = (rowAmt > 0 && rowDist > 0) ? (discountAmt / rowAmt) * rowDist : 0;
                    payableDist = Math.max(0, rowDist - freeDist);
                } else if (discType === 'Per_Plant') {
                    discountAmt = Math.min(rowQty * discVal, rowAmt);
                    discPct = (rowAmt > 0) ? (discountAmt / rowAmt) * 100 : 0;
                    freeDist = (rowAmt > 0 && rowDist > 0) ? (discountAmt / rowAmt) * rowDist : 0;
                    payableDist = Math.max(0, rowDist - freeDist);
                } else if (discType === 'Per_KM') {
                    discountAmt = Math.min(rowDist * discVal, rowAmt);
                    discPct = (rowAmt > 0) ? (discountAmt / rowAmt) * 100 : 0;
                    freeDist = (rowAmt > 0 && rowDist > 0) ? (discountAmt / rowAmt) * rowDist : 0;
                    payableDist = Math.max(0, rowDist - freeDist);
                }

                if (maxDiscAmt > 0 && discountAmt > maxDiscAmt) {
                    discountAmt = maxDiscAmt;
                    discPct = (rowAmt > 0) ? (discountAmt / rowAmt) * 100 : 0;
                }

                discPct = Math.min(100, Math.max(0, Math.round(discPct * 100) / 100));
                const payable = Math.max(0, rowAmt - discountAmt);

                $row.find(".f_disc_input").val(discPct);
                $row.find(".f_payable_input").val(payable.toFixed(2));
                
                let tagText = escapeHtml(plan.plan_name);
                if (discVal >= 100 && minDist > 0 && rowDist > minDist) {
                    tagText += ` (Free up to ${minDist} KM, -Rs ${discountAmt.toFixed(2)})`;
                } else {
                    tagText += ` (-Rs ${discountAmt.toFixed(2)})`;
                }

                // Update totaldistance, freedistance, payabledistance, narration hidden inputs
                $row.find(".f_totaldistance_input").val(rowDist);
                $row.find(".f_freedistance_input").val(freeDist);
                $row.find(".f_payabledistance_input").val(payableDist);
                $row.find(".f_narration_input").val(tagText);

                const infoHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa fa-tag me-1"></i>${tagText}</span> <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1 text-decoration-none" onclick="clearRowFreightDiscount($(this).closest('tr'))" title="Clear Discount"><i class="fa fa-times-circle"></i> Clear</button>`;
                $row.find(".f_discount_info").html(infoHtml).show();

                appliedCount++;
            }
        });

        calculateGrandTotalFreight();
        renderFreightEligibilityRules(false);
        updateDiscountRemarksNarration();

        if (!isAuto) {
            if (appliedCount > 0) {
                iziToast.success({
                    title: 'Discount Applied',
                    message: `Applied "${plan.plan_name}" to ${appliedCount} eligible delivery row(s)!`,
                    position: 'topRight'
                });
            } else {
                iziToast.warning({
                    title: 'No Eligible Rows',
                    message: `No delivery row matches "${plan.plan_name}" criteria (Requires: ${plan.plant_type}, Distance &ge; ${minDist} KM, Qty &ge; ${minQty.toLocaleString()}).`,
                    position: 'topRight'
                });
            }
        }
    };

    /**
     * Clear discount for a specific plan or for all delivery rows
     */
    window.clearPlanDiscountFromFreight = function(planId) {
        const rules = window.currentFreightRules || [];
        const plan = rules.find(r => String(r.id) === String(planId));
        const planPlant = plan ? String(plan.plant_type || '').toLowerCase() : 'all';
        const minQty = plan ? (parseInt(plan.min_order_qty) || 0) : 0;
        const maxQty = plan ? (parseInt(plan.max_order_qty) || 0) : 0;
        const minDist = plan ? (parseFloat(plan.min_distance_km) || 0) : 0;
        const maxDist = plan ? (parseFloat(plan.max_distance_km) || 0) : 0;

        let clearedCount = 0;
        $("#freightGroupTable tbody tr.group-row").each(function() {
            const $row = $(this);
            const rowPlant = String($row.data("planttype") || '').toLowerCase();
            const rowQty = parseFloat($row.data("totalqty")) || 0;
            const rowDist = parseFloat($row.find(".f_distance_info").val()) || 0;
            const rowAmt = parseFloat($row.find(".f_amt_input").val()) || 0;

            let shouldClear = false;
            if (!plan || planId === 0) {
                shouldClear = true;
            } else {
                const discInfo = $row.find(".f_discount_info").text();
                const plantOk = (planPlant === 'both' || planPlant === 'all' || planPlant === rowPlant || rowPlant === 'both');
                const qtyOk = (minQty === 0 || rowQty >= minQty) && (maxQty === 0 || rowQty <= maxQty);
                const distOk = (minDist === 0 || rowDist >= minDist) && (maxDist === 0 || rowDist <= maxDist);
                
                if ((discInfo && discInfo.indexOf(plan.plan_name) !== -1) || (plantOk && qtyOk && distOk)) {
                    shouldClear = true;
                }
            }

            if (shouldClear) {
                $row.attr("data-discount-cleared", "true");
                $row.find(".f_disc_input").val("0");
                $row.find(".f_payable_input").val(rowAmt.toFixed(2));
                $row.find(".f_totaldistance_input").val(rowDist);
                $row.find(".f_freedistance_input").val(0);
                $row.find(".f_payabledistance_input").val(rowDist);
                $row.find(".f_narration_input").val("");
                $row.find(".f_discount_info").hide().empty();
                clearedCount++;
            }
        });

        calculateGrandTotalFreight();
        renderFreightEligibilityRules(false);
        updateDiscountRemarksNarration();

        iziToast.info({
            title: 'Discount Cleared',
            message: `Cleared discount for ${clearedCount} delivery row(s).`,
            position: 'topRight'
        });
    };

    /**
     * Clear discount for an individual freight table row
     */
    window.clearRowFreightDiscount = function($row) {
        const amt = parseFloat($row.find(".f_amt_input").val()) || 0;
        const rowDist = parseFloat($row.find(".f_distance_info").val()) || 0;
        $row.attr("data-discount-cleared", "true");
        $row.find(".f_disc_input").val("0");
        $row.find(".f_payable_input").val(amt.toFixed(2));
        $row.find(".f_totaldistance_input").val(rowDist);
        $row.find(".f_freedistance_input").val(0);
        $row.find(".f_payabledistance_input").val(rowDist);
        $row.find(".f_narration_input").val("");
        $row.find(".f_discount_info").hide().empty();
        calculateGrandTotalFreight();
        renderFreightEligibilityRules(false);
        updateDiscountRemarksNarration();
        iziToast.info({
            title: 'Discount Cleared',
            message: 'Discount reset to 0% for this delivery.',
            position: 'bottomRight'
        });
    };

    /**
     * Updates the Remarks textarea with narration for any applied freight discount rules
     */
    window.updateDiscountRemarksNarration = function() {
        if (window.OrderModule && window.OrderModule.hydrating) return;
        const $remarks = $('#salesOrderForm textarea[name="remarks"]');
        if ($remarks.length === 0) return;

        let discountLines = [];
        $("#freightGroupTable tbody tr.group-row").each(function(index) {
            const $row = $(this);
            const discPct = parseFloat($row.find(".f_disc_input").val()) || 0;
            const amt = parseFloat($row.find(".f_amt_input").val()) || 0;
            const payable = parseFloat($row.find(".f_payable_input").val()) || 0;
            const discAmt = amt - payable;
            
            // Extract clean badge text without the "Clear" button text
            let tagText = $row.find(".f_discount_info .badge").text().trim();
            if (!tagText) {
                tagText = $row.find(".f_discount_info").clone().children('button').remove().end().text().trim();
            }
            
            if (discPct > 0 || discAmt > 0 || tagText) {
                let lineDesc = tagText ? tagText : `Discount: ${discPct}% (-Rs ${discAmt.toFixed(2)})`;
                discountLines.push(`Row ${index + 1}: ${lineDesc}`);
            }
        });

        let currentVal = $remarks.val() || '';
        
        // Remove previous auto-generated discount lines from remarks
        let filteredLines = currentVal.split('\n').filter(function(line) {
            let l = line.trim();
            if (l.startsWith("Freight Discount:") || l.startsWith("Freight Discount Applied:") || l.startsWith("[Freight Discount]")) {
                return false;
            }
            if (/^Row \d+:\s*(All Customers|Dealer|Farmer|Discount|Free|Grafted|Non-Grafted|Plan)/i.test(l)) {
                return false;
            }
            if (l.startsWith("- Row ") && (l.includes("Discount") || l.includes("Plan") || l.includes("Free up to"))) {
                return false;
            }
            return true;
        });
        
        let baseRemark = filteredLines.join('\n').trim();

        if (discountLines.length > 0) {
            let discNarration = "";
            if (discountLines.length === 1) {
                discNarration = `Freight Discount: ${discountLines[0]}`;
            } else {
                discNarration = `Freight Discount Applied:\n` + discountLines.map(d => `- ${d}`).join('\n');
            }
            $remarks.val(baseRemark ? (baseRemark + '\n\n' + discNarration) : discNarration);
        } else {
            $remarks.val(baseRemark);
        }
    };

    /**
     * jQuery API function to fetch applicable freight discount plan from krf_freight_discount_plan
     */
    window.getFreightDiscountPlan = function(params, callback) {
        var rooturl = $('body').data('routurl') || '../';
        var cleanRoot = rooturl || '';
        if (cleanRoot.endsWith('/')) {
            cleanRoot = cleanRoot.substring(0, cleanRoot.length - 1);
        }
        
        var ajaxUrl = cleanRoot + "/freight_discount_module/modal/freight_discount_modal.php?action=calculate_discount";
        
        $.ajax({
            url: ajaxUrl,
            type: "POST",
            data: {
                customer_type: params.customer_type || $("#customer_type").val() || 'Farmer',
                plant_type: params.plant_type || 'Grafted',
                order_qty: params.order_qty || 0,
                freight_amount: params.freight_amount || 0,
                distance_km: params.distance_km || 0,
                stateid: params.stateid || $("#stateid").val() || 'ALL',
                districtid: params.districtid || $("#districtid").val() || 'ALL',
                projectid: params.projectid || $('body').data('projectid') || 1
            },
            dataType: "JSON",
            success: function(res) {
                if (typeof callback === 'function') {
                    callback(res);
                }
            },
            error: function(xhr, status, error) {
                console.error("Freight Discount API Error:", error);
                if (typeof callback === 'function') {
                    callback({ status: false, message: error });
                }
            }
        });
    };

    /**
     * Apply freight discount from plan to a specific table row
     */
    window.applyRowFreightDiscount = function($row) {
        const pickupfrom = $('input[name="pickupfrom"]:checked').val() || 
                           $("#orderDeliveryFreightForm input[name='pickupfrom']:checked").val();
        if (pickupfrom !== 'delivered') {
            return;
        }

        const amt = parseFloat($row.find(".f_amt_input").val()) || 0;
        if (amt <= 0) {
            $row.find(".f_disc_input").val("0");
            $row.find(".f_payable_input").val("0.00");
            $row.find(".f_discount_info").hide().empty();
            calculateGrandTotalFreight();
            if (!window.OrderModule || !window.OrderModule.hydrating) renderFreightEligibilityRules();
            return;
        }

        const totalQty = parseFloat($row.data("totalqty")) || 0;
        const km = parseFloat($row.find(".f_distance_info").val()) || 0;
        const plantType = $row.data("planttype") || 'Grafted';
        const customerType = $("#customer_type").val() || 'Farmer';
        const stateId = $row.data("stateid") || $("#stateid").val() || 'ALL';
        const districtId = $row.data("districtid") || $("#districtid").val() || 'ALL';
        const projectid = '<?php echo $projectid; ?>' || 1;

        getFreightDiscountPlan({
            customer_type: customerType,
            plant_type: plantType,
            order_qty: totalQty,
            freight_amount: amt,
            distance_km: km,
            stateid: stateId,
            districtid: districtId,
            projectid: projectid
        }, function(res) {
            if (res && res.status && res.data && res.data.has_discount) {
                const discPct = parseFloat(res.data.discount_percent) || 0;
                const payable = parseFloat(res.data.payable_freight) || 0;
                $row.find(".f_disc_input").val(discPct);
                $row.find(".f_payable_input").val(payable.toFixed(2));
                
                if (res.data.applied_plan) {
                    const plan = res.data.applied_plan;
                    const minDist = parseFloat(plan.min_distance_km) || 0;
                    const discVal = parseFloat(plan.discount_value) || 0;
                    let freeDist = 0;
                    let payableDist = km;
                    if (plan.discount_type === 'Percentage' && discVal >= 100) {
                        if (minDist > 0 && km > minDist) {
                            freeDist = minDist;
                            payableDist = Math.max(0, km - minDist);
                        } else {
                            freeDist = km;
                            payableDist = 0;
                        }
                    } else if (amt > 0) {
                        freeDist = (parseFloat(res.data.discount_amount) / amt) * km;
                        payableDist = Math.max(0, km - freeDist);
                    }
                    
                    let tagText = plan.plan_name;
                    if (discVal >= 100 && minDist > 0 && km > minDist) {
                        tagText += ` (Free up to ${minDist} KM, -Rs ${parseFloat(res.data.discount_amount).toFixed(2)})`;
                    } else {
                        tagText += ` (-Rs ${parseFloat(res.data.discount_amount).toFixed(2)})`;
                    }

                    $row.find(".f_totaldistance_input").val(km);
                    $row.find(".f_freedistance_input").val(freeDist);
                    $row.find(".f_payabledistance_input").val(payableDist);
                    $row.find(".f_narration_input").val(tagText);

                    const infoHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa fa-tag me-1"></i>${escapeHtml(tagText)}</span> <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1 text-decoration-none" onclick="clearRowFreightDiscount($(this).closest('tr'))" title="Clear Discount"><i class="fa fa-times-circle"></i> Clear</button>`;
                    $row.find(".f_discount_info").html(infoHtml).show();
                }
            } else {
                // No discount plan matched
                const disc = parseFloat($row.find(".f_disc_input").val()) || 0;
                const payable = amt - (amt * (disc / 100));
                $row.find(".f_payable_input").val(payable.toFixed(2));
                $row.find(".f_totaldistance_input").val(km);
                $row.find(".f_freedistance_input").val(0);
                $row.find(".f_payabledistance_input").val(km);
                $row.find(".f_narration_input").val("");
                $row.find(".f_discount_info").hide().empty();
            }
            calculateGrandTotalFreight();
            renderFreightEligibilityRules(false);
            updateDiscountRemarksNarration();
        });
    };

    function autoCalculateRowFreight($row, projectid, rooturl) {
         $("#spanratedbl").html("");
         $("#spanratedbl").addClass("d-none");
         if ($row.attr('data-user-selected') === 'true') return;

        const km = parseFloat($row.find(".f_distance_info").val()) || 0;
        const totalQty = parseFloat($row.data("totalqty")) || 0;

        if (!km || km <= 0) {
            $row.find(".auto-vehicle-badge").html('<i class="fa fa-exclamation-circle me-1"></i>No Distance').removeClass('bg-info-subtle text-info border-info-subtle bg-success-subtle text-success border-success-subtle').addClass('bg-warning-subtle text-warning border-warning-subtle');
            return;
        }

        var cleanRoot = rooturl || '';
        if (cleanRoot.endsWith('/')) {
            cleanRoot = cleanRoot.substring(0, cleanRoot.length - 1);
        }
        
        var subPath = "/krf_provisional_sale_order_edit/modal/vehicle_modal.php?action=calculateFreight";
        var ajaxUrl = cleanRoot + subPath;
        $.ajax({
            url: ajaxUrl,
            type: "POST",
            data: { km: km, projectid: projectid, total_qty: totalQty },
            dataType: "JSON",
            success: function(res) {
                if ($row.attr('data-user-selected') === 'true') return;

                if (res.status === 'success' && res.best_vehicle && parseFloat(res.recommended_charge) > 0) {
                    let charge = parseFloat(res.recommended_charge) || 0;
                    if ($('input[name="pickupfrom"]:checked').val() === "delivered" && res.calcType && res.calcType.toLowerCase() === "rate") {
                        charge = charge * 2;
                        $("#spanratedbl").html("Freight is calculated for both onward and return journeys.");
                        $("#spanratedbl").removeClass("d-none");
                    }
                    const trips = Math.max(1, parseInt(res.trips_needed, 10) || 1);
                    $row.data('freightPerTrip', charge / trips);
                    $row.find('.f_trips_input').val(trips);
                    const v = res.best_vehicle;
                    const vehicleText = v.vehiclename + (v.vehicleno ? ' (' + v.vehicleno + ')' : '');
                    
                    $row.find(".f_amt_input").val(charge.toFixed(2));
                    $row.find(".f_vehicle_info").val(vehicleText);
                    
                    let badgeText = `<i class="fa fa-truck me-1"></i>${v.vehiclename}`;
                    if (res.trips_needed > 1) {
                        badgeText += ` (x${res.trips_needed} trips)`;
                    }
                    $row.find(".auto-vehicle-badge").html(badgeText).removeClass('bg-info-subtle text-info border-info-subtle bg-warning-subtle text-warning border-warning-subtle').addClass('bg-success-subtle text-success border-success-subtle');

                    applyRowFreightDiscount($row);
                } else if (res.status === 'success' && res.suggestions && res.suggestions.some(v => parseFloat(v.charge) > 0)) {
                    const validSug = res.suggestions.filter(v => parseFloat(v.charge) > 0);
                    const v = validSug[0];
                    const charge = parseFloat(v.charge) || 0;
                    $row.data('freightPerTrip', charge);
                    $row.find('.f_trips_input').val(1);
                    $row.find(".f_amt_input").val(charge.toFixed(2));
                    $row.find(".f_vehicle_info").val(v.vehiclename);
                    $row.find(".auto-vehicle-badge").html(`<i class="fa fa-truck me-1"></i>${v.vehiclename}`).removeClass('bg-info-subtle text-info border-info-subtle bg-warning-subtle text-warning border-warning-subtle').addClass('bg-primary-subtle text-primary border-primary-subtle');

                    applyRowFreightDiscount($row);
                } else {
                    $row.find(".auto-vehicle-badge").html('<i class="fa fa-exclamation-triangle me-1"></i>No rate defined').removeClass('bg-info-subtle text-info border-info-subtle bg-success-subtle text-success border-success-subtle').addClass('bg-warning-subtle text-warning border-warning-subtle');
                    applyRowFreightDiscount($row);
                }
            },
            error: function() {
                $row.find(".auto-vehicle-badge").html('<i class="fa fa-times me-1"></i>Error').removeClass('bg-info-subtle text-info border-info-subtle bg-success-subtle text-success border-success-subtle').addClass('bg-danger-subtle text-danger border-danger-subtle');
            }
        });
    }

    window.openFreightRowCalculator = function(btn, distance, totalQty) {
        const $row = $(btn).closest("tr");
        const projectid = '<?php echo $projectid; ?>';
        const rooturl = '<?php echo $rooturl; ?>';

        openFreightCalculator(distance, projectid, rooturl, function(data) {
            let chargeVal = parseFloat(data.charge || 0);
            const isDelivered = ($('input[name="pickupfrom"]:checked').val() || $("#orderDeliveryFreightForm input[name='pickupfrom']:checked").val() || 'delivered') === "delivered";
            const isRate = (data.calculationtype && data.calculationtype.toLowerCase() === "rate") || (data.calcType && data.calcType.toLowerCase() === "rate");

            if (isDelivered && isRate) {
                $("#spanratedbl").html("Freight is calculated for both onward and return journeys.");
                $("#spanratedbl").removeClass("d-none");
            } else {
                $("#spanratedbl").html("").addClass("d-none");
            }

            const trips = Math.max(1, parseInt(data.trips_needed, 10) || 1);
            $row.data('freightPerTrip', chargeVal / trips);
            $row.find('.f_trips_input').val(trips);
            $row.attr('data-user-selected', 'true');
            $row.find(".f_amt_input").val(chargeVal.toFixed(2)).trigger('input').trigger('change');
            $row.find(".f_vehicle_info").val(data.vehiclename || '');
            $row.find(".f_distance_info").val(data.calc_km || '');
            $row.find(".auto-vehicle-badge").html(`<i class="fa fa-truck me-1"></i>${data.vehiclename} (x${data.trips_needed || 1} trips)`).removeClass('bg-info-subtle text-info border-info-subtle bg-warning-subtle text-warning border-warning-subtle').addClass('bg-success-subtle text-success border-success-subtle');
            
            applyRowFreightDiscount($row);

            iziToast.success({ 
                title: 'Estimated', 
                message: `Freight calculated for ${data.vehiclename} (${data.calc_km} KM): Rs ${chargeVal.toFixed(2)}`,
                position: 'bottomRight'
            });
        }, totalQty);
    }

    $(document).on('click', '.f_recalculate_trips', function() {
        const $row = $(this).closest('tr');
        const input = $row.find('.f_trips_input')[0];
        if (!input || !input.checkValidity()) {
            if (input) input.reportValidity();
            return;
        }
        const perTrip = Number($row.data('freightPerTrip'));
        if (!Number.isFinite(perTrip)) {
            window.openFreightRowCalculator(this, Number($row.find('.f_distance_info').val()) || 0, Number($row.data('totalqty')) || 0);
            return;
        }
        const trips = Number(input.value);
        $row.attr('data-user-selected', 'true');
        $row.find('.f_amt_input').val((perTrip * trips).toFixed(2));
        const name = $row.find('.f_vehicle_info').val() || 'Vehicle';
        $row.find('.auto-vehicle-badge').text(name + ' (x' + trips + ' trips)');
        applyRowFreightDiscount($row);
    });

    // Freight Table Calculations
    $(document).on("input change keyup blur", ".f_amt_input", function() {
        const $row = $(this).closest("tr");
        applyRowFreightDiscount($row);
    });

    $(document).on("input change keyup blur", ".f_disc_input", function() {
        const loginRoleId = parseInt($('body').data('loginroleid') || $('#hidden_loginroleid').val() || '<?php echo (int)($loginroleid ?? ($_SESSION['loginroleid'] ?? 0)); ?>') || 0;
        if (loginRoleId > 3 && $(this).prop('readonly')) {
            return;
        }
        const $row = $(this).closest("tr");
        const amt = parseFloat($row.find(".f_amt_input").val()) || 0;
        let disc = parseFloat($row.find(".f_disc_input").val()) || 0;
        if (disc > 100) {
            disc = 100;
            $row.find(".f_disc_input").val(100);
        }
        
        const payable = amt - (amt * (disc / 100));
        $row.find(".f_payable_input").val(payable.toFixed(2));

        calculateGrandTotalFreight();
    });

    window.calculateGrandTotalFreight = function() {
        let totalAmt = 0;
        let totalPayable = 0;

        $(".f_amt_input").each(function() { totalAmt += parseFloat($(this).val()) || 0; });
        $(".f_payable_input").each(function() { totalPayable += parseFloat($(this).val()) || 0; });

        $("#total_freight_amt").text(totalAmt.toFixed(2));
        $("#total_payable_freight_amt").text(totalPayable.toFixed(2));

        // Dynamically update the overall order payable grand total
        let itemsPayable = parseFloat($("#grandtotalamount").text()) || 0;
        if (!itemsPayable) {
            $(".payableamount_td").each(function() {
                itemsPayable += parseFloat($(this).val()) || 0;
            });
        }
        let finalGrandTotal = itemsPayable + totalPayable;
        $("#grandtotalamount_final").text(finalGrandTotal.toFixed(2));
    };

    // 📍 Auto-populate Full Address from individual inputs in real-time
    $(document).on("input change", "input[name='delivery_state[]'], input[name='delivery_district[]'], input[name='delivery_city[]'], input[name='delivery_pincode[]']", function() {
        const $row = $(this).closest("tr");
        const state = $row.find("input[name='delivery_state[]']").val().trim();
        const district = $row.find("input[name='delivery_district[]']").val().trim();
        const city = $row.find("input[name='delivery_city[]']").val().trim();
        const pincode = $row.find("input[name='delivery_pincode[]']").val().trim();

        let addressParts = [];
        // Following the sequence: State, District, City, Pincode
        if (state) addressParts.push(state);
        if (district) addressParts.push(district);
        if (city) addressParts.push(city);
        if (pincode) addressParts.push(pincode);
        
        let fullAddress = addressParts.join(", ");
        $row.find("input[name='delivery_fulladdress[]']").val(fullAddress);
    });
  });
