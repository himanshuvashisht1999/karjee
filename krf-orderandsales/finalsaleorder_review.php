<?php
include('../header.php');
include('functions/orders_functions.php');

$mdid = "";
if (isset($_GET['mdid'])) {
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
  $groupid = $dddr[0]['groupid'];
}

$psnolist = GetFinalSalesOrdersWithProductDetails($krfconn,$authconn, $projectid,'','',$loginroleid,$loginid);
// print_r($psnolist);

/* Newest final order first, then highest sales-order number. */
usort($psnolist, static function (array $a, array $b): int {
    $timeA = !empty($a['created_at']) ? (strtotime($a['created_at']) ?: 0) : 0;
    $timeB = !empty($b['created_at']) ? (strtotime($b['created_at']) ?: 0) : 0;

    if ($timeA !== $timeB) {
        return $timeB <=> $timeA;
    }

    return strnatcasecmp((string)($b['sono'] ?? ''), (string)($a['sono'] ?? ''));
});

/* Unique, whitespace-cleaned, case-insensitive A-Z values for text filters. */
function krfFinalUniqueSortedFilterValues(array $values): array
{
    $unique = [];

    foreach ($values as $value) {
        $cleanValue = preg_replace('/\s+/u', ' ', trim((string)$value));
        if ($cleanValue === null || $cleanValue === '') {
            continue;
        }

        $key = function_exists('mb_strtolower')
            ? mb_strtolower($cleanValue, 'UTF-8')
            : strtolower($cleanValue);

        if (!array_key_exists($key, $unique)) {
            $unique[$key] = $cleanValue;
        }
    }

    $result = array_values($unique);
    usort($result, 'strnatcasecmp');

    return $result;
}

$uniqueOrderDates = [];
$uniqueDeliveryDates = [];
$filterStatusValues = [];
// print_r($psnolist);
foreach ($psnolist as $row) {
    $createdAt = trim((string)($row['created_at'] ?? ''));
    $createdTimestamp = $createdAt !== '' ? strtotime($createdAt) : false;
    if ($createdTimestamp !== false) {
        $uniqueOrderDates[date('Y-m-d', $createdTimestamp)] = true;
    }

    $deliveryDate = trim((string)($row['deliverydate'] ?? ''));
    if ($deliveryDate !== '' && $deliveryDate !== '0000-00-00') {
        $uniqueDeliveryDates[$deliveryDate] = true;
    }

    $filterStatusValues[] = $row['order_status'];
}

$uniqueOrderDates = array_keys($uniqueOrderDates);
$uniqueDeliveryDates = array_keys($uniqueDeliveryDates);
rsort($uniqueOrderDates, SORT_STRING);
rsort($uniqueDeliveryDates, SORT_STRING);

$uniqueClientNames = krfFinalUniqueSortedFilterValues(array_column($psnolist, 'clientname'));
$uniqueLoginNames = krfFinalUniqueSortedFilterValues(array_column($psnolist, 'loginname'));
$uniqueStatuses = krfFinalUniqueSortedFilterValues($filterStatusValues);

// Fetch all delivery addresses for the project indexed by psono / sono
$delivLocations = [];
$delQuery = "SELECT psono, sono, clientid, deliveryaddress, dest_state, dest_district, dest_city, dest_pincode, distance, deliveryqty 
             FROM krf_deliveryaddress_location 
             WHERE (tedit IS NULL OR tedit='') AND projectid = " . intval($projectid);
$delRes = $krfconn->query($delQuery);
if ($delRes) {
    while ($drow = $delRes->fetch_assoc()) {
        $p = trim($drow['psono'] ?? '');
        $s = trim($drow['sono'] ?? '');
        if (!empty($p)) {
            $delivLocations[$p][] = $drow;
        }
        if (!empty($s) && $s !== $p) {
            $delivLocations[$s][] = $drow;
        }
    }
}

function getOrderGroupColor($sono) {
    if (empty($sono)) return "#ffffff";
    $hash = md5($sono);
    $r = (hexdec(substr($hash, 0, 2)) % 40) + 215; 
    $g = (hexdec(substr($hash, 2, 2)) % 40) + 215; 
    $b = (hexdec(substr($hash, 4, 2)) % 40) + 215; 
    return "rgb($r, $g, $b)";
}
?>

<style>
  :root {
    --primary-blue: #007bff;
    --text-main: #333333;
    --text-muted: #888888;
    --bg-light: #f4f6f9;
    --card-shadow: 0 2px 10px rgba(0,0,0,0.05);
    --transition: all 0.3s ease;
  }

  .order-dashboard {
    padding: 2rem;
    background: #f8f9fa;
    min-height: 100vh;
    font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  }

  /* Main Header Card */
  .main-title-card {
    background: white;
    padding: 1.5rem 2rem;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    margin-bottom: 1rem;
    border-left: 6px solid var(--primary-blue);
    display: flex;
    justify-content: space-between;
    align-items: center;
    animation: fadeInDown 0.5s ease-out;
  }

  .title-content {
    display: flex;
    align-items: center;
    gap: 1.25rem;
  }

  .title-icon-wrapper {
    background: #e7f1ff;
    color: var(--primary-blue);
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 1.4rem;
  }

  .title-text h4 {
    margin: 0;
    font-weight: 400;
    color: var(--text-main);
    font-size: 1.8rem;
    letter-spacing: -0.5px;
  }

  .title-text p {
    margin: 4px 0 0;
    color: var(--text-muted);
    font-size: 1.1rem;
    font-weight: 300;
  }

  /* Search & Actions Strip */
  .search-wrapper {
    position: relative;
    flex: 1;
    max-width: 400px;
  }

  .search-wrapper i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
    font-size: 0.9rem;
  }

  .search-control {
    width: 100%;
    padding: 0.6rem 1rem 0.6rem 2.8rem;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    font-size: 0.95rem;
    transition: var(--transition);
    color: #4a5568;
  }

  .search-control:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    outline: none;
  }

  .btn-new-order {
    background: var(--primary-blue);
    color: white;
    border: none;
    padding: 0.65rem 1.25rem;
    border-radius: 8px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    transition: var(--transition);
    box-shadow: 0 4px 6px rgba(0, 123, 255, 0.15);
  }

  .btn-new-order:hover {
    background: #0069d9;
    transform: translateY(-1px);
    box-shadow: 0 6px 12px rgba(0, 123, 255, 0.2);
    color: white;
  }

  /* Filter Strip Styles */
  .filter-strip {
    background: white;
    padding: 1.25rem 1.5rem;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    margin-bottom: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    align-items: flex-end;
    animation: fadeInUp 0.5s ease-out;
    border: 1px solid #edf2f7;
    position: relative;
    z-index: 1020;
  }

  .filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex: 1;
    min-width: 180px;
  }

  .filter-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #4a5568;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .filter-control {
    width: 100%;
    padding: 0.6rem 0.75rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    font-size: 0.9rem;
    color: #2d3748;
    transition: var(--transition);
    background-color: #f8fafc;
  }

  .filter-control:focus {
    border-color: var(--primary-blue);
    background-color: white;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
  }

  .btn-clear-filters {
    padding: 0.6rem 1.25rem;
    border-radius: 8px;
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    color: #718096;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    height: 40px;
  }

  .btn-clear-filters:hover {
    background: #edf2f7;
    color: #2d3748;
  }

  .btn-export {
    padding: 0.6rem 1.25rem;
    border-radius: 8px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    height: 40px;
    white-space: nowrap;
  }

  .btn-export:hover {
    background: #d1fae5;
    color: #047857;
    border-color: #6ee7b7;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(16, 185, 129, 0.15);
  }

  .filter-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    position: relative;
    z-index: 1030;
  }

  .filter-actions .dropdown {
    position: relative;
  }

  .filter-actions .dropdown-menu {
    position: absolute !important;
    z-index: 1060 !important;
    min-width: 220px !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18) !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;
    padding: 6px 0 !important;
    background: #ffffff !important;
    margin-top: 4px !important;
  }

  .filter-actions .dropdown-item {
    display: flex !important;
    align-items: center !important;
    padding: 9px 16px !important;
    font-size: 0.88rem !important;
    font-weight: 500 !important;
    color: #334155 !important;
    text-decoration: none !important;
    line-height: 1.4 !important;
    transition: all 0.15s ease !important;
  }

  .filter-actions .dropdown-item i {
    width: 20px !important;
    font-size: 1rem !important;
    margin-right: 10px !important;
    text-align: center !important;
    flex-shrink: 0 !important;
    display: inline-block !important;
  }

  .filter-actions .dropdown-item:hover {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
  }

  @keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .order-strips-container { display: flex; flex-direction: column; gap: 0.5rem; }
  .order-strip {
    background: white; border-radius: 8px; padding: 0.75rem 1.25rem;
    display: grid; grid-template-columns: 40px 1.4fr 2fr 2.5fr 1.2fr 1.2fr 1.1fr;
    align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid #edf2f7; transition: var(--transition);
    position: relative; overflow: hidden;
  }
  .order-strip:hover { transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border-color: #cbd5e0; }
  .order-strip::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #48bb78; }
  .order-strip.status-confirmed::before { background: #48bb78; }
  .order-strip.status-completed::before { background: #38a169; }
  .order-strip.status-delivered::before { background: #3182ce; }
  .order-strip.status-pending::before { background: #f59e0b; }
  .order-strip.status-cancelled::before { background: #e53e3e; }
  .strip-item { min-width: 0; padding-right: 0.5rem; word-break: break-word; }
  .strip-label { font-size: 0.75rem; text-transform: uppercase; color: #718096; font-weight: 600; margin-bottom: 0.25rem; display: none; }
  .strip-content { font-weight: 500; color: #333; font-size: 0.9rem; }
  .order-no-pill { background: #f7fafc; padding: 0.15rem 0.5rem; border-radius: 9999px; font-family: monospace; font-size: 0.8rem; border: 1px solid #e2e8f0; }
  .amount-display { color: #2c5282; font-weight: 700; font-size: 1rem; }
  .action-buttons { display: flex; gap: 0.35rem; justify-content: flex-end; }
  .btn-strip { width: 32px; height: 32px; border-radius: 6px; border: none; display: flex; align-items: center; justify-content: center; transition: var(--transition); font-size: 0.8rem; }
  .btn-view { background: #ebf8ff; color: #3182ce; } .btn-view:hover { background: #3182ce; color: white; }
  .btn-edit { background: #faf5ff; color: #805ad5; } .btn-edit:hover { background: #805ad5; color: white; }
  .btn-pdf { background: #fff5f5; color: #e53e3e; } .btn-pdf:hover { background: #e53e3e; color: white; }

  .strips-header {
    position: sticky !important;
    top: 0;
    z-index: 1000;
    display: grid; grid-template-columns: 40px 1.4fr 2fr 2.5fr 1.2fr 1.2fr 1.1fr;
    padding: 0.75rem 1.25rem; color: #718096; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;
    background: #e9ecef !important; border-radius: 8px; margin-bottom: 0.5rem; border: 1px solid #f1f1f1;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  .strip-delivery-footer {
    grid-column: 1 / -1;
    margin-top: 0.6rem;
    padding-top: 0.55rem;
    border-top: 1px dashed rgba(0, 0, 0, 0.12);
    font-size: 0.84rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  @media (max-width: 992px) {
    .strips-header { display: none; }
    .order-strip { grid-template-columns: 1fr 1fr; gap: 1rem; }
    .strip-label { display: block; }
    .action-buttons { grid-column: span 2; justify-content: center; }
  }
</style>

<div class="container-main order-dashboard">
  <div class="main-title-card">
    <div class="title-content">
      <div class="title-icon-wrapper shadow-sm">
        <i class="fa fa-file-invoice-dollar"></i>
      </div>
      <div class="title-text">
        <h4>Final Sales Orders</h4>
        <p>Track and manage your final sales orders</p>
      </div>
    </div>
    <div class="header-actions">
      <button class="btn btn-sm btn-outline-secondary mr-2" onclick="window.location.reload();">
        <i class="fa fa-sync"></i> Refresh
      </button>
      <button class="btn-new-order newprovisionalorder d-inline-flex mr-2">
        <i class="fa fa-plus-circle"></i> New Provisional Order
      </button>
      <?php if (isset($loginroleid) && (int)$loginroleid <= 2): ?>
      <button class="btn-new-order newproductionorder d-inline-flex">
        <i class="fa fa-industry"></i> New Production Order
      </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="filter-strip">
    <div class="filter-group">
      <label class="filter-label">Choose Client</label>
      <select id="clientFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Clients">
        <?php foreach ($uniqueClientNames as $clientName): ?>
          <option value="<?php echo htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <label class="filter-label">Delivery Date</label>
      <select id="deliverDateFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Dates">
        <?php foreach ($uniqueDeliveryDates as $dDate): ?>
          <option value="<?php echo $dDate; ?>">
            <?php echo date('d-M-Y', strtotime($dDate)); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <label class="filter-label">Order Date</label>
      <select id="orderDateFilter" class="filter-control select2" multiple="multiple" data-placeholder="All Dates">
        <?php foreach ($uniqueOrderDates as $oDate): ?>
          <option value="<?php echo $oDate; ?>">
            <?php echo date('d-M-Y', strtotime($oDate)); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <label class="filter-label" for="loginFilter">Choose Confirmed By</label>

      <select
          id="loginFilter"
          class="filter-control select2"
          multiple
          data-placeholder="All Confirmed By"
      >
          <?php foreach ($uniqueLoginNames as $login): ?>
              <option value="<?= htmlspecialchars($login, ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars($login, ENT_QUOTES, 'UTF-8') ?>
              </option>
          <?php endforeach; ?>
      </select>
  </div>
    <div class="filter-group">
      <label class="filter-label">Search</label>
      <div class="search-wrapper" style="max-width: 100%;">
        <i class="fa fa-search"></i>
        <input type="text" id="orderSearchInput" class="search-control" placeholder="Search client, mobile, order or product..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
      </div>
    </div>

    <div class="filter-actions">
      <button type="button" id="clearFilters" class="btn-clear-filters">
        <i class="fa fa-times-circle"></i> Clear Filters
      </button>
      <div class="dropdown">
        <button class="btn-export dropdown-toggle" type="button" id="exportOrdersDropdown" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i class="fa fa-file-excel text-success me-1"></i> Export <span class="caret"></span>
        </button>
        <div class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="exportOrdersDropdown">
          <a class="dropdown-item" href="javascript:void(0)" onclick="exportFinalSalesOrders('excel')">
            <i class="fa fa-file-excel text-success"></i> <span>Export to Excel (.csv)</span>
          </a>
          <a class="dropdown-item" href="javascript:void(0)" onclick="exportFinalSalesOrders('csv')">
            <i class="fa fa-file-csv text-primary"></i> <span>Export to CSV</span>
          </a>
          <div class="dropdown-divider my-1"></div>
          <a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">
            <i class="fa fa-print text-secondary"></i> <span>Print View</span>
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="strips-header">
    <div>#</div>
    <div>Date/Sales Order</div>
    <div>Client Name / Provisional Order</div>
    <div>Product / Qty / Rate</div>
    <div>Delivery Date</div>
    <div>Amount</div>
    <div class="text-end">Actions</div>
  </div>

  <div class="order-strips-container" id="orderStripsList">
    <?php
    if (count($psnolist) > 0):
      $sno = 1;
      foreach ($psnolist as $row):
        $orderStatus = $row['order_status'];
        $statusClass = match ($orderStatus) {
          'Cancelled' => 'status-cancelled',
          'Completed' => 'status-completed',
          'Delivered' => 'status-delivered',
          'Pending'   => 'status-pending',
          default     => 'status-confirmed'
        };

        $badgeClass = match ($orderStatus) {
          'Cancelled' => 'bg-danger text-white',
          'Completed' => 'bg-success text-white',
          'Delivered' => 'bg-info text-white',
          'Pending'   => 'bg-warning text-dark',
          default     => 'bg-success text-white'
        };

        $groupColor = getOrderGroupColor($row['sono']);
        
        $pickupfrom = strtolower(trim($row['pickupfrom'] ?? ''));
        $psono = trim($row['psono'] ?? '');
        $sono = trim($row['sono'] ?? '');
        $orderLocations = $delivLocations[$psono] ?? ($delivLocations[$sono] ?? []);
        
        $locDisplayList = [];
        $locSearchableStr = '';
        foreach ($orderLocations as $loc) {
            $fullAddr = trim($loc['deliveryaddress'] ?? '');
            if (!empty($fullAddr)) {
                $geoParts = array_filter([$loc['dest_city'] ?? '', $loc['dest_district'] ?? '', $loc['dest_state'] ?? '', $loc['dest_pincode'] ?? '']);
                $geoStr = implode(', ', $geoParts);
                $locText = $fullAddr;
                if (!empty($geoStr) && stripos($fullAddr, $geoParts[0] ?? '') === false) {
                    $locText .= ' (' . $geoStr . ')';
                }
                if (!empty($loc['distance']) && floatval($loc['distance']) > 0) {
                    $locText .= ' [' . floatval($loc['distance']) . ' Km]';
                }
                if (!in_array($locText, $locDisplayList)) {
                    $locDisplayList[] = $locText;
                }
                $locSearchableStr .= ' ' . $fullAddr . ' ' . $geoStr;
            }
        }

        $searchableText = strtolower(implode(' ', [
          $row['sono'],
          $row['psono'] ?? '',
          $row['clientname'],
          $row['mobileno'] ?? '',
          $row['loginname'] ?? '',
          $row['product_name'] ?? '',
          $row['quantity'],
          $row['rate'],
          // $orderStatus,
          number_format($row['amount'], 2),
          date('d-M-Y', strtotime($row['created_at'])),
          (!empty($row['psodate']) && $row['psodate'] != '0000-00-00 00:00:00') ? date('d-M-Y', strtotime($row['psodate'])) : '',
          $row['deliverydate'] ?? '',
          $pickupfrom === 'pickup' ? 'self pickup' : 'company delivery',
          $locSearchableStr
        ]));
        ?>
        <div class="order-strip <?php echo $statusClass; ?>" data-search-term="<?php echo htmlspecialchars($searchableText, ENT_QUOTES, 'UTF-8'); ?>" style="background-color: <?php echo $groupColor; ?>;" data-status="<?php echo htmlspecialchars($orderStatus, ENT_QUOTES, 'UTF-8'); ?>" data-mobile="<?php echo htmlspecialchars($row['mobileno'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-login="<?php echo htmlspecialchars(trim($row['loginname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-psono="<?php echo htmlspecialchars($row['psono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          <div class="strip-item">
            <span class="strip-label">#</span>
            <div class="strip-content text-muted small"><?php echo str_pad($sno++, 2, '0', STR_PAD_LEFT); ?></div>
          </div>
          
          <div class="strip-item" data-order-date="<?php echo date('Y-m-d', strtotime($row['created_at'])); ?>">
            <span class="strip-label">Date/Sales Order</span>
            <div class="strip-content">
               <div class="small text-muted mb-1"><?php echo date('d-M-Y', strtotime($row['created_at'])); ?></div>
               <span class="order-no-pill"><?php echo htmlspecialchars($row['sono']); ?></span>
            </div>
          </div>

          <div class="strip-item" data-client="<?php echo htmlspecialchars($row['clientname']); ?>">
            <span class="strip-label">Client Name / Provisional Order</span>
            <div class="strip-content">
               
             <div class="strip-content text-primary font-weight-bold">
                  <?php echo htmlspecialchars($row['clientname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>

                  <?php if (!empty($row['mobileno'])) { ?>
                      (<?php echo htmlspecialchars($row['mobileno'], ENT_QUOTES, 'UTF-8'); ?>)
                  <?php } ?>
              </div>



              <?php if (!empty($row['psono'])): ?>
                <div class="small text-muted d-flex align-items-center gap-1 flex-wrap">
                  <span class="order-no-pill" style="font-size: 0.76rem; background: #fff;"><?php echo htmlspecialchars($row['psono']); ?></span>
                  <?php if (!empty($row['psodate']) && $row['psodate'] != '0000-00-00 00:00:00'): ?>
                    <span class="text-muted" style="font-size: 0.78rem;">(<?php echo date('d-M-Y', strtotime($row['psodate'])); ?>)</span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="strip-item" data-deliver-date="<?php echo htmlspecialchars($row['deliverydate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <span class="strip-label">Product / Qty / Rate</span>
            <div class="strip-content">
                <div class="font-weight-bold" style="line-height: 1.25; margin-bottom: 0.2rem;"><?php echo htmlspecialchars($row['product_name'] ?? 'N/A'); ?> <span class="badge bg-info d-none"><?php echo $row['product_sku'] ?? ''; ?></span></div>
                <div class="small mt-1 d-flex gap-3 flex-wrap">
                    <span>Qty: <span class="badge bg-light text-dark border"><?php echo number_format($row['quantity'], 0); ?></span></span>
                    <span>Rate: <span class="text-dark font-weight-bold"><?php echo $row['rate']; ?></span></span>
                </div>
            </div>
          </div>

          <div class="strip-item">
            <span class="strip-label">Delivery Date</span>
            <div class="strip-content">
               <span class="font-weight-bold"><?php echo ($row['deliverydate'] && $row['deliverydate'] != '0000-00-00') ? date('d-M-Y', strtotime($row['deliverydate'])) : 'N/A'; ?></span>
            </div>
          </div>

          <div class="strip-item">
            <span class="strip-label">Amount</span>
            <div class="strip-content amount-display">₹<?php echo number_format($row['amount'], 2); ?></div>
          </div>
          <div class="action-buttons">
            <button class="btn btn-strip btn-view viewfinalsalesOrderBtn" data-sono="<?php echo $row['sono']; ?>" data-psono="<?php echo $row['psono']; ?>" title="View Order">
              <i class="fa fa-eye"></i>
            </button>
            <button class="btn btn-strip btn-edit editOrderBtn d-none" data-sono="<?php echo $row['sono']; ?>" data-psono="<?php echo $row['psono']; ?>" title="Edit Order">
              <i class="fa fa-pencil-alt"></i>
            </button>
            <button class="btn btn-strip btn-pdf" onclick="FinalSalesOrderDownload('<?php echo $row['sono']; ?>');" title="Download PDF">
              <i class="fa fa-file-pdf"></i>
            </button>
          </div>

          <!-- Full Width Delivery Address / Pickup Footer -->
          <div class="strip-delivery-footer">
            <?php if (isset($row['orderamount'])): ?>
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 w-100"> 
                    <div class="amount-box col-md-3">
                        <span>Order Amount</span>
                        <strong> ₹<?= number_format((float)$row['orderamount'], 2) ?>
                        </strong>
                    </div>
                    <div class="amount-box col-md-3">
                        <span>Received Amount</span>
                        <strong class="text-success"> ₹<?= number_format( (float)($row['advanceamt'] ?? 0), 2 ) ?>
                        </strong>
                    </div>

                    <div class="amount-box col-md-3">
                        <span>Balance Amount</span>
                        <strong class="text-danger">
                            ₹<?= number_format( (float)($row['balanceamt'] ?? 0), 2) ?>
                        </strong>
                    </div>
                </div> 
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 w-100">
              <div class="d-flex align-items-center gap-2 flex-wrap" style="min-width: 0;">
                <?php if ($pickupfrom === 'pickup'): ?>
                  <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 0.78rem;">
                    <i class="fa fa-walking me-1"></i> Self Pickup by Client
                  </span>
                  <span class="text-muted" style="font-size: 0.83rem;">
                    Pickup by client as mentioned delivery date<?php if (!empty($row['deliverydate']) && $row['deliverydate'] != '0000-00-00'): ?>: <strong><?php echo date('d-M-Y', strtotime($row['deliverydate'])); ?></strong><?php endif; ?>
                  </span>
                <?php elseif ($pickupfrom === 'boxpacking'): ?>
                  <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1" style="font-size: 0.78rem;">
                    <i class="fa fa-box me-1"></i> Box Packing
                  </span>
                  <?php if (!empty($locDisplayList)): ?>
                    <span class="fw-bold text-secondary" style="font-size: 0.8rem;">Delivery Address:</span>
                    <span class="text-dark fw-medium" style="font-size: 0.84rem;">
                      <?php echo htmlspecialchars(implode('; ', $locDisplayList)); ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted" style="font-size: 0.83rem;">Box Packing for dispatch.</span>
                  <?php endif; ?>
                <?php else: ?>
                  <?php if (!empty($locDisplayList)): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.78rem;">
                      <i class="fa fa-truck me-1"></i> Company Delivery
                    </span>
                    <span class="fw-bold text-secondary" style="font-size: 0.8rem;">Delivery Address:</span>
                    <span class="text-dark fw-medium" style="font-size: 0.84rem;">
                      <?php echo htmlspecialchars(implode('; ', $locDisplayList)); ?>
                    </span>
                  <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 0.78rem;">
                      <i class="fa fa-exclamation-triangle me-1"></i> Missing Delivery Address
                    </span>
                    <span class="text-danger fw-semibold" style="font-size: 0.82rem;">
                      Delivery mode is Company Delivery, but no delivery location is recorded.
                    </span>
                  <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($row['loginname'])): ?>
                  <span class="text-secondary ms-1" style="font-size: 0.82rem;">
                    | Confirmed By: <strong class="text-dark"><?php echo htmlspecialchars($row['loginname']); ?></strong>
                    <?php if (!empty($row['created_at']) && $row['created_at'] != '0000-00-00 00:00:00'): ?>
                      (<strong><?php echo date('d-M-Y', strtotime($row['created_at'])); ?></strong>)
                    <?php endif; ?>
                  </span>
                <?php endif; ?>

                <?php if (!empty($row['order_updated_by']) && !empty($row['updated_at']) && $row['updated_at'] != '0000-00-00 00:00:00'): ?>
                  <span class="text-secondary" style="font-size: 0.82rem;">
                    | Last Updated By: <strong><?php echo htmlspecialchars($row['updatedby_name'] ?: $row['order_updated_by']); ?></strong>
                    at <strong><?php echo date('d-M-Y h:i A', strtotime($row['updated_at'])); ?></strong>
                  </span>
                <?php endif; ?>
              </div>
              
              <div>
                  <?php if ($orderStatus !== 'Cancelled' && ((int)$loginroleid <= 3 || empty($locDisplayList))) : ?>
                    <button type="button" onclick="openDeliveryAddressModal('<?php echo htmlspecialchars($row['clientid'] ?? ''); ?>', '<?php echo htmlspecialchars($row['psono'] ?? $row['sono']); ?>')" class="btn btn-sm btn-outline-primary px-2 py-1 fw-bold shadow-sm d-inline-flex align-items-center gap-1" style="font-size: 0.76rem; border-radius: 6px; background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; white-space: nowrap;">
                      <i class="fa fa-edit"></i> Update Address
                    </button>
                   <?php endif; ?>  
              </div>
            </div>
          </div>
        </div>
      <?php endforeach;
    else: ?>
      <div class="text-center py-5 bg-white rounded-3 shadow-sm border">
        <h5 class="text-muted">No Final Sales Orders Found</h5>
        <a href="provisionalsaleorder_review.php?k=<?php echo $key ?? ''; ?>&mdid=<?php echo $mdid; ?>" class="btn btn-outline-primary btn-sm mt-3">View Provisional Orders</a>
      </div>
    <?php endif; ?>
    <div id="noSearchResults" class="text-center py-5 bg-white rounded-3 shadow-sm border" style="display: none;">
      <h5 class="text-muted">No Matching Orders Found</h5>
    </div>
  </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-light">
        <h5 class="modal-title font-weight-bold">Final Sales Order Details</h5>
        <button type="button" class="close" onclick="$('#viewOrderModal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="viewOrderContent"></div>
    </div>
  </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 90%;">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Edit Final Sales Order</h5>
        <button type="button" class="close text-white" onclick="$('#editOrderModal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="editOrderContent"></div>
    </div>
  </div>
</div>

<!-- Other Modal -->
<div class="modal fade" id="otherModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 90%;">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="otherModalheader"></h5>
        <button type="button" class="close text-white" onclick="$('#otherModal').modal('hide')">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="otherModalContent"></div>
    </div>
  </div>
</div>

<!-- Dedicated Order Delivery & Freight Modal -->
<div class="modal fade" id="orderDeliveryFreightModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 92vw;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center">
        <h5 class="modal-title fw-bold fs-15 mb-0"><i class="fa fa-truck-loading me-2"></i>Manage Delivery Address & Freight Charges</h5>
        <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" onclick="$('#orderDeliveryFreightModal').modal('hide')" aria-label="Close" style="background: rgba(255,255,255,0.2); border: none; font-size: 1.4rem; line-height: 1; border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-3" id="orderDeliveryFreightContent" style="min-height: 520px; max-height: 85vh; overflow-y: auto;">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-2 text-muted fw-bold">Loading delivery and freight details...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<script>
  function openDeliveryAddressModal(clientId, orderId) {
    let companyid = $("body").data("companyid");
    let subcompanyid = $("body").data("subcompanyid");
    let projectid = $("body").data("projectid");

    $("#orderDeliveryFreightContent").html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted fw-bold">Loading delivery and freight details...</p></div>');
    $("#orderDeliveryFreightModal").modal("show");

    $.ajax({
      url: "modal/fetch_order_delivery_freight.php",
      type: "POST",
      data: { orderid: orderId, clientid: clientId, companyid: companyid, subcompanyid: subcompanyid, projectid: projectid },
      success: function (data) {
        $("#orderDeliveryFreightContent").html(data);
      },
      error: function() {
        $("#orderDeliveryFreightContent").html('<div class="alert alert-danger p-3">Failed to load order delivery details.</div>');
      }
    });
  }
  $(document).ready(function () {
    var $finalDashboard = $('.order-dashboard');

    function normalizeFinalOrderText(value) {
      return String(value || '')
        .toLowerCase()
        .replace(/\u00a0/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    }

    function formatFinalFilterDate(value) {
      var parts = String(value || '').split('-');
      if (parts.length !== 3) return value;

      var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      var monthIndex = Number(parts[1]) - 1;
      if (monthIndex < 0 || monthIndex > 11) return value;

      return parts[2] + '-' + months[monthIndex] + '-' + parts[0];
    }

    function rebuildFinalOrderFilterOptions(dashboard) {
      var rows = dashboard.querySelectorAll('#orderStripsList > .order-strip');
      var definitions = [
        {
          selector: '#clientFilter',
          value: function (row) {
            var cell = row.querySelector('[data-client]');
            return cell ? cell.getAttribute('data-client') : '';
          },
          date: false
        },
        {
          selector: '#deliverDateFilter',
          value: function (row) {
            var cell = row.querySelector('[data-deliver-date]');
            return cell ? cell.getAttribute('data-deliver-date') : '';
          },
          date: true
        },
        {
          selector: '#orderDateFilter',
          value: function (row) {
            var cell = row.querySelector('[data-order-date]');
            return cell ? cell.getAttribute('data-order-date') : '';
          },
          date: true
        },
        {
          selector: '#loginFilter',
          value: function (row) {
            return row.getAttribute('data-login') || '';
          },
          date: false
        },
        {
          selector: '#statusFilter',
          value: function (row) {
            return row.getAttribute('data-status') || '';
          },
          date: false
        }
      ];

      definitions.forEach(function (definition) {
        var select = dashboard.querySelector(definition.selector);
        if (!select) return;

        var $select = $(select);
        if ($select.hasClass('select2-hidden-accessible') && $.fn.select2) {
          $select.select2('destroy');
        }

        var unique = new Map();
        Array.prototype.forEach.call(rows, function (row) {
          var value = String(definition.value(row) || '')
            .replace(/\u00a0/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
          var key = normalizeFinalOrderText(value);

          if (key && !unique.has(key)) unique.set(key, value);
        });

        var values = Array.from(unique.values());
        values.sort(function (a, b) {
          return definition.date
            ? String(b).localeCompare(String(a))
            : String(a).localeCompare(String(b), undefined, {
                numeric: true,
                sensitivity: 'base'
              });
        });

        select.replaceChildren();
        values.forEach(function (value) {
          select.add(new Option(
            definition.date ? formatFinalFilterDate(value) : value,
            value,
            false,
            false
          ));
        });

      });
    }

    $finalDashboard.each(function () {
      rebuildFinalOrderFilterOptions(this);
    });

    if ($.fn.select2) {
      $finalDashboard.find('.select2').each(function () {
        var $select = $(this);
        if (!$select.hasClass('select2-hidden-accessible')) {
          $select.select2({
            width: '100%',
            closeOnSelect: false,
            placeholder: $select.attr('data-placeholder') || '',
            templateResult: function (data) {
              return data.id ? $('<span>').text(data.text) : data.text;
            }
          });
        }
      });
    }

    function selectedFinalOrderValues(dashboard, selector) {
      var select = dashboard.querySelector(selector);
      return select
        ? Array.prototype.map.call(select.selectedOptions, function (option) {
            return normalizeFinalOrderText(option.value);
          })
        : [];
    }

    function resolveFinalOrderDashboard(sourceElement) {
      if (sourceElement && typeof sourceElement.closest === 'function') {
        var localDashboard = sourceElement.closest('.order-dashboard');
        if (localDashboard) return localDashboard;
      }

      var dashboards = document.querySelectorAll('.order-dashboard');
      for (var i = dashboards.length - 1; i >= 0; i--) {
        if (dashboards[i].offsetParent !== null) return dashboards[i];
      }

      return dashboards.length ? dashboards[dashboards.length - 1] : null;
    }

    window.filterFinalSalesOrders = function (sourceElement) {
      var dashboard = resolveFinalOrderDashboard(sourceElement);
      if (!dashboard) return;

      var searchInput = dashboard.querySelector('#orderSearchInput');
      var searchWords = normalizeFinalOrderText(searchInput ? searchInput.value : '')
        .split(' ')
        .filter(Boolean);

      var selectedClients = selectedFinalOrderValues(dashboard, '#clientFilter');
      var selectedDeliveryDates = selectedFinalOrderValues(dashboard, '#deliverDateFilter');
      var selectedOrderDates = selectedFinalOrderValues(dashboard, '#orderDateFilter');
      var selectedLoginNames = selectedFinalOrderValues(dashboard, '#loginFilter');
      var selectedStatuses = selectedFinalOrderValues(dashboard, '#statusFilter');
      var rows = dashboard.querySelectorAll('#orderStripsList > .order-strip');
      var visibleCount = 0;

      Array.prototype.forEach.call(rows, function (row) {
        var clientCell = row.querySelector('[data-client]');
        var deliveryDateCell = row.querySelector('[data-deliver-date]');
        var orderDateCell = row.querySelector('[data-order-date]');
        var rowClient = normalizeFinalOrderText(clientCell ? clientCell.getAttribute('data-client') : '');
        var rowDeliveryDate = normalizeFinalOrderText(deliveryDateCell ? deliveryDateCell.getAttribute('data-deliver-date') : '');
        var rowOrderDate = normalizeFinalOrderText(orderDateCell ? orderDateCell.getAttribute('data-order-date') : '');
        var rowLoginName = normalizeFinalOrderText(row.getAttribute('data-login') || '');
        var rowStatus = normalizeFinalOrderText(row.getAttribute('data-status') || '');
        var rowSearchText = normalizeFinalOrderText(row.getAttribute('data-search-term') || '');

        var matchesSearch = searchWords.length === 0 || searchWords.every(function (word) {
          return rowSearchText.indexOf(word) !== -1;
        });

        var shouldShow = matchesSearch &&
          (selectedClients.length === 0 || selectedClients.indexOf(rowClient) !== -1) &&
          (selectedDeliveryDates.length === 0 || selectedDeliveryDates.indexOf(rowDeliveryDate) !== -1) &&
          (selectedOrderDates.length === 0 || selectedOrderDates.indexOf(rowOrderDate) !== -1) &&
          (selectedLoginNames.length === 0 || selectedLoginNames.indexOf(rowLoginName) !== -1) &&
          (selectedStatuses.length === 0 || selectedStatuses.indexOf(rowStatus) !== -1);

        row.style.setProperty('display', shouldShow ? 'grid' : 'none', 'important');
        row.hidden = !shouldShow;

        if (shouldShow) {
          visibleCount++;
          var serialCell = row.querySelector('.strip-item:first-child .strip-content');
          if (serialCell) serialCell.textContent = String(visibleCount).padStart(2, '0');
        }
      });

      var noResults = dashboard.querySelector('#noSearchResults');
      if (noResults) {
        var showNoResults = rows.length > 0 && visibleCount === 0;
        noResults.hidden = !showNoResults;
        noResults.style.display = showNoResults ? 'block' : 'none';
      }
    };

    var finalFilterSelector =
      '.order-dashboard #clientFilter, ' +
      '.order-dashboard #deliverDateFilter, ' +
      '.order-dashboard #orderDateFilter, ' +
      '.order-dashboard #loginFilter';

    $(document)
      .off('input.finalOrderFilters', '.order-dashboard #orderSearchInput')
      .on('input.finalOrderFilters', '.order-dashboard #orderSearchInput', function () {
        window.filterFinalSalesOrders(this);
      })
      .off(
        'change.finalOrderFilters ' +
        'select2:select.finalOrderFilters ' +
        'select2:unselect.finalOrderFilters ' +
        'select2:clear.finalOrderFilters',
        finalFilterSelector
      )
      .on(
        'change.finalOrderFilters ' +
        'select2:select.finalOrderFilters ' +
        'select2:unselect.finalOrderFilters ' +
        'select2:clear.finalOrderFilters',
        finalFilterSelector,
        function () {
          window.filterFinalSalesOrders(this);
        }
      )
      .off('click.finalOrderFilters', '.order-dashboard #clearFilters')
      .on('click.finalOrderFilters', '.order-dashboard #clearFilters', function () {
        var dashboard = this.closest('.order-dashboard');
        var searchInput = dashboard.querySelector('#orderSearchInput');
        if (searchInput) searchInput.value = '';

        $(dashboard)
          .find('#clientFilter, #deliverDateFilter, #orderDateFilter, #loginFilter')
          .val([])
          .trigger('change.select2');

        window.filterFinalSalesOrders(this);
      });

    window.filterFinalSalesOrders($finalDashboard.find('#orderSearchInput').get(0));

    // View Order
    $(document).on("click", ".viewfinalsalesOrderBtn", function () {
      let companyid = $("body").data("companyid");
      let subcompanyid = $("body").data("subcompanyid");
      let projectid = $("body").data("projectid");
      let sono = $(this).data("sono");
      $.ajax({
        url: "modal/fetch_salesorder_view.php",
        type: "POST",
        data: { sono: sono, companyid: companyid, subcompanyid: subcompanyid, projectid: projectid },
        success: function (data) {
          $("#viewOrderContent").html(data);
          $("#viewOrderModal").modal("show");
        }
      });
    });

    // Edit Order
    $(document).on("click", ".editOrderBtn", function () {
      let companyid = $("body").data("companyid");
      let subcompanyid = $("body").data("subcompanyid");
      let projectid = $("body").data("projectid");
      let psono = $(this).data("psono");
      $.ajax({
        url: "modal/fetch_order_edit.php",
        type: "POST",
        data: { psono: psono, companyid: companyid, subcompanyid: subcompanyid, projectid: projectid },
        success: function (data) {
          $("#editOrderContent").html(data);
          $("#editOrderModal").modal("show");
        }
      });
    });

    // newprovisionalorder
    $(document).on("click", ".newprovisionalorder", function () {
      let companyid = $("body").data("companyid");
      let subcompanyid = $("body").data("subcompanyid");
      let projectid = $("body").data("projectid");
      let routurl = $("body").data("routurl");
      let key = $("body").data("key");
      window.location.href = routurl + "krf-orderandsales/addNewProvisionalsaleorder.php?k=" + key + "&mdid=<?php echo $mdid; ?>&psono=&iframe=true";
    });

    $(document).on("click", ".newproductionorder", function () {
      let companyid = $("body").data("companyid");
      let subcompanyid = $("body").data("subcompanyid");
      let projectid = $("body").data("projectid");
      let routurl = $("body").data("routurl");
      let key = $("body").data("key");
      $("#otherModalContent").load(routurl + "krf-orderandsales/newProductionOrder.php?k=" + key + "&dz=11");
      $("#otherModal").modal("show");
      $("#otherModalheader").html("Production Stock Order");
    });
  });

  // Export Final Sales Orders Data to Excel / CSV
  function exportFinalSalesOrders(type) {
    let rows = [];
    let headers = [
      "S.No", 
      "Order Date", 
      "Sales Order No", 
      "Provisional Order No", 
      "Client Name", 
      "Contact No", 
      "Created By", 
      "Product Name", 
      "Quantity", 
      "Rate",
      "Delivery Date", 
      "Amount", 
      "Order Amount", 
      "Received Amount", 
      "Balance Amount"
    ];
    rows.push(headers.map(h => `"${h}"`).join(","));

    let $visibleStrips = $(".order-strip:visible");
    if ($visibleStrips.length === 0) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: "warning",
          title: "No Data Available",
          text: "There are no final sales order records matching your search/filter criteria."
        });
      } else {
        alert("There are no final sales order records matching your search/filter criteria.");
      }
      return;
    }

    $visibleStrips.each(function (index) {
      let $strip = $(this);
      let sono = String($strip.find(".order-no-pill").first().text() || "").trim();
      let psono = String($strip.attr("data-psono") ?? $strip.data("psono") ?? "").trim();
      let orderDate = String($strip.find(".strip-item[data-order-date]").data("order-date") || $strip.find(".strip-item:eq(1) .small").text() || "").trim();
      let clientName = String($strip.find(".strip-item[data-client]").data("client") || "").trim();
      let contactNo = String($strip.attr("data-mobile") ?? $strip.data("mobile") ?? "").trim();
      let createdBy = String($strip.attr("data-login") ?? $strip.data("login") ?? "").trim();
      let productName = String($strip.find(".strip-item:eq(3) .font-weight-bold").text() || "").trim();
      let qty = String($strip.find(".strip-item:eq(3) .badge").text() || "").trim().replace(/,/g, "");
      let rate = String($strip.find(".strip-item:eq(3) span:contains('Rate:') strong, .strip-item:eq(3) .font-weight-bold:last").text() || "").trim();
      let deliveryDate = String($strip.find(".strip-item:eq(4) .strip-content").text() || "").trim();
      let itemAmount = String($strip.find(".amount-display").text() || "").trim().replace(/[₹,]/g, "");
      let orderAmount = String($strip.find(".amount-box:eq(0) strong").text() || "").trim().replace(/[₹,]/g, "");
      let receivedAmount = String($strip.find(".amount-box:eq(1) strong").text() || "").trim().replace(/[₹,]/g, "");
      let balanceAmount = String($strip.find(".amount-box:eq(2) strong").text() || "").trim().replace(/[₹,]/g, "");

      let row = [
        index + 1,
        `"${orderDate.replace(/"/g, '""')}"`,
        `"${sono.replace(/"/g, '""')}"`,
        `"${psono.replace(/"/g, '""')}"`,
        `"${clientName.replace(/"/g, '""')}"`,
        `"${contactNo.replace(/"/g, '""')}"`,
        `"${createdBy.replace(/"/g, '""')}"`,
        `"${productName.replace(/"/g, '""')}"`,
        qty,
        rate,
        `"${deliveryDate.replace(/"/g, '""')}"`,
        itemAmount,
        orderAmount,
        receivedAmount,
        balanceAmount
      ];
      rows.push(row.join(","));
    });

    let csvContent = "\uFEFF" + rows.join("\r\n");
    let blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    let url = URL.createObjectURL(blob);
    let link = document.createElement("a");
    let todayStr = new Date().toISOString().slice(0, 10);
    link.setAttribute("href", url);
    link.setAttribute("download", `Final_Sales_Orders_${todayStr}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: "success",
        title: "Export Downloaded",
        text: `Exported ${$visibleStrips.length} final sales order record(s) successfully!`,
        timer: 2000,
        showConfirmButton: false
      });
    }
  }
</script>
