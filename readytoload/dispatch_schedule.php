<?php
/**
 * Dispatch Schedule by Client & Date - Informational Dashboard
 * Table Source: `karjeefarm`.`krf_sales_order_items`
 * Path: /readytoload/dispatch_schedule.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

// Session & Authentication Verification
if (!isset($_SESSION['sessionid']) || $_SESSION['sessionid'] !== session_id()) {
    $protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
    $prefix = ($_SERVER['HTTP_HOST'] == "localhost") ? $rooturl . "/krf_amanaccounting/" : $rooturl . "/";
    $requested_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    echo "<script>
        try {
            if (" . json_encode($requested_url) . " && !" . json_encode($requested_url) . ".includes('loginCell') && !" . json_encode($requested_url) . ".includes('logout')) {
                localStorage.setItem('last_active_module_url', " . json_encode($requested_url) . ");
            }
        } catch(e){}
        top.location.href = '" . $prefix . "loginCell/index.php';
    </script>";
    exit;
}

if (isset($_GET['dz'])) {
    require_once(__DIR__ . '/../connection.php');
} else {
    require_once(__DIR__ . '/../header.php');
}

$loginroleid = isset($loginroleid) ? (int)$loginroleid : (int)($_SESSION['loginroleid'] ?? 4);
$loginname   = $_SESSION['loginname'] ?? $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Client Dispatch Schedule | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

  <!-- Modern Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  
  <!-- FontAwesome & Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <!-- Dedicated Module Styling -->
  <link rel="stylesheet" href="css/dispatch_schedule.css?v=<?= time() ?>">
</head>
<body>

<div class="dispatch-page-wrap">

  <!-- 1. Hero Header & Quick Navigation -->
  <div class="dispatch-hero">
    <div class="dispatch-hero-title">
      <i class="fa fa-calendar-alt"></i>
      <div>
        <h1>Client Dispatch Schedule</h1>
        <p>Grouped by Dispatch Date & Client ID &bull; <span id="heroScopeSubtitle">Current Week Allocated Orders</span></p>
      </div>
    </div>
    
    <div class="dispatch-hero-nav">
      <button type="button" class="btn-hero-nav" onclick="loadDispatchSchedule();" title="Refresh list">
        <i class="fa fa-sync-alt"></i> Refresh
      </button>
    </div>
  </div>

  <!-- 2. Status Summary Cards (Compact, Interactive Filters with Animated Respective Borders) -->
  <div class="summary-cards-grid">
    
    <!-- 1. Ready to Load Orders (Default Active: allocated, readytoload) -->
    <div class="summary-card card-readytoload active" data-status="readytoload" onclick="setStatusFilter('readytoload');" role="button" tabindex="0" title="Click to filter Ready to Load orders (allocated, readytoload)">
      <div class="summary-card-inner">
        <div class="summary-card-top">
          <div class="summary-icon-title-wrap">
            <div class="summary-icon-box icon-readytoload">
              <i class="fa fa-truck-loading"></i>
            </div>
            <span class="summary-title-text">Ready to Load</span>
          </div>
          <div class="summary-badge-wrap">
            <span class="active-pulse-dot"></span>
            <span class="summary-status-tag tag-readytoload">Ready To Load</span>
          </div>
        </div>
        <div class="summary-card-main">
          <span class="summary-val-number" id="sumReadyToLoadOrders">0</span>
          <span class="summary-val-unit">Orders</span>
        </div>
        <div class="summary-card-footer">
          <span class="summary-sub-stat" title="Ready to Load Plants">
            <i class="fa fa-seedling text-warning"></i> <strong id="sumReadyToLoadPlants">0</strong> plants
          </span>
          <span class="summary-sub-divider">•</span>
          <span class="summary-sub-stat" title="Ready to Load Trays">
            <i class="fa fa-boxes text-warning"></i> <strong id="sumReadyToLoadTrays">0</strong> trays
          </span>
          <span class="summary-sub-divider">•</span>
          <span class="summary-sub-stat" title="Distinct Clients">
            <i class="fa fa-users text-secondary"></i> <strong id="sumReadyToLoadClients">0</strong> clients
          </span>
        </div>
      </div>
    </div>

    <!-- 2. Ready to Dispatch Orders (readytodispatch, loaded, partially loaded) -->
    <div class="summary-card card-readytodispatch" data-status="readytodispatch" onclick="setStatusFilter('readytodispatch');" role="button" tabindex="0" title="Click to filter Ready to Dispatch orders (readytodispatch, loaded)">
      <div class="summary-card-inner">
        <div class="summary-card-top">
          <div class="summary-icon-title-wrap">
            <div class="summary-icon-box icon-readytodispatch">
              <i class="fa fa-truck-fast"></i>
            </div>
            <span class="summary-title-text">Ready to Dispatch</span>
          </div>
          <div class="summary-badge-wrap">
            <span class="active-pulse-dot"></span>
            <span class="summary-status-tag tag-readytodispatch">Ready To Dispatch</span>
          </div>
        </div>
        <div class="summary-card-main">
          <span class="summary-val-number" id="sumReadyToDispatchOrders">0</span>
          <span class="summary-val-unit">Orders</span>
        </div>
        <div class="summary-card-footer">
          <span class="summary-sub-stat" title="Ready to Dispatch Plants">
            <i class="fa fa-seedling text-info"></i> <strong id="sumReadyToDispatchPlants">0</strong> plants
          </span>
          <span class="summary-sub-divider">•</span>
          <span class="summary-sub-stat" title="Ready to Dispatch Trays">
            <i class="fa fa-boxes text-info"></i> <strong id="sumReadyToDispatchTrays">0</strong> trays
          </span>
          <span class="summary-sub-divider">•</span>
          <span class="summary-sub-stat" title="Distinct Clients">
            <i class="fa fa-users text-secondary"></i> <strong id="sumReadyToDispatchClients">0</strong> clients
          </span>
        </div>
      </div>
    </div>

    <!-- 3. Dispatched Orders -->
    <div class="summary-card card-dispatched" data-status="dispatched" onclick="setStatusFilter('dispatched');" role="button" tabindex="0" title="Click to filter Dispatched orders">
      <div class="summary-card-inner">
        <div class="summary-card-top">
          <div class="summary-icon-title-wrap">
            <div class="summary-icon-box icon-dispatched">
              <i class="fa fa-circle-check"></i>
            </div>
            <span class="summary-title-text">Dispatched</span>
          </div>
          <div class="summary-badge-wrap">
            <span class="active-pulse-dot"></span>
            <span class="summary-status-tag tag-dispatched">Dispatched</span>
          </div>
        </div>
        <div class="summary-card-main">
          <span class="summary-val-number" id="sumDispatchedOrders">0</span>
          <span class="summary-val-unit">Orders</span>
        </div>
        <div class="summary-card-footer">
          <span class="summary-sub-stat" title="Dispatched Plants">
            <i class="fa fa-seedling text-success"></i> <strong id="sumDispatchedPlants">0</strong> plants
          </span>
          <span class="summary-sub-divider">•</span>
          <span class="summary-sub-stat" title="Dispatched Trays">
            <i class="fa fa-boxes text-success"></i> <strong id="sumDispatchedTrays">0</strong> trays
          </span>
          <span class="summary-sub-divider">•</span>
          <span class="summary-sub-stat" title="Distinct Clients">
            <i class="fa fa-users text-secondary"></i> <strong id="sumDispatchedClients">0</strong> clients
          </span>
        </div>
      </div>
    </div>

    <!-- 4. All Allocated Orders (Displayed in the last, after Dispatched summary card) -->
    <div class="summary-card card-all" data-status="all" onclick="setStatusFilter('all');" role="button" tabindex="0" title="Click to view all allocated orders">
      <div class="summary-card-inner">
        <div class="summary-card-top">
          <div class="summary-icon-title-wrap">
            <div class="summary-icon-box icon-all">
              <i class="fa fa-layer-group"></i>
            </div>
            <span class="summary-title-text">All Allocated</span>
          </div>
          <div class="summary-badge-wrap">
            <span class="active-pulse-dot"></span>
            <span class="summary-status-tag tag-all">All</span>
          </div>
        </div>
        <div class="summary-card-main">
          <span class="summary-val-number" id="sumAllOrders">0</span>
          <span class="summary-val-unit">Orders</span>
        </div>
        <div class="summary-card-footer">
          <span class="summary-sub-stat" title="Total Scheduled Plants">
            <i class="fa fa-seedling text-indigo"></i> <strong id="sumAllPlants">0</strong> plants
          </span>
          <span class="summary-sub-divider">•</span>
          <span class="summary-sub-stat" title="Total Estimated Trays">
            <i class="fa fa-boxes text-indigo"></i> <strong id="sumAllTrays">0</strong> trays
          </span>
          <span class="summary-sub-divider">•</span>
          <span class="summary-sub-stat" title="Distinct Clients">
            <i class="fa fa-users text-secondary"></i> <strong id="sumAllClients">0</strong> clients
          </span>
        </div>
      </div>
    </div>

  </div>

  <!-- 3. Filter & Search Toolbar -->
  <div class="filter-card">
    <div class="filter-row-top">
      <div class="d-flex align-items-center flex-wrap" style="gap: 0.65rem;">
        <span class="filter-label mb-0" style="font-size: 0.82rem;"><i class="fa fa-calendar-alt mr-1"></i> Date View:</span>
        <div class="scope-pill-group">
          <button type="button" class="scope-pill-btn active" data-scope="current_week" onclick="setScope('current_week');" title="Current Week (Monday - Sunday)">
            <i class="fa fa-calendar-week mr-1"></i> Current Week (Default)
          </button>
          <button type="button" class="scope-pill-btn" data-scope="today" onclick="setScope('today');">
            <i class="fa fa-calendar-day mr-1"></i> Today
          </button>
          <button type="button" class="scope-pill-btn" data-scope="upcoming" onclick="setScope('upcoming');">
            <i class="fa fa-calendar-plus mr-1"></i> Upcoming
          </button>
          <button type="button" class="scope-pill-btn" data-scope="present_to_past" onclick="setScope('present_to_past');">
            <i class="fa fa-history mr-1"></i> Present &amp; Past
          </button>
          <button type="button" class="scope-pill-btn" data-scope="all" onclick="setScope('all');">
            <i class="fa fa-list mr-1"></i> All Dates
          </button>
        </div>
      </div>
    </div>

    <div class="filter-grid">
      <!-- 1. Location Filter -->
      <div class="filter-col-location">
        <label class="filter-label"><i class="fa fa-map-marker-alt mr-1 text-danger"></i> Location: <span class="text-danger">*</span></label>
        <select id="filterLocation" class="filter-input filter-select" onchange="onLocationChange();">
          <option value="">-- Choose Location --</option>
        </select>
      </div>

      <!-- 2. Polyhouse Filter -->
      <div class="filter-col-polyhouse">
        <label class="filter-label"><i class="fa fa-warehouse mr-1 text-primary"></i> Polyhouse: <span class="text-danger">*</span></label>
        <select id="filterPolyhouse" class="filter-input filter-select" onchange="onPolyhouseChange();" disabled>
          <option value="">-- Choose Location First --</option>
        </select>
      </div>

      <!-- 3. Search Input -->
      <div>
        <label class="filter-label"><i class="fa fa-search mr-1"></i> Search Filter:</label>
        <input type="text" id="filterSearch" class="filter-input" placeholder="Search Client Name, ID, Phone, PSO/SO, Crop, District...">
      </div>

      <!-- 4. From Date -->
      <div>
        <label class="filter-label"><i class="fa fa-calendar-alt mr-1"></i> From Date:</label>
        <input type="date" id="filterFromDate" class="filter-input">
      </div>

      <!-- 5. To Date -->
      <div>
        <label class="filter-label"><i class="fa fa-calendar-alt mr-1"></i> To Date:</label>
        <input type="date" id="filterToDate" class="filter-input">
      </div>

      <!-- Buttons -->
      <div>
        <button type="button" class="btn-filter-apply" onclick="loadDispatchSchedule();">
          <i class="fa fa-filter"></i> Apply
        </button>
      </div>

      <div>
        <button type="button" class="btn-filter-reset" onclick="resetFilters();" title="Reset all filters">
          <i class="fa fa-undo"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Active Status Filter Notification Pill / Banner -->
  <div id="activeFilterNotice" class="active-filter-bar" style="display: none;">
    <div class="active-filter-left">
      <span class="active-filter-dot"></span>
      <span class="active-filter-label">Filtered by Summary: <strong id="activeFilterName">Ready to Load Order</strong></span>
      <span class="active-filter-badge" id="activeFilterCountBadge">0 Orders</span>
    </div>
    <button type="button" class="btn-clear-status" onclick="setStatusFilter('all');" title="Show all allocated orders">
      <i class="fa fa-times-circle mr-1"></i> Show All Allocated
    </button>
  </div>

  <!-- 4. Grouped Dispatch Schedule Container -->
  <div id="scheduleContainer">
    <!-- Rendered via JavaScript -->
  </div>

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/dispatch_schedule.js?v=<?= time() ?>"></script>

</body>
</html>
