<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loginid = "";
$loginroleid = "";
$companyid = "";
$subcompanyid = "";
$projectid = "";
$loginname = "";
$key = $_GET['k'] ?? '';
$mdid = $_GET['mdid'] ?? '';
$dz = $_GET['dz'] ?? '';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? "https://" : "http://";
$url = basename($_SERVER["PHP_SELF"]);
$rooturl = $protocol . $_SERVER['HTTP_HOST'];
if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "192.168.1.65") {
  $prefix = $rooturl . "/krf_amanaccounting/";
} else {
  $prefix = $rooturl . "/";
}
$rooturl = $prefix;

$use_standalone_layout = false;

if (!empty($dz) || empty($key)) {
  $use_standalone_layout = true;
  if (file_exists(dirname(__DIR__) . '/connection.php')) {
    require_once(dirname(__DIR__) . '/connection.php');
  }
  if (file_exists(dirname(__DIR__) . '/functions/function_master.php')) {
    include_once(dirname(__DIR__) . '/functions/function_master.php');
  }
  if (file_exists(__DIR__ . '/functions/orders_functions.php')) {
    include_once(__DIR__ . '/functions/orders_functions.php');
  }

  if (!empty($key)) {
    $key_decoded = base64url_decode($key);
    $key_parts = explode("|", $key_decoded);
    $checkkey = base64url_decode($key_parts[count($key_parts) - 1] ?? '');
    $actkey = "";
    for ($i = 0; $i < count($key_parts) - 1; $i++) {
      $actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
    }
    if ($actkey == $checkkey) {
      $loginid = $key_parts[0] ?? '';
      $loginroleid = $key_parts[1] ?? '';
      $companyid = $key_parts[2] ?? '';
      $subcompanyid = $key_parts[3] ?? '';
      $projectid = $key_parts[4] ?? '';
    }
  }

  if (empty($loginid) && !empty($_SESSION['loginid'])) $loginid = $_SESSION['loginid'];
  if (empty($loginroleid) && !empty($_SESSION['loginroleid'])) $loginroleid = $_SESSION['loginroleid'];
  if (empty($companyid) && !empty($_SESSION['companyid'])) $companyid = $_SESSION['companyid'];
  if (empty($subcompanyid) && !empty($_SESSION['subcompanyid'])) $subcompanyid = $_SESSION['subcompanyid'];
  if (empty($projectid) && !empty($_SESSION['projectid'])) $projectid = $_SESSION['projectid'];
  if (empty($loginname) && !empty($_SESSION['loginname'])) $loginname = $_SESSION['loginname'];
} else {
  $hide_heading = true;
  require('../header.php');
  if (file_exists(__DIR__ . '/functions/orders_functions.php')) {
    include_once(__DIR__ . '/functions/orders_functions.php');
  }
}

if (empty($projectid)) $projectid = $_SESSION['projectid'] ?? 17;
if (empty($companyid)) $companyid = $_SESSION['companyid'] ?? 19;
if (empty($subcompanyid)) $subcompanyid = $_SESSION['subcompanyid'] ?? 12;

$history = getAdvancePaymentTransactions($krfconn, $companyid, $subcompanyid, $projectid);

// Function to generate unique background color based on UTR
function getUTRColor($utr) {
  if (!$utr) return 'transparent';
  $hash = md5($utr);
  $r = hexdec(substr($hash, 0, 2)) % 60 + 195;
  $g = hexdec(substr($hash, 2, 2)) % 60 + 195;
  $b = hexdec(substr($hash, 4, 2)) % 60 + 195;
  return "rgba($r, $g, $b, 0.2)";
}
?>

<?php if ($use_standalone_layout): ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Receipt History | Karjee Farm</title>

  <!-- Google Fonts & Bootstrap -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<?php else: ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php endif; ?>

<script>
  if (window.self !== window.top) {
    document.documentElement.classList.add('is-iframe');
    if (document.body) document.body.classList.add('is-iframe');
  }
</script>

<style>
  body {
    font-family: 'Inter', sans-serif;
    background: #f8fafc;
    color: #334155;
  }
  html.is-iframe .navbar,
  html.is-iframe nav.navbar,
  html.is-iframe .navbar-premium,
  html.is-iframe .company-header-strip,
  body.is-iframe .navbar,
  body.is-iframe nav.navbar,
  body.is-iframe .navbar-premium,
  body.is-iframe .company-header-strip,
  .is-iframe .navbar,
  .is-iframe nav.navbar,
  .is-iframe .navbar-premium,
  .is-iframe .company-header-strip {
    display: none !important;
  }
  .payment-history-card {
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    background: #ffffff;
    overflow: hidden;
  }
  .utr-group-tag {
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 4px;
    background: rgba(0,0,0,0.06);
    color: #475569;
    font-family: monospace;
  }
  .status-badge-allocated {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
    border-radius: 20px;
    padding: 3px 10px;
    font-size: 0.78rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
  }
  .status-badge-unallocated {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
    border-radius: 20px;
    padding: 3px 10px;
    font-size: 0.78rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
  }
  .btn-action-round {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    text-decoration: none;
  }
  .btn-action-round:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  }
  .payment-history-card table.premium-table thead th {
    background: #f1f5f9 !important;
    color: #334155 !important;
    font-weight: 700 !important;
    font-size: 0.82rem !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e2e8f0 !important;
    padding: 14px 16px !important;
  }
  .payment-history-card table.premium-table tbody td {
    padding: 14px 16px !important;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9 !important;
    font-size: 0.88rem !important;
  }
  .payment-history-card table.premium-table thead th:first-child,
  .payment-history-card table.premium-table tbody td:first-child {
    padding-left: 24px !important;
  }
  .payment-history-card table.premium-table thead th:last-child,
  .payment-history-card table.premium-table tbody td:last-child {
    padding-right: 24px !important;
  }
</style>

<?php if ($use_standalone_layout): ?>
</head>
<body>
<?php endif; ?>

<div class="container-fluid px-3 px-md-4 py-4" style="max-width: 1440px; margin: 0 auto;">
  
  <!-- Filter Section -->
  <div class="card payment-history-card mb-4">
    <div class="card-body p-3 p-md-4">
      <form id="filterForm" method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="mdid" value="<?php echo htmlspecialchars($mdid); ?>">
        <input type="hidden" name="k" value="<?php echo htmlspecialchars($key); ?>">
        <?php if(!empty($dz)): ?><input type="hidden" name="dz" value="<?php echo htmlspecialchars($dz); ?>"><?php endif; ?>

        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label small font-weight-bold fw-bold text-secondary mb-1">
            <i class="fa fa-user text-primary mr-1 me-1"></i> Client Name
          </label>
          <input type="text" name="filter_client" class="form-control form-control-sm auto-filter" style="height: 38px;"
                 placeholder="Search client name..." value="<?php echo htmlspecialchars($_GET['filter_client']??''); ?>">
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label small font-weight-bold fw-bold text-secondary mb-1">
            <i class="fa fa-hashtag text-secondary mr-1 me-1"></i> UTR / Ref No
          </label>
          <input type="text" name="filter_utr" class="form-control form-control-sm auto-filter" style="height: 38px;"
                 placeholder="Search UTR..." value="<?php echo htmlspecialchars($_GET['filter_utr']??''); ?>">
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label small font-weight-bold fw-bold text-secondary mb-1">
            <i class="fa fa-tag text-info mr-1 me-1"></i> Allocation Status
          </label>
          <select name="filter_status" class="form-select form-control form-control-sm auto-filter" style="height: 38px;">
            <option value="">All Status</option>
            <option value="allocated" <?php echo ($_GET['filter_status']??'') == 'allocated' ? 'selected' : ''; ?>>Allocated</option>
            <option value="unallocated" <?php echo ($_GET['filter_status']??'') == 'unallocated' ? 'selected' : ''; ?>>Unallocated</option>
          </select>
        </div>

        <div class="col-12 col-sm-6 col-md-3 d-flex align-items-center gap-2">
          <button type="button" onclick="resetFilters()" class="btn btn-outline-secondary btn-sm px-3 rounded" style="height: 38px;">
            <i class="fa fa-undo mr-1 me-1"></i> Reset Filters
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Table Card -->
  <div class="card payment-history-card">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
      <div>
        <h5 class="mb-0 font-weight-bold fw-bold text-primary d-flex align-items-center gap-2">
          <i class="fa fa-history mr-1.5 me-1.5"></i> Payment Receipt History
        </h5>
        <small class="text-muted">Viewing all advance receipts recorded across provisional orders.</small>
      </div>
      
      <?php
        $nav_extra = (!empty($dz) ? '&dz=' . urlencode($dz) : '') . (isset($_GET['iframe']) ? '&iframe=true' : '');
      ?>
      <div class="d-flex align-items-center gap-2">
        <a href="paymentsummary.php?mdid=<?php echo urlencode($mdid); ?>&k=<?php echo urlencode($key); ?><?php echo $nav_extra; ?>" class="btn btn-outline-success btn-sm rounded shadow-sm px-3 py-1.5">
          <i class="fa fa-chart-pie mr-1 me-1"></i> Payment Summary
        </a>
        <a href="receivedpayment_verification.php?mdid=<?php echo urlencode($mdid); ?>&k=<?php echo urlencode($key); ?><?php echo $nav_extra; ?>" class="btn btn-outline-primary btn-sm rounded shadow-sm px-3 py-1.5">
          <i class="fa fa-shield-alt mr-1 me-1"></i> Payment Verification
        </a>
        <a href="paymentadvicemanual.php?mdid=<?php echo urlencode($mdid); ?>&k=<?php echo urlencode($key); ?><?php echo $nav_extra; ?>" class="btn btn-primary btn-sm rounded shadow-sm px-3 py-1.5">
          <i class="fa fa-plus-circle mr-1 me-1"></i> New Payment Advice
        </a>
      </div>
    </div>
    
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table premium-table table-hover mb-0" id="paymentHistoryTbl">
          <thead>
            <tr>
              <th>Client Details</th>
              <th>Payment Info</th>
              <th>Allocation</th>
              <th>Order / Product Details</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($history) > 0): ?>
              <?php foreach ($history as $row): 
                $utrColor = getUTRColor($row['utrnumber']);
                $isAllocated = !empty($row['invoiceno']);
                $status_val = $isAllocated ? 'allocated' : 'unallocated';
              ?>
                <tr class="payment-row" 
                    data-client="<?php echo strtolower($row['clientname']); ?>" 
                    data-utr="<?php echo strtolower($row['utrnumber']); ?>" 
                    data-status="<?php echo $status_val; ?>"
                    style="background-color: <?php echo $utrColor; ?>;">
                  <td>
                    <div class="font-weight-bold fw-bold text-dark"><?php echo htmlspecialchars($row['clientname']); ?></div>
                    <small class="text-muted"><i class="fa fa-phone mr-1 me-1"></i> <?php echo htmlspecialchars($row['clientmobileno']??'N/A'); ?></small>
                  </td>
                  <td>
                    <div class="text-success font-weight-bold fw-bold">&#8377;<?php echo number_format($row['advanceamount'], 2); ?></div>
                    <small class="d-block text-muted">
                      <i class="fa fa-calendar-alt mr-1 me-1"></i> <?php echo date('d M Y', strtotime($row['paymentdate'])); ?>
                    </small>
                    <?php if($row['utrnumber']): ?>
                      <span class="utr-group-tag mt-1 d-inline-block">UTR: <?php echo htmlspecialchars($row['utrnumber']); ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($isAllocated): ?>
                      <span class="status-badge-allocated">
                        <i class="fa fa-check-circle mr-1 me-1"></i> Allocated
                      </span>
                      <small class="d-block mt-1 text-muted">On: <?php echo date('d M, H:i', strtotime($row['allocated_datetime'])); ?></small>
                    <?php else: ?>
                      <span class="status-badge-unallocated">
                        <i class="fa fa-clock mr-1 me-1"></i> Unallocated
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($isAllocated): ?>
                      <div class="font-weight-bold fw-bold text-dark">Order: #<?php echo htmlspecialchars($row['invoiceno']); ?></div>
                      <small class="d-block text-muted">
                        <i class="fa fa-seedling text-success mr-1 me-1"></i> <?php echo htmlspecialchars($row['product_name'] ?? 'N/A'); ?> (Qty: <?php echo $row['quantity'] ?? 0; ?>)
                      </small>
                    <?php else: ?>
                      <span class="text-muted fst-italic small">No order linked</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <div class="btn-group gap-1">
                      <?php if ($isAllocated): ?>
                        <button class="btn btn-action-round text-danger" title="Remove Allocation" 
                                onclick="removePaymentAllocation(<?php echo $row['sno']; ?>)">
                          <i class="fa fa-unlink"></i>
                        </button>
                      <?php else: ?>
                        <a href="UnAllocatedToAllocate.php?mdid=<?php echo htmlspecialchars($mdid); ?>" class="btn btn-action-round text-primary" title="Reallocate">
                          <i class="fa fa-link"></i>
                        </a>
                      <?php endif; ?>
                      
                      <?php if($row['receiptpath']): ?>
                        <a href="<?php echo $prefix . $row['receiptpath']; ?>" target="_blank" class="btn btn-action-round text-info" title="View Receipt">
                          <i class="fa fa-file-invoice"></i>
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="fa fa-receipt fa-3x mb-3 opacity-50"></i><br>
                  No payment history found for this project.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php if ($use_standalone_layout): ?>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
<?php else: ?>
  <?php if (file_exists('../footer.php')) include('../footer.php'); ?>
<?php endif; ?>

<script>
function removePaymentAllocation(sno) {
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Are you sure?',
      text: "This will unlink the payment from the order. You will need to reallocate it later.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, Unlink it!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        doRemovePaymentAllocation(sno);
      }
    });
  } else {
    if (confirm('Are you sure? This will unlink the payment from the order.')) {
      doRemovePaymentAllocation(sno);
    }
  }
}

function doRemovePaymentAllocation(sno) {
  $.ajax({
    url: 'modal/paymentadvice_modal.php?action=RemoveAllocation',
    type: 'POST',
    data: { sno: sno },
    dataType: 'JSON',
    success: function(res) {
      if (res.status === 'success') {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Success', res.message, 'success').then(() => {
            location.reload();
          });
        } else {
          alert(res.message);
          location.reload();
        }
      } else {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Error', res.message, 'error');
        } else {
          alert(res.message);
        }
      }
    }
  });
}

function resetFilters() {
  $('.auto-filter').val('');
  $('.payment-row').show();
}

// Client-side jQuery Filtering
$(document).ready(function() {
  $('.auto-filter').on('input change', function() {
    const clientSearch = $('input[name="filter_client"]').val().toLowerCase().trim();
    const utrSearch = $('input[name="filter_utr"]').val().toLowerCase().trim();
    const statusSearch = $('select[name="filter_status"]').val();

    $('.payment-row').each(function() {
      const row = $(this);
      const rowClient = row.attr('data-client') || '';
      const rowUtr = row.attr('data-utr') || '';
      const rowStatus = row.attr('data-status') || '';

      let show = true;

      if (clientSearch && !rowClient.includes(clientSearch)) show = false;
      if (utrSearch && !rowUtr.includes(utrSearch)) show = false;
      if (statusSearch && rowStatus !== statusSearch) show = false;

      if (show) {
        row.show();
      } else {
        row.hide();
      }
    });
  });

  // Prevent form submission
  $('#filterForm').on('submit', function(e) {
    e.preventDefault();
  });
});
</script>
