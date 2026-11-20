<?php
$clientData = [];

if (isset($_GET['dz'])) {
    require('../connection.php'); 

    $key = $_GET['k'] ?? '';
    $keyDecoded = base64url_decode($key);
    $keyParts = explode('|', $keyDecoded);
    $checkKey = base64url_decode($keyParts[count($keyParts) - 1] ?? '');
    $actualKey = implode('|', array_slice($keyParts, 0, -1));

    if ($actualKey !== $checkKey || count($keyParts) < 6) {
        echo '<div class="col-md-12 text-center black"><h3>OOPS!! Something went wrong. Please try again.</h3></div>';
        die;
    }

    [$loginid, $loginroleid, $companyid, $subcompanyid, $projectid, $logintype] = array_slice($keyParts, 0, 6);
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $rooturl = $host === 'localhost' ? $protocol . '://' . $host . '/krf_amanaccounting/' : $protocol . '://' . $host . '/';
    $prefix = $rooturl;
} else {
    require('../header.php');
}

$companyid = $companyid ?? '';
$subcompanyid = $subcompanyid ?? '';
$projectid = $projectid ?? '';
$loginid = $loginid ?? '';
$rooturl = $rooturl ?? ($prefix ?? '');

$sql = "SELECT sno, client_id, client_name, invoicing_address, phones,
               client_email, stateid, statename, districtid, districtname,
               cityname, is_trader
        FROM krf_client_master
        WHERE companyid = ? AND subcompanyid = ? AND projectid = ?
          AND IFNULL(tedit, '') = ''
        ORDER BY client_name, sno";
$stmt = $krfconn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('sss', $companyid, $subcompanyid, $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $clientData[] = $row;
    }
    $stmt->close();
}

$totalClients = count($clientData);
$traderCount = count(array_filter($clientData, static fn($row) => (int)($row['is_trader'] ?? 0) === 1));
$regularCount = $totalClients - $traderCount;

function clientHtml($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<style>
  :root {
    --client-primary: #0d9488;
    --client-primary-dark: #0f766e;
    --client-light-bg: #f8fafc;
  }
  .modal-lg-custom { max-width: 92% !important; }
  .client-card {
    border: 0; border-radius: 14px; overflow: hidden; margin-bottom: 2rem;
    background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,.05);
  }
  .client-card-header {
    padding: 1.25rem 1.5rem; color: #fff;
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
  }
  .stat-chip {
    display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px;
    border: 1px solid rgba(255,255,255,.3); border-radius: 20px;
    color: #fff; background: rgba(255,255,255,.18); font-size: .825rem; font-weight: 600;
  }
  .header-actions-bar { margin-top: 12px; }
  .search-box-wrapper { position: relative; min-width: 280px; max-width: 440px; flex-grow: 1; }
  .search-box-wrapper .form-control {
    height: 40px; padding-left: 40px; padding-right: 36px; border-radius: 24px;
    border: 1px solid #cbd5e1; font-size: .875rem;
  }
  .search-box-wrapper .form-control:focus {
    border-color: var(--client-primary); box-shadow: 0 0 0 3px rgba(13,148,136,.2);
  }
  .search-box-wrapper .search-icon, .search-box-wrapper .clear-icon {
    position: absolute; top: 50%; transform: translateY(-50%); color: #64748b; z-index: 2;
  }
  .search-box-wrapper .search-icon { left: 14px; pointer-events: none; }
  .search-box-wrapper .clear-icon { right: 14px; cursor: pointer; display: none; }
  .table-responsive-wrapper {
    overflow: auto; max-height: calc(100vh - 220px); border: 1px solid #e2e8f0; border-radius: 10px;
  }
  .custom-client-table { width: 100%; margin-bottom: 0; border-collapse: separate; border-spacing: 0; }
  .custom-client-table thead { position: sticky; top: 0; z-index: 20; }
  .custom-client-table thead th {
    position: sticky; top: 0; z-index: 20; padding: 12px 14px;
    background: #e2f2fe !important; color: #052c65 !important;
    border-bottom: 2px solid #cbd5e1; box-shadow: 0 2px 6px rgba(0,0,0,.15);
    font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
  }
  .custom-client-table tbody td { padding: 13px 14px; vertical-align: middle; font-size: .85rem; }
  .custom-client-table tbody tr:nth-of-type(even) { background: #fdfefe; }
  .custom-client-table tbody tr:hover { background: #f0fdf4 !important; }
  .client-id { color: #0f766e; font-weight: 700; font-family: monospace; }
  .client-type-badge { display: inline-block; padding: 4px 9px; border-radius: 14px; font-size: .72rem; font-weight: 700; }
  .badge-trader { color: #92400e; background: #fef3c7; border: 1px solid #fde68a; }
  .badge-client { color: #166534; background: #dcfce7; border: 1px solid #bbf7d0; }
  .btn-action-icon { width: 32px; height: 32px; padding: 0; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; }
  .client-form-section {
    height: 100%; padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff;
  }
  .client-form-section h6 { color: #0f766e; font-weight: 700; margin-bottom: 14px; }
  .client-form-section .form-control, .client-form-section .form-select { border-radius: 8px; }
  .fs-13 { font-size: .8125rem; }
  .fs-14 { font-size: .875rem; }
  @media (max-width: 767.98px) {
    .modal-lg-custom { max-width: 98% !important; margin: .5rem auto; }
    .header-actions-bar { flex-direction: column; align-items: stretch !important; gap: 10px; }
    .search-box-wrapper { min-width: 100%; max-width: 100%; }
    .action-btns-group { justify-content: flex-end; flex-wrap: wrap; }
    .stat-chips-row { margin-top: 10px; flex-wrap: wrap; }
  }
</style>

<div class="container-fluid px-3 py-3">
  <div class="client-card">
    <div class="client-card-header">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
          <h4 class="mb-1 text-white fw-bold"><i class="fa fa-users me-2"></i>Client Master Directory</h4>
          <p class="mb-0 text-white-50 fs-13">Manage client contact, invoicing address, and location information.</p>
        </div>
        <div class="d-flex align-items-center gap-2 stat-chips-row">
          <span class="stat-chip"><i class="fa fa-layer-group text-warning"></i>Total: <strong id="showingClientCount"><?php echo $totalClients; ?></strong> / <?php echo $totalClients; ?></span>
          <span class="stat-chip"><i class="fa fa-user-check text-info"></i>Clients: <strong><?php echo $regularCount; ?></strong></span>
          <span class="stat-chip"><i class="fa fa-store text-warning"></i>Traders: <strong><?php echo $traderCount; ?></strong></span>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center header-actions-bar w-100 pt-2 border-top border-white-20">
        <div class="search-box-wrapper">
          <i class="fa fa-search search-icon"></i>
          <input type="text" id="clientSearchInput" class="form-control" placeholder="Search client, phone, email, city, district, state..." autocomplete="off">
          <i class="fa fa-times clear-icon" id="clearClientSearch" title="Clear Search"></i>
        </div>
        <div class="action-btns-group d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-warning text-dark fw-bold shadow-sm" onclick="openClientModal()" style="border-radius:20px;padding:6px 16px;height:38px;">
            <i class="fa fa-plus-circle me-1"></i>New Client
          </button>
          <button type="button" class="btn btn-sm btn-outline-light text-white fw-bold shadow-sm" onclick="window.location.reload()" style="border-radius:20px;padding:6px 14px;height:38px;" title="Reload Page">
            <i class="fa fa-sync-alt"></i>
          </button>
        </div>
      </div>
    </div>

    <div class="card-body p-3">
      <div class="table-responsive-wrapper">
        <table class="table custom-client-table" id="clientTable">
          <thead>
            <tr>
              <th class="text-center" style="width:55px">S.No.</th>
              <th>Client</th>
              <th>Contact</th>
              <th>Location</th>
              <th>Invoicing Address</th>
              <th class="text-center">Type</th>
              <th class="text-center" style="width:95px">Action</th>
            </tr>
          </thead>
          <tbody id="clientTableBody">
          <?php if ($totalClients > 0): ?>
            <?php foreach ($clientData as $index => $row): ?>
              <tr id="client_row_<?php echo (int)$row['sno']; ?>">
                <td class="text-center text-muted"><?php echo $index + 1; ?></td>
                <td>
                  <div class="fw-bold text-dark fs-14"><?php echo clientHtml($row['client_name']); ?></div>
                  <small class="client-id"><i class="fa fa-id-card me-1"></i><?php echo clientHtml($row['client_id']); ?></small>
                </td>
                <td>
                  <div><i class="fa fa-phone text-muted me-1"></i><?php echo clientHtml($row['phones'] ?: '-'); ?></div>
                  <small class="text-muted"><i class="fa fa-envelope me-1"></i><?php echo clientHtml($row['client_email'] ?: '-'); ?></small>
                </td>
                <td>
                  <div class="fw-semibold"><?php echo clientHtml($row['cityname'] ?: '-'); ?></div>
                  <small class="text-muted"><?php echo clientHtml(implode(', ', array_filter([$row['districtname'], $row['statename']])) ?: '-'); ?></small>
                </td>
                <td><span title="<?php echo clientHtml($row['invoicing_address']); ?>"><?php echo clientHtml(mb_strimwidth((string)$row['invoicing_address'], 0, 85, '...')); ?></span></td>
                <td class="text-center">
                  <span class="client-type-badge <?php echo (int)$row['is_trader'] === 1 ? 'badge-trader' : 'badge-client'; ?>">
                    <?php echo (int)$row['is_trader'] === 1 ? 'Trader' : 'Client'; ?>
                  </span>
                </td>
                <td class="text-center text-nowrap">
                  <button type="button" class="btn btn-sm btn-info text-white btn-action-icon me-1 edit-client-btn"
                    title="Edit Client"
                    data-client-id="<?php echo clientHtml($row['client_id']); ?>"
                    data-project-id="<?php echo clientHtml($projectid); ?>"><i class="fa fa-edit"></i></button>
                  <button type="button" class="btn btn-sm btn-danger btn-action-icon deactivate-client-btn"
                    title="Deactivate Client"
                    data-client-sno="<?php echo (int)$row['sno']; ?>"><i class="fa fa-user-slash"></i></button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr id="noClientFoundRow"><td colspan="7" class="text-center text-muted py-5"><i class="fa fa-users fa-3x mb-3 d-block"></i><h6 class="fw-bold">No Clients Found</h6><small>Click <strong>New Client</strong> to create the first record.</small></td></tr>
          <?php endif; ?>
            <tr id="noClientSearchRow" style="display:none"><td colspan="7" class="text-center text-muted py-5"><i class="fa fa-search-minus fa-3x mb-3 d-block"></i><h6 class="fw-bold">No Matching Clients Found</h6><small>Change the search text or clear the search.</small></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="clientModal" tabindex="-1" role="dialog" aria-labelledby="clientModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg-custom" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius:16px;overflow:hidden">
      <div class="modal-header text-white" style="background:linear-gradient(135deg,#0f766e 0%,#0d9488 100%)">
        <h5 class="modal-title fw-bold" id="clientModalLabel"><i class="fa fa-user-plus me-2"></i>Add New Client</h5>
        <button type="button" class="btn-close btn-close-white close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" style="background:#f8fafc">
        <form id="clientForm" novalidate>
          <input type="hidden" name="sno" id="client_sno">
          <input type="hidden" name="client_id" id="client_id">
          <input type="hidden" name="companyid" id="client_companyid" value="<?php echo clientHtml($companyid); ?>">
          <input type="hidden" name="subcompanyid" id="client_subcompanyid" value="<?php echo clientHtml($subcompanyid); ?>">
          <input type="hidden" name="projectid" id="client_projectid" value="<?php echo clientHtml($projectid); ?>">
          <input type="hidden" name="loginid" value="<?php echo clientHtml($loginid); ?>">

          <div class="client-form-section mb-3">
            <h6><i class="fa fa-address-card me-2"></i>Client & Contact Details</h6>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-bold fs-13">Client Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="client_name" id="client_name" maxlength="100" required autocomplete="off">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold fs-13">Mobile Number <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" name="phones" id="phones" maxlength="10" minlength="10" inputmode="numeric" pattern="[0-9]{10}" required placeholder="10-digit mobile number">
                <div class="invalid-feedback" id="phoneUniqueFeedback"></div>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-bold fs-13">Email</label>
                <input type="email" class="form-control" name="client_email" id="client_email" maxlength="500" placeholder="client@example.com">
              </div>
              <div class="col-md-2">
                <label class="form-label fw-bold fs-13 d-block">Client Type</label>
                <div class="form-check form-switch mt-2">
                  <input class="form-check-input" type="checkbox" name="is_trader" id="is_trader" value="1">
                  <label class="form-check-label" for="is_trader">Is Trader</label>
                </div>
              </div>
            </div>
          </div>

          <div class="client-form-section mb-3">
            <h6><i class="fa fa-map-marker-alt me-2"></i>Location Details</h6>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-bold fs-13">State</label>
                <select class="form-select form-control" name="stateid" id="client_stateid"><option value="">Select State</option></select>
                <input type="hidden" name="statename" id="client_statename">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold fs-13">District</label>
                <select class="form-select form-control" name="districtid" id="client_districtid" disabled><option value="">Select District</option></select>
                <input type="hidden" name="districtname" id="client_districtname">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold fs-13">City / Village</label>
                <input type="text" class="form-control" name="cityname" id="cityname" maxlength="45" placeholder="City or village name">
              </div>
            </div>
          </div>

          <div class="client-form-section">
            <h6><i class="fa fa-file-invoice me-2"></i>Invoicing Address</h6>
            <textarea class="form-control" name="invoicing_address" id="invoicing_address" rows="3" maxlength="500" placeholder="Automatically filled from City, District and State; you can edit it"></textarea>
            <div class="alert alert-light border mb-0 mt-3 fs-13"><i class="fa fa-info-circle text-info me-1"></i>Client ID is generated automatically for a new client and remains unchanged during editing.</div>
          </div>

          <div class="row mt-4 pt-2 border-top">
            <div class="col-12 text-end">
              <button type="button" class="btn btn-secondary px-4 me-2" data-bs-dismiss="modal" data-dismiss="modal" style="border-radius:20px">Close</button>
              <button type="submit" class="btn btn-success px-4" id="saveClientBtn" style="border-radius:20px;background:#0d9488;border-color:#0d9488"><i class="fa fa-save me-1"></i>Save Client Details</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if (!isset($_GET['dz'])) { include('../footer.php'); } ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function ($) {
  'use strict';
  const endpoint = <?php echo json_encode($rooturl . 'clientMaster/modal/client_modal.php'); ?>;
  let statesLoaded = false;

  function showModal() {
    if ($.fn.modal) $('#clientModal').modal('show');
  }

  function escapeOption(value) {
    return $('<div>').text(value == null ? '' : value).html();
  }

  function clearMobileWarning() {
    const input = document.getElementById('phones');
    input.setCustomValidity('');
    $('#phones').removeClass('is-invalid');
    $('#phoneUniqueFeedback').text('');
  }

  function normalizeMobileForForm(value) {
    let digits = String(value || '').replace(/\D/g, '');
    if (digits.length === 12 && digits.substring(0, 2) === '91') digits = digits.slice(-10);
    else if (digits.length === 11 && digits.charAt(0) === '0') digits = digits.slice(-10);
    return digits.slice(0, 10);
  }

  function checkMobileUnique() {
    const $phone = $('#phones');
    const phone = $.trim($phone.val());
    clearMobileWarning();
    if (!phone) return $.Deferred().resolve({ unique: true, message: '' }).promise();

    return $.ajax({
      url: endpoint,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'checkMobileUnique',
        sno: $('#client_sno').val() || 0,
        companyid: $('#client_companyid').val(),
        subcompanyid: $('#client_subcompanyid').val(),
        projectid: $('#client_projectid').val(),
        client_name: $('#client_name').val(),
        phones: phone
      }
    }).then(function (res) {
      if (res.status === 'success' && res.data && res.data.unique === false) {
        const message = res.message || 'This mobile number is already used by another client.';
        $phone.addClass('is-invalid');
        $('#phoneUniqueFeedback').text(message);
        return {
          unique: false,
          exactDuplicate: res.data.exact_duplicate === true,
          message: message
        };
      }
      return { unique: true, exactDuplicate: false, message: '' };
    });
  }

  function checkNameExists() {
    const clientName = $.trim($('#client_name').val());
    if (!clientName) return $.Deferred().resolve({ exists: false }).promise();

    return $.ajax({
      url: endpoint,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'checkNameExists',
        sno: $('#client_sno').val() || 0,
        client_name: clientName
      }
    }).then(function (res) {
      return {
        exists: res.status === 'success' && res.data && res.data.exists === true,
        message: res.message || 'This client name is already used by another client.'
      };
    });
  }

  function updateInvoicingAddress() {
    const $address = $('#invoicing_address');
    const canAutoFill = $.trim($address.val()) === '' || $address.data('auto-address') === true;
    if (!canAutoFill) return;

    const city = $.trim($('#cityname').val());
    const district = $.trim($('#client_districtname').val());
    const state = $.trim($('#client_statename').val());
    $address.val([city, district, state].filter(Boolean).join(', '));
    $address.data('auto-address', true);
  }

  function loadStates(selectedStateId) {
    if (statesLoaded) {
      $('#client_stateid').val(String(selectedStateId || ''));
      return $.Deferred().resolve().promise();
    }
    return $.ajax({ url: endpoint, type: 'POST', dataType: 'json', data: { action: 'getStates' } })
      .done(function (res) {
        if (res.status !== 'success') throw new Error(res.message || 'Unable to load states.');
        let options = '<option value="">Select State</option>';
        (res.data || []).forEach(function (item) {
          options += '<option value="' + escapeOption(item.stateid) + '">' + escapeOption(item.statename) + '</option>';
        });
        $('#client_stateid').html(options);
        statesLoaded = true;
        $('#client_stateid').val(String(selectedStateId || ''));
      })
      .fail(function () { Swal.fire('Error', 'Unable to load the state list.', 'error'); });
  }

  function loadDistricts(stateId, selectedDistrictId) {
    const $district = $('#client_districtid');
    $district.html('<option value="">Loading...</option>').prop('disabled', true);
    if (!stateId) {
      $district.html('<option value="">Select District</option>');
      return $.Deferred().resolve().promise();
    }
    return $.ajax({ url: endpoint, type: 'POST', dataType: 'json', data: { action: 'getDistricts', stateid: stateId } })
      .done(function (res) {
        let options = '<option value="">Select District</option>';
        (res.data || []).forEach(function (item) {
          options += '<option value="' + escapeOption(item.districtid) + '">' + escapeOption(item.districtname) + '</option>';
        });
        $district.html(options).prop('disabled', false).val(String(selectedDistrictId || ''));
        $('#client_districtname').val($district.find('option:selected').text() === 'Select District' ? '' : $district.find('option:selected').text());
      })
      .fail(function () {
        $district.html('<option value="">Select District</option>');
        Swal.fire('Error', 'Unable to load the district list.', 'error');
      });
  }

  window.openClientModal = function () {
    $('#clientForm')[0].reset();
    $('#client_sno, #client_id, #client_statename, #client_districtname').val('');
    $('#client_districtid').html('<option value="">Select District</option>').prop('disabled', true);
    $('#invoicing_address').val('').data('auto-address', true);
    clearMobileWarning();
    $('#clientModalLabel').html('<i class="fa fa-user-plus me-2"></i>Add New Client');
    loadStates('').always(showModal);
  };

  function fillClientEditForm(row) {
    $('#clientForm')[0].reset();
    $('#client_sno').val(row.sno || '');
    $('#client_id').val(row.client_id || '');
    $('#client_companyid').val(row.companyid || '');
    $('#client_subcompanyid').val(row.subcompanyid || '');
    $('#client_projectid').val(row.projectid || '');
    $('#client_name').val(row.client_name || '');
    $('#phones').val(normalizeMobileForForm(row.phones));
    $('#client_email').val(row.client_email || '');
    $('#invoicing_address').val(row.invoicing_address || '').data('auto-address', !$.trim(row.invoicing_address || ''));
    $('#cityname').val(row.cityname || '');
    $('#is_trader').prop('checked', Number(row.is_trader) === 1);
    clearMobileWarning();
    $('#clientModalLabel').html('<i class="fa fa-user-edit me-2"></i>Edit Client Details');

    loadStates(row.stateid).done(function () {
      $('#client_statename').val(row.statename || '');
      loadDistricts(row.stateid, row.districtid).done(function () {
        $('#client_districtname').val(row.districtname || '');
        updateInvoicingAddress();
      });
    }).always(showModal);
  }

  /*
   * Reusable edit entry point for every module in the project.
   * Only client ID and project ID are required from the calling component.
   */
  window.openClientEditModal = function (clientId, projectId) {
    clientId = String(clientId || '').trim();
    projectId = String(projectId || '').trim();
    if (!clientId || !projectId) {
      Swal.fire('Missing Details', 'Client ID and Project ID are required.', 'warning');
      return;
    }

    Swal.fire({
      title: 'Loading Client Details...',
      allowOutsideClick: false,
      didOpen: function () { Swal.showLoading(); }
    });

    $.ajax({
      url: endpoint,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'getClient',
        clientid: clientId,
        projectid: projectId
      }
    }).done(function (res) {
      if (res.status !== 'success' || !res.data) {
        Swal.fire('Error', res.message || 'Client record was not found.', 'error');
        return;
      }
      Swal.close();
      fillClientEditForm(res.data);
    }).fail(function (xhr) {
      const message = xhr.responseJSON && xhr.responseJSON.message
        ? xhr.responseJSON.message
        : 'Unable to load client details.';
      Swal.fire('Error', message, 'error');
    });
  };

  // Backward-compatible alias if an older page already uses this name.
  window.openEditClientModal = window.openClientEditModal;

  window.deactivateClient = function (sno) {
    Swal.fire({ title: 'Deactivate this client?', text: 'The record will remain in the database but will be removed from the active client list.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, deactivate' })
      .then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({ url: endpoint, type: 'POST', dataType: 'json', data: { action: 'deactivateClient', sno: sno, companyid: <?php echo json_encode($companyid); ?>, subcompanyid: <?php echo json_encode($subcompanyid); ?>, projectid: <?php echo json_encode($projectid); ?> } })
          .done(function (res) {
            if (res.status === 'success') Swal.fire('Deactivated', res.message, 'success').then(function () { window.location.reload(); });
            else Swal.fire('Error', res.message || 'Unable to deactivate client.', 'error');
          }).fail(function (xhr) {
            const message = xhr.responseJSON && xhr.responseJSON.message
              ? xhr.responseJSON.message
              : 'Unable to deactivate client.';
            Swal.fire('Cannot Deactivate', message, 'warning');
          });
      });
  };

  // Backward compatibility for older buttons that still call deleteClient().
  window.deleteClient = window.deactivateClient;

  $(document).ready(function () {
    // Dynamic row actions use data attributes; no inline JavaScript is generated.
    $(document).on('click', '.edit-client-btn', function () {
      openClientEditModal(
        $(this).attr('data-client-id'),
        $(this).attr('data-project-id')
      );
    });

    $(document).on('click', '.deactivate-client-btn', function () {
      deactivateClient($(this).attr('data-client-sno'));
    });

    $('#phones').on('input', function () {
      this.value = normalizeMobileForForm(this.value);
      clearMobileWarning();
    }).on('blur', function () {
      checkMobileUnique();
    });

    $('#client_stateid').on('change', function () {
      const text = $(this).find('option:selected').text();
      $('#client_statename').val(this.value ? text : '');
      $('#client_districtname').val('');
      updateInvoicingAddress();
      loadDistricts(this.value, '');
    });
    $('#client_districtid').on('change', function () {
      const text = $(this).find('option:selected').text();
      $('#client_districtname').val(this.value ? text : '');
      updateInvoicingAddress();
    });

    $('#cityname').on('input change', updateInvoicingAddress);
    $('#invoicing_address').on('input', function () {
      $(this).data('auto-address', false);
    });

    $('#clientSearchInput').on('input search', function () {
      const query = $(this).val().toLowerCase().trim();
      $('#clearClientSearch').toggle(query.length > 0);
      let visible = 0;
      $('#clientTableBody tr').not('#noClientFoundRow,#noClientSearchRow').each(function () {
        const match = $(this).text().toLowerCase().indexOf(query) !== -1;
        $(this).toggle(match);
        if (match) visible++;
      });
      $('#noClientSearchRow').toggle(visible === 0 && $('#clientTableBody tr').not('#noClientFoundRow,#noClientSearchRow').length > 0);
      $('#showingClientCount').text(visible);
    });
    $('#clearClientSearch').on('click', function () { $('#clientSearchInput').val('').trigger('input').focus(); });

    $('#clientForm').on('submit', function (e) {
      e.preventDefault();
      if (!this.checkValidity()) { this.reportValidity(); return; }
      const form = this;
      const $button = $('#saveClientBtn').prop('disabled', true);

      function saveClient(allowDuplicateMobile) {
        const formData = new FormData(form);
        formData.append('action', 'saveUpdateClient');
        if (allowDuplicateMobile) formData.append('allow_duplicate_mobile', '1');
        Swal.fire({ title: 'Saving Client Details...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
        $.ajax({ url: endpoint, type: 'POST', data: formData, dataType: 'json', contentType: false, processData: false })
          .done(function (res) {
            if (res.status === 'success') Swal.fire('Success', res.message, 'success').then(function () { window.location.reload(); });
            else Swal.fire('Error', res.message || 'Unable to save client.', 'error');
          })
          .fail(function (xhr) {
            const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Something went wrong while saving client details.';
            Swal.fire('Error', message, 'error');
          })
          .always(function () { $button.prop('disabled', false); });
      }

      function checkNameAndSave(mobileCheck) {
        if (mobileCheck && mobileCheck.exactDuplicate === true) {
          $button.prop('disabled', false);
          $('#phones').focus();
          Swal.fire('Duplicate Client', mobileCheck.message || 'The same client name and mobile number already exist.', 'error');
          return;
        }

        checkNameExists().done(function (result) {
          const duplicateMobile = mobileCheck && mobileCheck.unique === false;
          if (!duplicateMobile && !result.exists) {
            saveClient(false);
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
            if (confirmation.isConfirmed) saveClient(duplicateMobile);
            else $button.prop('disabled', false);
          });
        }).fail(function () {
          $button.prop('disabled', false);
          Swal.fire('Error', 'Unable to verify the client name. Please try again.', 'error');
        });
      }

      checkMobileUnique().done(function (mobileCheck) {
        checkNameAndSave(mobileCheck);
      }).fail(function () {
        $button.prop('disabled', false);
        Swal.fire('Error', 'Unable to verify the mobile number. Please try again.', 'error');
      });
    });
  });
})(jQuery);
</script>
