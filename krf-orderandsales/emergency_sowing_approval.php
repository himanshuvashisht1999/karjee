<?php
include('../header.php');
include('functions/orders_functions.php');
include('functions/client_functions.php');

$mdid = $_GET['mdid'] ?? "";
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = (count($dddr) > 0) ? $dddr[0]['groupid'] : "";
?>

<style>
  :root {
    --primary-blue: #0d6efd;
    --emergency-red: #dc2626;
    --emergency-bg: #fef2f2;
    --emergency-border: #fecaca;
    --text-main: #1e293b;
    --text-muted: #64748b;
    --bg-light: #f8fafc;
    --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    --transition: all 0.2s ease-in-out;
  }

  .emergency-dashboard {
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    min-height: 100vh;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    width: 100%;
    overflow-x: hidden;
  }

  /* Header Card */
  .dashboard-header-card {
    background: #ffffff;
    border-radius: 10px;
    padding: 0.9rem 1.25rem;
    color: #1e293b;
    box-shadow: var(--card-shadow);
    border: 1px solid #e2e8f0;
    margin-bottom: 0.85rem;
  }

  /* Summary KPI Cards */
  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
    margin-bottom: 0.85rem;
  }

  .kpi-card {
    background: white;
    border-radius: 8px;
    padding: 0.65rem 0.85rem;
    border: 1px solid #e2e8f0;
    box-shadow: var(--card-shadow);
    display: flex;
    align-items: center;
    gap: 0.65rem;
    transition: var(--transition);
  }

  .kpi-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.05);
  }

  .kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    flex-shrink: 0;
  }

  .kpi-icon.emergency { background: #fee2e2; color: #dc2626; }
  .kpi-icon.quantity { background: #e0f2fe; color: #0284c7; }
  .kpi-icon.value { background: #dcfce7; color: #16a34a; }
  .kpi-icon.clients { background: #f3e8ff; color: #9333ea; }

  .kpi-title {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 0.1rem;
  }

  .kpi-value {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--text-main);
    line-height: 1.15;
  }

  /* Filter Strip */
  .filter-strip {
    background: white;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    box-shadow: var(--card-shadow);
    margin-bottom: 0.85rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    align-items: flex-end;
    border: 1px solid #e2e8f0;
  }

  .filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    flex: 1;
    min-width: 140px;
  }

  .filter-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }

  .filter-control {
    width: 100%;
    padding: 0.38rem 0.6rem;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    font-size: 0.8rem;
    color: #1e293b;
    background-color: #f8fafc;
    transition: var(--transition);
  }

  .filter-control:focus {
    border-color: var(--primary-blue);
    background-color: white;
    outline: none;
  }

  .search-wrapper {
    position: relative;
    width: 100%;
  }

  .search-wrapper i {
    position: absolute;
    left: 9px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.75rem;
  }

  .search-wrapper input {
    padding-left: 28px;
  }

  .btn-clear-filters {
    padding: 0.38rem 0.75rem;
    border-radius: 6px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    color: #64748b;
    font-weight: 600;
    font-size: 0.78rem;
    cursor: pointer;
    transition: var(--transition);
    height: 31px;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    white-space: nowrap;
  }

  .btn-clear-filters:hover {
    background: #e2e8f0;
    color: #1e293b;
  }

  /* Desktop Strips Header (100% fluid width, NO scroll-x) */
  .strips-header {
    display: grid;
    grid-template-columns: 32px 130px 1.3fr 1.6fr 150px 95px 145px;
    padding: 0.45rem 0.75rem;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #64748b;
    background: #eef2f6;
    border-radius: 6px 6px 0 0;
    border: 1px solid #e2e8f0;
    border-bottom: none;
    width: 100%;
    align-items: center;
  }

  /* Order Strip Styles - Sleek Low Height Row */
  .order-strips-container {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    width: 100%;
  }

  .order-strip {
    background: white;
    border-radius: 6px;
    padding: 0.45rem 0.75rem;
    display: grid;
    grid-template-columns: 32px 130px 1.3fr 1.6fr 150px 95px 145px;
    align-items: center;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    border: 1px solid #e2e8f0;
    border-left: 4px solid #dc2626 !important;
    transition: var(--transition);
    width: 100%;
    min-height: 44px;
  }

  .order-strip:hover {
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    border-color: #cbd5e1;
    border-left-color: #b91c1c !important;
    background-color: #fafbfc;
  }

  .strip-item {
    min-width: 0;
    padding-right: 0.35rem;
    line-height: 1.2;
  }

  .order-no-pill {
    background: #f1f5f9;
    padding: 0.12rem 0.4rem;
    border-radius: 4px;
    font-family: 'SFMono-Regular', Consolas, Menlo, monospace;
    font-size: 0.76rem;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    color: #0f172a;
    display: inline-block;
  }

  .sowing-badge-emergency {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    font-size: 0.74rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
  }

  .amount-display {
    color: #0f172a;
    font-weight: 700;
    font-size: 0.85rem;
  }

  /* Action Buttons */
  .action-buttons {
    display: flex;
    gap: 0.25rem;
    justify-content: flex-end;
    align-items: center;
  }

  .btn-approve-sowing {
    background: #16a34a;
    color: white;
    border: none;
    font-weight: 700;
    font-size: 0.7rem;
    padding: 0.28rem 0.55rem;
    border-radius: 5px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    transition: var(--transition);
    cursor: pointer;
    white-space: nowrap;
    height: 28px;
  }

  .btn-approve-sowing:hover {
    background: #15803d;
    color: white;
  }

  .btn-strip {
    width: 28px;
    height: 28px;
    border-radius: 5px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    font-size: 0.75rem;
    cursor: pointer;
    padding: 0;
  }

  .btn-strip:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  .btn-view:hover { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
  .btn-pdf:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

  /* Mobile Responsive Strip Styles (< 768px) */
  @media (max-width: 768px) {
    .emergency-dashboard {
      padding: 0.75rem 0.65rem;
    }
    .strips-header {
      display: none;
    }
    .order-strip {
      display: grid;
      grid-template-columns: 1fr auto;
      row-gap: 0.35rem;
      column-gap: 0.5rem;
      padding: 0.65rem 0.75rem;
      min-height: auto;
    }
    .strip-item-sno {
      display: none;
    }
    .strip-item-order {
      grid-column: 1;
    }
    .strip-item-amount {
      grid-column: 2;
      text-align: right;
    }
    .strip-item-client {
      grid-column: 1 / -1;
      padding-top: 0.15rem;
    }
    .strip-item-product {
      grid-column: 1 / -1;
      padding-top: 0.15rem;
    }
    .strip-item-sowing {
      grid-column: 1;
      padding-top: 0.2rem;
    }
    .strip-item-actions {
      grid-column: 2;
      justify-content: flex-end;
      padding-top: 0.2rem;
    }
  }

  /* Skeleton Loading */
  .skeleton {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 4px;
  }

  @keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
  }
</style>

<div class="emergency-dashboard"
     id="emergencyDashboard"
     data-key="<?php echo htmlspecialchars($key ?? ''); ?>"
     data-companyid="<?php echo htmlspecialchars($companyid ?? ''); ?>"
     data-subcompanyid="<?php echo htmlspecialchars($subcompanyid ?? ''); ?>"
     data-projectid="<?php echo htmlspecialchars($projectid ?? ''); ?>"
     data-loginroleid="<?php echo htmlspecialchars($loginroleid ?? '0'); ?>"
     data-loginid="<?php echo htmlspecialchars($loginid ?? ''); ?>"
     data-loginname="<?php echo htmlspecialchars($loginname ?? $loginid ?? ''); ?>"
     data-routurl="<?php echo htmlspecialchars($routurl ?? '../'); ?>">

  <!-- Header Card -->
  <div class="dashboard-header-card d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge bg-danger text-white px-2 py-0.5 rounded-pill" style="font-size: 0.68rem; font-weight: 700;">
          <i class="fa fa-exclamation-triangle me-1"></i> HIGH PRIORITY
        </span>
        <span class="text-secondary small fw-semibold" style="font-size: 0.76rem;">Order Management</span>
      </div>
      <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.2rem;">
        <i class="fa fa-seedling text-danger"></i> Emergency Sowing Approvals
      </h4>
    </div>

    <div class="d-flex align-items-center gap-2">
      <button type="button" class="btn btn-outline-secondary btn-sm px-2.5 py-1 fw-semibold rounded-2 shadow-sm" id="btnRefreshData" style="font-size: 0.78rem;">
        <i class="fa fa-sync-alt me-1"></i> Refresh List
      </button>
    </div>
  </div>

  <!-- KPI Summary Cards -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon emergency">
        <i class="fa fa-exclamation-circle"></i>
      </div>
      <div>
        <div class="kpi-title">Pending Approvals</div>
        <div class="kpi-value" id="kpiPendingCount">0</div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon quantity">
        <i class="fa fa-cubes"></i>
      </div>
      <div>
        <div class="kpi-title">Total Quantity</div>
        <div class="kpi-value" id="kpiTotalQty">0</div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon value">
        <i class="fa fa-rupee-sign"></i>
      </div>
      <div>
        <div class="kpi-title">Total Order Value</div>
        <div class="kpi-value" id="kpiTotalValue">₹0.00</div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon clients">
        <i class="fa fa-users"></i>
      </div>
      <div>
        <div class="kpi-title">Total Clients</div>
        <div class="kpi-value" id="kpiClientCount">0</div>
      </div>
    </div>
  </div>

  <!-- Filter Strip -->
  <div class="filter-strip">
    <div class="filter-group">
      <label class="filter-label">Filter Client</label>
      <select id="clientFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Clients"></select>
    </div>

    <div class="filter-group">
      <label class="filter-label">Order Date</label>
      <select id="orderDateFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Order Dates"></select>
    </div>

    <div class="filter-group">
      <label class="filter-label">Sowing Date</label>
      <select id="sowingDateFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Sowing Dates"></select>
    </div>

    <div class="filter-group">
      <label class="filter-label">Created By</label>
      <select id="creatorFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Creators"></select>
    </div>

    <div class="filter-group" style="min-width: 180px;">
      <label class="filter-label">Search Orders</label>
      <div class="search-wrapper">
        <i class="fa fa-search"></i>
        <input type="text" id="searchInput" class="filter-control" placeholder="Search order, client...">
      </div>
    </div>

    <div>
      <button type="button" class="btn-clear-filters" id="btnClearFilters">
        <i class="fa fa-times-circle"></i> Clear Filters
      </button>
    </div>
  </div>

  <!-- Strips Header (Desktop) -->
  <div class="strips-header">
    <div>#</div>
    <div>Order No & Date</div>
    <div>Client Details</div>
    <div>Product & Qty</div>
    <div>Sowing & Delivery</div>
    <div>Amount</div>
    <div class="text-end">Actions</div>
  </div>

  <!-- Orders Container -->
  <div class="order-strips-container" id="emergencyOrdersContainer">
    <!-- Dynamic Content Loaded via API -->
  </div>

  <!-- No Results State -->
  <div id="noResultsState" class="text-center py-4 bg-white rounded-3 shadow-sm border mt-2" style="display: none;">
    <div class="p-3">
      <i class="fa fa-check-circle text-success mb-2" style="font-size: 2.2rem;"></i>
      <h6 class="fw-bold text-dark mb-1">No Pending Emergency Sowing Approvals</h6>
      <p class="text-muted small mb-0">All emergency sowing orders have been processed and approved.</p>
    </div>
  </div>

</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-light py-2">
        <h6 class="modal-title font-weight-bold mb-0">Order Details</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#viewOrderModal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="viewOrderContent"></div>
    </div>
  </div>
</div>

<?php include('../footer.php'); ?>

<script>
window.EMERGENCY_CONTEXT = {
    key: <?php echo json_encode($key ?? ''); ?>,
    companyid: <?php echo json_encode($companyid ?? ''); ?>,
    subcompanyid: <?php echo json_encode($subcompanyid ?? ''); ?>,
    projectid: <?php echo json_encode($projectid ?? ''); ?>,
    loginroleid: <?php echo json_encode($loginroleid ?? '0'); ?>,
    loginid: <?php echo json_encode($loginid ?? ''); ?>,
    loginname: <?php echo json_encode($loginname ?? $loginid ?? ''); ?>,
    routurl: <?php echo json_encode($routurl ?? '../'); ?>
};
</script>

<!-- Include Emergency Sowing JS API Client -->
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/emergencysowing_api.js"></script>

<script>
$(document).ready(function () {
    let globalOrders = [];

    // Ensure body also has the data attributes
    $('body').data('projectid', window.EMERGENCY_CONTEXT.projectid);
    $('body').data('companyid', window.EMERGENCY_CONTEXT.companyid);
    $('body').data('subcompanyid', window.EMERGENCY_CONTEXT.subcompanyid);
    $('body').data('loginroleid', window.EMERGENCY_CONTEXT.loginroleid);
    $('body').data('loginid', window.EMERGENCY_CONTEXT.loginid);
    $('body').data('loginname', window.EMERGENCY_CONTEXT.loginname);
    $('body').data('routurl', window.EMERGENCY_CONTEXT.routurl);
    $('body').data('key', window.EMERGENCY_CONTEXT.key);

    // Initialize Select2 dropdowns
    $('.select2').select2({
        width: '100%',
        dropdownAutoWidth: true
    }).on('select2:select select2:unselect', applyFilters);

    // Initial Load
    loadEmergencyOrders();

    // Refresh Button
    $('#btnRefreshData').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Refreshing...');
        loadEmergencyOrders().always(function () {
            $btn.prop('disabled', false).html('<i class="fa fa-sync-alt me-1"></i> Refresh List');
        });
    });

    // Search Input Real-time Filtering
    $('#searchInput').on('input', applyFilters);

    // Clear Filters
    $('#btnClearFilters').on('click', function () {
        $('#searchInput').val('');
        $('#clientFilter, #orderDateFilter, #sowingDateFilter, #creatorFilter').val([]).trigger('change');
        applyFilters();
    });

    /**
     * Fetch and Render Emergency Sowing Orders using the JS API
     */
    function loadEmergencyOrders() {
        showLoadingSkeleton();

        return EmergencySowingAPI.fetchPendingOrders()
            .done(function (res) {
                if (res.status === 'success') {
                    globalOrders = res.data || [];
                    populateFilters(globalOrders);
                    updateKpis(globalOrders);
                    renderOrderStrips(globalOrders);
                } else {
                    $('#emergencyOrdersContainer').html(`
                        <div class="alert alert-danger shadow-sm border-0 py-2 small">
                            <i class="fa fa-exclamation-triangle me-2"></i> ${res.message || 'Error loading orders.'}
                        </div>
                    `);
                }
            })
            .fail(function (xhr, status, error) {
                $('#emergencyOrdersContainer').html(`
                    <div class="alert alert-danger shadow-sm border-0 py-2 small">
                        <i class="fa fa-exclamation-triangle me-2"></i> Server communication error: ${error || 'Unknown error'}
                    </div>
                `);
            });
    }

    /**
     * Show Skeleton Loading state
     */
    function showLoadingSkeleton() {
        let skeletonHtml = '';
        for (let i = 0; i < 2; i++) {
            skeletonHtml += `
                <div class="order-strip" style="opacity: 0.7;">
                    <div class="skeleton" style="height: 16px; width: 18px;"></div>
                    <div class="skeleton" style="height: 22px; width: 95px;"></div>
                    <div class="skeleton" style="height: 22px; width: 120px;"></div>
                    <div class="skeleton" style="height: 22px; width: 140px;"></div>
                    <div class="skeleton" style="height: 22px; width: 110px;"></div>
                    <div class="skeleton" style="height: 20px; width: 65px;"></div>
                    <div class="skeleton" style="height: 26px; width: 110px;"></div>
                </div>
            `;
        }
        $('#emergencyOrdersContainer').html(skeletonHtml);
        $('#noResultsState').hide();
    }

    /**
     * Update KPI counters
     */
    function updateKpis(orders) {
        const count = orders.length;
        let totalQty = 0;
        let totalValue = 0;
        const clientsSet = new Set();

        orders.forEach(o => {
            totalQty += parseFloat(o.total_qty || 0);
            totalValue += parseFloat(o.orderamount || 0);
            if (o.clientname) clientsSet.add(o.clientname.trim());
        });

        $('#kpiPendingCount').text(count);
        $('#kpiTotalQty').text(totalQty.toLocaleString('en-IN'));
        $('#kpiTotalValue').text('₹' + totalValue.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#kpiClientCount').text(clientsSet.size);
    }

    /**
     * Populate filter options dynamically
     */
    function populateFilters(orders) {
        const clients = new Set();
        const orderDates = new Set();
        const sowingDates = new Set();
        const creators = new Set();

        orders.forEach(o => {
            if (o.clientname) clients.add(o.clientname.trim());
            if (o.created_at_formatted) orderDates.add(o.created_at_formatted.trim());
            if (o.sowingdate_formatted) sowingDates.add(o.sowingdate_formatted.trim());
            if (o.created_by_name) creators.add(o.created_by_name.trim());
        });

        updateSelectOptions('#clientFilter', Array.from(clients).sort());
        updateSelectOptions('#orderDateFilter', Array.from(orderDates).sort());
        updateSelectOptions('#sowingDateFilter', Array.from(sowingDates).sort());
        updateSelectOptions('#creatorFilter', Array.from(creators).sort());
    }

    function updateSelectOptions(selector, items) {
        const $el = $(selector);
        const currentVals = $el.val() || [];
        $el.empty();
        items.forEach(item => {
            const isSelected = currentVals.includes(item);
            $el.append(new Option(item, item, isSelected, isSelected));
        });
    }

    /**
     * Render the order strips HTML
     */
    function renderOrderStrips(orders) {
        const $container = $('#emergencyOrdersContainer');
        $container.empty();

        if (orders.length === 0) {
            $('#noResultsState').fadeIn(200);
            return;
        }
        $('#noResultsState').hide();

        orders.forEach((order, idx) => {
            const sno = String(idx + 1).padStart(2, '0');

            // Product items display
            let productSummary = '';
            if (order.items && order.items.length > 0) {
                const firstItem = order.items[0];
                const extraCount = order.items.length - 1;
                productSummary = `
                    <div class="fw-bold text-dark text-truncate" title="${escapeHtml(firstItem.clean_product_name || firstItem.product_name)}" style="font-size: 0.8rem;">
                        ${escapeHtml(firstItem.clean_product_name || firstItem.product_name)}
                    </div>
                    <div class="small text-muted d-flex align-items-center gap-1" style="font-size: 0.74rem;">
                        <span>Qty: <strong class="text-dark font-monospace">${Number(firstItem.quantity).toLocaleString('en-IN')}</strong></span>
                        ${extraCount > 0 ? `<span class="badge bg-secondary text-white py-0 px-1" style="font-size: 0.65rem;">+${extraCount}</span>` : ''}
                    </div>
                `;
            } else {
                productSummary = `<span class="text-muted small">No items</span>`;
            }

            const sowingDateDisplay = order.sowingdate_formatted ? `
                <div class="sowing-badge-emergency">
                    <i class="fa fa-seedling text-danger"></i> ${order.sowingdate_formatted}
                </div>
            ` : `<span class="text-muted small">N/A</span>`;

            const deliveryDateDisplay = (order.items && order.items[0] && order.items[0].expecteddeliverydate_formatted)
                ? order.items[0].expecteddeliverydate_formatted
                : 'N/A';

            const creatorDisplay = order.created_by_name || order.created_by || 'System';
            const clientDisplay = escapeHtml(order.clientname || 'N/A');
            const mobileDisplay = order.mobileno ? `(${escapeHtml(order.mobileno)})` : '';

            const searchableText = `${order.psono} ${clientDisplay} ${order.mobileno || ''} ${creatorDisplay} ${order.districtname || ''} ${order.statename || ''}`.toLowerCase();

            const stripHtml = `
                <div class="order-strip" 
                     id="order_strip_${order.psono}"
                     data-psono="${order.psono}"
                     data-client="${escapeHtml(order.clientname || '')}"
                     data-order-date="${order.created_at_formatted || ''}"
                     data-sowing-date="${order.sowingdate_formatted || ''}"
                     data-creator="${escapeHtml(creatorDisplay)}"
                     data-search="${searchableText}">

                    <!-- 1. Serial No -->
                    <div class="strip-item strip-item-sno text-muted font-monospace fw-bold" style="font-size: 0.75rem;">
                        ${sno}
                    </div>

                    <!-- 2. Order No & Date -->
                    <div class="strip-item strip-item-order">
                        <span class="order-no-pill">${order.psono}</span>
                        <div class="text-muted" style="font-size: 0.72rem; margin-top: 1px;">${order.created_at_formatted}</div>
                    </div>

                    <!-- 3. Client Details -->
                    <div class="strip-item strip-item-client">
                        <div class="fw-bold text-primary text-truncate" title="${clientDisplay}" style="font-size: 0.8rem;">${clientDisplay}</div>
                        <div class="text-muted text-truncate" style="font-size: 0.73rem;">${mobileDisplay}</div>
                    </div>

                    <!-- 4. Product & Qty -->
                    <div class="strip-item strip-item-product">
                        ${productSummary}
                    </div>

                    <!-- 5. Emergency Sowing & Delivery -->
                    <div class="strip-item strip-item-sowing">
                        ${sowingDateDisplay}
                        <div class="text-muted" style="font-size: 0.72rem; margin-top: 1px;">Del: <strong>${deliveryDateDisplay}</strong></div>
                    </div>

                    <!-- 6. Order Amount -->
                    <div class="strip-item strip-item-amount">
                        <div class="amount-display">₹${Number(order.orderamount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                    </div>

                    <!-- 7. Action Buttons -->
                    <div class="action-buttons strip-item-actions">
                        <button type="button" 
                                class="btn-approve-sowing btnApproveOrder" 
                                data-psono="${order.psono}" 
                                data-client="${clientDisplay}"
                                data-projectid="${order.projectid || ''}">
                            <i class="fa fa-check"></i> Approve
                        </button>
                        <button type="button" class="btn-strip btn-view viewOrderBtn" data-psono="${order.psono}" title="View Order Details">
                            <i class="fa fa-eye"></i>
                        </button>
                        <button type="button" class="btn-strip btn-pdf" onclick="provisionalorderDownload('${order.psono}');" title="Download Order PDF">
                            <i class="fa fa-file-pdf"></i>
                        </button>
                    </div>

                </div>
            `;

            $container.append(stripHtml);
        });
    }

    /**
     * Filter active order strips
     */
    function applyFilters() {
        const searchTerm = ($('#searchInput').val() || '').toLowerCase().trim();
        const selectedClients = $('#clientFilter').val() || [];
        const selectedOrderDates = $('#orderDateFilter').val() || [];
        const selectedSowingDates = $('#sowingDateFilter').val() || [];
        const selectedCreators = $('#creatorFilter').val() || [];

        let visibleCount = 0;

        $('.order-strip').each(function () {
            const $strip = $(this);
            const searchable = $strip.data('search') || '';
            const rowClient = $strip.data('client') || '';
            const rowOrderDate = $strip.data('order-date') || '';
            const rowSowingDate = $strip.data('sowing-date') || '';
            const rowCreator = $strip.data('creator') || '';

            const matchSearch = !searchTerm || searchable.indexOf(searchTerm) > -1;
            const matchClient = selectedClients.length === 0 || selectedClients.includes(rowClient);
            const matchOrderDate = selectedOrderDates.length === 0 || selectedOrderDates.includes(rowOrderDate);
            const matchSowingDate = selectedSowingDates.length === 0 || selectedSowingDates.includes(rowSowingDate);
            const matchCreator = selectedCreators.length === 0 || selectedCreators.includes(rowCreator);

            if (matchSearch && matchClient && matchOrderDate && matchSowingDate && matchCreator) {
                $strip.show();
                visibleCount++;
            } else {
                $strip.hide();
            }
        });

        if (visibleCount === 0 && globalOrders.length > 0) {
            $('#noResultsState').show();
        } else if (globalOrders.length > 0) {
            $('#noResultsState').hide();
        }
    }

    /**
     * Approve Button Click Handler
     */
    $(document).on('click', '.btnApproveOrder', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const psono = $btn.data('psono');
        const clientName = $btn.data('client') || 'the client';
        const projectid = $btn.data('projectid') || window.EMERGENCY_CONTEXT.projectid;
        const loginid = window.EMERGENCY_CONTEXT.loginid;
        const loginname = window.EMERGENCY_CONTEXT.loginname || loginid;

        Swal.fire({
            title: 'Confirm Emergency Sowing Approval',
            html: `
                <div class="text-start p-1" style="font-size: 0.92rem;">
                    <p class="mb-3 text-dark">
                        Are you sure you want to approve emergency sowing for order <strong class="text-primary font-monospace">${escapeHtml(psono)}</strong> (${escapeHtml(clientName)})?
                    </p>
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2 text-success fw-bold">
                            <i class="fa fa-check-circle"></i> Status will be updated to: <span class="badge bg-secondary text-white">Draft</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2 text-muted small">
                            <i class="fa fa-user-check"></i> Approval will be recorded by: <strong>${escapeHtml(loginname)}</strong>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-primary small">
                            <i class="fa fa-whatsapp"></i> WhatsApp alert will be sent to the order creator.
                        </div>
                    </div>
                    <div class="alert alert-info border-0 p-2 rounded-2 mb-0 d-flex align-items-center gap-2 small">
                        <i class="fa fa-info-circle flex-shrink-0"></i>
                        <span>Once approved, status moves to Draft and creator can proceed to payment advice / next action.</span>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-check-circle me-1"></i> Yes, Approve Emergency Sowing',
            cancelButtonText: '<i class="fa fa-times me-1"></i> Cancel',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#64748b',
            focusConfirm: false,
            allowOutsideClick: false,
            customClass: {
                confirmButton: 'btn btn-success px-4 py-2 fw-bold',
                cancelButton: 'btn btn-secondary px-4 py-2 fw-semibold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Approving...');

                EmergencySowingAPI.approveOrder(psono, projectid, loginid, loginname)
                    .done(function (res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Approved Successfully!',
                                text: res.message || `Order ${psono} approved, status updated to Draft, and WhatsApp notification sent.`,
                                timer: 2500,
                                showConfirmButton: false
                            });

                            $(`#order_strip_${psono}`).slideUp(300, function () {
                                $(this).remove();
                                loadEmergencyOrders();
                            });
                        } else {
                            $btn.prop('disabled', false).html('<i class="fa fa-check"></i> Approve');
                            Swal.fire({
                                icon: 'error',
                                title: 'Approval Failed',
                                text: res.message || 'Unable to approve order. Please try again.'
                            });
                        }
                    })
                    .fail(function (xhr, status, error) {
                        $btn.prop('disabled', false).html('<i class="fa fa-check"></i> Approve');
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: error || 'Failed to communicate with server.'
                        });
                    });
            }
        });
    });

    // View Order Handler
    $(document).on('click', '.viewOrderBtn', function () {
        const psono = $(this).data('psono');
        const companyid = window.EMERGENCY_CONTEXT.companyid;
        const subcompanyid = window.EMERGENCY_CONTEXT.subcompanyid;
        const projectid = window.EMERGENCY_CONTEXT.projectid;

        $.ajax({
            url: 'modal/fetch_order_view.php',
            type: 'POST',
            data: { psono: psono, companyid: companyid, subcompanyid: subcompanyid, projectid: projectid },
            success: function (data) {
                $('#viewOrderContent').html(data);
                $('#viewOrderModal').modal('show');
            }
        });
    });

    function escapeHtml(text) {
        if (text == null) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});

// Download Provisional Order PDF
function provisionalorderDownload(psono) {
    const routurl = (window.EMERGENCY_CONTEXT && window.EMERGENCY_CONTEXT.routurl) || $('body').data('routurl') || '../';
    const key = (window.EMERGENCY_CONTEXT && window.EMERGENCY_CONTEXT.key) || $('body').data('key') || '';
    window.open(routurl + 'krf-orderandsales/modal/provisionalnvoicePDF.php?psono=' + encodeURIComponent(psono) + '&k=' + encodeURIComponent(key), '_blank');
}
</script>
