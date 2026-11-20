
function getEffectiveLoginRoleId() {
  if (typeof window.CURRENT_USER_ROLE_ID !== 'undefined') {
    let r = parseInt(window.CURRENT_USER_ROLE_ID, 10);
    if (!isNaN(r)) return r;
  }
  if (window.OrderModule && window.OrderModule.config && typeof window.OrderModule.config.loginroleid !== 'undefined') {
    let r = parseInt(window.OrderModule.config.loginroleid, 10);
    if (!isNaN(r) && r > 0) return r;
  }
  let bodyRole = $('body').data('loginroleid');
  if (bodyRole !== undefined && bodyRole !== null && bodyRole !== '') {
    let r = parseInt(bodyRole, 10);
    if (!isNaN(r)) return r;
  }
  let hiddenRole = $('#hidden_loginroleid').val();
  if (hiddenRole !== undefined && hiddenRole !== null && hiddenRole !== '') {
    let r = parseInt(hiddenRole, 10);
    if (!isNaN(r)) return r;
  }
  return <?php echo intval($loginroleid ?? 99); ?>;
}
window.getEffectiveLoginRoleId = getEffectiveLoginRoleId;

function cleanNumber(val) {
  if (typeof val === 'number') return isNaN(val) ? 0 : val;
  if (!val) return 0;
  let str = String(val).replace(/,/g, '').trim();
  let num = parseFloat(str);
  return isNaN(num) ? 0 : num;
}
window.cleanNumber = cleanNumber;

   function returnFromOrderEditor() {
    if(!window.OrderModule.isConfirmed() && window.poEnquiryReturnUrl){window.location.href=window.poEnquiryReturnUrl;return;}
    const current = new URL(window.location.href);
    const followup = !window.OrderModule.isConfirmed() &&
        current.searchParams.get('return_to') === 'draft_followup';

    const root = String($('body').data('routurl') || '')
        .replace(/\/?$/, '/');

    const path = followup
        ? (current.searchParams.get('followup_admin') === '1'
            ? 'krf_order_followup/provisional_order_followup_admin.php'
            : 'krf_order_followup/provisional_order_followup.php')
        : (window.OrderModule.isConfirmed()
            ? (current.searchParams.get('entry_mode') === 'admin_edit'
                ? 'krf-orderandsales/confirmed_order_modification_admin.php'
                : 'krf-orderandsales/finalsaleorder_review_new.php')
            : 'krf-orderandsales/provisionalsaleorder_review_new.php');

    const target = new URL(root + path, window.location.origin);

    target.searchParams.set(
        'k',
        current.searchParams.get('k') ||
        String($('body').data('key') || '')
    );
    target.searchParams.set(
        'mdid',
        current.searchParams.get('mdid') || ''
    );
    target.searchParams.set('iframe', 'true');
    if (followup) target.searchParams.set('open_psono', $('#edit_psono').val() || current.searchParams.get('orderno') || '');

    window.location.href = target.toString();
}

  // mobileno
  // clientname
  // Set today's date as min 
  document.addEventListener("DOMContentLoaded", function () {
    let today = new Date().toISOString().split("T")[0];
    let loginRoleId = parseInt($("body").data("loginroleid") || $("#hidden_loginroleid").val() || "<?php echo intval($loginroleid ?? 0); ?>") || 0;
    document.querySelectorAll(".expecteddelivery").forEach(function (input) {
      if (!window.OrderModule.isEdit()) input.setAttribute("min", today);
      if (loginRoleId > 1) {
        input.setAttribute("readonly", "readonly");
        input.style.pointerEvents = "none";
        input.style.backgroundColor = "#f8fafc";
        input.setAttribute("tabindex", "-1");
      }
    });
  });
  $(document).on("click", "#isadddeliveryaddress", function () {
    $("#adddeliveryaddressModal").modal("show");
    // $("#adddeliveryaddressModal").modal("hide");
    // if($(this).is(":checked")){
    // }
  });


  // Global function called from the Iframe in locatioinaddressget_api.php
  window.appendAddressRow = function (data) {
    if (!data || !data.full_address) {
      Swal.fire('Incomplete Data', 'Delivery address is required', 'warning');
      return false;
    }
    // console.log(data);
    const latinput = data.lat || "";
    const longinput = data.lon || "";
    const statename = data.state || "";
    const districtname = data.district || "";
    const cityname = data.city || "";
    const pincode = data.pincode || "";
    const fulladdress = data.full_address || "";
    const locationIds = findStateDistrictIds(statename, districtname);

    const stateid = locationIds.stateid;
    const districtid = locationIds.districtid;


    let productOptionsHtml = '<option value="all">All Products</option>';
    $("#provisionorderitemsTable tbody tr").each(function () {
      const $productSelect = $(this).find("select[name='productname[]'], select[name='productname']");
      if (!$productSelect.length) return;
      const productId = $productSelect.val();
      if (!productId) return;
      const productText = $productSelect.find("option:selected").text();
      // Prevent duplicate options
      if (!productOptionsHtml.includes(`value="${productId}"`)) {
        productOptionsHtml += `<option value="${productId}">${productText}</option>`;
      }
    });

    const $newRow = $(`
        <tr class="align-middle">
            <td>
                <select name="delivery_productid[]" class="form-select select2 delivery_product_select">${productOptionsHtml}</select>
            </td>
            <td>
                <input type="text" name="delivery_qty[]" class="form-control delivery_qty_input" placeholder="0" onKeyPress="return isFloatNumber(event,this);" required />
            </td>
            <td>
                <input type="text" name="delivery_distance[]" class="form-control delivery_distance_input" value="${data.calc_km || data.distance_km || 0}" onKeyPress="return isFloatNumber(event,this);" />
            </td>
            <td class="d-none">
                <input type="hidden" name="delivery_latlong[]" value="${latinput},${longinput}" />
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
                         value="${statename}">

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
                       value="${districtname}">

                <input type="hidden"
                       class="delivery_district_id"
                       name="delivery_district_id[]"
                       value="${districtid}">
            </td>
            <td>
                <input type="text" name="delivery_city[]" class="form-control" value="${cityname}">
            </td>
            <td>
                <input type="text" name="delivery_pincode[]" class="form-control" value="${pincode}">
            </td>
            <td>
                <input type="text" name="delivery_fulladdress[]" class="form-control" value="${fulladdress}">
            </td>
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

    // Append the row immediately to the table
    $("#deliveryaddresstbl tbody").append($newRow);

    // Initialize Select2 for the new product dropdown
    $newRow.find('.select2').select2({
      width: '100%',
      placeholder: 'Select Product'
    });

    // SweetAlert confirmation for adding more addresses
    Swal.fire({
      title: "Address Added Successfully!",
      text: "Would you like to add another delivery address to this order?",
      icon: "success",
      showCancelButton: true,
      confirmButtonText: "Yes, Add More",
      cancelButtonText: "No, I'm Done",
      customClass: {
        confirmButton: 'btn btn-primary px-4 me-2',
        cancelButton: 'btn btn-outline-secondary px-4'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        // 1. Clear parent window coordinate inputs
        $("#latlonginput, #latinput, #longinput").val("");
        
        // 2. Reset the form inside the Iframe
        const iframe = document.getElementById("location-iframe");
        if (iframe && iframe.contentWindow && typeof iframe.contentWindow.resetForm === 'function') {
          iframe.contentWindow.resetForm();
        }

        // 3. Optional: Scroll the modal back to top if needed
        $("#adddeliveryaddressModal .modal-body").animate({ scrollTop: 0 }, 300);
      } else {
        $("#adddeliveryaddressModal").modal("hide");
        Swal.fire({
          title: "All Set!",
          text: "Delivery details have been updated.",
          icon: "info",
          timer: 1000,
          showConfirmButton: false
        });
      }
    });
  };


  // Live Search & Validation for Mobile Number
  window.validateMobileNumber = function() {
    const $mobileInput = $('#mobileno');
    const $msgDiv = $('#mobileno-validation-msg');
    const val = $mobileInput.val().trim();
    const mobileRegex = /^[6-9]\d{9}$/;

    if (val === '') {
      $mobileInput.removeClass('is-invalid is-valid');
      $msgDiv.empty().removeClass('text-danger text-success');
      return false;
    }

    if (mobileRegex.test(val)) {
      $mobileInput.removeClass('is-invalid').addClass('is-valid');
      $msgDiv.removeClass('text-danger').addClass('text-success')
             .html('<i class="fa fa-check-circle me-1"></i> Valid mobile number');
      return true;
    } else {
      $mobileInput.removeClass('is-valid').addClass('is-invalid');
      $msgDiv.removeClass('text-success').addClass('text-danger');
      if (val.length < 10) {
        $msgDiv.html('<i class="fa fa-exclamation-triangle me-1"></i> Mobile number must be 10 digits (entered: ' + val.length + ')');
      } else if (!/^[6-9]/.test(val)) {
        $msgDiv.html('<i class="fa fa-exclamation-triangle me-1"></i> Mobile number must start with 6, 7, 8, or 9');
      } else {
        $msgDiv.html('<i class="fa fa-exclamation-triangle me-1"></i> Invalid mobile number');
      }
      return false;
    }
  };

  $(document).ready(function () {
    const $mobileInput = $('#mobileno');
    const $resultsDiv = $('#mobileno-results');
    const $nameInput = $('#clientname');
    const $nameResultsDiv = $('#clientname-results');
    let searchTimeout;
    let nameSearchTimeout;

    function renderClientResults(data, $targetDiv) {
      if (data && data.length > 0) {
        let html = '';
        data.forEach(function (item) {
          html += `
            <div class="search-result-item"
               data-clientid="${escapeAttr(item.client_id || '')}"
               data-clientname="${escapeAttr(item.client_name || '')}"
               data-mobileno="${escapeAttr(item.phones || '')}"
               data-emailaddress="${escapeAttr(item.client_email || '')}"
               data-address="${escapeAttr(item.invoicing_address || '')}"
               data-statename="${escapeAttr(item.statename || '')}"
               data-stateid="${escapeAttr(item.stateid || '')}"
               data-districtname="${escapeAttr(item.districtname || '')}"
               data-districtid="${escapeAttr(item.districtid || '')}"
               data-tahseel="${escapeAttr(item.tahseel || '')}"
               data-cityname="${escapeAttr(item.cityname || '')}"
               data-isdealer="${escapeAttr(item.is_dealer || '0')}"
               data-customertype="${(parseInt(item.is_dealer) === 1 || item.is_dealer === '1') ? 'Dealer' : 'Farmer'}">
            <div class="search-result-name">${escapeHtml(item.client_name || '')}</div>
            <div class="search-result-meta">
              <span><i class="fa fa-phone"></i> ${item.phones || 'N/A'}</span>
              <span><i class="fa fa-map-marker-alt"></i> ${item.districtname || item.cityname || item.statename || ''}</span>
            </div>
            <div class="search-result-address">${item.invoicing_address || ''}</div>
          </div>`;
        });
        $targetDiv.html(html).show();
      } else {
        $targetDiv.hide().empty();
      }
    }

    function setupKeyboardNav($input, $container) {
      $input.on('keydown', function (e) {
        const $items = $container.find('.search-result-item');
        let $active = $container.find('.search-result-item.active');

        if (e.key === "ArrowDown") {
          e.preventDefault();
          if (!$items.length) return;
          if (!$active.length) {
            $items.first().addClass('active');
          } else {
            $active.removeClass('active').next().addClass('active');
            if (!$container.find('.search-result-item.active').length) {
              $items.first().addClass('active');
            }
          }
          const newActive = $container.find('.search-result-item.active');
          if (newActive.length) {
            newActive[0].scrollIntoView({ block: 'nearest' });
          }
        } else if (e.key === "ArrowUp") {
          e.preventDefault();
          if (!$items.length) return;
          if (!$active.length) {
            $items.last().addClass('active');
          } else {
            $active.removeClass('active').prev().addClass('active');
            if (!$container.find('.search-result-item.active').length) {
              $items.last().addClass('active');
            }
          }
          const newActive = $container.find('.search-result-item.active');
          if (newActive.length) {
            newActive[0].scrollIntoView({ block: 'nearest' });
          }
        } else if (e.key === "Enter") {
          if ($active.length) {
            e.preventDefault();
            $active.trigger('click');
          }
        } else if (e.key === "Escape") {
          $container.hide();
        }
      });
    }

    setupKeyboardNav($mobileInput, $resultsDiv);
    setupKeyboardNav($nameInput, $nameResultsDiv);

    $mobileInput.on('input keyup change blur', function() {
      window.validateMobileNumber();
    });

    // Disable browser native autofill suggestions dynamically
    $('#salesOrderForm input, #salesOrderForm textarea').attr({
      'autocomplete': 'one-time-code',
      'autocorrect': 'off',
      'autocapitalize': 'off',
      'spellcheck': 'false'
    });

    // Live search by Mobile Number
    $mobileInput.on('input', function () {
      const term = $(this).val().trim();
      clearTimeout(searchTimeout);

      if (term.length < 3) {
        $resultsDiv.hide().empty();
        return;
      }

      searchTimeout = setTimeout(function () {
        const companyid = $("body").data("companyid");
        const subcompanyid = $("body").data("subcompanyid");
        const projectid = $("body").data("projectid");

        $.ajax({
          url: "modal/client_modal.php?action=clientDetailsFrommaster",
          type: 'POST',
          dataType: 'json',
          data: {
            companyid: companyid,
            subcompanyid: subcompanyid,
            projectid: projectid,
            sortby: 'mobileno',
            sortbyvalue: term
          },
          success: function (data) {
            renderClientResults(data, $resultsDiv);
          }
        });
      }, 300);
    });

    // Live search by Client Name
    $nameInput.on('input', function () {
      const term = $(this).val().trim();
      clearTimeout(nameSearchTimeout);

      if (term.length < 2) {
        $nameResultsDiv.hide().empty();
        return;
      }

      nameSearchTimeout = setTimeout(function () {
        const companyid = $("body").data("companyid");
        const subcompanyid = $("body").data("subcompanyid");
        const projectid = $("body").data("projectid");

        $.ajax({
          url: "modal/client_modal.php?action=clientDetailsFrommaster",
          type: 'POST',
          dataType: 'json',
          data: {
            companyid: companyid,
            subcompanyid: subcompanyid,
            projectid: projectid,
            sortby: 'searchclientname',
            sortbyvalue: term
          },
          success: function (data) {
            renderClientResults(data, $nameResultsDiv);
          }
        });
      }, 300);
    });

    function escapeAttr(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    }

    // Handle selection from custom results
    $(document).on('click', '.search-result-item', function () { 
      const $item = $(this);
      handleExistingClientSelection($item[0]);
      if (typeof window.checkClientDraftOrders === 'function') window.checkClientDraftOrders();
      $resultsDiv.hide().empty();
      $nameResultsDiv.hide().empty();
    });

    // Close results when clicking outside
    $(document).on('click', function (e) {
      if (!$(e.target).closest('.position-relative').length) {
        $resultsDiv.hide();
        $nameResultsDiv.hide();
      }
    });
  });

  // Remove old blur handlers and results grid generation
  // (Removed #mobileno blur and #clientname blur logic)

  // ===============================
  // Click Event
  // ===============================
  $(document).on("change", "input[name='existclient']", function () {
    $(".client-selection-card").removeClass("selected");
    if ($(this).is(":checked")) {
      $(this).closest(".client-selection-card").addClass("selected");
    }
    handleExistingClientSelection(this);
      if (typeof window.checkClientDraftOrders === 'function') window.checkClientDraftOrders();
  });

  // ===============================
  // Auto Apply On Page Load / Modal Open
  // ===============================
  $(document).ready(function () {
    applyCheckedExistingClient();
  });

  // ===============================
  // Remove Delivery Address Row
  // ===============================
  $(document).on("click", ".rmdeliveryaddbtn", function () {
    $(this).closest("tr").remove();
  });

  var globalProductOptions = "";
  GetProductListFrommaster_callback(function (res) {
    if (res.length > 0) {
      globalProductOptions = '<option value="" selected disabled><?php echo _t('select'); ?></option>';
      for (var i = 0; i < res.length; i++) {
        var rawName = res[i].ProductName || '';
        var displayName = rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
        window.allProductsMap = window.allProductsMap || {};
        var isGrafted = res[i].IsGrafted || (res[i].sku && res[i].sku.toUpperCase().includes('NONGRAFTED') ? 'NonGrafted' : (res[i].sku && res[i].sku.toUpperCase().includes('GRAFTED') ? 'Grafted' : (rawName.match(/^Grafted/i) ? 'Grafted' : 'NonGrafted')));
        window.allProductsMap[res[i].ProductID] = res[i];
        window.allProductsMap[res[i].ProductID].isGrafted = isGrafted;
        globalProductOptions += '<option value="' + res[i].ProductID + '" data-priceofproduct="' + res[i].PriceOfProduct + '" data-isgrafted="' + isGrafted + '">' + displayName + '</option>';
      }
      $(".productname").each(function () {
        const selectedProduct = $(this).val();
        $(this).html(globalProductOptions);
        if (selectedProduct) {
            if (!$(this).find('option').filter(function () { return String(this.value) === String(selectedProduct); }).length) {
                const original = $(this).closest('tr').data('edit-original');
                $(this).append(new Option(original ? original.product_name : selectedProduct, selectedProduct));
            }
            $(this).val(selectedProduct).trigger('change.select2');
        }
      });

      // Initialize Select2 on page load
      $('.productname.select2').select2({
        placeholder: "Select Product",
        allowClear: true,
        width: '100%'
      });
    }
  });

  $(document).ready(function () {
    let initLoginRoleId = parseInt($("body").data("loginroleid") || $("#hidden_loginroleid").val() || "<?php echo intval($loginroleid ?? 0); ?>") || 0;
    if (initLoginRoleId > 2) {
      $("#provisionorderitemsTable tbody .rate_td").prop("readonly", true).css("background-color", "#f8fafc");
    } else {
      $("#provisionorderitemsTable tbody .rate_td").prop("readonly", false).css("background-color", "");
    }

    // Recalculate total when a row is added
    $("#addRow").click(function () {
      var table = $('#provisionorderitemsTable');
      var $tbody = table.find('tbody');

      // Validation: Check if the last row is filled before adding a new one
      var $lastRow = $tbody.find('tr:last');
      if ($lastRow.length > 0) {
        var lastProduct = $lastRow.find('.productname').val();
        var lastRate = $lastRow.find('.rate_td').val();
        var lastQty = $lastRow.find('.qty_td').val();

        if (!lastProduct || !lastRate || parseFloat(lastQty) <= 0) {
          iziToast.warning({
            title: 'Incomplete Row',
            message: 'Please select a Product and enter Rate & Quantity before adding a new item.',
            position: 'topRight'
          });
          return;
        }
      }

      var $firstRow = $tbody.find('tr:first');
      if (!$firstRow.length && window.OrderModule) $firstRow = window.OrderModule.itemTemplate;

      // Clone the first row but remove Select2 artifacts first
      var $newRow = $firstRow.clone();

      // Clean up the cloned row
      $newRow.removeData('edit-original');
      $newRow.removeClass('row_ignore'); // Ensure new row isn't ignored
      $newRow.find('.select2-container').remove(); // Remove existing Select2 containers
      $newRow.find('.select2').removeClass('select2-hidden-accessible'); // Reset select2 class
      $newRow.find('select').attr('data-select2-id', null);
      $newRow.find('option').attr('data-select2-id', null);

      // Reset values
      $newRow.find('input').val('');
      $newRow.find('select').val('');

      // Ensure product options are present
      if (globalProductOptions) {
        $newRow.find('.productname').html(globalProductOptions);
      }

      // Set min date for delivery
      let today = new Date().toISOString().split("T")[0];
      $newRow.find(".expecteddelivery").attr("min", today);
      let loginRoleId = parseInt($("body").data("loginroleid") || $("#hidden_loginroleid").val() || "<?php echo intval($loginroleid ?? 0); ?>") || 0;
      if (loginRoleId > 1) {
        $newRow.find(".expecteddelivery").attr("readonly", true).css({"pointer-events": "none", "background-color": "#f8fafc"}).attr("tabindex", "-1");
      }
      if (loginRoleId > 2) {
        $newRow.find(".rate_td").prop("readonly", true).css("background-color", "#f8fafc");
      } else {
        $newRow.find(".rate_td").prop("readonly", false).css("background-color", "");
      }

      // Append and Initialize
      $tbody.append($newRow);

      // Re-initialize Select2 for the new row specifically
      $newRow.find('.productname.select2').select2({
        placeholder: "Select Product",
        allowClear: true,
        width: '100%'
      });

      loginRoleId = parseInt($("#hidden_loginroleid").val() || 0, 10);
      $(".distprt_td").prop("readonly", loginRoleId > 3);
      $(".distamt_td").prop("readonly", loginRoleId > 3);
      Invoicegrandtotalgstamont('totalamount', 'gsttotalamount', 'ttldiscountamount', 'grandtotalamount', 'payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'provisionorderitemsTable');
    });

    
    // Enter opens allocation only for a positive quantity.
    $("#provisionorderitemsTable").on("keydown", ".qty_td, .qtycal", function (e) {
      if (e.key !== "Enter") return;
      e.preventDefault();
      const qty = Number($(this).val());
      if (!Number.isFinite(qty) || qty <= 0 || e.repeat) return;
      if ($(this).closest("tr").data("edit-qty-pending")) return;
      $(this).data("edit-enter-allocation", true);
      this.blur();
    });

    // Prevent default enter key on other table inputs/selects
    $("#provisionorderitemsTable").on("keydown", "input:not(.qty_td):not(.qtycal), select", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
      }
    });

    // Recalculate total when a row is removed
    $(document).on("click", ".removeRow", function () {
      let productid = $(this).closest("tr").find(".productname").val()
      $(this).closest("tr").remove();
      Invoicegrandtotalgstamont('totalamount', 'gsttotalamount', 'ttldiscountamount', 'grandtotalamount', 'payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'provisionorderitemsTable');
    });

        $(document).on("change", ".productname", function () {
      if (window.OrderModule && window.OrderModule.hydrating) return;
      let row = $(this).closest("tr");
      row.find(".expecteddelivery").val('');
      row.find(".isreadytosale").val('');
      row.find(".stockdbid").val('');
      let selected = row.find(".productname option:selected");
      let price = selected.length ? parseFloat(selected.data("priceofproduct")) : 0;
      row.find("input.ratecal").val(price).trigger("input");

      let productId = $(this).val();
      if (productId) {
        fetchClientLastPurchaseHistory(productId, row);
        fetchAndApplyClientDiscount(productId, row, true);
      } else {
        $("#product-last-purchase-suggestion").slideUp(200).empty();
        $("#product-discount-suggestion").slideUp(200).empty();
      }
    });

    $(document).on("select2:select", "#provisionorderitemsTable .productname", function () {
      if (window.OrderModule && window.OrderModule.hydrating) return;
      let row = $(this).closest("tr");
      if ($(this).val()) {
        setTimeout(function () {
          row.find(".qty_td").focus().select();
        }, 100);
      }
    });

    $(document).on("input change", ".qtycal", function () {
      if (window.OrderModule && window.OrderModule.hydrating) return;
      let row = $(this).closest("tr");
      let productId = row.find(".productname").val();
      if (productId) {
        fetchAndApplyClientDiscount(productId, row, true);
      }
    });

  // Safe HTML escaper
  function escapeClientHtml(value) {
    return $('<div>').text(value == null ? '' : value).html();
  }

  // Dedicated function to fetch and display last purchase rate & date for the client
  function fetchClientLastPurchaseHistory(productId, $row) {
    const $suggestionDiv = $("#product-last-purchase-suggestion");
    if (!productId) {
      $suggestionDiv.slideUp(200).empty();
      return;
    }

    const clientId = $("#clientid").val() || "";
    const clientMobile = $("#mobileno").val() || "";
    const clientName = $("#clientname").val() || "Client";
    const routurl = $("body").data("routurl") || "../";
    const projectid = $("body").data("projectid") || "";

    if (!clientId && !clientMobile) {
      $suggestionDiv.slideUp(200).empty();
      return;
    }

    const rowIndex = $row ? $row.index() : 0;
    let masterPrice = 0;
    if ($row && $row.length) {
      let selectedOpt = $row.find(".productname option:selected");
      masterPrice = selectedOpt.length ? parseFloat(selectedOpt.data("priceofproduct")) : 0;
    }

    $.ajax({
      url: routurl + "krf_provisional_sale_order_edit/modal/product_modal.php?action=GetClientLastPurchaseHistory",
      type: "POST",
      dataType: "json",
      data: {
        clientid: clientId,
        mobileno: clientMobile,
        productid: productId,
        projectid: projectid
      },
      success: function (res) {
        if (res && res.status === "success" && res.data) {
          const dataList = Array.isArray(res.data) ? res.data : [res.data];
          if (dataList.length === 0) {
            $suggestionDiv.slideUp(200).empty();
            return;
          }

          const safeClientName = escapeClientHtml(clientName);
          const formattedMasterRate = masterPrice > 0 ? parseFloat(masterPrice).toFixed(2) : null;

          let entriesHtml = dataList.map((d, index) => {
            const formattedRate = parseFloat(d.rate).toFixed(2);
            const safeOrderType = escapeClientHtml(d.order_type || 'Order');
            const safeOrderNo = escapeClientHtml(d.order_no || '');
            const safeSono = escapeClientHtml(d.sono || '');
            const safePsono = escapeClientHtml(d.psono || '');
            const previewTitle = safeSono 
              ? `Click to preview Final Sale Invoice (${safeSono})` 
              : `Click to preview ${safeOrderType} (${safeOrderNo})`;

            return `
              <div class="d-flex flex-wrap align-items-center justify-content-between p-2 px-3 rounded-2 bg-white border shadow-2xs gap-2" style="border-color: #cbd5e1 !important;">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <span class="badge bg-light text-dark border px-2 py-1 fw-bold" style="font-size: 0.78rem;">#${index + 1}</span>
                  <span class="badge bg-soft-success text-success border border-success-subtle px-2 py-1" style="font-size: 0.85rem;"><i class="fa fa-tag me-1"></i> Rate: <strong style="font-size: 0.92rem;">₹${formattedRate}</strong></span>
                  <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.82rem;"><i class="fa fa-calendar-alt text-primary me-1"></i> Date: <strong>${d.order_date || 'N/A'}</strong></span>
                  <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.82rem;"><i class="fa fa-boxes text-info me-1"></i> Qty: <strong>${d.quantity || 'N/A'}</strong></span>
                  ${d.order_no ? `
                    <span class="badge bg-light text-secondary border px-2 py-1 preview-invoice-badge" 
                          style="font-size: 0.82rem; cursor: pointer; transition: all 0.2s ease; user-select: none;" 
                          title="${previewTitle}"
                          onclick="previewSaleOrderInvoice('${safeOrderType}', '${safeOrderNo}', '${safeSono}', '${safePsono}')"
                          onmouseover="this.style.backgroundColor='#e0f2fe'; this.style.color='#0369a1'; this.style.borderColor='#7dd3fc';"
                          onmouseout="this.style.backgroundColor=''; this.style.color=''; this.style.borderColor='';">
                      <i class="fa fa-receipt text-primary me-1"></i> ${safeOrderType}: <strong class="text-primary text-decoration-underline">${safeOrderNo}</strong>
                      <i class="fa fa-external-link-alt ms-1 text-muted" style="font-size: 0.72rem;"></i>
                    </span>` : ''}
                </div>
                <div>
                  <button type="button" class="btn btn-sm btn-primary shadow-xs fw-bold d-flex align-items-center gap-1" onclick="applyLastPurchaseRate(${d.rate}, ${rowIndex})" style="border-radius: 6px; padding: 4px 12px; font-size: 0.82rem;">
                    <i class="fa fa-check-circle"></i> Apply Rate (₹${formattedRate})
                  </button>
                </div>
              </div>
            `;
          }).join('');

          const suggestionHtml = `
            <div class="alert alert-info border-0 shadow-sm p-3 rounded-3" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd !important; animation: fadeIn 0.3s ease-out;">
              <div class="d-flex flex-wrap align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #bae6fd !important; gap: 8px;">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle p-1 d-flex align-items-center justify-content-center bg-white shadow-sm flex-shrink-0" style="width: 32px; height: 32px; border: 1px solid #7dd3fc;">
                    <i class="fa fa-history text-primary" style="font-size: 0.95rem;"></i>
                  </div>
                  <div>
                    <div class="fw-bold" style="color: #0369a1; font-size: 0.92rem;">
                      <i class="fa fa-lightbulb text-warning me-1"></i> Last Sale History for <span class="text-dark">${safeClientName}</span>:
                      <span class="badge bg-white text-info border ms-1" style="font-size: 0.78rem;">${dataList.length} ${dataList.length === 1 ? 'record' : 'recent records'}</span>
                    </div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  ${formattedMasterRate ? `
                    <span class="badge bg-white text-dark border py-1 px-2 d-none d-sm-inline-block" style="font-size: 0.82rem;">
                      <i class="fa fa-database text-primary me-1"></i> Master Rate: <strong class="text-primary">₹${formattedMasterRate}</strong>
                    </span>
                    <button type="button" class="btn btn-sm shadow-xs fw-bold d-flex align-items-center gap-1" onclick="applyLastPurchaseRate(${masterPrice}, ${rowIndex})" style="border-radius: 6px; padding: 4px 10px; font-size: 0.8rem; background: #e0f2fe; border: 1px solid #7dd3fc; color: #0369a1; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#0284c7'; this.style.color='#ffffff'; this.style.borderColor='#0284c7';" onmouseout="this.style.backgroundColor='#e0f2fe'; this.style.color='#0369a1'; this.style.borderColor='#7dd3fc';">
                      <i class="fa fa-undo"></i> Reset Master (₹${formattedMasterRate})
                    </button>
                  ` : ''}
                  <button type="button" class="btn-close ms-1" onclick="$('#product-last-purchase-suggestion').slideUp(200)" aria-label="Close" style="font-size: 0.8rem;"></button>
                </div>
              </div>

              <div class="d-flex flex-column gap-2 mt-2">
                ${entriesHtml}
              </div>
            </div>
          `;

          $suggestionDiv.removeClass("d-none").html(suggestionHtml).stop(true, true).slideDown(250);
        } else {
          $suggestionDiv.slideUp(200).empty();
        }
      },
      error: function () {
        $suggestionDiv.slideUp(200).empty();
      }
    });
  }

  // Global helper to preview final sale invoice in a modal (without opening new tab)
  window.previewSaleOrderInvoice = function (orderType, orderNo, sono, psono) {
    const routurl = $("body").data("routurl") || "../";
    const companyid = $("body").data("companyid") || "";
    const subcompanyid = $("body").data("subcompanyid") || "";
    const projectid = $("body").data("projectid") || "";
    const targetSono = sono || orderNo || "";

    if (!targetSono) return;

    const invoiceUrl = `${routurl}krf_provisional_sale_order_edit/modal/finalsalesorderPDF.php?sono=${encodeURIComponent(targetSono)}&companyid=${encodeURIComponent(companyid)}&subcompanyid=${encodeURIComponent(subcompanyid)}&projectid=${encodeURIComponent(projectid)}&mode=I`;

    $("#previewModalSono").text(targetSono);
    $("#previewModalNewTabBtn").attr("href", invoiceUrl);

    const loaderEl = document.getElementById("previewModalLoader");
    const iframeEl = document.getElementById("previewInvoiceIframe");

    // Display loader overlay
    if (loaderEl) {
      loaderEl.style.display = "flex";
      loaderEl.style.opacity = "1";
    }

    const hideLoader = function () {
      if (loaderEl) {
        loaderEl.style.transition = "opacity 0.25s ease";
        loaderEl.style.opacity = "0";
        setTimeout(function () {
          loaderEl.style.display = "none";
        }, 250);
      }
    };

    if (iframeEl) {
      iframeEl.onload = hideLoader;
      iframeEl.src = invoiceUrl;
    }

    // Safety timeout: ensure loader disappears even if browser suppresses iframe onload for inline PDF plugins
    setTimeout(hideLoader, 800);

    // Open modal (BS5, BS4, BS3 & vanilla JS fallback)
    const modalEl = document.getElementById("finalSaleInvoicePreviewModal");
    if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
      try {
        let bsModal = bootstrap.Modal.getInstance(modalEl);
        if (!bsModal) {
          bsModal = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
        }
        bsModal.show();
      } catch (e) {
        if (typeof $ !== "undefined" && typeof $.fn.modal === "function") {
          $("#finalSaleInvoicePreviewModal").modal("show");
        }
      }
    } else if (typeof $ !== "undefined" && typeof $.fn.modal === "function") {
      $("#finalSaleInvoicePreviewModal").modal("show");
    } else if (modalEl) {
      modalEl.style.display = "block";
      modalEl.classList.add("show");
      document.body.classList.add("modal-open");
    }
  };

  window.closeInvoicePreviewModal = function () {
    const modalEl = document.getElementById("finalSaleInvoicePreviewModal");
    if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
      try {
        const bsModal = bootstrap.Modal.getInstance(modalEl);
        if (bsModal) {
          bsModal.hide();
        }
      } catch (e) {}
    }
    if (typeof $ !== "undefined" && typeof $.fn.modal === "function") {
      try {
        $("#finalSaleInvoicePreviewModal").modal("hide");
      } catch(e) {}
    }
    if (modalEl) {
      modalEl.style.display = "none";
      modalEl.classList.remove("show");
    }
    document.body.classList.remove("modal-open");
    $(".modal-backdrop").remove();
    const iframeEl = document.getElementById("previewInvoiceIframe");
    if (iframeEl) {
      iframeEl.src = "about:blank";
    }
  };

  // Global helper to apply the suggested rate to the target row
  window.applyLastPurchaseRate = function (rate, targetRowIndex) {
    let $targetRow;
    if (targetRowIndex !== undefined && targetRowIndex !== null) {
      $targetRow = $("#provisionorderitemsTable tbody tr").eq(targetRowIndex);
    }
    if (!$targetRow || !$targetRow.length) {
      $targetRow = $("#provisionorderitemsTable tbody tr:last");
    }
    const $rateInput = $targetRow.find("input.ratecal");
    $rateInput.val(rate).trigger("input");

    // Smooth visual highlight feedback
    $rateInput.css({ "background-color": "#dcfce7", "border-color": "#22c55e", "transition": "all 0.3s" });
    setTimeout(() => {
      let loginRoleId = parseInt($("body").data("loginroleid") || $("#hidden_loginroleid").val() || "<?php echo intval($loginroleid ?? 0); ?>") || 0;
      let defaultBg = (loginRoleId > 2) ? "#f8fafc" : "";
      $rateInput.css({ "background-color": defaultBg, "border-color": "" });
    }, 1200);
  };

  // Dedicated function to fetch, display, and auto-apply client discount for the selected product
  function fetchAndApplyClientDiscount(productId, $row, autoApply) {
    if (autoApply === undefined) autoApply = true;
    const $discountDiv = $("#product-discount-suggestion");
    if (!productId) {
      $discountDiv.slideUp(200).empty();
      return;
    }

    const clientId = $("#clientid").val() || "";
    const clientMobile = $("#mobileno").val() || "";
    const clientName = $("#clientname").val() || "Client";
    const routurl = $("body").data("routurl") || "../";
    const projectid = $("body").data("projectid") || "";

    if (!clientId && !clientMobile) {
      $discountDiv.slideUp(200).empty();
      return;
    }

    const rowIndex = $row ? $row.index() : 0;
    let qty = 0;
    let price = 0;
    if ($row && $row.length) {
      qty = parseFloat($row.find("input.qty_td").val()) || 0;
      let selectedOpt = $row.find(".productname option:selected");
      price = parseFloat($row.find("input.rate_td").val()) || (selectedOpt.length ? parseFloat(selectedOpt.data("priceofproduct")) : 0);
    }

    $.ajax({
      url: routurl + "krf_provisional_sale_order_edit/modal/product_modal.php?action=getClientProductDiscount",
      type: "POST",
      dataType: "json",
      data: {
        clientid: clientId,
        mobileno: clientMobile,
        productid: productId,
        quantity: qty,
        price: price,
        projectid: projectid
      },
      success: function (res) {
        if (res && res.status === "success" && (res.has_discount || res.policy_enabled)) {
          const safeClientName = escapeClientHtml(clientName);
          const isGrafted = res.is_grafted;
          const plantTypeLabel = isGrafted ? 'Grafted Plant' : 'Non-Grafted Plant';
          const plantBadge = isGrafted
            ? `<span class="badge bg-white text-dark border px-2 py-1" style="font-size: 0.8rem;"><i class="fa fa-seedling text-success me-1"></i> Grafted</span>`
            : `<span class="badge bg-white text-dark border px-2 py-1" style="font-size: 0.8rem;"><i class="fa fa-leaf text-success me-1"></i> Non-Grafted</span>`;

          const rule = res.applicable_rule || 'none';
          const discType = res.discount_type || 'rate';
          const discPercent = parseFloat(res.discount_percent || 0);
          const discAmount = parseFloat(res.discount_amount || 0);
          const discRate = parseFloat(res.discount_rate || 0);
          const activePolicy = res.active_policy || {};

          let ruleText = rule === 'asperplane' ? 'As Per Plan (Quantity Slabs)' : (rule === 'everyorder' ? 'Every Order' : 'None');
          let typeText = discType === 'rate' ? 'Rate (₹ / Plant)' : (discType === 'percent' ? 'Percentage (%)' : 'Fixed Amount (₹ / Order)');

          let policyDetailsHtml = '';

          if (rule === 'everyorder') {
            let valFormatted = discType === 'percent' ? `${discPercent}%` : `₹${discAmount.toFixed(2)}`;
            policyDetailsHtml = `
              <div class="d-flex flex-wrap align-items-center justify-content-between p-2 px-3 rounded-2 bg-white border shadow-2xs gap-2" style="border-color: #a7f3d0 !important;">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <span class="badge bg-soft-success text-success border border-success-subtle px-2 py-1" style="font-size: 0.85rem;">
                    <i class="fa fa-percentage me-1"></i> Calculation: <strong>${typeText}</strong>
                  </span>
                  <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.85rem;">
                    <i class="fa fa-tag text-success me-1"></i> Value: <strong class="text-success" style="font-size: 0.92rem;">${valFormatted}</strong>
                  </span>
                  <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.82rem;">
                    <i class="fa fa-check-circle text-primary me-1"></i> Rule: <strong>Every Order</strong>
                  </span>
                </div>
                <div>
                  <button type="button" class="btn btn-sm btn-success shadow-xs fw-bold d-flex align-items-center gap-1" onclick="applyClientDiscountToRow('${discType}', ${discType === 'percent' ? discPercent : discAmount}, ${rowIndex})" style="border-radius: 6px; padding: 4px 12px; font-size: 0.82rem;">
                    <i class="fa fa-check-circle"></i> Apply Discount (${valFormatted})
                  </button>
                </div>
              </div>
            `;
          } else if (rule === 'asperplane') {
            const slabs = activePolicy.slabs || [];
            let slabsHtml = slabs.map((s, sIdx) => {
              const minQ = parseInt(s.min_qty) || 0;
              const maxQ = parseInt(s.max_qty) || 0;
              const maxDisplay = maxQ > 0 ? maxQ.toLocaleString() : 'Above';
              const sType = s.type || 'rate';
              const sVal = sType === 'percent' ? `${parseFloat(s.percent || 0)}%` : `₹${parseFloat(s.amount || 0).toFixed(2)}`;
              const isMatched = res.matched_slab && (res.matched_slab.min_qty == s.min_qty);

              return `
                <div class="d-flex flex-wrap align-items-center justify-content-between p-2 px-3 rounded-2 bg-white border shadow-2xs gap-2 ${isMatched ? 'border-success' : ''}" style="border-color: ${isMatched ? '#10b981' : '#cbd5e1'} !important; ${isMatched ? 'background-color: #ecfdf5 !important;' : ''}">
                  <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge ${isMatched ? 'bg-success text-white' : 'bg-light text-dark'} border px-2 py-1 fw-bold" style="font-size: 0.78rem;">
                      ${isMatched ? '<i class="fa fa-check me-1"></i> MATCHED SLAB' : `Slab #${sIdx + 1}`}
                    </span>
                    <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.82rem;">
                      <i class="fa fa-boxes text-info me-1"></i> Qty: <strong>${minQ.toLocaleString()} - ${maxDisplay}</strong>
                    </span>
                    <span class="badge bg-soft-success text-success border border-success-subtle px-2 py-1" style="font-size: 0.85rem;">
                      <i class="fa fa-tag me-1"></i> ${sType === 'percent' ? 'Discount %' : 'Rate/Amt'}: <strong>${sVal}</strong>
                    </span>
                  </div>
                  <div>
                    <button type="button" class="btn btn-sm ${isMatched ? 'btn-success' : 'btn-outline-success'} shadow-xs fw-bold d-flex align-items-center gap-1" onclick="applyClientDiscountToRow('${sType}', ${sType === 'percent' ? (s.percent || 0) : (s.amount || 0)}, ${rowIndex})" style="border-radius: 6px; padding: 4px 12px; font-size: 0.82rem;">
                      <i class="fa fa-check-circle"></i> Apply (${sVal})
                    </button>
                  </div>
                </div>
              `;
            }).join('');

            policyDetailsHtml = `
              <div class="d-flex flex-column gap-2">
                ${slabsHtml}
              </div>
            `;
          }

          const discountCardHtml = `
            <div class="alert alert-success border-0 shadow-sm p-3 rounded-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #86efac !important; animation: fadeIn 0.3s ease-out;">
              <div class="d-flex flex-wrap align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #86efac !important; gap: 8px;">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle p-1 d-flex align-items-center justify-content-center bg-white shadow-sm flex-shrink-0" style="width: 32px; height: 32px; border: 1px solid #86efac;">
                    <i class="fa fa-percent text-success" style="font-size: 0.95rem;"></i>
                  </div>
                  <div>
                    <div class="fw-bold" style="color: #166534; font-size: 0.92rem;">
                      <i class="fa fa-tags text-success me-1"></i> Applicable Discount for <span class="text-dark">${safeClientName}</span>:
                      ${plantBadge}
                      <span class="badge bg-success text-white ms-1" style="font-size: 0.78rem;"><i class="fa fa-check-circle me-1"></i> Active Policy (${ruleText})</span>
                    </div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <button type="button" class="btn btn-sm shadow-xs fw-bold d-flex align-items-center gap-1" onclick="clearClientDiscountFromRow(${rowIndex})" style="border-radius: 6px; padding: 4px 10px; font-size: 0.8rem; background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#ef4444'; this.style.color='#ffffff'; this.style.borderColor='#ef4444';" onmouseout="this.style.backgroundColor='#fee2e2'; this.style.color='#b91c1c'; this.style.borderColor='#fca5a5';">
                    <i class="fa fa-times-circle"></i> Clear Discount
                  </button>
                  <button type="button" class="btn-close ms-1" onclick="$('#product-discount-suggestion').slideUp(200)" aria-label="Close" style="font-size: 0.8rem;"></button>
                </div>
              </div>

              <div class="mt-2">
                ${policyDetailsHtml}
              </div>
            </div>
          `;

          $discountDiv.removeClass("d-none").html(discountCardHtml).stop(true, true).slideDown(250);

          // Auto-apply discount to row if autoApply is enabled
          if (autoApply && $row && $row.length && res.has_discount) {
            applyClientDiscountToRowInternal(res, $row);
          }
        } else {
          $discountDiv.slideUp(200).empty();
          if (autoApply && $row && $row.length) {
            // Reset discount to 0 if no policy
            $row.find("input.distprt_td").val('');
            $row.find("input.distamt_td").val('');
            $row.find("input.ratecal").trigger("input");
          }
        }
      },
      error: function () {
        $discountDiv.slideUp(200).empty();
      }
    });
  }

  // Internal helper to apply discount object to a specific row
  function applyClientDiscountToRowInternal(res, $targetRow) {
    if (!res || !res.has_discount || !$targetRow || !$targetRow.length) return;

    const discType = res.discount_type;
    const discPercent = parseFloat(res.discount_percent || 0);
    const discAmount = parseFloat(res.discount_amount || 0);
    const discRate = parseFloat(res.discount_rate || 0);
    const qty = parseFloat($targetRow.find("input.qty_td").val()) || 0;
    const rate = parseFloat($targetRow.find("input.rate_td").val()) || 0;

    let $distprt = $targetRow.find("input.distprt_td");
    let $distamt = $targetRow.find("input.distamt_td");

    if (discType === 'percent' && discPercent > 0) {
      $distprt.val(discPercent).trigger("input");
    } else if (discType === 'rate' && (discRate > 0 || discAmount > 0)) {
      const effectiveRateDisc = discRate > 0 ? discRate : discAmount;
      if (qty > 0) {
        const totalDiscAmt = (effectiveRateDisc * qty).toFixed(2);
        $distamt.val(totalDiscAmt).trigger("input");
      } else if (rate > 0) {
        const pctEquiv = ((effectiveRateDisc / rate) * 100).toFixed(2);
        $distprt.val(pctEquiv).trigger("input");
      }
    } else if (discType === 'fixed' && discAmount > 0) {
      $distamt.val(discAmount).trigger("input");
    }

    // Highlight animation
    $distprt.add($distamt).css({ "background-color": "#dcfce7", "border-color": "#22c55e", "transition": "all 0.3s" });
    setTimeout(() => {
      $distprt.add($distamt).css({ "background-color": "", "border-color": "" });
    }, 1200);
  }

  // Global helper to apply client discount from UI button click
  window.applyClientDiscountToRow = function (type, value, targetRowIndex) {
    let $targetRow;
    if (targetRowIndex !== undefined && targetRowIndex !== null) {
      $targetRow = $("#provisionorderitemsTable tbody tr").eq(targetRowIndex);
    }
    if (!$targetRow || !$targetRow.length) {
      $targetRow = $("#provisionorderitemsTable tbody tr:last");
    }

    const numVal = parseFloat(value) || 0;
    const qty = parseFloat($targetRow.find("input.qty_td").val()) || 0;
    const rate = parseFloat($targetRow.find("input.rate_td").val()) || 0;
    let $distprt = $targetRow.find("input.distprt_td");
    let $distamt = $targetRow.find("input.distamt_td");

    if (type === 'percent') {
      $distprt.val(numVal).trigger("input");
    } else if (type === 'rate') {
      if (qty > 0) {
        $distamt.val((numVal * qty).toFixed(2)).trigger("input");
      } else if (rate > 0) {
        $distprt.val(((numVal / rate) * 100).toFixed(2)).trigger("input");
      } else {
        $distamt.val(numVal.toFixed(2)).trigger("input");
      }
    } else if (type === 'fixed') {
      $distamt.val(numVal.toFixed(2)).trigger("input");
    }

    $distprt.add($distamt).css({ "background-color": "#dcfce7", "border-color": "#22c55e", "transition": "all 0.3s" });
    setTimeout(() => {
      $distprt.add($distamt).css({ "background-color": "", "border-color": "" });
    }, 1200);
  };

  // Global helper to clear client discount
  window.clearClientDiscountFromRow = function (targetRowIndex) {
    let $targetRow;
    if (targetRowIndex !== undefined && targetRowIndex !== null) {
      $targetRow = $("#provisionorderitemsTable tbody tr").eq(targetRowIndex);
    }
    if (!$targetRow || !$targetRow.length) {
      $targetRow = $("#provisionorderitemsTable tbody tr:last");
    }
    $targetRow.find("input.distprt_td").val('');
    $targetRow.find("input.distamt_td").val('');
    $targetRow.find("input.ratecal").trigger("input");

    // Soft highlight on cleared fields
    $targetRow.find("input.distprt_td, input.distamt_td").css({ "background-color": "#fee2e2", "border-color": "#ef4444", "transition": "all 0.3s" });
    setTimeout(() => {
      $targetRow.find("input.distprt_td, input.distamt_td").css({ "background-color": "", "border-color": "" });
    }, 800);
  };


    $(document).on("change", "input[name='pickupfrom']", function () {
      if (window.OrderModule && window.OrderModule.hydrating) return;
      const mode = $("input[name='pickupfrom']:checked").val();
      $("#deliveryboxdiv").addClass("d-none");
      $("#deliveryaddressdiv").fadeOut();
      $("#btnSkipDeliveryStep").removeClass("d-none");

      $("#deliveryaddresstbl tbody").empty();
      $("#deliveryboxtbl tbody").empty();

        $(".f_disc_input").prop("disabled", true);
        $(".f_amt_input").prop("disabled", true);

      if (mode !== "delivered") {

        $(".f_disc_input").prop("disabled", false);
        $(".f_amt_input").prop("disabled", false);
          $("#freeFreightNotification").empty().hide();
          $("#spanratedbl").html("").addClass("d-none");
          if (typeof updateDiscountRemarksNarration === 'function') {
              updateDiscountRemarksNarration();
          }
      }

      if (mode === "delivered") {
        $("#deliveryaddressdiv").fadeIn();
        fetches_states();
        if (typeof applyCheckedExistingClient === "function") {
          applyCheckedExistingClient();
        }
      } else if (mode === "boxpacking") {
        $("#btnSkipDeliveryStep").addClass("d-none");
        $("#deliveryboxdiv").removeClass("d-none");
        fillboxdata();

      } else {
        $("#deliveryaddressdiv").fadeOut();
        $("#deliveryaddresstbl tbody").empty();
      }
    });

    $(document).ready(function () {
      calculateFreightPayable("#freight_amount", "#discount_percent", "#payablefreight_amount");

      // Initial state sync for delivery address
      if ($("input[name='pickupfrom']:checked").val() === "delivered") {
        $("#deliveryaddressdiv").show();
      } else {
        $("#deliveryaddressdiv").hide();
      }
    });

    // Submit form using AJAX
    $("#salesOrderForm").submit(function (e) {
      e.preventDefault();
      if (!window.OrderModule || !window.OrderModule.ready) return;
      const $form = $(this);
      const $submitBtn = $form.find('button[type="submit"]');

      // 1. Basic Validation: Ensure at least one product is added
      if ($("#provisionorderitemsTable tbody tr").length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Empty Order',
          text: 'Please add at least one product to the provisional order.'
        });
        return false;
      }

      // 2. Check if 'pickupfrom' selection is made
      let pickupOption = $("input[name='pickupfrom']:checked").val();
      if (!pickupOption) {
        Swal.fire({
          icon: 'warning',
          title: 'Action Required',
          text: 'Please select whether the order will be Picked Up or Delivered.'
        });
        return false;
      }

      if (!window.OrderModule.validateAddressRows()) return false;

      // 3. If 'delivered' is selected, ensure freight charge is provided
        let totalFreight = 0;
        let hasEmptyFreight = false;
      if (pickupOption === "delivered") { 
        $(".f_amt_input").each(function() {
            let val = $(this).val().trim();
            if (val === "" || isNaN(parseFloat(val))) {
                hasEmptyFreight = true;
                $(this).addClass("is-invalid");
            } else {
                $(this).removeClass("is-invalid");
                totalFreight += parseFloat(val);
            }
        });

        if (hasEmptyFreight && $(".f_amt_input").length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Freight Charges Missing',
                text: 'Please enter freight charges for all delivery locations in Step ' + (OrderModule.isAddress() ? '2' : '4') + '.'
            });
            goToStep(4);
            return false;
        }

        // Optional: Ensure at least some freight if delivered
        if (totalFreight === 0 && $(".f_amt_input").length > 0) {
            // Allow 0 if explicitly entered, but warn if nothing entered
        }
      }else if (pickupOption === "boxpacking") {
        
        $(".freightamount").each(function() {
            let val = $(this).val().trim();
            if (val === "" || isNaN(parseFloat(val))) {
                hasEmptyFreight = true;
                $(this).addClass("is-invalid");
            } else {
                $(this).removeClass("is-invalid");
                totalFreight += parseFloat(val);
            }
        });

        if (hasEmptyFreight && $(".freightamount").length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Freight Charges Missing',
                text: 'Please enter freight charges for all delivery locations in Step ' + (OrderModule.isAddress() ? '2' : '4') + '.'
            });
            goToStep(4);
            return false;
        }

        // Optional: Ensure at least some freight if delivered
        if (totalFreight === 0 && $(".freightamount").length > 0) {
            // Allow 0 if explicitly entered, but warn if nothing entered
        }


      }

      // 4. Update Remarks with any applied discount narration before confirmation
      if (typeof window.updateDiscountRemarksNarration === 'function') {
        window.updateDiscountRemarksNarration();
      }

      // 5. Confirm before saving
      Swal.fire({
        title: window.OrderModule.isConfirmed() ? 'Update Confirmed Order' : (window.OrderModule.isEdit() ? 'Update Provisional Order' : 'Create Provisional Order'),
        text: window.OrderModule.isConfirmed() ? 'Save changes to this confirmed sales order?' : (window.OrderModule.isEdit() ? 'Save changes to this provisional sales order?' : 'Create this provisional sales order?'),
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: window.OrderModule.isEdit() ? 'Yes, Save Changes' : 'Yes, Submit Order',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (!result.isConfirmed) {
          return;
        }

        const key = $("body").data("key");
        const mdid = "<?php echo $mdid; ?>";
        const routurl = $("body").data("routurl");

        // Disable button and show loading state
        $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i> Saving...');

        // Freight data is now sent via arrays f_amount[], f_vehicle[], f_distance[], f_discount[]
        // so we don't need the old hidden fields if the PHP is updated to loop through them. 
        $.ajax({
          url: "module_order/save_order.php",
          type: "POST",
          data: window.OrderModule.serialize($form),
          dataType: "json",
          success: function (response) {
            console.log(response)
            if (response.status === "success") {
              if (!window.OrderModule.isEdit()) {
              // Trigger background email process
              if (response.clientid && response.psono) {
                SendEmailProvisionalOrderDetails(response.clientid, response.psono, function (eres) {
                  console.log("Email Notification Response:", eres);
                });
              }

              // Trigger WhatsApp Notification
              whatsapp_sendprovisionalorder(response.clientid, response.psono, function (wa_res) {
                console.log("WhatsApp Response:", wa_res);
              });

              const fromFollowup=Boolean(window.poEnquiryReturnUrl)||new URL(window.location.href).searchParams.get("return_to")==="draft_followup";
              Swal.fire({
                title: "Order Saved Successfully!",
                text: "Provisional Order No: " + response.psono + "\nWhat would you like to do next?",
                icon: "success",
                showDenyButton: !fromFollowup,
                // showCancelButton: true, // Commented: Hide 'Advance Payment' button
                showCancelButton: false,
                confirmButtonColor: '#3085d6',
                denyButtonColor: '#007bff',
                // cancelButtonColor: '#17a2b8',
                confirmButtonText: "Add More Order",
                denyButtonText: "Review This Order",
                // cancelButtonText: "Advance Payment"
              }).then((result) => {
                if (result.isConfirmed) {
                  if(fromFollowup)returnFromOrderEditor();
                  else window.location.reload();
                } else if (result.isDenied) {
                  window.location.href = "../krf-orderandsales/provisionalsaleorder_review_new.php?k=" + key + "&mdid=" + mdid + "&iframe=true";
                }
                /* else if (result.dismiss === Swal.DismissReason.cancel) {
                  const mob = encodeURIComponent($("#mobileno").val() || "");
                  const psono = encodeURIComponent(response.psono || "");
                  const clid = encodeURIComponent(response.clientid || "");
                  const cname = encodeURIComponent($("#clientname").val() || "");
                  window.location.href = `../krf-orderandsales/paymentadvicemanual.php?k=${key}&mdid=${mdid}&iframe=true&psono=${psono}&mob=${mob}&clid=${clid}&cname=${cname}`;
                } */
              });

                return;
              }

              // Use the existing WhatsApp AJAX method after successful saving.
              if(response.notification && response.notification.status==='Ready'){
                if(typeof whatsapp_sendmodifiedorder==='function')whatsapp_sendmodifiedorder(response.notification,window.OrderModule.isConfirmed(),function(res){if(res.status!=='success')alertmessage('warning','Order saved. WhatsApp: '+res.message);});
                else alertmessage('warning','Order saved. Please install the updated whatsappaction.js to send the modification message.');
              }
             Swal.fire({
                  icon: 'success',
                  title: 'Changes saved',
                  text: 'PSO No: ' + response.psono + (response.notification && response.notification.status === 'Warning' ? '\n' + response.notification.message : '')
              }).then(function () {
                  returnFromOrderEditor();
              });

            } else {
              $submitBtn.prop('disabled', false).text(window.OrderModule.isEdit() ? 'Save Changes' : 'Save Provisional Order');
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.message || 'Failed to save the order.'
              });
            }
          },
          error: function (xhr, status, error) {
            $submitBtn.prop('disabled', false).text(window.OrderModule.isEdit() ? 'Save Changes' : 'Save Provisional Order');
            console.error("Submission Error:", error, xhr.responseText);
            alertmessage('warning', (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to save the order. Please check the submitted details.');
          }
        });
      });
    });

    // whatsapp_sendprovisionalorder('C00002', 'PSO-2026-17-00001', function (wa_res) {
    //   console.log("WhatsApp Response:", wa_res);
    // });

    $(document).on("click", ".gotoprovisionalorderlist", function (event) {
      event.preventDefault();
      returnFromOrderEditor();
    });
    //ajay
    $(document).on("change", ".chkavqty", function () {
      let $row = $(this).closest('tr');
      let requiredQty = cleanNumber($("#requriedqty_client").val());

      if ($(this).is(":checked")) {
        // --- 🟢 DUPLICATE VALIDATION START 🟢 ---
        let productid = $row.data("productid");
        let deliverydate = $row.data("expecteddelivery_stk"); // The raw date for comparison (YYYY-MM-DD)
        let displayDate = $row.find(".display_ready_date").text().trim() || $row.data("displaydate") || $row.find("td:eq(5)").text().trim(); // The formatted date for the message (DD-MM-YYYY)
        let $activeRow = $("#productname_client").data("rowdata");
        let exists = false;

        // 1. Check against other rows in the main order table
        $("#provisionorderitemsTable tbody tr").not($activeRow).each(function () {
          let $pRow = $(this);
          let pId = $pRow.find(".productname").val();
          let pDate = $pRow.find(".expecteddelivery").val();

          if (pId == productid && (window.OrderModule.isConfirmed() ? String($pRow.find('.stockdbid').val()) === String($row.data('dbsno')) : pDate == deliverydate)) {
            exists = true;
            return false; // break
          }
        });

        // 2. Also check if another row in THIS modal is already checked with the same date
        if (!exists) {
          $(".chkavqty:checked").not(this).each(function () {
            let $mRow = $(this).closest("tr");
            if (window.OrderModule.isConfirmed() ? String($mRow.data('dbsno')) === String($row.data('dbsno')) : $mRow.data("expecteddelivery_stk") == deliverydate) {
              exists = true;
              return false; // break
            }
          });
        }

        if (exists) {
          $(this).prop("checked", false);
          Swal.fire({
            icon: 'warning',
            title: 'Already Added',
            text: 'Already added this product for delivery date: ' + displayDate + '. Please choose a different delivery date.',
            confirmButtonText: 'OK'
          });
          return; // 🛑 Stop here - do not update any allocation values
        }
        // --- 🔴 DUPLICATE VALIDATION END 🔴 ---

        let otherAllotedTotal = 0;
        $(".chkavqty:checked").not(this).each(function () {
          otherAllotedTotal += cleanNumber($(this).closest("tr").find(".alloted_stk").text());
        });

        let remainingNeeded = Math.max(0, requiredQty - otherAllotedTotal);
        let avilableqty_stk = cleanNumber($row.attr("data-availableqty") || $row.find(".balance-display-val").text() || $row.find(".avilableqty_stk").text());

        let allocateQty = Math.min(avilableqty_stk, remainingNeeded);
        $row.find(".alloted_stk").text(allocateQty > 0 ? allocateQty : 0);
        $row.find(".alloted_stk").prop("contenteditable", true);
      } else {
        $row.find(".alloted_stk").text("");
        $row.find(".alloted_stk").prop("contenteditable", false);
      }

      let finalTotal = 0;
      $(".chkavqty:checked").each(function () {
        finalTotal += cleanNumber($(this).closest("tr").find(".alloted_stk").text());
      });

      let remainingNeededAfter = Math.max(0, requiredQty - finalTotal);
      if (remainingNeededAfter > 0 && $(".chkavqty:checked").length > 0) {
        $(".chkavqty:checked").each(function () {
          if (remainingNeededAfter <= 0) return false;
          let $r = $(this).closest("tr");
          let avail = cleanNumber($r.attr("data-availableqty") || $r.find(".balance-display-val").text() || $r.find(".avilableqty_stk").text());
          let curr = cleanNumber($r.find(".alloted_stk").text());
          let canAdd = Math.min(avail - curr, remainingNeededAfter);
          if (canAdd > 0) {
            let updated = curr + canAdd;
            $r.find(".alloted_stk").text(updated);
            finalTotal += canAdd;
            remainingNeededAfter -= canAdd;
          }
        });
      }

      $("#allocatestock").val(finalTotal > 0 ? finalTotal : "");
      let balRemaining = Math.max(0, requiredQty - finalTotal);
      $("#balqty_client").val(balRemaining);

      if (finalTotal > 0) {
        if (balRemaining <= 0) {
          $("#headingstk").text("Required quantity is filled from the stock order?");
          $("#chkboxuncheckall").prop("checked", false);
          $("#chkboxuncheckall").prop("disabled", true);
          if (!$(".chkavqty:not(:checked)").prop("disabled")) {
            Swal.fire({
              icon: 'success',
              title: 'Allocation Complete',
              text: 'The required quantity has been fully allocated.',
              timer: 1500,
              showConfirmButton: false
            });
          }
          $(".chkavqty:not(:checked)").prop("disabled", true);
        } else {
          $("#headingstk").text("Do you want to create a new stock order for the remaining quantity (" + balRemaining + ")?");
          $("#chkboxuncheckall").prop("checked", false);
          $("#chkboxuncheckall").prop("disabled", false);
          $(".chkavqty").prop("disabled", false);
        }
      } else {
        $("#headingstk").text(window.OrderModule.isConfirmed() ? 'Allocate the additional plants from available stock, or create new production for the balance.' : 'Do you want to create a completely new stock order?');
        $("#chkboxuncheckall").prop("checked", false);
        $("#chkboxuncheckall").prop("disabled", false);
        $(".chkavqty").prop("disabled", false);
      }
    });

    $(document).on('input', '.alloted_stk', function (e) {
      let $row = $(this).closest('tr');
      let el = this;
      const sel = window.getSelection();
      const range = sel.rangeCount > 0 ? sel.getRangeAt(0) : null;
      let caretOffset = 0;
      if (range) {
        const preCaretRange = range.cloneRange();
        preCaretRange.selectNodeContents(el);
        preCaretRange.setEnd(range.endContainer, range.endOffset);
        caretOffset = preCaretRange.toString().length;
      }

      // --- Clean value: keep only digits
      let originalText = $(this).text();
      let cleanedText = originalText.replace(/\D/g, '');

      // --- Calculate caret shift due to removed non-digit chars
      let nonDigitBeforeCaret = (originalText.slice(0, caretOffset).match(/\D/g) || []).length;
      caretOffset -= nonDigitBeforeCaret;
      if (caretOffset < 0) caretOffset = 0;
      if (caretOffset > cleanedText.length) caretOffset = cleanedText.length;

      // --- Update text only if changed
      if (cleanedText !== originalText) {
        $(this).text(cleanedText);
      }

      // --- Restore caret at same logical position
      const rangeNew = document.createRange();
      const selNew = window.getSelection();
      rangeNew.setStart(el.firstChild || el, caretOffset);
      rangeNew.collapse(true);
      selNew.removeAllRanges();
      selNew.addRange(rangeNew);

      let otherTotal = 0;
      let requiredQty = cleanNumber($("#requriedqty_client").val());

      $(".chkavqty:checked").each(function () {
        let $r = $(this).closest("tr");
        if ($r.is($row)) return; // skip current row
        let qty = cleanNumber($r.find(".alloted_stk").text());
        otherTotal += qty;
      });

      let maxQty = cleanNumber($row.attr('data-availableqty') || $row.find('.balance-display-val').text() || $row.find('.avilableqty_stk').text());
      let bal_qty = Math.max(0, requiredQty - otherTotal);
      let currentVal = parseInt(cleanedText || 0, 10);

      let finalAlloted = otherTotal;
      if (currentVal > 0) {
        if (currentVal <= bal_qty && currentVal <= maxQty) {
          finalAlloted = otherTotal + currentVal;
        } else if (currentVal > maxQty || currentVal > bal_qty) {
          let capped = Math.min(bal_qty, maxQty);
          $(this).text(capped > 0 ? capped : "");
          finalAlloted = otherTotal + capped;
        } else {
          $(this).text("");
        }
      }

      $("#allocatestock").val(finalAlloted > 0 ? finalAlloted : "");
      let balRemaining = Math.max(0, requiredQty - finalAlloted);
      $("#balqty_client").val(balRemaining);

      if (finalAlloted > 0) {
        if (balRemaining <= 0) {
          $("#headingstk").text("Required quantity is filled from the stock order?");
          $("#chkboxuncheckall").prop("checked", false);
          $("#chkboxuncheckall").prop("disabled", true);
          if (!$(".chkavqty:not(:checked)").prop("disabled")) {
            Swal.fire({
              icon: 'success',
              title: 'Allocation Complete',
              text: 'The required quantity has been fully allocated.',
              timer: 1500,
              showConfirmButton: false
            });
          }
          $(".chkavqty:not(:checked)").prop("disabled", true);
        } else {
          $("#headingstk").text("Do you want to create a new stock order for the remaining quantity (" + balRemaining + ")?");
          $("#chkboxuncheckall").prop("checked", false);
          $("#chkboxuncheckall").prop("disabled", false);
          $(".chkavqty").prop("disabled", false);
        }
      } else {
        $("#headingstk").text(window.OrderModule.isConfirmed() ? 'Allocate the additional plants from available stock, or create new production for the balance.' : 'Do you want to create a completely new stock order?');
        $("#chkboxuncheckall").prop("checked", false);
        $("#chkboxuncheckall").prop("disabled", false);
        $(".chkavqty").prop("disabled", false);
      }
    });
    $(document).on('paste', '.alloted_stk', function (e) {
      let paste = (e.originalEvent || e).clipboardData.getData('text');
      // only digits allowed
      if (!/^\d+$/.test(paste)) {
        e.preventDefault();
      }
    });


    $(document).on("change select2:select", "#provisionorderitemsTable .productname", function (event) {
      if (window.OrderModule && window.OrderModule.hydrating) return;
      if (event.type === "change" && !event.originalEvent) return;
      const $row = $(this).closest("tr");
      if (!$(this).val() || $row.data("edit-qty-pending")) return;
      $row.data("edit-product-changed", true);
      setTimeout(function () { $row.find(".qty_td").trigger("focus").select(); }, 100);
    });

    // Retain the accepted quantity and date until allocation is confirmed.
    $(document).on("focus", "#provisionorderitemsTable .qty_td", function () {
      const $input = $(this), $row = $input.closest("tr");
      if ($row.data("edit-qty-pending")) return;
      $input.data("edit-last-qty", $input.val());
      $input.data("old-date", $row.find(".expecteddelivery").val());
    });
    $(document).on("input change", "#provisionorderitemsTable .qty_td", function () {
      if (window.OrderModule && window.OrderModule.hydrating) return;
      const $input = $(this), $row = $input.closest("tr");
      const previous = $input.data("edit-last-qty");
      $row.data("edit-qty-changed", previous !== undefined && Number(previous) !== Number($input.val()));
    });
    $("#stockdataModal").on("hidden.bs.modal", function () {
      const $row = $("#productname_client").data("rowdata");
      if ($row && $row.data("edit-qty-pending")) restoreEditedQuantity($row);
    });

    $(document).on("blur", ".qty_td", function () {
      let $input = $(this);
      let $row = $input.closest("tr");
      const originalItem = $row.data('edit-original');
      if (window.handleConfirmedQuantityEdit && window.handleConfirmedQuantityEdit($row,originalItem,function($extra){handleExpectedDeliveryChange($extra,'tblstockdatamaster');})) return;
      const enterAllocation = $input.data("edit-enter-allocation") === true;
      $input.removeData("edit-enter-allocation");
      if (!enterAllocation && !$row.data('edit-product-changed') && !$row.data('edit-qty-changed') && originalItem && Number($input.val()) === Number(originalItem.quantity) && String($row.find('.productname').val()) === String(originalItem.product_id)) return;
      if ($row.data("edit-qty-pending")) return;
      if (!enterAllocation && !$row.data("edit-product-changed") && $input.data("edit-last-qty") !== undefined && Number($input.val()) === Number($input.data("edit-last-qty"))) return;
      let qty = parseFloat($input.val()) || 0;
      // Selecting a product may move focus before a quantity has been entered.
      if (qty <= 0) return;
      $row.data("edit-qty-pending", true);
      let oldDate = $input.data("old-date") || $row.find(".expecteddelivery").val() || "";
      let productVal = $row.find(".productname").val();
      /* 🔴 Product validation */
      if (!productVal) {
        Swal.fire({
          icon: "warning",
          title: "Product Required",
          text: "Please select or enter product name before setting requested delivery date."
        }).then(() => {
          restoreEditedQuantity($row);
        });
        return;
      }

      /* 🔴 Quantity validation */
      if (qty <= 0) {
        Swal.fire({
          icon: "warning",
          title: "Invalid Quantity",
          text: "Please enter a valid quantity before changing requested delivery date."
        }).then(() => {
          restoreEditedQuantity($row);
        });
        return;
      }

      /* 🔴 Duplicate product check */
      // if (!checkDuplicateProducts()) {
      //   Swal.fire({
      //     icon: "error",
      //     title: "Duplicate Product",
      //     text: "This product is already added. Please update the existing row."
      //   }).then(() => {
      //     restoreEditedQuantity($row);
      //   });
      //   return;
      // }

      /* 🔎 Check if stock already allocated */
      let stkid = "";
      $("#provisionorderitemsTable tbody tr").each(function () {
        let $r = $(this);
        if (
          $r.find(".productname").val() === productVal &&
          ($r.find(".stockdbid").val() || "")
        ) {
          stkid = $r.find(".stockdbid").val();
          return false; // break loop
        }
      });

      /* ⚠️ Confirmation if stock exists */
      if (stkid) {
        Swal.fire({
          icon: "warning",
          title: "Edit Order Quantity",
          text: "Are you sure you want to edit the order quantity? Stock quantity has already been allocated.",
          showCancelButton: true,
          confirmButtonText: "Yes, Proceed",
          cancelButtonText: "No, Cancel"
        }).then((result) => {
          if (!result.isConfirmed) {
            restoreEditedQuantity($row);
            return;
          }
          handleExpectedDeliveryChange($row, "tblstockdatamaster");
        });
      } else {
        handleExpectedDeliveryChange($row, "tblstockdatamaster");
      }
    });

  });
  let activeStockCheckXhr = null;
  function handleExpectedDeliveryChange(rowElement, tableid) {
    $("#balqty_client").val('');

    var $table = $("#" + tableid + " tbody");
    $table.empty();
    var projectid = $('body').data('projectid');
    var loginid = $('body').data('loginid');
    const $row = $(rowElement);
    $('#confirmedDeltaInfo').remove();
    if(window.OrderModule.isConfirmed() && $row.data('confirmed-additional')) {
      $('<div>',{id:'confirmedDeltaInfo',class:'alert alert-info'}).text('Existing quantity: '+$row.data('confirmed-base-qty')+' | Revised quantity: '+$row.data('confirmed-requested-qty')+' | Additional allocation: '+$row.find('.qty_td').val()).prependTo('#stockdataModalContent');
    }
    $("#productname_client").removeData();
    $("#productname_client").data("rowdata", $row);
    const productid = $row.find(".productname").val()
    const deliverydate = $row.find(".expecteddelivery").val();

    // 🛑 Removed mergeDuplicateProductsInTable to prevent clearing existing dates/rows
    // mergeDuplicateProductsInTable("provisionorderitemsTable", productid);

    const productname = $row.find(".productname option:selected").text()
    //$row.find(".productname").find("select").val();  
    const qty = $row.find(".qty_td").val();

    let dateObj = new Date(deliverydate);
    let day = String(dateObj.getDate()).padStart(2, '0');
    let month = String(dateObj.getMonth() + 1).padStart(2, '0'); // months are 0-based
    let year = dateObj.getFullYear();
    let formattedDate = `${day}-${month}-${year}`;
    $("#productname_client").val(productname);
    // $("#expecteddelivery_client").val(formattedDate);
    $("#requriedqty_client").val(qty);
    $("#allocatestock").val('');
    $("#balqty_client").val(qty);
    let formData = new FormData();
    formData.append('projectid', projectid);
    formData.append('loginid', loginid);
    formData.append('productid', productid);
    formData.append('requiredqty', qty);
    formData.append('k', window.OrderModule.config.key || '');
    formData.append('edit_psono', window.OrderModule.isEdit() ? ($('#edit_psono').val() || window.OrderModule.config.orderno || '') : '');
    formData.append('deliverydate', deliverydate);
    let consumedQty = 0;
    var rows = "";
    var k = 1;

    if (activeStockCheckXhr) {
      activeStockCheckXhr.abort();
    }

    $table.html(`
      <tr>
        <td colspan="11" class="text-center py-4 text-primary">
          <div class="spinner-border spinner-border-sm me-2" role="status"></div>
          <span class="fw-semibold">Loading available production stock...</span>
        </td>
      </tr>
    `);
    $("#stockdataModal").modal("show");

    activeStockCheckXhr = $.ajax({
      url: window.OrderModule.isConfirmed() ? 'module_order/confirmed_stock.php' : 'modal/stockverification.php?action=chkstock',
      type: "POST",
      data: formData,
      dataType: "json",
      contentType: false,  // ✅ Required when using FormData
      processData: false,  // ✅ Required when using FormData
      cache: false,
      error: function (xhr, status, error) {
        if (status === 'abort') return;
        $table.html(`
          <tr>
            <td colspan="11" class="text-center py-4 text-danger fw-semibold">
              <i class="fa fa-exclamation-triangle me-2"></i>Failed to fetch stock data (${error || 'Server error'}).
            </td>
          </tr>
        `);
      },
      success: async function (res) {
        try { res = await window.OrderModule.mergeAllocatedStock(res, $row); }
        catch (error) { Swal.fire('Stock check failed', error.responseJSON ? error.responseJSON.message : error.message, 'error'); return; }
        $table.empty();
        if (res && res.length > 0) {
          for (var i = 0; i < res.length; i++) {
            let allottedItem = "";
            let alreadyAllotted = !!allottedItem;
            consumedQty = allottedItem ? allottedItem.consumedqty : 0;
            finalstk = Number(res[i].stkqty || 0)   + Number(res[i].readytosale_qty || 0); 
            rows += `
                <tr data-dbsno="${res[i].stksno}"
                    data-productid="${res[i].productid}"
                    data-displaydate="${res[i].displaydate || ''}"
                    data-expecteddelivery_stk="${res[i].expected_delivery_date}"
                    data-orderstkqty="${res[i].stkqty}"
                    data-readytosale_qty="${res[i].readytosale_qty}"
                    data-confirmed_allocated="${res[i].confirmed_allocated}"
                    data-unconfirmed_allocated="${res[i].unconfirmed_allocated}"
                    data-rso_movement="${res[i].rso_movement || 0}"
                    data-availableqty="${res[i].availableqty || 0}"
                    >
                    <td class="text-center text-muted font-monospace">${k}</td>
                    <td class='row-checkbox text-center noexport'>
                        <input type='checkbox' class='chkavqty' name='chkavqty'
                               style='transform: scale(1.4); cursor: pointer;' ${alreadyAllotted ? "checked" : ""}>
                    </td>
                    <td class="text-nowrap fw-semibold text-dark">${res[i].productname || ''}</td>
                    <td class="text-nowrap font-monospace text-secondary">${res[i].stockorder_no || ''}</td>
                    <td class="text-nowrap">
                        <div class="fw-bold text-dark"><i class="fa fa-map-marker-alt text-danger me-1"></i>${res[i].locationname || 'N/A'}</div>
                        ${res[i].polyhouse_details ? `
                            <div class="small text-secondary mt-0.5" style="font-size:0.75rem; line-height: 1.35;">
                                <i class="fa fa-warehouse text-primary me-1"></i>${res[i].polyhouse_details}
                            </div>
                        ` : (res[i].total_trays > 0 ? `
                            <div class="small text-muted" style="font-size:0.75rem;">
                                ${res[i].total_trays} Trays (${Number(res[i].total_plants || 0).toLocaleString()} Plants)
                            </div>
                        ` : '<div class="small text-muted" style="font-size:0.75rem;">-</div>')}
                    </td>
                    <td class="text-nowrap display_ready_date">${res[i].displaydate || ''}</td>
                    <td class="text-center fw-semibold">${Number(finalstk || 0).toLocaleString()}</td>
                    <td class="text-center">
                        ${Number(res[i].confirmed_allocated || 0) > 0 ? `
                            <span class="badge bg-primary text-white px-2 py-1 view-stock-alloc-btn"
                                  style="cursor: pointer; font-size: 0.82rem; font-weight: 600; box-shadow: 0 1px 3px rgba(13,110,253,0.25);"
                                  data-stockorderno="${res[i].stockorder_no || ''}"
                                  data-productid="${res[i].productid || ''}"
                                  data-productname="${(res[i].productname || '').replace(/"/g, '&quot;')}"
                                  data-type="confirmed"
                                  data-qty="${res[i].confirmed_allocated}"
                                  title="Click to view Confirmed Allocations (${res[i].confirmed_allocated})">
                                <i class="fa fa-users me-1" style="font-size: 0.72rem;"></i>${Number(res[i].confirmed_allocated).toLocaleString()}
                            </span>
                        ` : `<span class="text-muted fw-semibold">0</span>`}
                    </td>
                    <td class="text-center">
                        ${Number(res[i].unconfirmed_allocated || 0) > 0 ? `
                            <span class="badge bg-warning text-dark px-2 py-1 view-stock-alloc-btn"
                                  style="cursor: pointer; font-size: 0.82rem; font-weight: 600; border: 1px solid #d97706; box-shadow: 0 1px 3px rgba(245,158,11,0.2);"
                                  data-stockorderno="${res[i].stockorder_no || ''}"
                                  data-productid="${res[i].productid || ''}"
                                  data-productname="${(res[i].productname || '').replace(/"/g, '&quot;')}"
                                  data-type="unconfirmed"
                                  data-qty="${res[i].unconfirmed_allocated}"
                                  title="Click to view Unconfirmed Allocations (${res[i].unconfirmed_allocated})">
                                <i class="fa fa-clock me-1" style="font-size: 0.72rem;"></i>${Number(res[i].unconfirmed_allocated).toLocaleString()}
                            </span>
                        ` : `<span class="text-muted fw-semibold">0</span>`}
                    </td>
                    <td class="text-center">
                        ${Number(res[i].rso_movement || 0) > 0 ? `
                            <span class="badge text-white px-2 py-1"
                                  style="background-color: #6f42c1; font-size: 0.82rem; font-weight: 600; box-shadow: 0 1px 3px rgba(111,66,193,0.25);"
                                  title="RSO Movement: ${Number(res[i].rso_movement).toLocaleString()} Plants">
                                <i class="fa fa-truck-ramp-box me-1" style="font-size: 0.72rem;"></i>${Number(res[i].rso_movement).toLocaleString()}
                            </span>
                        ` : `<span class="text-muted fw-semibold">0</span>`}
                    </td>
                    <td class="avilableqty_stk text-center fw-bold text-success text-nowrap" data-availableqty="${res[i].availableqty || 0}">
                        <span class="balance-display-val">${Number(res[i].availableqty || 0).toLocaleString()}</span>
                        ${(getEffectiveLoginRoleId() <= 3) ? `
                        <button type="button" class="btn btn-sm btn-outline-success border-0 p-1 ms-1 edit-balance-btn"
                                style="line-height: 1; vertical-align: middle; border-radius: 4px;"
                                data-stockorderno="${res[i].stockorder_no || ''}"
                                data-productid="${res[i].productid || ''}"
                                data-productname="${(res[i].productname || '').replace(/"/g, '&quot;')}"
                                data-projectid="${res[i].projectid || ''}"
                                data-balance="${res[i].availableqty || 0}"
                                title="Edit Balance / Consume difference as Karjee Farm">
                            <i class="fa fa-pencil-alt text-success" style="font-size: 0.8rem;"></i>
                        </button>
                        ` : ''}
                    </td>
                    <td class="alloted_stk text-center fw-bold bg-light"></td>
                </tr>`;
            k++;
          }
        } else {
          rows += `
        <tr>
          <td colspan="12" style="text-align:center;font-weight:bold;">
            Stock is not available for this product
          </td>
        </tr>`;
        }
        $table.html(rows);
        $("#searchStockTableInput").val('');
        updateStockSummaryBadges();
        if (consumedQty > 0) {
          $("#allocatestock").val(consumedQty);
        }
        let finalBalQty = cleanNumber($("#requriedqty_client").val()) - cleanNumber($("#allocatestock").val());
        $("#balqty_client").val(finalBalQty);
        sowingcalculation();

        if (finalBalQty <= 0 && cleanNumber($("#allocatestock").val()) > 0) {
          $("#headingstk").text("Required quantity is filled from the stock order?");
          setTimeout(() => { $(".chkavqty:not(:checked)").prop("disabled", true); }, 100);
        } else {
          $("#headingstk").text(window.OrderModule.isConfirmed() ? 'Allocate the additional plants from available stock, or create new production for the balance.' : 'Do you want to create a completely new stock order?');
        }
        $(".emergencycheckbox").prop("checked", false);
         $(".emergencycheckbox").removeData("sowingdt");
         $(".emergencycheckbox").removeData("sowingRddt");
        $("#stockdataModal").modal("show");
      }
    });
  }

  function updateStockSummaryBadges() {
    let totConf = 0;
    let totUnconf = 0;
    let totRsoMv = 0;
    let totBal = 0;

    $("#tblstockdatamaster tbody tr").each(function () {
      const $tr = $(this);
      if ($tr.find("td").length <= 1) return;
      if ($tr.is(":hidden")) return;

      totConf += Number($tr.attr("data-confirmed_allocated") || 0);
      totUnconf += Number($tr.attr("data-unconfirmed_allocated") || 0);
      totRsoMv += Number($tr.attr("data-rso_movement") || 0);
      totBal += Number($tr.attr("data-availableqty") || 0);
    });

    $("#badgeTotalConfirmed").text(totConf.toLocaleString());
    $("#badgeTotalUnconfirmed").text(totUnconf.toLocaleString());
    $("#badgeTotalRsoMovement").text(totRsoMv.toLocaleString());
    $("#badgeTotalBalance").text(totBal.toLocaleString());
  }

  $(document).on("input", "#searchStockTableInput", function () {
    let filter = $(this).val().toLowerCase().trim();
    $("#tblstockdatamaster tbody tr").each(function () {
      if ($(this).find("td").length <= 1) { return; }
      let text = $(this).text().toLowerCase();
      $(this).toggle(!filter || text.indexOf(filter) > -1);
    });
    updateStockSummaryBadges();
  });

  /* ==========================================================
   * STOCK ALLOCATION MODAL & BALANCE ADJUSTMENT HANDLERS
   * ========================================================== */
  let currentAllocModalStockOrder = '';
  let currentAllocModalProductId = 0;
  let currentAllocModalProductName = '';
  let currentAllocModalType = '';

  function refreshStockDataMasterTable() {
    let $row = $("#productname_client").data("rowdata");
    if ($row && $row.length > 0) {
      handleExpectedDeliveryChange($row[0], "tblstockdatamaster");
    }
  }

  function loadStockAllocationsModal(stockorder, productId, productName, type) {
    currentAllocModalStockOrder = stockorder;
    currentAllocModalProductId = productId;
    currentAllocModalProductName = productName;
    currentAllocModalType = type;

    const isConf = (type === 'confirmed');
    $('#allocModalTitlePrefix').text(isConf ? 'Confirmed Allocations' : 'Unconfirmed Allocations');
    $('#stockAllocModalHeader').css('background', isConf 
      ? 'linear-gradient(135deg, #0d6efd 0%, #052c65 100%)' 
      : 'linear-gradient(135deg, #d97706 0%, #78350f 100%)'
    );
    $('#stockAllocModalIcon').attr('class', isConf ? 'fa fa-users text-primary' : 'fa fa-clock text-warning');
    $('#allocModalStockOrderNo').text(stockorder);
    $('#allocModalProductName').text(productName);
    $('#allocModalTotalBadge').text('Loading...');
    $('#searchAllocModalTable').val('');

    const canSeeActions = (getEffectiveLoginRoleId() <= 3);
    $('#tblStockAllocClients .col-alloc-actions').toggle(canSeeActions);

    const $tbody = $('#tblStockAllocClientsBody');
    $tbody.html(`
      <tr>
        <td colspan="${canSeeActions ? 10 : 9}" class="text-center py-4">
          <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
          <span class="text-muted fw-semibold">Loading allocations data...</span>
        </td>
      </tr>
    `);

    $('#stockAllocClientsModal').modal('show');

    $.ajax({
      url: 'modal/stockverification.php?action=get_stock_allocations',
      type: 'POST',
      data: {
        stockorder_no: stockorder,
        product_id: productId,
        type: type
      },
      dataType: 'json',
      success: function(resp) {
        if (resp && resp.status && resp.data) {
          const list = resp.data;
          const totalQty = resp.total_qty || 0;
          $('#allocModalTotalBadge').text(`Total: ${Number(totalQty).toLocaleString()} Plants`);
          $('#allocModalRowCount').text(list.length);

          if (list.length === 0) {
            $tbody.html(`
              <tr>
                <td colspan="${canSeeActions ? 10 : 9}" class="text-center py-4 text-muted">
                  <i class="fa fa-info-circle me-1"></i> No ${isConf ? 'confirmed' : 'unconfirmed'} allocations found for this stock order.
                </td>
              </tr>
            `);
            return;
          }

          let html = '';
          list.forEach((item, index) => {
            const isInternal = item.is_internal || (item.psono === 'KF-INTERNAL');
            const statusClass = (item.confirmstatus && item.confirmstatus.toLowerCase() === 'confirmed')
              ? 'bg-success text-white'
              : 'bg-warning text-dark';
            const displayStatus = item.confirmstatus || item.pso_status || 'Pending';

            const dispStatus = item.dispatch_status || 'Not Dispatched';
            const dispBadgeClass = item.dispatch_badge_class || 'bg-light text-secondary border';
            const isDispatched = !!item.is_dispatched;

            html += `
              <tr data-consumed-id="${item.consumed_id}" data-psono="${item.psono || ''}">
                <td class="text-center font-monospace text-muted">${index + 1}</td>
                <td class="font-monospace">
                  ${isInternal ? `
                    <span class="badge bg-secondary px-2 py-1">KF-INTERNAL</span>
                  ` : `
                    <span class="fw-bold text-primary">${item.psono || '-'}</span>
                  `}
                </td>
                <td>
                  <div class="fw-bold text-dark">${item.clientname || 'Karjee Farm'}</div>
                  ${item.mobileno && item.mobileno !== '-' ? `
                    <div class="small text-muted"><i class="fa fa-phone me-1 text-success"></i>${item.mobileno}</div>
                  ` : ''}
                </td>
                <td class="text-center text-nowrap">${item.order_date || '-'}</td>
                <td class="text-center text-nowrap">${item.expected_delivery_date || '-'}</td>
                <td class="text-center fw-bold ${isConf ? 'text-primary' : 'text-warning-emphasis'}">
                  ${Number(item.consumed_qty || 0).toLocaleString()}
                </td>
                <td class="text-center">
                  ${isInternal ? `
                    <span class="badge bg-info text-dark px-2 py-1">Internal Farm</span>
                  ` : `
                    <span class="badge ${statusClass} px-2 py-1">${displayStatus}</span>
                  `}
                </td>
                <td class="text-center text-nowrap">
                  <span class="badge ${dispBadgeClass} px-2 py-1" style="font-size: 0.78rem;">
                    <i class="fa ${isDispatched ? 'fa-truck' : (dispStatus === 'Partial Dispatch' ? 'fa-boxes' : (dispStatus.indexOf('Ready') > -1 ? 'fa-dolly' : 'fa-clock'))} me-1"></i>
                    ${dispStatus}
                  </span>
                  ${item.dispatched_at ? `
                    <div class="small text-muted mt-0.5" style="font-size:0.72rem;" title="Dispatched At">
                      <i class="fa fa-calendar-check text-success me-0.5"></i>${item.dispatched_at}
                    </div>
                  ` : ''}
                  ${item.invoicenos ? `
                    <div class="small text-primary mt-0.5 text-truncate" style="font-size:0.72rem; max-width: 140px;" title="Invoice(s): ${item.invoicenos}">
                      <i class="fa fa-file-invoice me-0.5"></i>${item.invoicenos}
                    </div>
                  ` : ''}
                </td>
                <td class="small text-secondary" style="max-width: 220px; white-space: normal; word-break: break-word;">
                  ${item.consumed_remark || item.item_remark || '-'}
                </td>
                ${canSeeActions ? `
                <td class="text-center text-nowrap col-alloc-actions">
                  ${!isInternal && item.psono ? `
                    <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2 fw-semibold btn-edit-pso me-1" 
                            data-psono="${item.psono}" title="Edit Provisional Order #${item.psono}">
                      <i class="fa fa-edit me-1"></i> Edit Order
                    </button>
                  ` : ''}
                  ${isDispatched ? `
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2 fw-semibold" disabled title="Cannot remove: This order allocation is already dispatched">
                      <i class="fa fa-lock me-1"></i> Dispatched
                    </button>
                  ` : `
                    <button type="button" class="btn btn-sm btn-outline-danger py-0.5 px-2 fw-semibold btn-remove-alloc-prod"
                            data-consumed-id="${item.consumed_id}"
                            data-psoi-id="${item.psoi_id || ''}"
                            data-psono="${item.psono || ''}"
                            data-stockorder="${stockorder}"
                            data-productid="${productId}"
                            data-productname="${(item.product_name || productName || '').replace(/"/g, '&quot;')}"
                            data-qty="${item.consumed_qty}"
                            data-clientname="${(item.clientname || '').replace(/"/g, '&quot;')}"
                            data-is-internal="${isInternal ? '1' : '0'}"
                            title="${isInternal ? 'Cancel internal consumption to restore balance' : 'Remove product from provisional order with remark'}">
                      <i class="fa ${isInternal ? 'fa-undo' : 'fa-trash'} me-1"></i> ${isInternal ? 'Restore' : 'Remove'}
                    </button>
                  `}
                </td>
                ` : ''}
              </tr>
            `;
          });
          $tbody.html(html);
        } else {
          $tbody.html(`
            <tr>
              <td colspan="${canSeeActions ? 10 : 9}" class="text-center py-4 text-danger">
                <i class="fa fa-exclamation-triangle me-1"></i> Failed to load allocations: ${resp && resp.message ? resp.message : 'Unknown error'}
              </td>
            </tr>
          `);
        }
      },
      error: function(xhr, status, error) {
        $tbody.html(`
          <tr>
            <td colspan="${canSeeActions ? 10 : 9}" class="text-center py-4 text-danger">
              <i class="fa fa-exclamation-triangle me-1"></i> Communication error: ${error}
            </td>
          </tr>
        `);
      }
    });
  }

  // Click on Conf. Alloc or Unconf. Alloc Badge
  $(document).on('click', '.view-stock-alloc-btn', function (e) {
    e.preventDefault();
    const stockorder = $(this).data('stockorderno');
    const productId = $(this).data('productid');
    const productName = $(this).data('productname');
    const type = $(this).data('type');
    loadStockAllocationsModal(stockorder, productId, productName, type);
  });

  // Click Edit Order button in Allocation Modal
  $(document).on('click', '.btn-edit-pso', function (e) {
    e.preventDefault();
    if (getEffectiveLoginRoleId() > 3) {
      Swal.fire('Access Denied', 'Only Admin roles (Role <= 3) can edit orders.', 'error');
      return;
    }
    const psono = $(this).data('psono');
    if (!psono) return;
    const urlParams = new URLSearchParams(window.location.search);
    const key = urlParams.get('k') || '';
    const mdid = urlParams.get('mdid') || '';
    const routurl = $('body').data('routurl') || '../';
    const editUrl = `${routurl}krf-orderandsales/addNewProvisionalsaleorder.php?mode=edit&psono=${encodeURIComponent(psono)}&k=${encodeURIComponent(key)}&mdid=${encodeURIComponent(mdid)}&iframe=true`;
    window.open(editUrl, '_blank');
  });

  // Click Remove Product button in Allocation Modal
  $(document).on('click', '.btn-remove-alloc-prod', function (e) {
    e.preventDefault();
    if (getEffectiveLoginRoleId() > 3) {
      Swal.fire('Access Denied', 'Only Admin roles (Role <= 3) can remove allocations.', 'error');
      return;
    }
    const $btn = $(this);
    const consumedId = $btn.data('consumed-id');
    const psoiId = $btn.data('psoi-id') || '';
    const psono = $btn.data('psono');
    const stockorder = $btn.data('stockorder');
    const productId = $btn.data('productid') || '';
    const productName = $btn.data('productname') || 'Product';
    const qty = $btn.data('qty') || 0;
    const clientName = $btn.data('clientname') || '';
    const isInternal = $btn.data('is-internal') == '1';

    if (isInternal) {
      Swal.fire({
        title: 'Restore Stock Balance?',
        html: `
          <div class="text-start">
            <p class="mb-2">This will cancel the Karjee Farm internal consumption of <strong>${Number(qty).toLocaleString()} plants</strong> for Stock Order <strong>${stockorder}</strong> and restore the balance.</p>
            <label class="form-label fw-bold small text-secondary">Remark / Reason for Restoration <span class="text-danger">*</span></label>
            <textarea id="swal_remove_remark" class="form-control" rows="3" placeholder="Enter reason for restoring stock balance (Mandatory)..."></textarea>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa fa-undo me-1"></i> Yes, Restore Balance',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        preConfirm: () => {
          const remark = $('#swal_remove_remark').val().trim();
          if (!remark) {
            Swal.showValidationMessage('Remark / reason is mandatory.');
            return false;
          }
          return { remark: remark, financial_action: 'credit_note' };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          executeRemoveAllocation(consumedId, psoiId, psono, productId, stockorder, result.value.remark, result.value.financial_action, productName);
        }
      });
      return;
    }

    // Step 1: Pre-check impact of removal via AJAX
    Swal.fire({
      title: 'Checking Order Details...',
      text: 'Verifying order items and payment status...',
      allowOutsideClick: false,
      didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
      url: 'modal/stockverification.php?action=check_removal_impact',
      type: 'POST',
      data: {
        consumed_id: consumedId,
        psoi_id: psoiId,
        psono: psono,
        product_id: productId,
        stockorder_no: stockorder
      },
      dataType: 'json',
      success: function(chk) {
        if (!chk || !chk.status) {
          Swal.fire('Cannot Remove', (chk && chk.message) ? chk.message : 'Failed to verify order details.', 'error');
          return;
        }

        const isFullRemoval = !!chk.is_full_removal;
        const totalPaid = parseFloat(chk.total_paid || 0);
        const clName = chk.clientname || clientName || 'Customer';
        const pso = chk.psono || psono;
        const remaining = chk.remaining_active_items || 0;

        let titleText = isFullRemoval ? '⚠️ Full Order Cancellation & Settlement' : 'Remove Product from Provisional Order?';
        let bodyHtml = '';

        if (isFullRemoval) {
          if (totalPaid > 0) {
            bodyHtml = `
              <div class="text-start">
                <div class="alert alert-danger py-2 px-3 mb-3 small d-flex align-items-start gap-2 shadow-xs">
                  <i class="fa fa-exclamation-triangle text-danger fs-5 mt-0.5"></i>
                  <div>
                    <strong class="d-block text-danger">Warning: Full Order Cancellation!</strong>
                    Removing <strong>${productName}</strong> will cancel the <strong>ENTIRE Provisional Order #${pso}</strong> for <strong>${clName}</strong> because all items will be cancelled.
                  </div>
                </div>

                <div class="p-2.5 rounded border mb-3 bg-light d-flex justify-content-between align-items-center">
                  <div>
                    <div class="small text-muted fw-semibold">Advance Payment Received Against Order:</div>
                    <div class="fs-5 fw-bold text-success">
                      <i class="fa fa-money-bill-wave me-1"></i> ₹ ${totalPaid.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </div>
                  </div>
                  <span class="badge bg-success text-white px-2 py-1">Payment Received</span>
                </div>

                <label class="form-label fw-bold small text-dark mb-1">
                  Choose Financial Settlement Option <span class="text-danger">*</span>:
                </label>
                <div class="mb-3">
                  <div class="p-2.5 border rounded mb-2 bg-white shadow-xs d-flex align-items-center" style="cursor: pointer;" onclick="$('#settle_cn').prop('checked', true);">
                    <input class="form-check-input mt-0" type="radio" name="swal_financial_settlement" id="settle_cn" value="credit_note" checked style="transform: none !important; -webkit-transform: none !important; position: static !important; accent-color: #0d6efd; width: 18px; height: 18px; margin: 0 8px 0 0 !important; flex-shrink: 0; cursor: pointer;">
                    <label class="form-check-label mb-0" for="settle_cn" style="cursor: pointer; margin-left: 6px;">
                      <div class="fw-bold text-primary"><i class="fa fa-file-invoice me-1"></i> Issue Credit Note</div>
                      <div class="text-muted" style="font-size: 0.78rem;">Generate an official Credit Note & credit ₹ ${totalPaid.toLocaleString('en-IN', { minimumFractionDigits: 2 })} to customer ledger balance.</div>
                    </label>
                  </div>
                  <div class="p-2.5 border rounded bg-white shadow-xs d-flex align-items-center" style="cursor: pointer;" onclick="$('#settle_cash').prop('checked', true);">
                    <input class="form-check-input mt-0" type="radio" name="swal_financial_settlement" id="settle_cash" value="refund" style="transform: none !important; -webkit-transform: none !important; position: static !important; accent-color: #dc3545; width: 18px; height: 18px; margin: 0 8px 0 0 !important; flex-shrink: 0; cursor: pointer;">
                    <label class="form-check-label mb-0" for="settle_cash" style="cursor: pointer; margin-left: 6px;">
                      <div class="fw-bold text-danger"><i class="fa fa-hand-holding-usd me-1"></i> Return Cash / Refund</div>
                      <div class="text-muted" style="font-size: 0.78rem;">Return payment to customer as Cash / Bank refund and record write-off in client ledger.</div>
                    </label>
                  </div>
                </div>

                <label class="form-label fw-bold small text-secondary">Cancellation Remark / Reason <span class="text-danger">*</span></label>
                <textarea id="swal_remove_remark" class="form-control" rows="2" placeholder="Enter mandatory cancellation reason / remark..."></textarea>
              </div>
            `;
          } else {
            bodyHtml = `
              <div class="text-start">
                <div class="alert alert-warning py-2 px-3 mb-3 small d-flex align-items-start gap-2 shadow-xs">
                  <i class="fa fa-exclamation-triangle text-warning fs-5 mt-0.5"></i>
                  <div>
                    <strong class="d-block text-dark">Full Provisional Order Cancellation</strong>
                    This is the only/last active item in <strong>${pso}</strong> (${clName}). Removing it will cancel the entire provisional order.
                  </div>
                </div>
                <div class="p-2 rounded border mb-3 bg-light small text-muted">
                  <i class="fa fa-info-circle text-info me-1"></i> No advance payment was received (₹ 0.00). No credit note or refund required.
                </div>
                <label class="form-label fw-bold small text-secondary">Cancellation Remark / Reason <span class="text-danger">*</span></label>
                <textarea id="swal_remove_remark" class="form-control" rows="2" placeholder="Enter mandatory cancellation remark..."></textarea>
              </div>
            `;
          }
        } else {
          bodyHtml = `
            <div class="text-start">
              <div class="alert alert-warning py-2 mb-3 small">
                <i class="fa fa-exclamation-triangle me-1"></i> You are removing <strong>${productName}</strong> (Allocated: <strong>${Number(qty).toLocaleString()}</strong>) from Order <strong>${pso}</strong> (${clName}).
                <div class="mt-1 text-muted"><em>(${remaining} other product(s) will remain active in this order).</em></div>
              </div>
              <label class="form-label fw-bold small text-secondary">Cancellation Remark / Reason <span class="text-danger">*</span></label>
              <textarea id="swal_remove_remark" class="form-control" rows="3" placeholder="Enter mandatory cancellation remark..."></textarea>
            </div>
          `;
        }

        Swal.fire({
          title: titleText,
          html: bodyHtml,
          icon: isFullRemoval ? (totalPaid > 0 ? 'warning' : 'question') : 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: isFullRemoval 
            ? (totalPaid > 0 ? '<i class="fa fa-check me-1"></i> Yes, Confirm & Settle' : '<i class="fa fa-trash me-1"></i> Yes, Cancel Whole Order') 
            : '<i class="fa fa-trash me-1"></i> Yes, Remove Product',
          cancelButtonText: 'Cancel / Keep Order',
          focusConfirm: false,
          preConfirm: () => {
            const remark = $('#swal_remove_remark').val().trim();
            if (!remark) {
              Swal.showValidationMessage('Remark / reason is mandatory.');
              return false;
            }
            const finAction = $("input[name='swal_financial_settlement']:checked").val() || 'credit_note';
            return { remark: remark, financial_action: finAction };
          }
        }).then((result) => {
          if (result.isConfirmed) {
            executeRemoveAllocation(consumedId, psoiId, psono, productId, stockorder, result.value.remark, result.value.financial_action, productName);
          }
        });
      },
      error: function(xhr, status, error) {
        Swal.fire('Error', 'Communication error checking order details: ' + error, 'error');
      }
    });
  });

  function executeRemoveAllocation(consumedId, psoiId, psono, productId, stockorder, remark, financialAction, productName) {
    Swal.fire({
      title: 'Processing...',
      text: 'Removing product allocation and processing settlement...',
      allowOutsideClick: false,
      didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
      url: 'modal/stockverification.php?action=remove_stock_allocation',
      type: 'POST',
      data: {
        consumed_id: consumedId,
        psoi_id: psoiId,
        psono: psono,
        product_id: productId,
        stockorder_no: stockorder,
        remark: remark,
        financial_action: financialAction || 'credit_note',
        projectid: $('body').data('projectid') || 1,
        loginroleid: getEffectiveLoginRoleId()
      },
      dataType: 'json',
      success: function(resp) {
        if (resp && resp.status) {
          Swal.fire({
            icon: 'success',
            title: resp.is_full_removal ? 'Order Cancelled' : 'Product Removed',
            text: resp.message || 'Product removed successfully.',
            confirmButtonColor: '#10b981'
          });
          // Refresh allocations modal table
          loadStockAllocationsModal(stockorder, productId, productName, currentAllocModalType);
          // Refresh parent stock verification table
          refreshStockDataMasterTable();
        } else {
          Swal.fire('Error', resp && resp.message ? resp.message : 'Failed to remove product.', 'error');
        }
      },
      error: function(xhr, status, error) {
        Swal.fire('Error', 'Communication error with server: ' + error, 'error');
      }
    });
  }

  // Click Edit Balance button in Stock Table
  $(document).on('click', '.edit-balance-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();
    if (getEffectiveLoginRoleId() > 3) {
      Swal.fire('Access Denied', 'Only Admin roles (Role <= 3) can edit stock balance.', 'error');
      return;
    }
    const $btn = $(this);
    const stockorder = $btn.data('stockorderno');
    const productId = $btn.data('productid');
    const productName = $btn.data('productname');
    const projectId = $btn.data('projectid') || $('body').data('projectid') || 1;
    const currentBalance = parseInt($btn.data('balance') || 0);

    const modalHtml = `
      <div class="text-start">
        <div class="p-2 mb-3 rounded bg-light border">
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted small">Stock Order:</span>
            <span class="fw-bold font-monospace">${stockorder}</span>
          </div>
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted small">Product:</span>
            <span class="fw-semibold text-truncate" style="max-width: 250px;">${productName}</span>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted small">Current Available Balance:</span>
            <span class="fw-bold text-success" id="curr_bal_display">${currentBalance.toLocaleString()} Plants</span>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold small text-secondary">
            New Balance Quantity <span class="text-danger">*</span>
          </label>
          <input type="number" id="swal_new_balance" class="form-control form-control-lg text-center fw-bold text-primary" 
                 value="${currentBalance}" min="0" step="1" placeholder="Enter new balance">
          <div class="form-text small">Enter updated balance quantity (can be increased or decreased).</div>
        </div>

        <div id="swal_lessed_info_box" class="alert alert-secondary py-2 px-3 mb-3 small">
          <i class="fa fa-info-circle me-1"></i> Enter a new balance quantity to adjust stock.
        </div>

        <div class="mb-2">
          <label class="form-label fw-bold small text-secondary">
            Correction Remark / Reason <span class="text-danger">*</span>
          </label>
          <textarea id="swal_balance_remark" class="form-control" rows="2" placeholder="e.g. Damage plants / Karjee Farm consumption / stock count correction (Mandatory)..."></textarea>
        </div>
      </div>
    `;

    Swal.fire({
      title: '<i class="fa fa-balance-scale text-primary me-2"></i>Edit Stock Balance',
      html: modalHtml,
      showCancelButton: true,
      confirmButtonColor: '#198754',
      cancelButtonColor: '#6c757d',
      confirmButtonText: '<i class="fa fa-check-circle me-1"></i> Update Balance',
      cancelButtonText: 'Cancel',
      focusConfirm: false,
      willOpen: () => {
        $(document).off('focusin.bs.modal');
      },
      didOpen: () => {
        $(document).off('focusin.bs.modal');
        const $newBal = $('#swal_new_balance');
        const $infoBox = $('#swal_lessed_info_box');
        const confirmBtn = Swal.getConfirmButton();

        function updateDiff() {
          const val = $newBal.val();
          if (val === '' || isNaN(val)) {
            $infoBox.removeClass('alert-info alert-success alert-warning alert-danger').addClass('alert-secondary')
              .html('<i class="fa fa-info-circle me-1"></i> Please enter a valid balance quantity.');
            confirmBtn.disabled = true;
            return;
          }
          const n = parseInt(val);
          if (n < 0) {
            $infoBox.removeClass('alert-info alert-success alert-warning alert-secondary').addClass('alert-danger')
              .html('<i class="fa fa-exclamation-circle me-1"></i> New balance cannot be negative.');
            confirmBtn.disabled = true;
          } else if (n === currentBalance) {
            $infoBox.removeClass('alert-danger alert-warning alert-secondary').addClass('alert-info')
              .html('<i class="fa fa-info-circle me-1"></i> Balance quantity: <strong>' + currentBalance.toLocaleString() + '</strong> Plants. Enter a new balance to adjust, or add a remark.');
            confirmBtn.disabled = false;
          } else if (n < currentBalance) {
            const diffQty = currentBalance - n;
            $infoBox.removeClass('alert-danger alert-success alert-secondary alert-info').addClass('alert-warning')
              .html('<i class="fa fa-arrow-down text-danger me-1"></i> Balance will be <strong>reduced</strong> by <strong class="text-danger">' + diffQty.toLocaleString() + '</strong> plants (consumed as Karjee Farm).');
            confirmBtn.disabled = false;
          } else {
            const diffQty = n - currentBalance;
            $infoBox.removeClass('alert-danger alert-warning alert-secondary alert-info').addClass('alert-success')
              .html('<i class="fa fa-arrow-up text-success me-1"></i> Balance will be <strong>increased</strong> by <strong class="text-success">' + diffQty.toLocaleString() + '</strong> plants (New balance: <strong>' + n.toLocaleString() + '</strong>).');
            confirmBtn.disabled = false;
          }
        }

        $newBal.on('input keyup change paste', updateDiff);
        setTimeout(() => {
          $newBal.focus().select();
        }, 80);
        updateDiff();
      },
      didClose: () => {
        $(document).off('focusin.bs.modal');
      },
      preConfirm: () => {
        const val = $('#swal_new_balance').val();
        const n = parseInt(val);
        const remark = $('#swal_balance_remark').val().trim();

        if (isNaN(n) || n < 0) {
          Swal.showValidationMessage('Please enter a valid non-negative balance.');
          return false;
        }
        if (!remark) {
          Swal.showValidationMessage('Correction remark is mandatory.');
          return false;
        }
        return {
          new_balance: n,
          remark: remark
        };
      }
    }).then((result) => {
      if (result.isConfirmed) {
        const newBal = result.value.new_balance;
        const remark = result.value.remark;

        Swal.fire({
          title: 'Updating Balance...',
          text: 'Saving updated stock balance...',
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
          url: 'modal/stockverification.php?action=adjust_stock_balance',
          type: 'POST',
          data: {
            stockorder_no: stockorder,
            product_id: productId,
            product_name: productName,
            projectid: projectId,
            current_balance: currentBalance,
            new_balance: newBal,
            remark: remark,
            loginroleid: getEffectiveLoginRoleId()
          },
          dataType: 'json',
          success: function(resp) {
            if (resp && resp.status) {
              Swal.fire({
                title: 'Balance Updated!',
                text: resp.message || 'Balance updated successfully.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
              });
              // Refresh parent stock verification table immediately
              refreshStockDataMasterTable();
            } else {
              Swal.fire('Error', resp && resp.message ? resp.message : 'Failed to update balance.', 'error');
            }
          },
          error: function(xhr, status, error) {
            Swal.fire('Error', 'Communication error with server: ' + error, 'error');
          }
        });
      }
    });
  });

  // Filter in Allocation Modal
  $(document).on("input", "#searchAllocModalTable", function () {
    let filter = $(this).val().toLowerCase().trim();
    $("#tblStockAllocClients tbody tr").each(function () {
      let text = $(this).text().toLowerCase();
      $(this).toggle(!filter || text.indexOf(filter) > -1);
    });
  });

  // Ensure parent modal stays scrollable when child modal closes
  $('#stockAllocClientsModal').on('hidden.bs.modal', function () {
    if ($('#stockdataModal').hasClass('show') || $('#stockdataModal').is(':visible')) {
      $('body').addClass('modal-open');
    }
  });
  $(document).on("click", ".emergencycheckbox", function (e) {
    const $chk = $(this);
    const isChecking = $chk.is(":checked");

    // Cache baseline dates if not already saved
    if (!$chk.data("sowingdt")) {
      $chk.data("sowingdt", $("#basesowingdate").text());
      $chk.data("sowingRddt", $("#stkreadydt").val());
    }
    if (!$chk.data("sowingRddt") && $("#stkreadydt").val()) {
      $chk.data("sowingRddt", $("#stkreadydt").val());
    }

    if (isChecking) {
      // Revert checked state temporarily until user confirms
      $chk.prop("checked", false);

      const minDate = "<?php echo date('Y-m-d', strtotime('+1 day')); ?>";
      const maxDate = "<?php echo date('Y-m-d', strtotime('+3 days')); ?>";

      Swal.fire({
        title: 'Emergency Sowing Confirmation',
        html: `
          <div class="text-start p-1" style="font-size: 0.92rem;">
            <p class="mb-3 text-dark fw-semibold">
              Have you confirmed with both the <span class="text-primary">Sowing Department</span> and <span class="text-primary">Procurement Department</span> regarding availability?
            </p>
            <div class="p-3 bg-light rounded-3 border mb-3">
              <div class="d-flex align-items-center gap-2 mb-2 text-dark">
                <i class="fa fa-seedling text-success"></i> <span><strong>Seed Availability & Stock</strong></span>
              </div>
              <div class="d-flex align-items-center gap-2 mb-2 text-dark">
                <i class="fa fa-th-large text-primary"></i> <span><strong>Trays & Nursery Space Capacity</strong></span>
              </div>
              <div class="d-flex align-items-center gap-2 text-dark">
                <i class="fa fa-calendar-check text-info"></i> <span><strong>Emergency Sowing Timeline Feasibility</strong></span>
              </div>
            </div>

          <!-- Sowing Date -->
            <div class="mb-3">
              <label for="emergencySowingDate" class="form-label fw-bold text-dark mb-1">
                <i class="fa fa-calendar-alt text-primary me-1"></i>
                Sowing Date <span class="text-muted small fw-normal">(Next 3 days only)</span>
              </label>

              <input type="date"
                     id="emergencySowingDate"
                     class="form-control"
                     value="${minDate}"
                     min="${minDate}"
                     max="${maxDate}"
                     placeholder="Select Sowing Date"
                     autocomplete="off"
                      >
            </div>
            <div class="alert alert-warning border-0 p-2 rounded-2 mb-0 d-flex align-items-center gap-2" style="font-size: 0.85rem; background-color: #fef3c7; color: #92400e;">
              <i class="fa fa-exclamation-triangle flex-shrink-0"></i>
              <span>Please proceed only if both departments have verified and approved availability.</span>
            </div>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-check-circle me-1"></i> Yes, Confirmed & Proceed',
        cancelButtonText: '<i class="fa fa-times me-1"></i> Cancel',
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#64748b',
        focusConfirm: false,
        allowOutsideClick: false,
        customClass: {
          confirmButton: 'btn btn-primary px-4 py-2 fw-bold',
          cancelButton: 'btn btn-secondary px-4 py-2 fw-semibold'
        },
        preConfirm: () => {
          const selectedDate = $("#emergencySowingDate").val();
          if (!selectedDate) {
            Swal.showValidationMessage("Please select a sowing date.");
            return false;
          }
          if (selectedDate < minDate || selectedDate > maxDate) {
            Swal.showValidationMessage(`Please choose a sowing date within the next 3 days (${minDate} to ${maxDate}).`);
            return false;
          }
          return selectedDate;
        }
      }).then((result) => {
        if (result.isConfirmed) {
          $chk.prop("checked", true);
          const chosenSowingDate = result.value || $("#emergencySowingDate").val(); // YYYY-MM-DD
          const formattedSowingDate = changeDateFormatYmd_dmY(chosenSowingDate);
          $("#basesowingdate").html(formattedSowingDate);

          // Calculate growing days from baseline original sowing date to baseline ready date
          const origSowingStr = dmyToYmd($chk.data("sowingdt") || "");
          const origReadyStr = dmyToYmd($chk.data("sowingRddt") || "");

          let growingDays = 0;
          if (origSowingStr && origReadyStr) {
            const dSowing = new Date(origSowingStr + "T00:00:00");
            const dReady = new Date(origReadyStr + "T00:00:00");
            growingDays = Math.round((dReady - dSowing) / (1000 * 60 * 60 * 24));
          }

          if (growingDays > 0) {
            const finalDate = addDaysToDate(chosenSowingDate, growingDays);
            $("#stkreadydt").val(finalDate).attr("min", chosenSowingDate);
          } else {
            $("#stkreadydt").val(chosenSowingDate).attr("min", chosenSowingDate);
          }
          
        } else {
          $chk.prop("checked", false);
        }
      });
    } else {
      // Unchecked: restore baseline original sowing date
      $("#basesowingdate").html($chk.data("sowingdt"));

      // Restore baseline original ready date
      const sowingRddt = $chk.data("sowingRddt");
      if (sowingRddt) {
        $("#stkreadydt").val(sowingRddt).attr("min", sowingRddt);
      }
    }
  });
function addDaysToDate(ymdDate, days) {
    const date = new Date(ymdDate + "T00:00:00");
    date.setDate(date.getDate() + Number(days));
    return (
        date.getFullYear() + "-" +
        String(date.getMonth() + 1).padStart(2, "0") + "-" +
        String(date.getDate()).padStart(2, "0")
    );
}
function dmyToYmd(dateStr) {
    if (!dateStr) return '';
    dateStr = dateStr.trim();
    const parts = dateStr.split('-');
    if (parts.length !== 3) return '';
    // If already in YYYY-MM-DD format
    if (parts[0].length === 4) return dateStr;
    // If in DD-MM-YYYY format
    const [day, month, year] = parts;
    return `${year}-${month}-${day}`;
}
  function restoreEditedQuantity($row) {
    if($row.data("enquiry-allocation-pending")){$row.remove();window.dispatchEvent(new Event("po-enquiry-allocation-cancelled"));Invoicegrandtotalgstamont("totalamount","gsttotalamount","ttldiscountamount","grandtotalamount","payableamount_td","cgstamt_td","sgstamt_td","igstamt_td","distamt_td","provisionorderitemsTable");return;}
    const $input = $row.find(".qty_td");
    if (window.OrderModule.isConfirmed() && $row.data('confirmed-additional')) {
      $row.remove();
      Invoicegrandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','grandtotalamount','payableamount_td','cgstamt_td','sgstamt_td','igstamt_td','distamt_td','provisionorderitemsTable');
      return;
    }
    const previous = $input.data("edit-last-qty");
    if (previous !== undefined) $input.val(previous);
    const date = $input.data("old-date");
    if (date !== undefined) $row.find(".expecteddelivery").val(date);
    $row.removeData("edit-qty-pending").removeData("edit-product-changed").data("edit-qty-changed", false);
    $row.find(".ratecal").trigger("input");
  }
  function clearnotaccepted() {
    let $row = $("#productname_client").data("rowdata");
    if ($row && $row.data("edit-qty-pending")) {
      restoreEditedQuantity($row);
      $("#stockdataModal").modal("hide");
      return;
    }
    let totalRequiredQty = parseInt($row.find(".qty_td").val()) || 0;
    const productId = $row.find(".productname").val();
    const discount = $row.find(".distprt_td").val();
    const rate_td = $row.find(".rate_td").val();
    $row.find(".expecteddelivery").val("");
    $row.find(".stockdbid").val('');
    $row.find(".rate_td").val('');
    $row.find(".qty_td").val('');
    $row.find(".distprt_td").val('');
    $row.find(".distamt_td").val('');
    $row.find(".productname").val('').trigger("change");
    Invoicegrandtotalgstamont('totalamount', 'gsttotalamount', 'ttldiscountamount', 'grandtotalamount', 'payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'provisionorderitemsTable');
    $("#stockdataModal").modal("hide");
  }


  // function closemodalfunction() {
  //   let allqty = parseInt($("#allocatestock").val(), 10) || 0;
  //   let requiredqty = parseInt($("#requriedqty_client").val(), 10) || 0;
  //   let createstkqty = parseInt($("#balqty_client").val(), 10) || 0;

  //   // Strict Validation: Ensure existing stock covers the full required quantity
  //   // Unallocated Balance check logic:
  //   // If Allocated Qty is less than Required Qty, stop and ask to allocate from other production order.
  //   if (createstkqty > 0) {
  //     Swal.fire({
  //       icon: "error",
  //       title: "Insufficient Stock Allocation",
  //       html: `
  //               You have allocated <b>${allqty}</b> out of <b>${requiredqty}</b> required.<br>
  //               Remaining Quantity: <b>${createstkqty}</b><br><br>
  //               Please select another production order to fulfill the remaining quantity or ensure full allocation from available stock.
  //           `,
  //       confirmButtonText: "OK, I'll Select More",
  //       buttonsStyling: false,
  //       customClass: {
  //         confirmButton: 'btn btn-primary fw-bold px-4'
  //       }
  //     });
  //     return;
  //   }

  //   finalclosemodalfunction();
  // }


  function finalclosemodalfunction() {
    if (!updateSowingAllocationGate()) { alertmessage('warning', 'Sowing date could not be calculated. Please check the product, seed and growing-day settings.'); return; } 
    let $row = $("#productname_client").data("rowdata");
    if (!$row || !$row.length) return;
    // Clear stock reference from original row
    $row.find(".stockdbid").val("");
    let totalRequiredQty = parseInt($row.find(".qty_td").val(), 10) || 0;
    const productId = $row.find(".productname").val();
    const discount = $row.find(".distprt_td").val();
    const rate_td = $row.find(".rate_td").val();
    let rowCreated = false;
    let $lastTargetRow = null;
    let fromReadyStk = 0;
    /* 🔁 Allocate selected stock rows */
    $("#tblstockdatamaster tbody tr input[type='checkbox']:checked").each(function (i) {
      const $stkrow = $(this).closest("tr");
      const allotedQty = cleanNumber($stkrow.find(".alloted_stk").text());
      if (allotedQty <= 0) return;
      const expectedDelivery = $stkrow.data("expecteddelivery_stk") || "";
      const stkid = $stkrow.data("dbsno") || "";
      const readytosale_qty = cleanNumber($stkrow.data("readytosale_qty"));

      const orderStkQty = cleanNumber($stkrow.data("orderstkqty"));
      const confirmedQty = cleanNumber($stkrow.data("confirmed_allocated"));
      const unconfirmedQty = cleanNumber($stkrow.data("unconfirmed_allocated"));
      const allottedQty = cleanNumber(allotedQty);
      const availableQty = cleanNumber($stkrow.attr("data-availableqty") || $stkrow.find(".balance-display-val").text() || $stkrow.find(".avilableqty_stk").text());

      const actualStk =  orderStkQty  - (confirmedQty + unconfirmedQty)  + allottedQty;

      fromReadyStk = 0;

    if (actualStk > 0 && readytosale_qty >0) {

        fromReadyStk =  actualStk;
    }
      totalRequiredQty -= allotedQty;
      let $targetRow;
      if (!rowCreated) {
        $targetRow = $row;
      } else {
        $("#addRow").trigger("click");
        $targetRow = $("#provisionorderitemsTable tbody tr:last");
        $targetRow.addClass("row_ignore");
      }

      $targetRow.find(".productname").val(productId).trigger("change");
      $targetRow.find(".rate_td").val(rate_td);
      $targetRow.find(".qty_td").val(allotedQty);
      $targetRow.find(".expecteddelivery").val(expectedDelivery);
      $targetRow.find(".qty_td").data("edit-last-qty", allotedQty).data("old-date", expectedDelivery);
      $targetRow.data("edit-qty-changed", false).data("edit-emergency-sowing", $("#emergencycheckbox").is(":checked"));
      $targetRow.find(".distprt_td").val(discount);
      $targetRow.find(".stockdbid").val(stkid);
      $targetRow.find(".isreadytosale").val(fromReadyStk);
      $targetRow.find(".ratecal").trigger("input");
      rowCreated = true;
      $lastTargetRow = $targetRow;
    });
    /* ➕ Handle remaining quantity (new stock) */
    let remainingQty = Math.max(0, totalRequiredQty);
    $("#balqty_client").val(remainingQty);
    if (remainingQty > 0) {
      let $targetRow;
      if (rowCreated) {
        $("#addRow").trigger("click");
        $targetRow = $("#provisionorderitemsTable tbody tr:last");
      } else {
        $targetRow = $row;
      }
      
      $targetRow.addClass("row_ignore");
      $targetRow.find(".productname").val(productId).trigger("change");
      $targetRow.find(".rate_td").val(rate_td);
      $targetRow.find(".qty_td").val(remainingQty);
      $targetRow.find(".expecteddelivery").val($("#stkreadydt").val());
      $targetRow.find(".qty_td").data("edit-last-qty", remainingQty).data("old-date", $("#stkreadydt").val());
      $targetRow.data("edit-qty-changed", false).data("edit-emergency-sowing", $("#emergencycheckbox").is(":checked"));
      $targetRow.find(".distprt_td").val(discount);
      $targetRow.find(".stockdbid").val("");
      $targetRow.find(".isreadytosale").val('');
      $targetRow.find(".ratecal").trigger("input");
    }
    /* 🔄 Recalculate totals */
    Invoicegrandtotalgstamont(
      'totalamount',
      'gsttotalamount',
      'ttldiscountamount',
      'grandtotalamount',
      'payableamount_td',
      'cgstamt_td',
      'sgstamt_td',
      'igstamt_td',
      'distamt_td',
      'provisionorderitemsTable'
    );
    $row.data("edit-emergency-sowing", $("#emergencycheckbox").is(":checked"));
    $row.removeData("edit-qty-pending").removeData("edit-product-changed").data("edit-qty-changed", false);
    $row.find(".qty_td").data("edit-last-qty", $row.find(".qty_td").val()).data("old-date", $row.find(".expecteddelivery").val());
    if($row.data("enquiry-allocation-pending")){$row.removeData("enquiry-allocation-pending");window.dispatchEvent(new Event("po-enquiry-allocation-complete"));}
    /* ❌ Close modal */
    $("#stockdataModal").modal("hide");
  }

  function checkDuplicateProducts() {
    let productIds = [];
    let isDuplicate = false;
    let duplicateProduct = "";
    $(".productname").each(function () {
      let $row = $(this).closest("tr");
      // Ignore system-generated rows
      if ($row.hasClass("row_ignore")) return;
      let val = $(this).val() || "";
      if (val !== "") {
        if (productIds.includes(val)) {
          isDuplicate = true;
          duplicateProduct = val;
          $(this).addClass("is-invalid"); // visual feedback
        } else {
          productIds.push(val);
          $(this).removeClass("is-invalid");
        }
      }
    });
    if (isDuplicate) {
      Swal.fire({
        icon: "error",
        title: "Duplicate Product Detected",
        text: "The same product has been added more than once. Please correct it before proceeding.",
        confirmButtonText: "OK"
      });
      return false;
    }
    return true; // ✅ No duplicates
  }

  let sowingCalculationValid = false;
  let sowingCalculationRequest = 0;
  function updateSowingAllocationGate() {
    const required = Number($('#requriedqty_client').val()) || 0;
    let allocated = 0;
    $('#tblstockdatamaster .chkavqty:checked').each(function () { allocated += Number($(this).closest('tr').find('.alloted_stk').text()) || 0; });
    const blocked = required > allocated && !sowingCalculationValid;
    $('#savepaymentdatabtn').prop('disabled', blocked);
    $('#sowingAllocationWarning').text(blocked ? 'Sowing date unavailable. Please check seed growing days and product settings.' : '');
    return !blocked;
  }
  $(document).on('change input keyup', '#tblstockdatamaster .chkavqty, #tblstockdatamaster .alloted_stk', function () { setTimeout(updateSowingAllocationGate, 0); });
  function sowingcalculation() {
    sowingCalculationValid = false;
    const request = ++sowingCalculationRequest;
    updateSowingAllocationGate(); 
    $(".growingdaydiv").addClass("d-none");
    $("#growingday").text("");
    let $row = $("#productname_client").data("rowdata");
    if (!$row || !$row.length) return;
    let projectid = $("body").data("projectid");
    let productid = $row.find(".productname").val();
    let deliverydate = $row.find(".expecteddelivery").val(); // YYYY-MM-DD  
    let formData = new FormData();
    formData.append("projectid", projectid);
    formData.append("productid", productid);
    $("#basesowingdate").html('<span class="spinner-border spinner-border-sm" role="status"></span>');
    $.ajax({
      url: "modal/stockverification.php?action=fecthgrowingdate",
      type: "POST",
      data: formData,
      dataType: "json",
      contentType: false,
      processData: false,
      cache: false,
      success: function (res) {
        if (request !== sowingCalculationRequest) return;
        // console.log(res);
        if (res && res.length > 0) {    
          if(parseInt(res[0].growingdays||0) <=0){
            $(".growingdaydiv").removeClass("d-none");
            $("#growingday").text("Sowing growing days are not configured for this product. Please update the master.");

          }

          sowingCalculationValid = res[0].valid_sowing === true;
          updateSowingAllocationGate();
          if (!sowingCalculationValid) alertmessage('warning', res[0].message || 'Sowing date could not be calculated. Please check the product, seed and growing-day settings.');
          let expecteddeliverydate = res[0].expecteddeliverydate;
          let rawSowingDate = res[0].sowingdate || res[0].rootsowingdate || "";
          let formattedSowingDate = (typeof window.changeDateFormatYmd_dmY === "function")
            ? window.changeDateFormatYmd_dmY(rawSowingDate)
            : (typeof changeDateFormatYmd_dmY === "function" ? changeDateFormatYmd_dmY(rawSowingDate) : rawSowingDate);

          if (expecteddeliverydate) {
            $("#stkreadydt").val(expecteddeliverydate);
            $("#stkreadydt").attr("min", expecteddeliverydate);
          }
          if (formattedSowingDate) {
            $("#basesowingdate").html(formattedSowingDate);
          } else {
            $("#basesowingdate").html("N/A");
          } 
 
        } else {
          sowingCalculationValid = false; updateSowingAllocationGate();
          alertmessage('warning', 'Sowing date could not be calculated. Please check the product, seed and growing-day settings.');
          $("#basesowingdate").html("N/A");
          $(".emergencycheckbox").prop("checked", false);
         $(".emergencycheckbox").removeData("sowingdt");
         $(".emergencycheckbox").removeData("sowingRddt");
          $("#stockdataModal").modal("show");
        }
      },

      error: function () {
        if (request !== sowingCalculationRequest) return;
        sowingCalculationValid = false; updateSowingAllocationGate();
        $("#basesowingdate").html("N/A");
        alertmessage('warning', 'Unable to calculate sowing date. Please check the product and seed settings.');
      }
    });
  }

  function mergeDuplicateProductsInTable(tableId, productid) {
    let productMap = {}; // Track only the specific product
    $("#" + tableId + " tbody tr").each(function () {
      let $row = $(this);
      let currentProductId = $row.find(".productname").val();
      if (currentProductId != productid) return;
      let qty = parseFloat($row.find(".qty_td").val()) || 0;
      if (!productMap[productid]) {
        // first matching row
        productMap[productid] = {
          mainRow: $row,
          totalQty: qty,
          duplicates: []
        };
      } else {
        // duplicate row → add qty and mark removal
        productMap[productid].totalQty += qty;
        productMap[productid].duplicates.push($row);
      }
    });

    // If no duplicates found → nothing to do
    if (!productMap[productid]) return;

    // Apply merge
    let item = productMap[productid];

    // Update total quantity in first row
    item.mainRow.find(".qty_td").val(item.totalQty);
    item.mainRow.find(".ratecal").trigger("input");
    item.mainRow.find(".stockdbid").val("");
    item.mainRow.find(".expecteddelivery").val("");
    item.mainRow.find(".isreadytosale").val("");

    // Remove all duplicates
    item.duplicates.forEach($row => $row.remove());
  }
  function closemodaldirectly() {
    // 🛑 Removed clearing of expecteddelivery to preserve user's choice
    // let $row = $("#productname_client").data("rowdata");
    // $row.find(".expecteddelivery").val("");
    $("#stockdataModal").modal("hide");
  }
  function confirmmessage(title1, title2) {
    return Swal.fire({
      title: title1,
      text: title2,
      showDenyButton: true,
      confirmButtonText: "YES",
      denyButtonText: "NO"
    }).then((result) => {
      return result.isConfirmed;   // true = YES, false = NO
    });
  }  

  // --- Dynamic Enquiry/Lead Importing & Client Checker Logic ---
  function openProductEnquiryModal() { 
    enquiryStates($("#stateid").val() || $("#statename option:selected").data("stateid") || "");

    $('input[name="priority"][value="Low"]').prop("checked", true);
    $('#enq_clientid').val($('#clientid').val() || '');
    $('#enq_mobileno').val($('#mobileno').val() || '');
    $('#enq_clientname').val($('#clientname').val() || '');
    $('#newProductEnquiryModal').data('linked-client-name', String($('#enq_clientname').val()).trim()).data('linked-client-mobile', String($('#enq_mobileno').val()).trim());
    $('#enq_emailid').val($('#emailid').val() || '');

    $('#enq_stateid').val($('#stateid').val() || '');

    $('#enq_districtid').val($('#districtid').val() || '');
    $('#enq_cityname').val($('#cityname').val() || '');
    $('#enq_address').val($('#delivery_address').val() || '');

    // Reset enquiry inputs
    window.EnquiryGrid.reset();
    $('#enq_remarks').val('');

    // Open modal supporting both Bootstrap 4 and Bootstrap 5
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal && typeof bootstrap.Modal.getInstance === 'function') {
      try {
        var modalEl = document.getElementById('newProductEnquiryModal');
        var modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        window.enquiryModalInstance = modalInstance;
        modalInstance.show();
      } catch (e) {
        $('#newProductEnquiryModal').modal('show');
      }
    } else {
      $('#newProductEnquiryModal').modal('show');
    }
  }

  $(document).on('input change', '#enq_clientname, #enq_mobileno', function () {
    const $modal = $('#newProductEnquiryModal');
    if (String($('#enq_clientname').val()).trim() !== $modal.data('linked-client-name') ||
        String($('#enq_mobileno').val()).trim() !== $modal.data('linked-client-mobile')) {
      $('#enq_clientid').val('');
    }
  });

  function closeProductEnquiryModal() {
    const $modal = $('#newProductEnquiryModal');
    const modalEl = $modal[0];
    if (!modalEl) return;
    // Use the instance that actually opened the enquiry.
    const jqInstance = $modal.data('bs.modal');
    const instance = jqInstance || window.enquiryModalInstance ||
      (window.bootstrap && bootstrap.Modal && typeof bootstrap.Modal.getInstance === 'function'
        ? bootstrap.Modal.getInstance(modalEl) : null);
    if (instance && typeof instance.hide === 'function') instance.hide();
    else if (typeof $.fn.modal === 'function') $modal.modal('hide');
    // Older/mixed Bootstrap scripts can leave a visible modal without an instance.
    setTimeout(function () {
      if ($modal.hasClass('show') || $modal.hasClass('in') || $modal.is(':visible')) {
        $modal.removeClass('show in').hide().attr('aria-hidden', 'true').removeAttr('aria-modal');
        $modal.trigger('hidden.bs.modal');
      }
      if (!$('.modal.show, .modal.in').length) {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
      }
      window.enquiryModalInstance = null;
    }, 400);
  }

function getStateOptions(selectedStateId = "") {

    selectedStateId = String(selectedStateId ?? "").trim();

    let options = `<option value="" ${!selectedStateId ? "selected" : ""}>
        -- Select State --
    </option>`;

    const usedStates = new Set();

    stateDistrictArray.forEach(item => {

        if (usedStates.has(String(item.stateid))) {
            return;
        }

        usedStates.add(String(item.stateid));

        const selected =
            selectedStateId &&
            String(item.stateid) === String(selectedStateId)
                ? "selected"
                : "";

        options += `
            <option
                value="${item.stateid}"
                data-name="${item.statename}"
                data-hindi="${item.statename_hn || ''}"
                ${selected}
            >
                ${item.statename}
            </option>
        `;
    });

    return options;
}
  function getDistrictOptions(stateid, selectedDistrictId = "") {

    let options = `<option value="">-- Select District --</option>`;

    if (!stateid) {
        return options;
    }

    stateDistrictArray.forEach(item => {

        if (String(item.stateid) !== String(stateid)) {
            return;
        }

        const selected =
            String(item.districtid) === String(selectedDistrictId)
                ? "selected"
                : "";

        options += `
            <option
                value="${item.districtid}"
                data-name="${item.districtname}"
                data-hindi="${item.districtname_hn || ''}"
                ${selected}
            >
                ${item.districtname}
            </option>
        `;
    });

    return options;
}
 $(document).on("change", ".del_state", function () {

    const $row = $(this).closest("tr");

    const stateId = $(this).val();
    const stateName = $(this).find("option:selected").data("name") || "";

    $row.find(".delivery_stateid").val(stateId);
    $row.find(".delivery_state").val(stateName);

    const $district = $row.find(".del_district");

    $district.html(
        getDistrictOptions(stateId, "")
    );

    // Since first district is automatically selected
    const districtId = $district.val() || "";
    const districtName =
        $district.find("option:selected").data("name") || "";

    $row.find(".delivery_district_id").val(districtId);
    $row.find(".delivery_district").val(districtName);
});
 

$(document).on("change", ".del_district", function () {

    const $row = $(this).closest("tr");

    const districtid = $(this).val();
    const districtName = $(this).find("option:selected").data("name") || "";

    $row.find(".delivery_district_id").val(districtid);
    $row.find(".delivery_district").val(districtName);
});

function findStateDistrictIds(stateName, districtName) {

    const clean = value => String(value || '').trim().toLowerCase();

    const stateText = clean(stateName);
    const districtText = clean(districtName);

    let stateid = '';
    let districtid = '';

    // Find State - English OR Hindi
    const stateRow = stateDistrictArray.find(item =>
        clean(item.statename) === stateText ||
        clean(item.statename_hn) === stateText
    );

    if (stateRow) {
        stateid = stateRow.stateid;
    }

    // Find District only inside selected State
    if (stateid && districtText) {

        const districtRow = stateDistrictArray.find(item =>
            String(item.stateid) === String(stateid) &&
            (
                clean(item.districtname) === districtText ||
                clean(item.districtname_hn) === districtText
            )
        );

        if (districtRow) {
            districtid = districtRow.districtid;
        }
    }

    return {
        stateid,
        districtid
    };
}
  //ajay

    function initModalSelect2() {
        if (window.cleanEnquirySearchSelect) window.cleanEnquirySearchSelect();
        $('#enq_statename, #enq_districtname').each(function () {
            const $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
            $select.prop('multiple', false).select2({
                dropdownParent: $('#newProductEnquiryModal'), width: '100%',
                multiple: false, closeOnSelect: true,
                templateResult: function (item) { return item.text; },
                templateSelection: function (item) { return item.text; }
            });
        });
    }
    function enquiryDistricts(stateid, selectedid) {
        const $district = $('#enq_districtname').empty().append(new Option('Select District', ''));
        const seen = new Set();
        stateDistrictArray.forEach(function (row) {
            const id = String(row.districtid || '');
            if (String(row.stateid) !== String(stateid) || !id || seen.has(id)) return;
            seen.add(id); $district.append(new Option(row.districtname, id));
        });
        $district.val(String(selectedid || '')).trigger('change.select2');
        $('#enq_districtid').val($district.val() || '');
    }
    $(document).on('change', '#enq_statename', function () {
        $('#enq_stateid').val($(this).val() || '');
        enquiryDistricts($(this).val(), '');
        updateEnquiryAddress();
    });
    $(document).on('change', '#enq_districtname', function () {
        $('#enq_districtid').val($(this).val() || '');
        updateEnquiryAddress();
    });
    function updateEnquiryAddress() {
        const parts = [$('#enq_cityname').val(),
            $('#enq_districtname').val() ? $('#enq_districtname option:selected').text() : '',
            $('#enq_statename').val() ? $('#enq_statename option:selected').text() : ''];
        $('#enq_address').val(parts.map(function(value){return String(value || '').trim();}).filter(Boolean).join(', '));
    }
    $(document).on('input change', '#enq_cityname', updateEnquiryAddress);
    function enquiryStates(selectedid = '') {
        const $state = $('#enq_statename').empty().append(new Option('Select State', ''));
        const seen = new Set();
        stateDistrictArray.forEach(function (row) {
            const id = String(row.stateid || '');
            if (!id || seen.has(id)) return;
            seen.add(id); $state.append(new Option(row.statename, id));
        });
        $state.val(String(selectedid || '')).trigger('change.select2');
        $('#enq_stateid').val($state.val() || '');
        enquiryDistricts($state.val(), $('#districtid').val());
    }
    $('#newProductEnquiryModal').on('shown.bs.modal.enquirySelect', function () {
        initModalSelect2();
        if (window.EnquiryGrid && window.EnquiryGrid.initialize) window.EnquiryGrid.initialize();
    }).on('hide.bs.modal.enquirySelect', function () {
        $(this).find('.select2-hidden-accessible').each(function () { $(this).select2('close'); });
    });

  function fetches_states() {     
    if (stateDistrictArray .length > 0) return;
      $.ajax({
          url: "../krf_provisional_sale_order_edit/modal/direct_sowing.php?action=getstate_district",
          type: "POST",
          dataType: "json",
          success: function (res) {
              const $stateSelect = $("#enq_statename");
              $stateSelect.empty();

              $stateSelect.append(
                  `<option value="">Select State</option>`
              );

              if (res.status === "success" && Array.isArray(res.data)) {
                    stateDistrictArray = res.data;
              }
          },

          error: function (xhr, status, error) {
              console.log("State loading error:", error);
          }
      });
  }

    /* ========== STEP 1 =========== */
    function loadBoxPackingMaster() {
        const projectid = $("#hidden_projectid").val();
        return $.ajax({
            url: "modal/box_packquery.php?action=getboxpackingfreightmaster",
            type: "POST",
            dataType: "json",
            data: {
                projectid: projectid
            }
        }).then(function (res) {
            if (res.status !== "success") {
                boxPackingMaster = [];
                throw new Error(res.message || "Unable to load Box Packing Master.");
            }
            boxPackingMaster = res.data || [];
            return boxPackingMaster;
        });
    }

   /* ================= STEP 2 EVERY PRODUCT ROW========= */
    window.fillEditBoxRows = fillboxdata;
    async function fillboxdata() {
        try {
            /* Load master first */
            if (boxPackingMaster.length === 0) {
                await loadBoxPackingMaster();
            }
            const stateOptions = getBoxStateOptions();
            let rowsHtml = "";
            let rowCounter = 0;
            $("#provisionorderitemsTable tbody tr").each(function () {
                const $sourceRow = $(this);
                const $productSelect = $sourceRow.find(".productname");
                const productId = $productSelect.val();
                const productName = $productSelect.find("option:selected").text().trim();
                const qtyText = $sourceRow.find(".qty_td").val().trim();
                const disp_date =$sourceRow.find(".expecteddelivery").val();
                const qty = parseFloat(qtyText) || 0;
                if (!productId) {
                    return true;
                }
                rowCounter++;
                const rowId =
                    "boxrow_" +
                    Date.now() +
                    "_" +
                    rowCounter;
                rowsHtml += `
                    <tr
                        id="${rowId}"
                        class="box-delivery-row"
                        data-productid="${escapeHtml(productId)}"
                        data-productqty="${qty}"                 

                    >

                        <td class="text-center">
                            ${rowCounter}
                        </td>


                        <td>
                            <strong>
                                ${escapeHtml(productName)}
                            </strong>
                            <input
                                type="hidden"
                                class="box_productname"
                                name="box_productname[]"
                                value="${escapeHtml(productName)}"
                            >

                            <input
                                type="hidden"
                                class="box_productid"
                                 name="box_productid[]"
                                value="${escapeHtml(productId)}"
                            >
                        </td>
                         <td>
                            <strong class="boxdate">
                                ${escapeHtml(disp_date)}
                            </strong>
                        </td>
                        <td class="text-center product_qty">
                            ${qty}

                            <input
                                type="hidden"
                                class="box_product_qty"
                                 name="box_product_qty[]"
                                value="${escapeHtml(qty)}"
                            >
                        </td>
                        <td>
                            <select
                                class="form-control state_box"
                                name="state_box[]"
                                required
                            >
                                ${stateOptions}
                            </select>
                           <input
                              type="hidden"
                              class="box_state_name"
                              name="box_state_name[]"
                              value=""
                          >
                          <input
                                      type="hidden"
                                      class="box_state_id"
                                      name="box_state_id[]"
                                      value=""
                                  >
                        </td>


                        <td>
                            <select
                                class="form-control district_box" 
                                 name="district_box[]"
                                required
                                disabled
                            >
                                <option value="">
                                    -- Select District --
                                </option>
                            </select>

                           <input
                              type="hidden"
                              class="box_district_name"
                              name="box_district_name[]"
                              value=""
                          >
                           <input
                              type="hidden"
                              class="box_district_id"
                              name="box_district_id[]"
                              value=""
                          >
                        </td>


                        <td>
                            <select
                                class="form-control city_box" 
                                 name="city_box[]"
                                required
                                disabled
                            >
                                <option value="">
                                    -- Select Location --
                                </option>
                            </select>


                           <input
                              type="hidden"
                              class="box_city_name"
                              name="box_city_name[]"
                              value=""
                          >
                           <input
                              type="hidden"
                              class="box_city_id"
                              name="box_city_id[]"
                              value=""
                          >
                        </td>


                        <td class="text-center">

                            <input
                                type="number"
                                class="form-control plants_per_box"
                                 name="plants_per_box[]"
                                value="500"
                                min="1"
                                step="1"
                                readonly
                            >
                        </td>
                        <td>
                            <input
                                type="number"
                                class="form-control no_of_box"
                                 name="no_of_box[]"
                                value="${qty > 0 ? Math.ceil(qty / 500) : 0}"
                                min="0"
                                step="1" 
                            >
                        </td> 

                        <td>
                              <select
                                  class="form-control pickuppoint"
                                  name="pickuppoint[]"
                                  required
                                  disabled
                              >
                                  <option value="">
                                      -- Select Location First --
                                  </option>
                              </select>
                          </td>

                        <!-- CHARGES PER PACK 
                       <td>
                            <select
                                class="form-control freighcharges"
                                 name="box_freighcharges[]"
                                disabled
                            >
                                <option value="">
                                    -- Select Location --
                                </option>
                            </select>
                        </td>-->

                    <td>
                        <input
                            type="number"
                            class="form-control freighcharges"
                            name="box_freighcharges[]"
                            value="0.00"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                            readonly
                        >
                    </td>


                          <!-- AMOUNT -->
                          <td>

                              <input
                                  type="number"
                                  class="form-control freightamount"
                                  name="box_freightamount[]"
                                  value="0.00"
                                  min="0"
                                  step="0.01"
                                  readonly
                              >

                          </td>

                      <td class="text-center">
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input apply_box_all"
                                       style="width:18px; height:18px; cursor:pointer;">

                                 
                            </div>
                        </td>                  

                    </tr>
                `;

            });


            /* Clear old generated rows first */
            $("#deliveryboxtbl tbody").empty();


            if (rowCounter === 0) {

                $("#deliveryboxtbl tbody").html(`
                    <tr>
                        <td colspan="11"
                            class="text-center text-muted">
                            No product found.
                        </td>
                    </tr>
                `);

                return;
            }


            /*
             * IMPORTANT:
             * If source table contains 2 products,
             * rowsHtml contains 2 <tr>.
             */
            $("#deliveryboxtbl tbody").html(rowsHtml);


        } catch (error) {

            console.error(error);

            Swal.fire(
                "Error",
                error.message || "Unable to prepare Box Packing data.",
                "error"
            );

        }
    }

    function loadPickupPointOptions($row) {

      const $locationOption =
          $row.find(".city_box option:selected");

      const locationId =
          $locationOption.val();

      const $pickup =
          $row.find(".pickuppoint");


      // No location selected
      if (!locationId) {

          $pickup
              .html(`
                  <option value="">
                      -- Select Location First --
                  </option>
              `)
              .prop("disabled", true);

          return;
      }


      const airportCharge =
          parseFloat(
              $locationOption.data("airport")
          ) || 0;

      const busCharge =
          parseFloat(
              $locationOption.data("bus")
          ) || 0;

      const railwayCharge =
          parseFloat(
              $locationOption.data("railway")
          ) || 0;


      let html = `
          <option value="">
              -- Select Pickup Point --
          </option>
      `;


      // Bus
      if (busCharge > 0) {

          html += `
              <option
                  value="Bus"
                  data-charge="${busCharge}"
              >
                  Bus Stop (${busCharge.toFixed(2)})
              </option>
          `;
      }


      // Railway
      if (railwayCharge > 0) {

          html += `
              <option
                  value="Railway"
                  data-charge="${railwayCharge}"
              >
                  Railway Station (${railwayCharge.toFixed(2)})
              </option>
          `;
      }


      // Airport
      if (airportCharge > 0) {

          html += `
              <option
                  value="Airport"
                  data-charge="${airportCharge}"
              >
                  Airport (${airportCharge.toFixed(2)})
              </option>
          `;
      }


      // No pickup charge defined
      if (
          busCharge <= 0 &&
          railwayCharge <= 0 &&
          airportCharge <= 0
      ) {

          html = `
              <option value="">
                  No Pickup Point Available
              </option>
          `;

          $pickup
              .html(html)
              .prop("disabled", true);

          return;
      }


      $pickup
          .html(html)
          .prop("disabled", false);
  }

     function loadFreightChargeOptions($row) {

      const pickupType =
          $row.find(".pickuppoint").val() || "";

      const $locationOption =
          $row.find(".city_box option:selected");

      const locationId =
          $locationOption.val();

      const $freight =
          $row.find(".freighcharges");


      // -----------------------------------------
      // No location
      // -----------------------------------------
      if (!locationId) {

          $freight
              .html(`
                  <option value="">
                      -- Select Location --
                  </option>
              `)
              .prop("disabled", true);

          $row.find(".freightamount").val("0.00");

          return;
      }


      // -----------------------------------------
      // Rates from selected State/District/Location
      // -----------------------------------------
      const airportCharge =
          parseFloat($locationOption.data("airport")) || 0;

      const busCharge =
          parseFloat($locationOption.data("bus")) || 0;

      const railwayCharge =
          parseFloat($locationOption.data("railway")) || 0;


      // -----------------------------------------
      // Pickup related default rate
      // -----------------------------------------
      let selectedCharge = null;

      switch (pickupType) {

          case "Airport":
              selectedCharge = airportCharge;
              break;

          case "Bus":
              selectedCharge = busCharge;
              break;

          case "Railway":
              selectedCharge = railwayCharge;
              break;
      }


      // -----------------------------------------
      // DISTINCT available rates
      // -----------------------------------------
      const distinctCharges = [
          ...new Set([
              airportCharge,
              busCharge,
              railwayCharge
          ].map(v => Number(v)))
      ];


      let html = "";


      // No Pickup Point selected
      if (selectedCharge === null) {

          html += `
              <option value="" selected>
                  -- Select Charges --
              </option>
          `;
      }


      // -----------------------------------------
      // Selected Pickup rate FIRST
      // -----------------------------------------
      if (selectedCharge !== null) {

          html += `
              <option
                  value="${selectedCharge}"
                  selected
              >
                  ${Number(selectedCharge).toFixed(2)}
              </option>
          `;
      }


      // -----------------------------------------
      // Remaining distinct rates
      // -----------------------------------------
      distinctCharges.forEach(function (charge) {

          if (
              selectedCharge !== null &&
              Number(charge) === Number(selectedCharge)
          ) {
              return;
          }

          html += `
              <option value="${charge}">
                  ${Number(charge).toFixed(2)}
              </option>
          `;
      });


      // -----------------------------------------
      // IMPORTANT
      // Rebuild dropdown completely
      // -----------------------------------------
      $freight
          .html(html)
          .prop("disabled", false);


      // Explicitly set selected value
      if (selectedCharge !== null) {

          $freight.val(
              String(selectedCharge)
          );
      }


      // -----------------------------------------
      // Recalculate amount
      // -----------------------------------------
      calculateBoxFreightAmount($row);
  }


    /* ================ UNIQUE STATE OPTIONS ========== */
    function getBoxStateOptions() {
        let html = `<option value="">-- Select State --</option>`;
        const unique = new Map();
        boxPackingMaster.forEach(function (item) {
            if (!unique.has(item.stateid)) {
                unique.set(item.stateid, item.statename);
            }

        });
        unique.forEach(function (stateName, stateId) {
            html += `
                <option value="${escapeHtml(stateId)}">
                    ${escapeHtml(stateName)}
                </option>
            `;
        });
        return html;
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    } 
    function getBoxLocationOptions(stateid, districtid) {

    let html = `<option value="">-- Select Location --</option>`;

    boxPackingMaster
        .filter(item =>
            String(item.stateid) === String(stateid) &&
            String(item.districtid) === String(districtid)
        )
        .forEach(function (item) {

            html += `
                <option
                    value="${escapeHtml(item.id)}"

                    data-location="${escapeHtml(item.location)}"

                    data-plantsperbox="${
                        parseInt(item.plants_per_box || 500)
                    }"

                    data-airport="${
                        parseFloat(item.airport_charges || 0)
                    }"

                    data-bus="${
                        parseFloat(item.bus_charges || 0)
                    }"

                    data-railway="${
                        parseFloat(item.railway_charges || 0)
                    }"
                >
                    ${escapeHtml(item.location)}
                </option>
            `;
        });

    return html;
}
    $(document).on("change", ".state_box", function () {

    const $row = $(this).closest("tr");

    const stateid = $(this).val();
    const stateName = $(this).find("option:selected").text().trim();

    // Store State Name
    $row.find(".box_state_name").val(stateName);
    $row.find(".box_state_id").val(stateid);

    const $district = $row.find(".district_box");
    const $location = $row.find(".city_box");


    // Reset dependent fields
    $district
        .prop("disabled", true)
        .html(`
            <option value="">
                -- Select District --
            </option>
        `);

    $location
        .prop("disabled", true)
        .html(`
            <option value="">
                -- Select Location --
            </option>
        `);


    resetBoxFreightRow($row);


    if (!stateid) {
        return;
    }


    $district
        .html(getBoxDistrictOptions(stateid))
        .prop("disabled", false);

});
    $(document).on("change", ".district_box", function () {

    const $row = $(this).closest("tr");
    const stateid =  $row.find(".state_box").val();
    const districtid =  $(this).val();
    const $location = $row.find(".city_box");
    const districtName = $(this).find("option:selected").text().trim();
    // Store State Name
    $row.find(".box_district_name").val(districtName);
    $row.find(".box_district_id").val(districtid);


    $location
        .prop("disabled", true)
        .html(`
            <option value="">
                -- Select Location --
            </option>
        `);


    resetBoxFreightRow($row);


    if (!stateid || !districtid) {
        return;
    }


    $location
        .html(
            getBoxLocationOptions(
                stateid,
                districtid
            )
        )
        .prop("disabled", false);

}); 

   /* $(document).on("change", ".city_box", function () {
      const $row = $(this).closest("tr"); 
      const $option = $(this).find("option:selected");
      const cityName = $(this).find("option:selected").text().trim();

      const locationid =  $(this).val();
       $row.find(".box_city_name").val(cityName);
       $row.find(".box_city_id").val(locationid);

      prepareBoxDeliveryRow($row);

      loadPickupPointOptions($row);

      // Reset charge until pickup point selected
      $row.find(".freighcharges")
          .val("0.00")
          .prop("readonly", true);

      $row.find(".freightamount")
          .val("0.00");
  });*/

  $(document).on("change", ".city_box", function () {
    const $row = $(this).closest("tr");
    const cityId   = $(this).val();
    const cityName = $(this).find("option:selected").text().trim();
    $row.find(".box_city_name").val(cityName);
    $row.find(".box_city_id").val(cityId);
    prepareBoxDeliveryRow($row);
    loadPickupPointOptions($row);
    // Reset charge until pickup point selected
    $row.find(".freighcharges")
        .val("0.00")
        .prop("readonly", true);

    $row.find(".freightamount")
        .val("0.00");
});
  function calculateBoxFreightAmount($row) {

    const noOfBoxes =
        parseFloat(
            $row.find(".no_of_box").val()
        ) || 0;

    const chargePerPack =
        parseFloat(
            $row.find(".freighcharges").val()
        ) || 0;

    const amount =
        noOfBoxes * chargePerPack;

    $row.find(".freightamount")
        .val(amount.toFixed(2));
}

  $(document).on(
    "input change",
    ".freighcharges",
    function () {

        const $row = $(this).closest("tr");

        calculateBoxFreightAmount($row);
    }
);

    function prepareBoxDeliveryRow($row) {

    const $location =
        $row.find(".city_box");

    const $option =
        $location.find("option:selected");


    const masterId =
        parseInt($option.val()) || 0;


    if (!masterId) {

        resetBoxFreightRow($row);

        return;
    }


    // ----------------------------------------
    // Master values
    // ----------------------------------------

    const plantsPerBox =
        parseInt(
            $option.data("plantsperbox")
        ) || 500;


    /*
     * IMPORTANT:
     * This freight is resolved from
     * State + District + Location.
     */
    const freightPerPack =
        parseFloat(
            $option.data("freight")
        ) || 0;


    // ----------------------------------------
    // Product quantity
    // ----------------------------------------

    const qty =
        parseFloat(
            $row.data("productqty")
        ) || 0;


    // ----------------------------------------
    // Number of boxes
    // ----------------------------------------

    const noOfBoxes =
        qty > 0 && plantsPerBox > 0
            ? Math.ceil(qty / plantsPerBox)
            : 0;


    // ----------------------------------------
    // Update box fields
    // ----------------------------------------

    $row
        .find(".plants_per_box")
        .val(plantsPerBox);


    $row
        .find(".no_of_box")
        .val(noOfBoxes);


    // ----------------------------------------
    // Charges Per Pack
    // ----------------------------------------

    const $freight =
        $row.find(".freighcharges");


    if (freightPerPack > 0) {

        $freight
            .html(`
                <option
                    value="${freightPerPack}"
                    selected
                >
                    ${freightPerPack.toFixed(2)}
                </option>
            `)
            .prop("disabled", false);

    } else {

        $freight
            .html(`
                <option value="0">
                    No Rate Defined
                </option>
            `)
            .prop("disabled", false);
    }


    // ----------------------------------------
    // Store master information
    // ----------------------------------------

    $row.attr(
        "data-boxmasterid",
        masterId
    );


    // ----------------------------------------
    // Calculate amount
    // ----------------------------------------

    calculateBoxFreightAmount($row);
}

function calculateBoxFreightAmount($row) {

    const noOfBoxes =
        parseFloat(
            $row.find(".no_of_box").val()
        ) || 0;


    const chargePerPack =
        parseFloat(
            $row.find(".freighcharges").val()
        ) || 0;


    const amount =
        noOfBoxes * chargePerPack;


    $row
        .find(".freightamount")
        .val(
            amount.toFixed(2)
        );
}
$(document).on(
    "input change",
    ".no_of_box, .freighcharges",
    function () {

        const $row =
            $(this).closest("tr");

        calculateBoxFreightAmount($row);
    }
);
function resetBoxFreightRow($row) {

    $row
        .find(".plants_per_box")
        .val(500);


    const qty =
        parseFloat(
            $row.data("productqty")
        ) || 0;


    const defaultBoxes =
        qty > 0
            ? Math.ceil(qty / 500)
            : 0;


    $row
        .find(".no_of_box")
        .val(defaultBoxes);


    $row
        .find(".freighcharges")
        .html(`
            <option value="0">
                -- Select Location First --
            </option>
        `)
        .prop("disabled", true);


    $row
        .find(".freightamount")
        .val("0.00");


    $row.removeAttr(
        "data-boxmasterid"
    );
}
/*$(document).on("change", ".pickuppoint", function () {

    const $row =
        $(this).closest("tr");

    updatePickupCharge($row);

});*/

$(document).on("change", ".pickuppoint", function () {

    const $row =
        $(this).closest("tr");

    loadFreightChargeOptions($row);

});
$(document).on("change", ".pickuppoint", function () {

    const $row = $(this).closest("tr");

    const pickupPoint = $(this).val();

    const selectedCharge =
        parseFloat(
            $(this)
                .find("option:selected")
                .data("charge")
        ) || 0;

    const $freight =
        $row.find(".freighcharges");

    if (!pickupPoint) {

        $freight
            .val("0.00")
            .prop("readonly", true);

        $row.find(".freightamount")
            .val("0.00");

        return;
    }

    // Default rate from master,
    // but operator can change it.
    $freight
        .val(selectedCharge.toFixed(2))
        .prop("readonly", false);

    calculateBoxFreightAmount($row);
});
/*function updatePickupCharge($row) {

    const pickupType =
        $row.find(".pickuppoint").val();

    const $option =
        $row.find(".city_box option:selected");


    let pickupCharge = 0;


    if (!$option.val()) {

        $row.data(
            "pickupcharge",
            0
        );

        return;
    }


    switch (pickupType) {

        case "Airport":

            pickupCharge =
                parseFloat(
                    $option.data("airport")
                ) || 0;

            break;


        case "Bus":

            pickupCharge =
                parseFloat(
                    $option.data("bus")
                ) || 0;

            break;


        case "Railway":

            pickupCharge =
                parseFloat(
                    $option.data("railway")
                ) || 0;

            break;
    }


    $row.data(
        "pickupcharge",
        pickupCharge
    );
}*/

 $(document).on("change", ".apply_box_all", function () {

    const $checkbox = $(this);

    if (!this.checked) {
        return;
    }

    const $sourceRow = $checkbox.closest("tr");

    const pickupType =
        $sourceRow.find(".pickuppoint").val() || "";

    const stateId =
        $sourceRow.find(".state_box").val() || "";

    const districtId =
        $sourceRow.find(".district_box").val() || "";

    const locationId =
        $sourceRow.find(".city_box").val() || "";


    // ----------------------------------------
    // Validation
    // ----------------------------------------

    if (!stateId) {

        Swal.fire(
            "Required",
            "Please select State first.",
            "warning"
        );

        $checkbox.prop("checked", false);

        return;
    }


    if (!districtId) {

        Swal.fire(
            "Required",
            "Please select District first.",
            "warning"
        );

        $checkbox.prop("checked", false);

        return;
    }


    if (!locationId) {

        Swal.fire(
            "Required",
            "Please select Location first.",
            "warning"
        );

        $checkbox.prop("checked", false);

        return;
    }


    // ----------------------------------------
    // Apply to all other rows
    // ----------------------------------------

    $("#deliveryboxtbl tbody .box-delivery-row")
        .not($sourceRow)
        .each(function () {

            const $row = $(this);


            // --------------------------------
            // State
            // --------------------------------

            $row
                .find(".state_box")
                .val(stateId);


            // --------------------------------
            // District
            // --------------------------------

            $row
                .find(".district_box")
                .html(
                    getBoxDistrictOptions(stateId)
                )
                .prop("disabled", false)
                .val(districtId);


            // --------------------------------
            // Location
            // --------------------------------

            $row
                .find(".city_box")
                .html(
                    getBoxLocationOptions(
                        stateId,
                        districtId
                    )
                )
                .prop("disabled", false)
                .val(locationId);


            // --------------------------------
            // Pickup Point
            // --------------------------------

            $row
                .find(".pickuppoint")
                .val(pickupType);


            // --------------------------------
            // Recalculate Box Qty etc.
            // --------------------------------

            prepareBoxDeliveryRow($row);


            // --------------------------------
            // Populate Freight Charges
            //
            // Pickup selected value becomes
            // default, but all distinct rates
            // remain in dropdown.
            // --------------------------------

            loadFreightChargeOptions($row);

        });


    Swal.fire({
        icon: "success",
        title: "Applied",
        text: "Delivery details applied to all products.",
        timer: 1200,
        showConfirmButton: false
    });

});
 /* ============DISTRICT OPTIONS BASED ON STATE======= */
    function getBoxDistrictOptions(stateid) {
        let html = `<option value="">-- Select District --</option>`;
        const unique = new Map();
        boxPackingMaster
            .filter(item => String(item.stateid) === String(stateid))
            .forEach(function (item) {

                if (!unique.has(item.districtid)) {
                    unique.set(item.districtid, item.districtname);
                }
            });
        unique.forEach(function (districtName, districtId) {

            html += `
                <option value="${escapeHtml(districtId)}">
                    ${escapeHtml(districtName)}
                </option>
            `;
        });

        return html;
    }

  // ajay

  $(document).ready(function () {
    $('#productEnquiryForm').on('submit', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const clientname = $('#enq_clientname').val().trim();
      const mobileno = $('#enq_mobileno').val().trim();
      if (!/^[0-9]{10}$/.test(mobileno)) { Swal.fire('Invalid Mobile Number', 'Please enter exactly 10 digits.', 'warning'); return; }
      let items;
      try { items = window.EnquiryGrid.items(); } catch (error) { Swal.fire('Required Fields', error.message, 'warning'); return; }
      const product_name = items[0].product_name;

      if (!mobileno || !clientname || !product_name) {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Required Fields', 'Please fill in Mobile Number, Client Name, and Demanded Product Name.', 'warning');
        } else {
          alert('Please fill in Mobile Number, Client Name, and Demanded Product Name.');
        }
        return;
      }

      const routurl = $('body').data('routurl') || '../';
      const companyid = $('body').data('companyid') || '';
      const subcompanyid = $('body').data('subcompanyid') || '';
      const projectid = $('body').data('projectid') || '';
      var priority = $('input[name="priority"]:checked').val();

      let districtname = $('#enq_districtname option:selected').val() ? ($('#enq_districtname option:selected').text() || "").trim() : "";

      let statename = $('#enq_statename option:selected').val() ? ($('#enq_statename option:selected').text() || "").trim() : ""; 

      const formData = {
        companyid: companyid,
        subcompanyid: subcompanyid,
        projectid: projectid,
        clientid: $('#enq_clientid').val(),
        clientname: clientname,
        mobileno: mobileno,
        emailid: $('#enq_emailid').val(),
        stateid: $('#enq_stateid').val(), 
        statename: statename,

        districtid: $('#enq_districtid').val(),
        districtname: districtname,
        cityname: $('#enq_cityname').val(),
        address: $('#enq_address').val(),
        product_name: product_name,
        items: JSON.stringify(items),
        created_by: $('body').data('loginid') || '',
        remarks: $('#enq_remarks').val(),
        priority:priority
      };

      const $btn = $('#btnSubmitEnquiry');
      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

      $.ajax({
        url: 'modal/client_modal.php?action=saveProductEnquiry',
        type: 'POST',
        dataType: 'json',
        data: formData,
        success: function (res) {
          $btn.prop('disabled', false).html('<i class="fa fa-save me-1"></i> Save Enquiry');
          if (res && res.status === 'success') {
            const current = new URL(window.location.href);
            const root = String($('body').data('routurl') || '../').replace(/\/?$/, '/');
            const target = new URL(root + 'krf-orderandsales/provisionalsaleorder_review_new.php', window.location.href);
            target.searchParams.set('k', current.searchParams.get('k') || String($('body').data('key') || ''));
            target.searchParams.set('mdid', current.searchParams.get('mdid') || '');
            target.searchParams.set('iframe', 'true');
            window.location.href = target.toString();
          } else {
            const errMsg = res && res.message ? res.message : 'Failed to save enquiry.';
            if (typeof Swal !== 'undefined') {
              Swal.fire('Error', errMsg, 'error');
            } else {
              alert('Error: ' + errMsg);
            }
          }
        },
        error: function (xhr, status, error) {
          $btn.prop('disabled', false).html('<i class="fa fa-save me-1"></i> Save Enquiry');
          console.error('Save enquiry AJAX error:', error);
          if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'Failed to communicate with server. Please try again.', 'error');
          } else {
            alert('Failed to communicate with server. Please try again.');
          }
        }
      });
    });
  });
