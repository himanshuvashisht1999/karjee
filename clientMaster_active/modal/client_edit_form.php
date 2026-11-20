<?php
/*
 * Reusable Client Edit component.
 *
 * Include once on any project page:
 *   include('components/client_edit_form.php');
 *
 * Open from any button/link:
 *   openClientEditModal(clientId, projectId);
 */
$clientEditRootUrl = $rooturl ?? ($prefix ?? '');
?>

<style>
  #sharedClientEditModal .modal-dialog { max-width: 92% !important; }
  #sharedClientEditModal .modal-content { border: 0; border-radius: 16px; overflow: hidden; }
  #sharedClientEditModal .modal-header {
    color: #fff; border: 0;
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
  }
  #sharedClientEditModal .modal-body { padding: 1.5rem; background: #f8fafc; }
  #sharedClientEditModal .client-edit-section {
    height: 100%; padding: 16px; border: 1px solid #e2e8f0;
    border-radius: 12px; background: #fff;
  }
  #sharedClientEditModal .client-edit-section h6 { color: #0f766e; font-weight: 700; margin-bottom: 14px; }
  #sharedClientEditModal .form-control,
  #sharedClientEditModal .form-select { border-radius: 8px; }
  #sharedClientEditModal .form-label { font-size: .8125rem; font-weight: 700; }
  @media (max-width: 767.98px) {
    #sharedClientEditModal .modal-dialog { max-width: 98% !important; margin: .5rem auto; }
  }
</style>

<div class="modal fade" id="sharedClientEditModal" tabindex="-1" role="dialog" aria-labelledby="sharedClientEditModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content shadow-lg">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="sharedClientEditModalLabel"><i class="fa fa-user-edit me-2"></i>Edit Client Details</h5>
        <button type="button" class="btn-close btn-close-white close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="sharedClientEditForm" novalidate>
          <input type="hidden" name="sno" id="sce_sno">
          <input type="hidden" name="client_id" id="sce_client_id">
          <input type="hidden" name="companyid" id="sce_companyid">
          <input type="hidden" name="subcompanyid" id="sce_subcompanyid">
          <input type="hidden" name="projectid" id="sce_projectid">
          <input type="hidden" name="stateid" id="sce_stateid_value">
          <input type="hidden" name="statename" id="sce_statename">
          <input type="hidden" name="districtid" id="sce_districtid_value">
          <input type="hidden" name="districtname" id="sce_districtname">

          <div class="client-edit-section mb-3">
            <h6><i class="fa fa-address-card me-2"></i>Client & Contact Details</h6>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Client Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="client_name" id="sce_client_name" maxlength="100" required>
              </div>
              <div class="col-md-3">
                <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" name="phones" id="sce_phones" maxlength="10" minlength="10" inputmode="numeric" pattern="[0-9]{10}" required placeholder="10-digit mobile number">
                <div class="invalid-feedback" id="sce_phone_feedback"></div>
              </div>
              <div class="col-md-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="client_email" id="sce_client_email" maxlength="500">
              </div>
              <div class="col-md-2">
                <label class="form-label d-block">Client Type</label>
                <div class="form-check form-switch mt-2">
                  <input class="form-check-input" type="checkbox" name="is_trader" id="sce_is_trader" value="1">
                  <label class="form-check-label" for="sce_is_trader">Is Trader</label>
                </div>
              </div>
            </div>
          </div>

          <div class="client-edit-section mb-3">
            <h6><i class="fa fa-map-marker-alt me-2"></i>Location Details</h6>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">State</label>
                <select class="form-select form-control" id="sce_stateid"><option value="">Select State</option></select>
              </div>
              <div class="col-md-4">
                <label class="form-label">District</label>
                <select class="form-select form-control" id="sce_districtid" disabled><option value="">Select District</option></select>
              </div>
              <div class="col-md-4">
                <label class="form-label">City / Village</label>
                <input type="text" class="form-control" name="cityname" id="sce_cityname" maxlength="45">
              </div>
            </div>
          </div>

          <div class="client-edit-section">
            <h6><i class="fa fa-file-invoice me-2"></i>Invoicing Address</h6>
            <textarea class="form-control" name="invoicing_address" id="sce_invoicing_address" rows="3" maxlength="500" placeholder="Automatically filled from City, District and State; you can edit it"></textarea>
            <div class="alert alert-light border mb-0 mt-3 small"><i class="fa fa-info-circle text-info me-1"></i>Client ID: <strong id="sce_display_client_id"></strong></div>
          </div>

          <div class="row mt-4 pt-2 border-top">
            <div class="col-12 text-end">
              <button type="button" class="btn btn-secondary px-4 me-2" data-bs-dismiss="modal" data-dismiss="modal" style="border-radius:20px">Close</button>
              <button type="submit" class="btn btn-success px-4" id="sce_save_btn" style="border-radius:20px;background:#0d9488;border-color:#0d9488"><i class="fa fa-save me-1"></i>Update Client Details</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function ($) {
  'use strict';
  if (!$ || window.__sharedClientEditLoaded) return;
  window.__sharedClientEditLoaded = true;

  const endpoint = <?php echo json_encode($clientEditRootUrl . 'clientMaster/modal/client_modal.php'); ?>;
  let statesLoaded = false;

  function esc(value) { return $('<div>').text(value == null ? '' : value).html(); }

  function normalizeSharedMobile(value) {
    let digits = String(value || '').replace(/\D/g, '');
    if (digits.length === 12 && digits.substring(0, 2) === '91') digits = digits.slice(-10);
    else if (digits.length === 11 && digits.charAt(0) === '0') digits = digits.slice(-10);
    return digits.slice(0, 10);
  }

  function clearSharedMobileWarning() {
    const input = document.getElementById('sce_phones');
    input.setCustomValidity('');
    $('#sce_phones').removeClass('is-invalid');
    $('#sce_phone_feedback').text('');
  }

  function checkSharedMobileUnique() {
    const $phone = $('#sce_phones');
    const phone = $.trim($phone.val());
    clearSharedMobileWarning();
    if (!phone) return $.Deferred().resolve({ unique: true, message: '' }).promise();

    return $.post(endpoint, {
      action: 'checkMobileUnique',
      sno: $('#sce_sno').val() || 0,
      companyid: $('#sce_companyid').val(),
      subcompanyid: $('#sce_subcompanyid').val(),
      projectid: $('#sce_projectid').val(),
      client_name: $('#sce_client_name').val(),
      phones: phone
    }, null, 'json').then(function (res) {
      if (res.status === 'success' && res.data && res.data.unique === false) {
        const message = res.message || 'This mobile number is already used by another client.';
        $phone.addClass('is-invalid');
        $('#sce_phone_feedback').text(message);
        return {
          unique: false,
          exactDuplicate: res.data.exact_duplicate === true,
          message: message
        };
      }
      return { unique: true, exactDuplicate: false, message: '' };
    });
  }

  function checkSharedNameExists() {
    const clientName = $.trim($('#sce_client_name').val());
    if (!clientName) return $.Deferred().resolve({ exists: false }).promise();

    return $.post(endpoint, {
      action: 'checkNameExists',
      sno: $('#sce_sno').val() || 0,
      client_name: clientName
    }, null, 'json').then(function (res) {
      return {
        exists: res.status === 'success' && res.data && res.data.exists === true,
        message: res.message || 'This client name is already used by another client.'
      };
    });
  }

  function updateSharedInvoiceAddress() {
    const $address = $('#sce_invoicing_address');
    const canAutoFill = $.trim($address.val()) === '' || $address.data('auto-address') === true;
    if (!canAutoFill) return;

    const city = $.trim($('#sce_cityname').val());
    const district = $.trim($('#sce_districtname').val());
    const state = $.trim($('#sce_statename').val());
    $address.val([city, district, state].filter(Boolean).join(', '));
    $address.data('auto-address', true);
  }

  function showEditModal() {
    if ($.fn.modal) {
      $('#sharedClientEditModal').modal('show');
      return;
    }
    if (window.bootstrap && bootstrap.Modal) {
      new bootstrap.Modal(document.getElementById('sharedClientEditModal')).show();
    }
  }

  function loadStates(selectedId) {
    if (statesLoaded) {
      $('#sce_stateid').val(String(selectedId || ''));
      return $.Deferred().resolve().promise();
    }
    return $.post(endpoint, { action: 'getStates' }, null, 'json').done(function (res) {
      if (res.status !== 'success') throw new Error(res.message || 'Unable to load states.');
      let html = '<option value="">Select State</option>';
      (res.data || []).forEach(function (item) { html += '<option value="' + esc(item.stateid) + '">' + esc(item.statename) + '</option>'; });
      $('#sce_stateid').html(html).val(String(selectedId || ''));
      statesLoaded = true;
    });
  }

  function loadDistricts(stateId, selectedId) {
    const $district = $('#sce_districtid').html('<option value="">Loading...</option>').prop('disabled', true);
    return $.post(endpoint, { action: 'getDistricts', stateid: stateId }, null, 'json').done(function (res) {
      let html = '<option value="">Select District</option>';
      (res.data || []).forEach(function (item) { html += '<option value="' + esc(item.districtid) + '">' + esc(item.districtname) + '</option>'; });
      $district.html(html).prop('disabled', false).val(String(selectedId || ''));
    });
  }

  function populate(row) {
    $('#sharedClientEditForm')[0].reset();
    $('#sce_sno').val(row.sno || '');
    $('#sce_client_id').val(row.client_id || '');
    $('#sce_companyid').val(row.companyid || '');
    $('#sce_subcompanyid').val(row.subcompanyid || '');
    $('#sce_projectid').val(row.projectid || '');
    $('#sce_client_name').val(row.client_name || '');
    $('#sce_phones').val(normalizeSharedMobile(row.phones));
    $('#sce_client_email').val(row.client_email || '');
    $('#sce_invoicing_address').val(row.invoicing_address || '').data('auto-address', !$.trim(row.invoicing_address || ''));
    $('#sce_cityname').val(row.cityname || '');
    $('#sce_is_trader').prop('checked', Number(row.is_trader) === 1);
    $('#sce_display_client_id').text(row.client_id || '');
    clearSharedMobileWarning();

    loadStates(row.stateid).done(function () {
      $('#sce_stateid_value').val(row.stateid || '');
      $('#sce_statename').val(row.statename || '');
      loadDistricts(row.stateid, row.districtid).done(function () {
        $('#sce_districtid_value').val(row.districtid || '');
        $('#sce_districtname').val(row.districtname || '');
        updateSharedInvoiceAddress();
      });
    });
    showEditModal();
  }

  window.openClientEditModal = function (clientId, projectId) {
    clientId = String(clientId || '').trim();
    projectId = String(projectId || '').trim();
    if (!clientId || !projectId) {
      Swal.fire('Missing Details', 'Client ID and Project ID are required.', 'warning');
      return;
    }
    Swal.fire({ title: 'Loading Client Details...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
    $.post(endpoint, { action: 'getClient', clientid: clientId, projectid: projectId }, null, 'json')
      .done(function (res) {
        if (res.status !== 'success' || !res.data) { Swal.fire('Error', res.message || 'Client was not found.', 'error'); return; }
        Swal.close();
        populate(res.data);
      })
      .fail(function (xhr) { Swal.fire('Error', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to load client details.', 'error'); });
  };

  $(document).on('change', '#sce_stateid', function () {
    const stateName = $(this).find('option:selected').text();
    $('#sce_stateid_value').val(this.value);
    $('#sce_statename').val(this.value ? stateName : '');
    $('#sce_districtid_value,#sce_districtname').val('');
    updateSharedInvoiceAddress();
    loadDistricts(this.value, '');
  });
  $(document).on('change', '#sce_districtid', function () {
    $('#sce_districtid_value').val(this.value);
    $('#sce_districtname').val(this.value ? $(this).find('option:selected').text() : '');
    updateSharedInvoiceAddress();
  });

  $(document).on('input change', '#sce_cityname', updateSharedInvoiceAddress);
  $(document).on('input', '#sce_invoicing_address', function () {
    $(this).data('auto-address', false);
  });

  $(document).on('input', '#sce_phones', function () {
    this.value = normalizeSharedMobile(this.value);
    clearSharedMobileWarning();
  });
  $(document).on('blur', '#sce_phones', function () {
    checkSharedMobileUnique();
  });

  $(document).on('submit', '#sharedClientEditForm', function (e) {
    e.preventDefault();
    if (!this.checkValidity()) { this.reportValidity(); return; }
    const form = this;
    const $button = $('#sce_save_btn').prop('disabled', true);

    function saveSharedClient(allowDuplicateMobile) {
      const formData = new FormData(form);
      formData.append('action', 'saveUpdateClient');
      if (allowDuplicateMobile) formData.append('allow_duplicate_mobile', '1');
      Swal.fire({ title: 'Updating Client Details...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
      $.ajax({ url: endpoint, type: 'POST', data: formData, dataType: 'json', contentType: false, processData: false })
        .done(function (res) {
          if (res.status === 'success') {
            Swal.fire('Success', res.message, 'success').then(function () {
              $(document).trigger('client:updated', [res.data]);
              window.location.reload();
            });
          } else Swal.fire('Error', res.message || 'Unable to update client.', 'error');
        })
        .fail(function (xhr) { Swal.fire('Error', xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to update client.', 'error'); })
        .always(function () { $button.prop('disabled', false); });
    }

    function checkSharedNameAndSave(mobileCheck) {
      if (mobileCheck && mobileCheck.exactDuplicate === true) {
        $button.prop('disabled', false);
        $('#sce_phones').focus();
        Swal.fire('Duplicate Client', mobileCheck.message || 'The same client name and mobile number already exist.', 'error');
        return;
      }

      checkSharedNameExists().done(function (result) {
        const duplicateMobile = mobileCheck && mobileCheck.unique === false;
        if (!duplicateMobile && !result.exists) {
          saveSharedClient(false);
          return;
        }

        let title = 'Client Name Already Exists';
        let message = result.message + ' Do you want to save this client?';
        if (duplicateMobile) {
          title = 'Mobile Number Already Exists';
          message = mobileCheck.message;
          if (result.exists) message += ' The client name is also already in use.';
          message += ' Do you want to save anyway?';
        }

        Swal.fire({
          icon: 'warning',
          title: title,
          text: message,
          showCancelButton: true,
          confirmButtonText: 'Yes, Save',
          cancelButtonText: 'No'
        }).then(function (confirmation) {
          if (confirmation.isConfirmed) saveSharedClient(duplicateMobile);
          else $button.prop('disabled', false);
        });
      }).fail(function () {
        $button.prop('disabled', false);
        Swal.fire('Error', 'Unable to verify the client name. Please try again.', 'error');
      });
    }

    checkSharedMobileUnique().done(function (mobileCheck) {
      checkSharedNameAndSave(mobileCheck);
    }).fail(function () {
      $button.prop('disabled', false);
      Swal.fire('Error', 'Unable to verify the mobile number. Please try again.', 'error');
    });
  });
})(window.jQuery);
</script>
